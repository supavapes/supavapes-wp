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

/**
 * The lifecycle handler.
 *
 * @since 2.11.0
 *
 * @method \WC_Moneris get_plugin()
 */
class Lifecycle extends Framework\Plugin\Lifecycle {


	/**
	 * Constructs the class.
	 *
	 * @since 2.11.0
	 *
	 * @param \WC_Moneris $plugin
	 */
	public function __construct( \WC_Moneris $plugin ) {

		parent::__construct( $plugin );

		$this->upgrade_versions = [
			'2.3.3',
			'2.15.0',
			'2.17.0',
			'2.17.2',
			'3.0.0',
		];
	}


	/**
	 * Installs the plugin.
	 *
	 * @since 2.11.0
	 */
	protected function install() {

		$settings_key = 'woocommerce_' . \WC_Moneris::CREDIT_CARD_GATEWAY_ID . '_settings';
		$settings     = get_option( $settings_key, [] );

		// v1 releases didn't track the version number, so we can't tell what we're upgrading from
		if ( ! empty( $settings ) ) {
			$this->upgrade( null );
		}

		// TODO remove this flag in a future plugin update when disabling Hosted Tokenization will be disallowed {FN 2020-10-20}
		update_option( 'woocommerce_moneris_show_hosted_tokenization_toggle', 'no' );

		$settings['hosted_tokenization'] = 'yes';

		// default test credentials
		$settings['ca_test_store_id']  = 'store1';
		$settings['ca_test_api_token'] = 'yesguy1';
		$settings['us_test_store_id']  = 'monusqa003';
		$settings['us_test_api_token'] = 'qatoken';

		update_option( $settings_key, $settings );
	}


	/**
	 * Performs any upgrade routines.
	 *
	 * @since 2.11.0
	 *
	 * @param string $installed_version currently installed version
	 */
	protected function upgrade( $installed_version ) {

		if ( null === $installed_version ) {

			// upgrading from v1
			$settings = $this->get_plugin()->get_gateway_settings( \WC_Moneris::CREDIT_CARD_GATEWAY_ID );

			// rename 'purchasecountry' to 'integration'
			$settings['integration'] = $settings['purchasecountry'];
			unset( $settings['purchasecountry'] );

			// framework standard
			$settings['enable_csc'] = $settings['enable_cvd'];
			unset( $settings['enable_cvd'] );

			$settings['dynamic_descriptor'] = $settings['dynamicdescriptor'];
			unset( $settings['dynamicdescriptor'] );

			$settings['environment'] = 'yes' === $settings['sandbox'] ? 'test' : 'production';
			unset( $settings['sandbox'] );

			if ( 'test' === $settings['environment'] ) {

				$settings['test_store_id'] = $settings['storeid'];
				unset( $settings['storeid'] );

				$settings['test_api_token'] = $settings['apitoken'];
				unset( $settings['apitoken'] );

			} else {

				$settings['store_id'] = $settings['storeid'];
				unset( $settings['storeid'] );

				$settings['api_token'] = $settings['apitoken'];
				unset( $settings['apitoken'] );

			}

			// v1 supported only charge transactions
			$settings['transaction_type'] = 'charge';

			// update to new settings
			update_option( 'woocommerce_' . \WC_Moneris::CREDIT_CARD_GATEWAY_ID . '_settings', $settings );
		}

		parent::upgrade( $installed_version );
	}


	/**
	 * Upgrades to v2.3.3.
	 *
	 * @since 2.11.0
	 */
	protected function upgrade_to_2_3_3() {

		$settings = $this->get_plugin()->get_gateway_settings( \WC_Moneris::CREDIT_CARD_GATEWAY_ID );

		$settings['integration_country'] = $settings['integration'];

		unset( $settings['integration'] );

		// update to new settings
		update_option( 'woocommerce_' . \WC_Moneris::CREDIT_CARD_GATEWAY_ID . '_settings', $settings );
	}


	/**
	 * Upgrades to version 2.15.0.
	 *
	 * @since 2.15.0
	 */
	protected function upgrade_to_2_15_0() {

		$gateway = $this->get_plugin()->get_gateway( \WC_Moneris::CREDIT_CARD_GATEWAY_ID );

		if ( is_callable( [ $gateway, 'hosted_tokenization_enabled'] ) ) {

			// TODO add an upgrade script in a future update to remove this flag once disabling Hosted Tokenization will be disallowed {FN 2020-10-20}
			update_option( 'woocommerce_moneris_show_hosted_tokenization_toggle', wc_bool_to_string( ! $gateway->hosted_tokenization_enabled() ) );
		}
	}


