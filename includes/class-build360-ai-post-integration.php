<?php
/**
 * Post/Page integration class
 * Handles single post/page AI generation metabox AND bulk page generation
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

        // Bulk actions on page list
        add_filter('bulk_actions-edit-page', array($this, 'register_page_bulk_actions'));

        // Action Scheduler callback for processing a single page
        add_action('build360_ai_process_single_page', array($this, 'process_single_page_background'), 10, 2);

        // Cleanup callback
        add_action('build360_ai_cleanup_bulk_page_job', array($this, 'cleanup_bulk_page_job'));

        // Inject modals on page list page
        add_action('admin_footer-edit.php', array($this, 'render_page_bulk_modals'));
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

    /**
     * Register bulk actions on page list
     */
    public function register_page_bulk_actions($actions) {
        $actions['build360_ai_generate_pages'] = __('Generate content with Build360 AI', 'build360-ai');
        return $actions;
    }

    /**
     * Render field selection + review modals on page list page
     */
    public function render_page_bulk_modals() {
        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== 'page') {
            return;
        }
        ?>
        <!-- Field Selection Modal for Bulk Page Generation -->
        <div id="build360-ai-page-field-select-modal" class="build360-bulk-modal">
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
                            <input type="checkbox" name="bulk_page_fields[]" value="seo_title" checked>
                            <?php _e('SEO Title', 'build360-ai'); ?>
                        </label>
                        <label>
                            <input type="checkbox" name="bulk_page_fields[]" value="seo_description">
                            <?php _e('SEO Description', 'build360-ai'); ?>
                        </label>
                    </div>
                    <p class="build360-batch-info">
                        <span class="dashicons dashicons-info"></span>
                        <?php _e('Pages are processed in batches of 50 for reliability.', 'build360-ai'); ?>
                    </p>
                </div>
                <div class="build360-bulk-modal-footer">
                    <button type="button" class="button build360-page-field-select-cancel"><?php _e('Cancel', 'build360-ai'); ?></button>
                    <button type="button" class="button button-primary build360-page-field-select-start"><?php _e('Start Generation', 'build360-ai'); ?></button>
                </div>
            </div>
        </div>

        <!-- Review Modal for Bulk Page Generation Results -->
        <div id="build360-ai-bulk-page-review-modal" class="build360-bulk-modal">
            <div class="build360-bulk-modal-overlay"></div>
            <div class="build360-bulk-modal-content build360-review-content">
                <div class="build360-bulk-modal-header">
                    <h2><?php _e('Review Generated Content', 'build360-ai'); ?></h2>
                    <div class="build360-review-header-actions">
                        <span class="build360-review-counter"></span>
                        <button type="button" class="button button-primary build360-page-review-accept-all"><?php _e('Accept All Remaining', 'build360-ai'); ?></button>
                    </div>
                    <button type="button" class="build360-bulk-modal-close"><span class="dashicons dashicons-no-alt"></span></button>
                </div>
                <div class="build360-bulk-modal-body build360-review-body">
                    <div class="build360-review-products"></div>
                </div>
                <div class="build360-bulk-modal-footer">
                    <div class="build360-review-pagination"></div>
                    <button type="button" class="button build360-page-review-close"><?php _e('Close', 'build360-ai'); ?></button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Process a single page in the background (Action Scheduler callback)
     *
     * @param string $job_id
     * @param int $page_id
     */
    public function process_single_page_background($job_id, $page_id) {
        $job_data = get_option('build360_ai_bulk_page_job_' . $job_id);
        if (!$job_data || !is_array($job_data)) {
            return;
        }

        // Bail if cancelled or errored
        if ($job_data['status'] === 'cancelled' || !empty($job_data['error'])) {
            return;
        }

        $page = get_post($page_id);
        if (!$page || $page->post_type !== 'page') {
            $this->update_page_job_status($job_id, $page_id, 'failed');
            $this->maybe_finalize_page_job($job_id);
            return;
        }

        // Skip if this page was already processed (guard against duplicate scheduled actions)
        // Allow 'failed' to retry — duplicates can recover fields that timed out
        $job_data = get_option('build360_ai_bulk_page_job_' . $job_id);
        if (isset($job_data['pages'][$page_id]) && in_array($job_data['pages'][$page_id]['status'], array('completed', 'processing'), true)) {
            return;
        }

        // Mark as processing
        if (isset($job_data['pages'][$page_id])) {
            $job_data['pages'][$page_id]['status'] = 'processing';
            update_option('build360_ai_bulk_page_job_' . $job_id, $job_data, false);
        }

        $agent_id = $job_data['agent_id'];
        $fields = $job_data['fields'];
        $fields_succeeded = 0;

        $generator = new Build360_AI_Generator();

        $page_data = array(
            'name'        => $page->post_title,
            'description' => $page->post_content,
        );

        foreach ($fields as $field) {
            // Re-check cancellation between fields
            $job_data = get_option('build360_ai_bulk_page_job_' . $job_id);
            if ($job_data && ($job_data['status'] === 'cancelled' || !empty($job_data['error']))) {
                break;
            }

            try {
                $result = $generator->generate_content_field('page', $field, $page_data, $agent_id);

                if (!is_wp_error($result)) {
                    $content = $this->extract_content_from_response($result, $field);

                    if ($content !== null) {
                        // Store as preview in custom table
                        $preview_store = new Build360_AI_Preview_Store();
                        $preview_store->save($job_id, $page_id, 'page', $field, $content);

                        $job_data = get_option('build360_ai_bulk_page_job_' . $job_id);
                        if (isset($job_data['pages'][$page_id])) {
                            $job_data['pages'][$page_id]['field_statuses'][$field] = 'completed';
                        }
                        update_option('build360_ai_bulk_page_job_' . $job_id, $job_data, false);
                        $fields_succeeded++;
                    } else {
                        $job_data = get_option('build360_ai_bulk_page_job_' . $job_id);
                        if (isset($job_data['pages'][$page_id])) {
                            $job_data['pages'][$page_id]['field_statuses'][$field] = 'failed';
                        }
                        update_option('build360_ai_bulk_page_job_' . $job_id, $job_data, false);
                    }
                } else {
                    $error_msg = $result->get_error_message();

                    // Token exhaustion — stop entire job
                    if (strpos($error_msg, 'nsufficient token') !== false || strpos($error_msg, '403') !== false) {
                        $job_data = get_option('build360_ai_bulk_page_job_' . $job_id);
                        if (isset($job_data['pages'][$page_id])) {
                            $job_data['pages'][$page_id]['field_statuses'][$field] = 'failed';
                        }
                        $job_data['error'] = 'insufficient_tokens';
                        $job_data['error_message'] = __('Token balance exhausted. Please purchase more tokens to continue.', 'build360-ai');
                        update_option('build360_ai_bulk_page_job_' . $job_id, $job_data, false);
                        break;
                    }

                    $job_data = get_option('build360_ai_bulk_page_job_' . $job_id);
                    if (isset($job_data['pages'][$page_id])) {
                        $job_data['pages'][$page_id]['field_statuses'][$field] = 'failed';
                    }
                    update_option('build360_ai_bulk_page_job_' . $job_id, $job_data, false);
                }
            } catch (Exception $e) {
                $job_data = get_option('build360_ai_bulk_page_job_' . $job_id);
                if (isset($job_data['pages'][$page_id])) {
                    $job_data['pages'][$page_id]['field_statuses'][$field] = 'failed';
                }
                update_option('build360_ai_bulk_page_job_' . $job_id, $job_data, false);
            }
        }

        $final_status = $fields_succeeded > 0 ? 'completed' : 'failed';
        $this->update_page_job_status($job_id, $page_id, $final_status);
        $this->maybe_finalize_page_job($job_id);
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
     * Update a single page's status within a bulk job
     */
    private function update_page_job_status($job_id, $page_id, $status) {
        $job_data = get_option('build360_ai_bulk_page_job_' . $job_id);
        if (!$job_data || !is_array($job_data)) {
            return;
        }

        if (isset($job_data['pages'][$page_id])) {
            $job_data['pages'][$page_id]['status'] = $status;
        }

        $job_data['completed']++;
        if ($status === 'completed') {
            $job_data['succeeded']++;
        } else {
            $job_data['failed']++;
        }

        update_option('build360_ai_bulk_page_job_' . $job_id, $job_data, false);
    }

    /**
     * Schedule the next batch of pending pages
     */
    public function schedule_next_page_batch($job_id) {
        $job_data = get_option('build360_ai_bulk_page_job_' . $job_id);
        if (!$job_data || !is_array($job_data)) {
            return 0;
        }

        if ($job_data['status'] === 'cancelled' || !empty($job_data['error'])) {
            return 0;
        }

        $pending_ids = array();
        foreach ($job_data['pages'] as $pid => $pdata) {
            if ($pdata['status'] === 'pending') {
                $pending_ids[] = $pid;
            }
            if (count($pending_ids) >= 50) {
                break;
            }
        }

        if (empty($pending_ids)) {
            return 0;
        }

        $delay = 0;
        foreach ($pending_ids as $page_id) {
            if (function_exists('as_schedule_single_action')) {
                as_schedule_single_action(
                    time() + $delay,
                    'build360_ai_process_single_page',
                    array($job_id, $page_id),
                    'build360-ai'
                );
            }
            $delay += 2;
        }

        return count($pending_ids);
    }

    /**
     * Check if all pages are done and finalize or schedule next batch
     */
    private function maybe_finalize_page_job($job_id) {
        $job_data = get_option('build360_ai_bulk_page_job_' . $job_id);
        if (!$job_data || !is_array($job_data)) {
            return;
        }

        // Already finalized — don't reschedule anything
        if ($job_data['status'] === 'completed' || $job_data['status'] === 'cancelled') {
            return;
        }

        $pending_count = 0;
        foreach ($job_data['pages'] as $pid => $pdata) {
            if ($pdata['status'] === 'pending') {
                $pending_count++;
            }
        }

        if ($job_data['completed'] >= $job_data['total']) {
            $job_data['status'] = 'completed';
            $job_data['completed_at'] = current_time('mysql');
            update_option('build360_ai_bulk_page_job_' . $job_id, $job_data, false);
        } elseif ($pending_count > 0) {
            $processing_count = 0;
            foreach ($job_data['pages'] as $pid => $pdata) {
                if ($pdata['status'] === 'processing') {
                    $processing_count++;
                }
            }
            if ($processing_count === 0) {
                $this->schedule_next_page_batch($job_id);
            }
        }
    }

    /**
     * Cleanup bulk page job data
     */
    public function cleanup_bulk_page_job($job_id) {
        // Clean up all previews for this job with 1 query
        $preview_store = new Build360_AI_Preview_Store();
        $preview_store->delete_job($job_id);

        delete_option('build360_ai_bulk_page_job_' . $job_id);

        $users = get_users(array(
            'meta_key' => '_build360_ai_active_bulk_page_job',
            'meta_value' => $job_id,
        ));
        foreach ($users as $user) {
            delete_user_meta($user->ID, '_build360_ai_active_bulk_page_job');
        }
    }

    /**
     * Apply generated content to a page field
     *
     * @param int $page_id
     * @param string $field
     * @param string $content
     */
    public function apply_page_field_content($page_id, $field, $content) {
        switch ($field) {
            case 'seo_title':
                $sanitized = sanitize_text_field($content);
                // Yoast
                update_post_meta($page_id, '_yoast_wpseo_title', $sanitized);
                $this->update_yoast_indexable_post($page_id, 'title', $sanitized);
                // RankMath
                update_post_meta($page_id, 'rank_math_title', $sanitized);
                // SEOPress
                update_post_meta($page_id, '_seopress_titles_title', $sanitized);
                // AIOSEO
                update_post_meta($page_id, '_aioseo_title', $sanitized);
                break;
            case 'seo_description':
                $sanitized = wp_kses_post($content);
                // Yoast
                update_post_meta($page_id, '_yoast_wpseo_metadesc', $sanitized);
                $this->update_yoast_indexable_post($page_id, 'description', $sanitized);
                // RankMath
                update_post_meta($page_id, 'rank_math_description', $sanitized);
                // SEOPress
                update_post_meta($page_id, '_seopress_titles_desc', $sanitized);
                // AIOSEO
                update_post_meta($page_id, '_aioseo_description', $sanitized);
                break;
        }
    }

    /**
     * Update Yoast SEO indexable table directly for a post/page
     *
     * @param int    $page_id
     * @param string $column  'title' or 'description'
     * @param string $value
     */
    private function update_yoast_indexable_post($page_id, $column, $value) {
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
                'object_id'   => $page_id,
                'object_type' => 'post',
            ),
            array('%s'),
            array('%d', '%s')
        );
    }
}
