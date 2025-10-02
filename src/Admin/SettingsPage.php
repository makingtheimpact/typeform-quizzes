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
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_action('admin_init', [__CLASS__, 'register_sections_and_fields']);
        add_action('init', [__CLASS__, 'handle_export_import_early']);
        add_action('admin_init', [__CLASS__, 'migrate_quiz_orders']);
    }

    /**
     * Add admin menu
     */
    public static function add_admin_menu(): void
    {
        // Main menu page (this will be the quiz list page)
        add_menu_page(
            __('Typeform Quizzes', 'typeform-quizzes'),
            __('Typeform Quizzes', 'typeform-quizzes'),
            'edit_posts',
            'typeform-quizzes',
            [__CLASS__, 'render_quiz_list_page'],
            'dashicons-feedback',
            25
        );

        // Add New Quiz submenu (this will redirect to the post creation)
        add_submenu_page(
            'typeform-quizzes',
            __('Add New Quiz', 'typeform-quizzes'),
            __('Add New Quiz', 'typeform-quizzes'),
            'edit_posts',
            'post-new.php?post_type=typeform_quiz'
        );

        // Settings submenu
        add_submenu_page(
            'typeform-quizzes',
            __('Settings', 'typeform-quizzes'),
            __('Settings', 'typeform-quizzes'),
            'manage_options',
            'typeform-quizzes-settings',
            [__CLASS__, 'render_settings_page']
        );
    }

    /**
     * Render quiz list page (main admin page)
     */
    public static function render_quiz_list_page(): void
    {
        // Redirect to the quiz list page
        wp_redirect(admin_url('edit.php?post_type=typeform_quiz'));
        exit;
    }

    /**
     * Render main admin page (legacy method for compatibility)
     */
    public static function render_page(): void
    {
        ?>
        <div class="wrap">
            <h1><?php _e('Typeform Quizzes', 'typeform-quizzes'); ?></h1>
            <p><?php _e('Manage your Typeform Quizzes and configure settings.', 'typeform-quizzes'); ?></p>
            
            <div class="card">
                <h2><?php _e('Quick Start', 'typeform-quizzes'); ?></h2>
                <p><?php _e('To display quizzes on your site, use the following shortcode:', 'typeform-quizzes'); ?></p>
                <code>[typeform_quizzes_slider]</code>
                
                <h3><?php _e('Shortcode Parameters', 'typeform-quizzes'); ?></h3>
                <ul>
                    <li><strong>max</strong> - Maximum number of quizzes to display (default: 20)</li>
                    <li><strong>cols_desktop</strong> - Number of columns on desktop (default: 6)</li>
                    <li><strong>cols_tablet</strong> - Number of columns on tablet (default: 3)</li>
                    <li><strong>cols_mobile</strong> - Number of columns on mobile (default: 2)</li>
                    <li><strong>order</strong> - Order of quizzes: menu_order, date, title, rand (default: menu_order)</li>
                </ul>
                
                <p><a href="<?php echo admin_url('edit.php?post_type=typeform_quiz'); ?>" class="button button-primary">
                    <?php _e('Manage Quizzes', 'typeform-quizzes'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=typeform-quizzes-settings'); ?>" class="button">
                    <?php _e('Settings', 'typeform-quizzes'); ?>
                </a></p>
            </div>
        </div>
        <?php
    }

    /**
     * Render settings page
     */
    public static function render_settings_page(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Forbidden', 'typeform-quizzes'), __('Forbidden', 'typeform-quizzes'), ['response' => 403]);
        }

        // Handle form submissions
        self::handle_settings_save();
        
        // Clear migration transient if requested (for debugging)
        if (isset($_GET['clear_migration']) && $_GET['clear_migration'] === '1') {
            self::clear_migration_transient();
            echo '<div class="notice notice-info"><p>Migration transient cleared. Page will refresh to run migration again.</p></div>';
            echo '<script>setTimeout(function(){ window.location.href = window.location.href.split("?")[0]; }, 2000);</script>';
        }

        // Admin notice after default settings save
        $notice = isset($_GET['tfq_notice']) ? sanitize_text_field($_GET['tfq_notice']) : '';
        if ($notice === 'defaults_saved') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Default shortcode settings saved successfully.', 'typeform-quizzes') . '</p></div>';
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Typeform Quizzes Settings', 'typeform-quizzes'); ?></h1>
            
            <?php
            // Display success message if settings were just saved
            if (isset($_GET['settings-updated']) && sanitize_text_field($_GET['settings-updated']) === 'true') {
                $referer = wp_get_referer();
                if ($referer) {
                    if (strpos($referer, 'typeform_quizzes_defaults_options') !== false) {
                        echo '<div class="notice notice-success is-dismissible" style="margin: 20px 0;"><p><strong>✅ ' . esc_html__('Success!', 'typeform-quizzes') . '</strong> ' . esc_html__('Quiz settings saved successfully.', 'typeform-quizzes') . '</p></div>';
                    } else {
                        echo '<div class="notice notice-success is-dismissible" style="margin: 20px 0;"><p><strong>✅ ' . esc_html__('Success!', 'typeform-quizzes') . '</strong> ' . esc_html__('Settings saved successfully.', 'typeform-quizzes') . '</p></div>';
                    }
                } else {
                    echo '<div class="notice notice-success is-dismissible" style="margin: 20px 0;"><p><strong>✅ ' . esc_html__('Success!', 'typeform-quizzes') . '</strong> ' . esc_html__('Settings saved successfully.', 'typeform-quizzes') . '</p></div>';
                }
            }
            ?>
            
            <!-- Quiz Management Section -->
            <div style="background: #f8f9fa; border: 1px solid #e1e5e9; border-radius: 8px; padding: 25px; margin: 30px 0;">
                <h2 style="margin: 0 0 20px 0; color: #0073aa; font-size: 24px; border-bottom: 3px solid #0073aa; padding-bottom: 15px;">
                    📝 <?php esc_html_e('Quiz Management', 'typeform-quizzes'); ?>
                </h2>
                <p style="margin: 0 0 20px 0; color: #666; font-size: 14px; line-height: 1.5;">
                    <?php esc_html_e('Create and manage your Typeform Quizzes. Each quiz needs a Typeform URL and can have a featured image.', 'typeform-quizzes'); ?>
                </p>
                <p style="margin: 0 0 20px 0;">
                    <a href="<?php echo admin_url('edit.php?post_type=typeform_quiz'); ?>" class="button button-primary" style="background: #0073aa; border-color: #0073aa; padding: 8px 20px; font-size: 14px;">
                        📝 <?php esc_html_e('Manage Quizzes', 'typeform-quizzes'); ?>
                    </a>
                    <a href="<?php echo admin_url('post-new.php?post_type=typeform_quiz'); ?>" class="button" style="margin-left: 10px; padding: 8px 20px; font-size: 14px;">
                        ➕ <?php esc_html_e('Add New Quiz', 'typeform-quizzes'); ?>
                    </a>
                </p>
            </div>

            <!-- Default Shortcode Settings Section -->
            <div style="background: #f8f9fa; border: 1px solid #e1e5e9; border-radius: 8px; padding: 25px; margin: 30px 0;">
                <h2 style="margin: 0 0 20px 0; color: #0073aa; font-size: 24px; border-bottom: 3px solid #0073aa; padding-bottom: 15px;">
                    ⚙️ <?php esc_html_e('Default Shortcode Settings', 'typeform-quizzes'); ?>
                </h2>
                <p style="margin: 0 0 20px 0; color: #666; font-size: 14px; line-height: 1.5;">
                    <?php esc_html_e('Configure default values for all shortcode parameters. These settings will be used when no specific values are provided in the shortcode.', 'typeform-quizzes'); ?>
                </p>

            <form method="post" action="options.php">
                <?php
                settings_fields('typeform_quizzes_defaults_options'); // same group
                do_settings_sections('typeform-quizzes-tools');       // same page slug
                submit_button('💾 ' . __('Save Default Settings', 'typeform-quizzes'), 'primary', 'submit', false, ['style' => 'background: #0073aa; border-color: #0073aa; padding: 8px 20px; font-size: 14px;']);
                ?>
            </form>
            </div>

            <!-- Export/Import Section -->
            <div style="background: #f8f9fa; border: 1px solid #e1e5e9; border-radius: 8px; padding: 25px; margin: 30px 0;">
                <h2 style="margin: 0 0 20px 0; color: #0073aa; font-size: 24px; border-bottom: 3px solid #0073aa; padding-bottom: 15px;">
                    📤 <?php esc_html_e('Export & Import', 'typeform-quizzes'); ?>
                </h2>
                <p style="margin: 0 0 20px 0; color: #666; font-size: 14px; line-height: 1.5;">
                    <?php esc_html_e('Export your quizzes and settings to JSON files for backup or transfer to another site. Import from previously exported files.', 'typeform-quizzes'); ?>
                </p>
                
                <!-- Quizzes Export/Import -->
                <div style="margin-bottom: 30px;">
                    <h3 style="margin: 0 0 15px 0; color: #0073aa; font-size: 20px; border-bottom: 2px solid #e1e5e9; padding-bottom: 10px;">
                        🎯 <?php esc_html_e('Quizzes', 'typeform-quizzes'); ?>
                    </h3>
                    <div style="display: flex; gap: 20px; flex-wrap: wrap; margin: 20px 0;">
                        <!-- Export Quizzes Section -->
                        <div style="flex: 1; min-width: 300px; background: #fff; border: 1px solid #e1e5e9; border-radius: 6px; padding: 20px;">
                            <h4 style="margin: 0 0 15px 0; color: #0073aa; font-size: 18px; border-bottom: 2px solid #e1e5e9; padding-bottom: 10px;">
                                📤 <?php esc_html_e('Export Quizzes', 'typeform-quizzes'); ?>
                            </h4>
                            <p style="margin: 0 0 15px 0; color: #666; font-size: 14px;">
                                <?php esc_html_e('Download all your quizzes as a JSON file for backup or migration.', 'typeform-quizzes'); ?>
                            </p>
                            <form method="post" action="" style="margin: 0;">
                                <?php wp_nonce_field('typeform_quizzes_settings', '_wpnonce'); ?>
                                <input type="hidden" name="action" value="export_quizzes">
                                <button type="submit" class="button button-primary" style="background: #0073aa; border-color: #0073aa; padding: 8px 20px; font-size: 14px;">
                                    📥 <?php esc_html_e('Download Quiz Export', 'typeform-quizzes'); ?>
                                </button>
                            </form>
                        </div>
                        
                        <!-- Import Quizzes Section -->
                        <div style="flex: 1; min-width: 300px; background: #fff; border: 1px solid #e1e5e9; border-radius: 6px; padding: 20px;">
                            <h4 style="margin: 0 0 15px 0; color: #0073aa; font-size: 18px; border-bottom: 2px solid #e1e5e9; padding-bottom: 10px;">
                                📥 <?php esc_html_e('Import Quizzes', 'typeform-quizzes'); ?>
                            </h4>
                            <p style="margin: 0 0 15px 0; color: #666; font-size: 14px;">
                                <?php esc_html_e('Upload a previously exported JSON file to import quizzes.', 'typeform-quizzes'); ?>
                            </p>
                            <form method="post" action="" enctype="multipart/form-data" style="margin: 0;">
                                <?php wp_nonce_field('typeform_quizzes_settings', '_wpnonce'); ?>
                                <input type="hidden" name="action" value="import_quizzes">
                                <input type="file" name="quiz_import_file" accept=".json" required style="margin-bottom: 10px; width: 100%;">
                                <br>
                                <label style="display: flex; align-items: center; margin: 10px 0; font-size: 14px;">
                                    <input type="checkbox" name="overwrite_existing" value="1" style="margin-right: 8px;">
                                    <?php esc_html_e('Overwrite existing quizzes with same title', 'typeform-quizzes'); ?>
                                </label>
                                <button type="submit" class="button button-secondary" style="padding: 8px 20px; font-size: 14px;">
                                    📤 <?php esc_html_e('Upload & Import', 'typeform-quizzes'); ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Settings Export/Import -->
                <div>
                    <h3 style="margin: 0 0 15px 0; color: #0073aa; font-size: 20px; border-bottom: 2px solid #e1e5e9; padding-bottom: 10px;">
                        ⚙️ <?php esc_html_e('Plugin Settings', 'typeform-quizzes'); ?>
                    </h3>
                    <div style="display: flex; gap: 20px; flex-wrap: wrap; margin: 20px 0;">
                        <!-- Export Settings Section -->
                        <div style="flex: 1; min-width: 300px; background: #fff; border: 1px solid #e1e5e9; border-radius: 6px; padding: 20px;">
                            <h4 style="margin: 0 0 15px 0; color: #0073aa; font-size: 18px; border-bottom: 2px solid #e1e5e9; padding-bottom: 10px;">
                                📤 <?php esc_html_e('Export Settings', 'typeform-quizzes'); ?>
                            </h4>
                            <p style="margin: 0 0 15px 0; color: #666; font-size: 14px;">
                                <?php esc_html_e('Download your plugin settings as a JSON file for backup or transfer to another site.', 'typeform-quizzes'); ?>
                            </p>
                            <form method="post" action="" style="margin: 0;">
                                <?php wp_nonce_field('typeform_quizzes_settings', '_wpnonce'); ?>
                                <input type="hidden" name="action" value="export_settings">
                                <button type="submit" class="button button-primary" style="background: #0073aa; border-color: #0073aa; padding: 8px 20px; font-size: 14px;">
                                    📥 <?php esc_html_e('Download Settings Export', 'typeform-quizzes'); ?>
                                </button>
                            </form>
                        </div>
                        
                        <!-- Import Settings Section -->
                        <div style="flex: 1; min-width: 300px; background: #fff; border: 1px solid #e1e5e9; border-radius: 6px; padding: 20px;">
                            <h4 style="margin: 0 0 15px 0; color: #0073aa; font-size: 18px; border-bottom: 2px solid #e1e5e9; padding-bottom: 10px;">
                                📥 <?php esc_html_e('Import Settings', 'typeform-quizzes'); ?>
                            </h4>
                            <p style="margin: 0 0 15px 0; color: #666; font-size: 14px;">
                                <?php esc_html_e('Upload a previously exported settings JSON file to restore your configuration.', 'typeform-quizzes'); ?>
                            </p>
                            <form method="post" action="" enctype="multipart/form-data" style="margin: 0;">
                                <?php wp_nonce_field('typeform_quizzes_settings', '_wpnonce'); ?>
                                <input type="hidden" name="action" value="import_settings">
                                <input type="file" name="settings_import_file" accept=".json" required style="margin-bottom: 15px; width: 100%;">
                                <br>
                                <button type="submit" class="button button-secondary" style="padding: 8px 20px; font-size: 14px;">
                                    📤 <?php esc_html_e('Upload & Import Settings', 'typeform-quizzes'); ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; padding: 15px; margin: 20px 0;">
                    <h4 style="margin: 0 0 10px 0; color: #856404; font-size: 16px;">⚠️ Important Notes:</h4>
                    <ul style="margin: 0; padding-left: 20px; color: #856404; line-height: 1.6; font-size: 14px;">
                        <li><strong>Quiz Export:</strong> Includes quiz titles, content, Typeform URLs, featured images, and display order</li>
                        <li><strong>Settings Export:</strong> Includes all plugin configuration settings (colors, dimensions, layout options, etc.)</li>
                        <li><strong>Quiz Import:</strong> Creates new quiz posts - existing quizzes won't be modified unless "Overwrite" is checked</li>
                        <li><strong>Settings Import:</strong> Replaces all current settings with the imported configuration</li>
                        <li>Featured images will be re-imported from the original URLs if available</li>
                        <li>Always backup your site before importing data</li>
                        <li>Settings exports are compatible across different WordPress installations</li>
                    </ul>
                </div>
            </div>

            <!-- Usage Section -->
            <div style="background: #f8f9fa; border: 1px solid #e1e5e9; border-radius: 8px; padding: 25px; margin: 30px 0;">
                <h2 style="margin: 0 0 20px 0; color: #0073aa; font-size: 24px; border-bottom: 3px solid #0073aa; padding-bottom: 15px;">
                    📚 Usage & Documentation
                </h2>
                <p style="margin: 0 0 20px 0; color: #666; font-size: 14px; line-height: 1.5;">
                    Use the shortcode <code>[typeform_quizzes_slider max="20" max_width="1450" thumb_height="200" cols_desktop="6" cols_tablet="3" cols_mobile="2" gap="20" border_radius="16" title_color="#000000" title_hover_color="#777777" controls_spacing="56" controls_spacing_tablet="56" controls_bottom_spacing="20" arrow_border_radius="0" arrow_padding="3" arrow_width="35" arrow_height="35" arrow_bg_color="#111111" arrow_hover_bg_color="#000000" arrow_icon_color="#ffffff" arrow_icon_hover_color="#ffffff" arrow_icon_size="28" pagination_dot_color="#cfcfcf" pagination_active_dot_color="#111111" pagination_dot_gap="10" pagination_dot_size="8" active_slide_border_color="#0073aa" darken_inactive_slides="1" order="menu_order"]</code> in your posts or pages.
                </p>
                
                <div style="background: #e7f3ff; border: 1px solid #b3d9ff; border-radius: 6px; padding: 20px; margin: 20px 0;">
                    <h3 style="margin: 0 0 15px 0; color: #0073aa; font-size: 18px; border-bottom: 2px solid #b3d9ff; padding-bottom: 10px;">
                        🎯 Separate Main Viewer Window
                    </h3>
                    <p style="margin: 0 0 15px 0; color: #333; font-size: 14px; line-height: 1.5;">
                        <strong>New Feature:</strong> You can now separate the main viewer window from the quiz slider for more flexible layouts. When enabled, use the new <code>[typeform_quizzes_viewer]</code> shortcode to place the main viewer anywhere on your page.
                    </p>
                    <p style="margin: 0 0 15px 0; color: #333; font-size: 14px; line-height: 1.5;">
                        <strong>How to use:</strong>
                    </p>
                    <ul style="margin: 0 0 15px 0; padding-left: 20px; color: #333; line-height: 1.6;">
                        <li>Enable "Separate Main Viewer Window" in the Basic Configuration section above</li>
                        <li>Use <code>[typeform_quizzes_slider]</code> to display only the quiz thumbnails and navigation</li>
                        <li>Use <code>[typeform_quizzes_viewer]</code> to display the main viewer window separately</li>
                        <li>The viewer will automatically load the first quiz in your collection</li>
                    </ul>
                    <p style="margin: 0; color: #666; font-size: 13px; font-style: italic;">
                        This allows you to create custom layouts where the viewer and slider are positioned independently on your page.
                    </p>
                </div>
            
                <div style="background: #fff; border: 1px solid #e1e5e9; border-radius: 6px; padding: 20px; margin: 20px 0;">
                    <h3 style="margin: 0 0 15px 0; color: #0073aa; font-size: 18px; border-bottom: 2px solid #e1e5e9; padding-bottom: 10px;">
                        📋 Parameters
                    </h3>
                    <ul style="margin: 0; padding-left: 20px; color: #333; line-height: 1.6;">
                        <li><strong>max</strong> (optional): Maximum number of quizzes to display (default: 20)</li>
                        <li><strong>order</strong> (optional): Quiz order - "menu_order", "date", "title", or "rand" (default: menu_order)</li>
                        <li><strong>max_width</strong> (optional): Maximum width of grid in pixels (default: 1450)</li>
                        <li><strong>thumb_height</strong> (optional): Thumbnail height in pixels - any number between 50-1000 (default: 200)</li>
                        <li><strong>cols_desktop</strong> (optional): Number of quizzes visible per row on desktop (default: 6)</li>
                        <li><strong>cols_tablet</strong> (optional): Number of quizzes visible per row on tablet (default: 3)</li>
                        <li><strong>cols_mobile</strong> (optional): Number of quizzes visible per row on mobile (default: 2)</li>
                        <li><strong>gap</strong> (optional): Gap between quiz items in pixels (default: 20)</li>
                        <li><strong>border_radius</strong> (optional): Border radius for quiz thumbnails in pixels (default: 16)</li>
                        <li><strong>title_color</strong> (optional): Color of quiz titles in hex format (default: #000000)</li>
                        <li><strong>title_hover_color</strong> (optional): Color of quiz titles on hover in hex format (default: #777777)</li>
                        <li><strong>controls_spacing</strong> (optional): Space between pagination dots and next/prev buttons in pixels (default: 56)</li>
                        <li><strong>controls_spacing_tablet</strong> (optional): Space between pagination dots and next/prev buttons on tablet screens in pixels (default: 56)</li>
                        <li><strong>controls_bottom_spacing</strong> (optional): Space between bottom of slider and controls in pixels (default: 20)</li>
                        <li><strong>arrow_border_radius</strong> (optional): Border radius for arrow buttons in pixels (default: 0)</li>
                        <li><strong>arrow_padding</strong> (optional): Internal padding for arrow buttons in pixels (default: 3)</li>
                        <li><strong>arrow_width</strong> (optional): Width of arrow buttons in pixels (default: 35)</li>
                        <li><strong>arrow_height</strong> (optional): Height of arrow buttons in pixels (default: 35)</li>
                        <li><strong>arrow_bg_color</strong> (optional): Background color of arrow buttons in hex format (default: #111111)</li>
                        <li><strong>arrow_hover_bg_color</strong> (optional): Background color of arrow buttons on hover in hex format (default: #000000)</li>
                        <li><strong>arrow_icon_color</strong> (optional): Color of the arrow icons in hex format (default: #ffffff)</li>
                        <li><strong>arrow_icon_hover_color</strong> (optional): Color of the arrow icons on hover in hex format (default: #ffffff)</li>
                        <li><strong>arrow_icon_size</strong> (optional): Size of the arrow icons in pixels (default: 28)</li>
                        <li><strong>pagination_dot_color</strong> (optional): Color of inactive pagination dots in hex format (default: #cfcfcf)</li>
                        <li><strong>pagination_active_dot_color</strong> (optional): Color of active pagination dot in hex format (default: #111111)</li>
                        <li><strong>pagination_dot_gap</strong> (optional): Gap between pagination dots in pixels (default: 10)</li>
                        <li><strong>pagination_dot_size</strong> (optional): Size of pagination dots in pixels (default: 8)</li>
                        <li><strong>active_slide_border_color</strong> (optional): Border color for the active/selected quiz slide in hex format (default: #0073aa)</li>
                        <li><strong>darken_inactive_slides</strong> (optional): Whether to apply a dark overlay to inactive quiz slides (1 = enabled, 0 = disabled, default: 1)</li>
                        <li><strong>show_viewer_title</strong> (optional): Whether to show quiz titles above the viewer window (1 = enabled, 0 = disabled, default: 1)</li>
                    </ul>
                    
                    <h4 style="margin: 20px 0 15px 0; color: #0073aa; font-size: 16px; border-bottom: 2px solid #e1e5e9; padding-bottom: 8px;">
                        [typeform_quizzes_viewer] Parameters
                    </h4>
                    <ul style="margin: 0; padding-left: 20px; color: #333; line-height: 1.6;">
                        <li><strong>height</strong> (optional): Height of the viewer iframe in pixels or percentage (default: 600px)</li>
                        <li><strong>width</strong> (optional): Width of the viewer container in pixels or percentage (default: 100%)</li>
                        <li><strong>quiz_id</strong> (optional): Specific quiz ID to display. If not provided, shows the first quiz in the collection</li>
                        <li><strong>show_title</strong> (optional): Whether to show the quiz title above the viewer (true/false, default: true)</li>
                    </ul>
                </div>

                <div style="background: #fff; border: 1px solid #e1e5e9; border-radius: 6px; padding: 20px; margin: 20px 0;">
                    <h3 style="margin: 0 0 15px 0; color: #0073aa; font-size: 18px; border-bottom: 2px solid #e1e5e9; padding-bottom: 10px;">
                        💡 Examples
                    </h3>
            <p><strong>Basic usage:</strong><br>
            <code>[typeform_quizzes_slider]</code></p>
            
            <p><strong>Custom slider layout:</strong><br>
            <code>[typeform_quizzes_slider max="12" cols_desktop="4" cols_tablet="2" cols_mobile="1" gap="15"]</code></p>
            
            <p><strong>Order by published date (newest first):</strong><br>
            <code>[typeform_quizzes_slider order="date"]</code></p>
            
            <p><strong>Order by title (A-Z):</strong><br>
            <code>[typeform_quizzes_slider order="title"]</code></p>
            
            <p><strong>Random order:</strong><br>
            <code>[typeform_quizzes_slider order="rand"]</code></p>
            
            
            <p><strong>Custom styling:</strong><br>
            <code>[typeform_quizzes_slider border_radius="20" title_color="#0066cc" title_hover_color="#003366"]</code></p>
            
            <p><strong>Compact pagination spacing:</strong><br>
            <code>[typeform_quizzes_slider controls_spacing="30"]</code></p>
            
            <p><strong>Responsive pagination spacing:</strong><br>
            <code>[typeform_quizzes_slider controls_spacing="80" controls_spacing_tablet="40"]</code></p>
            
            <p><strong>Custom bottom spacing:</strong><br>
            <code>[typeform_quizzes_slider controls_bottom_spacing="40"]</code></p>
            
            <p><strong>Custom arrow styling:</strong><br>
            <code>[typeform_quizzes_slider arrow_border_radius="8" arrow_width="40" arrow_height="40" arrow_padding="5" arrow_bg_color="#0066cc" arrow_hover_bg_color="#004499" arrow_icon_color="#ffffff" arrow_icon_size="24"]</code></p>
            
            <p><strong>Custom pagination dots:</strong><br>
            <code>[typeform_quizzes_slider pagination_dot_color="#e0e0e0" pagination_active_dot_color="#0066cc" pagination_dot_gap="15" pagination_dot_size="10"]</code></p>
            
            <p><strong>Disable darkening of inactive slides:</strong><br>
            <code>[typeform_quizzes_slider darken_inactive_slides="0"]</code></p>
            
            <p><strong>Hide quiz titles in viewer:</strong><br>
            <code>[typeform_quizzes_slider show_viewer_title="0"]</code></p>
            
            <p><strong>Individual quiz display:</strong><br>
            <code>[typeform_quiz id="123" width="100%" height="500px"]</code></p>
            
            <p><strong>Individual quiz by URL:</strong><br>
            <code>[typeform_quiz url="https://form.typeform.com/to/abc123" width="100%" height="500px"]</code></p>
            
            <p><strong>Separate main viewer window:</strong><br>
            <code>[typeform_quizzes_viewer height="600px" width="100%" show_title="true"]</code></p>
            
            <p><strong>Separate viewer with specific quiz:</strong><br>
            <code>[typeform_quizzes_viewer quiz_id="123" height="500px" show_title="false"]</code></p>
            
            <p><strong>Note:</strong> The slider displays quizzes in a single row with pagination controls. Use the arrow buttons or dots to navigate through all quizzes. Click on a quiz to load it in the viewer below.</p>
                </div>

                <!-- Troubleshooting Section -->
                <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; padding: 20px; margin: 20px 0;">
                    <h3 style="margin: 0 0 15px 0; color: #856404; font-size: 18px; border-bottom: 2px solid #ffeaa7; padding-bottom: 10px;">
                        🔧 Troubleshooting
                    </h3>
                    <p style="margin: 0; color: #856404; line-height: 1.6;">
                        <strong>If new quizzes don't appear:</strong> Reload the page where the slider is embedded. The shortcode will fetch the latest quiz data.
                    </p>
                </div>
            </div>
        </div>
        <?php
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
                'sanitize_callback' => [__CLASS__, 'sanitize_defaults'],
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

        add_settings_field(
            'separate_viewer',
            __('Separate Main Viewer Window', 'typeform-quizzes'),
            [__CLASS__, 'field_separate_viewer'],
            'typeform-quizzes-tools',
            'tfq_basic_config'
        );

        add_settings_field(
            'show_viewer_title',
            __('Show Quiz Title in Viewer', 'typeform-quizzes'),
            [__CLASS__, 'field_show_viewer_title'],
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

    public static function field_separate_viewer(): void {
        $opts = \MTI\TypeformQuizzes\Services\Options::all();
        $val = isset($opts['separate_viewer']) ? $opts['separate_viewer'] : false;
        ?>
        <input type="hidden" name="typeform_quizzes_defaults[separate_viewer]" value="0">
        <input type="checkbox" id="separate_viewer" name="typeform_quizzes_defaults[separate_viewer]" 
               value="1" <?php checked($val, 1); ?>>
        <p class="description"><?php esc_html_e('Enable separate main viewer window shortcode [typeform_quizzes_viewer]. When enabled, the main viewer will not appear with the slider and must be placed separately using the new shortcode.', 'typeform-quizzes'); ?></p>
        <?php
    }

    public static function field_show_viewer_title(): void {
        $opts = \MTI\TypeformQuizzes\Services\Options::all();
        $val = isset($opts['show_viewer_title']) ? $opts['show_viewer_title'] : true;
        ?>
        <input type="hidden" name="typeform_quizzes_defaults[show_viewer_title]" value="0">
        <input type="checkbox" id="show_viewer_title" name="typeform_quizzes_defaults[show_viewer_title]" 
               value="1" <?php checked($val, 1); ?>>
        <p class="description"><?php esc_html_e('Show quiz title above the viewer window. This applies to both integrated and separate viewers.', 'typeform-quizzes'); ?></p>
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
        $val = isset($opts['arrow_icon_size']) ? $opts['arrow_icon_size'] : '28';
        ?>
        <input type="number" id="arrow_icon_size" name="typeform_quizzes_defaults[arrow_icon_size]" 
               value="<?php echo esc_attr($val); ?>" 
               min="12" max="48" placeholder="28">
        <p class="description"><?php esc_html_e('Size of the arrow icons in pixels (default: 28)', 'typeform-quizzes'); ?></p>
        <?php
    }

    /**
     * Handle export/import actions early to avoid headers already sent issues
     */
    public static function handle_export_import_early(): void
    {
        // Only handle on admin pages and for our specific actions
        if (!is_admin() || !isset($_POST['action']) || !isset($_POST['_wpnonce'])) {
            return;
        }
        
        $action = sanitize_text_field($_POST['action']);
        
        // Only handle our specific actions
        if (!in_array($action, ['export_quizzes', 'import_quizzes', 'export_settings', 'import_settings'])) {
            return;
        }
        
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['_wpnonce'], 'typeform_quizzes_settings')) {
            wp_die(__('Security check failed', 'typeform-quizzes'), __('Forbidden', 'typeform-quizzes'), ['response' => 403]);
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'typeform-quizzes'), __('Forbidden', 'typeform-quizzes'), ['response' => 403]);
        }
        
        if ($action === 'export_quizzes') {
            self::handle_export_quizzes();
        } elseif ($action === 'import_quizzes') {
            self::handle_import_quizzes();
        } elseif ($action === 'export_settings') {
            self::handle_export_settings();
        } elseif ($action === 'import_settings') {
            self::handle_import_settings();
        }
    }

    /**
     * Handle settings form submissions and show success messages
     */
    public static function handle_settings_save(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Note: Export/import actions are now handled in handle_export_import_early()
        // This method can be used for other settings form handling if needed
    }

    /**
     * Handle quiz export
     */
    public static function handle_export_quizzes(): void
    {
        // Security check - capability check only since nonce is verified in handle_export_import_early()
        if (!current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'typeform-quizzes'), __('Forbidden', 'typeform-quizzes'), ['response' => 403]);
        }
        
        // Start output buffering to prevent any output before headers
        ob_start();

        // Get all published quizzes
        $quizzes = get_posts([
            'post_type' => 'typeform_quiz',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ]);

        $export_data = [
            'version' => '1.0',
            'export_date' => current_time('mysql'),
            'site_url' => get_site_url(),
            'quizzes' => []
        ];

        foreach ($quizzes as $quiz) {
            $quiz_data = [
                'title' => $quiz->post_title,
                'content' => $quiz->post_content,
                'typeform_url' => get_post_meta($quiz->ID, '_typeform_url', true),
                'featured_image_url' => get_the_post_thumbnail_url($quiz->ID, 'full'),
                'meta' => [
                    'quiz_order' => get_post_meta($quiz->ID, '_quiz_order', true)
                ]
            ];
            $export_data['quizzes'][] = $quiz_data;
        }

        // Generate filename
        $filename = 'typeform-quizzes-export-' . date('Y-m-d-H-i-s') . '.json';

        // Clear any existing output buffer
        ob_clean();
        
        // Set headers for file download
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

        // Output JSON data
        echo json_encode($export_data, JSON_PRETTY_PRINT);
        
        // End output buffering and send output
        ob_end_flush();
        exit;
    }

    /**
     * Handle quiz import
     */
    public static function handle_import_quizzes(): void
    {
        // Security check - capability check only since nonce is verified in handle_settings_save()
        if (!current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'typeform-quizzes'), __('Forbidden', 'typeform-quizzes'), ['response' => 403]);
        }

        // Check if file was uploaded
        if (!isset($_FILES['quiz_import_file']) || $_FILES['quiz_import_file']['error'] !== UPLOAD_ERR_OK) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . esc_html__('Error uploading file. Please try again.', 'typeform-quizzes') . '</p></div>';
            });
            return;
        }

        $file = $_FILES['quiz_import_file'];
        
        // Validate file type
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($file_extension !== 'json') {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . esc_html__('Invalid file type. Please upload a JSON file.', 'typeform-quizzes') . '</p></div>';
            });
            return;
        }

        // Read and validate JSON
        $json_content = file_get_contents($file['tmp_name']);
        $json_data = json_decode($json_content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . esc_html__('Invalid JSON file. Please check the file format.', 'typeform-quizzes') . '</p></div>';
            });
            return;
        }

        // Use already validated JSON data
        $import_data = $json_data;

        // Validate import data structure
        if (!isset($import_data['quizzes']) || !is_array($import_data['quizzes'])) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>Invalid import file format. Missing quizzes data.</p></div>';
            });
            return;
        }

        $imported_count = 0;
        $skipped_count = 0;
        $errors = [];

        foreach ($import_data['quizzes'] as $quiz_data) {
            try {
                // Check if quiz already exists (by title)
                $existing_quiz_query = new \WP_Query([
                    'post_type' => 'typeform_quiz',
                    'post_status' => 'publish',
                    'posts_per_page' => 1,
                    'title' => $quiz_data['title']
                ]);

                $overwrite_existing = isset($_POST['overwrite_existing']) && $_POST['overwrite_existing'] === '1';

                if ($existing_quiz_query->have_posts() && !$overwrite_existing) {
                    $skipped_count++;
                    continue;
                }

                // Prepare quiz data
                $quiz_post_data = [
                    'post_title' => sanitize_text_field($quiz_data['title']),
                    'post_content' => wp_kses_post($quiz_data['content']),
                    'post_type' => 'typeform_quiz',
                    'post_status' => 'publish',
                    'post_author' => get_current_user_id()
                ];

                // If overwriting, update existing post
                if ($existing_quiz_query->have_posts() && $overwrite_existing) {
                    $existing_post = $existing_quiz_query->posts[0];
                    $quiz_post_data['ID'] = $existing_post->ID;
                    $quiz_id = wp_update_post($quiz_post_data);
                } else {
                    // Create new post
                    $quiz_id = wp_insert_post($quiz_post_data);
                }

                if (is_wp_error($quiz_id)) {
                    $errors[] = 'Error creating/updating quiz "' . $quiz_data['title'] . '": ' . $quiz_id->get_error_message();
                    continue;
                }

                // Save Typeform URL
                if (!empty($quiz_data['typeform_url'])) {
                    update_post_meta($quiz_id, '_typeform_url', esc_url_raw($quiz_data['typeform_url']));
                }

                // Save quiz order
                if (isset($quiz_data['meta']['quiz_order'])) {
                    update_post_meta($quiz_id, '_quiz_order', intval($quiz_data['meta']['quiz_order']));
                }

                // Handle featured image
                if (!empty($quiz_data['featured_image_url'])) {
                    self::import_featured_image($quiz_id, $quiz_data['featured_image_url']);
                }

                $imported_count++;

            } catch (\Exception $e) {
                $errors[] = 'Error importing quiz "' . $quiz_data['title'] . '": ' . $e->getMessage();
            }
        }

        // Show results
        $message = "Import completed! Imported: {$imported_count} quizzes";
        if ($skipped_count > 0) {
            $message .= ", Skipped: {$skipped_count} quizzes (already exist)";
        }
        if (!empty($errors)) {
            $message .= ", Errors: " . count($errors);
        }

        add_action('admin_notices', function() use ($message, $errors) {
            echo '<div class="notice notice-success"><p>' . esc_html($message) . '</p></div>';
            if (!empty($errors)) {
                echo '<div class="notice notice-warning"><p><strong>Errors:</strong><br>' . implode('<br>', array_map('esc_html', $errors)) . '</p></div>';
            }
        });
    }

    /**
     * Import featured image from URL
     */
    private static function import_featured_image(int $post_id, string $image_url): void
    {
        if (empty($image_url)) {
            return;
        }

        // Download image
        $image_data = wp_remote_get($image_url);
        if (is_wp_error($image_data)) {
            return;
        }

        $image_body = wp_remote_retrieve_body($image_data);
        if (empty($image_body)) {
            return;
        }

        // Get image filename
        $filename = basename(parse_url($image_url, PHP_URL_PATH));
        if (empty($filename)) {
            $filename = 'quiz-image-' . $post_id . '.jpg';
        }

        // Upload image
        $upload = wp_upload_bits($filename, null, $image_body);
        if ($upload['error']) {
            return;
        }

        // Create attachment
        $attachment = [
            'post_mime_type' => wp_check_filetype($filename)['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit'
        ];

        $attachment_id = wp_insert_attachment($attachment, $upload['file'], $post_id);
        if (is_wp_error($attachment_id)) {
            return;
        }

        // Generate attachment metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        // Suppress EXIF warnings that can occur with corrupted image files
        $attachment_data = @wp_generate_attachment_metadata($attachment_id, $upload['file']);
        if ($attachment_data) {
            wp_update_attachment_metadata($attachment_id, $attachment_data);
        }

        // Set as featured image
        set_post_thumbnail($post_id, $attachment_id);
    }

    /**
     * Handle settings export
     */
    public static function handle_export_settings(): void
    {
        // Security check - capability check only since nonce is verified in handle_export_import_early()
        if (!current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'typeform-quizzes'), __('Forbidden', 'typeform-quizzes'), ['response' => 403]);
        }
        
        // Start output buffering to prevent any output before headers
        ob_start();

        // Get current settings
        $settings = \MTI\TypeformQuizzes\Services\Options::all();

        $export_data = [
            'version' => '1.0',
            'export_date' => current_time('mysql'),
            'site_url' => get_site_url(),
            'plugin_version' => defined('TFQ_VERSION') ? TFQ_VERSION : '1.1.0',
            'settings' => $settings
        ];

        // Generate filename
        $filename = 'typeform-quizzes-settings-export-' . date('Y-m-d-H-i-s') . '.json';

        // Clear any existing output buffer
        ob_clean();
        
        // Set headers for file download
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

        // Output JSON data
        echo json_encode($export_data, JSON_PRETTY_PRINT);
        
        // End output buffering and send output
        ob_end_flush();
        exit;
    }

    /**
     * Handle settings import
     */
    public static function handle_import_settings(): void
    {
        // Security check - capability check only since nonce is verified in handle_export_import_early()
        if (!current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'typeform-quizzes'), __('Forbidden', 'typeform-quizzes'), ['response' => 403]);
        }

        // Check if file was uploaded
        if (!isset($_FILES['settings_import_file']) || $_FILES['settings_import_file']['error'] !== UPLOAD_ERR_OK) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . esc_html__('Error uploading file. Please try again.', 'typeform-quizzes') . '</p></div>';
            });
            return;
        }

        $file = $_FILES['settings_import_file'];
        
        // Validate file type
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($file_extension !== 'json') {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . esc_html__('Invalid file type. Please upload a JSON file.', 'typeform-quizzes') . '</p></div>';
            });
            return;
        }

        // Read and validate JSON
        $json_content = file_get_contents($file['tmp_name']);
        $json_data = json_decode($json_content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . esc_html__('Invalid JSON file. Please check the file format.', 'typeform-quizzes') . '</p></div>';
            });
            return;
        }

        // Validate import data structure
        if (!isset($json_data['settings']) || !is_array($json_data['settings'])) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . esc_html__('Invalid import file format. Missing settings data.', 'typeform-quizzes') . '</p></div>';
            });
            return;
        }

        // Always replace all settings when importing
        $imported_settings = $json_data['settings'];

        // Sanitize the imported settings using the same sanitizer as the form
        $sanitized_settings = self::sanitize_defaults($imported_settings);

        // Save the settings
        $success = \MTI\TypeformQuizzes\Services\Options::replace($sanitized_settings);

        // Check if settings are identical to current settings
        $current_settings = \MTI\TypeformQuizzes\Services\Options::all();
        $settings_identical = ($sanitized_settings === $current_settings);

        if ($success || $settings_identical) {
            if ($settings_identical) {
                $message = __('Settings imported successfully! The imported settings are identical to your current configuration, so no changes were needed.', 'typeform-quizzes');
            } else {
                $message = __('Settings imported successfully! Your configuration has been restored.', 'typeform-quizzes');
            }
            
            add_action('admin_notices', function() use ($message) {
                echo '<div class="notice notice-success"><p>' . esc_html($message) . '</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . esc_html__('Error saving settings. Please try again.', 'typeform-quizzes') . '</p></div>';
            });
        }
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
        
        // Map section IDs to their display names and WordPress dashicons
        $section_info = [
            'tfq_basic_config' => [
                'title' => __('Basic Configuration', 'typeform-quizzes'),
                'icon' => 'dashicons-admin-settings'
            ],
            'tfq_layout_grid' => [
                'title' => __('Layout & Grid Settings', 'typeform-quizzes'),
                'icon' => 'dashicons-grid-view'
            ],
            'tfq_colors_styling' => [
                'title' => __('Colors & Styling', 'typeform-quizzes'),
                'icon' => 'dashicons-admin-appearance'
            ],
            'tfq_navigation_controls' => [
                'title' => __('Navigation Controls', 'typeform-quizzes'),
                'icon' => 'dashicons-controls-play'
            ],
            'tfq_pagination' => [
                'title' => __('Pagination', 'typeform-quizzes'),
                'icon' => 'dashicons-marker'
            ]
        ];
        
        $info = $section_info[$section_id] ?? [
            'title' => $section_title,
            'icon' => 'dashicons-admin-settings'
        ];
        
        // Output enhanced section header with styling
        echo '<div class="tfq-section-header" data-section="' . esc_attr($section_id) . '" style="margin: 30px 0 20px 0; padding: 0 0 15px 0; border-bottom: 2px solid #e1e1e1;">';
        echo '<div style="display: flex; align-items: center;">';
        echo '<span class="dashicons ' . esc_attr($info['icon']) . '" style="font-size: 20px; color: #0073aa; margin-right: 12px; width: 20px; height: 20px;"></span>';
        echo '<h3 style="margin: 0; font-size: 18px; font-weight: 600; color: #23282d; line-height: 1.3;">';
        echo esc_html($info['title']);
        echo '</h3>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Migrate quiz orders to ensure all quizzes have proper order values
     */
    public static function migrate_quiz_orders(): void
    {
        // Only run once per admin session
        if (get_transient('tfq_quiz_orders_migrated')) {
            return;
        }

        // Only run for admin users
        if (!current_user_can('manage_options')) {
            return;
        }

        // Get all quizzes ordered by creation date
        $quizzes = get_posts([
            'post_type' => 'typeform_quiz',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'date',
            'order' => 'ASC'
        ]);

        if (empty($quizzes)) {
            // Set transient to prevent running again
            set_transient('tfq_quiz_orders_migrated', true, HOUR_IN_SECONDS);
            return;
        }

        // Get existing order values to avoid duplicates
        $existing_orders = [];
        foreach ($quizzes as $quiz) {
            $menu_order = $quiz->menu_order;
            $meta_order = get_post_meta($quiz->ID, '_quiz_order', true);
            
            // Get the current order value
            if ($menu_order > 0) {
                $existing_orders[] = $menu_order;
            } elseif ($menu_order === 0 && $meta_order !== '' && $meta_order !== false) {
                $existing_orders[] = $meta_order;
            }
        }

        // Find the next available order value
        $next_order = 1;
        if (!empty($existing_orders)) {
            $next_order = max($existing_orders) + 1;
        }

        // Set order values for quizzes that don't have them
        foreach ($quizzes as $quiz) {
            $menu_order = $quiz->menu_order;
            $meta_order = get_post_meta($quiz->ID, '_quiz_order', true);
            
            // Check if quiz already has an order value
            $has_order = false;
            if ($menu_order > 0) {
                $has_order = true;
            } elseif ($menu_order === 0 && $meta_order !== '' && $meta_order !== false) {
                $has_order = true;
            }
            
            // Only set order if quiz doesn't have one
            if (!$has_order) {
                // Update both meta field and menu_order directly in database to avoid triggering save_post
                update_post_meta($quiz->ID, '_quiz_order', $next_order);
                global $wpdb;
                $wpdb->update(
                    $wpdb->posts,
                    ['menu_order' => $next_order],
                    ['ID' => $quiz->ID],
                    ['%d'],
                    ['%d']
                );
                $next_order++;
            }
        }

        // Set transient to prevent running again
        set_transient('tfq_quiz_orders_migrated', true, HOUR_IN_SECONDS);
    }

    /**
     * Clear migration transient (for debugging/testing)
     */
    public static function clear_migration_transient(): void
    {
        delete_transient('tfq_quiz_orders_migrated');
    }

    /**
     * Sanitize and validate default settings
     */
    public static function sanitize_defaults($input): array
    {
        if (!current_user_can('manage_options')) {
            return [];
        }

        if (!is_array($input)) {
            return [];
        }

        $sanitized = [];

        // Basic configuration
        $sanitized['max'] = \MTI\TypeformQuizzes\Support\Sanitize::integer_clamp(
            $input['max'] ?? 20, 
            1, 
            100,
            20
        );

        $sanitized['cols_desktop'] = \MTI\TypeformQuizzes\Support\Sanitize::integer_clamp(
            $input['cols_desktop'] ?? 6, 
            1, 
            12,
            6
        );

        $sanitized['cols_tablet'] = \MTI\TypeformQuizzes\Support\Sanitize::integer_clamp(
            $input['cols_tablet'] ?? 3, 
            1, 
            8,
            3
        );

        $sanitized['cols_mobile'] = \MTI\TypeformQuizzes\Support\Sanitize::integer_clamp(
            $input['cols_mobile'] ?? 2, 
            1, 
            4,
            2
        );

        $sanitized['order'] = \MTI\TypeformQuizzes\Support\Sanitize::order(
            $input['order'] ?? 'menu_order'
        );

        $sanitized['separate_viewer'] = \MTI\TypeformQuizzes\Support\Sanitize::boolean(
            $input['separate_viewer'] ?? false
        );

        $sanitized['show_viewer_title'] = \MTI\TypeformQuizzes\Support\Sanitize::boolean(
            $input['show_viewer_title'] ?? true
        );

        // Layout settings
        $sanitized['max_width'] = \MTI\TypeformQuizzes\Support\Sanitize::integer_clamp(
            $input['max_width'] ?? 1450, 
            200, 
            2000,
            1450
        );

        $sanitized['thumb_height'] = \MTI\TypeformQuizzes\Support\Sanitize::integer_clamp(
            $input['thumb_height'] ?? 200, 
            100, 
            500,
            200
        );

        $sanitized['border_radius'] = \MTI\TypeformQuizzes\Support\Sanitize::integer_clamp(
            $input['border_radius'] ?? 16, 
            0, 
            50,
            16
        );

        $sanitized['gap'] = \MTI\TypeformQuizzes\Support\Sanitize::integer_clamp(
            $input['gap'] ?? 20, 
            0, 
            100,
            20
        );

        // Color settings
        $sanitized['title_color'] = \MTI\TypeformQuizzes\Support\Sanitize::hex_color(
            $input['title_color'] ?? '#000000'
        );

        $sanitized['title_hover_color'] = \MTI\TypeformQuizzes\Support\Sanitize::hex_color(
            $input['title_hover_color'] ?? '#777777'
        );

        $sanitized['active_slide_border_color'] = \MTI\TypeformQuizzes\Support\Sanitize::hex_color(
            $input['active_slide_border_color'] ?? '#0073aa'
        );

        $sanitized['darken_inactive_slides'] = \MTI\TypeformQuizzes\Support\Sanitize::boolean(
            $input['darken_inactive_slides'] ?? true
        );

        // Navigation settings
        $sanitized['controls_spacing'] = \MTI\TypeformQuizzes\Support\Sanitize::integer_clamp(
            $input['controls_spacing'] ?? 56, 
            0, 
            200,
            56
        );

        $sanitized['controls_spacing_tablet'] = \MTI\TypeformQuizzes\Support\Sanitize::integer_clamp(
            $input['controls_spacing_tablet'] ?? 56, 
            0, 
            200,
            56
        );

        $sanitized['controls_bottom_spacing'] = \MTI\TypeformQuizzes\Support\Sanitize::integer_clamp(
            $input['controls_bottom_spacing'] ?? 20, 
            0, 
            100,
            20
        );

        $sanitized['arrow_border_radius'] = \MTI\TypeformQuizzes\Support\Sanitize::integer_clamp(
            $input['arrow_border_radius'] ?? 0, 
            0, 
            50,
            0
        );

        $sanitized['arrow_padding'] = \MTI\TypeformQuizzes\Support\Sanitize::integer_clamp(
            $input['arrow_padding'] ?? 3, 
            0, 
            20,
            3
        );

        $sanitized['arrow_width'] = \MTI\TypeformQuizzes\Support\Sanitize::integer_clamp(
            $input['arrow_width'] ?? 35, 
            20, 
            100,
            35
        );

        $sanitized['arrow_height'] = \MTI\TypeformQuizzes\Support\Sanitize::integer_clamp(
            $input['arrow_height'] ?? 35, 
            20, 
            100,
            35
        );

        $sanitized['arrow_bg_color'] = \MTI\TypeformQuizzes\Support\Sanitize::hex_color(
            $input['arrow_bg_color'] ?? '#111111'
        );

        $sanitized['arrow_hover_bg_color'] = \MTI\TypeformQuizzes\Support\Sanitize::hex_color(
            $input['arrow_hover_bg_color'] ?? '#000000'
        );

        $sanitized['arrow_icon_color'] = \MTI\TypeformQuizzes\Support\Sanitize::hex_color(
            $input['arrow_icon_color'] ?? '#ffffff'
        );

        $sanitized['arrow_icon_hover_color'] = \MTI\TypeformQuizzes\Support\Sanitize::hex_color(
            $input['arrow_icon_hover_color'] ?? '#ffffff'
        );

        $sanitized['arrow_icon_size'] = \MTI\TypeformQuizzes\Support\Sanitize::integer_clamp(
            $input['arrow_icon_size'] ?? 28, 
            12, 
            48,
            28
        );

        // Pagination settings
        $sanitized['pagination_dot_color'] = \MTI\TypeformQuizzes\Support\Sanitize::hex_color(
            $input['pagination_dot_color'] ?? '#cfcfcf'
        );

        $sanitized['pagination_active_dot_color'] = \MTI\TypeformQuizzes\Support\Sanitize::hex_color(
            $input['pagination_active_dot_color'] ?? '#0073aa'
        );

        $sanitized['pagination_dot_gap'] = \MTI\TypeformQuizzes\Support\Sanitize::integer_clamp(
            $input['pagination_dot_gap'] ?? 10, 
            0, 
            50,
            10
        );

        $sanitized['pagination_dot_size'] = \MTI\TypeformQuizzes\Support\Sanitize::integer_clamp(
            $input['pagination_dot_size'] ?? 8, 
            4, 
            20,
            8
        );

        return $sanitized;
    }
}
