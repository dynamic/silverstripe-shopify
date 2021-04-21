<?php

namespace Dynamic\Shopify\Model;

use Dynamic\Shopify\Client\ShopifyClient;
use Dynamic\Shopify\Page\ShopifyCollection;
use Dynamic\Shopify\Page\ShopifyProduct;
use Dynamic\Shopify\Task\ShopifyImportTask;
use GuzzleHttp\Client;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Assets\Image;
use SilverStripe\Dev\Debug;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\Read;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\HasManyList;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;
use SilverStripe\View\HTML;

/**
 * Class ShopifyFile
 * @package Dynamic\Shopify\Model
 *
 * @property string ShopifyID
 * @property string Type
 * @property string PreviewSrc
 * @property int SortOrder
 *
 * @property int ProductID
 * @method ShopifyProduct Product
 * @property int CollectionID
 * @method ShopifyCollection Collection
 * @property int VariantID
 * @method ShopifyVariant Variant
 * @property int OriginalSourceID
 * @method ShopifyFileSource OriginalSource
 *
 * @method HasManyList|ShopifyVariant[] Variants
 * @method HasManyList|ShopifyFileSource[] Sources
 *
 * @mixin Versioned
 */
class ShopifyFile extends DataObject
{

    const VIDEO = 'VIDEO';
    const EXTERNAL_VIDEO = 'EXTERNAL_VIDEO';
    const MODEL_3D = 'MODEL_3D';
    const IMAGE = 'IMAGE';

    /**
     * @var string
     * @config
     */
    private static $table_name = 'ShopifyFile';

    /**
     * @var string[]
     * @config
     */
    private static $extensions = [
        Versioned::class,
    ];

    /**
     * @var string[]
     * @config
     */
    private static $db = [
        'ShopifyID' => 'Varchar',
        'Type' => 'Varchar',
        'PreviewSrc' => 'Varchar(255)',
        'SortOrder' => 'Int',
    ];

    /**
     * @var string[]
     * @config
     */
    private static $data_map = [
        'id' => 'ShopifyID',
        'mediaContentType' => 'Type',
        'altText' => 'Title',
        'preview' => [
            'image' => [
                'originalSrc' => 'PreviewSrc',
            ],
        ],
        'position' => 'SortOrder',
    ];

    /**
     * @var string[]
     * @config
     */
    private static $has_one = [
        'Product' => ShopifyProduct::class,
        'Collection' => ShopifyCollection::class,
        'Variant' => ShopifyVariant::class,
        'OriginalSource' => ShopifyFileSource::class,
    ];

    /**
     * @var string[]
     * @config
     */
    private static $has_many = [
        'Variants' => ShopifyVariant::class,
        'Sources' => ShopifyFileSource::class,
    ];

    /**
     * @var string[]
     * @config
     */
    private static $cascade_deletes = [
        'OriginalSource',
        'Sources',
    ];

    /**
     * @var bool[]
     * @config
     */
    private static $indexes = [
        'ShopifyID' => true
    ];

    /**
     * @var string[]
     * @config
     */
    private static $summary_fields = [
        'CMSThumbnail' => 'Image',
        'Title',
        'ShopifyID'
    ];

    /**
     * @var string[]
     * @config
     */
    private static $searchable_fields = [
        'Title',
        'ShopifyID',
    ];

    /**
     * @var string
     * @config
     */
    private static $default_sort = 'SortOrder ASC';

    /**
     * @return FieldList
     */
    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            foreach ($fields as $field) {
                $field->setReadonly(true);
            }

            $fields->addFieldToTab('Root.Main', ReadonlyField::create('ShopifyID'));

            $fields->addFieldsToTab('Root.Sources', [
                ReadonlyField::create('PreviewSrc'),
                ReadonlyField::create('Width'),
                ReadonlyField::create('Height'),
                ReadonlyField::create('SortOrder'),
            ]);

