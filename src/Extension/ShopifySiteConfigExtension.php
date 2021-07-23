<?php

namespace Dynamic\Shopify\Extension;

use SilverStripe\ORM\DataExtension;

/**
 * Class ShopifySiteConfigExtension
 * @package Dynamic\Shopify\Extension
 *
 * @property string ShopCurrencyCode
 */
class ShopifySiteConfigExtension extends DataExtension
{
    /**
     * @var string[]
     */
    private static $db = [
        'ShopCurrencyCode' => 'Varchar',
    ];
}
