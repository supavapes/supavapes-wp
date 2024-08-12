/*!
 * WooCommerce Moneris
 * Version 2.0
 *
 * Copyright (c) 2012-2024, SkyVerge, Inc. (info@skyverge.com)
 * Licensed under the GNU General Public License v3.0
 * http://www.gnu.org/licenses/gpl-3.0.html
 */
jQuery( document ).ready( function ( $ ) {

	'use strict';

	/* global wc_moneris_hosted_tokenization_params */

	var startTime = null;

	// checkout page
	if ( $( 'form.checkout' ).length ) {

		registerHostedTokenizationCallbackHandler();

		// handle payment methods, note this is bound to the updated_checkout trigger so it fires even when other parts
		// of the checkout are changed
		$( document.body ).on( 'updated_checkout', function() { handleSavedPaymentMethods(); } );

		// validate payment data before order is submitted
		$( 'form.checkout' ).bind( 'checkout_place_order_moneris', function() {

			// if not using a permanent token and we don't yet have a temporary token, post
			// the hosted credit card account number form field to retrieve a temporary token
			if ( shouldUseNewCardNumber() ) {

				hostedTokenizationRequest( $( this ) );

				// halt the form submission so we have a chance to request the temporary card token
				return false;
			}

			// otherwise validate our non-hosted form fields (expiration date/csc)
			return validateCardData( $( this ) ); }
		);

	// add payment method page
	} else if ( $( 'form#add_payment_method' ).length ) {

		registerHostedTokenizationCallbackHandler();

		$( 'form#add_payment_method' ).on( 'submit', function() {

			if ( $( '#wc-moneris-temp-payment-token' ).val() ) {
				return validateCardData( $( this ) );
			}

			hostedTokenizationRequest( $( this ) );

			return false;
		} );


	// checkout->pay page
	} else {

		// handle payment methods on checkout->pay page
		handleSavedPaymentMethods();

		registerHostedTokenizationCallbackHandler();

		// validate card data before order is submitted when the payment gateway is selected
		$( 'form#order_review' ).submit( function() {

			if ( 'moneris' === $( '#order_review input[name=payment_method]:checked' ).val() ) {

				// if not using a permanent token and we don't yet have a temporary token, post
				// the hosted credit card account number form field to retreive a temporary token
				if ( shouldUseNewCardNumber() ) {

					hostedTokenizationRequest( $( this ) );

					// halt the form submission so we have a chance to request the temporary card token
					return false;
				}

				// otherwise validate our non-hosted form fields (expiration date/csc)
				return validateCardData( $( this ) );
			}

		} );
	}


	/**
	 * Formats the expiration date field with jQuery.Payment
	 *
	 * @since 2.10.0
	 */
	function formatExpDate() {
		if ( $( '#wc-moneris-expiry' ).length ) {
			$( '#wc-moneris-expiry' ).payment( 'formatCardExpiry' );
		}
	}


	// auto-format the expiration date field on checkout update
	$( document.body ).on( 'updated_checkout', formatExpDate );

	formatExpDate();


	/**
	 * Post the hosted credit card account form field to retrieve a temporary
	 * token
	 */
	function hostedTokenizationRequest( $form ) {

		// block the checkout form while we work
		$form.addClass( 'processing' );

		var form_data = $form.data();

		if ( form_data['blockUI.isBlocked'] !== 1 ) {
			$form.block( { message: null, overlayCSS: { background: '#fff', opacity: 0.6 } } );
		}

		// post the Moneris Hosted Tokenization credit card account number field iframe (see hostedTokenizationResponse() for the callback handler)
		startTime = Math.round( new Date().getTime() / 1000 );
		$( '#wc-moneris-account-number' )[0].contentWindow.postMessage( 'tokenize', wc_moneris_hosted_tokenization_params.hosted_tokenization_url );
	}


	/**
	 * Callback for the hosted tokenization credit card account number field
	 * iframe
	 */
	function hostedTokenizationResponse( e ) {

		if ( -1 === e.origin.indexOf( 'moneris.com' ) ) {
			return;
		}

		// bail if e.data is not an string -- the tokenization response should include a JSON encoded object in e.data
		// browser extensions on Safari can trigger events that appear to be tokenization responses but are not: https://app.clubhouse.io/skyverge/story/55570
		if ( 'string' !== typeof e.data ) {
			return;
		}

		var data        = $.parseJSON( e.data );
		var requestTime = Math.round( new Date().getTime() / 1000 ) - startTime;
		var $form;

		// if expiry text box or CVD text box are enabled, the returned responseCode value will be in the form of a list (e.g. [“944”,”943”]), since there may be more than one failure
		// if only the card number text box is displayed, the responseCode will be returned in the form of a string
		// https://developer.moneris.com/en/Documentation/NA/E-Commerce%20Solutions/Hosted%20Solutions/Hosted%20Tokenization
		if ( ! Array.isArray( data.responseCode ) ) {
			data.responseCode = [ data.responseCode ];
		}

		// make sure each response code is an int
		data.responseCode = $.map( data.responseCode, function( element ) {
			return parseInt( element, 10 );
		} );

		// notify the backend since the hosted tokenization request took place in the client browser
		$.get(
			wc_moneris_hosted_tokenization_params.ajaxurl,
			{
				action:       'wc_payment_gateway_moneris_handle_hosted_tokenization_response',
				orderId:      wc_moneris_hosted_tokenization_params.order_id,
				responseCode: data.responseCode.join( ',' ),
				errorMessage: data.errorMessage,
				token:        data.dataKey,
				requestTime:  requestTime
			}
		);

		if ( $( 'form.checkout' ).length ) {
			$form = $( 'form.checkout' );
		} else if ( $( 'form#add_payment_method' ).length ) {
			$form = $( 'form#add_payment_method' );
		} else {
			$form = $( 'form#order_review' );
		}

		// cancel processing
		$form.removeClass( 'processing' ).unblock();

		// a response code value less than 50 is considered a success response
		if ( Math.max.apply( null, data.responseCode ) < 50 ) {

			// success: set the temporary token and submit the checkout form
			$( '#wc-moneris-use-new-payment-method' ).val( data.dataKey );
			$( '#wc-moneris-temp-payment-token' ).val( 1 );
			$( '#wc-moneris-card-bin' ).val( data.bin );

			$form.submit();

			// on the pay page we apparently have to re-activate the form block ourselves
			if ( ! $( 'form.checkout' ).length ) {
				$form.addClass( 'processing' );

				var form_data = $form.data();

				if ( form_data['blockUI.isBlocked'] !== 1 ) {
					$form.block( { message: null, overlayCSS: { background: '#fff', opacity: 0.6 } } );
				}
			}
		} else {
			// error creating token, blank out any we may have previously returned
			$( '#wc-moneris-use-new-payment-method' ).val( '' );
			$( '#wc-moneris-temp-payment-token' ).val( '' );

			// display an error message
			var errors = [];

			if ( -1 !== data.responseCode.indexOf( 943 ) ) {
				errors.push( wc_moneris_hosted_tokenization_params.card_number_missing_or_invalid );
			} else {
				// general error message
				errors.push( wc_moneris_hosted_tokenization_params.general_error );
			}

			renderErrors( $form, errors );
		}
	}


	/**
	 * Registers the hosted tokenization credit card account number field iframe
	 * callback
	 */
	function registerHostedTokenizationCallbackHandler() {
		// Listen for the response from Moneris' Hosted Tokenization credit card account number iframe
		if ( window.addEventListener ) {
			window.addEventListener( 'message', hostedTokenizationResponse, false );
		} else {
			if ( window.attachEvent ) {
				window.attachEvent( 'onmessage', hostedTokenizationResponse );
			}
		}
	}


	// Perform validation on the card info entered, excluding the account number
	// which is in the iframe and not available to us
	function validateCardData( $form ) {

		if ( $form.is( '.processing' ) ) {
			return false;
		}

		var $paymentFields = $( '.payment_method_moneris' );

		var tokenizedPaymentMethodSelected = $paymentFields.find( '.js-sv-wc-payment-gateway-payment-token:checked' ).val();

		var errors = [];

		// don't validate expiry fields if a saved payment method is being used
		if ( ! tokenizedPaymentMethodSelected ) {
			var expiry   = $.payment.cardExpiryVal( $( '#wc-moneris-expiry' ).val() );
			var expMonth = getExpMonth( expiry );
			var expYear  = getExpYear( expiry );

			// validate expiration date
			var currentYear = new Date().getFullYear();

			if ( /\D/.test( expMonth ) || /\D/.test( expYear ) ||
					expMonth > 12 ||
					expMonth < 1 ||
					expYear < currentYear ||
					expYear > currentYear + 20 ) {
				errors.push( wc_moneris_hosted_tokenization_params.card_exp_date_invalid );
			}
		}

		var csc = $paymentFields.find( '#wc-moneris-csc' ).val();  // optional element

		// validate CSC if present
		if ( 'undefined' !== typeof csc ) {

			if ( ! csc ) {
				errors.push( wc_moneris_hosted_tokenization_params.cvv_missing );
			} else {

				if (/\D/.test( csc ) ) {
					errors.push( wc_moneris_hosted_tokenization_params.cvv_digits_invalid );
				}

				if ( csc.length < 3 || csc.length > 4 ) {
					errors.push( wc_moneris_hosted_tokenization_params.cvv_length_invalid );
				}

			}

		}

		if ( errors.length > 0 ) {

			renderErrors( $form, errors );

			return false;

		} else {
			return true;
		}
	}


	/**
	 * Gets the status of the Use New Payment Method radio button (if it exists).
	 *
	 * @since 2.10.0
	 *
	 * @returns {boolean}
	 */
	function shouldUseNewCardNumber() {

		var $useNewPaymentMethod = $( '#wc-moneris-use-new-payment-method' );

		if ( ! $useNewPaymentMethod || 0 === $useNewPaymentMethod.length ) {
			return true;
		}

		if ( $useNewPaymentMethod.is( ':checked' ) && ! $useNewPaymentMethod.val() ) {
			return true;
		}

		return $useNewPaymentMethod.is( 'input[type="hidden"]' ) && ! $useNewPaymentMethod.val();
	}


	// render any new errors and bring them into the viewport
	function renderErrors( $form, errors ) {

		// hide and remove any previous errors
		$( '.woocommerce-error, .woocommerce-message' ).remove();

		// add errors
		$form.prepend( '<ul class="woocommerce-error"><li>' + errors.join( '</li><li>' ) + '</li></ul>' );

		// unblock UI
		$form.removeClass( 'processing' ).unblock();

		$form.find( '.input-text, select' ).blur();

		// scroll to top
		$( 'html, body' ).animate( {
			scrollTop: ( $form.offset().top - 100 )
		}, 1000 );
	}


	// show/hide the saved payment methods when a saved payment method is de-selected/selected
	function handleSavedPaymentMethods() {

		$( 'input.js-wc-moneris-payment-token' ).change( function() {

			var tokenizedPaymentMethodSelected = $( 'input.js-wc-moneris-payment-token:checked' ).val(),
				$newPaymentMethodSection = $( 'div.js-wc-moneris-new-payment-method-form' ),
				$cscField = $( '#wc-moneris-csc' ).closest( '.form-row' );

			if ( tokenizedPaymentMethodSelected ) {
				// using an existing tokenized payment method, hide the 'new method' fields
				$newPaymentMethodSection.slideUp( 200 );

				if ( wc_moneris_hosted_tokenization_params.require_csc ) {
					// move the CSC field out of the 'new method' fields so it can be used with the tokenized transaction
					$cscField.removeClass( 'form-row-last' ).addClass( 'form-row-first' );
					$newPaymentMethodSection.after( $cscField );
				}

			} else {
				// use new payment method, display the 'new method' fields
				$newPaymentMethodSection.slideDown( 200 );

				if ( wc_moneris_hosted_tokenization_params.require_csc ) {
					// move the CSC field back into its regular spot
					$cscField.removeClass( 'form-row-first' ).addClass( 'form-row-last' );
					$newPaymentMethodSection.find( '.js-sv-wc-payment-gateway-credit-card-form-expiry' ).closest( '.form-row' ).after( $cscField );
				}
			}

		} ).change();

		// display the 'save payment method' option for guest checkouts if the 'create account' option is checked
		//  but only hide the input if there is a 'create account' checkbox (some themes just display the password)
		$( 'input#createaccount' ).change( function() {

			var $parentRow;

			$parentRow = $( 'input.js-wc-moneris-tokenize-payment-method' ).closest( 'p.form-row' );

			if ( $( this ).is( ':checked' ) ) {
				$parentRow.slideDown();
				$parentRow.next().show();
			} else {
				$parentRow.hide();
				$parentRow.next().hide();
			}

		} ).change();
	}


	/**
	 * Gets the expiration month from a jQuery.payments expiry object.
	 *
	 * @since 2.10.0
	 *
	 * @param object expiry
	 * @return string
	 */
	function getExpMonth( expiry ) {
		return expiry.month < 10 ? '0' + expiry.month : '' + expiry.month;
	}


	/**
	 * Gets the expiration year from a jQuery.payments expiry object.
	 *
	 * @since 2.10.0
	 *
	 * @param object expiry
	 * @return string
	 */
	function getExpYear( expiry ) {
		return expiry.year;
	}


} );
