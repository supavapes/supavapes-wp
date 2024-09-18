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

?>
<div class="price-wrap">
<p class="<?php echo esc_attr( apply_filters( 'woocommerce_product_price_class', 'price' ) ); ?> price-test">
<?php echo $product->get_price_html(); ?>


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
			$vaping_liquid = get_post_meta( $product->get_id(), '_vaping_liquid', true );
			$vaping_liquid = (int) $vaping_liquid;
			$state = isset( $_COOKIE['user_state'] ) ? sanitize_text_field( $_COOKIE['user_state'] ) : '';

			if ( isset( $vaping_liquid ) && ! empty( $vaping_liquid ) ) {
				$ontario_tax = supavapes_calculate_ontario_tax( $vaping_liquid );
				$federal_tax = supavapes_calculate_federal_tax( $vaping_liquid );
			}
			
			// Determine the final price based on state
			if ( 'Gujarat' !== $state ) {
				$final_price = isset( $sale_price ) && ! empty( $sale_price ) ? $sale_price : $reg_price;
				$final_price += $federal_tax;
			} else {
				$final_price = isset( $sale_price ) && ! empty( $sale_price ) ? $sale_price : $reg_price;
				$final_price += $ontario_tax + $federal_tax;
			}

			?>
			<?php if ( isset( $vaping_liquid ) && !empty( $vaping_liquid ) && $vaping_liquid >= 10 ) { ?>
				<div class="info-icon-container">
					<img src="/wp-content/uploads/2024/09/info-icon.svg" class="info-icon" alt="Info Icon" style="height: 15px; width: 15px; position: relative;">
					<div class="price-breakup-popup">
						<h5 class="header"><?php esc_html_e( 'Price Breakdown', 'supavapes' ); ?></h5>
						<table class="pricetable">
							<?php if ( isset( $sale_price ) && !empty( $sale_price ) ) { ?>
							<tr>
								<td class='leftprice'><?php esc_html_e( 'Product Price', 'supavapes' ); ?></td>
								<td class='rightprice'><?php echo wc_price( $sale_price ); ?></td>
							</tr>
							<?php } else { ?>
							<tr>
								<td class='leftprice'><?php esc_html_e( 'Product Price', 'supavapes' ); ?></td>
								<td class='rightprice'><?php echo wc_price( $reg_price ); ?></td>
							</tr>
							<?php } ?>
							<?php if ( 'Gujarat' !== $state ) { ?>
							<tr>
								<td class='leftprice'><?php esc_html_e( 'Federal Excise Tax', 'supavapes' ); ?></td>
								<td class='rightprice'><?php echo wc_price( $federal_tax ); ?></td>
							</tr>
							<?php } else { ?>
							<tr>
								<td class='leftprice'><?php esc_html_e( 'Ontario Excise Tax', 'supavapes' ); ?></td>
								<td class='rightprice'><?php echo wc_price( $ontario_tax ); ?></td>
							</tr>
							<tr>
								<td class='leftprice'><?php esc_html_e( 'Federal Excise Tax', 'supavapes' ); ?></td>
								<td class='rightprice'><?php echo wc_price( $federal_tax ); ?></td>
							</tr>
							<?php } ?>
							<tr class="wholesaleprice">
								<td class='leftprice'><?php esc_html_e( 'Wholesale Price', 'supavapes' ); ?></td>
								<td class='rightprice'><?php echo wc_price( $final_price ); ?></td>
							</tr>
						</table>
					</div>
				</span>
			<?php }?>
			<?php 
		
			} ?>
</div>
</p>