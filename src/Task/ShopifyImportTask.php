<?php

namespace Dynamic\Shopify\Task;

use Dynamic\Shopify\Client\ShopifyClient;
use Dynamic\Shopify\Model\ShopifyFile;
use Dynamic\Shopify\Page\ShopifyProduct;
use Dynamic\Shopify\Page\ShopifyCollection;
use Dynamic\Shopify\Model\ShopifyVariant;
use GuzzleHttp\Client;
use SilverStripe\CMS\Model\VirtualPage;
use SilverStripe\Control\Director;
use SilverStripe\Core\Convert;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;

/**
 * Class ShopifyImportTask
 * @package Dynamic\Shopify\Task
 */
class ShopifyImportTask extends BuildTask
{
    const NOTICE = 0;
    const SUCCESS = 1;
    const WARN = 2;
    const ERROR = 3;

    /**
     * @var string
     */
    protected $title = 'Shopify - import products';

    /**
     * @var string
     */
    protected $description = 'Import shopify products from the configured store';

    /**
     * @var string
     */
    private static $segment = 'ShopifyImportTask';

    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @param \SilverStripe\Control\HTTPRequest $request
     * @throws \SilverStripe\ORM\ValidationException
     */
    public function run($request)
    {
        if (!Director::is_cli()) {
            echo "<pre>";
        }

        try {
            $client = new ShopifyClient();
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            exit($e->getMessage());
        } catch (\Exception $e) {
            exit($e->getMessage());
        }

        $this->importCollections($client);
        $this->importProducts($client);
        $this->arrangeSiteMap($client);

        if (!Director::is_cli()) {
            echo "</pre>";
        }
        exit('Done');
    }

    /**
     * @param ShopifyClient $client
     * @param null $sinceId
     * @param array $keepCollections
     */
    public function importCollections(ShopifyClient $client, $sinceId = null, $keepCollections = [])
    {
        try {
            $collections = $client->collections(
                10,
                $sinceId
            );
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            exit($e->getMessage());
        }

        if (($collections && $collections['body'])) {
            $lastId = $sinceId;
            foreach ($collections['body']->data->collections->edges as $shopifyCollection) {
                // Create the collection
                if ($collection = $this->importObject(ShopifyCollection::class, $shopifyCollection->node)) {
                    $keepCollections[] = $collection->ID;

                    // Create the image
                    if (!empty($shopifyCollection->node->image)) {
                        if ($image = $this->importObject(ShopifyFile::class, $shopifyCollection->node->image)) {
                            $collection->FileID = $image->ID;
                        } else {
                            self::log(
                                "[{$shopifyCollection->node->image->id}] Could not create file",
                                self::ERROR
                            );
                        }
                    } else {
                        if ($collection->FileID) {
                            $file = ShopifyFile::get()->byID($collection->FileID);
                            $fileTitle = $file->Title;
                            $fileShopifyID = $file->ShopifyID;
                            $file->doUnpublish();
                            $file->delete();
                            $collection->FileID = 0;
                            self::log(
                                "[{$fileShopifyID}] Deleted file {$fileTitle}",
                                self::SUCCESS
                            );
                        }
                    }

                    if ($collection->isChanged()) {
                        $collection->write();
                        self::log(
                            "[{$collection->ShopifyID}] Saved collection {$collection->Title}",
                            self::SUCCESS
                        );
                    } else {
                        self::log(
                            "[{$collection->ShopifyID}] Collection {$collection->Title} is unchanged",
                            self::SUCCESS
                        );
                    }

                    // Set current publish status for collection
                    if ($collection->CollectionActive && !$collection->isLiveVersion()) {
                        $collection->publishRecursive();
                        self::log(
                            "[{$collection->ShopifyID}] Published collection {$collection->Title}",
                            self::SUCCESS
                        );
                    } elseif (!$collection->CollectionActive && $collection->IsPublished()) {
                        $collection->doUnpublish();
                        self::log(
                            "[{$collection->ShopifyID}] Unpublished collection {$collection->Title}",
                            self::SUCCESS
                        );
                    }

                    $lastId = $shopifyCollection->cursor;
                } else {
                    self::log(
                        "[{$shopifyCollection->node->id}] Could not create collection",
                        self::ERROR
                    );
                }
            }

            if ($collections['body']->data->collections->pageInfo->hasNextPage) {
                self::log(
                    "[{$sinceId}] Try to import the next page of collections since last cursor",
                    self::SUCCESS
                );
                $this->importCollections($client, $lastId, $keepCollections);
            } else {
                // Cleanup old collections
                foreach (ShopifyCollection::get()->exclude(['ID' => $keepCollections]) as $collection) {
                    $collectionShopifyId = $collection->ShopifyID;
                    $collectionTitle = $collection->Title;
                    $collection->doUnpublish();
                    self::log(
                        "[{$collectionShopifyId}] Unpublished collection {$collectionTitle}",
                        self::SUCCESS
                    );
                }
            }
        }
    }

