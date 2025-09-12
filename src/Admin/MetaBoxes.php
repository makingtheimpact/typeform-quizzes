<?php
/**
 * Meta Boxes Handler
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
 * Meta Boxes Class
 * 
 * Handles meta boxes for quiz editing functionality.
 */
class MetaBoxes
{
    /**
     * Initialize meta box functionality
     */
    public static function init(): void
    {
        add_action('add_meta_boxes', [__CLASS__, 'add_meta_boxes']);
        add_action('save_post', [__CLASS__, 'save_meta_boxes']);
    }

    /**
     * Add meta boxes for quiz settings
     */
    public static function add_meta_boxes(): void
    {
        if (!current_user_can('edit_posts')) {
            return;
        }
        
        add_meta_box(
            'typeform_quiz_settings',
            __('Quiz Settings', 'typeform-quizzes'),
            [__CLASS__, 'render_meta_box'],
            'typeform_quiz',
            'normal',
            'high'
        );
    }

    /**
     * Render meta box content
     */
    public static function render_meta_box($post): void
    {
        if (!current_user_can('edit_posts')) {
            return;
        }

        $typeform_url = get_post_meta($post->ID, '_typeform_url', true);
        $quiz_order = get_post_meta($post->ID, '_quiz_order', true);
        $menu_order = $post->menu_order;
        
        // Use menu_order as primary, fall back to meta field
        $display_order = $menu_order > 0 ? $menu_order : $quiz_order;
        
        // Add nonce field
        wp_nonce_field('typeform_quiz_meta_box', 'typeform_quiz_meta_box_nonce');
        
        echo '<table class="form-table">';
        echo '<tr>';
        echo '<th><label for="typeform_url">' . esc_html__('Typeform URL', 'typeform-quizzes') . '</label></th>';
        echo '<td><input type="url" id="typeform_url" name="typeform_url" value="' . esc_attr($typeform_url) . '" class="regular-text" required>';
        echo '<p class="description">' . esc_html__('Enter the full Typeform URL for this quiz.', 'typeform-quizzes') . '</p></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th><label for="quiz_order">' . esc_html__('Display Order', 'typeform-quizzes') . '</label></th>';
        echo '<td><input type="number" id="quiz_order" name="quiz_order" value="' . esc_attr($display_order ?: 0) . '" class="small-text" min="0">';
        echo '<p class="description">' . esc_html__('Lower numbers appear first in the slider (0 = first).', 'typeform-quizzes') . ' <strong>' . esc_html__('Tip:', 'typeform-quizzes') . '</strong> ' . esc_html__('Use the "Change Order" button on the quizzes list page for easier reordering with drag & drop.', 'typeform-quizzes') . '</p></td>';
        echo '</tr>';
        echo '</table>';
    }

    /**
     * Save meta box data
     */
    public static function save_meta_boxes($post_id): void
    {
        try {
            // Prevent multiple saves
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }

            // Prevent infinite loops by checking if we're already processing this post
            static $processing_posts = [];
            if (isset($processing_posts[$post_id])) {
                return;
            }
            $processing_posts[$post_id] = true;

            // Check user permissions
            if (!current_user_can('edit_post', $post_id)) {
                unset($processing_posts[$post_id]);
                return;
            }

            // Verify nonce
            if (!isset($_POST['typeform_quiz_meta_box_nonce']) || !wp_verify_nonce($_POST['typeform_quiz_meta_box_nonce'], 'typeform_quiz_meta_box')) {
                unset($processing_posts[$post_id]);
                return;
            }

            // Save Typeform URL
            if (isset($_POST['typeform_url'])) {
                $url = sanitize_url($_POST['typeform_url']);
                if (!empty($url) && self::is_valid_typeform_url($url)) {
                    update_post_meta($post_id, '_typeform_url', $url);
                } else {
                    // Clear invalid URL
                    delete_post_meta($post_id, '_typeform_url');
                }
            }

            // Save quiz order
            if (isset($_POST['quiz_order'])) {
                $order = intval($_POST['quiz_order']);
                update_post_meta($post_id, '_quiz_order', $order);
                
                // Update menu_order directly in database to avoid triggering save_post again
                global $wpdb;
                $wpdb->update(
                    $wpdb->posts,
                    ['menu_order' => $order],
                    ['ID' => $post_id],
                    ['%d'],
                    ['%d']
                );
            }

            // Clean up processing flag
            unset($processing_posts[$post_id]);

        } catch (\Exception $e) {
            error_log('Typeform Quizzes: Error saving meta boxes - ' . $e->getMessage());
            // Clean up processing flag on error
            unset($processing_posts[$post_id]);
        }
    }

    /**
     * Validate Typeform URL format
     */
    private static function is_valid_typeform_url($url): bool
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
