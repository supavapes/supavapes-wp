<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if someone accessed directly.
}

/**
 * Class opr_points_rewards_manager
 * Description manage all operation of plugin
 */

class opr_points_rewards_manager {
	// Hold opr_points_rewards class data
	public $opr_points_rewards;

	// Hold opr_points_rewards_core data
	public $opr_points_rewards_core;

	// OPR_Points_Rewards_Customer data
	public $opr_points_rewards_customer;

	// OPR_Points_Rewards_Setting_Page data
	public $opr_points_rewards_setting_page;

	// Hold OPR_Points_Rewards_Setting data
	public $opr_points_rewards_setting;

	// Hold API/OPR_Points_Rewards_Setting data
	public $opr_points_rewards_setting_api;

	/**
	 * Class construct
	 *
	 * @since 1.0.0
	 * @param object $opr_points_rewards opr_points_rewards
	 * @return void
	 */
	public function __construct( $opr_points_rewards ) {

		$this->opr_points_rewards = $opr_points_rewards;
		// Add actions
		$this->opr_manager_actions();

		// Include files
		$this->opr_manager_includes();

		// Create instance
		$this->opr_manager_create_instance();
	}

	/**
	 * Add actions
	 *
	 * @since 1.0.0
	 * @return void void
	 */
	public function opr_manager_actions() {
		// runs while plugin activate
		register_activation_hook($this->opr_points_rewards->get_opr_plugin_file(), array($this, 'opr_manager_activate'));
		// runs while plugin deactivate
		register_deactivation_hook($this->opr_points_rewards->get_opr_plugin_file(), array($this, 'opr_manager_deactivate'));
	}

