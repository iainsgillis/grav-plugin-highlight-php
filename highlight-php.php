<?php

namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
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
        if (!$this->config->get('plugins.php-highlight.enabled')) {
            return;
        }

        // enable other required events
        $this->enable([
            'onShortcodeHandlers' => ['onShortcodeHandlers', 0],
        ]);

        // set the configured theme, falling back to 'default' if unset
        $theme = $this->config->get('plugins.php-highlight.theme') ?: 'default';

        // register the css for our plugin
        $this->addHighlightingAssets($theme);
    }

    public function onShortcodeHandlers()
    {
        $this->grav['shortcode']->registerAllShortcodes(__DIR__ . '/shortcodes');
    }

    private function addHighLightingAssets($theme)
    {
        // add the syntax highlighting CSS file
        $this->grav['assets']->addCss('plugin://php-highlight/vendor/scrivo/highlight.php/styles/' . $theme . '.css');
    }
}
