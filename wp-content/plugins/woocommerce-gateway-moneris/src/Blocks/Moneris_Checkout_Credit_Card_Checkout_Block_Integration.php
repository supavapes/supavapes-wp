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
 * @copyright Copyright (c) 2012-2023, SkyVerge, Inc. (info@skyverge.com)
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\Moneris\Blocks;

use Exception;
use SkyVerge\WooCommerce\PluginFramework\v5_12_1\Payment_Gateway\Blocks\Gateway_Checkout_Block_Integration;
use SkyVerge\WooCommerce\PluginFramework\v5_12_1\SV_WC_API_Exception;
use SkyVerge\WooCommerce\PluginFramework\v5_12_1\SV_WC_Payment_Gateway;

/**
 * Checkout block integration for the {@see \WC_Gateway_Moneris_Checkout_Credit_Card} gateway.
 *
 * @since 3.4.0
 *
 * @property \WC_Gateway_Moneris_Checkout_Credit_Card $gateway the gateway instance
 */
class Moneris_Checkout_Credit_Card_Checkout_Block_Integration extends Gateway_Checkout_Block_Integration {


	/**
	 * Adds hooks.
	 *
	 * @since 3.4.0
	 *
	 * @return void
	 */
	public function add_hooks(): void {

		parent::add_hooks();

		add_action( 'wp_ajax_' . $this->get_ticket_number_action_name(), [ $this, 'get_ticket_number' ] );
		add_action( 'wp_ajax_nopriv_' . $this->get_ticket_number_action_name(), [ $this, 'get_ticket_number' ] );
	}


	/**
	 * Gets the AJAX action name to return a ticket number.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	protected function get_ticket_number_action_name() : string {

		return 'wc_' . $this->get_name() . '_' . $this->block_name . '_block_get_ticket_number';
	}


	/**
	 * Sends the ticket number to the checkout block via AJAX.
	 *
	 * @since 3.4.0
	 *
	 * @internal AJAX callback
	 *
	 * @return void
	 * @throws Exception
	 */
	public function get_ticket_number() : void {

		if ( ! wp_verify_nonce( $_REQUEST['nonce'], $this->get_ticket_number_action_name() ) ) {
			wp_send_json_error( 'Invalid nonce.', 401 );
		}

		try {

			$checkout_response = $this->gateway->get_checkout_api()->create_checkout_request( $_REQUEST['test_amount'] ?: null );

			if ( ! $checkout_response->is_successful() ) {

				$status_code = $checkout_response->get_status_code();

				throw new SV_WC_API_Exception( $checkout_response->get_status_message(), is_numeric( $status_code ) ? $status_code : 400 );
			}

			$ticket_number = $checkout_response->get_ticket_number();

		} catch ( Exception $exception ) {

			wp_send_json_error( $exception->getMessage(), $exception->getCode() );
			exit;
		}

		wp_send_json_success( $ticket_number );
	}


	/**
	 * Adds payment method data.
	 *
	 * @internal
	 *
	 * @see Gateway_Checkout_Block_Integration::get_payment_method_data()
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $payment_method_data
	 * @param \WC_Gateway_Moneris_Checkout_Credit_Card $gateway
	 * @return array<string, mixed>
	 */
	public function add_payment_method_data( array $payment_method_data, SV_WC_Payment_Gateway $gateway ) : array {

		$payment_method_data['gateway'] = array_merge(
			$payment_method_data['gateway'] ?: [],
			[
				'form_type'           => $gateway->get_form_type(),
				'dynamic_descriptor'  => $gateway->get_dynamic_descriptor(),
				'avs_neither_match'   => $gateway->get_option( 'avs_either_match', 'reject' ),
				'avs_zip_match'       => $gateway->get_option( 'avs_zip_match', 'accept' ),
				'avs_street_match'    => $gateway->get_option( 'avs_street_match', 'accept' ),
				'avs_not_verified'    => $gateway->get_option( 'avs_not_verified', 'accept' ),
				'ticket_number_nonce' => wp_create_nonce( $this->get_ticket_number_action_name() ),
			]
		);

		$payment_method_data['flags'] = array_merge(
			$payment_method_data['flags'] ?: [],
			[
				'is_avs_enabled'        => $gateway->avs_enabled(),
				'is_csc_required'       => $gateway->csc_required(),
				'is_3d_secure_enabled'  => $gateway->is_3d_secure_enabled(),
				'is_pay_page_enabled'   => $gateway->is_pay_page_enabled(),
				'is_apple_pay_enabled'  => $gateway->apple_pay_enabled(),
				'is_google_pay_enabled' => $gateway->google_pay_enabled(),
			]
		);

		return $payment_method_data;
	}


}
