jQuery(document).ready(function($) {
    // Cache the statuses to avoid repeated lookups
    var statuses = wc_order_status_dropdown.statuses;

    // Dropdown-Menü hinzufügen
    $('td.order_status').each(function() {
        var $statusCol = $(this);
        var $row = $statusCol.closest('tr');
        var currentStatusText = $statusCol.text().trim();
        
        // Check if order is from Final-POS
        var isReadonly = $row.find('td.origin').text().includes('Final-pos');

        function assignDotClass(statusKey) {
            switch(statusKey) {
                case 'wc-pending': return 'orange';
                case 'wc-processing': return 'main';
                case 'wc-completed': return 'grey';
                case 'wc-cancelled': return 'lightred';
                case 'wc-refunded': return 'pink';
                case 'wc-failed': return 'red';
                case 'wc-on-hold': return 'yellow';
                case 'wc-trash': return 'lightgrey';
                case 'wc-pending-payment': return 'orange';
                case 'wc-pending-cancel': return 'lightred';
                default: return 'black';
            }
        }

        function getStatusKeyFromText(statusText) {
            return Object.keys(statuses).find(key => statuses[key] === statusText);
        }

        var currentStatusKey = getStatusKeyFromText(currentStatusText);
        var dotColor = assignDotClass(currentStatusKey);

        if (isReadonly) {
            // For Final-POS orders, just show the status text with dot
            var statusHtml = '<span class="dot ' + dotColor + '"></span>' + 
                           '<span class="status-text">' + currentStatusText + '</span>';
            $statusCol.empty().append(statusHtml);
            $statusCol.addClass('readonly');
        } else {
            // Your existing dropdown creation code
            var dropdownHtml = '<select class="wc-order-status-change">';
            $.each(statuses, function(status_key, status_value) {
                dropdownHtml += '<option value="' + status_key + '"' + (currentStatusKey == status_key ? ' selected="selected"' : '') + '>' + status_value + '</option>';
            });
            dropdownHtml += '</select>';

            var checkmarkHtml = '<span class="checkmark" style="display: none;"><span class="material-symbols-outlined">published_with_changes</span></span>';
            var spinnerHtml = '<span class="spinner"></span>';

            $statusCol.empty().append('<span class="dot ' + dotColor + '"></span>').append(spinnerHtml).append(dropdownHtml).append(checkmarkHtml);

            $statusCol.on('click', '.checkmark', function(e) {
                e.preventDefault();
                var $this = $(this);
                var $statusCol = $this.closest('td');
                var $spinner = $statusCol.find('.spinner');
                var $dropdown = $statusCol.find('.wc-order-status-change');
                var newStatus = $dropdown.val();
                var orderId = $statusCol.closest('tr').attr('id').replace('order-', '');

                $spinner.addClass('is-active');

                $.ajax({
                    url: wc_order_status_dropdown.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wc_update_order_status',
                        order_id: orderId,
                        status: newStatus,
                        security: wc_order_status_dropdown.nonce
                    },
                    success: function(response) {
                        if(response.success) {
                            var selectedStatus = response.data.new_status_label;
                            $statusCol.find('.dot').removeClass().addClass('dot ' + assignDotClass(response.data.new_status_slug));
                            $statusCol.find('.checkmark').show();
                            $statusCol.find('.spinner').removeClass('is-active');
                            setTimeout(function() {
                                $statusCol.find('.checkmark').fadeOut(300);
                            }, 100);
                        }
                    }
                });
            });

            $statusCol.on('change', '.wc-order-status-change', function() {
                var $this = $(this);
                var $statusCol = $this.closest('td');
                var $checkmark = $statusCol.find('.checkmark');
                var newStatus = $this.val();
                $checkmark.hide();
            }).trigger('change');

            $statusCol.on('change', '.wc-order-status-change', function() {
                var $this = $(this);
                var $statusCol = $this.closest('td');
                var $checkmark = $statusCol.find('.checkmark');
                $checkmark.show();
            });
        }
    });

    // Add some CSS for readonly orders
    $('<style>')
        .text(`
            td.order_status.readonly {
                pointer-events: none;
                opacity: 0.8;
            }
            td.order_status.readonly .status-text {
                margin-left: 8px;
            }
        `)
        .appendTo('head');
});

document.addEventListener('DOMContentLoaded', function() {
    var downloadButtons = document.querySelectorAll('.wc-action-button-download');
    downloadButtons.forEach(function(button) {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            window.open(this.getAttribute('href'), '_blank');
        });
    });
});
