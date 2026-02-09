/**
 * Build360 AI - Bulk Generation Progress Tracker
 *
 * Handles progress bar UI, polling, cancel, and results modal
 * for background bulk content generation via Action Scheduler.
 */
(function($) {
    'use strict';

    var BulkTracker = {
        jobId: '',
        pollInterval: null,
        pollDelay: 3000,
        vars: {},

        init: function() {
            this.vars = window.build360_ai_bulk_vars || {};
            if (!this.vars.ajax_url) return;

            // Check URL param first (just redirected from bulk action)
            var urlParams = new URLSearchParams(window.location.search);
            var urlJobId = urlParams.get('build360_ai_bulk_job');

            if (urlJobId) {
                this.jobId = urlJobId;
                this.injectProgressBar();
                this.startPolling();
            } else if (this.vars.active_job_id) {
                // Active job from a previous page load
                this.jobId = this.vars.active_job_id;
                this.checkExistingJob();
            }
        },

        checkExistingJob: function() {
            var self = this;
            $.post(this.vars.ajax_url, {
                action: 'build360_ai_bulk_progress',
                nonce: this.vars.nonces.bulk_progress,
                job_id: this.jobId
            }, function(response) {
                if (response.success && response.data) {
                    var status = response.data.status;
                    if (status === 'processing') {
                        self.injectProgressBar();
                        self.startPolling();
                    } else if (status === 'completed') {
                        self.injectProgressBar();
                        self.updateUI(response.data);
                    }
                    // cancelled or no_job: do nothing
                }
            });
        },

        injectProgressBar: function() {
            if ($('#build360-ai-bulk-progress').length) return;

            var s = this.vars.strings;
            var html = '<div id="build360-ai-bulk-progress" class="build360-bulk-progress-wrap">' +
                '<div class="build360-bulk-header">' +
                    '<span class="dashicons dashicons-update build360-bulk-spin"></span>' +
                    '<strong>' + s.progress_title + '</strong>' +
                    '<span class="build360-bulk-status-badge">' + s.processing + '</span>' +
                '</div>' +
                '<div class="build360-bulk-bar-container">' +
                    '<div class="build360-bulk-bar" style="width: 0%"></div>' +
                '</div>' +
                '<div class="build360-bulk-stats">' +
                    '<span class="build360-bulk-counter">0 ' + s.of + ' 0 ' + s.products_processed + '</span>' +
                    '<span class="build360-bulk-succeeded"></span>' +
                '</div>' +
                '<div class="build360-bulk-products"></div>' +
                '<div class="build360-bulk-actions">' +
                    '<button type="button" class="button build360-bulk-cancel-btn">' + s.cancel + '</button>' +
                    '<button type="button" class="button button-primary build360-bulk-results-btn" style="display:none;">' + s.view_results + '</button>' +
                '</div>' +
            '</div>';

            // Insert after the first .wrap h1 or before the table
            var $wrap = $('.wrap');
            if ($wrap.length) {
                var $heading = $wrap.find('h1.wp-heading-inline, h1:first');
                if ($heading.length) {
                    $(html).insertAfter($heading.parent().find('.subsubsub').length ? $heading.parent().find('.subsubsub') : $heading);
                } else {
                    $wrap.prepend(html);
                }
            }

            // Bind events
            var self = this;
            $(document).on('click', '.build360-bulk-cancel-btn', function() {
                if (confirm(s.cancel_confirm)) {
                    self.cancelJob();
                }
            });
            $(document).on('click', '.build360-bulk-results-btn', function() {
                self.showResults();
            });
        },

        startPolling: function() {
            var self = this;
            this.pollInterval = setInterval(function() {
                self.fetchProgress();
            }, this.pollDelay);
            // Immediate first fetch
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
                action: 'build360_ai_bulk_progress',
                nonce: this.vars.nonces.bulk_progress,
                job_id: this.jobId
            }, function(response) {
                if (response.success && response.data) {
                    self.updateUI(response.data);
                } else if (response.data && response.data.no_job) {
                    self.stopPolling();
                }
            }).fail(function() {
                // Don't stop polling on network errors; just skip
            });
        },

        updateUI: function(data) {
            var s = this.vars.strings;
            var $wrap = $('#build360-ai-bulk-progress');
            if (!$wrap.length) return;

            var pct = data.total > 0 ? Math.round((data.completed / data.total) * 100) : 0;

            // Progress bar
            $wrap.find('.build360-bulk-bar').css('width', pct + '%');

            // Counter
            $wrap.find('.build360-bulk-counter').text(
                data.completed + ' ' + s.of + ' ' + data.total + ' ' + s.products_processed
            );

            // Succeeded/failed
            var statsText = '';
            if (data.succeeded > 0) statsText += data.succeeded + ' ' + s.succeeded;
            if (data.failed > 0) statsText += (statsText ? ', ' : '') + data.failed + ' ' + s.failed;
            $wrap.find('.build360-bulk-succeeded').text(statsText);

            // Status badge
            var $badge = $wrap.find('.build360-bulk-status-badge');
            var $spinner = $wrap.find('.build360-bulk-spin');

            if (data.status === 'completed') {
                $badge.text(s.completed).removeClass('status-processing status-cancelled').addClass('status-completed');
                $spinner.removeClass('build360-bulk-spin');
                $wrap.find('.build360-bulk-cancel-btn').hide();
                $wrap.find('.build360-bulk-results-btn').show();
                this.stopPolling();
            } else if (data.status === 'cancelled') {
                $badge.text(s.cancelled).removeClass('status-processing status-completed').addClass('status-cancelled');
                $spinner.removeClass('build360-bulk-spin');
                $wrap.find('.build360-bulk-cancel-btn').hide();
                this.stopPolling();
            } else {
                $badge.text(s.processing).addClass('status-processing');
            }

            // Per-product list
            this.renderProductList(data.products);
        },

        renderProductList: function(products) {
            var s = this.vars.strings;
            var $container = $('#build360-ai-bulk-progress .build360-bulk-products');
            var html = '<table class="build360-bulk-products-table"><tbody>';

            var fieldLabels = {
                'description': s.description,
                'short_description': s.short_description,
                'seo_title': s.seo_title,
                'seo_description': s.seo_description
            };

            $.each(products, function(pid, p) {
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
                action: 'build360_ai_bulk_cancel',
                nonce: this.vars.nonces.bulk_cancel,
                job_id: this.jobId
            }, function(response) {
                if (response.success) {
                    self.stopPolling();
                    self.fetchProgress(); // One final update
                }
            });
        },

        showResults: function() {
            var self = this;

            // Show loading state
            if ($('#build360-ai-bulk-results-modal').length) {
                $('#build360-ai-bulk-results-modal').addClass('active');
                return;
            }

            $.post(this.vars.ajax_url, {
                action: 'build360_ai_bulk_results',
                nonce: this.vars.nonces.bulk_results,
                job_id: this.jobId
            }, function(response) {
                if (response.success && response.data) {
                    self.renderResultsModal(response.data);
                }
            });
        },

        renderResultsModal: function(data) {
            var s = this.vars.strings;
            var fieldLabels = {
                'description': s.description,
                'short_description': s.short_description,
                'seo_title': s.seo_title,
                'seo_description': s.seo_description
            };

            var body = '';
            $.each(data.products, function(pid, p) {
                var statusClass = p.status === 'completed' ? 'result-success' : 'result-fail';
                body += '<div class="build360-result-product ' + statusClass + '">';
                body += '<div class="result-product-header">';
                body += '<strong>' + $('<span>').text(p.name).html() + '</strong>';
                if (p.edit_url) {
                    body += ' <a href="' + p.edit_url + '" class="build360-result-edit" target="_blank">' + s.edit + ' &rarr;</a>';
                }
                body += '</div>';

                if (p.fields) {
                    body += '<div class="result-fields">';
                    $.each(p.fields, function(fname, fdata) {
                        var label = fieldLabels[fname] || fname;
                        var fclass = fdata.status === 'completed' ? 'field-ok' : 'field-fail';
                        body += '<div class="result-field ' + fclass + '">';
                        body += '<span class="result-field-label">' + label + ':</span> ';
                        if (fdata.preview) {
                            body += '<span class="result-field-preview">' + $('<span>').text(fdata.preview).html() + '...</span>';
                        } else {
                            body += '<span class="result-field-status">' + (fdata.status === 'completed' ? '&#10003;' : '&#10007;') + '</span>';
                        }
                        body += '</div>';
                    });
                    body += '</div>';
                }
                body += '</div>';
            });

            var summary = data.succeeded + ' ' + s.succeeded;
            if (data.failed > 0) summary += ', ' + data.failed + ' ' + s.failed;

            var html = '<div id="build360-ai-bulk-results-modal" class="build360-bulk-modal active">' +
                '<div class="build360-bulk-modal-overlay"></div>' +
                '<div class="build360-bulk-modal-content">' +
                    '<div class="build360-bulk-modal-header">' +
                        '<h2>' + s.results_title + '</h2>' +
                        '<span class="build360-bulk-modal-summary">' + summary + '</span>' +
                        '<button type="button" class="build360-bulk-modal-close"><span class="dashicons dashicons-no-alt"></span></button>' +
                    '</div>' +
                    '<div class="build360-bulk-modal-body">' + body + '</div>' +
                    '<div class="build360-bulk-modal-footer">' +
                        '<button type="button" class="button build360-bulk-modal-close-btn">' + s.close + '</button>' +
                    '</div>' +
                '</div>' +
            '</div>';

            $('body').append(html);

            $(document).on('click', '.build360-bulk-modal-close, .build360-bulk-modal-close-btn, .build360-bulk-modal-overlay', function() {
                $('#build360-ai-bulk-results-modal').remove();
            });
        }
    };

    $(document).ready(function() {
        BulkTracker.init();
    });

})(jQuery);
