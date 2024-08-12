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

use SkyVerge\WooCommerce\PluginFramework\v5_12_1 as Framework;

defined( 'ABSPATH' ) or exit;

/**
 * Moneris Checkout API response object.
 *
 * @since 3.0.0
 */
#[\AllowDynamicProperties]
class WC_Moneris_Checkout_API_Response extends Framework\SV_WC_API_JSON_Response implements Framework\SV_WC_Payment_Gateway_API_Response {


	/** @var bool used to mark this response as failed */
	private $failed = null;

	/** @var bool used to mark this response as held */
	private $held = false;

	/** @var string overrides the response message */
	private $status_message = null;


	/**
	 * Build the data object from the raw JSON.
	 *
	 * @since 3.0.0
	 *
	 * @param WC_Moneris_API_Request $request the request that resulted in this response
	 * @param string $raw_response_json The raw JSON
	 */
	public function __construct( $request, $raw_response_json ) {

		$this->request           = $request;
		$this->raw_response_json = $raw_response_json;
		$this->response_data     = json_decode( $raw_response_json );

		if ( is_object( $this->response_data ) && isset( $this->response_data->response ) ) {
			$this->response_data = $this->response_data->response;
		}
	}


	/**
	 * Confirms if the response was approved.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function transaction_approved() {

		// transaction success overridden?
		if ( true === $this->failed ) {
			return false;
		}

		return $this->is_successful() && is_numeric( $this->get_status_code() ) && $this->get_status_code() < 50;
	}


	/**
	 * Overrides the response message returned by Moneris.
	 *
	 * @since 3.0.0
	 *
	 * @param string $status_message the new response message
	 */
	public function set_status_message( $status_message ) {
		$this->status_message = $status_message;
	}


	/**
	 * Confirms if the response was approved.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function is_successful() {
		return 'true' === $this->__get( 'success' );
	}


	/**
	 * Returns the result of the AVS check:.
	 *
	 * + `Z` - zip match, locale no match
	 * + `A` - zip no match, locale match
	 * + `N` - zip no match, locale no match
	 * + `Y` - zip match, locale match
	 * + `U` - zip and locale could not be verified
	 * + null - unsupported card, or unknown response
	 *
	 * @since 3.0.0
	 *
	 * @see Framework\SV_WC_Payment_Gateway_API_Authorization_Response::get_avs_result()
	 * @return string result of the AVS check, if any
	 */
	public function get_avs_result() {

		$card_type = $this->get_card_type();
		$code      = $this->get_avs_result_code();

		return self::get_avs_code_mapping( $card_type, $code );
	}


	/**
	 * Translates the card-specific issuer AVS code to our simplified, unified
	 * scheme.
	 *
	 * + `Z` - zip match, locale no match
	 * + `A` - zip no match, locale match
	 * + `N` - zip no match, locale no match
	 * + `Y` - zip match, locale match
	 * + `U` - zip and locale could not be verified
	 * + null - unsupported card, or unknown result code
	 *
	 * @since 3.0.0
	 *
	 * @see Framework\SV_WC_Payment_Gateway_API_Authorization_Response::get_avs_result()
	 *
	 * @return string result of the AVS check, if any
	 */
	public static function get_avs_code_mapping($card_type, $avs_code) {

		// shared mapping for visa/discover/jcb
		$visa_disc_jcb = [
			'D' => 'Y',
			'F' => 'Y',
			'M' => 'Y',
			'Y' => 'Y',
			'N' => 'N',
			'P' => 'Z',
			'Z' => 'Z',
			'B' => 'A',
			'A' => 'A',
			'C' => 'U',
			'G' => 'U',
			'I' => 'U',
			'R' => 'U',
			'U' => 'U',
			'S' => 'U',
		];

		$lookup = [
			Framework\SV_WC_Payment_Gateway_Helper::CARD_TYPE_VISA       => $visa_disc_jcb,
			Framework\SV_WC_Payment_Gateway_Helper::CARD_TYPE_DISCOVER   => $visa_disc_jcb,
			Framework\SV_WC_Payment_Gateway_Helper::CARD_TYPE_JCB        => $visa_disc_jcb,
			Framework\SV_WC_Payment_Gateway_Helper::CARD_TYPE_MASTERCARD => [
				'X' => 'Y',
				'Y' => 'Y',
				'N' => 'N',
				'W' => 'Z',
				'Z' => 'Z',
				'A' => 'A',
				'R' => 'U',
				'S' => 'U',
				'U' => 'U',
			],
			Framework\SV_WC_Payment_Gateway_Helper::CARD_TYPE_AMEX => [
				'E' => 'Y',
				'M' => 'Y',
				'Y' => 'Y',
				'W' => 'N',
				'N' => 'N',
				'D' => 'Z',
				'L' => 'Z',
				'Z' => 'Z',
				'F' => 'A',
				'O' => 'A',
				'A' => 'A',
				'R' => 'U',
				'S' => 'U',
				'U' => 'U',
			],
		];

		if ( ! isset( $lookup[ $card_type ][ $avs_code ] ) ) {
			return null;
		}

		return $lookup[ $card_type ][ $avs_code ];
	}


