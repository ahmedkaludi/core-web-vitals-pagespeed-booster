<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit8cc649ca1238d8fad15c0e7338cf29c3
{
    public static $prefixesPsr0 = array (
        'S' => 
        array (
            'Sabberworm\\CSS' => 
            array (
                0 => __DIR__ . '/..' . '/sabberworm/php-css-parser/lib',
            ),
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
           
            $loader->prefixesPsr0 = ComposerStaticInit8cc649ca1238d8fad15c0e7338cf29c3::$prefixesPsr0;

        }, null, ClassLoader::class);
    }
}
