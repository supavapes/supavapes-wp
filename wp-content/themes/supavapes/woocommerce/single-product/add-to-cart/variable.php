<?php
/**
 * Variable product add to cart
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/add-to-cart/variable.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 6.1.0
 */

defined( 'ABSPATH' ) || exit;

global $product;
// echo do_shortcode('[specific_month_sales year="2024" month="5"]');
$attribute_keys  = array_keys( $attributes );
$variations_json = wp_json_encode( $available_variations );
$variations_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );
$terms = get_the_terms($product->get_id(), 'store_locator');

if ($terms && !is_wp_error($terms)) {
    // Get the first term
    $first_term = reset($terms);
    $store_location = $first_term->name;
} else {
    $store_location = 'Hawkesbury Location'; // Default location if no terms are found
}
do_action( 'woocommerce_before_add_to_cart_form' ); ?>
<a href="#variable-table-wrap" class="available-options"><?php echo esc_html__('Available Options'); ?></a>
<?php if(isset($terms) && !empty($terms)){?>
<div class="surface-pick-up" data-surface-pick-up="">
    <div class="surface-pick-up-embed surface-pick-up-embed--available">
	<svg width="14" height="15" class="surface-pick-up-icon" viewBox="0 0 14 15" fill="none" xmlns="http://www.w3.org/2000/svg">
	<path d="M13.6747 2.88135C13.2415 2.44761 12.5381 2.44789 12.1044 2.88135L5.03702 9.94902L1.89587 6.8079C1.46213 6.37416 0.759044 6.37416 0.325304 6.8079C-0.108435 7.24163 -0.108435 7.94472 0.325304 8.37846L4.25157 12.3047C4.4683 12.5215 4.7525 12.6301 5.03672 12.6301C5.32094 12.6301 5.6054 12.5217 5.82213 12.3047L13.6747 4.45189C14.1084 4.01845 14.1084 3.31507 13.6747 2.88135Z" fill="#51A551"/>
	</svg>

        <div class="surface-pick-up-embed__location-info">
            <h3 class="surface-pick-up-embed__location-availability">Pickup available at <b><?php echo esc_html($store_location); ?></b></h3>
            <small class="surface-pick-up-embed__location-pick-up-time">Usually ready in 24 hours</small>
        </div>
        <a href="javascript:void(0);" class="surface-pick-up-embed__modal-btn check-available-stores" data-product_id="<?php echo $product->get_id(); ?>">
            Check availability at other stores
        </a>
    </div>
</div>
<?php }?>
<?php
/**
 * Detect plugin. For frontend only.
 */
include_once ABSPATH . 'wp-admin/includes/plugin.php';

// check for plugin using plugin name
if ( is_plugin_active( 'sassy-social-share/sassy-social-share.php' ) ) {
	//plugin is activated
	?>
	<div class="sv-social-share-icons">
		<label><?php echo esc_html__('Share:','hello-elementor-child'); ?></label>
		<?php echo do_shortcode('[Sassy_Social_Share]'); ?>
	</div>
	<?php
}?> 
<form class="variations_form cart"
	action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>"
	method="post" enctype='multipart/form-data' data-product_id="<?php echo absint( $product->get_id() ); ?>"
	data-product_variations="<?php echo $variations_attr; // WPCS: XSS ok. ?>">
	<?php
	do_action( 'woocommerce_before_variations_form' );
	$product_id = absint( $product->get_id() );
	// echo do_shortcode( '[vpe-woo-variable-product id="' . $product_id . '"]' );
	do_action( 'woocommerce_after_variations_form' );
	?>
</form>
<?php
do_action( 'woocommerce_after_add_to_cart_form' );