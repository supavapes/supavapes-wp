<?php
/**
 * Rental agreement email class.
 *
 *   @link       https://www.cmsminds.com/
 *   @since      1.0.0
 *
 * @package    Easy_Reservations
 * @subpackage Easy_Reservations/includes/classes/emails
 */
if ( ! defined( 'ABSPATH') ) exit; // Exit if accessed directly

/**
 * Rental agreement email class.
 *
 * @package    Easy_Reservations
 * @subpackage Easy_Reservations/includes/classes/emails
 * @author     cmsMinds <info@cmsminds.com>
 * @since      1.0.0
 * @extends \WC_Email
 */
class Out_For_Delivery_Email extends WC_Email {
	/**
	 * Set email defaults.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Email slug we can use to filter other data.
		$this->id          = 'custom_email';
		$this->title       = __( 'Out For Delivery', 'supavapes' );
		$this->description = __( 'An email sent to the customer when they place a custom order.', 'supavapes' );

		// For admin area to let the user know we are sending this email to the customer.
		$this->customer_email = true;
		$this->heading        = __( 'Out For Delivery', 'supavapes' );

		// translators: placeholder is {blogname}, a variable that will be substituted when email is sent out.
		$this->subject = sprintf( _x( '[%s] Out For Delivery', 'default email subject for rental ggreement being sent to the customer', 'supavapes' ), '{blogname}' );

		// Template paths.
		$this->template_html  = 'out-for-delivery-email-html.php';
		$this->template_plain = 'plain/out-for-delivery-email-plain.php';

		add_action( 'hello_elementor_out_for_delivery_email', array( $this, 'hello_elementor_hello_elementor_out_for_delivery_callback' ), 20, 2 );

		// Call parent constructor.
		parent::__construct();

		// Template base path.
		$this->template_base = HELLO_ELEMENTOR_EMAIL_TEMPLATE_PATH;

		// Recipient.
		$this->recipient = $this->get_option( 'recipient' );
	}

	/**
		 * Get email subject.
		 *
		 * @param bool $partial Whether it is a partial refund or a full refund.
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_subject() {
			return __( 'Your {first_name} and {last_name}', 'woocommerce' );
		
		}

	/**
	 * This callback helps fire the email notification.
	 *
	 * @param int      $order_id WooCommerce order id.
	 * @param WC_Order $wc_order WooCommerce order.
	 * @since 1.0.0
	 */
	public function hello_elementor_hello_elementor_out_for_delivery_callback( $first_name, $last_name ) {
	
		// Email data object.
		$this->object = $this->create_object( $first_name, $last_name );
		// echo $this->get_subject();
		// die('lkoo');
		// Fire the notification now.
		$this->send(
			$this->get_recipient(),
			$this->get_subject(),
			$this->get_content(),
			$this->get_headers(),
			$this->get_attachments()
		);
	}

	/**
	 * Create the data object that will be used in the template.
	 *
	 * @param int      $order_id WooCommerce order id.
	 * @param WC_Order $wc_order WooCommerce order.
	 * @return stdClass
	 * @since 1.0.0
	 */
	public static function create_object( $first_name, $last_name ) {
		global $wpdb;
		$item_object = new stdClass();

		// WooCommerce Order ID.
		$item_object->first_name = $first_name;
		$item_object->last_name = $last_name;

		// Admin email.
		$item_object->admin_email = get_option( 'admin_email' );

		/**
		 * This filter is fired when sending cancellation requests email on customer request.
		 *
		 * This filter helps managing the item data in the cancellation request email template.
		 *
		 * @param stdClass $item_object Data object.
		 * @return stdClass
		 * @since 1.0.0
		 */
		return apply_filters( 'send_out_for_delivery_email_object', $item_object );
	}

	/**
	 * Get the html content of the email.
	 *
	 * @return string
	 */
	public function get_content_html() {
		ob_start();

		wc_get_template(
			$this->template_html,
			array(
				'item_data'     => $this->object,
				'email_heading' => $this->get_heading()
			),
			'',
			$this->template_base
		);

		return ob_get_clean();
	}

	/**
	 * Get the plain text content of the email.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		ob_start();

		wc_get_template(
			$this->template_plain,
			array(
				'item_data'     => $this->object,
				'email_heading' => $this->get_heading()
			),
			'',
			$this->template_base
		);

		return ob_get_clean();
	}

	/**
	 * Get the email subject line.
	 *
	 * @return string
	 */
	public function get_subject() {
		$subject = $this->get_option( 'subject', $this->get_default_subject( true ) );
		return apply_filters( 'woocommerce_email_subject_' . $this->id, $this->format_string( $this->subject ), $this->object );
	}

	/**
	 * Get the email recipient.
	 *
	 * @return string
	 */
	public function get_recipient() {

		return apply_filters( 'woocommerce_email_recipient_' . $this->id, array( 'parth.sanghvi@concatstring.com' ), $this->object );
	}

	/**
	 * Get the email main heading line.
	 *
	 * @return string
	 */
	public function get_heading() {

		return apply_filters( 'woocommerce_email_heading_' . $this->id, $this->format_string( $this->heading ), $this->object );
	}
	
	/**
	 * Get the email attachments.
	 *
	 * @return array
	 */
	// public function get_attachments() {
	// 	// Get the attachment file.
	// 	$agreement_file = ersrv_get_plugin_settings( 'ersrv_rental_agreement_file_id' );

	// 	// Return blank, if there is no attachment file.
	// 	if ( -1 === $agreement_file ) {
	// 		return array();
	// 	}

	// 	// Get the file path.
	// 	$agreement_file_path = get_attached_file( $agreement_file );

	// 	return array( $agreement_file_path );
	// }

	/**
	 * Get the email settings.
	 *
	 * @return string
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'supavapes' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable this email notification', 'supavapes' ),
				'default' => 'yes'
			),
			'subject' => array(
				'title'       => __( 'Subject', 'supavapes' ),
				'type'        => 'text',
				'description' => sprintf( __( 'This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', 'supavapes' ), $this->subject ),
				'placeholder' => '',
				'default'     => ''
			),
			'heading' => array(
				'title'       => __( 'Email Heading', 'supavapes' ),
				'type'        => 'text',
				'description' => sprintf( __( 'This controls the main heading contained within the email notification. Leave blank to use the default heading: <code>%s</code>.', 'supavapes' ), $this->heading ),
				'placeholder' => '',
				'default'     => ''
			),
			'email_type' => array(
				'title'       => __( 'Email type', 'supavapes' ),
				'type'        => 'select',
				'description' => __( 'Choose which format of email to send.', 'supavapes' ),
				'default'     => 'html',
				'class'       => 'email_type',
				'options'		=> array(
					'html' => __( 'HTML', 'supavapes' ),
				)
			)
		);
	}
} // end \Out_For_Delivery_Email class