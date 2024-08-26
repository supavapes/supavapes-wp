<?php

/**
 * If the function, `supavapes_product_reviews_callback` doesn't exist.
 */
if ( ! function_exists( 'supavapes_product_reviews_callback' ) ) {
	/**
	 * Display product reviews.
	 * Pass product ids as a shortcode attribute. In case of multiple product IDs, pass them comma-separated.
	 * Use of Shortcode: [product_reviews ids=""]
	 *
	 * @param array $atts Shortcode arrtibutes.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	function supavapes_product_reviews_callback( $atts = array() ) {
		// Return, if it's the admin screen.
		if ( is_admin() ) {
			return;
		}

		$atts = shortcode_atts(
			array(
				'ids' => '',
			),
			$atts,
			'product_reviews'
		);

		// Start preparing the shortcode HTML.
		ob_start();

		set_query_var( 'shortcode_atts', $atts );
		require locate_template( 'templates/shortcodes/product-reviews.php' );

		return ob_get_clean();
	}
}

add_shortcode( 'product_reviews', 'supavapes_product_reviews_callback' );

/**
 * If the function, `supavapes_monthly_deals_callback` doesn't exist.
 */
if ( ! function_exists( 'supavapes_monthly_deals_callback' ) ) {
	/**
	 * Display the monthly deals on the homepage.
	 * Pass term id as a shortcode attribute. Set limit to display number of products. Default limit will be 8.
	 * Use of Shortcode: [[monthly_deals limit=""]
	 *
	 * @param array $atts Shortcode arrtibutes.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	function supavapes_monthly_deals_callback( $atts = array() ) {
		// Return, if it's the admin screen.
		if ( is_admin() ) {
			return;
		}

		$atts = shortcode_atts(
			array(
				'limit' => 8,
			),
			$atts
		);

		// Start preparing the shortcode HTML.
		ob_start();

		set_query_var( 'shortcode_atts', $atts );
		require locate_template( 'templates/shortcodes/monthly-deals.php' );

		return ob_get_clean();
	}
}

add_shortcode( 'monthly_deals', 'supavapes_monthly_deals_callback' );

/**
 * If the function, `supavapes_new_arrivals_callback` doesn't exist.
 */
if ( ! function_exists( 'supavapes_new_arrivals_callback' ) ) {
	/**
	 * Function to display New Arrivals Products.
	 * Pass term id as a shortcode attribute. Set limit to display number of products. Default limit will be 8.
	 * Use of Shortcode: [new_arrivals limit="10"]
	 *
	 * @param array $atts Shortcode arrtibutes.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	function supavapes_new_arrivals_callback( $atts = array() ) {
		// Return, if it's the admin screen.
		if ( is_admin() ) {
			return;
		}

		$atts = shortcode_atts(
			array(
				'limit' => 8,
			),
			$atts
		);

		ob_start();
		set_query_var( 'shortcode_atts', $atts );
		require locate_template( 'templates/shortcodes/new-arrival.php' );

		return ob_get_clean();
	}
}

add_shortcode( 'new_arrivals', 'supavapes_new_arrivals_callback' );

/**
 * If the function, `supavapes_our_juices_callback` doesn't exist.
 */
if ( ! function_exists( 'supavapes_our_juices_callback' ) ) {
	/**
	 * Function to display Juice products.
	 * Pass term id as a shortcode attribute. Set limit to display number of products. Default limit will be 8.
	 * Use of Shortcode: [our_juices term_ids=""]
	 * 
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	function supavapes_our_juices_callback( $atts = array() ) {
		// Return, if it's the admin screen.
		if ( is_admin() ) {
			return;
		}

		$atts = shortcode_atts(
			array(
				'term_ids' => '',
			),
			$atts
		);

		ob_start();
		set_query_var('shortcode_atts', $atts);
		require locate_template('templates/shortcodes/our-juices.php');

		return ob_get_clean();
	}
}

add_shortcode( 'our_juices', 'supavapes_our_juices_callback' );

/**
 * If the function, `supavapes_search_form_callback` doesn't exist.
 */
if ( ! function_exists( 'supavapes_search_form_callback' ) ) {
	/**
	 * Funtion to display default search form of wordpress.
	 * Shortcode Usage: [search_form]
	 * 
	 * @return string
	 * @since 1.0.0
	 */
	function supavapes_search_form_callback() {
		// Return, if it's the admin screen.
		if ( is_admin() ) {
			return;
		}

		return get_search_form(false);
	}
}

add_shortcode( 'search_form', 'supavapes_search_form_callback' );

/**
 * If the function, `supavapes_mini_cart_callback` doesn't exist.
 */
if ( ! function_exists( 'supavapes_mini_cart_callback' ) ) {
	/**
	 * Function to display Mini Cart in header
	 * Shortcode Usage: [mini_cart]
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 * @since 1.0.0
	 */
	function supavapes_mini_cart_callback( $atts = array() ) { 
		// Return, if it's the admin screen.
		if ( is_admin() ) {
			return;
		}

		ob_start();
		require_once get_stylesheet_directory() . '/templates/shortcodes/minicart.php';

		return ob_get_clean();
	}
}

add_shortcode( 'mini_cart', 'supavapes_mini_cart_callback' );

/**
 * If the function `supavapes_cart_count_callback` doesn't exist.
 */
if ( ! function_exists( 'supavapes_cart_count_callback' ) ) {
	/**
	 * Function to render cart count
	 * 
	 * @since 1.0.0
	 */
	function supavapes_cart_count_callback() { 
		// Return, if it's the admin screen.
		if ( is_admin() ) {
			return;
		}

		ob_start();
		require_once get_stylesheet_directory() . '/templates/shortcodes/cart-count.php';

		return ob_get_clean();
	}
}

add_shortcode( 'cart_count', 'supavapes_cart_count_callback' );

/**
 * If the function, `supavapes_blog_listing_callback` doesn't exist.
 */
if ( ! function_exists( 'supavapes_blog_listing_callback' ) ) {
	/**
	 * List the supavapes blogs.
	 *
	 * @param $atts array Shortcode attributes.
	 * 
	 * @return string
	 * @since 1.0.0
	 */
	function supavapes_blog_listing_callback( $atts = array() ) { 
		// Return, if it's the admin screen.
		if ( is_admin() ) {
			return;
		}

		ob_start();
		require_once get_stylesheet_directory() . '/templates/shortcodes/blog-listing.php';

		return ob_get_clean();
	}
}

add_shortcode( 'blog_listing', 'supavapes_blog_listing_callback' );
