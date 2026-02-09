<?php
/**
 * Plugin Name: Build360 AI Content Generator
 * Plugin URI: https://build360.ai
 * Description: AI-powered content generation for WooCommerce products, categories, posts, and pages
 * Version: 1.1.0
 * Author: Build360
 * Author URI: https://build360.ai
 * Text Domain: build360-ai
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 7.9
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('BUILD360_AI_VERSION', '1.1.0');
define('BUILD360_AI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BUILD360_AI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BUILD360_AI_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include required files
require_once BUILD360_AI_PLUGIN_DIR . 'includes/class-build360-ai.php';

// Load plugin textdomain
function build360_ai_load_textdomain() {
    load_plugin_textdomain('build360-ai', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'build360_ai_load_textdomain');

// Initialize the plugin
function build360_ai_init() {
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'build360_ai_woocommerce_missing_notice');
        return;
    }

    // Initialize the main plugin class
    $build360_ai = new Build360_AI();
    $build360_ai->init();
}
add_action('plugins_loaded', 'build360_ai_init');

// WooCommerce missing notice
function build360_ai_woocommerce_missing_notice() {
    ?>
    <div class="error">
        <p><?php _e('Build360 AI Content Generator requires WooCommerce to be installed and active.', 'build360-ai'); ?></p>
    </div>
    <?php
}

// Activation hook
register_activation_hook(__FILE__, 'build360_ai_activate');
function build360_ai_activate() {
    // Create necessary database tables and options
    add_option('build360_ai_api_key', '');
    add_option('build360_ai_domain', '');
    add_option('build360_ai_text_style', 'professional');
    add_option('build360_ai_max_product_text', '250');
    add_option('build360_ai_max_product_desc_text', '300');
    add_option('build360_ai_max_blog_text', '350');

    // Content type options
    add_option('build360_ai_content_types', [
        'product' => [
            'description' => true,
            'short_description' => true,
            'seo_keyword' => true,
            'seo_title' => true,
            'seo_meta_description' => true,
            'image_alt' => true
        ],
        'product_cat' => [
            'description' => true,
            'short_description' => true,
            'seo_keyword' => true,
            'seo_title' => true,
            'seo_meta_description' => true,
            'image_alt' => true
        ],
        'post' => [
            'seo_keyword' => true,
            'seo_title' => true,
            'seo_meta_description' => true,
            'image_alt' => true
        ],
        'page' => [
            'seo_title' => true,
            'seo_meta_description' => true
        ],
        'image' => [
            'alt_text' => true,
            'title' => true,
            'caption' => true,
            'description' => true
        ]
    ]);

    // Flush rewrite rules
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'build360_ai_deactivate');
function build360_ai_deactivate() {
    // Clean up if needed
    flush_rewrite_rules();
}

// Uninstall hook
register_uninstall_hook(__FILE__, 'build360_ai_uninstall');
function build360_ai_uninstall() {
    // Clean up options
    delete_option('build360_ai_api_key');
    delete_option('build360_ai_domain');
    delete_option('build360_ai_text_style');
    delete_option('build360_ai_max_product_text');
    delete_option('build360_ai_max_product_desc_text');
    delete_option('build360_ai_max_blog_text');
    delete_option('build360_ai_content_types');
} 
