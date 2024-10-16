<?php


class Twilio_Order_Pending_Payment_SMS extends Twilio_SMS {


	/**
	 * Template name
	 *
	 * @var string
	 */
	private $template_name = 'pending_payment';

	/**
	 * Twilio_Order_Pending_Payment_SMS constructor.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Trigger the sending of this sms.
	 *
	 * @param int            $order_id The order ID.
	 * @param WC_Order|false $order    Order object.
	 */
	public function trigger( $order_id, $order = false ) {
		if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
			$order = wc_get_order( $order_id );
		}

		if ( is_a( $order, 'WC_Order' ) ) {
			$this->object                            = $order;
			$this->recipient_number                  = $this->object->get_billing_phone();
			$this->placeholders['{{order_id}}']      = $this->object->get_id();
			$this->placeholders['{{customer_name}}'] = $this->object->get_billing_first_name();
			$this->placeholders['{{payment_link}}']  = $this->object->get_checkout_payment_url();
			$this->placeholders['{{order_total}}']   = $this->object->get_total();
		}

		if ( $this->is_enabled() && $this->get_recipient_number() ) {
			$body_template = $this->get_templates( $this->template_name );
			$this->set_body( $this->get_recipient_number(), $body_template );
			$this->send_sms();
		}
	}
}

new Twilio_Order_Pending_Payment_SMS();
