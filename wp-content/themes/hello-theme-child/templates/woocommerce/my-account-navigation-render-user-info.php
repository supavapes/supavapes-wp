<?php
/**
 * This file is used to get user info with gravatar on my account page.
 */
defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

$current_logged_in_user = wp_get_current_user();
// debug($current_user);
if ( $current_logged_in_user->exists() ) {
    // Get user data
    $user_id = $current_logged_in_user->ID;
    $first_name = $current_logged_in_user->user_firstname;
    $last_name = $current_logged_in_user->user_lastname;
    $user_nicename = $current_logged_in_user->user_nicename;
    $email = $current_logged_in_user->user_email;
    $avatar = get_avatar($user_id, 150); // 150 is the size of the avatar
    $custom_avatar = get_user_meta($user_id, 'profile_picture', true);
    $biography = get_user_meta($user_id, 'description', true);
    ?>
    <div class="user-account-info">
        <div class="user-avatar">
            <?php echo wp_kses_post($avatar); ?>
            <div class="upload-icon">
                <img src="/wp-content/uploads/2024/06/upload.png" alt="Upload Icon" id="uploadIcon" />
                <input type="file" id="fileInput" accept="image/*" style="display: none;">
            </div>
        </div>
        <div class="user-details">
            <?php if(!empty($first_name) && !empty($last_name)) {?>
                <p class="user-name"><?php echo esc_html($first_name) . ' ' . esc_html($last_name); ?></p>
            <?php }else{?>
                <p class="user-name"><?php echo esc_html($user_nicename); ?></p>
            <?php }?>
            <p class="user-email"><?php echo esc_html($email); ?></p>
            <?php if ($custom_avatar): ?>
                <a href="javascript:void(0);" class="sv-remove-avatar"><?php esc_html_e('Remove Avatar', 'hello-elementor-child'); ?></a>
            <?php endif; ?>
        </div>
    </div>
    <?php
    if (!empty($biography)) {
        echo '<p class="user-biography">' . esc_html($biography) . '</p>';
    }
}