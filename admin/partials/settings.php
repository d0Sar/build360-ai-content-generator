<?php
/**
 * Admin settings template
 *
 * @package Build360_AI
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Initialize settings
$settings_instance = new Build360_AI_Settings();

// Get settings
$settings = $settings_instance->get_settings();
$models = $settings_instance->get_ai_models();
$text_styles = $settings_instance->get_text_styles();
$max_lengths = $settings_instance->get_max_text_lengths();

// Get content types
$content_types = $settings_instance->get_content_types();

// Get API instance
$api = new Build360_AI_API();
$token_balance = $api->get_token_balance();
$api_configured = $api->is_configured();
?>

<div class="wrap build360-ai-settings-wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="build360-ai-settings-intro">
        <p><?php _e('Configure your Build360 AI Content Generator settings below. These settings control how the AI generates content for your products, posts, and pages.', 'build360-ai'); ?></p>
    </div>

    <?php if (isset($_GET['settings-updated']) && $_GET['settings-updated']): ?>
        <div class="notice notice-success">
            <p><?php _e('Settings saved successfully!', 'build360-ai'); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="options.php" id="build360-ai-main-settings-form">
        <?php settings_fields('build360_ai_settings'); ?>
        <?php do_settings_sections('build360_ai_settings'); ?>

        <div class="build360-ai-card">
        <div class="build360-ai-settings-section">
            <h2><span class="dashicons dashicons-admin-network"></span> <?php _e('API Configuration', 'build360-ai'); ?></h2>
            <p class="build360-ai-section-description">
                <?php _e('Connect to the Build360 AI service by entering your API credentials below. These are required for the plugin to function.', 'build360-ai'); ?>
            </p>

            <div class="build360-ai-connection-status">
                <?php if ($api_configured): ?>
                    <div class="build360-ai-status-connected">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php _e('Connected to Build360 AI service', 'build360-ai'); ?>
                    </div>
                <?php else: ?>
                    <div class="build360-ai-status-disconnected">
                        <span class="dashicons dashicons-warning"></span>
                        <?php _e('Not connected to Build360 AI service', 'build360-ai'); ?>
                    </div>
                <?php endif; ?>
            </div>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="build360_ai_api_key"><?php _e('API Key', 'build360-ai'); ?></label>
                        <span class="build360-ai-required-field">*</span>
                    </th>
                    <td>
                        <input type="password"
                               id="build360_ai_api_key"
                               name="build360_ai_api_key"
                               value="<?php echo esc_attr(get_option('build360_ai_api_key')); ?>"
                               class="regular-text"
                               required>
                        <button type="button" class="button button-secondary build360-ai-toggle-password" data-target="build360_ai_api_key">
                            <span class="dashicons dashicons-visibility"></span>
                        </button>
                        <p class="description">
                            <?php _e('Enter your Build360 AI API key. You can find this in your <a href="https://build360.ai/account" target="_blank">account settings</a>.', 'build360-ai'); ?>
                        </p>
                        <div class="build360-ai-test-connection-wrapper">
                            <button type="button" id="build360_ai_test_connection" class="button button-secondary">
                                <span class="dashicons dashicons-yes-alt"></span> <?php _e('Test Connection', 'build360-ai'); ?>
                            </button>
                            <span class="spinner"></span>
                            <div id="build360_ai_connection_result" class="build360-ai-connection-result"></div>

                            <div class="build360-ai-api-details-toggle">
                                <button type="button" id="build360_ai_toggle_api_details" class="button button-link">
                                    <span class="dashicons dashicons-info-outline"></span> <?php _e('Show API Details', 'build360-ai'); ?>
                                </button>
                            </div>

                            <div id="build360_ai_api_details" class="build360-ai-api-details">
                                <div class="build360-ai-api-details-content">
                                    <h4><?php _e('API Endpoint Information', 'build360-ai'); ?></h4>
                                    <p><?php _e('When you test the connection, the following API request is made:', 'build360-ai'); ?></p>

                                    <div class="build360-ai-code-block">
                                        <h5><?php _e('Request', 'build360-ai'); ?></h5>
                                        <pre><code>POST {your-domain}/api/test-website-token
Headers:
  Accept: application/json
  Content-Type: application/json
  Authorization: Bearer {your-api-key}</code></pre>
                                    </div>

                                    <div class="build360-ai-code-block">
                                        <h5><?php _e('Successful Response', 'build360-ai'); ?></h5>
                                        <pre><code>{
  "success": true,
  "message": "Website token is valid",
  "website": {
    "id": 123,
    "name": "Your Website",
    "url": "https://example.com",
    "user_id": 456,
    "tokens_used": 789
  }
}</code></pre>
                                    </div>

                                    <div class="build360-ai-code-block">
                                        <h5><?php _e('Error Response', 'build360-ai'); ?></h5>
                                        <pre><code>{
  "success": false,
  "message": "Invalid website token",
  "error": "unauthorized"
}</code></pre>
                                    </div>

                                    <p class="build360-ai-api-note"><?php _e('Note: The API connection test verifies your API key and domain without generating any content or using your token balance.', 'build360-ai'); ?></p>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="build360_ai_domain"><?php _e('API Domain', 'build360-ai'); ?></label>
                        <span class="build360-ai-required-field">*</span>
                    </th>
                    <td>
                        <input type="url"
                               id="build360_ai_domain"
                               name="build360_ai_domain"
                               value="<?php echo esc_url(get_option('build360_ai_domain', 'https://api.build360.ai')); ?>"
                               placeholder="https://api.build360.ai"
                               class="regular-text"
                               required>
                        <p class="description">
                            <?php _e('Enter the domain for the Build360 AI API. The default value should work for most users.', 'build360-ai'); ?>
                            <span class="build360-ai-tooltip" title="<?php esc_attr_e('Only change this if instructed by Build360 support team.', 'build360-ai'); ?>">
                                <span class="dashicons dashicons-info-outline"></span>
                            </span>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        </div>

        <div class="build360-ai-card">
        <div class="build360-ai-settings-section">
            <h2><span class="dashicons dashicons-admin-tools"></span> <?php _e('Advanced Settings', 'build360-ai'); ?></h2>
            <p class="build360-ai-section-description">
                <?php _e('Configure advanced plugin settings. These options are primarily for troubleshooting and development.', 'build360-ai'); ?>
            </p>

            <div class="build360-ai-advanced-settings-grid">
                <div class="build360-ai-advanced-setting-card">
                    <div class="build360-ai-advanced-setting-header">
                        <label class="build360-ai-field-toggle">
                            <input type="checkbox" name="build360_ai_debug_mode" value="1" <?php checked(!empty($settings['debug_mode'])); ?>>
                            <span class="build360-ai-toggle-slider"></span>
                            <span class="build360-ai-field-title"><?php _e('Debug Mode', 'build360-ai'); ?></span>
                        </label>
                        <span class="build360-ai-tooltip" title="<?php esc_attr_e('Logs detailed information about API requests and responses for troubleshooting.', 'build360-ai'); ?>">
                            <span class="dashicons dashicons-info-outline"></span>
                        </span>
                    </div>
                    <div class="build360-ai-advanced-setting-body">
                        <p class="description">
                            <?php _e('Enable debug mode to log API requests and responses. This is useful for troubleshooting issues with content generation.', 'build360-ai'); ?>
                        </p>
                        <p class="build360-ai-debug-path">
                            <?php _e('Debug logs will be stored in:', 'build360-ai'); ?>
                            <code><?php echo esc_html(WP_CONTENT_DIR . '/debug.log'); ?></code>
                        </p>
                    </div>
                </div>

                <div class="build360-ai-advanced-setting-card">
                    <div class="build360-ai-advanced-setting-header">
                        <span class="build360-ai-field-title"><?php _e('Cache Settings', 'build360-ai'); ?></span>
                        <span class="build360-ai-tooltip" title="<?php esc_attr_e('Manage the plugin\'s content cache to improve performance.', 'build360-ai'); ?>">
                            <span class="dashicons dashicons-info-outline"></span>
                        </span>
                    </div>
                    <div class="build360-ai-advanced-setting-body">
                        <p class="description">
                            <?php _e('The plugin caches generated content to improve performance and reduce API usage.', 'build360-ai'); ?>
                        </p>
                        <button type="button" class="button button-secondary" id="build360_ai_clear_cache">
                            <span class="dashicons dashicons-trash"></span>
                            <?php _e('Clear Content Cache', 'build360-ai'); ?>
                        </button>
                    </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="build360-ai-card">
            <div class="build360-ai-settings-section">
                <h2><span class="dashicons dashicons-networking"></span> <?php _e('AI Agent Assignments', 'build360-ai'); ?></h2>
                <p class="build360-ai-section-description">
                    <?php _e('Assign your configured AI Agents to specific post types and taxonomies. The assigned agent\'s settings will be used when generating content for these types.', 'build360-ai'); ?>
                </p>
                <div id="build360-ai-agent-assignments-wrapper">
                    <table class="wp-list-table widefat striped" id="build360-ai-agent-assignments-table">
                        <thead>
                            <tr>
                                <th><?php _e('Content Type (Post Type / Taxonomy)', 'build360-ai'); ?></th>
                                <th><?php _e('AI Agent', 'build360-ai'); ?></th>
                                <th><?php _e('Actions', 'build360-ai'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="build360-ai-agent-assignments-tbody">
                            <?php
                            $assignments = get_option('build360_ai_agent_assignments', array());
                            $agents = get_option('build360_ai_agents', array());

                            $available_post_types = get_post_types(array('public' => true), 'objects');
                            $available_taxonomies = get_taxonomies(array('public' => true), 'objects');
                            
                            $content_type_options = array();
                            foreach ($available_post_types as $pt_key => $pt) {
                                $content_type_options[$pt_key] = sprintf('%s (Post Type)', $pt->label);
                            }
                            foreach ($available_taxonomies as $tax_key => $tax) {
                                $content_type_options[$tax_key] = sprintf('%s (Taxonomy)', $tax->label);
                            }
                            asort($content_type_options);

                            if (empty($assignments)) { 
                                // Add a blank row if no assignments yet for the template
                                $assignments[] = array('type' => '', 'agent_id' => '');
                            }

                            foreach ($assignments as $index => $assignment) :
                            ?>
                            <tr class="build360-ai-agent-assignment-row" data-index="<?php echo esc_attr($index); ?>">
                                <td>
                                    <select name="build360_ai_agent_assignments[<?php echo esc_attr($index); ?>][type]" class="build360-ai-assignment-type">
                                        <option value=""><?php _e('-- Select Content Type --', 'build360-ai'); ?></option>
                                        <?php foreach ($content_type_options as $key => $label) : ?>
                                            <option value="<?php echo esc_attr($key); ?>" <?php selected(isset($assignment['type']) ? $assignment['type'] : '', $key); ?>>
                                                <?php echo esc_html($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="build360_ai_agent_assignments[<?php echo esc_attr($index); ?>][agent_id]" class="build360-ai-assignment-agent">
                                        <option value=""><?php _e('-- Select AI Agent --', 'build360-ai'); ?></option>
                                        <?php if (!empty($agents)) : ?>
                                            <?php foreach ($agents as $agent_id => $agent_data) : ?>
                                                <option value="<?php echo esc_attr($agent_id); ?>" <?php selected(isset($assignment['agent_id']) ? $assignment['agent_id'] : '', $agent_id); ?>>
                                                    <?php echo esc_html($agent_data['name'] ?? $agent_id); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php else : ?>
                                            <option value="" disabled><?php _e('No agents configured. Please create an agent first.', 'build360-ai'); ?></option>
                                        <?php endif; ?>
                                    </select>
                                </td>
                                <td>
                                    <button type="button" class="button button-small build360-ai-remove-assignment-row"><?php _e('Remove', 'build360-ai'); ?></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <button type="button" id="build360-ai-add-assignment-row" class="button" style="margin-top: 10px;">
                        <?php _e('Add Assignment', 'build360-ai'); ?>
                    </button>
                    <button type="button" id="build360-ai-sync-agents" class="button button-secondary" style="margin-top: 10px; margin-left: 5px;">
                        <span class="dashicons dashicons-update"></span> <?php _e('Sync Agents', 'build360-ai'); ?>
                    </button>
                    <span id="build360-ai-sync-agents-spinner" class="spinner" style="float: none; vertical-align: middle; margin-left: 5px; display: none;"></span>
                    <span id="build360-ai-sync-agents-result" style="margin-left: 10px; vertical-align: middle;"></span>
                </div>
            </div>
        </div>

        <?php if ($api_configured): ?>
        <div class="build360-ai-card">
        <div class="build360-ai-settings-section build360-ai-token-section">
                <h2><span class="dashicons dashicons-money-alt"></span> <?php _e('Token Status', 'build360-ai'); ?></h2>
            <p class="build360-ai-section-description">
                    <?php _e('Monitor your token status. Tokens are used when generating content.', 'build360-ai'); ?>
            </p>

            <div class="build360-ai-token-dashboard">
                <?php if (is_wp_error($token_balance)): ?>
                    <div class="notice notice-error inline">
                        <p><?php echo esc_html($token_balance->get_error_message()); ?></p>
                    </div>
                    <?php elseif (!isset($token_balance['available']) || !isset($token_balance['used'])) : ?>
                        <div class="notice notice-warning inline">
                            <p><?php _e('Token information is currently unavailable or incomplete.', 'build360-ai'); ?></p>
                        </div>
                    <?php else:
                        $available_tokens = $token_balance['available'];
                        $used_tokens_website = $token_balance['used'];
                    ?>
                    <div class="build360-ai-token-card">
                        <div class="build360-ai-token-header">
                            <span class="build360-ai-token-title"><?php _e('Available Tokens', 'build360-ai'); ?></span>
                        </div>
                        <div class="build360-ai-token-body">
                            <div class="build360-ai-token-count">
                                <span class="dashicons dashicons-token"></span>
                                    <span class="build360-ai-token-number"><?php echo esc_html(number_format($available_tokens)); ?></span>
                                </div>
                                <p class="description" style="text-align: center;"><?php _e('Your current remaining token balance.', 'build360-ai'); ?></p>
                        </div>
                        <div class="build360-ai-token-footer">
                                <a href="https://build360.obs.com.gr/tokens/purchase" class="button button-primary" target="_blank">
                                <span class="dashicons dashicons-cart"></span>
                                <?php _e('Purchase More Tokens', 'build360-ai'); ?>
                            </a>
                        </div>
                    </div>

                    <div class="build360-ai-token-usage-card">
                        <div class="build360-ai-token-header">
                                <span class="build360-ai-token-title"><?php _e('Usage by This Website', 'build360-ai'); ?></span>
                        </div>
                        <div class="build360-ai-token-body">
                                <div class="build360-ai-token-count">
                                    <span class="dashicons dashicons-chart-line"></span>
                                    <span class="build360-ai-token-number"><?php echo esc_html(number_format($used_tokens_website)); ?></span>
                                </div>
                                <p class="description" style="text-align: center;"><?php _e('Tokens used by this specific website.', 'build360-ai'); ?></p>
                                <hr style="margin: 15px 0;">
                            <div class="build360-ai-token-usage-info">
                                     <p class="description">
                                        <?php _e('Content Generated (this website):', 'build360-ai'); ?> 
                                        <strong><?php echo esc_html(number_format(get_option('build360_ai_content_generated', 0))); ?> <?php _e('fields', 'build360-ai'); ?></strong>
                                    </p>
                                    <p class="description">
                                        <?php _e('Products Enhanced (this website):', 'build360-ai'); ?> 
                                        <strong><?php echo esc_html(number_format(get_option('build360_ai_products_enhanced', 0))); ?> <?php _e('products', 'build360-ai'); ?></strong>
                                    </p>
                                    <hr style="margin: 15px 0;">
                                <p class="description">
                                    <?php _e('Token usage varies based on the AI model, content length, and complexity.', 'build360-ai'); ?>
                                </p>
                                <p class="description">
                                    <?php _e('Estimated average token usage:', 'build360-ai'); ?>
                                </p>
                                <ul class="build360-ai-token-usage-list">
                                    <li><?php _e('Short description: ~500 tokens', 'build360-ai'); ?></li>
                                    <li><?php _e('Full description: ~1,500 tokens', 'build360-ai'); ?></li>
                                    <li><?php _e('SEO metadata: ~300 tokens', 'build360-ai'); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <p class="submit">
            <button type="button" id="build360_ai_save_settings" class="button button-primary">
                <?php esc_html_e('Save Settings', 'build360-ai'); ?>
            </button>
            <span class="spinner" style="float: none; vertical-align: middle;"></span>
        </p>
        <div id="build360_ai_settings_result" class="build360-ai-connection-result" style="margin-top: 10px;"></div>

    </form>
</div>
