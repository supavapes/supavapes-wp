<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'bulk-common-functions.php';
require_once plugin_dir_path(__FILE__) . 'stockmanagement.php';

// Am Anfang der Datei nach den requires
function verify_bulk_editor_nonce() {
    if (!isset($_REQUEST['_wpnonce'])) {
        wp_send_json_error(['message' => __('Security check failed.', 'final-pos')]);
        exit;
    }
    
    $nonce = sanitize_text_field(wp_unslash($_REQUEST['_wpnonce']));
    if (!wp_verify_nonce($nonce, 'bulk_editor_nonce')) {
        wp_send_json_error(['message' => __('Security check failed.', 'final-pos')]);
        exit;
    }
}
/* Publishing in Version 1.2.0 
function register_bulk_editor_submenu() {
    add_submenu_page(
        'edit.php?post_type=product',
        __('Bulk Editor', 'final-pos'),
        __('Bulk Editor', 'final-pos'),
        'manage_woocommerce',
        'bulk-editor',
        'render_bulk_editor_page'
    );
}
add_action('admin_menu', 'register_bulk_editor_submenu');*/

function get_products_per_page() {
    $default_per_page = 20;
    
    if (!isset($_GET['per_page']) || !current_user_can('manage_woocommerce')) {
        return $default_per_page;
    }
    
    if (!wp_verify_nonce(sanitize_key($_GET['_wpnonce'] ?? ''), 'bulk_editor_nonce')) {
        return $default_per_page;
    }
    
    $per_page = sanitize_text_field(wp_unslash($_GET['per_page']));
    return max(5, min(100, intval($per_page)));
}

