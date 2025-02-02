<?php
/**
 * Theme functions and definitions.
 *
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * https://developers.elementor.com/docs/hello-elementor-theme/
 *
 * @package HelloElementorChild
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define('HELLO_ELEMENTOR_CHILD_VERSION', '2.0.0');
define('HELLO_ELEMENTOR_CHILD_THEME_PATH', get_stylesheet_directory());
include 'includes/classes/class-wc-custom-emails-manager.php';
// include 'includes/classes/class-wc-custom-sms-manager.php';
include 'includes/classes/class-mailchimp-subscribers-table.php';
// include HELLO_ELEMENTOR_CHILD_THEME_PATH .'/integration/functions.php';
include 'includes/shortcodes.php';

remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );

/**
 * If the function, `supavapes_wp_enqueue_scripts_callback` doesn't exist.
 */
if ( ! function_exists( 'supavapes_wp_enqueue_scripts_callback' ) ) {
	/**
	 * Enqueue the custom scripts and styles for the site front.
	 *
	 * @since 1.0.0
	 */
	function supavapes_wp_enqueue_scripts_callback() {
		/************************ ENQUEUE CSS FILES ************************/

		// Enqueue slick minified style.
		wp_enqueue_style(
			'sv-slick-min-style',
			get_stylesheet_directory_uri() . '/assets/css/slick_slider/slick.min.css',
			array(),
			filemtime( get_stylesheet_directory() . '/assets/css/slick_slider/slick.min.css' ),
			'all'
		);

		// Enqueue slick theme minified style.
		wp_enqueue_style(
			'sv-slick-theme-style',
			get_stylesheet_directory_uri() . '/assets/css/slick_slider/slick-theme.min.css',
			array(),
			filemtime( get_stylesheet_directory() . '/assets/css/slick_slider/slick-theme.min.css' ),
			'all'
		);

		// Enqueue slick theme minified style.
		wp_enqueue_style(
			'sv-jquery-ui-css',
			get_stylesheet_directory_uri() . '/assets/css/jquery-ui.min.css',
			array(),
			filemtime( get_stylesheet_directory() . '/assets/css/jquery-ui.min.css' ),
			'all'
		);

		// Enqueue the child theme style file.
		wp_enqueue_style(
			'hello-elementor-child-style',
			get_stylesheet_directory_uri() . '/style.css',
			array( 'hello-elementor-theme-style' ),
			filemtime( get_stylesheet_directory() . '/style.css' ), 
			'all'
		);

		// Enqueue the child theme custom style file.
		wp_enqueue_style(
			'hello-elementor-child-custom',
			get_stylesheet_directory_uri() . '/assets/css/custom.css',
			array(),
			filemtime( get_stylesheet_directory() . '/assets/css/custom.css' ),
			'all'
		);

		// Enqueue the chile theme media style file.
		wp_enqueue_style(
			'hello-elementor-child-media',
			get_stylesheet_directory_uri() . '/assets/css/media.css',
			array(),
			filemtime( get_stylesheet_directory() . '/assets/css/media.css' ),
			'all'
		);

		/************************ ENQUEUE JS FILES ************************/

		// Enqueue the slick script file.
		wp_enqueue_script(
			'sv-slick-slider-js',
			get_stylesheet_directory_uri() . '/assets/js/slick.js',
			array(),
			filemtime( get_stylesheet_directory() . '/assets/js/slick.js' ),
			true
		);

		// Enqueue the chart script file.
		if ( is_account_page() ) {
			wp_enqueue_script(
				'sv-chart-js',
				get_stylesheet_directory_uri() . '/assets/js/chart.js',
				array(),
				filemtime( get_stylesheet_directory() . '/assets/js/chart.js' ),
				true
			);
		}

		// Enqueue the jQuery UI dialog script.
		wp_enqueue_script( 'jquery-ui-dialog' );

		// Enqueue the custom script file.
		wp_enqueue_script(
			'sv-custom-js',
			get_stylesheet_directory_uri() . '/assets/js/custom.js',
			array(),
			filemtime( get_stylesheet_directory() . '/assets/js/custom.js' ),
			true
		);

		// Enqueue the custom frontend script file.
		wp_enqueue_script(
			'sv-frontend-js',
			get_stylesheet_directory_uri() . '/assets/js/frontend.js',
			array(),
			filemtime( get_stylesheet_directory() . '/assets/js/frontend.js' ),
			true
		);

		
		if ( function_exists( 'is_cart' ) && is_cart() || function_exists( 'is_checkout' ) && is_checkout() ) {
			wp_enqueue_script(
				'custom-block-notices',
				get_stylesheet_directory_uri() . '/assets/js/custom-block-notices.js',
				array( 'wp-element', 'wp-data', 'wc-blocks-checkout' ), // Ensure dependencies for WooCommerce Blocks
				'1.0',
				true
			);
		}

		// Localize the custom script variables.
		wp_localize_script(
			'sv-custom-js',
			'sv_ajax',
			array(
				'ajax_url'                     => admin_url( 'admin-ajax.php' ),
				'cart_url'                     => wc_get_cart_url(),
				'site_url'                     => get_site_url(),
				'verify_age_disagree_btn'      => get_field( 'verify_age_disagree_button_url', 'option' ),
				'nonce'                        => wp_create_nonce( 'quick_view_nonce' ),
				'search_variations_nonce'      => wp_create_nonce( 'search_variations_nonce' ),
				'quick_view_add_to_cart_nonce' => wp_create_nonce( 'quick_view_add_to_cart_nonce' ),
				'failure_attempt_nonce'        => wp_create_nonce( 'failure_attempt_nonce' ),
				'fun_questionnaire_nonce'      => wp_create_nonce( 'fun_questionnaire_nonce' ),
				'current_user'                 => ( is_user_logged_in() ) ? get_current_user_id() : 0,
				'payment_fail_counter'         => get_field( 'counter_for_prevent_user_to_place_order','option' ),
			)
		);
	}
}

add_action( 'wp_enqueue_scripts', 'supavapes_wp_enqueue_scripts_callback', 20 );

/**
 * If the function, `supavapes_admin_enqueue_scripts_callback` doesn't exist.
 */
if ( ! function_exists( 'supavapes_admin_enqueue_scripts_callback' ) ) {
	/**
	 * Enqueue the custom scripts and styles for the site admin dashboard.
	 *
	 * @since 1.0.0
	 */
	function supavapes_admin_enqueue_scripts_callback() {
		$page   = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$action = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		// Enqueue media styles and scripts on the order edit screen.
		if ( 'wc-orders' === $page && 'edit' === $action ) {
			wp_enqueue_media();
		}

		// Enqueue the custom style for the admin dashboard.
		wp_enqueue_style(
			'supavapes-custom-admin-style',
			get_stylesheet_directory_uri() . '/assets/css/admin-style.css',
			array(),
			filemtime( get_stylesheet_directory() . '/assets/css/admin-style.css' ).
			'all'
		);

		// Enqueue the custom script for the admin dashboard.
		wp_enqueue_script(
			'supavapes-custom-admin-script',
			get_stylesheet_directory_uri() . '/assets/js/admin-script.js',
			array( 'jquery' ),
			filemtime( get_stylesheet_directory() . '/assets/js/admin-script.js' ),
			true
		);

		// Localize the custom script variables.
		wp_localize_script(
			'supavapes-custom-admin-script',
			'SupaVapesCustomAdminScript',
			array(
				'ajax_url'                         => admin_url( 'admin-ajax.php' ),
				'nonce'                            => wp_create_nonce( 'support_request_nonce' ),
				'media_uploader_modal_header'      => __( 'Upload Order Notes Attachments', 'supavapes' ),
				'review_attachments_allowed_types' => array( '.jpeg', '.jpg', '.png' ),
			)
		);
	}
}

add_action( 'admin_enqueue_scripts', 'supavapes_admin_enqueue_scripts_callback' );

/**
 * If the function, `supavapes_init_callback` doesn't exist.
 */
if ( ! function_exists( 'supavapes_init_callback' ) ) {
	/**
	 * Add custom action on WordPress initialization.
	 *
	 * @since 1.0.0
	 */
	function supavapes_init_callback() {
		// Enqueue custom endpoints for customer dashboard.
		add_rewrite_endpoint( 'notification-preference', EP_ROOT | EP_PAGES );
		add_rewrite_endpoint( 'wishlist', EP_ROOT | EP_PAGES );
		add_rewrite_endpoint( 'support-request', EP_ROOT | EP_PAGES );
		add_rewrite_endpoint('view-request', EP_ROOT | EP_PAGES);

		// Register custom post types.
		require_once get_stylesheet_directory() . '/includes/custom-post-types/index.php';

		// Register custom taxonomies.
		require_once get_stylesheet_directory() . '/includes/custom-taxonomies/index.php';

		// Start custom session.
		if ( ! session_id() ) {
			session_start();
		}
	}
}

add_action( 'init', 'supavapes_init_callback' );

/**
 * If the function, `supavapes_woocommerce_add_to_cart_fragments_callback` doesn't exist.
 */
if ( ! function_exists( 'supavapes_woocommerce_add_to_cart_fragments_callback' ) ) {
	/**
	 * Function to update mini cart fragment
	 * 
	 * @param array $fragments This variable holds the fragments value array.
	 * @return array
	 * @since 1.0.0
	 */
	function supavapes_woocommerce_add_to_cart_fragments_callback( $fragments ) {
		$fragments['span.cart-counter'] = '<span class="cart-counter">' . WC()->cart->get_cart_contents_count() . '</span>';
		
		return $fragments;
		
	}
}

add_filter( 'woocommerce_add_to_cart_fragments', 'supavapes_woocommerce_add_to_cart_fragments_callback' );

/**
 * If the function, `supavapes_body_class_callback` doesn't exist.
 */
