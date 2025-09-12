<?php
/**
 * Migration Handler
 * 
 * @package Typeform_Quizzes
 * @version 1.1.0
 * @author Making The Impact LLC
 */

namespace MTI\TypeformQuizzes\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

/**
 * Migration Class
 * 
 * Handles data migration and cleanup functionality.
 */
class Migration
{
    /**
     * Initialize migration functionality
     */
    public static function init(): void
    {
        add_action('admin_init', [__CLASS__, 'migrate_order_data']);
        add_action('admin_init', [__CLASS__, 'fix_duplicate_order_values']);
    }

    /**
     * Migrate existing order data from meta field to menu_order
     */
    public static function migrate_order_data(): void
    {
        // Only run once
        if (get_option('typeform_quiz_order_migrated')) {
            return;
        }
        
        // Only run for admin users
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }
        
        global $wpdb;
        
        // Get all quizzes with _quiz_order meta
        $quizzes = $wpdb->get_results(
            "SELECT p.ID, p.menu_order, pm.meta_value as quiz_order 
             FROM {$wpdb->posts} p 
             LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_quiz_order'
             WHERE p.post_type = 'typeform_quiz' 
             AND p.post_status = 'publish'
             ORDER BY CAST(pm.meta_value AS UNSIGNED) ASC"
        );
        
        if (empty($quizzes)) {
            // Mark migration as complete even if no quizzes exist
            update_option('typeform_quiz_order_migrated', true);
            return;
        }
        
        $migrated_count = 0;
        
        foreach ($quizzes as $quiz) {
            $quiz_order = intval($quiz->quiz_order);
            $current_menu_order = intval($quiz->menu_order);
            
            // Only migrate if quiz_order exists and menu_order is 0 or empty
            if ($quiz_order > 0 && $current_menu_order === 0) {
                $result = $wpdb->update(
                    $wpdb->posts,
                    ['menu_order' => $quiz_order],
                    ['ID' => $quiz->ID],
                    ['%d'],
                    ['%d']
                );
                
                if ($result !== false) {
                    $migrated_count++;
                }
            }
        }
        
        // Mark migration as complete
        update_option('typeform_quiz_order_migrated', true);
        
        // Also fix any existing duplicate order values
        self::fix_duplicate_order_values();
    }

    /**
     * Fix duplicate order values in menu_order
     */
    public static function fix_duplicate_order_values(): void
    {
        global $wpdb;
        
        // Only run for admin users
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }
        
        // Only run once - check if we've already fixed duplicates
        if (get_option('typeform_quiz_duplicates_fixed')) {
            return;
        }
        
        // Get all quizzes ordered by menu_order
        $quizzes = $wpdb->get_results(
            "SELECT ID, menu_order 
             FROM {$wpdb->posts} 
             WHERE post_type = 'typeform_quiz' 
             AND post_status = 'publish'
             ORDER BY menu_order ASC, ID ASC"
        );
        
        if (empty($quizzes)) {
            update_option('typeform_quiz_duplicates_fixed', true);
            return;
        }
        
        $fixed_count = 0;
        $used_orders = [];
        
        foreach ($quizzes as $index => $quiz) {
            $current_order = intval($quiz->menu_order);
            $new_order = $index + 1;
            
            // If order is already correct, skip
            if ($current_order === $new_order) {
                $used_orders[] = $new_order;
                continue;
            }
            
            // Check if the desired order is already taken
            while (in_array($new_order, $used_orders)) {
                $new_order++;
            }
            
            // Update the order
            $result = $wpdb->update(
                $wpdb->posts,
                ['menu_order' => $new_order],
                ['ID' => $quiz->ID],
                ['%d'],
                ['%d']
            );
            
            if ($result !== false) {
                $fixed_count++;
                $used_orders[] = $new_order;
            }
        }
        
        // Mark as fixed
        update_option('typeform_quiz_duplicates_fixed', true);
    }

    /**
     * Reset the duplicate fix flag (for debugging or manual re-fixing)
     */
    public static function reset_duplicate_fix_flag(): void
    {
        delete_option('typeform_quiz_duplicates_fixed');
    }
}
