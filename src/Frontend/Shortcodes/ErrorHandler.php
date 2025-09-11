<?php
/**
 * Error Handler for Typeform Quizzes Shortcode
 * 
 * @package Typeform_Quizzes
 * @version 1.1.0
 * @author Making The Impact LLC
 */

namespace MTI\TypeformQuizzes\Frontend\Shortcodes;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

/**
 * Error Handler Class
 * 
 * Handles error rendering and logging for the shortcode system.
 */
class ErrorHandler
{
    /**
     * Render an error message
     * 
     * @param string $message Error message
     * @return string HTML error message
     */
    public static function render_error($message) {
        return '<div class="typeform-quizzes-error typeform-quizzes-error-message">' . 
               esc_html($message) . 
               '</div>';
    }
    
    /**
     * Log an error message
     * 
     * @param string $message Error message
     * @return void
     */
    public static function log_error($message) {
        $log_message = 'Typeform Quizzes: ' . $message;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log($log_message);
        }
    }
}
