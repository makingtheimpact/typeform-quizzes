<?php
/**
 * Slider Renderer for Typeform Quizzes Shortcode
 * 
 * @package Typeform_Quizzes
 * @version 1.1.0
 * @author Making The Impact LLC
 */

namespace MTI\TypeformQuizzes\Frontend\Shortcodes;

use MTI\TypeformQuizzes\Frontend\Pagination\Dots;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

/**
 * Slider Renderer Class
 * 
 * Handles rendering of the quiz slider HTML.
 */
class SliderRenderer
{
    /**
     * Render quizzes slider HTML
     * 
     * @param array $quizzes Array of quiz data
     * @param string $slider_id Unique slider ID
     * @param array $atts Shortcode attributes
     * @param int $max_width Maximum width
     * @param int $thumb_height Thumbnail height
     * @param int $cols_desktop Desktop columns
     * @param int $cols_tablet Tablet columns
     * @param int $cols_mobile Mobile columns
     * @param int $gap Gap between items
     * @return string HTML output
     */
    public static function render_quizzes_slider_html($quizzes, $slider_id, $atts, $max_width, $thumb_height, $cols_desktop, $cols_tablet, $cols_mobile, $gap) {
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
        $pagination_dot_gap = min(max(intval($atts['pagination_dot_gap']), 0), 50);
        $pagination_dot_size = min(max(intval($atts['pagination_dot_size']), 4), 20);
        $active_slide_border_color = \MTI\TypeformQuizzes\Support\Sanitize::hex_color($atts['active_slide_border_color']);
        $darken_inactive_slides = intval($atts['darken_inactive_slides']);

        // Set thumbnail height
        $thumb_height_css = $thumb_height . 'px';

        // Suppress any potential output before HTML
        ob_start();
        ?>
        <div class="typeform-quizzes-slider-container" 
             style="max-width: <?php echo $max_width; ?>px; margin: 0 auto;"
             data-cols-desktop="<?php echo $cols_desktop; ?>"
             data-cols-tablet="<?php echo $cols_tablet; ?>"
             data-cols-mobile="<?php echo $cols_mobile; ?>"
             data-gap="<?php echo $gap; ?>"
             data-pagination-dot-color="<?php echo esc_attr($pagination_dot_color); ?>"
             data-pagination-active-dot-color="<?php echo esc_attr($pagination_active_dot_color); ?>"
             data-pagination-dot-size="<?php echo $pagination_dot_size; ?>"
             data-pagination-dot-gap="<?php echo $pagination_dot_gap; ?>">
            <!-- Quiz viewer - now at the top -->
            <div class="quiz-viewer">
                <div class="quiz-viewer-header">
                    <h2 class="quiz-viewer-title"><?php echo esc_html($quizzes[0]['title']); ?></h2>
                    <button class="quiz-viewer-close">×</button>
                </div>
                <div class="quiz-viewer-content">
                    <div style="position: absolute; top: 0; left: 0; right: 0; height: 4px;"></div>
                    <?php if (self::is_local_development()): ?>
                    <div id="dev-notice" style="background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 10px; margin-bottom: 10px; border-radius: 4px; font-size: 14px;">
                        <strong>Development Notice:</strong> Typeform CSP may block embedding in local development. 
                        <a href="<?php echo esc_url($quizzes[0]['url']); ?>" target="_blank" style="color: #0073aa; text-decoration: underline;">Click here to open in new tab</a>
                    </div>
                    <?php endif; ?>
                    <iframe id="quiz-iframe" 
                            src="<?php echo esc_url(self::get_embed_url($quizzes[0]['url'])); ?>" 
                            width="100%" 
                            height="600px" 
                            frameborder="0" 
                            style="border: none; display: block; background: white;"
                            allow="camera; microphone; geolocation; autoplay; encrypted-media; fullscreen; payment"
                            sandbox="allow-same-origin allow-scripts allow-forms allow-popups allow-popups-to-escape-sandbox allow-top-navigation-by-user-activation"
                            loading="lazy"
                            referrerpolicy="strict-origin-when-cross-origin"
                            onerror="this.style.display='none'; document.getElementById('dev-notice').style.display='block';">
                    </iframe>
                    <!-- Expand/Fullscreen Toggle Button -->
                    <button class="quiz-viewer-expand" style="
                        position: absolute;
                        bottom: 15px;
                        right: 15px;
                        width: 40px;
                        height: 40px;
                        background: rgba(0, 0, 0, 0.7);
                        border: none;
                        border-radius: 50%;
                        color: white;
                        cursor: pointer;
                        font-size: 16px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        transition: all 0.3s ease;
                        z-index: 10;
                    " title="Expand to fullscreen">
                        <i class="fa-solid fa-expand"></i>
                    </button>
                </div>
            </div>
            
            <div class="typeform-quizzes-slider swiper" id="<?php echo esc_attr($slider_id); ?>">
                <div class="swiper-wrapper">
                    <?php foreach ($quizzes as $index => $quiz): ?>
                        <div class="swiper-slide typeform-quiz-slide <?php echo $index === 0 ? 'active' : ''; ?>" data-quiz-url="<?php echo esc_url($quiz['url']); ?>" data-quiz-title="<?php echo esc_attr($quiz['title']); ?>">
                            <div class="quiz-thumbnail">
                                <?php if ($quiz['thumbnail']): ?>
                                    <img src="<?php echo esc_url($quiz['thumbnail']); ?>" 
                                         alt="<?php echo esc_attr($quiz['title']); ?>">
                                <?php else: ?>
                                    <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; position: absolute; top: 0; left: 0;">
                                        📝
                                    </div>
                                <?php endif; ?>
                                <div class="quiz-overlay">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </div>
                            </div>
                            <h3 class="quiz-title">
                                <?php echo esc_html($quiz['title']); ?>
                            </h3>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Navigation controls below the slider -->
            <div class="slider-controls">                
                <!-- All controls on the same row -->
                <div class="controls-row">
                    <button class="tfqrow-arrow tfqrow-arrow-prev" data-dir="prev" aria-label="Previous" data-bg-color="<?php echo $arrow_bg_color; ?>" data-hover-bg-color="<?php echo $arrow_hover_bg_color; ?>" data-icon-color="<?php echo $arrow_icon_color; ?>" data-hover-icon-color="<?php echo $arrow_icon_hover_color; ?>">
                        <i class="fa-solid fa-angle-left"></i>
                    </button>

                    <!-- Pagination dots centered -->
                    <?php echo Dots::markup($slider_id); ?>
                    
                    <button class="tfqrow-arrow tfqrow-arrow-next" data-dir="next" aria-label="Next" data-bg-color="<?php echo $arrow_bg_color; ?>" data-hover-bg-color="<?php echo $arrow_hover_bg_color; ?>" data-icon-color="<?php echo $arrow_icon_color; ?>" data-hover-icon-color="<?php echo $arrow_icon_hover_color; ?>">
                        <i class="fa-solid fa-angle-right"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Inline styles moved to typeform-quizzes-custom.css -->

        <!-- Inline JavaScript moved to typeform-quizzes.js -->
        <?php
        $output = ob_get_clean();
        
        // Add pagination JavaScript initialization
        $swiperParams = [
            'paginationDotColor' => $pagination_dot_color,
            'paginationActiveDotColor' => $pagination_active_dot_color,
            'paginationDotSize' => $pagination_dot_size,
            'paginationDotGap' => $pagination_dot_gap,
            'colsDesktop' => $cols_desktop,
            'colsTablet' => $cols_tablet,
            'colsMobile' => $cols_mobile,
            'gap' => $gap
        ];
        
        $output .= Dots::script($slider_id, $swiperParams);
        
        // Clean any potential PHP errors/warnings from output
        $output = preg_replace('/^.*?(<div class="typeform-quizzes-slider-container")/s', '$1', $output);
        
        return $output;
    }

