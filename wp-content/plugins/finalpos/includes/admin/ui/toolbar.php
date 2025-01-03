<?php

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}
// Include common functions
require_once plugin_dir_path(__FILE__) . '../../common-functions.php';

// Remove default WordPress admin bar items
remove_action('admin_bar_menu', 'wp_admin_bar_updates_menu', 50);
add_action('admin_bar_menu', 'add_custom_toolbar_items', 1);

function add_custom_toolbar_items($wp_admin_bar) {
    // Remove default WordPress menu items with higher priority
    add_action('wp_before_admin_bar_render', function() use ($wp_admin_bar) {
        $wp_admin_bar->remove_node('wp-logo');
        $wp_admin_bar->remove_node('site-name');
        $wp_admin_bar->remove_node('updates');
        $wp_admin_bar->remove_node('comments');
        $wp_admin_bar->remove_node('my-account');
        $wp_admin_bar->remove_node('woocommerce-site-visibility-badge');
    }, 0);

    // Additional measures for stubborn elements
    remove_action('admin_bar_menu', 'wp_admin_bar_wp_menu', 10);
    remove_action('admin_bar_menu', 'wp_admin_bar_site_menu', 30);
    remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);
    remove_action('admin_bar_menu', 'wp_admin_bar_my_account_menu', 0);

    // Remove WooCommerce site visibility badge
    remove_action('admin_bar_menu', array('WC_Admin_Bar_Visibility', 'add_menu_items'), 31);

    // Add logo only in the frontend
    if (!is_admin()) {
        $custom_logo_url = get_option('final_pos_logo_url', '');
        $default_logo_url = plugins_url('finalpos/assets/img/ui/final-logo.png');
        
        $logo_url = $custom_logo_url ?: $default_logo_url;
        
        $wp_admin_bar->add_menu(array(
            'id'    => 'frontend-logo',
            'title' => '<img src="' . esc_url($logo_url) . '" alt="Logo" class="frontend-toolbar-logo">',
            'href'  => admin_url(),
            'meta'  => array('class' => 'frontend-logo-container'),
        ));
    }

    // Define menu items and submenus
    $menu_items = array(
        'new-content' => array(
            'title' => 'New Content',
            'href'  => admin_url('post-new.php'),
            'icon'  => 'add_circle',
        ),
        'quick-action' => array(
            'title' => 'Quick Action',
            'href'  => '#',
            'icon'  => 'bolt',
            'submenus' => array(
                'quick-menus' => array(
                    'title' => 'Edit Menus',
                    'href'  => admin_url('nav-menus.php'),
                ),
                'quick-widgets' => array(
                    'title' => 'Edit Widgets',
                    'href'  => admin_url('widgets.php'),
                ),
                'quick_plugins' => array(
                    'title' => 'Plugins',
                    'href'  => admin_url('plugins.php'),
                ),
                'quick_themes' => array(
                    'title' => 'Themes',
                    'href'  => admin_url('themes.php'),
                ),
                'quick_updates' => array(
                    'title' => 'Updates',
                    'href'  => admin_url('update-core.php'),
                ),
                'quick_general_options' => array(
                    'title' => 'General Options',
                    'href'  => admin_url('options-general.php'),
                ),
            ),
        ),
    );

    // Add WooCommerce settings only if WooCommerce is active
    if (final_is_woocommerce_active()) {
        $menu_items['quick-action']['submenus']['woocommerce-settings'] = array(
            'title' => 'Store Settings',
            'href'  => admin_url('admin.php?page=wc-settings'),
        );
    }

    // Add menu items to the admin bar
    foreach ($menu_items as $id => $item) {
        $wp_admin_bar->add_menu(array(
            'id'    => $id,
            'title' => '<span class="ab-icon custom-icon"></span>',
            'href'  => $item['href'] ?? '#',
            'meta'  => array('class' => 'custom-toolbar-icon', 'title' => $item['title']),
        ));

        // Add submenus if available
        if (!empty($item['submenus'])) {
            foreach ($item['submenus'] as $submenu_id => $submenu) {
                $wp_admin_bar->add_menu(array(
                    'parent' => $id,
                    'id'     => $submenu_id,
                    'title'  => $submenu['title'],
                    'href'   => $submenu['href'],
                ));
            }
        }
    }

    // Add secondary menu items to the admin bar (right, before the Final POS button)
    $secondary_items = array(
        'search' => array(
            'title' => 'Search',
            'href'  => '#',
            'icon'  => 'search',
        ),
        'plugins' => array(
            'title' => 'Plugins',
            'href'  => admin_url('plugins.php'),
            'icon'  => 'extension',
            'update_count' => is_admin() ? (get_plugin_updates() ? count(get_plugin_updates()) : 0) : 0,
        ),
        'dashboard' => array(
            'title' => 'Home',
            'href'  => is_admin() ? home_url('/') : admin_url(),
            'icon'  => is_admin() ? 'storefront' : 'dashboard',
        ),
    );

    // Add fullscreen icon for specific page
    if (is_admin() && final_verify_toolbar_nonce()) {
        $post_type = final_get_sanitized_get('post_type');
        $page = final_get_sanitized_get('page');
        
        if ($post_type === 'product' && $page === 'bulk-editor') {
            $secondary_items['fullscreen-mode'] = array(
                'title' => 'Fullscreen',
                'href'  => final_get_toolbar_nonce_url(add_query_arg(array('post_type' => $post_type, 'page' => $page), admin_url('edit.php'))),
                'icon'  => 'fullscreen',
            );
        }
    }

    foreach ($secondary_items as $id => $item) {
        $title = '<span class="ab-icon custom-icon"></span>';
        if ($id === 'plugins' && $item['update_count'] > 0) {
            $title .= '<span class="update-plugins count-' . $item['update_count'] . '"><span class="update-count">' . number_format_i18n($item['update_count']) . '</span></span>';
        }
        $wp_admin_bar->add_menu(array(
            'id'    => $id,
            'title' => $title,
            'href'  => $item['href'],
            'meta'  => array('class' => 'custom-toolbar-icon' . ($id === 'fullscreen-mode' ? ' fullscreen-toggle' : ''), 'title' => $item['title']),
            'parent' => 'top-secondary',
        ));
    }

    // Add "Final POS" menu item (right)
    $wp_admin_bar->add_menu(array(
        'id'    => 'final-pos',
        'title' => '<div class="final-pos-class">Final HUB</div>',
        'href'  => 'https://hub.finalpos.com/store/dashboard',
        'meta'  => array(
            'target' => '_blank',
            'rel' => 'noopener noreferrer'
        ),
        'parent' => 'top-secondary',
    ));

    // Add user initials menu item (right)
    $current_user = wp_get_current_user();
    $initials = strtoupper(substr($current_user->user_firstname, 0, 1)) . strtoupper(substr($current_user->user_lastname, 0, 1));
    if (empty($initials)) {
        $initials = strtoupper($current_user->user_login[0]);
    }
    $wp_admin_bar->add_menu(array(
        'id'    => 'user-initials',
        'title' => '<div class="user-initials-class">' . esc_html($initials) . '</div>',
        'href'  => admin_url('profile.php'),
        'parent' => 'top-secondary',
    ));
}

