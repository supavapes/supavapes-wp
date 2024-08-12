<?php

defined('ABSPATH') || exit;
// die('lkoo');


get_header();
$request_id = get_query_var('view-request');
$support_request = get_post($request_id);

if (!$support_request || $support_request->post_type != 'support_request') {
    wp_redirect(home_url('/my-account/'));
    exit;
}

//get_header('myaccount'); ?>

<div class="woocommerce-MyAccount-content">
    <h2>Support Request #<?php echo $request_id; ?></h2>
    
    <p><strong>Request Status:</strong> <?php 
    $approved = get_post_meta($request_id, '_support_request_approved', true);
    $declined = get_post_meta($request_id, '_support_request_declined', true);
    $status = 'Pending';

    if ($approved) {
        $status = 'Approved';
    } elseif ($declined) {
        $status = 'Declined';
    }
    echo esc_html($status);
    ?></p>

    <p><strong>Request Details:</strong> <?php echo nl2br(esc_html($support_request->post_content)); ?></p>
    
    <!-- Add any additional information you want to display here -->

</div>

<?php 
get_footer();
//get_footer('myaccount'); ?>
