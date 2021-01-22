<?php

namespace Dynamic\Shopify\Page;

use Dynamic\Shopify\Model\ShopifyFile;
use Dynamic\Shopify\Task\ShopifyImportTask;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataObject;

class ShopifyCollection extends \Page
{
    private static $table_name = 'ShopifyCollection';

    private static $db = [
        'ShopifyID' => 'Varchar',
    ];

    private static $data_map = [
        'id' => 'ShopifyID',
        'handle' => 'URLSegment',
        'title' => 'Title',
        'body_html' => 'Content',
        'updated_at' => 'LastEdited',
        'created_at' => 'Created',
    ];

    private static $has_one = [
        'File' => ShopifyFile::class
    ];

    private static $many_many = [
        'Products' => ShopifyProduct::class,
    ];


    private static $many_many_extraFields = [
        'Products' => [
            'SortValue' => 'Varchar',
            'Position' => 'Int',
            'Featured' => 'Boolean',
            'Imported' => 'Boolean'
        ],
    ];

    private static $owns = [
        'File'
    ];

    private static $indexes = [
        'ShopifyID' => true,
    ];

    private static $summary_fields = [
        'File.CMSThumbnail' => 'Image',
        'Title',
        'ShopifyID'
    ];

    public function getCMSFields()
    {
        $self =& $this;
        $this->beforeUpdateCMSFields(function (FieldList $fields) use ($self) {
            $fields->addFieldsToTab('Root.Main', [
                ReadonlyField::create('Title'),
                ReadonlyField::create('URLSegment'),
                ReadonlyField::create('ShopifyID'),
                ReadonlyField::create('Content'),
                UploadField::create('Image')->performReadonlyTransformation(),
            ]);

            $fields->addFieldsToTab('Root.Products', [
                GridField::create('Products', 'Products', $this->Products(), GridFieldConfig_RecordViewer::create())
            ]);
        });

        return parent::getCMSFields();
    }

    /**
     * Creates a new Shopify Collection from the given data
     * but does not publish it
     *
     * @param $shopifyCollection
     * @return ProductCollection
     * @throws \SilverStripe\ORM\ValidationException
     */
    public static function findOrMakeFromShopifyData($shopifyCollection)
    {
        if (!$collection = self::getByShopifyID($shopifyCollection->id)) {
            $collection = self::create();
        }

        $map = self::config()->get('data_map');
        ShopifyImportTask::loop_map($map, $collection, $shopifyCollection);

        if ($collection->isChanged()) {
            $collection->write();
        }

        return $collection;
    }

    /**
     * @param $shopifyId
     *
     * @return ProductCollection
     */
    public static function getByShopifyID($shopifyId)
    {
        return DataObject::get_one(self::class, ['ShopifyID' => $shopifyId]);
    }

    public static function getByURLSegment($urlSegment)
    {
        return DataObject::get_one(self::class, ['URLSegment' => $urlSegment]);
    }
}
