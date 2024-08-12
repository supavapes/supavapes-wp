<?php
/**
 * WooCommerce Moneris.
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade WooCommerce Moneris to newer
 * versions in the future. If you wish to customize WooCommerce Moneris for your
 * needs please refer to https://docs.woocommerce.com/document/moneris/ for more information.
 *
 * @author    SkyVerge
 * @copyright Copyright (c) 2012-2024, SkyVerge, Inc. (info@skyverge.com)
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */
defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_12_1 as Framework;

/**
 * WooCommerce Moneris Gateway Main Plugin Class.
 *
 * @since 2.0.0
 *
 * @method \WC_Gateway_Moneris_Credit_Card|\WC_Gateway_Moneris_Checkout_Credit_Card get_gateway( $gateway_id = null )
 */
#[AllowDynamicProperties]
class WC_Moneris extends Framework\SV_WC_Payment_Gateway_Plugin {


	/** version number */
	const VERSION = '3.4.1';

	/** @var WC_Moneris single instance of this plugin */
	protected static $instance;

	/** the plugin id */
	const PLUGIN_ID = 'moneris';

	/** the credit card gateway class name */
	const CREDIT_CARD_GATEWAY_CLASS_NAME = 'WC_Gateway_Moneris_Credit_Card';

	/** the checkout credit card gateway class name */
	const CHECKOUT_CREDIT_CARD_GATEWAY_CLASS_NAME = 'WC_Gateway_Moneris_Checkout_Credit_Card';

	/** the credit card gateway id */
	const CREDIT_CARD_GATEWAY_ID = 'moneris';

	/** the interac online gateway id */
	const INTERAC_GATEWAY_ID = 'moneris_interac';

	/** the production URL endpoint for the Canadian integration */
	const PRODUCTION_URL_ENDPOINT_CA = 'https://www3.moneris.com';

	/** the test (sandbox) URL endpoint for the Canadian integration */
	const TEST_URL_ENDPOINT_CA = 'https://esqa.moneris.com';

	/** the production URL endpoint for the US integration */
	const PRODUCTION_URL_ENDPOINT_US = 'https://esplus.moneris.com';

	/** the test (sandbox) URL endpoint for the US integration */
	const TEST_URL_ENDPOINT_US = 'https://esplusqa.moneris.com';

	/** the Canadian integration identifier */
	const INTEGRATION_CA = 'ca';

	/** the US integration identifier */
	const INTEGRATION_US = 'us';

	/** @var array the Canadian test hosted tokenization profile IDs */
	protected $ca_test_ht_profile_ids;

	/** @var array the US test hosted tokenization profile IDs */
	protected $us_test_ht_profile_ids;

	/** @var array active gateways */
	protected $active_gateways;


