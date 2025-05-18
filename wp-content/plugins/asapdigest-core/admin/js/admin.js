/**
 * ASAP Digest Admin Scripts
 * 
 * @package ASAPDigest_Core
 * @created 03.31.25 | 03:34 PM PDT
 */

(function($) {
    'use strict';

    // Main initialization function
    function initASAPDigestAdmin() {
        initQuickActions();
        initSettingsForm();
        initStatsExport();
        initNotifications();
        initAISettings(); // Initialize AI settings
    }

    // AI Settings Handler
    function initAISettings() {
        // Run only if we're on the AI settings page
        if ($('.asap-ai-test-area').length === 0) return;
        
        // Handle AI connection test
        window.asapTestAIConnection = function() {
            var provider = document.getElementById('asap_ai_provider').value;
            var apiKey = document.getElementById('asap_ai_api_key').value;
            var resultSpan = document.getElementById('ai-test-result');
            
            if (!provider || !apiKey) {
                resultSpan.textContent = 'Provider and API key are required.';
                resultSpan.style.color = 'red';
                return;
            }
            
            resultSpan.textContent = asapDigestAdmin.i18n.testing || 'Testing...';
            
            // Get nonce value - try the inline nonce field first, then fall back to localized value
            var nonceValue = '';
            if (window.asapTestConnectionNonce) {
                nonceValue = window.asapTestConnectionNonce;
                console.log('Using inline nonce field');
            } else if (asapDigestAdmin && asapDigestAdmin.nonce) {
                nonceValue = asapDigestAdmin.nonce;
                console.log('Using localized nonce');
            } else {
                console.log('No nonce available!');
            }
            
            // Debug information
            console.log('Test connection request data:', {
                provider: provider,
                api_key_length: apiKey.length,
                nonce_value: nonceValue,
                ajax_url: ajaxurl || (asapDigestAdmin ? asapDigestAdmin.ajaxurl : '/wp-admin/admin-ajax.php')
            });
            
            // Following WordPress AJAX handler standardization protocol
            $.ajax({
                url: ajaxurl || (asapDigestAdmin ? asapDigestAdmin.ajaxurl : '/wp-admin/admin-ajax.php'),
                type: 'POST',
                data: {
                    action: 'asap_test_ai_connection',
                    provider: provider,
                    api_key: apiKey,
                    nonce: nonceValue
                },
                beforeSend: function(xhr, settings) {
                    console.log('Sending AJAX request:', settings);
                },
                success: function(response) {
                    console.log('AJAX response received:', response);
                    if (response.success) {
                        resultSpan.textContent = response.data.message || asapDigestAdmin.i18n.success;
                        resultSpan.style.color = 'green';
                    } else {
                        resultSpan.textContent = (response.data && response.data.message) ? 
                            (asapDigestAdmin.i18n.error || 'Error: ') + response.data.message : 
                            (asapDigestAdmin.i18n.error || 'Error');
                        resultSpan.style.color = 'red';
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    var errorMessage = 'AJAX error';
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.data && response.data.message) {
                            errorMessage = response.data.message;
                        }
                    } catch(e) {}
                    
                    resultSpan.textContent = (asapDigestAdmin.i18n.error || 'Error: ') + errorMessage;
                    resultSpan.style.color = 'red';
                },
                complete: function(xhr, status) {
                    console.log('AJAX request completed:', status);
                }
            });
        };
    }

    // Notifications Handler
    function initNotifications() {
        // Placeholder for notification initialization
        // This function was referenced but not defined, causing a JavaScript error
        console.log('Notifications system initialized');
    }

    // Quick Actions Handler
    function initQuickActions() {
        $('#send-test-digest').on('click', function(e) {
            e.preventDefault();
            const button = $(this);
            
            if (button.hasClass('asap-digest-loading')) return;
            
            button.addClass('asap-digest-loading');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'asap_send_test_digest',
                    nonce: asapDigestAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotification('Test digest sent successfully!', 'success');
                    } else {
                        showNotification(response.data.message || 'Error sending test digest', 'error');
                    }
                },
                error: function() {
                    showNotification('Server error while sending test digest', 'error');
                },
                complete: function() {
                    button.removeClass('asap-digest-loading');
                }
            });
        });

        $('#preview-next-digest').on('click', function(e) {
            e.preventDefault();
            const button = $(this);
            
            if (button.hasClass('asap-digest-loading')) return;
            
            button.addClass('asap-digest-loading');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'asap_preview_next_digest',
                    nonce: asapDigestAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Open preview in new window
                        window.open(response.data.preview_url, '_blank');
                    } else {
                        showNotification(response.data.message || 'Error generating preview', 'error');
                    }
                },
                error: function() {
                    showNotification('Server error while generating preview', 'error');
                },
                complete: function() {
                    button.removeClass('asap-digest-loading');
                }
            });
        });
    }

    // Settings Form Handler
    function initSettingsForm() {
        const form = $('#asap-digest-settings-form');
        
        form.on('submit', function(e) {
            e.preventDefault();
            const submitButton = form.find('[type="submit"]');
            
            if (submitButton.hasClass('asap-digest-loading')) return;
            
            submitButton.addClass('asap-digest-loading');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: form.serialize() + '&action=asap_save_settings',
                success: function(response) {
                    if (response.success) {
                        showNotification('Settings saved successfully!', 'success');
                    } else {
                        showNotification(response.data.message || 'Error saving settings', 'error');
                    }
                },
                error: function() {
                    showNotification('Server error while saving settings', 'error');
                },
                complete: function() {
                    submitButton.removeClass('asap-digest-loading');
                }
            });
        });
    }

    // Stats Export Handler
    function initStatsExport() {
        $('#export-stats-csv, #export-stats-json').on('click', function(e) {
            e.preventDefault();
            const button = $(this);
            const format = button.attr('id').includes('csv') ? 'csv' : 'json';
            
            if (button.hasClass('asap-digest-loading')) return;
            
            button.addClass('asap-digest-loading');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'asap_export_stats',
                    format: format,
                    nonce: asapDigestAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Create and trigger download
                        const blob = new Blob([response.data.content], {
                            type: format === 'csv' ? 'text/csv' : 'application/json'
                        });
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.style.display = 'none';
                        a.href = url;
                        a.download = `asap-digest-stats.${format}`;
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        a.remove();
                        
                        showNotification('Export completed successfully!', 'success');
                    } else {
                        showNotification(response.data.message || 'Error exporting stats', 'error');
                    }
                },
                error: function() {
                    showNotification('Server error while exporting stats', 'error');
                },
                complete: function() {
                    button.removeClass('asap-digest-loading');
                }
            });
        });
    }

    // Notification System
    function showNotification(message, type = 'success') {
        const noticeClass = `asap-digest-notice asap-digest-notice-${type}`;
        const notice = $(`<div class="${noticeClass}">${message}</div>`);
        
        // Remove existing notices
        $('.asap-digest-notice').remove();
        
        // Add new notice
        $('.wrap.asap-digest-admin').prepend(notice);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            notice.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }

    // Initialize ThickBox for Browse Recommended Models
    function initThickBox() {
        // Only proceed if we're on a page that needs ThickBox
        if (!$('#hf-browse-models').length) {
            return;
        }

        // Wait for ThickBox to be loaded if not present yet
        var maxWait = 3000; // 3 seconds
        var interval = 50;
        var waited = 0;
        
        function tryInit() {
            // Check if jQuery and ThickBox are available
            if (typeof tb_show === 'undefined') {
                if (waited < maxWait) {
                    waited += interval;
                    setTimeout(tryInit, interval);
                } else {
                    // Only log error if we actually need ThickBox on this page
                    if ($('#hf-browse-models').length) {
                        console.error('ThickBox not loaded! Please check if thickbox.js is properly enqueued.');
                    }
                }
                return;
            }

            // ThickBox is loaded, set up the browse models functionality
            $('#hf-browse-models').on('click', function(e) {
                e.preventDefault();
                var width = Math.min(1200, $(window).width() * 0.9);
                var height = $(window).height() * 0.9;
                var modalContent = '<div id="hf-models-browser" class="loading"><p>Loading recommended models...</p><div class="spinner is-active"></div></div>';
                var modalId = 'hf-models-browser-modal';
                
                // Remove any existing modal
                $('#' + modalId).remove();
                
                // Add new modal
                $('body').append('<div id="' + modalId + '" style="display:none;">' + modalContent + '</div>');
                
                // Show ThickBox
                tb_show('Recommended Hugging Face Models', '#TB_inline?width=' + width + '&height=' + height + '&inlineId=' + modalId);
                
                // Load models via AJAX
                $.ajax({
                    url: asap_admin_vars.rest_url + 'asap/v1/ai/models/recommended',
                    type: 'GET',
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', asap_admin_vars.rest_nonce);
                    },
                    success: function(response) {
                        if (!$('#TB_window').is(':visible')) {
                            $('#' + modalId).remove();
                            return;
                        }
                        
                        // Check if we have a valid response with models
                        if (response && response.models) {
                            var html = '<div class="hf-models-container">';
                            html += '<div class="hf-models-tabs">';
                            html += '<ul class="category-tabs">';
                            var isFirst = true;
                            
                            // Create tabs for each category
                            Object.keys(response.models).forEach(function(category) {
                                var categoryId = category.toLowerCase().replace(/[^a-z0-9]/g, '-');
                                html += '<li' + (isFirst ? ' class="tabs"' : '') + '><a href="#tab-' + categoryId + '">' + category + '</a></li>';
                                isFirst = false;
                            });
                            html += '</ul>';
                            
                            // Create content for each tab
                            isFirst = true;
                            Object.entries(response.models).forEach(function([category, models]) {
                                var categoryId = category.toLowerCase().replace(/[^a-z0-9]/g, '-');
                                html += '<div id="tab-' + categoryId + '" class="tabs-panel' + (isFirst ? '' : ' hidden') + '">';
                                html += '<table class="widefat fixed" cellspacing="0">';
                                html += '<thead><tr><th>Model ID</th><th>Description</th><th>Actions</th></tr></thead>';
                                html += '<tbody>';
                                
                                // Add each model in the category
                                Object.entries(models).forEach(function([modelId, description]) {
                                    html += '<tr>';
                                    html += '<td class="model-id">' + modelId + '</td>';
                                    html += '<td class="model-description">' + description + '</td>';
                                    html += '<td class="actions">';
                                    html += '<button type="button" class="button button-primary hf-select-model" data-model-id="' + modelId + '">Select</button>';
                                    html += '</td>';
                                    html += '</tr>';
                                });
                                
                                html += '</tbody></table>';
                                html += '</div>';
                                isFirst = false;
                            });
                            
                            html += '</div>'; // Close tabs container
                            html += '</div>'; // Close models container
                            
                            // Update the content and remove loading state
                            $('#hf-models-browser').removeClass('loading').html(html);
                            
                            // Set up tab navigation
                            $('.category-tabs a').on('click', function(e) {
                                e.preventDefault();
                                var tab = $(this).attr('href');
                                $('.category-tabs li').removeClass('tabs');
                                $(this).parent().addClass('tabs');
                                $('.tabs-panel').addClass('hidden');
                                $(tab).removeClass('hidden');
                            });
                            
                            // Set up model selection
                            $('.hf-select-model').on('click', function() {
                                var modelId = $(this).data('model-id');
                                var description = $(this).closest('tr').find('.model-description').text();
                                var category = $(this).closest('.tabs-panel').attr('id').replace('tab-', '');
                                
                                // Generate a sensible display name based on model ID and category
                                var displayName = generateModelDisplayName(modelId, category, description);
                                
                                // Set form values
                                $('#hf_model_id').val(modelId);
                                $('#hf_model_label').val(displayName);
                                
                                // Close ThickBox
                                tb_remove();
                                $('#' + modalId).remove();
                            });
                        } else {
                            $('#hf-models-browser').removeClass('loading').html('<p class="error">Error: Failed to load models. Invalid response format.</p>');
                        }
                    },
                    error: function(xhr, status, error) {
                        var errorMessage = 'Failed to load models. Please check your connection and try again.';
                        
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response && response.message) {
                                errorMessage = response.message;
                            } else if (response && response.data && response.data.message) {
                                errorMessage = response.data.message;
                            } else if (xhr.status === 404) {
                                errorMessage = 'Recommended models endpoint not found. Please verify API configuration.';
                            } else if (xhr.status === 401 || xhr.status === 403) {
                                errorMessage = 'Unauthorized. Please check your API key and permissions.';
                            } else if (status === 'timeout') {
                                errorMessage = 'Request timed out. The server took too long to respond.';
                            }
                        } catch (e) {
                            console.error('Error parsing error response:', e);
                        }
                        
                        $('#hf-models-browser').removeClass('loading').html('<p class="error">Error: ' + errorMessage + '</p>');
                        console.error('Error loading models:', {
                            status: status,
                            error: error,
                            response: xhr.responseText
                        });
                    },
                    timeout: 15000 // Reduce timeout to 15 seconds to prevent long waiting
                });
            });
        }
        
        // Start initialization process
        tryInit();
    }
    
    // Helper function to get current models
    function getCurrentModels() {
        var models = {};
        $('#hf-models-list tr[data-model-id]').each(function() {
            var modelId = $(this).data('model-id');
            var modelName = $(this).find('.model-name').text();
            models[modelId] = modelName;
        });
        return models;
    }
    
    // Helper function to update model verification status in database
    function updateModelVerificationStatus(modelId, isVerified) {
        // Use the global nonce from asapDigestAdmin
        var nonceValue = asapDigestAdmin.nonce;
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'asap_update_hf_model_verification',
                model_id: modelId,
                is_verified: isVerified ? 1 : 0,
                nonce: nonceValue
            },
            success: function(response) {
                if (response.success) {
                    console.log('Model verification status updated:', response.data);
                } else {
                    console.error('Error updating model verification status:', response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error updating model verification status:', xhr.responseText);
            }
        });
    }
    
    // Helper function to mark a model as failed
    function markModelAsFailed(modelId) {
        // Get model row
        var modelRow = $('#hf-models-list tr[data-model-id="' + modelId + '"]');
        
        // Update row class and status indicator
        modelRow.removeClass('model-verified model-unverified').addClass('model-failed');
        modelRow.find('.model-status').html('<span class="status-failed">✗ Failed</span>');
        
        // Update the verification status in the database
        updateModelVerificationStatus(modelId, false);
    }
    
    // Call ThickBox initialization when document is ready
    $(document).ready(function() {
        initThickBox();
    });
    
    // Handle Hugging Face model testing
    $('#hf-test-new-model').on('click', function() {
        var modelId = $('#hf_model_id').val();
        var apiKey = $('#asap_ai_huggingface_key').val();
        var resultIndicator = $('#hf-test-new-result');
        
        if (!modelId) {
            resultIndicator.html('<span class="error">⚠️ Please enter a model ID</span>');
            return;
        }
        
        if (!apiKey) {
            resultIndicator.html('<span class="error">⚠️ Please enter your Hugging Face API key first</span>');
            return;
        }
        
        // Show loading indicator with timer
        resultIndicator.html('<span class="test-loading"></span> Testing... <span class="test-timer">0</span>s');
        
        // Start timer
        let seconds = 0;
        const timer = setInterval(function() {
            seconds++;
            resultIndicator.find('.test-timer').text(seconds);
        }, 1000);
        
        // Use the global nonce from asapDigestAdmin
        var nonceValue = asapDigestAdmin.nonce;
        
        // Test the model
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'asap_test_ai_connection',
                provider: 'huggingface',
                api_key: apiKey,
                model: modelId,
                nonce: nonceValue
            },
            success: function(response) {
                clearInterval(timer);
                console.log('huggingface test response:', response);
                if (response.success) {
                    resultIndicator.html('<span class="success">✓ ' + response.data.message + '</span>');
                } else {
                    var errorMsg = response.data.message || 'Connection failed';
                    var errorDetails = '';
                    
                    // Enhance error message based on common error patterns
                    if (errorMsg.indexOf('Not Found') !== -1) {
                        errorDetails = '<div class="error-details">This model may not exist or is not available through the Inference API.<br>Try one of the recommended models instead.</div>';
                    } else if (errorMsg.indexOf('API key') !== -1) {
                        errorDetails = '<div class="error-details">Check that your API key is valid and has the necessary permissions.</div>';
                    } else if (errorMsg.indexOf('timed out') !== -1) {
                        errorDetails = '<div class="error-details">The server took too long to respond. The model may be too large or the API service is under heavy load.</div>';
                    }
                    
                    resultIndicator.html('<span class="error">✗ ' + errorMsg + '</span>' + errorDetails);
                }
            },
            error: function(xhr, status, error) {
                clearInterval(timer);
                console.log('huggingface test error:', {xhr, status, error});
                var errorMsg = 'Connection failed';
                
                try {
                    // Try to parse the response JSON for better error messages
                    var jsonResponse = JSON.parse(xhr.responseText);
                    if (jsonResponse && jsonResponse.data && jsonResponse.data.message) {
                        errorMsg = jsonResponse.data.message;
                    }
                } catch (e) {
                    // If JSON parsing fails, use the raw responseText if available
                    if (xhr.responseText && xhr.responseText.length < 100) {
                        errorMsg = xhr.responseText;
                    }
                }
                
                resultIndicator.html('<span class="error">✗ ' + errorMsg + '</span>');
            },
            timeout: 30000 // Increase timeout for large models
        });
    });
    
    // Handle add model form submission
    $('#hf-add-model-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var modelId = $('#hf_model_id').val().trim();
        var modelLabel = $('#hf_model_label').val().trim();
        var apiKey = $('#asap_ai_huggingface_key').val();
        var verifyBeforeAdd = $('#hf-verify-before-add').is(':checked');
        var submitButton = form.find('button[type="submit"]');
        var resultIndicator = $('#hf-test-new-result');
        
        // Basic validation
        if (!modelId || !modelLabel) {
            showAdminNotice('error', 'Please enter both Model ID and Display Name.');
            return;
        }
        
        if (verifyBeforeAdd) {
            if (!apiKey) {
                showAdminNotice('error', 'Please enter your Hugging Face API key to verify this model.');
            return;
        }
        
            // Show loading
            submitButton.prop('disabled', true);
            resultIndicator.html('<span class="test-loading"></span> Verifying model...');
            
            // Get nonce
            var nonceValue = asapDigestAdmin.nonce;
            
            // Test model first
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'asap_test_ai_connection',
                    provider: 'huggingface',
                    api_key: apiKey,
                    model: modelId,
                    nonce: nonceValue
                },
                success: function(response) {
                    if (response.success) {
                        // Model verified, now add it
                        resultIndicator.html('<span class="success">✓ Verified</span>');
                        
                        // Continue with adding model
                        addModelToDatabase(modelId, modelLabel, submitButton, resultIndicator, true);
                    } else {
                        // Model verification failed
                        var errorMsg = response.data.message || 'Verification failed';
                        resultIndicator.html('<span class="error">✗ ' + errorMsg + '</span>');
                        
                        if (confirm('Model verification failed: ' + errorMsg + '\n\nAdd this model anyway?')) {
                            addModelToDatabase(modelId, modelLabel, submitButton, resultIndicator, false);
                        } else {
                            submitButton.prop('disabled', false);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Model verification error:', xhr.responseText);
                    var errorMsg = 'Verification failed';
                    
                    try {
                        if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                            errorMsg = xhr.responseJSON.data.message;
                        } else {
                            var jsonResponse = JSON.parse(xhr.responseText);
                            if (jsonResponse && jsonResponse.data && jsonResponse.data.message) {
                                errorMsg = jsonResponse.data.message;
                            }
                        }
                    } catch (e) {
                        if (xhr.responseText && xhr.responseText.length < 100) {
                            errorMsg = xhr.responseText;
                        }
                    }
                    
                    resultIndicator.html('<span class="error">✗ ' + errorMsg + '</span>');
                    
                    if (confirm('Model verification failed: ' + errorMsg + '\n\nAdd this model anyway?')) {
                        addModelToDatabase(modelId, modelLabel, submitButton, resultIndicator, false);
                    } else {
                        submitButton.prop('disabled', false);
                    }
                }
            });
        } else {
            // Skip verification, add model directly
            addModelToDatabase(modelId, modelLabel, submitButton, resultIndicator, false);
        }
    });
    
    // Helper function to add model to database
    function addModelToDatabase(modelId, modelLabel, submitButton, resultIndicator, isVerified) {
        // Use the global nonce from asapDigestAdmin
        var nonceValue = asapDigestAdmin.nonce;
        
        // Send the request to add the model
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'asap_save_custom_hf_models',
                operation: 'add',
                model_id: modelId,
                model_label: modelLabel,
                is_verified: isVerified ? 1 : 0,
                nonce: nonceValue
            },
            success: function(response) {
                if (response.success) {
                    // Clear form
                    $('#hf_model_id, #hf_model_label').val('');
                    
                    // Clear indicator if there was one
                    resultIndicator.html('');
                    
                    // Get verification status classes
                    var modelClass = isVerified ? 'model-verified' : 'model-unverified';
                    var statusHtml = isVerified ? 
                        '<span class="status-verified">✓ Verified</span>' : 
                        '<span class="status-unverified">⚠ Unverified</span>';
                    
                    // Check if there's a "no items" row that needs to be removed
                    if ($('#hf-models-list .no-items').length) {
                        $('#hf-models-list').empty();
                    }
                    
                    // Add new row to the table
                    var newRow = $('<tr data-model-id="' + modelId + '" class="' + modelClass + '">' +
                                  '<td class="model-id">' + modelId + '</td>' +
                                  '<td class="model-name">' + modelLabel + '</td>' +
                        '<td class="model-status">' + statusHtml + '</td>' +
                                  '<td class="actions">' +
                                    '<button type="button" class="button button-small hf-test-model" data-model="' + modelId + '">Test</button> ' +
                                    '<button type="button" class="button button-small hf-edit-model" data-model="' + modelId + '" data-name="' + modelLabel + '">Edit</button> ' +
                                    '<button type="button" class="button button-small hf-delete-model" data-model="' + modelId + '">Delete</button>' +
                                    '<span class="test-result-indicator"></span>' +
                                  '</td>' +
                                '</tr>');
                    
                    $('#hf-models-list').append(newRow);
                    
                    // Add to the dropdown
                    $('#asap_ai_huggingface_model').append('<option value="' + modelId + '">' + modelLabel + '</option>');
                    
                    // Show success message
                    showAdminNotice('success', 'Model added successfully' + (isVerified ? ' and verified.' : '.'));
                    
                    // Update verification status in database if verified
                    if (isVerified) {
                        updateModelVerificationStatus(modelId, true);
                    }
                } else {
                    showAdminNotice('error', 'Error adding model: ' + (response.data.message || 'Unknown error'));
                }
                
                // Re-enable submit button
                submitButton.prop('disabled', false);
            },
            error: function(xhr, status, error) {
                console.error('Error adding model:', xhr.responseText);
                showAdminNotice('error', 'Error adding model. Please try again.');
                
                // Re-enable submit button
                submitButton.prop('disabled', false);
            }
        });
    }
    
    // Handle test model button click
    $(document).on('click', '.hf-test-model', function() {
        var button = $(this);
        var modelId = button.data('model');
        var apiKey = $('#asap_ai_huggingface_key').val();
        var resultIndicator = button.siblings('.test-result-indicator');
        
        if (!apiKey) {
            resultIndicator.html('<span class="error">⚠️ Please enter API key first</span>');
            return;
        }
        
        // Show loading indicator
        resultIndicator.html('<span class="test-loading"></span> Testing...');
        
        // Get nonce
        var nonceValue = asapDigestAdmin.nonce;
        
        // Send test request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'asap_test_ai_connection',
                provider: 'huggingface',
                api_key: apiKey,
                model: modelId,
                nonce: nonceValue
            },
            success: function(response) {
                if (response.success) {
                    resultIndicator.html('<span class="success">✓ Working</span>');
                    
                    // Update row class
                    button.closest('tr').removeClass('model-failed model-unverified').addClass('model-verified');
                    button.closest('tr').find('.model-status').html('<span class="status-verified">✓ Verified</span>');
                    
                    // Update verification status
                    updateModelVerificationStatus(modelId, true);
                    
                    // Clear result after delay
                    setTimeout(function() {
                        resultIndicator.html('');
                    }, 3000);
                } else {
                    var errorMsg = response.data.message || 'Connection failed';
                    resultIndicator.html('<span class="error">✗ ' + errorMsg + '</span>');
                    
                    // Mark model as failed
                    markModelAsFailed(modelId);
                }
            },
            error: function(xhr, status, error) {
                console.error('Test model error:', xhr.responseText);
                var errorMsg = 'Connection failed';
                
                try {
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMsg = xhr.responseJSON.data.message;
                    } else {
                        var jsonResponse = JSON.parse(xhr.responseText);
                        if (jsonResponse && jsonResponse.data && jsonResponse.data.message) {
                            errorMsg = jsonResponse.data.message;
                        }
                    }
                } catch (e) {
                    if (xhr.responseText && xhr.responseText.length < 100) {
                        errorMsg = xhr.responseText;
                    }
                }
                
                resultIndicator.html('<span class="error">✗ ' + errorMsg + '</span>');
                
                // Mark model as failed
                markModelAsFailed(modelId);
            }
        });
    });
    
    // Handle editing a model (open dialog)
    $(document).on('click', '.hf-edit-model', function() {
        var modelId = $(this).data('model');
        var modelName = $(this).data('name');
        
        // Set the form values
        $('#edit_original_model_id').val(modelId);
        $('#edit_model_id').val(modelId);
        $('#edit_model_label').val(modelName);
        
        // Properly destroy any existing dialog instance
        var dialogElement = $('#hf-edit-model-dialog');
        if (dialogElement.hasClass('ui-dialog-content')) {
            dialogElement.dialog('destroy');
        }
        
        // Show the dialog
        dialogElement.dialog({
            modal: true,
            width: 500,
            dialogClass: 'wp-dialog',
            title: 'Edit Model: ' + modelName,
            buttons: {
                'Update': function() {
                    var dialog = $(this);
                    var originalModelId = $('#edit_original_model_id').val();
                    var newModelId = $('#edit_model_id').val().trim();
                    var newModelLabel = $('#edit_model_label').val().trim();
                    var verifyAfterUpdate = $('#edit_verify_after_update').is(':checked');
                    
                    if (!newModelId || !newModelLabel) {
                        showAdminNotice('error', 'Both Model ID and Display Name are required.');
                        return;
                    }
                    
                    // Get current models
                    let models = getCurrentModels();
                    
                    // Check if new model ID already exists (unless it's the same as original)
                    if (newModelId !== originalModelId && models[newModelId]) {
                        showAdminNotice('error', 'A model with this ID already exists. Please use a different ID.');
                        return;
                    }
                    
                    // Use the global nonce from asapDigestAdmin
                    var nonceValue = asapDigestAdmin.nonce;
                    
                    // Function to update the model in the database
                    function updateModelInDatabase() {
                    // Save the model using operation-based format
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'asap_save_custom_hf_models',
                            operation: 'update',
                            original_model_id: originalModelId,
                            model_id: newModelId,
                            model_label: newModelLabel,
                            nonce: nonceValue
                        },
                        success: function(response) {
                            if (response.success) {
                                // Update the table row
                                var row = $('tr[data-model-id="' + originalModelId + '"]');
                                row.attr('data-model-id', newModelId);
                                row.find('.model-id').text(newModelId);
                                row.find('.model-name').text(newModelLabel);
                                row.find('.hf-test-model').attr('data-model', newModelId);
                                row.find('.hf-edit-model').attr('data-model', newModelId).attr('data-name', newModelLabel);
                                row.find('.hf-delete-model').attr('data-model', newModelId);
                                    
                                    // If model ID changed, remove verification status
                                    if (newModelId !== originalModelId) {
                                        row.removeClass('model-verified model-failed').addClass('model-unverified');
                                        row.find('.model-status').html('<span class="status-unverified">⚠ Unverified</span>');
                                        
                                        // Remove from verified models
                                        updateModelVerificationStatus(originalModelId, false);
                                    }
                                
                                // Update dropdown
                                var option = $('#asap_ai_huggingface_model option[value="' + originalModelId + '"]');
                                if (option.length) {
                                    option.val(newModelId).text(newModelLabel);
                                } else {
                                    $('#asap_ai_huggingface_model').append(new Option(newModelLabel, newModelId));
                                }
                                
                                // Close the dialog
                                dialog.dialog('close');
                                
                                // Show success notice
                                showAdminNotice('success', 'Model updated successfully!');
                            } else {
                                showAdminNotice('error', 'Error updating model: ' + (response.data ? response.data.message : 'Unknown error'));
                            }
                        },
                        error: function(xhr, status, error) {
                            showAdminNotice('error', 'An error occurred while updating the model: ' + error);
                            console.error('Error updating model:', {xhr, status, error});
                        }
                    });
                    }
                    
                    // If we need to verify after update and the model ID changed
                    if (verifyAfterUpdate && newModelId !== originalModelId) {
                        // Test the new model
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'asap_test_ai_connection',
                                provider: 'huggingface',
                                api_key: $('#asap_ai_huggingface_key').val(),
                                model: newModelId,
                                nonce: nonceValue
                            },
                            success: function(response) {
                                if (response.success) {
                                    // Model is verified, update it and mark as verified
                                    updateModelInDatabase();
                                    setTimeout(function() {
                                        updateModelVerificationStatus(newModelId, true);
                                    }, 500);
                                } else {
                                    // Model verification failed
                                    var errorMsg = response.data.message || 'Connection failed';
                                    
                                    // Ask if user wants to update anyway
                                    if (confirm('Model verification failed: ' + errorMsg + '. Update this model anyway?')) {
                                        updateModelInDatabase();
                                    }
                                }
                            },
                            error: function(xhr, status, error) {
                                // Handle error
                                var errorMsg = 'Connection failed';
                                
                                try {
                                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                                        errorMsg = xhr.responseJSON.data.message;
                                    } else {
                                        var jsonResponse = JSON.parse(xhr.responseText);
                                        if (jsonResponse && jsonResponse.data && jsonResponse.data.message) {
                                            errorMsg = jsonResponse.data.message;
                                        }
                                    }
                                } catch (e) {
                                    // Use raw text if available
                                    if (xhr.responseText && xhr.responseText.length < 100) {
                                        errorMsg = xhr.responseText;
                                    }
                                }
                                
                                // Ask if user wants to update anyway
                                if (confirm('Model verification failed: ' + errorMsg + '. Update this model anyway?')) {
                                    updateModelInDatabase();
                                }
                            },
                            timeout: 30000
                        });
                    } else {
                        // Just update the model without verification
                        updateModelInDatabase();
                    }
                },
                'Cancel': function() {
                    $(this).dialog('close');
                }
            },
            close: function() {
                // Clear form values on close
                $('#edit_original_model_id, #edit_model_id, #edit_model_label').val('');
                $('#edit_verify_after_update').prop('checked', true);
            }
        });
    });
    
    // Handle deleting a model
    $(document).on('click', '.hf-delete-model', function() {
        var modelId = $(this).data('model');
        var row = $(this).closest('tr');
        
        if (!confirm('Are you sure you want to delete this model?')) {
            return;
        }
        
        // Use the global nonce from asapDigestAdmin
        var nonceValue = asapDigestAdmin.nonce;
        
        // Delete the model using operation-based format
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'asap_save_custom_hf_models',
                operation: 'delete',
                model_id: modelId,
                nonce: nonceValue
            },
            success: function(response) {
                if (response.success) {
                    // Remove the row from the table
                    row.remove();
                    
                    // Remove from dropdown
                    $('#asap_ai_huggingface_model option[value="' + modelId + '"]').remove();
                    
                    // If there are no models left, add the "no items" row
                    if ($('#hf-models-list tr').length === 0) {
                        $('#hf-models-list').append('<tr class="no-items"><td colspan="4">No custom models added yet.</td></tr>');
                    }
                    
                    // Also remove from verified models list
                    updateModelVerificationStatus(modelId, false);
                    
                    // Show success notice
                    showAdminNotice('success', 'Model deleted successfully!');
                } else {
                    showAdminNotice('error', 'Error deleting model: ' + (response.data ? response.data.message : 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                showAdminNotice('error', 'An error occurred while deleting the model: ' + error);
                console.error('Error deleting model:', {xhr, status, error});
            }
        });
    });

    // Handle Verify All Models button
    $(document).on('click', '#hf-verify-all-models', function() {
        var button = $(this);
        var apiKey = $('#asap_ai_huggingface_key').val();
        var resultIndicator = $('#hf-bulk-action-result');
        
        if (!apiKey) {
            resultIndicator.html('<span class="error">⚠️ Please enter your Hugging Face API key first</span>');
            return;
        }
        
        // Get all models
        var models = [];
        $('#hf-models-list tr[data-model-id]').each(function() {
            models.push($(this).data('model-id'));
        });
        
        if (models.length === 0) {
            resultIndicator.html('<span class="error">No models to verify</span>');
            return;
        }
        
        // Confirm
        if (!confirm('This will test all ' + models.length + ' models. This might take some time. Continue?')) {
            return;
        }
        
        // Show loading indicator with model count
        resultIndicator.html('<span class="test-loading"></span> Verifying all models (0/' + models.length + ')...');
        
        // Disable button
        button.prop('disabled', true);
        
        // Use the global nonce from asapDigestAdmin
        var nonceValue = asapDigestAdmin.nonce;
        
        // Track progress and results
        var processed = 0;
        var verified = 0;
        var failed = 0;
        
        // Start testing models one by one
        testNextModel(0);
        
        function testNextModel(index) {
            if (index >= models.length) {
                // All models tested
                resultIndicator.html(
                    '<span class="success">✓ Verification complete: ' + 
                    verified + ' verified, ' + 
                    failed + ' failed</span>'
                );
                
                // Re-enable button
                button.prop('disabled', false);
                return;
            }
            
            // Update progress
            resultIndicator.html(
                '<span class="test-loading"></span> Verifying all models (' + 
                processed + '/' + models.length + ')...'
            );
            
            var modelId = models[index];
            var modelRow = $('#hf-models-list tr[data-model-id="' + modelId + '"]');
            var modelResultIndicator = modelRow.find('.test-result-indicator');
            
            // Show loading indicator for this model
            modelResultIndicator.html('<span class="test-loading"></span> Testing...');
            
            // Test the model
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'asap_test_ai_connection',
                    provider: 'huggingface',
                    api_key: apiKey,
                    model: modelId,
                    nonce: nonceValue
                },
                success: function(response) {
                    processed++;
                    
                    if (response.success) {
                        verified++;
                        modelResultIndicator.html('<span class="success">✓ Verified</span>');
                        updateModelVerificationStatus(modelId, true);
                        
                        // Update row class
                        modelRow.removeClass('model-failed model-unverified').addClass('model-verified');
                        modelRow.find('.model-status').html('<span class="status-verified">✓ Verified</span>');
                        
                        // Clear result after delay
                        setTimeout(function() {
                            modelResultIndicator.html('');
                        }, 3000);
                    } else {
                        failed++;
                        var errorMsg = response.data.message || 'Connection failed';
                        modelResultIndicator.html('<span class="error">✗ ' + errorMsg + '</span>');
                        markModelAsFailed(modelId);
                    }
                    
                    // Process next model
                    testNextModel(index + 1);
                },
                error: function(xhr, status, error) {
                    processed++;
                    failed++;
                    console.error('Test model error:', xhr.responseText);
                    
                    var errorMsg = 'Connection failed';
                    try {
                        if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                            errorMsg = xhr.responseJSON.data.message;
                        } else {
                            var jsonResponse = JSON.parse(xhr.responseText);
                            if (jsonResponse && jsonResponse.data && jsonResponse.data.message) {
                                errorMsg = jsonResponse.data.message;
                            }
                        }
                    } catch (e) {
                        if (xhr.responseText && xhr.responseText.length < 100) {
                            errorMsg = xhr.responseText;
                        }
                    }
                    
                    modelResultIndicator.html('<span class="error">✗ ' + errorMsg + '</span>');
                    markModelAsFailed(modelId);
                    
                    // Process next model
                    testNextModel(index + 1);
                }
            });
        }
    });
    
    // Handle Remove Failed Models button
    $(document).on('click', '#hf-remove-failed-models', function() {
        var button = $(this);
        var resultIndicator = $('#hf-bulk-action-result');
        
        // Get failed models from the UI
        var failedModels = [];
        $('#hf-models-list tr.model-failed').each(function() {
            failedModels.push($(this).data('model-id'));
        });
        
        if (failedModels.length === 0) {
            resultIndicator.html('<span class="error">No failed models to remove</span>');
            return;
        }
        
        // Confirm
        if (!confirm('This will remove ' + failedModels.length + ' failed models. Continue?')) {
            return;
        }
        
        // Disable button
        button.prop('disabled', true);
        
        // Show loading indicator
        resultIndicator.html('<span class="test-loading"></span> Removing failed models...');
        
        // Use the global nonce from asapDigestAdmin
        var nonceValue = asapDigestAdmin.nonce;
        
        // Send request to remove failed models
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'asap_remove_failed_models',
                nonce: nonceValue
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    resultIndicator.html('<span class="success">✓ ' + response.data.message + '</span>');
                    
                    // Remove failed models from UI
                    $('#hf-models-list tr.model-failed').remove();
                    
                    // Update the dropdown
                    for (var i = 0; i < failedModels.length; i++) {
                        $('#asap_ai_huggingface_model option[value="' + failedModels[i] + '"]').remove();
                    }
                    
                    // If no models left, show the "no items" row
                    if ($('#hf-models-list tr[data-model-id]').length === 0) {
                        $('#hf-models-list').html('<tr class="no-items"><td colspan="4">No custom models added yet. Add a model above or select from recommended models.</td></tr>');
                    }
                    
                    // Show success notice
                    showAdminNotice('success', response.data.message);
                } else {
                    resultIndicator.html('<span class="error">✗ Error removing models</span>');
                    console.error('Error removing models:', response.data);
                }
                
                // Re-enable button
                button.prop('disabled', false);
            },
            error: function(xhr, status, error) {
                resultIndicator.html('<span class="error">✗ Error removing models</span>');
                console.error('AJAX error removing models:', xhr.responseText);
                
                // Re-enable button
                button.prop('disabled', false);
            }
        });
    });

    // Helper function to show admin notice
    function showAdminNotice(type, message) {
        var noticeClass = 'notice-' + type;
        var notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
        
        // Remove any existing notices
        $('.notice').remove();
        
        // Add notice at the top of the content
        $('.wrap h1').after(notice);
        
        // Make notice dismissible
        if (typeof wp !== 'undefined' && wp.notices && wp.notices.removeDismissed) {
            wp.notices.removeDismissed();
        }
        
        // Auto-dismiss after 5 seconds
            setTimeout(function() {
            notice.fadeOut(500, function() {
                    notice.remove();
                });
        }, 5000);
    }

    // Helper function to generate a nice display name for models
    function generateModelDisplayName(modelId, category, description) {
        // Extract the model name from the ID (after the last slash if present)
        var modelName = modelId.split('/').pop();
        
        // Convert kebab-case to Title Case
        modelName = modelName.split('-').map(function(word) {
            return word.charAt(0).toUpperCase() + word.slice(1);
        }).join(' ');
        
        // Format category for display (capitalized)
        category = category.charAt(0).toUpperCase() + category.slice(1).replace(/-/g, ' ');
        
        // Create display format based on context
        var displayName = '';
        
        // If model starts with specific prefixes, use a different format
        if (modelId.startsWith('facebook/bart') || modelId.startsWith('google/flan')) {
            // For models like "facebook/bart-large-cnn" -> "BART - Large CNN (Summarization)"
            var provider = modelId.split('/')[0].charAt(0).toUpperCase() + modelId.split('/')[0].slice(1);
            var model = modelName.split(' ')[0].toUpperCase();
            var variant = modelName.split(' ').slice(1).join(' ');
            displayName = model + ' - ' + variant + ' (' + category + ')';
        } else if (modelId.includes('bert') || modelId.includes('roberta')) {
            // For BERT/RoBERTa models
            var type = '';
            if (modelId.includes('distilbert')) {
                type = 'DistilBERT';
            } else if (modelId.includes('roberta')) {
                type = 'RoBERTa';
            } else {
                type = 'BERT';
            }
            displayName = type + ' - ' + modelName.replace('Distilbert', '').replace('Bert', '').replace('Roberta', '') + ' (' + category + ')';
        } else {
            // Default format
            displayName = modelName + ' (' + category + ')';
        }
        
        return displayName;
    }

    // Initialize on document ready
    $(document).ready(initASAPDigestAdmin);

})(jQuery); 