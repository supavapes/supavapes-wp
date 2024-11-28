<?php
// Prevent direct access to the script
if ( ! defined('ABSPATH') ) {
    exit;
}
if ( ! is_admin() ) {
    return;
}

// Include common functions at the beginning of the file
require_once plugin_dir_path( __FILE__ ) . '../../common-functions.php';

// Redirect WooCommerce top-level menu
function redirect_woocommerce_top_level_menu() {
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#toplevel_page_woocommerce > a').on('click', function(e) {
                e.preventDefault();
                window.location.href = '<?php echo esc_url( admin_url( 'edit.php?post_type=shop_order' ) ); ?>';
            });
        });
    </script>
    <?php
}
add_action( 'admin_footer', 'redirect_woocommerce_top_level_menu' );

// Function to get combined UI settings
function get_combined_ui_settings() {
    $generic_settings = get_option( 'final_pos_ui_settings', array() );
    $user_preferences = final_get_user_preferences();

    // Ensure generic settings are in the correct format
    $generic_settings = maybe_unserialize( $generic_settings );

    // Combine settings, with generic settings taking precedence
    return array_merge( $user_preferences, $generic_settings );
}

// Sort and customize the admin menu
function custom_admin_menu_order( $menu_order ) {
    $ui_settings = get_combined_ui_settings();

    // If wp_admin_menu is enabled in generic settings, return the default menu order
    if ( ! empty( $ui_settings['wp_admin_menu'] ) ) {
        return $menu_order;
    }

    global $submenu;

    // Add the new main menu item at the first position
    array_unshift( $menu_order, 'final-main-menu' );

    // List of slugs to be ordered
    $final_is_woocommerce_active = final_is_woocommerce_active();
    $known_slugs = array_filter( [
        $final_is_woocommerce_active ? 'separator-store' : '',
        'final-admin-dashboard', // New main menu item
        'woocommerce',
        'edit.php?post_type=product',  // WooCommerce Products
        'woocommerce-marketing',
        'wc-admin&path=/analytics/overview',
        'users-placeholder', // Placeholder for users menu
        'separator-custom', // New Custom Heading
        'separator-content',
        'edit.php?post_type=page',
        'edit.php',
        'upload.php',
        'users-placeholder-web', // Placeholder for users menu
        'separator-appearance',
        'admin-ajax.php?action=kernel&p=customizer',
        'themes.php',
        'tools.php',
        'options-general.php',
        'xts_theme_settings', // Woodmart theme settings
        'xts_dashboard', // Woodmart dashboard
        'astra', // Astra theme settings
        'separator-plugins',
    ] );

    // Filter only the slugs that exist in the menu
    $known_slugs = array_intersect( $known_slugs, $menu_order );

    // Add user menu based on WooCommerce status
    $user_placeholder = $final_is_woocommerce_active ? 'users-placeholder' : 'users-placeholder-web';
    array_splice( $known_slugs, array_search( $user_placeholder, $known_slugs ) + ($final_is_woocommerce_active ? 5 : 4), 0, 'users.php' );

    // Collect custom menu items
    $custom_menu_items = apply_filters( 'final_custom_menu_items', [] );

    // Add custom menu items after 'separator-custom'
    if ( ! empty( $custom_menu_items ) ) {
        $custom_position = array_search( 'separator-custom', $known_slugs );
        if ( $custom_position !== false ) {
            foreach ( array_reverse( $custom_menu_items ) as $item ) {
                array_splice( $known_slugs, $custom_position + 1, 0, $item[2] );
            }
        }
    }

    // Dynamically place custom post types under "upload.php"
    $custom_post_types = get_post_types( [ '_builtin' => false ], 'objects' );
    foreach ( $custom_post_types as $post_type ) {
        $slug = "edit.php?post_type={$post_type->name}";
        // If the post type is not already in the defined menu, move it under "upload.php"
        if ( ! in_array( $slug, $known_slugs ) && in_array( $slug, $menu_order ) ) {
            $position = array_search( 'upload.php', $known_slugs ) + 1;
            array_splice( $known_slugs, $position, 0, $slug );
        }
    }

    // Clean up the final order
    $final_order = array_merge( $known_slugs, array_diff( $menu_order, $known_slugs ) );

    // Move Woodmart theme items and Astra under options-general.php
    $options_general_index = array_search( 'separator-appearance', $final_order );
    if ( $options_general_index !== false ) {
        $theme_items = [ 'xts_dashboard', 'xts_theme_settings', 'astra' ];
        foreach ( $theme_items as $item ) {
            $item_index = array_search( $item, $final_order );
            if ( $item_index !== false ) {
                // Remove the item from its current position
                unset( $final_order[$item_index] );
                // Insert it after options-general.php
                array_splice( $final_order, $options_general_index + 1, 0, $item );
                $options_general_index++; // Increment to keep the order
            }
        }
    }

    return $final_order;
}
add_filter( 'menu_order', 'custom_admin_menu_order', 99 );
add_filter( 'custom_menu_order', '__return_true' );

