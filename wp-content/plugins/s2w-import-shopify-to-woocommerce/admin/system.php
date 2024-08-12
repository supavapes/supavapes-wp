<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_System' ) ) {
	class S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_System {

		public function __construct() {
			add_action( 'admin_menu', array( $this, 'admin_menu' ), 25 );
		}

		public function admin_menu() {
			$menu_slug = 's2w-import-shopify-to-woocommerce-status';
			add_submenu_page(
				's2w-import-shopify-to-woocommerce',
				esc_html__( 'System Status', 's2w-import-shopify-to-woocommerce' ),
				esc_html__( 'System Status', 's2w-import-shopify-to-woocommerce' ),
				apply_filters( 'vi_s2w_admin_sub_menu_capability', 'manage_options', $menu_slug ),
				$menu_slug,
				array( $this, 'page_callback_system_status' )
			);
		}

		public function page_callback_system_status() {
			?>
            <h2><?php esc_html_e( 'System Status', 's2w-import-shopify-to-woocommerce' ) ?></h2>
            <table cellspacing="0" id="status" class="widefat">
                <thead>
                <tr>
                    <th><?php esc_html_e( 'Option name', 's2w-import-shopify-to-woocommerce' ) ?></th>
                    <th><?php esc_html_e( 'Your option value', 's2w-import-shopify-to-woocommerce' ) ?></th>
                    <th><?php esc_html_e( 'Minimum recommended value', 's2w-import-shopify-to-woocommerce' ) ?></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td data-export-label="file_get_contents">file_get_contents</td>
                    <td>
						<?php
						if ( function_exists( 'file_get_contents' ) ) {
							?>
                            <mark class="yes">&#10004; <code class="private"></code></mark>
							<?php
						} else {
							?>
                            <mark class="error">&#10005;</mark>'
							<?php
						}
						?>
                    </td>
                    <td><?php esc_html_e( 'Required', 's2w-import-shopify-to-woocommerce' ) ?></td>
                </tr>
                <tr>
                    <td data-export-label="file_put_contents">file_put_contents</td>
                    <td>
						<?php
						if ( function_exists( 'file_put_contents' ) ) {
							?>
                            <mark class="yes">&#10004; <code class="private"></code></mark>
							<?php
						} else {
							?>
                            <mark class="error">&#10005;</mark>
							<?php
						}
						?>

                    </td>
                    <td><?php esc_html_e( 'Required', 's2w-import-shopify-to-woocommerce' ) ?></td>
                </tr>
                <tr>
                    <td data-export-label="mkdir">mkdir</td>
                    <td>
						<?php
						if ( function_exists( 'mkdir' ) ) {
							?>
                            <mark class="yes">&#10004; <code class="private"></code></mark>
							<?php
						} else {
							?>
                            <mark class="error">&#10005;</mark>
							<?php
						}
						?>

                    </td>
                    <td><?php esc_html_e( 'Required', 's2w-import-shopify-to-woocommerce' ) ?></td>
                </tr>
                <tr>
                    <td data-export-label="<?php esc_html_e( 'Log Directory Writable', 's2w-import-shopify-to-woocommerce' ) ?>"><?php esc_html_e( 'Log Directory Writable', 's2w-import-shopify-to-woocommerce' ) ?></td>
                    <td>
						<?php

						if ( wp_is_writable( VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CACHE ) ) {
							echo '<mark class="yes">&#10004; <code class="private">' . VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CACHE . '</code></mark> ';
						} else {
							printf( '<mark class="error">&#10005; ' . __( 'To allow logging, make <code>%s</code> writable or define a custom <code>VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CACHE</code>.', 's2w-import-shopify-to-woocommerce' ) . '</mark>', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CACHE );
						}
						?>

                    </td>
                    <td><?php esc_html_e( 'Required', 's2w-import-shopify-to-woocommerce' ) ?></td>
                </tr>
				<?php
				$max_execution_time = ini_get( 'max_execution_time' );
				$max_input_vars     = ini_get( 'max_input_vars' );
				$memory_limit       = ini_get( 'memory_limit' );
				?>
                <tr>
                    <td data-export-label="<?php esc_attr_e( 'PHP Time Limit', 's2w-import-shopify-to-woocommerce' ) ?>"><?php esc_html_e( 'PHP Time Limit', 's2w-import-shopify-to-woocommerce' ) ?></td>
                    <td style="<?php if ( $max_execution_time > 0 && $max_execution_time < 300 ) {
						echo esc_attr( 'color:red' );
					} ?>"><?php echo esc_html( $max_execution_time ); ?></td>
                    <td><?php esc_html_e( '300', 's2w-import-shopify-to-woocommerce' ) ?></td>
                </tr>
                <tr>
                    <td data-export-label="<?php esc_attr_e( 'PHP Max Input Vars', 's2w-import-shopify-to-woocommerce' ) ?>"><?php esc_html_e( 'PHP Max Input Vars', 's2w-import-shopify-to-woocommerce' ) ?></td>

                    <td style="<?php if ( $max_input_vars < 1000 ) {
						echo esc_attr( 'color:red' );
					} ?>"><?php echo esc_html( $max_input_vars ); ?></td>
                    <td><?php esc_html_e( '1000', 's2w-import-shopify-to-woocommerce' ) ?></td>
                </tr>
                <tr>
                    <td data-export-label="<?php esc_attr_e( 'Memory Limit', 's2w-import-shopify-to-woocommerce' ) ?>"><?php esc_html_e( 'Memory Limit', 's2w-import-shopify-to-woocommerce' ) ?></td>

                    <td style="<?php if ( intval( $memory_limit ) < 64 ) {
						echo esc_attr( 'color:red' );
					} ?>"><?php echo esc_html( $memory_limit ); ?></td>
                    <td><?php esc_html_e( '64M', 's2w-import-shopify-to-woocommerce' ) ?></td>
                </tr>
                <tr>
                    <td data-export-label="<?php esc_attr_e( 'Socket timeout', 's2w-import-shopify-to-woocommerce' ) ?>"><?php esc_html_e( 'Socket timeout', 's2w-import-shopify-to-woocommerce' ) ?></td>

                    <td><?php echo ini_get( "default_socket_timeout" ); ?></td>
                    <td><?php esc_html_e( '', 's2w-import-shopify-to-woocommerce' ) ?></td>
                </tr>

                </tbody>
            </table>
			<?php
		}

	}
}
