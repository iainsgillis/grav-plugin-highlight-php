<?php

namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Grav;
use Grav\Common\Filesystem\Folder;
use Grav\Common\Inflector;
use Grav\Common\Plugin;
use Grav\Framework\File\File;
use InvalidArgumentException;
use Pimple\Exception\FrozenServiceException;
use Pimple\Exception\UnknownIdentifierException;
use RocketTheme\Toolbox\Event\Event;

/**
 * Class HighlightPhpPlugin
 * @package Grav\Plugin
 */
class HighlightPhpPlugin extends Plugin
{
    private const BUILT_IN_STYLES_DIRECTORY = 'plugin://highlight-php/vendor/scrivo/highlight.php/styles/';
    private const CUSTOM_STYLES_DIRECTORY = 'user-data://highlight-php/';
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
            'onPluginsInitialized' => ['onPluginsInitialized', 0],
            'onGetPageBlueprints' => ['onGetPageBlueprints', 0]
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

        // enable other required events
        $this->enable([
            'onShortcodeHandlers' => ['onShortcodeHandlers', 0],
            'onPageInitialized' => ['onPageInitialized', 0]
        ]);

        if (!(is_dir(self::CUSTOM_STYLES_DIRECTORY))) {
            Folder::create(self::CUSTOM_STYLES_DIRECTORY);
            $demoCss = '.hljs { font-family: cursive; }';
            $file = new File(self::CUSTOM_STYLES_DIRECTORY . 'exampleOverrideCursiveFont.css');
            $file->save($demoCss);
        }
    }

    /**
     * Check for per-page configuration settings 
     */
    public function onPageInitialized()
    {
        // don't proceed if in admin
        if ($this->isAdmin()) {
            return;
        }

        $defaults = $this->config->get('plugins.highlight-php');
        $isSiteWide = $defaults['assetLoading'] === 'siteWide';

        $page = $this->grav['page'];
        $pagePreferencesSet = isset($page->header()->{'highlight-php'});

        $config = $this->mergeConfig($page);

        // per-page strategy, no page override: don't load assets
        if (!$isSiteWide && !$pagePreferencesSet) {
            return;
        }

        // two cases where we proceed to check against the style !== 'None' rules...
        if (
            !$pagePreferencesSet && $isSiteWide ||          // ➊ plugin defaults: no user override with site-wide strategy
            $pagePreferencesSet && $config['enabled']       // ➋ per-page preferences set and the user set 'enabled' to true
        ) {
            // set the configured theme, falling back to 'default' if unset somehow
            $style = $this->config->get('style') ?: 'default';
            // set the configured theme, falling back to 'None' if unset somehow
            $customStyle = $this->config->get('customStyle') ?: 'None';

            // register the css for our plugin, if required
            if ($this->shouldLoadAsset($style)) {
                $this->addHighlightingAssets($style, 'builtIn');
            }
            if ($this->shouldLoadAsset($customStyle)) {
                $this->addHighlightingAssets($customStyle, 'custom');
            }
        }
    }

    /**
     * Register shortcodes
     */
    public function onShortcodeHandlers()
    {
        // FYI: `onShortCodeHandlers` is fired by the shortcode core at the `onThemesInitialized` event 
        $this->grav['shortcode']->registerAllShortcodes(__DIR__ . '/shortcodes');
    }

    /**
     * Helper function to make other code's intent clearer
     * @param string $styleName basename of a CSS file
     * @return bool true if $styleName is not the string 'None'
     */
    private function shouldLoadAsset($styleName)
    {
        return $styleName !== 'None';
    }

    /**
     * Adds a CSS file to Grav's asset pipeline
     * @param string $styleName basename of the CSS file
     * @param string $builtInOrCustom which directory to look for the file
     */
    private function addHighLightingAssets($styleName, $builtInOrCustom)
    {
        $locator = $this->grav['locator'];
        switch ($builtInOrCustom) {
            case 'builtIn':
                $themePath = $locator->findResource(self::BUILT_IN_STYLES_DIRECTORY . $styleName . '.css', false);
                break;
            case 'custom':
                $themePath = $locator->findResource(self::CUSTOM_STYLES_DIRECTORY  . $styleName . '.css', false);
                break;
            default:
                $errorMsg = 'Highlight-PHP plugin error: Invalid parameter passed to `addHighLightingAssets`. Must be one of `builtIn` or `custom`. No syntax highlighting CSS will be loaded.';
                $this->grav['debugger']->addMessage($errorMsg);
                $this->grav['log']->error($errorMsg);
                return;
        }
        $this->grav['assets']->addCss($themePath);
    }

    /**
     * 
     * @param string $directory Input URI to search
     * @return string[] associative array of css filenames, plus a 'None' entry
     */
    private static function getThemesInDirectory($directory)
    {
        /** @var UniformResourceLocator $locator */
        $locator = Grav::instance()['locator'];

        # initialize an array with our default
        $themes = array('None' => 'None');

        # use the findResource method to resolve the plugin stream location; false returns a relative path
        $stylesPath = $locator->findResource($directory, false);

        # plain old PHP glob. See https://www.php.net/manual/en/function.glob.php
        $cssFiles = glob($stylesPath . '/*.css');

        foreach ($cssFiles as $cssFile) {
            $theme = basename($cssFile, '.css');
            $themes[$theme] = Inflector::titleize($theme);
        }

        return $themes;
    }

    /**
     * List of themes available that ship with the plugin
     * @return string[]
     */
    public static function getBuiltInThemes()
    {
        $builtInThemes = HighlightPhpPlugin::getThemesInDirectory(self::BUILT_IN_STYLES_DIRECTORY);
        return $builtInThemes;
    }

    /**
     * List of themes available in the user/data/highlight-php directory
     * @return string[] associative array of css filenames, plus a 'None' entry
     */
    public static function getCustomThemes()
    {
        $customThemes = HighlightPhpPlugin::getThemesInDirectory(self::CUSTOM_STYLES_DIRECTORY);
        return $customThemes;
    }

    /**
     * List of themes available in the user/data/highlight-php directory
     * @return string[] associative array of css filenames, plus a 'None' entry
     */
    public static function getUserActivationPreference()
    {
        return Grav::instance()['config']->get('plugins.highlight-php.assetLoading') === 'siteWide';
    }

    public function onGetPageBlueprints($event)
    {
        $types = $event->types;
        $types->scanBlueprints('plugin://highlight-php/blueprints');
    }
}
