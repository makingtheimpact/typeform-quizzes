<?php
/**
 * Context Builder for Typeform Quizzes Shortcode
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
 * Context Builder Class
 * 
 * Handles building the context array for the shortcode template.
 */
final class ContextBuilder
{
    /**
     * Build shortcode context
     * 
     * @param array $atts Shortcode attributes
     * @param string $content Shortcode content
     * @return array Context array
     */
    public static function build(array $atts, string $content = ''): array {
        // Validate and sanitize input
        if (!is_array($atts)) {
            $atts = [];
        }
        
        // Debug logging for raw attributes
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("TFQ Debug - ContextBuilder: Raw shortcode atts: " . print_r($atts, true));
        }
        
        // Sanitize all input attributes
        $atts = \MTI\TypeformQuizzes\Support\Sanitize::shortcode_attributes($atts);
        
        // Get defaults from admin settings
        $defaults = get_option('typeform_quizzes_defaults', []);
        
        // Use the SAME atts parsing you already have
        $atts = shortcode_atts([
            'max' => $defaults['max'] ?? 20,
            'max_width' => $defaults['max_width'] ?? 1450,
            'thumb_height' => $defaults['thumb_height'] ?? 200,
            'cols_desktop' => $defaults['cols_desktop'] ?? 6,
            'cols_tablet' => $defaults['cols_tablet'] ?? 3,
            'cols_mobile' => $defaults['cols_mobile'] ?? 2,
            'gap' => $defaults['gap'] ?? 20,
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

        // Allow unlimited with max="all", 0, or -1
        $raw_max = is_string($atts['max']) ? strtolower(trim($atts['max'])) : $atts['max'];
        if ($raw_max === 'all' || intval($raw_max) === 0 || intval($raw_max) === -1) {
            $max_quizzes = -1; // WordPress 'no limit'
        } else {
            $max_quizzes = min(max(intval($atts['max']), 1), \MTI\TypeformQuizzes\Support\Constants::MAX_QUIZZES_LIMIT);
        }
        $max_width = min(max(intval($atts['max_width']), 200), 2000);
        $thumb_height = min(max(intval($atts['thumb_height']), 50), 1000);
        
        
        // Validate column settings
        $cols_desktop = min(max(intval($atts['cols_desktop']), 1), 12);
        $cols_tablet = min(max(intval($atts['cols_tablet']), 1), 8);
        $cols_mobile = min(max(intval($atts['cols_mobile']), 1), 4);
        
        $gap = min(max(intval($atts['gap']), 0), 100);
        
        // Validate order parameter
        $valid_orders = ['menu_order', 'date', 'title', 'rand'];
        $order = in_array($atts['order'], $valid_orders) ? $atts['order'] : 'menu_order';

        // Get quizzes using QuizRepository
        $quizzes = \MTI\TypeformQuizzes\Frontend\Repository\QuizRepository::get_quizzes($max_quizzes, $order);
        
        if (empty($quizzes)) {
            // Return error context instead of empty quizzes
            return [
                'error' => 'No quizzes found. Please add some Typeform Quizzes first.',
                'atts' => $atts,
                'slider_id' => 'tfq-slider-' . uniqid()
            ];
        }

        // Generate unique ID for this slider instance
        $slider_id = 'tfq-slider-' . uniqid();
        
        // Pre-compute all styling attributes that the template needs
        $border_radius = intval($atts['border_radius']);
        $title_color = \MTI\TypeformQuizzes\Support\Sanitize::hex_color($atts['title_color']);
        $title_hover_color = \MTI\TypeformQuizzes\Support\Sanitize::hex_color($atts['title_hover_color']);
        $controls_spacing = intval($atts['controls_spacing']);
        $controls_spacing_tablet = intval($atts['controls_spacing_tablet']);
        $controls_bottom_spacing = intval($atts['controls_bottom_spacing']);
        $arrow_border_radius = intval($atts['arrow_border_radius']);
        $arrow_padding = intval($atts['arrow_padding']);
        $arrow_width = intval($atts['arrow_width']);
        $arrow_height = intval($atts['arrow_height']);
        $arrow_bg_color = \MTI\TypeformQuizzes\Support\Sanitize::hex_color($atts['arrow_bg_color']);
        $arrow_hover_bg_color = \MTI\TypeformQuizzes\Support\Sanitize::hex_color($atts['arrow_hover_bg_color']);
        $arrow_icon_color = \MTI\TypeformQuizzes\Support\Sanitize::hex_color($atts['arrow_icon_color']);
        $arrow_icon_hover_color = \MTI\TypeformQuizzes\Support\Sanitize::hex_color($atts['arrow_icon_hover_color']);
        $arrow_icon_size = intval($atts['arrow_icon_size']);
        $pagination_dot_color = \MTI\TypeformQuizzes\Support\Sanitize::hex_color($atts['pagination_dot_color']);
        $pagination_active_dot_color = \MTI\TypeformQuizzes\Support\Sanitize::hex_color($atts['pagination_active_dot_color']);
        $pagination_dot_gap = \MTI\TypeformQuizzes\Support\Sanitize::integer_clamp($atts['pagination_dot_gap'], 0, 50, 10);
        $pagination_dot_size = \MTI\TypeformQuizzes\Support\Sanitize::integer_clamp($atts['pagination_dot_size'], 4, 20, 8);
        $active_slide_border_color = \MTI\TypeformQuizzes\Support\Sanitize::hex_color($atts['active_slide_border_color']);
        $darken_inactive_slides = intval($atts['darken_inactive_slides']);
        
        // Set thumbnail height CSS
        $thumb_height_css = $thumb_height . 'px';
        
        // Swiper parameters for JavaScript
        $swiper_params = [
            'paginationDotColor' => $pagination_dot_color,
            'paginationActiveDotColor' => $pagination_active_dot_color,
            'paginationDotSize' => $pagination_dot_size,
            'paginationDotGap' => $pagination_dot_gap,
            'colsDesktop' => $cols_desktop,
            'colsTablet' => $cols_tablet,
            'colsMobile' => $cols_mobile,
            'gap' => $gap
        ];
        
        // Enqueue scripts and styles using AssetManager
        AssetManager::enqueue_slider_assets($atts);

        return [
            'atts' => $atts,
            'slider_id' => $slider_id,
            'quizzes' => $quizzes,
            'max_width' => $max_width,
            'thumb_height' => $thumb_height,
            'thumb_height_css' => $thumb_height_css,
            'cols_desktop' => $cols_desktop,
            'cols_tablet' => $cols_tablet,
            'cols_mobile' => $cols_mobile,
            'gap' => $gap,
            'border_radius' => $border_radius,
            'title_color' => $title_color,
            'title_hover_color' => $title_hover_color,
            'controls_spacing' => $controls_spacing,
            'controls_spacing_tablet' => $controls_spacing_tablet,
            'controls_bottom_spacing' => $controls_bottom_spacing,
            'arrow_border_radius' => $arrow_border_radius,
            'arrow_padding' => $arrow_padding,
            'arrow_width' => $arrow_width,
            'arrow_height' => $arrow_height,
            'arrow_bg_color' => $arrow_bg_color,
            'arrow_hover_bg_color' => $arrow_hover_bg_color,
            'arrow_icon_color' => $arrow_icon_color,
            'arrow_icon_hover_color' => $arrow_icon_hover_color,
            'arrow_icon_size' => $arrow_icon_size,
            'pagination_dot_color' => $pagination_dot_color,
            'pagination_active_dot_color' => $pagination_active_dot_color,
            'pagination_dot_gap' => $pagination_dot_gap,
            'pagination_dot_size' => $pagination_dot_size,
            'active_slide_border_color' => $active_slide_border_color,
            'darken_inactive_slides' => $darken_inactive_slides,
            'swiper_params' => $swiper_params
        ];
    }

    /**
     * Build shortcode context (backward compatibility)
     * 
     * @param array $atts Shortcode attributes
     * @param string $content Shortcode content
     * @return array Context array
     */
    public static function build_shortcode_context($atts, $content = ''): array {
        if (function_exists('_deprecated_function')) _deprecated_function(__FUNCTION__, TFQ_VERSION);
        return self::build($atts, $content);
    }
}
