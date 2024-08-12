<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Sync orders manually from admin Orders table page
 */
if ( ! class_exists( 'S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Update_Orders' ) ) {
	class S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Update_Orders {
		protected static $settings;
		protected $is_page;
		protected $request;
		protected $process;
		protected $gmt_offset;

		public function __construct() {
			self::$settings = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_instance();
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_script' ) );
			add_filter( 'manage_edit-shop_order_columns', array( $this, 'button_update_from_shopify' ) );
			add_action( 'manage_shop_order_posts_custom_column', array( $this, 'column_callback_order' ), 2, 20 );
			/*HPOS*/
			add_filter( 'woocommerce_shop_order_list_table_columns', array( $this, 'button_update_from_shopify' ) );
			add_action( 'woocommerce_shop_order_list_table_custom_column', array( $this, 'column_callback_order' ), 2, 20 );

			add_action( 'wp_ajax_s2w_update_orders', array( $this, 'update_orders' ) );
			add_action( 'wp_ajax_s2w_update_order_options_save', array( $this, 'save_options' ) );
		}

		/**
		 * @param $name
		 * @param bool $set_name
		 *
		 * @return string|void
		 */
		private static function set( $name, $set_name = false ) {
			return VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::set( $name, $set_name );
		}

		/**
		 * Add Shopify sync column on admin orders table
		 *
		 * @param $cols
		 *
		 * @return mixed
		 */
		public function button_update_from_shopify( $cols ) {
			$cols['s2w_update_from_shopify'] = '<span class="s2w-button ' . self::set( 'shopify-update-order' ) . '">' . __( 'Shopify Sync', 's2w-import-shopify-to-woocommerce' ) . '</span>';

			return $cols;
		}

		/**
		 * Shopify sync column content, show log and status
		 *
		 * @param $col
		 * @param $order
		 */
		public function column_callback_order( $col, $order ) {

			if ( $col === 's2w_update_from_shopify' ) {
				$all_options = self::get_supported_options();
				if ( null === $this->gmt_offset ) {
					$this->gmt_offset = get_option( 'gmt_offset' );
				}
				if ( ! is_object( $order ) ) {
					$order = wc_get_order( $order );
				}
				$order_id       = $order->get_id();
				$shopify_id     = $order->get_meta( '_s2w_shopify_order_id', true );
				$update_history = $order->get_meta( '_s2w_update_order_history', true );
				$order->save_meta_data();
				if ( $shopify_id ) {
					?>
                    <div class="<?php echo esc_attr( self::set( 'update-from-shopify-history' ) ) ?>">
						<?php
						if ( $update_history ) {
							$update_time          = isset( $update_history['time'] ) ? $update_history['time'] : '';
							$update_status        = isset( $update_history['status'] ) ? $update_history['status'] : '';
							$update_order_options = isset( $update_history['fields'] ) ? $update_history['fields'] : array();
							$update_fields_html   = array();
							$error                = '';
							if ( $update_status === 'error' && ! empty( $update_history['message'] ) ) {
								$error = $update_history['message'];
							}

							if ( is_array( $update_order_options ) && count( $update_order_options ) ) {
								foreach ( $update_order_options as $update_field ) {
									if ( key_exists( $update_field, $all_options ) ) {
										$update_fields_html[] = $all_options[ $update_field ];
									} elseif ( $update_field === 'domain' ) {
										$update_fields_html[] = 'domain';
									}
								}
							}

							?>
                            <p><?php esc_html_e( 'Last sync: ', 's2w-import-shopify-to-woocommerce' ) ?>
                                <strong>
                                    <span class="<?php echo esc_attr( self::set( 'update-from-shopify-history-time' ) ) ?>"><?php echo esc_html( $update_time ? date_i18n( 'F d, Y H:i:s', absint( $update_time ) + $this->gmt_offset * 3600
	                                    ) : '' ) ?></span>
                                </strong>
                            </p>
                            <p><?php esc_html_e( 'Status: ', 's2w-import-shopify-to-woocommerce' ) ?><strong><span
                                            title="<?php echo esc_attr( $error ) ?>"
                                            class="<?php echo esc_attr( self::set( array(
												'update-from-shopify-history-status',
												'update-from-shopify-history-status-' . $update_status
											) ) ) ?>"><?php echo esc_html( ucwords( $update_status ) ) ?></span></strong>
                            </p>
                            <p><?php esc_html_e( 'Synced field(s): ', 's2w-import-shopify-to-woocommerce' ) ?>
                                <strong><span
                                            class="<?php echo esc_attr( self::set( 'update-from-shopify-history-fields' ) ) ?>"><?php echo implode( ', ', $update_fields_html ) ?></span></strong>
                            </p>
							<?php
						} else {
							?>
                            <p><?php esc_html_e( 'Last sync: ', 's2w-import-shopify-to-woocommerce' ) ?>
                                <strong><span
                                            class="<?php echo esc_attr( self::set( 'update-from-shopify-history-time' ) ) ?>"></span></strong>
                            </p>
                            <p><?php esc_html_e( 'Status: ', 's2w-import-shopify-to-woocommerce' ) ?><strong><span
                                            class="<?php echo esc_attr( self::set( 'update-from-shopify-history-status' ) ) ?>"></span></strong>
                            </p>
                            <p><?php esc_html_e( 'Synced field(s): ', 's2w-import-shopify-to-woocommerce' ) ?>
                                <strong><span
                                            class="<?php echo esc_attr( self::set( 'update-from-shopify-history-fields' ) ) ?>"></span></strong>
                            </p>
							<?php
						}
						?>
                    </div>
                    <span class="s2w-button <?php echo esc_attr( self::set( 'shopify-order-id' ) ) ?>"
                          data-order_id="<?php echo esc_attr( $order_id ) ?>"
                          data-shopify_order_id="<?php echo esc_attr( $shopify_id ) ?>"><?php esc_html_e( 'Sync', 's2w-import-shopify-to-woocommerce' ) ?>
                        </span>
					<?php
				}
			}
		}

		/**
		 * Ajax handler for saving options
		 */
		public function save_options() {
			check_ajax_referer( 's2w_action_nonce', '_s2w_nonce' );
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die();
			}
			$update_order_options                  = isset( $_POST['update_order_options'] ) ? stripslashes_deep( $_POST['update_order_options'] ) : array();
			$update_order_options_show             = isset( $_POST['update_order_options_show'] ) ? sanitize_text_field( $_POST['update_order_options_show'] ) : '';
			$settings                              = self::$settings->get_params();
			$settings['update_order_options']      = $update_order_options;
			$settings['update_order_options_show'] = $update_order_options_show;
			update_option( 's2w_params', $settings );
			wp_send_json( array(
				'status' => 'success'
			) );
		}

		/**
		 * Ajax handle for syncing orders
		 *
		 */
		public function update_orders() {
			check_ajax_referer( 's2w_action_nonce', '_s2w_nonce' );
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die();
			}
			$gmt_offset           = get_option( 'gmt_offset' );
			$order_id             = isset( $_POST['order_id'] ) ? sanitize_text_field( $_POST['order_id'] ) : '';
			$order                = wc_get_order( $order_id );
			$update_order_options = self::$settings->get_params( 'update_order_options' );
			ignore_user_abort( true );
			if ( isset( $_POST['update_order_options'] ) ) {
				$update_order_options                  = $_POST['update_order_options'];
				$settings                              = self::$settings->get_params();
				$settings['update_order_options']      = $update_order_options;
				$settings['update_order_options_show'] = isset( $_POST['update_order_options_show'] ) ? sanitize_text_field( $_POST['update_order_options_show'] ) : '';
				update_option( 's2w_params', $settings );
			}
			$update_history = array(
				'time'    => current_time( 'timestamp', true ),
				'status'  => 'error',
				'fields'  => $update_order_options,
				'message' => '',
			);
			$all_options    = self::get_supported_options();;
			$fields = array();
			foreach ( $update_order_options as $update_field ) {
				if ( key_exists( $update_field, $all_options ) ) {
					$fields[] = $all_options[ $update_field ];
				} elseif ( $update_field === 'domain' ) {
					$fields[] = 'domain';
				}

			}
			/*Only order domain without any option*/
			if ( in_array( 'domain', $fields ) ) {
				if ( $order_id ) {
					$domain                    = self::$settings->get_params( 'domain' );
					$update_history['status']  = 'success';
					$update_history['message'] = '';
					$order->update_meta_data( '_s2w_update_order_history', $update_history );
					/*compa with w2s */
					if ( empty( $order->update_meta_data( '_s2w_shopify_domain', true ) ) ) {
						$order->update_meta_data( '_s2w_shopify_domain', $domain, true );
					}
					$response           = $update_history;
					$response['time']   = date_i18n( 'F d, Y H:i:s', $response['time'] + $gmt_offset * 3600 );
					$response['fields'] = $fields;
					$order->save_meta_data();
					wp_send_json( $response );
				}
			}
			$fields_import = implode( ', ', $fields );
			if ( $order ) {
				S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE::remove_refunded_email_notification();
				$domain       = self::$settings->get_params( 'domain' );
				$access_token = self::$settings->get_params( 'access_token' );
				$api_key      = self::$settings->get_params( 'api_key' );
				$api_secret   = self::$settings->get_params( 'api_secret' );
				$shopify_id   = $order->get_meta( '_s2w_shopify_order_id', true );
				if ( $shopify_id ) {
					add_filter( 'http_request_timeout', array( $this, 'bump_request_timeout' ) );
					if ( in_array( 'date_paid', $update_order_options ) ) {
						$request = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::wp_remote_get( $domain, $access_token, $api_key, $api_secret, 'orders', false, array(
							'ids'         => $shopify_id,
							'status'      => 'any',
							'transaction' => true

						) );
					} else {
						$request = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::wp_remote_get( $domain, $access_token, $api_key, $api_secret, 'orders', false, array(
							'ids'    => $shopify_id,
							'status' => 'any'
						) );
					}

					if ( $request['status'] === 'success' ) {
						$order_data = (array) $request['data'];

						if ( count( $order_data ) ) {
							$update_which = array();
							foreach ( $all_options as $all_option_k => $all_option ) {
								$update_which[ $all_option_k ] = in_array( $all_option_k, $update_order_options );
							}
//						    remove_all_actions('woocommerce_before_order_object_save');
//						    remove_all_actions('woocommerce_after_order_object_save');
							$new_data = array();
							self::sync_order_data( $order, $order_data, $update_which, $new_data );
							$update_history['status']  = 'success';
							$update_history['message'] = '';
							$order->update_meta_data( '_s2w_update_order_history', $update_history );
							$order->save_meta_data();
							$response           = $update_history;
							$response['time']   = date_i18n( 'F d, Y H:i:s', $response['time'] + $gmt_offset * 3600 );
							$response['fields'] = $fields_import;
							do_action( 's2w_update_order_data_successfully', $order_id, $order_data );
							wp_send_json( array_merge( $response, $new_data ) );
						} else {
							$update_history['status']  = 'error';
							$update_history['message'] = esc_html__( 'Not found', 's2w-import-shopify-to-woocommerce' );
							$order->update_meta_data( '_s2w_update_order_history', $update_history );
							$order->save_meta_data();
							$response           = $update_history;
							$response['time']   = date_i18n( 'F d, Y H:i:s', $response['time'] + $gmt_offset * 3600 );
							$response['fields'] = $fields_import;
							wp_send_json( $response );
						}
					} else {
						$update_history['status']  = 'error';
						$update_history['message'] = $request['data'];
						$order->update_meta_data( '_s2w_update_order_history', $update_history );
						$order->save_meta_data();
						$response           = $update_history;
						$response['time']   = date_i18n( 'F d, Y H:i:s', $response['time'] + $gmt_offset * 3600 );
						$response['fields'] = $fields_import;
						wp_send_json( $response );
					}
				}
				$response           = $update_history;
				$response['time']   = date_i18n( 'F d, Y H:i:s', $response['time'] + $gmt_offset * 3600 );
				$response['fields'] = $fields_import;
				wp_send_json( $response );

			} else {
				wp_send_json( array(
					'status'  => 'error',
					'message' => ''
				) );
			}
		}

		/**
		 * Sync an order
		 *
		 * @param $order WC_Order
		 * @param $order_data
		 * @param $update_which
		 * @param $new_data
		 *
		 * @throws Exception
		 */
		public static function sync_order_data( $order, $order_data, $update_which, &$new_data ) {
			$gmt_offset = get_option( 'gmt_offset' );
			$order_id   = $order->get_id();
			if ( ! empty( $update_which['date_paid'] ) ) {
				$order_data        = (array) $order_data;
				$shopify_order_id  = $order->get_meta( '_s2w_shopify_order_id', true );
				$transactions_data = (array) $order_data[ 'Order_' . $shopify_order_id ] ?? [];
				$transactions_data = (array) $transactions_data['transactions'] ?? [];
				$transaction       = (array) $transactions_data[0] ?? [];
				$date_paid_data    = (array) $transaction['createdAt'] ?? [];
				$date_paid         = $date_paid_data[0] ?? '';
				$order->set_date_paid( $date_paid );
				$order->save();
			}
			$billing_address  = isset( $order_data['billing_address'] ) ? $order_data['billing_address'] : array();
			$shipping_address = isset( $order_data['shipping_address'] ) ? $order_data['shipping_address'] : array();
			$update_data      = array();
			if ( ! empty( $update_which['order_status'] ) ) {
				$order_status_mapping = self::$settings->get_params( 'order_status_mapping' );
				if ( ! is_array( $order_status_mapping ) || ! count( $order_status_mapping ) ) {
					$order_status_mapping = self::$settings->get_default( 'order_status_mapping' );
				}
				$financial_status = isset( $order_data['financial_status'] ) ? $order_data['financial_status'] : '';
				$order_status     = apply_filters( 's2w_update_order_status', isset( $order_status_mapping[ $financial_status ] ) ? $order_status_mapping[ $financial_status ] : '', $order_data, $order );

				if ( $order_status ) {
					$update_data['post_status'] = "wc-{$order_status}";
					$new_data['order_status']   = '<mark class="order-status status-' . $order_status . ' tips"><span>' . wc_get_order_status_name( $order_status ) . '</span></mark>';
				}
			}
			if ( ! empty( $update_which['order_date'] ) ) {
				$processed_at = apply_filters( 's2w_import_order_created_date', $order_data['processed_at'], $order_data );
				if ( $processed_at ) {
					$processed_at_gmt                 = strtotime( $processed_at );
					$date_gmt                         = date( 'Y-m-d H:i:s', $processed_at_gmt );
					$date                             = date( 'Y-m-d H:i:s', ( $processed_at_gmt + $gmt_offset * 3600 ) );
					$update_data['post_date']         = $date;
					$update_data['post_date_gmt']     = $date_gmt;
					$update_data['post_modified']     = $date;
					$update_data['post_modified_gmt'] = $date_gmt;
					$new_data['order_date']           = '<time datetime="' . date_i18n( 'Y-m-d\TH:i:s', strtotime( $date ) ) . '+00:00' . '" title="' . date_i18n( 'M d, Y h:i A', strtotime( $date ) ) . '">' . date_i18n( 'M d, Y', strtotime( $date ) ) . '</time>';
				}
			}
			if ( ! empty( $update_which['fulfillments'] ) ) {
				$shopify_order_fulfillments = isset( $order_data['fulfillments'] ) ? $order_data['fulfillments'] : array();
				if ( $shopify_order_fulfillments ) {
					$order->update_meta_data( '_s2w_shopify_order_fulfillments', $shopify_order_fulfillments );
					$order_tracking_data = [];
					foreach ( $shopify_order_fulfillments as $shopify_order_fulfillment ) {
						if ( empty( $shopify_order_fulfillment['tracking_company'] ) ) {
							continue;
						}
						foreach ( $shopify_order_fulfillment['line_items'] as $line_item ) {
							$order_tracking_data[] = [
								'title'            => $line_item['name'],
								'carrier'          => $shopify_order_fulfillment['tracking_company'],
								'tracking_numbers' => $shopify_order_fulfillment['tracking_numbers'],
							];
						}
					}

					do_action( 's2w_update_fulfillment', $order, $order_tracking_data );
				}
			}
			if ( ! empty( $update_which['line_items'] ) ) {
				$product_line_items  = array_keys( $order->get_items( 'line_item' ) );
				$shipping_line_items = array_keys( $order->get_items( 'shipping' ) );
				$tax_line_items      = array_keys( $order->get_items( 'tax' ) );
				$coupon_line_items   = array_keys( $order->get_items( 'coupon' ) );
				$order_total         = $order_data['total_price'];
				$order_total_tax     = $order_data['total_tax'];
				$total_discounts     = $order_data['total_discounts'];
				$total_shipping      = isset( $order_data['total_shipping_price_set']['shop_money']['amount'] ) ? floatval( $order_data['total_shipping_price_set']['shop_money']['amount'] ) : 0;
				$shipping_lines      = $order_data['shipping_lines'];
				$discount_codes      = $order_data['discount_codes'];
				$order->set_currency( ! empty( $order_data['currency'] ) ? $order_data['currency'] : get_woocommerce_currency() );
				$order->set_prices_include_tax( $order_data['taxes_included'] );
				$order->set_shipping_total( $total_shipping );
				$order->set_discount_total( $total_discounts );
				//      set discount tax
				$order->set_cart_tax( $order_total_tax );
				if ( isset( $shipping_lines['tax_lines']['price'] ) && $shipping_lines['tax_lines']['price'] ) {
					$order->set_shipping_tax( $shipping_lines['tax_lines']['price'] );
				}
				$order->set_total( $order_total );

				/*create order line items*/
				$line_items     = $order_data['line_items'];
				$line_items_ids = array();
				self::remove_order_item( $product_line_items, $line_items, $order );
				foreach ( $line_items as $line_item_k => $line_item ) {
					$item                 = isset( $product_line_items[ $line_item_k ] ) ? new WC_Order_Item_Product( $product_line_items[ $line_item_k ] ) : new WC_Order_Item_Product();
					$shopify_product_id   = $line_item['product_id'];
					$shopify_variation_id = $line_item['variant_id'];
					$sku                  = $line_item['sku'];
					$product_id           = '';
					if ( $shopify_variation_id ) {
						$found_variation_id = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::product_get_woo_id_by_shopify_id( $shopify_variation_id, true );
						if ( $found_variation_id ) {
							$product_id = $found_variation_id;
						}
					}
					if ( ! $product_id && $shopify_product_id ) {
						$found_product_id = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::product_get_woo_id_by_shopify_id( $shopify_product_id );
						if ( $found_product_id ) {
							$product_id = $found_product_id;
						}
					}
					if ( ! $product_id && $sku ) {
						$product_id = wc_get_product_id_by_sku( $sku );
					}
					$item->set_props(
						array(
							'quantity' => $line_item['quantity'],
							'subtotal' => $line_item['price'],
							'total'    => intval( $line_item['quantity'] ) * $line_item['price'],
							'name'     => $line_item['name'],
						)
					);
					if ( is_array( $line_item['tax_lines'] ) && count( $line_item['tax_lines'] ) ) {
						$line_item_tax = 0;
						$taxes         = array(
							'subtotal' => array(),
							'total'    => array(),
						);
						foreach ( $line_item['tax_lines'] as $line_item_tax_line ) {
							$line_item_tax       += floatval( $line_item_tax_line['price'] );
							$taxes['subtotal'][] = $line_item_tax_line['price'];
							$taxes['total'][]    = $line_item_tax_line['price'];
						}
						$item->set_props(
							array(
								'subtotal_tax' => $line_item_tax,
								'total_tax'    => $line_item_tax,
								'taxes'        => $taxes,
							)
						);
					}
					if ( $product_id ) {
						$product = wc_get_product( $product_id );
						if ( $product ) {
							$item->set_props(
								array(
									'name'         => $product->get_name(),
									'tax_class'    => $product->get_tax_class(),
									'product_id'   => $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id(),
									'variation_id' => $product->is_type( 'variation' ) ? $product->get_id() : 0,
									'variation'    => $product->is_type( 'variation' ) ? $product->get_attributes() : array(),
								)
							);
						}
					}
					$item_id = $item->save();
					$order->add_item( $item );
					$line_items_ids[ $item_id ] = $line_item['id'];
				}
				//create order shipping line
				$shipping_line_items_count = count( $shipping_line_items );
				if ( $shipping_line_items_count > 1 ) {
					$item = new WC_Order_Item_Shipping( $shipping_line_items[0] );
					for ( $temp = 1; $temp < $shipping_line_items_count; $temp ++ ) {
						$order->remove_item( $shipping_line_items[ $temp ] );
					}
				} elseif ( $shipping_line_items_count > 0 ) {
					$item = new WC_Order_Item_Shipping( $shipping_line_items[0] );
				} else {
					$item = new WC_Order_Item_Shipping();
				}
				if ( is_array( $shipping_lines ) && count( $shipping_lines ) ) {
					foreach ( $shipping_lines as $shipping_line ) {
						$item->set_props(
							array(
								'method_title' => $shipping_line['title'],
								'method_id'    => floatval( $shipping_line['price'] ) > 0 ? 'flat_rate' : 'free_shipping',
								'total'        => $shipping_line['price'],
							)
						);
						if ( is_array( $shipping_line['tax_lines'] ) && count( $shipping_line['tax_lines'] ) ) {
							$shipping_line_tax = array();
							foreach ( $shipping_line['tax_lines'] as $shipping_line_tax_line ) {
								$shipping_line_tax[] = $shipping_line_tax_line['price'];
							}
							$item->set_props(
								array(
									'taxes' => array( 'total' => $shipping_line_tax ),
								)
							);
						}
					}
				} else {
					$item->set_props(
						array(
							'method_title' => isset( $shipping_lines[0]['title'] ) ? $shipping_lines[0]['title'] : ( $total_shipping ? 'Flat rate' : 'Free shipping' ),
							'method_id'    => $total_shipping ? 'flat_rate' : 'free_shipping',
							'total'        => $total_shipping,
						)
					);
				}
				$shipping_lines_id = $item->save();
				$order->add_item( $item );
//				create order tax lines
				$tax_lines = $order_data['tax_lines'];
				self::remove_order_item( $tax_line_items, $tax_lines, $order );
				if ( is_array( $tax_lines ) && count( $tax_lines ) ) {
					foreach ( $tax_lines as $tax_line_k => $tax_line ) {
						$item = isset( $tax_line_items[ $tax_line_k ] ) ? new WC_Order_Item_Tax( $tax_line_items[ $tax_line_k ] ) : new WC_Order_Item_Tax();
						$item->set_props(
							array(
								'tax_total' => $tax_line['price'],
								'label'     => $tax_line['title'],
							)
						);
						$item->save();
						$order->add_item( $item );
					}
				}

//				create order coupon lines
				self::remove_order_item( $coupon_line_items, $discount_codes, $order );
				if ( is_array( $discount_codes ) && count( $discount_codes ) ) {
					foreach ( $discount_codes as $discount_code_k => $discount_code ) {
						$item = isset( $coupon_line_items[ $discount_code_k ] ) ? new WC_Order_Item_Coupon( $coupon_line_items[ $discount_code_k ] ) : new WC_Order_Item_Coupon();
						$item->set_props(
							array(
								'code'     => $discount_code['code'],
								'discount' => $discount_code['amount'],
							)
						);
						$item->save();
						$order->add_item( $item );
					}
				}
				$refund_items = $order->get_refunds();
				$refunds      = $order_data['refunds'];
				S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE::process_refunds( $refund_items, $refunds, $order_id, $line_items_ids, $shipping_lines_id );
				$order->save();
			}
			if ( count( $update_data ) ) {
				$update_data['ID'] = $order_id;
				wp_update_post( $update_data );
			}
			$data = array();
			if ( ! empty( $update_which['billing_address'] ) && $billing_address ) {
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
					'billing_email'      => S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE::get_billing_email( $order_data ),
				), $data );
			}

			if ( ! empty( $update_which['shipping_address'] ) && $shipping_address ) {
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
			if ( ! empty( $update_which['customer'] ) ) {
				$customer_id = S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE::get_customer_id( $order_data );
				if ( $customer_id ) {
					$order->set_customer_id( $customer_id );
					$order->save();
				}
			}
			if ( count( $data ) ) {
				foreach ( $data as $key => $value ) {
					if ( is_callable( array( $order, "set_{$key}" ) ) ) {
						$order->{"set_{$key}"}( $value );
						// Store custom fields prefixed with wither shipping_ or billing_. This is for backwards compatibility with 2.6.x.
					} elseif ( isset( $fields_prefix[ current( explode( '_', $key ) ) ] ) ) {
						if ( ! isset( $shipping_fields[ $key ] ) ) {
							$order->update_meta_data( '_' . $key, $value );
						}
					}
				}
				$order->save();
			}
		}

		/**
		 * Remove line items to reimport
		 *
		 * @param $current_line_items
		 * @param $line_items
		 * @param $order WC_Order
		 */
		public static function remove_order_item( $current_line_items, $line_items, &$order ) {
			if ( count( $current_line_items ) > $line_items_count = count( $line_items ) ) {
				$removed_items = array_splice( $current_line_items, $line_items_count );
				foreach ( $removed_items as $item_id ) {
					$order->remove_item( $item_id );
				}
			}
		}

		/**
		 * @param $val
		 *
		 * @return bool|mixed|void
		 */
		public function bump_request_timeout( $val ) {
			return self::$settings->get_params( 'request_timeout' );
		}

		/**
		 *
		 */
		public function admin_enqueue_script() {
			global $pagenow;
			$post_type = isset( $_REQUEST['post_type'] ) ? sanitize_text_field( $_REQUEST['post_type'] ) : '';
			$page      = isset( $_REQUEST['page'] ) ? sanitize_text_field( $_REQUEST['page'] ) : '';

			if ( ( $pagenow === 'edit.php' && $post_type === 'shop_order' ) || ( $page === 'wc-orders' ) ) {
				wp_enqueue_script( 's2w-html-scroll-handler', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_JS . 'html-scroll-handler.js', array( 'jquery' ), VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_VERSION );
				wp_enqueue_style( 's2w-import-shopify-to-woocommerce-update-order', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'update-orders.css' );
				wp_enqueue_script( 's2w-import-shopify-to-woocommerce-update-order', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_JS . 'update-orders.js', array( 'jquery' ), VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_VERSION );
				wp_localize_script( 's2w-import-shopify-to-woocommerce-update-order', 's2w_params_admin_update_orders', array(
					'url'                       => admin_url( 'admin-ajax.php' ),
					'update_order_options'      => self::$settings->get_params( 'update_order_options' ),
					'update_order_options_show' => self::$settings->get_params( 'update_order_options_show' ),
					'_s2w_nonce'                => wp_create_nonce( 's2w_action_nonce' ),
				) );
				add_action( 'admin_footer', array( $this, 'wp_footer' ) );
			}
		}

		/**
		 * Currently supported options
		 *
		 * @return array
		 */
		public static function get_supported_options() {
			return array(
				'order_status'     => esc_html__( 'Order status', 's2w-import-shopify-to-woocommerce' ),
				'order_date'       => esc_html__( 'Order date', 's2w-import-shopify-to-woocommerce' ),
				'fulfillments'     => esc_html__( 'Order fulfillments', 's2w-import-shopify-to-woocommerce' ),
				'billing_address'  => esc_html__( 'Billing address', 's2w-import-shopify-to-woocommerce' ),
				'shipping_address' => esc_html__( 'Shipping address', 's2w-import-shopify-to-woocommerce' ),
				'line_items'       => esc_html__( 'Line items', 's2w-import-shopify-to-woocommerce' ),
				'customer'         => esc_html__( 'Customer', 's2w-import-shopify-to-woocommerce' ),
				'date_paid'        => esc_html__( 'Date paid', 's2w-import-shopify-to-woocommerce' ),
			);
		}

		/**
		 * Sync popup
		 */
		public function wp_footer() {
			$all_options    = self::get_supported_options();
			$update_options = self::$settings->get_params( 'update_order_options' );
			?>
            <div class="<?php echo esc_attr( self::set( array(
				'update-order-options-container',
				'hidden'
			) ) ) ?>">
				<?php wp_nonce_field( 's2w_update_order_options_action_nonce', '_s2w_update_order_options_nonce' ) ?>
                <div class="<?php echo esc_attr( self::set( 'overlay' ) ) ?>"></div>
                <div class="<?php echo esc_attr( self::set( 'update-order-options-content' ) ) ?>">
                    <div class="<?php echo esc_attr( self::set( 'update-order-options-content-header' ) ) ?>">
                        <h2><?php esc_html_e( 'Sync options', 's2w-import-shopify-to-woocommerce' ) ?></h2>
                        <span class="<?php echo esc_attr( self::set( 'update-order-options-close' ) ) ?>"></span>
                    </div>
                    <div class="<?php echo esc_attr( self::set( 'update-order-options-content-body' ) ) ?>">
						<?php
						foreach ( $all_options as $option_key => $option_value ) {
							?>
                            <div class="<?php echo esc_attr( self::set( 'update-order-options-content-body-row' ) ) ?>">
                                <div class="<?php echo esc_attr( self::set( 'update-order-options-option-wrap' ) ) ?>">
                                    <input type="checkbox" value="1"
                                           data-order_option="<?php echo esc_attr( $option_key ) ?>"
										<?php if ( in_array( $option_key, $update_options ) ) {
											echo esc_attr( 'checked' );
										} ?>
                                           id="<?php echo esc_attr( self::set( 'update-order-options-' . $option_key ) ) ?>"
                                           class="<?php echo esc_attr( self::set( 'update-order-options-option' ) ) ?>">
                                    <label for="<?php echo esc_attr( self::set( 'update-order-options-' . $option_key ) ) ?>"><?php echo esc_html( $option_value ) ?></label>
                                </div>
                            </div>
							<?php
						}

						?>
						<?php
						if ( class_exists( 'Viw2s_Pro' ) ) {
							?>
                            <div class="<?php echo esc_attr( 's2w-update-order-options-content-body-row s2w-update-compa-w2s' ) ?>">
                                <div class="<?php echo esc_attr( 's2w-update-order-options-option-wrap' ) ?>">
                                    <input type="checkbox" value="1"
                                           data-order_option="domain"
                                           id="<?php echo esc_attr( 's2w-update-order-options-domain' ); ?>"
                                           class="<?php echo esc_attr( 's2w-update-order-options-option' ); ?>">
                                    <label for="<?php echo esc_attr( 's2w-update-order-options-domain' ); ?>"><?php echo esc_html( 'Sync Domain ' ) ?></label>
                                    <div class="<?php echo esc_attr( 's2w-option-description' ) ?>"> <?php esc_html_e( 'Enable this option to sync product/order data imported from the previous version 1.2', 's2w-import-shopify-to-woocommerce' ) ?></div>
                                </div>
                            </div>
							<?php
						}
						?>
                    </div>
                    <div class="<?php echo esc_attr( self::set( 'update-order-options-content-body-1' ) ) ?>">
                        <div class="<?php echo esc_attr( self::set( 'update-order-options-content-body-row' ) ) ?>">
                            <input type="checkbox" value="1"
								<?php checked( '1', self::$settings->get_params( 'update_order_options_show' ) ) ?>
                                   id="<?php echo esc_attr( self::set( 'update-order-options-show' ) ) ?>"
                                   class="<?php echo esc_attr( self::set( 'update-order-options-show' ) ) ?>">
                            <label for="<?php echo esc_attr( self::set( 'update-order-options-show' ) ) ?>"><?php esc_html_e( 'Show these options when clicking on "Sync" button on each order', 's2w-import-shopify-to-woocommerce' ) ?></label>
                        </div>
                    </div>
                    <div class="<?php echo esc_attr( self::set( 'update-order-options-content-footer' ) ) ?>">
                        <span class="button-primary <?php echo esc_attr( self::set( array(
	                        'update-order-options-button-save',
	                        'button',
	                        'hidden'
                        ) ) ) ?>">
                            <?php esc_html_e( 'Save', 's2w-import-shopify-to-woocommerce' ) ?>
                        </span>
                        <span class="button-primary <?php echo esc_attr( self::set( array(
							'update-order-options-button-update',
							'button',
							'hidden'
						) ) ) ?>">
                            <?php esc_html_e( 'Sync selected', 's2w-import-shopify-to-woocommerce' ) ?>(<span
                                    class="<?php echo esc_attr( self::set( 'selected-number' ) ) ?>">0</span>)
                        </span>
                        <span class="button-primary <?php echo esc_attr( self::set( array(
							'update-order-options-button-update-single',
							'button',
							'hidden'
						) ) ) ?>" data-update_order_id="">
                            <?php esc_html_e( 'Sync', 's2w-import-shopify-to-woocommerce' ) ?>
                        </span>
                        <span class="<?php echo esc_attr( self::set( array(
							'update-order-options-button-cancel',
							'button'
						) ) ) ?>">
                            <?php esc_html_e( 'Cancel', 's2w-import-shopify-to-woocommerce' ) ?>
                        </span>
                    </div>
                </div>
                <div class="<?php echo esc_attr( self::set( 'saving-overlay' ) ) ?>"></div>
            </div>
			<?php
		}
	}
}