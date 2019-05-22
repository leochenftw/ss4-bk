<?php

namespace {

    use SilverStripe\Forms\TextareaField;
    use SilverStripe\Forms\TextField;
    use SilverStripe\SiteConfig\SiteConfig;
    use SilverStripe\Forms\TabSet;
    use SilverStripe\Forms\Tab;
    use SilverStripe\Forms\FieldList;
    use SilverStripe\CMS\Model\SiteTree;
    use Leochenftw\Util;
    use Leochenftw\Debugger;
    use SilverStripe\Control\Controller;
    use App\Web\Model\PageHero;
    use SilverShop\HasOneField\HasOneButtonField;

    class Page extends SiteTree
    {
        private static $db = [
            'Excerpt'   =>  'Text'
        ];

        private static $has_one = [
            'PageHero'  =>  PageHero::class
        ];

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
                'Metadata',
                'PageHeroID'
            ]);

            $fields->addFieldsToTab(
                'Root.Main',
                [
                    TextareaField::create('Excerpt'),
                    HasOneButtonField::create($this, "PageHero")
                ],
                'URLSegment'
            );

            $fields->addFieldToTab(
                'Root.SEO',
                $meta,
                'OG'
            );


            return $fields;
        }

        public function getData($mini = false)
        {
            if ($mini) {
                return  [
                    'id'    =>  $this->ID,
                    'title' =>  $this->Title,
                    'url'   =>  $this->Link() == '/' ? '/' : rtrim($this->Link(), '/')
                ];
            }

            $siteconfig =   SiteConfig::current_site_config();
            return [
                'id'            =>  $this->ID,
                'siteconfig'    =>  $siteconfig->getData(),
                'navigation'    =>  $this->get_menu_items(),
                'title'         =>  $this->Title,
                'menu_title'    =>  $this->MenuTitle,
                'excerpt'       =>  $this->Excerpt,
                'hero'          =>  $this->PageHero()->exists() ?
                                    $this->PageHero()->getData() : null,
                'pagetype'      =>  strtolower($this->get_type($this->ClassName)),
                'ancestors'     =>  $this->get_ancestors($this)
            ];
        }

        private function get_ancestors($item, $ancestors = [])
        {
            if (!$item->Parent()->exists()) {
                return array_reverse($ancestors);
            }

            $ancestors[]    =   [
                'title'         =>  $item->Parent()->MenuTitle,
                'menu_title'    =>  $this->Parent()->MenuTitle,
                'url'           =>  $item->Parent()->Link() != '/' ? rtrim($item->Parent()->Link(), '/') : '/'
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
                    'title'     =>  $item->MenuTitle,
                    'url'       =>  $link != '/' ? rtrim($link, '/') : '/',
                    'active'    =>  $item->isSection() || $item->isCurrent(),
                    'sub'       =>  $this->get_menu_items($item->Children()),
                    'pagetype'  =>  strtolower($this->get_type($item->ClassName))
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
