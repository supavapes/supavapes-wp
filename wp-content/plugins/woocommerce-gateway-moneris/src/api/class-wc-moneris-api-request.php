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
 * Moneris API Request Class.
 *
 * Generates XML required by API specs to perform an API request
 *
 * Data Validation: Moneris defines maximum lengths and allowed characters for
 * all request fields, however these rules do not seem to be enforced, so we're
 * not bothering with them.
 *
 * @since 2.0
 */
class WC_Moneris_API_Request extends Framework\SV_WC_API_XML_Request implements Framework\SV_WC_Payment_Gateway_API_Request {


	/** @var string identifier */
	private $id;

	/** @var string Moneris store id */
	private $store_id;

	/** @var string Moneris API token for store id */
	private $api_token;

	/** @var string integration country, one of 'ca' or 'us' */
	private $integration;

	/** @var WC_Order optional order object if this request was associated with an order */
	protected $order;

	/** @var string the type of this request, one of 'preauth', 'purchase', or 'completion' */
	protected $request_type;

	/** e-commerce indicator */
	const CRYPT_TYPE_SSL_ENABLED_MERCHANT = 7;


	/**
	 * Construct a Moneris request object.
	 *
	 * @since 2.0
	 *
	 * @param string $id identifier
	 * @param string $store_id Moneris store id
	 * @param string $api_token Moneris API token for store id
	 * @param string $integration integration country, one of 'ca' or 'us' (defaults to 'ca')
	 */
	public function __construct( $id, $store_id, $api_token, $integration = 'ca' ) {

		$this->store_id    = $store_id;
		$this->api_token   = $api_token;
		$this->integration = $integration;
		$this->id          = $id;
	}


	/**
	 * Returns the transaction type prefix.
	 *
	 * @since 2.0
	 *
	 * @return string transaction type prefix based on current integration country
	 */
	protected function get_type_prefix() {
		return $this->integration === 'us' ? 'us_' : '';
	}


	/**
	 * Creates a credit card auth request for the payment method/customer associated with $order.
	 *
	 * @since 2.0
	 *
	 * @param WC_Order $order the order object
	 */
	public function credit_card_auth( $order ) {

		if ( isset( $order->payment->token ) && $order->payment->token ) {
			$this->request_type = 'res_preauth_cc';
		} else {
			$this->request_type = 'preauth';
		}

		$this->credit_card_charge_auth_request( $this->get_type_prefix().$this->request_type, $order );
	}


	/**
	 * Creates a credit card charge request for the payment method/
	 * customer associated with $order.
	 *
	 * @since 2.0
	 *
	 * @param WC_Order $order the order object
	 */
	public function credit_card_charge( $order ) {

		if ( isset( $order->payment->token ) && $order->payment->token ) {
			$this->request_type = 'res_purchase_cc';
		} else {
			$this->request_type = 'purchase';
		}

		$this->credit_card_charge_auth_request( $this->get_type_prefix().$this->request_type, $order );
	}


	/**
	 * Creates a credit card capture request for the payment method/customer associated with $order.
	 *
	 * @since 2.0
	 *
	 * @param WC_Order $order the order object
	 */
	public function credit_card_capture( $order ) {

		// store the order object for later use
		$this->order        = $order;
		$this->request_type = 'completion';

		// the order of these elements CANNOT be changed.  THIS MEANS YOU, FUTURE JUSTIN
		$this->request_data = [
			'order_id'    => $order->capture->receipt_id,
			'comp_amount' => $order->capture->amount,
			'txn_number'  => $order->capture->trans_id,
			'crypt_type'  => self::CRYPT_TYPE_SSL_ENABLED_MERCHANT,
		];
	}


	/**
	 * Creates a refund request for the payment associated with $order.
	 *
	 * @since 2.8.0
	 *
	 * @param WC_Order $order the order object
	 */
	public function refund( $order ) {

		// store the order object for later use
		$this->order        = $order;
		$this->request_type = 'refund';

		// the order of these elements CANNOT be changed.  THIS MEANS YOU, FUTURE JUSTIN
		$this->request_data = [
			'order_id'   => $order->refund->receipt_id,
			'amount'     => $order->refund->amount,
			'txn_number' => $order->refund->trans_id,
			'crypt_type' => self::CRYPT_TYPE_SSL_ENABLED_MERCHANT,
		];
	}


