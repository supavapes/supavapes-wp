<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Function to check variant is present in cart or not.
 *
 * @param $variation_id
 *
 * @return bool
 */
function wqcmv_is_variant_in_cart( $variation_id ) {

	$in_cart = false;
	$cart    = WC()->cart->get_cart_contents();
	foreach ( $cart as $cart_item ) {
		$product_in_cart = $cart_item['variation_id'];
		if ( 0 !== $product_in_cart && $product_in_cart === $variation_id ) {
			$in_cart = $cart_item['quantity'];
		}
	}

	return $in_cart;

}

/**
 * Function to define default headers and return updated headers if any changes made to the headers.
 * @return mixed|void
 */
function wqcmv_updated_headers() {

	$default_headers     = array(
		'title' => esc_html__( 'Product Name', 'woocommerce-quick-cart-for-multiple-variations' ),
		// 'sku'   => esc_html__( 'SKU', 'woocommerce-quick-cart-for-multiple-variations' ),
		'price' => esc_html__( 'Price', 'woocommerce-quick-cart-for-multiple-variations' ),
		'qty'   => esc_html__( 'Quantity', 'woocommerce-quick-cart-for-multiple-variations' ),
	);
	$wqcmv_table_headers = apply_filters( 'wqcmv_table_headers', $default_headers );

	return $wqcmv_table_headers;

}

/**
 * Function to fetch html of variants when it requires.
 *
 * @param int $variation_id
 *
 * @return false|string
 */
