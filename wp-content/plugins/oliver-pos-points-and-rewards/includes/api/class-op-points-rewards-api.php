<?php
namespace OPR_API;

use WP_REST_Server;
use WC_Points_Rewards_Product;
use WC_Points_Rewards_Manager;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if someone accessed directly.
}

/**
 * Class OPR_Points_Rewards_Setting
 * Description handle all product operation of plugin
 */
class OPR_Points_Rewards_Api {

    // Op points and rewards manager
    private $opr_points_rewards_manager;

    // opr_endpoint for rest route api
    private $opr_endpoint;

    /**
     * Class construct
     *
     * @since 1.0.0
     * @param object $opr_points_rewards_manager opr_points_rewards_manager
     * @return void void
     */
    public function __construct( $opr_points_rewards_manager ) {
        // Intialize this opr_points_rewards_manager
        $this->opr_points_rewards_manager = $opr_points_rewards_manager;

        // Intialize this opr_endpoint
        $this->opr_endpoint = $this->opr_points_rewards_manager->opr_points_rewards->opr_endpoint;

        // Add actions
        $this->opr_api_actions();
    }

    /**
     * Add actions
     *
     * @since 1.0.0
     * @return void void
     */
    public function opr_api_actions() {
        // Register rest routes / api's
        add_action('rest_api_init', array($this, 'opr_api_register_rest_route'));
    }

    /**
     * Get Settings
     *
     * @since 1.0.0
     * @return array settings
     */
    public function opr_api_register_rest_route() {
        register_rest_route( $this->opr_endpoint, '/get-points/', array(
                'methods' => WP_REST_Server::ALLMETHODS,
                'callback' => array($this, 'opr_api_get_points'),
                'permission_callback' => array($this, 'rest_authentication')
            )
        );
		
		register_rest_route( $this->opr_endpoint, '/user-point/', array(
                'methods' => WP_REST_Server::ALLMETHODS,
                'callback' => array($this, 'opr_api_get_user_point'),
                'permission_callback' => array($this, 'rest_authentication')
            )
        );
    }