	/**
	 * Creates a refund request for the payment associated with $order.
	 *
	 * TODO: remove after 2020-08 or v3.0.0 {CW 2019-08-21}
	 *
	 * @since 2.8.0
	 * @deprecated 2.11.0
	 *
	 * @param WC_Order $order the order object
	 */
	public function void( $order ) {
		wc_deprecated_function( __METHOD__, '2.11.0' );
	}


	/**
	 * Tokenize the payment method associated with $order.  This can be used to
	 * tokenize a brand new credit card, if $order has members
	 * `payment->account_number` and `payment->exp_year`, or it can be used to
	 * tokenize the payment method used with a previous transaction if $order
	 * has the members `cc_moneris_receipt_id` and `wc_moneris_trans_id`.
	 *
	 * @since 2.0
	 *
	 * @param WC_Order $order the order object
	 */
	public function tokenize_payment_method( $order ) {

		// store the order object for later use
		$this->order = $order;

		if ( isset( $order->payment->trans_id ) && $order->payment->trans_id && isset( $order->payment->receipt_id ) && $order->payment->receipt_id ) {

			// Tokenize a previous transaction
			// res_tokenize_cc|us_res_tokenize_cc
			$this->request_type = 'res_tokenize_cc';

			$this->request_data = [
				'order_id'   => $order->payment->receipt_id,
				'txn_number' => $order->payment->trans_id,
				'cust_id'    => $order->customer_id,
				'phone'      => $order->get_billing_phone( 'edit' ),
				'email'      => $order->get_billing_email( 'edit' ),
				'note'       => Framework\SV_WC_Helper::str_truncate( Framework\SV_WC_Helper::str_to_sane_utf8( $order->get_customer_note( 'edit' ) ), 30 ),
			];
		} else {

			// Add a new credit card or make temporary token permanent
			if ( isset( $order->payment->account_number ) && $order->payment->account_number ) {
				// res_add_cc|us_res_add_cc
				$this->request_type = 'res_add_cc';
			} elseif ( isset( $order->payment->token ) && $order->payment->token ) {
				// res_add_token|us_res_add_token
				$this->request_type = 'res_add_token';
			}

			$this->request_data = [
				'cust_id' => $order->customer_id,
				'phone'   => $order->get_billing_phone( 'edit' ),
				'email'   => $order->get_billing_email( 'edit' ),
				'note'    => Framework\SV_WC_Helper::str_truncate( Framework\SV_WC_Helper::str_to_sane_utf8( $order->get_customer_note( 'edit' ) ), 30 ),
			];

			if ( isset( $order->payment->account_number ) && $order->payment->account_number ) {
				$this->request_data['pan'] = $order->payment->account_number;
			} elseif ( isset( $order->payment->token ) && $order->payment->token ) {
				$this->request_data['data_key'] = $order->payment->token;
			}

			$this->request_data['expdate']    = substr( $order->payment->exp_year, -2 ).$order->payment->exp_month; // YYMM
			$this->request_data['crypt_type'] = self::CRYPT_TYPE_SSL_ENABLED_MERCHANT;
		}

		// include avs fields?
		if ( $order->perform_avs ) {
			$this->add_avs_elements( $order );
		}
	}


	/**
	 * Request to delete a payment token.
	 *
	 * @since 2.0
	 *
	 * @param string $token the token to delete
	 * @param string $customer_id the associated customer id
	 */
	public function delete_tokenized_payment_method( $token, $customer_id ) {

		// Delete an existing tokenized credit card
		// res_delete|us_res_delete
		$this->request_type = 'res_delete';

		$this->request_data = [
			'data_key' => $token,
		];
	}


	/** Interac Methods ******************************************************/


	/**
	 * Confirms an Interac idebit purchase for the given order.
	 *
	 * $order is expected to have a `payment->track2` member, which is used to confirm the payment
	 *
	 * @since 2.0
	 *
	 * @param WC_Order $order the order
	 */
	public function idebit_purchase( $order ) {

		$this->order = $order;

		// Delete an existing tokenized credit card
		// idebit_purchase
		$this->request_type = 'idebit_purchase';

		$this->request_data = [
			'order_id'      => preg_replace( '/[^a-zA-Z0-9-]/', '', $order->unique_transaction_ref ),
			'cust_id'       => $order->customer_id,
			'amount'        => number_format( $order->payment_total, 2, '.', '' ),
			'idebit_track2' => $order->payment->track2,
		];
	}


	/** Helper Methods ******************************************************/


