<?php
/**
 * Cart Page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.9.0
 */

defined( 'ABSPATH' ) || exit;

// get_header( 'shop' ); 

do_action( 'woocommerce_before_cart' ); ?>
<form class="woocommerce-cart-form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
	<?php do_action( 'woocommerce_before_cart_table' ); ?>
	<table class="shop_table shop_table_responsive cart woocommerce-cart-form__contents" cellspacing="0">
		<thead>
			<tr>
				<!-- <th class="product-thumbnail"><span class="screen-reader-text"><?php esc_html_e( 'Thumbnail image', 'woocommerce' ); ?></span></th> -->
				<th class="product-name"><?php esc_html_e( 'Product', 'woocommerce' ); ?></th>
				<th class="product-price"><?php esc_html_e( 'Price', 'woocommerce' ); ?></th>
				<th class="product-quantity"><?php esc_html_e( 'Quantity', 'woocommerce' ); ?></th>
				<th class="product-subtotal"><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></th>
				<th class="product-remove"><span class="screen-reader-text"><?php esc_html_e( 'Remove item', 'woocommerce' ); ?></span></th>
			</tr>
		</thead>
		<tbody>
			<?php do_action( 'woocommerce_before_cart_contents' ); ?>
			<?php
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
				$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
				/**
				 * Filter the product name.
				 *
				 * @since 2.1.0
				 * @param string $product_name Name of the product in the cart.
				 * @param array $cart_item The product in the cart.
				 * @param string $cart_item_key Key for the product in the cart.
				 */
				$product_name = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );
				if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
					$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
					?>
					<tr class="woocommerce-cart-form__cart-item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">
						<!-- <td class="product-thumbnail">
						</td> -->
						<td class="product-name" data-title="<?php esc_attr_e('Product', 'woocommerce'); ?>">
							<?php
								$thumbnail = apply_filters('woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key);
								if (!$product_permalink) {
									echo $thumbnail; // PHPCS: XSS ok.
								} else {
									printf('<a href="%s" class="cart-product-image">%s</a>', esc_url($product_permalink), $thumbnail); // PHPCS: XSS ok.
								}
								if (!$product_permalink) {
									echo wp_kses_post($product_name . '&nbsp;');
								} else {
									echo wp_kses_post(apply_filters('woocommerce_cart_item_name', sprintf('<a href="%s" class="cart-product-title">%s</a>', esc_url($product_permalink), $_product->get_name()), $cart_item, $cart_item_key));
								}
							?>
							<?php
								do_action('woocommerce_after_cart_item_name', $cart_item, $cart_item_key);
								// Meta data.
								echo wc_get_formatted_cart_item_data($cart_item); // PHPCS: XSS ok.
								// Backorder notification.
								if ($_product->backorders_require_notification() && $_product->is_on_backorder($cart_item['quantity'])) {
									echo wp_kses_post(apply_filters('woocommerce_cart_item_backorder_notification', '<p class="backorder_notification">' . esc_html__('Available on backorder', 'woocommerce') . '</p>', $product_id));
								}
							?>
						</td>
						<td class="product-price" data-title="<?php esc_attr_e( 'Price', 'woocommerce' ); ?>">
							<?php
								echo apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key ); // PHPCS: XSS ok.
							?>
						</td>
						<td class="product-quantity" data-title="<?php esc_attr_e( 'Quantity', 'woocommerce' ); ?>">
						<?php
						if ( $_product->is_sold_individually() ) {
							$min_quantity = 1;
							$max_quantity = 1;
						} else {
							$min_quantity = 0;
							$max_quantity = $_product->get_max_purchase_quantity();
						}
						$product_quantity = woocommerce_quantity_input(
							array(
								'input_name'   => "cart[{$cart_item_key}][qty]",
								'input_value'  => $cart_item['quantity'],
								'max_value'    => $max_quantity,
								'min_value'    => $min_quantity,
								'product_name' => $product_name,
							),
							$_product,
							false
						);
						echo apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item ); // PHPCS: XSS ok.
						?>
						</td>
						<td class="product-subtotal" data-title="<?php esc_attr_e( 'Subtotal', 'woocommerce' ); ?>">
							<?php
								echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // PHPCS: XSS ok.
							?>
						</td>
						<td class="product-remove">
							<?php
								echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									'woocommerce_cart_item_remove_link',
									sprintf(
										'<a href="%s" class="remove" aria-label="%s" data-product_id="%s" data-product_sku="%s"><i class="fa fa-trash"></i></a>',
										esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
										/* translators: %s is the product name */
										esc_attr( sprintf( __( 'Remove %s from cart', 'woocommerce' ), wp_strip_all_tags( $product_name ) ) ),
										esc_attr( $product_id ),
										esc_attr( $_product->get_sku() )
									),
									$cart_item_key
								);
							?>
						</td>
					</tr>
					<?php
				}
			}
			?>
			<?php do_action( 'woocommerce_cart_contents' ); ?>
			<tr>
				<td colspan="6" class="actions">
					<?php if ( wc_coupons_enabled() ) { ?>
						<div class="coupon">
							<label for="coupon_code" class="screen-reader-text"><?php esc_html_e( 'Coupon:', 'woocommerce' ); ?></label> <input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php esc_attr_e( 'Coupon code', 'woocommerce' ); ?>" /> <button type="submit" class="button<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="apply_coupon" value="<?php esc_attr_e( 'Apply coupon', 'woocommerce' ); ?>"><?php esc_html_e( 'Apply coupon', 'woocommerce' ); ?></button>
							<?php do_action( 'woocommerce_cart_coupon' ); ?>
						</div>
					<?php } ?>
					<button type="submit" class="button<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="update_cart" value="<?php esc_attr_e( 'Update cart', 'woocommerce' ); ?>"><?php esc_html_e( 'Update cart', 'woocommerce' ); ?></button>
					<?php do_action( 'woocommerce_cart_actions' ); ?>
					<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
				</td>
			</tr>
			<?php do_action( 'woocommerce_after_cart_contents' ); ?>
		</tbody>
	</table>
	<?php do_action( 'woocommerce_after_cart_table' ); ?>
