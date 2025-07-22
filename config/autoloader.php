<?php
/**
 * File Autoloader
 */

spl_autoload_register(function ($className) {
    $directories = [
        __DIR__ . '/../config/',
        __DIR__ . '/../controllers/',
        __DIR__ . '/../middleware/',
        __DIR__ . '/../models/',
        __DIR__ . '/../public/',
        __DIR__ . '/../utils/'
    ];

    foreach ($directories as $dir) {
        $file = $dir . $className . ".php";

        if (file_exists($file)) {
            require_once $file;

            return;
        }
    }
});