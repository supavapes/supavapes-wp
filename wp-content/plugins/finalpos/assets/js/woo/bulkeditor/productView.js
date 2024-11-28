jQuery(document).ready(function($) {
    // Function to switch view
    function switchView(view) {
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
    }

    // Event listener for view switch
    $('.view-select').on('change', function() {
        switchView($(this).val());
    });

    // Initialize view
    switchView($('.view-select').val());

    // Event listener for plus-button
    $(document).on('click', '.plus-button', function() {
        const $parentRow = $(this).closest('tr');
        $parentRow.nextUntil('tr:not(.variant-row)').toggleClass('hidden').each(function() {
            toggleEditFields($(this), !$(this).hasClass('hidden'));
        });
    });

    // Product search
    $('.search-box').on('keyup', debounce(function() {
        const searchTerm = $(this).val().toLowerCase();
        $('.product-list tbody tr.product-row').each(function() {
            const row = $(this);
            const searchableText = row.find('td').text().toLowerCase();
            const isMatch = searchableText.includes(searchTerm);
            row.toggle(isMatch);
            row.nextUntil('tr:not(.variant-row)').toggle(isMatch);
        });
    }, 300));

    // Debounce function
    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, arguments), wait);
        };
    }
});