    /**
     * Get embed URL for a Typeform
     * 
     * @param string $url Original Typeform URL
     * @return string Embed URL
     */
    public static function get_embed_url($url) {
        if (empty($url)) {
            return '';
        }
        
        // Check if we're in a development environment
        $is_local_dev = self::is_local_development();
        
        // Add embed parameters for better display
        $params = [
            'embed' => 'true',
            'embed-hide-headers' => 'true',
            'embed-hide-footer' => 'true',
            'embed-opacity' => '100',
            'typeform-embed' => 'popup-blank',
            'embed-open' => 'true'
        ];
        
        // Add development-specific parameters
        if ($is_local_dev) {
            $params['embed-opacity'] = '0';
        }
        
        // Build the URL with parameters
        $separator = strpos($url, '?') !== false ? '&' : '?';
        return $url . $separator . http_build_query($params);
    }

    /**
     * Check if running in local development
     * 
     * @return bool True if local development, false otherwise
     */
    public static function is_local_development() {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $is_local = (
            strpos($host, 'localhost') !== false ||
            strpos($host, '127.0.0.1') !== false ||
            strpos($host, '192.168.') !== false ||
            strpos($host, '10.0.') !== false ||
            strpos($host, '.local') !== false ||
            strpos($host, '.test') !== false ||
            strpos($host, '.dev') !== false ||
            (defined('WP_DEBUG') && WP_DEBUG) ||
            (defined('WP_ENV') && WP_ENV === 'development')
        );
        
        return $is_local;
    }
}