if ( ! function_exists( 'supavapes_body_class_callback' ) ) {
	/**
	 * Add custom classes to the body tag.
	 *
	 * @param $classes array Body classes.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	function supavapes_body_class_callback( $classes ) {
		$classes[] = 'sv-popup-open';

		if ( ! is_user_logged_in() && is_account_page() ) {
			$classes[] = 'sv-logged-out';
		}

		return $classes;
	}
}

add_filter( 'body_class', 'supavapes_body_class_callback' );

/**
 * If the function, `supavapes_wp_footer_callback` doesn't exist.
 */
if ( ! function_exists( 'supavapes_wp_footer_callback' ) ) {
	/**
	 * Function to display whatsapp chat icon
	 *
	 * @since 1.0.0
	 * 
	 */
	function supavapes_wp_footer_callback() {
		require_once get_stylesheet_directory() . '/templates/woocommerce/render-whatsapp-chat-icon.php';

		// Render the modal for product quick view.
		require_once get_stylesheet_directory() . '/templates/modals/quick-view-modal.php';

		// Render the modal to prevent checkout after a certain number of failures.
		if( is_checkout() ) {
			require_once get_stylesheet_directory() . '/templates/modals/checkout-prevent-popup.php';
		}

		// Verify age modal when website opens.
		require_once get_stylesheet_directory() . '/templates/modals/verify-age-modal.php';

		require_once get_stylesheet_directory() . '/templates/modals/location-popup.php';

		// Show the store availability data.
		if ( is_product() ) {
			require_once get_stylesheet_directory() . '/templates/modals/store-data-popup.php';
		}

		// Site pre-loader.
		ob_start();
		?>
		<div class="pre-loader_page" id="loader">
			<div class="loader_row">
				<span class="sv-loader"></span>
			</div>
		</div>
		<?php
		echo ob_get_clean();
	}
}

add_action( 'wp_footer', 'supavapes_wp_footer_callback', 99 );

/**
 * If the function, `supavapes_widgets_init_callback` doesn't exist.
 */
if ( ! function_exists( 'supavapes_widgets_init_callback' ) ) {
	/**
	 * Function to Register sidebars for the shop page filters.
	 *
	 * @since 1.0.0
	 */
	function supavapes_widgets_init_callback() {
		// Register sidebar to show the product categories.
		register_sidebar(
			array(
				'name'          => __( 'Product Category', 'supavapes' ),
				'id'            => 'product-cat-widgets',
				'description'   => __( 'Widgets in this area will be shown on shop page to list product categories', 'supavapes' ),
				'before_widget'	=> '<div class="widget-wrap">',
				'after_widget'	=> '</div>',
				'before_title'	=> '<h4 class="widget-title">',
				'after_title'	=> '</h4>',
			)
		);

		// Register sidebar to show the product filters.
		register_sidebar(
			array(
				'name'          => __( 'Product Filters', 'supavapes' ),
				'id'            => 'product-cat-filters',
				'description'   => __( 'Widgets in this area will be shown on shop page to filter products', 'supavapes' ),
				'before_widget'	=> '<div class="widget-wrap">',
				'after_widget'	=> '</div>',
				'before_title'	=> '<h4 class="widget-title">',
				'after_title'	=> '</h4>',
			)
		);
	}
}

add_action( 'widgets_init', 'supavapes_widgets_init_callback' );

/**
 * If the function, `supavapes_acf_init_callback` doesn't exist.
 */
if ( ! function_exists( 'supavapes_acf_init_callback' ) ) {
	/**
	 * Initialize the ACF fields.
	 *
	 * @since 1.0.0
	 */
	function supavapes_acf_init_callback() {
		if ( function_exists( 'acf_add_options_page' ) ) {
			acf_add_options_page(
				array(
					'menu_title' => __( 'SupaVapes', 'supavapes' ),
					'menu_slug'  => 'supavapes-settings',
				)
			);

			$acf_subpages = array(
				array(
					'page_title'  => __( 'Verify Age Popup Settings', 'supavapes' ),
					'menu_title'  => __( 'Verify Age Popup Settings', 'supavapes' ),
					'parent_slug' => 'supavapes-settings',
				),
				array(
					'page_title'  => __( 'Subscriber Modal Popup Settings', 'supavapes' ),
					'menu_title'  => __( 'Subscriber Modal Popup Settings', 'supavapes' ),
					'parent_slug' => 'supavapes-settings',
				),
				array(
					'page_title'  => __( 'Mailchimp Settings', 'supavapes' ),
					'menu_title'  => __( 'Mailchimp Settings', 'supavapes' ),
					'parent_slug' => 'supavapes-settings',
				),
				array(
					'page_title'  => __( 'Announcement Top Bar', 'supavapes' ),
					'menu_title'  => __( 'Announcement Top Bar', 'supavapes' ),
					'parent_slug' => 'supavapes-settings',
				),
				array(
					'page_title'  => __( 'Twilio Notifications', 'supavapes' ),
					'menu_title'  => __( 'Twilio Notifications', 'supavapes' ),
					'parent_slug' => 'supavapes-settings',
				),
				array(
					'page_title'  => __( 'Fun Questionnaries', 'supavapes' ),
					'menu_title'  => __( 'Fun Questionnaries', 'supavapes' ),
					'parent_slug' => 'supavapes-settings',
				),
				array(
					'page_title'  => __( 'Shop & Category Settings', 'supavapes' ),
					'menu_title'  => __( 'Shop & Category Settings', 'supavapes' ),
					'parent_slug' => 'supavapes-settings',
				),
				array(
					'page_title'  => __( 'Cart', 'supavapes' ),
					'menu_title'  => __( 'Cart', 'supavapes' ),
					'parent_slug' => 'supavapes-settings',
				),
				array(
					'page_title'  => __( 'Checkout', 'supavapes' ),
					'menu_title'  => __( 'Checkout', 'supavapes' ),
					'parent_slug' => 'supavapes-settings',
				),
				array(
					'page_title'  => __( 'Customer Dashboard', 'supavapes' ),
					'menu_title'  => __( 'Customer Dashboard', 'supavapes' ),
					'parent_slug' => 'supavapes-settings',
				),
				array(
					'page_title'  => __( 'Product Individual Page', 'supavapes' ),
					'menu_title'  => __( 'Product Individual Page', 'supavapes' ),
					'parent_slug' => 'supavapes-settings',
				),
				array(
					'page_title'  => __( 'Jokes: Customer Dashboard', 'supavapes' ),
					'menu_title'  => __( 'Jokes: Customer Dashboard', 'supavapes' ),
					'parent_slug' => 'supavapes-settings',
				),
				array(
					'page_title'  => __( 'Customer Support Request', 'supavapes' ),
					'menu_title'  => __( 'Customer Support Request', 'supavapes' ),
					'parent_slug' => 'supavapes-settings',
				),
				array(
					'page_title'  => __( 'WC Email Content', 'supavapes' ),
					'menu_title'  => __( 'WC Email Content', 'supavapes' ),
					'parent_slug' => 'supavapes-settings',
				),
				array(
					'page_title'  => __( 'Vaping Duty', 'supavapes' ),
					'menu_title'  => __( 'Vaping Duty', 'supavapes' ),
					'parent_slug' => 'supavapes-settings',
				),
				array(
					'page_title'  => __( 'Location Popup', 'supavapes' ),
					'menu_title'  => __( 'Location Popup', 'supavapes' ),
					'parent_slug' => 'supavapes-settings',
				),
			);

			if ( ! empty( $acf_subpages ) && is_array( $acf_subpages ) ) {
				foreach ( $acf_subpages as $subpage ) {
					acf_add_options_sub_page(
						array(
							'page_title'  => $subpage['page_title'],
							'menu_title'  => $subpage['menu_title'],
							'parent_slug' => $subpage['parent_slug'],
						)
					);
				}
			}
		}
	}
}

add_action( 'acf/init', 'supavapes_acf_init_callback' );

/**
 * If the function, `supavapes_woocommerce_product_get_rating_html_callback` doesn't exist.
 */
if ( ! function_exists( 'supavapes_woocommerce_product_get_rating_html_callback' ) ) {
	/**
	 * Override the product rating HTML section.
	 *
	 * @param string $html   Rating html.
	 * @param int    $rating Rating.
	 * @param int    $count  Rating count.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	function supavapes_woocommerce_product_get_rating_html_callback( $html, $rating, $count ) {
		ob_start();
		require_once get_stylesheet_directory() . '/templates/woocommerce/product-rating-html.php';

		return ob_get_clean();
	}
}

add_filter( 'woocommerce_product_get_rating_html', 'supavapes_woocommerce_product_get_rating_html_callback', 10, 3 );

/**
 * If the function, `supavapes_woocommerce_before_main_content_callback` doesn't exist.
 */
if ( ! function_exists( 'supavapes_woocommerce_before_main_content_callback' ) ) {
	/**
	 * Function to Render custom widgets which was register to filter products on shop page.
	 *
	 * @since 1.0.0
	 */
	function supavapes_woocommerce_before_main_content_callback() {
		require_once get_stylesheet_directory() . '/templates/woocommerce/render-sidebar-filters.php';
	}
}

add_action( 'woocommerce_before_main_content', 'supavapes_woocommerce_before_main_content_callback' );

/**
 * If the function, `supavapes_quick_cart_ajax_callback` doesn't exist.
 */
if ( ! function_exists( 'supavapes_quick_cart_ajax_callback' ) ) {
	/**
	 * Render the product quick view window.
	 *
	 * @since 1.0.0
	 */
	function supavapes_quick_cart_ajax_callback() {
		$nonce      = filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_STRING );
		$product_id = (int) filter_input( INPUT_POST, 'product_id', FILTER_SANITIZE_NUMBER_INT );

		// Verify nonce.
		if ( ! wp_verify_nonce( $nonce, 'quick_cart_nonce' ) ) {
			$error = new WP_Error( '001', 'Invalid Nonce', 'Nonce for the ajax call quick cart could not be verified.' );
			wp_send_json_error( $error );
		}

		// Return error if product ID is missing.
		if ( empty( $product_id ) || 0 === $product_id ) {
			$error = new WP_Error( '002', 'Product ID Missing', 'Product could not be added to the cart due to the missing product ID.' );
			wp_send_json_error( $error );
		}

		ob_start();
		require_once get_stylesheet_directory() . '/templates/callback_functions/quick-cart.php';
		$mini_cart = ob_get_clean();

		// Send the AJAX success response.
		wp_send_json_success(
			array(
				'message'       => __( 'Product is successfully added to the cart.', 'supavapes' ),
				'mini_cart'     => $mini_cart,
				'cart_quantity' => $cart_quantity, // Receiving from quick-cart.php.
			)
		);
	}
}

add_action( 'wp_ajax_quick_cart', 'supavapes_quick_cart_ajax_callback' );
add_action( 'wp_ajax_nopriv_quick_cart', 'supavapes_quick_cart_ajax_callback' );

/**
 * If the function, `supavapes_woocommerce_ajax_add_to_cart_ajax_callback` is not defined.
 */
if ( ! function_exists( 'supavapes_woocommerce_ajax_add_to_cart_ajax_callback' ) ) {
	/**
	 * Add product to cart.
	 *
	 * @since 1.00.
	 */
	function supavapes_woocommerce_ajax_add_to_cart_ajax_callback() {
		$product_id = (int) filter_input( INPUT_POST, 'product_id', FILTER_SANITIZE_NUMBER_INT );
		$quantity   = filter_input( INPUT_POST, 'quantity', FILTER_SANITIZE_NUMBER_INT );
		$quantity   = ( ! empty( $quantity ) ) ? (int) $quantity : 1;

		// Return error if product ID is missing.
		if ( empty( $product_id ) || 0 === $product_id ) {
			$error = new WP_Error( '002', 'Product ID Missing', 'Product could not be added to the cart due to the missing product ID.' );
			wp_send_json_error( $error );
		}

		// Add product to the cart.
		WC()->cart->add_to_cart( $product_id, $quantity);

		ob_start();
		woocommerce_mini_cart();
		$mini_cart = ob_get_clean();

		// Send the mini cart HTML in the response.
		wp_send_json_success(
			array(
				'message'       => __( 'Product is successfully added to the cart.', 'supavapes' ),
				'mini_cart'     => $mini_cart, // Capture the mini cart HTML.
				'cart_quantity' => WC()->cart->get_cart_contents_count(), // Get cart quantity.
			)
		);
	}
}

add_action( 'wp_ajax_woocommerce_ajax_add_to_cart', 'supavapes_woocommerce_ajax_add_to_cart_ajax_callback' );
add_action( 'wp_ajax_nopriv_woocommerce_ajax_add_to_cart', 'supavapes_woocommerce_ajax_add_to_cart_ajax_callback' );

/**
 * If the function, `supavapes_render_minicart_ajax_callback` doesn't exist.
 */
if ( ! function_exists( 'supavapes_render_minicart_ajax_callback' ) ) {
	/**
	 * Render minicart.
	 *
	 * @since 1.0.0
	 */
	function supavapes_render_minicart_ajax_callback() {
		// Send the AJAX response.

		ob_start();
		woocommerce_mini_cart();
		$mini_cart = ob_get_clean();

		wp_send_json_success(
			array(
				'message'   => 'Open Mini Cart',
				'mini_cart' => $mini_cart,
			)
		);
	}
}

add_action( 'wp_ajax_render_minicart', 'supavapes_render_minicart_ajax_callback' );
add_action( 'wp_ajax_nopriv_render_minicart', 'supavapes_render_minicart_ajax_callback' );

/**
 * If the function, `supavapes_quick_view_product_callback` doesn't exist.
 */
if ( ! function_exists( 'supavapes_quick_view_product_callback' ) ) {
	/**
	 * Render the quick view modal HTML for the products.
	 *
	 * @since 1.0.0
	 */
	function supavapes_quick_view_product_callback() {
		ob_start();
		require_once get_stylesheet_directory() . '/templates/callback_functions/quick-view.php';

		// Send the AJAX response.
		wp_send_json_success(
			array(
				'html' => ob_get_clean(),
			)
		);
		wp_die();
	}
}

add_action( 'wp_ajax_quick_view_product', 'supavapes_quick_view_product_callback' );
add_action( 'wp_ajax_nopriv_quick_view_product', 'supavapes_quick_view_product_callback' );


add_action('wp_ajax_get_order_items', 'get_order_items_callback');
add_action('wp_ajax_nopriv_get_order_items', 'get_order_items_callback');

function get_order_items_callback() {
	$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
	if ($order_id > 0) {
		$order = wc_get_order($order_id);
		if ($order) {
			$items = $order->get_items();
			$formatted_items = array();
			foreach ($items as $item_id => $item) {
				// Get item name, product ID, and variation ID if available
				$item_name = $item->get_name();
				$product_id = $item->get_product_id();
				$variation_id = $item->get_variation_id();
				$formatted_items[] = array(
					'name' => $item_name,
					'product_id' => $product_id,
					'variation_id' => $variation_id
				);
			}
			wp_send_json_success(array('items' => $formatted_items));
		} else {
			wp_send_json_error('Order not found.');
		}
	} else {
		wp_send_json_error('Invalid order ID.');
	}
	wp_die();
}


add_action('wp_ajax_add_support_request', 'sv_add_support_request');
add_action('wp_ajax_nopriv_add_support_request', 'sv_add_support_request');

function sv_add_support_request() {
	$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
	$selected_values = isset($_POST['selectedValues']) ? json_decode(stripslashes($_POST['selectedValues']), true) : array();
	$additional_info = isset($_POST['additional_info']) ? sanitize_textarea_field($_POST['additional_info']) : '';

	if ($order_id > 0 && !empty($selected_values)) {
		$selected_product_titles = array();
		$selected_product_ids = array();
		foreach ($selected_values as $item) {
			$product_id = intval($item['product_id']);
			$variation_id = !empty($item['variation_id']) ? intval($item['variation_id']) : 0;
			$product = wc_get_product($variation_id ? $variation_id : $product_id);
			if ($product) {
				$selected_product_titles[] = $product->get_title() . ($variation_id ? ' (Variation: ' . $variation_id . ')' : '');
				$selected_product_ids[] = $product_id;
			}
		}
		$selected_product_titles_text = implode(', ', $selected_product_titles);
		$selected_product_ids_text = implode(', ', $selected_product_ids);
		$post_content = $additional_info;
		$post_title = 'Support Request - Order #' . $order_id;
		$post_data = array(
			'post_title'   => $post_title,
			'post_content' => $post_content,
			'post_status'  => 'publish',
			'post_type'    => 'support_request',
			'post_parent'  => $order_id,  // Set the order ID as the parent post ID
			'post_date'    => current_time('mysql'),  // Set the post date to the current time
		);
		$post_id = wp_insert_post($post_data);
		if (!is_wp_error($post_id)) {
			// Update the post title with the support request ID
			$updated_post_title = 'Support Request #' . $post_id;
			wp_update_post(array(
				'ID'         => $post_id,
				'post_title' => $updated_post_title
			));

			update_post_meta($post_id, '_order_id', $order_id);
			$uploaded_image_ids = array();
			if (!empty($_FILES['upload_images'])) {
				$uploaded_files = $_FILES['upload_images'];
				$count_files = count($uploaded_files['name']);
				for ($i = 0; $i < $count_files; $i++) {
					if ($uploaded_files['error'][$i] == 0) {
						$file = array(
							'name'     => $uploaded_files['name'][$i],
							'type'     => $uploaded_files['type'][$i],
							'tmp_name' => $uploaded_files['tmp_name'][$i],
							'error'    => $uploaded_files['error'][$i],
							'size'     => $uploaded_files['size'][$i],
						);
						$_FILES = array("upload_image" => $file);
						foreach ($_FILES as $file => $array) {
							$newupload = sv_handle_attachment($file, $post_id);
							if ($i == 0) {
								set_post_thumbnail($post_id, $newupload);
							}
							$uploaded_image_ids[] = $newupload;
						}
					}
				}
			}
			if (!empty($uploaded_image_ids)) {
				update_field('support_request_product_images', $uploaded_image_ids, $post_id);
			}
			if (!empty($selected_product_titles_text)) {
				update_field('support_request_selected_product_items', $selected_product_titles_text, $post_id);
			}
			if (!empty($selected_product_ids_text)) {
				update_field('support_request_selected_product_ids', $selected_product_ids_text, $post_id);
			}

			$extra_data = array(
				'{support_id}'      => $post_id,
				'{product_title}'   => $selected_product_titles_text,
				'{additional_info}' => $additional_info
			);
		
			WC()->mailer()->emails['WC_Send_Support_Request']->trigger($order_id, $extra_data);
					 
			wp_send_json_success('Support request successfully created.');
		} else {
			wp_send_json_error('Failed to create support request.');
		}
	} else {
		wp_send_json_error('Invalid order ID or empty selected values.');
	}
	wp_die();
}



// Helper function to send email notification
function sv_send_support_request_notification($post_id, $order_id, $selected_values_text, $additional_info) {

	$admin_email = get_option( 'admin_email' );
	$shop_managers = sv_get_shop_managers();
	$recipients = array_merge(array($admin_email), $shop_managers);
	$subject = 'New Support Request - Order #' . $order_id;
	$message = 'A new support request has been created.' . "\n\n";
	$message .= 'Order ID: ' . $order_id . "\n";
	$message .= 'Selected Order Items: ' . $selected_values_text . "\n";
	$message .= 'Additional Information: ' . $additional_info . "\n";
	wp_mail($recipients, $subject, $message);

}

// Helper function to get all shop managers
function sv_get_shop_managers() {

	$shop_managers = array();
	$args = array(
		'role' => 'shop_manager',
		'fields' => 'user_email'
	);
	$users = get_users($args);
	if (!empty($users)) {
		$shop_managers = $users;
	}
	return $shop_managers;

}


function sv_handle_attachment($file_handler, $post_id, $set_thu = false) {
	
	if ($_FILES[$file_handler]['error'] !== UPLOAD_ERR_OK) return false;
	require_once(ABSPATH . 'wp-admin/includes/file.php');
	require_once(ABSPATH . 'wp-admin/includes/media.php');
	require_once(ABSPATH . 'wp-admin/includes/image.php');
	$attach_id = media_handle_upload($file_handler, $post_id);
	if ($set_thu) set_post_thumbnail($post_id, $attach_id);
	return $attach_id;

}


add_action('wp_ajax_approve_support_request', 'sv_approve_support_request');
function sv_approve_support_request() {

	// check_ajax_referer('support_request_nonce', 'nonce');
	$post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
	if ($post_id > 0) {
		$selected_values_text = get_field('support_request_selected_product_items', $post_id);
		$additional_info = get_post_meta($post_id, 'additional_info', true);
		$order_id = get_post_meta($post_id, '_order_id', true);
		$selected_products = get_post_meta($post_id, 'support_request_selected_product_ids', true);
		$product_ids = !empty($selected_products) ? explode(', ', $selected_products) : array();
		if (!empty($product_ids)) {
			$order = wc_create_order();
			$original_order = wc_get_order($order_id);
			if ($original_order) {
				// Fetch customer ID from the original order
				$customer_id = $original_order->get_customer_id();

				// Set the customer ID for the new order
				$order->set_customer_id($customer_id);

				$billing_details = $original_order->get_address('billing');
				$shipping_details = $original_order->get_address('shipping');
				$order->set_address($billing_details, 'billing');
				$order->set_address($shipping_details, 'shipping');
				$original_items = $original_order->get_items();
				foreach ($original_items as $item_id => $item) {
					$product_id = $item->get_product_id();
					$variation_id = $item->get_variation_id();
					if (in_array($product_id, $product_ids) || in_array($variation_id, $product_ids)) {
						$quantity = $item->get_quantity();
						$product = wc_get_product($variation_id ? $variation_id : $product_id);
						if ($product) {
							$order->add_product($product, $quantity);
						}
					}
				}
			} else {
				error_log('Original Order not found: ' . $order_id);
			}
			$order->calculate_totals();
			$order->update_status('processing', 'Order created from support request approval.');
			$new_order_id = $order->get_id();
			wp_update_post(array(
				'ID' => $post_id,
				'post_status' => 'publish'
			));
			update_post_meta($post_id, '_support_request_approved', 'yes');
			update_post_meta($post_id, '_support_request_new_order_id', $new_order_id); // Store the new order ID
			if ($original_order) {
				$customer_email = $original_order->get_billing_email();

				$extra_data = array(
					'{order_id}'      => $order_id,
					'{new_order_id}'   => $new_order_id,
					// '{customer_email}' => $customer_email
				);
			
				WC()->mailer()->emails['WC_Send_Approve_Request']->trigger( $order_id, $extra_data );


				// sv_send_approval_email($customer_email, $order_id, $new_order_id);
			}
			wp_send_json_success();
		} else {
			wp_send_json_error('No selected products found.');
		}
	} else {
		wp_send_json_error('Invalid support request ID.');
	}
	wp_die();
}




add_action('wp_ajax_decline_support_request', 'decline_support_request');
function decline_support_request() {

	check_ajax_referer('support_request_nonce', 'nonce');
	$post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
	$reason = isset($_POST['reason']) ? sanitize_text_field($_POST['reason']) : '';
	if ($post_id > 0) {
		$order_id = get_post_meta($post_id, '_order_id', true);
		$order = wc_get_order($order_id);
		if ($order) {
			$customer_email = $order->get_billing_email();
			wp_update_post(array(
				'ID' => $post_id,
				'post_status' => 'publish'
			));
			update_post_meta($post_id, '_support_request_declined', 'yes');
			update_post_meta($post_id, '_support_request_decline_reason', $reason);

			$extra_data = array(
				'{order_id}' => $order_id,
				'{reason}'   => $reason
			);
		
			WC()->mailer()->emails['WC_Send_Decline_Request']->trigger( $order_id, $extra_data );

			// sv_send_decline_email($customer_email, $order_id, $reason);
			wp_send_json_success();
		} else {
			wp_send_json_error('Order not found.');
		}
	} else {
		wp_send_json_error('Invalid support request ID.');
	}
	wp_die();

}


// Handle AJAX request for follow-up submission
add_action('wp_ajax_send_follow_up', 'sv_send_follow_up');
add_action('wp_ajax_nopriv_send_follow_up', 'sv_send_follow_up');

function sv_send_follow_up() {
	if (!isset($_POST['request_id']) || !isset($_POST['follow_up_text'])) {
		wp_send_json_error('Missing required parameters.');
		return;
	}

	$user_id = get_current_user_id();
	$user_info = get_userdata($user_id);

	$display_name = $user_info->display_name; // User's display name

	$request_id = intval($_POST['request_id']);
	$order_id = get_post_meta($request_id, '_order_id', true);
	$follow_up_text = sanitize_text_field($_POST['follow_up_text']);
	
	$admin_email = get_option('admin_email');
	$shop_managers = sv_get_shop_managers(); // Ensure this function returns an array of email addresses
	$recipients = array_merge(array($admin_email), $shop_managers);

	// $subject = 'New Follow-Up Message for Support Request #' . $request_id;
	// $message = 'A new follow-up message has been added to the support request #' . $request_id . ":\n\n" . $follow_up_text;

	// wp_mail($recipients, $subject, $message);

	$extra_data = array(
		'{request_id}'     => $request_id,
		'{followup_message}' => $follow_up_text,
		'{customer_name}' => $display_name
	);

	WC()->mailer()->emails['WC_Send_FollowUp_Request']->trigger( $order_id, $extra_data );

	wp_send_json_success('Follow-up message sent successfully.');
}


// Helper function to send decline email
function sv_send_decline_email($customer_email, $order_id, $reason) {
	
	$subject = 'Support Request Declined - Order #' . $order_id;
	$message = 'We regret to inform you that your support request for Order #' . $order_id . ' has been declined. Reason: ' . $reason;
	wp_mail($customer_email, $subject, $message);

}

// Helper function to send approval email
function sv_send_approval_email($customer_email, $order_id, $new_order_id) {
	$subject = 'Support Request Approved - Order #' . $order_id;
	$message = 'We are pleased to inform you that your support request for Order #' . $order_id . ' has been approved. A new order (Order #' . $new_order_id . ') has been created for the same.';
	wp_mail($customer_email, $subject, $message);
}


// Add custom columns to support request admin list table
add_filter('manage_support_request_posts_columns', 'add_support_request_columns');
function add_support_request_columns($columns) {
	
	$columns['parent_post'] = __('Order ID', 'supavapes');
	$columns['status'] = __('Request Status', 'supavapes');
	return $columns;

}

// Display parent post ID and status in custom columns
add_action('manage_support_request_posts_custom_column', 'render_support_request_columns', 10, 2);
function render_support_request_columns($column, $post_id) {
	if ($column == 'parent_post') {
		$parent_id = wp_get_post_parent_id($post_id);
		if ($parent_id) {
			$post_type = get_post_type($parent_id);
			if ($post_type === 'shop_order_placehold') {
				echo '<a href="'.get_site_url().'/wp-admin/admin.php?page=wc-orders&action=edit&id='.$parent_id.'" target="_blank">' . $parent_id . '</a>';
			} else {
				echo $parent_id;
			}
		} else {
			_e('No Parent', 'supavapes');
		}
	} elseif ($column == 'status') {
		$approved = get_post_meta($post_id, '_support_request_approved', true);
		$declined = get_post_meta($post_id, '_support_request_declined', true);
		if ($approved) {
			echo '<span style="color: green;">' . __('Approved', 'supavapes') . '</span>';
		} elseif ($declined) {
			echo '<span style="color: red;">' . __('Declined', 'supavapes') . '</span>';
		} else {
			echo __('Pending', 'supavapes');
		}
	}
}


/**
* Ajax callback funtion to add product into cart with quick cart action icon.
*
* @since 1.0.0 
*/
function sv_quick_view_add_to_cart_action(){

	check_ajax_referer('quick_view_add_to_cart_nonce', 'security');
	if ( isset($_POST['product_id'] ) || isset($_POST['quantity']) || isset($_POST['variation_id'])) {
		$product_id = intval( $_POST['product_id'] ); 
		$quantity = intval($_POST['quantity']);
		$variation_id = intval($_POST['variation_id']);
		WC()->cart->add_to_cart( $product_id, $quantity, $variation_id );
		wp_send_json_success( 'Product added to cart successfully' );
	} else {
		wp_send_json_error( 'Product ID is missing' );
	}

}
add_action('wp_ajax_quick_view_add_to_cart_action', 'sv_quick_view_add_to_cart_action');
add_action('wp_ajax_nopriv_quick_view_add_to_cart_action', 'sv_quick_view_add_to_cart_action');


/**
* Function to display Fun Questionaries.
*
* @since 1.0.0 
*
* Use of Shortcode: [fun_questionaries]
* 
*/
function sv_fun_questionaries() {

	$questionaries_html = '';
	ob_start();
	require_once get_stylesheet_directory() . '/templates/shortcodes/fun-questionnaire.php';
	$questionaries_html = ob_get_clean();
	return $questionaries_html;

}
add_shortcode('fun_questionaries','sv_fun_questionaries');

function sv_add_custom_query_vars( $vars ) {
	$vars[] = 'notification-preference';
	$vars[] = 'wishlist';
	$vars[] = 'support-request';
	// $vars[] = 'view-request';
	return $vars;
}
add_filter( 'query_vars', 'sv_add_custom_query_vars', 0 );

function sv_add_custom_menu_item_my_account( $items ) {
	
	$items = array(
		'dashboard'       => __( 'Dashboard', 'supavapes' ),
		'orders'          => __( 'Orders', 'supavapes' ),
		'notification-preference' => __('Notification','supavapes'),
		'support-request' => __('Support Request','supavapes'),
		'wishlist' => __('Wishlist','supavapes'),
		'downloads'       => __( 'Downloads', 'supavapes' ),
		'edit-address'    => _n( 'Addresses', 'Address', (int) wc_shipping_enabled(), 'supavapes' ),
		'payment-methods' => __( 'Payment methods', 'supavapes' ),
		'edit-account'    => __( 'Account details', 'supavapes' ),
		'customer-logout' => __( 'Logout', 'supavapes' ),
		);
	if ( isset( $items['downloads'] ) ) {
		unset( $items['downloads'] );
	}
	return $items;
}
add_filter( 'woocommerce_account_menu_items', 'sv_add_custom_menu_item_my_account' );


/**
* Function to render notification tab content on my account page
*
* @since 1.0.0 
*/
function sv_notification_preference_content_my_account() {
	require_once 'templates/dashboard/notifications-tab.php';
}
add_action( 'woocommerce_account_notification-preference_endpoint', 'sv_notification_preference_content_my_account' );


/**
* Function to render Support Request tab content on my account page
*
* @since 1.0.0 
*/
function sv_support_request_content_my_account() {
	require_once 'templates/dashboard/support-request-tab.php';
}
add_action( 'woocommerce_account_support-request_endpoint', 'sv_support_request_content_my_account' );

// Display content for Support Request detail page
add_action( 'woocommerce_account_view-request_endpoint', 'support_request_detail_content' );
function support_request_detail_content() {
	require_once 'templates/dashboard/view-support-request.php';
}

/**
* Function to render dashboard tab content on my account page
*
* @since 1.0.0 
*/
function sv_custom_dashboard_content() {
	require_once 'templates/dashboard/dashboard-tab.php';
}
add_action('woocommerce_account_dashboard', 'sv_custom_dashboard_content', 5);


/**
* Function to render wishlist tab content on my account page
*
* @since 1.0.0 
*/
function sv_wishlist_my_account() {
	include_once ABSPATH . 'wp-admin/includes/plugin.php';
		// check for plugin using plugin name
		if ( is_plugin_active( 'yith-woocommerce-wishlist/init.php' ) ) {
		echo do_shortcode('[yith_wcwl_wishlist]');
	}
}
add_action( 'woocommerce_account_wishlist_endpoint', 'sv_wishlist_my_account' );


function sv_remove_duplicate_notices($message, $message_code = '') {
	static $notices = [];

	// Create a unique hash for the message
	$hash = md5($message);

	// Check if the notice is already stored
	if (isset($notices[$hash])) {
		return '';
	}

	// Store the notice
	$notices[$hash] = true;

	return $message;
}

// Hook into the WooCommerce error handling to remove duplicate error messages
add_filter('woocommerce_add_error', 'sv_remove_duplicate_notices', 10, 2);
add_filter('woocommerce_add_notice', 'sv_remove_duplicate_notices', 10, 2);
add_filter('woocommerce_add_success', 'sv_remove_duplicate_notices', 10, 2);


/**
* Debug Function
*
* @since 1.0.0 
*/
if ( ! function_exists( 'debug' ) ) {
	function debug( $params ) {
		echo '<pre>';
		print_r( $params );
		echo '</pre>';
	}
}


/**
* Ajax callback Function to send selected values of fun questionnaire.
*
* @since 1.0.0 
*/
function sv_send_selected_value() {
	// check_ajax_referer('fun_questionnaire_nonce', 'security');
	if (isset($_POST['selectedValues']) && isset($_POST['quizemail'])) {
		$selectedValues = json_decode(stripslashes($_POST['selectedValues']), true);
		$quizemail = sanitize_email($_POST['quizemail']);
		$emails_to_add = array($quizemail);
		$totalQuestions = count($selectedValues);
		$correctCount = 0;
		foreach ($selectedValues as $questionNumber => $selectedValue) {
			if ($selectedValue === 'correct') {
				$correctCount++;
			}
		}
		$percentage = ($correctCount / $totalQuestions) * 100;
		$result = ($percentage >= 75) ? 'pass' : 'fail';
		$coupon_code = '';
		if ($result === 'pass') {
			$coupon_code = sv_generate_unique_coupon_code();
			sv_create_coupon($coupon_code, $emails_to_add);
			// Send email
			// sv_send_quiz_email($quizemail, $coupon_code);

				$extra_data = array(
					'{coupon_code}'   => $coupon_code
				);
			
				WC()->mailer()->emails['WC_Email_Fun_Questionnaire_Order']->trigger( $quizemail, $extra_data );
		}
		wp_send_json_success(array('result' => $result, 'coupon_code' => $coupon_code));
	} else {
		wp_send_json_error('selectedValues or quizemail is missing');
	}
}
add_action('wp_ajax_send_selected_value', 'sv_send_selected_value');
add_action('wp_ajax_nopriv_send_selected_value', 'sv_send_selected_value');

/**
 * Function to send the quiz result email.
 *
 * @param string $email Recipient email address.
 * @param string $coupon_code Generated coupon code.
 */
function sv_send_quiz_email($email, $coupon_code) {
	$subject = 'Quiz Result: You Passed!';
	$message = 'Congratulations! You have passed the quiz. Your coupon code is: ' . $coupon_code;
	$headers = array('Content-Type: text/html; charset=UTF-8');

	wp_mail($email, $subject, $message, $headers);
}



function sv_modify_custom_post_type_query($query) {
	// Check if this is the admin area and the main query
	if (is_admin() && $query->is_main_query()) {
		// Specify your custom post type
		$post_type = 'support_request';

		// Check if the query is for the specified custom post type
		if ($query->get('post_type') === $post_type) {
			// Set the order and orderby parameters
			$query->set('orderby', 'date');
			$query->set('order', 'DESC');
		}
	}
}
add_action('pre_get_posts', 'sv_modify_custom_post_type_query');


/**
* Function to generate a unique coupon code
*
* @since 1.0.0 
*/
function sv_generate_unique_coupon_code() {
	return 'fnq_' . strtoupper(wp_generate_password(8, false));
}


/**
* Function to create the coupon and add allowed emails
*
* @param Integer $coupon_code This variable holds the coupon_code value integer.
* @param Array $emails_to_add This variable holds the emails_to_add value array.
* @since 1.0.0 
*/ 
function sv_create_coupon($coupon_code, $emails_to_add) {

	$coupon = array(
		'post_title'   => $coupon_code,
		'post_content' => '',
		'post_status'  => 'publish',
		'post_author'  => get_current_user_id(),
		'post_type'    => 'shop_coupon'
	);
	$new_coupon_id = wp_insert_post($coupon);
	update_post_meta($new_coupon_id, 'discount_type', 'percent');
	update_post_meta($new_coupon_id, 'coupon_amount', '20');
	update_post_meta($new_coupon_id, 'individual_use', 'yes');
	update_post_meta($new_coupon_id, 'product_ids', '');
	update_post_meta($new_coupon_id, 'exclude_product_ids', '');
	update_post_meta($new_coupon_id, 'usage_limit', '1');
	update_post_meta($new_coupon_id, 'expiry_date', gmdate('Y-m-d', strtotime('+1 day')));
	update_post_meta($new_coupon_id, 'apply_before_tax', 'yes');
	update_post_meta($new_coupon_id, 'free_shipping', 'no');
	update_post_meta($new_coupon_id, 'customer_email', $emails_to_add);

}


/**
* Function to change the order of orderby dropdown.
* 
* @param Array $orderby This variable holds the orderby value array.
* @since 1.0.0 
*/ 
function custom_woocommerce_catalog_orderby( $orderby ) {

	$orderby['menu_order'] = __( 'Sort By', 'supavapes' );
	$orderby['popularity'] = __( 'Popularity', 'supavapes' );
	$orderby['rating'] = __( 'Average Rating', 'supavapes' );
	$orderby['date'] = __( 'Latest', 'supavapes' );
	$orderby['price'] = __( 'Price: Low to High', 'supavapes' );
	$orderby['price-desc'] = __( 'Price: High to Low', 'supavapes' );
	return $orderby;

}
add_filter( 'woocommerce_catalog_orderby', 'custom_woocommerce_catalog_orderby', 20 );


/**
* Shortcode function to display Topbar free delivery timer.
*
* @since 1.0.0
* Use of Shortcode: [announcement_top_bar]
* 
*/
function sv_announcement_top_bar(){

	$output = '';
	ob_start();
	require_once 'templates/shortcodes/announcement-top-bar.php';
	$output = ob_get_clean();
	return $output;

}
add_shortcode('announcement_top_bar','sv_announcement_top_bar');


/**
* Shortcode function to display Checkout banner.
*
* @since 1.0.0
* Use of Shortcode: [checkout_banner]
* 
*/
function sv_checkout_banner(){

	$output = "";
	ob_start();
	require_once 'templates/shortcodes/checkout-banner.php';
	$output = ob_get_clean();
	return $output;

}
add_shortcode('checkout_banner','sv_checkout_banner');


/**
* Shortcode function to display Deals banner.
*
* @since 1.0.0
* Use of Shortcode: [deals_banner]
* 
*/
function sv_deals_banner(){

	$output = "";
	ob_start();
	require_once 'templates/shortcodes/deals-banner.php';
	$output = ob_get_clean();
	return $output;

}
add_shortcode('deals_banner','sv_deals_banner');


/**
* Shortcode function to display Checkout banner.
*
* @since 1.0.0
* Use of Shortcode: [my_account_banner]
* 
*/
function sv_my_account_banner(){

	$output = "";
	ob_start();
	require_once 'templates/shortcodes/my-account-banner.php';
	$output = ob_get_clean();
	return $output;

}
add_shortcode('my_account_banner','sv_my_account_banner');


/**
* Shortcode function to display Cart banner.
*
* @since 1.0.0
* Use of Shortcode: [cart_banner]
* 
*/
function sv_cart_banner(){

	$output = "";
	ob_start();
	require_once 'templates/shortcodes/cart-banner.php';
	$output = ob_get_clean();
	return $output;

}
add_shortcode('cart_banner','sv_cart_banner');


/**
* Function to change the minimum amount of price filter on shop page.
*
* @since 1.0.0
* 
*/
add_filter('woocommerce_price_filter_widget_min_amount', function($min_amount) {
	
	if($min_amount === 0){
		$min_amount = 1;
	}
	return $min_amount;

});


/**
* Function to change the maximum amount of price filter on shop page.
*
* @since 1.0.0
* 
*/
add_filter('woocommerce_price_filter_widget_max_amount', function($max_amount) {

	$max_amount = 500;
	return $max_amount;

});


/**
* Function to display flash notifications.
*
* @since 1.0.0
* 
*/
function sv_notifications(){
	require_once 'templates/notifications/notifications.php';
}
add_action('wp_footer','sv_notifications');


remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20 );
add_action( 'woocommerce_proceed_to_checkout', 'sv_custom_button_proceed_to_checkout', 20 );
function sv_custom_button_proceed_to_checkout() {
	echo '<a href="'.esc_url(wc_get_checkout_url()).'" class="checkout-button button alt wc-forward">' .
	esc_html__("Proceed to Checkout ", "woocommerce") . '<svg width="21" height="18" viewBox="0 0 21 18" fill="none" xmlns="http://www.w3.org/2000/svg">
	<path d="M20.0183 7.7734L12.5955 0.350527C12.3955 0.157364 12.1276 0.0504805 11.8496 0.0528965C11.5715 0.0553126 11.3056 0.166835 11.1089 0.363444C10.9123 0.560052 10.8008 0.826016 10.7984 1.10405C10.796 1.38209 10.9029 1.64995 11.096 1.84995L16.7088 7.4627H1.06041C0.779172 7.4627 0.509452 7.57442 0.310587 7.77328C0.111721 7.97215 0 8.24187 0 8.52311C0 8.80435 0.111721 9.07406 0.310587 9.27293C0.509452 9.4718 0.779172 9.58352 1.06041 9.58352H16.7088L11.096 15.1963C10.9948 15.2941 10.914 15.4111 10.8584 15.5405C10.8028 15.6698 10.7736 15.809 10.7723 15.9498C10.7711 16.0906 10.7979 16.2302 10.8513 16.3605C10.9046 16.4909 10.9833 16.6093 11.0829 16.7088C11.1825 16.8084 11.3009 16.8871 11.4312 16.9405C11.5615 16.9938 11.7011 17.0206 11.8419 17.0194C11.9827 17.0182 12.1219 16.9889 12.2512 16.9333C12.3806 16.8778 12.4976 16.797 12.5955 16.6957L20.0183 9.27282C20.2171 9.07396 20.3288 8.80429 20.3288 8.52311C20.3288 8.24192 20.2171 7.97225 20.0183 7.7734Z" fill="white"></path>
	</svg>
</a>';
}


/**
* Ajax callback function to remove item from minicart.
*
* @since 1.0.0
* 
*/
function sv_remove_minicart_action() {

	$cart_quantity = WC()->cart->get_cart_contents_count();
	wp_send_json_success(array(
		'cart_quantity' => $cart_quantity
	));

}
add_action('wp_ajax_remove_minicart_action', 'sv_remove_minicart_action');
add_action('wp_ajax_nopriv_remove_minicart_action', 'sv_remove_minicart_action');


/**
* Function to get total order amount for current logged-in user including completed and processing orders
*
* @since 1.0.0
* 
*/
function sv_get_total_order_amount_for_current_user() {
	if (is_user_logged_in()) {
		$user_id = get_current_user_id();
		$args = array(
			'customer_id' => $user_id,
			'status' => array('wc-completed', 'wc-processing'),
			'limit' => -1
		);
		$orders = wc_get_orders($args);
		$total_amount = 0;
		foreach ($orders as $order) {
			$total_amount += $order->get_total();
		}
		return $total_amount;
	} else {
		return 0;
	}
}


/**
* Function to render mailchimp form.
*
* @since 1.0.0
* 
*/
function sv_mailchimp_form($atts) {

	$atts = shortcode_atts(
		array(
			'button_text' => 'Send',
		),
		$atts,
		'mailchimp_form'
	);
	set_query_var('shortcode_atts', $atts);
	ob_start();
	require locate_template('templates/shortcodes/mailchimp-form.php');
	$output = ob_get_clean();
	return $output;

}
add_shortcode('mailchimp_form', 'sv_mailchimp_form');


/**
* Function to render mailchimp form.
*
* @since 1.0.0
* 
*/
function sv_footer_mailchimp_form($atts) {

	$atts = shortcode_atts(
		array(
			'button_text' => 'Send',
		),
		$atts,
		'mailchimp_form'
	);
	set_query_var('shortcode_atts', $atts);
	ob_start();
	require locate_template('templates/shortcodes/footer-mailchimp-form.php');
	$output = ob_get_clean();
	return $output;

}
add_shortcode('footer_mailchimp_form', 'sv_footer_mailchimp_form');


