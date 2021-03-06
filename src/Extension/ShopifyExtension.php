<?php

namespace Dynamic\Shopify\Extension;

use Dynamic\Shopify\Client\ShopifyClient;
use Dynamic\Shopify\Page\ShopifyProduct;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\FieldType\DBCurrency;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\View\Requirements;

/**
 * Class ShopifyExtension
 * @package Dynamic\Shopify\Extension
 */
class ShopifyExtension extends Extension
{
    /**
     * @return DBField
     */
    public function getCartOptions()
    {
        $configValue = Convert::array2json([
            'cart' => [
                'text' => [
                    'title' => _t('Shopify.CartTitle', 'Cart'),
                    'empty' => _t('Shopify.CartEmpty', 'Your cart is empty.'),
                    'button' => _t('Shopify.CartButton', 'Checkout'),
                    'total' => _t('Shopify.CartTotal', 'Subtotal'),
                    'currency' => ShopifyProduct::config()->get('currency'),
                    'notice' => _t('Shopify.CartNotice', 'Shipping and discount codes are added at checkout.'),
                ],
                'popup' => 0,
                'contents' => [
                    'note' => 1
                ]
            ],
        ]);

        return DBField::create_field(DBHTMLText::class, "$configValue");
    }

    /**
     * @return mixed
     */
    public function getStoreFrontToken()
    {
        return ShopifyClient::config()->get('storefront_access_token');
    }

    /**
     * @return mixed
     */
    public function getCurrencySymbol()
    {
        return DBCurrency::config()->get('currency_symbol');
    }

    /**
     * @return mixed
     */
    public function getDomain()
    {
        return ShopifyClient::get_domain();
    }

    /**
     *
     */
    public function onAfterInit()
    {
        Requirements::javascript('dynamic/silverstripe-shopify:client/cart.init.js');
    }
}