function render_bulk_editor_page() {
    if (!current_user_can('manage_woocommerce')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'final-pos'));
    }
    
    wp_enqueue_style('bulk-editor-css', plugins_url('../../../assets/css/woo/bulkeditor.css', __FILE__), [], '1.0.0');
    wp_enqueue_script('bulk-editor-js', plugins_url('../../../assets/js/woo/bulkeditor/bulkeditor.js', __FILE__), ['jquery', 'select2'], '1.0.0', true);
    wp_enqueue_script('bulk-editor-view-management-js', plugins_url('../../../assets/js/woo/bulkeditor/view-management.js', __FILE__), ['jquery', 'bulk-editor-js'], '1.0.0', true);
    wp_enqueue_script('bulk-editor-filters-js', plugins_url('../../../assets/js/woo/bulkeditor/filters.js', __FILE__), ['jquery', 'bulk-editor-js'], '1.0.0', true);
    wp_enqueue_script('bulk-editor-popup-js', plugins_url('../../../assets/js/woo/bulkeditor/popup.js', __FILE__), ['jquery', 'bulk-editor-js'], '1.0.0', true);
    wp_enqueue_script('bulk-editor-image-management-js', plugins_url('../../../assets/js/woo/bulkeditor/image-management.js', __FILE__), ['jquery', 'bulk-editor-js', 'media-upload'], '1.0.0', true);
    
    wp_enqueue_media();
    wp_enqueue_editor();
    // WooCommerce Admin Styles und Scripts
    wp_enqueue_style('woocommerce_admin_styles');
    wp_enqueue_script('select2');
    
    wp_localize_script('bulk-editor-js', 'bulkEditorTranslations', [
        'variations' => esc_html__('Variations', 'final-pos'),
        'variationDataSaved' => esc_html__('Variation data saved successfully.', 'final-pos'),
        'errorSavingVariationData' => esc_html__('Error saving variation data: ', 'final-pos'),
        'errorLoadingVariationData' => esc_html__('Error loading variation data: ', 'final-pos'),
        'anErrorOccurred' => esc_html__('An error occurred. Please try again. Error: ', 'final-pos'),
        'customFields' => $custom_fields,
        'nonce' => wp_create_nonce('bulk_editor_nonce'),
        // Add any other data you need to pass to JavaScript
    ]);
    wp_enqueue_style('material-symbols-outlined', 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined', [], '1.0.0');

    // Pagination parameters are not security-critical in admin context
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $paged = isset($_GET['paged']) ? max(1, intval(sanitize_text_field(wp_unslash($_GET['paged'])))) : 1;
    $products_per_page = get_products_per_page();

    $products = new WP_Query([
        'post_type' => 'product',
        'posts_per_page' => $products_per_page,
        'paged' => $paged
    ]);

    $total_products = $products->found_posts;
    $total_pages = ceil($total_products / $products_per_page);

    $custom_columns = get_option('bulk_editor_custom_columns', []);

    echo '<div class="wrap">';
    echo '<h1 class="wp-heading-inline">' . esc_html__('Bulk Editor', 'final-pos') . '</h1>';
    echo '<a href="#" id="save-all" class="page-title-action">' . esc_html__('Save All', 'final-pos') . '</a>';
    echo '<a href="' . esc_url(admin_url('post-new.php?post_type=product')) . '" class="page-title-action">' . esc_html__('Create Product', 'final-pos') . '</a>';
    echo '<hr class="wp-header-end">';
    echo '
        <div class="filter-bar">
            <button class="add-custom-column" title="' . esc_attr__('Add Custom Column', 'final-pos') . '">
                <span class="material-symbols-outlined">add_column_right</span>
            </button>
            <div class="view-selector">
                <select class="view-select">
                    <option value="regular">' . esc_html__('Main View', 'final-pos') . '</option>
                    <option value="description">' . esc_html__('Content', 'final-pos') . '</option>
                    <option value="show-all">' . esc_html__('Show All', 'final-pos') . '</option>
                </select>
            </div>
            <div class="filters">
            <select id="bulk-actions-selector" style="display:none;">
                <option value="">' . esc_html__('Bulk Actions', 'final-pos') . '</option>
                <option value="dummy-action">' . esc_html__('Dummy Action', 'final-pos') . '</option>
            </select>
                <select class="filter-category">
                    <option value="all-categories">' . esc_html__('All Categories', 'final-pos') . '</option>';
                    foreach (get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]) as $category) {
                        echo '<option value="' . esc_attr($category->term_id) . '">' . esc_html($category->name) . '</option>';
                    }
    echo '
                </select>
                <select class="filter-status">
                    <option value="all-statuses">' . esc_html__('All Statuses', 'final-pos') . '</option>
                    <option value="publish">' . esc_html__('Published', 'final-pos') . '</option>
                    <option value="draft">' . esc_html__('Draft', 'final-pos') . '</option>
                </select>
                <select class="filter-stock">
                    <option value="all-stock">' . esc_html__('All Stock', 'final-pos') . '</option>
                    <option value="in-stock">' . esc_html__('In Stock', 'final-pos') . '</option>
                    <option value="out-of-stock">' . esc_html__('Out of Stock', 'final-pos') . '</option>
                    <option value="on-backorder">' . esc_html__('On Backorder', 'final-pos') . '</option>
                </select>
            </div>
            <div class="toggle-container">
                <label for="edit-all-toggle" class="edit-all-label">' . esc_html__('Edit all', 'final-pos') . '</label>
                <label class="switch">
                    <input type="checkbox" id="edit-all-toggle" class="edit-all-toggle">
                    <span class="slider"></span>
                </label>
            </div>
            <div class="search-container">
                <input type="text" class="search-box" placeholder="' . esc_attr__('Search products...', 'final-pos') . '" />
            </div>
        </div>
             <div class="product-list">
            <table>
                <thead>
                    <tr>
                        <th class="checkbox-column"></th> <!-- Header for the checkbox column -->
                        <th class="name-column sticky">' . esc_html__('Name', 'final-pos') . ' <span class="sort-icon material-symbols-outlined">unfold_more</span></th>
                        <th class="category-column">' . esc_html__('Category', 'final-pos') . ' <span class="sort-icon material-symbols-outlined">unfold_more</span></th>
                        <th class="tags-column">' . esc_html__('Tags', 'final-pos') . ' <span class="sort-icon material-symbols-outlined">unfold_more</span></th>
                        <th class="description-column" style="display:none;">' . esc_html__('Description', 'final-pos') . ' <span class="sort-icon material-symbols-outlined">unfold_more</span></th>
                        <th class="short_description-column" style="display:none;">' . esc_html__('Short Description', 'final-pos') . ' <span class="sort-icon material-symbols-outlined">unfold_more</span></th>';
                        
                        if (defined('WPSEO_VERSION')) {
                            echo '<th class="focus-keyword-column" style="display:none;">' . esc_html__('SEO Keyword', 'final-pos') . ' <span class="sort-icon material-symbols-outlined">unfold_more</span></th>
                                  <th class="seo-title-column" style="display:none;">' . esc_html__('SEO Title', 'final-pos') . ' <span class="sort-icon material-symbols-outlined">unfold_more</span></th>
                                  <th class="meta-description-column" style="display:none;">' . esc_html__('SEO Meta', 'final-pos') . ' <span class="sort-icon material-symbols-outlined">unfold_more</span></th>';
                        }

                        foreach ($custom_columns as $custom_column) {
                            echo '<th class="custom-column" data-field="' . esc_attr($custom_column['field']) . '">' . esc_html($custom_column['label']) . '</th>';
                        }

    echo '            <th class="status-column">' . esc_html__('Status', 'final-pos') . ' <span class="sort-icon material-symbols-outlined">unfold_more</span></th>
                        <th class="source-column">' . esc_html__('Source', 'final-pos') . ' <span class="sort-icon material-symbols-outlined">unfold_more</span></th>
                        <th class="inventory-column">' . esc_html__('Stock', 'final-pos') . ' <span class="sort-icon material-symbols-outlined">unfold_more</span></th>
                        <th class="sku-column">' . esc_html__('SKU', 'final-pos') . ' <span class="sort-icon material-symbols-outlined">unfold_more</span></th>
                        <th class="price-column">' . esc_html__('Price', 'final-pos') . ' <span class="sort-icon material-symbols-outlined">unfold_more</span></th>
                        <th class="actions-column sticky always-visible"></th>
                    </tr>
                </thead>
                <tbody>';
    
    if ($products->have_posts()) {
        while ($products->have_posts()) {
            $products->the_post();
            $product = wc_get_product(get_the_ID());
            $is_variable = $product->is_type('variable');
            $categories = wp_get_post_terms(get_the_ID(), 'product_cat', ['fields' => 'all']);
            $tags = wp_get_post_terms(get_the_ID(), 'product_tag', ['fields' => 'all']);
            $status = get_post_status(get_the_ID());
            $stock = $product->get_stock_quantity();
            $sku = $product->get_sku();
            $price_html = $product->get_price_html();
            $thumbnail_url = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail') ?: wc_placeholder_img_src();
            $description = get_post_field('post_content', get_the_ID());
            $short_description = $product->get_short_description();
    
            echo '<tr class="product-row" data-product-id="' . esc_attr(get_the_ID()) . '" data-edited="false">';
            echo '<td>';
            echo '<input type="checkbox" class="product-checkbox" data-product-id="' . esc_attr(get_the_ID()) . '">';
            echo '</td>';
            echo '<td class="name-column">';
            echo '<div class="name-column-content">';
            echo '<div class="image-container" style="position: relative;">'; // Neuer Container für das Bild
            echo '<a href="#" class="edit-image" data-product-id="' . esc_attr(get_the_ID()) . '"><img src="' . esc_url($thumbnail_url) . '" alt="' . esc_attr(get_the_title()) . '" class="product-image"></a>';
            echo '<span class="edit-gallery material-symbols-outlined" style="position: absolute; top: -5px; right: 10px; border-radius: 50%; padding: 2px; cursor: pointer; border: 1px solid var(--uxlabs-input-bg-color); font-size: 12px;" data-product-id="' . esc_attr(get_the_ID()) . '">collections</span>'; // Neues Galerie-Bearbeitungssymbol
            echo '</div>'; // Schließt den image-container
            echo '<div class="title-container">';
            echo '<span class="title-display" title="' . esc_attr(get_the_title()) . '">' . esc_html(get_the_title()) . '</span>';
            echo '<input type="text" class="title-input hidden" value="' . esc_attr(get_the_title()) . '" />';
            echo '</div>';
            if ($is_variable) {
                echo '<span class="plus-button material-symbols-outlined">keyboard_arrow_down</span>';
            }
            echo '</div>';
            echo '</td>';
    
            echo '<td class="category-column">';
            echo '<span class="category-display">' . esc_html(implode(', ', wp_list_pluck($categories, 'name'))) . '</span>';
            echo '<select class="category-select hidden" data-product-id="' . esc_attr(get_the_ID()) . '" multiple="multiple">';
            foreach ($categories as $category) {
                echo '<option value="' . esc_attr($category->term_id) . '" selected>' . esc_html($category->name) . '</option>';
            }
            echo '</select></td>';
    
            echo '<td class="tags-column">';
            if (!empty($tags)) {
                echo '<span class="tags-display">' . esc_html(implode(', ', wp_list_pluck($tags, 'name'))) . '</span>';
            } else {
                echo '<span class="tags-display">' . esc_html(__('No Tags', 'final-pos')) . '</span>';
            }
            echo '<select class="tags-select hidden" data-product-id="' . esc_attr(get_the_ID()) . '" multiple="multiple">';
            foreach ($tags as $tag) {
                echo '<option value="' . esc_attr($tag->term_id) . '" selected>' . esc_html($tag->name) . '</option>';
            }
            echo '</select></td>';
    
            echo '<td class="description-column" style="display:none;">';
            echo '<span class="material-symbols-outlined edit-description-icon">edit_note</span>';
            echo '<span class="description-display">' . esc_html($description) . '</span>';
            echo '<input type="text" class="description-input hidden" value="' . esc_attr($description) . '" />';
            echo '</td>';
    
            echo '<td class="short_description-column" style="display:none;">';
            echo '<span class="material-symbols-outlined edit-description-icon">edit_note</span>';
            echo '<span class="short_description-display">' . esc_html($short_description) . '</span>';
            echo '<input type="text" class="short_description-input hidden" value="' . esc_attr($short_description) . '" />';
            echo '</td>';

            if (defined('WPSEO_VERSION')) {
                foreach (['_yoast_wpseo_focuskw' => 'focus-keyword', '_yoast_wpseo_title' => 'seo-title', '_yoast_wpseo_metadesc' => 'meta-description'] as $meta_key => $column_class) {
                    echo '<td class="' . esc_attr($column_class) . '-column" style="display:none;">';
                    $meta_value = esc_html(get_post_meta(get_the_ID(), $meta_key, true));
                    echo '<span class="' . esc_attr($column_class) . '-display">' . esc_html($meta_value) . '</span>';
                    echo '<input type="text" class="' . esc_attr($column_class) . '-input hidden" value="' . esc_attr($meta_value) . '" />';
                    echo '</td>';
                }
            }

            foreach ($custom_columns as $custom_column) {
                $custom_field = $custom_column['field'];
                $custom_value = get_post_meta(get_the_ID(), $custom_field, true);
                echo '<td class="custom-column" data-field="' . esc_attr($custom_field) . '">';
                echo '<span class="custom-field-display">' . esc_html($custom_value) . '</span>';
                echo '<input type="text" class="custom-field-input hidden" data-field="' . esc_attr($custom_field) . '" value="' . esc_attr($custom_value) . '">';
                echo '</td>';
            }
            
            echo '<td class="status-column">';
            $status_class = $status === 'publish' ? 'status-publish' : 'status-draft';
            $status_display = $status === 'publish' ? esc_html(__('Published', 'final-pos')) : esc_html(__('Draft', 'final-pos'));
            echo '<span class="status-display ' . esc_attr($status_class) . '" data-status="' . esc_attr($status) . '">' . esc_html($status_display) . '</span>';
            echo '<select class="status-select hidden" data-product-id="' . esc_attr(get_the_ID()) . '">
                    <option value="publish" ' . selected($status, 'publish', false) . '>' . esc_html(__('Published', 'final-pos')) . '</option>
                    <option value="draft" ' . selected($status, 'draft', false) . '>' . esc_html(__('Draft', 'final-pos')) . '</option>
                </select>';
            echo '</td>';
    
            echo '<td class="source-column">Woo</td>';
    
            echo '<td class="inventory-column">';
            echo '<span class="inventory-display" style="cursor: pointer;">' . esc_html(($stock !== null ? $stock : '∞')) . ' ' . esc_html(__('in stock', 'final-pos')) . '</span>';
            echo '<input type="hidden" class="stock-input" data-stock-quantity="' . esc_attr($stock) . '" data-stock-status="' . esc_attr($product->get_stock_status()) . '" data-manage-stock="' . esc_attr($product->get_manage_stock() ? 'yes' : 'no') . '" data-backorders="' . esc_attr($product->get_backorders()) . '">';
            echo '</td>';
    
            echo '<td class="sku-column">';
            echo '<span class="sku-display">' . esc_html($sku) . '</span>';
            echo '<input type="text" class="sku-input hidden" value="' . esc_attr($sku) . '" />';
            echo '</td>';
    
            echo '<td class="price-column">';
            if (!$is_variable) {
                $price = $product->get_price();
                if ($price !== '') {
                    echo '<span class="price-display">$' . esc_html(number_format((float)$price, 2)) . '</span>';
                    echo '<input type="number" class="price-input hidden" data-product-id="' . esc_attr(get_the_ID()) . '" value="' . esc_attr($price) . '" step="0.01" style="max-width: 105px;">';
                } else {
                    echo '<span class="price-display">' . esc_html(__('N/A', 'final-pos')) . '</span>';
                    echo '<input type="number" class="price-input hidden" data-product-id="' . esc_attr(get_the_ID()) . '" value="" step="0.01" style="max-width: 105px;">';
                }
            } else {
                echo '<span class="price-display">' . wp_kses_post($price_html) . '</span>';
            }
            echo '</td>';

            echo '<td class="actions-column always-visible">';
            echo '<span class="material-symbols-outlined more-options">more_horiz</span>';
            echo '<div class="actions-menu hidden">';
            echo '<a href="' . esc_url(get_permalink()) . '" target="_blank"><span class="material-symbols-outlined">visibility</span> ' . esc_html(__('View', 'final-pos')) . '</a>';
            echo '<a href="' . esc_url(get_edit_post_link()) . '"><span class="material-symbols-outlined">edit</span> ' . esc_html(__('Edit', 'final-pos')) . '</a>';
            echo '<a href="#" class="delete-product" data-product-id="' . esc_attr(get_the_ID()) . '"><span class="material-symbols-outlined">delete</span> ' . esc_html(__('Delete', 'final-pos')) . '</a>';
            echo '</div>';
            echo '</td>';
    
            echo '</tr>';
    
            if ($is_variable) {
                foreach ($product->get_available_variations() as $variation) {
                    $variation_obj = new WC_Product_Variation($variation['variation_id']);
                    $variation_stock = $variation_obj->get_stock_quantity();
                    $variation_price = $variation_obj->get_price();
                    $variation_sku = $variation_obj->get_sku();
                    $attributes = wc_get_formatted_variation($variation_obj, true);
                    $variation_thumbnail_url = wp_get_attachment_image_url($variation_obj->get_image_id(), 'thumbnail') ?: $thumbnail_url;
            
                    echo '<tr class="variant-row hidden" data-product-id="' . esc_attr($variation['variation_id']) . '">';
                    echo '<td>';
                    echo '<input type="checkbox" class="product-checkbox" data-product-id="' . esc_attr($variation['variation_id']) . '">';
                    echo '</td>';
                    echo '<td class="name-column">';
                    echo '<div class="name-column-content">';
                    echo '<div class="image-container" style="position: relative;">'; // Neuer Container für das Bild
                    echo '<a href="#" class="edit-image" data-product-id="' . esc_attr($variation['variation_id']) . '"><img src="' . esc_url($variation_thumbnail_url) . '" alt="' . esc_attr($attributes) . '" class="product-image"></a>';
                    echo '<span class="edit-gallery material-symbols-outlined" style="position: absolute; top: -5px; right: 10px; border-radius: 50%; padding: 2px; cursor: pointer; border: 1px solid var(--uxlabs-input-bg-color); font-size: 12px;" data-product-id="' . esc_attr($variation['variation_id']) . '">collections</span>'; // Neues Galerie-Bearbeitungssymbol
                    echo '</div>'; // Schließt den image-container
                    echo '<span class="variation-attributes" title="' . esc_attr($attributes) . '">' . esc_html($attributes) . '</span>';
                    echo '<span class="material-symbols-outlined" style="margin-left: auto;">more_vert</span>';
                    echo '</div>';
                    echo '</td>';
                    echo '<td class="category-column"></td>';
                    echo '<td class="tags-column"></td>';
                    echo '<td class="description-column" style="display:none;"></td>';
                    echo '<td class="short_description-column" style="display:none;"></td>';
            
                    foreach ($custom_columns as $custom_column) {
                        $custom_field = $custom_column['field'];
                        $custom_value = get_post_meta($variation['variation_id'], $custom_field, true);
                        echo '<td class="custom-column" data-field="' . esc_attr($custom_field) . '">';
                        echo $custom_value !== '' ? '<span class="custom-field-display">' . esc_html($custom_value) . '</span><input type="text" class="custom-field-input hidden" data-field="' . esc_attr($custom_field) . '" value="' . esc_attr($custom_value) . '">' : '<span class="custom-field-display">-</span>';
                        echo '</td>';
                    }

                    echo '<td class="status-column"></td>';
                    echo '<td class="source-column"></td>';
                    echo '<td class="inventory-column">';
                    echo '<span class="inventory-display" style="cursor: pointer;">' . esc_html(($variation_stock !== null ? $variation_stock : '∞')) . ' ' . esc_html(__('in stock', 'final-pos')) . '</span>';
                    echo '<input type="hidden" class="stock-input" data-stock-quantity="' . esc_attr($variation_stock) . '" data-stock-status="' . esc_attr($variation_obj->get_stock_status()) . '" data-manage-stock="' . esc_attr($variation_obj->get_manage_stock() ? 'yes' : 'no') . '" data-backorders="' . esc_attr($variation_obj->get_backorders()) . '">';
                    echo '</td>';
                    echo '<td class="sku-column">';
                    echo '<span class="sku-display">' . esc_html($variation_sku) . '</span>';
                    echo '<input type="text" class="sku-input hidden" value="' . esc_attr($variation_sku) . '" />';
                    echo '</td>';
                    echo '<td class="price-column">';
                    echo '<span class="price-display">$' . esc_html(number_format($variation_price, 2)) . '</span>';
                    echo '<input type="number" class="price-input hidden" data-product-id="' . esc_attr($variation['variation_id']) . '" value="' . esc_attr($variation_price) . '" step="0.01" style="max-width: 105px;">';
                    echo '</td>';
                    echo '<td class="actions-column">';
                    echo '<div class="actions-menu hidden">';
                    echo '<a href="' . esc_url(get_permalink($product->get_id())) . '" target="_blank"><span class="material-symbols-outlined">visibility</span> ' . esc_html(__('View', 'final-pos')) . '</a>';
                    echo '<a href="' . esc_url(get_edit_post_link($product->get_id())) . '"><span class="material-symbols-outlined">edit</span> ' . esc_html(__('Edit', 'final-pos')) . '</a>';
                    echo '<a href="#" class="delete-product" data-product-id="' . esc_attr($variation['variation_id']) . '"><span class="material-symbols-outlined">delete</span> ' . esc_html(__('Delete', 'final-pos')) . '</a>';
                    echo '</div>';
                    echo '</td>';
                    echo '</tr>';
                }
            }
        }
    } else {
        echo '<tr><td colspan="9">' . esc_html(__('No products found', 'final-pos')) . '</td></tr>';
    }
    wp_reset_postdata();
        
        echo '
                    </tbody>
                </table>
            </div>
        </div>
    ';

    echo '<div class="footer-navigation">';
    echo '<div class="tablenav-pages">';
    echo wp_kses_post(paginate_links([ // Escaping the output
        'base' => add_query_arg('paged', '%#%'),
        'format' => '',
        'prev_text' => esc_html__('&laquo; Previous', 'final-pos'),
        'next_text' => esc_html__('Next &raquo;', 'final-pos'),
        'total' => $total_pages,
        'current' => $paged
    ]));
    echo '</div>';
    echo '<div class="products-per-page">';
    echo '<form method="get">';
    echo '<input type="hidden" name="post_type" value="product">';
    echo '<input type="hidden" name="page" value="bulk-editor">';
    echo '<label for="per_page">' . esc_html(__('Products per page:', 'final-pos')) . '</label>';
    echo '<select name="per_page" id="per_page" onchange="this.form.submit()">';
    $options = [5, 20, 50, 100, 200, 500];
    foreach ($options as $option) {
        echo '<option value="' . esc_attr($option) . '"' . selected($products_per_page, $option, false) . '>' . esc_html($option) . '</option>';
    }
    echo '</select>';
    echo '</form>';
    echo '</div>';
    echo '</div>';

    echo '</div>';
    
    echo '
    <div id="popup-overlay" class="popup-overlay"></div>
    <div id="generic-popup" class="generic-popup">
        <div class="generic-popup-header">
            <h2 id="generic-popup-title"></h2>
            <span class="material-symbols-outlined generic-popup-close">close</span>
        </div>
        <div class="generic-popup-content">
            <!-- The content will be dynamically inserted -->
        </div>
        <div class="generic-popup-actions">
            <button class="cancel-button">' . esc_html(__('Cancel', 'final-pos')) . '</button>
            <button class="save-button">' . esc_html(__('Save', 'final-pos')) . '</button>
        </div>
    </div>';

    echo '
    <div id="custom-column-popup" class="generic-popup" style="display:none;">
        <div class="generic-popup-header">
            <h2>' . esc_html(__('Add Custom Columns', 'final-pos')) . '</h2>
            <span class="material-symbols-outlined generic-popup-close">close</span>
        </div>
        <div class="generic-popup-content">
            <div id="custom-columns-container"></div>
            <button id="add-custom-column-field" class="button">' . esc_html(__('Add Column', 'final-pos')) . '</button>
            <button id="get-custom-fields" class="button">' . esc_html(__('Get custom fields', 'final-pos')) . '</button>
        </div>
        <div class="generic-popup-actions">
            <button class="cancel-button">' . esc_html(__('Cancel', 'final-pos')) . '</button>
            <button class="save-button">' . esc_html(__('Save', 'final-pos')) . '</button>
        </div>
    </div>';

    render_stock_popup(); // Neue Zeile zum Rendern des Stock Popups

    $custom_fields = get_product_custom_fields();
    wp_localize_script('bulk-editor-js', 'bulkEditorData', [
        'customFields' => $custom_fields,
        'nonce' => wp_create_nonce('bulk_editor_nonce'),
        'ajaxurl' => admin_url('admin-ajax.php')
    ]);
}
function update_product_data() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'bulk_editor_nonce')) {
        wp_send_json_error(['message' => __('Security check failed.', 'final-pos')]);
        return;
    }

    // Sanitize custom fields
    $custom_fields = [];
    if (!empty($_POST['custom_fields'])) {
        // Sanitize the JSON string before unslashing
        $raw_json = sanitize_text_field(wp_unslash($_POST['custom_fields']));
        $raw_fields = json_decode($raw_json, true);
        if (is_array($raw_fields)) {
            foreach ($raw_fields as $key => $value) {
                $custom_fields[sanitize_text_field($key)] = sanitize_text_field($value);
            }
        }
    }

    // Sanitize variations
    $variations = [];
    if (!empty($_POST['variations'])) {
        // Sanitize the JSON string before unslashing
        $raw_json = sanitize_text_field(wp_unslash($_POST['variations']));
        $raw_variations = json_decode($raw_json, true);
        if (is_array($raw_variations)) {
            foreach ($raw_variations as $var_id => $var_data) {
                $sanitized_var_data = [];
                foreach ($var_data as $key => $value) {
                    if ($key === 'price') {
                        $sanitized_var_data[$key] = floatval($value);
                    } elseif ($key === 'stock') {
                        $sanitized_var_data[$key] = intval($value);
                    } elseif ($key === 'custom_fields' && is_array($value)) {
                        $sanitized_var_data[$key] = array_map('sanitize_text_field', $value);
                    } else {
                        $sanitized_var_data[$key] = sanitize_text_field($value);
                    }
                }
                $variations[intval($var_id)] = $sanitized_var_data;
            }
        }
    }

    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    if (!$product_id) {
        wp_send_json_error(['message' => __('Invalid product ID.', 'final-pos')]);
        return;
    }

    $product = wc_get_product($product_id);
    if (!$product) {
        wp_send_json_error(['message' => __('Product not found.', 'final-pos')]);
        return;
    }

    $data = [
        'categories' => isset($_POST['categories']) ? array_map('intval', $_POST['categories']) : [],
        'tags' => isset($_POST['tags']) ? array_map('intval', $_POST['tags']) : [],
        'price' => isset($_POST['price']) ? floatval(wp_unslash($_POST['price'])) : null,
        'status' => isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : '',
        'title' => isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '',
        'sku' => isset($_POST['sku']) ? sanitize_text_field(wp_unslash($_POST['sku'])) : '',
        'description' => isset($_POST['description']) ? wp_kses_post(wp_unslash($_POST['description'])) : '',
        'short_description' => isset($_POST['short_description']) ? wp_kses_post(wp_unslash($_POST['short_description'])) : '',
        'focus_keyword' => isset($_POST['focus_keyword']) ? sanitize_text_field(wp_unslash($_POST['focus_keyword'])) : '',
        'seo_title' => isset($_POST['seo_title']) ? sanitize_text_field(wp_unslash($_POST['seo_title'])) : '',
        'meta_description' => isset($_POST['meta_description']) ? sanitize_text_field(wp_unslash($_POST['meta_description'])) : '',
    ];

    wp_set_post_terms($product_id, $data['categories'], 'product_cat');
    wp_set_post_terms($product_id, $data['tags'], 'product_tag');

    if ($data['price'] !== null) {
        $product->set_regular_price($data['price']);
        $product->set_price($data['price']);
    }

    $product->set_name($data['title']);
    $product->set_status($data['status']);
    $product->set_description($data['description']);
    $product->set_short_description($data['short_description']);
    $product->set_sku($data['sku']);

    update_post_meta($product_id, '_yoast_wpseo_focuskw', $data['focus_keyword']);
    update_post_meta($product_id, '_yoast_wpseo_title', $data['seo_title']);
    update_post_meta($product_id, '_yoast_wpseo_metadesc', $data['meta_description']);

    // Update custom fields
    if (!empty($custom_fields)) {
        foreach ($custom_fields as $field => $value) {
            update_post_meta($product_id, sanitize_text_field($field), sanitize_text_field($value));
        }
    }

    $product->save();

    // Update variations
    if ($product->is_type('variable') && !empty($variations)) {
        foreach ($variations as $variation_id => $variation_data) {
            $variation = wc_get_product($variation_id);
            if ($variation && $variation->is_type('variation')) {
                if (isset($variation_data['price'])) {
                    $variation->set_regular_price($variation_data['price']); // Already sanitized as float
                    $variation->set_price($variation_data['price']); // Already sanitized as float
                }
                if (isset($variation_data['sku'])) {
                    $variation->set_sku($variation_data['sku']); // Already sanitized
                }
                if (isset($variation_data['stock'])) {
                    $variation->set_stock_quantity($variation_data['stock']); // Already sanitized as int
                }
                if (isset($variation_data['custom_fields'])) {
                    foreach ($variation_data['custom_fields'] as $field => $value) {
                        update_post_meta($variation_id, $field, $value); // Already sanitized
                    }
                }
                $variation->save();
            }
        }
    }

    wp_send_json_success(['message' => __('Product updated successfully.', 'final-pos')]);
}
add_action('wp_ajax_update_product_data', 'update_product_data');