	/**
	 * Returns the string representation of this request with any and all
	 * sensitive elements masked or removed.
	 *
	 * @since 2.0
	 *
	 * @see Framework\SV_WC_Payment_Gateway_API_Request::to_string_safe()
	 *
	 * @return string the request XML, safe for logging/displaying
	 */
	public function to_string_safe() {

		$request = $this->to_string();

		// replace merchant authentication
		if ( preg_match( '/<api_token>(.*)<\/api_token>/', $request, $matches ) ) {
			$request = preg_replace( '/<api_token>.*<\/api_token>/', '<api_token>' . str_repeat( '*', strlen( $matches[1] ) ) . '</api_token>', $request );
		}

		// replace real card number
		if ( preg_match( '/<pan>(.*)<\/pan>/', $request, $matches )) {
			$request = preg_replace( '/<pan>.*<\/pan>/', '<pan>' . substr( $matches[1], 0, 1 ) . str_repeat( '*', strlen( $matches[1] ) - 5 ) . substr( $matches[1], -4 ) . '</pan>', $request );
		}

		// replace real CSC code
		if ( preg_match( '/<cvd_value>(.*)<\/cvd_value>/', $request, $matches ) ) {
			$request = preg_replace( '/<cvd_value>.*<\/cvd_value>/', '<cvd_value>' . str_repeat( '*', strlen( $matches[1] ) ) . '</cvd_value>', $request );
		}

		// replace interac account number
		if ( preg_match( '/<idebit_track2>(.*)<\/idebit_track2>/', $request, $matches ) ) {

			[ $pan, $suffix ] = explode( '=', $matches[1] );
			$pan              = substr( $pan, 0, 1 ) . str_repeat( '*', strlen( $pan ) - 5 ) . substr( $pan, -4 );
			$request          = preg_replace( '/<idebit_track2>.*<\/idebit_track2>/', '<idebit_track2>' . $pan . '=' . $suffix . '</idebit_track2>', $request );
		}

		return $this->prettify_xml( $request );
	}


	/**
	 * Gets the authentication information to add to the request.
	 *
	 * @since 2.11.0
	 *
	 * @param string $store_id Moneris store id
	 * @param string $api_token Moneris API token for store id
	 */
	private function get_auth_elements( $store_id, $api_token ) {

		/*
		 * Filter the authentication info used for API requests.
		 *
		 * This can be used to vary the authentication on a per-order basis, for hings like multi-currency support :)
		 *
		 * Warning: this may be removed in a future release and replace with a filter at the request data level (e.g. a filter around an array of request data)
		 *
		 * @since 2.2.2
		 *
		 * @param array $auth {
		 *   @type string $store_id store ID
		 *   @type string $api_token API token
		 * }
		 * @param \WC_Moneris_API_Request $this Moneris API request class instance
		 */
		return apply_filters( 'wc_moneris_api_request_auth_info', ['store_id' => $store_id, 'api_token' => $api_token], $this->get_order(), $this );
	}


	/**
	 * Adds the avs elements to the request.
	 *
	 * @since 2.0
	 *
	 * @param WC_Order $order the order
	 */
	private function add_avs_elements( $order ) {

		// avs_info
		$this->request_data['avs_info'] = [
			'avs_street_name' => $order->get_billing_address_1( 'edit' ),
			// Added this filter because http://woothemes.zendesk.com/agent/tickets/230635 claimed that zip+4 failed
			'avs_zipcode' => apply_filters( 'wc_gateway_moneris_request_avs_zipcode', str_replace( '-', '', $order->get_billing_postcode( 'edit' ) ), $order ),
		];

		if ( 'purchase' === $this->request_type || 'preauth' === $this->request_type ) {
			$this->request_data['avs_info']['avs_shiptocountry'] = $order->get_shipping_country( 'edit' );
			$this->request_data['avs_info']['avs_custphone']     = $order->get_billing_phone( 'edit' );
			$this->request_data['avs_info']['avs_custip']        = $order->get_customer_ip_address( 'edit' );
			$this->request_data['avs_info']['avs_browser']       = $order->get_customer_user_agent( 'edit' );
		}
	}


	/**
	 * Adds the csc elements to the request.
	 *
	 * @since 2.0
	 *
	 * @param WC_Order $order the order
	 */
	private function add_csc_elements( $order ) {

		// cvd_info
		$this->request_data['cvd_info'] = [
			'cvd_indicator' => 1,
			'cvd_value'     => $order->payment->csc,
		];
	}