	/**
     * update points
     *
     * @since 2.0.2
     * @return point
     */
	public function opr_api_get_user_point( $request_data ) {
		$params = $request_data->get_params();
        $request_data = json_decode( $params['oprRequestData'] );
		$customer_name  = '';
        $customer_id  = 0;
		$current_points = 0;
		if (isset($request_data->email)) {
            $email = $request_data->email;
            if ( ! empty($email) && email_exists($email)) {
                $get_user_by_email = get_user_by('email', $email);
                $customer_name = $get_user_by_email->display_name;
                $customer_id = $get_user_by_email->ID;
            }
        }
		if ($customer_id > 0) {
            $current_points = WC_Points_Rewards_Manager::get_users_points( $customer_id ) ;
        }
		$data = array(
            'customer_name' => $customer_name,
            'customer_id' => $customer_id,
			'current_points' => $current_points
		);
		return $data;
	}
    /**
     * Get Points
     *
     * @since 1.0.0
     * @param array $hide_fields returns points
     * @return array points
     */
    public function opr_api_get_points( $request_data ) {
        $params = $request_data->get_params();
        $request_data = json_decode( $params['oprRequestData'] );
		$customer_name  = '';
        $customer_id  = 0;
        $points_earned_this_sale = 0;
        $current_points = 0;
        $currency_value_of_points = 0;
        $current_sale_total = 0;
        $get_users_points_value = 0;
        $get_users_points_redeem_for_sale = 0;
        $get_users_already_add_points_to_sale = 0;
        $opr_totalTax = 0;
        $cart_products = array();
		
		list( $opr_points_value, $opr_monetary_value ) = explode( ':', get_option( 'wc_points_rewards_redeem_points_ratio', '' ) );
		
		if (isset($request_data->email)) {
            $email = $request_data->email;
            if ( ! empty($email) && email_exists($email)) {
                $get_user_by_email = get_user_by('email', $email);
                $customer_name = $get_user_by_email->display_name;
                $customer_id = $get_user_by_email->ID;
            }
        }
		
		if (isset($request_data->products)) {
            $cart_products = $request_data->products;
             if ( ! empty($cart_products) && is_array($cart_products)) {
                 foreach ($cart_products as $cart_product) {
                    $get_points_earned_for_product_purchase = WC_Points_Rewards_Product::get_points_earned_for_product_purchase( $cart_product->product_id );
                    $points_earned_this_sale += ( $get_points_earned_for_product_purchase * (int) $cart_product->quantity );
                }
            }
        }
		
		$points_earned_this_sale = WC_Points_Rewards_Manager::round_the_points($points_earned_this_sale);
        $wc_points_rewards_redeem_points_ratio = get_option('wc_points_rewards_redeem_points_ratio');
        if ( $wc_points_rewards_redeem_points_ratio ) {
            $currency_value_of_points = $wc_points_rewards_redeem_points_ratio;
        }
		if ($customer_id > 0) {
            $current_points = WC_Points_Rewards_Manager::get_users_points( $customer_id ) ;
            $get_users_points_value = WC_Points_Rewards_Manager::get_users_points_value( $customer_id );
        }
        $get_users_points_value = (float) $get_users_points_value;
		
		if ( isset( $request_data->sub_total ) ) {
            $current_sale_total = $request_data->sub_total;
        }
		if ( isset( $request_data->totalTax ) ) {
            $opr_totalTax = $request_data->totalTax;
        }
		
		if ( (int) $get_users_points_value > $current_sale_total ) {
            $get_users_points_value = $get_users_points_value - ( $get_users_points_value - $current_sale_total );
        }
		
		$get_users_points_value -=  $get_users_already_add_points_to_sale;
        $get_users_points_redeem_for_sale = WC_Points_Rewards_Manager::calculate_points_for_discount( $get_users_points_value );

        // Get calculated discount for points redeeming Receive (discount_applied, minimum_discount, max_discount)
		
		$opr_api_get_discount_calculations = $this->opr_api_get_discount_calculations( true, null, false , $customer_id, $get_users_points_value, $cart_products, ( $current_sale_total - $request_data->totalTax ), $get_users_already_add_points_to_sale );
		$wc_points_rewards_redeeming_points = $opr_api_get_discount_calculations['discount_applied'];

        $wc_points_rewards_cart_min_discount_amount = $opr_api_get_discount_calculations['minimum_discount'];
        $wc_points_rewards_cart_min_discount_points = WC_Points_Rewards_Manager::calculate_points_for_discount( $wc_points_rewards_cart_min_discount_amount );

        $wc_points_rewards_cart_max_discount_amount = $opr_api_get_discount_calculations['max_discount'];
        if ( 0 == $wc_points_rewards_cart_max_discount_amount ) {
            $wc_points_rewards_cart_max_discount_amount = $wc_points_rewards_redeeming_points;
        }

        $opr_tax_inclusive = get_option( 'wc_points_rewards_points_tax_application');
        if($opr_tax_inclusive=='exclusive'){
            $tax_free_current_sale_total = $current_sale_total;
            if($tax_free_current_sale_total < $wc_points_rewards_cart_max_discount_amount)
            {
                $wc_points_rewards_cart_max_discount_amount = $tax_free_current_sale_total;
            }
        }
		if($opr_tax_inclusive=='inclusive'){
            $tax_free_current_sale_total = $current_sale_total + $opr_totalTax;
            if($tax_free_current_sale_total < $wc_points_rewards_cart_max_discount_amount)
            {
                $wc_points_rewards_cart_max_discount_amount = $tax_free_current_sale_total;
            }
        }
        
        $wc_points_rewards_cart_max_discount_points = WC_Points_Rewards_Manager::calculate_points_for_discount( $wc_points_rewards_cart_max_discount_amount );
        if ( $wc_points_rewards_redeeming_points != $get_users_points_value ) {
            $get_users_points_value = $wc_points_rewards_redeeming_points;
            $get_users_points_redeem_for_sale = WC_Points_Rewards_Manager::calculate_points_for_discount( $get_users_points_value );
        }

        //Set the discount code to the current user ID + the current time in YYYY_MM_DD_H_M format
        $discount_code = sprintf( 'wc_points_redemption_%s_%s', $customer_id, gmdate( 'Y_m_d_h_i', current_time( 'timestamp' ) ) );
        $opr_minimum_points_discount = get_option( 'wc_points_rewards_cart_min_discount', '' );
        $opr_maximum_points_discount = get_option( 'wc_points_rewards_cart_max_discount');
        $opr_points_rewards_max_discount = get_option( 'wc_points_rewards_max_discount' );
        if(empty($opr_points_rewards_max_discount)){
            $opr_points_rewards_max_discount = 0;
        }
		else
		{
			$wc_points_rewards_cart_max_discount_points=$get_users_points_redeem_for_sale;
		}
        if(empty($opr_minimum_points_discount)){
            $opr_minimum_points_discount = 0;
        }
        if(empty($opr_maximum_points_discount)){
            $opr_maximum_points_discount = 9999999;
        }

        $opr_tax_inclusive = get_option( 'wc_points_rewards_points_tax_application');
        $partial_redemption = get_option( 'wc_points_rewards_partial_redemption_enabled');
		$data = array(
            'opr_tax_inclusive' => $opr_tax_inclusive,
            'opr_totalTax' => $opr_totalTax,
            'customer_name' => $customer_name,
            'customer_id' => $customer_id,
            'current_sale_total' => $current_sale_total,
            'points_earned_this_sale' => $points_earned_this_sale,
            'current_points' => $current_points,
            'current_points_monetary_value' => $this->opr_api_points_to_monetary_value( $current_points ),
            'currency_value_of_points' => $currency_value_of_points,
            'discount_code' => $discount_code,
            'get_users_points_value' => $get_users_points_value,
            'get_users_points_redeem_for_sale' => $get_users_points_redeem_for_sale,
            'wc_points_rewards_redeeming_points' => $wc_points_rewards_redeeming_points,
            'wc_points_rewards_cart_min_discount_points' => $wc_points_rewards_cart_min_discount_points,
            'wc_points_rewards_cart_max_discount_points' => $wc_points_rewards_cart_max_discount_points,
            'opr_points_value' => $opr_points_value,
            'opr_monetary_value' => $opr_monetary_value,
            'opr_rounding_option' => get_option( 'wc_points_rewards_earn_points_rounding' ),
            'opr_minimum_points_discount' => $opr_minimum_points_discount,
            'opr_maximum_points_discount' => $opr_maximum_points_discount,
            'opr_points_rewards_max_discount' => $opr_points_rewards_max_discount,
			'partial_redemption' => $partial_redemption
        );
		return $data;
    }

