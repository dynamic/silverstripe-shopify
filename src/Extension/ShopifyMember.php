<?php

namespace Dynamic\Shopify\Extension;

use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Member;

/**
 * Class ShopifyMember
 *
 * @property Member|ShopifyMember $owner
 * @property string $ShopifyID
 */
class ShopifyMember extends DataExtension
{
    /**
     * @var string[]
     */
    private static $db = [
        'ShopifyID' => 'Varchar(255)',
    ];

    /**
     * @param FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldsToTab(
            'Root.Shopify',
            [
                $fields->dataFieldByName('ShopifyID'),
            ]
        );
    }

    /**
     * @param Member $member
     * @return false|void
     */
    public function canEdit($member)
    {
        if ($this->owner->Email === 'shopifytask') {
            return false;
        }
    }

    /**
     * @param Member $member
     * @return false|void
     */
    public function canDelete($member)
    {
        if ($this->owner->Email === 'shopifytask') {
            return false;
        }
    }

    /**
     * @throws ValidationException
     */
    public function requireDefaultRecords()
    {
        if (!Member::get()->filter('Email', 'shopifytask')->first()) {
            $member = Member::create();
            $member->FirstName = 'Shopify';
            $member->Surname = 'Task';
            $member->Email = 'shopifytask';
            $member->write();
        }
    }
}
