<?php
$current_logged_in_user = wp_get_current_user();
if ( $current_logged_in_user->exists() ) {
    // Get user data
    $user_id = $current_logged_in_user->ID;
    $avatar = get_avatar($user_id, 32); // 32 is the size of the avatar
}
?>
<div class="user-header-gavatar"><?php echo wp_kses_post($avatar); ?></div>