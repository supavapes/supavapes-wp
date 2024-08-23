<?php
$checkout_popup_heading = get_field('checkout_popup_heading','option');
$checkout_popup_sub_heading = get_field('checkout_popup_sub_heading','option');
?>
<div class="checkout-prevent-popup">
	<div class="overlay"></div>
	<div class="checkout-prevent-popup-content">
		<span class="checkout-prevent-popup-close">
			<svg width="23" height="23" viewBox="0 0 23 23" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M0.715756 20.4308L20.1773 0.969284L22.2927 3.08467L2.83114 22.5462L0.715756 20.4308ZM0.480713 2.80262L2.50208 0.78125L22.4807 20.7599L20.4593 22.7812L0.480713 2.80262Z" fill="white"></path>
			</svg>				
		</span>
		<div class="checkout-prevent-popup-content-wrap">
			<div class="checkout-prevent-popup-content-detail">
				<div class="checkout-prevent-popup-left">
					<?php if(!empty($checkout_popup_heading)) {?>
						<h2><?php echo esc_html($checkout_popup_heading,'hello-elementor-child'); ?></h2>
					<?php }?>
					<?php if(!empty($checkout_popup_sub_heading)){?>	
						<p><?php echo esc_html($checkout_popup_sub_heading,'hello-elementor-child'); ?></p>
					<?php }?>	
					<form>
						<div class="form-control">
							<div class="form-input">
								<input type="text" placeholder="first name" class="failur-attempt-fname" value="<?php echo esc_attr(WC()->checkout->get_value( 'billing_first_name' )); ?>">
							</div>
							<div class="form-input">
								<input type="text" placeholder="last name" class="failur-attempt-lname" value="<?php echo esc_attr(WC()->checkout->get_value( 'billing_last_name' )); ?>">
							</div>							
						</div>
						<div class="form-control">
							<div class="form-input">
								<input type="email" placeholder="Your Email Address" class="failur-attempt-email" value="<?php echo esc_attr(WC()->checkout->get_value( 'billing_email' )); ?>">
							</div>
							<div class="form-input">
								<input type="tel" placeholder="Your phone Number" class="failur-attempt-tel" value="<?php echo esc_attr(WC()->checkout->get_value( 'billing_phone' )); ?>">					
							</div>
							</div>
						<div class="form-control">
							<textarea placeholder="Add Your Comments"></textarea>
						</div>
						<div class="form-group">
							<input type="checkbox" id="send-me-copy">
							<label for="send-me-copy"><?php esc_html_e('Send me a copy','hello-elementor-child'); ?></label>
						</div>
						<button type="submit" class="multiple-payment-attempt-failur-submit"><span><?php esc_html_e('Submit','hello-elementor-child'); ?></span>
							<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path fill-rule="evenodd" clip-rule="evenodd" d="M17.1685 6.83144L9.2485 12.3303L0.964495 9.56858C0.386257 9.37544 -0.00330583 8.83293 2.11451e-05 8.22343C0.0033919 7.61394 0.39742 7.07475 0.97789 6.88835L22.1573 0.0678196C22.6607 -0.0940205 23.2133 0.038796 23.5872 0.412775C23.9612 0.786754 24.094 1.3393 23.9322 1.84276L17.1116 23.0221C16.9252 23.6026 16.386 23.9966 15.7766 24C15.1671 24.0033 14.6245 23.6137 14.4314 23.0355L11.6563 14.7114L17.1685 6.83144Z" fill="#EC4E34"></path>
							</svg>
						</button>
					</form>		
				</div>
				<div class="checkout-prevent-popup-right">
					<?php echo wp_kses_post(woocommerce_mini_cart()); ?>
				</div>
			</div>
		</div>
	</div>
</div> 