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

function replace_stock_field_with_popup_trigger() {
    global $post;
    $product = wc_get_product($post->ID);
    
    if (!$product) {
        return;
    }

    $stock_quantity = $product->get_stock_quantity();
    $manage_stock = $product->get_manage_stock() ? 'yes' : 'no';
    $backorders = $product->get_backorders();

    echo '<div class="options_group show_if_simple show_if_variable">';
    echo '<p class="form-field _stock_field">';
    echo '<label for="stock_management_trigger">' . esc_html__('Stock quantity', 'final-pos') . '</label>';
    echo '<span id="stock_management_trigger" class="button">' . esc_html__('Manage Stock', 'final-pos') . '</span>';
    echo '<input type="hidden" id="_stock" name="_stock" value="' . esc_attr($stock_quantity) . '">';
    echo '<input type="hidden" id="_manage_stock" name="_manage_stock" value="' . esc_attr($manage_stock) . '">';
    echo '<input type="hidden" id="_backorders" name="_backorders" value="' . esc_attr($backorders) . '">';
    echo '<span class="current-stock" style="margin-left: 10px;">Stock on hand: <strong>' . esc_html($stock_quantity) . '</strong></span>'; // Aktuellen Lagerbestand inline anzeigen
    echo '</p>';
    echo '</div>';

    // Enqueue necessary scripts and styles
    wp_enqueue_script('final-stock-management', plugin_dir_url(__FILE__) . '../../../assets/js/woo/bulkeditor/popup.js', ['jquery'], '1.0', true);
    wp_enqueue_style('final-stock-management', plugin_dir_url(__FILE__) . '../../../assets/css/woo/bulkeditor-popup.css', [], '1.0'); // Added version parameter

    wp_localize_script('final-stock-management', 'finalStockManagement', array(
        'nonce' => wp_create_nonce('final_stock_management'),
        'ajaxurl' => admin_url('admin-ajax.php')
    ));

    // Add inline script to initialize the popup
    add_action('admin_footer', 'add_stock_management_popup_script');
}

function add_variation_stock_management_button($loop, $variation_data, $variation) {
    $variation_object = wc_get_product($variation->ID);
    $stock_quantity = $variation_object->get_stock_quantity();
    $manage_stock = $variation_object->get_manage_stock() ? 'yes' : 'no';
    $backorders = $variation_object->get_backorders();

    echo '<p class="form-row form-row-first">';
    // Hinzufügen des Labels für "Manage Stock" in Fettschrift
    echo '<label for="manage_stock_' . esc_attr($variation->ID) . '" style="font-weight: bold;display: block;margin-bottom: 10px;">' . esc_html__('Stock', 'final-pos') . '</label>';
    // Button und Lagerbestand inline anzeigen
    echo '<span class="button variation_manage_stock" 
          data-variation-id="' . esc_attr($variation->ID) . '" 
          data-stock-quantity="' . esc_attr($stock_quantity) . '" 
          data-manage-stock="' . esc_attr($manage_stock) . '" 
          data-backorders="' . esc_attr($backorders) . '">
        ' . esc_html__('Manage Stock', 'final-pos') . '
    </span>';
    echo '<span class="current-stock" style="margin-left: 10px;">Stock on hand: <strong>' . esc_html($stock_quantity) . '</strong></span>'; // Aktuellen Lagerbestand inline anzeigen
    echo '</p>';
}

