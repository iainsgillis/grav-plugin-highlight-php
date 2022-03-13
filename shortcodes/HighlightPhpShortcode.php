<?php // shortcodes/HighlightPhpShortcode.php

namespace Grav\Plugin\Shortcodes;

use DomainException;
use Exception;
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
                $lang = $sc->getName();
                $code = $sc->getBbCode();
                return $this->render($lang, $code);
            });
        }
    }
    /**
     * Helper method to produce processed, syntax-highlightable HTML 
     * @param string $lang language or alias supported by highlight.php
     * @param string $code the code to tokenize and syntax highlight
     * @return string the HTML with the appropriate classes to be rendered as highlighted in the browser
     * @throws DomainException 
     * @throws Exception 
     */
    private function render(string $lang, string $code)
    {
        try {
            $hl = new \Highlight\Highlighter();
            $highlighted = $hl->highlight($lang, $code);
            $output = $highlighted->value;
            return "<code class='hljs language-$highlighted->language' style='display: inline;'>$output</code>";
        } catch (DomainException $e) {
            // if someone uses an unsupported language, we don't want to break the site
            return "<code class='hljs whoops-$lang-unknown-language'>$code</code>";
        }
    }
}
