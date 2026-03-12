/**
 * Build360 AI - Bulk Page Generation Progress Tracker
 *
 * Mirrors build360-ai-bulk-terms.js but for pages.
 * Handles:
 * - Field selection modal
 * - Progress bar UI, polling, cancel
 * - Review modal with pagination, accept/skip/accept-all
 */
(function($) {
    'use strict';

    var BulkPageTracker = {
        jobId: '',
        pollInterval: null,
        pollDelay: 3000,
        vars: {},
        selectedPageIds: [],
        reviewPage: 1,

        init: function() {
            this.vars = window.build360_ai_bulk_page_vars || {};
            if (!this.vars.ajax_url) return;

            this.bindFormIntercept();
            this.bindModalEvents();

            // Check URL param first (just redirected from bulk action)
            var urlParams = new URLSearchParams(window.location.search);
            var urlJobId = urlParams.get('build360_ai_bulk_page_job');

            if (urlJobId) {
                this.jobId = urlJobId;
                this.injectProgressBar();
                this.startPolling();
            } else if (this.vars.active_job_id) {
                this.jobId = this.vars.active_job_id;
                this.checkExistingJob();
            }
        },

        /**
         * Intercept bulk form submit on page list page
         */
        bindFormIntercept: function() {
            var self = this;

            // WordPress page list page: form #posts-filter
            $('#posts-filter').on('submit', function(e) {
                var $form = $(this);
                var action = $form.find('select[name="action"]').val();
                var action2 = $form.find('select[name="action2"]').val();
                var selectedAction = (action && action !== '-1') ? action : action2;

                if (selectedAction === 'build360_ai_generate_pages') {
                    e.preventDefault();

                    var checked = $form.find('input[name="post[]"]:checked');
                    if (checked.length === 0) {
                        alert(self.vars.strings.no_pages_selected);
                        return;
                    }

                    self.selectedPageIds = [];
                    checked.each(function() {
                        self.selectedPageIds.push($(this).val());
                    });

                    self.showFieldSelectionModal();
                }
            });
        },

        /**
         * Bind events for modals (field selection + review)
         */
        bindModalEvents: function() {
            var self = this;

            // Field selection modal
            $(document).on('click', '.build360-page-field-select-start', function() {
                self.startBulkGeneration();
            });
            $(document).on('click', '.build360-page-field-select-cancel, #build360-ai-page-field-select-modal .build360-bulk-modal-close, #build360-ai-page-field-select-modal .build360-bulk-modal-overlay', function() {
                self.hideFieldSelectionModal();
            });

            // Review modal
            $(document).on('click', '.build360-page-review-close, #build360-ai-bulk-page-review-modal .build360-bulk-modal-close, #build360-ai-bulk-page-review-modal .build360-bulk-modal-overlay', function() {
                self.hideReviewModal();
            });
            $(document).on('click', '.build360-page-review-accept-btn', function() {
                var pageId = $(this).data('page-id');
                self.acceptPage(pageId, $(this));
            });
            $(document).on('click', '.build360-page-review-skip-btn', function() {
                var pageId = $(this).data('page-id');
                self.skipPage(pageId, $(this));
            });
            $(document).on('click', '.build360-page-review-accept-all', function() {
                if (confirm(self.vars.strings.accept_all_confirm)) {
                    self.acceptAll();
                }
            });
            $(document).on('click', '.build360-page-review-page-btn', function() {
                var page = $(this).data('page');
                if (page) {
                    self.reviewPage = page;
                    self.loadReviewPage();
                }
            });
        },

        // ---- Field Selection Modal ----

        showFieldSelectionModal: function() {
            var $modal = $('#build360-ai-page-field-select-modal');
            $modal.find('.build360-field-select-count').text(
                this.selectedPageIds.length + ' ' + this.vars.strings.pages_selected
            );
            $modal.css({display: 'flex', alignItems: 'center', justifyContent: 'center'});
        },

        hideFieldSelectionModal: function() {
            $('#build360-ai-page-field-select-modal').css('display', 'none');
        },

        startBulkGeneration: function() {
            var self = this;
            var $modal = $('#build360-ai-page-field-select-modal');

            var fields = [];
            $modal.find('input[name="bulk_page_fields[]"]:checked').each(function() {
                fields.push($(this).val());
            });

            if (fields.length === 0) {
                alert(this.vars.strings.no_fields_selected);
                return;
            }

            var $startBtn = $modal.find('.build360-page-field-select-start');
            $startBtn.prop('disabled', true).text(this.vars.strings.processing);

            $.post(this.vars.ajax_url, {
                action: 'build360_ai_start_bulk_pages',
                nonce: this.vars.nonces.start_bulk_pages,
                page_ids: this.selectedPageIds,
                fields: fields
            }, function(response) {
                $startBtn.prop('disabled', false).text(self.vars.strings.start_generation);
                self.hideFieldSelectionModal();

                if (response.success && response.data && response.data.job_id) {
                    self.jobId = response.data.job_id;
                    self.injectProgressBar();
                    self.startPolling();
                } else {
                    var msg = (response.data && response.data.message) ? response.data.message : 'Error starting bulk generation.';
                    alert(msg);
                }
            }).fail(function() {
                $startBtn.prop('disabled', false).text(self.vars.strings.start_generation);
                alert('Network error. Please try again.');
            });
        },

        // ---- Progress Bar ----

        checkExistingJob: function() {
            var self = this;
            $.post(this.vars.ajax_url, {
                action: 'build360_ai_bulk_page_progress',
                nonce: this.vars.nonces.bulk_page_progress,
                job_id: this.jobId
            }, function(response) {
                if (response.success && response.data) {
                    var status = response.data.status;
                    if (status === 'processing') {
                        self.injectProgressBar();
                        self.startPolling();
                    } else if (status === 'completed' || status === 'cancelled') {
                        self.injectProgressBar();
                        self.updateUI(response.data);
                    }
                }
            });
        },

        injectProgressBar: function() {
            if ($('#build360-ai-bulk-page-progress').length) return;

            var s = this.vars.strings;
            var html = '<div id="build360-ai-bulk-page-progress" class="build360-bulk-progress-wrap">' +
                '<div class="build360-bulk-header">' +
                    '<span class="dashicons dashicons-update build360-bulk-spin"></span>' +
                    '<strong>' + s.progress_title + '</strong>' +
                    '<span class="build360-bulk-status-badge">' + s.processing + '</span>' +
                '</div>' +
                '<div class="build360-bulk-bar-container">' +
                    '<div class="build360-bulk-bar" style="width: 0%"></div>' +
                '</div>' +
                '<div class="build360-bulk-stats">' +
                    '<span class="build360-bulk-counter">0 ' + s.of + ' 0 ' + s.pages_processed + '</span>' +
                    '<span class="build360-bulk-succeeded"></span>' +
                '</div>' +
                '<p class="build360-bulk-batch-info">' +
                    '<span class="dashicons dashicons-info-outline"></span> ' + s.batch_info +
                '</p>' +
                '<div class="build360-bulk-products"></div>' +
                '<div class="build360-bulk-actions">' +
                    '<button type="button" class="button build360-bulk-page-cancel-btn">' + s.cancel + '</button>' +
                    '<button type="button" class="button button-primary build360-bulk-page-results-btn" style="display:none;">' + s.view_results + '</button>' +
                    '<button type="button" class="button build360-bulk-page-dismiss-btn" style="display:none;">' + (s.dismiss || 'Dismiss') + '</button>' +
                '</div>' +
            '</div>';

            var $wrap = $('.wrap');
            if ($wrap.length) {
                var $heading = $wrap.find('h1.wp-heading-inline, h1:first');
                if ($heading.length) {
                    $(html).insertAfter($heading);
                } else {
                    $wrap.prepend(html);
                }
            }

            // Bind events
            var self = this;
            $(document).on('click', '.build360-bulk-page-cancel-btn', function() {
                if (confirm(s.cancel_confirm)) {
                    self.cancelJob();
                }
            });
            $(document).on('click', '.build360-bulk-page-results-btn', function() {
                self.showReviewModal();
            });
            $(document).on('click', '.build360-bulk-page-dismiss-btn', function() {
                self.dismissJob();
            });
        },

        startPolling: function() {
            var self = this;
            this.pollInterval = setInterval(function() {
                self.fetchProgress();
            }, this.pollDelay);
            this.fetchProgress();
        },

        stopPolling: function() {
            if (this.pollInterval) {
                clearInterval(this.pollInterval);
                this.pollInterval = null;
            }
        },

        fetchProgress: function() {
            var self = this;
            $.post(this.vars.ajax_url, {
                action: 'build360_ai_bulk_page_progress',
                nonce: this.vars.nonces.bulk_page_progress,
                job_id: this.jobId
            }, function(response) {
                if (response.success && response.data) {
                    self.updateUI(response.data);
                } else if (response.data && response.data.no_job) {
                    self.stopPolling();
                }
            }).fail(function() {
                // Don't stop polling on network errors
            });
        },

        updateUI: function(data) {
            var s = this.vars.strings;
            var $wrap = $('#build360-ai-bulk-page-progress');
            if (!$wrap.length) return;

            var pct = data.total > 0 ? Math.round((data.completed / data.total) * 100) : 0;

            $wrap.find('.build360-bulk-bar').css('width', pct + '%');
            $wrap.find('.build360-bulk-counter').text(
                data.completed + ' ' + s.of + ' ' + data.total + ' ' + s.pages_processed
            );

            var statsText = '';
            if (data.succeeded > 0) statsText += data.succeeded + ' ' + s.succeeded;
            if (data.failed > 0) statsText += (statsText ? ', ' : '') + data.failed + ' ' + s.failed;
            $wrap.find('.build360-bulk-succeeded').text(statsText);

            var $badge = $wrap.find('.build360-bulk-status-badge');
            var $spinner = $wrap.find('.build360-bulk-spin');

            if (data.status === 'completed') {
                $badge.text(s.completed).removeClass('status-processing status-cancelled').addClass('status-completed');
                $spinner.removeClass('build360-bulk-spin');
                $wrap.find('.build360-bulk-page-cancel-btn').hide();
                $wrap.find('.build360-bulk-page-results-btn').show();
                $wrap.find('.build360-bulk-page-dismiss-btn').show();
                $wrap.find('.build360-bulk-batch-info').hide();
                this.stopPolling();
            } else if (data.status === 'cancelled') {
                $badge.text(s.cancelled).removeClass('status-processing status-completed').addClass('status-cancelled');
                $spinner.removeClass('build360-bulk-spin');
                $wrap.find('.build360-bulk-page-cancel-btn').hide();
                $wrap.find('.build360-bulk-page-dismiss-btn').show();
                $wrap.find('.build360-bulk-batch-info').hide();
                this.stopPolling();
            } else {
                $badge.text(s.processing).addClass('status-processing');
            }

            // Show error message if present
            if (data.error) {
                var $errorMsg = $wrap.find('.build360-bulk-error-message');
                if (!$errorMsg.length) {
                    var errorHtml = '<div class="build360-bulk-error-message">' +
                        '<span class="dashicons dashicons-warning"></span> ' +
                        '<span class="build360-bulk-error-text"></span>' +
                        '</div>';
                    $wrap.find('.build360-bulk-stats').after(errorHtml);
                    $errorMsg = $wrap.find('.build360-bulk-error-message');
                }
                var errorText = data.error_message || data.error;
                if (data.error === 'insufficient_tokens') {
                    errorText += ' <a href="' + (this.vars.account_url || 'https://build360.gr') + '" target="_blank">' +
                        (s.purchase_tokens || 'Purchase tokens') + ' &rarr;</a>';
                }
                $errorMsg.find('.build360-bulk-error-text').html(errorText);
                $errorMsg.show();
            }

            this.renderPageList(data.pages);
        },

        renderPageList: function(pages) {
            var s = this.vars.strings;
            var $container = $('#build360-ai-bulk-page-progress .build360-bulk-products');
            var html = '<table class="build360-bulk-products-table"><tbody>';

            var fieldLabels = {
                'seo_title': s.seo_title,
                'seo_description': s.seo_description
            };

            $.each(pages, function(pid, p) {
                var statusIcon = '';
                var statusClass = '';
                switch (p.status) {
                    case 'completed':
                        statusIcon = '<span class="dashicons dashicons-yes-alt build360-status-ok"></span>';
                        statusClass = 'row-completed';
                        break;
                    case 'processing':
                        statusIcon = '<span class="dashicons dashicons-update build360-bulk-spin-small"></span>';
                        statusClass = 'row-processing';
                        break;
                    case 'failed':
                        statusIcon = '<span class="dashicons dashicons-dismiss build360-status-fail"></span>';
                        statusClass = 'row-failed';
                        break;
                    default:
                        statusIcon = '<span class="dashicons dashicons-clock"></span>';
                        statusClass = 'row-pending';
                }

                var fieldBadges = '';
                if (p.fields) {
                    $.each(p.fields, function(fname, fstatus) {
                        var label = fieldLabels[fname] || fname;
                        var cls = 'field-' + fstatus;
                        fieldBadges += '<span class="build360-field-badge ' + cls + '">' + label + '</span>';
                    });
                }

                html += '<tr class="' + statusClass + '">' +
                    '<td class="col-status">' + statusIcon + '</td>' +
                    '<td class="col-name">' + $('<span>').text(p.name).html() + '</td>' +
                    '<td class="col-fields">' + fieldBadges + '</td>' +
                '</tr>';
            });

            html += '</tbody></table>';
            $container.html(html);
        },

        cancelJob: function() {
            var self = this;
            $.post(this.vars.ajax_url, {
                action: 'build360_ai_bulk_page_cancel',
                nonce: this.vars.nonces.bulk_page_cancel,
                job_id: this.jobId
            }, function(response) {
                if (response.success) {
                    self.stopPolling();
                    self.fetchProgress();
                }
            });
        },

        dismissJob: function(force) {
            var self = this;
            $.post(this.vars.ajax_url, {
                action: 'build360_ai_bulk_page_dismiss',
                nonce: this.vars.nonces.bulk_page_dismiss,
                job_id: this.jobId,
                force: force ? '1' : '0'
            }, function(response) {
                if (response.success && response.data) {
                    if (response.data.has_pending_reviews) {
                        var msg = response.data.pending_count + ' ' + self.vars.strings.dismiss_pending_warning;
                        if (confirm(msg)) {
                            self.dismissJob(true);
                        }
                        return;
                    }
                    $('#build360-ai-bulk-page-progress').slideUp(300, function() {
                        $(this).remove();
                    });
                    var url = new URL(window.location.href);
                    if (url.searchParams.has('build360_ai_bulk_page_job')) {
                        url.searchParams.delete('build360_ai_bulk_page_job');
                        window.history.replaceState({}, '', url.toString());
                    }
                }
            });
        },

        // ---- Review Modal ----

        showReviewModal: function() {
            this.reviewPage = 1;
            $('#build360-ai-bulk-page-review-modal').css({display: 'flex', alignItems: 'center', justifyContent: 'center'});
            this.loadReviewPage();
        },

        hideReviewModal: function() {
            $('#build360-ai-bulk-page-review-modal').css('display', 'none');
        },

        loadReviewPage: function() {
            var self = this;
            var s = this.vars.strings;
            var $body = $('#build360-ai-bulk-page-review-modal .build360-review-products');
            $body.html('<div class="build360-review-loading"><span class="spinner is-active"></span> Loading...</div>');

            $.post(this.vars.ajax_url, {
                action: 'build360_ai_bulk_page_review',
                nonce: this.vars.nonces.bulk_page_review,
                job_id: this.jobId,
                page: this.reviewPage,
                per_page: 20
            }, function(response) {
                if (response.success && response.data) {
                    self.renderReviewPages(response.data);
                    self.renderReviewPagination(response.data);
                    self.renderExpirationNotice(response.data.expires_at);
                } else {
                    var msg = (response.data && response.data.message) ? response.data.message : 'Error loading review data.';
                    $body.html('<p class="build360-review-error">' + msg + '</p>');
                }
            }).fail(function() {
                $body.html('<p class="build360-review-error">Network error. Please try again.</p>');
            });
        },

        renderReviewPages: function(data) {
            var s = this.vars.strings;
            var fieldLabels = {
                'seo_title': s.seo_title,
                'seo_description': s.seo_description
            };

            var $container = $('#build360-ai-bulk-page-review-modal .build360-review-products');
            var html = '';

            if (!data.pages || data.pages.length === 0) {
                html = '<p class="build360-review-empty">No pages to review.</p>';
                $container.html(html);
                return;
            }

            $.each(data.pages, function(idx, pg) {
                var isApplied = pg.review_status === 'applied';
                var cardClass = 'build360-review-card' + (isApplied ? ' build360-review-applied' : '');

                html += '<div class="' + cardClass + '" data-page-id="' + pg.id + '">';
                html += '<div class="build360-review-card-header">';
                html += '<strong>' + $('<span>').text(pg.name).html() + '</strong>';
                if (pg.edit_url) {
                    html += ' <a href="' + pg.edit_url + '" target="_blank" class="build360-review-edit-link">' + s.edit + ' &rarr;</a>';
                }
                if (isApplied) {
                    html += '<span class="build360-review-status-badge build360-review-status-applied">' + s.applied + '</span>';
                }
                html += '</div>';

                if (!isApplied && pg.previews) {
                    html += '<div class="build360-review-fields">';
                    $.each(data.fields, function(fidx, field) {
                        if (pg.previews[field]) {
                            var label = fieldLabels[field] || field;
                            // All SEO fields render as inputs (short text)
                            html += '<div class="build360-review-field">';
                            html += '<label class="build360-review-field-label">' + label + '</label>';
                            html += '<input type="text" class="build360-review-input" data-field="' + field + '" value="' +
                                $('<span>').text(pg.previews[field]).html() + '">';
                            html += '</div>';
                        }
                    });
                    html += '</div>';

                    html += '<div class="build360-review-card-actions">';
                    html += '<button type="button" class="button button-primary build360-page-review-accept-btn" data-page-id="' + pg.id + '">' +
                        '<span class="dashicons dashicons-yes"></span> ' + s.accept + '</button>';
                    html += '<button type="button" class="button build360-page-review-skip-btn" data-page-id="' + pg.id + '">' +
                        '<span class="dashicons dashicons-no"></span> ' + s.skip + '</button>';
                    html += '</div>';
                }

                html += '</div>';
            });

            $container.html(html);

            var counter = data.total_pages_count + ' ' + s.pages_processed;
            $('#build360-ai-bulk-page-review-modal .build360-review-counter').text(counter);
        },

        renderReviewPagination: function(data) {
            var $pagination = $('#build360-ai-bulk-page-review-modal .build360-review-pagination');

            if (data.total_pages <= 1) {
                $pagination.html('');
                return;
            }

            var html = '<div class="build360-review-pages">';

            if (data.page > 1) {
                html += '<button type="button" class="button build360-page-review-page-btn" data-page="' + (data.page - 1) + '">&laquo;</button>';
            }

            for (var i = 1; i <= data.total_pages; i++) {
                if (i === data.page) {
                    html += '<span class="build360-review-page-current">' + i + '</span>';
                } else if (i <= 3 || i > data.total_pages - 2 || Math.abs(i - data.page) <= 1) {
                    html += '<button type="button" class="button build360-page-review-page-btn" data-page="' + i + '">' + i + '</button>';
                } else if (i === 4 && data.page > 5) {
                    html += '<span class="build360-review-page-dots">...</span>';
                } else if (i === data.total_pages - 2 && data.page < data.total_pages - 4) {
                    html += '<span class="build360-review-page-dots">...</span>';
                }
            }

            if (data.page < data.total_pages) {
                html += '<button type="button" class="button build360-page-review-page-btn" data-page="' + (data.page + 1) + '">&raquo;</button>';
            }

            html += '</div>';
            $pagination.html(html);
        },

        renderExpirationNotice: function(expiresAt) {
            var $notice = $('#build360-ai-bulk-page-review-modal .build360-review-expiration');
            if (!expiresAt) {
                if ($notice.length) $notice.remove();
                return;
            }

            var now = new Date();
            var expires = new Date(expiresAt);
            var minutesLeft = Math.max(0, Math.round((expires - now) / 60000));

            if (minutesLeft <= 0) {
                var text = 'Previews have expired. Please run generation again.';
                var cssClass = 'build360-review-expiration build360-review-expiration-expired';
            } else if (minutesLeft <= 10) {
                var text = 'Previews expire in ' + minutesLeft + ' minute' + (minutesLeft !== 1 ? 's' : '') + '!';
                var cssClass = 'build360-review-expiration build360-review-expiration-warning';
            } else {
                var text = 'Previews expire in ' + minutesLeft + ' minutes.';
                var cssClass = 'build360-review-expiration';
            }

            if ($notice.length) {
                $notice.attr('class', cssClass).text(text);
            } else {
                $('#build360-ai-bulk-page-review-modal .build360-review-products').before(
                    '<div class="' + cssClass + '">' + text + '</div>'
                );
            }
        },

        acceptPage: function(pageId, $btn) {
            var self = this;
            var s = this.vars.strings;
            var $card = $btn.closest('.build360-review-card');

            var edits = {};
            $card.find('.build360-review-input').each(function() {
                var field = $(this).data('field');
                var val = $(this).val();
                if (field && val) {
                    edits[field] = val;
                }
            });

            $btn.prop('disabled', true).text(s.applying);
            $card.find('.build360-page-review-skip-btn').prop('disabled', true);

            $.post(this.vars.ajax_url, {
                action: 'build360_ai_bulk_page_apply',
                nonce: this.vars.nonces.bulk_page_apply,
                page_id: pageId,
                edits: edits
            }, function(response) {
                if (response.success) {
                    $card.addClass('build360-review-applied');
                    $card.find('.build360-review-fields').slideUp(200);
                    $card.find('.build360-review-card-actions').html(
                        '<span class="build360-review-status-badge build360-review-status-applied"><span class="dashicons dashicons-yes-alt"></span> ' + s.applied + '</span>'
                    );
                } else {
                    var msg = (response.data && response.data.message) ? response.data.message : 'Error applying content.';
                    alert(msg);
                    $btn.prop('disabled', false).text(s.accept);
                    $card.find('.build360-page-review-skip-btn').prop('disabled', false);
                }
            }).fail(function() {
                alert('Network error.');
                $btn.prop('disabled', false).text(s.accept);
                $card.find('.build360-page-review-skip-btn').prop('disabled', false);
            });
        },

        skipPage: function(pageId, $btn) {
            var self = this;
            var s = this.vars.strings;
            var $card = $btn.closest('.build360-review-card');

            $btn.prop('disabled', true);
            $card.find('.build360-page-review-accept-btn').prop('disabled', true);

            $.post(this.vars.ajax_url, {
                action: 'build360_ai_bulk_page_reject',
                nonce: this.vars.nonces.bulk_page_reject,
                page_id: pageId
            }, function(response) {
                if (response.success) {
                    $card.addClass('build360-review-skipped');
                    $card.find('.build360-review-fields').slideUp(200);
                    $card.find('.build360-review-card-actions').html(
                        '<span class="build360-review-status-badge build360-review-status-skipped"><span class="dashicons dashicons-no"></span> ' + s.skipped + '</span>'
                    );
                } else {
                    $btn.prop('disabled', false);
                    $card.find('.build360-page-review-accept-btn').prop('disabled', false);
                }
            });
        },

        acceptAll: function() {
            var self = this;
            var s = this.vars.strings;
            var $btn = $('.build360-page-review-accept-all');
            $btn.prop('disabled', true).text(s.applying);

            $.post(this.vars.ajax_url, {
                action: 'build360_ai_bulk_page_apply_all',
                nonce: this.vars.nonces.bulk_page_apply_all,
                job_id: this.jobId
            }, function(response) {
                $btn.prop('disabled', false).text(s.accept_all);
                if (response.success) {
                    $('.build360-review-card').not('.build360-review-applied, .build360-review-skipped').each(function() {
                        $(this).addClass('build360-review-applied');
                        $(this).find('.build360-review-fields').slideUp(200);
                        $(this).find('.build360-review-card-actions').html(
                            '<span class="build360-review-status-badge build360-review-status-applied"><span class="dashicons dashicons-yes-alt"></span> ' + s.applied + '</span>'
                        );
                    });
                    var msg = (response.data && response.data.message) ? response.data.message : s.applied;
                    alert(msg);
                } else {
                    var errMsg = (response.data && response.data.message) ? response.data.message : 'Error applying content.';
                    alert(errMsg);
                }
            }).fail(function() {
                $btn.prop('disabled', false).text(s.accept_all);
                alert('Network error.');
            });
        }
    };

    $(document).ready(function() {
        BulkPageTracker.init();
    });

})(jQuery);