// Enqueue scripts and styles
function enqueue_toolbar_icons() {
    wp_enqueue_style('final-toolbar-style', plugins_url('../../../assets/css/ui/toolbar.css', __FILE__), array(), '1.0.0'); // Added version parameter
}
add_action('admin_enqueue_scripts', 'enqueue_toolbar_icons');
add_action('wp_enqueue_scripts', 'enqueue_toolbar_icons'); // Enqueue toolbar changes for frontend

// Add JavaScript for fullscreen functionality
function enqueue_fullscreen_script() {
    if (!is_admin()) {
        return;
    }

    $nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
    if (!wp_verify_nonce($nonce, 'final_toolbar_action')) {
        return;
    }

    $post_type = isset($_GET['post_type']) ? sanitize_text_field(wp_unslash($_GET['post_type'])) : '';
    $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
    
    if ($post_type === 'product' && $page === 'bulk-editor') {
        wp_enqueue_script('fullscreen-toggle', plugins_url('../../../assets/js/ui/toolbar.js', __FILE__), array('jquery'), '1.0.0', true);
    }
}
add_action('admin_enqueue_scripts', 'enqueue_fullscreen_script');

// Helper function to safely get and sanitize GET parameters
function final_get_sanitized_get($key) {
    $nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
    if (!wp_verify_nonce($nonce, 'final_toolbar_action')) {
        return '';
    }

    return isset($_GET[$key]) ? sanitize_text_field(wp_unslash($_GET[$key])) : '';
}

function final_verify_toolbar_nonce() {
    $nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
    return wp_verify_nonce($nonce, 'final_toolbar_action');
}

// Add this new function to generate the nonce URL
function final_get_toolbar_nonce_url($url) {
    return wp_nonce_url($url, 'final_toolbar_action');
}
