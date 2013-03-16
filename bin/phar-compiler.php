<?php
define('ROOT_PATH', realpath(__DIR__  . '/../') . '/');

compile(ROOT_PATH . 'bin/vdb-uri.phar');

function compile($destFile)
{
    if (file_exists($destFile)) {
        unlink($destFile);
    }

    $phar = new \Phar($destFile);
    $phar->setSignatureAlgorithm(\Phar::SHA1);
    $phar->startBuffering();

    $files = get_src_files();
    foreach ($files as $file) {
        $phar->addFromString(str_replace(ROOT_PATH, '', $file), file_get_contents($file));
    }

    $phar['_web.php'] = "<?php throw new \LogicException('This PHAR file can only be used from the CLI.'); __HALT_COMPILER();";
    $phar['_cli.php'] = "<?php require_once '" . ROOT_PATH . "vendor/autoload.php'; __HALT_COMPILER();";
    $phar->setDefaultStub('_cli.php', '_web.php');

    $phar->stopBuffering();

    unset($phar);
}

function get_src_files()
{
    $files = array(
        ROOT_PATH . 'vendor/autoload.php',
    );

    $dirs = array(
        ROOT_PATH . 'src/VDB/Uri',
        ROOT_PATH . 'vendor/composer'
    );

    foreach ($dirs as $dir) {
        $files = array_merge($files, glob($dir . '/*.php'));
    }

    return $files;
}

