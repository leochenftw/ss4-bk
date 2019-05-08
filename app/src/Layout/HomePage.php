<?php

namespace App\Web\Layout;
use Page;
use SilverStripe\Versioned\Versioned;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class HomePage extends Page
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'HomePage';
    private static $description = 'This is the Homepage. You can only have one Homepage at any one time';

    public function canCreate($member = null, $context = [])
    {
        if (Versioned::get_by_stage(__CLASS__, 'Stage')->count() > 0) {
            return false;
        }

        return parent::canCreate($member, $context);
    }
}
