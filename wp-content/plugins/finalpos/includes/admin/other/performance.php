<?php
// If this file is called directly, abort.
if ( ! defined('ABSPATH')) {
    exit;
}

if ( ! is_admin()) {
    return;
}

// Fetch saved settings using the common function
$saved_settings = final_get_advanced_settings();

// Check if optimizations are enabled
$optimizations_enabled = !empty($saved_settings['wordpress_optimizations']);

// Action Scheduler Optimization
if ($optimizations_enabled) {
    // EMOTICONS AND FEEDS
    $emoji_actions = [
        'wp_head' => [
            'print_emoji_detection_script' => 7,
            'wp_resource_hints' => 2,
            'rsd_link',
            'wlwmanifest_link',
            'wp_generator',
            'feed_links' => 2,
            'feed_links_extra' => 3,
            'the_generator',
        ],
        'admin_print_styles' => 'print_emoji_styles',
        'admin_print_scripts' => 'print_emoji_detection_script',
        'embed_head' => 'print_emoji_detection_script',
        'the_content_feed' => 'wp_staticize_emoji',
        'comment_text_rss' => 'wp_staticize_emoji',
        'wp_mail' => 'wp_staticize_emoji_for_email',
    ];

    foreach ($emoji_actions as $hook => $actions) {
        if (is_array($actions)) {
            foreach ($actions as $action => $priority) {
                remove_action($hook, $action, $priority);
            }
        } else {
            remove_action($hook, $actions);
        }
    }

    // Define constants if not already defined
    defined('EMPTY_TRASH_DAYS') || define('EMPTY_TRASH_DAYS', 7);
    defined('WP_POST_REVISIONS') || define('WP_POST_REVISIONS', 5);
    defined('DISALLOW_FILE_EDIT') || define('DISALLOW_FILE_EDIT', true);

    // Remove Feed Generator Tag
    $feed_generator_tags = [
        'atom_head',
        'comments_atom_head',
        'commentsrss2_head',
        'rdf_header',
        'rss_head',
        'rss2_head',
        'opml_head',
    ];
    foreach ($feed_generator_tags as $tag) {
        remove_action('wp_head', 'the_generator');
    }

    // Admin filters
    add_filter('admin_footer_text', '__return_false');
    add_filter('admin_email_check_interval', '__return_false');
    add_filter('action_scheduler_retention_period', fn() => 5 * DAY_IN_SECONDS);/*
    add_filter('heartbeat_settings', fn($settings) => ['interval' => 120]);*/

    // Dashboard Widgets Management
    add_action('wp_dashboard_setup', function() {
        global $wp_meta_boxes;
        $wp_meta_boxes['dashboard']['normal']['core'] = [];
        $wp_meta_boxes['dashboard']['side']['core'] = [];
    });

    // Hide update notice for non-admin users
    add_action('admin_head', function() {
        if (!current_user_can('update_core')) {
            remove_action('admin_notices', 'update_nag', 3);
        }
    }, 1);

    // Autosave Interval
    add_filter('autosave_interval', fn() => 300);

    // Jetpack Optimizations
    add_filter('jetpack_just_in_time_msgs', '__return_false', 20);
    add_filter('jetpack_show_promotions', '__return_false', 20);
    add_filter('jetpack_blaze_enabled', '__return_false');

    // Elementor Dashboard Widget Management
    add_action('wp_dashboard_setup', function() {
        remove_meta_box('e-dashboard-overview', 'dashboard', 'normal');
    }, 40);
    /*add_filter('elementor/frontend/print_google_fonts', '__return_false');*/

    // Generic Optimizations
    add_filter('wpseo_debug_markers', '__return_false');
    add_action('wp_default_scripts', function($scripts) {
        if (!is_admin() && isset($scripts->registered['jquery'])) {
            $script = $scripts->registered['jquery'];
            if ($script->deps) {
                $script->deps = array_diff($script->deps, ['jquery-migrate']);
            }
        }
    });
}

// Disable Feeds
if (!empty($saved_settings['disable_feeds'])) {
    $feed_types = ['do_feed', 'do_feed_rdf', 'do_feed_rss', 'do_feed_rss2', 'do_feed_atom'];
    foreach ($feed_types as $feed) {
        add_action($feed, 'final_boost_disable_feed', 1);
    }
}

