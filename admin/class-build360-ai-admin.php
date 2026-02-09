<?php
/**
 * The admin-specific functionality of the plugin.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Build360_AI_Admin {
    /**
     * Generator instance
     *
     * @var Build360_AI_Generator
     */
    private $generator;

    /**
     * Settings instance
     *
     * @var Build360_AI_Settings
     */
    private $settings;

    /**
     * Initialize the class
     */
    public function init() {
        // Load dependencies
        $this->generator = new Build360_AI_Generator();
        $this->settings = new Build360_AI_Settings();

        // Add admin menu
        add_action('admin_menu', array($this, 'add_plugin_menu'));

        // Add settings link to plugins page
        add_filter('plugin_action_links_' . BUILD360_AI_PLUGIN_BASENAME, array($this, 'add_settings_link'));

        // Register settings
        add_action('admin_init', array($this, 'register_settings'));

        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * Add plugin menu items
     */
    public function add_plugin_menu() {
        add_menu_page(
            __('Build360 AI', 'build360-ai'),
            __('Build360 AI', 'build360-ai'),
            'manage_options',
            'build360-ai',
            array($this, 'render_dashboard_page'),
            'dashicons-text-page',
            56
        );

        // First submenu is automatically added by WordPress for the main menu page

        add_submenu_page(
            'build360-ai',
            __('AI Agents', 'build360-ai'),
            __('AI Agents', 'build360-ai'),
            'manage_options',
            'build360-ai-agents',
            array($this, 'render_agents_page')
        );

        add_submenu_page(
            'build360-ai',
            __('Settings', 'build360-ai'),
            __('Settings', 'build360-ai'),
            'manage_options',
            'build360-ai-settings',
            array($this, 'render_settings_page')
        );

        add_submenu_page(
            'build360-ai',
            __('Activity Log', 'build360-ai'),
            __('Activity Log', 'build360-ai'),
            'manage_options',
            'build360-ai-activity-log',
            array($this, 'render_activity_log_page')
        );
    }

    /**
     * Add settings link to plugin listing
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=build360-ai-settings') . '">' . __('Settings', 'build360-ai') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('build360_ai_settings', 'build360_ai_api_key');
        register_setting('build360_ai_settings', 'build360_ai_domain');
        register_setting('build360_ai_settings', 'build360_ai_model');
        register_setting('build360_ai_settings', 'build360_ai_text_style');
        register_setting('build360_ai_settings', 'build360_ai_max_product_text');
        register_setting('build360_ai_settings', 'build360_ai_max_product_desc_text');
        register_setting('build360_ai_settings', 'build360_ai_max_blog_text');
        register_setting('build360_ai_settings', 'build360_ai_content_types');
    }

    /**
     * Add meta boxes to product pages
     */
    public function add_product_meta_boxes() {
        // Removed duplicate meta box registration
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_assets($hook_suffix) {
        // Check if we are on a product edit page or a plugin page
        $screen = get_current_screen();
        $is_product_edit_page = ($screen && $screen->post_type === 'product' && ($hook_suffix === 'post.php' || $hook_suffix === 'post-new.php'));
        $is_plugin_page = (strpos($hook_suffix, 'build360-ai') !== false);

        if (!$is_product_edit_page && !$is_plugin_page) {
            return;
        }

        // Enqueue Font Awesome (common for all our pages)
        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            array(),
            '6.4.0'
        );

        // Enqueue main admin CSS (common for all our pages)
        // Ensure the path is correct, as per previous discussion, it should be css/admin.css
        $main_css_path = BUILD360_AI_PLUGIN_DIR . 'css/admin.css';
        if (file_exists($main_css_path)) {
        wp_enqueue_style(
                'build360-ai-admin-main', // Renamed handle to avoid conflict if 'build360-ai-admin' is used for JS
            BUILD360_AI_PLUGIN_URL . 'css/admin.css',
            array(),
                filemtime($main_css_path)
            );
        } else {
            // Fallback or error logging if the main CSS is not found
            // For now, let's try the other path as a fallback, though this should be standardized
            $fallback_css_path = BUILD360_AI_PLUGIN_DIR . 'admin/css/build360-ai-admin.css';
            if (file_exists($fallback_css_path)) {
        wp_enqueue_style(
                    'build360-ai-admin-main',
                    BUILD360_AI_PLUGIN_URL . 'admin/css/build360-ai-admin.css',
                    array(),
                    filemtime($fallback_css_path)
        );
            }
        }


        // Enqueue utilities script first (common for all our pages)
        wp_enqueue_script(
            'build360-ai-utils',
            BUILD360_AI_PLUGIN_URL . 'js/build360-ai-utils.js',
            array('jquery'),
            BUILD360_AI_VERSION,
            true
        );

        $localization_data = $this->get_localized_data(); // Base localization data

        if ($is_plugin_page) {
            // Enqueue admin script (js/admin.js) for plugin's own admin pages
        wp_enqueue_script(
                'build360-ai-admin', // This is js/admin.js
            BUILD360_AI_PLUGIN_URL . 'js/admin.js',
            array('jquery', 'build360-ai-utils'),
                BUILD360_AI_VERSION, // Use consistent versioning
            true
        );
            wp_localize_script('build360-ai-admin', 'build360_ai_vars', $localization_data);

            // Enqueue test styles
            $test_css_path = BUILD360_AI_PLUGIN_DIR . 'css/build360-ai-test.css';
            if (file_exists($test_css_path)) {
                wp_enqueue_style(
                    'build360-ai-test',
                    BUILD360_AI_PLUGIN_URL . 'css/build360-ai-test.css',
                    array('build360-ai-admin-main'), // Depends on the main admin CSS
                    BUILD360_AI_VERSION
                );
            }

            // Enqueue test scripts (js/build360-ai-test.js)
        wp_enqueue_script(
            'build360-ai-test',
            BUILD360_AI_PLUGIN_URL . 'js/build360-ai-test.js',
            array('jquery', 'build360-ai-utils', 'build360-ai-admin'),
            BUILD360_AI_VERSION,
            true
        );
            wp_localize_script('build360-ai-test', 'build360_ai_vars', $localization_data);


        // Enqueue agents scripts
        if (strpos($hook_suffix, 'build360-ai-agents') !== false) {
            wp_enqueue_script(
                'build360-ai-agents',
                BUILD360_AI_PLUGIN_URL . 'js/build360-ai-agents.js',
                array('jquery', 'build360-ai-utils'),
                    rand(10000, 99999), // Use rand() for cache busting during development
                true
            );
                wp_localize_script('build360-ai-agents', 'build360_ai_vars', $localization_data);
        }

        // Enqueue settings scripts
        if (strpos($hook_suffix, 'build360-ai-settings') !== false) {
            wp_enqueue_script(
                'build360-ai-settings',
                BUILD360_AI_PLUGIN_URL . 'js/build360-ai-settings.js',
                array('jquery', 'build360-ai-utils'),
                BUILD360_AI_VERSION,
                true
            );
                wp_localize_script('build360-ai-settings', 'build360_ai_vars', $localization_data);
            }
        }

        if ($is_product_edit_page) {
            // Enqueue product metabox specific script
            // Assuming js/build360-ai-product.js is enqueued with handle 'build360-ai-product'
            // This enqueue might happen in class-build360-ai-product-integration.php,
            // but we add localization here if the script is already enqueued.
            // For safety, let's enqueue it here if not already done, making sure it has 'jquery' and 'build360-ai-utils' as deps.

                wp_enqueue_script(
                    'build360-ai-product',
                    BUILD360_AI_PLUGIN_URL . 'js/build360-ai-product.js',
                    array('jquery', 'build360-ai-utils'),
                    rand(10000, 99999), // Changed to rand() for cache busting
                    true
                );
            
            // Prepare specific localization for product page
            $product_localization_data = $localization_data; // Start with base data
            
            $current_post_id = get_the_ID();
            $post_type = get_post_type($current_post_id);
            $current_agent_id = null;

            if ($post_type) {
                $agent_assignments = get_option('build360_ai_agent_assignments', array());
                foreach ($agent_assignments as $assignment) {
                    if (isset($assignment['type']) && $assignment['type'] === $post_type && isset($assignment['agent_id'])) {
                        $current_agent_id = $assignment['agent_id'];
                        break;
            }
        }
            }
            $product_localization_data['current_agent_id'] = $current_agent_id;
            $product_localization_data['post_id'] = $current_post_id;
            
            // Ensure the nonce for generate_content is present
            if (!isset($product_localization_data['nonces']['generate_content'])) {
                 $product_localization_data['nonces']['generate_content'] = wp_create_nonce('build360_ai_generate_content');
            }

            wp_localize_script('build360-ai-product', 'build360_ai_vars', $product_localization_data);

            // Also enqueue metabox specific CSS if any, e.g., css/build360-ai.css
            $metabox_css_path = BUILD360_AI_PLUGIN_DIR . 'css/build360-ai.css';
            if (file_exists($metabox_css_path)) {
                 wp_enqueue_style(
                    'build360-ai-metabox',
                    BUILD360_AI_PLUGIN_URL . 'css/build360-ai.css',
                    array(),
                    filemtime($metabox_css_path)
                );
            }
        }
    }

    /**
     * Get localized data for scripts
     *
     * @return array
     */
    private function get_localized_data() {
        // Get the site URL and parse it to get just the host (domain name)
        $site_url = get_site_url();
        $parsed_site_url = wp_parse_url($site_url);
        $site_host = isset($parsed_site_url['host']) ? $parsed_site_url['host'] : '';

        return array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonces' => array(
                 // Ensure all nonces are created here
                'save_settings' => wp_create_nonce('build360_ai_save_settings'),
                'activate_website' => wp_create_nonce('build360_ai_activate_website'),
                'test_connection' => wp_create_nonce('build360_ai_test_connection'),
                'generate_content' => wp_create_nonce('build360_ai_generate_content'), // Added for metabox
                'get_posts' => wp_create_nonce('build360_ai_get_posts_nonce'), // Example, if needed
                'delete_agent_assignment' => wp_create_nonce('build360_ai_delete_agent_assignment_nonce'), // Example for agent assignments
                'list_agents' => wp_create_nonce('build360_ai_list_agents_nonce'),
                'get_agent_details' => wp_create_nonce('build360_ai_get_agent_details_nonce'),
                'save_agent' => wp_create_nonce('build360_ai_save_agent_nonce'),
                'delete_agent' => wp_create_nonce('build360_ai_delete_agent_nonce'),
                'get_token_balance' => wp_create_nonce('build360_ai_get_token_balance_nonce'),
            ),
            'strings' => array(
                'confirm_delete_assignment' => __('Are you sure you want to delete this assignment?', 'build360-ai'),
                'settings_saved' => __('Settings saved successfully.', 'build360-ai'),
                'error_saving_settings' => __('Error saving settings.', 'build360-ai'),
                'activation_successful' => __('Website activated successfully!', 'build360-ai'),
                'activation_failed' => __('Website activation failed.', 'build360-ai'),
                'connection_successful' => __('Connection successful!', 'build360-ai'),
                'connection_failed' => __('Connection failed.', 'build360-ai'),
                'no_fields_selected' => __('Please select at least one field to generate.', 'build360-ai'),
                'agent_not_assigned' => __('No AI Agent is assigned to this content type. Please check plugin settings.', 'build360-ai'),
                'nonce_error' => __('Security token is missing. Please refresh the page.', 'build360-ai'),
                'ajax_error' => __('An error occurred while processing your request.', 'build360-ai'),
                'select_agent' => __('-- Select AI Agent --', 'build360-ai'),
                'select_content_type' => __('-- Select Content Type --', 'build360-ai'),
                // from build360-ai-test.js, good to have them centralized
                'description' => __('Description', 'build360-ai'),
                'short_description' => __('Short Description', 'build360-ai'),
                'meta_title' => __('Meta Title', 'build360-ai'),
                'meta_description' => __('Meta Description', 'build360-ai'),
                'content' => __('Content', 'build360-ai'),
                'excerpt' => __('Excerpt', 'build360-ai'),
                 'remove_assignment' => __('Remove', 'build360-ai'),
                 'add_assignment' => __('Add Assignment', 'build360-ai'),
                 // Add any other strings your JS files might need
            ),
            'api_details' => array(
                'api_endpoint_domain' => get_option('build360_ai_domain', 'https://api.build360.ai'), // Centralize API domain
                'api_key' => get_option('build360_ai_api_key', ''), // Main API Key
                'ip_address' => $_SERVER['SERVER_ADDR'] ?? 'not_available', // Server IP
                'wordpress_site_host' => $site_host, // Site Host
            ),
            // Provide content types and agents for dynamic dropdowns in JS (e.g. for settings page)
            // These might not be strictly needed for build360-ai-product.js but are good for build360_ai_vars completeness
             'content_types' => $this->get_js_content_types(),
             'agents' => $this->get_js_agents(),
            // Alias to maintain backward-compatibility with JS that expects build360_ai_vars.i18n
            'i18n' => array(
                'characters'               => __('characters', 'build360-ai'),
                'recommended_short'        => __('Recommended for short copy such as titles or alt text.', 'build360-ai'),
                'recommended_medium'       => __('Recommended for medium copy such as meta descriptions.', 'build360-ai'),
                'recommended_long'         => __('Recommended for detailed copy like full descriptions.', 'build360-ai'),
                'hide_api_details'         => __('Hide API Details', 'build360-ai'),
                'show_api_details'         => __('Show API Details', 'build360-ai'),
                'enter_api_details'        => __('Please enter API Key and Domain first.', 'build360-ai'),
                'ajax_error'               => __('Ajax error', 'build360-ai'),
                'saving'                   => __('Saving...', 'build360-ai'),
                'length_guide_short_label' => __('Short (100-150 characters)', 'build360-ai'),
                'length_guide_medium_label'=> __('Medium (200-300 characters)', 'build360-ai'),
                'length_guide_long_label'  => __('Long (350+ characters)', 'build360-ai'),
                'length_guide_short_desc'  => __('Suitable for titles, alt text', 'build360-ai'),
                'length_guide_medium_desc' => __('Suitable for short descriptions, meta descriptions', 'build360-ai'),
                'length_guide_long_desc'   => __('Suitable for full descriptions, blog content', 'build360-ai'),
            ),
        );
    }
    
    private function get_js_content_types() {
        $wp_post_types = get_post_types(array('public' => true, 'show_ui' => true), 'objects');
        $wp_taxonomies = get_taxonomies(array('public' => true, 'show_ui' => true), 'objects');
        $content_types = array();

        foreach ($wp_post_types as $post_type) {
            $content_types[$post_type->name] = $post_type->label;
        }
        foreach ($wp_taxonomies as $taxonomy) {
            $content_types[$taxonomy->name] = $taxonomy->label;
        }
        return $content_types;
    }

    private function get_js_agents() {
        $agents_option = get_option('build360_ai_agents', array());
        $agents = array();
        if (is_array($agents_option)) {
            foreach ($agents_option as $agent_id => $agent_data) {
                $agents[] = array( // Changed to array of objects for easier iteration in JS
                    'id' => $agent_id,
                    'name' => isset($agent_data['name']) ? $agent_data['name'] : sprintf(__('Agent %s', 'build360-ai'), $agent_id)
                );
            }
        }
        return $agents;
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        require_once BUILD360_AI_PLUGIN_DIR . 'admin/partials/dashboard.php';
    }

    /**
     * Render products page
     */
    public function render_products_page() {
        require_once BUILD360_AI_PLUGIN_DIR . 'admin/partials/products.php';
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        require_once BUILD360_AI_PLUGIN_DIR . 'admin/partials/settings.php';
    }

    /**
     * Render agents page
     */
    public function render_agents_page() {
        require_once BUILD360_AI_PLUGIN_DIR . 'admin/partials/agents.php';
    }

    /**
     * Render Activity Log page
     */
    public function render_activity_log_page() {
        require_once BUILD360_AI_PLUGIN_DIR . 'admin/partials/activity-log.php';
    }

    /**
     * Render product meta box
     */
    public function render_product_meta_box($post) {
        require_once BUILD360_AI_PLUGIN_DIR . 'admin/partials/product-meta-box.php';
    }

    /**
     * Get product categories as string
     */
    private function get_product_categories($product) {
        $terms = get_the_terms($product->get_id(), 'product_cat');
        if (!$terms || is_wp_error($terms)) {
            return '';
        }
        return implode(', ', wp_list_pluck($terms, 'name'));
    }

    /**
     * Get product attributes
     */
    private function get_product_attributes($product) {
        $attributes = array();
        foreach ($product->get_attributes() as $attribute) {
            if ($attribute->is_taxonomy()) {
                $terms = wp_get_post_terms($product->get_id(), $attribute->get_name());
                if (!is_wp_error($terms)) {
                    $attributes[$attribute->get_name()] = implode(', ', wp_list_pluck($terms, 'name'));
                }
            } else {
                $attributes[$attribute->get_name()] = $attribute->get_options();
            }
        }
        return $attributes;
    }

    /**
     * Get product keywords (from tags)
     */
    private function get_product_keywords($product) {
        $terms = get_the_terms($product->get_id(), 'product_tag');
        if (!$terms || is_wp_error($terms)) {
            return array();
        }
        return wp_list_pluck($terms, 'name');
    }
}