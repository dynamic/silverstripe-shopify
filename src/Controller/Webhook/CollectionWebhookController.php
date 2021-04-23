<?php

namespace Dynamic\Shopify\Controller\Webhook;

use Dynamic\Shopify\Model\ShopifyFile;
use Dynamic\Shopify\Model\ShopifyFileSource;
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

        $data = json_decode($request->getBody());
        $collection = ShopifyCollection::create();
        $collection->ShopifyID = $data->id;
        $collection->URLSegment = $data->handle;
        $collection->SortOrder = $data->sort_order;
        $collection->Title = $data->title;
        $collection->Content = $data->body_html;
        $collection->Created = $data->updated_at;
        $collection->LastEdited = $data->updated_at;

        $collection->write();
        if ($data->published_at) {
            $collection->publishRecursive();
        }
    }

    /**
     * @param HTTPRequest $request
     */
    public function update($request)
    {
        if ($request === null) {
            $request = $this->getRequest();
        }

        $data = json_decode($request->getBody());
        $collection = ShopifyCollection::getByShopifyID($data->id);
        if (!$collection) {
            $collection = ShopifyCollection::create();
            $collection->ShopifyID = $data->id;
        }
        $collection->URLSegment = $data->handle;
        $collection->SortOrder = $data->sort_order;
        $collection->Title = $data->title;
        $collection->Content = $data->body_html;
        $collection->Created = $data->updated_at;
        $collection->LastEdited = $data->updated_at;

        if (isset($data->image)) {
            $file = $collection->File();
            if (!$file || $collection->FileID === 0) {
                if (!$collection->exists()) {
                    $collection->write();
                }
                $file = ShopifyFile::create();
                $file->Type = ShopifyFile::IMAGE;
                $file->ShopifyID = $collection->ShopifyID;
                $file->CollectionID = $collection->ID;
                $file->write();
                $collection->FileID = $file->ID;

                $source = ShopifyFileSource::create();
                $source->write();
                $file->OriginalSourceID = $source->ID;
            }

            $source = $file->OriginalSource();
            $source->Width = $data->image->width;
            $source->Height = $data->image->height;
            $source->URL = $data->image->src;
            if ($source->isChanged()) {
                $source->write();
            }

            $file->PreviewSrc = $data->image->src;
            if ($file->isChanged()) {
                $file->write();
            }
        } elseif ($collection->FileID) {
            $collection->File()->delete();
            $collection->FileID = 0;
        }

        if ($collection->isChanged()) {
            $collection->write();
            if ($data->published_at) {
                $collection->publishRecursive();
            }
        }
    }
}
