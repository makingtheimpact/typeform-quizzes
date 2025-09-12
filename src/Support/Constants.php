<?php
/**
 * Plugin Constants
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
 * Constants Class
 * 
 * Contains all plugin constants.
 */
final class Constants
{
    /** @var int Maximum number of quizzes that can be displayed */
    const MAX_QUIZZES_LIMIT = 50;
    
    /** @var string Nonce action for AJAX requests */
    const NONCE_ACTION = 'tfq_purge_nonce_action';
    
    /** @var string Nonce name for AJAX requests */
    const NONCE_NAME = 'tfq_purge_nonce';
}
