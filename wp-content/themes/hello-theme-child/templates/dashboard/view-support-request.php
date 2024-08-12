<?php 
defined('ABSPATH') || exit;
$request_id = get_query_var('view-request');
$support_request = get_post($request_id);
if (!$support_request || $support_request->post_type !== 'support_request') {
    wp_redirect(home_url('/my-account/'));
    exit;
}

$order_id = get_post_meta($request_id, '_order_id', true);
$new_order_id = get_post_meta($request_id, '_support_request_new_order_id', true);
$new_order = wc_get_order($new_order_id);
$order = wc_get_order($order_id);
if (!$order) {
    wp_redirect(home_url('/my-account/'));
    exit;
}
// Calculate the number of days since the support request was submitted
$request_date = new DateTime($support_request->post_date);
$today = new DateTime();
$interval = $today->diff($request_date);
$days_since_submitted = $interval->days;
?>
<div class="woocommerce-support-request-content">
    <h2><?php esc_html_e('Support Request','hello-elementor-child'); ?> #<?php echo esc_html($request_id); ?></h2>
    <p><strong><?php esc_html_e('Request Status:','hello-elementor-child'); ?></strong> <?php
    $approved = get_post_meta($request_id, '_support_request_approved', true);
    $declined = get_post_meta($request_id, '_support_request_declined', true);
    $sr_status = 'Pending';
    if ($approved) {
        $sr_status = 'Approved';
    } elseif ($declined) {
        $sr_status = 'Declined';
    }
    echo esc_html($sr_status);
    ?></p>
    <p><strong><?php esc_html_e('Request Details:','hello-elementor-child'); ?></strong> <?php echo nl2br(esc_html($support_request->post_content)); ?></p>
    <?php if ($sr_status === 'Approved'): ?>
        <p><?php esc_html_e('Your request is approved and a new order has been generated.'); ?><a href="<?php echo esc_url($new_order->get_view_order_url()); ?>"> <?php  esc_html_e('View Order','hello-elementor-child'); ?></a></p>
    <?php elseif ($sr_status === 'Declined'): ?>
        <p><?php esc_html_e('Your request has been declined.','hello-elementor-child'); ?></p>
        <p><?php esc_html_e('Reason: ','hello-elementor-child'); ?><?php echo esc_html(get_post_meta($request_id, '_support_request_decline_reason', true)); ?></p>
    <?php elseif ($sr_status === 'Pending' && $days_since_submitted > 0): ?>
        <p><?php esc_html_e('It has been ','hello-elementor-child'); ?><?php echo esc_html($days_since_submitted); ?><?php esc_html_e(' days since you submitted the request. Still did not get a response. Do you want to follow up?','hello-elementor-child') ?></p>
        <div class="follow-up-wrapper">
            <button id="follow-up-button" class="button"><?php esc_html_e('Follow Up','hello-elementor-child'); ?></button>
            <div id="follow-up-form" style="display: none;">
                <textarea id="follow-up-text" rows="4" cols="50"></textarea>
                <div id="follow-up-message"></div>
                <button id="follow-up-submit" class="button" data-request_id="<?php echo esc_attr($request_id); ?>"><?php esc_html_e('Submit','hello-elementor-child'); ?></button>
            </div>
        </div>
    <?php endif; ?>
    <section class="woocommerce-order-details">
        <h2 class="woocommerce-order-details__title"><?php esc_html_e('Support Request Details','hello-elementor-child'); ?></h2>
        <table class="woocommerce-table woocommerce-table--order-details shop_table order_details">
            <thead>
                <tr>
                    <th class="woocommerce-table__product-name product-name"><?php esc_html_e('Product','hello-elementor-child'); ?></th>
                    <th class="woocommerce-table__product-total product-total"><?php esc_html_e('Total','hello-elementor-child'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Retrieve the selected product IDs from the support request meta
                $selected_product_ids = get_post_meta($request_id, 'support_request_selected_product_ids', true);
                $selected_product_ids = explode(', ', $selected_product_ids);
                $subtotal = 0;
                if (!empty($selected_product_ids)) :
                    foreach ($selected_product_ids as $product_id) :
                        foreach ($order->get_items() as $item_id => $item) :
                            $order_item_id = $item->get_product_id();
                            $order_variation_id = $item->get_variation_id();
                            if ($order_item_id == $product_id || $order_variation_id == $product_id) :
                                $product = $item->get_product();
                                $product_name = $item->get_name();
                                $quantity = $item->get_quantity();
                                $total = $item->get_total();
                                $subtotal += $total;
                ?>
                <tr class="woocommerce-table__line-item order_item">
                    <td class="woocommerce-table__product-name product-name">
                        <?php echo esc_html($product_name); ?> <strong class="product-quantity">×&nbsp;<?php echo esc_html($quantity); ?></strong>
                    </td>
                    <td class="woocommerce-table__product-total product-total">
                        <span class="woocommerce-Price-amount amount"><?php echo wp_kses_post(wc_price($total)); ?></span>
                    </td>
                </tr>
                <?php
                            endif;
                        endforeach;
                    endforeach;
                else :
                ?>
                <tr class="woocommerce-table__line-item order_item">
                    <td class="woocommerce-table__product-name product-name" colspan="2">
                    <?php esc_html_e('No products found for this request.','hello-elementor-child'); ?>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th scope="row"><?php esc_html_e('Subtotal:','hello-elementor-child'); ?></th>
                    <td><span class="woocommerce-Price-amount amount"><?php echo wp_kses_post(wc_price($subtotal)); ?></span></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Total:','hello-elementor-child'); ?></th>
                    <td><span class="woocommerce-Price-amount amount"><?php echo wp_kses_post(wc_price($subtotal)); ?></span></td>
                </tr>
            </tfoot>
        </table>
    </section>
</div>