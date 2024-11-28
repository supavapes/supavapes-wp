<?php


// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}
// Include common functions
require_once plugin_dir_path(__FILE__) . '../../common-functions.php';

// Enqueue Scripts and Styles
function enqueue_sidebar_scripts() {
    wp_enqueue_script('sidebar-js', plugins_url('../../../assets/js/ui/sidebar.js', __FILE__), ['jquery'], '1.0.0', true);
    wp_localize_script('sidebar-js', 'uxlabsAjax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('uxlabs_ajax_nonce')
    ]);
}
add_action('admin_enqueue_scripts', 'enqueue_sidebar_scripts');

// Save User Preferences
function uxlabs_save_preferences() {
    // Add nonce verification
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'uxlabs_ajax_nonce')) {
        wp_send_json_error(__('Invalid nonce', 'final-pos'));
    }

    if (isset($_POST['key'], $_POST['value'])) {
        $key = sanitize_text_field(wp_unslash($_POST['key']));
        $value = sanitize_text_field(wp_unslash($_POST['value']));
        $preferences = final_get_user_preferences();
        $preferences[$key] = $value;
        update_user_meta(get_current_user_id(), 'final_preferences', $preferences);
        wp_send_json_success(['key' => $key, 'value' => $value]);
    }
    wp_send_json_error();
}
add_action('wp_ajax_uxlabs_save_preferences', 'uxlabs_save_preferences');

// Get User Preferences and Generic Settings
function uxlabs_get_preferences() {
    $user_preferences = final_get_user_preferences();
    $generic_settings = get_option('final_pos_ui_settings', array());
    
    // Ensure generic settings are in the correct format
    $generic_settings = maybe_unserialize($generic_settings);
    
    // Convert boolean values to '0' or '1' strings for consistency
    foreach ($generic_settings as $key => $value) {
        $generic_settings[$key] = $value ? '1' : '0';
    }

    wp_send_json_success(array(
        'user_preferences' => $user_preferences,
        'generic_settings' => $generic_settings
    ));
}
add_action('wp_ajax_uxlabs_get_preferences', 'uxlabs_get_preferences');

// Apply User Preferences
function uxlabs_apply_preferences() {
    $user_preferences = final_get_user_preferences();
    $generic_settings = get_option('final_pos_ui_settings', array());
    
    // Ensure generic settings are in the correct format
    $generic_settings = maybe_unserialize($generic_settings);
    
    // Apply settings (generic settings take precedence)
    foreach ($generic_settings as $key => $value) {
        if ($value) {
            uxlabs_apply_setting($key);
        } elseif (!empty($user_preferences[$key])) {
            uxlabs_apply_setting($key);
        }
    }
}

function uxlabs_apply_setting($key) {
    switch ($key) {
        case 'dark_mode':
            echo '<script>document.body.classList.add("uxlabs-dark-mode");</script>';
            break;
        case 'hide_menu_icons':
            echo '<script>jQuery(document).ready(function($) { $("#adminmenu").addClass("uxlabs-hide-menu-icons"); });</script>';
            break;
        case 'hide_admin_toolbar':
            echo '<script>jQuery(document).ready(function($) { $("body").addClass("uxlabs-hide-admin-toolbar"); });</script>';
            break;
        // Add more cases as needed
    }
}

add_action('admin_footer', 'uxlabs_apply_preferences');

