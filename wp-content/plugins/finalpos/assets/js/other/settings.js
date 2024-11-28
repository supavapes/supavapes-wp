jQuery(document).ready(function($) {
    // Tab switching functionality
    function handleTabClick(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        $('.final-settings-sidebar a').removeClass('active').filter(this).addClass('active');
        $('.final-tab-content').removeClass('active').filter(target).addClass('active');
    }

    $('.final-settings-sidebar a').on('click', handleTabClick).first().click();

    // Toggle visibility of sync icons
    $('.toggle-control input[type="checkbox"]').on('change', function() {
        $(this).closest('.toggle-item').find('.sync-icon').toggleClass('hidden', !this.checked);
    });

    // Sync functionality
    $('.sync-button').on('click', function() {
        var syncType = $(this).data('sync-type');
        var $syncIcon = $(this).addClass('rotating');

        $.ajax({
            url: finalWizardNonce.ajaxurl,
            type: 'POST',
            data: {
                action: 'final_sync_request',
                nonce: finalWizardNonce.nonce,
                sync_type: syncType
            },
            success: function(response) {
                if (response.success) {
                    console.log('Sync initiated for ' + syncType);
                    alert('Sync initiated for ' + syncType + '.');
                } else {
                    console.error('Error:', response.data);
                    alert('Error: ' + response.data);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error:', textStatus, errorThrown);
                alert('An error occurred. Please check the console for more details.');
            },
            complete: function() {
                $syncIcon.removeClass('rotating');
            }
        });
    });

    if (finalWizardData.woocommerceActive) {
        // WooCommerce-specific functionality
        $('.sync-button').on('click', function() {
            // ... (sync button functionality)
        });

        // ... (other WooCommerce-related code)
    }
});

document.addEventListener('DOMContentLoaded', function() {
    // Tab switching functionality
    var tabs = document.querySelectorAll('.final-settings-sidebar a');
    var tabContents = document.querySelectorAll('.final-tab-content');

    tabs.forEach(function(tab) {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            var targetId = this.getAttribute('href').substring(1);
            tabContents.forEach(content => content.style.display = 'none');
            document.getElementById(targetId).style.display = 'block';
        });
    });

    // Configure link click handlers
    document.querySelectorAll('.configure-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            var popupId = this.getAttribute('data-popup') + 'Popup';
            var popup = document.getElementById(popupId);
            if (popup) popup.style.display = 'block';
        });
    });

    // Close popup handlers
    document.querySelectorAll('.close-popup').forEach(function(button) {
        button.addEventListener('click', function() {
            var popup = this.closest('.setup-popup');
            if (popup) popup.style.display = 'none';
        });
    });

    // Close popup when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('setup-popup')) {
            event.target.style.display = 'none';
        }
    });

    // Category search functionality
    var categorySearch = document.getElementById('categorySearch');
    var categoryItems = document.querySelectorAll('.category-item');

    if (categorySearch) {
        categorySearch.addEventListener('input', function() {
            var searchTerm = this.value.toLowerCase();
            categoryItems.forEach(function(item) {
                item.style.display = item.querySelector('.category-name').textContent.toLowerCase().includes(searchTerm) ? '' : 'none';
            });
        });
    }

    // "All categories" checkbox functionality
    var allCategoriesCheckbox = document.getElementById('allCategories');
    var categoryCheckboxes = document.querySelectorAll('.category-item input[type="checkbox"]:not(#allCategories)');

    if (allCategoriesCheckbox) {
        allCategoriesCheckbox.addEventListener('change', function() {
            categoryCheckboxes.forEach(checkbox => checkbox.checked = this.checked);
        });
    }

    categoryCheckboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            allCategoriesCheckbox.checked = Array.from(categoryCheckboxes).every(cb => cb.checked);
        });
    });

    // Save categories button functionality
    var saveButton = document.querySelector('.save-categories');
    if (saveButton) {
        saveButton.addEventListener('click', function() {
            var selectedCategories = Array.from(categoryCheckboxes).filter(checkbox => checkbox.checked).map(checkbox => parseInt(checkbox.value, 10));
            console.log('Selected categories:', selectedCategories);
            saveWizardOption('final_pos_category_sync', selectedCategories);
            document.getElementById('productsPopup').style.display = 'none';
        });
    }

    // Save timeframe button functionality
    var saveTimeframeButton = document.querySelector('.save-timeframe');
    if (saveTimeframeButton) {
        saveTimeframeButton.addEventListener('click', function() {
            var selectedTimeframe = document.querySelector('input[name="order_timeframe"]:checked').value;
            saveWizardOption('final_pos_order_timeframe', selectedTimeframe);
            saveWizardOption('final_pos_order_timeframe_start', calculateStartDate(parseInt(selectedTimeframe, 10)));
            document.getElementById('ordersPopup').style.display = 'none';
        });
    }

    // Save sync toggles
    document.querySelectorAll('#products, #orders, #customers').forEach(function(toggle) {
        toggle.addEventListener('change', function() {
            var syncStatus = {
                products: document.getElementById('products').checked,
                orders: document.getElementById('orders').checked,
                customers: document.getElementById('customers').checked
            };
            saveWizardOption('final_pos_sync_status', syncStatus);
        });
    });

    // Highlight selected timeframe
    document.querySelectorAll('.timeframe-item input[type="radio"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.timeframe-item input[type="radio"]').forEach(item => item.parentElement.classList.remove('selected'));
            this.parentElement.classList.add('selected');
        });
    });

    // Save UI and Advanced settings
    var uiAdvancedToggles = {
        ui: document.querySelectorAll('#tab2 input[type="checkbox"]'),
        advanced: document.querySelectorAll('#tab3 input[type="checkbox"]')
    };

    function getTabIdFromToggle(toggle) {
        return toggle.closest('#tab2') ? 'ui' : toggle.closest('#tab3') ? 'advanced' : null;
    }

    function saveSettings(toggle) {
        var tabId = getTabIdFromToggle(toggle);
        if (!tabId) return console.error('Unable to determine tab for toggle:', toggle);

        console.log('Saving settings for tab:', tabId);
        var settings = {};
        uiAdvancedToggles[tabId].forEach(t => settings[t.id] = t.checked);

        fetch(finalWizardNonce.ajaxurl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                'action': 'save_ui_advanced_settings',
                'settings': JSON.stringify(settings),
                'tab': tabId,
                'nonce': finalWizardNonce.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Server response:', data.data);
                savedSettings[tabId] = data.data.settings;
            } else {
                console.error('Failed to save ' + tabId + ' settings:', data.data);
            }
        })
        .catch(console.error);
    }

    // Add event listeners for UI and Advanced toggles
    Object.keys(uiAdvancedToggles).forEach(tabId => {
        uiAdvancedToggles[tabId].forEach(toggle => {
            toggle.addEventListener('change', function() {
                saveSettings(this);
            });
        });
    });

    // Apply saved settings
    if (typeof savedSettings !== 'undefined') {
        Object.keys(savedSettings).forEach(tabId => {
            Object.keys(savedSettings[tabId]).forEach(key => {
                var toggle = document.getElementById(key);
                if (toggle) toggle.checked = savedSettings[tabId][key] === 1;
            });
        });

        // Set the correct UI Choice radio button
        if (savedSettings.ui && savedSettings.ui.ui_choice) {
            var uiChoiceRadio = document.querySelector('input[name="ui_choice"][value="' + savedSettings.ui.ui_choice + '"]');
            if (uiChoiceRadio) uiChoiceRadio.checked = true;
        }
    }

    // UI Choice radio buttons
    var uiChoiceRadios = document.querySelectorAll('input[name="ui_choice"]');
    var uiSettingsToggles = document.querySelectorAll('#tab2 .toggle-item');

    function updateUISettingsVisibility() {
        uiSettingsToggles.forEach(toggle => toggle.style.display = document.querySelector('input[name="ui_choice"]:checked').value === 'classic' ? 'none' : 'flex');
    }

    updateUISettingsVisibility();

    uiChoiceRadios.forEach(function(radio) {
        radio.addEventListener('change', function() {
            saveWizardOption('final_pos_ui_choice', this.value);
            updateUISettingsVisibility();
        });
    });

    // UI Pro color pickers
    var colorPickers = document.querySelectorAll('#tab4 input[type="color"]');
    var saveUiProButton = document.getElementById('save-ui-pro-settings');

    colorPickers.forEach(function(picker) {
        picker.addEventListener('change', function() {
            updateColorPreview(this);
        });
    });

    function updateColorPreview(picker) {
        var preview = picker.nextElementSibling;
        if (preview && preview.classList.contains('color-preview')) {
            preview.style.backgroundColor = picker.value;
        }
    }

    if (saveUiProButton) {
        saveUiProButton.addEventListener('click', function() {
            var colors = {};
            colorPickers.forEach(function(picker) {
                colors[picker.id] = picker.value;
            });
            saveUiProSettings(colors);
        });
    }

    function saveUiProSettings(colors) {
        fetch(finalWizardNonce.ajaxurl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                'action': 'save_ui_pro_settings',
                'colors': JSON.stringify(colors),
                'nonce': finalWizardNonce.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('UI Pro settings saved successfully');
                alert('UI Pro settings saved successfully');
            } else {
                console.error('Failed to save UI Pro settings:', data.data);
                alert('Failed to save UI Pro settings');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while saving UI Pro settings');
        });
    }

    // Initialize color preview on page load
    colorPickers.forEach(function(picker) {
        updateColorPreview(picker);
        picker.addEventListener('input', function() {
            updateColorPreview(this);
        });
    });

    var resetUiProButton = document.getElementById('reset-ui-pro-settings');

    if (resetUiProButton) {
        resetUiProButton.addEventListener('click', function() {
            if (confirm('Are you sure you want to reset all colors to their default values?')) {
                resetUiProSettings();
            }
        });
    }

    function resetUiProSettings() {
        fetch(finalWizardNonce.ajaxurl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                'action': 'reset_ui_pro_settings',
                'nonce': finalWizardNonce.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('UI Pro settings reset successfully');
                alert('UI Pro settings reset successfully');
                updateColorPickers(data.data.default_colors);
            } else {
                console.error('Failed to reset UI Pro settings:', data.data);
                alert('Failed to reset UI Pro settings');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while resetting UI Pro settings');
        });
    }

    function updateColorPickers(colors) {
        for (var id in colors) {
            var picker = document.getElementById(id);
            if (picker) {
                picker.value = colors[id];
                updateColorPreview(picker);
            }
        }
    }

    // Logo upload functionality
    var uploadLogoButton = document.getElementById('upload_logo_button');
    var removeLogoButton = document.getElementById('remove_logo_button');
    var logoUrlInput = document.getElementById('logo_url');
    var logoPreview = document.querySelector('.logo-preview');

    if (uploadLogoButton) {
        uploadLogoButton.addEventListener('click', function(e) {
            e.preventDefault();
            if (window.logoMediaFrame) {
                window.logoMediaFrame.open();
                return;
            }

            window.logoMediaFrame = wp.media({
                title: 'Select or Upload Logo',
                button: { text: 'Use this logo' },
                multiple: false
            });

            window.logoMediaFrame.on('select', function() {
                var attachment = window.logoMediaFrame.state().get('selection').first().toJSON();
                logoUrlInput.value = attachment.url;
                updateLogoPreview(attachment.url);
                saveLogoUrl(attachment.url);
            });

            window.logoMediaFrame.open();
        });
    }

    if (removeLogoButton) {
        removeLogoButton.addEventListener('click', function(e) {
            e.preventDefault();
            logoUrlInput.value = '';
            updateLogoPreview('');
            saveLogoUrl('');
        });
    }

    if (logoUrlInput) {
        logoUrlInput.addEventListener('change', function() {
            updateLogoPreview(this.value);
            saveLogoUrl(this.value);
        });
    }

    function updateLogoPreview(url) {
        logoPreview.innerHTML = url ? '<img src="' + url + '" alt="Logo Preview">' : '<p>No logo uploaded</p>';
    }

    function saveLogoUrl(url) {
        fetch(finalWizardNonce.ajaxurl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                'action': 'save_logo_url',
                'logo_url': url,
                'nonce': finalWizardNonce.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Logo URL saved successfully');
            } else {
                console.error('Failed to save Logo URL:', data.data);
            }
        })
        .catch(console.error);
    }

    // UI Pro settings reveal functionality
    window.revealUiProSettings = function(code) {
        if (code === 'showUiPro') {
            var uiProTab = document.querySelector('.final-settings-sidebar a[href="#tab4"]');
            var uiProContent = document.getElementById('tab4');
            if (uiProTab && uiProContent) {
                uiProTab.style.display = 'block';
                uiProContent.classList.remove('hidden-pro-settings');
                console.log('UI Pro settings revealed. You can now access them from the sidebar.');
            } else {
                console.log('UI Pro settings elements not found.');
            }
        } else {
            console.log('Invalid code. UI Pro settings remain hidden.');
        }
    };

    console.log('To reveal UI Pro settings, use the command: revealUiProSettings("showUiPro")');

    // Set initial selected categories on page load
    function setInitialSelectedCategories() {
        if (typeof finalWizardData !== 'undefined' && finalWizardData.selectedCategories) {
            var selectedCategories = finalWizardData.selectedCategories;
            document.querySelectorAll('.category-item input[type="checkbox"]:not(#allCategories)').forEach(function(checkbox) {
                checkbox.checked = selectedCategories.includes(parseInt(checkbox.value, 10));
            });
            var allCategoriesCheckbox = document.getElementById('allCategories');
            if (allCategoriesCheckbox) {
                allCategoriesCheckbox.checked = document.querySelectorAll('.category-item input[type="checkbox"]:not(#allCategories)').length === selectedCategories.length;
            }
        }
    }

    setInitialSelectedCategories();

    // Reset wizard status button functionality
    var resetWizardStatusButton = document.getElementById('reset-wizard-status');
    if (resetWizardStatusButton) {
        resetWizardStatusButton.addEventListener('click', function() {
            fetch(finalWizardNonce.ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    'action': 'reset_wizard_status',
                    'nonce': finalWizardNonce.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Wizard status reset successfully');
                    alert('Wizard status reset successfully');
                } else {
                    console.error('Failed to reset wizard status:', data.data);
                    alert('Failed to reset wizard status');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while resetting wizard status');
            });
        });
    }
});

// Calculate start date
function calculateStartDate(days) {
    var date = new Date();
    date.setDate(date.getDate() - days);
    return date.toISOString();
}

// Update saveWizardOption function
function saveWizardOption(optionName, optionValue) {
    console.log('Saving option:', optionName, optionValue);
    
    const formData = new URLSearchParams();
    formData.append('action', 'save_wizard_option');
    formData.append('option_name', optionName);
    formData.append('option_value', JSON.stringify(optionValue));
    formData.append('nonce', finalWizardNonce.nonce);

    fetch(finalWizardNonce.ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Option saved successfully:', data);
        } else {
            console.error('Failed to save option:', data.data);
            throw new Error(data.data);
        }
    })
    .catch(error => {
        console.error('Error saving option:', error);
        alert('Error saving option: ' + error.message);
    });
}
