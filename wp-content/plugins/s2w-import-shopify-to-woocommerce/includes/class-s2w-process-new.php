<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Import images of products imported/updated on the main settings page
 *
 * Class WP_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_Process_New
 */
class WP_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_Process_New extends S2W_Background_Process {

	/**
	 * @var string
	 */
	protected $action = 's2w_process_new';
}