function wqcmv_fetch_product_block_html( $variation_id = 0, $changed_variations = array() ) {
	
	if ( 0 === $variation_id ) {
		return '';
	}
	$product = wc_get_product( $variation_id );

	if ( false === $product || empty( $product ) ) {
		return '';
	}
	$unavailable_variants_option       = get_option( 'vpe_allow_unavailable_variants' );
	$wqcmv_enable_thumbnail_visibility = get_option( 'wqcmv_enable_thumbnail_visibility' );
	$show_out_of_stock_variants        = ( 'yes' === $unavailable_variants_option ) ? 'yes' : 'no';

	/* return empty when do not display the out of stock variation and variation is outofstock */
	if ( 'no' === $show_out_of_stock_variants && ( 'instock' !== $product->get_stock_status() && 'onbackorder' !== $product->get_stock_status() ) ) {
		return '';
	}

	$stock_availibility = true;
	$stock_text         = "";
	$stock_in_cart      = false;

	ob_start();
	$variation_title = $product->get_formatted_name();
	$parent_id       = $product->get_parent_id();
	$product_title   = get_the_title( $parent_id );
	$variation_title = str_replace( $product_title . " - ", " ", $variation_title );
	$variation_title = wp_strip_all_tags( $variation_title ); //Removed span tag from title.
	$sku             = $product->get_sku();
	// Variant image
	$variation_thumbnail_id = get_post_thumbnail_id( $variation_id );
	$variation_thumbnail    = wc_placeholder_img_src();
	if ( '' !== $variation_thumbnail_id ) {
		$variation_thumbnail_url = wp_get_attachment_image_src( $variation_thumbnail_id, 'thumbnail' );
		$variation_full_url      = wp_get_attachment_image_src( $variation_thumbnail_id, 'medium' );
		if ( empty( $variation_thumbnail_url ) ) {
			$variation_thumbnail = wc_placeholder_img_src();
		} else {
			$variation_thumbnail = $variation_thumbnail_url[0];
			$variation_full      = $variation_full_url[0];
		}
	} else {
		if ( 0 !== $parent_id ) {
			$variation_thumbnail_id = get_post_thumbnail_id( $parent_id );
			if ( $variation_thumbnail_id !== '' ) {
				$variation_thumbnail_url = wp_get_attachment_image_src( $variation_thumbnail_id, 'thumbnail' );
				$variation_full_url      = wp_get_attachment_image_src( $variation_thumbnail_id, 'medium' );
				if ( empty( $variation_thumbnail_url ) ) {
					$variation_thumbnail = wc_placeholder_img_src();
				} else {
					$variation_thumbnail = $variation_thumbnail_url[0];
					$variation_full      = $variation_full_url[0];
				}
			}
		}
	}
	// Fetch variant price
	$reg_price  = get_post_meta( $variation_id, '_regular_price', true );
	$sale_price = get_post_meta( $variation_id, '_sale_price', true );
	if ( $sale_price ) {
		$price = '<del>' . ( is_numeric( $reg_price ) ? wc_price( $reg_price ) : $reg_price ) . '</del><ins>' . ( is_numeric( $sale_price ) ? wc_price( $sale_price ) : $sale_price ) . '</ins>';
	}else{
		$price = '<ins>' . ( is_numeric( $reg_price ) ? wc_price( $reg_price ) : $reg_price ) . '</ins>';
	}
	
	$manage_stock = get_post_meta( $variation_id, '_manage_stock', true );

	$span             = '';
	$prod_stock_class = '';
	$vid_qty          = 0;
	if ( 'yes' === $manage_stock ) {
		$prod_stock      = intval( get_post_meta( $variation_id, '_stock', true ) );
		$prod_backorders = get_post_meta( $variation_id, '_backorders', true );
		if ( 'yes' === $prod_backorders || 'notify' === $prod_backorders ) {
			$stock_availibility = true;
			$stock_text         = ( 'yes' === $prod_backorders ) ? "In Stock" : "Available on backorder";
			$prod_stock_class   = 'product-in-stock backorders-allowed';
		} else if ( $prod_stock > 0 ) {
			$stock_availibility = true;
			$stock_text         = "In Stock";
			$prod_stock_class   = 'product-in-stock backorders-not-allowed';
		} else {
			$stock_availibility = false;
			$stock_text         = "Not In Stock";
			$prod_stock_class   = 'product-not-in-stock';
		}

		if ( 'no' === $prod_backorders ) {
			$avalilable_quantity = wqcmv_is_variant_in_cart( $variation_id );
			$stock_in_cart       = false;
			if ( $avalilable_quantity === $prod_stock ) {
				$stock_in_cart = true;
				$stock_text    = "Already in Cart";
			} else {
				$prod_stock = intval( $prod_stock - $avalilable_quantity );
				if ( 0 < $prod_stock ) {
					$stock_available_str = sprintf( esc_html__( '%1$d Available', 'woocommerce-quick-cart-for-multiple-variations' ), $prod_stock );
					$span                = '<span class="vpe_small_stock dd">(' . $stock_available_str . ')</span>';
				}
			}
		}
	} else {
		$prod_stock_status = get_post_meta( $variation_id, '_stock_status', true );
		if ( 'instock' === $prod_stock_status || 'onbackorder' === $prod_stock_status ) {
			$stock_availibility = true;
			$stock_text         = "In Stock";
			$prod_stock_class   = 'product-in-stock';
		} else {
			$stock_availibility = false;
			$stock_text         = "Not In Stock";
			$prod_stock_class   = 'product-not-in-stock';
		}
	}

	$display_stock_icon = apply_filters( 'wqcmv_stock_icon_display', true );

	if ( 'In Stock' === $stock_text ) {
		$stock_text = apply_filters( 'wqcmv_variation_instock_text', $stock_text );
	} elseif ( 'Not In Stock' === $stock_text ) {
		$stock_text = apply_filters( 'wqcmv_variation_notinstock_text', $stock_text );
	} elseif ( 'Available on backorder' === $stock_text ) {
		$stock_text = apply_filters( 'wqcmv_variation_instock_with_backorder_allowed_text', $stock_text );
	}

	$vid_qty = 0;
	if ( ! empty( $changed_variations ) && is_array( $changed_variations ) ) {
		$variation_ids = array_map( 'intval', array_column( $changed_variations, 'vid' ) ); //get all variations ids with converted to int.
		$vid_key       = array_search( $variation_id, $variation_ids, true );
		$vid_qty       = ( false !== $vid_key && null !== $vid_key ) ? $changed_variations[ $vid_key ]['qty'] : 0;
	}
	?>
	<?php
	ob_start();
		$price = $price;
		?>
		<div class="cartRow">
			
			<div class="cartItem">
				<?php
				if ( 'yes' === $wqcmv_enable_thumbnail_visibility ) {
					?>
					<div class="vpn_product_image_gallery">
						<label class="vpn_product_label"><?php echo esc_html__("Product Image",'woocommerce-quick-cart-for-multiple-variations'); ?></label>
						<img src="<?php echo esc_url( $variation_thumbnail ); ?>" alt="">
					</div>
					<?php
				}
				?>
				<div class="vpn_product_title">
					<label class="vpn_product_label"><?php echo esc_html__("Product Title",'woocommerce-quick-cart-for-multiple-variations'); ?></label>
					<h4><?php echo wp_kses_post( apply_filters( 'wqcmv_variation_title', $variation_title, $variation_id ) ); ?></h4>
				</div>
				<div class="vpn_product_price">
					<label class="vpn_product_label"><?php echo esc_html__("Price",'woocommerce-quick-cart-for-multiple-variations'); ?></label>
					<h4 class="priceInfo"><?php echo wp_kses_post( $price ); ?></h4>
				</div>
				<div class="quantity">
					<label class="vpn_product_label"><?php echo esc_html__("Quantity",'woocommerce-quick-cart-for-multiple-variations'); ?></label>
						<div class="qtyCount test-quantity-class">
							<?php
							$qty_not_available = 'Not In Stock';
							// $stock_in_cart;
							if ( $prod_stock_class === 'product-not-in-stock' || $prod_stock_class === 'product-not-in-stock backorders-not-allowed' || isset( $stock_in_cart ) && true === $stock_in_cart ) {
								// echo "Stock in cart: ".$stock_in_cart;
								// echo "availability: ".$stock_availibility;
								if ( ! $stock_availibility ||  $stock_in_cart === true) {
									// echo "innn";
									/*

									<button type="button" name="wqcmv_notify" class="btn" id="notify_me" data-variation-id="<?php echo esc_attr( $variation_id ); ?>"><?php echo esc_html__( apply_filters( 'wqcmv_change_notify_me_text', 'Notify Me', $variation_id ), 'woocommerce-quick-cart-for-multiple-variations' ); ?></button> 
									*/
									?>
									<button type="button" name="wqcmv_notify" class="btn" id="vns_pre_order" data-variation-id="<?php echo esc_attr( $variation_id ); ?>"><?php echo esc_html__( apply_filters( 'wqcmv_change_notify_me_text', 'Pre Order', $variation_id ), 'woocommerce-quick-cart-for-multiple-variations' ); ?></button> 
								<?php } ?>
								<!-- <button class="btn"> Notify Me</button> -->
							<?php } else { ?>
								<label>Qty:</label>
								<a href="" class="minusQty"></a>
								<input type="number"
										name="qty"
										id="qty"
										value="<?php echo isset( $vid_qty )  ? esc_attr( $vid_qty ) : ''; ?>"
										min="1"
										size="4"
										max="<?php echo isset( $prod_backorders ) && 'no' === $prod_backorders && isset( $prod_stock ) ? esc_attr( $prod_stock ) : ''; ?>"
										class="variant-qty-input"
										data-variation-id="<?php echo isset( $variation_id ) ? esc_attr( $variation_id ) : ''; ?>"
										data-stock-quantity="<?php echo isset( $prod_stock ) ? esc_attr( $prod_stock ) : ''; ?>"
										data-variable-name="<?php echo isset( $variation_title ) ? esc_attr( $variation_title ) : ''; ?>"
										data-stockclass="<?php echo isset( $prod_stock_class ) ? esc_attr( $prod_stock_class ) : ''; ?>"
										data-manage-stock="<?php echo isset( $manage_stock ) ? esc_attr( $manage_stock ) : ''; ?>"
										data-backorders="<?php echo isset( $prod_backorders ) ? esc_attr( $prod_backorders ) : ''; ?>"
										placeholder="e.g.: 1,2..."
										readonly
								>
								<a href="" class="plusQty"></a>
								
							<?php } ?>
						</div>
						
					</div>
					<div class="vpn_stock_status">
						<label class="vpn_product_label"><?php echo esc_html__("Stock",'woocommerce-quick-cart-for-multiple-variations'); ?></label>
					<?php
						$qty_not_available = 'Not In Stock';
						if ( $prod_stock_class === 'product-not-in-stock' || $prod_stock_class === 'product-not-in-stock backorders-not-allowed' || isset( $stock_in_cart ) && true === $stock_in_cart ) {
							?>
							<label><?php echo esc_html__( apply_filters( 'wqcmv_change_quantity_not_available_text', $qty_not_available ), 'woocommerce-quick-cart-for-multiple-variations' ); ?></label>
							<?php
						} else {
							?>
							<label><span class="status"><?php echo wp_kses_post( $stock_text . $span ); ?></span></label>
							<?php
						}
						?>
					</div>
				<!-- <div class="cartItemInfo">
					
				</div> -->
			</div>
		</div>
	<?php
	$html = ob_get_clean();

return $html;

}

