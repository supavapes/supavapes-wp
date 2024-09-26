<?php

namespace Codemanas\InactiveLogout\Backend;

use Codemanas\InactiveLogout\Controllers\Admin\TabController;

class Menu {
	private static ?Menu $_instance = null;

	public static function getInstance(): ?Menu {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	protected function __construct() {
		add_action( 'admin_menu', [ $this, 'registerMenu' ] );
		add_action( 'network_admin_menu', [ $this, 'multisiteMenu' ] );
	}

	public function menuDigest(): array {
		return [
			'title'      => __( 'Inactive User Logout Settings', 'inactive-logout' ),
			'name'       => __( 'Inactive Logout', 'inactive-logout' ),
			'capability' => 'manage_options',
			'slug'       => 'inactive-logout',
			'callback'   => [ $this, 'renderPage' ]
		];
	}

	/**
	 * Register Menu
	 *
	 * @return void
	 */
	public function registerMenu() {
		$menu = $this->menuDigest();
		if ( is_multisite() && ! empty( get_site_option( '__ina_overrideby_multisite_setting' ) ) ) {
			return;
		}

		add_options_page(
			$menu['title'],
			$menu['name'],
			$menu['capability'],
			$menu['slug'],
			$menu['callback'],
		);
	}

	/**
	 * Add menu page for multisite.
	 */
	public function multisiteMenu() {
		$menu = $this->menuDigest();
		add_menu_page(
			$menu['title'],
			$menu['name'],
			$menu['capability'],
			$menu['slug'],
			$menu['callback'],
		);
	}

	public function renderPage() {
		$tab        = TabController::getInstance();
		$active_tab = $tab->getActiveTab();

		do_action( 'ina_before_settings_wrapper' );
		require_once INACTIVE_LOGOUT_VIEWS . '/tpl-inactive-logout-settings.php';
		$tab->handleTabRouter();
	}
}