function sv_fetch_mailchimp_subscribers() {

	$mailchimp_api_key = get_field('mailchimp_api_key','option');
	$mailchimp_list_id = get_field('mailchimp_list_id','option');
	$api_key = $mailchimp_api_key;
	$list_id = $mailchimp_list_id;
	$url = 'https://<dc>.api.mailchimp.com/3.0/lists/' . $list_id . '/members';
	$url = str_replace('<dc>', substr($api_key, strpos($api_key, '-') + 1), $url);
	$count = 100;
	$offset = 0;
	$all_members = array();
		do {
			$response = wp_remote_get($url, array(
				'headers' => array(
					'Authorization' => 'apikey ' . $api_key
				),
				'body' => array(
					'count' => $count,
					'offset' => $offset
				)
			));
			if (is_wp_error($response)) {
				error_log('Mailchimp API request failed: ' . $response->get_error_message());
				return array();
			}
			$body = wp_remote_retrieve_body($response);
			$data = json_decode($body, true);

			if (isset($data['members'])) {
				$all_members = array_merge($all_members, $data['members']);
				$offset += $count;
			} else {
				break;
			}
		} 
		while (isset($data['total_items']) && count($all_members) < $data['total_items']);
		error_log('Mailchimp subscribers fetched: ' . count($all_members));
		return $all_members;

}

function sv_mailchimp_subscribers_page() {
	$subscribers = sv_fetch_mailchimp_subscribers();
	if (isset($_REQUEST['s'])) {
		$search = sanitize_text_field($_REQUEST['s']);
		$subscribers = array_filter($subscribers, function ($subscriber) use ($search) {
			return stripos($subscriber['email_address'], $search) !== false;
		});
	}
	$table = new Mailchimp_Subscribers_Table($subscribers);
	$table->prepare_items();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Mailchimp Subscribers', 'supavapes' ); ?></h1>
		<form method="get">
			<input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
			<?php
			$table->search_box(__( 'Search Subscribers', 'supavapes'), 'search_id' );
			?>
		</form>
		<?php $table->display(); ?>
		<table class="wp-list-table widefat striped">
			<thead>
				<tr>
					<?php
					foreach ($table->get_columns() as $column_name => $column_label) {
						echo '<th scope="col">' . esc_html($column_label) . '</th>';
					}
					?>
				</tr>
			</thead>
			<tbody id="the-list">
				<?php
				foreach ($table->items as $item) {
					echo '<tr>';
					foreach ($table->get_columns() as $column_name => $column_label) {
						echo '<td>' . $table->column_default($item, $column_name) . '</td>';
					}
					echo '</tr>';
				}
				?>
			</tbody>
		</table>
	</div>
	<?php

}


function sv_handle_mailchimp_subscription_action() {

	if (isset($_GET['email']) && isset($_GET['action_type'])) {
		$email = sanitize_email($_GET['email']);
		$action_type = sanitize_text_field($_GET['action_type']);
		$mailchimp_api_key = get_field('mailchimp_api_key','option');
		$mailchimp_list_id = get_field('mailchimp_list_id','option');
		$api_key = $mailchimp_api_key;
		$list_id = $mailchimp_list_id;
		$member_id = md5(strtolower($email));
		$new_status = ($action_type === 'subscribe') ? 'subscribed' : 'unsubscribed';
		if ($new_status === 'subscribe') {
			wc_add_notice(__('Status changed successfully!', 'supavapes'), 'success');
		}
		$url = 'https://' . substr($api_key, strpos($api_key, '-') + 1) . '.api.mailchimp.com/3.0/lists/' . $list_id . '/members/' . $member_id;
		$response = wp_remote_request($url, [
			'method' => 'PATCH',
			'body' => json_encode([
				'status' => $new_status
			]),
			'headers' => [
				'Authorization' => 'Basic ' . base64_encode('user:' . $api_key),
				'Content-Type' => 'application/json'
			]
		]);
		if (is_wp_error($response)) {
			set_transient('mailchimp_subscription_notice', 'Failed to update subscription status.', 30);
		} else {
			set_transient('mailchimp_subscription_notice', 'Status changed successfully!', 30);
		}
		wp_redirect(admin_url('admin.php?page=mailchimp-subscribers'));
		exit;
	}

}
add_action('admin_post_mailchimp_subscription_action', 'sv_handle_mailchimp_subscription_action');


function sv_display_mailchimp_admin_notice() {
	if ($notice = get_transient('mailchimp_subscription_notice')) {
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php echo esc_html($notice); ?></p>
		</div>
		<?php
		delete_transient('mailchimp_subscription_notice');
	}
}
add_action('admin_notices', 'sv_display_mailchimp_admin_notice');


function sv_register_mailchimp_subscribers_page() {
	add_submenu_page(
		'mailchimp-for-wp',
		'Subscribers',
		'Subscribers',
		'manage_options',
		'mailchimp-subscribers',
		'sv_mailchimp_subscribers_page'
	);
}
add_action('admin_menu', 'sv_register_mailchimp_subscribers_page');


add_action('woocommerce_before_account_navigation', 'sv_add_user_info_above_account_navigation');
function sv_add_user_info_above_account_navigation() {
	require_once 'templates/woocommerce/my-account-navigation-render-user-info.php';
}


function sv_user_meta_with_customer_dashboard_dropdown(){

	$output_html = "";
	ob_start();
	require_once 'templates/shortcodes/user-meta-dropdown.php';
	$output_html = ob_get_clean();
	return $output_html;

}
add_shortcode('user_meta_with_customer_dashboard_dropdown','sv_user_meta_with_customer_dashboard_dropdown');


function sv_header_gravatar(){

	$output_html = "";
	ob_start();
	require_once 'templates/shortcodes/user-gravatar.php';
	$output_html = ob_get_clean();
	return $output_html;

}
add_shortcode('user_gravatar','sv_header_gravatar');


function sv_subscriber_popup_form(){

	require_once 'templates/modals/subscription-modal.php';

}
add_action('wp_footer','sv_subscriber_popup_form');


function sv_support_request_successfully_added_modal(){

	require_once 'templates/modals/support-request-successfully-added-modal.php';

}
add_action('wp_footer','sv_support_request_successfully_added_modal');


function sv_prevent_place_order_success_message_modal(){

	require_once 'templates/modals/prevent-place-order-success-modal.php';

}
add_action('wp_footer','sv_prevent_place_order_success_message_modal');


add_filter('get_the_categories', 'remove_uncategorized_links', 1);
function remove_uncategorized_links( $categories ){

  foreach ( $categories as $cat_key => $category ){
	if( 1 == $category->term_id ){
	  unset( $categories[ $cat_key ] );
	}
  }
  return $categories;

}


function add_mailchimp_script_to_head() {
	?>
	<script id="mcjs">
		!function(c,h,i,m,p){
			m=c.createElement(h),
			p=c.getElementsByTagName(h)[0],
			m.async=1,
			m.src=i,
			p.parentNode.insertBefore(m,p)
		}(document,"script","https://chimpstatic.com/mcjs-connected/js/users/b5352c22d6f33a44b90a55035/7dedf0acb6821bee93d211c2d.js");
	</script>
	<?php
}
add_action('wp_head', 'add_mailchimp_script_to_head');


add_action('wp_ajax_nopriv_mailchimp_subscribe', 'sv_mailchimp_subscribe');
add_action('wp_ajax_mailchimp_subscribe', 'sv_mailchimp_subscribe');
function sv_mailchimp_subscribe() {

	if (!isset($_POST['email']) || !is_email($_POST['email'])) {
		wp_send_json_error('Invalid email address.');
	}
	$mailchimp_api_key = get_field('mailchimp_api_key','option');
	$mailchimp_list_id = get_field('mailchimp_list_id','option');
	$api_key = $mailchimp_api_key;
	$list_id = $mailchimp_list_id;
	$email = $_POST['email'];
	$phone = $_POST['phone'];
	$member_id = md5(strtolower($email));
	$data_center = substr($api_key, strpos($api_key, '-') + 1);
	$url = 'https://' . $data_center . '.api.mailchimp.com/3.0/lists/' . $list_id . '/members/' . $member_id;
	$response = wp_remote_get($url, array(
		'headers' => array(
			'Authorization' => 'apikey ' . $api_key
		)
	));
	if (is_wp_error($response)) {
		wp_send_json_error('Failed to connect to Mailchimp.');
	}
	$response_body = wp_remote_retrieve_body($response);
	$result = json_decode($response_body);
	if ($result->status == 'subscribed') {
		wp_send_json_error('This user is already subscribed.');
	}
	$data = array(
		'email_address' => $email,
		'status' => 'subscribed',
		'merge_fields' => array(
			'PHONE' => $phone,
			'SOURCE' => 'Popup'
		)
	);
	$response = wp_remote_post($url, array(
		'method' => 'PUT',
		'headers' => array(
			'Authorization' => 'apikey ' . $api_key,
			'Content-Type' => 'application/json'
		),
		'body' => json_encode($data)
	));
	if (is_wp_error($response)) {
		wp_send_json_error('Failed to connect to Mailchimp.');
	}
	$response_body = wp_remote_retrieve_body($response);
	$result = json_decode($response_body);
	if ($result->status === 'subscribed') {
		$extra_data = array(
			'{subscriber_email}'   => $email,
			'{subscriber_phone}'   => $phone,
			);
		WC()->mailer()->emails['WC_Email_Subscribe_User']->trigger( $email, $extra_data );
		WC()->mailer()->emails['WC_Email_Subscribe_Admin']->trigger( $extra_data );
		wp_send_json_success('Subscribed successfully!');
	} else {
		wp_send_json_error($result->detail);
	}

}


/**
* Ajax callback Function to filter variations.
*
* @since 1.0.0 
*/
function sv_checkout_services(){

	$output_html = '';
	ob_start();
	require_once 'templates/shortcodes/checkout-services.php';
	$output_html = ob_get_clean();
	return $output_html;

}
add_shortcode('checkout_services','sv_checkout_services');


/**
* Ajax callback Function to filter variations.
*
* @since 1.0.0 
*/
add_action('wp_ajax_search_product_variations', 'sv_search_product_variations');
add_action('wp_ajax_nopriv_search_product_variations', 'sv_search_product_variations');
function sv_search_product_variations() {

	$html = '';
	require_once 'templates/callback_functions/filter-variations.php';
	wp_send_json_success($html);

}


/**
* Function to add sku for each product on product detail page.
*
* @since 1.0.0 
*/
add_action('woocommerce_single_product_summary', 'sv_show_sku', 5);
function sv_show_sku() {

	global $product;
	if ($product->get_sku()) {
		echo '<div class="simple-sku"><p>SKU: ' . $product->get_sku() . '</p></div>';
	}

}


/**
* Function to add sku for each product on product detail page.
*
* @since 1.0.0
*/
add_action('woocommerce_after_add_to_cart_button', 'sv_add_content_after_addtocart_button_func');
function sv_add_content_after_addtocart_button_func() {
	require_once 'templates/woocommerce/multi-store-setup-details-single.php';
}

function sv_upload_profile_picture() {

	if (!isset($_POST['user_id']) || !is_user_logged_in()) {
		wp_send_json_error(['message' => 'Unauthorized request.']);
		return;
	}
	$user_id = intval($_POST['user_id']);
	if ($user_id !== get_current_user_id()) {
		wp_send_json_error(['message' => 'Unauthorized request.']);
		return;
	}
	if (!function_exists('wp_handle_upload')) {
		require_once(ABSPATH . 'wp-admin/includes/file.php');
	}
	$uploadedfile = $_FILES['file'];
	$upload_overrides = ['test_form' => false];
	$movefile = wp_handle_upload($uploadedfile, $upload_overrides);
	if ($movefile && !isset($movefile['error'])) {
		$url = $movefile['url'];
		update_user_meta($user_id, 'profile_picture', $url);
		wp_send_json_success(['url' => $url]);
	} else {
		wp_send_json_error(['message' => $movefile['error']]);
	}

}
add_action('wp_ajax_upload_profile_picture', 'sv_upload_profile_picture');


function sv_get_custom_avatar($avatar, $id_or_email, $size, $default, $alt) {

	$user = false;
	if (is_numeric($id_or_email)) {
		$user_id = (int) $id_or_email;
		$user = get_user_by('id', $user_id);
	} elseif (is_object($id_or_email)) {
		if (!empty($id_or_email->user_id)) {
			$user_id = (int) $id_or_email->user_id;
			$user = get_user_by('id', $user_id);
		}
	} else {
		$user = get_user_by('email', $id_or_email);
	}
	if ($user && is_object($user)) {
		$custom_avatar = get_user_meta($user->ID, 'profile_picture', true);
		if ($custom_avatar) {
			$avatar = "<img alt='{$alt}' src='{$custom_avatar}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
		}
	}
	return $avatar;

}
add_filter('get_avatar', 'sv_get_custom_avatar', 10, 5);


add_action('wp_ajax_remove_profile_picture', 'sv_remove_profile_picture');
function sv_remove_profile_picture() {

	if (!isset($_POST['user_id']) || !is_user_logged_in()) {
		wp_send_json_error(['message' => 'Unauthorized request.']);
		return;
	}
	$user_id = intval($_POST['user_id']);
	if ($user_id !== get_current_user_id()) {
		wp_send_json_error(['message' => 'Unauthorized request.']);
		return;
	}
	delete_user_meta($user_id, 'profile_picture');
	$default_avatar = get_avatar_url($user_id);
	wp_send_json_success(['url' => $default_avatar]);

}


/**
* Ajax callback function to fetch multiple payment attempt failur data.
*
* @since 1.0.0
* 
*/
add_action('wp_ajax_nopriv_multiple_payment_attempt', 'sv_multiple_payment_attempt_failur');
add_action('wp_ajax_multiple_payment_attempt', 'sv_multiple_payment_attempt_failur');
function sv_multiple_payment_attempt_failur(){
	require_once 'templates/callback_functions/multiple-payment-attempt-failur.php';
}


add_action('wp_login', 'sv_update_last_login_details', 10, 2);
function sv_update_last_login_details($user_login, $user) {

	$user_ip = $_SERVER['REMOTE_ADDR'];
	update_user_meta($user->ID, 'last_login', current_time('mysql'));
	update_user_meta($user->ID, 'last_login_ip', $user_ip);

}


function get_location_from_ip($ip) {

	$response = wp_remote_get("http://ip-api.com/json/{$ip}");
	if (is_wp_error($response)) {
		return false;
	}
	$body = wp_remote_retrieve_body($response);
	$data = json_decode($body);
	if ($data->status === 'success') {
		return $data->city . ', ' . $data->regionName . ', ' . $data->country;
	}
	return false;

}


add_action('show_user_profile', 'sv_show_last_login_details_in_user_profile');
add_action('edit_user_profile', 'sv_show_last_login_details_in_user_profile');
function sv_show_last_login_details_in_user_profile($user) {

	$last_login = get_user_meta($user->ID, 'last_login', true);
	$last_login_ip = get_user_meta($user->ID, 'last_login_ip', true);
	$location = get_location_from_ip($last_login_ip);
	?>
	<h3><?php echo esc_html__('Last Login Details','supavapes'); ?></h3>
	<table class="form-table">
		<tr>
			<th><label for="last_login"><?php echo esc_html__('Last Login','supavapes'); ?></label></th>
			<td>
				<input type="text" name="last_login" id="last_login" value="<?php echo esc_attr($last_login); ?>" class="regular-text" disabled />
			</td>
		</tr>
		<tr>
			<th><label for="last_login_ip"><?php echo esc_html__('Last Login IP','supavapes'); ?></label></th>
			<td>
				<input type="text" name="last_login_ip" id="last_login_ip" value="<?php echo esc_attr($last_login_ip); ?>" class="regular-text" disabled />
			</td>
		</tr>
		<tr>
			<th><label for="last_login_location"><?php echo esc_html__('Last Login Location','supavapes'); ?></label></th>
			<td>
				<input type="text" name="last_login_location" id="last_login_location" value="<?php echo esc_attr($location); ?>" class="regular-text" disabled />
			</td>
		</tr>
	</table>
	<?php
	
}

function format_date($date_string) {

	$date = new DateTime($date_string);
	return $date->format('F j, Y');

}


// Function to calculate and display savings
function sv_calculate_and_display_savings() {

	$dynamic_percentage = get_field('my_account_money_savior_percentage', 'option'); // Default to 78 if not set
	if($dynamic_percentage == ''){
		$dynamic_percentage = 78;
	}
	$total_amount = sv_get_total_order_amount_for_current_user();
	$percentage_multiplier = 1 + ($dynamic_percentage / 100); // Convert percentage to multiplier
	$increased_amount = $total_amount * $percentage_multiplier; // Apply dynamic percentage
	$savings = $increased_amount - $total_amount;
	return sprintf(__('%s', 'supavapes'), wc_price($savings));
	
}

// Shortcode to display savings
function sv_display_savings() {
	return sv_calculate_and_display_savings();
}
add_shortcode('total_savings', 'sv_display_savings');


function sv_login_logo() {
?> 
<style type="text/css"> 
body.login div#login h1 a {
background-image: url('/wp-content/uploads/2024/04/supavapes_footer.png');
width: 200px;
background-size: contain;
background-position: center;
} 
</style>
<script>
document.addEventListener("DOMContentLoaded", function() {
	var loginLink = document.querySelector("#login h1 a");
	if (loginLink) {
		loginLink.href = "https://dev.supavapes.com/";
	}
});
</script>
<?php } 
add_action( 'login_enqueue_scripts', 'sv_login_logo' );


add_action('woocommerce_order_status_failed', 'sv_handle_payment_failed', 10, 1);
function sv_handle_payment_failed($order_id) {

	$order = wc_get_order($order_id);
	error_log('Payment failed for Order ID: ' . $order_id);
	if (isset($_COOKIE['payment_fail_counter'])) {
		$counter = intval($_COOKIE['payment_fail_counter']);
		$counter++;
	} else {
		$counter = 1;
	}
	setcookie('payment_fail_counter', $counter, time() + (86400 * 30), COOKIEPATH, COOKIE_DOMAIN);
	if (!headers_sent()) {
		header('Set-Cookie: payment_fail_counter=' . $counter . '; Path=' . COOKIEPATH . '; Domain=' . COOKIE_DOMAIN . '; Max-Age=' . (86400 * 30) . '; SameSite=Lax');
	}

}


add_action('wp_ajax_available_store_details', 'sv_available_store_details');
add_action('wp_ajax_nopriv_available_store_details', 'sv_available_store_details');
function sv_available_store_details() {

	$html_content = "";
	ob_start();
	if (isset($_POST['productId']) && isset($_POST['lat']) && isset($_POST['lng'])) {
		$product_id = intval($_POST['productId']);
		$terms = get_the_terms($product_id, 'store_locator');
		$origin_lat = sanitize_text_field($_POST['lat']);
		$origin_lng = sanitize_text_field($_POST['lng']);
		if ($terms && !is_wp_error($terms)) {
			$term_data = array();
			foreach ($terms as $term) {
				$term_data[] = array(
					'name' => $term->name,
					'address_line_1' => get_term_meta($term->term_id, 'address_line_1', true),
					'address_line_2' => get_term_meta($term->term_id, 'address_line_2', true),
					'contact_number' => get_term_meta($term->term_id, 'contact_number', true),
					'pickup_avialablility' => get_term_meta($term->term_id, 'pickup_avialablility', true)
				);
			}
			?>
			<div class="surface-pick-up-modal__header">
				<h2 class="surface-pick-up-modal__title"><?php echo esc_html(get_the_title($product_id)); ?></h2>
			</div>
			<ul class="surface-pick-up-items" role="list">
				<?php foreach ($term_data as $store): 
					$destination_address = $store['address_line_1'] . ' ' . $store['address_line_2'];
					$destination_coords = get_geocode($destination_address);
					if (!$destination_coords) {
						wp_send_json_error('Failed to get destination coordinates.');
						return;
					}
					$destination_lat = $destination_coords['lat'];
					$destination_lng = $destination_coords['lng'];
					$distance_km = haversine_distance($origin_lat, $origin_lng, $destination_lat, $destination_lng);
					$google_maps_link = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($destination_address);
					?>
					<li class="surface-pick-up-item surface-pick-up-item--available" data-surface-pick-up-item="">
						<div class="surface-pick-up-item__header">
							<h3 class="surface-pick-up-item__pick-up-location"><?php echo esc_html($store['name'],'supavapes'); ?></h3>
							<p class="surface-pick-up-item__pick-up-distance">
								<span data-distance="" data-latitude="45.607124" data-longitude="-74.584797"><?php echo number_format((float)$distance_km, 2, '.', ''); ?></span>
								<span data-distance-unit="metric"><?php echo esc_html__('km','supavapes'); ?></span>
							</p>
						</div>
						<?php if($store['pickup_avialablility'] == 1){ ?>
						<div class="surface-pick-up-item__availability"> 
							<svg width="14" height="15" class="surface-pick-up-icon" viewBox="0 0 14 15" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M13.6747 2.88135C13.2415 2.44761 12.5381 2.44789 12.1044 2.88135L5.03702 9.94902L1.89587 6.8079C1.46213 6.37416 0.759044 6.37416 0.325304 6.8079C-0.108435 7.24163 -0.108435 7.94472 0.325304 8.37846L4.25157 12.3047C4.4683 12.5215 4.7525 12.6301 5.03672 12.6301C5.32094 12.6301 5.6054 12.5217 5.82213 12.3047L13.6747 4.45189C14.1084 4.01845 14.1084 3.31507 13.6747 2.88135Z" fill="#51A551"></path>
							</svg>            
							<?php echo esc_html__('Pickup available, usually ready in 24 hours','supavapes'); ?>            
						</div>
						<?php } else { ?>
						<div class="surface-pick-up-item__availability unavailable"> 
							<svg width="15" height="15" class="surface-pick-up-icon" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M13.7331 11.7904L2.76068 0.817889C2.28214 0.339349 1.50629 0.339349 1.02848 0.817889L0.450213 1.39528C-0.0283263 1.87397 -0.0283263 2.64981 0.450213 3.12759L11.4227 14.1001C11.9014 14.5786 12.6772 14.5786 13.155 14.1001L13.7324 13.5227C14.2118 13.0449 14.2118 12.2689 13.7331 11.7904Z" fill="#EC4E34"/>
							<path d="M11.4227 0.818263L0.450213 11.7908C-0.0283263 12.2693 -0.0283263 13.0453 0.450213 13.5231L1.0276 14.1005C1.50629 14.579 2.28214 14.579 2.75991 14.1005L13.7331 3.12873C14.2118 2.65019 14.2118 1.87434 13.7331 1.39657L13.1557 0.819181C12.6772 0.339723 11.9014 0.339723 11.4227 0.818263Z" fill="#EC4E34"/>
							</svg>
							<?php echo esc_html__('Pickup currently unavailable','supavapes'); ?>           
						</div> 
						<?php } ?>
						<address class="surface-pick-up-item__address-info">
							<p>
								<a href="<?php echo esc_url($google_maps_link); ?>" target="_blank"> <?php echo esc_html($store['address_line_1'],'supavapes'); ?><br>
								<?php echo esc_html($store['address_line_2'],'supavapes'); ?></a><br>
								<a href="tel:<?php echo esc_html($store['contact_number'],'supavapes'); ?>"><?php echo esc_html($store['contact_number'],'supavapes'); ?></a><br>
							</p>
						</address>
					</li>
				<?php endforeach; ?>   
			</ul>
			<?php
			$html_content = ob_get_clean();
			wp_send_json_success($html_content);
		} else {
			wp_send_json_error('No terms found.');
		}
	} else {
		wp_send_json_error('Invalid product ID.');
	}

}

/**
 * If the function, `supavapes_add_meta_boxes_callback` doesn't exist.
 */
if ( ! function_exists( 'supavapes_add_meta_boxes_callback' ) ) {
	/**
	 * Remove original order notes metabox and add new one.
	 * The functionality to remain the same with addition to allowing to upload media files with the notes.
	 *
	 * @since 1.0.0
	 */
	function supavapes_add_meta_boxes_callback() {
		global $current_screen;

		/**
		 * Removing the shop order notes metabox.
		 * We're adding our own to allow uploading media attachments along with textual notes.
		 */
		remove_meta_box( 'woocommerce-order-notes' , 'woocommerce_page_wc-orders' , 'side' );

		// Add custom metabox for order notes that includes media uploads.
		add_meta_box(
			'woocommerce-order-notes',
			__( 'Order notes with media', 'supavapes' ),
			'supavapes_order_notes_metabox_callback',
			'woocommerce_page_wc-orders',
			'side',
			'default'
		);

		// Add metabox on the shop orders page to allow the shop managers to assign an order to themselves.
		add_meta_box(
			'shop-manager-order-assignment',
			__( 'Shop Managers', 'supavapes' ),
			'supavapes_shop_manager_oder_assignment_callback',
			'woocommerce_page_wc-orders',
			'side',
			'high'
		);

		// Add metabox to showcase the spport request actions.
		add_meta_box(
			'support-request-actions',
			__( 'Support Request Actions', 'supavapes' ),
			'supavapes_support_request_actions_callback',
			'support_request',
			'side',
			'high'
		);
	}
}

add_action( 'add_meta_boxes', 'supavapes_add_meta_boxes_callback', 99 );


// Render the meta box content
function supavapes_support_request_actions_callback($post) {
	
	wp_nonce_field('support_request_nonce_action', 'support_request_nonce_field');
	$approved = get_post_meta($post->ID, '_support_request_approved', true);
	$declined = get_post_meta($post->ID, '_support_request_declined', true);
	if ($approved) {
		echo '<p style="color: green;">' . __('This request is approved.', 'supavapes') . '</p>';
	} elseif ($declined) {
		echo '<p style="color: red;">' . __('This request is declined.', 'supavapes') . '</p>';
	} else {
		echo '<p><strong>' . __('Status:', 'supavapes') . '</strong> ' . __('Pending', 'supavapes') . '</p>';
		echo '<div id="support-request-loader" style="display:none;">';
		echo '<img src="https://woocommerce-401163-4488997.cloudwaysapps.com/wp-content/uploads/2024/07/spinner.gif" alt="Loading...">';
		echo '</div>';
		echo '<button type="button" class="button approve-support-request" data-id="' . $post->ID . '">' . __('Approve', 'supavapes') . '</button> ';
		echo '<button type="button" class="button decline-support-request" data-id="' . $post->ID . '">' . __('Decline', 'supavapes') . '</button>';
	}
	echo '
	<div id="decline-dialog" title="Decline Support Request" style="display: none;">
		<p>Please provide a reason for declining the support request:</p>
		<textarea id="decline-reason" rows="4" cols="30"></textarea>
		<button type="button" id="submit-decline-reason" class="button" data-id="' . $post->ID . '">' . __('Decline Request', 'supavapes') . '</button>
	</div>
	';
}


