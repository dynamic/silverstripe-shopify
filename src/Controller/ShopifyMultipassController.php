<?php

namespace Dynamic\Shopify\Controller;

use Dynamic\Shopify\Client\ShopifyClient;
use SilverStripe\Control\Controller;
use SilverStripe\Dev\Debug;

/**
 * Class ShopifyMultipassController
 * @package Dynamic\Shopify\Controller
 */
class ShopifyMultipassController extends Controller
{
    /**
     * @var array
     */
    private static $allowed_actions = [
        'index',
    ];

    /**
     *
     */
    public function index()
    {
        $domain = ShopifyClient::config()->get('shopify_domain');
        $token = null;

        if ($multipass_secret = ShopifyClient::config()->get('multipass_secret')) {
            if ($member = Security::getCurrentUser()) {
                $customer_data = array(
                    "email" => $member->Email,
                    "remote_ip" => $_SERVER['REMOTE_ADDR'],
                );

                $multipass = new ShopifyMultipass($multipass_secret);
                $token = $multipass->generate_token($customer_data);
            }
        }
        Debug::show($token);
        //$this->redirect("{$domain}account/login/multipass/{$token}");
    }
}
