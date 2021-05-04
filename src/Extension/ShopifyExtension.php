<?php

namespace Dynamic\Shopify\Extension;

use Dynamic\Shopify\Client\ShopifyClient;
use Dynamic\Shopify\Page\ShopifyProduct;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\FieldType\DBCurrency;
use SilverStripe\View\Requirements;

/**
 * Class ShopifyExtension
 * @package Dynamic\Shopify\Extension
 */
class ShopifyExtension extends Extension
{
    /**
     * @return string
     *
     * todo - create private static $cart_options on ShopifyProduct to isolate cart options from button options
     */
    public function getCartOptions()
    {
        return Convert::array2json(array_merge_recursive(ShopifyProduct::config()->get('button_options'), [
            'cart' => [
                'text' => [
                    'title' => _t('Shopify.CartTitle', 'Cart'),
                    'empty'=> _t('Shopify.CartEmpty', 'Your cart is empty.'),
                    'button' => _t('Shopify.CartButton', 'Checkout'),
                    'total' => _t('Shopify.CartTotal', 'Subtotal'),
                    'currency' => ShopifyProduct::config()->get('currency'),
                    'notice' => _t('Shopify.CartNotice', 'Shipping and discount codes are added at checkout.')
                ],
                'popup' => 0,
            ]
        ]));
    }

    /**
     *
     */
    public function onAfterInit()
    {
        if (ShopifyClient::config()->get('inject_javascript') !== false) {
            $domain = ShopifyClient::config()->get('shopify_domain');
            $accessToken = ShopifyClient::config()->get('storefront_access_token');
            $currencySymbol = DBCurrency::config()->get('currency_symbol');
            Requirements::javascript('//sdks.shopifycdn.com/buy-button/latest/buybutton.js');
            Requirements::customScript(<<<JS
            (function () {
                var client = ShopifyBuy.buildClient({
                  domain: '{$domain}',
                  storefrontAccessToken: '{$accessToken}'
                });

                window.shopifyClient = ShopifyBuy.UI.init(client);
                window.shopifyClient.createComponent('cart', {
                   node: document.getElementById('shopify-cart'),
                   moneyFormat: '$currencySymbol{{amount}}',
                   options: {$this->getCartOptions()}
                });
            })();
JS
            );
        }
    }
}