            $fields->fieldByName('Root.Sources.Sources')
                ->getConfig()->removeComponentsByType(GridFieldFilterHeader::class);
        });

        return parent::getCMSFields();
    }

    /**
     * Creates a new Shopify Image from the given data
     *
     * @param $shopifyFile
     * @return ShopifyFile
     * @throws \SilverStripe\ORM\ValidationException
     */
    public static function findOrMakeFromShopifyData($shopifyFile)
    {
        if (!$file = self::getByShopifyID($shopifyFile->id)) {
            $file = self::create();
        }
        $map = self::config()->get('data_map');
        ShopifyImportTask::loop_map($map, $file, $shopifyFile);
        if (!$file->isInDB()) {
            $file->write();
        }

        $originalSource = $file->OriginalSource() ?: ShopifyFileSource::create();
        $originalSource->FileID = $file->ID;
        if (!$shopifyFile->offsetExists('mediaContentType')) {
            $originalSource->URL = $shopifyFile->originalSrc;
            $originalSource->Width = $shopifyFile->width;
            $originalSource->Height = $shopifyFile->height;
            $file->Type = static::IMAGE;
        } else {
            if ($shopifyFile->mediaContentType === static::IMAGE) {
                $originalSource->URL = $shopifyFile->image->originalSrc;
                $originalSource->Width = $shopifyFile->image->width;
                $originalSource->Height = $shopifyFile->image->height;
            } else if ($shopifyFile->mediaContentType === static::EXTERNAL_VIDEO) {
                $originalSource->URL = $shopifyFile->embeddedUrl;
            } else { // Video & 3d model
                $originalSource->URL = $shopifyFile->originalSource->url;
                $originalSource->Format = $shopifyFile->originalSource->format;
                $originalSource->MimeType = $shopifyFile->originalSource->mimeType;
                if ($shopifyFile->mediaContentType === static::VIDEO) {
                    $originalSource->Width = $shopifyFile->originalSource->width;
                    $originalSource->Height = $shopifyFile->originalSource->height;
                }
            }
        }

        if ($originalSource->isChanged()) {
            $originalSource->write();
        }
        $file->OriginalSourceID = $originalSource->ID;

        if ( $shopifyFile->offsetExists('mediaContentType') &&
            ($shopifyFile->mediaContentType === static::VIDEO || $shopifyFile->mediaContentType === static::MODEL_3D)
        ) {
            foreach ($shopifyFile->sources as $source) {
                $filter = [
                    'Format' => $source->format,
                ];
                if ($shopifyFile->mediaContentType === static::VIDEO) {
                    $filter['Height'] = $source->height;
                }
                $sourceFile = $file->Sources()->filter($filter)->first() ?: ShopifyFileSource::create();
                $sourceFile->Format = $source->format;
                $sourceFile->MimeType = $source->mimeType;
                $sourceFile->URL = $source->url;
                if ($shopifyFile->mediaContentType === static::VIDEO) {
                    $sourceFile->Height = $source->height;
                    $sourceFile->Width = $source->width;
                }

                if ($sourceFile->isChanged()) {
                    $sourceFile->write();
                }
                $file->Sources()->add($sourceFile);
            }
        }

        if ($file->isChanged()) {
            $file->write();
            if ($file->isPublished()) {
                $file->publishSingle();
            }
        }
        return $file;
    }

    /**
     * @param $shopifyId
     * @return DataObject|null
     */
    public static function getByShopifyID($shopifyId)
    {
        return DataObject::get_one(self::class, ['ShopifyID' => $shopifyId]);
    }

    /**
     * @param string|null $format
     * @param int $height
     * @return ShopifyFileSource|null
     */
    public function getFormat($format = null, $height = 0)
    {
        if ($format === null) {
            return $this->OriginalSource();
        }
        $filter = [
            'Format' => $format,
        ];

        if ($height) {
            $filter['Height'] = $height;
        }

        return $this->Sources()->filter($filter)->first();
    }

    /**
     * @param int|string $width
     * @param int|string $height
     * @param string $format
     * @return string
     */
    private function generateTransformedURL($width = '', $height = '', $format = '')
    {
        $pattern = '/(.*)(\.\S+?)(\?.*)/m';
        $dims = sprintf('_%sx%s', $width, $height);
        if ($format !== 'png' && $format !== 'jpg' && $format !== 'webm') {
            $format = '';
        }

        preg_match_all('/.*\.(\S+?)\?.*/m', $this->OriginalSource()->URL, $matches);
        if (count($matches) > 1) {
            if ($matches[1][0] === $format) {
                $format = '';
            }
        }
        if ($format !== '') {
            $format = '.' . $format;
        }

        $subst = '$1' . $dims . '$2' . $format . '$3';
        return preg_replace($pattern, $subst, $this->OriginalSource()->URL);
    }

    /**
     * @param int $width
     * @param int $height
     * @param string $format
     * @return ShopifyFileSource|null
     */
    public function getTransform($width, $height, $format = null)
    {
        if ($this->Type === static::EXTERNAL_VIDEO) {
            return $this->OriginalSource();
        }

        if ($this->Type === static::MODEL_3D) {
            if ($source = $this->Sources()->find('Format', $format)) {
                return $source;
            }
            return $this->OriginalSource();
        }

        if ($this->Type === static::VIDEO) {
            $filter = [
                'Format' => $format,
                'Width' => $width,
                'Height' => $height,
            ];
            if ($source = $this->Sources()->filter($filter)->first()) {
                return $source;
            }
            return $this->OriginalSource();
        }

        if ($this->Type === static::IMAGE) {
            $file = ShopifyFileSource::create();
            $file->Height = $height;
            $file->Width = $width;
            $file->Format = $format;
            $file->URL = $this->generateTransformedURL($width, $height, $format);
            return $file;
        }

        return null;
    }

    /**
     * @return string
     */
    public function getURL()
    {
        return $this->OriginalSource()->URL;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->OriginalSource()->Width;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return $this->OriginalSource()->Height;
    }

    /**
     * @param int $width
     * @param int $height
     * @return DBField
     */
    public function getThumbnail($width, $height)
    {
        return DBField::create_field(
            'HTMLFragment',
            HTML::createTag('img', [
                'src' => $this->PreviewSrc ?: $this->getURL(),
                'width' => $width,
                'height' => $height,
            ])
        );
    }

    /**
     * @return DBField
     */
    public function getCMSThumbnail()
    {
        return $this->getThumbnail(60, 60);
    }
}
