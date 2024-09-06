<?php

namespace Codemanas\InactiveLogout\Controllers\Admin;

class TabController {

	protected array $tabTemplates = [];

	protected function __construct() {
		$this->tabTemplates = [
			'ina-basic'    => 'tpl-inactive-logout-basic.php',
			'ina-support'  => 'tpl-inactive-logout-support.php',
			'ina-advanced' => 'tpl-inactive-logout-advanced.php'
		];
	}

	public function getActiveTab() {
		$tab = filter_input( INPUT_GET, 'tab' );

		return $tab ?? 'ina-basic';
	}

	public function handleTabRouter() {
		$tab = $this->getActiveTab();

		if ( array_key_exists( $tab, $this->tabTemplates ) ) {
			require_once INACTIVE_LOGOUT_VIEWS . '/tabs/' . $this->tabTemplates[ $tab ];
		}

		do_action( 'ina_after_settings_wrapper', $tab );
	}

	private static ?TabController $_instance = null;

	public static function getInstance(): ?TabController {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}