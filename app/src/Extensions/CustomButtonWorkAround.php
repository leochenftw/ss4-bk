<?php

namespace App\Web\Extension;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Control\Controller;
use Leochenftw\Debugger;

use App\Web\Model\ContactSubmission;
use App\Web\Email\ContactSubmissionAcknowledgement;
use App\Web\Email\ContactSubmissionNotice;

class CustomButtonWorkAround extends Extension
{
    public function updateFormActions(FieldList $actions)
    {
        $list   =   [
            ContactSubmission::class
            // add models below
        ];

        $record =   $this->owner->getRecord();

        if (!in_array($record->ClassName, $list) || !$record->exists()) {
            return;
        }

        // Now, custom actions start
        if ($record->ClassName == ContactSubmission::class) {
            $actions->push(FormAction::create('send_email', 'Resend email')
                ->setUseButtonTag(true)
                ->setAttribute('data-icon', 'accept'));
        }
        // OK, custom actions finish

        $right_group = $actions->fieldByName('RightGroup');
        $actions->remove($right_group);
        $actions->push($right_group);
    }

    // Make custom actions work
    public function send_email($data, $form)
    {
        $notice =   ContactSubmissionNotice::create($this->owner->getRecord());
        $ack    =   ContactSubmissionAcknowledgement::create($this->owner->getRecord());

        $notice->send();
        $ack->send();

        $form->sessionMessage('Sent', 'good', ValidationResult::CAST_HTML);
        return Controller::curr()->redirectBack();
    }
}