function add_stock_management_popup_script() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Delegierte Event-Handler für Variation-Buttons
        $(document).on('click', '#stock_management_trigger, .variation_manage_stock', function() {
            const isVariation = $(this).hasClass('variation_manage_stock');
            const productId = isVariation ? $(this).data('variation-id') : $('#post_ID').val();
            const mainProductId = $('#post_ID').val();
            const stockQuantity = isVariation ? $(this).data('stock-quantity') : $('#_stock').val();
            const manageStock = isVariation ? $(this).data('manage-stock') : $('#_manage_stock').val();
            const backorders = isVariation ? $(this).data('backorders') : $('#_backorders').val();

            openStockManagementPopup(productId, stockQuantity, manageStock, backorders, isVariation, mainProductId);
        });

        function openStockManagementPopup(productId, stockQuantity, manageStock, backorders, isVariation = false, mainProductId = null) {
            const popup = $('#stock-popup');
            
            popup.find('.stock-value.onhand').text(stockQuantity);
            popup.find('#new-stock-on-hand').text(stockQuantity);
            popup.find('#manage-stock').prop('checked', manageStock === 'yes');
            popup.find('#backorders').val(backorders);

            $('#popup-overlay').fadeIn();
            popup.fadeIn();

            // Update new stock on hand based on action and quantity
            $('#stock-action, #quantity').on('change', function() {
                const action = $('#stock-action').val();
                const quantity = parseInt($('#quantity').val(), 10) || 0;
                let newStock = parseInt(stockQuantity, 10);

                if (['stock_received', 'restock_return'].includes(action)) {
                    newStock += quantity;
                } else if (action === 'inventory_recount') {
                    newStock = quantity;
                } else if (['damage', 'theft', 'loss', 'sale', 'refund_damage'].includes(action)) {
                    newStock -= quantity;
                }

                $('#new-stock-on-hand').text(newStock); // Update the new stock on hand
                $('.current-stock strong').text(newStock); // Update the displayed stock on hand next to the button
            });

            // Handle save button click
            popup.find('.save-button').off('click').on('click', function() {
                const newStockQuantity = parseInt($('#new-stock-on-hand').text(), 10);
                const newManageStock = $('#manage-stock').is(':checked') ? 'yes' : 'no';
                const newBackorders = $('#backorders').val();
                const stockAction = $('#stock-action').val();
                const actionQuantity = parseInt($('#quantity').val(), 10);

                // Optimized action determination
                const actionMap = {
                    'stock_received': { baseAction: 'ADD', quantity: actionQuantity },
                    'restock_return': { baseAction: 'ADD', quantity: actionQuantity },
                    'sale': { baseAction: 'REMOVE', quantity: -actionQuantity },
                    'damage': { baseAction: 'REMOVE', quantity: -actionQuantity },
                    'theft': { baseAction: 'REMOVE', quantity: -actionQuantity },
                    'loss': { baseAction: 'REMOVE', quantity: -actionQuantity },
                    'refund_damage': { baseAction: 'REMOVE', quantity: -actionQuantity },
                    'inventory_recount': { baseAction: 'RECOUNT', quantity: newStockQuantity }
                };

                const action = actionMap[stockAction];
                if (!action) {
                    console.error('Invalid stock action:', stockAction);
                    alert('Invalid stock action');
                    return;
                }

                const { baseAction, quantity } = action;
                const specificAction = stockAction.toUpperCase();

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'final_update_stock_data',
                        nonce: finalStockManagement.nonce,  // Add the nonce here
                        product_id: productId,
                        main_product_id: mainProductId,
                        stock_quantity: newStockQuantity,
                        manage_stock: newManageStock,
                        backorders: newBackorders,
                        is_variation: isVariation,
                        base_action: baseAction,
                        specific_action: specificAction,
                        quantity: quantity
                    },
                    success: function(response) {
                        if (response.success) {
                            if (response.data && response.data.api_result) {
                                console.log('API result:', response.data.api_result);
                                if (!response.data.api_result.success) {
                                    alert('Error updating stock in FinalPOS: ' + JSON.stringify(response.data.api_result.response));
                                }
                            }
                            updateLocalUI(isVariation, productId, newStockQuantity, newManageStock, newBackorders);
                            closeStockManagementPopup();
                        } else {
                            alert('Error updating stock data: ' + (response.data ? response.data.message : 'Unknown error'));
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error('AJAX error:', textStatus, errorThrown);
                    }
                });
            });

            // Handle cancel and close buttons
            popup.find('.cancel-button, .generic-popup-close').off('click').on('click', closeStockManagementPopup);

            // Close popup when clicking on overlay
            $('#popup-overlay').off('click').on('click', closeStockManagementPopup);

            // Prevent closing when clicking inside the popup
            popup.on('click', function(e) {
                e.stopPropagation();
            });
        }

        function closeStockManagementPopup() {
            $('#popup-overlay').fadeOut();
            $('#stock-popup').fadeOut();
        }

        function updateLocalUI(isVariation, productId, newStockQuantity, newManageStock, newBackorders) {
            if (isVariation) {
                const $trigger = $(`.variation_manage_stock[data-variation-id="${productId}"]`);
                $trigger.data({
                    'stock-quantity': newStockQuantity,
                    'manage-stock': newManageStock,
                    'backorders': newBackorders
                });
                // Update only the specific variation's stock input fields
                $trigger.closest('.woocommerce_variation').find('input[name^="variable_stock"]').val(newStockQuantity);
                $trigger.closest('.woocommerce_variation').find('input[name^="variable_manage_stock"]').val(newManageStock);
                $trigger.closest('.woocommerce_variation').find('select[name^="variable_backorders"]').val(newBackorders);
                
                // Update the displayed stock for the specific variation
                $trigger.closest('.form-row').find('.current-stock strong').text(newStockQuantity);
            } else {
                $('#_stock').val(newStockQuantity);
                $('#_manage_stock').val(newManageStock);
                $('#_backorders').val(newBackorders);
                
                // Update the displayed stock for the main product
                $('.current-stock strong').text(newStockQuantity);
            }
        }
    });
    </script>
    <?php
}

