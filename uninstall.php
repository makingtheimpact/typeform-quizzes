<?php
/**
 * Uninstall script for Typeform Quizzes plugin
 * 
 * This file is executed when the plugin is deleted through the WordPress admin.
 * It removes only the typeform_quizzes_defaults option as specified.
 * 
 * @package Typeform_Quizzes
 * @version 1.1.0
 * @author Making The Impact LLC
 * @since 1.1.0
 */

// Prevent direct access
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit('Direct access forbidden.');
}

// Delete the typeform_quizzes_defaults option
delete_option('typeform_quizzes_defaults');
