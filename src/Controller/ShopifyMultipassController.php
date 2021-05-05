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
        $request = $this->getRequest();

        $return_url = Director::absoluteURL(ShopifyMultipass::config()->get('return_url'));
        if ($backURL = $request->getVar('BackURL')) {
            $return_url = Director::absoluteURL($backURL);
        }

        if ($multipass_secret = ShopifyMultipass::config()->get('multipass_secret')) {
            if ($member = Security::getCurrentUser()) {
                if (filter_var($member->Email, FILTER_VALIDATE_EMAIL)) {
                    $domain = ShopifyClient::config()->get('shopify_domain');
                    $customer_data = array(
                        "email" => $member->Email,
                        "first_name" => $member->FirstName,
                        "last_name" => $member->Surname,
                        "return_to" => $return_url,
                        //"remote_ip" => $request->getIP(),
                    );

                    $multipass = new ShopifyMultipass($multipass_secret);
                    $token = $multipass->generate_token($customer_data);

                    return $this->redirect("https://{$domain}/account/login/multipass/{$token}");
                }
            }
        }
        return $this->redirect($return_url);
    }
}
