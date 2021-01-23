<?php

namespace Dynamic\Shopify\Test\Model;

use Dynamic\Shopify\Model\ShopifyVariant;
use Dynamic\Shopify\Page\ShopifyProduct;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;

class ShopifyVariantTest extends SapphireTest
{
    /**
     * @var string
     */
    protected static $fixture_file = '../Fixture/shopify-variant.yml';

    /**
     *
     */
    public function testGetCMSFields()
    {
        $object = $this->objFromFixture(ShopifyVariant::class, 'one');
        $fields = $object->getCMSFields();
        $this->assertInstanceOf(FieldList::class, $fields);
    }

    /**
     *
     */
    public function testGetByShopifyID()
    {
        $object = $this->objFromFixture(ShopifyVariant::class, 'one');
        $expected = ShopifyVariant::getByShopifyID('012345');
        $this->assertEquals($expected, $object);
    }
}