// Disable Gutenberg
if (!empty($saved_settings['gutenberg_disable'])) {
    add_filter('use_block_editor_for_post_type', '__return_false', 100);
    add_filter('after_setup_theme', function() {
        $gutenberg_actions = [
            'admin_menu' => 'gutenberg_menu',
            'admin_init' => 'gutenberg_redirect_demo',
            'rest_api_init' => [
                'gutenberg_register_rest_routes',
                'gutenberg_add_taxonomy_visibility_field',
            ],
            'admin_notices' => 'gutenberg_build_files_notice',
            'admin_enqueue_scripts' => 'gutenberg_check_if_classic_needs_warning_about_blocks',
            'admin_init' => 'gutenberg_add_edit_link_filters',
            'admin_print_scripts-edit.php' => 'gutenberg_replace_default_add_new_button',
        ];

        foreach ($gutenberg_actions as $hook => $action) {
            if (is_array($action)) {
                foreach ($action as $a) {
                    remove_action($hook, $a);
                }
            } else {
                remove_action($hook, $action);
            }
        }

        remove_filter('wp_refresh_nonces', 'gutenberg_add_rest_nonce_to_heartbeat_response_headers');
        remove_filter('get_edit_post_link', 'gutenberg_revisions_link_to_editor');
        remove_filter('wp_prepare_revision_for_js', 'gutenberg_revisions_restore');
        remove_filter('rest_request_after_callbacks', 'gutenberg_filter_oembed_result');
        remove_filter('registered_post_type', 'gutenberg_register_post_prepare_functions');
        remove_action('do_meta_boxes', 'gutenberg_meta_box_save', 1000);
        remove_action('submitpost_box', 'gutenberg_intercept_meta_box_render');
        remove_action('submitpage_box', 'gutenberg_intercept_meta_box_render');
        remove_action('edit_page_form', 'gutenberg_intercept_meta_box_render');
        remove_action('edit_form_advanced', 'gutenberg_intercept_meta_box_render');
        remove_filter('redirect_post_location', 'gutenberg_meta_box_save_redirect');
        remove_filter('filter_gutenberg_meta_boxes', 'gutenberg_filter_meta_boxes');
        remove_filter('body_class', 'gutenberg_add_responsive_body_class');
        remove_filter('admin_url', 'gutenberg_modify_add_new_button_url');
        remove_filter('register_post_type_args', 'gutenberg_filter_post_type_labels');
        remove_action('edit_form_top', 'gutenberg_remember_classic_editor_when_saving_posts');
    });

    // Block Library Styles
    add_filter('wp_enqueue_scripts', function() {
        wp_dequeue_style('wp-block-library');
    }, 100);

    // Disable Widgets Block Editor
    add_filter('gutenberg_use_widgets_block_editor', '__return_false', 100);
    add_filter('use_widgets_block_editor', '__return_false');

    add_action('after_setup_theme', function() {
        remove_theme_support('widgets-block-editor');
    });

    // Deactivate the Template Editor
    remove_theme_support('block-templates');
}

// WOOCOMMERCE
if (!empty($saved_settings['woocommerce_optimizations'])) {
    // Marketing Hub
    add_filter('woocommerce_marketing_menu_items', '__return_empty_array');
    add_filter('woocommerce_helper_suppress_admin_notices', '__return_true');

        // Marketing Hub
    add_filter('woocommerce_marketing_menu_items', '__return_empty_array');
    add_filter('woocommerce_admin_features', 'final_boost_disable_features');

    function final_boost_disable_features($features) {
        unset($features[array_search('marketing', $features)]);
        return $features;
    }

    // Disable WooCommerce Status Meta Box
    add_action('wp_dashboard_setup', function() {
        remove_meta_box('woocommerce_dashboard_status', 'dashboard', 'normal');
    });

    // Disable WooCommerce Dashboard Setup Widget
    add_action('wp_dashboard_setup', function() {
        remove_meta_box('wc_admin_dashboard_setup', 'dashboard', 'normal');
    }, 40);

    // Disable WooCommerce Marketplace Suggestions
    add_filter('woocommerce_allow_marketplace_suggestions', '__return_false', 999);

    // Disable Extensions submenu
    add_action('admin_menu', function() {
        remove_submenu_page('woocommerce', 'wc-addons');
        remove_submenu_page('woocommerce', 'wc-addons&section=helper');
    }, 999);

    // Hide Discover other payment providers link
    add_action('admin_head', function() {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                if (window.location.search.includes('wc-settings&tab=checkout')) {
                    const paymentBloat = document.querySelector('#settings-other-payment-methods').parentElement.parentElement;
                    paymentBloat.style.display = 'none';
                }
            });
        </script>
        <?php
    });

    // Remove the "Shipping Extensions" WooCommerce menu entry
    add_action('admin_menu', function() {
        remove_submenu_page('woocommerce', 'octolize-shipping-extensions');
    }, 999);
}

// Disable Comments Function
if (!empty($saved_settings['disable_comments'])) {
    add_action('admin_init', function() {
        remove_post_type_support('post', 'comments');
        remove_post_type_support('page', 'comments');
        
        // Check if the comments menu page exists before removing it
        if (menu_page_url('edit-comments.php', false)) {
            remove_menu_page('edit-comments.php');
        }
        
        remove_meta_box('commentstatusdiv', 'post', 'normal');
        remove_meta_box('commentstatusdiv', 'page', 'normal');

        if (is_admin_bar_showing()) {
            remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);
        }

        global $pagenow;
        if ($pagenow === 'edit-comments.php') {
            wp_redirect(admin_url());
            exit;
        }
    });

    // Disable comment feeds and status checks
    add_filter('comments_open', '__return_false', 20, 2);
    add_filter('pings_open', '__return_false', 20, 2);
    add_filter('feed_links_show_comments_feed', '__return_false');
}

// Disable XMLRPC
if (!empty($saved_settings['disable_xmlrpc'])) {
    add_filter('xmlrpc_enabled', '__return_false');
    add_filter('xmlrpc_methods', 'final_boost_disable_xmlrpc_methods');
    remove_action('xmlrpc_rsd_apis', 'rest_output_rsd');
}
