<?php
/**
 * Frontend Assets Handler
 * 
 * @package Typeform_Quizzes
 * @version 1.1.0
 * @author Making The Impact LLC
 */

namespace MTI\TypeformQuizzes\Frontend;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

/**
 * Frontend Assets Class
 * 
 * Handles enqueuing of public-facing CSS and JavaScript assets.
 */
class Assets
{
    /**
     * Initialize frontend assets
     * 
     * @return void
     */
    public static function init()
    {
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);
    }

    /**
     * Enqueue necessary scripts and styles for the slider
     * 
     * @return void
     */
    public static function enqueue_scripts()
    {
        // Only load assets if shortcodes are present on the page
        global $post;
        if (!$post || !has_shortcode($post->post_content, 'typeform_quiz') && !has_shortcode($post->post_content, 'typeform_quizzes_slider')) {
            return;
        }

        // Check if any FontAwesome version is already loaded
        $fa_loaded = wp_style_is('font-awesome', 'enqueued') || 
                     wp_style_is('fontawesome', 'enqueued') || 
                     wp_style_is('font-awesome-5', 'enqueued') ||
                     wp_style_is('font-awesome-6', 'enqueued') ||
                     wp_style_is('font-awesome-4', 'enqueued') ||
                     wp_style_is('typeform-quizzes-fontawesome', 'enqueued');

        if (!$fa_loaded) {
            // Load FontAwesome 6.4.0 with a unique handle to avoid conflicts
            $fontawesome_url = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
            wp_enqueue_style('typeform-quizzes-fontawesome', $fontawesome_url, [], '6.4.0');
            wp_style_add_data('typeform-quizzes-fontawesome', 'defer', true);
        }

        // Enqueue slider assets
        self::enqueue_slider_assets();
    }

    /**
     * Enqueue slider assets with fallback handling
     * 
     * @param array $atts Shortcode attributes (optional)
     * @return void
     */
    private static function enqueue_slider_assets($atts = [])
    {
        wp_enqueue_script('jquery');

        // Check for page builder and hosting compatibility
        $compatibility = self::check_page_builder_compatibility();
        $hosting = self::check_hosting_compatibility();

        // Check if Swiper is already enqueued to prevent conflicts
        $swiper_handles = ['swiper', 'swiper-bundle', 'swiper-js', 'swiper-css', 'typeform-quizzes-swiper'];
        $swiper_js_enqueued = false;
        $swiper_css_enqueued = false;

        foreach ($swiper_handles as $handle) {
            if (wp_script_is($handle, 'enqueued') || wp_script_is($handle, 'done')) {
                $swiper_js_enqueued = true;
            }
            if (wp_style_is($handle, 'enqueued') || wp_style_is($handle, 'done')) {
                $swiper_css_enqueued = true;
            }
        }

        // Additional check for global Swiper object
        if (!$swiper_js_enqueued && !wp_script_is('swiper', 'registered')) {
            // Check if Swiper is loaded globally (common with page builders)
            add_action('wp_footer', function() {
                if (wp_script_is('typeform-quizzes-swiper', 'enqueued')) {
                    echo '<script>if (typeof Swiper === "undefined" && typeof window.Swiper === "undefined") { console.warn("Typeform Quizzes: Swiper not found, slider may not work properly"); }</script>';
                }
            }, 999);
        }

        // Only enqueue Swiper if not already loaded
        if (!$swiper_js_enqueued) {
            $swiper_js_url = 'https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.js';
            $fallback_url = 'https://unpkg.com/swiper@8/swiper-bundle.min.js';

            wp_enqueue_script('typeform-quizzes-swiper', $swiper_js_url, ['jquery'], '8.0.0', true);
            wp_script_add_data('typeform-quizzes-swiper', 'defer', true);

            // Add fallback for hosting platforms that might block CDN
            if ($hosting['needs_optimization']) {
                wp_script_add_data('typeform-quizzes-swiper', 'integrity', 'sha384-...');
                wp_script_add_data('typeform-quizzes-swiper', 'crossorigin', 'anonymous');
            }
        }

        if (!$swiper_css_enqueued) {
            $swiper_css_url = 'https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.css';
            wp_enqueue_style('typeform-quizzes-swiper', $swiper_css_url, [], '8.0.0');
            wp_style_add_data('typeform-quizzes-swiper', 'defer', true);
        }

        // Enqueue custom CSS file after swiper styles
        $custom_css_url = TFQ_PLUGIN_URL . 'assets/css/typeform-quizzes-custom.css';
        wp_enqueue_style('typeform-quizzes-custom', $custom_css_url, ['typeform-quizzes-swiper'], TFQ_VERSION);

        // Enqueue custom JavaScript file
        $custom_js_url = TFQ_PLUGIN_URL . 'assets/js/typeform-quizzes.js';
        wp_enqueue_script('typeform-quizzes-custom', $custom_js_url, ['jquery', 'typeform-quizzes-swiper'], TFQ_VERSION, true);

        // Add CSS variables for custom styles
        self::add_custom_css_variables($atts);

        // Add fallback handling via JavaScript
        if (!$swiper_js_enqueued) {
            wp_add_inline_script('typeform-quizzes-swiper', '
                // Fallback for Swiper if CDN fails
                if (typeof Swiper === "undefined") {
                    console.warn("Typeform Quizzes: Swiper CDN failed to load, using fallback");
                    // You could implement a local fallback here if needed
                }
            ', 'after');
        }

        // Add CSS scoping to prevent conflicts with other plugins
        add_action('wp_head', function() use ($compatibility, $hosting) {
            $optimizations = self::get_hosting_optimizations($hosting);
            echo '<style id="typeform-quizzes-conflict-prevention">
            /* Typeform Quizzes - Conflict Prevention CSS */
            .typeform-quizzes-slider-container {
                position: relative;
                overflow: hidden;
            }
            .typeform-quizzes-slider-container .swiper {
                width: 100%;
                height: 100%;
            }
            .typeform-quizzes-slider-container .swiper-slide {
                text-align: center;
                font-size: 18px;
                background: #fff;
                display: flex;
                justify-content: center;
                align-items: center;
            }
            ' . $optimizations . '
            </style>';
        }, 1);
    }

    /**
     * Add custom CSS variables for styling
     * 
     * @param array $atts Shortcode attributes (optional)
     * @return void
     */
    private static function add_custom_css_variables($atts = [])
    {
        // Get defaults from admin settings
        $defaults = get_option('typeform_quizzes_defaults', []);

        // Merge shortcode attributes with defaults
        $atts = shortcode_atts([
            'thumb_height' => $defaults['thumb_height'] ?? 200,
            'arrow_icon_size' => $defaults['arrow_icon_size'] ?? 16,
            'arrow_icon_color' => $defaults['arrow_icon_color'] ?? '#333333',
            'arrow_icon_hover_color' => $defaults['arrow_icon_hover_color'] ?? '#666666',
            'arrow_bg_color' => $defaults['arrow_bg_color'] ?? '#ffffff',
            'arrow_hover_bg_color' => $defaults['arrow_hover_bg_color'] ?? '#f0f0f0',
            'arrow_width' => $defaults['arrow_width'] ?? 40,
            'arrow_height' => $defaults['arrow_height'] ?? 40,
            'arrow_border_radius' => $defaults['arrow_border_radius'] ?? 4,
            'title_color' => $defaults['title_color'] ?? '#333333',
            'title_hover_color' => $defaults['title_hover_color'] ?? '#666666',
            'active_slide_border_color' => $defaults['active_slide_border_color'] ?? '#007cba',
            'border_radius' => $defaults['border_radius'] ?? 8,
            'controls_spacing' => $defaults['controls_spacing'] ?? 20,
            'controls_spacing_tablet' => $defaults['controls_spacing_tablet'] ?? 15,
            'controls_bottom_spacing' => $defaults['controls_bottom_spacing'] ?? 20,
            'pagination_dot_color' => $defaults['pagination_dot_color'] ?? '#cccccc',
            'pagination_active_dot_color' => $defaults['pagination_active_dot_color'] ?? '#007cba',
            'pagination_dot_size' => $defaults['pagination_dot_size'] ?? 8,
            'pagination_dot_gap' => $defaults['pagination_dot_gap'] ?? 8,
        ], $atts);

        // Set values from merged attributes
        $thumb_height = $atts['thumb_height'];
        $arrow_icon_size = $atts['arrow_icon_size'];
        $arrow_icon_color = $atts['arrow_icon_color'];
        $arrow_icon_hover_color = $atts['arrow_icon_hover_color'];
        $arrow_bg_color = $atts['arrow_bg_color'];
        $arrow_hover_bg_color = $atts['arrow_hover_bg_color'];
        $arrow_width = $atts['arrow_width'];
        $arrow_height = $atts['arrow_height'];
        $arrow_border_radius = $atts['arrow_border_radius'];
        $title_color = $atts['title_color'];
        $title_hover_color = $atts['title_hover_color'];
        $active_slide_border_color = $atts['active_slide_border_color'];
        $border_radius = $atts['border_radius'];
        $controls_spacing = $atts['controls_spacing'];
        $controls_spacing_tablet = $atts['controls_spacing_tablet'];
        $controls_bottom_spacing = $atts['controls_bottom_spacing'];
        $pagination_dot_color = $atts['pagination_dot_color'];
        $pagination_active_dot_color = $atts['pagination_active_dot_color'];
        $pagination_dot_size = $atts['pagination_dot_size'];
        $pagination_dot_gap = $atts['pagination_dot_gap'];

        // Add CSS variables
        wp_add_inline_style('typeform-quizzes-custom', "
            :root {
                --tfq-thumb-height: {$thumb_height}px;
                --tfq-arrow-icon-size: {$arrow_icon_size}px;
                --tfq-arrow-icon-color: {$arrow_icon_color};
                --tfq-arrow-icon-hover-color: {$arrow_icon_hover_color};
                --tfq-arrow-bg-color: {$arrow_bg_color};
                --tfq-arrow-hover-bg-color: {$arrow_hover_bg_color};
                --tfq-arrow-width: {$arrow_width}px;
                --tfq-arrow-height: {$arrow_height}px;
                --tfq-arrow-border-radius: {$arrow_border_radius}px;
                --tfq-title-color: {$title_color};
                --tfq-title-hover-color: {$title_hover_color};
                --tfq-active-slide-border-color: {$active_slide_border_color};
                --tfq-border-radius: {$border_radius}px;
                --tfq-controls-spacing: {$controls_spacing}px;
                --tfq-controls-spacing-tablet: {$controls_spacing_tablet}px;
                --tfq-controls-bottom-spacing: {$controls_bottom_spacing}px;
                --tfq-pagination-dot-color: {$pagination_dot_color};
                --tfq-pagination-active-dot-color: {$pagination_active_dot_color};
                --tfq-pagination-dot-size: {$pagination_dot_size}px;
                --tfq-pagination-dot-gap: {$pagination_dot_gap}px;
            }
        ");
    }

    /**
     * Check page builder compatibility
     * 
     * @return array
     */
    private static function check_page_builder_compatibility()
    {
        $compatibility = [
            'elementor' => defined('ELEMENTOR_VERSION'),
            'beaver_builder' => class_exists('FLBuilder'),
            'divi' => defined('ET_BUILDER_VERSION'),
            'gutenberg' => function_exists('register_block_type'),
            'classic_editor' => !function_exists('register_block_type'),
        ];

        return $compatibility;
    }

    /**
     * Check hosting compatibility
     * 
     * @return array
     */
    private static function check_hosting_compatibility()
    {
        $hosting = [
            'wp_engine' => defined('WPE_PLUGIN_BASE'),
            'kinsta' => defined('KINSTAMU_VERSION'),
            'siteground' => defined('SITEGROUND_OPTIMIZER_VERSION'),
            'easywp' => defined('EASYWP_VERSION'),
            'godaddy' => defined('GD_SYSTEM_PLUGIN_DIR'),
            'bluehost' => defined('BLUEHOST_PLUGIN_VERSION'),
            'hostgator' => defined('HOSTGATOR_PLUGIN_VERSION'),
            'needs_optimization' => false,
        ];

        // Check if optimization is needed
        $hosting['needs_optimization'] = $hosting['wp_engine'] || $hosting['kinsta'] || $hosting['siteground'];

        return $hosting;
    }

    /**
     * Get hosting optimizations
     * 
     * @param array $hosting Hosting compatibility array
     * @return string
     */
    private static function get_hosting_optimizations($hosting)
    {
        $optimizations = '';

        if ($hosting['wp_engine']) {
            $optimizations .= '
                .typeform-quizzes-slider-container {
                    will-change: transform;
                }
            ';
        }

        if ($hosting['kinsta']) {
            $optimizations .= '
                .typeform-quizzes-slider-container .swiper-slide {
                    backface-visibility: hidden;
                }
            ';
        }

        if ($hosting['siteground']) {
            $optimizations .= '
                .typeform-quizzes-slider-container {
                    contain: layout style paint;
                }
            ';
        }

        return $optimizations;
    }
}
