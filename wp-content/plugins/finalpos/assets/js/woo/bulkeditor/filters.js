jQuery(document).ready(function($) {
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

    // Filter for category and status
    $('.filter-category, .filter-status').on('change', function() {
        const selectedCategory = $('.filter-category').val();
        const selectedStatus = $('.filter-status').val().toLowerCase();

        $('.product-list tbody tr.product-row').each(function() {
            const row = $(this);
            const productCategories = row.find('.category-select').val() || [];
            const productStatus = row.find('.status-display').text().toLowerCase();

            const matchesCategory = selectedCategory === 'all-categories' || productCategories.includes(selectedCategory);
            const matchesStatus = selectedStatus === 'all-statuses' || productStatus === selectedStatus;

            row.toggle(matchesCategory && matchesStatus);
            row.nextUntil('tr:not(.variant-row)').toggle(matchesCategory && matchesStatus);
        });
    });

    // Filter for stock
    $('.filter-stock').on('change', function() {
        const selectedStock = $(this).val();

        $('.product-list tbody tr.product-row').each(function() {
            const row = $(this);
            const stockStatus = row.find('.price-input').data('stock-status');

            const matchesStock = selectedStock === 'all' || 
                (selectedStock === 'in-stock' && stockStatus === 'instock') ||
                (selectedStock === 'out-of-stock' && stockStatus === 'outofstock') ||
                (selectedStock === 'on-backorder' && stockStatus === 'onbackorder');

            row.toggle(matchesStock);
        });
    });

    // Debounce function
    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), wait);
        };
    }
});