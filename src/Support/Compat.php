<?php
/**
 * Compatibility Layer
 * 
 * @package Typeform_Quizzes
 * @version 1.1.0
 * @author Making The Impact LLC
 */

namespace MTI\TypeformQuizzes\Support;

use MTI\TypeformQuizzes\Services\Options;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

// Options compatibility functions
if (!function_exists('tfq_get_option')) {
    /**
     * Get a plugin option value
     * 
     * @param string $key Option key
     * @param mixed $default Default value if option doesn't exist
     * @return mixed Option value or default
     */
    function tfq_get_option($key, $default = null) {
        return Options::get($key, $default);
    }
}

if (!function_exists('tfq_update_option')) {
    /**
     * Update a plugin option value
     * 
     * @param string $key Option key
     * @param mixed $value Option value
     * @return bool True on success, false on failure
     */
    function tfq_update_option($key, $value) {
        return Options::update($key, $value);
    }
}

if (!function_exists('tfq_delete_option')) {
    /**
     * Delete a plugin option
     * 
     * @param string $key Option key
     * @return bool True on success, false on failure
     */
    function tfq_delete_option($key) {
        return Options::delete($key);
    }
}

// Quiz data compatibility functions
if (!function_exists('tfq_get_quizzes')) {
    /**
     * Get quizzes for display
     * 
     * @param int $max_quizzes Maximum number of quizzes
     * @param string $order Order of quizzes
     * @return array Array of quiz data
     */
    function tfq_get_quizzes($max_quizzes = 20, $order = 'menu_order') {
        if (function_exists('_deprecated_function')) _deprecated_function(__FUNCTION__, TFQ_VERSION);
        return \MTI\TypeformQuizzes\Frontend\Repository\QuizRepository::get_quizzes((int)$max_quizzes, (string)$order);
    }
}

// Sanitization compatibility functions
if (!function_exists('tfq_sanitize_hex_color')) {
    /**
     * Sanitize hex color value
     * 
     * @param string $color Color value to sanitize
     * @return string Sanitized hex color
     */
    function tfq_sanitize_hex_color($color) {
        if (function_exists('_deprecated_function')) _deprecated_function(__FUNCTION__, TFQ_VERSION);
        return \MTI\TypeformQuizzes\Support\Sanitize::hex_color($color);
    }
}

if (!function_exists('tfq_sanitize_css_dimension')) {
    /**
     * Sanitize CSS dimension value
     * 
     * @param string $value The dimension value to sanitize
     * @param string $default Default value if sanitization fails
     * @return string Sanitized dimension value
     */
    function tfq_sanitize_css_dimension($value, $default = '100%') {
        if (function_exists('_deprecated_function')) _deprecated_function(__FUNCTION__, TFQ_VERSION);
        return \MTI\TypeformQuizzes\Support\Sanitize::css_dimension($value, $default);
    }
}

if (!function_exists('tfq_sanitize_integer_bounds')) {
    /**
     * Sanitize integer with bounds checking
     * 
     * @param mixed $value Value to sanitize
     * @param int $min Minimum allowed value
     * @param int $max Maximum allowed value
     * @param int $default Default value if invalid
     * @return int Sanitized integer
     */
    function tfq_sanitize_integer_bounds($value, $min, $max, $default) {
        if (function_exists('_deprecated_function')) _deprecated_function(__FUNCTION__, TFQ_VERSION);
        return \MTI\TypeformQuizzes\Support\Sanitize::integer_bounds($value, $min, $max, $default);
    }
}

if (!function_exists('tfq_sanitize_integer_clamp')) {
    /**
     * Sanitize integer with min/max clamping
     * 
     * @param mixed $value Value to sanitize
     * @param int $min Minimum allowed value
     * @param int $max Maximum allowed value
     * @param int $default Default value if invalid
     * @return int Sanitized integer clamped to bounds
     */
    function tfq_sanitize_integer_clamp($value, $min, $max, $default) {
        if (function_exists('_deprecated_function')) _deprecated_function(__FUNCTION__, TFQ_VERSION);
        return \MTI\TypeformQuizzes\Support\Sanitize::integer_clamp($value, $min, $max, $default);
    }
}

