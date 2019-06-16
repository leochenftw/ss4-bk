<?php

namespace App\Web\Task;
use SilverStripe\Dev\BuildTask;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Core\Convert;
use SilverStripe\Control\Director;
use Leochenftw\Debugger;
use SilverStripe\Core\Config\Config;
use SilverStripe\Admin\LeftAndMainExtension;
use SilverStripe\Core\ClassInfo;
use \Exception;
use \RecursiveDirectoryIterator;
use \RecursiveArrayIterator;
use \RecursiveIteratorIterator;
use App\Web\Extension\VueRenderExtension;
use Page;
use SilverStripe\ErrorPage\ErrorPage;
use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\CMS\Model\VirtualPage;
use SilverStripe\Versioned\Versioned;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class VuePrerenderTask extends BuildTask
{
    private $reserved   =   [
        'DIST_PATH/index.html',
        'DIST_PATH/static',
        'DIST_PATH/.htaccess'
    ];

    private $ignored_page_types =   [

    ];

    /**
     * @var bool $enabled If set to FALSE, keep it from showing in the list
     * and from being executable through URL or CLI.
     */
    protected $enabled = true;

    /**
     * @var string $title Shown in the overview on the TaskRunner
     * HTML or CLI interface. Should be short and concise, no HTML allowed.
     */
    protected $title = 'Vue Prerenderer';

    /**
     * @var string $description Describe the implications the task has,
     * and the changes it makes. Accepts HTML formatting.
     */
    protected $description = 'Prebuild all pages for Vue frontend use';

    /**
     * This method called via the TaskRunner
     *
     * @param SS_HTTPRequest $request
     */
    public function run($request)
    {
        $dir    =   Config::inst()->get(VueRenderExtension::class, 'dist_path');

        foreach ($this->reserved as &$path) {
            $path   =   str_replace('DIST_PATH', $dir, $path);
        }

        if (!$dir) {
            throw new Exception('Please set your "dist_path" in the yml file first!');
        }

        $this->purge_existing($dir);

        foreach (Versioned::get_by_stage(Page::class, 'Live')->exclude(['ClassName' => [ErrorPage::class, RedirectorPage::class, VirtualPage::class]]) as $page) {
            $page->render_vue_html(fileowner($dir), filegroup($dir));
            print $page->Title . ' has been rendered.'  . PHP_EOL;
        }

        print 'All page rendered!' . PHP_EOL;;
    }

    private function purge_existing($dir)
    {
        $it     =   new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files  =   new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);

        foreach($files as $file) {
            if ($file->isDir()){
                if ($this->can_be_deleted($file)) {
                    rmdir($file->getRealPath());
                }
            } else {
                if ($this->can_be_deleted($file)) {
                    unlink($file->getRealPath());
                }
            }
        }

        print 'Previously rendered files & directories have all been purged. Now rendering new...' . PHP_EOL;
    }

    private function can_be_deleted(&$file)
    {
        if (in_array($file->getRealPath(), $this->reserved)) {
            return false;
        }

        if ($file->isLink()) {
            return false;
        }

        foreach ($this->reserved as $rule) {
            if (strpos($file->getRealPath(), $rule) !== false) {
                return false;
            }
        }

        return true;
    }
}
