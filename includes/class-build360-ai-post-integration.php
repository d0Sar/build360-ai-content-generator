<?php
/**
 * Post/Page integration class
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Build360_AI_Post_Integration {

    /**
     * Initialize integration
     */
    public function init() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
    }

    /**
     * Add meta box to post and page edit screens
     */
    public function add_meta_box() {
        $post_types = array('post', 'page');
        foreach ($post_types as $post_type) {
            add_meta_box(
                'build360_ai_post_meta_box',
                __('Build360 AI Content Generator', 'build360-ai'),
                array($this, 'render_meta_box'),
                $post_type,
                'normal',
                'high'
            );
        }
    }

    /**
     * Render meta box content
     */
    public function render_meta_box($post) {
        include BUILD360_AI_PLUGIN_DIR . 'admin/partials/post-meta-box.php';
    }
}
