<?php
/**
 * Reservation cancellation request approved email.
 *
 * @package Easy_Reservations
 * @subpackage Easy_Reservations/admin/templates/emails
 */

defined( 'ABSPATH' ) || exit;

/* translators: 1: %s: order item name, 2: order ID, 3: order date */
$opening_paragraph = sprintf( __( 'This is to update you that your request for cancelling reservation for %1$s in order #%2$s that you placed on %3$s has been approved. The details about the reservation item are as follows:', 'hello-elementor-child' ), $item_data->first_name, $item_data->last_name);
$order_item        = $item_data->item;

/**
 * This hook runs on the custom email headers.
 *
 * This hook helps in customizing email header text.
 *
 * @param string $email_heading Email heading.
 * @since 1.0.0
 */
do_action( 'woocommerce_email_header', $email_heading );
?>
<p><?php echo esc_html( $opening_paragraph ); ?></p>
<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee;" bordercolor="#eee">
	<tbody>
		<tr>
			<th scope="row" style="text-align:left; border: 1px solid #eee;">First Name</th>
			<td style="text-align:left; border: 1px solid #eee;">
				<?php
				// Print the item subtotal.
				echo $item_data->first_name;
				?>
			</td>
		</tr>
		<tr>
			<th scope="row" style="text-align:left; border: 1px solid #eee;">Last Name</th>
			<td style="text-align:left; border: 1px solid #eee;">
				<?php
				// Print the item subtotal.
				echo $item_data->last_name;
				?>
			</td>
		</tr>
	</tbody>
</table>
<p><?php esc_html_e( 'This is a system generated email. Please DO NOT respond to it.', 'hello-elementor-child' ); ?></p>
<p>
	<?php
	/* translators: 1: %s: order view URL */
	echo wp_kses_post( make_clickable( sprintf( __( 'You can view this order in the dashboard here: %s', 'hello-elementor-child' ), $item_data->order_view_url ) ) );
	?>
</p>
<?php
/**
 * This hook runs on the custom email footers.
 *
 * This hook helps in customizing email footer text.
 *
 * @since 1.0.0
 */
do_action( 'woocommerce_email_footer' );