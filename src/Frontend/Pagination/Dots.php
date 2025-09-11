<?php
/**
 * Pagination Dots Handler for Typeform Quizzes
 * 
 * @package Typeform_Quizzes
 * @version 1.1.0
 * @author Making The Impact LLC
 */

namespace MTI\TypeformQuizzes\Frontend\Pagination;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

/**
 * Pagination Dots Class
 * 
 * Handles pagination markup and JavaScript initialization for quiz sliders.
 */
class Dots
{
    /**
     * Generate pagination markup HTML
     * 
     * @param string $slider_id Unique slider ID
     * @return string HTML for pagination wrapper
     */
    public static function markup(string $slider_id): string
    {
        return '<div class="pagination-container">
                    <div class="swiper-pagination tfqpagination"></div>
                </div>';
    }

    /**
     * Generate pagination JavaScript initialization
     * 
     * @param string $slider_id Unique slider ID
     * @param array $swiperParams Swiper configuration parameters
     * @return string JavaScript code wrapped in <script> tags
     */
    public static function script(string $slider_id, array $swiperParams): string
    {
        // Extract pagination-specific parameters
        $paginationDotColor = $swiperParams['paginationDotColor'] ?? '#cfcfcf';
        $paginationActiveDotColor = $swiperParams['paginationActiveDotColor'] ?? '#111111';
        $paginationDotSize = $swiperParams['paginationDotSize'] ?? 8;
        $paginationDotGap = $swiperParams['paginationDotGap'] ?? 10;
        $colsDesktop = $swiperParams['colsDesktop'] ?? 6;
        $colsTablet = $swiperParams['colsTablet'] ?? 3;
        $colsMobile = $swiperParams['colsMobile'] ?? 2;
        $gap = $swiperParams['gap'] ?? 20;

        // Generate the exact JavaScript that was in the original implementation
        $script = "
        <script>
        (function($) {
            'use strict';
            
            // Initialize Swiper with the exact same configuration as before
            if (typeof Swiper !== 'undefined') {
                const sliderElement = document.getElementById('" . esc_js($slider_id) . "');
                if (sliderElement && !sliderElement.swiper) {
                    const swiper = new Swiper('#' + '" . esc_js($slider_id) . "', {
                        slidesPerView: " . intval($colsDesktop) . ",
                        spaceBetween: " . intval($gap) . ",
                        loop: false,
                        slidesPerGroup: " . intval($colsDesktop) . ",
                        navigation: {
                            nextEl: '.tfqrow-arrow[data-dir=\"next\"]',
                            prevEl: '.tfqrow-arrow[data-dir=\"prev\"]',
                        },
                        pagination: {
                            el: '.swiper-pagination',
                            clickable: true,
                        },
                        breakpoints: {
                            320: {
                                slidesPerView: " . intval($colsMobile) . ",
                                spaceBetween: " . intval($gap) . ",
                                slidesPerGroup: " . intval($colsMobile) . ",
                            },
                            768: {
                                slidesPerView: " . intval($colsTablet) . ",
                                spaceBetween: " . intval($gap) . ",
                                slidesPerGroup: " . intval($colsTablet) . ",
                            },
                            1024: {
                                slidesPerView: " . intval($colsDesktop) . ",
                                spaceBetween: " . intval($gap) . ",
                                slidesPerGroup: " . intval($colsDesktop) . ",
                            },
                        },
                        on: {
                            init: function() {
                                console.log('Swiper initialized with', this.slides.length, 'slides');
                                console.log('Slides per group:', this.params.slidesPerGroup);
                                console.log('Expected pages:', Math.ceil(this.slides.length / this.params.slidesPerGroup));
                            }
                        }
                    });
                }
            }
        })(jQuery);
        </script>";

        return $script;
    }
}
