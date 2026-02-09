/**
 * Build360 AI Taxonomy JavaScript
 */
jQuery(document).ready(function ($) {
    var $generateBtn = $('#build360_ai_taxonomy_generate');
    var $spinner = $('#build360_ai_taxonomy_spinner');
    var $status = $('#build360_ai_taxonomy_status');
    var $review = $('#build360_ai_taxonomy_review');
    var $reviewBody = $('#build360_ai_taxonomy_review_body');
    var storedData = {};
    var storedFields = [];

    if (!$generateBtn.length) {
        return;
    }

    $generateBtn.on('click', function () {
        var fieldsToGenerate = [];
        $('input[name="build360_ai_tax_fields[]"]:checked').each(function () {
            fieldsToGenerate.push($(this).val());
        });

        if (fieldsToGenerate.length === 0) {
            showStatus('Please select at least one field to generate.', 'error');
            return;
        }

        var termName = $('#name').val();
        var termDescription = $('#tag-description').length ? $('#tag-description').val() : '';

        var agentId = build360_ai_vars.current_agent_id || null;
        if (!agentId) {
            showStatus('No AI Agent is assigned to this content type. Please check plugin settings.', 'error');
            return;
        }

        var nonce = build360_ai_vars.nonces && build360_ai_vars.nonces.generate_content;
        if (!nonce) {
            showStatus('Security token is missing. Please refresh the page.', 'error');
            return;
        }

        $generateBtn.prop('disabled', true);
        $spinner.addClass('is-active');
        $status.hide();
        $review.hide();

        $.ajax({
            url: build360_ai_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'build360_ai_generate_content',
                nonce: nonce,
                product_id: 0,
                payload: {
                    title: termName,
                    description: termDescription,
                    type: 'taxonomy',
                    agent_id: agentId
                },
                fields_to_update: fieldsToGenerate
            },
            success: function (response) {
                if (response.success && response.data && response.data.content && response.data.content.data) {
                    var dataFromApi = response.data.content.data;
                    storedData = dataFromApi;
                    storedFields = fieldsToGenerate;

                    $reviewBody.empty();
                    var hasContent = false;

                    var labels = {
                        'description': 'Category Description',
                        'seo_title': 'SEO Title',
                        'seo_description': 'SEO Description'
                    };

                    fieldsToGenerate.forEach(function (key) {
                        var text = null;
                        if (key === 'description' && dataFromApi.content) text = dataFromApi.content;
                        else if (key === 'seo_title' && dataFromApi.meta_title) text = dataFromApi.meta_title;
                        else if (key === 'seo_description' && dataFromApi.meta_description) text = dataFromApi.meta_description;

                        if (text) {
                            hasContent = true;
                            var heading = labels[key] || key;
                            $reviewBody.append(
                                '<div style="margin-bottom: 10px;">' +
                                '<strong>' + heading + ':</strong>' +
                                '<div style="background: #fff; border: 1px solid #ddd; padding: 8px; border-radius: 3px; margin-top: 4px; white-space: pre-wrap;">' + text + '</div>' +
                                '</div>'
                            );
                        }
                    });

                    if (hasContent) {
                        $review.show();
                    } else {
                        showStatus('No content returned.', 'error');
                    }
                } else {
                    var msg = (response.data && response.data.message) ? response.data.message : 'Generation failed.';
                    showStatus(msg, 'error');
                }
            },
            error: function () {
                showStatus('AJAX error occurred.', 'error');
            },
            complete: function () {
                $spinner.removeClass('is-active');
                if (!$review.is(':visible')) {
                    $generateBtn.prop('disabled', false);
                }
            }
        });
    });

    // Approve: apply content to fields
    $('#build360_ai_taxonomy_approve').on('click', function () {
        storedFields.forEach(function (key) {
            var text = null;
            if (key === 'description' && storedData.content) text = storedData.content;
            else if (key === 'seo_title' && storedData.meta_title) text = storedData.meta_title;
            else if (key === 'seo_description' && storedData.meta_description) text = storedData.meta_description;

            if (text) {
                if (key === 'description') {
                    $('#tag-description').val(text);
                } else if (key === 'seo_title') {
                    // Yoast taxonomy SEO title
                    $('input[name="wpseo_taxonomy_meta[title]"], #wpseo_taxonomy_title, input[name="rank_math_title"]').val(text).trigger('input');
                } else if (key === 'seo_description') {
                    // Yoast taxonomy SEO description
                    $('textarea[name="wpseo_taxonomy_meta[desc]"], #wpseo_taxonomy_desc, textarea[name="rank_math_description"]').val(text).trigger('input');
                }
            }
        });

        $review.hide();
        showStatus('Fields updated. Click "Update" to save the term.', 'success');
        $generateBtn.prop('disabled', false);
    });

    // Cancel
    $('#build360_ai_taxonomy_cancel').on('click', function () {
        $review.hide();
        $generateBtn.prop('disabled', false);
    });

    // Retry
    $('#build360_ai_taxonomy_retry').on('click', function () {
        $review.hide();
        $generateBtn.prop('disabled', false).trigger('click');
    });

    function showStatus(message, type) {
        var bgColor = type === 'error' ? '#f8d7da' : '#d1e7dd';
        var textColor = type === 'error' ? '#842029' : '#0f5132';
        var borderColor = type === 'error' ? '#f5c2c7' : '#badbcc';
        $status.css({
            'background-color': bgColor,
            'color': textColor,
            'border': '1px solid ' + borderColor
        }).text(message).show();
    }
});
