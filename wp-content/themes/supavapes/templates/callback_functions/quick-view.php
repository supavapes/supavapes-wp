<?php
global $product;
	check_ajax_referer('quick_view_nonce', 'security');
    if ( isset($_POST['product_id'] ) ) {
        $product_id = intval( $_POST['product_id'] );
		$product = wc_get_product( $product_id );
		$rating = $product->get_average_rating();
       ?>
	   <?php if ( $product && method_exists( $product, 'get_type' ) ) {
				// $product_type = $product->get_type(); 
				?>
	   <button class="quick-view-close">
			<svg width="23" height="23" viewBox="0 0 23 23" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M0.715756 20.4308L20.1773 0.969284L22.2927 3.08467L2.83114 22.5462L0.715756 20.4308ZM0.480713 2.80262L2.50208 0.78125L22.4807 20.7599L20.4593 22.7812L0.480713 2.80262Z" fill="white"></path>
            </svg>
		</button>
			<div class="product-popup-main">
				<div class="product-gallery">
				<div class="slider-for">
				<?php
					$gallery_image_ids = $product->get_gallery_image_ids();
					if (!empty($gallery_image_ids)) {
						foreach ($gallery_image_ids as $image_id) {
							$image_url = wp_get_attachment_url($image_id);
							$image_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
							echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($image_alt) . '">';
						}
					} else {
						$thumbnail_id = $product->get_image_id();
						$thumbnail_url = wp_get_attachment_url($thumbnail_id);
						$thumbnail_alt = get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true);
						echo '<img src="' . esc_url($thumbnail_url) . '" alt="' . esc_attr($thumbnail_alt) . '">';
					}
				?>
				</div>
				<div class="slider-nav">
					<?php
						$gallery_image_ids = $product->get_gallery_image_ids();
						if (!empty($gallery_image_ids)) {
							foreach ($gallery_image_ids as $image_id) {
								$image_url = wp_get_attachment_url($image_id);
								$image_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
								
								echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($image_alt) . '">';
							}
						} else {
							echo esc_html('No gallery images found for this product.','hello-elementor-child');
						}
					?>
				</div>
				</div>
				<div class="product-summery">
				<div class="product-title"><?php echo esc_html(get_the_title($product_id),'hello-elementor-child');?></div>
				<div class="product-price">
					<?php
						if ($product->is_type('simple')) {
							if ($product->get_sale_price()) {
								echo esc_html("$" . $product->get_sale_price());
							} else {
								echo esc_html("$" . $product->get_regular_price());
							}
						}else if($product->is_type('variable')){
							$available_variations = $product->get_available_variations();
							if (!empty($available_variations)) {
							$variation_id = $available_variations[0]['variation_id'];
							$variation_product = wc_get_product($variation_id);
							$price = $variation_product->get_price();
							echo esc_html("$".$price);
							} 
						}
					?>
				</div>
				<?php
				if ($product->is_in_stock()) {
					echo wp_kses_post(wc_get_stock_html($product));
				?>
				<div class="product-ratting">
				<?php $rating_num = intval($rating); ?>
					<ul class="customer-ratting">
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
				<?php $trimmed_content = wp_trim_words($product->get_description(), 40, '...'); ?>
				<div class="product-detail"><?php echo wp_kses_post($trimmed_content); ?></div>
				<!-- <a href="<?php //echo esc_url(get_permalink( $product->get_id())); ?>"><?php //echo esc_html__('Read more','hello-elementor-child'); ?></a> -->
				<?php if ($product->is_type('variable')) : ?>
					<div class="product-variation">
						<div class="label">
							<label for="flavours"><?php echo esc_html__('Flavours','hello-elementor-child'); ?></label>
						</div>
						<div class="value">
							<select name="flavours" id="flavours">
								<option value=""><?php echo esc_html__('Choose an option','hello-elementor-child'); ?></option>
								<?php foreach ($available_variations as $variation) : ?>
									<?php
									$variation_id = $variation['variation_id'];
									$variation_qty = $variation['max_qty'];
									foreach ($variation['attributes'] as $attribute_name => $attribute_value) :
										?>
										<option value="<?php echo esc_attr($variation_id); ?>" data-quantity="<?php echo esc_attr($variation_qty); ?>"><?php echo esc_html($attribute_value,'hello-elementor-child'); ?></option>
									<?php endforeach; ?>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
				<?php endif; ?>
				<div class="product-single-variation-wrap">
					<div class="add-to-card-variation">
						<div class="product-quantity quantity">
							<?php if ($product->is_in_stock()) { 
								$backorders_allowed = $product->backorders_allowed();
								$stock_quantity = $product->get_stock_quantity();
								?>
								<button class="qty-count qty-count--minus minus" data-action="minus" type="button">-</button>
								<input type="" id="quantity_663231758773c" class="input-text qty text quick-view-popup-qty" name="quantity" value="1" aria-label="Product quantity" size="4" min="1" max="<?php echo !$backorders_allowed ? $stock_quantity : ''; ?>" step="1" placeholder="" inputmode="numeric" autocomplete="off" readonly>
								<button class="qty-count qty-count--add plus" data-action="add" type="button">+</button>
							<?php } else {
								?>
								<button class="qty-count qty-count--minus minus" data-action="minus" type="button" disabled>-</button>
								<input type="" id="quantity_663231758773c" class="input-text qty text quick-view-popup-qty" name="quantity" value="1" aria-label="Product quantity" size="4" min="1" max="" step="1" placeholder="" inputmode="numeric" autocomplete="off" readonly>
								<button class="qty-count qty-count--add plus" data-action="add" type="button" disabled>+</button>
							<?php } ?>
						</div>
						<p class="stock-message" style="display: none;"><span><?php echo esc_html__('Out of Stock','hello-elementor-child'); ?></span></p>
							<button type="submit" class="single_add_to_cart_button button alt wc-variation-selection-needed quick-view-add-to-cart" data-product_id="<?php echo esc_attr($product_id); ?>">
								<span><?php echo esc_html__('Add to Cart','hello-elementor-child'); ?></span>
								<svg width="25" height="24" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M18.2949 17.882C16.7436 17.8806 15.4847 19.137 15.4832 20.6883C15.4817 22.2396 16.7381 23.4985 18.2895 23.5C19.8408 23.5015 21.0996 22.2451 21.1012 20.6937V20.691C21.0997 19.1413 19.8446 17.8851 18.2949 17.882ZM23.2771 4.43118C23.2099 4.41816 23.1416 4.41156 23.0731 4.41145H5.9701L5.69922 2.59928C5.53046 1.39579 4.50102 0.50037 3.28574 0.5H1.0835C0.485089 0.5 0 0.985089 0 1.5835C0 2.18191 0.485089 2.667 1.0835 2.667H3.28844C3.35499 2.66652 3.41939 2.69056 3.46935 2.73453C3.51931 2.7785 3.55133 2.83932 3.55931 2.90539L5.22789 14.3417C5.45665 15.7949 6.70665 16.867 8.17773 16.8717H19.4488C20.8652 16.8736 22.087 15.8781 22.3716 14.4907L24.135 5.7008C24.2487 5.11329 23.8646 4.54488 23.2771 4.43118ZM11.4136 20.5707C11.3477 19.0649 10.1049 17.8795 8.59759 17.8847C7.04752 17.9474 5.8417 19.2548 5.90434 20.8048C5.96444 22.2922 7.17433 23.4743 8.66261 23.5H8.73033C10.2802 23.4321 11.4815 22.1206 11.4136 20.5707Z" fill="#EC4E34">
									</path>
								</svg>
							</button>
							
						<?php } else {
							?>
							<button type="submit" class="single_add_to_cart_button button alt disabled wc-variation-selection-needed quick-view-add-to-cart" data-product_id="<?php echo esc_attr($product_id); ?>" disabled><span><?php echo esc_html__('Add to Cart','hello-elementor-child'); ?></span>
									<svg width="25" height="24" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M18.2949 17.882C16.7436 17.8806 15.4847 19.137 15.4832 20.6883C15.4817 22.2396 16.7381 23.4985 18.2895 23.5C19.8408 23.5015 21.0996 22.2451 21.1012 20.6937V20.691C21.0997 19.1413 19.8446 17.8851 18.2949 17.882ZM23.2771 4.43118C23.2099 4.41816 23.1416 4.41156 23.0731 4.41145H5.9701L5.69922 2.59928C5.53046 1.39579 4.50102 0.50037 3.28574 0.5H1.0835C0.485089 0.5 0 0.985089 0 1.5835C0 2.18191 0.485089 2.667 1.0835 2.667H3.28844C3.35499 2.66652 3.41939 2.69056 3.46935 2.73453C3.51931 2.7785 3.55133 2.83932 3.55931 2.90539L5.22789 14.3417C5.45665 15.7949 6.70665 16.867 8.17773 16.8717H19.4488C20.8652 16.8736 22.087 15.8781 22.3716 14.4907L24.135 5.7008C24.2487 5.11329 23.8646 4.54488 23.2771 4.43118ZM11.4136 20.5707C11.3477 19.0649 10.1049 17.8795 8.59759 17.8847C7.04752 17.9474 5.8417 19.2548 5.90434 20.8048C5.96444 22.2922 7.17433 23.4743 8.66261 23.5H8.73033C10.2802 23.4321 11.4815 22.1206 11.4136 20.5707Z" fill="#EC4E34">
										</path>
									</svg>
								</button>
								<span class="popup-out-of-stock"><?php echo esc_html__('Out of stock','hello-elementor-child'); ?></span>
						<?php } ?>
						<a href="<?php echo esc_url(get_permalink($product_id)); ?>" class="btn view-all-btn shop-all-btn">
							<svg width="19" height="19" viewBox="0 0 19 19" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M17.135 0.405758L4.74175 0.405759C4.41354 0.411464 4.1007 0.545847 3.87061 0.77997C3.64052 1.01409 3.51159 1.32922 3.51159 1.65748C3.51159 1.98574 3.64052 2.30087 3.87061 2.53499C4.1007 2.76911 4.41354 2.9035 4.74175 2.9092L14.1128 2.9092L1.04951 15.9725C0.814736 16.2073 0.682839 16.5257 0.682839 16.8578C0.682839 17.1898 0.814736 17.5082 1.04952 17.743C1.28429 17.9778 1.60272 18.1097 1.93475 18.1097C2.26678 18.1097 2.58521 17.9778 2.81998 17.743L15.8833 4.67967L15.8833 14.0508C15.8804 14.217 15.9107 14.3821 15.9723 14.5365C16.0339 14.6909 16.1256 14.8315 16.2421 14.95C16.3587 15.0686 16.4976 15.1628 16.6509 15.227C16.8042 15.2913 16.9688 15.3244 17.135 15.3244C17.3013 15.3244 17.4658 15.2913 17.6191 15.227C17.7724 15.1628 17.9114 15.0686 18.0279 14.95C18.1444 14.8315 18.2362 14.6909 18.2978 14.5365C18.3594 14.3821 18.3896 14.217 18.3868 14.0508L18.3868 1.65748C18.3867 1.32552 18.2548 1.00717 18.0201 0.772434C17.7853 0.537701 17.467 0.405808 17.135 0.405758Z" fill="white"/>
							</svg>
							<span><?php echo esc_html__('View Details','hello-elementor-child'); ?></span>
						</a>
					</div>
				</div>
				</div>
			</div>
			<?php } ?>
			<div id="success-message" class="success-message"></div>
	   <?php
    }