<?php
/**
 * Taxonomy integration class for product_cat and category edit pages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Build360_AI_Taxonomy_Integration {

    /**
     * Initialize integration
     */
    public function init() {
        // Product categories (WooCommerce)
        add_action('product_cat_edit_form_fields', array($this, 'render_taxonomy_fields'), 100, 1);

        // Post categories (WordPress)
        add_action('category_edit_form_fields', array($this, 'render_taxonomy_fields'), 100, 1);
    }

    /**
     * Render AI generation fields on taxonomy edit page
     *
     * @param WP_Term $term The term object being edited.
     */
    public function render_taxonomy_fields($term) {
        include BUILD360_AI_PLUGIN_DIR . 'admin/partials/taxonomy-meta-fields.php';
    }
}