	/**
	 * Upgrades to version 2.17.0.
	 *
	 * @since 2.17.0
	 */
	protected function upgrade_to_2_17_0() {

		$gateway  = $this->get_plugin()->get_gateway( \WC_Moneris::CREDIT_CARD_GATEWAY_ID );
		$settings = $this->get_plugin()->get_gateway_settings( \WC_Moneris::CREDIT_CARD_GATEWAY_ID );

		$hosted_tokenization_enabled    = isset( $settings['hosted_tokenization'] ) && 'yes' === $settings['hosted_tokenization'];
		$hosted_tokenization_profile_id = $settings['hosted_tokenization_profile_id'] ?? '';

		if ( ( ! $hosted_tokenization_enabled || ! $hosted_tokenization_profile_id ) && $gateway->is_enabled() ) {
			update_option( 'woocommerce_moneris_show_hosted_tokenization_required_notice', 'yes' );
		}

		delete_option( 'woocommerce_moneris_show_hosted_tokenization_toggle' );
	}


	/**
	 * Upgrades to version 2.17.2.
	 *
	 * Beginning from this version, merchants should enter their test store ID and API token manually.
	 * This upgrade script will update to the defaults the plugin had been using so far as provided by Moneris.
	 *
	 * @since 2.17.2
	 */
	protected function upgrade_to_2_17_2() {

		$gateway  = $this->get_plugin()->get_gateway( \WC_Moneris::CREDIT_CARD_GATEWAY_ID );
		$settings = $this->get_plugin()->get_gateway_settings( \WC_Moneris::CREDIT_CARD_GATEWAY_ID );

		$settings_to_update = [
			\WC_Moneris::INTEGRATION_CA => [
				'ca_test_store_id'     => 'ca_test_api_token',
				'cad_ca_test_store_id' => 'cad_ca_test_api_token',
				'usd_ca_test_store_id' => 'usd_ca_test_api_token',
			],
			\WC_Moneris::INTEGRATION_US => [
				'us_test_store_id'     => 'us_test_api_token',
				'usd_us_test_store_id' => 'usd_us_test_api_token',
				'cad_us_test_store_id' => 'cad_us_test_api_token',
			],
		];

		foreach ( $settings_to_update as $integration => $test_credentials ) {

			foreach ( $test_credentials as $store_id => $api_token) {

				if ( ! empty( $settings[ $store_id ] ) ) {
					$settings[ $api_token ] = $gateway->get_default_test_api_token( $integration, $settings[ $store_id ] );
				}
			}
		}

		update_option( 'woocommerce_' . \WC_Moneris::CREDIT_CARD_GATEWAY_ID . '_settings', $settings );
	}


	/**
	 * Upgrades to version 3.0.0.
	 *
	 * Beginning from this version, merchants will be upgraded to Moneris Checkout (MCO).
	 * This upgrade routine will add a wp_option for the legacy users and allow them to migrate to MCO.
	 *
	 * @since 3.0.0
	 */
	protected function upgrade_to_3_0_0() {

		$settings      = $this->get_plugin()->get_gateway_settings( \WC_Moneris::CREDIT_CARD_GATEWAY_ID );
		$is_production = 'production' === $settings['environment'];

		if ( $is_production ) {
			$store_id  = $settings['store_id'] ?? '';
			$api_token = $settings['api_token'] ?? '';
		} else {
			$store_id  = $settings['ca_test_store_id'] ?? '';
			$api_token = $settings['ca_test_api_token'] ?? '';
		}

		if ( $store_id && $api_token ) {
			update_option( 'woocommerce_' . \WC_Moneris::CREDIT_CARD_GATEWAY_ID . '_legacy_gateway_enabled', true );
		}

		$moneris_settings = get_option( 'woocommerce_' . \WC_Moneris::INTERAC_GATEWAY_ID . '_settings' );

		if ( $moneris_settings && isset( $moneris_settings['enabled'] ) && 'yes' === $moneris_settings['enabled'] ) {
			update_option( 'woocommerce_' . \WC_Moneris::INTERAC_GATEWAY_ID . '_available', true );
		}
	}


}
