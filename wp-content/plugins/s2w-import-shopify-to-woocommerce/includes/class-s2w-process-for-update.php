<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Import images when syncing products(manually) or images of products imported/updated by webhooks
 *
 * Class WP_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_Process_For_Update
 */
class WP_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_Process_For_Update extends S2W_Background_Process {

	/**
	 * @var string
	 */
	protected $action = 's2w_process_for_update';
}