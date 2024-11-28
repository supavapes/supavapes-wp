jQuery(document).ready(function($) {
    // Event listener for image editing
    $(document).on('click', '.edit-image', function(e) {
        e.preventDefault();
        const productId = $(this).data('product-id');
        openMediaLibrary(productId);
    });

    // Event listener for gallery editing
    $(document).on('click', '.edit-gallery', function(e) {
        e.preventDefault();
        e.stopPropagation(); // Verhindert, dass das Ereignis zum edit-image Ereignis weitergeleitet wird
        const productId = $(this).data('product-id');
        openGalleryEditor(productId);
    });

    function openMediaLibrary(productId) {
        const frame = wp.media({
            title: 'Select or Upload Image',
            button: { text: 'Use this image' },
            multiple: false
        });

        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            updateProductImage(productId, attachment.id);
        });

        frame.open();
    }

    function openGalleryEditor(productId) {
        const frame = wp.media({
            title: 'Edit Product Gallery',
            button: { text: 'Update gallery' },
            multiple: true,
            library: { type: 'image' }
        });

        // Set to existing gallery
        frame.on('open', function() {
            var selection = frame.state().get('selection');
            getExistingGalleryAttachments(productId, function(attachments) {
                attachments.forEach(function(attachmentId) {
                    selection.add(wp.media.attachment(attachmentId));
                });
            });
        });

        frame.on('select', function() {
            var imageIDs = frame.state().get('selection').map(function(attachment) {
                return attachment.id;
            });
            updateProductGallery(productId, imageIDs);
        });

        frame.open();
    }

    function getExistingGalleryAttachments(productId, callback) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_product_gallery',
                product_id: productId
            },
            success: function(response) {
                if (response.success) {
                    callback(response.data);
                } else {
                    console.error('Failed to get existing gallery:', response.data);
                    callback([]);
                }
            },
            error: function() {
                console.error('Ajax request failed');
                callback([]);
            }
        });
    }

    function updateProductImage(productId, attachmentId) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'update_product_image',
                product_id: productId,
                attachment_id: attachmentId
            },
            success: response => {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Failed to update image.');
                }
            }
        });
    }

    function updateProductGallery(productId, imageIDs) {
        console.log('Updating gallery for product:', productId, 'with images:', imageIDs);
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'update_product_gallery',
                product_id: productId,
                gallery_ids: imageIDs
            },
            success: function(response) {
                console.log('Server response:', response);
                if (response.success) {
                    location.reload();
                } else {
                    alert('Failed to update gallery: ' + (response.data || 'Unknown error'));
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error:', textStatus, errorThrown);
                console.log('Full error object:', jqXHR);
                alert('Failed to update gallery. Please check the console for more details.');
            }
        });
    }


});