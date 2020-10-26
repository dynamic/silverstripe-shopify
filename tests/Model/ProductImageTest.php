<?php

namespace Dynamic\Shopify\Test\Model;

use Dynamic\Shopify\Model\ProductImage;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;

/**
 * Class ProductImageTest
 * @package Dynamic\Shopify\Test\Model
 */
class ProductImageTest extends SapphireTest
{
    protected static $fixture_file = '../Fixture/product-image.yml';

    public function testGetCMSFields()
    {
        $object = $this->objFromFixture(ProductImage::class, 'one');
        $fields = $object->getCMSFields();
        $this->assertInstanceOf(FieldList::class, $fields);
    }
}