/**
 * Allowed html for wp kses.
 * @return array
 */
function wqcmv_wses_allowed_variation_html() {
	return array(
		'tr'    => array(
			'class'          => array(),
			'data-tobeshown' => array(),
		),
		'i'     => array(
			'class' => array(),
		),
		'td'    => array(
			'class'      => array(),
			'data-title' => array(),
			'id'         => array(),
		),
		'a'     => array(
			'href'          => array(),
			'class'         => array(),
			'data-fancybox' => array(),
		),
		'img'   => array(
			'src'   => array(),
			'class' => array(),
		),
		'span'  => array(
			'class' => array(),
		),
		'input' => array(
			'name'                => array(),
			'class'               => array(),
			'type'                => array(),
			'id'                  => array(),
			'value'               => array(),
			'max'                 => array(),
			'min'                 => array(),
			'size'                => array(),
			'data-variation-id'   => array(),
			'data-stock-quantity' => array(),
			'data-variable-name'  => array(),
			'data-backorders'     => array(),
			'data-stockclass'     => array(),
			'data-manage-stock'   => array(),
			'placeholder'         => array(),
		),
		'del'   => array(),
		'br'    => array(),
	);
}

/**
 * @param $product_id
 *
 * @return int[]|WP_Post[]
 */
function wqcmv_conditional_logic_variation( $product_id, $variations_per_page = 10, $paged = 1 ) {
	$variation_data = array();
	$variation_args = array(
		'post_type'      => 'product_variation',
		'posts_per_page' => $variations_per_page,
		'post_status'    => 'publish',
		'post_parent'    => $product_id,
		'fields'         => 'ids',
		'paged'          => $paged,
		'orderby'        => 'title',
		'order'          => 'ASC',
	);

	$unavailable_variants_option = get_option( 'vpe_allow_unavailable_variants' );

	//meta query if need to show only instock or onbackorder product variations.
	if ( 'no' === $unavailable_variants_option ) {
		$variation_args['meta_query'] = array(
			'relation' => 'OR',
			array(
				'key'     => '_stock_status',
				'value'   => 'instock',
				'compare' => '='
			),
			array(
				'key'     => '_stock_status',
				'value'   => 'onbackorder',
				'compare' => '='
			)
		);
	}

	$wp_query_result = new WP_Query( $variation_args );
	$variation_pids  = isset( $wp_query_result->posts ) ? $wp_query_result->posts : array();
	$pagination      = isset( $wp_query_result->max_num_pages ) && $paged < $wp_query_result->max_num_pages ? 'yes' : 'no';

	wp_reset_postdata();
	wp_reset_query();

	$variation_data['variations'] = $variation_pids;
	$variation_data['pagination'] = $pagination;

	return $variation_data;

}

