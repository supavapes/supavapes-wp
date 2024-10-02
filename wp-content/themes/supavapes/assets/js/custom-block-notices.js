(function(wp) {
    const { createNotice } = wp.data.dispatch('core/notices');
    
    // Function to add a custom notice
    function addCustomNotice(message, status = 'info') {
        createNotice(
            status, // Type of notice: 'success', 'error', 'info', etc.
            message, // Message content
            {
                isDismissible: true, // Optional, whether the notice is dismissible by the user
            }
        );
    }

    // Check if the cart or checkout blocks are present and add the notice
    function checkPageAndAddNotice() {
        const pathname = window.location.pathname;
        
        if ( pathname.includes('cart') ) {
            addCustomNotice('This is a custom notice for the Cart page!', 'info');
        }

        if ( pathname.includes('checkout') ) {
            addCustomNotice('This is a custom notice for the Checkout page!', 'info');
        }
    }

    // Wait until the WooCommerce blocks are fully loaded/rendered
    wp.domReady(() => {
        // Check page on load
        checkPageAndAddNotice();

        // If necessary, observe for changes in the DOM for dynamic content (e.g., block rendering updates)
        const observer = new MutationObserver(() => {
            checkPageAndAddNotice();
        });

        // Observe for mutations on the body (can be optimized for specific elements if needed)
        observer.observe(document.body, { childList: true, subtree: true });
    });

})(window.wp);
