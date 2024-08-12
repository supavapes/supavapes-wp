<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
/**
 * Fired during plugin activation
 *
 * @link       http://www.multidots.com/
 * @since      1.0.0
 *
 * @package    woocommerce-quick-cart-for-multiple-variations
 * @subpackage woocommerce-quick-cart-for-multiple-variations/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    woocommerce-quick-cart-for-multiple-variations
 * @subpackage woocommerce-quick-cart-for-multiple-variations/includes
 * @author     Multidots <wordpress@multidots.com>
 */
class Variant_purchase_extended_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		  set_transient( '_welcome_screen_WC_Variable_Products_Purchase_Extended_activation_redirect_data', true, 30 );
        /**
         * This code updates all the product to auto apply the new template.
         */
		if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) || is_plugin_active_for_network( 'woocommerce/woocommerce.php' ) ) {
			$action_timestamp = time() + 10;
			$chunk_size       = 10;
			$query_results    = Variant_purchase_extended_Activator::wqcmv_get_variable_products( 1, 1, true );
			$total            = isset( $query_results->total ) ? $query_results->total : 0;
			if ( 0 === $total ) {
				return;
			}
			$num_batches = ceil( $total / $chunk_size );
			if ( isset( $num_batches ) && 0 < $num_batches ) {
				for ( $i = 1; $i <= $num_batches; $i ++ ) {
					wp_schedule_single_event( $action_timestamp, 'wqcmv_bulk_update_visibility_options', array( $i ) );
				}
			}
		}

	}

	/**
	 * This plugin returns the variable products for updating the meta option for display variation table in product page.
	 *
	 * @param int $limit
	 * @param int $page
	 * @param bool $paginate
	 *
	 * @return array|object
	 */
	public static function wqcmv_get_variable_products( $limit = 10, $page = 1, $paginate = false ) {
		$query         = new WC_Product_Query( array(
			'limit'    => $limit,
			'orderby'  => 'date',
			'order'    => 'DESC',
			'return'   => 'ids',
			'type'     => 'variable',
			'page'     => $page,
			'paginate' => $paginate,
		) );
		$query_results = $query->get_products();

		return $query_results;
	}
}