function wqcmv_fetch_outofstcok_variation_ids( $product_id, $per_page = 10, $paged = 1 ) {
	$variations_data = array();
	$variation_args  = array(
		'post_type'      => array( 'product_variation' ),
		'posts_per_page' => $per_page,
		'post_status'    => 'publish',
		'post_parent'    => $product_id,
		'fields'         => 'ids',
		'paged'          => $paged,
		'meta_query'     => array(
			array(
				'key'     => '_stock_status',
				'value'   => 'outofstock',
				'compare' => '='
			)
		)
	);

	$wp_query_results = new WP_Query( $variation_args );
	$pagination     = isset( $wp_query_results->max_num_pages ) && $paged < $wp_query_results->max_num_pages ? 'yes' : 'no';
	$variation_pids = isset( $wp_query_results->posts ) ? $wp_query_results->posts : array();
	wp_reset_postdata();
	wp_reset_query();

	$variations_data['variation_ids'] = $variation_pids;
	$variations_data['pagination']    = $pagination;

	return $variations_data;
}

function wqcmv_get_outofstock_product_html( $variation_ids ) {
	if ( empty( $variation_ids ) || ! is_array( $variation_ids ) ) {
		return '';
	}

	ob_start();
	foreach ( $variation_ids as $ids ) {
		$variation_all_data = wc_get_product( $ids );
		$variation_title    = $variation_all_data->get_formatted_name();
		$variation_title    = wp_strip_all_tags( $variation_title );
		$sku                = $variation_all_data->get_sku();
		if ( $variation_all_data->is_on_sale() ) {
			$price = $variation_all_data->get_price_html();
		} else {
			$price = wc_price( wc_get_price_to_display( $variation_all_data ) ) . $variation_all_data->get_price_suffix();
		}
		?>
        <tr class="product-out-of-stock" data-variation-id="<?php esc_html_e( $ids ); ?>">
            <td><span aria-hidden="true"><?php if ( ! empty( $sku ) ) {
						esc_html_e( $sku );
					} else {
						esc_html_e( '-' );
					} ?></span></td>
            <td class="has-row-actions">
                <span><?php esc_html_e( $variation_title, 'woocommerce-quick-cart-for-multiple-variations' ); ?></span>
            </td>
            <td><input type="number" class="wqcmv-prod-qty-<?php esc_html_e( $ids ); ?> test-qty"
                       placeholder="0"
                       min="1" value="1" onpaste="return false" readonly></td>
            <td><?php echo wp_kses_post( $price ); ?></td>
            <td><input type="checkbox" id="wqcmv-out-of-stock-prods-<?php echo esc_attr( $ids ); ?>"
                       class="wqcmv-out-of-stock-prods" name="out-of-stock-product"
                       value="<?php esc_html_e( $ids ); ?>"></td>
        </tr>
		<?php
	}
	$html = ob_get_clean();
	return $html;

}

/**
 * Function to return the default email subject which will be sent when variations will be restocked.
 *
 * @return string
 */
function wqcmv_default_email_subject() {

	return esc_html__( 'Product is back into stock!', 'woocommerce-quick-cart-for-multiple-variations' );

}

/**
 * Function to return the default email content which will be sent when variations will be restocked.
 *
 * @return string
 */
