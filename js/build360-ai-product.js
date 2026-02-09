/**
 * Build360 AI Product JavaScript
 */
console.log('Build360 AI Debug: Script file parsing started.');

jQuery(document).ready(function ($) {
    console.log('Build360 AI Debug: jQuery(document).ready fired.');

    // Initialize immediately (no window.load) because script may be loaded after load event.
    initBuild360AIProduct();

    function initBuild360AIProduct() {
        const $metaBox = $('.build360-ai-meta-box');
        if (!$metaBox.length) {
            console.error('Build360 AI Debug: Meta box .build360-ai-meta-box not found!');
            return;
        }
        console.log('Build360 AI Debug: MetaBox found. Length:', $metaBox.length);

        const $generateBtn = $('#build360_ai_generate');
        console.log('Build360 AI Debug: $generateBtn selector by ID #build360_ai_generate length:', $generateBtn.length);

        const $spinner = $metaBox.find('.spinner');
        const $status = $metaBox.find('.generation-status');
        const $languageSelector = $('#build360_ai_language');

        // Skip tooltip and validation utilities for now
        // Build360AIUtils.initTooltips();
        // Build360AIUtils.initNumberInputValidation();

        if ($generateBtn.length === 0) {
            console.error('Build360 AI Debug: Generate button with ID #build360_ai_generate not found! Click events will not be bound.');
        } else {
            console.log('Build360 AI Debug: Generate button FOUND. Attaching click handler.');
            $generateBtn.on('click', handleGenerateClick);
        }

        // Delegated modal buttons remain outside
    }

    function handleGenerateClick() {
        const $metaBox = $('.build360-ai-meta-box');
        const $generateBtn = $(this);
        const $spinner = $metaBox.find('.spinner');
        const $status = $metaBox.find('.generation-status');
        const $languageSelector = $('#build360_ai_language');
        let storedGeneratedContent = {};
        let storedFieldsToUpdate = [];

        console.log('Build360 AI Debug: Generate button clicked.');

        const fieldsToGenerate = [];
        $metaBox.find('input[name="build360_ai_fields[]"]:checked').each(function () {
            fieldsToGenerate.push($(this).val());
        });

        if (fieldsToGenerate.length === 0) {
            console.log('Build360 AI Debug: No fields selected.');
            $status.removeClass('success').addClass('error')
                .text((build360_ai_vars.i18n && build360_ai_vars.i18n.no_fields_selected) || 'Please select at least one field to generate.')
                .show();
            return;
        }

        const productTitle = $('#title').val();
        let productDescription = '';
        if (typeof tinyMCE !== 'undefined' && tinyMCE.get('content')) {
            productDescription = tinyMCE.get('content').getContent();
        } else {
            productDescription = $('#content').val();
        }

        const agentId = build360_ai_vars.current_agent_id || null;
        if (!agentId) {
            console.log('Build360 AI Debug: Agent ID not found.');
            $status.removeClass('success').addClass('error')
                .text((build360_ai_vars.i18n && build360_ai_vars.i18n.agent_not_assigned) || 'No AI Agent is assigned to this content type. Please check plugin settings.')
                .show();
            return;
        }

        const nonce = build360_ai_vars.nonces && build360_ai_vars.nonces.generate_content
            ? build360_ai_vars.nonces.generate_content
            : $('#build360_ai_nonce').val();

        if (!nonce) {
            console.log('Build360 AI Debug: Nonce not found.');
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
                agent_id: agentId
            },
            fields_to_update: fieldsToGenerate,
            language: $languageSelector.val()
        };
        console.log('Build360 AI Debug: Preparing AJAX request. Data:', ajaxData);

        $.ajax({
            url: build360_ai_vars.ajax_url,
            type: 'POST',
            data: ajaxData,
            beforeSend: function () {
                console.log('Build360 AI Debug: AJAX request starting...');
            },
            success: function (response) {
                console.log('Build360 AI Debug: AJAX success. Response:', response);
                const $reviewModal = $('#build360-ai-review-modal');
                const $reviewModalBody = $('#build360-ai-review-modal-body');

                if (!$reviewModal.length || !$reviewModalBody.length) {
                    console.error('Build360 AI Debug: Modal elements not found!');
                    $status.removeClass('success').addClass('error').text('Modal missing.').show();
                    $generateBtn.prop('disabled', false);
                    $spinner.removeClass('is-active');
                    return;
                }

                if (response.success && response.data && response.data.content && response.data.content.data) {
                    const dataFromApi = response.data.content.data;
                    storedGeneratedContent = dataFromApi;
                    storedFieldsToUpdate = fieldsToGenerate;
                    window.storedGeneratedContent = dataFromApi;
                    window.storedFieldsToUpdate = fieldsToGenerate;

                    $reviewModalBody.empty();
                    let hasContent = false;

                    const displayLabels = {
                        'title': 'Product Name',
                        'description': 'Description',
                        'short_description': 'Short Description',
                        'seo_title': 'SEO Title',
                        'seo_description': 'SEO Description',
                        'image_alt': 'Image Alt Text'
                    };

                    fieldsToGenerate.forEach(function (key) {
                        let generatedText = null;

                        if (key === 'title' && dataFromApi.title) generatedText = dataFromApi.title;
                        else if (key === 'description' && dataFromApi.content) generatedText = dataFromApi.content;
                        else if (key === 'short_description' && dataFromApi.short_description) generatedText = dataFromApi.short_description;
                        else if (key === 'seo_title' && dataFromApi.meta_title) generatedText = dataFromApi.meta_title;
                        else if (key === 'seo_description' && dataFromApi.meta_description) generatedText = dataFromApi.meta_description;
                        else if (key === 'image_alt' && dataFromApi.image_alt) generatedText = dataFromApi.image_alt;

                        const headingText = displayLabels[key] || key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());

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
                    $status.addClass('error').text('Generation failed.').show();
                }
            },
            error: function (xhr, status, error) {
                console.log('Build360 AI Debug: AJAX error. Status:', status, 'Error:', error, 'XHR:', xhr);
                $status.addClass('error').text('AJAX error').show();
            },
            complete: function () {
                console.log('Build360 AI Debug: AJAX request completed.');
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

        fieldKeysToUpdate.forEach(function (key) {
            let textToApply = null;

            if (key === 'title' && apiData.title) textToApply = apiData.title;
            else if (key === 'description' && apiData.content) textToApply = apiData.content;
            else if (key === 'short_description' && apiData.short_description) textToApply = apiData.short_description;
            else if (key === 'seo_title' && apiData.meta_title) textToApply = apiData.meta_title;
            else if (key === 'seo_description' && apiData.meta_description) textToApply = apiData.meta_description;
            else if (key === 'image_alt' && apiData.image_alt) textToApply = apiData.image_alt;

            if (textToApply) {
                if (key === 'title') { $('#title').val(textToApply).trigger('input'); }
                else if (key === 'description') { if (typeof tinyMCE !== 'undefined' && tinyMCE.get('content')) tinyMCE.get('content').setContent(textToApply); else $('#content').val(textToApply); }
                else if (key === 'short_description') { if (typeof tinyMCE !== 'undefined' && tinyMCE.get('excerpt')) tinyMCE.get('excerpt').setContent(textToApply); else $('#excerpt').val(textToApply); }
                else if (key === 'seo_title') {
                    $('#yoast_wpseo_title, input[name="yoast_wpseo_title"], #rank_math_title').val(textToApply).trigger('input');
                    // Update Yoast React UI via Redux store
                    if (window.wp && wp.data && wp.data.dispatch('yoast-seo/editor')) {
                        wp.data.dispatch('yoast-seo/editor').updateData({ title: textToApply });
                    }
                }
                else if (key === 'seo_description') {
                    $('#yoast_wpseo_metadesc, textarea[name="yoast_wpseo_metadesc"], #rank_math_description').val(textToApply).trigger('input');
                    // Update Yoast React UI via Redux store
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

    // Delegated handler in case button is added later or wasn\'t found at ready time
    $(document).on('click', '#build360_ai_generate', function (event) {
        console.log('Build360 AI Debug: Delegated click handler triggered for #build360_ai_generate button.');
        handleGenerateClick.call(this, event);
    });

    // Additional debug: log meta box HTML structure (first 500 chars) for inspection
    console.log('Build360 AI Debug: MetaBox HTML snippet:', $('.build360-ai-meta-box').html().substring(0, 500));

});