// Add admin menu headings
function custom_admin_menu_headings() {
    $ui_settings = get_combined_ui_settings();

    // If wp_admin_menu is enabled in generic settings, don't add custom headings
    if ( ! empty( $ui_settings['wp_admin_menu'] ) ) {
        return;
    }

    global $menu;
    if ( final_is_woocommerce_active() ) {
        $menu[] = array( __( 'Store', 'final-pos' ), 'read', 'separator-store', '', 'wp-not-current-submenu menu-top custom-heading', 'separator-store', '' );
    }
    $menu[] = array( __( 'Content', 'final-pos' ), 'activate_plugins', 'separator-content', '', 'wp-not-current-submenu menu-top custom-heading', 'separator-content', '' );
    $menu[] = array( __( 'Design', 'final-pos' ), 'administrator', 'separator-appearance', '', 'wp-not-current-submenu menu-top custom-heading', 'separator-appearance', '' );
    $menu[] = array( __( 'Plugins', 'final-pos' ), 'read', 'separator-plugins', '', 'wp-not-current-submenu menu-top custom-heading', 'separator-plugins', '' );
}
add_action( 'admin_menu', 'custom_admin_menu_headings' );

// Function to add custom headings and collect custom menu items
function add_custom_menu_heading() {
    $ui_settings = get_combined_ui_settings();

    // If wp_admin_menu is enabled in generic settings, don't add custom headings
    if ( ! empty( $ui_settings['wp_admin_menu'] ) ) {
        return;
    }

    global $menu;

    // Collect custom menu items
    $custom_menu_items = apply_filters( 'final_custom_menu_items', [] );

    // Add the custom heading only if there are custom menu items
    if ( ! empty( $custom_menu_items ) ) {
        $menu[] = array( __( 'Extensions', 'final-pos' ), 'read', 'separator-custom', '', 'wp-not-current-submenu menu-top custom-heading', 'separator-custom', '' );

        // Add the custom menu items
        foreach ( $custom_menu_items as $item ) {
            $menu[] = $item;
        }
    }
}
add_action( 'admin_menu', 'add_custom_menu_heading', 20 );

// Rename WooCommerce menu
function customize_admin_menu() {
    global $menu;

    // Loop through the menu and adjust individual menu items
    foreach ( $menu as $key => $item ) {
        // Change the WooCommerce menu item
        if ( $item[2] === 'woocommerce' ) {
            $menu[$key][0] = __( 'Orders', 'final-pos' );
            $menu[$key][6] = 'dashicons-cart';
        }
        // Change the HTML Blocks menu item
        elseif ( $item[2] === 'edit.php?post_type=cms_block' ) {
            $menu[$key][0] = __( 'Blocks', 'final-pos' );
        }
    }
}
add_action( 'admin_menu', 'customize_admin_menu' );
// Enqueue scripts and styles
function enqueue_menu_icons() {
    wp_enqueue_style( 'final-menu-style', plugins_url( '../../../assets/css/ui/menu.css', __FILE__ ), array(), '1.0.0' ); // Added version parameter
}
add_action( 'admin_enqueue_scripts', 'enqueue_menu_icons' );

// Unsorted menu customization
function customize_admin_menus() {
    $ui_settings = get_combined_ui_settings();
    $user_preferences = get_user_meta( get_current_user_id(), 'final_preferences', true );

    // If wp_admin_menu is enabled in generic settings, don't customize the menu
    if ( ! empty( $ui_settings['wp_admin_menu'] ) ) {
        return;
    }

    // Remove unnecessary menu items
    remove_admin_menu_items();

    // Add comments as a submenu under Users
    add_comments_to_users_menu();

    // Hide menu icons if the setting is enabled
    if ( ! empty( $ui_settings['hide_menu_icons'] ) || 
         ( ! empty( $user_preferences['hide_menu_icons'] ) && $user_preferences['hide_menu_icons'] === '1' ) ) {
        add_filter( 'admin_body_class', 'add_hide_menu_icons_class' );
    }
}
add_action( 'admin_menu', 'customize_admin_menus', 999 );

function remove_admin_menu_items() {
    // Existing removals
    remove_submenu_page( 'tools.php', 'site-health.php' );
    remove_submenu_page( 'index.php', 'index.php' );
    remove_submenu_page( 'index.php', 'index.php?page=overview' );
    remove_submenu_page( 'edit.php', 'post-new.php' );
    remove_submenu_page( 'edit.php?post_type=page', 'post-new.php?post_type=page' );
    remove_submenu_page( 'users.php', 'user-new.php' );
    remove_submenu_page( 'woocommerce', 'wc-reports' );
    remove_submenu_page( 'woocommerce', 'wc-ppcp-main' );

    // Remove Comments from main menu
    remove_menu_page( 'edit-comments.php' );

    // Existing removals
    remove_menu_page( 'plugin-install.php' );
    remove_menu_page( 'update-core.php' );
    remove_menu_page( 'index.php' );
}

// New function to add Comments as a submenu under Users
function add_comments_to_users_menu() {
    // Load the saved settings using the common function
    $saved_settings = final_get_advanced_settings();

    // Check if comments are disabled
    if ( empty( $saved_settings['disable_comments'] ) ) {
        add_submenu_page(
            'users.php',                 // Parent slug
            __( 'Comments', 'final-pos' ),   // Page title
            __( 'Comments', 'final-pos' ),   // Menu title
            'moderate_comments',         // Capability
            'edit-comments.php'          // Menu slug
        );
    }
}

// Function to add the class to the body element
function add_hide_menu_icons_class( $classes ) {
    return $classes . ' hide-menu-icons';
}
