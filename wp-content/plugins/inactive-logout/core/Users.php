<?php

namespace Codemanas\InactiveLogout;

class Users {

	/**
	 * Logout the actual session from here
	 *
	 * @since 3.0.0
	 * @author Deepen
	 */
	public function logoutSession() {
		check_ajax_referer( '_inaajax', 'security' );

		//Logout Nows
		if ( is_user_logged_in() ) {
			wp_logout();
		}

		wp_send_json( array(
			'isLoggedIn' => is_user_logged_in()
		) );

		wp_die();
	}

	private static ?Users $_instance = null;

	public static function getInstance(): ?Users {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}