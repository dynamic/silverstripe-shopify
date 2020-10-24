<?php

namespace Dynamic\Shopify\Test\Page;

use Dynamic\Shopify\Page\Product;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;

/**
 * Class ProductTest
 * @package Dynamic\Shopify\Test\Page
 */
class ProductTest extends SapphireTest
{
    /**
     * @var string
     */
    protected static $fixture_file = '../Fixture/product.yml';

    /**
     *
     */
    public function testGetCMSFields()
    {
        $object = $this->objFromFixture(Product::class, 'one');
        $fields = $object->getCMSFields();
        $this->assertInstanceOf(FieldList::class, $fields);
    }
}
