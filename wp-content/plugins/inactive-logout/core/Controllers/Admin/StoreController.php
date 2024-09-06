<?php

namespace Codemanas\InactiveLogout\Controllers\Admin;

use Codemanas\InactiveLogout\Helpers;
use Codemanas\InactiveLogout\Requests\GeneralSettingRequests;
use Codemanas\InactiveLogout\Requests\RoleBasedSettingRequests;

class StoreController {

	/**
	 * Save general settings logic here
	 *
	 * @return void
	 */
	public function saveGeneralSetting() {
		check_ajax_referer( '_nonce_action_save_timeout_settings', '_save_timeout_settings' );

		do_action( 'ina_before_update_basic_settings' );

		//Update Text Localizations to another field.
		$localizationData = GeneralSettingRequests::getLocalizations();
		Helpers::update_option( '__ina_logout_popup_localizations', $localizationData );

		$postedData = GeneralSettingRequests::get();

		// If multisite is Active then Add these settings to multisite option table as well.
		if ( Helpers::isMultisite() ) {
			$overrideForAllSites = filter_input( INPUT_POST, 'idle_overrideby_multisite_setting', FILTER_SANITIZE_NUMBER_INT );
			update_site_option( '__ina_overrideby_multisite_setting', $overrideForAllSites );
		}

		$save_minutes = ( $postedData['logout_time'] > 1440 ) ? 1440 : $postedData['logout_time'] * 60; // 60 minutes
		if ( ! empty( $postedData['logout_time'] ) ) {
			//If redirection is a custom link
			$externalRedirectionLink = ! empty( $postedData['redirect_page_link'] ) && 'custom-page-redirect' === $postedData['redirect_page_link'] ? filter_input( INPUT_POST, 'custom_redirect_text_field' ) : false;
			if ( ! empty( $postedData['enable_redirect'] ) && ! empty( $postedData['redirect_page_link'] ) && 'custom-page-redirect' === $postedData['redirect_page_link'] ) {
				$postedData['custom_redirect_text_field'] = sanitize_url( $externalRedirectionLink );
				Helpers::update_option( '__ina_custom_redirect_text_field', sanitize_url( $externalRedirectionLink ) );
			} else {
				$postedData['custom_redirect_text_field'] = null;
			}

			/**
			 * Save settings
			 *
			 * Deprecating in in future versions use "__ina_general_settings" instead
			 */
			$postedData['logout_time'] = $save_minutes;
			foreach ( $postedData as $key => $value ) {
				Helpers::update_option( "__ina_{$key}", $value );
			}

			//New method to save all data into one row
			Helpers::update_option( "__ina_general_settings", $postedData );
		}

		do_action( 'ina_after_update_basic_settings' );

		Helpers::update_option( '__ina_saved_options', __( 'General Settings saved.', 'inactive-logout' ), false );

		wp_send_json_success();
	}

	/**
	 * Save role based settings logic here
	 *
	 * @return void
	 */
	public function saveRoleBasedSettings() {
		$postedData = RoleBasedSettingRequests::get();

		$container_multi_user_arr = array();

		//Enabled multi-role
		if ( ! empty( $postedData['ina_multiuser_roles'] ) ) {
			foreach ( $postedData['ina_multiuser_roles'] as $k => $ina_multiuser_role ) {
				$user_timeout_minutes               = ! empty( $postedData['ina_individual_user_timeout'][ $k ] ) ? absint( $postedData['ina_individual_user_timeout'][ $k ] ) : 15;
				$redirect_page_link                 = $postedData['ina_redirect_page_individual_user'][ $ina_multiuser_role ] ?? null;
				$disabled_for_user                  = ! empty( $postedData['ina_disable_inactive_logout'][ $ina_multiuser_role ] );
				$disabled_for_user_concurrent_login = ! empty( $postedData['ina_disable_inactive_concurrent_login'][ $ina_multiuser_role ] );

				// Validate URL
				if ( $redirect_page_link && filter_var( $redirect_page_link, FILTER_VALIDATE_URL ) === false ) {
					$redirect_page_link = false;
				}

				$container_multi_user_arr[] = array(
					'role'                      => $ina_multiuser_role,
					'timeout'                   => min( $user_timeout_minutes, 1440 ),
					'redirect_page'             => $redirect_page_link,
					'disabled_feature'          => $disabled_for_user ? 1 : null,
					'disabled_concurrent_login' => $disabled_for_user_concurrent_login ? 1 : null,
				);
			}
		}

		do_action( 'ina_before_update_adv_settings', $container_multi_user_arr );

		Helpers::update_option( '__ina_enable_timeout_multiusers', $postedData['ina_enable_different_role_timeout'] );
		if ( ! empty( $postedData['ina_enable_different_role_timeout'] ) ) {
			Helpers::update_option( '__ina_multiusers_settings', $container_multi_user_arr );
		}

		do_action( 'ina_after_update_adv_settings', $container_multi_user_arr );

		Helpers::set_message( __( 'Role based settings saved.', 'inactive-logout' ) );
	}

	public function resetRoleBasedSettings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			exit;
		}

		check_ajax_referer( '_ina_security_nonce', 'security' );

		delete_option( '__ina_roles' );
		delete_option( '__ina_enable_timeout_multiusers' );
		delete_option( '__ina_multiusers_settings' );

		if ( wp_doing_ajax() && get_current_blog_id() == get_main_network_id() && is_multisite() ) {
			delete_site_option( '__ina_roles' );
			delete_site_option( '__ina_enable_timeout_multiusers' );
			delete_site_option( '__ina_multiusers_settings' );
		}

		Helpers::update_option( '__ina_saved_options', __( 'Role based settings reset.', 'inactive-logout' ), false );

		wp_send_json( array(
			'code' => 1,
			'msg'  => esc_html__( 'Reset advanced settings successful.', 'inactive-logout' ),
		) );

		wp_die();
	}

	private static ?StoreController $_instance = null;

	public static function getInstance(): ?StoreController {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

}