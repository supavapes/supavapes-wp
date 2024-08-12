<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA {
	private $params;
	private $default;
	private static $prefix;
	protected static $instance = null;
	protected static $allow_html = null;

	/**
	 * VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA constructor.
	 * Init setting
	 */
	public function __construct() {
		self::$prefix = 's2w-';
		global $s2w_settings;
		if ( ! $s2w_settings ) {
			$s2w_settings = get_option( 's2w_params', array() );
		}
		$this->default = array(
			'domain'                => '',
			/*access_token is for custom apps*/
			'access_token'          => '',
			/*api_key and api_secret are for private apps which are deprecated and can't be created as of January 2022*/
			'api_key'               => '',
			'api_secret'            => '',
			'number'                => '',
			'validate'              => '',
			'auto_update_key'       => '',
			'request_timeout'       => '60',
			/*Import pages options*/
			'spages_per_request'    => 10,

			/*Import blogs options*/
			'blogs_update_if_exist' => array(),
			/*Role settings*/
			'capabilities'          => array(
				'change_settings' => 'manage_options',
				'cron_products'   => 'manage_woocommerce',
				'cron_orders'     => 'manage_woocommerce',
				'webhooks'        => 'manage_options',
				'import_by_id'    => 'manage_woocommerce',
				'import_csv'      => 'manage_woocommerce',
				'failed_images'   => 'manage_woocommerce',
				'access_logs'     => 'manage_woocommerce',
			),
		);
		$this->default = wp_parse_args( $this->default, $this->get_default_products_import_params() );
		$this->default = wp_parse_args( $this->default, $this->get_default_orders_import_params() );
		$this->default = wp_parse_args( $this->default, $this->get_default_coupons_import_params() );
		$this->default = wp_parse_args( $this->default, $this->get_default_customers_import_params() );

		$this->default = wp_parse_args( $this->default, $this->get_default_products_sync_params() );
		$this->default = wp_parse_args( $this->default, $this->get_default_orders_sync_params() );
		$this->default = wp_parse_args( $this->default, $this->get_default_cron_products_sync_params() );
		$this->default = wp_parse_args( $this->default, $this->get_default_cron_orders_sync_params() );

		$this->default = wp_parse_args( $this->default, $this->get_default_webhooks_params() );
		$this->default = wp_parse_args( $this->default, $this->get_default_csv_import_params() );

		$this->params = apply_filters( 's2w_params', wp_parse_args( $s2w_settings, $this->default ) );
	}

	/**
	 * Params used for webhooks
	 *
	 * @return array
	 */
	public function get_default_webhooks_params() {
		return array(
			/*Webhook settings*/
			'webhooks_shared_secret'          => '',
			/*Webhook settings - orders*/
			'webhooks_orders_enable'          => '',
			'webhooks_orders_create_customer' => '',
			'webhooks_orders_options'         => array(
				'order_status',
			),
			'webhooks_order_status_mapping'   => $this->get_params( 'order_status_mapping' ),
			/*Webhook settings - products*/
			'webhooks_products_enable'        => '',
			'only_update_product_exist'       => '',
			'webhooks_products_options'       => array(
				'inventory',
			),
			/*Webhook settings - customers*/
			'webhooks_customers_enable'       => '',
		);
	}

	/**
	 * Params used for orders sync(manually)
	 *
	 * @return array
	 */
	public function get_default_orders_sync_params() {
		return array(
			/*Sync orders options*/
			'update_order_options_show' => 1,
			'update_order_options'      => array(
				'order_status',
				'order_date',
			),
		);
	}

	/**
	 * Params used for products sync(manually)
	 *
	 * @return array
	 */
	public function get_default_products_sync_params() {
		return array(
			/*products sync*/
			'update_product_options_show' => 1,
			'update_product_options'      => array(
				'images',
				'price'
			),
			'update_product_metafields'   => array(),
		);
	}

	/**
	 * Params used for Cron orders sync
	 *
	 * @return array
	 */
	public function get_default_cron_orders_sync_params() {
		return array(
			/*Cron orders sync*/
			'cron_update_orders'            => 0,
			'cron_update_orders_force_sync' => 0,
			'cron_update_orders_options'    => array( 'status' ),
			'cron_update_orders_status'     => array( 'wc-pending', 'wc-on-hold', 'wc-processing' ),
			'cron_update_orders_range'      => 30,
			'cron_update_orders_interval'   => 5,
			'cron_update_orders_hour'       => 0,
			'cron_update_orders_minute'     => 0,
			'cron_update_orders_second'     => 0,
			'cron_update_orders_date_paid'  => 0,
		);
	}

	/**
	 * Params used for Cron products sync
	 *
	 * @return array
	 */
	public function get_default_cron_products_sync_params() {
		return array(
			/*Cron products sync*/
			'cron_update_products'            => 0,
			'cron_update_products_force_sync' => 0,
			'cron_update_products_options'    => array( 'inventory' ),
			'cron_update_products_status'     => array( 'publish' ),
			'cron_update_products_categories' => array(),
			'cron_update_products_interval'   => 5,
			'cron_update_products_hour'       => 0,
			'cron_update_products_minute'     => 0,
			'cron_update_products_second'     => 0,
		);
	}

	/**
	 * Params used for coupons import
	 *
	 * @return array
	 */
	public function get_default_coupons_import_params() {
		return array(
			/*Import coupons options*/
			'coupons_per_request'    => '100',
			'coupon_starts_at_min'   => '',
			'coupon_starts_at_max'   => '',
			'coupon_ends_at_min'     => '',
			'coupon_ends_at_max'     => '',
			'coupon_zero_times_used' => '1',
		);
	}

	/**
	 * Params used for customers import
	 *
	 * @return array
	 */
	public function get_default_customers_import_params() {
		return array(
			/*Import customers options*/
			'customers_per_request'         => '100',
			'customers_last_date_import'    => '',
			'customers_role'                => 'customer',
			'customers_with_purchases_only' => '',
			'update_existing_customers'     => '',
		);
	}

	/**
	 * Params used for orders import
	 *
	 * @return array
	 */
	public function get_default_orders_import_params() {
		return array(
			/*Import orders options*/
			'order_since_id'           => '',
			'order_processed_at_min'   => '',
			'order_processed_at_max'   => '',
			'order_financial_status'   => 'any',
			'order_fulfillment_status' => 'any',
			'orders_per_request'       => '50',
			'order_tag_to_status'      => array(),//Custom work -> do not remove
			'order_status_mapping'     => array(
				'pending'            => 'pending',
				'authorized'         => 'processing',
				'partially_paid'     => 'completed',
				'paid'               => 'completed',
				'refunded'           => 'refunded',
				'partially_refunded' => 'refunded',
				'voided'             => 'cancelled',
			),
			'order_import_sequence'    => 'desc',
		);
	}

	/**
	 * Params used for products CSV import
	 *
	 * @return array
	 */
	public function get_default_csv_import_params() {
		return array(
			'download_images_later' => '1',
			'csv_if_product_exists' => 'skip',//used for CSV import only
		);
	}

	/**
	 * Params used for products import
	 *
	 * @return array
	 */
	public function get_default_products_import_params() {
		return array(
			/*Import products options*/
			'download_images'             => '1',
			'use_external_image'          => '',
			'disable_background_process'  => '',
			'download_description_images' => '1',
			'keep_slug'                   => '1',
			'product_status'              => 'publish',
			'product_status_mapping'      => array(
				'active'   => 'publish',
				'archived' => 'pending',
				'draft'    => 'draft',
			),
			'product_categories'          => array(),
			'products_per_request'        => '5',
			'product_import_sequence'     => 'title asc',
			'product_since_id'            => '',
			'product_product_type'        => '',
			'product_collection_id'       => '',
			'product_vendor'              => '',
			'product_created_at_min'      => '',
			'product_created_at_max'      => '',
			'product_published_at_min'    => '',
			'product_published_at_max'    => '',
			'global_attributes'           => 0,
			'variable_sku'                => '{shopify_product_id}',
			'product_type_as'             => '',
			'product_type_meta'           => '',
			'product_vendor_as'           => '',
			'product_vendor_meta'         => '',
			'product_barcode_meta'        => '',
			'product_author'              => '',
			'update_existing_products'    => '',
		);
	}

	/**
	 * Make sure only 1 instance of this class exists
	 *
	 * @param bool $new
	 *
	 * @return VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA
	 */
	public static function get_instance( $new = false ) {
		if ( $new || null === self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Get a setting param, use this method to make sure undefined error does not occur
	 *
	 * @param string $name
	 *
	 * @return bool|mixed|void
	 */
	public function get_params( $name = '' ) {
		if ( ! $name ) {
			return $this->params;
		} elseif ( isset( $this->params[ $name ] ) ) {
			return apply_filters( 's2w_params' . $name, $this->params[ $name ] );
		} else {
			return false;
		}
	}

	/**
	 * @param string $name
	 *
	 * @return array|bool|mixed|void
	 */
	public function get_default( $name = '' ) {
		if ( ! $name ) {
			return $this->default;
		} elseif ( isset( $this->default[ $name ] ) ) {
			return apply_filters( 's2w_params_default' . $name, $this->default[ $name ] );
		} else {
			return false;
		}
	}

	/**
	 * @param $name
	 * @param bool $set_name
	 *
	 * @return string|void
	 */
	public static function set( $name, $set_name = false ) {
		if ( is_array( $name ) ) {
			return implode( ' ', array_map( array( 'VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA', 'set' ), $name ) );
		} else {
			if ( $set_name ) {
				return esc_attr( str_replace( '-', '_', self::$prefix . $name ) );
			} else {
				return esc_attr( self::$prefix . $name );
			}
		}
	}

	/**
	 * Check if sku exists
	 *
	 * @param string $sku
	 *
	 * @return bool
	 */
	public static function sku_exists( $sku = '' ) {
		global $wpdb;
		$sku_exists = false;
		if ( $sku ) {
			/*Not sure which method is faster
			$id_from_sku = wc_get_product_id_by_sku( $sku );
			$product     = $id_from_sku ? wc_get_product( $id_from_sku ) : false;
			$sku_exists  = $product && 'importing' !== $product->get_status();
			*/
			$table_posts    = "{$wpdb->prefix}posts";
			$table_postmeta = "{$wpdb->prefix}postmeta";
			$query          = "SELECT count(*) from {$table_postmeta} join {$table_posts} on {$table_postmeta}.post_id={$table_posts}.ID where {$table_posts}.post_type in ('product','product_variation') and {$table_posts}.post_status in ('publish','draft','private','pending') and {$table_postmeta}.meta_key = '_sku' and {$table_postmeta}.meta_value = %s";
			$results        = $wpdb->get_var( $wpdb->prepare( $query, $sku ) );
			if ( intval( $results ) > 0 ) {
				$sku_exists = true;
			}
		}

		return $sku_exists;
	}

	/**
	 * Find Woo product ID(s) by Shopify ID
	 *
	 * @param $shopify_id
	 * @param bool $is_variation
	 * @param bool $count
	 * @param bool $multiple
	 *
	 * @return array|null|object|string
	 */
	public static function product_get_woo_id_by_shopify_id( $shopify_id, $is_variation = false, $count = false, $multiple = false ) {
		global $wpdb;
		if ( $shopify_id ) {
			$table_posts    = "{$wpdb->prefix}posts";
			$table_postmeta = "{$wpdb->prefix}postmeta";
			if ( $is_variation ) {
				$post_type = 'product_variation';
				$meta_key  = '_shopify_variation_id';
			} else {
				$post_type = 'product';
				$meta_key  = '_shopify_product_id';
			}
			if ( $count ) {
				$query   = "SELECT count(*) from {$table_postmeta} join {$table_posts} on {$table_postmeta}.post_id={$table_posts}.ID where {$table_posts}.post_type = '{$post_type}' and {$table_posts}.post_status != 'trash' and {$table_postmeta}.meta_key = '{$meta_key}' and {$table_postmeta}.meta_value = %s";
				$results = $wpdb->get_var( $wpdb->prepare( $query, $shopify_id ) );
			} else {
				$query = "SELECT {$table_postmeta}.* from {$table_postmeta} join {$table_posts} on {$table_postmeta}.post_id={$table_posts}.ID where {$table_posts}.post_type = '{$post_type}' and {$table_posts}.post_status != 'trash' and {$table_postmeta}.meta_key = '{$meta_key}' and {$table_postmeta}.meta_value = %s";
				if ( $multiple ) {
					$results = $wpdb->get_results( $wpdb->prepare( $query, $shopify_id ), ARRAY_A );
				} else {
					$results = $wpdb->get_var( $wpdb->prepare( $query, $shopify_id ), 1 );
				}
			}

			return $results;
		} else {
			return false;
		}
	}

	/**
	 * Find Order, Coupon, Image, Post, Page ID(s) by Shopify ID
	 *
	 * @param $shopify_id
	 * @param bool $count
	 * @param string $type
	 * @param bool $multiple
	 * @param string $meta_key
	 *
	 * @return array|bool|null|object|string
	 */
	public static function query_get_id_by_shopify_id( $shopify_id, $type = 'order', $count = false, $multiple = false, $meta_key = '' ) {
		global $wpdb;
		if ( $shopify_id ) {
			$table_posts    = "{$wpdb->prefix}posts";
			$table_postmeta = "{$wpdb->prefix}postmeta";
			switch ( $type ) {
				case 'image':
					$post_type = 'attachment';
					break;
				case 'post':
				case 'blog':
					$post_type = 'post';
					break;
				case 'page':
					$post_type = 'page';
					break;
				case 'coupon':
				case 'price_rule':
					$post_type = 'shop_coupon';
					break;
				case 'order':
				default:
					$post_type = 'shop_order';
					if ( \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
						$table_posts    = "{$wpdb->prefix}wc_orders";
						$table_postmeta = "{$wpdb->prefix}wc_orders_meta";
					}
					break;
			}
			if ( ! $meta_key ) {
				$meta_key = "_s2w_shopify_{$type}_id";
			}
			if ( $count ) {
				if ( $type === 'order' && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
					$query = "SELECT count(*) from {$table_postmeta} join {$table_posts} on {$table_postmeta}.order_id={$table_posts}.id where {$table_postmeta}.meta_key = '{$meta_key}' and {$table_postmeta}.meta_value = %s";
				} else {
					$query = "SELECT count(*) from {$table_postmeta} join {$table_posts} on {$table_postmeta}.post_id={$table_posts}.ID where {$table_posts}.post_type = '{$post_type}' and {$table_posts}.post_status != 'trash' and {$table_postmeta}.meta_key = '{$meta_key}' and {$table_postmeta}.meta_value = %s";
				}
				$results = $wpdb->get_var( $wpdb->prepare( $query, $shopify_id ) );
			} else {
				if ( $type === 'order' && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
					$query = "SELECT {$table_postmeta}.* from {$table_postmeta} join {$table_posts} on {$table_postmeta}.order_id={$table_posts}.id where {$table_postmeta}.meta_key = '{$meta_key}' and {$table_postmeta}.meta_value = %s";
				} else {
					$query = "SELECT {$table_postmeta}.* from {$table_postmeta} join {$table_posts} on {$table_postmeta}.post_id={$table_posts}.ID where {$table_posts}.post_type = '{$post_type}' and {$table_posts}.post_status != 'trash' and {$table_postmeta}.meta_key = '{$meta_key}' and {$table_postmeta}.meta_value = %s";
				}
				if ( $multiple ) {
					$results = $wpdb->get_results( $wpdb->prepare( $query, $shopify_id ), ARRAY_A );
				} else {
					$results = $wpdb->get_var( $wpdb->prepare( $query, $shopify_id ), 1 );
				}
			}

			return $results;
		} else {
			return false;
		}
	}

	/**
	 * Find customer ID(s) by Shopify ID
	 *
	 * @param $shopify_id
	 * @param bool $count
	 * @param bool $multiple
	 * @param string $meta_key
	 *
	 * @return array|bool|object|string|null
	 */
	public static function customer_get_id_by_shopify_id( $shopify_id, $count = false, $multiple = false, $meta_key = '' ) {
		global $wpdb;
		if ( $shopify_id ) {
			$table      = "{$wpdb->prefix}users";
			$table_meta = "{$wpdb->prefix}usermeta";
			if ( ! $meta_key ) {
				$meta_key = '_s2w_shopify_customer_id';
			}
			if ( $count ) {
				$query   = "SELECT count(*) from {$table_meta} join {$table} on {$table_meta}.user_id={$table}.ID WHERE {$table_meta}.meta_key = '{$meta_key}' and {$table_meta}.meta_value = %s";
				$results = $wpdb->get_var( $wpdb->prepare( $query, $shopify_id ) );
			} else {
				$query = "SELECT {$table_meta}.* from {$table_meta} join {$table} on {$table_meta}.user_id={$table}.ID WHERE {$table_meta}.meta_key = '{$meta_key}' and {$table_meta}.meta_value = %s";
				if ( $multiple ) {
					$results = $wpdb->get_results( $wpdb->prepare( $query, $shopify_id ), ARRAY_A );
				} else {
					$results = $wpdb->get_var( $wpdb->prepare( $query, $shopify_id ), 1 );
				}
			}

			return $results;
		} else {
			return false;
		}
	}

	/**
	 * Find Woo product ID(s) by Shopify ID(WP_Query version)
	 *
	 * @param $shopify_id
	 * @param bool $is_variation
	 *
	 * @return int|string|WP_Post
	 */
	public static function get_woo_id_by_shopify_id( $shopify_id, $is_variation = false ) {
		$product_id = '';
		if ( $shopify_id ) {
			$args = array(
				'post_status'    => array( 'publish', 'pending', 'draft' ),
				'posts_per_page' => '1',
				'cache_results'  => false,
				'no_found_rows'  => true,
				'fields'         => 'ids',
			);
			if ( ! $is_variation ) {
				$args['post_type']  = 'product';
				$args['meta_query'] = array(
					'relation' => 'AND',
					array(
						'relation' => 'OR',
						array(
							'key'     => '_shopify_product_id',
							'value'   => $shopify_id,
							'compare' => '=',
						),
						array(
							'key'     => '_s2w_shopipy_product_id',
							'value'   => $shopify_id,
							'compare' => '=',
						),
					)
				);
			} else {
				$args['meta_key']   = '_shopify_variation_id';
				$args['meta_value'] = $shopify_id;
				$args['post_type']  = 'product_variation';
			}
			$the_query = new WP_Query( $args );

			if ( $the_query->have_posts() ) {
				$product_id = $the_query->posts[0];
			}
			wp_reset_postdata();
		}

		return $product_id;
	}

	/**
	 * @param $value
	 * @param string $key
	 *
	 * @return int|string|WP_Post
	 */
	/*public static function get_order_id_by_meta( $value, $key = '_s2w_shopify_order_id' ) {
		$order_id  = '';
		$args      = array(
			'post_type'      => 'shop_order',
			'post_status'    => array(
				'wc-pending',
				'wc-processing',
				'wc-on-hold',
				'wc-completed',
				'wc-cancelled',
				'wc-refunded',
				'wc-failed'
			),
			'meta_key'       => $key,
			'meta_value'     => $value,
			'posts_per_page' => 1,
			'cache_results'  => false,
			'no_found_rows'  => true,
			'fields'         => 'ids',
		);
		$the_query = new WP_Query( $args );
		if ( $the_query->have_posts() ) {
			$order_id = $the_query->posts[0];
		}
		wp_reset_postdata();

		return $order_id;
	}*/

	/**
	 * Delete log and cached files
	 *
	 * @param $files
	 */
	public static function delete_files( $files ) {
		if ( is_array( $files ) ) {
			if ( count( $files ) ) {
				foreach ( $files as $file ) { // iterate files
					if ( is_file( $file ) ) {
						unlink( $file );
					} // delete file
				}
			}
		} elseif ( is_file( $files ) ) {
			unlink( $files );
		}
	}

	/**
	 * Delete cache dir
	 *
	 * @param $dirPath
	 */
	public static function deleteDir( $dirPath ) {
		if ( is_dir( $dirPath ) ) {
			if ( substr( $dirPath, strlen( $dirPath ) - 1, 1 ) != '/' ) {
				$dirPath .= '/';
			}
			$files = glob( $dirPath . '*', GLOB_MARK );
			foreach ( $files as $file ) {
				if ( is_dir( $file ) ) {
					self::deleteDir( $file );
				} else {
					unlink( $file );
				}
			}
			rmdir( $dirPath );
		}
	}

	/**
	 * Create cache folder to store log and cache files
	 */
	protected static function create_plugin_cache_folder() {
		if ( ! is_dir( VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CACHE ) ) {
			wp_mkdir_p( VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CACHE );
			file_put_contents( VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CACHE . '.htaccess', '<IfModule !mod_authz_core.c>
Order deny,allow
Deny from all
</IfModule>
<IfModule mod_authz_core.c>
  <RequireAll>
    Require all denied
  </RequireAll>
</IfModule>
' );
		}
	}

	/**
	 * Make dir
	 *
	 * @param $path
	 */
	public static function create_cache_folder( $path ) {
		self::create_plugin_cache_folder();
		if ( ! is_dir( $path ) ) {
			wp_mkdir_p( $path );
		}
	}

	/**
	 * Get cache path based on API credentials of a Shopify store
	 *
	 * @param $domain
	 * @param $access_token
	 * @param $api_key
	 * @param $api_secret
	 *
	 * @return string
	 */
	public static function get_cache_path( $domain, $access_token, $api_key, $api_secret ) {
		if ( $access_token ) {
			return VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CACHE . md5( $access_token ) . '_' . md5( $domain ) . '_' . $domain;
		} else {
			return VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CACHE . md5( $api_key ) . '_' . md5( $api_secret ) . '_' . $domain;
		}
	}

	/**
	 * Join arguments when building requests
	 *
	 * @param $args
	 *
	 * @return mixed
	 */
	public static function implode_args( $args ) {
		foreach ( $args as $key => $value ) {
			if ( is_array( $value ) ) {
				$args[ $key ] = implode( ',', $value );
			}
		}

		return $args;
	}

	/**
	 * Get access scopes
	 *
	 * @param $domain
	 * @param $access_token
	 * @param $api_key
	 * @param $api_secret
	 *
	 * @return array
	 */
	public static function get_access_scopes( $domain, $access_token, $api_key, $api_secret ) {
		if ( $access_token ) {
			$url     = "https://{$domain}/admin/oauth/access_scopes.json";
			$headers = array( 'X-Shopify-Access-Token' => $access_token );
		} else {
			$url     = "https://{$api_key}:{$api_secret}@{$domain}/admin/oauth/access_scopes.json";
			$headers = array( 'Authorization' => 'Basic ' . base64_encode( $api_key . ':' . $api_secret ) );
		}
		$request = wp_remote_get(
			$url, array(
				'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36',
				'timeout'    => 10,
				'headers'    => $headers,
			)
		);
		$return  = array(
			'status' => 'error',
			'data'   => '',
			'code'   => '',
		);
		if ( ! is_wp_error( $request ) ) {
			if ( isset( $request['response']['code'] ) ) {
				$return['code'] = $request['response']['code'];
			}
			$body = vi_s2w_json_decode( $request['body'] );
			if ( isset( $body['errors'] ) ) {
				$return['data'] = $body['errors'];
			} else {
				$return['status'] = 'success';
				$return['data']   = $body['access_scopes'];
			}
		} else {
			$return['data'] = $request->get_error_message();
			$return['code'] = $request->get_error_code();
		}

		return $return;
	}

	/**
	 * wp_remote_get wrapper function to make GET requests
	 *
	 * @param $domain
	 * @param $access_token
	 * @param $api_key
	 * @param $api_secret
	 * @param string $type
	 * @param bool $count
	 * @param array $original_args
	 * @param int $timeout
	 * @param bool $return_pagination_link
	 * @param string $version
	 *
	 * @return array
	 */
	public static function wp_remote_get( $domain, $access_token, $api_key, $api_secret, $type = 'products', $count = false, $original_args = array(), $timeout = 300, $return_pagination_link = false, $version = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_REST_ADMIN_VERSION ) {
		$args = self::implode_args( wp_parse_args( $original_args, array( 'limit' => 250 ) ) );
		if ( $access_token ) {
			$url     = "https://{$domain}/admin";
			$headers = array(
				'X-Shopify-Access-Token' => $access_token,
			);
		} else {
			$url     = "https://{$api_key}:{$api_secret}@{$domain}/admin";
			$headers = array(
				'Authorization' => 'Basic ' . base64_encode( $api_key . ':' . $api_secret ),
			);
		}
		if ( $version ) {
			$url .= "/api/{$version}";
		}
		if ( $count ) {
			$url .= "/{$type}/count.json";
		} else {
			switch ( $type ) {
				case 'discount_codes':
					$price_rule_id = '';
					if ( ! empty( $original_args['ids'] ) ) {
						if ( is_array( $original_args['ids'] ) ) {
							$price_rule_id = $original_args['ids'][0];
						} else {
							$price_rule_id = $original_args['ids'];
						}
						unset( $args['ids'] );
						unset( $original_args['ids'] );
					}
					$url .= "/price_rules/{$price_rule_id}/{$type}.json";
					break;
				case 'price_rules':
					if ( ! empty( $original_args['ids'] ) ) {
						if ( is_array( $original_args['ids'] ) ) {
							$price_rule_id = $original_args['ids'][0];
						} else {
							$price_rule_id = $original_args['ids'];
						}
						unset( $args['ids'] );
						unset( $original_args['ids'] );
						$url  .= "/{$type}/$price_rule_id.json";
						$type = 'price_rule';
					} else {
						$url .= "/{$type}.json";
					}

					break;
				case 'orders':
					/*GraphQL for get order transaction*/
					if ( isset( $original_args['transaction'] ) && $original_args['transaction'] ) {
						if ( is_array( $original_args['ids'] ) ) {
							$shopify_order_ids = array_unique( $original_args['ids'] );
						} else {
							$shopify_order_ids = [ $original_args['ids'] ];
						}


						$url           = "https://{$domain}/admin/api/2024-01/graphql.json";
						$type          = 'orders';
						$graphQL_query = 'query {';
						foreach ( $shopify_order_ids as $key => $shopify_order_id ) {
							$graphQL_query .= 'Order_' . $shopify_order_id . ':order(id:"gid://shopify/Order/' . $shopify_order_id . '") {transactions {createdAt}}';
						}
						$graphQL_query           .= '}';
						$headers['Content-Type'] = 'application/json';

						$return = array(
							'status' => 'error',
							'data'   => '',
							'code'   => '',
						);
						try {
							$request = wp_remote_post(
								$url,
								array(
									'timeout' => $timeout,
									'headers' => $headers,
									'body'    => wp_json_encode( [
										'query' => $graphQL_query
									] )
								)
							);
							$body    = wp_remote_retrieve_body( $request );
							$body    = json_decode( $body );

							$return['status'] = 'success';
							$return['data']   = $body->data ?? [];
						} catch ( \Exception $e ) {
							$return['code'] = $e->getCode();
							$return['data'] = $e->getMessage();
						}

						return $return;
					} else {
						$url .= "/{$type}.json";
					}

					break;
				default:
					$url  .= "/{$type}.json";
					$type = explode( '/', $type )[0];
			}
		}
		$url     = add_query_arg( $args, $url );
		$request = wp_remote_get(
			$url, array(
				'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36',
				'timeout'    => $timeout,
				'headers'    => $headers,
			)
		);

		$return = array(
			'status' => 'error',
			'data'   => '',
			'code'   => '',
		);
		if ( ! is_wp_error( $request ) ) {
			if ( isset( $request['response']['code'] ) ) {
				$return['code'] = $request['response']['code'];
			}
			if ( $return_pagination_link ) {
				$return['pagination_link'] = self::get_pagination_link( $request );
			}
			$body = vi_s2w_json_decode( $request['body'] );
			if ( isset( $body['errors'] ) ) {
				$return['data'] = $body['errors'];
			} else {
				$return['status'] = 'success';
				if ( $count ) {
					$return['data'] = absint( $body['count'] );
				} else {
					if ( ! empty( $original_args['ids'] ) && ! is_array( $original_args['ids'] ) ) {
						$return['data'] = isset( $body[ $type ][0] ) ? $body[ $type ][0] : array();
					} else {
						$return['data'] = $body[ $type ];
					}
				}
			}
		} else {
			$return['data'] = $request->get_error_message();
			$return['code'] = $request->get_error_code();
		}

		return $return;
	}

	/**
	 * Get metafields
	 *
	 * @param $domain
	 * @param $access_token
	 * @param $api_key
	 * @param $api_secret
	 * @param $id
	 * @param string $type
	 * @param bool $count
	 * @param array $original_args
	 * @param int $timeout
	 * @param bool $return_pagination_link
	 * @param string $version
	 *
	 * @return array
	 */
	public static function wp_remote_get_metafields( $domain, $access_token, $api_key, $api_secret, $id, $type = 'products', $count = false, $original_args = array(), $timeout = 300, $return_pagination_link = false, $version = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_REST_ADMIN_VERSION ) {
		$args = self::implode_args( wp_parse_args( $original_args, array( 'limit' => 250 ) ) );
		if ( $access_token ) {
			$url     = "https://{$domain}/admin";
			$headers = array(
				'X-Shopify-Access-Token' => $access_token,
			);
		} else {
			$url     = "https://{$api_key}:{$api_secret}@{$domain}/admin";
			$headers = array(
				'Authorization' => 'Basic ' . base64_encode( $api_key . ':' . $api_secret ),
			);
		}
		if ( $version ) {
			$url .= "/api/{$version}";
		}
		if ( $type ) {
			if ( $type === 'variants' ) {
				$ids        = explode( ',', $id );
				$product_id = $ids[0];
				$variant_id = isset( $ids[1] ) ? $ids[1] : '';
				if ( $count ) {
					$url .= "/products/{$product_id}/{$type}/{$variant_id}/metafields/count.json";
				} else {
					$url .= "/products/{$product_id}/{$type}/{$variant_id}/metafields.json";
				}
			} else {
				if ( $count ) {
					$url .= "/{$type}/{$id}/metafields/count.json";
				} else {
					$url .= "/{$type}/{$id}/metafields.json";
				}
			}
		} else {
			if ( $count ) {
				$url .= "/metafields/count.json";
			} else {
				$url .= "/metafields.json";
			}
		}
		$url     = add_query_arg( $args, $url );
		$request = wp_remote_get(
			$url, array(
				'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36',
				'timeout'    => $timeout,
				'headers'    => $headers,
			)
		);
		$return  = array(
			'status' => 'error',
			'data'   => '',
			'code'   => '',
		);
		if ( ! is_wp_error( $request ) ) {
			if ( isset( $request['response']['code'] ) ) {
				$return['code'] = $request['response']['code'];
			}
			if ( $return_pagination_link ) {
				$return['pagination_link'] = self::get_pagination_link( $request );
			}
			$body = vi_s2w_json_decode( $request['body'] );
			if ( isset( $body['errors'] ) ) {
				$return['data'] = $body['errors'];
			} else {
				$return['status'] = 'success';
				if ( $count ) {
					$return['data'] = absint( $body['count'] );
				} else {
					$return['data'] = $body['metafields'];
				}
			}
		} else {
			$return['data'] = $request->get_error_message();
			$return['code'] = $request->get_error_code();
		}

		return $return;
	}

	/**
	 * Get articles(Posts)
	 *
	 * @param $domain
	 * @param $access_token
	 * @param $api_key
	 * @param $api_secret
	 * @param $blog_id
	 * @param bool $count
	 * @param array $original_args
	 * @param int $timeout
	 * @param bool $return_pagination_link
	 * @param string $version
	 *
	 * @return array
	 */
	public static function wp_remote_get_articles( $domain, $access_token, $api_key, $api_secret, $blog_id, $count = false, $original_args = array(), $timeout = 300, $return_pagination_link = false, $version = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_REST_ADMIN_VERSION ) {
		$args = self::implode_args( wp_parse_args( $original_args, array( 'limit' => 250 ) ) );
		if ( $access_token ) {
			$url     = "https://{$domain}/admin";
			$headers = array(
				'X-Shopify-Access-Token' => $access_token,
			);
		} else {
			$url     = "https://{$api_key}:{$api_secret}@{$domain}/admin";
			$headers = array(
				'Authorization' => 'Basic ' . base64_encode( $api_key . ':' . $api_secret ),
			);
		}
		$single_article = false;
		if ( $version ) {
			$url .= "/api/{$version}";
		}
		if ( $count ) {
			$url .= "/blogs/{$blog_id}/articles/count.json";
		} else {
			if ( ! empty( $original_args['ids'] ) && ( ! is_array( $original_args['ids'] ) || count( $original_args['ids'] ) === 1 ) ) {
				$url .= "/blogs/{$blog_id}/articles/{$original_args['ids']}.json";
				unset( $args['ids'] );
				$single_article = true;
			} else {
				$url .= "/blogs/{$blog_id}/articles.json";
			}
		}
		$url = add_query_arg( $args, $url );

		$request = wp_remote_get(
			$url, array(
				'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36',
				'timeout'    => $timeout,
				'headers'    => $headers,
			)
		);
		$return  = array(
			'status' => 'error',
			'data'   => '',
			'code'   => isset( $request['response']['code'] ) ? $request['response']['code'] : '',
		);
		if ( ! is_wp_error( $request ) ) {
			if ( $return_pagination_link ) {
				$return['pagination_link'] = self::get_pagination_link( $request );
			}
			$body = vi_s2w_json_decode( $request['body'] );
			if ( isset( $body['errors'] ) ) {
				$return['data'] = $body['errors'];
			} else {
				$return['status'] = 'success';
				if ( $count ) {
					$return['data'] = absint( $body['count'] );
				} else {
					if ( $single_article ) {
						$return['data'] = isset( $body['article'] ) ? $body['article'] : array();
					} else {
						$return['data'] = $body['articles'];
					}
				}
			}
		} else {
			$return['data'] = $request->get_error_message();
		}

		return $return;
	}

	/**
	 * Find the respective pagination link from page number
	 *
	 * @param $page_number
	 * @param $domain
	 * @param $access_token
	 * @param $api_key
	 * @param $api_secret
	 * @param string $type
	 * @param array $args
	 *
	 * @return array|bool|mixed
	 */
	public static function get_pagination_link_by_page_number( $page_number, $domain, $access_token, $api_key, $api_secret, $type = 'products', $args = array() ) {
		$args           = wp_parse_args( $args, array( 'limit' => 250 ) );
		$args['fields'] = 'id';
		$response       = self::wp_remote_get( $domain, $access_token, $api_key, $api_secret, $type, true, $args );
		if ( $response['status'] === 'success' ) {
			$pagination_link = array(
				'previous' => '',
				'next'     => '',
			);
			$count           = $response['data'];
			$limit           = intval( $args['limit'] );
			$total_pages     = ceil( $count / $limit );
			if ( $page_number <= $total_pages ) {
				$new_args = array( 'fields' => 'id', 'limit' => $args['limit'] );
				for ( $i = 0; $i < $page_number; $i ++ ) {
					$response = self::wp_remote_get( $domain, $access_token, $api_key, $api_secret, $type, false, $new_args, 300, true );
					if ( $response['status'] === 'success' ) {
						$pagination_link = $response['pagination_link'];
						if ( $response['pagination_link']['next'] ) {
							$new_args['page_info'] = $response['pagination_link']['next'];
						}
					} else {
						return false;
					}
				}
			}

			return $pagination_link;
		} else {
			return false;
		}
	}

	/**
	 * Get pagination link info from response header
	 *
	 * @param $request
	 *
	 * @return mixed|string
	 */
	public static function get_pagination_link( $request ) {
		$link      = wp_remote_retrieve_header( $request, 'link' );
		$page_link = array( 'previous' => '', 'next' => '' );
		if ( $link ) {
			$links = explode( ',', $link );
			foreach ( $links as $url ) {
				$params = wp_parse_url( $url );
				parse_str( $params['query'], $query );
				if ( ! empty( $query['page_info'] ) ) {
					$query_params = explode( '>;', $query['page_info'] );
					if ( trim( $query_params[1] ) === 'rel="next"' ) {
						$page_link['next'] = $query_params[0];
					} else {
						$page_link['previous'] = $query_params[0];
					}
				}
			}
		}

		return $page_link;
	}

	/**
	 * Sanitize taxonomy name when importing product attributes
	 *
	 * @param $name
	 *
	 * @return string
	 */
	public static function sanitize_taxonomy_name( $name ) {
		return strtolower( urlencode( wc_sanitize_taxonomy_name( $name ) ) );
	}

	/**
	 * Import images
	 * Make sure an image is not imported twice by checking for existence of image ID
	 *
	 * @param $shopify_id
	 * @param $url
	 * @param int $post_parent
	 * @param array $exclude
	 * @param string $post_title
	 * @param null $desc
	 *
	 * @return array|bool|int|object|string|WP_Error|null
	 */
	public static function download_image( &$shopify_id, $url, $post_parent = 0, $exclude = array(), $post_title = '', $desc = null ) {
		global $wpdb;
		if ( self::use_external_image() ) {
			$external_image = EXMAGE_WP_IMAGE_LINKS::add_image( $url, $image_id, $post_parent );
			$thumb_id       = $external_image['id'] ? $external_image['id'] : new WP_Error( 'exmage_image_error', $external_image['message'] );
		} else {
			$new_url   = $url;
			$parse_url = wp_parse_url( $new_url );
			$scheme    = empty( $parse_url['scheme'] ) ? 'http' : $parse_url['scheme'];
			$image_id  = "{$parse_url['host']}{$parse_url['path']}";
			$new_url   = "{$scheme}://{$image_id}";
			$reg       = self::get_image_file_extension_reg();
			preg_match( $reg, $new_url, $matches );
			if ( ! is_array( $matches ) || ! count( $matches ) ) {
				preg_match( $reg, $url, $matches );
				if ( is_array( $matches ) && count( $matches ) ) {
					$new_url  .= "?{$matches[0]}";
					$image_id .= "?{$matches[0]}";
				}
			}
			if ( ! $shopify_id ) {
				$shopify_id = $image_id;
			}

			$thumb_id = self::query_get_id_by_shopify_id( $shopify_id, 'image' );
			if ( ! $thumb_id ) {
				$thumb_id = s2w_upload_image( $new_url, $post_parent, $exclude, $post_title, $desc );
			} elseif ( $post_parent ) {
				$table_postmeta = "{$wpdb->prefix}posts";
				$wpdb->query( $wpdb->prepare( "UPDATE {$table_postmeta} set post_parent=%s WHERE ID=%s AND post_parent = 0 LIMIT 1", array(
					$post_parent,
					$thumb_id
				) ) );
			}
		}

		return $thumb_id;
	}

	/**
	 * Check if EXMAGE plugin is active and use_external_image option is ON
	 *
	 * @return bool
	 */
	public static function use_external_image() {
		$instance = self::get_instance();

		return ( $instance->get_params( 'use_external_image' ) && $instance->get_params( 'download_images' ) && class_exists( 'EXMAGE_WP_IMAGE_LINKS' ) );
	}

	/**
	 * Regex for currently supported image file extensions
	 *
	 * @return string
	 */
	public static function get_image_file_extension_reg() {
		return '/[^\?]+\.(jpg|JPG|jpeg|JPEG|jpe|JPE|gif|GIF|png|PNG|webp|WEBP|heic|HEIC)/';
	}

	/**
	 * @return array
	 */
	public static function get_functionality() {
		return array(
			'change_settings' => esc_html__( 'Change the plugin\'s main settings and import data via API', 's2w-import-shopify-to-woocommerce' ),
			'import_csv'      => esc_html__( 'Import CSV', 's2w-import-shopify-to-woocommerce' ),
			'cron_products'   => esc_html__( 'Change Cron products sync', 's2w-import-shopify-to-woocommerce' ),
			'cron_orders'     => esc_html__( 'Change Cron orders sync', 's2w-import-shopify-to-woocommerce' ),
			'webhooks'        => esc_html__( 'Change webhooks settings', 's2w-import-shopify-to-woocommerce' ),
			'import_by_id'    => esc_html__( 'Import by ID', 's2w-import-shopify-to-woocommerce' ),
			'failed_images'   => esc_html__( 'Failed Images', 's2w-import-shopify-to-woocommerce' ),
			'access_logs'     => esc_html__( 'Logs', 's2w-import-shopify-to-woocommerce' ),
		);
	}

	/**
	 * Get capability to access a specific functionality
	 *
	 * @param $functionality
	 *
	 * @return string
	 */
	public static function get_required_capability( $functionality ) {
		$instance     = self::get_instance();
		$capabilities = $instance->get_params( 'capabilities' );
		$capability   = isset( $capabilities[ $functionality ] ) ? $capabilities[ $functionality ] : '';
		if ( ! in_array( $capability, self::get_capabilities( $functionality ) ) ) {
			return 'manage_options';
		}

		return $capability;
	}

	/**
	 * All capabilities that may have access to this plugin
	 *
	 * @param $functionality
	 *
	 * @return mixed|void
	 */
	public static function get_capabilities( $functionality ) {
		return apply_filters( 's2w_get_capabilities', array(
			'manage_options',
			'manage_woocommerce'
		), $functionality );
	}

	/**
	 * All Shopify product statuses
	 *
	 * @return array
	 */
	public static function get_shopify_product_statuses() {
		return array(
			'active'   => esc_html_x( 'Active', 'shopify_product_status', 's2w-import-shopify-to-woocommerce' ),
			'archived' => esc_html_x( 'Archived', 'shopify_product_status', 's2w-import-shopify-to-woocommerce' ),
			'draft'    => esc_html_x( 'Draft', 'shopify_product_status', 's2w-import-shopify-to-woocommerce' ),
		);
	}

	/**
	 * @param $content
	 *
	 * @return string
	 */
	public static function wp_kses_post( $content ) {
		if ( self::$allow_html === null ) {
			self::$allow_html = wp_kses_allowed_html( 'post' );
			self::$allow_html = array_merge_recursive( self::$allow_html, array(
					'input'  => array(
						'type'         => 1,
						'id'           => 1,
						'name'         => 1,
						'class'        => 1,
						'placeholder'  => 1,
						'autocomplete' => 1,
						'style'        => 1,
						'value'        => 1,
						'size'         => 1,
						'checked'      => 1,
						'disabled'     => 1,
						'readonly'     => 1,
						'data-*'       => 1,
					),
					'form'   => array(
						'method' => 1,
						'id'     => 1,
						'class'  => 1,
						'action' => 1,
						'data-*' => 1,
					),
					'select' => array(
						'id'       => 1,
						'name'     => 1,
						'class'    => 1,
						'multiple' => 1,
						'data-*'   => 1,
					),
					'option' => array(
						'value'    => 1,
						'selected' => 1,
						'data-*'   => 1,
					),
				)
			);
			foreach ( self::$allow_html as $key => $value ) {
				if ( $key === 'input' ) {
					self::$allow_html[ $key ]['data-*']   = 1;
					self::$allow_html[ $key ]['checked']  = 1;
					self::$allow_html[ $key ]['disabled'] = 1;
					self::$allow_html[ $key ]['readonly'] = 1;
				} elseif ( in_array( $key, array( 'div', 'span', 'a', 'form', 'select', 'option', 'tr', 'td' ) ) ) {
					self::$allow_html[ $key ]['data-*'] = 1;
				}
			}
		}

		return wp_kses( $content, self::$allow_html );
	}
}