<?php
$checkout_prevent_success_popup_image = get_field('checkout_prevent_success_popup_image','option');
$checkout_prevent_success_popup_thank_you_text = get_field('checkout_prevent_success_popup_thank_you_text','option');
$checkout_prevent_success_popup_thank_you_message = get_field('checkout_prevent_success_popup_thank_you_message','option');
?>
<div class="sv-email-modal" id="sv-prevent-checkout">
	<div class="overlay"></div>
    <div class="sv-email-modal__dialog">
        <div class="sv-email-modal__content">
            <div class="sv-email-modal__header">
                <?php if(isset($checkout_prevent_success_popup_image) && !empty($checkout_prevent_success_popup_image)){?>
                    <img src="<?php echo esc_url($checkout_prevent_success_popup_image['url']);?>" alt="sv-email" class="sv-email-modal__image">
                <?php }?>
                <button type="button" class="sv-email-modal__close-button" aria-label="Close">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                        <path d="M9.88542 8L15.6094 2.27604C16.1302 1.75521 16.1302 0.911458 15.6094 0.390625C15.0885 -0.130208 14.2448 -0.130208 13.724 0.390625L8 6.11458L2.27604 0.390625C1.75521 -0.130208 0.911458 -0.130208 0.390625 0.390625C-0.130208 0.911458 -0.130208 1.75521 0.390625 2.27604L6.11458 8L0.390625 13.724C-0.130208 14.2448 -0.130208 15.0885 0.390625 15.6094C0.651042 15.8698 0.992187 16 1.33333 16C1.67448 16 2.01563 15.8698 2.27604 15.6094L8 9.88542L13.724 15.6094C13.9844 15.8698 14.3255 16 14.6667 16C15.0078 16 15.349 15.8698 15.6094 15.6094C16.1302 15.0885 16.1302 14.2448 15.6094 13.724L9.88542 8Z" fill="black"/>
                    </svg>
                </button>
            </div>
            <div class="sv-email-modal__body">
                <div class="sv-email-modal__heading-info">
                    <?php if(!empty($checkout_prevent_success_popup_thank_you_text)){?>
                        <h2 class="sv-email-modal__title"><?php echo esc_html($checkout_prevent_success_popup_thank_you_text,'hello-elementor-child'); ?></h2>
                    <?php }?>
                    <?php if(!empty($checkout_prevent_success_popup_thank_you_message)){?>
                        <p class="sv-email-modal__description"><?php echo esc_html($checkout_prevent_success_popup_thank_you_message,'hello-elementor-child'); ?></p>
                    <?php }?>
                </div>
            </div>
        </div>
    </div>
</div>