<?php

namespace Dynamic\Shopify\Extension;

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
            $url = Director::absoluteBaseURL() . 'multipass';
            return $this->redirect($url);
        }
        return parent::redirectAfterSuccessfulLogin();
    }
}
