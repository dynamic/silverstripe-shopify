<?php

namespace Dynamic\Shopify\Test\Extension;

use Dynamic\Shopify\Page\ShopifyProduct;
use SilverStripe\Dev\SapphireTest;

/**
 * Class ShopifyExtensionTest
 * @package Dynamic\Shopify\Test\Extension
 */
class ShopifyExtensionTest extends SapphireTest
{
    /**
     *
     */
    public function testGetCartOptions()
    {
        $object = singleton(\PageController::class);
        $script = $object->getCartOptions();
        $this->assertInternalType('string', $script);
    }
}
