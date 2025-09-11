<?php
/**
 * Quiz Repository for Typeform Quizzes
 * 
 * @package Typeform_Quizzes
 * @version 1.1.0
 * @author Making The Impact LLC
 */

namespace MTI\TypeformQuizzes\Frontend\Repository;

use WP_Query;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

/**
 * Quiz Repository Class
 * 
 * Handles read-only quiz data retrieval from the database.
 */
final class QuizRepository
{
    /**
     * Get quizzes from database
     * 
     * @param int $max Maximum number of quizzes to retrieve
     * @param string $order Order of quizzes
     * @return array Array of quiz data
     */
    public static function get_quizzes(int $max, string $order): array
    {
        // Check if post type exists
        if (!post_type_exists('typeform_quiz')) {
            return [];
        }

        // Validate order parameter
        $valid_orders = ['menu_order', 'date', 'title', 'rand'];
        $order = in_array($order, $valid_orders) ? $order : 'menu_order';

        // For custom order (menu_order), we need to handle both menu_order and _quiz_order meta
        if ($order === 'menu_order') {
            return self::get_quizzes_custom_order($max);
        }

        // Build query arguments for other order types
        $args = [
            'post_type' => 'typeform_quiz',
            'post_status' => 'publish',
            'posts_per_page' => ($max === -1 ? -1 : $max),
            'no_found_rows' => true,
            'ignore_sticky_posts' => true,
            'orderby' => $order,
            'order' => 'ASC'
        ];

        // Handle random order
        if ($order === 'rand') {
            $args['orderby'] = 'rand';
            unset($args['order']);
        }

        $quizzes_query = new WP_Query($args);

        // Safety: if some theme/plugin capped posts_per_page to 10 via pre_get_posts,
        // re-run with -1 and slice locally so we still honor your desired max.
        if ($max !== -1 &&
            $quizzes_query->post_count < $max &&
            $quizzes_query->found_posts > $quizzes_query->post_count) {

            $args['posts_per_page'] = -1;
            $quizzes_query = new WP_Query($args);
        }

        $quizzes = [];

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

        // Apply final limit if needed (for safety re-query case)
        if ($max !== -1 && count($quizzes) > $max) {
            $quizzes = array_slice($quizzes, 0, $max);
        }

        return $quizzes;
    }

