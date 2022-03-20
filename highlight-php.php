<?php

namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Grav;
use Grav\Common\Filesystem\Folder;
use Grav\Common\Inflector;
use Grav\Common\Plugin;

/**
 * Class HighlightPhpPlugin
 * @package Grav\Plugin
 */
class HighlightPhpPlugin extends Plugin
{
    /**
     * @return array
     *
     * The getSubscribedEvents() gives the core a list of events
     *     that the plugin wants to listen to. The key of each
     *     array section is the event that the plugin listens to
     *     and the value (in the form of an array) contains the
     *     callable (or function) as well as the priority. The
     *     higher the number the higher the priority.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onPluginsInitialized' => [
                ['autoload', 100000], // since we're requiring Grav < 1.7
                ['onPluginsInitialized', 0]
            ]
        ];
    }

    /**
     * Composer autoload
     *
     * @return ClassLoader
     */
    public function autoload(): ClassLoader
    {
        return require __DIR__ . '/vendor/autoload.php';
    }

    /**
     * Initialize the plugin
     */
    public function onPluginsInitialized()
    {
        // don't proceed if in admin
        if ($this->isAdmin()) {
            return;
        }

        // don't proceed if plugin is disabled
        if (!$this->config->get('plugins.highlight-php.enabled')) {
            return;
        }

        // enable other required events
        $this->enable([
            'onShortcodeHandlers' => ['onShortcodeHandlers', 0],
        ]);

        // set the configured theme, falling back to 'default' if unset
        $theme = $this->config->get('plugins.highlight-php.theme') ?: 'default';

        // create the user/custom directory if it doesn't exist
        $customStylesDirName = $this->config->get('plugins.highlight-php.custom_styles');
        $locator = Grav::instance()['locator'];
        $userCustomDirPath = $locator->findResource('user://') . '/' . 'custom' . '/' . $customStylesDirName;
        if (!($locator->findResource($userCustomDirPath))) {
            Folder::create($userCustomDirPath);
        }

        // register the css for our plugin
        $this->addHighlightingAssets($theme);
    }

    public function onShortcodeHandlers()
    {
        // FYI: `onShortCodeHandlers` is fired by the shortcode core at the `onThemesInitialized` event 
        $this->grav['shortcode']->registerAllShortcodes(__DIR__ . '/shortcodes');
    }

    private function addHighLightingAssets($theme)
    {
        $locator = $this->grav['locator'];
        if (str_ends_with($theme, '¹')) {
            // custom theme
            $theme = str_replace('¹', '', $theme);
            $customStylesDirName = $this->grav['config']->get('plugins.highlight-php.custom_styles');
            $themePath = $locator->findResource('user://custom/' . $customStylesDirName . '/' . $theme . '.css', false);
        } else {
            // built-in theme
            $themePath = $locator->findResource('plugin://highlight-php/vendor/scrivo/highlight.php/styles/' . $theme . '.css', false);
        }
        $this->grav['assets']->addCss($themePath);
    }

    public static function getAvailableThemes()
    {
        # make references to objects on our Grav instance
        $grav = Grav::instance();
        $locator = $grav['locator'];
        $config = $grav['config'];

        # initialize an empty array
        $themes = [];

        # resolve the custom styles directory
        $customStylesDirName = $config->get('plugins.highlight-php.custom_styles');
        $customStylesPath = $locator->findResource('user://custom/' . $customStylesDirName, false);

        if ($customStylesPath) {
            # get our list of custom CSS files
            $customCssFiles = glob($customStylesPath . '/*.css');
            foreach ($customCssFiles as $cssFile) {
                # append a superscript 1 (¹) to prevent naming conflicts if customizing an inbuilt theme
                $theme = basename($cssFile, '.css') . '¹';
                # indicate to the user that this theme is one of the custom uploads
                $themes[$theme] = Inflector::titleize($theme) . ' (custom)';
            }
        }

        # ➍ use the findResource method to resolve the plugin stream location; false returns a relative path
        $bundledStylesPath = $locator->findResource('plugin://highlight-php/vendor/scrivo/highlight.php/styles', false);

        # plain old PHP glob. See https://www.php.net/manual/en/function.glob.php
        $cssFiles = glob($bundledStylesPath . '/*.css');

        foreach ($cssFiles as $cssFile) {
            # ➋ store our key
            $theme = basename($cssFile, ".css");
            # ➌ set our value and add it to the array
            $themes[$theme] = Inflector::titleize($theme); # ➍ thanks, titleize
        }

        # return the array
        return $themes;
    }
}
