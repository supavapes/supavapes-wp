<?php

namespace Codemanas\InactiveLogout\Requests;

class GeneralSettingRequests {

	/**
	 * Get main general settings data.
	 *
	 * @return array
	 */
	public static function get(): array {
		return [
			'logout_time'                          => filter_input( INPUT_POST, 'idle_timeout', FILTER_SANITIZE_NUMBER_INT ),
			'logout_message'                       => wp_kses_post( filter_input( INPUT_POST, 'idle_message_text' ) ),
			'after_logout_message'                 => wp_kses_post( filter_input( INPUT_POST, 'after_session_logout_message' ) ),
			'disable_countdown'                    => filter_input( INPUT_POST, 'idle_disable_countdown', FILTER_SANITIZE_NUMBER_INT ),
			'countdown_timeout'                    => filter_input( INPUT_POST, 'idle_countdown_timeout', FILTER_SANITIZE_NUMBER_INT ),
			'warn_message_enabled'                 => filter_input( INPUT_POST, 'ina_show_warn_message_only', FILTER_SANITIZE_NUMBER_INT ),
			'popup_behaviour'                      => filter_input( INPUT_POST, 'ina_popup_behaviour', FILTER_SANITIZE_NUMBER_INT ),
			'warn_message'                         => wp_kses_post( filter_input( INPUT_POST, 'ina_show_warn_message' ) ),
			'concurrent_login'                     => filter_input( INPUT_POST, 'ina_disable_multiple_login', FILTER_SANITIZE_NUMBER_INT ),
			'enable_redirect'                      => filter_input( INPUT_POST, 'ina_enable_redirect_link', FILTER_SANITIZE_NUMBER_INT ),
			'redirect_page_link'                   => filter_input( INPUT_POST, 'ina_redirect_page' ),
			'enable_debugger'                      => filter_input( INPUT_POST, 'ina_enable_debugger' ),
			'disable_close_without_reload'         => filter_input( INPUT_POST, 'popup_modal_close_without_reload_hide' ),
			'disable_automatic_redirect_on_logout' => filter_input( INPUT_POST, 'ina_disable_automatic_redirect' )
		];
	}

	/**
	 * Get localization posted data
	 *
	 * @return array
	 */
	public static function getLocalizations(): array {
		return [
			'text_close'                        => filter_input( INPUT_POST, 'popup_modal_text_close' ),
			'text_ok'                           => filter_input( INPUT_POST, 'popup_modal_text_ok' ),
			'continue_browsing_text'            => filter_input( INPUT_POST, 'popup_modal_text_continue_browsing' ),
			'popup_heading_text'                => filter_input( INPUT_POST, 'popup_modal_text_popup_heading' ),
			'wakeup_cta'                        => filter_input( INPUT_POST, 'popup_modal_wakeup_continue_btn' ),
			'bottom_countdown_logout_text'      => filter_input( INPUT_POST, 'bottom_countdown_logout_text' ),
			'bottom_countdown_last_active_text' => filter_input( INPUT_POST, 'bottom_countdown_last_active_text' ),
		];
	}
}