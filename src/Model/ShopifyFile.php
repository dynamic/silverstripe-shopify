<?php

namespace Dynamic\Shopify\Model;

use Dynamic\Shopify\Client\ShopifyClient;
use Dynamic\Shopify\Page\ShopifyCollection;
use Dynamic\Shopify\Page\ShopifyProduct;
use Dynamic\Shopify\Page\ShopifyVariant;
use Dynamic\Shopify\Task\ShopifyImportTask;
use GuzzleHttp\Client;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Assets\Image;
use SilverStripe\Dev\Debug;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\Read;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;

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

            $fields->add(ReadonlyField::create('ShopifyID'));
            $fields->add(ReadonlyField::create('OrginialSrc'));
            $fields->add(ReadonlyField::create('Width'));
            $fields->add(ReadonlyField::create('Height'));
            $fields->add(ReadonlyField::create('SortOrder'));
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
        if ($shopifyFile->mediaContentType === "IMAGE") {
            $originalSource->URL = $shopifyFile->image->originalSrc;
            $originalSource->Width = $shopifyFile->image->width;
            $originalSource->Height = $shopifyFile->image->height;
        } else if ($shopifyFile->mediaContentType === "EXTERNAL_VIDEO") {
            $originalSource->URL = $shopifyFile->embeddedUrl;
        } else { // Video & 3d model
            $originalSource->URL = $shopifyFile->originalSource->url;
            $originalSource->Format = $shopifyFile->originalSource->format;
            $originalSource->MimeType = $shopifyFile->originalSource->mimeType;
            if ($shopifyFile->mediaContentType === "VIDEO") {
                $originalSource->Width = $shopifyFile->originalSource->width;
                $originalSource->Height = $shopifyFile->originalSource->height;
            }
        }

        if ($originalSource->isChanged()) {
            $originalSource->write();
        }
        $file->OriginalSourceID = $originalSource->ID;

        if ($shopifyFile->mediaContentType === "VIDEO" || $shopifyFile->mediaContentType === "MODEL_3D") {
            print_r($shopifyFile->sources);
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
     * @return ShopifyFileSource|null
     */
    public function getFormat($format = null)
    {
        if ($format === null) {
            return $this->OriginalSource();
        }

        return $this->Sources()->find('Format', $format);
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
}
