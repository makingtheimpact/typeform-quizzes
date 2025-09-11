<?php
/**
 * Typeform Quizzes Shortcode Handler
 * 
 * @package Typeform_Quizzes
 * @version 1.1.0
 * @author Making The Impact LLC
 */

namespace MTI\TypeformQuizzes\Frontend\Shortcodes;

use MTI\TypeformQuizzes\Support\Template;

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
     * Called by the old global function. This method will:
     * 1) Build a context using legacy helpers (for now),
     * 2) Render using a template (markup identical to today),
     * 3) Keep filters/actions intact.
     * 
     * @param array $atts Shortcode attributes
     * @param string $content Shortcode content
     * @return string
     */
    public static function render_via_legacy($atts, $content = ''): string
    {
        // 1) Build the context using legacy code for now
        if (function_exists('tfq_build_shortcode_context')) {
            $ctx = tfq_build_shortcode_context($atts, $content);
        } else {
            // First run: just fall back to the original callback body
            // Return whatever the old function returned (HTML string).
            if (function_exists('typeform_quizzes_shortcode_legacy_body')) {
                return typeform_quizzes_shortcode_legacy_body($atts, $content);
            }
            // If neither exists, fail soft:
            return '';
        }

        // 2) Render the exact same HTML via a template
        $html = Template::render('shortcode-typeform-quizzes.php', ['ctx' => $ctx]);

        // 3) Preserve existing filter names for compatibility
        $html = apply_filters('tfq_shortcode_html', $html, $ctx);

        return $html;
    }
}
