<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Sync orders using cron
 */
if ( ! class_exists( 'S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Cron_Update_Orders' ) ) {
	class S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Cron_Update_Orders {
		protected static $settings;
		public static $update_orders;
		public static $get_data_to_update;
		protected $next_schedule;

		public function __construct() {
			self::$settings = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_instance();
			add_action( 'plugins_loaded', array( $this, 'background_process' ) );
			add_action( 'admin_init', array( $this, 'admin_init' ) );
			add_action( 'admin_init', array( $this, 'save_options' ) );
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_script' ) );
			add_action( 'admin_menu', array( $this, 'admin_menu' ), 16 );
			add_action( 's2w_cron_update_orders', array( $this, 'cron_update_orders' ) );
			add_filter( 'cron_schedules', array( $this, 'cron_schedules' ) );
			$this->next_schedule = wp_next_scheduled( 's2w_cron_update_orders' );
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
		 * Cron interval, maximum once a day
		 *
		 * @param $schedules
		 *
		 * @return mixed
		 */
		public function cron_schedules( $schedules ) {
			$schedules['s2w_cron_update_orders_interval'] = array(
				'interval' => 86400 * absint( self::$settings->get_params( 'cron_update_orders_interval' ) ),
				'display'  => __( 'Cron Orders Sync', 's2w-import-shopify-to-woocommerce' ),
			);

			return $schedules;
		}

		/**
		 * Add menu
		 */
		public function admin_menu() {
			add_submenu_page( 's2w-import-shopify-to-woocommerce',
				esc_html__( 'Cron Orders Sync', 's2w-import-shopify-to-woocommerce' ),
				esc_html__( 'Cron Orders Sync', 's2w-import-shopify-to-woocommerce' ),
				self::get_required_capability(), 's2w-import-shopify-to-woocommerce-cron-update-orders', array(
					$this,
					'page_callback'
				)
			);
		}

		/**
		 * Cron Orders Sync page
		 */
		public function page_callback() {
			?>
            <div class="wrap">
                <h2><?php esc_html_e( 'Cron Orders Sync', 's2w-import-shopify-to-woocommerce' ) ?></h2>
				<?php S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE::security_recommendation_html(); ?>
                <p></p>
                <form class="vi-ui form" method="post">
					<?php wp_nonce_field( 's2w_action_nonce', '_s2w_nonce' ); ?>
                    <div class="vi-ui segment">
						<?php
						if ( ! self::$settings->get_params( 'validate' ) ) {
							?>
                            <div class="vi-ui negative message"><?php esc_html_e( 'You need to enter correct domain, API key and API secret to use this function', 's2w-import-shopify-to-woocommerce' );; ?></div>
							<?php
						}
						if ( $this->next_schedule ) {
							$gmt_offset = intval( get_option( 'gmt_offset' ) );
							?>
                            <div class="vi-ui positive message"><?php printf( __( 'Next schedule: <strong>%s</strong>', 's2w-import-shopify-to-woocommerce' ), date_i18n( 'F j, Y g:i:s A', ( $this->next_schedule + HOUR_IN_SECONDS * $gmt_offset ) ) ); ?></div>
							<?php
						} else {
							?>
                            <div class="vi-ui negative message"><?php esc_html_e( 'Cron Orders Sync is currently DISABLED', 's2w-import-shopify-to-woocommerce' );; ?></div>
							<?php
						}
						?>
                        <table class="form-table">
                            <tbody>
                            <tr>
                                <th>
                                    <label for="<?php echo esc_attr( self::set( 'cron_update_orders' ) ) ?>"><?php esc_html_e( 'Enable cron', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                </th>
                                <td>
                                    <div class="vi-ui toggle checkbox checked">
                                        <input type="checkbox"
                                               name="<?php echo esc_attr( self::set( 'cron_update_orders', true ) ) ?>"
                                               id="<?php echo esc_attr( self::set( 'cron_update_orders' ) ) ?>"
                                               value="1" <?php checked( self::$settings->get_params( 'cron_update_orders' ), '1' ) ?>>
                                        <label for="<?php echo esc_attr( self::set( 'cron_update_orders' ) ) ?>"><?php esc_html_e( 'Automatically sync Shopify orders', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="<?php echo esc_attr( self::set( 'cron_update_orders_force_sync' ) ) ?>"><?php esc_html_e( 'Force sync', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                </th>
                                <td>
                                    <div class="vi-ui toggle checkbox checked">
                                        <input type="checkbox"
                                               name="<?php echo esc_attr( self::set( 'cron_update_orders_force_sync', true ) ) ?>"
                                               id="<?php echo esc_attr( self::set( 'cron_update_orders_force_sync' ) ) ?>"
                                               value="1" <?php checked( self::$settings->get_params( 'cron_update_orders_force_sync' ), '1' ) ?>>
                                        <label for="<?php echo esc_attr( self::set( 'cron_update_orders_force_sync' ) ) ?>"><?php esc_html_e( 'Sync orders even there are no changes in data in comparison to the previous sync', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="<?php echo esc_attr( self::set( 'cron_update_orders_interval' ) ) ?>"><?php esc_html_e( 'Run cron every', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                </th>
                                <td>
                                    <div class="vi-ui right labeled input">
                                        <input type="number"
                                               required
                                               min="1"
                                               name="<?php echo esc_attr( self::set( 'cron_update_orders_interval', true ) ) ?>"
                                               id="<?php echo esc_attr( self::set( 'cron_update_orders_interval' ) ) ?>"
                                               value="<?php echo esc_attr( self::$settings->get_params( 'cron_update_orders_interval' ) ) ?>">
                                        <label for="<?php echo esc_attr( self::set( 'cron_update_orders_interval' ) ) ?>"
                                               class="vi-ui label"><?php esc_html_e( 'Day(s)', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                    </div>
                                    <p><?php esc_html_e( 'You should run cron for less than 300 orders per day', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="<?php echo esc_attr( self::set( 'cron_update_orders_hour' ) ) ?>"><?php esc_html_e( 'Run cron at', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                </th>
                                <td>
                                    <div class="equal width fields">
                                        <div class="field">
                                            <div class="vi-ui right labeled input">
                                                <input type="number" min="0" max="23"
                                                       required
                                                       name="<?php echo esc_attr( self::set( 'cron_update_orders_hour', true ) ) ?>"
                                                       id="<?php echo esc_attr( self::set( 'cron_update_orders_hour' ) ) ?>"
                                                       value="<?php echo esc_attr( self::$settings->get_params( 'cron_update_orders_hour' ) ) ?>">
                                                <label for="<?php echo esc_attr( self::set( 'cron_update_orders_hour' ) ) ?>"
                                                       class="vi-ui label"><?php esc_html_e( 'Hour', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                            </div>
                                        </div>
                                        <div class="field">
                                            <div class="vi-ui right labeled input">
                                                <input type="number" min="0" max="59"
                                                       required
                                                       name="<?php echo esc_attr( self::set( 'cron_update_orders_minute', true ) ) ?>"
                                                       id="<?php echo esc_attr( self::set( 'cron_update_orders_minute' ) ) ?>"
                                                       value="<?php echo esc_attr( self::$settings->get_params( 'cron_update_orders_minute' ) ) ?>">
                                                <label for="<?php echo esc_attr( self::set( 'cron_update_orders_minute' ) ) ?>"
                                                       class="vi-ui label"><?php esc_html_e( 'Minute', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                            </div>
                                        </div>
                                        <div class="field">
                                            <div class="vi-ui right labeled input">
                                                <input type="number" min="0" max="59"
                                                       required
                                                       name="<?php echo esc_attr( self::set( 'cron_update_orders_second', true ) ) ?>"
                                                       id="<?php echo esc_attr( self::set( 'cron_update_orders_second' ) ) ?>"
                                                       value="<?php echo esc_attr( self::$settings->get_params( 'cron_update_orders_second' ) ) ?>">
                                                <label for="<?php echo esc_attr( self::set( 'cron_update_orders_second' ) ) ?>"
                                                       class="vi-ui label"><?php esc_html_e( 'Second', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                            </div>
                                        </div>
                                    </div>

                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="<?php echo esc_attr( self::set( 'cron_update_orders_status' ) ) ?>"><?php esc_html_e( 'Only sync orders with status:', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                </th>
                                <td>
                                    <select class="vi-ui fluid dropdown"
                                            name="<?php echo esc_attr( self::set( 'cron_update_orders_status', true ) ) ?>[]"
                                            multiple="multiple"
                                            id="<?php echo esc_attr( self::set( 'cron_update_orders_status' ) ) ?>">
										<?php
										$cron_update_orders_status = self::$settings->get_params( 'cron_update_orders_status' );
										$options                   = wc_get_order_statuses();
										foreach ( $options as $option_k => $option_v ) {
											?>
                                            <option value="<?php echo esc_attr( $option_k ) ?>"<?php if ( in_array( $option_k, $cron_update_orders_status ) )
												echo esc_attr( 'selected' ) ?>><?php echo esc_html( $option_v ); ?></option>
											<?php
										}
										?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="<?php echo esc_attr( self::set( 'cron_update_orders_range' ) ) ?>"><?php esc_html_e( 'Only sync orders created in the last x day(s):', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                </th>
                                <td>
                                    <div class="vi-ui right labeled input">
                                        <input type="number" min="0" max=""
                                               name="<?php echo esc_attr( self::set( 'cron_update_orders_range', true ) ) ?>"
                                               id="<?php echo esc_attr( self::set( 'cron_update_orders_range' ) ) ?>"
                                               value="<?php echo esc_attr( self::$settings->get_params( 'cron_update_orders_range' ) ) ?>">
                                        <label for="<?php echo esc_attr( self::set( 'cron_update_orders_range' ) ) ?>"
                                               class="vi-ui label"><?php esc_html_e( 'Day(s)', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                    </div>
                                    <p class="description"><?php esc_html_e( 'Set 0 to skip filtering orders by date range', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="<?php echo esc_attr( self::set( 'cron_update_orders_date_paid' ) ) ?>"><?php esc_html_e( 'Sync order date paid', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                </th>
                                <td>
                                    <div class="vi-ui toggle checkbox checked">
                                        <input type="checkbox"
                                               name="<?php echo esc_attr( self::set( 'cron_update_orders_date_paid', true ) ) ?>"
                                               id="<?php echo esc_attr( self::set( 'cron_update_orders_date_paid' ) ) ?>"
                                               value="1" <?php checked( self::$settings->get_params( 'cron_update_orders_date_paid' ), '1' ) ?>>
                                        <label for="<?php echo esc_attr( self::set( 'cron_update_orders_date_paid' ) ) ?>"><?php esc_html_e( 'By default, the paid order date is determined by the order transaction date, independently of the order API. As a result, there will be a separate section with a distinct synchronization option.', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                    </div>
                                </td>
                            </tr>
                            <tr class="wrap_cron_update_orders_options">
                                <th>
                                    <label for="<?php echo esc_attr( self::set( 'cron_update_orders_options' ) ) ?>"><?php esc_html_e( 'Select options to sync', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                </th>
                                <td>
                                    <select class="vi-ui fluid dropdown"
                                            name="<?php echo esc_attr( self::set( 'cron_update_orders_options', true ) ) ?>[]"
                                            multiple="multiple"
                                            id="<?php echo esc_attr( self::set( 'cron_update_orders_options' ) ) ?>">
										<?php
										$cron_update_orders_options = self::$settings->get_params( 'cron_update_orders_options' );
										$options                    = array(
											'status'           => esc_html__( 'Status', 's2w-import-shopify-to-woocommerce' ),
											'billing_address'  => esc_html__( 'Billing Address', 's2w-import-shopify-to-woocommerce' ),
											'shipping_address' => esc_html__( 'Shipping Address', 's2w-import-shopify-to-woocommerce' ),
											'fulfillments'     => esc_html__( 'Fulfillments', 's2w-import-shopify-to-woocommerce' ),
											'date_paid'        => esc_html__( 'Date paid', 's2w-import-shopify-to-woocommerce' ),
										);
										foreach ( $options as $option_k => $option_v ) {
											?>
                                            <option value="<?php echo esc_attr( $option_k ) ?>"<?php if ( in_array( $option_k, $cron_update_orders_options ) )
												echo esc_attr( 'selected' ) ?>><?php echo esc_html( $option_v ); ?></option>
											<?php
										}
										?>
                                    </select>
                                    <p class="description"><?php esc_html_e( 'Which order data do you want to sync?', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <p>
                        <button type="submit" class="vi-ui labeled icon button primary"
                                name="s2w_save_cron_update_orders"><i
                                    class="icon save"></i><?php esc_html_e( 'Save', 's2w-import-shopify-to-woocommerce' ) ?>
                        </button>
                    </p>
                </form>
            </div>
			<?php
		}

		/**
		 * Background process that pulls latest data from Shopify and handle orders sync
		 */
		public function background_process() {
			self::$get_data_to_update = new WP_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_Process_Cron_Update_Orders_Get_Data();
			self::$update_orders      = new WP_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_Process_Cron_Update_Orders();
		}

		/**
		 * Cancel sync
		 */
		public function admin_init() {
			if ( isset( $_REQUEST['s2w_cron_update_orders_cancel'] ) && $_REQUEST['s2w_cron_update_orders_cancel'] ) {
				self::$get_data_to_update->kill_process();
				self::$update_orders->kill_process();
				wp_safe_redirect( @remove_query_arg( 's2w_cron_update_orders_cancel' ) );
				exit;
			}
		}

		/**
		 * Notice of "Cron Orders Sync" status
		 */
		public function admin_notices() {
			if ( self::$get_data_to_update->is_downloading() || self::$update_orders->is_downloading() ) {
				?>
                <div class="updated">
                    <p>
						<?php esc_html_e( 'S2W - Import Shopify to WooCommerce: "Cron Orders Sync" is running in the background.', 's2w-import-shopify-to-woocommerce' ) ?>
                    </p>
                </div>
				<?php
			} else {
				$complete = false;
				if ( get_transient( 's2w_process_cron_update_orders_complete' ) ) {
					$complete = true;
					delete_transient( 's2w_process_cron_update_orders_complete' );
				}
				if ( get_transient( 's2w_background_processing_cron_update_orders_complete' ) ) {
					$complete = true;
					delete_transient( 's2w_background_processing_cron_update_orders_complete' );
				}
				if ( $complete ) {
					?>
                    <div class="updated">
                        <p>
							<?php esc_html_e( 'S2W - Import Shopify to WooCommerce: "Cron Orders Sync" finishes.', 's2w-import-shopify-to-woocommerce' ) ?>
                        </p>
                    </div>
					<?php
				}
			}
		}

		/**
		 * Query imported Shopify orders then push to queue to fetch data
		 */
		public function cron_update_orders() {
			vi_s2w_init_set();
			$args_order_query = [
				'status'   => self::$settings->get_params( 'cron_update_orders_status' ),
				'limit'    => 250,
				'meta_key' => '_s2w_shopify_order_id',
				'orderby'  => "ID",
				'order'    => "ASC",
				'paginate' => true,
			];

			$now              = current_time( 'timestamp', true );
			$date_range       = self::$settings->get_params( 'cron_update_orders_range' );
			$time_range       = $now - $date_range * 86400;
			if ( $date_range ) {
				$args_order_query['date_created'] = '>=' . $time_range;

			}

			$the_query         = wc_get_orders( $args_order_query );
			$orders            = $the_query->orders ?? [];
			$shopify_order_ids = array( 'data' => array() );

			if ( ! empty( $orders ) ) {
				$max_num_pages = $the_query->max_num_pages;
				foreach ( $orders as $order ) {
					$shopify_order_id                       = $order->get_meta( '_s2w_shopify_order_id', true );
					$order_id                               = $order->get_id();
					$shopify_order_ids['data'][ $order_id ] = $shopify_order_id;
				}

				self::$get_data_to_update->push_to_queue( $shopify_order_ids );
				if ( $max_num_pages > 1 ) {
					for ( $i = 2; $i <= $max_num_pages; $i ++ ) {
						vi_s2w_set_time_limit();
						$args_order_query ['paged']    = $i;
						$the_query         = wc_get_orders( $args_order_query );
						$orders            = $the_query->orders ?? [];
						$shopify_order_ids = array( 'data' => array() );
						if ( ! empty( $orders ) ) {
							foreach ( $orders as $order ) {
								$shopify_order_id                       = $order->get_meta( '_s2w_shopify_order_id', true );
								$order_id                               = $order->get_id();
								$shopify_order_ids['data'][ $order_id ] = $shopify_order_id;

							}
						}
						wp_reset_postdata();

						self::$get_data_to_update->push_to_queue( $shopify_order_ids )->save()->dispatch();
					}
				}
				self::$get_data_to_update->save()->dispatch();
			}
			wp_reset_postdata();
		}

		/**
		 * Save settings
		 */
		public function save_options() {
			global $s2w_settings;
			if ( ! current_user_can( self::get_required_capability() ) ) {
				return;
			}
			if ( ! isset( $_POST['s2w_save_cron_update_orders'] ) ) {
				return;
			}
			if ( ! isset( $_POST['_s2w_nonce'] ) || ! wp_verify_nonce( $_POST['_s2w_nonce'], 's2w_action_nonce' ) ) {
				return;
			}
			$args = self::$settings->get_default_cron_orders_sync_params();
			S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE::fill_params_from_post( $args, 's2w_' );
			$args         = array_merge( self::$settings->get_params(), $args );
			$s2w_settings = $args;
			if ( $args['cron_update_orders'] && ( ! self::$settings->get_params( 'cron_update_orders' ) || $args['cron_update_orders_interval'] != self::$settings->get_params( 'cron_update_orders_interval' ) || $args['cron_update_orders_hour'] != self::$settings->get_params( 'cron_update_orders_hour' ) || $args['cron_update_orders_minute'] != self::$settings->get_params( 'cron_update_orders_minute' ) || $args['cron_update_orders_second'] != self::$settings->get_params( 'cron_update_orders_second' ) ) ) {
				if ( $args['validate'] ) {
					$gmt_offset = intval( get_option( 'gmt_offset' ) );
					$this->unschedule_event();
					$schedule_time_local = strtotime( 'today' ) + HOUR_IN_SECONDS * abs( $args['cron_update_orders_hour'] ) + MINUTE_IN_SECONDS * abs( $args['cron_update_orders_minute'] ) + $args['cron_update_orders_second'];
					if ( $gmt_offset < 0 ) {
						$schedule_time_local -= DAY_IN_SECONDS;
					}
					$schedule_time = $schedule_time_local - HOUR_IN_SECONDS * $gmt_offset;
					if ( $schedule_time < time() ) {
						$schedule_time += DAY_IN_SECONDS;
					}
					/*Call here to apply new interval to cron_schedules filter when calling method wp_schedule_event*/
					self::$settings = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_instance( true );
					$schedules      = wp_get_schedules();
					$recurrence     = 's2w_cron_update_orders_interval';
					if ( ! isset( $schedules[ $recurrence ] ) ) {
						/*In case 'cron_schedules' filter is removed, happened to one customer*/
						$recurrence = 'daily';
					}
					$schedule = wp_schedule_event( $schedule_time, $recurrence, 's2w_cron_update_orders' );

					if ( $schedule !== false ) {
						$this->next_schedule = $schedule_time;
					} else {
						$this->next_schedule = '';
					}
				} else {
					$args['cron_update_products'] = '';
					$args['cron_update_orders']   = '';
					self::$settings               = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_instance( true );
					$this->unschedule_event();
				}
			} else {
				self::$settings = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_instance( true );
				if ( ! $args['cron_update_orders'] ) {
					$this->unschedule_event();
				}
			}

			update_option( 's2w_params', $args );
		}

		/**
		 * Unschedule when disabled or need rescheduling
		 */
		public function unschedule_event() {
			wp_unschedule_hook( 's2w_cron_update_orders' );
			$this->next_schedule = '';
			self::$get_data_to_update->kill_process();
			self::$update_orders->kill_process();
		}

		/**
		 * Enqueue
		 *
		 * @param $page
		 */
		public function admin_enqueue_script( $page ) {
			if ( $page === 'shopify-to-woo_page_s2w-import-shopify-to-woocommerce-cron-update-orders' ) {
				S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE::enqueue_3rd_library( array(
					'accordion',
					'menu',
					'progress',
					'tab',
					'step',
					'sortable',
				), true );
				wp_enqueue_style( 's2w-import-shopify-to-woocommerce-cron-update-orders', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'cron-update-orders.css' );
				wp_enqueue_script( 's2w-import-shopify-to-woocommerce-cron-update-orders', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_JS . 'cron-update-orders.js', array( 'jquery' ), VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_VERSION );
			}
		}

		/**
		 * Required capability
		 *
		 * @return mixed|void
		 */
		private static function get_required_capability() {
			return apply_filters( 'vi_s2w_admin_sub_menu_capability', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_required_capability( 'cron_orders' ), 's2w-import-shopify-to-woocommerce-cron-update-orders' );
		}
	}
}
