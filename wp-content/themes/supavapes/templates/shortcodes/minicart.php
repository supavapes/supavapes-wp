<?php
/**
 * This file is used to get minicart data.
 * 
 * @version 1.0.0
 */
defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

?>
<a href="#" class="dropdown-back" data-toggle="dropdown">
	<i class="fa fa-shopping-cart" aria-hidden="true"></i>
	<div class="basket-item-count" style="display: inline;">
		<span class="cart-items-count count">
		</span>
	</div>
</a>
<ul class="dropdown-menu dropdown-menu-mini-cart">
	<li>
		<div class="widget_shopping_cart_content">
			<p><?php esc_html_e( 'Loading..', 'supavapes' ); ?></p>
		</div>
	</li>
</ul>
