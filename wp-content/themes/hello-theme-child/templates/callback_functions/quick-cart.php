<?php
if(isset($_POST['product_id']) && !empty($_POST['product_id'])){
    $product_id = intval( $_POST['product_id'] ); 
}
WC()->cart->add_to_cart( $product_id);
 // Get cart quantity
 $cart_quantity = WC()->cart->get_cart_contents_count();
 // Capture the mini cart HTML
 woocommerce_mini_cart();