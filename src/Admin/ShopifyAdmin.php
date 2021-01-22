<?php

namespace Dynamic\Shopify\Admin;

use Dynamic\Shopify\Model\ShopifyFile;
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
    /**
     * @var string[]
     */
    private static $managed_models = [
        ShopifyProduct::class,
        ShopifyVariant::class,
        ShopifyCollection::class,
        ShopifyFile::class,
    ];

    /**
     * @var string
     */
    private static $url_segment = 'shopify';

    /**
     * @var string
     */
    private static $menu_title = 'Shopify';
}
