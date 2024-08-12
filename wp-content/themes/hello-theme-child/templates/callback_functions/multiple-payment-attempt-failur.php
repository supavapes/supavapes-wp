<?php
// check_ajax_referer('multiple_payment_attempt_nonce', 'security');

if(isset($_POST['nonce']) && !empty($_POST['nonce'])){
    $failure_attempt_nonce = sanitize_text_field($_POST['nonce']);
}

// Check nonce for security
if (!isset($failure_attempt_nonce) || !wp_verify_nonce($failure_attempt_nonce, 'failure_attempt_nonce')) {
    wp_send_json_error('Invalid nonce');
    return;
}
// Retrieve form data
if(isset($_POST['first_name']) && !empty($_POST['first_name'])){
    $first_name = sanitize_text_field($_POST['first_name']);
}
if(isset($_POST['last_name']) && !empty($_POST['last_name'])){
    $last_name = sanitize_text_field($_POST['last_name']);
}
if(isset($_POST['email']) && !empty($_POST['email'])){
    $email = sanitize_text_field($_POST['email']);
}
if(isset($_POST['phone']) && !empty($_POST['phone'])){
    $phone = sanitize_text_field($_POST['phone']);
}
if(isset($_POST['send_a_copy']) && !empty($_POST['send_a_copy'])){
    $send_a_copy = sanitize_text_field($_POST['send_a_copy']);
}
if(isset($_POST['comments']) && !empty($_POST['comments'])){
    $form_comments = sanitize_text_field($_POST['comments']);
}
// $phone = $_POST['phone'];
// $send_a_copy = $_POST['send_a_copy'];
// $comments = sanitize_text_field($_POST['comments']);

// Create the post title
$post_title = $first_name . ' ' . $last_name . ' - ' . current_time('Y-m-d H:i:s');

// Create the post array
$post_data = array(
    'post_title'    => $post_title,
    'post_content'  => $form_comments,
    'post_status'   => 'publish',
    'post_author'   => 1,
    'post_type'     => 'multiple_attempt',
);

// Insert the post into the database
$new_post_id = wp_insert_post($post_data);

// If post is created successfully, save the additional meta data
if ($new_post_id) {
    update_post_meta($new_post_id, 'first_name', $first_name);
    update_post_meta($new_post_id, 'last_name', $last_name);
    update_post_meta($new_post_id, 'email', $email);
    update_post_meta($new_post_id, 'phone', $phone);



    WC()->mailer()->emails['WC_Email_Payment_Fail_Multiple_Attempt_Admin_Order']->trigger( $new_post_id );

    // do_action('send_user_info_on_payment_attempt_fail_to_admin','Payment','Fail');

    if($send_a_copy === "true"){
        // do_action('send_user_info_on_payment_attempt_fail_to_user','Payment','Fail');
        
        WC()->mailer()->emails['WC_Email_Payment_Fail_Multiple_Attempt_Order']->trigger( $new_post_id );
    }

    wp_send_json_success(array('message' => 'Payment attempt failure recorded successfully!'));
} else {
    wp_send_json_error(array('message' => 'Failed to record payment attempt failure.'));
}

wp_die();