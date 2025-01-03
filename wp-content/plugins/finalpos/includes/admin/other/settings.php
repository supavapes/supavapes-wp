<?php

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}
function final_add_settings_page()
{
    add_submenu_page(
        'final-admin-dashboard',
        'Final Settings',
        'Settings',
        'manage_options',
        'final-plugin-settings',
        'final_render_settings_page'
    );
}

$wizard_status = get_option('final_wizard_status',  '');
if ($wizard_status === 'completed_sync' && get_option('final_pos_consumer_key')) {
    add_action('admin_menu', 'final_add_settings_page');
}

require_once plugin_dir_path(__FILE__) . '../../api/sync.php';

function final_render_settings_page()
{
    // Load saved options
    $ui_choice = get_option('final_pos_ui_choice', 'modern');

    // Load UI and Advanced settings
    $ui_settings = [
        'dark_mode',
        'wp_admin_menu',
        'hide_menu_icons',
        'hide_admin_toolbar',
        'show_notifications'
    ];

    $advanced_settings = [
        'wordpress_optimizations',
        'gutenberg_disable',
        'disable_comments',
        'disable_feeds',
        'disable_xmlrpc',
        'final_debug_log'
    ];

    // Check if WooCommerce is active
    $woocommerce_active = final_is_woocommerce_active();

    // Add WooCommerce optimizations only if WooCommerce is active
    if ($woocommerce_active) {
        $advanced_settings[] = 'woocommerce_optimizations';
    }

    $saved_settings = [
        'ui' => get_option('final_pos_ui_settings', []),
        'advanced' => get_option('final_pos_advanced_settings', [])
    ];

    // Ensure default values are set
    foreach (array_merge($ui_settings, $advanced_settings) as $setting) {
        if (!isset($saved_settings['ui'][$setting])) {
            $saved_settings['ui'][$setting] = 0;
        }
    }

    if ($woocommerce_active) {
        // Load WooCommerce-specific options
        $order_timeframe = get_option('final_pos_order_timeframe', 30);
        $sync_status = get_option('final_pos_sync_status', [
            'products' => true,
            'orders' => true,
            'customers' => true
        ]);

        // Ensure $sync_status is an array
        if (!is_array($sync_status)) {
            $sync_status = [
                'products' => true,
                'orders' => true,
                'customers' => true
            ];
        }

        // Load selected categories
        $selected_categories = final_get_selected_categories();
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Final Settings', 'final-pos'); ?></h1>
        <div class="final-settings-container">
            <div class="final-settings-sidebar">
                <ul>
                    <?php if ($woocommerce_active): ?>
                        <li><a href="#tab1"><?php esc_html_e('Sync Settings', 'final-pos'); ?></a></li>
                    <?php endif; ?>
                    <!-- <li><a href="#tab2"><?php esc_html_e('UI Settings', 'final-pos'); ?></a></li> -->
                    <li><a href="#tab3"><?php esc_html_e('Advanced', 'final-pos'); ?></a></li>
                    <li><a href="#tab4"><?php esc_html_e('UI Pro', 'final-pos'); ?></a></li>
                </ul>
            </div>
            <div class="final-settings-content">
                <?php if ($woocommerce_active): ?>
                    <div id="tab1" class="final-tab-content">
                        <h2><?php esc_html_e('Sync Settings', 'final-pos'); ?></h2>
                        <p><?php esc_html_e('Choose the types you want to sync from your WooCommerce store to Final POS:', 'final-pos'); ?>
                        </p>
                        <hr>
                        <div class="toggle-group">
                            <?php foreach (['products', 'orders', 'customers'] as $type): ?>
                                <div class="toggle-item">
                                    <div class="toggle-label">
                                        <div class="icon-box">
                                            <?php include(plugin_dir_path(__FILE__) . '../../../assets/img/icons/' . esc_attr($type) . '.svg'); ?>
                                        </div>
                                        <div>
                                            <label for="<?php echo esc_attr($type); ?>"
                                                class="toggle-labeltext"><?php echo esc_html(ucfirst($type)); ?></label>
                                            <?php
                                            /* translators: %s: sync type (products, orders, or customers) */
                                            $sync_description = sprintf(__('Syncing all %s.', 'final-pos'), esc_html($type));
                                            ?>
                                            <p class="toggle-description"><?php echo esc_html($sync_description); ?></p>
                                            <?php if ($type !== 'customers'): ?>
                                                <a href="#" class="configure-link"
                                                    data-popup="<?php echo esc_attr($type); ?>"><?php esc_html_e('Configure your sync terms', 'final-pos'); ?></a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="toggle-control">
                                        <label class="switch">
                                            <input type="checkbox" id="<?php echo esc_attr($type); ?>" <?php checked(isset($sync_status[$type]) && $sync_status[$type]); ?>>
                                            <span class="slider round"></span>
                                        </label>
                                        <div
                                            class="sync-icon <?php echo (isset($sync_status[$type]) && $sync_status[$type]) ? '' : 'hidden'; ?>">
                                            <span class="material-symbols-outlined sync-button"
                                                data-sync-type="<?php echo esc_attr($type); ?>">sync</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <div id="tab2" class="final-tab-content">
                    <h2><?php esc_html_e('UI Settings', 'final-pos'); ?></h2>
                    <p><?php esc_html_e('Select your preferred style. Precedence over individual user preferences:', 'final-pos'); ?>
                    </p>
                    <hr>
                    <div class="ui-choice-container">
                        <div class="radio-group">
                            <?php foreach (['modern' => 'Modern UI', 'classic' => 'Classic UI'] as $value => $label): ?>
                                <label>
                                    <input type="radio" name="ui_choice" value="<?php echo esc_attr($value); ?>" <?php checked($ui_choice, $value); ?>>
                                    <?php echo esc_html($label); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <hr>
                    <div class="toggle-group">
                        <?php
                        $toggles = [
                            'dark_mode' => ['icon' => 'dark_mode', 'label' => 'Enable Dark Mode', 'description' => 'Enable dark mode in the Backend.'],
                            'wp_admin_menu' => ['icon' => 'menu', 'label' => 'WP Admin Menu', 'description' => 'Show the regular WordPress Menu.'],
                            'hide_menu_icons' => ['icon' => 'visibility_off', 'label' => 'Hide Menu Icons', 'description' => 'Hide the icons in the Admin Menu.'],
                            'hide_admin_toolbar' => ['icon' => 'toolbar', 'label' => 'Hide Admin Toolbar', 'description' => 'Hide the dynamic Toolbar elements.'],
                            'show_notifications' => ['icon' => 'notifications', 'label' => 'Show Notifications', 'description' => 'Show all notifications in the Backend.']
                        ];
                        foreach ($toggles as $id => $toggle): ?>
                            <div class="toggle-item">
                                <div class="toggle-label">
                                    <span
                                        class="material-symbols-outlined uxlabs-boxed-icon"><?php echo esc_html($toggle['icon']); ?></span>
                                    <div>
                                        <label for="<?php echo esc_attr($id); ?>"
                                            class="toggle-labeltext"><?php echo esc_html($toggle['label']); ?></label>
                                        <p class="toggle-description"><?php echo esc_html($toggle['description']); ?></p>
                                    </div>
                                </div>
                                <div class="toggle-control">
                                    <label class="switch">
                                        <input type="checkbox" id="<?php echo esc_attr($id); ?>" <?php checked(isset($saved_settings['ui'][$id]) && $saved_settings['ui'][$id]); ?>>
                                        <span class="slider round"></span>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div id="tab3" class="final-tab-content">
                    <h2><?php esc_html_e('Advanced Settings', 'final-pos'); ?></h2>
                    <p><?php esc_html_e('Configure advanced performance, optimization and expert settings:', 'final-pos'); ?>
                    </p>
                    <hr>
                    <div class="toggle-group">
                        <?php
                        $advanced_toggles = [
                            'wordpress_optimizations' => ['icon' => 'public', 'label' => 'WordPress Optimizations', 'description' => 'Optimize various WordPress core features and behaviors.'],
                            'gutenberg_disable' => ['icon' => 'dashboard_customize', 'label' => 'Disable Gutenberg', 'description' => 'Disable Gutenberg editor and related features.'],
                            'disable_comments' => ['icon' => 'comment', 'label' => 'Disable Comments', 'description' => 'Completely disable comments functionality.'],
                            'disable_feeds' => ['icon' => 'rss_feed', 'label' => 'Disable Feeds', 'description' => 'Disable RSS, Atom, and other feed functionalities.'],
                            'disable_xmlrpc' => ['icon' => 'api', 'label' => 'Disable XML-RPC', 'description' => 'Disable XML-RPC functionality for improved security.'],
                            'final_debug_log' => ['icon' => 'bug_report', 'label' => 'Activate Final Debug Log', 'description' => 'Enable debug logging for troubleshooting POS Features.']
                        ];

                        // Add WooCommerce optimizations toggle at the beginning if WooCommerce is active
                        if ($woocommerce_active) {
                            $advanced_toggles = array_merge(
                                ['woocommerce_optimizations' => ['icon' => 'shopping_cart', 'label' => 'WooCommerce Optimizations', 'description' => 'Optimize WooCommerce features and admin interface.']],
                                $advanced_toggles
                            );
                        }

                        foreach ($advanced_toggles as $id => $toggle): ?>
                            <div class="toggle-item">
                                <div class="toggle-label">
                                    <span
                                        class="material-symbols-outlined uxlabs-boxed-icon"><?php echo esc_html($toggle['icon']); ?></span>
                                    <div>
                                        <label for="<?php echo esc_attr($id); ?>"
                                            class="toggle-labeltext"><?php echo esc_html($toggle['label']); ?></label>
                                        <p class="toggle-description"><?php echo esc_html($toggle['description']); ?></p>
                                    </div>
                                </div>
                                <div class="toggle-control">
                                    <label class="switch">
                                        <input type="checkbox" id="<?php echo esc_attr($id); ?>" <?php checked(isset($saved_settings['advanced'][$id]) && $saved_settings['advanced'][$id]); ?>>
                                        <span class="slider round"></span>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button id="reset-wizard-status"
                        class="button button-secondary"><?php esc_html_e('Reset Wizard Status', 'final-pos'); ?></button>
                </div>
                <div id="tab4" class="final-tab-content hidden-pro-settings">
                    <h2><?php esc_html_e('UI Pro Settings', 'final-pos'); ?></h2>
                    <p><?php esc_html_e('Customize the main colors of your admin interface and upload your logo:', 'final-pos'); ?>
                    </p>
                    <hr>
                    <h3><?php esc_html_e('Logo Upload', 'final-pos'); ?></h3>
                    <div class="logo-upload-container">
                        <div class="logo-preview">
                            <?php
                            $logo_url = esc_url(get_option('final_pos_logo_url', ''));
                            echo !empty($logo_url) ? '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr__('Logo Preview', 'final-pos') . '">' : '<p>' . esc_html__('No logo uploaded', 'final-pos') . '</p>';
                            ?>
                        </div>
                        <div class="logo-upload-controls">
                            <input type="text" id="logo_url" name="logo_url" value="<?php echo esc_attr($logo_url); ?>"
                                placeholder="<?php esc_attr_e('Enter logo URL or use upload button', 'final-pos'); ?>">
                            <button type="button" id="upload_logo_button"
                                class="button"><?php esc_html_e('Upload Logo', 'final-pos'); ?></button>
                            <button type="button" id="remove_logo_button"
                                class="button"><?php esc_html_e('Remove Logo', 'final-pos'); ?></button>
                        </div>
                    </div>
                    <hr>
                    <h3><?php esc_html_e('Color Settings', 'final-pos'); ?></h3>
                    <div class="color-picker-group">
                        <?php
                        $color_settings = [
                            'primary_color' => ['label' => 'Primary Color', 'default' => '#3D4C66'],
                            'base_color' => ['label' => 'Base Color', 'default' => '#2797e8'],
                            'secondary_color' => ['label' => 'Secondary Color', 'default' => '#EAF5FF'],
                            'text_color' => ['label' => 'Text Color', 'default' => '#4d4d4d']
                        ];
                        foreach ($color_settings as $key => $setting): ?>
                            <div class="color-picker-item">
                                <label for="<?php echo esc_attr($key); ?>"><?php echo esc_html($setting['label']); ?></label>
                                <div class="color-picker-wrapper">
                                    <input type="color" id="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>"
                                        value="<?php echo esc_attr(get_option('final_pos_' . $key, $setting['default'])); ?>">
                                    <div class="color-preview"
                                        style="background-color: <?php echo esc_attr(get_option('final_pos_' . $key, $setting['default'])); ?>;">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <h3><?php esc_html_e('Dark Mode', 'final-pos'); ?></h3>
                    <div class="color-picker-group">
                        <?php
                        $dark_color_settings = [
                            'dark_primary_color' => ['label' => 'Primary Color', 'default' => '#54698e'],
                            'dark_base_color' => ['label' => 'Base Color', 'default' => '#2797e8'],
                            'dark_secondary_color' => ['label' => 'Secondary Color', 'default' => '#b6cee789'],
                            'dark_text_color' => ['label' => 'Text Color', 'default' => '#f7f7f7']
                        ];
                        foreach ($dark_color_settings as $key => $setting): ?>
                            <div class="color-picker-item">
                                <label for="<?php echo esc_attr($key); ?>"><?php echo esc_html($setting['label']); ?></label>
                                <div class="color-picker-wrapper">
                                    <input type="color" id="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>"
                                        value="<?php echo esc_attr(get_option('final_pos_' . $key, $setting['default'])); ?>">
                                    <div class="color-preview"
                                        style="background-color: <?php echo esc_attr(get_option('final_pos_' . $key, $setting['default'])); ?>;">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div>
                        <button type="button" id="save-ui-pro-settings"
                            class="button button-primary"><?php esc_html_e('Save UI Pro Settings', 'final-pos'); ?></button>
                        <button type="button" id="reset-ui-pro-settings"
                            class="button"><?php esc_html_e('Reset to Default Colors', 'final-pos'); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($woocommerce_active): ?>
        <!-- Popups -->
        <div id="productsPopup" class="setup-popup">
            <div class="setup-popup-content">
                <span class="close-popup">&times;</span>
                <h2><?php esc_html_e('Product Categories', 'final-pos'); ?></h2>
                <p><?php esc_html_e('We will sync all your product categories, or you can choose specific categories to sync from your WooCommerce store to Final POS.', 'final-pos'); ?>
                </p>

                <div class="category-list-container">
                    <h3><?php esc_html_e('Category list', 'final-pos'); ?></h3>
                    <div class="search-container">
                        <input type="text" id="categorySearch"
                            placeholder="<?php esc_attr_e('Search for category', 'final-pos'); ?>">
                        <span class="material-symbols-outlined search-icon">search</span>
                    </div>
                    <div class="category-list">
                        <label class="category-item">
                            <input type="checkbox" id="allCategories" checked>
                            <span class="category-name"><?php esc_html_e('All categories', 'final-pos'); ?></span>
                        </label>
                        <?php
                        function display_category_hierarchical($category, $depth = 0)
                        {
                            $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $depth);
                            $sync_status = get_term_meta($category->term_id, 'final_sync', true);
                            $checked = $sync_status ? 'checked' : '';
                            echo '<label class="category-item" style="padding-left: ' . esc_attr($depth * 20) . 'px;">';
                            echo esc_html($indent) . '<input type="checkbox" name="product_category[]" value="' . esc_attr($category->term_id) . '" data-parent-id="' . esc_attr($category->parent) . '" ' . esc_attr($checked) . '>';
                            echo '<span class="category-name">' . esc_html($category->name) . '</span>';
                            echo '</label>';

                            $child_categories = get_terms(array(
                                'taxonomy' => 'product_cat',
                                'hide_empty' => false,
                                'parent' => $category->term_id
                            ));

                            if (!empty($child_categories)) {
                                foreach ($child_categories as $child_category) {
                                    display_category_hierarchical($child_category, $depth + 1);
                                }
                            }
                        }

                        $top_level_categories = get_terms(array(
                            'taxonomy' => 'product_cat',
                            'hide_empty' => false,
                            'parent' => 0
                        ));

                        foreach ($top_level_categories as $category) {
                            display_category_hierarchical($category, 0);
                        }
                        ?>
                    </div>
                </div>
                <button type="button" class="save-categories"><?php esc_html_e('Save', 'final-pos'); ?></button>
            </div>
        </div>

        <div id="ordersPopup" class="setup-popup">
            <div class="setup-popup-content">
                <span class="close-popup">&times;</span>
                <h2><?php esc_html_e('Order timeframe', 'final-pos'); ?></h2>
                <p><?php esc_html_e('We will sync online orders from your WooCommerce store to Final POS for the last 30 days, or you can select a specific time period:', 'final-pos'); ?>
                </p>
                <p class="infotext">
                    <?php esc_html_e('Please note that extending the timeframe might affect your device\'s performance.', 'final-pos'); ?>
                </p>

                <div class="timeframe-list">
                    <?php
                    $timeframes = array(7, 30, 60, 90, 365, 730);
                    foreach ($timeframes as $days) {
                        $checked = $order_timeframe == $days ? 'checked' : '';
                        echo '<label class="timeframe-item">';
                        echo '<input type="radio" name="order_timeframe" value="' . esc_attr($days) . '" ' . esc_attr($checked) . '>';
                        echo '<span class="timeframe-name">' . esc_html('Last ' . ($days == 365 ? '1 year' : ($days == 730 ? '2 years' : $days . ' days'))) . '</span>';
                        echo '</label>';
                    }
                    ?>
                </div>
                <button type="button" class="save-timeframe"><?php esc_html_e('Save', 'final-pos'); ?></button>
            </div>
        </div>
    <?php endif; ?>

    <script>
        var savedSettings = <?php echo wp_json_encode($saved_settings); ?>;
        <?php if ($woocommerce_active): ?>
            var selectedCategories = <?php echo wp_json_encode($selected_categories); ?>;
        <?php endif; ?>
        var woocommerceActive = <?php echo wp_json_encode($woocommerce_active); ?>;
    </script>
    <?php
}

