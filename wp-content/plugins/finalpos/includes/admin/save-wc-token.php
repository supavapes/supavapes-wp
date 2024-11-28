<?php
// save_wc_token.php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function finalpos_save_wc_token() {
    // Check if the request method is POST
    if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
        wp_send_json_error(__('Invalid request method.', 'final-pos'), 405);
        return; // Exit the function after sending the error response
    }

    // Read the JSON payload
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if ($data !== null && isset($data['consumer_key']) && isset($data['consumer_secret'])) {
        $consumer_key = sanitize_text_field($data['consumer_key']);
        $consumer_secret = sanitize_text_field($data['consumer_secret']);

        if (empty($consumer_key) || empty($consumer_secret)) {
            update_option('final_pos_error', __('Consumer key and secret cannot be empty.', 'final-pos'));
            wp_send_json_error(__('Invalid consumer key or secret.', 'final-pos'), 400);
            return; // Exit the function after sending the error response
        }

        // Save the Consumer Key and Consumer Secret separately
        update_option('final_pos_consumer_key', $consumer_key);
        update_option('final_pos_consumer_secret', $consumer_secret);

        // Save the combined version for compatibility (if needed)
        $combined = $consumer_key . '&&&' . $consumer_secret;
        update_option('final_pos_token', $combined);

        update_option('final_pos_error', '');

        wp_send_json_success(__('Consumer key and secret saved successfully.', 'final-pos'));
    } else {
        update_option('final_pos_error', __('Could not connect to WooCommerce store. Please try again.', 'final-pos'));
        wp_send_json_error(__('Invalid JSON payload.', 'final-pos'), 400);
    }
}

// Register the AJAX handlers for WooCommerce AJAX (wc-ajax)
add_action('wc_ajax_finalpos_save_token', 'finalpos_save_wc_token');
add_action('wc_ajax_nopriv_finalpos_save_token', 'finalpos_save_wc_token');