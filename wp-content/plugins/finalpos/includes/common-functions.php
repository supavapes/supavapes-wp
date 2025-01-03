<?php

if (!defined('ABSPATH')) {
    exit;
}

// Check if WooCommerce is active
if (!function_exists('final_is_woocommerce_active')) {
    function final_is_woocommerce_active() {
        return in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')));
    }
}

/**
 * Retrieve user preferences for the current user.
 *
 * @return array User preferences.
 */
function final_get_user_preferences() {
    return get_user_meta(get_current_user_id(), 'final_preferences', true) ?: [];
}

/**
 * Check if the current user has a specific role.
 *
 * @param string $role Role to check.
 * @return bool True if the user has the role, false otherwise.
 */
function final_user_has_role($role) {
    return current_user_can($role);
}

/**
 * Get the latest content of a specified type.
 *
 * @param string $type  Post type (default: 'post').
 * @param int    $limit Number of posts to retrieve (default: 5).
 * @return array Array of latest content with title, author, date, edit link, and view link.
 */
function final_get_latest_content($type = 'post', $limit = 5) {
    $args = [
        'post_type'      => $type,
        'posts_per_page' => $limit,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ];

    $latest_content = get_posts($args);

    return array_map(function ($item) {
        return [
            'title'     => $item->post_title,
            'author'    => get_the_author_meta('display_name', $item->post_author),
            'date'      => get_the_date('Y-m-d', $item),
            'edit_link' => get_edit_post_link($item->ID),
            'view_link' => get_permalink($item->ID),
        ];
    }, $latest_content);
}

/**
 * Get the latest pages.
 *
 * @param int $limit Number of pages to retrieve (default: 5).
 * @return array Array of latest pages.
 */
function final_get_latest_pages($limit = 5) {
    return final_get_latest_content('page', $limit);
}

/**
 * Get the latest posts.
 *
 * @param int $limit Number of posts to retrieve (default: 5).
 * @return array Array of latest posts.
 */
function final_get_latest_posts($limit = 5) {
    return final_get_latest_content('post', $limit);
}

/**
 * Retrieve WooCommerce orders by status.
 *
 * @param string $status Order status.
 * @param int    $limit  Number of orders to retrieve (default: 10).
 * @return array Array of orders with details.
 */
function final_get_woocommerce_orders_by_status($status, $limit = 10) {
    if (!final_is_woocommerce_active()) {
        return [];
    }

    $orders = wc_get_orders([
        'limit'   => $limit,
        'status'  => $status,
        'type'    => 'shop_order',
        'orderby' => 'date',
        'order'   => 'DESC',
    ]);

    return array_map(function($order) {
        if ($order instanceof WC_Order) {
            return [
                'id'       => $order->get_id(),
                'number'   => $order->get_order_number(),
                'customer' => $order->get_formatted_billing_full_name(),
                'total'    => $order->get_total(),
                'date'     => $order->get_date_created()->format('Y-m-d H:i:s'),
            ];
        }
        return null;
    }, $orders);
}

/**
 * Get the saved advanced settings for Final POS.
 *
 * @return array Saved advanced settings.
 */
function final_get_advanced_settings() {
    return get_option('final_pos_advanced_settings', []);
}
