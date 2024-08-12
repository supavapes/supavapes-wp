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

use SkyVerge\WooCommerce\PluginFramework\v5_12_1 as Framework;
use WC_Order;

/**
 * The capture handler.
 *
 * @since 2.11.0
 *
 * @method \WC_Gateway_Moneris_Credit_Card get_gateway()
 */
class Capture extends Framework\Payment_Gateway\Handlers\Capture {


	/**
	 * Handles a failed capture.
	 *
	 * @since 2.11.0
	 *
	 * @param WC_Order $order order object
	 * @param Framework\SV_WC_Payment_Gateway_API_Response $response API response object
	 */
	public function do_capture_failed( WC_Order $order, Framework\SV_WC_Payment_Gateway_API_Response $response ) {

		parent::do_capture_failed( $order, $response );

		if ( is_callable( [ $response, 'is_authorization_invalid' ] ) && $response->is_authorization_invalid() ) {

			// mark the capture as invalid if it's already been fully captured
			$this->get_gateway()->update_order_meta( $order, 'auth_can_be_captured', 'no' );
		}
	}


}
