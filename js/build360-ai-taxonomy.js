/**
 * Build360 AI Taxonomy JavaScript
 * Matches product generation flow
 */
jQuery(document).ready(function ($) {
    var $generateBtn = $('#build360_ai_taxonomy_generate');
    var $spinner = $('#build360_ai_taxonomy_spinner');
    var $status = $('#build360_ai_taxonomy_status');
    var $modal = $('#build360-ai-taxonomy-review-modal');
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
        var termDescription = $('#description').length ? $('#description').val() : ($('#tag-description').length ? $('#tag-description').val() : '');
        var keywords = $('#build360_ai_tax_keywords').val() || '';

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

        $generateBtn.prop('disabled', true).text('Generating...');
        $spinner.addClass('is-active');
        $status.removeClass('success error').hide();
        closeModal();

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
                    agent_id: agentId,
                    keywords: keywords
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
                                '<div class="review-field-group">' +
                                    '<h4>' + heading + '</h4>' +
                                    '<div class="review-content-preview">' + text + '</div>' +
                                '</div>'
                            );
                        }
                    });

                    if (hasContent) {
                        openModal();
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
                $generateBtn.prop('disabled', false).text('Generate Content');
            }
        });
    });

    // Approve: apply content to fields
    $(document).on('click', '.build360-ai-taxonomy-approve', function () {
        storedFields.forEach(function (key) {
            var text = null;
            if (key === 'description' && storedData.content) text = storedData.content;
            else if (key === 'seo_title' && storedData.meta_title) text = storedData.meta_title;
            else if (key === 'seo_description' && storedData.meta_description) text = storedData.meta_description;

            if (text) {
                if (key === 'description') {
                    // TinyMCE first (taxonomy edit page uses it)
                    if (typeof tinymce !== 'undefined' && tinymce.get('description')) {
                        tinymce.get('description').setContent(text);
                    }
                    // Also set the textarea (for Code/text mode)
                    var $descField = $('#description').length ? $('#description') : $('#tag-description');
                    $descField.val(text).trigger('change');
                } else if (key === 'seo_title') {
                    // Hidden input (submitted with form)
                    $('#hidden_wpseo_title').val(text).trigger('change');
                    // Update Yoast React UI via Redux store (same as product JS)
                    if (window.wp && wp.data && wp.data.dispatch('yoast-seo/editor')) {
                        wp.data.dispatch('yoast-seo/editor').updateData({ title: text });
                    }
                    // RankMath fallback
                    $('input[name="rank_math_title"]').val(text).trigger('input');
                } else if (key === 'seo_description') {
                    // Hidden input (submitted with form)
                    $('#hidden_wpseo_desc').val(text).trigger('change');
                    // Update Yoast React UI via Redux store (same as product JS)
                    if (window.wp && wp.data && wp.data.dispatch('yoast-seo/editor')) {
                        wp.data.dispatch('yoast-seo/editor').updateData({ description: text });
                    }
                    // RankMath fallback
                    $('textarea[name="rank_math_description"]').val(text).trigger('input');
                }
            }
        });

        closeModal();
        showStatus('Fields updated. Click "Update" to save the term.', 'success');
    });

    // Cancel
    $(document).on('click', '.build360-ai-taxonomy-cancel', function () {
        closeModal();
    });

    // Retry
    $(document).on('click', '.build360-ai-taxonomy-retry', function () {
        closeModal();
        $generateBtn.trigger('click');
    });

    // Close modal on overlay click
    $(document).on('click', '#build360-ai-taxonomy-review-modal .modal-overlay', function () {
        closeModal();
    });

    function openModal() {
        $modal.css('display', 'flex');
    }

    function closeModal() {
        $modal.hide();
    }

    function showStatus(message, type) {
        $status.removeClass('success error').addClass(type).text(message).show();
    }
});
