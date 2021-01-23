<?php

namespace Dynamic\Shopify\Test\Extension;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Security\Member;

/**
 * Class ShopifyMemberTest
 * @package Dynamic\Shopify\Test\Extension
 */
class ShopifyMemberTest extends SapphireTest
{
    /**
     *
     */
    public function testGetCMSFields()
    {
        $object = singleton(Member::class);
        $fields = $object->getCMSFields();
        $this->assertInstanceOf(FieldList::class, $fields);
        $this->assertNotNull($fields->dataFieldByName('ShopifyID'));
    }
}
