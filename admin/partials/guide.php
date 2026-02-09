<?php
/**
 * Guide page template
 *
 * @package Build360_AI
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap build360-guide-wrap">
    <h1><?php _e('Build360 AI - Guide', 'build360-ai'); ?></h1>
    <p class="description" style="margin-bottom: 20px;">
        <?php _e('Everything you need to know about using the Build360 AI Content Generator plugin.', 'build360-ai'); ?>
    </p>

    <!-- 1. Getting Started -->
    <div class="build360-guide-section open">
        <div class="build360-guide-section-header">
            <span class="dashicons dashicons-flag"></span>
            <h3><?php _e('Getting Started', 'build360-ai'); ?></h3>
            <span class="dashicons dashicons-arrow-up-alt2 build360-guide-toggle"></span>
        </div>
        <div class="build360-guide-section-body" style="display: block;">
            <h4><?php _e('1. Get Your API Key', 'build360-ai'); ?></h4>
            <ol>
                <li><?php _e('Log in to your <strong>Build360.gr</strong> account dashboard.', 'build360-ai'); ?></li>
                <li><?php _e('Navigate to the API section and copy your API key.', 'build360-ai'); ?></li>
                <li><?php _e('If you don\'t have an account yet, register at build360.gr.', 'build360-ai'); ?></li>
            </ol>

            <h4><?php _e('2. Configure the Plugin', 'build360-ai'); ?></h4>
            <ol>
                <li><?php printf(__('Go to <strong>Build360 AI &rarr; <a href="%s">Settings</a></strong> in your WordPress admin.', 'build360-ai'), esc_url(admin_url('admin.php?page=build360-ai-settings'))); ?></li>
                <li><?php _e('Paste your API key into the <strong>API Key</strong> field.', 'build360-ai'); ?></li>
                <li><?php _e('The <strong>API Domain</strong> is pre-filled with the default value. Don\'t change it unless instructed by support.', 'build360-ai'); ?></li>
                <li><?php _e('Click <strong>Test Connection</strong> to verify everything works.', 'build360-ai'); ?></li>
                <li><?php _e('Click <strong>Save Settings</strong>.', 'build360-ai'); ?></li>
            </ol>

            <div class="guide-tip">
                <strong><?php _e('Tip:', 'build360-ai'); ?></strong> <?php _e('If the test connection fails, double-check that your API key has no extra spaces and that your server can make outbound HTTPS requests.', 'build360-ai'); ?>
            </div>
        </div>
    </div>

    <!-- 2. AI Agents -->
    <div class="build360-guide-section">
        <div class="build360-guide-section-header">
            <span class="dashicons dashicons-groups"></span>
            <h3><?php _e('AI Agents', 'build360-ai'); ?></h3>
            <span class="dashicons dashicons-arrow-down-alt2 build360-guide-toggle"></span>
        </div>
        <div class="build360-guide-section-body">
            <p><?php _e('AI Agents are configurations that define <em>how</em> the AI generates content. Each agent has its own model, style, and system prompt.', 'build360-ai'); ?></p>

            <h4><?php _e('Creating an Agent', 'build360-ai'); ?></h4>
            <ol>
                <li><?php printf(__('Go to <strong>Build360 AI &rarr; <a href="%s">AI Agents</a></strong>.', 'build360-ai'), esc_url(admin_url('admin.php?page=build360-ai-agents'))); ?></li>
                <li><?php _e('Click <strong>Add New Agent</strong>.', 'build360-ai'); ?></li>
                <li><?php _e('Fill in the required fields:', 'build360-ai'); ?>
                    <ul>
                        <li><strong><?php _e('Name', 'build360-ai'); ?></strong> &ndash; <?php _e('e.g., "Product Descriptions" or "Blog SEO"', 'build360-ai'); ?></li>
                        <li><strong><?php _e('AI Model', 'build360-ai'); ?></strong> &ndash; <?php _e('GPT-4o is recommended for best quality. Lighter models are faster and cheaper.', 'build360-ai'); ?></li>
                        <li><strong><?php _e('System Prompt', 'build360-ai'); ?></strong> &ndash; <?php _e('Instructions that tell the AI how to write. Be specific about tone, language, and formatting.', 'build360-ai'); ?></li>
                    </ul>
                </li>
                <li><?php _e('Click <strong>Save Agent</strong>.', 'build360-ai'); ?></li>
            </ol>

            <h4><?php _e('System Prompt Tips', 'build360-ai'); ?></h4>
            <ul>
                <li><?php _e('Be specific: "Write in Greek, professional tone, 150-200 words" works better than "write a description".', 'build360-ai'); ?></li>
                <li><?php _e('Include formatting instructions: "Use bullet points for features" or "Include a call-to-action at the end".', 'build360-ai'); ?></li>
                <li><?php _e('Mention your industry: "You are writing for a construction materials e-commerce store" gives better context.', 'build360-ai'); ?></li>
            </ul>
        </div>
    </div>

    <!-- 3. Single Product Generation -->
    <div class="build360-guide-section">
        <div class="build360-guide-section-header">
            <span class="dashicons dashicons-edit-page"></span>
            <h3><?php _e('Single Product Generation', 'build360-ai'); ?></h3>
            <span class="dashicons dashicons-arrow-down-alt2 build360-guide-toggle"></span>
        </div>
        <div class="build360-guide-section-body">
            <h4><?php _e('Using the Meta Box', 'build360-ai'); ?></h4>
            <ol>
                <li><?php _e('Edit any WooCommerce product.', 'build360-ai'); ?></li>
                <li><?php _e('Scroll down to the <strong>Build360 AI Content Generator</strong> box.', 'build360-ai'); ?></li>
                <li><?php _e('Select which fields to generate:', 'build360-ai'); ?>
                    <ul>
                        <li><strong><?php _e('Product Description', 'build360-ai'); ?></strong> &ndash; <?php _e('The main, detailed product description.', 'build360-ai'); ?></li>
                        <li><strong><?php _e('Short Description', 'build360-ai'); ?></strong> &ndash; <?php _e('A brief summary shown on the product page.', 'build360-ai'); ?></li>
                        <li><strong><?php _e('SEO Title', 'build360-ai'); ?></strong> &ndash; <?php _e('Optimized title for search engines (saved to Yoast, Rank Math, etc).', 'build360-ai'); ?></li>
                        <li><strong><?php _e('SEO Description', 'build360-ai'); ?></strong> &ndash; <?php _e('Meta description for search engine results.', 'build360-ai'); ?></li>
                        <li><strong><?php _e('Image Alt Text', 'build360-ai'); ?></strong> &ndash; <?php _e('Accessibility and SEO text for the product image.', 'build360-ai'); ?></li>
                    </ul>
                </li>
                <li><?php _e('Optionally add <strong>keywords</strong> to guide the AI.', 'build360-ai'); ?></li>
                <li><?php _e('Click <strong>Generate Content</strong>.', 'build360-ai'); ?></li>
                <li><?php _e('Review the generated content in the modal that appears.', 'build360-ai'); ?></li>
                <li><?php _e('Click <strong>Approve & Update Fields</strong> to save, or <strong>Retry</strong> for a new version.', 'build360-ai'); ?></li>
            </ol>

            <div class="guide-tip">
                <strong><?php _e('Tip:', 'build360-ai'); ?></strong> <?php _e('The more product data you have (categories, attributes, tags), the better the AI can generate relevant content.', 'build360-ai'); ?>
            </div>
        </div>
    </div>

    <!-- 4. Bulk Generation -->
    <div class="build360-guide-section">
        <div class="build360-guide-section-header">
            <span class="dashicons dashicons-controls-repeat"></span>
            <h3><?php _e('Bulk Generation', 'build360-ai'); ?></h3>
            <span class="dashicons dashicons-arrow-down-alt2 build360-guide-toggle"></span>
        </div>
        <div class="build360-guide-section-body">
            <h4><?php _e('How It Works', 'build360-ai'); ?></h4>
            <p><?php _e('Bulk generation processes multiple products in the background using WooCommerce\'s Action Scheduler. You can navigate away while it runs.', 'build360-ai'); ?></p>

            <h4><?php _e('Steps', 'build360-ai'); ?></h4>
            <ol>
                <li><?php _e('Go to <strong>Products &rarr; All Products</strong>.', 'build360-ai'); ?></li>
                <li><?php _e('Select the products you want to generate content for using the checkboxes.', 'build360-ai'); ?></li>
                <li><?php _e('From the <strong>Bulk actions</strong> dropdown, select <strong>"Generate content with Build360 AI"</strong>.', 'build360-ai'); ?></li>
                <li><?php _e('Click <strong>Apply</strong>.', 'build360-ai'); ?></li>
                <li><?php _e('A progress bar will appear showing real-time status for each product.', 'build360-ai'); ?></li>
            </ol>

            <h4><?php _e('What Gets Generated', 'build360-ai'); ?></h4>
            <p><?php _e('Bulk generation creates <strong>4 fields</strong> per product:', 'build360-ai'); ?></p>
            <ul>
                <li><?php _e('Product Description', 'build360-ai'); ?></li>
                <li><?php _e('Short Description', 'build360-ai'); ?></li>
                <li><?php _e('SEO Title (Yoast, Rank Math, etc.)', 'build360-ai'); ?></li>
                <li><?php _e('SEO Meta Description', 'build360-ai'); ?></li>
            </ul>

            <h4><?php _e('Progress Tracking', 'build360-ai'); ?></h4>
            <ul>
                <li><?php _e('The progress bar updates every 3 seconds automatically.', 'build360-ai'); ?></li>
                <li><?php _e('You can navigate away and come back &ndash; the progress bar will reappear.', 'build360-ai'); ?></li>
                <li><?php _e('Click <strong>Cancel</strong> to stop the job. Products already processed will keep their content.', 'build360-ai'); ?></li>
                <li><?php _e('Click <strong>View Results</strong> when done to see a summary with content previews.', 'build360-ai'); ?></li>
            </ul>

            <div class="guide-warning">
                <strong><?php _e('Important:', 'build360-ai'); ?></strong> <?php _e('Bulk generation replaces existing content without a review step. Make sure you have backups if needed.', 'build360-ai'); ?>
            </div>
        </div>
    </div>

    <!-- 5. Agent Assignments -->
    <div class="build360-guide-section">
        <div class="build360-guide-section-header">
            <span class="dashicons dashicons-networking"></span>
            <h3><?php _e('Agent Assignments', 'build360-ai'); ?></h3>
            <span class="dashicons dashicons-arrow-down-alt2 build360-guide-toggle"></span>
        </div>
        <div class="build360-guide-section-body">
            <p><?php _e('Agent assignments connect your AI Agents to specific content types. This tells the plugin which agent to use when generating content.', 'build360-ai'); ?></p>

            <h4><?php _e('Setting Up Assignments', 'build360-ai'); ?></h4>
            <ol>
                <li><?php printf(__('Go to <strong>Build360 AI &rarr; <a href="%s">Settings</a></strong>.', 'build360-ai'), esc_url(admin_url('admin.php?page=build360-ai-settings'))); ?></li>
                <li><?php _e('Scroll to the <strong>AI Agent Assignments</strong> section.', 'build360-ai'); ?></li>
                <li><?php _e('For each content type (e.g., "Products"), select the agent you want to use.', 'build360-ai'); ?></li>
                <li><?php _e('Click <strong>Add Assignment</strong> to add more rows.', 'build360-ai'); ?></li>
                <li><?php _e('Click <strong>Save Settings</strong>.', 'build360-ai'); ?></li>
            </ol>

            <div class="guide-tip">
                <strong><?php _e('Tip:', 'build360-ai'); ?></strong> <?php _e('You need at least one agent assigned to "Products" for bulk generation and the product meta box to work.', 'build360-ai'); ?>
            </div>
        </div>
    </div>

    <!-- 6. Token System -->
    <div class="build360-guide-section">
        <div class="build360-guide-section-header">
            <span class="dashicons dashicons-money-alt"></span>
            <h3><?php _e('Token System', 'build360-ai'); ?></h3>
            <span class="dashicons dashicons-arrow-down-alt2 build360-guide-toggle"></span>
        </div>
        <div class="build360-guide-section-body">
            <h4><?php _e('What Are Tokens?', 'build360-ai'); ?></h4>
            <p><?php _e('Tokens are the currency used by AI models to process text. Every piece of content you generate consumes tokens from your account balance.', 'build360-ai'); ?></p>

            <h4><?php _e('Estimated Usage', 'build360-ai'); ?></h4>
            <ul>
                <li><strong><?php _e('Short description:', 'build360-ai'); ?></strong> <?php _e('~500 tokens', 'build360-ai'); ?></li>
                <li><strong><?php _e('Full description:', 'build360-ai'); ?></strong> <?php _e('~1,500 tokens', 'build360-ai'); ?></li>
                <li><strong><?php _e('SEO metadata:', 'build360-ai'); ?></strong> <?php _e('~300 tokens', 'build360-ai'); ?></li>
                <li><strong><?php _e('Full product (all 4 fields):', 'build360-ai'); ?></strong> <?php _e('~2,600 tokens', 'build360-ai'); ?></li>
            </ul>

            <h4><?php _e('Checking Your Balance', 'build360-ai'); ?></h4>
            <p><?php printf(__('Your token balance is displayed on the <a href="%s">Dashboard</a> and <a href="%s">Settings</a> pages.', 'build360-ai'), esc_url(admin_url('admin.php?page=build360-ai')), esc_url(admin_url('admin.php?page=build360-ai-settings'))); ?></p>

            <h4><?php _e('Purchasing More Tokens', 'build360-ai'); ?></h4>
            <p><?php _e('Click the <strong>Purchase More Tokens</strong> button on the Dashboard or Settings page to buy additional tokens from your Build360.gr account.', 'build360-ai'); ?></p>
        </div>
    </div>

    <!-- 7. Troubleshooting -->
    <div class="build360-guide-section">
        <div class="build360-guide-section-header">
            <span class="dashicons dashicons-sos"></span>
            <h3><?php _e('Troubleshooting', 'build360-ai'); ?></h3>
            <span class="dashicons dashicons-arrow-down-alt2 build360-guide-toggle"></span>
        </div>
        <div class="build360-guide-section-body">
            <h4><?php _e('"Connection Failed" Error', 'build360-ai'); ?></h4>
            <ul>
                <li><?php _e('Verify your API key is correct (no extra spaces or line breaks).', 'build360-ai'); ?></li>
                <li><?php _e('Check that the API Domain is correct (default: <code>https://api.build360.ai</code>).', 'build360-ai'); ?></li>
                <li><?php _e('Ensure your server allows outbound HTTPS connections.', 'build360-ai'); ?></li>
                <li><?php _e('Ask your hosting provider if they block external API calls.', 'build360-ai'); ?></li>
            </ul>

            <h4><?php _e('"No AI Agent Assigned" Error', 'build360-ai'); ?></h4>
            <ul>
                <li><?php _e('Go to Settings and assign an agent to the "Products" content type.', 'build360-ai'); ?></li>
                <li><?php _e('If no agents appear in the dropdown, create one first in the AI Agents page.', 'build360-ai'); ?></li>
                <li><?php _e('Click "Sync Agents" on the Settings page to refresh the agent list from the API.', 'build360-ai'); ?></li>
            </ul>

            <h4><?php _e('Content Not Generated / Empty Results', 'build360-ai'); ?></h4>
            <ul>
                <li><?php _e('Check that the product has a title &ndash; the AI needs at least a product name to work with.', 'build360-ai'); ?></li>
                <li><?php _e('Enable <strong>Debug Mode</strong> in Settings to see detailed API logs.', 'build360-ai'); ?></li>
                <li><?php _e('Check your token balance &ndash; generation fails silently if you\'re out of tokens.', 'build360-ai'); ?></li>
            </ul>

            <h4><?php _e('Bulk Generation Stuck', 'build360-ai'); ?></h4>
            <ul>
                <li><?php _e('Bulk generation runs via WooCommerce Action Scheduler. Check <strong>WooCommerce &rarr; Status &rarr; Scheduled Actions</strong> to see if actions are queued.', 'build360-ai'); ?></li>
                <li><?php _e('If actions are stuck as "Pending", your WP-Cron might not be running. Ask your host to set up a real cron job.', 'build360-ai'); ?></li>
                <li><?php _e('You can cancel a stuck job from the product list page and start a new one.', 'build360-ai'); ?></li>
            </ul>

            <h4><?php _e('SEO Fields Not Updating', 'build360-ai'); ?></h4>
            <ul>
                <li><?php _e('SEO fields are saved to Yoast SEO, Rank Math, SEOPress, and All in One SEO meta fields automatically.', 'build360-ai'); ?></li>
                <li><?php _e('Make sure you have at least one SEO plugin installed and active.', 'build360-ai'); ?></li>
                <li><?php _e('Clear your SEO plugin\'s cache after bulk generation.', 'build360-ai'); ?></li>
            </ul>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('.build360-guide-section-header').on('click', function() {
        var $section = $(this).closest('.build360-guide-section');
        var $body = $section.find('.build360-guide-section-body');
        var $toggle = $section.find('.build360-guide-toggle');

        $section.toggleClass('open');

        if ($section.hasClass('open')) {
            $body.slideDown(200);
            $toggle.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
        } else {
            $body.slideUp(200);
            $toggle.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
        }
    });
});
</script>
