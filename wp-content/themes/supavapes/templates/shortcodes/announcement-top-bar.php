<?php 

$announcement_bar_heading = get_field('announcement_bar_heading','option');
$announcement_bar_button = get_field('announcement_bar_button','option');
?>

<div class="announcement-bar" style="color: #ffffff; background: #ec4e34;" data-announcement-bar="">     
		<div class="announcement-bar-text">
			<span class="announcement-bar-close">x</span>
			<?php echo esc_html($announcement_bar_heading); ?>
			<div class="annoucement_contdown">
				<div class="countdown">
					<div id="countdown-timer">
						<div class="countdown-detail">
							<div id="hours" class="countdown-detail"></div>
							<span><?php echo esc_html__('Hours','hello-elementor-child'); ?></span>
						</div>
						<div class="countdown-detail">
							<div id="minutes" class="countdown-detail"></div>
							<span><?php echo esc_html__('Minutes','hello-elementor-child'); ?></span>
						</div>
						<div class="countdown-detail">
							<div id="seconds" class="countdown-detail"></div>
							<span><?php echo esc_html__('Seconds','hello-elementor-child'); ?></span>
						</div>                 
					</div>
				</div>
			</div>
			<?php if(isset($announcement_bar_button) && !empty($announcement_bar_button)){?>
			<a class="announcement-bar-link" href="<?php echo esc_url($announcement_bar_button['url']); ?>" target="_blank">
				<span><?php echo esc_html__($announcement_bar_button['title'],'hello-elementor-child'); ?></span>
				<span class="svg">
					<svg width="13" height="13" viewBox="0 0 13 13" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M1.83907 0.00415039C1.1099 0.00415039 0.505737 0.608317 0.505737 1.33748V10.6708C0.505737 11.4 1.1099 12.0042 1.83907 12.0042H11.1724C11.9016 12.0042 12.5057 11.4 12.5057 10.6708V7H11.1724V10.6708H1.83907V1.33748H5.5V0.00415039H1.83907ZM7.83907 0.00415039V1.33748H10.2314L4.03352 7.53193L4.97796 8.47637L11.1724 2.28193V4.67082H12.5057V0.00415039H7.83907Z" fill="#777BF7"></path>
					</svg>
				</span>
            </a>
			<?php }?>
		</div>
	</div>