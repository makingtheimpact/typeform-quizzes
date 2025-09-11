<?php
/**
 * Quiz Retriever for Typeform Quizzes Shortcode
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
 * Quiz Retriever Class
 * 
 * Handles retrieval of quizzes from the database.
 */
class QuizRetriever
{
    /**
     * Get quizzes from database
     * 
     * @param int $max_quizzes Maximum number of quizzes to retrieve
     * @param string $order Order of quizzes
     * @return array Array of quiz posts
     */
    public static function get_quizzes($max_quizzes, $order) {
        // Check if post type exists
        if (!post_type_exists('typeform_quiz')) {
            return [];
        }

        // Validate order parameter
        $valid_orders = ['menu_order', 'date', 'title', 'rand'];
        $order = in_array($order, $valid_orders) ? $order : 'menu_order';

        // Build query arguments - get more posts than needed to account for filtering
        $args = [
            'post_type' => 'typeform_quiz',
            'post_status' => 'publish',
            'numberposts' => -1, // Get all posts to ensure we don't miss any
            'orderby' => $order,
            'order' => 'ASC'
        ];

        // Handle random order
        if ($order === 'rand') {
            $args['orderby'] = 'rand';
            unset($args['order']);
        }

        
        $quizzes_query = new \WP_Query($args);
        $quizzes = [];

        if ($quizzes_query->have_posts()) {
            while ($quizzes_query->have_posts() && count($quizzes) < $max_quizzes) {
                $quizzes_query->the_post();
                $quiz_id = get_the_ID();
                $typeform_url = get_post_meta($quiz_id, '_typeform_url', true);
                
                // Check alternative meta field names for backward compatibility
                if (empty($typeform_url)) {
                    $alt_url = get_post_meta($quiz_id, 'typeform_url', true);
                    if (!empty($alt_url)) {
                        $typeform_url = $alt_url;
                    }
                }
                
                // Validate URL before adding to results
                if (!empty($typeform_url) && self::is_valid_typeform_url($typeform_url)) {
                    $quizzes[] = [
                        'id' => $quiz_id,
                        'title' => get_the_title(),
                        'excerpt' => get_the_excerpt(),
                        'url' => $typeform_url,
                        'thumbnail' => get_the_post_thumbnail_url($quiz_id, 'medium'),
                        'permalink' => get_permalink()
                    ];
                }
            }
            wp_reset_postdata();
        }

        // If we still don't have enough quizzes, try a fallback query
        if (count($quizzes) < $max_quizzes) {
            $fallback_args = [
                'post_type' => 'typeform_quiz',
                'post_status' => 'publish',
                'posts_per_page' => $max_quizzes * 2,
            ];
            
            $fallback_query = new \WP_Query($fallback_args);
            
            if ($fallback_query->have_posts()) {
                while ($fallback_query->have_posts() && count($quizzes) < $max_quizzes) {
                    $fallback_query->the_post();
                    $quiz_id = get_the_ID();
                    $title = get_the_title();
                    
                    // Check for various possible meta keys for backward compatibility
                    $typeform_url = get_post_meta($quiz_id, '_typeform_url', true);
                    if (empty($typeform_url)) {
                        $typeform_url = get_post_meta($quiz_id, 'typeform_url', true);
                    }
                    if (empty($typeform_url)) {
                        $typeform_url = get_post_meta($quiz_id, 'typeform_quiz_url', true);
                    }
                    
                    // Validate URL before adding to results
                    if (!empty($typeform_url) && self::is_valid_typeform_url($typeform_url)) {
                        $quizzes[] = [
                            'id' => $quiz_id,
                            'title' => $title,
                            'excerpt' => get_the_excerpt(),
                            'url' => $typeform_url,
                            'thumbnail' => get_the_post_thumbnail_url($quiz_id, 'medium'),
                            'permalink' => get_permalink()
                        ];
                    }
                }
                wp_reset_postdata();
            }
        }


        return $quizzes;
    }

    /**
     * Check if a URL is a valid Typeform URL
     * 
     * @param string $url URL to validate
     * @return bool True if valid, false otherwise
     */
    public static function is_valid_typeform_url($url) {
        if (empty($url)) {
            return false;
        }
        
        // Check if it's a valid URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        // Check if it's a Typeform URL
        $parsed = parse_url($url);
        return isset($parsed['host']) && (
            strpos($parsed['host'], 'typeform.com') !== false ||
            strpos($parsed['host'], 't.typeform.com') !== false
        );
    }
}
