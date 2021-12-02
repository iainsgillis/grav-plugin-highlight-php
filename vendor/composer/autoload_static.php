<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit61b57ed6dfbb65cd70f7b41ac33678ff
{
    public static $files = array (
        'b6ec61354e97f32c0ae683041c78392a' => __DIR__ . '/..' . '/scrivo/highlight.php/HighlightUtilities/functions.php',
    );

    public static $prefixLengthsPsr4 = array (
        'G' => 
        array (
            'Grav\\Plugin\\HighlightPhp\\' => 25,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Grav\\Plugin\\HighlightPhp\\' => 
        array (
            0 => __DIR__ . '/../..' . '/classes',
        ),
    );

    public static $prefixesPsr0 = array (
        'H' => 
        array (
            'Highlight\\' => 
            array (
                0 => __DIR__ . '/..' . '/scrivo/highlight.php',
            ),
            'HighlightUtilities\\' => 
            array (
                0 => __DIR__ . '/..' . '/scrivo/highlight.php',
            ),
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'Grav\\Plugin\\HighlightPhpPlugin' => __DIR__ . '/../..' . '/highlight-php.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit61b57ed6dfbb65cd70f7b41ac33678ff::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit61b57ed6dfbb65cd70f7b41ac33678ff::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInit61b57ed6dfbb65cd70f7b41ac33678ff::$prefixesPsr0;
            $loader->classMap = ComposerStaticInit61b57ed6dfbb65cd70f7b41ac33678ff::$classMap;

        }, null, ClassLoader::class);
    }
}
