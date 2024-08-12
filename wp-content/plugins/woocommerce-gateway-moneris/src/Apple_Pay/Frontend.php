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
 * needs please refer to http://docs.woocommerce.com/document/moneris/ for more information.
 *
 * @author    SkyVerge
 * @copyright Copyright (c) 2012-2024, SkyVerge, Inc. (info@skyverge.com)
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\Moneris\Apple_Pay;

defined( 'ABSPATH' ) or exit;

use Exception;
use SkyVerge\WooCommerce\PluginFramework\v5_12_1 as Framework;
use WC_Cart;
use WC_Product;

/**
 * The Moneris Apple Pay frontend handler.
 *
 * @method \SkyVerge\WooCommerce\Moneris\Apple_Pay get_handler()
 * @method \WC_Gateway_Moneris_Credit_Card get_gateway()
 */
class Frontend extends Framework\SV_WC_Payment_Gateway_Apple_Pay_Frontend {


	/**
	 * Gets the JS handler class name.
	 *
	 * Overridden so the FW doesn't append the version number to the end.
	 *
	 * @since 2.14.0
	 *
	 * @return string
	 */
	protected function get_js_handler_class_name() {
		return $this->js_handler_base_class_name;
	}


	/**
	 * Enqueues the scripts and styles.
	 *
	 * @since 1.11.0
	 */
	public function enqueue_scripts() {

		parent::enqueue_scripts();

		// limit the Moneris Apple Pay SDK to relevant pages, as JS errors will be thrown if it's not utilized
		if ( is_product() || is_cart() || is_checkout() ) {

			$sdk_url = $this->get_gateway()->is_test_environment() ? 'https://esqa.moneris.com/applepayjs/applepay-api.js' : 'https://www3.moneris.com/applepayjs/applepay-api.js';

			wp_register_script( 'moneris-apple-pay-js', $sdk_url, [], $this->get_plugin()->get_version(), false );
		}

		wp_enqueue_script( 'wc-moneris-apple-pay', $this->get_plugin()->get_plugin_url() . '/assets/js/frontend/wc-moneris-apple-pay.min.js', ['jquery', 'moneris-apple-pay-js'], $this->get_plugin()->get_version(), true );
	}


	/**
	 * Renders the Apple Pay button and Moneris iframe markup.
	 *
	 * @since 1.11.0
	 */
	public function render_button() {

		parent::render_button();

		?>
		<div
			id="moneris-apple-pay"
			style="display: none;"
			store-id="<?php echo esc_attr( $this->get_gateway()->get_store_id() ); ?>"
			merchant-identifier="<?php echo esc_attr( $this->get_handler()->get_merchant_id() ); ?>"
			display-name="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"></div>
		<?php
	}


	/**
	 * Initializes the product handling.
	 *
	 * @since 2.14.0
	 */
	public function init_product() {

		$this->js_handler_base_class_name = 'WC_Moneris_Apple_Pay_Product_Handler';

		parent::init_product();
	}


	/**
	 * Gets the args passed to the product JS handler.
	 *
	 * @since 2.14.0
	 *
	 * @param WC_Product $product product object
	 * @return array
	 * @throws Exception
	 */
	protected function get_product_js_handler_args( WC_Product $product ) {

		$args = $this->get_handler_args();

		try {

			$product = wc_get_product( get_the_ID() );

			if ( ! $product ) {
				throw new Framework\SV_WC_Payment_Gateway_Exception( 'Product does not exist.' );
			}

			$payment_request = $this->get_handler()->get_product_payment_request( $product );

			$args['payment_request'] = $payment_request;

		} catch ( Framework\SV_WC_Payment_Gateway_Exception $exception ) {

			$this->get_handler()->log( 'Could not initialize Apple Pay. ' . $exception->getMessage() );
		}

		// replace the page load nonces now that a session has been created
		$args['create_order_nonce']    = wp_create_nonce( 'wc_moneris_apple_pay_create_order' );
		$args['process_receipt_nonce'] = wp_create_nonce( 'wc_moneris_apple_pay_process_receipt' );

		/**
		 * Filters the Apple Pay product handler args.
		 *
		 * @since 1.11.0
		 *
		 * @param array $args
		 */
		return (array) apply_filters( 'wc_moneris_apple_pay_product_handler_args', $args );
	}


	/** Cart functionality ****************************************************/


	/**
	 * Initializes the cart handling.
	 *
	 * @since 2.14.0
	 */
	public function init_cart() {

		$this->js_handler_base_class_name = 'WC_Moneris_Apple_Pay_Cart_Handler';

		parent::init_cart();
	}


	/**
	 * Gets the args passed to the cart JS handler.
	 *
	 * @since 2.14.0
	 *
	 * @param WC_Cart $cart cart object
	 * @return array
	 */
	protected function get_cart_js_handler_args( WC_Cart $cart ) {

		$args = $this->get_handler_args();

		try {

			$payment_request = $this->get_handler()->get_cart_payment_request( WC()->cart );

			$args['payment_request'] = $payment_request;

		} catch ( Framework\SV_WC_Payment_Gateway_Exception $exception ) {

			$args['payment_request'] = false;
		}

		/**
		 * Filters the Apple Pay cart handler args.
		 *
		 * @since 1.11.0
		 *
		 * @param array $args
		 */
		return (array) apply_filters( 'wc_moneris_apple_pay_cart_handler_args', $args );
	}


	/** Checkout functionality ************************************************/


	/**
	 * Initializes the checkout handling.
	 *
	 * @since 2.14.0
	 */
	public function init_checkout() {

		$this->js_handler_base_class_name = 'WC_Moneris_Apple_Pay_Checkout_Handler';

		parent::init_checkout();
	}


	/**
	 * Gets the args passed to the checkout JS handler.
	 *
	 * @since 2.14.0
	 *
	 * @return array
	 */
	protected function get_checkout_js_handler_args() {

		/*
		 * Filters the Apple Pay checkout handler args.
		 *
		 * @since 1.11.0
		 *
		 * @param array $args
		 */
		return apply_filters( 'wc_moneris_apple_pay_checkout_handler_args', $this->get_handler_args() );
	}


	/**
	 * Gets the Moneris handler args.
	 *
	 * @since 1.11.0
	 *
	 * @return array
	 */
	protected function get_handler_args() {

		return [
			'create_order_nonce'    => wp_create_nonce( 'wc_moneris_apple_pay_create_order' ),
			'process_receipt_nonce' => wp_create_nonce( 'wc_moneris_apple_pay_process_receipt' ),
			'debug_log'             => ! $this->get_gateway()->debug_off(),
		];
	}


}