</form>
<?php do_action( 'woocommerce_before_cart_collaterals' ); ?>
<div class="cart-collaterals">
	<?php
		/**
		 * Cart collaterals hook.
		 *
		 * @hooked woocommerce_cross_sell_display
		 * @hooked woocommerce_cart_totals - 10
		 */
		do_action( 'woocommerce_cart_collaterals' );
		?>
		<div class="cart-total-right">
			<p><?php echo esc_html__('Taxes and shipping calculated at checkout','hello-elementor-child'); ?></p>
			<div class="cart-shop-links">
				<p class="continue-shopping"><?php echo esc_html__('Continue Shopping','hello-elementor-child'); ?>
					<svg width="21" height="18" viewBox="0 0 21 18" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M20.0183 7.7734L12.5955 0.350527C12.3955 0.157364 12.1276 0.0504805 11.8496 0.0528965C11.5715 0.0553126 11.3056 0.166835 11.1089 0.363444C10.9123 0.560052 10.8008 0.826016 10.7984 1.10405C10.796 1.38209 10.9029 1.64995 11.096 1.84995L16.7088 7.4627H1.06041C0.779172 7.4627 0.509452 7.57442 0.310587 7.77328C0.111721 7.97215 0 8.24187 0 8.52311C0 8.80435 0.111721 9.07406 0.310587 9.27293C0.509452 9.4718 0.779172 9.58352 1.06041 9.58352H16.7088L11.096 15.1963C10.9948 15.2941 10.914 15.4111 10.8584 15.5405C10.8028 15.6698 10.7736 15.809 10.7723 15.9498C10.7711 16.0906 10.7979 16.2302 10.8513 16.3605C10.9046 16.4909 10.9833 16.6093 11.0829 16.7088C11.1825 16.8084 11.3009 16.8871 11.4312 16.9405C11.5615 16.9938 11.7011 17.0206 11.8419 17.0194C11.9827 17.0182 12.1219 16.9889 12.2512 16.9333C12.3806 16.8778 12.4976 16.797 12.5955 16.6957L20.0183 9.27282C20.2171 9.07396 20.3288 8.80429 20.3288 8.52311C20.3288 8.24192 20.2171 7.97225 20.0183 7.7734Z" fill="white"/>
					</svg>
				</p>
				<a href="/shop" class="btn view-all-btn">
				<svg width="25" height="24" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M18.2949 17.882C16.7436 17.8806 15.4847 19.137 15.4832 20.6883C15.4817 22.2396 16.7381 23.4985 18.2895 23.5C19.8408 23.5015 21.0996 22.2451 21.1012 20.6937V20.691C21.0997 19.1413 19.8446 17.8851 18.2949 17.882ZM23.2771 4.43118C23.2099 4.41816 23.1416 4.41156 23.0731 4.41145H5.9701L5.69922 2.59928C5.53046 1.39579 4.50102 0.50037 3.28574 0.5H1.0835C0.485089 0.5 0 0.985089 0 1.5835C0 2.18191 0.485089 2.667 1.0835 2.667H3.28844C3.35499 2.66652 3.41939 2.69056 3.46935 2.73453C3.51931 2.7785 3.55133 2.83932 3.55931 2.90539L5.22789 14.3417C5.45665 15.7949 6.70665 16.867 8.17773 16.8717H19.4488C20.8652 16.8736 22.087 15.8781 22.3716 14.4907L24.135 5.7008C24.2487 5.11329 23.8646 4.54488 23.2771 4.43118ZM11.4136 20.5707C11.3477 19.0649 10.1049 17.8795 8.59759 17.8847C7.04752 17.9474 5.8417 19.2548 5.90434 20.8048C5.96444 22.2922 7.17433 23.4743 8.66261 23.5H8.73033C10.2802 23.4321 11.4815 22.1206 11.4136 20.5707Z" fill="#EC4E34"></path>
				</svg>
				<span><?php echo esc_html__('Shop Now','hello-elementor-child'); ?></span>
				</a>
			</div>
		</div>
		<?php
	?>
</div>
<?php do_action( 'woocommerce_after_cart' ); 
// get_footer( 'shop' );
?>