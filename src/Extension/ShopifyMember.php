<?php

namespace Dynamic\Shopify\Extension;

use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;

/**
 * Class ShopifyMember
 * @package Dynamic\Shopify\Extension
 *
 * @property string ShopifyID
 */
class ShopifyMember extends DataExtension
{
    /**
     * @var string[]
     */
    private static $db = [
        'ShopifyID' => 'Varchar(255)',
    ];

    /**
     * @param FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldsToTab(
            'Root.Shopify',
            [
                $fields->dataFieldByName('ShopifyID')
            ]
        );
    }
}