if (!function_exists('tfq_sanitize_boolean')) {
    /**
     * Sanitize boolean value
     * 
     * @param mixed $value Value to sanitize
     * @param bool $default Default value if invalid
     * @return bool Sanitized boolean
     */
    function tfq_sanitize_boolean($value, $default = false) {
        if (function_exists('_deprecated_function')) _deprecated_function(__FUNCTION__, TFQ_VERSION);
        return \MTI\TypeformQuizzes\Support\Sanitize::boolean($value, $default);
    }
}

if (!function_exists('tfq_sanitize_order')) {
    /**
     * Sanitize order value
     * 
     * @param mixed $value Value to sanitize
     * @param array $allowed Allowed values
     * @param string $default Default value if invalid
     * @return string Sanitized order value
     */
    function tfq_sanitize_order($value, $allowed = ['menu_order', 'date', 'title', 'rand'], $default = 'menu_order') {
        if (function_exists('_deprecated_function')) _deprecated_function(__FUNCTION__, TFQ_VERSION);
        return \MTI\TypeformQuizzes\Support\Sanitize::order($value, $allowed, $default);
    }
}

if (!function_exists('tfq_sanitize_color')) {
    /**
     * Sanitize color value
     * 
     * @param mixed $value Color value to sanitize
     * @return string Sanitized hex color
     */
    function tfq_sanitize_color($value) {
        if (function_exists('_deprecated_function')) _deprecated_function(__FUNCTION__, TFQ_VERSION);
        return \MTI\TypeformQuizzes\Support\Sanitize::color($value);
    }
}

if (!function_exists('tfq_sanitize_intval')) {
    /**
     * Sanitize integer value with min/max bounds
     * 
     * @param mixed $value Value to sanitize
     * @param int $min Minimum allowed value
     * @param int $max Maximum allowed value
     * @return int Sanitized integer
     */
    function tfq_sanitize_intval($value, $min = PHP_INT_MIN, $max = PHP_INT_MAX) {
        if (function_exists('_deprecated_function')) _deprecated_function(__FUNCTION__, TFQ_VERSION);
        return \MTI\TypeformQuizzes\Support\Sanitize::intval($value, $min, $max);
    }
}

if (!function_exists('tfq_sanitize_boolval')) {
    /**
     * Sanitize boolean value
     * 
     * @param mixed $value Value to sanitize
     * @return bool Sanitized boolean
     */
    function tfq_sanitize_boolval($value) {
        if (function_exists('_deprecated_function')) _deprecated_function(__FUNCTION__, TFQ_VERSION);
        return \MTI\TypeformQuizzes\Support\Sanitize::boolval($value);
    }
}

if (!function_exists('tfq_sanitize_int')) {
    /**
     * Sanitize integer value (alias for tfq_sanitize_intval)
     * 
     * @param mixed $value Value to sanitize
     * @param int $min Minimum allowed value
     * @param int $max Maximum allowed value
     * @return int Sanitized integer
     */
    function tfq_sanitize_int($value, $min = PHP_INT_MIN, $max = PHP_INT_MAX) {
        if (function_exists('_deprecated_function')) _deprecated_function(__FUNCTION__, TFQ_VERSION);
        return \MTI\TypeformQuizzes\Support\Sanitize::intval($value, $min, $max);
    }
}

if (!function_exists('tfq_sanitize_bool')) {
    /**
     * Sanitize boolean value (alias for tfq_sanitize_boolval)
     * 
     * @param mixed $value Value to sanitize
     * @return bool Sanitized boolean
     */
    function tfq_sanitize_bool($value) {
        if (function_exists('_deprecated_function')) _deprecated_function(__FUNCTION__, TFQ_VERSION);
        return \MTI\TypeformQuizzes\Support\Sanitize::boolval($value);
    }
}

if (!function_exists('tfq_build_shortcode_context')) {
    /**
     * Build shortcode context
     * 
     * @param array $atts Shortcode attributes
     * @param string $content Shortcode content
     * @return array Context array
     */
    function tfq_build_shortcode_context($atts, $content = '') {
        if (function_exists('_deprecated_function')) _deprecated_function(__FUNCTION__, TFQ_VERSION);
        return \MTI\TypeformQuizzes\Frontend\Shortcodes\ContextBuilder::build((array)$atts, (string)$content);
    }
}

