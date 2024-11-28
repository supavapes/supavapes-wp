<?php

// Prevent direct access to the script
if ( ! defined('ABSPATH') ) {
    exit;
}

function final_output_custom_colors() {
    $colors = [
        'primary_color' => get_option('final_pos_primary_color', '#3D4C66'),
        'base_color' => get_option('final_pos_base_color', '#2797e8'),
        'secondary_color' => get_option('final_pos_secondary_color', '#EAF5FF'),
        'text_color' => get_option('final_pos_text_color', '#4d4d4d'),
        'dark_primary_color' => get_option('final_pos_dark_primary_color', '#54698e'),
        'dark_base_color' => get_option('final_pos_dark_base_color', '#2797e8'),
        'dark_secondary_color' => get_option('final_pos_dark_secondary_color', '#b6cee789'),
        'dark_text_color' => get_option('final_pos_dark_text_color', '#f7f7f7'),
    ];

    echo '<style>
        :root {
            --uxlabs-primary-color: ' . esc_attr($colors['primary_color']) . ';
            --uxlabs-base-color: ' . esc_attr($colors['base_color']) . ';
            --uxlabs-secondary-color: ' . esc_attr($colors['secondary_color']) . ';
            --uxlabs-text-color: ' . esc_attr($colors['text_color']) . ';
        }
        body.uxlabs-dark-mode {
            --uxlabs-primary-color: ' . esc_attr($colors['dark_primary_color']) . ';
            --uxlabs-base-color: ' . esc_attr($colors['dark_base_color']) . ';
            --uxlabs-secondary-color: ' . esc_attr($colors['dark_secondary_color']) . ';
            --uxlabs-text-color: ' . esc_attr($colors['dark_text_color']) . ';
        }
    </style>';
}
add_action('admin_head', 'final_output_custom_colors');

function final_reset_ui_pro_settings() {
    check_ajax_referer('final_wizard_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        return wp_send_json_error('Insufficient permissions');
    }

    $default_colors = [
        'primary_color' => '#3D4C66',
        'base_color' => '#2797e8',
        'secondary_color' => '#EAF5FF',
        'text_color' => '#4d4d4d',
        'dark_primary_color' => '#54698e',
        'dark_base_color' => '#2797e8',
        'dark_secondary_color' => '#b6cee789',
        'dark_text_color' => '#f7f7f7'
    ];

    foreach ($default_colors as $key => $color) {
        update_option('final_pos_' . $key, $color);
    }

    wp_send_json_success([
        'message' => 'UI Pro settings reset successfully',
        'default_colors' => $default_colors
    ]);
}
add_action('wp_ajax_reset_ui_pro_settings', 'final_reset_ui_pro_settings');

function final_add_body_class($classes) {
    if (!empty(final_get_advanced_settings()['woocommerce_optimizations'])) {
        $classes .= ' final-woo-optimizations-enabled';
    }
    return $classes;
}
add_filter('admin_body_class', 'final_add_body_class');

function extended_admin_menu() {
    $custom_logo_url = get_option('final_pos_logo_url', '');
    $default_logo_url = plugins_url('finalpos/assets/img/ui/final-logo.png');
    $default_shrinked_logo_url = plugins_url('finalpos/assets/img/ui/logo-shrunk.png');

    $logo_url = $custom_logo_url ?: $default_logo_url;
    $shrinked_logo_url = $custom_logo_url ?: $default_shrinked_logo_url;

    echo '<div id="custom-logo">
            <a href="' . esc_url(admin_url()) . '">
                <img src="' . esc_url($logo_url) . '" alt="Final Logo" class="full-logo">
                <img src="' . esc_url($shrinked_logo_url) . '" alt="Final Logo" class="shrinked-logo" style="display: none;">
            </a>
          </div>';
}
add_action('in_admin_header', 'extended_admin_menu', 0);

