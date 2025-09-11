<?php
/**
 * Lightweight PSR-4 autoloader for the plugin.
 */
spl_autoload_register(function($class) {
    $prefix = 'MTI\\TypeformQuizzes\\';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative = substr($class, $len);
    $relative_path = str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
    $file = __DIR__ . DIRECTORY_SEPARATOR . $relative_path;
    if (is_file($file)) {
        require $file;
    }
});
