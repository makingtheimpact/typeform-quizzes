<?php
/**
 * Template Helper Class
 * 
 * @package Typeform_Quizzes
 * @version 1.1.0
 * @author Making The Impact LLC
 */

namespace MTI\TypeformQuizzes\Support;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

/**
 * Template Helper Class
 * 
 * Handles template rendering for the plugin.
 */
final class Template
{
    /**
     * Render a template file with variables
     * 
     * @param string $relPath Relative path to template file
     * @param array $vars Variables to pass to template
     * @return string Rendered template content
     */
    public static function render(string $relPath, array $vars = []): string
    {
        $file = rtrim(TFQ_PLUGIN_DIR, '/\\') . '/templates/' . ltrim($relPath, '/\\');
        
        if (!is_file($file)) {
            return '';
        }
        
        extract($vars, EXTR_SKIP);
        ob_start();
        include $file;
        return ob_get_clean();
    }
}
