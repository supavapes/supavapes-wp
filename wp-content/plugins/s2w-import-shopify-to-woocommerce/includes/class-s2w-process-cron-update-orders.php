<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sync orders(Cron orders sync)
 *
 * Class WP_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_Process_Cron_Update_Orders
 */
class WP_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_Process_Cron_Update_Orders extends S2W_Background_Process {

	/**
	 * @var string
	 */
	protected $action = 's2w_process_cron_update_orders';

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
		$order_id         = isset( $item['order_id'] ) ? $item['order_id'] : '';
		$billing_address  = isset( $item['billing_address'] ) ? vi_s2w_json_decode( $item['billing_address'] ) : array();
		$shipping_address = isset( $item['shipping_address'] ) ? vi_s2w_json_decode( $item['shipping_address'] ) : array();
		$fulfillments     = isset( $item['fulfillments'] ) ? vi_s2w_json_decode( $item['fulfillments'] ) : array();
		$financial_status = isset( $item['financial_status'] ) ? $item['financial_status'] : '';
		$transactions_data     = isset( $item['transactions'] ) ?  $item['transactions']  : '';
		$email            = isset( $item['email'] ) ? $item['email'] : '';

		if ( $order_id ) {
			try {
				$settings     = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_instance();
				$domain       = $settings->get_params( 'domain' );
				$access_token = $settings->get_params( 'access_token' );
				$api_key      = $settings->get_params( 'api_key' );
				$api_secret   = $settings->get_params( 'api_secret' );
				if ( ( $domain && $api_key && $api_secret ) || $access_token ) {
					$path                       = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_cache_path( $domain, $access_token, $api_key, $api_secret ) . '/';
					$log_file                   = $path . 'cron_update_orders_logs.txt';
					$cron_update_orders_options = $settings->get_params( 'cron_update_orders_options' );
					$orders_date_paid_sync      = $settings->get_params( 'cron_update_orders_date_paid' );
					if ( is_array( $cron_update_orders_options ) && count( $cron_update_orders_options ) ) {
						$order_obj = wc_get_order( $order_id );
						if ( $order_obj ) {
							S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, "Updated order #{$order_id}." );
							$update_data = array();
							if ( $orders_date_paid_sync ) {

								$date_paid   = $transactions_data;
								if ( ! empty( $date_paid ) ) {
									$order_obj->set_date_paid($date_paid);
								}
							}
							if ( in_array( 'status', $cron_update_orders_options ) ) {
								$order_status_mapping = $settings->get_params( 'order_status_mapping' );
								$order_status         = isset( $order_status_mapping[ $financial_status ] ) ? ( 'wc-' . $order_status_mapping[ $financial_status ] ) : '';
								if ( $order_status ) {
									$order_obj->set_status( $order_status );
									$order_obj->set_id( $order_id );

								}
							}
							if ( in_array( 'fulfillments', $cron_update_orders_options ) && $fulfillments ) {
								$order_obj->update_meta_data( '_s2w_shopify_order_fulfillments', $fulfillments );
							}
							$order_obj->save();
							$data = array();
							if ( in_array( 'billing_address', $cron_update_orders_options ) && $billing_address ) {
								$data = array_merge( array(
									'billing_first_name' => isset( $billing_address['first_name'] ) ? $billing_address['first_name'] : '',
									'billing_last_name'  => isset( $billing_address['last_name'] ) ? $billing_address['last_name'] : '',
									'billing_company'    => isset( $billing_address['company'] ) ? $billing_address['company'] : '',
									'billing_country'    => isset( $billing_address['country'] ) ? $billing_address['country'] : '',
									'billing_address_1'  => isset( $billing_address['address1'] ) ? $billing_address['address1'] : '',
									'billing_address_2'  => isset( $billing_address['address2'] ) ? $billing_address['address2'] : '',
									'billing_postcode'   => isset( $billing_address['zip'] ) ? $billing_address['zip'] : '',
									'billing_city'       => isset( $billing_address['city'] ) ? $billing_address['city'] : '',
									'billing_state'      => isset( $billing_address['province'] ) ? $billing_address['province'] : '',
									'billing_phone'      => isset( $billing_address['phone'] ) ? $billing_address['phone'] : '',
									'billing_email'      => $email,
								), $data );
							}
							if ( in_array( 'shipping_address', $cron_update_orders_options ) && $shipping_address ) {
								$data = array_merge( array(
									'shipping_first_name' => isset( $shipping_address['first_name'] ) ? $shipping_address['first_name'] : '',
									'shipping_last_name'  => isset( $shipping_address['last_name'] ) ? $shipping_address['last_name'] : '',
									'shipping_company'    => isset( $shipping_address['company'] ) ? $shipping_address['company'] : '',
									'shipping_country'    => isset( $shipping_address['country'] ) ? $shipping_address['country'] : '',
									'shipping_address_1'  => isset( $shipping_address['address1'] ) ? $shipping_address['address1'] : '',
									'shipping_address_2'  => isset( $shipping_address['address2'] ) ? $shipping_address['address2'] : '',
									'shipping_postcode'   => isset( $shipping_address['zip'] ) ? $shipping_address['zip'] : '',
									'shipping_city'       => isset( $shipping_address['city'] ) ? $shipping_address['city'] : '',
									'shipping_state'      => isset( $shipping_address['province'] ) ? $shipping_address['province'] : '',
								), $data );
							}
							if ( count( $data ) ) {
								foreach ( $data as $key => $value ) {
									if ( is_callable( array( $order_obj, "set_{$key}" ) ) && $value ) {
										$order_obj->{"set_{$key}"}( $value );
										// Store custom fields prefixed with wither shipping_ or billing_. This is for backwards compatibility with 2.6.x.
									} elseif ( isset( $fields_prefix[ current( explode( '_', $key ) ) ] ) ) {
										if ( ! isset( $shipping_fields[ $key ] ) ) {
											$order_obj->update_meta_data( '_' . $key, $value );
										}
									}
								}
								$order_obj->save();
							}
						}
					}
				}
			} catch ( Error $e ) {
				S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::wc_log( 'Cron orders sync: ' . $e->getMessage() );

				return false;
			} catch ( Exception $e ) {
				S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::wc_log( 'Cron orders sync: ' . $e->getMessage() );

				return false;
			}
		}

		return false;
	}

	/**
	 * Complete
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 */
	protected function complete() {
		if ( ! $this->is_process_running() && $this->is_queue_empty() ) {
			$settings     = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_instance();
			$domain       = $settings->get_params( 'domain' );
			$access_token = $settings->get_params( 'access_token' );
			$api_key      = $settings->get_params( 'api_key' );
			$api_secret   = $settings->get_params( 'api_secret' );
			if ( ( $domain && $api_key && $api_secret ) || $access_token ) {
				$path     = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_cache_path( $domain, $access_token, $api_key, $api_secret ) . '/';
				$log_file = $path . 'cron_update_orders_logs.txt';
				S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, 'Cron orders sync finished.' . PHP_EOL );
			}
		}
		// Show notice to user or perform some other arbitrary task...
		parent::complete();
	}
}