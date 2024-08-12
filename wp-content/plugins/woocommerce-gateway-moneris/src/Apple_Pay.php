<?php
/**
 * WooCommerce Moneris.
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade WooCommerce Moneris to newer
 * versions in the future. If you wish to customize WooCommerce Moneris for your
 * needs please refer to http://docs.woocommerce.com/document/moneris/ for more information.
 *
 * @author    SkyVerge
 * @copyright Copyright (c) 2012-2024, SkyVerge, Inc. (info@skyverge.com)
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\Moneris;

defined( 'ABSPATH' ) or exit;

use Exception;
use SkyVerge\WooCommerce\PluginFramework\v5_12_1 as Framework;

/**
 * The Moneris Apple Pay handler.
 *
 * @since 1.11.0
 *
 * @method \WC_Gateway_Moneris_Credit_Card get_processing_gateway()
 */
class Apple_Pay extends Framework\SV_WC_Payment_Gateway_Apple_Pay {


	/**
	 * Initializes the Apple Pay handlers.
	 *
	 * @since 1.11.0
	 */
	protected function init() {

		if ( is_admin() && ! wp_doing_ajax() ) {
			$this->admin = new Framework\SV_WC_Payment_Gateway_Apple_Pay_Admin( $this );
		} else {
			$this->ajax     = new Apple_Pay\AJAX( $this );
			$this->frontend = new Apple_Pay\Frontend( $this->get_plugin(), $this );
		}

		// remove the "Test Mode" setting as it's not relevant for Moneris
		add_filter( 'woocommerce_get_settings_apple_pay', function ( $settings ) {

			foreach ( $settings as $key => $setting ) {

				if ( ! empty( $setting['id'] ) && 'sv_wc_apple_pay_test_mode' === $setting['id'] ) {

					unset( $settings[ $key ] );
					break;
				}
			}

			return $settings;
		} );
	}


	/**
	 * Processes a Moneris payment receipt.
	 *
	 * Moneris processes Apple Pay payment client-side, so we take the resulting data and build a standard API response object out of it, passing it to the gateway as if the transaction was already attempted.
	 *
	 * @since 1.11.0
	 *
	 * @param int $order_id order ID
	 * @param array $receipt_data transaction receipt data
	 * @return array
	 */
	public function process_receipt( $order_id, array $receipt_data ) {

		$this->get_processing_gateway()->log_api_request( [], $receipt_data );

		// load API classes
		$this->get_processing_gateway()->get_api();

		$handler = $this;

		add_filter( 'wc_payment_gateway_' . $this->get_processing_gateway()->get_id() . '_get_order', function ( $order ) use ( $handler, $receipt_data ) {

			$order = $handler->get_processing_gateway()->get_order_for_apple_pay( $order, $handler->get_stored_payment_response() );

			$order->payment->response = new \WC_Moneris_API_Receipt_Response( $receipt_data );

			return $order;
		} );

		return $this->get_processing_gateway()->process_payment( $order_id );
	}


	/**
	 * Creates a new order from an Apple Pay payment response.
	 *
	 * @since 1.11.0
	 *
	 * @param Framework\SV_WC_Payment_Gateway_Apple_Pay_Payment_Response $payment_response Apple Pay payment response object
	 * @return \WC_Order
	 * @throws Framework\SV_WC_Payment_Gateway_Exception
	 */
	public function create_order( Framework\SV_WC_Payment_Gateway_Apple_Pay_Payment_Response $payment_response ) {

		$order = null;

		try {
			$this->log( "Payment Response:\n" . $payment_response->to_string_safe() . "\n" );

			$order = Framework\Payment_Gateway\External_Checkout\Orders::create_order( WC()->cart );

			$order->set_payment_method( $this->get_processing_gateway() );

			// if we got to this point, the payment was authorized by Apple Pay
			// from here on out, it's up to the gateway to not screw things up.
			$order->add_order_note( __( 'Apple Pay payment authorized.', 'woocommerce-gateway-moneris' ) );

			$order->set_address( $payment_response->get_billing_address(), 'billing' );
			$order->set_address( $payment_response->get_shipping_address(), 'shipping' );

			if ( Framework\SV_WC_Plugin_Compatibility::is_wc_version_gte( '3.0' ) ) {
				$order->save();
			}

			if ( $user_id = $order->get_user_id() ) {
				$this->update_customer_addresses( $user_id, $payment_response );
			}

			// return the order with gateway data added
			return $this->get_processing_gateway()->get_order( $order );

		} catch ( Exception $exception ) { // general to catch WC core order exceptions

			if ( $order ) {

				$order->add_order_note( sprintf(
					/* translators: Placeholders: %s - the error message */
					__( 'Apple Pay payment failed. %s', 'woocommerce-gateway-moneris' ),
					$exception->getMessage()
				) );
			}

			throw new Framework\SV_WC_Payment_Gateway_Exception( $exception->getMessage(), $exception->getCode() );
		}
	}


}
