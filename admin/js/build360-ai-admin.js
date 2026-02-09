/**
 * Build360 AI Content Generator Admin Scripts
 */
(function ($) {
    'use strict';

    /**
     * Initialize all admin scripts
     */
    function initAdminScripts() {
        // Settings page
        if ($('#build360_ai_settings_form').length) {
            initSettingsPage();
        }

        // Products page
        if ($('.build360-ai-products').length) {
            initProductsPage();
        }

        // Dashboard page
        if ($('.build360-ai-dashboard').length) {
            initDashboardPage();
        }
    }

    /**
     * Initialize settings page
     */
    function initSettingsPage() {
        // Test connection is handled by build360-ai-settings.js

        // Save settings
        $('#build360_ai_save_settings').on('click', function () {
            const $button = $(this);
            const $spinner = $button.next('.spinner');
            const $resultDiv = $('#build360_ai_settings_result');
            const $form = $('#build360_ai_main_settings_form');

            $button.prop('disabled', true);
            $spinner.addClass('is-active');
            $resultDiv.removeClass('success error notice notice-success notice-error notice-warning').empty().hide();

            const formData = $form.serializeArray();
            const settingsData = {
                action: 'build360_ai_save_settings',
                nonce: build360_ai_vars.nonces.save_settings,
                settings: {}
            };
            let apiKeyToActivate = null;
            const agentAssignments = [];

            // Process form data into the settings object
            $.each(formData, function (i, field) {
                if (field.name.startsWith('build360_ai_agent_assignments[')) {
                    // This is handled by the agentAssignments array population below
                } else {
                    // For other settings, like 'build360_ai_api_key', 'build360_ai_domain', 'build360_ai_debug_mode'
                    // The PHP AJAX handler expects them with the 'build360_ai_' prefix.
                    settingsData.settings[field.name] = field.value;
                    if (field.name === 'build360_ai_api_key' && field.value) {
                        apiKeyToActivate = field.value;
                    }
                }
            });

            // Ensure boolean settings like debug_mode are sent correctly (0 or 1)
            // If the checkbox is not checked, it won't be in formData, so we set a default.
            if (!$('input[name="build360_ai_debug_mode"]').is(':checked')) {
                settingsData.settings['build360_ai_debug_mode'] = '0';
            }

            // Collect Agent Assignments
            $('#build360-ai-agent-assignments-tbody .build360-ai-agent-assignment-row').each(function () {
                const type = $(this).find('select[name^="build360_ai_agent_assignments"][name$="[type]"]').val();
                const agentId = $(this).find('select[name^="build360_ai_agent_assignments"][name$="[agent_id]"]').val();
                if (type && agentId) {
                    agentAssignments.push({ type: type, agent_id: agentId });
                }
            });
            settingsData.settings.agent_assignments = agentAssignments; // Add collected assignments to the settings payload

            // AJAX call to save settings
            $.ajax({
                url: build360_ai_vars.ajax_url,
                type: 'POST',
                data: settingsData,
                success: function (response) {
                    if (response.success) {
                        $resultDiv.removeClass('error').addClass('notice notice-success is-dismissible').html('<p>' + response.data.message + '</p>').show();

                        if (response.data.needs_activation && apiKeyToActivate && build360_ai_vars.site_domain && build360_ai_vars.site_ip_address) {
                            $resultDiv.append('<p>' + (build360_ai_vars.i18n.activating_website || 'Attempting to activate website...') + '</p>');

                            const activationData = {
                                action: 'build360_ai_activate_website',
                                nonce: build360_ai_vars.nonces.activate_website,
                                domain: build360_ai_vars.site_domain,
                                api_key: apiKeyToActivate,
                                ip_address: build360_ai_vars.site_ip_address
                            };

                            $.ajax({
                                url: build360_ai_vars.ajax_url,
                                type: 'POST',
                                data: activationData,
                                success: function (activationResponse) {
                                    if (activationResponse.success) {
                                        $resultDiv.append('<div class="notice notice-success is-dismissible"><p>' + activationResponse.data.message + '</p></div>');
                                    } else {
                                        let activationErrorMessage = activationResponse.data.message;
                                        if (activationResponse.data.data && activationResponse.data.data.message) {
                                            activationErrorMessage = activationResponse.data.data.message;
                                        } else if (activationResponse.data.debug_received_result && activationResponse.data.debug_received_result.message) {
                                            activationErrorMessage = activationResponse.data.debug_received_result.message;
                                        }
                                        $resultDiv.append('<div class="notice notice-error is-dismissible"><p>' + activationErrorMessage + '</p></div>');
                                    }
                                },
                                error: function () {
                                    $resultDiv.append('<div class="notice notice-error is-dismissible"><p>' + (build360_ai_vars.i18n.activation_ajax_error || 'Website activation failed due to a network error.') + '</p></div>');
                                },
                                complete: function () {
                                    $button.prop('disabled', false);
                                    $spinner.removeClass('is-active');
                                }
                            });
                        } else {
                            if (response.data.needs_activation && apiKeyToActivate && (!build360_ai_vars.site_domain || !build360_ai_vars.site_ip_address)) {
                                $resultDiv.append('<div class="notice notice-warning is-dismissible"><p>' + (build360_ai_vars.i18n.activation_missing_info || 'Could not attempt website activation: site domain or IP address not available to JavaScript.') + '</p></div>');
                            }
                            $button.prop('disabled', false);
                            $spinner.removeClass('is-active');
                        }
                    } else {
                        $resultDiv.removeClass('success').addClass('notice notice-error is-dismissible').html('<p>' + response.data.message + '</p>').show();
                        $button.prop('disabled', false);
                        $spinner.removeClass('is-active');
                    }
                },
                error: function () {
                    $resultDiv.removeClass('success').addClass('notice notice-error is-dismissible').html('<p>' + (build360_ai_vars.i18n.ajax_error || 'An unexpected error occurred while saving settings.') + '</p>').show();
                    $button.prop('disabled', false);
                    $spinner.removeClass('is-active');
                }
            });
        });

        // Toggle password visibility
        $(document).on('click', '.build360-ai-toggle-password', function () {
            const targetId = $(this).data('target');
            const $targetInput = $('#' + targetId);
            const type = $targetInput.attr('type') === 'password' ? 'text' : 'password';
            $targetInput.attr('type', type);
            $(this).find('.dashicons').toggleClass('dashicons-visibility dashicons-hidden');
        });

        // Toggle API details visibility
        $('#build360_ai_toggle_api_details').on('click', function () {
            const $detailsDiv = $('#build360_ai_api_details');
            $detailsDiv.slideToggle();
            if ($detailsDiv.is(':visible')) {
                $(this).html('<span class="dashicons dashicons-arrow-up-alt2"></span> ' + (build360_ai_vars.i18n.hide_api_details || 'Hide API Details'));
            } else {
                $(this).html('<span class="dashicons dashicons-info-outline"></span> ' + (build360_ai_vars.i18n.show_api_details || 'Show API Details'));
            }
        });

        // AI Agent Assignments - Add Row
        $('#build360-ai-add-assignment-row').on('click', function () {
            const newIndex = $('#build360-ai-agent-assignments-tbody .build360-ai-agent-assignment-row').length;
            const newRowHtml = getNewAssignmentRowHtml(newIndex);
            $('#build360-ai-agent-assignments-tbody').append(newRowHtml);
        });

        // AI Agent Assignments - Remove Row
        $(document).on('click', '.build360-ai-remove-assignment-row', function () {
            $(this).closest('.build360-ai-agent-assignment-row').remove();
        });

        // AI Agent Assignments - Sync Agents
        $(document).on('click', '#build360-ai-sync-agents', function () {
            const $button = $(this);
            const $spinner = $('#build360-ai-sync-agents-spinner');
            const $result = $('#build360-ai-sync-agents-result');

            $button.prop('disabled', true);
            $spinner.css('display', 'inline-block');
            $result.removeClass('success error').empty().text((build360_ai_vars.i18n.syncing_agents || 'Syncing agents...'));

            $.ajax({
                url: build360_ai_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'build360_ai_list_agents',
                    nonce: build360_ai_vars.nonces.list_agents
                },
                success: function (response) {
                    if (response.success) {
                        $result.removeClass('error').addClass('success').text(build360_ai_vars.i18n.agents_synced_successfully || 'Agents synced successfully!');

                        let fetchedAgentsData = [];
                        if (response.data && Array.isArray(response.data.data)) {
                            fetchedAgentsData = response.data.data;
                        } else if (response.data && Array.isArray(response.data)) {
                            fetchedAgentsData = response.data;
                        }

                        build360_ai_vars.agents = {};
                        fetchedAgentsData.forEach(function (agent) {
                            if (agent.id && agent.name) {
                                build360_ai_vars.agents[agent.id] = agent.name;
                            }
                        });

                        $('.build360-ai-assignment-agent').each(function () {
                            const $select = $(this);
                            const selectedValue = $select.val();
                            $select.empty();
                            $select.append($('<option>', { value: '' }).text(build360_ai_vars.i18n.select_agent || '-- Select AI Agent --'));

                            if (Object.keys(build360_ai_vars.agents).length > 0) {
                                $.each(build360_ai_vars.agents, function (agentId, agentName) {
                                    $select.append($('<option>', { value: agentId }).text(agentName));
                                });
                            } else {
                                $select.append($('<option>', { value: '', disabled: true }).text(build360_ai_vars.i18n.no_agents_configured || 'No agents configured.'));
                            }
                            $select.val(selectedValue);
                        });

                    } else {
                        let errorMessage = (response.data && response.data.message) ? response.data.message : (build360_ai_vars.i18n.agents_sync_failed || 'Failed to sync agents.');
                        $result.removeClass('success').addClass('error').text(errorMessage);
                    }
                },
                error: function () {
                    $result.removeClass('success').addClass('error').text(build360_ai_vars.i18n.ajax_error || 'An AJAX error occurred during agent sync.');
                },
                complete: function () {
                    $button.prop('disabled', false);
                    $spinner.hide();
                    setTimeout(function () {
                        if ($result.text() === (build360_ai_vars.i18n.syncing_agents || 'Syncing agents...')) {
                            $result.empty();
                        }
                    }, 3000);
                }
            });
        });
    }

    /**
     * Initialize products page
     */
    function initProductsPage() {
        // Show/hide product form
        $('.build360-ai-generate-button').on('click', function (e) {
            e.preventDefault();
            const productId = $(this).data('product-id');
            $('#build360_ai_product_form_' + productId).slideDown();
            $(this).hide();
        });

        // Cancel generate
        $('.build360-ai-cancel-button').on('click', function (e) {
            e.preventDefault();
            const $form = $(this).closest('.build360-ai-product-generate-form');
            const $button = $form.closest('li').find('.build360-ai-generate-button');
            $form.slideUp(function () {
                $button.show();
            });
        });

        // Generate content for single product
        $('.build360-ai-generate-content-button').on('click', function () {
            const $button = $(this);
            const $spinner = $button.next('.spinner');
            const $form = $button.closest('form');
            const $resultMessage = $form.find('.build360-ai-result-message');
            const productId = $button.data('product-id');

            // Get selected fields
            const fields = [];
            $form.find('input[name="build360_ai_fields[]"]:checked').each(function () {
                fields.push($(this).val());
            });

            if (fields.length === 0) {
                $resultMessage.removeClass('success').addClass('error').text(build360_ai_vars.i18n.no_fields_selected).slideDown();
                return;
            }

            const keywords = $form.find('input[name="build360_ai_keywords"]').val();

            $button.prop('disabled', true);
            $spinner.addClass('is-active');
            $resultMessage.removeClass('success error').empty().hide();

            $.ajax({
                url: build360_ai_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'build360_ai_generate_product_content',
                    nonce: $form.find('input[name="build360_ai_product_nonce_' + productId + '"]').val(),
                    product_id: productId,
                    fields: fields,
                    keywords: keywords
                },
                success: function (response) {
                    if (response.success) {
                        $resultMessage.addClass('success').text(response.data.message);

                        // Add generated badge if not already present
                        const $item = $form.closest('li');
                        if (!$item.hasClass('build360-ai-generated')) {
                            $item.addClass('build360-ai-generated');
                            if ($item.find('.build360-ai-generated-badge').length === 0) {
                                $item.find('.build360-ai-product-info').append('<div class="build360-ai-generated-badge"><span class="dashicons dashicons-yes"></span> ' + build360_ai_vars.i18n.ai_generated + '</div>');
                            }
                        }
                    } else {
                        $resultMessage.addClass('error').text(response.data.message);
                    }

                    $resultMessage.slideDown();
                },
                error: function () {
                    $resultMessage.addClass('error').text(build360_ai_vars.i18n.ajax_error).slideDown();
                },
                complete: function () {
                    $button.prop('disabled', false);
                    $spinner.removeClass('is-active');
                }
            });
        });

        // Bulk generate
        $('#build360_ai_bulk_generate').on('click', function () {
            const $button = $(this);
            const $spinner = $button.next('.spinner');
            const $form = $('#build360_ai_bulk_form');
            const $results = $('#build360_ai_bulk_results');
            const $progressBar = $results.find('.build360-ai-progress-bar-inner');
            const $progressText = $results.find('.build360-ai-progress-text');
            const $resultMessage = $results.find('.build360-ai-result-message');
            const $resultErrors = $results.find('.build360-ai-result-errors');

            // Get categories
            const categories = $('#build360_ai_product_categories').val() || [];

            // Get product limit
            const limit = $('#build360_ai_product_limit').val();

            // Get selected fields
            const fields = [];
            $form.find('input[name="build360_ai_fields[]"]:checked').each(function () {
                fields.push($(this).val());
            });

            if (fields.length === 0) {
                alert(build360_ai_vars.i18n.no_fields_selected);
                return;
            }

            const keywords = $('#build360_ai_keywords').val();
            const overwrite = $('#build360_ai_overwrite').is(':checked');

            $button.prop('disabled', true);
            $spinner.addClass('is-active');
            $progressBar.width('0%');
            $progressText.text(build360_ai_vars.i18n.loading_products);
            $resultMessage.empty();
            $resultErrors.empty();
            $results.slideDown();

            // First get the product IDs
            $.ajax({
                url: build360_ai_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'build360_ai_get_products',
                    nonce: build360_ai_vars.nonces.get_products,
                    categories: categories,
                    limit: limit,
                    overwrite: overwrite ? 1 : 0
                },
                success: function (response) {
                    if (response.success) {
                        const productIds = response.data.product_ids;
                        const totalProducts = productIds.length;

                        if (totalProducts === 0) {
                            $progressText.text(build360_ai_vars.i18n.no_products_found);
                            $button.prop('disabled', false);
                            $spinner.removeClass('is-active');
                            return;
                        }

                        $progressText.text(build360_ai_vars.i18n.processing.replace('%d', '0').replace('%t', totalProducts));

                        // Process products in batches
                        const batchSize = 5;
                        let currentBatch = 0;
                        let processedCount = 0;
                        let errors = [];

                        function processBatch() {
                            const batch = productIds.slice(currentBatch, currentBatch + batchSize);

                            if (batch.length === 0) {
                                // All batches processed
                                $progressBar.width('100%');
                                $progressText.text(build360_ai_vars.i18n.completed.replace('%d', processedCount).replace('%t', totalProducts));
                                $resultMessage.html(build360_ai_vars.i18n.generated_products.replace('%d', processedCount));

                                if (errors.length > 0) {
                                    $resultErrors.html('<p><strong>' + build360_ai_vars.i18n.errors + ':</strong></p><ul><li>' + errors.join('</li><li>') + '</li></ul>');
                                }

                                $button.prop('disabled', false);
                                $spinner.removeClass('is-active');
                                return;
                            }

                            $.ajax({
                                url: build360_ai_vars.ajax_url,
                                type: 'POST',
                                data: {
                                    action: 'build360_ai_bulk_generate_product_content',
                                    nonce: build360_ai_vars.nonces.bulk_generate,
                                    product_ids: batch,
                                    fields: fields,
                                    keywords: keywords
                                },
                                success: function (response) {
                                    if (response.success) {
                                        processedCount += response.data.processed;

                                        if (response.data.errors && response.data.errors.length > 0) {
                                            errors = errors.concat(response.data.errors);
                                        }

                                        const progress = Math.min(100, Math.round((processedCount / totalProducts) * 100));
                                        $progressBar.width(progress + '%');
                                        $progressText.text(build360_ai_vars.i18n.processing.replace('%d', processedCount).replace('%t', totalProducts));
                                    } else {
                                        errors.push(response.data.message);
                                    }

                                    currentBatch += batchSize;
                                    processBatch();
                                },
                                error: function () {
                                    errors.push(build360_ai_vars.i18n.ajax_error + ' (Batch ' + (currentBatch / batchSize + 1) + ')');
                                    currentBatch += batchSize;
                                    processBatch();
                                }
                            });
                        }

                        processBatch();
                    } else {
                        $progressText.text(build360_ai_vars.i18n.error_loading_products);
                        $resultMessage.addClass('error').text(response.data.message);
                        $button.prop('disabled', false);
                        $spinner.removeClass('is-active');
                    }
                },
                error: function () {
                    $progressText.text(build360_ai_vars.i18n.error_loading_products);
                    $resultMessage.addClass('error').text(build360_ai_vars.i18n.ajax_error);
                    $button.prop('disabled', false);
                    $spinner.removeClass('is-active');
                }
            });
        });

        // Handle bulk actions
        $('#cb-select-all-1, #cb-select-all-2').on('change', function () {
            var isChecked = $(this).prop('checked');
            var checkboxes = $('.build360-ai-products input[type="checkbox"][name="products[]"]');
            checkboxes.prop('checked', isChecked);
        });

        // Handle individual checkbox changes
        $('.build360-ai-products input[type="checkbox"][name="products[]"]').on('change', function () {
            var allCheckboxes = $('.build360-ai-products input[type="checkbox"][name="products[]"]');
            var checkedCheckboxes = allCheckboxes.filter(':checked');
            var headerCheckboxes = $('#cb-select-all-1, #cb-select-all-2');

            headerCheckboxes.prop('checked', allCheckboxes.length === checkedCheckboxes.length);
        });

        // Handle bulk generation form submission
        $('.build360-ai-products form').on('submit', function (e) {
            var action = $(this).find('select[name="action"]').val();
            var action2 = $(this).find('select[name="action2"]').val();
            var selectedProducts = $('input[name="products[]"]:checked').length;

            if ((action === '-1' && action2 === '-1') || selectedProducts === 0) {
                e.preventDefault();
                alert(build360_ai_vars.no_products_selected);
                return false;
            }

            if (!confirm(build360_ai_vars.confirm_bulk_generation)) {
                e.preventDefault();
                return false;
            }
        });

        // Handle single product generation
        $('.build360-ai-products .button[href*="action=generate"]').on('click', function (e) {
            if (!confirm(build360_ai_vars.confirm_single_generation)) {
                e.preventDefault();
                return false;
            }
        });

        // Handle settings form submission
        var settingsForm = $('form[action="options.php"]');
        if (settingsForm.length) {
            settingsForm.on('submit', function () {
                var apiKey = $('#build360_ai_api_key').val();
                var domain = $('#build360_ai_domain').val();

                if (!apiKey || !domain) {
                    alert(build360_ai_vars.required_fields_missing);
                    return false;
                }

                if (!isValidUrl(domain)) {
                    alert(build360_ai_vars.invalid_domain);
                    return false;
                }

                return true;
            });
        }

        // Validate URL format
        function isValidUrl(url) {
            try {
                new URL(url);
                return true;
            } catch (e) {
                return false;
            }
        }

        // Copy generated content to clipboard
        $('.build360-ai-copy-content').on('click', function (e) {
            e.preventDefault();
            var content = $(this).data('content');

            if (content) {
                var tempInput = $('<textarea>');
                $('body').append(tempInput);
                tempInput.val(content).select();
                document.execCommand('copy');
                tempInput.remove();

                alert(build360_ai_vars.content_copied);
            }
        });

        // Handle max length inputs
        $('input[type="number"][name^="build360_ai_max_length_"]').on('change', function () {
            var min = parseInt($(this).attr('min'));
            var max = parseInt($(this).attr('max'));
            var value = parseInt($(this).val());

            if (value < min) {
                $(this).val(min);
            } else if (value > max) {
                $(this).val(max);
            }
        });

        // Show/hide password
        var apiKeyField = $('#build360_ai_api_key');
        if (apiKeyField.length) {
            var toggleButton = $('<button>', {
                type: 'button',
                class: 'button',
                text: build360_ai_vars.show_api_key
            }).insertAfter(apiKeyField);

            toggleButton.on('click', function () {
                var type = apiKeyField.attr('type');
                apiKeyField.attr('type', type === 'password' ? 'text' : 'password');
                $(this).text(type === 'password' ? build360_ai_vars.hide_api_key : build360_ai_vars.show_api_key);
            });
        }

        // Function to get the HTML for a new assignment row
        function getNewAssignmentRowHtml(index) {
            let rowHtml = '<tr class="build360-ai-agent-assignment-row" data-index="' + index + '">';
            rowHtml += '<td><select name="build360_ai_agent_assignments[' + index + '][type]" class="build360-ai-assignment-type">';
            rowHtml += '<option value=""></option>'; // Blank default
            // Populate with build360_ai_vars.content_types (which you need to localize from PHP)
            if (build360_ai_vars.content_types) {
                $.each(build360_ai_vars.content_types, function (key, label) {
                    rowHtml += '<option value="' + key + '">' + label + '</option>';
                });
            }
            rowHtml += '</select></td>';
            rowHtml += '<td><select name="build360_ai_agent_assignments[' + index + '][agent_id]" class="build360-ai-assignment-agent">';
            rowHtml += '<option value=""></option>'; // Blank default
            // Populate with build360_ai_vars.agents (which you need to localize from PHP)
            if (build360_ai_vars.agents && Object.keys(build360_ai_vars.agents).length > 0) {
                $.each(build360_ai_vars.agents, function (id, agent) {
                    rowHtml += '<option value="' + id + '">' + agent.name + '</option>';
                });
            } else {
                rowHtml += '<option value="" disabled>' + (build360_ai_vars.i18n.no_agents_configured || 'No agents configured') + '</option>';
            }
            rowHtml += '</select></td>';
            rowHtml += '<td><button type="button" class="button button-small build360-ai-remove-assignment-row">Remove</button></td>';
            rowHtml += '</tr>';
            return rowHtml;
        }

    }

    /**
     * Initialize dashboard page
     */
    function initDashboardPage() {
        // Dashboard specific scripts
    }

    // Initialize admin scripts when DOM is ready
    $(document).ready(function () {
        initAdminScripts();
    });

})(jQuery); 