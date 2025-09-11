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
        return \MTI\TypeformQuizzes\Frontend\Shortcodes\QuizRetriever::get_quizzes($max_quizzes, $order);
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
        return \MTI\TypeformQuizzes\Frontend\Shortcodes\QuizRetriever::is_valid_typeform_url($url);
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
        return \MTI\TypeformQuizzes\Frontend\Shortcodes\SliderRenderer::is_local_development();
    }
}