function update_product_image() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'bulk_editor_nonce')) {
        wp_send_json_error(['message' => __('Security check failed.', 'final-pos')]);
        return;
    }
    
    if (!isset($_POST['product_id']) || !isset($_POST['attachment_id'])) {
        wp_send_json_error(['message' => __('Failed to update image.', 'final-pos')]);
        return;
    }

    $product_id = intval(wp_unslash($_POST['product_id']));
    $attachment_id = intval(wp_unslash($_POST['attachment_id']));
    
    if ($product_id && $attachment_id) {
        update_post_meta($product_id, '_thumbnail_id', $attachment_id);
        wp_send_json_success(['message' => __('Image updated successfully.', 'final-pos')]);
    } else {
        wp_send_json_error(['message' => __('Failed to update image.', 'final-pos')]);
    }
}
add_action('wp_ajax_update_product_image', 'update_product_image');

function delete_product() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'bulk_editor_nonce')) {
        wp_send_json_error(['message' => __('Security check failed.', 'final-pos')]);
        return;
    }
    
    if (!isset($_POST['product_id'])) {
        wp_send_json_error(['message' => __('Failed to delete product.', 'final-pos')]);
        return;
    }

    $product_id = intval(wp_unslash($_POST['product_id']));
    
    if ($product_id) {
        wp_delete_post($product_id, true);
        wp_send_json_success(['message' => __('Product deleted successfully.', 'final-pos')]);
    } else {
        wp_send_json_error(['message' => __('Failed to delete product.', 'final-pos')]);
    }
}
add_action('wp_ajax_delete_product', 'delete_product');

