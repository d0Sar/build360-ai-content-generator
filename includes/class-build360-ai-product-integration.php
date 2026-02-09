<?php
/**
 * Product integration class for WooCommerce integration
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
exit;
}

/**
 * Class Build360_AI_Product_Integration
 *
 * Handles integration with WooCommerce products.
 */
class Build360_AI_Product_Integration {
/**
 * Settings instance
 *
 * @var Build360_AI_Settings
 */
private $settings;

/**
 * Constructor
 */
public function __construct() {
    $this->settings = new Build360_AI_Settings();
}

/**
 * Initialize integration
 */
public function init() {
    // Add meta box to product edit page
    add_action('add_meta_boxes', array($this, 'add_meta_box'));

    // Add bulk action
    add_filter('bulk_actions-edit-product', array($this, 'register_bulk_actions'));
    add_filter('handle_bulk_actions-edit-product', array($this, 'handle_bulk_actions'), 10, 3);

    // Add admin notice for bulk action results
    add_action('admin_notices', array($this, 'bulk_action_admin_notice'));

    // Add generate button to product list
    add_filter('post_row_actions', array($this, 'add_row_action'), 10, 2);
    add_filter('product_row_actions', array($this, 'add_row_action'), 10, 2);

    // Handle single product generation
    add_action('admin_init', array($this, 'handle_single_generation'));

    // Register Action Scheduler hooks for background bulk processing
    add_action('build360_ai_process_single_product', array($this, 'process_single_product_background'), 10, 2);
    add_action('build360_ai_cleanup_bulk_job', array($this, 'cleanup_bulk_job'), 10, 1);
}

/**
 * Add meta box to product edit page
 */
public function add_meta_box() {
    add_meta_box(
        'build360_ai_product_meta_box',
        __('Build360 AI Content Generator', 'build360-ai'),
        array($this, 'render_meta_box'),
        'product',
        'normal',
        'high'
    );
}

/**
 * Render meta box content
 */
public function render_meta_box($post) {
    $product = wc_get_product($post);
    if (!$product) {
        return;
    }

    $last_generated = get_post_meta($post->ID, '_build360_ai_last_generated', true);
    $settings = Build360_AI_Settings::get_settings();
    wp_nonce_field('build360_ai_generate_content', 'build360_ai_meta_box_nonce');
    ?>
    <div class="build360-ai-meta-box">
        <div class="build360-ai-meta-box-header">
            <?php if ($last_generated): ?>
                <p class="description">
                    <?php printf(
                        __('Last generated: %s', 'build360-ai'),
                        date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_generated))
                    ); ?>
                </p>
            <?php endif; ?>
        </div>

        <div class="build360-ai-meta-box-content">
            <div class="build360-ai-field-group">
                <label><?php _e('Fields to Generate:', 'build360-ai'); ?></label>
                <div class="build360-ai-field-options">
                    <label>
                        <input type="checkbox" name="build360_ai_fields[]" value="description" checked>
                        <?php _e('Product Description', 'build360-ai'); ?>
                    </label>
                    <label>
                        <input type="checkbox" name="build360_ai_fields[]" value="short_description" checked>
                        <?php _e('Short Description', 'build360-ai'); ?>
                    </label>
                    <label>
                        <input type="checkbox" name="build360_ai_fields[]" value="seo_title">
                        <?php _e('SEO Title', 'build360-ai'); ?>
                    </label>
                    <label>
                        <input type="checkbox" name="build360_ai_fields[]" value="seo_description">
                        <?php _e('SEO Description', 'build360-ai'); ?>
                    </label>
                    <?php if ($product->get_image_id()): ?>
                        <label>
                            <input type="checkbox" name="build360_ai_fields[]" value="image_alt">
                            <?php _e('Image Alt Text', 'build360-ai'); ?>
                        </label>
                    <?php endif; ?>
                </div>
            </div>

            <div class="build360-ai-field-group">
                <label for="build360_ai_keywords"><?php _e('Keywords (comma separated):', 'build360-ai'); ?></label>
                <input type="text" id="build360_ai_keywords" name="build360_ai_keywords" class="regular-text"
                        placeholder="<?php esc_attr_e('e.g., modern, stylish, durable', 'build360-ai'); ?>">
                <p class="description">
                    <?php _e('Optional keywords to help guide the content generation.', 'build360-ai'); ?>
                </p>
            </div>

            <div class="build360-ai-field-group">
                <button type="button" class="button button-primary" id="build360_ai_generate">
                    <?php _e('Generate Content', 'build360-ai'); ?>
                </button>
                <span class="spinner"></span>
            </div>

            <div class="generation-status" style="display: none;"></div>

            <div id="build360_ai_results" style="display: none;">
                <h3><?php _e('Generated Content', 'build360-ai'); ?></h3>
                <div class="build360-ai-generated-content"></div>
            </div>
        </div>
        <!-- Review Modal -->