    /**
     * Returns the maximum possible discount available given the total amount of points the customer has
     *
     * @since 1.0.0
     * @return int|float discount points
     */
    public function opr_api_get_discount_calculations( $applying = true, $existing_discount_amounts = null, $for_display = false, $opr_user_id = 0, $opr_get_users_points_value = 0, $opr_get_cart = array(), $opr_subtotal = 0, $users_already_redeemed_points = 0) {
        // Get the value of the user's point balance
        $available_user_discount = WC_Points_Rewards_Manager::get_users_points_value( $opr_user_id ) - $this->opr_api_points_to_monetary_value( $users_already_redeemed_points );

        $discount_applied = 0;

        // No discount
        if ( $available_user_discount <= 0 ) {
            $discount_applied = 0;
        }

        // Limit the discount available by the global minimum discount if set.
        $minimum_discount = get_option( 'wc_points_rewards_cart_min_discount', '' );
        if ( $minimum_discount > $available_user_discount ) {
            $discount_applied = 0;
        }

        /*
         * Calculate the discount to be applied by iterating through each item in the cart and calculating the individual
         * maximum discount available.
         */
		 
		 
        foreach ( $opr_get_cart as $item ) {
            $wc_get_product = wc_get_product( $item->product_id );

            $discount     = 0;
            $max_discount = WC_Points_Rewards_Product::get_maximum_points_discount_for_product( $wc_get_product );

            if ( is_numeric( $max_discount ) ) {

                // Adjust the max discount by the quantity being ordered
                $max_discount *= $item->quantity;

                // If the discount available is greater than the max discount, apply the max discount
                $discount = ( $available_user_discount <= $max_discount ) ? $available_user_discount : $max_discount;
            } else {
                /*
                 * Only exclude taxes when configured to in settings and when generating a discount amount for displaying in
                 * the checkout message. This makes the actual discount money amount always tax inclusive.
                 */
                if ( 'exclusive' === get_option( 'wc_points_rewards_points_tax_application', wc_prices_include_tax() ? 'inclusive' : 'exclusive' ) && $for_display ) {
                    if ( function_exists( 'wc_get_price_excluding_tax' ) ) {
                        $max_discount = wc_get_price_excluding_tax( $wc_get_product, array( 'qty' => $item->quantity ) );
                    } elseif ( method_exists( $wc_get_product, 'get_price_excluding_tax' ) ) {
                        $max_discount = $wc_get_product->get_price_excluding_tax( $item->quantity );
                    } else {
                        $max_discount = $wc_get_product->get_price( 'edit' ) * $item->quantity;
                    }
                } else {
                    if ( function_exists( 'wc_get_price_including_tax' ) ) {
                        $max_discount = wc_get_price_including_tax( $wc_get_product, array( 'qty' => $item->quantity ) );
                    } elseif ( method_exists( $wc_get_product, 'get_price_including_tax' ) ) {
                        $max_discount = $wc_get_product->get_price_including_tax( $item->quantity );
                    } else {
                        $max_discount = $wc_get_product->get_price( 'edit' ) * $item->quantity;
                    }
                }

                // If the discount available is greater than the max discount, apply the max discount
                $discount = ( $available_user_discount <= $max_discount ) ? $available_user_discount : $max_discount;
            }

            // Add the discount to the amount to be applied
            $discount_applied += $discount;

            // Reduce the remaining discount available to be applied
            $available_user_discount -= $discount;
        }

        // Limit the discount available by the global maximum discount if set
        $max_discount = get_option( 'wc_points_rewards_cart_max_discount' );

        if ( false !== strpos( $max_discount, '%' ) ) {
            $max_discount = $this->opr_api_calculate_discount_modifier( $max_discount, $opr_subtotal );
        }

        if ( $max_discount && $max_discount < $discount_applied ) {
            $discount_applied = $max_discount;
        }
        return array(
            'discount_applied'  =>  (float) $discount_applied,
            'minimum_discount'  =>  (float) $minimum_discount,
            'max_discount'      =>  (float) $max_discount
        );
    }

