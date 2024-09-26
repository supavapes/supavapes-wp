<?php

namespace Codemanas\InactiveLogout\Requests;

class RoleBasedSettingRequests {

	/**
	 * Get main general settings data.
	 *
	 * @return array
	 */
	public static function get(): array {
		return [
			'ina_enable_different_role_timeout'     => filter_input( INPUT_POST, 'ina_enable_different_role_timeout' ),
			'ina_multiuser_roles'                   => filter_input( INPUT_POST, 'ina_multiuser_roles', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY ),
			'ina_individual_user_timeout'           => filter_input( INPUT_POST, 'ina_individual_user_timeout', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY ),
			'ina_redirect_page_individual_user'     => filter_input( INPUT_POST, 'ina_redirect_page_individual_user', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY ),
			'ina_disable_inactive_logout'           => filter_input( INPUT_POST, 'ina_disable_inactive_logout', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY ),
			'ina_disable_inactive_concurrent_login' => filter_input( INPUT_POST, 'ina_disable_inactive_concurrent_login', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY ),
		];
	}
}