// Search Shortcode
function uxlabs_search_shortcode() {
    ob_start(); ?>
    <div id="uxlabs-search-container">
        <div class="uxlabs-header">
            <h2><?php esc_html_e('Quick Search', 'final-pos'); ?></h2>
            <div class="uxlabs-buttons">
                <a href="#" class="uxlabs-button" id="search-all-button">
                    <span class="material-symbols-outlined finalicons">tune</span>
                    <?php esc_html_e('Search Parameters (Soon)', 'final-pos'); ?>
                </a>
            </div>
        </div>
        <input type="text" id="uxlabs-search-input" placeholder="<?php esc_attr_e('Search by name or order #', 'final-pos'); ?>">
        <div id="uxlabs-tabs">
            <?php
            $tabs = [
                'all' => __('All', 'final-pos'),
                'post' => __('Posts', 'final-pos'),
                'page' => __('Pages', 'final-pos'),
                'product' => __('Products', 'final-pos'),
                'shop_order' => __('Orders', 'final-pos'),
                'user' => __('Users', 'final-pos')
            ];
            foreach ($tabs as $type => $label) {
                $class = $type === 'all' ? ' class="active"' : '';
                printf(
                    '<a href="#" data-type="%s"%s>%s</a> ',
                    esc_attr($type),
                    esc_attr($class),
                    esc_html($label)
                );
            } ?>
        </div>
        <div id="uxlabs-results"></div>
        <div id="uxlabs-load-more-container" style="display: none;">
            <a href="#" id="uxlabs-load-more"><?php esc_html_e('Load more', 'final-pos'); ?></a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('uxlabs-search', 'uxlabs_search_shortcode');

// AJAX Search Function
function uxlabs_search_ajax() {
    // Add nonce verification
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'uxlabs_ajax_nonce')) {
        wp_send_json_error(__('Invalid nonce', 'final-pos'));
    }

    $query = isset($_POST['query']) ? sanitize_text_field(wp_unslash($_POST['query'])) : '';
    $post_type = isset($_POST['post_type']) ? sanitize_text_field(wp_unslash($_POST['post_type'])) : 'all';
    $paged = isset($_POST['page']) ? intval($_POST['page']) : 1;

    $results = ['html' => '', 'max_pages' => 1, 'grouped_results' => []];
    $grouped_results = ['post' => '', 'page' => '', 'product' => '', 'shop_order' => '', 'user' => ''];

    $per_page = 5; // Set number of items per page

    // User Search
    if (in_array($post_type, ['user', 'all'])) {
        $user_query_args = [
            'search' => '*' . esc_attr($query) . '*',
            'search_columns' => ['user_login', 'user_nicename', 'user_email', 'display_name'],
            'number' => $per_page,
            'paged' => $paged,
        ];
        $users = new WP_User_Query($user_query_args);
        foreach ($users->get_results() as $user) {
            $grouped_results['user'] .= get_result_item_html(
                'person',
                get_edit_user_link($user->ID),
                $user->display_name,
                $user->user_email
            );
        }
        $results['max_pages'] = ceil($users->get_total() / $per_page);
    }

    // WooCommerce Order Search
    if (final_is_woocommerce_active() && in_array($post_type, ['shop_order', 'all'])) {
        $order_query_args = [
            'limit' => $per_page,
            'paged' => $paged,
            'return' => 'ids',
            'status' => 'any',
            'orderby' => 'date',
            'order' => 'DESC',
            's' => $query,
        ];

        $order_query = new WC_Order_Query($order_query_args);
        $order_ids = $order_query->get_orders();

        foreach ($order_ids as $order_id) {
            $order = wc_get_order($order_id);
            if ($order) {
                $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                $order_number = $order->get_order_number();
                $customer_email = $order->get_billing_email();
                $total = $order->get_total();
                $currency_symbol = get_woocommerce_currency_symbol($order->get_currency());
                $formatted_total = $currency_symbol . number_format($total, 2, '.', ',');

                $grouped_results['shop_order'] .= get_result_item_html(
                    'shopping_cart',
                    $order->get_edit_order_url(),
                    // translators: %1$s: order number, %2$s: customer name
                    sprintf(__('Order #%1$s - %2$s', 'final-pos'), $order_number, $customer_name),
                    // translators: %1$s: formatted total, %2$s: customer email
                    sprintf(__('Total: %1$s | Email: %2$s', 'final-pos'), $formatted_total, $customer_email)
                );
            }
        }

        // Calculate total number of orders for pagination
        $total_orders_query = new WC_Order_Query([
            'limit' => -1,
            'return' => 'ids',
            's' => $query,
        ]);
        $total_orders = count($total_orders_query->get_orders());
        $results['max_pages'] = ceil($total_orders / $per_page);
    }

    // Product Search
    if (final_is_woocommerce_active() && in_array($post_type, ['product', 'all'])) {
        $product_query_args = [
            'post_type' => 'product',
            's' => $query,
            'posts_per_page' => $per_page,
            'paged' => $paged,
        ];

        $product_query = new WP_Query($product_query_args);

        while ($product_query->have_posts()) {
            $product_query->the_post();
            $grouped_results['product'] .= get_result_item_html(
                'inventory_2',
                get_edit_post_link(),
                get_the_title(),
                wp_trim_words(get_the_excerpt(), 15, '...')
            );
        }
        wp_reset_postdata();
        $results['max_pages'] = $product_query->max_num_pages;
    }

    // General Post and Page Search
    if (in_array($post_type, ['post', 'page', 'all'])) {
        $post_types = $post_type === 'all' ? ['post', 'page'] : [$post_type];
        $post_query_args = [
            's' => $query,
            'post_type' => $post_types,
            'posts_per_page' => $per_page,
            'paged' => $paged,
        ];

        $search_query = new WP_Query($post_query_args);

        while ($search_query->have_posts()) {
            $search_query->the_post();
            $current_post_type = get_post_type();
            $grouped_results[$current_post_type] .= get_result_item_html(
                get_post_type_icon($current_post_type),
                get_edit_post_link(),
                get_the_title(),
                wp_trim_words(get_the_excerpt(), 15, '...')
            );
        }
        wp_reset_postdata();
        $results['max_pages'] = $search_query->max_num_pages;
    }

    // Compile Results
    foreach ($grouped_results as $type => $content) {
        if (!empty($content)) {
            $title = ucfirst(str_replace('_', ' ', $type));
            $results['html'] .= "<div class=\"uxlabs-category-title\">$title</div><div class=\"uxlabs-category-$type\">$content</div>";
            $results['grouped_results'][$type] = $content;
        }
    }

    if (empty($results['html'])) {
        $results['html'] = '<div class="uxlabs-result-item">' . esc_html__('No results found', 'final-pos') . '</div>';
    }

    wp_send_json($results);
}
add_action('wp_ajax_uxlabs_search', 'uxlabs_search_ajax');
add_action('wp_ajax_nopriv_uxlabs_search', 'uxlabs_search_ajax');