// Meta box callback function to display the dropdown
function supavapes_shop_manager_oder_assignment_callback( $post ) {

	$shop_manager_id = get_post_meta( $post->ID, '_shop_manager', true );
	$shop_managers = get_users( array( 'role' => 'shop_manager' ) );
	echo '<p><label for="shop-manager">' . __( 'Select Shop Manager:', 'supavapes' ) . '</label> ';
	echo '<select id="shop-manager" name="shop_manager">';
	echo '<option value="">' . __( 'Select a manager', 'supavapes' ) . '</option>';
	foreach ( $shop_managers as $manager ) {
		$selected = selected( $shop_manager_id, $manager->ID, false );
		echo '<option value="' . esc_attr( $manager->ID ) . '" ' . $selected . '>' . esc_html( $manager->display_name ) . '</option>';
	}
	echo '</select></p>';

}

// Save the custom meta box data when order is saved or updated
function sv_save_shop_manager_meta_box_data( $order_id ) {

	if ( isset( $_POST['shop_manager'] ) ) {
		update_post_meta(
			$order_id,
			'_shop_manager',
			sanitize_text_field( $_POST['shop_manager'] )
		);
	}

}
add_action( 'woocommerce_process_shop_order_meta', 'sv_save_shop_manager_meta_box_data' );


// Function to get the saved shop manager ID
function get_saved_shop_manager_id( $order_id ) {

	$shop_manager_id = get_post_meta( $order_id, '_shop_manager', true );
	return $shop_manager_id;

}


// Display the selected shop manager value in order details
function sv_display_shop_manager_in_order_details( $order ) {
	
	$shop_manager_id = get_saved_shop_manager_id( $order->get_id() );
	if ( $shop_manager_id ) {
		echo '<p><strong>' . __( 'Shop Manager:', 'supavapes' ) . '</strong> ' . esc_html( get_userdata( $shop_manager_id )->display_name ) . '</p>';
	}

}
add_action( 'woocommerce_admin_order_data_after_order_details', 'sv_display_shop_manager_in_order_details', 10, 1 );


function get_geocode($address) {

	$api_key = 'AIzaSyDRfDT-5iAbIjrIqVORmmeXwAjDgLJudiM';
	$address = urlencode($address);
	$url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key={$api_key}";
	$response = wp_remote_get($url);
	if (is_wp_error($response)) {
		return false;
	}
	$body = wp_remote_retrieve_body($response);
	$data = json_decode($body);
	if ($data->status == 'OK') {
		$location = $data->results[0]->geometry->location;
		return array('lat' => $location->lat, 'lng' => $location->lng);
	} else {
		return false;
	}

}

function haversine_distance($lat1, $lon1, $lat2, $lon2) {    

	$earth_radius = 6371; // Earth radius in kilometers
	$dlat = deg2rad($lat2 - $lat1);
	$dlon = deg2rad($lon2 - $lon1);
	$a = sin($dlat/2) * sin($dlat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dlon/2) * sin($dlon/2);
	$c = 2 * atan2(sqrt($a), sqrt(1-$a));
	$distance = $earth_radius * $c;
	return $distance;

}

