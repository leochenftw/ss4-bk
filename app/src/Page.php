<?php

namespace {

    use SilverStripe\SiteConfig\SiteConfig;
    use SilverStripe\Forms\TabSet;
    use SilverStripe\Forms\Tab;
    use SilverStripe\Forms\FieldList;
    use SilverStripe\CMS\Model\SiteTree;
    use Leochenftw\Util;
    use Leochenftw\Debugger;
    use SilverStripe\Control\Controller;

    class Page extends SiteTree
    {
        private static $db = [];

        private static $has_one = [];

        /**
         * CMS Fields
         * @return FieldList
         */
        public function getCMSFields()
        {
            $fields =   parent::getCMSFields();
            $this->extend('updateCMSFields', $fields);

            $meta   =   $fields->fieldbyName('Root.Main.Metadata');

            $fields->removeByName([
                'Metadata'
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
            $siteconfig =   SiteConfig::current_site_config();
            return [
                'id'            =>  $this->ID,
                'siteconfig'    =>  [
                    'sitename'  =>  $siteconfig->Title,
                ],
                'navigation'    =>  $this->get_menu_items(),
                'title'         =>  $this->Title,
                'content'       =>  $this->get_content_data(),
                'pagetype'      =>  $this->get_type($this->ClassName),
                'ancestors'     =>  $this->get_ancestors($this)
            ];
        }

        private function get_ancestors($item, $ancestors = [])
        {
            if (!$item->Parent()->exists()) {
                return array_reverse($ancestors);
            }

            $ancestors[]    =   [
                'title' =>  $item->Parent()->Title,
                'link'   =>  $item->Parent()->Link() != '/' ? rtrim($item->Parent()->Link(), '/') : '/'
            ];

            return $this->get_ancestors($item->Parent(), $ancestors);
        }

        private function get_content_data()
        {
            $data   =   [];

            if (!empty($this->ContentLeft)) {
                $data[] =   Util::preprocess_content($this->ContentLeft);
            }

            if (!empty($this->ContentRight)) {
                $data[] =   Util::preprocess_content($this->ContentRight);
            }

            return $data;
        }

        private function get_menu_items($nav = null)
        {
            $nav    =   empty($nav) ? Controller::curr()->getMenu(1) : $nav;
            $list   =   [];
            foreach ($nav as $item) {
                $link   =   $item->Link();

                $list[] =   [
                    'label'     =>  $item->Title,
                    'url'       =>  $link != '/' ? rtrim($link, '/') : '/',
                    'active'    =>  $item->isSection() || $item->isCurrent(),
                    'sub'       =>  $this->get_type($item->ClassName) == 'ScriptCategoryPage' || $this->get_type($item->ClassName) == 'AuthorLanding' ?
                                    [] :
                                    $this->get_menu_items($item->Children()),
                    'pagetype'  =>  $this->get_type($item->ClassName)
                ];
            }

            return $list;
        }

        private function get_type($class)
        {
            $seg    =   explode('\\', $class);
            return $seg[count($seg) - 1];
        }
    }
}
