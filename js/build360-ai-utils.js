/**
 * Build360 AI Utilities
 * Common utility functions used across the plugin
 */
var Build360AIUtils = (function($) {
    'use strict';
    
    return {
        /**
         * Show a notification message
         * @param {string} message - The message to display
         * @param {string} type - The type of message (success, error, warning, info)
         * @param {jQuery} $container - The container to append the message to
         * @param {number} duration - How long to show the message (ms), 0 for no auto-hide
         */
        showNotification: function(message, type, $container, duration) {
            type = type || 'info';
            duration = duration || 3000;
            
            const $notification = $('<div class="build360-ai-notification ' + type + '"></div>')
                .html(message)
                .appendTo($container);
            
            if (duration > 0) {
                setTimeout(function() {
                    $notification.fadeOut(300, function() {
                        $(this).remove();
                    });
                }, duration);
            }
            
            return $notification;
        },
        
        /**
         * Format a number with commas
         * @param {number} number - The number to format
         * @return {string} The formatted number
         */
        formatNumber: function(number) {
            return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        },
        
        /**
         * Debounce function to limit how often a function can be called
         * @param {function} func - The function to debounce
         * @param {number} wait - The time to wait between calls (ms)
         * @return {function} The debounced function
         */
        debounce: function(func, wait) {
            let timeout;
            return function() {
                const context = this;
                const args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    func.apply(context, args);
                }, wait);
            };
        },
        
        /**
         * Update token usage display
         * @param {object} response - The response from the server
         */
        updateTokenUsage: function(response) {
            if (response.token_usage) {
                const usage = response.token_usage;
                const usagePercent = (usage.used / usage.total) * 100;
                
                $('.token-count').text(this.formatNumber(usage.remaining));
                $('.tokens-info .total-tokens').text(this.formatNumber(usage.total));
                $('.tokens-info .used-tokens').text(this.formatNumber(usage.used));
                
                if ($('.tokens-info .used-today').length) {
                    $('.tokens-info .used-today').text(this.formatNumber(usage.used_today || 0));
                }
                
                if ($('.tokens-info .used-month').length) {
                    $('.tokens-info .used-month').text(this.formatNumber(usage.used_month || 0));
                }
                
                $('.progress-bar').css('width', usagePercent + '%');
            }
        },
        
        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            $('.build360-ai-tooltip').hover(function() {
                const tooltip = $(this).attr('title');
                $(this).data('tooltip', tooltip).removeAttr('title');
                $('<div class="build360-ai-tooltip-popup"></div>')
                    .text(tooltip)
                    .appendTo('body')
                    .css({
                        top: $(this).offset().top - 30,
                        left: $(this).offset().left + $(this).width() / 2
                    })
                    .fadeIn(200);
            }, function() {
                $(this).attr('title', $(this).data('tooltip'));
                $('.build360-ai-tooltip-popup').remove();
            });
        },
        
        /**
         * Initialize number input validation
         */
        initNumberInputValidation: function() {
            $('input[type="number"]').on('input', function() {
                const min = parseInt($(this).attr('min')) || 0;
                const max = parseInt($(this).attr('max')) || 1000;
                const step = parseInt($(this).attr('step')) || 1;
                let value = parseInt($(this).val());
                
                if (isNaN(value)) {
                    return;
                }
                
                // Find the closest valid value based on step
                const remainder = (value - min) % step;
                if (remainder !== 0) {
                    const lowerValue = value - remainder;
                    const upperValue = lowerValue + step;
                    
                    // Choose the closest valid value
                    if (Math.abs(value - lowerValue) < Math.abs(value - upperValue)) {
                        value = lowerValue;
                    } else {
                        value = upperValue;
                    }
                }
                
                // Ensure value is within min/max range
                value = Math.max(min, Math.min(max, value));
                
                // Update the input value if it changed
                if (value !== parseInt($(this).val())) {
                    $(this).val(value);
                }
            });
        }
    };
})(jQuery);
