<?php
/**
 * Plugin Name: Typeform Quizzes
 * Plugin URI: https://makingtheimpact.com
 * Description: Professional Typeform Quizzes slider with advanced features.
 * Version: 1.1.0
 * Requires at least: 5.0
 * Tested up to: 6.5
 * Requires PHP: 7.4
 * Author: Making The Impact LLC
 * Author URI: https://makingtheimpact.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI: https://makingtheimpact.com/typeform-quizzes
 * Text Domain: typeform-quizzes
 * Domain Path: /languages
 * Network: false
 * 
 * Features:
 * - Responsive slider with Swiper.js integration
 * - Hosting platform compatibility (WP Engine, EasyWP, Kinsta, SiteGround, GoDaddy, Bluehost, HostGator)
 * - Enhanced security with input validation and rate limiting
 * - Performance optimization with object caching and lazy loading
 * - Conflict prevention with scoped CSS
 * - Comprehensive customization options
 * - Production-ready with error handling and logging
 * 
 * @package Typeform_Quizzes
 * @version 1.1.0
 * @author Making The Impact LLC
 * @copyright 2024 Making The Impact LLC
 * @license GPL-2.0-or-later
 */
// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

// Define plugin constants
define('TFQ_PLUGIN_FILE', __FILE__);
define('TFQ_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TFQ_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TFQ_VERSION', filemtime(__FILE__) ?: '1.1.0');

// ---- Phase 9 bootstrap (Composer autoload + fallback) ----
// Prefer Composer autoloader for better performance and PSR-4 compliance
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require __DIR__ . '/vendor/autoload.php';
} elseif ( file_exists( __DIR__ . '/src/autoload.php' ) ) {
    // Fallback to custom autoloader for WordPress installs that can't run Composer
    require __DIR__ . '/src/autoload.php';
} else {
    // No autoloader found - show admin error and return early
    add_action( 'admin_notices', function() {
        echo '<div class="notice notice-error"><p><strong>Typeform Quizzes:</strong> Required autoloader files not found. Please reinstall the plugin.</p></div>';
    });
    return;
}


// Load compatibility layer
require_once TFQ_PLUGIN_DIR . 'src/Support/Compat.php';

// Boot the plugin
MTI\TypeformQuizzes\Plugin::instance()->boot();

/**
 * Check if deprecation notices should be emitted
 * 
 * @since 1.1.0
 * @return bool True if deprecation notices should be shown, false otherwise
 */
function tfq_should_emit_deprecations() {
    return apply_filters( 'tfq_emit_deprecations', true );
}

/**
 * Typeform Quizzes Plugin Main Class
 * 
 * This class handles all functionality for the Typeform Quizzes plugin including:
 * - Custom post type registration and management
 * - Shortcode rendering for individual quizzes and sliders
 * - Admin interface and settings management
 * - Optimized performance with efficient database queries
 * - Advanced security with input validation and rate limiting
 * - Hosting platform compatibility (WP Engine, EasyWP, Kinsta, SiteGround, etc.)
 * - Conflict prevention with scoped CSS and proper Swiper integration
 * - Error handling and logging
 * - Production-ready features
 * 
 * @package Typeform_Quizzes
 * @version 1.1.0
 * @author Making The Impact LLC
 * @since 1.0.0
 */

// Initialize the plugin (modern architecture)
// The plugin is already initialized via MTI\TypeformQuizzes\Plugin::instance()->boot() above

// Register uninstall hook
register_uninstall_hook(__FILE__, 'typeform_quizzes_uninstall');

/**
 * Clean up plugin data on uninstall
 */
function typeform_quizzes_uninstall() {
    global $wpdb;
    
    // Only run if we're actually uninstalling
    if (!defined('WP_UNINSTALL_PLUGIN')) {
        return;
    }
    
    // Log uninstall process
    error_log('Typeform Quizzes: Starting uninstall cleanup process');
    
    try {
        // 1. Remove all custom post type posts
        $quiz_posts = get_posts([
            'post_type' => 'typeform_quiz',
            'post_status' => 'any',
            'numberposts' => -1,
            'fields' => 'ids'
        ]);
        
        if (!empty($quiz_posts)) {
            error_log('Typeform Quizzes: Found ' . count($quiz_posts) . ' quiz posts to delete');
            
            foreach ($quiz_posts as $post_id) {
                // Force delete to bypass trash
                wp_delete_post($post_id, true);
            }
            
            error_log('Typeform Quizzes: Deleted ' . count($quiz_posts) . ' quiz posts');
        }
        
        // 2. Clean up post meta data
        $meta_keys_to_remove = [
            '_typeform_url',
            'typeform_url', 
            'typeform_quiz_url'
        ];
        
        foreach ($meta_keys_to_remove as $meta_key) {
            $deleted_meta = $wpdb->delete(
                $wpdb->postmeta,
                ['meta_key' => $meta_key],
                ['%s']
            );
            
            if ($deleted_meta > 0) {
                error_log('Typeform Quizzes: Deleted ' . $deleted_meta . ' post meta entries for key: ' . $meta_key);
            }
        }
        
        // 3. Remove all plugin options
        $options_to_remove = [
            'typeform_quizzes_defaults',
            'typeform_quiz_order_migrated'
        ];
        
        foreach ($options_to_remove as $option_name) {
            if (delete_option($option_name)) {
                error_log('Typeform Quizzes: Deleted option: ' . $option_name);
            }
        }
        
        // 4. Remove all transients (rate limiting and any other transients)
        $transient_pattern = 'typeform_quiz_*';
        $transients = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . $transient_pattern
            )
        );
        
        $deleted_transients = 0;
        foreach ($transients as $transient) {
            $transient_name = str_replace('_transient_', '', $transient->option_name);
            if (delete_transient($transient_name)) {
                $deleted_transients++;
            }
        }
        
        if ($deleted_transients > 0) {
            error_log('Typeform Quizzes: Deleted ' . $deleted_transients . ' transients');
        }
        
        // 5. Clean up any remaining database entries
        // Remove any orphaned meta data that might remain
        $wpdb->query(
            "DELETE FROM {$wpdb->postmeta} 
             WHERE meta_key LIKE '%typeform%' 
             AND post_id NOT IN (SELECT ID FROM {$wpdb->posts})"
        );
        
        // 6. Clear any cached data
        wp_cache_flush();
        
        error_log('Typeform Quizzes: Uninstall cleanup completed successfully');
        
    } catch (Exception $e) {
        error_log('Typeform Quizzes: Error during uninstall cleanup: ' . $e->getMessage());
    } catch (Error $e) {
        error_log('Typeform Quizzes: Fatal error during uninstall cleanup: ' . $e->getMessage());
    }
}
