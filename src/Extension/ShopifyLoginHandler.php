<?php

namespace Dynamic\Shopify\Extension;

use Dynamic\Shopify\Client\ShopifyClient;
use Dynamic\Shopify\Client\ShopifyMultipass;
use SilverStripe\Control\Director;
use SilverStripe\Security\MemberAuthenticator\LoginHandler;

/**
 * Class ShopifyLoginHandler
 * @package Dynamic\Shopify\Extension
 */
class ShopifyLoginHandler extends LoginHandler
{
    /**
     * @return \SilverStripe\Control\HTTPResponse|void
     */
    protected function redirectAfterSuccessfulLogin()
    {
        $multipass_secret = ShopifyMultipass::config()->get('multipass_secret');
        if (isset($multipass_secret)) {
            $url = Director::absoluteURL('multipass');
            if ($backURL = $this->getBackURL()){
                $url = $url . '?BackURL=' . $backURL;
            } else if ($backURL = $this->getOffSiteBackURL('postVar')) {
                $url = $url . '?BackURL=' . $backURL;
            } else if ($backURL = $this->getOffSiteBackURL('getVar')) {
                $url = $url . '?BackURL=' . $backURL;
            }
            return $this->redirect($url);
        }
        return parent::redirectAfterSuccessfulLogin();
    }

    /**
     * So we can redirect back to checkout or cart. Will still only allow redirects to current site or shopify
     * @param string $method
     * @return bool|string
     */
    private function getOffSiteBackURL($method)
    {
        if ($backURL = $this->getRequest()->{$method}('BackURL')) {
            $host = parse_url($backURL, PHP_URL_HOST);
            if ($host === ShopifyClient::get_domain()) {
                return $backURL;
            }
        }
        return false;
    }
}
