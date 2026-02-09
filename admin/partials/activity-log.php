<?php
/**
 * Admin Activity Log template
 *
 * @package Build360_AI
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap build360-ai-activity-log-wrap">
    <h1><?php esc_html_e('Build360 AI - Activity Log', 'build360-ai'); ?></h1>

    <div class="build360-ai-card">
        <h2 class="card-header"><span class="dashicons dashicons-list-view"></span> <?php esc_html_e('All Recent Activity', 'build360-ai'); ?></h2>
        <div class="card-content">
            <?php
            $all_activity = get_option('build360_ai_recent_activity', array());

            if (!is_array($all_activity)) { // Ensure it's an array
                $all_activity = array();
                echo '<p class="notice notice-warning">'. esc_html__('Activity log data is corrupted and has been reset.', 'build360-ai') .'</p>';
                update_option('build360_ai_recent_activity', array()); // Reset if corrupted
            }

            if (!empty($all_activity)) :
            ?>
                <table class="wp-list-table widefat striped fixed">
                    <thead>
                        <tr>
                            <th scope="col" style="width: 25%;"><?php esc_html_e('Timestamp', 'build360-ai'); ?></th>
                            <th scope="col"><?php esc_html_e('Activity', 'build360-ai'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_activity as $activity_item) : ?>
                            <?php if (is_array($activity_item) && isset($activity_item['message']) && isset($activity_item['time'])) : ?>
                                <tr>
                                    <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($activity_item['time']))); ?></td>
                                    <td><?php echo esc_html($activity_item['message']); ?></td>
                                </tr>
                            <?php else : ?>
                                <?php error_log('[Build360 AI] Invalid activity item format in full activity log: ' . print_r($activity_item, true)); ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p class="no-activity"><span class="dashicons dashicons-info-outline"></span> <?php esc_html_e('No plugin activity has been recorded yet.', 'build360-ai'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div> 