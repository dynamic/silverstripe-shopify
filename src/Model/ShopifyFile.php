<?php

namespace Dynamic\Shopify\Model;

use Dynamic\Shopify\Client\ShopifyClient;
use Dynamic\Shopify\Page\ShopifyCollection;
use Dynamic\Shopify\Page\ShopifyProduct;
use Dynamic\Shopify\Page\ShopifyVariant;
use Dynamic\Shopify\Task\ShopifyImportTask;
use GuzzleHttp\Client;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Security;

/**
 * Class ShopifyFile
 * @package Dynamic\Shopify\Model
 */
class ShopifyFile extends File
{
    /**
     * @var string
     */
    private static $table_name = 'ShopifyFile';

    /**
     * @var string[]
     */
    private static $db = [
        'SortOrder' => 'Int',
        'ShopifyID' => 'Varchar',
        'OriginalSrc' => 'Varchar',
        'Width' => 'Int',
        'Height' => 'Int',
    ];

    /**
     * @var string[]
     */
    private static $data_map = [
        'id' => 'ShopifyID',
        'alt' => 'Title',
        'position' => 'SortOrder',
        'src' => 'OriginalSrc',
        'created_at' => 'Created',
        'updated_at' => 'LastEdited',
        'width' => 'Width',
        'height' => 'Height',
    ];

    /**
     * @var string[]
     */
    private static $has_one = [
        'Product' => ShopifyProduct::class,
        'Collection' => ShopifyCollection::class,
    ];

    /**
     * @var string[]
     */
    private static $has_many = [
        'Variants' => ShopifyVariant::class
    ];

    /**
     * @var bool[]
     */
    private static $indexes = [
        'ShopifyID' => true
    ];

    /**
     * @var string[]
     */
    private static $summary_fields = [
        'CMSThumbnail' => 'Image',
        'Title',
        'ShopifyID'
    ];

    /**
     * @var string[]
     */
    private static $searchable_fields = [
        'Title',
        'ShopifyID',
    ];

    /**
     * @var string
     */
    private static $default_sort = 'SortOrder ASC';

    /**
     * @return FieldList
     */
    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            foreach ($fields as $field) {
                $field->setReadonly(true);
            }

            $fields->add(ReadonlyField::create('Width'));
            $fields->add(ReadonlyField::create('Height'));
        });

        return parent::getCMSFields();
    }

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

    /**
     * @param $shopifyId
     * @return DataObject|null
     */
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
