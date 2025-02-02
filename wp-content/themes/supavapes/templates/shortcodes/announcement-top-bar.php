<?php
/**
 * This file is used to show content on top of the site as announcement bar.
 */
defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

$announcement_bar_heading = get_field( 'announcement_bar_heading', 'option' );
$announcement_bar_button  = get_field( 'announcement_bar_button', 'option' );
?>
<div class="announcement-bar" style="color: #ffffff; background: #ec4e34;" data-announcement-bar="">     
	<div class="announcement-bar-text">
		<span class="announcement-bar-close">x</span>
		<?php echo esc_html( $announcement_bar_heading ); ?>
		<div class="annoucement_contdown">
			<div class="countdown">
				<div id="countdown-timer">
					<div class="countdown-detail">
						<div id="hours" class="countdown-detail"></div>
						<span><?php esc_html_e( 'Hours', 'supavapes' ); ?></span>
					</div>
					<div class="countdown-detail">
						<div id="minutes" class="countdown-detail"></div>
						<span><?php esc_html_e( 'Minutes', 'supavapes' ); ?></span>
					</div>
					<div class="countdown-detail">
						<div id="seconds" class="countdown-detail"></div>
						<span><?php esc_html_e( 'Seconds', 'supavapes' ); ?></span>
					</div>                 
				</div>
			</div>
		</div>
		<?php if ( isset( $announcement_bar_button ) && ! empty( $announcement_bar_button ) ) { ?>
		<a class="announcement-bar-link" href="<?php echo esc_url( $announcement_bar_button['url'] ); ?>" target="_blank">
			<span><?php echo esc_html( $announcement_bar_button['title'] ); ?></span>
			<span class="svg">
				<svg width="13" height="13" viewBox="0 0 13 13" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M1.83907 0.00415039C1.1099 0.00415039 0.505737 0.608317 0.505737 1.33748V10.6708C0.505737 11.4 1.1099 12.0042 1.83907 12.0042H11.1724C11.9016 12.0042 12.5057 11.4 12.5057 10.6708V7H11.1724V10.6708H1.83907V1.33748H5.5V0.00415039H1.83907ZM7.83907 0.00415039V1.33748H10.2314L4.03352 7.53193L4.97796 8.47637L11.1724 2.28193V4.67082H12.5057V0.00415039H7.83907Z" fill="#777BF7"></path>
				</svg>
			</span>
		</a>
		<?php } ?>
	</div>
</div>
<?php
		$state      = isset( $_COOKIE['user_state'] ) ? sanitize_text_field( $_COOKIE['user_state'] ) : '';
		$country    = isset( $_COOKIE['user_country'] ) ? sanitize_text_field( $_COOKIE['user_country'] ) : '';
		$ip_address = $_SERVER['REMOTE_ADDR']; // Get user IP address
		$access_key = '8cccc64e392297'; // Get your free access key from ipinfo.io

		// Fetch location data based on the user's IP address
		$url      = "https://ipinfo.io/{$ip_address}?token={$access_key}";
		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) ) {
			return; // Handle error
		}

				$body = wp_remote_retrieve_body( $response );
				$data = json_decode( $body, true );

		if ( ! empty( $data ) ) {
			$ipinfo_region  = isset( $data['region'] ) ? $data['region'] : '';
			$ipinfo_country = isset( $data['country'] ) ? $data['country'] : '';
		}

