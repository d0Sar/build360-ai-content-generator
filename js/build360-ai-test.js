/**
 * Build360 AI Test Connection and Test Generation Scripts
 */
jQuery(document).ready(function($) {
    // Toggle API Details
    $('#build360_ai_toggle_api_details').on('click', function(e) {
        e.preventDefault();
        const $button = $(this);
        const $details = $('#build360_ai_api_details');

        if ($details.hasClass('visible')) {
            $details.removeClass('visible');
            $button.html('<span class="dashicons dashicons-info-outline"></span> ' +
                        (build360_ai_vars.strings.show_api_details || 'Show API Details'));
        } else {
            $details.addClass('visible');
            $button.html('<span class="dashicons dashicons-dismiss"></span> ' +
                        (build360_ai_vars.strings.hide_api_details || 'Hide API Details'));
        }
    });

    // Test Connection
    $('#build360_ai_test_connection').on('click', function () {
        const $button = $(this);
        const $spinner = $button.next('.spinner');
        const $result = $('#build360_ai_connection_result');
        const apiKey = $('#build360_ai_api_key').val();
        const domain = $('#build360_ai_domain').val();

        if (!apiKey || !domain) {
            $result.removeClass('success').addClass('error')
                .html(build360_ai_vars.i18n ? build360_ai_vars.i18n.enter_api_details : 'Please enter API Key and Domain first.');
            return;
        }

        // Show loading state
        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        $result.removeClass('success error').empty();

        // Make AJAX request
        $.ajax({
            url: build360_ai_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'build360_ai_test_connection',
                nonce: build360_ai_vars.nonces.test_connection,
                api_key: apiKey,
                domain: domain
            },
            success: function (response) {
                if (response.success) {
                    $result.removeClass('error').addClass('success').html(response.data.message);
                } else {
                    $result.removeClass('success').addClass('error').html(response.data.message);
                }
            },
            error: function () {
                $result.removeClass('success').addClass('error')
                    .html(build360_ai_vars.i18n ? build360_ai_vars.i18n.ajax_error : 'An error occurred.');
            },
            complete: function () {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    });

    // Test Generation
    $('#build360_ai_generate_test').on('click', function() {
        const $button = $(this);
        const $spinner = $button.next('.spinner');
        const $resultContainer = $('#build360_ai_test_result_container');
        const $copyButton = $('#build360_ai_copy_test_result');

        const contentType = $('#build360_ai_test_content_type').val();
        const field = $('#build360_ai_test_field').val();
        const keywords = $('#build360_ai_test_keywords').val();
        const name = $('#build360_ai_test_name').val();

        // Validate required fields
        let errors = [];
        if (!name) {
            errors.push('Sample name/title is required.');
        }

        if (contentType === 'product' && !keywords) {
            errors.push('Keywords are required for product content generation.');
        }

        if (errors.length > 0) {
            let errorHtml = '<div class="build360-ai-test-error">Please fix the following errors:<ul class="build360-ai-error-list">';
            errors.forEach(function(error) {
                errorHtml += '<li>' + error + '</li>';
            });
            errorHtml += '</ul></div>';
            $resultContainer.html(errorHtml);
            return;
        }

        // Show loading state
        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        $resultContainer.html('<div class="build360-ai-test-placeholder">Generating content...</div>');
        $copyButton.prop('disabled', true);

        // Make AJAX request
        $.ajax({
            url: build360_ai_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'build360_ai_test_generate',
                nonce: build360_ai_vars.nonce,
                content_type: contentType,
                field: field,
                keywords: keywords,
                name: name
            },
            success: function(response) {
                if (response.success) {
                    // Get content from response
                    let content = response.data.content;
                    let debugInfo = response.data.debug_info;

                    // Log debug info to console
                    if (debugInfo) {
                        console.log('Debug info:', debugInfo);
                    }

                    // Display the content directly - it should be a properly formatted string from PHP
                    if (content) {
                        $resultContainer.text(content);
                    } else {
                        $resultContainer.text('No content generated.');
                    }
                    $copyButton.prop('disabled', false);
                } else {
                    // Handle error response
                    let errorMessage = response.data.message || 'Error generating content.';

                    // Check if there are specific API errors
                    if (response.data.errors && response.data.errors.api_error) {
                        // Format API errors
                        let apiErrors = '';
                        if (typeof response.data.error_data === 'object' &&
                            response.data.error_data.api_error &&
                            response.data.error_data.api_error.data &&
                            response.data.error_data.api_error.data.errors) {

                            const errors = response.data.error_data.api_error.data.errors;
                            apiErrors = '<ul class="build360-ai-error-list">';

                            for (const field in errors) {
                                if (errors.hasOwnProperty(field)) {
                                    apiErrors += '<li>' + errors[field] + '</li>';
                                }
                            }

                            apiErrors += '</ul>';
                            errorMessage = 'API Error: ' + (response.data.error_data.api_error.data.message || 'Validation failed');
                        }

                        $resultContainer.html('<div class="build360-ai-test-error">' + errorMessage + apiErrors + '</div>');
                    } else {
                        $resultContainer.html('<div class="build360-ai-test-error">' + errorMessage + '</div>');
                    }
                }
            },
            error: function() {
                $resultContainer.html('<div class="build360-ai-test-error">' + (build360_ai_vars.ajax_error || 'An error occurred while generating content.') + '</div>');
            },
            complete: function() {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    });

    // Copy to Clipboard
    $('#build360_ai_copy_test_result').on('click', function() {
        const $button = $(this);
        const content = $('#build360_ai_test_result_container').text();

        // Use the modern clipboard API if available
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(content)
                .then(() => {
                    const originalText = $button.html();
                    $button.html('<span class="dashicons dashicons-yes"></span> ' + (build360_ai_vars.copied || 'Copied!'));

                    setTimeout(function() {
                        $button.html(originalText);
                    }, 2000);
                })
                .catch(err => {
                    console.error('Failed to copy: ', err);
                    alert(build360_ai_vars.copy_error || 'Failed to copy content.');
                });
        } else {
            // Fallback for older browsers
            // Create temporary textarea
            const $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(content).select();

            // Copy to clipboard
            try {
                document.execCommand('copy');
                const originalText = $button.html();
                $button.html('<span class="dashicons dashicons-yes"></span> ' + (build360_ai_vars.copied || 'Copied!'));

                setTimeout(function() {
                    $button.html(originalText);
                }, 2000);
            } catch (err) {
                console.error('Failed to copy: ', err);
                alert(build360_ai_vars.copy_error || 'Failed to copy content.');
            }

            $temp.remove();
        }
    });

    // Update field options based on content type
    $('#build360_ai_test_content_type').on('change', function() {
        const contentType = $(this).val();
        const $fieldSelect = $('#build360_ai_test_field');

        // Clear current options
        $fieldSelect.empty();

        // Add options based on content type
        switch (contentType) {
            case 'product':
                $fieldSelect.append('<option value="description">' + (build360_ai_vars.description || 'Description') + '</option>');
                $fieldSelect.append('<option value="short_description">' + (build360_ai_vars.short_description || 'Short Description') + '</option>');
                $fieldSelect.append('<option value="meta_title">' + (build360_ai_vars.meta_title || 'Meta Title') + '</option>');
                $fieldSelect.append('<option value="meta_description">' + (build360_ai_vars.meta_description || 'Meta Description') + '</option>');
                break;
            case 'category':
                $fieldSelect.append('<option value="description">' + (build360_ai_vars.description || 'Description') + '</option>');
                $fieldSelect.append('<option value="meta_title">' + (build360_ai_vars.meta_title || 'Meta Title') + '</option>');
                $fieldSelect.append('<option value="meta_description">' + (build360_ai_vars.meta_description || 'Meta Description') + '</option>');
                break;
            case 'post':
                $fieldSelect.append('<option value="content">' + (build360_ai_vars.content || 'Content') + '</option>');
                $fieldSelect.append('<option value="excerpt">' + (build360_ai_vars.excerpt || 'Excerpt') + '</option>');
                $fieldSelect.append('<option value="meta_title">' + (build360_ai_vars.meta_title || 'Meta Title') + '</option>');
                $fieldSelect.append('<option value="meta_description">' + (build360_ai_vars.meta_description || 'Meta Description') + '</option>');
                break;
            default:
                $fieldSelect.append('<option value="description">' + (build360_ai_vars.description || 'Description') + '</option>');
        }
    });
});