// Fügen Sie diese Funktion hinzu oder aktualisieren Sie sie, falls sie bereits existiert
function final_save_wizard_option()
{
    check_ajax_referer('final_wizard_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }

    $option_name = isset($_POST['option_name']) ? sanitize_text_field(wp_unslash($_POST['option_name'])) : '';

    // Sanitize the raw input value immediately
    $raw_value = isset($_POST['option_value']) ? sanitize_text_field(wp_unslash($_POST['option_value'])) : '';

    if (empty($option_name)) {
        wp_send_json_error('Option name is required');
        return;
    }

    // Decode JSON if the value is JSON
    $decoded_value = json_decode($raw_value, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        // Wenn es ein Array ist, sanitize jeden Wert rekursiv
        $option_value = final_sanitize_array_recursive($decoded_value);

        // Spezielle Behandlung für sync_status
        if ($option_name === 'final_pos_sync_status') {
            $option_value = array_merge(
                ['products' => false, 'orders' => false, 'customers' => false],
                array_intersect_key($option_value, array_flip(['products', 'orders', 'customers']))
            );
            array_walk($option_value, function (&$value) {
                $value = (bool) $value;
            });
        }
    } else {
        // Wenn es kein JSON ist, verwende den bereits sanitierten Wert
        $option_value = $raw_value;
    }

    $updated = update_option($option_name, $option_value);

    if ($updated) {
        wp_send_json_success([
            'message' => 'Option saved successfully',
            'value' => $option_value
        ]);
    } else {
        wp_send_json_error('Failed to update option');
    }
}
add_action('wp_ajax_save_wizard_option', 'final_save_wizard_option');

// Hilfsfunktion zum rekursiven Sanitizen von Arrays
function final_sanitize_array_recursive($array)
{
    if (!is_array($array)) {
        return sanitize_text_field($array);
    }

    foreach ($array as $key => &$value) {
        if (is_array($value)) {
            $value = final_sanitize_array_recursive($value);
        } else {
            // Behandle verschiedene Datentypen
            if (is_bool($value)) {
                $value = (bool) $value;
            } elseif (is_numeric($value)) {
                $value = is_float($value) ? (float) $value : (int) $value;
            } else {
                $value = sanitize_text_field($value);
            }
        }
    }

    return $array;
}

// Add this new helper function
function final_sanitize_option_value($value)
{
    // If the value is a JSON string, decode it first
    $decoded = json_decode($value, true);

    if (json_last_error() === JSON_ERROR_NONE) {
        // If it's an array, sanitize each element recursively
        if (is_array($decoded)) {
            return array_map(function ($item) {
                if (is_array($item)) {
                    return final_sanitize_option_value($item);
                }
                if (is_bool($item)) {
                    return (bool) $item;
                }
                if (is_int($item)) {
                    return intval($item);
                }
                if (is_float($item)) {
                    return floatval($item);
                }
                return sanitize_text_field($item);
            }, $decoded);
        }
        return $decoded;
    }

    // If it's not JSON, sanitize as regular text
    if (is_numeric($value)) {
        return is_float($value) ? floatval($value) : intval($value);
    }

    if (is_bool($value)) {
        return (bool) $value;
    }

    // Default to sanitizing as text
    return sanitize_text_field($value);
}

// Fügen Sie diese neue Funktion hinzu, um die Kategorien im Frontend zu laden
function final_get_selected_categories()
{
    $selected_categories = get_option('final_pos_category_sync', array());
    if (!is_array($selected_categories)) {
        $selected_categories = array();
    }
    return $selected_categories;
}

// Fügen Sie diese Funktion hinzu
function final_enqueue_admin_scripts($hook)
{
    if ('final-pos_page_final-plugin-settings' !== $hook) {
        return;
    }
    wp_enqueue_style('final-wizard-css', plugin_dir_url(__FILE__) . '../../../assets/css/finalwizard.css', [], '1.0'); // Added version parameter
    wp_enqueue_style('final-admin-css', plugin_dir_url(__FILE__) . '../../../assets/css/settings.css', [], '1.0'); // Added version parameter
    wp_enqueue_script('final-admin-js', plugin_dir_url(__FILE__) . '../../../assets/js/other/settings.js', array('jquery'), '1.0', true);

    // Add nonce for AJAX
    wp_localize_script('final-admin-js', 'finalWizardNonce', array(
        'nonce' => wp_create_nonce('final_wizard_nonce'),
        'ajaxurl' => admin_url('admin-ajax.php')
    ));

    // Enqueue WordPress Media Library
    wp_enqueue_media();

    $localize_data = array(
        'nonce' => wp_create_nonce('final_wizard_nonce'),
        'ajaxurl' => admin_url('admin-ajax.php'),
        'woocommerceActive' => final_is_woocommerce_active()
    );

    if (final_is_woocommerce_active()) {
        $localize_data['selectedCategories'] = final_get_selected_categories();
    }

    wp_localize_script('final-admin-js', 'finalWizardData', $localize_data);
}
add_action('admin_enqueue_scripts', 'final_enqueue_admin_scripts');

// Add this new function to handle the AJAX request
function final_handle_sync_request()
{
    check_ajax_referer('final_wizard_nonce', 'nonce');

    if (!current_user_can('manage_options') || !final_is_woocommerce_active()) {
        wp_send_json_error('Insufficient permissions or WooCommerce is not active');
    }

    $sync_type = isset($_POST['sync_type']) ? sanitize_text_field(wp_unslash($_POST['sync_type'])) : '';

    // Create an instance of Sync_Page
    $sync_page = new Sync_Page();

    ob_start(); // Start output buffering

    switch ($sync_type) {
        case 'products':
            $sync_page->handle_sync_products();
            break;
        case 'orders':
            $sync_page->handle_sync_orders();
            break;
        case 'customers':
            $sync_page->handle_sync_customers();
            break;
        case 'all':
            $sync_page->handle_sync_all();
            break;
        default:
            wp_send_json_error('Invalid sync type');
            return;
    }

    $output = ob_get_clean(); // Get the buffered output and clear the buffer

    wp_send_json_success(array('message' => 'Sync initiated', 'output' => $output));
}
add_action('wp_ajax_final_sync_request', 'final_handle_sync_request');

function final_save_ui_advanced_settings()
{
    check_ajax_referer('final_wizard_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        return wp_send_json_error('Insufficient permissions');
    }

    // Sanitize raw input immediately
    $raw_settings = isset($_POST['settings']) ? sanitize_text_field(wp_unslash($_POST['settings'])) : '';
    $settings = json_decode($raw_settings, true);
    $tab = isset($_POST['tab']) ? sanitize_text_field(wp_unslash($_POST['tab'])) : '';

    if (!is_array($settings) || empty($tab)) {
        return wp_send_json_error('Invalid settings data');
    }

    $option_name = $tab === 'ui' ? 'final_pos_ui_settings' : 'final_pos_advanced_settings';
    $current_settings = get_option($option_name, []);

    // Sanitize each setting
    foreach ($settings as $key => $value) {
        $sanitized_key = sanitize_key($key);
        $sanitized_value = sanitize_text_field($value);
        $current_settings[$sanitized_key] = (int) $sanitized_value;
    }

    $updated = update_option($option_name, $current_settings);

    wp_send_json_success([
        'message' => "$tab settings saved successfully",
        'settings' => $current_settings
    ]);
}
add_action('wp_ajax_save_ui_advanced_settings', 'final_save_ui_advanced_settings');

function final_save_ui_pro_settings()
{
    check_ajax_referer('final_wizard_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        return wp_send_json_error('Insufficient permissions');
    }

    // Sanitize raw input immediately
    $raw_colors = isset($_POST['colors']) ? sanitize_text_field(wp_unslash($_POST['colors'])) : '';
    $colors = json_decode($raw_colors, true);

    if (!is_array($colors)) {
        return wp_send_json_error('Invalid color data');
    }

    $color_options = [
        'primary_color' => 'final_pos_primary_color',
        'base_color' => 'final_pos_base_color',
        'secondary_color' => 'final_pos_secondary_color',
        'text_color' => 'final_pos_text_color',
        'dark_primary_color' => 'final_pos_dark_primary_color',
        'dark_base_color' => 'final_pos_dark_base_color',
        'dark_secondary_color' => 'final_pos_dark_secondary_color',
        'dark_text_color' => 'final_pos_dark_text_color'
    ];

    foreach ($color_options as $key => $option_name) {
        if (isset($colors[$key])) {
            $sanitized_color = sanitize_hex_color($colors[$key]);
            if ($sanitized_color) {
                update_option($option_name, $sanitized_color);
            }
        }
    }

    wp_send_json_success('UI Pro settings saved successfully');
}
add_action('wp_ajax_save_ui_pro_settings', 'final_save_ui_pro_settings');

function final_save_logo_url()
{
    check_ajax_referer('final_wizard_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        return wp_send_json_error('Insufficient permissions');
    }

    $logo_url = isset($_POST['logo_url']) ? esc_url_raw(wp_unslash($_POST['logo_url'])) : '';
    update_option('final_pos_logo_url', $logo_url);

    wp_send_json_success('Logo URL saved successfully');
}
add_action('wp_ajax_save_logo_url', 'final_save_logo_url');

function final_reset_wizard_status()
{
    check_ajax_referer('final_wizard_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        return wp_send_json_error('Insufficient permissions');
    }

    update_option('final_wizard_status', 'notdone');
    wp_send_json_success('Wizard status reset to notdone');
}
add_action('wp_ajax_reset_wizard_status', 'final_reset_wizard_status');















