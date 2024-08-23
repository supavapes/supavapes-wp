jQuery(document).ready(function() {
	jQuery('#deal-popup-video').get(0).play();

	jQuery('.button').on('click', function() {
		var $this = jQuery(this);
	
		setTimeout(function() {
			$this.blur(); // Remove focus from the button
	
			// Create a clone of the element
			var clone = $this.clone(true);
			
			// Replace the original element with the clone
			$this.replaceWith(clone);
		}, 1000); // Ensure this happens after the click event
	});

	// });
	jQuery(".supa-deals-share").click(function() {
        jQuery(this).siblings(".share-option").toggleClass("active");
    });

    const $tableResponsive = jQuery('.vpe_table_responsive');
    const $containerBtn = jQuery('.vpe_container_btn');

    if ($tableResponsive.length) {
        const observerOptions = {
            root: null,
            rootMargin: '0px',
            threshold: 0.01 // Trigger when 1% of the element is in the viewport
        };

        const observerCallback = (entries, observer) => {
            entries.forEach(entry => {
                // console.log('Intersection Observer Entry:', entry); // Log entry details
                if (entry.isIntersecting) {
                    $tableResponsive.addClass('in-view');
                    $containerBtn.addClass('in-view-btn');
                } else {
                    $tableResponsive.removeClass('in-view');
                    $containerBtn.removeClass('in-view-btn');
                }
            });
        };

        const observer = new IntersectionObserver(observerCallback, observerOptions);

        observer.observe($tableResponsive[0]);
    } else {
    }
	jQuery('.joke-slider').slick({
		infinite: true,
		slidesToShow:1,
		slidesToScroll: 1,
		dots: true,
		arrows: false,
		autoplay: true,
		autoplaySpeed: 3000,
	  });
	  jQuery('.sv-active-offer-slider').slick({
		infinite: true,
		slidesToShow:1,
		slidesToScroll: 1,
		dots: true,
		arrows: false,
		autoplay: true,
		autoplaySpeed: 5000,
	  });

	  jQuery('.related-posts .related-blog-wrap').slick({
		infinite: false,
		slidesToShow:3,
		slidesToScroll: 1,
		dots: true,
		arrows: false,
		autoplay: false,
		autoplaySpeed: 5000,
		responsive: [
			{
			  breakpoint: 1024,
			  settings: {
				slidesToShow: 2,
				slidesToScroll: 1
			  }
			},
			{
			  breakpoint: 575,
			  settings: {
				slidesToShow: 1,
				slidesToScroll: 1
			  }
			}
		  ]
		
	  });


	//Shop page
	var widgetTitles = jQuery('.product-cat-listing .widget-wrap .widget-title');

    // Function to toggle category list visibility
    function toggleCategoryList(categoryList) {
        if (categoryList.css('display') === "none" || categoryList.css('display') === "") {
            categoryList.css('display', 'block');
        } else {
            categoryList.css('display', 'none');
        }
    }

    // Event listener for widget titles
    widgetTitles.each(function() {
        var title = jQuery(this);
        var categoryList = title.next();

        // Hide category list initially on small screens
        if (jQuery(window).width() <= 768) {
            // Show/hide category list on click
            title.on('click', function() {
                toggleCategoryList(categoryList);
            });
            categoryList.hide(); // use jQuery hide() method instead of css('display', 'none')
        }
    });
	// Quickview popup
	const quickViewBtns = jQuery(".quick-view-popup");
    const overlay = jQuery("#overlay");
    const quickViewPopup = jQuery("#quickViewPopup");

    quickViewBtns.each(function() {
        jQuery(this).on("click", function() {
            overlay.css("display", "block");
            quickViewPopup.css("display", "block");
        });
    });

    overlay.on("click", function() {
        overlay.css("display", "none");
        quickViewPopup.css("display", "none");
    });

	var currentUrl = window.location.href;
	var baseUrl = window.location.origin;

	jQuery('.my-account-user a').each(function() {
		var linkUrl = jQuery(this).attr('href');
		var fullLinkUrl = new URL(linkUrl, baseUrl).href;

		if (currentUrl === fullLinkUrl) {
			jQuery(this).parent().addClass('active');
		}
	});

	var themeSwitcherInput = jQuery('#theme_switch');
    var body = jQuery('body');

    // Function to toggle theme mode
    function toggleTheme(isDark) {
        if (isDark) {
            body.removeClass('light-theme').addClass('dark-theme');
            themeSwitcherInput.prop('checked', true);
            localStorage.setItem('theme', 'dark');
        } else {
			// console.log('lidedasdasda');
            body.removeClass('dark-theme').addClass('light-theme');
            themeSwitcherInput.prop('checked', false);
            localStorage.setItem('theme', 'light');
        }
    }

    // Toggle theme mode based on checkbox change
    themeSwitcherInput.change(function() {
        toggleTheme(themeSwitcherInput.prop('checked'));
    });

    // Detect system preference and set initial theme
    var storedTheme = localStorage.getItem('theme');
	// console.log(storedTheme);
    var isDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;

    if (storedTheme === 'dark' || (isDarkMode && storedTheme !== 'light') || storedTheme === null) {
		// console.log('in if');
        toggleTheme(true);
    } else {
		// console.log('in else');
        toggleTheme(false);
    }


	// Multi selectbox

   // Toggle options container visibility
jQuery(document).on('click', '.multi-select-box', function() {
    jQuery(this).find('.options-container').toggle();
});

// Handle checkbox change event
jQuery(document).on('change', '.multi-select-box .option input[type="checkbox"]', function() {
    updateSelectedItems();
});

// Handle remove item click
jQuery(document).on('click', '.selected .remove', function(e) {
    e.stopPropagation();
    var value = jQuery(this).data('value');
    jQuery('.option input[value="' + value + '"]').prop('checked', false);
    updateSelectedItems();
});

// Function to update selected items display
function updateSelectedItems() {
    var selectedItems = [];
    jQuery('.multi-select-box .option input[type="checkbox"]:checked').each(function() {
        selectedItems.push({
            value: jQuery(this).val(),
            text: jQuery(this).siblings('label').text()
        });
    });

    var selectedItemsHtml = '';
    if (selectedItems.length > 0) {
        selectedItems.forEach(function(item) {
            selectedItemsHtml += `
                <div class="selected">
                    ${item.text}
                    <span class="remove" data-value="${item.value}">&times;</span>
                </div>`;
        });
    } else {
        selectedItemsHtml = '<span class="placeholder">Select options...</span>';
    }
    jQuery('.multi-select-box .selected-items').html(selectedItemsHtml);
}

    jQuery(document).click(function(event) {
        if (!jQuery(event.target).closest('.multi-select-box').length) {
            jQuery('.options-container').hide();
        }
    });
    

    function createNewUploadBox() {
        return `
            <div class="customer-support-img-box customer-support-img-input-box">
                <div class="upload-icon">
                    <img decoding="async" src="/wp-content/uploads/2024/07/upload.png" alt="Upload Icon" class="uploadImgIcon">
                    <input type="file" class="fileImgInput" accept="image/*" style="display: none;">
                </div>
            </div>
        `;
    }
    
    function handleFileChange($input) {
        const file = $input[0].files[0];
        if (file) {
            const reader = new FileReader();
            const $box = $input.closest('.customer-support-img-box');
            let $img = $box.find('.customer-support-img');
    
            reader.onload = function(event) {
                if ($img.length === 0) {
                    // If there's no image tag, create one
                    $box.append('<img class="customer-support-img" height="150" width="150">');
                    $img = $box.find('.customer-support-img');
                }
                $img.attr('src', event.target.result);
            };
    
            reader.readAsDataURL(file);
    
            // Check if this is the third input
            if ($box.hasClass('customer-support-img-input-box')) {
                // Remove the input box class from the current box
                $box.removeClass('customer-support-img-input-box');
                
                // Add remove icon
                $box.append('<div class="remove-icon">x</div>');
    
                // Add a new upload input box
                jQuery('.upload-img-list').append(createNewUploadBox());
            }
        }
    }
    
    // Delegated event for dynamically added elements
    jQuery('.upload-img-list').on('click', '.uploadImgIcon', function() {
        jQuery(this).siblings('.fileImgInput').click();
    });
    
    jQuery('.upload-img-list').on('change', '.fileImgInput', function() {
        handleFileChange(jQuery(this));
    });
    
    jQuery('.upload-img-list').on('click', '.remove-icon', function() {
        jQuery(this).closest('.customer-support-img-box').remove();
    });
	
	 
});