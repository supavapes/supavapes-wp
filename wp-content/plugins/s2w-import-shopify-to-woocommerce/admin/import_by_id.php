<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Import_By_Id' ) ) {
	class S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Import_By_Id {
		protected static $settings;
		protected $process_single_new;

		public function __construct() {
			self::$settings = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_instance();
			add_action( 'admin_menu', array( $this, 'admin_menu' ), 18 );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 1 );
			add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
			add_action( 'wp_ajax_s2w_import_shopify_to_woocommerce_by_id', array( $this, 'import_by_id' ) );
		}

		/**
		 *
		 */
		public function admin_enqueue_scripts() {
			global $pagenow;
			$page = isset( $_REQUEST['page'] ) ? sanitize_text_field( $_REQUEST['page'] ) : '';
			if ( $pagenow === 'admin.php' && $page === 's2w-import-shopify-to-woocommerce-import-by-id' ) {
				S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE::enqueue_3rd_library( array(
					'accordion',
					'button',
					'checkbox',
					'dropdown',
					'form',
					'input',
					'label',
					'icon',
					'segment',
				) );
				wp_enqueue_style( 's2w-import-shopify-to-woocommerce-import-by-id', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'import-by-id.css', '', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_VERSION );
				wp_enqueue_script( 's2w-import-shopify-to-woocommerce-import-by-id', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_JS . 'admin-import-by-id.js', array( 'jquery' ), VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_VERSION );
				wp_localize_script( 's2w-import-shopify-to-woocommerce-import-by-id', 's2w_params_admin_import_by_id', array(
					'url'              => admin_url( 'admin-ajax.php' ),
					'_s2w_nonce'       => wp_create_nonce( 's2w_action_nonce' ),
					'i18n_empty_alert' => esc_html__( 'Please enter ID(s) of Shopify items that you want to import', 's2w-import-shopify-to-woocommerce' ),
				) );
			}
		}

		/**
		 * @param $name
		 * @param bool $set_name
		 *
		 * @return string
		 */
		protected static function set( $name, $set_name = false ) {
			return VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::set( $name, $set_name );
		}

		/**
		 *
		 */
		public function plugins_loaded() {
			$this->process_single_new = new WP_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_Process_Single_New();
			if ( ! empty( $_REQUEST['s2w_cancel_download_image_single'] ) ) {
				$this->process_single_new->kill_process();
				wp_safe_redirect( @remove_query_arg( 's2w_cancel_download_image_single' ) );
				exit;
			}
		}

		/**
		 *
		 */
		public function admin_menu() {
			add_submenu_page(
				's2w-import-shopify-to-woocommerce',
				esc_html__( 'Import by ID', 's2w-import-shopify-to-woocommerce' ),
				esc_html__( 'Import by ID', 's2w-import-shopify-to-woocommerce' ),
				self::get_required_capability(),
				's2w-import-shopify-to-woocommerce-import-by-id',
				array( $this, 'page_callback_import_by_id' )
			);
		}

		/**
		 *
		 */
		public function page_callback_import_by_id() {
			?>
            <div class="wrap">
                <h2><?php esc_html_e( 'Import products, orders or customers by ID', 's2w-import-shopify-to-woocommerce' ) ?></h2>
                <div class="vi-ui form">
                    <div class="vi-ui segment">
                        <div class="vi-ui labeled fluid input">
                            <div class="vi-ui left label">
                                <select id="<?php echo esc_attr( self::set( 'import-item-type' ) ) ?>"
                                        class="vi-ui fluid dropdown">
									<?php
									foreach (
										array(
											'products'  => esc_html__( 'Products', 's2w-import-shopify-to-woocommerce' ),
											'orders'    => esc_html__( 'Orders', 's2w-import-shopify-to-woocommerce' ),
											'customers' => esc_html__( 'Customers', 's2w-import-shopify-to-woocommerce' ),
										) as $key => $value
									) {
										?>
                                        <option value="<?php echo esc_attr( $key ) ?>"><?php echo esc_html( $value ) ?></option>
										<?php
									}
									?>
                                </select>
                            </div>
                            <input type="text" id="<?php echo esc_attr( self::set( 'shopify-item-id' ) ) ?>">
                        </div>
                        <p><?php esc_html_e( 'Enter ids of Shopify items separated by "," to import.', 's2w-import-shopify-to-woocommerce' ) ?></p>
                        <p>
                            <span class="vi-ui labeled icon positive button tiny <?php echo esc_attr( self::set( 'button-import' ) ) ?>"><i
                                        class="icon cloud download"></i><?php esc_html_e( 'Import', 's2w-import-shopify-to-woocommerce' ) ?></span>
                        </p>
                    </div>
                    <div class="vi-ui segment <?php echo esc_attr( self::set( 'import-message' ) ) ?>">
                    </div>
                </div>
            </div>
			<?php
		}

		/**
		 * Import by ID - ajax handler
		 */
		public function import_by_id() {
			check_ajax_referer( 's2w_action_nonce', '_s2w_nonce' );
			if ( ! current_user_can( self::get_required_capability() ) ) {
				wp_die();
			}
			$item_id     = isset( $_POST['item_id'] ) ? sanitize_text_field( $_POST['item_id'] ) : '';
			$import_type = isset( $_POST['import_type'] ) ? sanitize_text_field( $_POST['import_type'] ) : 'products';
			$item_ids    = array();
			if ( $item_id ) {
				$item_ids = explode( ',', $item_id );
			}
			$item_ids = array_map( 'absint', $item_ids );
			$item_ids = array_filter( $item_ids );
			if ( ! is_array( $item_ids ) || ! count( $item_ids ) ) {
				wp_send_json( array(
					'status'  => 'error',
					'message' => '<p>' . esc_html__( 'Please enter valid Shopify item ID(s)', 's2w-import-shopify-to-woocommerce' ) . '</p>',
				) );
			}
			$domain       = self::$settings->get_params( 'domain' );
			$access_token = self::$settings->get_params( 'access_token' );
			$api_key      = self::$settings->get_params( 'api_key' );
			$api_secret   = self::$settings->get_params( 'api_secret' );
			$path         = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_cache_path( $domain, $access_token, $api_key, $api_secret ) . '/';
			$log_file     = $path . 'import_by_id_logs.txt';
			VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::create_cache_folder( $path );
			$message = '';
			switch ( $import_type ) {
				case 'customers':
					foreach ( $item_ids as $current_import_id ) {
						$existing_id = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::customer_get_id_by_shopify_id( $current_import_id );
						if ( $existing_id ) {
							$message      .= '<p>' . esc_html__( 'Customer exists ', 's2w-import-shopify-to-woocommerce' ) . '<strong>' . $current_import_id . '</strong><a target="_blank" href="' . esc_url( admin_url( 'user-edit.php?user_id=' . $existing_id ) ) . '">' . esc_html__( ' View', 's2w-import-shopify-to-woocommerce' ) . '</a></p>';
							$logs_content = "#{$current_import_id}: " . esc_html__( 'Skip because customer exists', 's2w-import-shopify-to-woocommerce' ) . ", WC customer ID: #{$existing_id}";
							S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, $logs_content );
							continue;
						}
						$request = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::wp_remote_get( $domain, $access_token, $api_key, $api_secret, $import_type, false, array( 'ids' => $current_import_id ) );
						if ( $request['status'] !== 'success' ) {
							$message      .= '<p>' . $request['data'] . ' <strong>' . $current_import_id . '</strong></p>';
							$logs_content = "Error: {$request['data']}, Shopify customer ID: {$current_import_id}";
							S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, $logs_content );
							continue;
						}
						$data = $request['data'];
						if ( ! count( $data ) ) {
							$message      .= '<p>No data<strong> ' . $current_import_id . '</strong></p>';
							$logs_content = "Error: No data, Shopify customer ID: {$current_import_id}";
							S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, $logs_content );
							continue;
						}
						$user_id = S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE::import_customer( $data );
						if ( is_wp_error( $user_id ) ) {
							$message      .= '<p>' . $user_id->get_error_message() . '<strong> ' . $current_import_id . '</strong></p>';
							$logs_content = "Error: " . $user_id->get_error_message() . ", Shopify customer ID: {$current_import_id}";
							S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, $logs_content );
							continue;
						}
						$message      .= '<p>' . esc_html__( 'Successfully import ', 's2w-import-shopify-to-woocommerce' ) . '<strong>' . $current_import_id . '</strong>: <a target="_blank" href="' . esc_url( admin_url( 'user-edit.php?user_id=' . $user_id ) ) . '">' . esc_html__( ' View', 's2w-import-shopify-to-woocommerce' ) . '</a></p>';
						$logs_content = "#{$current_import_id}: " . esc_html__( 'Import successfully', 's2w-import-shopify-to-woocommerce' ) . ", WC user ID: #{$user_id}";
						S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, $logs_content );
						do_action( 's2w_bulk_import_item_successfully', $user_id, $import_type, $data );
					}
					break;
				case 'orders':
					S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE::remove_refunded_email_notification();
					foreach ( $item_ids as $current_import_id ) {
						$existing_id = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::query_get_id_by_shopify_id( $current_import_id, 'order' );
						if ( $existing_id ) {
							$message      .= '<p>' . esc_html__( 'Order exists ', 's2w-import-shopify-to-woocommerce' ) . '<strong>' . $current_import_id . '</strong><a target="_blank" href="' . esc_url( admin_url( 'post.php?post=' . $existing_id . '&action=edit' ) ) . '">' . esc_html__( ' View', 's2w-import-shopify-to-woocommerce' ) . '</a></p>';
							$logs_content = "#{$current_import_id}: " . esc_html__( 'Skip because order exists', 's2w-import-shopify-to-woocommerce' ) . ", WC order ID: #{$existing_id}";
							S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, $logs_content );
							continue;
						}
						$request = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::wp_remote_get( $domain, $access_token, $api_key, $api_secret, $import_type, false, array( 'ids' => $current_import_id ) );
						if ( $request['status'] !== 'success' ) {
							$message      .= '<p>' . $request['data'] . ' <strong>' . $current_import_id . '</strong></p>';
							$logs_content = "Error: {$request['data']}, Shopify order ID: " . $current_import_id;
							S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, $logs_content );
							continue;
						}
						$data = $request['data'];
						if ( ! count( $data ) ) {
							$message      .= '<p>No data<strong> ' . $current_import_id . '</strong></p>';
							$logs_content = "Error: No data, Shopify order ID: " . $current_import_id;
							S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, $logs_content );
							continue;
						}
						$order_id     = S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE::import_order( $data );
						$message      .= '<p>' . esc_html__( 'Successfully import ', 's2w-import-shopify-to-woocommerce' ) . '<strong>' . $current_import_id . '</strong>: <a target="_blank" href="' . esc_url( admin_url( 'post.php?post=' . $order_id . '&action=edit' ) ) . '">' . esc_html__( ' View', 's2w-import-shopify-to-woocommerce' ) . '</a></p>';
						$logs_content = "#{$current_import_id}: " . esc_html__( 'Import successfully', 's2w-import-shopify-to-woocommerce' ) . ", WC order ID: #{$order_id}";
						S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, $logs_content );
						do_action( 's2w_bulk_import_item_successfully', $order_id, $import_type, $data );
					}
					break;
				case 'products':
					$download_images        = self::$settings->get_params( 'download_images' );
					$keep_slug              = self::$settings->get_params( 'keep_slug' );
					$product_status         = self::$settings->get_params( 'product_status' );
					$product_status_mapping = self::$settings->get_params( 'product_status_mapping' );
					$product_categories     = self::$settings->get_params( 'product_categories' );
					$global_attributes      = self::$settings->get_params( 'global_attributes' );
					$variable_sku           = self::$settings->get_params( 'variable_sku' );
					$product_author         = self::$settings->get_params( 'product_author' );
					if ( $product_author && ( $product_author == get_current_user_id() || ! get_user_by( 'id', $product_author ) ) ) {
						$product_author = '';
					}

					foreach ( $item_ids as $current_import_id ) {
						$existing_id = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::product_get_woo_id_by_shopify_id( $current_import_id );
						if ( $existing_id ) {
							$message      .= '<p>' . esc_html__( 'Product exists ', 's2w-import-shopify-to-woocommerce' ) . '<strong>' . $current_import_id . '</strong><a target="_blank" href="' . esc_url( admin_url( 'post.php?post=' . $existing_id . '&action=edit' ) ) . '">' . esc_html__( ' View', 's2w-import-shopify-to-woocommerce' ) . '</a></p>';
							$title        = get_the_title( $existing_id );
							$logs_content = "[{$current_import_id}]{$title}: " . esc_html__( 'Skip because product exists', 's2w-import-shopify-to-woocommerce' ) . ", WC product ID: {$existing_id}";
							S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, $logs_content );
							continue;
						}
						$request = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::wp_remote_get( $domain, $access_token, $api_key, $api_secret, $import_type, false, array( 'ids' => $current_import_id ) );
						if ( $request['status'] !== 'success' ) {
							$message      .= '<p>' . $request['data'] . ' <strong>' . $current_import_id . '</strong></p>';
							$logs_content = "Error: {$request['data']}, Shopify product ID: " . $current_import_id;
							S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, $logs_content );
							continue;
						}
						$data = $request['data'];
						if ( ! count( $data ) ) {
							$message      .= '<p>No data<strong> ' . $current_import_id . '</strong></p>';
							$logs_content = "Error: No data, Shopify product ID: " . $current_import_id;
							S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, $logs_content );
							continue;
						}
						if ( ! empty( $product_status_mapping[ $data['status'] ] ) ) {
							$product_status = $product_status_mapping[ $data['status'] ];
						}
						if ( $product_status === 'not_import' ) {
							continue;
						}
						$sku     = str_replace( array(
							'{shopify_product_id}',
							'{product_slug}'
						), array( $current_import_id, $data['handle'] ), $variable_sku );
						$sku     = str_replace( ' ', '', $sku );
						$options = isset( $data['options'] ) ? $data['options'] : array();
						if ( VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::sku_exists( $sku ) ) {
							$sku = '';
						}
						if ( is_array( $options ) && count( $options ) ) {
							$dispatch    = false;
							$images_d    = array();
							$description = isset( $data['body_html'] ) ? html_entity_decode( $data['body_html'], ENT_QUOTES | ENT_XML1, 'UTF-8' ) : '';
							$product_id  = S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE::import_product( $options, $data, $sku, $global_attributes, $product_status, $keep_slug, $download_images, $product_categories, $images_d, $product_author );
							if ( ! is_wp_error( $product_id ) ) {
								S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE::handle_description_images( $description, $product_id, $dispatch, $this->process_single_new );
								if ( count( $images_d ) ) {
									if ( self::$settings->get_params( 'disable_background_process' ) ) {
										foreach ( $images_d as $images_d_k => $images_d_v ) {
											S2W_Error_Images_Table::insert( $product_id, implode( ',', $images_d_v['product_ids'] ), $images_d_v['src'], $images_d_v['alt'], intval( $images_d_v['set_gallery'] ), $images_d_v['id'] );
										}
									} else {
										$dispatch = true;
										foreach ( $images_d as $images_d_k => $images_d_v ) {
											$this->process_single_new->push_to_queue( $images_d_v );
										}
									}
								}
								if ( $dispatch ) {
									$this->process_single_new->save()->dispatch();
								}
								$message      .= '<p>' . esc_html__( 'Successfully import ', 's2w-import-shopify-to-woocommerce' ) . '<strong>' . $current_import_id . '</strong>: <a target="_blank" href="' . esc_url( admin_url( 'post.php?post=' . $product_id . '&action=edit' ) ) . '">' . esc_html__( ' View', 's2w-import-shopify-to-woocommerce' ) . '</a></p>';
								$logs_content = "[{$current_import_id}]{$data['title']}: " . esc_html__( 'Import successfully', 's2w-import-shopify-to-woocommerce' ) . ", WC product ID: {$product_id}";
								S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, $logs_content );
								do_action( 's2w_bulk_import_item_successfully', $product_id, $import_type, $data );
							}
						}

					}
					break;
			}
			wp_send_json( array(
				'status'  => 'success',
				'message' => $message,
			) );
		}

		/**
		 * Required capability
		 *
		 * @return mixed|void
		 */
		private static function get_required_capability() {
			return apply_filters( 'vi_s2w_admin_sub_menu_capability', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_required_capability( 'import_by_id' ), 's2w-import-shopify-to-woocommerce-import-by-id' );
		}
	}
}
