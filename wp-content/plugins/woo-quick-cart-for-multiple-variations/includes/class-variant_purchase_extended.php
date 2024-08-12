<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://www.multidots.com/
 * @since      1.0.0
 *
 * @package    woocommerce-quick-cart-for-multiple-variations
 * @subpackage woocommerce-quick-cart-for-multiple-variations/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    woocommerce-quick-cart-for-multiple-variations
 * @subpackage woocommerce-quick-cart-for-multiple-variations/includes
 * @author     Multidots <wordpress@multidots.com>
 */
class Variant_purchase_extended {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Variant_purchase_extended_Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $version The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->version     = '1.1.7';
		$this->plugin_name = 'Quick Bulk Variations Checkout for WooCommerce';
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Variant_purchase_extended_Loader. Orchestrates the hooks of the plugin.
	 * - Variant_purchase_extended_i18n. Defines internationalization functionality.
	 * - Variant_purchase_extended_Admin. Defines all hooks for the admin area.
	 * - Variant_purchase_extended_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		// Define Static Function From here.
		require_once __DIR__ . '/variant_purchase_extended_functions.php';

		// Define Email Function From here.
		require_once __DIR__ . '/variant_purchase_extended_emails.php';

		// The class responsible for orchestrating the actions and filters of the core plugin.
		require_once __DIR__ . '/class-variant_purchase_extended-loader.php';

		// The class responsible for defining internationalization functionality of the plugin.
		require_once __DIR__ . '/class-variant_purchase_extended-i18n.php';

		// The class responsible for defining all actions that occur in the admin area.
		require_once __DIR__ . '/../admin/class-variant_purchase_extended-admin.php';

		// The class responsible for defining all actions that occur in the public-facing side of the site.
		require_once __DIR__ . '/../public/class-variant_purchase_extended-public.php';

		$this->loader = new Variant_purchase_extended_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Variant_purchase_extended_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
		$plugin_i18n = new Variant_purchase_extended_i18n();
		$plugin_i18n->set_domain( $this->get_plugin_name() );
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Variant_purchase_extended_Admin( $this->get_plugin_name(), $this->get_version() );
		$page         = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles_scripts');
		$this->loader->add_action( 'admin_init', $plugin_admin, 'welcome_variable_purchase_extended_screen_do_activation_redirect' );
		if ( empty( $GLOBALS['admin_page_hooks']['dots_store'] ) ) {
			$this->loader->add_action( 'admin_menu', $plugin_admin, 'dot_store_menu_traking_fbg' );
		}
		$this->loader->add_action( "admin_menu", $plugin_admin, "add_new_menu_items_traking_fbg" );
		if ( ! empty( $page ) && ( ( $page === 'woocommerce-quick-cart-for-multiple-variations' ) ) ) {
			$this->loader->add_filter( 'admin_footer_text', $plugin_admin, 'wqcmv_admin_footer_review' );
		}

