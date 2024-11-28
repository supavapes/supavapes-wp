<?php
// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}
// Load Post Extensions JS
function final_enqueue_post_extensions() {
    if (current_user_can('manage_options')) {
        wp_enqueue_script(
            'final-posts-js',
            plugin_dir_url(__FILE__) . '../../../assets/js/other/posts.js',
            array(),
            filemtime(plugin_dir_path(__FILE__) . '../../../assets/js/other/posts.js'),
            true
        );
    }
}
add_action('admin_enqueue_scripts', 'final_enqueue_post_extensions');

// Categories and Tags for Pages
function final_add_taxonomies_to_pages() {
    register_taxonomy_for_object_type('category', 'page');
    register_taxonomy_for_object_type('post_tag', 'page');
}
add_action('init', 'final_add_taxonomies_to_pages');

function final_add_taxonomy_metaboxes_to_pages() {
    add_meta_box('categorydiv', 'Kategorien', 'post_categories_meta_box', 'page', 'side', 'default');
    add_meta_box('tagsdiv-post_tag', 'Tags', 'post_tags_meta_box', 'page', 'side', 'low');
}
add_action('add_meta_boxes_page', 'final_add_taxonomy_metaboxes_to_pages');