// AJAX compatibility functions
if (!function_exists('tfq_ajax_reorder')) {
    /**
     * Handle AJAX reorder request (backward compatibility)
     * 
     * @return void
     */
    function tfq_ajax_reorder() {
        if (function_exists('_deprecated_function')) _deprecated_function(__FUNCTION__, TFQ_VERSION);
        \MTI\TypeformQuizzes\Api\Ajax\Reorder::handle();
    }
}

if (!function_exists('tfq_render_error')) {
    /**
     * Render an error message
     * 
     * @param string $message Error message
     * @return string HTML error message
     */
    function tfq_render_error($message) {
        if (function_exists('_deprecated_function')) _deprecated_function(__FUNCTION__, TFQ_VERSION);
        return \MTI\TypeformQuizzes\Frontend\Shortcodes\ErrorHandler::render_error($message);
    }
}

if (!function_exists('tfq_log_error')) {
    /**
     * Log an error message
     * 
     * @param string $message Error message
     * @return void
     */
    function tfq_log_error($message) {
        if (function_exists('_deprecated_function')) _deprecated_function(__FUNCTION__, TFQ_VERSION);
        \MTI\TypeformQuizzes\Frontend\Shortcodes\ErrorHandler::log_error($message);
    }
}

// Asset management compatibility functions
if (!function_exists('tfq_enqueue_slider_assets')) {
    /**
     * Enqueue slider assets
     * 
     * @param array $atts Shortcode attributes
     * @return void
     */
    function tfq_enqueue_slider_assets($atts = []) {
        if (function_exists('_deprecated_function')) _deprecated_function(__FUNCTION__, TFQ_VERSION);
        \MTI\TypeformQuizzes\Frontend\Shortcodes\AssetManager::enqueue_slider_assets($atts);
    }
}

// URL and validation compatibility functions
if (!function_exists('tfq_is_valid_typeform_url')) {
    /**
     * Check if a URL is a valid Typeform URL
     * 
     * @param string $url URL to validate
     * @return bool True if valid, false otherwise
     */
    function tfq_is_valid_typeform_url($url) {
        if (function_exists('_deprecated_function')) _deprecated_function(__FUNCTION__, TFQ_VERSION);
        return \MTI\TypeformQuizzes\Frontend\Repository\QuizRepository::is_valid_typeform_url($url);
    }
}

if (!function_exists('tfq_get_embed_url')) {
    /**
     * Get embed URL for a Typeform
     * 
     * @param string $url Original Typeform URL
     * @return string Embed URL
     */
    function tfq_get_embed_url($url) {
        if (function_exists('_deprecated_function')) _deprecated_function(__FUNCTION__, TFQ_VERSION);
        return \MTI\TypeformQuizzes\Frontend\Shortcodes\SliderRenderer::get_embed_url($url);
    }
}

// Development and utility functions
if (!function_exists('tfq_is_local_development')) {
    /**
     * Check if running in local development
     * 
     * @return bool True if local development, false otherwise
     */
    function tfq_is_local_development() {
        if (function_exists('_deprecated_function')) _deprecated_function(__FUNCTION__, TFQ_VERSION);
        return \MTI\TypeformQuizzes\Frontend\Shortcodes\SliderRenderer::is_local_development();
    }
}

// Field callback compatibility functions
// These wrappers prevent fatal errors if themes/other code call old global field callback functions

// Basic Configuration Fields
if (!function_exists('tfq_render_field_max')) {
    /**
     * Render max field
     * 
     * @return void
     */
    function tfq_render_field_max() {
        if (function_exists('_deprecated_function')) _deprecated_function(__FUNCTION__, TFQ_VERSION);
        \MTI\TypeformQuizzes\Admin\SettingsPage::field_max();
    }
}

if (!function_exists('tfq_render_field_order')) {
    /**
     * Render order field
     * 
     * @return void
     */
    function tfq_render_field_order() {
        if (function_exists('_deprecated_function')) _deprecated_function(__FUNCTION__, TFQ_VERSION);
        \MTI\TypeformQuizzes\Admin\SettingsPage::field_order();
    }
}

if (!function_exists('tfq_render_field_center_on_click')) {
    /**
     * Render center_on_click field
     * 
     * @return void
     */
    function tfq_render_field_center_on_click() {
        if (function_exists('_deprecated_function')) _deprecated_function(__FUNCTION__, TFQ_VERSION);
        \MTI\TypeformQuizzes\Admin\SettingsPage::field_center_on_click();
    }
}

