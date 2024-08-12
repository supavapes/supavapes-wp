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
defined( 'ABSPATH' ) or exit;

/**
 * The Moneris API transaction receipt response class.
 *
 * This extends the standard response to skip XML parsing.
 *
 * @since 1.11.0
 */
class WC_Moneris_API_Receipt_Response extends WC_Moneris_API_Response {


	/**
	 * WC_Moneris_API_Receipt_Response constructor.
	 *
	 * @since 1.11.0
	 *
	 * @param array $receipt_data client-side receipt data
	 */
	public function __construct( array $receipt_data ) {

		$this->response_xml = new stdClass();

		$this->response_xml->receipt = (object) $receipt_data;

		$this->request = new WC_Moneris_API_Request( '', '', '' );
	}


	/**
	 * Gets the AVS result code.
	 *
	 * Client-side responses don't include any AVS result data.
	 *
	 * @since 1.11.0
	 *
	 * @return string
	 */
	public function get_avs_result_code() {
		return '';
	}


	/**
	 * Gets the CSC result code.
	 *
	 * Client-side responses don't include any CSC result data.
	 *
	 * @since 1.11.0
	 *
	 * @return string
	 */
	public function get_csc_result_code() {
		return '';
	}


}