add_action('woocommerce_product_options_inventory_product_data', 'replace_stock_field_with_popup_trigger', 9);
add_action('woocommerce_variation_options_inventory', 'add_variation_stock_management_button', 11, 3);
add_action('admin_footer', 'render_stock_popup');
add_action('wp_ajax_final_update_stock_data', 'final_update_stock_data_ajax_handler');
add_action('wp_ajax_nopriv_final_update_stock_data', 'final_update_stock_data_ajax_handler');

function final_update_stock_data_ajax_handler() {
    // Verify nonce first
    if (!check_ajax_referer('final_stock_management', 'nonce', false)) {
        wp_send_json_error(['message' => 'Invalid security token']);
        return;
    }

    global $sync_page_instance;
    
    if (!isset($sync_page_instance) || !($sync_page_instance instanceof Sync_Page)) {
        require_once plugin_dir_path(__FILE__) . '../../../includes/api/sync.php';
        $sync_page_instance = new Sync_Page();
    }

    // Validate and sanitize all POST inputs
    $product_id = isset($_POST['product_id']) ? absint(wp_unslash($_POST['product_id'])) : 0;
    $main_product_id = isset($_POST['main_product_id']) ? absint(wp_unslash($_POST['main_product_id'])) : 0;
    $new_stock_quantity = isset($_POST['stock_quantity']) ? absint(wp_unslash($_POST['stock_quantity'])) : 0;
    $manage_stock = isset($_POST['manage_stock']) ? sanitize_text_field(wp_unslash($_POST['manage_stock'])) === 'yes' : false;
    $backorders = isset($_POST['backorders']) ? sanitize_text_field(wp_unslash($_POST['backorders'])) : '';
    $is_variation = isset($_POST['is_variation']) ? sanitize_text_field(wp_unslash($_POST['is_variation'])) === 'true' : false;
    $base_action = isset($_POST['base_action']) ? sanitize_text_field(wp_unslash($_POST['base_action'])) : '';
    $specific_action = isset($_POST['specific_action']) ? sanitize_text_field(wp_unslash($_POST['specific_action'])) : '';
    $quantity = isset($_POST['quantity']) ? absint(wp_unslash($_POST['quantity'])) : 0;

    // Validate required fields
    if (!$product_id || !$main_product_id || !$base_action || !$specific_action) {
        wp_send_json_error(['message' => 'Missing required fields']);
        return;
    }

    $product = wc_get_product($product_id);
    if (!$product) {
        wp_send_json_error(['message' => 'Product not found']);
        return;
    }

    // Call the API through Sync_Page
    $variant_id = $is_variation ? $product_id : null;
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
            $product->set_stock_quantity($new_stock_quantity);
            $product->set_manage_stock($manage_stock);
            $product->set_backorders($backorders);
            $product->save();
            
            wp_send_json_success([
                'new_stock' => $new_stock_quantity,
                'api_result' => $api_result
            ]);
        } else {
            wp_send_json_error([
                'message' => 'Stock sync with Final failed, please check connection',
                'api_result' => $api_result
            ]);
        }
    } catch (Exception $e) {
        wp_send_json_error([
            'message' => 'Stock update failed: ' . $e->getMessage(),
            'api_result' => isset($api_result) ? $api_result : null
        ]);
    }
}














