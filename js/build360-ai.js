jQuery(document).ready(function($) {
    'use strict';

    // This file is kept for backward compatibility
    // All functionality has been moved to build360-ai-product.js

    // Handle copy to clipboard
    $(document).on('click', '.build360-ai-copy-content', function() {
        var content = $(this).data('content');
        var $temp = $('<textarea>');

        $('body').append($temp);
        $temp.val(content).select();

        try {
            document.execCommand('copy');
            $(this).text(build360_ai_vars.copied_text);

            var $button = $(this);
            setTimeout(function() {
                $button.text(build360_ai_vars.copy_text);
            }, 2000);
        } catch (e) {
            alert(build360_ai_vars.copy_error);
        }

        $temp.remove();
    });
});