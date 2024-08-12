<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This html is for showing the success and error messages for the validation.
 */
?>
<div id="wqcmv-manage-outofstock-products-modal" class="modal modal-for-notify">
    <div class="notification_popup error">
        <div class="notification_icon"><i class="fa fa-shield" aria-hidden="true"></i></div>
        <div class="notification_message">
            <span class="title"></span>
        </div>
    </div>
    <div class="notification_popup success">
        <div class="notification_icon"><i class="fa fa-shield" aria-hidden="true"></i></div>
        <div class="notification_message">
            <span class="title"></span>
        </div>
    </div>
    <div class="modal-content">
        <div class="modal-header">
            <span class="close">×</span>
            <h2 class="wqcmv-modal-title"></h2>
        </div>
        <div class="modal-body wqcmv-manage-outofstock-products-modal-content">
            <div class="loader_page" id="loader" style="display:none">
                <div class="loader_row">
                    <span class="sv-loader"></span>
                </div>
            </div>
            <div class="wqcmv-modal-container">
                
            </div>
        </div>
    </div>
</div>