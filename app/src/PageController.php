<?php

namespace {

    use SilverStripe\CMS\Controllers\ContentController;
    use SilverStripe\Core\Config\Config;
    use SilverStripe\Core\Convert;
    use SilverStripe\Control\HTTPRequest;
    use SilverStripe\SiteConfig\SiteConfig;
    use SilverStripe\View\ArrayData;
    use SilverStripe\View\Requirements;
    use SilverStripe\Control\Director;
    use Leochenftw\Debugger;

    class PageController extends ContentController
    {
        /**
         * An array of actions that can be accessed via a request. Each array element should be an action name, and the
         * permissions or conditions required to allow the user to access it.
         *
         * <code>
         * [
         *     'action', // anyone can access this action
         *     'action' => true, // same as above
         *     'action' => 'ADMIN', // you must have ADMIN permissions to access this action
         *     'action' => '->checkAction' // you can only access this action if $this->checkAction() returns true
         * ];
         * </code>
         *
         * @var array
         */
        private static $allowed_actions = [];

        public function index(HTTPRequest $request)
        {
            // check for CORS options request
            if ($this->request->httpMethod() === 'OPTIONS' ) {
                // create direct response without requesting any controller
                $response   =   $this->getResponse();
                // set CORS header from config
                $response   =   $this->addCORSHeaders($response);
                $response->output();
                exit;
            }

            $header     =   $this->getResponse();

            if ($this->request->isAjax()) {
                $this->addCORSHeaders($header);
                return json_encode($this->getData());
            }

            return $this->renderWith([$this->ClassName, 'Page']);
        }

        protected function init()
        {
            parent::init();
            // You can include any CSS or JS required by your project here.
            // See: https://docs.silverstripe.org/en/developer_guides/templates/requirements/
            Requirements::themedCSS('styles');
            if (SilverStripe\Control\Director::isDev()) {
                SilverStripe\View\SSViewer::config()->set('source_file_comments', true);
            }
        }

        public function MetaTags($includeTitle = true)
        {
            $tags = parent::MetaTags($includeTitle);

            if ($this->ConanicalURL) {
                $tags .= "<link rel=\"canonical\" href=\"" . Convert::raw2att($this->ConanicalURL) . "\" />\n";
            } elseif (SiteConfig::current_site_config()->ConanicalURL) {
                $tags .= "<link rel=\"canonical\" href=\"";
                $tags .= Convert::raw2att(SiteConfig::current_site_config()->ConanicalURL) . "\" />\n";
            }

            if ($this->MetaKeywords) {
                $tags .= "<meta name=\"keywords\" content=\"" . Convert::raw2att($this->MetaKeywords) . "\" />\n";
            }
            if ($this->ExtraMeta) {
                $tags .= $this->ExtraMeta . "\n";
            }

            if ($this->URLSegment == 'home' && SiteConfig::current_site_config()->GoogleSiteVerificationCode) {
                $tags .= '<meta name="google-site-verification" content="'
                        . SiteConfig::current_site_config()->GoogleSiteVerificationCode . '" />' . "\n";
            }

            // prevent bots from spidering the site whilest in dev.
            if (!Director::isLive()) {
                $tags .= "<meta name=\"robots\" content=\"noindex, nofollow, noarchive\" />\n";
            } elseif (!empty($this->MetaRobots)) {
                $tags .= "<meta name=\"robots\" content=\"$this->MetaRobots\" />\n";
            }

            $this->extend('MetaTags', $tags);

            return $tags;
        }

        public function getOGTwitter()
        {
            $site_config    =   SiteConfig::current_site_config();
            if (!empty($this->OGType) || !empty($site_config->OGType)) {
                $data       =   [
                                    'OGType'                =>  !empty($this->OGType) ?
                                                                $this->OGType :
                                                                $site_config->OGType,
                                    'AbsoluteLink'          =>  $this->AbsoluteLink(),
                                    'OGTitle'               =>  !empty($this->OGTitle) ?
                                                                $this->OGTitle :
                                                                $this->Title,
                                    'OGDescription'         =>  !empty($this->OGDescription) ?
                                                                $this->OGDescription :
                                                                $site_config->OGDescription,
                                    'OGImage'               =>  !empty($this->OGImage()->exists()) ?
                                                                $this->OGImage() :
                                                                $site_config->OGImage(),
                                    'OGImageLarge'          =>  !empty($this->OGImageLarge()->exists()) ?
                                                                $this->OGImageLarge() :
                                                                $site_config->OGImageLarge(),
                                    'TwitterCard'           =>  !empty($this->TwitterCard) ?
                                                                $this->TwitterCard :
                                                                $site_config->TwitterCard,
                                    'TwitterTitle'          =>  !empty($this->TwitterTitle) ?
                                                                $this->TwitterTitle :
                                                                $this->Title,
                                    'TwitterDescription'    =>  !empty($this->TwitterDescription) ?
                                                                $this->TwitterDescription :
                                                                $site_config->TwitterDescription,
                                    'TwitterImageLarge'     =>  !empty($this->TwitterImageLarge()->exists()) ?
                                                                $this->TwitterImageLarge() :
                                                                $site_config->TwitterImageLarge(),
                                    'TwitterImage'          =>  !empty($this->TwitterImage()->exists()) ?
                                                                $this->TwitterImage() :
                                                                $site_config->TwitterImage(),
                                ];

                return ArrayData::create($data);
            }

            return null;
        }

        protected function addCORSHeaders($response)
        {
            $config             =   Config::inst()->get('Leochenftw\Restful\RestfulController');

            $default_origin     =   $config['CORSOrigin'];
            $allowed_origins    =   $config['CORSOrigins'];

            if (in_array($this->request->getHeader('origin'), $allowed_origins)) {
                $response->addHeader('Access-Control-Allow-Origin', $this->request->getHeader('origin'));
            } else {
                $response->addHeader('Access-Control-Allow-Origin', $default_origin);
            }

            $response->addHeader('Access-Control-Allow-Methods', $config['CORSMethods']);
            $response->addHeader('Access-Control-Max-Age', $config['CORSMaxAge']);
            $response->addHeader('Access-Control-Allow-Headers', $config['CORSAllowHeaders']);
            if ($config['CORSAllowCredentials']) {
                $response->addHeader('Access-Control-Allow-Credentials', 'true');
            }

            $response->addHeader('Content-Type', 'application/json');

            return $response;
        }
    }
}