function create_term() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'bulk_editor_nonce')) {
        wp_send_json_error(['message' => __('Security check failed.', 'final-pos')]);
        return;
    }
    
    if (!isset($_POST['term'])) {
        wp_send_json_error(['message' => __('Term not provided.', 'final-pos')]);
        return;
    }

    $term = sanitize_text_field(wp_unslash($_POST['term']));
    $taxonomy = sanitize_text_field(wp_unslash($_POST['taxonomy'] ?? 'product_cat'));
    
    $result = wp_insert_term($term, $taxonomy);
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    } else {
        wp_send_json_success(['id' => $result['term_id'], 'name' => $term]);
    }
}

add_action('wp_ajax_create_category', function() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'bulk_editor_nonce')) {
        wp_send_json_error(['message' => __('Security check failed.', 'final-pos')]);
        return;
    }
    
    if (!isset($_POST['term'])) {
        wp_send_json_error(['message' => __('Term not provided.', 'final-pos')]);
        return;
    }
    create_term(sanitize_text_field(wp_unslash($_POST['term'])), 'product_cat');
});

add_action('wp_ajax_create_tag', function() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'bulk_editor_nonce')) {
        wp_send_json_error(['message' => __('Security check failed.', 'final-pos')]);
        return;
    }
    
    if (!isset($_POST['term'])) {
        wp_send_json_error(['message' => __('Term not provided.', 'final-pos')]);
        return;
    }
    create_term(sanitize_text_field(wp_unslash($_POST['term'])), 'product_tag');
});