function wqcmv_default_email_content() {

	$site_title = get_bloginfo();
	ob_start();
	?>
    <div id="wrapper" dir="ltr" style="background-color:#f5f5f5;margin:0;padding:70px 0 70px 0;width:100%">
        <div class="adM"></div>
        <table width="100%" height="100%">
            <tbody>
            <tr>
                <td valign="top" align="center">
                    <div id="template_header_image"><h1
                                style="margin-top:0; text-align: center;"><?php esc_html_e( $site_title, 'woocommerce-quick-cart-for-multiple-variations' ); ?></h1>
                    </div>
                    <table id="template_container"
                           style="background-color:#fdfdfd;border:1px solid #dcdcdc;border-radius:3px" width="600"
                           cellspacing="0" cellpadding="0" border="0">
                        <tbody>
                        <tr>
                            <td valign="top" align="center">
                                <table id="template_header"
                                       style="background-color:#fff;color:#000;border-bottom:0;font-weight:bold;line-height:100%;vertical-align:middle;font-family:Helvetica Neue,Helvetica,Roboto,Arial,sans-serif;border-radius:3px 3px 0 0"
                                       width="600" cellspacing="0" cellpadding="0" border="0">
                                    <tbody>
                                    <tr>
                                        <td id="header_wrapper" style="padding:36px 48px;display:block"><h1
                                                    style="color:#000;font-family:Helvetica Neue,Helvetica,Roboto,Arial,sans-serif;font-size:30px;font-weight:300;line-height:150%;margin:0;text-align:left"> <?php esc_html_e( 'Product is back in stock.', 'woocommerce-quick-cart-for-multiple-variations' ); ?></h1>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" align="center">
                                <table id="template_body" width="600" cellspacing="0" cellpadding="0" border="0">
                                    <tbody>
                                    <tr>
                                        <td id="body_content" style="background-color:#fdfdfd"
                                            valign="top"> <?php $variation_link = '{variation_link}';
											$variation_title                    = '{variation_title}';
											$variation_title                    = '<a href="' . $variation_link . '">' . $variation_title . '</a>';
											$cart_link                          = '{cart_link}';
											$continue_purchasing                = '<a href="' . $cart_link . '">' . esc_html__( 'Continue purchasing' ) . '</a>'; ?>
                                            <table width="100%" cellspacing="0" cellpadding="20" border="0">
                                                <tbody>
                                                <tr>
                                                    <td style="padding:0 48px 0" valign="top">
                                                        <div id="body_content_inner"
                                                             style="color:#737373;font-family:&quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif;font-size:14px;line-height:150%;text-align:left">
                                                            <p style="margin:0 0 16px"><?php echo sprintf( 'Hello User, %3$s%3$s We are pleased to inform you that the %1$s product is back in stock and added to your cart. %2$s.', wp_kses_post( $variation_title ), wp_kses_post( $continue_purchasing ), '<br />' ); ?></p>
                                                            <p style="margin:0 0 16px"><?php esc_html_e( 'Thank you!', 'woocommerce-quick-cart-for-multiple-variations' ); ?></p>
                                                            <p style="margin:10px 0 36px;"></p></div>
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
	<?php
	return ob_get_clean();

}

