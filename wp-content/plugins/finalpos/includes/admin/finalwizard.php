<?php

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}
// Add a custom admin submenu under Final Dashboard for the Final POS Setup Wizard
function final_pos_add_admin_submenu()
{
    add_submenu_page(
        'final-admin-dashboard', // Parent slug
        __('Final Setup Wizard', 'final-pos'), // Page title
        __('Setup Wizard', 'final-pos'),        // Menu title
        'manage_options',                           // Capability
        'final-pos-setup',                         // Menu slug
        'final_pos_setup_wizard'                   // Callback function
    );
}
add_action('admin_menu', 'final_pos_add_admin_submenu');

// Add Content Security Policy headers only for the Final POS Setup Wizard page
function add_csp_headers()
{
    // Check if we're on the admin page and it's the Final POS Setup page
    $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
    $is_valid_nonce = wp_verify_nonce($nonce, 'final_wizard_action');
    $current_page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';

    if (is_admin() && $is_valid_nonce && $current_page === 'final-pos-setup') {
        header("Content-Security-Policy: default-src 'self' https: data: 'unsafe-inline' 'unsafe-eval'; font-src 'self' https: data: https://js.stripe.com; frame-ancestors 'self' https://hub.finalpos.com; frame-src https://hub.finalpos.com https://js.stripe.com; connect-src 'self' https://hub.finalpos.com https://js.stripe.com;");
    }
}
add_action('send_headers', 'add_csp_headers');

// Enqueue scripts and styles only on the final POS setup page
function enqueue_final_wizard($hook)
{
    if ($hook !== 'final-pos_page_final-pos-setup') {
        return;
    }

    // Enqueue CSS
    wp_enqueue_style('finalwizard', plugins_url('../../assets/css/finalwizard.css', __FILE__), array(), '1.0.0');

    // Enqueue JS
    wp_enqueue_script('finalwizard', plugins_url('../../assets/js/finalwizard.js', __FILE__), array('jquery'), '1.0.0', true);

    // Localize script with necessary data for AJAX
    $store_url = esc_url(get_site_url());
    $registration_url = "https://hub.finalpos.com/registration?plugin=true&store_url=" . urlencode($store_url);
    $login_url = "https://hub.finalpos.com/login?plugin=true&store_url=" . urlencode($store_url);

    wp_localize_script('finalwizard', 'finalWizardData', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('final_wizard_nonce'),
        'storeUrl' => esc_url(get_site_url()) . '/',
        'registrationUrl' => esc_url($registration_url),
        'loginUrl' => esc_url($login_url),
        'userId' => get_current_user_id(),
        'returnUrl' => admin_url('admin.php?page=final-pos-setup&step=wc-auth-complete'),
        'callbackUrl' => add_query_arg('wc-ajax', 'finalpos_save_token', home_url('/'))
    ));

    // Debugging information removed for production
    // error_log('finalWizardData: ' . print_r(wp_json_encode(array(
    //     'ajaxurl' => admin_url('admin-ajax.php'),
    //     'nonce' => wp_create_nonce('final_wizard_nonce'),
    //     'storeUrl' => esc_url(get_site_url()) . '/',
    //     'registrationUrl' => esc_url($registration_url),
    //     'loginUrl' => esc_url($login_url),
    //     'userId' => get_current_user_id(),
    //     'returnUrl' => admin_url('admin.php?page=final-pos-setup&step=wc-auth-complete'),
    //     'callbackUrl' => add_query_arg('wc-ajax', 'finalpos_save_token', home_url('/'))
    // )), true));
}
add_action('admin_enqueue_scripts', 'enqueue_final_wizard');

