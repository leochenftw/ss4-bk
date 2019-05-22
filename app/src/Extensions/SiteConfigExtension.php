<?php

namespace App\Web\Extension;

use SilverStripe\Forms\TextField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
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
        $fields->addFieldToTab(
            'Root.Main',
            LiteralField::create('CacheFlusher', '<p><a style="color: red;" target="_blank" href="/?flush=all">Flush cached data</a></p>')
        );
        $fields->addFieldsToTab(
            'Root.Contact',
            [
                TextField::create('ContactRecipients', 'Contact Recipients')->setDescription('Use "," to separate multiple email addresses'),
                TextField::create('ContactBcc', 'Contact Bcc')->setDescription('Use "," to separate multiple email addresses'),
            ]
        );
        return $fields;
    }

    public function getData()
    {
        return [
            'title'     =>  $this->owner->Title,
            'tagline'   =>  $this->owner->Tagline
        ];
    }
}
