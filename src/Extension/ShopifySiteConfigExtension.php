<?php

namespace Dynamic\Shopify\Extension;

use SilverStripe\ORM\DataExtension;

/**
 * Class ShopifySiteConfigExtension
 *
 * @property SiteConfig|ShopifySiteConfigExtension $owner
 * @property string $ShopCurrencyCode
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
