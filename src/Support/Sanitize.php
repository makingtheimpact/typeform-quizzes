<?php
/**
 * Sanitization and Validation Helpers
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
 * Sanitize Class
 * 
 * Pure helper functions for sanitization and validation.
 * No WordPress hooks, just pure input/output functions.
 */
final class Sanitize
{
    /**
     * Sanitize hex color value
     * 
     * @param string $color Color value to sanitize
     * @return string Sanitized hex color
     */
    public static function hex_color(string $color): string
    {
        if (empty($color)) {
            return '#111111';
        }
        
        // Remove any non-hex characters
        $color = preg_replace('/[^0-9a-fA-F]/', '', $color);
        
        // Ensure it's a valid hex color
        if (strlen($color) === 6) {
            return '#' . $color;
        } elseif (strlen($color) === 3) {
            return '#' . $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2];
        }
        
        return '#111111';
    }

    /**
     * Sanitize CSS dimension value
     * 
     * @param string $value The dimension value to sanitize
     * @param string $default Default value if sanitization fails
     * @return string Sanitized dimension value
     */
    public static function css_dimension(string $value, string $default = '100%'): string
    {
        if (empty($value)) {
            return $default;
        }
        
        // Allow common CSS units and percentages
        if (preg_match('/^(\d+(?:\.\d+)?)(px|%|em|rem|vh|vw)$/', $value, $matches)) {
            return $value;
        }
        
        // If it's just a number, assume pixels
        if (is_numeric($value)) {
            return $value . 'px';
        }
        
        return $default;
    }

    /**
     * Sanitize integer with bounds checking
     * 
     * @param mixed $value Value to sanitize
     * @param int $min Minimum allowed value
     * @param int $max Maximum allowed value
     * @param int $default Default value if invalid
     * @return int Sanitized integer
     */
    public static function integer_bounds($value, int $min, int $max, int $default): int
    {
        $int_value = intval($value);
        
        if ($int_value < $min || $int_value > $max) {
            return $default;
        }
        
        return $int_value;
    }

    /**
     * Sanitize integer with min/max clamping
     * 
     * @param mixed $value Value to sanitize
     * @param int $min Minimum allowed value
     * @param int $max Maximum allowed value
     * @param int $default Default value if invalid
     * @return int Sanitized integer clamped to bounds
     */
    public static function integer_clamp($value, int $min, int $max, int $default): int
    {
        $int_value = intval($value);
        
        if ($int_value < $min) {
            return $min;
        }
        
        if ($int_value > $max) {
            return $max;
        }
        
        return $int_value;
    }

    /**
     * Sanitize boolean value
     * 
     * @param mixed $value Value to sanitize
     * @param bool $default Default value if invalid
     * @return bool Sanitized boolean
     */
    public static function boolean($value, bool $default = false): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            $lower = strtolower(trim($value));
            if (in_array($lower, ['true', '1', 'yes', 'on'], true)) {
                return true;
            }
            if (in_array($lower, ['false', '0', 'no', 'off', ''], true)) {
                return false;
            }
        }
        
        if (is_numeric($value)) {
            return (bool) intval($value);
        }
        
        return $default;
    }

    /**
     * Sanitize order value
     * 
     * @param mixed $value Value to sanitize
     * @param array $allowed Allowed values
     * @param string $default Default value if invalid
     * @return string Sanitized order value
     */
    public static function order($value, array $allowed = ['menu_order', 'date', 'title', 'rand'], string $default = 'menu_order'): string
    {
        if (is_string($value) && in_array($value, $allowed, true)) {
            return $value;
        }
        
        return $default;
    }

    /**
     * Sanitize positive integer
     * 
     * @param mixed $value Value to sanitize
     * @param int $default Default value if invalid
     * @return int Sanitized positive integer
     */
    public static function positive_integer($value, int $default = 0): int
    {
        $int_value = intval($value);
        return $int_value > 0 ? $int_value : $default;
    }

    /**
     * Sanitize non-negative integer
     * 
     * @param mixed $value Value to sanitize
     * @param int $default Default value if invalid
     * @return int Sanitized non-negative integer
     */
    public static function non_negative_integer($value, int $default = 0): int
    {
        $int_value = intval($value);
        return $int_value >= 0 ? $int_value : $default;
    }

    /**
     * Sanitize text field
     * 
     * @param mixed $value Value to sanitize
     * @param string $default Default value if invalid
     * @return string Sanitized text
     */
    public static function text_field($value, string $default = ''): string
    {
        if (!is_string($value)) {
            return $default;
        }
        
        return sanitize_text_field($value);
    }

    /**
     * Sanitize key
     * 
     * @param mixed $value Value to sanitize
     * @param string $default Default value if invalid
     * @return string Sanitized key
     */
    public static function key($value, string $default = ''): string
    {
        if (!is_string($value)) {
            return $default;
        }
        
        return sanitize_key($value);
    }

    /**
     * Sanitize URL
     * 
     * @param mixed $value Value to sanitize
     * @param string $default Default value if invalid
     * @return string Sanitized URL
     */
    public static function url($value, string $default = ''): string
    {
        if (!is_string($value)) {
            return $default;
        }
        
        return sanitize_url($value);
    }
}
