<?php

namespace App\Web\Extension;
use SilverStripe\Admin\LeftAndMainExtension;
use Page;

class VueRenderExtension extends LeftAndMainExtension
{
    /**
     * Defines methods that can be called directly
     * @var array
     */
    private static $allowed_actions = [
        'do_render' => true
    ];

    public function do_render()
    {
        $record =   $this->owner->getRecord($this->owner->request->Param('ID'));

        if (!is_subclass_of($record, 'Page') && $record->ClassName != Page::class) {
            return $this->owner->redirectBack();
        }

        $record->render_vue_html();
        return $this->owner->redirectBack();
    }
}
