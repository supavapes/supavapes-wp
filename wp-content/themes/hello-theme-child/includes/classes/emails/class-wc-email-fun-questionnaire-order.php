<?php
/**
 * Class WC_Email_Fun_Questionnaire_Order file.
 *
 * @package WooCommerce\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Email_Fun_Questionnaire_Order', false ) ) :

	/**
	 * Fun Questionnaire Order Email.
	 *
	 * An email sent to the customer when a order is fun questionnaire for.
	 *
	 * @class       WC_Email_Fun_Questionnaire_Order
	 * @version     3.5.0
	 * @package     WooCommerce\Classes\Emails
	 * @extends     WC_Email
	 */
	class WC_Email_Fun_Questionnaire_Order extends WC_Email {

		/**
		 * @var string $email_template_name
		 */
		public $email_template_name = 'fun_questionnaire_email';


		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'customer_fun_questionnaire_order';
			$this->customer_email = true;

			$this->title          = __( 'Fun Questionnaire', 'hello-elementor-child' );
			$this->description    = __( 'An email sent to the customer when they pass the quize.', 'hello-elementor-child' );
			$this->template_html  = 'emails/customer-fun-questionnaire-order.php';
			$this->template_plain = 'emails/plain/customer-fun-questionnaire-order.php';
			$this->placeholders   = array();

			// Call parent constructor.
			parent::__construct();
		}

		/**
		 * Get email subject.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_subject() {
			return __( 'Congratulations! Enjoy a 20% Discount on Your Next Purchase', 'hello-elementor-child' );
		}

		/**
		 * Get email heading.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_heading() {
			return sprintf( _x( '[%s] Quiz Result: Pass', 'default email subject for rental ggreement being sent to the customer', 'hello-elementor-child' ), '{blogname}' );
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * @param int            $order_id The order ID.
		 * @param WC_Order|false $order Order object.
		 */
		public function trigger( $quizemail, $extra_data = false ) {
			$this->setup_locale();

				$this->recipient        = $quizemail;

				if ( ! empty( $extra_data ) ) {
					foreach ( $extra_data as $key => $extra ) {
						$this->placeholders[ $key ] = $extra;
					}
				}
			

			if ( $this->is_enabled() && $this->get_recipient() ) {
				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			}

			$this->restore_locale();
		}

		/**
		 * Get content html.
		 *
		 * @return string
		 */
		public function get_content_html() {
			return wc_get_template_html(
				$this->template_html,
				array(
					'email_heading'      => $this->get_heading(),
					'sent_to_admin'      => false,
					'plain_text'         => false,
					'email'              => $this,
				)
			);
		}

		/**
		 * Get content plain.
		 *
		 * @return string
		 */
		public function get_content_plain() {
			return wc_get_template_html(
				$this->template_plain,
				array(
					'email_heading'      => $this->get_heading(),
					'sent_to_admin'      => false,
					'plain_text'         => true,
					'email'              => $this,
				)
			);
		}

		/**
		 * Get email subject.
		 *
		 * @return string
		 */
		public function get_subject() {
			return apply_filters( 'woocommerce_email_subject_' . $this->id, $this->format_string( $this->get_option( 'subject', $this->get_default_subject() ) ), $this->object, $this );
		}

		/**
		 * Get email heading.
		 *
		 * @return string
		 */
		public function get_heading() {
			return apply_filters( 'woocommerce_email_heading_' . $this->id, $this->format_string( $this->get_option( 'heading', $this->get_default_heading() ) ), $this->object, $this );
		}

		/**
		 * Get email custom content.
		 *
		 * @param $email_templates
		 *
		 * @return mixed|string|void
		 */
		public function get_custom_content( $email_templates ) {
			$email_content = '';
			if ( isset( $email_templates[ $this->email_template_name ] ) ) {
				$email_content = $email_templates[ $this->email_template_name ];
			}
			return apply_filters( 'woocommerce_email_custom_content_' . $this->id, $this->format_string( $email_content ), $this->object, $this );
		}

		/**
		 * Default content to show below main email content.
		 *
		 * @return string
		 * @since 3.7.0
		 */
		public function get_default_additional_content() {
			return __( 'Thanks for using {site_url}!', 'hello-elementor-child' );
		}
	}

endif;

return new WC_Email_Fun_Questionnaire_Order();