    /**
     * Calculate the maximum points discount when it's set to a percentage by multiplying the percentage times the cart's price
     *
     * @since 1.0
     * @param string $percentage the percentage to multiply the price by
     * @return float the maximum discount after adjusting for the percentage
     */
    public function opr_api_calculate_discount_modifier( $percentage, $amount ) {

        $percentage = str_replace( '%', '', $percentage ) / 100;
        return $percentage * $amount;
    }

    /**
     * Calculate the value of the points earned for a purchase based on the given amount. This uses the ratio set in the admin settings (e.g. For every 100 points get a $1 discount). The points value is formatted to 2 decimal places.
     *
     * @since 1.0
     * @param int $amount the amount of points to calculate the monetary value for
     * @return float the monetary value of the points
     */
    public function opr_api_points_to_monetary_value( $c_points ) {

        list( $points, $monetary_value ) = explode( ':', get_option( 'wc_points_rewards_redeem_points_ratio', '' ) );

        return ( $c_points / $points ) * $monetary_value;
    }

    /**
     * Check request token is valid or not.
     *
     * @since 1.0.0
     * @param array Request header parameter
     * @return bool Returns true if valid otherwise false
     */
    public function rest_authentication( $request ) {
        $params = $request->get_headers();
		return true;
    }
}
