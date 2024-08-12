<?php

global $product;
$terms = get_the_terms($product->get_id(), 'store_locator');

if ($terms && !is_wp_error($terms)) {
    // Get the first term
    $first_term = reset($terms);
    $store_location = $first_term->name;
} else {
    $store_location = 'Hawkesbury Location'; // Default location if no terms are found
}
if(isset($terms) && !empty($terms)){
?>
<div class="surface-pick-up" data-surface-pick-up="">
    <div class="surface-pick-up-embed surface-pick-up-embed--available">
	<svg width="14" height="15" class="surface-pick-up-icon" viewBox="0 0 14 15" fill="none" xmlns="http://www.w3.org/2000/svg">
	<path d="M13.6747 2.88135C13.2415 2.44761 12.5381 2.44789 12.1044 2.88135L5.03702 9.94902L1.89587 6.8079C1.46213 6.37416 0.759044 6.37416 0.325304 6.8079C-0.108435 7.24163 -0.108435 7.94472 0.325304 8.37846L4.25157 12.3047C4.4683 12.5215 4.7525 12.6301 5.03672 12.6301C5.32094 12.6301 5.6054 12.5217 5.82213 12.3047L13.6747 4.45189C14.1084 4.01845 14.1084 3.31507 13.6747 2.88135Z" fill="#51A551"/>
	</svg>

        <div class="surface-pick-up-embed__location-info">
            <h3 class="surface-pick-up-embed__location-availability"><?php esc_html_e('Pickup available at','hello-elementor-child'); ?><b><?php echo esc_html($store_location); ?></b></h3>
            <small class="surface-pick-up-embed__location-pick-up-time"><?php esc_html_e('Usually ready in 24 hours','hello-elementor-child'); ?></small>
        </div>
        <a href="javascript:void(0);" class="surface-pick-up-embed__modal-btn check-available-stores" data-product_id="<?php echo esc_attr($product->get_id()); ?>">
            <?php esc_html_e('Check availability at other stores','hello-elementor-child'); ?>
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
if (is_plugin_active('sassy-social-share/sassy-social-share.php')) {
    //plugin is activated
    ?>
    <div class="sv-social-share-icons">
        <label><?php echo esc_html__('Share:', 'hello-elementor-child'); ?></label>
        <?php echo do_shortcode('[Sassy_Social_Share]'); ?>
    </div>
    <?php
}
?>