<?php

namespace Dynamic\Shopify\Test\Element;

use DNADesign\Elemental\Models\BaseElement;
use Dynamic\Shopify\Element\ElementProducts;
use Dynamic\Shopify\Page\ShopifyProduct;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;

if (!class_exists(BaseElement::class)) {
    return;
}

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
        $this->assertNotNull($fields->dataFieldByName('Products'));
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

    /**
     *
     */
    public function testGetSummary()
    {
        $object = $this->objFromFixture(ElementProducts::class, 'one');
        $summary = $object->getSummary();
        $this->assertEquals('3 Products', $summary);
    }
}
