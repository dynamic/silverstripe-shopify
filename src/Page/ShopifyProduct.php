<?php

namespace Dynamic\Shopify\Page;

use Dynamic\Shopify\Model\ShopifyFile;
use Dynamic\Shopify\Model\ShopifyVariant;
use Dynamic\Shopify\Task\ShopifyImportTask;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBCurrency;
use SilverStripe\View\Requirements;

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
     * @var string[]
     */
    private static $db = [
        'ShopifyID' => 'Varchar',
        'Vendor' => 'Varchar',
        'ProductType' => 'Varchar',
        //'Tags' => 'Varchar',
        'ProductActive' => 'Boolean',
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
     * @var int[]
     */
    private static $defaults = [
        'ShowInMenus' => 0,
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
        //'Tags'
    ];

    /**
     * @var string[]
     *
     * Field mappings from Shopify
     */
    private static $data_map = [
        'id' => 'ShopifyID',
        'title' => 'Title',
        'handle' => 'URLSegment',
        'descriptionHtml' => 'Content',
        'vendor' => 'Vendor',
        'productType' => 'ProductType',
        'createdAt' => 'Created',
        'updatedAt' => 'LastEdited',
        //'tags' => 'Tags',
        'publishedOnCurrentPublication' => 'ProductActive'
    ];

    /**
     * @var \array[][]
     *
     * Set options for the Buy Button display
     */
    private static $options = [
        'product' => [
            'contents' => [
                'title' => false,
                'variantTitle' => false,
                'price' => true,
                'description' => false,
                'quantity' => true,
                'img' => false,
            ]
        ]
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
                    //ReadonlyField::create('Tags'),
                    ReadonlyField::create('ProductActive'),
                ]
            );

            $fields->addFieldsToTab(
                'Root.Variants',
                [
                    GridField::create(
                        'Variants',
                        'Variants',
                        $this->Variants(),
                        GridFieldConfig_RecordViewer::create()
                    )
                ]
            );

            $fields->addFieldsToTab(
                'Root.Media',
                [
                    GridField::create(
                        'Files',
                        'Files',
                        $this->Files(),
                        GridFieldConfig_RecordViewer::create()
                    )
                ]
            );

            $fields->addFieldsToTab(
                'Root.Collections',
                [
                    GridField::create(
                        'Collections',
                        'Collections',
                        $this->Collections(),
                        GridFieldConfig_RecordViewer::create()
                    )
                ]
            );
        });

        return parent::getCMSFields();
    }

    /**
     * @return mixed
     */
    public function getImage()
    {
        if ($this->Files()->exists()) {
            return $this->Files()->first();
        }
    }

    /**
     * @return string
     */
    public function getButtonOptions()
    {
        return Convert::array2json(array_merge_recursive(self::config()->get('options'), [
            'product' => [
                'text' => [
                    'button' => _t('Shopify.ProductButton', 'Add to cart'),
                    'outOfStock' => _t('Shopify.ProductOutOfStock', 'Out of stock'),
                    'unavailable' => _t('Shopify.ProductUnavailable', 'Unavailable'),
                ]
            ]
        ]));
    }

    /**
     *
     */
    public function getButtonScript()
    {
        if ($this->ShopifyID) {
            $currencySymbol = DBCurrency::config()->get('currency_symbol');
            Requirements::customScript(<<<JS
            (function () {
                if (window.shopifyClient) {
                    window.shopifyClient.createComponent('product', {
                        id: {$this->ShopifyID},
                        node: document.getElementById('product-component-{$this->ShopifyID}'),
                        moneyFormat: '$currencySymbol{{amount}}',
                        options: {$this->ButtonOptions}
                    });
                }
            })();
JS
            );
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
     * @return string|null
     */
    public function getSKU()
    {
        if ($this->Variants()->exists()) {
            return $this->Variants()->first()->SKU;
        }

        return null;
    }

    /**
     * @param $shopifyProduct
     * @return ShopifyProduct
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
            if ($product->isPublished()) {
                $product->publishSingle();
            }
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
