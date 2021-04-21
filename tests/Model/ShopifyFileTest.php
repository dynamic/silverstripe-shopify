<?php

namespace Dynamic\Shopify\Test\Model;

use Dynamic\Shopify\Model\ShopifyFile;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;

/**
 * Class ShopifyFileTest
 * @package Dynamic\Shopify\Test\Model
 */
class ShopifyFileTest extends SapphireTest
{
    /**
     * @var string
     */
    protected static $fixture_file = '../Fixture/shopify-file.yml';

    /**
     *
     */
    public function testGetCMSFields()
    {
        $object = $this->objFromFixture(ShopifyFile::class, 'one');
        $fields = $object->getCMSFields();
        $this->assertInstanceOf(FieldList::class, $fields);
        $this->assertNotNull($fields->dataFieldByName('ShopifyID'));
        $this->assertNotNull($fields->dataFieldByName('OriginalSourceID'));
        $this->assertNotNull($fields->dataFieldByName('Width'));
        $this->assertNotNull($fields->dataFieldByName('Height'));
        $this->assertNotNull($fields->dataFieldByName('SortOrder'));
    }
}
