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
 * needs please refer to http://docs.woocommerce.com/document/moneris-payment-gateway/
 *
 * @author      SkyVerge
 * @copyright   Copyright (c) 2013-2024, SkyVerge, Inc. (info@skyverge.com)
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */
defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_12_1 as Framework;

/**
 * Moneris subscription integration class.
 *
 * @since 3.0.0
 */
class WC_Moneris_Payment_Gateway_Integration_Subscription extends Framework\SV_WC_Payment_Gateway_Integration_Subscriptions {


	/**
	 * Bootstraps the class.
	 *
	 * @since 3.4.0
	 *
	 * @param Framework\SV_WC_Payment_Gateway $gateway
	 */
	public function __construct( Framework\SV_WC_Payment_Gateway $gateway ) {

		parent::__construct( $gateway );

		add_filter( "wc_payment_gateway_{$gateway->get_id()}_can_tokenize_with_or_after_sale", [ $this, 'maybe_can_tokenize_with_or_after_sale' ], 10, 2 );
		add_filter( "wc_payment_gateway_{$gateway->get_id()}_should_skip_transaction", [ $this, 'maybe_should_not_skip_transaction' ], 10, 2 );
		add_filter( "wc_payment_gateway_{$gateway->get_id()}_preload_request_data", [ $this, 'maybe_add_trial_subscription_renewal_total' ] );
	}


	/**
	 * Processes a Change Payment transaction.
	 *
	 * This hooks in before standard payment processing to simply add or create token data and avoid certain failure conditions affecting the subscription object.
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 *
	 * @param bool|array $result result from any others filtering this
	 * @param int $order_id an order or subscription ID
	 * @param \WC_Gateway_Moneris_Checkout_Credit_Card $gateway gateway object
	 * @return array<mixed>|void $result change payment result
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function process_change_payment( $result, $order_id, $gateway ) {

		// if this is not a subscription and not changing payment, bail for normal order processing
		if ( ! is_callable( [ $gateway, 'has_ticket_number' ] ) || ! wcs_is_subscription( $order_id ) || ! did_action( 'woocommerce_subscription_change_payment_method_via_pay_shortcode' ) ) {
			return $result;
		}

		if ( $gateway->has_ticket_number() ) {

			$subscription = $gateway->get_order( $order_id );
			$response     = $gateway->get_checkout_api()->create_receipt_request( $gateway->get_ticket_number(), $gateway->get_order( $order_id ) );

			if ( $response->get_payment_token_id() ) {

				$subscription->payment->token = $response->get_payment_token_id();
				$subscription                 = $gateway->addMonerisCheckoutPaymentDataToOrder( $subscription, $response );

				$gateway->get_payment_tokens_handler()->create_token( $subscription, $response );
				$gateway->add_transaction_data( $subscription );

				return [
					'result'   => 'success',
					'redirect' => $subscription->get_view_order_url(),
				];
			}
		}

		return parent::process_change_payment( $result, $order_id, $gateway );
	}


	/**
	 * Overrides whether Moneris can tokenize the payment method after the sale.
	 *
	 * Ensures $0 transactions for subscriptions (free trials) can be tokenized.
	 *
	 * @see maybe_add_trial_subscription_renewal_total()
	 *
	 * @internal
	 * @since 3.4.0
	 *
	 * @param bool $result
	 * @param WC_Order $order the order being paid for
	 * @return bool
	 */
	public function maybe_can_tokenize_with_or_after_sale( bool $result, WC_Order $order ): bool {

		return wcs_order_contains_subscription( $order ) ? true : $result;
	}


	/**
	 * Overrides whether the transaction should be skipped when processing payment for an order.
	 *
	 * Ensures $0 transactions for subscriptions (free trials) can be tokenized, by essentially forcing a $0 transaction
	 * with tokenization.
	 *
	 * @see maybe_add_trial_subscription_renewal_total()
	 *
	 * @internal
	 * @since 3.4.0
	 *
	 * @param bool $result
	 * @param WC_Order $order the order being paid for
	 * @return bool
	 */
	public function maybe_should_not_skip_transaction( bool $result, WC_Order $order ): bool {

		/** @var WC_Gateway_Moneris_Checkout_Credit_Card $gateway */
		$gateway = $this->get_gateway();

		// if the order contains a subscription and the card is not already saved, don't skip the transaction
		return wcs_order_contains_subscription( $order ) && ! $gateway->is_card_already_saved() ? false : $result;
	}


	/**
	 * Use the subscription renewal cost for initial pre-auth if the cart only contains free trial subscription(s).
	 * Necessary because Moneris doesn't allow $0 pre-auths.
	 *
	 * @see maybe_can_tokenize_with_or_after_sale()
	 * @see maybe_should_not_skip_transaction()
	 *
	 * @internal
	 * @since 3.4.0
	 *
	 * @param array $requestData preload request data to be filtered
	 * @return array the filtered request data
	 */
	public function maybe_add_trial_subscription_renewal_total( array $requestData ): array {
		global $wp;

		// bail early if this is not a $0 transaction - note that anything that resolves to `empty` is considered $0
		if ( ! empty( (float) $requestData['txn_total'] ) ) {
			return $requestData;
		}

		if ( is_checkout_pay_page() ) {

			// we know we have an order object so get the renewal total from there
			if ( ( $order = wc_get_order( absint( $wp->query_vars['order-pay'] ) ) ) && wcs_order_contains_subscription( $order ) ) {
				$requestData['txn_total'] = Framework\SV_WC_Helper::number_format(
					\WC_Subscriptions_Order::get_recurring_total( $order )
				);
			}

		// otherwise try to get it from the cart
		} else if ( ! empty( WC()->cart->recurring_carts ) ) {

			$requestData['txn_total'] = Framework\SV_WC_Helper::number_format( array_reduce(
				WC()->cart->recurring_carts,
				static fn($carry, $recurring_cart) => $carry + $recurring_cart->total,
				0
			) );

		// finally, if cart totals haven't been updated yet (e.g. during block checkout) we have to check cart items directly
		} else if ( \WC_Subscriptions_Cart::cart_contains_subscription() ) {

			$field = '_subscription_price';
			$requestData['txn_total'] = Framework\SV_WC_Helper::number_format( array_reduce(
				WC()->cart->get_cart(),
				static fn( $carry, $cart_item ) => isset( $cart_item[ $field ] ) ?
					$carry += $cart_item[ $field ]
					: $carry += \WC_Subscriptions_Product::get_meta_data( $cart_item['data'], $field, 0 ),
				0));
		}

		return $requestData;
	}


}
