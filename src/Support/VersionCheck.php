<?php
/**
 * Version Compatibility Checker
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
 * Version Check Class
 * 
 * Handles PHP and WordPress version compatibility checks.
 */
class VersionCheck
{
    /**
     * Initialize version checking
     */
    public static function init(): void
    {
        add_action('admin_notices', [__CLASS__, 'php_version_notice']);
        add_action('admin_notices', [__CLASS__, 'wp_version_notice']);
    }

    /**
     * Display PHP version notice
     */
    public static function php_version_notice(): void
    {
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>' . esc_html__('Typeform Quizzes:', 'typeform-quizzes') . '</strong> ' . esc_html__('This plugin requires PHP 7.4 or higher. Your current version is', 'typeform-quizzes') . ' ' . PHP_VERSION . '.';
            echo '</p></div>';
        }
    }

    /**
     * Display WordPress version notice
     */
    public static function wp_version_notice(): void
    {
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>' . esc_html__('Typeform Quizzes:', 'typeform-quizzes') . '</strong> ' . esc_html__('This plugin requires WordPress 5.0 or higher. Your current version is', 'typeform-quizzes') . ' ' . get_bloginfo('version') . '.';
            echo '</p></div>';
        }
    }

    /**
     * Check if current environment is compatible
     */
    public static function is_compatible(): bool
    {
        return version_compare(PHP_VERSION, '7.4', '>=') && 
               version_compare(get_bloginfo('version'), '5.0', '>=');
    }

    /**
     * Get compatibility status
     */
    public static function get_compatibility_status(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'php_compatible' => version_compare(PHP_VERSION, '7.4', '>='),
            'wp_version' => get_bloginfo('version'),
            'wp_compatible' => version_compare(get_bloginfo('version'), '5.0', '>='),
            'overall_compatible' => self::is_compatible()
        ];
    }
}
