jQuery('.approve-support-request').on('click', function(e) {
    e.preventDefault();
    var post_id = jQuery(this).data('id');
    jQuery('#support-request-loader').show();
    jQuery('#decline-dialog').css('display','none');
    if (confirm('Are you sure you want to approve this support request?')) {
        jQuery.ajax({
            url: supportRequest.ajax_url,
            type: 'POST',
            data: {
                action: 'approve_support_request',
                post_id: post_id,
                // nonce: supportRequest.nonce
            },
            success: function(response) {
                if (response.success) {
                    // alert('Support request approved and order created.');
                    jQuery('#support-request-loader').hide();
                    location.reload();
                } else {
                    alert('Failed to approve support request: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                alert('An error occurred: ' + error);
            }
        });
    }
});

jQuery('.decline-support-request').on('click', function(e) {
    e.preventDefault();
    jQuery('#decline-dialog').css('display','block');   
});


jQuery('#submit-decline-reason').on('click', function(e) {
    e.preventDefault();
    var post_id = jQuery(this).data('id');
    jQuery('#support-request-loader').show();
    var reason = jQuery('#decline-reason').val();
        jQuery.ajax({
            url: supportRequest.ajax_url,
            type: 'POST',
            data: {
                action: 'decline_support_request',
                post_id: post_id,
                reason: reason,
                nonce: supportRequest.nonce
            },
            success: function(response) {
                if (response.success) {
                    // alert('Support request declined and customer notified.');
                    jQuery('#support-request-loader').hide();
                    location.reload();
                } else {
                    alert('Failed to decline support request: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                alert('An error occurred: ' + error);
            }
        });
});


jQuery('.decline-support-request').on('click', function(e) {
    e.preventDefault();
    var post_id = jQuery(this).data('id');
    // Open the dialog
    // jQuery('#decline-dialog').dialog({
    //     modal: true,
    //     buttons: {
    //         "Decline": function() {
    //             var reason = jQuery('#decline-reason').val();
    //             if (reason.trim() === '') {
    //                 alert('Please provide a reason for declining.');
    //                 return;
    //             }

    //             jQuery.ajax({
    //                 url: supportRequest.ajax_url,
    //                 type: 'POST',
    //                 data: {
    //                     action: 'decline_support_request',
    //                     post_id: post_id,
    //                     nonce: supportRequest.nonce,
    //                     reason: reason
    //                 },
    //                 success: function(response) {
    //                     if (response.success) {
    //                         alert('Support request declined and customer notified.');
    //                         location.reload();
    //                     } else {
    //                         alert('Failed to decline support request: ' + response.data);
    //                     }
    //                 },
    //                 error: function(xhr, status, error) {
    //                     alert('An error occurred: ' + error);
    //                 }
    //             });

    //             jQuery(this).dialog('close');
    //         },
    //         Cancel: function() {
    //             jQuery(this).dialog('close');
    //         }
    //     }
    // });
});