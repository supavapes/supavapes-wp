document.addEventListener('DOMContentLoaded', function () {
    const notices = document.querySelectorAll('.notice-error:not(.hidden):not(.inline), .notice-warning:not(.hidden):not(.inline), .notice-info:not(.hidden):not(.inline), div.error:not(.hidden):not(.inline), .notice.notice-error:not(.hidden):not(.inline)');
    const notificationBubble = document.getElementById('notification-bubble');
    const notificationText = document.getElementById('custom-notification-text');
    const userName = notificationText.getAttribute('data-name');
    const count = notices.length;

    // Start with no notifications
    notificationBubble.classList.add('no-notifications');
    notificationBubble.textContent = '0';

    // Update notification bubble count and text after a short delay
    setTimeout(() => {
        if (count > 0) {
            notificationBubble.textContent = count;
            notificationBubble.classList.remove('no-notifications');
            notificationBubble.classList.add('error-bubble');
            notificationText.textContent = count === 1
                ? `${final_ui_object.i18n.hey} ${userName}! ${final_ui_object.i18n.notification_single}`
                : `${final_ui_object.i18n.hey} ${userName}! ${final_ui_object.i18n.notification_multiple.replace('%d', count)}`;
        } else {
            notificationBubble.textContent = '0';
            notificationBubble.classList.remove('error-bubble');
            notificationBubble.classList.add('no-notifications');
            notificationText.textContent = `${final_ui_object.i18n.hey} ${userName}! ${final_ui_object.i18n.all_caught_up}`;
        }

        // Fade in the notification text
        setTimeout(() => {
            notificationText.classList.add('show');
        }, 50); // Small delay to ensure the new text is set before fading in
    }, 500); // 500ms delay for the transition effect

    // Initially hide all notices and set up for animation
    notices.forEach(function (notice) {
        notice.style.display = 'none';
        notice.style.opacity = '0';
        notice.style.transition = 'opacity 0.3s ease-in-out';
    });

    // Toggle notice visibility on bar click with animation
    document.getElementById('custom-notifications-bar').addEventListener('click', function () {
        const isHidden = notices.length > 0 && notices[0].style.display === 'none';
        
        if (isHidden) {
            notices.forEach(function (notice) {
                notice.style.display = 'block';
                setTimeout(() => {
                    notice.style.opacity = '1';
                }, 10);
            });
        } else {
            notices.forEach(function (notice) {
                notice.style.opacity = '0';
                notice.addEventListener('transitionend', function handler() {
                    notice.style.display = 'none';
                    notice.removeEventListener('transitionend', handler);
                });
            });
        }
    });

    

    jQuery(document).ready(function($) {
        // Function to set search placeholders
        function setSearchPlaceholders() {
            $('.search-box').each(function() {
                const searchBox = $(this);
                const searchInput = searchBox.find('input[type="search"]');
                const searchButton = searchBox.find('input[type="submit"]');
                const label = searchBox.find('label.screen-reader-text');

                // Extract post type from label text
                const labelText = label.text();
                const postType = labelText.replace(final_ui_object.i18n.search + ' ', '').replace(':', '');

                // Set placeholder text
                searchInput.attr('placeholder', final_ui_object.i18n.search_placeholder.replace('%s', postType));

                // Update button text
                searchButton.val(final_ui_object.i18n.search);
            });
        }

        // Function to update error messages
        function handleAjaxError() {
            sidebarContent.innerHTML = `<p>${final_ui_object.i18n.no_content}</p>`;
        }

        // Aufruf der Funktion
        setSearchPlaceholders();
    });

    
    // Toggle sidebar on specific button clicks
    function toggleSidebar(shortcode) {
        const sidebar = document.getElementById("final-sidebar");
        const sidebarContent = document.getElementById("final-sidebar-content");
        const overlay = document.getElementById("final-sidebar-overlay");

        jQuery.ajax({
            url: ajaxurl,
            type: "POST",
            data: {
                action: "execute_shortcode",
                shortcode: shortcode,
                _wpnonce: final_ui_object.nonce // Make sure this matches the nonce name in PHP
            },
            success: function(response) {
                if (response.success) {
                    sidebarContent.innerHTML = response.data;

                    // Re-run scripts loaded by the shortcode
                    const scripts = sidebarContent.getElementsByTagName("script");
                    for (let i = 0; i < scripts.length; i++) {
                        eval(scripts[i].innerText);
                    }

                    // Show the sidebar and overlay with animation
                    sidebar.style.display = "block";
                    overlay.style.display = "block";
                    requestAnimationFrame(() => {
                        sidebar.classList.add("show");
                        overlay.classList.add("show");
                    });
                } else {
                    console.error('Error:', response.data);
                    sidebarContent.innerHTML = "<p>There was a problem loading the content.</p>";
                }
            },
            error: function() {
                sidebarContent.innerHTML = "<p>There was a problem loading the content.</p>";
                sidebar.style.display = "block";
                overlay.style.display = "block";
                requestAnimationFrame(() => {
                    sidebar.classList.add("show");
                    overlay.classList.add("show");
                });
            }
        });
    }

    // Close sidebar when overlay or outside is clicked
    document.getElementById("final-sidebar-overlay").addEventListener("click", closeSidebar);
    document.addEventListener("click", function(e) {
        const sidebar = document.getElementById("final-sidebar");
    });

    function closeSidebar() {
        const sidebar = document.getElementById("final-sidebar");
        const overlay = document.getElementById("final-sidebar-overlay");

        // Hide the sidebar and overlay with animation
        sidebar.classList.remove("show");
        overlay.classList.remove("show");

        sidebar.addEventListener('transitionend', function handleTransitionEnd() {
            sidebar.style.display = "none";
            overlay.style.display = "none";
            sidebar.removeEventListener('transitionend', handleTransitionEnd);
        });
    }

    document.getElementById("wp-admin-bar-search").addEventListener("click", function(e) {
        e.preventDefault();
        toggleSidebar("[uxlabs-search]");
    });
    
    document.getElementById("wp-admin-bar-plugins").addEventListener("click", function(e) {
        e.preventDefault();
        toggleSidebar("[uxlabs-plugin]");
    });

    document.getElementById("wp-admin-bar-user-initials").addEventListener("click", function(e) {
        e.preventDefault();
        toggleSidebar("[uxlabs-user]");
    });

    // Open Users menu for comments submenu
    var usersMenu = document.getElementById('menu-users');
    if (usersMenu) {
        // Überprüfen, ob wir uns auf der Kommentarseite befinden
        if (window.location.href.indexOf('edit-comments.php') !== -1) {
            usersMenu.classList.add('wp-has-current-submenu', 'wp-menu-open');
            usersMenu.classList.remove('wp-not-current-submenu');
            var usersLink = usersMenu.querySelector('a.wp-has-submenu');
            if (usersLink) {
                usersLink.classList.add('wp-has-current-submenu', 'wp-menu-open');
                usersLink.classList.remove('wp-not-current-submenu');
            }
            var commentsLink = usersMenu.querySelector('a[href="edit-comments.php"]');
            if (commentsLink) {
                commentsLink.classList.add('current');
            }
        }
    }

    // Hide WooCommerce "Site visibility" tab if optimizations are enabled
    function hideWooCommerceSiteVisibilityTab() {
        const wooOptimizationsEnabled = document.body.classList.contains('final-woo-optimizations-enabled');
        if (wooOptimizationsEnabled) {
            const siteVisibilityTab = document.querySelector('a.nav-tab[href*="page=wc-settings&tab=site-visibility"]');
            if (siteVisibilityTab) {
                siteVisibilityTab.style.display = 'none';
            }
        }
    }

    hideWooCommerceSiteVisibilityTab();

    // SEARCH MENU
    var searchInput = document.getElementById('admin-menu-search');
    var menuItems = document.querySelectorAll('#adminmenu li.menu-top');
    searchInput.addEventListener('keyup', function() {
        var filter = searchInput.value.toLowerCase();
        menuItems.forEach(function(menuItem) {
            var textElement = menuItem.querySelector('.wp-menu-name');
            var menuVisible = false;
            if (textElement && textElement.textContent.toLowerCase().includes(filter)) {
                menuItem.style.display = '';
                menuVisible = true;
            } else {
                menuItem.style.display = 'none';
            }

            var subMenu = menuItem.querySelector('.wp-submenu');
            if (subMenu) {
                subMenu.querySelectorAll('li').forEach(function(subMenuItem) {
                    var subMenuTextElement = subMenuItem.querySelector('a');
                    if (subMenuTextElement && subMenuTextElement.textContent.toLowerCase().includes(filter)) {
                        subMenuItem.style.display = '';
                        menuItem.style.display = '';
                        menuVisible = true;
                    } else {
                        subMenuItem.style.display = 'none';
                    }
                });
            }
            if (!menuVisible) menuItem.style.display = 'none';
        });
    });

    // Logo and search bar functionality
    jQuery(document).ready(function($) {
        function toggleLogo() {
            var body = $('body');
            var customLogo = $('#custom-logo');
            var fullLogo = $('#custom-logo .full-logo');
            var shrinkedLogo = $('#custom-logo .shrinked-logo');

            if (body.hasClass("folded")) {
                fullLogo.hide();
                shrinkedLogo.show();
                customLogo.addClass("shrinked");
            } else {
                fullLogo.show();
                shrinkedLogo.hide();
                customLogo.removeClass("shrinked");
            }
        }

        function toggleSearchVisibility() {
            var body = $('body');
            var searchWrapper = $('#admin-menu-search-wrapper');
            searchWrapper.toggle(!body.hasClass("folded"));
        }

        function updateCollapseIcon() {
            var body = $('body');
            var iconSpan = $('#collapse-button .material-symbols-outlined');
            iconSpan.text(body.hasClass("folded") ? "keyboard_double_arrow_right" : "keyboard_double_arrow_left");
        }

        // Initial checks
        toggleLogo();
        toggleSearchVisibility();
        updateCollapseIcon();

        // Watch for changes to the body class
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === "class") {
                    toggleLogo();
                    toggleSearchVisibility();
                    updateCollapseIcon();
                }
            });
        });

        observer.observe($('body')[0], {attributes: true});

        // Replace the collapse menu icon
        var collapseButton = $('#collapse-button');
        if (collapseButton.length) {
            var originalIcon = collapseButton.find('.collapse-button-icon');
            if (originalIcon.length) {
                originalIcon.html('<span class="material-symbols-outlined"></span>');
            }

            // Update icon on button click
            collapseButton.on('click', function() {
                setTimeout(updateCollapseIcon, 300); // Delay to allow class change
            });
        }

        // Search functionality
        $('#admin-menu-search').on('keyup', function() {
            var filter = $(this).val().toLowerCase();
            $('#adminmenu li.menu-top').each(function() {
                var $menuItem = $(this);
                var $textElement = $menuItem.find('.wp-menu-name');
                var menuVisible = false;

                if ($textElement.text().toLowerCase().includes(filter)) {
                    $menuItem.show();
                    menuVisible = true;
                } else {
                    $menuItem.hide();
                }

                var $subMenu = $menuItem.find('.wp-submenu');
                if ($subMenu.length) {
                    $subMenu.find('li').each(function() {
                        var $subMenuItem = $(this);
                        var $subMenuTextElement = $subMenuItem.find('a');
                        if ($subMenuTextElement.text().toLowerCase().includes(filter)) {
                            $subMenuItem.show();
                            $menuItem.show();
                            menuVisible = true;
                        } else {
                            $subMenuItem.hide();
                        }
                    });
                }

                if (!menuVisible) $menuItem.hide();
            });
        });
    });
});

