<?php
/**
 * Plugin Name: Typeform Quizzes
 * Plugin URI: https://makingtheimpact.com
 * Description: Professional Typeform Quizzes slider with advanced features. Responsive design, Swiper.js integration, hosting platform compatibility (WP Engine, EasyWP, Kinsta, SiteGround), enhanced security, performance optimization, and comprehensive customization options. Production-ready with conflict prevention and caching.
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

// Require autoloader
require_once TFQ_PLUGIN_DIR . 'src/autoload.php';

// Boot the plugin
MTI\TypeformQuizzes\Plugin::instance()->boot();

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
final class Typeform_Quizzes {
    /** @var string Nonce action for AJAX requests */
    const NONCE_ACTION = 'tfq_purge_nonce_action';
    
    /** @var string Nonce name for AJAX requests */
    const NONCE_NAME   = 'tfq_purge_nonce';
    
    /** @var int Maximum number of quizzes that can be displayed */
    const MAX_QUIZZES_LIMIT = 50;
    
    /** @var array Error messages for admin display */
    private static $errors = [];
    
    /**
     * Initialize the plugin
     * 
     * Sets up all hooks, actions, and filters needed for the plugin to function.
     * Includes version checks, text domain loading, and feature registration.
     * 
     * @since 1.0.0
     * @return void
     */
    public static function init() {
        // Check PHP version compatibility
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            add_action('admin_notices', [__CLASS__, 'php_version_notice']);
            return;
        }
        
        // Check WordPress version compatibility
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            add_action('admin_notices', [__CLASS__, 'wp_version_notice']);
            return;
        }
        
        // Add security headers for admin pages
        add_action('admin_init', [__CLASS__, 'add_security_headers']);
        
        // Check for plugin conflicts
        add_action('admin_init', [__CLASS__, 'check_plugin_conflicts']);
        
        // Validate environment
        add_action('admin_init', [__CLASS__, 'validate_environment']);
        
        // Load text domain for internationalization
        add_action('init', [__CLASS__, 'load_textdomain']);
        
        // Initialize plugin
        add_action('init', [__CLASS__, 'register_post_type']);
        add_action('add_meta_boxes', [__CLASS__, 'add_meta_boxes']);
        add_action('save_post', [__CLASS__, 'save_meta_boxes'], 10, 1);
        add_action('admin_menu', [__CLASS__, 'add_tools_page']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);
        
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_scripts']);
        add_shortcode('typeform_quiz', [__CLASS__, 'render_typeform_quiz']);
        add_shortcode('typeform_quizzes_slider', [__CLASS__, 'render_quizzes_slider']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_action('admin_init', [__CLASS__, 'handle_settings_save']);
        
        // Add activation/deactivation hooks
        register_activation_hook(__FILE__, [__CLASS__, 'activate']);
        register_deactivation_hook(__FILE__, [__CLASS__, 'deactivate']);
        
        // Add error logging
        add_action('admin_notices', [__CLASS__, 'display_errors']);
        
        // Add admin notices for quiz management
        add_action('admin_notices', [__CLASS__, 'display_quiz_notices']);
    }
    
    /**
     * Load plugin text domain for internationalization
     */
    /**
     * Add security headers for admin pages
     */
    public static function add_security_headers() {
        if (is_admin() && current_user_can('manage_options')) {
            // Prevent clickjacking
            header('X-Frame-Options: SAMEORIGIN');
            // Prevent MIME type sniffing
            header('X-Content-Type-Options: nosniff');
            // Enable XSS protection
            header('X-XSS-Protection: 1; mode=block');
            // Referrer policy
            header('Referrer-Policy: strict-origin-when-cross-origin');
        }
    }

    /**
     * Check for potential plugin conflicts
     */
    public static function check_plugin_conflicts() {
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        $conflicts = [];
        
        // Check for known conflicting plugins
        $conflicting_plugins = [
            'swiper-slider/swiper-slider.php' => 'Swiper Slider',
            'swiper-gallery/swiper-gallery.php' => 'Swiper Gallery',
        ];
        
        foreach ($conflicting_plugins as $plugin_file => $plugin_name) {
            if (is_plugin_active($plugin_file)) {
                $conflicts[] = $plugin_name;
            }
        }
        
        if (!empty($conflicts)) {
            add_action('admin_notices', function() use ($conflicts) {
                $conflict_list = implode(', ', $conflicts);
                echo '<div class="notice notice-warning is-dismissible">';
                echo '<p><strong>' . esc_html__('Typeform Quizzes:', 'typeform-quizzes') . '</strong> ' . esc_html__('Potential conflicts detected with:', 'typeform-quizzes') . ' ' . esc_html($conflict_list) . '. ';
                echo esc_html__('Please test your quiz sliders thoroughly after any updates.', 'typeform-quizzes') . '</p>';
                echo '</div>';
            });
        }
    }

    /**
     * Validate plugin environment
     */
    public static function validate_environment() {
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        $issues = [];
        
        // Check memory limit
        $memory_limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
        if ($memory_limit < 128 * MB_IN_BYTES) {
            $issues[] = 'Low memory limit detected. Consider increasing PHP memory_limit to at least 128M for optimal performance.';
        }
        
        // Check if cURL is available
        if (!function_exists('curl_init')) {
            $issues[] = 'cURL extension is not available. This may affect external resource loading.';
        }
        
        // Check if JSON functions are available
        if (!function_exists('json_encode') || !function_exists('json_decode')) {
            $issues[] = 'JSON functions are not available. This will prevent the plugin from working properly.';
        }
        
        // Check for required WordPress functions
        if (!function_exists('wp_enqueue_script') || !function_exists('add_shortcode')) {
            $issues[] = 'Required WordPress functions are not available. Please check your WordPress installation.';
        }
        
        if (!empty($issues)) {
            add_action('admin_notices', function() use ($issues) {
                echo '<div class="notice notice-error is-dismissible">';
                echo '<p><strong>' . esc_html__('Typeform Quizzes - Environment Issues Detected:', 'typeform-quizzes') . '</strong></p>';
                echo '<ul>';
                foreach ($issues as $issue) {
                    echo '<li>' . esc_html($issue) . '</li>';
                }
                echo '</ul>';
                echo '</div>';
            });
        }
    }

    public static function load_textdomain() {
        load_plugin_textdomain('typeform-quizzes', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
    
    /**
     * Register custom post type for Typeform Quizzes
     */
    public static function register_post_type() {
        $labels = [
            'name' => 'Typeform Quizzes',
            'singular_name' => 'Typeform Quiz',
            'menu_name' => 'Typeform Quizzes',
            'add_new' => 'Add New Quiz',
            'add_new_item' => 'Add New Typeform Quiz',
            'edit_item' => 'Edit Typeform Quiz',
            'new_item' => 'New Typeform Quiz',
            'view_item' => 'View Typeform Quiz',
            'search_items' => 'Search Quizzes',
            'not_found' => 'No quizzes found',
            'not_found_in_trash' => 'No quizzes found in trash',
            'all_items' => 'All Quizzes',
        ];

        $args = [
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'typeform-quiz'],
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 20,
            'menu_icon' => 'dashicons-forms',
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'page-attributes'],
            'show_in_rest' => true,
        ];

        register_post_type('typeform_quiz', $args);
        
        // Add custom columns to the admin list view
        add_filter('manage_typeform_quiz_posts_columns', [__CLASS__, 'add_admin_columns']);
        add_action('manage_typeform_quiz_posts_custom_column', [__CLASS__, 'populate_admin_columns'], 10, 2);
        add_filter('manage_edit-typeform_quiz_sortable_columns', [__CLASS__, 'make_columns_sortable']);
        
        // Add reorder functionality
        add_action('restrict_manage_posts', [__CLASS__, 'add_reorder_button']);
        add_action('admin_footer', [__CLASS__, 'add_reorder_modal']);
        add_action('wp_ajax_typeform_quiz_reorder', [__CLASS__, 'handle_reorder_ajax']);
        
        // Migrate existing order data on admin init
        add_action('admin_init', [__CLASS__, 'migrate_order_data']);
        
        // Ensure admin list uses correct ordering
        add_action('pre_get_posts', [__CLASS__, 'admin_list_ordering']);
    }
    
    /**
     * Add custom columns to quiz admin list
     */
    public static function add_admin_columns($columns) {
        if (!current_user_can('edit_posts')) {
            return $columns;
        }
        $new_columns = [];
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['typeform_url'] = 'Typeform URL';
        $new_columns['quiz_order'] = 'Order';
        $new_columns['featured_image'] = 'Thumbnail';
        $new_columns['date'] = $columns['date'];
        
        return $new_columns;
    }
    
    /**
     * Populate custom admin columns
     */
    public static function populate_admin_columns($column, $post_id) {
        if (!current_user_can('edit_posts')) {
            return;
        }
        switch ($column) {
            case 'typeform_url':
                $url = get_post_meta($post_id, '_typeform_url', true);
                if ($url) {
                    echo '<a href="' . esc_url($url) . '" target="_blank" style="color: #0073aa;">' . esc_html($url) . '</a>';
                } else {
                    echo '<span style="color: #999;">' . esc_html__('No URL set', 'typeform-quizzes') . '</span>';
                }
                break;
                
            case 'quiz_order':
                $menu_order = get_post($post_id)->menu_order;
                $meta_order = get_post_meta($post_id, '_quiz_order', true);
                // Show menu_order if available, otherwise show meta field
                $order = $menu_order ?: $meta_order;
                echo esc_html($order ?: '0');
                break;
                
            case 'featured_image':
                if (has_post_thumbnail($post_id)) {
                    echo wp_kses(get_the_post_thumbnail($post_id, [50, 50], ['style' => 'border-radius: 4px;']), [
                        'img' => [
                            'src' => [],
                            'alt' => [],
                            'width' => [],
                            'height' => [],
                            'style' => []
                        ]
                    ]);
                } else {
                    echo wp_kses('<div style="width: 50px; height: 50px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 4px; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px;">📝</div>', [
                        'div' => [
                            'style' => []
                        ]
                    ]);
                }
                break;
        }
    }
    
    /**
     * Make admin columns sortable
     */
    public static function make_columns_sortable($columns) {
        $columns['quiz_order'] = 'menu_order';
        return $columns;
    }
    
    /**
     * Add reorder button to admin list page
     */
    public static function add_reorder_button() {
        global $typenow;
        if ($typenow === 'typeform_quiz' && current_user_can('edit_posts')) {
            echo '<button type="button" id="reorder-quizzes-btn" class="button button-secondary" style="margin-left: 10px;">
                <span class="dashicons dashicons-sort" style="vertical-align: middle; margin-right: 5px;"></span>
                ' . esc_html__('Change Order', 'typeform-quizzes') . '
            </button>';
        }
    }
    
    /**
     * Add reorder modal to admin footer
     */
    public static function add_reorder_modal() {
        global $typenow;
        if ($typenow === 'typeform_quiz' && current_user_can('edit_posts')) {
            ?>
            <div id="typeform-quizzes-reorder-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 999999;">
                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 8px; padding: 0; min-width: 700px; max-width: 95%; max-height: 90%; display: flex; flex-direction: column; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
                    <div style="padding: 20px; border-bottom: 1px solid #ddd; flex-shrink: 0;">
                        <h2 style="margin: 0 0 10px 0; color: #0073aa; font-size: 20px;">Reorder Quizzes</h2>
                        <p style="margin: 0; color: #666; font-size: 14px;">Drag and drop the quizzes below to reorder them. The order will be saved when you click "Save Order".</p>
                    </div>
                    <div id="reorder-list" style="flex: 1; overflow-y: auto; padding: 15px; min-height: 400px; max-height: 60vh;">
                        <div style="text-align: center; padding: 50px; color: #999;">
                            <span class="dashicons dashicons-update" style="font-size: 24px; margin-right: 10px; animation: spin 1s linear infinite;"></span>
                            Loading quizzes...
                        </div>
                    </div>
                    <div style="padding: 20px; border-top: 1px solid #ddd; background: #f9f9f9; border-radius: 0 0 8px 8px; flex-shrink: 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div id="quiz-count" style="color: #666; font-size: 14px;"></div>
                            <div>
                                <button type="button" id="typeform-quizzes-close-reorder-modal" class="button">Close</button>
                                <button type="button" id="typeform-quizzes-save-reorder" class="button button-primary" style="margin-left: 10px;">Save Order</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <style>
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            </style>
            <?php
        }
    }
    
    /**
     * Validate AJAX request for security
     */
    private static function validate_ajax_request() {
        // Check if request is AJAX
        if (!wp_doing_ajax()) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Typeform Quizzes AJAX: Not an AJAX request');
            }
            wp_send_json_error(__('Invalid request method', 'typeform-quizzes'));
            return false;
        }
        
        // Check if nonce exists before validating
        if (!isset($_POST['nonce'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Typeform Quizzes AJAX: Missing nonce');
            }
            wp_send_json_error(__('Missing security nonce', 'typeform-quizzes'));
            return false;
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'typeform_quiz_reorder')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Typeform Quizzes AJAX: Invalid nonce');
            }
            wp_send_json_error(__('Invalid security nonce', 'typeform-quizzes'));
            return false;
        }
        
        if (!current_user_can('edit_posts')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Typeform Quizzes AJAX: Insufficient permissions for user: ' . get_current_user_id());
            }
            wp_send_json_error(__('Insufficient permissions', 'typeform-quizzes'));
            return false;
        }
        
        // Rate limiting check
        if (!self::check_rate_limit()) {
            wp_send_json_error(__('Rate limit exceeded', 'typeform-quizzes'));
            return false;
        }
        
        return true;
    }
    
    /**
     * Check rate limiting for AJAX requests
     */
    private static function check_rate_limit() {
        $user_id = get_current_user_id();
        $transient_key = 'tfq_ajax_rate_limit_' . $user_id;
        $requests = get_transient($transient_key) ?: 0;
        
        // Allow 10 requests per minute
        if ($requests >= 10) {
            return false;
        }
        
        set_transient($transient_key, $requests + 1, 60);
        return true;
    }
    
    /**
     * Sanitize shortcode attributes
     */
    private static function sanitize_shortcode_attributes($atts) {
        $sanitized = [];
        
        foreach ($atts as $key => $value) {
            $key = sanitize_key($key);
            
            switch ($key) {
                case 'max':
                case 'max_width':
                case 'thumb_height':
                case 'cols_desktop':
                case 'cols_tablet':
                case 'cols_mobile':
                case 'gap':
                case 'border_radius':
                case 'controls_spacing':
                case 'controls_spacing_tablet':
                case 'controls_bottom_spacing':
                case 'arrow_border_radius':
                case 'arrow_padding':
                case 'arrow_width':
                case 'arrow_height':
                case 'arrow_icon_size':
                case 'pagination_dot_size':
                case 'pagination_dot_gap':
                    $sanitized[$key] = absint($value);
                    break;
                    
                case 'title_color':
                case 'title_hover_color':
                case 'arrow_bg_color':
                case 'arrow_hover_bg_color':
                case 'arrow_icon_color':
                case 'arrow_icon_hover_color':
                case 'pagination_dot_color':
                case 'pagination_active_dot_color':
                case 'active_slide_border_color':
                    $sanitized[$key] = sanitize_hex_color($value);
                    break;
                    
                case 'center_on_click':
                case 'darken_inactive_slides':
                    $sanitized[$key] = rest_sanitize_boolean($value);
                    break;
                    
                case 'order':
                    $allowed_orders = ['menu_order', 'date', 'title', 'rand'];
                    $sanitized[$key] = in_array($value, $allowed_orders, true) ? $value : 'menu_order';
                    break;
                    
                default:
                    $sanitized[$key] = sanitize_text_field($value);
                    break;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Handle AJAX request for reordering
     */
    public static function handle_reorder_ajax() {
        // Enable error logging for debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Typeform Quizzes AJAX: Starting handle_reorder_ajax');
        }
        
        try {
            // Enhanced security checks
            if (!self::validate_ajax_request()) {
                return;
            }
            
            $action = sanitize_text_field($_POST['action_type'] ?? '');
            
            // Validate action type
            $allowed_actions = ['get_quizzes', 'update_order', 'save_order'];
            if (!in_array($action, $allowed_actions, true)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Typeform Quizzes AJAX: Invalid action type: ' . $action);
                }
                wp_send_json_error(__('Invalid action type', 'typeform-quizzes'));
            }
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Typeform Quizzes AJAX: Action type: ' . $action);
            }
        
        if ($action === 'get_quizzes') {
            $quizzes = get_posts([
                'post_type' => 'typeform_quiz',
                'post_status' => 'publish',
                'numberposts' => -1,
                'orderby' => 'menu_order',
                'order' => 'ASC'
            ]);
            
            $quiz_data = [];
            foreach ($quizzes as $quiz) {
                $quiz_data[] = [
                    'id' => $quiz->ID,
                    'title' => $quiz->post_title,
                    'menu_order' => $quiz->menu_order,
                    'thumbnail' => get_the_post_thumbnail_url($quiz->ID, [50, 50]) ?: ''
                ];
            }
            
            wp_send_json_success($quiz_data);
        }
        
        if ($action === 'save_order') {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Typeform Quizzes AJAX: Processing save_order action');
            }
            
            // Check if order_data exists
            if (!isset($_POST['order_data'])) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Typeform Quizzes AJAX: Missing order_data');
                }
                wp_send_json_error(__('Missing order data', 'typeform-quizzes'));
            }
            
            $order_data = json_decode(stripslashes($_POST['order_data']), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Typeform Quizzes AJAX: JSON decode error: ' . json_last_error_msg());
                }
                wp_send_json_error(__('Invalid JSON data: ', 'typeform-quizzes') . json_last_error_msg());
            }
            
            if (!is_array($order_data)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Typeform Quizzes AJAX: Order data is not an array: ' . print_r($order_data, true));
                }
                wp_send_json_error(__('Invalid order data format', 'typeform-quizzes'));
            }
            
            if (empty($order_data)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Typeform Quizzes AJAX: Empty order data');
                }
                wp_send_json_error(__('No order data provided', 'typeform-quizzes'));
            }
            
            global $wpdb;
            
            // Check database connection
            if (!$wpdb->db_connect()) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Typeform Quizzes AJAX: Database connection failed');
                }
                wp_send_json_error(__('Database connection failed', 'typeform-quizzes'));
            }
            
            // Prepare bulk update query
            $update_cases = [];
            $quiz_ids = [];
            
            foreach ($order_data as $index => $quiz_id) {
                $quiz_id = intval($quiz_id);
                // Stricter validation for quiz IDs
                if ($quiz_id <= 0 || $quiz_id > 999999) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('Typeform Quizzes AJAX: Invalid quiz ID: ' . $quiz_id);
                    }
                    wp_send_json_error(__('Invalid quiz ID in order data', 'typeform-quizzes'));
                }
                
                // Verify the quiz actually exists
                $quiz_exists = get_post($quiz_id);
                if (!$quiz_exists || $quiz_exists->post_type !== 'typeform_quiz') {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('Typeform Quizzes AJAX: Quiz does not exist: ' . $quiz_id);
                    }
                    wp_send_json_error(__('Quiz not found in order data', 'typeform-quizzes'));
                }
                
                $quiz_ids[] = $quiz_id;
                $update_cases[] = $wpdb->prepare("WHEN %d THEN %d", $quiz_id, $index);
            }
            
            if (empty($update_cases)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Typeform Quizzes AJAX: No valid quiz IDs to update');
                }
                wp_send_json_error(__('No valid quiz IDs to update', 'typeform-quizzes'));
            }
            
            // Use simple bulk update without transactions
            $case_sql = implode(' ', $update_cases);
            $ids_sql = implode(',', array_map('intval', $quiz_ids));
            
            $query = "
                UPDATE {$wpdb->posts} 
                SET menu_order = CASE ID {$case_sql} END 
                WHERE ID IN ({$ids_sql}) AND post_type = 'typeform_quiz'
            ";
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Typeform Quizzes AJAX: Executing query: ' . $query);
                error_log('Typeform Quizzes AJAX: Order data: ' . print_r($order_data, true));
                error_log('Typeform Quizzes AJAX: Quiz IDs: ' . print_r($quiz_ids, true));
            }
            
            $result = $wpdb->query($query);
            
            if ($result === false) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Typeform Quizzes AJAX: Query failed: ' . $wpdb->last_error);
                }
                wp_send_json_error(__('Failed to update quiz order: ', 'typeform-quizzes') . $wpdb->last_error);
            }
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Typeform Quizzes AJAX: Updated ' . $result . ' rows');
                
                // Verify the update worked by checking the actual menu_order values
                $verification_query = "SELECT ID, post_title, menu_order FROM {$wpdb->posts} WHERE ID IN (" . implode(',', array_map('intval', $quiz_ids)) . ") AND post_type = 'typeform_quiz' ORDER BY menu_order ASC";
                $verification_results = $wpdb->get_results($verification_query);
                error_log('Typeform Quizzes AJAX: Verification - Current menu_order values: ' . print_r($verification_results, true));
            }
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Typeform Quizzes AJAX: Order saved successfully');
            }
            
            wp_send_json_success('Order saved successfully');
        }
        
        wp_send_json_error(__('Invalid action: ', 'typeform-quizzes') . $action);
        
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Typeform Quizzes AJAX: Fatal error: ' . $e->getMessage());
                error_log('Typeform Quizzes AJAX: Stack trace: ' . $e->getTraceAsString());
            }
            wp_send_json_error(__('Error: ', 'typeform-quizzes') . $e->getMessage());
        } catch (Error $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Typeform Quizzes AJAX: Fatal PHP error: ' . $e->getMessage());
                error_log('Typeform Quizzes AJAX: Stack trace: ' . $e->getTraceAsString());
            }
            wp_send_json_error(__('Fatal error: ', 'typeform-quizzes') . $e->getMessage());
        }
    }
    
    

    /**
     * Migrate existing order data from meta field to menu_order
     */
    public static function migrate_order_data() {
        // Only run once
        if (get_option('typeform_quiz_order_migrated')) {
            return;
        }
        
        // Only run for admin users
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }
        
        $quizzes = get_posts([
            'post_type' => 'typeform_quiz',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query' => [
                [
                    'key' => '_quiz_order',
                    'compare' => 'EXISTS'
                ]
            ]
        ]);
        
        foreach ($quizzes as $quiz) {
            $order = get_post_meta($quiz->ID, '_quiz_order', true);
            if ($order !== '' && $quiz->menu_order == 0) {
                wp_update_post([
                    'ID' => $quiz->ID,
                    'menu_order' => intval($order)
                ]);
            }
        }
        
        // Mark migration as complete
        update_option('typeform_quiz_order_migrated', true);
    }
    
    /**
     * Ensure admin list uses correct ordering
     */
    public static function admin_list_ordering($query) {
        if (!is_admin() || !$query->is_main_query() || !current_user_can('edit_posts')) {
            return;
        }
        
        // Sanitize and validate post_type parameter
        $post_type = isset($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : '';
        if ($post_type === 'typeform_quiz') {
            // If no specific orderby is set, use menu_order
            $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : '';
            if (empty($orderby)) {
                $query->set('orderby', 'menu_order');
                $query->set('order', 'ASC');
            }
        }
    }
    
    /**
     * Add meta boxes for quiz settings
     */
    public static function add_meta_boxes() {
        if (!current_user_can('edit_posts')) {
            return;
        }
        
        add_meta_box(
            'typeform_quiz_settings',
            'Quiz Settings',
            [__CLASS__, 'render_meta_box'],
            'typeform_quiz',
            'normal',
            'high'
        );
    }
    
    /**
     * Render meta box content
     */
    public static function render_meta_box($post) {
        if (!current_user_can('edit_posts')) {
            return;
        }
        
        wp_nonce_field('typeform_quiz_meta_box', 'typeform_quiz_meta_box_nonce');
        
        $typeform_url = get_post_meta($post->ID, '_typeform_url', true);
        $quiz_order = get_post_meta($post->ID, '_quiz_order', true);
        
        echo '<table class="form-table">';
        echo '<tr>';
        echo '<th><label for="typeform_url">' . esc_html__('Typeform URL', 'typeform-quizzes') . '</label></th>';
        echo '<td><input type="url" id="typeform_url" name="typeform_url" value="' . esc_attr($typeform_url) . '" class="regular-text" placeholder="https://form.typeform.com/to/...">';
        echo '<p class="description">' . esc_html__('Enter the full Typeform URL for this quiz.', 'typeform-quizzes') . '</p></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th><label for="quiz_order">' . esc_html__('Display Order', 'typeform-quizzes') . '</label></th>';
        echo '<td><input type="number" id="quiz_order" name="quiz_order" value="' . esc_attr($quiz_order ?: 0) . '" class="small-text" min="0">';
        echo '<p class="description">' . esc_html__('Lower numbers appear first in the slider (0 = first).', 'typeform-quizzes') . ' <strong>' . esc_html__('Tip:', 'typeform-quizzes') . '</strong> ' . esc_html__('Use the "Change Order" button on the quizzes list page for easier reordering with drag & drop.', 'typeform-quizzes') . '</p></td>';
        echo '</tr>';
        echo '</table>';
    }
    
    /**
     * Save meta box data
     */
    public static function save_meta_boxes($post_id) {
        try {
            // Prevent multiple saves
            if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
                return;
            }
            
            // Check if this is the correct post type
            if (get_post_type($post_id) !== 'typeform_quiz') {
                return;
            }
            
            if (!isset($_POST['typeform_quiz_meta_box_nonce']) || 
                !wp_verify_nonce($_POST['typeform_quiz_meta_box_nonce'], 'typeform_quiz_meta_box')) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Typeform Quizzes: Invalid or missing nonce for post ' . $post_id);
                }
                return;
            }

            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }

            if (!current_user_can('edit_post', $post_id)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Typeform Quizzes: User lacks permission to edit post ' . $post_id);
                }
                return;
            }
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Typeform Quizzes: Saving meta boxes for post ' . $post_id);
            }

        if (isset($_POST['typeform_url'])) {
            $original_url = trim($_POST['typeform_url']);
            
            if (!empty($original_url)) {
                // Use esc_url_raw for better URL sanitization
                $sanitized_url = esc_url_raw($original_url);
                
                // Fallback to manual sanitization if esc_url_raw returns empty
                if (empty($sanitized_url)) {
                    $sanitized_url = filter_var($original_url, FILTER_SANITIZE_URL);
                }
                
                // If still empty, use the original URL with basic sanitization
                if (empty($sanitized_url)) {
                    $sanitized_url = sanitize_text_field($original_url);
                }
                
                $meta_result = update_post_meta($post_id, '_typeform_url', $sanitized_url);
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Typeform Quizzes: Updated typeform_url for post ' . $post_id . ' - Result: ' . ($meta_result ? 'success' : 'failed'));
                }
            } else {
                // If URL is empty, delete the meta
                $delete_result = delete_post_meta($post_id, '_typeform_url');
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Typeform Quizzes: Deleted typeform_url for post ' . $post_id . ' - Result: ' . ($delete_result ? 'success' : 'failed'));
                }
            }
        }

        if (isset($_POST['quiz_order'])) {
            $order_value = intval($_POST['quiz_order']);
            // Validate order value is within reasonable bounds
            if ($order_value < 0 || $order_value > 9999) {
                $order_value = 0; // Reset to default if invalid
            }
            update_post_meta($post_id, '_quiz_order', $order_value);
            
            // Only update menu_order if the post exists and the value is different
            $current_post = get_post($post_id);
            if ($current_post && $current_post->menu_order != $order_value) {
                $update_result = wp_update_post([
                    'ID' => $post_id,
                    'menu_order' => $order_value
                ]);
                
                // Check for errors in wp_update_post
                if (is_wp_error($update_result)) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('Typeform Quizzes: Error updating menu_order for post ' . $post_id . ': ' . $update_result->get_error_message());
                    }
                } elseif ($update_result === 0) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('Typeform Quizzes: wp_update_post returned 0 for post ' . $post_id . ' - post may not exist or no changes made');
                    }
                } else {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('Typeform Quizzes: Successfully updated menu_order for post ' . $post_id . ' to ' . $order_value);
                    }
                }
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Typeform Quizzes: Skipping menu_order update for post ' . $post_id . ' - post not found or value unchanged');
                }
            }
        }
        
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Typeform Quizzes: Fatal error in save_meta_boxes for post ' . $post_id . ': ' . $e->getMessage());
                error_log('Typeform Quizzes: Stack trace: ' . $e->getTraceAsString());
            }
        } catch (Error $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Typeform Quizzes: Fatal PHP error in save_meta_boxes for post ' . $post_id . ': ' . $e->getMessage());
                error_log('Typeform Quizzes: Stack trace: ' . $e->getTraceAsString());
            }
        }
    }
    
    /**
     * Plugin activation hook
     * 
     * Sets up default options and flushes rewrite rules on plugin activation.
     * 
     * @since 1.0.0
     * @return void
     */
    public static function activate() {
        // Set default options if they don't exist
        if (!get_option('typeform_quizzes_defaults')) {
            $defaults = [
                'max' => 20,
                'max_width' => 1450,
                'thumb_height' => '200',
                'cols_desktop' => 6,
                'cols_tablet' => 3,
                'cols_mobile' => 2,
                'gap' => 20,
                'center_on_click' => true,
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
        
        // Register the post type to ensure it's available
        self::register_post_type();
        
        
        // Flush rewrite rules to register custom post type URLs
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation hook
     * 
     * Flushes rewrite rules on plugin deactivation.
     * 
     * @since 1.0.0
     * @return void
     */
    public static function deactivate() {
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Display PHP version notice
     */
    public static function php_version_notice() {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>' . esc_html__('Typeform Quizzes:', 'typeform-quizzes') . '</strong> ' . esc_html__('This plugin requires PHP 7.4 or higher. Your current version is', 'typeform-quizzes') . ' ' . PHP_VERSION . '.';
        echo '</p></div>';
    }
    
    /**
     * Display WordPress version notice
     */
    public static function wp_version_notice() {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>' . esc_html__('Typeform Quizzes:', 'typeform-quizzes') . '</strong> ' . esc_html__('This plugin requires WordPress 5.0 or higher. Your current version is', 'typeform-quizzes') . ' ' . get_bloginfo('version') . '.';
        echo '</p></div>';
    }
    
    /**
     * Log errors for display
     */
    private static function log_error($message) {
        $log_message = 'Typeform Quizzes: ' . $message;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log($log_message);
        }
        
        // Log to WordPress debug log if available
        if (function_exists('wp_debug_log')) {
            wp_debug_log($log_message);
        }
        
        // Store error for admin display
        self::$errors[] = $message;
        
        // Log to custom log file if WP_DEBUG_LOG is enabled
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            $log_file = WP_CONTENT_DIR . '/debug.log';
            if (is_writable($log_file)) {
                file_put_contents($log_file, '[' . current_time('mysql') . '] ' . $log_message . "\n", FILE_APPEND | LOCK_EX);
            }
        }
    }
    
    /**
     * Display logged errors
     */
    public static function display_errors() {
        if (!empty(self::$errors) && current_user_can('manage_options')) {
            foreach (self::$errors as $error) {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error) . '</p></div>';
            }
        }
    }
    
    /**
     * Health check for production monitoring
     */
    public static function health_check() {
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
        
        // Check for errors
        if (!empty(self::$errors)) {
            $health_status['status'] = 'error';
            $health_status['checks'][] = 'Errors detected: ' . count(self::$errors);
        }
        
        return $health_status;
    }
    
    /**
     * Display quiz management notices
     */
    public static function display_quiz_notices() {
        if (!current_user_can('edit_posts')) {
            return;
        }
        
        $screen = get_current_screen();
        
        // Only show on quiz admin pages
        if (!$screen || $screen->post_type !== 'typeform_quiz') {
            return;
        }
        
        // Check if there are any quizzes
        $quiz_count = wp_count_posts('typeform_quiz');
        $total_quizzes = $quiz_count->publish + $quiz_count->draft + $quiz_count->private;
        
        if ($total_quizzes === 0 && $screen->id === 'edit-typeform_quiz') {
            echo '<div class="notice notice-info is-dismissible">';
            echo '<p><strong>' . esc_html__('Welcome to Typeform Quizzes!', 'typeform-quizzes') . '</strong> ' . esc_html__('You haven\'t created any quizzes yet.', 'typeform-quizzes') . ' ';
            echo '<a href="' . admin_url('post-new.php?post_type=typeform_quiz') . '" class="button button-primary" style="margin-left: 10px;">' . esc_html__('Create Your First Quiz', 'typeform-quizzes') . '</a>';
            echo '</p></div>';
        }
    }

    public static function add_tools_page() {
        add_submenu_page(
            'edit.php?post_type=typeform_quiz',  // parent slug
            'Typeform Quizzes Settings',          // page title
            'Settings',                           // menu title
            'manage_options',                     // capability
            'typeform-quizzes-tools',          // slug
            [__CLASS__, 'render_page']
        );
    }

    public static function register_settings() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        register_setting('typeform_quizzes_defaults_options', 'typeform_quizzes_defaults', [
            'type' => 'array',
            'sanitize_callback' => [__CLASS__, 'sanitize_defaults'],
            'default' => []
        ]);
    }
    
    
    /**
     * Sanitize CSS dimension value (width, height, etc.)
     * 
     * Validates and sanitizes CSS dimension values to prevent XSS attacks
     * and ensure proper formatting. Supports common CSS units.
     * 
     * @since 1.0.2
     * @param string $value The dimension value to sanitize
     * @param string $default Default value if sanitization fails
     * @return string Sanitized dimension value
     */
    private static function sanitize_css_dimension($value, $default = '100%') {
        if (empty($value)) {
            return $default;
        }
        
        // Allow common CSS units and percentages
        if (preg_match('/^(\d+(?:\.\d+)?)(px|%|em|rem|vh|vw)$/', $value, $matches)) {
            return $value;
        }
        
        // If it's just a number, assume pixels
        if (is_numeric($value)) {
            return $value . 'px';
        }
        
        return $default;
    }
    
    /**
     * Validate Typeform URL format
     * 
     * Checks if a URL is a valid Typeform URL that can be embedded.
     * Validates both the URL format and the domain.
     * 
     * @since 1.0.2
     * @param string $url The URL to validate
     * @return bool True if valid Typeform URL, false otherwise
     */
    private static function is_valid_typeform_url($url) {
        if (empty($url)) {
            return false;
        }
        
        // Check if it's a valid URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        // Check if it's a Typeform URL
        $parsed = parse_url($url);
        return isset($parsed['host']) && (
            strpos($parsed['host'], 'typeform.com') !== false ||
            strpos($parsed['host'], 't.typeform.com') !== false
        );
    }
    
    /**
     * Render error message with consistent styling
     * 
     * Creates a standardized error message display for users.
     * Uses consistent styling and proper escaping.
     * 
     * @since 1.0.2
     * @param string $message The error message to display
     * @return string HTML for the error message
     */
    private static function render_error($message) {
        return '<div class="typeform-quizzes-error typeform-quizzes-error-message">' . 
               esc_html($message) . 
               '</div>';
    }
    
    /**
     * Sanitize hex color value
     */
    private static function sanitize_hex_color($color) {
        if (empty($color)) {
            return '#111111';
        }
        
        // Remove any non-hex characters
        $color = preg_replace('/[^0-9a-fA-F]/', '', $color);
        
        // Ensure it's a valid hex color
        if (strlen($color) === 6) {
            return '#' . $color;
        } elseif (strlen($color) === 3) {
            return '#' . $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2];
        }
        
        return '#111111';
    }

    /**
     * Sanitize and validate default settings
     */
    public static function sanitize_defaults($input) {
        if (!current_user_can('manage_options')) {
            return [];
        }
        
        if (!is_array($input)) {
            return [];
        }
        
        $sanitized = [];
        
        // Maximum quizzes - stricter validation
        $max_value = isset($input['max']) ? intval($input['max']) : 20;
        if ($max_value < 1 || $max_value > self::MAX_QUIZZES_LIMIT) {
            $max_value = 20; // Reset to default if invalid
        }
        $sanitized['max'] = $max_value;
        
        // Max width - stricter validation
        $max_width_value = isset($input['max_width']) ? intval($input['max_width']) : 1450;
        if ($max_width_value < 200 || $max_width_value > 2000) {
            $max_width_value = 1450; // Reset to default if invalid
        }
        $sanitized['max_width'] = $max_width_value;
        
        // Thumbnail height - stricter validation
        $thumb_height_value = isset($input['thumb_height']) ? intval($input['thumb_height']) : 200;
        if ($thumb_height_value < 50 || $thumb_height_value > 1000) {
            $thumb_height_value = 200; // Reset to default if invalid
        }
        $sanitized['thumb_height'] = $thumb_height_value;
        
        // Columns - stricter validation
        $cols_desktop_value = isset($input['cols_desktop']) ? intval($input['cols_desktop']) : 6;
        if ($cols_desktop_value < 1 || $cols_desktop_value > 12) {
            $cols_desktop_value = 6; // Reset to default if invalid
        }
        $sanitized['cols_desktop'] = $cols_desktop_value;
        
        $cols_tablet_value = isset($input['cols_tablet']) ? intval($input['cols_tablet']) : 3;
        if ($cols_tablet_value < 1 || $cols_tablet_value > 8) {
            $cols_tablet_value = 3; // Reset to default if invalid
        }
        $sanitized['cols_tablet'] = $cols_tablet_value;
        
        $cols_mobile_value = isset($input['cols_mobile']) ? intval($input['cols_mobile']) : 2;
        if ($cols_mobile_value < 1 || $cols_mobile_value > 4) {
            $cols_mobile_value = 2; // Reset to default if invalid
        }
        $sanitized['cols_mobile'] = $cols_mobile_value;
        
        // Gap - stricter validation
        $gap_value = isset($input['gap']) ? intval($input['gap']) : 20;
        if ($gap_value < 0 || $gap_value > 100) {
            $gap_value = 20; // Reset to default if invalid
        }
        $sanitized['gap'] = $gap_value;
        
        // Center on click
        $sanitized['center_on_click'] = isset($input['center_on_click']) ? 
            (bool) $input['center_on_click'] : true;
        
        // Order
        $sanitized['order'] = isset($input['order']) && in_array($input['order'], ['menu_order', 'date', 'title', 'rand']) ? 
            $input['order'] : 'menu_order';
        
        // Style controls
        $sanitized['border_radius'] = isset($input['border_radius']) ? 
            min(max(intval($input['border_radius']), 0), 50) : 16;
        
        $sanitized['title_color'] = isset($input['title_color']) ? 
            self::sanitize_hex_color($input['title_color']) : '#000000';
        
        $sanitized['title_hover_color'] = isset($input['title_hover_color']) ? 
            self::sanitize_hex_color($input['title_hover_color']) : '#777777';
        
        $sanitized['controls_spacing'] = isset($input['controls_spacing']) ? 
            min(max(intval($input['controls_spacing']), 20), 200) : 56;
        
                $sanitized['controls_spacing_tablet'] = isset($input['controls_spacing_tablet']) ?
            min(max(intval($input['controls_spacing_tablet']), 20), 200) : 56;
        
        
        $sanitized['controls_bottom_spacing'] = isset($input['controls_bottom_spacing']) ?
            min(max(intval($input['controls_bottom_spacing']), 10), 100) : 20;
        
        // Arrow styling settings
        $sanitized['arrow_border_radius'] = isset($input['arrow_border_radius']) ? 
            min(max(intval($input['arrow_border_radius']), 0), 50) : 0;
        
        $sanitized['arrow_padding'] = isset($input['arrow_padding']) ? 
            min(max(intval($input['arrow_padding']), 0), 20) : 3;
        
        $sanitized['arrow_width'] = isset($input['arrow_width']) ? 
            min(max(intval($input['arrow_width']), 20), 100) : 35;
        
        $sanitized['arrow_height'] = isset($input['arrow_height']) ? 
            min(max(intval($input['arrow_height']), 20), 100) : 35;
        
        $sanitized['arrow_bg_color'] = isset($input['arrow_bg_color']) ? 
            self::sanitize_hex_color($input['arrow_bg_color']) : '#111111';
        
        $sanitized['arrow_hover_bg_color'] = isset($input['arrow_hover_bg_color']) ? 
            self::sanitize_hex_color($input['arrow_hover_bg_color']) : '#000000';
        
        $sanitized['arrow_icon_color'] = isset($input['arrow_icon_color']) ? 
            self::sanitize_hex_color($input['arrow_icon_color']) : '#ffffff';
        
        $sanitized['arrow_icon_hover_color'] = isset($input['arrow_icon_hover_color']) ? 
            self::sanitize_hex_color($input['arrow_icon_hover_color']) : '#ffffff';
        
        $sanitized['arrow_icon_size'] = isset($input['arrow_icon_size']) ? 
            min(max(intval($input['arrow_icon_size']), 12), 48) : 28;
        
        // Pagination dot settings
        $sanitized['pagination_dot_color'] = isset($input['pagination_dot_color']) ? 
            self::sanitize_hex_color($input['pagination_dot_color']) : '#cfcfcf';
        
        $sanitized['pagination_active_dot_color'] = isset($input['pagination_active_dot_color']) ? 
            self::sanitize_hex_color($input['pagination_active_dot_color']) : '#111111';
        
        $sanitized['pagination_dot_gap'] = isset($input['pagination_dot_gap']) ? 
            min(max(intval($input['pagination_dot_gap']), 0), 50) : 10;
        
        $sanitized['pagination_dot_size'] = isset($input['pagination_dot_size']) ? 
            min(max(intval($input['pagination_dot_size']), 4), 20) : 8;
        
        // Active slide border color
        $sanitized['active_slide_border_color'] = isset($input['active_slide_border_color']) ? 
            self::sanitize_hex_color($input['active_slide_border_color']) : '#0073aa';
        
        // Darken inactive slides
        $sanitized['darken_inactive_slides'] = isset($input['darken_inactive_slides']) ? 1 : 0;
        
        return $sanitized;
    }

    public static function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Forbidden', 'typeform-quizzes'), __('Forbidden', 'typeform-quizzes'), ['response' => 403]);
        }



        // Admin notice after default settings save
        $notice = isset($_GET['tfq_notice']) ? sanitize_text_field($_GET['tfq_notice']) : '';
        if ($notice === 'defaults_saved') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Default shortcode settings saved successfully.', 'typeform-quizzes') . '</p></div>';
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Typeform Quizzes Settings', 'typeform-quizzes'); ?></h1>
            
            <?php
            // Display success message if settings were just saved
            if (isset($_GET['settings-updated']) && sanitize_text_field($_GET['settings-updated']) === 'true') {
                $referer = wp_get_referer();
                if ($referer) {
                    if (strpos($referer, 'typeform_quizzes_defaults_options') !== false) {
                        echo '<div class="notice notice-success is-dismissible" style="margin: 20px 0;"><p><strong>✅ ' . esc_html__('Success!', 'typeform-quizzes') . '</strong> ' . esc_html__('Quiz settings saved successfully.', 'typeform-quizzes') . '</p></div>';
                    } else {
                        echo '<div class="notice notice-success is-dismissible" style="margin: 20px 0;"><p><strong>✅ ' . esc_html__('Success!', 'typeform-quizzes') . '</strong> ' . esc_html__('Settings saved successfully.', 'typeform-quizzes') . '</p></div>';
                    }
                } else {
                    echo '<div class="notice notice-success is-dismissible" style="margin: 20px 0;"><p><strong>✅ ' . esc_html__('Success!', 'typeform-quizzes') . '</strong> ' . esc_html__('Settings saved successfully.', 'typeform-quizzes') . '</p></div>';
                }
            }
            ?>
            
            <script>
            jQuery(document).ready(function($) {
                // Initialize color pickers
                $('.color-picker').wpColorPicker();
                
                // Auto-hide success messages after 5 seconds
                $('.notice-success').delay(5000).fadeOut(500);
            });
            </script>
            
            <!-- API Configuration Section -->
            <div style="background: #f8f9fa; border: 1px solid #e1e5e9; border-radius: 8px; padding: 25px; margin: 30px 0;">
                <h2 style="margin: 0 0 20px 0; color: #0073aa; font-size: 24px; border-bottom: 3px solid #0073aa; padding-bottom: 15px;">
                    📝 <?php esc_html_e('Quiz Management', 'typeform-quizzes'); ?>
                </h2>
                <p style="margin: 0 0 20px 0; color: #666; font-size: 14px; line-height: 1.5;">
                    <?php esc_html_e('Create and manage your Typeform Quizzes. Each quiz needs a Typeform URL and can have a featured image.', 'typeform-quizzes'); ?>
                </p>
                <p style="margin: 0 0 20px 0;">
                    <a href="<?php echo admin_url('edit.php?post_type=typeform_quiz'); ?>" class="button button-primary" style="background: #0073aa; border-color: #0073aa; padding: 8px 20px; font-size: 14px;">
                        📝 <?php esc_html_e('Manage Quizzes', 'typeform-quizzes'); ?>
                    </a>
                    <a href="<?php echo admin_url('post-new.php?post_type=typeform_quiz'); ?>" class="button" style="margin-left: 10px; padding: 8px 20px; font-size: 14px;">
                        ➕ <?php esc_html_e('Add New Quiz', 'typeform-quizzes'); ?>
                    </a>
                </p>
            </div>

            <!-- Default Shortcode Settings Section -->
            <div style="background: #f8f9fa; border: 1px solid #e1e5e9; border-radius: 8px; padding: 25px; margin: 30px 0;">
                <h2 style="margin: 0 0 20px 0; color: #0073aa; font-size: 24px; border-bottom: 3px solid #0073aa; padding-bottom: 15px;">
                    ⚙️ <?php esc_html_e('Default Shortcode Settings', 'typeform-quizzes'); ?>
                </h2>
                <p style="margin: 0 0 20px 0; color: #666; font-size: 14px; line-height: 1.5;">
                    <?php esc_html_e('Configure default values for all shortcode parameters. These settings will be used when no specific values are provided in the shortcode.', 'typeform-quizzes'); ?>
                </p>

            <h2><?php esc_html_e('Default Shortcode Settings', 'typeform-quizzes'); ?></h2>
                            <form method="post" action="options.php">
                    <?php settings_fields('typeform_quizzes_defaults_options'); ?>
                    <?php $defaults = get_option('typeform_quizzes_defaults', []); ?>
                <table class="form-table">
                    
                    <!-- Basic Configuration -->
                    <tr>
                        <th scope="row" colspan="2">
                            <h3 style="margin: 0; padding: 15px 0; border-bottom: 2px solid #0073aa; color: #0073aa; font-size: 16px;">📋 <?php esc_html_e('Basic Configuration', 'typeform-quizzes'); ?></h3>
                        </th>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="max"><?php esc_html_e('Maximum Quizzes', 'typeform-quizzes'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="max" name="typeform_quizzes_defaults[max]" 
                                   value="<?php echo esc_attr($defaults['max'] ?? 20); ?>" 
                                   class="small-text" min="1" max="50">
                            <p class="description"><?php esc_html_e('Maximum number of quizzes to display (default: 20)', 'typeform-quizzes'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="order"><?php esc_html_e('Quiz Order', 'typeform-quizzes'); ?></label>
                        </th>
                        <td>
                            <select id="order" name="typeform_quizzes_defaults[order]">
                                <option value="menu_order" <?php selected($defaults['order'] ?? 'menu_order', 'menu_order'); ?>><?php esc_html_e('Custom order (by quiz order field)', 'typeform-quizzes'); ?></option>
                                <option value="date" <?php selected($defaults['order'] ?? 'menu_order', 'date'); ?>><?php esc_html_e('Published date (newest first)', 'typeform-quizzes'); ?></option>
                                <option value="title" <?php selected($defaults['order'] ?? 'menu_order', 'title'); ?>><?php esc_html_e('Title (A-Z)', 'typeform-quizzes'); ?></option>
                                <option value="rand" <?php selected($defaults['order'] ?? 'menu_order', 'rand'); ?>><?php esc_html_e('Random', 'typeform-quizzes'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('How quizzes should be ordered in the slider', 'typeform-quizzes'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="center_on_click"><?php esc_html_e('Center Quiz on Click', 'typeform-quizzes'); ?></label>
                        </th>
                        <td>
                            <input type="hidden" name="typeform_quizzes_defaults[center_on_click]" value="0">
                            <input type="checkbox" id="center_on_click" name="typeform_quizzes_defaults[center_on_click]" 
                                   value="1" <?php checked($defaults['center_on_click'] ?? true, true); ?>>
                            <p class="description"><?php esc_html_e('Center the quiz viewer when a quiz is clicked (default: enabled)', 'typeform-quizzes'); ?></p>
                        </td>
                    </tr>
                    
                    <!-- Layout & Grid Settings -->
                    <tr>
                        <th scope="row" colspan="2">
                            <h3 style="margin: 0; padding: 15px 0; border-bottom: 2px solid #0073aa; color: #0073aa; font-size: 16px;">🎯 <?php esc_html_e('Layout & Grid Settings', 'typeform-quizzes'); ?></h3>
                        </th>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="max_width"><?php esc_html_e('Maximum Width (px)', 'typeform-quizzes'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="max_width" name="typeform_quizzes_defaults[max_width]" 
                                   value="<?php echo esc_attr($defaults['max_width'] ?? 1450); ?>" 
                                   class="small-text" min="200" max="2000">
                            <p class="description"><?php esc_html_e('Maximum width of the slider container (default: 1450px)', 'typeform-quizzes'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cols_desktop"><?php esc_html_e('Desktop Columns', 'typeform-quizzes'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="cols_desktop" name="typeform_quizzes_defaults[cols_desktop]" 
                                   value="<?php echo esc_attr($defaults['cols_desktop'] ?? 6); ?>" 
                                   class="small-text" min="1" max="12">
                            <p class="description"><?php esc_html_e('Number of quizzes visible per row on desktop screens (default: 6)', 'typeform-quizzes'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cols_tablet"><?php esc_html_e('Tablet Columns', 'typeform-quizzes'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="cols_tablet" name="typeform_quizzes_defaults[cols_tablet]" 
                                   value="<?php echo esc_attr($defaults['cols_tablet'] ?? 3); ?>" 
                                   class="small-text" min="1" max="8">
                            <p class="description"><?php esc_html_e('Number of quizzes visible per row on tablet screens (default: 3)', 'typeform-quizzes'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cols_mobile"><?php esc_html_e('Mobile Columns', 'typeform-quizzes'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="cols_mobile" name="typeform_quizzes_defaults[cols_mobile]" 
                                   value="<?php echo esc_attr($defaults['cols_mobile'] ?? 2); ?>" 
                                   class="small-text" min="1" max="4">
                            <p class="description"><?php esc_html_e('Number of quizzes visible per row on mobile screens (default: 2)', 'typeform-quizzes'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="gap"><?php esc_html_e('Gap Between Items (px)', 'typeform-quizzes'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="gap" name="typeform_quizzes_defaults[gap]" 
                                   value="<?php echo esc_attr($defaults['gap'] ?? 20); ?>" 
                                   class="small-text" min="0" max="100">
                            <p class="description"><?php esc_html_e('Space between quiz items (default: 20px)', 'typeform-quizzes'); ?></p>
                        </td>
                    </tr>
                    
                    <!-- Thumbnail Settings -->
                    <tr>
                        <th scope="row" colspan="2">
                            <h3 style="margin: 0; padding: 15px 0; border-bottom: 2px solid #0073aa; color: #0073aa; font-size: 16px;">🖼️ <?php esc_html_e('Thumbnail Settings', 'typeform-quizzes'); ?></h3>
                        </th>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="thumb_height"><?php esc_html_e('Thumbnail Height (px)', 'typeform-quizzes'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="thumb_height" name="typeform_quizzes_defaults[thumb_height]" 
                                   value="<?php echo esc_attr($defaults['thumb_height'] ?? '200'); ?>" 
                                   class="small-text" min="50" max="1000" step="10">
                            <p class="description"><?php esc_html_e('Height of quiz thumbnails in pixels (default: 200px)', 'typeform-quizzes'); ?></p>
                        </td>
                    </tr>
                    
                    <!-- Visual Styling -->
                    <tr>
                        <th scope="row" colspan="2">
                            <h3 style="margin: 0; padding: 15px 0; border-bottom: 2px solid #0073aa; color: #0073aa; font-size: 16px;">🎨 <?php esc_html_e('Visual Styling', 'typeform-quizzes'); ?></h3>
                        </th>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="border_radius"><?php esc_html_e('Thumbnail Border Radius (px)', 'typeform-quizzes'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="border_radius" name="typeform_quizzes_defaults[border_radius]" 
                                   value="<?php echo esc_attr($defaults['border_radius'] ?? 16); ?>" 
                                   class="small-text" min="0" max="50">
                            <p class="description"><?php esc_html_e('Border radius for thumbnail images (default: 16px)', 'typeform-quizzes'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="title_color"><?php esc_html_e('Title Color', 'typeform-quizzes'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="title_color" name="typeform_quizzes_defaults[title_color]" 
                                   value="<?php echo esc_attr($defaults['title_color'] ?? '#000000'); ?>" 
                                   class="color-picker" data-default-color="#000000">
                            <p class="description"><?php esc_html_e('Color of quiz titles (default: #000000)', 'typeform-quizzes'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="title_hover_color"><?php esc_html_e('Title Hover Color', 'typeform-quizzes'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="title_hover_color" name="typeform_quizzes_defaults[title_hover_color]" 
                                   value="<?php echo esc_attr($defaults['title_hover_color'] ?? '#777777'); ?>" 
                                   class="color-picker" data-default-color="#777777">
                            <p class="description"><?php esc_html_e('Color of quiz titles on hover (default: #777777)', 'typeform-quizzes'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="active_slide_border_color"><?php esc_html_e('Active Slide Border Color', 'typeform-quizzes'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="active_slide_border_color" name="typeform_quizzes_defaults[active_slide_border_color]" 
                                   value="<?php echo esc_attr($defaults['active_slide_border_color'] ?? '#0073aa'); ?>" 
                                   class="color-picker" data-default-color="#0073aa">
                            <p class="description"><?php esc_html_e('Border color for the active/selected quiz slide (default: #0073aa)', 'typeform-quizzes'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="darken_inactive_slides"><?php esc_html_e('Darken Inactive Slides', 'typeform-quizzes'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="darken_inactive_slides" name="typeform_quizzes_defaults[darken_inactive_slides]" 
                                   value="1" <?php checked($defaults['darken_inactive_slides'] ?? true, 1); ?>>
                            <p class="description"><?php esc_html_e('Apply a dark overlay to inactive quiz slides to make the active quiz stand out (default: enabled)', 'typeform-quizzes'); ?></p>
                        </td>
                    </tr>
                    
                    <!-- Control Spacing -->
                    <tr>
                        <th scope="row" colspan="2">
                            <h3 style="margin: 0; padding: 15px 0; border-bottom: 2px solid #0073aa; color: #0073aa; font-size: 16px;">📏 <?php esc_html_e('Control Spacing', 'typeform-quizzes'); ?></h3>
                        </th>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="controls_spacing"><?php esc_html_e('Desktop Controls Spacing (px)', 'typeform-quizzes'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="controls_spacing" name="typeform_quizzes_defaults[controls_spacing]" 
                                   value="<?php echo esc_attr($defaults['controls_spacing'] ?? 56); ?>" 
                                   class="small-text" min="20" max="200">
                            <p class="description"><?php esc_html_e('Space between pagination dots and next/prev buttons on desktop screens (default: 56px)', 'typeform-quizzes'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="controls_spacing_tablet"><?php esc_html_e('Tablet Controls Spacing (px)', 'typeform-quizzes'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="controls_spacing_tablet" name="typeform_quizzes_defaults[controls_spacing_tablet]" 
                                   value="<?php echo esc_attr($defaults['controls_spacing_tablet'] ?? 56); ?>" 
                                   class="small-text" min="20" max="200">
                            <p class="description"><?php esc_html_e('Space between pagination dots and next/prev buttons on tablet screens (default: 56px)', 'typeform-quizzes'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="controls_bottom_spacing"><?php esc_html_e('Controls Bottom Spacing (px)', 'typeform-quizzes'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="controls_bottom_spacing" name="typeform_quizzes_defaults[controls_bottom_spacing]" 
                                   value="<?php echo esc_attr($defaults['controls_bottom_spacing'] ?? 20); ?>" 
                                   class="small-text" min="10" max="100">
                            <p class="description"><?php esc_html_e('Space between bottom of slider and controls (default: 20px)', 'typeform-quizzes'); ?></p>
                        </td>
                    </tr>
                    
                    <!-- Arrow Button Styling -->
                    <tr>
                        <th scope="row" colspan="2">
                            <h3 style="margin: 0; padding: 15px 0; border-bottom: 2px solid #0073aa; color: #0073aa; font-size: 16px;">⬅️➡️ <?php esc_html_e('Arrow Button Styling', 'typeform-quizzes'); ?></h3>
                        </th>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="arrow_border_radius"><?php esc_html_e('Arrow Border Radius (px)', 'typeform-quizzes'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="arrow_border_radius" name="typeform_quizzes_defaults[arrow_border_radius]" 
                                   value="<?php echo esc_attr($defaults['arrow_border_radius'] ?? 0); ?>" 
                                   class="small-text" min="0" max="50">
                            <p class="description"><?php esc_html_e('Border radius for arrow buttons (default: 0px)', 'typeform-quizzes'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="arrow_padding"><?php esc_html_e('Arrow Padding (px)', 'typeform-quizzes'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="arrow_padding" name="typeform_quizzes_defaults[arrow_padding]" 
                                   value="<?php echo esc_attr($defaults['arrow_padding'] ?? 3); ?>" 
                                   class="small-text" min="0" max="20">
                            <p class="description"><?php esc_html_e('Internal padding for arrow buttons (default: 3px)', 'typeform-quizzes'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="arrow_width"><?php esc_html_e('Arrow Width (px)', 'typeform-quizzes'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="arrow_width" name="typeform_quizzes_defaults[arrow_width]" 
                                   value="<?php echo esc_attr($defaults['arrow_width'] ?? 35); ?>" 
                                   class="small-text" min="20" max="100">
                            <p class="description"><?php esc_html_e('Width of arrow buttons (default: 35px). The arrow icon will be automatically centered within this width.', 'typeform-quizzes'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="arrow_height"><?php esc_html_e('Arrow Height (px)', 'typeform-quizzes'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="arrow_height" name="typeform_quizzes_defaults[arrow_height]" 
                                   value="<?php echo esc_attr($defaults['arrow_height'] ?? 35); ?>" 
                                   class="small-text" min="20" max="100">
                            <p class="description"><?php esc_html_e('Height of arrow buttons (default: 35px). The arrow icon will be automatically centered within this height.', 'typeform-quizzes'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="arrow_bg_color">Arrow Background Color</label>
                        </th>
                        <td>
                            <input type="text" id="arrow_bg_color" name="typeform_quizzes_defaults[arrow_bg_color]" 
                                   value="<?php echo esc_attr($defaults['arrow_bg_color'] ?? '#111111'); ?>" 
                                   class="color-picker" data-default-color="#111111">
                            <p class="description">Background color of arrow buttons (default: #111111)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="arrow_hover_bg_color">Arrow Hover Background Color</label>
                        </th>
                        <td>
                            <input type="text" id="arrow_hover_bg_color" name="typeform_quizzes_defaults[arrow_hover_bg_color]" 
                                   value="<?php echo esc_attr($defaults['arrow_hover_bg_color'] ?? '#000000'); ?>" 
                                   class="color-picker" data-default-color="#000000">
                            <p class="description">Background color of arrow buttons on hover (default: #000000)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="arrow_icon_color">Arrow Icon Color</label>
                        </th>
                        <td>
                            <input type="text" id="arrow_icon_color" name="typeform_quizzes_defaults[arrow_icon_color]" 
                                   value="<?php echo esc_attr($defaults['arrow_icon_color'] ?? '#ffffff'); ?>" 
                                   class="color-picker" data-default-color="#ffffff">
                            <p class="description">Color of the arrow icons (default: #ffffff)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="arrow_icon_hover_color">Arrow Icon Hover Color</label>
                        </th>
                        <td>
                            <input type="text" id="arrow_icon_hover_color" name="typeform_quizzes_defaults[arrow_icon_hover_color]" 
                                   value="<?php echo esc_attr($defaults['arrow_icon_hover_color'] ?? '#ffffff'); ?>" 
                                   class="color-picker" data-default-color="#ffffff">
                            <p class="description">Color of the arrow icons on hover (default: #ffffff)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="arrow_icon_size">Arrow Icon Size (px)</label>
                        </th>
                        <td>
                            <input type="number" id="arrow_icon_size" name="typeform_quizzes_defaults[arrow_icon_size]" 
                                   value="<?php echo esc_attr($defaults['arrow_icon_size'] ?? 28); ?>" 
                                   class="small-text" min="12" max="48">
                            <p class="description">Size of the arrow icons (default: 28px)</p>
                        </td>
                    </tr>
                    
                    <!-- Pagination Dot Styling -->
                    <tr>
                        <th scope="row" colspan="2">
                            <h3 style="margin: 0; padding: 15px 0; border-bottom: 2px solid #0073aa; color: #0073aa; font-size: 16px;">🔘 Pagination Dot Styling</h3>
                        </th>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="pagination_dot_color">Pagination Dot Color</label>
                        </th>
                        <td>
                            <input type="text" id="pagination_dot_color" name="typeform_quizzes_defaults[pagination_dot_color]" 
                                   value="<?php echo esc_attr($defaults['pagination_dot_color'] ?? '#cfcfcf'); ?>" 
                                   class="color-picker" data-default-color="#cfcfcf">
                            <p class="description">Color of inactive pagination dots (default: #cfcfcf)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="pagination_active_dot_color">Active Pagination Dot Color</label>
                        </th>
                        <td>
                            <input type="text" id="pagination_active_dot_color" name="typeform_quizzes_defaults[pagination_active_dot_color]" 
                                   value="<?php echo esc_attr($defaults['pagination_active_dot_color'] ?? '#111111'); ?>" 
                                   class="color-picker" data-default-color="#111111">
                            <p class="description">Color of active pagination dot (default: #111111)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="pagination_dot_gap">Pagination Dot Gap (px)</label>
                        </th>
                        <td>
                            <input type="number" id="pagination_dot_gap" name="typeform_quizzes_defaults[pagination_dot_gap]" 
                                   value="<?php echo esc_attr($defaults['pagination_dot_gap'] ?? 10); ?>" 
                                   class="small-text" min="0" max="50">
                            <p class="description">Gap between pagination dots in pixels (default: 10px)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="pagination_dot_size">Pagination Dot Size (px)</label>
                        </th>
                        <td>
                            <input type="number" id="pagination_dot_size" name="typeform_quizzes_defaults[pagination_dot_size]" 
                                   value="<?php echo esc_attr($defaults['pagination_dot_size'] ?? 8); ?>" 
                                   class="small-text" min="4" max="20">
                            <p class="description">Size of pagination dots in pixels (default: 8px)</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('💾 ' . __('Save Default Settings', 'typeform-quizzes'), 'primary', 'submit', false, ['style' => 'background: #0073aa; border-color: #0073aa; padding: 8px 20px; font-size: 14px;']); ?>
            </form>
            </div>

            <!-- Export/Import Section -->
            <div style="background: #f8f9fa; border: 1px solid #e1e5e9; border-radius: 8px; padding: 25px; margin: 30px 0;">
                <h2 style="margin: 0 0 20px 0; color: #0073aa; font-size: 24px; border-bottom: 3px solid #0073aa; padding-bottom: 15px;">
                    📤 <?php esc_html_e('Export & Import Quizzes', 'typeform-quizzes'); ?>
                </h2>
                <p style="margin: 0 0 20px 0; color: #666; font-size: 14px; line-height: 1.5;">
                    <?php esc_html_e('Export your quizzes to a JSON file for backup or transfer to another site. Import quizzes from a previously exported file.', 'typeform-quizzes'); ?>
                </p>
                
                <div style="display: flex; gap: 20px; flex-wrap: wrap; margin: 20px 0;">
                    <!-- Export Section -->
                    <div style="flex: 1; min-width: 300px; background: #fff; border: 1px solid #e1e5e9; border-radius: 6px; padding: 20px;">
                        <h3 style="margin: 0 0 15px 0; color: #0073aa; font-size: 18px; border-bottom: 2px solid #e1e5e9; padding-bottom: 10px;">
                            📤 <?php esc_html_e('Export Quizzes', 'typeform-quizzes'); ?>
                        </h3>
                        <p style="margin: 0 0 15px 0; color: #666; font-size: 14px;">
                            <?php esc_html_e('Download all your quizzes as a JSON file for backup or migration.', 'typeform-quizzes'); ?>
                        </p>
                        <form method="post" action="" style="margin: 0;">
                            <?php wp_nonce_field('typeform_quizzes_settings', '_wpnonce'); ?>
                            <input type="hidden" name="action" value="export_quizzes">
                            <button type="submit" class="button button-primary" style="background: #0073aa; border-color: #0073aa; padding: 8px 20px; font-size: 14px;">
                                📥 <?php esc_html_e('Download Quiz Export', 'typeform-quizzes'); ?>
                            </button>
                        </form>
                    </div>
                    
                    <!-- Import Section -->
                    <div style="flex: 1; min-width: 300px; background: #fff; border: 1px solid #e1e5e9; border-radius: 6px; padding: 20px;">
                        <h3 style="margin: 0 0 15px 0; color: #0073aa; font-size: 18px; border-bottom: 2px solid #e1e5e9; padding-bottom: 10px;">
                            📥 <?php esc_html_e('Import Quizzes', 'typeform-quizzes'); ?>
                        </h3>
                        <p style="margin: 0 0 15px 0; color: #666; font-size: 14px;">
                            <?php esc_html_e('Upload a previously exported JSON file to import quizzes.', 'typeform-quizzes'); ?>
                        </p>
                        <form method="post" action="" enctype="multipart/form-data" style="margin: 0;">
                            <?php wp_nonce_field('typeform_quizzes_settings', '_wpnonce'); ?>
                            <input type="hidden" name="action" value="import_quizzes">
                            <input type="file" name="quiz_import_file" accept=".json" required style="margin-bottom: 10px; width: 100%;">
                            <br>
                            <label style="display: flex; align-items: center; margin: 10px 0; font-size: 14px;">
                                <input type="checkbox" name="overwrite_existing" value="1" style="margin-right: 8px;">
                                <?php esc_html_e('Overwrite existing quizzes with same title', 'typeform-quizzes'); ?>
                            </label>
                            <button type="submit" class="button button-secondary" style="padding: 8px 20px; font-size: 14px;">
                                📤 <?php esc_html_e('Upload & Import', 'typeform-quizzes'); ?>
                            </button>
                        </form>
                    </div>
                </div>
                
                <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; padding: 15px; margin: 20px 0;">
                    <h4 style="margin: 0 0 10px 0; color: #856404; font-size: 16px;">⚠️ Important Notes:</h4>
                    <ul style="margin: 0; padding-left: 20px; color: #856404; line-height: 1.6; font-size: 14px;">
                        <li>Export includes quiz titles, content, Typeform URLs, featured images, and display order</li>
                        <li>Import will create new quiz posts - existing quizzes won't be modified unless "Overwrite" is checked</li>
                        <li>Featured images will be re-imported from the original URLs if available</li>
                        <li>Always backup your site before importing quizzes</li>
                    </ul>
                </div>
            </div>

            <!-- Usage Section -->
            <div style="background: #f8f9fa; border: 1px solid #e1e5e9; border-radius: 8px; padding: 25px; margin: 30px 0;">
                <h2 style="margin: 0 0 20px 0; color: #0073aa; font-size: 24px; border-bottom: 3px solid #0073aa; padding-bottom: 15px;">
                    📚 Usage & Documentation
                </h2>
                <p style="margin: 0 0 20px 0; color: #666; font-size: 14px; line-height: 1.5;">
                    Use the shortcode <code>[typeform_quizzes_slider max="20" max_width="1450" thumb_height="200" cols_desktop="6" cols_tablet="3" cols_mobile="2" gap="20" center_on_click="true" border_radius="16" title_color="#000000" title_hover_color="#777777" controls_spacing="56" controls_spacing_tablet="56" controls_bottom_spacing="20" arrow_border_radius="0" arrow_padding="3" arrow_width="35" arrow_height="35" arrow_bg_color="#111111" arrow_hover_bg_color="#000000" arrow_icon_color="#ffffff" arrow_icon_hover_color="#ffffff" arrow_icon_size="28" pagination_dot_color="#cfcfcf" pagination_active_dot_color="#111111" pagination_dot_gap="10" pagination_dot_size="8" active_slide_border_color="#0073aa" darken_inactive_slides="1" order="menu_order"]</code> in your posts or pages.
                </p>
            
                <div style="background: #fff; border: 1px solid #e1e5e9; border-radius: 6px; padding: 20px; margin: 20px 0;">
                    <h3 style="margin: 0 0 15px 0; color: #0073aa; font-size: 18px; border-bottom: 2px solid #e1e5e9; padding-bottom: 10px;">
                        📋 Parameters
                    </h3>
                    <ul style="margin: 0; padding-left: 20px; color: #333; line-height: 1.6;">
                        <li><strong>max</strong> (optional): Maximum number of quizzes to display (default: 20)</li>
                        <li><strong>order</strong> (optional): Quiz order - "menu_order", "date", "title", or "rand" (default: menu_order)</li>
                        <li><strong>max_width</strong> (optional): Maximum width of grid in pixels (default: 1450)</li>
                        <li><strong>thumb_height</strong> (optional): Thumbnail height in pixels - any number between 50-1000 (default: 200)</li>
                        <li><strong>cols_desktop</strong> (optional): Number of quizzes visible per row on desktop (default: 6)</li>
                        <li><strong>cols_tablet</strong> (optional): Number of quizzes visible per row on tablet (default: 3)</li>
                        <li><strong>cols_mobile</strong> (optional): Number of quizzes visible per row on mobile (default: 2)</li>
                        <li><strong>gap</strong> (optional): Gap between quiz items in pixels (default: 20)</li>
                        <li><strong>center_on_click</strong> (optional): Whether to center the quiz viewer when clicked - "true" or "false" (default: true)</li>
                        <li><strong>border_radius</strong> (optional): Border radius for quiz thumbnails in pixels (default: 16)</li>
                        <li><strong>title_color</strong> (optional): Color of quiz titles in hex format (default: #000000)</li>
                        <li><strong>title_hover_color</strong> (optional): Color of quiz titles on hover in hex format (default: #777777)</li>
                        <li><strong>controls_spacing</strong> (optional): Space between pagination dots and next/prev buttons in pixels (default: 56)</li>
                        <li><strong>controls_spacing_tablet</strong> (optional): Space between pagination dots and next/prev buttons on tablet screens in pixels (default: 56)</li>
                        <li><strong>controls_bottom_spacing</strong> (optional): Space between bottom of slider and controls in pixels (default: 20)</li>
                        <li><strong>arrow_border_radius</strong> (optional): Border radius for arrow buttons in pixels (default: 0)</li>
                        <li><strong>arrow_padding</strong> (optional): Internal padding for arrow buttons in pixels (default: 3)</li>
                        <li><strong>arrow_width</strong> (optional): Width of arrow buttons in pixels (default: 35)</li>
                        <li><strong>arrow_height</strong> (optional): Height of arrow buttons in pixels (default: 35)</li>
                        <li><strong>arrow_bg_color</strong> (optional): Background color of arrow buttons in hex format (default: #111111)</li>
                        <li><strong>arrow_hover_bg_color</strong> (optional): Background color of arrow buttons on hover in hex format (default: #000000)</li>
                        <li><strong>arrow_icon_color</strong> (optional): Color of the arrow icons in hex format (default: #ffffff)</li>
                        <li><strong>arrow_icon_hover_color</strong> (optional): Color of the arrow icons on hover in hex format (default: #ffffff)</li>
                        <li><strong>arrow_icon_size</strong> (optional): Size of the arrow icons in pixels (default: 28)</li>
                        <li><strong>pagination_dot_color</strong> (optional): Color of inactive pagination dots in hex format (default: #cfcfcf)</li>
                        <li><strong>pagination_active_dot_color</strong> (optional): Color of active pagination dot in hex format (default: #111111)</li>
                        <li><strong>pagination_dot_gap</strong> (optional): Gap between pagination dots in pixels (default: 10)</li>
                        <li><strong>pagination_dot_size</strong> (optional): Size of pagination dots in pixels (default: 8)</li>
                        <li><strong>active_slide_border_color</strong> (optional): Border color for the active/selected quiz slide in hex format (default: #0073aa)</li>
                        <li><strong>darken_inactive_slides</strong> (optional): Whether to apply a dark overlay to inactive quiz slides (1 = enabled, 0 = disabled, default: 1)</li>
                    </ul>
                </div>

                <div style="background: #fff; border: 1px solid #e1e5e9; border-radius: 6px; padding: 20px; margin: 20px 0;">
                    <h3 style="margin: 0 0 15px 0; color: #0073aa; font-size: 18px; border-bottom: 2px solid #e1e5e9; padding-bottom: 10px;">
                        💡 Examples
                    </h3>
            <p><strong>Basic usage:</strong><br>
            <code>[typeform_quizzes_slider]</code></p>
            
            <p><strong>Custom slider layout:</strong><br>
            <code>[typeform_quizzes_slider max="12" cols_desktop="4" cols_tablet="2" cols_mobile="1" gap="15"]</code></p>
            
            <p><strong>Order by published date (newest first):</strong><br>
            <code>[typeform_quizzes_slider order="date"]</code></p>
            
            <p><strong>Order by title (A-Z):</strong><br>
            <code>[typeform_quizzes_slider order="title"]</code></p>
            
            <p><strong>Random order:</strong><br>
            <code>[typeform_quizzes_slider order="rand"]</code></p>
            
            <p><strong>Disable quiz centering:</strong><br>
            <code>[typeform_quizzes_slider center_on_click="false"]</code></p>
            
            <p><strong>Custom styling:</strong><br>
            <code>[typeform_quizzes_slider border_radius="20" title_color="#0066cc" title_hover_color="#003366"]</code></p>
            
            <p><strong>Compact pagination spacing:</strong><br>
            <code>[typeform_quizzes_slider controls_spacing="30"]</code></p>
            
            <p><strong>Responsive pagination spacing:</strong><br>
            <code>[typeform_quizzes_slider controls_spacing="80" controls_spacing_tablet="40"]</code></p>
            
            <p><strong>Custom bottom spacing:</strong><br>
            <code>[typeform_quizzes_slider controls_bottom_spacing="40"]</code></p>
            
            <p><strong>Custom arrow styling:</strong><br>
            <code>[typeform_quizzes_slider arrow_border_radius="8" arrow_width="40" arrow_height="40" arrow_padding="5" arrow_bg_color="#0066cc" arrow_hover_bg_color="#004499" arrow_icon_color="#ffffff" arrow_icon_size="24"]</code></p>
            
            <p><strong>Custom pagination dots:</strong><br>
            <code>[typeform_quizzes_slider pagination_dot_color="#e0e0e0" pagination_active_dot_color="#0066cc" pagination_dot_gap="15" pagination_dot_size="10"]</code></p>
            
            <p><strong>Disable darkening of inactive slides:</strong><br>
            <code>[typeform_quizzes_slider darken_inactive_slides="0"]</code></p>
            
            <p><strong>Individual quiz display:</strong><br>
            <code>[typeform_quiz id="123" width="100%" height="500px"]</code></p>
            
            <p><strong>Individual quiz by URL:</strong><br>
            <code>[typeform_quiz url="https://form.typeform.com/to/abc123" width="100%" height="500px"]</code></p>
            
            <p><strong>Note:</strong> The slider displays quizzes in a single row with pagination controls. Use the arrow buttons or dots to navigate through all quizzes. Click on a quiz to load it in the viewer below.</p>
                </div>

                <!-- Troubleshooting Section -->
                <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; padding: 20px; margin: 20px 0;">
                    <h3 style="margin: 0 0 15px 0; color: #856404; font-size: 18px; border-bottom: 2px solid #ffeaa7; padding-bottom: 10px;">
                        🔧 Troubleshooting
                    </h3>
                    <p style="margin: 0; color: #856404; line-height: 1.6;">
                        <strong>If new quizzes don't appear:</strong> Reload the page where the slider is embedded. The shortcode will fetch the latest quiz data.
                    </p>
                </div>
            </div>
        </div>
        <?php
    }


    /**
     * Handle settings form submissions and show success messages
     */
    public static function handle_settings_save() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Handle export/import actions
        // Verify nonce for security
        if (isset($_POST['action']) && wp_verify_nonce($_POST['_wpnonce'], 'typeform_quizzes_settings')) {
            $action = sanitize_text_field($_POST['action']);
            
            if ($action === 'export_quizzes') {
                self::handle_export_quizzes();
            } elseif ($action === 'import_quizzes') {
                self::handle_import_quizzes();
            }
        }
    }

    /**
     * Handle quiz export
     */
    public static function handle_export_quizzes() {
        // Security check - capability check only since nonce is verified in handle_settings_save()
        if (!current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'typeform-quizzes'), __('Forbidden', 'typeform-quizzes'), ['response' => 403]);
        }

        // Get all published quizzes
        $quizzes = get_posts([
            'post_type' => 'typeform_quiz',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ]);

        $export_data = [
            'version' => '1.0',
            'export_date' => current_time('mysql'),
            'site_url' => get_site_url(),
            'quizzes' => []
        ];

        foreach ($quizzes as $quiz) {
            $quiz_data = [
                'title' => $quiz->post_title,
                'content' => $quiz->post_content,
                'excerpt' => $quiz->post_excerpt,
                'menu_order' => $quiz->menu_order,
                'typeform_url' => get_post_meta($quiz->ID, '_typeform_url', true),
                'featured_image_url' => get_the_post_thumbnail_url($quiz->ID, 'full'),
                'meta' => [
                    'quiz_order' => get_post_meta($quiz->ID, '_quiz_order', true)
                ]
            ];
            $export_data['quizzes'][] = $quiz_data;
        }

        // Generate filename
        $filename = 'typeform-quizzes-export-' . date('Y-m-d-H-i-s') . '.json';

        // Set headers for file download
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen(json_encode($export_data, JSON_PRETTY_PRINT)));

        // Output the JSON data
        echo json_encode($export_data, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Handle quiz import
     */
    public static function handle_import_quizzes() {
        // Security check - capability check only since nonce is verified in handle_settings_save()
        if (!current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'typeform-quizzes'), __('Forbidden', 'typeform-quizzes'), ['response' => 403]);
        }

        // Check if file was uploaded
        if (!isset($_FILES['quiz_import_file']) || $_FILES['quiz_import_file']['error'] !== UPLOAD_ERR_OK) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . esc_html__('Error uploading file. Please try again.', 'typeform-quizzes') . '</p></div>';
            });
            return;
        }

        $file = $_FILES['quiz_import_file'];
        $overwrite_existing = isset($_POST['overwrite_existing']) && $_POST['overwrite_existing'] === '1';

        // Enhanced file validation
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['json'];
        $max_file_size = 5 * 1024 * 1024; // 5MB limit
        
        // Validate file extension
        if (!in_array($file_extension, $allowed_extensions)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . esc_html__('Invalid file type. Please upload a JSON file.', 'typeform-quizzes') . '</p></div>';
            });
            return;
        }
        
        // Validate file size
        if ($file['size'] > $max_file_size) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . esc_html__('File too large. Maximum size is 5MB.', 'typeform-quizzes') . '</p></div>';
            });
            return;
        }
        
        // Validate MIME type
        $allowed_mime_types = ['application/json', 'text/plain'];
        $file_type = wp_check_filetype($file['name'], ['json' => 'application/json']);
        if (!$file_type['type'] || !in_array($file_type['type'], $allowed_mime_types)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . esc_html__('Invalid file type detected. Please upload a valid JSON file.', 'typeform-quizzes') . '</p></div>';
            });
            return;
        }
        
        // Additional security check - validate file content
        $file_content = file_get_contents($file['tmp_name']);
        if ($file_content === false || empty($file_content)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . esc_html__('Unable to read file content. Please try again.', 'typeform-quizzes') . '</p></div>';
            });
            return;
        }
        
        // Validate JSON structure
        $json_data = json_decode($file_content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . esc_html__('Invalid JSON format. Please check your file.', 'typeform-quizzes') . '</p></div>';
            });
            return;
        }

        // Use already validated JSON data
        $import_data = $json_data;

        // Validate import data structure
        if (!isset($import_data['quizzes']) || !is_array($import_data['quizzes'])) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>Invalid import file format. Missing quizzes data.</p></div>';
            });
            return;
        }

        $imported_count = 0;
        $skipped_count = 0;
        $errors = [];

        foreach ($import_data['quizzes'] as $quiz_data) {
            try {
                // Check if quiz already exists (by title)
                $existing_quiz_query = new WP_Query([
                    'post_type' => 'typeform_quiz',
                    'post_status' => 'publish',
                    'title' => $quiz_data['title'],
                    'posts_per_page' => 1,
                    'fields' => 'ids'
                ]);
                $existing_quiz = $existing_quiz_query->have_posts() ? get_post($existing_quiz_query->posts[0]) : null;
                
                if ($existing_quiz && !$overwrite_existing) {
                    $skipped_count++;
                    continue;
                }

                // Prepare quiz data
                $post_data = [
                    'post_title' => sanitize_text_field($quiz_data['title']),
                    'post_content' => wp_kses_post($quiz_data['content']),
                    'post_excerpt' => sanitize_text_field($quiz_data['excerpt']),
                    'post_type' => 'typeform_quiz',
                    'post_status' => 'publish',
                    'menu_order' => intval($quiz_data['menu_order'] ?? 0)
                ];

                // Update existing quiz or create new one
                if ($existing_quiz && $overwrite_existing) {
                    $post_data['ID'] = $existing_quiz->ID;
                    $quiz_id = wp_update_post($post_data);
                } else {
                    $quiz_id = wp_insert_post($post_data);
                }

                if (is_wp_error($quiz_id) || !$quiz_id) {
                    $errors[] = 'Failed to create/update quiz: ' . $quiz_data['title'];
                    continue;
                }

                // Save meta data
                if (!empty($quiz_data['typeform_url'])) {
                    update_post_meta($quiz_id, '_typeform_url', esc_url_raw($quiz_data['typeform_url']));
                }

                if (isset($quiz_data['meta']['quiz_order'])) {
                    update_post_meta($quiz_id, '_quiz_order', intval($quiz_data['meta']['quiz_order']));
                }

                // Handle featured image
                if (!empty($quiz_data['featured_image_url'])) {
                    self::import_featured_image($quiz_id, $quiz_data['featured_image_url']);
                }

                $imported_count++;

            } catch (Exception $e) {
                $errors[] = 'Error importing quiz "' . $quiz_data['title'] . '": ' . $e->getMessage();
            }
        }

        // Show results
        $message = "Import completed! Imported: {$imported_count} quizzes";
        if ($skipped_count > 0) {
            $message .= ", Skipped: {$skipped_count} quizzes (already exist)";
        }
        if (!empty($errors)) {
            $message .= ", Errors: " . count($errors);
        }

        add_action('admin_notices', function() use ($message, $errors) {
            $class = !empty($errors) ? 'notice-warning' : 'notice-success';
            echo '<div class="notice ' . $class . '"><p>' . esc_html($message) . '</p></div>';
            
            if (!empty($errors)) {
                echo '<div class="notice notice-error"><p><strong>Errors:</strong></p><ul>';
                foreach ($errors as $error) {
                    echo '<li>' . esc_html($error) . '</li>';
                }
                echo '</ul></div>';
            }
        });
    }

    /**
     * Import featured image from URL
     */
    private static function import_featured_image($post_id, $image_url) {
        if (empty($image_url)) {
            return false;
        }

        // Check if image already exists
        $attachment_id = attachment_url_to_postid($image_url);
        if ($attachment_id) {
            set_post_thumbnail($post_id, $attachment_id);
            return $attachment_id;
        }

        // Download image
        $image_data = wp_remote_get($image_url);
        if (is_wp_error($image_data)) {
            return false;
        }

        $image_body = wp_remote_retrieve_body($image_data);
        if (empty($image_body)) {
            return false;
        }

        // Get file extension
        $file_extension = pathinfo(parse_url($image_url, PHP_URL_PATH), PATHINFO_EXTENSION);
        if (empty($file_extension)) {
            $file_extension = 'jpg'; // Default fallback
        }

        // Create filename
        $filename = 'quiz-thumbnail-' . $post_id . '.' . $file_extension;

        // Upload image
        $upload = wp_upload_bits($filename, null, $image_body);
        if ($upload['error']) {
            return false;
        }

        // Create attachment
        $attachment = [
            'post_mime_type' => wp_check_filetype($filename)['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit'
        ];

        $attachment_id = wp_insert_attachment($attachment, $upload['file'], $post_id);
        if (is_wp_error($attachment_id)) {
            return false;
        }

        // Generate attachment metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
        wp_update_attachment_metadata($attachment_id, $attachment_data);

        // Set as featured image
        set_post_thumbnail($post_id, $attachment_id);

        return $attachment_id;
    }

    /**
     * Enqueue necessary scripts and styles for the slider
     */
    public static function enqueue_scripts() {
        // Only load assets if shortcodes are present on the page
        global $post;
        if (!$post || !has_shortcode($post->post_content, 'typeform_quiz') && !has_shortcode($post->post_content, 'typeform_quizzes_slider')) {
            return;
        }
        
        // Check if any FontAwesome version is already loaded
        $fa_loaded = wp_style_is('font-awesome', 'enqueued') || 
                     wp_style_is('fontawesome', 'enqueued') || 
                     wp_style_is('font-awesome-5', 'enqueued') ||
                     wp_style_is('font-awesome-6', 'enqueued') ||
                     wp_style_is('font-awesome-4', 'enqueued') ||
                     wp_style_is('typeform-quizzes-fontawesome', 'enqueued');
        
        if (!$fa_loaded) {
            // Load FontAwesome 6.4.0 with a unique handle to avoid conflicts
            $fontawesome_url = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
            wp_enqueue_style('typeform-quizzes-fontawesome', $fontawesome_url, [], '6.4.0');
            wp_style_add_data('typeform-quizzes-fontawesome', 'defer', true);
        }
        
        // Add our custom CSS for consistent arrow display - only when shortcode is present
        add_action('wp_head', function() use ($fa_loaded) {
            echo '<style>
            /* Typeform Quizzes Slider - Arrow Controls (Plugin-specific selectors) */
            .typeform-quizzes-slider .ytrow-arrow {
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
                color: var(--arrow-icon-color, #ffffff) !important;
            }
            
            /* Removed span wrapper - icons are now direct children */
            
            /* FontAwesome icons styling */
            .typeform-quizzes-slider .ytrow-arrow .fa-solid.fa-angle-left,
            .typeform-quizzes-slider .ytrow-arrow .fa-solid.fa-angle-right {
                font-size: inherit;
                line-height: 1;
                display: flex;
                align-items: center;
                justify-content: center;
                color: var(--arrow-icon-color, #ffffff) !important;
                vertical-align: middle;
            }
            
            /* CSS-only arrow fallbacks - ONLY show when FontAwesome fails */
            .typeform-quizzes-slider .ytrow-arrow.fa-fallback[data-dir="prev"]::before,
            .typeform-quizzes-slider .ytrow-arrow.fa-fallback[data-dir="next"]::before {
                content: "";
                position: absolute;
                width: 0;
                height: 0;
                border: solid transparent;
                border-width: 8px;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
            }
            
            .typeform-quizzes-slider .ytrow-arrow.fa-fallback[data-dir="prev"]::before {
                border-right-color: currentColor;
                margin-left: -2px;
            }
            
            .typeform-quizzes-slider .ytrow-arrow.fa-fallback[data-dir="next"]::before {
                border-left-color: currentColor;
                margin-left: 2px;
            }
            

            
            /* Ensure proper icon sizing and positioning */
            .typeform-quizzes-slider .ytrow-arrow i {
                font-size: inherit !important;
                line-height: 1 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                color: var(--arrow-icon-color, #ffffff) !important;
                width: 100% !important;
                height: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                vertical-align: middle !important;
            }
            
            /* CSS-only arrow sizing based on container */
            .typeform-quizzes-slider .ytrow-arrow.fa-fallback[data-dir="prev"]::before,
            .typeform-quizzes-slider .ytrow-arrow.fa-fallback[data-dir="next"]::before {
                border-width: calc(var(--arrow-icon-size, 28px) * 0.3);
            }
            
            /* Ensure arrows are visible and properly sized */
            .typeform-quizzes-slider .ytrow-arrow .fa-solid.fa-angle-left,
            .typeform-quizzes-slider .ytrow-arrow .fa-solid.fa-angle-right {
                font-size: var(--arrow-icon-size, 28px) !important;
                color: var(--arrow-icon-color, #ffffff) !important;
                line-height: 1 !important;
                vertical-align: middle !important;
            }
            
            /* Additional specificity to override site link colors */
            .typeform-quizzes-slider .tfqrow-arrow i {
                color: var(--arrow-icon-color, #ffffff) !important;
            }
            
            /* Maximum specificity to ensure arrow colors override all site styles */
            .typeform-quizzes-slider .tfqrow-arrow[data-dir="prev"] i,
            .typeform-quizzes-slider .tfqrow-arrow[data-dir="next"] i {
                color: var(--arrow-icon-color, #ffffff) !important;
            }
            
            /* Ensure default state arrow colors are applied */
            .typeform-quizzes-slider .tfqrow-arrow:not(:hover) i {
                color: var(--arrow-icon-color, #ffffff) !important;
            }
            
            /* Hide Font Awesome icons when using CSS fallback */
            .typeform-quizzes-slider .ytrow-arrow.fa-fallback .fa-solid.fa-angle-left,
            .typeform-quizzes-slider .ytrow-arrow.fa-fallback .fa-solid.fa-angle-right {
                display: none !important;
            }
            
            /* Arrow Button Base Styles - Global */
            .typeform-quizzes-slider .tfqrow-arrow {
                width: var(--arrow-width, 35px);
                height: var(--arrow-height, 35px);
                background: var(--arrow-bg-color, #111111);
                border-radius: var(--arrow-border-radius, 0px);
                padding: var(--arrow-padding-vertical, 3.5px) var(--arrow-padding-horizontal, 3.5px);
                position: static;
                margin: 0;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                border: none;
                transition: background-color 0.3s ease;
            }
            
            /* Arrow Icon Styles - Global */
            .typeform-quizzes-slider .tfqrow-arrow-icon {
                color: var(--arrow-icon-color, #ffffff);
                font-size: var(--arrow-icon-size, 28px);
                line-height: 1;
                display: block;
            }
            
            /* Arrow Hover Styles - Global */
            .typeform-quizzes-slider .tfqrow-arrow:hover {
                background: var(--arrow-hover-bg-color, #000000);
            }
            
            .typeform-quizzes-slider .tfqrow-arrow:hover .tfqrow-arrow-icon {
                color: var(--arrow-icon-hover-color, #ffffff);
            }
            </style>';
        });
    }

    /**
     * Enqueue admin scripts and styles
     */
    public static function enqueue_admin_scripts($hook) {
        if (!current_user_can('edit_posts')) {
            return;
        }
        
        // Load on our plugin page and quiz edit pages
        if ($hook === 'typeform_quiz_page_typeform-quizzes-tools') {
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
        }
        
        // Load reorder functionality on quiz list page
        if ($hook === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'typeform_quiz') {
            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_script('jquery-ui-dialog');
            wp_enqueue_style('wp-jquery-ui-dialog');
            
            // Add our custom reorder script
            wp_add_inline_script('jquery-ui-sortable', self::get_reorder_script());
            wp_add_inline_style('wp-jquery-ui-dialog', self::get_reorder_styles());
        }
    }
    
    /**
     * Get reorder JavaScript
     */
    public static function get_reorder_script() {
        $ajax_url = admin_url('admin-ajax.php');
        $nonce = wp_create_nonce('typeform_quiz_reorder');
        
        return "
        jQuery(document).ready(function($) {
            let reorderModal = $('#typeform-quizzes-reorder-modal');
            let reorderList = $('#typeform-quizzes-reorder-list');
            let isDirty = false;
            
            // Open modal
            $('#reorder-quizzes-btn').on('click', function() {
                loadQuizzes();
                reorderModal.show();
            });
            
            // Close modal
            $('#typeform-quizzes-close-reorder-modal').on('click', function() {
                if (isDirty) {
                    if (confirm('You have unsaved changes. Are you sure you want to close?')) {
                        reorderModal.hide();
                        isDirty = false;
                    }
                } else {
                    reorderModal.hide();
                }
            });
            
            // Close on background click
            reorderModal.on('click', function(e) {
                if (e.target === this) {
                    if (isDirty) {
                        if (confirm('You have unsaved changes. Are you sure you want to close?')) {
                            reorderModal.hide();
                            isDirty = false;
                        }
                    } else {
                        reorderModal.hide();
                    }
                }
            });
            
            // Load quizzes
            function loadQuizzes() {
                reorderList.html('<div style=\"text-align: center; padding: 50px; color: #999;\"><span class=\"dashicons dashicons-update\" style=\"font-size: 24px; margin-right: 10px;\"></span>Loading quizzes...</div>');
                
                $.post('$ajax_url', {
                    action: 'typeform_quiz_reorder',
                    action_type: 'get_quizzes',
                    nonce: '$nonce'
                }, function(response) {
                    if (response.success) {
                        renderQuizzes(response.data);
                    } else {
                        reorderList.html('<div style=\"text-align: center; padding: 50px; color: #d63638;\">Error loading quizzes: ' + response.data + '</div>');
                    }
                });
            }
            
            // Render quizzes
            function renderQuizzes(quizzes) {
                if (quizzes.length === 0) {
                    reorderList.html('<div style=\"text-align: center; padding: 50px; color: #999;\">No quizzes found.</div>');
                    $('#quiz-count').text('');
                    return;
                }
                
                // Update quiz count
                $('#quiz-count').text(quizzes.length + ' quiz' + (quizzes.length !== 1 ? 'zes' : '') + ' total');
                
                let html = '<ul id=\"sortable-quizzes\" style=\"list-style: none; margin: 0; padding: 0;\">';
                quizzes.forEach(function(quiz, index) {
                    // Create a proper thumbnail with error handling
                    let thumbnail = '';
                    if (quiz.thumbnail && quiz.thumbnail !== '') {
                        thumbnail = '<img src=\"' + quiz.thumbnail + '\" alt=\"' + quiz.title + '\" style=\"width: 100%; height: 100%; object-fit: cover;\" onerror=\"this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';\"><div style=\"display: none; width: 100%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 4px; align-items: center; justify-content: center; color: white; font-size: 20px;\">📝</div>';
                    } else {
                        thumbnail = '<div style=\"width: 100%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 4px; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px;\">📝</div>';
                    }
                    
                    html += '<li data-id=\"' + quiz.id + '\" style=\"display: flex; align-items: center; padding: 12px 15px; margin: 3px 0; background: #fff; border: 1px solid #e1e5e9; border-radius: 6px; cursor: move; transition: all 0.2s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.1);\">';
                    html += '<span class=\"dashicons dashicons-menu\" style=\"margin-right: 12px; color: #999; font-size: 16px; cursor: move;\"></span>';
                    html += '<div style=\"width: 45px; height: 45px; margin-right: 12px; flex-shrink: 0; border-radius: 4px; overflow: hidden; background: #f5f5f5;\">' + thumbnail + '</div>';
                    html += '<div style=\"width: 50px; margin-right: 15px; flex-shrink: 0; text-align: center;\">';
                    html += '<div style=\"font-weight: 600; color: #0073aa; font-size: 16px; line-height: 1;\">' + (index + 1) + '</div>';
                    html += '<div style=\"font-size: 10px; color: #666; text-transform: uppercase; letter-spacing: 0.5px;\">Order</div>';
                    html += '</div>';
                    html += '<div style=\"flex: 1; min-width: 0;\">';
                    html += '<div style=\"font-weight: 600; color: #333; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;\">' + quiz.title + '</div>';
                    html += '</div>';
                    html += '</li>';
                });
                html += '</ul>';
                
                reorderList.html(html);
                
                // Initialize sortable with better options
                $('#sortable-quizzes').sortable({
                    handle: '.dashicons-menu',
                    placeholder: 'ui-state-highlight',
                    cursor: 'move',
                    tolerance: 'pointer',
                    distance: 5,
                    opacity: 0.8,
                    scroll: true,
                    scrollSensitivity: 100,
                    scrollSpeed: 20,
                    update: function() {
                        isDirty = true;
                        updateOrderNumbers();
                    },
                    start: function(event, ui) {
                        ui.item.addClass('ui-sortable-helper');
                    },
                    stop: function(event, ui) {
                        ui.item.removeClass('ui-sortable-helper');
                    }
                });
                
                // Add hover effects
                $('#sortable-quizzes li').hover(
                    function() {
                        $(this).css('background', '#f8f9fa').css('border-color', '#0073aa');
                    },
                    function() {
                        $(this).css('background', '#fff').css('border-color', '#e1e5e9');
                    }
                );
            }
            
            // Update order numbers
            function updateOrderNumbers() {
                $('#sortable-quizzes li').each(function(index) {
                    $(this).find('div:nth-child(3) div:first-child').text(index + 1);
                });
            }
            
            // Save order
            $('#typeform-quizzes-save-reorder').on('click', function() {
                let orderData = [];
                $('#sortable-quizzes li').each(function() {
                    orderData.push($(this).data('id'));
                });
                
                $(this).prop('disabled', true).text('Saving...');
                
                $.post('$ajax_url', {
                    action: 'typeform_quiz_reorder',
                    action_type: 'save_order',
                    order_data: JSON.stringify(orderData),
                    nonce: '$nonce'
                }, function(response) {
                    if (response.success) {
                        alert('Order saved successfully!');
                        reorderModal.hide();
                        isDirty = false;
                        location.reload(); // Refresh to show new order
                    } else {
                        console.error('AJAX Error Response:', response);
                        alert('Error saving order: ' + (response.data || 'Unknown error'));
                    }
                }).fail(function(xhr, status, error) {
                    console.error('AJAX Request Failed:', {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        responseText: xhr.responseText,
                        error: error
                    });
                    alert('Failed to save order. Status: ' + xhr.status + ' - ' + xhr.statusText);
                }).always(function() {
                    $('#save-reorder').prop('disabled', false).text('Save Order');
                });
            });
        });
        ";
    }
    
    /**
     * Get reorder CSS styles
     */
    public static function get_reorder_styles() {
        return "
        #typeform-quizzes-reorder-modal .ui-state-highlight {
            background: #e3f2fd !important;
            border: 2px dashed #2196f3 !important;
            height: 60px;
            border-radius: 6px;
            margin: 3px 0;
        }
        
        #typeform-quizzes-sortable-quizzes li.ui-sortable-helper {
            background: #fff !important;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
            transform: rotate(1deg) scale(1.02);
            z-index: 1000;
            border-color: #0073aa !important;
        }
        
        #typeform-quizzes-sortable-quizzes li.ui-sortable-helper .dashicons-menu {
            color: #0073aa !important;
        }
        
        .dashicons-menu {
            cursor: move;
            transition: color 0.2s ease;
        }
        
        .dashicons-menu:hover {
            color: #0073aa !important;
        }
        
        #reorder-list {
            scrollbar-width: thin;
            scrollbar-color: #c1c1c1 #f1f1f1;
        }
        
        #reorder-list::-webkit-scrollbar {
            width: 8px;
        }
        
        #reorder-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        #reorder-list::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }
        
        #reorder-list::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        #typeform-quizzes-reorder-modal .ui-sortable-helper {
            pointer-events: none;
        }
        ";
    }

    /**
     * Render individual typeform quiz shortcode
     * 
     * Displays a single Typeform quiz in an embedded iframe with customizable dimensions.
     * Supports both quiz ID and direct URL parameters.
     * 
     * @since 1.0.0
     * @param array $atts Shortcode attributes
     * @return string HTML output or error message
     * 
     * @example
     * [typeform_quiz id="123" width="100%" height="500px"]
     * [typeform_quiz url="https://form.typeform.com/to/abc123" width="100%" height="500px"]
     */
    public static function render_typeform_quiz($atts) {
        // Validate and sanitize shortcode attributes
        $atts = shortcode_atts([
            'id' => 0,
            'url' => '',
            'width' => '100%',
            'height' => '500px'
        ], $atts, 'typeform_quiz');

        $quiz_id = intval($atts['id']);
        $typeform_url = sanitize_url($atts['url']);
        $width = self::sanitize_css_dimension($atts['width'], '100%');
        $height = self::sanitize_css_dimension($atts['height'], '500px');

        // If ID is provided, get the URL from post meta
        if ($quiz_id > 0) {
            $post = get_post($quiz_id);
            if (!$post || $post->post_type !== 'typeform_quiz' || $post->post_status !== 'publish') {
                return self::render_error('Invalid quiz ID provided.');
            }
            
            $typeform_url = get_post_meta($quiz_id, '_typeform_url', true);
            if (empty($typeform_url)) {
                return self::render_error('No Typeform URL found for this quiz.');
            }
        }

        if (empty($typeform_url)) {
            return self::render_error('Typeform URL is required.');
        }

        // Validate Typeform URL format
        if (!self::is_valid_typeform_url($typeform_url)) {
            return self::render_error('Invalid Typeform URL format.');
        }

        // Ensure URL is properly formatted for embedding
        if (strpos($typeform_url, '/to/') === false) {
            return self::render_error('Invalid Typeform URL format. URL must contain "/to/" for embedding.');
        }

        // Add embed parameters if not already present
        if (strpos($typeform_url, '?') === false) {
            $typeform_url .= '?embed=true&embed-hide-headers=true&embed-hide-footer=true';
        } else {
            $typeform_url .= '&embed=true&embed-hide-headers=true&embed-hide-footer=true';
        }

        return '<div class="typeform-quiz-container" style="width: ' . $width . '; height: ' . $height . '; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                    <iframe src="' . esc_url($typeform_url) . '" 
                            width="100%" 
                            height="100%" 
                            frameborder="0" 
                            marginheight="0" 
                            marginwidth="0"
                            sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-popups-to-escape-sandbox allow-top-navigation-by-user-activation"
                            loading="lazy"
                            referrerpolicy="strict-origin-when-cross-origin"
                            style="border: none;">
                    </iframe>
                </div>';
    }

    /**
     * Render typeform quizzes slider shortcode
     * 
     * Displays multiple Typeform quizzes in a responsive slider with customizable
     * layout, styling, and behavior options. Supports various ordering methods
     * and extensive customization parameters. Features Swiper.js integration,
     * hosting platform compatibility, enhanced security, and performance optimization.
     * 
     * @since 1.0.0
     * @param array $atts Shortcode attributes
     * @return string HTML output or error message
     * 
     * Available attributes:
     * - max: Maximum number of quizzes to display (default: 20)
     * - max_width: Maximum width of the slider container (default: 1450px)
     * - thumb_height: Height of quiz thumbnails (default: 200px)
     * - cols_desktop: Number of columns on desktop (default: 6)
     * - cols_tablet: Number of columns on tablet (default: 3)
     * - cols_mobile: Number of columns on mobile (default: 2)
     * - gap: Space between slides (default: 20px)
     * - center_on_click: Center slide on click (default: true)
     * - border_radius: Border radius for thumbnails (default: 16px)
     * - title_color: Color of quiz titles (default: #000000)
     * - title_hover_color: Color of quiz titles on hover (default: #0073aa)
     * - active_slide_border_color: Border color for active slide (default: #0073aa)
     * - darken_inactive_slides: Darken inactive slides (default: false)
     * - controls_spacing: Spacing between controls (default: 20px)
     * - controls_bottom_spacing: Bottom spacing for controls (default: 20px)
     * - arrow_*: Various arrow styling options
     * - pagination_*: Various pagination styling options
     * - order: Order of quizzes (menu_order, date, title, rand) (default: menu_order)
     * 
     * @example
     * [typeform_quizzes_slider]
     * [typeform_quizzes_slider max="12" cols_desktop="4" cols_tablet="2" cols_mobile="1"]
     * [typeform_quizzes_slider order="date" center_on_click="false"]
     * [typeform_quizzes_slider title_color="#ff0000" arrow_bg_color="#000000"]
     */
    public static function render_quizzes_slider($atts) {
        try {
            // Validate and sanitize input
            if (!is_array($atts)) {
                $atts = [];
            }
            
            // Sanitize all input attributes
            $atts = self::sanitize_shortcode_attributes($atts);
            
            // Get defaults from admin settings
            $defaults = get_option('typeform_quizzes_defaults', []);
            
            $atts = shortcode_atts([
                'max' => $defaults['max'] ?? 20,
                'max_width' => $defaults['max_width'] ?? 1450,
                'thumb_height' => $defaults['thumb_height'] ?? 200,
                'cols_desktop' => $defaults['cols_desktop'] ?? 6,
                'cols_tablet' => $defaults['cols_tablet'] ?? 3,
                'cols_mobile' => $defaults['cols_mobile'] ?? 2,
                'gap' => $defaults['gap'] ?? 20,
                'center_on_click' => $defaults['center_on_click'] ?? true,
                'border_radius' => $defaults['border_radius'] ?? 16,
                'title_color' => $defaults['title_color'] ?? '#000000',
                'title_hover_color' => $defaults['title_hover_color'] ?? '#777777',
                'controls_spacing' => $defaults['controls_spacing'] ?? 56,
                'controls_spacing_tablet' => $defaults['controls_spacing_tablet'] ?? 56,
                'controls_bottom_spacing' => $defaults['controls_bottom_spacing'] ?? 20,
                'arrow_border_radius' => $defaults['arrow_border_radius'] ?? 0,
                'arrow_padding' => $defaults['arrow_padding'] ?? 3,
                'arrow_width' => $defaults['arrow_width'] ?? 35,
                'arrow_height' => $defaults['arrow_height'] ?? 35,
                'arrow_bg_color' => $defaults['arrow_bg_color'] ?? '#111111',
                'arrow_hover_bg_color' => $defaults['arrow_hover_bg_color'] ?? '#000000',
                'arrow_icon_color' => $defaults['arrow_icon_color'] ?? '#ffffff',
                'arrow_icon_hover_color' => $defaults['arrow_icon_hover_color'] ?? '#ffffff',
                'arrow_icon_size' => $defaults['arrow_icon_size'] ?? 28,
                'pagination_dot_color' => $defaults['pagination_dot_color'] ?? '#cfcfcf',
                'pagination_active_dot_color' => $defaults['pagination_active_dot_color'] ?? '#111111',
                'pagination_dot_gap' => $defaults['pagination_dot_gap'] ?? 10,
                'pagination_dot_size' => $defaults['pagination_dot_size'] ?? 8,
                'active_slide_border_color' => $defaults['active_slide_border_color'] ?? '#0073aa',
                'darken_inactive_slides' => $defaults['darken_inactive_slides'] ?? 1,
                'order' => $defaults['order'] ?? 'menu_order'
            ], $atts, 'typeform_quizzes_slider');

            // Validate and sanitize inputs with proper bounds checking
            $max_quizzes = min(max(intval($atts['max']), 1), self::MAX_QUIZZES_LIMIT);
            $max_width = min(max(intval($atts['max_width']), 200), 2000);
            $thumb_height = min(max(intval($atts['thumb_height']), 50), 1000);
            
            // Validate column settings
            $cols_desktop = min(max(intval($atts['cols_desktop']), 1), 12);
            $cols_tablet = min(max(intval($atts['cols_tablet']), 1), 8);
            $cols_mobile = min(max(intval($atts['cols_mobile']), 1), 4);
            
            $gap = min(max(intval($atts['gap']), 0), 100);
            $center_on_click = (bool) $atts['center_on_click'];
            
            // Validate order parameter
            $valid_orders = ['menu_order', 'date', 'title', 'rand'];
            $order = in_array($atts['order'], $valid_orders) ? $atts['order'] : 'menu_order';

            // Get quizzes
            $quizzes = self::get_quizzes($max_quizzes, $order);
            
            if (empty($quizzes)) {
                return self::render_error('No quizzes found. Please add some Typeform Quizzes first.');
            }

            // Generate unique ID for this slider instance
            $slider_id = 'tfq-slider-' . uniqid();
            
            // Enqueue scripts and styles
            self::enqueue_slider_assets($atts);

            // Render the slider
            return self::render_quizzes_slider_html($quizzes, $slider_id, $atts, $max_width, $thumb_height, $cols_desktop, $cols_tablet, $cols_mobile, $gap, $center_on_click);

        } catch (Exception $e) {
            self::log_error('Quiz slider error: ' . $e->getMessage());
            return self::render_error('An error occurred while loading the quiz slider.');
        }
    }

    /**
     * Get quizzes from database
     */
    private static function get_quizzes($max_quizzes, $order) {

        // Check if post type exists
        if (!post_type_exists('typeform_quiz')) {
            return [];
        }
        
        
        // First, try to get all published quizzes
        $args = [
            'post_type' => 'typeform_quiz',
            'post_status' => 'publish',
            'posts_per_page' => $max_quizzes,
        ];

        switch ($order) {
            case 'date':
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
                break;
            case 'title':
                $args['orderby'] = 'title';
                $args['order'] = 'ASC';
                break;
            case 'rand':
                $args['orderby'] = 'rand';
                break;
            case 'menu_order':
            default:
                // For custom order, use menu_order field (WordPress standard)
                $args['orderby'] = 'menu_order';
                $args['order'] = 'ASC';
                break;
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Typeform Quizzes: Query args for get_quizzes: ' . print_r($args, true));
        }
        
        $quizzes_query = new WP_Query($args);
        $quizzes = [];

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Typeform Quizzes: Found ' . $quizzes_query->found_posts . ' quizzes with order: ' . $order);
        }

        if ($quizzes_query->have_posts()) {
            while ($quizzes_query->have_posts()) {
                $quizzes_query->the_post();
                $quiz_id = get_the_ID();
                $typeform_url = get_post_meta($quiz_id, '_typeform_url', true);
                
                // Check alternative meta field names for backward compatibility
                if (empty($typeform_url)) {
                    $alt_url = get_post_meta($quiz_id, 'typeform_url', true);
                    if (!empty($alt_url)) {
                        $typeform_url = $alt_url;
                    }
                }
                
                // Validate URL before adding to results
                if (!empty($typeform_url) && self::is_valid_typeform_url($typeform_url)) {
                    $quizzes[] = [
                        'id' => $quiz_id,
                        'title' => get_the_title(),
                        'excerpt' => get_the_excerpt(),
                        'url' => $typeform_url,
                        'thumbnail' => get_the_post_thumbnail_url($quiz_id, 'medium'),
                        'permalink' => get_permalink()
                    ];
                }
            }
            wp_reset_postdata();
        }

        // If no quizzes found, try fallback query for backward compatibility
        if (empty($quizzes)) {
            $fallback_args = [
                'post_type' => 'typeform_quiz',
                'post_status' => 'publish',
                'posts_per_page' => $max_quizzes,
            ];
            
            $fallback_query = new WP_Query($fallback_args);
            
            if ($fallback_query->have_posts()) {
                while ($fallback_query->have_posts()) {
                    $fallback_query->the_post();
                    $quiz_id = get_the_ID();
                    $title = get_the_title();
                    
                    // Check for various possible meta keys for backward compatibility
                    $typeform_url = get_post_meta($quiz_id, '_typeform_url', true);
                    if (empty($typeform_url)) {
                        $typeform_url = get_post_meta($quiz_id, 'typeform_url', true);
                    }
                    if (empty($typeform_url)) {
                        $typeform_url = get_post_meta($quiz_id, 'typeform_quiz_url', true);
                    }
                    
                    // Validate URL before adding to results
                    if (!empty($typeform_url) && self::is_valid_typeform_url($typeform_url)) {
                        $quizzes[] = [
                            'id' => $quiz_id,
                            'title' => $title,
                            'excerpt' => get_the_excerpt(),
                            'url' => $typeform_url,
                            'thumbnail' => get_the_post_thumbnail_url($quiz_id, 'medium'),
                            'permalink' => get_permalink()
                        ];
                    }
                }
                wp_reset_postdata();
            }
        }

        
        return $quizzes;
    }

    /**
     * Get embed URL for Typeform
     */
    private static function get_embed_url($url) {
        if (empty($url)) {
            return '';
        }
        
        // Check if we're in a development environment
        $is_local_dev = self::is_local_development();
        
        // Add embed parameters for better display
        $params = [
            'embed' => 'true',
            'embed-hide-headers' => 'true',
            'embed-hide-footer' => 'true',
            'embed-opacity' => '100',
            'typeform-embed' => 'popup-blank',
            'embed-open' => 'true'
        ];
        
        // For local development, add additional parameters to help with CSP issues
        if ($is_local_dev) {
            $params['embed-opacity'] = '100';
            $params['typeform-embed'] = 'popup-blank';
        }
        
        $query_string = http_build_query($params);
        
        if (strpos($url, '?') === false) {
            return $url . '?' . $query_string;
        } else {
            return $url . '&' . $query_string;
        }
    }
    
    /**
     * Check if we're in a local development environment
     */
    private static function is_local_development() {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $is_local = (
            strpos($host, 'localhost') !== false ||
            strpos($host, '127.0.0.1') !== false ||
            strpos($host, '192.168.') !== false ||
            strpos($host, '10.0.') !== false ||
            strpos($host, '.local') !== false ||
            strpos($host, '.test') !== false ||
            strpos($host, '.dev') !== false ||
            (defined('WP_DEBUG') && WP_DEBUG) ||
            (defined('WP_ENV') && WP_ENV === 'development')
        );
        
        return $is_local;
    }

    /**
     * Check for page builder conflicts and adjust loading accordingly
     */
    private static function check_page_builder_compatibility() {
        $is_elementor = defined('ELEMENTOR_VERSION');
        $is_divi = defined('ET_BUILDER_VERSION');
        $is_beaver = class_exists('FLBuilder');
        $is_wp_bakery = defined('WPB_VC_VERSION');
        
        return [
            'is_elementor' => $is_elementor,
            'is_divi' => $is_divi,
            'is_beaver' => $is_beaver,
            'is_wp_bakery' => $is_wp_bakery,
            'has_page_builder' => $is_elementor || $is_divi || $is_beaver || $is_wp_bakery
        ];
    }

    /**
     * Check hosting platform compatibility and apply optimizations
     */
    private static function check_hosting_compatibility() {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $server_software = $_SERVER['SERVER_SOFTWARE'] ?? '';
        
        // WP Engine detection
        $is_wp_engine = defined('WPE_PLUGIN_BASE') || 
                       strpos($host, 'wpengine') !== false || 
                       strpos($host, 'wpe') !== false ||
                       defined('WPE_APIKEY') ||
                       (defined('WP_DEBUG') && strpos($server_software, 'WP Engine') !== false);
        
        // EasyWP detection
        $is_easywp = strpos($host, 'easywp') !== false || 
                    defined('EASYWP_VERSION') ||
                    defined('EASYWP_PLUGIN_BASE') ||
                    strpos($host, 'namecheap') !== false;
        
        // Cloudflare detection
        $is_cloudflare = isset($_SERVER['HTTP_CF_RAY']) || 
                        isset($_SERVER['HTTP_CF_CONNECTING_IP']) ||
                        strpos($host, 'cloudflare') !== false;
        
        // Kinsta detection
        $is_kinsta = defined('KINSTAMU_PLUGIN_URL') || 
                    strpos($host, 'kinsta') !== false ||
                    strpos($server_software, 'Kinsta') !== false;
        
        // SiteGround detection
        $is_siteground = defined('SG_CACHE_PATH') || 
                        strpos($host, 'siteground') !== false ||
                        strpos($server_software, 'SiteGround') !== false;
        
        // GoDaddy detection
        $is_godaddy = strpos($host, 'godaddy') !== false || 
                     strpos($host, 'secureserver') !== false ||
                     defined('GD_SYSTEM_PLUGIN_DIR');
        
        // Bluehost detection
        $is_bluehost = strpos($host, 'bluehost') !== false || 
                      strpos($host, 'bluehost.com') !== false ||
                      defined('BLUEHOST_PLUGIN_DIR');
        
        // HostGator detection
        $is_hostgator = strpos($host, 'hostgator') !== false || 
                       strpos($host, 'hostgator.com') !== false;
        
        // Generic CDN detection
        $is_cdn = $is_cloudflare || 
                 strpos($host, 'cdn') !== false ||
                 strpos($host, 'cloudfront') !== false ||
                 strpos($host, 'maxcdn') !== false ||
                 strpos($host, 'keycdn') !== false;
        
        // Shared hosting detection
        $is_shared_hosting = $is_godaddy || $is_bluehost || $is_hostgator || 
                           strpos($host, 'shared') !== false ||
                           strpos($host, 'cpanel') !== false;
        
        // Managed WordPress hosting
        $is_managed_wp = $is_wp_engine || $is_easywp || $is_kinsta || $is_siteground;
        
        return [
            'is_wp_engine' => $is_wp_engine,
            'is_easywp' => $is_easywp,
            'is_cloudflare' => $is_cloudflare,
            'is_kinsta' => $is_kinsta,
            'is_siteground' => $is_siteground,
            'is_godaddy' => $is_godaddy,
            'is_bluehost' => $is_bluehost,
            'is_hostgator' => $is_hostgator,
            'is_cdn' => $is_cdn,
            'is_shared_hosting' => $is_shared_hosting,
            'is_managed_wp' => $is_managed_wp,
            'needs_optimization' => $is_managed_wp || $is_shared_hosting || $is_cdn,
            'platform' => self::get_hosting_platform_name($is_wp_engine, $is_easywp, $is_kinsta, $is_siteground, $is_godaddy, $is_bluehost, $is_hostgator)
        ];
    }
    
    /**
     * Get hosting platform name for debugging
     */
    private static function get_hosting_platform_name($wp_engine, $easywp, $kinsta, $siteground, $godaddy, $bluehost, $hostgator) {
        if ($wp_engine) return 'WP Engine';
        if ($easywp) return 'EasyWP';
        if ($kinsta) return 'Kinsta';
        if ($siteground) return 'SiteGround';
        if ($godaddy) return 'GoDaddy';
        if ($bluehost) return 'Bluehost';
        if ($hostgator) return 'HostGator';
        return 'Unknown';
    }
    
    /**
     * Get hosting-specific optimizations
     */
    private static function get_hosting_optimizations($hosting) {
        $css = '';
        
        if (!$hosting['needs_optimization']) {
            return $css;
        }
        
        $css .= '
        /* Hosting platform optimizations for ' . $hosting['platform'] . ' */
        .typeform-quizzes-slider-container {
            will-change: transform;
            transform: translateZ(0);
        }';
        
        // WP Engine specific optimizations
        if ($hosting['is_wp_engine']) {
            $css .= '
            .typeform-quizzes-slider-container {
                contain: layout style paint;
                backface-visibility: hidden;
                perspective: 1000px;
            }';
        }
        
        // EasyWP specific optimizations
        if ($hosting['is_easywp']) {
            $css .= '
            .typeform-quizzes-slider-container {
                contain: layout style;
                will-change: auto;
            }';
        }
        
        // Shared hosting optimizations
        if ($hosting['is_shared_hosting']) {
            $css .= '
            .typeform-quizzes-slider-container {
                contain: layout style;
                transform: none;
            }
            .typeform-quizzes-slider {
                contain: layout style;
            }';
        }
        
        // CDN optimizations
        if ($hosting['is_cdn']) {
            $css .= '
            .typeform-quizzes-slider-container {
                will-change: transform;
                transform: translate3d(0, 0, 0);
            }';
        }
        
        return $css;
    }

    /**
     * Enqueue slider assets with fallback handling
     */
    private static function enqueue_slider_assets($atts = []) {
        wp_enqueue_script('jquery');
        
        // Check for page builder and hosting compatibility
        $compatibility = self::check_page_builder_compatibility();
        $hosting = self::check_hosting_compatibility();
        
        // Check if Swiper is already enqueued to prevent conflicts
        $swiper_handles = ['swiper', 'swiper-bundle', 'swiper-js', 'swiper-css', 'typeform-quizzes-swiper'];
        $swiper_js_enqueued = false;
        $swiper_css_enqueued = false;
        
        foreach ($swiper_handles as $handle) {
            if (wp_script_is($handle, 'enqueued') || wp_script_is($handle, 'done')) {
                $swiper_js_enqueued = true;
            }
            if (wp_style_is($handle, 'enqueued') || wp_style_is($handle, 'done')) {
                $swiper_css_enqueued = true;
            }
        }
        
        // Additional check for global Swiper object
        if (!$swiper_js_enqueued && !wp_script_is('swiper', 'registered')) {
            // Check if Swiper is loaded globally (common with page builders)
            add_action('wp_footer', function() {
                if (wp_script_is('typeform-quizzes-swiper', 'enqueued')) {
                    echo '<script>if (typeof Swiper === "undefined" && typeof window.Swiper === "undefined") { console.warn("Typeform Quizzes: Swiper not found, slider may not work properly"); }</script>';
                }
            }, 999);
        }
        
        // Only enqueue Swiper if not already loaded
        if (!$swiper_js_enqueued) {
            $swiper_js_url = 'https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.js';
            $fallback_url = 'https://unpkg.com/swiper@8/swiper-bundle.min.js';
            
            wp_enqueue_script('typeform-quizzes-swiper', $swiper_js_url, ['jquery'], '8.0.0', true);
            wp_script_add_data('typeform-quizzes-swiper', 'defer', true);
            
            // Add fallback for hosting platforms that might block CDN
            if ($hosting['needs_optimization']) {
                wp_script_add_data('typeform-quizzes-swiper', 'integrity', 'sha384-...');
                wp_script_add_data('typeform-quizzes-swiper', 'crossorigin', 'anonymous');
            }
        }
        
        if (!$swiper_css_enqueued) {
            $swiper_css_url = 'https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.css';
            wp_enqueue_style('typeform-quizzes-swiper', $swiper_css_url, [], '8.0.0');
            wp_style_add_data('typeform-quizzes-swiper', 'defer', true);
        }
        
        // Enqueue custom CSS file after swiper styles
        $custom_css_url = plugin_dir_url(__FILE__) . 'assets/css/typeform-quizzes-custom.css';
        wp_enqueue_style('typeform-quizzes-custom', $custom_css_url, ['typeform-quizzes-swiper'], '1.0.0');
        
        // Enqueue custom JavaScript file
        $custom_js_url = plugin_dir_url(__FILE__) . 'assets/js/typeform-quizzes.js';
        wp_enqueue_script('typeform-quizzes-custom', $custom_js_url, ['jquery', 'typeform-quizzes-swiper'], '1.0.0', true);
        
        // Add CSS variables for custom styles
        self::add_custom_css_variables($atts);
        
        // Add fallback handling via JavaScript
        if (!$swiper_js_enqueued) {
            wp_add_inline_script('typeform-quizzes-swiper', '
                // Fallback for Swiper if CDN fails
                if (typeof Swiper === "undefined") {
                    console.warn("Typeform Quizzes: Swiper CDN failed to load, using fallback");
                    // You could implement a local fallback here if needed
                }
            ', 'after');
        }
        
        // Add CSS scoping to prevent conflicts with other plugins
        add_action('wp_head', function() use ($compatibility, $hosting) {
            $optimizations = self::get_hosting_optimizations($hosting);
            echo '<style id="typeform-quizzes-conflict-prevention">
            /* Typeform Quizzes - Conflict Prevention CSS */
            .typeform-quizzes-slider-container {
                isolation: isolate;
                contain: layout style;
                position: relative;
                z-index: 1;
            }
            .typeform-quizzes-slider {
                all: initial;
                font-family: inherit;
                position: relative;
                z-index: 1;
            }
            .typeform-quizzes-slider * {
                box-sizing: border-box;
            }
            ' . $optimizations . '
            </style>';
        }, 1);
        
        // Enqueue Font Awesome if not already loaded
        self::enqueue_font_awesome();
        
        // Add performance optimizations
        self::add_performance_optimizations();
    }
    
    /**
     * Add performance optimizations
     */
    private static function add_performance_optimizations() {
        // Add lazy loading for images
        add_filter('wp_get_attachment_image_attributes', [__CLASS__, 'add_lazy_loading'], 10, 3);
        
        // Optimize Swiper loading
        add_action('wp_footer', [__CLASS__, 'optimize_swiper_loading'], 1);
        
        // Add resource hints for better performance
        add_action('wp_head', [__CLASS__, 'add_resource_hints'], 1);
    }
    
    /**
     * Add lazy loading to images
     */
    public static function add_lazy_loading($attr, $attachment, $size) {
        // Only add lazy loading to quiz thumbnails
        if (isset($attr['class']) && strpos($attr['class'], 'quiz-thumbnail') !== false) {
            $attr['loading'] = 'lazy';
            $attr['decoding'] = 'async';
        }
        return $attr;
    }
    
    /**
     * Optimize Swiper loading
     */
    public static function optimize_swiper_loading() {
        if (wp_script_is('typeform-quizzes-swiper', 'enqueued')) {
            echo '<script>
            // Optimize Swiper initialization
            document.addEventListener("DOMContentLoaded", function() {
                // Preload Swiper if not already loaded
                if (typeof Swiper === "undefined") {
                    const link = document.createElement("link");
                    link.rel = "preload";
                    link.href = "https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.js";
                    link.as = "script";
                    document.head.appendChild(link);
                }
            });
            </script>';
        }
    }
    
    /**
     * Add resource hints for better performance
     */
    public static function add_resource_hints() {
        if (wp_script_is('typeform-quizzes-swiper', 'enqueued')) {
            echo '<link rel="dns-prefetch" href="//cdn.jsdelivr.net">';
            echo '<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>';
        }
        if (wp_style_is('typeform-quizzes-fontawesome', 'enqueued')) {
            echo '<link rel="dns-prefetch" href="//cdnjs.cloudflare.com">';
            echo '<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>';
        }
    }
    
    /**
     * Enqueue Font Awesome if not already loaded with fallback handling
     */
    private static function enqueue_font_awesome() {
        // Check if Font Awesome is already enqueued
        $fa_handles = ['font-awesome', 'fontawesome', 'font-awesome-5', 'font-awesome-6', 'font-awesome-4', 'fa', 'typeform-quizzes-fontawesome'];
        $fa_enqueued = false;
        
        foreach ($fa_handles as $handle) {
            if (wp_style_is($handle, 'enqueued') || wp_style_is($handle, 'done')) {
                $fa_enqueued = true;
                break;
            }
        }
        
        // If Font Awesome is not enqueued, enqueue it with fallback
        if (!$fa_enqueued) {
            $fa_url = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
            wp_enqueue_style('typeform-quizzes-fontawesome', $fa_url, [], '6.4.0');
            wp_style_add_data('typeform-quizzes-fontawesome', 'defer', true);
            
            // Add fallback CSS for critical icons
            wp_add_inline_style('typeform-quizzes-fontawesome', '
                /* Fallback for critical Font Awesome icons */
                .typeform-quizzes-arrow-left::before,
                .typeform-quizzes-arrow-right::before {
                    content: "‹";
                    font-family: Arial, sans-serif;
                    font-weight: bold;
                }
                .typeform-quizzes-arrow-right::before {
                    content: "›";
                }
            ');
        }
    }

    /**
     * Render quizzes slider HTML
     */
    private static function render_quizzes_slider_html($quizzes, $slider_id, $atts, $max_width, $thumb_height, $cols_desktop, $cols_tablet, $cols_mobile, $gap, $center_on_click) {
        $border_radius = intval($atts['border_radius']);
        $title_color = sanitize_hex_color($atts['title_color']);
        $title_hover_color = sanitize_hex_color($atts['title_hover_color']);
        $controls_spacing = intval($atts['controls_spacing']);
        $controls_spacing_tablet = intval($atts['controls_spacing_tablet']);
        $controls_bottom_spacing = intval($atts['controls_bottom_spacing']);
        $arrow_border_radius = intval($atts['arrow_border_radius']);
        $arrow_padding = intval($atts['arrow_padding']);
        $arrow_width = intval($atts['arrow_width']);
        $arrow_height = intval($atts['arrow_height']);
        $arrow_bg_color = sanitize_hex_color($atts['arrow_bg_color']);
        $arrow_hover_bg_color = sanitize_hex_color($atts['arrow_hover_bg_color']);
        $arrow_icon_color = sanitize_hex_color($atts['arrow_icon_color']);
        $arrow_icon_hover_color = sanitize_hex_color($atts['arrow_icon_hover_color']);
        $arrow_icon_size = intval($atts['arrow_icon_size']);
        $pagination_dot_color = sanitize_hex_color($atts['pagination_dot_color']);
        $pagination_active_dot_color = sanitize_hex_color($atts['pagination_active_dot_color']);
        $pagination_dot_gap = min(max(intval($atts['pagination_dot_gap']), 0), 50);
        $pagination_dot_size = min(max(intval($atts['pagination_dot_size']), 4), 20);
        $active_slide_border_color = sanitize_hex_color($atts['active_slide_border_color']);
        $darken_inactive_slides = intval($atts['darken_inactive_slides']);

        // Set thumbnail height
        $thumb_height_css = $thumb_height . 'px';

        // Suppress any potential output before HTML
        ob_start();
        ?>
        <div class="typeform-quizzes-slider-container" 
             style="max-width: <?php echo $max_width; ?>px; margin: 0 auto;"
             data-cols-desktop="<?php echo $cols_desktop; ?>"
             data-cols-tablet="<?php echo $cols_tablet; ?>"
             data-cols-mobile="<?php echo $cols_mobile; ?>"
             data-gap="<?php echo $gap; ?>"
             data-center-on-click="<?php echo $center_on_click ? 'true' : 'false'; ?>"
             data-pagination-dot-color="<?php echo esc_attr($pagination_dot_color); ?>"
             data-pagination-active-dot-color="<?php echo esc_attr($pagination_active_dot_color); ?>"
             data-pagination-dot-size="<?php echo $pagination_dot_size; ?>"
             data-pagination-dot-gap="<?php echo $pagination_dot_gap; ?>">
            <!-- Quiz viewer - now at the top -->
            <div class="quiz-viewer">
                <div class="quiz-viewer-header">
                    <h2 class="quiz-viewer-title"><?php echo esc_html($quizzes[0]['title']); ?></h2>
                    <button class="quiz-viewer-close">×</button>
                </div>
                <div class="quiz-viewer-content">
                    <div style="position: absolute; top: 0; left: 0; right: 0; height: 4px;"></div>
                    <?php if (self::is_local_development()): ?>
                    <div id="dev-notice" style="background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 10px; margin-bottom: 10px; border-radius: 4px; font-size: 14px;">
                        <strong>Development Notice:</strong> Typeform CSP may block embedding in local development. 
                        <a href="<?php echo esc_url($quizzes[0]['url']); ?>" target="_blank" style="color: #0073aa; text-decoration: underline;">Click here to open in new tab</a>
                    </div>
                    <?php endif; ?>
                    <iframe id="quiz-iframe" 
                            src="<?php echo esc_url(self::get_embed_url($quizzes[0]['url'])); ?>" 
                            width="100%" 
                            height="600px" 
                            frameborder="0" 
                            style="border: none; display: block; background: white;"
                            allow="camera; microphone; geolocation; autoplay; encrypted-media; fullscreen; payment"
                            sandbox="allow-same-origin allow-scripts allow-forms allow-popups allow-popups-to-escape-sandbox allow-top-navigation-by-user-activation"
                            loading="lazy"
                            referrerpolicy="strict-origin-when-cross-origin"
                            onerror="this.style.display='none'; document.getElementById('dev-notice').style.display='block';">
                    </iframe>
                    <!-- Expand/Fullscreen Toggle Button -->
                    <button class="quiz-viewer-expand" style="
                        position: absolute;
                        bottom: 15px;
                        right: 15px;
                        width: 40px;
                        height: 40px;
                        background: rgba(0, 0, 0, 0.7);
                        border: none;
                        border-radius: 50%;
                        color: white;
                        cursor: pointer;
                        font-size: 16px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        transition: all 0.3s ease;
                        z-index: 10;
                    " title="Expand to fullscreen">
                        <i class="fa-solid fa-expand"></i>
                    </button>
                </div>
            </div>
            
            <div class="typeform-quizzes-slider swiper" id="<?php echo esc_attr($slider_id); ?>">
                <div class="swiper-wrapper">
                    <?php foreach ($quizzes as $index => $quiz): ?>
                        <div class="swiper-slide typeform-quiz-slide <?php echo $index === 0 ? 'active' : ''; ?>" data-quiz-url="<?php echo esc_url($quiz['url']); ?>" data-quiz-title="<?php echo esc_attr($quiz['title']); ?>">
                            <div class="quiz-thumbnail">
                                <?php if ($quiz['thumbnail']): ?>
                                    <img src="<?php echo esc_url($quiz['thumbnail']); ?>" 
                                         alt="<?php echo esc_attr($quiz['title']); ?>">
                                <?php else: ?>
                                    <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; position: absolute; top: 0; left: 0;">
                                        📝
                                    </div>
                                <?php endif; ?>
                                <div class="quiz-overlay">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </div>
                            </div>
                            <h3 class="quiz-title">
                                <?php echo esc_html($quiz['title']); ?>
                            </h3>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Navigation controls below the slider -->
            <div class="slider-controls">                
                <!-- All controls on the same row -->
                <div class="controls-row">
                    <button class="tfqrow-arrow tfqrow-arrow-prev" data-dir="prev" aria-label="Previous" data-bg-color="<?php echo $arrow_bg_color; ?>" data-hover-bg-color="<?php echo $arrow_hover_bg_color; ?>" data-icon-color="<?php echo $arrow_icon_color; ?>" data-hover-icon-color="<?php echo $arrow_icon_hover_color; ?>">
                        <i class="fa-solid fa-angle-left"></i>
                    </button>

                    <!-- Pagination dots centered -->
                    <div class="pagination-container">
                        <div class="swiper-pagination tfqpagination"></div>
                    </div>
                    
                    <button class="tfqrow-arrow tfqrow-arrow-next" data-dir="next" aria-label="Next" data-bg-color="<?php echo $arrow_bg_color; ?>" data-hover-bg-color="<?php echo $arrow_hover_bg_color; ?>" data-icon-color="<?php echo $arrow_icon_color; ?>" data-hover-icon-color="<?php echo $arrow_icon_hover_color; ?>">
                        <i class="fa-solid fa-angle-right"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Inline styles moved to typeform-quizzes-custom.css -->

        <!-- Inline JavaScript moved to typeform-quizzes.js -->
        <?php
        $output = ob_get_clean();
        
        // Clean any potential PHP errors/warnings from output
        $output = preg_replace('/^.*?(<div class="typeform-quizzes-slider-container")/s', '$1', $output);
        
        return $output;
    }

    /**
     * Render the slider shortcode - REMOVED
     */
    public static function render_slider_removed($atts) {
        return '<div class="notice notice-warning"><p>This functionality has been removed from this plugin. Please use the Typeform Quizzes slider instead.</p></div>';
    }
    

    /**
     * Get data - REMOVED
     */
    private static function get_data_removed($id, $max_items = 18, $cache_ttl = 86400, $api_key = '', $thumb_quality = 'medium', $order = 'date') {
        return [];
    }
    
    /**
     * Add CSS variables for custom styles
     * @param array $atts Shortcode attributes (optional)
     */
    private static function add_custom_css_variables($atts = []) {
        // Get defaults from admin settings
        $defaults = get_option('typeform_quizzes_defaults', []);
        
        // Merge shortcode attributes with defaults
        $atts = shortcode_atts([
            'thumb_height' => $defaults['thumb_height'] ?? 200,
            'arrow_icon_size' => $defaults['arrow_icon_size'] ?? 16,
            'arrow_icon_color' => $defaults['arrow_icon_color'] ?? '#333333',
            'arrow_icon_hover_color' => $defaults['arrow_icon_hover_color'] ?? '#666666',
            'arrow_bg_color' => $defaults['arrow_bg_color'] ?? '#ffffff',
            'arrow_hover_bg_color' => $defaults['arrow_hover_bg_color'] ?? '#f0f0f0',
            'arrow_width' => $defaults['arrow_width'] ?? 40,
            'arrow_height' => $defaults['arrow_height'] ?? 40,
            'arrow_border_radius' => $defaults['arrow_border_radius'] ?? 4,
            'title_color' => $defaults['title_color'] ?? '#333333',
            'title_hover_color' => $defaults['title_hover_color'] ?? '#666666',
            'active_slide_border_color' => $defaults['active_slide_border_color'] ?? '#007cba',
            'border_radius' => $defaults['border_radius'] ?? 8,
            'controls_spacing' => $defaults['controls_spacing'] ?? 20,
            'controls_spacing_tablet' => $defaults['controls_spacing_tablet'] ?? 15,
            'controls_bottom_spacing' => $defaults['controls_bottom_spacing'] ?? 20,
            'pagination_dot_color' => $defaults['pagination_dot_color'] ?? '#cccccc',
            'pagination_active_dot_color' => $defaults['pagination_active_dot_color'] ?? '#007cba',
            'pagination_dot_size' => $defaults['pagination_dot_size'] ?? 8,
            'pagination_dot_gap' => $defaults['pagination_dot_gap'] ?? 8,
        ], $atts);
        
        // Set values from merged attributes
        $thumb_height = $atts['thumb_height'];
        $arrow_icon_size = $atts['arrow_icon_size'];
        $arrow_icon_color = $atts['arrow_icon_color'];
        $arrow_icon_hover_color = $atts['arrow_icon_hover_color'];
        $arrow_bg_color = $atts['arrow_bg_color'];
        $arrow_hover_bg_color = $atts['arrow_hover_bg_color'];
        $arrow_width = $atts['arrow_width'];
        $arrow_height = $atts['arrow_height'];
        $arrow_border_radius = $atts['arrow_border_radius'];
        $title_color = $atts['title_color'];
        $title_hover_color = $atts['title_hover_color'];
        $active_slide_border_color = $atts['active_slide_border_color'];
        $border_radius = $atts['border_radius'];
        $controls_spacing = $atts['controls_spacing'];
        $controls_spacing_tablet = $atts['controls_spacing_tablet'];
        $controls_bottom_spacing = $atts['controls_bottom_spacing'];
        $pagination_dot_color = $atts['pagination_dot_color'];
        $pagination_active_dot_color = $atts['pagination_active_dot_color'];
        $pagination_dot_size = $atts['pagination_dot_size'];
        $pagination_dot_gap = $atts['pagination_dot_gap'];
        
        // Generate CSS for thumb height
        $thumb_height_css = is_numeric($thumb_height) ? $thumb_height . 'px' : $thumb_height;
        
        // Add inline CSS with variables
        $custom_css = "
        .typeform-quizzes-slider-container {
            --tfq-thumb-height: {$thumb_height_css};
            --tfq-arrow-icon-size: {$arrow_icon_size}px;
            --tfq-arrow-icon-color: {$arrow_icon_color};
            --tfq-arrow-icon-hover-color: {$arrow_icon_hover_color};
            --tfq-arrow-bg-color: {$arrow_bg_color};
            --tfq-arrow-hover-bg-color: {$arrow_hover_bg_color};
            --tfq-arrow-width: {$arrow_width}px;
            --tfq-arrow-height: {$arrow_height}px;
            --tfq-arrow-border-radius: {$arrow_border_radius}px;
            --tfq-arrow-padding-vertical: " . max(0, ($arrow_height - $arrow_icon_size) / 2) . "px;
            --tfq-arrow-padding-horizontal: " . max(0, ($arrow_width - $arrow_icon_size) / 2) . "px;
            --tfq-title-color: {$title_color};
            --tfq-title-hover-color: {$title_hover_color};
            --tfq-active-slide-border-color: {$active_slide_border_color};
            --tfq-border-radius: {$border_radius}px;
            --tfq-controls-spacing: {$controls_spacing}px;
            --tfq-controls-spacing-tablet: {$controls_spacing_tablet}px;
            --tfq-controls-bottom-spacing: {$controls_bottom_spacing}px;
            --tfq-pagination-dot-color: {$pagination_dot_color};
            --tfq-pagination-active-dot-color: {$pagination_active_dot_color};
            --tfq-pagination-dot-size: {$pagination_dot_size}px;
            --tfq-pagination-dot-gap: {$pagination_dot_gap}px;
        }";
        
        // Add darken inactive slides override if needed
        $darken_inactive_slides = $atts['darken_inactive_slides'] ?? true;
        if (!$darken_inactive_slides) {
            $custom_css .= "
            .typeform-quizzes-slider .quiz-thumbnail::before {
                opacity: 0 !important;
            }";
        }
        
        wp_add_inline_style('typeform-quizzes-custom', $custom_css);
    }
}

// Initialize the plugin
Typeform_Quizzes::init();

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
