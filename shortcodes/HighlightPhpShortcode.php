<?php // shortcodes/HighlightPhpShortcode.php

namespace Grav\Plugin\Shortcodes;

use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class HighlightPhpShortcode extends Shortcode
{
    public function init()
    {
        $rawHandlers = $this->shortcode->getRawHandlers();
        // create an instance of the Highlighter class
        $hl = new \Highlight\Highlighter();

        // store the result of the listRegisteredLanguages helper method,
        // passing in the `true` argument to include aliases
        $langs = array_unique($hl->listRegisteredLanguages(true));

        // loop over the languages...
        foreach ($langs as $k) {

            // ... and add each one in turn
            $rawHandlers->add($k, function (ShortcodeInterface $sc) {
                // TODO: update the logic required by the Thunderer Shortcode engine
                return "<div>shortcode <span style='font-family: monospace'>" . $sc->getName() . "</span> successfully registered!</div>";
            });
        }
    }
}
