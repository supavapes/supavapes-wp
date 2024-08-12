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
 * Moneris Checkout API Class.
 *
 * Handles sending/receiving/parsing of Moneris JSON checkout API
 *
 * @since 3.0.0
 */
#[\AllowDynamicProperties]
class WC_Moneris_Checkout_API extends Framework\SV_WC_API_Base {


	/** production environment */
	const ENVIRONMENT_PRODUCTION = 'prod';

	/** staging environment */
	const ENVIRONMENT_STAGING = 'qa';

	/** the production endpoint URL */
	const ENDPOINT_PRODUCTION = 'https://gateway.moneris.com';

	/** the staging endpoint URL */
	const ENDPOINT_STAGING = 'https://gatewayt.moneris.com';


	/** @var string identifier */
	private $id;

	/** @var string Moneris store id */
	private $store_id;

	/** @var string Moneris API token for store id */
	private $api_token;

	/** @var string Moneris API checkout id */
	private $checkout_id;

	/** @var string environment, one of 'qa' or 'prod' */
	private $environment;


	/**
	 * Constructor - setup request object and set endpoint.
	 *
	 * @since 3.0.0
	 *
	 * @param string $id identifier
	 * @param string $store_id Moneris store id
	 * @param string $api_token Moneris API token for store id
	 * @param string $checkout_id Moneris API checkout id
	 * @param string $environment, one of 'qa' or 'prod'
	 */
	public function __construct( $id, $store_id, $api_token, $checkout_id, $environment ) {

		$this->id          = $id;
		$this->request_uri = $this->set_request_uri( $environment );
		$this->store_id    = $store_id;
		$this->api_token   = $api_token;
		$this->environment = $environment;
		$this->checkout_id = $checkout_id;

		$this->set_request_content_type_header( 'application/json' );
		$this->set_request_accept_header( 'application/json' );
	}


	/**
	 * Creates a checkout request.
	 *
	 * @since 3.0.0
	 *
	 * @param int|float|null $test_amount optional
	 * @return \WC_Moneris_Checkout_API_Response
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function create_checkout_request( $test_amount = null ) {

		$request = $this->get_new_request();

		$request->create_checkout_request( $test_amount );

		/** @var \WC_Moneris_Checkout_API_Response $response */
		$response = $this->perform_request( $request );

		return $response;
	}


	/**
	 * Creates a checkout request.
	 *
	 * @since 3.0.0
	 *
	 * @param string $ticket ticket number for receipt
	 * @param \WC_Order $order the order object
	 * @return \WC_Moneris_Checkout_API_Response
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function create_receipt_request( string $ticket, WC_Order $order ) {

		$request = $this->get_new_request();

		$request->create_receipt_request( $ticket, $order );

		return $this->perform_request( $request );
	}


	/**
	 * Sets the request URI according to the environment.
	 *
	 * @since 3.0.0
	 *
	 * @param string $environment the desired environment
	 */
	private function set_request_uri( string $environment ) {

		if ( static::ENVIRONMENT_PRODUCTION === $environment ) {
			return self::ENDPOINT_PRODUCTION;
		}

		return self::ENDPOINT_STAGING;
	}


	/**
	 * Gets a new API request object.
	 *
	 * @since 3.0.0
	 *
	 * @param array $args optional request arguments
	 * @return \WC_Moneris_Checkout_API_Request API request object
	 */
	protected function get_new_request( $args = [] ) {

		// default handler
		$this->response_handler = 'WC_Moneris_Checkout_API_Response';

		/**
		 * Filters the new API request args.
		 *
		 * @since 3.0.0
		 *
		 * @param array {
		 *
		 *    @type string $gatewayId   gateway ID
		 *    @type string $storeId     configured store ID
		 *    @type string $apiToken    configured API token
		 *    @type string $checkoutId  configured checkout Id
		 *    @type string $environment configured environment
		 * } $args
		 * @param WC_Moneris_Checkout_API $api the API object
		 */
		$args = (array) apply_filters( 'wc_moneris_checkout_api_new_request_args', array_merge( $args, [
			'gatewayId'   => $this->id,
			'storeId'     => $this->store_id,
			'apiToken'    => $this->api_token,
			'checkoutId'  => $this->checkout_id,
			'environment' => $this->environment,
		] ), $this );

		return new \WC_Moneris_Checkout_API_Request( $args['gatewayId'], $args['storeId'], $args['apiToken'], $args['checkoutId'], $args['environment'] );
	}


	/**
	 * Gets the main plugin instance.
	 *
	 * @see Framework\SV_WC_API_Base::get_plugin()
	 *
	 * @since 3.0.0
	 *
	 * @return \WC_Moneris
	 */
	protected function get_plugin() {
		return wc_moneris();
	}


	/**
	 * Returns the gateway instance associated with this request.
	 *
	 * @since 3.0.0
	 *
	 * @return WC_Gateway_Moneris_Checkout_Credit_Card
	 */
	public function get_gateway() {

		return wc_moneris()->get_gateway( $this->id );
	}


	/**
	 * Removes the tokenized payment method.
	 *
	 * @since 3.0.0
	 *
	 * @param string $token the payment method token
	 * @param string $customer_id unique Moneris customer ID
	 * @return Framework\SV_WC_API_Response&\WC_Moneris_API_Response
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function remove_tokenized_payment_method( string $token, int $customer_id ) {

		$this->request_uri = $this->get_gateway()->get_api_endpoint();
		$this->set_request_content_type_header( 'application/xml' );
		$this->set_request_accept_header( 'application/xml' );

		$request = $this->get_api_request();

		$request->delete_tokenized_payment_method( $token, $customer_id );

		$this->response_handler = 'WC_Moneris_API_Delete_Payment_Token_Response';

		return $this->perform_request( $request );
	}


	/**
	 * Return the parsed response object for the request, overridden primarily
	 * to provide the request object to the response classes, as Auth.net does not
	 * return some useful data (like a credit card's expiration date) in the response
	 * so it must be retrieved from the request.
	 *
	 * @since 3.0.0
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
	 * Returns false, as Moneris does not support a tokenized payment method query request.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function supports_get_tokenized_payment_methods() {
		return false;
	}


	/**
	 * Get api request.
	 *
	 * @since 3.0.0
	 *
	 * @return WC_Moneris_API_Request
	 */
	protected function get_api_request() {
		return new WC_Moneris_API_Request( $this->id, $this->store_id, $this->api_token );
	}


	 /**
	 * Determines if updating tokenized payment methods via API is supported.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function supports_update_tokenized_payment_method() {

		return false;
	}


}
