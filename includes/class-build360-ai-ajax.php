<?php
/**
 * AJAX handler class
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Build360_AI_Ajax
 *
 * Handles all AJAX requests for the Build360 AI Content Generator plugin.
 */
class Build360_AI_Ajax {
    /**
     * Settings instance
     *
     * @var Build360_AI_Settings
     */
    private $settings;

    /**
     * API instance
     *
     * @var Build360_AI_API
     */
    private $api;

    /**
     * Product integration instance
     *
     * @var Build360_AI_Product_Integration
     */
    private $product_integration;

    /**
     * Log file path
     * @var string
     */
    private $log_file;

    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = new Build360_AI_Settings();
        $this->api = new Build360_AI_API();
        $this->product_integration = new Build360_AI_Product_Integration();
        
        // Define log file path directly using plugin_dir_path
        $this->log_file = plugin_dir_path(__FILE__) . '../logs/debug_agent_save.log'; // Adjusted path

        // Ensure log directory exists
        $log_dir = dirname($this->log_file);
        if (!is_dir($log_dir)) {
            // Check if parent directory is writable
            $parent_dir = dirname($log_dir);
            if (!is_writable($parent_dir)) {
                error_log('[Build360 AI Critical] Log parent directory not writable: ' . $parent_dir);
                // Fallback to default PHP error log if custom log dir can't be made
                $this->log_file = null; 
            } else {
                if (!wp_mkdir_p($log_dir)) {
                    error_log('[Build360 AI Critical] Could not create log directory: ' . $log_dir);
                    $this->log_file = null; // Fallback
                }
            }
        } elseif (!is_writable($log_dir)) {
            error_log('[Build360 AI Critical] Log directory not writable: ' . $log_dir);
            $this->log_file = null; // Fallback
        }

