<?php
// This file is used to show user dashboard dropdown in header.

$current_logged_in_user = wp_get_current_user();
if ( $current_logged_in_user->exists() ) {
    // Get user data
    $user_id 		= 	$current_logged_in_user->ID;
    $first_name 	= 	$current_logged_in_user->user_firstname;
    $last_name 		= 	$current_logged_in_user->user_lastname;
	$user_nicename 	= 	$current_logged_in_user->user_nicename;
    $avatar 		= 	get_avatar( $user_id, 32 ); // 32 is the size of the avatar
}
?>
<ul class="my-account-user">
	<li class="my-account-user-info">
		<div class="user-gavatar"><?php echo wp_kses_post( $avatar ); ?></div>
		<?php if( !empty( $first_name ) && !empty( $last_name ) ) {?>
		<div class="user-fname-lname"><?php echo esc_html( $first_name ) .' '.esc_html( $last_name ); ?></div>
		<?php } else {?>
			<div class="user-fname-lname"><?php echo esc_html( $user_nicename ); ?></div>
		<?php } ?>
	</li>
	<li class="my-account-user-dashboard">
		<a href="/my-account/"><?php esc_html_e( 'Dashboard', 'supavapes' ); ?></a>
	</li>
	<li class="my-account-user-orders">
		<a href="/my-account/orders/"><?php esc_html_e( 'Orders', 'supavapes' ); ?></a>
	</li>
	<li class="my-account-user-notifications">
		<a href="/my-account/notification-preference/"><?php esc_html_e( 'Notification', 'supavapes' ); ?></a>
	</li>
	<li class="my-account-user-support-request">
		<a href="/my-account/support-request/"><?php esc_html_e( 'Support Request', 'supavapes' ); ?></a>
	</li>
	<li class="my-account-user-wishlist">
		<a href="/my-account/wishlist/"><?php esc_html_e( 'Wishlist', 'supavapes' ); ?></a>
	</li>
	<li class="my-account-user-address">
		<a href="/my-account/edit-address/"><?php esc_html_e( 'Addresses', 'supavapes' ); ?></a>
	</li>
	<li class="my-account-user-payment">
		<a href="/my-account/payment-methods/"><?php esc_html_e( 'Payment methods', 'supavapes' ); ?></a>
	</li>
	<li class="my-account-user-account">
		<a href="/my-account/edit-account/"><?php esc_html_e( 'Account details', 'supavapes' ); ?></a>
	</li>
	<li class="my-account-user-logout">
		<a href="/my-account/customer-logout/?_wpnonce=29cd3023d9"><?php esc_html_e( 'Logout', 'supavapes' ); ?></a>
	</li>
</ul>