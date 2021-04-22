<?php

namespace Dynamic\Shopify\Controller\Webhook;

use Dynamic\Shopify\Page\ShopifyCollection;
use Dynamic\Shopify\Page\ShopifyProduct;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;

/**
 * Class CollectionWebhookController
 * @package Dynamic\Shopify\Controller\Webhook
 */
class CollectionWebhookController extends Controller
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
        /** @var ShopifyCollection|null $product */
        $product = ShopifyCollection::get()->find('ShopifyID', $body['id']);
        if (!$product) {
            return $this->httpError(404, 'collection with id ' . $body['id'] . ' not found');
        }
        $product->doUnpublish();
    }

    /**
     * @param HTTPRequest $request
     */
    public function createCollection($request)
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
