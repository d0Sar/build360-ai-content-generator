<?php
/**
 * Main plugin class
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Build360_AI {
    /**
     * Instance of this class
     *
     * @var Build360_AI
     */
    protected static $instance = null;

    /**
     * API client instance
     *
     * @var Build360_AI_API
     */
    public $api = null;

    /**
     * Constructor
     */
    public function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Get instance of this class
     *
     * @return Build360_AI
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Include required files
     */
    private function includes() {
        // Core classes
        require_once BUILD360_AI_PLUGIN_DIR . 'includes/class-build360-ai-api.php';
        require_once BUILD360_AI_PLUGIN_DIR . 'includes/class-build360-ai-settings.php';
        require_once BUILD360_AI_PLUGIN_DIR . 'includes/class-build360-ai-generator.php';
        require_once BUILD360_AI_PLUGIN_DIR . 'includes/class-build360-ai-product-integration.php';
        require_once BUILD360_AI_PLUGIN_DIR . 'includes/class-build360-ai-post-integration.php';
        require_once BUILD360_AI_PLUGIN_DIR . 'includes/class-build360-ai-taxonomy-integration.php';
        require_once BUILD360_AI_PLUGIN_DIR . 'includes/class-build360-ai-ajax.php';
        
        // Admin classes
        if (is_admin()) {
            require_once BUILD360_AI_PLUGIN_DIR . 'admin/class-build360-ai-admin.php';
        }
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Register activation and deactivation hooks
        register_activation_hook(BUILD360_AI_PLUGIN_BASENAME, array($this, 'activate'));
        register_deactivation_hook(BUILD360_AI_PLUGIN_BASENAME, array($this, 'deactivate'));
        
        // Initialize admin
        if (is_admin()) {
            $admin = new Build360_AI_Admin();
            $admin->init();
        }
        
        // Add settings link on plugins page
        add_filter('plugin_action_links_' . BUILD360_AI_PLUGIN_BASENAME, array($this, 'add_settings_link'));
        
        // Add scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Initialize API
        $this->api = new Build360_AI_API();
    }

    /**
     * Initialize the plugin
     */
    public function init() {
        // Initialize settings
        $settings = new Build360_AI_Settings();
        $settings->init();
        
        // Initialize product integration
        $product_integration = new Build360_AI_Product_Integration();
        $product_integration->init();

        // Initialize post/page integration
        $post_integration = new Build360_AI_Post_Integration();
        $post_integration->init();

        // Initialize taxonomy integration
        $taxonomy_integration = new Build360_AI_Taxonomy_Integration();
        $taxonomy_integration->init();

        // Initialize AJAX handlers
        $ajax = new Build360_AI_Ajax();
        $ajax->init();
        
        // Add init hook for other plugins to extend
        do_action('build360_ai_init', $this);
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Add activation code here
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Add deactivation code here
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
     * Enqueue admin assets
     */
    public function enqueue_admin_assets() {
        // Add asset enqueuing code here
    }
} 