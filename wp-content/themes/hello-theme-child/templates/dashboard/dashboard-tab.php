<?php
$user_id = get_current_user_id();
if ($user_id === 0) {
    return 'User not logged in.';
}
$customer_orders = wc_get_orders(array(
    'customer' => $user_id,
    'limit' => 1,
    'orderby' => 'date',
    'order' => 'DESC',
    'status' => array('pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded', 'failed') // Exclude 'draft' status
));
// debug();
// die('lkoooooooo');
if(!empty($customer_orders)){
	$order_data = $customer_orders[0];
	$order_id = $order_data->get_id();
	$order_date = $order_data->get_date_created()->date('Y-m-d H:i:s');
	$order_total = $order_data->get_total();
	$order_items = $order_data->get_items();
	$order_items = $order_data->get_items();
	$product_names = array();
	if(isset($order_items) && !empty($order_items)){
		foreach ($order_items as $item) {
			$product_name = $item->get_name();
			$product_names[] = $product_name;
		}
	}
}else{
	// echo "No orders found";
}


?>
	<h2 class="dashboard-main-title"><?php esc_html_e('Customer ','hello-elementor-child'); ?><span><?php esc_html_e('saved money ','hello-elementor-child'); ?></span><?php esc_html_e(' when not consuming','hello-elementor-child'); ?><span><?php esc_html_e(' cigar','hello-elementor-child'); ?></span></h2>
	<div class="chart-container">
		<div class="chart-header">
		<div class="year-dropdown">
            <label for="purchase-year" class="dashboard-box-title"><?php esc_html_e('Purchase Summary ss In','hello-elementor-child'); ?></label>
            <select id="purchase-year">
                <option value="2024"><?php esc_html_e('2024','hello-elementor-child'); ?></option>
                <option value="2023"><?php esc_html_e('2023','hello-elementor-child'); ?></option>
                <option value="2022"><?php esc_html_e('2022','hello-elementor-child'); ?></option>
                <option value="2021"><?php esc_html_e('2021','hello-elementor-child'); ?></option>
            </select>
        </div>
			
			<div class="tabs">
				<div class="tab" data-tab="day"><?php esc_html_e('Daily','hello-elementor-child'); ?></div>
				<div class="tab" data-tab="month"><?php esc_html_e('Monthly','hello-elementor-child'); ?></div>
				<div class="tab" data-tab="week"><?php esc_html_e('Weekly','hello-elementor-child'); ?></div>
			</div>
		</div>
		<div class="tab-content" id="day">
			<canvas id="dailyChart"></canvas>
		</div>
		<div class="tab-content" id="month">
			<canvas id="monthlyChart"></canvas>
		</div>
		<div class="tab-content" id="week">
			<canvas id="weeklyChart"></canvas>
		</div>
	</div>
	<div class="dashboard-six-box">
		<div class="dashboard-box">
			<h3 class="dashboard-box-title"><?php esc_html_e('Last Order','hello-elementor-child'); ?></h3>
			<?php if(isset($product_names) && !empty($product_names)){?>
			<div class="dashboard-box-data">
				<div class="dashboard-box-data-detail">
					<label class="dashboard-box-data-lable"><?php esc_html_e('Order id','hello-elementor-child'); ?></label>
					<p class="dashboard-box-data-value">#<?php echo esc_html($order_id); ?></p>
				</div>
				<div class="dashboard-box-data-detail">
					<label class="dashboard-box-data-lable"><?php esc_html_e('Product Name','hello-elementor-child'); ?></label>
					<?php
					$count = 1; 
					if (!empty($product_names)) { 
						foreach ($product_names as $product_name) {
							if($count === 1){
					?>
					<p class="dashboard-box-data-value"><?php echo esc_html($product_name,'hello-elementor-child'); ?></p>
					<?php 
						}
					$count++;
						}
					}
					?>
				</div>
				<div class="dashboard-box-data-detail">
					<label class="dashboard-box-data-lable"><?php esc_html_e('Order Total','hello-elementor-child'); ?></label>
					<p class="dashboard-box-data-value">$<?php echo esc_html($order_total); ?></p>
				</div>
			</div>
			<?php }else{
				esc_html_e("No order has been made yet.",'hello-elementor-child');	
			}?>
		</div>
		<div class="dashboard-box">
			<h3 class="dashboard-box-title"><?php esc_html_e('Money Savior','hello-elementor-child'); ?></h3>	
			<div class="money-save-box">	
				<h4 class="money-save-value"><?php echo do_shortcode('[total_savings]');?></h4>	
				<p class="money-save-text"><?php esc_html_e('Saved by not consuming cigarettes','hello-elementor-child'); ?></p>
			</div>
		</div>
		<div class="dashboard-box">
			<h3 class="dashboard-box-title"><?php esc_html_e('Notifications','hello-elementor-child'); ?></h3>
			<?php 
				$user_id = get_current_user_id();
				$email = get_user_meta( $user_id, 'email', true );
				// $whatsapp = get_user_meta( $user_id, 'whatsapp', true );
				$sms = get_user_meta( $user_id, 'sms', true );
			?>
			<div class="dashboard-box-notification">
				<span class="notification-selction">
					<?php 
					if($email === "on"){
						$email_class = "active-preference";
					}else{
						$email_class = "";
					}
					?>
					<label class="notification-selction-email <?php echo esc_attr($email_class); ?>">
						<svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M14.2552 11.9131L12.4127 13.7619C11.6655 14.5117 10.3508 14.5278 9.58746 13.7619L7.74483 11.9131L1.12695 18.5524C1.37329 18.6664 1.6449 18.7343 1.93365 18.7343H20.0665C20.3552 18.7343 20.6267 18.6664 20.873 18.5525L14.2552 11.9131Z" fill="#EC4E34"/>
							<path d="M20.0664 3.26562H1.93359C1.64484 3.26562 1.37324 3.3336 1.12698 3.44751L8.19865 10.5426C8.19912 10.5431 8.19968 10.5432 8.20016 10.5437C8.20045 10.544 8.20064 10.5444 8.20072 10.5448L10.5001 12.8518C10.7443 13.096 11.2557 13.096 11.5 12.8518L13.7989 10.5452C13.7989 10.5452 13.7995 10.5441 13.7999 10.5437C13.7999 10.5437 13.801 10.5431 13.8014 10.5426L20.8729 3.44747C20.6267 3.33352 20.3552 3.26562 20.0664 3.26562ZM0.205648 4.34895C0.0782031 4.60668 0 4.89277 0 5.19922V16.8008C0 17.1072 0.0781172 17.3933 0.205605 17.651L6.83495 11.0002L0.205648 4.34895ZM21.7944 4.34887L15.1651 11.0002L21.7944 17.6511C21.9218 17.3934 22 17.1073 22 16.8008V5.19922C22 4.89268 21.9218 4.60659 21.7944 4.34887Z" fill="#EC4E34"/>
						</svg><?php echo esc_html__('By Email','hello-elementor-child'); ?>
					</label>
				</span>
				<!-- <span class="notification-selction"> -->
					<?php 
					// if($whatsapp === "on"){
					// 	$whatsapp_class = "active-preference";
					// }else{
					// 	$whatsapp_class = "";
					// }
					?>
					<!-- <label class="notification-selction-whatsapp <?php //echo esc_attr($whatsapp_class); ?>">
						<svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M11.0027 0H10.9973C4.93213 0 0 4.9335 0 11C0 13.4062 0.7755 15.6365 2.09413 17.4474L0.72325 21.5339L4.95138 20.1823C6.69075 21.3345 8.76562 22 11.0027 22C17.0679 22 22 17.0651 22 11C22 4.93488 17.0679 0 11.0027 0ZM17.4034 15.5334C17.138 16.2827 16.0847 16.9042 15.2446 17.0857C14.6699 17.2081 13.9191 17.3057 11.3919 16.258C8.15925 14.9188 6.0775 11.6339 5.91525 11.4207C5.75988 11.2076 4.609 9.68138 4.609 8.10287C4.609 6.52437 5.41062 5.75575 5.73375 5.42575C5.99913 5.15488 6.43775 5.03113 6.8585 5.03113C6.99463 5.03113 7.117 5.038 7.227 5.0435C7.55013 5.05725 7.71237 5.0765 7.9255 5.58663C8.19088 6.226 8.83712 7.8045 8.91412 7.96675C8.9925 8.129 9.07088 8.349 8.96088 8.56213C8.85775 8.78213 8.767 8.87975 8.60475 9.06675C8.4425 9.25375 8.2885 9.39675 8.12625 9.5975C7.97775 9.77213 7.81 9.95913 7.997 10.2823C8.184 10.5985 8.83025 11.6531 9.78175 12.5001C11.0096 13.5932 12.0051 13.9425 12.3612 14.091C12.6266 14.201 12.9429 14.1749 13.1368 13.9686C13.3829 13.7033 13.6867 13.2633 13.9961 12.8301C14.2161 12.5194 14.4939 12.4809 14.7854 12.5909C15.0824 12.694 16.654 13.4709 16.9771 13.6317C17.3003 13.794 17.5134 13.871 17.5917 14.0071C17.6687 14.1433 17.6687 14.7826 17.4034 15.5334Z" fill="#EC4E34"/>
						</svg><?php //echo esc_html__('By Whatsapp','hello-elementor-child');?>
					</label> -->
				<!-- </span> -->
				<span class="notification-selction">
					<?php 
					if($sms === "on"){
						$sms_class = "active-preference";
					}else{
						$sms_class = "";
					}
					?>
					<label class="notification-selction-sms <?php echo esc_attr($sms_class); ?>">
						<svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M15.2129 1.3584H2.48801C1.11595 1.3584 0 2.47435 0 3.84641V14.9973C0 15.5254 0.597251 15.8231 1.01884 15.522L4.56264 12.9936C4.86 12.7818 5.20978 12.6696 5.57503 12.6696H13.3695C14.7416 12.6696 15.8575 11.5536 15.8575 10.1816V2.00296C15.8575 1.64716 15.5687 1.3584 15.2129 1.3584ZM12.0821 9.16662H4.63483C4.27903 9.16662 3.99027 8.87829 3.99027 8.52206C3.99027 8.16626 4.27903 7.8775 4.63483 7.8775H12.0821C12.4379 7.8775 12.7267 8.16626 12.7267 8.52206C12.7267 8.87829 12.4379 9.16662 12.0821 9.16662ZM12.0821 6.15866H4.63483C4.27903 6.15866 3.99027 5.87033 3.99027 5.5141C3.99027 5.1583 4.27903 4.86954 4.63483 4.86954H12.0821C12.4379 4.86954 12.7267 5.1583 12.7267 5.5141C12.7267 5.87033 12.4379 6.15866 12.0821 6.15866Z" fill="#EC4E34"/>
							<path d="M22.0001 8.84586V19.9963C22.0001 20.5209 21.4058 20.8243 20.9812 20.521L17.4374 17.993C17.1401 17.7812 16.7903 17.669 16.4251 17.669H8.63059C7.25853 17.669 6.14258 16.5527 6.14258 15.1806V13.9585H13.3685C15.4513 13.9585 17.1457 12.2642 17.1457 10.1814V6.35742H19.5121C20.8841 6.35742 22.0001 7.4738 22.0001 8.84586Z" fill="#EC4E34"/>
						</svg><?php esc_html_e('By Sms','hello-elementor-child');?>
					</label>
				</span>
			</div>
		</div>
		<div class="dashboard-box">
			<h3 class="dashboard-box-title"><?php esc_html_e('Last login','hello-elementor-child'); ?></h3>	
			<?php 
				if (is_user_logged_in()) {
					$user_id = get_current_user_id();
					$last_login = get_user_meta($user_id, 'last_login', true);
					$last_login_ip = get_user_meta($user_id, 'last_login_ip', true);
					$formatted_last_login = format_date($last_login);
					$location = get_location_from_ip($last_login_ip);
					$last_login_browser = get_user_meta( $user_id, 'last_login_browser', true );
					$last_login_device = get_user_meta($user_id, 'last_login_device', true);
			?>
			<div class="dashboard-box-data">
				<?php if(isset($last_login_device) && !empty($last_login_device)){?>
					<div class="dashboard-box-data-detail">
						<label class="dashboard-box-data-lable"><?php esc_html_e('Device & Browser','hello-elementor-child'); ?></label>
						<p class="dashboard-box-data-value"><?php echo esc_html__($last_login_device,'hello-elementor-child'); ?> | <?php echo esc_html__($last_login_browser,'hello-elementor-child'); ?></p>
					</div>
				<?php }?>
				<?php if(!empty($formatted_last_login)){?>
					<div class="dashboard-box-data-detail">
						<label class="dashboard-box-data-lable"><?php echo esc_html__('Date','hello-elementor-child'); ?></label>
						<p class="dashboard-box-data-value"><?php echo esc_html($formatted_last_login); ?></p>
					</div>
				<?php }?>
				<?php if(!empty($location)){?>
					<div class="dashboard-box-data-detail">
						<label class="dashboard-box-data-lable"><?php echo esc_html__('Location','hello-elementor-child'); ?></label>
						<p class="dashboard-box-data-value"><?php echo esc_html($location,'hello-elementor-child'); ?></p>
					</div>
				<?php }?>
			</div>
			<?php 
				} else {
					return '<p>Please log in to see your last login details.</p>';
				}
			?>
		</div>
		<div class="dashboard-box sv-active-offer-box">
			<h3 class="dashboard-box-title"><?php esc_html_e('Active Offers','hello-elementor-child'); ?></h3>
			<div class="sv-active-offer-slider">
    <?php
    $active_offers = supa_active_offers_from_discount();
    // debug($active_offers);
    foreach ($active_offers as $offer) {
        $offer_title = $offer['title'];
        $discount_value = isset($offer['discount_value']) ? $offer['discount_value'] : '';
        $product_category = isset($offer['product_category'][0]) ? $offer['product_category'][0] : '';
        $products = isset($offer['products']) ? $offer['products'] : '';
        $buy_type = isset($offer['buy_type']) ? $offer['buy_type'] : '';
        $buy = isset($offer['buy']) ? $offer['buy'] : '';
        $free_qty = isset($offer['free_qty']) ? $offer['free_qty'] : '';
        $free_products = isset($offer['free_products']) ? $offer['free_products'] : '';
        $title_to_display = '';
        $shop_now_url = ''; 
        $image_url = '';

        if (!empty($product_category)) {
            $term_val = get_term($product_category);
            // debug($term_val);
            if ($term_val && !is_wp_error($term_val)) {
                $title_to_display = $term_val->name;
                $shop_now_url = get_term_link($term_val);
                $thumbnail_id = get_term_meta($product_category, 'thumbnail_id', true);
                if ($thumbnail_id) {
                    $image_url = wp_get_attachment_url($thumbnail_id);
                }
            }
        } elseif (!empty($products)) {
            $product = wc_get_product($products[0]);
            if ($product) {
                $title_to_display = $product->get_name();
                $shop_now_url = get_permalink($product->get_id());
                $image_id = $product->get_image_id();
                if ($image_id) {
                    $image_url = wp_get_attachment_url($image_id);
                }
            }
        }
        ?>
        <div class="sv-deals-item-box">
            <div class="box-text">
                <?php if (!empty($buy)) : ?>
                    <span><?php esc_html_e('BUY','hello-elementor-child'); ?> <?php echo esc_html($buy); ?></span>
                <?php endif; ?>
                <h4><?php echo esc_html($title_to_display); ?></h4>
                <?php if (!empty($discount_value)) { ?>
                    <h4><?php esc_html_e('Get ','hello-elementor-child'); ?><span><?php echo esc_html($discount_value); ?></span></h4>
                <?php } else if (!empty($free_qty)) { ?>
                    <h4><?php esc_html_e('Get ','hello-elementor-child'); ?><span><?php echo esc_html($free_qty . " Free"); ?></span></h4>
                <?php } ?>
                <div class="sv-delas-item-list">
                    <ul><li><?php esc_html_e('Conditions may apply','hello-elementor-child'); ?></li></ul>
                </div>
                <a href="<?php echo esc_url($shop_now_url); ?>" tabindex="-1"><?php esc_html_e('Shop Now','hello-elementor-child'); ?></a>
            </div>
            <div class="box-image">
                <?php if (!empty($image_url)) : ?>
                    <img src="<?php echo esc_url($image_url); ?>" alt="image">
                <?php else : ?>
                    <img src="/wp-content/uploads/2024/07/placeholedr-img.png" alt="default image">
                <?php endif; ?>
            </div>
        </div>
    <?php } ?>
</div>

		</div>
		<div class="dashboard-box joke-slider">
		<?php
		if( have_rows('customer_dashboard_jokes','option') ):
			while( have_rows('customer_dashboard_jokes','option') ) : the_row();
			$joke_content = get_sub_field('joke_content','option');
				if(!empty($joke_content)){
				?>
				<div class="joke-text">
					<?php echo wp_kses_post($joke_content); ?>
				</div>
				<?php
				}
			endwhile;
		else :
		endif;?>
		</div>
	</div>