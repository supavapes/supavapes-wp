<?php
/**
 * Plugin Name: Inventory Management
 * Description: Inventory Management, Demand Forecasting, Automated Replenishment and Purchase Order Management for WooCommerce, all in one powerful tool.
 * Version: 2.5.3
 * Author: Shelf Planner
 * Author URI: https://shelfplanner.com
 * Text Domain: shelf-planner
 * Domain Path: /languages
 * Woo: 18734002062098:e1a5d3c99dcce5f6f9b51fb523d0ac55
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package extension
 */

defined('ABSPATH') || exit;

if (!defined('WP_REST_API_DISABLED')) {
	define('WP_REST_API_DISABLED', false);
}

if (!defined('SPC_WC__VERSION')) {
	define('SPC_WC__VERSION', '2.5.3');
}

if (!defined('SPC_WC__MAIN_PLUGIN_FILE')) {
	define('SPC_WC__MAIN_PLUGIN_FILE', __FILE__);
}

require_once plugin_dir_path(__FILE__) . 'includes/shelf_planner_config.php';
require_once plugin_dir_path(__FILE__) . 'includes/shelf_planner_setup.php';
require_once plugin_dir_path(__FILE__) . 'includes/shelf_planner_connector.php';

use ShelfPlanner\shelf_planner_config;
use ShelfPlanner\shelf_planner_setup;
use ShelfPlanner\shelf_planner_connector;

// phpcs:disable WordPress.Files.FileName

/**
 * WooCommerce fallback notice.
 *
 * @since 0.1.0
 */
function inventory_management_woocommerce_missing_wc_notice()
{
	$config = shelf_planner_config::instance();
	$class = 'notice notice-error';
	$message = esc_html__(sprintf('Inventory Management requires WooCommerce to be installed and active. You can download %s here.', '<a href="https://woo.com/" target="_blank">WooCommerce</a>'), $config->get_domain());
	printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
}

function inventory_management_woocommerce_spcwc_notice()
{
	$config = shelf_planner_config::instance();
	$class = 'notice notice-error';
	$message = __('Found Stock Management for Woocommerce plugin installed. Please uninstall it before using Inventory Management, thanks.', $config->get_domain());
	printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
}

register_activation_hook(__FILE__, 'inventory_management_woocommerce_activate');

/**
 * Activation hook.
 *
 * @since 0.1.0
 */
function inventory_management_woocommerce_activate()
{
	if (!inventory_management_woocommerce_check_dependencies()) {
		inventory_management_woocommerce_deactivate_plugin();
		return;
	}

	$config = shelf_planner_config::instance();
	$sp_connector = shelf_planner_connector::instance($config);
	$sp_connector->activate();
}

function inventory_management_woocommerce_check_dependencies() {
	$config = shelf_planner_config::instance();

	if (class_exists('shelf_planner') || !class_exists('WooCommerce')) {
		$message = '';

		if (class_exists('shelf_planner')) {
			$message = __('Found Stock Management for Woocommerce plugin installed. Please uninstall it before using Inventory Management, thanks.', $config->get_domain());
		}

		if (!class_exists('WooCommerce')) {
			$message = esc_html__(sprintf('Inventory Management requires WooCommerce to be installed and active. You can download %s here.', '<a href="https://woo.com/" target="_blank">WooCommerce</a>'), $config->get_domain());
		}

		wp_die('Inventory Management could not be activated. ' . $message);
		return false;
	}

	return true;
}

function inventory_management_woocommerce_deactivate_plugin()
{
	require_once(ABSPATH . 'wp-admin/includes/plugin.php');
	deactivate_plugins(plugin_basename(__FILE__));
	if (isset($_GET['activate'])) {
		unset($_GET['activate']);
	}
}

register_deactivation_hook(__FILE__, 'inventory_management_woocommerce_deactivate');

/**
 * Dectivation hook.
 *
 * @since 2.1.1
 */
function inventory_management_woocommerce_deactivate()
{
	$config = shelf_planner_config::instance();
	$sp_connector = shelf_planner_connector::instance($config);
	$sp_connector->deactivate();
}

function inventory_management_woocommerce_update_hook($upgrader_object, $options) {
    // Check if the plugin was updated
    if ($options['action'] == 'update' && $options['type'] == 'plugin' && isset($options['plugins']) && is_array($options['plugins'])) {
        // Get the list of updated plugins
        $updated_plugins = $options['plugins'];

        // Check if our plugin is in the list of updated plugins
        if (in_array(plugin_basename(__FILE__), $updated_plugins)) {
            // Perform actions after the plugin is updated
            inventory_management_woocommerce_activate();
        }
    }
}

add_action('upgrader_process_complete', 'inventory_management_woocommerce_update_hook', 10, 2);

if (!class_exists('inventory_management_woocommerce')):
	/**
	 * The inventory_management_woocommerce class.
	 */
	class inventory_management_woocommerce
	{
		/**
		 * This class instance.
		 *
		 * @var \inventory_management_woocommerce single instance of this class.
		 */
		private static $instance;

		/**
		 * Constructor.
		 */
		public function __construct()
		{
			if (is_admin()) {
				$config = shelf_planner_config::instance();
				new shelf_planner_setup($config);
			}
		}

		/**
		 * Cloning is forbidden.
		 */
		public function __clone()
		{
			$config = shelf_planner_config::instance();
			wc_doing_it_wrong(__FUNCTION__, __('Cloning is forbidden.', $config->get_domain()), $config->get_version());
		}

		/**
		 * Unserializing instances of this class is forbidden.
		 */
		public function __wakeup()
		{
			$config = shelf_planner_config::instance();
			wc_doing_it_wrong(__FUNCTION__, __('Unserializing instances of this class is forbidden.', $config->get_domain()), $config->get_version());
		}

		/**
		 * Gets the main instance.
		 *
		 * Ensures only one instance can be loaded.
		 *
		 * @return \inventory_management_woocommerce
		 */
		public static function instance()
		{

			if (null === self::$instance) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}
endif;

add_action('plugins_loaded', 'inventory_management_woocommerce_init', 10);

/**
 * Initialize the plugin.
 *
 * @since 0.1.0
 */
function inventory_management_woocommerce_init()
{
	$config = shelf_planner_config::instance();
	load_plugin_textdomain($config->get_domain(), false, plugin_basename(dirname(__FILE__)) . '/languages');

	if (!inventory_management_woocommerce_check_dependencies()) {
		inventory_management_woocommerce_deactivate_plugin();
		return;
	}

	inventory_management_woocommerce::instance($config);
	shelf_planner_connector::instance($config);
}
