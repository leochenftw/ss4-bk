<?php

namespace App\Web\Page;

use Leochenftw\Util\CacheHandler;
use Page;
use SilverStripe\Control\Director;
use SilverStripe\Versioned\Versioned;

/**
 * Description.
 */
class HomePage extends Page
{
    /**
     * Defines the database table name.
     *
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

    public function getData()
    {
        $data = Director::isDev() ? null : CacheHandler::read('page.' . $this->ID, 'PageData');

        if (empty($data)) {
            $data = parent::getData();

            CacheHandler::save('page.' . $this->ID, $data, 'PageData');
        }

        return $data;
    }
}
