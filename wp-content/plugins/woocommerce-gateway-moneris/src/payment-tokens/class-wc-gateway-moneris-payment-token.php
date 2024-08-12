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
 * Moneris payment token class.
 *
 * @since 3.0.0
 */
class WC_Gateway_Moneris_Payment_Token extends Framework\SV_WC_Payment_Gateway_Payment_Token {


	/**
	 * Gets the issuer ID.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_issuer_id() {

		return $this->data['issuer_id'] ?? '';
	}


}
