<?php
/**
 * Autoloader for MTI\TypeformQuizzes namespace
 * 
 * @package Typeform_Quizzes
 * @version 1.1.0
 * @author Making The Impact LLC
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

spl_autoload_register(function ($class) {
    // Check if the class is in our namespace
    if (strpos($class, 'MTI\\TypeformQuizzes\\') !== 0) {
        return;
    }
    
    // Remove the namespace prefix
    $class = substr($class, strlen('MTI\\TypeformQuizzes\\'));
    
    // Convert namespace separators to directory separators
    $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    
    // Build the file path
    $file = TFQ_PLUGIN_DIR . 'src' . DIRECTORY_SEPARATOR . $class . '.php';
    
    // Load the file if it exists
    if (file_exists($file)) {
        require_once $file;
    }
});
