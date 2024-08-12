jQuery(document).ready(function ($) {
    'use strict';

    var WQCMVAdminJSFields = {
        init: function () {
            $(document).on('click', 'span.enable_wqcmv_discription_tab', this.wqcmvEnableDescriptionTab);
            $(document).on('click', '.close', this.wqcmvCloseModal);
            $(document).on('click', '.woocommerce_variation #wqcmv-view-customer-requests-log', this.wqcmvViewCustomerRequestsLog);
        },

        wqcmvEnableDescriptionTab: function (event) {
            var data = $(this);
            event.preventDefault();
            $(this).next('p.description').toggle();

        },

        wqcmvCloseModal: function (event) {

            event.preventDefault();
            $('.modal').fadeOut('slow');

        },

        wqcmvViewCustomerRequestsLog: function (event) {
            var data;
            var variationId = $(this).data('variation-id');
            var modelDiv = $('.wqcmv-notificaiton-request-logs-modal-content');
            var modalheader = $('#wqcmv-notificaiton-request-logs .modal-header');
            var imgTag = $('<img />', {src: WQCMVAdminJSObj.loader_url, alt: 'Loader'});
            event.preventDefault();
            $('.woocommerce_variation').removeClass('open closed');
            $('.woocommerce_variable_attributes').css('display', 'none');
            $('#wqcmv-notificaiton-request-logs').show();
            modelDiv.text('');
            $('<p />', {text: WQCMVAdminJSObj.fetch_notification_log_wait_message}).appendTo(imgTag);
            modelDiv.addClass('wqcmv-modal-loading');
            imgTag.appendTo(modelDiv);
            data = {
                'action': 'wqcmv_fetch_notifications_log',
                'variation_id': variationId
            };
            $.ajax({
                dataType: 'JSON',
                url: WQCMVAdminJSObj.ajaxurl,
                type: 'POST',
                data: data,
                success: function (response) {
                    var acc;
                    var i;
                    var panel;
                    if ('notifications-log-fetched' === response.data.message) {
                        modelDiv.text('');
                        modalheader.text('');
                        $('<div />', {class: 'wqcmv-notification-logs', html: response.data.html}).appendTo(modelDiv);
                        modelDiv.removeClass('wqcmv-modal-loading');
                        if ('' !== response.data.modal_title) {
                            $('<span />', {
                                class: 'close',
                                text: 'x'
                            }).appendTo(modalheader);
                            $('<h2 />', {
                                class: 'wqcmv-modal-title',
                                html: response.data.modal_title
                            }).appendTo(modalheader);
                        }
                        acc = document.getElementsByClassName('wqcmv-accordion');
                        for (i = 0; i < acc.length; i++) {
                            acc[i].addEventListener('click', function () {
                                this.classList.toggle('wqcmv-active');
                                panel = this.nextElementSibling;
                                if ('block' === panel.style.display) {
                                    panel.style.display = 'none';
                                } else {
                                    panel.style.display = 'block';
                                }
                            });
                        }
                    }
                },
            });

        }
    };
    WQCMVAdminJSFields.init();
});