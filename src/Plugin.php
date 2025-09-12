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
        // Check compatibility first
        if (!\MTI\TypeformQuizzes\Support\VersionCheck::is_compatible()) {
            return;
        }

        // Initialize version checking
        \MTI\TypeformQuizzes\Support\VersionCheck::init();
        
        // Initialize error handling
        \MTI\TypeformQuizzes\Support\ErrorHandler::init();
        
        // Initialize health check
        \MTI\TypeformQuizzes\Support\HealthCheck::init();
        
        // Initialize post type
        \MTI\TypeformQuizzes\Admin\PostType::init();
        
        // Initialize meta boxes
        \MTI\TypeformQuizzes\Admin\MetaBoxes::init();
        
        // Initialize migration
        \MTI\TypeformQuizzes\Admin\Migration::init();
        
        // Initialize shortcodes
        \MTI\TypeformQuizzes\Frontend\Shortcodes\ShortcodeManager::init();
        
        // Initialize frontend assets
        \MTI\TypeformQuizzes\Frontend\Assets::init();
        
        // Initialize admin assets
        \MTI\TypeformQuizzes\Admin\Assets::init();
        
        // Initialize settings page
        \MTI\TypeformQuizzes\Admin\SettingsPage::init();
        
        // Initialize post list features
        \MTI\TypeformQuizzes\Admin\PostList::init();
        
        // Initialize AJAX handlers
        \MTI\TypeformQuizzes\Api\Ajax\Reorder::init();
        
        // Add activation/deactivation hooks
        register_activation_hook(TFQ_PLUGIN_FILE, [__CLASS__, 'activate']);
        register_deactivation_hook(TFQ_PLUGIN_FILE, [__CLASS__, 'deactivate']);
    }

    /**
     * Plugin activation
     */
    public static function activate(): void
    {
        // Set default options if they don't exist
        if (!get_option('typeform_quizzes_defaults')) {
            $defaults = [
                'max' => 20,
                'max_width' => 1450,
                'thumb_height' => 200,
                'cols_desktop' => 6,
                'cols_tablet' => 3,
                'cols_mobile' => 2,
                'gap' => 20,
                'border_radius' => 16,
                'title_color' => '#000000',
                'title_hover_color' => '#777777',
                'controls_spacing' => 56,
                'controls_spacing_tablet' => 56,
                'controls_bottom_spacing' => 20,
                'arrow_border_radius' => 0,
                'arrow_padding' => 3,
                'arrow_width' => 35,
                'arrow_height' => 35,
                'arrow_bg_color' => '#111111',
                'arrow_hover_bg_color' => '#000000',
                'arrow_icon_color' => '#ffffff',
                'arrow_icon_hover_color' => '#ffffff',
                'arrow_icon_size' => 28,
                'pagination_dot_color' => '#cfcfcf',
                'pagination_active_dot_color' => '#111111',
                'pagination_dot_gap' => 10,
                'pagination_dot_size' => 8,
                'active_slide_border_color' => '#0073aa',
                'darken_inactive_slides' => 1,
                'order' => 'menu_order'
            ];
            update_option('typeform_quizzes_defaults', $defaults);
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public static function deactivate(): void
    {
        // Flush rewrite rules
        flush_rewrite_rules();
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
