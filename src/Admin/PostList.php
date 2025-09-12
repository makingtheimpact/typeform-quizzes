<?php
/**
 * Admin Post List Features
 * 
 * Handles custom columns, featured images, and reorder functionality for the quiz post list.
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
 * Post List Admin Class
 * 
 * Handles all admin list page features including custom columns,
 * featured image display, and drag-and-drop reordering functionality.
 */
class PostList
{
    /**
     * Initialize post list features
     * 
     * @return void
     */
    public static function init(): void
    {
        add_action('init', [__CLASS__, 'add_admin_columns']);
        add_action('restrict_manage_posts', [__CLASS__, 'add_reorder_button']);
        add_action('admin_footer', [__CLASS__, 'add_reorder_modal']);
        add_action('wp_ajax_tfq_reorder', [__CLASS__, 'handle_reorder_ajax']);
        add_action('pre_get_posts', [__CLASS__, 'admin_list_ordering']);
    }

    /**
     * Add custom columns to quiz admin list
     */
    public static function add_admin_columns(): void
    {
        if (!current_user_can('edit_posts')) {
            return;
        }

        // Add custom columns to the admin list view
        add_filter('manage_typeform_quiz_posts_columns', [__CLASS__, 'modify_admin_columns']);
        add_action('manage_typeform_quiz_posts_custom_column', [__CLASS__, 'populate_admin_columns'], 10, 2);
        add_filter('manage_edit-typeform_quiz_sortable_columns', [__CLASS__, 'make_columns_sortable']);
    }

    /**
     * Modify admin columns
     */
    public static function modify_admin_columns($columns): array
    {
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
    public static function populate_admin_columns($column, $post_id): void
    {
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
                
                // Prioritize menu_order (WordPress native ordering)
                // Only fall back to meta field if menu_order is explicitly 0 and meta exists
                if ($menu_order > 0) {
                    $order = $menu_order;
                } elseif ($menu_order === 0 && $meta_order !== '' && $meta_order !== false) {
                    $order = $meta_order;
                } else {
                    $order = $menu_order; // This will be 0
                }
                
                echo esc_html($order);
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
    public static function make_columns_sortable($columns): array
    {
        $columns['quiz_order'] = 'menu_order';
        return $columns;
    }

    /**
     * Add reorder button to admin list page
     */
    public static function add_reorder_button(): void
    {
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
    public static function add_reorder_modal(): void
    {
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
            <?php
        }
    }

    /**
     * Handle reorder AJAX requests
     */
    public static function handle_reorder_ajax(): void
    {
        // Security checks
        if (!self::verify_reorder_nonce()) {
            return;
        }

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Insufficient permissions', 'typeform-quizzes'));
            return;
        }

        $action_type = sanitize_text_field($_POST['action_type'] ?? '');

        switch ($action_type) {
            case 'get_quizzes':
                self::ajax_get_quizzes();
                break;
            case 'save_order':
                self::ajax_save_order();
                break;
            default:
                wp_send_json_error(__('Invalid action', 'typeform-quizzes'));
        }
    }

    /**
     * Verify reorder nonce
     */
    private static function verify_reorder_nonce(): bool
    {
        if (!isset($_POST['nonce'])) {
            wp_send_json_error(__('Missing security nonce', 'typeform-quizzes'));
            return false;
        }

        if (!wp_verify_nonce($_POST['nonce'], 'tfq_reorder')) {
            wp_send_json_error(__('Invalid security nonce', 'typeform-quizzes'));
            return false;
        }

        return true;
    }

    /**
     * AJAX: Get quizzes for reordering
     */
    private static function ajax_get_quizzes(): void
    {
        $quizzes = get_posts([
            'post_type' => 'typeform_quiz',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ]);

        $quiz_data = [];
        foreach ($quizzes as $quiz) {
            $menu_order = $quiz->menu_order;
            $meta_order = get_post_meta($quiz->ID, '_quiz_order', true);
            
            // Prioritize menu_order (WordPress native ordering)
            // Only fall back to meta field if menu_order is explicitly 0 and meta exists
            if ($menu_order > 0) {
                $order = $menu_order;
            } elseif ($menu_order === 0 && $meta_order !== '' && $meta_order !== false) {
                $order = $meta_order;
            } else {
                $order = $menu_order; // This will be 0
            }
            
            $quiz_data[] = [
                'id' => $quiz->ID,
                'title' => $quiz->post_title,
                'order' => $order,
                'thumbnail' => get_the_post_thumbnail_url($quiz->ID, [50, 50])
            ];
        }

        wp_send_json_success($quiz_data);
    }

    /**
     * AJAX: Save quiz order
     */
    private static function ajax_save_order(): void
    {
        if (!isset($_POST['order_data'])) {
            wp_send_json_error(__('Missing order data', 'typeform-quizzes'));
            return;
        }

        $order_data = json_decode(stripslashes($_POST['order_data']), true);
        if (!is_array($order_data)) {
            wp_send_json_error(__('Invalid order data', 'typeform-quizzes'));
            return;
        }

        global $wpdb;
        foreach ($order_data as $index => $quiz_id) {
            $quiz_id = intval($quiz_id);
            if ($quiz_id > 0) {
                // Update menu_order directly in database to avoid triggering save_post
                $wpdb->update(
                    $wpdb->posts,
                    ['menu_order' => $index],
                    ['ID' => $quiz_id],
                    ['%d'],
                    ['%d']
                );
            }
        }

        wp_send_json_success(__('Order saved successfully', 'typeform-quizzes'));
    }

    /**
     * Ensure admin list uses correct ordering
     */
    public static function admin_list_ordering($query): void
    {
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
}
