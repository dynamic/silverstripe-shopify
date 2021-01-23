<?php

namespace Dynamic\Shopify\Page;

use Dynamic\Shopify\Model\ShopifyFile;
use Dynamic\Shopify\Model\ShopifyVariant;
use Dynamic\Shopify\Task\ShopifyImportTask;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBCurrency;

/**
 * Class ShopifyProduct
 * @package Dynamic\Shopify\Page
 */
class ShopifyProduct extends \Page
{
    /**
     * @var string
     */
    private static $table_name = 'ShopifyProduct';

    /**
     * @var string
     */
    private static $currency = 'USD';

    /**
     * @var \false[][][]
     *
     * Set options for the Buy Button display
     */
    private static $options = [
        'product' => [
            'contents' => [
                'title' => false,
                'variantTitle' => false,
                'price' => false,
                'description' => false,
                'quantity' => false,
                'img' => false,
            ]
        ]
    ];

    /**
     * @var string[]
     */
    private static $db = [
        'ShopifyID' => 'Varchar',
        'Vendor' => 'Varchar',
        'ProductType' => 'Varchar',
        'Tags' => 'Varchar',
        'Status' => 'Varchar',
    ];

    /**
     * @var string[]
     *
     * Field mappings from Shopify
     */
    private static $data_map = [
        'id' => 'ShopifyID',
        'title' => 'Title',
        'body_html' => 'Content',
        'vendor' => 'Vendor',
        'product_type' => 'ProductType',
        'created_at' => 'Created',
        'handle' => 'URLSegment',
        'status' => 'Status',
        'updated_at' => 'LastEdited',
        'tags' => 'Tags',
    ];

    /**
     * @var string[]
     */
    private static $has_many = [
        'Variants' => ShopifyVariant::class,
        'Files' => ShopifyFile::class
    ];

    /**
     * @var string[]
     */
    private static $belongs_many_many = [
        'Collections' => ShopifyCollection::class
    ];

    /**
     * @var string[]
     */
    private static $owns = [
        'Files',
    ];

    /**
     * @var string[]
     */
    private static $cascade_deletes = [
        'Variants',
        'Files',
    ];

    /**
     * @var bool[]
     */
    private static $indexes = [
        'ShopifyID' => true,
        'Vendor' => false,
        'ProductType' => false,
    ];

    /**
     * @var string
     */
    private static $default_sort = 'Created DESC';

    /**
     * @var string[]
     */
    private static $summary_fields = [
        'Image.CMSThumbnail' => 'Image',
        'Title',
        'Vendor',
        'ProductType',
        'ShopifyID'
    ];

    /**
     * @var string[]
     */
    private static $searchable_fields = [
        'Title',
        'URLSegment',
        'ShopifyID',
        'Content',
        'Vendor',
        'ProductType',
        'Tags'
    ];

    /**
     * @return FieldList
     */
    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            $fields->dataFieldByName('Title')
                ->setReadonly(true);

            $fields->dataFieldByName('URLSegment')
                ->setReadonly(true);

            $fields->replaceField(
                'Content',
                ReadonlyField::create('Content', 'Description')
            );

            $fields->addFieldsToTab(
                'Root.Details',
                [
                    ReadonlyField::create('ShopifyID'),
                    ReadonlyField::create('Vendor'),
                    ReadonlyField::create('ProductType'),
                    ReadonlyField::create('Tags'),
                    ReadonlyField::create('Status'),
                ]
            );

            $fields->addFieldsToTab('Root.Variants', [
                GridField::create('Variants', 'Variants', $this->Variants(), GridFieldConfig_RecordViewer::create())
            ]);

            $fields->addFieldsToTab('Root.Media', [
                GridField::create('Files', 'Files', $this->Files(), GridFieldConfig_RecordViewer::create())
            ]);

            $fields->removeByName(['LinkTracking','FileTracking']);
        });

        return parent::getCMSFields();
    }

    /**
     * @return mixed
     */
    public function getImage()
    {
        if ($this->Files()) {
            return $this->Files()->first();
        }
    }

    /**
     * @return DataObject|null
     */
    public function getVariantWithLowestPrice()
    {
        return DataObject::get_one(ShopifyVariant::class, ['ProductID' => $this->ID], true, 'Price ASC');
    }

    /**
     * @return DBCurrency|null
     */
    public function getPrice()
    {
        if ($product = $this->getVariantWithLowestPrice()) {
            return $product->dbObject('Price');
        }

        return null;
    }

    /**
     * @return DBCurrency|null
     */
    public function getCompareAtPrice()
    {
        if ($product = $this->getVariantWithLowestPrice()) {
            return $product->dbObject('CompareAtPrice');
        }

        return null;
    }

    /**
     * Creates a new Shopify Product from the given data
     * but does not publish it
     *
     * @param $shopifyProduct
     * @return Product
     * @throws \SilverStripe\ORM\ValidationException
     */
    public static function findOrMakeFromShopifyData($shopifyProduct)
    {
        if (!$product = self::getByShopifyID($shopifyProduct->id)) {
            $product = self::create();
        }

        $map = self::config()->get('data_map');
        ShopifyImportTask::loop_map($map, $product, $shopifyProduct);

        if ($product->isChanged()) {
            $product->write();
        }

        return $product;
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
     * @param $urlSegment
     * @return DataObject|null
     */
    public static function getByURLSegment($urlSegment)
    {
        return DataObject::get_one(self::class, ['URLSegment' => $urlSegment]);
    }
}
