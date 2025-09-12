<?php
/**
 * AJAX Reorder Handler
 * 
 * @package Typeform_Quizzes
 * @version 1.1.0
 * @author Making The Impact LLC
 */

namespace MTI\TypeformQuizzes\Api\Ajax;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

/**
 * Reorder AJAX Handler Class
 * 
 * Handles AJAX requests for quiz reordering functionality.
 */
final class Reorder
{
    /**
     * Initialize AJAX handlers
     */
    public static function init(): void
    {
        add_action('wp_ajax_tfq_reorder', [__CLASS__, 'handle']);
        add_action('wp_ajax_tfq_clear_cache', [__CLASS__, 'clear_cache']);
    }

    /**
     * Handle AJAX reorder request
     */
    public static function handle(): void
    {
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
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Typeform Quizzes AJAX: Found ' . count($quizzes) . ' quizzes');
                }
                
                $quiz_data = [];
                foreach ($quizzes as $quiz) {
                    $quiz_data[] = [
                        'id' => $quiz->ID,
                        'title' => $quiz->post_title,
                        'menu_order' => $quiz->menu_order,
                        'thumbnail' => get_the_post_thumbnail_url($quiz->ID, [50, 50]) ?: ''
                    ];
                }
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Typeform Quizzes AJAX: Quiz data: ' . print_r($quiz_data, true));
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
                
                // Start transaction
                $wpdb->query('START TRANSACTION');
                
                try {
                    $updated_count = 0;
                    
                    foreach ($order_data as $index => $quiz_id) {
                        $quiz_id = intval($quiz_id);
                        $new_order = $index + 1;
                        
                        if ($quiz_id <= 0) {
                            continue; // Skip invalid IDs
                        }
                        
                        // Update menu_order
                        $result = $wpdb->update(
                            $wpdb->posts,
                            ['menu_order' => $new_order],
                            [
                                'ID' => $quiz_id,
                                'post_type' => 'typeform_quiz'
                            ],
                            ['%d'],
                            ['%d', '%s']
                        );
                        
                        if ($result !== false) {
                            $updated_count++;
                            
                            // Also update _quiz_order meta for consistency
                            update_post_meta($quiz_id, '_quiz_order', $new_order);
                            
                            if (defined('WP_DEBUG') && WP_DEBUG) {
                                error_log("Typeform Quizzes AJAX: Updated quiz {$quiz_id} to order {$new_order}");
                            }
                        } else {
                            if (defined('WP_DEBUG') && WP_DEBUG) {
                                error_log("Typeform Quizzes AJAX: Failed to update quiz {$quiz_id}: " . $wpdb->last_error);
                            }
                        }
                    }
                    
                    // Commit transaction
                    $wpdb->query('COMMIT');
                    
                    // Clear caches after successful update
                    wp_cache_flush();
                    
                    // Clear any plugin-specific transients
                    $wpdb->query(
                        "DELETE FROM {$wpdb->options} 
                         WHERE option_name LIKE '_transient_tf_quizzes_%' 
                         OR option_name LIKE '_transient_timeout_tf_quizzes_%'"
                    );
                    
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("Typeform Quizzes AJAX: Successfully updated {$updated_count} quizzes");
                    }
                    
                    wp_send_json_success([
                        'message' => sprintf(__('Successfully updated %d quizzes', 'typeform-quizzes'), $updated_count),
                        'updated_count' => $updated_count,
                        'debug_info' => [
                            'total_quizzes' => count($order_data),
                            'updated_count' => $updated_count,
                            'wp_debug' => defined('WP_DEBUG') && WP_DEBUG
                        ]
                    ]);
                    
                } catch (Exception $e) {
                    // Rollback transaction
                    $wpdb->query('ROLLBACK');
                    
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('Typeform Quizzes AJAX: Transaction failed: ' . $e->getMessage());
                    }
                    
                    wp_send_json_error(__('Failed to update quiz order', 'typeform-quizzes'));
                }
            }
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Typeform Quizzes AJAX: Fatal error: ' . $e->getMessage());
                error_log('Typeform Quizzes AJAX: Stack trace: ' . $e->getTraceAsString());
            }
            wp_send_json_error(__('An unexpected error occurred', 'typeform-quizzes'));
        }
    }

    /**
     * Validate AJAX request
     * 
     * @return bool True if valid, false otherwise
     */
    private static function validate_ajax_request(): bool
    {
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
        
        if (!wp_verify_nonce($_POST['nonce'], 'tfq_reorder')) {
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
        
        return true;
    }

    /**
     * Clear caches after successful reorder
     */
    public static function clear_cache(): void
    {
        // Check if request is AJAX
        if (!wp_doing_ajax()) {
            wp_send_json_error(__('Invalid request method', 'typeform-quizzes'));
            return;
        }

        // Check if nonce exists before validating
        if (!isset($_POST['nonce'])) {
            wp_send_json_error(__('Missing security nonce', 'typeform-quizzes'));
            return;
        }

        if (!wp_verify_nonce($_POST['nonce'], 'tfq_reorder')) {
            wp_send_json_error(__('Invalid security nonce', 'typeform-quizzes'));
            return;
        }

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Insufficient permissions', 'typeform-quizzes'));
            return;
        }

        try {
            // Clear WordPress object cache
            wp_cache_flush();
            
            // Clear any plugin-specific transients
            global $wpdb;
            $wpdb->query(
                "DELETE FROM {$wpdb->options} 
                 WHERE option_name LIKE '_transient_tf_quizzes_%' 
                 OR option_name LIKE '_transient_timeout_tf_quizzes_%'"
            );
            
            // Clear any other caches that might affect quiz display
            if (function_exists('wp_cache_delete_group')) {
                wp_cache_delete_group('typeform_quizzes');
            }
            
            // Clear any Elementor cache if available
            if (class_exists('\Elementor\Plugin')) {
                \Elementor\Plugin::$instance->files_manager->clear_cache();
            }
            
            // Clear any other popular caching plugins
            if (function_exists('w3tc_flush_all')) {
                w3tc_flush_all();
            }
            
            if (function_exists('wp_cache_clear_cache')) {
                wp_cache_clear_cache();
            }
            
            if (function_exists('rocket_clean_domain')) {
                rocket_clean_domain();
            }
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Typeform Quizzes: Cache cleared successfully');
            }
            
            wp_send_json_success(['message' => __('Cache cleared successfully', 'typeform-quizzes')]);
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Typeform Quizzes: Cache clear error: ' . $e->getMessage());
            }
            wp_send_json_error(__('Failed to clear cache', 'typeform-quizzes'));
        }
    }
}
