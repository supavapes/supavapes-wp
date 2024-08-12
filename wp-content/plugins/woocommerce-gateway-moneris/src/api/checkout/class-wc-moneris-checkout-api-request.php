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

defined( 'ABSPATH' ) or exit;

/**
 * Moneris Checkout API request object.
 *
 * @since 3.0.0
 */
#[\AllowDynamicProperties]
class WC_Moneris_Checkout_API_Request extends Framework\SV_WC_API_JSON_Request {


	/** @var string Moneris gateway id */
	private $id;

	/** @var string Moneris store id */
	private $store_id;

	/** @var string Moneris API token for store id */
	private $api_token;

	/** @var string Moneris API checkout id */
	private $checkout_id;

	/** @var string environment, one of 'qa' or 'prod' */
	private $environment;

	/** @var WC_Order optional order object if this request was associated with an order */
	protected $order;


	/**
	 * Construct a Moneris request object.
	 *
	 * @since 3.0.
	 *
	 * @param string $id Moneris gateway id
	 * @param string $store_id Moneris store id
	 * @param string $api_token Moneris API token for store id
	 * @param string $checkout_id Moneris configured checkout id
	 * @param string $environment Moneris configured environment
	 */
	public function __construct( $id, $store_id, $api_token, $checkout_id, $environment ) {

		$this->id          = $id;
		$this->store_id    = $store_id;
		$this->api_token   = $api_token;
		$this->checkout_id = $checkout_id;
		$this->environment = $environment;
	}


	/**
	 * Gets the request data.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public function get_data() {

		// required for every request
		$required_data = [
			'store_id'    => $this->store_id,
			'api_token'   => $this->api_token,
			'checkout_id' => $this->checkout_id,
			'environment' => $this->environment,
		];

		$this->request_data = array_merge( $required_data, $this->request_data );

		/**
		 * API Request Data
		 *
		 * Allow actors to modify the request data before it's sent to Moneris
		 *
		 * @since 3.0.0
		 *
		 * @param array $data request data to be filtered
		 * @param \WC_Moneris_Checkout_API_Request $this, API request class instance
		 */
		$this->request_data = apply_filters( 'wc_moneris_checkout_api_request_data', $this->request_data, $this );

		return $this->request_data;
	}


	/**
	 * Creates a checkout request.
	 *
	 * @since 3.0.0
	 *
	 * @param int|float|null $test_amount
	 */
	public function create_checkout_request( $test_amount = null ) {
		global $wp;

		$this->path = '/chkt/request/request.php';

		$customer_data = WC()->session->get( 'customer' );
		$order_amount  = WC()->cart->get_total( 'edit' );
		$order_number  = null;

		if ( is_checkout_pay_page() ) {

			$order_id = isset( $wp->query_vars['order-pay'] ) ? absint( $wp->query_vars['order-pay'] ) : 0;

			$this->order = wc_get_order( $order_id );

			if ( $order_id ) {

				$order_amount = $this->order->get_total();
				$order_number = $this->order->get_order_number();
				$retry_count  = (int) $this->order->get_meta( '_wc_'.\WC_Moneris::CREDIT_CARD_GATEWAY_ID.'_mco_retry_count' );

				// On subsequent loads (if any) of the MCO form for the same order append the retry count to the order number,
				// to avoid duplicate order ID errors from the checkout request endpoint, as each order number can only be used once.
				if ( $retry_count > 0 ) {
					$order_number .= '-'.$retry_count;
				}

				if ( 'qa' === $this->environment ) {
					$order_number .= '-'.uniqid( '', true );
				}

				$this->order->update_meta_data( '_wc_' . \WC_Moneris::CREDIT_CARD_GATEWAY_ID . '_mco_retry_count', $retry_count + 1 );
				$this->order->save_meta_data();
			}
		}

		$total_amount = Framework\SV_WC_Helper::number_format( $order_amount );

		if ( 'qa' === $this->environment ) {

			$test_id = 'wc-' . $this->id . '-test-amount';

			if ( isset( $_GET[ $test_id ] ) ) {

				$test_amount = $_GET[ $test_id ] ?: 0;

			} elseif ( isset( $_POST['post_data'] ) ) {

				parse_str( $_POST['post_data'], $query_params );

				if ( isset( $query_params[ $test_id ] ) ) {
					$test_amount = $query_params[ $test_id ];
				}
			}

			if ( $test_amount ) {
				$total_amount = Framework\SV_WC_Helper::number_format( $test_amount );
			}
		}

		$gateway = wc_moneris()->get_gateway( $this->id );

		$request_data = [
			'action'             => 'preload',
			'language'           => 'fr' === $this->get_language_code() ? 'fr' : 'en',
			'txn_total'          => $total_amount,
			'dynamic_descriptor' => $gateway->get_dynamic_descriptor() ?? '',
			'contact_details'    => [
				'first_name' => $customer_data['first_name'] ?? '',
				'last_name'  => $customer_data['last_name'] ?? '',
				'email'      => $customer_data['email'] ?? '',
				'phone'      => $customer_data['phone'] ?? '',
			],
		];

		// always include billing details as the check form preload will fail if Moneris Checkout has AVS required and is expecting this field
		$request_data['billing_details'] = [
			'address_1'   => $customer_data['address_1'] ?? '',
			'address_2'   => $customer_data['address_2'] ?? '',
			'city'        => $customer_data['city'] ?? '',
			'province'    => $customer_data['state'] ?? '',
			'country'     => $customer_data['country'] ?? '',
			'postal_code' => $customer_data['postcode'] ?? '',
		];

		/**
		 * Checkout API 'preload' Request Data
		 *
		 * Allow actors to modify the data for 'preload' requests before it's sent to Moneris. This data is used to load the embedded payment form.
		 *
		 * @since 3.4.0
		 *
		 * @param array $requestData preload request data to be filtered
		 * @param \WC_Moneris_Checkout_API_Request $this API request class instance
		 */
		$this->request_data = apply_filters( 'wc_payment_gateway_' . $this->id . '_preload_request_data', $request_data, $this );

		// add minimum total to load payment form for Add/Update Payment method
		if ( is_add_payment_method_page() || isset( $_GET['change_payment_method'] ) ) {
			$this->request_data['txn_total'] = Framework\SV_WC_Helper::number_format( 0.10 );
		}

		if ( $order_number ) {
			$this->request_data['order_no'] = $order_number;
		}

		// @TODO: handle it for different checks like 3Ds, AVS, etc.
	}


	/**
	 * Gets the current site's language code.
	 *
	 * @since 3.0.2
	 *
	 * @return string ISO language code
	 */
	protected function get_language_code() : string {

		return substr( get_locale() ?: 'en_US', 0, 2 );
	}


	/**
	 * Creates receipt request for the Moneris Checkout transaction.
	 *
	 * @since 3.0.0
	 *
	 * @param string $ticket ticket number for receipt
	 * @param \WC_Order $order the order object
	 * @param bool $should_tokenize
	 */
	public function create_receipt_request( string $ticket, WC_Order $order, bool $should_tokenize = false ) {

		$this->order        = $order;
		$this->path         = '/chkt/request/request.php';
		$this->request_data = [
			'action' => 'receipt',
			'ticket' => $ticket,
		];
	}


	/**
	 * Returns the order associated with this request, if there was one.
	 *
	 * @since 3.0.0
	 *
	 * @return WC_Order the order object
	 */
	public function get_order() {

		return $this->order;
	}


}
