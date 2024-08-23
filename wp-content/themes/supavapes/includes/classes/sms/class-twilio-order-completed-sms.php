<?php

/**
 * Class Twilio_Order_Completed_SMS
 */
class Twilio_Order_Completed_SMS extends Twilio_SMS {


	/**
	 * Template name
	 *
	 * @var string
	 */
	private $template_name = 'order_completed';

	/**
	 * Twilio_Order_Completed_SMS constructor.
	 */
	public function __construct() {
		$this->init();

		// Triggers for this email.
		add_action( 'woocommerce_order_status_completed', array( $this, 'trigger' ), 10, 2 );
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
			$this->placeholders['{{order_total}}']   = $this->object->get_total();
		}

		if ( $this->is_enabled() && $this->get_recipient_number() ) {
			$body_template = $this->get_templates( $this->template_name );
			$this->set_body( $this->get_recipient_number(), $body_template );
			$this->send_sms();
		}
	}
}

new Twilio_Order_Completed_SMS();
