<?php

namespace Dynamic\Shopify\Test\Page;

use Dynamic\Shopify\Page\ShopifyCollection;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;

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
    }
}
