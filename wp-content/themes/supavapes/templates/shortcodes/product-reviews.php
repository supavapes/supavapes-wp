<?php
$atts = get_query_var('shortcode_atts', array(
    'ids' => ''
));
$product_ids = explode(',', $atts['ids']);
	if (empty($product_ids)) {
		return esc_html_e('Please provide product IDs.');
	}
	
	?>
	<div class="review-slider">
		<?php
		foreach ($product_ids as $product_id) {
			$product = wc_get_product($product_id);
			if ($product) {
				$p_title = $product->get_name();
				$price = $product->get_price_html();
				$image = $product->get_image();
				$rating = $product->get_average_rating();
				$reviews = get_comments(
					array(
						'post_id' => $product_id,
						'status' => 'approve',
						'type' => 'review'
					)
				);
				$product_url = get_permalink($product_id);
				$comment_content = $reviews[0]->comment_content;
				?>
				<div class="slider-box">
					<div class="sv-product-review">
						<p class="add-read-more show-less-content dfdsf"><?php echo esc_html($comment_content,'hello-elementor-child'); ?></p>
						<div class="sv-product-review-from">
							<h3><?php echo esc_html(get_comment_author($reviews[0]->comment_ID)); ?></h3>
							<ul class="sv-stars">
								<?php $rating_num = intval($rating); ?>
								<li>
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
								</li>
							</ul>
						</div>
					</div>
					<a href="<?php echo esc_url($product_url); ?>" class="sv-product">
						<div class="sv-product-img"><?php echo wp_kses_post($image); ?></div>
						<span class="divider"></span>
						<div class="sv-product-detail">
							<h4 class="sv-product-name"><?php echo esc_html($p_title,'hello-elementor-child'); ?></h4>
							<p class="sv-product-price"><?php echo wp_kses_post($price); ?></p>
						</div>
					</a>
				</div>
				    <?php
			    }
		    }
		?>
	</div>