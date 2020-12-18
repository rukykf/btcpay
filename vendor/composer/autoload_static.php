<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit1014d2eb747bcdf96f56b309ec0830c7
{
    public static $fallbackDirsPsr4 = array (
        0 => __DIR__ . '/..' . '/btcpayserver/btcpayserver-php-client/src',
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->fallbackDirsPsr4 = ComposerStaticInit1014d2eb747bcdf96f56b309ec0830c7::$fallbackDirsPsr4;
            $loader->classMap = ComposerStaticInit1014d2eb747bcdf96f56b309ec0830c7::$classMap;

        }, null, ClassLoader::class);
    }
}