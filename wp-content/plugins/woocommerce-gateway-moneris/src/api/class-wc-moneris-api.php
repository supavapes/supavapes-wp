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
 * Moneris API Class.
 *
 * Handles sending/receiving/parsing of Moneris XML, this is the main API
 * class responsible for communication with the Moneris API
 *
 * @since 2.0
 */
class WC_Moneris_API extends Framework\SV_WC_API_Base implements Framework\SV_WC_Payment_Gateway_API {


	/** @var string identifier */
	private $id;

	/** @var string Moneris store id */
	private $store_id;

	/** @var string Moneris API token for store id */
	private $api_token;

	/** @var string integration country, one of 'ca' or 'us' */
	private $integration;

	/** @var \WC_Order order associated with the request */
	protected $order;


	/**
	 * Constructor - setup request object and set endpoint.
	 *
	 * @since 2.0
	 *
	 * @param string $id identifier
	 * @param string $api_endpoint API URL endpoint
	 * @param string $store_id Moneris store id
	 * @param string $api_token Moneris API token for store id
	 * @param string $integration optional integration country, one of 'ca' or 'us', defaults to 'ca'
	 */
	public function __construct( $id, $api_endpoint, $store_id, $api_token, $integration = 'ca' ) {

		$this->id          = $id;
		$this->request_uri = $api_endpoint;
		$this->store_id    = $store_id;
		$this->api_token   = $api_token;
		$this->integration = $integration;

		$this->set_request_content_type_header( 'application/xml' );
		$this->set_request_accept_header( 'application/xml' );
	}


	/** Request methods ******************************************************/


