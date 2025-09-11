<?php
/**
 * Typeform Quizzes Shortcode Handler
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
 * Typeform Quizzes Shortcode Class
 * 
 * Handles the typeform_quizzes shortcode for rendering quiz sliders.
 */
class TypeformQuizzesShortcode
{
    /**
     * Register the shortcode
     * 
     * @return void
     */
    public static function register()
    {
        add_shortcode('typeform_quizzes_slider', [self::class, 'render']);
    }

    /**
     * Render the typeform quizzes shortcode
     * 
     * @param array $atts Shortcode attributes
     * @param string $content Shortcode content
     * @return string
     */
    public static function render($atts, $content = '')
    {
        // Delegate to the original method in the main class
        // This preserves all existing functionality while allowing for future refactoring
        return \Typeform_Quizzes::render_quizzes_slider($atts);
    }
}
