<?php

namespace Dynamic\Shopify\Model;

use Dynamic\Shopify\Model\ShopifyFile;
use Dynamic\Shopify\Page\ShopifyProduct;
use Dynamic\Shopify\Task\ShopifyImportTask;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataObject;

/**
 * Class ShopifyVariant
 * @package Dynamic\Shopify\Model
 */
class ShopifyVariant extends DataObject
{
    /**
     * @var string
     */
    private static $table_name = 'ShopifyVariant';

    /**
     * @var string[]
     */
    private static $db = [
        'Title' => 'Varchar(255)',
        'ShopifyID' => 'Varchar',
        'SKU' => 'Varchar',
        'Price' => 'Currency',
        'CompareAtPrice' => 'Currency',
        'SortOrder' => 'Int',
        'Inventory' => 'Int',
    ];

    /**
     * @var string[]
     */
    private static $has_one = [
        'Product' => ShopifyProduct::class,
        'File' => ShopifyFile::class
    ];

    private static $owns = [
        'File',
    ];

    /**
     * @var string[]
     */
    private static $cascade_deletes = [
        'File',
    ];

    /**
     * @var bool[]
     */
    private static $indexes = [
        'ShopifyID' => true,
    ];

    /**
     * @var string[]
     */
    private static $summary_fields = [
        'File.CMSThumbnail' => 'Image',
        'Title',
        'Price.Nice' => 'Price',
        'SKU',
        'ShopifyID'
    ];

    /**
     * @var string[]
     */
    private static $searchable_fields = [
        'Title',
        'Price',
        'SKU',
        'ShopifyID',
    ];

    /**
     * @var string[]
     */
    private static $data_map = [
        'title'=> 'Title',
        'id'=> 'ShopifyID',
        'sku'=> 'SKU',
        'price'=> 'Price',
        'compareAtPrice'=> 'CompareAtPrice',
        'position' => 'SortOrder',
        'createdAt' => 'Created',
        'updatedAt' => 'LastEdited',
        'inventoryQuantity' => 'Inventory',
    ];

    /**
     * @return FieldList
     */
    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            foreach ($fields as $field) {
                $field->setReadonly(true);
            }

            $fields->replaceField(
                'ProductID',
                ReadonlyField::create('ProductName', 'Product')
                    ->setValue($this->Product()->Title)
            );
        });

        return parent::getCMSFields();
    }

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

        if (isset($shopifyVariant->image)) {
            $exploded = explode('/', $shopifyVariant->image->id);
            $imageID = end($exploded);

            if ($file = ShopifyFile::getByShopifyID($imageID)) {
                $variant->FileID = $file->ID;
            } else {
                $file = ShopifyFile::findOrMakeFromShopifyData($shopifyVariant->image);
                $variant->FileID = $file->ID;
            }
        }


        if ($variant->isChanged()) {
            $variant->write();
        }

        return $variant;
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
