<?php

namespace Dynamic\Shopify\Test\Page;

use Dynamic\Shopify\Model\ShopifyVariant;
use Dynamic\Shopify\Page\ShopifyProduct;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;

/**
 * Class ShopifyProductTest
 * @package Dynamic\Shopify\Test\Page
 */
class ShopifyProductTest extends SapphireTest
{
    /**
     * @var string
     */
    protected static $fixture_file = '../Fixture/shopify-product.yml';

    /**
     *
     */
    public function testGetCMSFields()
    {
        $object = $this->objFromFixture(ShopifyProduct::class, 'one');
        $fields = $object->getCMSFields();
        $this->assertInstanceOf(FieldList::class, $fields);
    }

    /**
     *
     */
    public function testGetPrice()
    {
        $object = $this->objFromFixture(ShopifyProduct::class, 'one');
        $variant = $this->objFromFixture(ShopifyVariant::class, 'one');
        $this->assertEquals($object->getPrice(), $variant->dbObject('Price'));
    }

    /**
     *
     */
    public function testGetCompareAtPrice()
    {
        $object = $this->objFromFixture(ShopifyProduct::class, 'one');
        $variant = $this->objFromFixture(ShopifyVariant::class, 'one');
        $this->assertEquals($object->getCompareAtPrice(), $variant->dbObject('CompareAtPrice'));
    }

    /**
     *
     */
    public function testGetByShopifyID()
    {
        $object = $this->objFromFixture(ShopifyProduct::class, 'one');
        $expected = ShopifyProduct::getByShopifyID('12345');
        $this->assertEquals($object, $expected);
    }
}
