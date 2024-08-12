<?php
/**
 * WooCommerce Moneris.
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade WooCommerce Moneris to newer
 * versions in the future. If you wish to customize WooCommerce Moneris for your
 * needs please refer to http://docs.woocommerce.com/document/moneris-payment-gateway/
 *
 * @author      SkyVerge
 * @copyright   Copyright (c) 2013-2024, SkyVerge, Inc. (info@skyverge.com)
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */
defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\Moneris\Handlers\Checkout;
use SkyVerge\WooCommerce\PluginFramework\v5_12_1 as Framework;

/**
 * Moneris payment form class.
 *
 * @since 3.0.0
 *
 * @method \WC_Gateway_Moneris_Checkout_Credit_Card get_gateway()
 */
class WC_Moneris_Payment_Form extends Framework\SV_WC_Payment_Gateway_Payment_Form {


	/** @var string ticket number required for loading the Moneris Form */
	protected string $ticket_number;


	/**
	 * Sets up class.
	 *
	 * @since 3.0.2
	 *
	 * @param Framework\SV_WC_Payment_Gateway|Framework\SV_WC_Payment_Gateway_Direct $gateway gateway for form
	 */
	public function __construct( $gateway ) {

		parent::__construct( $gateway );

		$this->ticket_number = '';

		add_action( 'woocommerce_checkout_update_order_review', [ $this, 'validate_checkout_fields' ] );
		add_action( 'woocommerce_after_checkout_validation',    [ $this, 'after_checkout_validation' ], 10, 2 );
	}


	/**
	 * Avoid PHP errors by stopping checkout if the customer manages to quickly submit the checkout form just before the Moneris Checkout iframe loads.
	 *
	 * This can happen if you have one last missing required field, and you paste in the value and quickly hit "enter" before the Moneris Checkout iframe loads.
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 */
	public function after_checkout_validation( $data, $errors ) {

		// unless this is a payment attempt, just return
		if ( ! isset( $_POST['woocommerce-process-checkout-nonce'] ) ) {
			return;
		}

		if ( $this->get_gateway()->is_pay_page_enabled() ) {
			return;
		}

		$token_posted  = isset( $_POST['wc-moneris-payment-token'] ) && $_POST['wc-moneris-payment-token'];
		$ticket_posted = isset( $_POST['wc_moneris_ticket'] )        && $_POST['wc_moneris_ticket'];

		// neither a moneris token nor ticket, so this checkout attempt is invalid
		if ( $data['payment_method'] == $this->get_gateway()->get_id_dasherized() && ! ( $token_posted || $ticket_posted ) ) {
			$errors->add( 'checkout', 'Error processing payment, please try again.' );
		}
	}


	/**
	 * Gets the JavaScript class name.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	protected function get_js_handler_class_name() : string {
		return 'WC_Moneris_Payment_Form_Handler';
	}


	/**
	 * Gets the JavaScript handler arguments.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	protected function get_js_handler_args() : array {

		$args = parent::get_js_handler_args();

		$args['environment']              = $this->get_gateway()->is_test_environment() ? 'qa' : 'prod';
		$args['isCheckoutLoggingEnabled'] = $this->get_gateway()->debug_checkout();
		$args['isPayPageEnabled']         = $this->get_gateway()->is_pay_page_enabled();

		return $args;
	}


	/**
	 * Render a test amount input field that can be used to override the order total when using the gateway in demo mode.
	 *
	 * The order total can then be set to various amounts to simulate various authorization/settlement responses.
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 */
	public function render_payment_form_description() {

		parent::render_payment_form_description();

		if ( $this->get_gateway()->is_test_environment() && ! is_add_payment_method_page() ) :

			$id = 'wc-' . $this->get_gateway()->get_id_dasherized() . '-test-amount';

			?>
			<p class="form-row">
				<label for="<?php echo sanitize_key( $id ); ?>"><?php esc_html_e( 'Test Amount', 'woocommerce-gateway-moneris' ); ?></label>
				<input
					type="text" id="<?php echo sanitize_key( $id ); ?>"
					name="<?php echo esc_attr( $id ); ?>"
					value="<?php echo esc_attr( $_GET[ $id ] ?? '' ) ?>"/>
				<span style="display: block; font-size: 10px;" class="description"><?php esc_html_e( 'Enter a test amount to trigger a specific error response, or leave blank to use the order total.', 'woocommerce-gateway-moneris' ); ?></span>
				<script>
					<?php // reload the page when the test amount field loses focus, to reload the Checkout form in case the test amount has changed ?>
					jQuery( '#<?php echo $id; ?>' ).on( 'blur', function() {
						var searchParams = new URLSearchParams( window.location.search );
						searchParams.set( '<?php echo $id; ?>', jQuery( '#<?php echo $id; ?>' ).val() );
						window.location = window.location.href.split( '?' )[0] + '?' + searchParams;
					} );
				</script>
			</p>
			<?php

		endif;
	}


	/**
	 * Sets the ticket number from response.
	 *
	 * @since 3.0.2
	 */
	protected function set_ticket_number() {

		$response = $this->get_gateway()->create_checkout_request();

		if ( $response ) {
			$this->ticket_number = $response->get_ticket_number();
		} else {
			$this->ticket_number = '';
		}
	}


