<?php

namespace Dynamic\Shopify\Controller\Webhook;

use Dynamic\Shopify\Page\ShopifyProduct;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;

/**
 * Class ProductWebhookController
 * @package Dynamic\Shopify\Controller\Webhook
 */
class ProductWebhookController extends Controller
{

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
        return 'create product';
    }

    /**
     * @param HTTPRequest $request
     */
    public function update($request)
    {
        if ($request === null) {
            $request = $this->getRequest();
        }

        return 'update product';
    }
}
