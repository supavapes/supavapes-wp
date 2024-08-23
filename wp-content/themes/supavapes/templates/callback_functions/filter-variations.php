<?php
/**
 * This file is used to filter variations with ajax.
 */
defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

// Check nonce for security
if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'search_variations_nonce')) {
    wp_send_json_error('Invalid nonce');
    return;
}

if (!isset($_POST['search_query']) || !isset($_POST['product_id'])) {
    wp_send_json_error('Invalid request');
}
$search_query = sanitize_text_field($_POST['search_query']);
$product_id = intval($_POST['product_id']);
$filters = isset($_POST['filters']) ? sanitize_filters(sanitize_text_field($_POST['filters'])) : [];

$product = wc_get_product($product_id);
if (!$product || !$product->is_type('variable')) {
    wp_send_json_error('Invalid product');
}

$variations = $product->get_available_variations();
$filtered_variations = [];
foreach ($variations as $variation) {
    $variation_product = wc_get_product($variation['variation_id']);
    $variation_title = $variation_product->get_formatted_name();
    $parent_id = $product->get_parent_id();
    $product_title = get_the_title($parent_id);
    $variation_title = str_replace($product_title . " - ", " ", $variation_title);
    $variation_title = wp_strip_all_tags($variation_title);
    $match_search_query = stripos($variation_title, $search_query) !== false;
    $match_filters = true;
    foreach ($filters as $attribute_name => $attribute_value) {
        if (!empty($attribute_value)) {
            $attribute_slug = strtolower(str_replace(' ', '-', $attribute_name));
            $variation_attribute_value = $variation_product->get_attribute($attribute_slug);
            if (stripos($variation_attribute_value, $attribute_value) === false) {
                $match_filters = false;
                break;
            }
        }
    }
    if ($match_search_query && $match_filters) {
        $filtered_variations[] = $variation;
    }
}

$html = '';
foreach ($filtered_variations as $variation) {
    $variation_html = wqcmv_fetch_product_block_html($variation['variation_id']);
    if ($variation_html) {
        $html .= $variation_html;
    } else {
        $html .= '<!-- No HTML generated for variation ID: ' . $variation['variation_id'] . ' -->';
    }
}

if (empty($html)) {
    $html = '
    <div class="no-variations-found">
        <img src="/wp-content/uploads/2024/06/no-variation-found.svg" alt="No Variations Found" />
        <p>No variation found matching your search. Please try again.</p>
        <a href="javascript:void(0);" class="button reset-variation-filter">Reset Filter</a>
    </div>
    ';
    wp_send_json_success(['html' => $html, 'found' => false]);
} else {
    wp_send_json_success(['html' => $html, 'found' => true]);
}


function sanitize_filters($filters) {
    // If filters is not an array, return an empty array
    if (!is_array($filters)) {
        return [];
    }

    // Sanitize each filter value
    foreach ($filters as $key => $value) {
        // Assuming filters are simple text fields
        $filters[$key] = sanitize_text_field($value);
    }

    return $filters;
}