	/**
	 * Create a new cc charge (purchase) transaction using Moneris XML API.
	 *
	 * This request, if successful, causes a charge to be incurred by the
	 * specified credit card. Notice that the authorization for the charge is
	 * obtained when the card issuer receives this request. The resulting
	 * authorization code is returned in the response to this request.
	 *
	 * @since 2.0.0
	 *
	 * @param \WC_Order $order order object
	 * @return Framework\SV_WC_API_Response|WC_Moneris_API_Response API response object
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function credit_card_charge( WC_Order $order ) {

		$this->order = $order;

		$request = $this->get_new_request();

		$request->credit_card_charge( $order );

		return $this->perform_request( $request );
	}


	/**
	 * Creates a new cc auth (pre-auth) transaction using Moneris XML API.
	 *
	 * This request is used for a transaction in which the merchant needs
	 * authorization of a charge, but does not wish to actually make the charge
	 * at this point in time. For example, if a customer orders merchandise to
	 * be shipped, you could issue this request at the time of the order to
	 * make sure the merchandise will be paid for by the card issuer. Then at
	 * the time of actual merchandise shipment, you perform the actual charge
	 * using the capture request.
	 *
	 * Note: A PreAuth transaction must be reversed within 72 hours by sending
	 * a $0 capture if funds are not to be captured
	 *
	 * @since 2.0.0
	 *
	 * @param \WC_Order $order order object
	 * @return Framework\SV_WC_API_Response|\WC_Moneris_API_Response API response object
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function credit_card_authorization( WC_Order $order ) {

		$this->order = $order;

		$request = $this->get_new_request();

		$request->credit_card_auth( $order );

		return $this->perform_request( $request );
	}


	/**
	 * Capture funds for a credit card authorization (pre-auth) using Moneris XML API.
	 *
	 * This request can be made only after a previous and successful
	 * authorization (pre-auth) request, where the card issuer has authorized a
	 * charge to be made against the specified credit card in the future. The
	 * order_id and txn_number from that prior transaction must be used in this
	 * subsequent and related transaction. This request actually causes that
	 * authorized charge to be incurred against the customer's credit card.
	 *
	 * @since 2.0.0
	 *
	 * @param WC_Order $order order object
	 * @return Framework\SV_WC_API_Response|\WC_Moneris_API_Response API response object
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function credit_card_capture( WC_Order $order ) {

		$this->order = $order;

		$request = $this->get_new_request();

		$request->credit_card_capture( $order );

		return $this->perform_request( $request );
	}


	/**
	 * Reverse a credit card authorization (pre-auth) using Moneris XML API.
	 *
	 * This request can be made only after a previous and successful
	 * authorization (pre-auth) request. The order_id and txn_number from that
	 * prior transaction must be used in this subsequent and related transaction.
	 *
	 * An authorization must either be captured or reversed within 72 hours
	 *
	 * @since 2.0.0
	 *
	 * @param WC_Order $order order object
	 * @return Framework\SV_WC_API_Response|\WC_Moneris_API_Response API response object
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function credit_card_authorization_reverse( WC_Order $order ) {

		$this->order = $order;

		// an authorization is reversed by capturing $0
		$order->capture->amount = '0.00';

		$request = $this->get_new_request();

		$request->credit_card_capture( $order );

		return $this->perform_request( $request );
	}


	/**
	 * Performs a refund for the given order.
	 *
	 * If the gateway does not support refunds, this method can be a no-op.
	 *
	 * @since 2.2.0
	 *
	 * @param \WC_Order $order order object
	 * @return Framework\SV_WC_API_Response|\WC_Moneris_API_Response API response object
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function refund( WC_Order $order ) {

		$this->order = $order;

		$request = $this->get_new_request();

		$request->refund( $order );

		return $this->perform_request( $request );
	}


	/**
	 * Performs a void for the given order.
	 *
	 * If the gateway does not support voids, this method can be a no-op.
	 *
	 * @since 2.2.0
	 *
	 * @param \WC_Order $order order object
	 * @return Framework\SV_WC_API_Response|\WC_Moneris_API_Response API response object
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function void(WC_Order $order) {

		/* Voids are triggered when a payment is authorised, but not captured.
		 * However, Moneris can process voids, called "payment corrections", only
		 * AFTER a transaction has been captured. For payments that have been authorised,
		 * but not captured, an "authorisation reverse" has to be issued.
		 *
		 * @link https://developer.moneris.com/Documentation/NA/E-Commerce%20Solutions/API/Purchase%20Correction?lang=php
		 */
		return $this->credit_card_authorization_reverse( $order );
	}


	/**
	 * Store sensitive payment information for a particular customer.
	 *
	 * If the $order object has both `wc_moneris_trans_id` and `wc_moneris_receipt_id`
	 * members, the ResTokenizeCC request will be used to tokenize an existing
	 * transaction.  Otherwise, the ResAddCC request will be used.
	 *
	 * @since 2.0.0
	 *
	 * @param \WC_Order $order order object
	 * @return Framework\SV_WC_API_Response|\WC_Moneris_API_Response
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function tokenize_payment_method(WC_Order $order) {
		$this->order = $order;

		$request = $this->get_new_request();

		$request->tokenize_payment_method( $order );

		$this->response_handler = 'WC_Moneris_API_Create_Payment_Token_Response';

		return $this->perform_request( $request );
	}


	/**
	 * Removes the tokenized payment method.
	 *
	 * @since 2.0.0
	 *
	 * @param string $token the payment method token
	 * @param string $customer_id unique Moneris customer ID
	 * @return Framework\SV_WC_API_Response|\WC_Moneris_API_Response
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function remove_tokenized_payment_method( $token, $customer_id ) {

		$request = $this->get_new_request();

		$request->delete_tokenized_payment_method( $token, $customer_id );

		$this->response_handler = 'WC_Moneris_API_Delete_Payment_Token_Response';

		return $this->perform_request( $request );
	}


	/**
	 * Determines if Moneris supports a tokenized payment method remove request.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public function supports_remove_tokenized_payment_method() {
		return true;
	}


	/** Interac methods ******************************************************/


	/**
	 * Confirms an Interac idebit transaction by sending a purchase request.
	 *
	 * @since 2.0.0
	 *
	 * @param \WC_Order $order the order
	 * @return Framework\SV_WC_API_Response|\WC_Moneris_API_Response
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function idebit_purchase( WC_Order $order ) {

		$this->order = $order;

		$request = $this->get_new_request();

		$request->idebit_purchase( $order );

		$this->response_handler = 'WC_Moneris_API_iDebit_Response';

		return $this->perform_request( $request );
	}


	/** Helper methods ******************************************************/


	/**
	 * Gets a new API request object.
	 *
	 * @since 2.0.0
	 *
	 * @param array $args optional request arguments
	 * @return \WC_Moneris_API_Request API request object
	 */
	protected function get_new_request( $args = [] ) {

		// default handler
		$this->response_handler = 'WC_Moneris_API_Response';

		/**
		 * Filters the new API request args.
		 *
		 * @since 2.7.1
		 *
		 * @param array {
		 *
		 *    @type string $id          identifier
		 *    @type string $store_id    configured store ID
		 *    @type string $api_token   configured API token
		 *    @type string $integration configured integration
		 * }
		 */
		$args = apply_filters( 'wc_moneris_api_new_request_args', array_merge( $args, [
			'id'          => $this->id,
			'store_id'    => $this->store_id,
			'api_token'   => $this->api_token,
			'integration' => $this->integration,
		] ), $this->get_order(), $this);

		return new \WC_Moneris_API_Request( $args['id'], $args['store_id'], $args['api_token'], $args['integration'] );
	}


	/**
	 * Return the parsed response object for the request, overridden primarily
	 * to provide the request object to the response classes, as Auth.net does not
	 * return some useful data (like a credit card's expiration date) in the response
	 * so it must be retrieved from the request.
	 *
	 * @since 2.11.0
	 *
	 * @see Framework\SV_WC_API_Base::get_parsed_response()
	 *
	 * @param string $raw_response_body
	 * @return object response class instance which implements Framework\SV_WC_API_Request
	 */
	protected function get_parsed_response( $raw_response_body ) {

		$handler_class = $this->get_response_handler();

		return new $handler_class( $this->get_request(), $raw_response_body );
	}


	/**
	 * Returns the order associated with a request, if any.
	 *
	 * @since 2.3.2
	 *
	 * @return \WC_Order
	 */
	public function get_order() {
		return $this->order;
	}


	/**
	 * Returns the gateway instance associated with this request.
	 *
	 * @since 2.3.2
	 *
	 * @return string
	 */
	public function get_gateway() {
		return wc_moneris()->get_gateway( $this->id );
	}


	/**
	 * Gets the main plugin instance.
	 *
	 * @see Framework\SV_WC_API_Base::get_plugin()
	 *
	 * @since 2.11.0
	 *
	 * @return \WC_Moneris
	 */
	protected function get_plugin() {
		return wc_moneris();
	}


	/** No-op methods ******************************************************/


	/**
	 * Moneris does not support updating tokenized payment methods.
	 *
	 * @since 2.11.0
	 *
	 * @param \WC_Order $order order object
	 */
	public function update_tokenized_payment_method( WC_Order $order ) {
		// no-op
	}


	/**
	 * Determines if updating tokenized payment methods via API is supported.
	 *
	 * @since 2.11.0
	 *
	 * @return bool
	 */
	public function supports_update_tokenized_payment_method() {
		return false;
	}


	/**
	 * Perform a customer ACH check debit transaction using the Moneris XML API.
	 *
	 * @since 2.0.0
	 *
	 * @param \WC_Order $order order object
	 */
	public function check_debit( WC_Order $order ) {
		// no-op
	}


	/**
	 * Moneris does not support retrieving all tokenized payment methods for a profile.
	 *
	 * It does however have a request to verify a payment token, which we could do something with.
	 *
	 * @since 2.0.0
	 *
	 * @param string $customer_id unique Moneris customer ID
	 */
	public function get_tokenized_payment_methods( $customer_id ) {
		// no-op
	}


	/**
	 * Returns false, as Moneris does not support a tokenized payment method query request.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public function supports_get_tokenized_payment_methods() {
		return false;
	}


}
