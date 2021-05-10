<?php

namespace Dynamic\Shopify\Controller\Webhook;

use Dynamic\Shopify\Client\ShopifyClient;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;

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

    /**
     * @param HTTPRequest $request
     */
    public function index($request = null)
    {
        if ($request === null) {
            $request = $this->getRequest();
        }

        $topic = explode('/', $request->getHeader('X-Shopify-Topic'));
        $type = $topic[0];
        $procedure = $topic[1];
        if ($procedure === 'create') {
            $procedure = 'create' . ucfirst($type);
            if (substr($procedure, -1) === 's') {
                $procedure = substr($procedure, 0, -1);
            }
        }

        $routing = static::config()->get('type_routing');
        if (!array_key_exists($type, $routing)) {
            return $this->httpError(404, 'No handling set up for type');
        }

        /** @var Controller $controller */
        $controller = $routing[$type]::create();
        $controller->setRequest($request);
        if (!$controller->hasMethod($procedure)) {
            return $this->httpError(404, "No procedure {$procedure} set up for type");
        }

        return $controller->{$procedure}($request);
    }
}
