<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log' ) ) {
	class S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Log {

		public function __construct() {
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 20 );
			add_action( 'admin_menu', array( $this, 'admin_menu' ), 20 );
			add_action( 'wp_ajax_s2w_view_log', array( $this, 'generate_log_ajax' ) );
		}

		public function admin_enqueue_scripts( $page ) {
			if ( $page === 'shopify-to-woo_page_s2w-import-shopify-to-woocommerce-logs' ) {
				S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE::enqueue_3rd_library( array( 'segment', 'message' ) );
			}
		}

		/**
		 * Print log content
		 */
		public function generate_log_ajax() {
			/*Check the nonce*/
			if ( ! current_user_can( self::get_required_capability() ) || empty( $_GET['action'] ) || ! check_admin_referer( $_GET['action'] ) ) {
				wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 's2w-import-shopify-to-woocommerce' ) );
			}
			if ( empty( $_GET['s2w_file'] ) ) {
				wp_die( esc_html__( 'No log file selected.', 's2w-import-shopify-to-woocommerce' ) );
			}
			$file = urldecode( sanitize_text_field( $_GET['s2w_file'] ) );
			if ( substr( $file, - 4 ) === '.log' ) {
				if ( ! class_exists( 'WC_Log_Handler_File' ) ) {
					wp_die( esc_html__( 'Log file not found.', 's2w-import-shopify-to-woocommerce' ) );
				}
				$files = WC_Log_Handler_File::get_log_files();
				if ( ! count( $files ) || ! in_array( $file, $files ) ) {
					wp_die( esc_html__( 'Log file not found.', 's2w-import-shopify-to-woocommerce' ) );
				}
				$file = WC_LOG_DIR . $file;
			} else {
				if ( ! is_file( $file ) ) {
					wp_die( esc_html__( 'Log file not found.', 's2w-import-shopify-to-woocommerce' ) );
				}
				if ( ! in_array( basename( $file, '.txt' ), $this->log_files() ) ) {
					wp_die( esc_html__( 'Not supported', 's2w-import-shopify-to-woocommerce' ) );
				}
			}
			echo wp_kses_post( nl2br( file_get_contents( $file ) ) );
			exit();
		}

		public function admin_menu() {
			add_submenu_page(
				's2w-import-shopify-to-woocommerce',
				esc_html__( 'Logs', 's2w-import-shopify-to-woocommerce' ),
				esc_html__( 'Logs', 's2w-import-shopify-to-woocommerce' ),
				self::get_required_capability(),
				's2w-import-shopify-to-woocommerce-logs',
				array( $this, 'page_callback_logs' )
			);
		}

		/**
		 *
		 */
		public function page_callback_logs() {
			?>
            <div class="wrap">
                <h2><?php esc_html_e( 'Import Shopify to WooCommerce log files', 's2w-import-shopify-to-woocommerce' ); ?></h2>
				<?php
				$folders = glob( VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CACHE . '*', GLOB_ONLYDIR );
				if ( $folders ) {
					foreach ( $folders as $folder ) {
						$files = array();
						foreach ( $this->log_files() as $file_name ) {
							$log_file = $folder . "/{$file_name}.txt";
							if ( is_file( $log_file ) ) {
								$files[] = $log_file;
							}
						}
						if ( $files ) {
							?>
                            <div class="vi-ui message">
                                <div class="header"><?php echo esc_html( trailingslashit( $folder ) ) ?></div>
                                <ul class="list">
									<?php
									$this->print_log_html( $files );
									?>
                                </ul>
                            </div>
							<?php
						}
					}
				}
				if ( class_exists( 'WC_Log_Handler_File' ) ) {
					$files = WC_Log_Handler_File::get_log_files();
					if ( count( $files ) ) {
						foreach ( $files as $key => $value ) {
							if ( substr( $key, 0, 4 ) !== 's2w-' ) {
								unset( $files[ $key ] );
							}
						}
					}
					if ( $files ) {
						?>
                        <div class="vi-ui message">
                            <div class="header"><?php printf( esc_html__( 'Import CSV and Error logs(below files will be automatically deleted by WooCommerce after %s days from the day they were created)', 'woocommerce-alidropship' ), apply_filters( 'woocommerce_logger_days_to_retain_logs', 30 ) ) ?></div>
                            <ul class="list">
								<?php
								$this->print_log_html( $files );
								?>
                            </ul>
                        </div>
						<?php
					}
				}
				?>
            </div>
			<?php
		}

		/**
		 * @return array
		 */
		public function log_files() {
			return array(
				'logs',
				'import_by_id_logs',
				'cron_update_products_logs',
				'cron_update_orders_logs',
				'webhooks_logs',
				'debug'
			);
		}

		/**
		 * List of log files and View link
		 *
		 * @param $logs
		 */
		public function print_log_html( $logs ) {
			if ( is_array( $logs ) && count( $logs ) ) {
				foreach ( $logs as $log ) {
					?>
                    <li><?php echo esc_html( basename( $log ) ) ?>
                        <a target="_blank" rel="nofollow"
                           href="<?php echo esc_url( add_query_arg( array(
							   'action'   => 's2w_view_log',
							   's2w_file' => urlencode( $log ),
							   '_wpnonce' => wp_create_nonce( 's2w_view_log' ),
						   ), admin_url( 'admin-ajax.php' ) ) ) ?>"><?php esc_html_e( 'View', 's2w-import-shopify-to-woocommerce' ) ?>
                        </a>
                    </li>
					<?php
				}
			}
		}

		/**
		 * Required capability
		 *
		 * @return mixed|void
		 */
		private static function get_required_capability() {
			return apply_filters( 'vi_s2w_admin_sub_menu_capability', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_required_capability( 'access_logs' ), 's2w-import-shopify-to-woocommerce-logs' );
		}

		/**
		 * Take use of WooCommerce logger
		 *
		 * @param $content
		 * @param string $source
		 * @param string $level
		 */
		public static function wc_log( $content, $source = 'errors', $level = 'error' ) {
			$content = strip_tags( $content );
			$log     = wc_get_logger();
			$log->log( $level,
				$content,
				array(
					'source' => 's2w-' . $source,
				)
			);
		}

		/**
		 * Log error, data
		 *
		 * @param $log_file
		 * @param $logs_content
		 */
		public static function log( $log_file, $logs_content ) {
			$logs_content = PHP_EOL . "[" . date( "Y-m-d H:i:s" ) . "] " . $logs_content;
			if ( is_file( $log_file ) ) {
				file_put_contents( $log_file, $logs_content, FILE_APPEND );
			} else {
				file_put_contents( $log_file, $logs_content );
			}
		}
	}
}