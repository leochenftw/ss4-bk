<?php
namespace App\Web\Extension;
use SilverStripe\Core\Extension;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Core\Convert;
use SilverStripe\Control\Director;
use Leochenftw\Debugger;
use SilverStripe\Core\Config\Config;
use App\Web\Extension\VueRenderExtension;
use \Exception;
use \RecursiveArrayIterator;
use \RecursiveIteratorIterator;
use Page;

class VueRenderer extends Extension
{
    public function render_vue_html($www_user = null, $www_group = null)
    {
        $record =   $this->owner;
        if ($record->isPublished()) {
            $siteconfig =   SiteConfig::current_site_config();
            $dir    =   Config::inst()->get(VueRenderExtension::class, 'dist_path');
            if (!$dir) {
                throw new Exception('Please set your "dist_path" in the yml file first!');
            }

            $index  =   Config::inst()->get(VueRenderExtension::class, 'dist_path') . '/index.html';
            if (!is_dir($dir)) {
                throw new Exception('Frontend does not exists!');
            }

            if (!file_exists($index)) {
                throw new Exception('Frontend index page is not found!');
            }

            $index  =   file_get_contents($index);
            if (strpos($record->Link(), '?') !== false) {
                return;
            }

            if (!is_dir($dir . $record->Link())) {
                mkdir($dir . $record->Link(), 0755, true);
            }

            if (!empty($www_user)) {
                chown($dir . $record->Link(), $www_user);
            }

            if (!empty($www_group)) {
                chgrp($dir . $record->Link(), $www_group);
            }

            $script_pattern =   "/\<script type=\"text\/javascript\" src=\"(.*?)\"\>\<\/script\>/i";
            preg_match_all($script_pattern, $index, $matches);
            $scripts        =   count($matches) > 0 ? $matches[0] : [];
            $style_pattern  =   "/\<link href=\"(.*?)\" rel=\"stylesheet\">/i";
            preg_match_all($style_pattern, $index, $matches);
            $styles         =   count($matches) > 0 ? $matches[0] : [];

            if (file_exists($dir . $record->Link() . 'index.html')) {
                $index  =   file_get_contents($dir . $record->Link() . 'index.html');
                if (!empty($index)) {
                    $title_pattern  =   "/\<title>(.*?)<\/title>/i";
                    preg_match($title_pattern, $index, $matches);
                    if (!empty($matches) && count($matches) > 1) {
                        $index  =   str_replace($matches[1], (!empty($record->MetaTitle) ? $record->MetaTitle : $record->Title) . ' | ' . $siteconfig->Title, $index);
                    }

                    $index  =   $this->purge_existing_tags($index);

                    $i      =   strpos($index, '</head>');
                    $index  =   substr_replace($index, implode("\n", $styles) . "\n" . $this->compose_meta($record), $i, 0);

                    $i      =   strpos($index, '</body>');
                    $index  =   substr_replace($index, implode("\n", $scripts) . "\n", $i, 0);

                    $this->write_html($dir, $record->Link() . 'index.html', $index, $www_user, $www_group);
                }
            }

            $html   =   '<!DOCTYPE html>
            <html>
                <head>
                    <meta charset="utf-8">
                    <meta name="viewport" content="width=device-width,initial-scale=1.0">
                    <title>' . (!empty($record->MetaTitle) ? $record->MetaTitle : $record->Title) . ' | Independent Schools of New Zealand</title>
                    ' . implode("\n", $styles) . '
                    ' . $this->compose_meta($record) . '
                </head>
                <body>
                    <div id="app"></div>
                    ' . implode("\n", $scripts) . '
                </body>
            </html>';
            return $this->write_html($dir, $record->Link() . 'index.html', $html, $www_user, $www_group);
        }
    }

    private function write_html($dir, $file_name, $html, $www_user = null, $www_group = null)
    {
        $html  =   str_replace("\n", "", $html);
        $html  =   str_replace("\t", "", $html);

        while (strpos($html, "  ") !== false) {
            $html  =   str_replace("  ", " ", $html);
        }
        $html  =   str_replace("> <", "><", $html);
        $html  =   str_replace("> </", "></", $html);

        file_put_contents($dir . $file_name, $html);

        if (!empty($www_user)) {
            chown($dir . $file_name, $www_user);
        }

        if (!empty($www_group)) {
            chgrp($dir . $file_name, $www_group);
        }

        return true;
    }

