<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://www.multidots.com/
 * @since      1.0.0
 *
 * @package    woocommerce-quick-cart-for-multiple-variations
 * @subpackage woocommerce-quick-cart-for-multiple-variations/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    woocommerce-quick-cart-for-multiple-variations
 * @subpackage woocommerce-quick-cart-for-multiple-variations/public
 * @author     Multidots <wordpress@multidots.com>
 */
class Variant_purchase_extended_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.0.0
	 */
	function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	function enqueue_styles_scripts() {

		global $post;
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		if ( is_cart() ) {
			wp_register_script( 'wqcmv-cart-page-js', plugin_dir_url( __FILE__ ) . 'js/cart-page-query' . $suffix . '.js', array( 'jquery' ), '1.2.0', true );
			wp_enqueue_script( 'wqcmv-cart-page-js' );
		} else {
			$product_id = get_the_ID();
			if ( empty( $product_id ) ) {
				$product_id = isset( $post->ID ) ? $post->ID : false;
			}
			if ( false === $product_id ) {
				return;
			}

			$product_data = wc_get_product( $product_id );
			if ( empty( $product_data ) || ! is_object( $product_data ) ) {
				return;
			}

			$product_description       = $product_data->get_description();
			$product_short_description = $product_data->get_short_description();
			$enqueue_style_script      = false;
			if ( ! empty( $product_data ) && 'variable' === $product_data->get_type() ) {
				$enqueue_style_script = true;
			} elseif ( ! empty( $product_data ) && 'simple' === $product_data->get_type() ) {
				$enqueue_style_script = true;
			} elseif ( ( ! empty( $product_description ) && has_shortcode( $product_description, 'vpe-woo-variable-product' ) ) ||
				( ! empty( $product_short_description ) && has_shortcode( $product_short_description, 'vpe-woo-variable-product' ) ) ) {
				$enqueue_style_script = true;
			} elseif ( is_shop() || is_product_category() ) {
				$enqueue_style_script = true;
			}

			if ( $enqueue_style_script ) {
				$theme = wp_get_theme();
				wp_enqueue_style( $this->plugin_name . '-variable-image-fancybox-css', plugin_dir_url( __FILE__ ) . 'css/jquery.fancybox.css', '', '3.5.7' );
				wp_enqueue_style( $this->plugin_name . '-font-awesome.min', plugin_dir_url( __FILE__ ) . 'css/font-awesome.min.css', '', '2.1.5' );
				$wqcmv_additional_css = get_option( 'wqcmv_additional_css' );

				/*
				 * Enqueue CSS based on activated theme.
				 */
				if ( 'Twenty Sixteen' === $theme->name || 'Twenty Sixteen' === $theme->parent_theme ) {
					wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/twenty-sixteen.css', array(), $this->version, 'all' );
					wp_add_inline_style( $this->plugin_name, $wqcmv_additional_css );
				} else if ( 'Twenty Seventeen' === $theme->name || 'Twenty Seventeen' === $theme->parent_theme ) {
					wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/twenty-seventeen.css', array(), $this->version, 'all' );
					wp_add_inline_style( $this->plugin_name, $wqcmv_additional_css );
				} else if ( 'Twenty Eighteen' === $theme->name || 'Twenty Eighteen' === $theme->parent_theme ) {
					wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/twenty-eighteen.css', array(), $this->version, 'all' );
					wp_add_inline_style( $this->plugin_name, $wqcmv_additional_css );
				} else if ( 'Twenty Nineteen' === $theme->name || 'Twenty Nineteen' === $theme->parent_theme ) {
					wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/twenty-nineteen.css', array(), $this->version, 'all' );
					wp_add_inline_style( $this->plugin_name, $wqcmv_additional_css );
				} else if ( 'Salient' === $theme->name || 'Salient' === $theme->parent_theme ) {
					wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/sallient.css', array(), $this->version, 'all' );
					wp_add_inline_style( $this->plugin_name, $wqcmv_additional_css );
				} else {
					wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/general.css', array(), $this->version, 'all' );
					wp_add_inline_style( $this->plugin_name, $wqcmv_additional_css );
				}

				//enqueue Javascripts
				wp_enqueue_script( 'variable-image-fancybox-js', plugin_dir_url( __FILE__ ) . 'js/jquery.fancybox.pack.js', array( 'jquery' ), '3.5.7', true );
				wp_enqueue_script( 
					$this->plugin_name, 
					plugin_dir_url( __FILE__ ) . 'js/variant_purchase_extended-public.js', 
					array( 'jquery' ), 
					filemtime( plugin_dir_path( __FILE__ ) . 'js/variant_purchase_extended-public.js' ),
					true 
				);
				wp_localize_script(
					$this->plugin_name,
					'WQCMVPublicJSObj',
					array(
						'ajaxurl'                 => admin_url( 'admin-ajax.php' ),
						'loader_url'              => includes_url( 'images/spinner-2x.gif' ),
						'fetch_products_wait_msg' => esc_html__( 'Please wait while the products are loading !!', 'woocommerce-quick-cart-for-multiple-variations' ),
						'wait_msg'                => esc_html__( 'Please wait...', 'woocommerce-quick-cart-for-multiple-variations' )
					)
				);
			}
		}
	}

    /**
	 * Function that locate the WooCommerce variable template.
	 *
	 * @param $template
	 * @param $template_name
	 * @param $template_path
	 *
	 * @return string
	 */
    function wqcmv_woocommerce_locate_template( $template, $template_name, $template_path ) {
    	$_template = $template;
    	if ( ! $template_path ) {
    		$template_path = WC()->template_url;
    	}
    	$plugin_path = plugin_dir_path( __FILE__ ) . 'woocommerce/';

		// Look within passed path within the theme - this is priority
    	$template = locate_template(
    		array(
    			$template_path . $template_name,
    			$template_name
    		)
    	);

    	if ( ! $template && file_exists( $plugin_path . $template_name ) ) {
    		$template = $plugin_path . $template_name;
    	}

    	if ( ! $template ) {
    		$template = $_template;
    	}

		// Return what we found
    	return $template;
    }

	/**
	 * Define the shortcode template.
	 *
	 * @param $atts
	 *
	 * @return false|string
	 */
	function wqcmv_shortcode_template( $atts ) {

		$product_id = ( isset( $atts['id'] ) && ! empty( $atts['id'] ) ) ? $atts['id'] : '';
		if ( '' !== $product_id ) {
			ob_start();
			$add_to_cart_button_text   = get_option( 'vpe_add_to_cart_button_text' );
			$variation_per_page_option = intval( get_option( 'vpe_variation_per_page' ) );
			$per_page                  = ! empty( $variation_per_page_option ) ? $variation_per_page_option : 10;

			$variations_data     = wqcmv_conditional_logic_variation( $product_id, $per_page, 1 );
			$variations          = $variations_data['variations'];
			if( empty( $variations ) )
				return;

			$pagination          = $variations_data['pagination'];
			// $wqcmv_table_headers = wqcmv_updated_headers();
			do_action( 'wqcmv_before_container' );
			?>
			<div class="vpe-variations-container reteteeete">
				<div class="vpe_table_responsive">
					<div class="cartSection">
						<?php do_action( 'wqcmv_table_before' ); ?>
						<?php
						$row_html     = '';
						// $allowed_html = wqcmv_wses_allowed_variation_html();
						foreach ( $variations as $variation_id ) {
							$row_html .= wqcmv_fetch_product_block_html( $variation_id, array() );
						}
						echo $row_html;
						?>
						<?php
						do_action( 'wqcmv_table_after' );
						$vpe_allow_users_to_contact_admin = get_option( 'vpe_allow_users_to_contact_admin' );
						$unavailable_variants_option      = get_option( 'vpe_allow_unavailable_variants' );
						$variations_data  = wqcmv_fetch_outofstcok_variation_ids( $product_id );
						$out_of_stock_prods = isset( $variations_data['variation_ids'] ) && !empty( $variations_data['variation_ids'] ) ? true : false;
						if ( 'yes' === $vpe_allow_users_to_contact_admin && 'yes' === $unavailable_variants_option && true === $out_of_stock_prods ) {

							$text_for_contact_admin_link = __( 'Click here to pre-order out of stock items', 'woocommerce-quick-cart-for-multiple-variations' );
							?>
							<!-- <a href="javascript:void(0);"
							id="vpe-contact-admin" data-product-id="<?php echo esc_attr( $product_id ); ?>">
								<?php echo esc_html( apply_filters( 'wqcmv_contact_admin_link_text', $text_for_contact_admin_link ) ); ?>
							</a> -->
							<?php
						} ?>
					</div>
				</div>
				<?php ?>
				<!-- PAGINATION OF Products -->
				<?php

				if ( 'yes' === $pagination ) {
					$previous_btn_text = esc_html__( 'Go Back', 'woocommerce-quick-cart-for-multiple-variations' );
					$next_btn_text     = esc_html__( 'View More Options', 'woocommerce-quick-cart-for-multiple-variations' );
					?>
					<div class="pagination-for-products">
						<button type="button" data-loadchunk="0"
						class="prev products-pagination vpe-normal-directory-paginate vpe-core-btn"
						disabled="disabled">
						<?php echo esc_html( apply_filters( 'wqcmv_previous_button_text', $previous_btn_text ) ); ?>
					</button>
					<button type="button" data-loadchunk="1"
					class="next products-pagination vpe-normal-directory-paginate vpe-core-btn"><?php echo esc_html( apply_filters( 'wqcmv_next_button_text', $next_btn_text ) ); ?>
				</button>
				<input type="hidden" id="vpe-active-chunk" value="1">
				<input type="hidden" id="vpe-next-chunk" value="1">
			</div>
			<?php
		} ?>
		<div class="vpe_container_btn">
			<button type="button"
			class="vpe_single_add_to_cart_button">
			<span><?php echo ! empty( $add_to_cart_button_text ) ? esc_html( $add_to_cart_button_text ) : esc_html__( 'Add to cart', 'woocommerce-quick-cart-for-multiple-variations' ); ?></span>
			<svg width="25" height="24" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M18.2949 17.882C16.7436 17.8806 15.4847 19.137 15.4832 20.6883C15.4817 22.2396 16.7381 23.4985 18.2895 23.5C19.8408 23.5015 21.0996 22.2451 21.1012 20.6937V20.691C21.0997 19.1413 19.8446 17.8851 18.2949 17.882ZM23.2771 4.43118C23.2099 4.41816 23.1416 4.41156 23.0731 4.41145H5.9701L5.69922 2.59928C5.53046 1.39579 4.50102 0.50037 3.28574 0.5H1.0835C0.485089 0.5 0 0.985089 0 1.5835C0 2.18191 0.485089 2.667 1.0835 2.667H3.28844C3.35499 2.66652 3.41939 2.69056 3.46935 2.73453C3.51931 2.7785 3.55133 2.83932 3.55931 2.90539L5.22789 14.3417C5.45665 15.7949 6.70665 16.867 8.17773 16.8717H19.4488C20.8652 16.8736 22.087 15.8781 22.3716 14.4907L24.135 5.7008C24.2487 5.11329 23.8646 4.54488 23.2771 4.43118ZM11.4136 20.5707C11.3477 19.0649 10.1049 17.8795 8.59759 17.8847C7.04752 17.9474 5.8417 19.2548 5.90434 20.8048C5.96444 22.2922 7.17433 23.4743 8.66261 23.5H8.73033C10.2802 23.4321 11.4815 22.1206 11.4136 20.5707Z" fill="#EC4E34"></path>
			</svg>
		</button>
		<!-- <a href="<?php //echo esc_url( wc_get_cart_url() ); ?>"
			class="vpe-view-cart"><?php //esc_html_e( 'View Cart', 'woocommerce-quick-cart-for-multiple-variations' ); ?>
		</a> -->
		</div>
		<div class="error-message-blk"></div>
		<div class="vpe-ajax-loader">
			<img src="<?php echo esc_url( includes_url( 'images/spinner-2x.gif' ) ); ?>" alt="spinner-2x">
			<p class="vpe-ajax-loader-message"></p>
			<input type="hidden" id="vpe-parent-product-id" value="<?php echo esc_attr( $product_id ); ?>"/>
		</div>
	</div>
	<?php
	do_action( 'wqcmv_after_container' );

	return ob_get_clean();
} else {
	esc_html_e( 'Product ID missing', 'woocommerce-quick-cart-for-multiple-variations' );
}

}

	/*
	 * Ajax Call For Get Variable Product As Quantity From Single Add to Cart Button.
	 */
	function wqcmv_woocommerce_ajax_add_to_cart() {

		$action = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		if ( isset( $action ) && 'wqcmv_woocommerce_ajax_add_to_cart' === $action ) {
			$filter_args = array(
				'variations' => array(
					'filter' => FILTER_SANITIZE_STRING,
					'flags'  => FILTER_FORCE_ARRAY,
				)
			);

			$filtered_array = filter_input_array( INPUT_POST, $filter_args );
			$variations     = isset( $filtered_array['variations'] ) && is_array( $filtered_array['variations'] ) ? $filtered_array['variations'] : array();
			$product_id     = filter_input( INPUT_POST, 'parent_product_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			foreach ( $variations as $variation ) {
				$vid = absint( $variation['vid'] );
				$qty = absint( $variation['qty'] );
				WC()->cart->add_to_cart( $product_id, $qty, $vid );
			}
			$cart_total            = WC()->cart->get_cart_total();
			$cart_count            = WC()->cart->get_cart_contents_count();
			$redirect_to_cart_page = get_option( 'woocommerce_cart_redirect_after_add' );
			$cart_url              = wc_get_page_permalink( 'cart' );
			$result                = array(
				'message'          => 'vpe-product-added-to-cart-prac',
				'cart_count'       => $cart_count,
				'redirect_to_cart' => $redirect_to_cart_page,
				'cart_url'         => $cart_url,
				'cart_total'       => $cart_total
			);
			wp_send_json_success( $result );
			wp_die();
		}

	}

	/*
		* Products Pagination Ajax Call.
	*/
		function wqcmv_products_pagination() {

			$action    = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			$loadchunk = filter_input( INPUT_POST, 'loadchunk', FILTER_SANITIZE_NUMBER_INT );
			if ( isset( $action ) && 'wqcmv_products_pagination' === $action ) {
				$product_id                = filter_input( INPUT_POST, 'parent_product_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
				$array_filter_args         = array(
					'changed_variations' => array(
						'flags'  => FILTER_REQUIRE_ARRAY,
						"filter" => FILTER_SANITIZE_STRING
					)
				);
				$filter_changed_variation  = filter_input_array( INPUT_POST, $array_filter_args );
				$changed_variations        = ( isset( $filter_changed_variation['changed_variations'] ) && ! empty( $filter_changed_variation['changed_variations'] ) ) ? $filter_changed_variation['changed_variations'] : array();
				$variation_per_page_option = ! empty( get_option( 'vpe_variation_per_page' ) ) ? intval( get_option( 'vpe_variation_per_page' ) ) : 10;
				$variations_data           = wqcmv_conditional_logic_variation( $product_id, $variation_per_page_option, $loadchunk );
				$variation_pids            = $variations_data['variations'];
				$pagination                = $variations_data['pagination'];
				$wqcmv_table_headers       = wqcmv_updated_headers();
				$html                      = '';
				if ( ! empty( $variation_pids ) && is_array( $variation_pids ) ) {
					foreach ( $variation_pids as $chunk_product_id ) {
						$html .= wqcmv_fetch_product_block_html( $chunk_product_id, $changed_variations, $wqcmv_table_headers );
					}
				}
				$result = array(
					'message'              => 'vpe-product-pagination',
					'html'                 => $html,
					'loadchunk'            => $loadchunk,
					'chunk_available'      => $pagination,
					'next_chunk_available' => $pagination,
					'prev_chunk_available' => isset( $loadchunk ) && 1 < intval( $loadchunk ) ? 'yes' : 'no'
				);
				wp_send_json_success( $result );
				wp_die();
			}

		}

	/**
	 * Filter added to add a class in the body.
	 *
	 * @param $classes
	 *
	 * @return array
	 */
	function wqcmv_body_classes( $classes ) {

		global $post;
		if ( has_shortcode( $post->post_content, 'vpe-woo-variable-product' ) ) {
			$classes[] = 'vpe-shortcode';
		}

		return $classes;

	}


	/**
	 * Modal for contacting admin when products get out of stock.
	 */
	function wqcmv_modal_html() {
		$contact_admin_file = plugin_dir_path( __FILE__ ) . 'partials/modal/modal-to-contact-admin-for-unavailable-products.php';
		if ( file_exists( $contact_admin_file ) ) {
			include_once( plugin_dir_path( __FILE__ ) . 'partials/modal/modal-to-contact-admin-for-unavailable-products.php' );
		}

	}

	/**
	 * show out of stock products in modal.
	 */
	function wqcmv_get_out_of_stock_products() {

		$action     = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$product_id = filter_input( INPUT_POST, 'product_id', FILTER_SANITIZE_NUMBER_INT );
		if ( ! isset( $product_id ) || empty( $product_id ) ) {
			return '';
		}

		if ( isset( $action ) && 'wqcmv_get_out_of_stock_products' === $action ) {
			$per_page         = apply_filters( 'wqcmv_admin_contact_form_variation_per_page', 5 );
			$variations_data  = wqcmv_fetch_outofstcok_variation_ids( $product_id, $per_page, 1 );
			$pagination       = isset( $variations_data['pagination'] ) ? $variations_data['pagination'] : 'no';
			$out_of_stock_ids = isset( $variations_data['variation_ids'] ) ? $variations_data['variation_ids'] : array();
			$modal_title      = esc_html__( 'Pre-Order Form', 'woocommerce-quick-cart-for-multiple-variations' );
			ob_start();
			?>
			<div class="wqcmv_product_table">
				<?php do_action( 'wcqcmv_before_contact_admin_modal_content', $product_id ); ?>
				<table class="wp-list-table widefat striped">
					<thead>
						<tr>
							<th scope="col"
							class="manage-column"><?php esc_html_e( 'SKU', 'woocommerce-quick-cart-for-multiple-variations' ); ?></th>
							<th scope="col" class="manage-column"><a
								href="javascript:void(0);"><span><?php esc_html_e( 'Product', 'woocommerce-quick-cart-for-multiple-variations' ); ?></span></a>
							</th>
							<th scope="col"
							class="manage-column"><?php esc_html_e( 'Quantity', 'woocommerce-quick-cart-for-multiple-variations' ); ?></th>
							<th scope="col"
							class="manage-column"><?php esc_html_e( 'Cost', 'woocommerce-quick-cart-for-multiple-variations' ); ?></th>
							<th scope="col" class="manage-column"><input type="checkbox" id="wqcmv-select-all-checkbox"
								name=""></th>
							</tr>
						</thead>
						<tbody id="the-list" class="wqcmv-recommended-prod-list">
							<?php
							foreach ( $out_of_stock_ids as $ids ) {
								$variation_all_data = wc_get_product( $ids );
								$variation_title    = $variation_all_data->get_formatted_name();
								$variation_title    = wp_strip_all_tags( $variation_title );
								$sku                = $variation_all_data->get_sku();
								if ( $variation_all_data->is_on_sale() ) {
									$price = $variation_all_data->get_price_html();
								} else {
									$price = wc_price( wc_get_price_to_display( $variation_all_data ) ) . $variation_all_data->get_price_suffix();
								}
								?>
								<tr class="product-out-of-stock" data-variation-id="<?php esc_html_e( $ids ); ?>">
									<td><span aria-hidden="true"><?php if ( ! empty( $sku ) ) {
										esc_html_e( $sku );
									} else {
										esc_html_e( '-' );
									} ?></span></td>
									<td class="has-row-actions">
										<span><?php esc_html_e( $variation_title, 'woocommerce-quick-cart-for-multiple-variations' ); ?></span>
									</td>
									<td><input type="number" class="wqcmv-prod-qty-<?php esc_html_e( $ids ); ?> test-qty"
										placeholder="0"
										min="1" value="1" onpaste="return false"></td>
										<td><?php echo wp_kses_post( $price ); ?></td>
										<td><input type="checkbox" id="wqcmv-out-of-stock-prods-<?php echo esc_attr( $ids ); ?>"
											class="wqcmv-out-of-stock-prods" name="out-of-stock-product"
											value="<?php esc_html_e( $ids ); ?>" readonly></td>
										</tr>
										<?php
									} ?>
								</tbody>
							</table>
							<?php do_action( 'wcqcmv_after_contact_admin_modal_variations', $product_id ); ?>
						</div>
						<?php if ( 'yes' === $pagination ) {
							$previous_btn_text = esc_html__( 'Previous', 'woocommerce-quick-cart-for-multiple-variations' );
							$next_btn_text     = esc_html__( 'Next', 'woocommerce-quick-cart-for-multiple-variations' );
							?>
							<div class="pagination-for-products-modal">
								<button type="button" data-loadchunk="0"
								class="prev modal-pagination vpe-normal-directory-paginate vpe-core-btn"
								disabled="disabled">
								<?php echo esc_html( apply_filters( 'wqcmv_previous_button_text', $previous_btn_text ) ); ?>
							</button>
							<button type="button" data-loadchunk="1"
							class="next modal-pagination vpe-normal-directory-paginate vpe-core-btn"><?php echo esc_html( apply_filters( 'wqcmv_next_button_text', $next_btn_text ) ); ?>
						</button>
						<input type="hidden" id="modal-pagination-chunk" name="modal-pagination-chunk"
						data-product-id="<?php echo esc_attr( $product_id ); ?>" value="1">
					</div>
					<?php do_action( 'wcqcmv_after_contact_admin_modal_pagination', $product_id ); ?>
					<?php
				} ?>
				<div class="wqcmv_product_form">
					<div class="wqcmv_product_fields">
						<div class="form-control control-col-6">
							<input type="text" name="wqcmv_name" id="wqcmv_name"
							placeholder="<?php esc_html_e( 'Enter Your Name', 'woocommerce-quick-cart-for-multiple-variations' ) ?>"
							required>
						</div>
						<div class="form-control control-col-6">
							<input type="email" name="wqcmv_email"
							placeholder="<?php esc_html_e( 'Enter Your Email', 'woocommerce-quick-cart-for-multiple-variations' ) ?>"
							required>
						</div>
						<div class="form-control">
							<textarea rows="5" cols="5" name="wqcmv_message"
							placeholder="<?php esc_html_e( 'Enter Your Message', 'woocommerce-quick-cart-for-multiple-variations' ); ?>"></textarea>
						</div>
					</div>
					<div class="wqcmv_product_submit">
						<div class="form-control">
							<button type="button" class="button button-secondary wqcmv-send-notofication-to-admin">
								<?php esc_html_e( apply_filters( 'wqcmv_contact_admin_submit_button_text', 'Submit' ), 'woocommerce-quick-cart-for-multiple-variations' ); ?>
							</button>
						</div>
					</div>
				</div>
				<?php do_action( 'wcqcmv_after_contact_admin_modal_content', $product_id ); ?>
				<?php
				$html   = ob_get_clean();
				$result = array(
					'message'     => 'wqcmv-out-of-stock-products-fetched',
					'html'        => $html,
					'modal_title' => apply_filters( 'wqcmv_contact_admin_modal_header_text', $modal_title ),
				);
				wp_send_json_success( $result );
				wp_die();
			}

		}

		function wqcmv_modal_pagination_outofstock_products() {
			$action     = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			$product_id = filter_input( INPUT_POST, 'product_id', FILTER_SANITIZE_NUMBER_INT );
			$loadchunk  = filter_input( INPUT_POST, 'loadchunk', FILTER_SANITIZE_NUMBER_INT );

			if ( empty( $product_id ) || 'wqcmv_modal_pagination_outofstock_products' !== $action ) {
				return '';
			}
			$per_page                 = apply_filters( 'wqcmv_admin_contact_form_variation_per_page', 5 );
			$variations_data          = wqcmv_fetch_outofstcok_variation_ids( $product_id, $per_page, $loadchunk );
			$pagination               = isset( $variations_data['pagination'] ) ? $variations_data['pagination'] : 'no';
			$pagination_variation_ids = isset( $variations_data['variation_ids'] ) ? $variations_data['variation_ids'] : array();
			ob_start();
			?>
			<?php
			$html   = wqcmv_get_outofstock_product_html( $pagination_variation_ids );
			$result = array(
				'message'              => 'modal-product-pagination',
				'html'                 => $html,
				'loadchunk'            => $loadchunk,
				'next_chunk_available' => $pagination,
				'prev_chunk_available' => isset( $loadchunk ) && 1 < intval( $loadchunk ) ? 'yes' : 'no'
			);
			wp_send_json_success( $result );
			wp_die();
			?>
			<?php
		}

	/**
	 * Ajax function to send notification to admin.
	 */
	function wqcmv_send_notification_to_admin() {

		$action = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		if ( isset( $action ) && 'wqcmv_send_notification_to_admin' === $action ) {
			$name               = filter_input( INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			$email              = filter_input( INPUT_POST, 'email', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			$user_message       = filter_input( INPUT_POST, 'user_message', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			$admin_email        = get_option( 'admin_email' );
			$email_subject      = esc_html__( 'Request for out of stock products', 'woocommerce-quick-cart-for-multiple-variations' );
			$filter_args        = array(
				'items' => array(
					'flags'  => FILTER_REQUIRE_ARRAY,
					'filter' => FILTER_SANITIZE_NUMBER_INT
				)
			);
			$filter_items_array = filter_input_array( INPUT_POST, $filter_args );
			$items              = isset( $filter_items_array['items'] ) && ! empty( $filter_items_array['items'] ) && is_array( $filter_items_array['items'] ) ? $filter_items_array['items'] : array();
			$unique_items       = ! empty( $items ) ? array_unique( $items, SORT_REGULAR ) : array();
			$mail_content       = wqcmv_contact_admin_email_content( $unique_items, $user_message, $name, $email );
			if ( function_exists( 'wqcmv_send_mail' ) ) {
				wqcmv_send_mail( $admin_email, $email_subject, $mail_content );
			}
			$result = array(
				'message' => 'wqcmv-notification-sent',
			);
			wp_send_json_success( $result );
		}
		wp_die();

	}

	/**
	 * Ajax function to load modal for user to notify outofstock products.
	 */
	function wqcmv_get_user_email_for_notify_user() {

		$action = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		if ( isset( $action ) && 'wqcmv_get_user_email_for_notify_user' === $action ) {
			$variation_id = filter_input( INPUT_POST, 'variation_id', FILTER_SANITIZE_NUMBER_INT );
			$current_user = wp_get_current_user();
			ob_start();
			?>
			<div class="wqcmv_product_form" data-variation-id="<?php echo esc_attr( $variation_id ); ?>">
				<div class="wqcmv_product_fields wqcmv-notify-me-form-fields">
					<div class="form-control control-col-6">
						<?php if ( is_user_logged_in() ) {
							$modal_title = esc_html__( 'Enter your email and we’ll let you know when it’s here', 'woocommerce-quick-cart-for-multiple-variations' );
							?>
							<input type="email" name="wqcmv_user_email" id="wqcmv_user_email"
							placeholder="user@example.com"
							value="<?php echo esc_attr( $current_user->user_email ); ?>" required>
							<?php do_action( 'wqcmv_notify_me_modal_field', $current_user, $variation_id ); ?>
						<?php } else {
							$modal_title = esc_html__( 'Enter Details', 'woocommerce-quick-cart-for-multiple-variations' );
							?>
							<input type="email" name="wqcmv_user_email" id="wqcmv_user_email"
							placeholder="user@example.com"
							required>
							<?php do_action( 'wqcmv_notify_me_modal_field', $current_user, $variation_id ); ?>
							<span><input type="checkbox" name="create_account" id="create_account"
								value="yes"><label
								for="create_account"><?php esc_html_e( 'I want to create my account', 'woocommerce-quick-cart-for-multiple-variations' ); ?></label></span>
								<?php
							} ?>
						</div>
					</div>
					<div class="wqcmv_product_submit">
						<div class="form-control">

							<button type="button" class="button button-secondary wqcmv-notify-for-outofstock">
								<?php esc_html_e( apply_filters( 'wqcmv_notify_modal_submit_button_text', __( 'Submit', 'woocommerce-quick-cart-for-multiple-variations' ), $variation_id ) ); ?>
							</button>
						</div>
					</div>
				</div>
				<?php
				$html   = ob_get_clean();
				$result = array(
					'message'     => 'wqcmv-modal-opened-for-notify-user',
					'html'        => $html,
					'modal_title' => $modal_title
				);
				wp_send_json_success( $result );
				wp_die();
			}

		}

	/**
	 * Ajax function to Store User Data To Notify Later.
	 */
	function wqcmv_notification_request() {

		$action = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		if ( isset( $action ) && 'wqcmv_notification_request' === $action ) {
			$variation_id        = filter_input( INPUT_POST, 'variation_id', FILTER_SANITIZE_NUMBER_INT );
			$wqcmv_user_email    = filter_input( INPUT_POST, 'email', FILTER_VALIDATE_EMAIL );
			$error_message       = $success_message = $success_html = $error_html = '';
			$create_account      = filter_input( INPUT_POST, 'create_account', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			$existing_user_email = get_post_meta( $variation_id, 'notification_email' );
			if ( 'yes' === $create_account ) {
				$exploded_email  = explode( "@", $wqcmv_user_email );
				$wqcmv_username  = $exploded_email[0];
				$random_password = wp_generate_password( 12, false );
				if ( username_exists( $wqcmv_username ) ) {
					$wqcmv_username = wqcmv_generate_custom_username( $wqcmv_username );
				}
				if ( false === email_exists( $wqcmv_user_email ) ) {
					$success_message = 'success';
					ob_start();
					do_action( 'wqcmv_update_variation_for_restock_notification', $variation_id, $wqcmv_user_email );
					if ( wp_create_user( $wqcmv_username, $random_password, $wqcmv_user_email ) ) {
						$account_template = wqcmv_account_created_template( $wqcmv_username, $random_password );
						?>
						<i class="fa fa-check-circle"></i>
						<p><?php esc_html_e( 'Request submitted successfully & account is created. Please check mail for account details.', 'woocommerce-quick-cart-for-multiple-variations' ) ?></p>
						<?php
						if ( function_exists( 'wqcmv_send_mail' ) ) {
							wqcmv_send_mail( $wqcmv_user_email, 'Account Created', $account_template );
						}
					}
					$success_html = ob_get_clean();
				} else {
					ob_start();
					$error_message = 'error';
					?>
					<i class="fa fa-times" aria-hidden="true"></i>
					<?php esc_html_e( 'Account with this email id is already exist.', 'woocommerce-quick-cart-for-multiple-variations' ); ?>
					<a href="javascript:void(0);"
					class="wqcmv-return-to-form button button-secondary"><?php esc_html_e( 'Retry', 'woocommerce-quick-cart-for-multiple-variations' ) ?></a>
					<?php
					$error_html = ob_get_clean();
				}
			} else if ( in_array( $wqcmv_user_email, $existing_user_email, true ) ) {
				ob_start();
				$error_message = 'error';
				?>
				<i class="fa fa-times" aria-hidden="true"></i>
				<p><?php esc_html_e( 'This email id is already registered to be notified for this variation . ', 'woocommerce-quick-cart-for-multiple-variations' ) ?></p>
				<a href="javascript:void(0);"
				class="wqcmv-return-to-form button button-secondary"><?php esc_html_e( 'Retry', 'woocommerce-quick-cart-for-multiple-variations' ) ?></a>
				<?php
				$error_html = ob_get_clean();
			} else {
				ob_start();
				$success_message = 'success';
				do_action( 'wqcmv_update_variation_for_restock_notification', $variation_id, $wqcmv_user_email );
				?>
				<i class="fa fa-check-circle"></i>
				<?php esc_html_e( 'Request submitted successfully..', 'woocommerce-quick-cart-for-multiple-variations' );
				$success_html = ob_get_clean();
			}
			if ( ! in_array( $wqcmv_user_email, $existing_user_email, true ) ) {
				add_post_meta( $variation_id, 'notification_email', $wqcmv_user_email );
			}
			$html   = ob_get_clean();
			$result = array(
				'message'     => 'wqcmv-modal-opened-for-notify-user',
				'html'        => $html,
				'errormsg'    => $error_message,
				'errorhtml'   => $error_html,
				'sucessmsg'   => $success_message,
				'successhtml' => $success_html
			);
			wp_send_json_success( $result );
		}

	}

	/**
	 * Override variable headers.
	 */
	function wqcmv_modify_table_headers( $header_array ) {

		$enable_stock_visibility_option = get_option( 'vpe_enable_stock_visibility' );
		$additional_column              = array();
		if ( 'yes' === $enable_stock_visibility_option ) {
			$additional_column['stock_status'] = esc_html__( 'Stock Status', 'woocommerce-quick-cart-for-multiple-variations' );
			$temp                              = array_splice( $header_array, 0, 2 );
			$updated_array                     = array_merge( $temp, $additional_column );
			$header_array                      = array_merge( $updated_array, $header_array );
		}

		return $header_array;

	}


	/**
	 * Add product to cart if cart url has product id as a parameter probably coming from the notify email link.
	 */
	function wqcmv_add_to_cart_for_registered_user() {

		if ( function_exists('is_cart') && is_cart() ) {
			$pid = absint( filter_input( INPUT_GET, 'pid', FILTER_SANITIZE_NUMBER_INT ) );
			if ( ! empty( $pid ) ) {
				if ( empty( WC()->cart->get_cart() ) ) {
					WC()->cart->add_to_cart( $pid, 1 );
				} else {
					$prouct_exists = false;
					foreach ( WC()->cart->get_cart() as $cart_item ) {
						$variation_id = $cart_item['variation_id'];
						$product_id   = $cart_item['product_id'];
						$_pid         = 0 !== $variation_id ? $variation_id : $product_id;
						if ( $pid === $_pid ) {
							$prouct_exists = true;
						}
					}
					if ( false === $prouct_exists ) {
						WC()->cart->add_to_cart( $pid, 1 );
					}
				}
			}
		}
	}
	/**
	 * Function to call ajax for the pre order functionality.
	 * 
	 * @since 1.0.0
	 * 
	 */
	public function wqcmv_get_user_email_for_notify_user_pre_order_callback() {
		$action = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

		// Check if action mismatches.
		if ( empty( $action ) || 'wqcmv_get_user_email_for_notify_user_pre_order' !== $action ) {
			echo esc_html( 0 );
			wp_die();
		}
		$current_user       = wp_get_current_user();
		$current_uid        = $current_user->ID;
		$current_user_email = $current_user->user_email;
		$variation_id       = filter_input( INPUT_POST, 'variation_id', FILTER_SANITIZE_NUMBER_INT );
		$modal_title        = esc_html__( 'Pre-Order Form', 'woocommerce-quick-cart-for-multiple-variations' );
		$variation_all_data = wc_get_product( $variation_id );
		$variation_title    = $variation_all_data->get_formatted_name();
		$variation_title    = wp_strip_all_tags( $variation_title );
		$sku                = $variation_all_data->get_sku();
		if ( $variation_all_data->is_on_sale() ) {
			$price = $variation_all_data->get_price_html();
		} else {
			$price = wc_price( wc_get_price_to_display( $variation_all_data ) ) . $variation_all_data->get_price_suffix();
		}
		ob_start();
		?>
		<div class="wqcmv_pre_order_main_container">
			<div class="wqcmv_product_table ">
				<table class="wp-list-table widefat striped">
					<thead>
						<tr>
							<th scope="col" class="manage-column"><a
								href="javascript:void(0);"><span><?php esc_html_e( 'Product', 'woocommerce-quick-cart-for-multiple-variations' ); ?></span></a>
							</th>
							<th scope="col"
							class="manage-column"><?php esc_html_e( 'Quantity', 'woocommerce-quick-cart-for-multiple-variations' ); ?></th>
						</tr>
					</thead>
					<tbody id="the-list" class="wqcmv-recommended-prod-list">
						<tr class="product-out-of-stock" data-variation-id="<?php esc_html_e( $variation_id ); ?>">
							<td class="has-row-actions">
								<span><?php esc_html_e( $variation_title, 'woocommerce-quick-cart-for-multiple-variations' ); ?></span>
							</td>
							<td>
								<div class="quantity">
									<div class="qtyCount">
										<label>Qty:</label>
										<a href="" class="minusQty"></a>
										<input type="number" name="qty" id="qty" value="1" min="1" size="4" max="79" class="variant-qty-input wqcmv-prod-qty-<?php esc_html_e( $variation_id ); ?> test-qty" readonly>
										<a href="" class="plusQty"></a>
										
									</div>
								</div>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			<div class="wqcmv_product_form wqcmv_pre_order_container">
				<div class="wqcmv_product_fields">
					<div class="form-control control-col-6">
						<input type="email" name="wqcmv_email" id="wqcmv_email" placeholder="<?php esc_html_e( 'Enter Your Email', 'woocommerce-quick-cart-for-multiple-variations' ) ?>" value="<?php echo esc_attr( $current_user_email );?>" required>
					</div>
				</div>
				<div class="wqcmv_product_submit">
					<div class="form-control">
						<button type="button" class="button button-secondary wqcmv-send-notofication-to-admin-pre-order" data-pid="<?php echo esc_attr( $variation_id ); ?>" data-uid="<?php echo esc_attr( $current_uid ); ?>">
							<?php esc_html_e( apply_filters( 'wqcmv_contact_admin_submit_button_text_pre_order', 'Submit' ), 'woocommerce-quick-cart-for-multiple-variations' ); ?>
						</button>
					</div>
				</div>
			</div>
		</div>
		<?php
		$html   = ob_get_clean();
		$result = array(
			'message'     => 'wqcmv-out-of-stock-products-fetched-pre-order',
			'html'        => $html,
			'modal_title' => apply_filters( 'wqcmv_contact_admin_modal_header_text_pre_order', $modal_title ),
		);
		wp_send_json_success( $result );
		wp_die();
	}
	/**
	 * Callback function to run on init functions.
	 * 
	 * @since 1.0.0
	 */
	public function wqcmv_init_callback() {
		wqcmv_custom_post_type_pre_order();
	}
	/**
	 * function to call ajax to store pre order information.
	 * 
	 * @since 1.0.0
	 */
	public function wqcmv_store_pre_order_send_notification_to_admin_callback() {
		$action = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

		// Check if action mismatches.
		if ( empty( $action ) || 'wqcmv_store_pre_order_send_notification_to_admin' !== $action ) {
			echo esc_html( 0 );
			wp_die();
		}
		$user_id          = filter_input( INPUT_POST, 'uid', FILTER_SANITIZE_NUMBER_INT );
		$product_id       = filter_input( INPUT_POST, 'pid', FILTER_SANITIZE_NUMBER_INT );
		$product_all_data = wc_get_product( $product_id );
		$product_title    = $product_all_data->get_formatted_name();
		$product_title    = wp_strip_all_tags( $product_title );
		$email            = filter_input( INPUT_POST, 'email', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$qty              = filter_input( INPUT_POST, 'qty', FILTER_SANITIZE_NUMBER_INT );
		$post_title       = $email . '(' . gmdate('Y-m-d h:i:s') . ')';
		$args = array(
			'post_title'    => $post_title,
			'post_status'   => 'publish',
			'post_author'   => $user_id,
			'post_type'     => 'pre_order',
			'post_date'     => gmdate('Y-m-d h:i:s'),
			'post_date_gmt' => gmdate('Y-m-d h:i:s'),
		);
		$request_id = wp_insert_post( $args );
		if ( ! empty( $request_id ) ) {
			update_post_meta( $request_id, 'wqcmvp_user_id', $user_id );
			update_post_meta( $request_id, 'wqcmvp_product_id', $product_id );
			update_post_meta( $request_id, 'wqcmvp_email', $email );
			update_post_meta( $request_id, 'wqcmvp_qty', $qty );
			update_post_meta( $request_id, 'wqcmvp_product_title', $product_title );
			$stored_data_array = array(
				'wqcmvp_user_id'       => $user_id,
				'wqcmvp_product_id'    => $product_id,
				'wqcmvp_email'         => $email,
				'wqcmvp_qty'           => $qty,
				'wqcmvp_product_title' => $product_title,
			);
			update_post_meta( $request_id, 'wqcmv_pre_order_data', $stored_data_array );
		}
		$result = array(
			'message'     => 'wqcmv-pre-order-notification-sent',
		);
		wp_send_json_success( $result );
		wp_die();
	}

	/**
	 * Function for override stock html for simple product which not in stock.
	 *
	 * @param string $html returns the stock html.
	 * @param string $availability define product stock.
	 * @param object $product Holds product object.
	 *
	 * @since 1.0.0
	 */
	public function wqcmv_simple_product_outofstock_html( $html, $availability, $product ) {
		if ( $product->is_type( 'simple' ) && ! $product->is_in_stock() ) {
			$html = '<div class="cartItemInfo"><div class="quantity"><button type="button" name="wqcmv_notify" class="btn" id="vns_simpleproduct_pre_order" style="font-size: 13px;" data-product_id="' . $product->get_id() . '">Pre Order</button></div></div>';
		}

		return $html;
	}

	/**
	 * Function to call ajax for the pre order functionality.
	 * 
	 * @since 1.0.0
	 * 
	 */
	public function wqcmv_get_user_email_for_notify_user_simple_product_pre_order_callback() {
		$action = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

		// Check if action mismatches.
		if ( empty( $action ) || 'wqcmv_get_user_email_for_notify_user_simple_product_pre_order' !== $action ) {
			echo esc_html( 0 );
			wp_die();
		}
		$current_user       = wp_get_current_user();
		$current_uid        = $current_user->ID;
		$current_user_email = $current_user->user_email;
		$product_id         = filter_input( INPUT_POST, 'product_id', FILTER_SANITIZE_NUMBER_INT );
		$modal_title        = esc_html__( 'Pre-Order Form', 'woocommerce-quick-cart-for-multiple-variations' );
		$product_all_data   = wc_get_product( $product_id );
		$product_title      = $product_all_data->get_formatted_name();
		$product_title      = wp_strip_all_tags( $product_title );
		if ( $product_all_data->is_on_sale() ) {
			$price = $product_all_data->get_price_html();
		} else {
			$price = wc_price( wc_get_price_to_display( $product_all_data ) ) . $product_all_data->get_price_suffix();
		}
		ob_start();
		?>
		<div class="wqcmv_pre_order_main_container">
			<div class="wqcmv_product_table ">
				<table class="wp-list-table widefat striped">
					<thead>
						<tr>
							<th scope="col" class="manage-column"><a
								href="javascript:void(0);"><span><?php esc_html_e( 'Product', 'woocommerce-quick-cart-for-multiple-variations' ); ?></span></a>
							</th>
							<th scope="col"
							class="manage-column"><?php esc_html_e( 'Quantity', 'woocommerce-quick-cart-for-multiple-variations' ); ?></th>
						</tr>
					</thead>
					<tbody id="the-list" class="wqcmv-recommended-prod-list">
						<tr class="product-out-of-stock" data-product-id="<?php esc_html_e( $product_id ); ?>">
							<td class="has-row-actions">
								<span><?php esc_html_e( $product_title, 'woocommerce-quick-cart-for-multiple-variations' ); ?></span>
							</td>
							<td>
								<div class="quantity">
									<div class="qtyCount">
										<label>Qty:</label>
										<a href="" class="minusQty"></a>
										<input type="number" name="qty" id="qty" value="0" min="1" size="4" max="79" class="variant-qty-input wqcmv-prod-qty-<?php esc_html_e( $product_id ); ?> test-qty" readonly>
										<a href="" class="plusQty"></a>
									</div>
								</div>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			<div class="wqcmv_product_form wqcmv_pre_order_container">
				<div class="wqcmv_product_fields">
					<div class="form-control control-col-6">
						<input type="email" name="wqcmv_email" placeholder="<?php esc_html_e( 'Enter Your Email', 'woocommerce-quick-cart-for-multiple-variations' ) ?>" value="<?php echo esc_attr( $current_user_email );?>" required>
					</div>
				</div>
				<div class="wqcmv_product_submit">
					<div class="form-control">
						<button type="button" class="button button-secondary wqcmv-send-notofication-to-admin-pre-order" data-pid="<?php echo esc_attr( $product_id ); ?>" data-uid="<?php echo esc_attr( $current_uid ); ?>">
							<?php esc_html_e( apply_filters( 'wqcmv_contact_admin_submit_button_text_pre_order', 'Submit' ), 'woocommerce-quick-cart-for-multiple-variations' ); ?>
						</button>
					</div>
				</div>
			</div>
		</div>
		<?php
		$html   = ob_get_clean();
		$result = array(
			'message'     => 'wqcmv-out-of-stock-products-fetched-pre-order',
			'html'        => $html,
			'modal_title' => apply_filters( 'wqcmv_contact_admin_modal_header_text_pre_order', $modal_title ),
		);
		wp_send_json_success( $result );
		wp_die();
	}
}

