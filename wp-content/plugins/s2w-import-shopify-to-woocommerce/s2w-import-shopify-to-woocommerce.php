<?php
/**
 * Plugin Name: S2W - Import Shopify to WooCommerce Premium
 * Plugin URI: https://villatheme.com/extensions/s2w-import-shopify-to-woocommerce
 * Description: Migrate products, customers, orders... from Shopify store to WooCommerce
 * Version: 1.2.5
 * Author: VillaTheme
 * Author URI: https://villatheme.com
 * Text Domain: s2w-import-shopify-to-woocommerce
 * Domain Path: /languages
 * Copyright 2019-2024 VillaTheme.com. All rights reserved.
 * Tested up to: 6.5
 * WC tested up to: 8.6
 * Requires PHP: 7.0
 * Requires at least: 5.0
 **/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
define( 'VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_VERSION', '1.2.5' );
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
define( 'VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DIR', WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 's2w-import-shopify-to-woocommerce' . DIRECTORY_SEPARATOR );
define( 'VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_INCLUDES', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DIR . 'includes' . DIRECTORY_SEPARATOR );
if ( is_file( VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_INCLUDES . 'class-s2w-error-images-table.php' ) ) {
	require_once VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_INCLUDES . 'class-s2w-error-images-table.php';
}


if ( is_file( VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_INCLUDES . 'define.php' ) ) {
	require_once VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_INCLUDES . 'define.php';
}


use \Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore as CustomersDataStore;
use \Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore as OrdersStatsDataStore;
use \Automattic\WooCommerce\Admin\API\Reports\Cache as ReportsCache;