    /**
     * Import the shopify products
     * @param Client $client
     * @param array $ids
     *
     * @throws \Exception
     */
    public function importProducts(ShopifyClient $client, $sinceId = null, $keepProducts = [])
    {
        try {
            $products = $client->products($limit = 10, $sinceId);
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            exit($e->getMessage());
        }

        if (($products && $products['body'])) {
            $lastId = $sinceId;
            $shopifyProducts = new ArrayList((array)$products['body']->data->products->edges);
            if ($shopifyProducts->exists()) {
                foreach ($products['body']->data->products->edges as $shopifyProduct) {
                    // Create the product
                    if ($product = $this->importObject(ShopifyProduct::class, $shopifyProduct->node)) {
                        $keepProducts[] = $product->ID;

                        // Create images
                        $images = new ArrayList((array)$shopifyProduct->node->images->edges);
                        if ($images->exists()) {
                            $keepImages = [];
                            foreach ($shopifyProduct->node->images->edges as $shopifyImage) {
                                if ($image = $this->importObject(ShopifyFile::class, $shopifyImage->node)) {
                                    $keepImages[] = $image->ID;
                                    $product->Files()->add($image);
                                } else {
                                    self::log(
                                        "[{$shopifyImage->node->id}] Could not create file",
                                        self::ERROR
                                    );
                                }
                            }

                            // remove unused images
                            if (empty($keepImages)) {
                                $files = $product->Files();
                            } else {
                                $files = $product->Files()->exclude(['ID' => $keepImages]);
                            }
                            foreach ($files as $file) {
                                $fileTitle = $file->Title;
                                $fileShopifyID = $file->ShopifyID;
                                $file->doUnpublish();
                                $file->delete();
                                self::log(
                                    "[{$fileShopifyID}] Deleted file {$fileTitle}",
                                    self::SUCCESS
                                );
                            }
                        }

                        // Create variants
                        $variants = new ArrayList((array)$shopifyProduct->node->variants->edges);
                        if ($variants->exists()) {
                            $keepVariants = [];
                            foreach ($shopifyProduct->node->variants->edges as $shopifyVariant) {
                                if ($variant = $this->importObject(ShopifyVariant::class, $shopifyVariant->node)) {
                                    $variant->ProductID = $product->ID;

                                    // Create the file
                                    if (!empty($shopifyVariant->node->image)) {
                                        if ($image = $this->importObject(ShopifyFile::class, $shopifyVariant->node->image)) {
                                            $variant->FileID = $image->ID;
                                        } else {
                                            self::log(
                                                "[{$shopifyVariant->node->image->id}] Could not create file",
                                                self::ERROR
                                            );
                                        }
                                    } else {
                                        if ($variant->FileID) {
                                            $file = ShopifyFile::get()->byID($variant->FileID);
                                            $fileShopifyID = $file->ShopifyID;
                                            $fileTitle = $file->Title;
                                            $file->doUnpublish();
                                            $file->delete();
                                            $variant->FileID = 0;
                                            self::log(
                                                "[{$fileShopifyID}] Deleted file {$fileTitle}",
                                                self::SUCCESS
                                            );
                                        }
                                    }

                                    if ($variant->isChanged()) {
                                        $variant->write();
                                        self::log(
                                            "[{$variant->ShopifyID}] Saved Variant {$variant->Title}",
                                            self::SUCCESS
                                        );
                                    }
                                    $keepVariants[] = $variant->ID;
                                    $product->Variants()->add($variant);
                                } else {
                                    self::log(
                                        "[{$shopifyVariant->node->ID}] Could not create variant",
                                        self::ERROR
                                    );
                                }
                            }

                            // remove unused variants
                            foreach ($product->Variants()->exclude(['ID' => $keepVariants]) as $variant) {
                                $variantId = $variant->ID;
                                $variantShopifyId = $variant->ShopifyID;
                                $variant->delete();
                                self::log(
                                    // phpcs:ignore Generic.Files.LineLength.TooLong
                                    "[{$variantShopifyId}] Deleted variant {$variant->Title} of product [{$product->ShopifyID}]",
                                    self::SUCCESS
                                );
                            }
                        }

                        // Write the product record if changed
                        if ($product->isChanged()) {
                            $product->write();
                            self::log(
                                "[{$product->ShopifyID}] Saved product {$product->Title}",
                                self::SUCCESS
                            );
                        } else {
                            self::log(
                                "[{$product->ShopifyID}] Product {$product->Title} is unchanged",
                                self::SUCCESS
                            );
                        }

                        // Set current publish status for product
                        if ($product->ProductActive && !$product->isLiveVersion()) {
                            $product->publishRecursive();
                            self::log(
                                "[{$product->ShopifyID}] Published product {$product->Title}",
                                self::SUCCESS
                            );
                        } elseif (!$product->ProductActive && $product->IsPublished()) {
                            $product->doUnpublish();
                            self::log(
                                "[{$product->ShopifyID}] Unpublished product {$product->Title}",
                                self::SUCCESS
                            );
                        }
                        $lastId = $shopifyProduct->cursor;
                    } else {
                        self::log("[{$shopifyProduct->node->id}] Could not create product", self::ERROR);
                    }
                }

                if ($products['body']->data->products->pageInfo->hasNextPage) {
                    self::log(
                        "[{$sinceId}] Try to import the next page of products since last cursor",
                        self::SUCCESS
                    );
                    $this->importProducts($client, $lastId, $keepProducts);
                } else {
                    // Cleanup old products
                    foreach (ShopifyProduct::get()->exclude(['ID' => $keepProducts]) as $product) {
                        $productShopifyId = $product->ShopifyID;
                        $product->doUnpublish();
                        self::log(
                            "[{$productShopifyId}] Unpublished  product {$product->Title}",
                            self::SUCCESS
                        );
                    }
                }
            }
        }
    }

