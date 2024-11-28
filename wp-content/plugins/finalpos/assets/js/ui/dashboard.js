document.addEventListener('DOMContentLoaded', function () {
    const dashboardData = window.dashboardData;
    const wpDateFormat = window.wpDateFormat;
    const currencySettings = window.currencySettings;
    const translations = window.translations;

    // CSS Variables Retrieval
    const rootStyles = getComputedStyle(document.documentElement);
    const primaryColor = rootStyles.getPropertyValue('--uxlabs-primary-color').trim();
    const secondaryColor = rootStyles.getPropertyValue('--uxlabs-secondary-color').trim();
    const borderColor = rootStyles.getPropertyValue('--uxlabs-border-color').trim();

    const revenueCtx = document.getElementById('revenue-chart').getContext('2d');
    const ordersCtx = document.getElementById('orders-chart').getContext('2d');

    // Helper Function: Prepare Chart Data
    const prepareChartData = (data, valueKey) => 
        data.map(day => ({
            x: new Date(day.date),
            y: parseFloat(day[valueKey])
        }));

    // Helper Function: Fill Missing Dates
    const fillMissingDates = (data, startDate, endDate) => {
        const dateMap = new Map(data.map(item => [item.x.toISOString().split('T')[0], item]));
        const filledData = [];
        const currentDate = new Date(startDate);

        while (currentDate <= endDate) {
            const dateStr = currentDate.toISOString().split('T')[0];
            filledData.push(dateMap.has(dateStr) ? dateMap.get(dateStr) : { x: new Date(dateStr), y: 0 });
            currentDate.setDate(currentDate.getDate() + 1);
        }

        return filledData;
    };

    // Helper Function: Overlay Data
    const overlayData = (currentData, previousData) =>
        currentData.map((item, index) => ({
            x: item.x,
            current: item.y,
            previous: previousData[index] ? previousData[index].y : 0,
            previousDate: previousData[index] ? previousData[index].x : null
        }));

    // Helper Function: Format Date Label
    const formatDateLabel = (date) => {
        // Simple translation from PHP to JavaScript Date format
        const formatMap = {
            'F': 'MMMM',
            'Y': 'yyyy',
            'm': 'MM',
            'd': 'dd',
            'j': 'd',
            'S': 'do',
            'M': 'MMM',
            'l': 'EEEE',
            'D': 'EEE',
            'y': 'yy',
            'n': 'M',
        };

        let jsFormat = wpDateFormat.replace(/[FYmdjSMlDyn]/g, match => formatMap[match] || match);
        
        return new Intl.DateTimeFormat('en-US', { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric'
        }).format(date);
    };

    // Prepare Data
    const last14DaysRevenue = prepareChartData(dashboardData.last_14_days_revenue, 'total_sales');
    const previous14DaysRevenue = prepareChartData(dashboardData.previous_14_days_revenue, 'total_sales');
    const last14DaysOrders = prepareChartData(dashboardData.last_14_days_orders, 'order_count');
    const previous14DaysOrders = prepareChartData(dashboardData.previous_14_days_orders, 'order_count');

    // Cover 14 Days in Chart Data
    const today = new Date();
    const date_14_days_ago = new Date(today);
    date_14_days_ago.setDate(today.getDate() - 14);
    const date_28_days_ago = new Date(today);
    date_28_days_ago.setDate(today.getDate() - 28);

    const filledLast14DaysRevenue = fillMissingDates(last14DaysRevenue, date_14_days_ago, today);
    const filledPrevious14DaysRevenue = fillMissingDates(previous14DaysRevenue, date_28_days_ago, date_14_days_ago);

    const filledLast14DaysOrders = fillMissingDates(last14DaysOrders, date_14_days_ago, today);
    const filledPrevious14DaysOrders = fillMissingDates(previous14DaysOrders, date_28_days_ago, date_14_days_ago);

    // Overlay Data for Charts
    const revenueData = overlayData(filledLast14DaysRevenue, filledPrevious14DaysRevenue);
    const ordersData = overlayData(filledLast14DaysOrders, filledPrevious14DaysOrders);

    // Helper Function: Format Number with Thousand and Decimal Separators
    const formatNumber = (number, decimals = 0) => {
        const { thousandSeparator, decimalSeparator, numDecimals } = currencySettings;

        let negative = number < 0;
        number = Math.abs(number);

        let fixedNumber = number.toFixed(decimals);
        let [integer, decimal] = fixedNumber.split('.');

        // Insert thousand separators
        integer = integer.replace(/\B(?=(\d{3})+(?!\d))/g, thousandSeparator);

        let formatted = decimal ? `${integer}${decimalSeparator}${decimal}` : integer;

        return negative ? `-${formatted}` : formatted;
    };

    // Helper Function: Format Currency
    const formatCurrency = (amount) => {
        const {
            symbol,
            position,
            thousandSeparator,
            decimalSeparator,
            numDecimals
        } = currencySettings;

        let negative = amount < 0;
        amount = Math.abs(amount);

        let fixedAmount = amount.toFixed(numDecimals);
        let [integer, decimal] = fixedAmount.split('.');
        
        // Insert thousand separators
        integer = integer.replace(/\B(?=(\d{3})+(?!\d))/g, thousandSeparator);
        
        let formattedNumber = decimal ? `${integer}${decimalSeparator}${decimal}` : integer;
        
        let formattedCurrency;
        switch(position) {
            case 'left':
                formattedCurrency = symbol + formattedNumber;
                break;
            case 'right':
                formattedCurrency = formattedNumber + symbol;
                break;
            case 'left_space':
                formattedCurrency = symbol + ' ' + formattedNumber;
                break;
            case 'right_space':
                formattedCurrency = formattedNumber + ' ' + symbol;
                break;
            default:
                formattedCurrency = symbol + formattedNumber;
        }

        if(negative) {
            formattedCurrency = '-' + formattedCurrency;
        }

        return formattedCurrency;
    };

    // Create Charts
    const createChart = (ctx, data, labels, label1, label2, isCurrency = false) => {
        return new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: label1,
                        data: data.map(item => ({ x: item.x, y: item.current })),
                        borderColor: primaryColor,
                        backgroundColor: 'transparent',
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: label2,
                        data: data.map(item => ({ x: item.x, y: item.previous })),
                        borderColor: secondaryColor,
                        backgroundColor: 'transparent',
                        fill: true,
                        tension: 0.3
                    }
                ]
            },
            options: {
                scales: {
                    x: {
                        type: 'time',
                        time: { unit: 'day' },
                        ticks: {
                            display: false // Remove x-axis labels
                        },
                        grid: {
                            display: true,
                            drawBorder: false,
                            drawOnChartArea: true,
                            drawTicks: false,
                            color: (context) => {
                                // Show every other vertical line
                                return context.index % 2 === 0 ? borderColor : 'transparent';
                            },
                            lineWidth: 1,
                            borderDash: [5, 3]
                        }
                    },
                    y: {
                        display: false,
                        beginAtZero: true,
                        grid: { display: false }
                    }
                },
                elements: { line: { tension: 0.3 } },
                plugins: {
                    tooltip: {
                        callbacks: {
                            title: function(tooltipItems) {
                                const item = tooltipItems[0];
                                const currentDate = new Date(item.parsed.x);
                                if (item.datasetIndex === 0) { // Current period
                                    return formatDateLabel(currentDate);
                                } else { // Previous period
                                    const previousDate = new Date(currentDate.getTime() - 14 * 24 * 60 * 60 * 1000);
                                    return formatDateLabel(previousDate);
                                }
                            },
                            label: function(context) {
                                const label = context.dataset.label || '';
                                const value = context.parsed.y;
                                if (isCurrency) {
                                    return `${label}: ${formatCurrency(value)}`;
                                } else {
                                    return `${label}: ${formatNumber(value, 0)}`;
                                }
                            }
                        }
                    },
                    legend: { display: false }
                }
            }
        });
    };

    createChart(revenueCtx, revenueData, revenueData.map(item => item.x), 'Last 14 Days', 'Previous 14 Days', true);
    createChart(ordersCtx, ordersData, ordersData.map(item => item.x), 'Last 14 Days', 'Previous 14 Days', false);

    // Calculate Percentage Change
    const calculateChangePercent = (currentTotal, previousTotal) => 
        previousTotal ? (((currentTotal - previousTotal) / previousTotal) * 100).toFixed(2) : 'N/A';

    const last14DaysRevenueTotal = filledLast14DaysRevenue.reduce((sum, day) => sum + day.y, 0);
    const previous14DaysRevenueTotal = filledPrevious14DaysRevenue.reduce((sum, day) => sum + day.y, 0);
    const revenueChangePercent = calculateChangePercent(last14DaysRevenueTotal, previous14DaysRevenueTotal);

    const last14DaysOrdersTotal = filledLast14DaysOrders.reduce((sum, day) => sum + day.y, 0);
    const previous14DaysOrdersTotal = filledPrevious14DaysOrders.reduce((sum, day) => sum + day.y, 0);
    const ordersChangePercent = calculateChangePercent(last14DaysOrdersTotal, previous14DaysOrdersTotal);

    // Set Content
    document.getElementById('revenue-total').textContent = formatCurrency(last14DaysRevenueTotal);
    document.getElementById('revenue-comparison').textContent = sprintf(translations.inLastPeriod, formatCurrency(previous14DaysRevenueTotal));
    document.getElementById('revenue-change-tag').textContent = `${formatNumber(revenueChangePercent, 2)}%`;
    document.getElementById('revenue-change-tag').classList.add(revenueChangePercent >= 0 ? 'positive' : 'negative');

    document.getElementById('orders-total').textContent = formatNumber(last14DaysOrdersTotal, 0);
    document.getElementById('orders-comparison').textContent = sprintf(translations.inLastPeriod, formatNumber(previous14DaysOrdersTotal, 0));
    document.getElementById('orders-change-tag').textContent = `${formatNumber(ordersChangePercent, 2)}%`;
    document.getElementById('orders-change-tag').classList.add(ordersChangePercent >= 0 ? 'positive' : 'negative');

    // Replace Product List Code with the Following:
    const productsList = document.getElementById('products-list');
    let last14DaysProductsTotalQuantity = dashboardData.last_14_days_products.total_quantity;
    let previous14DaysProductsTotalQuantity = dashboardData.previous_14_days_products.total_quantity;

    dashboardData.last_14_days_products.products.forEach((product, index) => {
        const previousProduct = dashboardData.previous_14_days_products.products[index];
        const previousQuantity = previousProduct ? previousProduct.quantity : 0;
        const quantityChangePercent = previousQuantity ? calculateChangePercent(product.quantity, previousQuantity) : 'N/A';

        const listItem = document.createElement('tr');
        const quantityComparisonClass = product.quantity >= previousQuantity ? 'positive' : 'negative';
        listItem.innerHTML = `
            <td class="product-name">${product.title}</td>
            <td class="product-sold"><span class="quantity-comparison ${quantityComparisonClass}">${formatNumber(product.quantity, 0)} vs ${formatNumber(previousQuantity, 0)}</span></td>
            <td class="product-revenue">${formatCurrency(product.total_sales)}</td>
        `;
        productsList.appendChild(listItem);
    });

    const productsChangePercent = calculateChangePercent(last14DaysProductsTotalQuantity, previous14DaysProductsTotalQuantity);

    // Update these lines to use the calculated totals
    document.getElementById('products-total').textContent = formatNumber(last14DaysProductsTotalQuantity, 0);
    document.getElementById('products-comparison').textContent = sprintf(translations.inLastPeriod, formatNumber(previous14DaysProductsTotalQuantity, 0));
    document.getElementById('products-change-tag').textContent = `${formatNumber(productsChangePercent, 2)}%`;
    document.getElementById('products-change-tag').classList.add(productsChangePercent >= 0 ? 'positive' : 'negative');
    
});