function wqcmv_contact_admin_email_content( $products = array(), $user_message = '', $name = '', $email = '' ) {
	if ( empty( $products ) ) {
		return;
	}

	$site_title = get_bloginfo();
	ob_start();
	?>
    <div id="wrapper" dir="ltr" style="background-color:#f5f5f5;margin:0;padding:70px 0 70px 0;width:100%">
        <div class="adM"></div>
        <table width="100%" height="100%">
            <tbody>
            <tr>
                <td valign="top" align="center">
                    <div id="template_header_image"><p
                                style="margin-top:0"><?php esc_html_e( $site_title, 'woocommerce-quick-cart-for-multiple-variations' ) ?></p>
                    </div>
                    <table id="template_container"
                           style="background-color:#fdfdfd;border:1px solid #dcdcdc;border-radius:3px" width="600"
                           cellspacing="0" cellpadding="0" border="0">
                        <tbody>
                        <tr>
                            <td valign="top" align="center">
                                <table id="template_header"
                                       style="background-color:#fff;color:#000;border-bottom:0;font-weight:bold;line-height:100%;vertical-align:middle;font-family:Helvetica Neue,Helvetica,Roboto,Arial,sans-serif;border-radius:3px 3px 0 0"
                                       width="600" cellspacing="0" cellpadding="0" border="0">
                                    <tbody>
                                    <tr>
                                        <td id="header_wrapper" style="padding:36px 48px;display:block"><h1
                                                    style="color:#000;font-family:Helvetica Neue,Helvetica,Roboto,Arial,sans-serif;font-size:30px;font-weight:300;line-height:150%;margin:0;text-align:left"> <?php esc_html_e( 'Out of Stock Products Request', 'woocommerce-quick-cart-for-multiple-variations' ); ?></h1>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" align="center">
                                <table id="template_body" width="600" cellspacing="0" cellpadding="0" border="0">
                                    <tbody>
                                    <tr>
                                        <td id="body_content" style="background-color:#fdfdfd" valign="top">
                                            <table width="100%" cellspacing="0" cellpadding="20" border="0">
                                                <tbody>
                                                <tr>
                                                    <td style="padding:0 48px 0" valign="top">
                                                        <div id="body_content_inner"
                                                             style="color:#737373;font-family:Helvetica Neue,Helvetica,Roboto,Arial,sans-serif;font-size:14px;line-height:150%;text-align:left">
                                                            <p style="margin:0 0 16px"> <?php esc_html_e( 'Hi Admin,', 'woocommerce-quick-cart-for-multiple-variations' ); ?></p>
                                                            <p style="margin:0 0 16px"><?php echo sprintf( esc_html( 'Here is the request from %1$s (%2$s) about the following products that are out of stock.' ), esc_html( $name ), esc_html( $email ) ); ?></p>
                                                            <table border="1" cellpadding="5" cellspacing="0">
                                                                <thead>
                                                                <tr>
                                                                    <th scope="col"
                                                                        class="manage-column"><?php esc_html_e( 'SKU', 'woocommerce-quick-cart-for-multiple-variations' ); ?></th>
                                                                    <th scope="col" class="manage-column"><a
                                                                                href="javascript:void(0);"><span><?php esc_html_e( 'Product', 'woocommerce-quick-cart-for-multiple-variations' ); ?></span></a>
                                                                    </th>
                                                                    <th scope="col"
                                                                        class="manage-column"> <?php esc_html_e( 'Quantity', 'woocommerce-quick-cart-for-multiple-variations' ); ?></th>
                                                                    <th scope="col"
                                                                        class="manage-column"> <?php esc_html_e( 'Unit Price', 'woocommerce-quick-cart-for-multiple-variations' ); ?></th>
                                                                </tr> <?php foreach ( $products as $data ) {
																	$id                 = $data['id'];
																	$qty                = $data['qty'];
																	$sku                = get_post_meta( $id, '_sku', true );
																	$variation_all_data = wc_get_product( $id );
																	$variation_title    = $variation_all_data->get_formatted_name();
																	$variation_title    = wp_strip_all_tags( $variation_title );
																	$sku                = $variation_all_data->get_sku();
																	if ( $variation_all_data->is_on_sale() ) {
																		$price = $variation_all_data->get_price_html();
																	} else {
																		$price = wc_price( wc_get_price_to_display( $variation_all_data ) ) . $variation_all_data->get_price_suffix();
																	}
																	?>
                                                                    <tr>
                                                                        <td>
                                                                        <span aria-hidden="true"><?php if ( ! empty( $sku ) ) {
																				esc_html_e( $sku, 'woocommerce-quick-cart-for-multiple-variations' );
																			} else {
																				esc_html_e( 'N/A', 'woocommerce-quick-cart-for-multiple-variations' );
																			} ?></span></td>
                                                                        <td class="has-row-actions">
                                                                            <span><?php esc_html_e( $variation_title, 'woocommerce-quick-cart-for-multiple-variations' ); ?></span>
                                                                        </td>
                                                                        <td><span><?php esc_html_e( $qty ); ?></span>
                                                                        </td>
                                                                        <td><?php echo wp_kses_post( $price ); ?></td>
                                                                    </tr> <?php } ?></thead>
                                                            </table>
                                                            <p style="margin:10px 0 36px;"><?php if ( ! empty( $user_message ) ) {
																	echo sprintf( 'User Message: %1$s', esc_html( $user_message ) );
																} ?></p></div>
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
	<?php
	$html = ob_get_clean();

	return $html;
}

