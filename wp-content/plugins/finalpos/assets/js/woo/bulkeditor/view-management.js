jQuery(document).ready(function($) {
    // Function to switch view
    window.switchView = function(view) {
        const $th = $('.product-list th');
        const $td = $('.product-list td');
        const $columns = {
            name: $('.product-list .name-column'),
            action: $('.product-list .actions-column'),
            desc: $('.product-list .description-column, .product-list .short_description-column'),
            focusKeyword: $('.product-list .focus-keyword-column'),
            seoTitle: $('.product-list .seo-title-column'),
            metaDescription: $('.product-list .meta-description-column')
        };

        $th.add($td).show().removeClass('sticky');

        if (view === 'description') {
            $th.add($td).hide();
            $columns.name.add($columns.action).add($columns.desc).add($columns.focusKeyword).add($columns.seoTitle).add($columns.metaDescription).show();
        } else if (view === 'show-all') {
            $columns.name.add($columns.action).addClass('sticky');
        } else {
            $columns.desc.hide();
            $columns.focusKeyword.hide();
            $columns.seoTitle.hide();
            $columns.metaDescription.hide();
        }
    };

    // Function to initialize all Select2 elements
    window.initializeAllSelect2 = function() {
        $('.category-select').each(function() {
            window.initializeSelect2($(this), 'category');
        });
        $('.tags-select').each(function() {
            window.initializeSelect2($(this), 'tag');
        });
        $('.attribute-select, .variation-attribute-select').each(function() {
            window.initializeSelect2($(this));
        });
    };

    // Event listener for view switch
    $('.view-select').on('change', function() {
        switchView($(this).val());
    });

    // Initialize view
    switchView($('.view-select').val());

    // Function to toggle edit fields
    window.toggleEditFields = function(row, show) {
        const fieldsToToggle = [
            '.category-display', 
            '.status-display', 
            '.tags-display', 
            '.price-display', 
            '.title-display', 
            '.sku-display', 
            '.description-display', 
            '.short_description-display', 
            '.focus-keyword-display', 
            '.seo-title-display', 
            '.meta-description-display'
        ];

        if (show) {
            row.find(fieldsToToggle.join(', ')).hide();
            row.find('.category-select, .status-select, .price-input, .title-input, .sku-input, .description-input, .short_description-input, .focus-keyword-input, .seo-title-input, .meta-description-input').removeClass('hidden');
            initializeRowSelect2(row);
            row.find('.custom-field-display').hide();
            row.find('.custom-field-input').removeClass('hidden');
            row.find('.edit-gallery').show(); // Zeigt das Galerie-Bearbeitungssymbol

            // Add event listeners for changes
            row.find('input, select, textarea').on('change', function() {
                markAsEdited(row);
            });

            // For text fields, also listen to 'input' event
            row.find('input[type="text"], textarea').on('input', function() {
                markAsEdited(row);
            });
        } else {
            row.find(fieldsToToggle.join(', ')).show();
            row.find('.category-select, .status-select, .price-input, .title-input, .sku-input, .description-input, .short_description-input, .focus-keyword-input, .seo-title-input, .meta-description-input').addClass('hidden');
            row.find('.custom-field-display').show();
            row.find('.custom-field-input').addClass('hidden');
            row.find('.edit-gallery').hide(); // Versteckt das Galerie-Bearbeitungssymbol

            if (row.find('.category-select').hasClass('select2-hidden-accessible')) {
                row.find('.category-select').select2('destroy');
            }
            if (row.find('.tags-select').hasClass('select2-hidden-accessible')) {
                row.find('.tags-select').select2('destroy');
            }
        }

        // Behandle Variationen
        if (row.hasClass('product-row')) {
            const variationRows = row.nextUntil('tr:not(.variant-row)');
            variationRows.each(function() {
                const $this = $(this);
                if (show) {
                    $this.find('.edit-gallery').show();
                } else {
                    $this.find('.edit-gallery').hide();
                }
            });
        }
    };

    // Function to initialize row Select2
    function initializeRowSelect2(row) {
        const categorySelect = row.find('.category-select');
        const tagsSelect = row.find('.tags-select');
        
        if (!categorySelect.hasClass('select2-hidden-accessible')) {
            window.initializeSelect2(categorySelect, 'category');
        }
        if (!tagsSelect.hasClass('select2-hidden-accessible')) {
            window.initializeSelect2(tagsSelect, 'tag');
        }
    }

    // Function to mark a row as edited
    window.markAsEdited = function(row) {
        row.attr('data-edited', 'true');
        // Optional: Visual feedback for edited rows
        row.addClass('edited-row');
    };

    // Event listener for edit all toggle
    $('#edit-all-toggle').on('change', function() {
        const show = $(this).is(':checked');
        $('.product-list .product-row, .product-list .variant-row').each(function() {
            toggleEditFields($(this), show);
        });
        if (show) {
            window.initializeAllSelect2();
        }
    });

    // Event listener for plus-button
    $(document).on('click', '.plus-button', function() {
        const $parentRow = $(this).closest('tr');
        $parentRow.nextUntil('tr:not(.variant-row)').toggleClass('hidden').each(function() {
            toggleEditFields($(this), !$(this).hasClass('hidden'));
        });
    });
});