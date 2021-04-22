<?php

namespace Dynamic\Shopify\Controller\Webhook;

use Dynamic\Shopify\Client\ShopifyClient;
use SilverStripe\Control\Controller;

/**
 * Class WebhookController
 * @package Dynamic\Shopify\Controller\Webhook
 *
 * The base for all shopify webhook controllers. It will automatically validate the request.
 */
class WebhookController extends Controller
{
    /**
     * @inerhitDoc
     * @throws \SilverStripe\Control\HTTPResponse_Exception
     */
    public function init()
    {
        parent::init();

        $request = $this->getRequest();
        if ($request->getHeader('X-Shopify-Shop-Domain') !== ShopifyClient::config()->get('shopify_domain')) {
            return $this->httpError(403, 'mis-matched shopify domain');
        }

        $secret = ShopifyClient::config()->get('shared_secret');
        $calculated_hmac = base64_encode(hash_hmac('sha256', $request->getBody(), $secret, true));
        if (hash_equals($request->getHeader('X-Shopify-Hmac-Sha256'), $calculated_hmac)) {
            return $this->httpError(403, 'payload did not verify correctly');
        }
    }
}