function wqcmv_account_created_template( $username = '', $password = '' ) {
	$site_title = get_bloginfo();
	ob_start();
	?>
    <div id="wrapper" dir="ltr" style="background-color:#f5f5f5;margin:0;padding:70px 0 70px 0;width:100%">
        <div class="adM"></div>
        <table width="100%" height="100%">
            <tbody>
            <tr>
                <td valign="top" align="center">
                    <div id="template_header_image"><p
                                style="margin-top:0"><?php esc_html_e( $site_title, 'woocommerce-quick-cart-for-multiple-variations' ) ?></p>
                    </div>
                    <table id="template_container"
                           style="background-color:#fdfdfd;border:1px solid #dcdcdc;border-radius:3px" width="600"
                           cellspacing="0" cellpadding="0" border="0">
                        <tbody>
                        <tr>
                            <td valign="top" align="center">
                                <table id="template_header"
                                       style="background-color:#fff;color:#000;border-bottom:0;font-weight:bold;line-height:100%;vertical-align:middle;font-family:Helvetica Neue,Helvetica,Roboto,Arial,sans-serif;border-radius:3px 3px 0 0"
                                       width="600" cellspacing="0" cellpadding="0" border="0">
                                    <tbody>
                                    <tr>
                                        <td id="header_wrapper" style="padding:36px 48px;display:block"><h1
                                                    style="color:#000;font-family:&quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif;font-size:30px;font-weight:300;line-height:150%;margin:0;text-align:left"> <?php esc_html_e( 'Account Created.', 'woocommerce-quick-cart-for-multiple-variations' ); ?></h1>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" align="center">
                                <table id="template_body" width="600" cellspacing="0" cellpadding="0" border="0">
                                    <tbody>
                                    <tr>
                                        <td id="body_content" style="background-color:#fdfdfd" valign="top">
                                            <table width="100%" cellspacing="0" cellpadding="20" border="0">
                                                <tbody>
                                                <tr>
                                                    <td style="padding:0 48px 0" valign="top">
                                                        <div id="body_content_inner"
                                                             style="color:#737373;font-family:&quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif;font-size:14px;line-height:150%;text-align:left">
                                                            <p style="margin:0 0 16px"><?php echo sprintf( 'Hello %1$s, Your account is created successfully. Please login with the account details given below:', esc_html( $username ) ); ?></p>
                                                            <p><?php esc_html_e( 'Username: ' . $username, 'woocommerce-quick-cart-for-multiple-variations' ); ?></p>
                                                            <p><?php esc_html_e( 'Password: ' . $password, 'woocommerce-quick-cart-for-multiple-variations' ); ?></p>
                                                            <p><?php esc_html_e( 'This is a temparary password for the first login. You may change it later.' ) ?></p>
                                                            <p style="margin:0 0 16px"><?php esc_html_e( 'Thank you', 'woocommerce-quick-cart-for-multiple-variations' ); ?></p>
                                                            <p style="margin:10px 0 36px;"></p></div>
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
	<?php
	$html = ob_get_clean();

	return $html;
}

function wqcmv_account_confirmation_email_template() {

	$site_title = get_bloginfo();
	ob_start();
	?>
    <div id="wrapper" dir="ltr" style="background-color:#f5f5f5;margin:0;padding:70px 0 70px 0;width:100%">
        <div class="adM"></div>
        <table width="100%" height="100%">
            <tbody>
            <tr>
                <td valign="top" align="center">
                    <div id="template_header_image"><p
                                style="margin-top:0"><?php esc_html_e( $site_title, 'woocommerce-quick-cart-for-multiple-variations' ); ?></p>
                    </div>
                    <table id="template_container"
                           style="background-color:#fdfdfd;border:1px solid #dcdcdc;border-radius:3px" width="600"
                           cellspacing="0" cellpadding="0" border="0">
                        <tbody>
                        <tr>
                            <td valign="top" align="center">
                                <table id="template_header"
                                       style="background-color:#fff;color:#000;border-bottom:0;font-weight:bold;line-height:100%;vertical-align:middle;font-family:&quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif;border-radius:3px 3px 0 0"
                                       width="600" cellspacing="0" cellpadding="0" border="0">
                                    <tbody>
                                    <tr>
                                        <td id="header_wrapper" style="padding:36px 48px;display:block"><h1
                                                    style="color:#000;font-family:&quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif;font-size:30px;font-weight:300;line-height:150%;margin:0;text-align:left"> <?php esc_html_e( 'Account Created.', 'woocommerce-quick-cart-for-multiple-variations' ); ?></h1>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" align="center">
                                <table id="template_body" width="600" cellspacing="0" cellpadding="0" border="0">
                                    <tbody>
                                    <tr>
                                        <td id="body_content" style="background-color:#fdfdfd" valign="top">
                                            <table width="100%" cellspacing="0" cellpadding="20" border="0">
                                                <tbody>
                                                <tr>
                                                    <td style="padding:0 48px 0" valign="top">
                                                        <div id="body_content_inner"
                                                             style="color:#737373;font-family:&quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif;font-size:14px;line-height:150%;text-align:left">
                                                            <p style="margin:0 0 16px"><?php echo sprintf( 'Hello User, We are pleased to inform you that the %1$s product is back in stock. You may visit our store and grab it.' ); ?></p>
                                                            <p style="margin:0 0 16px"><?php esc_html_e( 'Thank you', 'woocommerce-quick-cart-for-multiple-variations' ); ?></p>
                                                            <p style="margin:10px 0 36px;"></p></div>
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
	<?php
	return ob_get_clean();

}

/**
 *
 * Create custom username.
 *
 * @param $username
 *
 * @return string
 */
