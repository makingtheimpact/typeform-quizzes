<?php
/**
 * Asset Manager for Typeform Quizzes Shortcode
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
 * Asset Manager Class
 * 
 * Handles asset enqueuing for the shortcode system.
 */
class AssetManager
{
    /**
     * Enqueue slider assets
     * 
     * @param array $atts Shortcode attributes
     * @return void
     */
    public static function enqueue_slider_assets($atts = []) {
        // Delegate to the main Assets class
        \MTI\TypeformQuizzes\Frontend\Assets::enqueue_slider_assets($atts);
    }
}
