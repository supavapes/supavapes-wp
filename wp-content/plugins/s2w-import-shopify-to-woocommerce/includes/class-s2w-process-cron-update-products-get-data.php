<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get products data to sync(Cron products sync)
 *
 * Class WP_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_Process_Cron_Update_Products_Get_Data
 */
class WP_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_Process_Cron_Update_Products_Get_Data extends S2W_Background_Process {

	/**
	 * @var string
	 */
	protected $action = 's2w_process_cron_update_products_get_data';

	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param mixed $item Queue item to iterate over
	 *
	 * @return mixed
	 */
	protected function task( $item ) {

		$data = isset( $item['data'] ) ? $item['data'] : array();

		if ( is_array( $data ) && count( $data ) ) {
			try {
				$settings     = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_instance();
				$domain       = $settings->get_params( 'domain' );
				$access_token = $settings->get_params( 'access_token' );
				$api_key      = $settings->get_params( 'api_key' );
				$api_secret   = $settings->get_params( 'api_secret' );
				$force_sync   = $settings->get_params( 'cron_update_products_force_sync' );
				if ( ( $domain && $api_key && $api_secret ) || $access_token ) {
					vi_s2w_init_set();
					$path = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_cache_path( $domain, $access_token, $api_key, $api_secret ) . '/';
					VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::create_cache_folder( $path );
					$file             = $path . 'update_products_data.txt';
					$log_file         = $path . 'cron_update_products_logs.txt';
					$old_product_data = array();
					if ( is_file( $file ) ) {
						$old_product_data = file_get_contents( $file );
						if ( $old_product_data ) {
							$old_product_data = vi_s2w_json_decode( $old_product_data );
						}
					}
					add_filter( 'http_request_timeout', array( $this, 'bump_request_timeout' ) );
					$request = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::wp_remote_get( $domain, $access_token, $api_key, $api_secret, 'products', false, array(
						'fields' => array(
							'id',
							'options',
							'variants',
						),
						'ids'    => array_values( $data )
					) );
					if ( $request['status'] === 'success' ) {
						$products_data = $request['data'];
						if ( is_array( $products_data ) && count( $products_data ) ) {
							$change = 0;
							foreach ( $products_data as $product_data ) {
								$shopify_product_id = strval( $product_data['id'] );
								$variants           = $product_data['variants'];
								$new_data_get       = array( 'variants' => $variants );
								$product_id         = array_search( $shopify_product_id, $data );
								if ( $product_id !== false ) {
									if ( array_key_exists( $shopify_product_id, $old_product_data ) ) {
										if ( $force_sync || json_encode( $old_product_data[ $shopify_product_id ] ) !== json_encode( $new_data_get ) ) {
											$old_product_data[ $shopify_product_id ] = $new_data_get;
											$this->queue_item_to_update( array(
												'product_id'   => $product_id,
												'product_data' => json_encode( $product_data ),
											) );
											$change ++;
										}
									} else {
										$old_product_data[ $shopify_product_id ] = $new_data_get;
										$this->queue_item_to_update( array(
											'product_id'   => $product_id,
											'product_data' => json_encode( $product_data ),
										) );
										$change ++;
									}
								}
							}
							if ( $change > 0 ) {
								file_put_contents( $file, json_encode( $old_product_data ) );
								S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Cron_Update_Products::$update_products->save()->dispatch();
							}
						} else {
							S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, 'Error: No data' );
						}
					} else {
						S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, 'Error: ' . $request['data'] );
					}
				}
			} catch ( Error $e ) {
				S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::wc_log( 'Background get product data: ' . $e->getMessage() );

				return false;
			} catch ( Exception $e ) {
				S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::wc_log( 'Background get product data: ' . $e->getMessage() );

				return false;
			}
		}

		return false;
	}

	private function queue_item_to_update( $new_data ) {
		S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Cron_Update_Products::$update_products->push_to_queue( $new_data );
	}

	public function bump_request_timeout( $val ) {
		return 600;
	}
}