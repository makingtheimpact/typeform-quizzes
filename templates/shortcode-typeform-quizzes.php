<?php
/** @var array $ctx */
$atts = $ctx['atts'];
$slider_id = $ctx['slider_id'];
$quizzes = $ctx['quizzes'];
$max_width = $ctx['max_width'];
$thumb_height = $ctx['thumb_height'];
$cols_desktop = $ctx['cols_desktop'];
$cols_tablet = $ctx['cols_tablet'];
$cols_mobile = $ctx['cols_mobile'];
$gap = $ctx['gap'];

// Use pre-computed styling attributes from context
$border_radius = $ctx['border_radius'];
$title_color = $ctx['title_color'];
$title_hover_color = $ctx['title_hover_color'];
$controls_spacing = $ctx['controls_spacing'];
$controls_spacing_tablet = $ctx['controls_spacing_tablet'];
$controls_bottom_spacing = $ctx['controls_bottom_spacing'];
$arrow_border_radius = $ctx['arrow_border_radius'];
$arrow_padding = $ctx['arrow_padding'];
$arrow_width = $ctx['arrow_width'];
$arrow_height = $ctx['arrow_height'];
$arrow_bg_color = $ctx['arrow_bg_color'];
$arrow_hover_bg_color = $ctx['arrow_hover_bg_color'];
$arrow_icon_color = $ctx['arrow_icon_color'];
$arrow_icon_hover_color = $ctx['arrow_icon_hover_color'];
$arrow_icon_size = $ctx['arrow_icon_size'];
$pagination_dot_color = $ctx['pagination_dot_color'];
$pagination_active_dot_color = $ctx['pagination_active_dot_color'];
$pagination_dot_gap = $ctx['pagination_dot_gap'];
$pagination_dot_size = $ctx['pagination_dot_size'];
$active_slide_border_color = $ctx['active_slide_border_color'];
$darken_inactive_slides = $ctx['darken_inactive_slides'];
$thumb_height_css = $ctx['thumb_height_css'];
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
            <?php if (\MTI\TypeformQuizzes\Frontend\Shortcodes\SliderRenderer::is_local_development()): ?>
            <div id="dev-notice" style="background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 10px; margin-bottom: 10px; border-radius: 4px; font-size: 14px;">
                <strong>Development Notice:</strong> Typeform CSP may block embedding in local development. 
                <a href="<?php echo esc_url($quizzes[0]['url']); ?>" target="_blank" style="color: #0073aa; text-decoration: underline;">Click here to open in new tab</a>
            </div>
            <?php endif; ?>
            <iframe id="quiz-iframe" 
                    src="<?php echo esc_url(\MTI\TypeformQuizzes\Frontend\Shortcodes\SliderRenderer::get_embed_url($quizzes[0]['url'])); ?>" 
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
            <?php echo \MTI\TypeformQuizzes\Frontend\Pagination\Dots::markup($slider_id); ?>
            
            <button class="tfqrow-arrow tfqrow-arrow-next" data-dir="next" aria-label="Next" data-bg-color="<?php echo $arrow_bg_color; ?>" data-hover-bg-color="<?php echo $arrow_hover_bg_color; ?>" data-icon-color="<?php echo $arrow_icon_color; ?>" data-hover-icon-color="<?php echo $arrow_icon_hover_color; ?>">
                <i class="fa-solid fa-angle-right"></i>
            </button>
        </div>
    </div>
</div>

<?php
// Add pagination JavaScript initialization using pre-computed parameters
echo \MTI\TypeformQuizzes\Frontend\Pagination\Dots::script($slider_id, $ctx['swiper_params']);
?>
