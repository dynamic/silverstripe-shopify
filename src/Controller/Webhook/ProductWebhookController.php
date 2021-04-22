<?php

namespace Dynamic\Shopify\Controller\Webhook;

use Dynamic\Shopify\Page\ShopifyProduct;
use SilverStripe\Control\HTTPRequest;

/**
 * Class ProductWebhookController
 * @package Dynamic\Shopify\Controller\Webhook
 */
class ProductWebhookController extends WebhookController
{
    /**
     * @var string[]
     */
    private static $url_handlers = [
        'create' => 'createProduct',
    ];

    /**
     * @var string[]
     */
    private static $allowed_actions = [
        'createProduct',
        'update',
        'delete',
    ];

    /**
     * @param HTTPRequest $request
     * @throws \SilverStripe\Control\HTTPResponse_Exception
     */
    public function delete($request)
    {
        if ($request === null) {
            $request = $this->getRequest();
        }

        $body = json_decode($request->getBody(), true);
        /** @var ShopifyProduct|null $product */
        $product = ShopifyProduct::get()->find('ShopifyID', $body['id']);
        if (!$product) {
            return $this->httpError(404, 'product with id ' . $body['id'] . ' not found');
        }
        $product->doUnpublish();
    }

    /**
     * @param HTTPRequest $request
     */
    public function createProduct($request)
    {
        if ($request === null) {
            $request = $this->getRequest();
        }
        return 'All good';
    }

    /**
     * @param HTTPRequest $request
     */
    public function update($request)
    {
        if ($request === null) {
            $request = $this->getRequest();
        }
    }
}
