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
 * needs please refer to https://docs.woocommerce.com/document/moneris/ for more information.
 *
 * @author    SkyVerge
 * @copyright Copyright (c) 2012-2024, SkyVerge, Inc. (info@skyverge.com)
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\Moneris\Handlers;

defined( 'ABSPATH' ) or exit;

use WC_Checkout;
use WP_Error;

/**
 * Overrides the WC_Checkout to handle the validation using default WC_Checkout methods after making them public.
 *
 * @since 3.0.2
 */
class Checkout extends WC_Checkout {


	/**
	 * Sets the main WC_Checkout Instance to this class.
	 *
	 * @since 3.0.2
	 *
	 * @param $instance \WC_Checkout instance
	 */
	public function __construct( $instance ) {

		self::$instance = $instance;
	}


	/**
	 * Validates that the checkout has enough info to proceed.
	 *
	 * @since 3.0.2
	 *
	 * @param array<string, mixed> $data an array of posted data
	 * @param WP_Error $errors validation errors
	 */
	public function validate_checkout( &$data, &$errors ) {

		// copy this piece from the parent method because the 'terms-field' will not be available in the $_POST superglobal that the parent method expects
		if ( empty( $data['woocommerce_checkout_update_totals'] ) && empty( $data['terms'] ) && ! empty( $data['terms-field'] ) ) {
			$errors->add( 'terms', __( 'Please read and accept the terms and conditions to proceed with your order.', 'woocommerce' ) );
		}

		parent::validate_checkout( $data, $errors );
	}


	/**
	 * Update customer and session data from the posted checkout data.
	 *
	 * @since 3.0.2
	 *
	 * @param array<string, mixed> $data posted data
	 */
	public function update_session( $data ) {

		parent::update_session( $data );
	}


}
