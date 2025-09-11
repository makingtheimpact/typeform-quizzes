<?php
/**
 * Admin Assets Handler
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
 * Admin Assets Class
 * 
 * Handles enqueuing of admin-only CSS and JavaScript assets.
 */
class Assets
{
    /**
     * Initialize admin assets
     * 
     * @return void
     */
    public static function init()
    {
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_scripts']);
    }

    /**
     * Enqueue admin scripts and styles
     * 
     * @param string $hook Current admin page hook
     * @return void
     */
    public static function enqueue_admin_scripts($hook)
    {
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
     * 
     * @return string
     */
    public static function get_reorder_script()
    {
        $ajax_url = admin_url('admin-ajax.php');
        $nonce = wp_create_nonce('typeform_quiz_reorder');

        return "
        jQuery(document).ready(function($) {
            var reorderModal = $('#typeform-quizzes-reorder-modal');
            var reorderList = $('#reorder-list');
            var isDirty = false;
            
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
            
            // Close modal on backdrop click
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
            
            // Load quizzes for reordering
            function loadQuizzes() {
                $.ajax({
                    url: '{$ajax_url}',
                    type: 'POST',
                    data: {
                        action: 'typeform_quiz_reorder',
                        action_type: 'get_quizzes',
                        nonce: '{$nonce}'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Build HTML from quiz data
                            var html = '';
                            if (response.data && response.data.length > 0) {
                                response.data.forEach(function(quiz, index) {
                                    html += '<li data-quiz-id=\"' + quiz.id + '\" class=\"quiz-reorder-item\">';
                                    html += '<span class=\"dashicons dashicons-menu\" style=\"margin-right: 10px; color: #666; cursor: move;\"></span>';
                                    html += '<div class=\"quiz-item\" style=\"display: flex; align-items: center; flex: 1;\">';
                                    if (quiz.thumbnail) {
                                        html += '<img src=\"' + quiz.thumbnail + '\" alt=\"' + quiz.title + '\" class=\"quiz-thumbnail\" style=\"width: 50px; height: 50px; border-radius: 4px; margin-right: 15px; object-fit: cover;\">';
                                    }
                                    html += '<div style=\"flex: 1;\">';
                                    html += '<div class=\"quiz-title\" style=\"font-weight: 500; color: #333; margin-bottom: 5px;\">' + quiz.title + '</div>';
                                    html += '<div class=\"quiz-order\" style=\"font-size: 12px; color: #666;\">Order: ' + (index + 1) + '</div>';
                                    html += '</div>';
                                    html += '</div>';
                                    html += '</li>';
                                });
                                reorderList.html(html);
                                
                                // Update quiz count
                                $('#quiz-count').text(response.data.length + ' quizzes');
                                
                                initSortable();
                            } else {
                                reorderList.html('<li>No quizzes found</li>');
                            }
                        } else {
                            alert('Error loading quizzes: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('Error loading quizzes. Please try again.');
                    }
                });
            }
            
            // Initialize sortable
            function initSortable() {
                reorderList.sortable({
                    placeholder: 'ui-state-highlight',
                    cursor: 'move',
                    axis: 'y',
                    start: function(event, ui) {
                        ui.helper.css('z-index', 1000);
                    },
                    update: function(event, ui) {
                        isDirty = true;
                        updateOrderNumbers();
                    }
                });
            }
            
            // Update order numbers after sorting
            function updateOrderNumbers() {
                reorderList.find('li').each(function(index) {
                    $(this).find('.quiz-order').text('Order: ' + (index + 1));
                });
            }
            
            // Save reorder
            $('#typeform-quizzes-save-reorder').on('click', function() {
                var order = [];
                reorderList.find('li').each(function() {
                    order.push($(this).data('quiz-id'));
                });
                
                $.ajax({
                    url: '{$ajax_url}',
                    type: 'POST',
                    data: {
                        action: 'typeform_quiz_reorder',
                        action_type: 'save_order',
                        order_data: JSON.stringify(order),
                        nonce: '{$nonce}'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            var button = $('#typeform-quizzes-save-reorder');
                            var originalText = button.text();
                            button.text('Saved!').css('background-color', '#46b450');
                            
                            setTimeout(function() {
                                button.text(originalText).css('background-color', '');
                                reorderModal.hide();
                                isDirty = false;
                                location.reload();
                            }, 1500);
                        } else {
                            alert('Error saving order: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('Error saving order. Please try again.');
                    }
                });
            });
            
            // Cancel reorder
            $('#cancel-quiz-reorder').on('click', function() {
                if (isDirty) {
                    if (confirm('You have unsaved changes. Are you sure you want to cancel?')) {
                        reorderModal.hide();
                        isDirty = false;
                    }
                } else {
                    reorderModal.hide();
                }
            });
        });
        ";
    }

    /**
     * Get reorder CSS styles
     * 
     * @return string
     */
    public static function get_reorder_styles()
    {
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
        
        .quiz-reorder-item {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 15px;
            margin: 5px 0;
            display: flex;
            align-items: center;
            transition: all 0.2s ease;
        }
        
        .quiz-reorder-item:hover {
            border-color: #0073aa;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .quiz-reorder-item .dashicons-menu {
            margin-right: 10px;
            color: #666;
        }
        
        .quiz-reorder-item .quiz-title {
            flex: 1;
            font-weight: 500;
            color: #333;
        }
        
        .quiz-reorder-item .quiz-status {
            margin-left: 10px;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .quiz-reorder-item .quiz-status.publish {
            background: #d4edda;
            color: #155724;
        }
        
        .quiz-reorder-item .quiz-status.draft {
            background: #f8d7da;
            color: #721c24;
        }
        
        .quiz-reorder-item .quiz-status.private {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .quiz-reorder-item .quiz-date {
            margin-left: 10px;
            color: #666;
            font-size: 12px;
        }
        
        .quiz-reorder-item {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 15px;
            margin: 5px 0;
            display: flex;
            align-items: center;
            transition: all 0.2s ease;
            list-style: none;
        }
        
        .quiz-reorder-item:hover {
            border-color: #0073aa;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .quiz-reorder-item .dashicons-menu {
            margin-right: 10px;
            color: #666;
            cursor: move;
        }
        
        .quiz-reorder-item .quiz-item {
            display: flex;
            align-items: center;
            flex: 1;
        }
        
        .quiz-reorder-item .quiz-thumbnail {
            width: 50px;
            height: 50px;
            border-radius: 4px;
            margin-right: 15px;
            object-fit: cover;
        }
        
        .quiz-reorder-item .quiz-title {
            font-weight: 500;
            color: #333;
            margin-bottom: 5px;
        }
        
        .quiz-reorder-item .quiz-order {
            font-size: 12px;
            color: #666;
        }
        
        .reorder-modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        
        .reorder-modal-buttons .button {
            min-width: 100px;
        }
        
        .reorder-modal-buttons .button-primary {
            background: #0073aa;
            border-color: #0073aa;
        }
        
        .reorder-modal-buttons .button-primary:hover {
            background: #005a87;
            border-color: #005a87;
        }
        
        .reorder-modal-buttons .button-secondary {
            background: #f1f1f1;
            border-color: #ccc;
            color: #555;
        }
        
        .reorder-modal-buttons .button-secondary:hover {
            background: #e1e1e1;
            border-color: #999;
        }
        
        .reorder-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ddd;
        }
        
        .reorder-modal-header h2 {
            margin: 0;
            color: #333;
        }
        
        .reorder-modal-header .close-btn {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #666;
            padding: 5px;
        }
        
        .reorder-modal-header .close-btn:hover {
            color: #000;
        }
        
        .reorder-modal-content {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .reorder-modal-content .no-quizzes {
            text-align: center;
            color: #666;
            padding: 40px 20px;
            font-style: italic;
        }
        
        .reorder-modal-content .loading {
            text-align: center;
            color: #666;
            padding: 40px 20px;
        }
        
        .reorder-modal-content .loading .spinner {
            float: none;
            margin: 0 auto 10px;
        }
        ";
    }
}
