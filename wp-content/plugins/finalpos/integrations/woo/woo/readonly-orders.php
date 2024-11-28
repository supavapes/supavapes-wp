<?php

if (!defined('ABSPATH')) {
    exit;
}
function finalpos_enqueue_readonly_orders_script($hook) {
    // Prüfen ob wir auf der Order Edit Seite sind
    if ($hook !== 'woocommerce_page_wc-orders' && $hook !== 'post.php') {
        return;
    }

    // Order ID aus URL holen (HPOS und Classic)
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $order_id = isset($_GET['id']) ? absint($_GET['id']) : (isset($_GET['post']) ? absint($_GET['post']) : 0);
    if (!$order_id) return;

    // Order laden
    $order = wc_get_order($order_id);
    if (!$order) return;

    // Readonly Status prüfen
    $readonly = $order->get_meta('readonly') === 'true';
    if ($readonly) {
        wp_enqueue_script('readonly-orders', plugin_dir_url(__FILE__) . '../../../assets/js/woo/readonly-orders.js', array('jquery'), '1.0.0', true); // Added version parameter
        wp_localize_script('readonly-orders', 'orderData', array(
            'readonly' => $readonly,
            'message' => 'This order is read-only and cannot be modified.'
        ));
    }
}
add_action('admin_enqueue_scripts', 'finalpos_enqueue_readonly_orders_script');

// Block all order status changes for readonly orders
function finalpos_block_status_transitions($new_status, $old_status, $order) {
    if (!$order || !is_a($order, 'WC_Order')) {
        return $new_status;
    }

    $readonly = $order->get_meta('readonly') === 'true';
    if ($readonly) {
        return $old_status; // Keep the old status
    }

    return $new_status;
}
add_filter('woocommerce_order_status_transition', 'finalpos_block_status_transitions', 10, 3);

// Block all order updates completely
function finalpos_prevent_readonly_order_updates($order) {
    if (!$order || !is_a($order, 'WC_Order')) {
        return $order;
    }

    $readonly = $order->get_meta('readonly') === 'true';
    if ($readonly) {
        // Prevent any updates by triggering an error that WooCommerce will catch
        throw new Exception('This order is read-only and cannot be modified.');
    }

    return $order;
}
add_filter('woocommerce_before_order_object_save', 'finalpos_prevent_readonly_order_updates', 999, 1);

// Add admin notice for readonly orders
function finalpos_add_readonly_notice() {
    $screen = get_current_screen();
    if (!$screen) return;

    // Prüfen ob wir auf einer Order Edit Seite sind
    if ($screen->base !== 'woocommerce_page_wc-orders' && $screen->base !== 'post') {
        return;
    }

    // Order ID ermitteln
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $order_id = isset($_GET['id']) ? absint($_GET['id']) : (isset($_GET['post']) ? absint($_GET['post']) : 0);
    if (!$order_id) return;

    // Order laden
    $order = wc_get_order($order_id);
    if (!$order) return;

    // Readonly Status prüfen und Notice ausgeben
    if ($order->get_meta('readonly') === 'true') {
        echo '<div class="notice notice-success"><p><strong>Hinweis:</strong> Diese Bestellung ist schreibgeschützt und kann nicht bearbeitet werden.</p></div>';
    }
}
add_action('admin_notices', 'finalpos_add_readonly_notice');

// Prevent stock recalculation for readonly orders
function finalpos_prevent_stock_recalculation($order) {
    if (!$order || !is_a($order, 'WC_Order')) {
        return true; // Allow stock reduction by default
    }

    $readonly = $order->get_meta('readonly') === 'true';
    if ($readonly) {
        return false; // Prevent stock reduction for readonly orders
    }

    return true; // Allow stock reduction for non-readonly orders
}
add_filter('woocommerce_can_reduce_order_stock', 'finalpos_prevent_stock_recalculation', 10, 1);
