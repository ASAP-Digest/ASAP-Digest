/**
 * ASAP Digest Content Source Management
 * 
 * JavaScript for managing content sources in the WordPress admin.
 * 
 * @package ASAPDigest_Core
 * @since 2.3.0
 */

(function($) {
    'use strict';

    // Source management object
    const SourceManager = {
        // DOM elements
        elements: {
            sourceList: $('#source-list-table'),
            sourceModal: $('#source-modal'),
            sourceForm: $('#source-form'),
            deleteConfirmModal: $('#delete-confirm-modal'),
            searchInput: $('#source-search'),
            typeFilter: $('#source-type-filter'),
            statusFilter: $('#source-status-filter'),
            pagination: $('.pagination-links'),
            formMessages: $('#form-messages')
        },

        // Current data
        data: {
            sources: [],
            currentPage: 1,
            totalPages: 1,
            perPage: 15,
            currentSourceId: null,
            filters: {
                search: '',
                type: '',
                status: ''
            }
        },

        /**
         * Initialize the module
         */
        init: function() {
            this.bindEvents();
            this.loadSources();
        },

        /**
         * Bind event listeners
         */
        bindEvents: function() {
            // Add new source button
            $('#add-source-btn').on('click', this.openAddSourceModal.bind(this));
            
            // Form submission
            this.elements.sourceForm.on('submit', this.handleFormSubmit.bind(this));
            
            // Table actions
            this.elements.sourceList.on('click', '.edit-source', this.handleEditSource.bind(this));
            this.elements.sourceList.on('click', '.delete-source', this.handleDeleteSource.bind(this));
            this.elements.sourceList.on('click', '.run-source', this.handleRunSource.bind(this));
            
            // Delete confirmation
            $('#confirm-delete-btn').on('click', this.confirmDeleteSource.bind(this));
            
            // Search and filters
            this.elements.searchInput.on('input', this.debounce(this.handleSearch.bind(this), 500));
            this.elements.typeFilter.on('change', this.handleFilter.bind(this));
            this.elements.statusFilter.on('change', this.handleFilter.bind(this));
            
            // Pagination
            this.elements.pagination.on('click', 'a', this.handlePagination.bind(this));
            
            // Dynamic source type fields
            $('#source-type').on('change', this.updateSourceTypeFields.bind(this));
        },

        /**
         * Load sources from API
         */
        loadSources: function() {
            // Show loading state
            this.showLoading();
            
            // Prepare request data
            const data = {
                action: 'asap_digest_get_content_sources',
                nonce: asapDigestAdmin.sources_nonce,
                search: this.data.filters.search,
                type: this.data.filters.type,
                status: this.data.filters.status,
                offset: (this.data.currentPage - 1) * this.data.perPage,
                limit: this.data.perPage
            };
            
            // Make AJAX request
            $.get(ajaxurl, data)
                .done(this.handleSourcesLoaded.bind(this))
                .fail(this.handleAjaxError.bind(this))
                .always(this.hideLoading.bind(this));
        },

        /**
         * Handle sources loaded from API
         * 
         * @param {Object} response API response
         */
        handleSourcesLoaded: function(response) {
            if (response.success) {
                // Update data
                this.data.sources = response.data.sources || [];
                this.data.totalPages = Math.ceil(response.data.total / this.data.perPage);
                
                // Render table and pagination
                this.renderSourceTable();
                this.renderPagination();
            } else {
                this.showFormMessage('error', response.data.message || 'Error loading sources');
            }
        },

        /**
         * Render sources table
         */
        renderSourceTable: function() {
            const tbody = this.elements.sourceList.find('tbody');
            tbody.empty();
            
            if (this.data.sources.length === 0) {
                tbody.html('<tr><td colspan="7" class="no-items">No sources found.</td></tr>');
                return;
            }
            
            // Add each source to the table
            this.data.sources.forEach(source => {
                const row = $('<tr></tr>');
                
                // Format dates
                const lastFetch = source.last_fetch ? new Date(source.last_fetch).toLocaleString() : 'Never';
                const createdAt = new Date(source.created_at).toLocaleString();
                
                // Health indicator
                let healthClass = 'status-indicator';
                let healthIcon = '';
                
                if (source.health === 'good') {
                    healthClass += ' status-good';
                    healthIcon = '<span class="dashicons dashicons-yes-alt"></span>';
                } else if (source.health === 'warning') {
                    healthClass += ' status-warning';
                    healthIcon = '<span class="dashicons dashicons-warning"></span>';
                } else if (source.health === 'error') {
                    healthClass += ' status-error';
                    healthIcon = '<span class="dashicons dashicons-dismiss"></span>';
                } else {
                    healthIcon = '<span class="dashicons dashicons-minus"></span>';
                }
                
                // Status indicator
                let statusClass = 'status-indicator';
                let statusText = source.status.charAt(0).toUpperCase() + source.status.slice(1);
                
                if (source.status === 'active') {
                    statusClass += ' status-active';
                } else if (source.status === 'paused') {
                    statusClass += ' status-paused';
                } else if (source.status === 'inactive') {
                    statusClass += ' status-inactive';
                }
                
                // Add cells to row
                row.html(`
                    <td>${source.id}</td>
                    <td class="column-primary">
                        <strong>${source.name}</strong>
                        <div class="row-actions">
                            <span class="edit"><a href="#" class="edit-source" data-id="${source.id}">Edit</a> | </span>
                            <span class="run"><a href="#" class="run-source" data-id="${source.id}">Run Now</a> | </span>
                            <span class="delete"><a href="#" class="delete-source" data-id="${source.id}">Delete</a></span>
                        </div>
                    </td>
                    <td>${source.type}</td>
                    <td><div class="${statusClass}">${statusText}</div></td>
                    <td><div class="${healthClass}">${healthIcon}</div></td>
                    <td>${source.items_count}</td>
                    <td>${lastFetch}</td>
                `);
                
                tbody.append(row);
            });
        },

        /**
         * Render pagination links
         */
        renderPagination: function() {
            const pagination = this.elements.pagination;
            pagination.empty();
            
            if (this.data.totalPages <= 1) {
                return;
            }
            
            // Previous button
            if (this.data.currentPage > 1) {
                pagination.append(`<a href="#" class="prev-page" data-page="${this.data.currentPage - 1}"><span class="screen-reader-text">Previous page</span><span aria-hidden="true">‹</span></a>`);
            } else {
                pagination.append('<span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>');
            }
            
            // Current page indicator
            pagination.append(`<span class="paging-input">${this.data.currentPage} of <span class="total-pages">${this.data.totalPages}</span></span>`);
            
            // Next button
            if (this.data.currentPage < this.data.totalPages) {
                pagination.append(`<a href="#" class="next-page" data-page="${this.data.currentPage + 1}"><span class="screen-reader-text">Next page</span><span aria-hidden="true">›</span></a>`);
            } else {
                pagination.append('<span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>');
            }
        },

        /**
         * Open add source modal
         * 
         * @param {Event} e Click event
         */
        openAddSourceModal: function(e) {
            if (e) e.preventDefault();
            
            // Reset form
            this.elements.sourceForm[0].reset();
            this.elements.sourceForm.find('input[name="source_id"]').val('');
            this.data.currentSourceId = null;
            
            // Update form title
            this.elements.sourceModal.find('.modal-title').text('Add New Content Source');
            
            // Reset configuration fields
            this.updateSourceTypeFields();
            
            // Show modal
            this.elements.sourceModal.addClass('open');
        },

        /**
         * Handle edit source button click
         * 
         * @param {Event} e Click event
         */
        handleEditSource: function(e) {
            e.preventDefault();
            
            const sourceId = $(e.currentTarget).data('id');
            this.data.currentSourceId = sourceId;
            
            // Show loading state
            this.showLoading();
            
            // Get source details
            $.get(ajaxurl, {
                action: 'asap_digest_get_content_source',
                nonce: asapDigestAdmin.sources_nonce,
                id: sourceId
            })
                .done(this.handleSourceLoaded.bind(this))
                .fail(this.handleAjaxError.bind(this))
                .always(this.hideLoading.bind(this));
        },

        /**
         * Handle source loaded from API
         * 
         * @param {Object} response API response
         */
        handleSourceLoaded: function(response) {
            if (response.success) {
                const source = response.data;
                
                // Update form title
                this.elements.sourceModal.find('.modal-title').text('Edit Content Source');
                
                // Fill form fields
                this.elements.sourceForm.find('input[name="source_id"]').val(source.id);
                this.elements.sourceForm.find('input[name="name"]').val(source.name);
                this.elements.sourceForm.find('select[name="type"]').val(source.type);
                this.elements.sourceForm.find('input[name="url"]').val(source.url);
                this.elements.sourceForm.find('select[name="frequency"]').val(source.frequency);
                this.elements.sourceForm.find('select[name="status"]').val(source.status);
                
                // Update configuration fields for the source type
                this.updateSourceTypeFields();
                
                // Fill configuration fields if available
                if (source.configuration) {
                    const config = source.configuration;
                    for (const key in config) {
                        const field = this.elements.sourceForm.find(`[name="configuration[${key}]"]`);
                        if (field.length) {
                            if (field.is(':checkbox')) {
                                field.prop('checked', config[key]);
                            } else {
                                field.val(config[key]);
                            }
                        }
                    }
                }
                
                // Show modal
                this.elements.sourceModal.addClass('open');
            } else {
                this.showFormMessage('error', response.data.message || 'Error loading source');
            }
        },

        /**
         * Handle source form submission
         * 
         * @param {Event} e Submit event
         */
        handleFormSubmit: function(e) {
            e.preventDefault();
            
            // Show loading state
            this.showSavingState();
            
            // Prepare form data
            const formData = this.getFormData();
            const sourceId = this.data.currentSourceId;
            
            // Determine action (add or update)
            const action = sourceId ? 'asap_digest_update_content_source' : 'asap_digest_add_content_source';
            
            // Add source ID if updating
            if (sourceId) {
                formData.id = sourceId;
            }
            
            // Make API request
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: JSON.stringify(formData),
                contentType: 'application/json',
                beforeSend: xhr => {
                    xhr.setRequestHeader('X-WP-Nonce', asapDigestAdmin.sources_nonce);
                },
                headers: {
                    'X-WP-Nonce': asapDigestAdmin.sources_nonce
                }
            })
                .done(response => this.handleFormSubmitResponse(response, sourceId))
                .fail(this.handleAjaxError.bind(this))
                .always(this.hideSavingState.bind(this));
        },

        /**
         * Get form data for submission
         * 
         * @return {Object} Form data
         */
        getFormData: function() {
            const formData = {};
            const form = this.elements.sourceForm;
            
            // Basic fields
            formData.name = form.find('input[name="name"]').val();
            formData.type = form.find('select[name="type"]').val();
            formData.url = form.find('input[name="url"]').val();
            formData.frequency = form.find('select[name="frequency"]').val();
            formData.status = form.find('select[name="status"]').val();
            
            // Configuration fields
            formData.configuration = {};
            form.find('[name^="configuration["]').each(function() {
                const field = $(this);
                const key = field.attr('name').match(/configuration\[(.*)\]/)[1];
                
                if (field.is(':checkbox')) {
                    formData.configuration[key] = field.is(':checked');
                } else {
                    formData.configuration[key] = field.val();
                }
            });
            
            return formData;
        },

        /**
         * Handle form submission response
         * 
         * @param {Object} response API response
         * @param {number} sourceId Source ID (if updating)
         */
        handleFormSubmitResponse: function(response, sourceId) {
            if (response.success) {
                // Close modal
                this.elements.sourceModal.removeClass('open');
                
                // Show success message
                this.showFormMessage('success', response.data.message || 'Source saved successfully');
                
                // Reload sources
                this.loadSources();
            } else {
                this.showFormMessage('error', response.data.message || 'Error saving source');
            }
        },

        /**
         * Handle delete source button click
         * 
         * @param {Event} e Click event
         */
        handleDeleteSource: function(e) {
            e.preventDefault();
            
            const sourceId = $(e.currentTarget).data('id');
            this.data.currentSourceId = sourceId;
            
            // Get source name
            const source = this.data.sources.find(s => s.id === sourceId);
            const sourceName = source ? source.name : `Source #${sourceId}`;
            
            // Update confirmation message
            this.elements.deleteConfirmModal.find('.confirm-text').text(`Are you sure you want to delete "${sourceName}"? This action cannot be undone.`);
            
            // Show confirmation modal
            this.elements.deleteConfirmModal.addClass('open');
        },

        /**
         * Confirm delete source
         * 
         * @param {Event} e Click event
         */
        confirmDeleteSource: function(e) {
            e.preventDefault();
            
            const sourceId = this.data.currentSourceId;
            
            if (!sourceId) {
                return;
            }
            
            // Show loading state
            this.showLoading();
            
            // Close confirmation modal
            this.elements.deleteConfirmModal.removeClass('open');
            
            // Make API request
            $.post(ajaxurl, {
                action: 'asap_digest_delete_content_source',
                nonce: asapDigestAdmin.sources_nonce,
                id: sourceId
            })
                .done(this.handleDeleteResponse.bind(this))
                .fail(this.handleAjaxError.bind(this))
                .always(this.hideLoading.bind(this));
        },

        /**
         * Handle delete response
         * 
         * @param {Object} response API response
         */
        handleDeleteResponse: function(response) {
            if (response.success) {
                // Show success message
                this.showFormMessage('success', response.data.message || 'Source deleted successfully');
                
                // Reload sources
                this.loadSources();
            } else {
                this.showFormMessage('error', response.data.message || 'Error deleting source');
            }
        },

        /**
         * Handle run source button click
         * 
         * @param {Event} e Click event
         */
        handleRunSource: function(e) {
            e.preventDefault();
            
            const sourceId = $(e.currentTarget).data('id');
            
            // Show loading state
            $(e.currentTarget).addClass('loading').text('Running...');
            
            // Make API request
            $.post(ajaxurl, {
                action: 'asap_digest_trigger_content_crawler',
                nonce: asapDigestAdmin.sources_nonce,
                id: sourceId
            })
                .done(response => this.handleRunResponse(response, e.currentTarget))
                .fail(error => this.handleRunError(error, e.currentTarget))
                .always(() => {
                    // Reset button
                    setTimeout(() => {
                        $(e.currentTarget).removeClass('loading success error').text('Run Now');
                    }, 3000);
                });
        },

        /**
         * Handle run source response
         * 
         * @param {Object} response API response
         * @param {Element} button Button element
         */
        handleRunResponse: function(response, button) {
            if (response.success) {
                // Update button
                $(button).removeClass('loading').addClass('success').text('Success!');
                
                // Show success message
                this.showFormMessage('success', response.data.message || 'Source ran successfully');
                
                // Reload sources after delay
                setTimeout(() => {
                    this.loadSources();
                }, 3000);
            } else {
                this.handleRunError(response, button);
            }
        },

        /**
         * Handle run source error
         * 
         * @param {Object} error Error response
         * @param {Element} button Button element
         */
        handleRunError: function(error, button) {
            // Update button
            $(button).removeClass('loading').addClass('error').text('Failed!');
            
            // Show error message
            const message = error.responseJSON?.data?.message || 'Error running source';
            this.showFormMessage('error', message);
        },

        /**
         * Handle search input
         */
        handleSearch: function() {
            this.data.filters.search = this.elements.searchInput.val();
            this.data.currentPage = 1;
            this.loadSources();
        },

        /**
         * Handle filter change
         */
        handleFilter: function() {
            this.data.filters.type = this.elements.typeFilter.val();
            this.data.filters.status = this.elements.statusFilter.val();
            this.data.currentPage = 1;
            this.loadSources();
        },

        /**
         * Handle pagination link click
         * 
         * @param {Event} e Click event
         */
        handlePagination: function(e) {
            e.preventDefault();
            
            const page = parseInt($(e.currentTarget).data('page'), 10);
            
            if (page && page !== this.data.currentPage) {
                this.data.currentPage = page;
                this.loadSources();
            }
        },

        /**
         * Update source type fields
         */
        updateSourceTypeFields: function() {
            const sourceType = this.elements.sourceForm.find('select[name="type"]').val();
            const configFields = this.elements.sourceForm.find('.config-fields');
            
            // Hide all config sections
            configFields.find('.config-section').hide();
            
            // Show section for selected type
            configFields.find(`.config-section[data-type="${sourceType}"]`).show();
        },

        /**
         * Show form message
         * 
         * @param {string} type Message type (success, error)
         * @param {string} message Message text
         */
        showFormMessage: function(type, message) {
            const messageElement = this.elements.formMessages;
            
            messageElement.removeClass('success error').addClass(type).text(message).show();
            
            // Hide after delay
            setTimeout(() => {
                messageElement.fadeOut();
            }, 5000);
        },

        /**
         * Handle AJAX error
         * 
         * @param {Object} error Error response
         */
        handleAjaxError: function(error) {
            const message = error.responseJSON?.data?.message || 'An error occurred';
            this.showFormMessage('error', message);
        },

        /**
         * Show loading state
         */
        showLoading: function() {
            $('.source-management-wrap').addClass('loading');
        },

        /**
         * Hide loading state
         */
        hideLoading: function() {
            $('.source-management-wrap').removeClass('loading');
        },

        /**
         * Show saving state
         */
        showSavingState: function() {
            this.elements.sourceForm.find('button[type="submit"]').addClass('loading').text('Saving...');
        },

        /**
         * Hide saving state
         */
        hideSavingState: function() {
            this.elements.sourceForm.find('button[type="submit"]').removeClass('loading').text('Save Source');
        },

        /**
         * Debounce function to limit how often a function can run
         * 
         * @param {Function} func Function to debounce
         * @param {number} wait Wait time in milliseconds
         * @return {Function} Debounced function
         */
        debounce: function(func, wait) {
            let timeout;
            
            return function() {
                const context = this;
                const args = arguments;
                
                clearTimeout(timeout);
                
                timeout = setTimeout(() => {
                    func.apply(context, args);
                }, wait);
            };
        }
    };

    // Initialize on DOM ready
    $(document).ready(function() {
        SourceManager.init();
    });

})(jQuery); 