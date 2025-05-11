 * ASAP Digest Content Library
 * 
 * JavaScript for managing ingested content in the WordPress admin.
 * 
 * @package ASAPDigest_Core
 * @since 2.3.0
 */

(function($) {
    'use strict';

    // Content Library object
    const ContentLibrary = {
        // DOM elements
        elements: {
            contentTable: $('#content-library-table'),
            contentModal: $('#content-detail-modal'),
            confirmModal: $('#confirm-modal'),
            searchInput: $('#content-search'),
            typeFilter: $('#content-type-filter'),
            statusFilter: $('#content-status-filter'),
            qualityFilter: $('#content-quality-filter'),
            pagination: $('.pagination-links'),
            formMessages: $('#form-messages'),
            selectAll: $('#select-all'),
            bulkForm: $('#bulk-action-form')
        },

        // Current data
        data: {
            contents: [],
            currentPage: 1,
            totalPages: 1,
            perPage: 20,
            selectedIds: [],
            currentContentId: null,
            filters: {
                search: '',
                type: '',
                status: '',
                min_quality: 0
            }
        },

        /**
         * Initialize the module
         */
        init: function() {
            this.bindEvents();
            this.loadContents();
        },

        /**
         * Bind event listeners
         */
        bindEvents: function() {
            // Table actions
            this.elements.contentTable.on('click', '.view-content', this.handleViewContent.bind(this));
            this.elements.contentTable.on('click', '.edit-content', this.handleEditContent.bind(this));
            this.elements.contentTable.on('click', '.approve-content', this.handleApproveContent.bind(this));
            this.elements.contentTable.on('click', '.reject-content', this.handleRejectContent.bind(this));
            this.elements.contentTable.on('click', '.delete-content', this.handleDeleteContent.bind(this));
            
            // Bulk actions
            this.elements.selectAll.on('change', this.handleSelectAll.bind(this));
            this.elements.contentTable.on('change', '.content-check', this.handleContentCheck.bind(this));
            this.elements.bulkForm.on('submit', this.handleBulkAction.bind(this));
            
            // Search and filters
            this.elements.searchInput.on('input', this.debounce(this.handleSearch.bind(this), 500));
            this.elements.typeFilter.on('change', this.handleFilter.bind(this));
            this.elements.statusFilter.on('change', this.handleFilter.bind(this));
            this.elements.qualityFilter.on('change', this.handleFilter.bind(this));
            
            // Pagination
            this.elements.pagination.on('click', 'a', this.handlePagination.bind(this));
            
            // Modal actions
            $('.modal-close').on('click', function() {
                $(this).closest('.asap-modal').removeClass('open');
            });
            
            // Confirmation actions
            $('#confirm-delete-btn').on('click', this.confirmDeleteContent.bind(this));
            $('#confirm-bulk-delete-btn').on('click', this.confirmBulkAction.bind(this));
        },

        /**
         * Load contents from API
         */
        loadContents: function() {
            $('.content-library-wrap').addClass('loading');
            
            const data = {
                action: 'asap_search_content',
                nonce: asapDigestAdmin.nonce,
                search: this.data.filters.search,
                type: this.data.filters.type,
                status: this.data.filters.status,
                min_quality: this.data.filters.min_quality,
                page: this.data.currentPage,
                per_page: this.data.perPage
            };
            
            $.post(ajaxurl, data)
                .done(response => {
                    if (response.success) {
                        this.data.contents = response.data.items || [];
                        this.data.totalPages = response.data.total_pages || 1;
                        this.data.currentPage = response.data.current_page || 1;
                        
                        this.renderContentTable();
                        this.renderPagination();
                        
                        this.data.selectedIds = [];
                        this.elements.selectAll.prop('checked', false);
                        this.updateBulkActionState();
                    } else {
                        this.showFormMessage('error', response.data?.message || 'Error loading contents');
                    }
                })
                .fail(error => {
                    const message = error.responseJSON?.data?.message || 'An error occurred';
                    this.showFormMessage('error', message);
                })
                .always(() => {
                    $('.content-library-wrap').removeClass('loading');
                });
        },

        /**
         * Render content table
         */
        renderContentTable: function() {
            const tbody = this.elements.contentTable.find('tbody');
            tbody.empty();
            
            if (this.data.contents.length === 0) {
                tbody.html('<tr><td colspan="8" class="no-items">No content items found.</td></tr>');
                return;
            }
            
            this.data.contents.forEach(item => {
                const row = $('<tr></tr>');
                
                // Prepare status classes
                let statusClass = 'status-indicator';
                statusClass += item.status === 'approved' ? ' status-approved' : 
                              item.status === 'pending' ? ' status-pending' : 
                              item.status === 'rejected' ? ' status-rejected' : 
                              ' status-processing';
                
                // Prepare quality classes
                let qualityClass = 'status-indicator quality-';
                let qualityText = '';
                
                if (item.quality_score >= 90) {
                    qualityClass += 'excellent';
                    qualityText = 'Excellent';
                } else if (item.quality_score >= 70) {
                    qualityClass += 'good';
                    qualityText = 'Good';
                } else if (item.quality_score >= 50) {
                    qualityClass += 'average';
                    qualityText = 'Average';
                } else {
                    qualityClass += 'poor';
                    qualityText = 'Poor';
                }
                
                // Format status text
                const statusText = item.status.charAt(0).toUpperCase() + item.status.slice(1);
                
                // Add cells to row
                row.html(`
                    <td class="check-column">
                        <input type="checkbox" class="content-check" value="${item.id}" ${this.data.selectedIds.includes(item.id) ? 'checked' : ''}>
                    </td>
                    <td class="column-primary">
                        <strong>${item.title}</strong>
                        <div class="row-actions">
                            <span class="view"><a href="#" class="view-content" data-id="${item.id}">View</a> | </span>
                            <span class="edit"><a href="#" class="edit-content" data-id="${item.id}">Edit</a> | </span>
                            <span class="approve"><a href="#" class="approve-content" data-id="${item.id}">Approve</a> | </span>
                            <span class="reject"><a href="#" class="reject-content" data-id="${item.id}">Reject</a> | </span>
                            <span class="delete"><a href="#" class="delete-content" data-id="${item.id}">Delete</a></span>
                        </div>
                    </td>
                    <td>${item.type}</td>
                    <td><div class="${statusClass}">${statusText}</div></td>
                    <td><div class="${qualityClass}">${qualityText} (${item.quality_score})</div></td>
                    <td>${item.source_hostname || 'Unknown'}</td>
                    <td>${item.publish_date_formatted || 'Unknown'}</td>
                    <td>${item.created_at_formatted || 'Unknown'}</td>
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
         * Handle view content button click
         */
        handleViewContent: function(e) {
            e.preventDefault();
            
            const contentId = $(e.currentTarget).data('id');
            this.data.currentContentId = contentId;
            
            $('.content-library-wrap').addClass('loading');
            
            $.post(ajaxurl, {
                action: 'asap_get_content_details',
                nonce: asapDigestAdmin.nonce,
                content_id: contentId
            })
                .done(response => {
                    if (response.success) {
                        const content = response.data;
                        
                        // Populate modal with content details
                        $('#content-detail-modal .modal-title').text('Content Details');
                        $('#content-detail-modal .content-detail-title').text(content.title);
                        $('#content-detail-modal .content-detail-content').html(content.content);
                        
                        // Show modal
                        $('#content-detail-modal').addClass('open');
                    } else {
                        this.showFormMessage('error', response.data?.message || 'Error loading content details');
                    }
                })
                .fail(error => {
                    const message = error.responseJSON?.data?.message || 'An error occurred';
                    this.showFormMessage('error', message);
                })
                .always(() => {
                    $('.content-library-wrap').removeClass('loading');
                });
        },

        /**
         * Handle edit content button click
         */
        handleEditContent: function(e) {
            e.preventDefault();
            
            const contentId = $(e.currentTarget).data('id');
            
            // Redirect to edit page
            window.location.href = `?page=asap-content-library&action=edit&id=${contentId}`;
        },

        /**
         * Handle approve content button click
         */
        handleApproveContent: function(e) {
            e.preventDefault();
            
            const contentId = $(e.currentTarget).data('id');
            
            this.changeContentStatus(contentId, 'approved');
        },

        /**
         * Handle reject content button click
         */
        handleRejectContent: function(e) {
            e.preventDefault();
            
            const contentId = $(e.currentTarget).data('id');
            
            this.changeContentStatus(contentId, 'rejected');
        },

        /**
         * Change content status
         * 
         * @param {number} contentId Content ID
         * @param {string} status New status
         */
        changeContentStatus: function(contentId, status) {
            $('.content-library-wrap').addClass('loading');
            
            $.post(ajaxurl, {
                action: 'asap_bulk_action_content',
                bulk_action: 'change_status',
                new_status: status,
                content_ids: [contentId],
                nonce: asapDigestAdmin.nonce
            })
                .done(response => {
                    if (response.success) {
                        this.showFormMessage('success', response.data?.message || `Content ${status} successfully`);
                        this.loadContents();
                    } else {
                        this.showFormMessage('error', response.data?.message || 'Error updating status');
                    }
                })
                .fail(error => {
                    const message = error.responseJSON?.data?.message || 'An error occurred';
                    this.showFormMessage('error', message);
                })
                .always(() => {
                    $('.content-library-wrap').removeClass('loading');
                });
        },

        /**
         * Handle delete content button click
         */
        handleDeleteContent: function(e) {
            e.preventDefault();
            
            const contentId = $(e.currentTarget).data('id');
            this.data.currentContentId = contentId;
            
            // Show confirmation modal
            $('#confirm-modal').addClass('open');
        },

        /**
         * Confirm delete content
         */
        confirmDeleteContent: function(e) {
            e.preventDefault();
            
            const contentId = this.data.currentContentId;
            
            if (!contentId) {
                return;
            }
            
            // Close confirmation modal
            $('#confirm-modal').removeClass('open');
            
            // Delete content
            $('.content-library-wrap').addClass('loading');
            
            $.post(ajaxurl, {
                action: 'asap_bulk_action_content',
                bulk_action: 'delete',
                content_ids: [contentId],
                nonce: asapDigestAdmin.nonce
            })
                .done(response => {
                    if (response.success) {
                        this.showFormMessage('success', response.data?.message || 'Content deleted successfully');
                        this.loadContents();
                    } else {
                        this.showFormMessage('error', response.data?.message || 'Error deleting content');
                    }
                })
                .fail(error => {
                    const message = error.responseJSON?.data?.message || 'An error occurred';
                    this.showFormMessage('error', message);
                })
                .always(() => {
                    $('.content-library-wrap').removeClass('loading');
                });
        },

        /**
         * Handle select all checkbox change
         */
        handleSelectAll: function(e) {
            const isChecked = $(e.currentTarget).is(':checked');
            
            // Update checkboxes
            this.elements.contentTable.find('.content-check').prop('checked', isChecked);
            
            // Update selected IDs
            if (isChecked) {
                this.data.selectedIds = this.data.contents.map(item => item.id);
            } else {
                this.data.selectedIds = [];
            }
            
            // Update bulk action state
            this.updateBulkActionState();
        },

        /**
         * Handle individual content checkbox change
         */
        handleContentCheck: function(e) {
            const checkbox = $(e.currentTarget);
            const contentId = parseInt(checkbox.val(), 10);
            const isChecked = checkbox.is(':checked');
            
            // Update selected IDs
            if (isChecked && !this.data.selectedIds.includes(contentId)) {
                this.data.selectedIds.push(contentId);
            } else if (!isChecked && this.data.selectedIds.includes(contentId)) {
                this.data.selectedIds = this.data.selectedIds.filter(id => id !== contentId);
            }
            
            // Update select all checkbox
            const allChecked = this.data.selectedIds.length === this.data.contents.length;
            this.elements.selectAll.prop('checked', allChecked);
            
            // Update bulk action state
            this.updateBulkActionState();
        },

        /**
         * Update bulk action state
         */
        updateBulkActionState: function() {
            const hasSelection = this.data.selectedIds.length > 0;
            this.elements.bulkForm.find('select').prop('disabled', !hasSelection);
            this.elements.bulkForm.find('button[type="submit"]').prop('disabled', !hasSelection);
        },

        /**
         * Handle bulk action form submission
         */
        handleBulkAction: function(e) {
            e.preventDefault();
            
            const action = this.elements.bulkForm.find('select').val();
            
            if (this.data.selectedIds.length === 0) {
                this.showFormMessage('error', 'No items selected');
                return;
            }
            
            if (action === 'delete') {
                // Show confirmation modal
                $('#bulk-confirm-modal').addClass('open');
            } else {
                this.processBulkAction(action);
            }
        },

        /**
         * Confirm bulk action
         */
        confirmBulkAction: function(e) {
            e.preventDefault();
            
            // Close confirmation modal
            $('#bulk-confirm-modal').removeClass('open');
            
            // Get selected action
            const action = this.elements.bulkForm.find('select').val();
            
            this.processBulkAction(action);
        },

        /**
         * Process bulk action
         */
        processBulkAction: function(action) {
            $('.content-library-wrap').addClass('loading');
            
            // Prepare data based on action
            let data = {
                action: 'asap_bulk_action_content',
                nonce: asapDigestAdmin.nonce,
                content_ids: this.data.selectedIds
            };
            
            if (action === 'delete') {
                data.bulk_action = 'delete';
            } else if (action === 'approve' || action === 'reject' || action === 'pending') {
                data.bulk_action = 'change_status';
                data.new_status = action;
            }
            
            $.post(ajaxurl, data)
                .done(response => {
                    if (response.success) {
                        this.showFormMessage('success', response.data?.message || 'Bulk action completed successfully');
                        this.loadContents();
                    } else {
                        this.showFormMessage('error', response.data?.message || 'Error performing bulk action');
                    }
                })
                .fail(error => {
                    const message = error.responseJSON?.data?.message || 'An error occurred';
                    this.showFormMessage('error', message);
                })
                .always(() => {
                    $('.content-library-wrap').removeClass('loading');
                });
        },

        /**
         * Handle search input
         */
        handleSearch: function() {
            this.data.filters.search = this.elements.searchInput.val();
            this.data.currentPage = 1;
            this.loadContents();
        },

        /**
         * Handle filter change
         */
        handleFilter: function() {
            this.data.filters.type = this.elements.typeFilter.val();
            this.data.filters.status = this.elements.statusFilter.val();
            this.data.filters.min_quality = parseInt(this.elements.qualityFilter.val(), 10);
            this.data.currentPage = 1;
            this.loadContents();
        },

        /**
         * Handle pagination link click
         */
        handlePagination: function(e) {
            e.preventDefault();
            
            const page = parseInt($(e.currentTarget).data('page'), 10);
            
            if (page && page !== this.data.currentPage) {
                this.data.currentPage = page;
                this.loadContents();
            }
        },

        /**
         * Show form message
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
         * Debounce function
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
        ContentLibrary.init();
    });

})(jQuery); 
/**
 