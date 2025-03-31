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

    // Initialize on document ready
    $(document).ready(initASAPDigestAdmin);

})(jQuery); 