function final_admin_ui_setup() {
    // Admin menu search
    echo '<script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function() {
            var adminMenu = document.getElementById("adminmenu");
            if (adminMenu) {
                var searchWrapper = document.createElement("div");
                searchWrapper.id = "admin-menu-search-wrapper";
                searchWrapper.innerHTML = `<div class="custom-search-container" style="display:flex;align-items:center;background-color:#f5f5f5;border-radius:4px;min-height:35px;">
                                                <span class="custom-search-icon material-symbols-outlined">manage_search</span>
                                                <input type="text" id="admin-menu-search" class="custom-search-input" placeholder="' . esc_attr(__('Search menu...', 'final-pos')) . '" style="width:100%;padding-left:35px;font-size:12px;border:none;min-height:35px;background-color:#f5f5f5;border-radius:4px;">
                                            </div>`;
                adminMenu.parentNode.insertBefore(searchWrapper, adminMenu);
            }
        });
    </script>';

    // Custom sidebar HTML
    echo '<div id="final-sidebar-overlay" style="display:none;"></div>
          <div id="final-sidebar" style="display:none;">
              <div id="final-sidebar-content"></div>
          </div>';

    // Modify search box for specific screens
    $screen = get_current_screen();
    if (method_exists($screen, 'is_block_editor') && !$screen->is_block_editor() && $screen->base === 'edit') {
        $search_box_id = $screen->id . '-search-input';
        $post_type = $screen->post_type;
        echo '<script type="text/javascript">
            document.addEventListener("DOMContentLoaded", function() {
                var searchInput = document.getElementById("' . esc_js($search_box_id) . '");
                if (searchInput) {
                    searchInput.setAttribute("data-post-type", "' . esc_js($post_type) . '");
                }
            });
        </script>';
    }
}
add_action('admin_footer', 'final_admin_ui_setup');


function final_kses_icon($icon) {
    $allowed_html = array(
        'span' => array(
            'class' => array(),
        ),
    );
    return wp_kses($icon, $allowed_html);
}

function final_admin_notifications_bar() {
    $current_user = wp_get_current_user();
    $user_first_name = esc_html($current_user->user_firstname);
    $site_title = esc_html(get_bloginfo('name'));
    $is_indexed = get_option('blog_public') === '1';

    ob_start();
    do_action('final_admin_notice_error');
    $error_notifications = ob_get_clean();

    $site_badge_text = $is_indexed ? 'Live' : 'Hidden';
    $site_badge_class = $is_indexed ? 'badge-live' : 'badge-hidden';
    $site_icon = final_kses_icon('<span class="material-symbols-outlined badgeicon">public</span>');
    $site_admin_page_url = admin_url('options-reading.php');

    $saved_settings = final_get_advanced_settings();
    $woo_optimizations_enabled = !empty($saved_settings['woocommerce_optimizations']);

    $woo_badge = '';
    if (class_exists('WooCommerce') && !$woo_optimizations_enabled) {
        $is_coming_soon = get_option('woocommerce_coming_soon', 'no') === 'yes';
        $woo_badge_text = $is_coming_soon ? 'Soon' : 'Live';
        $woo_badge_class = $is_coming_soon ? 'badge-coming-soon' : 'badge-live';
        $woo_icon = final_kses_icon('<span class="material-symbols-outlined badgeicon">shopping_cart</span>');
        $woo_admin_page_url = admin_url('admin.php?page=wc-settings&tab=site-visibility');

        $woo_badge = sprintf(
            '<a href="%s" class="badge woo-badge %s">%s %s</a>',
            esc_url($woo_admin_page_url),
            esc_attr($woo_badge_class),
            $woo_icon,
            esc_html($woo_badge_text)
        );
    }

    ?>
    <div id="custom-notifications-bar" class="custom-notifications-bar">
        <div class="left">
            <?php echo wp_kses_post(final_kses_icon('<span class="material-symbols-outlined">notifications</span>')); ?>
            <span id="notification-bubble" class="notification-bubble no-notifications">0</span>
            <?php
            /* translators: %s: user's first name */
            $greeting = sprintf(__('Hey %s!', 'final-pos'), $user_first_name);
            ?>
            <span id="custom-notification-text" data-name="<?php echo esc_attr($user_first_name); ?>">
                <?php echo esc_html($greeting); ?>
            </span>
        </div>
        <div class="right">
            <span class="site-title"><?php echo esc_html($site_title); ?></span>
            <a href="<?php echo esc_url($site_admin_page_url); ?>" class="badge site-badge <?php echo esc_attr($site_badge_class); ?>"><?php echo wp_kses_post($site_icon) . ' ' . esc_html($site_badge_text); ?></a>
            <?php echo wp_kses_post($woo_badge); ?>
        </div>
    </div>
    <div id="custom-notifications-container" class="custom-notifications-container">
        <?php echo wp_kses_post($error_notifications); ?>
    </div>
    <?php
}
add_action('in_admin_header', 'final_admin_notifications_bar', 20);
add_action('wp_ajax_execute_shortcode', 'execute_shortcode_callback');
function execute_shortcode_callback() {
    // Check for nonce
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'final_ui_nonce')) {
        wp_send_json_error('Security check failed.');
        return;
    }

    // Sanitize and unslash the shortcode
    $shortcode = isset($_POST['shortcode']) ? sanitize_text_field(wp_unslash($_POST['shortcode'])) : null;

    if ($shortcode) {
        // Execute the shortcode and send the result
        $result = do_shortcode($shortcode);
        wp_send_json_success($result);
    } else {
        wp_send_json_error('No shortcode provided.');
    }
}
function enqueue_ui_scripts() {
    wp_enqueue_script('ui-js', plugins_url('../../../assets/js/ui/ui.js', __FILE__), ['jquery'], '1.0.0', true);
    wp_localize_script('ui-js', 'final_ui_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('final_ui_nonce'),
        // Add translations
        'i18n' => array(
            'hey' => __('Hey', 'final-pos'), // translators: Greeting
            'notification_single' => __('You have one notification that you might want to check.', 'final-pos'), // translators: Notification message for a single notification
            'notification_multiple' => __('You have %d notifications that you might want to check.', 'final-pos'), // translators: %d: number of notifications
            'all_caught_up' => __('All systems are go! You\'re all caught up.', 'final-pos'), // translators: Message indicating no new notifications
            'search_menu' => __('Search menu...', 'final-pos'), // translators: Placeholder text for search menu
            'search_placeholder' => __('Search %s...', 'final-pos'), // translators: %s: name of the search context
            'search' => __('Search', 'final-pos'), // translators: Search button text
            'no_content' => __('There was a problem loading the content.', 'final-pos') // translators: Error message for content loading issues
        )
    ));
}
add_action('admin_enqueue_scripts', 'enqueue_ui_scripts');