add_action('wp_ajax_update_description_data', 'update_description_data');

function get_custom_fields() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'bulk_editor_nonce')) {
        wp_send_json_error(['message' => __('Security check failed.', 'final-pos')]);
        return;
    }
    
    $custom_fields = get_product_custom_fields(true);
    wp_send_json_success($custom_fields);
}
add_action('wp_ajax_get_custom_fields', 'get_custom_fields');

function get_variation_management_data() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'bulk_editor_nonce')) {
        wp_send_json_error(['message' => __('Security check failed.', 'final-pos')]);
        return;
    }
    
    if (!isset($_POST['product_id']) || !($product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT))) {
        wp_send_json_error(['message' => __('No valid product ID provided.', 'final-pos')]);
        return;
    }

    $product = wc_get_product($product_id);
    if (!$product || !$product->is_type('variable')) {
        wp_send_json_error(['message' => __('Invalid product or not a variable product.', 'final-pos')]);
        return;
    }

    $attributes = $product->get_attributes();
    $variations = $product->get_children();

    $form_html = '<form id="variation-management-form">';
    $form_html .= '<input type="hidden" name="product_id" value="' . esc_attr($product_id) . '">';
    $form_html .= '<h3>' . __('Product Attributes', 'final-pos') . ' <span class="material-symbols-outlined add-attribute" title="' . __('Add New Attribute', 'final-pos') . '">add_circle</span></h3>';
    $form_html .= build_attributes_html($attributes);
    $form_html .= '<h3>' . __('Variations', 'final-pos') . ' <span class="material-symbols-outlined add-new-variation" title="' . __('Add New Variation', 'final-pos') . '">add_circle</span></h3>';
    $form_html .= build_variations_html($variations, $attributes);
    $form_html .= '</form>';

    wp_send_json_success(['html' => $form_html]);
}