    /**
     * @param ShopifyClient $client
     * @param null $sinceId
     * @throws \SilverStripe\ORM\ValidationException
     */
    public function arrangeSiteMap(ShopifyClient $client, $sinceId = null)
    {
        $products = ShopifyProduct::get();
        if (!$products->count()) {
            self::log("[Product] No products to parse");
            return;
        }

        foreach ($products as $product) {
            $this->generateVirtuals($client, $product);
        }
    }

    /**
     * @param $client
     * @param $product
     * @param null $sinceId
     * @param array $keepVirtuals
     * @throws \SilverStripe\ORM\ValidationException
     */
    public function generateVirtuals($client, $product, $sinceId = null, $keepVirtuals = [])
    {
        $collections = null;

        try {
            $collections = $client->productCollections($product->ShopifyID, 25, $sinceId);
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            exit($e->getMessage());
        }

        if ($collections && $collections['body']) {
            $lastId = $sinceId;
            foreach ($collections['body']->data->product->collections->edges as $index => $shopifyCollection) {
                if ($collection = ShopifyCollection::getByShopifyID(self::parseShopifyID($shopifyCollection->node->id))
                ) {
                    if ($index < 1) {
                        $product->ParentID = $collection->ID;
                        if ($product->isChanged('ParentID', DataObject::CHANGE_VALUE)) {
                            $product->write();
                            if ($product->isPublished() && !$product->isLiveVersion()) {
                                $product->publishSingle();
                            }
                            self::log(
                                "Collection [$collection->ShopifyID] set as parent of Product [$product->ShopifyID] ",
                                self::SUCCESS
                            );
                        } else {
                            self::log(
                                "[{$product->ShopifyID}] Product is unchanged",
                                self::SUCCESS
                            );
                        }
                    } else {
                        if (!$virtual = VirtualPage::get()->filter([
                            'CopyContentFromID' => $product->ID,
                            'ParentID' => $collection->ID,
                        ])->first()) {
                            $virtual = VirtualPage::create();
                            $virtual->CopyContentFromID = $product->ID;
                            $virtual->ParentID = $collection->ID;
                            $virtual->write();
                            $ShopifyID = $product->ShopifyID;
                            $CollectionID = $collection->ShopifyID;
                            self::log(
                                "[$ShopifyID] Virtual Product created under Collection [$CollectionID]",
                                self::SUCCESS
                            );
                        }
                        $keepVirtuals[] = $virtual->ID;
                        if ($virtual->isChanged()) {
                            $virtual->write();
                            self::log(
                                "[{$product->ShopifyID}] Updated virtual product",
                                self::SUCCESS
                            );
                        } else {
                            self::log(
                                "[{$product->ShopifyID}] Virtual product is unchanged",
                                self::SUCCESS
                            );
                        }
                        // Set current publish status for virtual product
                        if ($product->ProductActive && !$virtual->isLiveVersion()) {
                            $virtual->publishSingle();
                            self::log(
                                "[{$product->ShopifyID}] Published virtual product",
                                self::SUCCESS
                            );
                        } elseif (!$product->ProductActive && $virtual->IsPublished()) {
                            $virtual->doUnpublish();
                            $virtual->delete();
                            self::log(
                                "[{$product->ShopifyID}] Deleted virtual product",
                                self::SUCCESS
                            );
                        }
                    }
                    $lastId = $shopifyCollection->cursor;
                }
            }

            if ($collections['body']->data->product->collections->pageInfo->hasNextPage) {
                self::log(
                    "[{$sinceId}] Try to import the next page of collections since last cursor",
                    self::SUCCESS
                );
                $this->generateVirtuals($client, $productId, $lastId, $keepVirtuals);
            } else {
                // Cleanup old virtuals
                $virtuals = VirtualPage::get()
                    ->filter('CopyContentFromID', $product->ID);
                if ($keepVirtuals) {
                    $virtuals = $virtuals->exclude(['ID' => $keepVirtuals]);
                }
                if ($virtuals) {
                    foreach ($virtuals as $oldVirtual) {
                        $virtualShopifyId = $product->ShopifyID;
                        $virtualTitle = $product->Title;
                        $oldVirtual->doUnpublish();
                        $oldVirtual->delete();
                        self::log(
                            "[{$virtualShopifyId}] Deleted virtual product {$virtualTitle}",
                            self::SUCCESS
                        );
                    }
                }
            }
        }
    }