// Generate Result Item HTML
function get_result_item_html($icon, $url, $title, $excerpt) {
    return "<div class=\"uxlabs-result-item\">
        <div class=\"result-title\">
            <span class=\"material-symbols-outlined uxlabs-boxed-icon\">$icon</span>
            <div>
                <a href=\"" . esc_url($url) . "\"><strong>" . esc_html($title) . "</strong></a>
                <span class=\"result-excerpt\">" . esc_html($excerpt) . "</span>
            </div>
        </div>
        <div class=\"result-actions\">
            <a href=\"" . esc_url($url) . "\">
                <span class=\"material-symbols-outlined\">more_horiz</span>
            </a>
        </div>
    </div>";
}

// Get Post Type Icon
function get_post_type_icon($post_type) {
    $icons = [
        'post' => 'article',
        'page' => 'description',
        'product' => 'inventory_2',
        'shop_order' => 'shopping_cart',
        'user' => 'person'
    ];

    return $icons[$post_type] ?? 'description';
}

// Plugin Widget Shortcode
function uxlabs_plugins_widget_shortcode() {
    ob_start(); ?>
    <div id="uxlabs-plugin">
        <div class="uxlabs-plugin-status">
            <div class="uxlabs-header">
                <h2><?php esc_html_e('Plugin Status', 'final-pos'); ?></h2>
                <div class="uxlabs-buttons">
                    <a href="<?php echo esc_url(admin_url('plugins.php')); ?>" class="uxlabs-button">
                        <span class="material-symbols-outlined finalicons">view_list</span>
                        <?php esc_html_e('All Plugins', 'final-pos'); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('plugin-install.php')); ?>" class="uxlabs-button">
                        <span class="material-symbols-outlined finalicons">add</span>
                        <?php esc_html_e('Add New', 'final-pos'); ?>
                    </a>
                </div>
            </div>
            <?php echo wp_kses_post(uxlabs_get_recent_plugins()); ?>
        </div>
        <div class="uxlabs-site-status">
            <div class="uxlabs-header">
                <h2><?php esc_html_e('System Status', 'final-pos'); ?></h2>
                <div class="uxlabs-buttons">
                    <a href="<?php echo esc_url(admin_url('update-core.php')); ?>" class="uxlabs-button">
                        <span class="material-symbols-outlined finalicons">update</span>
                        <?php esc_html_e('Updates', 'final-pos'); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('site-health.php')); ?>" class="uxlabs-button">
                        <span class="material-symbols-outlined finalicons">health_and_safety</span>
                        <?php esc_html_e('Site Health', 'final-pos'); ?>
                    </a>
                </div>
            </div>
            <?php echo wp_kses_post(uxlabs_get_site_status()); ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('uxlabs-plugin', 'uxlabs_plugins_widget_shortcode');

