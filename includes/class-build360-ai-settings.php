<?php
/**
 * Settings class for Build360 AI plugin
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Build360_AI_Settings {
    /**
     * Constructor
     */
    public function __construct() {
        // Nothing to do here
    }

    /**
     * Initialize settings
     */
    public function init() {
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // API Settings
        register_setting('build360_ai_api_settings', 'build360_ai_api_key');
        register_setting('build360_ai_api_settings', 'build360_ai_domain');
        
        // AI Model Settings
        register_setting('build360_ai_model_settings', 'build360_ai_model');
        register_setting('build360_ai_model_settings', 'build360_ai_text_style');
        register_setting('build360_ai_model_settings', 'build360_ai_max_product_text');
        register_setting('build360_ai_model_settings', 'build360_ai_max_product_desc_text');
        register_setting('build360_ai_model_settings', 'build360_ai_max_blog_text');
        
        // Content Type Settings
        register_setting('build360_ai_content_settings', 'build360_ai_content_types');

        // Token Usage Settings
        register_setting('build360_ai_token_settings', 'build360_ai_token_limit');
        register_setting('build360_ai_token_settings', 'build360_ai_token_usage');
        register_setting('build360_ai_token_settings', 'build360_ai_token_usage_today');
        register_setting('build360_ai_token_settings', 'build360_ai_token_usage_month');
        register_setting('build360_ai_token_settings', 'build360_ai_token_usage_last_reset');
    }

    /**
     * Get available AI models
     *
     * @return array
     */
    public function get_ai_models() {
        return array(
            'gpt-4o' => __('GPT-4o (Latest)', 'build360-ai'),
            'gpt-4' => __('GPT-4', 'build360-ai'),
            'gpt-4-turbo' => __('GPT-4 Turbo', 'build360-ai'),
            'gpt-3.5-turbo' => __('GPT-3.5 Turbo', 'build360-ai'),
            'claude-3-opus' => __('Claude 3 Opus', 'build360-ai'),
            'claude-3-sonnet' => __('Claude 3 Sonnet', 'build360-ai'),
            'claude-3-haiku' => __('Claude 3 Haiku', 'build360-ai'),
            'gemini-pro' => __('Gemini Pro', 'build360-ai'),
            'llama-3' => __('Llama 3', 'build360-ai'),
        );
    }

    /**
     * Get available text styles
     *
     * @return array
     */
    public function get_text_styles() {
        return array(
            'formal' => __('Formal', 'build360-ai'),
            'casual' => __('Casual', 'build360-ai'),
            'professional' => __('Professional', 'build360-ai'),
            'friendly' => __('Friendly', 'build360-ai'),
        );
    }

    /**
     * Get available max text lengths
     *
     * @return array
     */
    public function get_max_text_lengths() {
        return array(
            '100' => __('100 chars', 'build360-ai'),
            '150' => __('150 chars', 'build360-ai'),
            '200' => __('200 chars', 'build360-ai'),
            '250' => __('250 chars', 'build360-ai'),
            '300' => __('300 chars', 'build360-ai'),
            '350' => __('350 chars', 'build360-ai'),
            '400' => __('400 chars', 'build360-ai'),
        );
    }

    /**
     * Save settings
     *
     * @param array $data
     * @return bool
     */
    public function save_settings($data) {
        // Save API settings
        if (isset($data['api_key'])) {
            update_option('build360_ai_api_key', sanitize_text_field($data['api_key']));
        }
        
        if (isset($data['domain'])) {
            update_option('build360_ai_domain', sanitize_text_field($data['domain']));
        }
        
        // Save AI model settings
        if (isset($data['model'])) {
            update_option('build360_ai_model', sanitize_text_field($data['model']));
        }
        
        if (isset($data['text_style'])) {
            update_option('build360_ai_text_style', sanitize_text_field($data['text_style']));
        }
        
        if (isset($data['max_product_text'])) {
            update_option('build360_ai_max_product_text', sanitize_text_field($data['max_product_text']));
        }
        
        if (isset($data['max_product_desc_text'])) {
            update_option('build360_ai_max_product_desc_text', sanitize_text_field($data['max_product_desc_text']));
        }
        
        if (isset($data['max_blog_text'])) {
            update_option('build360_ai_max_blog_text', sanitize_text_field($data['max_blog_text']));
        }
        
        // Save content type settings
        if (isset($data['content_types'])) {
            $content_types = array();
            
            // Sanitize content types
            foreach ($data['content_types'] as $type => $fields) {
                $content_types[$type] = array();
                foreach ($fields as $field => $enabled) {
                    $content_types[$type][$field] = (bool) $enabled;
                }
            }
            
            update_option('build360_ai_content_types', $content_types);
        }
        
        // Save Debug Mode setting (on/off)
        if (isset($data['build360_ai_debug_mode'])) {
            // Normalize value to '1' or '0'
            $debug_value = ($data['build360_ai_debug_mode'] === '1' || $data['build360_ai_debug_mode'] === 1 || $data['build360_ai_debug_mode'] === true) ? '1' : '0';
            update_option('build360_ai_debug_mode', $debug_value);
        }
        
        return true;
    }

    /**
     * Get settings
     *
     * @return array
     */
    public static function get_settings() {
        return array(
            'api_key' => get_option('build360_ai_api_key', ''),
            'domain' => get_option('build360_ai_domain', ''),
            'model' => get_option('build360_ai_model', 'gpt-4'),
            'text_style' => get_option('build360_ai_text_style', 'professional'),
            'max_product_text' => get_option('build360_ai_max_product_text', '200'),
            'max_product_desc_text' => get_option('build360_ai_max_product_desc_text', '400'),
            'max_blog_text' => get_option('build360_ai_max_blog_text', '1000'),
            'content_types' => get_option('build360_ai_content_types', array())
        );
    }

    /**
     * Get content types and their fields
     *
     * @return array
     */
    public function get_content_types() {
        return array(
            'product' => array(
                'label' => __('Product Content', 'build360-ai'),
                'fields' => array(
                    'title' => array(
                        'label' => __('Product Title', 'build360-ai'),
                        'max_length' => 100
                    ),
                    'description' => array(
                        'label' => __('Product Description', 'build360-ai'),
                        'max_length' => 2000
                    ),
                    'short_description' => array(
                        'label' => __('Short Description', 'build360-ai'),
                        'max_length' => 300
                    ),
                    'meta_title' => array(
                        'label' => __('Meta Title', 'build360-ai'),
                        'max_length' => 60
                    ),
                    'meta_description' => array(
                        'label' => __('Meta Description', 'build360-ai'),
                        'max_length' => 160
                    ),
                    'image_alt' => array(
                        'label' => __('Image Alt Text', 'build360-ai'),
                        'max_length' => 100
                    )
                )
            ),
            'category' => array(
                'label' => __('Category Content', 'build360-ai'),
                'fields' => array(
                    'title' => array(
                        'label' => __('Category Title', 'build360-ai'),
                        'max_length' => 100
                    ),
                    'description' => array(
                        'label' => __('Category Description', 'build360-ai'),
                        'max_length' => 1000
                    ),
                    'meta_title' => array(
                        'label' => __('Meta Title', 'build360-ai'),
                        'max_length' => 60
                    ),
                    'meta_description' => array(
                        'label' => __('Meta Description', 'build360-ai'),
                        'max_length' => 160
                    )
                )
            )
        );
    }

    /**
     * Get content type fields
     *
     * @param string $content_type
     * @return array
     */
    public function get_content_type_fields($content_type) {
        $content_types = $this->get_content_types();
        
        if (isset($content_types[$content_type])) {
            return $content_types[$content_type];
        }
        
        return array();
    }

    /**
     * Check if field is enabled for content type
     *
     * @param string $content_type
     * @param string $field
     * @return bool
     */
    public function is_field_enabled($content_type, $field) {
        $fields = $this->get_content_type_fields($content_type);
        
        if (isset($fields[$field])) {
            return (bool) $fields[$field];
        }
        
        return false;
    }

    /**
     * Get enabled fields for content type
     *
     * @param string $content_type
     * @return array
     */
    public function get_enabled_fields($content_type) {
        $fields = $this->get_content_type_fields($content_type);
        $enabled = array();
        
        foreach ($fields as $field => $enabled_status) {
            if ($enabled_status) {
                $enabled[] = $field;
            }
        }
        
        return $enabled;
    }

    /**
     * Get token usage statistics
     *
     * @return array Token usage data
     */
    public function get_token_usage() {
        $api = new Build360_AI_API();
        $live_token_data = $api->get_token_balance(); // This calls the external API

        // Default values in case API call fails or returns unexpected data
        $default_total_tokens = get_option('build360_ai_token_limit', 1000000); // Fallback total
        $default_used_tokens = get_option('build360_ai_token_usage', 0);      // Fallback used
        $default_remaining_tokens = max(0, $default_total_tokens - $default_used_tokens);

        $total_tokens = $default_total_tokens;
        $used_tokens_by_site = $default_used_tokens;
        $remaining_tokens_on_account = $default_remaining_tokens;

        if (!is_wp_error($live_token_data) && isset($live_token_data['available']) && isset($live_token_data['used'])) {
            // 'available' from API is the current remaining balance on the user's account.
            // 'used' from API is what this specific website has consumed.
            $current_remaining_on_account = intval($live_token_data['available']);
            $consumed_by_this_site = intval($live_token_data['used']);

            $remaining_tokens_on_account = $current_remaining_on_account;
            $used_tokens_by_site = $consumed_by_this_site;
            // Calculate the effective total for this site's context for progress bar logic
            // This is the current remaining balance + what this site has already used.
            $total_tokens = $current_remaining_on_account + $consumed_by_this_site;
            
            // Optionally, update WordPress options if you want to store a snapshot
            // update_option('build360_ai_token_limit', $total_tokens); // Effective total for site context
            // update_option('build360_ai_token_usage', $used_tokens_by_site); // Site specific usage
        } else {
            if (is_wp_error($live_token_data)) {
                error_log('Build360 AI: Error fetching token balance from API: ' . $live_token_data->get_error_message());
            }
            // Fallback to stored options if API fails
            // $total_tokens, $used_tokens_by_site, $remaining_tokens_on_account are already set to defaults from options
        }
        
        // Local daily/monthly tracking (can be kept or removed based on requirements)
        $this->maybe_reset_token_counters();
        $used_today = get_option('build360_ai_token_usage_today', 0);
        $used_month = get_option('build360_ai_token_usage_month', 0);
        
        return array(
            'total'     => $total_tokens,                 // Effective total for this website's usage context
            'used'      => $used_tokens_by_site,          // What this website has used
            'remaining' => $remaining_tokens_on_account,  // Actual remaining on the user's account
            'used_today'=> intval($used_today),          // Local tracking
            'used_month'=> intval($used_month),          // Local tracking
        );
    }

    /**
     * Update token usage
     *
     * @param int $tokens Number of tokens used
     * @return void
     */
    public function update_token_usage($tokens) {
        // Get current values
        $used_tokens = get_option('build360_ai_token_usage', 0);
        $used_today = get_option('build360_ai_token_usage_today', 0);
        $used_month = get_option('build360_ai_token_usage_month', 0);
        
        // Update values
        update_option('build360_ai_token_usage', $used_tokens + $tokens);
        update_option('build360_ai_token_usage_today', $used_today + $tokens);
        update_option('build360_ai_token_usage_month', $used_month + $tokens);
    }

    /**
     * Reset token usage counters if needed
     *
     * @return void
     */
    private function maybe_reset_token_counters() {
        $last_reset = get_option('build360_ai_token_usage_last_reset');
        $now = current_time('timestamp');
        
        if (!$last_reset) {
            $last_reset = $now;
            update_option('build360_ai_token_usage_last_reset', $now);
        }
        
        $last_reset_date = date('Y-m-d', $last_reset);
        $current_date = date('Y-m-d', $now);
        
        // Reset daily counter if it's a new day
        if ($last_reset_date !== $current_date) {
            update_option('build360_ai_token_usage_today', 0);
        }
        
        // Reset monthly counter if it's a new month
        if (date('Y-m', $last_reset) !== date('Y-m', $now)) {
            update_option('build360_ai_token_usage_month', 0);
        }
        
        // Update last reset time
        if ($last_reset_date !== $current_date) {
            update_option('build360_ai_token_usage_last_reset', $now);
        }
    }
} 