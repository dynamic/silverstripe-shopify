<?php

namespace Dynamic\Shopify\Test\Page;

use Dynamic\Shopify\Model\ShopifyFile;
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
        $this->assertNotNull($fields->dataFieldByName('ShopifyID'));
        $this->assertNotNull($fields->dataFieldByName('Vendor'));
        $this->assertNotNull($fields->dataFieldByName('ProductType'));
        $this->assertNotNull($fields->dataFieldByName('Tags'));
        $this->assertNotNull($fields->dataFieldByName('Status'));
        $this->assertNotNull($fields->dataFieldByName('Variants'));
        $this->assertNotNull($fields->dataFieldByName('Files'));
        $this->assertNotNull($fields->dataFieldByName('Collections'));
    }

    /**
     *
     */
    public function testGetImage()
    {
        $object = $this->objFromFixture(ShopifyProduct::class, 'one');
        $image = $object->getImage();
        $this->assertEquals($image, $object->Files()->first());
    }

    /**
     *
     */
    public function testGetButtonOptions()
    {
        $object = $this->objFromFixture(ShopifyProduct::class, 'one');
        $script = $object->getButtonOptions();
        $this->assertInternalType('string', $script);
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
    public function testGetSKU()
    {
        $object = $this->objFromFixture(ShopifyProduct::class, 'one');
        $sku = $object->getSKU();
        $this->assertEquals($sku, '12345');
    }

    /**
     *
     */
    public function testGetByShopifyID()
    {
        $object = $this->objFromFixture(ShopifyProduct::class, 'one');
        $expected = ShopifyProduct::getByShopifyID('12345');
        $this->assertEquals($expected, $object);
    }

    /**
     *
     */
    public function testGetByURLSegment()
    {
        $object = $this->objFromFixture(ShopifyProduct::class, 'one');
        $expected = ShopifyProduct::getByURLSegment('product-one');
        $this->assertEquals($expected, $object);
    }
}