	/**
	 * Constructs the class.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {

		$this->active_gateways = $this->get_active_gateways();

		parent::__construct(
			self::PLUGIN_ID,
			self::VERSION,
			[
				'text_domain'        => 'woocommerce-gateway-moneris',
				'gateways'           => $this->active_gateways,
				'dependencies'       => [
					'php_extensions' => ['SimpleXML', 'xmlwriter', 'dom'],
				],
				'currencies'         => ['CAD', 'USD'],
				'require_ssl'        => true,
				'supported_features' => [
					'hpos'   => true,
					'blocks' => [
						'cart'     => true,
						'checkout' => true,
					],
				],
				'supports'           => [
					self::FEATURE_CAPTURE_CHARGE,
					self::FEATURE_CUSTOMER_ID,
					self::FEATURE_MY_PAYMENT_METHODS,
				],
			]
		);

		$this->ca_test_ht_profile_ids = [];

		$this->us_test_ht_profile_ids = [];

		$this->includes();

		add_action( 'init', [ $this, 'include_template_functions'], 25 );

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts'] );

		// Display Interac issuer data to customer
		add_action( 'woocommerce_order_details_after_order_table', [ $this, 'interac_order_table_receipt_data'] );
		add_action( 'woocommerce_email_after_order_table', [ $this, 'interac_email_order_table_receipt_data'], 10, 3 );

		add_action( 'woocommerce_order_status_on-hold_to_cancelled', [ $this, 'maybe_reverse_authorization'] );

		if ( is_admin() && ! wp_doing_ajax() ) {

			add_filter( 'woocommerce_order_actions', [ $this, 'add_order_reverse_authorization_action'] );

			add_action( 'woocommerce_order_action_' . $this->get_id() . '_reverse_authorization', [ $this, 'maybe_reverse_authorization'] );
		}

		// Pay Page - Hosted Tokenization Checkout
		// AJAX handler to handle request logging
		add_action( 'wp_ajax_wc_payment_gateway_' . $this->get_id() . '_handle_hosted_tokenization_response', [ $this, 'handle_hosted_tokenization_response'] );
		add_action( 'wp_ajax_nopriv_wc_payment_gateway_' . $this->get_id() . '_handle_hosted_tokenization_response', [ $this, 'handle_hosted_tokenization_response'] );

		// handle checkout migration upgrade notice action
		add_action( 'admin_post_moneris-checkout-migration', [ $this, 'handle_checkout_migration' ] );
	}


	/**
	 * Gets the active gateways.
	 *
	 * @since 3.0.0
	 * @deprecated since 3.4.0
	 * @TODO remove this method by version 4.0.0 or by January 2025 {unfulvio 2024-01-17}
	 *
	 * @return array<class-string<WC_Gateway_Moneris_Checkout_Credit_Card|WC_Gateway_Moneris_Credit_Card>
	 */
	protected function getActiveGateways() : array {

		wc_deprecated_function( __METHOD__, '3.4.0', 'WC_Moneris::get_active_gateways()' );

		return $this->get_active_gateways();
	}


	/**
	 * Gets the active gateways.
	 *
	 * @since 3.4.0
	 *
	 * @return array<class-string<WC_Gateway_Moneris_Checkout_Credit_Card|WC_Gateway_Moneris_Credit_Card>
	 */
	protected function get_active_gateways() : array {

		return [
			self::CREDIT_CARD_GATEWAY_ID => self::is_legacy_mode_enabled() ? self::CREDIT_CARD_GATEWAY_CLASS_NAME : self::CHECKOUT_CREDIT_CARD_GATEWAY_CLASS_NAME,
		];
	}


	/**
	 * Loads any required files.
	 *
	 * @since 2.0.0
	 */
	public function includes() {

		if ( self::is_legacy_mode_enabled() ) {
			require_once $this->get_plugin_path() . '/src/class-wc-gateway-moneris-credit-card.php';
			require_once $this->get_plugin_path() . '/src/payment-forms/class-wc-moneris-payment-form.php';
		} else {
			require_once $this->get_plugin_path() . '/src/class-wc-gateway-moneris-checkout-credit-card.php';
			require_once $this->get_plugin_path() . '/src/payment-forms/class-wc-moneris-checkout-payment-form.php';
		}

		// tokens handler class
		require_once $this->get_plugin_path() . '/src/payment-tokens/class-wc-gateway-moneris-payment-token.php';
		require_once $this->get_plugin_path() . '/src/class-wc-gateway-moneris-payment-tokens-handler.php';
	}


	/**
	 * Gets the My Payment Methods instance.
	 *
	 * @since 2.14.0
	 *
	 * @return \SkyVerge\WooCommerce\Moneris\My_Payment_Methods
	 */
	protected function get_my_payment_methods_instance() {

		require_once $this->get_plugin_path() . '/src/My_Payment_Methods.php';

		return new \SkyVerge\WooCommerce\Moneris\My_Payment_Methods( $this );
	}


	/**
	 * Confirms if the legacy mode enabled (before MCO).
	 *
	 * @since 3.0.0
	 *
	 * @return bool if the legacy mode is enabled or not
	 */
	public static function isLegacyModeEnabled() {

		wc_deprecated_function( __METHOD__, '3.4.0', 'WC_Moneris::is_legacy_mode_enabled()' );

		return self::is_legacy_mode_enabled();
	}


	/**
	 * Confirms if the legacy mode enabled (before MCO).
	 *
	 * @since 3.4.0
	 *
	 * @return bool if the legacy mode is enabled or not
	 */
	public static function is_legacy_mode_enabled() : bool {

		return (bool) get_option( 'woocommerce_'.self::CREDIT_CARD_GATEWAY_ID.'_legacy_gateway_enabled', false );
	}