function build_attributes_html($attributes) {
    $html = '<div id="product-attributes">';
    foreach ($attributes as $attribute) {
        $html .= build_single_attribute_html($attribute);
    }
    $html .= '</div>';
    return $html;
}

function build_single_attribute_html($attribute) {
    $attribute_name = $attribute->get_name();
    $attribute_label = wc_attribute_label($attribute_name);
    $attribute_type = $attribute->is_taxonomy() ? 'global' : 'custom';
    $attribute_values = $attribute->get_options();
    $is_visible = $attribute->get_visible();
    $is_variation = $attribute->get_variation();

    $select_html = '<select name="attributes[' . esc_attr($attribute_name) . '][]" multiple class="attribute-select select2" data-attribute-type="' . esc_attr($attribute_type) . '">';
    if ($attribute_type === 'global') {
        $terms = get_terms(['taxonomy' => $attribute_name, 'hide_empty' => false]);
        foreach ($terms as $term) {
            $selected = in_array($term->term_id, $attribute_values) ? 'selected' : '';
            $select_html .= '<option value="' . esc_attr($term->slug) . '" ' . $selected . '>' . esc_html($term->name) . '</option>';
        }
    } else {
        foreach ($attribute_values as $value) {
            $select_html .= '<option value="' . esc_attr($value) . '" selected>' . esc_html($value) . '</option>';
        }
    }
    $select_html .= '</select>';

    $html = '<div class="attribute">';
    $html .= '<label>' . esc_html($attribute_label) . '</label>';
    $html .= $select_html;
    $html .= render_attribute_options($attribute_name, $is_visible, $is_variation);
    $html .= '</div>';
    return $html;
}

