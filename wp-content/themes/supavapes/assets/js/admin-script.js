jQuery( document ).ready( function( $ ) {
	'use strict';

	var note_attachments = [];

	// Approve support request.
	$( document ).on( 'click', '.approve-support-request', function( e ) {
		e.preventDefault();

		var post_id = $( this ).data( 'id' );
		$( '#support-request-loader' ).show();
		$( '#decline-dialog' ).css( 'display', 'none' );

		// Confirm approval.
		var confirm_approval = confirm( 'Are you sure you want to approve this support request?' );

		if ( confirm_approval ) {
			$.ajax( {
				url: SupaVapesCustomAdminScript.ajax_url,
				type: 'POST',
				data: {
					action: 'approve_support_request',
					post_id: post_id,
					nonce: SupaVapesCustomAdminScript.nonce
				},
				success: function( response ) {
					if ( response.success ) {
						console.log( 'Support request approved and order created.' );
						$( '#support-request-loader' ).hide();
						location.reload();
					} else {
						console.warn( 'Failed to approve support request: ' + response.data );
					}
				},
				error: function( xhr, status, error ) {
					alert( 'An error occurred: ' + error );
				}
			}) ;
		}
	} );

	// Decline support request.
	$( document ).on( 'click', '.decline-support-request', function( e ) {
		e.preventDefault();
		$( '#decline-dialog' ).css( 'display', 'block' );
	} );

	// Submit declination status with reason.
	$( document ).on( 'click', '#submit-decline-reason', function( e ) {
		e.preventDefault();

		var post_id = $( this ).data( 'id' );
		var reason  = $('#decline-reason').val();

		// Shoot the ajax for declining support request.
		$.ajax( {
			url: SupaVapesCustomAdminScript.ajax_url,
			type: 'POST',
			data: {
				action: 'decline_support_request',
				post_id: post_id,
				reason: reason,
				nonce: SupaVapesCustomAdminScript.nonce
			},
			beforeSend: function() {
				$( '#support-request-loader' ).show();
			},
			success: function(response) {
				if (response.success) {
					console.log( 'Support request declined and customer notified.' );
					$( '#support-request-loader' ).hide();
					location.reload();
				} else {
					console.warn( 'Failed to decline support request: ' + response.data );
				}
			},
			error: function(xhr, status, error) {
				console.warn('An error occurred: ' + error);
			}
		} );
	} );

	$( document ).on( 'click', '.add-order-notes-attachments a', function() {
		var attachments_html = '';
		var images = wp.media( {
			title: SupaVapesCustomAdminScript.media_uploader_modal_header,
			multiple: true,
		} ).open()
		.on( 'select', function( e ) {
			var images_arr       = images.state().get( 'selection' ).toJSON();

			// Iterate through the selected files.
			for ( var i in images_arr ) {
				var image_id  = images_arr[i].id;
				var image_url = images_arr[i].url;
				var filename  = images_arr[i].filename;

				// If the filename is invalid, skip.
				if ( -1 === is_valid_string( filename ) ) {
					continue;
				}

				// Check if the filename has a dot in it.
				var dot_index = filename.indexOf( '.' );

				// If there is no dot, skip.
				if ( -1 === dot_index ) {
					continue;
				}

				// Get the extension from the image URL and see if that is within the allowed types.
				var file_ext = filename.split( '.' ).pop();
				file_ext     = '.' + file_ext;

				// Skip the iteration, if the file extension doesn't match the allowed ones.
				if ( -1 === $.inArray( file_ext, SupaVapesCustomAdminScript.review_attachments_allowed_types ) ) {
					continue;
				}

				// Collect the image data into an array.
				note_attachments.push( image_id );

				// Prepare the attachments HTML.
				attachments_html += '<div class="notes-image-item" data-imageid="' + image_id + '">';
				attachments_html += '<img alt="legacy-system-notice-2.png" src="' + image_url + '" class="ersrvr_attached_files">';
				attachments_html += '<a href="javascript:void(0)" class="delete-link">';
				attachments_html += '<span class="icon"><span class="dashicons dashicons-dismiss"></span></span>';
				attachments_html += '<span class="text sr-only">Delete</span>';
				attachments_html += '</a>';
				attachments_html += '</div>';
			}

			// Paste the HTML now.
			$( '.order-notes-attachments-container .gallery-images' ).html( attachments_html );
		} );
	} );

	// Add order note and upload attachments to the last note.
	$( '#woocommerce-order-notes' ).on( 'click', 'button.add_note', function() {
		/**
		 * Timeout is set so that the latest note is added in the database and then the attachments are added to the note.
		 * If we don't add the timeout, then the attachments are added to the 2nd last order note.
		 */
		// setTimeout( function() {
			var order_id   = $( '#post_ID' ).val();
			var order_note = $( '#add_order_note' ).val();
			console.log( order_note );
			// Return, if the order id is empty or invalid.
			if ( -1 === is_valid_number( order_id ) ) {
				console.warn( 'Order ID not found or is invalid. Aborting...' );
				return false;
			}

			// Return, if there is no order note.
			if ( -1 === is_valid_string( order_note ) ) {
				console.warn( 'Order note not found. Aborting...' );
				return false;
			}

			// Send the AJAX now to update the review attachments.
			$.ajax( {
				dataType: 'JSON',
				url: SupaVapesCustomAdminScript.ajax_url,
				type: 'POST',
				data: {
					action: 'add_note_attachments',
					order_id: order_id,
					note_attachments: note_attachments,
				},
				success: function ( response ) {
					if( 'media-attached' === response.data.code ) {
						$( '.order-notes-attachments-container .gallery-images' ).html('');
						note_attachments = [];
						console.log( 'note attachments updated successfully' );
						$( '#add_order_note' ).val(' ');
						refresh_order_notes( order_id );
					}
				},
			} );
		// }, 1000 );
	} );

	/**
	 * Refresh order notes.
	 *
	 * @param {*} order_id 
	 */
	function refresh_order_notes( order_id ) {
		$.ajax( {
			dataType: 'JSON',
			url: SupaVapesCustomAdminScript.ajax_url,
			type: 'POST',
			data: {
				action: 'refresh_order_notes',
				order_id: order_id,
			},
			success: function ( response ) {
				if( 'notes-refreshed' === response.data.code ) {
					$( '#woocommerce-order-notes .order_notes' ).html( response.data.html );
				}
			},
		} );
	}

	/**
	 * Check if a number is valid.
	 * 
	 * @param {number} data 
	 */
	function is_valid_number( data ) {
		if ( '' === data || undefined === data || isNaN( data ) || 0 === data ) {
			return -1;
		} else {
			return 1;
		}
	}

	/**
	 * Check if a string is valid.
	 *
	 * @param {string} $data
	 */
	function is_valid_string( data ) {
		if ( '' === data || undefined === data || ! isNaN( data ) || 0 === data ) {
			return -1;
		} else {
			return 1;
		}
	}
} );
