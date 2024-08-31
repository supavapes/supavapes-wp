<?php
/**
 * Plugin Name:       		Oliver POS Points and Rewards
 * Description:       		Oliver POS extension plugin for Woocommerce Points and Rewards
 * Version:           		2.1.0
 * Author:            		Oliver POS
 * Author URI:        		https://oliverpos.com/
 * License:           		GPL-2.0+
 * License URI:       		http://www.gnu.org/licenses/gpl-2.0.txt
 * WC requires at least:	3.8
 * WC tested up to:			8.3.0
 * Text Domain: 			oliver-points-and-rewards
 * Domain Path: 			/languages/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if someone accessed directly.
}

define('OPR_PAGE_NAME' , 'Oliver Points and Rewards');
define('OPR_PAGE_SLUG' , 'oliver-points-and-rewards');
define('OPR_REGISTER_URL' , 'https://sell1.oliverpos.com');
define('OPR_SERVER_URL' , 'https://app.oliverpos.com/api/WCBridge');
define( 'OPR_PATH', plugin_dir_path( __FILE__ ) );
define( 'OPR_PATH_ROOT',  __FILE__ );
define( 'OPR_SAVE_EXTENTION',  "SaveExtention" );
define( 'OPR_DEACTIVATE_EXTENTION',  "DeactivateExtention" );
define( 'PLUGIN_LOGO',  "https://ps.w.org/oliver-pos/assets/icon-128x128.png" );

new Oliver_Points_Rewards();

class Oliver_Points_Rewards {

	// Hold plugin path
	public $opr_plugin_path;

	// Hold plugin url
	public $opr_plugin_url;

	// Hold plugin root file
	public $opr_plugin_file;

	// Hold opr_points_rewards class data
	public $opr_core;

	/** 
	 * Opr_endpoint for Plugin REST API's
	 *
	 * @since 1.0.0
	 * @var string the opr_endpoint / namespace page to use for frontend 
	 */
	public $opr_endpoint = OPR_PAGE_SLUG;

	public function __construct() {
		$this->opr_run(); // Call to master
	}

	/**
	 * Create theinstance of opr_points_rewards_manager
	 *
	 * @since 1.0.0
	 * @return void void
	 */
	public function opr_run() {
		require_once( dirname( __FILE__ ) . '/includes/class-op-points-rewards-manager.php' ); 
        //Create custom hook and call hook from here
        add_action('oliver_points_and_rewards_activate_plugin', array( __CLASS__,  'oliver_pos_points_and_rewards_connect_to_hub' ));
        add_action('oliver_points_and_rewards_deactivate_plugin', array( __CLASS__,  'oliver_pos_points_and_rewards_disconnect_to_hub' ));
		add_action('wp_enqueue_scripts', array( __CLASS__,  'pos_bridge_enqueue_scripts_and_styles_in_front' ));
		// Manager class
		new opr_points_rewards_manager($this);
	}

	/**
	 * Gets the absolute plugin path without a trailing slash, e.g.
	 * /path/to/wp-content/plugins/plugin-directory
	 *
	 * @since 1.0.0
	 * @return string plugin path
	 */
	public function get_opr_plugin_path() {

		if ( $this->opr_plugin_path ) {
			return $this->opr_plugin_path;
		}
        $this->opr_plugin_path = untrailingslashit( plugin_dir_path( __FILE__ ) );
		return $this->opr_plugin_path;
	}

	/**
	 * Gets the plugin url without a trailing slash
	 *
	 * @since 1.0.0
	 * @return string the plugin url
	 */
	public function get_opr_plugin_url() {

		if ( $this->opr_plugin_url ) {
			return $this->opr_plugin_url;
		}
		$this->opr_plugin_url = untrailingslashit( plugins_url( '/', __FILE__ ) );
        return $this->opr_plugin_url;
	}
    /**
	 * Gets the plugin file
	 *
	 * @since 1.0.0
	 * @return string the plugin file
	 */
	public function get_opr_plugin_file() {
		if ( $this->opr_plugin_file ) {
			return $this->opr_plugin_file;
		}
        $this->opr_plugin_file = __FILE__;
        return $this->opr_plugin_file;
	}
    /**
	 * Disconnected from hub
	 *
	 * @since 2.0.2
	 * 
	 */
    public static function oliver_pos_points_and_rewards_disconnect_to_hub(){
        $plugin_details = get_plugin_data( OPR_PATH_ROOT );
		$server_url = OPR_SERVER_URL;
		$code 	= $plugin_details['TextDomain'];
		$opr_endpoint 	= OPR_DEACTIVATE_EXTENTION; // DeactivateExtention
		// Url to call
		$esc_url = esc_url_raw("{$server_url}/{$opr_endpoint}?code={$code}");
		// Get cURL resource
		$responceHub = wp_remote_get($esc_url, array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( get_option( 'oliver_pos_subscription_client_id' ).":".get_option( 'oliver_pos_subscription_token' ) ),
            ), 
            )
        );
        if ( is_wp_error($responceHub)) {
			$responceHubData = json_encode(array("Message" => $responceHub->get_error_message()));
		}
		else
		{
			if (wp_remote_retrieve_response_code($responceHub) == 200)
			{
				$responceHubData = json_decode(wp_remote_retrieve_body($responceHub));
				if ($responceHubData->IsSuccess )
				{
                    update_option('oliver_points_and_rewards_status', 'disconnected');
                }
            }
        }
    }
    /**
	 * Connect from hub
	 *
	 * @since 2.0.2
	 * 
	 */
    public static function oliver_pos_points_and_rewards_connect_to_hub(){
        $opr_endpoint 	= OPR_SAVE_EXTENTION;	// ActivateExtention
		$server_url = OPR_SERVER_URL;
		$PageUrl 	= get_option('op_points_rewards_extenstion_url');
		$esc_url = esc_url_raw("{$server_url}/{$opr_endpoint}");
		$plugin_details = get_plugin_data( OPR_PATH_ROOT );
   
		$data = array(
            'Name' => $plugin_details['Name'],
            'Description' => $plugin_details['Description'],
            'Version' => $plugin_details['Version'],
			'code' => $plugin_details['TextDomain'],
			'HostUrl' => home_url(),
			'PageUrl' => $PageUrl,
			'logo' => PLUGIN_LOGO,
			'viewManagement' => array(),
			
		);
        $data['viewManagement'][]=array(
            'ViewSlug'            => 'Customer View',
            'ActionSlug'          => '1',
            'SubTitle'          => '1',
        );
        $data['viewManagement'][]=array(
            'ViewSlug'            => 'checkout',
            'ActionSlug'          => '1',
            'SubTitle'          => '1',
        );
		$responceHub = wp_remote_post( esc_url_raw( $esc_url ), array(
            
            'body' => $data,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( get_option( 'oliver_pos_subscription_client_id' ).":".get_option( 'oliver_pos_subscription_token' ) ),
            ),
        ) );
        if ( is_wp_error($responceHub) ) {
			$responceHubData = json_encode(array("Message" => $responceHub->get_error_message()));
		}
		else
		{
			if (wp_remote_retrieve_response_code($responceHub) == 200)
			{
				$responceHubData = json_decode(wp_remote_retrieve_body($responceHub));
				if ( $responceHubData->IsSuccess )
				{
                    update_option('oliver_points_and_rewards_status', 'connected');
                }
            }
        }
    }
	public static function pos_bridge_enqueue_scripts_and_styles_in_front(){
		wp_register_style( 'pos-bridge-style-connect-css', plugins_url('assets/css/style.css', dirname(__FILE__)), '', '', '' ); 
		wp_enqueue_style( 'pos-bridge-style-connect-css' );
	}
}

