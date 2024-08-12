<?php
/**
 * Order details table shown in emails.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-order-details.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 3.7.0
 */

defined( 'ABSPATH' ) || exit;

$text_align = is_rtl() ? 'right' : 'left';
global $woocommerce;
?>
<h2>
	<?php
    $before = '';
    $after  = '';
	/* translators: %s: Order ID. */
	echo wp_kses_post( $before . sprintf( __( 'Cart Details', 'woocommerce' ) ) );
	?>
</h2>
<div style="margin-bottom: 40px;">
	<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
		<thead>
			<tr>
				<th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php esc_html_e( 'Product', 'woocommerce' ); ?></th>
				<th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php esc_html_e( 'Quantity', 'woocommerce' ); ?></th>
				<th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php esc_html_e( 'Price', 'woocommerce' ); ?></th>
			</tr>
		</thead>
		<tbody>
        <?php
            $items = $woocommerce->cart->get_cart();
            foreach($items as $item => $values) :
                $_product = wc_get_product( $values['data']->get_id() ); 
            ?>
            <tr class="">
                <td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>; vertical-align: middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word;">
                    <?php
                    // Product name with link.
                    echo '<a href="' . get_permalink( $_product->get_id() ) . '">' . wp_kses_post( $_product->get_title() ) . '</a>';
                    ?>
                </td>
                <td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>; vertical-align:middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;">
                    <?php
                    echo wp_kses_post( $values['quantity'] );
                    ?>
                </td>
                <td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>; vertical-align:middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;">
                    <?php 
                    $price = get_post_meta( $values['product_id'], '_price', true );
                    echo wp_kses_post( '$' . $price );
                    ?>
                </td>
            </tr>
            <?php endforeach; ?>
		</tbody>
	</table>
</div>