<?php
/**
 * Taxonomy meta fields template for AI generation
 *
 * @package Build360_AI
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

wp_nonce_field('build360_ai_generate_content', 'build360_ai_taxonomy_nonce');
?>

<tr class="form-field">
    <th scope="row" colspan="2">
        <h3 style="margin: 0; padding: 10px 0 5px; border-top: 1px solid #ddd;">
            <?php _e('Build360 AI Content Generator', 'build360-ai'); ?>
        </h3>
    </th>
</tr>

<tr class="form-field build360-ai-taxonomy-row">
    <th scope="row">
        <label><?php _e('Generate Content For', 'build360-ai'); ?></label>
    </th>
    <td>
        <div class="build360-ai-taxonomy-fields" style="margin-bottom: 12px;">
            <label style="display: inline-block; margin-right: 15px;">
                <input type="checkbox" name="build360_ai_tax_fields[]" value="description" checked />
                <?php _e('Category Description', 'build360-ai'); ?>
            </label>
            <label style="display: inline-block; margin-right: 15px;">
                <input type="checkbox" name="build360_ai_tax_fields[]" value="seo_title" />
                <?php _e('SEO Title', 'build360-ai'); ?>
            </label>
            <label style="display: inline-block; margin-right: 15px;">
                <input type="checkbox" name="build360_ai_tax_fields[]" value="seo_description" />
                <?php _e('SEO Description', 'build360-ai'); ?>
            </label>
        </div>

        <div style="margin-bottom: 12px;">
            <button type="button" id="build360_ai_taxonomy_generate" class="button button-primary">
                <?php _e('Generate Content', 'build360-ai'); ?>
            </button>
            <span class="spinner" id="build360_ai_taxonomy_spinner" style="float: none; margin-top: 0;"></span>
        </div>

        <div id="build360_ai_taxonomy_status" style="display: none; padding: 8px 12px; border-radius: 4px; margin-bottom: 10px;"></div>

        <div id="build360_ai_taxonomy_review" style="display: none; background: #f9f9f9; border: 1px solid #e5e5e5; border-radius: 4px; padding: 15px; margin-bottom: 10px;">
            <h4 style="margin-top: 0;"><?php _e('Review Generated Content', 'build360-ai'); ?></h4>
            <div id="build360_ai_taxonomy_review_body"></div>
            <div style="margin-top: 12px; text-align: right;">
                <button type="button" id="build360_ai_taxonomy_cancel" class="button"><?php _e('Cancel', 'build360-ai'); ?></button>
                <button type="button" id="build360_ai_taxonomy_retry" class="button"><?php _e('Retry', 'build360-ai'); ?></button>
                <button type="button" id="build360_ai_taxonomy_approve" class="button button-primary"><?php _e('Approve & Apply', 'build360-ai'); ?></button>
            </div>
        </div>

        <p class="description"><?php _e('Generate AI content for this category. Content is applied to the fields above when you click Approve, then save the term normally.', 'build360-ai'); ?></p>
    </td>
</tr>
