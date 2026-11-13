<?php

namespace Grav\Plugin\Shortcodes {
    class Shortcode
    {
        public $shortcode;
    }
}

namespace Thunder\Shortcode\Shortcode {
    interface ShortcodeInterface
    {
        public function getBbCode();
        public function getContent();
        public function getParameter($name);
    }
}

namespace {
    error_reporting(E_ALL);
    set_error_handler(function ($severity, $message, $file, $line) {
        throw new \ErrorException($message, 0, $severity, $file, $line);
    });

    function smokeAssert($condition, $message)
    {
        if (!$condition) {
            throw new \RuntimeException($message);
        }
    }

    function smokeShortcode($language, $content = null, array $parameters = array())
    {
        return new class($language, $content, $parameters) implements \Thunder\Shortcode\Shortcode\ShortcodeInterface {
            private $language;
            private $content;
            private $parameters;

            public function __construct($language, $content, array $parameters)
            {
                $this->language = $language;
                $this->content = $content;
                $this->parameters = $parameters;
            }

            public function getBbCode()
            {
                return $this->language;
            }

            public function getContent()
            {
                return $this->content;
            }

            public function getParameter($name)
            {
                return $this->parameters[$name] ?? null;
            }
        };
    }

    try {
        $root = dirname(__DIR__);
        require $root . '/vendor/autoload.php';
        require $root . '/shortcodes/HighlightPhpShortcode.php';

        $registry = new class {
            public $handlers = array();

            public function add($name, $callback)
            {
                $this->handlers[$name] = $callback;
            }
        };
        $shortcode = new \Grav\Plugin\Shortcodes\HighlightPhpShortcode();
        $shortcode->shortcode = new class($registry) {
            private $registry;

            public function __construct($registry)
            {
                $this->registry = $registry;
            }

            public function getRawHandlers()
            {
                return $this->registry;
            }
        };
        $shortcode->init();
        smokeAssert(isset($registry->handlers['hl']) && is_callable($registry->handlers['hl']), 'init() must register the hl callback');
        $render = $registry->handlers['hl'];

        $tests = array(
            'inline PHP' => function () use ($render) {
                $code = '<?php echo "hello";';
                $html = $render(smokeShortcode('php', null, array('code' => $code)));
                smokeAssert(strpos($html, "<code class='hljs language-php' style='display: inline'>") === 0, 'Expected inline PHP code element');
                smokeAssert(strpos($html, '<pre') === false, 'Inline PHP must not have a pre wrapper');
                smokeAssert(strpos($html, '<span class="hljs-') !== false, 'PHP must be highlighted');
                smokeAssert(html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8') === $code, 'Inline PHP source must be preserved and escaped');
            },
            'block JavaScript' => function () use ($render) {
                $code = 'const answer = 42;';
                $html = $render(smokeShortcode('js', "\r\n" . $code . "\r\n", array('code' => 'ignored')));
                smokeAssert(strpos($html, "<pre class='hljs'><code class='hljs language-javascript' style='display: block'>") === 0, 'Expected block JavaScript wrapper and canonical language');
                smokeAssert(substr($html, -13) === '</code></pre>', 'Block must close code and pre elements');
                smokeAssert(strpos($html, '<span class="hljs-') !== false, 'JavaScript must be highlighted');
                smokeAssert(html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8') === $code, 'Block content must take precedence and trim surrounding newlines');
            },
            'unsupported language plaintext' => function () use ($render) {
                $expected = "<code class='hljs whoops-smoke-unsupported-unknown-language'>plain text</code>";
                smokeAssert($render(smokeShortcode('smoke-unsupported', null, array('code' => 'plain text'))) === $expected, 'Unsupported inline language must fall back to plaintext');
                smokeAssert($render(smokeShortcode('smoke-unsupported', 'plain text')) === "<pre class='hljs'>" . $expected . '</pre>', 'Unsupported block language must retain its wrapper');
            },
            'missing code parameter' => function () use ($render) {
                smokeAssert($render(smokeShortcode('php')) === "<code class='hljs language-php' style='display: inline'></code>", 'Missing code must produce empty inline output');
            },
            'missing language' => function () use ($render) {
                smokeAssert($render(smokeShortcode(null, null, array('code' => 'plain text'))) === "<code class='hljs whoops--unknown-language'>plain text</code>", 'Missing language must fall back without errors');
            },
            'empty block' => function () use ($render) {
                smokeAssert($render(smokeShortcode('js', '')) === "<pre class='hljs'><code class='hljs language-javascript' style='display: block'></code></pre>", 'Empty content must remain a block, not inline');
            },
            'built-in stylesheet' => function () use ($root) {
                $path = $root . '/vendor/scrivo/highlight.php/styles/default.css';
                smokeAssert(is_file($path) && is_readable($path), 'Built-in default stylesheet must exist and be readable');
                smokeAssert(filesize($path) > 0, 'Built-in default stylesheet must not be empty');
            },
        );

        foreach ($tests as $name => $test) {
            $test();
            echo 'PASS: ' . $name . PHP_EOL;
        }
        echo 'All ' . count($tests) . ' smoke tests passed.' . PHP_EOL;
    } catch (\Throwable $error) {
        fwrite(STDERR, 'FAIL: ' . $error->getMessage() . ' (' . $error->getFile() . ':' . $error->getLine() . ')' . PHP_EOL);
        exit(1);
    }
}