if ( ! class_exists( 'S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE' ) ) {
	class S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE {
		protected static $settings;
		protected $is_page;
		protected $request;
		protected $process;
		public static $process_new;
		protected static $process_post_image;

		public function __construct() {
			register_activation_hook( __FILE__, array( __CLASS__, 'register_activation_hook' ) );
			add_action( 'before_woocommerce_init', function () {
				if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
					\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
				}
			} );
			vi_s2w_init_set();
			self::$settings = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_instance();
			$this->is_page  = false;
			add_action( 'plugins_loaded', array( $this, 'villaTheme_require_environment' ) );
			add_action( 'init', array( $this, 'init' ) );
			add_action( 'plugins_loaded', array( $this, 'background_process' ) );
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			add_action( 'admin_init', array( $this, 'delete_import_history' ) );
			add_action( 'admin_init', array( $this, 'check_key' ) );
			add_action( 'admin_init', array( $this, 'save_and_check_key' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 11 );
			add_action( 'wp_ajax_s2w_save_settings', array( $this, 'save_settings' ) );
			add_action( 'wp_ajax_s2w_save_settings_products_options', array( $this, 'save_settings_products_options' ) );
			add_action( 'wp_ajax_s2w_save_settings_orders_options', array( $this, 'save_settings_orders_options' ) );
			add_action( 'wp_ajax_s2w_save_settings_customers_options', array( $this, 'save_settings_customers_options' ) );
			add_action( 'wp_ajax_s2w_save_settings_coupons_options', array( $this, 'save_settings_coupons_options' ) );
			add_action( 'wp_ajax_s2w_import_shopify_to_woocommerce', array( $this, 'sync' ) );
			add_action( 'wp_ajax_s2w_search_cate', array( $this, 'search_cate' ) );
			add_action( 'wp_ajax_s2w_search_user', array( $this, 'search_user' ) );
			add_filter(
				'plugin_action_links_s2w-import-shopify-to-woocommerce/s2w-import-shopify-to-woocommerce.php', array(
					$this,
					'settings_link'
				)
			);
			add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts' ) );
			add_action( 'woocommerce_order_list_table_restrict_manage_orders', array( $this, 'restrict_manage_posts' ) );
			add_action( 'parse_query', array( $this, 'parse_query' ) );
			add_filter( 'woocommerce_shop_order_search_fields', array( $this, 'woocommerce_shop_order_search_order_total' ) );
			add_filter( 'woocommerce_order_number', array( $this, 'woocommerce_order_number' ), 10, 2 );
			add_action( 'admin_init', array( $this, 'update_data_new_version' ) );
			add_action( 's2w_bulk_import_item_exists', array( $this, 'invalidate_report_cache' ), 10, 2 );
			add_action( 's2w_bulk_import_item_successfully', array( $this, 'invalidate_report_cache' ), 10, 2 );
			register_deactivation_hook( __FILE__, array( __CLASS__, 'vi_s2w_register_deactivation_hook' ) );

		}

		public function villaTheme_require_environment() {
			if ( ! class_exists( 'VillaTheme_Require_Environment' ) ) {
				include_once VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_INCLUDES . 'support.php';
			}
			$environment = new VillaTheme_Require_Environment( [
					'plugin_name'     => 'S2W - Import Shopify to WooCommerce Premium',
					'php_version'     => '7.0',
					'wp_version'      => '5.0',
					'wc_version'      => '6.0',
					'require_plugins' => [
						[
							'slug' => 'woocommerce',
							'name' => 'WooCommerce',
						],
					]
				]
			);
			if ( $environment->has_error() ) {
				return;
			}
		}

		/**
		 * Check if woocommerce analytics option is enabled
		 * Must check before using OrdersStatsDataStore, CustomersDataStore and ReportsCache
		 *
		 * @return bool
		 */
		private static function woocommerce_analytics_enabled() {
			return get_option( 'woocommerce_analytics_enabled' ) !== 'no';
		}

		/**
		 * @param $import_id
		 * @param $step
		 */
		public function invalidate_report_cache( $import_id, $step ) {
			if ( $step === 'orders' ) {
				if ( $import_id && self::woocommerce_analytics_enabled() ) {
					OrdersStatsDataStore::sync_order( $import_id );
					CustomersDataStore::sync_order_customer( $import_id );
					ReportsCache::invalidate();
				}
			}
		}

		/**
		 *
		 */
		public static function vi_s2w_register_deactivation_hook() {
			$settings                        = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_instance();
			$options                         = $settings->get_params();
			$options['cron_update_products'] = '';
			$options['cron_update_orders']   = '';
			update_option( 's2w_params', $options );
			wp_clear_scheduled_hook( 's2w_cron_update_products' );
			wp_clear_scheduled_hook( 's2w_cron_update_orders' );
		}

		/**
		 *
		 */
		public static function register_activation_hook() {
			S2W_Error_Images_Table::create_table();
			S2W_Error_Images_Table::add_column( 'image_id' );
			S2W_Error_Images_Table::modify_column( 'image_id', 'varchar(200)' );
		}

		protected static function set( $name, $set_name = false ) {
			return VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::set( $name, $set_name );
		}

		/**
		 *
		 */
		public function update_data_new_version() {
			if ( ! get_option( 'vi_s2w_update_data_new_version' ) ) {
				$files = glob( VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CACHE . '*.txt' );
				VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::delete_files( $files );
				$dirs = glob( VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CACHE . '*', GLOB_ONLYDIR );
				if ( is_array( $dirs ) && count( $dirs ) ) {
					$domain       = self::$settings->get_params( 'domain' );
					$access_token = self::$settings->get_params( 'access_token' );
					$api_key      = self::$settings->get_params( 'api_key' );
					$api_secret   = self::$settings->get_params( 'api_secret' );
					$new_dir_name = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_cache_path( $domain, $access_token, $api_key, $api_secret );
					if ( ( $domain && $api_key && $api_secret ) || $access_token ) {
						$shop_name_length = strlen( $domain );
						foreach ( $dirs as $dir ) {
							$dir_name = substr( $dir, ( strlen( $dir ) - $shop_name_length ), $shop_name_length );
							if ( $dir_name === $domain ) {
								if ( $new_dir_name !== $dir ) {
									if ( ! @rename( $dir, $new_dir_name ) ) {
										VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::deleteDir( $dir );
									}
								}
							} else {
								VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::deleteDir( $dir );
							}
						}
					} else {
						foreach ( $dirs as $dir ) {
							VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::deleteDir( $dir );
						}
					}
				}
				update_option( 'vi_s2w_update_data_new_version', time() );
			}
		}

		/**
		 * @param $order_number
		 * @param $order WC_Order
		 *
		 * @return mixed
		 */
		public function woocommerce_order_number( $order_number, $order ) {
			if ( $order ) {
				$order_id       = $order->get_id();
				$s_order_number = get_post_meta( $order_id, '_s2w_shopify_order_number', true );
				if ( $s_order_number ) {
					$order_number = $s_order_number;
				}
			}

			return $order_number;
		}

		/**
		 * @param $search_fields
		 *
		 * @return array
		 */
		public function woocommerce_shop_order_search_order_total( $search_fields ) {
			$search_fields[] = '_s2w_shopify_order_number';

			return $search_fields;
		}

		/**
		 *
		 */
		public function load_plugin_textdomain() {
			$locale = apply_filters( 'plugin_locale', get_locale(), 's2w-import-shopify-to-woocommerce' );
			load_textdomain( 's2w-import-shopify-to-woocommerce', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_LANGUAGES . "s2w-import-shopify-to-woocommerce-$locale.mo" );
			load_plugin_textdomain( 's2w-import-shopify-to-woocommerce', false, VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_LANGUAGES );
		}

		/**
		 *
		 */
		public function init() {
			$this->load_plugin_textdomain();
			if ( class_exists( 'VillaTheme_Support_Pro' ) ) {
				new VillaTheme_Support_Pro(
					array(
						'support'   => 'https://villatheme.com/supports/forum/plugins/import-shopify-to-woocommerce/',
						'docs'      => 'http://docs.villatheme.com/?item=import-shopify-to-woocommerce',
						'review'    => 'https://codecanyon.net/downloads',
						'css'       => VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS,
						'image'     => VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_IMAGES,
						'slug'      => 's2w-import-shopify-to-woocommerce',
						'menu_slug' => 's2w-import-shopify-to-woocommerce',
						'version'   => VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_VERSION
					)
				);
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
		 * @param $query
		 */
		public function parse_query( $query ) {
			global $pagenow;
			$post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( $_GET['post_type'] ) : '';
			$page      = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
			if (
				( ( $pagenow === 'edit.php' && $post_type === 'shop_order' ) || ( $page == 'wc-orders' ) ) &&
				isset( $_GET['s2w_import_orders'] ) &&
				$_GET['s2w_import_orders']
			) {
				$q_vars = &$query->query_vars;
				if ( empty( $q_vars['meta_query'] ) ) {
					$q_vars['meta_query'] = array(
						'relation' => 'AND',
						array(
							'key'     => '_s2w_shopify_order_id',
							'compare' => 'EXISTS'
						),
					);
				} else {
					$q_vars['meta_query']['relation'] = 'AND';
					$q_vars['meta_query'][]           = array(
						'key'     => '_s2w_shopify_order_id',
						'compare' => 'EXISTS'
					);
				}
			}
		}

		/**
		 * Filter orders imported by S2W
		 */
		public function restrict_manage_posts() {
			global $typenow;
			$page = isset( $_REQUEST['page'] ) ? sanitize_text_field( $_REQUEST['page'] ) : '';
			if ( in_array( $typenow, wc_get_order_types( 'view-orders' ), true ) || ( $page === 'wc-orders' ) ) {
				?>
                <span style="padding: 7px;background: white;line-height: 33px;margin: 4px;">
                     <input style="height: 16px;" type="checkbox"
                            name="<?php echo esc_attr( self::set( 'import-orders', true ) ) ?>"
                            id="<?php echo esc_attr( self::set( 'import-orders' ) ) ?>"
                            value="1" <?php if ( isset( $_GET['s2w_import_orders'] ) && $_GET['s2w_import_orders'] ) {
	                     echo esc_attr( 'checked' );
                     } ?>>
                <label for="<?php echo esc_attr( self::set( 'import-orders' ) ) ?>"><?php esc_html_e( 'Imported by S2W', 's2w-import-shopify-to-woocommerce' ) ?></label>
                </span>
				<?php
			}
		}

		public function check_key() {
			/**
			 * Check update
			 */
			if ( class_exists( 'VillaTheme_Plugin_Check_Update' ) ) {
				$setting_url = admin_url( 'admin.php?page=s2w-import-shopify-to-woocommerce' );
				$key         = self::$settings->get_params( 'auto_update_key' );
				new VillaTheme_Plugin_Check_Update (
					VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_VERSION,                    // current version
					'https://villatheme.com/wp-json/downloads/v3',  // update path
					's2w-import-shopify-to-woocommerce/s2w-import-shopify-to-woocommerce.php',                  // plugin file slug
					's2w-import-shopify-to-woocommerce', '25122', $key, $setting_url
				);
				new VillaTheme_Plugin_Updater( 's2w-import-shopify-to-woocommerce/s2w-import-shopify-to-woocommerce.php', 's2w-import-shopify-to-woocommerce', $setting_url );
			}
		}


		/**
		 * Delete import history and cache files
		 */
		public function delete_import_history() {
			global $pagenow;
			if ( ! current_user_can( self::get_required_capability() ) ) {
				return;
			}
			if ( $pagenow === 'admin.php' && isset( $_GET['page'] ) && $_GET['page'] === 's2w-import-shopify-to-woocommerce' && isset( $_POST['s2w_delete_history'] ) ) {
				$domain       = self::$settings->get_params( 'domain' );
				$access_token = self::$settings->get_params( 'access_token' );
				$api_key      = self::$settings->get_params( 'api_key' );
				$api_secret   = self::$settings->get_params( 'api_secret' );
				foreach ( self::get_supported_elements() as $type => $type_name ) {
					if ( isset( $_POST[ $type ] ) && $_POST[ $type ] ) {
						self::delete_cache_and_history( $type, $domain, $access_token, $api_key, $api_secret );
					}
				}

				self::$settings = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_instance();
			}
		}

		/**
		 * @param $links
		 *
		 * @return mixed
		 */
		public function settings_link( $links ) {
			$settings_link = '<a href="' . esc_url( admin_url( 'admin.php' ) ) . '?page=s2w-import-shopify-to-woocommerce" title="' . esc_attr__( 'Settings', 's2w-import-shopify-to-woocommerce' ) . '">' . esc_html__( 'Settings', 's2w-import-shopify-to-woocommerce' ) . '</a>';
			array_unshift( $links, $settings_link );

			return $links;
		}

		/**
		 * Ajax categories search
		 */
		public function search_cate() {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				return;
			}
			self::check_ajax_referrer();
			ob_start();
			$keyword = filter_input( INPUT_GET, 'keyword', FILTER_SANITIZE_STRING );
			if ( ! $keyword ) {
				$keyword = filter_input( INPUT_POST, 'keyword', FILTER_SANITIZE_STRING );
			}
			if ( empty( $keyword ) ) {
				die();
			}
			$categories = get_terms(
				array(
					'taxonomy'   => 'product_cat',
					'orderby'    => 'name',
					'order'      => 'ASC',
					'search'     => $keyword,
					'number'     => 100,
					'hide_empty' => false
				)
			);
			$items      = array();
			if ( count( $categories ) ) {
				foreach ( $categories as $category ) {
					$item    = array(
						'id'   => $category->term_id,
						'text' => $category->name
					);
					$items[] = $item;
				}
			}
			wp_send_json( $items );
		}

		/**
		 * Ajax users search
		 */
		public function search_user() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			self::check_ajax_referrer();
			ob_start();
			$keyword = filter_input( INPUT_GET, 'keyword', FILTER_SANITIZE_STRING );
			if ( ! $keyword ) {
				$keyword = filter_input( INPUT_POST, 'keyword', FILTER_SANITIZE_STRING );
			}
			if ( empty( $keyword ) ) {
				die();
			}
			$users = get_users(
				array(
					'search'         => "*$keyword*",
					'search_columns' => array(
						'user_login',
						'user_email',
						'user_url',
						'user_nicename',
						'display_name'
					),
					'number'         => 100,
				)
			);
			$items = array();
			if ( count( $users ) ) {
				foreach ( $users as $user ) {
					$item    = array(
						'id'   => $user->ID,
						'text' => "{$user->display_name}(#{$user->ID} - {$user->user_email})"
					);
					$items[] = $item;
				}
			}
			wp_send_json( $items );
		}

		/**
		 * Display message about importing images progress
		 */
		public function admin_notices() {
			if ( self::$process_new->is_downloading() ) {
				?>
                <div class="updated">
                    <h4>
						<?php printf( esc_html__( 'S2W - Import Shopify to WooCommerce: %s images are being imported in the background.', 's2w-import-shopify-to-woocommerce' ), self::$process_new->get_items_left() ) ?>
                    </h4>
                    <div>
						<?php printf( __( 'Please goto <a target="_blank" href="%s">Media</a> and view imported images. If <strong>some images are imported repeatedly and no new images are imported</strong>, please:', 's2w-import-shopify-to-woocommerce' ), esc_url( admin_url( 'upload.php' ) ) ) ?>
                        <ol>
                            <li><?php printf( __( '<strong>Stop importing products immediately</strong>', 's2w-import-shopify-to-woocommerce' ) ) ?></li>
                            <li><?php printf( __( '<a class="s2w-cancel-download-images-button" href="%s">Cancel importing</a></strong>', 's2w-import-shopify-to-woocommerce' ), add_query_arg( array( 's2w_cancel_download_image' => '1', ), $_SERVER['REQUEST_URI'] ) ) ?></li>
                            <li><?php printf( __( 'Contact <strong>support@villatheme.com</strong> or create your ticket at <a target="_blank" href="https://villatheme.com/supports/forum/plugins/import-shopify-to-woocommerce/">https://villatheme.com/supports/forum/plugins/import-shopify-to-woocommerce/</a>', 's2w-import-shopify-to-woocommerce' ) ) ?></li>
                        </ol>
                    </div>
                </div>
				<?php
			} elseif ( ! self::$process_new->is_queue_empty() ) {

				?>
                <div class="updated">
                    <h4>
						<?php printf( esc_html__( 'S2W - Import Shopify to WooCommerce: %s images are in the queue.', 's2w-import-shopify-to-woocommerce' ), self::$process_new->get_items_left() ) ?>
                    </h4>
                    <ol>
                        <li>
							<?php printf( __( 'If the same images are imported again and again, please <strong><a class="s2w-empty-queue-images-button" href="%s">Empty queue</a></strong> and go to Products to update missing images for your products.', 's2w-import-shopify-to-woocommerce' ), esc_url( add_query_arg( array( 's2w_cancel_download_image' => '1', ), $_SERVER['REQUEST_URI'] ) ) ) ?>
                        </li>
                        <li>
							<?php printf( __( 'If products images were importing normally before, please <strong><a class="s2w-start-download-images-button" href="%s">Resume download</a></strong>', 's2w-import-shopify-to-woocommerce' ), add_query_arg( array( 's2w_start_download_image' => '1', ), esc_url( $_SERVER['REQUEST_URI'] ) ) ) ?>
                        </li>
                    </ol>
                </div>
				<?php
			} else {
				$complete = false;
				if ( get_transient( 's2w_process_new_complete' ) ) {
					delete_transient( 's2w_process_new_complete' );
					$complete = true;
				}
				if ( get_transient( 's2w_background_processing_complete' ) ) {//old transient, not used since 1.1.12
					delete_transient( 's2w_background_processing_complete' );
					$complete = true;
				}
				if ( $complete ) {
					?>
                    <div class="updated">
                        <p>
							<?php esc_html_e( 'S2W - Import Shopify to WooCommerce: Product images are imported successfully.', 's2w-import-shopify-to-woocommerce' ) ?>
                        </p>
                    </div>
					<?php
				}
			}
		}

		/**
		 * Import images in background
		 */
		public function background_process() {
			self::$process_new = new WP_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_Process_New();
			if ( ! empty( $_REQUEST['s2w_cancel_download_image'] ) ) {
				delete_transient( 's2w_background_processing_complete' );
				self::$process_new->kill_process();
				wp_safe_redirect( @remove_query_arg( 's2w_cancel_download_image' ) );
				exit;
			} elseif ( ! empty( $_REQUEST['s2w_start_download_image'] ) ) {
				if ( ! self::$process_new->is_queue_empty() ) {
					self::$process_new->dispatch();
				}
				wp_safe_redirect( @remove_query_arg( 's2w_start_download_image' ) );
				exit;
			}
			self::$process_post_image = new WP_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_Process_Post_Image();
		}

		/**
		 * @param $collections
		 * @param $categories
		 */
		protected static function process_category_data( $collections, &$categories ) {
			if ( is_array( $collections ) && count( $collections ) ) {
				foreach ( $collections as $collection ) {
					$category = array(
						'shopify_id'          => $collection['id'],
						'name'                => $collection['title'],
						'shopify_product_ids' => array(),
						'woo_id'              => '',
					);
					$cate     = get_term_by( 'name', $category['name'], 'product_cat' );
					if ( ! $cate ) {
						$cate = wp_insert_term( $category['name'], 'product_cat', array( 'description' => isset( $collection['body_html'] ) ? $collection['body_html'] : '' ) );
						if ( ! is_wp_error( $cate ) ) {
							$cate_id            = isset( $cate['term_id'] ) ? $cate['term_id'] : '';
							$category['woo_id'] = $cate_id;
							if ( $cate_id ) {
								self::set_category_image( $collection, $cate_id );
							}
						}
					} else {
						$category['woo_id'] = $cate->term_id;
						if ( $cate->term_id ) {
							if ( ! get_term_meta( $cate->term_id, 'thumbnail_id', true ) ) {
								self::set_category_image( $collection, $cate->term_id );
							}
							if ( ! empty( $collection['body_html'] ) ) {
								wp_update_term( $cate->term_id, 'product_cat', array( 'description' => $collection['body_html'] ) );
							}
						}
					}
					$categories[] = $category;
				}
			}
		}

		/**
		 * @param $collection
		 * @param $cate_id
		 */
		protected static function set_category_image( $collection, $cate_id ) {
			if ( ! empty( $collection['image'] ) && ! empty( $collection['image']['src'] ) ) {
				$thumb_id = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::download_image( $image_id, $collection['image']['src'], isset( $collection['image']['alt'] ) ? $collection['image']['alt'] : '' );
				if ( $thumb_id && ! is_wp_error( $thumb_id ) ) {
					if ( $image_id ) {
						update_post_meta( $thumb_id, '_s2w_shopify_image_id', $image_id );
					}
					update_term_meta( $cate_id, 'thumbnail_id', $thumb_id );
				}
			}
		}

		/**
		 * @param $domain
		 * @param $access_token
		 * @param $api_key
		 * @param $api_secret
		 * @param $path
		 *
		 * @return array
		 */
		protected function initiate_categories_data( $domain, $access_token, $api_key, $api_secret, $path ) {
			$timeout    = self::$settings->get_params( 'request_timeout' );
			$categories = array();
			/*get custom collections*/
			$request = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::wp_remote_get(
				$domain, $access_token, $api_key, $api_secret, 'custom_collections', false, array(), $timeout, true
			);
			$return  = array(
				'status' => 'error',
				'data'   => '',
				'code'   => $request['code'],
			);
			$error   = 0;
			if ( $request['status'] === 'success' ) {
				$custom_collections = $request['data'];
				self::process_category_data( $custom_collections, $categories );
				while ( $request['pagination_link']['next'] ) {
					$request        = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::wp_remote_get(
						$domain, $access_token, $api_key, $api_secret, 'custom_collections', false, array( 'page_info' => $request['pagination_link']['next'] ), $timeout, true
					);
					$return['code'] = $request['code'];
					if ( $request['status'] === 'success' ) {
						$custom_collections = $request['data'];
						self::process_category_data( $custom_collections, $categories );
					}
				}
			} else {
				$error ++;
				$return['data'] = $request['data'];
			}
			/*get smart collections*/
			$request        = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::wp_remote_get(
				$domain, $access_token, $api_key, $api_secret, 'smart_collections', false, array(), $timeout, true
			);
			$return['code'] = $request['code'];
			if ( $request['status'] === 'success' ) {
				$smart_collections = $request['data'];
				self::process_category_data( $smart_collections, $categories );
				while ( $request['pagination_link']['next'] ) {
					$request        = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::wp_remote_get(
						$domain, $access_token, $api_key, $api_secret, 'smart_collections', false, array( 'page_info' => $request['pagination_link']['next'] ), $timeout, true
					);
					$return['code'] = $request['code'];
					if ( $request['status'] === 'success' ) {
						$smart_collections = $request['data'];
						self::process_category_data( $smart_collections, $categories );
					}
				}
			} else {
				$error ++;
				$return['data'] = $request['data'];
			}
			if ( $error < 1 ) {
				$return['status'] = 'success';
				$return['data']   = $categories;
			}
			$file_path = $path . '/categories.txt';
			file_put_contents( $file_path, json_encode( $categories ) );

			return $return;
		}

		/**
		 * @param $domain
		 * @param $access_token
		 * @param $api_key
		 * @param $api_secret
		 * @param $path
		 *
		 * @return array
		 */
		protected function initiate_blogs_data( $domain, $access_token, $api_key, $api_secret, $path ) {
			$timeout = self::$settings->get_params( 'request_timeout' );
			$blogs   = array();
			$request = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::wp_remote_get(
				$domain, $access_token, $api_key, $api_secret, 'blogs', false, array(
				'fields' => array(
					'id',
					'title',
					'handle',
					'tags'
				)
			), $timeout, true
			);
			$return  = array(
				'status' => 'error',
				'data'   => '',
				'code'   => $request['code'],
			);
			if ( $request['status'] === 'success' ) {
				if ( is_array( $request['data'] ) ) {
					$blogs = $request['data'];
				}
				while ( $request['pagination_link']['next'] ) {
					$request        = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::wp_remote_get(
						$domain, $access_token, $api_key, $api_secret, 'blogs', false, array( 'page_info' => $request['pagination_link']['next'] ), $timeout, true
					);
					$return['code'] = $request['code'];
					if ( $request['status'] === 'success' ) {
						if ( is_array( $request['data'] ) ) {
							$blogs = array_merge( $request['data'], $blogs );
						}
					}
				}
				$return['status'] = 'success';
				$return['data']   = $blogs;
			} else {
				$return['data'] = $request['data'];
			}
			$file_path = $path . '/blogs.txt';
			file_put_contents( $file_path, json_encode( $blogs ) );

			return $return;
		}

		/**
		 * @param $domain
		 * @param $access_token
		 * @param $api_key
		 * @param $api_secret
		 * @param $path
		 *
		 * @return array
		 */
		protected function initiate_shipping_zones_data( $domain, $access_token, $api_key, $api_secret, $path ) {
			$timeout        = self::$settings->get_params( 'request_timeout' );
			$shipping_zones = array();
			$request        = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::wp_remote_get(
				$domain, $access_token, $api_key, $api_secret, 'shipping_zones', false, array(), $timeout, true
			);
			$return         = array(
				'status' => 'error',
				'data'   => '',
				'code'   => $request['code'],
			);
			if ( $request['status'] === 'success' ) {
				$shipping_zones   = $request['data'];
				$return['status'] = 'success';
				$return['data']   = $shipping_zones;
			} else {
				$return['data'] = $request['data'];
			}
			$file_path = $path . '/shipping_zones.txt';
			file_put_contents( $file_path, json_encode( $shipping_zones ) );

			return $return;
		}

		/**
		 * @param $domain
		 * @param $access_token
		 * @param $api_key
		 * @param $api_secret
		 * @param $path
		 *
		 * @return array
		 */
		protected function initiate_countries_data( $domain, $access_token, $api_key, $api_secret, $path ) {
			$timeout   = self::$settings->get_params( 'request_timeout' );
			$countries = array();
			$request   = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::wp_remote_get(
				$domain, $access_token, $api_key, $api_secret, 'countries', false, array(), $timeout, true
			);
			$return    = array(
				'status' => 'error',
				'data'   => '',
				'code'   => $request['code'],
			);
			if ( $request['status'] === 'success' ) {
				$countries = $request['data'];
				while ( $request['pagination_link']['next'] ) {
					$request        = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::wp_remote_get(
						$domain, $access_token, $api_key, $api_secret, 'countries', false, array( 'page_info' => $request['pagination_link']['next'] ), $timeout, true
					);
					$return['code'] = $request['code'];
					if ( $request['status'] === 'success' ) {
						$countries = array_merge( $request['data'], $countries );
					}
				}

				$return['status'] = 'success';
				$return['data']   = $countries;
			} else {
				$return['data'] = $request['data'];
			}
			$file_path = $path . '/countries.txt';
			file_put_contents( $file_path, json_encode( $countries ) );

			return $return;
		}

		/**
		 * @param $domain
		 * @param $access_token
		 * @param $api_key
		 * @param $api_secret
		 * @param $path
		 *
		 * @return array
		 */
		protected function import_store_settings( $domain, $access_token, $api_key, $api_secret, $path ) {
			$timeout = self::$settings->get_params( 'request_timeout' );
			$shop    = array();
			$request = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::wp_remote_get(
				$domain, $access_token, $api_key, $api_secret, 'shop', false, array(), $timeout, true
			);
			$return  = array(
				'status' => 'error',
				'data'   => '',
				'code'   => $request['code'],
			);
			if ( $request['status'] === 'success' ) {
				$shop             = $request['data'];
				$return['status'] = 'success';
				$return['data']   = $shop;
			} else {
				$return['data'] = $request['data'];
			}
			$file_path = $path . '/shop.txt';
			file_put_contents( $file_path, json_encode( $shop ) );

			return $return;
		}

		/**
		 * @param $domain
		 * @param $access_token
		 * @param $api_key
		 * @param $api_secret
		 * @param $collection_id
		 * @param $path
		 *
		 * @return array
		 */
		public function get_product_ids_by_collection( $domain, $access_token, $api_key, $api_secret, $collection_id, $path ) {
			$timeout     = self::$settings->get_params( 'request_timeout' );
			$product_ids = array();
			$request     = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::wp_remote_get(
				$domain, $access_token, $api_key, $api_secret, 'products', false, array(
				'collection_id' => $collection_id,
				'fields'        => 'id'
			), $timeout, true
			);
			$return      = array(
				'status' => 'error',
				'data'   => '',
				'code'   => $request['code'],
			);
			if ( $request['status'] === 'success' ) {
				$products = $request['data'];
				if ( is_array( $products ) && count( $products ) ) {
					$product_ids = array_merge( array_column( $products, 'id' ), $product_ids );
				}
				while ( $request['pagination_link']['next'] ) {
					$request        = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::wp_remote_get(
						$domain, $access_token, $api_key, $api_secret, 'products', false, array( 'page_info' => $request['pagination_link']['next'] ), $timeout, true
					);
					$return['code'] = $request['code'];
					if ( $request['status'] === 'success' ) {
						$products = $request['data'];
						if ( is_array( $products ) && count( $products ) ) {
							$product_ids = array_merge( array_column( $products, 'id' ), $product_ids );
						}
					}
				}
			} else {
				$return['data'] = $request['data'];

				return $return;
			}
			file_put_contents( $path . 'category_' . $collection_id . '.txt', json_encode( $product_ids ) );
			$return['status'] = 'success';
			$return['data']   = $product_ids;

			return $return;
		}

		/**
		 * @param $domain
		 * @param $access_token
		 * @param $api_key
		 * @param $api_secret
		 * @param $blog_id
		 * @param $path
		 *
		 * @return array
		 */
		public function get_blog_post_ids_by_collection( $domain, $access_token, $api_key, $api_secret, $blog_id, $path ) {
			$timeout  = self::$settings->get_params( 'request_timeout' );
			$articles = array();
			$request  = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::wp_remote_get_articles(
				$domain, $access_token, $api_key, $api_secret, $blog_id, false, array(), $timeout, true
			);
			$return   = array(
				'status' => 'error',
				'data'   => '',
				'code'   => $request['code'],
			);
			if ( $request['status'] === 'success' ) {
				if ( is_array( $request['data'] ) && count( $request['data'] ) ) {
					$articles = $request['data'];
				}
				while ( $request['pagination_link']['next'] ) {
					$request        = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::wp_remote_get_articles(
						$domain, $access_token, $api_key, $api_secret, $blog_id, false, array( 'page_info' => $request['pagination_link']['next'] ), $timeout, true
					);
					$return['code'] = $request['code'];
					if ( $request['status'] === 'success' ) {
						if ( is_array( $request['data'] ) && count( $request['data'] ) ) {
							$articles = array_merge( $request['data'], $articles );
						}
					}
				}
			} else {
				$return['data'] = $request['data'];

				return $return;
			}
			file_put_contents( $path . 'blog_' . $blog_id . '.txt', json_encode( $articles ) );
			$return['status'] = 'success';
			$return['data']   = $articles;

			return $return;
		}

		/**
		 * Count number of products available to import, calculate total pages
		 *
		 * @param $history_product_option
		 * @param $domain
		 * @param $access_token
		 * @param $api_key
		 * @param $api_secret
		 *
		 * @return array
		 */
		protected function initiate_products_data( $history_product_option, $domain, $access_token, $api_key, $api_secret ) {
			$history = self::get_default_history( 'products' );
			$this->add_filters_args( $import_args );
			$request = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::wp_remote_get(
				$domain, $access_token, $api_key, $api_secret, 'products', true, $import_args, self::$settings->get_params( 'request_timeout' )
			);
			$return  = array(
				'status' => 'error',
				'data'   => '',
				'code'   => $request['code'],
			);
			if ( $request['status'] === 'success' ) {
				$count                     = $request['data'];
				$history['total_products'] = $count;
				$total_pages               = ceil( $count / $history['products_per_file'] );
				$history['total_pages']    = $total_pages;
				update_option( $history_product_option, $history );
				if ( 0 == $total_pages ) {
					$return['data'] = esc_html__( 'No data to import', 's2w-import-shopify-to-woocommerce' );
					$return['code'] = 'no_data';
				} else {
					$return['status'] = 'success';
					$return['data']   = $history;
				}
			} else {
				$return['data'] = $request['data'];
			}

			return $return;
		}

		/**
		 * Count number of items available to import, calculate total pages
		 *
		 * @param $history_option
		 * @param $domain
		 * @param $access_token
		 * @param $api_key
		 * @param $api_secret
		 * @param string $type singular form of an element
		 * @param string $data_type
		 *
		 * @return array
		 */
		protected function initiate_data( $history_option, $domain, $access_token, $api_key, $api_secret, $type, $data_type = '' ) {
			$history = self::get_default_history( $type );
			if ( ! $data_type ) {
				$data_type = $type;
			}
			$this->add_filters_args( $import_args, $type );
			$request = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::wp_remote_get(
				$domain, $access_token, $api_key, $api_secret, $data_type, true, $import_args, self::$settings->get_params( 'request_timeout' )
			);
			$return  = array(
				'status' => 'error',
				'data'   => '',
				'code'   => $request['code'],
			);
			if ( $request['status'] === 'success' ) {
				$count                          = $request['data'];
				$history["total_{$type}"]       = $count;
				$total_pages                    = ceil( $count / $history["{$type}_per_file"] );
				$history["{$type}_total_pages"] = $total_pages;
				update_option( $history_option, $history );
				if ( 0 == $total_pages ) {
					$return['data'] = esc_html__( 'No data to import', 's2w-import-shopify-to-woocommerce' );
					$return['code'] = 'no_data';
				} else {
					$return['status'] = 'success';
					$return['data']   = $history;
				}
			} else {
				$return['data'] = $request['data'];
			}

			return $return;
		}

		/**
		 * Count number of pages available to import, calculate total pages
		 *
		 * @param $history_option
		 * @param $domain
		 * @param $access_token
		 * @param $api_key
		 * @param $api_secret
		 *
		 * @return array
		 */
		protected function initiate_pages_data( $history_option, $domain, $access_token, $api_key, $api_secret ) {
			$history = self::get_default_history( 'spages' );
			$this->add_filters_args( $import_args, 'pages' );
			$request = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::wp_remote_get(
				$domain, $access_token, $api_key, $api_secret, 'pages', true, $import_args, self::$settings->get_params( 'request_timeout' )
			);
			$return  = array(
				'status' => 'error',
				'data'   => '',
				'code'   => $request['code'],
			);
			if ( $request['status'] === 'success' ) {
				$count                         = $request['data'];
				$history["total_spages"]       = $count;
				$total_pages                   = ceil( $count / $history["spages_per_file"] );
				$history["spages_total_pages"] = $total_pages;
				update_option( $history_option, $history );
				if ( 0 == $total_pages ) {
					$return['data'] = esc_html__( 'No data to import', 's2w-import-shopify-to-woocommerce' );
					$return['code'] = 'no_data';
				} else {
					$return['status'] = 'success';
					$return['data']   = $history;
				}
			} else {
				$return['data'] = $request['data'];
			}

			return $return;
		}

		/**
		 * Options which are available to change from main settings page
		 *
		 * @return bool|mixed|void
		 */
		private function get_main_settings_args() {
			$params = self::$settings->get_params();
			$params = array_diff_key(
				$params,
				self::$settings->get_default_csv_import_params(),
				self::$settings->get_default_cron_orders_sync_params(),
				self::$settings->get_default_cron_products_sync_params(),
				self::$settings->get_default_webhooks_params(),
				self::$settings->get_default_products_sync_params(),
				self::$settings->get_default_orders_sync_params()
			);
			unset( $params['validate'] );
			unset( $params['number'] );
			unset( $params['import_categories'] );
//			foreach ( $params as $k => $v ) {
//				if ( in_array( $k, array(
//						'validate',
//						'number',
//						'import_categories',
//						'csv_if_product_exists',
//						'download_images_later'
//					) ) || strpos( $k, 'webhooks_' ) === 0 || strpos( $k, 'cron_update_' ) === 0 || strpos( $k, 'update_product_' ) === 0 || strpos( $k, 'update_order_' ) === 0 ) {
//					unset( $params[ $k ] );
//				}
//			}

			return $params;
		}

		/**
		 * Save settings and check auto-update key
		 */
		public function save_and_check_key() {
			global $s2w_settings;
			if ( ! current_user_can( self::get_required_capability() ) ) {
				return;
			}
			if ( ! isset( $_POST['s2w_check_key'] ) || ! $_POST['s2w_check_key'] ) {
				return;
			}
			if ( ! isset( $_POST['_s2w_nonce'] ) || ! wp_verify_nonce( $_POST['_s2w_nonce'], 's2w_action_nonce' ) ) {
				return;
			}
			$args              = $this->get_main_settings_args();
			$advanced_settings = array();
			foreach ( $this->get_advanced_settings_keys() as $a_key ) {
				$advanced_settings[ $a_key ] = $args[ $a_key ];
			}
			self::fill_params_from_post( $args, 's2w_' );
			if ( ! current_user_can( 'manage_options' ) ) {
				$args = array_merge( $args, $advanced_settings );
			}
			$domain               = $args['domain'];
			$access_token         = $args['access_token'];
			$api_key              = $args['api_key'];
			$api_secret           = $args['api_secret'];
			$auto_update_key      = $args['auto_update_key'];
			$args['variable_sku'] = str_replace( ' ', '', $args['variable_sku'] );
			$args['validate']     = self::$settings->get_params( 'validate' );
			$path                 = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_cache_path( $domain, $access_token, $api_key, $api_secret ) . '/';
			delete_site_transient( 'update_plugins' );
			delete_transient( 'villatheme_item_25122' );
			/*delete old message if auto update key changes*/
			if ( $auto_update_key != self::$settings->get_params( 'auto_update_key' ) ) {
				delete_option( 's2w-import-shopify-to-woocommerce_messages' );
			}
			do_action( 'villatheme_save_and_check_key_s2w-import-shopify-to-woocommerce', $auto_update_key );
			$old_domain       = self::$settings->get_params( 'domain' );
			$old_access_token = self::$settings->get_params( 'access_token' );
			$old_api_key      = self::$settings->get_params( 'api_key' );
			$old_api_secret   = self::$settings->get_params( 'api_secret' );
			if ( $domain ) {
				if ( $access_token ) {
					if ( ! $args['validate'] || $domain != $old_domain || $access_token != $old_access_token ) {
						$request = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_access_scopes( $domain, $access_token, $api_key, $api_secret );
						if ( $request['status'] === 'success' ) {
							$args['validate'] = 1;
						} else {
							$args['validate'] = '';
						}
					}
				} else {
					if ( ! $args['validate'] || $domain !== $old_domain || $api_key !== $old_api_key || $api_secret !== $old_api_secret ) {
						$request = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_access_scopes( $domain, $access_token, $api_key, $api_secret );
						if ( $request['status'] === 'success' ) {
							$args['validate'] = 1;
						} else {
							$args['validate'] = '';
						}
					} else {
						$args['validate'] = '';
					}
				}
			} else {
				$args['validate'] = '';
			}
			/*Delete import history if one of the filter options changes*/
			self::maybe_delete_cache_and_history( 'orders', $args, $domain, $access_token, $api_key, $api_secret );
			self::maybe_delete_cache_and_history( 'products', $args, $domain, $access_token, $api_key, $api_secret );
			if ( $args['validate'] ) {
				if ( $domain === $old_domain ) {
					$old_dir = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_cache_path( $old_domain, $old_access_token, $old_api_key, $old_api_secret );
					if ( is_dir( $old_dir ) ) {
						/*If use the same Shopify store but a different API, rename existing one*/
						if ( ! @rename( $old_dir, $path ) ) {
							VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::create_cache_folder( $path );
						}
					} else {
						VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::create_cache_folder( $path );
					}
				} else {
					$dirs = glob( VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CACHE . "*_$domain", GLOB_ONLYDIR );
					if ( is_array( $dirs ) && count( $dirs ) ) {
						/*If use the same Shopify store but a different API, rename existing one*/
						if ( ! @rename( $dirs[0], $path ) ) {
							VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::create_cache_folder( $path );
						}
					} else {
						VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::create_cache_folder( $path );
					}
				}

			} else {
				$args['cron_update_products'] = '';
				$args['cron_update_orders']   = '';
				$this->unschedule_event();
			}
			update_option( 's2w_params', array_merge( self::$settings->get_params(), $args ) );
			$s2w_settings   = $args;
			self::$settings = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_instance( true );
		}

		/**
		 * Options that only admin can see and change
		 *
		 * @return array
		 */
		private function get_advanced_settings_keys() {
			return array(
				'domain',
				'access_token',
				'api_key',
				'api_secret',
				'capabilities',
				'auto_update_key',
			);
		}

		/**
		 * Ajax save settings and validate API
		 */
		public function save_settings() {
			if ( ! current_user_can( self::get_required_capability() ) ) {
				return;
			}
			self::check_ajax_referrer();
			add_filter( 'http_request_timeout', array( $this, 'bump_request_timeout' ) );
			$args              = $this->get_main_settings_args();
			$advanced_settings = array();
			foreach ( $this->get_advanced_settings_keys() as $a_key ) {
				$advanced_settings[ $a_key ] = $args[ $a_key ];
			}
			self::fill_params_from_post( $args );
			if ( ! current_user_can( 'manage_options' ) ) {
				/*Merge old advanced settings in case the current user is not an admin*/
				$args = array_merge( $args, $advanced_settings );
			}

			$domain                  = $args['domain'];
			$access_token            = $args['access_token'];
			$api_key                 = $args['api_key'];
			$api_secret              = $args['api_secret'];
			$args['variable_sku']    = str_replace( ' ', '', $args['variable_sku'] );
			$args['validate']        = self::$settings->get_params( 'validate' );
			$path                    = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_cache_path( $domain, $access_token, $api_key, $api_secret ) . '/';
			$history_product_option  = 's2w_' . $domain . '_history';
			$history                 = get_option( $history_product_option, self::get_default_history( 'products' ) );
			$history_update_products = get_option( 's2w_' . $domain . '_history_update_products', self::get_default_history( 'update_products' ) );
			$history_orders          = get_option( 's2w_' . $domain . '_history_orders', self::get_default_history( 'orders' ) );
			$history_customers       = get_option( 's2w_' . $domain . '_history_customers', self::get_default_history( 'customers' ) );
			$history_coupons         = get_option( 's2w_' . $domain . '_history_coupons', self::get_default_history( 'coupons' ) );

			$elements = array(
				'store_settings' => '',
				'payments'       => '',
				'shipping_zones' => '',
				'taxes'          => '',
				'pages'          => '',
				'blogs'          => '',
				'coupons'        => '',
			);

			foreach ( $elements as $key => $value ) {
				$element = get_option( 's2w_' . $domain . '_history_' . $key );
				if ( isset( $element['time'] ) && $element['time'] ) {
					$elements[ $key ] = 1;
				}
			}
			$elements['products']        = isset( $history['time'] ) && $history['time'] ? 1 : '';
			$elements['customers']       = isset( $history_customers['time'] ) && $history_customers['time'] ? 1 : '';
			$elements['coupons']         = isset( $history_coupons['time'] ) && $history_coupons['time'] ? 1 : '';
			$elements['orders']          = isset( $history_orders['time'] ) && $history_orders['time'] ? 1 : '';
			$elements['update_products'] = isset( $history_update_products['time'] ) && $history_update_products['time'] ? 1 : '';
			$api_error                   = '';
			$old_domain                  = self::$settings->get_params( 'domain' );
			$old_access_token            = self::$settings->get_params( 'access_token' );
			$old_api_key                 = self::$settings->get_params( 'api_key' );
			$old_api_secret              = self::$settings->get_params( 'api_secret' );
			/*Validate API credentials and access scopes*/
			if ( $domain ) {
				if ( $access_token ) {
					if ( ! $args['validate'] || $domain != $old_domain || $access_token != $old_access_token ) {
						$request = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_access_scopes( $domain, $access_token, $api_key, $api_secret );
						if ( $request['status'] === 'success' ) {
							$args['validate'] = 1;
						} else {
							$api_error        = $request['data'];
							$args['validate'] = '';
						}
					}
				} else {
					if ( $api_key && $api_secret ) {
						if ( ! $args['validate'] || $domain != $old_domain || $api_key != $old_api_key || $api_secret != $old_api_secret ) {
							$request = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_access_scopes( $domain, $access_token, $api_key, $api_secret );
							if ( $request['status'] === 'success' ) {
								$args['validate'] = 1;
							} else {
								$api_error        = $request['data'];
								$args['validate'] = '';
							}
						}
					} else {
						$args['validate'] = '';
					}
				}
			} else {
				$args['validate'] = '';
			}
			/*Delete import history if one of the filter options changes*/
			if ( self::maybe_delete_cache_and_history( 'orders', $args, $domain, $access_token, $api_key, $api_secret ) ) {
				$history_orders = self::get_default_history( 'orders' );
			}
			if ( self::maybe_delete_cache_and_history( 'products', $args, $domain, $access_token, $api_key, $api_secret ) ) {
				$history = self::get_default_history( 'products' );
			}

			if ( $args['validate'] ) {
				/*If credentials are valid, create an unique cache folder*/
				if ( $domain === $old_domain ) {
					$old_dir = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_cache_path( $old_domain, $old_access_token, $old_api_key, $old_api_secret );
					if ( is_dir( $old_dir ) ) {
						/*If use the same Shopify store but a different API, rename existing one*/
						if ( ! @rename( $old_dir, $path ) ) {
							VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::create_cache_folder( $path );
						}
					} else {
						VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::create_cache_folder( $path );
					}
				} else {
					$dirs = glob( VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CACHE . "*_$domain", GLOB_ONLYDIR );
					if ( is_array( $dirs ) && count( $dirs ) ) {
						/*If use the same Shopify store but a different API, rename existing one*/
						if ( ! @rename( $dirs[0], $path ) ) {
							VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::create_cache_folder( $path );
						}
					} else {
						VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::create_cache_folder( $path );
					}
				}
			} else {
				$args['cron_update_products'] = '';
				$args['cron_update_orders']   = '';
				$this->unschedule_event();
			}
			update_option( 's2w_params', array_merge( self::$settings->get_params(), $args ) );
			wp_send_json( array_merge( $history, $history_update_products, $history_customers, $history_coupons, $history_orders, array(
				'api_error'         => $api_error,
				'validate'          => $args['validate'],
				'imported_elements' => $elements,
			) ) );
		}

		/**
		 * Unschedule products and orders sync, cancel all related background tasks
		 */
		public function unschedule_event() {
			/*Cron products sync*/
			$cron_update_products = wp_next_scheduled( 's2w_cron_update_products' );
			if ( $cron_update_products ) {
				wp_unschedule_event( $cron_update_products, 's2w_cron_update_products' );
			}
			S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Cron_Update_Products::$get_data_to_update->kill_process();
			S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Cron_Update_Products::$update_products->kill_process();
			/*Cron orders sync*/
			$cron_update_orders = wp_next_scheduled( 's2w_cron_update_orders' );
			if ( $cron_update_orders ) {
				wp_unschedule_event( $cron_update_orders, 's2w_cron_update_orders' );
			}
			S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Cron_Update_Orders::$get_data_to_update->kill_process();
			S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Cron_Update_Orders::$update_orders->kill_process();
		}

		/**
		 * Ajax save order options
		 */
		public function save_settings_orders_options() {
			if ( ! current_user_can( self::get_required_capability() ) ) {
				return;
			}
			self::check_ajax_referrer();
			$domain       = self::$settings->get_params( 'domain' );
			$access_token = self::$settings->get_params( 'access_token' );
			$api_key      = self::$settings->get_params( 'api_key' );
			$api_secret   = self::$settings->get_params( 'api_secret' );
			$path         = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_cache_path( $domain, $access_token, $api_key, $api_secret ) . '/';
			$history      = get_option( 's2w_' . $domain . '_history_orders', self::get_default_history( 'orders' ) );
			$args         = self::$settings->get_default_orders_import_params();
			self::fill_params_from_post( $args );

			if ( self::maybe_delete_cache_and_history( 'orders', $args, $domain, $access_token, $api_key, $api_secret ) ) {
				$history = self::get_default_history( 'orders' );
			}
			VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::create_cache_folder( $path );
			update_option( 's2w_params', array_merge( self::$settings->get_params(), $args ) );
			wp_send_json( array_merge( $history, $args ) );
		}

		public function save_settings_customers_options() {
			if ( ! current_user_can( self::get_required_capability() ) ) {
				return;
			}
			self::check_ajax_referrer();
			$domain       = self::$settings->get_params( 'domain' );
			$access_token = self::$settings->get_params( 'access_token' );
			$api_key      = self::$settings->get_params( 'api_key' );
			$api_secret   = self::$settings->get_params( 'api_secret' );
			$path         = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_cache_path( $domain, $access_token, $api_key, $api_secret ) . '/';
			$history      = get_option( 's2w_' . $domain . '_history_customers', self::get_default_history( 'customers' ) );
			$args         = self::$settings->get_default_customers_import_params();
			self::fill_params_from_post( $args );
			VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::create_cache_folder( $path );
			update_option( 's2w_params', array_merge( self::$settings->get_params(), $args ) );
			wp_send_json( array_merge( $history, $args ) );
		}

		/**
		 * Ajax save coupon options
		 */
		public function save_settings_coupons_options() {
			if ( ! current_user_can( self::get_required_capability() ) ) {
				return;
			}
			self::check_ajax_referrer();
			$domain       = self::$settings->get_params( 'domain' );
			$access_token = self::$settings->get_params( 'access_token' );
			$api_key      = self::$settings->get_params( 'api_key' );
			$api_secret   = self::$settings->get_params( 'api_secret' );
			$path         = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_cache_path( $domain, $access_token, $api_key, $api_secret ) . '/';
			$history      = get_option( 's2w_' . $domain . '_history_coupons', self::get_default_history( 'coupons' ) );
			$args         = self::$settings->get_default_coupons_import_params();
			self::fill_params_from_post( $args );

			if ( self::maybe_delete_cache_and_history( 'coupons', $args, $domain, $access_token, $api_key, $api_secret ) ) {
				$history = self::get_default_history( 'coupons' );
			}
			VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::create_cache_folder( $path );
			update_option( 's2w_params', array_merge( self::$settings->get_params(), $args ) );
			wp_send_json( array_merge( $history, $args ) );
		}

		/**
		 * Used when saving settings to delete import history and cache files of products, orders or coupons if one of their filter options changes
		 *
		 * @param $type
		 * @param $args
		 * @param $domain
		 * @param $access_token
		 * @param $api_key
		 * @param $api_secret
		 *
		 * @return bool
		 */
		private static function maybe_delete_cache_and_history( $type, $args, $domain, $access_token, $api_key, $api_secret ) {
			$reset = false;
			switch ( $type ) {
				case 'products':
					$filter_options = array(
						'product_import_sequence',
						'product_since_id',
						'product_product_type',
						'product_vendor',
						'product_collection_id',
						'product_created_at_min',
						'product_created_at_max',
						'product_published_at_min',
						'product_published_at_max',
						'product_status_mapping',
					);
					break;
				case 'orders':
					$filter_options = array(
						'order_import_sequence',
						'order_since_id',
						'order_processed_at_min',
						'order_financial_status',
						'order_fulfillment_status',
						'order_processed_at_max'
					);
					break;
				case 'coupons':
					$filter_options = array(
						'coupon_starts_at_min',
						'coupon_starts_at_max',
						'coupon_ends_at_min',
						'coupon_ends_at_max',
						'coupon_zero_times_used',
					);
					break;
				default:
					$filter_options = array();
			}

			foreach ( $filter_options as $filter_option ) {
				if ( $args[ $filter_option ] != self::$settings->get_params( $filter_option ) ) {
					self::delete_cache_and_history( $type, $domain, $access_token, $api_key, $api_secret );
					$reset = true;
					break;
				}
			}

			return $reset;
		}

		/**
		 * Delete import history and cache files of a specific element
		 *
		 * @param $type
		 * @param $domain
		 * @param $access_token
		 * @param $api_key
		 * @param $api_secret
		 */
		private static function delete_cache_and_history( $type, $domain, $access_token, $api_key, $api_secret ) {
			$path = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_cache_path( $domain, $access_token, $api_key, $api_secret );
			switch ( $type ) {
				case 'products':
					delete_option( 's2w_' . $domain . '_history' );
					VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::delete_files( glob( $path . '/product_*.txt' ) );
					VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::delete_files( glob( $path . '/page_*.txt' ) );/*old files*/
					break;
				case 'product_categories':
					delete_option( 's2w_' . $domain . "_history_{$type}" );
					VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::delete_files( glob( $path . '/category_*.txt' ) );
					VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::delete_files( $path . '/categories.txt' );
					break;
				case 'blogs':
				case 'pages':
					delete_option( 's2w_' . $domain . "_history_{$type}" );
					VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::delete_files( glob( $path . "/{$type}_*.txt" ) );
					VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::delete_files( $path . "/{$type}.txt" );
					break;
				case 'shipping_zones':
					delete_option( 's2w_' . $domain . "_history_{$type}" );
					VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::delete_files( $path . "/{$type}.txt" );
					break;
				case 'store_settings':
					delete_option( 's2w_' . $domain . "_history_{$type}" );
					VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::delete_files( $path . '/shop.txt' );
					break;
				case 'taxes':
					delete_option( 's2w_' . $domain . "_history_{$type}" );
					VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::delete_files( $path . '/shop.txt' );
					VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::delete_files( $path . '/countries.txt' );
					break;
				default:
					if ( isset( self::get_supported_elements()[ $type ] ) ) {
						delete_option( 's2w_' . $domain . "_history_{$type}" );
						VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::delete_files( glob( $path . "/{$type}_*.txt" ) );
					}
			}
		}

		/**
		 * @param $type
		 *
		 * @return array
		 */
		private static function get_default_history( $type ) {
			if ( 'products' === $type ) {
				$history = array(
					'total_products'         => 0,
					'total_pages'            => 0,
					'current_import_id'      => '',
					'current_import_product' => - 1,
					'current_import_page'    => 1,
					'products_per_file'      => 250,
					'last_product_error'     => '',
				);
			} else {
				$singular = self::get_singular_element_name( $type );
				$history  = array(
					"total_{$type}"               => 0,
					"{$type}_total_pages"         => 0,
					"{$type}_current_import_id"   => '',
					"current_import_{$singular}"  => - 1,
					"{$type}_current_import_page" => 1,
					"{$type}_per_file"            => 250,
					"last_{$singular}_error"      => '',
				);
			}

			return $history;
		}

		/**
		 * Ajax save product options
		 */
		public function save_settings_products_options() {
			if ( ! current_user_can( self::get_required_capability() ) ) {
				return;
			}
			self::check_ajax_referrer();
			$domain                 = self::$settings->get_params( 'domain' );
			$access_token           = self::$settings->get_params( 'access_token' );
			$api_key                = self::$settings->get_params( 'api_key' );
			$api_secret             = self::$settings->get_params( 'api_secret' );
			$path                   = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_cache_path( $domain, $access_token, $api_key, $api_secret ) . '/';
			$history_product_option = 's2w_' . $domain . '_history';
			$history                = get_option( $history_product_option, self::get_default_history( 'products' ) );

			$args = self::$settings->get_default_products_import_params();
			self::fill_params_from_post( $args );
			if ( self::maybe_delete_cache_and_history( 'products', $args, $domain, $access_token, $api_key, $api_secret ) ) {
				$history = self::get_default_history( 'products' );
			}
			VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::create_cache_folder( $path );
			update_option( 's2w_params', array_merge( self::$settings->get_params(), $args ) );
			wp_send_json( array_merge( $history, $args ) );
		}

		/**
		 * Ajax handler for importing data from Shopify
		 *
		 * @throws WC_Data_Exception
		 */
		public function sync() {
			if ( ! current_user_can( self::get_required_capability() ) ) {
				die;
			}
			self::check_ajax_referrer();
			ignore_user_abort( true );
			add_filter( 'http_request_timeout', array( $this, 'bump_request_timeout' ) );
			$domain       = self::$settings->get_params( 'domain' );
			$access_token = self::$settings->get_params( 'access_token' );
			$api_key      = self::$settings->get_params( 'api_key' );
			$api_secret   = self::$settings->get_params( 'api_secret' );

			$path = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_cache_path( $domain, $access_token, $api_key, $api_secret ) . '/';
			VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::create_cache_folder( $path );
			$step      = isset( $_POST['step'] ) ? sanitize_text_field( $_POST['step'] ) : '';
			$error_log = isset( $_POST['error_log'] ) ? wp_kses_post( $_POST['error_log'] ) : '';
			$logs      = '';
			$log_file  = $path . 'logs.txt';
			if ( $error_log ) {
				S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, $error_log );
			}
			$history_option = 's2w_' . $domain . '_history_' . $step;
			$import_history = get_option( $history_option, array() );
			/*array(
				'total_orders'         => 0,
				'orders_total_pages'            => 0,
				'orders_current_import_id'      => '',
				'current_import_order' => - 1,
				'orders_current_import_page'    => 1,
				'orders_per_file'      => 250,
				'orders_per_request'   => 50,
				'last_order_error'     => '',
			)*/
			$history_product_option = 's2w_' . $domain . '_history';
			$history                = get_option( $history_product_option, array() );
			/*array(
				'total_products'         => 0,
				'total_pages'            => 0,
				'current_import_id'      => '',
				'current_import_product' => - 1,
				'current_import_page'    => 1,
				'products_per_file'      => 250,
				'last_product_error'     => '',
			)*/

			if ( $domain && ( $access_token || ( $api_key && $api_secret ) ) ) {
				switch ( $step ) {
					case 'coupons':
						$current_import_id     = isset( $_POST['coupons_current_import_id'] ) ? sanitize_text_field( $_POST['coupons_current_import_id'] ) : '';
						$current_import_coupon = isset( $_POST['current_import_coupon'] ) ? intval( sanitize_text_field( $_POST['current_import_coupon'] ) ) : - 1;
						$current_import_page   = isset( $_POST['coupons_current_import_page'] ) ? absint( sanitize_text_field( $_POST['coupons_current_import_page'] ) ) : 1;
						$total_pages           = isset( $_POST['coupons_total_pages'] ) ? absint( sanitize_text_field( $_POST['coupons_total_pages'] ) ) : 1;
						if ( ! $import_history ) {
							$history_data = $this->initiate_data( $history_option, $domain, $access_token, $api_key, $api_secret, $step, 'price_rules' );
							if ( $history_data['status'] === 'success' ) {
								$import_history = $history_data['data'];
								wp_send_json( array_merge( $import_history, array(
										'status' => 'retry'
									)
								) );
							} else {
								wp_send_json( array(
										'status'  => 'error',
										'code'    => $history_data['code'],
										'message' => $history_data['data']
									)
								);
							}
						} elseif ( ! empty( $import_history['time'] ) ) {
							/*If already imported, delete cache and history then reimport*/
							self::delete_cache_and_history( $step, $domain, $access_token, $api_key, $api_secret );
							$history_data = $this->initiate_data( $history_option, $domain, $access_token, $api_key, $api_secret, $step, 'price_rules' );
							if ( $history_data['status'] === 'success' ) {
								$import_history = $history_data['data'];
								wp_send_json( array_merge( $import_history, array(
										'status' => 'retry'
									)
								) );
							} else {
								wp_send_json( array(
										'status'  => 'error',
										'code'    => $history_data['code'],
										'message' => $history_data['data']
									)
								);
							}
						}
						$coupons_per_request = self::$settings->get_params( 'coupons_per_request' );
						$coupons_per_file    = isset( $import_history['coupons_per_file'] ) ? $import_history['coupons_per_file'] : 250;
						if ( $total_pages >= $current_import_page ) {
							$file_path     = "{$path}{$step}_{$current_import_page}.txt";
							$coupons       = array();
							$page_info_num = empty( $import_history["{$step}_page_info_num"] ) ? 1 : intval( $import_history["{$step}_page_info_num"] );
							if ( ! is_file( $file_path ) || $page_info_num < $current_import_page + 1 ) {
								$import_args = array();
								if ( ! empty( $import_history["{$step}_page_info"] ) && ! empty( $import_history["{$step}_page_info_num"] ) ) {
									$import_args['page_info'] = $import_history["{$step}_page_info"];
								} else {
									$this->add_filters_args( $import_args, $step );
								}
								$request = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::wp_remote_get(
									$domain, $access_token, $api_key, $api_secret, 'price_rules', false, $import_args, self::$settings->get_params( 'request_timeout' ), true
								);
								if ( $request['status'] === 'success' ) {
									$coupons = $request['data'];
									if ( is_array( $coupons ) && count( $coupons ) ) {
										file_put_contents( $file_path, json_encode( $coupons ) );
									}
									if ( $request['pagination_link']['next'] ) {
										$page_info_num ++;
										$import_history["{$step}_page_info"]     = $request['pagination_link']['next'];
										$import_history["{$step}_page_info_num"] = $page_info_num;
										if ( $page_info_num < $current_import_page + 1 ) {
											update_option( $history_option, $import_history );
											wp_send_json( array_merge( $import_history, array(
													'status' => 'retry'
												)
											) );
										}
									}
								} else {
									wp_send_json( array(
										'status'  => 'error',
										'code'    => $request['code'],
										'message' => $request['data'],
									) );
								}
							} else {
								$coupons = vi_s2w_json_decode( file_get_contents( $file_path ) );
							}
							$current_page_count = count( $coupons );

							if ( $current_page_count ) {
								$current = $current_import_coupon;
								$max     = ( $current + $coupons_per_request + 1 ) < $current_page_count ? ( $current + $coupons_per_request + 1 ) : $current_page_count;
								wp_suspend_cache_invalidation( true );
								for ( $coupon_key = $current + 1; $coupon_key < $max; $coupon_key ++ ) {
									$current_import_coupon                       = $coupon_key;
									$import_history['coupons_current_import_id'] = $current_import_id;
									$import_history['current_import_coupon']     = $current_import_coupon;
									$price_rule                                  = isset( $coupons[ $coupon_key ] ) ? $coupons[ $coupon_key ] : array();
									if ( is_array( $price_rule ) && count( $price_rule ) ) {
										$price_rule  = apply_filters( 's2w_import_price_rule_data', $price_rule, $price_rule );
										$existing_id = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::query_get_id_by_shopify_id( $price_rule['id'], 'price_rule' );
										if ( $existing_id ) {
											$log['shopify_id']  = $price_rule['id'];
											$log['woo_id']      = $existing_id;
											$log['message']     = esc_html__( 'Coupon exists', 's2w-import-shopify-to-woocommerce' );
											$log['title']       = get_the_title( $existing_id );
											$log['product_url'] = admin_url( 'post.php?post=' . $log['woo_id'] . '&action=edit' );
											$logs_content       = "[{$log['shopify_id']}]{$log['title']}: {$log['message']}, WC Coupon ID: {$log['woo_id']}";
											S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, $logs_content );
											$logs .= '<div>' . $log['title'] . ': <strong>' . $log['message'] . '.</strong>' . ( $log['product_url'] ? '<a href="' . esc_url( $log['product_url'] ) . '" target="_blank" rel="nofollow">View & edit</a>' : '' ) . '</div>';
										} else {
											$title = isset( $price_rule['title'] ) ? sanitize_text_field( $price_rule['title'] ) : '';
											if ( $title ) {
												$existing_id = wc_get_coupon_id_by_code( $title );
												if ( $existing_id ) {
													$log['shopify_id']  = $price_rule['id'];
													$log['woo_id']      = $existing_id;
													$log['message']     = esc_html__( 'Coupon exists', 's2w-import-shopify-to-woocommerce' );
													$log['product_url'] = admin_url( 'post.php?post=' . $log['woo_id'] . '&action=edit' );
													$logs_content       = "[{$log['shopify_id']}]{$title}: {$log['message']}, WC Coupon ID: {$log['woo_id']}";
													S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, $logs_content );
													$logs .= '<div>' . $title . ': <strong>' . $log['message'] . '.</strong>' . ( $log['product_url'] ? '<a href="' . esc_url( $log['product_url'] ) . '" target="_blank" rel="nofollow">View & edit</a>' : '' ) . '</div>';
													update_post_meta( $existing_id, '_s2w_shopify_price_rule_id', $price_rule['id'] );
												} else {
													$coupon = new WC_Coupon();
													$coupon->set_code( $title );
													$value_type  = isset( $price_rule['value_type'] ) ? sanitize_text_field( $price_rule['value_type'] ) : '';
													$target_type = isset( $price_rule['target_type'] ) ? sanitize_text_field( $price_rule['target_type'] ) : '';
													if ( $target_type == 'shipping_line' ) {
														$coupon->set_free_shipping( 1 );
														$coupon->set_discount_type( 'percent' );
														$coupon->set_amount( 0 );
													} else {
														$value = isset( $price_rule['value'] ) ? abs( $price_rule['value'] ) : '';
														$coupon->set_free_shipping( 0 );
														$coupon->set_amount( $value );
														if ( $value_type == 'percentage' ) {
															$coupon->set_discount_type( 'percent' );
														} else {
															$allocation_method = isset( $price_rule['allocation_method'] ) ? sanitize_text_field( $price_rule['allocation_method'] ) : '';
															$target_selection  = isset( $price_rule['target_selection'] ) ? sanitize_text_field( $price_rule['target_selection'] ) : '';
															if ( $target_selection == 'entitled' ) {
																$coupon->set_discount_type( 'fixed_product' );
																$entitled_product_ids = isset( $price_rule['entitled_product_ids'] ) ? $price_rule['entitled_product_ids'] : array();
																$entitled_variant_ids = isset( $price_rule['entitled_variant_ids'] ) ? $price_rule['entitled_variant_ids'] : array();
																$include_product      = array();
																if ( count( $entitled_product_ids ) ) {
																	foreach ( $entitled_product_ids as $shopify_product_id ) {
																		$entitled_product_id = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::product_get_woo_id_by_shopify_id( $shopify_product_id );
																		if ( $entitled_product_id ) {
																			$include_product[] = $entitled_product_id;
																		}
																	}
																}
																if ( count( $entitled_variant_ids ) ) {
																	foreach ( $entitled_variant_ids as $shopify_variation_id ) {
																		$entitled_variant_id = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::product_get_woo_id_by_shopify_id( $shopify_variation_id, true );
																		if ( $entitled_variant_id ) {
																			$include_product[] = $entitled_variant_id;
																		}
																	}
																}
																if ( count( $include_product ) ) {
																	$coupon->set_product_ids( $include_product );
																}
																if ( $allocation_method == 'across' ) {
																	$coupon->set_limit_usage_to_x_items( 1 );
																}
															} else {
																$coupon->set_discount_type( 'fixed_cart' );
															}
														}
													}
													$usage_limit               = isset( $price_rule['usage_limit'] ) ? sanitize_text_field( $price_rule['usage_limit'] ) : '';
													$once_per_customer         = isset( $price_rule['once_per_customer'] ) ? sanitize_text_field( $price_rule['once_per_customer'] ) : '';
													$ends_at                   = isset( $price_rule['ends_at'] ) ? sanitize_text_field( $price_rule['ends_at'] ) : '';
													$minimum_amount            = isset( $price_rule['prerequisite_subtotal_range']['greater_than_or_equal_to'] ) ? sanitize_text_field( $price_rule['prerequisite_subtotal_range']['greater_than_or_equal_to'] ) : '';
													$customer_selection        = isset( $price_rule['customer_selection'] ) ? sanitize_text_field( $price_rule['customer_selection'] ) : '';
													$prerequisite_customer_ids = isset( $price_rule['prerequisite_customer_ids'] ) ? ( $price_rule['prerequisite_customer_ids'] ) : array();
													if ( $customer_selection == 'prerequisite' && count( $prerequisite_customer_ids ) ) {
														$file_path1 = $path . 'customers_emails.txt';
														if ( is_file( $file_path1 ) ) {
															$customers         = vi_s2w_json_decode( file_get_contents( $file_path1 ) );
															$email_restictions = array();
															foreach ( $prerequisite_customer_ids as $customer_id ) {
																if ( isset( $customers[ $customer_id ] ) && $customers[ $customer_id ] ) {
																	$email_restictions[] = $customers[ $customer_id ];
																}
															}
															$coupon->set_email_restrictions( array_unique( $email_restictions ) );
														}
													}
													$coupon->set_usage_limit( $usage_limit );
													$coupon->set_usage_limit_per_user( $once_per_customer );
													$coupon->set_minimum_amount( $minimum_amount );
													$coupon->set_date_expires( $ends_at );
													$coupon->set_individual_use( 1 );
													$existing_id = $coupon->save();
													update_post_meta( $existing_id, '_s2w_shopify_price_rule_id', $price_rule['id'] );
													update_post_meta( $existing_id, '_s2w_shopify_domain', $domain );
												}
											}
										}
									}
								}
								wp_suspend_cache_invalidation( false );
								$import_history['current_import_coupon']       = $current_import_coupon;
								$import_history['coupons_current_import_page'] = $current_import_page;
								$import_history['coupons_current_import_id']   = $current_import_id;
								$imported_coupons                              = ( $current_import_page - 1 ) * $coupons_per_file + $current_import_coupon + 1;
								if ( $current_import_coupon == $current_page_count - 1 ) {
									if ( $current_import_page == $total_pages ) {
										$import_history['time'] = current_time( 'timestamp' );
										update_option( $history_option, $import_history );
										S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, 'Import coupons successfully, total: ' . $import_history['total_coupons'] );
										wp_send_json( array(
											'status'                      => 'finish',
											'message'                     => sprintf( esc_html__( 'Completed %s/%s', 's2w-import-shopify-to-woocommerce' ), $imported_coupons, $import_history['total_coupons'] ),
											'imported_coupons'            => $imported_coupons,
											'coupons_current_import_id'   => $current_import_id,
											'coupons_current_import_page' => $current_import_page,
											'current_import_coupon'       => $current_import_coupon,
											'logs'                        => $logs,
										) );
									} else {
										$current_import_coupon = - 1;
										$current_import_page ++;
										update_option( $history_option, $import_history );
										wp_send_json( array(
											'status'                      => 'success',
											'message'                     => sprintf( esc_html__( 'Importing... %s/%s completed', 's2w-import-shopify-to-woocommerce' ), $imported_coupons, $import_history['total_coupons'] ),
											'imported_coupons'            => $imported_coupons,
											'coupons_current_import_id'   => $current_import_id,
											'coupons_current_import_page' => $current_import_page,
											'current_import_coupon'       => $current_import_coupon,
											'logs'                        => $logs,
										) );
									}
								} else {
									update_option( $history_option, $import_history );
									wp_send_json( array(
										'status'                      => 'success',
										'message'                     => sprintf( esc_html__( 'Importing... %s/%s completed', 's2w-import-shopify-to-woocommerce' ), $imported_coupons, $import_history['total_coupons'] ),
										'imported_coupons'            => $imported_coupons,
										'coupons_current_import_id'   => $current_import_id,
										'coupons_current_import_page' => $current_import_page,
										'current_import_coupon'       => $current_import_coupon,
										'logs'                        => $logs,
									) );
								}
							}
							if ( $current_import_page == $total_pages ) {
								$import_history['time'] = current_time( 'timestamp' );
								update_option( $history_option, $import_history );

								wp_send_json( array(
									'status'                      => 'finish',
									'message'                     => sprintf( esc_html__( 'Completed %s/%s', 's2w-import-shopify-to-woocommerce' ), $import_history['total_coupons'], $import_history['total_coupons'] ),
									'imported_coupons'            => $import_history['total_coupons'],
									'coupons_current_import_id'   => $current_import_id,
									'coupons_current_import_page' => $current_import_page,
									'current_import_coupon'       => $current_import_coupon,
									'logs'                        => $logs,
								) );
							} else {
								$imported_coupons      = $current_import_page * $coupons_per_file;
								$current_import_coupon = - 1;
								$current_import_page ++;
								update_option( $history_option, $import_history );
								wp_send_json( array(
									'status'                      => 'success',
									'message'                     => sprintf( esc_html__( 'Importing... %s/%s completed', 's2w-import-shopify-to-woocommerce' ), $imported_coupons, $import_history['total_coupons'] ),
									'imported_coupons'            => $imported_coupons,
									'coupons_current_import_id'   => $current_import_id,
									'coupons_current_import_page' => $current_import_page,
									'current_import_coupon'       => $current_import_coupon,
									'logs'                        => $logs,
								) );
							}
						}
						wp_send_json( array(
							'status'                      => 'finish',
							'message'                     => sprintf( esc_html__( 'Completed %s/%s', 's2w-import-shopify-to-woocommerce' ), $import_history['total_coupons'], $import_history['total_coupons'] ),
							'imported_coupons'            => $import_history['total_coupons'],
							'coupons_current_import_id'   => $current_import_id,
							'coupons_current_import_page' => $current_import_page,
							'current_import_coupon'       => $current_import_coupon,
							'logs'                        => $logs,
						) );
						break;
					case 'customers':
						if ( ! $import_history ) {
							$history_data = $this->initiate_data( $history_option, $domain, $access_token, $api_key, $api_secret, $step );
							if ( $history_data['status'] === 'success' ) {
								$import_history = $history_data['data'];
								wp_send_json( array_merge( $import_history, array(
										'status' => 'retry'
									)
								) );
							} else {
								wp_send_json( array(
										'status'  => 'error',
										'code'    => $history_data['code'],
										'message' => $history_data['data']
									)
								);
							}
						} elseif ( ! empty( $import_history['time'] ) ) {
							/*If already imported, delete cache and history then reimport*/
							self::delete_cache_and_history( $step, $domain, $access_token, $api_key, $api_secret );
							$history_data = $this->initiate_data( $history_option, $domain, $access_token, $api_key, $api_secret, $step );
							if ( $history_data['status'] === 'success' ) {
								$import_history = $history_data['data'];
								wp_send_json( array_merge( $import_history, array(
										'status' => 'retry'
									)
								) );
							} else {
								wp_send_json( array(
										'status'  => 'error',
										'code'    => $history_data['code'],
										'message' => $history_data['data']
									)
								);
							}
						}
						$current_import_id       = isset( $_POST['customers_current_import_id'] ) ? sanitize_text_field( $_POST['customers_current_import_id'] ) : '';
						$current_import_customer = isset( $_POST['current_import_customer'] ) ? intval( sanitize_text_field( $_POST['current_import_customer'] ) ) : - 1;
						$current_import_page     = isset( $_POST['customers_current_import_page'] ) ? absint( sanitize_text_field( $_POST['customers_current_import_page'] ) ) : 1;
						$total_pages             = isset( $_POST['customers_total_pages'] ) ? absint( sanitize_text_field( $_POST['customers_total_pages'] ) ) : 1;
						$customers_per_request   = self::$settings->get_params( 'customers_per_request' );
						$customer_last_date      = self::$settings->get_params( 'customers_last_date_import' );

						$update_existing_customers = self::$settings->get_params( 'update_existing_customers' );
						$customers_per_file        = isset( $import_history['customers_per_file'] ) ? $import_history['customers_per_file'] : 250;
						$customer_date_created     = '';
						if ( ( $customer_last_date === '' ) && ( $customer_last_date > 0 ) ) {
							$current_date = date_i18n( 'U' );

							$customer_date_created = date_i18n( DateTime::ATOM, $current_date - $customer_last_date * 24 * 60 * 60 );
						}
						if ( $total_pages >= $current_import_page ) {
							$file_path     = "{$path}{$step}_{$current_import_page}.txt";
							$customers     = array();
							$page_info_num = empty( $import_history["{$step}_page_info_num"] ) ? 1 : intval( $import_history["{$step}_page_info_num"] );
							if ( ! is_file( $file_path ) || $page_info_num < $current_import_page + 1 ) {
								$import_args = array();
								if ( ! empty( $import_history["{$step}_page_info"] ) && ! empty( $import_history["{$step}_page_info_num"] ) ) {
									$import_args['page_info'] = $import_history["{$step}_page_info"];
								} else {
									$this->add_filters_args( $import_args, $step );
								}
								$import_args['limit'] = $customers_per_file;

								if ( ! empty( $customer_date_created ) ) {
									$import_args['created_at_min'] = $customer_date_created;
								}

								$request = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::wp_remote_get( $domain, $access_token, $api_key, $api_secret, $step, false, $import_args, self::$settings->get_params( 'request_timeout' ), true );

								if ( $request['status'] === 'success' ) {
									$customers = $request['data'];
									if ( is_array( $customers ) && count( $customers ) ) {
										file_put_contents( $file_path, json_encode( $customers ) );
									}
									if ( $request['pagination_link']['next'] ) {
										$page_info_num ++;
										$import_history["{$step}_page_info"]     = $request['pagination_link']['next'];
										$import_history["{$step}_page_info_num"] = $page_info_num;
										if ( $page_info_num < $current_import_page + 1 ) {
											update_option( $history_option, $import_history );
											wp_send_json( array_merge( $import_history, array(
													'status' => 'retry'
												)
											) );
										}
									}
								} else {
									wp_send_json( array(
										'status'  => 'error',
										'code'    => $request['code'],
										'message' => $request['data'],
									) );
								}
							} else {
								$customers = vi_s2w_json_decode( file_get_contents( $file_path ) );
							}
							$current_page_count = count( $customers );
							if ( $current_page_count ) {
								$current = $current_import_customer;
								$max     = ( $current + $customers_per_request + 1 ) < $current_page_count ? ( $current + $customers_per_request + 1 ) : $current_page_count;
								wp_suspend_cache_invalidation( true );
								$customers_emails      = array();
								$customers_emails_file = $path . '/customers_emails.txt';
								if ( is_file( $customers_emails_file ) ) {
									$customers_emails = vi_s2w_json_decode( file_get_contents( $customers_emails_file ) );
								}
								$customers_with_purchases_only = self::$settings->get_params( 'customers_with_purchases_only' );
								for ( $customer_key = $current + 1; $customer_key < $max; $customer_key ++ ) {
									$current_import_customer                       = $customer_key;
									$import_history['customers_current_import_id'] = $current_import_id;
									$import_history['current_import_customer']     = $current_import_customer;
									$customer                                      = isset( $customers[ $customer_key ] ) ? $customers[ $customer_key ] : array();
									if ( is_array( $customer ) && count( $customer ) ) {
										$email        = sanitize_email( $customer['email'] );
										$orders_count = isset( $customer['orders_count'] ) ? absint( $customer['orders_count'] ) : 0;
										if ( $customers_with_purchases_only && $orders_count < 1 ) {
											continue;
										}
										if ( $email && ! isset( $customers_emails[ $customer['id'] ] ) ) {
											$customers_emails[ strval( $customer['id'] ) ] = $email;
										}
										$existing_id = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::customer_get_id_by_shopify_id( $customer['id'] );

										if ( $existing_id && ! $update_existing_customers ) {
											if ( self::woocommerce_analytics_enabled() ) {
												CustomersDataStore::update_registered_customer( $existing_id );
											}
											$log['shopify_id'] = $customer['id'];
											$log['woo_id']     = $existing_id;
											$logs_content      = "[{$customer['id']}]Customer exists, WP User ID: {$existing_id}";
											S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, $logs_content );
											$logs .= "<div>{$logs_content}</div>";
										} else {
											$user_id = self::import_customer( $customer, $existing_id );
											if ( is_wp_error( $user_id ) ) {
												S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, 'Error: ' . $user_id->get_error_message() );
											} else {

											}
										}
									}
								}
								file_put_contents( $customers_emails_file, json_encode( $customers_emails ) );
								wp_suspend_cache_invalidation( false );
								$import_history['current_import_customer']       = $current_import_customer;
								$import_history['customers_current_import_page'] = $current_import_page;
								$import_history['customers_current_import_id']   = $current_import_id;
								$imported_customers                              = ( $current_import_page - 1 ) * $customers_per_file + $current_import_customer + 1;
								if ( $current_import_customer == $current_page_count - 1 ) {
									if ( $current_import_page == $total_pages ) {
										$import_history['time'] = current_time( 'timestamp' );
										update_option( $history_option, $import_history );
										S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, 'Imported customers successfully, total: ' . $import_history['total_customers'] );
										wp_send_json( array(
											'status'                        => 'finish',
											'message'                       => sprintf( esc_html__( 'Completed %s/%s', 's2w-import-shopify-to-woocommerce' ), $imported_customers, $import_history['total_customers'] ),
											'imported_customers'            => $imported_customers,
											'customers_current_import_id'   => $current_import_id,
											'customers_current_import_page' => $current_import_page,
											'current_import_customer'       => $current_import_customer,
											'logs'                          => $logs,
										) );
									} else {
										$current_import_customer = - 1;
										$current_import_page ++;
										update_option( $history_option, $import_history );
										wp_send_json( array(
											'status'                        => 'success',
											'message'                       => sprintf( esc_html__( 'Importing... %s/%s completed', 's2w-import-shopify-to-woocommerce' ), $imported_customers, $import_history['total_customers'] ),
											'imported_customers'            => $imported_customers,
											'customers_current_import_id'   => $current_import_id,
											'customers_current_import_page' => $current_import_page,
											'current_import_customer'       => $current_import_customer,
											'logs'                          => $logs,
										) );
									}
								} else {
									update_option( $history_option, $import_history );
									wp_send_json( array(
										'status'                        => 'success',
										'message'                       => sprintf( esc_html__( 'Importing... %s/%s completed', 's2w-import-shopify-to-woocommerce' ), $imported_customers, $import_history['total_customers'] ),
										'imported_customers'            => $imported_customers,
										'customers_current_import_id'   => $current_import_id,
										'customers_current_import_page' => $current_import_page,
										'current_import_customer'       => $current_import_customer,
										'logs'                          => $logs,
									) );
								}
							}

							if ( $current_import_page == $total_pages ) {
								$import_history['time'] = current_time( 'timestamp' );
								update_option( $history_option, $import_history );

								wp_send_json( array(
									'status'                        => 'finish',
									'message'                       => sprintf( esc_html__( 'Completed %s/%s', 's2w-import-shopify-to-woocommerce' ), $import_history['total_customers'], $import_history['total_customers'] ),
									'imported_customers'            => $import_history['total_customers'],
									'customers_current_import_id'   => $current_import_id,
									'customers_current_import_page' => $current_import_page,
									'current_import_customer'       => $current_import_customer,
									'logs'                          => $logs,
								) );
							} else {
								$imported_customers      = $current_import_page * $customers_per_file;
								$current_import_customer = - 1;
								$current_import_page ++;
								update_option( $history_option, $import_history );
								wp_send_json( array(
									'status'                        => 'success',
									'message'                       => sprintf( esc_html__( 'Importing... %s/%s completed', 's2w-import-shopify-to-woocommerce' ), $imported_customers, $import_history['total_customers'] ),
									'imported_customers'            => $imported_customers,
									'customers_current_import_id'   => $current_import_id,
									'customers_current_import_page' => $current_import_page,
									'current_import_customer'       => $current_import_customer,
									'logs'                          => $logs,
								) );
							}
						}
						wp_send_json( array(
							'status'                        => 'finish',
							'message'                       => sprintf( esc_html__( 'Completed %s/%s', 's2w-import-shopify-to-woocommerce' ), $import_history['total_customers'], $import_history['total_customers'] ),
							'imported_customers'            => $import_history['total_customers'],
							'customers_current_import_id'   => $current_import_id,
							'customers_current_import_page' => $current_import_page,
							'current_import_customer'       => $current_import_customer,
							'logs'                          => $logs,
						) );
						break;
					case 'orders':
						self::remove_refunded_email_notification();
						$current_import_id    = isset( $_POST['orders_current_import_id'] ) ? sanitize_text_field( $_POST['orders_current_import_id'] ) : '';
						$current_import_order = isset( $_POST['current_import_order'] ) ? intval( sanitize_text_field( $_POST['current_import_order'] ) ) : - 1;
						$current_import_page  = isset( $_POST['orders_current_import_page'] ) ? absint( sanitize_text_field( $_POST['orders_current_import_page'] ) ) : 1;
						$total_pages          = isset( $_POST['orders_total_pages'] ) ? absint( sanitize_text_field( $_POST['orders_total_pages'] ) ) : 1;
						if ( ! $import_history ) {
							$history_data = $this->initiate_data( $history_option, $domain, $access_token, $api_key, $api_secret, $step );
							if ( $history_data['status'] === 'success' ) {
								$import_history = $history_data['data'];
								wp_send_json( array_merge( $import_history, array(
										'status' => 'retry'
									)
								) );
							} else {
								wp_send_json( array(
										'status'  => 'error',
										'code'    => $history_data['code'],
										'message' => $history_data['data']
									)
								);
							}
						} elseif ( ! empty( $import_history['time'] ) ) {
							/*If already imported, delete cache and history then reimport*/
							self::delete_cache_and_history( $step, $domain, $access_token, $api_key, $api_secret );
							$history_data = $this->initiate_data( $history_option, $domain, $access_token, $api_key, $api_secret, $step );
							if ( $history_data['status'] === 'success' ) {
								$import_history = $history_data['data'];
								wp_send_json( array_merge( $import_history, array(
										'status' => 'retry'
									)
								) );
							} else {
								wp_send_json( array(
										'status'  => 'error',
										'code'    => $history_data['code'],
										'message' => $history_data['data']
									)
								);
							}
						}
						$orders_per_request = self::$settings->get_params( 'orders_per_request' );
						$orders_per_file    = isset( $import_history['orders_per_file'] ) ? $import_history['orders_per_file'] : 250;
						if ( $total_pages >= $current_import_page ) {
							$file_path     = "{$path}{$step}_{$current_import_page}.txt";
							$orders        = array();
							$page_info_num = empty( $import_history["{$step}_page_info_num"] ) ? 1 : intval( $import_history["{$step}_page_info_num"] );
							if ( ! is_file( $file_path ) || $page_info_num < $current_import_page + 1 ) {
								$import_args = array();
								if ( ! empty( $import_history["{$step}_page_info"] ) && ! empty( $import_history["{$step}_page_info_num"] ) ) {
									$import_args['page_info'] = $import_history["{$step}_page_info"];
								} else {
									$this->add_filters_args( $import_args, $step );
								}
								$import_args['limit'] = $orders_per_file;
								$request              = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::wp_remote_get(
									$domain, $access_token, $api_key, $api_secret, $step, false, $import_args, self::$settings->get_params( 'request_timeout' ), true
								);
								if ( $request['status'] === 'success' ) {
									$orders = $request['data'];
									if ( is_array( $orders ) && count( $orders ) ) {
										file_put_contents( $file_path, json_encode( $orders ) );
									}
									if ( $request['pagination_link']['next'] ) {
										$page_info_num ++;
										$import_history["{$step}_page_info"]     = $request['pagination_link']['next'];
										$import_history["{$step}_page_info_num"] = $page_info_num;
										if ( $page_info_num < $current_import_page + 1 ) {
											update_option( $history_option, $import_history );
											wp_send_json( array_merge( $import_history, array(
													'status' => 'retry'
												)
											) );
										}
									}
								} else {
									wp_send_json( array(
										'status'  => 'error',
										'code'    => $request['code'],
										'message' => $request['data'],
									) );
								}
							} else {
								$orders = vi_s2w_json_decode( file_get_contents( $file_path ) );
							}
							$current_page_count = count( $orders );
							if ( $current_page_count ) {
								$current = $current_import_order;
								$max     = ( $current + $orders_per_request + 1 ) < $current_page_count ? ( $current + $orders_per_request + 1 ) : $current_page_count;
								wp_suspend_cache_invalidation( true );
								for ( $order_key = $current + 1; $order_key < $max; $order_key ++ ) {
									vi_s2w_set_time_limit();
									$current_import_order = $order_key;
									$order_data           = isset( $orders[ $order_key ] ) ? $orders[ $order_key ] : array();
									if ( is_array( $order_data ) && count( $order_data ) ) {
										$current_import_id = $order_data['id'];
										$existing_id       = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::query_get_id_by_shopify_id( $current_import_id );
										if ( $existing_id ) {
											$log['shopify_id']  = $current_import_id;
											$log['woo_id']      = $existing_id;
											$log['message']     = esc_html__( 'Order exists', 's2w-import-shopify-to-woocommerce' );
											$log['product_url'] = admin_url( 'post.php?post=' . $log['woo_id'] . '&action=edit' );
											$logs_content       = "[{$log['shopify_id']}]{$log['message']}, WP Post ID: {$log['woo_id']}";
											S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, $logs_content );
											$logs .= '<div><strong>#' . $existing_id . ' ' . $log['message'] . '.</strong>' . ( $log['product_url'] ? '<a href="' . esc_url( $log['product_url'] ) . '" target="_blank" rel="nofollow">View & edit</a>' : '' ) . '</div>';
											do_action( 's2w_bulk_import_item_exists', $existing_id, $step, $order_data );
										} else {
											if ( apply_filters( 's2w_import_orders_skip', false, $order_data ) ) {
												continue;
											}
											$order_id = self::import_order( $order_data );
											if ( $order_id ) {
												do_action( 's2w_bulk_import_item_successfully', $order_id, $step, $order_data );
											}
										}
									}
								}
								wp_suspend_cache_invalidation( false );
								$import_history['orders_current_import_id']   = $current_import_id;
								$import_history['current_import_order']       = $current_import_order;
								$import_history['orders_current_import_page'] = $current_import_page;
								$imported_orders                              = ( $current_import_page - 1 ) * $orders_per_file + $current_import_order + 1;
								if ( $current_import_order == $current_page_count - 1 ) {
									if ( $current_import_page == $total_pages ) {
										if ( self::woocommerce_analytics_enabled() ) {
											ReportsCache::invalidate();
										}
										$import_history['time'] = current_time( 'timestamp' );
										update_option( $history_option, $import_history );
										S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, 'Import orders successfully. Total: ' . $import_history['total_orders'] );
										wp_send_json( array(
											'status'                     => 'finish',
											'message'                    => sprintf( esc_html__( 'Completed %s/%s', 's2w-import-shopify-to-woocommerce' ), $imported_orders, $import_history['total_orders'] ),
											'imported_orders'            => $imported_orders,
											'orders_current_import_id'   => $current_import_id,
											'orders_current_import_page' => $current_import_page,
											'current_import_order'       => $current_import_order,
											'logs'                       => $logs,
										) );
									} else {
										$current_import_order = - 1;
										$current_import_page ++;
										update_option( $history_option, $import_history );
										wp_send_json( array(
											'status'                     => 'success',
											'message'                    => sprintf( esc_html__( 'Importing... %s/%s completed', 's2w-import-shopify-to-woocommerce' ), $imported_orders, $import_history['total_orders'] ),
											'imported_orders'            => $imported_orders,
											'orders_current_import_id'   => $current_import_id,
											'orders_current_import_page' => $current_import_page,
											'current_import_order'       => $current_import_order,
											'logs'                       => $logs,
										) );
									}
								} else {
									update_option( $history_option, $import_history );
									wp_send_json( array(
										'status'                     => 'success',
										'message'                    => sprintf( esc_html__( 'Importing... %s/%s completed', 's2w-import-shopify-to-woocommerce' ), $imported_orders, $import_history['total_orders'] ),
										'imported_orders'            => $imported_orders,
										'orders_current_import_id'   => $current_import_id,
										'orders_current_import_page' => $current_import_page,
										'current_import_order'       => $current_import_order,
										'logs'                       => $logs,
									) );
								}
							}

							if ( $current_import_page == $total_pages ) {
								$import_history['time'] = current_time( 'timestamp' );
								update_option( $history_option, $import_history );
								S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, 'Import orders successfully. Total: ' . $import_history['total_orders'] );
								wp_send_json( array(
									'status'                     => 'finish',
									'message'                    => sprintf( esc_html__( 'Completed %s/%s', 's2w-import-shopify-to-woocommerce' ), $import_history['total_orders'], $import_history['total_orders'] ),
									'imported_orders'            => $import_history['total_orders'],
									'orders_current_import_id'   => $current_import_id,
									'orders_current_import_page' => $current_import_page,
									'current_import_order'       => $current_import_order,
									'logs'                       => $logs,
								) );
							} else {
								$imported_orders      = $current_import_page * $orders_per_file;
								$current_import_order = - 1;
								$current_import_page ++;
								update_option( $history_option, $import_history );
								wp_send_json( array(
									'status'                     => 'success',
									'message'                    => sprintf( esc_html__( 'Importing... %s/%s completed', 's2w-import-shopify-to-woocommerce' ), $imported_orders, $import_history['total_orders'] ),
									'imported_orders'            => $imported_orders,
									'orders_current_import_id'   => $current_import_id,
									'orders_current_import_page' => $current_import_page,
									'current_import_order'       => $current_import_order,
									'logs'                       => $logs,
								) );
							}
						}
						S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, 'Import orders successfully. Total: ' . $import_history['total_orders'] );
						wp_send_json( array(
							'status'                     => 'finish',
							'message'                    => sprintf( esc_html__( 'Completed %s/%s', 's2w-import-shopify-to-woocommerce' ), $import_history['total_orders'], $import_history['total_orders'] ),
							'imported_orders'            => $import_history['total_orders'],
							'orders_current_import_id'   => $current_import_id,
							'orders_current_import_page' => $current_import_page,
							'current_import_order'       => $current_import_order,
							'logs'                       => $logs,
						) );
						break;
					case 'products':
						$download_images            = self::$settings->get_params( 'download_images' );
						$disable_background_process = self::$settings->get_params( 'disable_background_process' );
						$keep_slug                  = self::$settings->get_params( 'keep_slug' );
						$variable_sku               = self::$settings->get_params( 'variable_sku' );
						$global_attributes          = self::$settings->get_params( 'global_attributes' );
						$update_existing_products   = self::$settings->get_params( 'update_existing_products' );
						$product_status             = self::$settings->get_params( 'product_status' );
						$product_status_mapping     = self::$settings->get_params( 'product_status_mapping' );
						$product_categories         = self::$settings->get_params( 'product_categories' );
						$current_import_id          = isset( $_POST['current_import_id'] ) ? sanitize_text_field( $_POST['current_import_id'] ) : '';
						$current_import_product     = isset( $_POST['current_import_product'] ) ? intval( sanitize_text_field( $_POST['current_import_product'] ) ) : - 1;
						$current_import_page        = isset( $_POST['current_import_page'] ) ? absint( sanitize_text_field( $_POST['current_import_page'] ) ) : 1;
						$total_pages                = isset( $_POST['total_pages'] ) ? absint( sanitize_text_field( $_POST['total_pages'] ) ) : 1;
						if ( ! $history ) {
							$history_data = $this->initiate_products_data( $history_product_option, $domain, $access_token, $api_key, $api_secret );
							if ( $history_data['status'] === 'success' ) {
								$history = $history_data['data'];

								wp_send_json( array_merge( $history, array(
										'status' => 'retry'
									)
								) );
							} else {
								wp_send_json( array(
										'status'  => 'error',
										'message' => $history_data['data'],
										'code'    => $history_data['code'],
									)
								);
							}
						} elseif ( ! empty( $history['time'] ) ) {
							/*If already imported, delete cache and history then reimport*/
							self::delete_cache_and_history( $step, $domain, $access_token, $api_key, $api_secret );
							$history_data = $this->initiate_products_data( $history_product_option, $domain, $access_token, $api_key, $api_secret );
							if ( $history_data['status'] === 'success' ) {
								$history = $history_data['data'];

								wp_send_json( array_merge( $history, array(
										'status' => 'retry'
									)
								) );
							} else {
								wp_send_json( array(
										'status'  => 'error',
										'message' => $history_data['data'],
										'code'    => $history_data['code'],
									)
								);
							}
						}
						$product_author = self::$settings->get_params( 'product_author' );
						if ( $product_author && ( $product_author == get_current_user_id() || ! get_user_by( 'id', $product_author ) ) ) {
							$product_author = '';
						}
						$products_per_request = self::$settings->get_params( 'products_per_request' );
						$products_per_file    = isset( $history['products_per_file'] ) ? $history['products_per_file'] : 250;
						if ( $total_pages >= $current_import_page ) {
							$file_path     = $path . 'product_' . $current_import_page . '.txt';
							$products      = array();
							$page_info_num = empty( $history['page_info_num'] ) ? 1 : intval( $history['page_info_num'] );
							if ( ! is_file( $file_path ) || $page_info_num < $current_import_page + 1 ) {
								$import_args = array();
								if ( ! empty( $history['page_info'] ) && ! empty( $history['page_info_num'] ) ) {
									$import_args['page_info'] = $history['page_info'];
								} else {
									$this->add_filters_args( $import_args, $step );
								}
								$request = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::wp_remote_get(
									$domain, $access_token, $api_key, $api_secret, $step, false, $import_args, self::$settings->get_params( 'request_timeout' ), true
								);

								if ( $request['status'] === 'success' ) {
									$products = $request['data'];
									if ( is_array( $products ) && count( $products ) ) {
										file_put_contents( $file_path, json_encode( $products ) );
									}
									if ( $request['pagination_link']['next'] ) {
										$page_info_num ++;
										$history['page_info']     = $request['pagination_link']['next'];
										$history['page_info_num'] = $page_info_num;
										if ( $page_info_num < $current_import_page + 1 ) {
											update_option( $history_product_option, $history );
											wp_send_json( array_merge( $history, array(
													'status' => 'retry'
												)
											) );
										}
									}
								} else {
									wp_send_json( array(
										'status'  => 'error',
										'code'    => $request['code'],
										'message' => $request['data'],
									) );
								}
							} else {
								$products = vi_s2w_json_decode( file_get_contents( $file_path ) );
							}
							if ( is_array( $products ) && count( $products ) ) {
								$current = $current_import_product;
								$max     = ( $current + $products_per_request + 1 ) < count( $products ) ? ( $current + $products_per_request + 1 ) : count( $products );
								wp_suspend_cache_invalidation( true );
								for ( $key = $current + 1; $key < $max; $key ++ ) {
									vi_s2w_set_time_limit();
									$product_data = isset( $products[ $key ] ) ? $products[ $key ] : array();
									if ( is_array( $product_data ) && count( $product_data ) ) {
										$current_import_id = $product_data['id'];
										if ( ! empty( $product_status_mapping[ $product_data['status'] ] ) ) {
											$product_status = $product_status_mapping[ $product_data['status'] ];
										}
										if ( $product_status === 'not_import' ) {
											continue;
										}
										$log            = array(
											'shopify_id'  => $current_import_id,
											'woo_id'      => '',
											'title'       => $product_data['title'],
											'message'     => esc_html__( 'Import successfully', 's2w-import-shopify-to-woocommerce' ),
											'product_url' => '',
										);
										$product_status = apply_filters( 's2w_import_product_status', $product_status, $product_data );
										$sku            = str_replace( array(
											'{shopify_product_id}',
											'{product_slug}'
										), array( $current_import_id, $product_data['handle'] ), $variable_sku );
										$sku            = str_replace( ' ', '', $sku );

										$options     = isset( $product_data['options'] ) ? $product_data['options'] : array();
										$existing_id = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::product_get_woo_id_by_shopify_id( $current_import_id );
										if ( $existing_id && ! $update_existing_products ) {
											/*Skip if the product exists and update_existing_products option is OFF*/
											$log['woo_id']      = $existing_id;
											$log['message']     = esc_html__( 'Product exists', 's2w-import-shopify-to-woocommerce' );
											$log['title']       = get_the_title( $existing_id );
											$log['product_url'] = admin_url( 'post.php?post=' . $log['woo_id'] . '&action=edit' );
											$logs_content       = "[{$log['shopify_id']}]{$log['title']}: {$log['message']}, WC product ID: {$log['woo_id']}";
											S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, $logs_content );
											$logs .= '<div>' . $log['title'] . ': <strong>' . $log['message'] . '.</strong>' . ( $log['product_url'] ? '<a href="' . esc_url( $log['product_url'] ) . '" target="_blank" rel="nofollow">View & edit</a>' : '' ) . '</div>';
										} else {
											$import = false;
											if ( $existing_id || ! VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::sku_exists( $sku ) ) {
												$history['last_product_error'] = 1;
												if ( is_array( $options ) && count( $options ) ) {
													$import = true;
												}
											} elseif ( $error_log || $history['last_product_error'] ) {
												$product_id = wc_get_product_id_by_sku( $sku );
												if ( $product_id && is_array( $options ) && count( $options ) ) {
													$import      = true;
													$existing_id = $product_id;
												}
											} else {
												$log['woo_id']      = wc_get_product_id_by_sku( $sku );
												$log['message']     = 'Product SKU exists';
												$log['product_url'] = admin_url( 'post.php?post=' . $log['woo_id'] . '&action=edit' );
												$logs_content       = "[{$log['shopify_id']}]{$log['title']}: {$log['message']}, WC product ID: {$log['woo_id']}";
												S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, $logs_content );
												$logs .= '<div>' . $log['title'] . ': <strong>' . $log['message'] . '.</strong>' . ( $log['product_url'] ? '<a href="' . esc_url( $log['product_url'] ) . '" target="_blank" rel="nofollow">View & edit</a>' : '' ) . '</div>';
											}
											if ( $import ) {
												$dispatch    = false;
												$images_d    = array();
												$description = isset( $product_data['body_html'] ) ? html_entity_decode( $product_data['body_html'], ENT_QUOTES | ENT_XML1, 'UTF-8' ) : '';
												$product_id  = self::import_product( $options, $product_data, $sku, $global_attributes, $product_status, $keep_slug, $download_images, $product_categories, $images_d, $product_author, $existing_id );
												if ( ! is_wp_error( $product_id ) ) {
													self::handle_description_images( $description, $product_id, $dispatch, self::$process_new );
													if ( count( $images_d ) ) {
														if ( $disable_background_process ) {
															foreach ( $images_d as $images_d_k => $images_d_v ) {
																S2W_Error_Images_Table::insert( $product_id, implode( ',', $images_d_v['product_ids'] ), $images_d_v['src'], $images_d_v['alt'], intval( $images_d_v['set_gallery'] ), $images_d_v['id'] );
															}
														} else {
															$dispatch = true;
															foreach ( $images_d as $images_d_k => $images_d_v ) {
																self::$process_new->push_to_queue( $images_d_v );
															}
														}
													}
													if ( $dispatch ) {
														self::$process_new->save()->dispatch();
													}
													if ( $existing_id ) {
														$log['message'] = esc_html__( 'Updated', 's2w-import-shopify-to-woocommerce' );
													}
													$log['woo_id']                 = $product_id;
													$history['last_product_error'] = '';
													do_action( 's2w_bulk_import_item_successfully', $product_id, $step, $product_data );
													$log['product_url'] = admin_url( 'post.php?post=' . $log['woo_id'] . '&action=edit' );
													$logs_content       = "[{$log['shopify_id']}]{$log['title']}: {$log['message']}, WC product ID: {$log['woo_id']}";
													S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, $logs_content );
													$logs .= '<div>' . $log['title'] . ': <strong>' . $log['message'] . '.</strong>' . ( $log['product_url'] ? '<a href="' . esc_url( $log['product_url'] ) . '" target="_blank" rel="nofollow">View & edit</a>' : '' ) . '</div>';
												} else {
													$log['message'] = $product_id->get_error_message();
													$logs_content   = "[{$log['shopify_id']}]{$log['title']}: Error({$log['message']})";
													S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, $logs_content );
													$logs .= '<div>' . $log['title'] . ': <strong>' . $log['message'] . '.</strong>' . '</div>';
												}
											}
										}
									}
									$current_import_product            = $key;
									$history['current_import_id']      = $current_import_id;
									$history['current_import_product'] = $current_import_product;
									$history['current_import_page']    = $current_import_page;
									update_option( $history_product_option, $history );
								}
								wp_suspend_cache_invalidation( false );
								$imported_products = ( $current_import_page - 1 ) * $products_per_file + $current_import_product + 1;
								if ( $current_import_product == count( $products ) - 1 ) {
									if ( $current_import_page == $total_pages ) {
										$history['time'] = current_time( 'timestamp' );
										update_option( $history_product_option, $history );
										wp_send_json( array(
											'status'                 => 'finish',
											'message'                => sprintf( esc_html__( 'Completed %s/%s', 's2w-import-shopify-to-woocommerce' ), $history['total_products'], $history['total_products'] ),
											'imported_products'      => $imported_products,
											'current_import_id'      => $current_import_id,
											'current_import_page'    => $current_import_page,
											'current_import_product' => $current_import_product,
											'logs'                   => $logs,
										) );
									} else {
										$current_import_product = - 1;
										$current_import_page ++;
										$history['current_import_page'] = $current_import_page;
										update_option( $history_product_option, $history );
										wp_send_json( array(
											'status'                 => 'success',
											'message'                => sprintf( esc_html__( 'Importing... %s/%s completed', 's2w-import-shopify-to-woocommerce' ), $imported_products, $history['total_products'] ),
											'imported_products'      => $imported_products,
											'current_import_id'      => $current_import_id,
											'current_import_page'    => $current_import_page,
											'current_import_product' => $current_import_product,
											'logs'                   => $logs,
										) );
									}
								} else {
									update_option( $history_product_option, $history );
									wp_send_json( array(
										'status'                 => 'success',
										'message'                => sprintf( esc_html__( 'Importing... %s/%s completed', 's2w-import-shopify-to-woocommerce' ), $imported_products, $history['total_products'] ),
										'imported_products'      => $imported_products,
										'current_import_id'      => $current_import_id,
										'current_import_page'    => $current_import_page,
										'current_import_product' => $current_import_product,
										'logs'                   => $logs,
									) );
								}
							}
							if ( $current_import_page == $total_pages ) {
								$history['time'] = current_time( 'timestamp' );
								update_option( $history_product_option, $history );
								wp_send_json( array(
									'status'                 => 'finish',
									'message'                => sprintf( esc_html__( 'Completed %s/%s', 's2w-import-shopify-to-woocommerce' ), $history['total_products'], $history['total_products'] ),
									'imported_products'      => $history['total_products'],
									'current_import_id'      => $current_import_id,
									'current_import_page'    => $current_import_page,
									'current_import_product' => $current_import_product,
									'logs'                   => $logs,
								) );
							} else {
								$imported_products      = $current_import_page * $products_per_file;
								$current_import_product = - 1;
								$current_import_page ++;
								$history['current_import_page'] = $current_import_page;
								update_option( $history_product_option, $history );
								wp_send_json( array(
									'status'                 => 'success',
									'message'                => sprintf( esc_html__( 'Importing... %s/%s completed', 's2w-import-shopify-to-woocommerce' ), $imported_products, $history['total_products'] ),
									'imported_products'      => $imported_products,
									'current_import_id'      => $current_import_id,
									'current_import_page'    => $current_import_page,
									'current_import_product' => $current_import_product,
									'logs'                   => $logs,
								) );
							}
						}
						wp_send_json( array(
							'status'                 => 'finish',
							'message'                => sprintf( esc_html__( 'Completed %s/%s', 's2w-import-shopify-to-woocommerce' ), $history['total_products'], $history['total_products'] ),
							'imported_products'      => $history['total_products'],
							'current_import_id'      => $current_import_id,
							'current_import_page'    => $current_import_page,
							'current_import_product' => $current_import_product,
							'logs'                   => $logs,
						) );
						break;
					case 'product_categories':
						$file_path  = $path . 'categories.txt';
						$categories = array();
						if ( ! empty( $import_history['time'] ) ) {
							self::delete_cache_and_history( $step, $domain, $access_token, $api_key, $api_secret );
							$categories_data = $this->initiate_categories_data( $domain, $access_token, $api_key, $api_secret, $path );
							if ( $categories_data['status'] === 'success' ) {
								wp_send_json( array(
									'status'                  => 'retry',
									'categories_current_page' => 0,
									'total_categories'        => count( $categories_data['data'] ),
								) );
							} else {
								wp_send_json( array(
									'status'  => $categories_data['status'],
									'message' => $categories_data['data'],
								) );
							}
						} else {
							if ( ! is_file( $file_path ) ) {
								$categories_data = $this->initiate_categories_data( $domain, $access_token, $api_key, $api_secret, $path );
								if ( $categories_data['status'] === 'success' ) {
									wp_send_json( array(
										'status'                  => 'retry',
										'categories_current_page' => 0,
										'total_categories'        => count( $categories_data['data'] ),
									) );
								} else {
									wp_send_json( array(
										'status'  => $categories_data['status'],
										'message' => $categories_data['data'],
										'code'    => $categories_data['code'],
									) );
								}
							} else {
								$categories = vi_s2w_json_decode( file_get_contents( $file_path ) );
							}
						}
						$categories_current_page = isset( $_POST['categories_current_page'] ) ? $_POST['categories_current_page'] : 0;
						$total_categories        = count( $categories );
						if ( ! $total_categories ) {
							wp_send_json( array(
								'status'  => 'error',
								'message' => esc_html__( 'No data to import', 's2w-import-shopify-to-woocommerce' ),
							) );
						}
						if ( isset( $categories[ $categories_current_page ] ) ) {
							$category                 = $categories[ $categories_current_page ];
							$shopify_product_ids_file = $path . 'category_' . $category['shopify_id'] . '.txt';
							$shopify_product_ids      = array();
							if ( ! is_file( $shopify_product_ids_file ) ) {
								$shopify_product_ids_data = $this->get_product_ids_by_collection( $domain, $access_token, $api_key, $api_secret, $category['shopify_id'], $path );
								if ( $shopify_product_ids_data['status'] === 'success' ) {
									$shopify_product_ids = $shopify_product_ids_data['data'];
								} else {
									wp_send_json( array(
										'status'  => 'error',
										'message' => $shopify_product_ids_data['data'],
									) );
								}
							} else {
								$shopify_product_ids = vi_s2w_json_decode( file_get_contents( $shopify_product_ids_file ) );
							}
							if ( $category['woo_id'] && count( $shopify_product_ids ) ) {
								$args = array(
									'post_type'      => 'product',
									'post_status'    => array( 'publish', 'pending', 'draft' ),
									'posts_per_page' => - 1,
									'fields'         => 'ids',
									'meta_query'     => array(
										'relation' => 'AND',
										array(
											'key'     => '_shopify_product_id',
											'value'   => $shopify_product_ids,
											'compare' => 'IN'
										),
									)
								);

								$the_query = new WP_Query( $args );
								if ( $the_query->have_posts() ) {
									foreach ( $the_query->posts as $product_id ) {
										wp_set_post_terms( $product_id, $category['woo_id'], 'product_cat', true );
									}
								}
								wp_reset_postdata();
							}
						}
						$categories_current_page ++;
						if ( $categories_current_page < $total_categories ) {
							wp_send_json( array(
								'status'                  => 'success',
								'total_categories'        => $total_categories,
								'categories_current_page' => $categories_current_page,
							) );
						} else {
							update_option( $history_option, array(
								'time' => time(),
							) );
							S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, 'Import product categories successfully.' );
							wp_send_json( array(
								'status'                  => 'finish',
								'total_categories'        => $total_categories,
								'categories_current_page' => $categories_current_page,
								'message'                 => esc_html__( 'Completed', 's2w-import-shopify-to-woocommerce' ),
							) );
						}
						break;
					case 'store_settings':
						$file_path = $path . 'shop.txt';
						$settings  = array();
						if ( ! is_file( $file_path ) ) {
							$settings_data = $this->import_store_settings( $domain, $access_token, $api_key, $api_secret, $path );
							if ( 'success' == $settings_data['status'] ) {
								$settings = $settings_data['data'];
							} else {
								wp_send_json( array(
									'status'  => 'error',
									'message' => $settings_data['data'],
								) );
							}
						} else {
							$settings = vi_s2w_json_decode( file_get_contents( $file_path ) );
						}
						if ( is_array( $settings ) && count( $settings ) ) {
							$blog_name   = sanitize_text_field( $settings['name'] );
							$admin_email = sanitize_text_field( $settings['email'] );
							$time_zone   = sanitize_text_field( $settings['iana_timezone'] );

							$address           = sanitize_text_field( $settings['address1'] );
							$address_2         = sanitize_text_field( $settings['address2'] );
							$city              = sanitize_text_field( $settings['city'] );
							$country           = $settings['country_code'] ? sanitize_text_field( $settings['country_code'] ) : sanitize_text_field( $settings['country'] );
							$state             = sanitize_text_field( $settings['province'] );
							$postcode          = sanitize_text_field( $settings['zip'] );
							$weight_unit       = sanitize_text_field( $settings['weight_unit'] );
							$currency_code     = sanitize_text_field( $settings['currency'] );
							$money_format      = $settings['money_format'] ? trim( sanitize_text_field( $settings['money_format'] ) ) : trim( sanitize_text_field( $settings['money_with_currency_format'] ) );
							$money_format      = preg_replace( '!\s+!', ' ', $money_format );
							$currency_format_1 = strpos( $money_format, '{{amount}}' );
							$currency_format_2 = strpos( $money_format, '{{amount_no_decimals}}' );
							$currency_format_3 = strpos( $money_format, '{{amount_with_comma_separator}}' );
							$currency_format_4 = strpos( $money_format, '{{amount_no_decimals_with_comma_separator}}' );
							$currency_format_5 = strpos( $money_format, '{{amount_with_apostrophe_separator}}' );

							if ( ! $state ) {
								$state = '*';
							}
							if ( $blog_name ) {
								update_option( 'blogname', $blog_name );
							}
							if ( $admin_email ) {
								update_option( 'admin_email', $admin_email );
							}
							if ( $address ) {
								update_option( 'woocommerce_store_address', $address );
							}
							if ( $address_2 ) {
								update_option( 'woocommerce_store_address_2', $address_2 );
							}
							if ( $city ) {
								update_option( 'woocommerce_store_city', $city );
							}
							if ( $country || $state ) {
								update_option( 'woocommerce_default_country', $country . ':' . $state );
							}
							if ( $postcode ) {
								update_option( 'woocommerce_store_postcode', $postcode );
							}
							if ( $currency_code ) {
								update_option( 'woocommerce_currency', $currency_code );
							}
							if ( $weight_unit ) {
								update_option( 'woocommerce_weight_unit', $weight_unit );
							}
							$allowed_zones = timezone_identifiers_list();
							if ( $time_zone && in_array( $time_zone, $allowed_zones ) ) {
								update_option( 'timezone_string', $time_zone );
							}

							if ( false !== $currency_format_1 ) {
								update_option( 'woocommerce_price_decimal_sep', '.' );
								update_option( 'woocommerce_price_thousand_sep', ',' );
								if ( 0 != $currency_format_1 ) {
									if ( 1 == count( explode( ' ', $money_format ) ) ) {
										update_option( 'woocommerce_currency_pos', 'left' );
									} else {
										update_option( 'woocommerce_currency_pos', 'left_space' );
									}
								} else {
									if ( 1 == count( explode( ' ', $money_format ) ) ) {
										update_option( 'woocommerce_currency_pos', 'right' );
									} else {
										update_option( 'woocommerce_currency_pos', 'right_space' );
									}
								}
							} elseif ( false !== $currency_format_2 ) {
								update_option( 'woocommerce_price_num_decimals', 0 );
								update_option( 'woocommerce_price_thousand_sep', ',' );
								if ( 0 != $currency_format_2 ) {
									if ( 1 == count( explode( ' ', $money_format ) ) ) {
										update_option( 'woocommerce_currency_pos', 'left' );
									} else {
										update_option( 'woocommerce_currency_pos', 'left_space' );
									}
								} else {
									if ( 1 == count( explode( ' ', $money_format ) ) ) {
										update_option( 'woocommerce_currency_pos', 'right' );
									} else {
										update_option( 'woocommerce_currency_pos', 'right_space' );
									}
								}

							} elseif ( false !== $currency_format_3 ) {
								update_option( 'woocommerce_price_decimal_sep', ',' );
								update_option( 'woocommerce_price_thousand_sep', '.' );
								if ( 0 != $currency_format_3 ) {
									if ( 1 == count( explode( ' ', $money_format ) ) ) {
										update_option( 'woocommerce_currency_pos', 'left' );
									} else {
										update_option( 'woocommerce_currency_pos', 'left_space' );
									}
								} else {
									if ( 1 == count( explode( ' ', $money_format ) ) ) {
										update_option( 'woocommerce_currency_pos', 'right' );
									} else {
										update_option( 'woocommerce_currency_pos', 'right_space' );
									}
								}

							} elseif ( false !== $currency_format_4 ) {
								update_option( 'woocommerce_price_num_decimals', 0 );
								update_option( 'woocommerce_price_thousand_sep', '.' );
								if ( 0 != $currency_format_4 ) {
									if ( 1 == count( explode( ' ', $money_format ) ) ) {
										update_option( 'woocommerce_currency_pos', 'left' );
									} else {
										update_option( 'woocommerce_currency_pos', 'left_space' );
									}
								} else {
									if ( 1 == count( explode( ' ', $money_format ) ) ) {
										update_option( 'woocommerce_currency_pos', 'right' );
									} else {
										update_option( 'woocommerce_currency_pos', 'right_space' );
									}
								}
							} elseif ( false !== $currency_format_5 ) {
								update_option( 'woocommerce_price_decimal_sep', '.' );
								update_option( 'woocommerce_price_thousand_sep', "'" );
								if ( 0 != $currency_format_5 ) {
									if ( 1 == count( explode( ' ', $money_format ) ) ) {
										update_option( 'woocommerce_currency_pos', 'left' );
									} else {
										update_option( 'woocommerce_currency_pos', 'left_space' );
									}
								} else {
									if ( 1 == count( explode( ' ', $money_format ) ) ) {
										update_option( 'woocommerce_currency_pos', 'right' );
									} else {
										update_option( 'woocommerce_currency_pos', 'right_space' );
									}
								}
							}
							update_option( $history_option, array(
								'time' => time(),
							) );
							S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, 'Import store settings successfully' );
							wp_send_json( array(
								'status'  => 'success',
								'message' => esc_html__( 'Completed', 's2w-import-shopify-to-woocommerce' ),
							) );
						} else {
							wp_send_json( array(
								'status'  => 'error',
								'message' => esc_html__( 'No data to import', 's2w-import-shopify-to-woocommerce' ),
							) );
						}
						break;
					case 'pages':
						if ( ! $import_history ) {
							$history_data = $this->initiate_pages_data( $history_option, $domain, $access_token, $api_key, $api_secret );
							if ( $history_data['status'] === 'success' ) {
								$import_history = $history_data['data'];
								wp_send_json( array_merge( $import_history, array(
										'status' => 'retry'
									)
								) );
							} else {
								wp_send_json( array(
										'status'  => 'error',
										'message' => $history_data['data'],
										'code'    => $history_data['code'],
									)
								);
							}
						} elseif ( ! empty( $import_history['time'] ) ) {
							/*If already imported, delete cache and history then reimport*/
							self::delete_cache_and_history( $step, $domain, $access_token, $api_key, $api_secret );
							$history_data = $this->initiate_pages_data( $history_option, $domain, $access_token, $api_key, $api_secret );
							if ( $history_data['status'] === 'success' ) {
								$import_history = $history_data['data'];
								wp_send_json( array_merge( $import_history, array(
										'status' => 'retry'
									)
								) );
							} else {
								wp_send_json( array(
										'status'  => 'error',
										'message' => $history_data['data'],
										'code'    => $history_data['code'],
									)
								);
							}
						}
						$current_import_id    = isset( $_POST['spages_current_import_id'] ) ? sanitize_text_field( $_POST['spages_current_import_id'] ) : '';
						$current_import_spage = isset( $_POST['current_import_spage'] ) ? intval( sanitize_text_field( $_POST['current_import_spage'] ) ) : - 1;
						$current_import_page  = isset( $_POST['spages_current_import_page'] ) ? absint( sanitize_text_field( $_POST['spages_current_import_page'] ) ) : 1;
						$total_pages          = isset( $_POST['spages_total_pages'] ) ? absint( sanitize_text_field( $_POST['spages_total_pages'] ) ) : 1;
						$spages_per_request   = self::$settings->get_params( 'spages_per_request' );
						$spages_per_request   = $spages_per_request ? absint( $spages_per_request ) : self::$settings->get_default( 'spages_per_request' );
						$spages_per_file      = isset( $import_history['spages_per_file'] ) ? $import_history['spages_per_file'] : 250;
						if ( $total_pages >= $current_import_page ) {
							$file_path     = "{$path}{$step}_{$current_import_page}.txt";
							$spages        = array();
							$page_info_num = empty( $import_history["{$step}_page_info_num"] ) ? 1 : intval( $import_history["{$step}_page_info_num"] );
							if ( ! is_file( $file_path ) || $page_info_num < $current_import_page + 1 ) {
								$import_args = array();
								if ( ! empty( $import_history["{$step}_page_info"] ) && ! empty( $import_history["{$step}_page_info_num"] ) ) {
									$import_args['page_info'] = $import_history["{$step}_page_info"];
								} else {
									$this->add_filters_args( $import_args, $step );
								}
								$import_args['limit'] = $spages_per_file;
								$request              = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::wp_remote_get(
									$domain, $access_token, $api_key, $api_secret, $step, false, $import_args, self::$settings->get_params( 'request_timeout' ), true
								);
								if ( $request['status'] === 'success' ) {
									$spages = $request['data'];
									if ( is_array( $spages ) && count( $spages ) ) {
										file_put_contents( $file_path, json_encode( $spages ) );
									}
									if ( $request['pagination_link']['next'] ) {
										$page_info_num ++;
										$import_history["{$step}_page_info"]     = $request['pagination_link']['next'];
										$import_history["{$step}_page_info_num"] = $page_info_num;
										if ( $page_info_num < $current_import_page + 1 ) {
											update_option( $history_option, $import_history );
											wp_send_json( array_merge( $import_history, array(
													'status' => 'retry'
												)
											) );
										}
									}
								} else {
									wp_send_json( array(
										'status'  => 'error',
										'code'    => $request['code'],
										'message' => $request['data'],
									) );
								}
							} else {
								$spages = vi_s2w_json_decode( file_get_contents( $file_path ) );
							}
							$current_page_count = count( $spages );
							if ( $current_page_count ) {
								$current = $current_import_spage;
								$max     = ( $current + $spages_per_request + 1 ) < $current_page_count ? ( $current + $spages_per_request + 1 ) : $current_page_count;
								wp_suspend_cache_invalidation( true );
								for ( $spage_key = $current + 1; $spage_key < $max; $spage_key ++ ) {
									$current_import_spage                       = $spage_key;
									$import_history['spages_current_import_id'] = $current_import_id;
									$import_history['current_import_spage']     = $current_import_spage;
									$page                                       = isset( $spages[ $spage_key ] ) ? $spages[ $spage_key ] : array();
									if ( is_array( $page ) && count( $page ) ) {
										if ( ! VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::query_get_id_by_shopify_id( $page['id'], 'page' ) ) {
											$content = isset( $page['body_html'] ) ? html_entity_decode( $page['body_html'], ENT_QUOTES | ENT_XML1, 'UTF-8' ) : '';
											$title   = isset( $page['title'] ) ? wp_kses_post( $page['title'] ) : '';
											if ( $content ) {
												$post_id = wp_insert_post( array(
													'post_title'   => $title,
													'post_content' => $content,
													'post_status'  => 'publish',
													'post_type'    => 'page',
												), true );
												if ( is_wp_error( $post_id ) ) {
													S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, 'Error importing page ' . $page['id'] );
												} else {
													update_post_meta( $post_id, '_s2w_shopify_page_id', $page['id'] );
													update_post_meta( $post_id, '_s2w_shopify_domain', $domain );
												}
											}
										}
									}
								}
								wp_suspend_cache_invalidation( false );
								$import_history['current_import_spage']       = $current_import_spage;
								$import_history['spages_current_import_page'] = $current_import_page;
								$import_history['spages_current_import_id']   = $current_import_id;
								$imported_spages                              = ( $current_import_page - 1 ) * $spages_per_file + $current_import_spage + 1;
								if ( $current_import_spage == $current_page_count - 1 ) {
									if ( $current_import_page == $total_pages ) {
										$import_history['time'] = current_time( 'timestamp' );
										update_option( $history_option, $import_history );
										S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, 'Import pages successfully, total: ' . $import_history['total_spages'] );
										wp_send_json( array(
											'status'                     => 'finish',
											'message'                    => sprintf( esc_html__( 'Completed %s/%s', 's2w-import-shopify-to-woocommerce' ), $imported_spages, $import_history['total_spages'] ),
											'imported_spages'            => $imported_spages,
											'spages_current_import_id'   => $current_import_id,
											'spages_current_import_page' => $current_import_page,
											'current_import_spage'       => $current_import_spage,
											'logs'                       => $logs,
										) );
									} else {
										$current_import_spage = - 1;
										$current_import_page ++;
										update_option( $history_option, $import_history );
										wp_send_json( array(
											'status'                     => 'success',
											'message'                    => sprintf( esc_html__( 'Importing... %s/%s completed', 's2w-import-shopify-to-woocommerce' ), $imported_spages, $import_history['total_spages'] ),
											'imported_spages'            => $imported_spages,
											'spages_current_import_id'   => $current_import_id,
											'spages_current_import_page' => $current_import_page,
											'current_import_spage'       => $current_import_spage,
											'logs'                       => $logs,
										) );
									}
								} else {
									update_option( $history_option, $import_history );
									wp_send_json( array(
										'status'                     => 'success',
										'message'                    => sprintf( esc_html__( 'Importing... %s/%s completed', 's2w-import-shopify-to-woocommerce' ), $imported_spages, $import_history['total_spages'] ),
										'imported_spages'            => $imported_spages,
										'spages_current_import_id'   => $current_import_id,
										'spages_current_import_page' => $current_import_page,
										'current_import_spage'       => $current_import_spage,
										'logs'                       => $logs,
									) );
								}
							}

							if ( $current_import_page == $total_pages ) {
								$import_history['time'] = current_time( 'timestamp' );
								update_option( $history_option, $import_history );
								wp_send_json( array(
									'status'                     => 'finish',
									'message'                    => sprintf( esc_html__( 'Completed %s/%s', 's2w-import-shopify-to-woocommerce' ), $import_history['total_spages'], $import_history['total_spages'] ),
									'imported_spages'            => $import_history['total_spages'],
									'spages_current_import_id'   => $current_import_id,
									'spages_current_import_page' => $current_import_page,
									'current_import_spage'       => $current_import_spage,
									'logs'                       => $logs,
								) );
							} else {
								$imported_spages      = $current_import_page * $spages_per_file;
								$current_import_spage = - 1;
								$current_import_page ++;
								update_option( $history_option, $import_history );
								wp_send_json( array(
									'status'                     => 'success',
									'message'                    => sprintf( esc_html__( 'Importing... %s/%s completed', 's2w-import-shopify-to-woocommerce' ), $imported_spages, $import_history['total_spages'] ),
									'imported_spages'            => $imported_spages,
									'spages_current_import_id'   => $current_import_id,
									'spages_current_import_page' => $current_import_page,
									'current_import_spage'       => $current_import_spage,
									'logs'                       => $logs,
								) );
							}
						}
						wp_send_json( array(
							'status'                     => 'finish',
							'message'                    => sprintf( esc_html__( 'Completed %s/%s', 's2w-import-shopify-to-woocommerce' ), $import_history['total_spages'], $import_history['total_spages'] ),
							'imported_spages'            => $import_history['total_spages'],
							'spages_current_import_id'   => $current_import_id,
							'spages_current_import_page' => $current_import_page,
							'current_import_spage'       => $current_import_spage,
							'logs'                       => $logs,
						) );
						break;
					case 'blogs':
						$file_path = $path . 'blogs.txt';
						$blogs     = array();
						if ( ! empty( $import_history['time'] ) ) {
							self::delete_cache_and_history( $step, $domain, $access_token, $api_key, $api_secret );
							$blogs_data = $this->initiate_blogs_data( $domain, $access_token, $api_key, $api_secret, $path );
							if ( $blogs_data['status'] === 'success' ) {
								wp_send_json( array(
									'status'             => 'retry',
									'blogs_current_page' => 0,
									'total_blogs'        => count( $blogs_data['data'] ),
								) );
							} else {
								wp_send_json( array(
									'status'  => $blogs_data['status'],
									'message' => $blogs_data['data'],
									'code'    => $blogs_data['code'],
								) );
							}
						} else {
							if ( ! is_file( $file_path ) ) {
								$blogs_data = $this->initiate_blogs_data( $domain, $access_token, $api_key, $api_secret, $path );
								if ( $blogs_data['status'] === 'success' ) {
									wp_send_json( array(
										'status'             => 'retry',
										'blogs_current_page' => 0,
										'total_blogs'        => count( $blogs_data['data'] ),
									) );
								} else {
									wp_send_json( array(
										'status'  => $blogs_data['status'],
										'message' => $blogs_data['data'],
										'code'    => $blogs_data['code'],
									) );
								}
							} else {
								$blogs = vi_s2w_json_decode( file_get_contents( $file_path ) );
							}
						}
						$blogs_current_page = isset( $_POST['blogs_current_page'] ) ? $_POST['blogs_current_page'] : 0;
						$total_blogs        = count( $blogs );
						if ( ! $total_blogs ) {
							wp_send_json( array(
								'status'  => 'error',
								'code'    => 'no_data',
								'message' => esc_html__( 'No data to import', 's2w-import-shopify-to-woocommerce' ),
							) );
						}
						if ( isset( $blogs[ $blogs_current_page ] ) ) {
							$blog                     = $blogs[ $blogs_current_page ];
							$shopify_product_ids_file = $path . 'blog_' . $blog['id'] . '.txt';
							$articles                 = array();

							/*24-03-2023 - Remove check file for get new post created after the first imported blog*/
//							if ( ! is_file( $shopify_product_ids_file ) ) {
							$shopify_product_ids_data = $this->get_blog_post_ids_by_collection( $domain, $access_token, $api_key, $api_secret, $blog['id'], $path );
							if ( $shopify_product_ids_data['status'] === 'success' ) {
								$articles = $shopify_product_ids_data['data'];
							} else {
								wp_send_json( array(
									'status'  => 'error',
									'message' => $shopify_product_ids_data['data'],
								) );
							}
//							} else {
//								$articles = vi_s2w_json_decode( file_get_contents( $shopify_product_ids_file ) );
//							}

							if ( count( $articles ) ) {
								$dispatch              = false;
								$gmt_offset            = get_option( 'gmt_offset' );
								$blogs_update_if_exist = self::$settings->get_params( 'blogs_update_if_exist' );
								foreach ( $articles as $article ) {
									$existing_id = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::query_get_id_by_shopify_id( $article['id'], 'blog' );
									$description = isset( $article['body_html'] ) ? html_entity_decode( $article['body_html'], ENT_QUOTES | ENT_XML1, 'UTF-8' ) : '';
									if ( $existing_id ) {
										if ( count( $blogs_update_if_exist ) ) {
											$post_arr = array();
											if ( in_array( 'date', $blogs_update_if_exist ) ) {
												if ( ! empty( $article['created_at'] ) ) {
													$created_at                = $article['created_at'];
													$created_at_gmt            = strtotime( $created_at );
													$date_gmt                  = date( 'Y-m-d H:i:s', $created_at_gmt );
													$date                      = date( 'Y-m-d H:i:s', ( $created_at_gmt + $gmt_offset * 3600 ) );
													$post_arr['post_date']     = $date;
													$post_arr['post_date_gmt'] = $date_gmt;
												}
												if ( ! empty( $article['published_at'] ) ) {
													$post_arr['post_status']   = 'publish';
													$created_at                = $article['published_at'];
													$created_at_gmt            = strtotime( $created_at );
													$date_gmt                  = date( 'Y-m-d H:i:s', $created_at_gmt );
													$date                      = date( 'Y-m-d H:i:s', ( $created_at_gmt + $gmt_offset * 3600 ) );
													$post_arr['post_date']     = $date;
													$post_arr['post_date_gmt'] = $date_gmt;
												} else {
													$post_arr['post_status'] = 'private';
												}
												if ( ! empty( $article['updated_at'] ) ) {
													$updated_at                    = $article['updated_at'];
													$updated_at_gmt                = strtotime( $updated_at );
													$modified_gmt                  = date( 'Y-m-d H:i:s', $updated_at_gmt );
													$modified                      = date( 'Y-m-d H:i:s', ( $updated_at_gmt + $gmt_offset * 3600 ) );
													$post_arr['post_modified']     = $modified;
													$post_arr['post_modified_gmt'] = $modified_gmt;
												}
											}
											if ( in_array( 'description', $blogs_update_if_exist ) ) {
												if ( $description ) {
													$post_arr['post_content'] = $description;
													self::handle_post_description_images( $description, $existing_id, $dispatch );
												}
											}
											if ( in_array( 'categories', $blogs_update_if_exist ) ) {
												if ( $blog['title'] ) {
													wp_set_object_terms( $existing_id, explode( ',', $blog['title'] ), 'category', false );
												}
											}
											if ( in_array( 'tags', $blogs_update_if_exist ) ) {
												if ( $article['tags'] ) {
													wp_set_object_terms( $existing_id, explode( ',', $article['tags'] ), 'post_tag', false );
												}
											}
											if ( count( $post_arr ) ) {
												$post_arr['ID'] = $existing_id;
												wp_update_post( $post_arr );
												$log['shopify_id']  = $article['id'];
												$log['woo_id']      = $existing_id;
												$log['message']     = esc_html__( 'Update existing post', 's2w-import-shopify-to-woocommerce' );
												$log['title']       = get_the_title( $existing_id );
												$log['product_url'] = admin_url( 'post.php?post=' . $log['woo_id'] . '&action=edit' );
												$logs               .= '<div>' . $log['title'] . ': <strong>' . $log['message'] . '.</strong>' . ( $log['product_url'] ? '<a href="' . esc_url( $log['product_url'] ) . '" target="_blank" rel="nofollow">View & edit</a>' : '' ) . '</div>';
											} else {
												$log['shopify_id']  = $article['id'];
												$log['woo_id']      = $existing_id;
												$log['message']     = esc_html__( 'Blog post exists', 's2w-import-shopify-to-woocommerce' );
												$log['title']       = get_the_title( $existing_id );
												$log['product_url'] = admin_url( 'post.php?post=' . $log['woo_id'] . '&action=edit' );
												$logs_content       = "[{$log['shopify_id']}]{$log['title']}: {$log['message']}, WP Post ID: {$log['woo_id']}";
												S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, $logs_content );
												$logs .= '<div>' . $log['title'] . ': <strong>' . $log['message'] . '.</strong>' . ( $log['product_url'] ? '<a href="' . esc_url( $log['product_url'] ) . '" target="_blank" rel="nofollow">View & edit</a>' : '' ) . '</div>';
											}
										} else {
											$log['shopify_id']  = $article['id'];
											$log['woo_id']      = $existing_id;
											$log['message']     = esc_html__( 'Blog post exists', 's2w-import-shopify-to-woocommerce' );
											$log['title']       = get_the_title( $existing_id );
											$log['product_url'] = admin_url( 'post.php?post=' . $log['woo_id'] . '&action=edit' );
											$logs_content       = "[{$log['shopify_id']}]{$log['title']}: {$log['message']}, WP Post ID: {$log['woo_id']}";
											S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, $logs_content );
											$logs .= '<div>' . $log['title'] . ': <strong>' . $log['message'] . '.</strong>' . ( $log['product_url'] ? '<a href="' . esc_url( $log['product_url'] ) . '" target="_blank" rel="nofollow">View & edit</a>' : '' ) . '</div>';
										}
									} else {
										$title = isset( $article['title'] ) ? wp_kses_post( $article['title'] ) : '';
										if ( $description ) {
											$post_arr = array(
												'post_author'  => '',
												'post_title'   => $title,
												'post_content' => $description,
												'post_type'    => 'post',
												'post_excerpt' => $article['summary_html'],
											);

											if ( ! empty( $article['created_at'] ) ) {
												$created_at                = $article['created_at'];
												$created_at_gmt            = strtotime( $created_at );
												$date_gmt                  = date( 'Y-m-d H:i:s', $created_at_gmt );
												$date                      = date( 'Y-m-d H:i:s', ( $created_at_gmt + $gmt_offset * 3600 ) );
												$post_arr['post_date']     = $date;
												$post_arr['post_date_gmt'] = $date_gmt;
											}
											if ( ! empty( $article['published_at'] ) ) {
												$post_arr['post_status']   = 'publish';
												$created_at                = $article['published_at'];
												$created_at_gmt            = strtotime( $created_at );
												$date_gmt                  = date( 'Y-m-d H:i:s', $created_at_gmt );
												$date                      = date( 'Y-m-d H:i:s', ( $created_at_gmt + $gmt_offset * 3600 ) );
												$post_arr['post_date']     = $date;
												$post_arr['post_date_gmt'] = $date_gmt;
											} else {
												$post_arr['post_status'] = 'private';
											}
											if ( ! empty( $article['updated_at'] ) ) {
												$updated_at                    = $article['updated_at'];
												$updated_at_gmt                = strtotime( $updated_at );
												$modified_gmt                  = date( 'Y-m-d H:i:s', $updated_at_gmt );
												$modified                      = date( 'Y-m-d H:i:s', ( $updated_at_gmt + $gmt_offset * 3600 ) );
												$post_arr['post_modified']     = $modified;
												$post_arr['post_modified_gmt'] = $modified_gmt;
											}

											$post_id = wp_insert_post( $post_arr, true );
											if ( ! is_wp_error( $post_id ) ) {
												update_post_meta( $post_id, '_s2w_shopify_blog_id', $article['id'] );
												update_post_meta( $post_id, '_s2w_shopify_domain', $domain );
												if ( isset( $article['image']['src'] ) && $article['image']['src'] ) {
													$post_image = array(
														'post_id'           => $post_id,
														'src'               => $article['image']['src'],
														'alt'               => isset( $article['image']['alt'] ) ? $article['image']['alt'] : '',
														'description_image' => 0,
													);
													self::$process_post_image->push_to_queue( $post_image );
													$dispatch = true;
												}
												self::handle_post_description_images( $description, $post_id, $dispatch );
											}
											if ( $blog['title'] ) {
												wp_set_object_terms( $post_id, explode( ',', $blog['title'] ), 'category', false );
											}
											if ( $article['tags'] ) {
												wp_set_object_terms( $post_id, explode( ',', $article['tags'] ), 'post_tag', false );
											}
										}
									}
								}
								if ( $dispatch ) {
									self::$process_post_image->save()->dispatch();
								}
							}
						}
						$blogs_current_page ++;
						if ( $blogs_current_page < $total_blogs ) {
							wp_send_json( array(
								'status'             => 'success',
								'total_blogs'        => $total_blogs,
								'blogs_current_page' => $blogs_current_page,
								'logs'               => $logs,
							) );
						} else {
							update_option( $history_option, array(
								'time' => time(),
							) );
							S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, 'Import Blogs successfully.' );
							wp_send_json( array(
								'status'             => 'finish',
								'total_blogs'        => $total_blogs,
								'blogs_current_page' => $blogs_current_page,
								'message'            => esc_html__( 'Completed', 's2w-import-shopify-to-woocommerce' ),
								'logs'               => $logs,
							) );
						}
						break;
					case 'shipping_zones':
						$file_path      = $path . 'shipping_zones.txt';
						$shipping_zones = array();
						if ( ! is_file( $file_path ) ) {
							$shipping_zones_data = $this->initiate_shipping_zones_data( $domain, $access_token, $api_key, $api_secret, $path );
							if ( $shipping_zones_data['status'] === 'success' ) {
								$shipping_zones = $shipping_zones_data['data'];
							} else {
								wp_send_json( array(
									'status'  => 'error',
									'message' => $shipping_zones_data['data'],
								) );
							}
						} else {
							$shipping_zones = vi_s2w_json_decode( file_get_contents( $file_path ) );
						}
						if ( is_array( $shipping_zones ) && count( $shipping_zones ) ) {
							$wc_country = new WC_Countries();
							foreach ( $shipping_zones as $shipping_zone ) {
								$name                       = $shipping_zone['name'];
								$countries                  = $shipping_zone['countries'];
								$price_based_shipping_rates = $shipping_zone['price_based_shipping_rates'];
								if ( count( $countries ) == 1 && $countries[0]['code'] == "*" ) {
									/*global*/
									$zone = new WC_Shipping_Zone( 0 );
								} else {
									/*create new zone*/
									$zone = new WC_Shipping_Zone();
									$zone->set_zone_name( $name );
									foreach ( $countries as $country ) {
										$country_code = $country['code'];
										$states       = $country['provinces'];
										$woo_states   = $wc_country->get_states( $country_code );
										if ( is_array( $states ) ) {
											$add_country = true;
											if ( count( $states ) ) {
												foreach ( $states as $state ) {
													if ( isset( $woo_states[ $state['code'] ] ) ) {
														$zone->add_location( $country_code . ':' . $state['code'], 'state' );
														$add_country = false;
													}
												}
											}
											if ( $add_country ) {
												$zone->add_location( $country_code, 'country' );
											}
										}
									}
									$zone->save();
								}
								if ( is_array( $price_based_shipping_rates ) && count( $price_based_shipping_rates ) ) {
									foreach ( $price_based_shipping_rates as $price_based_shipping_rate ) {
										$shipping_name      = $price_based_shipping_rate['name'];
										$shipping_cost      = $price_based_shipping_rate['price'];
										$min_order_subtotal = $price_based_shipping_rate['min_order_subtotal'];
										if ( 0 == $shipping_cost ) {
											/*create free shipping method*/
											$shipping_method_id                   = $zone->add_shipping_method( 'free_shipping' );
											$shipping_method                      = WC_Shipping_Zones::get_shipping_method( $shipping_method_id );
											$shipping_method_option_key           = $shipping_method->get_instance_option_key();
											$shipping_method_option               = get_option( $shipping_method_option_key, array() );
											$shipping_method_option['min_amount'] = $min_order_subtotal;
											$shipping_method_option['requires']   = 'min_amount';
											$shipping_method_option['title']      = $shipping_name;
											update_option( $shipping_method_option_key, $shipping_method_option );
										} else {
											/*create flat rate shipping method*/
											$shipping_method_id                   = $zone->add_shipping_method( 'flat_rate' );
											$shipping_method                      = WC_Shipping_Zones::get_shipping_method( $shipping_method_id );
											$shipping_method_option_key           = $shipping_method->get_instance_option_key();
											$shipping_method_option               = get_option( $shipping_method_option_key, array() );
											$shipping_method_option['tax_status'] = 'taxable';
											$shipping_method_option['cost']       = $shipping_cost;
											$shipping_method_option['title']      = $shipping_name;
											update_option( $shipping_method_option_key, $shipping_method_option );
										}
									}
								}
							}
						}
						update_option( $history_option, array(
							'time' => time(),
						) );
						S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, 'Import shipping zones successfully.' );
						wp_send_json( array(
							'status'  => 'success',
							'message' => esc_html__( 'Completed', 's2w-import-shopify-to-woocommerce' ),
						) );
						break;
					case 'taxes':
						$file_path = $path . 'countries.txt';
						$countries = array();
						if ( ! is_file( $file_path ) ) {
							$countries_data = $this->initiate_countries_data( $domain, $access_token, $api_key, $api_secret, $path );
							if ( 'success' == $countries_data['status'] ) {
								$countries = $countries_data['data'];
							} else {
								wp_send_json( array(
									'status'  => 'error',
									'message' => $countries_data['data'],
								) );
							}
						} else {
							$countries = vi_s2w_json_decode( file_get_contents( $file_path ) );
						}
						$file_path1 = $path . 'shop.txt';
						$settings   = array();
						if ( ! is_file( $file_path1 ) ) {
							$settings_data = $this->import_store_settings( $domain, $access_token, $api_key, $api_secret, $path );
							if ( 'success' == $settings_data['status'] ) {
								$settings = $settings_data['data'];
							}
						} else {
							$settings = vi_s2w_json_decode( file_get_contents( $file_path1 ) );
						}
						$tax_rate_shipping = 0;
						if ( isset( $settings['tax_shipping'] ) && $settings['tax_shipping'] ) {
							$tax_rate_shipping = 1;
						}
						if ( is_array( $countries ) && count( $countries ) ) {
							foreach ( $countries as $country ) {
								$country_code = $country['code'];
								$tax_name     = $country['tax_name'];
								$states       = $country['provinces'];
								$tax_rate     = 100 * floatval( $country['tax'] );
								if ( is_array( $states ) && count( $states ) ) {
									foreach ( $states as $state ) {
										$tax_name    = $state['tax_name'];
										$tax_rate    = 100 * floatval( $state['tax'] );
										$tax_rates   = array(
											'tax_rate_country'  => $country_code,
											'tax_rate_state'    => $state['code'],
											'tax_rate'          => $tax_rate,
											'tax_rate_name'     => $tax_name,
											'tax_rate_priority' => 1,
											'tax_rate_shipping' => $tax_rate_shipping,
										);
										$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rates );
									}
								} else {
									$tax_rates   = array(
										'tax_rate_country'  => $country_code,
										'tax_rate_state'    => '',
										'tax_rate'          => $tax_rate,
										'tax_rate_name'     => $tax_name,
										'tax_rate_priority' => 1,
										'tax_rate_shipping' => $tax_rate_shipping,
									);
									$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rates );
								}
							}
							update_option( $history_option, array(
								'time' => time(),
							) );
							S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log::log( $log_file, 'Import tax successfully.' );
							wp_send_json( array(
								'status'  => 'success',
								'message' => esc_html__( 'Completed', 's2w-import-shopify-to-woocommerce' ),
							) );
						}
						wp_send_json( array(
							'status'  => 'error',
							'message' => esc_html__( 'No data to import', 's2w-import-shopify-to-woocommerce' ),
						) );

						break;
					default:
				}
			}
		}

		/**
		 * Apply filters from settings
		 *
		 * @param $import_args
		 * @param string $type
		 */
		public function add_filters_args( &$import_args, $type = 'products' ) {
			if ( ! is_array( $import_args ) ) {
				$import_args = array();
			}
			switch ( $type ) {
				case 'products':
					$product_since_id = self::$settings->get_params( 'product_since_id' );
					if ( $product_since_id ) {
						$import_args['since_id'] = $product_since_id;
					} else {
						$import_args['order'] = self::$settings->get_params( 'product_import_sequence' );
					}
					$product_created_at_min = self::$settings->get_params( 'product_created_at_min' );
					if ( $product_created_at_min ) {
						$import_args['created_at_min'] = date( DATE_ATOM, strtotime( $product_created_at_min ) );
					}
					$product_created_at_max = self::$settings->get_params( 'product_created_at_max' );
					if ( $product_created_at_max ) {
						$import_args['created_at_max'] = date( DATE_ATOM, strtotime( $product_created_at_max ) );
					}
					$product_published_at_min = self::$settings->get_params( 'product_published_at_min' );
					if ( $product_published_at_min ) {
						$import_args['published_at_min'] = date( DATE_ATOM, strtotime( $product_published_at_min ) );
					}
					$product_published_at_max = self::$settings->get_params( 'product_published_at_max' );
					if ( $product_published_at_max ) {
						$import_args['published_at_max'] = date( DATE_ATOM, strtotime( $product_published_at_max ) );
					}
					$product_collection_id = self::$settings->get_params( 'product_collection_id' );
					if ( $product_collection_id ) {
						$import_args['collection_id'] = $product_collection_id;
					}
					$product_product_type = self::$settings->get_params( 'product_product_type' );
					if ( $product_product_type ) {
						$import_args['product_type'] = urlencode( $product_product_type );
					}
					$product_vendor = self::$settings->get_params( 'product_vendor' );
					if ( $product_vendor ) {
						$import_args['vendor'] = $product_vendor;
					}
					$product_status         = array();
					$product_status_mapping = self::$settings->get_params( 'product_status_mapping' );
					foreach ( $product_status_mapping as $key => $value ) {
						if ( $value !== 'not_import' ) {
							$product_status[] = $key;
						}
					}
					$product_status = array_unique( $product_status );
					if ( count( $product_status ) && count( $product_status ) < 3 ) {
						$import_args['status'] = implode( ',', $product_status );
					}
					break;
				case 'orders':
					$import_args['status']             = 'any';
					$import_args['financial_status']   = self::$settings->get_params( 'order_financial_status' );
					$import_args['fulfillment_status'] = self::$settings->get_params( 'order_fulfillment_status' );
					$order_since_id                    = self::$settings->get_params( 'order_since_id' );
					if ( $order_since_id ) {
						$import_args['since_id'] = $order_since_id;
					} else {
						$import_args['order'] = 'processed_at ' . self::$settings->get_params( 'order_import_sequence' );
					}
					$order_processed_at_min = self::$settings->get_params( 'order_processed_at_min' );
					if ( $order_processed_at_min ) {
						$import_args['processed_at_min'] = date( DATE_ATOM, strtotime( $order_processed_at_min ) );
					}
					$order_processed_at_max = self::$settings->get_params( 'order_processed_at_max' );
					if ( $order_processed_at_max ) {
						$import_args['processed_at_max'] = date( DATE_ATOM, strtotime( $order_processed_at_max ) );
					}
					break;
				case 'coupons':
					$coupon_starts_at_min = self::$settings->get_params( 'coupon_starts_at_min' );
					if ( $coupon_starts_at_min ) {
						$import_args['starts_at_min'] = date( DATE_ATOM, strtotime( $coupon_starts_at_min ) );
					}
					$coupon_starts_at_max = self::$settings->get_params( 'coupon_starts_at_max' );
					if ( $coupon_starts_at_max ) {
						$import_args['starts_at_max'] = date( DATE_ATOM, strtotime( $coupon_starts_at_max ) );
					}
					$coupon_ends_at_min = self::$settings->get_params( 'coupon_ends_at_min' );
					if ( $coupon_ends_at_min ) {
						$import_args['ends_at_min'] = date( DATE_ATOM, strtotime( $coupon_ends_at_min ) );
					}
					$coupon_ends_at_max = self::$settings->get_params( 'coupon_ends_at_max' );
					if ( $coupon_ends_at_max ) {
						$import_args['ends_at_max'] = date( DATE_ATOM, strtotime( $coupon_ends_at_max ) );
					}
					$coupon_zero_times_used = self::$settings->get_params( 'coupon_zero_times_used' );
					if ( $coupon_zero_times_used ) {
						$import_args['times_used'] = 0;
					}
					break;
			}
		}

		/**
		 * Import options popup
		 */
		public function modal_option() {
			$modals = array_keys( self::get_import_options() );
			foreach ( $modals as $modal ) {
				?>
                <div class="<?php echo esc_attr( self::set( array(
					"import-options-modal",
					"import-{$modal}-options-modal",
					'hidden'
				) ) ) ?>">
                    <div class="<?php echo esc_attr( self::set( array(
						"import-options-overlay",
						"import-{$modal}-options-overlay"
					) ) ) ?>">
                    </div>
                    <div class="vi-ui segment <?php echo esc_attr( self::set( array(
						"import-options-main",
						"import-{$modal}-options-main"
					) ) ) ?>">
                    </div>
                    <div class="<?php echo esc_attr( self::set( array(
						"import-options-saving-overlay",
						"import-{$modal}-options-saving-overlay",
						'hidden'
					) ) ) ?>">
                    </div>
                </div>
				<?php
			}
		}

		/**
		 * @return array
		 */
		private static function get_import_options() {
			return array(
				'products'  => esc_html__( 'Import Products options ', 's2w-import-shopify-to-woocommerce' ),
				'orders'    => esc_html__( 'Import Orders options ', 's2w-import-shopify-to-woocommerce' ),
				'coupons'   => esc_html__( 'Import Coupons options ', 's2w-import-shopify-to-woocommerce' ),
				'customers' => esc_html__( 'Import Customers options ', 's2w-import-shopify-to-woocommerce' ),
				'blogs'     => esc_html__( 'Import Blogs options ', 's2w-import-shopify-to-woocommerce' ),
			);
		}

		/**
		 * Enqueue 3rd library: semantic ui, select2, sortable
		 *
		 * @param array $elements
		 * @param bool $exclude
		 */
		public static function enqueue_3rd_library( $elements = array(), $exclude = false ) {
			global $wp_scripts;
			$scripts         = $wp_scripts->registered;
			$exclude_dequeue = apply_filters( 's2w_exclude_dequeue_scripts', array( 'dokan-vue-bootstrap' ) );
			foreach ( $scripts as $k => $script ) {
				if ( in_array( $script->handle, $exclude_dequeue ) ) {
					continue;
				}
				preg_match( '/bootstrap/i', $k, $result );
				if ( count( array_filter( $result ) ) ) {
					unset( $wp_scripts->registered[ $k ] );
					wp_dequeue_script( $script->handle );
				}
			}
			wp_dequeue_script( 'select-js' );//Causes select2 error, from ThemeHunk MegaMenu Plus plugin
			wp_dequeue_style( 'eopa-admin-css' );
			$all_elements = array(
				'accordion',
				'button',
				'checkbox',
				'dropdown',
				'form',
				'icon',
				'input',
				'label',
				'menu',
				'message',
				'progress',
				'segment',
				'tab',
				'table',
				'select2',
				'step',
				'sortable',
			);
			if ( ! count( $elements ) ) {
				$elements = $all_elements;
			} elseif ( $exclude ) {
				$elements = array_diff( $all_elements, $elements );
			}
			foreach ( $elements as $element ) {
				if ( is_file( VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS_DIR . "{$element}.min.css" ) ) {
					wp_enqueue_style( "s2w-import-shopify-to-woocommerce-{$element}", VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . "{$element}.min.css" );
				} elseif ( is_file( VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS_DIR . "{$element}.css" ) ) {
					wp_enqueue_style( "s2w-import-shopify-to-woocommerce-{$element}", VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . "{$element}.css" );
				}
				if ( is_file( VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_JS_DIR . "{$element}.min.js" ) ) {
					wp_enqueue_script( "s2w-import-shopify-to-woocommerce-{$element}", VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_JS . "{$element}.min.js", array( 'jquery' ) );
				} elseif ( is_file( VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_JS_DIR . "{$element}.js" ) ) {
					wp_enqueue_script( "s2w-import-shopify-to-woocommerce-{$element}", VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_JS . "{$element}.js", array( 'jquery' ) );
				}
			}
			if ( in_array( 'sortable', $elements ) ) {
				wp_enqueue_script( 'jquery-ui-sortable' );
			}
			if ( in_array( 'select2', $elements ) ) {
				wp_enqueue_style( 'select2', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'select2.min.css' );
				if ( woocommerce_version_check( '3.0.0' ) ) {
					wp_enqueue_script( 'select2' );
				} else {
					wp_enqueue_script( 'select2-v4', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_JS . 'select2.js', array( 'jquery' ), '4.0.3' );
				}
			}
			if ( in_array( 'dropdown', $elements ) ) {
				wp_enqueue_style( 's2w-import-shopify-to-woocommerce-transition', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'transition.min.css' );
				wp_enqueue_script( 's2w-import-shopify-to-woocommerce-transition', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_JS . 'transition.min.js', array( 'jquery' ) );
				wp_enqueue_script( 's2w-import-shopify-to-woocommerce-address', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_JS . 'jquery.address-1.6.min.js', array( 'jquery' ) );
			}
		}

		/**
		 *
		 */
		public function admin_enqueue_scripts() {
			wp_enqueue_script( 's2w-import-shopify-to-woocommerce-cancel-download-images', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_JS . 'cancel-download-images.js', array( 'jquery' ), VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_VERSION );
			wp_localize_script( 's2w-import-shopify-to-woocommerce-cancel-download-images', 's2w_cancel_download_images_params', array(
				'i18n_confirm_cancel' => esc_html__( 'Do you want to cancel download Images for all products? You can update images anytime for products by going to admin products page.', 's2w-import-shopify-to-woocommerce' ),
				'i18n_confirm_remove' => esc_html__( 'Remove all images in the queue?', 's2w-import-shopify-to-woocommerce' ),
			) );
			global $pagenow;
			$page = isset( $_REQUEST['page'] ) ? sanitize_text_field( $_REQUEST['page'] ) : '';
			if ( $pagenow === 'admin.php' && $page === 's2w-import-shopify-to-woocommerce' ) {
				add_action( 'admin_footer', array( $this, 'modal_option' ) );
				$this->is_page = true;
				self::enqueue_3rd_library();
				wp_enqueue_style( 's2w-import-shopify-to-woocommerce-admin', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'admin-style.css' );
				wp_enqueue_style( 'villatheme-support', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'villatheme-support.css' );
				wp_enqueue_script( 's2w-html-scroll-handler', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_JS . 'html-scroll-handler.js', array( 'jquery' ), VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_VERSION );
				wp_enqueue_script( 's2w-import-shopify-to-woocommerce-admin', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_JS . 'admin-script.js', array( 'jquery' ), VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_VERSION );
				$domain                  = self::$settings->get_params( 'domain' );
				$history                 = get_option( 's2w_' . $domain . '_history', self::get_default_history( 'products' ) );
				$history_update_products = get_option( 's2w_' . $domain . '_history_update_products', self::get_default_history( 'update_products' ) );
				$history_orders          = get_option( 's2w_' . $domain . '_history_orders', self::get_default_history( 'orders' ) );
				$history_customers       = get_option( 's2w_' . $domain . '_history_customers', self::get_default_history( 'customers' ) );
				$history_spages          = get_option( 's2w_' . $domain . '_history_pages', self::get_default_history( 'spages' ) );
				$history_coupons         = get_option( 's2w_' . $domain . '_history_coupons', self::get_default_history( 'coupons' ) );

				$elements = array(
					'store_settings' => '',
					'payments'       => '',
					'shipping_zones' => '',
					'taxes'          => '',
					'pages'          => '',
					'blogs'          => '',
					'coupons'        => '',
				);

				foreach ( $elements as $key => $value ) {
					$element = get_option( 's2w_' . $domain . '_history_' . $key );
					if ( isset( $element['time'] ) && $element['time'] ) {
						$elements[ $key ] = 1;
					}
				}
				$elements['products']  = isset( $history['time'] ) && $history['time'] ? 1 : '';
				$elements['customers'] = isset( $history_customers['time'] ) && $history_customers['time'] ? 1 : '';
				$elements['coupons']   = isset( $history_coupons['time'] ) && $history_coupons['time'] ? 1 : '';
				$elements['orders']    = isset( $history_orders['time'] ) && $history_orders['time'] ? 1 : '';
				$settings              = $this->get_main_settings_args();
				if ( ! current_user_can( 'manage_options' ) ) {
					foreach ( $this->get_advanced_settings_keys() as $a_key ) {
						unset( $settings[ $a_key ] );
					}
				}
				$elements_titles = self::get_supported_elements();
				wp_localize_script( 's2w-import-shopify-to-woocommerce-admin', 's2w_params_admin', array_merge( $history_customers, $history_coupons, $history_orders, $history_update_products, $history, $history_spages, array(
					'url'                              => admin_url( 'admin-ajax.php' ),
					'warning_empty_store'              => esc_html__( 'Store address cannot be empty! ', 's2w-import-shopify-to-woocommerce' ),
					'warning_empty_api_key'            => esc_html__( 'API key cannot be empty! ', 's2w-import-shopify-to-woocommerce' ),
					'warning_empty_access_token'       => esc_html__( 'Access token cannot be empty! ', 's2w-import-shopify-to-woocommerce' ),
					'warning_empty_api_secret'         => esc_html__( 'API secret cannot be empty! ', 's2w-import-shopify-to-woocommerce' ),
					'error_connection'                 => esc_html__( 'Can not connect to your Shopify store. Please check your info.', 's2w-import-shopify-to-woocommerce' ),
					'error_assign_categories'          => esc_html__( 'Error assigning product categories', 's2w-import-shopify-to-woocommerce' ),
					'message_checking'                 => esc_html__( 'Checking, please wait ...', 's2w-import-shopify-to-woocommerce' ),
					'message_guide'                    => esc_html__( 'Click Import to start importing or Update cache to fetch new data to import', 's2w-import-shopify-to-woocommerce' ),
					'message_assign_categories'        => esc_html__( 'Assigning product categories.', 's2w-import-shopify-to-woocommerce' ),
					'message_importing'                => esc_html__( 'Importing...', 's2w-import-shopify-to-woocommerce' ),
					'message_waiting'                  => esc_html__( 'Waiting...', 's2w-import-shopify-to-woocommerce' ),
					'message_complete'                 => esc_html__( 'Completed', 's2w-import-shopify-to-woocommerce' ),
					'i18n_import_completed'            => esc_html__( 'Import completed.', 's2w-import-shopify-to-woocommerce' ),
					'i18n_required_data'               => esc_html__( 'Please select which data you want to import.', 's2w-import-shopify-to-woocommerce' ),
					'i18n_delete_history_warning'      => esc_html__( 'You are about to delete import history of selected elements. Continue?', 's2w-import-shopify-to-woocommerce' ),
					'i18n_reimport_warning'            => esc_html__( 'You already imported {imported_elements}. Do you want to continue?', 's2w-import-shopify-to-woocommerce' ),
					'i18n_category_search_placeholder' => esc_html__( 'Please fill in your category title', 's2w-import-shopify-to-woocommerce' ),
					'i18n_user_search_placeholder'     => esc_html__( 'Please enter user info to search', 's2w-import-shopify-to-woocommerce' ),
					'imported_elements'                => $elements,
					'elements_titles'                  => $elements_titles,
					'settings'                         => $settings,
					'validate_api'                     => current_user_can( 'manage_options' ) ? 1 : '',
				) ) );
			}
		}

		public function admin_menu() {
			add_menu_page(
				esc_html__( 'Import Shopify to WooCommerce', 's2w-import-shopify-to-woocommerce' ),
				esc_html__( 'Shopify to Woo', 's2w-import-shopify-to-woocommerce' ),
				self::get_required_capability(), 's2w-import-shopify-to-woocommerce', array(
				$this,
				'settings_callback'
			), 'dashicons-image-rotate-right', 2 );
		}

		public static function security_recommendation_html() {
			?>
            <div class="<?php echo esc_attr( self::set( 'security-warning' ) ) ?>">
                <div class="vi-ui warning message">
                    <div class="header">
						<?php esc_html_e( 'Shopify Admin API security recommendation', 's2w-import-shopify-to-woocommerce' ); ?>
                    </div>
                    <ul class="list">
                        <li><?php esc_html_e( 'You should enable only what is necessary for your app to work.', 's2w-import-shopify-to-woocommerce' ); ?></li>
                        <li><?php esc_html_e( 'Treat the API key and password like you would any other password, since whoever has access to these credentials has API access to the store.', 's2w-import-shopify-to-woocommerce' ); ?></li>
                        <li><?php esc_html_e( 'Change your API at least once a month', 's2w-import-shopify-to-woocommerce' ); ?></li>
                        <li><?php esc_html_e( 'If you only use API to import data, remove API permissions or delete the API after import completed', 's2w-import-shopify-to-woocommerce' ); ?></li>
                    </ul>
                </div>
            </div>
			<?php
		}

		public function settings_callback() {
			$active = self::$settings->get_params( 'validate' );
			?>
            <div class="wrap">
                <h2><?php esc_html_e( 'Import Shopify to WooCommerce', 's2w-import-shopify-to-woocommerce' ); ?></h2>
				<?php self::security_recommendation_html() ?>
                <p></p>
                <div class="<?php echo esc_attr( self::set( 'error-warning' ) ) ?>"
                     style="<?php if ( $active )
					     echo esc_attr( 'display:none' ) ?>">
                    <div class="vi-ui negative message"><?php esc_html_e( 'Please enter correct domain and access token(for Custom apps) or API key & password(for the deprecated Private apps)', 's2w-import-shopify-to-woocommerce' ); ?></div>
                </div>
                <p></p>
                <div class="vi-ui styled fluid accordion <?php if ( ! $active ) {
					echo esc_attr( 'active' );
				} ?> <?php echo esc_attr( self::set( 'accordion' ) ) ?>">
                    <div class='title'>
                        <i class="dropdown icon"></i>
						<?php esc_html_e( 'Settings', 's2w-import-shopify-to-woocommerce' ) ?>
                    </div>
                    <div class="content <?php if ( ! $active )
						echo esc_attr( 'active' ) ?>">
                        <form class="vi-ui form" method="post">
							<?php wp_nonce_field( 's2w_action_nonce', '_s2w_nonce' ); ?>
                            <div class="vi-ui segment">
                                <table class="form-table">
                                    <tbody>
									<?php
									if ( current_user_can( 'manage_options' ) ) {
										?>
                                        <tr>
                                            <th>
                                                <label for="<?php echo esc_attr( self::set( 'domain' ) ) ?>"><?php esc_html_e( 'Store address', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                            </th>
                                            <td>
                                                <input type="text"
                                                       name="<?php echo esc_attr( self::set( 'domain', true ) ) ?>"
                                                       id="<?php echo esc_attr( self::set( 'domain' ) ) ?>"
                                                       value="<?php echo esc_attr( htmlentities( self::$settings->get_params( 'domain' ) ) ) ?>">
                                                <p class="description"><?php echo __( 'Your Store address, eg: <strong>myshop.myshopify.com</strong>', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>
                                                <label for="<?php echo esc_attr( self::set( 'access_token' ) ) ?>"><?php esc_html_e( 'Access token', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                            </th>
                                            <td>
                                                <input type="text"
                                                       name="<?php echo esc_attr( self::set( 'access_token', true ) ) ?>"
                                                       id="<?php echo esc_attr( self::set( 'access_token' ) ) ?>"
                                                       value="<?php echo esc_attr( htmlentities( self::$settings->get_params( 'access_token' ) ) ) ?>">
                                                <p class="description"><?php _e( 'If you use <a target="_blank" href="https://help.shopify.com/en/manual/apps/custom-apps">custom apps</a>, please enter access token here. API key and API password are not used in this case so just ignore them.', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                                <p class="description"><?php _e( 'If you still need to use the deprecated private apps, please delete the access token field above.', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>
                                                <label for="<?php echo esc_attr( self::set( 'api_key' ) ) ?>"><?php esc_html_e( 'API key', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                            </th>
                                            <td>
                                                <input type="text"
                                                       name="<?php echo esc_attr( self::set( 'api_key', true ) ) ?>"
                                                       id="<?php echo esc_attr( self::set( 'api_key' ) ) ?>"
                                                       value="<?php echo esc_attr( htmlentities( self::$settings->get_params( 'api_key' ) ) ) ?>">
                                                <p class="description"><?php _e( '<a target="_blank" href="https://help.shopify.com/en/manual/apps/private-apps">Private apps</a> are deprecated and can\'t be created as of January 2022 but existing apps keys can still be used.', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>
                                                <label for="<?php echo esc_attr( self::set( 'api_secret' ) ) ?>"><?php esc_html_e( 'API secret(Password)', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                            </th>
                                            <td>
                                                <input type="text"
                                                       name="<?php echo esc_attr( self::set( 'api_secret', true ) ) ?>"
                                                       id="<?php echo esc_attr( self::set( 'api_secret' ) ) ?>"
                                                       value="<?php echo esc_attr( htmlentities( self::$settings->get_params( 'api_secret' ) ) ) ?>">
                                                <p class="description"><?php _e( '<a target="_blank" href="https://help.shopify.com/en/manual/apps/private-apps">Private apps</a> are deprecated and can\'t be created as of January 2022 but existing apps keys can still be used.', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th></th>
                                            <td>
                                                <div class='title'>
                                                    <i class="dropdown icon"></i>
													<?php esc_html_e( 'Learn how to get Access token', 's2w-import-shopify-to-woocommerce' ) ?>
                                                </div>
                                                <div class="content">
                                                    <iframe width="560" height="315"
                                                            src="https://www.youtube-nocookie.com/embed/1kyEp4d_cfw"
                                                            title="YouTube video player" frameborder="0"
                                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                                            allowfullscreen></iframe>
                                                </div>
                                            </td>
                                        </tr>
										<?php
									} else {
										?>
                                        <tr>
                                            <td colspan="2">
                                                <div class="vi-ui small message">
                                                    <div class="header"><?php esc_html_e( 'Only Administrators can access API credentials', 's2w-import-shopify-to-woocommerce' ); ?></div>
                                                </div>
                                            </td>
                                        </tr>
										<?php
									}
									?>
                                    <tr>
                                        <th>
                                            <label for="<?php echo esc_attr( self::set( 'request_timeout' ) ) ?>"><?php esc_html_e( 'Request timeout(s)', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                        </th>
                                        <td>
                                            <input type="number" min="60"
                                                   name="<?php echo esc_attr( self::set( 'request_timeout', true ) ) ?>"
                                                   id="<?php echo esc_attr( self::set( 'request_timeout' ) ) ?>"
                                                   value="<?php echo esc_attr( self::$settings->get_params( 'request_timeout' ) ) ?>">
                                        </td>
                                    </tr>
									<?php
									if ( current_user_can( 'manage_options' ) ) {
										?>
                                        <tr>
                                            <th>
                                                <label for="auto-update-key"><?php esc_html_e( 'Auto Update Key', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                            </th>
                                            <td>
                                                <div class="fields">
                                                    <div class="ten wide field">
                                                        <input type="text"
                                                               name="<?php echo esc_attr( self::set( 'auto_update_key', true ) ) ?>"
                                                               id="auto-update-key"
                                                               class="villatheme-autoupdate-key-field"
                                                               value="<?php echo esc_attr( htmlentities( self::$settings->get_params( 'auto_update_key' ) ) ) ?>">
                                                    </div>
                                                    <div class="six wide field">
                                                        <span class="vi-ui button green villatheme-get-key-button"
                                                              data-href="https://api.envato.com/authorization?response_type=code&client_id=villatheme-download-keys-6wzzaeue&redirect_uri=https://villatheme.com/update-key"
                                                              data-id="23741313"><?php esc_html_e( 'Get Key', 's2w-import-shopify-to-woocommerce' ) ?></span>
                                                    </div>
                                                </div>
												<?php do_action( 's2w-import-shopify-to-woocommerce_key' ) ?>
                                                <p class="description"><?php echo __( 'Please fill your key what you get from <a target="_blank" href="https://villatheme.com/my-download">VillaTheme</a> to automatically update S2W - Import Shopify to WooCommerce plugin. See guide <a target="_blank" href="https://villatheme.com/knowledge-base/how-to-use-auto-update-feature/">here</a>', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2">
                                                <div class="vi-ui positive small message">
                                                    <div class="header"><?php esc_html_e( 'Manage required user capability to be able to change settings or use plugin', 's2w-import-shopify-to-woocommerce' ); ?></div>
                                                    <ul class="list">
                                                        <li><?php esc_html_e( 'Only Administrators can access required user capability settings, API credentials and Auto update key', 's2w-import-shopify-to-woocommerce' ); ?></li>
                                                        <li><?php esc_html_e( 'manage_options = only Administrators can access', 's2w-import-shopify-to-woocommerce' ); ?></li>
                                                        <li><?php esc_html_e( 'manage_woocommerce = both Administrators and Shop managers can access', 's2w-import-shopify-to-woocommerce' ); ?></li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
										<?php
										$capabilities = self::$settings->get_params( 'capabilities' );
										foreach ( VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_functionality() as $key => $value ) {
											$get_capabilities = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_capabilities( $key );
											$capability       = isset( $capabilities[ $key ] ) ? $capabilities[ $key ] : '';
											?>
                                            <tr>
                                                <th><?php echo esc_html( $value ) ?></th>
                                                <td>
                                                    <select data-capability_key="<?php echo esc_attr( $key ) ?>"
                                                            name="<?php echo esc_attr( self::set( 'capabilities', true ) . '[' . $key . ']' ) ?>"
                                                            id="<?php echo esc_attr( self::set( "capability-$key" ) ) ?>"
                                                            class="vi-ui dropdown <?php echo esc_attr( self::set( 'capabilities' ) ) ?>">
														<?php
														foreach ( $get_capabilities as $get_capability ) {
															?>
                                                            <option value="<?php echo esc_attr( $get_capability ) ?>" <?php selected( $capability, $get_capability ) ?>><?php echo esc_html( $get_capability ) ?></option>
															<?php
														}
														?>
                                                    </select>
                                                </td>
                                            </tr>
											<?php
										}
									}
									?>
                                    </tbody>
                                </table>
                            </div>
							<?php
							foreach ( self::get_import_options() as $option_name => $option_head ) {
								?>
                                <div class='title' data-option_name="<?php echo esc_attr( $option_name ) ?>"
                                     id="<?php echo esc_attr( self::set( "import-{$option_name}-options-anchor" ) ) ?>">
                                    <i class="dropdown icon"></i>
                                    <span><?php echo esc_html( $option_head ) ?></span>
                                </div>
                                <div class="content" data-option_name="<?php echo esc_attr( $option_name ) ?>">
                                    <div class="vi-ui segment"
                                         id="<?php echo esc_attr( self::set( "import-{$option_name}-options" ) ) ?>">
                                        <div class="<?php echo esc_attr( self::set( array(
											'import-options-content',
											"import-{$option_name}-options-content"
										) ) ) ?>">
                                            <div class="<?php echo esc_attr( self::set( array(
												'import-options-heading',
												"import-{$option_name}-options-heading"
											) ) ) ?>">
                                                <i class="close icon <?php echo esc_attr( self::set( array(
													'import-options-close',
												) ) ) ?>" data-option_name="<?php echo esc_attr( $option_name ) ?>"></i>
                                                <h3><?php echo esc_html( $option_head ) ?></h3>
                                            </div>
											<?php
											switch ( $option_name ) {
												case 'products':
													?>
                                                    <table class="form-table">
                                                        <tbody>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'products_per_request' ) ) ?>"><?php esc_html_e( 'Products per ajax request', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
                                                                <input type="number" min="1" max="250"
                                                                       name="<?php echo esc_attr( self::set( 'products_per_request', true ) ) ?>"
                                                                       id="<?php echo esc_attr( self::set( 'products_per_request' ) ) ?>"
                                                                       value="<?php echo esc_attr( self::$settings->get_params( 'products_per_request' ) ) ?>">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'product_since_id' ) ) ?>"><?php esc_html_e( 'Restrict results to after the specified ID', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
                                                                <input type="text"
                                                                       name="<?php echo esc_attr( self::set( 'product_since_id', true ) ) ?>"
                                                                       id="<?php echo esc_attr( self::set( 'product_since_id' ) ) ?>"
                                                                       value="<?php echo esc_attr( self::$settings->get_params( 'product_since_id' ) ) ?>">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'product_product_type' ) ) ?>"><?php esc_html_e( 'Filter results by product type', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
                                                                <input type="text"
                                                                       name="<?php echo esc_attr( self::set( 'product_product_type', true ) ) ?>"
                                                                       id="<?php echo esc_attr( self::set( 'product_product_type' ) ) ?>"
                                                                       value="<?php echo esc_attr( self::$settings->get_params( 'product_product_type' ) ) ?>">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'product_vendor' ) ) ?>"><?php esc_html_e( 'Filter results by product Vendor', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
                                                                <input type="text"
                                                                       name="<?php echo esc_attr( self::set( 'product_vendor', true ) ) ?>"
                                                                       id="<?php echo esc_attr( self::set( 'product_vendor' ) ) ?>"
                                                                       value="<?php echo esc_attr( self::$settings->get_params( 'product_vendor' ) ) ?>">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'product_collection_id' ) ) ?>"><?php esc_html_e( 'Filter results by collection ID', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
                                                                <input type="text"
                                                                       name="<?php echo esc_attr( self::set( 'product_collection_id', true ) ) ?>"
                                                                       id="<?php echo esc_attr( self::set( 'product_collection_id' ) ) ?>"
                                                                       value="<?php echo esc_attr( self::$settings->get_params( 'product_collection_id' ) ) ?>">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'product_created_at_min' ) ) ?>"><?php esc_html_e( 'Import products created after date', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
                                                                <input type="date"
                                                                       name="<?php echo esc_attr( self::set( 'product_created_at_min', true ) ) ?>"
                                                                       id="<?php echo esc_attr( self::set( 'product_created_at_min' ) ) ?>"
                                                                       value="<?php echo esc_attr( self::$settings->get_params( 'product_created_at_min' ) ) ?>">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'product_created_at_max' ) ) ?>"><?php esc_html_e( 'Import products created before date', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
                                                                <input type="date"
                                                                       name="<?php echo esc_attr( self::set( 'product_created_at_max', true ) ) ?>"
                                                                       id="<?php echo esc_attr( self::set( 'product_created_at_max' ) ) ?>"
                                                                       value="<?php echo esc_attr( self::$settings->get_params( 'product_created_at_max' ) ) ?>">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'product_published_at_min' ) ) ?>"><?php esc_html_e( 'Import products published after date', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
                                                                <input type="date"
                                                                       name="<?php echo esc_attr( self::set( 'product_published_at_min', true ) ) ?>"
                                                                       id="<?php echo esc_attr( self::set( 'product_published_at_min' ) ) ?>"
                                                                       value="<?php echo esc_attr( self::$settings->get_params( 'product_published_at_min' ) ) ?>">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'product_published_at_max' ) ) ?>"><?php esc_html_e( 'Import products published before date', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
                                                                <input type="date"
                                                                       name="<?php echo esc_attr( self::set( 'product_published_at_max', true ) ) ?>"
                                                                       id="<?php echo esc_attr( self::set( 'product_published_at_max' ) ) ?>"
                                                                       value="<?php echo esc_attr( self::$settings->get_params( 'product_published_at_max' ) ) ?>">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'product_import_sequence' ) ) ?>"><?php esc_html_e( 'Import Products sequence', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
                                                                <select name="<?php echo esc_attr( self::set( 'product_import_sequence', true ) ) ?>"
                                                                        class="vi-ui fluid dropdown"
                                                                        id="<?php echo esc_attr( self::set( 'product_import_sequence' ) ) ?>">
                                                                    <option value="title asc" <?php selected( 'title asc', self::$settings->get_params( 'product_import_sequence' ) ) ?>><?php esc_html_e( 'Order by Title Ascending', 's2w-import-shopify-to-woocommerce' ) ?></option>
                                                                    <option value="title desc" <?php selected( 'title desc', self::$settings->get_params( 'product_import_sequence' ) ) ?>><?php esc_html_e( 'Order by Title Descending', 's2w-import-shopify-to-woocommerce' ) ?></option>
                                                                    <option value="created_at asc" <?php selected( 'created_at asc', self::$settings->get_params( 'product_import_sequence' ) ) ?>><?php esc_html_e( 'Order by Created Date Ascending', 's2w-import-shopify-to-woocommerce' ) ?></option>
                                                                    <option value="created_at desc" <?php selected( 'created_at desc', self::$settings->get_params( 'product_import_sequence' ) ) ?>><?php esc_html_e( 'Order by Created Date Descending', 's2w-import-shopify-to-woocommerce' ) ?></option>
                                                                    <option value="updated_at asc" <?php selected( 'updated_at asc', self::$settings->get_params( 'product_import_sequence' ) ) ?>><?php esc_html_e( 'Order by Updated Date Ascending', 's2w-import-shopify-to-woocommerce' ) ?></option>
                                                                    <option value="updated_at desc" <?php selected( 'updated_at desc', self::$settings->get_params( 'product_import_sequence' ) ) ?>><?php esc_html_e( 'Order by Updated Date Descending', 's2w-import-shopify-to-woocommerce' ) ?></option>
                                                                </select>
                                                                <p><?php esc_html_e( 'This is to sort the results after applying all filters above if any', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td colspan="2">
                                                                <div class="vi-ui message">
                                                                    <div class="description"><?php esc_html_e( 'Below options are also applied when you import/update products with other methods such as Webhooks, CSV...', 's2w-import-shopify-to-woocommerce' ) ?></div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'update_existing_products' ) ) ?>"><?php esc_html_e( 'Update existing products', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
                                                                <div class="vi-ui toggle checkbox checked">
                                                                    <input type="checkbox"
                                                                           name="<?php echo esc_attr( self::set( 'update_existing_products', true ) ) ?>"
                                                                           id="<?php echo esc_attr( self::set( 'update_existing_products' ) ) ?>"
                                                                           value="1" <?php checked( self::$settings->get_params( 'update_existing_products' ), '1' ) ?>>
                                                                    <label for="<?php echo esc_attr( self::set( 'update_existing_products' ) ) ?>"><?php esc_html_e( 'A product is checked for existence by its Shopify ID.', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                                </div>
                                                                <p><?php esc_html_e( 'If disabled, it will skip if a product exists.', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'global_attributes' ) ) ?>"><?php esc_html_e( 'Use global attributes', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
                                                                <div class="vi-ui toggle checkbox checked">
                                                                    <input type="checkbox"
                                                                           name="<?php echo esc_attr( self::set( 'global_attributes', true ) ) ?>"
                                                                           id="<?php echo esc_attr( self::set( 'global_attributes' ) ) ?>"
                                                                           value="1" <?php checked( self::$settings->get_params( 'global_attributes' ), '1' ) ?>>
                                                                    <label for="<?php echo esc_attr( self::set( 'global_attributes' ) ) ?>"><?php esc_html_e( 'WC product filters plugin, Variations Swatch plugin... only work with global attributes.', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'download_images' ) ) ?>"><?php esc_html_e( 'Import images', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
                                                                <div class="vi-ui toggle checkbox checked">
                                                                    <input type="checkbox"
                                                                           name="<?php echo esc_attr( self::set( 'download_images', true ) ) ?>"
                                                                           id="<?php echo esc_attr( self::set( 'download_images' ) ) ?>"
                                                                           value="1" <?php checked( self::$settings->get_params( 'download_images' ), '1' ) ?>>
                                                                    <label for="<?php echo esc_attr( self::set( 'download_images' ) ) ?>"><?php esc_html_e( 'Product images will be imported in the background.', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                                </div>
                                                                <p class="description"><?php esc_html_e( '*It\' much faster to turn this option off while importing products. You can import images later that after all products are imported by going to Products.', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                                            </td>
                                                        </tr>
														<?php
														if ( class_exists( 'EXMAGE_WP_IMAGE_LINKS' ) ) {
															?>
                                                            <tr>
                                                                <th>
                                                                    <label for="<?php echo esc_attr( self::set( 'use_external_image' ) ) ?>"><?php esc_html_e( 'Use external links for images', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                                </th>
                                                                <td>
                                                                    <div class="vi-ui toggle checkbox checked">
                                                                        <input type="checkbox"
                                                                               name="<?php echo esc_attr( self::set( 'use_external_image', true ) ) ?>"
                                                                               id="<?php echo esc_attr( self::set( 'use_external_image' ) ) ?>"
                                                                               value="1" <?php checked( self::$settings->get_params( 'use_external_image' ), '1' ) ?>>
                                                                        <label for="<?php echo esc_attr( self::set( 'use_external_image' ) ) ?>"><?php esc_html_e( 'Product image, gallery and variation image will still use their original Shopify cdn image URLs', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                                    </div>
                                                                    <p class="description"><?php esc_html_e( 'This helps save storage but you will not be able to edit them', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                                                </td>
                                                            </tr>
															<?php
														}
														?>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'download_description_images' ) ) ?>"><?php esc_html_e( 'Migrate description images', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
                                                                <div class="vi-ui toggle checkbox checked">
                                                                    <input type="checkbox"
                                                                           name="<?php echo esc_attr( self::set( 'download_description_images', true ) ) ?>"
                                                                           id="<?php echo esc_attr( self::set( 'download_description_images' ) ) ?>"
                                                                           value="1" <?php checked( self::$settings->get_params( 'download_description_images' ), '1' ) ?>>
                                                                    <label for="<?php echo esc_attr( self::set( 'download_description_images' ) ) ?>"><?php esc_html_e( 'Import images from product description to your WordPress server. If disabled, images in description will still use Shopify cdn link', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'disable_background_process' ) ) ?>"><?php esc_html_e( 'Disable background processing', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
                                                                <div class="vi-ui toggle checkbox checked">
                                                                    <input type="checkbox"
                                                                           name="<?php echo esc_attr( self::set( 'disable_background_process', true ) ) ?>"
                                                                           id="<?php echo esc_attr( self::set( 'disable_background_process' ) ) ?>"
                                                                           value="1" <?php checked( self::$settings->get_params( 'disable_background_process' ), '1' ) ?>>
                                                                    <label for="<?php echo esc_attr( self::set( 'disable_background_process' ) ) ?>"><?php _e( 'Product images and description images will be added to <a href="admin.php?page=s2w-import-shopify-to-woocommerce-error-images" target="_blank">Failed images</a> list so that you can go there to import all images with 1 click. This is recommended if your server is weak or if you usually have duplicated images issue.', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'keep_slug' ) ) ?>"><?php esc_html_e( 'Keep product slug', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
                                                                <div class="vi-ui toggle checkbox checked">
                                                                    <input type="checkbox"
                                                                           name="<?php echo esc_attr( self::set( 'keep_slug', true ) ) ?>"
                                                                           id="<?php echo esc_attr( self::set( 'keep_slug' ) ) ?>"
                                                                           value="1" <?php checked( self::$settings->get_params( 'keep_slug' ), '1' ) ?>>
                                                                    <label for="<?php echo esc_attr( self::set( 'keep_slug' ) ) ?>"></label>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'variable_sku' ) ) ?>"><?php esc_html_e( 'Variable product SKU', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
                                                                <input type="text"
                                                                       name="<?php echo esc_attr( self::set( 'variable_sku', true ) ) ?>"
                                                                       id="<?php echo esc_attr( self::set( 'variable_sku' ) ) ?>"
                                                                       value="<?php echo esc_attr( self::$settings->get_params( 'variable_sku' ) ) ?>">
                                                                <p><?php esc_html_e( 'SKU is unique in WooCommerce but Shopify does not have a parent product, only variants which are variations in WooCommerce.', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                                                <p><?php esc_html_e( '{shopify_product_id} - The ID of product in Shopify', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                                                <p><?php esc_html_e( '{product_slug} - The Slug of product in Shopify', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'product_status_mapping' ) ) ?>"><?php esc_html_e( 'Product status mapping', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
                                                                <div>
																	<?php
																	$statuses               = array(
																		'publish'    => esc_html__( 'Publish', 's2w-import-shopify-to-woocommerce' ),
																		'pending'    => esc_html__( 'Pending', 's2w-import-shopify-to-woocommerce' ),
																		'draft'      => esc_html__( 'Draft', 's2w-import-shopify-to-woocommerce' ),
																		'not_import' => esc_html__( 'Not import', 's2w-import-shopify-to-woocommerce' ),
																	);
																	$product_status_mapping = self::$settings->get_params( 'product_status_mapping' );
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
																		foreach ( VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_shopify_product_statuses() as $from => $from_v ) {
																			$to = isset( $product_status_mapping[ $from ] ) ? $product_status_mapping[ $from ] : '';
																			?>
                                                                            <tr>
                                                                                <td><?php echo esc_html( $from_v ) ?></td>
                                                                                <td>
                                                                                    <select class="vi-ui fluid dropdown <?php echo esc_attr( self::set( 'product_status_mapping' ) ) ?>"
                                                                                            data-from_status="<?php echo esc_attr( $from ) ?>"
                                                                                            name="<?php echo esc_attr( self::set( 'product_status_mapping', true ) . '[' . $from . ']' ) ?>">
																						<?php
																						foreach ( $statuses as $st => $status ) {
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
                                                                <div style="display: none;">
                                                                    <select class="vi-ui fluid dropdown"
                                                                            id="<?php echo esc_attr( self::set( 'product_status' ) ) ?>"
                                                                            name="<?php echo esc_attr( self::set( 'product_status', true ) ) ?>">
                                                                        <option value="publish" <?php selected( self::$settings->get_params( 'product_status' ), 'publish' ) ?>><?php esc_html_e( 'Publish', 's2w-import-shopify-to-woocommerce' ) ?></option>
                                                                        <option value="pending" <?php selected( self::$settings->get_params( 'product_status' ), 'pending' ) ?>><?php esc_html_e( 'Pending', 's2w-import-shopify-to-woocommerce' ) ?></option>
                                                                        <option value="draft" <?php selected( self::$settings->get_params( 'product_status' ), 'draft' ) ?>><?php esc_html_e( 'Draft', 's2w-import-shopify-to-woocommerce' ) ?></option>
                                                                    </select>
                                                                </div>
                                                                <p><?php esc_html_e( 'Status of products after importing successfully', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'product_categories' ) ) ?>"><?php esc_html_e( 'Product categories', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
                                                                <div>
                                                                    <select class="search-category"
                                                                            id="<?php echo esc_attr( self::set( 'product_categories' ) ) ?>"
                                                                            name="<?php echo esc_attr( self::set( 'product_categories', true ) ) ?>[]"
                                                                            multiple="multiple">
																		<?php

																		if ( is_array( self::$settings->get_params( 'product_categories' ) ) && count( self::$settings->get_params( 'product_categories' ) ) ) {
																			foreach ( self::$settings->get_params( 'product_categories' ) as $category_id ) {
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
                                                                </div>
                                                                <p><?php esc_html_e( 'Choose categories you want to add imported products to', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                                            </td>
                                                        </tr>
														<?php
														$product_taxonomies = get_taxonomies( array( 'object_type' => array( 'product' ) ), 'objects' );
														$product_type_as    = self::$settings->get_params( 'product_type_as' );
														?>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'product_type_as' ) ) ?>"><?php esc_html_e( 'Import Shopify product type as', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
                                                                <select class="vi-ui dropdown"
                                                                        id="<?php echo esc_attr( self::set( 'product_type_as' ) ) ?>"
                                                                        name="<?php echo esc_attr( self::set( 'product_type_as', true ) ) ?>">
                                                                    <option value=""><?php esc_html_e( 'Do not import', 's2w-import-shopify-to-woocommerce' ) ?></option>
                                                                    <option value="category" <?php selected( $product_type_as, 'category' ) ?>><?php esc_html_e( 'Category', 's2w-import-shopify-to-woocommerce' ) ?></option>
                                                                    <option value="tag" <?php selected( $product_type_as, 'tag' ) ?>><?php esc_html_e( 'Tag', 's2w-import-shopify-to-woocommerce' ) ?></option>
                                                                    <option value="meta" <?php selected( $product_type_as, 'meta' ) ?>><?php esc_html_e( 'Post meta', 's2w-import-shopify-to-woocommerce' ) ?></option>
																	<?php
																	foreach ( $product_taxonomies as $product_taxonomy => $product_taxonomy_obj ) {
																		if ( in_array( $product_taxonomy, array(
																				'product_cat',
																				'product_tag',
																				'product_type',
																			), true ) || substr( $product_taxonomy, 0, 3 ) === 'pa_' ) {
																			continue;
																		}
																		?>
                                                                        <option value="<?php echo esc_attr( $product_taxonomy ) ?>" <?php selected( $product_type_as, $product_taxonomy ) ?>><?php echo esc_html( $product_taxonomy_obj->label ) ?></option>
																		<?php
																	}
																	?>
                                                                </select>
                                                                <div class="vi-ui input">
                                                                    <input type="text"
                                                                           name="<?php echo esc_attr( self::set( 'product_type_meta', true ) ) ?>"
                                                                           id="<?php echo esc_attr( self::set( 'product_type_meta' ) ) ?>"
                                                                           placeholder="<?php esc_attr_e( 'Enter post meta key here', 's2w-import-shopify-to-woocommerce' ) ?>"
                                                                           value="<?php echo esc_attr( self::$settings->get_params( 'product_type_meta' ) ) ?>">
                                                                </div>
                                                            </td>
                                                        </tr>
														<?php
														$product_vendor_as = self::$settings->get_params( 'product_vendor_as' );
														?>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'product_vendor_as' ) ) ?>"><?php esc_html_e( 'Import Shopify product vendor as', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
                                                                <select class="vi-ui dropdown"
                                                                        id="<?php echo esc_attr( self::set( 'product_vendor_as' ) ) ?>"
                                                                        name="<?php echo esc_attr( self::set( 'product_vendor_as', true ) ) ?>">
                                                                    <option value=""><?php esc_html_e( 'Do not import', 's2w-import-shopify-to-woocommerce' ) ?></option>
                                                                    <option value="category" <?php selected( $product_vendor_as, 'category' ) ?>><?php esc_html_e( 'Category', 's2w-import-shopify-to-woocommerce' ) ?></option>
                                                                    <option value="tag" <?php selected( $product_vendor_as, 'tag' ) ?>><?php esc_html_e( 'Tag', 's2w-import-shopify-to-woocommerce' ) ?></option>
                                                                    <option value="meta" <?php selected( $product_vendor_as, 'meta' ) ?>><?php esc_html_e( 'Post meta', 's2w-import-shopify-to-woocommerce' ) ?></option>
																	<?php
																	foreach ( $product_taxonomies as $product_taxonomy => $product_taxonomy_obj ) {
																		if ( in_array( $product_taxonomy, array(
																				'product_cat',
																				'product_tag',
																				'product_type',
																			), true ) || substr( $product_taxonomy, 0, 3 ) === 'pa_' ) {
																			continue;
																		}
																		?>
                                                                        <option value="<?php echo esc_attr( $product_taxonomy ) ?>" <?php selected( $product_vendor_as, $product_taxonomy ) ?>><?php echo esc_html( $product_taxonomy_obj->label ) ?></option>
																		<?php
																	}
																	?>
                                                                </select>
                                                                <div class="vi-ui input">
                                                                    <input type="text"
                                                                           name="<?php echo esc_attr( self::set( 'product_vendor_meta', true ) ) ?>"
                                                                           id="<?php echo esc_attr( self::set( 'product_vendor_meta' ) ) ?>"
                                                                           placeholder="<?php esc_attr_e( 'Enter post meta key here', 's2w-import-shopify-to-woocommerce' ) ?>"
                                                                           value="<?php echo esc_attr( self::$settings->get_params( 'product_vendor_meta' ) ) ?>">
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'product_barcode_meta' ) ) ?>"><?php esc_html_e( 'Import Shopify product barcode as post meta', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
                                                                <div class="vi-ui fluid input">
                                                                    <input type="text"
                                                                           name="<?php echo esc_attr( self::set( 'product_barcode_meta', true ) ) ?>"
                                                                           id="<?php echo esc_attr( self::set( 'product_barcode_meta' ) ) ?>"
                                                                           placeholder="<?php esc_attr_e( 'Enter post meta key here', 's2w-import-shopify-to-woocommerce' ) ?>"
                                                                           value="<?php echo esc_attr( self::$settings->get_params( 'product_barcode_meta' ) ) ?>">
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'product_author' ) ) ?>"><?php esc_html_e( 'Product author', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
                                                                <div>
                                                                    <select class="search-user"
                                                                            id="<?php echo esc_attr( self::set( 'product_author' ) ) ?>"
                                                                            name="<?php echo esc_attr( self::set( 'product_author', true ) ) ?>">
																		<?php
																		$user_id = self::$settings->get_params( 'product_author' );
																		$user    = get_user_by( 'id', $user_id );
																		if ( $user ) {
																			?>
                                                                            <option value="<?php echo esc_attr( $user_id ) ?>"
                                                                                    selected><?php echo esc_html( $user->display_name ); ?></option>
																			<?php
																		}
																		?>
                                                                    </select>
                                                                </div>
                                                                <p><?php esc_html_e( 'Set author for imported products. If empty, product author will be the user who runs the import.', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                                            </td>
                                                        </tr>
                                                        </tbody>
                                                    </table>
													<?php
													break;
												case 'orders':
													?>
                                                    <table class="form-table">
                                                        <tbody>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'orders_per_request' ) ) ?>"><?php esc_html_e( 'Orders per ajax request', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
                                                                <input type="number" min="1" max="250"
                                                                       name="<?php echo esc_attr( self::set( 'orders_per_request', true ) ) ?>"
                                                                       id="<?php echo esc_attr( self::set( 'orders_per_request' ) ) ?>"
                                                                       value="<?php echo esc_attr( self::$settings->get_params( 'orders_per_request' ) ) ?>">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'order_since_id' ) ) ?>"><?php esc_html_e( 'Restrict results to after the specified ID', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
                                                                <input type="text"
                                                                       name="<?php echo esc_attr( self::set( 'order_since_id', true ) ) ?>"
                                                                       id="<?php echo esc_attr( self::set( 'order_since_id' ) ) ?>"
                                                                       value="<?php echo esc_attr( self::$settings->get_params( 'order_since_id' ) ) ?>">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'order_processed_at_min' ) ) ?>"><?php esc_html_e( 'Import orders created/imported at or after date ', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
                                                                <input type="date"
                                                                       name="<?php echo esc_attr( self::set( 'order_processed_at_min', true ) ) ?>"
                                                                       id="<?php echo esc_attr( self::set( 'order_processed_at_min' ) ) ?>"
                                                                       value="<?php echo esc_attr( self::$settings->get_params( 'order_processed_at_min' ) ) ?>">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'order_processed_at_max' ) ) ?>"><?php esc_html_e( 'Import orders created/imported at or before date ', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
                                                                <input type="date"
                                                                       name="<?php echo esc_attr( self::set( 'order_processed_at_max', true ) ) ?>"
                                                                       id="<?php echo esc_attr( self::set( 'order_processed_at_max' ) ) ?>"
                                                                       value="<?php echo esc_attr( self::$settings->get_params( 'order_processed_at_max' ) ) ?>">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'order_financial_status' ) ) ?>"><?php esc_html_e( 'Filter orders by financial status', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
																<?php
																$financial_statuses = array(
																	'any'                => esc_html__( 'All', 's2w-import-shopify-to-woocommerce' ),
																	'authorized'         => esc_html__( 'Authorized', 's2w-import-shopify-to-woocommerce' ),
																	'pending'            => esc_html__( 'Pending', 's2w-import-shopify-to-woocommerce' ),
																	'paid'               => esc_html__( 'Paid', 's2w-import-shopify-to-woocommerce' ),
																	'partially_paid'     => esc_html__( 'Partly paid', 's2w-import-shopify-to-woocommerce' ),
																	'refunded'           => esc_html__( 'Refunded', 's2w-import-shopify-to-woocommerce' ),
																	'partially_refunded' => esc_html__( 'Partly refunded', 's2w-import-shopify-to-woocommerce' ),
																	'voided'             => esc_html__( 'Voided', 's2w-import-shopify-to-woocommerce' ),
																	'unpaid'             => esc_html__( 'Unpaid', 's2w-import-shopify-to-woocommerce' ),
																);
																?>
                                                                <select name="<?php echo esc_attr( self::set( 'order_financial_status', true ) ) ?>"
                                                                        class="vi-ui fluid dropdown"
                                                                        id="<?php echo esc_attr( self::set( 'order_financial_status' ) ) ?>">
																	<?php
																	foreach ( $financial_statuses as $financial_status_k => $financial_status_v ) {
																		?>
                                                                        <option value="<?php echo esc_attr( $financial_status_k ); ?>" <?php selected( $financial_status_k, self::$settings->get_params( 'order_financial_status' ) ) ?>><?php echo esc_html( $financial_status_v ) ?></option>
																		<?php
																	}
																	?>
                                                                </select>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'order_fulfillment_status' ) ) ?>"><?php esc_html_e( 'Filter orders by fulfillment status', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
																<?php
																$fulfillment_statuses = array(
																	'any'         => esc_html__( 'All', 's2w-import-shopify-to-woocommerce' ),
																	'shipped'     => esc_html__( 'Shipped', 's2w-import-shopify-to-woocommerce' ),
																	'partial'     => esc_html__( 'Partial', 's2w-import-shopify-to-woocommerce' ),
																	'unshipped'   => esc_html__( 'Unshipped', 's2w-import-shopify-to-woocommerce' ),
																	'unfulfilled' => esc_html__( 'Unfulfilled', 's2w-import-shopify-to-woocommerce' ),
																);
																?>
                                                                <select name="<?php echo esc_attr( self::set( 'order_fulfillment_status', true ) ) ?>"
                                                                        class="vi-ui fluid dropdown"
                                                                        id="<?php echo esc_attr( self::set( 'order_fulfillment_status' ) ) ?>">
																	<?php
																	foreach ( $fulfillment_statuses as $fulfillment_status_k => $fulfillment_status_v ) {
																		?>
                                                                        <option value="<?php echo esc_attr( $fulfillment_status_k ); ?>" <?php selected( $fulfillment_status_k, self::$settings->get_params( 'order_fulfillment_status' ) ) ?>><?php echo esc_html( $fulfillment_status_v ) ?></option>
																		<?php
																	}
																	?>
                                                                </select>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'order_import_sequence' ) ) ?>"><?php esc_html_e( 'Import Orders sequence', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
                                                                <select name="<?php echo esc_attr( self::set( 'order_import_sequence', true ) ) ?>"
                                                                        class="vi-ui fluid dropdown"
                                                                        id="<?php echo esc_attr( self::set( 'order_import_sequence' ) ) ?>">
                                                                    <option value="asc" <?php selected( 'asc', self::$settings->get_params( 'order_import_sequence' ) ) ?>><?php esc_html_e( 'From oldest to latest orders', 's2w-import-shopify-to-woocommerce' ) ?></option>
                                                                    <option value="desc" <?php selected( 'desc', self::$settings->get_params( 'order_import_sequence' ) ) ?>><?php esc_html_e( 'From latest to oldest orders', 's2w-import-shopify-to-woocommerce' ) ?></option>
                                                                </select>
                                                                <p><?php esc_html_e( 'This is to sort the results after applying all filters above if any', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'order_status_mapping' ) ) ?>"><?php esc_html_e( 'Order status mapping', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
                                                                <div>
																	<?php
																	$statuses             = wc_get_order_statuses();
																	$order_status_mapping = self::$settings->get_params( 'order_status_mapping' );
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
                                                                                <td><?php echo esc_html( ucwords( str_replace( '_', ' ', $from ) ) ) ?></td>
                                                                                <td>
                                                                                    <select class="vi-ui fluid dropdown <?php echo esc_attr( self::set( 'order_status_mapping' ) ) ?>"
                                                                                            data-from_status="<?php echo esc_attr( $from ) ?>"
                                                                                            name="<?php echo esc_attr( self::set( 'order_status_mapping', true ) . '[' . $from . ']' ) ?>">
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
														<?php do_action( 's2w_settings_page_end_of_orders_options' ) ?>
                                                        </tbody>
                                                    </table>
													<?php
													break;
												case 'coupons':
													?>
                                                    <div class="vi-ui warning message small">
														<?php esc_html_e( 'Shopify API count currently returns all coupons regardless of filters so the total number of coupons to import may be wrong if you use filters. But the filters will work correctly while importing so you can still use filters to import coupons that you expect.', 's2w-import-shopify-to-woocommerce' ) ?>
                                                    </div>
                                                    <table class="form-table">
                                                        <tbody>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'coupons_per_request' ) ) ?>"><?php esc_html_e( 'Coupons per ajax request', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
                                                                <input type="number" min="1" max="250"
                                                                       name="<?php echo esc_attr( self::set( 'coupons_per_request', true ) ) ?>"
                                                                       id="<?php echo esc_attr( self::set( 'coupons_per_request' ) ) ?>"
                                                                       value="<?php echo esc_attr( self::$settings->get_params( 'coupons_per_request' ) ) ?>">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'coupon_starts_at_min' ) ) ?>"><?php esc_html_e( 'Import Coupons starting after date', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
                                                                <input type="date"
                                                                       name="<?php echo esc_attr( self::set( 'coupon_starts_at_min', true ) ) ?>"
                                                                       id="<?php echo esc_attr( self::set( 'coupon_starts_at_min' ) ) ?>"
                                                                       value="<?php echo esc_attr( self::$settings->get_params( 'coupon_starts_at_min' ) ) ?>">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'coupon_starts_at_max' ) ) ?>"><?php esc_html_e( 'Import Coupons starting before date', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
                                                                <input type="date"
                                                                       name="<?php echo esc_attr( self::set( 'coupon_starts_at_max', true ) ) ?>"
                                                                       id="<?php echo esc_attr( self::set( 'coupon_starts_at_max' ) ) ?>"
                                                                       value="<?php echo esc_attr( self::$settings->get_params( 'coupon_starts_at_max' ) ) ?>">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'coupon_ends_at_min' ) ) ?>"><?php esc_html_e( 'Import Coupons ending after date', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
                                                                <input type="date"
                                                                       name="<?php echo esc_attr( self::set( 'coupon_ends_at_min', true ) ) ?>"
                                                                       id="<?php echo esc_attr( self::set( 'coupon_ends_at_min' ) ) ?>"
                                                                       value="<?php echo esc_attr( self::$settings->get_params( 'coupon_ends_at_min' ) ) ?>">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'coupon_ends_at_max' ) ) ?>"><?php esc_html_e( 'Import Coupons ending before date', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
                                                                <input type="date"
                                                                       name="<?php echo esc_attr( self::set( 'coupon_ends_at_max', true ) ) ?>"
                                                                       id="<?php echo esc_attr( self::set( 'coupon_ends_at_max' ) ) ?>"
                                                                       value="<?php echo esc_attr( self::$settings->get_params( 'coupon_ends_at_max' ) ) ?>">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'coupon_zero_times_used' ) ) ?>"><?php esc_html_e( 'Not used yet', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
                                                                <div class="vi-ui toggle checkbox checked">
                                                                    <input type="checkbox"
                                                                           name="<?php echo esc_attr( self::set( 'coupon_zero_times_used', true ) ) ?>"
                                                                           id="<?php echo esc_attr( self::set( 'coupon_zero_times_used' ) ) ?>"
                                                                           value="1" <?php checked( self::$settings->get_params( 'coupon_zero_times_used' ), '1' ) ?>>
                                                                    <label for="<?php echo esc_attr( self::set( 'coupon_zero_times_used' ) ) ?>"></label>
                                                                </div>
                                                                <p><?php esc_html_e( 'Only import coupons whose times used is zero', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                                            </td>
                                                        </tr>
                                                        </tbody>
                                                    </table>
													<?php
													break;
												case 'customers':
													?>
                                                    <table class="form-table">
                                                        <tbody>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'customers_per_request' ) ) ?>"><?php esc_html_e( 'Customers per ajax request', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
                                                                <input type="number" min="1" max="250"
                                                                       name="<?php echo esc_attr( self::set( 'customers_per_request', true ) ) ?>"
                                                                       id="<?php echo esc_attr( self::set( 'customers_per_request' ) ) ?>"
                                                                       value="<?php echo esc_attr( self::$settings->get_params( 'customers_per_request' ) ) ?>">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'customers_last_date_import' ) ) ?>"><?php esc_html_e( 'Import customers within x day(s)', 's2w-import-shopify-to-woocommerce' )
																	?></label>
                                                            </th>
                                                            <td>
                                                                <input type="number" min="1" max="250"
                                                                       name="<?php echo esc_attr( self::set( 'customers_last_date_import', true ) ) ?>"
                                                                       id="<?php echo esc_attr( self::set( 'customers_last_date_import' ) ) ?>"
                                                                       value="<?php echo esc_attr( self::$settings->get_params( 'customers_last_date_import' ) ) ?>">
                                                                <p class="description"><?php esc_html_e( 'Import only the customer data within the last x day(s).', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                                                <p class="description"><?php esc_html_e( 'For example, if you set "Import customers within 30 days," it means you want to import only customers on Shopify within 30 days', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                                            </td>
                                                        </tr>
														<?php
														$customers_role = self::$settings->get_params( 'customers_role' );
														$wp_roles       = wp_roles()->roles;
														?>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'customers_role' ) ) ?>"><?php esc_html_e( 'Customers role', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
                                                                <select name="<?php echo esc_attr( self::set( 'customers_role', true ) ) ?>"
                                                                        class="vi-ui fluid dropdown"
                                                                        id="<?php echo esc_attr( self::set( 'customers_role' ) ) ?>">
																	<?php
																	if ( is_array( $wp_roles ) && count( $wp_roles ) ) {
																		unset( $wp_roles['administrator'] );
																		foreach ( $wp_roles as $role_key => $role_value ) {
																			?>
                                                                            <option value="<?php echo esc_attr( $role_key ) ?>" <?php selected( $customers_role, $role_key ) ?>><?php echo esc_html( $role_value['name'] ) ?></option>
																			<?php
																		}
																	}
																	?>
                                                                </select>
                                                                <p class="description"><?php esc_html_e( 'Set role for imported customers', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'customers_with_purchases_only' ) ) ?>"><?php esc_html_e( 'With Purchase(s) only', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
                                                                <div class="vi-ui toggle checkbox checked">
                                                                    <input type="checkbox"
                                                                           name="<?php echo esc_attr( self::set( 'customers_with_purchases_only', true ) ) ?>"
                                                                           id="<?php echo esc_attr( self::set( 'customers_with_purchases_only' ) ) ?>"
                                                                           value="1" <?php checked( self::$settings->get_params( 'customers_with_purchases_only' ), '1' ) ?>>
                                                                    <label for="<?php echo esc_attr( self::set( 'customers_with_purchases_only' ) ) ?>"></label>
                                                                </div>
                                                                <p class="description"><?php esc_html_e( 'Only import customers who have at least 1 purchase', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'update_existing_customers' ) ) ?>"><?php esc_html_e( 'Update existing customers', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
                                                                <div class="vi-ui toggle checkbox checked">
                                                                    <input type="checkbox"
                                                                           name="<?php echo esc_attr( self::set( 'update_existing_customers', true ) ) ?>"
                                                                           id="<?php echo esc_attr( self::set( 'update_existing_customers' ) ) ?>"
                                                                           value="1" <?php checked( self::$settings->get_params( 'update_existing_customers' ), '1' ) ?>>
                                                                    <label for="<?php echo esc_attr( self::set( 'update_existing_customers' ) ) ?>"><?php esc_html_e( 'A customer is checked for existence by their Shopify ID.', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                                </div>
                                                                <p><?php esc_html_e( 'If disabled, it will skip if a customer exists.', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                                            </td>
                                                        </tr>
                                                        </tbody>
                                                    </table>
													<?php
													break;
												case 'blogs':
													?>
                                                    <table class="form-table">
                                                        <tbody>
														<?php
														$blogs_update_if_exist = self::$settings->get_params( 'blogs_update_if_exist' );
														$blogs_update_options  = self::get_blogs_update_options();
														?>
                                                        <tr>
                                                            <th>
                                                                <label for="<?php echo esc_attr( self::set( 'blogs_update_if_exist' ) ) ?>"><?php esc_html_e( 'Update blogs if exist', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                                            </th>
                                                            <td>
                                                                <select name="<?php echo esc_attr( self::set( 'blogs_update_if_exist[]', true ) ) ?>"
                                                                        multiple
                                                                        class="vi-ui fluid dropdown"
                                                                        id="<?php echo esc_attr( self::set( 'blogs_update_if_exist' ) ) ?>">
																	<?php
																	if ( is_array( $blogs_update_options ) && count( $blogs_update_options ) ) {
																		foreach ( $blogs_update_options as $blogs_update_options_k => $blogs_update_options_v ) {
																			?>
                                                                            <option value="<?php echo esc_attr( $blogs_update_options_k ) ?>" <?php if ( in_array( $blogs_update_options_k, $blogs_update_if_exist ) ) {
																				echo esc_attr( 'selected' );
																			} ?>><?php echo esc_html( $blogs_update_options_v ) ?></option>
																			<?php
																		}
																	}
																	?>
                                                                </select>
                                                                <p class="description"><?php esc_html_e( 'If you want to update an existing blog while importing, select some options to update. Leave it blank to skip existing blogs.', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                                                <p class="description">
                                                                    <strong><?php esc_html_e( '*This option only works if your blogs are imported since version 1.0.9', 's2w-import-shopify-to-woocommerce' ) ?></strong>
                                                                </p>
                                                            </td>
                                                        </tr>
                                                        </tbody>
                                                    </table>
													<?php
													break;
											}
											?>
                                            <div class="<?php echo esc_attr( self::set( array(
												'import-options-footer',
												"import-{$option_name}-options-footer"
											) ) ) ?>">
                                            <span class="vi-ui labeled icon primary button tiny <?php echo esc_attr( self::set( "save-{$option_name}-options" ) ) ?>"><i
                                                        class="icon save"></i><?php esc_html_e( 'Save', 's2w-import-shopify-to-woocommerce' ) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
								<?php
							}
							?>
                            <p>
                                <span class="vi-ui labeled icon primary button <?php echo esc_attr( self::set( 'save' ) ) ?>"><i
                                            class="icon save"></i><?php esc_html_e( 'Save', 's2w-import-shopify-to-woocommerce' ) ?></span>
                                <button class="vi-ui labeled icon button" type="submit" name="s2w_check_key"
                                        value="check_key"><i
                                            class="icon save"></i><?php esc_html_e( 'Save & Check Key', 's2w-import-shopify-to-woocommerce' ) ?>
                                </button>
                            </p>
                        </form>
                    </div>
                </div>
                <p></p>

                <form class="vi-ui form <?php echo esc_attr( self::set( 'import-container' ) ) ?>"
                      style="<?php if ( ! $active )
					      echo esc_attr( 'display:none' ) ?>"
                      method="POST">
                    <div class="vi-ui segment">
                        <div class="vi-ui styled fluid accordion">
                            <div class='title'>
                                <i class="dropdown icon"></i>
								<?php esc_html_e( 'How to use this plugin', 's2w-import-shopify-to-woocommerce' ) ?>
                            </div>
                            <div class="content">
                                <iframe width="560" height="315"
                                        src="https://www.youtube-nocookie.com/embed/DF3XiCeSOhQ"
                                        title="YouTube video player" frameborder="0"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                        allowfullscreen></iframe>
                            </div>
                            <div class='title'>
                                <i class="dropdown icon"></i>
								<?php esc_html_e( 'Learn how to get Access token', 's2w-import-shopify-to-woocommerce' ) ?>
                            </div>
                            <div class="content">
                                <iframe width="560" height="315"
                                        src="https://www.youtube-nocookie.com/embed/1kyEp4d_cfw"
                                        title="YouTube video player" frameborder="0"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                        allowfullscreen></iframe>
                            </div>
                        </div>
						<?php
						$elements = self::get_supported_elements();
						?>
                        <table class="vi-ui celled table center aligned">
                            <thead>
                            <tr>
                                <th style="width: 200px;"><?php esc_html_e( 'Data', 's2w-import-shopify-to-woocommerce' ) ?></th>
                                <th style="width: 200px;"><?php esc_html_e( 'Enable', 's2w-import-shopify-to-woocommerce' ) ?></th>
                                <th><?php esc_html_e( 'Status', 's2w-import-shopify-to-woocommerce' ) ?></th>
                            </tr>
                            </thead>
                            <tbody>
							<?php
							if ( is_array( $elements ) && count( $elements ) ) {
								$shortcut_available = array_diff( array_keys( self::get_import_options() ), array( 'blogs' ) );
								foreach ( $elements as $key => $value ) {
									if ( $key === 'products' ) {
										$history           = get_option( 's2w_' . self::$settings->get_params( 'domain' ) . '_history', array(
											'total_products'         => 0,
											'total_pages'            => 0,
											'current_import_id'      => '',
											'current_import_product' => - 1,
											'current_import_page'    => 1,
											'products_per_file'      => 250,
											'last_product_error'     => '',
										) );
										$time              = isset( $history['time'] ) && $history['time'] ? $history['time'] : '';
										$imported_products = isset( $history['current_import_product'] ) ? ( intval( $history['current_import_product'] ) + 1 ) : '0';
										$total_products    = isset( $history['total_products'] ) ? intval( $history['total_products'] ) : '0';
										$check             = 0;
										if ( ! $total_products || $imported_products < $total_products ) {
											$check = 1;
										}
									} else {
										$history = get_option( 's2w_' . self::$settings->get_params( 'domain' ) . '_history_' . $key );
										$check   = 1;
										$time    = isset( $history['time'] ) && $history['time'] ? $history['time'] : '';
										if ( $time ) {
											$check = 0;
										}
									}
									?>
                                    <tr>
                                        <td>
											<?php
											echo esc_html( $value );
											if ( in_array( $key, $shortcut_available ) ) {
												?>
                                                <a href="<?php echo esc_attr( "#s2w-import-{$key}-options-anchor" ) ?>"
                                                   data-option_name="<?php echo esc_attr( $key ) ?>"
                                                   class="<?php echo esc_attr( self::set( 'import-options-shortcut' ) ) ?>"><?php esc_html_e( 'View settings', 's2w-import-shopify-to-woocommerce' ) ?></a>
												<?php
											}
											?>
                                        </td>
                                        <td class="<?php echo esc_attr( self::set( 'import-' . str_replace( '_', '-', $key ) . '-enable' ) ) ?>">
                                            <div class="vi-ui toggle checkbox checked">
                                                <input type="checkbox"
                                                       id="<?php echo esc_attr( self::set( 'import-' . str_replace( '_', '-', $key ) . '-enable' ) ) ?>"
                                                       class="<?php echo esc_attr( self::set( 'import-element-enable' ) ) ?>"
                                                       data-element_name="<?php echo esc_attr( $key ) ?>"
                                                       name="<?php echo esc_attr( $key ) ?>"
													<?php checked( $check, 1 ) ?>
                                                       value="1">
                                            </div>
                                            <i class="<?php echo esc_attr( self::set( 'import-' . str_replace( '_', '-', $key ) . '-check-icon' ) ) ?> vi-ui check icon <?php echo esc_attr( $check ? 'grey' : 'green' ) ?>"
                                               title="<?php if ( ! $check && $time ) {
												   printf( esc_attr__( 'Imported: %s', 's2w-import-shopify-to-woocommerce' ), date_i18n( 'F d, Y', $time ) );
											   } ?>"></i>
                                        </td>
                                        <td class="<?php echo esc_attr( self::set( 'import-' . str_replace( '_', '-', $key ) . '-status' ) ) ?>">
                                            <div class="vi-ui indicating progress standard <?php echo esc_attr( self::set( 'import-progress' ) ) ?>"
                                                 style="visibility: hidden"
                                                 id="<?php echo esc_attr( 's2w-' . str_replace( '_', '-', $key ) . '-progress' ) ?>">
                                                <div class="label"></div>
                                                <div class="bar">
                                                    <div class="progress"></div>
                                                </div>
                                            </div>
                                            <div class="<?php echo esc_attr( self::set( array(
												'import-error-actions',
												'hidden'
											) ) ) ?>"
                                                 data-option_name="<?php echo esc_attr( $key ) ?>"><?php esc_html_e( 'Automatically retry in: ', 's2w-import-shopify-to-woocommerce' ) ?>
                                                <span class="<?php echo esc_attr( self::set( 'import-error-retry-count' ) ) ?>"></span>
                                                <span
                                                        class="vi-ui mini button <?php echo esc_attr( self::set( 'import-error-actions-cancel' ) ) ?>"><?php esc_html_e( 'Cancel', 's2w-import-shopify-to-woocommerce' ) ?></span>
                                            </div>
                                        </td>
                                    </tr>
									<?php
								}
							}
							?>
                            <tr>
                                <td>
                                    <strong><?php esc_html_e( 'Enable all', 's2w-import-shopify-to-woocommerce' ) ?></strong>
                                </td>
                                <td>
                                    <div class="vi-ui toggle checkbox checked">
                                        <input type="checkbox"
                                               class="<?php echo esc_attr( self::set( 'import-element-enable-bulk' ) ) ?>">
                                    </div>
                                    <i class="vi-ui check icon" style="visibility: hidden"></i>
                                </td>
                                <td></td>
                            </tr>
                            </tbody>
                        </table>
                        <p>
                            <span class="vi-ui labeled icon positive button tiny <?php echo esc_attr( self::set( 'sync' ) ) ?>"><i
                                        class="icon cloud download"></i><?php esc_html_e( 'Import', 's2w-import-shopify-to-woocommerce' ) ?></span>
                            <input type="submit" name="s2w_delete_history"
                                   title="<?php esc_attr_e( 'Delete importing state and cached data of selected element(s) to start a new import', 's2w-import-shopify-to-woocommerce' ) ?>"
                                   value="<?php esc_attr_e( 'Delete import history', 's2w-import-shopify-to-woocommerce' ) ?>"
                                   class="vi-ui negative button tiny <?php echo esc_attr( self::set( 'delete-history' ) ) ?>">
                        </p>
                        <h4><?php esc_html_e( 'Logs: ', 's2w-import-shopify-to-woocommerce' ) ?></h4>
                        <div class="vi-ui segment <?php echo esc_attr( self::set( 'logs' ) ) ?>">
                        </div>
                    </div>
                </form>
            </div>
			<?php
			do_action( 'villatheme_support_s2w-import-shopify-to-woocommerce' );
		}

		/**
		 * Import/update a customer
		 *
		 * @param $customer
		 * @param string $existing_id
		 *
		 * @return int|mixed|string|WP_Error
		 * @throws Exception
		 */
		public static function import_customer( $customer, $existing_id = '' ) {
			$email           = sanitize_email( $customer['email'] );
			$default_address = isset( $customer['default_address'] ) ? $customer['default_address'] : array();
			if ( empty( $default_address ) && count( $customer['addresses'] ) ) {
				$default_address = $customer['addresses'][0];
			}
			$customer_data = array();
			$new_user_args = array( 'role' => self::$settings->get_params( 'customers_role' ) );
			if ( ! empty( $customer['phone'] ) ) {
				$customer_data['phone'] = $customer['phone'];
			}
			if ( ! empty( $customer['first_name'] ) ) {
				$customer_data['first_name'] = $customer['first_name'];
				$new_user_args['first_name'] = $customer['first_name'];
			}
			if ( ! empty( $customer['last_name'] ) ) {
				$customer_data['last_name'] = $customer['last_name'];
				$new_user_args['last_name'] = $customer['last_name'];
			}
			if ( ! empty( $customer['created_at'] ) ) {
				$new_user_args['user_registered'] = date( 'Y-m-d H:i:s', strtotime( $customer['created_at'] ) );
			}
			$customer_data = wp_parse_args( $customer_data, $default_address );
			if ( $existing_id ) {
				$user_id = $existing_id;
			} else {
				$user_id = self::wc_create_new_customer( $email, $customer['id'], '', '', $new_user_args );
			}
			if ( ! is_wp_error( $user_id ) ) {
				$wc_customer = new WC_Customer( $user_id );
				$wc_customer->set_email( $email );
				$wc_customer->set_billing_email( $email );
				if ( ! empty( $customer_data['first_name'] ) ) {
					$wc_customer->set_first_name( $customer_data['first_name'] );
					$wc_customer->set_shipping_first_name( $customer_data['first_name'] );
				}
				if ( ! empty( $customer_data['last_name'] ) ) {
					$wc_customer->set_last_name( $customer_data['last_name'] );
					$wc_customer->set_shipping_last_name( $customer_data['last_name'] );
				}
				if ( ! empty( $customer_data['phone'] ) ) {
					$wc_customer->set_billing_phone( isset( $customer_data['phone'] ) ? $customer_data['phone'] : '' );
					$wc_customer->set_shipping_phone( isset( $customer_data['phone'] ) ? $customer_data['phone'] : '' );
				}
				if ( ! empty( $new_user_args['user_registered'] ) ) {
					$wc_customer->set_date_created( $new_user_args['user_registered'] );
				}
				$wc_customer->set_billing_company( isset( $customer_data['company'] ) ? $customer_data['company'] : '' );
				$wc_customer->set_shipping_company( isset( $customer_data['company'] ) ? $customer_data['company'] : '' );
				$wc_customer->set_billing_address_1( isset( $customer_data['address1'] ) ? $customer_data['address1'] : '' );
				$wc_customer->set_shipping_address_1( isset( $customer_data['address1'] ) ? $customer_data['address1'] : '' );
				$wc_customer->set_billing_address_2( isset( $customer_data['address2'] ) ? $customer_data['address2'] : '' );
				$wc_customer->set_shipping_address_2( isset( $customer_data['address2'] ) ? $customer_data['address2'] : '' );
				$wc_customer->set_billing_city( isset( $customer_data['city'] ) ? $customer_data['city'] : '' );
				$wc_customer->set_shipping_city( isset( $customer_data['city'] ) ? $customer_data['city'] : '' );
				$wc_customer->set_billing_state( isset( $customer_data['province_code'] ) ? $customer_data['province_code'] : '' );
				$wc_customer->set_shipping_state( isset( $customer_data['province_code'] ) ? $customer_data['province_code'] : '' );
				$wc_customer->set_billing_country( isset( $customer_data['country_code'] ) ? $customer_data['country_code'] : '' );
				$wc_customer->set_shipping_country( isset( $customer_data['country_code'] ) ? $customer_data['country_code'] : '' );
				$wc_customer->set_billing_postcode( isset( $customer_data['zip'] ) ? $customer_data['zip'] : '' );
				$wc_customer->set_shipping_postcode( isset( $customer_data['zip'] ) ? $customer_data['zip'] : '' );
				if ( $user_id ) {
					$wc_customer->save();
				} else {
					try {
						$user_id = $wc_customer->save();
						if ( $user_id ) {
							update_user_meta( $user_id, '_s2w_shopify_customer_id', $customer['id'] );
							$domain = self::$settings->get_params( 'domain' );
							update_user_meta( $user_id, '_s2w_shopify_domain', $domain );/*Compatible with w2s*/
						}
					} catch ( Exception $e ) {
						return new WP_Error( 's2w_error_saving_customer', '[' . $e->getCode() . ']' . $e->getMessage() );
					}
				}
			}

			return $user_id;
		}

		/**
		 * Create new customer
		 *
		 * @param $email
		 * @param string $username
		 * @param string $password
		 * @param array $args
		 * @param $shopify_id
		 *
		 * @return int|mixed|WP_Error
		 */
		public static function wc_create_new_customer( $email, $shopify_id, $username = '', $password = '', $args = array() ) {
			if ( ! empty( $email ) && ! is_email( $email ) ) {
				return new WP_Error( 's2w-registration-error-invalid-email', esc_html__( 'Invalid email address.', 'woocommerce' ) );
			}

			if ( $user_id = email_exists( $email ) ) {
				/*Update shopify ID of previously imported customers*/
				update_user_meta( $user_id, '_s2w_shopify_customer_id', $shopify_id );

				return new WP_Error( 's2w-registration-error-email-exists', apply_filters( 'woocommerce_registration_error_email_exists', esc_html__( 'An account is already registered.', 'woocommerce' ), $email ) );
			}

			if ( empty( $username ) ) {
				$username = wc_create_new_customer_username( $email, $args );
			}

			$username = sanitize_user( $username );

			if ( empty( $username ) || ! validate_username( $username ) ) {
				return new WP_Error( 's2w-registration-error-invalid-username', esc_html__( 'Invalid account username.', 'woocommerce' ) );
			}

			if ( username_exists( $username ) ) {
				return new WP_Error( 's2w-registration-error-username-exists', esc_html__( 'Account username exists.', 'woocommerce' ) );
			}

			/*Handle password creation.*/
			$password_generated = false;
			if ( empty( $password ) ) {
				$password           = wp_generate_password();
				$password_generated = true;
			}

			if ( empty( $password ) ) {
				return new WP_Error( 's2w-registration-error-missing-password', esc_html__( 'Password required.', 'woocommerce' ) );
			}

			/* Use WP_Error to handle registration errors.*/
			$errors = new WP_Error();

			do_action( 's2w_woocommerce_register_post', $username, $email, $errors );

			$errors = apply_filters( 's2w_woocommerce_registration_errors', $errors, $username, $email );

			if ( $errors->get_error_code() ) {
				return $errors;
			}

			$new_customer_data = apply_filters(
				's2w_woocommerce_new_customer_data',
				array_merge(
					$args,
					array(
						'user_login' => $username,
						'user_pass'  => $password,
						'user_email' => $email,
					)
				)
			);

			$customer_id = wp_insert_user( $new_customer_data );

			if ( is_wp_error( $customer_id ) ) {
				return new WP_Error( 's2w-registration-error', __( 'Error', 'woocommerce' ) );
			} elseif ( $customer_id ) {
				if ( self::woocommerce_analytics_enabled() ) {
					CustomersDataStore::update_registered_customer( $customer_id );
				}
				update_user_meta( $customer_id, '_s2w_shopify_customer_id', $shopify_id );
			}

			do_action( 's2w_woocommerce_created_customer', $customer_id, $new_customer_data, $password_generated );

			return $customer_id;
		}

		/**
		 * Import an order
		 *
		 * @param $order_data
		 *
		 * @return int
		 * @throws WC_Data_Exception
		 */
		public static function import_order( $order_data ) {
			$gmt_offset        = get_option( 'gmt_offset' );
			$current_import_id = $order_data['id'];
			$line_items        = $order_data['line_items'];
			$fulfillments      = isset( $order_data['fulfillments'] ) ? $order_data['fulfillments'] : '';
			$billing_address   = isset( $order_data['billing_address'] ) ? $order_data['billing_address'] : '';
			$shipping_address  = isset( $order_data['shipping_address'] ) ? $order_data['shipping_address'] : '';
			$data              = array(
				'payment_method'      => isset( $order_data['payment_gateway_names'][0] ) ? $order_data['payment_gateway_names'][0] : '',
				/*Billing*/
				'billing_first_name'  => isset( $billing_address['first_name'] ) ? $billing_address['first_name'] : '',
				'billing_last_name'   => isset( $billing_address['last_name'] ) ? $billing_address['last_name'] : '',
				'billing_company'     => isset( $billing_address['company'] ) ? $billing_address['company'] : '',
				'billing_country'     => isset( $billing_address['country'] ) ? $billing_address['country'] : '',
				'billing_address_1'   => isset( $billing_address['address1'] ) ? $billing_address['address1'] : '',
				'billing_address_2'   => isset( $billing_address['address2'] ) ? $billing_address['address2'] : '',
				'billing_postcode'    => isset( $billing_address['zip'] ) ? $billing_address['zip'] : '',
				'billing_city'        => isset( $billing_address['city'] ) ? $billing_address['city'] : '',
				'billing_state'       => isset( $billing_address['province'] ) ? $billing_address['province'] : '',
				'billing_phone'       => isset( $billing_address['phone'] ) ? $billing_address['phone'] : '',
				'billing_email'       => self::get_billing_email( $order_data ),
				/*Shipping*/
				'shipping_first_name' => isset( $shipping_address['first_name'] ) ? $shipping_address['first_name'] : '',
				'shipping_last_name'  => isset( $shipping_address['last_name'] ) ? $shipping_address['last_name'] : '',
				'shipping_company'    => isset( $shipping_address['company'] ) ? $shipping_address['company'] : '',
				'shipping_country'    => isset( $shipping_address['country'] ) ? $shipping_address['country'] : '',
				'shipping_address_1'  => isset( $shipping_address['address1'] ) ? $shipping_address['address1'] : '',
				'shipping_address_2'  => isset( $shipping_address['address2'] ) ? $shipping_address['address2'] : '',
				'shipping_postcode'   => isset( $shipping_address['zip'] ) ? $shipping_address['zip'] : '',
				'shipping_city'       => isset( $shipping_address['city'] ) ? $shipping_address['city'] : '',
				'shipping_state'      => isset( $shipping_address['province'] ) ? $shipping_address['province'] : '',
			);
			$order_total       = $order_data['total_price'];
			$order_total_tax   = $order_data['total_tax'];
			$total_discounts   = $order_data['total_discounts'];
			$total_shipping    = isset( $order_data['total_shipping_price_set']['shop_money']['amount'] ) ? floatval( $order_data['total_shipping_price_set']['shop_money']['amount'] ) : 0;
			$shipping_lines    = $order_data['shipping_lines'];
			$discount_codes    = $order_data['discount_codes'];
			$financial_status  = $order_data['financial_status'];
			$customer_note     = $order_data['note'];

			/*Disable send email for customer*/
			add_filter( 'woocommerce_email_recipient_new_order', [ __CLASS__, 's2w_disable_customer_order_email_when_importing' ], 10, 2 );
			add_filter( 'woocommerce_email_recipient_customer_partially_refunded_order', [ __CLASS__, 's2w_disable_customer_order_email_when_importing' ], 10, 2 );
			add_filter( 'woocommerce_email_recipient_customer_invoice_pending', [ __CLASS__, 's2w_disable_customer_order_email_when_importing' ], 10, 2 );
			add_filter( 'woocommerce_email_recipient_customer_processing_order', [ __CLASS__, 's2w_disable_customer_order_email_when_importing' ], 10, 2 );
			add_filter( 'woocommerce_email_recipient_customer_on_hold_order', [ __CLASS__, 's2w_disable_customer_order_email_when_importing' ], 10, 2 );
			add_filter( 'woocommerce_email_recipient_customer_completed_order', [ __CLASS__, 's2w_disable_customer_order_email_when_importing' ], 10, 2 );
			$order           = new WC_Order();
			$fields_prefix   = array(
				'shipping' => true,
				'billing'  => true,
			);
			$shipping_fields = array(
				'shipping_method' => true,
				'shipping_total'  => true,
				'shipping_tax'    => true,
			);
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

			$order->set_created_via( 's2w_import' );
			$order->set_customer_id( self::get_customer_id( $order_data ) );
			$order->set_currency( ! empty( $order_data['currency'] ) ? $order_data['currency'] : get_woocommerce_currency() );
			$order->set_prices_include_tax( $order_data['taxes_included'] );
			$order->set_customer_ip_address( $order_data['browser_ip'] );
			$order->set_customer_user_agent( isset( $order_data['client_details']['user_agent'] ) ? $order_data['client_details']['user_agent'] : '' );
			$order->set_payment_method_title( $data['payment_method'] );
			$order->set_shipping_total( $total_shipping );
			$order->set_discount_total( $total_discounts );
			//      set discount tax
			$order->set_cart_tax( $order_total_tax );
			if ( isset( $shipping_lines['tax_lines']['price'] ) && $shipping_lines['tax_lines']['price'] ) {
				$order->set_shipping_tax( $shipping_lines['tax_lines']['price'] );
			}
			$order->set_total( $order_total );
			//		create order line items
			$map_products_ids = array();
			$line_items_ids   = array();
			foreach ( $line_items as $line_item ) {
				$item                 = new WC_Order_Item_Product();
				$shopify_product_id   = isset( $line_item['product_id'] ) ? $line_item['product_id'] : '';
				$shopify_variation_id = isset( $line_item['variant_id'] ) ? $line_item['variant_id'] : '';
				$sku                  = $line_item['sku'];
				$product_id           = '';

				if ( $shopify_variation_id ) {
					$found_variation_id = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::product_get_woo_id_by_shopify_id( $shopify_variation_id, true );
					if ( $found_variation_id ) {
						$product_id                              = $found_variation_id;
						$map_products_ids[ $found_variation_id ] = $shopify_variation_id;
					}
				}
				if ( ! $product_id && $shopify_product_id ) {
					$found_product_id = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::product_get_woo_id_by_shopify_id( $shopify_product_id );
					if ( $found_product_id ) {
						$product_id                            = $found_product_id;
						$map_products_ids[ $found_product_id ] = $shopify_product_id;
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
					$product_obj = wc_get_product( $product_id );
					if ( $product_obj ) {
						$item->set_props(
							array(
								'name'         => $product_obj->get_name(),
								'tax_class'    => $product_obj->get_tax_class(),
								'product_id'   => $product_obj->is_type( 'variation' ) ? $product_obj->get_parent_id() : $product_obj->get_id(),
								'variation_id' => $product_obj->is_type( 'variation' ) ? $product_obj->get_id() : 0,
								'variation'    => $product_obj->is_type( 'variation' ) ? $product_obj->get_attributes() : array()
							)
						);
					}
				}
				$item_id = $item->save();
				// Add item to order and save.
				$order->add_item( $item );
				$line_items_ids[ $item_id ] = $line_item['id'];
			}
			//create order shipping line
			$item = new WC_Order_Item_Shipping();
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
			//create order tax lines
			$tax_lines = $order_data['tax_lines'];
			if ( is_array( $tax_lines ) && count( $tax_lines ) ) {
				foreach ( $tax_lines as $tax_line ) {
					$item = new WC_Order_Item_Tax();
					$item->set_props(
						array(
							'tax_total' => $tax_line['price'],
							'label'     => $tax_line['title'],
						)
					);
					$order->add_item( $item );
				}
			}

			//create order coupon lines
			if ( is_array( $discount_codes ) && count( $discount_codes ) ) {
				foreach ( $discount_codes as $discount_code ) {
					$item = new WC_Order_Item_Coupon();
					$item->set_props(
						array(
							'code'     => $discount_code['code'],
							'discount' => $discount_code['amount'],
						)
					);
					$order->add_item( $item );
				}
			}
			$order_id = $order->save();
			$refunds  = $order_data['refunds'];
			self::process_refunds( array(), $refunds, $order_id, $line_items_ids, $shipping_lines_id );
			$order->add_order_note( esc_html__( 'This order is imported from Shopify store by S2W - Import Shopify to WooCommerce plugin.', 's2w-import-shopify-to-woocommerce' ) );
			if ( $customer_note ) {
				$order->add_order_note( $customer_note, false, true );
			}
			$order->save();
			$order_status_mapping = self::$settings->get_params( 'order_status_mapping' );
			if ( ! is_array( $order_status_mapping ) || ! count( $order_status_mapping ) ) {
				$order_status_mapping = self::$settings->get_default( 'order_status_mapping' );
			}
			$processed_at     = apply_filters( 's2w_import_order_created_date', $order_data['processed_at'], $order_data );
			$processed_at_gmt = strtotime( $processed_at );
			$date_gmt         = date( 'Y-m-d H:i:s', $processed_at_gmt );
			$date             = date( 'Y-m-d H:i:s', ( $processed_at_gmt + $gmt_offset * 3600 ) );
			$order_status     = apply_filters( 's2w_import_order_status', isset( $order_status_mapping[ $financial_status ] ) ? $order_status_mapping[ $financial_status ] : 'processing', $order_data, $order );
			/*wp_update_post( array(
				'ID'                => $order_id,
				'post_status'       => "wc-{$order_status}",
				'post_date'         => $date,
				'post_date_gmt'     => $date_gmt,
				'post_modified'     => $date,
				'post_modified_gmt' => $date_gmt,
			) );*/
			$order->set_status( "wc-{$order_status}" );
			$order->set_date_created( $date );
			$order->set_date_modified( $date );


			$order->update_meta_data( '_s2w_shopify_order_id', $current_import_id );
			$domain = self::$settings->get_params( 'domain' );
			$order->update_meta_data( '_s2w_shopify_domain', $domain );/*Compatible with w2s*/
			$order->update_meta_data( '_s2w_shopify_order_number', apply_filters( 's2w_shopify_order_number', $order_data['order_number'], $order_data, $order_id ) );
			$order->update_meta_data( '_s2w_shopify_order_fulfillments', $fulfillments );
			$order->save();

			/*Remove hook disable send email for customer when importing*/
			remove_filter( 'woocommerce_email_recipient_new_order', [ __CLASS__, 's2w_disable_customer_order_email_when_importing' ], 10, 2 );
			remove_filter( 'woocommerce_email_recipient_customer_partially_refunded_order', [ __CLASS__, 's2w_disable_customer_order_email_when_importing' ], 10, 2 );
			remove_filter( 'woocommerce_email_recipient_customer_invoice_pending', [ __CLASS__, 's2w_disable_customer_order_email_when_importing' ], 10, 2 );
			remove_filter( 'woocommerce_email_recipient_customer_on_hold_order', [ __CLASS__, 's2w_disable_customer_order_email_when_importing' ], 10, 2 );
			remove_filter( 'woocommerce_email_recipient_customer_processing_order', [ __CLASS__, 's2w_disable_customer_order_email_when_importing' ], 10, 2 );
			remove_filter( 'woocommerce_email_recipient_customer_completed_order', [ __CLASS__, 's2w_disable_customer_order_email_when_importing' ], 10, 2 );

			return $order_id;
		}

		/**
		 * Import refunds
		 *
		 * @param $refund_items - Woo refund items
		 * @param $refunds - Shopify order refunds
		 * @param $order_id - Woo order id
		 * @param $line_items_ids
		 * @param $shipping_lines_id
		 *
		 * @throws Exception
		 */
		public static function process_refunds( $refund_items, $refunds, $order_id, $line_items_ids, $shipping_lines_id ) {
			$refund_items_count      = count( $refund_items );
			$refund_line_items_count = count( $refunds );
			$create_refunds          = array();
			$gmt_offset              = get_option( 'gmt_offset' );
			if ( $refund_items_count !== $refund_line_items_count || apply_filters( 's2w_force_create_refunds', false ) ) {
				/*Must delete refunds created by the previous import if any*/
				foreach ( $refund_items as $refund_item ) {
					$refund_item->delete( true );
				}
				/*Import refunds*/
				foreach ( $refunds as $refunds_k => $refunds_v ) {
					$create_refund = array(
						'amount'         => 0,
						'reason'         => $refunds_v['note'],
						'order_id'       => $order_id,
						'line_items'     => array(),
						'refund_payment' => false,
						'restock_items'  => false,
					);
					$created_at    = $refunds_v['created_at'];
					if ( ! empty( $refunds_v['processed_at'] ) ) {
						$created_at = $refunds_v['processed_at'];
					}
					if ( $created_at ) {
						$created_at_gmt                = strtotime( $created_at );
						$date                          = date( 'Y-m-d H:i:s', ( $created_at_gmt + $gmt_offset * 3600 ) );
						$create_refund['date_created'] = $date;
					}
					if ( $refunds_v['note'] ) {
						$create_refund['reason'] = $refunds_v['note'];
					}
					if ( isset( $refunds_v['refund_line_items'] ) && count( $refunds_v['refund_line_items'] ) ) {
						/*Import refund line items*/
						$line_item_refund = $create_refund;
						foreach ( $refunds_v['refund_line_items'] as $refund_line_items_k => $refund_line_items_v ) {
							if ( ! empty( $refund_line_items_v['line_item_id'] ) ) {
								$line_item_id = array_search( $refund_line_items_v['line_item_id'], $line_items_ids );
								if ( $line_item_id !== false ) {
									$line_item_refund['line_items'][ $line_item_id ] = array(
										'qty'          => $refund_line_items_v['quantity'],
										'refund_total' => $refund_line_items_v['subtotal'],
										'refund_tax'   => array( 0 ),
									);
									$line_item_refund['amount']                      += $refund_line_items_v['subtotal'];
								}
							}
						}
						if ( count( $line_item_refund['line_items'] ) ) {
							$create_refunds[] = $line_item_refund;
						}
					}

					if ( isset( $refunds_v['order_adjustments'] ) && is_array( $refunds_v['order_adjustments'] ) && count( $refunds_v['order_adjustments'] ) ) {
						/*Import refund shipping*/
						if ( $shipping_lines_id ) {
							$shipping_refund       = $create_refund;
							$shipping_refund_total = 0;
							foreach ( $refunds_v['order_adjustments'] as $refund_line_items_k => $refund_line_items_v ) {
								if ( $refund_line_items_v['kind'] === 'shipping_refund' ) {
									$shipping_refund_total += isset( $refund_line_items_v['amount_set']['shop_money']['amount'] ) ? abs( $refund_line_items_v['amount_set']['shop_money']['amount'] ) : abs( $refund_line_items_v['amount'] );
								}
							}
							if ( $shipping_refund_total > 0 ) {
								$shipping_refund['line_items'][ $shipping_lines_id ] = array(
									'qty'          => 0,
									'refund_total' => $shipping_refund_total,
									'refund_tax'   => array( 0 ),
								);
								$shipping_refund['amount']                           += $shipping_refund_total;
								$create_refunds[]                                    = $shipping_refund;
							}
						}
						/*Import other refunds*/
						$order_adjustment_refund = $create_refund;
						foreach ( $refunds_v['order_adjustments'] as $refund_line_items_k => $refund_line_items_v ) {
							if ( $refund_line_items_v['kind'] !== 'shipping_refund' ) {
								$order_adjustment_refund['amount'] = abs( $refund_line_items_v['amount'] );
								$create_refunds[]                  = $order_adjustment_refund;
							}
						}
					}
				}
			}
			if ( count( $create_refunds ) ) {
				foreach ( $create_refunds as $create_refund ) {
					wc_create_refund( $create_refund );
				}
			}

			return;

			$refund_items_count      = count( $refund_items );
			$refund_line_items_count = count( $refunds );
			$create_refund           = array(
				'amount'         => 0,
				'reason'         => '',
				'order_id'       => $order_id,
				'line_items'     => array(),
				'refund_payment' => false,
				'restock_items'  => false,
			);
			if ( $refund_items_count !== $refund_line_items_count || apply_filters( 's2w_force_create_refunds', false ) ) {
				foreach ( $refund_items as $refund_item ) {
					$refund_item->delete( true );
				}
				foreach ( $refunds as $refunds_k => $refunds_v ) {
					$created_at = $refunds_v['created_at'];
					if ( ! empty( $refunds_v['processed_at'] ) ) {
						$created_at = $refunds_v['processed_at'];
					}
					if ( $created_at ) {
						$created_at_gmt                = strtotime( $created_at );
						$date                          = date( 'Y-m-d H:i:s', ( $created_at_gmt + $gmt_offset * 3600 ) );
						$create_refund['date_created'] = $date;
					}
					if ( $refunds_v['note'] ) {
						$create_refund['reason'] = $refunds_v['note'];
					}
					if ( isset( $refunds_v['refund_line_items'] ) && count( $refunds_v['refund_line_items'] ) && count( $line_items_ids ) ) {
						foreach ( $refunds_v['refund_line_items'] as $refund_line_items_k => $refund_line_items_v ) {
							if ( ! empty( $refund_line_items_v['line_item'] ) ) {
								$line_item_id = array_search( $refund_line_items_v['line_item_id'], $line_items_ids );
								if ( $line_item_id !== false ) {
									$create_refund['line_items'][ $line_item_id ] = array(
										'qty'          => $refund_line_items_v['quantity'],
										'refund_total' => $refund_line_items_v['subtotal'],
										'refund_tax'   => array( 0 ),
									);
									$create_refund['amount']                      += $refund_line_items_v['subtotal'];
								}
//								foreach ( $line_items_ids as $line_items_ids_k => $line_items_ids_v ) {
//									if ( ( $refund_line_items_v['line_item']['variant_id'] && $refund_line_items_v['line_item']['variant_id'] == $line_items_ids_v['variant_id'] ) || ( $refund_line_items_v['line_item']['product_id'] == $line_items_ids_v['product_id'] ) ) {
//										if ( $refund_line_items_v['line_item']['variant_id'] == $line_items_ids_v['variant_id'] ) {
//											$create_refund['line_items'][ $line_items_ids_k ] = array(
//												'qty'          => $refund_line_items_v['quantity'],
//												'refund_total' => $refund_line_items_v['subtotal'],
//												'refund_tax'   => array( 0 ),
//											);
//											$create_refund['amount']                          += $refund_line_items_v['subtotal'];
//											unset( $line_items_ids[ $line_items_ids_k ] );
//										}
//									}
//								}
							}
						}
					}

					if ( isset( $refunds_v['order_adjustments'] ) && is_array( $refunds_v['order_adjustments'] ) && count( $refunds_v['order_adjustments'] ) && $shipping_lines_id ) {
						$shipping_refund_total = 0;
						$shipping_refund_tax   = 0;
						foreach ( $refunds_v['order_adjustments'] as $refund_line_items_k => $refund_line_items_v ) {
							if ( $refund_line_items_v['kind'] === 'shipping_refund' ) {
								$shipping_refund_total += isset( $refund_line_items_v['amount_set']['shop_money']['amount'] ) ? abs( $refund_line_items_v['amount_set']['shop_money']['amount'] ) : abs( $refund_line_items_v['amount'] );
//								$shipping_refund_tax   += isset( $refund_line_items_v['tax_amount_set']['shop_money']['amount'] ) ? array( abs( $refund_line_items_v['tax_amount_set']['shop_money']['amount'] ) ) : array( 0 );


//								$create_refund    = array(
//									'amount'         => abs( $refund_line_items_v['amount'] ),
//									'reason'         => $refund_line_items_v['reason'],
//									'order_id'       => $order_id,
//									'line_items'     => array(
//										$shipping_lines_id => array(
//											'qty'          => 0,
//											'refund_total' => isset( $refund_line_items_v['amount_set']['shop_money']['amount'] ) ? abs( $refund_line_items_v['amount_set']['shop_money']['amount'] ) : abs( $refund_line_items_v['amount'] ),
//											'refund_tax'   => isset( $refund_line_items_v['tax_amount_set']['shop_money']['amount'] ) ? array( abs( $refund_line_items_v['tax_amount_set']['shop_money']['amount'] ) ) : array( 0 ),
//										)
//									),
//									'refund_payment' => false,
//									'restock_items'  => false,
//								);
//								$create_refunds[] = $create_refund;
							}
						}
						if ( $shipping_refund_total > 0 ) {
							$create_refund['line_items'][ $shipping_lines_id ] = array(
								'qty'          => 0,
								'refund_total' => $shipping_refund_total,
								'refund_tax'   => array( 0 ),
							);
							$create_refund['amount']                           += $shipping_refund_total;
						}
					}
				}
			}
			if ( count( $create_refund['line_items'] ) ) {
				wc_create_refund( $create_refund );
			}
		}

		/**
		 * @param $order_data
		 *
		 * @return string
		 */
		public static function get_billing_email( $order_data ) {
			$email = isset( $order_data['email'] ) ? sanitize_email( $order_data['email'] ) : '';
			if ( ! $email && isset( $order_data['customer']['email'] ) ) {
				$email = sanitize_email( $order_data['customer']['email'] );
			}

			return $email;
		}

		/**
		 * Get customer ID based on email or Shopify ID
		 *
		 * @param $order_data
		 *
		 * @return array|bool|false|int|object|string|null
		 */
		public static function get_customer_id( $order_data ) {
			$email       = self::get_billing_email( $order_data );
			$customer_id = email_exists( $email );
			if ( ! $customer_id && isset( $order_data['customer']['id'] ) ) {
				$customer_id = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::customer_get_id_by_shopify_id( $order_data['customer']['id'] );
			}

			return $customer_id;
		}

		/**
		 * Create variation attributes
		 *
		 * @param $global_attributes
		 * @param $options
		 * @param $variant
		 *
		 * @return array
		 */
		public static function create_variation_attributes( $global_attributes, $options, $variant ) {
			$attributes = array();
			if ( $global_attributes ) {
				foreach ( $options as $option_k => $option_v ) {
					$j = $option_k + 1;
					if ( isset( $variant[ 'option' . $j ] ) && $variant[ 'option' . $j ] ) {
						$attribute_id  = wc_attribute_taxonomy_id_by_name( $option_v['name'] );
						$attribute_obj = wc_get_attribute( $attribute_id );
						if ( $attribute_obj ) {
							$attribute_value = get_term_by( 'name', $variant[ 'option' . $j ], $attribute_obj->slug );
							if ( $attribute_value ) {
								$attributes[ VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::sanitize_taxonomy_name( $attribute_obj->slug ) ] = $attribute_value->slug;
							}
						}
					}
				}
			} else {
				foreach ( $options as $option_k => $option_v ) {
					$j = $option_k + 1;
					if ( isset( $variant[ 'option' . $j ] ) && $variant[ 'option' . $j ] ) {
						$attributes[ VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::sanitize_taxonomy_name( $option_v['name'] ) ] = $variant[ 'option' . $j ];
					}
				}
			}

			return $attributes;
		}

		/**
		 * Create product attributes
		 *
		 * @param $global_attributes
		 * @param $options
		 * @param $attr_data
		 */
		public static function create_product_attributes( $global_attributes, $options, &$attr_data ) {
			global $wp_taxonomies;
			if ( $global_attributes ) {
				$position = 1;
				foreach ( $options as $option_k => $option_v ) {
					$attribute_slug = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::sanitize_taxonomy_name( $option_v['name'] );
					$attribute_id   = wc_attribute_taxonomy_id_by_name( $option_v['name'] );
					if ( ! $attribute_id ) {
						$attribute_id = wc_create_attribute( array(
							'name'         => $option_v['name'],
							'slug'         => $attribute_slug,
							'type'         => 'select',
							'order_by'     => 'menu_order',
							'has_archives' => false,
						) );
					}
					if ( $attribute_id && ! is_wp_error( $attribute_id ) ) {
						$attribute_obj     = wc_get_attribute( $attribute_id );
						$attribute_options = array();
						if ( ! empty( $attribute_obj ) ) {
							$taxonomy                   = $attribute_obj->slug; // phpcs:ignore
							$wp_taxonomies[ $taxonomy ] = new WP_Taxonomy( $taxonomy, 'product' );
							if ( count( $option_v['values'] ) ) {
								foreach ( $option_v['values'] as $term_k => $term_v ) {
									$option_v['values'][ $term_k ] = strval( wc_clean( $term_v ) );
									$insert_term                   = wp_insert_term( $option_v['values'][ $term_k ], $taxonomy );
									if ( ! is_wp_error( $insert_term ) ) {
										$attribute_options[] = $insert_term['term_id'];
									} elseif ( isset( $insert_term->error_data ) && isset( $insert_term->error_data['term_exists'] ) ) {
										$attribute_options[] = $insert_term->error_data['term_exists'];
									}
								}
							}
						}
						$attribute_object = new WC_Product_Attribute();
						$attribute_object->set_id( $attribute_id );
						$attribute_object->set_name( wc_attribute_taxonomy_name_by_id( $attribute_id ) );
						if ( count( $attribute_options ) ) {
							$attribute_object->set_options( $attribute_options );
						} else {
							$attribute_object->set_options( $option_v['values'] );
						}
						$attribute_object->set_position( isset( $option_v['position'] ) ? $option_v['position'] : $position );
						$attribute_object->set_visible( apply_filters( 's2w_create_product_attribute_set_visible', 0, $option_v ) );
						$attribute_object->set_variation( 1 );
						$attr_data[] = $attribute_object;
					}
					$position ++;
				}
			} else {
				$position = 1;
				foreach ( $options as $option_k => $option_v ) {
					$attribute_object = new WC_Product_Attribute();
					$attribute_object->set_name( $option_v['name'] );
					$attribute_object->set_options( $option_v['values'] );
					$attribute_object->set_position( isset( $option_v['position'] ) ? $option_v['position'] : $position );
					$attribute_object->set_visible( apply_filters( 's2w_create_product_attribute_set_visible', 0, $option_v ) );
					$attribute_object->set_variation( 1 );
					$attr_data[] = $attribute_object;
					$position ++;
				}
			}
		}

		/**
		 * Import vendor based on settings
		 *
		 * @param $product_id
		 * @param $vendor
		 */
		public static function import_product_vendor( $product_id, $vendor ) {
			if ( $vendor ) {
				$product_vendor_as = self::$settings->get_params( 'product_vendor_as' );
				if ( $product_vendor_as ) {
					switch ( $product_vendor_as ) {
						case 'category':
							wp_set_object_terms( $product_id, $vendor, 'product_cat', true );
							break;
						case 'tag':
							wp_set_object_terms( $product_id, $vendor, 'product_tag', true );
							break;
						case 'meta':
							$product_vendor_meta = self::$settings->get_params( 'product_vendor_meta' );
							if ( $product_vendor_meta ) {
								update_post_meta( $product_id, $product_vendor_meta, $vendor );
							}
							break;
						default:
							wp_set_object_terms( $product_id, $vendor, $product_vendor_as, false );
					}
				}
			}
		}

		/**
		 * Import product type based on settings
		 *
		 * @param $product_id
		 * @param $product_type
		 */
		public static function import_product_type( $product_id, $product_type ) {
			if ( $product_type ) {
				$product_type_as = self::$settings->get_params( 'product_type_as' );
				if ( $product_type_as ) {
					switch ( $product_type_as ) {
						case 'category':
							wp_set_object_terms( $product_id, $product_type, 'product_cat', true );
							break;
						case 'tag':
							wp_set_object_terms( $product_id, $product_type, 'product_tag', true );
							break;
						case 'meta':
							$product_type_meta = self::$settings->get_params( 'product_type_meta' );
							if ( $product_type_meta ) {
								update_post_meta( $product_id, $product_type_meta, $product_type );
							}
							break;
						default:
							wp_set_object_terms( $product_id, $product_type, $product_type_as, false );
					}
				}
			}
		}

		/**
		 * Import barcode
		 *
		 * @param $product_id
		 * @param $barcode
		 */
		public static function import_product_barcode( $product_id, $barcode ) {
			$product_barcode_meta = self::$settings->get_params( 'product_barcode_meta' );
			if ( $product_barcode_meta ) {
				update_post_meta( $product_id, $product_barcode_meta, $barcode );
			}
		}

		/**
		 * Make sure emails are not sent to customers when migrating orders
		 */
		public static function remove_refunded_email_notification() {
			remove_all_actions( 'woocommerce_order_refunded' );
			remove_all_actions( 'woocommerce_order_status_refunded_notification' );
			remove_all_actions( 'woocommerce_order_fully_refunded_notification' );
			remove_all_actions( 'woocommerce_order_partially_refunded_notification' );
			remove_action( 'woocommerce_order_status_refunded', 'wc_order_fully_refunded' );
			remove_action( 'woocommerce_order_status_refunded', array(
				'WC_Emails',
				'send_transactional_email'
			) );
			remove_action( 'woocommerce_order_fully_refunded', array(
				'WC_Emails',
				'send_transactional_email'
			) );
			remove_action( 'woocommerce_order_partially_refunded', array(
				'WC_Emails',
				'send_transactional_email'
			) );
		}

		/**
		 * @return mixed|void
		 */
		private static function get_required_capability() {
			return apply_filters( 'vi_s2w_admin_menu_capability', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_required_capability( 'change_settings' ), 's2w-import-shopify-to-woocommerce' );
		}

		/**
		 * Look for all images from post(Shopify blog) description and import them based on settings
		 *
		 * @param $description
		 * @param $post_id
		 * @param $dispatch
		 */
		public static function handle_post_description_images( $description, $post_id, &$dispatch ) {
			preg_match_all( '/src="([\s\S]*?)"/im', preg_replace( '/<script\>[\s\S]*?<\/script>/im', '', preg_replace( '/<iframe\>[\s\S]*?<\/iframe>/im', '', $description ) ), $matches );
			if ( isset( $matches[1] ) && is_array( $matches[1] ) && count( $matches[1] ) ) {
				$dispatch           = true;
				$description_images = array_unique( $matches[1] );
				foreach ( $description_images as $description_image ) {
					$post_image = array(
						'post_id'           => $post_id,
						'src'               => $description_image,
						'description_image' => 1,
					);
					self::$process_post_image->push_to_queue( $post_image );
				}
			}
		}

		/**
		 * Look for all images from product description and import them based on settings
		 *
		 * @param $description
		 * @param $product_id
		 * @param $dispatch
		 * @param $process
		 */
		public static function handle_description_images( $description, $product_id, &$dispatch, &$process ) {
			if ( $description && self::$settings->get_params( 'download_description_images' ) && ! VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::use_external_image() ) {
				preg_match_all( '/src="([\s\S]*?)"/im', preg_replace( '/<script [\s\S]*?<\/script>/im', '', preg_replace( '/<iframe [\s\S]*?<\/iframe>/im', '', $description ) ), $matches );
				if ( isset( $matches[1] ) && is_array( $matches[1] ) && count( $matches[1] ) ) {
					$description_images = array_unique( $matches[1] );
					if ( self::$settings->get_params( 'disable_background_process' ) ) {
						foreach ( $description_images as $description_image ) {
							S2W_Error_Images_Table::insert( $product_id, '', $description_image, '', 2, '' );
						}
					} else {
						$dispatch = true;
						foreach ( $description_images as $description_image ) {
							$images_data = array(
								'id'          => '',
								'src'         => $description_image,
								'alt'         => '',
								'parent_id'   => $product_id,
								'product_ids' => array(),
								'set_gallery' => 2,
							);
							$process->push_to_queue( $images_data );
						}
					}
				}
			}
		}

		/**
		 * Import/update a product based on Shopify product data format
		 * Used for both API(Basic import from settings page, Webhooks, Import by ID) and CSV import
		 *
		 * @param $options
		 * @param $product_data
		 * @param $sku
		 * @param $global_attributes
		 * @param $product_status
		 * @param $keep_slug
		 * @param $download_images
		 * @param $product_categories
		 * @param $images_d
		 * @param string $product_author
		 * @param int $existing_id
		 *
		 * @return int|WP_Error
		 */
		public static function import_product( $options, $product_data, $sku, $global_attributes, $product_status, $keep_slug, $download_images, $product_categories, &$images_d, $product_author = '', $existing_id = 0 ) {
			try {
				$placeholder_image_id = s2w_get_placeholder_image();
				$manage_stock         = ( 'yes' === get_option( 'woocommerce_manage_stock' ) ) ? true : false;
				$variants             = isset( $product_data['variants'] ) ? $product_data['variants'] : array();
				$vendor               = apply_filters( 's2w_import_product_vendor', isset( $product_data['vendor'] ) ? $product_data['vendor'] : '', $product_data );
				$product_type         = apply_filters( 's2w_import_product_type', isset( $product_data['product_type'] ) ? $product_data['product_type'] : '', $product_data );
				$product_status       = apply_filters( 's2w_import_product_status', $product_status, $product_data );
				$attr_data            = array();
				$description          = isset( $product_data['body_html'] ) ? html_entity_decode( $product_data['body_html'], ENT_QUOTES | ENT_XML1, 'UTF-8' ) : '';
				$data                 = array( // Set up the basic post data to insert for our product
					'post_type'    => 'product',
					'post_excerpt' => '',
					'post_content' => $description,
					'post_title'   => isset( $product_data['title'] ) ? $product_data['title'] : '',
					'post_status'  => $product_status,
					'post_parent'  => '',
					'meta_input'   => array(
						'_visibility' => 'visible',
					)
				);
				$shopify_id           = isset( $product_data['id'] ) ? $product_data['id'] : '';
				if ( $shopify_id ) {
					$data['meta_input']['_shopify_product_id'] = $shopify_id;
					$domain                                    = self::$settings->get_params( 'domain' );
					$data['meta_input']['_s2w_shopify_domain'] = $domain;/*Compa w2s - for update product to shopify*/
					$data['meta_input']['_shopify_variant_id'] = $variants[0]['id'];/*Compa w2s - for update simple product to shopify*/
				}
				if ( $keep_slug && $product_data['handle'] ) {
					$data['post_name'] = $product_data['handle'];
				}
				if ( $product_author ) {
					$data['post_author'] = $product_author;
				}
				if (
				        (count( $options ) == 1) &&
                        (count( $options[0]['values'] )) == 1 &&
				        $options[0]['name'] === 'Title' && $options[0]['values'][0] === 'Default Title'
                ) {
					/*If a product only has 1 option and 1 option value, import it as a simple product*/
					$simple_sku                 = apply_filters( 's2w_simple_product_sku', $variants[0]['sku'], $shopify_id, $product_data['handle'] );
					$data['meta_input']['_sku'] = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::sku_exists( $simple_sku ) ? '' : $simple_sku;
					$regular_price              = $variants[0]['compare_at_price'];
					$sale_price                 = $variants[0]['price'];
					if ( ! floatval( $regular_price ) || floatval( $regular_price ) == floatval( $sale_price ) ) {
						$regular_price = $sale_price;
						$sale_price    = '';
					}
					$data['meta_input']['_regular_price'] = $regular_price;
					$data['meta_input']['_price']         = $regular_price;
					$data['meta_input']['_sale_price']    = $sale_price;
					if ( $sale_price ) {
						$data['meta_input']['_price'] = $sale_price;
					}
					if ( $options[0]['name'] !== 'Title' && $options[0]['values'][0] !== 'Default Title' ) {
						self::create_product_attributes( $global_attributes, $options, $attr_data );
					}
					if ( ! empty( $variants[0]['weight'] ) ) {
						$data['meta_input']['_weight'] = $variants[0]['weight'];
					}
					if ( $existing_id ) {
						$data['ID'] = $existing_id;
						$product_id = wp_update_post( $data, true );
					} else {
						$product_id = wp_insert_post( apply_filters( 's2w_import_product_post_data', $data, $product_data ), true );
					}
					if ( ! is_wp_error( $product_id ) ) {
						$images = isset( $product_data['images'] ) ? $product_data['images'] : array();
						if ( $download_images ) {
							if ( count( $images ) ) {
								foreach ( $images as $image ) {
									$images_d[] = array(
										'id'          => empty( $image['id'] ) ? '' : $image['id'],
										'src'         => $image['src'],
										'alt'         => $image['alt'],
										'parent_id'   => $product_id,
										'product_ids' => array(),
										'set_gallery' => 1,
									);
								}
								$images_d[0]['product_ids'][] = $product_id;
								$images_d[0]['set_gallery']   = 0;
								if ( $placeholder_image_id ) {
									update_post_meta( $product_id, '_thumbnail_id', $placeholder_image_id );
								}
							}
						}
						wp_set_object_terms( $product_id, 'simple', 'product_type' );
						if ( is_array( $product_categories ) && count( $product_categories ) ) {
							wp_set_post_terms( $product_id, $product_categories, 'product_cat', true );
						}

						$tags = isset( $product_data['tags'] ) ? $product_data['tags'] : '';
						if ( $tags ) {
							wp_set_object_terms( $product_id, explode( ',', $tags ), 'product_tag' );
						}
						self::import_product_vendor( $product_id, $vendor );
						self::import_product_type( $product_id, $product_type );
						if ( isset( $variants[0]['barcode'] ) ) {
							$barcode = apply_filters( 's2w_import_product_barcode', $variants[0]['barcode'], $variants[0] );
							self::import_product_barcode( $product_id, $barcode );
						}
						$product_obj = wc_get_product( $product_id );
						if ( $product_obj ) {
							if ( count( $attr_data ) ) {
								$product_obj->set_attributes( $attr_data );
							}
							/*Add condition ['inventory_management'] not equal null*/
							if ( $manage_stock && isset( $variants[0]['inventory_management'] ) && !empty( $variants[0]['inventory_management'] ) ) {
								$product_obj->set_manage_stock( 'yes' );
								$product_obj->set_stock_quantity( $variants[0]['inventory_quantity'] );
								if ( $variants[0]['inventory_quantity'] ) {
									$product_obj->set_stock_status( 'instock' );
								} else {
									$product_obj->set_stock_status( 'outofstock' );
								}
								if ( $variants[0]['inventory_policy'] === 'continue' ) {
									$product_obj->set_backorders( 'yes' );
								} else {
									$product_obj->set_backorders( 'no' );
								}
							} else {
								$product_obj->set_manage_stock( 'no' );
								$product_obj->set_stock_status( 'instock' );
							}
							$product_obj->save();
						}
					} else {
						wc_get_logger()->debug( $product_id->get_error_message(), [ 'source' => 'S2W' ] );
					}
				} else {
					$data['meta_input']['_sku']          = $sku;
					$data['meta_input']['_manage_stock'] = 'no';
					self::create_product_attributes( $global_attributes, $options, $attr_data );
					if ( $existing_id ) {
						$data['ID'] = $existing_id;
						$product_id = wp_update_post( $data, true );
					} else {
						$product_id = wp_insert_post( apply_filters( 's2w_import_product_post_data', $data, $product_data ), true );
					}
					if ( ! is_wp_error( $product_id ) ) {
						wp_set_object_terms( $product_id, 'variable', 'product_type' );
						$product_obj         = wc_get_product( $product_id );
						$existing_variations = array();
						if ( $product_obj ) {
							if ( count( $attr_data ) ) {
								$product_obj->set_attributes( $attr_data );
								$product_obj->save();
								wp_set_object_terms( $product_id, 'variable', 'product_type' );
							}
							if ( $existing_id ) {
								$existing_variations = $product_obj->get_children();
							}
						}
						$images_src = array();
						$images     = isset( $product_data['images'] ) ? $product_data['images'] : array();
						if ( $download_images ) {
							if ( count( $images ) ) {
								foreach ( $images as $image ) {
									$images_d[]   = array(
										'id'          => empty( $image['id'] ) ? '' : $image['id'],
										'src'         => $image['src'],
										'alt'         => $image['alt'],
										'parent_id'   => $product_id,
										'product_ids' => array(),
										'set_gallery' => 1,
									);
									$images_src[] = $image['src'];
								}
								$images_d[0]['product_ids'][] = $product_id;
								$images_d[0]['set_gallery']   = 0;
								if ( $placeholder_image_id && ! get_post_meta( $product_id, '_thumbnail_id', true ) ) {
									update_post_meta( $product_id, '_thumbnail_id', $placeholder_image_id );
								}
							}
						}
						if ( is_array( $product_categories ) && count( $product_categories ) ) {
							wp_set_post_terms( $product_id, $product_categories, 'product_cat', true );
						}
						$tags = isset( $product_data['tags'] ) ? $product_data['tags'] : '';
						if ( $tags ) {
							wp_set_object_terms( $product_id, explode( ',', $product_data['tags'] ), 'product_tag' );
						}
						self::import_product_vendor( $product_id, $vendor );
						self::import_product_type( $product_id, $product_type );
						if ( is_array( $variants ) && count( $variants ) ) {
							foreach ( $variants as $variant ) {
								vi_s2w_set_time_limit();
								$attributes            = self::create_variation_attributes( $global_attributes, $options, $variant );
								$existing_variation_id = 0;
								if ( $existing_id ) {
									if ( ! empty( $variant['id'] ) && $existing_variations ) {
										/*Check if a variation already exists by Shopify variant ID*/
										foreach ( $existing_variations as $existing_variation ) {
											if ( $variant['id'] == get_post_meta( $existing_variation, '_shopify_variation_id', true ) ) {
												$existing_variation_id = $existing_variation;
												break;
											}
										}
									}
									if ( ! $existing_variation_id ) {
										/*Check if a variation already exists by its attributes*/
										$search_attributes = array();
										foreach ( $attributes as $attribute_k => $attribute_v ) {
											$search_attributes[ 'attribute_' . $attribute_k ] = $attribute_v;
										}
										$existing_variation_id = self::find_matching_product_variation_id( $product_id, $search_attributes );
									}
								}
								$variation_obj = new WC_Product_Variation( $existing_variation_id );
								$variation_obj->set_parent_id( $product_id );
								$variation_obj->set_attributes( $attributes );
								$regular_price = $variant['compare_at_price'];
								$sale_price    = $variant['price'];
								if ( ! floatval( $regular_price ) || floatval( $regular_price ) == floatval( $sale_price ) ) {
									$regular_price = $sale_price;
									$sale_price    = '';
								}
								$fields = array(
									'sku'           => VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::sku_exists( $variant['sku'] ) ? '' : $variant['sku'],
									'regular_price' => $regular_price,
								);
								if ( $existing_variation_id || $sale_price ) {
									$fields['sale_price'] = $sale_price;
								}
								/*Add condition $variant['inventory_management'] not equal null*/
								if ( $manage_stock && isset( $variants['inventory_management'] ) && !empty( $variant['inventory_management'] ) ) {
									$variation_obj->set_manage_stock( 'yes' );
									$variation_obj->set_stock_quantity( $variant['inventory_quantity'] );
									if ( $variant['inventory_quantity'] ) {
										$variation_obj->set_stock_status( 'instock' );
									} else {
										$variation_obj->set_stock_status( 'outofstock' );
									}
									if ( $variant['inventory_policy'] === 'continue' ) {
										$variation_obj->set_backorders( 'yes' );
									} else {
										$variation_obj->set_backorders( 'no' );
									}
								} else {
									$variation_obj->set_manage_stock( 'no' );
									$variation_obj->set_stock_status( 'instock' );
								}
								if ( ! empty( $variant['weight'] ) ) {
									$fields['weight'] = $variant['weight'];
								}
								foreach ( $fields as $field => $field_v ) {
									$variation_obj->{"set_$field"}( wc_clean( $field_v ) );
								}
								$variation_obj_id = $variation_obj->save();
								if ( ! $existing_variation_id ) {
									/*Only apply this hook if this variation is newly added*/
									do_action( 'product_variation_linked', $variation_obj_id );
								}
								if ( $download_images ) {
									if ( ! empty( $variant['id'] ) ) {
										if ( count( $images ) ) {
											foreach ( $images as $image_k => $image_v ) {
												if ( in_array( $variant['id'], $image_v['variant_ids'] ) ) {
													$images_d[ $image_k ]['product_ids'][] = $variation_obj_id;
													if ( $placeholder_image_id ) {
														update_post_meta( $variation_obj_id, '_thumbnail_id', $placeholder_image_id );
													}
												}
											}
										}
									} else {
										if ( $images_src && $variant['image'] ) {
											$variation_image_search = array_search( $variant['image'], $images_src );
											if ( $variation_image_search !== false ) {
												$images_d[ $variation_image_search ]['product_ids'][] = $variation_obj_id;
											} else {
												$images_d[] = array(
													'src'         => $variant['image'],
													'alt'         => '',
													'parent_id'   => $product_id,
													'product_ids' => array( $variation_obj_id ),
													'set_gallery' => 0,
												);
											}
											if ( $placeholder_image_id && ! get_post_meta( $variation_obj_id, '_thumbnail_id', true ) ) {
												update_post_meta( $variation_obj_id, '_thumbnail_id', $placeholder_image_id );
											}
										}
									}
								}
								if ( ! empty( $variant['id'] ) ) {
									update_post_meta( $variation_obj_id, '_shopify_variation_id', $variant['id'] );
								}
								if ( isset( $variant['barcode'] ) ) {
									$barcode = apply_filters( 's2w_import_product_barcode', $variant['barcode'], $variant );
									self::import_product_barcode( $variation_obj_id, $barcode );
								}
								if ( $product_author ) {
									wp_update_post( array(
										'ID'          => $variation_obj_id,
										'post_author' => $product_author
									) );
								}
							}
						}
					} else {
						wc_get_logger()->debug( $product_id->get_error_message(), [ 'source' => 'S2W' ] );
					}
				}

				return $product_id;
			} catch ( Error $e ) {
				return new WP_Error( 's2w_import_product_error', $e->getMessage() );
			} catch ( Exception $e ) {
				return new WP_Error( 's2w_import_product_error', $e->getMessage() );
			}
		}

		/**
		 * Find variation of a product based on attributes
		 *
		 * @param $product_id
		 * @param $attributes
		 *
		 * @return int
		 */
		private static function find_matching_product_variation_id( $product_id, $attributes ) {
			return ( new \WC_Product_Data_Store_CPT() )->find_matching_product_variation(
				new \WC_Product( $product_id ),
				$attributes
			);
		}

		/**
		 * Check ajax nonce
		 */
		public static function check_ajax_referrer() {
			check_ajax_referer( 's2w_action_nonce', '_s2w_nonce' );
		}

		/**
		 * Currently supported data types
		 *
		 * @return array
		 */
		private static function get_supported_elements() {
			return array(
				'store_settings'     => esc_html__( 'Store settings', 's2w-import-shopify-to-woocommerce' ),
				'shipping_zones'     => esc_html__( 'Shipping zones', 's2w-import-shopify-to-woocommerce' ),
				'taxes'              => esc_html__( 'Taxes', 's2w-import-shopify-to-woocommerce' ),
				'pages'              => esc_html__( 'Pages', 's2w-import-shopify-to-woocommerce' ),
				'blogs'              => esc_html__( 'Blogs', 's2w-import-shopify-to-woocommerce' ),
				'customers'          => esc_html__( 'Customers', 's2w-import-shopify-to-woocommerce' ),
				'products'           => esc_html__( 'Products', 's2w-import-shopify-to-woocommerce' ),
				'product_categories' => esc_html__( 'Product categories', 's2w-import-shopify-to-woocommerce' ),
				'coupons'            => esc_html__( 'Coupons', 's2w-import-shopify-to-woocommerce' ),
				'orders'             => esc_html__( 'Orders', 's2w-import-shopify-to-woocommerce' ),
			);
		}

		/**
		 * @param $element
		 *
		 * @return false|string
		 */
		private static function get_singular_element_name( $element ) {
			switch ( $element ) {
				case 'product_categories':
					$singular = 'product_category';
					break;
				case 'taxes':
					$singular = 'tax';
					break;
				default:
					$singular = substr( $element, 0, ( strlen( $element ) - 1 ) );
			}

			return $singular;
		}

		/**
		 * @return array
		 */
		private static function get_blogs_update_options() {
			return array(
				'description' => esc_html__( 'Description', 's2w-import-shopify-to-woocommerce' ),
				'tags'        => esc_html__( 'Tags', 's2w-import-shopify-to-woocommerce' ),
				'categories'  => esc_html__( 'Categories', 's2w-import-shopify-to-woocommerce' ),
				'date'        => esc_html__( 'Date & Status', 's2w-import-shopify-to-woocommerce' ),
			);
		}

		/**
		 * @param $args
		 * @param string $prefix
		 */
		public static function fill_params_from_post( &$args, $prefix = '' ) {
			foreach ( $args as $key => $arg ) {
				$post_key = "{$prefix}{$key}";
				if ( isset( $_POST[ $post_key ] ) ) {
					if ( is_array( $_POST[ $post_key ] ) ) {
						$args[ $key ] = stripslashes_deep( $_POST[ $post_key ] );
					} else {
						$args[ $key ] = sanitize_text_field( stripslashes( $_POST[ $post_key ] ) );
					}
				} else {
					if ( is_array( $arg ) ) {
						$args[ $key ] = array();
					} else {
						$args[ $key ] = '';
					}
				}
			}
		}

		/**
		 * @param $recipient
		 * @param $order
		 *
		 * @return boolean
		 */
		public static function s2w_disable_customer_order_email_when_importing( $recipient, $order ) {
			return false;
		}
	}
}

new S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE();