jQuery(document).ready(function ($) {
    'use strict';

    var variations = [];
    var  recentUpdatedVariations = [];
    var focusInQuantityInput;
    //lockAddToCartBtn(); // Lock the button on every page load.

    $(document).on('change', '.variant-qty-input', function (e) {
        // alert('here');
        var element;
        element = $(this);
        vpeCreateRequiredArrays(element);
        hideError();

        /**
         * This code is for the case when backspace key is pressed after entering a valid qty.
         * The button gets enabled which should not if all the values are either 0 or blank.
         */
        checkForAllQuantities();
    });

    $(document).on('click', '.cartSection .plusQty', function(e) {
        e.preventDefault();
        
        var input = $(this).prev('input'),
            inputValue = parseInt(input.val()),
            maxValue = parseInt(input.attr('max'));
    
        // alert("Max: " + maxValue);
        // alert("Current Value: " + inputValue);
    
        if (inputValue < maxValue) {
            input.val(inputValue + 1);
        }
    
        $('.cartSection .variant-qty-input').change();
    });
    $(document).on('click', '.cartSection .minusQty', function(e) {
        e.preventDefault();
        
        var input = $(this).next('input'),
            inputValue = parseInt(input.val()),
            minValue = parseInt(input.attr('min'));
    
        // alert("Min: " + minValue);
        // alert("Current Value: " + inputValue);
    
        if (inputValue > minValue) {
            input.val(inputValue - 1);
        }
    
        $('.cartSection .variant-qty-input').change();
    });

    jQuery( document ).on( 'click', '.wqcmv-manage-outofstock-products-modal-content .plusQty', function ( e ) {
        e.preventDefault();
        var input = jQuery(this).next(),
        inputValue = input.val(),
        minValue =  parseInt(input.attr('max'));

        if (inputValue < minValue) {
            input.val(parseInt(inputValue) + 1);
        }
        jQuery('.wqcmv-manage-outofstock-products-modal-content .variant-qty-input').change();
    });

    jQuery( document ).on( 'click', '.wqcmv-manage-outofstock-products-modal-content .minusQty', function ( e ) {
        e.preventDefault();
        var input = jQuery(this).next(),
        inputValue = input.val(),
        minValue =  parseInt(input.attr('min'));

        if (inputValue > minValue) {
            input.val(parseInt(inputValue) - 1);
        }
        jQuery('.wqcmv-manage-outofstock-products-modal-content .variant-qty-input').change();
    });
    /**
     * Event fires on the quantity input field.
     */
    $(document).on('keyup', '.variant-qty-input', function (event) {
        // alert('ddddddd');
        var element;
        var isPressedKeyVerified;
        element = $(this);
        isPressedKeyVerified = verifyTheCurrentKeyPressed(event);
        if (true === isPressedKeyVerified) {
            vpeCreateRequiredArrays(element);
            hideError();
        }

        /**
         * This code is for the case when backspace key is pressed after entering a valid qty.
         * The button gets enabled which should not if all the values are either 0 or blank.
         */
        checkForAllQuantities();

    });

    $(document).on('keyup', '.test-qty', function (e) {

        var thisQtyInput = $(this).val();

        // var _temp_string = '12abc#@';
        thisQtyInput = thisQtyInput.replace(/([A-Za-z,.€@#])+/g, '');
        $(this).val(thisQtyInput);
    });

    /**
     * Check entered quantity for all the variations.
     */
    function checkForAllQuantities() {
        // alert('innn');
        var validQty = 0;
        $('.variant-qty-input').each(function () {
            var inputBox = $(this);
            var currentQty = parseInt(inputBox.val());
            if(currentQty == 0){
                currentQty = currentQty + 1;
            }
            if (0 < currentQty) {
                validQty++;
            }
        });
        if (0 < validQty) {
            unlockAddToCartBtn();
        } else {
            //lockAddToCartBtn();
        }

    }

    /**
     * function to pass key code.
     * @returns {*}
     */
    function passKeyCode(event) {
        var keyPass = event.keyCode || event.which;
        return keyPass;
    }

    /**
     * Verify the key pressed on the quantity input field.
     * @returns {boolean}
     */
    function verifyTheCurrentKeyPressed(event) {
        var keycodeArray = [];
        var i;
        var j;
        var enterKeyValue;
        var availableKeycode;
        for (i = 48, j = 96; 58 > i, 106 > j; i++, j++) {
            keycodeArray.push(i, j);
        }
        keycodeArray.push(38, j); //keycode for arrow keyup
        keycodeArray.push(40, j);
        enterKeyValue = passKeyCode(event);
        availableKeycode = $.inArray(enterKeyValue, keycodeArray);
        if (-1 !== availableKeycode) {
            return true;
        }

        return false;

    }

    /**
     * Created required array, traverse the quantity input boxes.
     */
    function vpeCreateRequiredArrays(element) {
        var inputBox = element;
        var variationId = parseInt(inputBox.data('variation-id'));
        var allowedStock = parseInt(inputBox.data('stock-quantity'));
        var backordersAllowed = inputBox.data('backorders');
        var manageStock = inputBox.data('manage-stock');
        var currentQty = parseInt(inputBox.val());
        var variationName = inputBox.data('variable-name');
        var result;
       
        if (0 < currentQty) {
            if ('no' === manageStock) {
                pushEntryIntoVariantsArray(variations, variationId, currentQty, variationName, allowedStock, backordersAllowed, manageStock, recentUpdatedVariations);
            } else {
                if ('no' === backordersAllowed) {
                    pushEntryIntoVariantsArray(variations, variationId, currentQty, variationName, allowedStock, backordersAllowed, manageStock, recentUpdatedVariations);
                } else {
                    pushEntryIntoVariantsArray(variations, variationId, currentQty, variationName, allowedStock, backordersAllowed, manageStock, recentUpdatedVariations);
                }
            }
        } else if (0 === currentQty || isNaN(currentQty) || 'undefined' === typeof currentQty) {
            result = recentUpdatedVariations.filter(function (v, i) {
                if (v.vid === variationId) {
                    recentUpdatedVariations.splice(i, 1);
                }
            });
            result = variations.filter(function (v, i) {
                if (v.vid === variationId) {
                    variations.splice(i, 1);
                }
            });
        }

        /**
         * Lock the button based on whether there is any valid variant to be added to cart.
         */
        if (0 < recentUpdatedVariations.length) {
            unlockAddToCartBtn();
        } else {
            //lockAddToCartBtn();
        }
    }

    /**
     * function to lock the cart button on page load and when all of the entered variations's quantity is less then 0.
     * @returns {boolean}
     */
    function lockAddToCartBtn() {

        $('.vpe_single_add_to_cart_button').attr('disabled', 'disabled');
        return false;

    }

    /**
     * function to unlock add to cart button when one of the entered variation's quantity greater then 0.
     */
    function unlockAddToCartBtn() {

        $('.vpe_single_add_to_cart_button').removeAttr('disabled');

    }

    /**
     * function to lock the container.
     */
    function lockVariantsContainer() {

        $('.vpe-ajax-loader-message').text('Adding variants to cart...');
        jQuery('.pre-loader_page').show();
        // $('.vpe-ajax-loader').css('display', 'block');

    }

    /**
     * function to unlock variant container.
     */
    function unlockVariantsContainer() {

        // $('.vpe-ajax-loader').css('display', 'none');
        jQuery('.pre-loader_page').hide();

    }

    /**
     * Pushing values to the variations array.
     * @param variations
     * @param variationId
     * @param currentQty
     * @param allowedStock
     * @param backordersAllowed
     * @param manageStock
     * @param recentUpdatedVariations
     */
    function pushEntryIntoVariantsArray(variations, variationId, currentQty, variationName, allowedStock, backordersAllowed, manageStock, recentUpdatedVariations) {

        var tempArr = {};
        var result;
        tempArr.vid = variationId;
        tempArr.qty = currentQty;
        tempArr['variant_name'] = variationName;
        tempArr['stock_quantity'] = allowedStock;
        console.log(tempArr);
        if ('yes' === manageStock) {
            if ('no' === backordersAllowed) {
                if (allowedStock < currentQty) {
                    tempArr['is_valid'] = 'No';
                } else {
                    tempArr['is_valid'] = 'Yes';
                }
            }
        } else {
            tempArr['is_valid'] = 'Yes';
        }
        result = variations.filter(function (v, i) {
            if (v.vid === variationId) {
                variations.splice(i, 1);
            }
        });
        variations.push(tempArr);
        result = recentUpdatedVariations.filter(function (v, i) {
            if (v.vid === variationId) {
                recentUpdatedVariations.splice(i, 1);
            }
        });
        recentUpdatedVariations.push(tempArr);

    }

    /**
     * hide the error message.
     */
    function hideError() {
        $('.error-message-blk').hide();
    }

    /**
     * Show view cart button after variations added successfully.
     */
    function showViewCartButton() {
        $('.vpe-view-cart').css('display', 'inline-block');
    }

    /**
     * Show the success message when variation added to the cart.
     */
    function showSuccessMessage() {
        $('.vpe_container_btn').after('<p class="success-message" style="color:green">Variations added successfully</p>');
    }

    /**
     * Hide the success message when variation added to the cart.
     */
    function hideSuccessMessage() {
        $('.success-message').fadeOut(3000);
    }

    /**
     * Bring fresh data.
     */
    function bringDataAsFresh() {
        $('.variant-qty-input').val(0);
        //lockAddToCartBtn();
    }

    /**
     * function to merge variations.
     * @returns {Array}
     */
    function mergeVariations(recentUpdatedVariations, variations) {
        var i;
        var variation;
        var variationId;
        var result;
        if (0 < recentUpdatedVariations.length || 0 < variations.length) {
            for (i in recentUpdatedVariations) {
                variation = recentUpdatedVariations[i];
                variationId = variation.vid;

                /**
                 * Check if the traversing variation exists in the variations array.
                 * If not, extract it from the recentUpdatedVariations and insert into variations
                 */
                result = variations.filter(function (v, i) {
                    if (v.vid === parseInt(variationId)) {
                        variations.splice(i, 1);
                    }
                });
                variations.push(variation);
            }
        }
        return variations;
    }

    /**
     * function to add variations in cart.
     * @param action
     * @param parentProductId
     * @param _merged_variations
     */
    function processAddToCart(action, parentProductId, mergedVariations) {

        var data = {
            'action': action,
            'variations': mergedVariations,
            'parent_product_id': parentProductId,
        };
        $.ajax({
            type: 'POST',
            url: WQCMVPublicJSObj.ajaxurl,
            data: data,
            success: function (response) {
                if ('vpe-product-added-to-cart-prac' === response.data.message) {
                    $('.vpe_container_btn').css('display', 'inline-block');

                    /**
                     * Updating the minicart.
                     * setInterval is just to make the user understand that some process is happening behind.
                     */
                    $(document.body).trigger('wc_fragment_refresh');
                    $('.vpe-ajax-loader-message').text('Updating the buffer stock as items have been added to cart now...');
                    setTimeout(function () {

                        /**
                         * Updating the buffer stock.
                         */
                        $('table.vpe_table tr td.vpe-qty-td input').each(function () {
                            var qtyInput = $(this);
                            var stock = qtyInput.data('stock-quantity');
                            var variationId = qtyInput.data('variation-id');
                            var backorders = qtyInput.data('backorders');
                            var arrFound;
                            var vidFoundQty;
                            var newStockQty;
                            if ('' !== stock) {
                                arrFound = variations.filter(function (vid) {
                                    return vid.vid === parseInt(variationId);
                                });
                                if (0 !== arrFound.length) {
                                    vidFoundQty = arrFound[0].qty;
                                    newStockQty = stock - vidFoundQty;
                                    qtyInput.data('stock-quantity', newStockQty);
                                    if (0 < newStockQty) {
                                        qtyInput.attr({
                                            'max': newStockQty,
                                            'data-stock-quantity': newStockQty
                                        });
                                    }
                                }
                            }
                        });

                        /**
                         * Update the stock column data.
                         */
                        $('table.vpe_table tr td.vpe-stock-td').each(function () {
                            var currentTd = $(this);
                            var elementClass = currentTd.attr('class');
                            var elementId = currentTd.attr('id');
                            var variationId = elementId.replace(elementClass + '-', '');
                            var inputQuantity = $('#vpe-qty-td-' + variationId + ' input');
                            var remainingStock = parseInt(inputQuantity.data('stock-quantity'));
                            var manageStock = inputQuantity.data('manage-stock');
                            var backordersAllowed = inputQuantity.data('backorders');
                            var status;
                            var stockHtml = '';
                            var arrFound = variations.filter(function (vid) {
                                return parseInt(vid.vid) === parseInt(variationId);
                            });
                            if (0 !== arrFound.length) {
                                if ('yes' === manageStock && 'no' === backordersAllowed) {
                                    currentTd.text('');
                                    if (0 === remainingStock) {
                                        $('#vpe-qty-td-' + variationId).text('');
                                        $('<div />', {
                                            class: 'out_stock_qty',
                                            text: 'N/A'
                                        }).appendTo('#vpe-qty-td-' + variationId);
                                        stockHtml = 'Already in Cart';
                                        $('<i />', {
                                            class: 'icon-smile'
                                        }).appendTo(currentTd);
                                        $('<span />', {
                                            class: 'status',
                                            text: stockHtml
                                        }).appendTo(currentTd);
                                    } else if (0 <= remainingStock) {

                                        /**
                                         * Simple update the span text in the td.
                                         */
                                        $('<i/>', {
                                            class: 'icon-smile'
                                        }).appendTo(currentTd);
                                        status = $('<span/>', {
                                            class: 'status',
                                            text: 'In Stock'
                                        });
                                        $('<span/>', {
                                            class: 'vpe_small_stock',
                                            text: '( ' + remainingStock + ' Available )'
                                        }).appendTo(status);
                                        status.appendTo(currentTd);
                                    }

                                }
                            }
                        });
                        if ('yes' === response.data.redirect_to_cart) {
                            window.location.replace(response.data.cart_url);
                        } else {
                            unlockVariantsContainer();
                        }
                        showViewCartButton();
                        showSuccessMessage();
                        jQuery('.cart-counter').text(response.data.cart_count);
                        jQuery('.header-cart .elementor-icon').trigger("click");
                        bringDataAsFresh();
                        recentUpdatedVariations = [];
                        hideSuccessMessage();
                    }, 1000);
                }
            }
        });

    }

    /**
     * Add to cart.
     *
     */
    $(document).on('click', '.vpe_single_add_to_cart_button', function (evt) {
        var mergedVariations = mergeVariations(recentUpdatedVariations, variations);
        // console.log(recentUpdatedVariations);
        var error = false;
        var needFocus = false;
        var ul;
        var parentProductId;
        var errorMessageBlk = $(this).parents().siblings('.error-message-blk');
        evt.preventDefault();
        errorMessageBlk.text('');
        ul = $('<ul/>');
        if( 0 === recentUpdatedVariations.length || 1 > recentUpdatedVariations.length ){
            error = true;
            needFocus = true;
            // $('<li/>', {
            //     class: 'warning-message',
            //     style: 'color:red',
            //     text: 'Please select at least one quantity for any variation'
            // }).appendTo(ul);
            ersrv_show_notification( 'bg-danger', 'fas fa-exclamation-triangle', 'Ooops! Error..', 'Please select at least one quantity for any variation' );
            // Scroll to the error message block
            // $('html, body').animate({
            //     scrollTop: errorMessageBlk.offset().top
            // }, 500);
            // jQuery('.error-message-blk').hide();

        }

        if( true !== needFocus ) {
            $.each(mergedVariations, function () {
                if ('No' === this.is_valid) {
                    error = true;
                    $('<li/>', {
                        class: 'warning-message',
                        style: 'color:red',
                        text: 'You cannot add that amount of ' + this.variant_name + ' to the cart because there is not enough stock (' + this.stock_quantity + ' remaining)'
                    }).appendTo(ul);
                }
            });
        }
        if (true === error) {
            ul.appendTo(errorMessageBlk);
            // errorMessageBlk.show();
            if( true === needFocus ){
                focusInQuantityInput(jQuery(this));
            }
        } else {
            parentProductId = $('#vpe-parent-product-id').val();
            lockVariantsContainer();
            // console.log(mergedVariations);
            processAddToCart('wqcmv_woocommerce_ajax_add_to_cart', parentProductId, mergedVariations);
        }

    });

    focusInQuantityInput = function(cartButton){
        var tableBody;
        var firstInput;
        if( undefined !== cartButton ){
            tableBody = cartButton.parent().parent();
            firstInput = tableBody.find('input[name=qty]').filter(':input:visible:first');
            firstInput.focus();
        }
    };

    /**
     * Pagination for the variations.
     */
    $(document).on('click', '.products-pagination', function () {

        var thisBtn = $(this);
        var activeChunkVal = $(thisBtn).siblings('#vpe-active-chunk').val();
        var nextChunkVal = $(thisBtn).siblings('#vpe-active-chunk').val();
        var activeChunk = parseInt(activeChunkVal);
        var nextChunk = parseInt(nextChunkVal);
        var loadchunk;
        var parentProductId;
        var data;

        if (thisBtn.hasClass('next')) {
            loadchunk = activeChunk + 1;
        }
        if (thisBtn.hasClass('prev')) {
            loadchunk = activeChunk - 1;
        }
        if (0 > loadchunk) {
            $(thisBtn).parent('.pagination-for-products').find('.prev').prop('disabled', true);
            $('html,body').animate({
                scrollTop: $('.vpe_table_responsive').offset().top - 90
            }, 1000);
            return false;
        }
        if (0 <= loadchunk) {
            $(thisBtn).parent('.pagination-for-products').find('.prev').prop('disabled', false);
            $('html,body').animate({
                scrollTop: $('.vpe_table_responsive').offset().top - 90
            }, 1000);
        }
        parentProductId = $('#vpe-parent-product-id').val();
        data = {
            'action': 'wqcmv_products_pagination',
            'loadchunk': loadchunk,
            'parent_product_id': parentProductId,
            'changed_variations': recentUpdatedVariations,
        };
        showVpaAjaxLoader(thisBtn);
        $('html,body').animate({
            scrollTop: $('.vpe_table_responsive').offset().top - 90
        }, 1000);
        $.ajax({
            dataType: 'JSON',
            url: WQCMVPublicJSObj.ajaxurl,
            type: 'POST',
            data: data,
            success: function (response) {
                if ('vpe-product-pagination' === response.data.message) {
                    hideVpaAjaxLoader(thisBtn);
                    if ('no' === response.data.next_chunk_available) {
                        $(thisBtn).parent('.pagination-for-products').find('.next').prop('disabled', true);
                    } else if ('yes' === response.data.next_chunk_available) {
                        $(thisBtn).parent('.pagination-for-products').find('.next').prop('disabled', false);
                        $(thisBtn).parent('.pagination-for-products').find('.prev').prop('disabled', false);
                    }
                    if ('no' === response.data.prev_chunk_available) {
                        $(thisBtn).parent('.pagination-for-products').find('.prev').prop('disabled', true);
                    }

                    // Overwrite the html only when any html is returned.
                    if ('' !== response.data.html) {
                        updateRowData(thisBtn, response.data.html);
                        if (thisBtn.hasClass('next')) {
                            $(thisBtn).siblings('#vpe-next-chunk').val(nextChunk + 1);
                            $(thisBtn).siblings('#vpe-active-chunk').val(activeChunk + 1);
                        }
                        if (thisBtn.hasClass('prev')) {
                            $(thisBtn).siblings('#vpe-next-chunk').val(nextChunk - 1);
                            $(thisBtn).siblings('#vpe-active-chunk').val(activeChunk - 1);
                        }
                    }
                }

            },
        });

    });

    /**
	 * Show the notification text.
	 *
	 * @param {string} bg_color Holds the toast background color.
	 * @param {string} icon Holds the toast icon.
	 * @param {string} heading Holds the toast heading.
	 * @param {string} message Holds the toast body message.
	 */
	function ersrv_show_notification( bg_color, icon, heading, message ) {
		jQuery( '.ersrv-notification-wrapper .toast' ).removeClass( 'bg-success bg-warning bg-danger' );
		jQuery( '.ersrv-notification-wrapper .toast' ).addClass( bg_color );
		jQuery( '.ersrv-notification-wrapper .toast .ersrv-notification-icon' ).removeClass( 'fa-skull-crossbones fa-check-circle fa-exclamation-circle' );
		jQuery( '.ersrv-notification-wrapper .toast .ersrv-notification-icon' ).addClass( icon );
		jQuery( '.ersrv-notification-wrapper .toast .ersrv-notification-heading' ).text( heading );
		jQuery( '.ersrv-notification-wrapper .toast .ersrv-notification-message' ).html( message );
		jQuery( '.ersrv-notification-wrapper .toast' ).removeClass( 'hide' ).addClass( 'show' );

		setTimeout( function() {
			jQuery( '.ersrv-notification-wrapper .toast' ).removeClass( 'show' ).addClass( 'hide' );
		}, 5000 );
	}

    /**
     * Function to callback of pre order functionality.
     */
    $( document ).on( 'click', '#vns_pre_order', function( event ) {
        event.preventDefault();
        jQuery('body').addClass('sv-popup-open');
        var variationId = $(this).data('variation-id');
        var newModalContainer;
        var data;
        var modalContainer = $('#wqcmv-manage-outofstock-products-modal .wqcmv-modal-container');
        $('#wqcmv-manage-outofstock-products-modal').addClass('notify-me');
        $('#wqcmv-manage-outofstock-products-modal').show();
        $('.loader_page').show();
        modalContainer.text('');
        modalContainer.addClass('wqcmv-modal-loading');
        // $('<img/>', {src: WQCMVPublicJSObj.loader_url, alt: 'Loader'}).appendTo(modalContainer);
        // $('<p/>', {text: WQCMVPublicJSObj.wait_msg}).appendTo(modalContainer);
        data = {
            'action': 'wqcmv_get_user_email_for_notify_user_pre_order',
            'variation_id': variationId
        };
        $.ajax({
            dataType: 'JSON',
            url: WQCMVPublicJSObj.ajaxurl,
            type: 'POST',
            data: data,
            success: function (response) {
                modalContainer.removeClass('wqcmv-modal-loading');
                if ('wqcmv-out-of-stock-products-fetched-pre-order' === response.data.message) {
                    $('.wqcmv-modal-title').text(response.data.modal_title);
                    newModalContainer = $('<div/>', {class: 'wqcmv-modal-container', html: response.data.html});
                    modalContainer.replaceWith(newModalContainer);
                    $('.loader_page').hide();
                }
            },
        });
    } );

    

    $( document ).on( 'click', '#vns_simpleproduct_pre_order', function( event ) {
        event.preventDefault();
        var product_id = $( this ).data( 'product_id' );
        var newModalContainer;
        var data;
        var modalContainer = $('#wqcmv-manage-outofstock-products-modal .wqcmv-modal-container');
        $('#wqcmv-manage-outofstock-products-modal').addClass('notify-me');
        $('#wqcmv-manage-outofstock-products-modal').show();
        modalContainer.text('');
        modalContainer.addClass('wqcmv-modal-loading');
        $('<img/>', {src: WQCMVPublicJSObj.loader_url, alt: 'Loader'}).appendTo(modalContainer);
        $('<p/>', {text: WQCMVPublicJSObj.wait_msg}).appendTo(modalContainer);
        data = {
            'action': 'wqcmv_get_user_email_for_notify_user_simple_product_pre_order',
            'product_id': product_id
        };
        $.ajax({
            dataType: 'JSON',
            url: WQCMVPublicJSObj.ajaxurl,
            type: 'POST',
            data: data,
            success: function (response) {
                modalContainer.removeClass('wqcmv-modal-loading');
                if ('wqcmv-out-of-stock-products-fetched-pre-order' === response.data.message) {
                    $('.wqcmv-modal-title').text(response.data.modal_title);
                    newModalContainer = $('<div/>', {class: 'wqcmv-modal-container', html: response.data.html});
                    modalContainer.replaceWith(newModalContainer);

                }
            },
        });
    } );

    function showVpaAjaxLoader(element) {
        // jQuery('.pre-loader_page').show();
        $(element).parent().siblings('.vpe-ajax-loader').show();
        $(element).parent().siblings('.vpe-ajax-loader').find('.vpe-ajax-loader-message').text('Loading Variants....');

    }

    function hideVpaAjaxLoader(element) {
        // jQuery('.pre-loader_page').hide();
        $(element).parent().siblings('.vpe-ajax-loader').hide();
    }

    function updateRowData(element, data) {
        var newRow;
        var paginataionRow = $(element).parent().parent().find('.vpe_table_responsive .vpe_table .pagination_row');
        newRow = $('<tbody/>', {class: 'pagination_row', html: data});
        paginataionRow.replaceWith(newRow);
    }

    /**
     * variation Image Change On click
     */
    $(document).on('click', '.variation-image-id', function () {
        var imgSrc = $(this).data('imageurl');
        $.fancybox.open(imgSrc);
    });

    /**
     * Ajax call to get out of stock products in modal.
     */
    $(document).on('click', '#vpe-contact-admin', function () {

        var button = $(this);
        var productID = button.data('product-id');
        var newModalContainer;
        var modalContainer = $('#wqcmv-manage-outofstock-products-modal .wqcmv-modal-container');
        var data;
        modalContainer.text('');
        modalContainer.addClass('wqcmv-modal-loading');
        $('#wqcmv-manage-outofstock-products-modal').show();
        $('<img/>', {src: WQCMVPublicJSObj.loader_url, alt: 'Loader'}).appendTo(modalContainer);
        $('<p/>', {text: WQCMVPublicJSObj.fetch_products_wait_msg}).appendTo(modalContainer);
        data = {
            'action': 'wqcmv_get_out_of_stock_products',
            'product_id': productID
        };
        $.ajax({
            dataType: 'JSON',
            url: WQCMVPublicJSObj.ajaxurl,
            type: 'POST',
            data: data,
            success: function (response) {
                modalContainer.removeClass('wqcmv-modal-loading');
                if ('wqcmv-out-of-stock-products-fetched' === response.data.message) {
                    $('.wqcmv-modal-title').text(response.data.modal_title);
                    newModalContainer = $('<div/>', {class: 'wqcmv-modal-container', html: response.data.html});
                    modalContainer.replaceWith(newModalContainer);
                }
            },
        });

    });

    /**
     * Ajax call for modal out of stock product pagination.
     */
    $(document).on('click', '.wqcmv-modal-container .modal-pagination', function () {
        var nextButton;
        var prevButton;
        var button = $(this);
        var loadChunkVar = button.siblings('#modal-pagination-chunk');
        var loadChunk = parseInt(loadChunkVar.val());
        var productID = parseInt(loadChunkVar.data('product-id'));
        var paginationList = $('.wqcmv_product_table #the-list');
        var data;
        if (button.hasClass('next')) {
            loadChunk = loadChunk + 1;
            nextButton = button;
            prevButton = button.siblings('.prev');
        } else if (button.hasClass('prev')) {
            loadChunk = loadChunk - 1;
            prevButton = button;
            nextButton = button.siblings('.next');
        }
        data = {
            'action': 'wqcmv_modal_pagination_outofstock_products',
            'product_id': productID,
            'loadchunk': loadChunk
        };
        $.ajax({
            dataType: 'JSON',
            url: WQCMVPublicJSObj.ajaxurl,
            type: 'POST',
            data: data,
            beforeSend: function (msg) {
                paginationList.css('opacity', '0.6');
            },
            success: function (response) {
                var newList;
                if ('modal-product-pagination' === response.data.message) {
                    if ('no' === response.data.next_chunk_available) {
                        nextButton.prop('disabled', true);
                    } else {
                        nextButton.prop('disabled', false);
                    }
                    if ('no' === response.data.prev_chunk_available) {
                        prevButton.prop('disabled', true);
                    } else {
                        prevButton.prop('disabled', false);
                    }
                    if ('' !== response.data.loadchunk) {
                        loadChunkVar.val(response.data.loadchunk);
                    }

                    // Overwrite the html only when any html is returned.
                    if ('' !== response.data.html) {
                        paginationList.text('');
                        newList = $('<tbody />', {
                            class: 'wqcmv-recommended-prod-list',
                            id: 'the-list',
                            html: response.data.html
                        });
                        $('#wqcmv-select-all-checkbox').prop('checked', false);
                        paginationList.replaceWith(newList);
                    }
                }
            }
        });

    });

    /**
     * function to display modal for notifying user.
     * @param button
     */
    function wqcmvGetRequestFormHtml(button) {

        var variationId = button.data('variation-id');
        var newModalContainer;
        var data;
        var modalContainer = $('#wqcmv-manage-outofstock-products-modal .wqcmv-modal-container');
        $('#wqcmv-manage-outofstock-products-modal').addClass('notify-me');
        $('#wqcmv-manage-outofstock-products-modal').show();
        modalContainer.text('');
        modalContainer.addClass('wqcmv-modal-loading');
        $('<img/>', {src: WQCMVPublicJSObj.loader_url, alt: 'Loader'}).appendTo(modalContainer);
        $('<p/>', {text: WQCMVPublicJSObj.wait_msg}).appendTo(modalContainer);
        data = {
            'action': 'wqcmv_get_user_email_for_notify_user',
            'variation_id': variationId
        };
        $.ajax({
            dataType: 'JSON',
            url: WQCMVPublicJSObj.ajaxurl,
            type: 'POST',
            data: data,
            success: function (response) {
                modalContainer.removeClass('wqcmv-modal-loading');
                if ('wqcmv-modal-opened-for-notify-user' === response.data.message) {
                    $('.wqcmv-modal-title').text(response.data.modal_title);
                    newModalContainer = $('<div/>', {class: 'wqcmv-modal-container', html: response.data.html});
                    modalContainer.replaceWith(newModalContainer);

                }
            },
        });

    }

    /**
     * Returns the html form.
     */
    $(document).on('click', '.wqcmv-return-to-form', function () {

        var button = $('#notify_me');
        wqcmvGetRequestFormHtml(button);

    });

    /**
     * Notify user for the outof stock products.
     */
    $(document).on('click', '#notify_me', function () {
        var button = $(this);
        wqcmvGetRequestFormHtml(button);
    });

    /**
     * Create an array to send notification to user when products are back in stock.
     */
    $(document).on('click', '.wqcmv-notify-for-outofstock', function () {
        var modalContainer = $('.wqcmv-modal-container');
        var variationId = $('.wqcmv_product_form').data('variation-id');
        var wqcmvUserEmail = $('#wqcmv_user_email').val();
        var createAccount = '';
        var validateEmail = '';
        var formInputs = {};
        var data;
        var regEmail = /^([a-zA-Z0-9_.])+@(([a-zA-Z_.])+\.)+([a-zA-Z_.]{2,4})+$/;

        if ($('#create_account').prop('checked')) {
            createAccount = 'yes';
        }

        if ('' === wqcmvUserEmail) {
            $('.notification_popup.error').fadeIn(1000);
            $('.notification_popup.error').addClass('active');
            $('.notification_message .title').html('Please enter email.');
            $('.notification_popup.error').fadeOut(3000);
            return false;
        }

        if (regEmail.test(wqcmvUserEmail)) {
            validateEmail = true;
        } else {
            $('.notification_popup.error').fadeIn(1000);
            $('.notification_popup.error').addClass('active');
            $('.notification_message .title').html('Please enter valid email.');
            $('.notification_popup.error').fadeOut(3000);
            return false;
        }
        if (true === validateEmail) {

            $('.wqcmv-notify-me-form-fields input').each(function () {
                formInputs[$(this).attr('name')] = $(this).val();
            });
            data = {
                'action': 'wqcmv_notification_request',
                'variation_id': variationId,
                'email': wqcmvUserEmail,
                'create_account': createAccount
            };
            $.ajax({
                dataType: 'JSON',
                url: WQCMVPublicJSObj.ajaxurl,
                type: 'POST',
                data: data,
                success: function (response) {
                    var newModalContainer;
                    if ('error' === response.data.errormsg) {
                        newModalContainer = $('<div/>', {
                            class: 'wqcmv-modal-container',
                            html: response.data.errorhtml
                        });
                        modalContainer.replaceWith(newModalContainer);
                        return false;
                    } else if ('success' === response.data.sucessmsg) {
                        newModalContainer = $('<div/>', {
                            class: 'wqcmv-modal-container',
                            html: response.data.successhtml
                        });
                        modalContainer.replaceWith(newModalContainer);
                        $('.modal-for-notify').fadeOut(2000);
                        return false;
                    } else if ('wqcmv-modal-opened-for-notify-user' === response.data.message) {
                        newModalContainer = $('<div/>', {class: 'wqcmv-modal-container', html: response.data.html});
                        modalContainer.replaceWith(newModalContainer);
                    }
                },
            });
        }

    });

    /**
     * Close error message popup
     */
    $(document).on('click', '.notification_close', function () {
        $('.notification_popup').removeClass('active success');
    });

    /**
     * Select all checkboxes.
     */
    $(document).on('click', '#wqcmv-select-all-checkbox', function () {
        if (true === $(this).prop('checked')) {
            $('.wqcmv-out-of-stock-prods').each(function () {
                $(this).attr('checked', 'checked');
            });
        } else if (false === $(this).prop('checked')) {
            $('.wqcmv-out-of-stock-prods').each(function () {
                $(this).removeAttr('checked');
            });
        }
    });

    $(document).on('click', '.wqcmv-out-of-stock-prods', function () {
        if ($('.wqcmv-out-of-stock-prods:checked').length === $('.wqcmv-out-of-stock-prods').length) {
            $('#wqcmv-select-all-checkbox').attr('checked', 'checked');
        } else {
            $('#wqcmv-select-all-checkbox').removeAttr('checked');
        }
    });

    jQuery(document).on('input', 'input.variant-qty-input', function () {
        var minVal = parseInt(jQuery(this).attr('min'));
        if (parseInt(jQuery(this).val()) < minVal) {
            jQuery(this).val(minVal);
        }
    });

    /**
     * jQuery to call ajax for store the pre order inquiry.
     */
    $( document ).on( 'click', '.wqcmv-send-notofication-to-admin-pre-order', function( event ) {
        event.preventDefault();
        var this_btn = $( this );
        var pid      = this_btn.data( 'pid' );
        var uid      = this_btn.data( 'uid' );
        var emailInput = this_btn.closest('.wqcmv_pre_order_container').find('input[name=wqcmv_email]');
        var email = emailInput.val();
        var qty      = parseInt($('.wqcmv-prod-qty-' + pid).val());

        // Email validation regex
        var emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;

        // Remove any existing error message
        emailInput.next('.email-error-message').remove();

        if (!emailPattern.test(email)) {
            // If email is not valid, show the error message and stop the process
            $('<span class="email-error-message" style="color:red;">Please enter a valid email address.</span>').insertAfter(emailInput);
            jQuery('.ersrv-notification').fadeIn();
			ersrv_show_notification( 'bg-danger', 'fas fa-exclamation-triangle', 'Ooops! Error..', 'There are few errors that needs to be addressed.' );
            return;
        } else {
            // Hide the error message if email is valid
            emailInput.next('.email-error-message').remove();
        }

        var data = {
            'action': 'wqcmv_store_pre_order_send_notification_to_admin',
            'pid': pid,
            'uid': uid,
            'email': email,
            'qty': qty,
        };
        $('.wqcmv-send-notofication-to-admin-pre-order').text('Submitting...');
        $.ajax({
            dataType: 'JSON',
            url: WQCMVPublicJSObj.ajaxurl,
            type: 'POST',
            data: data,
            success: function (response) {
                if ('wqcmv-pre-order-notification-sent' === response.data.message) {
                    $('.wqcmv-send-notofication-to-admin-pre-order').text('Submit');
                    $('<span class="request-success-message">Request sent successfully</span>').insertBefore('.wqcmv-send-notofication-to-admin-pre-order');
                    // $('.notification_popup.success').fadeIn(1000);
                    // $('.notification_popup.success').addClass('active');
                    // $('.notification_message .title').html('Request sent successfully');
                    // $('.notification_popup.success').fadeOut(3000);
                    $('#wqcmv-manage-outofstock-products-modal').fadeOut(2000);
                    jQuery('body').removeClass('sv-popup-open');
                    location.reload(true);

                }
            },

        });

    } );

    jQuery(document).on('keyup', "#wqcmv_email", function () {
        jQuery('.email-error-message').remove();
    });

    /**
     * Ajax call to send notification to admin.
     */
    $(document).on('click', '.wqcmv-send-notofication-to-admin', function () {
        var name = $('input[name=wqcmv_name]').val();
        var email = $('input[name=wqcmv_email]').val();
        var userMessage = $('textarea[name=wqcmv_message]').val();
        var items = [];
        var validateEmail = '';
        var validateName = '';
        var variantId;
        var qty;
        var result;
        var regex = /^([a-zA-Z]{2,16})$/;
        var data;
        var regEmail = /^([a-zA-Z0-9_.])+@(([a-zA-Z_.])+\.)+([a-zA-Z_.]{2,4})+$/;

        $('.wqcmv-out-of-stock-prods').each(function () {
            var temp = {};
            if (true === $(this).prop('checked')) {
                variantId = $(this).val();
                qty = parseInt($('.wqcmv-prod-qty-' + variantId).val());
                if ('' === qty || 0 === qty || 0 > qty || undefined === qty || isNaN(qty)) {
                    qty = 1;
                }
                temp.id = variantId;
                temp.qty = qty;
                result = items.filter(function (v, i) {
                    if (v.id === variantId) {
                        items.splice(i, 1);
                    }
                });
                items.push(temp);
            }
        });


        if ('' === email && '' === name && 0 === items.length) {
            $('.notification_popup.error').fadeIn(1000);
            $('.notification_popup.error').addClass('active');
            $('.notification_message .title').html('Please enter name.<br>Please enter email.<br>Please select variation');
            $('.notification_popup.error').fadeOut(3000);
            return false;
        } else if ('' === email && '' === name) {
            $('.notification_popup.error').fadeIn(1000);
            $('.notification_popup.error').addClass('active');
            $('.notification_message .title').html('Please enter name.<br> Please enter email');
            $('.notification_popup.error').fadeOut(3000);
            return false;
        } else if ('' === name) {
            $('.notification_popup.error').fadeIn(1000);
            $('.notification_popup.error').addClass('active');
            $('.notification_message .title').html('Please enter name');
            $('.notification_popup,error').fadeOut(3000);
            return false;
        } else if ('' === email) {
            $('.notification_popup.error').fadeIn(1000);
            $('.notification_popup.error').addClass('active');
            $('.notification_message .title').html('Please enter email');
            $('.notification_popup.error').fadeOut(3000);
            return false;
        }

        if (name.match(regex)) {
            validateName = true;
        } else {
            $('.notification_popup.error').fadeIn(1000);
            $('.notification_popup.error').addClass('active');
            $('.notification_message .title').html('Please enter valid name.');
            $('.notification_popup.error').fadeOut(3000);
            return false;
        }


        if (regEmail.test(email)) {
            validateEmail = true;
        } else {
            $('.notification_popup.error').fadeIn(1000);
            $('.notification_popup.error').addClass('active');
            $('.notification_message .title').html('Please enter valid email.');
            $('.notification_popup.error').fadeOut(3000);
            return false;
        }

        if (0 !== items.length && true === validateEmail && true === validateName) {
            data = {
                'action': 'wqcmv_send_notification_to_admin',
                'items': items,
                'name': name,
                'email': email,
                'user_message': userMessage
            };

            $('.wqcmv-send-notofication-to-admin').text('Submitting...');
            $.ajax({
                dataType: 'JSON',
                url: WQCMVPublicJSObj.ajaxurl,
                type: 'POST',
                data: data,
                success: function (response) {
                    if ('wqcmv-notification-sent' === response.data.message) {
                        $('.wqcmv-send-notofication-to-admin').text('Submit');
                        $('.notification_popup.success').fadeIn(1000);
                        $('.notification_popup.success').addClass('active');
                        $('.notification_message .title').html('Request sent successfully');
                        $('.notification_popup.success').fadeOut(3000);
                        $('#wqcmv-manage-outofstock-products-modal').fadeOut(2000);
                        

                    }
                },

            });
        } else {
            $('.notification_popup.error').fadeIn(1000);
            $('.notification_popup.error').addClass('active');
            $('.notification_message .title').html('Please select variation.');
            $('.notification_popup.error').fadeOut(3000);
            return false;
        }
    });

    /**
     * Close the notify me modal.
     */
    $(document).on('click', '.close', function () {
        jQuery('body').removeClass('sv-popup-open');
        $('#wqcmv-manage-outofstock-products-modal').removeClass('notify-me');
        $('#wqcmv-manage-outofstock-products-modal').hide();
        $('body').removeClass('modal-active');

    });


    //Function to toggle preorder items on quick view
    function toggleCartRows() {
        if ($('#checkbox_preorder').is(':checked')) {
            $('.cartRow').each(function() {
                if ($(this).find('#vns_pre_order').length > 0) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        } else {
            $('.cartRow').show();
        }
    }

    // toggleCartRows();

    $(document).on('change', '#checkbox_preorder', function (e) {
        toggleCartRows();
    });

});

/*
* Table view scroll in mobile.
* */
var tableMobile = function () {

    var outerWidth = jQuery('.vpe_table_responsive').innerWidth();
    if (440 > parseInt(outerWidth)) {
        jQuery('.vpe_table_responsive table').addClass('table_mobile');
    } else {
        jQuery('.vpe_table_responsive table').removeClass('table_mobile');
    }

};
tableMobile();

jQuery(window).resize(function () {

    tableMobile();

});