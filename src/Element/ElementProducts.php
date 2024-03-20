<?php

namespace Dynamic\Shopify\Element;

use DNADesign\Elemental\Models\BaseElement;
use Dynamic\Shopify\Page\ShopifyProduct;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\ValidationException;
use Symbiote\GridFieldExtensions\GridFieldAddExistingSearchButton;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

if (!class_exists(BaseElement::class)) {
    return;
}

/**
 * Class ElementProducts
 *
 * @property string $Content
 * @property int $Limit
 * @method ManyManyList|ShopifyProduct[] Products()
 */
class ElementProducts extends BaseElement
{
    /**
     * @var string
     */
    private static $table_name = 'ElementalProducts';

    /**
     * @var array
     */
    private static $db = [
        'Content' => 'HTMLText',
        'Limit' => 'Int',
    ];

    /**
     * @var array
     */
    private static $many_many = [
        'Products' => ShopifyProduct::class,
    ];

    /**
     * @var \string[][]
     */
    private static $many_many_extraFields = [
        'Products' => [
            'ElementSortOrder' => 'Int',
        ],
    ];

    /**
     * @var array
     */
    private static $defaults = array(
        'Limit' => 4,
    );

    /**
     * @var bool
     */
    private static $inline_editable = false;

    /**
     * @return \SilverStripe\Forms\FieldList
     */
    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            $fields->dataFieldByName('Content')
                ->setRows(10);

            $fields->insertBefore(
                'Content',
                $fields->dataFieldByName('Limit')
            );

            if ($this->ID) {
                $products = $fields->dataFieldByName('Products');
                $config = $products->getConfig();
                $config
                    ->removeComponentsByType([
                        GridFieldAddExistingAutocompleter::class,
                        GridFieldAddNewButton::class,
                    ])
                    ->addComponents([
                        new GridFieldAddExistingSearchButton(),
                        new GridFieldOrderableRows('ElementSortOrder')
                    ]);
            }
        });



        return parent::getCMSFields(); // TODO: Change the autogenerated stub
    }

    /**
     * @return mixed
     * @throws ValidationException
     */
    public function getProductsList()
    {
        $random = DB::get_conn()->random();
        $limit = $this->Limit;
        $products = $this->Products()->limit($limit)->sort('ElementSortOrder');
        $count = $products->count();
        $combined = ArrayList::create();

        if ($count < $limit) {
            $backFill = ShopifyProduct::get();
            if ($products->exists()) {
                $backFill = $backFill->exclude(['ID' => $products->column()]);
            }
            $backFill = $backFill
                ->sort($random)
                ->limit($limit - $count);

            foreach ($products as $product) {
                $combined->push($product);
            }

            foreach ($backFill as $product) {
                $combined->push($product);
            }

            $products = $combined;
        }

        return $products->limit($limit);
    }

    /**
     * @return string
     * @throws ValidationException
     */
    public function getSummary()
    {
        $count = $this->exists() ? $this->getProductsList()->count() : 0;
        $label = _t(
            ShopifyProduct::class . '.PLURALS',
            'A Product|{count} Products',
            ['count' => $count]
        );

        return DBField::create_field('HTMLText', $label)->Summary(20);
    }

    /**
     * @return array
     * @throws ValidationException
     */
    protected function provideBlockSchema()
    {
        $blockSchema = parent::provideBlockSchema();
        $blockSchema['content'] = $this->getSummary();
        return $blockSchema;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return _t(__CLASS__ . '.BlockType', 'Products');
    }
}
