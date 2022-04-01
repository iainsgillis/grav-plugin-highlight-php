<?php

namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Grav;
use Grav\Common\Filesystem\Folder;
use Grav\Common\Inflector;
use Grav\Common\Plugin;
use RocketTheme\Toolbox\File\File;

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

        // enable other required events
        $this->enable([
            'onShortcodeHandlers' => ['onShortcodeHandlers', 0],
        ]);

        if (!(is_dir(self::CUSTOM_STYLES_DIRECTORY))) {
            Folder::create(self::CUSTOM_STYLES_DIRECTORY);
            $demoCss = '.hljs { font-family: cursive; }';
            $file = File::instance(self::CUSTOM_STYLES_DIRECTORY . 'exampleOverrideCursiveFont.css');
            $file->save($demoCss);
        }

        // set the configured theme, falling back to 'default' if unset
        $style = $this->config->get('plugins.highlight-php.style') ?: 'default';
        // set the configured theme, falling back to 'default' if unset
        $customStyle = $this->config->get('plugins.highlight-php.customStyle') ?: 'None';

        // register the css for our plugin, if required
        if ($this->shouldLoadAsset($style)) {
            $this->addHighlightingAssets($style, 'builtIn');
        }
        if ($this->shouldLoadAsset($customStyle)) {
            $this->addHighlightingAssets($customStyle, 'custom');
        }
    }

    public function onPageInitialized()
    {
        // don't proceed if in admin
        if ($this->isAdmin()) {
            return;
        }
    }

    public function onShortcodeHandlers()
    {
        // FYI: `onShortCodeHandlers` is fired by the shortcode core at the `onThemesInitialized` event 
        $this->grav['shortcode']->registerAllShortcodes(__DIR__ . '/shortcodes');
    }

    private function shouldLoadAsset($styleName)
    {
        return $styleName !== 'None';
    }

    private function addHighLightingAssets($styleName, $builtInOrCustom)
    {
        $locator = $this->grav['locator'];
        if ($builtInOrCustom === 'builtIn') {
            $themePath = $locator->findResource(self::BUILT_IN_STYLES_DIRECTORY . $styleName . '.css', false);
        }
        if ($builtInOrCustom === 'custom') {
            $themePath = $locator->findResource(self::CUSTOM_STYLES_DIRECTORY  . $styleName . '.css', false);
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
}
