<?php 
$atts = get_query_var('shortcode_atts', array(
    'ids' => ''
));
$product_ids = explode(',', $atts['ids']);
if (empty($product_ids)) {
    return esc_html_e('Please provide product IDs.', 'supavapes');
}
?>
<div class="review-slider">
    <?php
    foreach ($product_ids as $product_id) {
        // $product = wc_get_product($product_id);
		$product_data = wc_get_product($product_id);
		$product_type = $product_data->get_type();
        if ($product_data && $product_data->get_status() === 'publish') {
            $p_title = $product_data->get_name();
            $price = $product_data->get_price_html();
            $image = $product_data->get_image();
            $rating = $product_data->get_average_rating();
            $reviews = get_comments(
                array(
                    'post_id' => $product_id,
                    'status' => 'approve',
                    'type' => 'review'
                )
            );
            $product_url = get_permalink($product_id);
            $comment_content = isset($reviews[0]) ? $reviews[0]->comment_content : '';


			// Check if the product is variable
			if ( $product_type == 'variable' ) {

				// Get all variations of the variable product
				$available_variations = $product_data->get_available_variations(); // Updated from $product to $product_data

				$min_vaping_liquid = PHP_INT_MAX;
				$max_vaping_liquid = PHP_INT_MIN;

				// Loop through each variation to find the minimum and maximum _vaping_liquid value
				foreach ( $available_variations as $variation ) {
					$vaping_liquid_value = (int) get_post_meta( $variation['variation_id'], '_vaping_liquid', true );

					// Skip any vaping liquid values that are zero
					if ( $vaping_liquid_value > 0 ) {
						// Set minimum and maximum values
						if ( $vaping_liquid_value < $min_vaping_liquid ) {
							$min_vaping_liquid = $vaping_liquid_value;
						}
						if ( $vaping_liquid_value > $max_vaping_liquid ) {
							$max_vaping_liquid = $vaping_liquid_value;
						}
					}
				}

				// Fallback if there are no variations
				if ( $min_vaping_liquid == PHP_INT_MAX ) {
					$min_vaping_liquid = 0;
				}
				if ( $max_vaping_liquid == PHP_INT_MIN ) {
					$max_vaping_liquid = 0;
				}
				
				// Calculate taxes for the minimum and maximum vaping liquid values
				$min_ontario_tax = supavapes_calculate_ontario_tax( $min_vaping_liquid );
				$max_ontario_tax = supavapes_calculate_ontario_tax( $max_vaping_liquid );
				$min_federal_tax = supavapes_calculate_federal_tax( $min_vaping_liquid );
				$max_federal_tax = supavapes_calculate_federal_tax( $max_vaping_liquid );

				// Get the price range (minimum and maximum prices of the variations)
				$min_price = $product_data->get_variation_price( 'min' );
				$max_price = $product_data->get_variation_price( 'max' );

				// Determine the state
				$state = isset( $_COOKIE['user_state'] ) ? sanitize_text_field( $_COOKIE['user_state'] ) : '';

				// Calculate final prices with tax for minimum and maximum values
				if ( 'Gujarat' !== $state ) {
					$final_min_price = floatval( $min_price ) + floatval( $min_federal_tax );
					$final_max_price = floatval( $max_price ) + floatval( $max_federal_tax );
				} else {
					$final_min_price = floatval( $min_price ) + floatval( $min_ontario_tax ) + floatval( $min_federal_tax );
					$final_max_price = floatval( $max_price ) + floatval( $max_ontario_tax ) + floatval( $max_federal_tax );
				} 
			
			} else {

				$vaping_liquid = get_post_meta( $product_data->get_id(), '_vaping_liquid', true );
				$vaping_liquid = (int) $vaping_liquid;
				$reg_price = $product_data->get_regular_price();
				$sale_price = $product_data->get_sale_price();

				// Calculate taxes using the custom functions if vaping_liquid is set.
				if ( isset( $vaping_liquid ) && ! empty( $vaping_liquid ) ) {
					$ontario_tax = supavapes_calculate_ontario_tax( $vaping_liquid );
					$federal_tax = supavapes_calculate_federal_tax( $vaping_liquid );
				}

				// Determine the final price based on the state.
				$state = isset( $_COOKIE['user_state'] ) ? sanitize_text_field( $_COOKIE['user_state'] ) : '';

				if ( 'Gujarat' !== $state ) {
					$final_price = isset( $sale_price ) && ! empty( $sale_price ) ? floatval( $sale_price ) : floatval( $reg_price );
					$final_price += floatval( $federal_tax );
				} else {
					$final_price = isset( $sale_price ) && ! empty( $sale_price ) ? floatval( $sale_price ) : floatval( $reg_price );
					$final_price += floatval( $ontario_tax ) + floatval( $federal_tax );
				}
		}

            if ( !empty( $comment_content ) ) {
                ?>
                <div class="slider-box">
                    <div class="sv-product-review">
                        <p class="add-read-more show-less-content dfdsf"><?php echo esc_html($comment_content, 'supavapes'); ?></p>
                        <div class="sv-product-review-from">
                            <h3><?php echo esc_html(get_comment_author($reviews[0]->comment_ID)); ?></h3>
                            <ul class="sv-stars">
                                <?php $rating_num = intval($rating); ?>
                                <li>
                                    <?php if ($rating_num >= 1 && $rating_num <= 5) { ?>
                                        <img src="<?php echo esc_url(get_site_url()); ?>/wp-content/uploads/2024/04/<?php echo $rating_num; ?>-star.png" />
                                    <?php } ?>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <a href="<?php echo esc_url($product_url); ?>" class="sv-product">
                        <div class="sv-product-img"><?php echo wp_kses_post($image); ?></div>
                        <span class="divider"></span>
                        <div class="sv-product-detail">
                            <h4 class="sv-product-name"><?php echo esc_html($p_title, 'supavapes'); ?></h4>
                            <div class="sv-product-price"><?php echo wp_kses_post($price); ?></div>
							<?php if ( $product_data && method_exists( $product_data, 'get_type' ) ) {
							$product_type = $product_data->get_type();
						}?>
						<?php if( $product_type == 'simple' ){ ?>
							<?php if ( isset( $vaping_liquid ) && !empty( $vaping_liquid ) && $vaping_liquid >= 10 ) {
										?>
							<div class="info-icon-container">
								<img src="/wp-content/uploads/2024/09/info-icon.svg" class="info-icon" alt="Info Icon" style="height: 15px; width: 15px; position: relative;">
								<div class="price-breakup-popup">
								<h5 class="header"><?php esc_html_e( 'Price Breakdown','supavapes' ); ?></h5>
									<table class="pricetable">
									<?php if ( isset( $sale_price ) && !empty( $sale_price ) ) { ?>
									<tr>
									<td class='leftprice'><?php esc_html_e( 'Product Price','supavapes' ); ?></td>
									<td class='rightprice'><?php echo wc_price( $sale_price ); ?></td>
									</tr>
									<?php }else{?>
									<tr>
									<td class='leftprice' ><?php esc_html_e( 'Product Price','supavapes' ); ?></td>
									<td class='rightprice'><?php echo wc_price( $reg_price ); ?></td>
									</tr>
									<?php }?>
									<?php if ( 'Gujarat' !== $state ) { ?>
									<tr>
									<td class='leftprice'><?php esc_html_e( 'Federal Excise Tax','supavapes' ); ?></td>
									<td class='rightprice'><?php echo wc_price( $federal_tax ); ?></td>
									</tr>
									<?php }else{?>
									<tr>
									<td class='leftprice'><?php esc_html_e( 'Ontario Excise Tax','supavapes' ); ?></td>
									<td class='rightprice'><?php echo wc_price( $ontario_tax ); ?></td>
									</tr>
									<tr>
									<td class='leftprice'><?php esc_html_e( 'Federal Excise Tax','supavapes' ); ?></td>
									<td class='rightprice'><?php echo wc_price( $federal_tax ); ?></td>
									</tr>
									<?php } ?>
									<tr class="wholesaleprice">
									<td class='leftprice'><?php esc_html_e( 'Wholesale Price','supavapes' ); ?></td>
									<td class='rightprice'><?php echo wc_price( $final_price ); ?></td>
									</tr>
									</table>
								</div>
							</div>
						<?php 
							}
						}else{
							// Only display the price breakdown box if either tax is greater than zero
							if ( $min_ontario_tax > 0 || $max_ontario_tax > 0 || $min_federal_tax > 0 || $max_federal_tax > 0 ) {

								// Check if min and max prices are the same to avoid showing price ranges
								if ( $min_price === $max_price ) {
								// Display simple price breakdown for variable product
								
								?>
							<div class="info-icon-container">
								<img src="/wp-content/uploads/2024/09/info-icon.svg" class="info-icon" alt="Info Icon" style="height: 15px; width: 15px; position: relative;">
								<div class="price-breakup-popup">
									<h5 class="header"><?php esc_html_e( 'Price Breakdown', 'supavapes' ); ?></h5>
									<table class="pricetable">
										<tr>
											<td class='leftprice'><?php esc_html_e( 'Product Price', 'supavapes' ); ?></td>
											<td class='rightprice'><?php echo wc_price( $min_price ); ?></td>
										</tr>
										<?php if ( 'Gujarat' !== $state ) { ?>
											<tr>
												<td class='leftprice'><?php esc_html_e( 'Federal Excise Tax', 'supavapes' ); ?></td>
												<td class='rightprice'><?php echo wc_price( $min_federal_tax ); ?></td>
											</tr>
										<?php } else { ?>
											<tr>
												<td class='leftprice'><?php esc_html_e( 'Ontario Excise Tax', 'supavapes' ); ?></td>
												<td class='rightprice'><?php echo wc_price( $min_ontario_tax ); ?></td>
											</tr>
											<tr>
												<td class='leftprice'><?php esc_html_e( 'Federal Excise Tax', 'supavapes' ); ?></td>
												<td class='rightprice'><?php echo wc_price( $min_federal_tax ); ?></td>
											</tr>
										<?php } ?>
										<tr class="wholesaleprice">
											<td class='leftprice'><?php esc_html_e( 'Wholesale Price', 'supavapes' ); ?></td>
											<td class='rightprice'><?php echo wc_price( $final_min_price ); ?></td>
										</tr>
									</table>
								</div>
							</div>
							<?php 
								}else{ ?>
								<div class="info-icon-container">
									<img src="/wp-content/uploads/2024/09/info-icon.svg" class="info-icon" alt="Info Icon" style="height: 15px; width: 15px; position: relative;">
									<div class="price-breakup-popup">
										<h5 class="header"><?php esc_html_e( 'Price Breakdown', 'supavapes' ); ?></h5>
										<table class="pricetable">
											<tr>
												<td class='leftprice'><?php esc_html_e( 'Product Price', 'supavapes' ); ?></td>
												<td class='rightprice'><?php echo wc_price( $min_price ).' - '.wc_price( $max_price ); ?></td>
											</tr>
											<?php if ( 'Gujarat' !== $state ) { ?>
												<tr>
													<td class='leftprice'><?php esc_html_e( 'Federal Excise Tax', 'supavapes' ); ?></td>
													<td class='rightprice'><?php echo wc_price( $min_federal_tax ).' - '.wc_price( $max_federal_tax ); ?></td>
												</tr>
											<?php } else { ?>
												<tr>
													<td class='leftprice'><?php esc_html_e( 'Ontario Excise Tax', 'supavapes' ); ?></td>
													<td class='rightprice'><?php echo wc_price( $min_ontario_tax ).' - '.wc_price( $max_ontario_tax ); ?></td>
												</tr>
												<tr>
													<td class='leftprice'><?php esc_html_e( 'Federal Excise Tax', 'supavapes' ); ?></td>
													<td class='rightprice'><?php echo wc_price( $min_federal_tax ).' - '.wc_price( $max_federal_tax ); ?></td>
												</tr>
											<?php } ?>
											<tr class="wholesaleprice">
												<td class='leftprice'><?php esc_html_e( 'Wholesale Price', 'supavapes' ); ?></td>
												<td class='rightprice'><?php echo wc_price( $final_min_price ).' - '.wc_price( $final_max_price ); ?></td>
											</tr>
										</table>
									</div>
								</div>
								<?php }
							}
						}
						?>
                        </div>
                    </a>
                </div>
                <?php
            }
        }
    }
    ?>
</div>
