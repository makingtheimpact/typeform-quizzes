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

    /**
     * New render method that doesn't rely on legacy functions
     * 
     * @param array $atts Shortcode attributes
     * @param string $content Shortcode content
     * @return string
     */
    public static function render($atts, $content = ''): string
    {
        try {
            // Validate and sanitize input
            if (!is_array($atts)) {
                $atts = [];
            }
            
            // Sanitize all input attributes
            $atts = \Typeform_Quizzes::sanitize_shortcode_attributes($atts);
            
            // Get defaults from admin settings
            $defaults = get_option('typeform_quizzes_defaults', []);
            
            $atts = shortcode_atts([
                'max' => $defaults['max'] ?? 20,
                'max_width' => $defaults['max_width'] ?? 1450,
                'thumb_height' => $defaults['thumb_height'] ?? 200,
                'cols_desktop' => $defaults['cols_desktop'] ?? 6,
                'cols_tablet' => $defaults['cols_tablet'] ?? 3,
                'cols_mobile' => $defaults['cols_mobile'] ?? 2,
                'gap' => $defaults['gap'] ?? 20,
                'center_on_click' => $defaults['center_on_click'] ?? true,
                'border_radius' => $defaults['border_radius'] ?? 16,
                'title_color' => $defaults['title_color'] ?? '#000000',
                'title_hover_color' => $defaults['title_hover_color'] ?? '#777777',
                'controls_spacing' => $defaults['controls_spacing'] ?? 56,
                'controls_spacing_tablet' => $defaults['controls_spacing_tablet'] ?? 56,
                'controls_bottom_spacing' => $defaults['controls_bottom_spacing'] ?? 20,
                'arrow_border_radius' => $defaults['arrow_border_radius'] ?? 0,
                'arrow_padding' => $defaults['arrow_padding'] ?? 3,
                'arrow_width' => $defaults['arrow_width'] ?? 35,
                'arrow_height' => $defaults['arrow_height'] ?? 35,
                'arrow_bg_color' => $defaults['arrow_bg_color'] ?? '#111111',
                'arrow_hover_bg_color' => $defaults['arrow_hover_bg_color'] ?? '#000000',
                'arrow_icon_color' => $defaults['arrow_icon_color'] ?? '#ffffff',
                'arrow_icon_hover_color' => $defaults['arrow_icon_hover_color'] ?? '#ffffff',
                'arrow_icon_size' => $defaults['arrow_icon_size'] ?? 28,
                'pagination_dot_color' => $defaults['pagination_dot_color'] ?? '#cfcfcf',
                'pagination_active_dot_color' => $defaults['pagination_active_dot_color'] ?? '#111111',
                'pagination_dot_gap' => $defaults['pagination_dot_gap'] ?? 10,
                'pagination_dot_size' => $defaults['pagination_dot_size'] ?? 8,
                'active_slide_border_color' => $defaults['active_slide_border_color'] ?? '#0073aa',
                'darken_inactive_slides' => $defaults['darken_inactive_slides'] ?? 1,
                'order' => $defaults['order'] ?? 'menu_order'
            ], $atts, 'typeform_quizzes_slider');

            // Validate and sanitize inputs with proper bounds checking
            $max_quizzes = min(max(intval($atts['max']), 1), \Typeform_Quizzes::MAX_QUIZZES_LIMIT);
            $max_width = min(max(intval($atts['max_width']), 200), 2000);
            $thumb_height = min(max(intval($atts['thumb_height']), 50), 1000);
            
            // Validate column settings
            $cols_desktop = min(max(intval($atts['cols_desktop']), 1), 12);
            $cols_tablet = min(max(intval($atts['cols_tablet']), 1), 8);
            $cols_mobile = min(max(intval($atts['cols_mobile']), 1), 4);
            
            $gap = min(max(intval($atts['gap']), 0), 100);
            $center_on_click = (bool) $atts['center_on_click'];
            
            // Validate order parameter
            $valid_orders = ['menu_order', 'date', 'title', 'rand'];
            $order = in_array($atts['order'], $valid_orders) ? $atts['order'] : 'menu_order';

            // Get quizzes using new class
            $quizzes = QuizRetriever::get_quizzes($max_quizzes, $order);
            
            if (empty($quizzes)) {
                return ErrorHandler::render_error('No quizzes found. Please add some Typeform Quizzes first.');
            }

            // Generate unique ID for this slider instance
            $slider_id = 'tfq-slider-' . uniqid();
            
            // Enqueue scripts and styles using new class
            AssetManager::enqueue_slider_assets($atts);

            // Render the slider using new class
            $html = SliderRenderer::render_quizzes_slider_html($quizzes, $slider_id, $atts, $max_width, $thumb_height, $cols_desktop, $cols_tablet, $cols_mobile, $gap, $center_on_click);

            // Preserve existing filter names for compatibility
            $html = apply_filters('tfq_shortcode_html', $html, compact('atts', 'slider_id', 'quizzes'));

            return $html;

        } catch (\Exception $e) {
            ErrorHandler::log_error('Quiz slider error: ' . $e->getMessage());
            return ErrorHandler::render_error('An error occurred while loading the quiz slider.');
        }
    }
}
