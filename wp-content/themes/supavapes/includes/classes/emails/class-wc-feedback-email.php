<?php
/**
 * Class WC_Send_Feedback file.
 *
 * @package WooCommerce\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Send_Feedback', false ) ) :

	/**
	 * Support request Email.
	 *
	 * An email sent to the customer when a Feedback
	 *
	 * @class       WC_Send_Feedback
	 * @version     3.5.0
	 * @package     WooCommerce\Classes\Emails
	 * @extends     WC_Email
	 */
	class WC_Send_Feedback extends WC_Email {

		/**
		 * @var string $email_template_name
		 */
		public $email_template_name = 'feedback_email';

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'customer_feedback_email';
			$this->customer_email = true;

			$this->title          = __( 'Feedback Email', 'hello-elementor-child' );
			$this->description    = __( 'An email sent to the customer when they place a custom order.', 'hello-elementor-child' );
			$this->template_html  = 'emails/customer-request-email.php';
			$this->template_plain = 'emails/plain/customer-request-email.php';
			$this->placeholders   = array(
				'{order_date}'   => '',
				'{order_number}' => '',
			);

			// Triggers for this email.
			add_action( 'send_feedback_email_notification', array( $this, 'trigger' ), 10, 1 );

			// Call parent constructor.
			parent::__construct();
		}

		/**
		 * Get email subject.
		 *
		 * @return string
		 * @since  3.1.0
		 */
		public function get_default_subject() {
			return __( 'Feedback Email', 'hello-elementor-child' );
		}

		/**
		 * Get email heading.
		 *
		 * @return string
		 * @since  3.1.0
		 */
		public function get_default_heading() {
			return sprintf( _x( '[%s] Feedback ', 'default email subject for rental agreement being sent to the customer', 'hello-elementor-child' ), '{blogname}' );
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * @param int $order_id The order ID.
		 */
		public function trigger( $order_id ) {
			$this->setup_locale();

			if ( ! $order_id ) {
				return;
			}

			$order = wc_get_order( $order_id );

			if ( is_a( $order, 'WC_Order' ) ) {
				$this->object                          = $order;
				$this->recipient                       = $this->object->get_billing_email();
				$this->placeholders['{customer_name}'] = $this->object->get_billing_first_name();

				// Get product names
				$product_names = [];
				foreach ( $order->get_items() as $item ) {
					$product_names[] = $item->get_name();
				}
				$this->placeholders['{product_name}'] = implode( ', ', $product_names );

				$this->placeholders['{store_name}']   = get_bloginfo( 'name' );
				$this->placeholders['{review_link}']  = get_permalink( wc_get_page_id( 'myaccount' ) ) . '#reviews'; // Adjust the review link as needed
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
					'order'              => $this->object,
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
					'order'              => $this->object,
					'email_heading'      => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
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

return new WC_Send_Feedback();
