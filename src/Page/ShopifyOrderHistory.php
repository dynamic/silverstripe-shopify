<?php

namespace Dynamic\Shopify\Page;

use Dynamic\Shopify\Controller\ShopifyOrderHistoryController;

/**
 * Class ShopifyOrderHistory
 *
 */
class ShopifyOrderHistory extends \Page
{

    /**
     * @var string
     */
    private static $table_name = "ShopifyOrderHistory";

    /**
     * @var string
     */
    private static $controller_name = ShopifyOrderHistoryController::class;
}
