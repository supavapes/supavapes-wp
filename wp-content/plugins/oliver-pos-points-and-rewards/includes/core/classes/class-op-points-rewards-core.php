<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if someone accessed directly.
}

/**
 * Class opr_points_rewards_core
 * Description manage all core operation of plugin
 */
class opr_points_rewards_core {

	// Op points and rewards manager
	private $opr_points_rewards_manager;

	/**
	 * Class construct
	 *
	 * @since 1.0.0
	 * @param object $opr_points_rewards opr_points_rewards
	 * @return void void
	 */
	public function __construct( $opr_points_rewards_manager ) {
		$this->opr_points_rewards_manager = $opr_points_rewards_manager;
		// Call create_pages
		$this->opr_core_oliver_create_pages();
	}

	/**
	 * Create pages
	 *
	 * @since 1.0.0
	 * @return void void
	 */
	public function opr_core_oliver_create_pages() {
		$page_ids = get_posts([
			'post_title' => OPR_PAGE_NAME,
			'post_status' => 'private',
			'post_type' => 'page',
			'fields'    => 'ids',  
		]);
		if ( empty($page_ids) ) {
			$post_details = array(
				'post_title'    => OPR_PAGE_NAME,
				'post_content'  => 'This page cant be load directly',
				'post_status'   => 'private',
				'post_type'     => 'page'
			);
			wp_insert_post( $post_details );
		}
	}

}
