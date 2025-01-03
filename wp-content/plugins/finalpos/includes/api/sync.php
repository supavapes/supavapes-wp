<?php

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}



// Prevent direct file execution
if (!defined('DOING_AJAX') && !defined('DOING_CRON')) {
    if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
        die('Sorry, you are not allowed to access this file directly.');
    }
}

class Sync_Page {
    private $api_base_url = 'https://services.finalpos.com/v1/api/';
    private $token;
    private $data;

    public function __construct() {
        $this->token = get_option('final_pos_access_token');
        $this->data = [
            'url' => get_site_url() . '/', // Geändert: Dynamische URL statt hartcodierter URL
            'key' => get_option('final_pos_consumer_key'),
            'secret' => get_option('final_pos_consumer_secret'),
            'platform' => 'woo-commerce'
        ];

        // add_action('admin_menu', [$this, 'add_sync_page']);
        add_action('admin_init', array($this, 'check_and_run_initial_sync'));
    }

    public function add_sync_page() {
        add_submenu_page('tools.php', 'Final Sync API Debug', 'Final API Debug', 'manage_options', 'sync-api-page', [$this, 'render_sync_page']);
    }

    public function render_sync_page() {
        // Verify nonces before processing any POST data
        if (!isset($_POST['sync_setup_nonce']) && 
            !isset($_POST['sync_api_nonce']) && 
            !isset($_POST['stock_action_nonce'])) {
            $this->display_sync_page();
            return;
        }

        // Check which nonce is present and verify it
        if (isset($_POST['sync_setup_nonce'])) {
            if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['sync_setup_nonce'])), 'sync_setup_action')) {
                wp_die('Invalid nonce');
            }
            $this->handle_setup_request();
        } elseif (isset($_POST['sync_api_nonce'])) {
            if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['sync_api_nonce'])), 'sync_api_action')) {
                wp_die('Invalid nonce');
            }
            $sync_type = isset($_POST['sync_type']) ? sanitize_text_field(wp_unslash($_POST['sync_type'])) : '';
            if (!empty($sync_type)) {
                $this->handle_sync_request($sync_type);
            }
        } elseif (isset($_POST['stock_action_nonce'])) {
            if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['stock_action_nonce'])), 'stock_action_test')) {
                wp_die('Invalid nonce');
            }
            // Sanitize and validate all POST inputs
            $stock_action = isset($_POST['stock_action']) ? sanitize_text_field(wp_unslash($_POST['stock_action'])) : '';
            $stock_quantity = isset($_POST['stock_quantity']) ? absint(wp_unslash($_POST['stock_quantity'])) : 0;
            $product_id = isset($_POST['product_id']) ? absint(wp_unslash($_POST['product_id'])) : 0;
            $variant_id = !empty($_POST['variant_id']) ? absint(wp_unslash($_POST['variant_id'])) : null;

            if (!empty($stock_action) && $stock_quantity > 0 && $product_id > 0) {
                $this->handle_stock_action_request(
                    $stock_action,
                    $stock_quantity,
                    $product_id,
                    $variant_id
                );
            }
        }

        $this->display_sync_page();
    }

    private function display_sync_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Sync API Page', 'final-pos'); ?></h1>
            
            <!-- New Setup button -->
            <form method="post" action="">
                <?php wp_nonce_field('sync_setup_action', 'sync_setup_nonce'); ?>
                <h2><?php esc_html_e('Setup', 'final-pos'); ?></h2>
                <p>
                    <button type="submit" name="sync_type" value="setup" class="button button-primary"><?php esc_html_e('Setup Sync', 'final-pos'); ?></button>
                </p>
            </form>

            <!-- Existing Sync Actions form -->
            <form method="post" action="">
                <?php wp_nonce_field('sync_api_action', 'sync_api_nonce'); ?>
                <h2><?php esc_html_e('Sync Actions', 'final-pos'); ?></h2>
                <p>
                    <?php
                    $sync_actions = [
                        'all' => __('Sync All', 'final-pos'),
                        'customers' => __('Sync Customers', 'final-pos'),
                        'products' => __('Sync Products', 'final-pos'),
                        'orders' => __('Sync Orders', 'final-pos')
                    ];
                    foreach ($sync_actions as $value => $label):
                        ?>
                        <button type="submit" name="sync_type" value="<?php echo esc_attr($value); ?>" class="button <?php echo $value === 'all' ? 'button-primary' : 'button-secondary'; ?>">
                            <?php echo esc_html($label); ?>
                        </button>
                    <?php endforeach; ?>
                </p>
            </form>

            <h2><?php esc_html_e('Test Stock Actions', 'final-pos'); ?></h2>
            <form method="post" action="">
                <?php wp_nonce_field('stock_action_test', 'stock_action_nonce'); ?>
                <p style="display: flex; align-items: center; gap: 10px;">
                    <label for="product_id"><?php esc_html_e('Product ID:', 'final-pos'); ?></label>
                    <input type="number" id="product_id" name="product_id" value="9155" min="1" step="1" required style="width: 100px;">
                    
                    <label for="variant_id"><?php esc_html_e('Variant ID (optional):', 'final-pos'); ?></label>
                    <input type="number" id="variant_id" name="variant_id" value="9156" min="1" step="1" style="width: 100px;">
                    
                    <label for="stock_quantity"><?php esc_html_e('Quantity:', 'final-pos'); ?></label>
                    <input type="number" id="stock_quantity" name="stock_quantity" value="1" min="1" step="1" required style="width: 80px;">
                </p>
                <p>
                    <?php
                    $stock_actions = ['ADD_STOCK_RECEIVED', 'ADD_RESTOCK_RETURN', 'REMOVE_SALE', 'REMOVE_DAMAGE', 'REMOVE_THEFT', 'REMOVE_LOSS', 'REMOVE_REFUND_DAMAGE', 'RECOUNT_INVENTORY_RECOUNT'];
                    foreach ($stock_actions as $action):
                        ?>
                        <button type="submit" name="stock_action" value="<?php echo esc_attr($action); ?>" class="button button-secondary">
                            <?php echo esc_html(str_replace('_', ' ', $action)); ?>
                        </button>
                    <?php endforeach; ?>
                </p>
            </form>
        </div>
        <?php
    }

    public function is_valid_request() {
        return $this->is_valid_nonce('sync_api_nonce', 'sync_api_action');
    }

    public function is_valid_stock_action_request() {
        return $this->is_valid_nonce('stock_action_nonce', 'stock_action_test');
    }

    public function is_valid_setup_request() {
        return $this->is_valid_nonce('sync_setup_nonce', 'sync_setup_action');
    }

    private function is_valid_nonce($nonce_name, $action) {
        if (!isset($_POST[$nonce_name])) {
            return false;
        }

        $nonce = sanitize_text_field(wp_unslash($_POST[$nonce_name]));
        return wp_verify_nonce($nonce, $action);
    }

    public function handle_sync_request($sync_type) {
        $sync_methods = [
            'all' => 'handle_sync_all',
            'customers' => 'handle_sync_customers',
            'products' => 'handle_sync_products',
            'orders' => 'handle_sync_orders'
        ];

        if (array_key_exists($sync_type, $sync_methods)) {
            $this->{$sync_methods[$sync_type]}();
        }
    }

    public function handle_sync_all() {
        $sync_types = ['customers', 'products', 'orders'];
        $results = [];
        $overall_success = true;

        foreach ($sync_types as $sync_type) {
            $method = "handle_sync_$sync_type";
            $result = $this->$method();
            $results[$sync_type] = $result;
            
            if (!$this->is_sync_successful($result)) {
                $overall_success = false;
            }
            
            sleep(1);
        }

        $this->display_sync_all_results($results);

        return array('success' => $overall_success, 'results' => $results);
    }

    public function handle_sync_customers() {
        return $this->make_api_request($this->build_sync_url('customers'));
    }

    public function handle_sync_products() {
        return $this->make_api_request($this->build_sync_url('products'));
    }

    public function handle_sync_orders() {
        return $this->make_api_request($this->build_sync_url('orders'));
    }

    private function display_sync_all_results($results) {
        echo "<div class='notice notice-info'><p><strong>Sync All Results:</strong></p>";
        foreach ($results as $sync_type => $result) {
            $status = $this->get_sync_status($result);
            echo "<p>" . esc_html($sync_type) . ": " . esc_html($status) . "</p>";
        }
        echo "</div>";
    }

    private function build_sync_url($endpoint) {
        $company_id = get_option('final_pos_company_id');
        $from_date = get_option('final_pos_order_timeframe_start');
        $categories_param = $this->get_categories_param();
        
        if ($endpoint === '') {
            // For 'Sync All', don't include the categories parameter
            return "{$this->api_base_url}sync/{$company_id}?fromDate={$from_date}";
        } else {
            $url = "{$this->api_base_url}sync/sync/{$endpoint}/{$company_id}?fromDate={$from_date}";
            // Only add categories parameter for products sync and if categories are selected
            if ($endpoint === 'products' && !empty($categories_param)) {
                $url .= "&categories={$categories_param}";
            }
            return $url;
        }
    }

    public function get_categories_param() {
        // Get the selected categories from the option
        $selected_categories = get_option('final_pos_category_sync');
        
        // Check if the option exists and is not empty
        if (!empty($selected_categories)) {
            // Decode the JSON string if it's stored as a JSON string
            if (is_string($selected_categories)) {
                $selected_categories = json_decode($selected_categories, true);
            }
            
            // Ensure all category IDs are integers
            $categories = array_map('intval', $selected_categories);
            
            // Return the comma-separated list of category IDs
            return implode(',', $categories);
        }
        
        // If no categories are selected, return an empty string
        return '';
    }



    public function make_api_request($url, $custom_data = null) {
        $data = $custom_data ?? $this->data;
        
        // Get categories and add them to the data
        $categories = $this->get_categories_param();
        $data['categories'] = !empty($categories) ? explode(',', $categories) : [];
        
        $args = array(
            'method'  => 'PUT',
            'headers' => array(
                'accept' => 'application/json',
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json'
            ),
            'body'    => wp_json_encode($data),
            'timeout' => 60,
        );

        $start_time = microtime(true);
        $response = wp_remote_request($url, $args);
        $execution_time = round(microtime(true) - $start_time, 2);

        if (is_wp_error($response)) {
            $this->log_request($url, $data, 0, $response->get_error_message(), $response->get_error_code(), $execution_time);
            $this->display_error_message($response->get_error_message(), $response->get_error_code());
        } else {
            $http_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $this->log_request($url, $data, $http_code, '', '', $execution_time);
            $this->handle_api_response($body, $http_code, '', '', $execution_time);
        }

        return is_wp_error($response) ? false : $body;
    }

    public function log_request($url, $data, $http_code, $curl_error, $curl_errno, $execution_time) {
        // Log request details to a custom logging function or database instead of error_log
        // Example: my_custom_log_function([
        //     'url' => $url,
        //     'data' => $data,
        //     'http_code' => $http_code,
        //     'curl_error' => $curl_error,
        //     'curl_errno' => $curl_errno,
        //     'execution_time' => $execution_time
        // ]);
    }

    public function handle_api_response($response, $http_code, $curl_error, $curl_errno, $execution_time) {
        // Instead of console.log, consider using a custom logging mechanism
        // Example: my_custom_log_function([
        //     'http_code' => $http_code,
        //     'response' => $response,
        //     'execution_time' => $execution_time,
        //     'curl_error' => $curl_error,
        //     'curl_errno' => $curl_errno
        // ]);

        if ($curl_errno) {
            $this->display_error_message($curl_error, $curl_errno);
        } else {
            $this->display_success_message($response, $http_code, $execution_time);
        }
    }

    public function display_error_message($curl_error, $curl_errno) {
        echo '<div class="notice notice-error"><p>' . esc_html("Error: $curl_error (Error Code: $curl_errno)") . '</p></div>';
    }

    public function display_success_message($response, $http_code, $execution_time) {
        $status = $this->get_sync_status($response);
        $message = $this->format_response_message($response);
        echo "<div class='notice " . esc_attr($status === 'Success' ? 'notice-success' : 'notice-warning') . "'><p>
            <strong>Sync Status:</strong> " . esc_html($status) . "<br>
            <strong>Response Code:</strong> " . esc_html($http_code) . "<br>
            <strong>Execution Time:</strong> " . esc_html($execution_time) . " seconds<br>
            <strong>Response:</strong><br>" . esc_html($message) . "
        </p></div>";
    }

    public function update_stock_action($product_id, $variant_id, $base_action, $specific_action, $quantity) {
        // Adjust quantity based on base_action
        $adjusted_quantity = ($base_action === 'REMOVE') ? -abs($quantity) : abs($quantity);
        
        // Check if product is a variant and build URL accordingly
        $product = wc_get_product($product_id);
        $url = $this->api_base_url . "product/update-woo-stock/";
        
        if ($product->get_type() === 'variation' || !empty($variant_id)) {
            // For variants, include both IDs
            $url .= "{$product_id}/{$variant_id}";
        } else {
            // For simple products, only include product ID
            $url .= $product_id;
        }

        $data = [
            'baseAction' => $base_action, 
            'specificAction' => $specific_action, 
            'quantity' => $adjusted_quantity
        ];

        $args = array(
            'method'  => 'PATCH',
            'headers' => array(
                'accept' => 'application/json',
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json'
            ),
            'body'    => wp_json_encode($data),
            'timeout' => 60,
        );

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            // Log the error
            // Example: my_custom_log_function("API Error: " . $response->get_error_message());
            return ['success' => false, 'response' => $response->get_error_message()];
        }

        $http_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        // Log the API Response and HTTP Code
        // Example: my_custom_log_function("API Response: $body");
        // Example: my_custom_log_function("API HTTP Code: $http_code");

        return ['success' => $http_code >= 200 && $http_code < 300, 'response' => json_decode($body, true)];
    }

    public function handle_stock_action_request($action, $quantity, $product_id, $variant_id = null) {
        // Log the action
        // Example: my_custom_log_function("handle_stock_action_request called with action: $action, quantity: $quantity, product_id: $product_id, variant_id: " . ($variant_id ? $variant_id : 'null'));

        list($base_action, $specific_action) = explode('_', $action, 2);
        $quantity = ($base_action === 'REMOVE') ? -abs($quantity) : abs($quantity);

        // Nur die Varianten-ID übergeben, wenn sie gesetzt ist und größer als 0
        $variant_id = (!empty($variant_id) && $variant_id > 0) ? $variant_id : null;

        $result = $this->update_stock_action($product_id, $variant_id, $base_action, $specific_action, $quantity);
        // Log the result
        // Example: my_custom_log_function("API result: " . print_r($result, true));
        $this->display_stock_action_result($result, $action, $quantity, $product_id, $variant_id);
        return $result;
    }

    public function display_stock_action_result($result, $action, $quantity, $product_id, $variant_id) {
        $status = $result['success'] ? 'Success' : 'Failed';
        $message = isset($result['response']['message']) ? $result['response']['message'] : wp_json_encode($result['response']);
        
        // Build the URL for display
        $url = $this->api_base_url . "product/update-woo-stock/";
        $url .= $variant_id ? "{$product_id}/{$variant_id}" : $product_id;
        
        echo "<div class='notice " . esc_attr($result['success'] ? 'notice-success' : 'notice-error') . "'><p>
            <strong>Stock Action:</strong> " . esc_html(ucfirst($action)) . "<br>
            <strong>Product ID:</strong> " . esc_html($product_id) . "<br>
            <strong>Variant ID:</strong> " . esc_html($variant_id ? $variant_id : 'N/A') . "<br>
            <strong>Quantity:</strong> " . esc_html($quantity) . "<br>
            <strong>Request URL:</strong> " . esc_html($url) . "<br>
            <strong>Status:</strong> " . esc_html($status) . "<br>
            <strong>Response:</strong> " . esc_html($message) . "
        </p></div>";
    }

    public function handle_setup_request() {
        $company_id = get_option('final_pos_company_id');
        $url = esc_url_raw("{$this->api_base_url}sync/setup/{$company_id}");

        $args = array(
            'method'  => 'PUT',
            'body'    => wp_json_encode($this->data),
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->token,
            ),
            'timeout' => 60,
        );

        $response = wp_remote_request($url, $args);
        $http_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        $success = ($http_code == 200 || $http_code == 412);
        $this->display_setup_result($body, $http_code);

        return array('success' => $success, 'response' => $body, 'http_code' => $http_code);
    }

    public function display_setup_result($response, $http_code) {
        $status = ($http_code >= 200 && $http_code < 300) ? 'Success' : 'Failed';
        $message = $this->format_response_message($response); // Diese Zeile verursacht den Fehler
        
        echo "<div class='notice " . esc_attr($status === 'Success' ? 'notice-success' : 'notice-error') . "'><p>
            <strong>" . esc_html__('Setup Status:', 'final-pos') . "</strong> " . esc_html($status) . "<br>
            <strong>" . esc_html__('Response Code:', 'final-pos') . "</strong> " . esc_html($http_code) . "<br>
            <strong>" . esc_html__('Response:', 'final-pos') . "</strong><br>" . esc_html($message) . "
        </p></div>";
    }

    // Füge diese neue Methode hinzu
    private function format_response_message($response) {
        if (empty($response)) {
            return 'No response received';
        }

        // Wenn die Antwort bereits ein String ist
        if (is_string($response)) {
            return $response;
        }

        // Wenn die Antwort ein JSON-String ist, versuche ihn zu decodieren
        $decoded = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            // Wenn es ein Array oder Objekt ist, formatiere es als String
            if (is_array($decoded) || is_object($decoded)) {
                return wp_json_encode($decoded, JSON_PRETTY_PRINT);
            }
            return (string)$decoded;
        }

        // Fallback: Konvertiere die Antwort in einen String
        return (string)$response;
    }

    public function check_and_run_initial_sync() {
        $wizard_status = get_option('final_wizard_status', '');
        
        // Nur fortfahren wenn der Status 'wc_auth_complete' ist
        if ($wizard_status === 'wc_auth_complete') {
            $setup_result = $this->handle_setup_request();
            
            if ($setup_result['success'] || $setup_result['http_code'] == 412) {
                $sync_result = $this->handle_sync_all();
                
                // Status auf 'completed_sync' setzen
                update_option('final_wizard_status', 'completed_sync');
                
                if (!$sync_result['success']) {
                    update_option('final_sync_error', wp_json_encode($sync_result));
                }
            } else {
                update_option('final_sync_error', wp_json_encode($setup_result));
                update_option('final_wizard_status', 'completed_sync');
            }

            // Nach erfolgreichem Sync zur Admin-Seite weiterleiten
            wp_safe_redirect(admin_url());
            exit;
        }
    }

    // Neue Hilfsmethode zum Überprüfen des Wizard-Status
    private function should_run_sync() {
        $wizard_status = get_option('final_wizard_status', '');
        return $wizard_status === 'done';
    }

    public function run_initial_sync() {
        if ($this->should_run_sync()) {
            $this->check_and_run_initial_sync();
        }
    }

    private function is_sync_successful($response) {
        $response_data = json_decode($response, true);
        return isset($response_data['status']) && $response_data['status'] === 'success';
    }

    private function get_sync_status($response) {
        if (empty($response)) {
            return 'Failed';
        }

        // Wenn die Antwort ein String ist, versuche sie als JSON zu decodieren
        if (is_string($response)) {
            $decoded = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                // Wenn die Decodierung erfolgreich war, verwende das decodierte Array
                $response = $decoded;
            }
        }

        // Wenn die Antwort ein Array ist
        if (is_array($response)) {
            // Prüfe verschiedene mögliche Statusschlüssel
            if (isset($response['status'])) {
                return ucfirst(strtolower($response['status']));
            }
            if (isset($response['success'])) {
                return $response['success'] ? 'Success' : 'Failed';
            }
        }

        // Fallback: Wenn kein eindeutiger Status gefunden wurde
        return 'Unknown';
    }
}

// Make the Sync_Page instance globally accessible
global $sync_page_instance;
$sync_page_instance = new Sync_Page();













