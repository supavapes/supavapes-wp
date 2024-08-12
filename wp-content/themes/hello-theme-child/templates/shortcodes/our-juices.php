<?php
$atts = get_query_var('shortcode_atts', array(
    'term_ids' => ''
));
global $product;
$term_ids = explode(',', $atts['term_ids']);
?>
<div class="sv-product-tab">
	<div class="sv-product-tab-header">
		<?php
		$first_iteration = true;
		foreach ($term_ids as $term_id) {
			$terms = get_term_by('id', $term_id, 'product_cat');
			if ($terms) {
				$term_name = $terms->name;
				$active_class = $first_iteration ? ' active-link' : '';
				echo '<p class="sv-product-tab-links' . esc_html($active_class) . '" onclick="opentab(\'' . esc_html($term_name) . '\')">' . esc_html($term_name) . '</p>';
				$first_iteration = false;
			} else {
				echo '<p>No term found with ID ' . esc_html($term_id) . '</p>';
			}
		}
		?>
	</div>
	<?php
	$first_iteration_tab = true;
	foreach ($term_ids as $term_id) {
		$terms = get_term_by('id', $term_id, 'product_cat');
		$term_link = get_term_link($terms, 'product_cat');
		if ( ! is_wp_error( $term_link ) && $term_link ) { 
			$sv_term_link = $term_link;
		}else{
			$sv_term_link = "#";
		}
		if ($terms) {
			$term_name = $terms->name;
			$active_class_content = $first_iteration_tab ? ' active-tab' : '';
			$products = new WP_Query(
				array(
					'post_type' => 'product',
					'posts_per_page' => 4,
					'tax_query' => array(
						array(
							'taxonomy' => 'product_cat',
							'field' => 'term_id',
							'terms' => $term_id,
						),
					),
				)
			);
			?>
			<div class="tab-contents<?php echo esc_attr($active_class_content); ?>" id="<?php echo esc_attr($term_name); ?>">
				<div class="sv-our-product-row">
					<div class="sv-product-slider-tab">
						<?php
						if ($products->have_posts()) {
							while ($products->have_posts()) {
								$products->the_post();
								$product_data = wc_get_product(get_the_ID());
								$rating = $product_data->get_average_rating();
								$price = $product_data->get_price_html();
								?>
								<div class="sv-our-product-box">
									<div class="sv-our-product-img">
										<?php the_post_thumbnail('thumbnail', array('class' => 'sv-our-product-img-thumb')); ?>
										<ul class="sv-stars">
											<?php $rating_num = intval($rating); ?>
											<li>
												<?php if ($rating_num === 1) { ?>
													<img
													src="<?php echo esc_url(get_site_url()); ?>/wp-content/uploads/2024/04/1-star.png" />
												<?php } else if ($rating_num === 2) { ?>
													<img
													src="<?php echo esc_url(get_site_url()); ?>/wp-content/uploads/2024/04/2-star.png" />
												<?php } else if ($rating_num === 3) { ?>
													<img
													src="<?php echo esc_url(get_site_url()); ?>/wp-content/uploads/2024/04/3-star.png" />
												<?php } else if ($rating_num === 4) { ?>
													<img
													src="<?php echo esc_url(get_site_url()); ?>/wp-content/uploads/2024/04/4-star.png" />
												<?php } else if ($rating_num === 5) { ?>
													<img
													src="<?php echo esc_url(get_site_url()); ?>/wp-content/uploads/2024/04/5-star.png" />
												<?php } ?>
											</li>
										</ul>
									</div>
									<div class="sv-our-product-detail">
										<h3 class="sv-our-product-title"><?php echo esc_html(get_the_title(),'hello-elementor-child'); ?></h3>
										<p class="sv-our-product-price">
											<p class="sv-product-price"><?php echo wp_kses_post($price); ?></p>
										</p>
									</div>
									<?php 
									if ( $product_data && method_exists( $product_data, 'get_type' ) ) {
										$product_type = $product_data->get_type();
										if( $product_type === 'variable' ){?>
										<a href="<?php echo esc_url(get_the_permalink()); ?>" class="sv-shop-btn"><?php echo esc_html__('Select Options','hello-elementor-child'); ?></a>
									<?php }else{?>
										<a href="<?php echo esc_url(get_the_permalink()); ?>" class="sv-shop-btn"><?php echo esc_html__('Shop Now','hello-elementor-child'); ?></a>
									<?php }?>
									<?php }?>
										<div class="sv-product-reactions">
										<?php //echo do_shortcode('[yith_wcwl_add_to_wishlist]');?>
										<?php $product_type = $product_data->get_type();
										if( $product && $product->is_type( 'simple' ) ){?>
											<button class="sv-product-reaction quick-view-popup quick-view-btn open-popup" data-id="popup_2" data-animation="scale" data-product_id="<?php echo esc_attr(get_the_ID());?>">
											<svg width="20" height="14" viewBox="0 0 20 14" fill="none" xmlns="http://www.w3.org/2000/svg">
												<path d="M19.8904 6.2291C19.8459 6.16695 18.7806 4.69039 17.0511 3.19715C15.0734 1.48949 12.642 0.125 10.0391 0.125C7.43086 0.125 4.98188 1.49508 2.99047 3.19496C1.24215 4.68734 0.157929 6.16285 0.112617 6.22492C0.039435 6.32521 0 6.44615 0 6.57029C0 6.69444 0.039435 6.81538 0.112617 6.91567C0.157929 6.97774 1.24215 8.45328 2.99047 9.94563C4.9675 11.6332 7.41863 13.0156 10.0391 13.0156C12.6546 13.0156 15.088 11.6385 17.0511 9.94352C18.7806 8.45028 19.8459 6.97367 19.8904 6.91156C19.9617 6.81205 20 6.69272 20 6.57033C20 6.44793 19.9617 6.32861 19.8904 6.2291ZM3.77082 9.07102C2.60859 8.08184 1.74312 7.08063 1.32961 6.57051C1.79086 6.00238 2.81484 4.82418 4.19164 3.72524C3.74957 4.60113 3.51563 5.5691 3.51563 6.57031C3.51563 7.5727 3.75012 8.5418 4.19316 9.41852C4.05048 9.30501 3.90968 9.18916 3.77082 9.07102ZM9.85547 11.8403C6.9893 11.7446 4.6875 9.41754 4.6875 6.57031C4.6875 3.64777 7.12906 1.21145 10.2175 1.30023C13.043 1.39465 15.3125 3.72227 15.3125 6.57031C15.3125 9.47789 12.9231 11.9308 9.85547 11.8403ZM15.8005 9.45715C16.2377 8.58817 16.4844 7.60758 16.4844 6.57031C16.4844 5.53199 16.2372 4.55047 15.7993 3.68086C16.8489 4.51793 17.8723 5.56227 18.6753 6.57012C18.2181 7.14317 17.19 8.3452 15.8005 9.45715Z" fill="white"/>
												<path d="M10.0273 3.64062C8.44656 3.64062 7.01953 4.92035 7.01953 6.57031C7.01953 8.22043 8.44676 9.5 10.0273 9.5C11.6428 9.5 12.957 8.18574 12.957 6.57031C12.957 4.95488 11.6428 3.64062 10.0273 3.64062ZM10.0273 8.32813C9.03215 8.32813 8.19141 7.52316 8.19141 6.57031C8.19141 5.61746 9.03215 4.8125 10.0273 4.8125C10.9966 4.8125 11.7852 5.60105 11.7852 6.57031C11.7852 7.53957 10.9966 8.32813 10.0273 8.32813Z" fill="white"/>
                    						</svg>
											</button>
									<?php }?>
									<?php if ( $product && $product->is_type( 'simple' ) ) {
                    					if ( $product->is_in_stock() || $product->backorders_allowed() ) { ?>
											<button class="sv-product-reaction quick-cart" data-product_id="<?php echo esc_attr($product->get_id());?>">
												<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
													<path
													d="M19.7404 6.27644C19.6758 6.23243 19.6033 6.20155 19.5268 6.18558C19.4504 6.16961 19.3715 6.16886 19.2948 6.18336C19.2181 6.19787 19.1449 6.22735 19.0796 6.27012C19.0142 6.3129 18.9579 6.36813 18.9139 6.43265L17.6996 8.21306C16.9321 4.66692 13.7686 2 9.99829 2C7.89349 2 5.91477 2.81967 4.42665 4.30811C4.31514 4.41965 4.25251 4.57091 4.25252 4.72863C4.25254 4.88634 4.3152 5.03759 4.42673 5.14911C4.48194 5.20434 4.5475 5.24815 4.61966 5.27804C4.69181 5.30793 4.76914 5.32331 4.84724 5.3233C4.92534 5.32329 5.00267 5.3079 5.07482 5.278C5.14697 5.24809 5.21252 5.20427 5.26773 5.14903C6.53113 3.88531 8.21115 3.18942 9.99821 3.18942C13.2747 3.18942 16.0099 5.562 16.5781 8.67883L14.4684 7.23987C14.4039 7.19585 14.3313 7.16496 14.2548 7.14899C14.1784 7.13301 14.0995 7.13225 14.0228 7.14676C13.9461 7.16127 13.8729 7.19075 13.8076 7.23353C13.7422 7.27631 13.686 7.33155 13.642 7.39608C13.598 7.46061 13.5671 7.53317 13.5511 7.60962C13.5352 7.68607 13.5344 7.76492 13.5489 7.84166C13.5635 7.9184 13.5929 7.99153 13.6357 8.05687C13.6785 8.12221 13.7337 8.17849 13.7982 8.22249L16.9474 10.3703L16.9479 10.3707C17.0465 10.4381 17.1631 10.4742 17.2825 10.4742C17.3794 10.4744 17.4749 10.4508 17.5606 10.4055C17.6464 10.3603 17.7197 10.2947 17.7743 10.2146L19.8966 7.10293C19.9406 7.03841 19.9715 6.96585 19.9874 6.88939C20.0034 6.81293 20.0042 6.73408 19.9897 6.65733C19.9751 6.58058 19.9457 6.50744 19.9029 6.44209C19.8601 6.37674 19.8049 6.32045 19.7404 6.27644ZM14.8406 14.496C13.5652 15.8331 11.8456 16.5695 9.99845 16.5695C6.72223 16.5695 3.98688 14.197 3.41873 11.0802L5.52853 12.5192C5.62705 12.5866 5.74367 12.6227 5.86307 12.6226C5.99022 12.6228 6.11406 12.5821 6.21642 12.5067C6.31878 12.4313 6.39427 12.325 6.43181 12.2035C6.46936 12.0821 6.46698 11.9517 6.42503 11.8317C6.38308 11.7117 6.30377 11.6083 6.19873 11.5366L3.04962 9.38876L3.04914 9.38836C2.98462 9.34435 2.91207 9.31347 2.83562 9.2975C2.75917 9.28153 2.68032 9.28078 2.60358 9.29528C2.52684 9.30979 2.4537 9.33927 2.38836 9.38204C2.32301 9.42482 2.26673 9.48005 2.22273 9.54457L0.100329 12.6562C0.0131861 12.7865 -0.0189652 12.946 0.0108693 13.0999C0.0407039 13.2538 0.130111 13.3897 0.259639 13.4781C0.389167 13.5664 0.548337 13.6 0.702521 13.5716C0.856706 13.5432 0.993429 13.455 1.08295 13.3263L2.29719 11.5459C3.06476 15.092 6.22838 17.7588 9.99837 17.7588C12.1738 17.7588 14.1992 16.8915 15.7012 15.3168C15.7551 15.2602 15.7973 15.1937 15.8255 15.1208C15.8537 15.048 15.8672 14.9703 15.8654 14.8922C15.8636 14.8141 15.8464 14.7372 15.8148 14.6658C15.7832 14.5943 15.7379 14.5298 15.6813 14.4759C15.5672 14.3671 15.4145 14.3081 15.2568 14.3118C15.0991 14.3156 14.9494 14.3818 14.8406 14.496Z"
													fill="white"></path>
												</svg>
											</button>
											<?php $nonce = wp_create_nonce( 'quick_cart_nonce' ); ?>
                    						<input type="hidden" name="quick_cart_nonce" id="quick_cart_nonce" value="<?php echo esc_attr( $nonce ); ?>">
										<?php 
										}
									}      
									?>
							</div>
						</div>
						<?php
					}
					wp_reset_postdata();
				} else {
					echo '<p>' . esc_html__('No products found.','hello-elementor-child') . '</p>';
				}
				?>				
			</div>
			<div class="center-view-btn">
				<a href="<?php echo esc_url($sv_term_link); ?>" class="btn view-all-btn">
					<svg width="25" height="24" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M18.2949 17.882C16.7436 17.8806 15.4847 19.137 15.4832 20.6883C15.4817 22.2396 16.7381 23.4985 18.2895 23.5C19.8408 23.5015 21.0996 22.2451 21.1012 20.6937V20.691C21.0997 19.1413 19.8446 17.8851 18.2949 17.882ZM23.2771 4.43118C23.2099 4.41816 23.1416 4.41156 23.0731 4.41145H5.9701L5.69922 2.59928C5.53046 1.39579 4.50102 0.50037 3.28574 0.5H1.0835C0.485089 0.5 0 0.985089 0 1.5835C0 2.18191 0.485089 2.667 1.0835 2.667H3.28844C3.35499 2.66652 3.41939 2.69056 3.46935 2.73453C3.51931 2.7785 3.55133 2.83932 3.55931 2.90539L5.22789 14.3417C5.45665 15.7949 6.70665 16.867 8.17773 16.8717H19.4488C20.8652 16.8736 22.087 15.8781 22.3716 14.4907L24.135 5.7008C24.2487 5.11329 23.8646 4.54488 23.2771 4.43118ZM11.4136 20.5707C11.3477 19.0649 10.1049 17.8795 8.59759 17.8847C7.04752 17.9474 5.8417 19.2548 5.90434 20.8048C5.96444 22.2922 7.17433 23.4743 8.66261 23.5H8.73033C10.2802 23.4321 11.4815 22.1206 11.4136 20.5707Z" fill="#EC4E34"></path>
					</svg>
					<span><?php echo esc_html__('View All','hello-elementor-child'); ?></span>
				</a>
			</div>
			</div>
		</div>
		<?php
		$first_iteration_tab = false;
	}
}
?>
</div>