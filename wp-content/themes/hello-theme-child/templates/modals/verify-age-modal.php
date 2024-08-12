<?php
$verify_age_popup_heading = get_field('verify_age_popup_heading','option');
$verify_age_popup_sub_heading = get_field('verify_age_popup_sub_heading','option');
$verify_age_agree_button_text = get_field('verify_age_agree_button_text','option');
$verify_age_disagree_button_text = get_field('verify_age_disagree_button_text','option');
?>
<div class="hulk_age_verify" id="hulk_age_verify" style="display:none;">
    <div class="back_imge template-1 " id="preview_img">
        <div class="preview_box"> 
            <section class="preview_box_section">
                <div class="preview_box_wrap">  
                    <div class="logo" style="background-color: #ffffff;">
                    </div>
                    <?php if(isset($verify_age_popup_heading) && !empty($verify_age_popup_heading)){?>
                        <h2 class="pre_title" id="preview_header_text" style="font-weight: 700;"><?php echo esc_html($verify_age_popup_heading,'hello-elementor-child'); ?></h2>
                    <?php }?>
                    <?php if(isset($verify_age_popup_sub_heading) && !empty($verify_age_popup_sub_heading)){?>
                        <p class="pre_contain" id="preview_sub_header_text" style="font-weight: 400;"><?php echo esc_html($verify_age_popup_sub_heading,'hello-elementor-child'); ?></p>
                    <?php }?>
                    <div class="preview_btn" style="background-color:  transparent ;">
                        <span class="main-box-bg" style="border-top-color:#ffffff;"></span>
                        <?php if(isset($verify_age_agree_button_text) && !empty($verify_age_agree_button_text)){?>
                        <div class="agree"> 
                            <button type="button" data-verified="true" class="agree_btn btn_verified">
                                <span><?php echo esc_html($verify_age_agree_button_text,'hello-elementor-child'); ?></span>
                            </button>
                        </div>
                        <?php }?>
                        <?php if(isset($verify_age_disagree_button_text) && !empty($verify_age_disagree_button_text)){?>
                        <div class="disagree" style="margin-left:15px;"> 
                            <button type="button" data-verified="false" class="disagree_btn btn_verified" id="btn_disabled">
                                <span><?php echo esc_html($verify_age_disagree_button_text,'hello-elementor-child'); ?></span>
                            </button>
                        </div>
                        <?php }?>
                    </div>
                </div> 
            </section>
        </div>
        <div class="av_overlay"></div>
    </div>
</div>