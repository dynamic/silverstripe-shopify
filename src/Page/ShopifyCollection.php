<?php

namespace Dynamic\Shopify\Page;

use Dynamic\Shopify\Model\ShopifyFile;
use Dynamic\Shopify\Task\ShopifyImportTask;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\CMS\Model\VirtualPage;
use SilverStripe\Control\Controller;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Dev\Debug;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Security;

/**
 * Class ShopifyCollection
 *
 * @property string $ShopifyID
 * @property int $ProductsCt
 * @property string $SortOrder
 * @property bool $CollectionActive
 * @property int $ProductsPerPage
 * @property int $ElementalAreaID
 * @property int $HeaderImageID
 * @property int $FileID
 * @method ElementalArea ElementalArea()
 * @method HeaderImage HeaderImage()
 * @method ShopifyFile File()
 * @mixin HeaderImageExtension
 * @mixin ElementalPageExtension
 */
class ShopifyCollection extends \Page
{
    /**
     * @var string
     */
    private static $table_name = 'ShopifyCollection';

    /**
     * @var string[]
     */
    private static $db = [
        'ShopifyID' => 'Varchar',
        'ProductsCt' => 'Int',
        'SortOrder' => 'Varchar(20)',
        'CollectionActive' => 'Boolean',
        'ProductsPerPage' => 'Int',
    ];

    /**
     * @var string[]
     */
    private static $data_map = [
        'id' => 'ShopifyID',
        'title' => 'Title',
        'handle' => 'URLSegment',
        'descriptionHtml' => 'Content',
        'productsCount' => 'ProductsCt',
        'createdAt' => 'Created',
        'updatedAt' => 'LastEdited',
        'sortOrder' => 'SortOrder',
        'publishedOnCurrentPublication' => 'CollectionActive',
    ];

    /**
     * @var string[]
     */
    private static $has_one = [
        'File' => ShopifyFile::class
    ];

    /**
     * @var bool[]
     */
    private static $indexes = [
        'ShopifyID' => true,
    ];

    /**
     * @var array
     */
    private static $defaults = [
        'ProductsPerPage' => 12,
    ];

    /**
     * @var string[]
     */
    private static $summary_fields = [
        'File.CMSThumbnail' => 'Image',
        'Title',
        'ShopifyID',
        'ProductsCt' => 'Products',
    ];

    /**
     * @var string[]
     */
    private static $allowed_children = [
        ShopifyCollection::class,
        ShopifyProduct::class,
        VirtualPage::class,
        RedirectorPage::class,
    ];

    /**
     * @return FieldList
     */
    public function getCMSFields()
    {
        $self =& $this;
        $this->beforeUpdateCMSFields(function (FieldList $fields) use ($self) {
            $fields->dataFieldByName('Title')
                ->setReadonly(true);

            $fields->dataFieldByName('URLSegment')
                ->setReadonly(true);

            $fields->replaceField(
                'Content',
                ReadonlyField::create('Content', 'Description')
            );

            $fields->addFieldsToTab('Root.Details', [
                ReadonlyField::create('ShopifyID'),
                ReadonlyField::create('Image')
                    ->setTitle('Image')
                    ->setValue($this->File()->getThumbnail(100, 100)),
                ReadonlyField::create('CollectionActive'),
                ReadonlyField::create('PublishedAt'),
            ]);

            $fields->addFieldsToTab(
                'Root.Display',
                [
                    NumericField::create('ProductsPerPage')
                        ->setTitle(_t(__CLASS__ . '.ProductsPerPage', 'Products Per Page')),
                ],
                'Content'
            );
        });

        return parent::getCMSFields();
    }

    /**
     * @inheritDoc
     */
    public function getCMSActions()
    {
        $actions = parent::getCMSActions();

        if (!$this->ShopifyID || !$this->canEdit()) {
            return $actions;
        }

        $controller = Controller::curr();
        if ($controller instanceof LeftAndMain) {
            /** @var FormAction $action */
            $action = FormAction::create('shopifyCollectionFetch', 'Re-fetch Shopify')
                ->addExtraClass('btn-primary font-icon-sync')
                ->setUseButtonTag(true);

            $actions->push($action);
        }

        return $actions;
    }

    /**
     * @return mixed
     */
    public function getProductList()
    {
        $id = $this->CopyContentFromID ?: $this->ID;
        $categories = ShopifyCollection::get()->filter('ParentID', $id)->column('ID');
        $categories[] = $id;

        $classes = array_merge(
            [
                VirtualPage::class,
                RedirectorPage::class,
            ],
            ClassInfo::subclassesFor(ShopifyProduct::class)
        );

        $products = SiteTree::get()
            ->filter('ClassName', $classes)
            ->filterAny([
                'ParentID' => $categories,
            ]);

        $this->extend('updateProductList', $products, $categories);

        $products = $products->filterByCallback(function ($page) {
            return $page->canView(Security::getCurrentUser());
        });

        return $products;
    }

    /**
     * @param $shopifyCollection
     * @return ProductCollection|ShopifyCollection
     * @throws \SilverStripe\ORM\ValidationException
     */
    public static function findOrMakeFromShopifyData($shopifyCollection)
    {
        if (!$collection = self::getByShopifyID($shopifyCollection->id)) {
            $collection = self::create();
        }

        $map = self::config()->get('data_map');
        ShopifyImportTask::loop_map($map, $collection, $shopifyCollection);

        if ($collection->isChanged()) {
            $collection->write();
            if ($collection->isPublished()) {
                $collection->publishRecursive();
            }
        }

        return $collection;
    }

    /**
     * @param $shopifyId
     * @return ProductCollection
     */
    public static function getByShopifyID($shopifyId)
    {
        return DataObject::get_one(self::class, ['ShopifyID' => $shopifyId]);
    }

    /**
     * @param $urlSegment
     * @return DataObject|null
     */
    public static function getByURLSegment($urlSegment)
    {
        return DataObject::get_one(self::class, ['URLSegment' => $urlSegment]);
    }
}
