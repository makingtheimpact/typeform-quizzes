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
        }
        
        return false;
    }
}
