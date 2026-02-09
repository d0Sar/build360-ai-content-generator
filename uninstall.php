<?php
// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
$options = array(
    'build360_ai_api_key',
    'build360_ai_domain',
    'build360_ai_model',
    'build360_ai_text_style',
    'build360_ai_content_generated',
    'build360_ai_products_enhanced',
    'build360_ai_recent_activity'
);

// Get all maximum length options
global $wpdb;
$max_length_options = $wpdb->get_col(
    "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'build360_ai_max_length_%'"
);

$options = array_merge($options, $max_length_options);

// Delete all plugin options
foreach ($options as $option) {
    delete_option($option);
}

// Delete post meta
$wpdb->delete(
    $wpdb->postmeta,
    array('meta_key' => '_build360_ai_last_generated'),
    array('%s')
); 