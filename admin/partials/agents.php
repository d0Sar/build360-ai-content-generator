<?php
/**
 * AI Agents template
 *
 * @package Build360_AI
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get settings instance
$settings_instance = new Build360_AI_Settings();
$token_usage = $settings_instance->get_token_usage();

// Get agents
$agents = get_option('build360_ai_agents', array());

// Get agent categories
$categories = array(
    'content' => __('Content Generation', 'build360-ai'),
    'seo' => __('SEO Optimization', 'build360-ai'),
    'support' => __('Customer Support', 'build360-ai'),
    'technical' => __('Technical Writing', 'build360-ai'),
    'marketing' => __('Marketing Copy', 'build360-ai'),
);
?>

<div class="wrap build360-ai-agents">
    <h1 class="wp-heading-inline"><?php _e('AI Agents', 'build360-ai'); ?></h1>
    <button type="button" class="page-title-action add-agent"><?php _e('Add New Agent', 'build360-ai'); ?></button>

    <div class="token-info">
        <span class="token-count"><?php echo number_format($token_usage['remaining']); ?></span>
        <span class="token-label"><?php _e('tokens available', 'build360-ai'); ?></span>
        <div class="progress-bar-container">
            <div class="progress-bar" style="width: <?php echo ($token_usage['used'] / $token_usage['total']) * 100; ?>%"></div>
        </div>
    </div>

    <div class="prompt-guide postbox">
        <div class="postbox-header">
            <h2 class="hndle" style="cursor: default; padding: 8px 12px;"><?php _e('System Prompt Guide', 'build360-ai'); ?></h2>
        </div>
        <div class="inside">
            <p><?php _e('The System Prompt defines how your AI agent writes content. Use <strong>placeholders</strong> to dynamically insert product/category data:', 'build360-ai'); ?></p>
            <table class="widefat striped" style="margin-bottom: 15px;">
                <thead>
                    <tr>
                        <th><?php _e('Placeholder', 'build360-ai'); ?></th>
                        <th><?php _e('Description', 'build360-ai'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td><code>{{title}}</code></td><td><?php _e('Product/category name', 'build360-ai'); ?></td></tr>
                    <tr><td><code>{{description}}</code></td><td><?php _e('Existing description text', 'build360-ai'); ?></td></tr>
                    <tr><td><code>{{keywords}}</code></td><td><?php _e('Keywords entered by the user during generation', 'build360-ai'); ?></td></tr>
                    <tr><td><code>{{categories}}</code></td><td><?php _e('Product categories', 'build360-ai'); ?></td></tr>
                    <tr><td><code>{{attributes}}</code></td><td><?php _e('Product attributes (size, color, etc.)', 'build360-ai'); ?></td></tr>
                    <tr><td><code>{{tags}}</code></td><td><?php _e('Product tags', 'build360-ai'); ?></td></tr>
                </tbody>
            </table>
            <p><strong><?php _e('Example prompt for products:', 'build360-ai'); ?></strong></p>
            <pre style="background: #f6f7f7; padding: 12px; border: 1px solid #ddd; border-radius: 4px; white-space: pre-wrap; font-size: 13px; line-height: 1.5;">You are an expert e-commerce copywriter. Write compelling, SEO-optimized content in Greek for the product "{{title}}".

Product info: {{description}}
Categories: {{categories}}
Tags: {{tags}}
Attributes: {{attributes}}
Target keywords: {{keywords}}

Guidelines:
- Write in natural, modern Greek
- Focus on benefits, not just features
- For descriptions: use short paragraphs, minimal HTML (&lt;p&gt;, &lt;strong&gt;, &lt;ul&gt;)
- For short descriptions: 2-3 concise sentences highlighting key selling points
- For meta titles: under 60 characters, include primary keyword
- For meta descriptions: under 155 characters, with a call-to-action</pre>
            <p class="description" style="margin-top: 10px;">
                <?php _e('<strong>Tip:</strong> If you don\'t include <code>{{keywords}}</code> in your prompt, the keywords field on the product/category page will have no effect. Same applies to all other placeholders &mdash; only the ones you include will be used.', 'build360-ai'); ?>
            </p>
        </div>
    </div>

    <div class="agents-list">
        <?php
        if (empty($agents)) :
        ?>
            <div class="no-agents">
                <p><?php _e('No agents found. Click "Add New Agent" to create one.', 'build360-ai'); ?></p>
            </div>
        <?php else : ?>
            <?php foreach ($agents as $agent_id => $agent) : ?>
                <div class="agent-card" data-id="<?php echo esc_attr($agent_id); ?>">
                    <div class="agent-header">
                        <h3><?php echo esc_html($agent['name']); ?></h3>
                        <div class="agent-actions">
                            <button type="button" class="edit-agent" title="<?php esc_attr_e('Edit Agent', 'build360-ai'); ?>">
                                <span class="dashicons dashicons-edit"></span>
                            </button>
                            <button type="button" class="delete-agent" title="<?php esc_attr_e('Delete Agent', 'build360-ai'); ?>">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </div>
                    </div>
                    <div class="agent-description"><?php echo esc_html($agent['description']); ?></div>
                    <div class="agent-stats">
                        <span class="stat">
                            <i class="fas fa-tasks"></i>
                            <?php printf(__('%d tasks', 'build360-ai'), $agent['stats']['tasks'] ?? 0); ?>
                        </span>
                        <span class="stat">
                            <i class="fas fa-check-circle"></i>
                            <?php printf(__('%d%% success', 'build360-ai'), $agent['stats']['success_rate'] ?? 100); ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Template -->
<div id="agent-modal" class="build360-ai-modal">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2><?php _e('Add New Agent', 'build360-ai'); ?></h2>
            <button type="button" class="close-modal">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="modal-body">
            <form id="agent-form">
                <div class="form-group">
                    <label for="agent-name">
                        <?php _e('Agent Name', 'build360-ai'); ?>
                        <span class="required">*</span>
                        <span class="build360-tooltip" data-tooltip="<?php esc_attr_e('A descriptive name for this agent, e.g. \'Product Descriptions\' or \'Blog SEO\'.', 'build360-ai'); ?>"><span class="dashicons dashicons-editor-help"></span></span>
                    </label>
                    <input type="text" id="agent-name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="agent-description">
                        <?php _e('Description', 'build360-ai'); ?>
                        <span class="required">*</span>
                        <span class="build360-tooltip" data-tooltip="<?php esc_attr_e('Brief description of what this agent does. For your reference only.', 'build360-ai'); ?>"><span class="dashicons dashicons-editor-help"></span></span>
                    </label>
                    <textarea id="agent-description" name="description" required></textarea>
                </div>

                <div class="form-group">
                    <label for="agent-prompt">
                        <?php _e('System Prompt', 'build360-ai'); ?>
                        <span class="required">*</span>
                        <span class="build360-tooltip" data-tooltip="<?php esc_attr_e('Instructions that define how the AI agent should behave and respond.', 'build360-ai'); ?>"><span class="dashicons dashicons-editor-help"></span></span>
                    </label>
                    <textarea id="agent-prompt" name="system_prompt" required></textarea>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" id="agent-is-active" name="is_active" checked>
                        <?php _e('Active', 'build360-ai'); ?>
                    </label>
                    <p class="description"><?php _e('Only active agents can be used for content generation.', 'build360-ai'); ?></p>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="button cancel-modal"><?php _e('Cancel', 'build360-ai'); ?></button>
            <button type="submit" class="button button-primary save-agent"><?php _e('Save Agent', 'build360-ai'); ?></button>
        </div>
    </div>
</div>

<style>
/* Agent-specific modal/form overrides (base styles in admin.css) */
.build360-ai-agents .build360-ai-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 100000;
}

.build360-ai-agents .modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    z-index: 100001;
}

.build360-ai-agents .modal-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #fff;
    border-radius: 4px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    z-index: 100002;
}

/* Token Info (agents page) */
.build360-ai-agents .token-info {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 16px;
    margin: 20px 0;
}

.build360-ai-agents .token-count {
    font-size: 1.2em;
    font-weight: 600;
    margin-right: 8px;
}

.build360-ai-agents .progress-bar-container {
    margin-top: 8px;
    background: #f0f0f1;
    border-radius: 4px;
    height: 8px;
    overflow: hidden;
}

.build360-ai-agents .progress-bar {
    height: 100%;
    background: #2271b1;
    transition: width 0.3s ease;
}

#agent-prompt {
    min-height: 220px;
}

.form-error {
    color: #d63638;
    display: block;
    margin-top: 5px;
    font-size: 12px;
}
</style>