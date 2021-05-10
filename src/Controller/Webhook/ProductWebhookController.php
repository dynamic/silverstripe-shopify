<?php

namespace Dynamic\Shopify\Controller\Webhook;

use Dynamic\Shopify\Model\ShopifyFile;
use Dynamic\Shopify\Model\ShopifyFileSource;
use Dynamic\Shopify\Model\ShopifyVariant;
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

        $body = json_decode($request->getBody());
        /** @var ShopifyProduct|null $product */
        $product = ShopifyProduct::getByShopifyID($body->id);
        if (!$product) {
            return $this->httpError(404, 'product with id ' . $body->id . ' not found');
        }
        $product->doArchive();
    }

    /**
     * @param HTTPRequest $request
     */
    public function createProduct($request)
    {
        if ($request === null) {
            $request = $this->getRequest();
        }

        $data = json_decode($request->getBody());
        $product = ShopifyProduct::create();
        $product->ShopifyID = $data->id;
        $product->Title = $data->title;
        $product->URLSegment = $data->handle;
        $product->Content = $data->body_html;
        $product->Vendor = $data->vendor;
        $product->ProductType = $data->product_type;
        $product->write();

        foreach ($data->variants as $variantData) {
            $variant = ShopifyVariant::create();
            $variant->ShopifyID = $variantData->id;
            $variant->Title = $variantData->title;
            $variant->SKU = $variantData->sku;
            $variant->Price = $variantData->price;
            $variant->CompareAtPrice = $variantData->compare_at_price;
            $variant->SortOrder = $variantData->position;
            $variant->Inventory = $variantData->inventory_quantity;

            $variant->write();
            $product->Variants()->add($variant);
        }

        foreach ($data->images as $image) {
            $source = ShopifyFileSource::create();
            $source->URL = $image->src;
            $source->Width = $image->width;
            $source->Height = $image->height;
            $source->write();

            $file = ShopifyFile::create();
            $file->ShopifyID = $image->id;
            $file->OriginalSourceID = $source->ID;

            $file->write();
            if ($image->product_id) {
                ShopifyProduct::getByShopifyID($image->product_id)->Files()->add($file);
            }

            foreach ($image->variant_ids as $variantID) {
                $variant = ShopifyVariant::getByShopifyID($variantID);
                $file->Variants()->add($variant);
            }
        }

        if ($data->published_at) {
            $product->publishRecursive();
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
        $product = ShopifyProduct::getByShopifyID($data->id);
        if (!$product) {
            $product = ShopifyProduct::create();
            $product->ShopifyID = $data->id;
        }
        $product->Title = $data->title;
        $product->URLSegment = $data->handle;
        $product->Content = $data->body_html;
        $product->Vendor = $data->vendor;
        $product->ProductType = $data->product_type;
        if ($product->isChanged()) {
            $product->write();
        }

        foreach ($data->variants as $variantData) {
            $variant = ShopifyVariant::getByShopifyID($variantData->id);
            if (!$variant) {
                $variant = ShopifyVariant::create();
                $variant->ShopifyID = $variantData->id;
            }
            $variant->Title = $variantData->title;
            $variant->SKU = $variantData->sku;
            $variant->Price = $variantData->price;
            $variant->CompareAtPrice = $variantData->compare_at_price;
            $variant->SortOrder = $variantData->position;
            $variant->Inventory = $variantData->inventory_quantity;

            if ($variant->isChanged()) {
                $variant->write();
            }
            $product->Variants()->add($variant);
        }

        foreach ($data->images as $image) {
            $file = ShopifyFile::getByShopifyID($image->id);
            if (!$file) {
                $file = ShopifyFile::create();
                $file->ShopifyID = $image->id;
                $source = ShopifyFileSource::create();
            } else {
                $source = $file->OriginalSource();
            }
            $source->URL = $image->src;
            $source->Width = $image->width;
            $source->Height = $image->height;
            if ($source->isChanged()) {
                $source->write();
            }

            if ($file->isChanged()) {
                $file->OriginalSourceID = $source->ID;
                $file->write();
                if ($image->product_id) {
                    ShopifyProduct::getByShopifyID($image->product_id)->Files()->add($file);
                }
            }

            foreach ($image->variant_ids as $variantID) {
                $variant = ShopifyVariant::getByShopifyID($variantID);
                $file->Variants()->add($variant);
            }
        }

        if ($data->published_at) {
            $product->publishRecursive();
        }
    }
}
