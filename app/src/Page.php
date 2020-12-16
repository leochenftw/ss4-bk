<?php

use App\Web\Page\HomePage;
use leochenftw\Util;
use Leochenftw\Util\CacheHandler;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Flushable;
use SilverStripe\Forms\FieldList;
use SilverStripe\SiteConfig\SiteConfig;

class Page extends SiteTree implements Flushable
{
    private static $has_one = [];

    public static function flush()
    {
        CacheHandler::delete(null, 'PageData');
    }

    /**
     * CMS Fields.
     *
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $this->extend('updateCMSFields', $fields);

        $meta = $fields->fieldbyName('Root.Main.Metadata');

        $fields->removeByName([
            'Metadata',
        ]);

        $fields->addFieldToTab(
            'Root.SEO',
            $meta,
            'OG'
        );

        return $fields;
    }

    public function getData()
    {
        $siteconfig = SiteConfig::current_site_config();
        $data = [
            'id' => $this->ID,
            'navigation' => $this->get_menu_items(),
            'title' => ($this instanceof HomePage) ? SiteConfig::current_site_config()->Title : (!empty($this->MetaTitle) ? $this->MetaTitle : $this->Title),
            'content' => Util::preprocess_content($this->Content),
            'pagetype' => ClassInfo::shortName($this->ClassName),
            'ancestors' => $this->get_ancestors($this),
        ];

        if (!empty($siteconfig->Data)) {
            $data = array_merge($data, ['siteconfig' => $siteconfig->Data]);
        }

        $this->extend('getData', $data);

        return $data;
    }

    /**
     * Event handler called after writing to the database.
     */
    protected function onAfterWrite()
    {
        parent::onAfterWrite();
        CacheHandler::delete(null, 'PageData');
    }

    private function get_ancestors($item, $ancestors = [])
    {
        if (!$item->Parent()->exists()) {
            return array_reverse($ancestors);
        }

        $ancestors[] = [
            'title' => $item->Parent()->Title,
            'link' => $item->Parent()->Link(),
        ];

        return $this->get_ancestors($item->Parent(), $ancestors);
    }

    private function get_menu_items($nav = null)
    {
        $controller = Controller::curr();
        $controller = !$controller->hasMethod('getMenu') ? PageController::create() : $controller;
        $nav = empty($nav) ? $controller->getMenu(1) : $nav;
        $list = [];
        foreach ($nav as $item) {
            $link = $item->Link();

            $list[] = [
                'label' => $item->Title,
                'url' => $link,
                'active' => $item->isSection() || $item->isCurrent(),
                'sub' => $this->get_menu_items($item->Children()),
                'pagetype' => ClassInfo::shortName($item->ClassName),
            ];
        }

        return $list;
    }
}
