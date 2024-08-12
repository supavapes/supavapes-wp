<?php
/**
 * Custom email templates manager class.
 *
 * 	@link       https://www.concatstring.com/
 * @since      1.0.0
 *
 * @package    Easy_Reservations
 * @subpackage Easy_Reservations/includes/classes/emails
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Custom email templates manager class.
 *
 * Defines the custom email templates and notifications.
 *
 * @package    Easy_Reservations
 * @subpackage Easy_Reservations/includes/classes/emails
 * @author     concatstring <info@concatstring.com>
 */
class WC_Custom_Emails_Manager {
	
	/**
	 * Constructor to help define actions.
	 */
	public function __construct() {
		add_filter( 'woocommerce_email_classes', array( &$this, 'ersrv_woocommerce_email_classes_callback' ) );
		add_filter( 'woocommerce_email_actions', array( &$this, 'supa_woocommerce_email_actions' ) );
	}

	/**
	 * Add custom class to send reservation emails.
	 *
	 * @param array $email_classes Email classes array.
	 * @return array
	 * @since 1.0.0
	 */
	public function ersrv_woocommerce_email_classes_callback( $email_classes ) {
		$email_classes['WC_Send_Custom_Email']                               = include __DIR__ . '/emails/class-wc-send-custom-email.php';
		$email_classes['WC_Email_Out_Of_Delivery_Order']                     = include __DIR__ . '/emails/class-wc-email-out-of-delivery-order.php';
		$email_classes['WC_Email_Delivered_Order']                           = include __DIR__ . '/emails/class-wc-email-delivered-order.php';
		$email_classes['WC_Email_Pickup_Ready_Order']                        = include __DIR__ . '/emails/class-wc-email-pickup-ready-order.php';
		$email_classes['WC_Email_Shipment_Ready_Order']                      = include __DIR__ . '/emails/class-wc-email-shipment-ready-order.php';
		$email_classes['WC_Email_Payment_Fail_Multiple_Attempt_Order']       = include __DIR__ . '/emails/class-wc-email-payment-fail-multiple-attempt-customer.php';
		$email_classes['WC_Email_Payment_Fail_Multiple_Attempt_Admin_Order'] = include __DIR__ . '/emails/class-wc-email-payment-fail-multiple-attempt-admin.php';
		$email_classes['WC_Email_Subscribe_User'] 							 = include __DIR__ . '/emails/class-wc-email-subscribe-user.php';
		$email_classes['WC_Email_Subscribe_Admin'] 							 = include __DIR__ . '/emails/class-wc-email-subscribe-admin.php';
		$email_classes['WC_Email_Fun_Questionnaire_Order']                   = include __DIR__ . '/emails/class-wc-email-fun-questionnaire-order.php';

		// Support request emails.
		$email_classes['WC_Send_Support_Request']  = include __DIR__ . '/emails/class-wc-email-support-request.php';
		$email_classes['WC_Send_Approve_Request']  = include __DIR__ . '/emails/class-wc-email-approve-request.php';
		$email_classes['WC_Send_Decline_Request']  = include __DIR__ . '/emails/class-wc-email-decline-request.php';
		$email_classes['WC_Send_FollowUp_Request'] = include __DIR__ . '/emails/class-wc-email-follow-up-request.php';

		// Feedback email
		$email_classes['WC_Send_Feedback'] = include __DIR__ . '/emails/class-wc-feedback-email.php';

		return $email_classes;
	}

	/**
	 * Register woo actions.
	 *
	 * @param array $actions Register actions.
	 *
	 * @return mixed
	 */
	public function supa_woocommerce_email_actions( $actions ) {
		$actions[] = 'woocommerce_order_status_shipment-ready_to_out-for-delivery';
		$actions[] = 'woocommerce_order_status_processing_to_pickup-ready';
		$actions[] = 'woocommerce_order_status_processing_to_shipment-ready';
		$actions[] = 'woocommerce_order_status_delivered';
		$actions[] = 'send_feedback_email';

		return $actions;
	}

}

new WC_Custom_Emails_Manager();