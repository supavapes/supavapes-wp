<?php
/**
 * The template for displaying product content within loops
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

// Ensure visibility.
if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}
$rating = $product->get_average_rating();
$product_type = $product->get_type();
$product_data = wc_get_product($product->get_id());
$price = $product_data->get_price_html();
$rating_num = intval($rating);
if($rating_num == 0){
    $rating_class = "no-ratting";
}else{
    $rating_class = '';
}
// echo do_shortcode( '[wpseo_breadcrumb]' );

?>
<li <?php wc_product_class( '', $product ); ?>>
<div class="sv-our-product-box">
        <div class="sv-our-product-img <?php echo esc_attr($rating_class); ?>">
			<?php echo wp_kses_post($product->get_image()); ?>
            <!-- <img src="/wp-content/uploads/2024/04/Z_Pods_LEX_10K_Pods_-_10ct_10.png" alt="product_iamge" class="sv-our-product-img-thumb"> -->
            <ul class="sv-stars">
                <?php if ($rating_num === 1) { ?>
                    <img src="<?php echo esc_url(get_site_url()); ?>/wp-content/uploads/2024/04/1-star.png" />
                <?php } else if ($rating_num === 2) { ?>
                    <img src="<?php echo esc_url(get_site_url()); ?>/wp-content/uploads/2024/04/2-star.png" />
                <?php } else if ($rating_num === 3) { ?>
                    <img src="<?php echo esc_url(get_site_url()); ?>/wp-content/uploads/2024/04/3-star.png" />
                <?php } else if ($rating_num === 4) { ?>
                    <img src="<?php echo esc_url(get_site_url()); ?>/wp-content/uploads/2024/04/4-star.png" />
                <?php } else if ($rating_num === 5) { ?>
                    <img src="<?php echo esc_url(get_site_url()); ?>/wp-content/uploads/2024/04/5-star.png" />
                <?php } ?>
            </ul>
        </div>
        <div class="sv-our-product-detail">
            <h3 class="sv-our-product-title"><?php echo esc_html($product->get_name(),'hello-elementor-child');?></h3>
            <p class="sv-our-product-price">
            <?php echo wp_kses_post($price); ?>
            </p>
        </div>
        <?php 
            if ( $product_data && method_exists( $product_data, 'get_type' ) ) {
                $product_type = $product_data->get_type();
                if( $product_type == 'variable' ){?>
                <a href="<?php echo esc_url(get_permalink( $product->get_id() ));?>" class="sv-shop-btn"><?php echo esc_html__('Select Options','hello-elementor-child'); ?></a> 
            <?php }else{?>
                <a href="<?php echo esc_url(get_permalink( $product->get_id() ));?>" class="sv-shop-btn"><?php echo esc_html__('Shop Now','hello-elementor-child'); ?></a> 
                <?php }?>
            <?php }?>
        <div class="sv-product-reactions">
            <?php echo do_shortcode('[yith_wcwl_add_to_wishlist]');?>
            <?php if ( $product && $product_type == 'simple' ) {?>
                <button class="sv-product-reaction quick-view-popup quick-view-btn open-popup" data-id="popup_2" data-animation="scale" data-product_id="<?php echo esc_attr($product->get_id());?>">
                    <svg width="20" height="14" viewBox="0 0 20 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19.8904 6.2291C19.8459 6.16695 18.7806 4.69039 17.0511 3.19715C15.0734 1.48949 12.642 0.125 10.0391 0.125C7.43086 0.125 4.98188 1.49508 2.99047 3.19496C1.24215 4.68734 0.157929 6.16285 0.112617 6.22492C0.039435 6.32521 0 6.44615 0 6.57029C0 6.69444 0.039435 6.81538 0.112617 6.91567C0.157929 6.97774 1.24215 8.45328 2.99047 9.94563C4.9675 11.6332 7.41863 13.0156 10.0391 13.0156C12.6546 13.0156 15.088 11.6385 17.0511 9.94352C18.7806 8.45028 19.8459 6.97367 19.8904 6.91156C19.9617 6.81205 20 6.69272 20 6.57033C20 6.44793 19.9617 6.32861 19.8904 6.2291ZM3.77082 9.07102C2.60859 8.08184 1.74312 7.08063 1.32961 6.57051C1.79086 6.00238 2.81484 4.82418 4.19164 3.72524C3.74957 4.60113 3.51563 5.5691 3.51563 6.57031C3.51563 7.5727 3.75012 8.5418 4.19316 9.41852C4.05048 9.30501 3.90968 9.18916 3.77082 9.07102ZM9.85547 11.8403C6.9893 11.7446 4.6875 9.41754 4.6875 6.57031C4.6875 3.64777 7.12906 1.21145 10.2175 1.30023C13.043 1.39465 15.3125 3.72227 15.3125 6.57031C15.3125 9.47789 12.9231 11.9308 9.85547 11.8403ZM15.8005 9.45715C16.2377 8.58817 16.4844 7.60758 16.4844 6.57031C16.4844 5.53199 16.2372 4.55047 15.7993 3.68086C16.8489 4.51793 17.8723 5.56227 18.6753 6.57012C18.2181 7.14317 17.19 8.3452 15.8005 9.45715Z" fill="white"/>
                        <path d="M10.0273 3.64062C8.44656 3.64062 7.01953 4.92035 7.01953 6.57031C7.01953 8.22043 8.44676 9.5 10.0273 9.5C11.6428 9.5 12.957 8.18574 12.957 6.57031C12.957 4.95488 11.6428 3.64062 10.0273 3.64062ZM10.0273 8.32813C9.03215 8.32813 8.19141 7.52316 8.19141 6.57031C8.19141 5.61746 9.03215 4.8125 10.0273 4.8125C10.9966 4.8125 11.7852 5.60105 11.7852 6.57031C11.7852 7.53957 10.9966 8.32813 10.0273 8.32813Z" fill="white"/>
                    </svg>
                </button>
            <?php }?>
            <?php if ( $product && $product_type == 'simple' ) {
                    if ( $product->is_in_stock() || $product->backorders_allowed() ) { ?>
                    <button class="sv-product-reaction quick-cart" data-product_id="<?php echo esc_attr($product->get_id());?>">
                    <svg width="25" height="24" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M23.1821 4.92207L23.182 4.92206C23.1459 4.91507 23.1093 4.91152 23.0726 4.91145C23.0725 4.91145 23.0724 4.91145 23.0723 4.91145L5.9701 4.91145H5.53928L5.47559 4.48536L5.20472 2.6732L5.20405 2.66872L5.20407 2.66872C5.06992 1.71212 4.2517 1.00037 3.28574 1C3.28572 1 3.2857 1 3.28568 1C3.28565 1 3.28561 1 3.28558 1L1.0835 1C0.761231 1 0.5 1.26123 0.5 1.5835C0.5 1.90577 0.761231 2.167 1.0835 2.167H3.28712H3.28844V2.667C3.35499 2.66652 3.41939 2.69056 3.46935 2.73453C3.51931 2.7785 3.55133 2.83932 3.55931 2.90539L23.1821 4.92207ZM23.1821 4.92207C23.498 4.98322 23.7047 5.28857 23.6443 5.60452C23.6442 5.60495 23.6442 5.60538 23.6441 5.60581L21.8818 14.3902C21.8817 14.3906 21.8816 14.3909 21.8816 14.3913C21.6444 15.5454 20.6278 16.3732 19.4495 16.3717H19.4488H8.17848C6.95418 16.3674 5.91387 15.4757 5.72223 14.2666L4.05474 2.83777L23.1821 4.92207ZM15.9832 20.6888C15.9845 19.4136 17.0192 18.3808 18.2944 18.382C19.5683 18.3848 20.6 19.4176 20.6012 20.6915V20.6932C20.5999 21.9685 19.5652 23.0012 18.29 23C17.0147 22.9987 15.982 21.964 15.9832 20.6888ZM6.40393 20.7847C6.35256 19.5136 7.33883 18.441 8.60852 18.3847C9.8434 18.3853 10.86 19.3579 10.9141 20.5926C10.9698 21.8631 9.9878 22.9387 8.71887 23H8.66712C7.44564 22.9768 6.45328 22.0059 6.40393 20.7847Z" stroke="white"/>
                    </svg>
                    </button>
                    <?php $nonce = wp_create_nonce( 'quick_cart_nonce' ); ?>
                    <input type="hidden" name="quick_cart_nonce" id="quick_cart_nonce" value="<?php echo esc_attr( $nonce ); ?>">
                <?php 
                    }
                }      
                ?>
        </div>
        <div id="success-message" class="success-message">
            <?php //wc_print_notice( 'Product added to the cart.', 'notice' ); // Add your custom notice message?>
        </div>
  </div>
</li>