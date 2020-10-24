<?php

namespace Dynamic\Shopify\Page;

use Dynamic\Shopify\Model\ProductImage;
use Dynamic\Shopify\Task\ShopifyImportTask\ShopifyImportTask;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBCurrency;

class Product extends \Page
{
    private static $table_name = 'ShopifyProduct';

    private static $currency = 'USD';

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

    private static $db = [
        'ShopifyID' => 'Varchar',
        'Vendor' => 'Varchar',
        'ProductType' => 'Varchar',
        'Tags' => 'Varchar'
    ];

    private static $default_sort = 'Created DESC';

    private static $searchable_fields = [
        'Title',
        'URLSegment',
        'ShopifyID',
        'Content',
        'Vendor',
        'ProductType',
        'Tags'
    ];

    private static $data_map = [
        'id' => 'ShopifyID',
        'title' => 'Title',
        'body_html' => 'Content',
        'vendor' => 'Vendor',
        'product_type' => 'ProductType',
        'created_at' => 'Created',
        'handle' => 'URLSegment',
        'updated_at' => 'LastEdited',
        'tags' => 'Tags',
    ];

    private static $has_one = [
        'Image' => ProductImage::class
    ];

    private static $has_many = [
        'Variants' => ProductVariant::class,
        'Images' => ProductImage::class
    ];

    private static $belongs_many_many = [
        'Collections' => ProductCollection::class
    ];

    private static $owns = [
        'Variants',
        'Images',
        'Image'
    ];

    private static $indexes = [
        'ShopifyID' => true,
    ];

    private static $summary_fields = [
        'Image.CMSThumbnail' => 'Image',
        'Title',
        'Vendor',
        'ProductType',
        'ShopifyID'
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldsToTab('Root.Main', [
            ReadonlyField::create('Title'),
            ReadonlyField::create('URLSegment'),
            ReadonlyField::create('ShopifyID'),
            ReadonlyField::create('Content'),
            ReadonlyField::create('Vendor'),
            ReadonlyField::create('ProductType'),
            ReadonlyField::create('Tags'),
            UploadField::create('Image')->performReadonlyTransformation(),
        ]);

        $fields->addFieldsToTab('Root.Variants', [
            GridField::create('Variants', 'Variants', $this->Variants(), GridFieldConfig_RecordViewer::create())
        ]);

        $fields->addFieldsToTab('Root.Images', [
            GridField::create('Images', 'Images', $this->Images(), GridFieldConfig_RecordViewer::create())
        ]);

        $fields->removeByName(['LinkTracking','FileTracking']);
        return $fields;
    }

    public function getVariantWithLowestPrice()
    {
        return DataObject::get_one(ProductVariant::class, ['ProductID' => $this->ID], true, 'Price ASC');
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

    public static function getByShopifyID($shopifyId)
    {
        return DataObject::get_one(self::class, ['ShopifyID' => $shopifyId]);
    }

    public static function getByURLSegment($urlSegment)
    {
        return DataObject::get_one(self::class, ['URLSegment' => $urlSegment]);
    }
}
