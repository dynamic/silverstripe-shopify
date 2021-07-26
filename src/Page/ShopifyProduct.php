<?php

namespace Dynamic\Shopify\Page;

use Dynamic\Shopify\Model\ShopifyFile;
use Dynamic\Shopify\Model\ShopifyVariant;
use Dynamic\Shopify\Task\ShopifyImportTask;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBCurrency;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\HasManyList;
use SilverStripe\View\Requirements;

/**
 * Class ShopifyProduct
 * @package Dynamic\Shopify\Page
 *
 * @property string ShopifyID
 *
 * @method HasManyList|ShopifyFile[] Files
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
        'Files' => ShopifyFile::class,
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
        'ShopifyID',
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
        'publishedOnCurrentPublication' => 'ProductActive',
    ];

    /**
     * @var \array[][]
     *
     * Set options for the Buy Button display
     * https://github.com/Shopify/buy-button-js/blob/master/src/defaults/components.js
     */
    private static $button_options = [
        'product' => [
            'iframe' => false,
            'contents' => [
                'title' => false,
                'variantTitle' => false,
                'price' => false,
                'description' => false,
                'quantity' => false,
                'img' => false,
            ],
        ],
    ];

    /**
     * Display the add to cart form for product pages
     *
     * @var array[]
     */
    private static $form_options = [
        'product' => [
            'iframe' => false,
            'contents' => [
                'title' => false,
                'variantTitle' => false,
                'price' => true,
                'description' => false,
                'quantity' => true,
                'img' => false,
            ],
        ],
    ];

    /**
     * Display an overlay generated by Shopify data
     *
     * @var \array[][]
     */
    private static $overlay_options = [
        'product' => [
            'iframe' => false,
            'buttonDestination' => 'modal',
            'contents' => [
                'img' => false,
                'title' => false,
                'variantTitle' => false,
                'price' => true,
                'unitPrice' => false,
                'options' => false,
                'quantityInput' => false,
                'description' => false,
            ],
        ],
        'modalProduct' => [
            'contents' => [
                'title' => true,
                'variantTitle' => false,
                'price' => true,
                'description' => true,
                'img' => false,
                'imgWithCarousel' => true,
                'buttonWithQuantity' => true,
                'button' => false,
                'quantity' => false,
            ],
        ],
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
                    ),
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
                    ),
                ]
            );
        });

        return parent::getCMSFields();
    }

    /**
     * @inheritDoc
     */
    public function getCMSActions()
    {
        $actions = parent::getCMSActions();

        if (!$this->ShopifyID || !$this->canEdit()) {
            return $actions;
        }

        $controller = Controller::curr();
        if ($controller instanceof LeftAndMain) {
            /** @var FormAction $action */
            $action = FormAction::create('shopifyProductFetch', 'Re-fetch Shopify')
                ->addExtraClass('btn-primary font-icon-sync')
                ->setUseButtonTag(true);

            $actions->push($action);
        }

        return $actions;
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
     * @return DBField
     */
    public function getButtonOptions()
    {
        $configValue = $this->prepareConfigString(json_encode(array_merge_recursive(self::config()->get('button_options'), [
            'product' => [
                'text' => [
                    'button' => _t('Shopify.ProductButton', 'Add to cart'),
                    'outOfStock' => _t('Shopify.ProductOutOfStock', 'Out of stock'),
                    'unavailable' => _t('Shopify.ProductUnavailable', 'Unavailable'),
                ],
            ],
        ])));

        return DBField::create_field(DBHTMLText::class, "$configValue");
    }

    /**
     * @return DBField
     */
    public function getFormOptions()
    {
        $configValue = $this->prepareConfigString(json_encode(array_merge_recursive(self::config()->get('form_options'), [
            'product' => [
                'text' => [
                    'button' => _t('Shopify.ProductButton', 'Add to cart'),
                    'outOfStock' => _t('Shopify.ProductOutOfStock', 'Out of stock'),
                    'unavailable' => _t('Shopify.ProductUnavailable', 'Unavailable'),
                ],
            ],
        ])));

        return DBField::create_field(DBHTMLText::class, "$configValue");
    }

    /**
     * @return DBField
     */
    public function getOverlayOptions()
    {
        $configValue = $this->prepareConfigString(json_encode(array_merge_recursive(self::config()->get('overlay_options'), [
            'product' => [
                'text' => [
                    'button' => _t('Shopify.ProductButton', 'Add to cart'),
                ],
            ],
        ])));

        return DBField::create_field(DBHTMLText::class, "$configValue");
    }

    /**
     * @param string $string
     * @return array|string|string[]
     */
    protected function prepareConfigString($string)
    {
        return $string;
        //return str_replace('"', '&quote;', $string);
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
