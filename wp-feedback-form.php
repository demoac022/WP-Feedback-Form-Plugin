<?php
/**
 * Plugin Name: WP Feedback Form
 * Plugin URI: https://github.com/demoac022/WP-Feedback-Form-Plugin
 * Description: A customizable feedback form plugin with admin panel and shortcode support
 * Version: 1.0.0
 * Author: demoac022
 * Author URI: https://github.com/demoac022
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-feedback-form
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('WP_FEEDBACK_FORM_VERSION', '1.0.0');
define('WP_FEEDBACK_FORM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_FEEDBACK_FORM_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once WP_FEEDBACK_FORM_PLUGIN_DIR . 'public/feedback-display.php';
require_once WP_FEEDBACK_FORM_PLUGIN_DIR . 'admin/feedback-admin.php';

// Activation Hook
register_activation_hook(__FILE__, 'wp_feedback_form_activate');

function wp_feedback_form_activate() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    // Create feedback table
    $table_name = $wpdb->prefix . 'feedback_submissions';
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        email varchar(100) NOT NULL,
        rating int(1) NOT NULL,
        message text NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Deactivation Hook
register_deactivation_hook(__FILE__, 'wp_feedback_form_deactivate');

function wp_feedback_form_deactivate() {
    // Cleanup tasks if needed
}

// Enqueue scripts and styles
function wp_feedback_form_enqueue_scripts() {
    wp_enqueue_style('wp-feedback-form-style', 
        WP_FEEDBACK_FORM_PLUGIN_URL . 'css/style.css',
        array(),
        WP_FEEDBACK_FORM_VERSION
    );
    
    wp_enqueue_script('wp-feedback-form-script',
        WP_FEEDBACK_FORM_PLUGIN_URL . 'js/script.js',
        array('jquery'),
        WP_FEEDBACK_FORM_VERSION,
        true
    );
    
    wp_localize_script('wp-feedback-form-script', 'wpFeedbackForm', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wp_feedback_form_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'wp_feedback_form_enqueue_scripts');