	/**
	 * Indicates the address verification result.
	 *
	 * @since 3.0.0
	 *
	 * @return string result of the AVS check, or empty
	 */
	public function get_avs_result_code() {

		$result = $this->response_data->receipt->cc->fraud->avs->code ?? null;

		if ( 'null' === $result ) {
			$result = null;
		}

		return $result ? strtoupper( $result ) : null;
	}


	/**
	 * Gets 3Ds secure 2.0 result.
	 *
	 * @link https://developer.moneris.com/More/Testing/Testing%203D%20Solutions
	 *
	 * @since 3.0.0
	 *
	 * @return string result of the 3DS check: 'Y', 'N', 'A', 'U', 'R', 'U', 'C', or null
	 */
	public function get_3ds_result() {

		$result = $this->response_data->receipt->cc->fraud->{'3d_secure'}->details->transStatus ?? null;

		if ( 'null' === $result ) {
			$result = null;
		}

		return $result;
	}


	/**
	 * Returns the result of the CSC check.  One of our standardized codes:.
	 *
	 * + `M` - CSC match
	 * + `N` - CSC no match
	 * + `U` - CSC could not be verified, or unknown result code
	 * + null - unsupported card
	 *
	 * @since 3.0.0
	 *
	 * @see Framework\SV_WC_Payment_Gateway_API_Authorization_Response::get_csc_result()
	 *
	 * @return string result of CSC check, or null for unsupported card type
	 */
	public function get_csc_result() {

		$card_type   = $this->get_card_type();
		$result_code = $this->get_csc_result_code();

		// pluck off the alphabetical response code, if there is one
		if ( ! $result_code ) {
			$response_code = null;
		} else {
			$response_code = trim( $result_code[1] );
		}

		return self::get_csc_code_mapping( $card_type, $response_code );
	}


	/**
	 * Indicates the raw CSC result.
	 *
	 * @since 3.0.0
	 *
	 * @return string result of the CSC check, or empty
	 */
	public function get_csc_result_code() {

		$result = $this->response_data->receipt->cc->fraud->cvd->code ?? null;

		if ( 'null' === $result ) {
			$result = null;
		}

		return $result;
	}


	/**
	 * Marks this response as failed.
	 *
	 * @since 3.0.0
	 */
	public function failed() {
		$this->failed = true;
	}


	/**
	 * Marks this response as held.
	 *
	 * @since 3.0.0
	 */
	public function held() {
		$this->held = true;
	}


