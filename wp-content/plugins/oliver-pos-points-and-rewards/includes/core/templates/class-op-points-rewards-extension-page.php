<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if someone accessed directly.
}

/**
 * Class OPR_Points_Rewards_Extension_Page
 * Description manage extension page of plugin
 */
class OPR_Points_Rewards_Extension_Page {

    //Op points and rewards manager
    private $opr_points_rewards_manager;

    /**
     * Class construct
     *
     * @since 1.0.0
     * @param object $opr_points_rewards opr_points_rewards
     * @return void void
     */
    public function __construct( $opr_points_rewards_manager = null ) {
        $this->opr_points_rewards_manager = $opr_points_rewards_manager;

        //Call actions
        //$this->actions();

        //Call render_style
        $this->opr_extension_render_style();

        //Call render_html
        $this->opr_extension_render_html();

        //Call render_script
        $this->opr_extension_render_script();

    }
	/**
     * Render style
     *
     * @since 1.0.0
     * @return void void
     */
	
    public function opr_extension_render_style( $var = null ) {
       ?>
		<style>
		
		body{
		background-color:#ffffff;
		}
        .opr_pfRewards_container * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
      }

      .opr_pfRewards_container {
        font-family: Poppins, sans-serif;
        padding: 24px;
        display: flex;
        flex-direction: column;
        gap: 16px;
        border-radius: 8px;
        max-width: 480px;
        border: 1px solid #d4dee5;
        margin: 24px auto;
        color: #3d4c66;
        font-size: 16px;
      }

      .opr_pfRewards_flex {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        height: 85px;
        width: 100%;
        overflow: hidden;
      }
      .opr_pfRewards {
        background-color: #f8fafc;
        border-radius: 8px;
        text-align: left;
        margin-bottom: 0;
        height: auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-direction: row;
        padding: 12px;
      }

      .opr_pfRewards h3 {
        font-size: 16px;
        line-height: normal;
        font-weight: 500;
        letter-spacing: normal;
      }

      .opr_pfRewards h1 {
        font-size: 16px;
        line-height: normal;
        font-weight: 400;
        letter-spacing: normal;
      }

      .opr_pfRewards h3,
      .opr_pfRewards h1 {
        margin: 0px;
        color: inherit;
      }

      .opr_pfRewards_hr {
        border: 1px solid #d4dee5;
        width: 100%;
        margin-top: 0px;
        margin-bottom: 0px;
        display: none;
      }

