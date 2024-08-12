<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Webhooks' ) ) {
	class S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Webhooks {
		protected static $settings;
		protected $process;
		protected $webhook_name;

		public function __construct() {
			self::$settings     = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_instance();
			$this->webhook_name = '';
			add_action( 'admin_menu', array( $this, 'admin_menu' ), 16 );
			add_action( 'admin_init', array( $this, 'save_options' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
			add_action( 'rest_api_init', array( $this, 'register_api' ) );
		}

		/**
		 * Save settings
		 */
		public function save_options() {
			global $s2w_settings;
			if ( ! current_user_can( self::get_required_capability() ) ) {
				return;
			}
			if ( ! isset( $_POST['s2w_save_webhooks_options'] ) ) {
				return;
			}
			if ( ! isset( $_POST['_s2w_nonce'] ) || ! wp_verify_nonce( $_POST['_s2w_nonce'], 's2w_action_nonce' ) ) {
				return;
			}
			$args = self::$settings->get_default_webhooks_params();
			S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE::fill_params_from_post( $args, 's2w_' );
			$args = array_merge( self::$settings->get_params(), $args );
			update_option( 's2w_params', $args );
			$s2w_settings   = $args;
			self::$settings = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_instance( true );
		}

		/**
		 *
		 */
		public function admin_enqueue_scripts() {
			global $pagenow;
			$page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
			if ( $pagenow === 'admin.php' && $page === 's2w-import-shopify-to-woocommerce-webhooks' ) {
				S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE::enqueue_3rd_library( array(
					'accordion',
					'menu',
					'progress',
					'tab',
					'step',
					'sortable',
				), true );
				wp_enqueue_style( 's2w-import-shopify-to-woocommerce-webhooks', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'webhooks.css' );
				wp_enqueue_script( 's2w-import-shopify-to-woocommerce-webhooks', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_JS . 'webhooks.js', array( 'jquery' ), VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_VERSION );
			}
		}

		public function admin_menu() {
			add_submenu_page( 's2w-import-shopify-to-woocommerce',
				esc_html__( 'Webhooks', 's2w-import-shopify-to-woocommerce' ),
				esc_html__( 'Webhooks', 's2w-import-shopify-to-woocommerce' ),
				self::get_required_capability(), 's2w-import-shopify-to-woocommerce-webhooks', array(
					$this,
					'page_callback'
				) );
		}

		/**
		 *
		 */
		public function page_callback() {
			?>
            <div class="wrap">
                <h2><?php esc_html_e( 'Webhooks', 's2w-import-shopify-to-woocommerce' ) ?></h2>
				<?php S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE::security_recommendation_html(); ?>
                <p></p>
                <form class="vi-ui form" method="post">
					<?php wp_nonce_field( 's2w_action_nonce', '_s2w_nonce' ); ?>
                    <div class="vi-ui segment">
                        <table class="form-table">
                            <tbody>
                            <tr>
                                <th>
                                    <label for="<?php echo esc_attr( self::set( 'webhooks_shared_secret' ) ) ?>"><?php esc_html_e( 'Webhooks shared secret', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                </th>
                                <td>
                                    <input type="text"
                                           class="<?php echo esc_attr( self::set( 'webhooks_shared_secret' ) ) ?>"
                                           name="<?php echo esc_attr( self::set( 'webhooks_shared_secret', true ) ) ?>"
                                           id="<?php echo esc_attr( self::set( 'webhooks_shared_secret' ) ) ?>"
                                           value="<?php echo esc_attr( htmlentities( self::$settings->get_params( 'webhooks_shared_secret' ) ) ) ?>">
                                    <div class="vi-ui positive message">
                                        <ul class="list">
                                            <li><?php echo wp_kses_post( __( 'You can find your shared secret within the message at the bottom of Notifications settings in your Shopify admin: "All your webhooks will be signed with <strong>{your_shared_secret}</strong> so you can verify their integrity."', 's2w-import-shopify-to-woocommerce' ) ) ?></li>
                                            <li><?php echo wp_kses_post( __( 'You must create at least 1 webhook to see the shared secret', 's2w-import-shopify-to-woocommerce' ) ) ?></li>
                                            <li><?php echo wp_kses_post( __( 'Please read the <a href="http://docs.villatheme.com/import-shopify-to-woocommerce/#set_up_child_menu_4124" target="_blank">docs</a> for more details', 's2w-import-shopify-to-woocommerce' ) ) ?></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        <div class="vi-ui segment">
                            <table class="form-table">
                                <tbody>
                                <tr>
                                    <th>
                                        <label for="<?php echo esc_attr( self::set( 'webhooks_orders_enable' ) ) ?>"><?php esc_html_e( 'Orders', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                    </th>
                                    <td>
                                        <div class="vi-ui toggle checkbox">
                                            <input type="checkbox"
                                                   name="<?php echo esc_attr( self::set( 'webhooks_orders_enable', true ) ) ?>"
                                                   id="<?php echo esc_attr( self::set( 'webhooks_orders_enable' ) ) ?>"
                                                   value="1" <?php checked( self::$settings->get_params( 'webhooks_orders_enable' ), '1' ) ?>>
                                            <label for="<?php echo esc_attr( self::set( 'webhooks_orders_enable' ) ) ?>"><?php esc_html_e( 'Enable', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        <label for="<?php echo esc_attr( self::set( 'webhooks_orders_create_customer' ) ) ?>"><?php esc_html_e( 'Create customer', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                    </th>
                                    <td>
                                        <div class="vi-ui toggle checkbox">
                                            <input type="checkbox"
                                                   name="<?php echo esc_attr( self::set( 'webhooks_orders_create_customer', true ) ) ?>"
                                                   id="<?php echo esc_attr( self::set( 'webhooks_orders_create_customer' ) ) ?>"
                                                   value="1" <?php checked( self::$settings->get_params( 'webhooks_orders_create_customer' ), '1' ) ?>>
                                            <label for="<?php echo esc_attr( self::set( 'webhooks_orders_create_customer' ) ) ?>"><?php esc_html_e( 'Automatically create customer if not exist when new order is imported successfully', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        <label for="<?php echo esc_attr( self::set( 'webhooks_orders_options' ) ) ?>"><?php esc_html_e( 'Sync which?', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                    </th>
                                    <td>
										<?php
										$all_options             = array(
											'order_status'     => esc_html__( 'Order status', 's2w-import-shopify-to-woocommerce' ),
											'order_date'       => esc_html__( 'Order date', 's2w-import-shopify-to-woocommerce' ),
											'fulfillments'     => esc_html__( 'Order fulfillments', 's2w-import-shopify-to-woocommerce' ),
											'billing_address'  => esc_html__( 'Billing address', 's2w-import-shopify-to-woocommerce' ),
											'shipping_address' => esc_html__( 'Shipping address', 's2w-import-shopify-to-woocommerce' ),
											'line_items'       => esc_html__( 'Line items', 's2w-import-shopify-to-woocommerce' ),
										);
										$webhooks_orders_options = self::$settings->get_params( 'webhooks_orders_options' );
										?>
                                        <select id="<?php echo esc_attr( self::set( 'webhooks_orders_options' ) ) ?>"
                                                class="vi-ui fluid dropdown"
                                                name="<?php echo esc_attr( self::set( 'webhooks_orders_options', true ) ) ?>[]"
                                                multiple="multiple">
											<?php
											foreach ( $all_options as $all_option_k => $all_option_v ) {
												?>
                                                <option value="<?php echo esc_attr( $all_option_k ) ?>" <?php if ( in_array( $all_option_k, $webhooks_orders_options ) ) {
													echo esc_attr( 'selected' );
												} ?>><?php echo esc_html( $all_option_v ) ?></option>
												<?php
											}
											?>
                                        </select>
                                        <p class="description"><?php _e( 'This option is used for updating order via webhook. Order statuses are mapped as below(Order status mapping for <strong>newly added orders</strong> is the same as the <a target="_blank" href="admin.php?page=s2w-import-shopify-to-woocommerce">import page</a>):', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                        <div>
											<?php
											$statuses             = wc_get_order_statuses();
											$order_status_mapping = self::$settings->get_params( 'webhooks_order_status_mapping' );
											if ( ! is_array( $order_status_mapping ) || ! count( $order_status_mapping ) ) {
												$order_status_mapping = self::$settings->get_default( 'order_status_mapping' );
											}
											?>
                                            <table class="vi-ui table">
                                                <thead>
                                                <tr>
                                                    <th><?php esc_html_e( 'From Shopify', 's2w-import-shopify-to-woocommerce' ) ?></th>
                                                    <th><?php esc_html_e( 'To WooCommerce', 's2w-import-shopify-to-woocommerce' ) ?></th>
                                                </tr>
                                                </thead>
                                                <tbody>
												<?php
												foreach ( $order_status_mapping as $from => $to ) {
													?>
                                                    <tr>
                                                        <td><?php esc_html_e( ucwords( str_replace( '_', ' ', $from ) ) ) ?></td>
                                                        <td>
                                                            <select class="vi-ui fluid dropdown <?php echo esc_attr( self::set( 'webhooks_order_status_mapping' ) ) ?>"
                                                                    data-from_status="<?php echo esc_attr( $from ) ?>"
                                                                    name="<?php echo esc_attr( self::set( 'webhooks_order_status_mapping', true ) . '[' . $from . ']' ) ?>">
                                                                <option value=""><?php esc_html_e( 'Do not sync', 's2w-import-shopify-to-woocommerce' ) ?></option>
																<?php
																foreach ( $statuses as $st => $status ) {
																	$st = substr( $st, 3 );
																	?>
                                                                    <option value="<?php echo esc_attr( $st ) ?>" <?php selected( $st, $to ) ?>><?php echo esc_html( $status ) ?></option>
																	<?php
																}
																?>
                                                            </select>
                                                        </td>
                                                    </tr>
													<?php
												}
												?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        <label><?php esc_html_e( 'Orders Webhook URL', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                    </th>
                                    <td>
                                        <div class="vi-ui fluid right labeled input <?php echo esc_attr( self::set( 'webhooks-url-container' ) ) ?>">
                                            <input type="text" readonly
                                                   class="<?php echo esc_attr( self::set( 'webhooks-url' ) ) ?>"
                                                   value="<?php echo esc_url( get_site_url( null, 'wp-json/s2w-import-shopify-to-woocommerce/orders' ) ) ?>">
                                            <i class="check green icon"></i>
                                            <label class="vi-ui label"><span
                                                        class="vi-ui small positive button <?php echo esc_attr( self::set( 'webhooks-url-copy' ) ) ?>"><?php esc_html_e( 'Copy', 's2w-import-shopify-to-woocommerce' ) ?></span></label>
                                        </div>
                                        <div class="vi-ui positive message">
                                            <ul class="list">
                                                <li><?php echo wp_kses_post( __( 'If you want to <strong>only import new order when one is created</strong> at your Shopify store, create a webhook with event <strong>Order Creation</strong> and use this URL for the webhook URL.', 's2w-import-shopify-to-woocommerce' ) ) ?></li>
                                                <li><?php echo wp_kses_post( __( 'If you want to both <strong>create new order when one is created and update existing order when one is updated</strong> at your Shopify store, create a webhook with event <strong>Order Update</strong> and use this URL for the webhook URL.', 's2w-import-shopify-to-woocommerce' ) ) ?></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="vi-ui segment">
                            <table class="form-table">
                                <tbody>
                                <tr>
                                    <th>
                                        <label for="<?php echo esc_attr( self::set( 'webhooks_products_enable' ) ) ?>"><?php esc_html_e( 'Products', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                    </th>
                                    <td>
                                        <div class="vi-ui toggle checkbox">
                                            <input type="checkbox"
                                                   name="<?php echo esc_attr( self::set( 'webhooks_products_enable', true ) ) ?>"
                                                   id="<?php echo esc_attr( self::set( 'webhooks_products_enable' ) ) ?>"
                                                   value="1" <?php checked( self::$settings->get_params( 'webhooks_products_enable' ), '1' ) ?>>
                                            <label for="<?php echo esc_attr( self::set( 'webhooks_products_enable' ) ) ?>"><?php esc_html_e( 'Enable', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        <label for="<?php echo esc_attr( self::set( 'only_update_product_exist' ) ) ?>"><?php esc_html_e( 'Update existing product only', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                    </th>
                                    <td>
                                        <div class="vi-ui toggle checkbox">
                                            <input type="checkbox"
                                                   name="<?php echo esc_attr( self::set( 'only_update_product_exist', true ) ) ?>"
                                                   id="<?php echo esc_attr( self::set( 'only_update_product_exist' ) ) ?>"
                                                   value="1" <?php checked( self::$settings->get_params( 'only_update_product_exist' ), '1' ) ?>>
                                            <label for="<?php echo esc_attr( self::set( 'only_update_product_exist' ) ) ?>"><?php esc_html_e( 'Enable', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                        </div>
                                        <p class="description"><?php esc_html_e( 'Enable this option to exclusively update products that have already been imported through the webhook.', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        <label for="<?php echo esc_attr( self::set( 'webhooks_products_options' ) ) ?>"><?php esc_html_e( 'Sync which?', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                    </th>
                                    <td>
										<?php
										$all_options = S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Update_Products::get_supported_options();
										unset( $all_options['metafields'] );
										$webhooks_products_options = self::$settings->get_params( 'webhooks_products_options' );
										?>
                                        <select id="<?php echo esc_attr( self::set( 'webhooks_products_options' ) ) ?>"
                                                class="vi-ui fluid dropdown"
                                                name="<?php echo esc_attr( self::set( 'webhooks_products_options', true ) ) ?>[]"
                                                multiple="multiple">
											<?php
											foreach ( $all_options as $all_option_k => $all_option_v ) {
												?>
                                                <option value="<?php echo esc_attr( $all_option_k ) ?>" <?php if ( in_array( $all_option_k, $webhooks_products_options ) ) {
													echo esc_attr( 'selected' );
												} ?>><?php echo esc_html( $all_option_v ) ?></option>
												<?php
											}
											?>
                                        </select>
                                        <p class="description"><?php esc_html_e( 'This option is used for updating product via webhook', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                        <p class="description s2w-barcode-description"><?php echo wp_kses_post( S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Update_Products::get_barcode_sync_description() ) ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        <label><?php esc_html_e( 'Products Webhook URL', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                    </th>
                                    <td>
                                        <div class="vi-ui fluid right labeled input <?php echo esc_attr( self::set( 'webhooks-url-container' ) ) ?>">
                                            <input type="text" readonly
                                                   class="<?php echo esc_attr( self::set( 'webhooks-url' ) ) ?>"
                                                   value="<?php echo esc_url( get_site_url( null, 'wp-json/s2w-import-shopify-to-woocommerce/products' ) ) ?>">
                                            <i class="check green icon"></i>
                                            <label class="vi-ui label"><span
                                                        class="vi-ui small positive button <?php echo esc_attr( self::set( 'webhooks-url-copy' ) ) ?>"><?php esc_html_e( 'Copy', 's2w-import-shopify-to-woocommerce' ) ?></span></label>
                                        </div>
                                        <div class="vi-ui positive message">
                                            <ul class="list">
                                                <li><?php echo wp_kses_post( __( 'If you want to <strong>only import new product when one is created</strong> at your Shopify store, create a webhook with event <strong>Product Creation</strong> and use this URL for the webhook URL.', 's2w-import-shopify-to-woocommerce' ) ) ?></li>
                                                <li><?php echo wp_kses_post( __( 'If you want to both <strong>create new product when one is created and update existing product when one is updated</strong> at your Shopify store, create a webhook with event <strong>Product Update</strong> and use this URL for the webhook URL.', 's2w-import-shopify-to-woocommerce' ) ) ?></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="vi-ui segment">
                            <table class="form-table">
                                <tbody>
                                <tr>
                                    <th>
                                        <label for="<?php echo esc_attr( self::set( 'webhooks_customers_enable' ) ) ?>"><?php esc_html_e( 'Customers', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                    </th>
                                    <td>
                                        <div class="vi-ui toggle checkbox">
                                            <input type="checkbox"
                                                   name="<?php echo esc_attr( self::set( 'webhooks_customers_enable', true ) ) ?>"
                                                   id="<?php echo esc_attr( self::set( 'webhooks_customers_enable' ) ) ?>"
                                                   value="1" <?php checked( self::$settings->get_params( 'webhooks_customers_enable' ), '1' ) ?>>
                                            <label for="<?php echo esc_attr( self::set( 'webhooks_customers_enable' ) ) ?>"><?php esc_html_e( 'Enable', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        <label><?php esc_html_e( 'Customers Webhook URL', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                    </th>
                                    <td>
                                        <div class="vi-ui fluid right labeled input <?php echo esc_attr( self::set( 'webhooks-url-container' ) ) ?>">
                                            <input type="text" readonly
                                                   class="<?php echo esc_attr( self::set( 'webhooks-url' ) ) ?>"
                                                   value="<?php echo esc_url( get_site_url( null, 'wp-json/s2w-import-shopify-to-woocommerce/customers' ) ) ?>">
                                            <i class="check green icon"></i>
                                            <label class="vi-ui label"><span
                                                        class="vi-ui small positive button <?php echo esc_attr( self::set( 'webhooks-url-copy' ) ) ?>"><?php esc_html_e( 'Copy', 's2w-import-shopify-to-woocommerce' ) ?></span></label>
                                        </div>
                                        <div class="vi-ui positive message">
                                            <ul class="list">
                                                <li><?php echo wp_kses_post( __( 'If you want to <strong>only import new customer when one is created</strong> at your Shopify store, create a webhook with event <strong>Customer Creation</strong> and use this URL for the webhook URL.', 's2w-import-shopify-to-woocommerce' ) ) ?></li>
                                                <li><?php echo wp_kses_post( __( 'If you want to both <strong>create new customer when one is created and update existing customer when one is updated</strong> at your Shopify store, create a webhook with event <strong>Customer Update</strong> and use this URL for the webhook URL.', 's2w-import-shopify-to-woocommerce' ) ) ?></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <p>
                        <button type="submit" class="vi-ui labeled icon button primary"
                                name="s2w_save_webhooks_options"><i
                                    class="icon save"></i><?php esc_html_e( 'Save', 's2w-import-shopify-to-woocommerce' ) ?>
                        </button>
                    </p>
                </form>
            </div>
			<?php
		}

		public function register_api() {
			register_rest_route(
				's2w-import-shopify-to-woocommerce', '/orders', array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'process_orders' ),
					'permission_callback' => '__return_true',
				)
			);
			register_rest_route(
				's2w-import-shopify-to-woocommerce', '/products', array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'process_products' ),
					'permission_callback' => '__return_true',
				)
			);
			register_rest_route(
				's2w-import-shopify-to-woocommerce', '/customers', array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'process_customers' ),
					'permission_callback' => '__return_true',
				)
			);
		}

		/**
		 * @param $request WP_REST_Request
		 *
		 * @throws Exception
		 */
		public function process_customers( $request ) {
			$domain                    = self::$settings->get_params( 'domain' );
			$access_token              = self::$settings->get_params( 'access_token' );
			$api_key                   = self::$settings->get_params( 'api_key' );
			$api_secret                = self::$settings->get_params( 'api_secret' );
			$shared_secret             = self::$settings->get_params( 'webhooks_shared_secret' );
			$webhooks_customers_enable = self::$settings->get_params( 'webhooks_customers_enable' );
			$hmac_header               = $request->get_header( 'x_shopify_hmac_sha256' );
			$this->webhook_name        = $request->get_header( 'x_shopify_topic' );
			$user_agent                = $request->get_header( 'user_agent' );
			$data                      = file_get_contents( 'php://input' );
			$path                      = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_cache_path( $domain, $access_token, $api_key, $api_secret ) . '/';
			if ( self::verify_webhook( $data, $hmac_header, $shared_secret ) ) {
				if ( ! $webhooks_customers_enable ) {
					self::log( $path, 'Customers webhook is currently disabled' );
				} else {
					$customer_data = vi_s2w_json_decode( $data );
					if ( ! empty( $customer_data['id'] ) ) {
						switch ( $this->webhook_name ) {
							case 'customers/create':
								if ( strtolower( $user_agent ) === 'ruby' ) {
									self::log( $path, 'Test Customer creation webhook: Successful' );
								} else {
									$this->create_customer( $customer_data, $path );
								}
								break;
							case 'customers/update':
								if ( strtolower( $user_agent ) === 'ruby' ) {
									self::log( $path, 'Test Customer update webhook: Successful' );
								} else {
									$this->update_customer( $customer_data, $path );
								}
								break;
							default:
								self::log( $path, "Wrong webhook request to customers: {$this->webhook_name}" );
						}
					}
				}
			} else {

				self::log( $path, 'Unverified Webhook call' );
			}
		}

		/**
		 * @param $customer_data
		 * @param $path
		 *
		 * @throws Exception
		 */
		public function update_customer( $customer_data, $path ) {
			$existing_id = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::customer_get_id_by_shopify_id( $customer_data['id'] );
			if ( $existing_id ) {
				$user_id = S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE::import_customer( $customer_data, $existing_id );
				if ( is_wp_error( $user_id ) ) {
					self::log( $path, "Error updating Shopify customer #{$customer_data['id']}, {$user_id->get_error_message()}. Customer data: " . json_encode( $customer_data ) );
				} else {
					self::log( $path, "Customer #{$existing_id} was successfully updated" );
				}
			} else {
				$this->create_customer( $customer_data, $path );
			}
		}

		/**
		 * @param $customer_data
		 * @param $path
		 *
		 * @return bool|int|mixed|WP_Error
		 * @throws Exception
		 */
		public function create_customer( $customer_data, $path ) {
			$user_id                       = false;
			$customers_with_purchases_only = self::$settings->get_params( 'customers_with_purchases_only' );
			$orders_count                  = isset( $customer_data['orders_count'] ) ? absint( $customer_data['orders_count'] ) : 0;
			if ( ! $customers_with_purchases_only || $orders_count > 0 ) {
				$user_id = S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE::import_customer( $customer_data );
				if ( is_wp_error( $user_id ) ) {
					self::log( $path, "Failed importing customer from Shopify #{$customer_data['id']}, {$user_id->get_error_message()}. Customer data: " . json_encode( $customer_data ) );
				} elseif ( $user_id ) {
					self::log( $path, "Customer #{$user_id} was imported from Shopify #{$customer_data['id']}" );
				} else {
					self::log( $path, "Failed importing customer from Shopify #{$customer_data['id']}. Customer data: " . json_encode( $customer_data ) );
				}
			}

			return $user_id;
		}

		/**
		 * @param $request WP_REST_Request
		 *
		 * @throws WC_Data_Exception
		 */
		public function process_orders( $request ) {
			$domain                 = self::$settings->get_params( 'domain' );
			$access_token           = self::$settings->get_params( 'access_token' );
			$api_key                = self::$settings->get_params( 'api_key' );
			$api_secret             = self::$settings->get_params( 'api_secret' );
			$shared_secret          = self::$settings->get_params( 'webhooks_shared_secret' );
			$webhooks_orders_enable = self::$settings->get_params( 'webhooks_orders_enable' );
			$hmac_header            = $request->get_header( 'x_shopify_hmac_sha256' );
			$this->webhook_name     = $request->get_header( 'x_shopify_topic' );
			$user_agent             = $request->get_header( 'user_agent' );
			$data                   = file_get_contents( 'php://input' );
			$path                   = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_cache_path( $domain, $access_token, $api_key, $api_secret ) . '/';
			if ( self::verify_webhook( $data, $hmac_header, $shared_secret ) ) {
				if ( ! $webhooks_orders_enable ) {
					self::log( $path, 'Orders webhook is currently disabled' );
				} else {
					$order_data = vi_s2w_json_decode( $data );
					if ( ! empty( $order_data['id'] ) ) {
						switch ( $this->webhook_name ) {
							case 'orders/create':
								if ( strtolower( $user_agent ) === 'ruby' ) {
									self::log( $path, 'Test Order creation webhook: Successful' );
								} else {
									S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE::remove_refunded_email_notification();
									$this->create_order( $order_data, $path );
								}
								break;
							case 'orders/updated':
								if ( strtolower( $user_agent ) === 'ruby' ) {
									self::log( $path, 'Test Order update webhook: Successful' );
								} else {
									S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE::remove_refunded_email_notification();
									$this->update_order( $order_data, $path );
								}
								break;
							default:
								self::log( $path, "Wrong webhook request to orders: {$this->webhook_name}" );
						}
					}
				}
			} else {

				self::log( $path, 'Unverified Webhook call' );
			}
		}

		/**
		 * @param $order_data
		 * @param $path
		 *
		 * @throws WC_Data_Exception
		 */
		public function create_order( $order_data, $path ) {
			do_action( 's2w_webhook_before_create_order', $this->webhook_name, $order_data, $path );
			$order_id = S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE::import_order( $order_data );
			if ( $order_id ) {
				self::log( $path, "New order was imported: #{$order_id}" );
				if ( self::$settings->get_params( 'webhooks_orders_create_customer' ) && is_array( $order_data['customer'] ) && count( $order_data['customer'] ) ) {
					/*Create customer if not exists and webhooks_orders_create_customer option is ON*/
					$order       = wc_get_order( $order_id );
					$customer_id = $order->get_user_id();
					if ( ! $customer_id ) {
						$customer_id = $this->create_customer( $order_data['customer'], $path );
						if ( $customer_id && ! is_wp_error( $customer_id ) ) {
							$order->set_customer_id( $customer_id );
							$order->save();
						}
					}
				}
				do_action( 's2w_webhook_create_order_successfully', $order_id, $order_data );
			}
		}

		/**
		 * @param $order_data
		 * @param $path
		 *
		 * @throws WC_Data_Exception
		 */
		public function update_order( $order_data, $path ) {
			$order_id = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::query_get_id_by_shopify_id( $order_data['id'] );
			if ( $order_id ) {
				do_action( 's2w_webhook_before_update_order', $this->webhook_name, $order_data, $path );
				$webhooks_orders_options = self::$settings->get_params( 'webhooks_orders_options' );
				if ( count( $webhooks_orders_options ) ) {
					$order = wc_get_order( $order_id );
					if ( $order ) {
						$all_options = S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Update_Orders::get_supported_options();;
						$update_which = array();
						foreach ( $all_options as $all_option_k => $all_option ) {
							$update_which[ $all_option_k ] = in_array( $all_option_k, $webhooks_orders_options );
						}
						if ( ! empty( $update_which['order_status'] ) ) {
							$update_which['order_status'] = false;//Do not sync order status S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Update_Orders::sync_order_data because webhook order sync uses its own order status mapping
							$order_status_mapping         = self::$settings->get_params( 'webhooks_order_status_mapping' );
							if ( is_array( $order_status_mapping ) && count( $order_status_mapping ) ) {
								$financial_status = isset( $order_data['financial_status'] ) ? $order_data['financial_status'] : '';
								$order_status     = apply_filters( 's2w_update_order_status', isset( $order_status_mapping[ $financial_status ] ) ? $order_status_mapping[ $financial_status ] : '', $order_data, $order );
								if ( $order_status ) {
									wp_update_post( array(
										'post_status' => 'wc-' . $order_status,
										'ID'          => $order_id
									) );
								}
							}
						}
						S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Update_Orders::sync_order_data( $order, $order_data, $update_which, $new_data );
						do_action( 's2w_webhook_update_order_successfully', $order_id, $order_data );
						self::log( $path, "Order #{$order_id} was updated" );
					}
				}
			} else {
				$this->create_order( $order_data, $path );
			}
		}

		/**
		 * @param $request WP_REST_Request
		 */
		public function process_products( $request ) {
			$domain                   = self::$settings->get_params( 'domain' );
			$access_token             = self::$settings->get_params( 'access_token' );
			$api_key                  = self::$settings->get_params( 'api_key' );
			$api_secret               = self::$settings->get_params( 'api_secret' );
			$shared_secret            = self::$settings->get_params( 'webhooks_shared_secret' );
			$webhooks_products_enable = self::$settings->get_params( 'webhooks_products_enable' );
			$hmac_header              = $request->get_header( 'x_shopify_hmac_sha256' );
			$this->webhook_name       = $request->get_header( 'x_shopify_topic' );
			$user_agent               = $request->get_header( 'user_agent' );
			$data                     = file_get_contents( 'php://input' );
			$path                     = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_cache_path( $domain, $access_token, $api_key, $api_secret ) . '/';
			if ( self::verify_webhook( $data, $hmac_header, $shared_secret ) ) {
				if ( ! $webhooks_products_enable ) {
					self::log( $path, 'Products webhook is currently disabled' );
				} else {
					$product_data = vi_s2w_json_decode( $data );
					if ( ! empty( $product_data['id'] ) ) {
						switch ( $this->webhook_name ) {
							case 'products/create':
								if ( strtolower( $user_agent ) === 'ruby' ) {
									self::log( $path, 'Test Product creation webhook: Successful' );
								} else {
									$this->create_product( $product_data, $path );
								}
								break;
							case 'products/update':
								if ( strtolower( $user_agent ) === 'ruby' ) {
									self::log( $path, 'Test Product update webhook: Successful' );
								} else {
									$this->update_product( $product_data, $path );
								}
								break;
							default:
								self::log( $path, "Wrong webhook request to products: {$this->webhook_name}" );
						}
					}
				}
			} else {

				self::log( $path, 'Unverified Webhook call' );
			}
		}

		/**
		 * Sync products via Product Update webhook
		 *
		 * @param $product_data
		 * @param $path
		 */
		public function update_product( $product_data, $path ) {
			$shopify_id              = $product_data['id'];
			$product_id              = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::product_get_woo_id_by_shopify_id( $shopify_id );
			$only_sync_product_exist = self::$settings->get_params( 'only_update_product_exist' );
			if ( $product_id ) {
				/*Sync existing products*/
				$webhooks_products_options = self::$settings->get_params( 'webhooks_products_options' );
				if ( count( $webhooks_products_options ) ) {
					$product = wc_get_product( $product_id );
					if ( $product ) {
						$variants = isset( $product_data['variants'] ) ? $product_data['variants'] : array();
						$options  = isset( $product_data['options'] ) ? $product_data['options'] : array();
						if ( count( $options ) && count( $variants ) ) {
							$update_which = array();
							$all_options  = S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Update_Products::get_supported_options();
							foreach ( $all_options as $all_option_k => $all_option ) {
								$update_which[ $all_option_k ] = in_array( $all_option_k, $webhooks_products_options );
							}
							if ( count( array_intersect( $webhooks_products_options, array(
								'price',
								'inventory',
								'variations',
								'variation_attributes',
								'variation_sku',
								'barcode',
							) ) )
							) {
								S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Update_Products::sync_product_variation_data( $product, $shopify_id, $update_which, $product_data );
							}
							S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Update_Products::sync_product_post_data( $product, $update_which, $product_data, $new_data );
							self::log( $path, "Product #{$product_id} was successfully updated" );
						} else {
							self::log( $path, "Product #{$product_id} update canceled: Empty data" );
						}
					}
				}
			} else {
				if ( ! $only_sync_product_exist ) {
					/*Create new if not exists*/
					$this->create_product( $product_data, $path );
				}
			}
		}

		/**
		 * Create a product via webhook
		 *
		 * @param $product_data
		 * @param $path
		 */
		public function create_product( $product_data, $path ) {
			$download_images        = self::$settings->get_params( 'download_images' );
			$keep_slug              = self::$settings->get_params( 'keep_slug' );
			$variable_sku           = self::$settings->get_params( 'variable_sku' );
			$global_attributes      = self::$settings->get_params( 'global_attributes' );
			$product_status         = self::$settings->get_params( 'product_status' );
			$product_status_mapping = self::$settings->get_params( 'product_status_mapping' );
			$product_categories     = self::$settings->get_params( 'product_categories' );
			$shopify_id             = $product_data['id'];
			if ( ! empty( $product_status_mapping[ $product_data['status'] ] ) ) {
				$product_status = $product_status_mapping[ $product_data['status'] ];
			}
			if ( $product_status === 'not_import' ) {
				return;
			}
			$sku     = str_replace( array(
				'{shopify_product_id}',
				'{product_slug}'
			), array( $shopify_id, $product_data['handle'] ), $variable_sku );
			$sku     = str_replace( ' ', '', $sku );
			$options = isset( $product_data['options'] ) ? $product_data['options'] : array();
			if ( ! VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::sku_exists( $sku ) ) {
				if ( is_array( $options ) && count( $options ) ) {
					$images_d   = array();
					$product_id = S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE::import_product( $options, $product_data, $sku, $global_attributes, $product_status, $keep_slug, $download_images, $product_categories, $images_d );
					if ( ! is_wp_error( $product_id ) ) {
						$dispatch    = false;
						$description = isset( $product_data['body_html'] ) ? html_entity_decode( $product_data['body_html'], ENT_QUOTES | ENT_XML1, 'UTF-8' ) : '';
						S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE::handle_description_images( $description, $product_id, $dispatch, S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Update_Products::$process_for_update );
						if ( count( $images_d ) ) {
							if ( self::$settings->get_params( 'disable_background_process' ) ) {
								foreach ( $images_d as $images_d_k => $images_d_v ) {
									S2W_Error_Images_Table::insert( $product_id, implode( ',', $images_d_v['product_ids'] ), $images_d_v['src'], $images_d_v['alt'], intval( $images_d_v['set_gallery'] ), $images_d_v['id'] );
								}
							} else {
								$dispatch = true;
								foreach ( $images_d as $images_d_k => $images_d_v ) {
									S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Update_Products::$process_for_update->push_to_queue( $images_d_v );
								}
							}
						}
						if ( $dispatch ) {
							S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Update_Products::$process_for_update->save()->dispatch();
						}
						self::log( $path, "Product #{$product_id} was successfully imported from Shopify #{$shopify_id}" );
					}
				}
			} else {
				self::log( $path, "Can not import product {$shopify_id}, SKU exists {$sku}" );
			}
		}

		/**
		 * https://shopify.dev/apps/webhooks/configuration/https
		 *
		 * @param $data
		 * @param $hmac_header
		 * @param $shared_secret
		 *
		 * @return bool
		 */
		public static function verify_webhook( $data, $hmac_header, $shared_secret ) {
			$calculated_hmac = base64_encode( hash_hmac( 'sha256', $data, $shared_secret, true ) );

			return hash_equals( $hmac_header, $calculated_hmac );
		}

		public static function set( $name, $set_name = false ) {
			return VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::set( $name, $set_name );
		}

		public static function log( $path, $content ) {
			S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $path . 'webhooks_logs.txt', $content );
		}

		private static function get_required_capability() {
			return apply_filters( 'vi_s2w_admin_sub_menu_capability', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_required_capability( 'webhooks' ), 's2w-import-shopify-to-woocommerce-webhooks' );
		}
	}
}