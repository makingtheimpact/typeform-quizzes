<?php
/**
 * Custom Post Type Registration
 * 
 * @package Typeform_Quizzes
 * @version 1.1.0
 * @author Making The Impact LLC
 */

namespace MTI\TypeformQuizzes\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

/**
 * Post Type Class
 * 
 * Handles custom post type registration and management.
 */
final class PostType
{
    /**
     * Initialize post type functionality
     */
    public static function init()
    {
        add_action('init', [__CLASS__, 'register_post_type']);
    }

    /**
     * Register custom post type for Typeform Quizzes
     */
    public static function register_post_type()
    {
        $labels = [
            'name' => __('Typeform Quizzes', 'typeform-quizzes'),
            'singular_name' => __('Typeform Quiz', 'typeform-quizzes'),
            'menu_name' => __('Typeform Quizzes', 'typeform-quizzes'),
            'add_new' => __('Add New Quiz', 'typeform-quizzes'),
            'add_new_item' => __('Add New Quiz', 'typeform-quizzes'),
            'edit_item' => __('Edit Quiz', 'typeform-quizzes'),
            'new_item' => __('New Quiz', 'typeform-quizzes'),
            'view_item' => __('View Quiz', 'typeform-quizzes'),
            'search_items' => __('Search Quizzes', 'typeform-quizzes'),
            'not_found' => __('No quizzes found', 'typeform-quizzes'),
            'not_found_in_trash' => __('No quizzes found in trash', 'typeform-quizzes'),
        ];

        $args = [
            'labels' => $labels,
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => 'typeform-quizzes', // Use our custom menu
            'show_in_nav_menus' => false,
            'show_in_admin_bar' => false,
            'query_var' => false,
            'rewrite' => false,
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => null, // Remove position since we're using custom menu
            'menu_icon' => null, // Remove icon since we're using custom menu
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'page-attributes'],
            'show_in_rest' => true,
        ];

        register_post_type('typeform_quiz', $args);
    }

}