function wqcmv_generate_custom_username( $username ) {
	$new_username = '';
	for ( $i=1; $i <= 5; $i++ ) {
		$new_username = sprintf( '%s%s', $username, $i );
		if ( ! username_exists( $new_username ) ) {
			break;
		}
	}
	return $new_username;
}

/**
 * This function returns the variable products for updating the variation table visibility option.
 *
 * @param int $limit
 * @param int $page
 * @param bool $paginate
 *
 * @return array|object
 */
function wqcmv_get_variable_products( $limit = 10, $page = 1, $paginate = false ) {
	$query         = new WC_Product_Query( array(
		'limit'    => $limit,
		'orderby'  => 'date',
		'order'    => 'DESC',
		'return'   => 'ids',
		'type'     => 'variable',
		'page'     => $page,
		'paginate' => $paginate,
	) );
	$query_results = $query->get_products();

	return $query_results;
}
/**
 * Register custom post type.
 * 
 * @since 1.0.0
 */
function wqcmv_custom_post_type_pre_order() {

	$labels = array(
		'name'                  => _x( 'Pre Order Request', 'Post Type General Name', 'woocommerce-quick-cart-for-multiple-variations' ),
		'singular_name'         => _x( 'Pre Order Requests', 'Post Type Singular Name', 'woocommerce-quick-cart-for-multiple-variations' ),
		'menu_name'             => __( 'Pre Order Requests', 'woocommerce-quick-cart-for-multiple-variations' ),
		'name_admin_bar'        => __( '', 'woocommerce-quick-cart-for-multiple-variations' ),
		'archives'              => __( 'Request Archives', 'woocommerce-quick-cart-for-multiple-variations' ),
		'attributes'            => __( 'Request Attributes', 'woocommerce-quick-cart-for-multiple-variations' ),
		'parent_item_colon'     => __( 'Parent Request:', 'woocommerce-quick-cart-for-multiple-variations' ),
		'all_items'             => __( 'All Requests', 'woocommerce-quick-cart-for-multiple-variations' ),
		'add_new_item'          => __( 'Add New Request', 'woocommerce-quick-cart-for-multiple-variations' ),
		'add_new'               => __( 'Add New', 'woocommerce-quick-cart-for-multiple-variations' ),
		'new_item'              => __( 'New Request', 'woocommerce-quick-cart-for-multiple-variations' ),
		'edit_item'             => __( 'Edit Request', 'woocommerce-quick-cart-for-multiple-variations' ),
		'update_item'           => __( 'Update Request', 'woocommerce-quick-cart-for-multiple-variations' ),
		'view_item'             => __( 'View Request', 'woocommerce-quick-cart-for-multiple-variations' ),
		'view_items'            => __( 'View Requests', 'woocommerce-quick-cart-for-multiple-variations' ),
		'search_items'          => __( 'Search Request', 'woocommerce-quick-cart-for-multiple-variations' ),
		'not_found'             => __( 'Not found', 'woocommerce-quick-cart-for-multiple-variations' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'woocommerce-quick-cart-for-multiple-variations' ),
		'featured_image'        => __( 'Featured Image', 'woocommerce-quick-cart-for-multiple-variations' ),
		'set_featured_image'    => __( 'Set featured image', 'woocommerce-quick-cart-for-multiple-variations' ),
		'remove_featured_image' => __( 'Remove featured image', 'woocommerce-quick-cart-for-multiple-variations' ),
		'use_featured_image'    => __( 'Use as featured image', 'woocommerce-quick-cart-for-multiple-variations' ),
		'insert_into_item'      => __( 'Insert into request', 'woocommerce-quick-cart-for-multiple-variations' ),
		'uploaded_to_this_item' => __( 'Uploaded to this request', 'woocommerce-quick-cart-for-multiple-variations' ),
		'items_list'            => __( 'Requests list', 'woocommerce-quick-cart-for-multiple-variations' ),
		'items_list_navigation' => __( 'Requests list navigation', 'woocommerce-quick-cart-for-multiple-variations' ),
		'filter_items_list'     => __( 'Filter requests list', 'woocommerce-quick-cart-for-multiple-variations' ),
	);
	$args = array(
		'label'                 => __( 'Pre Order Requests', 'woocommerce-quick-cart-for-multiple-variations' ),
		'description'           => __( 'Pre oder requests functionality', 'woocommerce-quick-cart-for-multiple-variations' ),
		'labels'                => $labels,
		'supports'              => array( 'title', 'editor', 'custom-fields' ),
		'hierarchical'          => true,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => false,
		'menu_position'         => 5,
		'show_in_admin_bar'     => false,
		'show_in_nav_menus'     => false,
		'can_export'            => true,
		'has_archive'           => false,
		'exclude_from_search'   => true,
		'publicly_queryable'    => true,
		'capability_type'       => 'page',
		'query_var'             => 'pre_order',
		'show_in_rest'          => true,

	);
	register_post_type( 'pre_order', $args );

}