		$this->loader->add_action( 'woocommerce_save_product_variation', $plugin_admin, 'wqcmv_notify_user_when_prooduct_back_instock' );
		$this->loader->add_action( 'woocommerce_variation_header', $plugin_admin, 'wqcmv_display_variation_customer_requests', 10, 1 );
		$this->loader->add_filter( 'manage_product_posts_columns', $plugin_admin, 'wqcmv_product_new_column_heading', 999 );
		$this->loader->add_action( 'manage_product_posts_custom_column', $plugin_admin, 'wqcmv_product_new_column_content', 10, 2 );
		$this->loader->add_action( 'admin_footer', $plugin_admin, 'wqcmv_admin_footer_custom_assets' );
		$this->loader->add_action( 'wp_ajax_wqcmv_fetch_notifications_log', $plugin_admin, 'wqcmv_fetch_notifications_log' );
		$this->loader->add_action( 'wqcmv_bulk_update_visibility_options', $plugin_admin, 'wqcmv_bulk_update_visibility_options_handler', 10, 1 );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'wqcmv_preorder_admin_menu', 10, 1 );
		$this->loader->add_filter( 'manage_pre_order_posts_columns', $plugin_admin, 'wqcmv_pre_order_new_column_heading', 999 );
		$this->loader->add_action( 'manage_pre_order_posts_custom_column', $plugin_admin, 'wqcmv_pre_order_new_column_content', 10, 2 );
		$this->loader->add_action( 'restrict_manage_posts', $plugin_admin, 'wqcmv_restrict_manage_posts_callback' );
		$this->loader->add_action( 'pre_get_posts', $plugin_admin, 'wqcmv_parse_query_callback', 99 );
		$this->loader->add_filter( 'bulk_actions-edit-pre_order', $plugin_admin, 'wqcmv_bulk_actions_edit_pre_order_callback', 999 );
		$this->loader->add_filter( 'handle_bulk_actions-edit-pre_order', $plugin_admin, 'wqcmv_handle_bulk_actions_edit_pre_order_callback', 10, 3 );
		$this->loader->add_filter( 'views_edit-pre_order', $plugin_admin, 'wqcmv_views_edit_pre_order_callback', 999 );	
		$this->loader->add_filter( 'months_dropdown_results', $plugin_admin, 'wqcmv_hide_admin_filter_callback' );
		$this->loader->add_action( 'wp_ajax_export_pre_oeder_data', $plugin_admin, 'wqcmv_export_pre_oeder_data_callback' );
		$this->loader->add_action( 'wp_ajax_nopriv_export_pre_oeder_data', $plugin_admin, 'wqcmv_export_pre_oeder_data_callback' );
		$this->loader->add_action( 'wp_ajax_notify_preorder_users', $plugin_admin, 'wqcmv_notify_preorder_users_callback' );
		$this->loader->add_action( 'wp_ajax_nopriv_notify_preorder_users', $plugin_admin, 'wqcmv_notify_preorder_users_callback' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Variant_purchase_extended_Public( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles_scripts' );
		$this->loader->add_action( 'wp_ajax_nopriv_wqcmv_woocommerce_ajax_add_to_cart', $plugin_public,
			'wqcmv_woocommerce_ajax_add_to_cart'
		);
		$this->loader->add_action( 'wp_ajax_wqcmv_woocommerce_ajax_add_to_cart', $plugin_public, 'wqcmv_woocommerce_ajax_add_to_cart' );
		$this->loader->add_action( 'wp_ajax_wqcmv_get_out_of_stock_products', $plugin_public, 'wqcmv_get_out_of_stock_products' );
		$this->loader->add_action( 'wp_ajax_nopriv_wqcmv_get_out_of_stock_products', $plugin_public,
			'wqcmv_get_out_of_stock_products'
		);
		$this->loader->add_action( 'wp_ajax_wqcmv_send_notification_to_admin', $plugin_public, 'wqcmv_send_notification_to_admin' );
		$this->loader->add_action( 'wp_ajax_nopriv_wqcmv_send_notification_to_admin', $plugin_public,
			'wqcmv_send_notification_to_admin'
		);
		$this->loader->add_action( 'wp_ajax_wqcmv_get_user_email_for_notify_user', $plugin_public, 'wqcmv_get_user_email_for_notify_user' );
		$this->loader->add_action( 'wp_ajax_nopriv_wqcmv_get_user_email_for_notify_user', $plugin_public, 'wqcmv_get_user_email_for_notify_user' );
		$this->loader->add_action( 'wp_ajax_wqcmv_notification_request', $plugin_public, 'wqcmv_notification_request' );
		$this->loader->add_action( 'wp_ajax_nopriv_wqcmv_notification_request', $plugin_public, 'wqcmv_notification_request' );
		$this->loader->add_action( 'wp_ajax_wqcmv_modal_pagination_outofstock_products', $plugin_public, 'wqcmv_modal_pagination_outofstock_products' );
		$this->loader->add_action( 'wp_ajax_nopriv_wqcmv_modal_pagination_outofstock_products', $plugin_public, 'wqcmv_modal_pagination_outofstock_products' );
		$this->loader->add_shortcode( 'vpe-woo-variable-product', $plugin_public, 'wqcmv_shortcode_template' );
		$this->loader->add_filter( 'body_class', $plugin_public, 'wqcmv_body_classes' );
		$this->loader->add_action( 'wp_footer', $plugin_public, 'wqcmv_modal_html' );
		$this->loader->add_action( 'wp_ajax_wqcmv_products_pagination', $plugin_public, 'wqcmv_products_pagination' );
		$this->loader->add_action( 'wp_ajax_nopriv_wqcmv_products_pagination', $plugin_public, 'wqcmv_products_pagination' );
		$this->loader->add_filter( 'wqcmv_table_headers', $plugin_public, 'wqcmv_modify_table_headers' );
		$this->loader->add_action( 'wp', $plugin_public, 'wqcmv_add_to_cart_for_registered_user' );
		$this->loader->add_filter( 'woocommerce_locate_template', $plugin_public, 'wqcmv_woocommerce_locate_template', 10, 3 );
		$this->loader->add_action( 'wp_ajax_wqcmv_get_user_email_for_notify_user_pre_order', $plugin_public, 'wqcmv_get_user_email_for_notify_user_pre_order_callback' );
		$this->loader->add_action( 'wp_ajax_nopriv_wqcmv_get_user_email_for_notify_user_pre_order', $plugin_public, 'wqcmv_get_user_email_for_notify_user_pre_order_callback' );
		$this->loader->add_action( 'init', $plugin_public, 'wqcmv_init_callback' );
		$this->loader->add_action( 'wp_ajax_wqcmv_store_pre_order_send_notification_to_admin', $plugin_public, 'wqcmv_store_pre_order_send_notification_to_admin_callback' );
		$this->loader->add_action( 'wp_ajax_nopriv_wqcmv_store_pre_order_send_notification_to_admin', $plugin_public,
			'wqcmv_store_pre_order_send_notification_to_admin_callback'
		);
		$this->loader->add_filter( 'woocommerce_stock_html', $plugin_public, 'wqcmv_simple_product_outofstock_html', 10, 3 );
		$this->loader->add_action( 'wp_ajax_wqcmv_get_user_email_for_notify_user_simple_product_pre_order', $plugin_public, 'wqcmv_get_user_email_for_notify_user_simple_product_pre_order_callback' );
		$this->loader->add_action( 'wp_ajax_nopriv_wqcmv_get_user_email_for_notify_user_simple_product_pre_order', $plugin_public, 'wqcmv_get_user_email_for_notify_user_simple_product_pre_order_callback' );
		
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {

		$this->loader->run();

	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @return    string    The name of the plugin.
	 * @since     1.0.0
	 */
	public function get_plugin_name() {

		return $this->plugin_name;

	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return    Variant_purchase_extended_Loader    Orchestrates the hooks of the plugin.
	 * @since     1.0.0
	 */
	public function get_loader() {

		return $this->loader;

	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return    string    The version number of the plugin.
	 * @since     1.0.0
	 */
	public function get_version() {

		return $this->version;

	}

}