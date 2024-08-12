/*!
 * WooCommerce Moneris
 * Version 3.0.0
 *
 * Copyright (c) 2012-2024, SkyVerge, Inc. (info@skyverge.com)
 * Licensed under the GNU General Public License v3.0
 * http://www.gnu.org/licenses/gpl-3.0.html
 */
jQuery( ( $ ) => {


	'use strict';


	/**
	 * Payment form handler.
	 *
	 * Interacts with the Moneris Checkout API to process a checkout payment form.
	 *
	 * @link https://developer.moneris.com/sitecore/media%20library/Hidden/MCO/Client-Side%20Checkout%20Page
	 *
	 * @since 3.0.0
	 */
	window.WC_Moneris_Payment_Form_Handler = class WC_Moneris_Payment_Form_Handler extends SV_WC_Payment_Form_Handler_v5_12_1 {


		/**
		 * Instantiates the payment form handler.
		 *
		 * @since 3.0.0
		 *
		 * @param {Object} args form handler arguments
		 */
		constructor( args ) {

			super( args );

			this.ticketNumber = this.hasTicketNumber() ? this.getTicketField().val() : '';
			this.environment = args.environment;
			this.isCheckoutLoggingEnabled = args.isCheckoutLoggingEnabled;
			this.isPayPageEnabled = args.isPayPageEnabled;
			this.clearErrors = $( 'form.checkout' ).length;
			let parent = this;

			$( '#wc-moneris-csc_field' ).hide();

			// initialization on form load, for Pay Page / Add Payment Method
			if ( ! this.formInitialized && ! $( 'form.checkout' ).length ) {

				if ( parent.ticketNumber ) {
					parent.initForm();
				} else {
					this.showErrorMessage( '-pay-page' );
				}

				this.placeOrderButtonHandling();
				this.handleCSCForSavedCards();
			}

			$( document ).on( 'updated_checkout', () => {

				parent.initializingForm = false;
				parent.ticketNumber = this.hasTicketNumber() ? this.getTicketField().val() : '';

				this.placeOrderButtonHandling();
				this.handleCSCForSavedCards();

				if ( parent.ticketNumber ) {
					parent.initForm();
				} else {
					this.showErrorMessage();
				}

				if ( this.isCheckoutLoggingEnabled && this.woocommerceError ){
					$( '.woocommerce-NoticeGroup' ).prepend( this.woocommerceError );
				}

			} );

			if ( $( 'form.checkout' ).length ) {

				$( document ).on( 'change', '#customer_details input, #customer_details select, #terms', function(){
					// avoid infinite refreshes of the checkout area in some cases due to the ticket number changing
					// causing another ticket to be created, account creation listener causing conflicting updates, etc.
					if ( $( this ).attr( 'name' ) !== 'wc-moneris-ticket' && $( this ).attr( 'name' ) !== 'createaccount' && ! parent.isPayPageEnabled ) {
						$( document.body ).trigger( 'update_checkout' );
					}
				} );
			}

			// re-render the form if there is any error
			$( document ).on( 'checkout_error', () => {

				// preserve error logs if the debugging is enabled, as they get removed due to the new debug logs coming in
				if ( this.isCheckoutLoggingEnabled ) {
					this.woocommerceError = $( '.woocommerce-error' ).length > 0 ? $( '.woocommerce-error' )[0].outerHTML : '';
				}

				$( 'body' ).trigger( 'update_checkout' );
			} );

			// handle successful recaptcha callback on checkout page
			window.recaptchaSuccessCallback = () => {

				if ( ! grecaptcha.getResponse() ) {
					return;
				}

				// unblock moneris form if the recaptcha was solved successfully
				this.unblockMonerisForm();

				// set interval of 1 second after successfully solving recaptcha to handle refresh and expiry
				window.recaptchaInterval = setInterval(() => {

					// clear interval after the form is blocked
					if ( $ ( '#wc-moneris-credit-card-checkout-form .blockUI.blockOverlay' ).length ) {

						clearInterval( window.recaptchaInterval );
						return;
					}

					parent.greCaptchaHandling();

				}, 1000 );
			}
		}


		/**
		 * Determines whether the ticket number exists.
		 *
		 * Checks the hidden input for a value.
		 *
		 * @since 3.0.0
		 *
		 * @returns {boolean} whether the ticket number exists
		 */
		hasTicketNumber() {
			return this.getTicketField().val() && this.getTicketField().val().length > 0;
		}


		/**
		 * Show/hide the CSC field when a saved payment method is de-selected/selected
		 *
		 * @since 3.0.2
		 */
		handleCSCForSavedCards() {

			this.toggleCSCField();

			$( 'input.js-wc-moneris-payment-token' ).change( this.toggleCSCField );
		}


		/**
		 * Toggle the CSC field based on tokenization radio buttons
		 *
		 * @since 3.0.2
		 */
		toggleCSCField() {

			const tokenizedPaymentMethodSelected = $( 'input.js-wc-moneris-payment-token:checked' ).val();
			const $newPaymentMethodSection = $( 'div.js-wc-moneris-new-payment-method-form' );
			const $cscField = $( '#wc-moneris-csc_field' );

			if( ! $cscField.length ) {
				return;
			}

			if ( tokenizedPaymentMethodSelected ) {
				// using an existing tokenized payment method, hide the 'new method' fields
				$newPaymentMethodSection.slideUp( 200 );
				$cscField.removeClass('form-row-last').slideDown( 200 );
			} else {
				// use new payment method, display the 'new method' fields
				$newPaymentMethodSection.slideDown( 200 );
				$cscField.slideUp( 200 );
			}
		}


		/**
		 * Gets the ticket number field.
		 *
		 * Returns a jQuery object with the hidden input that holds the ticket number value.
		 *
		 * @since 3.0.0
		 *
		 * @returns {Object} jQuery object
		 */
		getTicketField() {
			return $( '#wc-' + this.id_dasherized + '-ticket' );
		}


		/**
		 * Show error message if the form is not loaded yet.
		 *
		 * @since 3.0.0
		 *
		 * @returns void
		 */
		showErrorMessage( suffix = '' ) {
			$( '#wc-' + this.id_dasherized + '-error-message' + suffix ).show();
		}


		/**
		 * Validates card data.
		 *
		 * Implements and overrides parent method (bypasses validation, handled by Moneris Checkout API).
		 *
		 * @since 3.0.0
		 *
		 * @returns {boolean} check CVV if applicable, otherwise true when checking out with hosted Moneris checkout
		 */
		validate_card_data() {

			var $form = $( 'form.checkout' );

			if ( $form.is( '.processing' ) ) {
				return false;
			}

			const tokenizedPaymentMethodSelected = $( 'input.js-wc-moneris-payment-token:checked' ).val();

			if ( ! tokenizedPaymentMethodSelected ) {
				// paying with hosted Moneris checkout form
				return true;
			}

			var $paymentFields = $( '.payment_method_moneris' ),
				errors = [],
				csc = $paymentFields.find( '#wc-moneris-csc' ).val();  // optional element

			// validate CSC if present
			if ( 'undefined' !== typeof csc ) {

				if ( ! csc ) {

					errors.push( sv_wc_payment_gateway_payment_form_params.cvv_missing );

				} else {

					if ( /\D/.test( csc ) ) {
						errors.push( sv_wc_payment_gateway_payment_form_params.cvv_digits_invalid );
					}

					if ( csc.length < 3 || csc.length > 4 ) {
						errors.push( sv_wc_payment_gateway_payment_form_params.cvv_length_invalid );
					}
				}
			}

			if ( errors.length > 0 ) {

				this.render_errors( errors );

				return false;

			} else {

				return true;
			}
		}


		/**
		 * Override from parent and do nothing.
		 *
		 * @since 3.0.0
		 */
		do_inline_credit_card_validation() {
			// no-op
		}


		/**
		 * Handles the error event data.
		 *
		 * Logs errors to console and maybe renders them in a user-facing notice.
		 *
		 * @since 3.0.0
		 *
		 * @param {Object} event after a form error
		 */
		handleError( event ) {

			this.debugLog( 'error occurred' );
		}


		/**
		 * Initializes the form.
		 *
		 * Adds listeners for the ready and error events.
		 *
		 * @link https://developer.moneris.com/sitecore/media%20library/Hidden/MCO/Handling%20Callbacks
		 *
		 * @since 3.0.0
		 */
		initForm() {

			// run only once
			if ( this.initializingForm ) {
				return;
			}

			this.initializingForm = true;

			this.monerisForm = new monerisCheckout();
			this.monerisForm.setCheckoutDiv('wc-' + this.id_dasherized + '-credit-card-checkout-form');
			this.monerisForm.setMode(this.environment);

			// make sure we hide the core Place Order button when the Moneris checkout form loads
			this.togglePlaceOrderButton();

			// hide and then in the page_loaded callback, show the moneris checkout form after it has
			// rendered within the hidden container, to avoid jumping the page to the form each time it loads
			$( '#wc-moneris-container' ).hide();

			this.monerisForm.startCheckout(this.ticketNumber);

			// triggers when the moneris form is loaded
			this.monerisForm.setCallback( 'page_loaded', event => {

				this.initializingForm = false;
				this.formInitialized  = true;

				$('#wc-moneris-container').show();

				this.handlePaymentFormReady( event );

				// terms checkbox handling on pay page after the form is loaded, and the form's actual height is adjusted properly
				this.termsCheckboxHandling();

				this.clearErrors = true;
			} );

			// triggers when the moneris form has some error
			this.monerisForm.setCallback( 'error_event', event => {
				this.handleError(event);
			} );

			// triggers when the payment is completed
			this.monerisForm.setCallback( 'payment_complete', event => {

				this.clearErrors = false;

				const response = JSON.parse( event ) ;

				if ( response.response_code === '001' ) {

					this.getTicketField().val( response.ticket );
					this.form.submit();
				}
			} );
		}


		/**
		 * Handles a payment form ready event.
		 *
		 * Unblocks the payment form after initialization.
		 *
		 * @since 3.0.0
		 *
		 * @param {Object} event after the form is ready
		 */
		handlePaymentFormReady( event ) {

			if ( ! event.type || 'ready' !== event.type ) {
				this.debugLog( event );
			} else {
				this.debugLog( 'Payment form ready' );
			}

			this.form.unblock();
		}


		/**
		 * Block/unblock form UI if the terms and conditions checkbox is not checked on the WC Pay Page.
		 *
		 * @since 3.0.2
		 *
		 * @returns void
		 */
		termsCheckboxHandling() {

			this.greCaptchaHandling();

			// bail out if it is not the WooCommerce Pay Page or terms checkbox is disabled
			if ( ! $( 'body.woocommerce-order-pay' ).length || ! $( '#terms' ).length ) {
				return;
			}

			const blockMessage = 'Please read and accept the terms and conditions below to proceed with your order.';

			if ( ! $( '#terms:checked' ).val() ) {
				this.blockMonerisForm( blockMessage );
			}

			// toggle block/unblock overlay
			$( document ).on( 'change', '#terms', () => {

				if( 'on' === $( '#terms:checked' ).val() ) {
					this.unblockMonerisForm();
				} else {
					this.blockMonerisForm( blockMessage );
				}
			} );
		}


		/**
		 * Block/unblock form UI if the reCaptcha is not passed successfully on the WC Pay Page.
		 *
		 * @since 3.0.0
		 *
		 * @returns void
		 */
		greCaptchaHandling() {

			// bail out if the global myCaptcha variable is not available or the form is not loaded
			if ( ! ( $( '#g-recaptcha-checkout-i13' ).length || $( '#g-recaptcha-payment-method' ).length ) || ! $( '#wc-moneris-credit-card-checkout-form-Frame' ).length ) {
				return;
			}

			const blockMessage = 'Please verify captcha below to proceed with your order.';

			try {
				grecaptcha && ! grecaptcha.getResponse() && this.blockMonerisForm( blockMessage );
			} catch( err ) {
				this.blockMonerisForm( blockMessage );
			}
		}


		/**
		 * Blocks the Moneris form from payment processing.
		 *
		 * @since 3.0.2
		 *
		 * @param {String} message text to show in the blocked overlay
		 *
		 * @returns void
		 */
		blockMonerisForm( message = '' ) {

			$( '#wc-moneris-credit-card-checkout-form' ).block( {
				css: {
					width: '80%'
				},
				message,
			} );
		}


		/**
		 * Unblocks the Moneris form from payment processing.
		 *
		 * @since 3.0.2
		 *
		 * @returns void
		 */
		unblockMonerisForm() {

			$( '#wc-moneris-credit-card-checkout-form' ).unblock();

			this.termsCheckboxHandling();
		}


		/**
		 * Logs an item to console if logging is enabled.
		 *
		 * @since 3.0.0
		 *
		 * @param {String|Object} logData
		 */
		debugLog( logData ) {
			if ( this.isLoggingEnabled ) {
				console.log( logData );
			}
		}


		/**
		 * Determines whether the user has saved payment methods.
		 *
		 * @since 3.0.2
		 *
		 * @returns {boolean}
		 */
		userHasSavedPaymentMethods() {
			return $( '[name="wc-moneris-payment-token"]' ).length > 0;
		}


		/**
		 * Disable / enable the WooCommerce place order button based on the state of the Moneris payment gateway:
		 *
		 * - If the hosted Moneris Checkout form is visible (which has its own internal checkout button), disable the core Place Order button
		 * - If a Moneris saved payment method is selected, enable the core Place Order button
		 * - If another gateway is selected, return the Place Order button to being enabled
		 *
		 * @since 3.0.0
		 */
		placeOrderButtonHandling() {

			$( '[name="wc-moneris-payment-token"]' ).on( 'change', () => {

				if ( $( '[name="wc-moneris-payment-token"]:checked' ).val() === '' && this.hasTicketNumber() ) {

					if ( this.clearErrors ) {

						// avoid clearing errors from the gateway, e.g. payment decline
						$('.woocommerce-error').remove();
					}

					$( '#place_order').attr( 'disabled' , true );
					$( '#wc-moneris-tokenize-payment-method' ).parent().show();

				} else {

					$( '#place_order').attr( 'disabled' , false );
					$( '#wc-moneris-tokenize-payment-method' ).parent().hide();
				}

				this.termsCheckboxHandling();

			} ).change();

			$( 'input[name^="payment_method"]' ).on( 'change', () => {

				this.togglePlaceOrderButton();
			} );
		}


		/**
		 * Toggles Place Order button based on saved cards.
		 *
		 * @since 3.0.2
		 */
		togglePlaceOrderButton() {

			if ( $( 'input[name=payment_method]:checked' ).val() === 'moneris' ) {

				if ( this.userHasSavedPaymentMethods() ) {

					// fire the payment token selector change handler which will disable/enable the button as needed
					$('[name="wc-moneris-payment-token"]').change();

				} else if ( this.hasTicketNumber() ) {

					// Moneris Checkout form is shown: hide the core place order button and hide any checkout errors
					if ( this.clearErrors ) {
						// avoid clearing errors from the gateway, e.g. payment decline
						$( '.woocommerce-error' ).remove();
					}

					$( '#place_order' ).attr( 'disabled' , true );
					$( '#wc-moneris-tokenize-payment-method' ).parent().show();

				} else {

					// Moneris checkout form is hidden: leave the core place order button available to customers have a way of getting feedback
					$('#place_order').attr( 'disabled' , false );
					$('#wc-moneris-tokenize-payment-method').parent().hide();
				}

			} else {

				$( '#place_order' ).attr( 'disabled' , false );
			}

			this.termsCheckboxHandling();
		}
	}


	// dispatch loaded event
	$( document.body ).trigger( 'wc_moneris_payment_form_handler_loaded' );


} );

// handle reCaptcha callback on checkout for logged-in users
const woo_login_checkout_recaptcha_verified = () => {
	window.recaptchaSuccessCallback();
}

// handle reCaptcha callback on add payment method page
const add_payment_method_recaptcha_verified = () => {
	window.recaptchaSuccessCallback();
}

// handle reCaptcha callback on checkout for guest users
const woo_guest_checkout_recaptcha_verified = () => {
	window.recaptchaSuccessCallback();
}
