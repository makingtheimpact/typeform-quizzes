<?php
/**
 * Main Plugin Class
 * 
 * @package Typeform_Quizzes
 * @version 1.1.0
 * @author Making The Impact LLC
 */

namespace MTI\TypeformQuizzes;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

/**
 * Main Plugin Class
 * 
 * Singleton class that handles plugin initialization and bootstrapping.
 */
class Plugin
{
    /**
     * Plugin instance
     * 
     * @var Plugin|null
     */
    private static $instance = null;

    /**
     * Get plugin instance
     * 
     * @return Plugin
     */
    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }

    /**
     * Boot the plugin
     * 
     * @return void
     */
    public function boot()
    {
        // Initialize frontend assets
        \MTI\TypeformQuizzes\Frontend\Assets::init();
        
        // Initialize admin assets
        \MTI\TypeformQuizzes\Admin\Assets::init();
    }

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct()
    {
        // Private constructor for singleton pattern
    }

    /**
     * Prevent cloning
     */
    private function __clone()
    {
        // Prevent cloning
    }

    /**
     * Prevent unserialization
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
}
