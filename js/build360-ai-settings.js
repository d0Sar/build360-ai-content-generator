/**
 * Build360 AI Settings JavaScript
 */
jQuery(document).ready(function ($) {
    console.log('Build360 AI: settings.js loaded.');

    // Handle range input changes
    $('.build360-ai-range-control input[type="range"]').on('input', function () {
        const $this = $(this);
        const targetId = $this.data('target');
        const $targetInput = $('#' + targetId);

        // Update the number input value
        // Make sure we're dealing with a numeric value
        const numericValue = parseInt($this.val());
        $targetInput.val(numericValue);

        // Update the character count display if it exists
        const $characterCount = $this.closest('.build360-ai-range-control').find('.build360-ai-unit');
        if ($characterCount.length) {
            // Use the localized string if available, otherwise fallback to 'characters'
            const charactersText = build360_ai_vars.i18n.characters || 'χαρακτήρες';
            $characterCount.text(charactersText);
        }

        // Update recommendation text
        updateRecommendationText($this.val(), $this.closest('tr').find('.description'));
    });

    // Handle number input changes
    $('.build360-ai-range-control input[type="number"]').on('input', function () {
        const $this = $(this);
        // The slider ID is different from the number input ID
        // The number input ID is like 'build360_ai_max_length_title'
        // The slider ID is like 'build360_ai_max_length_slider_title'
        const id = $this.attr('id');
        const sliderId = id.replace('build360_ai_max_length_', 'build360_ai_max_length_slider_');
        const $slider = $('#' + sliderId);

        // Update the range slider value
        // Make sure we're dealing with a numeric value
        const numericValue = parseInt($this.val());
        $slider.val(numericValue);

        // Update the character count display if it exists
        const $characterCount = $this.closest('.build360-ai-range-control').find('.build360-ai-unit');
        if ($characterCount.length) {
            // Use the localized string if available, otherwise fallback to 'characters'
            const charactersText = build360_ai_vars.i18n.characters || 'χαρακτήρες';
            $characterCount.text(charactersText);
        }

        // Update recommendation text
        updateRecommendationText($this.val(), $this.closest('tr').find('.description'));
    });

    // Function to update recommendation text
    function updateRecommendationText(value, $descriptionElement) {
        if (!$descriptionElement.length) return;

        let recommendedText = '';
        // Make sure we're dealing with a numeric value
        value = parseInt(value) || 0; // Default to 0 if parsing fails

        // Use localized strings if available
        const i18nStrings = build360_ai_vars.i18n || {};

        if (value <= 150) {
            recommendedText = i18nStrings.recommended_short || 'Συνιστάται για σύντομο περιεχόμενο όπως τίτλοι ή εναλλακτικό κείμενο.';
        } else if (value <= 300) {
            recommendedText = i18nStrings.recommended_medium || 'Συνιστάται για περιεχόμενο μεσαίου μήκους όπως meta περιγραφές.';
        } else {
            recommendedText = i18nStrings.recommended_long || 'Συνιστάται για λεπτομερές περιεχόμενο όπως πλήρεις περιγραφές προϊόντων.';
        }

        // Extract the default value from the description
        // Try to match both English and Greek patterns
        let defaultMatch = $descriptionElement.text().match(/Default: (\d+)/);
        if (!defaultMatch) {
            defaultMatch = $descriptionElement.text().match(/\u03a0\u03c1\u03bf\u03b5\u03c0\u03b9\u03bb\u03bf\u03b3\u03ae: (\d+)/);
        }

        if (defaultMatch) {
            const defaultValue = defaultMatch[1];
            const charactersText = i18nStrings.characters || 'χαρακτήρες';
            $descriptionElement.text('Προεπιλογή: ' + defaultValue + ' ' + charactersText + '. ' + recommendedText);
        }
    }

    // Toggle password visibility
    $('.build360-ai-toggle-password').on('click', function () {
        const targetId = $(this).data('target');
        const $target = $('#' + targetId);
        const type = $target.attr('type') === 'password' ? 'text' : 'password';

        $target.attr('type', type);
        $(this).find('.dashicons').toggleClass('dashicons-visibility dashicons-hidden');
    });

    // Toggle API details
    $('#build360_ai_toggle_api_details').on('click', function () {
        const $details = $('#build360_ai_api_details');
        const $button = $(this);

        $details.slideToggle(200);

        if ($details.is(':visible')) {
            $button.html('<span class="dashicons dashicons-info-outline"></span> ' + build360_ai_vars.i18n.hide_api_details);
        } else {
            $button.html('<span class="dashicons dashicons-info-outline"></span> ' + build360_ai_vars.i18n.show_api_details);
        }
    });

    // Test connection button
    $('#build360_ai_test_connection').on('click', function () {
        const $button = $(this);
        const $spinner = $button.next('.spinner');
        const $result = $('#build360_ai_connection_result');
        const apiKey = $('#build360_ai_api_key').val();
        const domain = $('#build360_ai_domain').val();

        if (!apiKey || !domain) {
            $result.html('<div class="notice notice-error inline"><p>' + build360_ai_vars.i18n.enter_api_details + '</p></div>');
            return;
        }

        // Show loading state
        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        $result.empty();

        // Make AJAX request
        $.ajax({
            url: build360_ai_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'build360_ai_test_connection',
                nonce: build360_ai_vars.nonce,
                api_key: apiKey,
                domain: domain
            },
            success: function (response) {
                if (response.success) {
                    $result.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');

                    // Update token usage if available
                    if (response.data.token_usage) {
                        updateTokenUsage(response.data);
                    } else if (response.data.available_tokens !== undefined && response.data.used_tokens_website !== undefined) {
                        // Handle the new token balance structure from get_token_balance
                        updateTokenUsage({
                            token_balance: {
                                available: response.data.available_tokens,
                                used: response.data.used_tokens_website
                            }
                        });
                    }
                } else {
                    $result.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                }
            },
            error: function () {
                $result.html('<div class="notice notice-error inline"><p>' + build360_ai_vars.i18n.ajax_error + '</p></div>');
            },
            complete: function () {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    });

    // Tab navigation
    $('.build360-ai-tab-button').on('click', function () {
        const tabId = $(this).data('tab');

        // Update active tab button
        $('.build360-ai-tab-button').removeClass('active');
        $(this).addClass('active');

        // Show selected tab content
        $('.build360-ai-tab-pane').removeClass('active');
        $('#tab-' + tabId).addClass('active');
    });

    // Update token usage display
    function updateTokenUsage(data) {
        Build360AIUtils.updateTokenUsage(data);
    }

    // Initialize tooltips
    Build360AIUtils.initTooltips();

    // Fix length guide labels
    function updateLengthGuideLabels() {
        const i18nStrings = build360_ai_vars.i18n || {};

        // Update S, M, L labels if localized strings are available
        if (i18nStrings.short_label) {
            $('.build360-ai-length-guide-item .build360-ai-length-icon.short').text(i18nStrings.short_label.charAt(0));
        } else {
            $('.build360-ai-length-guide-item .build360-ai-length-icon.short').text('S');
        }

        if (i18nStrings.medium_label) {
            $('.build360-ai-length-guide-item .build360-ai-length-icon.medium').text(i18nStrings.medium_label.charAt(0));
        } else {
            $('.build360-ai-length-guide-item .build360-ai-length-icon.medium').text('M');
        }

        if (i18nStrings.long_label) {
            $('.build360-ai-length-guide-item .build360-ai-length-icon.long').text(i18nStrings.long_label.charAt(0));
        } else {
            $('.build360-ai-length-guide-item .build360-ai-length-icon.long').text('L');
        }

        // Update length guide labels
        if (i18nStrings.short_label) {
            $('.build360-ai-length-guide-item .build360-ai-length-label').eq(0).text((i18nStrings.length_guide_short_label || 'Σύντομο (100-150 χαρακτήρες)'));
            $('.build360-ai-length-guide-item .build360-ai-length-desc').eq(0).text((i18nStrings.length_guide_short_desc || 'Κατάλληλο για τίτλους, εναλλακτικό κείμενο'));
        }

        if (i18nStrings.medium_label) {
            $('.build360-ai-length-guide-item .build360-ai-length-label').eq(1).text((i18nStrings.length_guide_medium_label || 'Μεσαίο (200-300 χαρακτήρες)'));
            $('.build360-ai-length-guide-item .build360-ai-length-desc').eq(1).text((i18nStrings.length_guide_medium_desc || 'Κατάλληλο για σύντομες περιγραφές, meta περιγραφές'));
        }

        if (i18nStrings.long_label) {
            $('.build360-ai-length-guide-item .build360-ai-length-label').eq(2).text((i18nStrings.length_guide_long_label || 'Μεγάλο (350+ χαρακτήρες)'));
            $('.build360-ai-length-guide-item .build360-ai-length-desc').eq(2).text((i18nStrings.length_guide_long_desc || 'Κατάλληλο για πλήρεις περιγραφές, περιεχόμενο ιστολογίου'));
        }
    }

    // Call the function to update labels
    updateLengthGuideLabels();

    // Clean up any existing values that might have text in them
    function cleanupInputValues() {
        $('.build360-ai-range-control input[type="range"], .build360-ai-range-control input[type="number"]').each(function () {
            const $input = $(this);
            const currentValue = $input.val();

            // If the value contains non-numeric characters, extract just the number
            if (currentValue && isNaN(currentValue)) {
                const numericValue = parseInt(currentValue) || 0;
                $input.val(numericValue);
            }
        });
    }

    // Call the cleanup function when the page loads
    cleanupInputValues();

    // Settings Save Button Handler
    $('#build360_ai_save_settings').on('click', function (e) {
        e.preventDefault(); // Prevent any default button action
        console.log('Build360 AI: Custom Save Settings button clicked.');

        const $form = $('form#build360-ai-main-settings-form'); // Get the form
        const $button = $(this);
        const originalButtonText = $button.text();
        const $spinner = $button.next('.spinner');

        $button.text(build360_ai_vars.i18n.saving || 'Saving...').prop('disabled', true);
        $spinner.addClass('is-active');
        $('.wrap .notice').remove(); // Clear previous notices

        let settingsDataPayload = {}; // This will hold all data for the 'settings' key in the AJAX request

        // Standard input fields, select, textarea
        $form.find('input[name^="build360_ai_"], select[name^="build360_ai_"], textarea[name^="build360_ai_"]').each(function () {
            const $input = $(this);
            const name = $input.attr('name');

            // Skip agent assignment fields here, they are collected separately
            if (name.startsWith('build360_ai_agent_assignments[')) {
                return; // continue to next iteration
            }

            if ($input.is(':checkbox')) {
                settingsDataPayload[name] = $input.is(':checked') ? '1' : '0';
            } else if ($input.is(':radio')) {
                if ($input.is(':checked')) {
                    settingsDataPayload[name] = $input.val();
                }
            } else {
                settingsDataPayload[name] = $input.val();
            }
        });

        // Ensure build360_ai_debug_mode is explicitly set if the checkbox isn't checked (not in form data)
        if (settingsDataPayload['build360_ai_debug_mode'] === undefined) {
            settingsDataPayload['build360_ai_debug_mode'] = '0';
        }

        // Collect Agent Assignments
        const agentAssignments = [];
        $('#build360-ai-agent-assignments-tbody tr.build360-ai-agent-assignment-row').each(function () {
            const $row = $(this);
            const contentType = $row.find('select.build360-ai-assignment-type').val();
            const agentId = $row.find('select.build360-ai-assignment-agent').val();
            if (contentType && agentId) {
                agentAssignments.push({
                    type: contentType,
                    agent_id: agentId
                });
            }
        });
        // Add assignments to the payload under its specific key, matching the PHP handler
        settingsDataPayload['agent_assignments'] = agentAssignments;

        console.log('Build360 AI: Settings data (from button click) being sent:', settingsDataPayload);

        $.ajax({
            url: build360_ai_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'build360_ai_save_settings',
                nonce: build360_ai_vars.nonces.save_settings, // Corrected nonce path
                settings: settingsDataPayload // The entire collection of settings
            },
            success: function (response) {
                console.log('Build360 AI: Save settings response:', response);
                if (response.success) {
                    $('.wrap h1').after('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>');

                    if (response.data.needs_activation && build360_ai_vars.api_details) {
                        console.log('Build360 AI: Activation needed. Triggering website activation...');
                        triggerWebsiteActivation(); // This function is defined elsewhere in the file
                    }
                } else {
                    $('.wrap h1').after('<div class="notice notice-error is-dismissible"><p>' + (response.data.message || 'Error saving settings.') + '</p></div>');
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error('Build360 AI: Save settings AJAX error:', textStatus, errorThrown, jqXHR.responseText);
                $('.wrap h1').after('<div class="notice notice-error is-dismissible"><p>' + (build360_ai_vars.i18n.ajax_error || 'AJAX error saving settings.') + '</p></div>');
            },
            complete: function () {
                $button.text(originalButtonText).prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    }); // End of $('#build360_ai_save_settings').on('click', ...)

    function triggerWebsiteActivation() {
        const apiDetails = build360_ai_vars.api_details;
        const activationNonce = build360_ai_vars.activate_website_nonce;

        // Use the WordPress site's actual host for activation
        const domainForActivation = apiDetails.wordpress_site_host;

        if (!domainForActivation || !apiDetails.api_key || !apiDetails.ip_address) {
            console.error('Build360 AI: Missing API details for activation (WordPress site host, API key, or IP).');
            $('.wrap h1').after('<div class="notice notice-warning is-dismissible"><p>' + (build360_ai_vars.i18n.missing_api_details_for_activation || 'Cannot attempt activation: WordPress site host, API Key, or IP address is missing. Please ensure they are configured.') + '</p></div>');
            return;
        }

        if (!activationNonce) {
            console.error('Build360 AI: Missing nonce for website activation.');
            $('.wrap h1').after('<div class="notice notice-warning is-dismissible"><p>' + (build360_ai_vars.i18n.missing_activation_nonce || 'Cannot attempt activation: Security token (nonce) is missing.') + '</p></div>');
            return;
        }

        // Add a visual indicator for activation process if desired
        let $activationStatus = $('#build360-ai-activation-status');
        if (!$activationStatus.length) {
            $('#build360_ai_settings_result').after('<div id="build360-ai-activation-status" class="build360-ai-connection-result" style="margin-top:10px;"></div>');
            $activationStatus = $('#build360-ai-activation-status');
        }
        $activationStatus.html('<div class="notice notice-info inline"><p><span class="spinner is-active" style="vertical-align: middle;"></span> ' + (build360_ai_vars.i18n.activating_website || 'Attempting website activation...') + '</p></div>');

        $.ajax({
            url: build360_ai_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'build360_ai_activate_website',
                nonce: activationNonce,
                domain: domainForActivation, // Use the WordPress site's actual host
                api_key: apiDetails.api_key,
                ip_address: apiDetails.ip_address
            },
            success: function (activationResponse) {
                console.log('Build360 AI: Activation response:', activationResponse);
                if (activationResponse.success) {
                    $activationStatus.html('<div class="notice notice-success inline"><p>' + activationResponse.data.message + '</p></div>');
                    // Potentially update UI elements if needed, e.g., connection status indicator
                } else {
                    $activationStatus.html('<div class="notice notice-error inline"><p>' + (activationResponse.data.message || 'Website activation failed.') + '</p></div>');
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error('Build360 AI: Activation AJAX error:', textStatus, errorThrown);
                $activationStatus.html('<div class="notice notice-error inline"><p>' + (build360_ai_vars.i18n.ajax_error || 'AJAX error during activation.') + '</p></div>');
            }
        });
    }

    // Agent assignment row management
    function getNextAssignmentIndex() {
        let maxIndex = -1;
        $('#build360-ai-agent-assignments-tbody tr.build360-ai-agent-assignment-row').each(function () {
            const currentIndex = parseInt($(this).data('index'), 10);
            if (currentIndex > maxIndex) {
                maxIndex = currentIndex;
            }
        });
        return maxIndex + 1;
    }

    $('#build360-ai-add-assignment-row').on('click', function () {
        const newIndex = getNextAssignmentIndex();
        // Create a new row - this HTML should match what's in settings.php for a row
        // Needs access to content_type_options and agents options (which are PHP variables)
        // This will require passing these options via wp_localize_script or fetching them via AJAX if they are dynamic
        // For now, let's assume a simplified template or that these are available globally (not ideal)

        // Fallback: clone the first row if available, otherwise show error or have a template
        const $tbody = $('#build360-ai-agent-assignments-tbody');
        let $firstRow = $tbody.find('tr.build360-ai-agent-assignment-row:first');

        if (!$firstRow.length && $tbody.data('row-template')) {
            // If a template is stored in data attribute
            const newRowHtml = $tbody.data('row-template').replace(/__INDEX__/g, newIndex);
            $tbody.append(newRowHtml);
        } else if ($firstRow.length) {
            const $newRow = $firstRow.clone();
            $newRow.attr('data-index', newIndex);
            $newRow.find('select').each(function () {
                const name = $(this).attr('name').replace(/\[\d+\]/, '[' + newIndex + ']');
                $(this).attr('name', name).val(''); // Reset value
            });
            $newRow.find('input').each(function () { // If any inputs
                const name = $(this).attr('name').replace(/\[\d+\]/, '[' + newIndex + ']');
                $(this).attr('name', name).val('');
            });
            $tbody.append($newRow);
        } else {
            console.error("Build360 AI: Cannot add assignment row. No template or existing row to clone.");
            // Optionally, inform the user
            alert("Could not add a new assignment row. Please ensure the table structure is correct or refresh the page.");
        }
    });

    $('#build360-ai-agent-assignments-tbody').on('click', '.build360-ai-remove-assignment-row', function () {
        // Ensure at least one row remains if it's not a template row
        if ($('#build360-ai-agent-assignments-tbody tr.build360-ai-agent-assignment-row').length > 1) {
            $(this).closest('tr').remove();
        } else {
            // Optionally, clear the values of the last row instead of removing it
            $(this).closest('tr').find('select').val('');
            alert("At least one assignment row must remain. You can clear its values.");
        }
    });
});
