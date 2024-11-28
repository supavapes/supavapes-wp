<?php
if (!defined('ABSPATH')) {
    exit;
}
function get_product_custom_fields($force_refresh = false) {
    global $wpdb;

    $cached_fields = wp_cache_get('product_custom_fields');
    if ($cached_fields !== false && !$force_refresh) {
        return $cached_fields;
    }

    // Direct database query is used here for performance reasons
    // as there's no WordPress function that provides this specific data efficiently
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
    $custom_fields = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT DISTINCT meta_key 
            FROM {$wpdb->postmeta} pm
            JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE p.post_type IN (%s, %s)",
            'product',
            'product_variation'
        )
    );

    $standard_fields = [
        '_price', '_regular_price', '_sale_price', '_sku', '_stock', 
        '_stock_status', '_visibility', '_featured', '_weight', 
        '_length', '_width', '_height', '_purchase_note', 
        '_sold_individually', '_backorders', '_manage_stock', 
        '_product_attributes', '_yoast_wpseo_focuskw', 
        '_yoast_wpseo_title', '_yoast_wpseo_metadesc',
    ];

    $all_fields = array_unique(array_merge($custom_fields, $standard_fields));
    $excluded_fields = ['total_sales'];
    $all_fields = array_diff($all_fields, $excluded_fields);
    sort($all_fields);

    wp_cache_set('product_custom_fields', array_values($all_fields), '', 12 * HOUR_IN_SECONDS);

    return array_values($all_fields);
}
function render_attribute_options($attribute_name, $is_visible, $is_variation) {
    ob_start();
    ?>
    <div class="attribute-options" style="margin-top: 10px;">
        <div class="toggle-container space">
            <label class="switch">
                <input type="checkbox" name="attribute_visibility[<?php echo esc_attr($attribute_name); ?>]" <?php checked($is_visible, true); ?>>
                <span class="slider"></span>
            </label>
            <span><?php echo esc_html(__('Visible on the product page', 'final-pos')); ?></span>
        </div>
        <div class="toggle-container space">
            <label class="switch">
                <input type="checkbox" name="attribute_variation[<?php echo esc_attr($attribute_name); ?>]" <?php checked($is_variation, true); ?>>
                <span class="slider"></span>
            </label>
            <span><?php echo esc_html(__('Used for variations', 'final-pos')); ?></span>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function render_variation($variation, $attributes) {
    ob_start();
    ?>
    <div class="variation" data-variation-id="<?php echo esc_attr($variation->get_id()); ?>">
        <h4><?php echo esc_html(__('Variation ID:', 'final-pos')); ?> <?php echo esc_html($variation->get_id()); ?> <span class="material-symbols-outlined delete-variation" data-variation-id="<?php echo esc_attr($variation->get_id()); ?>" title="<?php echo esc_html(__('Delete Variation', 'final-pos')); ?>">delete</span></h4>
        <?php
        foreach ($attributes as $attribute) {
            $attribute_name = $attribute->get_name();
            $attribute_label = wc_attribute_label($attribute_name);
            $variation_value = $variation->get_attribute($attribute_name);
            ?>
            <div class="variation-attribute">
                <label><?php echo esc_html($attribute_label); ?></label>
                <select name="variations[<?php echo esc_attr($variation->get_id()); ?>][attribute_<?php echo esc_attr($attribute_name); ?>]" class="variation-attribute-select select2">
                    <option value=""><?php echo esc_html(__('Any', 'final-pos')); ?> <?php echo esc_html($attribute_label); ?></option>
                    <?php foreach ($attribute->get_options() as $option) : ?>
                        <option value="<?php echo esc_attr($option); ?>" <?php selected($variation_value, $option); ?>><?php echo esc_html($option); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php
        }
        ?>
        <div class="variation-price">
            <label><?php echo esc_html(__('Price', 'final-pos')); ?></label>
            <input type="number" step="0.01" name="variations[<?php echo esc_attr($variation->get_id()); ?>][price]" value="<?php echo esc_attr($variation->get_price()); ?>">
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function update_product_variations($product, $variations) {
    foreach ($variations as $variation_id => $variation_data) {
        $variation = wc_get_product($variation_id);
        if ($variation && $variation->is_type('variation')) {
            $current_attributes = $variation->get_attributes();
            $new_attributes = [];

            foreach ($variation_data as $key => $value) {
                $value_cleaned = wc_clean($value);
                if ($key === 'price') {
                    $variation->set_regular_price($value_cleaned);
                    $variation->set_price($value_cleaned);
                } elseif ($key === 'sku' && !empty($value_cleaned)) {
                    $variation->set_sku($value_cleaned);
                } elseif (strpos($key, 'attribute_') === 0) {
                    $attribute_name = str_replace('attribute_', '', $key);
                    $new_attributes[$attribute_name] = $value_cleaned;
                }
            }

            $variation->set_attributes(array_merge($current_attributes, $new_attributes));
            $variation->save();
        }
    }
}

function create_new_variation($product, $new_variation) {
    if (!empty($new_variation['attributes'])) {
        $variation = new WC_Product_Variation();
        $variation->set_parent_id($product->get_id());
        
        $variation->set_attributes(array_map('wc_clean', $new_variation['attributes']));
        
        if (!empty($new_variation['price'])) {
            $variation->set_regular_price(wc_clean($new_variation['price']));
        }
        
        $variation->set_status('publish');
        $variation->save();
    }
}

function update_product_attributes($product, $attributes, $visibility, $variation) {
    $product_attributes = [];
    foreach ($attributes as $name => $values) {
        if (is_array($values)) {
            $attribute = new WC_Product_Attribute();
            $attribute->set_name(wc_clean($name));
            $attribute->set_options(array_map('wc_clean', $values));
            $attribute->set_visible(!empty($visibility[$name]));
            $attribute->set_variation(!empty($variation[$name]));
            $product_attributes[] = $attribute;
        }
    }
    $product->set_attributes($product_attributes);
}
