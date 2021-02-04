<?php

namespace Dynamic\Shopify\Test\Page;

use Dynamic\Shopify\Page\ShopifyCollection;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\ArrayList;

/**
 * Class ShopifyCollectionTest
 * @package Dynamic\Shopify\Test\Page
 */
class ShopifyCollectionTest extends SapphireTest
{
    /**
     * @var string
     */
    protected static $fixture_file = '../Fixture/shopify-collection.yml';

    /**
     *
     */
    public function testGetCMSFields()
    {
        $object = $this->objFromFixture(ShopifyCollection::class, 'one');
        $fields = $object->getCMSFields();
        $this->assertInstanceOf(FieldList::class, $fields);
        $this->assertNotNull($fields->dataFieldByName('ShopifyID'));
        $this->assertNotNull($fields->dataFieldByName('File'));
        $this->assertNotNull($fields->dataFieldByName('Products'));
    }

    /**
     *
     */
    public function testGetProductList()
    {
        $object = $this->objFromFixture(ShopifyCollection::class, 'one');
        $products = $object->getProductList();
        $this->assertInstanceOf(ArrayList::class, $products);
    }

    /**
     *
     */
    public function testGetByShopifyID()
    {
        $object = $this->objFromFixture(ShopifyCollection::class, 'one');
        $expected = ShopifyCollection::getByShopifyID('0012345');
        $this->assertEquals($expected, $object);
    }

    /**
     *
     */
    public function testGetByURLSegment()
    {
        $object = $this->objFromFixture(ShopifyCollection::class, 'one');
        $expected = ShopifyCollection::getByURLSegment('collection-one');
        $this->assertEquals($expected, $object);
    }
}
