<?php
/**
 * Single Product Price
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/price.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $product;
// die('lkoooooo');
?>
<div class="price-wrap">
<p class="<?php echo esc_attr( apply_filters( 'woocommerce_product_price_class', 'price' ) ); ?>">
<?php $product->get_price_html(); ?>


<?php
		$product_data = wc_get_product($product->get_id());

		if ( $product_data && method_exists( $product_data, 'get_type' ) ) {
			$product_type = $product_data->get_type();
		}
			if( $product_type == 'simple' ){

			// remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
			// Get necessary price details
			$reg_price  = $product->get_regular_price();
			$sale_price = $product->get_sale_price();
			$product_price = $sale_price ? $sale_price : $reg_price; // Use sale price if available, otherwise regular price
			$vaping_liquid = get_post_meta( $product->get_id(), '_vaping_liquid', true );
			$vaping_liquid = (int) $vaping_liquid;
			$state = isset( $_COOKIE['user_state'] ) ? sanitize_text_field( $_COOKIE['user_state'] ) : '';

			if ( isset( $vaping_liquid ) && ! empty( $vaping_liquid ) ) {
				$ontario_tax = supavapes_calculate_ontario_tax( $vaping_liquid );
				$federal_tax = supavapes_calculate_federal_tax( $vaping_liquid );
			}
			
			// Determine the final price based on state
			if ( 'Ontario' !== $state ) {
				$final_price = isset( $sale_price ) && ! empty( $sale_price ) ? $sale_price : $reg_price;
				$final_price += $federal_tax;
			} else {
				$final_price = isset( $sale_price ) && ! empty( $sale_price ) ? $sale_price : $reg_price;
				$final_price += $ontario_tax + $federal_tax;
			}

			?>
			<?php if ( isset( $vaping_liquid ) && !empty( $vaping_liquid ) && $vaping_liquid >= 10 ) { 
				 echo supavapes_price_breakdown_custom_html( $product_price, $federal_tax, $ontario_tax, $final_price, $state );
				?>
			<?php }?>
			<?php 
		
			} ?>
</div>
</p>