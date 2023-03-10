<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit2b596b6a9d4c2c5ec40a67f646a7cb9b
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'PHPMailer\\PHPMailer\\' => 20,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'PHPMailer\\PHPMailer\\' => 
        array (
            0 => __DIR__ . '/..' . '/phpmailer/phpmailer/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit2b596b6a9d4c2c5ec40a67f646a7cb9b::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit2b596b6a9d4c2c5ec40a67f646a7cb9b::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit2b596b6a9d4c2c5ec40a67f646a7cb9b::$classMap;

        }, null, ClassLoader::class);
    }
}
