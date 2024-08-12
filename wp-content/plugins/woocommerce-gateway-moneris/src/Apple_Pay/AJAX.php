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

namespace SkyVerge\WooCommerce\Moneris\Apple_Pay;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_12_1 as Framework;

/**
 * The Apple Pay AJAX handler.
 *
 * @since 1.11.0
 *
 * @method \SkyVerge\WooCommerce\Moneris\Apple_Pay get_handler()
 */
class AJAX extends Framework\SV_WC_Payment_Gateway_Apple_Pay_AJAX {


	/**
	 * AJAX constructor.
	 *
	 * @since 1.11.0
	 *
	 * @param Framework\SV_WC_Payment_Gateway_Apple_Pay $handler
	 */
	public function __construct( Framework\SV_WC_Payment_Gateway_Apple_Pay $handler ) {

		parent::__construct( $handler );

		if ( $this->get_handler()->is_available() ) {

			// create a new order
			add_action( 'wp_ajax_wc_moneris_apple_pay_create_order', [ $this, 'create_order' ] );
			add_action( 'wp_ajax_nopriv_wc_moneris_apple_pay_create_order', [ $this, 'create_order' ] );

			// process the Moneris transaction receipt
			add_action( 'wp_ajax_wc_moneris_apple_pay_process_receipt', [ $this, 'process_receipt' ] );
			add_action( 'wp_ajax_nopriv_wc_moneris_apple_pay_process_receipt', [ $this, 'process_receipt' ] );
		}
	}


	/**
	 * Creates a new order for processing.
	 *
	 * @since 1.11.0
	 */
	public function create_order() {

		$this->get_handler()->log( 'Creating order' );

		try {

			if ( ! wp_verify_nonce( Framework\SV_WC_Helper::get_posted_value( 'nonce' ), 'wc_moneris_apple_pay_create_order' ) ) {
				throw new Framework\SV_WC_Payment_Gateway_Exception( 'Invalid nonce' );
			}

			$response = stripslashes( Framework\SV_WC_Helper::get_posted_value( 'payment' ) );

			$this->get_handler()->store_payment_response( $response );

			$order = $this->get_handler()->create_order( $this->get_handler()->get_stored_payment_response() );

			wp_send_json_success( [
				'id'             => $order->get_id(),
				'number'         => $order->unique_transaction_ref,
				'amount'         => $order->payment_total,
				'perform_charge' => $this->get_handler()->get_processing_gateway()->perform_credit_card_charge( $order ), // this is determined on the order level for virtual-only orders
			] );

		} catch ( Framework\SV_WC_Payment_Gateway_Exception $exception) {

			$this->get_handler()->log( 'Could not create order. ' . $exception->getMessage() );

			wp_send_json_error( [
				'message' => $exception->getMessage(),
				'code'    => $exception->getCode(),
			] );
		}
	}


	/**
	 * Processes the payment receipt after Moneris authorization.
	 *
	 * @internal
	 *
	 * @since 1.11.0
	 */
	public function process_receipt() {

		$this->get_handler()->log( 'Processing receipt' );

		$receipt_data = $_POST['receipt']['receipt'] ?? [];
		$order_id     = Framework\SV_WC_Helper::get_posted_value( 'order_id' );

		try {

			if ( ! wp_verify_nonce( Framework\SV_WC_Helper::get_posted_value( 'nonce' ), 'wc_moneris_apple_pay_process_receipt' ) ) {
				throw new Framework\SV_WC_Payment_Gateway_Exception( 'Invalid nonce' );
			}

			if ( empty( $receipt_data ) ) {
				throw new Framework\SV_WC_Payment_Gateway_Exception( 'Invalid receipt data' );
			}

			$result = $this->get_handler()->process_receipt( $order_id, $receipt_data );

			$this->get_handler()->clear_payment_data();

			if ( ! isset( $result['result'] ) || 'success' !== $result['result'] ) {
				throw new Framework\SV_WC_Payment_Gateway_Exception( 'Gateway processing error.' );
			}

			wp_send_json_success( $result );

		} catch ( Framework\SV_WC_Payment_Gateway_Exception $exception ) {

			$this->get_handler()->log( 'Payment failed. '.$exception->getMessage() );

			wp_send_json_error( [
				'message' => $exception->getMessage(),
				'code'    => $exception->getCode(),
			] );
		}
	}


}
