<?php

if (!defined('ABSPATH')) {
    exit;
}



// Function to display admin notices
function final_admin_notice() {
    if (!final_is_woocommerce_active()) {
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e('Final POS requires WooCommerce, please go to your', 'final-pos'); ?> <a href="<?php echo esc_url(admin_url('plugins.php')); ?>"><?php esc_html_e('Plugins', 'final-pos'); ?></a> <?php esc_html_e('and make sure it is installed and active.', 'final-pos'); ?></p>
        </div>
        <?php
    }

    // Check for missing required fields
    $missing_fields = final_check_required_fields();
    if (!empty($missing_fields)) {
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e('Final POS requires the following fields to be filled out:', 'final-pos'); ?></p>
            <ul>
                <?php foreach ($missing_fields as $field): ?>
                    <li><?php echo esc_html($field); ?></li>
                <?php endforeach; ?>
            </ul>
            <p>
                <?php 
                printf(
                    /* translators: %1$s: WooCommerce settings URL, %2$s: closing tag */
                    esc_html__('Please go to your %1$sWooCommerce settings%2$s and fill out these fields.', 'final-pos'),
                    '<a href="' . esc_url(admin_url('admin.php?page=wc-settings')) . '">',
                    '</a>'
                );
                ?>
            </p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'final_admin_notice');

// Function to check required fields
function final_check_required_fields() {
    $missing_fields = [];

    // Check currency
    if (empty(get_woocommerce_currency())) {
        $missing_fields[] = __('Currency', 'final-pos');
    }

    // Check language
    if (empty(get_locale())) {
        $missing_fields[] = __('Language', 'final-pos');
    }

    // Check timezone
    if (empty(wp_timezone_string())) {
        $missing_fields[] = __('Default Timezone', 'final-pos');
    }

    // Check tax inclusive setting
    $tax_display = get_option('woocommerce_tax_display_shop');
    if (empty($tax_display)) {
        $missing_fields[] = __('Tax Display in Shop', 'final-pos');
    }

    // Check store address
    $address_fields = [
        'woocommerce_store_address' => __('Store Address', 'final-pos'),
        'woocommerce_store_city' => __('Store City', 'final-pos'),
        'woocommerce_default_country' => __('Store Country', 'final-pos'),
        'woocommerce_store_postcode' => __('Store Postcode', 'final-pos'),
    ];

    foreach ($address_fields as $option => $label) {
        if (empty(get_option($option))) {
            $missing_fields[] = $label;
        }
    }

    return $missing_fields;
}








add_action('woocommerce_update_order', 'set_order_attribution_final_pos', 10, 1);

function set_order_attribution_final_pos($order_id) {
    remove_action('woocommerce_update_order', 'set_order_attribution_final_pos', 10);

    $order = wc_get_order($order_id);
    if (!$order || !$order->get_meta('pos_id')) {
        return;
    }

    $current_source = $order->get_meta('_wc_order_attribution_source_type');
    
    if ($current_source !== 'utm') {
        // Standard WooCommerce 
        $order->update_meta_data('_wc_order_attribution_source_type', 'utm');
        $order->update_meta_data('_wc_order_attribution_utm_source', 'final-pos');
        
        $order->save();
    }

    add_action('woocommerce_update_order', 'set_order_attribution_final_pos', 10, 1);
}




/* SYNC CATEGORY STATUS */

// Neue Spalte zur Produktkategorie-Tabelle hinzufügen
add_filter('manage_edit-product_cat_columns', 'add_product_cat_final_column');
function add_product_cat_final_column($columns) {
    $new_columns = array();
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'description') {
            $new_columns['final_sync'] = 'final-pos';
        }
    }
    return $new_columns;
}

// Inhalt für die neue Spalte hinzufügen
add_action('manage_product_cat_custom_column', 'add_product_cat_final_column_content', 10, 3);
function add_product_cat_final_column_content($content, $column_name, $term_id) {
    if ($column_name !== 'final_sync') {
        return $content;
    }

    $final_sync = get_term_meta($term_id, 'final_sync', true);
    if ($final_sync) {
        return '<span style="color: var(--uxlabs-base-color);">Synced</span>';
    } else {
        return '<span style="color: var(--uxlabs-text-color);">No</span>';
    }
}