<div id="build360-ai-review-modal" class="build360-ai-modal" style="display: none;">
<div class="modal-overlay"></div>
<div class="modal-content">
    <div class="modal-header">
        <h2><?php _e( 'Review Generated Content', 'build360-ai' ); ?></h2>
        <button type="button" class="close-modal build360-ai-review-cancel">
            <span class="dashicons dashicons-no-alt"></span>
        </button>
    </div>
    <div class="modal-body" id="build360-ai-review-modal-body">
        <!-- Generated content will be populated here by JavaScript -->
    </div>
    <div class="modal-footer">
        <button type="button" class="button build360-ai-review-cancel"><?php _e( 'Cancel', 'build360-ai' ); ?></button>
        <button type="button" class="button build360-ai-review-retry"><?php _e( 'Retry', 'build360-ai' ); ?></button>
        <button type="button" class="button button-primary build360-ai-review-approve"><?php _e( 'Approve & Update Fields', 'build360-ai' ); ?></button>
    </div>
</div>
</div>
    </div>
    <?php
}

/**
 * Register bulk actions
 */
public function register_bulk_actions($bulk_actions) {
    $bulk_actions['build360_ai_generate'] = __('Generate content with Build360 AI', 'build360-ai');
    return $bulk_actions;
}

/**
 * Add an entry to the recent activity log for this class's context.
 */
private function add_bulk_action_activity($activity_message) {
    if (empty(trim($activity_message))) return;

    $recent_activity = get_option('build360_ai_recent_activity', array());
    if (!is_array($recent_activity)) {
        $recent_activity = array();
    }

    array_unshift($recent_activity, array(
        'time' => current_time('mysql'),
        'message' => strval($activity_message)
    ));
    $recent_activity = array_slice($recent_activity, 0, 10);
    update_option('build360_ai_recent_activity', $recent_activity);
}

/**
 * Handle bulk actions - creates background job via Action Scheduler
 */
public function handle_bulk_actions($redirect_to, $doaction, $post_ids) {
    $this->add_bulk_action_activity( sprintf( __( 'handle_bulk_actions fired – received action "%s" for %d products.', 'build360-ai' ), $doaction, count( (array) $post_ids ) ) );

    if ($doaction !== 'build360_ai_generate') {
        return $redirect_to;
    }

    // Verify we have an agent assigned to products
    $agent_assignments = get_option('build360_ai_agent_assignments', array());
    $product_agent_id = null;

    foreach ($agent_assignments as $assignment) {
        if (isset($assignment['type']) && $assignment['type'] === 'product' && isset($assignment['agent_id'])) {
            $product_agent_id = $assignment['agent_id'];
            break;
        }
    }

    if (empty($product_agent_id)) {
        $this->add_bulk_action_activity(__('Bulk content generation failed: No AI Agent is assigned to the "product" content type. Please check plugin settings.', 'build360-ai'));
        $redirect_to = add_query_arg('build360_ai_error', 'no_agent', $redirect_to);
        return $redirect_to;
    }

    // Create job record
    $job_id = 'bulk_' . wp_generate_uuid4();
    $fields = array('description', 'short_description', 'seo_title', 'seo_description');

    $products_data = array();
    foreach ($post_ids as $post_id) {
        $product = wc_get_product($post_id);
        if (!$product) {
            continue;
        }
        $products_data[$post_id] = array(
            'name' => $product->get_name(),
            'status' => 'pending',
            'fields' => array(),
        );
        foreach ($fields as $field) {
            $products_data[$post_id]['fields'][$field] = array(
                'status' => 'pending',
                'preview' => '',
            );
        }
    }

    if (empty($products_data)) {
        $redirect_to = add_query_arg('build360_ai_error', 'no_valid_products', $redirect_to);
        return $redirect_to;
    }

    $job_data = array(
        'job_id' => $job_id,
        'status' => 'processing',
        'user_id' => get_current_user_id(),
        'total' => count($products_data),
        'completed' => 0,
        'succeeded' => 0,
        'failed' => 0,
        'fields' => $fields,
        'agent_id' => $product_agent_id,
        'products' => $products_data,
        'created_at' => current_time('mysql'),
    );

    update_option('build360_ai_bulk_job_' . $job_id, $job_data, false);

    // Store active job ID for this user so the JS can find it
    update_user_meta(get_current_user_id(), '_build360_ai_active_bulk_job', $job_id);

    $this->add_bulk_action_activity( sprintf( __( 'Bulk generation job %s started for %d products.', 'build360-ai' ), $job_id, count($products_data) ) );

    // Schedule one Action Scheduler task per product with 2-second stagger
    $delay = 0;
    foreach (array_keys($products_data) as $post_id) {
        if (function_exists('as_schedule_single_action')) {
            as_schedule_single_action(
                time() + $delay,
                'build360_ai_process_single_product',
                array($job_id, $post_id),
                'build360-ai'
            );
        }
        $delay += 2; // 2-second stagger between products
    }

    // Schedule cleanup after 1 hour
    if (function_exists('as_schedule_single_action')) {
        as_schedule_single_action(
            time() + 3600,
            'build360_ai_cleanup_bulk_job',
            array($job_id),
            'build360-ai'
        );
    }

    $redirect_to = add_query_arg('build360_ai_bulk_job', $job_id, $redirect_to);
    return $redirect_to;
}

