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

        // Load on our plugin pages only (not on individual post edit pages)
        // Check for our plugin pages using multiple methods for better compatibility
        $is_plugin_page = (
            $hook === 'toplevel_page_typeform-quizzes' || 
            $hook === 'typeform-quizzes_page_typeform-quizzes-settings' ||
            (isset($_GET['page']) && $_GET['page'] === 'typeform-quizzes-settings')
        );
        
        if ($is_plugin_page) {
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
            
            // Add custom admin styles for improved settings appearance
            wp_add_inline_style('wp-color-picker', self::get_admin_styles());
            
            // Add color picker initialization script with proper dependency
            wp_add_inline_script('wp-color-picker', self::get_color_picker_script());
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
        $nonce = wp_create_nonce('tfq_reorder');
        $debug_mode = defined('WP_DEBUG') && WP_DEBUG;
        
        return "
        // Ensure jQuery is available and handle conflicts
        (function($) {
            if (typeof $ === 'undefined') {
                console.error('Typeform Quizzes: jQuery not available, falling back to vanilla JS');
                return;
            }
            
            console.log('Typeform Quizzes: Reorder script loaded successfully');
            
            // Prevent duplicate initialization
            if (window.typeformQuizzesReorderInitialized) {
                console.log('Typeform Quizzes: Reorder already initialized, skipping');
                return;
            }
            window.typeformQuizzesReorderInitialized = true;
            
            $(document).ready(function() {
                // Only run on the correct admin page
                if (!$('#typeform-quizzes-reorder-modal').length) {
                    console.log('Typeform Quizzes: Reorder modal not found, skipping initialization');
                    return;
                }
                
                let reorderModal = $('#typeform-quizzes-reorder-modal');
                let reorderList = $('#reorder-list');
                let isDirty = false;
                
                // Open modal
                $('#reorder-quizzes-btn').on('click', function() {
                    // Reset button state when opening modal
                    $('#typeform-quizzes-save-reorder').prop('disabled', false)
                        .text('Save Order')
                        .css('background', '#0073aa')
                        .css('border-color', '#0073aa');
                    
                    // Reset quiz count display
                    $('#quiz-count').text('');
                    
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
                    reorderList.html('<div style=\"text-align: center; padding: 50px; color: #999;\"><span class=\"dashicons dashicons-update\" style=\"font-size: 24px; margin-right: 10px; animation: spin 1s linear infinite;\"></span>Loading quizzes...</div>');
                    
                    $.post('$ajax_url', {
                        action: 'tfq_reorder',
                        action_type: 'get_quizzes',
                        nonce: '$nonce'
                    }, function(response) {
                        if (response.success) {
                            if (response.data && response.data.length > 0) {
                                let html = '<ul id=\"sortable-quizzes\" style=\"list-style: none; padding: 0; margin: 0;\">';
                                response.data.forEach(function(quiz) {
                                    html += '<li data-id=\"' + quiz.id + '\" style=\"background: #fff; border: 1px solid #ddd; border-radius: 6px; padding: 15px; margin: 5px 0; cursor: move; display: flex; align-items: center;\">';
                                    html += '<div style=\"margin-right: 15px; font-size: 20px; color: #999;\">⋮</div>';
                                    if (quiz.thumbnail) {
                                        html += '<img src=\"' + quiz.thumbnail + '\" style=\"width: 40px; height: 40px; border-radius: 4px; margin-right: 15px; object-fit: cover;\">';
                                    } else {
                                        html += '<div style=\"width: 40px; height: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 4px; display: flex; align-items: center; justify-content: center; color: white; font-size: 16px; margin-right: 15px;\">📝</div>';
                                    }
                                    html += '<div style=\"flex: 1;\">';
                                    html += '<div style=\"font-weight: 600; color: #333; margin-bottom: 5px;\">' + quiz.title + '</div>';
                                    html += '<div class=\"quiz-order\" style=\"color: #666; font-size: 12px;\">Order: ' + (quiz.order + 1) + '</div>';
                                    html += '</div>';
                                    html += '</li>';
                                });
                                html += '</ul>';
                                reorderList.html(html);
                                
                                // Update quiz count
                                $('#quiz-count').text(response.data.length + ' quizzes');
                                
                                initSortable();
                            } else {
                                reorderList.html('<div style=\"text-align: center; padding: 50px; color: #999;\">No quizzes found</div>');
                            }
                        } else {
                            reorderList.html('<div style=\"text-align: center; padding: 50px; color: #d63638;\">Error loading quizzes: ' + response.data + '</div>');
                        }
                    }).fail(function() {
                        reorderList.html('<div style=\"text-align: center; padding: 50px; color: #d63638;\">Error loading quizzes. Please try again.</div>');
                    });
                }
                
                // Initialize sortable
                function initSortable() {
                    $('#sortable-quizzes').sortable({
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
                    $('#sortable-quizzes li').each(function(index) {
                        // Update the order number to reflect the new position
                        $(this).find('.quiz-order').text('Order: ' + (index + 1));
                    });
                }
                
                // Save reorder
                $('#typeform-quizzes-save-reorder').on('click', function() {
                    let orderData = [];
                    $('#sortable-quizzes li').each(function() {
                        orderData.push($(this).data('id'));
                    });
                    
                    let saveButton = $(this);
                    let originalText = saveButton.text();
                    
                    // Update button to show saving state
                    saveButton.prop('disabled', true)
                        .html('<span class=\"dashicons dashicons-update\" style=\"animation: spin 1s linear infinite; margin-right: 5px;\"></span>Saving...')
                        .css('background', '#0073aa');
                    
                    // Add debugging for staging
                    if ($debug_mode) {
                        console.log('Typeform Quizzes: Sending reorder request', {
                            ajax_url: '$ajax_url',
                            order_data: orderData,
                            nonce: '$nonce'
                        });
                    }
                    
                    $.post('$ajax_url', {
                        action: 'tfq_reorder',
                        action_type: 'save_order',
                        order_data: JSON.stringify(orderData),
                        nonce: '$nonce'
                    }, function(response) {
                        if ($debug_mode) {
                            console.log('Typeform Quizzes: AJAX Response', response);
                        }
                        if (response.success) {
                            // Show success state
                            saveButton.html('<span class=\"dashicons dashicons-yes-alt\" style=\"margin-right: 5px; color: #00a32a;\"></span>Saved!')
                                .css('background', '#00a32a')
                                .css('border-color', '#00a32a');
                            
                            // Show success message in modal
                            $('#quiz-count').html('<span style=\"color: #00a32a; font-weight: 600;\"><span class=\"dashicons dashicons-yes-alt\" style=\"margin-right: 5px;\"></span>Order saved successfully!</span>');
                            
                            // Clear any caches that might be affecting the display
                            if (typeof wp !== 'undefined' && wp.ajax) {
                                // Clear object cache if available
                                wp.ajax.post('tfq_clear_cache', {
                                    nonce: '$nonce'
                                }).done(function() {
                                    console.log('Typeform Quizzes: Cache cleared successfully');
                                }).fail(function() {
                                    console.log('Typeform Quizzes: Cache clear failed or not available');
                                });
                            }
                            
                            // Auto-close modal after 1.5 seconds
                            setTimeout(function() {
                                reorderModal.hide();
                                isDirty = false;
                                location.reload(); // Refresh to show new order
                            }, 1500);
                            
                        } else {
                            // Show error state
                            saveButton.html('<span class=\"dashicons dashicons-warning\" style=\"margin-right: 5px; color: #d63638;\"></span>Error')
                                .css('background', '#d63638')
                                .css('border-color', '#d63638');
                            
                            // Show error message in modal
                            $('#quiz-count').html('<span style=\"color: #d63638; font-weight: 600;\"><span class=\"dashicons dashicons-warning\" style=\"margin-right: 5px;\"></span>Error: ' + response.data + '</span>');
                            
                            // Reset button after 3 seconds
                            setTimeout(function() {
                                saveButton.prop('disabled', false)
                                    .text(originalText)
                                    .css('background', '#0073aa')
                                    .css('border-color', '#0073aa');
                            }, 3000);
                        }
                    }).fail(function(xhr, status, error) {
                        console.error('Typeform Quizzes: AJAX Error', {
                            xhr: xhr,
                            status: status,
                            error: error,
                            responseText: xhr.responseText
                        });
                        
                        // Show error state
                        saveButton.html('<span class=\"dashicons dashicons-warning\" style=\"margin-right: 5px; color: #d63638;\"></span>Error')
                            .css('background', '#d63638')
                            .css('border-color', '#d63638');
                            
                        // Show detailed error message in modal
                        let errorMsg = 'Error saving order. ';
                        if (xhr.responseText) {
                            try {
                                const response = JSON.parse(xhr.responseText);
                                errorMsg += response.data || 'Please try again.';
                            } catch (e) {
                                errorMsg += 'Please try again.';
                            }
                        } else {
                            errorMsg += 'Please try again.';
                        }
                        
                        $('#quiz-count').html('<span style=\"color: #d63638; font-weight: 600;\"><span class=\"dashicons dashicons-warning\" style=\"margin-right: 5px;\"></span>' + errorMsg + '</span>');
                        
                        // Reset button after 3 seconds
                        setTimeout(function() {
                            saveButton.prop('disabled', false)
                                .text(originalText)
                                .css('background', '#0073aa')
                                .css('border-color', '#0073aa');
                        }, 3000);
                    });
                });
            });
        })(jQuery);
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

    /**
     * Get color picker initialization script
     * 
     * @return string
     */
    public static function get_color_picker_script()
    {
        return "
        jQuery(document).ready(function($) {
            // Initialize color pickers
            $('.color-picker').wpColorPicker();
            
            // Auto-hide success messages after 5 seconds
            $('.notice-success').delay(5000).fadeOut(500);
        });
        ";
    }

    /**
     * Get admin styles for improved settings appearance
     * 
     * @return string
     */
    public static function get_admin_styles()
    {
        return "
        /* Enhanced Settings Section Headings */
        .tfq-section-header {
            margin: 30px 0 20px 0 !important;
            padding: 0 0 15px 0 !important;
            border-bottom: 2px solid #e1e1e1 !important;
            position: relative !important;
        }
        
        .tfq-section-header:first-of-type {
            margin-top: 25px !important;
        }
        
        .tfq-section-header .dashicons {
            font-size: 20px !important;
            color: #0073aa !important;
            margin-right: 12px !important;
            width: 20px !important;
            height: 20px !important;
            vertical-align: middle !important;
        }
        
        .tfq-section-header h3 {
            margin: 0 !important;
            font-size: 18px !important;
            font-weight: 600 !important;
            color: #23282d !important;
            line-height: 1.3 !important;
            display: inline-block !important;
            vertical-align: middle !important;
        }
        
        
        /* Hover effects for section headers */
        .tfq-section-header:hover .dashicons {
            color: #005177 !important;
            transition: color 0.2s ease !important;
        }
        
        .tfq-section-header:hover h3 {
            color: #005177 !important;
            transition: color 0.2s ease !important;
        }
        
        /* Enhanced form table styling */
        .typeform-quizzes-tools .form-table {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin: 0 0 30px 0 !important;
        }
        
        .typeform-quizzes-tools .form-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
            padding: 15px 20px !important;
            border-bottom: 1px solid #e1e5e9;
            width: 200px;
        }
        
        .typeform-quizzes-tools .form-table td {
            padding: 15px 20px !important;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .typeform-quizzes-tools .form-table tr:last-child th,
        .typeform-quizzes-tools .form-table tr:last-child td {
            border-bottom: none;
        }
        
        /* Enhanced field descriptions */
        .typeform-quizzes-tools .description {
            color: #666 !important;
            font-style: italic;
            margin-top: 5px !important;
            padding: 8px 12px;
            background: #f8f9fa;
            border-radius: 4px;
            border-left: 3px solid #0073aa;
        }
        
        /* Enhanced input styling */
        .typeform-quizzes-tools input[type='text'],
        .typeform-quizzes-tools input[type='number'],
        .typeform-quizzes-tools input[type='url'],
        .typeform-quizzes-tools select {
            border: 2px solid #e1e5e9 !important;
            border-radius: 6px !important;
            padding: 8px 12px !important;
            transition: border-color 0.3s ease !important;
        }
        
        .typeform-quizzes-tools input[type='text']:focus,
        .typeform-quizzes-tools input[type='number']:focus,
        .typeform-quizzes-tools input[type='url']:focus,
        .typeform-quizzes-tools select:focus {
            border-color: #0073aa !important;
            box-shadow: 0 0 0 3px rgba(0, 115, 170, 0.1) !important;
            outline: none !important;
        }
        
        /* Enhanced checkbox styling */
        .typeform-quizzes-tools input[type='checkbox'] {
            transform: scale(1.2);
            margin-right: 8px;
        }
        
        /* Enhanced submit button */
        .typeform-quizzes-tools .submit .button-primary {
            background: linear-gradient(135deg, #0073aa 0%, #005177 100%) !important;
            border: none !important;
            border-radius: 6px !important;
            padding: 12px 24px !important;
            font-size: 16px !important;
            font-weight: 600 !important;
            box-shadow: 0 2px 8px rgba(0, 115, 170, 0.3) !important;
            transition: all 0.3s ease !important;
        }
        
        .typeform-quizzes-tools .submit .button-primary:hover {
            background: linear-gradient(135deg, #005177 0%, #003d5c 100%) !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 115, 170, 0.4) !important;
        }
        
        /* Section spacing improvements */
        .typeform-quizzes-tools .form-table + .form-table {
            margin-top: 40px !important;
        }
        
        /* Responsive improvements */
        @media (max-width: 768px) {
            .typeform-quizzes-tools h3,
            h3[data-section] {
                font-size: 18px !important;
            }
            
            .typeform-quizzes-tools .form-table th,
            .typeform-quizzes-tools .form-table td {
                padding: 12px 15px !important;
            }
            
            .typeform-quizzes-tools .form-table th {
                width: auto;
                display: block;
                border-bottom: none;
            }
            
            .typeform-quizzes-tools .form-table td {
                display: block;
                padding-top: 5px !important;
            }
        }
        ";
    }
}
