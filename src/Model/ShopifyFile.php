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
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;

/**
 * Class ShopifyFile
 * @package Dynamic\Shopify\Model
 *
 * @mixin Versioned
 */
class ShopifyFile extends DataObject
{
    /**
     * @var string
     */
    private static $table_name = 'ShopifyFile';

    /**
     * @var string[]
     */
    private static $extensions = [
        Versioned::class,
    ];

    /**
     * @var string[]
     */
    private static $db = [
        'ShopifyID' => 'Varchar',
        'OriginalSrc' => 'Varchar(255)',
        'PreviewSrc' => 'Varchar(255)',
        'SortOrder' => 'Int',
    ];

    /**
     * @var string[]
     */
    private static $data_map = [
        'id' => 'ShopifyID',
        'altText' => 'Title',
        'originalSrc' => 'OriginalSrc',
        'preview' => [
            'image' => [
                'originalSrc' => 'PreviewSrc',
            ],
        ],
        'position' => 'SortOrder',
    ];

    /**
     * @var string[]
     */
    private static $has_one = [
        'Product' => ShopifyProduct::class,
        'Collection' => ShopifyCollection::class,
        'Variant' => ShopifyVariant::class,
    ];

    /**
     * @var string[]
     */
    private static $has_many = [
        'Variants' => ShopifyVariant::class
    ];

    /**
     * @var bool[]
     */
    private static $indexes = [
        'ShopifyID' => true
    ];

    /**
     * @var string[]
     */
    private static $summary_fields = [
        'CMSThumbnail' => 'Image',
        'Title',
        'ShopifyID'
    ];

    /**
     * @var string[]
     */
    private static $searchable_fields = [
        'Title',
        'ShopifyID',
    ];

    /**
     * @var string
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
}
