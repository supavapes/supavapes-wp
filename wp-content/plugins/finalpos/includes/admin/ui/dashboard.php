<?php

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}
// Add Final main menu and subpages
function add_final_main_menu() {
    $icon_url = plugins_url('finalpos/assets/img/icons/final.svg');

    // Add main menu page (Dashboard)
    add_menu_page(
        __('Final Dashboard', 'final-pos'),
        __('Final POS', 'final-pos'),
        'edit_pages', // Capability
        'final-admin-dashboard',
        'final_admin_dashboard_content',
        $icon_url,
        1
    );

    // Add Dashboard as the first submenu page (to match the main page)
    add_submenu_page(
        'final-admin-dashboard',
        __('Dashboard', 'final-pos'),
        __('Dashboard', 'final-pos'),
        'edit_pages',
        'final-admin-dashboard'
    );
}
add_action('admin_menu', 'add_final_main_menu', 1);



// Get revenue and order data
function get_dashboard_data() {
    if (!final_is_woocommerce_active()) {
        return false;
    }

    $transient_key = 'final_dashboard_data';
    $cached_data = get_transient($transient_key);

    if ($cached_data !== false) {
        return $cached_data;
    }

    $date_format = 'Y-m-d';
    $today = current_time($date_format);
    $date_14_days_ago = gmdate($date_format, strtotime('-14 days', strtotime($today)));
    $date_28_days_ago = gmdate($date_format, strtotime('-28 days', strtotime($today)));

    // Revenue and orders for the last 14 days
    $last_14_days_revenue = get_orders_revenue($date_14_days_ago, $today);
    $last_14_days_orders = get_orders_count($date_14_days_ago, $today);
    $last_14_days_products = get_best_selling_products($date_14_days_ago, $today);

    // Revenue and orders for the previous 14 days
    $previous_14_days_revenue = get_orders_revenue($date_28_days_ago, $date_14_days_ago);
    $previous_14_days_orders = get_orders_count($date_28_days_ago, $date_14_days_ago);
    $previous_14_days_products = get_best_selling_products($date_28_days_ago, $date_14_days_ago);

    $dashboard_data = [
        'last_14_days_revenue' => $last_14_days_revenue,
        'previous_14_days_revenue' => $previous_14_days_revenue,
        'last_14_days_orders' => $last_14_days_orders,
        'previous_14_days_orders' => $previous_14_days_orders,
        'last_14_days_products' => $last_14_days_products,
        'previous_14_days_products' => $previous_14_days_products,
        'start_date' => $date_14_days_ago,
        'end_date' => $today
    ];

    // Cache data for 5 minutes
    set_transient($transient_key, $dashboard_data, 5 * MINUTE_IN_SECONDS);

    return $dashboard_data;
}

// Helper function to process orders
function process_orders($start_date, $end_date, $callback) {
    $orders = wc_get_orders([
        'limit' => -1,
        'status' => ['wc-completed', 'wc-processing'],
        'date_created' => $start_date . '...' . $end_date,
        'return' => 'ids'
    ]);

    $results = [];

    foreach ($orders as $order_id) {
        $order = wc_get_order($order_id);
        $date = $order->get_date_created()->date('Y-m-d');
        $callback($order, $date, $results);
    }

    return $results;
}

// Get orders revenue
function get_orders_revenue($start_date, $end_date) {
    $revenue = process_orders($start_date, $end_date, function($order, $date, &$results) {
        $total = floatval($order->get_total());
        $results[$date] = ($results[$date] ?? 0) + $total;
    });

    return array_map(function($date, $total_sales) {
        return ['date' => $date, 'total_sales' => $total_sales];
    }, array_keys($revenue), $revenue);
}

// Get orders count
function get_orders_count($start_date, $end_date) {
    $order_count = process_orders($start_date, $end_date, function($order, $date, &$results) {
        $results[$date] = ($results[$date] ?? 0) + 1;
    });

    return array_map(function($date, $count) {
        return ['date' => $date, 'order_count' => $count];
    }, array_keys($order_count), $order_count);
}

