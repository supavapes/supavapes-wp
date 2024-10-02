( function( wp ) {
    const { createNotice } = wp.data.dispatch('core/notices');
    
    // Add a custom notice on the cart page
    if ( window.location.pathname.includes('cart') ) {
        createNotice(
            'success', // Can be "success", "warning", "error", or "info"
            'This is a custom notice for the cart page!', // Text of the notice
            {
                isDismissible: true, // Whether the notice can be dismissed
            }
        );
    }

    // Add a custom notice on the checkout page
    if ( window.location.pathname.includes('checkout') ) {
        createNotice(
            'info',
            'This is a custom notice for the checkout page!',
            {
                isDismissible: true,
            }
        );
    }

} )( window.wp );