?>
<div class="location-btn-wrap">
	<div class="location-btn-wrap-content">
		<?php if ( ! isset( $_COOKIE['user_city'] ) && empty( $_COOKIE['user_city'] ) || ! isset( $_COOKIE['user_state'] ) && empty( $_COOKIE['user_state'] ) || ! isset( $_COOKIE['user_country'] ) && empty( $_COOKIE['user_country'] ) ) { ?>
			<span class="location-country"><?php echo esc_html( $ipinfo_region ); ?>,</span>
			<span class="location-country"><?php echo esc_html( $ipinfo_country ); ?></span>
		<?php } else { ?>
			<span class="location-country"><?php echo esc_html( $state ); ?>,</span>
			<span class="location-country"><?php echo esc_html( $country ); ?></span>
		<?php } ?>
		<button class="edit-location-btn" id="edit-user-location-btn">
			<svg width="26" height="26" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
				<circle cx="13" cy="13" r="13" fill="white"/>
				<path d="M14.0713 8.26136C14.376 7.88128 14.3148 7.32623 13.9347 7.02161C13.5546 6.717 12.9996 6.77816 12.695 7.15824L14.0713 8.26136ZM6.4476 16.3634L7.11556 16.9393C7.12245 16.9313 7.12918 16.9231 7.13578 16.9149L6.4476 16.3634ZM6.24652 16.8549L5.3663 16.7974L5.36552 16.8141L6.24652 16.8549ZM6.08189 20.4097L5.20089 20.3689C5.19887 20.4127 5.2001 20.4565 5.20461 20.5002L6.08189 20.4097ZM7.00734 21.2176L7.03617 22.0991C7.09499 22.0971 7.15346 22.0893 7.21072 22.0757L7.00734 21.2176ZM10.5351 20.3815L10.7385 21.2397L10.7522 21.2363L10.5351 20.3815ZM10.9878 20.0946L11.6682 20.6559L11.6758 20.6463L10.9878 20.0946ZM18.7784 11.7893C19.0831 11.4093 19.022 10.8542 18.6421 10.5495C18.2622 10.2448 17.707 10.3058 17.4023 10.6858L18.7784 11.7893ZM12.6987 7.15801C12.394 7.53798 12.4549 8.09305 12.8349 8.3978C13.2149 8.70254 13.7699 8.64155 14.0747 8.26159L12.6987 7.15801ZM15.254 5.38148L15.9421 5.93328C15.9536 5.91882 15.9648 5.90401 15.9755 5.88885L15.254 5.38148ZM17.1696 5.00519L17.7338 4.32734C17.7057 4.30396 17.6762 4.28236 17.6455 4.26264L17.1696 5.00519ZM19.8001 7.19474L20.4243 6.57169C20.4051 6.55251 20.3851 6.53424 20.3643 6.5169L19.8001 7.19474ZM19.7895 9.12089L19.1722 8.49102C19.1473 8.51553 19.1237 8.54149 19.1018 8.56879L19.7895 9.12089ZM17.4023 10.6858C17.0974 11.0657 17.1584 11.6204 17.5383 11.9253C17.9181 12.2302 18.4735 12.1691 18.7784 11.7893L17.4023 10.6858ZM14.2589 7.5791C14.1867 7.0974 13.7377 6.76541 13.256 6.8376C12.7743 6.90978 12.4423 7.35879 12.5145 7.8405L14.2589 7.5791ZM18.2091 12.1114C18.6917 12.0459 19.0298 11.6014 18.9643 11.1188C18.8987 10.6362 18.4543 10.2981 17.9716 10.3636L18.2091 12.1114ZM12.695 7.15824L5.75942 15.8118L7.13578 16.9149L14.0713 8.26136L12.695 7.15824ZM5.77964 15.7874C5.53581 16.0703 5.3906 16.4248 5.3663 16.7974L7.12659 16.9123C7.12594 16.9222 7.12207 16.9317 7.11556 16.9393L5.77964 15.7874ZM5.36552 16.8141L5.20089 20.3689L6.96289 20.4505L7.12751 16.8957L5.36552 16.8141ZM5.20461 20.5002C5.30065 21.4314 6.10056 22.1296 7.03617 22.0991L6.9785 20.3361C6.97474 20.3362 6.97289 20.3357 6.97169 20.3352C6.97001 20.3346 6.96779 20.3334 6.96552 20.3314C6.96324 20.3294 6.96177 20.3274 6.96093 20.3259C6.96033 20.3247 6.95956 20.323 6.95917 20.3192L5.20461 20.5002ZM7.21072 22.0757L10.7385 21.2397L10.3317 19.5233L6.80395 20.3594L7.21072 22.0757ZM10.7522 21.2363C11.1114 21.145 11.4323 20.9418 11.6682 20.6559L10.3074 19.5333C10.3102 19.53 10.3139 19.5277 10.318 19.5266L10.7522 21.2363ZM11.6758 20.6463L18.7784 11.7893L17.4023 10.6858L10.2998 19.5429L11.6758 20.6463ZM14.0747 8.26159L15.9421 5.93328L14.566 4.82969L12.6987 7.15801L14.0747 8.26159ZM15.9755 5.88885C16.1384 5.65705 16.4551 5.59484 16.6937 5.74774L17.6455 4.26264C16.6115 3.60006 15.2391 3.86965 14.5326 4.87412L15.9755 5.88885ZM16.6054 5.68303L19.2359 7.87259L20.3643 6.5169L17.7338 4.32734L16.6054 5.68303ZM19.176 7.8178C19.2652 7.90728 19.3151 8.02875 19.3144 8.15518L21.0782 8.16487C21.0815 7.56785 20.8461 6.99426 20.4243 6.57169L19.176 7.8178ZM19.3144 8.15518C19.3138 8.28162 19.2625 8.40253 19.1722 8.49102L20.4069 9.75077C20.8333 9.33286 21.0749 8.7619 21.0782 8.16487L19.3144 8.15518ZM19.1018 8.56879L17.4023 10.6858L18.7784 11.7893L20.4773 9.67301L19.1018 8.56879ZM12.5145 7.8405C12.9253 10.5819 15.4623 12.4846 18.2091 12.1114L17.9716 10.3636C16.1808 10.607 14.5267 9.36641 14.2589 7.5791L12.5145 7.8405Z" fill="#EC4E34"/>
			</svg>
		</button>
	</div>
</div>