function build_variations_html($variations, $attributes) {
    $html = '<div id="variations-container">';
    foreach ($variations as $variation_id) {
        $variation = wc_get_product($variation_id);
        $html .= render_variation($variation, $attributes);
    }
    $html .= '</div>';
    return $html;
}

add_action('wp_ajax_get_variation_management_data', 'get_variation_management_data');

add_action('wp_ajax_update_variation_management_data', 'update_variation_management_data');

function update_variation_management_data() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'bulk_editor_nonce')) {
        wp_send_json_error(['message' => __('Security check failed.', 'final-pos')]);
        return;
    }
    
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(['message' => __('No permission.', 'final-pos')]);
        return;
    }

    $data = [
        'product_id' => isset($_POST['product_id']) ? intval(wp_unslash($_POST['product_id'])) : 0,
        'attributes' => isset($_POST['attributes']) ? array_map('sanitize_text_field', wp_unslash($_POST['attributes'])) : [],
        'attribute_visibility' => isset($_POST['attribute_visibility']) ? array_map('sanitize_text_field', wp_unslash($_POST['attribute_visibility'])) : [],
        'attribute_variation' => isset($_POST['attribute_variation']) ? array_map('sanitize_text_field', wp_unslash($_POST['attribute_variation'])) : [],
        'variations' => isset($_POST['variations']) ? array_map('sanitize_text_field', wp_unslash($_POST['variations'])) : [],
        'new_variation' => isset($_POST['new_variation']) ? array_map('sanitize_text_field', wp_unslash($_POST['new_variation'])) : null,
    ];

    // ... Rest der Funktion mit $data arbeiten
}

