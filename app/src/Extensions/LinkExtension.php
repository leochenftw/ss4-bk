<?php

namespace App\Web\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;

class LinkExtension extends DataExtension
{
    public function getData()
    {
        return [
            'id'            =>  $this->owner->ID,
            'title'         =>  $this->owner->Title,
            'url'           =>  $this->owner->getLinkURL(),
            'open_in_blank' =>  $this->owner->OpenInNewWindow ? true : false
        ];
    }
}
