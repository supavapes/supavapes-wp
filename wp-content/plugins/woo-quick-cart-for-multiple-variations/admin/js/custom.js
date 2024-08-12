jQuery( document ).ready( function ( $ ) {

    // Handle click event for Notify link
    jQuery('.wqcmvp_notify').on('click', function(e) {
        e.preventDefault();

        // Open popup
        openPopup();
    });

// Function to open the popup
function openPopup() {
    // Create the popup content
    var popupContent = '<div>' +
        '<label for="input1">Input 1:</label>' +
        '<input type="text" id="input1" name="input1" /><br>' +
        '<label for="input2">Input 2:</label>' +
        '<input type="text" id="input2" name="input2" /><br>' +
        '<button id="popupButton">Submit</button>' +
        '</div>';

    // Append the popup content to the body
    jQuery('<div></div>').appendTo('body')
        .html(popupContent)
        .dialog({
            modal: true,
            title: 'Notify',
            width: 'auto',
            resizable: false,
            buttons: {
                Close: function() {
                    jQuery(this).dialog('close');
                }
            }
        });

    // Handle click event for the popup button
    jQuery('#popupButton').on('click', function() {
        // Get the values of the input fields
        var input1Value = jQuery('#input1').val();
        var input2Value = jQuery('#input2').val();

        // Do something with the input values, such as sending them via AJAX
        // For example:
        // $.post('your-api-url', { input1: input1Value, input2: input2Value }, function(response) {
        //     console.log(response);
        // });
    });
}



    var ajaxurl = CustomJSObj.ajaxurl;
    $( document ).on( 'click', '.wqcmvp_export', function( e ) {
        e.preventDefault();
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: { 'action' : 'export_pre_oeder_data' },
            success: function( response ) {
                if ( 'csv-export-failed' === response ) {
                    $( '.export_message' ).text( 'There is no pre order available.' );
                    setTimeout( function(){
                        $( '.export_message' ).text( '' );
                    }, 3000);
                } else {
                    var a = document.createElement('a');
                    var url = 'data:application/csv;charset=utf-8,' + encodeURIComponent( response );
                    a.href = url;
                    a.download = 'pre_order.csv';
                    document.body.append(a);
                    a.click();
                    a.remove();
                    window.URL.revokeObjectURL(url);
                }
            }
        });
    } );


    // $( document ).on( 'click', '.wqcmvp_notify', function( e ) {
    //     e.preventDefault(); 
    //     $.ajax({
    //         url: ajaxurl,
    //         type: 'POST',
    //         data: { 'action' : 'notify_preorder_users' },
    //         success: function( response ) {
    //             console.log(response.data.html);
    //             // Display the received content in a Bootstrap Modal
    //             $('#popupModal .modal-body').html(response.data.html);
    //             $('#popupModal').modal('show');
    //         }
    //     });
    // } );
} );