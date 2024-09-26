<?php

namespace Codemanas\InactiveLogout\Backend;

use Codemanas\InactiveLogout\Helpers;

class Common {

	public function dismissNotices() {
		Helpers::update_option( 'ina_dismiss_like_notice', true );
	}

	public function filterPostPages() {
		$q = filter_input( INPUT_GET, 'q' );

		$args = [
			's'           => $q,
			'post_type'   => apply_filters( 'ina_free_get_custom_post_types', array( 'post', 'page' ) ),
			'post_status' => 'publish',
		];
		// The Query
		$posts_query = new \WP_Query( $args );

		$posts = [];
		if ( ! empty( $posts_query->have_posts() ) ) {
			foreach ( $posts_query->get_posts() as $post ) {
				$posts[] = [ 'text' => get_permalink( $post->ID ), 'id' => get_permalink( $post->ID ) ];
			}
		}

		wp_send_json( $posts );

		wp_die();
	}

	private static ?Common $_instance = null;

	public static function getInstance(): ?Common {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}
