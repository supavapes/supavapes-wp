<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get orders data to sync(Cron orders sync)
 *
 * Class WP_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_Process_Cron_Update_Orders_Get_Data
 */
class WP_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_Process_Cron_Update_Orders_Get_Data extends S2W_Background_Process {

	/**
	 * @var string
	 */
	protected $action = 's2w_process_cron_update_orders_get_data';

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
				$settings              = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_instance();
				$domain                = $settings->get_params( 'domain' );
				$access_token          = $settings->get_params( 'access_token' );
				$api_key               = $settings->get_params( 'api_key' );
				$api_secret            = $settings->get_params( 'api_secret' );
				$force_sync            = $settings->get_params( 'cron_update_orders_force_sync' );
				$orders_date_paid_sync = $settings->get_params( 'cron_update_orders_date_paid' );
				if ( ( $domain && $api_key && $api_secret ) || $access_token ) {
					vi_s2w_init_set();
					$path = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_cache_path( $domain, $access_token, $api_key, $api_secret ) . '/';
					VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::create_cache_folder( $path );
					$file             = $path . 'update_orders_data.txt';
					$log_file         = $path . 'cron_update_orders_logs.txt';
					$old_product_data = array();
					if ( is_file( $file ) ) {
						$old_product_data = file_get_contents( $file );
						if ( $old_product_data ) {
							$old_product_data = vi_s2w_json_decode( $old_product_data );
						}
					}
					add_filter( 'http_request_timeout', array( $this, 'bump_request_timeout' ) );
					if ( $orders_date_paid_sync ) {
						$request = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::wp_remote_get( $domain, $access_token, $api_key, $api_secret, 'orders', false, array(
							'status'      => 'any',
							'transaction' => true,
							'ids'         => array_values( $data )
						) );
					} else {
						$request = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::wp_remote_get( $domain, $access_token, $api_key, $api_secret, 'orders', false, array(
							'status' => 'any',
							'fields' => array(
								'id',
								'billing_address',
								'email',
								'fulfillments',
								'financial_status',
								'shipping_address',
							),
							'ids'    => array_values( $data )
						) );
					}

					if ( $request['status'] === 'success' ) {
						$orders = (array) $request['data'];

//						S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, 'New data: '.json_encode($orders) );
						if ( is_array( $orders ) && count( $orders ) ) {
							$change = 0;
							foreach ( $orders as $order_key => $order ) {
								if ( $orders_date_paid_sync ) {
									$shopify_order_id = absint( str_replace( 'Order_', '', $order_key ) );
									$new_data_get     = (array) $order;
									$order_id         = array_search( $shopify_order_id, $data );
								} else {
									$shopify_order_id = strval( $order['id'] );
									$new_data_get     = (array) $order;
									$order_id         = array_search( $shopify_order_id, $data );
								}
								if ( $order_id !== false ) {
									if ( array_key_exists( $shopify_order_id, $old_product_data ) ) {
										if ( $force_sync || json_encode( $old_product_data[ $shopify_order_id ] ) !== json_encode( $new_data_get ) ) {
											$old_product_data[ $shopify_order_id ] = $new_data_get;
											$this->queue_item_to_update( $order_id, $shopify_order_id, (array) $order );
											$change ++;
										} else {
//											S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, 'Not change: '.$shopify_order_id );
										}
									} else {
										$old_product_data[ $shopify_order_id ] = $new_data_get;
										$this->queue_item_to_update( $order_id, $shopify_order_id, (array) $order );
										$change ++;
									}
								} else {
//									S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, 'Not found: '.$shopify_order_id );
								}
							}
							if ( $change > 0 ) {
								file_put_contents( $file, json_encode( $old_product_data ) );
								S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Cron_Update_Orders::$update_orders->save()->dispatch();
							}
						} else {
							S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, 'Error: No data' );
						}

					} else {
						S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, 'Error: ' . $request['data'] );
					}
				}
			} catch ( Error $e ) {
				S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::wc_log( 'Background get order data: ' . $e->getMessage() );

				return false;
			} catch ( Exception $e ) {
				S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::wc_log( 'Background get order data: ' . $e->getMessage() );

				return false;
			}
		}

		return false;
	}

	/**
	 * Push order data to queue to update one by one
	 *
	 * @param $order_id
	 * @param $shopify_order_id
	 * @param $order
	 */
	private function queue_item_to_update( $order_id, $shopify_order_id, $order ) {
		$transactions = (array) ( $order['transactions'] ?? [] );
		$transaction  = (array) ( $transactions[0] ?? [] );
		$new_data     = array(
			'order_id'         => $order_id,
			'shopify_id'       => $shopify_order_id,
			'email'            => sanitize_email( $order['email'] ?? '' ),
			'financial_status' => $order['financial_status'] ?? '',
			'billing_address'  => json_encode( isset( $order['billing_address'] ) ? $order['billing_address'] : array() ),
			'shipping_address' => json_encode( isset( $order['shipping_address'] ) ? $order['shipping_address'] : array() ),
			'fulfillments'     => json_encode( $order['fulfillments'] ?? [] ),
			'transactions'     => ( $transaction['createdAt'] ?? '' ),
		);

		S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Cron_Update_Orders::$update_orders->push_to_queue( $new_data );
	}

	public function bump_request_timeout( $val ) {
		return 600;
	}
}