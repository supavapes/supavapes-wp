<?php
/**
 * The template for displaying product price filter widget.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-widget-price-filter.php
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

defined( 'ABSPATH' ) || exit;

?>
<?php do_action( 'woocommerce_widget_price_filter_start', $args ); ?>

<form method="get" action="<?php echo esc_url( $form_action ); ?>">
    <div class="price_slider_wrapper">
        <div class="price_slider" style="display:none;"></div>
        <div class="price_slider_amount" data-step="<?php echo esc_attr( $step ); ?>">
            <label class="screen-reader-text" for="min_price"><?php esc_html_e( 'Min price', 'woocommerce' ); ?></label>
            <?php 
                // Change the minimum price range here
                $new_min_price = 1; // Example: Set the new minimum price to 10
            ?>
            <input type="text" id="min_price" name="min_price" value="<?php echo esc_attr( $current_min_price ); ?>" data-min="<?php echo esc_attr( $new_min_price ); ?>" placeholder="<?php echo esc_attr__( 'Min price', 'woocommerce' ); ?>" />
            <label class="screen-reader-text" for="max_price"><?php esc_html_e( 'Max price', 'woocommerce' ); ?></label>
            <?php 
                // Change the maximum price range here
                $new_max_price = 1000; // Example: Set the new maximum price to 500
            ?>
            <input type="text" id="max_price" name="max_price" value="<?php echo esc_attr( $current_max_price ); ?>" data-max="<?php echo esc_attr( $new_max_price ); ?>" placeholder="<?php echo esc_attr__( 'Max price', 'woocommerce' ); ?>" />
            <?php /* translators: Filter: verb "to filter" */ ?>
            <button type="submit" class="button<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>"><?php echo esc_html__( 'Filter', 'woocommerce' ); ?></button>
            <div class="price_label" style="display:none;">
                <?php echo esc_html__( 'Price:', 'woocommerce' ); ?> <span class="from"></span> &mdash; <span class="to"></span>
            </div>
            <?php echo wc_query_string_form_fields( null, array( 'min_price', 'max_price', 'paged' ), '', true ); ?>
            <div class="clear"></div>
        </div>
    </div>
</form>
<script>
jQuery(document).ready(function () {
	// Function to update price label
	function updatePriceLabel() {
		// Retrieve data-min and data-max values
		var minPrice = jQuery('#min_price').data('min');
		var maxPrice = jQuery('#max_price').data('max');

		// Update price label content
		jQuery('.price_label .from').text(minPrice);
		jQuery('.price_label .to').text(maxPrice);
	}

	// Call the updatePriceLabel function on page load
	updatePriceLabel();
});
</script>

<?php do_action( 'woocommerce_widget_price_filter_end', $args ); ?>
