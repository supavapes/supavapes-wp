<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Sync products using cron
 */
if ( ! class_exists( 'S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Cron_Update_Products' ) ) {
	class S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Cron_Update_Products {
		protected static $settings;
		public static $update_products;
		public static $get_data_to_update;
		protected $next_schedule;

		public function __construct() {
			self::$settings = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_instance();
			add_action( 'plugins_loaded', array( $this, 'background_process' ) );
			add_action( 'admin_init', array( $this, 'admin_init' ) );
			add_action( 'admin_init', array( $this, 'save_options' ) );
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_script' ) );
			add_action( 'admin_menu', array( $this, 'admin_menu' ), 15 );
			add_action( 's2w_cron_update_products', array( $this, 'cron_update_products' ) );
			add_filter( 'cron_schedules', array( $this, 'cron_schedules' ) );
			$this->next_schedule = wp_next_scheduled( 's2w_cron_update_products' );
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
		 * Cron interval
		 *
		 * @param $schedules
		 *
		 * @return mixed
		 */
		public function cron_schedules( $schedules ) {
			$schedules['s2w_cron_update_products_interval'] = array(
				'interval' => 86400 * absint( self::$settings->get_params( 'cron_update_products_interval' ) ),
				'display'  => __( 'Cron Products Sync', 's2w-import-shopify-to-woocommerce' ),
			);

			return $schedules;
		}

		/**
		 * Add menu
		 */
		public function admin_menu() {
			add_submenu_page( 's2w-import-shopify-to-woocommerce',
				esc_html__( 'Cron Products Sync', 's2w-import-shopify-to-woocommerce' ),
				esc_html__( 'Cron Products Sync', 's2w-import-shopify-to-woocommerce' ),
				self::get_required_capability(), 's2w-import-shopify-to-woocommerce-cron-update-products', array(
					$this,
					'page_callback'
				)
			);
		}

		/**
		 * Cron Products Sync page
		 */
		public function page_callback() {
			?>
            <div class="wrap">
                <h2><?php esc_html_e( 'Cron Products Sync', 's2w-import-shopify-to-woocommerce' ) ?></h2>
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
                            <div class="vi-ui negative message"><?php esc_html_e( 'Cron Products Sync is currently DISABLED', 's2w-import-shopify-to-woocommerce' );; ?></div>
							<?php
						}
						?>

                        <table class="form-table">
                            <tbody>
                            <tr>
                                <th>
                                    <label for="<?php echo esc_attr( self::set( 'cron_update_products' ) ) ?>"><?php esc_html_e( 'Enable cron', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                </th>
                                <td>
                                    <div class="vi-ui toggle checkbox checked">
                                        <input type="checkbox"
                                               name="<?php echo esc_attr( self::set( 'cron_update_products', true ) ) ?>"
                                               id="<?php echo esc_attr( self::set( 'cron_update_products' ) ) ?>"
                                               value="1" <?php checked( self::$settings->get_params( 'cron_update_products' ), '1' ) ?>>
                                        <label for="<?php echo esc_attr( self::set( 'cron_update_products' ) ) ?>"><?php esc_html_e( 'Automatically sync Shopify products', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="<?php echo esc_attr( self::set( 'cron_update_products_force_sync' ) ) ?>"><?php esc_html_e( 'Force sync', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                </th>
                                <td>
                                    <div class="vi-ui toggle checkbox checked">
                                        <input type="checkbox"
                                               name="<?php echo esc_attr( self::set( 'cron_update_products_force_sync', true ) ) ?>"
                                               id="<?php echo esc_attr( self::set( 'cron_update_products_force_sync' ) ) ?>"
                                               value="1" <?php checked( self::$settings->get_params( 'cron_update_products_force_sync' ), '1' ) ?>>
                                        <label for="<?php echo esc_attr( self::set( 'cron_update_products_force_sync' ) ) ?>"><?php esc_html_e( 'Sync products even there are no changes in data in comparison to the previous sync', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="<?php echo esc_attr( self::set( 'cron_update_products_interval' ) ) ?>"><?php esc_html_e( 'Run cron every', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                </th>
                                <td>
                                    <div class="vi-ui right labeled input">
                                        <input type="number" min="1"
                                               required
                                               name="<?php echo esc_attr( self::set( 'cron_update_products_interval', true ) ) ?>"
                                               id="<?php echo esc_attr( self::set( 'cron_update_products_interval' ) ) ?>"
                                               value="<?php echo esc_attr( self::$settings->get_params( 'cron_update_products_interval' ) ) ?>">
                                        <label for="<?php echo esc_attr( self::set( 'cron_update_products_interval' ) ) ?>"
                                               class="vi-ui label"><?php esc_html_e( 'Day(s)', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                    </div>
                                    <p><?php esc_html_e( 'You should run cron for less than 300 products per day', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="<?php echo esc_attr( self::set( 'cron_update_products_hour' ) ) ?>"><?php esc_html_e( 'Run cron at', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                </th>
                                <td>
                                    <div class="equal width fields">
                                        <div class="field">
                                            <div class="vi-ui right labeled input">
                                                <input type="number" min="0" max="23"
                                                       required
                                                       name="<?php echo esc_attr( self::set( 'cron_update_products_hour', true ) ) ?>"
                                                       id="<?php echo esc_attr( self::set( 'cron_update_products_hour' ) ) ?>"
                                                       value="<?php echo esc_attr( self::$settings->get_params( 'cron_update_products_hour' ) ) ?>">
                                                <label for="<?php echo esc_attr( self::set( 'cron_update_products_hour' ) ) ?>"
                                                       class="vi-ui label"><?php esc_html_e( 'Hour', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                            </div>
                                        </div>
                                        <div class="field">
                                            <div class="vi-ui right labeled input">
                                                <input type="number" min="0" max="59"
                                                       required
                                                       name="<?php echo esc_attr( self::set( 'cron_update_products_minute', true ) ) ?>"
                                                       id="<?php echo esc_attr( self::set( 'cron_update_products_minute' ) ) ?>"
                                                       value="<?php echo esc_attr( self::$settings->get_params( 'cron_update_products_minute' ) ) ?>">
                                                <label for="<?php echo esc_attr( self::set( 'cron_update_products_minute' ) ) ?>"
                                                       class="vi-ui label"><?php esc_html_e( 'Minute', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                            </div>
                                        </div>
                                        <div class="field">
                                            <div class="vi-ui right labeled input">
                                                <input type="number" min="0" max="59"
                                                       required
                                                       name="<?php echo esc_attr( self::set( 'cron_update_products_second', true ) ) ?>"
                                                       id="<?php echo esc_attr( self::set( 'cron_update_products_second' ) ) ?>"
                                                       value="<?php echo esc_attr( self::$settings->get_params( 'cron_update_products_second' ) ) ?>">
                                                <label for="<?php echo esc_attr( self::set( 'cron_update_products_second' ) ) ?>"
                                                       class="vi-ui label"><?php esc_html_e( 'Second', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                            </div>
                                        </div>
                                    </div>

                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="<?php echo esc_attr( self::set( 'cron_update_products_status' ) ) ?>"><?php esc_html_e( 'Only sync products with status:', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                </th>
                                <td>
                                    <select class="vi-ui fluid dropdown"
                                            name="<?php echo esc_attr( self::set( 'cron_update_products_status', true ) ) ?>[]"
                                            multiple="multiple"
                                            id="<?php echo esc_attr( self::set( 'cron_update_products_status' ) ) ?>">
										<?php
										$cron_update_products_status = self::$settings->get_params( 'cron_update_products_status' );
										$options                     = array(
											'publish' => esc_html__( 'Publish', 's2w-import-shopify-to-woocommerce' ),
											'pending' => esc_html__( 'Pending', 's2w-import-shopify-to-woocommerce' ),
											'draft'   => esc_html__( 'Draft', 's2w-import-shopify-to-woocommerce' ),
										);
										foreach ( $options as $option_k => $option_v ) {
											?>
                                            <option value="<?php echo esc_attr( $option_k ) ?>"<?php if ( in_array( $option_k, $cron_update_products_status ) )
												echo esc_attr( 'selected' ) ?>><?php echo esc_html( $option_v ); ?></option>
											<?php
										}
										?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="<?php echo esc_attr( self::set( 'cron_update_products_categories' ) ) ?>"><?php esc_html_e( 'Only sync products of these categories:', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                </th>
                                <td>
                                    <select class="search-category"
                                            id="<?php echo esc_attr( self::set( 'cron_update_products_categories' ) ) ?>"
                                            name="<?php echo esc_attr( self::set( 'cron_update_products_categories', true ) ) ?>[]"
                                            multiple="multiple">
										<?php
										$cron_update_products_categories = self::$settings->get_params( 'cron_update_products_categories' );
										if ( is_array( $cron_update_products_categories ) && count( $cron_update_products_categories ) ) {
											foreach ( $cron_update_products_categories as $category_id ) {
												$category = get_term( $category_id );
												if ( $category ) {
													?>
                                                    <option value="<?php echo esc_attr( $category_id ) ?>"
                                                            selected><?php echo esc_html( $category->name ); ?></option>
													<?php
												}
											}
										}
										?>
                                    </select>
                                    <p class="description"><?php esc_html_e( 'Leave blank to sync products from all categories', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="<?php echo esc_attr( self::set( 'cron_update_products_options' ) ) ?>"><?php esc_html_e( 'Select options to sync', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                </th>
                                <td>
                                    <select class="vi-ui fluid dropdown"
                                            name="<?php echo esc_attr( self::set( 'cron_update_products_options', true ) ) ?>[]"
                                            multiple="multiple"
                                            id="<?php echo esc_attr( self::set( 'cron_update_products_options' ) ) ?>">
										<?php
										$cron_update_products_options = self::$settings->get_params( 'cron_update_products_options' );
										$all_options                  = S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Update_Products::get_supported_options();
										unset( $all_options['metafields'] );
										foreach ( $all_options as $all_option_k => $all_option_v ) {
											?>
                                            <option value="<?php echo esc_attr( $all_option_k ) ?>" <?php if ( in_array( $all_option_k, $cron_update_products_options ) )
												echo esc_attr( 'selected' ) ?>><?php echo esc_html( $all_option_v ); ?></option>
											<?php
										}
										?>
                                    </select>
                                    <p class="description s2w-barcode-description"><?php echo wp_kses_post( S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Update_Products::get_barcode_sync_description() ) ?></p>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <p>
                        <button type="submit" class="vi-ui labeled icon button primary"
                                name="s2w_save_cron_update_products"><i
                                    class="icon save"></i><?php esc_html_e( 'Save', 's2w-import-shopify-to-woocommerce' ) ?>
                        </button>
                    </p>
                </form>
            </div>
			<?php
		}

		/**
		 * Background process that pulls latest data from Shopify and handle products sync
		 */
		public function background_process() {
			self::$get_data_to_update = new WP_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_Process_Cron_Update_Products_Get_Data();
			self::$update_products    = new WP_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_Process_Cron_Update_Products();
		}

		/**
		 * Cancel products sync
		 */
		public function admin_init() {
			if ( isset( $_REQUEST['s2w_cron_update_products_cancel'] ) && $_REQUEST['s2w_cron_update_products_cancel'] ) {
				self::$get_data_to_update->kill_process();
				self::$update_products->kill_process();
				wp_safe_redirect( @remove_query_arg( 's2w_cron_update_products_cancel' ) );
				exit;
			}
		}

		/**
		 * Notice of "Cron Products Sync" status
		 */
		public function admin_notices() {
			if ( self::$get_data_to_update->is_downloading() || self::$update_products->is_downloading() ) {
				?>
                <div class="updated">
                    <p>
						<?php esc_html_e( 'S2W - Import Shopify to WooCommerce: "Cron Products Sync" is running in the background.', 's2w-import-shopify-to-woocommerce' ) ?>
                    </p>
                </div>
				<?php
			} else {
				$complete = false;
				if ( get_transient( 's2w_process_cron_update_products_complete' ) ) {
					delete_transient( 's2w_process_cron_update_products_complete' );
					$complete = true;
				}
				if ( get_transient( 's2w_background_processing_cron_update_products_complete' ) ) {
					delete_transient( 's2w_background_processing_cron_update_products_complete' );
					$complete = true;
				}
				if ( $complete ) {
					?>
                    <div class="updated">
                        <p>
							<?php esc_html_e( 'S2W - Import Shopify to WooCommerce: "Cron Products Sync" finished.', 's2w-import-shopify-to-woocommerce' ) ?>
                        </p>
                    </div>
					<?php
				}
			}
		}

		/**
		 * Query imported Shopify products then push to queue to fetch data
		 */
		public function cron_update_products() {
			vi_s2w_init_set();
			if ( ! self::$get_data_to_update->is_downloading() && self::$get_data_to_update->is_queue_empty() ) {
				$args       = array(
					'post_type'      => 'product',
					'post_status'    => self::$settings->get_params( 'cron_update_products_status' ),
					'posts_per_page' => 250,
					'meta_key'       => '_shopify_product_id',
					'orderby'        => 'ID',
					'order'          => 'ASC',
					'fields'         => 'ids',
					'suppress_filters' => true,/*Remove filter by language*/
				);
				$categories = self::$settings->get_params( 'cron_update_products_categories' );
				if ( is_array( $categories ) && count( $categories ) ) {
					$args['tax_query'] = array(
						'relation' => 'AND',
						array(
							'taxonomy' => 'product_cat',
							'field'    => 'ID',
							'terms'    => $categories,
							'operator' => 'IN'
						)
					);
				}
				$the_query           = new WP_Query( $args );
				$shopify_product_ids = array( 'data' => array() );
				if ( $the_query->have_posts() ) {
					$max_num_pages = $the_query->max_num_pages;
					$dispatch      = false;
					foreach ( $the_query->posts as $product_id ) {
						$shopify_product_id                         = get_post_meta( $product_id, '_shopify_product_id', true );
						$shopify_product_ids['data'][ $product_id ] = $shopify_product_id;
					}
					wp_reset_postdata();
					self::$get_data_to_update->push_to_queue( $shopify_product_ids );
					$dispatch = true;
					if ( $max_num_pages > 1 ) {
						for ( $i = 2; $i <= $max_num_pages; $i ++ ) {
							vi_s2w_set_time_limit();
							$args ['paged']      = $i;
							$the_query           = new WP_Query( $args );
							$shopify_product_ids = array( 'data' => array() );
							if ( $the_query->have_posts() ) {
								foreach ( $the_query->posts as $product_id ) {
									$shopify_product_id                         = get_post_meta( $product_id, '_shopify_product_id', true );
									$shopify_product_ids['data'][ $product_id ] = $shopify_product_id;
								}
							}
							wp_reset_postdata();
							self::$get_data_to_update->push_to_queue( $shopify_product_ids );
							$dispatch = true;
						}
					}
					if($dispatch){
						self::$get_data_to_update->save()->dispatch();
					}
				}
				wp_reset_postdata();
			}
		}

		/**
		 * Save settings
		 */
		public function save_options() {
			global $s2w_settings;
			if ( ! current_user_can( self::get_required_capability() ) ) {
				return;
			}
			if ( ! isset( $_POST['s2w_save_cron_update_products'] ) ) {
				return;
			}
			if ( ! isset( $_POST['_s2w_nonce'] ) || ! wp_verify_nonce( $_POST['_s2w_nonce'], 's2w_action_nonce' ) ) {
				return;
			}
			$args = self::$settings->get_default_cron_products_sync_params();
			S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE::fill_params_from_post( $args, 's2w_' );
			$args         = array_merge( self::$settings->get_params(), $args );
			$s2w_settings = $args;
			if ( $args['cron_update_products'] && ( ! self::$settings->get_params( 'cron_update_products' ) || $args['cron_update_products_interval'] != self::$settings->get_params( 'cron_update_products_interval' ) || $args['cron_update_products_hour'] != self::$settings->get_params( 'cron_update_products_hour' ) || $args['cron_update_products_minute'] != self::$settings->get_params( 'cron_update_products_minute' ) || $args['cron_update_products_second'] != self::$settings->get_params( 'cron_update_products_second' ) ) ) {
				if ( $args['validate'] ) {
					$gmt_offset = intval( get_option( 'gmt_offset' ) );
					$this->unschedule_event();
					$schedule_time_local = strtotime( 'today' ) + HOUR_IN_SECONDS * abs( $args['cron_update_products_hour'] ) + MINUTE_IN_SECONDS * abs( $args['cron_update_products_minute'] ) + $args['cron_update_products_second'];
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
					$recurrence     = 's2w_cron_update_products_interval';
					if ( ! isset( $schedules[ $recurrence ] ) ) {
						/*In case 'cron_schedules' filter is removed, happened to one customer*/
						$recurrence = 'daily';
					}
					$schedule = wp_schedule_event( $schedule_time, $recurrence, 's2w_cron_update_products' );
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
				if ( ! $args['cron_update_products'] ) {
					$this->unschedule_event();
				}
			}
			update_option( 's2w_params', $args );
		}

		/**
		 * Unschedule when disabled or need rescheduling
		 */
		public function unschedule_event() {
			wp_unschedule_hook( 's2w_cron_update_products' );
			$this->next_schedule = '';
			self::$get_data_to_update->kill_process();
			self::$update_products->kill_process();
		}

		/**
		 * @param $page
		 */
		public function admin_enqueue_script( $page ) {
			if ( $page === 'shopify-to-woo_page_s2w-import-shopify-to-woocommerce-cron-update-products' ) {
				S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE::enqueue_3rd_library( array(
					'accordion',
					'menu',
					'progress',
					'tab',
					'step',
					'sortable',
				), true );
				wp_enqueue_style( 's2w-import-shopify-to-woocommerce-cron-update-products', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'cron-update-products.css' );
				wp_enqueue_script( 's2w-import-shopify-to-woocommerce-cron-update-products', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_JS . 'cron-update-products.js', array( 'jquery' ), VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_VERSION );
				wp_localize_script( 's2w-import-shopify-to-woocommerce-cron-update-products', 's2w_params_admin_cron_update_products', array(
					'url'        => admin_url( 'admin-ajax.php' ),
					'_s2w_nonce' => wp_create_nonce( 's2w_action_nonce' ),
				) );
			}
		}

		/**
		 * Required capability
		 *
		 * @return mixed|void
		 */
		private static function get_required_capability() {
			return apply_filters( 'vi_s2w_admin_sub_menu_capability', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_required_capability( 'cron_products' ), 's2w-import-shopify-to-woocommerce-cron-update-products' );
		}
	}
}