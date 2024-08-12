/*!
 * WooCommerce Moneris
 *
 * Copyright (c) 2012-2019, SkyVerge, Inc.
 * Licensed under the GNU General Public License v3.0
 * http://www.gnu.org/licenses/gpl-3.0.html
 */
jQuery( document ).ready( function( $ ) {

	'use strict';

	// save the value of the data-tip attribute so that we can enable tooltips for connection settings inserted into the page
	// the data-tip attribute is removed by tipTip() when WooCommerce initializes tooltips
	$( '.currency-field' ).closest( 'tr' ).find( '.woocommerce-help-tip' ).each( function() {
		$( this ).attr( 'data-original-tip', $( this ).attr( 'data-tip' ) );
	} );

	// hide prototype fields, titles and title descriptions
	$( '.currency-field.hidden' ).closest( 'tr' ).hide();
	$( '.wc-settings-sub-title.currency-field.xxx-field + p' ).hide();

	// add delete connection settings link to existing sections title (including prototype fields)
	$( '.js-connection-settings-title' ).each( function() {
		$( this ).append( `<a href="#" title="${wc_moneris_admin.remove_connection_settings_label}" class="js-remove-connection-settings" style="text-decoration: none"><span class="dashicons dashicons-trash"></span></a>` );

		addCurrencyDataAttributes( $( this ) );
	} );

	// store lowercase currency code as data on every currency field
	$( '.js-integration-country-field' ).each( function () {
		addCurrencyDataAttributes( $( this ) );
	} );

	// hide Additional Connection Settings section if there are no currency options left
	if ( ! areThereCurrenciesLeftToAdd() ) {
		$( '#wc-settings-additional-connection-settings' ).hide();
	}

	// ask user for confirmation on click on migration button
	$( '#wc-moneris-migrate-button' ).on( 'click', function( e ) {

		if ( ! window.confirm('Have you added your Checkout ID in the gateway settings? Transactions will stop if you proceed without it.\nPlease click OK to proceed or Cancel to go back.') ) {
			e.preventDefault();
		}
	} );

	// delay registering event listeners so that they are added after the listeners added by the framework but before the listeners added in wc-moneris-admin.coffee
	setTimeout( function() {

		// sync add connection settings button text to selected currency
		$( `#woocommerce_${wc_moneris_admin.credit_card_gateway_id}_configured_currencies` ).on( 'change', function() {

			$( '#js-add-connection-settings' ).text( wc_moneris_admin.add_connection_settings_label.replace( '%s', $( this ).val() ) );
		} );

		// add new connection settings fields
		$( '#js-add-connection-settings' ).on( 'click', function( e ) {
			e.preventDefault();

			addConnectionSettingsFields( $( this ).closest( 'div' ).prev( 'table' ) );
		} );

		// delete existing connection settings
		$( '#mainform' ).on( 'click', '.js-remove-connection-settings', function( e ) {
			e.preventDefault();

			deleteConnectionSettingsFields( $( this ) );
		} );

		// update custom attributes on the new set of connection settings fields
		$( 'body' ).on( 'sv_wc_connection_settings_added', function( e, currency ) {

			$( '.currency-field.' + currency + '-field' ).data( 'currency', currency );

			// store currency code on remove settings icon as well
			$( '.currency-field.' + currency + '-field .js-remove-connection-settings' ).data( 'currency', currency );

			// show/hide credentials settings based on the current environment and hosted tokenization settings
			$( `#woocommerce_${wc_moneris_admin.credit_card_gateway_id}_environment` ).trigger( 'change' );
		} );

		// show fields available for the selected currency, environment, integration and hosted tokenization settings every time the integration country changes
		$( '#mainform' ).on( 'change', '.js-integration-country-field', function() {

			showConnectionSettingsFieldsForIntegration( $( this ) );
		} );

		// trigger a call to showConnectionSettingsFields() on each integration dropdown everytime the environment changes
		$( `#woocommerce_${wc_moneris_admin.credit_card_gateway_id}_environment` ).on( 'change', function() {

			let $environment = $( `#woocommerce_${wc_moneris_admin.credit_card_gateway_id}_environment` );

			if ( 'test' === $environment.val() ) {
				if ( 0 === $( '#wc-moneris-sandbox-credentials-info' ).length ) {
					$environment.after( '<p id="wc-moneris-sandbox-credentials-info" class="description">' + wc_moneris_admin.sandbox_credentials_description + '</p>' );
				} else {
					$( '#wc-moneris-sandbox-credentials-info' ).show();
				}
			} else {
				$( '#wc-moneris-sandbox-credentials-info' ).hide();
			}

			// avoid using trigger( 'change' ) to prevent listeners on wc-moneris-admin.coffee from firing unnecessarily
			$( '.js-integration-country-field' ).each( function() {
				showConnectionSettingsFieldsForIntegration( $( this ) );
			} );

		} ).change();


		$( `#woocommerce_${wc_moneris_admin.credit_card_gateway_id}_enable_avs` ).change( function() {

			var enableAvs = $( this ).is( ':checked' );

			if ( enableAvs ) {
				$( '.avs-field' ).closest( 'tr' ).show();
			} else {
				$( '.avs-field' ).closest( 'tr' ).hide();
			}
		} ).change();

		$( `#woocommerce_${wc_moneris_admin.credit_card_gateway_id}_enable_csc` ).change( function() {

			var enableCsc = $( this ).is( ':checked' );

			if ( enableCsc ) {
				$( '.csc-field' ).closest( 'tr' ).show();
			} else {
				$( '.csc-field' ).closest( 'tr' ).hide();
			}
		} ).change();

		// show the "require csc" field when the "enable csc" and "tokenization" fields are both checked
		$( `#woocommerce_${wc_moneris_admin.credit_card_gateway_id}_enable_csc, #woocommerce_${wc_moneris_admin.credit_card_gateway_id}_tokenization` ).change( function() {

			if ( $( `#woocommerce_${wc_moneris_admin.credit_card_gateway_id}_enable_csc` ).is( ':checked' ) && $( `#woocommerce_${wc_moneris_admin.credit_card_gateway_id}_tokenization` ).is( ':checked' ) ) {
				$( `#woocommerce_${wc_moneris_admin.credit_card_gateway_id}_require_csc` ).closest( 'tr' ).show();
			} else {
				$( `#woocommerce_${wc_moneris_admin.credit_card_gateway_id}_require_csc` ).closest( 'tr' ).hide();
			}

		} ).change();

	}, 300 );


	/**
	 * @description Adds settings fields to configure a new Moneris account.
	 *
	 * @since 2.13.0
	 *
	 * @param {jQuery} $table New fields will be inserted after this table.
	 */
	function addConnectionSettingsFields( $table ) {

		const $currencies = $( `#woocommerce_${wc_moneris_admin.credit_card_gateway_id}_configured_currencies` );
		const currency    = $currencies.val().toLowerCase();

		// prevent adding more than one set of connection settings for the same currency
		if ( $( '.currency-field.' + currency + '-field' ).length >= 1 ) {
			return;
		}

		const $title       = $( '.wc-settings-sub-title.currency-field.xxx-field' ).clone();
		const $description = $( '.wc-settings-sub-title.currency-field.xxx-field + p' ).clone();
		const $rows        = $( '.currency-field.xxx-field' ).closest( 'tr' ).clone();

		// update currency code in tittle
		$title.attr( 'id', $title.attr( 'id' ).replace( 'xxx', currency ) );
		$title.html( $title.html().replace( 'XXX', currency.toUpperCase() ) );

		// update currency code in title description
		$description.text( $description.text().replace( 'XXX', currency.toUpperCase() ) );

		// update field names and ids using the selected currency code
		const $fields = $rows.find( '.xxx-field' ).each( function() {

			const $field = $( this );
			const $label = $field.closest( 'tr' ).find( 'label[for]' );

			$label.attr( 'for' , $label.attr( 'for' ).replace( 'xxx', currency ) );
			$field.attr( 'name', $field.attr( 'name' ).replace( 'xxx', currency ) );
			$field.attr( 'id', $field.attr( 'id' ).replace( 'xxx', currency ) );
		} );

		// select default integration country for the selected currency
		$fields.filter( '.js-integration-country-field' ).val( wc_moneris_admin[ `${currency}_default_integration`] );

		// make sure cloned elements are visible now
		$title.removeClass( 'hidden xxx-field' ).addClass( currency + '-field' );
		$fields.removeClass( 'hidden xxx-field' ).addClass( currency + '-field' );

		// insert new fields into the form-table immediately before the additional connection settings dropdown
		$title.insertAfter( $table ).show();
		$description.insertAfter( $title ).show();
		$( '<table class="form-table"></table>' ).append( $rows.show() ).insertAfter( $description );

		// add selected currency to the array of configured currencies
		$currencies.closest( 'fieldset' ).append( '<input type="hidden" name="' + $currencies.attr( 'id' ) + '[]" value="' + currency.toUpperCase() + '">' );

		// restore the data-tip attribute and enable help tooltips
		$rows.find( '.woocommerce-help-tip' )
			.each( ( i, el ) => $( el ).attr( 'data-tip', $( el ).attr( 'data-original-tip' ) ) )
			.tipTip( {
				attribute: 'data-tip',
				fadeIn:    50,
				fadeOut:   50,
				delay:     200
			} );

		// hide Additional Connection Settings section if there are no currency options left
		if ( ! areThereCurrenciesLeftToAdd() ) {
			$( '#wc-settings-additional-connection-settings' ).hide();
		}

		$( 'body' ).trigger( 'sv_wc_connection_settings_added', [ currency ] );
	}


	/**
	 * @description Removes settings fields for a Moneris account.
	 *
	 * @since 2.13.0
	 *
	 * @param {jQuery} $link The link element that was clicked to remove the fields.
	 */
	function deleteConnectionSettingsFields( $link ) {

		const currency = $link.data( 'currency' );

		// find the title, description and field rows that will be deleted
		let $elements = $( '.wc-settings-sub-title.currency-field.' + currency + '-field' );

		$elements = $elements.add( '.wc-settings-sub-title.currency-field.' + currency + '-field + p' );
		$elements = $elements.add( $( '.currency-field.' + currency + '-field' ).closest( 'tr' ) );

		// hide the elements and remove them after all animations are complete (thanks to promise())
		$elements.delay( 50 ).fadeOut( 400 ).promise().done( function() {

			$elements.remove();

			// replace currency code with an empty value, that way an array of (empty) codes is still sent to the server if all connection settings are removed
			$( `[name="woocommerce_${wc_moneris_admin.credit_card_gateway_id}_configured_currencies[]"][value="` + currency.toUpperCase() + '"]' ).val( '' );
		} );

		// show Additional Connection Settings section
		$( '#wc-settings-additional-connection-settings' ).show();
	}


	/**
	 * @description Checks if there are still currency settings to add.
	 *
	 * @since 2.13.1
	 *
	 * @returns {boolean} True if there are currency settings left to add.
	 */
	function areThereCurrenciesLeftToAdd() {

		let currenciesToAdd = false;

		$( '#woocommerce_moneris_configured_currencies option' ).each( function() {

			const currency = $( this ).val().toLowerCase();

			// check if the connection settings fields for this currency are already visible
			if ( $( `.${currency}-field .js-remove-connection-settings` ).length >= 1 ) {
				$( this ).hide();
			} else {
				currenciesToAdd = true;
			}
		} );

		return currenciesToAdd;
	}


	/**
	 * @description Shows settings fields associated with the given integration country field.
	 *
	 * @since 2.13.0
	 *
	 * @param {jQuery} $integration The integration country dropdown for this group of settings.
	 */
	function showConnectionSettingsFieldsForIntegration( $integration ) {

		const environment  = $( `#woocommerce_${wc_moneris_admin.credit_card_gateway_id}_environment` ).val();

		showConnectionSettingsFields( $integration.data( 'currency' ), $integration.val(), environment );
	}


	/**
	 * @description Shows settings fields based on the current environment, integration country and currency.
	 *
	 * @since 2.13.0
	 *
	 * @param {string} currency    Lowercase currency code.
	 * @param {string} integration One of 'us' or 'ca'.
	 * @param {string} environment One of 'production' or 'test'.
	 */
	function showConnectionSettingsFields( currency, integration, environment ) {

		const $fields = $( `.integration-field.${currency}-field` ).not( '.hidden' );

		// hide all integration-dependant fields
		$fields.closest( 'tr' ).hide();

		// show the currently configured integration fields
		$fields.filter( `.${integration}-field.${environment}-field` ).closest( 'tr' ).show();

		// update the merchant center URL to match the selected environment and integration
		$( `.${currency}-field.hosted-tokenization-field.${environment}-field` ).each( function() {

			const $field = $( this );
			const url    = $field.data( integration + '-' + environment + '-merchant-center-url' );

			if ( url ) {
				$field.closest( 'tr' ).find( '.description a' ).attr( 'href', url );
			}
		} );

		// the dynamic descriptor description is only relevant when a US integration is being used
		const $description = $( `#woocommerce_${wc_moneris_admin.credit_card_gateway_id}_dynamic_descriptor` ).closest( 'td' ).find( '.description' );

		// confirm that US integration is selected on a visible Integration Country field
		if ( $( '.js-integration-country-field option[value="us"]:selected:visible' ).length ) {
			$description.show();
		} else {
			$description.hide();
		}
	}


	/**
	 * Adds currency data attributes for children elements.
	 *
	 * @since 2.17.1
	 *
	 * @param {Object} $container
	 */
	function addCurrencyDataAttributes( $container ) {

		const matches = $container.attr( 'class' ).match( /currency-field (\w{3})-field/ );

		if ( matches ) {

			let $currencyFields = $( `.currency-field.${ matches[ 1 ] }-field` ).data( 'currency', matches[ 1 ] );

			// store currency code on remove settings icon as well
			$currencyFields.find( '.js-remove-connection-settings' ).data( 'currency', matches[ 1 ] );
		}
	}

} );