// Layout & Grid Settings Fields
if (!function_exists('tfq_render_field_max_width')) {
    /**
     * Render max_width field
     * 
     * @return void
     */
    function tfq_render_field_max_width() {
        if (function_exists('_deprecated_function')) _deprecated_function(__FUNCTION__, TFQ_VERSION);
        \MTI\TypeformQuizzes\Admin\SettingsPage::field_max_width();
    }
}

if (!function_exists('tfq_render_field_cols_desktop')) {
    /**
     * Render cols_desktop field
     * 
     * @return void
     */
    function tfq_render_field_cols_desktop() {
        \MTI\TypeformQuizzes\Admin\SettingsPage::field_cols_desktop();
    }
}

if (!function_exists('tfq_render_field_cols_tablet')) {
    /**
     * Render cols_tablet field
     * 
     * @return void
     */
    function tfq_render_field_cols_tablet() {
        \MTI\TypeformQuizzes\Admin\SettingsPage::field_cols_tablet();
    }
}

if (!function_exists('tfq_render_field_cols_mobile')) {
    /**
     * Render cols_mobile field
     * 
     * @return void
     */
    function tfq_render_field_cols_mobile() {
        \MTI\TypeformQuizzes\Admin\SettingsPage::field_cols_mobile();
    }
}

if (!function_exists('tfq_render_field_gap')) {
    /**
     * Render gap field
     * 
     * @return void
     */
    function tfq_render_field_gap() {
        \MTI\TypeformQuizzes\Admin\SettingsPage::field_gap();
    }
}

if (!function_exists('tfq_render_field_thumb_height')) {
    /**
     * Render thumb_height field
     * 
     * @return void
     */
    function tfq_render_field_thumb_height() {
        \MTI\TypeformQuizzes\Admin\SettingsPage::field_thumb_height();
    }
}

if (!function_exists('tfq_render_field_border_radius')) {
    /**
     * Render border_radius field
     * 
     * @return void
     */
    function tfq_render_field_border_radius() {
        \MTI\TypeformQuizzes\Admin\SettingsPage::field_border_radius();
    }
}

// Colors & Styling Fields
if (!function_exists('tfq_render_field_title_color')) {
    /**
     * Render title_color field
     * 
     * @return void
     */
    function tfq_render_field_title_color() {
        \MTI\TypeformQuizzes\Admin\SettingsPage::field_title_color();
    }
}

if (!function_exists('tfq_render_field_title_hover_color')) {
    /**
     * Render title_hover_color field
     * 
     * @return void
     */
    function tfq_render_field_title_hover_color() {
        \MTI\TypeformQuizzes\Admin\SettingsPage::field_title_hover_color();
    }
}

if (!function_exists('tfq_render_field_active_slide_border_color')) {
    /**
     * Render active_slide_border_color field
     * 
     * @return void
     */
    function tfq_render_field_active_slide_border_color() {
        \MTI\TypeformQuizzes\Admin\SettingsPage::field_active_slide_border_color();
    }
}

if (!function_exists('tfq_render_field_darken_inactive_slides')) {
    /**
     * Render darken_inactive_slides field
     * 
     * @return void
     */
    function tfq_render_field_darken_inactive_slides() {
        \MTI\TypeformQuizzes\Admin\SettingsPage::field_darken_inactive_slides();
    }
}

// Navigation Controls Fields
if (!function_exists('tfq_render_field_controls_spacing')) {
    /**
     * Render controls_spacing field
     * 
     * @return void
     */
    function tfq_render_field_controls_spacing() {
        \MTI\TypeformQuizzes\Admin\SettingsPage::field_controls_spacing();
    }
}

if (!function_exists('tfq_render_field_controls_spacing_tablet')) {
    /**
     * Render controls_spacing_tablet field
     * 
     * @return void
     */
    function tfq_render_field_controls_spacing_tablet() {
        \MTI\TypeformQuizzes\Admin\SettingsPage::field_controls_spacing_tablet();
    }
}

