<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInita7e054cf16b6e0b04646a15cf900369a
{
    public static $prefixLengthsPsr4 = array (
        'W' => 
        array (
            'WebPConvert\\' => 12,
        ),
        'I' => 
        array (
            'ImageMimeTypeGuesser\\' => 21,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'WebPConvert\\' => 
        array (
            0 => __DIR__ . '/..' . '/rosell-dk/webp-convert/src',
        ),
        'ImageMimeTypeGuesser\\' => 
        array (
            0 => __DIR__ . '/..' . '/rosell-dk/image-mime-type-guesser/src',
        ),
    );

    public static $prefixesPsr0 = array (
        'S' => 
        array (
            'Sabberworm\\CSS' => 
            array (
                0 => __DIR__ . '/..' . '/sabberworm/php-css-parser/lib',
            ),
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInita7e054cf16b6e0b04646a15cf900369a::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInita7e054cf16b6e0b04646a15cf900369a::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInita7e054cf16b6e0b04646a15cf900369a::$prefixesPsr0;
            $loader->classMap = ComposerStaticInita7e054cf16b6e0b04646a15cf900369a::$classMap;

        }, null, ClassLoader::class);
    }
}