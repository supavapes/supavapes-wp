jQuery(document).ready(function($) {
    // Add nonce to all AJAX requests
    $.ajaxSetup({
        data: {
            security: bulkEditorData.nonce
        }
    });

    // Function to initialize Select2
    window.initializeSelect2 = function(element, type) {
        if (!element || !element.length) return;

        if (element.hasClass('select2-hidden-accessible')) {
            element.select2('destroy');
        }

        const config = {
            width: '100%',
            tags: true,
            createTag: params => {
                const term = $.trim(params.term);
                return term ? { id: term, text: term, newTag: true } : null;
            },
            ...(type && {
                dropdownParent: element.closest('td'),
                ajax: {
                    url: ajaxurl,
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        q: params.term,
                        action: `get_${type}_terms`,
                        attribute: element.data('attribute')
                    }),
                    processResults: data => ({ results: data }),
                    cache: true
                }
            })
        };

        element.select2(config);
    }

    // Add this function near the top of the file:
    function updateQueryStringParameter(uri, key, value) {
        var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
        var separator = uri.indexOf('?') !== -1 ? "&" : "?";
        if (uri.match(re)) {
            return uri.replace(re, '$1' + key + "=" + value + '$2');
        }
        else {
            return uri + separator + key + "=" + value;
        }
    }

    // Add this event listener near the bottom of the file:
    $('.tablenav-pages a').on('click', function(e) {
        e.preventDefault();
        var href = $(this).attr('href');
        window.location.href = updateQueryStringParameter(href, 'per_page', $('#per_page').val());
    });

    // Function to generate attribute options
    function generateAttributeOptions(attributeType) {
        return attributeType === 'global' ? `
            <label>Attribute Name:</label>
            <select class="global-attribute-select" style="width: 100%;">
                <option value="">Select or type to create new</option>
            </select>
            <label>Attribute Values:</label>
            <select class="global-attribute-values-select" multiple style="width: 100%;">
                <option value="">Select or type to create new</option>
            </select>
        ` : attributeType === 'custom' ? `
            <label>Attribute Name:</label>
            <input type="text" class="custom-attribute-name">
            <label>Attribute Values:</label>
            <select class="custom-attribute-values-select" multiple style="width: 100%;">
                <option value="">Select or type to create new</option>
            </select>
        ` : '';
    }

    // Function to initialize Select2 for global attributes
    function initializeSelect2ForGlobalAttributes(container) {
        container.find('.global-attribute-select').each(function() {
            initializeSelect2($(this), 'global_attribute');
        });
        container.find('.global-attribute-values-select').each(function() {
            initializeSelect2($(this), 'global_attribute_values');
        });
    }

    // Function to initialize Select2 for custom attributes
    function initializeSelect2ForCustomAttributes(container) {
        container.find('.custom-attribute-values-select').each(function() {
            initializeSelect2($(this), 'custom_attribute_values');
        });
    }

    // Event listener for changes in attribute type
    $(document).on('change', '.attribute-type-select', function() {
        const detailsContainer = $(this).siblings('.attribute-content');
        const attributeType = $(this).val();
        detailsContainer.html(generateAttributeOptions(attributeType));
        attributeType === 'global' ? initializeSelect2ForGlobalAttributes(detailsContainer) : initializeSelect2ForCustomAttributes(detailsContainer);
    });

    // Event listener for saving a new attribute
    $(document).on('click', '.save-attribute', function() {
        const attributeType = $(this).siblings('.attribute-type-select').val();
        const attributeName = attributeType === 'global' 
            ? $(this).siblings('.attribute-content').find('.global-attribute-select').val() 
            : $(this).siblings('.attribute-content').find('.custom-attribute-name').val();
        const attributeValue = attributeType === 'global' 
            ? $(this).siblings('.attribute-content').find('.global-attribute-value').val() 
            : $(this).siblings('.attribute-content').find('.custom-attribute-input').val();
        
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'create_attribute',
                attribute_type: attributeType,
                attribute_name: attributeName,
                attribute_value: attributeValue
            },
            success: response => {
                alert(response.success ? 'Attribute created successfully.' : 'Error: ' + response.data.message);
            },
            error: (jqXHR, textStatus, errorThrown) => {
                console.log('AJAX error: ' + textStatus + ' : ' + errorThrown);
            }
        });
    });

    // Event listener for row click and "Edit all" toggle
    $('.product-row').on('click', function(e) {
        if (!$('#edit-all-toggle').is(':checked') && !$(e.target).closest('.actions-column').length) {
            toggleEditFields($(this), true);
        }
    });

    // Event listener for checkbox selection
    $('.product-checkbox').on('change', function() {
        if ($('.product-checkbox:checked').length > 0) {
            $('#bulk-actions-selector').show();
        } else {
            $('#bulk-actions-selector').hide();
        }
    });

    // Event listener for more-options icon
    $(document).on('click', '.more-options', function(e) {
        e.stopPropagation();
        const menu = $(this).siblings('.actions-menu');
        $('.actions-menu').not(menu).hide();
        menu.toggle();
    });

    // Event listener for deleting a product
    $(document).on('click', '.delete-product', function(e) {
        e.preventDefault();
        const productId = $(this).data('product-id');
        if (confirm('Are you sure you want to delete this product?')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'delete_product',
                    product_id: productId
                },
                success: response => {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Failed to delete product.');
                    }
                }
            });
        }
    });

    // Event listener for opening the custom column popup
    $('.add-custom-column').on('click', function() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: { action: 'get_custom_fields' },
            success: function(response) {
                if (response.success) {
                    console.log('Custom Fields:', response.data);
                    openCustomColumnPopup(response.data);
                } else {
                    alert('Error loading custom fields.');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
            }
        });
    });

    // Function to open the custom column popup
    function openCustomColumnPopup(customFields) {
        $('#custom-columns-container').empty();
        const savedColumns = getSavedCustomColumns();

        savedColumns.forEach((column, index) => addCustomColumnField(customFields, column.field, column.label, index + 1));
        if (!savedColumns.length) {
            addCustomColumnField(customFields);
        }

        $('#popup-overlay').fadeIn();
        $('#custom-column-popup').fadeIn();

        // Initialize event listeners for the popup
        initializeCustomColumnPopupListeners();

        $('#custom-column-popup .save-button').off('click').on('click', function() {
            saveCustomColumns();
            closeCustomColumnPopup();
        });

        // Close popup on clicking the close button, cancel button, or overlay
        $('#custom-column-popup .generic-popup-close, #custom-column-popup .cancel-button, #popup-overlay').off('click').on('click', function(e) {
            if (e.target === this) {
                closeCustomColumnPopup();
            }
        });

        // Prevent closing when clicking inside the popup
        $('#custom-column-popup').on('click', function(e) {
            e.stopPropagation();
        });
    }

    // Function to initialize event listeners for the custom column popup
    function initializeCustomColumnPopupListeners() {
        $('#add-custom-column-field').off('click').on('click', function() {
            addCustomColumnField(bulkEditorData.customFields);
        });

        $('#get-custom-fields').off('click').on('click', function() {
            console.log('Get custom fields button clicked');
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_custom_fields',
                    security: bulkEditorData.nonce // Add the nonce
                },
                success: function(response) {
                    console.log('AJAX response received:', response);
                    if (response.success) {
                        console.log('Custom fields refreshed:', response.data);
                        updateCustomFieldsDropdown(response.data);
                        alert('Custom fields refreshed successfully!');
                    } else {
                        console.error('Error refreshing custom fields:', response.data);
                        alert('Error refreshing custom fields: ' + response.data);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('AJAX error:', textStatus, errorThrown);
                    console.error('Response:', jqXHR.responseText);
                    alert('Failed to refresh custom fields. Please check the console for more details.');
                }
            });
        });

        // Use event delegation for the delete custom column icon
        $('#custom-columns-container').off('click', '.delete-custom-column').on('click', '.delete-custom-column', function() {
            $(this).closest('.custom-column-field').remove();
        });
    }

    // Function to add custom column field
    function addCustomColumnField(customFields, selectedField = '', selectedLabel = '', index = 1) {
        const fieldHtml = `
            <div class="custom-column-field">
                <label>
                    Custom Column ${index}
                    <span class="material-symbols-outlined delete-custom-column" title="Remove Column">delete</span>
                </label>
                <input type="text" class="custom-column-label" placeholder="Column Label" value="${selectedLabel}" />
                <select class="custom-field-select">
                    <option value="">Select a custom field</option>
                    ${customFields.map(field => `<option value="${field}" ${field === selectedField ? 'selected' : ''}>${field}</option>`).join('')}
                </select>
            </div>
        `;
        $('#custom-columns-container').append(fieldHtml);
    }

    // Function to close the custom column popup
    function closeCustomColumnPopup() {
        $('#popup-overlay').fadeOut();
        $('#custom-column-popup').fadeOut();
    }

    // Function to get saved custom columns
    function getSavedCustomColumns() {
        return $('.product-list th.custom-column').map(function() {
            return {
                field: $(this).data('field'),
                label: $(this).text().replace(' ', '').replace('unfold_more', '').trim()
            };
        }).get();
    }

    function saveCustomColumns() {
        const selectedFields = $('.custom-column-field').map(function() {
            return {
                field: $(this).find('.custom-field-select').val(),
                label: $(this).find('.custom-column-label').val()
            };
        }).get().filter(column => column.field && column.label);

        console.log('Selected fields:', selectedFields);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'save_custom_columns',
                custom_columns: JSON.stringify(selectedFields),
                security: bulkEditorData.nonce // Add nonce for security
            },
            success: function(response) {
                console.log('AJAX response:', response);
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error saving custom columns: ' + response.data.message);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error:', textStatus, errorThrown);
                console.log('Response:', jqXHR.responseText);
            }
        });
    }

    function loadCustomFieldValue(productId, field) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_product_custom_field',
                product_id: productId,
                field: field
            },
            success: function(response) {
                if (response.success) {
                    const row = $(`.product-list tr[data-product-id="${productId}"]`);
                    row.find(`.custom-field-display[data-field="${field}"]`).text(response.data);
                    row.find(`.custom-field-input[data-field="${field}"]`).val(response.data);
                }
            }
        });
    }

    // Close menu on click outside
    $(document).on('click', function() {
        $('.actions-menu').hide();
    });

    // Do not close menu on click inside the menu
    $(document).on('click', '.actions-menu', function(e) {
        e.stopPropagation();
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
                    meta_description: row.find('.meta-description-input').val()
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
                    meta_description: row.find('.meta-description-input').val()
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

    $('#save-all').on('click', function() {
        const mainProductData = {};
    
        $('.product-list tbody tr[data-edited="true"]').each(function() {
            const row = $(this);
            const productId = row.data('product-id');
            const isVariation = row.hasClass('variant-row');
            const mainProductId = isVariation ? row.prevAll('.product-row').first().data('product-id') : productId;

            if (!mainProductData[mainProductId]) {
                mainProductData[mainProductId] = {
                    categories: [],
                    price: '',
                    status: '',
                    title: '',
                    tags: [],
                    sku: '',
                    description: '',
                    short_description: '',
                    focus_keyword: '',
                    seo_title: '',
                    meta_description: '',
                    custom_fields: {},
                    variations: {}
                };
            }

            const data = mainProductData[mainProductId];

            if (!isVariation) {
                // Main product data
                data.categories = row.find('.category-select').val() || [];
                data.price = row.find('.price-input').val();
                data.status = row.find('.status-select').val();
                data.title = row.find('.title-input').val();
                data.tags = row.find('.tags-select').val() || [];
                data.sku = row.find('.sku-input').val();
                data.description = row.find('.description-input').val();
                data.short_description = row.find('.short_description-input').val();
                data.focus_keyword = row.find('.focus-keyword-input').val();
                data.seo_title = row.find('.seo-title-input').val();
                data.meta_description = row.find('.meta-description-input').val();
            } else {
                // Variation data
                data.variations[productId] = {
                    price: row.find('.price-input').val(),
                    sku: row.find('.sku-input').val(),
                    stock: row.find('.stock-input').data('stock-quantity'),
                    custom_fields: {}
                };
            }

            // Custom fields (for both main products and variations)
            row.find('.custom-field-input').each(function() {
                const field = $(this).data('field');
                const value = $(this).val();
                if (isVariation) {
                    data.variations[productId].custom_fields[field] = value;
                } else {
                    data.custom_fields[field] = value;
                }
            });
        });
    
        // Send data to server
        $.each(mainProductData, function(productId, data) {
            data.action = 'update_product_data';
            data.product_id = productId;
    
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data,
                success: response => {
                    console.log(response.success ? `Product ${productId} updated successfully.` : `Failed to update product ${productId}, error: ${response.data.message}`);
                }
            });
        });
    
        alert('All changes saved.');
    });

    function updateCustomFieldsDropdown(customFields) {
        const dropdowns = $('.custom-field-select');
        dropdowns.each(function() {
            const dropdown = $(this);
            const selectedValue = dropdown.val();
            dropdown.empty();
            dropdown.append('<option value="">Select a custom field</option>');
            customFields.forEach(function(field) {
                dropdown.append($('<option>', {
                    value: field,
                    text: field,
                    selected: field === selectedValue
                }));
            });
        });
        // Update the global bulkEditorData object with the new custom fields
        if (typeof bulkEditorData !== 'undefined') {
            bulkEditorData.customFields = customFields;
        } else {
            console.warn('bulkEditorData is not defined');
        }
    }

    // Load the initial custom fields
    if (typeof bulkEditorData !== 'undefined' && bulkEditorData.customFields) {
        updateCustomFieldsDropdown(bulkEditorData.customFields);
    }

    
}); // Closes jQuery(document).ready
