<?php
$labels = array(
	'name'                       => _x('Store Locators', 'taxonomy general name', 'supavapes'),
	'singular_name'              => _x('Store Locator', 'taxonomy singular name', 'supavapes'),
	'search_items'               => __('Search Store Locators', 'supavapes'),
	'all_items'                  => __('All Store Locators', 'supavapes'),
	'parent_item'                => __('Parent Store Locator', 'supavapes'),
	'parent_item_colon'          => __('Parent Store Locator:', 'supavapes'),
	'edit_item'                  => __('Edit Store Locator', 'supavapes'),
	'update_item'                => __('Update Store Locator', 'supavapes'),
	'add_new_item'               => __('Add New Store Locator', 'supavapes'),
	'new_item_name'              => __('New Store Locator Name', 'supavapes'),
	'menu_name'                  => __('Store Locator', 'supavapes'),
);
$args = array(
	'hierarchical'               => true,
	'labels'                     => $labels,
	'show_ui'                    => true,
	'show_admin_column'          => true,
	'query_var'                  => true,
	'rewrite'                    => array('slug' => 'store-locator'),
);
register_taxonomy('store_locator', array('product'), $args);