	/**
	 * Validate checkout fields data.
	 *
	 * @since 3.0.2
	 *
	 * @param string $checkout_data checkout data
	 */
	public function validate_checkout_fields( $checkout_data = '' ) {

		if ( ! is_checkout() || $this->get_gateway()->is_pay_page_enabled() ) {
			return;
		}

		$this->ticket_number = '';

		$posted_data = [];

		parse_str( $checkout_data, $posted_data );

		// avoid rendering a bunch of errors unless we have the Moneris payment method selected
		if ( ! isset( $posted_data['payment_method'] ) || $this->get_gateway()->get_id() !== $posted_data['payment_method'] ) {
			return;
		}

		if ( ! isset( $posted_data['ship_to_different_address'] ) ) {
			$posted_data['ship_to_different_address'] = 0;
		}

		// avoid passing a blank country so WC can pull it from billing instead
		// prevents an error where payment form fails to load during guest checkout [ref: MWC-10632]
		if ( isset( $posted_data['shipping_country'] ) && '' === $posted_data['shipping_country'] ) {
			unset( $posted_data['shipping_country'] );
		}

		// include Moneris Checkout class for the fields validation
		require_once wc_moneris()->get_plugin_path() . '/src/Handlers/Checkout.php';

		$moneris_checkout = new Checkout( WC()->checkout() );
		$errors           = new WP_Error();

		try {

			// validate posted data and cart items before proceeding.
			$moneris_checkout->update_session( $posted_data );
			$moneris_checkout->validate_checkout( $posted_data, $errors );

			// finally, generate ticket number if there are no errors
			if ( empty( $errors->errors ) ) {
				$this->set_ticket_number();
			}

		} catch ( Exception $exception ) {
			// don't set a ticket
		}
	}


	/**
	 * Renders the payment fields HTML.
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 */
	public function render_payment_fields() {

		$user_tokens = $this->get_gateway()->get_payment_tokens_handler()->get_tokens( get_current_user_id() );

		if ( is_checkout() && $this->get_gateway()->csc_enabled_for_tokens() && count( $user_tokens ) > 0) {
			$this->render_payment_field( $this->get_csc_field() );
		}

		// set the ticket number to load form: on pay page or on add payment method page. On the checkout
		// page the ticket number will be set (if all required fields are set) by validateCheckoutFields
		// within the partial that's fetched on initial page load, and with each field change
		if ( is_checkout_pay_page() || is_add_payment_method_page() ) {
			$this->set_ticket_number();
		}

		if ( wc_terms_and_conditions_page_id() ) {
			$error_message = __( 'To complete payment, ensure all billing details are provided and Terms & Conditions are accepted.', 'woocommerce-gateway-moneris' );
		} else {
			$error_message = __( 'To complete payment, ensure all billing details are provided.', 'woocommerce-gateway-moneris' );
		}

		$gateway     = $this->get_gateway();
		$form_height = $gateway->has_digital_wallets_enabled() ? '985px' : '760px'; ?>

		<?php

		// Don't include the ticket number field in the returned partial unless we have a value.
		// This is because when the WC core checkout javascript refreshes the payment partial, it will "helpfully" do its best to fill in any fields that are missing values in the new partial, with the value from the old partial.
		// What this means is that if we have a valid ticket number, and then need to return a partial with no ticket number due to some missing required fields, the previous good ticket number will be incorrectly re-used.

		if ( $this->ticket_number ) :

			?>
			<input type="hidden" name="wc_<?php echo $gateway->get_id(); ?>_ticket" id="wc-<?php echo $gateway->get_id_dasherized(); ?>-ticket" value="<?php echo esc_attr( $this->ticket_number ); ?>" />
			<?php

		endif;

		?>
		<div id="wc-<?php echo $gateway->get_id(); ?>-container" style="height: <?php echo $this->ticket_number ? $form_height : 'auto'; ?>">

			<span style="color: #000; display: none;" class="wc-<?php echo $gateway->get_id_dasherized(); ?>-error-message" id="wc-<?php echo $gateway->get_id_dasherized(); ?>-error-message">
				<?php echo $error_message; ?>
			</span>
			<span style="color: #000; display: none;" class="wc-<?php echo $gateway->get_id_dasherized(); ?>-error-message" id="wc-<?php echo $gateway->get_id_dasherized(); ?>-error-message-pay-page">
				<?php esc_html_e( 'To complete this transaction you must log into your account or register an account and set your billing address.', 'woocommerce-gateway-moneris' ); ?>
			</span>

			<div id="wc-moneris-credit-card-checkout-form"></div>
		</div>
		<?php
	}


	/**
	 * Get Card Security Code (CSC) field to render.
	 *
	 * @since 3.0.2
	 *
	 * @return array<string, mixed>
	 */
	protected function get_csc_field() : array {

		return [
			'type'              => 'tel',
			'label'             => esc_html__( 'Card Security Code', 'woocommerce-gateway-moneris' ),
			'id'                => 'wc-' . $this->get_gateway()->get_id_dasherized() . '-csc',
			'name'              => 'wc-' . $this->get_gateway()->get_id_dasherized() . '-csc',
			'placeholder'       => esc_html__( 'CSC', 'woocommerce-gateway-moneris' ),
			'required'          => true,
			'input_class'       => ['js-sv-wc-moneris-gateway-csc-field js-sv-wc-payment-gateway-credit-card-form-csc'],
			'maxlength'         => 4,
			'custom_attributes' => [
				'autocomplete'   => 'off',
				'autocorrect'    => 'no',
				'autocapitalize' => 'no',
				'spellcheck'     => 'no',
			],
			'value' => '',
		];
	}


}
