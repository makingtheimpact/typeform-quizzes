<?php
namespace MTI\TypeformQuizzes\Admin;

/**
 * Settings registration isolated from the legacy monolithic class.
 *
 * IMPORTANT:
 * - Keeps the SAME settings group and option name to avoid losing data:
 *     Group:  typeform_quizzes_defaults_options
 *     Option: typeform_quizzes_defaults (array)
 * - Reuses the legacy sanitize callback to avoid behavior changes:
 *     [ 'Typeform_Quizzes', 'sanitize_defaults' ]
 *
 * Rendering of the admin page can remain in Typeform_Quizzes::render_page()
 * for now. We only move registration in this step.
 */
final class SettingsPage
{
    /**
     * Wire WordPress hooks.
     * Safe to call multiple times; hooks will only be added once by WP core.
     */
    public static function init(): void
    {
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }

    /**
     * Register settings without changing group/option names.
     */
    public static function register_settings(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Avoid double-registration if legacy code still runs.
        // Check if the setting is already registered.
        if (function_exists('get_registered_settings')) {
            $registered = get_registered_settings();
            if (isset($registered['typeform_quizzes_defaults'])) {
                return;
            }
        }

        register_setting(
            'typeform_quizzes_defaults_options',
            'typeform_quizzes_defaults',
            [
                'type'              => 'array',
                // Reuse the legacy sanitizer to keep exact behavior
                'sanitize_callback' => ['Typeform_Quizzes', 'sanitize_defaults'],
                'default'           => [],
                'show_in_rest'      => false,
            ]
        );
    }
}
