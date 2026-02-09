<?php
/**
 * Admin dashboard template
 *
 * @package Build360_AI
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get token balance and other necessary data
$api = new Build360_AI_API();
$token_data = $api->get_token_balance(); // Expects ['available' => x, 'used' => y] or error
$api_key_saved = get_option('build360_ai_api_key');

// Local stats
$content_generated_count = get_option('build360_ai_content_generated', 0);
$products_enhanced_count = get_option('build360_ai_products_enhanced', 0);
?>

<div class="wrap build360-ai-dashboard-wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div id="dashboard-widgets-wrap">
        <div id="dashboard-widgets" class="metabox-holder">

            <!-- Welcome Panel -->
            <div class="build360-ai-card welcome-panel">
                <h2 class="card-header"><span class="dashicons dashicons-megaphone"></span> <?php _e('Welcome to Build360 AI Content Generator', 'build360-ai'); ?></h2>
                <div class="card-content">
            <p class="about-description">
                        <?php _e('Generate high-quality content for your WooCommerce products and other content types using advanced AI technology. Manage your AI agents, assign them to tasks, and monitor your usage all in one place.', 'build360-ai'); ?>
            </p>
        </div>
            </div>

            <div class="grid-container">
                <!-- Left Column -->
                <div class="grid-column">
                    <!-- Quick Actions Card -->
                    <div class="build360-ai-card">
                        <h2 class="card-header"><span class="dashicons dashicons-controls-play"></span> <?php _e('Quick Actions', 'build360-ai'); ?></h2>
                        <div class="card-content">
                            <div class="action-buttons vertical">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=build360-ai-agents')); ?>" class="button button-large">
                                    <span class="dashicons dashicons-groups"></span> <?php _e('Manage AI Agents', 'build360-ai'); ?>
                                </a>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=build360-ai-settings')); ?>" class="button button-large">
                                    <span class="dashicons dashicons-admin-settings"></span> <?php _e('Configure Settings', 'build360-ai'); ?>
                                </a>
                            </div>
                        </div>
            </div>

                    <!-- Recent Activity Card -->
                    <div class="build360-ai-card">
                        <h2 class="card-header"><span class="dashicons dashicons-list-view"></span> <?php _e('Recent Activity', 'build360-ai'); ?></h2>
                        <div class="card-content">
                    <?php
                            $recent_activity = get_option('build360_ai_recent_activity', array());
                            if (!empty($recent_activity)) {
                                echo '<ul class="activity-list">';
                                foreach (array_slice($recent_activity, 0, 5) as $activity_item) { // Show latest 5
                                    // Ensure $activity_item is an array and has a 'message' key
                                    if (is_array($activity_item) && isset($activity_item['message'])) {
                                        $message_to_display = esc_html($activity_item['message']);
                                        $time_to_display = isset($activity_item['time']) ? '<span class="activity-time"> (' . esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($activity_item['time']))) . ')</span>' : '';
                                        echo '<li><span class="dashicons dashicons-yes-alt"></span> ' . $message_to_display . $time_to_display . '</li>';
                                    } else {
                                        // Log or display a fallback if structure is not as expected
                                        echo '<li><span class="dashicons dashicons-warning"></span> ' . esc_html__('Invalid activity item format.', 'build360-ai') . '</li>';
                                        error_log('[Build360 AI] Invalid activity item format in dashboard: ' . print_r($activity_item, true));
                                    }
                                }
                                echo '</ul>';
                                if (count($recent_activity) > 5) {
                                    // TODO: Link to a full activity log page if implemented - DONE
                                    echo '<p><a href="' . esc_url(admin_url('admin.php?page=build360-ai-activity-log')) . '">' . esc_html__('View all activity...', 'build360-ai') . '</a></p>';
                                }
                    } else {
                                echo '<p class="no-activity"><span class="dashicons dashicons-info-outline"></span> ' . esc_html__('No recent plugin activity recorded.', 'build360-ai') . '</p>';
                    }
                    ?>
                </div>
            </div>
        </div>

                <!-- Right Column -->
                <div class="grid-column">
                    <!-- Token Usage & API Status Card -->
                    <div class="build360-ai-card">
                         <h2 class="card-header"><span class="dashicons dashicons-dashboard"></span> <?php _e('API Status & Token Usage', 'build360-ai'); ?></h2>
                        <div class="card-content">
                            <div class="api-status-section">
                                <strong><?php _e('API Connection:', 'build360-ai'); ?></strong>
                                <?php if ($api_key_saved) : ?>
                                    <span class="status-ok"><span class="dashicons dashicons-yes"></span> <?php esc_html_e('Connected', 'build360-ai'); ?></span>
                                <?php else : ?>
                                    <span class="status-error"><span class="dashicons dashicons-no"></span> <?php esc_html_e('Not Connected - API Key Missing', 'build360-ai'); ?></span>
                                    <p><?php printf(wp_kses_post(__('Please <a href="%s">enter your API key</a> in the settings to enable AI features.', 'build360-ai')), esc_url(admin_url('admin.php?page=build360-ai-settings'))); ?></p>
                                <?php endif; ?>
                            </div>
                            <hr>
                            <h4><?php _e('Token Balance', 'build360-ai'); ?>
                                <span class="build360-tooltip" data-tooltip="<?php esc_attr_e('AI tokens are consumed with each generation. Purchase more from your Build360.gr account.', 'build360-ai'); ?>"><span class="dashicons dashicons-editor-help"></span></span>
                            </h4>
                            <?php if (is_wp_error($token_data)) : ?>
                                <p class="status-error"><span class="dashicons dashicons-warning"></span> <?php esc_html_e('Error fetching token balance:', 'build360-ai'); ?> <?php echo esc_html($token_data->get_error_message()); ?></p>
                            <?php elseif ($api_key_saved && isset($token_data['available'])) : ?>
                                <p><strong><?php esc_html_e('Available Tokens:', 'build360-ai'); ?></strong> <span class="token-count available"><?php echo esc_html(number_format_i18n($token_data['available'])); ?></span></p>
                                <p><strong><?php esc_html_e('Usage by This Website:', 'build360-ai'); ?></strong> <span class="token-count used"><?php echo esc_html(number_format_i18n($token_data['used'])); ?></span></p>
                                <a href="https://build360.obs.com.gr/tokens/purchase" target="_blank" class="button button-secondary">
                                    <span class="dashicons dashicons-cart"></span> <?php _e('Purchase More Tokens', 'build360-ai'); ?>
                </a>
                            <?php elseif (!$api_key_saved): ?>
                                 <p><?php _e('Token information will be available once the API key is saved.', 'build360-ai'); ?></p>
                            <?php else: ?>
                                <p class="status-error"><span class="dashicons dashicons-warning"></span> <?php esc_html_e('Could not retrieve token balance. The API response might be malformed.', 'build360-ai'); ?></p>
                                 <?php error_log('Build360 AI Dashboard: Token data malformed or missing. Data: ' . print_r($token_data, true)); ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Local Stats Card -->
                    <div class="build360-ai-card">
                        <h2 class="card-header"><span class="dashicons dashicons-chart-line"></span> <?php _e('Site Statistics', 'build360-ai'); ?></h2>
                        <div class="card-content">
                            <p><strong><?php _e('Content Generated:', 'build360-ai'); ?></strong> <span class="stat-number"><?php echo esc_html(number_format_i18n($content_generated_count)); ?></span> <?php _e('items', 'build360-ai'); ?>
                                <span class="build360-tooltip" data-tooltip="<?php esc_attr_e('Total number of content fields generated across all products.', 'build360-ai'); ?>"><span class="dashicons dashicons-editor-help"></span></span>
                            </p>
                            <p><strong><?php _e('Products Enhanced:', 'build360-ai'); ?></strong> <span class="stat-number"><?php echo esc_html(number_format_i18n($products_enhanced_count)); ?></span> <?php _e('products', 'build360-ai'); ?>
                                <span class="build360-tooltip" data-tooltip="<?php esc_attr_e('Number of products that have received AI-generated content.', 'build360-ai'); ?>"><span class="dashicons dashicons-editor-help"></span></span>
                            </p>
                             <p class="description"><?php _e('These are counts of generation events initiated by this plugin on your site.', 'build360-ai'); ?></p>
            </div>
        </div>

                </div>
            </div>
        </div>
    </div>
</div> 