// Get Recent Plugins
function uxlabs_get_recent_plugins() {
    $all_plugins = get_plugins();
    $updates_available = [];
    $no_updates = [];

    foreach ($all_plugins as $plugin_file => $plugin_data) {
        if (!empty(get_plugin_updates()[$plugin_file])) {
            $updates_available[$plugin_file] = $plugin_data;
        } else {
            $no_updates[$plugin_file] = $plugin_data;
        }
    }

    $recent_plugins = array_slice(array_merge($updates_available, $no_updates), 0, 3);

    ob_start();
    echo '<ul>';
    foreach ($recent_plugins as $plugin_file => $plugin_data) {
        $is_active = is_plugin_active($plugin_file);
        $icon = isset($updates_available[$plugin_file]) ? 'update' : 'check_circle';
        $new_version = isset($updates_available[$plugin_file]) 
            ? wp_kses('<span class="material-symbols-outlined" style="font-size: 13px;">upgrade</span> ' . esc_html(get_plugin_updates()[$plugin_file]->update->new_version), ['span' => ['class' => [], 'style' => []]])
            : esc_html__('Up to date', 'final-pos');

        $deactivate_url = wp_nonce_url('plugins.php?action=deactivate&plugin=' . urlencode($plugin_file), 'deactivate-plugin_' . $plugin_file);
        $activate_url = wp_nonce_url('plugins.php?action=activate&plugin=' . urlencode($plugin_file), 'activate-plugin_' . $plugin_file); ?>
        <li>
            <div class="plugin-title">
                <span class="material-symbols-outlined uxlabs-boxed-icon"><?php echo esc_html($icon); ?></span>
                <div>
                    <strong><?php echo esc_html($plugin_data['Name']); ?></strong>
                    <span class="plugin-description"><?php echo esc_html(wp_trim_words($plugin_data['Description'], 15, '...')); ?></span>
                    <div class="plugin-actions">
                        <?php if (isset($updates_available[$plugin_file])) : ?>
                            <a href="<?php echo esc_url(admin_url('update-core.php')); ?>" class="uxlabs-link"><?php esc_html_e('Update', 'final-pos'); ?></a>
                        <?php endif; ?>
                        <?php if ($is_active) : ?>
                            <a href="<?php echo esc_url($deactivate_url); ?>" class="uxlabs-link"><?php esc_html_e('Deactivate', 'final-pos'); ?></a>
                        <?php else : ?>
                            <a href="<?php echo esc_url($activate_url); ?>" class="uxlabs-link"><?php esc_html_e('Activate', 'final-pos'); ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="plugin-meta">
                <span><?php echo esc_html($plugin_data['Version']); ?></span>
                <p class="comparison-text"><?php echo wp_kses_post($new_version); ?></p>
            </div>
        </li>
        <?php
    }
    echo '</ul>';
    return ob_get_clean();
}

