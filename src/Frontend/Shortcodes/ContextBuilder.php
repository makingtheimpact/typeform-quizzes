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
class ContextBuilder
{
    /**
     * Build shortcode context
     * 
     * @param array $atts Shortcode attributes
     * @param string $content Shortcode content
     * @return array Context array
     */
    public static function build_shortcode_context($atts, $content = ''): array {
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

        // Call the SAME legacy helpers / options you use today
        $slider_id   = 'tfq-slider-' . uniqid();
        $slides      = [];   // however you currently build slide data
        $classes     = [];   // same classes you echo on the wrapper
        $swiper_args = [];   // your current Swiper init args
        $colors      = [];   // dot/arrow color settings you read from options

        return compact('atts','slider_id','slides','classes','swiper_args','colors');
    }
}
