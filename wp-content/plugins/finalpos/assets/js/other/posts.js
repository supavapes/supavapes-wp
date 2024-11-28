//
// Post Filter Toggles
//



jQuery(document).ready(function($) {
    // Filter-Toggle-Button hinzufügen
    $('.tablenav .bulkactions').after('<button type="button" class="button action postfilter-toggle"><span class="material-symbols-outlined">filter_alt</span></button>');

    // Toggle-Funktion für die Filter
    $('.postfilter-toggle').on('click change', function() {
        $('.tablenav .actions:not(.bulkactions)').toggle();
    });

    // Screen Options-Button hinzufügen
    $('.postfilter-toggle').after('<button type="button" class="button action screen-options-toggle"><span class="material-symbols-outlined">add_column_right</span></button>');

    // Toggle-Funktion für die Screen Options
    var screenOptionsToggle = document.querySelector('.screen-options-toggle');
    var originalScreenOptionsButton = document.getElementById('show-settings-link');
    if (screenOptionsToggle && originalScreenOptionsButton) {
        screenOptionsToggle.addEventListener('click', function(event) {
            event.preventDefault();
            originalScreenOptionsButton.click();
        });
    }
});


