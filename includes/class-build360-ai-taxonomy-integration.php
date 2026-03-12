<?php
/**
 * Taxonomy integration class for product_cat and category edit pages
 * Handles single-term AI generation fields AND bulk term generation
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

        // Bulk actions on product_cat taxonomy list
        add_filter('bulk_actions-edit-product_cat', array($this, 'register_bulk_actions'));

        // Bulk actions on category taxonomy list
        add_filter('bulk_actions-edit-category', array($this, 'register_bulk_actions'));

        // Action Scheduler callback for processing a single term
        add_action('build360_ai_process_single_term', array($this, 'process_single_term_background'), 10, 2);

        // Cleanup callback
        add_action('build360_ai_cleanup_bulk_term_job', array($this, 'cleanup_bulk_term_job'));

        // Inject modals on taxonomy list pages
        add_action('admin_footer-edit-tags.php', array($this, 'render_bulk_modals'));
    }

    /**
     * Render AI generation fields on taxonomy edit page
     *
     * @param WP_Term $term The term object being edited.
     */
    public function render_taxonomy_fields($term) {
        include BUILD360_AI_PLUGIN_DIR . 'admin/partials/taxonomy-meta-fields.php';
    }

    /**
     * Register bulk actions on taxonomy list page
     */
    public function register_bulk_actions($bulk_actions) {
        $bulk_actions['build360_ai_generate_terms'] = __('Generate content with Build360 AI', 'build360-ai');
        return $bulk_actions;
    }

    /**
     * Render field selection + review modals on taxonomy list pages
     */
    public function render_bulk_modals() {
        $screen = get_current_screen();
        if (!$screen || !in_array($screen->taxonomy, array('product_cat', 'category'))) {
            return;
        }
        ?>
        <!-- Field Selection Modal for Bulk Term Generation -->
        <div id="build360-ai-term-field-select-modal" class="build360-bulk-modal">
            <div class="build360-bulk-modal-overlay"></div>
            <div class="build360-bulk-modal-content build360-field-select-content">
                <div class="build360-bulk-modal-header">
                    <h2><?php _e('Select Fields to Generate', 'build360-ai'); ?></h2>
                    <button type="button" class="build360-bulk-modal-close"><span class="dashicons dashicons-no-alt"></span></button>
                </div>
                <div class="build360-bulk-modal-body">
                    <p class="build360-field-select-count"></p>
                    <div class="build360-field-select-options">
                        <label>
                            <input type="checkbox" name="bulk_term_fields[]" value="description" checked>
                            <?php _e('Category Description', 'build360-ai'); ?>
                        </label>
                        <label>
                            <input type="checkbox" name="bulk_term_fields[]" value="seo_title">
                            <?php _e('SEO Title', 'build360-ai'); ?>
                        </label>
                        <label>
                            <input type="checkbox" name="bulk_term_fields[]" value="seo_description">
                            <?php _e('SEO Description', 'build360-ai'); ?>
                        </label>
                    </div>
                    <p class="build360-batch-info">
                        <span class="dashicons dashicons-info"></span>
                        <?php _e('Categories are processed in batches of 50 for reliability.', 'build360-ai'); ?>
                    </p>
                </div>
                <div class="build360-bulk-modal-footer">
                    <button type="button" class="button build360-term-field-select-cancel"><?php _e('Cancel', 'build360-ai'); ?></button>
                    <button type="button" class="button button-primary build360-term-field-select-start"><?php _e('Start Generation', 'build360-ai'); ?></button>
                </div>
            </div>
        </div>

        <!-- Review Modal for Bulk Term Generation Results -->
        <div id="build360-ai-bulk-term-review-modal" class="build360-bulk-modal">
            <div class="build360-bulk-modal-overlay"></div>
            <div class="build360-bulk-modal-content build360-review-content">
                <div class="build360-bulk-modal-header">
                    <h2><?php _e('Review Generated Content', 'build360-ai'); ?></h2>
                    <div class="build360-review-header-actions">
                        <span class="build360-review-counter"></span>
                        <button type="button" class="button button-primary build360-term-review-accept-all"><?php _e('Accept All Remaining', 'build360-ai'); ?></button>
                    </div>
                    <button type="button" class="build360-bulk-modal-close"><span class="dashicons dashicons-no-alt"></span></button>
                </div>
                <div class="build360-bulk-modal-body build360-review-body">
                    <div class="build360-review-products"></div>
                </div>
                <div class="build360-bulk-modal-footer">
                    <div class="build360-review-pagination"></div>
                    <button type="button" class="button build360-term-review-close"><?php _e('Close', 'build360-ai'); ?></button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Process a single term in the background (Action Scheduler callback)
     *
     * @param string $job_id
     * @param int $term_id
     */
    public function process_single_term_background($job_id, $term_id) {
        $job_data = get_option('build360_ai_bulk_term_job_' . $job_id);
        if (!$job_data || !is_array($job_data)) {
            return;
        }

        // Bail if cancelled or errored
        if ($job_data['status'] === 'cancelled' || !empty($job_data['error'])) {
            return;
        }

        $term = get_term($term_id);
        if (!$term || is_wp_error($term)) {
            $this->update_term_job_status($job_id, $term_id, 'failed');
            $this->maybe_finalize_term_job($job_id);
            return;
        }

        // Skip if this term was already processed (guard against duplicate scheduled actions)
        // Allow 'failed' to retry — duplicates can recover fields that timed out
        $job_data = get_option('build360_ai_bulk_term_job_' . $job_id);
        if (isset($job_data['terms'][$term_id]) && in_array($job_data['terms'][$term_id]['status'], array('completed', 'processing'), true)) {
            return;
        }

        // Mark as processing
        if (isset($job_data['terms'][$term_id])) {
            $job_data['terms'][$term_id]['status'] = 'processing';
            update_option('build360_ai_bulk_term_job_' . $job_id, $job_data, false);
        }

        $agent_id = $job_data['agent_id'];
        $fields = $job_data['fields'];
        $fields_succeeded = 0;

        $generator = new Build360_AI_Generator();

        foreach ($fields as $field) {
            // Re-check cancellation between fields
            $job_data = get_option('build360_ai_bulk_term_job_' . $job_id);
            if ($job_data && ($job_data['status'] === 'cancelled' || !empty($job_data['error']))) {
                break;
            }

            try {
                $result = $generator->generate_term_content_with_agent($term, $field, $agent_id);

                if (!is_wp_error($result)) {
                    $content = $this->extract_content_from_response($result, $field);

                    if ($content !== null) {
                        // Store as preview in custom table
                        $preview_store = new Build360_AI_Preview_Store();
                        $preview_store->save($job_id, $term_id, 'term', $field, $content);

                        $job_data = get_option('build360_ai_bulk_term_job_' . $job_id);
                        if (isset($job_data['terms'][$term_id])) {
                            $job_data['terms'][$term_id]['field_statuses'][$field] = 'completed';
                        }
                        update_option('build360_ai_bulk_term_job_' . $job_id, $job_data, false);
                        $fields_succeeded++;
                    } else {
                        $job_data = get_option('build360_ai_bulk_term_job_' . $job_id);
                        if (isset($job_data['terms'][$term_id])) {
                            $job_data['terms'][$term_id]['field_statuses'][$field] = 'failed';
                        }
                        update_option('build360_ai_bulk_term_job_' . $job_id, $job_data, false);
                    }
                } else {
                    $error_msg = $result->get_error_message();

                    // Token exhaustion — stop entire job
                    if (strpos($error_msg, 'nsufficient token') !== false || strpos($error_msg, '403') !== false) {
                        $job_data = get_option('build360_ai_bulk_term_job_' . $job_id);
                        if (isset($job_data['terms'][$term_id])) {
                            $job_data['terms'][$term_id]['field_statuses'][$field] = 'failed';
                        }
                        $job_data['error'] = 'insufficient_tokens';
                        $job_data['error_message'] = __('Token balance exhausted. Please purchase more tokens to continue.', 'build360-ai');
                        update_option('build360_ai_bulk_term_job_' . $job_id, $job_data, false);
                        break;
                    }

                    $job_data = get_option('build360_ai_bulk_term_job_' . $job_id);
                    if (isset($job_data['terms'][$term_id])) {
                        $job_data['terms'][$term_id]['field_statuses'][$field] = 'failed';
                    }
                    update_option('build360_ai_bulk_term_job_' . $job_id, $job_data, false);
                }
            } catch (Exception $e) {
                $job_data = get_option('build360_ai_bulk_term_job_' . $job_id);
                if (isset($job_data['terms'][$term_id])) {
                    $job_data['terms'][$term_id]['field_statuses'][$field] = 'failed';
                }
                update_option('build360_ai_bulk_term_job_' . $job_id, $job_data, false);
            }
        }

        $final_status = $fields_succeeded > 0 ? 'completed' : 'failed';
        $this->update_term_job_status($job_id, $term_id, $final_status);
        $this->maybe_finalize_term_job($job_id);
    }

    /**
     * Extract content from API response
     */
    private function extract_content_from_response($api_response, $field) {
        if (is_wp_error($api_response)) {
            return null;
        }

        if (is_string($api_response)) {
            return $api_response;
        }

        if (is_array($api_response)) {
            $content_map = $api_response;
            if (isset($api_response['success']) && $api_response['success'] === true && isset($api_response['data']) && is_array($api_response['data'])) {
                $content_map = $api_response['data'];
            }

            if (isset($content_map[$field]) && is_string($content_map[$field])) {
                return $content_map[$field];
            }

            $alt_keys = array(
                'description' => array('content', 'full_description', 'description'),
                'seo_title' => array('seo_title', 'meta_title', 'title'),
                'seo_description' => array('seo_description', 'meta_description', 'metadesc'),
            );

            if (isset($alt_keys[$field])) {
                foreach ($alt_keys[$field] as $alt_key) {
                    if (isset($content_map[$alt_key]) && is_string($content_map[$alt_key])) {
                        return $content_map[$alt_key];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Update a single term's status within a bulk job
     */
    private function update_term_job_status($job_id, $term_id, $status) {
        $job_data = get_option('build360_ai_bulk_term_job_' . $job_id);
        if (!$job_data || !is_array($job_data)) {
            return;
        }

        if (isset($job_data['terms'][$term_id])) {
            $job_data['terms'][$term_id]['status'] = $status;
        }

        $job_data['completed']++;
        if ($status === 'completed') {
            $job_data['succeeded']++;
        } else {
            $job_data['failed']++;
        }

        update_option('build360_ai_bulk_term_job_' . $job_id, $job_data, false);
    }

    /**
     * Schedule the next batch of pending terms
     */
    public function schedule_next_term_batch($job_id) {
        $job_data = get_option('build360_ai_bulk_term_job_' . $job_id);
        if (!$job_data || !is_array($job_data)) {
            return 0;
        }

        if ($job_data['status'] === 'cancelled' || !empty($job_data['error'])) {
            return 0;
        }

        $pending_ids = array();
        foreach ($job_data['terms'] as $tid => $tdata) {
            if ($tdata['status'] === 'pending') {
                $pending_ids[] = $tid;
            }
            if (count($pending_ids) >= 50) {
                break;
            }
        }

        if (empty($pending_ids)) {
            return 0;
        }

        $delay = 0;
        foreach ($pending_ids as $term_id) {
            if (function_exists('as_schedule_single_action')) {
                as_schedule_single_action(
                    time() + $delay,
                    'build360_ai_process_single_term',
                    array($job_id, $term_id),
                    'build360-ai'
                );
            }
            $delay += 2;
        }

        return count($pending_ids);
    }

    /**
     * Check if all terms are done and finalize or schedule next batch
     */
    private function maybe_finalize_term_job($job_id) {
        $job_data = get_option('build360_ai_bulk_term_job_' . $job_id);
        if (!$job_data || !is_array($job_data)) {
            return;
        }

        // Already finalized — don't reschedule anything
        if ($job_data['status'] === 'completed' || $job_data['status'] === 'cancelled') {
            return;
        }

        $pending_count = 0;
        foreach ($job_data['terms'] as $tid => $tdata) {
            if ($tdata['status'] === 'pending') {
                $pending_count++;
            }
        }

        if ($job_data['completed'] >= $job_data['total']) {
            $job_data['status'] = 'completed';
            $job_data['completed_at'] = current_time('mysql');
            update_option('build360_ai_bulk_term_job_' . $job_id, $job_data, false);
        } elseif ($pending_count > 0) {
            $processing_count = 0;
            foreach ($job_data['terms'] as $tid => $tdata) {
                if ($tdata['status'] === 'processing') {
                    $processing_count++;
                }
            }
            if ($processing_count === 0) {
                $this->schedule_next_term_batch($job_id);
            }
        }
    }

    /**
     * Cleanup bulk term job data
     */
    public function cleanup_bulk_term_job($job_id) {
        // Clean up all previews for this job with 1 query
        $preview_store = new Build360_AI_Preview_Store();
        $preview_store->delete_job($job_id);

        delete_option('build360_ai_bulk_term_job_' . $job_id);

        $users = get_users(array(
            'meta_key' => '_build360_ai_active_bulk_term_job',
            'meta_value' => $job_id,
        ));
        foreach ($users as $user) {
            delete_user_meta($user->ID, '_build360_ai_active_bulk_term_job');
        }
    }

    /**
     * Apply generated content to a term field
     *
     * @param int $term_id
     * @param string $field
     * @param string $content
     */
    public function apply_term_field_content($term_id, $field, $content) {
        switch ($field) {
            case 'description':
                wp_update_term($term_id, get_term($term_id)->taxonomy, array(
                    'description' => wp_kses_post($content),
                ));
                break;
            case 'seo_title':
                $sanitized = sanitize_text_field($content);
                // Yoast - all 3 storage locations
                update_term_meta($term_id, 'wpseo_title', $sanitized);
                $this->update_yoast_taxonomy_meta($term_id, 'wpseo_title', $sanitized);
                $this->update_yoast_indexable_term($term_id, 'title', $sanitized);
                // RankMath
                update_term_meta($term_id, 'rank_math_title', $sanitized);
                // SEOPress
                update_term_meta($term_id, '_seopress_titles_title', $sanitized);
                break;
            case 'seo_description':
                $sanitized = wp_kses_post($content);
                // Yoast - all 3 storage locations
                update_term_meta($term_id, 'wpseo_desc', $sanitized);
                $this->update_yoast_taxonomy_meta($term_id, 'wpseo_desc', $sanitized);
                $this->update_yoast_indexable_term($term_id, 'description', $sanitized);
                // RankMath
                update_term_meta($term_id, 'rank_math_description', $sanitized);
                // SEOPress
                update_term_meta($term_id, '_seopress_titles_desc', $sanitized);
                break;
        }
    }

    /**
     * Update Yoast wpseo_taxonomy_meta option (the primary storage Yoast reads on edit pages)
     *
     * @param int    $term_id
     * @param string $key     'wpseo_title' or 'wpseo_desc'
     * @param string $value
     */
    private function update_yoast_taxonomy_meta($term_id, $key, $value) {
        $term = get_term($term_id);
        if (!$term || is_wp_error($term)) {
            return;
        }

        $taxonomy = $term->taxonomy;
        $tax_meta = get_option('wpseo_taxonomy_meta', array());

        if (!isset($tax_meta[$taxonomy])) {
            $tax_meta[$taxonomy] = array();
        }
        if (!isset($tax_meta[$taxonomy][$term_id])) {
            $tax_meta[$taxonomy][$term_id] = array();
        }

        $tax_meta[$taxonomy][$term_id][$key] = $value;

        update_option('wpseo_taxonomy_meta', $tax_meta, true);
    }

    /**
     * Update Yoast SEO indexable table directly for a term
     *
     * @param int    $term_id
     * @param string $column  'title' or 'description'
     * @param string $value
     */
    private function update_yoast_indexable_term($term_id, $column, $value) {
        global $wpdb;

        $table = $wpdb->prefix . 'yoast_indexable';

        // Check table exists
        $table_check = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        if ($table_check !== $table) {
            return;
        }

        // Only allow known columns
        $allowed = array('title', 'description');
        if (!in_array($column, $allowed, true)) {
            return;
        }

        $wpdb->update(
            $table,
            array($column => $value),
            array(
                'object_id'   => $term_id,
                'object_type' => 'term',
            ),
            array('%s'),
            array('%d', '%s')
        );
    }
}
