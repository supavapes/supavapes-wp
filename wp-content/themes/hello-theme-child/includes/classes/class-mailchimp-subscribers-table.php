<?php

$wordpress_root = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))));
$wp_load_path = $wordpress_root . '/wp-load.php';

if (file_exists($wp_load_path)) {
    require_once $wp_load_path;
} else {
    error_log('wp-load.php not found at: ' . $wp_load_path);
    exit('wp-load.php not found.');
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Mailchimp_Subscribers_Table extends WP_List_Table {
    private $subscribers;

    public function __construct($subscribers) {
        parent::__construct([
            'singular' => __('Subscriber', 'hello-elementor-child'),
            'plural'   => __('Subscribers', 'hello-elementor-child'),
            'ajax'     => false
        ]);
        $this->subscribers = $subscribers;
    }

    public function get_columns() {
        $columns = [
            'email_address' => __('Email Address', 'hello-elementor-child'),
            'phone_number'  => __('Phone Number', 'hello-elementor-child'),
            'status'        => __('Status', 'hello-elementor-child'),
            'action'        => __('Action', 'hello-elementor-child')
        ];
        return $columns;
    }

    public function prepare_items() {

        // Number of items per page
        $per_page = 10;
        // Get current page number
        $current_page = $this->get_pagenum();
        // Total number of items
        $total_items = count($this->subscribers);
        // Slice the subscribers array based on the current page and items per page
        $this->items = array_slice($this->subscribers, (($current_page - 1) * $per_page), $per_page);
        // Set pagination args
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ]);
    }
    
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'email_address':
                return esc_html($item['email_address']);
            case 'phone_number':
                return isset($item['merge_fields']['PHONE']) ? esc_html($item['merge_fields']['PHONE']) : '';
            case 'status':
                // Change "subscribed" to "active" and "unsubscribed" to "inactive" with colors
                $status = ($item['status'] == 'subscribed') ? __('Active', 'hello-elementor-child') : __('Inactive', 'hello-elementor-child');
                $color = ($item['status'] == 'subscribed') ? 'green' : 'red';
                return '<span style="color:' . esc_attr($color) . ';">' . esc_html($status) . '</span>';
            case 'action':
                $action_url = admin_url('admin-post.php?action=mailchimp_subscription_action&email=' . urlencode($item['email_address']));
                $action_text = ($item['status'] == 'subscribed') ? esc_html__('Unsubscribe', 'hello-elementor-child') : esc_html__('Subscribe', 'hello-elementor-child');
                $action = ($item['status'] == 'subscribed') ? 'unsubscribe' : 'subscribe';
                return '<a class="button" href="' . esc_url($action_url . '&action_type=' . $action) . '">' . $action_text . '</a>';
            default:
                return print_r($item, true);
        }
    }
    
    public function get_searchable_columns() {
        return ['email_address'];
    }

    public function search_box($text, $input_id) {
        if (empty($_REQUEST['s']) && !$this->has_items()) {
            return;
        }
        $input_id = $input_id . '-search-input';
        ?>
        <p class="search-box">
            <label class="screen-reader-text" for="<?php echo $input_id ?>"><?php echo $text; ?>:</label>
            <input type="search" id="<?php echo $input_id ?>" name="s" value="<?php echo _admin_search_query(); ?>" />
            <?php submit_button($text, '', '', false, ['id' => 'search-submit']); ?>
        </p>
        <?php
    }
}