	/**
	 * Translates the card-specific issuer CSC code to our simplified, unified
	 * scheme.
	 *
	 * + `M` - CSC match
	 * + `N` - CSC no match
	 * + `U` - CSC could not be verified, or unknown result code
	 * + null - unsupported card
	 *
	 * @since 3.0.0
	 *
	 * @see Framework\SV_WC_Payment_Gateway_API_Authorization_Response::get_cvd_result()
	 *
	 * @return string result of the CSC check, or null for unsupported card
	 */
	public static function get_csc_code_mapping( $card_type, $csc_code ) {

		// shared mapping for visa/mc/discover/jcb
		$all = [
			'M' => 'M',
			'N' => 'N',
			'P' => 'U',
			'U' => 'U',
		];

		$lookup = [
			Framework\SV_WC_Payment_Gateway_Helper::CARD_TYPE_VISA       => $all,
			Framework\SV_WC_Payment_Gateway_Helper::CARD_TYPE_DISCOVER   => $all,
			Framework\SV_WC_Payment_Gateway_Helper::CARD_TYPE_JCB        => $all,
			Framework\SV_WC_Payment_Gateway_Helper::CARD_TYPE_MASTERCARD => $all,
			Framework\SV_WC_Payment_Gateway_Helper::CARD_TYPE_AMEX       => $all + [
				'Y' => 'M',
				'R' => 'U',
			],
		];

		// unsupported card type
		if ( ! isset( $lookup[ $card_type ] ) ) {
			return null;
		}

		// map invalid code
		if ( ! isset( $lookup[ $card_type ][ $csc_code ] ) ) {
			return 'U';
		}

		return $lookup[ $card_type ][ $csc_code ];
	}


	/**
	 * Gets the card type, one of 'visa', 'mastercard', 'amex', 'discover', 'dinersclub', or 'jcb'.
	 *
	 * @since 3.0.0
	 *
	 * @return string normalized card type, or null if it can't be determined
	 */
	public function get_card_type() {

		$cardType = $this->response_data->receipt->cc->card_type ?? '';

		switch ((string) $cardType) {

			case 'V':  return Framework\SV_WC_Payment_Gateway_Helper::CARD_TYPE_VISA;
			case 'M':  return Framework\SV_WC_Payment_Gateway_Helper::CARD_TYPE_MASTERCARD;
			case 'AX': return Framework\SV_WC_Payment_Gateway_Helper::CARD_TYPE_AMEX;
			case 'C1': return Framework\SV_WC_Payment_Gateway_Helper::CARD_TYPE_JCB;
			case 'DC': return Framework\SV_WC_Payment_Gateway_Helper::CARD_TYPE_DINERSCLUB;
			case 'DI': return Framework\SV_WC_Payment_Gateway_Helper::CARD_TYPE_DISCOVER;

			default: return null;
		}
	}


	/**
	 * Returns the masked pan.
	 *
	 * @since 3.0.0
	 *
	 * @return string result of the CSC check, or empty
	 */
	public function get_masked_pan() {

		if ( isset( $this->response_data->receipt->cc->tokenize ) && 'true' === $this->response_data->receipt->cc->tokenize->success ) {
			return (string) $this->response_data->receipt->cc->tokenize->first4last4;
		}

		return '';
	}


	/**
	 * Confirms if the response was success.
	 *
	 * @since 3.0.0
	 *
	 * @return string|null ticket number
	 */
	public function get_ticket_number() {

		return $this->__get( 'ticket' ) ?? null;
	}


