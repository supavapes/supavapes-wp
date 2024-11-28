jQuery(document).ready(function($) {
    // Function to open the generic popup
    window.openGenericPopup = function(title, content, onSave) {
        const popup = $('#generic-popup');
        popup.find('.generic-popup-title').text(title);
        popup.find('.generic-popup-content').html(content);
        
        popup.find('.save-button').off('click').on('click', function() {
            if (typeof onSave === 'function') {
                onSave();
            }
            closeGenericPopup();
        });

        $('#popup-overlay').fadeIn();
        popup.fadeIn();

        // Handle cancel and close buttons
        popup.find('.cancel-button, .generic-popup-close').off('click').on('click', closeGenericPopup);

        // Close popup when clicking on overlay
        $('#popup-overlay').off('click').on('click', closeGenericPopup);

        // Prevent closing when clicking inside the popup
        popup.off('click').on('click', function(e) {
            e.stopPropagation();
        });
    };

    // Function to close the generic popup
    function closeGenericPopup() {
        $('#popup-overlay').fadeOut();
        $('#generic-popup').fadeOut();
    }

    // Variations management popup
    $(document).on('click', '.variant-row .material-symbols-outlined:contains("more_vert")', function(e) {
        e.stopPropagation();
        const variantRow = $(this).closest('tr');
        const productId = variantRow.prevAll('.product-row:first').data('product-id');

        $.post(ajaxurl, {
            action: 'get_variation_management_data',
            product_id: productId,
            nonce: finalBulkEditor.nonce
        }).done(response => {
            if (response.success) {
                openGenericPopup(bulkEditorTranslations.variations, response.data.html, function() {
                    const formData = $('#variation-management-form').serialize() + '&action=update_variation_management_data';

                    $.post(ajaxurl, formData).done(response => {
                        alert(response.success ? bulkEditorTranslations.variationDataSaved : bulkEditorTranslations.errorSavingVariationData + response.data.message);
                        if (response.success) location.reload();
                    }).fail((jqXHR, textStatus) => {
                        alert(bulkEditorTranslations.anErrorOccurred + textStatus);
                    });
                });

                initializeAllSelect2();
            } else {
                alert(bulkEditorTranslations.errorLoadingVariationData + response.data.message);
            }
        });
    });

    $('#save-variations').on('click', function(e) {
        e.preventDefault();
        const product_id = $('#product_id').val();
        const attributes = {};
        const variations = {};
        let new_variation = {};
    
        // Collect attributes
        $('.attribute-select').each(function() {
            attributes[$(this).data('attribute-type')] = $(this).val();
        });
    
        // Collect variations
        $('.variation').each(function() {
            const variation_id = $(this).data('variation-id');
            if (variation_id) {
                variations[variation_id] = {
                    attributes: {},
                    price: $(this).find(`input[name="variations[${variation_id}][price]"]`).val(),
                    sku: $(this).find(`input[name="variations[${variation_id}][sku]"]`).val()
                };
    
                $(this).find('.variation-attribute-select').each(function() {
                    variations[variation_id].attributes[$(this).attr('name').replace(`variations[${variation_id}][attribute_`, '').replace(']', '')] = $(this).val();
                });
            }
        });
    
        // Collect new variation data if any
        if ($('.new-variation').length) {
            $('.new-variation .variation-attribute-select').each(function() {
                new_variation.attributes = new_variation.attributes || {};
                new_variation.attributes[$(this).attr('name').replace('new_variation[attributes][', '').replace(']', '')] = $(this).val();
            });
            new_variation.price = $('.new-variation input[name="new_variation[price]"]').val();
        }
    
        $.post(ajaxurl, {
            action: 'update_variation_management_data',
            product_id,
            attributes,
            variations,
            new_variation,
            nonce: finalBulkEditor.nonce
        }).done(response => {
            alert(response.success ? 'Variation data updated successfully.' : `Error: ${response.data.message}`);
            if (response.success) location.reload();
        }).fail((jqXHR, textStatus, errorThrown) => {
            console.log(`AJAX error: ${textStatus} : ${errorThrown}`);
        });
    });

    // Gemeinsame Funktion für Inventory Management Popup
    window.openStockManagementPopup = function(productId, stockQuantity, manageStock, backorders, isVariation, mainProductId) {
        const popup = $('#stock-popup');
        
        popup.find('.stock-value.onhand').text(stockQuantity);
        popup.find('#new-stock-on-hand').text(stockQuantity);
        popup.find('#manage-stock').prop('checked', manageStock === 'yes');
        popup.find('#backorders').val(backorders);

        $('#popup-overlay').fadeIn();
        popup.fadeIn();

        // Update new stock on hand based on action and quantity
        $('#stock-action, #quantity').on('change', function() {
            const action = $('#stock-action').val();
            const quantity = parseInt($('#quantity').val(), 10) || 0;
            let newStock = parseInt(stockQuantity, 10);

            if (['stock_received', 'restock_return'].includes(action)) {
                newStock += quantity;
            } else if (action === 'inventory_recount') {
                newStock = quantity;
            } else if (['damage', 'theft', 'loss', 'sale', 'refund_damage'].includes(action)) {
                newStock -= quantity;
            }

            $('#new-stock-on-hand').text(newStock);
        });

        // Handle save button click
        popup.find('.save-button').off('click').on('click', function() {
            const newStockQuantity = parseInt($('#new-stock-on-hand').text(), 10);
            const newManageStock = $('#manage-stock').is(':checked') ? 'yes' : 'no';
            const newBackorders = $('#backorders').val();
            const stockAction = $('#stock-action').val();
            const actionQuantity = parseInt($('#quantity').val(), 10);

            // Mapping für stock_action zu baseAction und specificAction
            const actionMap = {
                'stock_received': { baseAction: 'ADD', specificAction: 'STOCK_RECEIVED' },
                'restock_return': { baseAction: 'ADD', specificAction: 'RESTOCK_RETURN' },
                'sale': { baseAction: 'REMOVE', specificAction: 'SALE' },
                'damage': { baseAction: 'REMOVE', specificAction: 'DAMAGE' },
                'theft': { baseAction: 'REMOVE', specificAction: 'THEFT' },
                'loss': { baseAction: 'REMOVE', specificAction: 'LOSS' },
                'refund_damage': { baseAction: 'REMOVE', specificAction: 'REFUND_DAMAGE' },
                'inventory_recount': { baseAction: 'RECOUNT', specificAction: 'INVENTORY_RECOUNT' }
            };

            const action = actionMap[stockAction];
            if (!action) {
                console.error('Invalid stock action:', stockAction);
                alert('Invalid stock action');
                return;
            }

            const { baseAction, specificAction } = action;

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'final_update_stock_data',
                    product_id: productId,
                    main_product_id: mainProductId,
                    stock_quantity: newStockQuantity,
                    manage_stock: newManageStock,
                    backorders: newBackorders,
                    is_variation: isVariation,
                    base_action: baseAction,
                    specific_action: specificAction,
                    quantity: actionQuantity,
                    nonce: finalStockManagement.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Aktualisieren Sie die UI basierend auf der Antwort
                        const row = $(`tr[data-product-id="${productId}"]`);
                        row.find('.stock-input').data('stock-quantity', newStockQuantity);
                        row.find('.stock-input').data('manage-stock', newManageStock);
                        row.find('.stock-input').data('backorders', newBackorders);
                        row.find('.inventory-display').text(newStockQuantity);
                        closeStockManagementPopup();
                    } else {
                        alert('Error updating stock data: ' + (response.data ? response.data.message : 'Unknown error'));
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('AJAX error:', textStatus, errorThrown);
                    alert('An unexpected error occurred.');
                }
            });
        });

        // Handle cancel and close buttons
        popup.find('.cancel-button, .generic-popup-close').off('click').on('click', closeStockManagementPopup);

        // Close popup when clicking on overlay
        $('#popup-overlay').off('click').on('click', closeStockManagementPopup);

        // Prevent closing when clicking inside the popup
        popup.on('click', function(e) {
            e.stopPropagation();
        });
    };

    function closeStockManagementPopup() {
        $('#popup-overlay').fadeOut();
        $('#stock-popup').fadeOut();
    }

    // Inventory management popup trigger
    $(document).on('click', '.inventory-display', function() {
        const row = $(this).closest('tr');
        const productId = row.data('product-id');
        const manageStock = $(this).siblings('.stock-input').data('manage-stock') === 'yes';
        const backorders = $(this).siblings('.stock-input').data('backorders');
        let stockQuantity = manageStock ? parseInt($(this).siblings('.stock-input').data('stock-quantity'), 10) : 0;
        const isVariation = row.hasClass('variant-row');
        const mainProductId = isVariation ? row.prevAll('.product-row:first').data('product-id') : productId;

        openStockManagementPopup(productId, stockQuantity, manageStock, backorders, isVariation, mainProductId);
    });

    // Description popup
    $(document).on('click', '.edit-description-icon', function() {
        const row = $(this).closest('tr');
        const productId = row.data('product-id');
        const description = row.find('.description-input').val();

        const content = `
            <label for="description-content">Description</label>
            <textarea id="description-content" name="description-content">${description}</textarea>
        `;

        openGenericPopup('Edit Description', content, function() {
            const newDescription = tinymce.get('description-content').getContent();
            const row = $(`tr[data-product-id="${productId}"]`);
            
            if (newDescription !== row.find('.description-input').val()) {
                markAsEdited(row);
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'update_description_data',
                    product_id: productId,
                    description: newDescription,
                    short_description: row.find('.short_description-input').val(),
                    focus_keyword: row.find('.focus-keyword-input').val(),
                    seo_title: row.find('.seo-title-input').val(),
                    meta_description: row.find('.meta-description-input').val(),
                    nonce: finalBulkEditor.nonce
                },
                success: response => {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error updating description.');
                    }
                }
            });
        });

        tinymce.init({
            selector: '#description-content',
            menubar: false,
            toolbar: 'bold italic | alignleft aligncenter alignright | bullist numlist outdent indent'
        });
    });

    // Short description popup
    $(document).on('click', '.edit-short-description-icon', function() {
        const row = $(this).closest('tr');
        const productId = row.data('product-id');
        const shortDescription = row.find('.short_description-input').val();

        const content = `
            <label for="short-description-content">Short Description</label>
            <textarea id="short-description-content" name="short-description-content">${shortDescription}</textarea>
        `;

        openGenericPopup('Edit Short Description', content, function() {
            const newShortDescription = tinymce.get('short-description-content').getContent();
            const row = $(`tr[data-product-id="${productId}"]`);
            
            if (newShortDescription !== row.find('.short_description-input').val()) {
                markAsEdited(row);
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'update_description_data',
                    product_id: productId,
                    description: row.find('.description-input').val(),
                    short_description: newShortDescription,
                    focus_keyword: row.find('.focus-keyword-input').val(),
                    seo_title: row.find('.seo-title-input').val(),
                    meta_description: row.find('.meta-description-input').val(),
                    nonce: finalBulkEditor.nonce
                },
                success: response => {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error updating short description.');
                    }
                }
            });
        });

        tinymce.init({
            selector: '#short-description-content',
            menubar: false,
            toolbar: 'bold italic | alignleft aligncenter alignright | bullist numlist outdent indent'
        });
    });
});
