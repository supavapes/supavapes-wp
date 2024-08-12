<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.thedotstore.com/
 * @since             1.0.0
 * @package           woo-quick-cart-for-multiple-variations
 *
 * @wordpress-plugin
 * Plugin Name:       Quick Bulk Variations Checkout for WooCommerce
 * Plugin URI:        https://www.thedotstore.com/
 * Description:       This plugin extends the variable purchase ability. Allows multiple variants to be purchased at a time.
 * Version:           1.2.0
 * Author:            theDotstore
 * Author URI:        https://www.thedotstore.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woo-quick-cart-for-multiple-variations
 * Domain Path:       /languages
 */
// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
	die;
}

if ( !defined( 'WQCMV_PLUGIN_PATH' ) ) {
	define( 'WQCMV_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

if ( !defined( 'WQCMV_PLUGIN_URL' ) ) {
	define( 'WQCMV_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( function_exists( 'wqcfmv_fs' ) ) {
	wqcfmv_fs()->set_basename( false, __FILE__ );
	return;
}

add_action( 'plugins_loaded', 'wqcmv_initialize_plugin' );
$wc_active = in_array( 'woocommerce/woocommerce.php', get_option( 'active_plugins' ), true );
if ( true === $wc_active ) {
	
	if ( !function_exists( 'wqcfmv_fs' ) ) {
		// Create a helper function for easy SDK access.
		function wqcfmv_fs()
		{
			global  $wqcfmv_fs ;
			
			if ( !isset( $wqcfmv_fs ) ) {
				// Include Freemius SDK.
				require_once dirname( __FILE__ ) . '/freemius/start.php';
				$wqcfmv_fs = fs_dynamic_init( array(
					'id'             => '4818',
					'slug'           => 'woo-quick-cart-for-multiple-variations',
					'type'           => 'plugin',
					'public_key'     => 'pk_864bd94363e48f7e8b9dab264f8c3',
					'is_premium'     => false,
					'premium_suffix' => 'Pro',
					'has_addons'     => false,
					'has_paid_plans' => true,
					'menu'           => array(
					'slug'       => 'woocommerce-quick-cart-for-multiple-variations',
					'first-path' => 'admin.php?page=woocommerce-quick-cart-for-multiple-variations&tab=wqcmv_variant_purchase_extended_get_started_method',
					'support'    => false,
				),
					'is_live'        => true,
				) );
			}
			
			return $wqcfmv_fs;
		}
		
		// Init Freemius.
		wqcfmv_fs();
		// Signal that SDK was initiated.
		do_action( 'wqcfmv_fs_loaded' );
		wqcfmv_fs()->add_action( 'after_uninstall', 'wqcfmv_fs_uninstall_cleanup' );
	}
}
/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-variant_purchase_extended-activator.php
 */
function activate_variant_purchase_extended() {
	require_once __DIR__ . '/includes/class-variant_purchase_extended-activator.php';
	Variant_purchase_extended_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-variant_purchase_extended-deactivator.php
 */
function deactivate_variant_purchase_extended() {
	require_once __DIR__ . '/includes/class-variant_purchase_extended-deactivator.php';
	Variant_purchase_extended_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_variant_purchase_extended' );
register_deactivation_hook( __FILE__, 'deactivate_variant_purchase_extended' );
/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_variant_purchase_extended() {
	/**
	 * The core plugin class that is used to define internationalization,
	 * admin-specific hooks, and public-facing site hooks.
	 */
	require __DIR__ . '/includes/class-variant_purchase_extended.php';
	require __DIR__ . '/includes/class-variant_purchase_extended-user-feedback.php';
	
	$plugin = new Variant_purchase_extended();
	$plugin->run();
}

/**
 * Check plugin requirement on plugins loaded, this plugin requires Gravity Forms to be installed and active.
 *
 * @since    1.0.0
 */
function wqcmv_initialize_plugin() {
	$wc_active = in_array( 'woocommerce/woocommerce.php', get_option( 'active_plugins' ), true );
	
	if ( current_user_can( 'activate_plugins' ) && $wc_active !== true ) {
		add_action( 'admin_notices', 'wqcmv_plugin_admin_notice' );
	} else {
		run_variant_purchase_extended();
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wqcmv_plugin_links' );
	}

}

/**
 * Adding additional row meta.
 *
 * @param $links
 * @param $file
 * @return array
 */
function wqcmv_custom_plugin_row_meta( $links, $file ) {
	$site_url = get_site_url();
	
	if ( strpos( $file, 'woo-quick-cart-for-multiple-variations.php' ) !== false ) {
		$new_links = array(
			'doc'     => '<a href="' . esc_url( $site_url ) . '/developer-doc/" target="_blank">' . esc_html__( 'Docs', 'woo-quick-cart-for-multiple-variations' ) . '</a>',
			'support' => '<a href="https://www.thedotstore.com/support/" target="_blank">' . esc_html__( 'Support ', 'woo-quick-cart-for-multiple-variations' ) . '</a>',
		);
		$links = array_merge( $links, $new_links );
	}
	
	return $links;
}

add_filter(
	'plugin_row_meta',
	'wqcmv_custom_plugin_row_meta',
	10,
	2
);
/**
 * Settings link on plugin listing page
 */
function wqcmv_plugin_links( $links ) {
	$vpe_links = array( '<a href="' . admin_url( 'admin.php?page=woocommerce-quick-cart-for-multiple-variations' ) . '">' . esc_html__( 'Settings', 'woo-quick-cart-for-multiple-variations' ) . '</a>' );
	return array_merge( $links, $vpe_links );
}

/**
 * Show admin notice in case of Gravity Forms plugin is missing.
 *
 * @since    1.0.0
 */
function wqcmv_plugin_admin_notice() {
	$vpe_plugin = esc_html__( 'Quick Bulk Variations Checkout for WooCommerce', 'woo-quick-cart-for-multiple-variations' );
	$wc_plugin = esc_html__( 'WooCommerce', 'woo-quick-cart-for-multiple-variations' );
	?>
	<div class="error">
		<p>
			<?php 
	echo  sprintf( esc_html__( '%1$s is ineffective as it requires %2$s to be installed and active.', 'woo-quick-cart-for-multiple-variations' ), '<strong>' . esc_html( $vpe_plugin ) . '</strong>', '<strong>' . esc_html( $wc_plugin ) . '</strong>' ) ;
	?>
		</p>
	</div>
	<?php 
}

/**
 * Debug function.
 */
if ( ! function_exists( 'debug' ) ) {
	function debug( $params ) {
		echo '<pre>';
		print_r( $params );
		echo '</pre>';
	}
}

add_action( 'admin_init', function() {
	// if ( '183.82.163.66' !== $_SERVER['REMOTE_ADDR'] ) {
	// 	return;
	// }

	// $post_id        = 83264;
	// $pre_order_data = get_post_meta( $post_id, 'wqcmv_pre_order_data', true );
	// debug( $pre_order_data );
	// die;

	// wqcmvp_product_id = 67138
	// wqcmvp_product_title = *CLOUD NURDZ SALT - 30ML* - 25MG, ALOE MANGO (210000055208)
	// wqcmv_pre_order_data
	// 
} );