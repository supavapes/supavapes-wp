<?php
/**
 * This file is used to get mailchimp form in subscribal modal popup.
 */
defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

// Retrieve the shortcode attributes
$atts = get_query_var('shortcode_atts', array(
    'button_text' => 'Send'
));
?>
<div id="mc_embed_shell">
        <link href="//cdn-images.mailchimp.com/embedcode/classic-061523.css" rel="stylesheet" type="text/css">
        <style type="text/css">
            #mc_embed_signup { background:#fff; clear:left; font:14px Helvetica,Arial,sans-serif; width: 600px; }
        </style>
        <div id="mc_embed_signup">
            <form action="https://supavapes.us22.list-manage.com/subscribe/post?u=b5352c22d6f33a44b90a55035&amp;id=9b62d40b4c&amp;f_id=00d2cee1f0" method="post" id="mc-embedded-subscribe-form-modal" name="mc-embedded-subscribe-form-modal" class="validate" target="_blank">
                <div id="mc_embed_signup_scroll" class="modal-subscribe-form footer-subscribe-form">
                    <div class="indicates-required"><span class="asterisk">*</span> indicates required</div>
                    <div class="mc-field-group">
                        <input type="email" name="EMAIL" placeholder="Enter Your Email Address" class="required email" id="mce-EMAIL" required="" value="">
                    </div>
                    <div class="mc-field-group">
                        <input type="text" name="PHONE" placeholder="Enter Your Phone Number" class="REQ_CSS" id="mce-PHONE" value="">
                    </div>
                    <div id="mce-responses" class="clear foot">
                        <div class="response" id="mce-error-response"></div>
                        <div class="response" id="mce-success-response"></div>
                    </div>
                    <div aria-hidden="true" style="position: absolute; left: -5000px;">
                        <input type="text" name="b_b5352c22d6f33a44b90a55035_9b62d40b4c" tabindex="-1" value="">
                    </div>
                    <div class="optionalParent">
                        <div class="clear foot">
                            <button type="submit" name="subscribe" id="mc-embedded-subscribe" class="button" value="<?php echo esc_attr($atts['button_text']); ?>">
                                <span><?php echo esc_html($atts['button_text']); ?></span>
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M17.1685 6.83144L9.2485 12.3303L0.964495 9.56858C0.386257 9.37544 -0.00330583 8.83293 2.11451e-05 8.22343C0.0033919 7.61394 0.39742 7.07475 0.97789 6.88835L22.1573 0.0678196C22.6607 -0.0940205 23.2133 0.038796 23.5872 0.412775C23.9612 0.786754 24.094 1.3393 23.9322 1.84276L17.1116 23.0221C16.9252 23.6026 16.386 23.9966 15.7766 24C15.1671 24.0033 14.6245 23.6137 14.4314 23.0355L11.6563 14.7114L17.1685 6.83144Z" fill="#EC4E34"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <script type="text/javascript" src="//s3.amazonaws.com/downloads.mailchimp.com/js/mc-validate.js"></script>
        <script type="text/javascript">
            (function($) {window.fnames = new Array(); window.ftypes = new Array();fnames[0]='EMAIL';ftypes[0]='email';fnames[4]='PHONE';ftypes[4]='phone';}(jQuery));var $mcj = jQuery.noConflict(true);
        </script>
    </div>