/**
 * Typeform Quizzes Plugin JavaScript
 * Handles slider initialization, quiz interactions, and UI functionality
 */

(function($) {
    'use strict';

    // Plugin object
    const TypeformQuizzes = {
        
        // Configuration
        config: {
            sliderId: null,
            colsDesktop: 6,
            colsTablet: 3,
            colsMobile: 2,
            gap: 20,
            paginationDotColor: '#cfcfcf',
            paginationActiveDotColor: '#111111',
            paginationDotSize: 8,
            paginationDotGap: 10
        },

        // Initialize the plugin
        init: function(config) {
            this.config = $.extend({}, this.config, config);
            this.bindEvents();
            this.initSlider();
        },

        // Bind event handlers with proper scoping
        bindEvents: function() {
            const self = this;
            
            // Handle quiz click - scoped to plugin container
            $(document).on('click', '.typeform-quizzes-slider-container .typeform-quiz-slide', function(e) {
                e.preventDefault();
                self.handleQuizClick($(this));
            });

            // Handle close button - scoped to plugin container
            $(document).on('click', '.typeform-quizzes-slider-container .quiz-viewer-close', function(e) {
                e.preventDefault();
                self.closeQuizViewer();
            });

            // Handle fullscreen toggle - scoped to plugin container
            $(document).on('click', '.typeform-quizzes-slider-container .quiz-viewer-expand', function(e) {
                e.preventDefault();
                self.toggleFullscreen($(this));
            });

            // Handle fullscreen toggle for separate viewers
            $(document).on('click', '.typeform-quiz-viewer-container .quiz-viewer-expand', function(e) {
                e.preventDefault();
                self.toggleFullscreen($(this));
            });

            // Handle escape key to exit fullscreen - scoped to plugin
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    // Check for integrated viewer fullscreen
                    if ($('.typeform-quizzes-slider-container .quiz-viewer').hasClass('quiz-viewer-fullscreen')) {
                        $('.typeform-quizzes-slider-container .quiz-viewer-expand').click();
                    }
                    // Check for separate viewer fullscreen
                    else if ($('.typeform-quiz-viewer-container').hasClass('quiz-viewer-fullscreen')) {
                        $('.typeform-quiz-viewer-container .quiz-viewer-expand').click();
                    }
                }
            });
        },

        // Initialize Swiper slider
        initSlider: function() {
            const self = this;
            
            // Handle CSP errors for local development
            this.handleCSPErrors();
            
            // Initialize arrow hover effects
            this.initArrowHoverEffects();
            
            // Font Awesome detection and fallback
            this.detectFontAwesome();
            
            // Initialize Swiper with conflict prevention
            if (typeof Swiper !== 'undefined' && this.config.sliderId) {
                // Check if slider is already initialized to prevent conflicts
                const sliderElement = document.getElementById(this.config.sliderId);
                if (sliderElement && sliderElement.swiper) {
                    console.warn('Typeform Quizzes: Slider already initialized, skipping re-initialization');
                    return;
                }
                
                const swiper = new Swiper('#' + this.config.sliderId, {
                    slidesPerView: this.config.colsDesktop,
                    spaceBetween: this.config.gap,
                    loop: false,
                    slidesPerGroup: this.config.colsDesktop,
                    navigation: {
                        nextEl: '.tfqrow-arrow[data-dir="next"]',
                        prevEl: '.tfqrow-arrow[data-dir="prev"]',
                    },
                    pagination: {
                        el: '.swiper-pagination',
                        clickable: true,
                    },
                    breakpoints: {
                        320: {
                            slidesPerView: this.config.colsMobile,
                            spaceBetween: this.config.gap,
                            slidesPerGroup: this.config.colsMobile,
                        },
                        768: {
                            slidesPerView: this.config.colsTablet,
                            spaceBetween: this.config.gap,
                            slidesPerGroup: this.config.colsTablet,
                        },
                        1024: {
                            slidesPerView: this.config.colsDesktop,
                            spaceBetween: this.config.gap,
                            slidesPerGroup: this.config.colsDesktop,
                        },
                    },
                    on: {
                        init: function() {
                            // Swiper initialized
                        }
                    }
                });
            }
        },

        // Handle quiz click
        handleQuizClick: function($slide) {
            const quizUrl = $slide.data('quiz-url');
            const quizTitle = $slide.data('quiz-title');
            
            if (quizUrl) {
                // Remove active class from all slides in this container
                $slide.closest('.typeform-quizzes-slider-container').find('.typeform-quiz-slide').removeClass('active');
                
                // Add active class to clicked slide
                $slide.addClass('active');
                
                // Add embed parameters
                let embedUrl = quizUrl;
                if (embedUrl.indexOf('?') === -1) {
                    embedUrl += '?embed=true&embed-hide-headers=true&embed-hide-footer=true';
                } else {
                    embedUrl += '&embed=true&embed-hide-headers=true&embed-hide-footer=true';
                }
                
                // Update quiz viewer in this container (if it exists)
                const $container = $slide.closest('.typeform-quizzes-slider-container');
                const $localViewer = $container.find('.quiz-viewer');
                if ($localViewer.length) {
                    $container.find('.quiz-viewer-title').text(quizTitle);
                    $container.find('#quiz-iframe').attr('src', embedUrl);
                }
                
                // Also update any separate viewers on the page
                this.updateSeparateViewers(quizUrl, quizTitle, embedUrl);
                
            }
        },

        // Update separate viewers on the page
        updateSeparateViewers: function(quizUrl, quizTitle, embedUrl) {
            // Find all separate viewer containers
            $('.typeform-quiz-viewer-container').each(function() {
                const $viewer = $(this);
                
                // Update title if it exists and is visible
                const $title = $viewer.find('.quiz-viewer-title');
                if ($title.length && $title.is(':visible')) {
                    $title.text(quizTitle);
                }
                
                // Update iframe
                const $iframe = $viewer.find('#quiz-iframe');
                if ($iframe.length) {
                    $iframe.attr('src', embedUrl);
                }
                
                // Update dev notice link if it exists
                const $devNotice = $viewer.find('#dev-notice a');
                if ($devNotice.length) {
                    $devNotice.attr('href', quizUrl);
                }
            });
        },

        // Close quiz viewer
        closeQuizViewer: function() {
            // Close integrated viewers
            $('.typeform-quizzes-slider-container .quiz-viewer').hide();
            $('.typeform-quizzes-slider-container #quiz-iframe').attr('src', '');
            
            // Also close separate viewers
            $('.typeform-quiz-viewer-container').hide();
            $('.typeform-quiz-viewer-container #quiz-iframe').attr('src', '');
        },

        // Toggle fullscreen mode
        toggleFullscreen: function($expandBtn) {
            // Try to find viewer in the same container first (integrated viewer)
            let $viewer = $expandBtn.closest('.typeform-quizzes-slider-container').find('.quiz-viewer');
            
            // If not found, look for separate viewer container
            if (!$viewer.length) {
                $viewer = $expandBtn.closest('.typeform-quiz-viewer-container');
            }
            
            const $icon = $expandBtn.find('i');
            
            if ($viewer.hasClass('quiz-viewer-fullscreen')) {
                // Exit fullscreen
                $viewer.removeClass('quiz-viewer-fullscreen');
                $icon.removeClass('fa-compress').addClass('fa-expand');
                $expandBtn.attr('title', 'Expand to fullscreen');
                $('body').css('overflow', '');
            } else {
                // Enter fullscreen
                $viewer.addClass('quiz-viewer-fullscreen');
                $icon.removeClass('fa-expand').addClass('fa-compress');
                $expandBtn.attr('title', 'Exit fullscreen');
                $('body').css('overflow', 'hidden');
            }
        },

        // Handle CSP errors for local development
        handleCSPErrors: function() {
            const iframe = document.getElementById('quiz-iframe');
            if (iframe) {
                iframe.addEventListener('error', function() {
                    const devNotice = document.getElementById('dev-notice');
                    if (devNotice) {
                        devNotice.style.display = 'block';
                    }
                });
            }
        },

        // Initialize arrow hover effects
        initArrowHoverEffects: function() {
            $('.typeform-quizzes-slider-container .tfqrow-arrow').each(function() {
                const $button = $(this);
                const bgColor = $button.data('bg-color');
                const hoverBgColor = $button.data('hover-bg-color');
                const iconColor = $button.data('icon-color');
                const hoverIconColor = $button.data('hover-icon-color');
                const $icon = $button.find('i');
                
                if (bgColor && hoverBgColor) {
                    $button.on('mouseenter', function() {
                        $button.css('background-color', hoverBgColor);
                        if ($icon.length && hoverIconColor) {
                            $icon.css('color', hoverIconColor);
                        }
                    });
                    
                    $button.on('mouseleave', function() {
                        $button.css('background-color', bgColor);
                        if ($icon.length && iconColor) {
                            $icon.css('color', iconColor);
                        }
                    });
                }
            });
        },

        // Font Awesome detection and fallback
        detectFontAwesome: function() {
            // Create a test element to check if Font Awesome is loaded
            const testElement = document.createElement('i');
            testElement.className = 'fa-solid fa-angle-left';
            testElement.style.position = 'absolute';
            testElement.style.left = '-9999px';
            testElement.style.visibility = 'hidden';
            document.body.appendChild(testElement);
            
            // Check if Font Awesome actually rendered
            const computedStyle = window.getComputedStyle(testElement);
            const fontFamily = computedStyle.getPropertyValue('font-family');
            const isFontAwesomeLoaded = fontFamily.includes('Font Awesome') || fontFamily.includes('FontAwesome');
            
            // Clean up test element
            document.body.removeChild(testElement);
            
            // Apply fallback class if Font Awesome is not loaded
            if (!isFontAwesomeLoaded) {
                $('.typeform-quizzes-slider-container .tfqrow-arrow').addClass('fa-fallback');
                $('.typeform-quizzes-slider-container .quiz-viewer-expand').addClass('fa-fallback');
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        // Get configuration from data attributes or use defaults
        const sliderElement = $('.typeform-quizzes-slider-container').first();
        if (sliderElement.length) {
            const config = {
                sliderId: sliderElement.find('.swiper').attr('id'),
                colsDesktop: parseInt(sliderElement.data('cols-desktop')) || 6,
                colsTablet: parseInt(sliderElement.data('cols-tablet')) || 3,
                colsMobile: parseInt(sliderElement.data('cols-mobile')) || 2,
                gap: parseInt(sliderElement.data('gap')) || 20,
                paginationDotColor: sliderElement.data('pagination-dot-color') || '#cfcfcf',
                paginationActiveDotColor: sliderElement.data('pagination-active-dot-color') || '#111111',
                paginationDotSize: parseInt(sliderElement.data('pagination-dot-size')) || 8,
                paginationDotGap: parseInt(sliderElement.data('pagination-dot-gap')) || 10
            };
            
            TypeformQuizzes.init(config);
        }
    });

    // Make TypeformQuizzes available globally with proper namespacing
    if (typeof window.TypeformQuizzesPlugin === 'undefined') {
        window.TypeformQuizzesPlugin = TypeformQuizzes;
    }

})(jQuery);
