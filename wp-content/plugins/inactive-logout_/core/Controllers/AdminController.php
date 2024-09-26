<?php

namespace Codemanas\InactiveLogout\Controllers;

use Codemanas\InactiveLogout\Backend\Menu;
use Codemanas\InactiveLogout\Controllers\Admin\StoreController;

class AdminController {

	private static ?AdminController $_instance = null;

	public static function getInstance(): ?AdminController {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	protected function __construct() {
		$this->init();

		add_action( 'admin_init', [ $this, 'store' ] );
		add_action( 'ina_before_settings_wrapper', [ $this, 'ina_before_settings_wrap' ] );
		add_action( 'ina_after_settings_wrapper', [ $this, 'ina_after_settings_wrap' ] );
	}

	public function init() {
		Menu::getInstance();
	}

	public function store() {
		$adv_submit = filter_input( INPUT_POST, 'adv_submit' );
		if ( ! empty( $adv_submit ) ) {
			if ( ! isset( $_REQUEST['_save_timeout_adv_settings'] ) && ! wp_verify_nonce( $_REQUEST['_save_timeout_adv_settings'], '_nonce_action_save_timeout_adv_settings' ) ) {
				wp_die( __( 'Not allowed', 'inactive-logout' ) );
			}

			StoreController::getInstance()->saveRoleBasedSettings();
		}
	}

	/**
	 * Settings wrapper html element.
	 */
	public function ina_before_settings_wrap() {
		echo '<div class="wrap ina-settings-wrapper">';
	}

	/**
	 * Settings wrapper html element.
	 */
	public function ina_after_settings_wrap() {
		echo '</div>';
	}
}