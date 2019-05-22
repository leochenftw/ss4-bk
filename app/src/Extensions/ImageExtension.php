<?php

namespace App\Web\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;

class ImageExtension extends DataExtension
{
    public function getData($resample = 'ScaleWidth', $width = 600, $height = null)
    {
        if (!$this->owner->exists()) return null;
        return [
            'id'    =>  $this->owner->ID,
            'title' =>  $this->owner->Title,
            'url'   =>  empty($height) ?
                        $this->owner->$resample($width)->getAbsoluteURL() :
                        (empty($width) ? $this->owner->$resample($height)->getAbsoluteURL() :
                        $this->owner->$resample($width, $height)->getAbsoluteURL())
        ];
    }
}
