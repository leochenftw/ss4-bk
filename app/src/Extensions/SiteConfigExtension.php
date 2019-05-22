<?php

namespace App\Web\Extension;

use SilverStripe\Forms\TextField;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;

/**
 * @file SiteConfigExtension
 *
 * Extension to provide Open Graph tags to site config.
 */
class SiteConfigExtension extends DataExtension
{
    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'ContactRecipients' =>  'Text',
        'ContactBcc'        =>  'Text'
    ];

    /**
     * Update Fields
     * @return FieldList
     */
    public function updateCMSFields(FieldList $fields)
    {
        $owner = $this->owner;
        $fields->addFieldsToTab(
            'Root.Contact',
            [
                TextField::create('ContactRecipients', 'Contact Recipients')->setDescription('Use "," to separate multiple email addresses'),
                TextField::create('ContactBcc', 'Contact Bcc')->setDescription('Use "," to separate multiple email addresses'),
            ]
        );
        return $fields;
    }
}
