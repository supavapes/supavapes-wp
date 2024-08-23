<?php
$subscriber_modal_featured = get_field('subscriber_modal_featured','option');
$subscribe_modal_popup_text = get_field('subscribe_modal_popup_text','option');
$subscribe_modal_form_text = get_field('subscribe_modal_form_text','option');
?>
<div class="deal-popup">
    <div class="overlay"></div>
    <div class="deal-popup-content">
        <span class="deal-popup-close"><svg width="23" height="23" viewBox="0 0 23 23" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M0.715756 20.4308L20.1773 0.969284L22.2927 3.08467L2.83114 22.5462L0.715756 20.4308ZM0.480713 2.80262L2.50208 0.78125L22.4807 20.7599L20.4593 22.7812L0.480713 2.80262Z" fill="white"/>
            </svg>
            </span>
		<div class="deal-popup-content-box">
		<div class="deal-popup-content-detail">
        <div class="deal-popup-left">
            <?php if($subscriber_modal_featured === 'video'){
                    $subscriber_modal_video = get_field('subscriber_modal_video','option');
                ?>
                 <video preload="auto" id="deal-popup-video" autoplay="autoplay" loop="loop" muted playsinline>
					<source src="<?php echo esc_url($subscriber_modal_video); ?>" type="video/mp4">
					<?php esc_html_e('Your browser does not support the video tag.','hello-elementor-child'); ?>
				</video>
                <?php }else{
                    $subscriber_modal_image = get_field('subscriber_modal_image','option');
                ?>
                <img src="<?php echo esc_url($subscriber_modal_image['url']); ?>">
            <?php }?>
            
        </div>
        <div class="deal-popup-right">
            <h2><?php echo wp_kses_post($subscribe_modal_popup_text); ?></h2>
            <p><?php echo esc_html($subscribe_modal_form_text,'hello-elementor-child'); ?></p>
            <?php echo do_shortcode('[mailchimp_form button_text="Sign Up"]'); ?>
        </div>
        </div>
		</div>       
    </div>
</div> 