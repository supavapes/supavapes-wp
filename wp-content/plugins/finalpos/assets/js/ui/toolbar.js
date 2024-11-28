(function($) {
    $(document).ready(function() {
        // Add close button to the body
        $('body').append('<button class="uxlabs-fullscreen-close" title="Exit Fullscreen"><span class="material-symbols-outlined">close</span></button>');

        $('#wp-admin-bar-fullscreen-mode, .uxlabs-fullscreen-close').on('click', function(e) {
            e.preventDefault();
            toggleFullScreen();
        });

        function toggleFullScreen() {
            $('body').toggleClass('uxlabs-fullscreen-mode');

            // Trigger window resize event to force any responsive elements to adjust
            $(window).trigger('resize');
        }
    });
})(jQuery);


