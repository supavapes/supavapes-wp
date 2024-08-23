<?php


abstract class Twilio_SMS {


	public $account_sid;
	public $auth_token;
	public $from;
	public $sms_templates;
	public $body_data;

	/**
	 * Recipients for the email.
	 *
	 * @var string
	 */
	public $recipient_number;

	/**
	 * Object this email is for, for example a customer, product, or email.
	 *
	 * @var object|bool
	 */
	public $object;

	/**
	 * Strings to find/replace in subjects/headings.
	 *
	 * @var array
	 */
	protected $placeholders = array();


	public function init() {
		$this->account_sid   = get_field( 'twilio_account_sid', 'option' );
		$this->auth_token    = get_field( 'twilio_auth_token', 'option' );
		$this->from          = get_field( 'sms_from_number', 'option' );
		$this->sms_templates = get_field( 'sms_templates', 'option' );
	}

	public function get_body() {
		return $this->body_data;
	}

	public function set_body( $to, $body ) {
		$this->body_data = array(
			'From' => $this->from,
			'Body' => $body,
			'To'   => $to,
		);
	}

	public function get_templates( $template_name ) {

		$body_template  = $this->sms_templates[ $template_name ];
		$search_fields  = array_keys( $this->placeholders );
		$replace_fields = array_values( $this->placeholders );
		// Replace placeholders with actual data
		$body_template = str_replace( $search_fields, $replace_fields, $body_template );

		return $body_template;
	}

	public function get_header() {
		return array(
			'Authorization' => 'Basic ' . base64_encode( "$this->account_sid:$this->auth_token" ),
		);
	}

	public function send_sms() {
		$args = array(
			'body'    => $this->get_body(),
			'headers' => $this->get_header(),
			'method'  => 'POST',
		);

		$url      = "https://api.twilio.com/2010-04-01/Accounts/$this->account_sid/Messages.json";
		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();

			$response = "Something went wrong: $error_message";

		} else {
			$response = json_decode( wp_remote_retrieve_body( $response ), true );
		}

		$this->object->add_order_note( $this->prepare_order_note( $response ) );
	}

	public function prepare_order_note( $response ) {
		if ( is_array( $response ) ) {
			// Format the SMS details into a single string
			$note  = "SMS Notification Sent:\n";
			$note .= '- SID: ' . $response['sid'] . "\n";
			$note .= '- To: ' . $response['to'] . "\n";
			$note .= '- From: ' . $response['from'] . "\n";
			$note .= '- Status: ' . $response['status'] . "\n";
			$note .= '- Date Created: ' . $response['date_created'] . "\n";
		} else {
			$note  = "SMS Notification Sent:\n";
			$note .= '- Error: ' . $response . "\n";
		}

		return $note;
	}

	public function is_enabled() {
		return true;
	}


	/**
	 * Get valid recipients.
	 *
	 * @return string
	 */
	public function get_recipient_number() {
		return $this->recipient_number;
	}
}