    /**
     * @param $class
     * @param $shopifyData
     * @return null
     */
    private function importObject($class, $shopifyData)
    {
        $object = null;
        $shopifyData->id = self::parseShopifyID($shopifyData->id);
        try {
            $object = $class::findOrMakeFromShopifyData($shopifyData);
            self::log("[{$object->ShopifyID}] Created {$class} {$object->Title}", self::SUCCESS);
        } catch (\Exception $e) {
            self::log($e->getMessage(), self::ERROR);
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            self::log("[Guzzle error] {$e->getMessage()}", self::ERROR);
        }

        return $object;
    }

    /**
     * Loop the given data map and possible sub maps
     *
     * @param array $map
     * @param $object
     * @param $data
     */
    public static function loop_map($map, &$object, $data)
    {
        foreach ($map as $from => $to) {
            if (is_array($to) && is_object($data[$from])) {
                self::loop_map($to, $object, $data[$from]);
            } elseif (isset($data[$from])) {
                $object->{$to} = $data[$from];
            }
        }
    }

    /**
     * @param $shopifyID
     * @return mixed|string
     */
    public static function parseShopifyID($shopifyID)
    {
        $exploded = explode('/', $shopifyID);
        return end($exploded);
    }

    /**
     * Log messages to the console or cron log
     *
     * @param $message
     * @param $code
     */
    protected static function log($message, $code = self::NOTICE)
    {
        switch ($code) {
            case self::ERROR:
                echo "[ ERROR ] {$message}\n";
                break;
            case self::WARN:
                echo "[WARNING] {$message}\n";
                break;
            case self::SUCCESS:
                echo "[SUCCESS] {$message}\n";
                break;
            case self::NOTICE:
            default:
                echo "[NOTICE ] {$message}\n";
                break;
        }
    }
}
