<?php
/**
 * Order details
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/order/order-details.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.5.0
 *
 * @var bool $show_downloads Controls whether the downloads table should be rendered.
 */


 $order = wc_get_order( $order_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
 if ( ! $order ) {
	 return;
 }
 
 $set_order_status = get_field('set_order_status', 'option');
 $order_items           = $order->get_items( apply_filters( 'woocommerce_purchase_order_item_types', 'line_item' ) );
 $show_purchase_note    = $order->has_status( apply_filters( 'woocommerce_purchase_note_order_statuses', array( 'completed', 'processing' ) ) );
 $show_customer_details = is_user_logged_in() && $order->get_user_id() === get_current_user_id();
 $downloads             = $order->get_downloadable_items();
 
 if ( $show_downloads ) {
	 wc_get_template(
		 'order/order-downloads.php',
		 array(
			 'downloads'  => $downloads,
			 'show_title' => true,
		 )
	 );
 }
 
 if ( ! empty( $order ) && !( is_checkout() && ! empty( is_wc_endpoint_url('order-received') ) ) ) {
	 $current_order_status = 'wc-' . $order->get_status(); // WooCommerce prefixes statuses with 'wc-'
	 
	 // Check if the current order status is in the allowed statuses array
	 if ( in_array( $current_order_status, $set_order_status ) ) {
		 $support_request_disable_in_days = get_field('support_request_disable_in_days', 'option');
		 $order_date = $order->get_date_created();
		 $current_date = new DateTime();
		 $interval = $current_date->diff( $order_date );
		 $days_since_order = $interval->days;
		 // Determine if the support request button should be disabled
		 $is_support_disabled = $days_since_order > $support_request_disable_in_days;
		 ?>
		<a 
            href="javascript:void(0);" 
            class="customer-support button <?php echo $is_support_disabled ? 'disabled' : ''; ?>" 
            data-order_id="<?php echo $order_id; ?>"
        >
            <?php esc_html_e('Customer Support', 'hello-elementor-child'); ?>
            <?php if ($is_support_disabled): ?>
                <span class="support-disabled-message"><?php esc_html_e('This order is no longer available for support request', 'hello-elementor-child'); ?></span>
            <?php endif; ?>
        </a>
		 <?php
	 }
 }
 ?>
<section class="woocommerce-order-details">
	<?php do_action( 'woocommerce_order_details_before_order_table', $order ); ?>
	<h2 class="woocommerce-order-details__title"><?php esc_html_e( 'Order details', 'woocommerce' ); ?></h2>
	<table class="woocommerce-table woocommerce-table--order-details shop_table order_details">
		<thead>
			<tr>
				<th class="woocommerce-table__product-name product-name"><?php esc_html_e( 'Product', 'woocommerce' ); ?></th>
				<th class="woocommerce-table__product-table product-total"><?php esc_html_e( 'Total', 'woocommerce' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			do_action( 'woocommerce_order_details_before_order_table_items', $order );
			foreach ( $order_items as $item_id => $item ) {
				$product = $item->get_product();
				wc_get_template(
					'order/order-details-item.php',
					array(
						'order'              => $order,
						'item_id'            => $item_id,
						'item'               => $item,
						'show_purchase_note' => $show_purchase_note,
						'purchase_note'      => $product ? $product->get_purchase_note() : '',
						'product'            => $product,
					)
				);
			}
			do_action( 'woocommerce_order_details_after_order_table_items', $order );
			?>
		</tbody>
		<tfoot>
			<?php
			foreach ( $order->get_order_item_totals() as $key => $total ) {
				?>
					<tr>
						<th scope="row"><?php echo esc_html( $total['label'] ); ?></th>
						<td><?php echo wp_kses_post( $total['value'] ); ?></td>
					</tr>
					<?php
			}
			?>
			<?php if ( $order->get_customer_note() ) : ?>
				<tr>
					<th><?php esc_html_e( 'Note:', 'woocommerce' ); ?></th>
					<td><?php echo wp_kses_post( nl2br( wptexturize( $order->get_customer_note() ) ) ); ?></td>
				</tr>
			<?php endif; ?>
		</tfoot>
	</table>
	<?php do_action( 'woocommerce_order_details_after_order_table', $order ); ?>
</section>
<div class="deal-popup customer-support-modal" style="display: none;">
    <div class="overlay"></div>
    <div class="deal-popup-content">
	<span class="deal-popup-close"><svg width="23" height="23" viewBox="0 0 23 23" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M0.715756 20.4308L20.1773 0.969284L22.2927 3.08467L2.83114 22.5462L0.715756 20.4308ZM0.480713 2.80262L2.50208 0.78125L22.4807 20.7599L20.4593 22.7812L0.480713 2.80262Z" fill="white"></path>
            </svg>
            </span>
		<div class="deal-popup-content-box">
			<div class="customer-support-form">
			<div class="select-order-item-box">
				<h3 class="select-oreder-title"><?php esc_html_e('Select Order items','hello-elementor-child'); ?></h3>
				<div class="multi-select-box">
					<div class="selected-items"><span class="placeholder"><?php esc_html_e('Select Items','hello-elementor-child'); ?></span></div>
						<div class="options-container" style="display: none;">

						</div>
						<div class="error-message" id="item-selection-error" style="color: red; display: none;"></div>
					</div>
				</div>
				<div class="upload-img-box">
					<h3 class="select-oreder-title"><?php esc_html_e('Upload product images','hello-elementor-child'); ?></h3>
					<div class="upload-img-list">
						<div class="customer-support-img-box">
							<img alt="" src="/wp-content/uploads/2024/07/placeholedr-img.png" class="customer-support-img" height="150" width="150">
							<div class="upload-icon">
								<img decoding="async" src="/wp-content/uploads/2024/07/upload.png" alt="Upload Icon" class="uploadImgIcon">
								<input type="file" class="fileImgInput" accept="image/*" style="display: none;">
							</div>
						</div>
						<div class="customer-support-img-box">
							<img alt="" src="/wp-content/uploads/2024/07/placeholedr-img.png" class="customer-support-img" height="150" width="150">
							<div class="upload-icon">
								<img decoding="async" src="/wp-content/uploads/2024/07/upload.png" alt="Upload Icon" class="uploadImgIcon">
								<input type="file" class="fileImgInput" accept="image/*" style="display: none;">
							</div>
						</div>
						<div class="customer-support-img-box customer-support-img-input-box">
							<div class="upload-icon">
								<img decoding="async" src="/wp-content/uploads/2024/07/upload.png" alt="Upload Icon" class="uploadImgIcon">
								<input type="file" class="fileImgInput" accept="image/*" style="display: none;">
							</div>
						</div>
					</div>
					<div class="error-message" id="image-upload-error" style="color: red; display: none;"></div>
				</div>
				<div class="additional-info-box">
					<textarea id="additional-info" placeholder=<?php esc_html_e("Additional information"); ?> ></textarea>
				</div>
				<button type="submit" class="button" id="customer-support-submit">
                    <span><?php esc_html_e('Submit','hello-elementor-child'); ?></span>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M17.1685 6.83144L9.2485 12.3303L0.964495 9.56858C0.386257 9.37544 -0.00330583 8.83293 2.11451e-05 8.22343C0.0033919 7.61394 0.39742 7.07475 0.97789 6.88835L22.1573 0.0678196C22.6607 -0.0940205 23.2133 0.038796 23.5872 0.412775C23.9612 0.786754 24.094 1.3393 23.9322 1.84276L17.1116 23.0221C16.9252 23.6026 16.386 23.9966 15.7766 24C15.1671 24.0033 14.6245 23.6137 14.4314 23.0355L11.6563 14.7114L17.1685 6.83144Z" fill="#EC4E34"></path>
                    </svg>
                </button>
			</div>				
		</div>    
    </div>
</div>
<div class="sv-email-modal" id="sv-email-modal">
	<div class="overlay"></div>
    <div class="sv-email-modal__dialog">
        <div class="sv-email-modal__content">
            <div class="sv-email-modal__header">
                <img src="/wp-content/themes/hello-elementor-child/assets/images/check-email-img.png" alt="sv-email" class="sv-email-modal__image">
                <button type="button" class="sv-email-modal__close-button" aria-label="Close">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                        <path d="M9.88542 8L15.6094 2.27604C16.1302 1.75521 16.1302 0.911458 15.6094 0.390625C15.0885 -0.130208 14.2448 -0.130208 13.724 0.390625L8 6.11458L2.27604 0.390625C1.75521 -0.130208 0.911458 -0.130208 0.390625 0.390625C-0.130208 0.911458 -0.130208 1.75521 0.390625 2.27604L6.11458 8L0.390625 13.724C-0.130208 14.2448 -0.130208 15.0885 0.390625 15.6094C0.651042 15.8698 0.992187 16 1.33333 16C1.67448 16 2.01563 15.8698 2.27604 15.6094L8 9.88542L13.724 15.6094C13.9844 15.8698 14.3255 16 14.6667 16C15.0078 16 15.349 15.8698 15.6094 15.6094C16.1302 15.0885 16.1302 14.2448 15.6094 13.724L9.88542 8Z" fill="black"/>
                    </svg>
                </button>
            </div>
            <div class="sv-email-modal__body">
                <div class="sv-email-modal__heading-info">
                    <h2 class="sv-email-modal__title">Check your emails</h2>
                    <p class="sv-email-modal__description">We sent over your shortlist. Thank you for using Reedsy's
                    Literary Agents Directory, happy publishing!</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
/**
 * Action hook fired after the order details.
 *
 * @since 4.4.0
 * @param WC_Order $order Order data.
 */
do_action( 'woocommerce_after_order_details', $order );

if ( $show_customer_details ) {
	wc_get_template( 'order/order-details-customer.php', array( 'order' => $order ) );
}
