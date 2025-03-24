<?php

namespace Dynamic\Shopify\Task;

use Dynamic\Shopify\Client\ShopifyClient;
use Dynamic\Shopify\Model\ShopifyFile;
use Dynamic\Shopify\Page\ShopifyProduct;
use Dynamic\Shopify\Page\ShopifyCollection;
use Dynamic\Shopify\Model\ShopifyVariant;
use GuzzleHttp\Client;
use Osiset\BasicShopifyAPI\ResponseAccess;
use SilverStripe\CMS\Model\VirtualPage;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;

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
     * @var null
     */
    private $previous_user = null;

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

        $this->changeToTaskUser();

        self::log("IMPORT COLLECTIONS", self::NOTICE);
        $this->importCollections($client);

        self::log("IMPORT PRODUCTS", self::NOTICE);
        $this->importProducts($client);

        self::log("ARRANGE SITEMAP", self::NOTICE);
        $this->arrangeSiteMap($client);

        $this->changeToPreviousUser();

        if (!Director::is_cli()) {
            echo "</pre>";
        }
        exit('Done');
    }

    /**
     * @return DataObject|Member|null
     * @throws ValidationException
     */
    protected function findOrCreateShopifyTaskUser()
    {
        if (!$member = Member::get()->filter('Email', 'shopifytask')->first()) {
            $member = Member::create();
            $member->FirstName = 'Shopify';
            $member->Surname = 'Task';
            $member->Email = 'shopifytask';
            $member->write();
        }

        return $member;
    }

    /**
     *
     */
    protected function changeToTaskUser()
    {
        $this->previous_user = Security::getCurrentUser();
        Security::setCurrentUser($this->findOrCreateShopifyTaskUser());
    }

    /**
     *
     */
    protected function changeToPreviousUser()
    {
        Security::setCurrentUser($this->previous_user);
        $this->previous_user = null;
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

        if (($collections && $collections['body']) && isset($collections['body']->data)) {
            $lastId = $sinceId;
            foreach ($collections['body']->data->collections->edges as $shopifyCollection) {
                // Create the collection
                if ($collection = $this->importObject(ShopifyCollection::class, $shopifyCollection->node)) {
                    $keepCollections[] = $collection->ID;

                    // Create the image
                    $this->importCollectionFiles($client, $collection);

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
                    self::NOTICE
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
     * @param ShopifyClient $client
     * @param ShopifyCollection $collection
     */
    public function importCollectionFiles($client, $collection)
    {
        try {
            $shopifyFile = $client->collectionMedia($collection->ShopifyID);
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            exit($e->getMessage());
        }

        if (!$shopifyFile || !$shopifyFile['body']) {
            return;
        }

        if (!$shopifyFile['body']->data->offsetExists('collection')) {
            return;
        }

        if (!$shopifyFile['body']->data->collection->offsetExists('image')) {
            return;
        }

        /** @var ShopifyFile $file */
        if ($file = $this->importObject(ShopifyFile::class, $shopifyFile['body']->data->collection->image)) {
            $file->CollectionID = $collection->ID;
            $file->write();
        } else {
            self::log(
                "[{$shopifyFile->node->id}] Could not create file",
                self::ERROR
            );
        }
    }

    /**
     * Import the shopify products
     *
     * @param ShopifyClient $client
     * @param null|string $sinceId
     * @param array $keepCollections
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

                        $this->importProductFiles($client, $product);
                        $this->importVariants($client, $product, $shopifyProduct->node);

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
                        self::NOTICE
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
     * @param ShopifyProduct $product
     * @param array|ResponseAccess $shopifyProduct
     */
    public function importVariants($client, $product, $shopifyProduct)
    {
        // Create variants
        $variants = new ArrayList((array)$shopifyProduct->variants->edges);
        if (!$variants->exists()) {
            return;
        }
        $keepVariants = [];
        foreach ($shopifyProduct->variants->edges as $shopifyVariant) {
            if ($variant = $this->importObject(ShopifyVariant::class, $shopifyVariant->node)) {
                $variant->ProductID = $product->ID;

                $this->importProductVariantFiles($client, $variant);

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
        if (count($keepVariants)) {
            $variants = $product->Variants()->exclude(['ID' => $keepVariants]);
        } else {
            $variants = $product->Variants();
        }
        foreach ($variants as $variant) {
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

    /**
     * @param ShopifyClient $client
     * @param ShopifyVariant $product
     * @param int $position
     * @param array $keepFiles
     */
    private function importProductVariantFiles($client, $product)
    {
        try {
            $shopifyFiles = $client->productMedia($product->ShopifyID, 1, null, true);
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            exit($e->getMessage());
        }

        if (!$shopifyFiles || !$shopifyFiles['body']) {
            return;
        }

        if (!$shopifyFiles['body']->data->offsetExists('productVariant')) {
            return;
        }

        if (!$shopifyFiles['body']->data->productVariant->offsetExists('media')) {
            return;
        }

        $edges = $shopifyFiles['body']->data->productVariant->media->edges;
        if (!$edges->count()) {
            return;
        }

        $shopifyFile = $edges->offsetGet(0);
        /** @var ShopifyFile $file */
        if ($file = $this->importObject(ShopifyFile::class, $shopifyFile->node)) {
            $file->VariantID = $product->ID;
            $file->write();
        } else {
            self::log(
                "[{$shopifyFile->node->id}] Could not create file",
                self::ERROR
            );
        }
    }

    /**
     * @param ShopifyClient $client
     * @param ShopifyProduct $product
     * @param string|null $sinceId
     * @param int $position
     * @param array $keepFiles
     */
    public function importProductFiles($client, $product, $sinceId = null, $pos = 0, $keepFiles = [])
    {
        try {
            $shopifyFiles = $client->productMedia($product->ShopifyID, $limit = 25, $sinceId);
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            exit($e->getMessage());
        }

        if (!$shopifyFiles || !$shopifyFiles['body']) {
            return;
        }

        $lastId = $sinceId;
        $position = $pos;
        foreach ($shopifyFiles['body']->data->product->media->edges as $shopifyFile) {
            $shopifyFile->node->offsetSet('position', $position);
            /** @var ShopifyFile $file */
            if ($file = $this->importObject(ShopifyFile::class, $shopifyFile->node)) {
                $file->SortOrder = $position;
                $keepFiles[] = $file->ID;
                $product->Files()->add($file);
                $lastId = $shopifyFile->cursor;
            } else {
                self::log(
                    "[{$shopifyFile->node->id}] Could not create file",
                    self::ERROR
                );
            }
            $position++;
        }

        if ($shopifyFiles['body']->data->product->media->pageInfo->hasNextPage) {
            $this->importProductFiles($client, $product, $lastId, $position, $keepFiles);
        } else {
            // remove unused images
            if (empty($keepFiles)) {
                $files = $product->Files();
            } else {
                $files = $product->Files()->exclude(['ID' => $keepFiles]);
            }
            foreach ($files as $file) {
                $fileTitle = $file->Title;
                $fileShopifyID = $file->ShopifyID;
                $file->delete();
                self::log(
                    "[{$fileShopifyID}] Deleted file {$fileTitle}",
                    self::SUCCESS
                );
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

            if (!$collections['body']->data->product) {
                return;
            }
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
                                "[$collection->ShopifyID] Collection set as parent of product [$product->ShopifyID] ",
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
                    self::NOTICE
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
    public function importObject($class, $shopifyData)
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
            if (!isset($from, $data) || !$data->offsetExists($from)) {
                continue;
            }
            if (is_array($to) && (is_object($data[$from]) || is_array($data[$from]))) {
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
    public static function log($message, $code = self::NOTICE)
    {
        if (Config::inst()->get(static::class, 'silent')) {
            return;
        }
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
