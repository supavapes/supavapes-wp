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

use SkyVerge\WooCommerce\PluginFramework\v5_12_1 as Framework;

/**
 * Moneris payment form class.
 *
 * @since 2.8.0
 */
class WC_Moneris_Payment_Form extends Framework\SV_WC_Payment_Gateway_Payment_Form {


	/**
	 * Adds action & filter hooks.
	 *
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Form::add_hooks()
	 *
	 * @since 2.10.3
	 */
	protected function add_hooks() {

		parent::add_hooks();

		// adjust the payment form JS args
		add_filter( 'wc_' . $this->get_gateway()->get_id() . '_payment_form_js_args', [ $this, 'adjust_form_args' ] );
	}


	/**
	 * Returns the active tokens for the current user/gateway.
	 *
	 * The results are filtered to show only tokens associated with the active currency
	 * or not associated with any currency.
	 *
	 * @since 2.13.0
	 *
	 * @return Framework\SV_WC_Payment_Gateway_Payment_Token[]
	 */
	protected function get_tokens() {

		if ( ! empty( $this->tokens ) ) {
			return $this->tokens;
		}

		$currency = get_woocommerce_currency();
		$tokens   = parent::get_tokens( get_current_user_id() );

		// reset flag to show new payment method form in case we remove the default token
		$this->default_new_payment_method = true;

		foreach ( array_keys( $tokens ) as $token_id ) {

			$data = $tokens[ $token_id ]->to_datastore_format();

			// remove tokens associated with a currency other than the active currency
			if ( isset( $data['currency'] ) && strtoupper( $data['currency'] ) !== $currency ) {

				unset( $tokens[ $token_id ] );

			// don't force new payment method if an existing token is default
			} elseif ( $tokens[ $token_id ]->is_default()) {

				$this->default_new_payment_method = false;
			}
		}

		return $this->tokens = $tokens;
	}


	/**
	 * Render a test amount input field that can be used to override the order total when using the gateway in demo mode.
	 *
	 * The order total can then be set to various amounts to simulate various authorization/settlement responses.
	 *
	 * @internal
	 *
	 * @since 2.1.0
	 *
	 * @return void
	 */
	public function render_payment_form_description() {

		parent::render_payment_form_description();

		if ( $this->get_gateway()->is_test_environment() && ! is_add_payment_method_page() ) :

			$id = 'wc-' . $this->get_gateway()->get_id_dasherized() . '-test-amount';

			?>
			<p class="form-row">
				<label for="<?php echo sanitize_html_class( $id ); ?>"><?php esc_html_e( 'Test Amount', 'woocommerce-gateway-moneris' ); ?></label>
				<input type="text" id="<?php echo sanitize_html_class( $id ); ?>" name="<?php echo esc_attr( $id ); ?>" />
				<span style="display: block; font-size: 10px;" class="description"><?php esc_html_e( 'Enter a test amount to trigger a specific error response, or leave blank to use the order total.', 'woocommerce-gateway-moneris' ); ?></span>
			</p>
			<?php

		endif;
	}


	/**
	 * Adjusts the payment form JS args.
	 *
	 * Ensures the CSC field is always required if present.
	 *
	 * @internal
	 *
	 * @since 2.10.3
	 *
	 * @param array $args
	 * @return array $args
	 */
	public function adjust_form_args( $args ) {

		$args['csc_required'] = $this->get_gateway()->csc_enabled();

		return $args;
	}


	/**
	 * Renders the gateway payment field which is overridden here when hosted tokenization is enabled.
	 *
	 * @since 2.10.0
	 *
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Form::render_payment_field()
	 *
	 * @param array $field
	 */
	protected function render_payment_field( $field ) {

		if ( $this->get_gateway()->hosted_tokenization_enabled() && isset( $field['id'] ) && 'wc-moneris-account-number' === $field['id'] ) :

			?>
			<p class="form-row form-row-full">
				<label for="wc-moneris-account-number"><?php esc_html_e( 'Credit Card Number', 'woocommerce-gateway-moneris' ); ?> <span class="required">*</span></label>
				<iframe class="input-text js-wc-payment-gateway-account-number" id="wc-moneris-account-number" src="<?php echo $this->get_gateway()->get_hosted_tokenization_iframe_url(); ?>" frameborder="0"></iframe>
				<input type="hidden" id="wc-moneris-card-bin" name="wc-moneris-card-bin" value="" />
				<input type="hidden" id="wc-moneris-temp-payment-token" name="wc-moneris-temp-payment-token" value="" />

				<?php if (! $this->has_tokens() || is_add_payment_method_page()) : ?>
					<input type="hidden" id="wc-moneris-use-new-payment-method" name="wc-moneris-payment-token" value="">
				<?php endif; ?>
			</p>
			<?php

		else :

			parent::render_payment_field( $field );

		endif;
	}


	/**
	 * Renders the default payment form JS when hosted tokenization is not enabled.
	 *
	 * @since 2.10.0
	 *
	 * @see Framework\SV_WC_Payment_Gateway_Payment_Form::render_js()
	 */
	public function render_js() {

		if ( ! $this->get_gateway()->hosted_tokenization_enabled() ) {
			parent::render_js();
		}
	}


	/**
	 * Gets the credit card payment fields.
	 *
	 * Overridden to set the CSC field as required so that WC doesn't add an
	 * "optional" label.
	 *
	 * @see \SV_WC_Payment_Gateway_Payment_Form::get_credit_card_fields()
	 *
	 * @since 2.10.2
	 *
	 * @return array
	 */
	public function get_credit_card_fields() {

		$fields = parent::get_credit_card_fields();

		if ( ! empty( $fields['card-csc'] ) ) {
			$fields['card-csc']['required'] = $this->get_gateway()->csc_enabled();
		}

		return $fields;
	}


}