add_action('wp_ajax_delete_variation', 'delete_variation');

function delete_variation() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'bulk_editor_nonce')) {
        wp_send_json_error(['message' => __('Security check failed.', 'final-pos')]);
        return;
    }
    
    if (!current_user_can('manage_woocommerce')) {
        return wp_send_json_error(['message' => __('No permission.', 'final-pos')]);
    }
    
    if (!isset($_POST['variation_id']) || !($variation_id = intval(wp_unslash($_POST['variation_id'] ?? 0)))) {
        return wp_send_json_error(['message' => __('Invalid variation ID.', 'final-pos')]);
    }
    
    $variation = wc_get_product($variation_id);
    if (!$variation || !$variation->is_type('variation')) {
        return wp_send_json_error(['message' => __('Invalid variation.', 'final-pos')]);
    }
    
    $product_id = $variation->get_parent_id();
    $product = wc_get_product($product_id);
    if (!$product || !$product->is_type('variable')) {
        return wp_send_json_error(['message' => __('Invalid parent product.', 'final-pos')]);
    }
    
    wp_delete_post($variation_id, true);
    wc_delete_product_transients($product_id);
    return wp_send_json_success(['message' => __('Variation deleted successfully.', 'final-pos')]);
}

add_action('wp_ajax_get_global_attributes', 'get_global_attributes_and_terms');
add_action('wp_ajax_get_global_attribute_terms', 'get_global_attributes_and_terms');

function get_global_attributes_and_terms() {
    // Verify nonce
    if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['nonce'])), 'bulk_editor_nonce')) {
        wp_send_json_error(['message' => __('Security check failed.', 'final-pos')]);
        return;
    }
    
    $search = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
    $attribute = isset($_GET['attribute']) ? sanitize_text_field(wp_unslash($_GET['attribute'])) : '';
    $attribute_taxonomies = wc_get_attribute_taxonomies();
    $results = [];

    foreach ($attribute_taxonomies as $taxonomy) {
        if (empty($search) || stripos($taxonomy->attribute_label, $search) !== false) {
            $results[] = [
                'id' => $taxonomy->attribute_name,
                'text' => $taxonomy->attribute_label
            ];
        }
    }

    if (!empty($attribute)) {
        $terms = get_terms([
            'taxonomy' => 'pa_' . $attribute,
            'hide_empty' => false,
            'search' => $search
        ]);

        foreach ($terms as $term) {
            $results[] = [
                'id' => $term->slug,
                'text' => $term->name
            ];
        }
    }

    wp_send_json($results);
}

add_action('wp_ajax_get_category_terms', function() {
    get_terms_by_taxonomy('product_cat');
});

add_action('wp_ajax_get_tag_terms', function() {
    get_terms_by_taxonomy('product_tag');
});

function get_terms_by_taxonomy($taxonomy) {
    // Verify nonce
    if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['nonce'])), 'bulk_editor_nonce')) {
        wp_send_json_error(['message' => __('Security check failed.', 'final-pos')]);
        return;
    }
    
    $search = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
    $terms = get_terms([
        'taxonomy' => $taxonomy,
        'hide_empty' => false,
        'search' => $search
    ]);
    
    $results = array_map(function($term) {
        return [
            'id' => $term->term_id,
            'text' => $term->name
        ];
    }, $terms);

    wp_send_json($results);
}

add_action('wp_ajax_get_custom_fields', 'get_custom_fields');

add_action('wp_ajax_save_custom_columns', 'save_custom_columns');

function save_custom_columns() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'bulk_editor_nonce')) {
        wp_send_json_error(['message' => __('Security check failed.', 'final-pos')]);
        return;
    }
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('No permission.', 'final-pos')]);
        return;
    }
    
    // Sanitize the JSON string before unslashing and decoding
    $raw_json = isset($_POST['custom_columns']) ? sanitize_text_field(wp_unslash($_POST['custom_columns'])) : '';
    $custom_columns_raw = json_decode($raw_json, true);
    
    if (!is_array($custom_columns_raw)) {
        wp_send_json_error(['message' => __('Invalid data format', 'final-pos')]);
        return;
    }

    $sanitized_columns = array_map(function($column) {
        return [
            'field' => isset($column['field']) ? sanitize_text_field($column['field']) : '',
            'label' => isset($column['label']) ? sanitize_text_field($column['label']) : ''
        ];
    }, $custom_columns_raw);
    
    update_option('bulk_editor_custom_columns', array_filter($sanitized_columns, function($column) {
        return !empty($column['field']) && !empty($column['label']);
    }));
    
    wp_send_json_success(['message' => __('Custom columns saved successfully.', 'final-pos')]);
}
