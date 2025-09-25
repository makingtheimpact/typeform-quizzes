<?php
/**
 * Shortcode Manager
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
 * Shortcode Manager Class
 * 
 * Handles shortcode registration and rendering.
 */
final class ShortcodeManager
{
    /**
     * Initialize shortcode functionality
     */
    public static function init()
    {
        add_action('init', [__CLASS__, 'register_shortcodes']);
    }

    /**
     * Register shortcodes
     */
    public static function register_shortcodes()
    {
        // Individual quiz shortcode
        add_shortcode('typeform_quiz', [__CLASS__, 'render_individual_quiz']);
        
        // Quiz slider shortcode
        add_shortcode('typeform_quizzes_slider', [__CLASS__, 'render_quiz_slider']);
        
        // Separate main viewer shortcode
        add_shortcode('typeform_quizzes_viewer', [__CLASS__, 'render_quiz_viewer']);
    }

    /**
     * Render individual quiz shortcode
     */
    public static function render_individual_quiz($atts, $content = '')
    {
        $atts = shortcode_atts([
            'id' => '',
            'url' => '',
            'width' => '100%',
            'height' => '500px',
            'title' => ''
        ], $atts);

        // Get quiz data
        if (!empty($atts['id'])) {
            $post = get_post($atts['id']);
            if (!$post || $post->post_type !== 'typeform_quiz') {
                return '<p>' . __('Quiz not found', 'typeform-quizzes') . '</p>';
            }
            $typeform_url = get_post_meta($post->ID, '_typeform_url', true);
            $title = $post->post_title;
        } elseif (!empty($atts['url'])) {
            $typeform_url = $atts['url'];
            $title = $atts['title'];
        } else {
            return '<p>' . __('Please provide either a quiz ID or URL', 'typeform-quizzes') . '</p>';
        }

        if (empty($typeform_url)) {
            return '<p>' . __('No Typeform URL found', 'typeform-quizzes') . '</p>';
        }

        // Convert to embed URL
        $embed_url = self::convert_to_embed_url($typeform_url);
        
        if (!$embed_url) {
            return '<p>' . __('Invalid Typeform URL', 'typeform-quizzes') . '</p>';
        }

        // Render quiz
        ob_start();
        ?>
        <div class="typeform-quiz-container" style="width: <?php echo esc_attr($atts['width']); ?>; margin: 0 auto;">
            <?php if (!empty($title)): ?>
                <h3 class="quiz-title"><?php echo esc_html($title); ?></h3>
            <?php endif; ?>
            <iframe src="<?php echo esc_url($embed_url); ?>" 
                    width="100%" 
                    height="<?php echo esc_attr($atts['height']); ?>" 
                    frameborder="0" 
                    allowfullscreen>
            </iframe>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render quiz slider shortcode
     */
    public static function render_quiz_slider($atts, $content = '')
    {
        // Use the existing TypeformQuizzesShortcode class
        return \MTI\TypeformQuizzes\Frontend\Shortcodes\TypeformQuizzesShortcode::render($atts, $content);
    }

    /**
     * Render separate quiz viewer shortcode
     */
    public static function render_quiz_viewer($atts, $content = '')
    {
        // Get the global setting for showing titles
        $global_show_title = \MTI\TypeformQuizzes\Services\Options::get('show_viewer_title', true);
        
        $atts = shortcode_atts([
            'height' => '600px',
            'width' => '100%',
            'quiz_id' => '',
            'show_title' => $global_show_title ? 'true' : 'false'
        ], $atts);

        // Get the first quiz or specific quiz
        if (!empty($atts['quiz_id'])) {
            $post = get_post($atts['quiz_id']);
            if (!$post || $post->post_type !== 'typeform_quiz') {
                return '<p>' . __('Quiz not found', 'typeform-quizzes') . '</p>';
            }
            $typeform_url = get_post_meta($post->ID, '_typeform_url', true);
            $title = $post->post_title;
        } else {
            // Get the first quiz from the default order
            $quizzes = \MTI\TypeformQuizzes\Frontend\Repository\QuizRepository::get_quizzes(1, 'menu_order');
            if (empty($quizzes)) {
                return '<p>' . __('No quizzes found. Please add some Typeform Quizzes first.', 'typeform-quizzes') . '</p>';
            }
            $quiz = $quizzes[0];
            $typeform_url = $quiz['url'];
            $title = $quiz['title'];
        }

        if (empty($typeform_url)) {
            return '<p>' . __('No Typeform URL found', 'typeform-quizzes') . '</p>';
        }

        // Convert to embed URL
        $embed_url = self::convert_to_embed_url($typeform_url);
        
        if (!$embed_url) {
            return '<p>' . __('Invalid Typeform URL', 'typeform-quizzes') . '</p>';
        }

        // Enqueue frontend assets for the viewer
        \MTI\TypeformQuizzes\Frontend\Assets::enqueue_slider_assets($atts);

        // Render viewer
        ob_start();
        ?>
        <div class="typeform-quiz-viewer-container" style="width: <?php echo esc_attr($atts['width']); ?>; margin: 0 auto;">
            <?php if ($atts['show_title'] === 'true' && !empty($title)): ?>
                <div class="quiz-viewer-header">
                    <h2 class="quiz-viewer-title"><?php echo esc_html($title); ?></h2>
                </div>
            <?php endif; ?>
            <div class="quiz-viewer-content">
                <div style="position: absolute; top: 0; left: 0; right: 0; height: 4px;"></div>
                <iframe id="quiz-iframe" 
                        src="<?php echo esc_url($embed_url); ?>" 
                        width="100%" 
                        height="<?php echo esc_attr($atts['height']); ?>" 
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
        <?php
        return ob_get_clean();
    }

    /**
     * Convert Typeform URL to embed URL
     */
    private static function convert_to_embed_url($url)
    {
        // Handle different Typeform URL formats
        if (strpos($url, 'typeform.com/to/') !== false) {
            // Convert https://form.typeform.com/to/abc123 to https://form.typeform.com/to/abc123?embed=popup
            return $url . '?embed=popup';
        } elseif (strpos($url, 'typeform.com/forms/') !== false) {
            // Convert https://form.typeform.com/forms/abc123 to https://form.typeform.com/forms/abc123?embed=popup
            return $url . '?embed=popup';
        } elseif (preg_match('/^https?:\/\/[a-zA-Z0-9-]+\.typeform\.com\/[a-zA-Z0-9_-]+$/', $url)) {
            // Convert custom subdomain URLs like https://okeefemediagroup.typeform.com/Jamesokeefe to embed format
            return $url . '?embed=popup';
        }
        
        return false;
    }
}
