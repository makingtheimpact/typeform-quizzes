<?php
/**
 * Health Check System
 * 
 * @package Typeform_Quizzes
 * @version 1.1.0
 * @author Making The Impact LLC
 */

namespace MTI\TypeformQuizzes\Support;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

/**
 * Health Check Class
 * 
 * Provides health monitoring and system validation functionality.
 */
class HealthCheck
{
    /**
     * Initialize health check functionality
     */
    public static function init(): void
    {
        add_action('wp_ajax_tfq_health_check', [__CLASS__, 'ajax_health_check']);
    }

    /**
     * Perform comprehensive health check
     */
    public static function health_check(): array
    {
        $health_status = [
            'status' => 'healthy',
            'checks' => [],
            'timestamp' => current_time('mysql')
        ];
        
        // Check if post type exists
        if (!post_type_exists('typeform_quiz')) {
            $health_status['status'] = 'warning';
            $health_status['checks'][] = 'Post type not registered';
        } else {
            $health_status['checks'][] = 'Post type registered';
        }
        
        // Check if shortcodes are registered
        if (!shortcode_exists('typeform_quizzes_slider')) {
            $health_status['status'] = 'error';
            $health_status['checks'][] = 'Shortcode not registered';
        } else {
            $health_status['checks'][] = 'Shortcode registered';
        }
        
        // Check hosting compatibility
        $hosting = self::check_hosting_compatibility();
        $health_status['checks'][] = 'Hosting platform: ' . $hosting['platform'];
        
        // Check for PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            $health_status['status'] = 'error';
            $health_status['checks'][] = 'PHP version too old: ' . PHP_VERSION;
        } else {
            $health_status['checks'][] = 'PHP version OK: ' . PHP_VERSION;
        }
        
        // Check for WordPress version
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            $health_status['status'] = 'error';
            $health_status['checks'][] = 'WordPress version too old: ' . get_bloginfo('version');
        } else {
            $health_status['checks'][] = 'WordPress version OK: ' . get_bloginfo('version');
        }
        
        // Check for required WordPress functions
        if (!function_exists('wp_enqueue_script') || !function_exists('add_shortcode')) {
            $health_status['status'] = 'error';
            $health_status['checks'][] = 'Required WordPress functions are not available';
        } else {
            $health_status['checks'][] = 'Required WordPress functions available';
        }
        
        // Check for plugin conflicts
        $conflicts = self::check_plugin_conflicts();
        if (!empty($conflicts)) {
            $health_status['status'] = 'warning';
            $health_status['checks'][] = 'Potential conflicts: ' . implode(', ', $conflicts);
        } else {
            $health_status['checks'][] = 'No plugin conflicts detected';
        }
        
        // Check database connectivity
        global $wpdb;
        if (!$wpdb->db_connect()) {
            $health_status['status'] = 'error';
            $health_status['checks'][] = 'Database connection failed';
        } else {
            $health_status['checks'][] = 'Database connection OK';
        }
        
        return $health_status;
    }

    /**
     * Check hosting platform compatibility
     */
    public static function check_hosting_compatibility(): array
    {
        $hosting_info = [
            'platform' => 'Unknown',
            'compatible' => true,
            'features' => []
        ];
        
        // Check for common hosting platforms
        $server_software = $_SERVER['SERVER_SOFTWARE'] ?? '';
        $server_name = $_SERVER['SERVER_NAME'] ?? '';
        
        if (strpos($server_software, 'nginx') !== false) {
            $hosting_info['platform'] = 'Nginx';
            $hosting_info['features'][] = 'Nginx detected';
        } elseif (strpos($server_software, 'Apache') !== false) {
            $hosting_info['platform'] = 'Apache';
            $hosting_info['features'][] = 'Apache detected';
        }
        
        // Check for specific hosting providers
        if (strpos($server_name, 'wpengine.com') !== false) {
            $hosting_info['platform'] = 'WP Engine';
        } elseif (strpos($server_name, 'kinsta.com') !== false) {
            $hosting_info['platform'] = 'Kinsta';
        } elseif (strpos($server_name, 'siteground.com') !== false) {
            $hosting_info['platform'] = 'SiteGround';
        } elseif (strpos($server_name, 'easywp.com') !== false) {
            $hosting_info['platform'] = 'EasyWP';
        }
        
        // Check for object caching
        if (wp_using_ext_object_cache()) {
            $hosting_info['features'][] = 'Object caching enabled';
        }
        
        // Check for CDN
        if (defined('WP_CDN_URL') || defined('CDN_URL')) {
            $hosting_info['features'][] = 'CDN detected';
        }
        
        return $hosting_info;
    }

    /**
     * Check for plugin conflicts
     */
    public static function check_plugin_conflicts(): array
    {
        $conflicts = [];
        
        // Check for known conflicting plugins
        $conflicting_plugins = [
            'swiper-slider/swiper-slider.php' => 'Swiper Slider',
            'swiper-gallery/swiper-gallery.php' => 'Swiper Gallery',
        ];
        
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $active_plugins = get_option('active_plugins', []);
        
        foreach ($conflicting_plugins as $plugin_file => $plugin_name) {
            if (in_array($plugin_file, $active_plugins)) {
                $conflicts[] = $plugin_name;
            }
        }
        
        return $conflicts;
    }

    /**
     * AJAX health check endpoint
     */
    public static function ajax_health_check(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'typeform-quizzes'));
        }
        
        $health_status = self::health_check();
        wp_send_json_success($health_status);
    }
}
