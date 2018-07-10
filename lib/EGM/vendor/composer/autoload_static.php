<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInita3d5ef6f44ae82dfafd3223c504f0465
{
    public static $files = array (
        'ad155f8f1cf0d418fe49e248db8c661b' => __DIR__ . '/..' . '/react/promise/src/functions_include.php',
        'c964ee0ededf28c96ebd9db5099ef910' => __DIR__ . '/..' . '/guzzlehttp/promises/src/functions_include.php',
        'a0edc8309cc5e1d60e3047b5df6b7052' => __DIR__ . '/..' . '/guzzlehttp/psr7/src/functions_include.php',
    );

    public static $prefixLengthsPsr4 = array (
        'R' => 
        array (
            'React\\Promise\\' => 14,
        ),
        'P' => 
        array (
            'Psr\\Http\\Message\\' => 17,
        ),
        'G' => 
        array (
            'GuzzleHttp\\Stream\\' => 18,
            'GuzzleHttp\\Ring\\' => 16,
            'GuzzleHttp\\Psr7\\' => 16,
            'GuzzleHttp\\Promise\\' => 19,
            'GuzzleHttp\\' => 11,
        ),
        'D' => 
        array (
            'Dnetix\\Redirection\\' => 19,
            'Dnetix\\' => 7,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'React\\Promise\\' => 
        array (
            0 => __DIR__ . '/..' . '/react/promise/src',
        ),
        'Psr\\Http\\Message\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/http-message/src',
        ),
        'GuzzleHttp\\Stream\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/streams/src',
        ),
        'GuzzleHttp\\Ring\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/ringphp/src',
        ),
        'GuzzleHttp\\Psr7\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/psr7/src',
        ),
        'GuzzleHttp\\Promise\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/promises/src',
        ),
        'GuzzleHttp\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/guzzle/src',
        ),
        'Dnetix\\Redirection\\' => 
        array (
            0 => __DIR__ . '/..' . '/dnetix/redirection/src',
        ),
        'Dnetix\\' => 
        array (
            0 => __DIR__ . '/..' . '/dnetix/utils/src/Dnetix',
        ),
    );

    public static $classMap = array (
        'DateCheckerTest' => __DIR__ . '/..' . '/dnetix/utils/tests/Dates/DateCheckerTest.php',
        'DecimalToRomansTest' => __DIR__ . '/..' . '/dnetix/utils/tests/Converters/DecimalToRomansTest.php',
        'RomansToDecimalTest' => __DIR__ . '/..' . '/dnetix/utils/tests/Converters/RomansToDecimalTest.php',
        'TestCase' => __DIR__ . '/..' . '/dnetix/utils/tests/TestCase.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInita3d5ef6f44ae82dfafd3223c504f0465::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInita3d5ef6f44ae82dfafd3223c504f0465::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInita3d5ef6f44ae82dfafd3223c504f0465::$classMap;

        }, null, ClassLoader::class);
    }
}
