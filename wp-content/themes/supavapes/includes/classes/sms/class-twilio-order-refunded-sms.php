<?php

/**
 * Class Twilio_Order_Refunded_SMS
 */
class Twilio_Order_Refunded_SMS extends Twilio_SMS {


	/**
	 * Refund order.
	 *
	 * @var WC_Order|bool
	 */
	public $refund;

	/**
	 * Is the order partial refunded?
	 *
	 * @var bool
	 */
	public $partial_refund;

	/**
	 * Template name
	 *
	 * @var string
	 */
	private $template_name = 'order_refunded';

	/**
	 * Twilio_Order_Refunded_SMS constructor.
	 */
	public function __construct() {
		$this->init();

		// Triggers for this email.
		add_action( 'woocommerce_order_fully_refunded', array( $this, 'trigger_full' ), 10, 2 );
		add_action( 'woocommerce_order_partially_refunded', array( $this, 'trigger_partial' ), 10, 2 );
	}

	/**
	 * Full refund notification.
	 *
	 * @param int $order_id  Order ID.
	 * @param int $refund_id Refund ID.
	 */
	public function trigger_full( $order_id, $refund_id = null ) {
		$this->trigger( $order_id, false, $refund_id );
	}

	/**
	 * Partial refund notification.
	 *
	 * @param int $order_id  Order ID.
	 * @param int $refund_id Refund ID.
	 */
	public function trigger_partial( $order_id, $refund_id = null ) {
		$this->trigger( $order_id, true, $refund_id );
	}

	/**
	 * Trigger the sending of this sms.
	 *
	 * @param int            $order_id The order ID.
	 * @param WC_Order|false $order    Order object.
	 */
	public function trigger( $order_id, $partial_refund = false, $refund_id = null ) {

		$this->partial_refund = $partial_refund;
		$id                   = $this->partial_refund ? 'customer_partially_refunded_order' : 'customer_refunded_order';

		if ( $order_id ) {
			$this->object                            = wc_get_order( $order_id );
			$this->recipient_number                  = $this->object->get_billing_phone();
			$this->placeholders['{{order_id}}']      = $this->object->get_id();
			$this->placeholders['{{customer_name}}'] = $this->object->get_billing_first_name();
			$this->placeholders['{{order_total}}']   = $this->object->get_total();
		}

		if ( $this->is_enabled() && $this->get_recipient_number() ) {
			$body_template = $this->get_templates( $this->template_name );
			$this->set_body( $this->get_recipient_number(), $body_template );
			$this->send_sms();
		}

		if ( ! empty( $refund_id ) ) {
			$this->refund = wc_get_order( $refund_id );
		} else {
			$this->refund = false;
		}
	}
}

new Twilio_Order_Refunded_SMS();
