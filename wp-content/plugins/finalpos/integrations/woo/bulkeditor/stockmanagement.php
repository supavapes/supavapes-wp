<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . '../../../includes/api/sync.php';

global $sync_page_instance;
if (!isset($sync_page_instance) || !($sync_page_instance instanceof Sync_Page)) {
    $sync_page_instance = new Sync_Page();
}

add_action('plugins_loaded', function() {
    global $sync_page_instance;
    if (!isset($sync_page_instance) || !($sync_page_instance instanceof Sync_Page)) {
        $sync_page_instance = new Sync_Page();
    }
});

function render_stock_popup() {
    echo '
    <div id="popup-overlay" class="popup-overlay"></div>
    <div id="stock-popup" class="generic-popup" style="display:none;">
        <div class="generic-popup-header">
            <h2>' . esc_html__('Manage Stock', 'final-pos') . '</h2>
            <span class="material-symbols-outlined generic-popup-close">close</span>
        </div>
        <div class="generic-popup-content">
            <div class="info-message" style="display:none;">
                <span class="material-symbols-outlined">info</span>
                <span>' . esc_html__('The Settings below apply to all variations without stock management.', 'final-pos') . '</span>
            </div>
            <hr>
            <div class="stock-value-container">
                <label>' . esc_html__('Stock on hand', 'final-pos') . '</label>
                <span class="stock-value onhand"></span>
            </div>
            <hr class="divider">
            <div>
                <label for="stock-action">' . esc_html__('Stock action', 'final-pos') . '</label>
                <select id="stock-action">
                    <option value="" disabled selected>' . esc_html__('Select Action', 'final-pos') . '</option>
                    <option value="stock_received">' . esc_html__('Stock Received (Add Stock)', 'final-pos') . '</option>
                    <option value="restock_return">' . esc_html__('Restock Return (Add Stock)', 'final-pos') . '</option>
                    <option value="sale">' . esc_html__('Sale (Remove Stock)', 'final-pos') . '</option>
                    <option value="damage">' . esc_html__('Damage (Remove Stock)', 'final-pos') . '</option>
                    <option value="theft">' . esc_html__('Theft (Remove Stock)', 'final-pos') . '</option>
                    <option value="loss">' . esc_html__('Loss (Remove Stock)', 'final-pos') . '</option>
                    <option value="refund_damage">' . esc_html__('Refund Damage (Remove Stock)', 'final-pos') . '</option>
                    <option value="inventory_recount">' . esc_html__('Inventory Re-count (Stock on Hand)', 'final-pos') . '</option>
                </select>
            </div>
            <div>
                <label for="quantity">' . esc_html__('Quantity', 'final-pos') . '</label>
                <input type="number" id="quantity" min="0" step="1" placeholder="' . esc_html__('Enter Quantity of selected action', 'final-pos') . '">
            </div>
            <hr class="divider">
            <div class="stock-value-container">
                <label>' . esc_html__('New stock on hand', 'final-pos') . '</label>
                <span class="stock-value new" id="new-stock-on-hand"></span>
            </div>
            <hr>
            <label for="backorders">' . esc_html__('Allow backorders?', 'final-pos') . '</label>
            <select id="backorders">
                <option value="no">' . esc_html__('Do not allow', 'final-pos') . '</option>
                <option value="notify">' . esc_html__('Allow, but notify customers', 'final-pos') . '</option>
                <option value="yes">' . esc_html__('Allow', 'final-pos') . '</option>
            </select>
            <div class="stock-management-toggle">
                <label for="manage-stock" class="switch">
                    <input type="checkbox" id="manage-stock">
                    <span class="slider"></span>
                </label>
                <span>' . esc_html__(' Enable stock management', 'final-pos') . '</span>
            </div>
        </div>
        <div class="generic-popup-actions">
            <button class="cancel-button">' . esc_html__('Cancel', 'final-pos') . '</button>
            <button class="save-button">' . esc_html__('Save', 'final-pos') . '</button>
        </div>
    </div>';
}

function update_stock_data() {
    // Verify nonce first
    if (!check_ajax_referer('final_stock_management', 'nonce', false)) {
        wp_send_json_error(['message' => __('Invalid security token', 'final-pos')]);
        return;
    }

    global $sync_page_instance;
    
    if (!isset($sync_page_instance) || !($sync_page_instance instanceof Sync_Page)) {
        require_once plugin_dir_path(__FILE__) . '../../../includes/api/sync.php';
        $sync_page_instance = new Sync_Page();
    }

    // Validate and sanitize all POST inputs
    $product_id = isset($_POST['product_id']) ? absint(wp_unslash($_POST['product_id'])) : 0;
    $stock_quantity = isset($_POST['stock_quantity']) ? absint(wp_unslash($_POST['stock_quantity'])) : 0;
    $manage_stock = isset($_POST['manage_stock']) ? 
        sanitize_text_field(wp_unslash($_POST['manage_stock'])) === 'yes' : false;
    $backorders = isset($_POST['backorders']) ? 
        sanitize_text_field(wp_unslash($_POST['backorders'])) : 'no';
    $is_variation = isset($_POST['is_variation']) ? 
        filter_var(wp_unslash($_POST['is_variation']), FILTER_VALIDATE_BOOLEAN) : false;
    $base_action = isset($_POST['base_action']) ? 
        sanitize_text_field(wp_unslash($_POST['base_action'])) : '';
    $specific_action = isset($_POST['specific_action']) ? 
        sanitize_text_field(wp_unslash($_POST['specific_action'])) : '';
    $quantity = isset($_POST['quantity']) ? absint(wp_unslash($_POST['quantity'])) : 0;

    // Validate required fields
    if (!$product_id || !$base_action || !$specific_action || !$quantity) {
        wp_send_json_error(['message' => __('Missing required fields', 'final-pos')]);
        return;
    }

    $product = $is_variation ? new WC_Product_Variation($product_id) : wc_get_product($product_id);

    if (!$product) {
        wp_send_json_error(['message' => __('Product not found', 'final-pos')]);
        return;
    }

    // Call the API through Sync_Page
    $variant_id = $is_variation ? $product_id : null;
    $main_product_id = $is_variation ? $product->get_parent_id() : $product_id;

    try {
        $api_result = $sync_page_instance->update_stock_action(
            $main_product_id, 
            $variant_id, 
            $base_action, 
            $specific_action, 
            $quantity
        );
        
        if ($api_result['success']) {
            // Update local WooCommerce stock
            $product->set_stock_quantity($stock_quantity);
            $product->set_manage_stock($manage_stock);
            $product->set_backorders($backorders);
            $product->save();
            
            wp_send_json_success([
                'message' => __('Stock data updated successfully.', 'final-pos'),
                'new_stock' => $stock_quantity,
                'api_result' => $api_result
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Stock sync with Final failed, please check connection', 'final-pos'),
                'api_result' => $api_result
            ]);
        }
    } catch (Exception $e) {
        wp_send_json_error([
            'message' => __('Stock update failed: ', 'final-pos') . $e->getMessage(),
            'api_result' => isset($api_result) ? $api_result : null
        ]);
    }
}

add_action('wp_ajax_update_stock_data', 'update_stock_data');
add_action('wp_ajax_nopriv_update_stock_data', 'update_stock_data');
