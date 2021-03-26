<?php

namespace Dynamic\Shopify\Task;

use Dynamic\Shopify\Client\ShopifyClient;
use Dynamic\Shopify\Model\ShopifyFile;
use Dynamic\Shopify\Page\ShopifyProduct;
use Dynamic\Shopify\Page\ShopifyCollection;
use Dynamic\Shopify\Model\ShopifyVariant;
use GuzzleHttp\Client;
use SilverStripe\Control\Director;
use SilverStripe\Core\Convert;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Dev\Debug;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;

class ShopifyImportTask extends BuildTask
{
    const NOTICE = 0;
    const SUCCESS = 1;
    const WARN = 2;
    const ERROR = 3;

    protected $title = 'Import shopify products';

    protected $description = 'Import shopify products from the configured store';

    private static $segment = 'ShopifyImportTask';

    protected $enabled = true;

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
        $this->beforeImportCollects();
        $this->importCollects($client);
        $this->afterImportCollects();

        if (!Director::is_cli()) {
            echo "</pre>";
        }
        exit('Done');
    }

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

        if (($collections = $collections['body'])) {
            $lastId = $sinceId;
            foreach ($collections->data->collections->edges as $shopifyCollection) {
                // Create the collection
                if ($collection = $this->importObject(ShopifyCollection::class, $shopifyCollection->node)) {
                    $keepCollections[] = $collection->ID;
                    // Create the image
                    if (!empty($shopifyCollection->node->image)) {
                        // The collection image does not have an id so set it from the scr to prevent double
                        // importing the image
                        $image = $shopifyCollection->node->image;
                        $image->id = $image->originalSrc;
                        if ($image = $this->importObject(ShopifyFile::class, $image)) {
                            $collection->FileID = $image->ID;
                            if ($collection->isChanged()) {
                                $collection->write();
                                self::log(
                                    "[{$collection->ShopifyID}] Collection {$collection->Title} saved",
                                    self::SUCCESS
                                );
                            } else {
                                self::log(
                                    "[{$collection->ShopifyID}] Collection {$collection->Title} has no changes",
                                    self::SUCCESS
                                );
                            }
                        }
                    }

                    // Set current publish status for collection
                    if ($collection->CollectionActive && !$collection->isLiveVersion()) {
                        $collection->publishSingle();
                        self::log("[{$collection->ShopifyID}] Published collection {$collection->Title}", self::SUCCESS);
                    } elseif (!$collection->CollectionActive && $collection->IsPublished()) {
                        $collection->doUnpublish();
                        self::log("[{$collection->ShopifyID}] Collection not active on Shopify, unpublished", self::SUCCESS);
                    } else {
                        self::log("[{$collection->ShopifyID}] Collection {$collection->Title} is already published", self::SUCCESS);
                    }

                    $lastId = $shopifyCollection->cursor;
                } else {
                    self::log("[{$shopifyCollection->node->id}] Could not create collection", self::ERROR);
                }
            }

            if ($lastId !== $sinceId) {
                self::log("[{$sinceId}] Try to import the next page of collections since last cursor", self::SUCCESS);
                $this->importCollections($client, $lastId, $keepCollections);
            } else {
                // Cleanup old collections
                foreach (ShopifyCollection::get()->exclude(['ID' => $keepCollections]) as $collection) {
                    $collectionShopifyId = $collection->ShopifyID;
                    $collection->doUnpublish();
                    $collection->delete();
                    self::log(
                        "[{$collectionShopifyId}] Deleted old collection {$collection->Title}",
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

        if (($products = $products['body'])) {
            $lastId = $sinceId;
            $shopifyProducts = new ArrayList((array)$products->data->products->edges);
            if ($shopifyProducts->exists()) {
                foreach ($products->data->products->edges as $shopifyProduct) {
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
                                }
                            }

                            // remove unused images
                            if (empty($keepImages)) {
                                $files = $product->Files();
                            } else {
                                $files = $product->Files()->exclude(['ID' => $keepImages]);
                            }
                            foreach ($files as $image) {
                                $imageId = $image->ID;
                                $imageShopifyId = $image->ShopifyID;
                                $image->doUnpublish();
                                $image->delete();
                                self::log(
                                    "[{$imageId}][{$imageShopifyId}] Deleted old image {$image->Title} connected to product",
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
                                    if ($variant->isChanged()) {
                                        $variant->write();
                                        self::log("[{$variant->ShopifyID}] Saved Variant {$variant->Title}", self::SUCCESS);
                                    }
                                    $keepVariants[] = $variant->ID;
                                    $product->Variants()->add($variant);
                                }
                            }

                            // remove unused variants
                            foreach ($product->Variants()->exclude(['ID' => $keepVariants]) as $variant) {
                                $variantId = $variant->ID;
                                $variantShopifyId = $variant->ShopifyID;
                                $variant->delete();
                                self::log(
                                    "[{$variantId}][{$variantShopifyId}] Deleted old variant {$variant->Title} connected to product",
                                    self::SUCCESS
                                );
                            }
                        }

                        // Write the product record if changed
                        if ($product->isChanged()) {
                            $product->write();
                            self::log("[{$product->ShopifyID}] Saved changes in product {$product->Title}", self::SUCCESS);
                        } else {
                            self::log("[{$product->ShopifyID}] Product {$product->Title} has no changes", self::SUCCESS);
                        }

                        // Set current publish status for product
                        if ($product->ProductActive && !$product->isLiveVersion()) {
                            $product->publishSingle();
                            self::log("[{$product->ShopifyID}] Published product {$product->Title}", self::SUCCESS);
                        } elseif (!$product->ProductActive && $product->IsPublished()) {
                            $product->doUnpublish();
                            self::log("[{$product->ShopifyID}] Product not active on Shopify, unpublished", self::SUCCESS);
                        } else {
                            self::log("[{$product->ShopifyID}] Product {$product->Title} is already published", self::SUCCESS);
                        }
                        $lastId = $shopifyProduct->cursor;
                    } else {
                        self::log("[{$lastId}] Could not create product", self::ERROR);
                    }
                }

                if ($lastId !== $sinceId) {
                    self::log("[{$sinceId}] Try to import the next page of products since last cursor", self::SUCCESS);
                    $this->importProducts($client, $lastId, $keepProducts);
                } else {
                    // Cleanup old products
                    foreach (ShopifyProduct::get()->exclude(['ID' => $keepProducts]) as $product) {
                        $productShopifyId = $product->ShopifyID;
                        $product->doUnpublish();
                        $product->delete();
                        self::log(
                            "[{$productShopifyId}] Deleted old product {$product->Title}",
                            self::SUCCESS
                        );
                    }
                }
            }
        }
    }

    /**
     * Import the Shopify Collects
     * @param Client $client
     *
     * @throws \SilverStripe\ORM\ValidationException
     */
    public function importCollects(ShopifyClient $client, $sinceId = 0)
    {
        $collections = ShopifyCollection::get();
        if (!$collections->count()) {
            self::log("[collection] No collections to parse");
            return;
        }

        foreach ($collections as $collection) {
            /** @var ShopifyCollection $collection */
            $collects = null;

            try {
                $collects = $client->collectionProducts($collection->URLSegment, [
                    'query' => [
                        'limit' => 250,
                        'since_id' => $sinceId
                    ]
                ]);
            } catch (\GuzzleHttp\Exception\GuzzleException $e) {
                exit($e->getMessage());
            }

            if (($collects = $collects['body'])) {
                $lastId = $sinceId;
                foreach ($collects->data->collectionByHandle->products->edges as $shopifyCollect) {
                    if ($product = ShopifyProduct::getByShopifyID(self::parseShopifyID($shopifyCollect->node->id))
                    ) {
                        $collection->Products()->add($product, [
                            //'ShopifyID' => self::parseShopifyID($shopifyCollect->node->id),
                            //'SortValue' => $shopifyCollect->sort_value,
                            //'Position' => $shopifyCollect->position,
                            'Imported' => true
                        ]);

                        $product->ParentID = $collection->ID;
                        if ($product->isChanged()) {
                            $product->write();
                        }

                        $lastId = $product->ShopifyID;
                        self::log("[" . self::parseShopifyID($shopifyCollect->node->id) . "] Created collect between
                        Product[{$product->ID}] and Collection[{$collection->ID}]", self::SUCCESS);
                    }
                }

                if ($lastId !== $sinceId) {
                    self::log("[{$sinceId}] Try to import the next page of collects since last id", self::SUCCESS);
                    //$this->importCollects($client, $lastId);
                }
            }
        }
    }

    // todo make this flexible so it's also usable for Products, Variants, Images, Collections.
    // if made flexible should also handle versions.
    public function beforeImportCollects()
    {
        // Set all imported values to 0
        $schema = DataObject::getSchema()->manyManyComponent(ShopifyCollection::class, 'Products');
        if (isset($schema['join']) && $join = $schema['join']) {
            DB::query("UPDATE `$join` SET `Imported` = 0 WHERE 1");
        }
    }

    public function afterImportCollects()
    {
        // Delete all collects that where not given during importe
        $schema = DataObject::getSchema()->manyManyComponent(ShopifyCollection::class, 'Products');
        if (isset($schema['join']) && $join = $schema['join']) {
            DB::query("DELETE FROM `$join` WHERE `Imported` = 0");
        }
    }

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
