<?php
/**
 * Post/Page meta box template
 *
 * @package Build360_AI
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$post_obj = get_post($post);
$last_generated = get_post_meta($post->ID, '_build360_ai_last_generated', true);
$categories = '';
$terms = get_the_terms($post->ID, 'category');
if ($terms && !is_wp_error($terms)) {
    $categories = implode(', ', wp_list_pluck($terms, 'name'));
}

// Security nonce
wp_nonce_field('build360_ai_generate_content', 'build360_ai_nonce');
?>

<div class="build360-ai-meta-box">
    <div class="build360-ai-header">
        <?php if ($last_generated): ?>
            <div class="token-info">
                <span class="token-label"><?php _e('Last generated:', 'build360-ai'); ?></span>
                <span class="token-count"><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_generated)); ?></span>
            </div>
        <?php endif; ?>
        <div class="language-selector">
            <select id="build360_ai_language">
                <option value="en">English</option>
                <option value="el">Ελληνικά</option>
                <option value="es">Español</option>
                <option value="fr">Français</option>
                <option value="de">Deutsch</option>
            </select>
        </div>
    </div>

    <div class="content-fields">
        <h4><?php _e('Generate Content For:', 'build360-ai'); ?>
            <span class="build360-tooltip" data-tooltip="<?php esc_attr_e('Select which fields to generate. Existing content will be replaced.', 'build360-ai'); ?>"><span class="dashicons dashicons-editor-help"></span></span>
        </h4>
        <div class="field-grid">
            <label class="field-label">
                <input type="checkbox" name="build360_ai_fields[]" value="content" />
                <span class="checkbox-custom"></span>
                <span class="label-text"><?php _e('Post Content', 'build360-ai'); ?></span>
            </label>
            <label class="field-label">
                <input type="checkbox" name="build360_ai_fields[]" value="seo_title" />
                <span class="checkbox-custom"></span>
                <span class="label-text"><?php _e('SEO Title', 'build360-ai'); ?></span>
            </label>
            <label class="field-label">
                <input type="checkbox" name="build360_ai_fields[]" value="seo_description" />
                <span class="checkbox-custom"></span>
                <span class="label-text"><?php _e('SEO Description', 'build360-ai'); ?></span>
            </label>
        </div>
    </div>

    <div class="keywords-section">
        <label for="build360_ai_keywords">
            <?php _e('Keywords (comma-separated):', 'build360-ai'); ?>
            <span class="build360-tooltip" data-tooltip="<?php esc_attr_e('Add relevant keywords to help generate more focused content.', 'build360-ai'); ?>"><span class="dashicons dashicons-editor-help"></span></span>
        </label>
        <div class="input-wrapper">
            <input type="text" id="build360_ai_keywords" name="build360_ai_keywords" value="<?php echo esc_attr($categories); ?>" placeholder="<?php esc_attr_e('e.g., technology, guide, tips', 'build360-ai'); ?>" />
        </div>
    </div>

    <div class="actions">
        <button type="button" id="build360_ai_generate" class="button button-primary generate-content">
            <i class="fas fa-magic"></i>
            <span class="button-text"><?php _e('Generate Content', 'build360-ai'); ?></span>
        </button>
        <span class="build360-tooltip" data-tooltip="<?php esc_attr_e('Generates AI content for selected fields. You\'ll review before applying.', 'build360-ai'); ?>"><span class="dashicons dashicons-editor-help"></span></span>
        <div class="spinner"></div>
    </div>

    <div class="generation-status" style="display: none;"></div>
</div>

<!-- Review Modal -->
<div id="build360-ai-review-modal" class="build360-ai-modal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2><?php _e('Review Generated Content', 'build360-ai'); ?></h2>
            <button type="button" class="close-modal build360-ai-review-cancel">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="modal-body" id="build360-ai-review-modal-body">
            <!-- Generated content will be populated here by JavaScript -->
        </div>
        <div class="modal-footer">
            <button type="button" class="button build360-ai-review-cancel"><?php _e('Cancel', 'build360-ai'); ?></button>
            <button type="button" class="button build360-ai-review-retry"><?php _e('Retry', 'build360-ai'); ?></button>
            <button type="button" class="button button-primary build360-ai-review-approve"><?php _e('Approve & Update Fields', 'build360-ai'); ?></button>
        </div>
    </div>
</div>

<style>
.build360-ai-meta-box {
    padding: 16px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    color: #333;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}
.build360-ai-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}
.token-info {
    padding: 8px 12px;
    background: #f0f7ff;
    border-radius: 6px;
    border-left: 3px solid #2271b1;
    display: inline-flex;
    align-items: center;
}
.token-label { font-weight: 600; margin-right: 8px; color: #2271b1; }
.token-count { font-weight: 700; color: #135e96; }
.language-selector select {
    padding: 6px 10px;
    border-radius: 4px;
    border: 1px solid #ddd;
    background-color: #f8f9fa;
    cursor: pointer;
    font-size: 13px;
}
.content-fields {
    margin-bottom: 20px;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 6px;
}
.content-fields h4 { margin: 0 0 12px; color: #23282d; font-size: 14px; font-weight: 600; }
.field-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 10px; }
.field-label {
    display: flex; align-items: center; margin-bottom: 8px; position: relative;
    cursor: pointer; padding: 6px 10px; border-radius: 4px; transition: background-color 0.2s;
}
.field-label:hover { background-color: #f0f0f0; }
.field-label input[type="checkbox"] { position: absolute; opacity: 0; cursor: pointer; height: 0; width: 0; }
.checkbox-custom {
    position: relative; display: inline-block; width: 18px; height: 18px;
    background-color: #fff; border: 2px solid #ddd; border-radius: 3px; margin-right: 10px; transition: all 0.2s;
}
.field-label input[type="checkbox"]:checked ~ .checkbox-custom { background-color: #2271b1; border-color: #2271b1; }
.checkbox-custom:after {
    content: ""; position: absolute; display: none; left: 5px; top: 1px;
    width: 5px; height: 10px; border: solid white; border-width: 0 2px 2px 0; transform: rotate(45deg);
}
.field-label input[type="checkbox"]:checked ~ .checkbox-custom:after { display: block; }
.label-text { font-size: 13px; }
.keywords-section { margin-bottom: 20px; }
.keywords-section label { display: block; margin-bottom: 8px; font-weight: 600; color: #23282d; font-size: 13px; }
.input-wrapper { position: relative; }
.keywords-section input {
    width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; transition: border-color 0.2s;
}
.keywords-section input:focus { border-color: #2271b1; box-shadow: 0 0 0 1px #2271b1; outline: none; }
.actions { display: flex; align-items: center; gap: 12px; }
.generate-content {
    display: flex; align-items: center; gap: 8px;
    padding: 8px 16px !important; height: auto !important; transition: all 0.2s !important;
}
.generate-content:hover { transform: translateY(-1px); box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); }
.generate-content i { font-size: 14px; }
.spinner { display: none; width: 20px; height: 20px; background: url(../images/spinner.gif) no-repeat; background-size: 20px 20px; }
.generation-status { margin-top: 15px; padding: 10px; border-radius: 4px; display: none; font-size: 13px; }
.generation-status.success { color: #0f5132; background-color: #d1e7dd; border: 1px solid #badbcc; }
.generation-status.error { color: #842029; background-color: #f8d7da; border: 1px solid #f5c2c7; }

/* Modal styles */
.build360-ai-modal {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    z-index: 100000; display: flex; align-items: center; justify-content: center;
}
.build360-ai-modal .modal-overlay {
    position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);
}
.build360-ai-modal .modal-content {
    position: relative; background-color: #fff; padding: 20px; border-radius: 5px;
    width: 60%; max-width: 700px; max-height: 80vh; overflow-y: auto; box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}
