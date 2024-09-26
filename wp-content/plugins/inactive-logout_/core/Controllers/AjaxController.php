<?php

namespace Codemanas\InactiveLogout\Controllers;

use Codemanas\InactiveLogout\Backend\Common;
use Codemanas\InactiveLogout\Controllers\Admin\StoreController;
use Codemanas\InactiveLogout\Users;

class AjaxController {

	protected function __construct() {
		$this->initHooks();
	}

	public function handles(): array {
		$storeController  = StoreController::getInstance();
		$commonController = Common::getInstance();

		return [
			'ina_save_settings'             => [ $storeController, 'saveGeneralSetting' ],
			'ina_reset_adv_settings'        => [ $storeController, 'resetRoleBasedSettings' ],
			'ina_get_pages_for_redirection' => [ $commonController, 'filterPostPages' ],
			'ina_dismiss_like_notice'       => [ $commonController, 'dismissNotices' ],
			'ina_logout_session'            => [ Users::getInstance(), 'logoutSession' ]
		];
	}

	public function initHooks() {
		$callBackWithKeys = $this->handles();
		foreach ( $callBackWithKeys as $k => $callback ) {
			add_action( 'wp_ajax_' . $k, $callback );
		}
	}

	private static ?AjaxController $_instance = null;

	public static function getInstance(): ?AjaxController {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

}