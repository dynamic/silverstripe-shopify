<?php

namespace Dynamic\Shopify\Page;

use LittleGiant\CatalogManager\ModelAdmin\CatalogPageAdmin;

class ProductAdmin extends CatalogPageAdmin
{
    private static $managed_models = [
        Product::class,
        ProductVariant::class,
        ProductCollection::class,
    ];

    private static $url_segment = 'products';

    private static $menu_title = 'Products';
}
