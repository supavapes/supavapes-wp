<?php
/**
 * Simple product add to cart
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/add-to-cart/simple.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product->is_purchasable() ) {
	return;
}

echo wc_get_stock_html( $product ); // WPCS: XSS ok.

if ( $product->is_in_stock() ) : ?>

	<?php do_action( 'woocommerce_before_add_to_cart_form' ); ?>

	<form class="cart" id="ajax_add_to_cart_form" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype='multipart/form-data'>
		<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>

		<?php
		do_action( 'woocommerce_before_add_to_cart_quantity' );

		woocommerce_quantity_input(
			array(
				'min_value'   => apply_filters( 'woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product ),
				'max_value'   => apply_filters( 'woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product ),
				'input_value' => isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : $product->get_min_purchase_quantity(), // WPCS: CSRF ok, input var ok.
				'product_id'  => $product->get_id(), // Pass the product ID
			)
		);

		do_action( 'woocommerce_after_add_to_cart_quantity' );
		?>
		<button type="submit" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>" class="single_add_to_cart_button button alt<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" data-product_id="<?php echo $product->get_id();?>"><span><?php echo esc_html( $product->single_add_to_cart_text() ); ?></span><svg width="25" height="24" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M18.2949 17.882C16.7436 17.8806 15.4847 19.137 15.4832 20.6883C15.4817 22.2396 16.7381 23.4985 18.2895 23.5C19.8408 23.5015 21.0996 22.2451 21.1012 20.6937V20.691C21.0997 19.1413 19.8446 17.8851 18.2949 17.882ZM23.2771 4.43118C23.2099 4.41816 23.1416 4.41156 23.0731 4.41145H5.9701L5.69922 2.59928C5.53046 1.39579 4.50102 0.50037 3.28574 0.5H1.0835C0.485089 0.5 0 0.985089 0 1.5835C0 2.18191 0.485089 2.667 1.0835 2.667H3.28844C3.35499 2.66652 3.41939 2.69056 3.46935 2.73453C3.51931 2.7785 3.55133 2.83932 3.55931 2.90539L5.22789 14.3417C5.45665 15.7949 6.70665 16.867 8.17773 16.8717H19.4488C20.8652 16.8736 22.087 15.8781 22.3716 14.4907L24.135 5.7008C24.2487 5.11329 23.8646 4.54488 23.2771 4.43118ZM11.4136 20.5707C11.3477 19.0649 10.1049 17.8795 8.59759 17.8847C7.04752 17.9474 5.8417 19.2548 5.90434 20.8048C5.96444 22.2922 7.17433 23.4743 8.66261 23.5H8.73033C10.2802 23.4321 11.4815 22.1206 11.4136 20.5707Z" fill="#EC4E34"></path>
					</svg></button>
		<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>
	</form>

	<?php do_action( 'woocommerce_after_add_to_cart_form' ); ?>

<?php endif; ?>