// Get Site Status
function uxlabs_get_site_status() {
    global $wp_version;

    $site_status = [
        'WordPress Version' => ['meta' => $wp_version, 'required' => '5.5.0', 'description' => __('Streamline your workflow with the Oliver POS.', 'final-pos')],
        'WooCommerce Version' => ['meta' => defined('WC_VERSION') ? WC_VERSION : __('Not Installed', 'final-pos'), 'required' => '5.5.0', 'description' => __('Streamline your workflow with the Oliver POS.', 'final-pos')],
        'PHP Version' => ['meta' => phpversion(), 'required' => '7.5.0', 'description' => __('Streamline your workflow with the Oliver POS.', 'final-pos')],
        'Memory Limit' => ['meta' => WP_MEMORY_LIMIT, 'required' => '64M', 'description' => __('Streamline your workflow with the Oliver POS.', 'final-pos')],
        'Max Execution Time' => ['meta' => ini_get('max_execution_time'), 'required' => '300', 'description' => __('Streamline your workflow with the Oliver POS.', 'final-pos')]
    ];

    ob_start();
    echo '<ul>';
    foreach ($site_status as $status_title => $status) {
        $status_class = 'uxlabs-success-text';
        
        if (version_compare($status['meta'], $status['required'], '<')) {
            $status_class = 'uxlabs-error-text';
        } elseif (version_compare($status['meta'], $status['required'], '=')) {
            $status_class = 'uxlabs-warning-text';
        } ?>
        <li>
            <div class="site-info-title">
                <span class="material-symbols-outlined uxlabs-boxed-icon">browser_updated</span>
                <div>
                    <strong><?php echo esc_html($status_title); ?></strong>
                    <p style="margin: 0; font-size: 12px; color: var(--uxlabs-text-light-color);">
                        <?php echo esc_html($status['description']); ?>
                    </p>
                </div>
            </div>
            <div class="site-info-meta">
                <span class="<?php echo esc_attr($status_class); ?>"><?php echo esc_html($status['meta']); ?></span>
                <p class="comparison-text"><?php esc_html_e('Required:', 'final-pos'); ?> <?php echo esc_html($status['required']); ?></p>
            </div>
        </li>
        <?php
    }
    echo '</ul>';
    return ob_get_clean();
}
// User Shortcode
function uxlabs_user_shortcode() {
    $current_user = wp_get_current_user();
    $initials = strtoupper(substr($current_user->user_firstname . $current_user->user_lastname, 0, 2)) ?: strtoupper($current_user->user_login[0]);
    $preferences = final_get_user_preferences();
    $generic_settings = get_option('final_pos_ui_settings', array());

    ob_start(); ?>
    <div id="uxlabs-user-container">
        <div class="uxlabs-header">
            <div class="uxlabs-user-initials">
                <div class="user-initials-class"><?php echo esc_html($initials); ?></div>
                <div class="uxlabs-user-info">
                <?php /* translators: %s: User's first name */ ?>
                    <h2><?php echo esc_html(sprintf(__('Hello %s!', 'final-pos'), $current_user->first_name)); ?></h2>
                    <p><?php echo esc_html(date_i18n('l, d.m.Y, H:i a')); ?></p>
                </div>
            </div>
        </div>
        <div id="user-profile-links">
            <?php
            $links = [
                ['url' => admin_url('profile.php'), 'icon' => 'person', 'text' => esc_html__('Edit Profile', 'final-pos')],
                ['url' => home_url('/'), 'icon' => 'home', 'text' => esc_html__('View Website', 'final-pos')],
                ['url' => wp_logout_url(), 'icon' => 'logout', 'text' => esc_html__('Logout', 'final-pos')]
            ];
            foreach ($links as $link) {
                echo '<a href="' . esc_url($link['url']) . '"><span class="material-symbols-outlined finalicons">' . esc_html($link['icon']) . '</span>' . esc_html($link['text']) . '</a>';
            } ?>
        </div>
        <div class="uxlabs-settings-status">
            <div class="uxlabs-header">
                <h2><?php esc_html_e('UI Preferences', 'final-pos'); ?></h2>
                <div class="uxlabs-buttons">
                    <a href="#" class="uxlabs-button"><span class="material-symbols-outlined finalicons">refresh</span><?php esc_html_e('All Settings', 'final-pos'); ?></a>
                    <a href="#" class="uxlabs-button"><span class="material-symbols-outlined finalicons">settings_backup_restore</span><?php esc_html_e('Classic WP', 'final-pos'); ?></a>
                </div>
            </div>
            <ul>
                <?php
                $settings = [
                    ['id' => 'dark_mode', 'icon' => 'dark_mode', 'title' => esc_html__('Enable Dark Mode', 'final-pos'), 'description' => esc_html__('Enable dark mode in the Backend.', 'final-pos')],
                    ['id' => 'sorted_admin_menu', 'icon' => 'menu', 'title' => esc_html__('WP Admin Menu', 'final-pos'), 'description' => esc_html__('Show the regular Wordpress Menu.', 'final-pos')],
                    ['id' => 'hide_menu_icons', 'icon' => 'visibility_off', 'title' => esc_html__('Hide Menu Icons', 'final-pos'), 'description' => esc_html__('Hide the icons in the Admin Menu.', 'final-pos')],
                    ['id' => 'hide_admin_toolbar', 'icon' => 'toolbar', 'title' => esc_html__('Hide Admin Toolbar', 'final-pos'), 'description' => esc_html__('Hide the dynamic Toolbar elements.', 'final-pos')],
                    ['id' => 'show_all_notifications', 'icon' => 'notifications', 'title' => esc_html__('Show Notifications', 'final-pos'), 'description' => esc_html__('Show all notifications in the Backend.', 'final-pos')]
                ];

                foreach ($settings as $setting) {
                    // Skip this setting if it's enabled in generic settings
                    if (!empty($generic_settings[$setting['id']])) {
                        continue;
                    }

                    $checked = isset($preferences[$setting['id']]) && $preferences[$setting['id']] === '1' ? 'checked' : ''; ?>
                    <li>
                        <div class="setting-title" style="display: flex; justify-content: space-between; align-items: center;">
                            <span class="material-symbols-outlined uxlabs-boxed-icon"><?php echo esc_html($setting['icon']); ?></span>
                            <div style="flex-grow: 1; margin-left: 10px;">
                                <strong><?php echo esc_html($setting['title']); ?></strong>
                                <span class="setting-description"><?php echo esc_html($setting['description']); ?></span>
                            </div>
                            <label class="switch" style="margin-left: auto;">
                                <input type="checkbox" class="uxlabs-preference-checkbox" data-preference="<?php echo esc_attr($setting['id']); ?>" <?php echo esc_attr($checked); ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </li>
                    <?php
                } ?>
            </ul>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('uxlabs-user', 'uxlabs_user_shortcode');

