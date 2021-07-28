<?php

namespace Dynamic\Shopify\Controller;

use Dynamic\Shopify\Page\ShopifyProduct;
use SilverStripe\View\Requirements;

/**
 * Class ShopifyProductController
 * @package Dynamic\Shopify\Controller
 *
 * @mixin ShopifyProduct
 */
class ShopifyProductController extends \PageController
{
    /**
     * @inheritDoc
     */
    public function init()
    {
        Requirements::customScript("
            (function () {
                window.dataLayer = window.dataLayer || [];
                dataLayer.push({ ecommerce: null });  // Clear the previous ecommerce object.
                dataLayer.push({
                  'ecommerce': {
                    'detail': {
                      'actionField': {'list': 'Product Detail Page View'},
                      'products': [{
                        'name': '{$this->Title}',
                        'id': '{$this->getSKU()}',
                        'price': '{$this->getPrice()->Nice()}',
                        'brand': '{$this->Vendor}',
                        'category': '{$this->ProductType}'
                       }]
                     }
                   }
                });
            })();
        ", 'shopify-product-detail-enhanced-ecommerce');

        return parent::init();
    }
}