	/**
	 * Flags the refund as never held.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function transaction_held() : bool {
		return $this->held;
	}


	/**
	 * Gets the transaction status message.
	 *
	 * This is intended for the merchant.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_status_message() {

		// message overridden?
		if ( null !== $this->status_message ) {
			return $this->status_message;
		}

		$message = '';

		// special handling for missing status code
		if ( ! is_numeric( $this->get_status_code() ) ) {

			if ( false !== strpos( $message, "No permission for:'cvd_info'" ) ) {
				$message .= ' ' . __( 'Please enable CVD handling in your Moneris merchant profile, or contact your Moneris account rep to enable CVD handling for your account.', 'woocommerce-gateway-moneris' );
			}

			if ( $message ) {
				return sprintf( __( 'Transaction incomplete: %s', 'woocommerce-gateway-moneris' ), $message );
			} else {
				return __( 'Transaction incomplete', 'woocommerce-gateway-moneris' );
			}
		}

		return $message;
	}


	/**
	 * Gets the refund status code.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_status_code() : string {
		return $this->response_data->receipt->cc->response_code ?? '';
	}


	/**
	 * Gets the transaction ID.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_transaction_id() : string {
		return $this->response_data->receipt->cc->transaction_no ?? '';
	}


	/**
	 * Gets the transaction code.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_transaction_code() : string {
		return $this->response_data->receipt->cc->transaction_code ?? '';
	}


	/**
	 * Gets the receipt ID.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_receipt_id() : string {
		return $this->response_data->receipt->cc->order_no ?? '';
	}


	/**
	 * Gets the reference number.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_reference_num() : string {
		return $this->response_data->receipt->cc->reference_no ?? '';
	}


	/**
	 * Gets the payment type.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_payment_type() : string {
		return \WC_Gateway_Moneris_Credit_Card::PAYMENT_TYPE_CREDIT_CARD;
	}


	/**
	 * Gets the response user message.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_user_message() {
		$helper = new WC_Moneris_API_Response_Message_Helper( $this );

		return $helper->get_message();
	}


	/**
	 * Get last four digits of the successfully authorized credit card.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_last_four() {
		return substr( $this->response_data->receipt->cc->first6last4 ?? '', -4 );
	}


	/**
	 * Get expiration month of the successfully authorized credit card.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_exp_month() {
		return substr( $this->response_data->receipt->cc->expiry_date ?? '', 0, 2 );
	}


	/**
	 * Returns the request object that resulted in this response.
	 *
	 * @since 3.0.0
	 * @return WC_Moneris_Checkout_API_Request request object
	 */
	public function get_request() {
		return $this->request;
	}


	/**
	 * Get expiration year of the successfully authorized credit card.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_exp_year() {
		return substr( $this->response_data->receipt->cc->expiry_date ?? '', -2 );
	}


	/**
	 * Get issuer id for subsequent payment request if tokenize enable in MCO.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_issuer_id() {

		if ( isset( $this->response_data->receipt->cc->issuer_id ) && 'null' !== $this->response_data->receipt->cc->issuer_id ) {
			return (string) $this->response_data->receipt->cc->issuer_id;
		}

		return '';
	}


	/**
	 * Get vault card id for subsequent payment request if tokenize enable in MCO.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_payment_token_id() {

		if ( isset( $this->response_data->receipt->cc->tokenize ) && 'true' === $this->response_data->receipt->cc->tokenize->success ) {
			return (string) $this->response_data->receipt->cc->tokenize->datakey;
		}

		return '';
	}


	/**
	 * Returns the payment token.
	 *
	 * @since 3.0.0
	 *
	 * @return Framework\SV_WC_Payment_Gateway_Payment_Token payment token
	 */
	public function get_payment_token() {

		$order = $this->get_request()->get_order();

		$token = [
			'default'        => true,
			'type'           => 'credit_card',
			'account_number' => $this->get_masked_pan(),
			'last_four'      => $this->get_last_four(),
			'card_type'      => $order->payment->card_type,
			'exp_month'      => $this->get_exp_month(),
			'exp_year'       => $this->get_exp_year(),
			// Moneris tokens can't be used on transactions processed on a different currency
			'currency' => $order->get_currency( 'edit' ),
		];

		// Save the issuer id for subsequent transaction
		if ( $this->get_issuer_id() ) {
			$token['issuer_id'] = $this->get_issuer_id();
		}

		return new Framework\SV_WC_Payment_Gateway_Payment_Token( (string) $this->get_payment_token_id(), $token );
	}


}
