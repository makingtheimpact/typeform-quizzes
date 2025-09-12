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

    /**
     * Sanitize color value (alias for hex_color)
     * 
     * @param mixed $value Color value to sanitize
     * @return string Sanitized hex color
     */
    public static function color($value): string
    {
        return self::hex_color($value);
    }

    /**
     * Sanitize integer value with min/max bounds
     * 
     * @param mixed $value Value to sanitize
     * @param int $min Minimum allowed value
     * @param int $max Maximum allowed value
     * @return int Sanitized integer
     */
    public static function intval($value, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX): int
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
     * Sanitize boolean value (alias for boolean)
     * 
     * @param mixed $value Value to sanitize
     * @return bool Sanitized boolean
     */
    public static function boolval($value): bool
    {
        return self::boolean($value);
    }

    /**
     * Sanitize shortcode attributes
     * 
     * @param array $atts Shortcode attributes
     * @return array Sanitized attributes
     */
    public static function shortcode_attributes(array $atts): array
    {
        $sanitized = [];
        
        foreach ($atts as $key => $value) {
            $key = sanitize_key($key);
            
            switch ($key) {
                case 'max':
                case 'max_width':
                case 'thumb_height':
                case 'cols_desktop':
                case 'cols_tablet':
                case 'cols_mobile':
                case 'gap':
                case 'border_radius':
                case 'controls_spacing':
                case 'controls_spacing_tablet':
                case 'controls_bottom_spacing':
                case 'arrow_border_radius':
                case 'arrow_padding':
                case 'arrow_width':
                case 'arrow_height':
                case 'arrow_icon_size':
                case 'pagination_dot_size':
                case 'pagination_dot_gap':
                    $sanitized[$key] = absint($value);
                    break;
                    
                case 'title_color':
                case 'title_hover_color':
                case 'arrow_bg_color':
                case 'arrow_hover_bg_color':
                case 'arrow_icon_color':
                case 'arrow_icon_hover_color':
                case 'pagination_dot_color':
                case 'pagination_active_dot_color':
                case 'active_slide_border_color':
                    $sanitized[$key] = self::hex_color($value);
                    break;
                    
                case 'darken_inactive_slides':
                    $sanitized[$key] = self::boolean($value);
                    break;
                    
                case 'order':
                    $sanitized[$key] = self::order($value);
                    break;
                    
                default:
                    $sanitized[$key] = sanitize_text_field($value);
                    break;
            }
        }
        
        return $sanitized;
    }
}
