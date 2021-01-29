<?php

namespace Dynamic\Shopify\Test\Element;

use Dynamic\Shopify\Element\ElementProducts;
use Dynamic\Shopify\Page\ShopifyProduct;
use SilverStripe\Dev\Debug;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;

/**
 * Class ElementProductsTest
 * @package Dynamic\Shopify\Test\Element
 */
class ElementProductsTest extends SapphireTest
{
    /**
     * @var string
     */
    protected static $fixture_file = '../Fixture/element-products.yml';

    /**
     *
     */
    public function testGetCMSFields()
    {
        $object = singleton(ElementProducts::class);
        $fields = $object->getCMSFields();
        $this->assertInstanceOf(FieldList::class, $fields);
    }

    /**
     *
     */
    public function testGetProductsList()
    {
        $object = singleton(ElementProducts::class);
        $object->Limit = 3;
        $product_one = $this->objFromFixture(ShopifyProduct::class, 'one');
        $product_two = $this->objFromFixture(ShopifyProduct::class, 'two');
        $object->Products()->add($product_one);
        $object->Products()->add($product_two);
        $object->write();

        $products = $object->getProductsList();
        Debug::show($products);
        $this->assertEquals(2, $object->Products()->count());
        $this->assertEquals(3, (int)$products->count());

        $object->Limit = 4;
        $object->write();
        $products = $object->getProductsList();
        $this->assertEquals(4, (int)$products->count());
    }
}