	/**
	 * Gets the billing elements to add to the request.
	 *
	 * @since 2.11.0
	 *
	 * @param WC_Order $order the order
	 * @return array
	 */
	private function get_billing_elements( $order ) {

		$address = $this->get_order_address( $order, 'billing' );

		/**
		 * Filters billing data before adding to the request.
		 *
		 * @since 2.8.2
		 *
		 * @param string[] $billing_data the billing data to send
		 * @param \WC_Order $order order object
		 */
		$billing_data = apply_filters( 'wc_moneris_credit_card_request_billing_data', [
			'first_name'    => $address['first_name'],
			'last_name'     => $address['last_name'],
			'company'       => $address['company'],
			'address'       => trim( $address['address_1'].' '.$address['address_2'] ),
			'city'          => $address['city'],
			'state'         => $address['state'],
			'postcode'      => $address['postcode'],
			'country'       => $address['country'],
			'phone_number'  => $order->get_billing_phone( 'edit' ),
			'fax'           => '',
			'tax1'          => '',
			'tax2'          => '',
			'tax3'          => '',
			'shipping_cost' => number_format( $order->get_shipping_total(), 2, '.', '' ),
		], $order );

		// billing
		return [
			'first_name'    => $billing_data['first_name'],
			'last_name'     => $billing_data['last_name'],
			'company_name'  => $billing_data['company'],
			'address'       => $billing_data['address'],
			'city'          => $billing_data['city'],
			'province'      => $billing_data['state'],
			'postal_code'   => $billing_data['postcode'],
			'country'       => $billing_data['country'],
			'phone_number'  => $billing_data['phone_number'],
			'fax'           => $billing_data['fax'],
			'tax1'          => $billing_data['tax1'],
			'tax2'          => $billing_data['tax2'],
			'tax3'          => $billing_data['tax3'],
			'shipping_cost' => $billing_data['shipping_cost'],
		];
	}


	/**
	 * Gets the shipping elements to add to the request.
	 *
	 * @since 2.11.0
	 *
	 * @param WC_Order $order the order
	 * @return array
	 */
	private function get_shipping_elements( $order ) {

		$address = $this->get_order_address( $order, 'shipping' );

		// shipping
		return [
			'first_name'    => $address['first_name'],
			'last_name'     => $address['last_name'],
			'company_name'  => $address['company'],
			'address'       => trim( $address['address_1'].' '.$address['address_2'] ),
			'city'          => $address['city'],
			'province'      => $address['state'],
			'postal_code'   => $address['postcode'],
			'country'       => $address['country'],
			'phone_number'  => '',
			'fax'           => '',
			'tax1'          => '',
			'tax2'          => '',
			'tax3'          => '',
			'shipping_cost' => number_format( $order->get_shipping_total(), 2, '.', '' ),
		];
	}


	/**
	 * Gets an order's address of a certain type.
	 *
	 * If getting the shipping address on virtual orders, this falls back to the billing address since WC 3.0+ doesn't set shipping address data for virtual orders.
	 *
	 * @since 2.7.1
	 *
	 * @param \WC_Order $order order object
	 * @param string $type address type, either 'billing' or 'shipping'
	 *
	 * @return array $address order address
	 */
	private function get_order_address( $order, $type = 'billing' ) {

		if ( 'shipping' === $type && ! $order->get_shipping_country( 'edit' ) ) {
			$type = 'billing';
		}

		return [
			'first_name' => $this->get_order_prop( $order, "{$type}_first_name" ),
			'last_name'  => $this->get_order_prop( $order, "{$type}_last_name" ),
			'company'    => $this->get_order_prop( $order, "{$type}_company" ),
			'address_1'  => $this->get_order_prop( $order, "{$type}_address_1" ),
			'address_2'  => $this->get_order_prop( $order, "{$type}_address_2" ),
			'city'       => $this->get_order_prop( $order, "{$type}_city" ),
			'state'      => $this->get_order_prop( $order, "{$type}_state" ),
			'postcode'   => $this->get_order_prop( $order, "{$type}_postcode" ),
			'country'    => $this->get_order_prop( $order, "{$type}_country" ),
		];
	}


