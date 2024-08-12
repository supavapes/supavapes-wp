jQuery(document).ready(function ($) {
    'use strict';
    $('.vi-ui.dropdown').dropdown();

    $('#s2w-cron_update_orders_date_paid').on('change', function () {
        let t = $(this);
        check_order_update_paid(t);
    });
    check_order_update_paid($('#s2w-cron_update_orders_date_paid'));

    function check_order_update_paid(t) {
        if (t.prop('checked')) {
            $('.wrap_cron_update_orders_options').addClass('s2w-hidden');
        } else {
            $('.wrap_cron_update_orders_options').removeClass('s2w-hidden');
        }
    }

    $('input[name="s2w_save_cron_update_orders"]').on('click', function (e) {
        if (!$('#s2w-cron_update_orders_options').val()) {
            alert('Please select at least one option to update');
            e.preventDefault();
        }
        if (!$('#s2w-cron_update_orders_status').val()) {
            alert('Please select order status you want to update');
            e.preventDefault();
        }
    });
});
