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
 * Note: This is a simplified version. For a unified log, consider a shared service.
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
    $recent_activity = array_slice($recent_activity, 0, 10); // Keep last 10
    update_option('build360_ai_recent_activity', $recent_activity);
}

/**
 * Handle bulk actions
 */
public function handle_bulk_actions($redirect_to, $doaction, $post_ids) {
    // Diagnostic log entry to observe the exact bulk action slug WordPress passes to this handler.
    $this->add_bulk_action_activity( sprintf( __( 'handle_bulk_actions fired – received action "%s" for %d products.', 'build360-ai' ), $doaction, count( (array) $post_ids ) ) );

    if ($doaction !== 'build360_ai_generate') {
        return $redirect_to;
    }

    $processed_count = 0;
    // Log the fact that the bulk action handler has been triggered. Helpful for debugging when nothing seems to happen.
    $this->add_bulk_action_activity( sprintf( __( 'Bulk action initiated for %d products.', 'build360-ai' ), count( $post_ids ) ) );
    $generator = new Build360_AI_Generator();
    $agent_assignments = get_option('build360_ai_agent_assignments', array());
    $product_agent_id = null;

    foreach ($agent_assignments as $assignment) {
        if (isset($assignment['type']) && $assignment['type'] === 'product' && isset($assignment['agent_id'])) {
            $product_agent_id = $assignment['agent_id'];
            break;
        }
    }

    if (empty($product_agent_id)) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>' . esc_html__('Bulk content generation failed: No AI Agent is assigned to the "product" content type. Please check plugin settings.', 'build360-ai') . '</p></div>';
        });
        // Log the failure so that it is visible in the Activity Log even when no agent is assigned.
        $this->add_bulk_action_activity(__('Bulk content generation failed: No AI Agent is assigned to the "product" content type. Please check plugin settings.', 'build360-ai'));
        return $redirect_to;
    }

    $generated_for_product_log = array(); // To log what was done for each product

    foreach ($post_ids as $post_id) {
        $product = wc_get_product($post_id);
        if (!$product) {
            continue;
        }

        try {
            $fields_to_generate_bulk = array('description', 'short_description'); // Fields to update in bulk
            $fields_updated_for_this_product = array();
            $product_name_for_log = $product->get_name();
            if (!is_string($product_name_for_log) || empty(trim($product_name_for_log))) {
                 $product_name_for_log = __('(Unknown Product)', 'build360-ai');
            }

            foreach ($fields_to_generate_bulk as $field) {
                // It may return:
                // 1) A string with the generated content
                // 2) An array like { "success": true, "data": { "description": "..." } }
                // 3) An array directly mapping field keys
                $api_response = $generator->generate_product_content_with_agent($product, $field, $product_agent_id, array());

                $actual_content_to_apply = null;

                if (is_wp_error($api_response)) {
                    error_log('[Build360 AI Bulk] WP_Error generating field ' . $field . ' for product ID ' . $post_id . ': ' . $api_response->get_error_message());
                    continue;
                }

                if (is_string($api_response)) {
                    // Simple string response = the generated content
                    $actual_content_to_apply = $api_response;
                } elseif (is_array($api_response)) {
                    // Check for wrapped structure with success => true
                    if (isset($api_response['success']) && $api_response['success'] === true && isset($api_response['data']) && is_array($api_response['data'])) {
                        $content_map = $api_response['data'];
                    } else {
                        // Maybe the whole array *is* the map already
                        $content_map = $api_response;
                    }

                    // Try to pull the desired field from the map
                    if (isset($content_map[$field])) {
                        $actual_content_to_apply = $content_map[$field];
                    } elseif ($field === 'description') {
                        if (isset($content_map['content'])) { $actual_content_to_apply = $content_map['content']; }
                        elseif (isset($content_map['full_description'])) { $actual_content_to_apply = $content_map['full_description']; }
                        elseif (isset($content_map['description'])) { $actual_content_to_apply = $content_map['description']; }
                    } elseif ($field === 'short_description') {
                        if (isset($content_map['short_description'])) { $actual_content_to_apply = $content_map['short_description']; }
                        elseif (isset($content_map['shortDescription'])) { $actual_content_to_apply = $content_map['shortDescription']; }
                    }
                }

                if ($actual_content_to_apply === null) {
                    error_log('[Build360 AI Bulk] Could not extract content for field ' . $field . ' from API response for product ID ' . $post_id . '. Response: ' . print_r($api_response, true));
                }

                if ($actual_content_to_apply !== null) {
                    if (is_array($actual_content_to_apply)) {
                        error_log('[Build360 AI Bulk] API returned an array for field ' . $field . ' for product ID ' . $post_id . '. Skipping field.');
                        continue;
                    }

                    $sanitized_content = wp_kses_post(strval($actual_content_to_apply));

                    switch ($field) {
                        case 'description':
                            $product->set_description($sanitized_content);
                            $fields_updated_for_this_product[] = __('description', 'build360-ai');
                            break;
                        case 'short_description':
                            $product->set_short_description($sanitized_content);
                            $fields_updated_for_this_product[] = __('short description', 'build360-ai');
                            break;
                    }
                }
            }

            if (!empty($fields_updated_for_this_product)) {
                $product->save();
                update_post_meta($post_id, '_build360_ai_last_generated', current_time('mysql'));
                $processed_count++;

                $activity_message = sprintf(
                    __('Bulk AI content generated for product: %s (ID: %d). Fields: %s', 'build360-ai'),
                    $product_name_for_log,
                    $post_id,
                    implode(', ', $fields_updated_for_this_product)
                );
                $this->add_bulk_action_activity($activity_message);

                $current_generated_option = get_option('build360_ai_content_generated', 0);
                update_option('build360_ai_content_generated', $current_generated_option + count($fields_updated_for_this_product));
            }

        } catch (Exception $e) {
            error_log(sprintf(
                'Build360 AI Bulk Action Exception: Failed for product %d: %s',
                $post_id,
                $e->getMessage()
            ));
        }
    }

    if ($processed_count > 0) {
         $current_products_enhanced = get_option('build360_ai_products_enhanced', 0);
         update_option('build360_ai_products_enhanced', $current_products_enhanced + $processed_count); 
    }

    $redirect_to = add_query_arg('build360_ai_processed', $processed_count, $redirect_to);
    return $redirect_to;
}

/**
 * Display admin notice for bulk action results
 */
public function bulk_action_admin_notice() {
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

    try {
        foreach ($fields as $field) {
            $content = $generator->generate_product_content($product, $field);
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
