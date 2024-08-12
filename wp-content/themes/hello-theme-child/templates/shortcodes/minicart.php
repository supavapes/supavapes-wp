<?php
/**
 * This file is used to get minicart data.
 */
defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

?>
<a href="#" class="dropdown-back" data-toggle="dropdown">
        <i class="fa fa-shopping-cart" aria-hidden="true"></i>
        <div class="basket-item-count" style="display: inline;">
            <span class="cart-items-count count">
				<?php
                	// echo wp_kses_post(WC()->cart->get_cart_contents_count());
				?>
            </span>
        </div>
    </a>
    <ul class="dropdown-menu dropdown-menu-mini-cart">
        <li>
            <div class="widget_shopping_cart_content">
                <p>Loading..</p>
				<?php
                	// echo wp_kses_post(woocommerce_mini_cart());
				?>
            </div>
        </li>
    </ul>