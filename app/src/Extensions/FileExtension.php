<?php

namespace App\Web\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;

class FileExtension extends DataExtension
{
    public function getData()
    {
        return [
            'id'    =>  $this->owner->ID,
            'title' =>  $this->owner->Title,
            'url'   =>  $this->owner->isPublished() ?
                        $this->owner->getAbsoluteURL() :
                        $this->owner->getURL()
        ];
    }
}