    /**
     * Get quizzes with custom order handling both menu_order and _quiz_order
     * 
     * @param int $max_quizzes Maximum number of quizzes to retrieve
     * @return array Array of quiz posts
     */
    private static function get_quizzes_custom_order(int $max_quizzes): array
    {
        // Only sync if we haven't done it recently (avoid performance issues)
        $last_sync = get_transient('tfq_order_sync_complete');
        if (!$last_sync) {
            self::sync_quiz_order_to_menu_order();
            set_transient('tfq_order_sync_complete', true, HOUR_IN_SECONDS);
        }
        
        // Get all published quizzes ordered by menu_order
        $args = [
            'post_type' => 'typeform_quiz',
            'post_status' => 'publish',
            'posts_per_page' => ($max_quizzes === -1 ? -1 : $max_quizzes),
            'no_found_rows' => true,
            'ignore_sticky_posts' => true,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ];

        $quizzes_query = new WP_Query($args);

        // Safety: if some theme/plugin capped posts_per_page to 10 via pre_get_posts,
        // re-run with -1 and slice locally so we still honor your desired max.
        if ($max_quizzes !== -1 &&
            $quizzes_query->post_count < $max_quizzes &&
            $quizzes_query->found_posts > $quizzes_query->post_count) {

            $args['posts_per_page'] = -1;
            $quizzes_query = new WP_Query($args);
        }

        $quizzes = [];
        $total_found = $quizzes_query->found_posts;
        $valid_count = 0;
        $invalid_count = 0;

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
                    $valid_count++;
                } else {
                    $invalid_count++;
                }
            }
            wp_reset_postdata();
        }

        // Apply final limit if needed (for safety re-query case)
        if ($max_quizzes !== -1 && count($quizzes) > $max_quizzes) {
            $quizzes = array_slice($quizzes, 0, $max_quizzes);
        }

        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("TFQ Debug - Custom Order: Total found: $total_found, Valid: $valid_count, Invalid: $invalid_count, Requested: $max_quizzes, Returned: " . count($quizzes));
        }

        return $quizzes;
    }

    /**
     * Sync _quiz_order meta field values to menu_order for consistent ordering
     */
    private static function sync_quiz_order_to_menu_order(): void
    {
        // Get ALL published quizzes, not just those with _quiz_order meta
        $quizzes = get_posts([
            'post_type' => 'typeform_quiz',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'ID',
            'order' => 'ASC'
        ]);

        $used_orders = [];
        $updated_count = 0;
        
        foreach ($quizzes as $quiz) {
            $meta_order = get_post_meta($quiz->ID, '_quiz_order', true);
            $current_menu_order = $quiz->menu_order;
            
            // If _quiz_order exists and is different from menu_order, update menu_order
            if ($meta_order !== '' && $meta_order !== false && intval($meta_order) != $current_menu_order) {
                $order_value = intval($meta_order);
                
                // Ensure unique order values
                while (in_array($order_value, $used_orders)) {
                    $order_value++;
                }
                
                $used_orders[] = $order_value;
                
                // Update menu_order to match _quiz_order
                wp_update_post([
                    'ID' => $quiz->ID,
                    'menu_order' => $order_value
                ]);
                
                $updated_count++;
            } elseif ($meta_order === '' || $meta_order === false) {
                // If no _quiz_order meta exists, only fix if menu_order is 0 or duplicate
                if ($current_menu_order == 0) {
                    // Only assign new order if current is 0 (unset)
                    $order_value = max($used_orders) + 1;
                    while (in_array($order_value, $used_orders)) {
                        $order_value++;
                    }
                    
                    $used_orders[] = $order_value;
                    
                    wp_update_post([
                        'ID' => $quiz->ID,
                        'menu_order' => $order_value
                    ]);
                    
                    // Also set the _quiz_order meta to match
                    update_post_meta($quiz->ID, '_quiz_order', $order_value);
                    
                    $updated_count++;
                } else {
                    // Preserve existing valid menu_order values
                    $used_orders[] = $current_menu_order;
                }
            } else {
                $used_orders[] = $current_menu_order;
            }
        }
        
        // Fix any remaining duplicate order values
        self::fix_duplicate_order_values();
    }

    /**
     * Fix duplicate order values in menu_order to ensure consistent ordering
     */
    private static function fix_duplicate_order_values(): void
    {
        global $wpdb;
        
        // Get all quizzes ordered by menu_order
        $quizzes = get_posts([
            'post_type' => 'typeform_quiz',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ]);
        
        $used_orders = [];
        $duplicates_found = false;
        
        foreach ($quizzes as $quiz) {
            $current_order = $quiz->menu_order;
            
            // If this order value is already used, assign a new unique value
            if (in_array($current_order, $used_orders)) {
                $new_order = max($used_orders) + 1;
                
                // Find the next available order value
                while (in_array($new_order, $used_orders)) {
                    $new_order++;
                }
                
                // Update the post with the new order value
                wp_update_post([
                    'ID' => $quiz->ID,
                    'menu_order' => $new_order
                ]);
                
                $used_orders[] = $new_order;
                $duplicates_found = true;
            } else {
                $used_orders[] = $current_order;
            }
        }
        
        // If we found duplicates, also update the _quiz_order meta field to match
        if ($duplicates_found) {
            foreach ($quizzes as $quiz) {
                $menu_order = get_post($quiz->ID)->menu_order;
                update_post_meta($quiz->ID, '_quiz_order', $menu_order);
            }
        }
    }

    /**
     * Check if a URL is a valid Typeform URL
     * 
     * @param string $url URL to validate
     * @return bool True if valid, false otherwise
     */
    private static function is_valid_typeform_url(string $url): bool
    {
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
}
