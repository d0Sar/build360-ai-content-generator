jQuery(document).ready(function ($) {
    console.log('Build360 AI: admin.js loaded. Minimal version.');

    // Initialize
    function init() {
        // Removed bindEvents(); as it was empty.
        // Global initializations that apply to ALL admin pages can go here, if any.
        // For example, if Build360AIUtils.initTooltips(); should run on every admin page, add it here.
        // However, it's often better to call utilities from page-specific scripts.
    }

    // Initialize
    init();

    // Nonce and AJAX URL check (can be kept for global admin context, if needed)
    if (typeof build360_ai_vars !== 'undefined' &&
        typeof build360_ai_vars.nonce === 'string' && build360_ai_vars.nonce.length > 0 &&
        build360_ai_vars.ajax_url) {
        console.log('Build360 AI: admin.js - Global nonce and AJAX URL available.');
    } else {
        console.warn('Build360 AI: admin.js - Global build360_ai_vars, nonce, or AJAX URL not fully available.');
    }
});