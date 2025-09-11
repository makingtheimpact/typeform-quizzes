# Typeform Quizzes Plugin

A professional WordPress plugin that creates a custom post type for Typeform Quizzes and provides responsive shortcodes for displaying them in sliders and individual views.

## 🚀 Features

- **Custom Post Type**: Create and manage Typeform Quizzes with featured images
- **Individual Quiz Shortcode**: Display a single quiz with `[typeform_quiz]`
- **Quiz Slider Shortcode**: Display multiple quizzes in a responsive slider with `[typeform_quizzes_slider]`
- **Responsive Design**: Fully responsive with customizable columns for desktop, tablet, and mobile
- **Quiz Ordering**: Control quiz display order (custom, date, title, random)
- **Featured Images**: Support for quiz thumbnails with fallback design
- **Interactive Viewer**: Click quizzes to load them in an embedded viewer
- **Customizable Styling**: Full control over colors, spacing, and layout
- **Caching System**: Built-in caching for optimal performance
- **Production Ready**: Secure, efficient, and compatible with other plugins

## 📋 Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Modern web browser with JavaScript enabled

## 🛠️ Installation

1. Upload the plugin files to `/wp-content/plugins/typeform-quizzes/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to 'Typeform Quizzes > Settings' to configure settings

## 📖 Usage

### Creating Quizzes

1. Go to 'Typeform Quizzes' in your WordPress admin
2. Click 'Add New Quiz'
3. Enter a title and description
4. Add a Typeform URL in the Quiz Settings meta box
5. Set the display order (lower numbers appear first)
6. Set a featured image for the quiz thumbnail
7. Publish the quiz

### Shortcodes

#### Individual Quiz
```php
[typeform_quiz id="123" width="100%" height="500px"]
[typeform_quiz url="https://form.typeform.com/to/abc123" width="100%" height="500px"]
```

#### Quiz Slider
```php
[typeform_quizzes_slider]
[typeform_quizzes_slider max="12" cols_desktop="4" cols_tablet="2" cols_mobile="1"]
[typeform_quizzes_slider order="date" center_on_click="false"]
[typeform_quizzes_slider max_width="1200" thumb_height="250" gap="30"]
[typeform_quizzes_slider title_color="#0066cc" title_hover_color="#004499"]
```

### Parameters

**Individual Quiz:**
- `id` - Quiz post ID (optional if URL is provided)
- `url` - Direct Typeform URL (optional if ID is provided)
- `width` - Container width (default: 100%)
- `height` - Container height (default: 500px)

**Quiz Slider:**
- `max` - Maximum quizzes to display (default: 20, max: 50)
- `order` - Order: menu_order, date, title, rand (default: menu_order)
- `max_width` - Maximum width of grid in pixels (default: 1450, max: 2000)
- `thumb_height` - Thumbnail height in pixels (default: 200, range: 50-1000)
- `cols_desktop` - Desktop columns (default: 6, max: 12)
- `cols_tablet` - Tablet columns (default: 3, max: 8)
- `cols_mobile` - Mobile columns (default: 2, max: 4)
- `gap` - Gap between items in pixels (default: 20, max: 100)
- `center_on_click` - Center quiz viewer when clicked (default: true)
- `border_radius` - Thumbnail border radius (default: 16, max: 50)
- `title_color` - Title color (default: #111111)
- `title_hover_color` - Title hover color (default: #000000)
- `controls_spacing` - Space around navigation controls (default: 56)
- `controls_spacing_tablet` - Tablet controls spacing (default: 56)
- `controls_spacing_mobile` - Mobile controls spacing (default: 56)
- `controls_bottom_spacing` - Space below slider (default: 20)
- `arrow_border_radius` - Arrow border radius (default: 0)
- `arrow_padding` - Arrow internal padding (default: 3)
- `arrow_width` - Arrow width (default: 35)
- `arrow_height` - Arrow height (default: 35)
- `arrow_bg_color` - Arrow background color (default: #111111)
- `arrow_hover_bg_color` - Arrow hover background color (default: #000000)
- `arrow_icon_color` - Arrow icon color (default: #ffffff)
- `arrow_icon_hover_color` - Arrow icon hover color (default: #ffffff)
- `arrow_icon_size` - Arrow icon size (default: 28)
- `pagination_dot_color` - Pagination dot color (default: #cfcfcf)
- `pagination_active_dot_color` - Active pagination dot color (default: #111111)
- `active_slide_border_color` - Active slide border color (default: #0073aa)

## 🎨 Styling

The plugin includes comprehensive CSS that can be customized. All styles are prefixed with `typeform-quizzes-` to avoid conflicts.

### CSS Classes
- `.typeform-quizzes-slider-container` - Main container
- `.typeform-quizzes-slider` - Swiper container
- `.typeform-quiz-slide` - Individual quiz slide
- `.quiz-viewer` - Quiz viewer container
- `.quiz-thumbnail` - Quiz thumbnail container
- `.quiz-title` - Quiz title

### CSS Variables
The plugin uses CSS custom properties for easy theming:
- `--thumb-height` - Thumbnail height
- `--arrow-icon-size` - Arrow icon size
- `--arrow-icon-color` - Arrow icon color
- `--controls-spacing` - Controls spacing
- `--controls-spacing-tablet` - Tablet controls spacing
- `--controls-spacing-mobile` - Mobile controls spacing

## 🔧 Configuration

### Default Settings
Configure default values for all shortcodes in the admin panel:
- Go to 'Typeform Quizzes > Settings'
- Adjust settings in the 'Default Shortcode Settings' section
- Click 'Save Default Settings'

### Cache Management
- Use the 'Purge Quiz Cache' button to clear cached data
- Cache is automatically managed with 1-hour TTL
- Cache keys are prefixed with `tf_quizzes_`

## 🔒 Security

- All inputs are sanitized and validated
- Nonces used for all form submissions
- Capability checks for admin functions
- SQL injection protection via WordPress APIs
- XSS prevention with proper escaping

## ⚡ Performance

- Built-in transient caching system
- Optimized database queries using WP_Query
- Conditional asset loading (only when shortcodes are used)
- CDN usage for external libraries
- Responsive image loading

## 🧪 Development

### File Structure
```
typeform-quizzes/
├── typeform-quizzes.php              # Main plugin file (all functionality)
├── README.md                         # Documentation
└── LICENSE                          # License file
```

**Note:** This plugin uses a single-file architecture for simplicity and ease of maintenance. All functionality is contained within the main plugin file.

### Hooks and Filters

**Actions:**
- `typeform_quizzes_init` - After plugin initialization
- `typeform_quizzes_quiz_selected` - When a quiz is selected in slider

**Filters:**
- `typeform_quizzes_default_settings` - Modify default settings
- `typeform_quizzes_quiz_data` - Modify quiz data before display

### JavaScript Events

**Custom Events:**
- `typeform-quiz-selected` - Triggered when a quiz is clicked
- `typeform-slider-initialized` - Triggered when slider is initialized

## 🐛 Troubleshooting

### Common Issues

**Quizzes not appearing:**
1. Check if quizzes are published
2. Verify Typeform URLs are valid
3. Purge cache using admin button
4. Check for JavaScript errors in browser console

**Styling issues:**
1. Check for theme CSS conflicts
2. Verify custom CSS is loading
3. Check responsive breakpoints

**Performance issues:**
1. Reduce maximum quizzes displayed
2. Check server resources
3. Verify caching is working

### Debug Mode

Enable WordPress debug mode to see detailed error logs:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## 📝 Changelog

### 1.0.2
- Fixed version consistency between plugin header and documentation
- Corrected cache duration documentation (1 hour, not 24 hours)
- Updated admin menu path references (Typeform Quizzes > Settings, not Tools > Typeform Quizzes)
- Added missing shortcode parameters (max_width, thumb_height) to documentation
- Corrected file structure documentation to reflect single-file architecture
- Enhanced shortcode examples with additional parameter combinations
- Improved parameter descriptions with accurate ranges and limits

### 1.0.1
- Initial release
- Custom post type for Typeform Quizzes
- Individual quiz and slider shortcodes
- Responsive design and customization options
- Admin settings page
- Production-ready security and performance features

## 📄 License

This plugin is licensed under the GPLv2 or later.

## 🤝 Support

For support and feature requests, please contact the plugin author or create an issue in the plugin repository.

## 🙏 Credits

- Swiper.js for the slider functionality
- Font Awesome for icons
- WordPress for the amazing platform