// Render the setup wizard
function final_pos_setup_wizard()
{
    global $finalWizardData;
    $finalWizardData = array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('final_wizard_nonce'),
        'storeUrl' => esc_url(get_site_url()) . '/',
        // Add other necessary data here
    );
    $store_url = esc_url(get_site_url());
    $current_step = 1;
    $authorization_denied = false;

    // Verify nonce for GET parameters
    $nonce_verified = isset($_REQUEST['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])), 'final_wizard_action');

    // Handle WooCommerce Auth return with nonce verification
    if ($nonce_verified && isset($_GET['step'])) {
        $step = sanitize_text_field(wp_unslash($_GET['step']));

        if ($step === 'wc-auth-complete') {
            if (isset($_GET['error'])) {
                $error = sanitize_text_field(wp_unslash($_GET['error']));
                if ($error === 'access_denied') {
                    $authorization_denied = true;
                }
            } else {
                // Set a transient to indicate that the wizard is complete
                set_transient('final_wizard_complete', true, 5 * MINUTE_IN_SECONDS);
                // Redirect to admin dashboard if WooCommerce auth is complete
                wp_safe_redirect(admin_url());
                exit;
            }
        }
    }

    // Handle WooCommerce Auth return
    if (isset($_GET['step']) && $_GET['step'] === 'wc-auth-complete') {
        if (!isset($_GET['error'])) {
            // WooCommerce Auth war erfolgreich
            update_option('final_wizard_status', 'wc_auth_complete');
            // for future use - indicate that the sync has been done at least once in this store in the past. 
            // won't be removed even if the plugin is uninstalled
            update_option('final_wizard_did_ever_sync', true);
            // Redirect zur Admin-Seite mit speziellem Parameter
            wp_safe_redirect(admin_url('admin.php?page=final-pos-setup&sync=start'));
            exit;
        } else {
            // Auth fehlgeschlagen
            update_option('final_wizard_status', 'wc_auth_failed');
        }
    }

    // Load saved options with defaults
    $order_timeframe = get_option('final_pos_order_timeframe', 30);
    $ui_choice = get_option('final_pos_ui_choice', 'modern');
    $sync_status = get_option('final_pos_sync_status', array('products' => true, 'orders' => true, 'customers' => true));

    // Ensure $sync_status is an array
    if (!is_array($sync_status)) {
        $sync_status = array('products' => true, 'orders' => true, 'customers' => true);
    }

    // Generate the iFrame URLs
    $registration_url = "https://hub.finalpos.com/registration?plugin=true&store_url={$store_url}";
    $login_url = "https://hub.finalpos.com/login?plugin=true&store_url={$store_url}";

    // Output the wizard HTML
    echo '<div id="final-pos-wizard" class="wizard-container" data-store-url="' . esc_attr($store_url) . '">';
    ?>
    <header>
        <img src="<?php echo esc_url(plugins_url('finalpos/assets/img/ui/final-logo.png')); ?>"
            alt="<?php esc_attr_e('Final Logo', 'final-pos'); ?>" class="final-logo">
    </header>

    <span class="material-symbols-outlined close-icon" onclick="closeWizard()">close</span>

    <div class="wizard-content">
        <?php if ($authorization_denied): ?>
            <div class="notice notice-error">
                <p><?php esc_html_e('You need to approve the WooCommerce Auth Screen to use FinalPOS sync features', 'final-pos'); ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- Step 1: Info Page -->
        <div class="wizard-step active" id="step1">
            <div class="content">
                <h1><?php esc_html_e('Welcome to Final POS Builder! Here are the steps to set up your website with Final:', 'final-pos'); ?>
                </h1>
                <div class="setup-steps">
                    <div class="step-item">
                        <div class="step-number">1</div>
                        <div class="step-info">
                            <h3><?php esc_html_e('Sign In to an existing account or Sign Up in Final', 'final-pos'); ?></h3>
                            <p><?php esc_html_e('Enter your and your business\'s information', 'final-pos'); ?></p>
                            <p><?php esc_html_e('Choose your subscription Plan', 'final-pos'); ?></p>
                        </div>
                    </div>
                    <div class="step-item">
                        <div class="step-number">2</div>
                        <div class="step-info">
                            <h3><?php esc_html_e('Sync your WooCommerce Store in Final', 'final-pos'); ?></h3>
                            <p><?php esc_html_e('By syncing your store, these items will be synced:', 'final-pos'); ?></p>
                            <div class="sync-items">
                                <div class="sync-item"><?php esc_html_e('Products', 'final-pos'); ?></div>
                                <div class="sync-item"><?php esc_html_e('Customers', 'final-pos'); ?></div>
                                <div class="sync-item"><?php esc_html_e('Orders', 'final-pos'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="login-options">
                    <h2><?php esc_html_e('Sign In to your Final account to get started!', 'final-pos'); ?></h2>
                    <button type="button" class="signin-btn"
                        onclick="nextStep(2)"><?php esc_html_e('Sign In to an existing account', 'final-pos'); ?></button>
                    <p class="signup-text"><?php esc_html_e('Don\'t have an account yet? ', 'final-pos'); ?><a href="#"
                            id="signUpLink"><?php esc_html_e('Sign Up', 'final-pos'); ?></a></p>
                </div>
                <div class="overview">
                    <h2><?php esc_html_e('Overview:', 'final-pos'); ?></h2>
                    <div class="icon-boxes">
                        <div class="icon-box-item">
                            <span class="material-symbols-outlined">point_of_sale</span>
                            <h3><?php esc_html_e('What is Final POS Builder?', 'final-pos'); ?></h3>
                            <p><?php esc_html_e('Final is the world\'s first point of sale builder. Make a unique and customized POS experience matching your needs.', 'final-pos'); ?>
                            </p>
                        </div>
                        <div class="icon-box-item">
                            <span class="material-symbols-outlined">sync</span>
                            <h3><?php esc_html_e('Always in sync with your online store', 'final-pos'); ?></h3>
                            <p><?php esc_html_e('Seamlessly integrate your brick-and-mortar store with your online presence, ensuring real-time synchronization of inventory and data.', 'final-pos'); ?>
                            </p>
                        </div>
                        <div class="icon-box-item">
                            <span class="material-symbols-outlined">devices</span>
                            <h3><?php esc_html_e('Desktop, mobile, kiosk and everything else.', 'final-pos'); ?></h3>
                            <p><?php esc_html_e('Design your POS interface for any device, from desktop to mobile, and bring your custom build to life with the Final POS app.', 'final-pos'); ?>
                            </p>
                        </div>
                        <div class="icon-box-item">
                            <span class="material-symbols-outlined">settings</span>
                            <h3><?php esc_html_e('Customizable and flexible', 'final-pos'); ?></h3>
                            <p><?php esc_html_e('Enjoy extreme control over your checkout process with dynamic fields, automation, and conditional logic.', 'final-pos'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 2: iFrame -->
        <div class="wizard-step" id="step2">
            <div class="final-pos-iframe-container">
                <iframe id="final-pos-iframe" src="" width="100%" height="100%" frameborder="0"></iframe>
            </div>
        </div>

        <!-- Step 3: Sync Settings -->
        <div class="wizard-step" id="step3">
            <div class="two-column">
                <div class="column-left">
                    <div class="content">
                        <button type="button" class="back-btn" onclick="prevStep(2)">←
                            <?php esc_html_e('Back', 'final-pos'); ?></button>
                        <h1><?php esc_html_e('Connection ready!', 'final-pos'); ?></h1>
                        <h2 class="step-title"><?php esc_html_e('Help us to set up your Dashboard!', 'final-pos'); ?></h2>
                        <p><?php esc_html_e('Choose the types you want to sync from your WooCommerce store to Final POS:', 'final-pos'); ?>
                        </p>
                        <div class="toggle-group">
                            <?php foreach (['products' => 'Products', 'orders' => 'Orders', 'customers' => 'Customers'] as $key => $label): ?>
                                <div class="toggle-item">
                                    <div class="toggle-label">
                                        <div class="icon-box">
                                            <?php include(plugin_dir_path(__FILE__) . '../../assets/img/icons/' . $key . '.svg'); ?>
                                        </div>
                                        <div>
                                            <label for="<?php echo esc_attr($key); ?>"
                                                class="toggle-labeltext"><?php echo esc_html($label); ?></label>
                                            <p class="toggle-description">
                                                <?php esc_html_e('Syncing all categories.', 'final-pos'); ?>
                                                <?php echo esc_html(ucfirst($label)); ?>
                                            </p>
                                            <?php if ($key !== 'customers'): ?>
                                                <a href="#" class="configure-link"
                                                    data-popup="<?php echo esc_attr($key); ?>"><?php esc_html_e('Configure your sync terms', 'final-pos'); ?></a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="toggle-control">
                                        <label class="switch">
                                            <input type="checkbox" id="<?php echo esc_attr($key); ?>" <?php checked(isset($sync_status[$key]) ? $sync_status[$key] : true); ?>>
                                            <span class="slider round"></span>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <!-- Instead of nextStep(4), directly call redirectToWcAuth() -->
                        <button type="button" class="next-btn"
                            onclick="redirectToWcAuth()"><?php esc_html_e('Authorize', 'final-pos'); ?></button>
                    </div>
                </div>
                <div class="column-right"
                    style="background-image: url('<?php echo esc_url(plugins_url('finalpos/assets/img/wizard-step1.jpg')); ?>');">
                </div>
            </div>
        </div>

        <!-- Step 4: UI Selection - REMOVED COMPLETELY -->
        <!-- <div class="wizard-step" id="step4">
                <div class="two-column">
                    <div class="column-left">
                        <div class="content">
                            <button type="button" class="back-btn" onclick="prevStep(3)">← <?php esc_html_e('Back', 'final-pos'); ?></button>
                            <h1><?php esc_html_e('One more step!', 'final-pos'); ?></h1>
                            <h2 class="step-title"><?php esc_html_e('Choose your Dashboard UI!', 'final-pos'); ?></h2>
                            
                            <p><?php esc_html_e('We\'ve redesigned the WordPress Admin interface for a cleaner, more intuitive experience.', 'final-pos'); ?></p>
                            
                            <h4><?php esc_html_e('Highlighted Improvements:', 'final-pos'); ?></h4>
                            <ul class="feature-list">
                                <li><?php esc_html_e('Modernized Backend UI', 'final-pos'); ?></li>
                                <li><?php esc_html_e('Overview Dashboard', 'final-pos'); ?></li>
                                <li><?php esc_html_e('Sorted Admin Menu', 'final-pos'); ?></li>
                                <li><?php esc_html_e('Advanced Search', 'final-pos'); ?></li>
                            </ul>

                            <div class="ui-selector">
                                <label>
                                    <input type="radio" name="ui-choice" value="modern" <?php checked($ui_choice, 'modern'); ?>>
                                    <?php esc_html_e('Embrace the New Look (recommended)', 'final-pos'); ?>
                                    <span class="radio-description"><?php esc_html_e('Yes, I want to switch to the new UI!', 'final-pos'); ?></span>
                                </label>
                                <label>
                                    <input type="radio" name="ui-choice" value="classic" <?php checked($ui_choice, 'classic'); ?>>
                                    <?php esc_html_e('Stick with classic', 'final-pos'); ?>
                                    <span class="radio-description"><?php esc_html_e('No, I prefer the traditional design.', 'final-pos'); ?></span>
                                </label>
                            </div>

                            <button type="button" class="next-btn" onclick="redirectToWcAuth()"><?php esc_html_e('Authorize', 'final-pos'); ?></button>
                        </div>
                    </div>
                    <div class="column-right" id="ui-preview"></div>
                </div>
            </div> -->

    </div>

    <!-- Popups -->
    <div id="productsPopup" class="setup-popup">
        <div class="setup-popup-content">
            <span class="close-popup">&times;</span>
            <h2><?php esc_html_e('Product Categories', 'final-pos'); ?></h2>
            <p><?php esc_html_e('We will sync all your product categories, or you can choose specific categories to sync from your WooCommerce store to Final POS.', 'final-pos'); ?>
            </p>

            <div class="category-list-container">
                <h3><?php esc_html_e('Category list', 'final-pos'); ?></h3>
                <div class="search-container">
                    <input type="text" id="categorySearch"
                        placeholder="<?php esc_attr_e('Search for category', 'final-pos'); ?>">
                    <span class="material-symbols-outlined search-icon">search</span>
                </div>
                <div class="category-list">
                    <label class="category-item">
                        <input type="checkbox" id="allCategories" checked>
                        <span class="category-name"><?php esc_html_e('All categories', 'final-pos'); ?></span>
                    </label>
                    <?php
                    // Recursive function to display hierarchical categories
                    function display_category_hierarchical($category,$depth=0){
                        $indent=str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;',$depth);
                        $sync_status=get_term_meta($category->term_id,'final_sync',true);
                        $checked=$sync_status?'checked':'';
                        echo'<label class="category-item" style="padding-left:'.($depth*20).'px;">';
                        echo$indent.'<input type="checkbox" name="product_category[]" value="'.$category->term_id.'" data-parent-id="'.$category->parent.'" '.$checked.'>';
                        echo'<span class="category-name">'.esc_html($category->name).'</span></label>';
                        $child_categories=get_terms(['taxonomy'=>'product_cat','hide_empty'=>false,'parent'=>$category->term_id]);
                        if(!empty($child_categories)){
                         foreach($child_categories as $child_category){
                          display_category_hierarchical($child_category,$depth+1);
                         }
                        }
                       }
                    $top_level_categories = get_terms(array(
                        'taxonomy' => 'product_cat',
                        'hide_empty' => false,
                        'parent' => 0
                    ));

                    foreach ($top_level_categories as $category) {
                        display_category_hierarchical($category, 0);
                    }
                    ?>
                </div>
            </div>
            <button type="button" class="save-categories"><?php esc_html_e('Save', 'final-pos'); ?></button>
        </div>
    </div>

    <div id="ordersPopup" class="setup-popup">
        <div class="setup-popup-content">
            <span class="close-popup">&times;</span>
            <h2><?php esc_html_e('Order timeframe', 'final-pos'); ?></h2>
            <p><?php esc_html_e('We will sync online orders from your WooCommerce store to Final POS for the last 30 days, or you can select a specific time period:', 'final-pos'); ?>
            </p>
            <p class="infotext">
                <?php esc_html_e('Please note that extending the timeframe might affect your device\'s performance.', 'final-pos'); ?>
            </p>

            <div class="timeframe-list">
                <?php
                $timeframes = array(7, 30, 60, 90, 365, 730);
                foreach ($timeframes as $days) {
                    $checked = $order_timeframe == $days ? 'checked' : '';
                    echo '<label class="timeframe-item">';
                    echo '<input type="radio" name="order_timeframe" value="' . esc_attr($days) . '" ' . esc_attr($checked) . '>';
                    echo '<span class="timeframe-name">' . esc_html('Last ' . ($days == 365 ? '1 year' : ($days == 730 ? '2 years' : $days . ' days'))) . '</span>';
                    echo '</label>';
                }
                ?>
            </div>
            <button type="button" class="save-timeframe"><?php esc_html_e('Save', 'final-pos'); ?></button>
        </div>
    </div>
    </div>
    <?php
}

// New AJAX handler to save all wizard settings at once
function save_all_wizard_settings()
{
    // Properly sanitize and unslash the nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'final_wizard_nonce')) {
        wp_send_json_error('Invalid nonce');
        return;
    }

    // Properly sanitize and unslash the settings
    $raw_settings = isset($_POST['settings']) ? sanitize_text_field(wp_unslash($_POST['settings'])) : '';
    $settings = !empty($raw_settings) ? json_decode($raw_settings, true) : array();

    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error('Invalid JSON in settings: ' . json_last_error_msg());
        return;
    }

    $valid_keys = array('category_sync', 'order_timeframe', 'order_timeframe_start', 'ui_choice', 'sync_status');
    $updated = array();

    foreach ($valid_keys as $key) {
        if (isset($settings[$key])) {
            $option_name = 'final_pos_' . $key;
            // Ensure the setting value is sanitized before saving
            $sanitized_value = is_array($settings[$key]) ?
                array_map('sanitize_text_field', $settings[$key]) :
                sanitize_text_field($settings[$key]);
            $success = update_option($option_name, $sanitized_value);
            $updated[$key] = $success;
        }
    }

    wp_send_json_success($updated);
}
add_action('wp_ajax_save_all_wizard_settings', 'save_all_wizard_settings');


// Add a custom admin submenu under Settings for the Final POS Setup Wizard
function final_pos_add_wc_auth_submenu()
{
    add_submenu_page(
        'final-pos-setup',
        __('WC Auth', 'final-pos'),
        __('WC Auth', 'final-pos'),
        'manage_options',
        'wc-auth',
        'final_pos_wc_auth_page'
    );
}
add_action('admin_menu', 'final_pos_add_wc_auth_submenu');

function final_pos_wc_auth_page()
{
    // Hier den Inhalt der wc-auth Seite implementieren
    echo '<div class="wrap">';
    echo '<h1>' . esc_html(__('WooCommerce Authorization', 'final-pos')) . '</h1>';
    echo '<p>' . esc_html(__('Please authorize FinalPOS to access your WooCommerce data.', 'final-pos')) . '</p>';
    echo '<button id="authorize-wc" class="button button-primary">' . esc_html(__('Authorize', 'final-pos')) . '</button>';
    echo '</div>';
}

// Am Ende der Datei hinzufügen oder überprüfen, ob dies bereits vorhanden ist:

function final_pos_save_wizard_status()
{
    check_ajax_referer('final_wizard_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $status = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : '';

    // Speichere den Status
    update_option('final_wizard_status', $status);

    // Wenn der Status 'done' ist, warten wir auf die WC Auth
    if ($status === 'done') {
        // Hier keine weitere Aktion - wir warten auf den WC Auth Callback
        wp_send_json_success('Waiting for WC Auth');
    }

    wp_send_json_success('Status updated');
}
add_action('wp_ajax_save_wizard_status', 'final_pos_save_wizard_status');

// Neue AJAX-Handler-Funktion zum Speichern des Access Tokens
function save_access_token()
{
    check_ajax_referer('final_wizard_nonce', 'nonce');

    $token = isset($_POST['token']) ? sanitize_text_field(wp_unslash($_POST['token'])) : '';
    $company_id = isset($_POST['company_id']) ? sanitize_text_field(wp_unslash($_POST['company_id'])) : '';

    // Debugging information removed for production
    // error_log('Attempting to save access token and company ID');

    if (empty($token) || empty($company_id)) {
        // Debugging information removed for production
        // error_log('Token or Company ID is empty');
        wp_send_json_error('Token or Company ID is empty');
        return;
    }

    $current_token = get_option('final_pos_access_token');
    $current_company_id = get_option('final_pos_company_id');

    $token_updated = ($token !== $current_token);
    $company_updated = ($company_id !== $current_company_id);

    if ($token_updated) {
        update_option('final_pos_access_token', $token, false);
    }

    if ($company_updated) {
        update_option('final_pos_company_id', $company_id, false);
    }

    if ($token_updated || $company_updated) {
        // Debugging information removed for production
        // error_log('Access token and/or company ID updated successfully');
        wp_send_json_success('Access token and/or company ID updated successfully');
    } else {
        // Debugging information removed for production
        // error_log('Access token and company ID remain unchanged');
        wp_send_json_success('Access token and company ID remain unchanged');
    }
}
add_action('wp_ajax_save_access_token', 'save_access_token');

