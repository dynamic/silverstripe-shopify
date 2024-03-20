<?php

namespace Dynamic\Shopify\Extension;

use Dynamic\Shopify\Client\ShopifyClient;
use Dynamic\Shopify\Page\ShopifyCollection;
use Dynamic\Shopify\Page\ShopifyProduct;
use Dynamic\Shopify\Task\ShopifyImportTask;
use SilverStripe\Admin\LeftAndMainExtension;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Security;

/**
 * Class ShopifyFetchExtension
 *
 * @property LeftAndMain|ShopifyFetchExtension $owner
 */
class ShopifyFetchExtension extends LeftAndMainExtension
{
    /**
     * @config
     * @var array
     */
    private static $allowed_actions = [
        'shopifyCollectionFetch',
        'shopifyProductFetch',
    ];

    /**
     * @var ShopifyClient
     */
    private $client;

    /**
     *
     */
    protected function setClient()
    {
        try {
            $client = new ShopifyClient();
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            exit($e->getMessage());
        } catch (\Exception $e) {
            exit($e->getMessage());
        }
        $this->client = $client;
    }

    /**
     * @return ShopifyClient
     */
    public function getClient()
    {
        if (!$this->client) {
            $this->setClient();
        }

        return $this->client;
    }

    /**
     * @param array $data
     * @param Form $form
     * @return \SilverStripe\Control\HTTPResponse
     * @throws \Exception
     */
    public function shopifyCollectionFetch($data, $form)
    {
        $id = $data['ID'];
        $record = $this->getShopifyObject($id);

        if ($record && !$record->canEdit()) {
            return Security::permissionFailure();
        }

        if (!$record || !$record->ShopifyID) {
            $this->owner->httpError(404, "Bad shopify ID: $record->ShopifyID");
        }

        $importTask = ShopifyImportTask::create();
        $previousSilent = $importTask->config()->get('silent');
        $importTask->config()->set('silent', true);
        $shopifyCollection = $this->getClient()->collection($record->ShopifyID)['body']->data->collection;

        if ($collection = $importTask->importObject(ShopifyCollection::class, $shopifyCollection)) {
            // Create the image
            $importTask->importCollectionFiles($this->getClient(), $collection);

            if ($collection->isChanged()) {
                $collection->write();
            }

            // Set current publish status for collection
            if ($collection->CollectionActive && !$collection->isLiveVersion()) {
                $collection->publishRecursive();
            } elseif (!$collection->CollectionActive && $collection->IsPublished()) {
                $collection->doUnpublish();
            }

            $importTask->config()->set('silent', $previousSilent);
            return $this->returnSuccess();
        }
        $this->owner->httpError(500, "Could not create collection: $record->ShopifyID");
    }

    /**
     * @param array $data
     * @param Form $form
     * @return \SilverStripe\Control\HTTPResponse
     * @throws \Exception
     */
    public function shopifyProductFetch($data, $form)
    {
        $id = $data['ID'];
        $record = $this->getShopifyObject($id);

        if ($record && !$record->canEdit()) {
            return Security::permissionFailure();
        }

        if (!$record || !$record->ShopifyID) {
            $this->owner->httpError(404, "Bad shopify ID: $record->ShopifyID");
        }

        /** @var ShopifyImportTask $importTask */
        $importTask = ShopifyImportTask::create();
        $previousSilent = $importTask->config()->get('silent');
        $importTask->config()->set('silent', true);
        $shopifyProduct = $this->getClient()->product($record->ShopifyID)['body']->data->product;

        if ($product = $importTask->importObject(ShopifyProduct::class, $shopifyProduct)) {
            $importTask->importProductFiles($this->getClient(), $product);
            $importTask->importVariants($this->getClient(), $product, $shopifyProduct);

            // Write the product record if changed
            if ($product->isChanged()) {
                $product->write();
            }

            // Set current publish status for product
            if ($product->ProductActive) {
                $product->publishRecursive();
            } elseif (!$product->ProductActive && $product->IsPublished()) {
                $product->doUnpublish();
            }
            $importTask->config()->set('silent', $previousSilent);
            return $this->returnSuccess();
        } else {
            $importTask->config()->set('silent', $previousSilent);
            $this->owner->httpError(500, "Could not create product: $record->ShopifyID");
        }
    }

    /**
     * @param int $id
     * @return DataObject|ShopifyCollection|ShopifyProduct
     */
    protected function getShopifyObject($id)
    {
        $className = $this->owner->currentPage()->getClassName();
        return DataObject::get_by_id($className, $id);
    }

    /**
     * @return mixed
     */
    protected function returnSuccess()
    {
        $this->owner->getResponse()->addHeader(
            'X-Status',
            rawurlencode(_t(__CLASS__ . '.UPDATED', 'Updated.'))
        );
        return $this->owner->getResponseNegotiator()->respond($this->owner->getRequest());
    }
}
