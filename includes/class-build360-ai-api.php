<?php
/**
 * API class for communicating with Build360 AI service
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Build360_AI_API {
    /**
     * API key (Website Specific API Key, e.g., wb_...)
     *
     * @var string
     */
    private $api_key;

    /**
     * API domain
     *
     * @var string
     */
    private $domain;

    /**
     * Website ID (from Build360 system, obtained after activation)
     *
     * @var string|int
     */
    private $website_id;

    /**
     * Constructor
     */
    public function __construct() {
        $this->api_key = get_option('build360_ai_api_key', '');
        $this->domain = get_option('build360_ai_domain', '');
        $this->website_id = get_option('build360_ai_website_id', '');

        // Trim trailing slash from domain if present
        $this->domain = rtrim($this->domain, '/');
    }

    /**
     * Check if API is configured
     *
     * @return bool
     */
    public function is_configured() {
        return !empty($this->api_key) && !empty($this->domain);
    }

    /**
     * Get API URL
     *
     * @param string $endpoint
     * @return string
     */
    private function get_api_url($endpoint) {
        return $this->domain . '/api/' . ltrim($endpoint, '/');
    }

    /**
     * Make API request
     *
     * @param string $endpoint
     * @param array $data
     * @param string $method
     * @param array|null $custom_headers Optional custom headers to override defaults.
     * @return array|WP_Error
     */
    public function request($endpoint, $data = array(), $method = 'POST', $custom_headers = null) {
        // Optional debug logging via WooCommerce logger if the admin enabled it.
        $debug_mode_enabled = get_option('build360_ai_debug_mode', '0');
        $use_wc_logger      = ($debug_mode_enabled === '1' || $debug_mode_enabled === 1);
        $wc_logger          = null;
        $wc_logger_context  = array( 'source' => 'build360-ai-api' );

        if ( $use_wc_logger && class_exists( 'WC_Logger' ) ) {
            $wc_logger = wc_get_logger();
        }

        // Check if API is configured
        if (!$this->is_configured() && $endpoint !== 'websites/activate') { // Allow activate_website even if not fully configured yet with site's API key
            return new WP_Error('api_not_configured', __('API is not configured. Please set API key and domain in the settings.', 'build360-ai'));
        }

        if ( $wc_logger ) {
            // Avoid logging sensitive Authorization header; show only token prefix.
            $redacted_headers = array();
            if ( is_array( $custom_headers ) ) {
                $redacted_headers = $custom_headers;
            }
            // Sanitize default headers too if they will be merged later
            $redacted_headers['Content-Type'] = 'application/json';
            $redacted_headers['Accept']       = 'application/json';
            if ( isset( $redacted_headers['Authorization'] ) ) {
                $redacted_headers['Authorization'] = substr( $redacted_headers['Authorization'], 0, 20 ) . '…';
            }

            $wc_logger->info( sprintf( 'API REQUEST → %s %s | Headers: %s | Body: %s', $method, $endpoint, wp_json_encode( $redacted_headers ), wp_json_encode( $data ) ), $wc_logger_context );
        }

        // Set up request arguments
        $default_headers = array(
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . $this->api_key,
        );

        $headers = is_array($custom_headers) ? $custom_headers : $default_headers;
        
        // For activate_website, we don't send an Authorization bearer token initially
        if ($endpoint === 'websites/activate') {
           unset($headers['Authorization']); // Remove Authorization if it was set by default path
        }

        $args = array(
            'method'    => $method,
            'timeout'   => 60,
            'headers'   => $headers,
        );

        // Add body for POST and PUT requests
        if (in_array($method, array('POST', 'PUT')) && !empty($data)) {
            $args['body'] = wp_json_encode($data);
        }

        // Make request
        $response = wp_remote_request($this->get_api_url($endpoint), $args);

        // Check for errors
        if (is_wp_error($response)) {
            if ( $wc_logger ) {
                $wc_logger->error( sprintf( 'API ERROR → %s %s | Error: %s', $method, $endpoint, $response->get_error_message() ), $wc_logger_context );
            }
            return $response;
        }

        // Get response code
        $response_code = wp_remote_retrieve_response_code($response);

        // Check if response is successful
        if ($response_code < 200 || $response_code >= 300) {
            $body = wp_remote_retrieve_body($response);
            $decoded_body = json_decode($body, true);
            
            $error_message = __('Unknown API error', 'build360-ai'); // Default message
            if (is_array($decoded_body) && isset($decoded_body['message'])) {
                $error_message = $decoded_body['message'];
            } elseif (!empty($body) && is_string($body)) {
                // If body is not JSON or JSON doesn't have 'message', use a snippet of the body.
                $error_message = sprintf(
                    __('API request failed with status %d. Response: %s', 'build360-ai'),
                    $response_code,
                    substr(strip_tags($body), 0, 100) // Show first 100 chars of non-JSON or non-standard JSON response
                );
            } elseif (empty($body)) {
                $error_message = sprintf(
                    __('API request failed with status %d and empty response body.', 'build360-ai'),
                    $response_code
                );
            }

            if ( $wc_logger ) {
                $wc_logger->error( sprintf( 'API ERROR ← %s %s | HTTP %d | Body: %s', $method, $endpoint, $response_code, $body ), $wc_logger_context );
            }

            return new WP_Error('api_error', $error_message, array(
                'status' => $response_code,
                'body'   => $body, // Return the raw body for further debugging if needed
                'data'   => $decoded_body // Keep original attempt to provide structured data
            ));
        }

        // Get response body
        $body = wp_remote_retrieve_body($response);

        // Parse JSON
        $data = json_decode($body, true);

        // Check if JSON decoding failed
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_decode_error', __('Failed to decode JSON response from API.', 'build360-ai'), array(
                'status' => $response_code,
                'body' => $body, // Include the raw body for debugging
                'json_error' => json_last_error_msg()
            ));
        }

        if ( $wc_logger ) {
            $wc_logger->info( sprintf( 'API RESPONSE ← %s %s | HTTP %d | Body: %s', $method, $endpoint, $response_code, $body ), $wc_logger_context );
        }

        // Return data
        return $data;
    }

    /**
     * Get account information
     *
     * @return array|WP_Error
     */
    public function get_account_info() {
        return $this->request('user', array(), 'GET');
    }

    /**
     * Get token balance
     *
     * @return array|WP_Error
     */
    public function get_token_balance() {
        // Directly call the specific endpoint for token balance
        $response = $this->request('user/token-balance', array(), 'GET');

        if (is_wp_error($response)) {
            return $response;
        }

        // Expected API response structure based on user clarification:
        // {
        //   "success": true,
        //   "token_balance": 141868, (Available Tokens)
        //   "website_tokens_used": 3132 (Used Tokens by this website)
        // }
        // The $this->request method returns the decoded JSON body directly.

        if (isset($response['success']) && $response['success'] === true && 
            isset($response['token_balance']) && isset($response['website_tokens_used'])) {
            
            return array(
                'available' => intval($response['token_balance']),
                'used'      => intval($response['website_tokens_used']),
            );
        }

        // If the response was not a WP_Error, or didn't contain the expected fields
        $error_message = __('Invalid response from server when fetching token balance.', 'build360-ai');
        if (isset($response['message'])) {
            $error_message = $response['message'];
        } elseif (is_array($response) && empty($response)){
            $error_message = __('Empty response from server when fetching token balance.', 'build360-ai');
        } elseif (is_array($response)) {
            $error_message = __('Token balance data not found in server response.', 'build360-ai');
        }
        
        return new WP_Error('token_balance_error', $error_message, $response);
    }

    /**
     * Test connection
     *
     * @return bool|WP_Error
     */
    public function test_connection() {
        $response = $this->request('test-website-token', array(), 'POST');

        if (is_wp_error($response)) {
            return $response;
        }

        if (isset($response['success']) && $response['success'] === true) {
            return true;
        }

        return new WP_Error('invalid_response', __('Invalid response from server', 'build360-ai'));
    }

    /**
     * Activate website
     *
     * @param string $domain The domain of the website to activate.
     * @param string $api_key The website-specific API key for activation.
     * @param string $ip_address The IP address of the website.
     * @return array|WP_Error The API response.
     */
    public function activate_website($domain, $api_key, $ip_address) {
        $payload = array(
            'domain'     => $domain,
            'api_key'    => $api_key, // This is the main account API key
            'ip_address' => $ip_address,
        );
        
        $custom_headers = array(
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        );

        $response = $this->request('websites/activate', $payload, 'POST', $custom_headers);

        if (is_wp_error($response)) {
            return $response; // Pass through WP_Error objects from request method
        }

        // If we reach here, the HTTP request was successful (2xx status code).
        // According to the user, the successful response body is e.g. {"id": 123, "message": "Activated."}
        
        if (isset($response['id']) && !empty($response['id'])) {
            // Successful activation, we have the website ID.
            // The API key used for future requests will be the main account API key,
            // which is already stored in WordPress options and loaded into $this->api_key by the constructor.
            // The AJAX handler will save the 'id' into 'build360_ai_website_id'.
            return [
                'success' => true, // Indicate success to the AJAX handler
                'message' => isset($response['message']) ? $response['message'] : __('Website activated successfully. ID received.', 'build360-ai'),
                'data'    => [
                    'id'      => $response['id'],
                    // No 'api_key' is returned by this specific endpoint to be re-saved.
                    // The main account API key remains the one in use.
                ]
            ];
        } else {
            // Activation response was 2xx, but did not contain the expected 'id'.
            $error_message = isset($response['message']) ? $response['message'] : __('Failed to activate website. API response did not include a website ID despite a successful HTTP status.', 'build360-ai');
            // Include the full response for debugging if it's an array/object
            $error_data = (is_array($response) || is_object($response)) ? $response : null; 
            return new WP_Error('api_activation_missing_id', $error_message, $error_data);
        }
    }

    /**
     * Generate content using a specific AI Agent.
     *
     * @param string $agent_id The ID of the AI agent to use.
     * @param string $content_context_type Type of content (e.g., 'post', 'product', 'taxonomy'). Used for context.
     * @param array  $external_api_data Data for the external API, including at least 'product_title', 'product_description', and potentially a base 'prompt' and 'fields_requested'.
     * @return array|WP_Error The API response (expected to be a map of generated fields) or WP_Error on failure.
     */
    public function generate_content($agent_id, $content_context_type, $external_api_data) {
        $all_agents_settings = get_option('build360_ai_agents', array());
        if (!isset($all_agents_settings[$agent_id])) {
            return new WP_Error('agent_not_found', __('AI Agent settings not found.', 'build360-ai'), array('agent_id' => $agent_id));
    }
        $agent_settings = $all_agents_settings[$agent_id];

        $api_text_style = isset($agent_settings['text_style']) ? $agent_settings['text_style'] : get_option('build360_ai_text_style', 'professional'); // Fallback to global
        $system_prompt_template = isset($agent_settings['system_prompt']) ? $agent_settings['system_prompt'] : "Generate content for {{title}}. Context: {{description}}"; // Default prompt
        // $content_settings_for_agent = isset($agent_settings['content_settings']) ? $agent_settings['content_settings'] : array(); // Field-specific settings like max length

        $base_title = isset($external_api_data['product_title']) ? $external_api_data['product_title'] : '';
        $base_description = !empty($external_api_data['product_description']) ? $external_api_data['product_description'] : '-';
        $fields_requested = isset($external_api_data['fields_requested']) ? $external_api_data['fields_requested'] : array();
        $categories = isset($external_api_data['categories']) ? $external_api_data['categories'] : '';
        $attributes = isset($external_api_data['attributes']) ? $external_api_data['attributes'] : '';
        $tags = isset($external_api_data['tags']) ? $external_api_data['tags'] : '';
        $keywords = isset($external_api_data['keywords']) ? $external_api_data['keywords'] : '';

        // Construct the final prompt using agent's system prompt and provided data
        // Placeholders like {{title}}, {{description}}, {{fields_requested}}, {{categories}}, {{attributes}}, {{tags}}, {{keywords}} can be used in the system_prompt_template.
        $final_prompt = str_replace(
            array('{{title}}', '{{description}}', '{{fields_requested}}', '{{categories}}', '{{attributes}}', '{{tags}}', '{{keywords}}'),
            array($base_title, $base_description, implode(', ', $fields_requested), $categories, $attributes, $tags, $keywords),
            $system_prompt_template
        );

        // The external API endpoint is /api/generate
        $endpoint = 'generate'; // Not 'generate/' . $content_context_type anymore for this specific API call

        // Backend API only accepts 'post' or 'taxonomy' as type.
        // Map WordPress content types to what the API expects.
        $api_type = $content_context_type;
        if (in_array($content_context_type, array('product', 'post', 'page'), true)) {
            $api_type = 'post';
        } elseif (in_array($content_context_type, array('category', 'product_cat', 'post_tag'), true)) {
            $api_type = 'taxonomy';
        }

        $request_body = array(
            'title'               => $base_title,
            'description'         => $base_description,
            'prompt'              => $final_prompt,
            'type'                => $api_type,
            'agent_id'            => $agent_id,
            'text_style'          => $api_text_style,
            'categories'          => $categories,
            'attributes'          => $attributes,
            'tags'                => $tags,
            'keywords'            => $keywords,
        );
        
        // TODO: Confirm which API key to use for Authorization: Bearer YOUR_WEBSITE_API_KEY
        // The $this->request method uses $this->api_key which is get_option('build360_ai_api_key') (the main one)
        // If a website-specific key (from activation) is needed for this endpoint, the logic in $this->request or here needs adjustment.
        // Based on previous understanding, the Authorization header is standard for most requests.

        return $this->request($endpoint, $request_body, 'POST');
    }

    /**
     * Bulk generate content
     *
     * @param string $content_type
     * @param array $items
     * @return array|WP_Error
     */
    public function bulk_generate_content($content_type, $items) {
        $endpoint = 'bulk-generate/' . $content_type;

        // Add style settings
        $data = array(
            'items' => $items,
            'text_style' => get_option('build360_ai_text_style', 'professional'),
        );

        // Add max length settings based on content type
        switch ($content_type) {
            case 'product':
                $data['max_length'] = get_option('build360_ai_max_product_text', 250);
                $data['max_desc_length'] = get_option('build360_ai_max_product_desc_text', 300);
                break;
            case 'post':
            case 'page':
                $data['max_length'] = get_option('build360_ai_max_blog_text', 350);
                break;
        }

        return $this->request($endpoint, $data);
    }

    /**
     * List all AI agents for the website.
     *
     * @return array|WP_Error
     */
    public function list_agents() {
        if (empty($this->website_id)) {
            return new WP_Error('website_not_activated', __('Website ID is missing. Please ensure the website is activated.', 'build360-ai'));
        }
        $endpoint = sprintf('websites/%s/agents', $this->website_id);
        return $this->request($endpoint, array(), 'GET');
    }

    /**
     * Create a new AI agent.
     *
     * @param array $agent_data Data for the new agent.
     * @return array|WP_Error
     */
    public function create_agent($agent_data) {
        if (empty($this->website_id)) {
            return new WP_Error('website_not_activated', __('Website ID is missing. Please ensure the website is activated.', 'build360-ai'));
        }
        $endpoint = sprintf('websites/%s/agents', $this->website_id);
        return $this->request($endpoint, $agent_data, 'POST');
    }

    /**
     * Get details for a specific AI agent.
     *
     * @param int $agent_id The ID of the agent.
     * @return array|WP_Error
     */
    public function get_agent($agent_id) {
        $endpoint = sprintf('agents/%s', $agent_id);
        return $this->request($endpoint, array(), 'GET');
    }

    /**
     * Update an existing AI agent.
     *
     * @param int $agent_id The ID of the agent to update.
     * @param array $agent_data Data to update for the agent.
     * @return array|WP_Error
     */
    public function update_agent($agent_id, $agent_data) {
        $endpoint = sprintf('agents/%s', $agent_id);
        return $this->request($endpoint, $agent_data, 'PUT');
    }

    /**
     * Delete an AI agent.
     *
     * @param int $agent_id The ID of the agent to delete.
     * @return array|WP_Error
     */
    public function delete_agent($agent_id) {
        $endpoint = sprintf('agents/%s', $agent_id);
        return $this->request($endpoint, array(), 'DELETE');
    }
}