        // Admin AJAX actions
        add_action('wp_ajax_build360_ai_generate_content', array($this, 'generate_content'));
        add_action('wp_ajax_build360_ai_test_connection', array($this, 'test_connection'));
        add_action('wp_ajax_build360_ai_test_generate', array($this, 'test_generate'));
        add_action('wp_ajax_build360_ai_get_token_balance', array($this, 'get_token_balance'));
        add_action('wp_ajax_build360_ai_save_settings', array($this, 'save_settings'));
        add_action('wp_ajax_build360_ai_generate_product_content', array($this, 'generate_product_content'));
        add_action('wp_ajax_build360_ai_bulk_generate_product_content', array($this, 'bulk_generate_product_content'));
        // Agent operations
        add_action('wp_ajax_build360_ai_list_agents', array($this, 'list_agents_handler'));
        add_action('wp_ajax_build360_ai_get_agent_details', array($this, 'get_agent_details_handler'));
        add_action('wp_ajax_build360_ai_save_agent', array($this, 'save_agent_handler'));
        add_action('wp_ajax_build360_ai_delete_agent', array($this, 'delete_agent_handler'));
        add_action('wp_ajax_build360_ai_activate_website', array($this, 'activate_website_handler'));
        // Bulk generation progress endpoints
        add_action('wp_ajax_build360_ai_bulk_progress', array($this, 'bulk_progress_handler'));
        add_action('wp_ajax_build360_ai_bulk_results', array($this, 'bulk_results_handler'));
        add_action('wp_ajax_build360_ai_bulk_cancel', array($this, 'bulk_cancel_handler'));
    }

    /**
     * Initialize hooks
     */
    public function init() {
        // Admin AJAX actions
        add_action('wp_ajax_build360_ai_generate_content', array($this, 'generate_content'));
        add_action('wp_ajax_build360_ai_test_connection', array($this, 'test_connection'));
        add_action('wp_ajax_build360_ai_get_token_balance', array($this, 'get_token_balance'));
        add_action('wp_ajax_build360_ai_save_settings', array($this, 'save_settings'));
        add_action('wp_ajax_build360_ai_generate_product_content', array($this, 'generate_product_content'));
        add_action('wp_ajax_build360_ai_bulk_generate_product_content', array($this, 'bulk_generate_product_content'));
        // Agent operations
        add_action('wp_ajax_build360_ai_list_agents', array($this, 'list_agents_handler'));
        add_action('wp_ajax_build360_ai_get_agent_details', array($this, 'get_agent_details_handler'));
        add_action('wp_ajax_build360_ai_save_agent', array($this, 'save_agent_handler'));
        add_action('wp_ajax_build360_ai_delete_agent', array($this, 'delete_agent_handler'));
        add_action('wp_ajax_build360_ai_activate_website', array($this, 'activate_website_handler'));
        // Bulk generation progress endpoints
        add_action('wp_ajax_build360_ai_bulk_progress', array($this, 'bulk_progress_handler'));
        add_action('wp_ajax_build360_ai_bulk_results', array($this, 'bulk_results_handler'));
        add_action('wp_ajax_build360_ai_bulk_cancel', array($this, 'bulk_cancel_handler'));
    }

    /**
     * Generate content (formerly product-specific, now more generic via agent_id)
     */
    public function generate_content() {
        error_log('[Build360 AI DEBUG] generate_content AJAX handler called. POST data: ' . print_r($_POST, true));
        
        // Check nonce first. The JS now sends nonce from build360_ai_vars.nonces.generate_content
        check_ajax_referer('build360_ai_generate_content', 'nonce');

        // Check user permissions (e.g., edit posts or specific capability)
        // For products, it was 'edit_products'. Let's use 'edit_posts' for broader compatibility for now.
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action.', 'build360-ai')
            ));
            return; // Important to exit after wp_send_json_error
        }

        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0; // Still useful for context and saving
        $payload_raw = isset($_POST['payload']) ? $_POST['payload'] : null;
        $fields_to_update = isset($_POST['fields_to_update']) && is_array($_POST['fields_to_update']) ? array_map('sanitize_text_field', $_POST['fields_to_update']) : array();
        // $language = isset($_POST['language']) ? sanitize_text_field($_POST['language']) : 'en'; // Language can be passed to API if supported by agent

        if (empty($payload_raw) || !is_array($payload_raw) || empty($fields_to_update)) {
            wp_send_json_error(array(
                'message' => __('Invalid request parameters. Payload or fields_to_update missing.', 'build360-ai')
            ));
            return;
        }
        
        // Sanitize payload
        $payload = array(
            'title' => isset($payload_raw['title']) ? sanitize_text_field($payload_raw['title']) : '',
            'description' => isset($payload_raw['description']) ? wp_kses_post($payload_raw['description']) : '',
            'type' => isset($payload_raw['type']) ? sanitize_key($payload_raw['type']) : '', // 'post' or 'taxonomy'
            'agent_id' => isset($payload_raw['agent_id']) ? sanitize_key($payload_raw['agent_id']) : '',
        );

        if (empty($payload['agent_id'])) {
            wp_send_json_error(array('message' => __('Agent ID is missing in the payload.', 'build360-ai')));
            return;
        }
        if (empty($payload['title']) && empty($payload['description'])) {
             wp_send_json_error(array('message' => __('Title or description must be provided for context.', 'build360-ai')));
            return;
        }


        // $api = new Build360_AI_API(); // API class will be instantiated by the generator or a new service class
        $generator = new Build360_AI_Generator(); // The Generator class will use the API class

        try {
            // The Build360_AI_Generator::generate_content_for_post method will be new or refactored
            // It will take the $payload (which includes agent_id, title, description, type)
            // and $fields_to_update (e.g., ['description', 'seo_title'])
            // Internally, it will use agent_id to get agent settings, then call the API service.
            // The response from generate_content_for_post should be structured to indicate
            // which fields got what content, and any errors.
            
            // Let's assume a new method in Build360_AI_Generator or a similar service
            // $generated_data = $generator->generate_via_agent($payload, $fields_to_update, $product_id, $language);
            
            // For now, we adapt the call to the existing Build360_AI_API structure, which will be refactored next.
            // The external API expects: product_title, product_description, prompt.
            // The agent_id will determine the actual prompt, model, etc.

            $api_service = new Build360_AI_API(); // We'll refactor its generate_content method

            // The API service's generate_content will need to accept the agent_id and use it.
            // The `data` it receives should be what the *external* API expects.
            // We need to prepare the $api_request_data based on $payload and agent settings.
            
            // This is a placeholder for fetching agent settings based on $payload['agent_id']
            // $agent_settings = get_option('build360_ai_agents')[$payload['agent_id']];
            // $prompt_template = $agent_settings['system_prompt'];
            // $final_prompt = str_replace(['{{title}}', '{{description}}'], [$payload['title'], $payload['description']], $prompt_template);

            $api_request_data = array(
                'product_title' => $payload['title'],       // Or however agent config maps it
                'product_description' => $payload['description'], // Or however agent config maps it
                'prompt' => 'Generate content based on the provided title and description.', // This will be dynamic based on agent
                // Agent specific settings (model, style, etc.) will be handled by the API class method using agent_id
                'fields_requested' => $fields_to_update // Inform API what specific pieces of content are needed
            );

            // The API's generate_content method needs to be refactored to accept agent_id
            // and use it to fetch agent-specific settings (model, style, actual prompt template)
            // and then make the call to the /api/generate endpoint.
            $api_response = $api_service->generate_content(
                $payload['agent_id'], // NEW: Pass agent_id
                $payload['type'],     // e.g., 'post' (used to be $content_type)
                $api_request_data    // This is the data for the *external* API
                                      // The method will use agent_id to get model, style, and formulate the final prompt.
            );

            if (is_wp_error($api_response)) {
            wp_send_json_error(array(
                    'message' => $api_response->get_error_message()
                ));
                return;
            }

            // $api_response from the external API should be a JSON object where keys are field names
            // (e.g., 'description', 'seo_title') and values are the generated content.
            // Example: { "description": "Generated full desc...", "seo_title": "Generated SEO Title" }
            
            $generated_content_map = $api_response; // Assuming direct map from API
            $update_errors = array();

            if ($product_id && $generated_content_map && is_array($generated_content_map)) {
                $product = wc_get_product($product_id); 
                if ($product) {
                    foreach ($fields_to_update as $field_key) { // $field_key is 'description', 'seo_title', 'title', etc.
                        
                        $api_response_key = '';
                        // Map the $field_key (from checkbox) to the actual key in the $generated_content_map (API response)
                        switch ($field_key) {
                            case 'title':
                                $api_response_key = 'title'; // API is expected to return 'title' for product name
                                break;
                            case 'description':
                                $api_response_key = 'content'; // API is expected to return 'content' for main description
                                break;
                            case 'short_description':
                                $api_response_key = 'short_description'; // API is expected to return 'short_description'
                                break;
                            case 'seo_title':
                                $api_response_key = 'meta_title'; // API is expected to return 'meta_title'
                                break;
                            case 'seo_description':
                                $api_response_key = 'meta_description'; // API is expected to return 'meta_description'
                                break;
                            case 'image_alt':
                                $api_response_key = 'image_alt'; // API is expected to return 'image_alt'
                                break;
                            default:
                                // If there are other field keys, they might map directly or need new cases
                                // For now, assume direct mapping if not specified, or log an error
                                // $api_response_key = $field_key; // Fallback if not explicitly mapped - could be risky
                                error_log('[Build360 AI] Unmapped field key encountered during save: ' . $field_key);
                                continue; // Skip unmapped keys to be safe
                        }

                        if (!empty($api_response_key) && isset($generated_content_map[$api_response_key])) {
                            $content_to_apply = $generated_content_map[$api_response_key];

                            // Ensure $content_to_apply is a string. If API returns an array, log it and skip.
                            if (is_array($content_to_apply)) {
                                error_log('[Build360 AI] API returned an array for key ' . $api_response_key . ' (mapped from field ' . $field_key . '), expected a string. Content: ' . print_r($content_to_apply, true));
                                $update_errors[] = sprintf(__('Content for field "%s" was not a simple text value and could not be saved.', 'build360-ai'), $field_key);
                                continue; // Skip this field to prevent "Array to string conversion"
                            }
                            
                            $string_content_to_apply = strval($content_to_apply); // Ensure it's a string

                            // Extra safeguard: ensure content is scalar after sanitization, default to empty string if not.
                            $final_content_to_save = '';

                            switch ($field_key) { // Switch on the original $field_key from the checkbox values
                                case 'title': 
                                    $final_content_to_save = sanitize_text_field($string_content_to_apply);
                                    if (!is_scalar($final_content_to_save)) {
                                        error_log('[Build360 AI] Non-scalar value detected for title after sanitization. Field: ' . $field_key . ', API Key: ' . $api_response_key);
                                        $final_content_to_save = '';
                                    }
                                    $product->set_name($final_content_to_save);
                                    break;
                                case 'description':
                                    $final_content_to_save = wp_kses_post($string_content_to_apply);
                                     if (!is_scalar($final_content_to_save)) {
                                        error_log('[Build360 AI] Non-scalar value detected for description after sanitization. Field: ' . $field_key . ', API Key: ' . $api_response_key);
                                        $final_content_to_save = '';
                                    }
                                    $product->set_description($final_content_to_save);
                                    break;
                                case 'short_description':
                                    $final_content_to_save = wp_kses_post($string_content_to_apply);
                                    if (!is_scalar($final_content_to_save)) {
                                        error_log('[Build360 AI] Non-scalar value detected for short_description after sanitization. Field: ' . $field_key . ', API Key: ' . $api_response_key);
                                        $final_content_to_save = '';
                                    }
                                    $product->set_short_description($final_content_to_save);
                                    break;
                                case 'seo_title':
                                    $final_content_to_save = sanitize_text_field($string_content_to_apply);
                                    if (!is_scalar($final_content_to_save)) {
                                        error_log('[Build360 AI] Non-scalar value detected for seo_title after sanitization. Field: ' . $field_key . ', API Key: ' . $api_response_key);
                                        $final_content_to_save = '';
                                    }
                                    update_post_meta($product_id, '_yoast_wpseo_title', $final_content_to_save);
                                    update_post_meta($product_id, 'rank_math_title', $final_content_to_save);
                                    update_post_meta($product_id, '_seopress_titles_title', $final_content_to_save);
                                    update_post_meta($product_id, '_aioseo_title', $final_content_to_save); 
                                    break;
                                case 'seo_description':
                                    $final_content_to_save = wp_kses_post($string_content_to_apply);
                                    if (!is_scalar($final_content_to_save)) {
                                        error_log('[Build360 AI] Non-scalar value detected for seo_description after sanitization. Field: ' . $field_key . ', API Key: ' . $api_response_key);
                                        $final_content_to_save = '';
                                    }
                                    update_post_meta($product_id, '_yoast_wpseo_metadesc', $final_content_to_save);
                                    update_post_meta($product_id, 'rank_math_description', $final_content_to_save);
                                    update_post_meta($product_id, '_seopress_titles_desc', $final_content_to_save);
                                    update_post_meta($product_id, '_aioseo_description', $final_content_to_save);
                                    break;
                                case 'image_alt':
                                    $final_content_to_save = sanitize_text_field($string_content_to_apply);
                                    if (!is_scalar($final_content_to_save)) {
                                        error_log('[Build360 AI] Non-scalar value detected for image_alt after sanitization. Field: ' . $field_key . ', API Key: ' . $api_response_key);
                                        $final_content_to_save = '';
                                    }
                                $image_id = $product->get_image_id();
                                if ($image_id) {
                                        update_post_meta($image_id, '_wp_attachment_image_alt', $final_content_to_save);
                                }
                                break;
                        }
                        } else {
                             error_log('[Build360 AI] Content for API key ' . $api_response_key . ' (mapped from field ' . $field_key . ') not found in API response or API key was empty.');
                        }
                    }
                    
                    // Save the product and handle potential errors
                    try {
                        $save_result = $product->save();

                        if (is_wp_error($save_result)) {
                            error_log('[Build360 AI] Error saving product (ID: ' . $product_id . '): ' . $save_result->get_error_message());
                            $update_errors[] = __('Error saving product updates.', 'build360-ai');
                            // Do not proceed to log activity if save failed
                        } elseif ($save_result === false || $save_result === 0) {
                            // Some save handlers might return false or 0 if no changes or on failure, though WC usually uses WP_Error
                            error_log('[Build360 AI] Product save operation returned false or 0 (ID: ' . $product_id . '). No changes might have been saved or an issue occurred.');
                            // Potentially add to $update_errors if this is considered a failure state
                            // $update_errors[] = __('Product save operation indicated no changes or an issue.', 'build360-ai');
                             // Log activity even if no changes, as generation was attempted
                            update_post_meta($product_id, '_build360_ai_last_generated', current_time('mysql'));
                            $product_name_for_log = $product->get_name();
                            if (!is_string($product_name_for_log) || empty(trim($product_name_for_log))) {
                                error_log('[Build360 AI] Product name for activity log was not a valid string. Product ID: ' . $product_id);
                                $product_name_for_log = __('(Unknown Product)', 'build360-ai');
                            }

                            $fields_string_for_log = implode(', ', $fields_to_update);
                            if (!is_string($fields_string_for_log)) { // Should always be a string from implode, but good to be safe.
                                error_log('[Build360 AI] Fields string for activity log was not valid. Product ID: ' . $product_id);
                                $fields_string_for_log = __('(unknown fields)', 'build360-ai');
                            }

                            $activity_message = sprintf(
                                __('AI content generation attempted for product: %s (ID: %d). Fields targeted: %s. Save operation reported no changes or an issue.', 'build360-ai'),
                                $product_name_for_log,
                                $product_id,
                                $fields_string_for_log
                            );
                            $this->add_to_recent_activity($activity_message);
                        } else {
                            // Product saved successfully (or at least no WP_Error returned and save_result indicates success/changes)
            update_post_meta($product_id, '_build360_ai_last_generated', current_time('mysql'));

                            $product_name_for_log = $product->get_name();
                            if (!is_string($product_name_for_log) || empty(trim($product_name_for_log))) {
                                error_log('[Build360 AI] Product name for activity log was not a valid string. Product ID: ' . $product_id);
                                $product_name_for_log = __('(Unknown Product)', 'build360-ai');
                            }

                            $fields_string_for_log = implode(', ', $fields_to_update);
                            if (!is_string($fields_string_for_log)) { // Should always be a string from implode, but good to be safe.
                                error_log('[Build360 AI] Fields string for activity log was not valid. Product ID: ' . $product_id);
                                $fields_string_for_log = __('(unknown fields)', 'build360-ai');
                            }

                            $activity_message = sprintf(
                                __('AI content generated and applied for product: %s (ID: %d). Fields updated: %s', 'build360-ai'),
                                $product_name_for_log,
                                $product_id,
                                $fields_string_for_log
                            );
                            error_log('[Build360 AI DEBUG] Attempting to log activity: ' . $activity_message); // DEBUG LOG
                            $this->add_to_recent_activity($activity_message);
                        }
                    } catch (Exception $e) {
                        error_log('[Build360 AI] Exception during product save (ID: ' . $product_id . '): ' . $e->getMessage());
                        $update_errors[] = __('An unexpected error occurred while saving product updates.', 'build360-ai');
                        // Do not proceed to log plugin activity if save threw an exception
                    }
                } else {
                     error_log('[Build360 AI] Could not retrieve product with ID: ' . $product_id);
                }
            } elseif ($product_id) { // Log if $generated_content_map is not as expected but product_id was present
                 error_log('[Build360 AI] Generated content map was empty or not an array for product ID: ' . $product_id . '. Map: ' . print_r($generated_content_map, true));
            }

            // Prepare success response for JS
            $success_data = array(
                'message' => __('Content generated and applied (if selected).', 'build360-ai'),
                'content' => $generated_content_map, // Send the original map back, JS knows how to parse it
                'fields_updated' => $fields_to_update,
            );
            if (!empty($update_errors)) {
                $success_data['errors'] = $update_errors; // Include any non-fatal errors
                $success_data['message'] = __('Content generation partially completed with some issues.', 'build360-ai');
            }

            wp_send_json_success($success_data);

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * Test the API connection
     */
    public function test_connection() {
        check_ajax_referer('build360_ai_test_connection', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action.', 'build360-ai')
            ));
        }

        // Get API key and domain from the request
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $domain = isset($_POST['domain']) ? esc_url_raw($_POST['domain']) : '';

        // Temporarily override the API settings
        $original_api_key = get_option('build360_ai_api_key', '');
        $original_domain = get_option('build360_ai_domain', '');

        update_option('build360_ai_api_key', $api_key);
        update_option('build360_ai_domain', $domain);

        $api = new Build360_AI_API();

        try {
            $result = $api->test_connection();

            // Restore original settings
            update_option('build360_ai_api_key', $original_api_key);
            update_option('build360_ai_domain', $original_domain);

            if ($result === true) {
                wp_send_json_success(array(
                    'message' => __('Connection successful! Your API key is valid.', 'build360-ai')
                ));
            } else {
                wp_send_json_error(array(
                    'message' => __('Connection failed. Please check your API credentials.', 'build360-ai')
                ));
            }
        } catch (Exception $e) {
            // Restore original settings
            update_option('build360_ai_api_key', $original_api_key);
            update_option('build360_ai_domain', $original_domain);

            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * Get the current token balance
     */
    public function get_token_balance() {
        check_ajax_referer('build360_ai_get_token_balance_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action.', 'build360-ai')
            ));
            return;
        }

        // Use the settings instance to get the token usage data for consistency
        $token_usage_data = $this->settings->get_token_usage();

        if (is_wp_error($token_usage_data)) {
            wp_send_json_error(array(
                'message' => $token_usage_data->get_error_message(),
                'data'    => $token_usage_data->get_error_data()
            ));
        } elseif (is_array($token_usage_data) && isset($token_usage_data['remaining']) && isset($token_usage_data['used']) && isset($token_usage_data['total'])) {
            wp_send_json_success($token_usage_data);
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to retrieve token usage data or data is in an unexpected format.', 'build360-ai'),
                'debug_data' => $token_usage_data 
            ));
        }
    }

    /**
     * Save settings
     */
    public function save_settings() {
        // Log the received nonce value
        if (isset($_POST['nonce'])) {
            $this->custom_log('[Build360 AI Debug] save_settings_handler received POST nonce: ' . $_POST['nonce']);
        } elseif (isset($_REQUEST['nonce'])) {
            $this->custom_log('[Build360 AI Debug] save_settings_handler received REQUEST nonce: ' . $_REQUEST['nonce']);
        } else {
            $this->custom_log('[Build360 AI Debug] save_settings_handler NO nonce received.');
        }
        
        // Log the expected nonce action name for check_ajax_referer
        $this->custom_log('[Build360 AI Debug] save_settings_handler expecting nonce action for check_ajax_referer: build360_ai_save_settings');

        check_ajax_referer('build360_ai_save_settings', 'nonce');

        if (!current_user_can('manage_options')) {
            $this->send_json_response(array(
                'message' => __('You do not have permission to perform this action.', 'build360-ai')
            ), false);
        }

        $raw_settings = isset($_POST['settings']) && is_array($_POST['settings']) ? $_POST['settings'] : array();
        $sanitized_settings = array();
        $agent_assignments_to_save = array();
        $api_key_was_saved = false;

        foreach ($raw_settings as $key => $value) {
            if ($key === 'build360_ai_api_key') {
                $api_key_was_saved = true;
            }
            if ($key === 'agent_assignments') {
                if (is_array($value)) {
                    foreach ($value as $index => $assignment) {
                        if (is_array($assignment) && !empty($assignment['type']) && !empty($assignment['agent_id'])) {
                            $agent_assignments_to_save[] = array(
                                'type'     => sanitize_text_field($assignment['type']),
                                'agent_id' => sanitize_text_field($assignment['agent_id']),
                            );
                        }
                    }
                }
            } else {
                if (is_array($value)) {
                    $sanitized_array = array();
                    foreach($value as $sub_key => $sub_value){
                        $sanitized_array[sanitize_key($sub_key)] = sanitize_text_field($sub_value);
                    }
                    $sanitized_settings[sanitize_key($key)] = $sanitized_array;
                } else {
                    // Specific sanitization for debug_mode (boolean as 0 or 1)
                    if ($key === 'build360_ai_debug_mode') {
                        $sanitized_settings[sanitize_key($key)] = ($value === '1' || $value === true) ? '1' : '0';
                    } else {
                        $sanitized_settings[sanitize_key($key)] = sanitize_text_field($value);
                    }
                }
            }
        }
        
        $result = $this->settings->save_settings($sanitized_settings); 

        if (is_wp_error($result)) {
            $this->send_json_response(array(
                'message' => $result->get_error_message(),
            ), false);
        }

        update_option('build360_ai_agent_assignments', $agent_assignments_to_save);

        $needs_activation = false;
        if ($api_key_was_saved && !get_option('build360_ai_website_id')) {
            $needs_activation = true;
        }

        $this->send_json_response(array(
            'message' => __('Settings saved successfully!', 'build360-ai'),
            'needs_activation' => $needs_activation,
            'saved_settings' => $sanitized_settings // Optional: send back what was saved for debugging
        ));
    }

    /**
     * Generate product content
     */
    public function generate_product_content() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'build360_ai_generate_product_content')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'build360-ai'),
            ));
        }

        // Get product ID
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

        if ($product_id <= 0) {
            wp_send_json_error(array(
                'message' => __('Invalid product ID.', 'build360-ai'),
            ));
        }

        // Get fields
        $fields = isset($_POST['fields']) && is_array($_POST['fields']) ? array_map('sanitize_text_field', $_POST['fields']) : array();

        if (empty($fields)) {
            wp_send_json_error(array(
                'message' => __('No fields selected.', 'build360-ai'),
            ));
        }

        // Get keywords
        $keywords = isset($_POST['keywords']) ? explode(',', sanitize_text_field($_POST['keywords'])) : array();
        $keywords = array_map('trim', $keywords);

        // Generate content
        $result = $this->product_integration->generate_product_content($product_id, $fields, $keywords);

        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message(),
            ));
        }

        wp_send_json_success(array(
            'message' => __('Content generated successfully!', 'build360-ai'),
        ));
    }

    /**
     * Bulk generate product content
     */
    public function bulk_generate_product_content() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'build360_ai_bulk_generate_product_content')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'build360-ai'),
            ));
        }

        // Get product IDs
        $product_ids = isset($_POST['product_ids']) && is_array($_POST['product_ids']) ? array_map('intval', $_POST['product_ids']) : array();

        if (empty($product_ids)) {
            wp_send_json_error(array(
                'message' => __('No products selected.', 'build360-ai'),
            ));
        }

        // Get fields
        $fields = isset($_POST['fields']) && is_array($_POST['fields']) ? array_map('sanitize_text_field', $_POST['fields']) : array();

        if (empty($fields)) {
            wp_send_json_error(array(
                'message' => __('No fields selected.', 'build360-ai'),
            ));
        }

        // Get keywords
        $keywords = isset($_POST['keywords']) ? explode(',', sanitize_text_field($_POST['keywords'])) : array();
        $keywords = array_map('trim', $keywords);

        // Process products
        $processed = 0;
        $errors = array();

        foreach ($product_ids as $product_id) {
            $result = $this->product_integration->generate_product_content($product_id, $fields, $keywords);

            if (is_wp_error($result)) {
                $errors[] = sprintf(
                    /* translators: 1: product ID, 2: error message */
                    __('Product #%1$s: %2$s', 'build360-ai'),
                    $product_id,
                    $result->get_error_message()
                );
            } else {
                $processed++;
            }
        }

        wp_send_json_success(array(
            'message' => sprintf(
                /* translators: %d: number of products */
                _n(
                    'Content generated for %d product.',
                    'Content generated for %d products.',
                    $processed,
                    'build360-ai'
                ),
                $processed
            ),
            'processed' => $processed,
            'errors' => $errors,
        ));
    }

    /**
     * Get bulk generation job progress
     */
    public function bulk_progress_handler() {
        check_ajax_referer('build360_ai_bulk_progress_nonce', 'nonce');

        if (!current_user_can('edit_products')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'build360-ai')));
            return;
        }

        $job_id = isset($_POST['job_id']) ? sanitize_text_field($_POST['job_id']) : '';

        // If no job_id passed, try to find active job for current user
        if (empty($job_id)) {
            $job_id = get_user_meta(get_current_user_id(), '_build360_ai_active_bulk_job', true);
        }

        if (empty($job_id)) {
            wp_send_json_error(array('message' => __('No active bulk job found.', 'build360-ai'), 'no_job' => true));
            return;
        }

        $job_data = get_option('build360_ai_bulk_job_' . $job_id);
        if (!$job_data || !is_array($job_data)) {
            // Job data cleaned up or doesn't exist
            delete_user_meta(get_current_user_id(), '_build360_ai_active_bulk_job');
            wp_send_json_error(array('message' => __('Bulk job data not found. It may have been cleaned up.', 'build360-ai'), 'no_job' => true));
            return;
        }

        // Build per-product summary (lightweight - no full previews)
        $products_summary = array();
        foreach ($job_data['products'] as $pid => $pdata) {
            $field_statuses = array();
            foreach ($pdata['fields'] as $fname => $fdata) {
                $field_statuses[$fname] = $fdata['status'];
            }
            $products_summary[$pid] = array(
                'name' => $pdata['name'],
                'status' => $pdata['status'],
                'fields' => $field_statuses,
            );
        }

        wp_send_json_success(array(
            'job_id' => $job_data['job_id'],
            'status' => $job_data['status'],
            'total' => $job_data['total'],
            'completed' => $job_data['completed'],
            'succeeded' => $job_data['succeeded'],
            'failed' => $job_data['failed'],
            'products' => $products_summary,
        ));
    }

    /**
     * Get full bulk generation results (with content previews)
     */
    public function bulk_results_handler() {
        check_ajax_referer('build360_ai_bulk_results_nonce', 'nonce');

        if (!current_user_can('edit_products')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'build360-ai')));
            return;
        }

        $job_id = isset($_POST['job_id']) ? sanitize_text_field($_POST['job_id']) : '';
        if (empty($job_id)) {
            $job_id = get_user_meta(get_current_user_id(), '_build360_ai_active_bulk_job', true);
        }

        if (empty($job_id)) {
            wp_send_json_error(array('message' => __('No active bulk job found.', 'build360-ai')));
            return;
        }

        $job_data = get_option('build360_ai_bulk_job_' . $job_id);
        if (!$job_data || !is_array($job_data)) {
            wp_send_json_error(array('message' => __('Bulk job data not found.', 'build360-ai')));
            return;
        }

        // Include full product details with previews
        $products_detail = array();
        foreach ($job_data['products'] as $pid => $pdata) {
            $products_detail[$pid] = array(
                'name' => $pdata['name'],
                'status' => $pdata['status'],
                'fields' => $pdata['fields'],
                'edit_url' => get_edit_post_link($pid, 'raw'),
            );
        }

        wp_send_json_success(array(
            'job_id' => $job_data['job_id'],
            'status' => $job_data['status'],
            'total' => $job_data['total'],
            'succeeded' => $job_data['succeeded'],
            'failed' => $job_data['failed'],
            'products' => $products_detail,
        ));
    }

    /**
     * Cancel a running bulk generation job
     */
    public function bulk_cancel_handler() {
        check_ajax_referer('build360_ai_bulk_cancel_nonce', 'nonce');

        if (!current_user_can('edit_products')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'build360-ai')));
            return;
        }

        $job_id = isset($_POST['job_id']) ? sanitize_text_field($_POST['job_id']) : '';
        if (empty($job_id)) {
            $job_id = get_user_meta(get_current_user_id(), '_build360_ai_active_bulk_job', true);
        }

        if (empty($job_id)) {
            wp_send_json_error(array('message' => __('No active bulk job found.', 'build360-ai')));
            return;
        }

        $job_data = get_option('build360_ai_bulk_job_' . $job_id);
        if (!$job_data || !is_array($job_data)) {
            wp_send_json_error(array('message' => __('Bulk job data not found.', 'build360-ai')));
            return;
        }

        // Mark job as cancelled - pending tasks will bail out when they start
        $job_data['status'] = 'cancelled';
        update_option('build360_ai_bulk_job_' . $job_id, $job_data, false);

        // Try to unschedule pending actions
        if (function_exists('as_unschedule_all_actions')) {
            as_unschedule_all_actions('build360_ai_process_single_product', null, 'build360-ai');
        }

        wp_send_json_success(array(
            'message' => __('Bulk generation job has been cancelled.', 'build360-ai'),
            'job_id' => $job_id,
        ));
    }

    /**
     * Add an entry to the recent activity log
     */
    private function add_to_recent_activity($activity) {
        error_log('[Build360 AI DEBUG] Entered add_to_recent_activity method.'); // DEBUG LOG
        $activity_message_str = strval($activity); // Ensure message is a string
        if (empty(trim($activity_message_str))) {
            error_log('[Build360 AI] add_to_recent_activity called with an empty message.');
            return;
        }

        $recent_activity = get_option('build360_ai_recent_activity', array());
        // Ensure $recent_activity is an array, even if get_option returns something unexpected (e.g. false on error)
        if (!is_array($recent_activity)) {
            error_log('[Build360 AI] build360_ai_recent_activity option was not an array. Resetting. Value: ' . print_r($recent_activity, true));
            $recent_activity = array();
        }

        array_unshift($recent_activity, array(
            'time' => current_time('mysql'),
            'message' => $activity_message_str // Use the stringified message
        ));

        // Keep only the last 10 activities
        $recent_activity = array_slice($recent_activity, 0, 10);

        error_log('[Build360 AI DEBUG] Data for update_option build360_ai_recent_activity: ' . print_r($recent_activity, true)); // DEBUG LOG
        if (!update_option('build360_ai_recent_activity', $recent_activity)) {
            error_log('[Build360 AI] Failed to update build360_ai_recent_activity option.');
        }
    }

    private function get_token_usage_data() {
        return method_exists($this->settings, 'get_token_usage')
            ? $this->settings->get_token_usage()
            : array(
                'total' => 1000000,
                'used' => 0,
                'used_today' => 0,
                'used_month' => 0
            );
    }

    private function send_json_response($data = array(), $success = true) {
        // Clear any unexpected output
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        if ($success) {
            wp_send_json_success($data);
        } else {
            wp_send_json_error($data);
        }
        wp_die(); // this is required to terminate immediately and return a proper response
    }

    /**
     * Get agent details (placeholder, to be replaced by API call)
     */
    public function get_agent_details_handler() {
        check_ajax_referer('build360_ai_get_agent_details_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            $this->send_json_response(array('message' => __('Permission denied.', 'build360-ai')), false);
        }

        $agent_id = isset($_POST['agent_id']) ? absint($_POST['agent_id']) : 0;
        if (empty($agent_id)) {
            $this->send_json_response(array('message' => __('Agent ID is required.', 'build360-ai')), false);
        }

        $result = $this->api->get_agent($agent_id);

        if (is_wp_error($result)) {
            $this->send_json_response(array('message' => $result->get_error_message(), 'data' => $result->get_error_data()), false);
        } else {
            $this->send_json_response($result);
        }
    }

    /**
     * Save (create or update) agent (placeholder, to be replaced by API call)
     */
    public function save_agent_handler() {
        check_ajax_referer('build360_ai_save_agent_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            $this->send_json_response(array('message' => __('Permission denied.', 'build360-ai')), false);
        }

        // WooCommerce Logger
        $wc_logger = null;
        if (class_exists('WC_Logger')) {
            $wc_logger = wc_get_logger();
            $context = array( 'source' => 'build360-ai-save-agent' );
        }

        $log_message_post = '[Build360 AI Debug] save_agent_handler _POST: ' . print_r($_POST, true);
        $this->custom_log($log_message_post);
        if ($wc_logger) {
            $wc_logger->debug($log_message_post, $context);
        }

        $agent_id = isset($_POST['agent_id']) ? absint($_POST['agent_id']) : 0;
        $agent_data = isset($_POST['agent_data']) && is_array($_POST['agent_data']) ? $_POST['agent_data'] : array();

        $log_message_agent_data = '[Build360 AI Debug] save_agent_handler $agent_data: ' . print_r($agent_data, true);
        $this->custom_log($log_message_agent_data);
        if ($wc_logger) {
            $wc_logger->debug($log_message_agent_data, $context);
        }
        
        $is_agent_id_empty = empty($agent_id);
        $is_name_empty = isset($agent_data['name']) ? empty($agent_data['name']) : true;
        $is_model_empty = isset($agent_data['ai_model']) ? empty($agent_data['ai_model']) : true;
        $is_prompt_empty = isset($agent_data['system_prompt']) ? empty($agent_data['system_prompt']) : true;
        
        $log_message_checks = '[Build360 AI Debug] Checking: is_agent_id_empty=' . ($is_agent_id_empty ? 'true' : 'false') .
                    ', is_name_empty=' . ($is_name_empty ? 'true' : 'false') .
                    ', is_model_empty=' . ($is_model_empty ? 'true' : 'false') .
                    ', is_prompt_empty=' . ($is_prompt_empty ? 'true' : 'false');
        $this->custom_log($log_message_checks);
        if ($wc_logger) {
            $wc_logger->debug($log_message_checks, $context);
        }

        if ($is_agent_id_empty && ($is_name_empty || $is_model_empty || $is_prompt_empty)) {
            $log_message_validation_failed = '[Build360 AI Debug] Validation failed: Name, AI Model, or System Prompt is empty.';
            $this->custom_log($log_message_validation_failed);
            if ($wc_logger) {
                $wc_logger->debug($log_message_validation_failed, $context);
            }
            $this->send_json_response(array('message' => __('Name, AI Model, and System Prompt are required to create an agent.', 'build360-ai')), false);
        }
        
        // Sanitize agent_data (example, expand as needed)
        $sanitized_data = array();
        $allowed_fields = array('name', 'description', 'ai_model', 'text_style', 'system_prompt', 'content_settings', 'is_active');
        foreach($allowed_fields as $field) {
            if (isset($agent_data[$field])) {
                if ($field === 'content_settings' && is_array($agent_data[$field])) {
                    // Further sanitize content_settings if necessary
                    $sanitized_data[$field] = $agent_data[$field]; // For now, pass as is, assuming client sends structured data
                } elseif ($field === 'is_active') {
                     $sanitized_data[$field] = rest_sanitize_boolean($agent_data[$field]);
                } else {
                    $sanitized_data[$field] = sanitize_textarea_field($agent_data[$field]); // Use sanitize_textarea_field for potentially multi-line prompts
                }
            }
        }
        if (isset($agent_data['name'])) { // Name is crucial, ensure it's always a string
             $sanitized_data['name'] = sanitize_text_field($agent_data['name']);
        }

        if (empty($sanitized_data)) {
            $this->send_json_response(array('message' => __('No valid agent data provided.', 'build360-ai')), false);
        }

        if ($agent_id) {
            // Update existing agent
            $result = $this->api->update_agent($agent_id, $sanitized_data);
        } else {
            // Create new agent
            $result = $this->api->create_agent($sanitized_data);
        }

        if (is_wp_error($result)) {
            $this->send_json_response(array('message' => $result->get_error_message(), 'data' => $result->get_error_data()), false);
        } else {
            // Agent saved successfully, now refresh the local WordPress option with all agents.
            $all_agents_result = $this->api->list_agents();
            if (!is_wp_error($all_agents_result)) {
                $agents_to_store = array();
                $agents_list = array();

                if (isset($all_agents_result['success']) && $all_agents_result['success'] === true && isset($all_agents_result['data']) && is_array($all_agents_result['data'])) {
                    $agents_list = $all_agents_result['data'];
                } elseif (is_array($all_agents_result)) {
                    $agents_list = $all_agents_result;
                }

                foreach ($agents_list as $agent) {
                    if (is_array($agent) && isset($agent['id'])) {
                        $agents_to_store[$agent['id']] = $agent;
                    } elseif (is_object($agent) && isset($agent->id)) {
                        $agents_to_store[$agent->id] = (array) $agent;
                    }
                }
                
                if (!empty($agents_to_store)) {
                    update_option('build360_ai_agents', $agents_to_store);
                } elseif (empty($agents_list)) { // If API returns empty list after deletion
                    update_option('build360_ai_agents', array());
                }
            } else {
                // Log error if fetching all agents failed after save, but proceed with success response for the save itself.
                $log_message_list_fail = '[Build360 AI Warning] save_agent_handler: Agent saved/created successfully, but failed to refresh local agent list: ' . $all_agents_result->get_error_message();
                $this->custom_log($log_message_list_fail);
                if (class_exists('WC_Logger')) {
                    $wc_logger = wc_get_logger();
                    $wc_logger->warning($log_message_list_fail, array( 'source' => 'build360-ai-save-agent' ));
                }
            }
            $this->send_json_response($result); // Send the original success response from create/update
        }
    }

    /**
     * Delete agent (placeholder, to be replaced by API call)
     */
    public function delete_agent_handler() {
        check_ajax_referer('build360_ai_delete_agent_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            $this->send_json_response(array('message' => __('Permission denied.', 'build360-ai')), false);
        }

        $agent_id = isset($_POST['agent_id']) ? absint($_POST['agent_id']) : 0;

        if (empty($agent_id)) {
            $this->send_json_response(array('message' => __('Agent ID is required.', 'build360-ai')), false);
        }

        $result = $this->api->delete_agent($agent_id);

        if (is_wp_error($result)) {
            $this->send_json_response(array('message' => $result->get_error_message(), 'data' => $result->get_error_data()), false);
        } else {
            // Agent deleted successfully, now refresh the local WordPress option with all agents.
            $all_agents_result = $this->api->list_agents();
            if (!is_wp_error($all_agents_result)) {
                $agents_to_store = array();
                $agents_list = array();

                if (isset($all_agents_result['success']) && $all_agents_result['success'] === true && isset($all_agents_result['data']) && is_array($all_agents_result['data'])) {
                    $agents_list = $all_agents_result['data'];
                } elseif (is_array($all_agents_result)) {
                    $agents_list = $all_agents_result;
                }

                foreach ($agents_list as $agent) {
                    if (is_array($agent) && isset($agent['id'])) {
                        $agents_to_store[$agent['id']] = $agent;
                    } elseif (is_object($agent) && isset($agent->id)) {
                        $agents_to_store[$agent->id] = (array) $agent;
                    }
                }
                
                if (!empty($agents_to_store)) {
                    update_option('build360_ai_agents', $agents_to_store);
                } elseif (empty($agents_list)) { // If API returns empty list after deletion
                    update_option('build360_ai_agents', array());
                }
                } else {
                // Log error if fetching all agents failed after delete, but proceed with success response for the delete itself.
                $log_message_list_fail = '[Build360 AI Warning] delete_agent_handler: Agent deleted successfully, but failed to refresh local agent list: ' . $all_agents_result->get_error_message();
                $this->custom_log($log_message_list_fail);
                 if (class_exists('WC_Logger')) {
                    $wc_logger = wc_get_logger();
                    $wc_logger->warning($log_message_list_fail, array( 'source' => 'build360-ai-delete-agent' ));
                }
            }
            $this->send_json_response($result); // Send the original success response from delete
        }
    }

    /**
     * Handle website activation AJAX request.
     */
    public function activate_website_handler() {
        check_ajax_referer('build360_ai_activate_website_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            $this->send_json_response(array(
                'message' => __('You do not have permission to perform this action.', 'build360-ai')
            ), false);
        }

        $submitted_domain = isset($_POST['domain']) ? sanitize_text_field($_POST['domain']) : '';
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $ip_address = isset($_POST['ip_address']) ? sanitize_text_field($_POST['ip_address']) : '';

        if (empty($submitted_domain) || empty($api_key) || empty($ip_address)) {
            $this->send_json_response(array(
                'message' => __('Domain, API Key, and IP Address are required.', 'build360-ai')
            ), false);
        }

        // Extract hostname from the submitted domain
        $parsed_url = wp_parse_url($submitted_domain);
        $domain_to_activate = isset($parsed_url['host']) ? $parsed_url['host'] : $submitted_domain;
        // Remove www. if present, as some APIs might prefer the apex domain
        // $domain_to_activate = preg_replace('/^www\./i', '', $domain_to_activate);
        // The above line for removing www. is optional and depends on your API requirements.
        // For now, let's use the host as parsed.

        // Validate the extracted hostname format
        if (!preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9][a-z0-9-]{0,61}[a-z0-9]$/i', $domain_to_activate)) {
            $this->send_json_response(array(
                 'message' => sprintf(__('Invalid domain name format after parsing: %s. Original: %s', 'build360-ai'), $domain_to_activate, $submitted_domain)
            ), false);
        }

        if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
            $this->send_json_response(array(
                'message' => __('Invalid IP Address format.', 'build360-ai')
            ), false);
        }

        $api = new Build360_AI_API();
        // Pass the cleaned $domain_to_activate to the API method
        $result = $api->activate_website($domain_to_activate, $api_key, $ip_address);

        if (is_wp_error($result)) {
            $this->send_json_response(array(
                'message' => $result->get_error_message(),
                'data' => $result->get_error_data() 
            ), false);
        } else {
            // $result is from Build360_AI_API::activate_website which we modified to return:
            // [ 'success' => true, 'message' => '...', 'data' => [ 'id' => WWW ] ]
            if (isset($result['success']) && $result['success'] === true && 
                isset($result['data']) && is_array($result['data']) &&
                isset($result['data']['id']) && !empty($result['data']['id'])) {
                
                // Save the website ID from Build360
                update_option('build360_ai_website_id', $result['data']['id']);
                
                // DO NOT update 'build360_ai_api_key' here. The key used for activation ($api_key parameter)
                // is the main account API key, which remains the active key.

                $success_message = isset($result['message']) ? $result['message'] : __('Website activated successfully and ID saved!', 'build360-ai');

                $this->send_json_response(array(
                    'message' => $success_message,
                    'website_id' => $result['data']['id'] // Send back the saved ID
                )); // Default is success = true
            } else {
                // This block should ideally not be reached if the API class method is correctly returning success/error.
                // However, if $result['success'] is false, or id is missing, this will be the path.
                $error_message = isset($result['message']) ? $result['message'] : __('Activation call succeeded but response structure was not as expected or indicated failure.', 'build360-ai');
                
                // For clearer debugging, pass the whole $result if it's not a WP_Error
                $debug_data = is_wp_error($result) ? $result->get_error_data() : $result;

                $this->send_json_response(array(
                    'message' => $error_message,
                    'debug_received_result' => $debug_data 
                ), false);
            }
        }
    }

    /**
     * List all AI agents for the website.
     */
    public function list_agents_handler() {
        check_ajax_referer('build360_ai_list_agents_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            $this->send_json_response(array('message' => __('Permission denied.', 'build360-ai')), false);
        }

        $result = $this->api->list_agents();

        if (is_wp_error($result)) {
            $this->send_json_response(array('message' => $result->get_error_message(), 'data' => $result->get_error_data()), false);
        } else {
            // $result is expected to be an array of agents or an object that can be cast to an array.
            // Example from API spec (conceptual):
            // { "success": true, "data": [ { "id": 1, "name": "Agent 1"}, {"id": 2, "name": "Agent 2"} ] }
            // Or directly: [ { "id": 1, "name": "Agent 1"}, {"id": 2, "name": "Agent 2"} ]

            $agents_to_store = array();
            if (isset($result['success']) && $result['success'] === true && isset($result['data']) && is_array($result['data'])) {
                // If response is wrapped like { "success": true, "data": [...] }
                $agents_list = $result['data'];
            } elseif (is_array($result)) {
                // If response is a direct array of agents
                $agents_list = $result;
            } else {
                // Unexpected format, log or handle error, but for now, don't update option
                $this->send_json_response($result); // Send original result if format is not recognized for option update
                return;
            }
            
            // Ensure we are storing an associative array keyed by agent ID for consistency
            // with how it might be used elsewhere (e.g., in the settings page dropdown).
            // The API class method list_agents() should ideally return this format.
            // If $agents_list is an indexed array of agent objects/arrays:
            // [ { "id": "xyz", "name": "My Agent" }, ... ]
            // we need to transform it to:
            // { "xyz": { "id": "xyz", "name": "My Agent" }, ... }

            foreach ($agents_list as $agent) {
                if (is_array($agent) && isset($agent['id'])) {
                    $agents_to_store[$agent['id']] = $agent;
                } elseif (is_object($agent) && isset($agent->id)) {
                    $agents_to_store[$agent->id] = (array) $agent; // Cast to array
                }
            }

            if (!empty($agents_to_store)) {
                update_option('build360_ai_agents', $agents_to_store);
            } else if (empty($agents_list)) { // If the API returns an empty list (no agents)
                update_option('build360_ai_agents', array()); // Ensure option is an empty array
            }
            // If agents_list was not empty but agents_to_store is (e.g. bad data format from API),
            // we don't update the option to avoid wiping potentially good old data with bad new data.
            // The original $result will be sent back in this case.

            $this->send_json_response($result); // Send the original API response back to the client
        }
    }

    private function custom_log($message) {
        if (!$this->log_file) {
            // Fallback to standard PHP error log if $this->log_file is not set (e.g. due to permission issues)
            error_log("(Build360 AI Custom Log Fallback) {$message}");
            return;
        }
        
        // Ensure log directory exists (might be redundant if constructor handles it, but good for safety)
        $log_dir = dirname($this->log_file);
        if (!is_dir($log_dir)) {
            if(!wp_mkdir_p($log_dir)) {
                 error_log("(Build360 AI Custom Log Fallback) Could not create log directory for: {$this->log_file}. Message: {$message}");
                 return;
            }
        }

        $timestamp = current_time('mysql');
        $log_entry = "[{$timestamp}] {$message}\n";
        if (file_put_contents($this->log_file, $log_entry, FILE_APPEND | LOCK_EX) === false) {
            error_log("(Build360 AI Custom Log Fallback) Could not write to log file: {$this->log_file}. Message: {$message}");
        }
    }
}