function processPayment(paymentData) {
    console.log(paymentData);
    const paymentToken = paymentData.paymentMethodData.tokenizationData.token;
    const cartTotal = jQuery('#woocommerce .order-total .amount').text().replace(/[^0-9.-]+/g, "");

    jQuery.ajax({
        url: ajax_gpay_object.ajax_url,
        method: 'POST',
        data: {
            action: 'sv_process_google_pay',
            nonce: ajax_gpay_object.gpay_nonce,
            payment_token: paymentToken,
            cart_total: cartTotal
        },
        success: function(response) {
            if (response.success) {
                window.location.href = '/order-confirmation';
            } else {
                alert('Payment failed: ' + response.data);
            }
        },
        error: function(error) {
            console.error('AJAX error: ', error);
            alert('There was an error processing your payment.');
        }
    });
}

jQuery(document).ready(function($) {
    onGooglePayLoaded();  // Ensure this is called to setup Google Pay
});