// Funktion zum Ersetzen der Dashicons
function replace_dashicons_with_material_icons() {
    // Array der Dashicons und ihrer entsprechenden Material Icons
    $icons = array(
        /* Woo */
        'dashicons-cart' => 'shopping_cart_checkout',
        'dashicons-archive' => 'qr_code_scanner',
        'dashicons-chart-bar' => 'monitoring',
        /* WP */
        'dashicons-dashboard' => 'dashboard',
        'dashicons-admin-post' => 'rate_review',
        'dashicons-admin-media' => 'add_photo_alternate',
        'dashicons-admin-links' => 'link',
        'dashicons-admin-comments' => 'comment',
        'dashicons-testimonial' => 'reviews',
        'dashicons-admin-appearance' => 'palette',
        'dashicons-admin-plugins' => 'extension',
        'dashicons-admin-users' => 'person',
        'dashicons-admin-tools' => 'build_circle',
        'dashicons-admin-settings' => 'tune',
        'dashicons-admin-network' => 'language',
        'dashicons-admin-generic' => 'tune',
        'dashicons-admin-home' => 'home',
        'dashicons-admin-collapse' => 'chevron_left',
        'dashicons-admin-site' => 'language',
        'dashicons-admin-themes' => 'palette',
        'dashicons-admin-multisite' => 'apps',
        'dashicons-admin-customizer' => 'tune',
        'dashicons-admin-user' => 'person',
        'dashicons-admin-page' => 'edit_document',
        'dashicons-format-standard' => 'text_fields',
        'dashicons-feedback' => 'feedback',
        'dashicons-format-image' => 'image',
        'dashicons-format-gallery' => 'collections',
        'dashicons-format-audio' => 'audiotrack',
        'dashicons-format-video' => 'videocam',
        'dashicons-format-links' => 'link',
        'dashicons-format-chat' => 'chat',
        'dashicons-format-status' => 'update',
        'dashicons-format-aside' => 'article',
        'dashicons-format-quote' => 'format_quote',
        'dashicons-camera' => 'camera_alt',
        'dashicons-images-alt' => 'image',
        'dashicons-images-alt2' => 'collections',
        'dashicons-video-alt' => 'videocam',
        'dashicons-video-alt2' => 'video_library',
        'dashicons-video-alt3' => 'video_call',
        'dashicons-index-card' => 'notes',
        'dashicons-carrot' => 'local_florist',
        'dashicons-drumstick' => 'fastfood',
        'dashicons-coffee' => 'free_breakfast',
        'dashicons-star-empty' => 'star_border',
        'dashicons-star-filled' => 'star',
        'dashicons-phone' => 'phone',
        'dashicons-facebook' => 'facebook',
        'dashicons-twitter' => 'twitter',
        'dashicons-instagram' => 'instagram',
        'dashicons-rss' => 'rss_feed',
        'dashicons-email' => 'email',
        'dashicons-share' => 'share',
        'dashicons-calendar' => 'event',
        'dashicons-palmtree' => 'beach_access',
        'dashicons-megaphone' => 'campaign',
        'dashicons-book' => 'book',
        'dashicons-edit' => 'edit',
        'dashicons-list-view' => 'format_list_bulleted',
        'dashicons-analytics' => 'analytics',
        'dashicons-visibility' => 'visibility',
        'dashicons-admin-users' => 'group',
        // Neue Icons hinzufügen
        'dashicons-menu' => 'menu',
        'dashicons-menu-alt' => 'menu',
        'dashicons-menu-alt2' => 'menu',
        'dashicons-menu-alt3' => 'menu',
        'dashicons-site' => 'site',
        'dashicons-site-alt' => 'site',
        'dashicons-site-alt2' => 'site',
        'dashicons-site-alt3' => 'site',
        'dashicons-post' => 'post',
        'dashicons-media' => 'media',
        'dashicons-links' => 'link',
        'dashicons-page' => 'page',
        'dashicons-appearance' => 'palette',
        'dashicons-plugins' => 'extension',
        'dashicons-tools' => 'build_circle',
        'dashicons-network' => 'language',
        'dashicons-generic' => 'tune',
        'dashicons-collapse' => 'chevron_left',
        'dashicons-filter' => 'filter',
        'dashicons-customizer' => 'tune',
        'dashicons-multisite' => 'apps',
        'dashicons-write-blog' => 'create',
        'dashicons-add-page' => 'add',
        'dashicons-view-site' => 'visibility',
        'dashicons-widgets-menus' => 'widgets',
        'dashicons-learn-more' => 'info',
        'dashicons-aside' => 'article',
        'dashicons-image' => 'image',
        'dashicons-gallery' => 'collections',
        'dashicons-video' => 'videocam',
        'dashicons-status' => 'update',
        'dashicons-quote' => 'format_quote',
        'dashicons-chat' => 'chat',
        'dashicons-audio' => 'audiotrack',
        'dashicons-camera-alt' => 'camera_alt',
        'dashicons-images-alt' => 'image',
        'dashicons-images-alt2' => 'collections',
        'dashicons-video-alt' => 'videocam',
        'dashicons-video-alt2' => 'video_library',
        'dashicons-video-alt3' => 'video_call',
        'dashicons-database-add' => 'add',
        'dashicons-database' => 'database',
        'dashicons-database-export' => 'export',
        'dashicons-database-import' => 'import',
        'dashicons-database-remove' => 'delete',
        'dashicons-database-view' => 'visibility',
        'dashicons-align-full-width' => 'align_horizontal_center',
        'dashicons-align-pull-left' => 'align_left',
        'dashicons-align-pull-right' => 'align_right',
        'dashicons-align-wide' => 'align_wide',
        'dashicons-block-default' => 'block',
        'dashicons-button' => 'button',
        'dashicons-cloud-saved' => 'cloud_done',
        'dashicons-cloud-upload' => 'cloud_upload',
        'dashicons-columns' => 'grid_view',
        'dashicons-cover-image' => 'image',
        'dashicons-ellipsis' => 'more_horiz',
        'dashicons-embed-audio' => 'audiotrack',
        'dashicons-embed-generic' => 'insert_drive_file',
        'dashicons-embed-photo' => 'image',
        'dashicons-embed-post' => 'post_add',
        'dashicons-embed-video' => 'videocam',
        'dashicons-exit' => 'exit_to_app',
        'dashicons-heading' => 'title',
        'dashicons-html' => 'code',
        'dashicons-info-outline' => 'info',
        'dashicons-insert' => 'add',
        'dashicons-insert-after' => 'add',
        'dashicons-insert-before' => 'add',
        'dashicons-remove' => 'remove',
        'dashicons-saved' => 'save',
        'dashicons-shortcode' => 'code',
        'dashicons-table-col-after' => 'table_rows',
        'dashicons-table-col-before' => 'table_rows',
        'dashicons-table-col-delete' => 'delete',
        'dashicons-table-row-after' => 'table_rows',
        'dashicons-table-row-before' => 'table_rows',
        'dashicons-table-row-delete' => 'delete',
        'dashicons-bold' => 'format_bold',
        'dashicons-italic' => 'format_italic',
        'dashicons-unordered-list' => 'format_list_bulleted',
        'dashicons-ordered-list' => 'format_list_numbered',
        'dashicons-ordered-list-rtl' => 'format_list_numbered',
        'dashicons-quote' => 'format_quote',
        'dashicons-align-left' => 'format_align_left',
        'dashicons-align-center' => 'format_align_center',
        'dashicons-align-right' => 'format_align_right',
        'dashicons-insert-more' => 'more_horiz',
        'dashicons-spellcheck' => 'spellcheck',
        'dashicons-expand' => 'expand_more',
        'dashicons-contract' => 'expand_less',
        'dashicons-kitchen-sink' => 'kitchen',
        'dashicons-underline' => 'format_underlined',
        'dashicons-justify' => 'format_align_justify',
        'dashicons-text-color' => 'format_color_fill',
        'dashicons-paste-word' => 'content_paste',
        'dashicons-paste-text' => 'content_paste',
        'dashicons-remove-formatting' => 'format_clear',
        'dashicons-video' => 'videocam',
        'dashicons-custom-character' => 'text_fields',
        'dashicons-outdent' => 'format_indent_decrease',
        'dashicons-indent' => 'format_indent_increase',
        'dashicons-help' => 'help',
        'dashicons-strikethrough' => 'strikethrough_s',
        'dashicons-unlink' => 'link_off',
        'dashicons-rtl' => 'format_textdirection_r_to_l',
        'dashicons-ltr' => 'format_textdirection_l_to_r',
        'dashicons-break' => 'break',
        'dashicons-code' => 'code',
        'dashicons-paragraph' => 'format_paragraph',
        'dashicons-table' => 'table_chart',
        'dashicons-align-left' => 'format_align_left',
        'dashicons-align-right' => 'format_align_right',
        'dashicons-align-center' => 'format_align_center',
        'dashicons-align-none' => 'format_align_justify',
        'dashicons-lock' => 'lock',
        'dashicons-unlock' => 'lock_open',
        'dashicons-calendar' => 'event',
        'dashicons-calendar-alt' => 'event_available',
        'dashicons-visibility' => 'visibility',
        'dashicons-hidden' => 'visibility_off',
        'dashicons-post-status' => 'assignment',
        'dashicons-edit' => 'edit',
        'dashicons-trash' => 'delete',
        'dashicons-sticky' => 'push_pin',
        'dashicons-external' => 'open_in_new',
        'dashicons-arrow-up' => 'arrow_upward',
        'dashicons-arrow-down' => 'arrow_downward',
        'dashicons-arrow-right' => 'arrow_forward',
        'dashicons-arrow-left' => 'arrow_back',
        'dashicons-arrow-up-alt' => 'arrow_upward',
        'dashicons-arrow-down-alt' => 'arrow_downward',
        'dashicons-arrow-right-alt' => 'arrow_forward',
        'dashicons-arrow-left-alt' => 'arrow_back',
        'dashicons-arrow-up-alt2' => 'arrow_upward',
        'dashicons-arrow-down-alt2' => 'arrow_downward',
        'dashicons-arrow-right-alt2' => 'arrow_forward',
        'dashicons-arrow-left-alt2' => 'arrow_back',
        'dashicons-sort' => 'sort',
        'dashicons-left-right' => 'swap_horiz',
        'dashicons-randomize' => 'shuffle',
        'dashicons-list-view' => 'view_list',
        'dashicons-excerpt-view' => 'view_list',
        'dashicons-grid-view' => 'grid_view',
        'dashicons-move' => 'move_to_inbox',
        'dashicons-share' => 'share',
        'dashicons-share-alt' => 'share',
        'dashicons-share-alt2' => 'share',
        'dashicons-rss' => 'rss_feed',
        'dashicons-email' => 'email',
        'dashicons-email-alt' => 'email',
        'dashicons-email-alt2' => 'email',
        'dashicons-networking' => 'group',
        'dashicons-amazon' => 'shopping_cart',
        'dashicons-facebook' => 'facebook',
        'dashicons-facebook-alt' => 'facebook',
        'dashicons-google' => 'google',
        'dashicons-instagram' => 'instagram',
        'dashicons-linkedin' => 'linkedin',
        'dashicons-pinterest' => 'pin_drop',
        'dashicons-podio' => 'group',
        'dashicons-reddit' => 'reddit',
        'dashicons-spotify' => 'music_note',
        'dashicons-twitch' => 'live_tv',
        'dashicons-twitter' => 'twitter',
        'dashicons-twitter-alt' => 'twitter',
        'dashicons-whatsapp' => 'whatsapp',
        'dashicons-xing' => 'group',
        'dashicons-youtube' => 'video_library',
        'dashicons-hammer' => 'build',
        'dashicons-art' => 'palette',
        'dashicons-migrate' => 'swap_horiz',
        'dashicons-performance' => 'trending_up',
        'dashicons-universal-access' => 'accessible',
        'dashicons-universal-access-alt' => 'accessible_forward',
        'dashicons-tickets' => 'confirmation_number',
        'dashicons-nametag' => 'badge',
        'dashicons-clipboard' => 'assignment',
        'dashicons-heart' => 'favorite',
        'dashicons-megaphone' => 'campaign',
        'dashicons-schedule' => 'schedule',
        'dashicons-tide' => 'tide',
        'dashicons-rest-api' => 'api',
        'dashicons-code-standards' => 'code',
        'dashicons-activity' => 'track_changes',
        'dashicons-bbpress' => 'forum',
        'dashicons-buddypress' => 'people',
        'dashicons-community' => 'group',
        'dashicons-forums' => 'forum',
        'dashicons-friends' => 'people',
        'dashicons-groups' => 'group',
        'dashicons-pm' => 'message',
        'dashicons-replies' => 'reply',
        'dashicons-topics' => 'topic',
        'dashicons-tracking' => 'track_changes',
        'dashicons-wordpress' => 'wordpress',
        'dashicons-wordpress-alt' => 'wordpress',
        'dashicons-pressthis' => 'publish',
        'dashicons-update' => 'update',
        'dashicons-update-alt' => 'update',
        'dashicons-screen-options' => 'settings',
        'dashicons-info' => 'info',
        'dashicons-cart' => 'shopping_cart',
        'dashicons-feedback' => 'feedback',
        'dashicons-cloud' => 'cloud',
        'dashicons-translation' => 'translate',
        'dashicons-tag' => 'label',
        'dashicons-category' => 'category',
        'dashicons-archive' => 'archive',
        'dashicons-tagcloud' => 'cloud',
        'dashicons-text' => 'text_fields',
        'dashicons-bell' => 'notifications',
        'dashicons-yes' => 'check_circle',
        'dashicons-yes-alt' => 'check_circle',
        'dashicons-no' => 'cancel',
        'dashicons-no-alt' => 'cancel',
        'dashicons-plus' => 'add',
        'dashicons-plus-alt' => 'add',
        'dashicons-plus-alt2' => 'add',
        'dashicons-minus' => 'remove',
        'dashicons-dismiss' => 'cancel',
        'dashicons-marker' => 'place',
        'dashicons-star-filled' => 'star',
        'dashicons-star-half' => 'star_half',
        'dashicons-star-empty' => 'star_border',
        'dashicons-flag' => 'flag',
        'dashicons-warning' => 'warning',
        'dashicons-location' => 'place',
        'dashicons-location-alt' => 'place',
        'dashicons-vault' => 'lock',
        'dashicons-shield' => 'shield',
        'dashicons-shield-alt' => 'shield',
        'dashicons-sos' => 'emergency',
        'dashicons-search' => 'search',
        'dashicons-slides' => 'slideshow',
        'dashicons-text-page' => 'description',
        'dashicons-analytics' => 'analytics',
        'dashicons-chart-pie' => 'pie_chart',
        'dashicons-chart-line' => 'show_chart',
        'dashicons-chart-area' => 'area_chart',
        'dashicons-groups' => 'group',
        'dashicons-businessman' => 'business_center',
        'dashicons-businesswoman' => 'business_center',
        'dashicons-businessperson' => 'business_center',
        'dashicons-id' => 'badge',
        'dashicons-id-alt' => 'badge',
        'dashicons-products' => 'shopping_cart',
        'dashicons-awards' => 'emoji_events',
        'dashicons-forms' => 'assignment',
        'dashicons-testimonial' => 'feedback',
        'dashicons-portfolio' => 'work',
        'dashicons-book' => 'book',
        'dashicons-book-alt' => 'book',
        'dashicons-download' => 'download',
        'dashicons-upload' => 'upload',
        'dashicons-backup' => 'backup',
        'dashicons-clock' => 'access_time',
        'dashicons-lightbulb' => 'lightbulb',
        'dashicons-microphone' => 'mic',
        'dashicons-desktop' => 'desktop_mac',
        'dashicons-laptop' => 'laptop_mac',
        'dashicons-tablet' => 'tablet_mac',
        'dashicons-smartphone' => 'smartphone',
        'dashicons-phone' => 'phone',
        'dashicons-index-card' => 'note',
        'dashicons-carrot' => 'local_florist',
        'dashicons-building' => 'business',
        'dashicons-store' => 'store',
        'dashicons-album' => 'album',
        'dashicons-palm-tree' => 'palm_tree',
        'dashicons-tickets-alt' => 'confirmation_number',
        'dashicons-money' => 'attach_money',
        'dashicons-money-alt' => 'attach_money',
        'dashicons-smiley' => 'sentiment_satisfied',
        'dashicons-thumbs-up' => 'thumb_up',
        'dashicons-thumbs-down' => 'thumb_down',
        'dashicons-layout' => 'grid_view',
        'dashicons-paperclip' => 'attach_file',
        'dashicons-color-picker' => 'color_lens',
        'dashicons-edit-large' => 'edit',
        'dashicons-edit-page' => 'edit',
        'dashicons-airplane' => 'airplanemode_active',
        'dashicons-bank' => 'account_balance',
        'dashicons-beer' => 'local_bar',
        'dashicons-calculator' => 'calculate',
        'dashicons-car' => 'directions_car',
        'dashicons-coffee' => 'local_cafe',
        'dashicons-drumstick' => 'fastfood',
        'dashicons-food' => 'restaurant',
        'dashicons-fullscreen-alt' => 'fullscreen',
        'dashicons-fullscreen-exit-alt' => 'fullscreen_exit',
        'dashicons-games' => 'sports_esports',
        'dashicons-hourglass' => 'hourglass_empty',
        'dashicons-open-folder' => 'folder_open',
        'dashicons-pdf' => 'picture_as_pdf',
        'dashicons-pets' => 'pets',
        'dashicons-printer' => 'print',
        'dashicons-privacy' => 'privacy_tip',
        'dashicons-superhero' => 'supervised_user_circle',
        'dashicons-superhero-alt' => 'supervised_user_circle'
    );
    ?>
    <style>
        <?php foreach ($icons as $dashicon => $material_icon) : ?>
            .<?php echo esc_attr($dashicon); ?>:before {
                content: "<?php echo esc_html($material_icon); ?>"; /* Material Icon Name */
                font-family: 'Material Symbols Outlined' !important;
                font-size: 16px !important;
            }
        <?php endforeach; ?>
    </style>
    <?php
}
add_action('admin_head', 'replace_dashicons_with_material_icons', 1); // Very high priority
add_action('wp_head', 'replace_dashicons_with_material_icons', 1); // Very high priority for Frontend












