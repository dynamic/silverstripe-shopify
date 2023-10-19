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
        $price = number_format($this->getPrice()->getValue(), 2);
        $name = $this->Title
            ? str_replace('\'', '\\\'', $this->Title)
            : $this->Title;
        $vendor = $this->Vendor
            ? str_replace('\'', '\\\'', $this->Vendor)
            : $this->Vendor;
        $category = $this->Parent()->Title
            ? str_replace('\'', '\\\'', $this->Parent()->Title)
            : $this->Parent()->Title;
        Requirements::customScript("
            (function () {
                window.dataLayer = window.dataLayer || [];
                dataLayer.push({ ecommerce: null });  /* Clear the previous ecommerce object. */
                dataLayer.push({
                  'ecommerce': {
                    'detail': {
                      'actionField': {'list': 'Product Detail Page View'},
                      'products': [{
                        'name': '{$name}',
                        'id': '{$this->getSKU()}',
                        'price': '{$price}',
                        'brand': '{$vendor}',
                        'category': '{$category}'
                       }]
                     }
                   }
                });
            })();
        ", 'shopify-product-detail-enhanced-ecommerce');

        return parent::init();
    }
}