.build360-ai-modal .modal-header {
    display: flex; justify-content: space-between; align-items: center;
    border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px;
}
.build360-ai-modal .modal-header h2 { margin: 0; font-size: 1.25em; }
.build360-ai-modal .close-modal { background: none; border: none; font-size: 1.5em; cursor: pointer; padding: 0; line-height: 1; }
.build360-ai-modal .modal-footer { border-top: 1px solid #eee; padding-top: 15px; margin-top: 20px; text-align: right; }
.build360-ai-modal .modal-footer .button + .button { margin-left: 10px; }
#build360-ai-review-modal-body .review-field-group {
    margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px dashed #eee;
}
#build360-ai-review-modal-body .review-field-group:last-child { border-bottom: none; }
#build360-ai-review-modal-body .review-field-group h4 { margin-top: 0; margin-bottom: 5px; font-size: 1em; font-weight: bold; color: #444; }
#build360-ai-review-modal-body .review-content-preview {
    background-color: #f9f9f9; border: 1px solid #e5e5e5; padding: 10px; border-radius: 3px;
    max-height: 200px; overflow-y: auto; white-space: pre-wrap; word-wrap: break-word; font-size: 13px;
}
@media screen and (max-width: 782px) {
    .build360-ai-header { flex-direction: column; align-items: flex-start; gap: 10px; }
    .language-selector, .language-selector select { width: 100%; }
    .field-grid { grid-template-columns: 1fr; }
}
</style>
