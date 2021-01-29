<?php

namespace Dynamic\Shopify\Test\Element;

use Dynamic\Shopify\Element\ElementProducts;
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
        $object = $this->objFromFixture(ElementProducts::class, 'one');
        $fields = $object->getCMSFields();
        $this->assertInstanceOf(FieldList::class, $fields);
    }

    /**
     *
     */
    public function testGetProductsList()
    {
        $object = $this->objFromFixture(ElementProducts::class, 'one');
        $products = $object->getProductsList();
        $this->assertEquals(2, $object->Products()->count());
        $this->assertEquals(3, (int)$products->count());

        $object->Limit = 4;
        $object->write();
        $products = $object->getProductsList();
        $this->assertEquals(4, (int)$products->count());
    }
}
