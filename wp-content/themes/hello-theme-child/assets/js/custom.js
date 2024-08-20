jQuery(window).on("load", function() {
    jQuery('.pre-loader_page').hide();
    jQuery('body').removeClass('sv-popup-open');
    // Listen for click event on the Moneris payment button
    jQuery('.wc-block-components-checkout-place-order-button.continue-payment').on('click', function(e) {
        e.preventDefault();
        var counter = getCookie('payment_fail_counter');
        jQuery('.sv-place-order').hide();
        jQuery('.wc-block-components-checkout-place-order-button').addClass('continue-payment');
        if (parseInt(counter) >= sv_ajax.payment_fail_counter) {
            jQuery('.pre-loader_page').hide();
            jQuery('.checkout-prevent-popup').css('display', 'flex');
            return false;
        }
    });

    if (jQuery('body').hasClass('woocommerce-checkout')) {
        jQuery('.wc-block-components-checkout-place-order-button').addClass('cod-payment');
    }
});
jQuery(document).ready(function() {

    // Function to refresh cart fragments
    function refreshCartFragments() {
        jQuery.ajax({
            url: sv_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'woocommerce_get_refreshed_fragments'
            },
            success: function(data) {
                if (data && data.fragments) {
                    jQuery.each(data.fragments, function(key, value) {
                        jQuery(key).replaceWith(value);
                    });
                }
            }
        });
    }

    // Refresh cart fragments when quantities are changed
    jQuery(document).on('click', 'button[name="update_cart"]', function(e) {
        refreshCartFragments();
    });

    // Refresh cart fragments when quantities are changed
    jQuery(document).on('click', '.product-remove a', function(e) {
        refreshCartFragments();
    });

    if (jQuery('body').hasClass('woocommerce-checkout')) {
        jQuery('.wc-block-components-checkout-place-order-button').addClass('cod-payment');
        if (jQuery('#checkbox-control-0').length) {
            jQuery('#checkbox-control-0').prop('checked', true);
        } 
        if (jQuery('#radio-control-wc-payment-method-options-cod').length) {
            jQuery('#radio-control-wc-payment-method-options-cod').prop('checked', true);
        } 
        
    }
   
    if (jQuery('body').hasClass('woocommerce-wishlist') && !jQuery('body').hasClass('woocommerce-account')) {
        jQuery('body').addClass('woocommerce-account');
    }
    jQuery('.announcement-bar-close').on('click', function(e) {
        jQuery('.announcement-bar').hide();
    });
    // Function to handle AJAX request
    function filterBlogs(page = 1) {
        var categoryID = jQuery('#category-filter').val();
        var searchTerm = jQuery('#search-blog').val();
        if (searchTerm.length >= 3 || searchTerm.length == '') {
            jQuery('.pre-loader_page').show();
            jQuery.ajax({
                url: sv_ajax.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'filter_blogs',
                    category_id: categoryID,
                    search_term: searchTerm,
                    page: page
                },
                success: function(response) {
                    jQuery('.pre-loader_page').hide();
                    jQuery('.sv-blog-listing').html(response.data.blog_html);
                    jQuery('.pagination').html(response.data.pagination_html);
                }
            });
        }
    }
    jQuery('#category-filter').change(function() {
        filterBlogs();
    });
    jQuery('#search-blog').keyup(function() {
        filterBlogs();
    });
    if (window.location.href.indexOf('view-request') > -1) {
        jQuery('.woocommerce-MyAccount-navigation-link--support-request').addClass('is-active');
    }
    jQuery('#follow-up-button').on('click', function() {
        jQuery('#follow-up-form').toggle();
    });
    jQuery('#follow-up-submit').on('click', function(e) {
        e.preventDefault();
        var followUpText = jQuery('#follow-up-text').val().trim();
        var request_id = jQuery(this).data('request_id');
        if (followUpText === '') {
            jQuery('#follow-up-message').html('<div class="error-message">Please enter a follow-up message.</div>');
            return;
        }
        jQuery('.pre-loader_page').show();
        jQuery('#follow-up-message').html('');
        jQuery.ajax({
            url: sv_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'send_follow_up',
                follow_up_text: followUpText,
                request_id: request_id
            },
            success: function(response) {
                jQuery('.pre-loader_page').hide();
                if (response.success) {
                    
                    jQuery('#follow-up-message').html('<div class="followup-success-message">Follow-up message sent successfully.</div>');
                    jQuery('#follow-up-text').val('');
                    setTimeout(function() {
                        jQuery('.followup-success-message').fadeOut();
                    }, 2000);
                    setTimeout(function() {
                        jQuery('#follow-up-form').hide();
                    }, 3000);
                } else {
                    jQuery('#follow-up-message').html('<div class="followup-error-message">Error: ' + response.data + '</div>');
                }
            },
            error: function(xhr, status, error) {
                jQuery('.pre-loader_page').hide();
                jQuery('#follow-up-message').html('<div class="error-message">Error sending follow-up message: ' + error + '</div>');
            }
        });
    });
    if (jQuery('body').hasClass('woocommerce-view-order')) {
        var order_id = jQuery('.customer-support').data("order_id");
        jQuery.ajax({
            url: sv_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_order_items',
                order_id: order_id
            },
            success: function(response) {
                if (response.success && response.data.items.length > 0) {
                    var optionsHtml = '';
                    response.data.items.forEach(function(item, index) {
                        var optionValue = item.variation_id ? item.variation_id : item.product_id;
                        var optionLabel = item.name;
                        optionsHtml += `
							<div class="option">
								<input type="checkbox" id="option${index + 1}" value="${optionValue}" data-product-id="${item.product_id}" data-variation-id="${item.variation_id}">
								<label for="option${index + 1}">${optionLabel}</label>
							</div>`;
                    });
                    jQuery('.options-container').html(optionsHtml);
                    jQuery('.options-container').show();
                } else {
                    jQuery('.options-container').html('<p>No items found.</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching order items:', error);
            }
        });
    }
    jQuery(document).on('click', '.customer-support', function(e) {
		if (jQuery(this).hasClass('disabled')) {
			e.preventDefault(); // Prevent the default action
			return; // Exit the function
		}
	
		jQuery('.customer-support-modal').css('display', 'flex');
		jQuery('body').addClass('sv-popup-open');
	});
	
    jQuery(document).on('click', '.sv-email-modal__close-button', function(e) {
        jQuery('.sv-email-modal').css('display', 'none');
        jQuery('body').removeClass('sv-popup-open');
    });
    jQuery(document).on('click', '#customer-support-submit', function(e) {
        e.preventDefault();
        jQuery('.pre-loader_page').show();
        var order_id = jQuery('.customer-support').data("order_id");
        var additional_info = jQuery('#additional-info').val();
        var selectedValues = [];
        var formData = new FormData();
        jQuery('.options-container .option input:checked').each(function() {
            var productId = jQuery(this).val();
            var variationId = jQuery(this).data('variation-id') || 0;
            selectedValues.push({
                product_id: productId,
                variation_id: variationId
            });
        });
        var imagesUploaded = 0;
        jQuery('.fileImgInput').each(function(index, element) {
            var file = jQuery(element)[0].files[0];
            if (file) {
                imagesUploaded++;
            }
        });
        var valid = true;
        jQuery('#item-selection-error').hide();
        jQuery('#image-upload-error').hide();
        if (imagesUploaded < 2) {
            jQuery('.pre-loader_page').hide();
            jQuery('#image-upload-error').text('Please upload at least two images.').show();
            valid = false;
        }
        if (selectedValues.length === 0) {
            jQuery('.pre-loader_page').hide();
            jQuery('#item-selection-error').text('Please select at least one item.').show();
            valid = false;
        }
        if (!valid) {
            if (!jQuery('.ersrv-notification .bg-danger').length) {
                jQuery('.ersrv-notification').fadeIn();
                ersrv_show_notification('bg-danger', 'fas fa-exclamation-triangle', 'Ooops! Error..', 'There are few errors that need to be addressed.');
            }
            return;
        }
        formData.append('action', 'add_support_request');
        formData.append('selectedValues', JSON.stringify(selectedValues));
        formData.append('order_id', order_id);
        formData.append('additional_info', additional_info);
        jQuery('.fileImgInput').each(function(index, element) {
            var file = jQuery(element)[0].files[0];
            if (file) {
                formData.append('upload_images[]', file);
            }
        });
        jQuery.ajax({
            url: sv_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    jQuery('.pre-loader_page').hide();
                    jQuery('.success-message').remove();
                    jQuery('.sv-email-modal').show();
                    jQuery('.customer-support-modal').css('display', 'none');
                    setTimeout(function() {
                        jQuery('.sv-email-modal').hide();
                    }, 5000);
                    jQuery('body').removeClass('sv-popup-open');

                    // jQuery('.customer-support')[0].reset();

                    jQuery('.options-container .option input').prop('checked', false);
                    jQuery('.fileImgInput').val('');
                    jQuery('#additional-info').val('');
                } else {
                    console.error('Error:', response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching order items:', error);
            }
        });
    });


    // Remove error messages when an image is uploaded
    jQuery(document).on('change', '.fileImgInput', function() {
        if (jQuery(this).val()) {
            jQuery('#image-upload-error').hide();
        }
    });

    // Remove error messages when an item is selected
    jQuery(document).on('change', '.options-container .option input', function() {
        if (jQuery('.options-container .option input:checked').length > 0) {
            jQuery('#item-selection-error').hide();
        }
    });


    jQuery('.approve-support-request').on('click', function(e) {
        e.preventDefault();
        var post_id = jQuery(this).data('id');
        if (confirm('Are you sure you want to approve this support request?')) {
            jQuery.ajax({
                url: supportRequest.ajax_url,
                type: 'POST',
                data: {
                    action: 'approve_support_request',
                    post_id: post_id,
                },
                success: function(response) {
                    if (response.success) {
                        alert('Support request approved and order created.');
                        location.reload();
                    } else {
                        alert('Failed to approve support request: ' + response.data);
                    }
                },
                error: function(xhr, status, error) {
                    alert('An error occurred: ' + error);
                }
            });
        }
    });
    jQuery(document).on('click', '.supa-add-dis', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var available_qty = jQuery(this).attr('data-qty');
        var current_qty = jQuery(this).closest('.cart_item').find('input.input-text.qty.text').val();
        var final_qty = parseInt(current_qty) + parseInt(available_qty);
        jQuery(this).closest('.cart_item').find('input.input-text.qty.text').val(final_qty).trigger('change');
        jQuery("[name='update_cart']").trigger("click");
    });
    jQuery('.comment_container').each(function() {
        if (jQuery(this).find('.sv-stars').length === 0) {
            jQuery(this).addClass('no-sv-stars');
        }
    });
    if (jQuery('.woocommerce-MyAccount-navigation-link--dashboard').hasClass('is-active')) {
        let dailyChart, monthlyChart, weeklyChart;
        const currentYear = new Date().getFullYear();
        switchTab('day');
        loadChart('dailyChart', 'day', currentYear);
        loadChart('monthlyChart', 'month', currentYear);
        loadChart('weeklyChart', 'week', currentYear);
        jQuery('#purchase-year').change(function() {
            jQuery('.pre-loader_page').show();
            const selectedYear = jQuery(this).val();
            // console.log('Selected Year:', selectedYear);
            updateChart(dailyChart, 'day', selectedYear);
            updateChart(monthlyChart, 'month', selectedYear);
            updateChart(weeklyChart, 'week', selectedYear);
        });
        jQuery('.tab').click(function() {
            const tab = jQuery(this).data('tab');
            switchTab(tab);
        });
    
        function switchTab(tab) {
            jQuery('.tab').removeClass('active');
            jQuery('.tab-content').removeClass('active');
            jQuery('.tab[data-tab="' + tab + '"]').addClass('active');
            jQuery('#' + tab).addClass('active');
        }
    
        function loadChart(chartId, type, year) {
            const ctx = document.getElementById(chartId);
    
            if (!ctx) {
                // console.error(`Element with ID ${chartId} not found`);
                createEmptyChart(chartId);
                return;
            }
    
            jQuery.ajax({
                url: sv_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'get_sales_data',
                    type: type,
                    year: year
                },
                success: function(response) {
                    if (response.success) {
                        const data = response.data[type];
                        if (data.length === 0) {
                            createEmptyChart(chartId);
                            return;
                        }
                        let labels;
                        if (type === 'day') {
                            labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                        } else if (type === 'month') {
                            labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                        } else if (type === 'week') {
                            labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5'];
                        }
                        if (chartId === 'dailyChart') {
                            if (dailyChart) dailyChart.destroy();
                            dailyChart = new Chart(ctx, getChartConfig(labels, data));
                        } else if (chartId === 'monthlyChart') {
                            if (monthlyChart) monthlyChart.destroy();
                            monthlyChart = new Chart(ctx, getChartConfig(labels, data));
                        } else if (chartId === 'weeklyChart') {
                            if (weeklyChart) weeklyChart.destroy();
                            weeklyChart = new Chart(ctx, getChartConfig(labels, data));
                        }
                        jQuery('.pre-loader_page').hide();
                    } else {
                        createEmptyChart(chartId);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Failed to fetch sales data:', textStatus, errorThrown);
                    console.error('Response Text:', jqXHR.responseText);
                    createEmptyChart(chartId);
                }
            });
        }
    
        function updateChart(chart, type, year) {
            loadChart(chart.canvas.id, type, year);
        }
    
        function getChartConfig(labels, data) {
            return {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Purchase',
                        data: data,
                        backgroundColor: 'rgba(236, 78, 52, 0.2)',
                        borderColor: '#EC4E34',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            };
        }
    
        function createEmptyChart(chartId) {
            const chartContainer = document.getElementById(chartId);
            if (chartContainer) {
                const ctx = chartContainer.getContext('2d');
                const emptyLabels = [''];
                const emptyData = [0];
                new Chart(ctx, getChartConfig(emptyLabels, emptyData));
            }
            jQuery('.pre-loader_page').hide();
        }
    }
        
    
    jQuery(document).on("click", '#radio-control-wc-payment-method-options-moneris', function(e) {
        jQuery('.sv-place-order').hide();
        jQuery('.wc-block-components-checkout-place-order-button').removeClass('cod-payment');
        jQuery('.wc-block-components-checkout-place-order-button').addClass('continue-payment');
    });
    jQuery(document).on("click", '#radio-control-wc-payment-method-options-cod', function(e) {
        jQuery('.sv-place-order').show();
        jQuery('.wc-block-components-checkout-place-order-button').addClass('cod-payment');
        jQuery('.wc-block-components-checkout-place-order-button').removeClass('continue-payment');
    });
    jQuery('#locate-button').on('click', function(e) {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                var lat = position.coords.latitude;
                var lng = position.coords.longitude;
                jQuery.get('https://maps.googleapis.com/maps/api/geocode/json', {
                    latlng: lat + ',' + lng,
                    key: 'AIzaSyDRfDT-5iAbIjrIqVORmmeXwAjDgLJudiM'
                }, function(response) {
                    if (response.status === 'OK') {
                        var result = response.results[0];
                        var city = '';
                        var country = '';
                        for (var i = 0; i < result.address_components.length; i++) {
                            var component = result.address_components[i];
                            if (component.types.includes('locality')) {
                                city = component.long_name;
                            }
                            if (component.types.includes('country')) {
                                country = component.long_name;
                            }
                        }
                        jQuery('#location').val(city + ', ' + country);
                    } else {
                        jQuery('#location-error').text('Unable to retrieve your location. Please try again.');
                        jQuery('#location-error').show();
                    }
                });
            }, function(error) {
                jQuery('#location-error').text('Geolocation is not supported by this browser or permission denied.');
                jQuery('#location-error').show();
            });
        } else {
            jQuery('#location-error').text('Geolocation is not supported by this browser.');
            jQuery('#location-error').show();
        }
    });
    jQuery('.error-message').hide();
    jQuery('#first-name, #last-name, #email-address, #phone-number, #message').on('keyup', function() {
        jQuery(this).next('.error-message').text('');
    });
    jQuery('.job-apply-form-submit').on('click', function(e) {
        e.preventDefault();
        jQuery('.error-message').text('');
        let valid = true;
        const firstName = jQuery('#first-name').val().trim();
        if (firstName === '') {
            jQuery('#first-name-error').text('First name is required.');
            valid = false;
        }
        const lastName = jQuery('#last-name').val().trim();
        if (lastName === '') {
            jQuery('#last-name-error').text('Last name is required.');
            valid = false;
        }
        const email = jQuery('#email-address').val().trim();
        const emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
        if (email === '') {
            jQuery('#email-error').text('Email address is required.');
            valid = false;
        } else if (!emailPattern.test(email)) {
            jQuery('#email-error').text('Please enter a valid email address.');
            valid = false;
        }
        const phone = jQuery('#phone-number').val().trim();
        const phonePattern = /^[0-9]{10}$/;
        if (phone === '') {
            jQuery('#phone-error').text('Phone number is required.');
            valid = false;
        } else if (!phonePattern.test(phone)) {
            jQuery('#phone-error').text('Please enter a valid phone number.');
            valid = false;
        }
        const message = jQuery('#message').val().trim();
        if (message === '') {
            jQuery('#message-error').text('Message is required.');
            valid = false;
        }
        if (valid) {
            alert('Form submitted successfully!');
        }
    });

    function getCookie(name) {
        var value = "; " + document.cookie;
        var parts = value.split("; " + name + "=");
        if (parts.length == 2) return parts.pop().split(";").shift();
    }
    jQuery(document).on("click", '.sv-place-order', function(e) {
        jQuery('.pre-loader_page').show();
        jQuery('.wc-block-components-checkout-place-order-button').css('opacity','0');
        var counter = getCookie('payment_fail_counter');
        if (parseInt(counter) >= sv_ajax.payment_fail_counter) {
            jQuery('.pre-loader_page').hide();
            jQuery('.checkout-prevent-popup').css('display', 'flex');
        } else {
            // alert('innnnnnn');
            jQuery('.wc-block-components-checkout-place-order-button').click();
            jQuery('.pre-loader_page').show();
            setTimeout(function() {
                jQuery('.pre-loader_page').hide();
            }, 7000);
        }
    });
    jQuery(document).on("click", '.check-available-stores', function(e) {
        jQuery('.pre-loader_page').show();
        jQuery('body').addClass('sv-popup-open');
        var productId = jQuery(this).data("product_id");
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                var lat = position.coords.latitude;
                var lng = position.coords.longitude;
                jQuery.ajax({
                    url: sv_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'available_store_details',
                        productId: productId,
                        lat: lat,
                        lng: lng
                    },
                    success: function(response) {
                        jQuery('.pre-loader_page').hide();
                        jQuery('.store-popup').css('display', 'flex');
                        if (response.success) {
                            jQuery('.store-popup .store-popup-content-detail').html(response.data);
                        } else {
                            alert('No store details found.');
                        }
                    },
                    error: function() {
                        jQuery('.pre-loader_page').hide();
                        alert('An error occurred. Please try again.');
                    }
                });
            }, function(error) {
                jQuery('.pre-loader_page').hide();
                console.error('Geolocation error:', error);
            });
        } else {
            jQuery('.pre-loader_page').hide();
            console.error('Geolocation is not supported by this browser.');
        }
    });
    jQuery(document).on("click", '.store-popup-close, .store-popup .overlay', function(e) {
        jQuery('body').removeClass('sv-popup-open');
        jQuery('.store-popup').css('display', 'none');
    });
    jQuery(document).on("click", '.reset-variation-filter', function(e) {
        jQuery('#search_variation').val('');
        jQuery('.search-variation-enter').trigger("click");

        jQuery('#flavour').val('');
        jQuery('#flavour').trigger("change");

        jQuery('#nicotinestrength').val('');
        jQuery('#nicotinestrength').trigger("change");

        jQuery('#alloultra7000').val('');
        jQuery('#alloultra7000').trigger("change");

        jQuery('#mg').val('');
        jQuery('#mg').trigger("change");

        jQuery('#size').val('');
        jQuery('#size').trigger("change");

        jQuery('#flavourbeast').val('');
        jQuery('#flavourbeast').trigger("change");

        jQuery('#resistance').val('');
        jQuery('#resistance').trigger("change");

        jQuery('#color').val('');
        jQuery('#color').trigger("change");

        jQuery('#title').val('');
        jQuery('#title').trigger("change");

        jQuery('#colours').val('');
        jQuery('#colours').trigger("change");

        jQuery('#colour').val('');
        jQuery('#colour').trigger("change");

        jQuery('#coilohms').val('');
        jQuery('#coilohms').trigger("change");

        jQuery('#paper').val('');
        jQuery('#paper').trigger("change");

        jQuery('#saltnixblast').val('');
        jQuery('#saltnixblast').trigger("change");

        jQuery('#type').val('');
        jQuery('#type').trigger("change");

        jQuery('#grey').val('');
        jQuery('#grey').trigger("change");

        jQuery('#strength').val('');
        jQuery('#strength').trigger("change");

        jQuery('#bottlesize').val('');
        jQuery('#bottlesize').trigger("change");

        jQuery('#spin12000').val('');
        jQuery('#spin12000').trigger("change");

        jQuery('#stlth8kpro').val('');
        jQuery('#stlth8kpro').trigger("change");
    });

    jQuery(document).on("click", '.multiple-payment-attempt-failur-submit', function(e) {
        e.preventDefault();
        jQuery('body').addClass('sv-popup-open');
        var send_a_copy = jQuery('#send-me-copy').is(':checked');
        var first_name = jQuery('.failur-attempt-fname').val();
        var last_name = jQuery('.failur-attempt-lname').val();
        var email = jQuery('.failur-attempt-email').val();
        var phone = jQuery('.failur-attempt-tel').val();
        var comments = jQuery('.checkout-prevent-popup-content-wrap textarea').val();
        var isValid = true;
        if (!validateEmail(email)) {
            isValid = false;
            if (!jQuery('.failur-attempt-email').next('.error-message').length) {
                addErrorMessage(jQuery('.failur-attempt-email'), 'Please enter a valid email address.');
            }
        }
        if (!isValid) {
            if (!jQuery('.ersrv-notification .bg-danger').length) {
                jQuery('.ersrv-notification').fadeIn();
                ersrv_show_notification('bg-danger', 'fas fa-exclamation-triangle', 'Ooops! Error..', 'There are few errors that need to be addressed.');
            }
            return;
        }
        jQuery('.pre-loader_page').show();
        jQuery.ajax({
            url: sv_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'multiple_payment_attempt',
                first_name: first_name,
                last_name: last_name,
                email: email,
                phone: phone,
                send_a_copy: send_a_copy,
                comments: comments,
                nonce: sv_ajax.failure_attempt_nonce,
            },
            success: function(response) {
                if (response.success) {
                    jQuery('.pre-loader_page').hide();
                    jQuery('.checkout-prevent-popup').css('display', 'none');
                    jQuery('#sv-prevent-checkout').css('display', 'block');
                    setTimeout(function() {
                        jQuery('#sv-prevent-checkout').css('display', 'none');
                    }, 3500);
                    jQuery('body').removeClass('sv-popup-open');
                    console.log(response.data.message);
                } else {
                    console.log(response.data.message);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            }
        });
    });
    jQuery('#uploadIcon').on('click', function() {
        jQuery('#fileInput').click();
    });
    jQuery('#fileInput').on('change', function() {
        const file = this.files[0];
        if (file) {
            jQuery('.pre-loader_page').show();
            jQuery('body').addClass('sv-popup-open');
            const formData = new FormData();
            formData.append('file', file);
            formData.append('action', 'upload_profile_picture');
            formData.append('user_id', sv_ajax.current_user);
            jQuery.ajax({
                url: sv_ajax.ajax_url,
                type: 'POST',
                processData: false,
                contentType: false,
                data: formData,
                success: function(response) {
                    if (response.success) {
                        jQuery('.avatar-150, .avatar-32').attr('src', response.data.url);
                        jQuery('.pre-loader_page').hide();
                        jQuery('body').removeClass('sv-popup-open');
                        if (!jQuery('.sv-remove-avatar').length) {
                            jQuery('.user-details').append('<a href="javascript:void(0);" class="sv-remove-avatar">Remove Avatar</a>');
                        }
                    } else {
                        alert('An error occurred while uploading the file.');
                    }
                },
                error: function() {
                    alert('An error occurred while uploading the file.');
                }
            });
        }
    });
    jQuery(document).on('click', '.sv-remove-avatar', function(e) {
        e.preventDefault();
        jQuery('.pre-loader_page').show();
        jQuery('body').addClass('sv-popup-open');
        jQuery.ajax({
            url: sv_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'remove_profile_picture',
                user_id: sv_ajax.current_user
            },
            success: function(response) {
                if (response.success) {
                    jQuery('.avatar-150, .avatar-32').attr('src', response.data.url);
                    jQuery('.pre-loader_page').hide();
                    jQuery('body').removeClass('sv-popup-open');
                    jQuery('.sv-remove-avatar').remove();
                } else {
                    alert('An error occurred while removing the avatar.');
                }
            },
            error: function() {
                alert('An error occurred while removing the avatar.');
            }
        });
    });
    jQuery('.available-options').on('click', function(event) {
        event.preventDefault();
        var targetOffset = jQuery('#variable-table-wrap').offset().top - 240;
        jQuery('html, body').animate({
            scrollTop: targetOffset
        }, 1000);
    });

    function searchVariations() {
        var searchQuery = jQuery('#search_variation').val();
        var productId = jQuery('#search_variation').data("product_id");
        var flavour = jQuery('.filter select[name="Flavour"]').val();
        var nicotineStrength = jQuery('.filter select[name="Nicotine Strength"]').val();
        var filters = {};
        jQuery('.select-filter-wrap .filter select').each(function() {
            var attributeName = jQuery(this).attr('name');
            var attributeValue = jQuery(this).val();
            filters[attributeName] = attributeValue;
        });
        if (searchQuery.length >= 3 || searchQuery.length == '') {
            jQuery('.pre-loader_page').show();
            jQuery('#search_variation').attr("disabled", true);
            jQuery('body').addClass('sv-popup-open');
            setTimeout(function() {
                jQuery.ajax({
                    url: sv_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'search_product_variations',
                        search_query: searchQuery,
                        product_id: productId,
                        filters: filters,
                        nonce: sv_ajax.search_variations_nonce
                    },
                    success: function(response) {
                        jQuery('.pre-loader_page').hide();
                        jQuery('#search_variation').attr("disabled", false);
                        jQuery('#search_variation').focus();
                        jQuery('body').removeClass('sv-popup-open');
                        if (response.success) {
                            jQuery('.cartSection').html(response.data.html);
                            if (response.data.found) {
                                jQuery('.variable-table-wrap').removeClass('sv-no-variation-found');
                            } else {
                                jQuery('.variable-table-wrap').addClass('sv-no-variation-found');
                            }
                        } else {
                            jQuery('.cartSection').html('<p>No variations found</p>');
                            jQuery('.variable-table-wrap').addClass('no-variation-found');
                        }
                    },
                    error: function() {
                        jQuery('.pre-loader_page').hide();
                        jQuery('body').removeClass('sv-popup-open');
                        jQuery('.cartSection').html('<p>Error fetching variations</p>');
                    }
                });
            }, 1000);
        }
    }
    jQuery('#search_variation').on('keypress', function(e) {
        if (e.which === 13) {
            searchVariations();
        }
    });
    jQuery('.search-variation-enter').on('click', function(e) {
        searchVariations();
    });
    jQuery('.filter select').on('change', searchVariations);


    function validateEmail(email) {
        var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    function validatePhone(phone) {
        var re = /^\+?[1-9]\d{1,14}$/;
        return re.test(phone);
    }

    function addErrorMessage($element, message) {
        var errorMessage = '<div class="error-message">' + message + '</div>';
        $element.after(errorMessage);
    }

    var $form = jQuery('.deal-popup-content-detail #mc-embedded-subscribe-form-modal');
    if ($form.length > 0) {
        jQuery(document).on('click', '.modal-subscribe-form #mc-embedded-subscribe', function(event) {
            if (event) event.preventDefault();
            register($form);
        });
    }
    var $footer_form = jQuery('.footer-subscribe-form #mc-embedded-subscribe-form-footer');
    if ($footer_form.length > 0) {
        jQuery(document).on('click', '.footer-subscribe-mail-form #mc-embedded-subscribe', function(event) {
            if (event) event.preventDefault();
            subscribe_user($footer_form);
        });
    }

    function subscribe_user($footer_form) {
        var footer_email = jQuery('.footer-subscribe-mail-form #mce-EMAIL').val();
        var footer_phone = jQuery('.footer-subscribe-mail-form #mce-PHONE').val();
        var footer_isValid = true;
        $footer_form.find('.error-message').remove();
        if (!validateEmail(footer_email)) {
            footer_isValid = false;
            addErrorMessage(jQuery('.footer-subscribe-mail-form #mce-EMAIL'), 'Please enter a valid email address.');
        }
        if (!validatePhone(footer_phone)) {
            footer_isValid = false;
            addErrorMessage(jQuery('.footer-subscribe-mail-form #mce-PHONE'), 'Please enter a valid phone number.');
        }
        if (!footer_isValid) {
            jQuery('.ersrv-notification').fadeIn();
            ersrv_show_notification('bg-danger', 'fas fa-exclamation-triangle', 'Ooops! Error..', 'There are few errors that need to be addressed.');
            return;
        }
        jQuery('.pre-loader_page').show();
        jQuery('body').addClass('sv-popup-open');
        jQuery.ajax({
            type: 'POST',
            url: sv_ajax.ajax_url,
            data: {
                action: 'mailchimp_subscribe',
                email: footer_email,
                phone: footer_phone
            },
            dataType: 'json',
            success: function(response) {
                jQuery('.pre-loader_page').hide();
                jQuery('body').removeClass('sv-popup-open');
                if (response.success) {
                    jQuery('.footer-subscribe-mail-form #mce-success-response').text('Thank you for subscribing.');
                    document.cookie = "subscribe_modal=true; path=/";
                    jQuery('.footer-subscribe-mail-form #mce-success-response').show();

                } else {
                    addErrorMessage(jQuery('.footer-subscribe-mail-form #mce-error-response'), response.data || 'Error');
                }
            },
            error: function(response) {
                alert('Could not connect to the registration server. Please try again later.');
            }
        });
    }

    function register($form) {
        var email = jQuery('.modal-subscribe-form #mce-EMAIL').val();
        var phone = jQuery('.modal-subscribe-form #mce-PHONE').val();
        var isValid = true;
        $form.find('.error-message').remove();
        if (!validateEmail(email)) {
            isValid = false;
            addErrorMessage(jQuery('.modal-subscribe-form #mce-EMAIL'), 'Please enter a valid email address.');
        }
        if (!validatePhone(phone)) {
            isValid = false;
            addErrorMessage(jQuery('.modal-subscribe-form #mce-PHONE'), 'Please enter a valid phone number.');
        }
        if (!isValid) {
            jQuery('.ersrv-notification').fadeIn();
            ersrv_show_notification('bg-danger', 'fas fa-exclamation-triangle', 'Ooops! Error..', 'There are few errors that need to be addressed.');
            return;
        }
        jQuery('.pre-loader_page').show();
        jQuery('body').addClass('sv-popup-open');
        jQuery.ajax({
            type: 'POST',
            url: sv_ajax.ajax_url,
            data: {
                action: 'mailchimp_subscribe',
                email: email,
                phone: phone
            },
            dataType: 'json',
            success: function(response) {
                jQuery('.pre-loader_page').hide();
                jQuery('body').removeClass('sv-popup-open');
                if (response.success) {
                    jQuery('.modal-subscribe-form #mce-success-response').text('Thank you for subscribing.');
                    document.cookie = "subscribe_modal=true; path=/";
                    jQuery('.modal-subscribe-form #mce-success-response').show();
                    setTimeout(function() {
                        jQuery('.deal-popup').css('display', 'none');
                    }, 3500);
                } else {
                    addErrorMessage(jQuery('.modal-subscribe-form #mce-error-response'), response.data || 'Error');
                }
            },
            error: function(response) {
                alert('Could not connect to the registration server. Please try again later.');
            }
        });
    }

    
    jQuery(document).on("keyup", '.modal-subscribe-form #mce-EMAIL', function(e) {
        jQuery(this).next('.error-message').hide();
    });
    jQuery(document).on("keyup", '.modal-subscribe-form #mce-PHONE', function(e) {
        jQuery(this).next('.error-message').hide();
    });
    if (jQuery('body').hasClass('error404')) {
        var seconds = 7;
        var $dvCountDown = jQuery("#CountDown");
        var $lblCount = jQuery("#CountDownLabel");
        $dvCountDown.show();
        $lblCount.text(seconds);
        var interval = setInterval(function() {
            seconds--;
            $lblCount.text(seconds);
            if (seconds === 0) {
                $dvCountDown.hide();
                clearInterval(interval);
                window.location.href = sv_ajax.site_url;
            }
        }, 1000);
    }
    var SectionHour = '.annoucement_contdown #hours';
    var SectionMinutes = '.annoucement_contdown #minutes';
    var SectionSecound = '.annoucement_contdown #seconds';
    var SectionCountTimer = '.annoucement_contdown #countdown-timer';
    var today = new Date();
    var lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    var lastDayDate = lastDay.getDate();
    let tom_day = today.getDate() + 1;
    const Months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    if (today.getDate() == lastDayDate) {
        var tom_month = Months[today.getMonth() + 1];
    } else {
        var tom_month = Months[today.getMonth()];
    }
    let tom_year = today.getFullYear();
    // console.log(tom_month)
    var new_date = tom_month + ' ' + tom_day + ', ' + tom_year + ' 12:45:00';
    var countDownDate = new Date(new_date).getTime();
    var x = setInterval(function() {
        var now = new Date().getTime();
        var distance = countDownDate - now;
        var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        var seconds = Math.floor((distance % (1000 * 60)) / 1000);
        jQuery(SectionHour).html(hours);
        jQuery(SectionMinutes).html(minutes);
        jQuery(SectionSecound).html(seconds);
        if (distance < 0) {
            clearInterval(x);
            jQuery(SectionCountTimer).html("Offer is Expired!");
        }
    }, 1000);
    jQuery('#commentform').on('submit', function(event) {
        var comment = jQuery('#comment').val().trim();
        if (comment === '') {
            event.preventDefault();
            if (!jQuery('.comment-error').length) {
                jQuery('<p class="comment-error" style="color: red; margin-top: 10px;">Empty comment is not allowed.</p>').insertAfter('#comment');
            }
        } else {
            jQuery('.comment-error').remove();
        }
    });
    jQuery('.price_label .from').text('$1');
    if (jQuery('body.single-product').find('.product-type-variable').length > 0) {
        jQuery('body').addClass('variable-product');
    }
    jQuery(document).on("click", '.modal-for-notify .minusQty', function(e) {
        e.preventDefault();
        var $qtyInput = jQuery(this).closest('.qtyCount').find('input.variant-qty-input');
        var currentVal = parseInt($qtyInput.val());

        if (!isNaN(currentVal) && currentVal > parseInt($qtyInput.attr('min'))) {
            $qtyInput.val(currentVal - 1);
        } else {
            $qtyInput.val(parseInt($qtyInput.attr('min')));
        }
    });
    jQuery(document).on("click", '.search-icon a', function(e) {
        setTimeout(function() {
            jQuery('.dgwt-wcas-search-input').focus();
        }, 200);
    });
    jQuery(document).on("click", '.modal-for-notify .plusQty', function(e) {
        e.preventDefault();
        var $qtyInput = jQuery(this).closest('.qtyCount').find('input.variant-qty-input');
        var currentVal = parseInt($qtyInput.val());
        if (!isNaN(currentVal) && currentVal < parseInt($qtyInput.attr('max')) || currentVal === 0) {
            $qtyInput.val(currentVal + 1);
        } else {
            $qtyInput.val(parseInt($qtyInput.attr('max')));
        }
    });
    jQuery(document).on("click", '.qty-count.qty-count--minus', function() {
        jQuery('button[name="update_cart"]').removeAttr('disabled');
    });
    jQuery(document).on("click", '.qty-count.qty-count--add', function() {
        jQuery('button[name="update_cart"]').removeAttr('disabled');
    });
    var stepCounter = 1;
    var selectedValuesQuize = {};
    var checkedValues = {};
    var email;

    function isValidEmail(email) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    jQuery('.quiz-email').keyup(function(event) {
        event.preventDefault();
        jQuery('.email-error-message').remove();
    });
    jQuery('.supa-quiz-submit').click(function(event) {
        event.preventDefault();
        email = jQuery('.quiz-email').val();
        jQuery('.quiz-email').next('.email-error-message').remove();
        if (email.trim() === '') {
            jQuery('.ersrv-notification').fadeIn();
            jQuery('<span class="email-error-message" style="color:red;">Please enter a valid email address.</span>').insertAfter('.quiz-email');
            ersrv_show_notification('bg-danger', 'fas fa-exclamation-triangle', 'Ooops! Error..', 'There are few errors that needs to be addressed.');
        } else if (!isValidEmail(email)) {
            jQuery('.ersrv-notification').fadeIn();
            jQuery('<span class="email-error-message" style="color:red;">Please enter a valid email address.</span>').insertAfter('.quiz-email');
            ersrv_show_notification('bg-danger', 'fas fa-exclamation-triangle', 'Ooops! Error..', 'There are few errors that needs to be addressed.');
            return;
        } else {
            jQuery('.supa-quiz-title').hide();
            jQuery('.supa-quiz-questions-section').css('display', 'flex');
            showStep(stepCounter);
        }
    });

    function showStep(stepNumber) {
        jQuery('.supa-quiz-question').hide();
        var currentStep = jQuery('#step_' + stepNumber);
        currentStep.css('display', 'flex');
        currentStep.find('.supa-quiz-question-inner').css('display', 'flex');
        if (stepNumber === 1) {
            jQuery('.supa-quiz-prev').hide();
        } else {
            jQuery('.supa-quiz-prev').show();
        }
        var lastStep = jQuery('.supa-quiz-question').last().attr('id').split('_')[1];
        if (stepNumber == lastStep) {
            jQuery('.supa-quiz-next').hide();
            jQuery('.supa-quiz-submit-answers').show();
        } else {
            jQuery('.supa-quiz-next').show();
            jQuery('.supa-quiz-submit-answers').hide();
        }
    }
    jQuery(document).on("click", '.supa-quiz-next', function() {
        var currentStepId = "#step_" + stepCounter;
        var allAnswered = true;
        jQuery(currentStepId + ' .supa-quiz-question-inner').each(function() {
            if (jQuery(this).find('input[type="radio"]:checked').length === 0) {
                allAnswered = false;
            }
        });
        if (!allAnswered) {
            jQuery('.ersrv-notification').fadeIn();
            ersrv_show_notification('bg-warning', 'fa-exclamation-circle', 'Warning', 'Please select an option before proceeding.');
            return;
        }
        stepCounter++;
        showStep(stepCounter);
    });
    jQuery(document).on("click", '.supa-quiz-prev', function() {
        stepCounter--;
        showStep(stepCounter);
    });

    function updateSelectedValues() {
        jQuery('.supa-quiz-question-inner').each(function() {
            var questionNumber = jQuery(this).attr('id').split('_')[1];
            var selectedValue = jQuery(this).find('input[type="radio"]:checked').data('answeris');
            var checkedValue = jQuery(this).find('input[type="radio"]:checked').val();
            if (selectedValue !== undefined) {
                selectedValuesQuize[questionNumber] = selectedValue;
                checkedValues[questionNumber] = checkedValue;
            }
        });
        // console.log(selectedValuesQuize);
    }
    
    jQuery(document).on('change', '.supa-quiz-question-inner input[type="radio"]', function() {
        updateSelectedValues();
    });
    
    /**
     * Show the notification text.
     *
     * @param {string} bg_color Holds the toast background color.
     * @param {string} icon Holds the toast icon.
     * @param {string} heading Holds the toast heading.
     * @param {string} message Holds the toast body message.
     */
    function ersrv_show_notification(bg_color, icon, heading, message) {
        jQuery('.ersrv-notification-wrapper .toast').removeClass('bg-success bg-warning bg-danger');
        jQuery('.ersrv-notification-wrapper .toast').addClass(bg_color);
        jQuery('.ersrv-notification-wrapper .toast .ersrv-notification-icon').removeClass('fa-skull-crossbones fa-check-circle fa-exclamation-circle');
        jQuery('.ersrv-notification-wrapper .toast .ersrv-notification-icon').addClass(icon);
        jQuery('.ersrv-notification-wrapper .toast .ersrv-notification-heading').text(heading);
        jQuery('.ersrv-notification-wrapper .toast .ersrv-notification-message').html(message);
        jQuery('.ersrv-notification-wrapper .toast').removeClass('hide').addClass('show');
        setTimeout(function() {
            jQuery('.ersrv-notification-wrapper .toast').removeClass('show').addClass('hide');
        }, 5000);
    }
    // Show notifications.
    // ersrv_show_notification( 'bg-success', 'fa-check-circle', toast_success_heading, 'Success text.' );
    // ersrv_show_notification( 'bg-warning', 'fa-exclamation-circle', toast_notice_heading, 'Notice text.' );
    // ersrv_show_notification( 'bg-danger', 'fa-skull-crossbones', toast_error_heading, 'Error text.' );

    function sendSelectedValues() {
        // var nonce = sv_ajax.fun_questionnaire_nonce;
        jQuery.ajax({
            url: sv_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'send_selected_value',
                quizemail: email,
                selectedValues: JSON.stringify(selectedValuesQuize),
                // security: nonce
            },
            success: function(response) {
                jQuery('.supa-quiz-questions-section').css('display', 'none');
                jQuery('.supa-quiz-result').css('display', 'flex');
                if (response.data.result == "fail") {
                    jQuery('.congrats').css('display', 'none');
                } else {
                    jQuery('.thanks').css('display', 'none');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
            }
        });
    }
    
    jQuery(document).on("click", '.supa-quiz-submit-answers', function() {
        var currentStepId = "#step_" + stepCounter;
        var allAnswered = true;
        jQuery(currentStepId + ' .supa-quiz-question-inner').each(function() {
            if (jQuery(this).find('input[type="radio"]:checked').length === 0) {
                allAnswered = false;
            }
        });
        if (!allAnswered) {
            jQuery('.ersrv-notification').fadeIn();
            ersrv_show_notification('bg-warning', 'fa-exclamation-circle', 'Warning', 'Please select an option before proceeding.');
            return;
        }
        stepCounter++;
        showStep(stepCounter);
        if (typeof email === 'undefined') {
            jQuery('.ersrv-notification').fadeIn();
            ersrv_show_notification('bg-danger', 'fa-skull-crossbones', 'error', 'Please enter your email before proceeding.');
            return;
        }
        sendSelectedValues(email);
    });

    function toggleButtons(quantity) {
        var $minusButton = jQuery('.qty-count--minus');
        var $plusButton = jQuery('.qty-count--add');
        var $addToCartButton = jQuery('.single_add_to_cart_button');
        if (quantity === null || quantity === undefined || quantity === '') {
            $minusButton.prop('disabled', true);
            $plusButton.prop('disabled', true);
            $addToCartButton.prop('disabled', true);
        } else {
            $minusButton.prop('disabled', false);
            $plusButton.prop('disabled', false);
            $addToCartButton.prop('disabled', false);
        }
    }
    jQuery(document).on("change", "#flavours", function() {
        var selectedQty = jQuery(this).find('option:selected').data('quantity');
        toggleButtons(selectedQty);
        var $stockMessage = jQuery('.stock-message');

        if (selectedQty === null || selectedQty === '') {
            $stockMessage.text('Out of Stock').show();
        } else {
            $stockMessage.text(selectedQty + ' in stock').show();
        }
    });

    function whatsapp(e) {
        e.parentElement.parentElement.remove()
    };
    if (document.body.scrollWidth > 900) {
        document.querySelectorAll('div.whatsapp a').forEach(e => e.href = "https://web." + e.href.split(".").slice(1).join('.'))
    }
    (function(jQuery) {
        jQuery.fn.openPopup = function(settings) {
            var elem = jQuery(this);
            var settings = jQuery.extend({
                anim: 'fade'
            }, settings);
            elem.show();
            elem.find('.popup-content').addClass(settings.anim + 'In');
        }
        jQuery.fn.closePopup = function(settings) {
            var elem = jQuery(this);
            var settings = jQuery.extend({
                anim: 'fade'
            }, settings);
            elem.find('.popup-content').removeClass(settings.anim + 'In').addClass(settings.anim + 'Out');
            setTimeout(function() {
                elem.hide();
                elem.find('.popup-content').removeClass(settings.anim + 'Out')
            }, 500);
        }
    }(jQuery));
    jQuery('.quick-view-btn').click(function() {
        jQuery('body').addClass('sv-popup-open');
        jQuery('.quick-view-loader').show();
        jQuery('.popup-content').hide();
        jQuery('.loader_page').show();
        var productId = jQuery(this).data('product_id');
        var nonce = sv_ajax.nonce;
        jQuery.ajax({
            url: sv_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'quick_view_product',
                product_id: productId,
                security: nonce
            },
            success: function(response) {
                if (response) {
                    jQuery('.quick-view-loader').hide();
                    jQuery('.popup-content').show();
                    jQuery('.popup-content').html(response.data.html);
                    jQuery('.loader_page').hide();
                    jQuery('.slider-for').slick({
                        slidesToShow: 1,
                        slidesToScroll: 1,
                        autoplay: true,
                        autoplaySpeed: 5000,
                        arrows: false,
                        fade: true,
                        asNavFor: '.slider-nav'
                    });
                    jQuery('.slider-nav').slick({
                        slidesToShow: 4,
                        slidesToScroll: 1,
                        asNavFor: '.slider-for',
                        autoplay: true,
                        autoplaySpeed: 5000,
                        dots: false,
                        arrows: true,
                        centerMode: false,
                        focusOnSelect: true
                    });
                    jQuery('.close-popup').click(function() {
                        jQuery('#' + jQuery(this).data('id')).closePopup({
                            anim: (!jQuery(this).attr('data-animation') || jQuery(this).data('animation') == null) ? 'fade' : jQuery(this).data('animation')
                        });
                    });
                }
            },
            error: function(xhr, status, error) {}
        });
        jQuery('#' + jQuery(this).data('id')).openPopup({
            anim: (!jQuery(this).attr('data-animation') || jQuery(this).data('animation') == null) ? 'fade' : jQuery(this).data('animation')
        });
        jQuery('.close-popup').click(function() {
            jQuery('body').removeClass('sv-popup-open');
            jQuery('#' + jQuery(this).data('id')).closePopup({
                anim: (!jQuery(this).attr('data-animation') || jQuery(this).data('animation') == null) ? 'fade' : jQuery(this).data('animation')
            });
        });
    });
    jQuery(document).on("click", ".quick-view-close", function() {
        jQuery('body').removeClass('sv-popup-open');
        jQuery("#quickViewPopup").hide();
        jQuery(".overlay").hide();
    });
    jQuery(document).on("click", ".close-notification", function() {
        jQuery(".ersrv-notification").fadeOut();
    });
    $ = function(id) {
        return document.getElementById(id);
    }
    var show = function(id) {
        $(id).style.display = 'block';
    }
    var hide = function(id) {
        $(id).style.display = 'none';
    }
    jQuery('.review-slider').each(function() {
        var $slider = jQuery(this);
        $slider.find('.slider-box').filter(function() {
            return jQuery(this).find('.sv-product-detail').children().length === 0;
        }).remove();
        $slider.slick({
            dots: true,
            arrows: true,
            infinite: true,
            autoplay: true,
            autoplaySpeed: 5000,
            rows: 0,
            slidesToShow: 3,
            slidesToScroll: 1,
            responsive: [{
                    breakpoint: 1024,
                    settings: {
                        dots: true,
                        arrows: false,
                        slidesToShow: 2
                    }
                },
                {
                    breakpoint: 480,
                    settings: {
                        dots: true,
                        arrows: false,
                        slidesToShow: 1
                    }
                }
            ]
        });
    });
    jQuery('.slider-for').slick({
        slidesToShow: 1,
        slidesToScroll: 1,
        arrows: false,
        autoplay: true,
        autoplaySpeed: 5000,
        fade: true,
        asNavFor: '.slider-nav'
    });
    jQuery('.slider-nav').slick({
        slidesToShow: 4,
        slidesToScroll: 1,
        asNavFor: '.slider-for',
        autoplay: true,
        autoplaySpeed: 5000,
        dots: false,
        arrows: true,
        centerMode: false,
        focusOnSelect: true
    });
    jQuery('.quick-view-popup').on('click', function() {
        jQuery('.slider-for').slick('refresh');
        jQuery('.slider-nav').slick('refresh');
        jQuery('.overlay').addClass('shown');
    });
    jQuery('.popup-close').on('click', function() {
        jQuery('.overlay').removeClass('shown');
    });
    jQuery('.sv-product-slider').slick({
        dots: true,
        arrows: false,
        infinite: true,
        autoplay: true,
        autoplaySpeed: 5000,
        slidesToShow: 4,
        slidesToScroll: 4,
        responsive: [{
                breakpoint: 1024,
                settings: {
                    dots: true,
                    arrows: false,
                    slidesToShow: 2,
                    slidesToScroll: 2
                }
            },
            {
                breakpoint: 992,
                settings: {
                    dots: true,
                    arrows: false,
                    slidesToShow: 2,
                    slidesToScroll: 2
                }
            },
            {
                breakpoint: 575,
                settings: {
                    dots: true,
                    arrows: false,
                    slidesToShow: 1,
                    slidesToScroll: 1
                }
            }
        ]
    });
    jQuery('.footer-img-category-slider').slick({
        dots: false,
        arrows: false,
        infinite: true,
        autoplay: true,
        autoplaySpeed: 5000,
        slidesToShow: 4,
        slidesToScroll: 1,
        responsive: [{
                breakpoint: 575,
                settings: {
                    slidesToShow: 3,
                    slidesToScroll: 1
                }
            },
            {
                breakpoint: 430,
                settings: {
                    slidesToShow: 2,
                    slidesToScroll: 1
                }
            }
        ]
    });
    jQuery('.sv-product-tab-links').on('click', function() {
        jQuery('.tab-contents .sv-product-slider-tab').slick('refresh');
    });
    jQuery('.tab-contents .sv-product-slider-tab').slick({
        dots: false,
        arrows: false,
        infinite: false,
        autoplay: true,
        autoplaySpeed: 5000,
        slidesToShow: 4,
        slidesToScroll: 4,
        responsive: [{
                breakpoint: 1024,
                settings: {
                    dots: true,
                    arrows: false,
                    slidesToShow: 3,
                    slidesToScroll: 3
                }
            },
            {
                breakpoint: 992,
                settings: {
                    dots: true,
                    arrows: false,
                    slidesToShow: 2,
                    slidesToScroll: 2
                }
            },
            {
                breakpoint: 575,
                settings: {
                    dots: true,
                    arrows: false,
                    slidesToShow: 1,
                    slidesToScroll: 1
                }
            }
        ]
    });
    jQuery('.page-banner-slider').slick({
        dots: false,
        infinite: false,
        autoplay: true,
        autoplaySpeed: 5000,
        arrows: false,
        speed: 300,
        slidesToShow: 1
    });
    jQuery(window).scroll(function() {
        if (jQuery(this).scrollTop() > 60) {
            jQuery('.main-nav').addClass('sticky');
            jQuery('.announcement-bar').addClass('fix-announcement-bar');
        } else {
            jQuery('.main-nav').removeClass('sticky');
            jQuery('.announcement-bar').removeClass('fix-announcement-bar');
        }
    });

    function AddReadMore() {
        var carLmt = 90;
        var readMoreTxt = " ...read more";
        var readLessTxt = " read less";
        jQuery(".add-read-more").each(function() {
            if (jQuery(this).find(".first-section").length)
                return;
            var allstr = jQuery(this).text();
            if (allstr.length > carLmt) {
                var firstSet = allstr.substring(0, carLmt);
                var secdHalf = allstr.substring(carLmt, allstr.length);
                var strtoadd = firstSet + "<span class='second-section'>" + secdHalf + "</span><span class='read-more'  title='Click to Show More'>" + readMoreTxt + "</span><span class='read-less' title='Click to Show Less'>" + readLessTxt + "</span>";
                jQuery(this).html(strtoadd);
            }
        });
        jQuery(document).on("click", ".read-more,.read-less", function() {
            jQuery(this).closest(".add-read-more").toggleClass("show-less-content show-more-content");
        });
    }
    AddReadMore();
    jQuery(document).on("click", ".quick-cart", function() {
        var productId = jQuery(this).data('product_id');
        var nonce = jQuery('#quick_cart_nonce').val();
        jQuery('.pre-loader_page').show();
        jQuery('body').addClass('sv-popup-open');
        jQuery.ajax({
            url: sv_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'quick_cart_action',
                product_id: productId,
                nonce: nonce
            },
            success: function(response) {
                var cartQuantity = response.data.cart_quantity;
                jQuery('.cart-counter').text(cartQuantity);
                jQuery('.pre-loader_page').hide();
                jQuery('body').removeClass('sv-popup-open');
                jQuery('.header-cart .elementor-icon').trigger("click");
                jQuery('.widget_shopping_cart_content').html(response.data.mini_cart);
            },
            error: function(xhr, status, error) {}
        });
    });
    jQuery('#ajax_add_to_cart_form').on('submit', function(e) {
        e.preventDefault();
        jQuery('.pre-loader_page').show();
        jQuery('body').addClass('sv-popup-open');
        var form = jQuery(this);
        var product_id = form.find('button[name="add-to-cart"]').val();
        var quantity = form.find('input[name="quantity"]').val();
        jQuery.ajax({
            url: sv_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'woocommerce_ajax_add_to_cart',
                product_id: product_id,
                quantity: quantity,
            },
            success: function(response) {
                if (response.error) {
                    alert(response.error);
                } else {
                    var cartQuantity = response.data.cart_quantity;
                    jQuery('.cart-counter').text(cartQuantity);
                    jQuery('.pre-loader_page').hide();
                    jQuery('body').removeClass('sv-popup-open');
                    jQuery('.header-cart .elementor-icon').trigger("click");
                    jQuery('.widget_shopping_cart_content').html(response.data.mini_cart);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
            }
        });
    });
    jQuery(document).on("click", ".header-cart a", function(e) {
        e.preventDefault();
        jQuery('.pre-loader_page').show();
        jQuery('.widget_shopping_cart_content').html('<p class="loading">Loading...</p>');
        jQuery.ajax({
            url: sv_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'render_minicart_data'
            },
            success: function(response) {
                if (response.success) {
                    jQuery('.pre-loader_page').hide();
                    jQuery('.widget_shopping_cart_content').html(response.data.mini_cart);
                } else {
                    jQuery('.widget_shopping_cart_content').html('<p class="error">Failed to load cart data.</p>');
                }
            },
            error: function(xhr, status, error) {
                jQuery('.widget_shopping_cart_content').html('<p class="error">An error occurred while loading the cart.</p>');
            }
        });
    });
    var $filterIcon = jQuery('.filter-icon');
    var $productCatFilters = jQuery('.product-cat-filters');
    $filterIcon.on('click', function() {
        $productCatFilters.toggle();
    });
    jQuery(document).on("click", ".remove_from_cart_button", function() {
        setTimeout(function() {
            jQuery('.ersrv-notification-wrapper .toast').removeClass('show').addClass('hide');
            jQuery.ajax({
                url: sv_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'remove_minicart_action'
                },
                success: function(response) {
                    var cartQuantity = response.data.cart_quantity;
                    jQuery('.cart-counter').text(cartQuantity);
                },
                error: function(xhr, status, error) {}
            });
        }, 2000);
    });
    jQuery('.quick-view-popup-qty').on('input', function() {
        var quantity = parseInt(jQuery(this).val());
        if (quantity >= 1) {
            jQuery('.quick-view-add-to-cart').prop('disabled', false).removeClass('disabled');
        } else {
            jQuery('.quick-view-add-to-cart').prop('disabled', true).addClass('disabled');
        }
    });
    jQuery(document).on("click", ".quick-view-add-to-cart", function() {
        var productId = jQuery(this).data('product_id');
        var nonce = sv_ajax.quick_view_add_to_cart_nonce;
        jQuery(this).text('Adding to cart..');
        var quantity = parseInt(jQuery('.quick-view-popup-qty').val());
        var selected_variation_id = jQuery("#flavours").val();
        jQuery.ajax({
            url: sv_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'quick_view_add_to_cart_action',
                product_id: productId,
                quantity: quantity,
                variation_id: selected_variation_id,
                security: nonce
            },
            success: function(response) {
                jQuery('.quick-view-add-to-cart').text('Add to cart');
                jQuery('.header-cart .elementor-icon').trigger("click");
                jQuery('#quickViewPopup').hide();
                jQuery('body').removeClass('sv-popup-open');
                jQuery('#overlay').css('display','none');
                // showSuccessMessage('Product added successfully to cart');
            },
            error: function(xhr, status, error) {}
        });
    });
    jQuery(document).on('click', 'button.plus, button.minus', function() {
        var quantity = parseInt(jQuery('.quick-view-popup-qty').val());
        if (quantity >= 1) {
            if (!jQuery('.stock').hasClass('out-of-stock')) {
                jQuery('.quick-view-add-to-cart').prop('disabled', false).removeClass('disabled');
            } else {
                jQuery('.quick-view-add-to-cart').prop('disabled', true).addClass('disabled');
            }
        } else {
            jQuery('.quick-view-add-to-cart').prop('disabled', true).addClass('disabled');
        }
        var quantityContainer = jQuery(this).parent('.quantity');
        var qty = quantityContainer.find('.qty');
        var val = parseFloat(qty.val());
        var min = parseFloat(qty.attr('min'));
        var max = parseFloat(qty.attr('max'));
        var step = parseFloat(qty.attr('step'));
        if (jQuery(this).is('.plus')) {
            if (!isNaN(max) && val >= max) {
                return;
            } else {
                qty.val(val + step);
            }
        } else {
            if (!isNaN(min) && val <= min) {
                return;
            } else {
                qty.val(val - step);
            }
        }
    });
    var ageVerifiedCookie = getCookie('age_verified');
    if (ageVerifiedCookie !== "true" || typeof ageVerifiedCookie === 'undefined') {
        jQuery('#hulk_age_verify').show();
        jQuery('body').addClass('sv-popup-open');
    }
    var subscribeModalCookie = getCookie('subscribe_modal');
    if (subscribeModalCookie !== "true" || typeof subscribeModalCookie === 'undefined') {
        if (jQuery('body').hasClass('home')) {
            if (ageVerifiedCookie == "true") {
                setTimeout(function() {
                    jQuery('.deal-popup').css('display', 'flex');
                    jQuery('body').addClass('sv-popup-open');
                }, 5000);
            }
        }
    }
    jQuery(document).on('click', '.deal-popup-close, .deal-popup .overlay', function() {
        jQuery('body').removeClass('sv-popup-open');
        jQuery('.deal-popup').css('display', 'none');
    });
    jQuery(document).on('click', '.checkout-prevent-popup-close, .checkout-prevent-popup .overlay', function() {
        jQuery('body').removeClass('sv-popup-open');
        jQuery('.checkout-prevent-popup').css('display', 'none');
    });
    jQuery('.btn_verified').on('click', function() {
        var verified = jQuery(this).data('verified');
        if (verified === true) {
            document.cookie = "age_verified=true; path=/";
            jQuery('#hulk_age_verify').hide();
            jQuery('body').removeClass('sv-popup-open');
            if (jQuery('body').hasClass('home')) {
                setTimeout(function() {
                    jQuery('.deal-popup').css('display', 'flex');
                    jQuery('body').addClass('sv-popup-open');
                }, 5000);
            }
        } else {
            window.location.href = sv_ajax.verify_age_disagree_btn;
        }
    });
});

function getCookie(name) {
    var value = "; " + document.cookie;
    var parts = value.split("; " + name + "=");
    if (parts.length == 2) return parts.pop().split(";").shift();
}
var tablinks = document.getElementsByClassName("sv-product-tab-links");
var tabcontents = document.getElementsByClassName("tab-contents");

function opentab(tabname) {
    for (tablink of tablinks) {
        tablink.classList.remove("active-link");
    }
    for (tabcontent of tabcontents) {
        tabcontent.classList.remove("active-tab");
    }
    event.currentTarget.classList.add("active-link");
    document.getElementById(tabname).classList.add("active-tab");
}
document.addEventListener("DOMContentLoaded", function() {
    var widgetTitles = document.querySelectorAll('.product-cat-filters .widget-wrap .widget-title');
    widgetTitles.forEach(function(title) {
        var form = title.nextElementSibling;
        form.style.display = "block";
        title.addEventListener('click', function() {
            if (form.style.display === "none" || form.style.display === "") {
                form.style.display = "block";
                title.classList.add('open');
                title.classList.remove('close');
            } else {
                form.style.display = "none";
                title.classList.add('close');
                title.classList.remove('open');
            }
        });
    });
});