	/**
	 * Gets the unfiltered value of an order's property.
	 *
	 * @since 2.12.0
	 *
	 * @param \WC_Order $order the order object
	 * @param string $prop the name of the property
	 * @return mixed
	 */
	private function get_order_prop( WC_Order $order, $prop ) {

		return is_callable( [ $order, "get_{$prop}" ] ) ? $order->{"get_{$prop}"}( 'edit' ) : null;
	}


	/**
	 * Gets the item elements to add to the request.
	 *
	 * @since 2.11.0
	 *
	 * @param WC_Order $order the order
	 * @return array
	 */
	private function get_item_elements( $order ) {

		$items = [];

		foreach ( $order->get_items() as $item ) {

			$product = $item->get_product();

			// item
			$items[] = [
				// note: the documentation make it look like 'description' should be used for the US integration rather than 'name', but this does not seem to be accurate
				'name'         => Framework\SV_WC_Helper::str_to_sane_utf8( htmlentities( $item['name'] ) ),
				'quantity'     => $item['qty'],
				'product_code' => $product ? Framework\SV_WC_Helper::str_truncate( $product->get_sku(), 20 ) : '',
				// This must contain at least 3 digits with two penny values. The minimum value passed can be 0.01 and the maximum 9999999.99
				'extended_amount' => number_format( $item['line_total'], 2, '.', '' ),
			];
		}

		return $items;
	}


	/**
	 * Adds the customer info elements to the request.
	 *
	 * @since 2.0
	 *
	 * @param WC_Order $order the order
	 */
	private function add_cust_info_elements( $order ) {

		// cust_info
		$this->request_data['cust_info'] = [
			'email'        => $order->get_billing_email( 'edit' ),
			'instructions' => Framework\SV_WC_Helper::str_truncate( Framework\SV_WC_Helper::str_to_sane_utf8( $order->get_customer_note( 'edit' ) ), 100 ),
		];

		$this->request_data['cust_info']['billing'] = $this->get_billing_elements( $order );

		$this->request_data['cust_info']['shipping'] = $this->get_shipping_elements( $order );

		$this->request_data['cust_info']['item'] = $this->get_item_elements( $order );
	}


	/**
	 * Add the credit card charge or auth elements.
	 *
	 * @since 2.0
	 * @param string $request_type one of preauth, purchase, us_preauth, us_purchase
	 * @param WC_Order $order the order object
	 */
	private function credit_card_charge_auth_request( $request_type, $order ) {

		// store the order object for later use
		$this->order = $order;

		// preauth|purchase|us_preauth|us_purchase|res_preauth_cc|us_res_preauth_cc|res_purchase_cc|us_res_purchase_cc

		if ( isset( $order->payment->token ) && $order->payment->token ) {
			$this->request_data['data_key'] = $order->payment->token;
		}

		$this->request_data['order_id'] = preg_replace( '/[^a-zA-Z0-9-]/', '', $order->unique_transaction_ref );

		if ( $order->customer_id ) {
			// empty value results in "Cancelled: Transaction data cannot have empty elements"
			$this->request_data['cust_id'] = $order->customer_id;
		}
		$this->request_data['amount'] = number_format( $order->payment_total, 2, '.', '' );

		if ( ! isset( $order->payment->token ) || ! $order->payment->token ) {
			$this->request_data['pan'] = $order->payment->account_number;
		}

		if ( isset( $order->payment->exp_year ) && $order->payment->exp_year && isset( $order->payment->exp_month ) && $order->payment->exp_month ) {
			$this->request_data['expdate'] = substr( $order->payment->exp_year, -2 ).$order->payment->exp_month; // YYMM
		}

		$this->request_data['crypt_type']         = self::CRYPT_TYPE_SSL_ENABLED_MERCHANT;
		$this->request_data['dynamic_descriptor'] = $order->dynamic_descriptor;

		// include avs fields?
		if ($order->perform_avs ) {
			$this->add_avs_elements( $order );
		}

		// include csc fields?
		if ( isset( $order->payment->csc ) ) {
			$this->add_csc_elements( $order );
		}

		$this->add_cust_info_elements( $order );

		//add cof support
		$this->add_cof_elements( $order );
	}


	/**
	 * Returns the order associated with this request, if there was one.
	 *
	 * @since 2.0
	 *
	 * @return WC_Order the order object
	 */
	public function get_order() {
		return $this->order;
	}


	/**
	 * Gets the type of this request.
	 *
	 * @since 2.0
	 *
	 * @return string the type of this request, one of 'preauth', 'purchase', or 'completion'
	 */
	public function get_type() {
		return $this->request_type;
	}


