<?php
/**
 * Template for Basic settings page.
 *
 * @package inactive-logout
 */

use Codemanas\InactiveLogout\Helpers;
?>

<div class="ina-settings-admin-wrap ina-settings-admin-support">
	<?php if ( ! Helpers::is_pro_version_active() ) { ?>
        <div class="ina-settings-admin-support-bg">
            <h3><?php esc_html_e( 'Need more features ?', 'inactive-logout' ); ?></h3>
            <p><?php esc_html_e( 'Among many other features/enhancements, inactive logout pro comes with a additional features if you feel like you need it. ', 'inactive-logout' ); ?><span class="dashicons dashicons-external"></span> <a target="_blank" href="https://www.inactive-logout.com/"><?php esc_html_e( 'Check out the pro version here', 'inactive-logout' ); ?></a></p>
            <ol>
                <li><a href="https://www.inactive-logout.com/blog/automatically-end-sessions-users-on-browser-tab-close-with-inactive-logout" target="_blank"><?php esc_html_e( 'Auto browser close logout.', 'inactive-logout' ); ?></a></li>
                <li><?php esc_html_e( 'Multiple tab sync.', 'inactive-logout' ); ?></li>
                <li><?php printf(esc_html__( '%1$sTrack Visitors%2$s based on %3$s(Login time, logout time, browser, online status, session duration, role, os, IP)%4$s', 'inactive-logout' ),'<a href="https://www.inactive-logout.com/blog/how-to-track-user-login-logout-history-using-inactive-logout-pro" target="_blank">','</a>','<strong>','</strong>'); ?></li>
                <li><?php esc_html_e( 'More options to role based settings.', 'inactive-logout' ); ?></li>
                <li><?php esc_html_e( 'Override multiple login priority.', 'inactive-logout' ); ?></li>
                <li><?php esc_html_e( 'Login Redirections - Redirect on login.', 'inactive-logout' ); ?></li>
                <li><?php esc_html_e( 'Logout Redirections - Redirect on Logout.', 'inactive-logout' ); ?></li>
                <li><?php esc_html_e( 'Login/Logout time - Last login and logout time.', 'inactive-logout' ); ?></li>
                <li><?php esc_html_e( 'Last Active Timestamp - When was user last active.', 'inactive-logout' ); ?></li>
                <li><?php esc_html_e( 'Online Status - Is user currently active or inactive.', 'inactive-logout' ); ?></li>
                <li><a href="https://www.inactive-logout.com/blog/how-to-force-logout-any-users-in-wordpress" target="_blank"><?php esc_html_e( 'Force logout by admin on any users.', 'inactive-logout' ); ?></a></li>
                <li><?php esc_html_e( 'Disable inactive logout for specified pages according to your need.', 'inactive-logout' ); ?></li>
                <li><a href="https://www.inactive-logout.com/blog/how-to-track-user-login-logout-history-using-inactive-logout-pro" target="_blank"><?php esc_html_e( 'Track User Session and Logout individually.', 'inactive-logout' ); ?></a></li>
                <li><a href="https://www.inactive-logout.com/blog/how-to-force-logout-any-users-in-wordpress" target="_blank"><?php esc_html_e( 'Logout popups for manually revoked user sessions.', 'inactive-logout' ); ?></a></li>
                <li><?php esc_html_e( 'Logout modal popup customizations.', 'inactive-logout' ); ?></li>
                <li><?php esc_html_e( 'Disable native wordpress login popup after logout.', 'inactive-logout' ); ?></li>
                <li><?php esc_html_e( 'Compatibility with OpenID Connect Generic Client WordPress plugin (Kinde supported).', 'inactive-logout' ); ?></li>
            </ol>
			<?php esc_html_e( 'and more..', 'inactive-logout' ); ?>
        </div>

        <div class="ina-settings-admin-support-bg">
            <p><?php printf(esc_html__( 'If you encounter any issues or have any queries please use the %1$s %2$ssupport forums%3$s or %1$s %4$ssend a support mail%3$s. I will reply to you at the earliest possible.', 'inactive-logout' ),'<span class="dashicons dashicons-external"></span>','<a href="https://wordpress.org/support/plugin/inactive-logout" target="_blank">','</a>','<a target="_blank" href="https://www.imdpen.com/contact" target="_blank">'); ?></p>
        </div>
	<?php } else { ?>
        <div class="ina-settings-admin-support-bg">
            <h3><?php esc_html_e( 'Premium Support Ticket', 'inactive-logout' ); ?></h3>
            <p><?php printf(esc_html__( 'Create a ticket from %1$s %2$sSupport forum%3$s. Check %1$s %4$ssite%3$s for recent change logs and updates.', 'inactive-logout' ),'<span class="dashicons dashicons-external"></span>','<a target="_blank" href="https://inactive-logout.com/support/">','</a>','<a target="_blank" href="https://inactive-logout.com/changelogs/">'); ?></p>
        </div>
	<?php } ?>

    <div class="ina-settings-admin-support-bg">
        <h3><?php esc_html_e( 'Want to Contribute with Translations?', 'inactive-logout' ); ?></h3>
        <p><?php printf(esc_html__( 'We really appreciate and welcome translation contributions. Please send us an email at %s with translation file if you want to contribute.', 'inactive-logout' ),'<a href="mailto:support@inactive-logout.com" target="_blank">support@inactive-logout.com</a>'); ?></p>
    </div>

    <div class="ina-settings-admin-support-bg">
        <h3><?php esc_html_e( 'Rate Inactive Logout', 'inactive-logout' ); ?></h3>
        <p><?php printf(esc_html__( 'We really appreciate if you can spare a minute to %1$s %2$srate the plugin%3$s.', 'inactive-logout' ),'<span class="dashicons dashicons-external"></span>','<a href="https://wordpress.org/support/plugin/inactive-logout/reviews/?filter=5#new-post" target="_blank">','</a>'); ?></p>
    </div>

    <div class="ina-settings-admin-support-bg">
        <h3><?php esc_html_e( 'Developer', 'inactive-logout' ); ?></h3>
        <p><?php printf(esc_html__( 'Feel free to reach me from %1$s %2$sHere%3$s, if you have any questions or queries.', 'inactive-logout' ),'<span class="dashicons dashicons-external"></span>','<a href="https://www.imdpen.com/contact" target="_blank">','</a>'); ?></p>
    </div>
</div>