	/**
	 * Confirms if the Moneris Interac was previously installed.
	 *
	 * @since 3.1.0
	 * @deprecated since 3.4.0
	 * @TODO remove this method by version 4.0.0 or by January 2025 {unfulvio 2024-01-17}
	 *
	 * @return bool if the moneris interac is available
	 */
	public static function userHasInteracSetup() {

		return static::is_interac_setup();
	}


	/**
	 * Confirms if the Moneris Interac was previously installed.
	 *
	 * @since 3.4.0
	 *
	 * @return bool if the moneris interac is available
	 */
	public static function is_interac_setup() : bool {

		$is_interac_available = get_option( 'woocommerce_'.self::INTERAC_GATEWAY_ID.'_available', false );
		$moneris_settings     = get_option( 'woocommerce_'.self::INTERAC_GATEWAY_ID.'_settings' );

		return $is_interac_available || ( $moneris_settings && isset( $moneris_settings['enabled'] ) && 'yes' === $moneris_settings['enabled'] );
	}


	/**
	 * Initializes the lifecycle handler.
	 *
	 * @since 2.11.0
	 */
	protected function init_lifecycle_handler() {

		require_once $this->get_plugin_path() . '/src/Handlers/Lifecycle.php';

		$this->lifecycle_handler = new \SkyVerge\WooCommerce\Moneris\Handlers\Lifecycle( $this );
	}


	/**
	 * Gets the settings page link.
	 *
	 * @since 2.0.0
	 *
	 * @param string $gateway_id gateway ID
	 * @return string
	 */
	public function get_settings_link( $gateway_id = null ) {

		return sprintf('<a href="%s">%s</a>',
			$this->get_settings_url( $gateway_id ),
			self::CREDIT_CARD_GATEWAY_ID === $gateway_id ? __( 'Configure Moneris', 'woocommerce-gateway-moneris' ) : __( 'Configure Interac', 'woocommerce-gateway-moneris' )
		);
	}


	/**
	 * Adds any required admin notices.
	 *
	 * @since  2.1.0
	 */
	public function add_admin_notices() {

		parent::add_admin_notices();

		// show a notice for any settings/configuration issues
		$this->add_settings_admin_notices();
	}


