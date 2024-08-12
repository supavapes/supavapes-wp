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
 * Moneris API Response Message Handler.
 *
 * Builds customer-friendly response messages by mapping the various Moneris
 * error codes to standardized messages
 *
 * @link https://developer.moneris.com/More/Testing/Financial%20Response%20Codes for listing of error codes
 *
 * @since 2.10.0
 *
 * @see Framework\SV_WC_Payment_Gateway_API_Response_Message_Helper
 */
class WC_Moneris_API_Response_Message_Helper extends Framework\SV_WC_Payment_Gateway_API_Response_Message_Helper {


	/** @var \WC_Moneris_API_Response transaction response */
	protected $response;

	/** @var array */
	protected $reasons = [

		// declined response codes
		'050' => 'moneris_error_try_later',
		'051' => 'card_expired',
		'052' => 'moneris_error_try_later',
		'053' => 'moneris_error_try_later',
		'054' => 'moneris_error_try_later',
		'055' => 'card_type_not_accepted',
		'056' => 'card_type_not_accepted',
		'057' => 'card_declined',
		'058' => 'card_declined',
		'059' => 'card_declined',
		'060' => 'card_declined',
		'061' => 'card_declined',
		'062' => 'moneris_error_try_later',
		'063' => 'moneris_error_try_later',
		'064' => 'moneris_error_try_later',
		'065' => 'error',
		'066' => 'moneris_error_try_later',
		'067' => 'moneris_error_try_later',
		'068' => 'moneris_error_try_later',
		'069' => 'moneris_error_try_later',
		'070' => 'card_type_invalid',
		'071' => 'card_type_invalid',
		'072' => 'moneris_error_try_later',
		'073' => 'card_type_invalid',
		'074' => 'moneris_error_try_later',
		'075' => 'moneris_error_try_later',
		'076' => 'insufficient_funds',
		'077' => 'credit_limit_reached',
		'078' => 'moneris_error_try_later',
		'079' => 'credit_limit_reached',
		'080' => 'credit_limit_reached',
		'081' => 'credit_limit_reached',
		'082' => 'card_declined',
		'083' => 'credit_limit_reached',
		'084' => 'error',
		'085' => 'card_type_not_accepted',
		'086' => 'moneris_error_try_later',
		'087' => 'error',
		'088' => 'moneris_error_try_later',
		'089' => 'card_declined',
		'090' => 'moneris_error_try_later',
		'091' => 'moneris_error_try_later',
		'092' => 'error',
		'093' => 'moneris_error_try_later',
		'094' => 'moneris_error_try_later',
		'095' => 'moneris_error_try_later',
		'096' => 'moneris_error_try_later',
		'097' => 'moneris_error_try_later',
		'098' => 'moneris_error_try_later',
		'099' => 'moneris_error_try_later',

		// system error response codes
		'150' => 'moneris_error_try_later',
		'200' => 'moneris_error_try_later',
		'201' => 'moneris_error_try_later',
		'202' => 'moneris_error_try_later',
		'203' => 'moneris_error_try_later',
		'204' => 'error',
		'205' => 'moneris_error_try_later',
		'206' => 'error',
		'207' => 'moneris_error_try_later',
		'208' => 'moneris_error_try_later',
		'209' => 'moneris_error_try_later',
		'210' => 'moneris_error_try_later',
		'212' => 'error',
		'251' => 'moneris_error_try_later',
		'252' => 'error',

		// credit card decline codes
		'475' => 'card_expiry_invalid',
		'476' => 'decline',
		'477' => 'decline',
		'478' => 'decline',
		'479' => 'decline',
		'480' => 'decline',
		'481' => 'decline',
		'482' => 'card_expired',
		'483' => 'decline',
		'484' => 'card_expired',
		'485' => 'card_declined',
		'486' => 'csc_mismatch',
		'487' => 'csc_mismatch',
		'489' => 'csc_mismatch',
		'490' => 'csc_mismatch',

		// system decline response codes
		'800' => 'moneris_error_try_later',
		'801' => 'moneris_error_try_later',
		'802' => 'moneris_error_try_later',
		'809' => 'moneris_error_try_later',
		'810' => 'moneris_error_try_later',
		'811' => 'moneris_error_try_later',
		'821' => 'moneris_error_try_later',
		'877' => 'moneris_error_try_later',
		'878' => 'moneris_error_try_later',
		'889' => 'moneris_error_try_later',
		'898' => 'moneris_error_try_later',
		'899' => 'moneris_error_try_later',
		'900' => 'decline',
		'901' => 'card_expired',
		'902' => 'decline',
		'903' => 'decline',
		'904' => 'error',
		'905' => 'decline',
		'906' => 'decline',
		'907' => 'decline',
		'908' => 'decline',
		'909' => 'decline',
	];


	/**
	 * Initializes the API response message handler.
	 *
	 * @since 2.10.0
	 *
	 * @param \WC_Moneris_API_Response $response
	 */
	public function __construct( $response ) {
		$this->response = $response;
	}


	/**
	 * Gets the user-facing error/decline message.
	 *
	 * Used in place of the get_user_message() method because this class is instantiated with the response class and handles generating the message ID internally.
	 *
	 * @since 2.10.0
	 *
	 * @return string
	 */
	public function get_message() {

		$response_code = $this->get_response()->get_status_code();
		$message_id    = $this->reasons[ $response_code ] ?? null;

		if ( null === $message_id && $this->get_response()->transaction_held() ) {
			$message_id = 'held_for_review';
		}

		return $this->get_user_message( $message_id );
	}


	/**
	 * Gets a message appropriate for a frontend user.
	 *
	 * This should be used to provide enough information to a user to allow them to resolve an issue on their own, but not enough to help nefarious folks fishing for info.
	 *
	 * Adds a custom moneris-specific error message.
	 *
	 * @since 2.10.0
	 *
	 * @see Framework\SV_WC_Payment_Gateway_API_Response_Message_Helper::get_user_message()
	 *
	 * @param string $message_id identifies the message to return
	 * @return string a user message
	 */
	public function get_user_message( $message_id ) {

		$message = 'moneris_error_try_later' === $message_id
				 ? __( 'Oops, sorry! A temporary error occurred. Please try again in 5 minutes.', 'woocommerce-gateway-moneris' )
				 : parent::get_user_message( $message_id );

		if ( ! $message ) {
			$message = esc_html__( 'We cannot process your order with the payment information that you provided. Please use a different payment account or an alternate payment method.', 'woocommerce-gateway-moneris' );
		}

		/*
		 * Filters the message that is returned by this handler when there is an error response in the API.
		 *
		 * @since 2.10.0
		 *
		 * @param string $message The message returned to the user
		 * @param string $message_id the id which corresponds with this message
		 * @param \WC_Moneris_API_Response_Message_Helper $response_message_helper_instance
		 */
		return apply_filters( 'wc_moneris_api_response_user_message', $message, $message_id, $this );
	}


	/**
	 * Gets the response object for this user message.
	 *
	 * @since 2.10.0
	 *
	 * @return \WC_Moneris_API_Response
	 */
	public function get_response() {
		return $this->response;
	}


}
