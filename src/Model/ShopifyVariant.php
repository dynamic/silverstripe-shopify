<?php

namespace Dynamic\Shopify\Model;

use Dynamic\Shopify\Model\ShopifyFile;
use Dynamic\Shopify\Page\ShopifyProduct;
use Dynamic\Shopify\Task\ShopifyImportTask;
use SilverStripe\ORM\DataObject;

class ShopifyVariant extends DataObject
{
    private static $table_name = 'ShopifyVariant';

    private static $db = [
        'ShopifyID' => 'Varchar',
        'Price' => 'Currency',
        'CompareAtPrice' => 'Currency',
        'SKU' => 'Varchar',
        'Sort' => 'Int',
        'Option1' => 'Varchar',
        'Option2' => 'Varchar',
        'Option3' => 'Varchar',
        'Taxable' => 'Boolean',
        'Barcode' => 'Varchar',
        'Inventory' => 'Int',
        'Grams' => 'Int',
        'Weight' => 'Decimal',
        'WeightUnit' => 'Varchar',
        'InventoryItemID' => 'Varchar',
        'RequiresShipping' => 'Boolean'
    ];

    private static $data_map = [
        'id'=> 'ShopifyID',
        'title'=> 'Title',
        'price'=> 'Price',
        'compare_at_price'=> 'CompareAtPrice',
        'sku'=> 'SKU',
        'position' => 'Sort',
        'option1' => 'Option1',
        'option2' => 'Option2',
        'option3' => 'Option3',
        'created_at' => 'Created',
        'updated_at' => 'LastEdited',
        'taxable' => 'Taxable',
        'barcode' => 'Barcode',
        'grams' => 'Grams',
        'inventory_quantity' => 'Inventory',
        'weight' => 'Weight',
        'weight_unit' => 'WeightUnit',
        'inventory_item_id' => 'InventoryItemID',
        'requires_shipping' => 'RequiresShipping'
    ];

    private static $has_one = [
        'Product' => ShopifyProduct::class,
        'Image' => ShopifyFile::class
    ];

    private static $indexes = [
        'ShopifyID' => true
    ];

    private static $summary_fields = [
        'Image.CMSThumbnail' => 'Image',
        'Title',
        'Price',
        'SKU',
        'ShopifyID'
    ];

    /**
     * Creates a new Shopify Variant from the given data
     *
     * @param $shopifyVariant
     * @return ProductVariant
     * @throws \SilverStripe\ORM\ValidationException
     */
    public static function findOrMakeFromShopifyData($shopifyVariant)
    {
        if (!$variant = self::getByShopifyID($shopifyVariant->id)) {
            $variant = self::create();
        }

        $map = self::config()->get('data_map');
        ShopifyImportTask::loop_map($map, $variant, $shopifyVariant);

        if ($image = ShopifyFile::getByShopifyID($shopifyVariant->image_id)) {
            $variant->ImageID = $image->ID;
        }

        if ($variant->isChanged()) {
            $variant->write();
        }

        return $variant;
    }

    public static function getByShopifyID($shopifyId)
    {
        return DataObject::get_one(self::class, ['ShopifyID' => $shopifyId]);
    }
}
