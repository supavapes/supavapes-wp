<?php
/**
 * Custom sms templates manager class.
 *
 * @since      1.0.0
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Custom sms templates manager class.
 *
 * Defines the custom email templates and notifications.
 *
 */
class WC_Custom_SMS_Manager {

	/**
	 * Constructor to help define actions.
	 */
	public function __construct() {
		add_filter( 'init', array( &$this, 'init_sms_classes' ) );
		add_action( 'woocommerce_after_resend_order_email', array( $this, 'supa_resend_order_email' ), 10, 2 );
	}

	public function init_sms_classes() {
		// Include email classes.
		include_once __DIR__ . '/sms/class-twilio-sms.php';

		// Triggers for this email.
		$sms                                      = array();
		$sms['Twilio_Order_Processing_SMS']       = include __DIR__ . '/sms/class-twilio-order-processing-sms.php';
		$sms['Twilio_Order_Pending_Payment_SMS']  = include __DIR__ . '/sms/class-twilio-order-pending-payment-sms.php';
		$sms['Twilio_Order_On_Hold_SMS']          = include __DIR__ . '/sms/class-twilio-order-on-hold-sms.php';
		$sms['Twilio_Order_Completed_SMS']        = include __DIR__ . '/sms/class-twilio-order-completed-sms.php';
		$sms['Twilio_Order_Refunded_SMS']         = include __DIR__ . '/sms/class-twilio-order-refunded-sms.php';
		$sms['Twilio_Order_Cancelled_SMS']        = include __DIR__ . '/sms/class-twilio-order-cancelled-sms.php';
		$sms['Twilio_Order_Failed_SMS']           = include __DIR__ . '/sms/class-twilio-order-failed-sms.php';
		$sms['Twilio_Order_Out_For_Delivery_SMS'] = include __DIR__ . '/sms/class-twilio-order-out-for-delivery-sms.php';
		$sms['Twilio_Order_Pickup_Ready_SMS']     = include __DIR__ . '/sms/class-twilio-order-pickup-ready-sms.php';
		$sms['Twilio_Order_Shipment_Ready_SMS']   = include __DIR__ . '/sms/class-twilio-order-shipment-ready-sms.php';
		$sms['Twilio_Order_Delivered_SMS']        = include __DIR__ . '/sms/class-twilio-order-delivered-sms.php';

		return $sms;
	}

	/**
	 * Re-send payment order sms.
	 *
	 * @param WC_Order $order order object.
	 * @param string $type order status.
	 */
	public function supa_resend_order_email( $order, $type ) {
		if ( 'customer_invoice' === $type ) {
			$all_sms = get_sms();

			$pending_payment = $all_sms['Twilio_Order_Pending_Payment_SMS'];

			if ( ! is_object( $order ) ) {
				$order = wc_get_order( absint( $order ) );
			}

			$pending_payment->trigger( $order->get_id(), $order );
		}
	}
}

new WC_Custom_SMS_Manager();