    private function purge_existing_tags($index)
    {
        $script_pattern =   "/\<script type=\"text\/javascript\" src=\"(.*?)\"\>\<\/script\>/i";
        preg_match_all($script_pattern, $index, $matches);
        if (!empty($matches) && count($matches) > 0) {
            foreach ($matches[0] as $script) {
                $index  =   str_replace($script, '', $index);
            }
        }

        $style_pattern  =   "/\<link href=\"(.*?)\" rel=\"stylesheet\">/i";
        preg_match_all($style_pattern, $index, $matches);
        if (!empty($matches) && count($matches) > 0) {
            foreach ($matches[0] as $script) {
                $index  =   str_replace($script, '', $index);
            }
        }

        $canonical_pattern  =   "/<link rel=\"canonical\" href=\"(.*?)\"(.*?)>/i";
        preg_match($canonical_pattern, $index, $matches);
        if (!empty($matches) && count($matches) > 1) {
            $index  =   str_replace($matches[0], '', $index);
        }

        $desc_pattern  =   "/<meta name=\"description\" content=\"(.*?)\"(.*?)>/i";
        preg_match($desc_pattern, $index, $matches);
        if (!empty($matches) && count($matches) > 1) {
            $index      =   str_replace($matches[0], '', $index);
        }

        $keyword_pattern    =   "/<meta name=\"keywords\" content=\"(.*?)\"(.*?)>/i";
        preg_match($keyword_pattern, $index, $matches);
        if (!empty($matches) && count($matches) > 1) {
            $index          =   str_replace($matches[0], '', $index);
        }

        $robots_pattern =   "/<meta name=\"robots\" content=\"(.*?)\"(.*?)>/i";
        preg_match($robots_pattern, $index, $matches);
        if (!empty($matches) && count($matches) > 1) {
            $index      =   str_replace($matches[0], '', $index);
        }

        $og_pattern =   "/<meta property=\"og:(.*?)>/i";
        preg_match_all($og_pattern, $index, $matches);
        if (!empty($matches) && count($matches) > 0) {
            foreach ($matches[0] as $og_tags) {
                $index  =   str_replace($og_tags, '', $index);
            }
        }

        $fb_pattern =   "/<meta property=\"fb:(.*?)>/i";
        preg_match_all($fb_pattern, $index, $matches);
        if (!empty($matches) && count($matches) > 0) {
            foreach ($matches[0] as $fb_tags) {
                $index  =   str_replace($fb_tags, '', $index);
            }
        }

        $twitter_pattern    =   "/<meta name=\"twitter:(.*?)>/i";
        preg_match_all($twitter_pattern, $index, $matches);
        if (!empty($matches) && count($matches) > 0) {
            foreach ($matches[0] as $twitter_tags) {
                $index  =   str_replace($twitter_tags, '', $index);
            }
        }

        $g_pattern =   "/<meta itemprop=\"name\" content=\"(.*?)>/i";
        preg_match_all($g_pattern, $index, $matches);
        if (!empty($matches) && count($matches) > 0) {
            foreach ($matches[0] as $g_tags) {
                $index  =   str_replace($g_tags, '', $index);
            }
        }

        $g_pattern =   "/<meta itemprop=\"description\" content=\"(.*?)>/i";
        preg_match_all($g_pattern, $index, $matches);
        if (!empty($matches) && count($matches) > 0) {
            foreach ($matches[0] as $g_tags) {
                $index  =   str_replace($g_tags, '', $index);
            }
        }

        $g_pattern =   "/<meta itemprop=\"image\" content=\"(.*?)>/i";
        preg_match_all($g_pattern, $index, $matches);
        if (!empty($matches) && count($matches) > 0) {
            foreach ($matches[0] as $g_tags) {
                $index  =   str_replace($g_tags, '', $index);
            }
        }

        return $index;
    }

    private function composer_canonical($record)
    {
        $social_base_url    =   SiteConfig::current_site_config()->SocialBaseURL;
        $local_canonical    =   empty($record->ConanicalURL) ? $record->AbsoluteLink() : Convert::raw2att($record->ConanicalURL);

        if (!empty($social_base_url)) {
            return str_replace(Director::absoluteBaseURL(), $social_base_url, $local_canonical);
        }

        return $local_canonical;
    }

    private function compose_meta($record)
    {
        $html   =   '<link rel="canonical" href="' . $this->composer_canonical($record) . '">' . "\n";

        $html   .=  '<meta name="description" content="' . $record->get_meta_description() . '">' . "\n";

        if (!empty($record->MetaKeywords)) {
            $html   .=  '<meta name="keywords" content="' . Convert::raw2att($record->MetaKeywords) . '">' . "\n";
        }

        if (!Director::isLive()) {
            $html   .=  '<meta name="robots" content="noindex, nofollow, noarchive">' . "\n";
        } elseif (!empty($record->MetaRobots)) {
            $html   .=  '<meta name="robots" content="' . Convert::raw2att($record->MetaRobots) . '">' . "\n";
        }

        $html   .=  $this->compose_og_twitter($record);

        return $html;
    }

    private function compose_og_twitter($record)
    {
        $html   =   '';
        foreach ($record->get_og_twitter_meta() as $item) {
            if (!empty($item['content'])) {
                $html   .=  '<meta ';
                foreach ($item as $key => $value) {
                    $html   .=  $key . '="' . $value . '" ';
                }

                $html   .=  '>' . "\n";
            }
        }

        return str_replace(' >', '>', $html);
    }
}