function get_woocommerce_sales_data() {
	if (!is_user_logged_in()) {
		wp_send_json_error('Unauthorized', 403);
		return;
	}
	global $wpdb;
	$user_id = get_current_user_id();
	$customer_id = $wpdb->get_var($wpdb->prepare("SELECT customer_id FROM {$wpdb->prefix}wc_customer_lookup WHERE user_id = %d", $user_id));
	if (!$customer_id) {
		wp_send_json_error('Customer ID not found for the current user.', 404);
		return;
	}
	$type = $_POST['type'];
	$year = $_POST['year'] ? intval($_POST['year']) : date('Y');
	$sales_data = [];
	$current_month = date('n');
	$current_year = date('Y');

	switch ($type) {
		case 'day':
			$start_of_week = date('Y-m-d', strtotime('monday this week'));
			$end_of_week = date('Y-m-d', strtotime('sunday this week'));
			$dates = [];
			for ($i = 0; $i < 7; $i++) {
				$date = date('Y-m-d', strtotime("+$i day", strtotime($start_of_week)));
				$dates[$date] = 0;
			}
			$results = $wpdb->get_results(
				$wpdb->prepare("
					SELECT DATE(date_created) as order_date, SUM(total_sales) as sales
					FROM {$wpdb->prefix}wc_order_stats
					WHERE DATE(date_created) BETWEEN %s AND %s
					AND status IN ('wc-completed', 'wc-processing')
					AND customer_id = %d
					AND YEAR(date_created) = %d
					GROUP BY DATE(date_created)
				", $start_of_week, $end_of_week, $customer_id, $year)
			);
			foreach ($results as $result) {
				$dates[$result->order_date] = $result->sales;
			}
			$sales_data = array_values($dates);
			break;
		case 'week':
			$first_day_of_month = date('Y-m-01', strtotime("$current_year-$current_month-01"));
			$last_day_of_month = date('Y-m-t', strtotime($first_day_of_month));
			$num_weeks = (int) ceil((date('j', strtotime($last_day_of_month)) - date('N', strtotime($first_day_of_month)) + 1) / 7);
			for ($week = 0; $week < $num_weeks; $week++) {
				$start_of_week = date('Y-m-d', strtotime("$first_day_of_month +". ($week * 7) ." days"));
				$end_of_week = date('Y-m-d', strtotime("$start_of_week +6 days"));
				if (strtotime($end_of_week) > strtotime($last_day_of_month)) {
					$end_of_week = $last_day_of_month;
				}
				if (strtotime($start_of_week) > strtotime('today')) {
					$sales_data[] = 0;
				} else {
					$result = $wpdb->get_var(
						$wpdb->prepare("
							SELECT SUM(total_sales)
							FROM {$wpdb->prefix}wc_order_stats
							WHERE DATE(date_created) BETWEEN %s AND %s
							AND status IN ('wc-completed', 'wc-processing')
							AND customer_id = %d
							AND YEAR(date_created) = %d
						", $start_of_week, $end_of_week, $customer_id, $year)
					);
					$sales_data[] = $result ? $result : 0;
				}
			}
			break;
		case 'month':
			for ($i = 1; $i <= 12; $i++) {
				if ($i > $current_month) {
					$sales_data[] = 0;
				} else {
					$month_start = date('Y-m-01', mktime(0, 0, 0, $i, 1, $year));
					$month_end = date('Y-m-t', mktime(0, 0, 0, $i, 1, $year));
					$result = $wpdb->get_var(
						$wpdb->prepare("
							SELECT SUM(total_sales)
							FROM {$wpdb->prefix}wc_order_stats
							WHERE DATE(date_created) BETWEEN %s AND %s
							AND status IN ('wc-completed', 'wc-processing')
							AND customer_id = %d
							AND YEAR(date_created) = %d
						", $month_start, $month_end, $customer_id, $year)
					);
					$sales_data[] = $result ? $result : 0;
				}
			}
			break;
	}

	if (empty($sales_data)) {
		wp_send_json_error('No purchase data found.', 404);
	} else {
		wp_send_json_success([$type => $sales_data]);
	}
}
add_action('wp_ajax_get_sales_data', 'get_woocommerce_sales_data');
add_action('wp_ajax_nopriv_get_sales_data', 'get_woocommerce_sales_data');

add_filter('woocommerce_cart_item_name', 'add_quantity_rule_message_to_cart_item_name', 20, 3);

function add_quantity_rule_message_to_cart_item_name( $product_name, $cart_item, $cart_item_key ) {

	global $wp_current_filter;
	if( in_array('wp_ajax_render_minicart_data', $wp_current_filter, true)) {
		return $product_name;
	}
	// echo "innn";
	// debug($cart_item);
	$discount_message = '';
	if (isset($cart_item['discounted_price']) && $cart_item['discounted_price'] > 0) {
		$discount_amount = wc_price($cart_item['discounted_price']);
		$discount_message = sprintf(__('Discounted price %s for this item.', 'supavapes'), $discount_amount);
		$discount_message = '<div class="discount_message" style="color: green;">' . $discount_message . '</div>';
	}
	if (isset($cart_item['wdr_free_product']) && 'Free' === $cart_item['wdr_free_product']) {
		return $product_name . $discount_message;
	}
	$product_id = $cart_item['product_id'];
	$rule_message = get_woo_discount_rules_msg($product_id, $cart_item['quantity']);
	if ($rule_message) {
		$rule_message = '<div class="quantity-rule-class" style="color: blue;">' . $rule_message . '</div>';
	}
	return '<div class="cart-product-wrapper">' . $product_name . $discount_message . $rule_message . '</div>';
}

function get_woo_discount_rules_msg( $product_id, $current_quantity ) {
	$manage_discount  = new Wdr\App\Controllers\ManageDiscount();
	$messages         = array();
	foreach ( $manage_discount->getDiscountRules() as $rule ) {
		if ( ! $rule->isEnabled() ) {
			continue;
		}
		$cs_filters = array();
		$filters    = $rule->getFilter();
		foreach ( $filters as $f_key => $filter ) {
			$type               = $rule->getFilterType( $filter );
			$method             = $rule->getFilterMethod( $filter );
			$values             = (array) $rule->getFilterOptionValue( $filter );
			$products           = array();
			$categories         = array();
			$exclude_products   = array();
			$exclude_categories = array();
			$cs_filter           = array();
			$cs_filter['type']   = $type;
			$cs_filter['method'] = $method;
			switch ( $type ) {
				case "product_category":
					if ( $method === "in_list" ) {
						$categories = array_merge( $categories, $values );
						$categories = array_unique( $categories );
						$cs_filter['product_category'] = $categories;
					} else {
						$exclude_categories = array_merge( $exclude_categories, $values );
						$cs_filter['exclude_product_category'] = $exclude_categories;
					}
					break;
				case "products":
					if ( $method === "in_list" ) {
						$parent_product_id = (array) $rule->getFilterOptionParentValue( $filter );
						if ( ! empty( $parent_product_id ) ) {
							$values = array_merge( $values, $parent_product_id );
						}
						$products              = array_merge( $products, $values );
						$products              = array_unique( $products );
						$cs_filter['products'] = $products;
					} else {
						$exclude_products              = array_merge( $exclude_products, $values );
						$cs_filter['exclude_products'] = $exclude_products;
					}
					break;
			}
			$cs_filters[ $f_key ] = $cs_filter;
		}
		$rule_quantity = array();
		$conditions    = $rule->getConditions();
		if ( ! empty( $conditions ) ) {
			foreach ( $conditions as $c_key => $condition ) {
				if ( isset( $condition->options ) ) {
					$rule_quantity[ $c_key ] = $condition->options;
				}
			}
			if ( count( $rule_quantity ) > 1 ) {
				$rule_quantity['relationship'] = $rule->getRelationship( 'condition', 'and' );
			}
		}
		$is_notice_visible = false;
		$required_quantity = 0;
		if ( isset( $cs_filters ) ) {
			foreach ( $cs_filters as $cs_filter ) {
				switch ( $cs_filter['type'] ) {
					case "product_category":
						if ( ! empty( $cs_filter['product_category'] ) ) {
							$args         = array(
								'post_status' => 'publish',
								'post_type'   => 'product',
								'numberposts' => - 1,
								'fields'      => 'ids',
								'tax_query'   => array(
									array(
										'taxonomy' => 'product_cat',
										'field'    => 'term_id',
										'terms'    => $cs_filter['product_category'],
										// When you have more term_id's seperate them by komma.
										'operator' => 'IN'
									)
								)
							);
							$cat_products = get_posts( $args );  
							if ( in_array( $product_id, $cat_products, true ) ) {
								$is_notice_visible = true;
							}
						}
						break;
					case "products":
						if ( ! empty( $cs_filter['products'] ) && in_array( (int) $product_id, array_map( 'intval', $cs_filter['products'] ), true ) ) {
							$is_notice_visible = true;
						}
						break;
				}
			}
		}
		if ( true === $is_notice_visible ) {
			if ( isset( $rule_quantity ) ) {
				foreach ( $rule_quantity as $woo_rule_quantity ) {
					$required_quantity = $woo_rule_quantity->value - $current_quantity;
				}
			}
		}
		if ( $required_quantity > 0 ) {
			switch ( $rule->getRuleDiscountType() ) {
				case 'wdr_simple_discount':
					$rule_discount  = ( $rule->getProductAdjustments() ) ? $rule->getProductAdjustments() : false;
					$final_discount = '';
					if ( isset( $rule_discount->type ) ) {
						if ( 'percentage' === $rule_discount->type ) {
							$final_discount = $rule_discount->value . '%';
						} elseif ( 'flat' === $rule_discount->type ) {
							$final_discount = get_woocommerce_currency_symbol() . $rule_discount->value . ' OFF';
						} elseif ( 'fixed_price' === $rule_discount->type ) {
							$final_discount = get_woocommerce_currency_symbol() . $rule_discount->value . ' Per Item';
						}
					}
					$messages[] = sprintf( __( '<a data-qty="%d" class="supa-add-dis">Add %d</a> more to claim %s discount!', 'supavapes' ), $required_quantity, $required_quantity, $final_discount );
					break;
				case 'wdr_buy_x_get_x_discount':
					$buy_x_get_x_adjustments = \WDRPro\App\Rules\BuyXGetX::getBuyXGetXAdjustments( $rule );
					$buy_x_get_x_adjustments = ( isset( $buy_x_get_x_adjustments->ranges ) && ! empty( $buy_x_get_x_adjustments->ranges ) ) ? $buy_x_get_x_adjustments->ranges : '';
					if ( isset( $buy_x_get_x_adjustments ) && ! empty( $buy_x_get_x_adjustments ) ) {
						foreach ( $buy_x_get_x_adjustments as $buyx_getx_adjustment ) {
							if ( 'free_product' === $buyx_getx_adjustment->free_type ) {
								$free_qty = $buyx_getx_adjustment->free_qty;
								$messages[]  = sprintf( __( '<a data-qty="%d" class="supa-add-dis">Add %d</a> more to get %s free', 'supavapes' ), $required_quantity, $required_quantity, $free_qty );
							} elseif ( 'percentage' === $buyx_getx_adjustment->free_type ) {
								$free_qty   = $buyx_getx_adjustment->free_qty;
								$free_value = $buyx_getx_adjustment->free_value . '%';
								$messages[]    = sprintf( __( '<a data-qty="%d" class="supa-add-dis"Add %d</a> more to get %s discount for %s items', 'supavapes' ),$required_quantity, $required_quantity, $free_value, $free_qty, );
							} elseif ( 'flat' === $buyx_getx_adjustment->free_type ) {
								$free_qty   = $buyx_getx_adjustment->free_qty;
								$free_value = get_woocommerce_currency_symbol() . $buyx_getx_adjustment->free_value . ' OFF';
								$messages[]    = sprintf( __( '<a data-qty="%d" class="supa-add-dis">Add %d</a> more to get %s discount for %s items', 'supavapes' ),$required_quantity, $required_quantity, $free_value, $free_qty, );
							}
						}
					}
					break;
				case 'wdr_buy_x_get_y_discount':
					$buy_x_get_y_adjustments = \WDRPro\App\Rules\BuyXGetY::getBuyXGetYAdjustments( $rule );
					$buy_x_get_y_adjustments = ( isset( $buy_x_get_y_adjustments->ranges ) && ! empty( $buy_x_get_y_adjustments->ranges ) ) ? $buy_x_get_y_adjustments->ranges : '';
					if ( isset( $buy_x_get_y_adjustments ) && ! empty( $buy_x_get_y_adjustments ) ) {
						foreach ( $buy_x_get_y_adjustments as $buy_x_get_y_adjustment ) {
							if ( 'free_product' === $buy_x_get_y_adjustment->free_type ) {
								$free_products = $buy_x_get_y_adjustment->products;
								$product_html = '<ul>';
								foreach ( $free_products as $free_product ) {
									$product_html .= sprintf('<li><a href="%s">%s</a></li>', get_permalink($free_product), get_the_title($free_product) );
								}
								$product_html .= '</ul>';
								$free_qty = $buy_x_get_y_adjustment->free_qty;
								$message  = sprintf( __( '<a data-qty="%d" class="supa-add-dis">Add %d</a> more to get below (%s) free product with %s quantity ', 'supavapes' ), $required_quantity, $required_quantity, count( $free_products ), $free_qty );
								$message  .= $product_html;
								$messages[] = $message;
							} elseif ( 'percentage' === $buy_x_get_y_adjustment->free_type ) {
								$free_products = $buy_x_get_y_adjustment->products;
								$product_html = '<ul>';
								foreach ( $free_products as $free_product ) {
									$product_html .= sprintf('<li><a href="%s">%s</a></li>', get_permalink($free_product), get_the_title($free_product) );
								}
								$product_html .= '</ul>';
								$free_qty   = $buy_x_get_y_adjustment->free_qty;
								$free_value = $buy_x_get_y_adjustment->free_value . '%';
								$message    = sprintf( __( '<a data-qty="%d" class="supa-add-dis">Add %d</a> more to get %s discount for below (%s) product with %s quantity', 'supavapes' ),$required_quantity, $required_quantity, $free_value, count( $free_products ), $free_qty );
								$message  .= $product_html;
								$messages[] = $message;
							} elseif ( 'flat' === $buy_x_get_y_adjustment->free_type ) {
								$free_products = $buy_x_get_y_adjustment->products;
								$product_html = '<ul>';
								foreach ( $free_products as $free_product ) {
									$product_html .= sprintf('<li><a href="%s">%s</a></li>', get_permalink($free_product), get_the_title($free_product) );
								}
								$product_html .= '</ul>';

								$free_qty   = $buy_x_get_y_adjustment->free_qty;
								$free_value = $buy_x_get_y_adjustment->free_value . ' OFF';
								$message    = sprintf( __( '<a data-qty="%d" class="supa-add-dis">Add %d</a> more to get %s discount for below (%s) product with %s quantity', 'supavapes' ), $required_quantity,$required_quantity, $free_value, count( $free_products ), $free_qty );
								$message  .= $product_html;
								$messages[] = $message;
							}
						}
					}
					break;
			}
		}
	}
	return !empty( $messages ) ? '<p class="supa-discount-wrap">'.implode( '</p><p class="supa-discount-wrap">', $messages ).'</p>' : '';
}

/**
 * Active offer from discount data.
 * @return array
 */
function supa_active_offers_from_discount() {
	$manage_discount = new Wdr\App\Controllers\ManageDiscount();
	$active_offers   = array();
	foreach ( $manage_discount->getDiscountRules() as $rule ) {
		if ( ! $rule->isEnabled() ) {
			continue;
		}
		$active_offer                  = array();
		$active_offer['id']            = $rule->getId();
		$active_offer['title']         = $rule->getTitle();
		$active_offer['discount_type'] = $rule->getRuleDiscountType();
		$conditions = $rule->getConditions();
		if ( ! empty( $conditions ) ) {
			foreach ( $conditions as $condition ) {
				if ( isset( $condition->options ) ) {
					$active_offer['buy'] = $condition->options->value;
				}
			}
		}
		$filters    = $rule->getFilter();
		foreach ( $filters as $f_key => $filter ) {
			$type               = $rule->getFilterType( $filter );
			$method             = $rule->getFilterMethod( $filter );
			$values             = (array) $rule->getFilterOptionValue( $filter );
			$products           = array();
			$categories         = array();
			$exclude_products   = array();
			$exclude_categories = array();
			switch ( $type ) {
				case "product_category":
					if ( $method === "in_list" ) {
						$categories = array_merge( $categories, $values );
						$categories = array_unique( $categories );
						$active_offer['product_category'] = $categories;
					} else {
						$exclude_categories = array_merge( $exclude_categories, $values );
						$active_offer['exclude_product_category'] = $exclude_categories;
					}
					break;
				case "products":
					if ( $method === "in_list" ) {
						$parent_product_id = (array) $rule->getFilterOptionParentValue( $filter );
						if ( ! empty( $parent_product_id ) ) {
							$values = array_merge( $values, $parent_product_id );
						}
						$products              = array_merge( $products, $values );
						$products              = array_unique( $products );
						$active_offer['products'] = $products;
					} else {
						$exclude_products              = array_merge( $exclude_products, $values );
						$active_offer['exclude_products'] = $exclude_products;
					}
					break;
			}
		}
		switch ( $rule->getRuleDiscountType() ) {
			case 'wdr_simple_discount':
				$rule_discount = ( $rule->getProductAdjustments() ) ? $rule->getProductAdjustments() : false;
				if ( isset( $rule_discount->type ) ) {
					if ( 'percentage' === $rule_discount->type ) {
						$active_offer['discount_value'] = $rule_discount->value . '%';
					} elseif ( 'flat' === $rule_discount->type ) {
						$active_offer['discount_value'] = get_woocommerce_currency_symbol() . $rule_discount->value . ' OFF';
					} elseif ( 'fixed_price' === $rule_discount->type ) {
						$active_offer['discount_value'] = get_woocommerce_currency_symbol() . $rule_discount->value . ' Per Item';
					}
				}
				break;
			case 'wdr_buy_x_get_x_discount':
				$buy_x_get_x_adjustments = \WDRPro\App\Rules\BuyXGetX::getBuyXGetXAdjustments( $rule );
				$buy_x_get_x_adjustments = ( isset( $buy_x_get_x_adjustments->ranges ) && ! empty( $buy_x_get_x_adjustments->ranges ) ) ? $buy_x_get_x_adjustments->ranges : '';

				if ( isset( $buy_x_get_x_adjustments ) && ! empty( $buy_x_get_x_adjustments ) ) {
					foreach ( $buy_x_get_x_adjustments as $buyx_getx_adjustment ) {
						$active_offer['buy_type'] = $buyx_getx_adjustment->free_type;
						if ( 'free_product' === $buyx_getx_adjustment->free_type ) {
							$active_offer['free_qty'] = $buyx_getx_adjustment->free_qty;
						} elseif ( 'percentage' === $buyx_getx_adjustment->free_type ) {
							$free_value                     = get_woocommerce_currency_symbol() . $buyx_getx_adjustment->free_value . '%';
							$active_offer['free_qty']       = $buyx_getx_adjustment->free_qty;
							$active_offer['discount_value'] = $free_value;
						} elseif ( 'flat' === $buyx_getx_adjustment->free_type ) {
							$free_value                     = get_woocommerce_currency_symbol() . $buyx_getx_adjustment->free_value . ' OFF';
							$active_offer['free_qty']       = $buyx_getx_adjustment->free_qty;
							$active_offer['discount_value'] = $free_value;
						}
					}
				}
				break;
			case 'wdr_buy_x_get_y_discount':
				$buy_x_get_y_adjustments = \WDRPro\App\Rules\BuyXGetY::getBuyXGetYAdjustments( $rule );
				$buy_x_get_y_adjustments = ( isset( $buy_x_get_y_adjustments->ranges ) && ! empty( $buy_x_get_y_adjustments->ranges ) ) ? $buy_x_get_y_adjustments->ranges : '';

				if ( isset( $buy_x_get_y_adjustments ) && ! empty( $buy_x_get_y_adjustments ) ) {
					foreach ( $buy_x_get_y_adjustments as $buy_x_get_y_adjustment ) {
						$active_offer['buy_type'] = $buy_x_get_y_adjustment->free_type;
						if ( 'free_product' === $buy_x_get_y_adjustment->free_type ) {
							$active_offer['free_products'] = $buy_x_get_y_adjustment->products;
							$active_offer['free_qty']      = $buy_x_get_y_adjustment->free_qty;
						} elseif ( 'percentage' === $buy_x_get_y_adjustment->free_type ) {
							$free_value                     = $buy_x_get_y_adjustment->free_value . '%';
							$active_offer['free_products']  = $buy_x_get_y_adjustment->products;
							$active_offer['free_qty']       = $buy_x_get_y_adjustment->free_qty;
							$active_offer['discount_value'] = $free_value;
						} elseif ( 'flat' === $buy_x_get_y_adjustment->free_type ) {
							$free_value                     = $buy_x_get_y_adjustment->free_value . ' OFF';
							$active_offer['free_products']  = $buy_x_get_y_adjustment->products;
							$active_offer['free_qty']       = $buy_x_get_y_adjustment->free_qty;
							$active_offer['discount_value'] = $free_value;
						}
					}
				}
				break;
		}
		$active_offers[] = $active_offer;
	}
	return $active_offers;
}

function get_browser_name($user_agent) {
	if (strpos($user_agent, 'Opera') || strpos($user_agent, 'OPR/')) return 'Opera';
	elseif (strpos($user_agent, 'Edge')) return 'Edge';
	elseif (strpos($user_agent, 'Chrome')) return 'Chrome';
	elseif (strpos($user_agent, 'Safari')) return 'Safari';
	elseif (strpos($user_agent, 'Firefox')) return 'Firefox';
	elseif (strpos($user_agent, 'MSIE') || strpos($user_agent, 'Trident/7')) return 'Internet Explorer';
	return 'Unknown';
}

function capture_browser_device_info($user_login, $user) {
	$user_id = $user->ID;
	// Get browser information
	$user_agent = $_SERVER['HTTP_USER_AGENT'];
	$browser_info = get_browser_name($user_agent);
	$device_name = get_device_name_from_user_agent($user_agent);
	// Save browser information
	update_user_meta($user_id, 'last_login_browser', $browser_info);
	// Parse user agent string to get device information
	// Store the device name in user meta
	update_user_meta($user_id, 'last_login_device', $device_name);
	// // Save last login time and IP
	// update_user_meta($user_id, 'last_login', current_time('mysql'));
	// update_user_meta($user_id, 'last_login_ip', $_SERVER['REMOTE_ADDR']);
}
add_action('wp_login', 'capture_browser_device_info', 10, 2);
// add_action('woocommerce_created_customer', 'capture_browser_device_info', 10, 1);


// Function to get device name from user agent string
function get_device_name_from_user_agent($user_agent) {
	if (strpos($user_agent, 'Windows') !== false) {
		return 'Windows PC';
	} elseif (strpos($user_agent, 'Mac') !== false) {
		return 'Macintosh';
	} elseif (strpos($user_agent, 'iPhone') !== false) {
		return 'iPhone';
	} elseif (strpos($user_agent, 'iPad') !== false) {
		return 'iPad';
	} elseif (strpos($user_agent, 'Android') !== false) {
		return 'Android Device';
	} else {
		return 'Unknown Device';
	}
}

add_filter('woocommerce_cart_item_name', 'add_free_product_message_to_cart_item_name', 20, 3);
function add_free_product_message_to_cart_item_name($product_name, $cart_item, $cart_item_key) {
	$output = '<div class="product-title-wrapper">';    
	$output .= $product_name;
	if (isset($cart_item['awdr_free_product_display'])) {
		$free_product_display = $cart_item['awdr_free_product_display'];
		$output .= '<br>' . $free_product_display;
	}
	$output .= wc_get_formatted_cart_item_data($cart_item);
	$output .= '</div>';
	return $output;
}


// Remove the "Add New" submenu for the support_request post type
add_action('admin_menu', 'remove_add_new_for_support_request');
function remove_add_new_for_support_request() {
	global $submenu;
	if (isset($submenu['edit.php?post_type=support_request'])) {
		foreach ($submenu['edit.php?post_type=support_request'] as $key => $value) {
			if (in_array(__('Add New', 'supavapes'), $value)) {
				unset($submenu['edit.php?post_type=support_request'][$key]);
			}
		}
	}
}


add_filter('use_block_editor_for_post_type', 'sv_use_block_editor_for_post_type_callback',10,2);
function sv_use_block_editor_for_post_type_callback( $current_status, $post_type ) {
	if ( empty( $post_type ) ) {
		return $current_status;
	}
	if ( 'support_request' === $post_type ) {
		return false;
	}
	return $current_status;
}


add_action('woocommerce_order_status_changed', 'schedule_feedback_email', 10, 4);
function convert_to_seconds($duration_type, $duration_value) {
	$seconds = 0;
	switch ($duration_type) {
		case 'minutes':
			$seconds = $duration_value * 60;
			break;
		case 'hours':
			$seconds = $duration_value * 60 * 60;
			break;
		case 'days':
			$seconds = $duration_value * 24 * 60 * 60;
			break;
	}
	return $seconds;
}

function schedule_feedback_email($order_id, $old_status, $new_status, $order) {
	if ($new_status === 'delivered') {
		// Fetch the ACF field values
		$duration_type = get_field('feedback_email_duration', 'option');
		$minutes = get_field('minutes', 'option');
		$days = get_field('days', 'option');
		$hours = get_field('hours', 'option');
		if($duration_type == 'minutes'){
			$duration_value = $minutes;
		}else if($duration_type == 'days'){
			$duration_value = $days;
		}else if($duration_type == 'hours'){
			$duration_value = $hours;
		}
		// Ensure the duration value is valid
		if ($duration_value > 0 && in_array($duration_type, array('minutes', 'hours', 'days'))) {
			// Convert the delay to seconds
			$delivery_time = time() + convert_to_seconds($duration_type, $duration_value);
			// Schedule the feedback email
			wp_schedule_single_event($delivery_time, 'send_feedback_email', array($order_id));
		}
	}
}

function getPostViews($postID){
	$count_key = 'post_views_count';
	$count = get_post_meta($postID, $count_key, true);
	if($count==''){
		delete_post_meta($postID, $count_key);
		add_post_meta($postID, $count_key, '0');
		return "0";
	}
	return $count;
}

function setPostViews($postID) {
	$count_key = 'post_views_count';
	$count = get_post_meta($postID, $count_key, true);
	if($count==''){
		$count = 0; 
		delete_post_meta($postID, $count_key);
		add_post_meta($postID, $count_key, '0');
	}else{
		$count++;
		update_post_meta($postID, $count_key, $count);
	}
}
remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);

// Function to add the shortcode to the post content
function sv_set_post_views($content) {
	if (is_single() && in_the_loop() && is_main_query()) {
		$content .= setPostViews(get_the_ID());        ;
	}
	return $content;
}
add_filter('the_content', 'sv_set_post_views');

// Shortcode to display related posts based on the current post's category
function sv_related_posts_shortcode($atts) {

	$output = '';
	ob_start();
	require locate_template('templates/shortcodes/realted-blogs.php');
	$output = ob_get_clean();
	return $output;
}
add_shortcode('related_posts', 'sv_related_posts_shortcode');


function sv_filter_blogs() {

	ob_start(); 
	$category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
	$search_term = isset($_POST['search_term']) ? $_POST['search_term'] : '';
	$paged = isset($_POST['page']) ? intval($_POST['page']) : 1;
	$args = array(
		'post_type' => 'post',
		'posts_per_page' => -1,
		'paged' => $paged,
	);
	if ($category_id) {
		$args['cat'] = $category_id;
	}
	if (!empty($search_term)) {
		$args['s'] = $search_term;
	}
	$query = new WP_Query($args);
	if ($query->have_posts()) {
		while ($query->have_posts()) {
			$query->the_post();
			$author_id = get_the_author_meta('ID');
			$author_name = get_the_author();
			$published_date = get_the_date();
			$post_content = get_the_content();
			$view_count = getPostViews(get_the_ID());
			$comment_count = get_comments_number(get_the_ID());
			?>
			<div class="sv-blog">
				<a href="<?php the_permalink(); ?>" class="sv-blog-img">
					<?php if (has_post_thumbnail()) : ?>
						<img src="<?php the_post_thumbnail_url('full'); ?>" alt="<?php the_title(); ?>">
					<?php endif; ?>
				</a>
				<div class="sv-blog-content">
					<a href="<?php the_permalink(); ?>" class="sv-blog-title"><?php the_title(); ?></a>
					<div class="sv-blog-meta-detail">
						<div class="sv-blog-meta">
							<svg width="15" height="17" viewBox="0 0 15 17" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M7.15861 7.71054C9.25148 7.71054 10.9481 6.01393 10.9481 3.92107C10.9481 1.8282 9.25148 0.131592 7.15861 0.131592C5.06575 0.131592 3.36914 1.8282 3.36914 3.92107C3.36914 6.01393 5.06575 7.71054 7.15861 7.71054Z" fill="#424242"/>
								<path fill-rule="evenodd" clip-rule="evenodd" d="M13.8952 16.1316C14.0068 16.1316 14.1137 16.0873 14.1926 16.0084C14.2715 15.9295 14.3158 15.8225 14.3158 15.711V15.7105C14.3158 13.8121 13.5617 11.9915 12.2193 10.6491C10.8769 9.30675 9.05629 8.55261 7.1579 8.55261C3.20455 8.55261 0 11.7572 0 15.7105V15.711C0 15.8225 0.0443076 15.9295 0.123176 16.0084C0.202043 16.0873 0.309011 16.1316 0.420547 16.1316H13.8952Z" fill="#424242"/>
							</svg>
							<span class="sv-blog-meta-title author"><?php echo esc_html($author_name); ?></span>
						</div>
						<div class="sv-blog-meta">
							<svg width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path fill-rule="evenodd" clip-rule="evenodd" d="M13.4406 1.59187V0.525258C13.4364 0.386658 13.3783 0.255164 13.2788 0.158633C13.1792 0.0621017 13.046 0.00811768 12.9073 0.00811768C12.7687 0.00811768 12.6354 0.0621017 12.5359 0.158633C12.4363 0.255164 12.3783 0.386658 12.374 0.525258V1.05848H9.97402C9.90311 1.05848 9.83524 1.08677 9.78522 1.13646C9.73553 1.18648 9.70724 1.25435 9.70724 1.32509V1.59187C9.70297 1.73047 9.64491 1.86196 9.54536 1.95849C9.44582 2.05503 9.3126 2.10901 9.17393 2.10901C9.03526 2.10901 8.90205 2.05503 8.8025 1.95849C8.70295 1.86196 8.64489 1.73047 8.64062 1.59187V0.525258C8.63636 0.386658 8.5783 0.255164 8.47875 0.158633C8.3792 0.0621017 8.24599 0.00811768 8.10732 0.00811768C7.96865 0.00811768 7.83544 0.0621017 7.73589 0.158633C7.63634 0.255164 7.57828 0.386658 7.57401 0.525258V1.05848H5.17401C5.10311 1.05848 5.03524 1.08677 4.98521 1.13646C4.93553 1.18648 4.90724 1.25435 4.90724 1.32509V1.59187C4.90297 1.73047 4.84491 1.86196 4.74536 1.95849C4.64581 2.05503 4.5126 2.10901 4.37393 2.10901C4.23526 2.10901 4.10205 2.05503 4.0025 1.95849C3.90295 1.86196 3.84489 1.73047 3.84063 1.59187V0.525258C3.83636 0.386658 3.7783 0.255164 3.67875 0.158633C3.5792 0.0621017 3.44599 0.00811768 3.30732 0.00811768C3.16865 0.00811768 3.03544 0.0621017 2.93589 0.158633C2.83634 0.255164 2.77828 0.386658 2.77401 0.525258V1.05848H1.97401C1.62038 1.05848 1.28122 1.19896 1.03117 1.44902C0.781107 1.69908 0.640625 2.03823 0.640625 2.39187V14.6585C0.640625 15.0121 0.781107 15.3513 1.03117 15.6013C1.28122 15.8514 1.62038 15.9919 1.97401 15.9919H15.3072C15.6609 15.9919 16 15.8514 16.2501 15.6013C16.5001 15.3513 16.6406 15.0121 16.6406 14.6585V2.39187C16.6406 2.03823 16.5001 1.69908 16.2501 1.44902C16 1.19896 15.6609 1.05848 15.3072 1.05848H14.774C14.7031 1.05848 14.6352 1.08677 14.5852 1.13646C14.5355 1.18648 14.5072 1.25435 14.5072 1.32509V1.59187C14.503 1.73047 14.4449 1.86196 14.3454 1.95849C14.2458 2.05503 14.1126 2.10901 13.9739 2.10901C13.8353 2.10901 13.702 2.05503 13.6025 1.95849C13.5029 1.86196 13.4449 1.73047 13.4406 1.59187ZM15.574 5.32526V14.6585C15.574 14.7294 15.5457 14.7973 15.496 14.8473C15.446 14.897 15.3781 14.9253 15.3074 14.9253H1.97385C1.90294 14.9253 1.83507 14.897 1.78505 14.8473C1.73525 14.7971 1.70723 14.7293 1.70707 14.6586V5.32509L15.574 5.32526ZM10.2406 11.1919C10.2406 11.0504 10.1844 10.9148 10.0843 10.8148C9.98433 10.7147 9.84869 10.6585 9.70724 10.6585H7.57401C7.43256 10.6585 7.29692 10.7147 7.1969 10.8148C7.09688 10.9148 7.04067 11.0504 7.04063 11.1919V13.3253C7.04071 13.4667 7.09694 13.6023 7.19696 13.7023C7.29698 13.8022 7.43259 13.8584 7.57401 13.8585H9.70724C9.84866 13.8584 9.98427 13.8022 10.0843 13.7023C10.1843 13.6023 10.2405 13.4667 10.2406 13.3253V11.1919ZM14.5072 11.1919C14.5072 11.0504 14.451 10.9148 14.351 10.8148C14.251 10.7148 14.1154 10.6586 13.974 10.6585H11.8406C11.6992 10.6585 11.5635 10.7147 11.4635 10.8148C11.3635 10.9148 11.3073 11.0504 11.3072 11.1919V13.3253C11.3073 13.4667 11.3636 13.6023 11.4636 13.7023C11.5636 13.8022 11.6992 13.8584 11.8406 13.8585H13.974C14.1154 13.8584 14.251 13.8022 14.351 13.7022C14.4509 13.6022 14.5071 13.4667 14.5072 13.3253V11.1919ZM5.97401 11.1919C5.97397 11.0504 5.91776 10.9148 5.81774 10.8148C5.71772 10.7147 5.58208 10.6585 5.44063 10.6585H3.30724C3.16581 10.6586 3.03021 10.7148 2.93023 10.8148C2.83025 10.9148 2.77406 11.0504 2.77401 11.1919V13.3253C2.7741 13.4667 2.83031 13.6022 2.93029 13.7022C3.03027 13.8022 3.16584 13.8584 3.30724 13.8585H5.44063C5.58205 13.8584 5.71766 13.8022 5.81768 13.7023C5.9177 13.6023 5.97393 13.4667 5.97401 13.3253V11.1919ZM10.2406 6.92526C10.2406 6.78381 10.1844 6.64816 10.0843 6.54814C9.98433 6.44812 9.84869 6.39191 9.70724 6.39187H7.57401C7.43256 6.39191 7.29692 6.44812 7.1969 6.54814C7.09688 6.64816 7.04067 6.78381 7.04063 6.92526V9.05848C7.04067 9.19993 7.09688 9.33557 7.1969 9.43559C7.29692 9.53561 7.43256 9.59182 7.57401 9.59187H9.70724C9.84869 9.59182 9.98433 9.53561 10.0843 9.43559C10.1844 9.33557 10.2406 9.19993 10.2406 9.05848V6.92526ZM14.5072 6.92526C14.5072 6.78384 14.451 6.64822 14.351 6.5482C14.251 6.44819 14.1154 6.39196 13.974 6.39187H11.8406C11.6992 6.39191 11.5635 6.44812 11.4635 6.54814C11.3635 6.64816 11.3073 6.78381 11.3072 6.92526V9.05848C11.3073 9.19993 11.3635 9.33557 11.4635 9.43559C11.5635 9.53561 11.6992 9.59182 11.8406 9.59187H13.974C14.1154 9.59178 14.251 9.53555 14.351 9.43553C14.451 9.33552 14.5072 9.1999 14.5072 9.05848V6.92526ZM5.97401 6.92526C5.97397 6.78381 5.91776 6.64816 5.81774 6.54814C5.71772 6.44812 5.58208 6.39191 5.44063 6.39187H3.30724C3.16581 6.39196 3.03021 6.44819 2.93023 6.5482C2.83025 6.64822 2.77406 6.78384 2.77401 6.92526V9.05848C2.77406 9.1999 2.83025 9.33552 2.93023 9.43553C3.03021 9.53555 3.16581 9.59178 3.30724 9.59187H5.44063C5.58208 9.59182 5.71772 9.53561 5.81774 9.43559C5.91776 9.33557 5.97397 9.19993 5.97401 9.05848V6.92526Z" fill="#424242"/>
							</svg>
							<span class="sv-blog-meta-title date"><?php echo esc_html($published_date); ?></span>
						</div>
					</div>
					<p class="sv-blog-text"><?php echo wp_trim_words($post_content, 20, '...'); ?></p>
					<a href="<?php the_permalink(); ?>" class="sv-blog-link">Read More
						<svg width="21" height="18" viewBox="0 0 21 18" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M20.0183 7.7734L12.5955 0.350527C12.3955 0.157364 12.1276 0.0504805 11.8496 0.0528965C11.5715 0.0553126 11.3056 0.166835 11.1089 0.363444C10.9123 0.560052 10.8008 0.826016 10.7984 1.10405C10.796 1.38209 10.9029 1.64995 11.096 1.84995L16.7088 7.4627H1.06041C0.779172 7.4627 0.509452 7.57442 0.310587 7.77328C0.111721 7.97215 0 8.24187 0 8.52311C0 8.80435 0.111721 9.07406 0.310587 9.27293C0.509452 9.4718 0.779172 9.58352 1.06041 9.58352H16.7088L11.096 15.1963C10.9948 15.2941 10.914 15.4111 10.8584 15.5405C10.8028 15.6698 10.7736 15.809 10.7723 15.9498C10.7711 16.0906 10.7979 16.2302 10.8513 16.3605C10.9046 16.4909 10.9833 16.6093 11.0829 16.7088C11.1825 16.8084 11.3009 16.8871 11.4312 16.9405C11.5615 16.9938 11.7011 17.0206 11.8419 17.0194C11.9827 17.0182 12.1219 16.9889 12.2512 16.9333C12.3806 16.8778 12.4976 16.797 12.5955 16.6957L20.0183 9.27282C20.2171 9.07396 20.3288 8.80429 20.3288 8.52311C20.3288 8.24192 20.2171 7.97225 20.0183 7.7734Z" fill="white"></path>
						</svg>
					</a>
				</div>
				<div class="blog-view-count">
					<div class="blog-view-count-box">
						<svg width="25" height="14" viewBox="0 0 25 14" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M12.5009 0C8.01365 0 3.94433 2.45504 0.941581 6.44269C0.696556 6.76939 0.696556 7.22581 0.941581 7.55251C3.94433 11.545 8.01365 14 12.5009 14C16.9883 14 21.0576 11.545 24.0603 7.55731C24.3053 7.23061 24.3053 6.77419 24.0603 6.4475C21.0576 2.45505 16.9883 0 12.5009 0ZM12.8228 11.9293C9.84412 12.1167 7.38427 9.66163 7.57164 6.67811C7.72538 4.21826 9.71921 2.22443 12.1791 2.07069C15.1578 1.88332 17.6176 4.33837 17.4303 7.32189C17.2717 9.77694 15.2779 11.7708 12.8228 11.9293ZM12.6739 9.65202C11.0692 9.75292 9.74323 8.43171 9.84893 6.82704C9.9306 5.50103 11.0068 4.42965 12.3328 4.34317C13.9375 4.24228 15.2635 5.56349 15.1578 7.16815C15.0713 8.49897 13.9951 9.57035 12.6739 9.65202Z" fill="white"/>
						</svg>
						<?php echo esc_html($view_count); ?>
					</div>
					<div class="blog-view-count-box">
						<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path fill-rule="evenodd" clip-rule="evenodd" d="M12.6 0.466675H1.4C0.6412 0.466675 0 1.10787 0 1.86667V9.56668C0 10.3255 0.6412 10.9667 1.4 10.9667H3.26667V13.3C3.26665 13.3471 3.28088 13.3931 3.30749 13.432C3.3341 13.4708 3.37185 13.5007 3.41577 13.5177C3.45972 13.5346 3.50775 13.5379 3.55359 13.5271C3.59942 13.5162 3.64092 13.4918 3.67267 13.457L5.93647 10.9667H12.6C13.3588 10.9667 14 10.3255 14 9.56668V1.86667C14 1.10787 13.3588 0.466675 12.6 0.466675ZM2.53906 3.51804C2.26292 3.51804 2.03906 3.74189 2.03906 4.01804C2.03906 4.29418 2.26292 4.51804 2.53906 4.51804H11.3789C11.655 4.51804 11.8789 4.29418 11.8789 4.01804C11.8789 3.74189 11.655 3.51804 11.3789 3.51804H2.53906ZM2.53906 6.48938C2.26292 6.48938 2.03906 6.71324 2.03906 6.98938C2.03906 7.26552 2.26292 7.48938 2.53906 7.48938H8.08203C8.35817 7.48938 8.58203 7.26552 8.58203 6.98938C8.58203 6.71324 8.35817 6.48938 8.08203 6.48938H2.53906Z" fill="white"/>
						</svg>
						<?php echo esc_html($comment_count); ?>
					</div>
				</div>
			</div>
			<?php
		}
		wp_reset_postdata();
		$pagination_html = paginate_links(
			array(
				'total' => $query->max_num_pages,
				'current' => $paged,
				'format' => '?paged=%#%',
				'show_all' => false,
				'type' => 'array',
				'prev_next' => true,
				'prev_text' => __('« Previous'),
				'next_text' => __('Next »', ''),
			)
		);
		$pagination_html = !empty($pagination_html) ? '<div class="pagination">' . implode('', $pagination_html) . '</div>' : '';
		$blog_html = ob_get_clean();
		wp_send_json_success(array(
			'message' => 'Filtered Blogs',
			'blog_html' => $blog_html,
			'pagination_html' => $pagination_html,
		));
	} else {
		wp_send_json_success(array(
			'message' => 'No posts found for this category and search term.',
			'blog_html' => '<p>No posts found for this category and search term.</p>',
			'pagination_html' => '',
		));
	}
	wp_die();
}
add_action('wp_ajax_filter_blogs', 'sv_filter_blogs');
add_action('wp_ajax_nopriv_filter_blogs', 'sv_filter_blogs');



function disable_reviews_for_non_logged_in_users() {
	if (!is_user_logged_in()) {
		// Remove the review form
		// add_filter('comments_open', 'disable_comments', 10, 2);
		add_filter('woocommerce_product_tabs', 'modify_reviews_tab', 98);
	}
}
add_action('template_redirect', 'disable_reviews_for_non_logged_in_users');

function disable_comments($open, $post_id) {
	if ('product' === get_post_type($post_id)) {
		$open = false;
	}

	return $open;
}

function modify_reviews_tab( $tabs ) {
	// 
	if ( isset($tabs['reviews']) && ! is_user_logged_in() ) {
		$tabs['reviews']['callback'] = 'custom_reviews_tab_content';
	}

	return $tabs;
}

function custom_reviews_tab_content() {
	comments_template();

	if ( ! is_user_logged_in() ) {
		echo wp_kses_post( '<p class="woocommerce-info">You must be <a href="/my-account">logged in</a> to post a review.</p>' );
	}
}


/**
 * If the function, `sv_duplicate_comment_id_callback` doesn't exist.
 */
if ( ! function_exists( 'sv_duplicate_comment_id_callback' ) ) {
	/**
	 * Nullify the duplicate comment ID so duplicate comments are allowed on all post types.
	 *
	 * @since 1.0.0
	 */
	function sv_duplicate_comment_id_callback() {

		return null;
	}
}
add_filter( 'duplicate_comment_id', 'sv_duplicate_comment_id_callback', 99 );


// add_filter('woocommerce_available_payment_gateways', 'sv_disable_credit_card_woocommerce_payments');
function sv_disable_credit_card_woocommerce_payments($available_gateways) {
	// Check if WooCommerce Payments is available
	if (isset($available_gateways['woocommerce_payments'])) {
		// Remove WooCommerce Payments gateway
		unset($available_gateways['woocommerce_payments']);
	}

	return $available_gateways;
}


add_filter( 'woocommerce_payment_gateway_supports', 'filter_payment_gateway_supports', 10, 3 );
function filter_payment_gateway_supports( $supports, $feature, $payment_gateway ) {
	// debug($payment_gateway);
	// Here in the array, set the allowed payment method IDs (slugs)
	$allowed_payment_method_ids = array('moneris', 'cod');

	if ( in_array($payment_gateway->id, $allowed_payment_method_ids ) && $feature === 'add_payment_method' ) {
		$supports = true;
	}
	return $supports;
}


function display_google_map() {
	ob_start(); ?>
	<div id="mapCanvas" style="width: 100%; height: 500px;"></div>
	<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDRfDT-5iAbIjrIqVORmmeXwAjDgLJudiM&callback=initMap" defer></script>
	<script>
		function initMap() {
			var map;
			var bounds = new google.maps.LatLngBounds();
			var mapOptions = {
				mapTypeId: 'roadmap'
			};

			map = new google.maps.Map(document.getElementById("mapCanvas"), mapOptions);
			map.setTilt(50);

			var markers = [
				['Supa Vapes Hawkesbury', 45.60773945746124, -74.58492574601854],
				['Supa Vapes 729 Walkley Rd', 45.362812274369, -75.68263443001749]
			];

			var infoWindowContent = [
				['<div class="info_content">' +
				'<h2>Supa Vapes Hawkesbury</h2>' +
				'<h3>1502 Main St E, Hawkesbury, ON K6A 1C7, Canada</h3>' +
				'</div>'],
				['<div class="info_content">' +
				'<h2>Supa Vapes 729 Walkley Rd</h2>' +
				'<h3>729 Walkley Rd, Ottawa, ON K1V 6R6, Canada</h3>' +
				'</div>']
			];

			var infoWindow = new google.maps.InfoWindow(), marker, i;

			for (i = 0; i < markers.length; i++) {
				var position = new google.maps.LatLng(markers[i][1], markers[i][2]);
				bounds.extend(position);
				marker = new google.maps.Marker({
					position: position,
					map: map,
					title: markers[i][0]
				});

				google.maps.event.addListener(marker, 'click', (function(marker, i) {
					return function() {
						infoWindow.setContent(infoWindowContent[i][0]);
						infoWindow.open(map, marker);
					}
				})(marker, i));
			}

			map.fitBounds(bounds);

			var boundsListener = google.maps.event.addListener((map), 'bounds_changed', function(event) {
				// Adjust the zoom based on the screen width
				var zoomLevel = 10;
				if (window.innerWidth < 768) { // For devices with width < 768px (like phones)
					zoomLevel = 8;
				} else if (window.innerWidth < 1024) { // For devices with width < 1024px (like tablets)
					zoomLevel = 9;
				}
				this.setZoom(zoomLevel);
				google.maps.event.removeListener(boundsListener);
			});
		}

		window.initMap = initMap;
	</script>
	<?php
	return ob_get_clean();
}
// Register the shortcode
add_shortcode('google_map_shortcode', 'display_google_map');

/**
 * If the function, `supavapes_woocommerce_variation_options_pricing_callback` doesn't exist.
 */
if ( ! function_exists( 'supavapes_woocommerce_variation_options_pricing_callback' ) ) {
	/**
	 * Add custom pricing for ontario and federal locations.
	 *
	 * @since 1.1.0
	 *
	 * @param int     $loop           Position in the loop.
	 * @param array   $variation_data Variation data.
	 * @param WP_Post $variation      Post data.
	 */
	function supavapes_woocommerce_variation_options_pricing_callback( $loop, $variation_data, $variation ) {
		// Add a field for the Ontario prices.
		// woocommerce_wp_text_input(
		// 	array(
		// 		'id'            => "_ontario_price_{$loop}",
		// 		'name'          => "_ontario_price[{$loop}]",
		// 		'value'         => wc_format_localized_price( get_post_meta( $variation->ID, '_ontario_price', true ) ),
		// 		'label'         => sprintf(
		// 			/* translators: %s: currency symbol */
		// 			__( 'Ontario price (%s)', 'woocommerce' ),
		// 			get_woocommerce_currency_symbol()
		// 		),
		// 		'data_type'     => 'price',
		// 		'wrapper_class' => 'form-row form-row-first',
		// 		'placeholder'   => __( 'Ontario price (required)', 'supavapes' ),
		// 	)
		// );

		// Add a field for the Federal prices.
		// woocommerce_wp_text_input(
		// 	array(
		// 		'id'            => "_federal_price_{$loop}",
		// 		'name'          => "_federal_price[{$loop}]",
		// 		'value'         => wc_format_localized_price( get_post_meta( $variation->ID, '_federal_price', true ) ),
		// 		'label'         => sprintf(
		// 			/* translators: %s: currency symbol */
		// 			__( 'Federal price (%s)', 'woocommerce' ),
		// 			get_woocommerce_currency_symbol()
		// 		),
		// 		'data_type'     => 'price',
		// 		'wrapper_class' => 'form-row form-row-last',
		// 		'placeholder'   => __( 'Federal price (required)', 'supavapes' ),
		// 	)
		// );

		// Add a field for the Federal prices.
		woocommerce_wp_text_input(
			array(
				'id'            => "_vaping_liquid_{$loop}",
				'name'          => "_vaping_liquid[{$loop}]",
				'value'         => wc_format_localized_price( get_post_meta( $variation->ID, '_vaping_liquid', true ) ),
				'label'         => sprintf(
					/* translators: %s: currency symbol */
					__( 'Vaping Liquid (ml)', 'woocommerce' ),
					get_woocommerce_currency_symbol()
				),
				'data_type'     => 'text',
				'wrapper_class' => 'form-row form-row-last',
				'placeholder'   => __( 'Vaping Liquid (required)', 'supavapes' ),
			)
		);
	}
}

add_action( 'woocommerce_variation_options_pricing', 'supavapes_woocommerce_variation_options_pricing_callback', 10, 3 );

/**
 * If the function `supavapes_woocommerce_save_product_variation_callback` doesn't exist.
 */
if ( ! function_exists( 'supavapes_woocommerce_save_product_variation_callback' ) ) {
	/**
	 * Save the free product/variation.
	 *
	 * @param int $variation_id Holds the variation ID.
	 * @param int $loop Holds the loop index for variations listing.
	 *
	 * @since 1.0.0
	 */
	function supavapes_woocommerce_save_product_variation_callback( $variation_id, $loop ) {
		supavapes_update_product_meta( $variation_id, $loop );
	}
}

add_action( 'woocommerce_save_product_variation', 'supavapes_woocommerce_save_product_variation_callback', 10, 2 );

/**
 * If the function `supavapes_update_product_meta` doesn't exist.
 */
if ( ! function_exists( 'supavapes_update_product_meta' ) ) {
	/**
	 * Save the free product/variation.
	 *
	 * @param int $product_id Holds the variation ID.
	 * @param int $loop Holds the loop index for variations listing.
	 *
	 * @since 1.0.0
	 */
	function supavapes_update_product_meta( $product_id, $loop = '' ) {
		$posted_array = filter_input_array( INPUT_POST );

		if ( isset( $loop ) && is_int( $loop ) ) {
			$ontario_price = ( isset( $posted_array['_ontario_price'][ $loop ] ) ) ? wp_unslash( $posted_array['_ontario_price'][ $loop ] ) : '';
			$federal_price = ( isset( $posted_array['_federal_price'][ $loop ] ) ) ? wp_unslash( $posted_array['_federal_price'][ $loop ] ) : '';
			$vaping_liquid = ( isset( $posted_array['_vaping_liquid'][ $loop ] ) ) ? wp_unslash( $posted_array['_vaping_liquid'][ $loop ] ) : '';

		} else {
			$ontario_price = ( isset( $posted_array['_ontario_price'] ) ) ? $posted_array['_ontario_price'] : '';
			$federal_price = ( isset( $posted_array['_federal_price'] ) ) ? $posted_array['_federal_price'] : '';
			$vaping_liquid = ( isset( $posted_array['_vaping_liquid'] ) ) ? $posted_array['_vaping_liquid'] : '';
		}

		// Update or delete the Ontario price.
		if ( $ontario_price !== '' ) {
			update_post_meta( $product_id, '_ontario_price', $ontario_price );
		} else {
			delete_post_meta( $product_id, '_ontario_price' );
		}

		// Update or delete the federal price.
		if ( $federal_price !== '' ) {
			update_post_meta( $product_id, '_federal_price', $federal_price );
		} else {
			delete_post_meta( $product_id, '_federal_price' );
		}

		// Update or delete the vaping liquid.
		if ( $vaping_liquid !== '' ) {
			update_post_meta( $product_id, '_vaping_liquid', $vaping_liquid );
		} else {
			delete_post_meta( $product_id, '_vaping_liquid' );
		}
	}
}


/**
 * If the function `supavapes_woocommerce_process_product_meta_callback` doesn't exist.
 */
if ( ! function_exists( 'supavapes_woocommerce_process_product_meta_callback' ) ) {
	/**
	 * Save the free product.
	 *
	 * @param int $product_id Holds the variation ID.
	 *
	 * @since 1.0.0
	 */
	function supavapes_woocommerce_process_product_meta_callback( $product_id ) {
		supavapes_update_product_meta( $product_id );
	}
}

add_action( 'woocommerce_process_product_meta', 'supavapes_woocommerce_process_product_meta_callback' );

/**
 * If the function `supavapes_woocommerce_product_options_pricing_callback` doesn't exist.
 */
if ( ! function_exists( 'supavapes_woocommerce_product_options_pricing_callback' ) ) {
	/**
	 * Add Ontario and Federal prices on the simple products screen.
	 *
	 * @since 1.0.0
	 */
	function supavapes_woocommerce_product_options_pricing_callback() {
		global $post;

		// Add a field for the Ontario prices.
		// woocommerce_wp_text_input(
		// 	array(
		// 		'id'          => '_ontario_price',
		// 		'name'        => '_ontario_price',
		// 		'value'       => wc_format_localized_price( get_post_meta( $post->ID, '_ontario_price', true ) ),
		// 		'label'       => sprintf(
		// 			/* translators: %s: currency symbol */
		// 			__( 'Ontario price (%s)', 'woocommerce' ),
		// 			get_woocommerce_currency_symbol()
		// 		),
		// 		'data_type'   => 'price',
		// 		'placeholder' => __( 'Ontario price (required)', 'supavapes' ),
		// 	)
		// );

		// Add a field for the Federal prices.
		// woocommerce_wp_text_input(
		// 	array(
		// 		'id'          => '_federal_price',
		// 		'name'        => '_federal_price',
		// 		'value'       => wc_format_localized_price( get_post_meta( $post->ID, '_federal_price', true ) ),
		// 		'label'       => sprintf(
		// 			/* translators: %s: currency symbol */
		// 			__( 'Federal price (%s)', 'woocommerce' ),
		// 			get_woocommerce_currency_symbol()
		// 		),
		// 		'data_type'   => 'price',
		// 		'placeholder' => __( 'Federal price (required)', 'supavapes' ),
		// 	)
		// );


		// Add a field for the Federal prices.
		woocommerce_wp_text_input(
			array(
				'id'          => '_vaping_liquid',
				'name'        => '_vaping_liquid',
				'value'       => get_post_meta( $post->ID, '_vaping_liquid', true ),
				'label'       => sprintf(
					/* translators: %s: currency symbol */
					__( 'Vaping Liquid (ml)', 'woocommerce' ),
					get_woocommerce_currency_symbol()
				),
				'data_type'   => 'text',
				'placeholder' => __( 'Vaping Liquid (required)', 'supavapes' ),
			)
		);
	}
}

add_action( 'woocommerce_product_options_pricing', 'supavapes_woocommerce_product_options_pricing_callback' );


/**
 * If the function `supavapes_woocommerce_set_dynamic_price_callback` doesn't exist.
 */
if ( ! function_exists( 'supavapes_woocommerce_set_dynamic_price_callback' ) ) {
	/**
	 * Ajax callback function to set dynamic price based on location.
	 *
	 * @since 1.0.0
	 */
	function supavapes_woocommerce_set_dynamic_price_callback() {
		$city = isset( $_POST['city'] ) ? sanitize_text_field( $_POST['city'] ) : '';
		$country = isset( $_POST['country'] ) ? sanitize_text_field( $_POST['country'] ) : '';
		$state = isset( $_POST['state'] ) ? sanitize_text_field( $_POST['state'] ) : '';
		
		if ( ! empty( $state ) ) {
			// Set the state value in a cookie that expires in 7 days
			// setcookie( 'user_state', $state, time() + (86400 * 30), "/" );
			setcookie( 'user_state', $state, time() + (86400 * 7), COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
			wp_send_json_success( array( 'message' => 'State value has been set in the cookie.' ) );
		} else {
			wp_send_json_error( array( 'message' => 'State value is not provided.' ) );
		}

		wp_die();
	}
}

add_action( 'wp_ajax_woocommerce_set_dynamic_price', 'supavapes_woocommerce_set_dynamic_price_callback' );
add_action( 'wp_ajax_nopriv_woocommerce_set_dynamic_price', 'supavapes_woocommerce_set_dynamic_price_callback' );



/**
 * If the function `supavapes_custom_price_html` doesn't exist.
 */
if ( ! function_exists( 'supavapes_custom_price_html' ) ) {
	/**
	 * Set custom price with the calculated tax.
	 *
	 * @param $price contains the product price html
	 * @param $product contains the product data.
	 * 
	 * @since 1.0.0
	 */
	function supavapes_custom_price_html( $price, $product ) {

		// Get user state dynamically
		$state = isset( $_COOKIE['user_state'] ) ? sanitize_text_field( $_COOKIE['user_state'] ) : '';

		// Initialize the price breakdown string
		$price_breakdown = '';

		if ( $product->is_type( 'variable' ) ) {
			// For variable products
			$available_variations = $product->get_available_variations();
			$min_vaping_liquid = PHP_INT_MAX;
			$max_vaping_liquid = PHP_INT_MIN;
	
			// Loop through each variation to find the minimum and maximum _vaping_liquid value
			foreach ( $available_variations as $variation ) {
				$vaping_liquid_value = (int) get_post_meta( $variation['variation_id'], '_vaping_liquid', true );
	
				// Skip any vaping liquid values that are zero
				if ( $vaping_liquid_value > 0 ) {
					// Set minimum and maximum values
					if ( $vaping_liquid_value < $min_vaping_liquid ) {
						$min_vaping_liquid = $vaping_liquid_value;
					}
					if ( $vaping_liquid_value > $max_vaping_liquid ) {
						$max_vaping_liquid = $vaping_liquid_value;
					}
				}
			}
	
			// Fallback if there are no variations
			if ( $min_vaping_liquid == PHP_INT_MAX ) {
				$min_vaping_liquid = 0;
			}

			if ( $max_vaping_liquid == PHP_INT_MIN ) {
				$max_vaping_liquid = 0;
			}

			// Calculate taxes for the minimum and maximum vaping liquid values
			$min_ontario_tax = supavapes_calculate_ontario_tax( $min_vaping_liquid );
			$max_ontario_tax = supavapes_calculate_ontario_tax( $max_vaping_liquid );
			$min_federal_tax = supavapes_calculate_federal_tax( $min_vaping_liquid );
			$max_federal_tax = supavapes_calculate_federal_tax( $max_vaping_liquid );
	
			// Get the price range (minimum and maximum prices of the variations)
			$min_price = $product->get_variation_price( 'min' );
			$max_price = $product->get_variation_price( 'max' );
	
			// Determine the state
			$state = isset( $_COOKIE['user_state'] ) ? sanitize_text_field( $_COOKIE['user_state'] ) : '';
	
			// Calculate final prices with tax for minimum and maximum values
			if ( 'Ontario' !== $state ) {
				$final_min_price = floatval( $min_price ) + floatval( $min_federal_tax );
				$final_max_price = floatval( $max_price ) + floatval( $max_federal_tax );
			} else {
				$final_min_price = floatval( $min_price ) + floatval( $min_ontario_tax ) + floatval( $min_federal_tax );
				$final_max_price = floatval( $max_price ) + floatval( $max_ontario_tax ) + floatval( $max_federal_tax );
			} 

			$price = ( $min_price === $max_price ) ? wc_price( $final_min_price ) : ( wc_price( $final_min_price ) . ' - ' . wc_price( $final_max_price ) );
		} else {
			// For simple products
			$reg_price     = (float) $product->get_regular_price();
			$sale_price    = (float) $product->get_sale_price();
			$vaping_liquid = get_post_meta( $product->get_id(), '_vaping_liquid', true );

			// Initialize tax variables
			$ontario_tax = 0;
			$federal_tax = 0;

			// Fetch dynamic duty rates from ACF fields
			$ontario_duty_per_2ml  = get_field( 'ontario_excise_value_2_ml', 'option' );
			$ontario_duty_per_10ml = get_field( 'ontario_excise_value_10_ml', 'option' );
			$federal_duty_per_2ml  = get_field( 'federal_excise_value_2_ml', 'option' );
			$federal_duty_per_10ml = get_field( 'federal_excise_value_10_ml', 'option' );

			// Calculate taxes if vaping_liquid is greater than or equal to 10
			if ( isset( $vaping_liquid ) && ! empty( $vaping_liquid ) && $vaping_liquid >= 10 ) {
				$ontario_tax = supavapes_calculate_ontario_tax( $vaping_liquid );
				$federal_tax = supavapes_calculate_federal_tax( $vaping_liquid );
			}

			// Determine the final price based on state
			if ( 'Ontario' !== $state ) {
				$final_price  = isset( $sale_price ) && ! empty( $sale_price ) ? $sale_price : $reg_price;
				$final_price += $federal_tax;
			} else {
				$final_price  = isset( $sale_price ) && ! empty( $sale_price ) ? $sale_price : $reg_price;
				$final_price += $ontario_tax + $federal_tax;
			}

			// Update the price display
			$price = wc_price( $final_price );
		}
		
		return $price;
	}
}

add_filter( 'woocommerce_get_price_html', 'supavapes_custom_price_html', 10, 2 );




/**
 * If the function `supavapes_set_custom_price_in_cart` doesn't exist.
 */
if ( ! function_exists( 'supavapes_set_custom_price_in_cart' ) ) {
	function supavapes_set_custom_price_in_cart( $cart ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		$state = isset( $_COOKIE['user_state'] ) ? sanitize_text_field( $_COOKIE['user_state'] ) : '';

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			$product_id = $cart_item['product_id'];
			$tax = 0;

			$ontario_duty_per_2ml 	= get_field('ontario_excise_value_2_ml', 'option');
			$ontario_duty_per_10ml 	= get_field('ontario_excise_value_10_ml', 'option');
			$federal_duty_per_2ml 	= get_field('federal_excise_value_2_ml', 'option');
			$federal_duty_per_10ml 	= get_field('federal_excise_value_10_ml', 'option');

			$ontario_tax = 0;
			$federal_tax = 0;

			if ( $cart_item['data']->is_type( 'variation' ) ) {
				$variation_id = $cart_item['variation_id'];
				$reg_price  = get_post_meta( $variation_id, '_regular_price', true );
				$sale_price = get_post_meta( $variation_id, '_sale_price', true );
				$vaping_liquid = get_post_meta( $variation_id, '_vaping_liquid', true );

				if ( isset( $vaping_liquid ) && !empty( $vaping_liquid ) && $vaping_liquid >= 10 ) {
					$first_part = 10;
					$second_part = $vaping_liquid - $first_part;

					$ontario_tax += ( 10 / 2 ) * $ontario_duty_per_2ml;
					if ($second_part > 0) {
						$ontario_tax += floor( $second_part / 10 ) * $ontario_duty_per_10ml;
					}

					$federal_tax += ( 10 / 2 ) * $federal_duty_per_2ml;
					if ($second_part > 0) {
						$federal_tax += floor( $second_part / 10 ) * $federal_duty_per_10ml;
					}
				}

				if ( 'Ontario' !== $state ) {
					$final_price = isset($sale_price) && !empty($sale_price) ? $sale_price : $reg_price;
					$final_price += $federal_tax;
				} else {
					$final_price = isset($sale_price) && !empty($sale_price) ? $sale_price : $reg_price;
					$final_price += $ontario_tax + $federal_tax;
				}

				$cart_item['data']->set_price( $final_price );

			} else {
				$reg_price  = $cart_item['data']->get_regular_price();
				$sale_price = $cart_item['data']->get_sale_price();
				$vaping_liquid = get_post_meta( $product_id, '_vaping_liquid', true );

				if ( isset( $vaping_liquid ) && !empty( $vaping_liquid ) && $vaping_liquid >= 10 ) {
					$first_part = 10;
					$second_part = $vaping_liquid - $first_part;

					$ontario_tax += ( 10 / 2 ) * $ontario_duty_per_2ml;
					if ($second_part > 0) {
						$ontario_tax += floor( $second_part / 10 ) * $ontario_duty_per_10ml;
					}

					$federal_tax += ( 10 / 2 ) * $federal_duty_per_2ml;
					if ($second_part > 0) {
						$federal_tax += floor( $second_part / 10 ) * $federal_duty_per_10ml;
					}
				}

				if ( 'Ontario' !== $state ) {
					$final_price = isset($sale_price) && !empty($sale_price) ? $sale_price : $reg_price;
					$final_price += $federal_tax;
				} else {
					$final_price = isset($sale_price) && !empty($sale_price) ? $sale_price : $reg_price;
					$final_price += $ontario_tax + $federal_tax;
				}

				$cart_item['data']->set_price( $final_price );
			}
		}
	}
}

// add_action( 'woocommerce_before_calculate_totals', 'supavapes_set_custom_price_in_cart', 10, 1 );


/**
 * If the function `supavapes_cart_item_custom_price` doesn't exist.
 */
if ( ! function_exists( 'supavapes_cart_item_custom_price' ) ) {
	/**
	 * Override cart item price display for variable products.
	 * 
	 * @param float $price Holds price of cart item.
	 * @param array $cart_item Holds cart item array.
	 * @param string $cart_item_key Holds cart item key.
	 * 
	 * @since 1.0.0
	 */
	function supavapes_cart_item_custom_price( $price, $cart_item, $cart_item_key ) {
		$state = isset( $_COOKIE['user_state'] ) ? sanitize_text_field( $_COOKIE['user_state'] ) : '';

		// Fetch dynamic duty rates from ACF fields
		$ontario_duty_per_2ml = get_field( 'ontario_excise_value_2_ml', 'option' );
		$ontario_duty_per_10ml = get_field( 'ontario_excise_value_10_ml', 'option' );
		$federal_duty_per_2ml = get_field( 'federal_excise_value_2_ml', 'option' );
		$federal_duty_per_10ml = get_field( 'federal_excise_value_10_ml', 'option' );

		$ontario_tax = 0;
		$federal_tax = 0;

		if ( $cart_item['data']->is_type( 'variation' ) ) {
			$variation_id = $cart_item['variation_id'];

			// Fetch regular and sale prices
			$reg_price  = get_post_meta( $variation_id, '_regular_price', true );
			$sale_price = get_post_meta( $variation_id, '_sale_price', true );

			// Fetch vaping_liquid value
			$vaping_liquid = get_post_meta( $variation_id, '_vaping_liquid', true );

			// Calculate taxes if vaping_liquid is greater than or equal to 10
			if ( isset( $vaping_liquid ) && !empty( $vaping_liquid ) && $vaping_liquid >= 10 ) {
				$first_part = 10;
				$second_part = $vaping_liquid - $first_part;

				// Ontario tax calculation
				$ontario_tax += (10 / 2) * $ontario_duty_per_2ml;
				if ( $second_part > 0 ) {
					$ontario_tax += floor( $second_part / 10 ) * $ontario_duty_per_10ml;
				}

				// Federal tax calculation
				$federal_tax += (10 / 2) * $federal_duty_per_2ml;
				if ( $second_part > 0 ) {
					$federal_tax += floor( $second_part / 10 ) * $federal_duty_per_10ml;
				}
			}

			// Determine the final price based on state
			if ( 'Ontario' !== $state ) {
				$final_price = isset( $sale_price ) && !empty( $sale_price ) ? $sale_price + $ontario_tax : $reg_price + $ontario_tax;
			} else {
				$final_price = isset( $sale_price ) && !empty( $sale_price ) ? $sale_price + $ontario_tax + $federal_tax : $reg_price + $ontario_tax + $federal_tax;
			}

			if ( isset( $vaping_liquid ) && ! empty( $vaping_liquid ) && $vaping_liquid >= 10 ) {
				// Set the price breakdown for the variation
				$price_breakdown .= sprintf(
					__( 'Variation ID: %d<br>Regular Price: %s<br>Ontario Tax: %s<br>Federal Tax: %s<br>Final Price: %s', 'woocommerce' ),
					$variation_id,
					wc_price( $reg_price ),
					wc_price( $ontario_tax ),
					wc_price( $federal_tax ),
					wc_price( $final_price )
				);

				// // Create an info icon with a tooltip
				// $info_icon_html = '<span class="supavapes-price-info">ℹ️
				// 	<div class="supavapes-tooltip">' . $price_breakdown . '</div>
				// </span>';
			}

			$price = wc_price( $final_price );

		} else {
			// For simple products
			$reg_price  = $cart_item['data']->get_regular_price();
			$sale_price = $cart_item['data']->get_sale_price();

			// Fetch vaping_liquid value for simple product
			$vaping_liquid = get_post_meta( $cart_item['product_id'], '_vaping_liquid', true );

			// Calculate taxes if vaping_liquid is greater than or equal to 10
			if ( isset( $vaping_liquid ) && !empty( $vaping_liquid ) && $vaping_liquid >= 10 ) {
				$first_part = 10;
				$second_part = $vaping_liquid - $first_part;

				// Ontario tax calculation
				$ontario_tax += (10 / 2) * $ontario_duty_per_2ml;
				if ( $second_part > 0 ) {
					$ontario_tax += floor( $second_part / 10 ) * $ontario_duty_per_10ml;
				}

				// Federal tax calculation
				$federal_tax += (10 / 2) * $federal_duty_per_2ml;
				if ( $second_part > 0 ) {
					$federal_tax += floor( $second_part / 10 ) * $federal_duty_per_10ml;
				}
			}

			// Determine the final price based on state
			if ( 'Ontario' !== $state ) {
				$final_price = isset( $sale_price ) && !empty( $sale_price ) ? $sale_price + $federal_tax : $reg_price + $federal_tax;
			} else {
				$final_price = isset( $sale_price ) && !empty( $sale_price ) ? $sale_price + $ontario_tax + $federal_tax : $reg_price + $ontario_tax + $federal_tax;
			}

			if ( isset( $vaping_liquid ) && ! empty( $vaping_liquid ) && $vaping_liquid >= 10 ) {
				// Prepare the price breakdown details
				$price_breakdown = sprintf(
					__( 'Regular Price: %s<br>Ontario Tax: %s<br>Federal Tax: %s', 'woocommerce' ),
					wc_price( $reg_price ),
					wc_price( $ontario_tax ),
					wc_price( $federal_tax )
				);
		
				// // Create an info icon with a tooltip
				// $info_icon_html = '<span class="supavapes-price-info">ℹ️
				// 	<div class="supavapes-tooltip">' . $price_breakdown . '</div>
				// </span>';
			}
			$price = wc_price( $final_price ) . $info_icon_html;
		}

		return $price;
	}
}

// add_filter( 'woocommerce_cart_item_price', 'supavapes_cart_item_custom_price', 10, 3 );



/**
 * If the function, `supavapes_order_notes_metabox_callback` doesn't exist.
 */
if ( ! function_exists( 'supavapes_order_notes_metabox_callback' ) ) {
	/**
	 * Add content for the order notes.
	 *
	 * @param object $post WordPress post object.
	 *
	 * @since 1.0.0
	 */
	function supavapes_order_notes_metabox_callback( $post ) {

		if ( $post instanceof WC_Order ) {
			$order_id = $post->get_id();
		} else {
			$order_id = $post->ID;
		}

		$args = array( 'order_id' => $order_id );

		if ( 0 !== $order_id ) {
			$notes = wc_get_order_notes( $args );
		} else {
			$notes = array();
		}

		include trailingslashit( get_stylesheet_directory() ) . 'woocommerce/admin/html-order-notes.php';
		?>
		<div class="add_note">
			<p>
				<label for="add_order_note"><?php esc_html_e( 'Add note', 'woocommerce' ); ?> <?php echo wc_help_tip( __( 'Add a note for your reference, or add a customer note (the user will be notified).', 'woocommerce' ) ); ?></label>
				<textarea type="text" name="order_note" id="add_order_note" class="input-text" cols="20" rows="5"></textarea>
				<div class="order-notes-attachments-container">
					<div class="gallery-images"></div>
				</div>
				<div class="add-order-notes-attachments">
					<a href="javascript:void(0);"><?php esc_html_e( 'Add attachments', 'supavapes' ); ?></a>
				</div>
			</p>
			<p>
				<label for="order_note_type" class="screen-reader-text"><?php esc_html_e( 'Note type', 'woocommerce' ); ?></label>
				<select name="order_note_type" id="order_note_type">
					<option value=""><?php esc_html_e( 'Private note', 'woocommerce' ); ?></option>
					<option value="customer"><?php esc_html_e( 'Note to customer', 'woocommerce' ); ?></option>
				</select>
				<button type="button" class="add_note button"><?php esc_html_e( 'Add', 'woocommerce' ); ?></button>
			</p>
		</div>
		<?php
	}
}

/**
 * If the function, `supavapes_add_note_attachments_ajax_callback`, doesn't exist.
 */
if ( ! function_exists( 'supavapes_add_note_attachments_ajax_callback' ) ) {
	/**
	 * Add media attachments to last added note.
	 *
	 * @since 1.0.0
	 */
	function supavapes_add_note_attachments_ajax_callback() {
		global $wpdb;

		$order_id         = (int) filter_input( INPUT_POST, 'order_id', FILTER_SANITIZE_NUMBER_INT );
		$posted_array     = filter_input_array( INPUT_POST );
		$note_attachments = $posted_array['note_attachments'];

		// Get the last added note ID.
		$comment_query = "SELECT `comment_ID` FROM `$wpdb->comments` WHERE  `comment_post_ID` = $order_id AND  `comment_type` LIKE  'order_note' ORDER BY `comment_date` DESC LIMIT 1";
		$query_results = $wpdb->get_row( $comment_query, ARRAY_A );
		$comment_id    = ( ! empty( $query_results['comment_ID'] ) ) ? $query_results['comment_ID'] : false;

		// Return, if the db query did not return the previous comment ID.
		if ( false === $comment_id ) {
			wp_send_json_error(
				array(
					'code'    => 'media-not-attached',
					'message' => __( 'Unable to add media to the note. Order note not found.', 'supavapes' ),
				)
			);
			wp_die();
		}

		// Update the comment meta.
		update_comment_meta( $comment_id, 'attachments', $note_attachments );

		// Send the ajax success response.
		wp_send_json_error(
			array(
				'code' => 'media-attached',
			)
		);
		wp_die();
	}
}

add_action( 'wp_ajax_add_note_attachments', 'supavapes_add_note_attachments_ajax_callback' );

/**
 * If the function, `supavapes_refresh_order_notes_callback`, doesn't exist.
 */
if ( ! function_exists( 'supavapes_refresh_order_notes_callback' ) ) {
	/**
	 * Refresh order notes.
	 *
	 * @since 1.0.0
	 */
	function supavapes_refresh_order_notes_callback() {
		$order_id = (int) filter_input( INPUT_POST, 'order_id', FILTER_SANITIZE_NUMBER_INT );

		// Return, if the order ID was not received.
		if ( false === $order_id ) {
			wp_send_json_error(
				array(
					'code'    => 'notes-not-refreshed',
					'message' => __( 'Unable to refresh the order notes. Order ID not found.', 'supavapes' ),
				)
			);
			wp_die();
		}

		$args  = array( 'order_id' => $order_id );
		$notes = wc_get_order_notes( $args );

		ob_start();
		include trailingslashit( get_stylesheet_directory() ) . 'woocommerce/admin/html-order-notes.php';
		$html = ob_get_clean();

		// Send the ajax success response.
		wp_send_json_error(
			array(
				'code' => 'notes-refreshed',
				'html' => $html,
			)
		);
		wp_die();
	}
}

add_action( 'wp_ajax_refresh_order_notes', 'supavapes_refresh_order_notes_callback' );



// Function to display the correct price based on the user state
function display_price_based_on_state() {
	global $product;

	// Get the product ID
	$product_id = $product->get_id();

	// Check for Ontario price
	$ontario_price = get_post_meta( $product_id, '_ontario_price', true );

	// Check for Federal price
	$federal_price = get_post_meta( $product_id, '_federal_price', true );

	// Get the user's state from the cookie
	$state = isset( $_COOKIE['user_state'] ) ? sanitize_text_field( $_COOKIE['user_state'] ) : '';

	// Determine which price to show
	if ( 'ontario' === strtolower( $state ) && $ontario_price ) {
		// Display Ontario price
		echo '<span class="product-price">' . wc_price( $ontario_price ) . ' (Ontario Price)</span>';
	} elseif ( 'federal' === strtolower( $state ) && $federal_price ) {
		// Display Federal price
		echo '<span class="product-price">' . wc_price( $federal_price ) . ' (Federal Price)</span>';
	} else {
		// Display regular or sale price
		if ( $product->is_on_sale() ) {
			echo '<span class="product-price">' . $product->get_sale_price() . ' (Sale Price)</span>';
		} else {
			echo '<span class="product-price">' . wc_price( $product->get_regular_price() ) . '</span>';
		}
	}
}

// Hook the function to display the price before the product summary
// add_action( 'woocommerce_before_single_product_summary', 'display_price_based_on_state', 9 );



/**
 * If the function, `supavapes_calculate_ontario_tax`, doesn't exist.
 */
if ( ! function_exists( 'supavapes_calculate_ontario_tax' ) ) {
	/**
	 * Calculate Ontario Tax based on vaping liquid volume.
	 *
	 * @param float $vaping_liquid The volume of the vaping liquid in ml.
	 * @return float The calculated Ontario tax amount.
	 * 
	 * Ontario tax calculated with the formula.
	 * 
	 * Tax applied on First 10ml liquid will be $1.12 * 5 = $5.6 ($1.12 per 2ml) // $1.12 will be dynamic. Can be changed from the back-end settings.
	 * Tax applied on Further value of liquid will be $1.12 * 10 = $11.2 ($1.12 per 10ml) // $1.12 will be dynamic. Can be changed from the back-end settings.
 	 * 
	 * @since 1.0.0
	 */
	function supavapes_calculate_ontario_tax( $vaping_liquid ) {
		// Fetch duty rates from ACF fields or replace with static values
		$ontario_excise_value_2_ml = get_field('ontario_excise_value_2_ml', 'option');
		$ontario_excise_value_10_ml = get_field('ontario_excise_value_10_ml', 'option');

		// Initialize duty rates
		$ontario_duty_per_2ml = $ontario_excise_value_2_ml; // Duty per 2 ml (first 10ml)
		$ontario_duty_per_10ml = $ontario_excise_value_10_ml; // Duty per 10 ml (remaining after 10ml)

		// Initialize tax variable
		$ontario_tax = 0;

		// Check if vaping_liquid value is greater than 10
		if ( $vaping_liquid >= 10 ) {
			// Divide the vaping_liquid value into two parts
			$first_part  = 10;
			$second_part = $vaping_liquid - $first_part;

			// Calculate tax for the first part (10 ml)
			$ontario_tax += (10 / 2) * $ontario_duty_per_2ml;

			// Calculate tax for the second part (if any)
			if ( $second_part > 0 ) {
				$full_tens = floor($second_part / 10); // Get full 10ml parts
				$remainder = $second_part % 10; // Get the remainder

				// Add tax for the full 10ml increments
				$ontario_tax += $full_tens * $ontario_duty_per_10ml;

				// Add $1 if there's any remainder (partial 10ml)
				if ( $remainder > 0 ) {
					$ontario_tax += 1; // Add $1 for the remainder
				}
			}
		}

		return $ontario_tax;
	}
}



if ( ! function_exists( 'supavapes_calculate_federal_tax' ) ) {
	/**
	 * Calculate Federal Tax based on vaping liquid volume.
	 *
	 * @param float $vaping_liquid The volume of the vaping liquid in ml.
	 * @return float The calculated Federal tax amount.
	 * 
	 * Federal tax calculated with the formula.
	 * 
	 * Tax applied on First 10ml liquid will be $1.12 * 5 = $5.6 ($1.12 per 2ml) // $1.12 will be dynamic. Can be changed from the back-end settings.
	 * Tax applied on Further value of liquid will be $1.12 * 10 = $11.2 ($1.12 per 10ml) // $1.12 will be dynamic. Can be changed from the back-end settings.
	 * 
	 * @since 1.0.0
	 */
	function supavapes_calculate_federal_tax( $vaping_liquid ) {
		
		// Fetch duty rates from ACF fields or replace with static values
		$federal_excise_value_2_ml = get_field('federal_excise_value_2_ml', 'option');
		$federal_excise_value_10_ml = get_field('federal_excise_value_10_ml', 'option');

		// Initialize duty rates
		$federal_duty_per_2ml = $federal_excise_value_2_ml; // Duty per 2 ml (first 10ml)
		$federal_duty_per_10ml = $federal_excise_value_10_ml; // Duty per 10 ml (remaining after 10ml)

		// Initialize tax variable
		$federal_tax = 0;

		// Check if vaping_liquid value is greater than 10
		if ( $vaping_liquid >= 10 ) {
			// Divide the vaping_liquid value into two parts
			$first_part  = 10;
			$second_part = $vaping_liquid - $first_part;

			// Calculate tax for the first part (10 ml)
			$federal_tax += (10 / 2) * $federal_duty_per_2ml;

			// Calculate tax for the second part (if any)
			if ( $second_part > 0 ) {
				$full_tens = floor($second_part / 10); // Get full 10ml parts
				$remainder = $second_part % 10; // Get the remainder
		
				// Add tax for the full 10ml increments
				$federal_tax += $full_tens * $federal_duty_per_10ml;

				// Add $1 if there's any remainder (partial 10ml)
				if ( $remainder > 0 ) {
					$federal_tax += 1; // Add $1 for the remainder
				}
			}
		}
		return $federal_tax;
	}
}



/**
 * If the function, `supavapes_detail_page_price_breakdown_callback`, doesn't exist.
 */
if ( ! function_exists( 'supavapes_detail_page_price_breakdown_callback' ) ) {
	/**
	 * Add price breakdown to the product detail page.
	 * 
	 * @since 1.0.0
	 */
	function supavapes_detail_page_price_breakdown_callback() {
		global $product;
		$product_id = $product->get_id();
		$product_data = wc_get_product( $product_id );

		if ( $product_data && method_exists( $product_data, 'get_type' ) ) {
			$product_type = $product_data->get_type();
		}

		// Retrieve active offers
		$active_offers = supa_active_offers_from_discount();
		$applied_discount = 0;

		// Check if the current product is eligible for any discount
		if ( ! empty( $active_offers ) ) {
			foreach ( $active_offers as $offer ) {
				if ( isset( $offer['products'] ) && in_array( $product_id, $offer['products'] ) ) {
					$applied_discount = $offer['discount_value']; // Apply the discount
					break;
				}
			}
		}

		// Logic for Simple Products
		if ( $product_type == 'simple' ) {
			// echo "innnnn";
			$reg_price  = $product->get_regular_price();
			$sale_price = $product->get_sale_price();
			$product_price = $sale_price ? $sale_price : $reg_price;

			// If there is a discount, apply it
			if ( $applied_discount ) {
				if ( strpos( $applied_discount, '%' ) !== false ) {
					// Percentage-based discount
					$discount_value = str_replace( '%', '', $applied_discount );
					$discount_amount = ( $product_price * $discount_value ) / 100;
				} else {
					// Fixed discount (assumed to be a numeric value like "$5 OFF")
					$discount_amount = floatval( str_replace( array('$', ' OFF'), '', $applied_discount ) );
				}

				// Apply the discount to the price
				$product_price = max( 0, $product_price - $discount_amount );
			}

			$vaping_liquid = get_post_meta( $product_id, '_vaping_liquid', true );
			$vaping_liquid = (int) $vaping_liquid;
			$state = isset( $_COOKIE['user_state'] ) ? sanitize_text_field( $_COOKIE['user_state'] ) : '';

			if ( isset( $vaping_liquid ) && ! empty( $vaping_liquid ) ) {
				$ontario_tax = supavapes_calculate_ontario_tax( $vaping_liquid );
				$federal_tax = supavapes_calculate_federal_tax( $vaping_liquid );
			}

			// Determine the final price based on state
			$final_price = $product_price;
			if ( 'Ontario' !== $state ) {
				$final_price += $federal_tax;
			} else {
				$final_price += $ontario_tax + $federal_tax;
			}

			// Add the info icon and price breakdown popup
			ob_start(); ?>
			<?php //echo wc_price( $product_price ); ?>
			<?php if ( isset( $vaping_liquid ) && ! empty( $vaping_liquid ) && $vaping_liquid >= 10 ) { 
				echo supavapes_price_breakdown_custom_html( $product_price, $federal_tax, $ontario_tax, $final_price, $state );
				?>
				<?php if( isset( $vaping_liquid ) && !empty( $vaping_liquid ) ) { ?>
					<p class="vaping-liquid-value"><?php esc_html_e( 'Vaping Liquid: ','supavapes' ); ?><?php echo $vaping_liquid.' ml'; ?></p>
				<?php } ?>
			<?php } ?>
			<?php 
			echo ob_get_clean();
		}

		// Logic for Variable Products
		if ( $product_type == 'variable' ) {
			$available_variations = $product->get_available_variations();

			$min_vaping_liquid = PHP_INT_MAX;
			$max_vaping_liquid = PHP_INT_MIN;

			foreach ( $available_variations as $variation ) {
				$vaping_liquid_value = (int) get_post_meta( $variation['variation_id'], '_vaping_liquid', true );

				if ( $vaping_liquid_value > 0 ) {
					if ( $vaping_liquid_value < $min_vaping_liquid ) {
						$min_vaping_liquid = $vaping_liquid_value;
					}
					if ( $vaping_liquid_value > $max_vaping_liquid ) {
						$max_vaping_liquid = $vaping_liquid_value;
					}
				}
			}
			if ( $min_vaping_liquid == PHP_INT_MAX ) {
				$min_vaping_liquid = 0;
			}
			if ( $max_vaping_liquid == PHP_INT_MIN ) {
				$max_vaping_liquid = 0;
			}
			$min_price = $product->get_variation_price( 'min' );
			$max_price = $product->get_variation_price( 'max' );
			$state = isset( $_COOKIE['user_state'] ) ? sanitize_text_field( $_COOKIE['user_state'] ) : '';
			$min_ontario_tax = supavapes_calculate_ontario_tax( $min_vaping_liquid );
			$max_ontario_tax = supavapes_calculate_ontario_tax( $max_vaping_liquid );
			$min_federal_tax = supavapes_calculate_federal_tax( $min_vaping_liquid );
			$max_federal_tax = supavapes_calculate_federal_tax( $max_vaping_liquid );
			if ( 'Ontario' !== $state ) {
				$final_min_price = floatval( $min_price ) + floatval( $min_federal_tax );
				$final_max_price = floatval( $max_price ) + floatval( $max_federal_tax );
			} else {
				$final_min_price = floatval( $min_price ) + floatval( $min_ontario_tax ) + floatval( $min_federal_tax );
				$final_max_price = floatval( $max_price ) + floatval( $max_ontario_tax ) + floatval( $max_federal_tax );
			}
			ob_start();
			?>
			<?php //echo $product->get_price_html(); ?>
			<?php
			if ( $min_ontario_tax > 0 || $max_ontario_tax > 0 || $min_federal_tax > 0 || $max_federal_tax > 0 ) {
				if ( $min_price === $max_price ) { 
					echo supavapes_price_breakdown_custom_html( $min_price, $min_federal_tax, $min_ontario_tax, $final_min_price, $state );
				} else { 
					echo supavapes_price_breakdown_in_range_custom_html( $min_price, $max_price, $min_federal_tax, $max_federal_tax, $min_ontario_tax, $max_ontario_tax, $final_min_price, $final_max_price, $state );
				} 
			}

			echo ob_get_clean();
		}
	}
}

// Add the price with the icon in place of the default one
add_action( 'woocommerce_single_product_summary', 'supavapes_detail_page_price_breakdown_callback', 10 );



/**
 * If the function, `supavapes_mini_cart_item_quantity_with_breakdown_callback`, doesn't exist.
 */
if ( ! function_exists( 'supavapes_mini_cart_item_quantity_with_breakdown_callback' ) ) {
	/**
	 * Add price breakup to the minicart items.
	 * 
	 * @param $quantity_html will contain existing quantity html for minicart.
	 * @param $cart_item contains the product items detail added into the cart.
	 * @param $cart_item_key contains the cart item key.
	 * 
	 * @since 1.0.0
	 */
	function supavapes_mini_cart_item_quantity_with_breakdown_callback( $quantity_html, $cart_item, $cart_item_key ) {
		// Get product details
		$product = $cart_item['data'];
		$product_id = $product->get_id();
		$quantity = $cart_item['quantity'];
		$product_price = wc_price( $product->get_price() );

		// Get necessary pricing details
		$reg_price  = $product->get_regular_price();
		$sale_price = $product->get_sale_price();
		$product_price = $sale_price ? $sale_price : $reg_price; // Use sale price if available, otherwise regular price
		$vaping_liquid = get_post_meta( $product_id, '_vaping_liquid', true );
		$vaping_liquid = (int) $vaping_liquid;

		// Custom tax calculations
		if ( isset( $vaping_liquid ) && !empty( $vaping_liquid ) ) {
			$ontario_tax = supavapes_calculate_ontario_tax( $vaping_liquid );
			$federal_tax = supavapes_calculate_federal_tax( $vaping_liquid );
		}
		// Determine the final price based on the state.
		$state = isset( $_COOKIE['user_state'] ) ? sanitize_text_field( $_COOKIE['user_state'] ) : '';

		// Final price adjustment based on the state (replace $state with the appropriate method to retrieve state)
		if ( 'Ontario' !== $state ) {
			$final_price = $sale_price ? $sale_price : $reg_price;
			$final_price += $federal_tax;
		} else {
			$final_price = $sale_price ? $sale_price : $reg_price;
			$final_price += $ontario_tax + $federal_tax;
		}

		// Start building the custom HTML
		ob_start();

		?>
		<!-- Display product quantity and price -->
		<div class="quantity">
			<?php echo sprintf( '%s &times; %s', $quantity, $product_price ); ?>
			<?php if ( isset( $vaping_liquid ) && !empty( $vaping_liquid ) && $vaping_liquid >= 10 ) { 
				 echo supavapes_price_breakdown_custom_html( $product_price, $federal_tax, $ontario_tax, $final_price, $state );
				?>
		</div>
		<?php }?>
		<!-- Price Breakdown with info icon -->

		<?php

		// quantity html output.
		$custom_quantity_html = ob_get_clean();

		return $custom_quantity_html;
	}
}

add_filter( 'woocommerce_widget_cart_item_quantity', 'supavapes_mini_cart_item_quantity_with_breakdown_callback', 10, 3 );


/**
 * If the function, `supavapes_add_custom_tax_meta_to_order_item`, doesn't exist.
 */
if ( ! function_exists( 'supavapes_add_custom_tax_meta_to_order_item' ) ) {
	/**
	 * Add tax values to the order meta while placing order.
	 * 
	 * @param $item will contain the order item data.
	 * @param $cart_item_key contains the cart item key.
	 * @param $values contains the product data to create a product object.
	 * @param $order contains order details.
	 * 
	 * @since 1.0.0
	 */
	function supavapes_add_custom_tax_meta_to_order_item( $item, $cart_item_key, $values, $order ) {
		$product = $values['data']; // Get the product object

		// Initialize variables for taxes
		$ontario_tax = 0;
		$federal_tax = 0;
		$final_tax = 0;

		// Check if the product is a variation
		if ( $product->is_type('variable') && $product->get_variation_id() ) {
			$product_id = $product->get_variation_id(); // For variable product (variation ID)
		} else {
			$product_id = $product->get_id(); // For simple product (regular product ID)
		}

		// Retrieve custom meta values based on the product ID (simple or variation)
		$vaping_liquid = get_post_meta($product_id, '_vaping_liquid', true);
		$vaping_liquid = (int) $vaping_liquid;
		$reg_price = $product->get_regular_price();
		$sale_price = $product->get_sale_price();

		// Calculate taxes using custom functions if vaping_liquid is set
		if ( !empty( $vaping_liquid ) ) {
			$ontario_tax = supavapes_calculate_ontario_tax($vaping_liquid);
			$federal_tax = supavapes_calculate_federal_tax($vaping_liquid);
		}

		// Determine final price and add taxes
		$state = isset( $_COOKIE['user_state']) ? sanitize_text_field($_COOKIE['user_state'] ) : '';
		if ( 'Ontario' !== $state ) {
			$final_price = !empty($sale_price) ? floatval($sale_price) : floatval($reg_price);
			$final_tax = floatval($federal_tax);
		} else {
			$final_price = !empty($sale_price) ? floatval($sale_price) : floatval($reg_price);
			$final_tax = floatval($ontario_tax) + floatval($federal_tax);
		}

		// Store the tax values in the order item meta
		$item->add_meta_data('ontario_tax', $ontario_tax, true);
		$item->add_meta_data('federal_tax', $federal_tax, true);
		$item->add_meta_data('final_tax_applied', $final_tax, true);
	}
}

add_action('woocommerce_checkout_create_order_line_item', 'supavapes_add_custom_tax_meta_to_order_item', 10, 4);


/**
 * Hide custom tax meta from appearing on the frontend (e.g., Order Received page).
 *
 * @param bool $display Whether to display the meta.
 * @param object $meta Meta data object.
 * @param object $item Order item object.
 * 
 * @return bool False if the meta key should not be displayed.
 */
function supavapes_hide_custom_order_meta( $display, $meta, $item ) {
	// List of meta keys to hide
	$hidden_meta_keys = array( 'ontario_tax', 'federal_tax', 'final_tax_applied' );

	// Hide if meta key is in the list
	if ( in_array( $meta->key, $hidden_meta_keys ) ) {
		return false;
	}

	return $display;
}

add_filter( 'woocommerce_order_item_display_meta_key', 'supavapes_hide_custom_order_meta', 10, 3 );


/**
 * Hide custom tax meta from appearing on the frontend (e.g., Order Received page).
 *
 * @param array $formatted_meta Meta data to display.
 * @param object $item Order item object.
 * 
 * @return array Filtered meta data.
 */
function supavapes_hide_custom_order_meta_data( $formatted_meta, $item ) {
	// List of meta keys to hide
	$hidden_meta_keys = array( 'ontario_tax', 'federal_tax', 'final_tax_applied' );

	// Loop through the meta data and remove the hidden keys
	foreach ( $formatted_meta as $key => $meta ) {
		if ( in_array( $meta->key, $hidden_meta_keys ) ) {
			unset( $formatted_meta[ $key ] );
		}
	}

	return $formatted_meta;
}

add_filter( 'woocommerce_order_item_get_formatted_meta_data', 'supavapes_hide_custom_order_meta_data', 10, 2 );


/**
 * If the function, `supavapes_add_custom_tax_meta_to_order_item`, doesn't exist.
 */
if ( ! function_exists( 'supavapes_woocommerce_admin_order_item_headers' ) ) {
	/**
	 * Add custom column headers for base price, Ontario tax, and federal tax
	 * 
	 * @since 1.0.0
	 */
	function supavapes_woocommerce_admin_order_item_headers() {
		echo '<th>Base Price</th>';
		echo '<th>Ontario Tax</th>';
		echo '<th>Federal Tax</th>';
	}
}

add_action('woocommerce_admin_order_item_headers', 'supavapes_woocommerce_admin_order_item_headers');


/**
 * If the function, `supavapes_display_order_item_tax_meta`, doesn't exist.
 */
if ( ! function_exists( 'supavapes_display_order_item_tax_meta' ) ) {
	/**
	 * Add custom column values to display tax data in the admin.
	 * 
	 * @param $_product will contain the product details.
	 * @param $item contains the order item data.
	 * @param $item_id contains the order item id.
	 * 
	 * @since 1.0.0
	 */
	function supavapes_display_order_item_tax_meta( $_product, $item, $item_id = null ) {
		// Check if the item is a product item, not a shipping item
		if ( $item instanceof WC_Order_Item_Product ) {
			// Retrieve the tax meta values
			$ontario_tax = wc_get_order_item_meta( $item_id, 'ontario_tax', true );
			$federal_tax = wc_get_order_item_meta( $item_id, 'federal_tax', true );

			// Get the product or variation details
			$product_id = $item->get_product_id(); // This gets the parent product ID or the variation ID.
			$variation_id = $item->get_variation_id(); // Get the variation ID if it exists.
			
			// Get product or variation price
			if ( $variation_id ) {
				// Get variation product
				$variation_product = wc_get_product($variation_id);
				$regular_price = $variation_product->get_regular_price();
				$sale_price = $variation_product->get_sale_price();
			} else {
				// If it's a simple product, get the regular product prices
				$product = wc_get_product($product_id);
				$regular_price = $product->get_regular_price();
				$sale_price = $product->get_sale_price();
			}

			// Base price: use the sale price if it exists, otherwise use the regular price
			$base_price = !empty( $sale_price ) ? $sale_price : $regular_price;

			// Display the values in the respective columns
			echo '<td>' . wc_price($base_price) . '</td>';
			echo '<td>' . wc_price($ontario_tax) . '</td>';
			echo '<td>' . wc_price($federal_tax) . '</td>';
		}
	}
}

add_action('woocommerce_admin_order_item_values', 'supavapes_display_order_item_tax_meta', 10, 3);



/**
 * If the function, `supavapes_modify_order_item_price_and_tax`, doesn't exist.
 */
if ( ! function_exists( 'supavapes_modify_order_item_price_and_tax' ) ) {
	/**
	 * Apply tax while we adding items to order from the back-end.
	 * 
	 * @param $item contains the order item data.
	 * @param $item_id contains the order item id.
	 * 
	 * @since 1.0.0
	 */
	function supavapes_modify_order_item_price_and_tax($item_id, $item) {
		// Check if it's a line item (product)
		if ( $item->get_type() === 'line_item' ) {
			// Get the product object
			$product = $item->get_product();
			$product_id = $product->get_id(); // Get the product ID
			
			if ( $product ) {
				// Get the regular and sale prices
				$reg_price = $product->get_regular_price();
				$sale_price = $product->get_sale_price();
				
				// Get the vaping_liquid custom field
				$vaping_liquid = get_post_meta($product_id, '_vaping_liquid', true);
				$vaping_liquid = (int) $vaping_liquid; // Ensure it's an integer
				
				// Initialize tax values
				$ontario_tax = 0;
				$federal_tax = 0;

				// Calculate taxes if vaping_liquid is set
				if (!empty($vaping_liquid)) {
					$ontario_tax = supavapes_calculate_ontario_tax( $vaping_liquid );
					$federal_tax = supavapes_calculate_federal_tax( $vaping_liquid );
				}

				// Determine state from the cookie
				$state = isset( $_COOKIE['user_state'] ) ? sanitize_text_field( $_COOKIE['user_state'] ) : '';

				// Set the final price based on whether the user is from Ontario
				if ('Ontario' !== $state) {
					// For non-Ontario users, apply federal tax only
					$final_price = !empty($sale_price) ? floatval($sale_price) : floatval($reg_price);
					$final_tax = floatval($federal_tax);
				} else {
					// For Ontario users, apply both Ontario and federal tax
					$final_price = !empty($sale_price) ? floatval($sale_price) : floatval($reg_price);
					$final_tax = floatval($ontario_tax) + floatval($federal_tax);
				}

				// Apply the tax to the final price
				$price_with_tax = $final_price + $final_tax;

				// Update the item total and subtotal (after price modification)
				$item->set_total( $price_with_tax * $item->get_quantity() ); // Total for all quantities
				$item->set_subtotal( $price_with_tax * $item->get_quantity() ); // Subtotal for all quantities

				// Save the tax values as order item meta
				wc_add_order_item_meta( $item_id, 'ontario_tax', $ontario_tax );
				wc_add_order_item_meta( $item_id, 'federal_tax', $federal_tax );

				// Save changes
				$item->save();
			}
		}
	}
}


add_action('woocommerce_new_order_item', 'supavapes_modify_order_item_price_and_tax', 10, 2);


/**
 * If the function, `supavapes_recalculate_order_items_based_on_state`, doesn't exist.
 */
if ( ! function_exists( 'supavapes_recalculate_order_items_based_on_state' ) ) {
	/**
	 * Apply tax while we adding items to order from the back-end.
	 * 
	 * @param $order contains the order item data.
	 * 
	 * @since 1.0.0
	 */
	function supavapes_recalculate_order_items_based_on_state($order) {
		
		// Get the billing state from the order
		$billing_state = $order->get_billing_state();

		// Debug log for tracking the billing state
		error_log('Billing state: ' . $billing_state);

		// Check if the billing state is empty
		if (empty($billing_state)) {
			error_log('No billing state found.');
			return;
		}

		// Loop through each line item in the order
		foreach ($order->get_items('line_item') as $item_id => $item) {
			$product = $item->get_product(); // Get the product object
			if (!$product) {
				continue; // Skip if product doesn't exist
			}

			$product_id = $product->get_id(); // Get the product ID
			$vaping_liquid = get_post_meta($product_id, '_vaping_liquid', true); // Retrieve custom meta
			$vaping_liquid = (int) $vaping_liquid;

			// Set default tax values
			$ontario_tax = 0;
			$federal_tax = 0;

			// Only calculate taxes if 'vaping_liquid' is set
			if (!empty($vaping_liquid)) {
				$ontario_tax = supavapes_calculate_ontario_tax($vaping_liquid);
				$federal_tax = supavapes_calculate_federal_tax($vaping_liquid);
			}

			// Determine price and tax based on the billing state
			if ('ON' !== $billing_state) {
				// If customer is outside Ontario, apply only federal tax
				$final_price = $product->get_sale_price() ? floatval($product->get_sale_price()) : floatval($product->get_regular_price());
				$final_tax = floatval($federal_tax);
			} else {
				// If customer is from Ontario, apply both Ontario and federal tax
				$final_price = $product->get_sale_price() ? floatval($product->get_sale_price()) : floatval($product->get_regular_price());
				$final_tax = floatval($ontario_tax) + floatval($federal_tax);
			}

			// Apply the final tax to the price
			$price_with_tax = $final_price + $final_tax;

			// Debug log for tracking price with tax
			error_log('Price with tax: ' . $price_with_tax);

			// Update the item total and subtotal (after tax modification)
			$item->set_total($price_with_tax * $item->get_quantity());
			$item->set_subtotal($price_with_tax * $item->get_quantity());

			// Save the tax values as meta data for the order item
			wc_update_order_item_meta($item_id, 'ontario_tax', $ontario_tax);
			wc_update_order_item_meta($item_id, 'federal_tax', $federal_tax);

			// Save the item to reflect changes
			$item->save();
		}

		// Recalculate the totals for the entire order after modifying items
		$order->calculate_totals();
	}

}

// add_action('woocommerce_before_save_order_items', 'supavapes_recalculate_order_items_based_on_state', 10, 1);



function supavapes_get_ip_location_and_set_cookies() {

	// debug($_COOKIE);
	if ( !isset($_COOKIE['user_city']) && empty($_COOKIE['user_city']) || !isset($_COOKIE['user_state']) && empty($_COOKIE['user_state']) || !isset($_COOKIE['user_country']) && empty($_COOKIE['user_country']) ) {
		// die('lkoooo');
		// echo "innnnnn";
		$ip_address = $_SERVER['REMOTE_ADDR']; // Get user IP address
		$access_key = '8cccc64e392297'; // Get your free access key from ipinfo.io

		// Fetch location data based on the user's IP address
		$url = "https://ipinfo.io/{$ip_address}?token={$access_key}";
		$response = wp_remote_get($url);

		if (is_wp_error($response)) {
			return; // Handle error
		}

		$body = wp_remote_retrieve_body($response);
		$data = json_decode($body, true);

		if (!empty($data)) {
			$location = explode(",", $data['loc']); // Get lat/lng if available
			$city = isset($data['city']) ? $data['city'] : '';
			$region = isset($data['region']) ? $data['region'] : '';
			$country = isset($data['country']) ? $data['country'] : '';

			// Set cookies for location data
			setcookie('user_city', $city, time() + 86400 * 7, '/');
			setcookie('user_state', $region, time() + 86400 * 7, '/');
			setcookie('user_country', $country, time() + 86400 * 7, '/');
			
		}

	}
	
}

add_action('init', 'supavapes_get_ip_location_and_set_cookies', 10);


/**
 * If the function `supavapes_update_user_location_callback` doesn't exist.
 */
if ( ! function_exists( 'supavapes_update_user_location_callback' ) ) {
	/**
	 * Ajax callback function to update user location.
	 *
	 * @since 1.0.0
	 */
	function supavapes_update_user_location_callback() {

		$userselectedstate = isset( $_POST['userselectedstate'] ) ? sanitize_text_field( $_POST['userselectedstate'] ) : '';
		$userselectedcountry = isset( $_POST['userselectedcountry'] ) ? sanitize_text_field( $_POST['userselectedcountry'] ) : '';
		
		if ( ! empty( $userselectedstate ) && ! empty( $userselectedcountry )) {
			// Set the state value in a cookie that expires in 7 days
			// setcookie( 'user_state', $state, time() + (86400 * 30), "/" );
			setcookie( 'user_state', $userselectedstate, time() + (86400 * 7), COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
			setcookie( 'user_country', $userselectedcountry, time() + (86400 * 7), COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
			wp_send_json_success( array( 'message' => 'State & country value has been updated in the cookie.' ) );
		} else {
			wp_send_json_error( array( 'message' => 'State & country value is not provided.' ) );
		}

		wp_die();
	}
}

add_action( 'wp_ajax_update_user_location', 'supavapes_update_user_location_callback' );
add_action( 'wp_ajax_nopriv_update_user_location', 'supavapes_update_user_location_callback' );


function supavapes_price_breakdown_order_received( $item_id, $item, $order, $is_visible ) {
	// Get the Ontario and Federal tax from the order meta
	$ontario_tax = wc_get_order_item_meta( $item_id, 'ontario_tax', true );
	$federal_tax = wc_get_order_item_meta( $item_id, 'federal_tax', true );

	// Get product and variation details
	$product_id = $item->get_product_id(); // Parent product ID
	$variation_id = $item->get_variation_id(); // Variation ID if it exists

	// Check if it's a variation or a simple product
	if ( $variation_id ) {
		// It's a variation, get the variation product object
		$product = wc_get_product( $variation_id );
	} else {
		// It's a simple product, get the product object
		$product = wc_get_product( $product_id );
	}

	// Get product price (regular and sale)
	$reg_price = $product->get_regular_price();
	$sale_price = $product->get_sale_price();
	$product_price = $sale_price ? $sale_price : $reg_price; // Use sale price if available, otherwise regular price

	// Calculate the final price including taxes
	$final_price = floatval( $product_price ) + floatval( $ontario_tax ) + floatval( $federal_tax );
	?>
	<div class="product-pricebreakup-wrap">
		<?php esc_html_e('Product Price: ','supavapes');?><?php echo wc_price( $final_price ); ?>
		<div class="info-icon-container">
		<img src="/wp-content/uploads/2024/09/info-icon.svg" class="info-icon" alt="Info Icon" style="height: 15px; width: 15px; position: relative;">
			<div class="price-breakup-popup">
				<h5 class="header"><?php esc_html_e( 'Price Breakdown','supavapes' ); ?></h5>
				<table class="pricetable">
					<tr>
						<td class='leftprice'><?php esc_html_e( 'Product Price','supavapes' ); ?></td>
						<td class='rightprice'><?php echo wc_price( $product_price ); ?></td>
					</tr>					
					<?php if ( isset( $federal_tax ) && !empty( $federal_tax ) ) { ?>
						<tr>
							<td class='leftprice'><?php esc_html_e( 'Federal Excise Tax','supavapes' ); ?></td>
							<td class='rightprice'><?php echo wc_price( $federal_tax ); ?></td>
						</tr>
					<?php } 
					if ( isset( $ontario_tax ) && !empty( $ontario_tax ) ) { ?>
						<tr>
							<td class='leftprice'><?php esc_html_e( 'Ontario Excise Tax','supavapes' ); ?></td>
							<td class='rightprice'><?php echo wc_price( $ontario_tax ); ?></td>
						</tr>
					<?php } ?>
					<tr class="wholesaleprice">
						<td class='leftprice'><?php esc_html_e( 'Total Price','supavapes' ); ?></td>
						<td class='rightprice'><?php echo wc_price( $final_price ); ?></td>
					</tr>
				</table>
			</div>
		</div>
	</div>
<?php 
}

// add_action( 'woocommerce_order_item_meta_end', 'supavapes_price_breakdown_order_received', 10, 4 );

/**
 * If the function, `supavapes_get_customer_current_location` doesn't exist.
 */
if ( ! function_exists( 'supavapes_get_customer_current_location' ) ) {
	/**
	 * Get the customer's current location.
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	function supavapes_get_customer_current_location() {

		return ( empty( $_COOKIE['user_state'] ) || is_null( $_COOKIE['user_state'] ) || false === $_COOKIE['user_state'] ) ? false : $_COOKIE['user_state'];
	}
}

/**
 * If the function, `supavapes_get_product_tax` doesn't exist.
 */
if ( ! function_exists( 'supavapes_get_product_tax' ) ) {
	/**
	 * Get the product's tax based on the location.
	 *
	 * @param float|int $vape_qty Vape product quantity in ml.
	 * @param string    $customer_location Customer current city.
	 *
	 * @return float
	 *
	 * @since 1.0.0
	 */
	function supavapes_get_product_tax( $vape_qty, $customer_location ) {
		$location             = ( ! empty( $customer_location ) && 'Ontario' === $customer_location ) ? 'ontario' : 'federal';
		$excise_value_2_ml    = get_field( "{$location}_excise_value_2_ml", 'option' );
		$excise_value_10_ml   = get_field( "{$location}_excise_value_10_ml", 'option' );
		$vape_qty_modulous_10 = $vape_qty % 10;
		$vape_qty_division_10 = floor( $vape_qty / 10 );

		// Check to see if the quantity is lesser than 10.
		if ( 0.0 === $vape_qty_division_10 ) {
			if ( 0 < $vape_qty_modulous_10 ) {
				$vape_qty_modulous_2 = $vape_qty % 2;
				$vape_qty_division_2 = floor( $vape_qty / 2 );
				$vape_qty_division_2 = ( 0.0 < $vape_qty_modulous_2 ) ? ( $vape_qty_division_2 + 1 ) : $vape_qty_division_2;
				$tax                 = $vape_qty_division_2 * 2;
			}
		}

		return $tax;
	}
}

/**
 * If the function, `supavapes_price_breakdown_html` doesn't exist.
 */
if ( ! function_exists( 'supavapes_price_breakdown_html' ) ) {
	/**
	 * Return the html for price breakdown popup.
	 *
	 * @param float $price Product price.
	 * @param float $federal_tax Federal exise duty value.
	 * @param float $ontario_tax Ontario exise duty value.
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	function supavapes_price_breakdown_html( $vape_qty = 0, $price = 0, $federal_tax = 0, $ontario_tax = 0 ) {
		if ( is_admin() ) return;

		$popup_heading             = get_field( 'popup_heading', 'option' );
		$popup_heading             = ( empty( $popup_heading ) || is_null( $popup_heading ) || false === $popup_heading ) ? __( 'Price Breakdown', 'supavapes' ) : $popup_heading;
		$product_price_label       = get_field( 'product_price_label', 'option' );
		$product_price_label       = ( empty( $product_price_label ) || is_null( $product_price_label ) || false === $product_price_label ) ? __( 'Price:', 'supavapes' ) : $product_price_label;
		$ontario_exise_tax_label   = get_field( 'ontario_exise_tax_label', 'option' );
		$ontario_exise_tax_label   = ( empty( $ontario_exise_tax_label ) || is_null( $ontario_exise_tax_label ) || false === $ontario_exise_tax_label ) ? __( 'Ontrio Tax:', 'supavapes' ) : $ontario_exise_tax_label;
		$federal_exise_tax_label   = get_field( 'federal_exise_tax_label', 'option' );
		$federal_exise_tax_label   = ( empty( $federal_exise_tax_label ) || is_null( $federal_exise_tax_label ) || false === $federal_exise_tax_label ) ? __( 'Federal Tax:', 'supavapes' ) : $federal_exise_tax_label;
		$total_product_price_label = get_field( 'total_product_price_label', 'option' );
		$total_product_price_label = ( empty( $total_product_price_label ) || is_null( $total_product_price_label ) || false === $total_product_price_label ) ? __( 'Total Product Price:', 'supavapes' ) : $total_product_price_label;
		$customer_location         = supavapes_get_customer_current_location();
		$vape_qty                  = 9; // In ml.
		$vape_tax                  = supavapes_get_product_tax( $vape_qty, $customer_location );

		ob_start();
		?>
		<div class="info-icon-container">
			<img src="/wp-content/uploads/2024/09/info-icon.svg" class="info-icon" alt="Info Icon" style="height: 15px; width: 15px; position: relative;">
			<div class="price-breakup-popup">
				<h5 class="header"><?php echo wp_kses_post( $popup_heading ); ?></h5>
				<table class="pricetable">
					<tr>
						<td class='leftprice'><?php echo wp_kses_post( $product_price_label ); ?></td>
						<td class='rightprice'><?php echo wc_price( $price ); ?></td>
					</tr>
					<?php if ( 'Ontario' === $customer_location ) { ?>
						<tr>
							<td class='leftprice'><?php echo wp_kses_post( $ontario_exise_tax_label ); ?></td>
							<td class='rightprice'><?php echo wc_price( $ontario_tax ); ?></td>
						</tr>
					<?php } ?>
					<tr>
						<td class='leftprice'><?php echo wp_kses_post( $federal_exise_tax_label ); ?></td>
						<td class='rightprice'><?php echo wc_price( $federal_tax ); ?></td>
					</tr>
					<tr class="wholesaleprice">
						<td class='leftprice'><?php echo wp_kses_post( $total_product_price_label ); ?></td>
						<td class='rightprice'><?php echo wc_price( 0 ); ?></td>
					</tr>
				</table>
			</div>
		</div>
		<?php

		echo ob_get_clean();

		return ob_get_clean();
	}
}

// supavapes_price_breakdown_html();



function supavapes_price_breakdown_custom_html( $product_price = null, $federal_tax = null, $ontario_tax = null, $final_price = null, $state = '' ) {
	
	$popup_heading             = get_field( 'popup_heading', 'option' );
	$popup_heading             = ( empty( $popup_heading ) || is_null( $popup_heading ) || false === $popup_heading ) ? __( 'Price Breakdown', 'supavapes' ) : $popup_heading;
	$product_price_label       = get_field( 'product_price_label', 'option' );
	$product_price_label       = ( empty( $product_price_label ) || is_null( $product_price_label ) || false === $product_price_label ) ? __( 'Price:', 'supavapes' ) : $product_price_label;
	$ontario_exise_tax_label   = get_field( 'ontario_exise_tax_label', 'option' );
	$ontario_exise_tax_label   = ( empty( $ontario_exise_tax_label ) || is_null( $ontario_exise_tax_label ) || false === $ontario_exise_tax_label ) ? __( 'Ontrio Tax:', 'supavapes' ) : $ontario_exise_tax_label;
	$federal_exise_tax_label   = get_field( 'federal_exise_tax_label', 'option' );
	$federal_exise_tax_label   = ( empty( $federal_exise_tax_label ) || is_null( $federal_exise_tax_label ) || false === $federal_exise_tax_label ) ? __( 'Federal Tax:', 'supavapes' ) : $federal_exise_tax_label;
	$total_product_price_label = get_field( 'total_product_price_label', 'option' );
	$total_product_price_label = ( empty( $total_product_price_label ) || is_null( $total_product_price_label ) || false === $total_product_price_label ) ? __( 'Total Product Price:', 'supavapes' ) : $total_product_price_label;
	ob_start(); // Start output buffering
	?>
	<div class="info-icon-container test">
		<img src="/wp-content/uploads/2024/09/info-icon.svg" class="info-icon" alt="Info Icon" style="height: 15px; width: 15px; position: relative;">
		<div class="price-breakup-popup">
			<h5 class="header"><?php echo wp_kses_post( $popup_heading ); ?></h5>
			<table class="pricetable">
				<tr>
					<td class='leftprice'><?php echo wp_kses_post( $product_price_label ); ?></td>
					<td class='rightprice'><?php echo wc_price( $product_price ); ?></td>
				</tr>
				<?php if ( 'Ontario' !== $state ) { ?>
				<tr>
					<td class='leftprice'><?php echo wp_kses_post( $federal_exise_tax_label ); ?></td>
					<td class='rightprice'><?php echo wc_price( $federal_tax ); ?></td>
				</tr>
				<?php } else { ?>
				<tr>
					<td class='leftprice'><?php echo wp_kses_post( $ontario_exise_tax_label ); ?></td>
					<td class='rightprice'><?php echo wc_price( $ontario_tax ); ?></td>
				</tr>
				<tr>
					<td class='leftprice'><?php echo wp_kses_post( $federal_exise_tax_label ); ?></td>
					<td class='rightprice'><?php echo wc_price( $federal_tax ); ?></td>
				</tr>
				<?php } ?>
				<tr class="wholesaleprice">
					<td class='leftprice'><?php echo wp_kses_post( $total_product_price_label ); ?></td>
					<td class='rightprice'><?php echo wc_price( $final_price ); ?></td>
				</tr>
			</table>
		</div>
	</div>
	<?php
	return ob_get_clean(); // Return the buffered output
}



function supavapes_price_breakdown_in_range_custom_html( $min_price = null, $max_price = null, $min_federal_tax = null, $max_federal_tax = null, $min_ontario_tax = null, $max_ontario_tax = null, $final_min_price = null, $final_max_price = null, $state = '' ) {
	
	$popup_heading             = get_field( 'popup_heading', 'option' );
	$popup_heading             = ( empty( $popup_heading ) || is_null( $popup_heading ) || false === $popup_heading ) ? __( 'Price Breakdown', 'supavapes' ) : $popup_heading;
	$product_price_label       = get_field( 'product_price_label', 'option' );
	$product_price_label       = ( empty( $product_price_label ) || is_null( $product_price_label ) || false === $product_price_label ) ? __( 'Price:', 'supavapes' ) : $product_price_label;
	$ontario_exise_tax_label   = get_field( 'ontario_exise_tax_label', 'option' );
	$ontario_exise_tax_label   = ( empty( $ontario_exise_tax_label ) || is_null( $ontario_exise_tax_label ) || false === $ontario_exise_tax_label ) ? __( 'Ontario Tax:', 'supavapes' ) : $ontario_exise_tax_label;
	$federal_exise_tax_label   = get_field( 'federal_exise_tax_label', 'option' );
	$federal_exise_tax_label   = ( empty( $federal_exise_tax_label ) || is_null( $federal_exise_tax_label ) || false === $federal_exise_tax_label ) ? __( 'Federal Tax:', 'supavapes' ) : $federal_exise_tax_label;
	$total_product_price_label = get_field( 'total_product_price_label', 'option' );
	$total_product_price_label = ( empty( $total_product_price_label ) || is_null( $total_product_price_label ) || false === $total_product_price_label ) ? __( 'Total Product Price:', 'supavapes' ) : $total_product_price_label;

	ob_start(); // Start output buffering
	?>
	<div class="info-icon-container test">
		<img src="/wp-content/uploads/2024/09/info-icon.svg" class="info-icon" alt="Info Icon" style="height: 15px; width: 15px; position: relative;">
		<div class="price-breakup-popup">
			<h5 class="header"><?php echo wp_kses_post( $popup_heading ); ?></h5>
			<table class="pricetable">
				<tr>
					<td class='leftprice'><?php echo wp_kses_post( $product_price_label ); ?></td>
					<td class='rightprice'><?php echo wc_price( $min_price ) . ' - ' . wc_price( $max_price ); ?></td>
				</tr>
				<?php if ( 'Ontario' !== $state ) { ?>
				<tr>
					<td class='leftprice'><?php echo wp_kses_post( $federal_exise_tax_label ); ?></td>
					<td class='rightprice'><?php echo wc_price( $min_federal_tax ) . ' - ' . wc_price( $max_federal_tax ); ?></td>
				</tr>
				<?php } else { ?>
				<tr>
					<td class='leftprice'><?php echo wp_kses_post( $ontario_exise_tax_label ); ?></td>
					<td class='rightprice'><?php echo wc_price( $min_ontario_tax ) . ' - ' . wc_price( $max_ontario_tax ); ?></td>
				</tr>
				<tr>
					<td class='leftprice'><?php echo wp_kses_post( $federal_exise_tax_label ); ?></td>
					<td class='rightprice'><?php echo wc_price( $min_federal_tax ) . ' - ' . wc_price( $max_federal_tax ); ?></td>
				</tr>
				<?php } ?>
				<tr class="wholesaleprice">
					<td class='leftprice'><?php echo wp_kses_post( $total_product_price_label ); ?></td>
					<td class='rightprice'><?php echo wc_price( $final_min_price ) . ' - ' . wc_price( $final_max_price ); ?></td>
				</tr>
			</table>
		</div>
	</div>
	<?php
	return ob_get_clean(); // Return the buffered output
}


if ( ! function_exists( 'supavapes_woocommerce_cart_calculate_fees_callback' ) ) {
	/**
	 * Add custom fees to cart and checkout based on the total _vaping_liquid meta across all items.
	 *
	 * @param WC_Cart $cart WooCommerce cart object.
	 *
	 * @since 1.0.0
	 */
	function supavapes_woocommerce_cart_calculate_fees_callback( $cart ) {

		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		// Only for Canada country (if not we exit).
		if ( 'CA' != WC()->customer->get_shipping_country() ) {
			return;
		}

		// Determine the final price based on the state.
		$state = isset( $_COOKIE['user_state'] ) ? sanitize_text_field( $_COOKIE['user_state'] ) : '';
		// $state = 'Ontario';

		$ontario_tax_total = 0;
		$federal_tax_total = 0;

		// Loop through cart items.
		foreach ( $cart->get_cart() as $cart_item ) {
			$product = $cart_item['data'];

			// Get _vaping_liquid meta value. 
			$vaping_liquid = get_post_meta( $product->get_id(), '_vaping_liquid', true );

			// If it's a variable product, get the variation meta.
			if ( $product->is_type( 'variation' ) ) {
				$vaping_liquid = get_post_meta( $cart_item['variation_id'], '_vaping_liquid', true );
			}

			// Ensure $vaping_liquid is numeric and valid.
			if ( is_numeric( $vaping_liquid ) && $vaping_liquid > 0 ) {
				// Calculate the total vaping liquid based on product quantity.
				// $total_vaping_liquid = $vaping_liquid * $cart_item['quantity'];
				// echo "Total vaping liquid: ".$total_vaping_liquid;

				// Calculate Ontario and Federal taxes using the provided functions.
				$ontario_tax_single = supavapes_calculate_ontario_tax( $vaping_liquid );
				$federal_tax_single = supavapes_calculate_federal_tax( $vaping_liquid );
				
				$ontario_tax_total += $ontario_tax_single * $cart_item['quantity'];
				$federal_tax_total += $federal_tax_single * $cart_item['quantity'];
			}
		}
		
		// echo "State: ".$state;
		if ( 'Ontario' == $state ) {
			// Add fees to the cart.
			if ( $ontario_tax_total > 0 ) {
				$cart->add_fee( __( 'ONTARIO TAX', 'supavapes' ), $ontario_tax_total, false );
			}

			if ( $federal_tax_total > 0 ) {
				$cart->add_fee( __( 'FEDERAL TAX', 'supavapes' ), $ontario_tax_total, false );
			}
		} else {
			if ( $federal_tax_total > 0 ) {
				$cart->add_fee( __( 'FEDERAL TAX', 'supavapes' ), $federal_tax_total, false );
			}
		}
	}

}

add_action( 'woocommerce_cart_calculate_fees', 'supavapes_woocommerce_cart_calculate_fees_callback' );

// last code at 5518


/**
 * If the function, `supavapes_hide_selected_terms` doesn't exist.
 */
if ( ! function_exists( 'supavapes_hide_selected_terms' ) ) {
	/**
	 * Remove categories from shop and other pages
	 * 
	 * @param array $terms contains variation product title for variable product.
	 * @param array $taxonomies contains taxonomied details.
	 * @param array $args contains terms arguments.
	 *
	 * @since 1.0.0
	 */
	function supavapes_hide_selected_terms( $terms, $taxonomies, $args ) {
		$new_terms = array();
		if ( in_array( 'product_cat', $taxonomies ) && !is_admin() && is_shop() ) {
			foreach ( $terms as $key => $term ) {
				if ( ! in_array( $term->slug, array( 'uncategorized' ) ) ) {
					$new_terms[] = $term;
				}
			}
			$terms = $new_terms;
		}
		return $terms;
	}
}

add_filter( 'get_terms', 'supavapes_hide_selected_terms', 10, 3 );


/**
 * If the function, `supavapes_redirect_wp_login_register_to_my_account` doesn't exist.
 */
if ( ! function_exists( 'supavapes_redirect_wp_login_register_to_my_account' ) ) {
	/**
	 * Redirect wordpress urls to my account page based on actions.
	 *
	 * @since 1.0.0
	 */
	function supavapes_redirect_wp_login_register_to_my_account() {
		// Check if the action is register
		if ( isset( $_GET['action'] ) && $_GET['action'] == 'register' ) {
			// Redirect to the WooCommerce My Account page
			wp_redirect( home_url( '/my-account/' ) );
			exit;
		}
		// Check if the action is lostpassword
		if ( isset( $_GET['action'] ) && $_GET['action'] == 'lostpassword' ) {
			// Redirect to the WooCommerce My Account page
			wp_redirect( home_url( '/my-account/lost-password/' ) );
			exit;
		}
	}

}

add_action( 'login_init', 'supavapes_redirect_wp_login_register_to_my_account' );


/**
 * If the function, `supavapes_single_location_badge` doesn't exist.
 */
if ( ! function_exists( 'supavapes_single_location_badge' ) ) {
	/**
	 * Add badge on product detail page on featured image
	 *
	 * @since 1.0.0
	 */
	function supavapes_single_location_badge() {
		// Define your custom image URL
		$state = isset( $_COOKIE['user_state'] ) ? sanitize_text_field( $_COOKIE['user_state'] ) : '';

		ob_start(); 
		?>
		<div class="single-location-badge" style="display:none;">
			<?php
				if ( 'Ontario' == $state ) {
					?>
						<img src="/wp-content/themes/supavapes/assets/images/shop-ontario.png" alt="Custom Image" />
					<?php
				} else {
					?>
						<img src="/wp-content/themes/supavapes/assets/images/shop-federal.png" alt="Custom Image" />
					<?php
				}
			?>
		</div>
		<?php
		echo ob_get_clean();
	}
}

add_action( 'woocommerce_before_single_product_summary', 'supavapes_single_location_badge', 5 );




/**
 * If the function, `supavapes_add_vaping_liquid_below_variation_title` doesn't exist.
 */
if ( ! function_exists( 'supavapes_add_vaping_liquid_below_variation_title' ) ) {
	/**
	 * Add badge on product detail page on featured image
	 * 
	 * @param string $variation_title contains variation product title for variable product
	 * @param int $variation_id contains variation id for variable product
	 *
	 * @since 1.0.0
	 */
	function supavapes_add_vaping_liquid_below_variation_title( $variation_title, $variation_id ) {
		// Get the custom field value for '_vaping_liquid'
		$vaping_liquid = get_post_meta( $variation_id, '_vaping_liquid', true );

		// Output the title as it is
		$output = '<div class="variation-title-wrap"><h4>' . wp_kses_post( $variation_title ) . '</h4>';

		// If the vaping liquid custom field is set, display it below the title
		if ( ! empty( $vaping_liquid ) ) {
			$output .= '<span><b>Vaping Liquid:</b> ' . esc_html( $vaping_liquid ) . ' ml</span></div>';
		}

		return $output;
	}
}

add_filter( 'wqcmv_variation_title', 'supavapes_add_vaping_liquid_below_variation_title', 10, 2 );

/**
 * If the function, `supavapes_match_location_callback` doesn't exist.
 */
if ( ! function_exists( 'supavapes_match_location_callback' ) ) {
	/**
	 * Callback function to match location with shipping location on checkout page.
	 *
	 * @since 1.0.0
	 */
	function supavapes_match_location_callback() {

		$shipping_state_code  =  WC()->customer->get_shipping_state();
		$shipping_country     =  WC()->customer->get_shipping_country(); // Get the shipping country dynamically
		$user_state 		  =  isset( $_COOKIE['user_state'] ) ? sanitize_text_field( $_COOKIE['user_state'] ) : ''; // Retrieve the user state from the cookie
		$shipping_state       =  WC()->countries->get_states( $shipping_country )[$shipping_state_code]; // Use the dynamic country code

		// Check if both states match
		if ( $shipping_state && $user_state && $shipping_state === $user_state ) {
			wp_send_json_success( array( 'match_location' => 'match' ) );
		} else {
			wp_send_json_success( array( 'match_location' => 'not match' ) );
		}
	}
}

add_action( 'wp_ajax_match_location', 'supavapes_match_location_callback' );
add_action( 'wp_ajax_nopriv_match_location', 'supavapes_match_location_callback' );



/**
 * If the function, `supavapes_checkout_notice_shortcode` doesn't exist.
 */
if ( ! function_exists( 'supavapes_checkout_notice_shortcode' ) ) {
	/**
	 * Create the shortcode for the checkout notice
	 *
	 * @since 1.0.0
	 */
	function supavapes_checkout_notice_shortcode() {
		ob_start();
		?>
		<div class="sv-woocommerce-notice-wrap">
			<?php
			wc_print_notice(sprintf(
				__("%s Your selected current location and shipping address do not match. Please update one of them to proceed.", "woocommerce"),
				'<strong>' . __("Location mismatch:", "woocommerce") . '</strong>'
			), 'notice', array('class' => 'custom-checkout-notice')); // Adding custom class here

			?>
		</div>
		<?php
		return ob_get_clean();
	}
}

add_shortcode( 'checkout_notice', 'supavapes_checkout_notice_shortcode' );