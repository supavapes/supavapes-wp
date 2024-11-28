(function($) {
    'use strict';

    var UXLABS = window.UXLABS || {};

    UXLABS.Sidebar = {
        preferences: {},
        genericSettings: {},
        currentPage: 1,
        maxPages: 1,
        isLoading: false,

        init: function() {
            this.setupSearch();
            this.setupPreferences();    
        },

        setupSearch: function() {
            var self = this;
            var debounceTimer;

            function performSearch(query, post_type, loadMore = false) {
                if (self.isLoading) return;
                self.isLoading = true;

                if (!loadMore) {
                    self.currentPage = 1;
                    $('#uxlabs-results').empty();
                }

                $.post(ajaxurl, {
                    action: 'uxlabs_search',
                    query: query,
                    post_type: post_type,
                    page: self.currentPage,
                    nonce: uxlabsAjax.nonce
                }, function(response) {
                    if (loadMore) {
                        $.each(response.grouped_results, function(category, content) {
                            var $existingCategory = $('#uxlabs-results .uxlabs-category-' + category);
                            if ($existingCategory.length) {
                                $existingCategory.append(content);
                            } else {
                                $('#uxlabs-results').append('<div class="uxlabs-category-title">' + category.charAt(0).toUpperCase() + category.slice(1) + 's</div><div class="uxlabs-category-' + category + '">' + content + '</div>');
                            }
                        });
                    } else {
                        $('#uxlabs-results').html(response.html);
                    }
                    self.currentPage++;
                    self.maxPages = response.max_pages;
                    $('#uxlabs-load-more-container').toggle(self.currentPage <= self.maxPages);
                }).fail(function() {
                    console.log('Search request failed.');
                }).always(function() {
                    self.isLoading = false;
                });
            }

            function debouncedSearch() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function() {
                    var query = $('#uxlabs-search-input').val();
                    var post_type = $('#uxlabs-tabs .active').data('type');
                    performSearch(query, post_type);
                }, 300);
            }

            $(document).on('input', '#uxlabs-search-input', debouncedSearch);

            $(document).on('click', '#uxlabs-tabs a', function(e) {
                e.preventDefault();
                $('#uxlabs-tabs a').removeClass('active');
                $(this).addClass('active');
                debouncedSearch();
            });

            $(document).on('click', '#uxlabs-load-more', function(e) {
                e.preventDefault();
                var query = $('#uxlabs-search-input').val();
                var post_type = $('#uxlabs-tabs .active').data('type');
                performSearch(query, post_type, true);
            });

            $(document).on('click', '#search-all-button, #only-posts-button', function(e) {
                e.preventDefault();
                var type = $(this).attr('id') === 'search-all-button' ? 'all' : 'post';
                $('#uxlabs-tabs a[data-type="' + type + '"]').trigger('click');
            });

            // Add a new button for Pages if needed
            $(document).on('click', '#only-pages-button', function(e) {
                e.preventDefault();
                $('#uxlabs-tabs a[data-type="page"]').trigger('click');
            });
        },

        setupPreferences: function() {
            var self = this;

            function updatePreference(key, value) {
                return $.post(ajaxurl, {
                    action: 'uxlabs_save_preferences',
                    key: key,
                    value: value,
                    nonce: uxlabsAjax.nonce
                }).done(function(response) {
                    if (response.success) {
                        self.preferences[key] = value;
                        self.applyPreference(key, value);
                    }
                });
            }

            function getPreferences() {
                return $.post(ajaxurl, {action: 'uxlabs_get_preferences'})
                    .done(function(response) {
                        if (response.success) {
                            self.preferences = response.data.user_preferences;
                            self.genericSettings = response.data.generic_settings;
                            self.updatePreferenceUI();
                            self.applyAllPreferences();
                        }
                    });
            }

            // Initial Abruf der Präferenzen
            getPreferences();

            // Event-Listener für Sidebar-Toggle
            $(document).on('click', '#uxlabs-toggle-sidebar', function() {
                getPreferences();
            });

            // Event-Listener für Präferenz-Änderungen
            $(document).on('change', '.uxlabs-preference-checkbox', function() {
                var $this = $(this);
                var key = $this.data('preference');
                var value = $this.is(':checked') ? '1' : '0';
                updatePreference(key, value);
            });

            self.updatePreferenceUI();
        },

        updatePreferenceUI: function() {
            var self = this;
            $('.uxlabs-preference-checkbox').each(function() {
                var $checkbox = $(this);
                var key = $checkbox.data('preference');
                if (self.genericSettings[key] === '1') {
                    $checkbox.prop('checked', true).prop('disabled', true);
                    $checkbox.closest('li').addClass('uxlabs-globally-enabled');
                } else {
                    $checkbox.prop('checked', self.preferences[key] === '1');
                    $checkbox.prop('disabled', false);
                    $checkbox.closest('li').removeClass('uxlabs-globally-enabled');
                }
            });
        },

        applyAllPreferences: function() {
            var self = this;
            $.each(this.genericSettings, function(key, value) {
                if (value === '1' || self.preferences[key] === '1') {
                    self.applyPreference(key, '1');
                }
            });
        },

        applyPreference: function(key, value) {
            switch(key) {
                case 'dark_mode':
                    $('body').toggleClass('uxlabs-dark-mode', value === '1');
                    break;
                case 'hide_menu_icons':
                    $('#adminmenu').toggleClass('uxlabs-hide-menu-icons', value === '1');
                    // Entfernen Sie den inline-style, da wir jetzt eine separate CSS-Datei verwenden
                    if (value === '1') {
                        $('#uxlabs-hide-menu-icons-style').remove();
                    }
                    break;
                case 'hide_admin_toolbar':
                    $('body').toggleClass('uxlabs-hide-admin-toolbar', value === '1');
                    break;
                // Add more cases as needed
            }
        }
    };

    $(document).ready(function() {
        UXLABS.Sidebar.init();
    });

})(jQuery);
