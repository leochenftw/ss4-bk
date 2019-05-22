<?php

namespace App\Web\Extensions;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\FieldList;
use UncleCheese\BetterButtons\Actions\CustomAction;
use App\Web\Email\PasswordRecoveryEmail;

class MemberExtension extends DataExtension
{
    private static $better_buttons_actions = [
        'password_recovery'
    ];
    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'DateLoggedIn'      =>  'Datetime',
        'ValidationKey'     =>  'Varchar(40)'
    ];

    /**
     * Update Fields
     * @return FieldList
     */
    public function updateCMSFields(FieldList $fields)
    {
        $owner = $this->owner;
        $fields->addFieldsToTab(
            'Root.Misc',
            [
                $fields->fieldByName('Root.Main.ValidationKey'),
                $fields->fieldByName('Root.Main.DateLoggedIn')->performReadonlyTransformation()
            ]
        );
        return $fields;
    }

    public function populateDefaults()
    {
        $this->owner->ValidationKey =   sha1(time() . rand());
    }

    public function getBetterButtonsActions()
    {
        $buttons = $this->getDefaultButtonList('actions');
        $fields = $this->createFieldList($buttons);

        $fields->removeByName([
            'action_save'
        ]);

        if ($this->owner->exists()) {
            $fields->push(CustomAction::create('password_recovery', 'Send password reset email')->setRedirectType(CustomAction::REFRESH));
        }

        return $fields;
    }

    public function password_recovery()
    {
        if ($this->owner->isActivated()) {
            $this->owner->populateDefaults();
            $this->owner->write();
        }

        $email  =   PasswordRecoveryEmail::create($this->owner);
        $email->send();
    }

    protected function getDefaultButtonList($config)
    {
        $new = ($this->owner->ID == 0);
        $buttonConfig = Config::inst()->get('UncleCheese\BetterButtons\Extensions\BetterButtons');//$this->config()->get($config);
        if (!$buttonConfig) {
            return [];
        }
        $key = $new
            ? ($this->checkVersioned() ? 'versioned_create' : 'create')
            : ($this->checkVersioned() ? 'versioned_edit' : 'edit');
        return isset($buttonConfig[$key]) ? (array) $buttonConfig[$key] : [];
    }

    protected function createFieldList($buttons)
    {
        $actions = FieldList::create();
        foreach ($buttons as $buttonType => $config) {
            $button = $this->createButtonFromConfig($buttonType, $config);
            if (!$button) {
                continue;
            }
            if ($button instanceof DropdownFormAction) {
                $this->populateButtonGroup($button, $config);
            }
            $actions->push($button);
        }
        return $actions;
    }

    public function checkVersioned()
    {
        $isVersioned = false;
        foreach ($this->owner->getExtensionInstances() as $extension) {
            if ($extension instanceof Versioned) {
                $isVersioned = true;
                break;
            }
        }
        return $isVersioned
            && $this->owner->config()->better_buttons_versioned_enabled
            && count($this->owner->getVersionedStages()) > 1;
    }

    public function getData()
    {
        return [
            'id'        =>  $this->owner->ID,
            'email'     =>  $this->owner->Email,
            'firstname' =>  $this->owner->FirstName,
            'surname'   =>  $this->owner->Surname,
            'is_admin'  =>  $this->owner->inGroup('administrators') ? true : false
        ];
    }

    public function isActivated()
    {
        return empty($this->owner->ValidationKey);
    }
}
