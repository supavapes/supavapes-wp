<?php
/**
 * This file is used to get cart count in the header.
 */
 defined( 'ABSPATH' ) || exit; // Exit if accessed directly.
$cartcount = WC()->cart->get_cart_contents_count();
?>
<span class="cart-counter"><?php echo esc_html($cartcount); ?></span>