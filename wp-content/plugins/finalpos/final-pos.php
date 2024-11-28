<?php
/*
 * Plugin Name: Final POS - Drag & Drop Point of Sale Builder
 * Description: Pair your WooCommerce store with FinalPOS
 * Author: FinalPOS
 * Author URI: https://finalpos.com
 * version: 1.1.1
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Requires at least: 4.9
 * Tested up to: 6.5.4
 * Requires PHP: 7.0


 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    die('Direct access is not allowed.');
}

// Include Composer Autoloader
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Add these lines after other require_once statements
require_once __DIR__ . '/includes/api/sync.php';

// Include common functions
require_once __DIR__ . '/includes/common-functions.php';

// Always include these files
require_once __DIR__ . '/includes/admin/other/settings.php';
require_once __DIR__ . '/includes/admin/save-wc-token.php'; // Neue Zeile
require_once __DIR__ . '/includes/admin/ui/dashboard.php';

// Enqueue Google Material Symbols
add_action('admin_enqueue_scripts', 'enqueue_material_icons', 1);
add_action('wp_enqueue_scripts', 'enqueue_material_icons', 1);
// Enqueue Material Icons
function enqueue_material_icons() {
    wp_enqueue_style('material-icons', 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@400', [], '1.0.0'); // Added specific version parameter
}

// Include WooCommerce-related files only if WooCommerce is active
if (final_is_woocommerce_active()) {
    require_once __DIR__ . '/integrations/woo/woo/final-woo.php';
    require_once __DIR__ . '/integrations/woo/woo/readonly-orders.php';
}

// Add this function to check and update the wizard status
function final_check_wizard_completion() {
    if (get_transient('final_wizard_complete')) {
        update_option('final_wizard_status', 'completed_sync');
        delete_transient('final_wizard_complete');
    }
}
add_action('admin_init', 'final_check_wizard_completion');

// Modify the wizard inclusion logic
function final_pos_init() {
    // Add nonce verification with sanitization
    $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
    $is_valid_nonce = wp_verify_nonce($nonce, 'final_pos_init');
    
    $wizard_status = get_option('final_wizard_status', '');
    $current_page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
    $sync_param = isset($_GET['sync']) ? sanitize_text_field(wp_unslash($_GET['sync'])) : '';
    
    // Verify nonce before processing GET parameters
    if ($is_valid_nonce || is_admin()) {
        if ($wizard_status !== 'completed_sync' || 
            ($current_page === 'final-pos-setup' && $sync_param === 'start')) {
            require_once __DIR__ . '/includes/admin/finalwizard.php';
        }
    }
}
add_action('init', 'final_pos_init');

// Check the UI choice
$ui_choice = get_option('final_pos_ui_choice', 'modern');

if ($ui_choice === 'classic') {
    // Enqueue base styles for classic UI
    add_action('admin_enqueue_scripts', function() {
        wp_enqueue_style('final-admin-base', plugin_dir_url(__FILE__) . 'assets/css/ui/admin-base.css', [], '1.0');
    });
}

// Load other files and execute functions only if UI choice is not 'classic'
if ($ui_choice !== 'classic') {
    // Admin related includes
    require_once __DIR__ . '/includes/admin/other/performance.php';
    require_once __DIR__ . '/includes/admin/other/posts.php';
    require_once __DIR__ . '/includes/admin/ui/ui.php';
    require_once __DIR__ . '/includes/admin/ui/toolbar.php';
    require_once __DIR__ . '/includes/admin/ui/final-sidebar.php';
    require_once __DIR__ . '/includes/admin/ui/menu.php';

    // WooCommerce related includes
    if (final_is_woocommerce_active()) {
        require_once __DIR__ . '/integrations/woo/woo.php';
        /* Pushing in Verison 1.2.0 - parts needed currently */
        require_once __DIR__ . '/integrations/woo/bulkeditor/bulkeditor-main.php';
        
        // Only load stock management if sync is completed
        $wizard_status = get_option('final_wizard_status', '');
        if ($wizard_status === 'completed' || $wizard_status === 'completed_sync') {
            require_once __DIR__ . '/integrations/woo/woo/stockmanagement-single.php';
        }
    }

    // Enqueue scripts and styles
    add_action('wp_enqueue_scripts', 'final_frontend_admin_color_scheme', 1);

    add_action('admin_init', 'set_final_admin_homepage');
    add_action('admin_init', 'disable_admin_color_scheme_picker');
    add_action('user_register', 'set_default_admin_color');
    add_action('init', 'set_admin_color_for_all_users');

    add_action('admin_enqueue_scripts', 'final_admin_color_scheme', 1);
    add_action('admin_init', 'register_final_admin_color_scheme');

    // Enqueue admin color scheme styles
    function final_admin_color_scheme() {
        wp_enqueue_style('final-admin-color-scheme', plugin_dir_url(__FILE__) . 'assets/css/ui/admin-style.css', [], '1.0'); // Added version parameter
        wp_enqueue_style('germanized-styles', plugin_dir_url(__FILE__) . 'assets/css/woo/germanized.css', [], '1.0'); // Added version parameter
    }

    // Enqueue frontend styles for logged-in users with admin rights
    function final_frontend_admin_color_scheme() {
        if (is_user_logged_in() && current_user_can('manage_options')) {
            wp_enqueue_style('final-admin-color-scheme', plugin_dir_url(__FILE__) . 'assets/css/ui/admin-style.css', [], '1.0'); // Added version parameter
            wp_enqueue_style('final-admin-color-colors', plugin_dir_url(__FILE__) . 'assets/css/ui/admin-colors.css', [], '1.0'); // Added version parameter
        }
    }

    // Register the new admin color scheme
    function register_final_admin_color_scheme() {
        wp_admin_css_color(
            'final_scheme',
            __('Final Scheme', 'final-pos'),
            plugin_dir_url(__FILE__) . 'assets/css/ui/admin-colors.css',
            array('#f1f1f1', '#0073aa', '#0096dd', '#096484', '#e1a948') // WIP
        );
    }

    // Set custom admin page as the default homepage
    function set_final_admin_homepage() {
        if (current_user_can('manage_options')) {
            $custom_homepage = 'admin.php?page=final-admin-dashboard';
            global $pagenow;
            if ($pagenow === 'index.php') {
                wp_redirect(admin_url($custom_homepage));
                exit;
            }
        }
    }

    // Set final scheme as default for the entire system
    function set_default_admin_color_scheme() {
        update_option('default_admin_color', 'final_scheme');
    }

    // Run this function once on plugin activation
    register_activation_hook(__FILE__, 'set_default_admin_color_scheme');

    // Override the default color scheme for all users
    function override_default_admin_color($user_color) {
        return 'final_scheme';
    }
    add_filter('get_user_option_admin_color', 'override_default_admin_color', 10, 1);

    // Remove the individual user update functions
    remove_action('user_register', 'set_default_admin_color');
    remove_action('init', 'set_admin_color_for_all_users');

    // Disable the admin color scheme picker
    function disable_admin_color_scheme_picker() {
        remove_action('admin_color_scheme_picker', 'admin_color_scheme_picker');
    }
    add_action('admin_init', 'disable_admin_color_scheme_picker');
}

// Always load text domain
add_action('plugins_loaded', 'final_load_textdomain');
function final_load_textdomain() {
    load_plugin_textdomain('final-pos', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}


