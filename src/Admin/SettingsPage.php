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
        add_action('admin_init', [__CLASS__, 'register_sections_and_fields']);
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

    /**
     * Register settings sections and fields.
     */
    public static function register_sections_and_fields(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Basic Configuration Section
        add_settings_section(
            'tfq_basic_config',
            null,
            [__CLASS__, 'render_section_header'],
            'typeform-quizzes-tools'
        );

        add_settings_field(
            'max',
            __('Maximum Quizzes', 'typeform-quizzes'),
            [__CLASS__, 'field_max'],
            'typeform-quizzes-tools',
            'tfq_basic_config'
        );

        add_settings_field(
            'order',
            __('Quiz Order', 'typeform-quizzes'),
            [__CLASS__, 'field_order'],
            'typeform-quizzes-tools',
            'tfq_basic_config'
        );


        // Layout & Grid Settings Section
        add_settings_section(
            'tfq_layout_grid',
            null,
            [__CLASS__, 'render_section_header'],
            'typeform-quizzes-tools'
        );

        add_settings_field(
            'max_width',
            __('Maximum Width (px)', 'typeform-quizzes'),
            [__CLASS__, 'field_max_width'],
            'typeform-quizzes-tools',
            'tfq_layout_grid'
        );

        add_settings_field(
            'cols_desktop',
            __('Desktop Columns', 'typeform-quizzes'),
            [__CLASS__, 'field_cols_desktop'],
            'typeform-quizzes-tools',
            'tfq_layout_grid'
        );

        add_settings_field(
            'cols_tablet',
            __('Tablet Columns', 'typeform-quizzes'),
            [__CLASS__, 'field_cols_tablet'],
            'typeform-quizzes-tools',
            'tfq_layout_grid'
        );

        add_settings_field(
            'cols_mobile',
            __('Mobile Columns', 'typeform-quizzes'),
            [__CLASS__, 'field_cols_mobile'],
            'typeform-quizzes-tools',
            'tfq_layout_grid'
        );

        add_settings_field(
            'gap',
            __('Gap Between Quizzes (px)', 'typeform-quizzes'),
            [__CLASS__, 'field_gap'],
            'typeform-quizzes-tools',
            'tfq_layout_grid'
        );

        add_settings_field(
            'thumb_height',
            __('Thumbnail Height (px)', 'typeform-quizzes'),
            [__CLASS__, 'field_thumb_height'],
            'typeform-quizzes-tools',
            'tfq_layout_grid'
        );

        add_settings_field(
            'border_radius',
            __('Border Radius (px)', 'typeform-quizzes'),
            [__CLASS__, 'field_border_radius'],
            'typeform-quizzes-tools',
            'tfq_layout_grid'
        );

        // Colors & Styling Section
        add_settings_section(
            'tfq_colors_styling',
            null,
            [__CLASS__, 'render_section_header'],
            'typeform-quizzes-tools'
        );

        add_settings_field(
            'title_color',
            __('Title Color', 'typeform-quizzes'),
            [__CLASS__, 'field_title_color'],
            'typeform-quizzes-tools',
            'tfq_colors_styling'
        );

        add_settings_field(
            'title_hover_color',
            __('Title Hover Color', 'typeform-quizzes'),
            [__CLASS__, 'field_title_hover_color'],
            'typeform-quizzes-tools',
            'tfq_colors_styling'
        );

        add_settings_field(
            'active_slide_border_color',
            __('Active Slide Border Color', 'typeform-quizzes'),
            [__CLASS__, 'field_active_slide_border_color'],
            'typeform-quizzes-tools',
            'tfq_colors_styling'
        );

        add_settings_field(
            'darken_inactive_slides',
            __('Darken Inactive Slides', 'typeform-quizzes'),
            [__CLASS__, 'field_darken_inactive_slides'],
            'typeform-quizzes-tools',
            'tfq_colors_styling'
        );

        // Navigation Controls Section
        add_settings_section(
            'tfq_navigation_controls',
            null,
            [__CLASS__, 'render_section_header'],
            'typeform-quizzes-tools'
        );

        add_settings_field(
            'controls_spacing',
            __('Controls Spacing (px)', 'typeform-quizzes'),
            [__CLASS__, 'field_controls_spacing'],
            'typeform-quizzes-tools',
            'tfq_navigation_controls'
        );

        add_settings_field(
            'controls_spacing_tablet',
            __('Controls Spacing Tablet (px)', 'typeform-quizzes'),
            [__CLASS__, 'field_controls_spacing_tablet'],
            'typeform-quizzes-tools',
            'tfq_navigation_controls'
        );

        add_settings_field(
            'controls_bottom_spacing',
            __('Controls Bottom Spacing (px)', 'typeform-quizzes'),
            [__CLASS__, 'field_controls_bottom_spacing'],
            'typeform-quizzes-tools',
            'tfq_navigation_controls'
        );

        add_settings_field(
            'arrow_border_radius',
            __('Arrow Border Radius (px)', 'typeform-quizzes'),
            [__CLASS__, 'field_arrow_border_radius'],
            'typeform-quizzes-tools',
            'tfq_navigation_controls'
        );

        add_settings_field(
            'arrow_padding',
            __('Arrow Padding (px)', 'typeform-quizzes'),
            [__CLASS__, 'field_arrow_padding'],
            'typeform-quizzes-tools',
            'tfq_navigation_controls'
        );

        add_settings_field(
            'arrow_width',
            __('Arrow Width (px)', 'typeform-quizzes'),
            [__CLASS__, 'field_arrow_width'],
            'typeform-quizzes-tools',
            'tfq_navigation_controls'
        );

        add_settings_field(
            'arrow_height',
            __('Arrow Height (px)', 'typeform-quizzes'),
            [__CLASS__, 'field_arrow_height'],
            'typeform-quizzes-tools',
            'tfq_navigation_controls'
        );

        add_settings_field(
            'arrow_bg_color',
            __('Arrow Background Color', 'typeform-quizzes'),
            [__CLASS__, 'field_arrow_bg_color'],
            'typeform-quizzes-tools',
            'tfq_navigation_controls'
        );

        add_settings_field(
            'arrow_hover_bg_color',
            __('Arrow Hover Background Color', 'typeform-quizzes'),
            [__CLASS__, 'field_arrow_hover_bg_color'],
            'typeform-quizzes-tools',
            'tfq_navigation_controls'
        );

        add_settings_field(
            'arrow_icon_color',
            __('Arrow Icon Color', 'typeform-quizzes'),
            [__CLASS__, 'field_arrow_icon_color'],
            'typeform-quizzes-tools',
            'tfq_navigation_controls'
        );

        add_settings_field(
            'arrow_icon_hover_color',
            __('Arrow Icon Hover Color', 'typeform-quizzes'),
            [__CLASS__, 'field_arrow_icon_hover_color'],
            'typeform-quizzes-tools',
            'tfq_navigation_controls'
        );

        add_settings_field(
            'arrow_icon_size',
            __('Arrow Icon Size (px)', 'typeform-quizzes'),
            [__CLASS__, 'field_arrow_icon_size'],
            'typeform-quizzes-tools',
            'tfq_navigation_controls'
        );

        // Pagination Section
        add_settings_section(
            'tfq_pagination',
            null,
            [__CLASS__, 'render_section_header'],
            'typeform-quizzes-tools'
        );

        add_settings_field(
            'pagination_dot_color',
            __('Pagination Dot Color', 'typeform-quizzes'),
            [__CLASS__, 'field_pagination_dot_color'],
            'typeform-quizzes-tools',
            'tfq_pagination'
        );

        add_settings_field(
            'pagination_active_dot_color',
            __('Pagination Active Dot Color', 'typeform-quizzes'),
            [__CLASS__, 'field_pagination_active_dot_color'],
            'typeform-quizzes-tools',
            'tfq_pagination'
        );

        add_settings_field(
            'pagination_dot_gap',
            __('Pagination Dot Gap (px)', 'typeform-quizzes'),
            [__CLASS__, 'field_pagination_dot_gap'],
            'typeform-quizzes-tools',
            'tfq_pagination'
        );

        add_settings_field(
            'pagination_dot_size',
            __('Pagination Dot Size (px)', 'typeform-quizzes'),
            [__CLASS__, 'field_pagination_dot_size'],
            'typeform-quizzes-tools',
            'tfq_pagination'
        );
    }

    // Field Methods
    public static function field_max(): void {
        $opts = \MTI\TypeformQuizzes\Services\Options::all();
        $val = isset($opts['max']) ? $opts['max'] : 20;
        ?>
        <input type="number" id="max" name="typeform_quizzes_defaults[max]" 
               value="<?php echo esc_attr($val); ?>" 
               class="small-text" min="1" max="50">
        <p class="description"><?php esc_html_e('Maximum number of quizzes to display (default: 20)', 'typeform-quizzes'); ?></p>
        <?php
    }

    public static function field_order(): void {
        $opts = \MTI\TypeformQuizzes\Services\Options::all();
        $val = isset($opts['order']) ? $opts['order'] : 'menu_order';
        ?>
        <select id="order" name="typeform_quizzes_defaults[order]">
            <option value="menu_order" <?php selected($val, 'menu_order'); ?>><?php esc_html_e('Custom order (by quiz order field)', 'typeform-quizzes'); ?></option>
            <option value="date" <?php selected($val, 'date'); ?>><?php esc_html_e('Published date (newest first)', 'typeform-quizzes'); ?></option>
            <option value="title" <?php selected($val, 'title'); ?>><?php esc_html_e('Title (A-Z)', 'typeform-quizzes'); ?></option>
            <option value="rand" <?php selected($val, 'rand'); ?>><?php esc_html_e('Random', 'typeform-quizzes'); ?></option>
        </select>
        <p class="description"><?php esc_html_e('How quizzes should be ordered in the slider', 'typeform-quizzes'); ?></p>
        <?php
    }


    public static function field_max_width(): void {
        $opts = \MTI\TypeformQuizzes\Services\Options::all();
        $val = isset($opts['max_width']) ? $opts['max_width'] : 1450;
        ?>
        <input type="number" id="max_width" name="typeform_quizzes_defaults[max_width]" 
               value="<?php echo esc_attr($val); ?>" 
               class="small-text" min="200" max="2000">
        <p class="description"><?php esc_html_e('Maximum width of the slider container (default: 1450px)', 'typeform-quizzes'); ?></p>
        <?php
    }

    public static function field_cols_desktop(): void {
        $opts = \MTI\TypeformQuizzes\Services\Options::all();
        $val = isset($opts['cols_desktop']) ? $opts['cols_desktop'] : 6;
        ?>
        <input type="number" id="cols_desktop" name="typeform_quizzes_defaults[cols_desktop]" 
               value="<?php echo esc_attr($val); ?>" 
               class="small-text" min="1" max="12">
        <p class="description"><?php esc_html_e('Number of quizzes visible per row on desktop screens (default: 6)', 'typeform-quizzes'); ?></p>
        <?php
    }

    public static function field_cols_tablet(): void {
        $opts = \MTI\TypeformQuizzes\Services\Options::all();
        $val = isset($opts['cols_tablet']) ? $opts['cols_tablet'] : 3;
        ?>
        <input type="number" id="cols_tablet" name="typeform_quizzes_defaults[cols_tablet]" 
               value="<?php echo esc_attr($val); ?>" 
               class="small-text" min="1" max="8">
        <p class="description"><?php esc_html_e('Number of quizzes visible per row on tablet screens (default: 3)', 'typeform-quizzes'); ?></p>
        <?php
    }

    public static function field_cols_mobile(): void {
        $opts = \MTI\TypeformQuizzes\Services\Options::all();
        $val = isset($opts['cols_mobile']) ? $opts['cols_mobile'] : 2;
        ?>
        <input type="number" id="cols_mobile" name="typeform_quizzes_defaults[cols_mobile]" 
               value="<?php echo esc_attr($val); ?>" 
               class="small-text" min="1" max="4">
        <p class="description"><?php esc_html_e('Number of quizzes visible per row on mobile screens (default: 2)', 'typeform-quizzes'); ?></p>
        <?php
    }

    public static function field_gap(): void {
        $opts = \MTI\TypeformQuizzes\Services\Options::all();
        $val = isset($opts['gap']) ? $opts['gap'] : 20;
        ?>
        <input type="number" id="gap" name="typeform_quizzes_defaults[gap]" 
               value="<?php echo esc_attr($val); ?>" 
               class="small-text" min="0" max="100">
        <p class="description"><?php esc_html_e('Gap between quiz items in pixels (default: 20px)', 'typeform-quizzes'); ?></p>
        <?php
    }

    public static function field_thumb_height(): void {
        $opts = \MTI\TypeformQuizzes\Services\Options::all();
        $val = isset($opts['thumb_height']) ? $opts['thumb_height'] : 200;
        ?>
        <input type="number" id="thumb_height" name="typeform_quizzes_defaults[thumb_height]" 
               value="<?php echo esc_attr($val); ?>" 
               class="small-text" min="100" max="500">
        <p class="description"><?php esc_html_e('Height of quiz thumbnails in pixels (default: 200px)', 'typeform-quizzes'); ?></p>
        <?php
    }

    public static function field_border_radius(): void {
        $opts = \MTI\TypeformQuizzes\Services\Options::all();
        $val = isset($opts['border_radius']) ? $opts['border_radius'] : 16;
        ?>
        <input type="number" id="border_radius" name="typeform_quizzes_defaults[border_radius]" 
               value="<?php echo esc_attr($val); ?>" 
               class="small-text" min="0" max="50">
        <p class="description"><?php esc_html_e('Border radius for quiz items in pixels (default: 16px)', 'typeform-quizzes'); ?></p>
        <?php
    }

    public static function field_title_color(): void {
        $opts = \MTI\TypeformQuizzes\Services\Options::all();
        $val = isset($opts['title_color']) ? $opts['title_color'] : '#000000';
        ?>
        <input type="text" id="title_color" name="typeform_quizzes_defaults[title_color]" 
               value="<?php echo esc_attr($val); ?>" 
               class="color-picker" placeholder="#000000">
        <p class="description"><?php esc_html_e('Color for quiz titles (default: #000000)', 'typeform-quizzes'); ?></p>
        <?php
    }

    public static function field_title_hover_color(): void {
        $opts = \MTI\TypeformQuizzes\Services\Options::all();
        $val = isset($opts['title_hover_color']) ? $opts['title_hover_color'] : '#777777';
        ?>
        <input type="text" id="title_hover_color" name="typeform_quizzes_defaults[title_hover_color]" 
               value="<?php echo esc_attr($val); ?>" 
               class="color-picker" placeholder="#777777">
        <p class="description"><?php esc_html_e('Color for quiz titles on hover (default: #777777)', 'typeform-quizzes'); ?></p>
        <?php
    }

    public static function field_active_slide_border_color(): void {
        $opts = \MTI\TypeformQuizzes\Services\Options::all();
        $val = isset($opts['active_slide_border_color']) ? $opts['active_slide_border_color'] : '#0073aa';
        ?>
        <input type="text" id="active_slide_border_color" name="typeform_quizzes_defaults[active_slide_border_color]" 
               value="<?php echo esc_attr($val); ?>" 
               class="color-picker" placeholder="#0073aa">
        <p class="description"><?php esc_html_e('Border color for the active slide (default: #0073aa)', 'typeform-quizzes'); ?></p>
        <?php
    }

    public static function field_darken_inactive_slides(): void {
        $opts = \MTI\TypeformQuizzes\Services\Options::all();
        $val = isset($opts['darken_inactive_slides']) ? $opts['darken_inactive_slides'] : true;
        ?>
        <input type="hidden" name="typeform_quizzes_defaults[darken_inactive_slides]" value="0">
        <input type="checkbox" id="darken_inactive_slides" name="typeform_quizzes_defaults[darken_inactive_slides]" 
               value="1" <?php checked($val, 1); ?>>
        <p class="description"><?php esc_html_e('Darken inactive slides to highlight the active one (default: enabled)', 'typeform-quizzes'); ?></p>
        <?php
    }

    public static function field_controls_spacing(): void {
        $opts = \MTI\TypeformQuizzes\Services\Options::all();
        $val = isset($opts['controls_spacing']) ? $opts['controls_spacing'] : 56;
        ?>
        <input type="number" id="controls_spacing" name="typeform_quizzes_defaults[controls_spacing]" 
               value="<?php echo esc_attr($val); ?>" 
               class="small-text" min="0" max="200">
        <p class="description"><?php esc_html_e('Spacing between navigation controls in pixels (default: 56px)', 'typeform-quizzes'); ?></p>
        <?php
    }

    public static function field_controls_spacing_tablet(): void {
        $opts = \MTI\TypeformQuizzes\Services\Options::all();
        $val = isset($opts['controls_spacing_tablet']) ? $opts['controls_spacing_tablet'] : 56;
        ?>
        <input type="number" id="controls_spacing_tablet" name="typeform_quizzes_defaults[controls_spacing_tablet]" 
               value="<?php echo esc_attr($val); ?>" 
               class="small-text" min="0" max="200">
        <p class="description"><?php esc_html_e('Spacing between navigation controls on tablet in pixels (default: 56px)', 'typeform-quizzes'); ?></p>
        <?php
    }

    public static function field_controls_bottom_spacing(): void {
        $opts = \MTI\TypeformQuizzes\Services\Options::all();
        $val = isset($opts['controls_bottom_spacing']) ? $opts['controls_bottom_spacing'] : 20;
        ?>
        <input type="number" id="controls_bottom_spacing" name="typeform_quizzes_defaults[controls_bottom_spacing]" 
               value="<?php echo esc_attr($val); ?>" 
               class="small-text" min="0" max="100">
        <p class="description"><?php esc_html_e('Spacing between controls and content in pixels (default: 20px)', 'typeform-quizzes'); ?></p>
        <?php
    }

    public static function field_arrow_border_radius(): void {
        $opts = \MTI\TypeformQuizzes\Services\Options::all();
        $val = isset($opts['arrow_border_radius']) ? $opts['arrow_border_radius'] : 0;
        ?>
        <input type="number" id="arrow_border_radius" name="typeform_quizzes_defaults[arrow_border_radius]" 
               value="<?php echo esc_attr($val); ?>" 
               class="small-text" min="0" max="50">
        <p class="description"><?php esc_html_e('Border radius for arrow buttons in pixels (default: 0px)', 'typeform-quizzes'); ?></p>
        <?php
    }

    public static function field_arrow_padding(): void {
        $opts = \MTI\TypeformQuizzes\Services\Options::all();
        $val = isset($opts['arrow_padding']) ? $opts['arrow_padding'] : 3;
        ?>
        <input type="number" id="arrow_padding" name="typeform_quizzes_defaults[arrow_padding]" 
               value="<?php echo esc_attr($val); ?>" 
               class="small-text" min="0" max="20">
        <p class="description"><?php esc_html_e('Padding inside arrow buttons in pixels (default: 3px)', 'typeform-quizzes'); ?></p>
        <?php
    }

    public static function field_arrow_width(): void {
        $opts = \MTI\TypeformQuizzes\Services\Options::all();
        $val = isset($opts['arrow_width']) ? $opts['arrow_width'] : 35;
        ?>
        <input type="number" id="arrow_width" name="typeform_quizzes_defaults[arrow_width]" 
               value="<?php echo esc_attr($val); ?>" 
               class="small-text" min="20" max="100">
        <p class="description"><?php esc_html_e('Width of arrow buttons in pixels (default: 35px)', 'typeform-quizzes'); ?></p>
        <?php
    }

    public static function field_arrow_height(): void {
        $opts = \MTI\TypeformQuizzes\Services\Options::all();
        $val = isset($opts['arrow_height']) ? $opts['arrow_height'] : 35;
        ?>
        <input type="number" id="arrow_height" name="typeform_quizzes_defaults[arrow_height]" 
               value="<?php echo esc_attr($val); ?>" 
               class="small-text" min="20" max="100">
        <p class="description"><?php esc_html_e('Height of arrow buttons in pixels (default: 35px)', 'typeform-quizzes'); ?></p>
        <?php
    }

    public static function field_arrow_bg_color(): void {
        $opts = \MTI\TypeformQuizzes\Services\Options::all();
        $val = isset($opts['arrow_bg_color']) ? $opts['arrow_bg_color'] : '#111111';
        ?>
        <input type="text" id="arrow_bg_color" name="typeform_quizzes_defaults[arrow_bg_color]" 
               value="<?php echo esc_attr($val); ?>" 
               class="color-picker" placeholder="#111111">
        <p class="description"><?php esc_html_e('Background color for arrow buttons (default: #111111)', 'typeform-quizzes'); ?></p>
        <?php
    }

    public static function field_arrow_hover_bg_color(): void {
        $opts = \MTI\TypeformQuizzes\Services\Options::all();
        $val = isset($opts['arrow_hover_bg_color']) ? $opts['arrow_hover_bg_color'] : '#000000';
        ?>
        <input type="text" id="arrow_hover_bg_color" name="typeform_quizzes_defaults[arrow_hover_bg_color]" 
               value="<?php echo esc_attr($val); ?>" 
               class="color-picker" placeholder="#000000">
        <p class="description"><?php esc_html_e('Background color for arrow buttons on hover (default: #000000)', 'typeform-quizzes'); ?></p>
        <?php
    }

    public static function field_arrow_icon_color(): void {
        $opts = \MTI\TypeformQuizzes\Services\Options::all();
        $val = isset($opts['arrow_icon_color']) ? $opts['arrow_icon_color'] : '#ffffff';
        ?>
        <input type="text" id="arrow_icon_color" name="typeform_quizzes_defaults[arrow_icon_color]" 
               value="<?php echo esc_attr($val); ?>" 
               class="color-picker" placeholder="#ffffff">
        <p class="description"><?php esc_html_e('Color for arrow icons (default: #ffffff)', 'typeform-quizzes'); ?></p>
        <?php
    }

    public static function field_arrow_icon_hover_color(): void {
        $opts = \MTI\TypeformQuizzes\Services\Options::all();
        $val = isset($opts['arrow_icon_hover_color']) ? $opts['arrow_icon_hover_color'] : '#ffffff';
        ?>
        <input type="text" id="arrow_icon_hover_color" name="typeform_quizzes_defaults[arrow_icon_hover_color]" 
               value="<?php echo esc_attr($val); ?>" 
               class="color-picker" placeholder="#ffffff">
        <p class="description"><?php esc_html_e('Color for arrow icons on hover (default: #ffffff)', 'typeform-quizzes'); ?></p>
        <?php
    }

    public static function field_arrow_icon_size(): void {
        $opts = \MTI\TypeformQuizzes\Services\Options::all();
        $val = isset($opts['arrow_icon_size']) ? $opts['arrow_icon_size'] : 28;
        ?>
        <input type="number" id="arrow_icon_size" name="typeform_quizzes_defaults[arrow_icon_size]" 
               value="<?php echo esc_attr($val); ?>" 
               class="small-text" min="10" max="50">
        <p class="description"><?php esc_html_e('Size of arrow icons in pixels (default: 28px)', 'typeform-quizzes'); ?></p>
        <?php
    }

    public static function field_pagination_dot_color(): void {
        $opts = \MTI\TypeformQuizzes\Services\Options::all();
        $val = isset($opts['pagination_dot_color']) ? $opts['pagination_dot_color'] : '#cfcfcf';
        ?>
        <input type="text" id="pagination_dot_color" name="typeform_quizzes_defaults[pagination_dot_color]" 
               value="<?php echo esc_attr($val); ?>" 
               class="color-picker" placeholder="#cfcfcf">
        <p class="description"><?php esc_html_e('Color for pagination dots (default: #cfcfcf)', 'typeform-quizzes'); ?></p>
        <?php
    }

    public static function field_pagination_active_dot_color(): void {
        $opts = \MTI\TypeformQuizzes\Services\Options::all();
        $val = isset($opts['pagination_active_dot_color']) ? $opts['pagination_active_dot_color'] : '#111111';
        ?>
        <input type="text" id="pagination_active_dot_color" name="typeform_quizzes_defaults[pagination_active_dot_color]" 
               value="<?php echo esc_attr($val); ?>" 
               class="color-picker" placeholder="#111111">
        <p class="description"><?php esc_html_e('Color for active pagination dot (default: #111111)', 'typeform-quizzes'); ?></p>
        <?php
    }

    public static function field_pagination_dot_gap(): void {
        $opts = \MTI\TypeformQuizzes\Services\Options::all();
        $val = isset($opts['pagination_dot_gap']) ? $opts['pagination_dot_gap'] : 10;
        ?>
        <input type="number" id="pagination_dot_gap" name="typeform_quizzes_defaults[pagination_dot_gap]" 
               value="<?php echo esc_attr($val); ?>" 
               class="small-text" min="0" max="50">
        <p class="description"><?php esc_html_e('Gap between pagination dots in pixels (default: 10px)', 'typeform-quizzes'); ?></p>
        <?php
    }

    public static function field_pagination_dot_size(): void {
        $opts = \MTI\TypeformQuizzes\Services\Options::all();
        $val = isset($opts['pagination_dot_size']) ? $opts['pagination_dot_size'] : 8;
        ?>
        <input type="number" id="pagination_dot_size" name="typeform_quizzes_defaults[pagination_dot_size]" 
               value="<?php echo esc_attr($val); ?>" 
               class="small-text" min="4" max="20">
        <p class="description"><?php esc_html_e('Size of pagination dots in pixels (default: 8px)', 'typeform-quizzes'); ?></p>
        <?php
    }

    /**
     * Render section header with enhanced styling
     * 
     * @param array $args Section arguments
     * @return void
     */
    public static function render_section_header($args): void
    {
        $section_id = $args['id'];
        $section_title = $args['title'];
        
        // Map section IDs to their display names and icons
        $section_info = [
            'tfq_basic_config' => [
                'title' => __('Basic Configuration', 'typeform-quizzes'),
                'icon' => '⚙️'
            ],
            'tfq_layout_grid' => [
                'title' => __('Layout & Grid Settings', 'typeform-quizzes'),
                'icon' => '📐'
            ],
            'tfq_colors_styling' => [
                'title' => __('Colors & Styling', 'typeform-quizzes'),
                'icon' => '🎨'
            ],
            'tfq_navigation_controls' => [
                'title' => __('Navigation Controls', 'typeform-quizzes'),
                'icon' => '🎮'
            ],
            'tfq_pagination' => [
                'title' => __('Pagination', 'typeform-quizzes'),
                'icon' => '🔘'
            ]
        ];
        
        $info = $section_info[$section_id] ?? [
            'title' => $section_title,
            'icon' => '⚙️'
        ];
        
        echo '<h3 data-section="' . esc_attr($section_id) . '">';
        echo esc_html($info['title']);
        echo '</h3>';
    }
}
