<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$plugin_version = '1.2.0';
?>
<div id="dotsstoremain">
    <div class="all-pad">
        <header class="dots-header">
            <div class="dots-logo-main">
                <img src="<?php echo esc_url( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'images/WSFL.png' ); ?>"
                     alt="WSFL">
            </div>
            <div class="dots-header-right">
                <div class="logo-detail">
                    <strong><?php esc_html_e( 'Quick Bulk Variations Checkout for WooCommerce' ); ?></strong>
                    <span><?php esc_html_e( 'Free Version ', 'woocommerce-quick-cart-for-multiple-variations' ); ?><?php echo esc_html( $plugin_version ); ?></span>
                </div>
                <div class="button-dots">
                    <span class="support_dotstore_image"><a target="_blank"
                                                            href="<?php echo esc_url( "https://www.thedotstore.com/support/" ); ?>">
                            <img src="<?php echo esc_url( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'images/support_new.png' ); ?>"
                                 alt="Support New"></a>
                    </span>
                </div>
            </div>
			<?php
			$site_url   = "admin.php?page=woocommerce-quick-cart-for-multiple-variations&tab=";
			$plugin_tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING );

            if (isset($plugin_tab) && $plugin_tab === 'wqcmv_variant_purchase_extended') {    
				$wc_variant_purchase_extended = "active";
			} else {
				$wc_variant_purchase_extended = "";
			}

            if (isset($plugin_tab) && $plugin_tab === 'wqcmv_variant_restock_notification') {     
				$wc_variant_restock_notification = "active";
			} else {
				$wc_variant_restock_notification = "";
			}

            if (isset($plugin_tab) && $plugin_tab === 'wqcmv_variant_purchase_extended_get_started_method') {     
				$wc_variant_purchase_get_started_method = "active";
			} else {
				$wc_variant_purchase_get_started_method = "";
			}

            if (isset($plugin_tab) && $plugin_tab === 'introduction_variant_extended') {     
				$introduction_variant_purchase = "active";
			} else {
				$introduction_variant_purchase = "";
			}
			?>
            <div class="dots-menu-main">
                <nav>
                    <ul>
                        <li>
                            <a class="dotstore_plugin <?php echo esc_attr( $wc_variant_purchase_extended ); ?>"
                               href="<?php echo esc_url( $site_url . 'wqcmv_variant_purchase_extended' ); ?>"><?php esc_html_e( 'General Settings', 'woocommerce-quick-cart-for-multiple-variations' ); ?></a>
                        </li>
                        <li>
                            <a class="dotstore_plugin <?php echo esc_attr( $wc_variant_restock_notification ); ?>"
                               href="<?php echo esc_url( $site_url . 'wqcmv_variant_restock_notification' ); ?>"><?php esc_html_e( 'Restock Notifications', 'woocommerce-quick-cart-for-multiple-variations' ); ?></a>
                        </li>
                        <li>
                            <a class="dotstore_plugin <?php echo esc_attr( $wc_variant_purchase_get_started_method ); ?> <?php echo esc_attr( $introduction_variant_purchase ); ?>"
                               href="<?php echo esc_url( $site_url . 'wqcmv_variant_purchase_extended_get_started_method' ); ?>"><?php esc_html_e( 'About Plugin', 'woocommerce-quick-cart-for-multiple-variations' ); ?></a>
                            <ul class="sub-menu">
                                <li>
                                    <a class="dotstore_plugin <?php echo esc_attr( $wc_variant_purchase_get_started_method ); ?>"
                                       href="<?php echo esc_url( $site_url . 'wqcmv_variant_purchase_extended_get_started_method' ); ?>"><?php esc_html_e( 'Getting Started', 'woocommerce-quick-cart-for-multiple-variations' ); ?></a>
                                </li>
                                <li><a class="dotstore_plugin <?php echo esc_attr( $introduction_variant_purchase ); ?>"
                                       href="<?php echo esc_url( $site_url . 'introduction_variant_extended' ); ?>"><?php esc_html_e( 'Quick info', 'woocommerce-quick-cart-for-multiple-variations' ); ?></a>
                                </li>
                            </ul>
                        </li>
                        <li>
                            <a class="dotstore_plugin"
                               href="#"><?php esc_html_e( 'Dotstore', 'woocommerce-quick-cart-for-multiple-variations' ); ?></a>
                            <ul class="sub-menu">
                                <li><a target="_blank"
                                       href="<?php echo esc_url( "http://www.thedotstore.com/woocommerce-plugins/" ); ?>"><?php esc_html_e( 'WooCommerce Plugins', 'woocommerce-quick-cart-for-multiple-variations' ); ?></a>
                                </li>
                                <li><a target="_blank"
                                       href="<?php echo esc_url( "http://www.thedotstore.com/wordpress-plugins/" ); ?>"><?php esc_html_e( 'Wordpress Plugins', 'woocommerce-quick-cart-for-multiple-variations' ); ?></a>
                                </li>
                                <br>
                                <li><a target="_blank"
                                       href="<?php echo esc_url( "https://www.thedotstore.com/support" ); ?>"><?php esc_html_e( 'Contact Support', 'woocommerce-quick-cart-for-multiple-variations' ); ?></a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </nav>
            </div>
        </header>