/**
 * Action Scheduler callback: process a single product in the background
 *
 * @param string $job_id The bulk job identifier
 * @param int $product_id The WooCommerce product ID
 */
public function process_single_product_background($job_id, $product_id) {
    $job_data = get_option('build360_ai_bulk_job_' . $job_id);
    if (!$job_data || !is_array($job_data)) {
        error_log('[Build360 AI Bulk] Job data not found for job ' . $job_id);
        return;
    }

    // Bail if job was cancelled
    if ($job_data['status'] === 'cancelled') {
        return;
    }

    $product = wc_get_product($product_id);
    if (!$product) {
        $this->update_product_job_status($job_id, $product_id, 'failed');
        $this->maybe_finalize_job($job_id);
        return;
    }

    // Mark product as processing
    $job_data = get_option('build360_ai_bulk_job_' . $job_id);
    if (isset($job_data['products'][$product_id])) {
        $job_data['products'][$product_id]['status'] = 'processing';
        update_option('build360_ai_bulk_job_' . $job_id, $job_data, false);
    }

    $agent_id = $job_data['agent_id'];
    $fields = $job_data['fields'];
    $fields_succeeded = 0;

    // Use Generator per-field approach (proven reliable)
    $generator = new Build360_AI_Generator();

    foreach ($fields as $field) {
        // Re-check cancellation between fields
        $job_data = get_option('build360_ai_bulk_job_' . $job_id);
        if ($job_data && $job_data['status'] === 'cancelled') {
            return;
        }

        try {
            $result = $generator->generate_product_content_with_agent($product, $field, $agent_id);

            if (!is_wp_error($result)) {
                $content = $this->extract_content_from_response($result, $field);

                if ($content !== null) {
                    $this->apply_field_content($product, $product_id, $field, $content);

                    $job_data = get_option('build360_ai_bulk_job_' . $job_id);
                    if (isset($job_data['products'][$product_id]['fields'][$field])) {
                        $job_data['products'][$product_id]['fields'][$field]['status'] = 'completed';
                        $job_data['products'][$product_id]['fields'][$field]['preview'] = mb_substr(wp_strip_all_tags($content), 0, 100);
                    }
                    update_option('build360_ai_bulk_job_' . $job_id, $job_data, false);
                    $fields_succeeded++;
                } else {
                    error_log('[Build360 AI Bulk] No content extracted for product ' . $product_id . ' field ' . $field);
                    $job_data = get_option('build360_ai_bulk_job_' . $job_id);
                    if (isset($job_data['products'][$product_id]['fields'][$field])) {
                        $job_data['products'][$product_id]['fields'][$field]['status'] = 'failed';
                    }
                    update_option('build360_ai_bulk_job_' . $job_id, $job_data, false);
                }
            } else {
                error_log('[Build360 AI Bulk] WP_Error for product ' . $product_id . ' field ' . $field . ': ' . $result->get_error_message());
                $job_data = get_option('build360_ai_bulk_job_' . $job_id);
                if (isset($job_data['products'][$product_id]['fields'][$field])) {
                    $job_data['products'][$product_id]['fields'][$field]['status'] = 'failed';
                }
                update_option('build360_ai_bulk_job_' . $job_id, $job_data, false);
            }
        } catch (Exception $e) {
            error_log('[Build360 AI Bulk] Exception for product ' . $product_id . ' field ' . $field . ': ' . $e->getMessage());
            $job_data = get_option('build360_ai_bulk_job_' . $job_id);
            if (isset($job_data['products'][$product_id]['fields'][$field])) {
                $job_data['products'][$product_id]['fields'][$field]['status'] = 'failed';
            }
            update_option('build360_ai_bulk_job_' . $job_id, $job_data, false);
        }
    }

    // Save product after all fields processed
    if ($fields_succeeded > 0) {
        $product->save();
        update_post_meta($product_id, '_build360_ai_last_generated', current_time('mysql'));

        // Track usage stats
        $current_generated = get_option('build360_ai_content_generated', 0);
        update_option('build360_ai_content_generated', $current_generated + $fields_succeeded);

        $current_products = get_option('build360_ai_products_enhanced', 0);
        update_option('build360_ai_products_enhanced', $current_products + 1);

        $this->add_bulk_action_activity(sprintf(
            __('Bulk AI content generated for product: %s (ID: %d). %d fields updated.', 'build360-ai'),
            $product->get_name(),
            $product_id,
            $fields_succeeded
        ));
    }

    // Update product status in job
    $final_status = $fields_succeeded > 0 ? 'completed' : 'failed';
    $this->update_product_job_status($job_id, $product_id, $final_status);
    $this->maybe_finalize_job($job_id);
}

