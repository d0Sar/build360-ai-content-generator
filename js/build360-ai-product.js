/**
 * Build360 AI Product JavaScript
 */
jQuery(document).ready(function ($) {

    // Initialize immediately (no window.load) because script may be loaded after load event.
    initBuild360AIProduct();

    function initBuild360AIProduct() {
        const $metaBox = $('.build360-ai-meta-box');
        if (!$metaBox.length) {
            return;
        }

        const $generateBtn = $('#build360_ai_generate');
        if ($generateBtn.length) {
            $generateBtn.on('click', handleGenerateClick);
        }
    }

    function handleGenerateClick() {
        const $metaBox = $('.build360-ai-meta-box');
        const $generateBtn = $(this);
        const $spinner = $metaBox.find('.spinner');
        const $status = $metaBox.find('.generation-status');

        const fieldsToGenerate = [];
        $metaBox.find('input[name="build360_ai_fields[]"]:checked').each(function () {
            fieldsToGenerate.push($(this).val());
        });

        if (fieldsToGenerate.length === 0) {
            $status.removeClass('success').addClass('error')
                .text((build360_ai_vars.i18n && build360_ai_vars.i18n.no_fields_selected) || 'Please select at least one field to generate.')
                .show();
            return;
        }

        // Get title: classic editor #title, or Gutenberg post title
        let productTitle = $('#title').val();
        if (!productTitle && window.wp && wp.data && wp.data.select('core/editor')) {
            productTitle = wp.data.select('core/editor').getEditedPostAttribute('title') || '';
        }

        // Get content: classic editor TinyMCE/textarea, or Gutenberg
        let productDescription = '';
        if (typeof tinyMCE !== 'undefined' && tinyMCE.get('content')) {
            productDescription = tinyMCE.get('content').getContent();
        } else if ($('#content').length) {
            productDescription = $('#content').val();
        } else if (window.wp && wp.data && wp.data.select('core/editor')) {
            productDescription = wp.data.select('core/editor').getEditedPostAttribute('content') || '';
        }

        const agentId = build360_ai_vars.current_agent_id || null;
        if (!agentId) {
            $status.removeClass('success').addClass('error')
                .text((build360_ai_vars.i18n && build360_ai_vars.i18n.agent_not_assigned) || 'No AI Agent is assigned to this content type. Please check plugin settings.')
                .show();
            return;
        }

        const nonce = build360_ai_vars.nonces && build360_ai_vars.nonces.generate_content
            ? build360_ai_vars.nonces.generate_content
            : $('#build360_ai_nonce').val();

        if (!nonce) {
            $status.removeClass('success').addClass('error')
                .text((build360_ai_vars.i18n && build360_ai_vars.i18n.nonce_error) || 'Security token is missing. Please refresh the page.')
                .show();
            return;
        }

        $generateBtn.prop('disabled', true);
        $spinner.addClass('is-active');
        $status.hide().empty();

        const ajaxData = {
            action: 'build360_ai_generate_content',
            nonce: nonce,
            product_id: $('#post_ID').val(),
            payload: {
                title: productTitle,
                description: productDescription,
                type: 'post',
                agent_id: agentId,
                categories: build360_ai_vars.product_categories || '',
                attributes: build360_ai_vars.product_attributes || '',
                tags: build360_ai_vars.product_tags || '',
                keywords: $('#build360_ai_keywords').val() || ''
            },
            fields_to_update: fieldsToGenerate
        };

        $.ajax({
            url: build360_ai_vars.ajax_url,
            type: 'POST',
            data: ajaxData,
            success: function (response) {
                console.log('Build360 AI: Response:', response);
                const $reviewModal = $('#build360-ai-review-modal');
                const $reviewModalBody = $('#build360-ai-review-modal-body');

                if (!$reviewModal.length || !$reviewModalBody.length) {
                    $status.removeClass('success').addClass('error').text('Modal missing.').show();
                    $generateBtn.prop('disabled', false);
                    $spinner.removeClass('is-active');
                    return;
                }

                // The API response is nested: response.data.content contains the generated map.
                // The map may have a "data" wrapper (from Laravel) or be flat.
                var generatedMap = null;
                if (response.success && response.data && response.data.content) {
                    generatedMap = response.data.content.data || response.data.content;
                }

                if (generatedMap && typeof generatedMap === 'object') {
                    console.log('Build360 AI: generatedMap:', JSON.stringify(generatedMap));
                    console.log('Build360 AI: fieldsToGenerate:', fieldsToGenerate);
                    window.storedGeneratedContent = generatedMap;
                    window.storedFieldsToUpdate = fieldsToGenerate;

                    $reviewModalBody.empty();
                    let hasContent = false;

                    const displayLabels = {
                        'title': 'Product Name',
                        'content': 'Post Content',
                        'description': 'Description',
                        'short_description': 'Short Description',
                        'seo_title': 'SEO Title',
                        'seo_description': 'SEO Description',
                        'image_alt': 'Image Alt Text'
                    };

                    // Map checkbox field keys to API response keys
                    const fieldToApiKey = {
                        'title': 'title',
                        'content': 'content',
                        'description': 'content',
                        'short_description': 'short_description',
                        'seo_title': 'meta_title',
                        'seo_description': 'meta_description',
                        'image_alt': 'image_alt'
                    };

                    fieldsToGenerate.forEach(function (key) {
                        var apiKey = fieldToApiKey[key];
                        var generatedText = apiKey ? generatedMap[apiKey] : null;
                        console.log('Build360 AI: field=' + key + ' apiKey=' + apiKey + ' value=' + (generatedText || '(empty)'));

                        const headingText = displayLabels[key] || key.replace(/_/g, ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); });

                        if (generatedText) {
                            hasContent = true;
                            $reviewModalBody.append(
                                '<div class="review-field-group"><h4>' + headingText +
                                '</h4><div class="review-content-preview">' + generatedText + '</div></div>'
                            );
                        }
                    });
                    if (hasContent) {
                        $reviewModal.fadeIn();
                    } else {
                        $status.addClass('error').text('No content returned.').show();
                    }
                } else {
                    var errorMsg = (response.data && response.data.message) ? response.data.message : 'Generation failed.';
                    $status.addClass('error').text(errorMsg).show();
                }
            },
            error: function (xhr, status, error) {
                console.error('Build360 AI: AJAX error:', status, error);
                $status.addClass('error').text('AJAX error').show();
            },
            complete: function () {
                $spinner.removeClass('is-active');
                if (!$('#build360-ai-review-modal').is(':visible')) {
                    $('#build360_ai_generate').prop('disabled', false);
                }
            }
        });
    }

    // Delegated event handling for modal buttons
    $(document.body).on('click', '#build360-ai-review-modal .build360-ai-review-approve', function () {
        console.log('Build360 AI Debug: Approve clicked');
        applyGeneratedContent();
    });

    $(document.body).on('click', '#build360-ai-review-modal .build360-ai-review-cancel', function () {
        $('#build360-ai-review-modal').fadeOut();
        $('#build360_ai_generate').prop('disabled', false);
    });

    $(document.body).on('click', '#build360-ai-review-modal .build360-ai-review-retry', function () {
        $('#build360-ai-review-modal').fadeOut();
        $('#build360_ai_generate').prop('disabled', false).trigger('click');
    });

    function applyGeneratedContent() {
        const apiData = window.storedGeneratedContent || {};
        const fieldKeysToUpdate = window.storedFieldsToUpdate || [];
        const $status = $('.build360-ai-meta-box .generation-status');
        if (!Object.keys(apiData).length) {
            $status.addClass('error').text('No data to apply').show(); return;
        }

        const fieldToApiKey = {
            'title': 'title',
            'content': 'content',
            'description': 'content',
            'short_description': 'short_description',
            'seo_title': 'meta_title',
            'seo_description': 'meta_description',
            'image_alt': 'image_alt'
        };

        fieldKeysToUpdate.forEach(function (key) {
            var apiKey = fieldToApiKey[key];
            var textToApply = apiKey ? apiData[apiKey] : null;

            if (textToApply) {
                if (key === 'title') {
                    $('#title').val(textToApply).trigger('input');
                    if (window.wp && wp.data && wp.data.dispatch('core/editor')) {
                        wp.data.dispatch('core/editor').editPost({ title: textToApply });
                    }
                }
                else if (key === 'content' || key === 'description') {
                    if (typeof tinyMCE !== 'undefined' && tinyMCE.get('content')) {
                        tinyMCE.get('content').setContent(textToApply);
                    } else if ($('#content').length) {
                        $('#content').val(textToApply);
                    } else if (window.wp && wp.data && wp.data.dispatch('core/editor')) {
                        wp.data.dispatch('core/editor').editPost({ content: textToApply });
                    }
                }
                else if (key === 'short_description') {
                    if (typeof tinyMCE !== 'undefined' && tinyMCE.get('excerpt')) tinyMCE.get('excerpt').setContent(textToApply);
                    else $('#excerpt').val(textToApply);
                }
                else if (key === 'seo_title') {
                    $('#yoast_wpseo_title, input[name="yoast_wpseo_title"], #rank_math_title').val(textToApply).trigger('input');
                    if (window.wp && wp.data && wp.data.dispatch('yoast-seo/editor')) {
                        wp.data.dispatch('yoast-seo/editor').updateData({ title: textToApply });
                    }
                }
                else if (key === 'seo_description') {
                    $('#yoast_wpseo_metadesc, textarea[name="yoast_wpseo_metadesc"], #rank_math_description').val(textToApply).trigger('input');
                    if (window.wp && wp.data && wp.data.dispatch('yoast-seo/editor')) {
                        wp.data.dispatch('yoast-seo/editor').updateData({ description: textToApply });
                    }
                }
            }
        });
        $status.removeClass('error').addClass('success').text('Fields updated').show();
        $('#build360-ai-review-modal').fadeOut();
        $('#build360_ai_generate').prop('disabled', false);
    }

    // NOTE: Direct binding is done in initBuild360AIProduct(). No delegated handler needed
    // as the button exists in the PHP-rendered meta box at DOM ready time.

});
