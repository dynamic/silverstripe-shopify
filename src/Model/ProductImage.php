<?php

namespace Dynamic\Shopify\Model;

use Dynamic\Shopify\Client\ShopifyClient;
use Dynamic\Shopify\Page\Product;
use Dynamic\Shopify\Page\ProductVariant;
use Dynamic\Shopify\Task\ShopifyImportTask\ShopifyImportTask;
use GuzzleHttp\Client;
use SilverStripe\Assets\Folder;
use SilverStripe\Assets\Image;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Security;

class ProductImage extends Image
{
    private static $table_name = 'ShopifyImage';

    private static $db = [
        'Sort' => 'Int',
        'ShopifyID' => 'Varchar',
        'OriginalSrc' => 'Varchar'
    ];

    private static $default_sort = 'Sort ASC';

    private static $data_map = [
        'id' => 'ShopifyID',
        'alt' => 'Title',
        'position' => 'Sort',
        'src' => 'OriginalSrc',
        'created_at' => 'Created',
        'updated_at' => 'LastEdited'
    ];

    private static $has_one = [
        'Product' => Product::class
    ];

    private static $has_many = [
        'Variants' => ProductVariant::class
    ];

    private static $indexes = [
        'ShopifyID' => true
    ];

    private static $summary_fields = [
        'CMSThumbnail' => 'Image',
        'Title',
        'ShopifyID'
    ];

    /**
     * Creates a new Shopify Image from the given data
     *
     * @param $shopifyImage
     * @return Image
     * @throws \SilverStripe\ORM\ValidationException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function findOrMakeFromShopifyData($shopifyImage)
    {
        if (!$image = self::getByShopifyID($shopifyImage->id)) {
            $image = self::create();
        }

        $map = self::config()->get('data_map');
        ShopifyImportTask::loop_map($map, $image, $shopifyImage);

        // import the image if the source has changed
        if ($image->isChanged('OriginalSrc', DataObject::CHANGE_VALUE)) {
            $folder = isset($shopifyImage->product_id) ? $shopifyImage->product_id : 'collection';
            $image->downloadImage($image->OriginalSrc, "shopify/$folder");
        }

        if ($image->isChanged()) {
            $image->write();
        }

        if (!$image->isLiveVersion()) {
            $image->publishSingle();
        }

        return $image;
    }

    public static function getByShopifyID($shopifyId)
    {
        return DataObject::get_one(self::class, ['ShopifyID' => $shopifyId]);
    }

    /**
     * Download the image from the shopify CDN
     *
     * @param $src
     * @param $folder
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function downloadImage($src, $folder)
    {
        $client = new Client(['http_errors' => false]);
        $request = $client->request('GET', $src);
        $folder = Folder::find_or_make($folder);
        $sourcePath = pathinfo($src);
        $fileName = explode('?', $sourcePath['basename'])[0];
        $this->setFromString($request->getBody()->getContents(), $fileName);
        $this->ParentID = $folder->ID;
        $this->OwnerID = ($user = Security::getCurrentUser()) ? $user->ID : 0;
        $this->publishFile();
    }
}
