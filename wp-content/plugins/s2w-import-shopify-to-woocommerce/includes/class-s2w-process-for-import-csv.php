<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Import images of products imported/updated from CSV files
 *
 * Class WP_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_Process_For_Import_Csv
 */
class WP_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_Process_For_Import_Csv extends S2W_Background_Process {

	/**
	 * @var string
	 */
	protected $action = 's2w_process_for_import_csv';
}