      #opr_current_points_applied, #opr_points_update {
        border: 1px solid #d4dee5;
        border-radius: 6px;
        font-size: inherit;
        padding: 6px;
        width: 84px;
        text-align: right;
        color: inherit;
        font-family: inherit;
        background-color: #ffffff;
      }

      #opr_current_points_applied:focus, #opr_points_update:focus {
        outline: none;
        outline-offset: 0px;
        border-color: #2797e8;
      }

      .opr_pfRewards_button {
        background-color: #2797e8;
        border: 1px solid #2797e8;
        color: #fff;
        border-radius: 8px;
        width: 100%;
        font-size: 18px;
        font-weight: 500;
        cursor: pointer;
        height: auto;
        font-family: Poppins;
        padding: 12px;
      }

      .opr_pfRewards_button:focus {
        outline: 0px;
        outline-offset: 0px;
      }

      .opr_pfRewards_button:hover {
        background-color: hsl(205, 71%, 53%);
      }

      .opr_pfRewards_button:active {
        scale: 0.95;
      }

      .opr_pfRewards_button_enable {
        background-color: transparent;
        border: 1px solid #d4dee5;
        color: #3d4c66;
        border-radius: 8px;
        width: 100%;
        font-size: 18px;
        font-weight: 500;
        cursor: pointer;
        height: auto;
        font-family: Poppins;
        padding: 12px;
      }

      .opr_pfRewards_button_enable:hover {
        background-color: #f8fafc;
      }

      .opr_pfRewards_button_enable:active {
        scale: 0.95;
      }

      .opr_pfRewards_button_disabled {
        pointer-events: none;
        opacity: 0.5;
      }

      input:focus {
        outline: none;
        outline-offset: 0px;
      }

      input[type="number"]::-webkit-outer-spin-button,
      input[type="number"]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
      }

      input[type="number"] {
        -moz-appearance: textfield;
      }
	  #opr_display_error{
            color: #ff525f;
            text-align: left;
            font-size: 14px;
            margin-bottom: 12px;
		}
		.opr_pfRewards.opr_pf_update_flex #opr_points_update{
			width:68%;
			padding:8px;
			text-align:left;
		}
		
		input::placeholder{
			opacity:0.4;
		}
		.opr_pfRewards.opr_pf_update_flex button{
			width:30%;
		    padding:8px;
			font-size:16px;
		}
		.opr_pfRewards_form , .opr_redeem_container{
			display: flex;
			flex-direction: column;
			gap: 16px;
		}
		#opr_redeem_container, #opr_pfRewards_form, #opr_pfRewards_container{
			display: none;
		}

			</style>
		<?php
    }

    /**
     * Render html
     *
     * @since 1.0.0
     * @return void void
     */
    public function opr_extension_render_html( $var = null ) {
		if (isset($_POST['submit_points'])){
			global $wc_points_rewards, $wpdb;
			$user_id = $_POST['customer_id'];
			$points_balance = $_POST['update_points'];
			$points_change = 0;
			// get old points of user
			$table = $wpdb->prefix . "wc_points_rewards_user_points";
			$query = "SELECT * FROM $table WHERE user_id = %d AND points_balance != 0 ORDER BY date ASC";
			$points = $wpdb->get_results( $wpdb->prepare( $query, $user_id ) );

			// no non-zero records, so create a new one
			if ( empty( $points ) && 0 != $points_balance ) {
				$points_change = $points_balance;
				$wpdb->insert(
					$table,
					array(
						'user_id'        => $user_id,
						'points'         => $points_balance,
						'points_balance' => $points_balance,
						'date'           => current_time( 'mysql', 1 ),
					),
					array(
						'%d',
						'%d',
						'%d',
						'%s',
					)
				);
			} elseif ( count( $points ) > 0 ) {  // existing non-zero points records
				$total_points_balance = 0;
				// total up the existing points balance
				foreach ( $points as $_points ) {
					$total_points_balance += $_points->points_balance;
				}
				if ( $total_points_balance != $points_balance ) {
					// get the difference (the amount required to make the users points balance equal to the new balance)
					$points_change = $points_difference = $points_balance - $total_points_balance;
					// the goal is to get each existing record as close to zero as possible, oldest to newest
					foreach ( $points as $index => &$_points ) {
						if ( $_points->points_balance < 0 && $points_difference > 0 ) {
							$_points->points_balance += $points_difference;
							if ( $_points->points_balance <= 0 || count( $points ) - 1 == $index ) {
								// used up all of points_difference, or reached the newest user points record which therefore receives the remaining balance
								$points_difference = 0;
								break;
							} else {
								// still have more points balance to distribute
								$points_difference = $_points->points_balance;
								$_points->points_balance = 0;
							}
						} elseif ( $_points->points_balance > 0 && $points_difference < 0 ) {
							$_points->points_balance += $points_difference;
							if ( $_points->points_balance >= 0 || count( $points ) - 1 == $index ) {
								// used up all of points_difference, or reached the newest user points record which therefore receives the remaining balance
								$points_difference = 0;
								break;
							} else {
								// still have more points balance to distribute
								$points_difference = $_points->points_balance;
								$_points->points_balance = 0;
							}
						} elseif ( count( $points ) - 1 == $index && 0 != $points_difference ) {
							// if we made it here, assign all remaining points to the final record and we're done
							$_points->points_balance += $points_difference;
							$points_difference = 0;
						}
					}
					// update any affected rows
					for ( $i = 0; $i <= $index; $i++ ) {
						$wpdb->update(
							"wp_wc_points_rewards_user_points",
							array(
								'points_balance' => $points[ $i ]->points_balance,
							),
							array(
								'id' => $points[ $i ]->id,
							),
							array( '%d' ),
							array( '%d' )
						);
					}
				}
			}
		}
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <meta http-equiv="X-UA-Compatible" content="ie=edge">
            <meta http-Equiv="Cache-Control" Content="no-cache" />
            <meta http-Equiv="Pragma" Content="no-cache" />
            <meta http-Equiv="Expires" Content="0" />
			<title>oliver points and rewards</title>
			<!-- Added Poppins Font -->
			<link rel="preconnect" href="https://fonts.googleapis.com" />
			<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
			<link
			  href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
			  rel="stylesheet"
			/>
        </head>
        <body>
        <div class="opr_content">
            <div class="opr_pfRewards_container" id="opr_pfRewards_container">
				<div class="opr_redeem_container" id="opr_redeem_container">
					<div class="opr_pfRewards opr_pfRewards_flex">
						<h3>Customer Name</h3>
						<h1 id="opr_customer_name"></h1>
					</div>
					<div class="opr_pfRewards opr_pfRewards_flex">
						<h3>Points Earned This Sale</h3>
						<h1 id="opr_points_earned_this_sale"></h1>
					</div>
					<div class="opr_pfRewards opr_pfRewards_flex">
						<h3 id="opr_current_points_heading">Current Points</h3>
						<h1 id="opr_current_points"></h1>
					</div>
					 <div class="opr_pfRewards opr_pfRewards_flex">
						<h3>Currency Value of Points</h3>
						<h1 id="opr_currency_value_of_points"></h1>
					</div>
					<hr class="opr_pfRewards_hr" />
					<div class="opr_pfRewards opr_pfRewards_flex">
						<h3>Points to Redeem</h3>
						<input type="number" id="opr_current_points_applied" min="0" max="0" value="0">
					</div>
					<div id="opr_discount_code" style="display: none;"></div>
					<div id="opr_maximum_points_discount" style="display: none;"></div>
					<div id="opr_minimum_points_discount" style="display: none;"></div>
					<div id="opr_current_cart_total" style="display: none;"></div>
					<div id="opr_get_users_points_value" style="display: none;"></div>
					<div id="opr_get_users_points_redeem_for_sale" style="display: none;"></div>
					<div id="opr_get_rewards_cart_max_discount_points" style="display: none;"></div>
					<div id="opr_points_rewards_max_discount" style="display: none;"></div>
					<div id="opr_totalTax" style="display: none;"></div>
					<div id="opr_tax_inclusive" style="display: none;"></div>
					<div id="opr_display_error"></div>
					<button class="opr_pfRewards_button opr_pfRewards_flex" id="opr_redeem_points_button">Redeem Points</button>
					<button class="opr_pfRewards_button_disabled opr_pfRewards_button_enable" id="opr_cancel_redeemed_points_button" onclick="opr_cancel_redeemed_points_button()">Cancel Redemtion</button>
				</div>
				<form action="#" method="post" class="opr_pfRewards_form" id="opr_pfRewards_form">
					<div class="opr_pfRewards opr_pfRewards_flex">
						<h3>Customer Name</h3>
						<h1 id="opr_customer_name_update"></h1>
					</div>
					<div class="opr_pfRewards opr_pfRewards_flex">
						<h3>Current Points</h3>
						<h1 id="opr_current_points_update"></h1>
					</div>
					<div class="opr_pfRewards opr_pf_update_flex">
						<input type="number" placeholder="Enter points to update" id="opr_points_update" value="" name="update_points" required>
						<input type="hidden"  value="" name="customer_id" id="opr_customer_id">
						<button type="submit" name="submit_points" class="opr_pfRewards_button opr_pfRewards_flex" id="opr_update_points_button">Update</button>
					</div>
				</form>
            </div>
        </div>
        </body>
        </html>
        <?php
    }

    /**
     * Create script
     *
     * @since 1.0.0
     * @return void void
     */
    public function opr_extension_render_script( $var = null ) {
        ?>
        <script>
            callredeemPoints();
			callAppReady();
            var oprOliverExtensionTargetOrigin = "<?php echo esc_html__( get_option('op_points_rewards_extenstion_origin_url'), OPR_PAGE_SLUG ); ?>";
            var oprRedeemPoints = 0;
            function callredeemPoints(){
                var RedeemPoints = {
                    command: 'redeemPoints',
                    version:"2.0",
                    method: 'post'
                }
                window.parent.postMessage(JSON.stringify(RedeemPoints 	), '*');
		    }
			function callAppReady(){
                var AppReady = {
                    command: 'appReady',
                    version:"1.0",
                    method: 'get'
                }
                window.parent.postMessage(JSON.stringify(AppReady 	), '*');
		    }
            function callCartValue(){
                var CartReq = {
                    command: 'CartValue',
                    version:"1.0",
                    method: 'get'
                }
                window.parent.postMessage(JSON.stringify(CartReq 	), '*');
		    }
            function callCustomerInSale(){
                var CustomerInSaleReq = {
                    command: 'CustomerInSale',
                    version:"1.0",
                    method: 'get'
                }
                window.parent.postMessage(JSON.stringify(CustomerInSaleReq 	), '*');
            }
            function callCustomerDetails(){
                var CustomerDetails = {
                    command: 'CustomerDetails',
                    version:"1.0",
                    method: 'get'
                }
                window.parent.postMessage(JSON.stringify(CustomerDetails 	), '*');
            }
            function callCart(){
                var CustomerInSaleReq = {
                    command: 'Cart',
                    version:"1.0",
                    method: 'get'
                }
                window.parent.postMessage(JSON.stringify(CustomerInSaleReq 	), '*');
            }
            /**
             * This function run on page load
             *
             * @since 1.0.0
             * @return void
             */
            window.addEventListener('load', (event) => {
                // invoke the payment toggle function
                oprToggleExtensionReady();
            });

            /**
             * Received messages send by origin
             *
             * @since 1.0.0
             * @return void
             */
            jsonObj = [];
            var sub_total;
            var total_tax;
            var email;
            var productids = [];
            window.addEventListener('message', function(e) {
                const msg = JSON.parse(e.data);
                if (( msg.oliverpos && msg.oliverpos.command === "redeemPoints" ) || msg.command === "redeemPoints") 
                {
                    if(msg.status==403){
                        document.getElementById("opr_display_error").innerHTML = msg.error;
                        document.getElementById('opr_redeem_points_button').classList.add("opr_pfRewards_button_disabled");
                        return false;
                    } 
                }
                if (( msg.oliverpos && msg.oliverpos.command === "appReady" ) || msg.command === "appReady") 
                {
                    if(msg.data.view=='CheckoutView'){
						callCart();
						callCustomerInSale();
						callCustomerDetails();
						callCartValue();
                        document.getElementById("opr_pfRewards_container").style.display = "flex";
						document.getElementById("opr_pfRewards_form").style.display = "none";
						document.getElementById("opr_redeem_container").style.display = "flex";
					}
                    //Update points
					if(msg.data.view=='CustomerView'){
						let email = msg.data.CustomerEmail;
                        document.getElementById("opr_pfRewards_container").style.display = "flex";
						document.getElementById("opr_pfRewards_form").style.display = "flex";
						document.getElementById("opr_redeem_container").style.display = "none";
						var customer = { email:email};
						oprUpdatePoints(JSON.stringify(customer));
					}  
                }
                
                if (( msg.oliverpos && msg.oliverpos.command === "CustomerInSale" ) || msg.command === "CustomerInSale") 
                {
                   email = msg.data.email;
				}
                if (( msg.oliverpos && msg.oliverpos.command === "Cart" ) || msg.command === "Cart") 
                {
                    if(msg.data.items != null){
                        msg.data.items.forEach(function (val, index, theArray) {
                            if (val.product_id) {
                                    productids.push({'product_id':val.product_id,'quantity':val.quantity});
                                }   
                            }); 
                    }
                }
				if (( msg.oliverpos && msg.oliverpos.command === "CartValue" ) || msg.command === "CartValue") 
                {
                    var sub_total = msg.data.sub_total;
                    var total_tax = msg.data.total_tax;
                    var customer = { products:productids, sub_total:sub_total, email:email, totalTax:total_tax};
					oprGetPoints(JSON.stringify(customer));                           
                }
                
            }, false);

            /**
             * Bind DOM events
             *
             * @since 1.0.0
             * @return void void
             */
            function bindEvent(element, eventName, eventHandler) {
                element.addEventListener(eventName, eventHandler, false);
            }

            /**
             * Send a message to the parent
             *
             * @since 1.0.0
             * @return void void
             */
            var sendMessage = function (msg) {
                window.parent.postMessage(msg, '*');
            };
			var oprUpdatePoints = function(requestData){
                let oprXhttp1 = new XMLHttpRequest();
                let oprHomeUrl = `<?php echo esc_url( home_url('wp-json/'.OPR_PAGE_SLUG.'/user-point') ); ?>`;
                oprXhttp1.open("POST", `${oprHomeUrl}`, true);
                //Send the proper header information along with the request
                oprXhttp1.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                oprXhttp1.send(`oprRequestData=${requestData}`);

                oprXhttp1.onreadystatechange = (e) => {
                    if (typeof oprXhttp1.responseText !== "undefined" && oprXhttp1.responseText!=='') {
                        let oprResponse = JSON.parse( oprXhttp1.responseText );
                        document.getElementById("opr_customer_id").value = oprResponse.customer_id;
                        document.getElementById("opr_current_points_update").innerHTML = oprResponse.current_points;
                        document.getElementById("opr_points_update").value = oprResponse.current_points;
                        document.getElementById("opr_customer_name_update").innerHTML = oprResponse.customer_name;
					
		            }
				}
            }
            /**
             * Collect all details of points for the sale and customer
             *
             * @since 1.0.0
             * @return void void
             */
            var oprGetPoints = function(requestData){
                let oprXhttp = new XMLHttpRequest();
                let oprHomeUrl = `<?php echo esc_url( home_url('wp-json/'.OPR_PAGE_SLUG.'/get-points') ); ?>`;
                oprXhttp.open("POST", `${oprHomeUrl}`, true);
                //Send the proper header information along with the request
                oprXhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                oprXhttp.send(`oprRequestData=${requestData}`);

                oprXhttp.onreadystatechange = (e) => {
                    if (typeof oprXhttp.responseText !== "undefined" && oprXhttp.responseText!=='') {
                        let oprResponse = JSON.parse( oprXhttp.responseText );
						if(oprResponse.partial_redemption=='no'){
							document.getElementById('opr_current_points_applied').readOnly = true;
						}
						document.getElementById("opr_customer_name").innerHTML = oprResponse.customer_name;
						document.getElementById("opr_points_earned_this_sale").innerHTML = oprResponse.points_earned_this_sale;
                        document.getElementById("opr_current_points").innerHTML = oprResponse.current_points;
                        document.getElementById("opr_current_points").setAttribute("data-current-points", `${oprResponse.current_points}`);
                        document.getElementById("opr_currency_value_of_points").innerHTML = oprResponse.current_points_monetary_value;
                        document.getElementById("opr_discount_code").setAttribute("data-code", `${oprResponse.discount_code}`);
						document.getElementById("opr_minimum_points_discount").setAttribute("minimum-points-code", `${oprResponse.opr_minimum_points_discount}`);
						document.getElementById("opr_maximum_points_discount").setAttribute("maximum-points-code", `${oprResponse.opr_maximum_points_discount}`);
                        document.getElementById("opr_current_cart_total").setAttribute("data-cart-value", `${oprResponse.current_sale_total}`);
                        document.getElementById("opr_get_users_points_value").setAttribute("data-points-value", `${oprResponse.get_users_points_value}`);
                        document.getElementById("opr_get_users_points_redeem_for_sale").setAttribute("data-points", `${oprResponse.get_users_points_redeem_for_sale}`);
						document.getElementById("opr_get_rewards_cart_max_discount_points").setAttribute("rewards_cart_max_discount", `${oprResponse.wc_points_rewards_cart_max_discount_points}`);
						document.getElementById("opr_points_rewards_max_discount").setAttribute("rewards_cart_max_discount-check", `${oprResponse.opr_points_rewards_max_discount}`);
						document.getElementById("opr_tax_inclusive").setAttribute("opr_tax_inclusive-check", `${oprResponse.opr_tax_inclusive}`);
						document.getElementById("opr_totalTax").setAttribute("opr_totalTax-check", `${oprResponse.opr_totalTax}`);

                        if ( ! oprResponse.get_users_points_redeem_for_sale) {
                            // add button disable
                            document.getElementById('opr_current_points_heading').innerHTML = "No points available";
                        }
						
						let opr_current_points_applied = document.getElementById("opr_current_points_applied");
                        opr_current_points_applied.setAttribute("min",   oprResponse.wc_points_rewards_cart_min_discount_points );
						if(parseFloat(oprResponse.current_points)<parseFloat(oprResponse.wc_points_rewards_cart_max_discount_points)){
                            opr_current_points_applied.setAttribute("max",   oprResponse.current_points );
                            opr_current_points_applied.setAttribute("value", oprResponse.current_points );
                        }
                        else{
                            opr_current_points_applied.setAttribute("value", oprResponse.wc_points_rewards_cart_max_discount_points );
                            opr_current_points_applied.setAttribute("max",   oprResponse.wc_points_rewards_cart_max_discount_points );
                        }
                        opr_current_points_applied.setAttribute("data-opr-points-value", oprResponse.opr_points_value );
                        opr_current_points_applied.setAttribute("data-opr-monetory-value", oprResponse.opr_monetary_value );
                        opr_current_points_applied.setAttribute("data-opr-prounding-option", oprResponse.opr_rounding_option );
                    }
                }
            }

            /**
             * Perform redeem operations on click of redeem points button
             *
             * @since 1.0.0
             * @return void void
             */
            var oprRedeemPointsButtom = document.getElementById('opr_redeem_points_button');
            bindEvent(oprRedeemPointsButtom, 'click', function (e) {
                let opr_current_points = document.getElementById("opr_current_points").innerHTML;
                let opr_current_points_data_value = document.getElementById("opr_current_points").getAttribute("data-current-points");
				let rewards_cart_max_discount = document.getElementById("opr_get_rewards_cart_max_discount_points").getAttribute("rewards_cart_max_discount");
                let opr_get_users_points_value = document.getElementById("opr_get_users_points_value").getAttribute("data-points-value");
                let opr_get_users_points_redeem_for_sale = document.getElementById("opr_get_users_points_redeem_for_sale").getAttribute("data-points");
                // empty error box
                document.getElementById("opr_display_error").innerHTML = " ";
                // remove button disable
                document.getElementById('opr_cancel_redeemed_points_button').classList.remove("opr_pfRewards_button_disabled");
                let opr_points_ratio = 1;
                let opr_currency_ratio = 1;
                let opr_redeem_points = document.getElementById("opr_current_points_applied").value;
                let opr_redeem_points_max_value = document.getElementById("opr_current_points_applied").getAttribute("max");
                let opr_get_cart_total = document.getElementById("opr_current_cart_total").getAttribute("data-cart-value");
                if(opr_redeem_points.length ==0){
                    document.getElementById("opr_current_points_applied").value=0;
					document.getElementById("opr_display_error").innerHTML = `Invalid Redemtion, Points should be greater than 0`;
                    return false;
				}
                opr_redeem_points = (typeof opr_redeem_points !== "undefined") ? parseFloat( opr_redeem_points ) : 0;
                opr_get_cart_total = (typeof opr_get_cart_total !== "undefined") ? parseFloat( opr_get_cart_total ) : 0;
				
				var opr_minimum_points_discount = document.getElementById("opr_minimum_points_discount").getAttribute("minimum-points-code");
				var opr_maximum_points_discount = document.getElementById("opr_maximum_points_discount").getAttribute("maximum-points-code");
				var opr_totalTax = document.getElementById("opr_totalTax").getAttribute("opr_totalTax-check");
				
				var opr_tax_inclusive = document.getElementById("opr_tax_inclusive").getAttribute("opr_tax_inclusive-check");
				let opr_redeem_points_amount = (typeof opr_get_users_points_value !== "undefined") ? parseFloat( opr_get_users_points_value ) : 0;

               if (parseFloat(opr_redeem_points) < 0 || parseFloat(opr_current_points_data_value) < 0) {
                    document.getElementById("opr_display_error").innerHTML = `Invalid Redemtion, Points should be greater than 0`;
                    return false;
                }
                 var redeem_amount = points_to_monetary_value( opr_redeem_points );
				if (parseFloat(redeem_amount) < parseFloat(opr_minimum_points_discount) ) {
                    var opr_minimum_points = monetary_to_points_value( opr_minimum_points_discount );
                    document.getElementById("opr_display_error").innerHTML = `Minimum points allowed to be deducted ${opr_minimum_points} `;
                    return false;
                }
                 if(parseFloat(opr_maximum_points_discount) !== 0){
                    if (parseFloat(redeem_amount) > parseFloat(opr_maximum_points_discount)) {
                        var opr_maximum_points = monetary_to_points_value( opr_maximum_points_discount );
                        document.getElementById("opr_display_error").innerHTML = `Maximum points allowed to be deducted ${opr_maximum_points} `;
                        return false;
                    }
                    if (parseFloat(opr_redeem_points) > parseFloat(opr_redeem_points_max_value)) {
                        document.getElementById("opr_display_error").innerHTML = `You have only ${opr_redeem_points_max_value} points for redeem`;
                        return false;
                    }
                }
				else{
                 if (parseFloat(opr_redeem_points) > parseFloat(opr_redeem_points_max_value)) {
                        document.getElementById("opr_display_error").innerHTML = `You have only ${opr_redeem_points_max_value} points for redeem`;
                        return false;
                    }
                }
                var max_redeem_amount = points_to_monetary_value(opr_redeem_points_max_value);
				//If Redeem Amount is greater then cart total
                if (redeem_amount > opr_get_cart_total) {
					if(opr_tax_inclusive == 'inclusive'){
						var redeem_amount_points = redeem_amount;
					}
					else{
						var redeem_amount_points = monetary_to_points_value( opr_get_cart_total );
						redeem_amount_points = redeem_amount_points.toFixed(2);
						var redeem_amount = points_to_monetary_value( redeem_amount_points );
					}
                     
                    opr_redeem_points = redeem_amount_points;
					document.getElementById("opr_current_points").innerHTML = parseFloat(opr_current_points) - redeem_amount_points;
					document.getElementById("opr_current_points_applied").value = 0;
                    document.getElementById('opr_redeem_points_button').classList.add("opr_pfRewards_button_disabled");
				} 
				else{
					document.getElementById("opr_current_points").innerHTML = parseFloat(opr_current_points) - opr_redeem_points;
					document.getElementById("opr_current_points_applied").value = 0;
				}
				
                document.getElementById("opr_current_points_applied").setAttribute("max", parseFloat(rewards_cart_max_discount) - opr_redeem_points);
				if((parseFloat(rewards_cart_max_discount) - opr_redeem_points)==0){
					document.getElementById('opr_redeem_points_button').classList.add("opr_pfRewards_button_disabled");
				}
				document.getElementById("opr_get_rewards_cart_max_discount_points").setAttribute("rewards_cart_max_discount", parseFloat(rewards_cart_max_discount) - opr_redeem_points);
				var new_carruncy_points = parseFloat(opr_current_points) - opr_redeem_points;
					var new_carruncy_val = points_to_monetary_value( new_carruncy_points );
					document.getElementById("opr_currency_value_of_points").innerHTML = new_carruncy_val;

				if(parseFloat(opr_maximum_points_discount) == 0){
                    document.getElementById("opr_current_points_applied").value = parseFloat(opr_redeem_points_max_value) - opr_redeem_points;
                    document.getElementById("opr_current_points_applied").setAttribute("max", parseFloat(opr_redeem_points_max_value) - opr_redeem_points);
					document.getElementById("opr_minimum_points_discount").setAttribute("minimum-points-code", 0);
					if(parseFloat(opr_redeem_points_max_value) - opr_redeem_points==0){
                         document.getElementById('opr_redeem_points_button').classList.add("opr_pfRewards_button_disabled");
						 document.getElementById("opr_display_error").innerHTML = "You have reached your limit for this order";
                    }
                }
                document.getElementById("opr_current_cart_total").setAttribute("data-cart-value", opr_get_cart_total-redeem_amount);
                var persnt=0;
                var redeem_tax_amount = 0;
                if(opr_tax_inclusive == 'exclusive'){                  
					opr_totalTax = (typeof opr_totalTax !== "undefined") ? parseFloat( opr_totalTax ) : 0;
                    opr_get_cart_total = opr_get_cart_total- opr_totalTax;
                    persnt = (opr_totalTax*100)/opr_get_cart_total;
                    newcart_totol = opr_get_cart_total- redeem_amount;
                    newdisval = (newcart_totol*persnt)/100;
                    redeem_tax_amount = opr_totalTax - newdisval;
                }
                else{
                    opr_tax_inclusive='inclusive';
                }
				var jsonMsg = {    
                    command: "redeemPoints",
                    method: "post",
					version:"3.0",    
                    data:
                        {
                            points:
                                {
                                    "amount": redeem_amount,
                                    "amount_tax": redeem_tax_amount,
                                    "opr_tax_inclusive": opr_tax_inclusive,
                                    "points": Math.abs(opr_redeem_points),
                                    "discount_code": document.getElementById("opr_discount_code").getAttribute("data-code")
                                }
                        }
                }
                sendMessage(JSON.stringify(jsonMsg));
            });

            /**
             * Cancel redeemed points and cancel discount
             *
             * @since 1.0.0
             * @return void void
             */
            var opr_cancel_redeemed_points_button = function(requestData) {
                // add button disable
                document.getElementById('opr_cancel_redeemed_points_button').classList.add("opr_pfRewards_button_disabled");
                var jsonMsg = {
                    command: "cancelRedeemedPoints",
                    method: "post",
					version:"3.0",    
                    data:
                        {
                            points:
                                {
                                  "flag": true  
                                }
                        }
                }
                sendMessage(JSON.stringify(jsonMsg));
                window.location.reload(true);
            }
             /**
             * When load send extension ready message
             *
             * @since 1.0.0
             * @return void void
             */
            var oprToggleExtensionReady = function() {
                let jsonMsg = {
                    oliverpos: {
                        "event": "extensionReady"
                    },
                }

                sendMessage(JSON.stringify(jsonMsg));
            }

            /**
             * Round the points using merchant selected method.
             *
             * @since 1.6.16
             * @param float $points That will be rounded.
             * @param string $rounding_option That will be rounding option.
             *
             * @return int $points Points after rounding.
             */
            var opr_round_the_points = function( points ) {
                let rounding_option = document.getElementById("opr_current_points_applied").getAttribute("data-opr-prounding-option");
                switch ( rounding_option ) {
                    case 'ceil':
                        points_earned = Math.ceil( points );
                        break;
                    case 'floor':
                        points_earned = Math.floor( points );
                        break;
                    default:
                        points_earned = Math.round( points );
                        break;
                }
                return points_earned;
            }

            /**
             * Calculate the value of the points earned for a purchase based on the given amount. This uses the ratio set in the
             * admin settings (e.g. For every 100 points get a $1 discount). The points value is formatted to 2 decimal places.
             *
             * @since 1.0
             * @param int $amount the amount of points to calculate the monetary value for
             * @return float the monetary value of the points
             */
            var points_to_monetary_value = function( points ) {
                let opr_current_points_applied = document.getElementById("opr_current_points_applied");
                let opr_points_value    =   opr_current_points_applied.getAttribute("data-opr-points-value");
                let opr_monetary_value  =   opr_current_points_applied.getAttribute("data-opr-monetory-value");
                var checkval =  ( parseFloat(points) / parseFloat(opr_points_value) ) * parseFloat(opr_monetary_value);
                return checkval;
            }
            var monetary_to_points_value = function( redeem_amount ) {
                let opr_current_points_applied = document.getElementById("opr_current_points_applied");
                let opr_points_value    =   opr_current_points_applied.getAttribute("data-opr-points-value");
                let opr_monetary_value  =   opr_current_points_applied.getAttribute("data-opr-monetory-value");
                var checkpoints =  ( parseFloat(redeem_amount) * parseFloat(opr_points_value) ) / parseFloat(opr_monetary_value);
                return checkpoints;
            }
        </script>
        <?php
    }
}