	/**
	 * Gets the request data to be converted to XML.
	 *
	 * @since 2.11.0
	 *
	 * @return array
	 */
	public function get_data() {

		// required for every transaction
		$transaction_data = $this->get_auth_elements( $this->store_id, $this->api_token );

		// add required request data
		$this->request_data = [
			$this->get_root_element() => array_merge( $transaction_data, [
				$this->get_type_prefix().$this->request_type => $this->request_data,
			] ),
		];

		/*
		 * API Request Data
		 *
		 * Allow actors to modify the request data before it's sent to Moneris
		 *
		 * @since 2.11.0
		 *
		 * @param array $data request data to be filtered
		 * @param \WC_Order $order order instance
		 * @param \WC_Moneris_API_Request $this, API request class instance
		 */
		$this->request_data = apply_filters( 'wc_moneris_api_request_data', $this->request_data, $this->order, $this );

		return $this->request_data;
	}


	/**
	 * Get the root element for the XML document.
	 *
	 * @since 2.11.0
	 *
	 * @return string
	 */
	protected function get_root_element() {
		return 'request';
	}


	/**
	 * Add support for Credential on File.
	 *
	 * @see https://developer.moneris.com/Documentation/NA/E-Commerce%20Solutions/API/Purchase#purchasewithcredentialonfile
	 * @see http://url.com//https://developer.moneris.com/Documentation/NA/E-Commerce%20Solutions/API/Card%20Verification#:~:text=IssuerID-,Definitions%20of%20Request%20Fields%20%E2%80%93%20Credential%20on%20File,-VARIABLE%20NAME
	 *
	 * @since 3.0.0
	 *
	 * @param WC_Order $order the order object
	 */
	private function add_cof_elements( $order ) {

		$issuer_id  = '';
		$cof_fields = [];

		if ( $order->payment->token ) {
			$token      = $this->get_gateway()->get_payment_tokens_handler()->get_token( $order->get_user_id(), $order->payment->token );
			$token_data = $token->to_datastore_format();
			$issuer_id  = $token_data['issuer_id'] ?? '';
		}

		// first time saving card for non subscription item
		if ( $should_save = $this->get_gateway()->get_payment_tokens_handler()->should_tokenize() ) {
			$cof_fields = [
				'payment_indicator'   => 'C',
				'payment_information' => 0,
				'issuer_id'           => '',
			];
		}

		// already saved card for non subscription items
		if ( $issuer_id ) {
			$cof_fields = [
				'payment_indicator'   => 'Z',
				'payment_information' => 2,
				'issuer_id'           => $issuer_id,
			];
		}

		// first time saving card in vault for subscription item
		if ( $should_save && function_exists( 'wcs_order_contains_subscription' ) && wcs_order_contains_subscription( $order->get_id() ) && ! wcs_order_contains_renewal( $order->get_id() ) ) {

			$cof_fields = [
				'payment_indicator'   => 'R',
				'payment_information' => 0,
				'issuer_id'           => '',
			];

			$this->request_data['crypt_type'] = 6;
		}

		// first time payment with already saved card for  subscription item
		if ( $issuer_id && function_exists( 'wcs_order_contains_subscription' ) && wcs_order_contains_subscription( $order->get_id() ) && ! wcs_order_contains_renewal( $order->get_id() ) ) {

			$cof_fields = [
				'payment_indicator'   => 'R',
				'payment_information' => 2,
				'issuer_id'           => $issuer_id,
			];

			$this->request_data['crypt_type'] = 6;
		}

		// subscription renewal payment
		if ( function_exists( 'wcs_order_contains_renewal' ) && wcs_order_contains_renewal( $order->get_id() ) && $issuer_id ) {

			$cof_fields['payment_indicator']   = 'R';
			$cof_fields['payment_information'] = 2;
			$cof_fields['issuer_id']           = $issuer_id;

			$this->request_data['crypt_type'] = 6;
		}

		// make sure cof fields not empty
		if ( ! empty( $cof_fields ) ) {
			$this->request_data['cof_info'] = $cof_fields;
		}
	}


	/**
	 * Returns the gateway instance associated with this request.
	 *
	 * @since 3.0.0
	 *
	 * @return \WC_Gateway_Moneris_Checkout_Credit_Card|\WC_Gateway_Moneris_Credit_Card
	 */
	public function get_gateway() {

		return wc_moneris()->get_gateway( $this->id );
	}


}
