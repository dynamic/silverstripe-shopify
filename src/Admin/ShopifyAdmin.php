<?php

namespace Dynamic\Shopify\Admin;

use Dynamic\Shopify\Model\ShopifyVariant;
use Dynamic\Shopify\Page\ShopifyCollection;
use Dynamic\Shopify\Page\ShopifyProduct;
use LittleGiant\CatalogManager\ModelAdmin\CatalogPageAdmin;

/**
 * Class ShopifyAdmin
 * @package Dynamic\Shopify\Admin
 */
class ShopifyAdmin extends CatalogPageAdmin
{
    private static $managed_models = [
        ShopifyProduct::class,
        ShopifyVariant::class,
        ShopifyCollection::class,
    ];

    private static $url_segment = 'shopify';

    private static $menu_title = 'Shopify';
}