	/**
	 * Handles Moneris checkout migration callback.
	 *
	 * @since 3.0.0
	 *
	 * @internal
	 */
	public function handle_checkout_migration() {

		$nonce = $_REQUEST['_wpnonce'] ?? null;

		if ( $nonce && wp_verify_nonce( $nonce, 'moneris-checkout-migration' ) ) {

			update_option( 'woocommerce_'.self::CREDIT_CARD_GATEWAY_ID.'_legacy_gateway_enabled', false );

			wp_redirect( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=moneris' ), 301 );
			exit();
		}

		wp_redirect( admin_url( 'admin.php?page=wc-settings&tab=checkout' ), 301 );
		exit();
	}


	/**
	 * Handles Moneris checkout migration callback.
	 *
	 * @since 3.0.0
	 * @deprecated since 3.4.0
	 * @TODO remove this method by version 4.0.0 or by January 2025 {unfulvio 2024-01-17}
	 */
	public function monerisCheckoutMigrationCallback() {

		$this->handle_checkout_migration();
	}


	/**
	 * Enqueues scripts in the WP Admin.
	 *
	 * @since 2.10.0
	 */
	public function enqueue_admin_scripts() {

		wp_enqueue_script( 'woocommerce_moneris_admin', $this->get_plugin_url() . '/assets/js/admin/wc-moneris-admin.min.js' );

		wp_enqueue_script(
			'woocommerce_moneris_admin_settings',
			$this->get_plugin_url() . '/assets/js/admin/wc-moneris-admin-settings.min.js',
			['woocommerce_moneris_admin']
		);

		wp_localize_script( 'woocommerce_moneris_admin', 'wc_moneris_admin', [
			'credit_card_gateway_id'  => self::CREDIT_CARD_GATEWAY_ID,
			'integration_ca'          => self::INTEGRATION_CA,
			'integration_us'          => self::INTEGRATION_US,
			'cad_default_integration' => self::INTEGRATION_CA,
			'usd_default_integration' => self::INTEGRATION_US,
			/* translators: Placeholders: %s - currency code */
			'add_connection_settings_label'    => esc_html__( 'Add connection settings for %s', 'woocommerce-gateway-moneris' ),
			'remove_connection_settings_label' => esc_html__( 'Remove these merchant settings', 'woocommerce-gateway-moneris' ),
			'sandbox_credentials_description'  => sprintf(
				/* translators: Placeholders: %1$s - opening link HTML tag, %2$s - closing link HTML tag */
				__( 'Moneris uses a set of shared test accounts. %1$sClick here%2$s for a list of available test store accounts.', 'woocommerce-gateway-moneris' ),
				'<a href="https://developer.moneris.com/en/More/Testing/Testing%20a%20Solution" target="_blank">',
				'</a>'
			),
		] );
	}


	/**
	 * Adds any settings admin notices.
	 *
	 * @since 2.0.0
	 */
	private function add_settings_admin_notices() {

		$settings = wp_parse_args( $this->get_gateway_settings( self::CREDIT_CARD_GATEWAY_ID ), [
			'environment' => '',
		] );

		// technically not DRY, but avoids unnecessary instantiation of the gateway class
		if ( ( ( isset( $settings['integration'] ) && 'us' === $settings['integration'] && strlen( $settings['dynamic_descriptor'] ) > 20 && ! isset( $_POST['woocommerce_moneris_integration'] ) ) ||
			( isset( $_POST['woocommerce_moneris_integration'] ) && 'us' === $_POST['woocommerce_moneris_integration'] && strlen( $_POST['woocommerce_moneris_dynamic_descriptor'] ) > 20 ) ) ) {
			$message = sprintf(
				__( '%1$sMoneris Gateway:%2$s US integration dynamic descriptor is too long.  You are recommended to %3$sshorten%4$s it to 20 characters or less as only the first 20 characters will be used.', 'woocommerce-gateway-moneris' ),
				'<strong>', '</strong>',
				'<a href="' . $this->get_settings_url() . '#woocommerce_moneris_dynamic_descriptor">', '</a>'
			);
			$this->get_admin_notice_handler()->add_admin_notice( $message, 'us-dynamic-descriptor-notice' );
		}

		$environment = $_POST['woocommerce_moneris_environment'] ?? $settings['environment'];

		// warning if no hosted tokenization profile id is configured in the production environment
		if ( self::is_legacy_mode_enabled() && 'production' === $environment && ! ( $_POST['woocommerce_moneris_hosted_tokenization_profile_id'] ?? $settings['hosted_tokenization_profile_id'] ?? '' ) ) {

			$message = sprintf(
				/* translators: Placeholders: %1$s - <strong> tag, %2$s - </strong> tag, %3$s - <a> tag, %4$s - </a> tag */
				__( '%1$sMoneris Gateway:%2$s Hosted tokenization will not be active until a %3$sProfile ID%4$s is configured.', 'woocommerce-gateway-moneris' ),
				'<strong>', '</strong>',
				'<a href="' . $this->get_settings_url() . '#woocommerce_moneris_hosted_tokenization_profile_id">', '</a>'
			);

			$this->get_admin_notice_handler()->add_admin_notice( $message, 'hosted-tokenization-profile-id-missing-notice', ['notice_class' => 'error'] );
		}

		if ( ! self::is_legacy_mode_enabled() && ! in_array( get_woocommerce_currency(), ['CAD', 'USD'] ) ) {

			$message = sprintf(
				__( '%1$sHeads up!%2$s Moneris only supports CAD and USD. Please check your currency in the store settings.', 'woocommerce-gateway-moneris' ),
				'<strong>', '</strong>',
			);

			$this->get_admin_notice_handler()->add_admin_notice( $message, 'only-cad-currency-support-notice', [
				'notice_class' => 'error',
				'dismissible'  => false,
			] );
		}

		// add notice for Moneris Checkout migration for the legacy users only
		if ( self::is_legacy_mode_enabled() ) {

			$action       = 'moneris-checkout-migration';
			$migrationUrl = wp_nonce_url(
				add_query_arg(
					[
						'action' => $action,
					],
					admin_url( 'admin-post.php' )
				),
				$action
			);

			$hasTokenization = false;

			if ( $gateway = $this->get_gateway( self::CREDIT_CARD_GATEWAY_ID ) ) {
				$hasTokenization = $gateway->tokenization_enabled();
			}

			$message = sprintf(__('Important: in October 2022 Moneris is retiring several of their eCommerce features that the WooCommerce Moneris Payment Gateway plugin relies on.
			As a result we are migrating from our Hosted Tokenization solution to an integration with Moneris Checkout. To avoid any interruption in payment processing you must create a Moneris Checkout Profile and migrate this gateway prior to October, 2022. To do so, log in to your
			Moneris Merchant Resource Center account and from the dropdown menu "Admin" click "Moneris Checkout Config". Then click "Create Profile" and use the following settings:
			%1$s
				%2$sUnder Checkout Type select "I have my custom order form and want to use Moneris simply for payment processing."%3$s
				%2$sUnder Order Summary (Cart) unselect "Order Summary (Cart) details"%3$s
				%2$sUnder Customer Details unselect "Customer\'s Personal Details"%3$s
				%2$sUnder Payment > Accepted Digital Wallets select "Apple Pay" or "Google Pay" if you want to accept digital wallet payments%3$s
				%2$sUnder Payment > Payment Security ensure "Auto Decision by Moneris" is unchecked%3$s
				%2$sUnder Payment > Payment Security select "AVS" and "Mandatory"%3$s
				%2$sUnder Payment > Payment Security select "CVV" and "Mandatory"%3$s
				%2$sUnder Payment > Transaction Type select "Preauthorization"%3$s'.
				( $hasTokenization ? '%2$sUnder Payment > Transaction Type select "Tokenize Card"%3$s' : '' ) .
				'%2$sUnder Branding & Design > Customizations unselect "Enable Fullscreen"%3$s
				%2$sUnder Order Confirmation > Order Confirmation Processing select "Use Own Page"%3$s
				%2$sUnder Email Communications > Customer Emails unselect "Approved Transactions"%3$s
				%2$sAll other settings can be left as default%3$s
			%4$s
			Click "Save" and then copy the Checkout ID, enter it into the "Checkout ID" setting in the %11$sMoneris payment gateway settings%12$s, and then click on "Begin Migration" below.
			%5$s%6$sBegin migration%7$s %8$sLearn more%9$s%10$s', 'woocommerce-gateway-moneris'),
				'<ul style="padding-left: 15px; list-style-type: disc; margin-top: 0; ">',
				'<li>',
				'</li>',
				'</ul>',
				'<div style="margin-top: 15px;">',
				'<a class="button button-primary" id="wc-moneris-migrate-button" href="' . $migrationUrl.'">',
				'</a>',
				'<a class="button" href="' . $this->get_documentation_url() . '">',
				'</a>',
				'</div>',
				'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=moneris' ) . '">',
				'</a>'
			);

			$this->get_admin_notice_handler()->add_admin_notice( $message, $action, [
				'notice_class'            => 'error',
				'dismissible'             => false,
				'always_show_on_settings' => true,
			] );
		}
	}


	/**
	 * Adds a "Reverse Authorization" action to the Admin Order Edit Order
	 * Actions dropdown.
	 *
	 * @since 2.0.0
	 *
	 * @param array $actions available order actions
	 * @return array
	 */
	public function add_order_reverse_authorization_action( $actions ) {

		// bail adding a new order from the admin
		if ( ! isset( $_REQUEST['post'] ) ) {
			return $actions;
		}

		$order = wc_get_order( (int) $_REQUEST['post'] );

		if ( ! $order ) {
			return $actions;
		}

		$payment_method = $order->get_payment_method();

		// bail if the order wasn't paid for with this gateway
		if ( ! $this->has_gateway( $payment_method ) ) {
			return $actions;
		}

		$gateway = $this->get_gateway( $payment_method );

		// ensure that the authorization is still valid for capture
		if ( ! $gateway->get_capture_handler()->order_can_be_captured( $order ) ) {
			return $actions;
		}

		$actions[ $this->get_id() . '_reverse_authorization' ] = __( 'Reverse Authorization', 'woocommerce-gateway-moneris' );

		return $actions;
	}


	/**
	 * Reverse a prior authorization if this payment method was used for the
	 * given order, the charge hasn't already been captured/reversed.
	 *
	 * @since 2.0
	 *
	 * @see Framework\Payment_Gateway\Handlers\Capture::order_can_be_captured()
	 *
	 * @param \WC_Order|int $order the order identifier or order object
	 */
	public function maybe_reverse_authorization( $order ) {

		if ( ! is_object( $order ) ) {
			$order = wc_get_order( $order );
		}

		$payment_method = $order->get_payment_method();

		// bail if the order wasn't paid for with this gateway
		if ( ! $this->has_gateway( $payment_method ) ) {
			return;
		}

		$gateway = $this->get_gateway( $payment_method );

		// ensure the authorization is still valid for capture
		if ( ! $gateway->get_capture_handler() || ! $gateway->get_capture_handler()->order_can_be_captured( $order ) ) {
			return;
		}

		// remove order status change actions, otherwise we get a whole bunch of reverse calls and errors
		remove_action( 'woocommerce_order_status_on-hold_to_cancelled', [ $this, 'maybe_reverse_authorization'] );
		remove_action( 'woocommerce_order_action_' . $this->get_id() . '_reverse_authorization', [ $this, 'maybe_reverse_authorization'] );

		// Starting in WC 2.1 we need to remove the meta box order save action, otherwise the wp_update_post() call
		//  in WC_Order::update_status() to update the post last modified will re-trigger the save action, which
		//  will update the order status to $_POST['order_status'] which of course will be whatever the order status
		//  was prior to the auth capture (ie 'on-hold')
		remove_action( 'woocommerce_process_shop_order_meta', 'WC_Meta_Box_Order_Data::save', 10 );

		// perform the capture
		$gateway->do_credit_card_reverse_authorization( $order );
	}


	/** Frontend methods ******************************************************/


	/**
	 * Includes the template functions.
	 *
	 * @since 2.0.0
	 */
	public function include_template_functions() {

		require_once $this->get_plugin_path() . '/src/wc-gateway-moneris-template.php';
	}


	/**
	 * Displays the Interac Issuer confirmation number and name in the order
	 * receipt table, if the given order was paid for via Interac.
	 *
	 * @since 2.0.0
	 *
	 * @param \WC_Order $order order object
	 */
	public function interac_order_table_receipt_data( $order ) {

		// non-interac order
		if ( self::INTERAC_GATEWAY_ID !== $order->get_payment_method( 'edit' ) ) {
			return;
		}

		$issuer_conf = $order->get_meta( '_wc_moneris_interac_idebit_issconf' );
		$issuer_name = $order->get_meta( '_wc_moneris_interac_idebit_issname' );

		// missing the data
		if ( ! $issuer_conf || ! $issuer_name ) {
			return;
		}

		// otherwise: display the idebit data ?>
		<header>
			<h2><?php _e( 'INTERAC Details', 'woocommerce-gateway-moneris' ); ?></h2>
		</header>
		<dl class="interac_details">
			<dt><?php _e( 'Issuer Confirmation:', 'woocommerce-gateway-moneris' ); ?></dt><dd><?php echo esc_html( $issuer_conf ); ?></dd>
			<dt><?php _e( 'Issuer Name:', 'woocommerce-gateway-moneris' ); ?></dt><dd><?php echo esc_html( $issuer_name ); ?></dd>
		</dl>
		<?php
	}


	/**
	 * Displays the Interac Issuer confirmation number and name in the email
	 * order receipt table, if the given order was paid for via Interac.
	 *
	 * @since 2.0.0
	 *
	 * @param \WC_Order $order the order object
	 * @param bool $sent_to_admin whether the email was sent to admin
	 * @param bool $plain_text whether the email is plaintext
	 */
	public function interac_email_order_table_receipt_data( $order, $sent_to_admin, $plain_text = false ) {

		// non-interac order
		if ( self::INTERAC_GATEWAY_ID !== $order->get_payment_method( 'edit' ) ) {
			return;
		}

		$issuer_conf = $order->get_meta( '_wc_moneris_interac_idebit_issconf' );
		$issuer_name = $order->get_meta( '_wc_moneris_interac_idebit_issname' );

		// missing the data
		if ( ! $issuer_conf || ! $issuer_name ) {
			return;
		}

		if ( ! $plain_text ) :

			// html email ?>
			<h2><?php _e( 'INTERAC Details', 'woocommerce-gateway-moneris' ); ?></h2>

			<p><strong><?php _e( 'Issuer Confirmation:', 'woocommerce-gateway-moneris' ); ?></strong> <?php echo esc_html( $issuer_conf ); ?></p>
			<p><strong><?php _e( 'Issuer Name:', 'woocommerce-gateway-moneris' ); ?></strong> <?php echo esc_html( $issuer_name ); ?></p>
			<?php

		else :

			// plain text email
			echo __( 'INTERAC Details', 'woocommerce-gateway-moneris' ) . "\n\n";

			echo __( 'Issuer Confirmation:', 'woocommerce-gateway-moneris' ) . ' '.esc_html( $issuer_conf ) . "\n";
			echo __( 'Issuer Name:', 'woocommerce-gateway-moneris' ) . ' '.esc_html( $issuer_name ) . "\n";

		endif;
	}


	/** Hosted Tokenization methods ******************************************************/


	/**
	 * Handles the hosted tokenization response by handing off to the gateway.
	 *
	 * @since 2.0.0
	 */
	public function handle_hosted_tokenization_response() {
		$this->get_gateway()->handle_hosted_tokenization_response();
	}


	/**
	 * Gets the hosted tokenization profile IDs for the Canada test stores.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function get_ca_test_ht_profile_ids() {
		return $this->ca_test_ht_profile_ids;
	}


	/**
	 * Gets the hosted tokenization profile IDs for the US test stores.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function get_us_test_ht_profile_ids() {
		return $this->us_test_ht_profile_ids;
	}


	/** Helper methods ******************************************************/


	/**
	 * Builds the Apple Pay handler instance.
	 *
	 * @since 1.11.0
	 *
	 * @return \SkyVerge\WooCommerce\Moneris\Apple_Pay
	 */
	protected function build_apple_pay_instance() {

		require_once $this->get_plugin_path() . '/src/Apple_Pay.php';
		require_once $this->get_plugin_path() . '/src/Apple_Pay/AJAX.php';
		require_once $this->get_plugin_path() . '/src/Apple_Pay/Frontend.php';

		return new \SkyVerge\WooCommerce\Moneris\Apple_Pay( $this );
	}


	/**
	 * Gets the one true instance of the plugin.
	 *
	 * @since 2.2.0
	 *
	 * @return \WC_Moneris
	 */
	public static function instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Gets the plugin sales page URL.
	 *
	 * @since 2.11.0
	 *
	 * @return string
	 */
	public function get_sales_page_url() {
		return 'https://woocommerce.com/products/moneris-gateway/';
	}


	/**
	 * Gets the plugin documentation URL.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_documentation_url() {
		return 'https://docs.woocommerce.com/document/moneris/';
	}


	/**
	 * Gets the plugin support URL.
	 *
	 * @since 2.3.0
	 *
	 * @return string
	 */
	public function get_support_url() {
		return 'https://woocommerce.com/my-account/marketplace-ticket-form/';
	}


	/**
	 * Returns the plugin name, localized.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_plugin_name() {
		return __( 'WooCommerce Moneris Gateway', 'woocommerce-gateway-moneris' );
	}


	/**
	 * Returns __FILE__.
	 *
	 * @since 2.0
	 * @return string the full path and filename of the plugin file
	 */
	protected function get_file() {
		return __FILE__;
	}


} // end WC_Moneris


/**
 * Returns the One True Instance of Moneris.
 *
 * @since 2.2.0
 * @return \WC_Moneris
 */
function wc_moneris() {
	return \WC_Moneris::instance();
}
