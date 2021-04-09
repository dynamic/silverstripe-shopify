<?php

namespace Dynamic\Shopify\Controller;

use Dynamic\Shopify\Client\ShopifyClient;
use Dynamic\Shopify\Client\ShopifyMultipass;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Security\Security;

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
        $return_url = Director::absoluteBaseURL() . ShopifyMultipass::config()->get('return_url');

        if ($multipass_secret = ShopifyMultipass::config()->get('multipass_secret')) {
            if ($member = Security::getCurrentUser()) {
                $domain = ShopifyClient::config()->get('shopify_domain');
                $token_date = new \DateTime('NOW');
                $token_date = $token_date->format('c'); // ISO8601 formated datetime
                $customer_data = array(
                    "email" => $member->Email,
                    "created_at" => $token_date,
                    "first_name" => $member->FirstName,
                    "last_name" => $member->Surname,
                    "return_to" => $return_url,
                );

                $multipass = new ShopifyMultipass($multipass_secret);
                $token = $multipass->generate_token($customer_data);

                return $this->redirect("https://{$domain}/account/login/multipass/{$token}");
            }
        }
        return $this->redirect($return_url);
    }
}
