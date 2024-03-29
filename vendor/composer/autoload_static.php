<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitb0c240b07b35f610694ec8b55b68e6a3
{
    public static $prefixLengthsPsr4 = array (
        'A' => 
        array (
            'Automattic\\WooCommerce\\' => 23,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Automattic\\WooCommerce\\' => 
        array (
            0 => __DIR__ . '/..' . '/automattic/woocommerce/src/WooCommerce',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitb0c240b07b35f610694ec8b55b68e6a3::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitb0c240b07b35f610694ec8b55b68e6a3::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitb0c240b07b35f610694ec8b55b68e6a3::$classMap;

        }, null, ClassLoader::class);
    }
}
