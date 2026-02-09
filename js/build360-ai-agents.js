/**
 * Build360 AI Agents JavaScript
 */
jQuery(document).ready(function ($) {
    console.log('Build360 AI: build360-ai-agents.js loaded.');

    // Cache DOM elements
    const $modal = $('#agent-modal');
    const $form = $('#agent-form');
    const $agentsListContainer = $('.agents-list'); // Renamed for clarity, was $agentsList
    const $addAgentButton = $('.add-agent'); // This button is on the main agents page, not just in modal
    const $saveAgentButton = $modal.find('.save-agent'); // Ensure this is specific to modal's save button
    const $cancelButton = $modal.find('.cancel-modal');
    const $closeButton = $modal.find('.close-modal');
    const $searchInput = $('#agent-search'); // For search functionality
    const $categoryButtons = $('.category-btn'); // For category filtering
    const $temperatureInput = $modal.find('#agent-temperature'); // Specific to modal
    const $temperatureValue = $temperatureInput.next('.range-value'); // Specific to modal
    const $templateButtons = $modal.find('.load-template'); // Specific to modal

    // Prompt templates (if used in this page's modal)
    const promptTemplates = {
        content: `You are a skilled content writer...`,
        seo: `You are an SEO expert...`,
        support: `You are a customer support specialist...`
        // Make sure these match what your modal offers, or remove if not used here
    };

    // Initialize
    function init() {
        bindEvents();
        if (typeof build360_ai_vars !== 'undefined' &&
            typeof build360_ai_vars.nonces === 'object' &&
            typeof build360_ai_vars.nonces.list_agents === 'string' && build360_ai_vars.nonces.list_agents.length > 0 &&
            build360_ai_vars.ajax_url) {
            loadAgents(); // Load agents on page initialization
            fetchAndDisplayTokenBalance(); // Fetch token balance on init
        } else {
            console.warn('Build360 AI Agents: Essential AJAX parameters (like nonces or AJAX URL) not available. Cannot load agents or token balance.');
            $agentsListContainer.html('<div class="notice notice-error"><p>Could not initialize agent loading. AJAX parameters missing.</p></div>');
            $('.token-info .token-count').text('N/A');
        }
        Build360AIUtils.initNumberInputValidation();
        Build360AIUtils.initTooltips();
    }

    // Bind events
    function bindEvents() {
        // Add agent button (on main page)
        $addAgentButton.on('click', showAddModal);

        // Edit agent buttons (delegated to agents list container)
        $agentsListContainer.on('click', '.edit-agent', function (e) {
            e.preventDefault();
            const agentId = $(this).closest('.agent-card').data('id');
            showEditModal(agentId);
        });

        // Delete agent buttons (delegated to agents list container)
        $agentsListContainer.on('click', '.delete-agent', function (e) {
            e.preventDefault();
            const agentId = $(this).closest('.agent-card').data('id');
            confirmDelete(agentId);
        });

        // Modal specific buttons
        $closeButton.on('click', hideModal);
        $cancelButton.on('click', hideModal);
        $saveAgentButton.on('click', handleSaveAgent); // Or $form.on('submit', handleSaveAgent) if button is type=submit

        // Search input
        $searchInput.on('input', Build360AIUtils.debounce(handleSearch, 300));

        // Category filter
        $categoryButtons.on('click', handleCategoryFilter);

        // Temperature range input in modal
        $temperatureInput.on('input', function () {
            if ($temperatureValue.length) {
                $temperatureValue.text(this.value);
            }
        });

        // Template buttons in modal
        $templateButtons.on('click', function () {
            const templateKey = $(this).data('template');
            if (promptTemplates[templateKey]) {
                $modal.find('#agent-prompt').val(promptTemplates[templateKey]);
            }
        });

        // Close modal on overlay click (if overlay exists)
        // $('.modal-overlay').on('click', hideModal); 
        // Or close on window click outside modal (more robust)
        $(window).on('click', function (event) {
            if ($(event.target).is($modal)) {
                hideModal();
            }
        });
    }

    // Function to load agents via AJAX
    function loadAgents() {
        console.log('Build360 AI: Loading agents via AJAX...');
        $agentsListContainer.html('<div class="build360-loading-spinner"><span class="spinner is-active"></span> Loading agents...</div>');

        $.ajax({
            url: build360_ai_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'build360_ai_list_agents',
                nonce: build360_ai_vars.nonces.list_agents
            },
            success: function (response) {
                console.log('Build360 AI: List agents response:', response);
                $agentsListContainer.empty();
                if (response.success && response.data && Array.isArray(response.data.data)) {
                    if (response.data.data.length > 0) {
                        response.data.data.forEach(function (agent) {
                            const agentCard = `
                                <div class="agent-card" data-id="${agent.id}" data-category="${agent.category || 'general'}">
                                    <div class="agent-header">
                                        <h3 class="agent-name">${agent.name}</h3>
                                        <div class="agent-actions">
                                            <button type="button" class="button-icon edit-agent" title="Edit Agent"><span class="dashicons dashicons-edit"></span></button>
                                            <button type="button" class="button-icon delete-agent" title="Delete Agent"><span class="dashicons dashicons-trash"></span></button>
                                        </div>
                                    </div>
                                    <div class="agent-model">${agent.ai_model || 'N/A'}</div>
                                    <p class="agent-description">${agent.description || 'No description.'}</p>
                                    <div class="agent-footer">
                                        <span class="agent-status ${agent.is_active ? 'active' : 'inactive'}">
                                            ${agent.is_active ? ((build360_ai_vars.i18n && build360_ai_vars.i18n.active) || 'Active') : ((build360_ai_vars.i18n && build360_ai_vars.i18n.inactive) || 'Inactive')}
                                        </span>
                                        ${agent.usage_count !== undefined ? `<span class="agent-usage"><span class="dashicons dashicons-chart-bar"></span> ${agent.usage_count} uses</span>` : ''}
                                    </div>
                                </div>
                            `;
                            $agentsListContainer.append(agentCard);
                        });
                    } else {
                        // updateAgentsVisibility will handle showing the 'no agents' message
                    }
                } else {
                    $agentsListContainer.html('<div class="notice notice-error"><p>' + (response.data.message || (build360_ai_vars.i18n && build360_ai_vars.i18n.error_loading_agents) || 'Error loading agents.') + '</p></div>');
                }
                updateAgentsVisibility(); // Call after loading or on error
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error('Build360 AI: Error loading agents:', textStatus, errorThrown);
                $agentsListContainer.html('<div class="notice notice-error"><p>' + ((build360_ai_vars.i18n && build360_ai_vars.i18n.ajax_error) || 'AJAX error loading agents.') + '</p></div>');
                updateAgentsVisibility(); // Call even on error to show appropriate message
            }
        });
    }

    // Show add modal
    function showAddModal() {
        resetForm();
        $modal.find('.modal-header h2').text((build360_ai_vars.i18n && build360_ai_vars.i18n.add_agent) || 'Add New Agent');
        $form.removeData('agent-id');
        showModal();
    }

    // Show edit modal
    function showEditModal(agentId) {
        resetForm();
        $modal.find('.modal-header h2').text((build360_ai_vars.i18n && build360_ai_vars.i18n.edit_agent) || 'Edit Agent');
        $form.addClass('loading'); // Add loading indicator to form

        $.ajax({
            url: build360_ai_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'build360_ai_get_agent_details',
                nonce: build360_ai_vars.nonces.get_agent_details,
                agent_id: agentId
            },
            success: function (response) {
                // Expected response structure from WP AJAX (see Network tab):
                // response = {
                //   success: true, // From wp_send_json_success
                //   data: {       // This is the $result from PHP
                //     success: true, // From Laravel API response
                //     data: {       // Actual agent data from Laravel API
                //       id: ..., name: ..., ai_model: ..., etc.
                //     }
                //   }
                // }

                if (response.success &&
                    response.data &&
                    typeof response.data === 'object' &&
                    response.data.success &&
                    response.data.data &&
                    typeof response.data.data === 'object') {

                    const agent = response.data.data; // Get the actual agent object

                    $modal.find('#agent-name').val(agent.name);
                    $modal.find('#agent-model').val(agent.ai_model);
                    $modal.find('#agent-text-style').val(agent.text_style || '');
                    $modal.find('#agent-description').val(agent.description);
                    $modal.find('#agent-prompt').val(agent.system_prompt);
                    $modal.find('#agent-category').val(agent.category || 'general');
                    $temperatureInput.val(agent.temperature || 0.7).trigger('input');
                    $modal.find('#agent-max-tokens').val(agent.max_tokens || 2000);
                    $modal.find('#agent-is-active').prop('checked', agent.is_active);

                    // TODO: Handle population of complex fields like 'content_settings'
                    // Example: if (agent.content_settings && agent.content_settings.product_content) { ... }

                    $form.data('agent-id', agentId);
                    showModal();
                } else {
                    let errorMessage = (build360_ai_vars.i18n && build360_ai_vars.i18n.error_loading_agent_details) || 'Error loading agent details.';
                    if (response.data && response.data.message) {
                        errorMessage = response.data.message; // Message from Laravel API layer
                    } else if (response.data && typeof response.data === 'string') {
                        errorMessage = response.data; // Message from wp_send_json_error if it sent a simple string
                    } else if (response.message) {
                        errorMessage = response.message; // General message from response if others are not available
                    }
                    showErrorInModal(errorMessage);
                }
            },
            error: function () {
                showErrorInModal((build360_ai_vars.i18n && build360_ai_vars.i18n.ajax_error) || 'AJAX error loading agent details.');
            },
            complete: function () {
                $form.removeClass('loading');
            }
        });
    }

    // Handle save agent (Form submission via button click or form submit)
    function handleSaveAgent(e) {
        e.preventDefault();
        if (!validateForm()) {
            return;
        }

        $saveAgentButton.prop('disabled', true).html('<span class="spinner is-active"></span> ' + ((build360_ai_vars.i18n && build360_ai_vars.i18n.saving) || 'Saving...'));

        const agentId = $form.data('agent-id') || '';
        const agent_data = {
            name: $modal.find('#agent-name').val(),
            ai_model: $modal.find('#agent-model').val(),
            text_style: $modal.find('#agent-text-style').val(),
            description: $modal.find('#agent-description').val(),
            system_prompt: $modal.find('#agent-prompt').val(),
            category: $modal.find('#agent-category').val(),
            temperature: parseFloat($temperatureInput.val()),
            max_tokens: parseInt($modal.find('#agent-max-tokens').val(), 10),
            is_active: $modal.find('#agent-is-active').is(':checked'),
            // Add content_settings if your form supports it
        };

        // Add content_settings (complex object, ensure it's structured as expected by backend)
        // This is an example, adjust based on your actual form fields for content_settings
        agent_data.content_settings = {
            product_content: {
                fields: $modal.find('input[name="content_settings[product_content][fields][]"]:checked').map(function () { return $(this).val(); }).get(),
                // max_length_title: parseInt($modal.find('#cs-product-max-title').val(), 10),
                // max_length_desc: parseInt($modal.find('#cs-product-max-desc').val(), 10)
            },
            // ... other content types
        };

        $.ajax({
            url: build360_ai_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'build360_ai_save_agent',
                nonce: build360_ai_vars.nonces.save_agent,
                agent_id: agentId,
                agent_data: agent_data
            },
            success: function (response) {
                if (response.success) {
                    hideModal();
                    loadAgents(); // Reload the list to show changes
                    // Optionally, show a success notice on the main page
                } else {
                    showErrorInModal(response.data.message || (build360_ai_vars.i18n && build360_ai_vars.i18n.error_saving_agent) || 'Error saving agent.');
                }
            },
            error: function () {
                showErrorInModal((build360_ai_vars.i18n && build360_ai_vars.i18n.ajax_error) || 'AJAX error saving agent.');
            },
            complete: function () {
                $saveAgentButton.prop('disabled', false).html((build360_ai_vars.i18n && build360_ai_vars.i18n.save_agent) || 'Save Agent');
            }
        });
    }

    // Confirm delete
    function confirmDelete(agentId) {
        const confirmMessage = (build360_ai_vars.i18n && build360_ai_vars.i18n.confirm_delete_agent) || 'Are you sure you want to delete this agent?';
        if (confirm(confirmMessage)) {
            $.ajax({
                url: build360_ai_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'build360_ai_delete_agent',
                    nonce: build360_ai_vars.nonces.delete_agent,
                    agent_id: agentId
                },
                success: function (response) {
                    if (response.success) {
                        loadAgents(); // Reload the list
                    } else {
                        alert(response.data.message || (build360_ai_vars.i18n && build360_ai_vars.i18n.error_deleting_agent) || 'Error deleting agent.');
                    }
                },
                error: function () {
                    alert((build360_ai_vars.i18n && build360_ai_vars.i18n.ajax_error) || 'AJAX error deleting agent.');
                }
            });
        }
    }

    // Validate form in modal
    function validateForm() {
        let isValid = true;
        $modal.find('.form-error').remove(); // Clear previous errors within the modal

        const requiredFields = {
            '#agent-name': (build360_ai_vars.i18n && build360_ai_vars.i18n.name_required) || 'Name is required',
            '#agent-model': (build360_ai_vars.i18n && build360_ai_vars.i18n.model_required) || 'AI Model is required',
            '#agent-prompt': (build360_ai_vars.i18n && build360_ai_vars.i18n.prompt_required) || 'System Prompt is required'
        };

        for (const fieldId in requiredFields) {
            const $field = $modal.find(fieldId);
            if (!$field.val() || $field.val().trim() === '') {
                $field.after(`<span class="form-error">${requiredFields[fieldId]}</span>`);
                isValid = false;
            }
        }
        return isValid;
    }

    // Reset form
    function resetForm() {
        $form[0].reset();
        $form.removeData('agent-id');
        $temperatureInput.val(0.7).trigger('input'); // Reset temperature slider
        $modal.find('.form-error').remove();
    }

    // Show modal
    function showModal() {
        $modal.addClass('active').fadeIn(200); // Use jQuery fadeIn for smoother effect
        $('body').addClass('modal-open'); // Prevent background scrolling
    }

    // Hide modal
    function hideModal() {
        $modal.removeClass('active').fadeOut(200);
        $('body').removeClass('modal-open');
        resetForm(); // Reset form when modal is hidden
    }

    // Show error message inside the modal
    function showErrorInModal(message) {
        $form.find('.form-error').remove(); // Clear existing errors
        const $error = $('<div class="form-error notice notice-error is-dismissible"><p></p></div>').find('p').text(message).end();
        $form.prepend($error);
    }

    // Handle search
    function handleSearch() {
        const searchTerm = $searchInput.val().toLowerCase();
        $agentsListContainer.find('.agent-card').each(function () {
            const $agentCard = $(this);
            const name = $agentCard.find('.agent-name').text().toLowerCase();
            const description = $agentCard.find('.agent-description').text().toLowerCase();
            const model = $agentCard.find('.agent-model').text().toLowerCase();

            if (name.includes(searchTerm) || description.includes(searchTerm) || model.includes(searchTerm)) {
                $agentCard.show();
            } else {
                $agentCard.hide();
            }
        });
        updateAgentsVisibility(); // Update based on search results
    }

    // Handle category filter
    function handleCategoryFilter() {
        const $button = $(this);
        const category = $button.data('category');

        $categoryButtons.removeClass('active');
        $button.addClass('active');

        if (category === 'all') {
            $agentsListContainer.find('.agent-card').show();
        } else {
            $agentsListContainer.find('.agent-card').each(function () {
                const $agentCard = $(this);
                if ($agentCard.data('category') === category) {
                    $agentCard.show();
                } else {
                    $agentCard.hide();
                }
            });
        }
        updateAgentsVisibility(); // Update based on filter results
    }

    // Update agents visibility and show 'no agents' message if needed
    function updateAgentsVisibility() {
        const $visibleAgents = $agentsListContainer.find('.agent-card:visible');
        let $noAgentsMessage = $agentsListContainer.find('.no-agents-message');

        if ($visibleAgents.length === 0) {
            if ($noAgentsMessage.length === 0) {
                // Construct the 'no agents' message with a button
                // Ensure build360_ai_vars.i18n are available or provide defaults
                const noAgentsStr = (build360_ai_vars.i18n && build360_ai_vars.i18n.no_agents_found) || 'No agents found.';
                const tryFiltersStr = (build360_ai_vars.i18n && build360_ai_vars.i18n.try_different_filters) || 'Try adjusting your search or filters.';
                const addAgentStr = (build360_ai_vars.i18n && build360_ai_vars.i18n.add_new_agent) || 'Add New Agent';

                $agentsListContainer.append(`
                    <div class="no-agents-message" style="text-align: center; padding: 20px;">
                        <span class="dashicons dashicons-info-outline" style="font-size: 48px; color: #ccc;"></span>
                        <h3>${noAgentsStr}</h3>
                        <p>${tryFiltersStr}</p>
                        <button type="button" class="button button-primary add-agent">${addAgentStr}</button>
                    </div>
                `);
            } else {
                $noAgentsMessage.show();
            }
        } else {
            if ($noAgentsMessage.length > 0) {
                $noAgentsMessage.hide();
            }
        }
    }

    // Function to fetch and display token balance
    function fetchAndDisplayTokenBalance() {
        console.log('Build360 AI Token: fetchAndDisplayTokenBalance called.');

        // Check if the specific nonce for token balance exists
        if (!build360_ai_vars.nonces.get_token_balance) {
            console.warn('Build360 AI Token: Nonce for get_token_balance is missing. Cannot fetch token balance.');
            $('.token-info .token-count').text('N/A (Nonce missing)');
            $('.token-info .progress-bar').css('width', '0%');
            return;
        }
        console.log('Build360 AI Token: Nonce found, proceeding with AJAX call.');

        $.ajax({
            url: build360_ai_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'build360_ai_get_token_balance',
                nonce: build360_ai_vars.nonces.get_token_balance
            },
            success: function (response) {
                console.log('Build360 AI Token: AJAX success. Response:', response);
                if (response.success && response.data &&
                    typeof response.data.remaining !== 'undefined' &&
                    typeof response.data.used !== 'undefined' &&
                    typeof response.data.total !== 'undefined') {

                    const remainingTokens = parseInt(response.data.remaining, 10);
                    const usedTokens = parseInt(response.data.used, 10);
                    const totalTokens = parseInt(response.data.total, 10);
                    console.log('Build360 AI Token: Parsed values - Remaining:', remainingTokens, 'Used:', usedTokens, 'Total:', totalTokens);

                    const formattedBalance = remainingTokens.toLocaleString();
                    $('.token-info .token-count').text(formattedBalance);

                    let progressPercent = 0;
                    if (totalTokens > 0) {
                        progressPercent = (usedTokens / totalTokens) * 100;
                    }
                    $('.token-info .progress-bar').css('width', progressPercent + '%');
                    console.log('Build360 AI Token: UI updated. Progress Percent:', progressPercent);

                } else {
                    let errorMsg = 'Invalid response structure or token data not found.';
                    if (response.data && response.data.message) {
                        errorMsg = response.data.message;
                    } else if (response.message) {
                        errorMsg = response.message;
                    }
                    console.error('Build360 AI Token: Error processing token balance response:', errorMsg, 'Full Response:', response);
                    $('.token-info .token-count').text('Error (Processing)');
                    $('.token-info .progress-bar').css('width', '0%');
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error('Build360 AI Token: AJAX error fetching token balance. Status:', textStatus, 'Error:', errorThrown, 'jqXHR:', jqXHR);
                $('.token-info .token-count').text('Error (AJAX)');
                $('.token-info .progress-bar').css('width', '0%');
            }
        });
    }

    // Initialize
    init();
});