// User Shortcode JS 
jQuery(document).ready(function($) {
    function updatePreferences(key, value) {
        return $.post(ajaxurl, {
            action: 'uxlabs_save_preferences',
            key: key,
            value: value,
            _wpnonce: final_ui_object.nonce // Add the nonce here
        });
    }

    function applyPreferences(preferences) {
        if (preferences.dark_mode === '1') {
            $('body').addClass('uxlabs-dark-mode');
        } else {
            $('body').removeClass('uxlabs-dark-mode');
        }

        if (preferences.hide_menu_icons === '1') {
            $('#adminmenu').addClass('uxlabs-hide-menu-icons');
            $('#uxlabs-inline-style').remove();
            $('<style id="uxlabs-inline-style">#adminmenu .wp-menu-image, #adminmenu .wp-menu-image.svg { display: none !important; }</style>').appendTo('head');
        } else {
            $('#adminmenu').removeClass('uxlabs-hide-menu-icons');
            $('#uxlabs-inline-style').remove();
            $('#adminmenu .wp-menu-image, #adminmenu .wp-menu-image.svg').css('display', '');
        }

        if (preferences.hide_admin_toolbar === '1') {
            $('body').addClass('uxlabs-hide-admin-toolbar');
        } else {
            $('body').removeClass('uxlabs-hide-admin-toolbar');
        }
    }

    $.post(ajaxurl, {
        action: 'uxlabs_get_preferences',
        _wpnonce: final_ui_object.nonce // Add the nonce here
    }, function(response) {
        if (response.success) {
            applyPreferences(response.data);
        }
    });
});