// Get best selling products
function get_best_selling_products($start_date, $end_date, $limit = 5) {
    $product_sales = process_orders($start_date, $end_date, function($order, $date, &$results) {
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            if (!isset($results[$product_id])) {
                $results[$product_id] = ['quantity' => 0, 'total_sales' => 0];
            }
            $results[$product_id]['quantity'] += $item->get_quantity();
            $results[$product_id]['total_sales'] += $item->get_total();
        }
    });

    uasort($product_sales, fn($a, $b) => $b['quantity'] <=> $a['quantity']);

    $total_quantity = 0;
    $total_sales = 0;
    $formatted_products = [];

    foreach (array_slice($product_sales, 0, $limit, true) as $product_id => $sales) {
        $product = wc_get_product($product_id);
        if ($product) {
            $formatted_products[] = [
                'title' => $product->get_name(),
                'quantity' => $sales['quantity'],
                'total_sales' => $sales['total_sales'],
            ];
            $total_quantity += $sales['quantity'];
            $total_sales += $sales['total_sales'];
        }
    }

    return [
        'products' => $formatted_products,
        'total_quantity' => $total_quantity,
        'total_sales' => $total_sales
    ];
}

// Render dashboard card function
function render_dashboard_card($title, $id_prefix, $comparison_value, $chart = true, $table = false) {
    if (!final_is_woocommerce_active()) {
        return;
    }
    ?>
    <div class="<?php echo esc_attr($id_prefix); ?>-card">
        <div class="card-header">
            <h3><?php echo esc_html($title); ?></h3>
            <p class="total-value" id="<?php echo esc_attr($id_prefix); ?>-total">0</p>
            <p class="period-comparison" id="<?php echo esc_attr($id_prefix); ?>-comparison">
                <?php
                /* translators: %s: comparison value from the previous period */
                $comparison_text = sprintf(__('vs. %s in last period', 'final-pos'), $comparison_value);
                echo esc_html($comparison_text);
                ?>
            </p>
            <span class="change-tag" id="<?php echo esc_attr($id_prefix); ?>-change-tag">0%</span>
        </div>
        <?php if ($chart): ?>
            <div id="<?php echo esc_attr($id_prefix); ?>-chart-container">
                <canvas id="<?php echo esc_attr($id_prefix); ?>-chart"></canvas>
            </div>
            <div class="chart-legend">
                <span class="last-14-days"><?php esc_html_e('Last 14 Days', 'final-pos'); ?></span>
                <span class="previous-14-days"><?php esc_html_e('Previous 14 Days', 'final-pos'); ?></span>
            </div>
        <?php endif; ?>
        <?php if ($table): ?>
            <div id="<?php echo esc_attr($id_prefix); ?>-list-container">
                <table id="<?php echo esc_attr($id_prefix); ?>-table">
                    <thead>
                        <tr>
                            <th class="product-name"><?php esc_html_e('Product', 'final-pos'); ?></th>
                            <th class="product-sold"><?php esc_html_e('Sold', 'final-pos'); ?></th>
                            <th class="product-revenue"><?php esc_html_e('Revenue', 'final-pos'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="<?php echo esc_attr($id_prefix); ?>-list"></tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

// Render latest content card function
function render_latest_content_card($title, $content_list, $id_prefix) {
    ?>
    <div class="<?php echo esc_attr($id_prefix); ?>-card">
        <div class="card-header">
            <h3><?php echo esc_html($title); ?></h3>
        </div>
        <div id="<?php echo esc_attr($id_prefix); ?>-list-container">
            <table id="<?php echo esc_attr($id_prefix); ?>-table">
                <thead>
                    <tr>
                        <th class="content-title"><?php esc_html_e('Title', 'final-pos'); ?></th>
                        <th class="content-author"><?php esc_html_e('Author', 'final-pos'); ?></th>
                        <th class="content-date"><?php esc_html_e('Date', 'final-pos'); ?></th>
                        <th class="content-actions"><?php esc_html_e('Actions', 'final-pos'); ?></th>
                    </tr>
                </thead>
                <tbody id="<?php echo esc_attr($id_prefix); ?>-list">
                    <?php foreach ($content_list as $content): ?>
                        <tr>
                            <td class="content-title"><?php echo esc_html($content['title']); ?></td>
                            <td class="content-author"><?php echo esc_html($content['author']); ?></td>
                            <td class="content-date"><?php echo esc_html($content['date']); ?></td>
                            <td class="content-actions">
                                <table class="action-icons">
                                    <tr>
                                        <td><a href="<?php echo esc_url($content['view_link']); ?>"><span class="material-symbols-outlined uxlabs-boxed-icon">visibility</span></a></td>
                                        <td><a href="<?php echo esc_url($content['edit_link']); ?>"><span class="material-symbols-outlined uxlabs-boxed-icon">more_horiz</span></a></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

// Render WooCommerce orders card
function render_woocommerce_orders_card() {
    $statuses = [
        'pending' => ['label' => __('Payment Pending', 'final-pos'), 'color' => '#dd4c4c'],
        'on-hold' => ['label' => __('On Hold', 'final-pos'), 'color' => '#f6b93b'],
        'processing' => ['label' => __('Processing', 'final-pos'), 'color' => '#2da448'],
        'completed' => ['label' => __('Completed', 'final-pos'), 'color' => '#2797e8']
    ];
    ?>
    <div class="woocommerce-orders-card">
        <div class="card-header">
            <h3><?php esc_html_e('Latest Orders', 'final-pos'); ?></h3>
        </div>
        <div id="woocommerce-orders-kanban">
            <?php foreach ($statuses as $status_key => $status_info): ?>
                <div class="kanban-column" data-status="<?php echo esc_attr($status_key); ?>">
                    <h4>
                        <span class="status-circle" style="background-color: <?php echo esc_attr($status_info['color']); ?>"></span>
                        <?php echo esc_html($status_info['label']); ?>
                    </h4>
                    <div class="kanban-items" id="<?php echo esc_attr($status_key); ?>-items">
                        <?php
                        $orders = array_filter(final_get_woocommerce_orders_by_status($status_key, 10));
                        foreach ($orders as $order): ?>
                            <div class="kanban-item" draggable="true" data-order-id="<?php echo esc_attr($order['id']); ?>" data-status="<?php echo esc_attr($status_key); ?>">
                                <div class="order-header">
                                    <span class="order-customer"><?php echo esc_html($order['customer']); ?></span>
                                    <span class="order-number" style="color: <?php echo esc_attr($status_info['color']); ?>; background-color: <?php echo esc_attr($status_info['color']); ?>20;">#<?php echo esc_html($order['number']); ?></span>
                                </div>
                                <div class="order-details">
                                    <span class="order-total"><?php echo wp_kses_post(wc_price($order['total'])); ?></span>
                                    <span class="order-date"><?php echo esc_html(gmdate('Y-m-d H:i', strtotime($order['date']))); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

// Render final dashboard content
function final_admin_dashboard_content() {
    $dashboard_data = final_is_woocommerce_active() ? get_dashboard_data() : false;
    $latest_pages = final_get_latest_pages();
    $latest_posts = final_get_latest_posts();

    // Get WooCommerce currency settings only if WooCommerce is active
    $currency_symbol = final_is_woocommerce_active() ? html_entity_decode(get_woocommerce_currency_symbol()) : '';
    $currency_pos = final_is_woocommerce_active() ? get_option('woocommerce_currency_pos', 'left') : ''; // Default to 'left' if not set
    $thousand_sep = final_is_woocommerce_active() ? get_option('woocommerce_price_thousand_sep', ',') : '';
    $decimal_sep = final_is_woocommerce_active() ? get_option('woocommerce_price_decimal_sep', '.') : '';
    $num_decimals = final_is_woocommerce_active() ? get_option('woocommerce_price_num_decimals', 2) : 0;

    // Get WordPress date format
    $date_format = get_option('date_format');

    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Dashboard', 'final-pos'); ?></h1> <!-- Added translatable content -->
        <?php if (final_is_woocommerce_active()): ?>
            <div class="final-widgets">
                <?php
                render_dashboard_card(__('Revenue', 'final-pos'), 'revenue', '$0.00');
                render_dashboard_card(__('Orders', 'final-pos'), 'orders', '0');
                render_dashboard_card(__('Products', 'final-pos'), 'products', '0', false, true);
                ?>
            </div>
            <div class="final-widgets full-width">
                <?php render_woocommerce_orders_card(); ?>
            </div>
        <?php endif; ?>
        <!-- Latest content section -->
        <div class="final-widgets new-row">
            <?php
            render_latest_content_card(__('Latest Pages', 'final-pos'), $latest_pages, 'latest-pages');
            render_latest_content_card(__('Latest Posts', 'final-pos'), $latest_posts, 'latest-posts');
            ?>
        </div>
    </div>
    <?php if (final_is_woocommerce_active()): ?>
        <script>
            window.dashboardData = <?php echo wp_json_encode($dashboard_data); ?>;
            window.wpDateFormat = <?php echo wp_json_encode($date_format); ?>;
            window.currencySettings = {
                symbol: <?php echo wp_json_encode($currency_symbol); ?>,
                position: <?php echo wp_json_encode($currency_pos); ?>,
                thousandSeparator: <?php echo wp_json_encode($thousand_sep); ?>,
                decimalSeparator: <?php echo wp_json_encode($decimal_sep); ?>,
                numDecimals: <?php echo wp_json_encode($num_decimals); ?>
            };
            window.translations = {
                <?php
                /* translators: %s: value from the previous period for comparison */
                $comparison_text = __('vs. %s in last period', 'final-pos');
                ?>
                inLastPeriod: <?php echo wp_json_encode($comparison_text); ?>
            };
        </script>
    <?php endif; ?>
<?php
}

// Enqueue scripts and styles
function enqueue_dashboard_icons($hook) {
    if ($hook !== 'toplevel_page_final-admin-dashboard') {
        return;
    }

    wp_enqueue_style('final-dashboard-style', plugins_url('../../../assets/css/ui/dashboard.css', __FILE__), [], '1.0.0');

    if (final_is_woocommerce_active()) {
        // Verwende lokale Versionen der Bibliotheken
        wp_enqueue_script('chart-js', plugins_url('../../../assets/js/lib/chart.js', __FILE__), [], '3.7.0', true);
        wp_enqueue_script('chartjs-adapter-date-fns', plugins_url('../../../assets/js/lib/chartjs-adapter-date-fns.js', __FILE__), ['chart-js'], '1.0.0', true);
        wp_enqueue_script('final-dashboard-js', plugins_url('../../../assets/js/ui/dashboard.js', __FILE__), ['chart-js', 'chartjs-adapter-date-fns', 'jquery'], '1.0.0', true);
        
        wp_localize_script('final-dashboard-js', 'finalWooCommerceOrders', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('final-woocommerce-orders-nonce')
        ]);
    }
}
add_action('admin_enqueue_scripts', 'enqueue_dashboard_icons');

// Update order status via AJAX
function update_order_status() {
    check_ajax_referer('final-woocommerce-orders-nonce', 'nonce');

    if (!current_user_can('edit_shop_orders')) {
        wp_send_json_error('Permission denied');
    }

    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $new_status = isset($_POST['new_status']) ? sanitize_text_field(wp_unslash($_POST['new_status'])) : '';

    if (!$order_id || !$new_status) {
        wp_send_json_error('Invalid data');
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        wp_send_json_error('Order not found');
    }

    $order->update_status($new_status);
    wp_send_json_success();
}
add_action('wp_ajax_update_order_status', 'update_order_status');







