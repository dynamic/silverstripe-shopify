<?php

namespace Dynamic\Shopify\Test\Page;

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
        $object = $this->objFromFixture(ShopifyProductTest::class, 'one');
        $fields = $object->getCMSFields();
        $this->assertInstanceOf(FieldList::class, $fields);
    }
}
