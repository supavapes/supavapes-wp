<?php
/**
 * Class WC_Email_Payment_Fail_Multiple_Attempt_Admin_Order file
 *
 * @package WooCommerce\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Email_Payment_Fail_Multiple_Attempt_Admin_Order' ) ) :

	/**
	 * New Order Email.
	 *
	 * An email sent to the admin when a Payment fail multiple attempt for admin.
	 *
	 * @class       WC_Email_Payment_Fail_Multiple_Attempt_Admin_Order
	 * @version     2.0.0
	 * @package     WooCommerce\Classes\Emails
	 * @extends     WC_Email
	 */
	class WC_Email_Payment_Fail_Multiple_Attempt_Admin_Order extends WC_Email {


		/**
		 * @var string $email_template_name
		 */
		public $email_template_name = 'multiple_payment_attempt_failure_admin_email';

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'admin_payment_fail_multiple_attempt_order';
			$this->title          = __( 'Payment Fail Multiple Attempt - Admin', 'woocommerce' );
			$this->description    = __( 'New order emails are sent to chosen recipient(s) when a new order is received.', 'woocommerce' );
			$this->template_html  = 'emails/customer-request-email.php';
			$this->template_plain = 'emails/plain/customer-request-email.php';
			$this->template_cart_detail = 'emails/cart-detail.php';
			$this->placeholders   = array(
				'{first_name}' => '',
				'{last_name}' => '',
				'{email}' => '',
				'{phone}' => '',
				'{comments}' => '',
				'{cart_content}' => ''
			);

			
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
			return __( '[{site_title}]: Payment Fail Attempt', 'woocommerce' );
		}

		/**
		 * Get email heading.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Payment Fail Attempt', 'woocommerce' );
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * @param int            $order_id The order ID.
		 * @param WC_Order|false $order Order object.
		 */
		public function trigger( $post_id ) {
			$this->setup_locale();


				// $this->object    = get_post($post_id);
				$this->recipient = get_option( 'admin_email' );
				$this->placeholders['{first_name}'] = get_post_meta($post_id, 'first_name',true);
				$this->placeholders['{last_name}'] = get_post_meta($post_id, 'last_name',true);
				$this->placeholders['{email}'] = get_post_meta($post_id, 'email',true);
				$this->placeholders['{phone}'] = get_post_meta($post_id, 'phone',true);
				$this->placeholders['{comments}'] = get_post_meta($post_id, 'comments',true);
				$this->placeholders['{cart_content}'] = $this->get_cart_details();
	

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
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin'      => true,
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
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin'      => true,
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
		 * Get Cart Details.
		 *
		 * @return string
		 */
		public function get_cart_details() {
			return wc_get_template_html(
				$this->template_cart_detail,
				array()
			);
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
		 * @since 3.7.0
		 * @return string
		 */
		public function get_default_additional_content() {
			return __( 'Congratulations on the sale.', 'woocommerce' );
		}

		/**
		 * Return content from the additional_content field.
		 *
		 * Displayed above the footer.
		 *
		 * @since 3.7.0
		 * @return string
		 */
		public function get_additional_content() {
			/**
			 * This filter is documented in ./class-wc-email.php
			 *
			 * @since 7.8.0
			 */
			return apply_filters( 'woocommerce_email_additional_content_' . $this->id, $this->format_string( $this->get_option( 'additional_content' ) ), $this->object, $this );
		}

	}

endif;

return new WC_Email_Payment_Fail_Multiple_Attempt_Admin_Order();
