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
defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_12_1 as Framework;

/**
 * Handle the payment tokenization related functionality.
 *
 * @since 2.5.0
 *
 * @method \WC_Gateway_Moneris_Credit_Card|\WC_Gateway_Moneris_Interac get_gateway()
 */
class WC_Gateway_Moneris_Payment_Tokens_Handler extends Framework\SV_WC_Payment_Gateway_Payment_Tokens_Handler {


	/** @var WC_Moneris_Checkout_API_Response instance */
	private $tokenization_response;


	/**
	 * Sets the tokenization response.
	 *
	 * @since 3.0.0
	 *
	 * @param WC_Moneris_Checkout_API_Response $response tokenization response
	 */
	public function set_tokenization_response( $response ) {

		$this->tokenization_response = $response;
	}


	/**
	 * Tokenizes the current payment method and adds the standard transaction data to the order post record.
	 *
	 * @since 2.5.0
	 *
	 * @param WC_Order $order the order object
	 * @param WC_Moneris_API_Response $response optional create payment token response, or null if the tokenize payment method request should be made
	 * @return WC_Order the order object
	 * @param string $environment_id optional environment id, defaults to plugin current environment
	 * @throws Exception on network error or request error
	 * @throws Framework\SV_WC_Plugin_Exception if payment method tokenization is not supported
	 */
	public function create_token( WC_Order $order, $response = null, $environment_id = null ) {

		if ( isset( $this->tokenization_response ) ) {
			// the actual tokenization response (auth response) from the Moneris Checkout transaction was passed in previously,
			// so use that instead of what may be capture transaction
			$response = $this->tokenization_response;
		}

		// tokenize a previous transaction, so long as it wasn't placed with a temporary token, which must be converted to a permanent token
		if ( $response ) {

			if ( ! Framework\SV_WC_Helper::get_posted_value( 'wc-moneris-temp-payment-token' ) ) {
				// normal credit card account number direct tokenization
				$order->payment->receipt_id = $response->get_receipt_id();
				$order->payment->trans_id   = $response->get_transaction_id();
			} else {
				// temporary token to permanent token, use the masked pan returned by the tokenized request
				$order->payment->card_type = Framework\SV_WC_Payment_Gateway_Helper::card_type_from_account_number( $response->get_masked_pan() );
			}

			// record the issuer id for subsequent transaction
			if ( $response->get_issuer_id() ) {
				$order->payment->issuer_id = $response->get_issuer_id();
			}
		}

		// record the issuer id for subsequent transaction
		if ( $response->get_issuer_id() ) {
			$order->payment->issuer_id = $response->get_issuer_id();
		}

		// when changing a subscriptions payment method, blank out the original trans id/receipt id so that a new card number will be run and tokenized
		if ( isset( $_POST['woocommerce_change_payment'] ) && $_POST['woocommerce_change_payment'] ) {
			$order->payment->trans_id   = null;
			$order->payment->receipt_id = null;
		}

		return parent::create_token( $order, $response, $environment_id );
	}


	/**
	 * Gets the payment token object identified by $token from the user identified by $user_id.
	 *
	 * @since 2.5.0
	 *
	 * @param int $user_id WordPress user ID, or 0 for guest
	 * @param string $token payment token
	 * @param string|null $environment_id optional environment ID
	 * @return Framework\SV_WC_Payment_Gateway_Payment_Token|null
	 */
	public function get_token( $user_id, $token, $environment_id = null ) {

		if ( Framework\SV_WC_Helper::get_posted_value( 'wc-moneris-temp-payment-token' ) && $this->get_gateway()->hosted_tokenization_available() ) {

			$exp_month = Framework\SV_WC_Helper::get_posted_value( 'wc-moneris-exp-month' );
			$exp_year  = Framework\SV_WC_Helper::get_posted_value( 'wc-moneris-exp-year' );
			$expiry    = Framework\SV_WC_Helper::get_posted_value( 'wc-moneris-expiry' );

			if ( ! $exp_month & ! $exp_year && $expiry ) {
				[ $exp_month, $exp_year ] = array_map( 'trim', explode( '/', $expiry ) );
			}

			// working with a hosted tokenization temp token
			return new Framework\SV_WC_Payment_Gateway_Payment_Token(
				Framework\SV_WC_Helper::get_posted_value( 'wc-moneris-payment-token' ),
				[
					'type'      => 'credit_card',
					'exp_month' => $exp_month,
					'exp_year'  => $exp_year,
				]
			);
		}

		// normal behavior
		return parent::get_token( $user_id, $token, $environment_id );
	}


	/**
	 * Determines if the current payment method should be tokenized.
	 *
	 * Should tokenize when requested by customer or otherwise forced.
	 * This parameter is passed from the checkout page/payment form.
	 *
	 * @since 2.5.0
	 *
	 * @return bool true if the current payment method should be tokenized
	 */
	public function should_tokenize() {

		// make the temp payment token permanent
		return Framework\SV_WC_Helper::get_posted_value( 'wc-moneris-tokenize-payment-method' )
			&& ( ! Framework\SV_WC_Helper::get_posted_value( 'wc-moneris-payment-token' )
				|| Framework\SV_WC_Helper::get_posted_value( 'wc-moneris-temp-payment-token' ) );
	}


	/**
	 * Builds the token object.
	 *
	 * @since 3.0.0
	 *
	 * @param string $token payment token
	 * @param \WC_Payment_Token|array $data {
	 *     Payment token data.
	 *
	 *     @type bool   $default   Optional. Indicates this is the default payment token
	 *     @type string $type      Payment type. Either 'credit_card' or 'check'
	 *     @type string $last_four Last four digits of account number
	 *     @type string $card_type Credit card type (`visa`, `mc`, `amex`, `disc`, `diners`, `jcb`) or `echeck`
	 *     @type string $exp_month Optional. Expiration month (credit card only)
	 *     @type string $exp_year  Optional. Expiration year (credit card only)
	 * }
	 * @return WC_Gateway_Moneris_Payment_Token payment token
	 */
	public function build_token( $token, $data ) {

		return new \WC_Gateway_Moneris_Payment_Token( $token, $data );
	}


}