	/**
	 * Perform operation while time of plugin activation
	 *
	 * @since 1.0.0
	 * @return void void
	 */
	public function opr_manager_activate() {
		// Check if network admin
		if ( is_network_admin() ) {
			wp_die( esc_html__('This plugin can only be activated within each individual site. <br><a href="' . network_admin_url( 'plugins.php' ) . '">&laquo; Return to Plugins</a>', OPR_PAGE_SLUG) );
			exit;
		}
        // check oliver pos and woocommerce-points-and-rewards plugins active or not
		if (! is_plugin_active('oliver-pos/oliver-pos.php') && ! is_plugin_active('woocommerce-points-and-rewards/woocommerce-points-and-rewards.php')) {

			wp_die('Oops!! <br> Please install and activate these Plugins. <br>
			. WooCommerce Points and Rewards<br> .Oliver POS <br>
			<a href="' . admin_url( 'plugins.php' ) . '">&laquo; Back to plugins</a>');
			exit;
		}
		elseif(! is_plugin_active('oliver-pos/oliver-pos.php')) {
			
			wp_die('Oops!! <br> Please install and activate these Plugins. <br>
			. Oliver POS <br>
			<a href="' . admin_url( 'plugins.php' ) . '">&laquo; Back to plugins</a>');
			exit;
		}
		elseif(! is_plugin_active('woocommerce-points-and-rewards/woocommerce-points-and-rewards.php')) {
			
			wp_die('Oops!! <br> Please install and activate these Plugins. <br>
			. WooCommerce Points and Rewards <br>
			<a href="' . admin_url( 'plugins.php' ) . '">&laquo; Back to plugins</a>');
			exit;
		}
		elseif(is_plugin_active('oliver-pos/oliver-pos.php')){
			$oliver_pos_plugin_dir = WP_PLUGIN_DIR . '/oliver-pos/oliver-pos.php';
    		$oliver_data = get_plugin_data( $oliver_pos_plugin_dir );
			if ( version_compare( $oliver_data['Version'], '2.4.1.7', '<' ) ) {
				wp_die('Oops!! <br> Please update oliver pos minimum supportive version 2.4.1.7 for points and rewards plugin. <br>
					<a href="' . admin_url( 'plugins.php' ) . '">&laquo; Back to plugins</a>');
					exit;
			}
        }
		// Call opr_manger_activate options
		$this->opr_manager_activate_options();
		// Change In httaccess file
		$this->opr_enable_x_frame();
		// Call Opr_manger_plugin_activation_trigger
		$this->opr_manager_plugin_activation_trigger();
	}

	/**
	 * Perform operation while time of plugin deactivation
	 *
	 * @since 1.0.0
	 * @return void void
	 */
	public function opr_manager_deactivate() {
		// call plugin_deactivation_trigger
		$this->opr_manager_plugin_deactivation_trigger();
		// call deactivate options
		$this->opr_manager_deactivate_options();
        //$page = get_page_by_title(OPR_PAGE_NAME);
        $page_ids = get_posts([
			'post_title' => OPR_PAGE_NAME,
			'post_status' => 'private',
			'post_type' => 'page',
			'fields'    => 'ids',  
		]);
		if(!empty($page_ids)){
			foreach($page_ids as $page_id){
				wp_delete_post($page_id ,true);
			}
		}
	}

	/**
	 * Add option while plugin activate
	 *
	 * @since 1.0.0
	 * @return void void 
	 */
	public function opr_manager_activate_options() {
		
		// add date time when plugin active
		add_option('op_points_rewards_activation_date', gmdate('Y-m-d H:i:s'));

		add_option('op_points_rewards_extenstion_origin_url', OPR_REGISTER_URL);
		// set plugin extenstion server url
		add_option('op_points_rewards_extenstion_server_url', OPR_SERVER_URL);
        
        // set plugin code
        add_option('op_points_rewards_code', OPR_PAGE_SLUG);
        
		// set plugin extenstion name
		add_option('op_points_rewards_extenstion_name', 'Points and Rewards');

		// set plugin extenstion url
		add_option('op_points_rewards_extenstion_url', OPR_PAGE_SLUG.'/?opr-extension-page');

		// set plugin extenstion id
		add_option('op_points_rewards_extenstion_id', md5('opr_' . home_url()));
	}

	/**
	 * Fire trigger while plugin deactivate
	 *
	 * @since 1.0.0
	 * @return void void
	 */
	public function opr_manager_plugin_activation_trigger( $value = '' ) {
        do_action( 'oliver_points_and_rewards_activate_plugin' );
	}
	/**
	 * Dwlwtw option while plugin deactivate
	 *
	 * @since 1.0.0
	 * @return void void
	 */
	public function opr_manager_deactivate_options() {
		// Delete plugin options
		delete_option('op_points_rewards_activation_date');
		delete_option('op_points_rewards_extenstion_origin_url');
		delete_option('op_points_rewards_extenstion_server_url');
		delete_option('op_points_rewards_extenstion_name');
		delete_option('op_points_rewards_extenstion_url');
		delete_option('op_points_rewards_extenstion_id');
	}

	/**
	 * Fire trigger while plugin deactivate
	 *
	 * @since 1.0.0
	 * @return void void
	 */
	public function opr_manager_plugin_deactivation_trigger( $value = '' ) {
        do_action( 'oliver_points_and_rewards_deactivate_plugin' );
    }

	/**
	 * Include files
	 *
	 * @since 1.0.0
	 * @return void void
	 */
	public function opr_manager_includes() {
		// Core class
		require_once( dirname( __FILE__ ) . '/core/classes/class-op-points-rewards-core.php' );

		// Core class
		require_once( dirname( __FILE__ ) . '/core/templates/class-op-points-rewards-extension-page.php' );

		// Core function
		require_once( dirname( __FILE__ ) . '/core/functions/op-points-rewards-core.php' );

		// API setting class
		require_once( dirname( __FILE__ ) . '/api/class-op-points-rewards-api.php' );
	}

	/**
	 * Create instance of included class files
	 *
	 * @since 1.0.0
	 * @return void void
	 */
	public function opr_manager_create_instance() {
		// Core class
		$this->opr_points_rewards_core   =   new opr_points_rewards_core($this);

		// Api setting class
		$this->opr_points_rewards_api	=   new OPR_API\OPR_Points_Rewards_Api($this);
	}

	/**
	 * Create instance of included class files
	 *
	 * @since 1.0.0
	 * @return void void
	 */
	public function opr_manager_render_extension_page() {
		// Core class
		require_once( dirname( __FILE__ ) . '/core/templates/class-op-points-rewards-extension-page.php' );

		// Core class
		$this->opr_points_rewards_core = new opr_points_rewards_core($this);
	}
	public function opr_enable_x_frame(){
		$home_path = get_home_path();
		$site_url = get_site_url();
		$htaccess_location = $home_path . '.htaccess';
		$content = array(
			'Header always unset X-Frame-Options
			<IfModule mod_headers.c>
			  <FilesMatch "\.(php|html)$">
				Header set Content-Security-Policy "frame-ancestors https://hub.oliverpos.com https://hub1.oliverpos.com https://sell.oliverpos.com https://sell1.oliverpos.com'.$site_url.'"
			  </FilesMatch>
			</IfModule>'
		);
		insert_with_markers( $htaccess_location, 'Oliver points and rewards', $content );
	}

}
