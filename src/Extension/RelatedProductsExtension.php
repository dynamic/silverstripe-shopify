<?php

namespace Dynamic\Shopify\Extension;

use Dynamic\Shopify\Page\ShopifyProduct;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Versioned\GridFieldArchiveAction;
use Symbiote\GridFieldExtensions\GridFieldAddExistingSearchButton;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

/**
 * Class RelatedProductsExtension
 * @package Dynamic\Shopify\Extension
 */
class RelatedProductsExtension extends DataExtension
{
    /**
     * @var array
     */
    private static $many_many = [
        'RelatedProducts' => ShopifyProduct::class,
    ];

    /**
     * @var array
     */
    private static $many_many_extraFields = [
        'RelatedProducts' => [
            'SortOrder' => 'Int',
        ],
    ];

    /**
     * @param FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        if ($this->owner->ID) {
            $fields->addFieldToTab(
                'Root.Related',
                GridField::create(
                    'RelatedProducts',
                    'Related Products',
                    $this->owner->RelatedProducts()->sort('SortOrder'),
                    $productConfig = GridFieldConfig_RelationEditor::create()
                )
            );
            $productConfig
                ->removeComponentsByType([
                    GridFieldAddNewButton::class,
                    GridFieldAddExistingAutocompleter::class,
                    GridFieldArchiveAction::class
                ])
                ->addComponents(
                    new GridFieldOrderableRows('SortOrder'),
                    $addnew = new GridFieldAddExistingSearchButton()
                );

            $addnew->setSearchList(ShopifyProduct::get()->exclude('ID', $this->owner->ID));
        }
    }

    /**
     * @return mixed
     */
    public function getRelatedProductsList()
    {
        return $this->owner->RelatedProducts()->sort('SortOrder');
    }
}