// Add this helper function at the beginning of the script
function sprintf(str, ...args) {
    return str.replace(/%s/g, () => args.shift());
}

jQuery(document).ready(function($) {
    const kanbanItems = document.querySelectorAll('.kanban-item');
    const kanbanColumns = document.querySelectorAll('.kanban-column');

    kanbanItems.forEach(item => {
        item.addEventListener('dragstart', dragStart);
        item.addEventListener('dragend', dragEnd);
    });

    kanbanColumns.forEach(column => {
        column.addEventListener('dragover', dragOver);
        column.addEventListener('dragenter', dragEnter);
        column.addEventListener('dragleave', dragLeave);
        column.addEventListener('drop', drop);
    });

    function dragStart() {
        this.classList.add('dragging');
    }

    function dragEnd() {
        this.classList.remove('dragging');
    }

    function dragOver(e) {
        e.preventDefault();
    }

    function dragEnter(e) {
        e.preventDefault();
        this.classList.add('drag-over');
    }

    function dragLeave() {
        this.classList.remove('drag-over');
    }

    function drop() {
        this.classList.remove('drag-over');
        const draggingItem = document.querySelector('.dragging');
        const newStatus = this.dataset.status;
        const orderId = draggingItem.dataset.orderId;

        // Update order status via AJAX
        $.ajax({
            url: finalWooCommerceOrders.ajax_url,
            type: 'POST',
            data: {
                action: 'update_order_status',
                order_id: orderId,
                new_status: newStatus,
                nonce: finalWooCommerceOrders.nonce
            },
            success: function(response) {
                if (response.success) {
                    this.querySelector('.kanban-items').appendChild(draggingItem);
                } else {
                    alert('Failed to update order status. Please try again.');
                }
            }.bind(this),
            error: function() {
                alert('An error occurred. Please try again.');
            }
        });
    }
});