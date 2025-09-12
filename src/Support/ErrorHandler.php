<?php
/**
 * Error Handler
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
 * Error Handler Class
 * 
 * Handles error logging and display functionality.
 */
class ErrorHandler
{
    /**
     * @var array Stored errors
     */
    private static $errors = [];

    /**
     * Initialize error handling
     */
    public static function init(): void
    {
        add_action('admin_notices', [__CLASS__, 'display_errors']);
    }

    /**
     * Log errors for display
     */
    public static function log_error(string $message): void
    {
        $log_message = 'Typeform Quizzes: ' . $message;
        
        // Log to WordPress error log
        error_log($log_message);
        
        // Store for admin display
        self::$errors[] = $message;
        
        // Also log to custom log file if WP_DEBUG is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $log_file = WP_CONTENT_DIR . '/typeform-quizzes-debug.log';
            $timestamp = current_time('Y-m-d H:i:s');
            $log_entry = "[{$timestamp}] {$log_message}" . PHP_EOL;
            file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
        }
    }

    /**
     * Display logged errors
     */
    public static function display_errors(): void
    {
        if (!empty(self::$errors) && current_user_can('manage_options')) {
            foreach (self::$errors as $error) {
                echo '<div class="notice notice-error is-dismissible">';
                echo '<p><strong>' . esc_html__('Typeform Quizzes Error:', 'typeform-quizzes') . '</strong> ' . esc_html($error) . '</p>';
                echo '</div>';
            }
        }
    }

    /**
     * Clear all stored errors
     */
    public static function clear_errors(): void
    {
        self::$errors = [];
    }

    /**
     * Get all stored errors
     */
    public static function get_errors(): array
    {
        return self::$errors;
    }

    /**
     * Check if there are any errors
     */
    public static function has_errors(): bool
    {
        return !empty(self::$errors);
    }
}
