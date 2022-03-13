<?php // shortcodes/HighlightPhpShortcode.php

namespace Grav\Plugin\Shortcodes;

use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class HighlightPhpShortcode extends Shortcode
{
    public function init()
    {
        $rawHandlers = $this->shortcode->getRawHandlers();
        $rawHandlers->add('hl', function (ShortcodeInterface $sc) {
            return "<div>shortcode <span style='font-family: monospace'>hl</span> successfully registered!</div>";
        });
    }
}

