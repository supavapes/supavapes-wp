<?php
/**
 * Displayed when no products are found matching the current query
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/loop/no-products-found.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.8.0
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="woocommerce-no-products-found">
	<?php //wc_print_notice( esc_html__( 'No products were found matching your selection.', 'woocommerce' ), 'notice' ); ?>
	<div class="no-products-found-box">
		<p class="no-products-found-text">No products found matching your search criteria. Please try again.</p>
		<a href="/shop" class="button reset-filter">Reset Filter
			<svg width="21" height="18" viewBox="0 0 21 18" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M20.0183 7.7734L12.5955 0.350527C12.3955 0.157364 12.1276 0.0504805 11.8496 0.0528965C11.5715 0.0553126 11.3056 0.166835 11.1089 0.363444C10.9123 0.560052 10.8008 0.826016 10.7984 1.10405C10.796 1.38209 10.9029 1.64995 11.096 1.84995L16.7088 7.4627H1.06041C0.779172 7.4627 0.509452 7.57442 0.310587 7.77328C0.111721 7.97215 0 8.24187 0 8.52311C0 8.80435 0.111721 9.07406 0.310587 9.27293C0.509452 9.4718 0.779172 9.58352 1.06041 9.58352H16.7088L11.096 15.1963C10.9948 15.2941 10.914 15.4111 10.8584 15.5405C10.8028 15.6698 10.7736 15.809 10.7723 15.9498C10.7711 16.0906 10.7979 16.2302 10.8513 16.3605C10.9046 16.4909 10.9833 16.6093 11.0829 16.7088C11.1825 16.8084 11.3009 16.8871 11.4312 16.9405C11.5615 16.9938 11.7011 17.0206 11.8419 17.0194C11.9827 17.0182 12.1219 16.9889 12.2512 16.9333C12.3806 16.8778 12.4976 16.797 12.5955 16.6957L20.0183 9.27282C20.2171 9.07396 20.3288 8.80429 20.3288 8.52311C20.3288 8.24192 20.2171 7.97225 20.0183 7.7734Z" fill="white"></path>
			</svg>
		</a>
	</div>
	<?php echo do_shortcode('[fibosearch]'); ?>
</div>
