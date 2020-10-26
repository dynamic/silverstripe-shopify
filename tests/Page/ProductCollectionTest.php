<?php

namespace Dynamic\Shopify\Test\Page;

use Dynamic\Shopify\Page\ProductCollection;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;

/**
 * Class ProductCollectionTest
 * @package Dynamic\Shopify\Test\Page
 */
class ProductCollectionTest extends SapphireTest
{
    /**
     * @var string
     */
    protected static $fixture_file = '../Fixture/product-collection.yml';

    /**
     *
     */
    public function testGetCMSFields()
    {
        $object = $this->objFromFixture(ProductCollection::class, 'one');
        $fields = $object->getCMSFields();
        $this->assertInstanceOf(FieldList::class, $fields);
    }
}
