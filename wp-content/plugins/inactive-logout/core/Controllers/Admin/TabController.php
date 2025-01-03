<?php

namespace Codemanas\InactiveLogout\Controllers\Admin;

use Codemanas\InactiveLogout\Helpers;

class TabController {

	protected array $tabTemplates = [];

	protected function __construct() {
		$this->tabTemplates = [
			'ina-basic'      => 'tpl-inactive-logout-basic.php',
			'ina-support'    => 'tpl-inactive-logout-support.php',
			'ina-advanced'   => 'tpl-inactive-logout-advanced.php',
			'ina-user-based' => 'tpl-inactive-logout-user-based.php'
		];
	}

	public function getActiveTab() {
		$tab = filter_input( INPUT_GET, 'tab' );

		return $tab ?? 'ina-basic';
	}

	public function handleTabRouter() {
		$tab = $this->getActiveTab();

		if ( array_key_exists( $tab, $this->tabTemplates ) ) {
			if ( Helpers::is_pro_version_active() && $tab == "ina-user-based" ) {
				require_once INLOGOUT_ADDON_PLUGIN_VIEWS . $this->tabTemplates[ $tab ];
			} else {
				require_once INACTIVE_LOGOUT_VIEWS . '/tabs/' . $this->tabTemplates[ $tab ];
			}
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