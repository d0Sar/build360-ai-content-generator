<?php
/**
 * Taxonomy meta fields template for AI generation
 * Uses EXACT same HTML structure and CSS classes as product metabox
 *
 * @package Build360_AI
 */

if (!defined('ABSPATH')) {
    exit;
}

wp_nonce_field('build360_ai_generate_content', 'build360_ai_taxonomy_nonce');

$term_id = isset($term) ? $term->term_id : 0;
$last_generated = $term_id ? get_term_meta($term_id, '_build360_ai_last_generated', true) : '';
?>

<tr class="form-field">
    <td colspan="2" style="padding: 0;">
        <div class="postbox" style="margin-top: 20px;">
            <div class="postbox-header">
                <h2 class="hndle" style="cursor: default;"><?php _e('Build360 AI Content Generator', 'build360-ai'); ?></h2>
            </div>
            <div class="inside">
                <div class="build360-ai-meta-box">
                    <div class="build360-ai-meta-box-header">
                        <?php if ($last_generated) : ?>
                            <p class="description">
                                <?php printf(__('Last generated: %s', 'build360-ai'), date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_generated))); ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="build360-ai-meta-box-content">
                        <div class="build360-ai-field-group">
                            <label><?php _e('Fields to Generate:', 'build360-ai'); ?></label>
                            <div class="build360-ai-field-options">
                                <label>
                                    <input type="checkbox" name="build360_ai_tax_fields[]" value="description" checked>
                                    <?php _e('Category Description', 'build360-ai'); ?>
                                </label>
                                <label>
                                    <input type="checkbox" name="build360_ai_tax_fields[]" value="seo_title">
                                    <?php _e('SEO Title', 'build360-ai'); ?>
                                </label>
                                <label>
                                    <input type="checkbox" name="build360_ai_tax_fields[]" value="seo_description">
                                    <?php _e('SEO Description', 'build360-ai'); ?>
                                </label>
                            </div>
                        </div>

                        <div class="build360-ai-field-group">
                            <label for="build360_ai_tax_keywords"><?php _e('Keywords (comma separated):', 'build360-ai'); ?></label>
                            <input type="text" id="build360_ai_tax_keywords" name="build360_ai_tax_keywords" class="regular-text" placeholder="<?php esc_attr_e('e.g., modern, stylish, durable', 'build360-ai'); ?>">
                            <p class="description">
                                <?php _e('Optional keywords to help guide the content generation.', 'build360-ai'); ?>
                            </p>
                        </div>

                        <div class="build360-ai-field-group">
                            <button type="button" class="button button-primary" id="build360_ai_taxonomy_generate">
                                <?php _e('Generate Content', 'build360-ai'); ?>
                            </button>
                            <span class="spinner" id="build360_ai_taxonomy_spinner"></span>
                        </div>

                        <div id="build360_ai_taxonomy_status" class="generation-status" style="display: none;"></div>
                    </div>

                    <!-- Review Modal -->
                    <div id="build360-ai-taxonomy-review-modal" class="build360-ai-modal" style="display: none;">
                        <div class="modal-overlay"></div>
                        <div class="modal-content">
                            <div class="modal-header">
                                <h2><?php _e('Review Generated Content', 'build360-ai'); ?></h2>
                                <button type="button" class="close-modal build360-ai-taxonomy-cancel">
                                    <span class="dashicons dashicons-no-alt"></span>
                                </button>
                            </div>
                            <div class="modal-body" id="build360_ai_taxonomy_review_body"></div>
                            <div class="modal-footer">
                                <button type="button" class="button build360-ai-taxonomy-cancel"><?php _e('Cancel', 'build360-ai'); ?></button>
                                <button type="button" class="button build360-ai-taxonomy-retry"><?php _e('Retry', 'build360-ai'); ?></button>
                                <button type="button" class="button button-primary build360-ai-taxonomy-approve"><?php _e('Approve & Update Fields', 'build360-ai'); ?></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </td>
</tr>

<style>
/* Override taxonomy page label styles to match product metabox */
.postbox .build360-ai-field-group > label {
    display: block !important;
    margin-bottom: 5px !important;
    font-weight: 600 !important;
}
.postbox .build360-ai-field-options label {
    display: block !important;
    margin-bottom: 8px !important;
    font-weight: normal !important;
}
.postbox .build360-ai-field-options input[type="checkbox"] {
    margin-right: 5px;
}
.postbox .build360-ai-meta-box-content {
    background: #fff;
    border: 1px solid #c3c4c7;
    padding: 20px;
}
.postbox .build360-ai-meta-box .spinner {
    float: none;
    margin: 0 10px;
    visibility: visible;
    opacity: 0;
}
.postbox .build360-ai-meta-box .spinner.is-active {
    opacity: 1;
}
.postbox .hndle {
    padding: 8px 12px !important;
}
/* Modal styles */
#build360-ai-taxonomy-review-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}
#build360-ai-taxonomy-review-modal .modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}
#build360-ai-taxonomy-review-modal .modal-content {
    position: relative;
    background-color: #fff;
    padding: 20px;
    border-radius: 5px;
    width: 60%;
    max-width: 700px;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}
#build360-ai-taxonomy-review-modal .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
    margin-bottom: 15px;
}
#build360-ai-taxonomy-review-modal .modal-header h2 { margin: 0; font-size: 1.25em; }
#build360-ai-taxonomy-review-modal .close-modal { background: none; border: none; font-size: 1.5em; cursor: pointer; padding: 0; line-height: 1; }
#build360-ai-taxonomy-review-modal .modal-body .review-field-group { margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px dashed #eee; }
#build360-ai-taxonomy-review-modal .modal-body .review-field-group:last-child { border-bottom: none; }
#build360-ai-taxonomy-review-modal .modal-body .review-field-group h4 { margin-top: 0; margin-bottom: 5px; font-size: 1em; font-weight: bold; color: #444; }
#build360-ai-taxonomy-review-modal .modal-body .review-content-preview { background-color: #f9f9f9; border: 1px solid #e5e5e5; padding: 10px; border-radius: 3px; max-height: 200px; overflow-y: auto; white-space: pre-wrap; word-wrap: break-word; font-size: 13px; }
#build360-ai-taxonomy-review-modal .modal-footer { border-top: 1px solid #eee; padding-top: 15px; margin-top: 20px; text-align: right; }
#build360-ai-taxonomy-review-modal .modal-footer .button + .button { margin-left: 10px; }
</style>
