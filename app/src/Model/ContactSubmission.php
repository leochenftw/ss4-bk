<?php

namespace App\Web\Model;

use SilverStripe\ORM\DataObject;
use UncleCheese\BetterButtons\Actions\CustomAction;
use Leochenftw\Debugger;
use App\Web\Email\ContactSubmissionAcknowledgement;
use App\Web\Email\ContactSubmissionNotice;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class ContactSubmission extends DataObject
{
    private static $better_buttons_actions = [
        'send_email'
    ];

    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'ContactSubmission';

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'Title'     =>  'Varchar(128)',
        'Content'   =>  'Text',
        'Name'      =>  'Varchar(128)',
        'Email'     =>  'Varchar(256)'
    ];

    public function send_email()
    {
        $notice =   ContactSubmissionNotice::create($this);
        $ack    =   ContactSubmissionAcknowledgement::create($this);

        $notice->send();
        $ack->send();
    }

    public function getBetterButtonsActions()
    {
        $fields = parent::getBetterButtonsActions();
        $fields->removeByName([
            'action_save'
        ]);

        if ($this->exists()) {
            $fields->push(CustomAction::create('send_email', 'Send Email')->setRedirectType(CustomAction::REFRESH));
        }

        return $fields;
    }

    public function isPublished()
    {
        return true;
    }

    public function stagesDiffer()
    {
        return false;
    }
}
