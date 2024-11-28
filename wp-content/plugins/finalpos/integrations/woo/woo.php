<?php

if (!defined('ABSPATH')) {
    exit;
}

// Remove WooCommerce Menus
function remove_woocommerce_menus() {
    remove_submenu_page( 'woocommerce', 'wc-admin&path=/extensions' ); // Remove "Extensions"
    remove_submenu_page( 'woocommerce', 'wc-reports' ); // Remove "Reports"
    remove_submenu_page( 'woocommerce', 'wc-admin&path=/customers' ); // Remove "Customers"
    remove_submenu_page( 'woocommerce', 'wc-admin&path=/home' ); // Remove "Home"
    remove_submenu_page('edit.php?post_type=product', 'post-new.php?post_type=product');
}
add_action( 'admin_menu', 'remove_woocommerce_menus', 999 );

function final_woocommerce_admin_scripts() {
    global $pagenow;
    
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $post_type = isset($_GET['post_type']) ? sanitize_text_field(wp_unslash($_GET['post_type'])) : '';
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
    
    // WooCommerce top-level menu redirect
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            var wcMenu = $("#toplevel_page_wc-admin > a");
            if (wcMenu.length > 0) {
                wcMenu.on("click", function(e) {
                    e.preventDefault();
                    window.location.href = "<?php echo esc_url(admin_url("edit.php?post_type=shop_order")); ?>";
                });
            }
        });
    </script>
    <?php

    // Dynamic Orders Dropdowns
    $on_order_page = ($pagenow == 'edit.php' && $post_type === 'shop_order') ||
                     ($pagenow == 'admin.php' && $page === 'wc-orders');

    if ($on_order_page) {
        wp_enqueue_script('wc-order-status-dropdown', plugins_url('/finalpos/assets/js/woo/orderscreen.js'), array('jquery'), '1.0', true); // Added version parameter
        wp_localize_script('wc-order-status-dropdown', 'wc_order_status_dropdown', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wc-order-status-nonce'),
            'statuses' => wc_get_order_statuses()
        ));
    }
}
add_action('admin_footer', 'final_woocommerce_admin_scripts');

add_action('wp_ajax_wc_update_order_status', 'wc_update_order_status_callback');
function wc_update_order_status_callback() {
    // Security check
    check_ajax_referer('wc-order-status-nonce', 'security');

    // Check if the user has permission to update order status
    if (!current_user_can('edit_shop_orders')) {
        wp_send_json_error(__('Insufficient permissions.', 'final-pos'));
    }

    // Check if the required data is provided
    if (!isset($_POST['order_id']) || !isset($_POST['status'])) {
        wp_send_json_error(__('Missing data.', 'final-pos'));
    }

    $order_id = sanitize_text_field(wp_unslash($_POST['order_id']));
    $new_status = sanitize_text_field(wp_unslash($_POST['status']));

    // Retrieve the order
    $order = wc_get_order($order_id);

    // Check if the order exists
    if (!$order) {
        wp_send_json_error(__('Order not found.', 'final-pos'));
    }

    // Check if order is readonly
    if ($order->get_meta('readonly') === 'true') {
        wp_send_json_error(__('This order is read-only and cannot be modified.', 'final-pos'));
    }

    // Update order status
    $order->update_status($new_status);

    // Send success message
    wp_send_json_success(array(
        'new_status_label' => wc_get_order_status_name($new_status),
        'new_status_slug' => $new_status
    ));
}

