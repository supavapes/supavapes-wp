<?php
$user_id = get_current_user_id();
// Query the support_request posts
$args = array(
    'post_type'      => 'support_request',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'author' => $user_id,
);

$support_requests = new WP_Query($args);

if ($support_requests->have_posts()) {
    echo '<table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">';
    echo '<thead>';
    echo '<tr>';
    echo '<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-number"><span class="nobr">Request ID</span></th>';
    echo '<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-date"><span class="nobr">Requested Date</span></th>';
    echo '<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-status"><span class="nobr">Request Status</span></th>';
    echo '<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-actions"><span class="nobr">Action</span></th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    while ($support_requests->have_posts()) {
        $support_requests->the_post();
        $sr_id = get_the_ID();
        $post_title = get_the_title();
        $post_date = get_the_date();
        
        // Get request status from post meta
        $approved = get_post_meta($sr_id, '_support_request_approved', true);
        $declined = get_post_meta($sr_id, '_support_request_declined', true);
        $sr_status = 'Pending';

        if ($approved) {
            $sr_status = 'Approved';
        } elseif ($declined) {
            $sr_status = 'Declined';
        }

        $view_url = home_url('/my-account/view-request/' . $sr_id); // Updated URL

        echo '<tr class="woocommerce-orders-table__row woocommerce-orders-table__row--status-' . esc_html($sr_status) . ' order">';
        echo '<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-number" data-title="Request ID"<a href="' . esc_url($view_url) . '" class="woocommerce-button button">#' . esc_html($sr_id) . '</a></td>';
        echo '<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-date" data-title="Requested Date">' . esc_html($post_date) . '</td>';
        echo '<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-status" data-title="Request Status">' . esc_html($sr_status) . '</td>';
        echo '<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-actions" data-title="Actions"><a href="' . esc_url($view_url) . '" class="woocommerce-button button view">View</a></td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
} else {
    wc_print_notice('No support requests found.', 'notice');
}

wp_reset_postdata();