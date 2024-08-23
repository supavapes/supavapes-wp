<?php
$current_logged_in_user = wp_get_current_user();
if ( $current_logged_in_user->exists() ) {
    // Get user data
    $user_id = $current_logged_in_user->ID;
    $first_name = $current_logged_in_user->user_firstname;
    $last_name = $current_logged_in_user->user_lastname;
	$user_nicename = $current_logged_in_user->user_nicename;
    $avatar = get_avatar($user_id, 32); // 32 is the size of the avatar
}
?>
<ul class="my-account-user">
		<li class="my-account-user-info">
			<div class="user-gavatar"><?php echo wp_kses_post($avatar); ?></div>
			<?php if(!empty($first_name) && !empty($last_name)) {?>
			<div class="user-fname-lname"><?php echo esc_html($first_name) .' '.esc_html($last_name); ?></div>
			<?php }else{?>
				<div class="user-fname-lname"><?php echo esc_html($user_nicename); ?></div>
			<?php }?>
		</li>
		<li class="my-account-user-dashboard">
			<a href="/my-account/"><?php echo esc_html('Dashboard','hello-elementor-child'); ?></a>
		</li>
		<li class="my-account-user-orders">
			<a href="/my-account/orders/"><?php echo esc_html('Orders','hello-elementor-child'); ?></a>
		</li>
		<li class="my-account-user-notifications">
			<a href="/my-account/notification-preference/"><?php echo esc_html('Notification','hello-elementor-child'); ?></a>
		</li>
		<li class="my-account-user-support-request">
			<a href="/my-account/support-request/"><?php echo esc_html('Support Request','hello-elementor-child'); ?></a>
		</li>
		<li class="my-account-user-wishlist">
			<a href="/my-account/wishlist/"><?php echo esc_html('Wishlist','hello-elementor-child'); ?></a>
		</li>
		<li class="my-account-user-address">
			<a href="/my-account/edit-address/"><?php echo esc_html('Addresses','hello-elementor-child'); ?></a>
		</li>
		<li class="my-account-user-payment">
			<a href="/my-account/payment-methods/"><?php echo esc_html('Payment methods','hello-elementor-child'); ?></a>
		</li>
		<li class="my-account-user-account">
			<a href="/my-account/edit-account/"><?php echo esc_html('Account details','hello-elementor-child'); ?></a>
		</li>
		<li class="my-account-user-logout">
			<a href="/my-account/customer-logout/?_wpnonce=29cd3023d9"><?php echo esc_html('Logout','hello-elementor-child'); ?></a>
		</li>
	</ul>