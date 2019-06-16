<?php

namespace {
    use SilverStripe\Core\Convert;
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
    use SilverStripe\Control\Director;
    use SilverShop\HasOneField\HasOneButtonField;
    use SilverStripe\Core\Flushable;
    use Psr\SimpleCache\CacheInterface;
    use Leochenftw\Util\CacheHandler;
    use SilverStripe\Security\Member;
    use App\Web\Extension\VueRenderExtension;
    use App\Web\Extension\VueRenderer;
    use SilverStripe\Forms\FormAction;
    use SilverStripe\View\Requirements;
    use SilverStripe\ErrorPage\ErrorPage;
    use SilverStripe\CMS\Model\RedirectorPage;
    use SilverStripe\CMS\Model\VirtualPage;

    class Page extends SiteTree implements Flushable
    {
        public static function flush()
        {
            CacheHandler::delete(null, 'PageData');
        }

        /**
         * Defines extension names and parameters to be applied
         * to this object upon construction.
         * @var array
         */
        private static $extensions = [
            VueRenderer::class,
            VueRenderExtension::class
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
                'Metadata'
            ]);

            $fields->addFieldToTab(
                'Root.SEO',
                $meta,
                'OG'
            );


            return $fields;
        }

        public function getCMSActions()
        {
            if ($this->ClassName == ErrorPage::class || $this->ClassName == RedirectorPage::class || $this->ClassName == VirtualPage::class) {
                return parent::getCMSActions();
            }

            Requirements::javascript('leochenftw/leoss4bk: client/js/cms.js');
            $fields = parent::getCMSActions();
            $fields->fieldByName('MajorActions')->push(
                FormAction::create('do_render', 'Pre-Render')->addExtraClass('btn-vue-prerenderer')
            );

            return $fields;
        }

        public function getData($mini = false)
        {
            if ($mini) {
                if ($mini_data = CacheHandler::read('page.' . $this->ID . '.mini', 'PageData')) {
                    return $mini_data;
                }

                $mini_data  =   [
                    'id'    =>  $this->ID,
                    'title' =>  $this->Title,
                    'url'   =>  $this->Link() == '/' ? '/' : rtrim($this->Link(), '/')
                ];

                CacheHandler::save('page.' . $this->ID . '.mini', $mini_data, 'PageData');

                return $mini_data;
            }

            if ($data = CacheHandler::read('page.' . $this->ID, 'PageData')) {
                return $data;
            }

            $siteconfig =   SiteConfig::current_site_config();
            $data   =   [
                'id'            =>  $this->ID,
                'siteconfig'    =>  $siteconfig->getData(),
                'navigation'    =>  $this->get_menu_items(),
                'title'         =>  $this->Title,
                'content'       =>  Util::preprocess_content($this->Content),
                'menu_title'    =>  $this->MenuTitle,
                'pagetype'      =>  $this->get_type($this->ClassName),
                'ancestors'     =>  $this->get_ancestors($this),
                'meta'          =>  [
                    'canonical'     =>  str_replace(
                                            Director::absoluteBaseURL(),
                                            $siteconfig->SocialBaseURL,
                                            $this->ConanicalURL ? Convert::raw2att($this->ConanicalURL) : $this->AbsoluteLink()
                                        ),
                    'keywords'      =>  !empty($this->MetaKeywords) ? Convert::raw2att($this->MetaKeywords) : Convert::raw2att($siteconfig->MetaKeywords),
                    'description'   =>  $this->get_meta_description(),
                    'robots'        =>  Director::isLive() ?
                                        (!empty($this->MetaRobots) ? Convert::raw2att($this->MetaRobots) : null) :
                                        'noindex, nofollow, noarchive',
                    'social'        =>  $this->get_og_twitter_meta()
                ]
            ];

            CacheHandler::save('page.' . $this->ID, $data, 'PageData');

            return $data;
        }

        public function get_meta_description()
        {
            if (!empty($this->MetaDescription)) {
                return Convert::raw2att($this->MetaDescription);
            } elseif (!empty(SiteConfig::current_site_config()->MetaDescription)) {
                return Convert::raw2att(SiteConfig::current_site_config()->MetaDescription);
            }

            return Convert::raw2att(Util::getWords($this->Content, 50));
        }

        private function get_ancestors($item, $ancestors = [])
        {
            if (!$item->Parent()->exists()) {
                return array_reverse($ancestors);
            }

            $ancestors[]    =   [
                'title'         =>  $item->Parent()->Title,
                'menu_title'    =>  $this->Parent()->MenuTitle,
                'url'           =>  $item->Parent()->Link() != '/' ? rtrim($item->Parent()->Link(), '/') : '/'
            ];

            return $this->get_ancestors($item->Parent(), $ancestors);
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
                    'pagetype'  =>  $this->get_type($item->ClassName)
                ];
            }

            return $list;
        }

        private function get_type($class)
        {
            $seg    =   explode('\\', $class);
            return strtolower($seg[count($seg) - 1]);
        }
    }
}
