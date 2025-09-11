<?php
/**
 * Options Service
 * 
 * @package Typeform_Quizzes
 * @version 1.1.0
 * @author Making The Impact LLC
 */

namespace MTI\TypeformQuizzes\Services;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

/**
 * Options Service Class
 * 
 * Handles plugin options management with a clean interface.
 */
final class Options
{
    /**
     * Get an option value
     * 
     * @param string $key Option key
     * @param mixed $default Default value if option doesn't exist
     * @return mixed Option value or default
     */
    public static function get(string $key, $default = null)
    {
        return get_option($key, $default);
    }

    /**
     * Update an option value
     * 
     * @param string $key Option key
     * @param mixed $value Option value
     * @return bool True on success, false on failure
     */
    public static function update(string $key, $value)
    {
        return update_option($key, $value);
    }

    /**
     * Delete an option
     * 
     * @param string $key Option key
     * @return bool True on success, false on failure
     */
    public static function delete(string $key)
    {
        return delete_option($key);
    }

    /**
     * Check if an option exists
     * 
     * @param string $key Option key
     * @return bool True if option exists, false otherwise
     */
    public static function exists(string $key)
    {
        return get_option($key) !== false;
    }
}