if (!function_exists('tfq_render_field_controls_bottom_spacing')) {
    /**
     * Render controls_bottom_spacing field
     * 
     * @return void
     */
    function tfq_render_field_controls_bottom_spacing() {
        \MTI\TypeformQuizzes\Admin\SettingsPage::field_controls_bottom_spacing();
    }
}

if (!function_exists('tfq_render_field_arrow_border_radius')) {
    /**
     * Render arrow_border_radius field
     * 
     * @return void
     */
    function tfq_render_field_arrow_border_radius() {
        \MTI\TypeformQuizzes\Admin\SettingsPage::field_arrow_border_radius();
    }
}

if (!function_exists('tfq_render_field_arrow_padding')) {
    /**
     * Render arrow_padding field
     * 
     * @return void
     */
    function tfq_render_field_arrow_padding() {
        \MTI\TypeformQuizzes\Admin\SettingsPage::field_arrow_padding();
    }
}

if (!function_exists('tfq_render_field_arrow_width')) {
    /**
     * Render arrow_width field
     * 
     * @return void
     */
    function tfq_render_field_arrow_width() {
        \MTI\TypeformQuizzes\Admin\SettingsPage::field_arrow_width();
    }
}

if (!function_exists('tfq_render_field_arrow_height')) {
    /**
     * Render arrow_height field
     * 
     * @return void
     */
    function tfq_render_field_arrow_height() {
        \MTI\TypeformQuizzes\Admin\SettingsPage::field_arrow_height();
    }
}

if (!function_exists('tfq_render_field_arrow_bg_color')) {
    /**
     * Render arrow_bg_color field
     * 
     * @return void
     */
    function tfq_render_field_arrow_bg_color() {
        \MTI\TypeformQuizzes\Admin\SettingsPage::field_arrow_bg_color();
    }
}

if (!function_exists('tfq_render_field_arrow_hover_bg_color')) {
    /**
     * Render arrow_hover_bg_color field
     * 
     * @return void
     */
    function tfq_render_field_arrow_hover_bg_color() {
        \MTI\TypeformQuizzes\Admin\SettingsPage::field_arrow_hover_bg_color();
    }
}

if (!function_exists('tfq_render_field_arrow_icon_color')) {
    /**
     * Render arrow_icon_color field
     * 
     * @return void
     */
    function tfq_render_field_arrow_icon_color() {
        \MTI\TypeformQuizzes\Admin\SettingsPage::field_arrow_icon_color();
    }
}

if (!function_exists('tfq_render_field_arrow_icon_hover_color')) {
    /**
     * Render arrow_icon_hover_color field
     * 
     * @return void
     */
    function tfq_render_field_arrow_icon_hover_color() {
        \MTI\TypeformQuizzes\Admin\SettingsPage::field_arrow_icon_hover_color();
    }
}

if (!function_exists('tfq_render_field_arrow_icon_size')) {
    /**
     * Render arrow_icon_size field
     * 
     * @return void
     */
    function tfq_render_field_arrow_icon_size() {
        \MTI\TypeformQuizzes\Admin\SettingsPage::field_arrow_icon_size();
    }
}

// Pagination Fields
if (!function_exists('tfq_render_field_pagination_dot_color')) {
    /**
     * Render pagination_dot_color field
     * 
     * @return void
     */
    function tfq_render_field_pagination_dot_color() {
        \MTI\TypeformQuizzes\Admin\SettingsPage::field_pagination_dot_color();
    }
}

if (!function_exists('tfq_render_field_pagination_active_dot_color')) {
    /**
     * Render pagination_active_dot_color field
     * 
     * @return void
     */
    function tfq_render_field_pagination_active_dot_color() {
        \MTI\TypeformQuizzes\Admin\SettingsPage::field_pagination_active_dot_color();
    }
}

if (!function_exists('tfq_render_field_pagination_dot_gap')) {
    /**
     * Render pagination_dot_gap field
     * 
     * @return void
     */
    function tfq_render_field_pagination_dot_gap() {
        \MTI\TypeformQuizzes\Admin\SettingsPage::field_pagination_dot_gap();
    }
}

if (!function_exists('tfq_render_field_pagination_dot_size')) {
    /**
     * Render pagination_dot_size field
     * 
     * @return void
     */
    function tfq_render_field_pagination_dot_size() {
        \MTI\TypeformQuizzes\Admin\SettingsPage::field_pagination_dot_size();
    }
}
