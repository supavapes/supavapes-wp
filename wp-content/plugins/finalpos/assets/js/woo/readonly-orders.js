jQuery(document).ready(function($) {
    if (typeof orderData !== 'undefined' && orderData.readonly) {
        // Disable all inputs and buttons
        $(':input, :button, .button').prop('disabled', true);
        $('.edit_address').remove();

        // Specifically target order status dropdown and buttons
        $('.wc-order-status select').prop('disabled', true);
        $('.wc-order-status').addClass('disabled');
        $('.order_actions').find('select, button').prop('disabled', true);

        // Remove action buttons
        $('.wc-order-status-actions').remove();
        
        // Block all AJAX requests related to order updates
        $(document).ajaxSend(function(event, xhr, settings) {
            if (settings.url.includes('wc-ajax') || settings.url.includes('admin-ajax.php')) {
                if (settings.url.includes('orders')) {
                    xhr.abort();
                    alert('This order is read-only and cannot be modified.');
                }
            }
        });

        // Add visual indicator at the top
        $('<div class="notice notice-warning" style="margin: 10px 0;">' +
            '<p><strong>Notice:</strong> This order is read-only and cannot be modified.</p>' +
          '</div>').insertBefore('.woocommerce-order-data');
    }
});