/**
 * Extract content from API response (various formats)
 *
 * @param mixed $api_response
 * @param string $field
 * @return string|null
 */
private function extract_content_from_response($api_response, $field) {
    if (is_wp_error($api_response)) {
        error_log('[Build360 AI Bulk] WP_Error for field ' . $field . ': ' . $api_response->get_error_message());
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

        // Direct field match
        if (isset($content_map[$field]) && is_string($content_map[$field])) {
            return $content_map[$field];
        }

        // Alternative keys by field type
        $alt_keys = array(
            'description' => array('content', 'full_description', 'description'),
            'short_description' => array('short_description', 'shortDescription', 'excerpt'),
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
 * Apply generated content to a product field
 *
 * @param WC_Product $product
 * @param int $product_id
 * @param string $field
 * @param string $content
 */
private function apply_field_content($product, $product_id, $field, $content) {
    switch ($field) {
        case 'description':
            $product->set_description(wp_kses_post($content));
            break;
        case 'short_description':
            $product->set_short_description(wp_kses_post($content));
            break;
        case 'seo_title':
            $sanitized = sanitize_text_field($content);
            update_post_meta($product_id, '_yoast_wpseo_title', $sanitized);
            update_post_meta($product_id, 'rank_math_title', $sanitized);
            update_post_meta($product_id, '_seopress_titles_title', $sanitized);
            update_post_meta($product_id, '_aioseo_title', $sanitized);
            break;
        case 'seo_description':
            $sanitized = wp_kses_post($content);
            update_post_meta($product_id, '_yoast_wpseo_metadesc', $sanitized);
            update_post_meta($product_id, 'rank_math_description', $sanitized);
            update_post_meta($product_id, '_seopress_titles_desc', $sanitized);
            update_post_meta($product_id, '_aioseo_description', $sanitized);
            break;
        case 'image_alt':
            if ($product->get_image_id()) {
                update_post_meta($product->get_image_id(), '_wp_attachment_image_alt', sanitize_text_field($content));
            }
            break;
    }
}

/**
 * Update a single product's status within a bulk job
 *
 * @param string $job_id
 * @param int $product_id
 * @param string $status completed|failed
 */
private function update_product_job_status($job_id, $product_id, $status) {
    $job_data = get_option('build360_ai_bulk_job_' . $job_id);
    if (!$job_data || !is_array($job_data)) {
        return;
    }

    if (isset($job_data['products'][$product_id])) {
        $job_data['products'][$product_id]['status'] = $status;
    }

    $job_data['completed']++;
    if ($status === 'completed') {
        $job_data['succeeded']++;
    } else {
        $job_data['failed']++;
    }

    update_option('build360_ai_bulk_job_' . $job_id, $job_data, false);
}

/**
 * Check if all products in a job are done and finalize
 *
 * @param string $job_id
 */
private function maybe_finalize_job($job_id) {
    $job_data = get_option('build360_ai_bulk_job_' . $job_id);
    if (!$job_data || !is_array($job_data)) {
        return;
    }

    if ($job_data['completed'] >= $job_data['total']) {
        $job_data['status'] = 'completed';
        $job_data['completed_at'] = current_time('mysql');
        update_option('build360_ai_bulk_job_' . $job_id, $job_data, false);

        // Clear active job user meta so progress bar doesn't show permanently
        if (!empty($job_data['user_id'])) {
            delete_user_meta($job_data['user_id'], '_build360_ai_active_bulk_job');
        }

        $this->add_bulk_action_activity(sprintf(
            __('Bulk generation job completed: %d succeeded, %d failed out of %d products.', 'build360-ai'),
            $job_data['succeeded'],
            $job_data['failed'],
            $job_data['total']
        ));
    }
}

/**
 * Cleanup bulk job data (Action Scheduler callback)
 *
 * @param string $job_id
 */
public function cleanup_bulk_job($job_id) {
    delete_option('build360_ai_bulk_job_' . $job_id);

    // Clear user meta if this was their active job
    $users = get_users(array(
        'meta_key' => '_build360_ai_active_bulk_job',
        'meta_value' => $job_id,
    ));
    foreach ($users as $user) {
        delete_user_meta($user->ID, '_build360_ai_active_bulk_job');
    }
}

/**
 * Display admin notice for bulk action results
 */
public function bulk_action_admin_notice() {
    if (!empty($_REQUEST['build360_ai_bulk_job'])) {
        $job_id = sanitize_text_field($_REQUEST['build360_ai_bulk_job']);
        echo '<div class="notice notice-info" id="build360-ai-bulk-notice"><p>';
        echo esc_html__('Build360 AI bulk content generation has been started in the background. You can track progress below.', 'build360-ai');
        echo '</p></div>';
    }

    if (!empty($_REQUEST['build360_ai_error'])) {
        $error = sanitize_text_field($_REQUEST['build360_ai_error']);
        $message = '';
        switch ($error) {
            case 'no_agent':
                $message = __('Bulk content generation failed: No AI Agent is assigned to the "product" content type. Please check plugin settings.', 'build360-ai');
                break;
            case 'no_valid_products':
                $message = __('Bulk content generation failed: No valid products were found in the selection.', 'build360-ai');
                break;
        }
        if ($message) {
            echo '<div class="notice notice-error"><p>' . esc_html($message) . '</p></div>';
        }
    }

    if (!empty($_REQUEST['build360_ai_processed'])) {
        $processed = intval($_REQUEST['build360_ai_processed']);
        printf(
            '<div class="notice notice-success"><p>' .
            _n(
                'Generated content for %s product.',
                'Generated content for %s products.',
                $processed,
                'build360-ai'
            ) . '</p></div>',
            $processed
        );
    }
}

/**
 * Add generate action to product list
 */
public function add_row_action($actions, $post) {
    if ($post->post_type === 'product') {
        $actions['build360_ai_generate'] = sprintf(
            '<a href="%s">%s</a>',
            wp_nonce_url(
                add_query_arg(
                    array(
                        'action' => 'build360_ai_generate',
                        'product_id' => $post->ID,
                    ),
                    admin_url('admin.php?page=build360-ai-products')
                ),
                'build360_ai_generate_single'
            ),
            __('Generate with AI', 'build360-ai')
        );
    }
    return $actions;
}

/**
 * Handle single product generation from list action
 */
public function handle_single_generation() {
    if (
        !isset($_GET['action']) ||
        $_GET['action'] !== 'build360_ai_generate' ||
        !isset($_GET['product_id']) ||
        !isset($_GET['_wpnonce'])
    ) {
        return;
    }

    if (!wp_verify_nonce($_GET['_wpnonce'], 'build360_ai_generate_single')) {
        wp_die(__('Security check failed.', 'build360-ai'));
    }

    $product_id = absint($_GET['product_id']);
    $product = wc_get_product($product_id);
    if (!$product) {
        wp_die(__('Product not found.', 'build360-ai'));
    }

    $generator = new Build360_AI_Generator();
    $fields = array('description', 'short_description');
    $success = false;

    // Look up agent_id from assignments (same pattern as handle_bulk_actions)
    $agent_assignments = get_option('build360_ai_agent_assignments', array());
    $product_agent_id = null;
    foreach ($agent_assignments as $assignment) {
        if (isset($assignment['type']) && $assignment['type'] === 'product' && isset($assignment['agent_id'])) {
            $product_agent_id = $assignment['agent_id'];
            break;
        }
    }

    if (empty($product_agent_id)) {
        wp_die(__('No AI Agent is assigned to the "product" content type. Please check plugin settings.', 'build360-ai'));
    }

    try {
        foreach ($fields as $field) {
            $content = $generator->generate_product_content($product, $field, $product_agent_id);
            if ($content) {
                switch ($field) {
                    case 'description':
                        $product->set_description($content);
                        break;
                    case 'short_description':
                        $product->set_short_description($content);
                        break;
                }
            }
        }

        $product->save();
        update_post_meta($product_id, '_build360_ai_last_generated', current_time('mysql'));
        $success = true;

        // Track usage
        $current_generated = get_option('build360_ai_content_generated', 0);
        update_option('build360_ai_content_generated', $current_generated + count($fields));

        $current_products = get_option('build360_ai_products_enhanced', 0);
        update_option('build360_ai_products_enhanced', $current_products + 1);

    } catch (Exception $e) {
        wp_die(sprintf(
            __('Failed to generate content: %s', 'build360-ai'),
            $e->getMessage()
        ));
    }

    if ($success) {
        wp_redirect(add_query_arg('message', 'generated', wp_get_referer()));
        exit;
    }
}

/**
 * Get settings instance
 */
private function get_settings() {
    $settings_instance = new Build360_AI_Settings();
    return $settings_instance->get_settings();
}

/**
 * Generate content for product
 *
 * @param int $product_id Product ID
 * @param array $fields Fields to generate
 * @param array $keywords Optional keywords to guide generation
 * @return array Results of generation
 */
public function generate_product_content($product_id, $fields = [], $keywords = []) {
    // Get settings
    $settings = $this->get_settings();

    // Get product
    $product = wc_get_product($product_id);
    if (!$product) {
        return [
            'success' => false,
            'message' => __('Product not found', 'build360-ai')
        ];
    }

    // If no fields specified, use default fields
    if (empty($fields)) {
        $fields = ['description', 'short_description'];
    }

    $generator = new Build360_AI_Generator();
    $results = [];
    $success_count = 0;

    // Prepare product data
    $product_data = [
        'name' => $product->get_name(),
        'keywords' => $keywords
    ];

    // Generate content for each field
    foreach ($fields as $field) {
        try {
            $content = $generator->generate_content_field('product', $field, $product_data);

            if (is_wp_error($content)) {
                $results[$field] = [
                    'success' => false,
                    'message' => $content->get_error_message()
                ];
                continue;
            }

            // Apply content to product
            switch ($field) {
                case 'description':
                    $product->set_description($content);
                    break;
                case 'short_description':
                    $product->set_short_description($content);
                    break;
                case 'seo_title':
                    update_post_meta($product_id, '_yoast_wpseo_title', $content);
                    update_post_meta($product_id, '_aioseo_title', $content);
                    update_post_meta($product_id, '_seopress_titles_title', $content);
                    break;
                case 'seo_description':
                case 'seo_meta_description':
                    update_post_meta($product_id, '_yoast_wpseo_metadesc', $content);
                    update_post_meta($product_id, '_aioseo_description', $content);
                    update_post_meta($product_id, '_seopress_titles_desc', $content);
                    break;
                case 'image_alt':
                    if ($product->get_image_id()) {
                        update_post_meta($product->get_image_id(), '_wp_attachment_image_alt', $content);
                    }
                    break;
            }

            $results[$field] = [
                'success' => true,
                'content' => $content
            ];
            $success_count++;

        } catch (Exception $e) {
            $results[$field] = [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // Save product if any content was generated
    if ($success_count > 0) {
        $product->save();
        update_post_meta($product_id, '_build360_ai_last_generated', current_time('mysql'));

        // Track usage
        $current_generated = get_option('build360_ai_content_generated', 0);
        update_option('build360_ai_content_generated', $current_generated + $success_count);

        $current_products = get_option('build360_ai_products_enhanced', 0);
        update_option('build360_ai_products_enhanced', $current_products + 1);
    }

    return [
        'success' => $success_count > 0,
        'results' => $results,
        'fields_processed' => count($fields),
        'fields_succeeded' => $success_count
    ];
}
}
