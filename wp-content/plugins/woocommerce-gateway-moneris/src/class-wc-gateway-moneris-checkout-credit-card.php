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
 * needs please refer to http://docs.woocommerce.com/document/moneris/ for more information.
 *
 * @author    SkyVerge
 * @copyright Copyright (c) 2012-2024, SkyVerge, Inc. (info@skyverge.com)
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

use SkyVerge\WooCommerce\Moneris\Blocks\Moneris_Checkout_Credit_Card_Checkout_Block_Integration;
use SkyVerge\WooCommerce\PluginFramework\v5_12_1 as Framework;

defined( 'ABSPATH' ) or exit;

/**
 * Moneris Checkout (MCO) Payment Gateway Credit Card Class.
 *
 * Implements the Moneris checkout and direct credit card API
 *
 * @since 3.0.0
 */
#[AllowDynamicProperties]
class WC_Gateway_Moneris_Checkout_Credit_Card extends Framework\SV_WC_Payment_Gateway_Direct {


	/** @var string configured dynamic descriptor */
	protected $dynamic_descriptor;

	/** @var string the configured production store id */
	protected $store_id;

	/** @var string the configured production api token */
	protected $api_token;

	/** @var string the configured moneris checkout id */
	protected $checkout_id;

	/** @var string the configured test store id for the Canadian integration */
	protected $ca_test_store_id;

	/** @var string the configured test API token for the Canadian integration */
	protected $ca_test_api_token;

	/** @var string the configured test store id for the Canadian integration */
	protected $ca_test_checkout_id;

	/** @var string whether avs is enabled 'yes' or 'no' */
	protected $enable_avs;

	/** @var string how to handle AVS neither street address nor zip code match: 'accept', 'reject', 'hold' */
	protected $avs_neither_match;

	/** @var string how to handle AVS zip code match: 'accept', 'reject', 'hold' */
	protected $avs_zip_match;

	/** @var string how to handle AVS street address match: 'accept', 'reject', 'hold' */
	protected $avs_street_match;

	/** @var string how to handle AVS neither street address nor zip code verified: 'accept', 'reject', 'hold' */
	protected $avs_not_verified;

	/** @var string whether CSC is required for *all* (including tokenized) transactions, 'yes' or 'no' */
	protected $require_csc;

	/** @var string how to handle CVD does not match: 'accept', 'reject', 'hold' */
	protected $csc_not_match;

	/** @var string how to handle CVD not verified: 'accept', 'reject', 'hold' */
	protected $csc_not_verified;

	/** @var string whether Apple Pay is enabled 'yes' or 'no' */
	protected $enable_apple_pay;

	/** @var string whether Google Pay is enabled 'yes' or 'no' */
	protected $enable_google_pay;

	/** @var string whether 3D secure 2.0 is enabled */
	protected $enable_3d_secure;

	/** @var WC_Moneris_API instance */
	protected $api;

	/** @var WC_Moneris_Checkout_API instance */
	protected $checkoutAPI;

	/** @var string hopefully temporary work-around for the inflexible Framework\SV_WC_Payment_Gateway::do_transaction() handling of order notes for held authorize-only transaction */
	protected $held_authorization_status_message;

	/** @var string the configured form type */
	protected $form_type;

	/** @var Moneris_Checkout_Credit_Card_Checkout_Block_Integration|null */
	protected ?Moneris_Checkout_Credit_Card_Checkout_Block_Integration $moneris_checkout_credit_card_checkout_block_integration = null;

	/** @var int maximum dynamic descriptor length */
	const DYNAMIC_DESCRIPTOR_MAX_LENGTH = 20;

	/** @var string Inline form type */
	const FORM_TYPE_INLINE = 'inline';

	/** @var string Pay Page form type */
	const FORM_TYPE_PAY_PAGE = 'paypage';


	/**
	 * Initializes the gateway.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {

		parent::__construct(
			\WC_Moneris::CREDIT_CARD_GATEWAY_ID,
			wc_moneris(),
			[
				'method_title'       => __( 'Moneris', 'woocommerce-gateway-moneris' ),
				'method_description' => __( 'Allow customers to securely check out using Moneris', 'woocommerce-gateway-moneris' ),
				'supports'           => [
					self::FEATURE_PRODUCTS,
					self::FEATURE_TOKENIZATION,
					self::FEATURE_TOKEN_EDITOR,
					self::FEATURE_CREDIT_CARD_CHARGE,
					self::FEATURE_CREDIT_CARD_AUTHORIZATION,
					self::FEATURE_CREDIT_CARD_CAPTURE,
					self::FEATURE_CREDIT_CARD_CHARGE_VIRTUAL,
					self::FEATURE_DETAILED_CUSTOMER_DECLINE_MESSAGES,
					self::FEATURE_PAYMENT_FORM,
					self::FEATURE_ADD_PAYMENT_METHOD,
					self::FEATURE_REFUNDS,
					self::FEATURE_VOIDS,
				],
				'payment_type' => self::PAYMENT_TYPE_CREDIT_CARD,
				'environments' => [
					self::ENVIRONMENT_PRODUCTION => __( 'Production', 'woocommerce-gateway-moneris' ),
					self::ENVIRONMENT_TEST       => __( 'Sandbox', 'woocommerce-gateway-moneris' ),
				],
				'currencies' => [], // no currency requirements
			]
		);

		// re-init order button text after parent has loaded settings
		$this->order_button_text = $this->get_order_button_text();

		// render additional rows on the WC Status page for various gateway settings
		add_action( 'wc_payment_gateway_'.$this->get_id().'_system_status_end', [ $this, 'render_status_rows' ] );

		// add order id on WooCommerce Order page
		add_action( 'woocommerce_admin_order_data_after_order_details', [ $this, 'append_moneris_order_id_to_order_details' ] );

		add_filter( 'wc_payment_gateway_'.$this->get_id().'_credit_card_transaction_approved_order_note', [ $this, 'add_moneris_order_id_to_order_note' ], 10, 3 );
	}


	/**
	 * Add the Moneris Order ID to the order success admin note.
	 *
	 * @since 3.4.0
	 *
	 * @internal
	 *
	 * @param string|mixed $message
	 * @param \WC_Order|mixed $order,
	 * @param \WC_Moneris_API_Response|mixed $response
	 * @return string|mixed
	 */
	public function add_moneris_order_id_to_order_note( $message, $order, $response ) {

		if ( ! is_string( $message ) || ! $response instanceof \WC_Moneris_API_Response) {
			return $message;
		}

		if ( $receipt_id = $response->get_receipt_id() ) {
			/* translators: Placeholder: %s - Moneris order ID */
			$message .= sprintf( ' ' . __( '(Moneris Order ID: %s)', 'woocommerce-gateway-moneris' ), $receipt_id );
		}

		return $message;
	}


	/**
	 * Adds the Moneris order ID to an order note.
	 *
	 * @since 3.0.2
	 * @deprecated since 3.4.0
	 *
	 * @param string $message
	 * @param \WC_Order $order
	 * @param \WC_Moneris_API_Response $response
	 * @return string|mixed
	 */
	public function addMonerisOrderIdToOrderNote( $message, $order, $response ) {

		wc_deprecated_function( __METHOD__, '3.4.0', __CLASS__ . '::add_moneris_order_id_to_order_note()' );

		return $this->add_moneris_order_id_to_order_note( $message, $order, $response );
	}


	/**
	 * Enqueues the payment form scripts and styles.
	 *
	 * @since 1.0.0
	 */
	protected function enqueue_gateway_assets() {

		if ( is_account_page() && ! is_add_payment_method_page() ) {
			return;
		}

		$url = 'https://gateway.moneris.com/chkt/js/chkt_v1.00.js';

		if (  $this->is_test_environment() ) {
			$url = 'https://gatewayt.moneris.com/chkt/js/chkt_v1.00.js';
		}

		wp_enqueue_script( 'moneris-checkout-js', $url, [], \WC_Moneris::VERSION, true );

		parent::enqueue_gateway_assets();
	}


	/**
	 * Initializes the capture handler.
	 *
	 * @since 2.11.0
	 */
	public function init_capture_handler() {

		require_once $this->get_plugin()->get_plugin_path().'/src/Handlers/Capture.php';

		$this->capture_handler = new \SkyVerge\WooCommerce\Moneris\Handlers\Capture( $this );
	}


	/**
	 * Renders additional rows on the WC Status page for various gateway settings.
	 *
	 * @internal
	 *
	 * @since 2.10.2
	 */
	public function render_status_rows() {
		?>
		<tr>
			<td data-export-label="Integration Version"><?php esc_html_e( 'Integration Version', 'woocommerce-gateway-moneris' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( __( 'Displays the plugin integration version.', 'woocommerce-gateway-moneris' ) ); ?></td>
			<td>
				<?php esc_html_e( 'Moneris Checkout', 'woocommerce-gateway-moneris' ); ?>
			</td>
		</tr>

		<tr>
			<td data-export-label="AVS Enabled"><?php esc_html_e( 'AVS Enabled', 'woocommerce-gateway-moneris' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( __( 'Displays whether AVS is enabled.', 'woocommerce-gateway-moneris' ) ); ?></td>
			<td>
				<?php if ( $this->avs_enabled() ) : ?>
					<mark class="yes">&#10004;</mark>
				<?php else : ?>
					<mark class="no">&ndash;</mark>
				<?php endif; ?>
			</td>
		</tr>

		<?php if ( $this->avs_enabled() ) : ?>

			<tr>
				<td data-export-label="AVS Actions"><?php esc_html_e( 'AVS Actions', 'woocommerce-gateway-moneris' ); ?>:</td>
				<td class="help"><?php echo wc_help_tip( __( 'Displays the transaction action for each AVS result.', 'woocommerce-gateway-moneris' ) ); ?></td>
				<td>
					<?php /* translators: Placeholders: %s - transaction result action like "hold" or "accept" */
					echo sprintf( esc_html__( 'Neither Match: %s', 'woocommerce-gateway-moneris' ), $this->avs_neither_match ).', '; ?>
					<?php /* translators: Placeholders: %s - transaction result action like "hold" or "accept" */
					echo sprintf( esc_html__( 'Zip Match: %s', 'woocommerce-gateway-moneris' ), $this->avs_zip_match ).', '; ?>
					<?php /* translators: Placeholders: %s - transaction result action like "hold" or "accept" */
					echo sprintf( esc_html__( 'Street Match: %s', 'woocommerce-gateway-moneris' ), $this->avs_street_match ).', '; ?>
					<?php /* translators: Placeholders: %s - transaction result action like "hold" or "accept" */
					echo sprintf( esc_html__( 'Not Verified: %s', 'woocommerce-gateway-moneris' ), $this->avs_not_verified ); ?>
				</td>
			</tr>

		<?php endif; ?>

		<tr>
			<td data-export-label="CSC Enabled"><?php esc_html_e( 'CSC Enabled', 'woocommerce-gateway-moneris' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( __( 'Displays whether CSC validation is enabled.', 'woocommerce-gateway-moneris' ) ); ?></td>
			<td>
				<?php if ( $this->csc_enabled() ) : ?>
					<mark class="yes">&#10004;</mark>
				<?php else : ?>
					<mark class="no">&ndash;</mark>
				<?php endif; ?>
			</td>
		</tr>

		<tr>
			<td data-export-label="CSC Required"><?php esc_html_e( 'CSC Required', 'woocommerce-gateway-moneris' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( __( 'Displays whether CSC validation is required for all transactions.', 'woocommerce-gateway-moneris' ) ); ?></td>
			<td>
			<td>
				<?php if ( $this->csc_required() ) : ?>
					<mark class="yes">&#10004;</mark>
				<?php else : ?>
					<mark class="no">&ndash;</mark>
				<?php endif; ?>
			</td>
		</tr>

		<?php if ( $this->csc_enabled() ) : ?>

			<tr>
				<td data-export-label="CSC Actions"><?php esc_html_e( 'CSC Actions', 'woocommerce-gateway-moneris' ); ?>:</td>
				<td class="help"><?php echo wc_help_tip( __( 'Displays the transaction action for each CSC result.', 'woocommerce-gateway-moneris' ) ); ?></td>
				<td>
					<?php /* translators: Placeholders: %s - transaction result action like "hold" or "accept" */
					echo sprintf( esc_html__( 'No Match - %s', 'woocommerce-gateway-moneris' ), $this->csc_not_match ).', '; ?>
					<?php /* translators: Placeholders: %s - transaction result action like "hold" or "accept" */
					echo sprintf( esc_html__( 'Not Verified - %s', 'woocommerce-gateway-moneris' ), $this->csc_not_verified ); ?>
				</td>
			</tr>

		<?php endif; ?>

		<?php
	}


	/**
	 * Gets an array of form fields specific for this method.
	 *
	 * @since 2.0.0
	 *
	 * @return array<string, mixed>
	 */
	protected function get_method_form_fields() {

		// add form fields for default connection settings
		$form_fields = $this->get_connection_settings_form_fields();

		$form_fields += [

			'form_type' => [
				'title'       => __( 'Form Type', 'woocommerce-gateway-moneris' ),
				'type'        => 'select',
				'class'       => 'form-type-select',
				'description' => Framework\Blocks\Blocks_Handler::is_checkout_block_in_use() ? __( 'The payment form can appear on the Checkout page as a modal or on a separate Pay Page.', 'woocommerce-gateway-moneris' ) : __( 'The payment form can appear inline on the Checkout page or on a separate Pay Page.', 'woocommerce-gateway-moneris' ),
				'default'     => self::FORM_TYPE_INLINE,
				'options'     => [
					self::FORM_TYPE_INLINE   => Framework\Blocks\Blocks_Handler::is_checkout_block_in_use() ? __( 'Modal', 'woocommerce-gateway-moneris' ) : __( 'Inline', 'woocommerce-gateway-moneris' ),
					self::FORM_TYPE_PAY_PAGE => __( 'Pay Page', 'woocommerce-gateway-moneris' ),
				],
			],

			'dynamic_descriptor' => [
				'title'       => __( 'Dynamic Descriptor', 'woocommerce-gateway-moneris' ),
				'type'        => 'text',
				'desc_tip'    => __( 'What your buyers will see on their credit card statement ', 'woocommerce-gateway-moneris' ),
				'description' => __( 'Twenty characters maximum allowed' ),
				'custom_attributes' => [
					'maxlength'   => self::DYNAMIC_DESCRIPTOR_MAX_LENGTH,
				],
			],

			'enable_apple_pay' => [
				'title'       => __( 'Apple Pay', 'woocommerce-gateway-moneris' ),
				'label'       => __( 'Allow customers to securely pay via Apple Pay.', 'woocommerce-gateway-moneris' ),
				'description' => __( 'This must first be enabled in your Moneris checkout profile, and will disable support for WooCommerce Subscriptions and WooCommerce Pre-Orders.', 'woocommerce-gateway-moneris' ),
				'type'        => 'checkbox',
				'default'     => 'no',
			],

			'enable_google_pay' => [
				'title'       => __( 'Google Pay', 'woocommerce-gateway-moneris' ),
				'label'       => __( 'Allow customers to securely pay via Google Pay.', 'woocommerce-gateway-moneris' ),
				'description' => __( 'This must first be enabled in your Moneris checkout profile, and will disable support for WooCommerce Subscriptions and WooCommerce Pre-Orders.', 'woocommerce-gateway-moneris' ),
				'type'        => 'checkbox',
				'default'     => 'no',
			],

			'enable_avs' => [
				'title'       => __( 'Address Verification Service (AVS)', 'woocommerce-gateway-moneris' ),
				'label'       => __( 'Perform an AVS check on customers billing addresses', 'woocommerce-gateway-moneris' ),
				'description' => __( 'This must first be enabled in your Moneris checkout profile and works only with Visa / MasterCard / Discover / JCB / American Express card types.  All other card types will not be declined due to AVS.', 'woocommerce-gateway-moneris' ),
				'type'        => 'checkbox',
				'default'     => 'no',
			],

			'avs_neither_match' => [
				'description' => __( 'If neither street address nor zip code match', 'woocommerce-gateway-moneris' ),
				'class'       => 'avs-field',
				'type'        => 'select',
				'options'     => [
					'accept' => __( 'Accept Transaction', 'woocommerce-gateway-moneris' ),
					'reject' => __( 'Reject Transaction', 'woocommerce-gateway-moneris' ),
					'hold'   => __( 'Hold Transaction', 'woocommerce-gateway-moneris' ),
				],
				'default'  => 'reject',
				'desc_tip' => __( "Use 'Accept' to automatically accept the transaction, 'Reject' to automatically decline the transaction, and 'Hold' to perform an authorization and hold the order for review.", 'woocommerce-gateway-moneris' ),
			],

			'avs_zip_match' => [
				'description' => __( 'If zip code matches but street address does not match or could not be verified', 'woocommerce-gateway-moneris' ),
				'class'       => 'avs-field',
				'type'        => 'select',
				'options'     => [
					'accept' => __( 'Accept Transaction', 'woocommerce-gateway-moneris' ),
					'reject' => __( 'Reject Transaction', 'woocommerce-gateway-moneris' ),
					'hold'   => __( 'Hold Transaction', 'woocommerce-gateway-moneris' ),
				],
				'default'  => 'accept',
				'desc_tip' => __( "Use 'Accept' to automatically accept the transaction, 'Reject' to automatically decline the transaction, and 'Hold' to perform an authorization and hold the order for review.", 'woocommerce-gateway-moneris' ),
			],

			'avs_street_match' => [
				'description' => __( 'If street address matches but zip code does not match or could not be verified', 'woocommerce-gateway-moneris' ),
				'class'       => 'avs-field',
				'type'        => 'select',
				'options'     => [
					'accept' => __( 'Accept Transaction', 'woocommerce-gateway-moneris' ),
					'reject' => __( 'Reject Transaction', 'woocommerce-gateway-moneris' ),
					'hold'   => __( 'Hold Transaction', 'woocommerce-gateway-moneris' ),
				],
				'default'  => 'accept',
				'desc_tip' => __( "Use 'Accept' to automatically accept the transaction, 'Reject' to automatically decline the transaction, and 'Hold' to perform an authorization and hold the order for review.", 'woocommerce-gateway-moneris' ),
			],

			'avs_not_verified' => [
				'description' => __( 'If street address and zip code could not be verified', 'woocommerce-gateway-moneris' ),
				'class'       => 'avs-field',
				'type'        => 'select',
				'options'     => [
					'accept' => __( 'Accept Transaction', 'woocommerce-gateway-moneris' ),
					'reject' => __( 'Reject Transaction', 'woocommerce-gateway-moneris' ),
					'hold'   => __( 'Hold Transaction', 'woocommerce-gateway-moneris' ),
				],
				'default'  => 'accept',
				'desc_tip' => __( "Use 'Accept' to automatically accept the transaction, 'Reject' to automatically decline the transaction, and 'Hold' to perform an authorization and hold the order for review.", 'woocommerce-gateway-moneris' ),
			],

		];

		// the following is to adjust the sort order of the form fields with CSC and 3DSecure fields following other fields
		$csc_form_fields = $secure_3d_form_fields = [];

		foreach ( $this->form_fields as $name => $field ) {

			if ( 'enable_csc' == $name || ( isset( $field['class'] ) && false !== strpos( $field['class'], 'csc-field' ) ) ) {

				$csc_form_fields[ $name ] = $field;

				unset( $this->form_fields[ $name ] );
			}

			if ( 'enable_3d_secure' == $name ) {

				$secure_3d_form_fields[ $name ] = $field;

				unset( $this->form_fields[ $name ] );
			}
		}

		return $form_fields + $csc_form_fields + $secure_3d_form_fields;
	}


	/**
	 * Adds the CSC result handling form fields.
	 *
	 * @since 2.0.0
	 *
	 * @param array $form_fields gateway form fields
	 * @return array
	 */
	protected function add_csc_form_fields( $form_fields ) {

		$form_fields = parent::add_csc_form_fields( $form_fields );

		$form_fields['enable_csc']['label']       = __( 'Collect a Card Security Code (CVD) on checkout and validate', 'woocommerce-gateway-moneris' );
		$form_fields['enable_csc']['description'] = __( 'This must first be enabled in your Moneris Checkout profile and works only with Visa / MasterCard / Discover / American Express card types. All other card types will not be declined due to CSC.', 'woocommerce-gateway-moneris' );

		$form_fields['csc_not_match'] = [
			'description' => __( 'If CSC does not match', 'woocommerce-gateway-moneris' ),
			'class'       => 'csc-field',
			'type'        => 'select',
			'options'     => [
				'accept' => __( 'Accept Transaction', 'woocommerce-gateway-moneris' ),
				'reject' => __( 'Reject Transaction', 'woocommerce-gateway-moneris' ),
				'hold'   => __( 'Hold Transaction', 'woocommerce-gateway-moneris' ),
			],
			'default'  => 'reject',
			'desc_tip' => __( "Use 'Accept' to automatically accept the transaction, 'Reject' to automatically decline the transaction, and 'Hold' to perform an authorization and hold the order for review.", 'woocommerce-gateway-moneris' ),
		];

		$form_fields['csc_not_verified'] = [
			'description' => __( 'If CSC could not be verified', 'woocommerce-gateway-moneris' ),
			'class'       => 'csc-field',
			'type'        => 'select',
			'options'     => [
				'accept' => __( 'Accept Transaction', 'woocommerce-gateway-moneris' ),
				'reject' => __( 'Reject Transaction', 'woocommerce-gateway-moneris' ),
				'hold'   => __( 'Hold Transaction', 'woocommerce-gateway-moneris' ),
			],
			'default'  => 'reject',
			'desc_tip' => __( "Use 'Accept' to automatically accept the transaction, 'Reject' to automatically decline the transaction, and 'Hold' to perform an authorization and hold the order for review.", 'woocommerce-gateway-moneris' ),
		];

		$form_fields['require_csc'] = [
			'title'       => __( 'Require Card Verification', 'woocommerce-gateway-moneris' ),
			'class'       => 'csc-field',
			'label'       => __( 'Require the Card Security Code for all transactions', 'woocommerce-gateway-moneris' ),
			'description' => __( 'Enabling this field will require the CSC even for tokenized transactions, and will disable support for WooCommerce Subscriptions and WooCommerce Pre-Orders.', 'woocommerce-gateway-moneris' ),
			'type'        => 'checkbox',
			'default'     => 'no',
		];

		$form_fields['enable_3d_secure'] = [
			'title'       => __( '3D Secure', 'woocommerce-gateway-moneris' ),
			'label'       => __( 'Enable 3D Secure 2.0 in your checkout', 'woocommerce-gateway-moneris' ),
			'description' => __( 'This must first be enabled in your Moneris Checkout profile and works only with Visa / MasterCard / American Express card types. All other card types will not be declined due to 3D Secure.', 'woocommerce-gateway-moneris' ),
			'type'        => 'checkbox',
			'default'     => 'no',
		];

		return $form_fields;
	}


	/**
	 * Adds the Hosted Tokenization options.
	 *
	 * @since 2.0.0
	 *
	 * @param array $form_fields gateway form fields
	 * @return array $form_fields gateway form fields
	 */
	protected function add_tokenization_form_fields($form_fields) {

		$form_fields = parent::add_tokenization_form_fields( $form_fields );

		$form_fields['tokenization']['label']       = _x( 'Allow customers to securely save their payment details for future checkout.  You must contact your Moneris account rep to enable the "Vault" option on your account before enabling this setting, and then enable it in your Moneris Checkout profile.', 'Supports tokenization', 'woocommerce-gateway-moneris' );
		$form_fields['tokenization']['description'] = __( 'This must first be enabled in your Moneris Checkout profile.', 'woocommerce-gateway-moneris' );

		return $form_fields;
	}


	/**
	 * Gets connection settings for form fields.
	 *
	 * @since 2.13.0
	 *
	 * @return array
	 */
	protected function get_connection_settings_form_fields() {

		$production_fields = [
			'store_id' => [
				'title'    => __( 'Store ID', 'woocommerce-gateway-moneris' ),
				'type'     => 'text',
				'class'    => 'js-store-id-field environment-field production-field',
				'desc_tip' => __( 'Your Moneris store ID', 'woocommerce-gateway-moneris' ),
			],

			'api_token' => [
				'title'    => __( 'API Token', 'woocommerce-gateway-moneris' ),
				'type'     => 'password',
				'class'    => 'environment-field production-field',
				'desc_tip' => __( 'Your Moneris API token.  Find this by logging into your Moneris account and going to Admin &gt; store settings &gt; API Token', 'woocommerce-gateway-moneris' ),
			],

			'checkout_id' => [
				'title'       => __( 'Checkout ID', 'woocommerce-gateway-moneris' ),
				'type'        => 'text',
				'class'       => 'js-checkout-id-field environment-field production-field',
				'description' => sprintf(
					__( 'Generate this by %screating a Moneris Checkout profile%s in the Merchant Resource Center. ', 'woocommerce-gateway-moneris' ),
					'<a href="'.$this->get_plugin()->get_documentation_url().'" target="_blank">',
					'</a>',
				),
			],
		];

		$sandbox_fields = [

			/*
			 * This input field was originally a dropdown.
			 * We listed hardcoded store IDs as follows (the last option was commented out):
			 *
			 * 	'store1'  => 'store1',
			 * 	'store2'  => 'store2',
			 * 	'store3'  => 'store3',
			 * 	'store5'  => 'store5 (test AVS &amp; CVD)',
			 * 	// 'moneris' => 'moneris (test VBV)',
			 */
			'ca_test_store_id' => [
				'title'   => __( 'Store ID', 'woocommerce-gateway-moneris' ),
				'type'    => 'text',
				'default' => 'store3',
				'class'   => 'js-store-id-field environment-field test-field integration-field ca-field',
			],

			'ca_test_api_token' => [
				'title'   => __( 'API token', 'woocommerce-gateway-moneris' ),
				'type'    => 'text',
				'default' => 'yesguy',
				'class'   => 'js-api-token-field environment-field test-field integration-field ca-field',
			],

			'ca_test_checkout_id' => [
				'title'       => __( 'Checkout ID', 'woocommerce-gateway-moneris' ),
				'type'        => 'text',
				'class'       => 'js-api-checkout-id-field environment-field test-field integration-field ca-field',
				'description' => sprintf(
					__( 'Generate this by %screating a Moneris Checkout profile%s in the Test Merchant Resource Center. ', 'woocommerce-gateway-moneris' ),
					'<a href="'.$this->get_plugin()->get_documentation_url().'" target="_blank">',
					'</a>',
				),
			],

		];

		return $production_fields + $sandbox_fields;
	}


	/**
	 * Gets the merchant account transaction URL for the given order.
	 *
	 * @since 2.0.0
	 *
	 * @param \WC_Order $order order object
	 * @return string|null
	 */
	public function get_transaction_url($order) {

		$receipt_id  = $this->get_order_meta( $order, 'receipt_id' );
		$trans_id    = $this->get_order_meta( $order, 'auth_trans_id' );
		$environment = $this->get_order_meta( $order, 'environment' );
		$integration = $this->get_order_meta( $order, 'integration' );

		// fall back to the regular transaction ID if the auth-id isn't present
		if ( ! $trans_id ) {
			$trans_id = $this->get_order_meta( $order, 'trans_id' );
		}

		if ( ! $receipt_id || ! $trans_id || ! $environment || ! $integration ) {
			return null;
		}

		$host = $this->get_moneris_host( $environment );

		$host .= '/mpg/reports/order_history/index.php';

		$this->view_transaction_url = add_query_arg( ['order_no' => $receipt_id, 'orig_txn_no' => $trans_id], $host );

		return parent::get_transaction_url( $order );
	}


	/**
	 * Appends a Moneris order ID to the WooCommerce order details.
	 *
	 * @since 3.4.0
	 * @internal
	 *
	 * @param \WC_Order|mixed $order
	 * @return void
	 */
	public function append_moneris_order_id_to_order_details( $order ) : void {

		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$receipt_id     = $this->get_order_meta( $order, 'receipt_id' );
		$payment_method = $order->get_payment_method();

		if ( \WC_Moneris::CREDIT_CARD_GATEWAY_ID !== $payment_method || ! isset( $this->view_transaction_url ) || ! is_string( $receipt_id ) || empty( $receipt_id ) ) {
			return;
		}

		echo '<p style="display: inline-block;"><label>' . __( 'Moneris Order ID:', 'woocommerce-gateway-moneris' ) . '</label> <a href="' . esc_url( $this->view_transaction_url ) . '" target="_blank">' . esc_html( $receipt_id ) . '</a></p>';
	}


	/**
	 * Adds the Moneris receipt ID to an order.
	 *
	 * @since 3.0.2
	 * @deprecated since 3.4.0
	 *
	 * @param \WC_Order|mixed $order
	 * @return void
	 */
	public function addIDToOrderMeta( $order ) : void {

		wc_deprecated_function( __METHOD__, '3.4.0', __CLASS__ . '::append_moneris_id_to_order()' );

		$this->append_moneris_order_id_to_order_details( $order );
	}


	/**
	 * Determines if the gateway is properly configured to perform transactions.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public function is_configured() {
		return parent::is_configured() && $this->get_store_id() && $this->get_api_token() && $this->get_checkout_id();
	}


	/**
	 * Determines if the pay page is enabled.
	 *
	 * @since 3.4.0
	 *
	 * @return bool
	 */
	public function is_pay_page_enabled() : bool {
		global $wp_query;

		// prevents a redirect if we're already on the pay page or when adding payment method from account area
		if ( $wp_query && ( is_checkout_pay_page() || is_add_payment_method_page() ) ) {
			return false;
		}

		// check the selection in the gateway options
		$enabled = self::FORM_TYPE_PAY_PAGE === $this->get_form_type();

		/**
		 * Filters the setting to enable pay page.
		 *
		 * This filter was introduced in 3.2.0 before the gateway option was added in 3.3.0, so it's being kept to maintain backwards compatibility.
		 *
		 * @since 3.2.0
		 *
		 * @param bool $enabled true to enable Pay Page mode
		 * @param \WC_Gateway_Moneris_Checkout_Credit_Card $gateway
		 */
		return (bool) apply_filters( 'wc_payment_gateway_moneris_enable_pay_page', $enabled, $this );
	}


	/**
	 * Confirms if the Pay Page is enabled to process payments instead of Checkout page.
	 *
	 * @TODO remove this method by version 4.0.0 or by December 2024
	 *
	 * @deprecated since 3.4.0
	 * @since 3.2.0
	 *
	 * @return bool
	 */
	public function isPayPageEnabled() : bool {

		wc_deprecated_function( __METHOD__, '3.4.0', __CLASS__ . '::is_pay_page_enabled()' );

		return $this->is_pay_page_enabled();
	}


	/**
	 * Processes the payment by redirecting customer to the WooCommerce pay page if the pay page filter is enabled.
	 *
	 * @see \WC_Payment_Gateway::process_payment()
	 *
	 * @since 3.2.0
	 *
	 * @param int $order_id the order to process
	 * @return array with keys 'result' and 'redirect'
	 */
	public function process_payment( $order_id ) : array {

		// if we're on the checkout page and the pay page mode is enabled, redirect to the pay page
		if ( is_checkout() && ! is_checkout_pay_page() && $this->is_pay_page_enabled() ) {
			$order       = wc_get_order( $order_id );
			$payment_url = $order->get_checkout_payment_url( $this->is_pay_page_enabled() );

			if ( ! $payment_url ) {
				return ['result' => 'failure'];
			}

			return [
				'result'   => 'success',
				'redirect' => $payment_url,
			];
		}

		// in all other cases, process the payment as usual
		return parent::process_payment( $order_id );
	}


	/**
	 * Add any Moneris specific payment and transaction information as
	 * class members of WC_Order instance.  Added members can include:.
	 *
	 * $order->dynamic_descriptor - Merchant defined description sent on a per-transaction basis that will appear on the credit card statement appended to the merchant�s business name.
	 * $order->perform_avs - true if the avs data should be included with the transaction request
	 *
	 * @since 2.0.0
	 *
	 * @param int|\WC_Order $order order or order ID being processed
	 * @return \WC_Order
	 */
	public function get_order( $order ) {

		// add common order members
		$order = parent::get_order( $order );

		// add the configured dynamic descriptor
		$order->dynamic_descriptor = $this->get_dynamic_descriptor();

		// whether to include the avs fields
		$order->perform_avs = $this->avs_enabled();

		// add CSC if not added yet (if Hosted Tokenization is enabled and Saved Card Verification is disabled)
		$posted_csc = Framework\SV_WC_Helper::get_posted_value( 'wc-'.$this->get_id_dasherized().'-csc' );

		if ( empty( $order->payment->csc ) && ! empty( $posted_csc ) ) {
			$order->payment->csc = $posted_csc;
		}

		if ( empty( $order->payment->card_type ) ) {

			// determine the card type from the account number
			if ( ! empty( $order->payment->account_number ) ) {
				$account_number = $order->payment->account_number;
			} else {
				$account_number = Framework\SV_WC_Helper::get_posted_value( 'wc-'.$this->get_id_dasherized().'-card-bin' );
			}

			$order->payment->card_type = Framework\SV_WC_Payment_Gateway_Helper::card_type_from_account_number( $account_number );
		}

		if ( $this->is_test_environment() ) {

			// Add a suffix to the transaction ID to avoid "duplicate order ID" errors
			// during testing
			$order->unique_transaction_ref = $order->unique_transaction_ref.'-'.uniqid( '', true );

			// Test amount entered in enhanced payment form
			// @since 2.8.0
			if ( ( $test_amount = Framework\SV_WC_Helper::get_posted_value( 'wc-'.$this->get_id_dasherized().'-test-amount' ) ) ) {
				$order->payment_total = Framework\SV_WC_Helper::number_format( $test_amount );
			}
		}

		$order->payment->card_type = Framework\SV_WC_Payment_Gateway_Helper::normalize_card_type( $order->payment->card_type );

		return $order;
	}


	/**
	 * Adds transaction data to the order for Moneris Checkout orders.
	 *
	 * @since 2.0.0
	 *
	 * @param \WC_Order $order the order object
	 * @param \WC_Moneris_Checkout_API_Response|null $response API response object
	 * @return Framework\SV_WC_Payment_Gateway_API_Response API response object
	 * @throws Exception network timeouts, etc
	 */
	public function addMonerisCheckoutPaymentDataToOrder($order, $response = null) {
		$order->payment->account_number = $response->get_last_four();
		$order->payment->card_type      = $response->get_card_type();
		$order->payment->exp_month      = $response->get_exp_month();
		$order->payment->exp_year       = $response->get_exp_year();

		return $order;
	}


	/**
	 * Performs a credit card transaction for the given order and returns the
	 * result.
	 *
	 * @since 2.0.0
	 *
	 * @param \WC_Order $order the order object
	 * @param \WC_Moneris_API_Response|null $response API response object
	 * @return Framework\SV_WC_Payment_Gateway_API_Response $order the order object
	 * @throws Exception network timeouts, etc
	 */
	protected function do_credit_card_transaction( $order, $response = null ) {

		// handle Moneris checkout transactions
		if ( $this->has_ticket_number() && ! $this->is_card_already_saved() ) {

			$response = $this->get_checkout_api()->create_receipt_request( $this->get_ticket_number(), $order );

			// bail out from the e-fraud checks or capture if the transaction was disapproved by Moneris
			if ( is_null( $response ) || ( ! is_null( $response ) && ! $response->transaction_approved() ) ) {
				return parent::do_credit_card_transaction( $order, $response );
			}

			$order = $this->addMonerisCheckoutPaymentDataToOrder( $order, $response );

			// Tokenization is enabled / disabled within the Checkout Profile, so if it's enabled then every card
			// will be automatically tokenized. So we remove the token if the customer didn't indicate that they
			// wanted their card saved
			if (! $this->get_payment_tokens_handler()->should_tokenize() && $response->get_payment_token_id()) {
				$this->get_api()->remove_tokenized_payment_method( $response->get_payment_token_id(), $order->get_user_id() );
			}

			if ( $this->get_payment_tokens_handler()->should_tokenize() && $response->get_payment_token_id() ) {
				// pass the auth/tokenization response into the handler to use later
				$this->get_payment_tokens_handler()->set_tokenization_response( $response );
			}

			// store the original authorization transaction ID in case it's needed later
			$order->payment->auth_trans_id = $response->get_transaction_id();

			// get the combined efraud action
			$efraud_action = $this->get_efraud_action( $response->get_avs_result(), $response->get_csc_result(), $order, $response->get_3ds_result() );

			if ( 'accept' == $efraud_action ) {

				if ( $this->perform_credit_card_charge( $order ) ) {

					$order = $this->get_order_for_capture( $order );

					// complete the charge if needed
					$order->capture->trans_id   = $response->get_transaction_id();
					$order->capture->receipt_id = $response->get_receipt_id();

					$response = $this->get_api()->credit_card_capture( $order );

				} // otherwise just return the authorization response

			} elseif ( 'reject' == $efraud_action ) {

				$order = $this->get_order_for_capture( $order );

				// reverse the charge
				$order->capture->trans_id   = $response->get_transaction_id();
				$order->capture->receipt_id = $response->get_receipt_id();

				$this->get_api()->credit_card_authorization_reverse( $order );

				// mark the original response as failed since we've reversed the authorization
				$response->failed();

				$messages = [];

				if ( $this->has_avs_validations() && 'reject' == $this->get_avs_action( $response->get_avs_result() )) {
					$messages[] = sprintf( __( 'AVS %s', 'woocommerce-gateway-moneris' ), $this->get_avs_error_message( $response->get_avs_result() ) );
				}

				if ( $this->has_csc_validations() && 'reject' == $this->get_csc_action( $response->get_csc_result() )) {
					$messages[] = sprintf( __( 'CSC %s', 'woocommerce-gateway-moneris' ), $this->get_csc_error_message( $response->get_csc_result() ) );
				}

				if ( $this->is_3d_secure_enabled() && 'reject' == $this->get_3d_secure_action( $response->get_3ds_result() )) {
					$messages[] = sprintf( __( 'CAVV %s', 'woocommerce-gateway-moneris' ), __( 'authentication failed.', 'woocommerce-gateway-moneris' ) );
				}

				$response->set_status_message( implode( ', ', $messages ) );

				// card is tokenized by default in Moneris, so delete the tokenized card if the transaction is rejected due to e-fraud failure
				if ( $this->get_payment_tokens_handler()->should_tokenize() && $response->get_payment_token_id() ) {
					$this->get_api()->remove_tokenized_payment_method( $response->get_payment_token_id(), $order->get_user_id() );
				}

				return $response;

			} else {

				// mark the response as held
				$response->held();

				$messages = [];

				if ( $this->has_avs_validations() && 'hold' == $this->get_avs_action( $response->get_avs_result() )) {
					$messages[] = sprintf( __( 'AVS %s (result: %s)', 'woocommerce-gateway-moneris' ), $this->get_avs_error_message( $response->get_avs_result() ), $response->get_avs_result_code() );
				}

				if ( $this->has_csc_validations() && 'hold' == $this->get_csc_action( $response->get_csc_result() )) {
					$messages[] = sprintf( __( 'CSC %s (result: %s)', 'woocommerce-gateway-moneris' ), $this->get_csc_error_message( $response->get_csc_result() ), $response->get_csc_result_code() );
				}

				if ( $this->is_3d_secure_enabled() && 'hold' == $this->get_3d_secure_action( $response->get_3ds_result() )) {
					$messages[] = sprintf( __( 'CAVV %s', 'woocommerce-gateway-moneris' ), __( '(3Ds 2.0) authentication failed.', 'woocommerce-gateway-moneris' ) );
				}

				$message = __( 'Authorization', 'woocommerce-gateway-moneris' ).' '.implode( ', ', $messages );

				if ( $this->perform_credit_card_authorization( $order ) ) {
					// workaround
					$this->held_authorization_status_message = $message;
				} else {
					// this message will be added to the 'hold' order notes
					$response->set_status_message( $message );
				}

				return $response;
			}

			return parent::do_credit_card_transaction( $order, $response );
		}

		// look for a response attached to the order object
		$response = null === $response && ! empty( $order->payment->response ) ? $order->payment->response : $response;

		// normal operation if no avs checks in force or card does not support it, or there is already a payment receipt (from client-side processing)
		if ( $response || ! $this->has_efraud_validations() || ( $order->payment->card_type && ! $this->card_supports_efraud_validations( $order->payment->card_type ) ) ) {
			return parent::do_credit_card_transaction( $order, $response );
		}

		// we have at least one efraud condition, so we'll start by doing an authorization for the full amount
		$response = $this->get_api()->credit_card_authorization( $order );

		// authorization failure, we're done
		if ( ! $response->transaction_approved() ) {
			return $response;
		}

		// store the original authorization transaction ID in case it's needed later
		$order->payment->auth_trans_id = $response->get_transaction_id();

		// set the card type/account number for hosted tokenized (non-tokenization) transactions
		if ( ! $order->payment->card_type ) {
			$order->payment->card_type      = $response->get_card_type();
			$order->payment->account_number = $response->get_masked_pan();
		}

		$masked_pan = $response->get_masked_pan();

		// set the last four digits of the credit card for hosted tokenized (non-tokenization) transactions with efraud
		if ( empty( $order->payment->last_four ) && ! empty( $masked_pan ) ) {
			$order->payment->last_four = substr( $masked_pan, -4 );
		}

		// get the combined efraud action
		$efraud_action = $this->get_efraud_action( $response->get_avs_result(), $response->get_csc_result(), $order );

		if ( 'accept' == $efraud_action  ) {

			if ( $this->perform_credit_card_charge( $order )) {

				$order = $this->get_order_for_capture( $order );

				// complete the charge if needed
				$order->capture->trans_id   = $response->get_transaction_id();
				$order->capture->receipt_id = $response->get_receipt_id();

				$response = $this->get_api()->credit_card_capture( $order );
			} // otherwise just return the authorization response

		} elseif ( 'reject' == $efraud_action ) {
			$order = $this->get_order_for_capture( $order );

			// reverse the charge
			$order->capture->trans_id   = $response->get_transaction_id();
			$order->capture->receipt_id = $response->get_receipt_id();

			$this->get_api()->credit_card_authorization_reverse( $order );

			// mark the original response as failed since we've reversed the authorization
			$response->failed();

			$messages = [];

			if ( $this->has_avs_validations() && 'reject' == $this->get_avs_action( $response->get_avs_result() ) ) {
				$messages[] = sprintf( __( 'AVS %s (result: %s)', 'woocommerce-gateway-moneris' ), $this->get_avs_error_message( $response->get_avs_result() ), $response->get_avs_result_code() );
			}

			if ( $this->has_csc_validations() && 'reject' == $this->get_csc_action( $response->get_csc_result() ) ) {
				$messages[] = sprintf( __( 'CSC %s (result: %s)', 'woocommerce-gateway-moneris' ), $this->get_csc_error_message( $response->get_csc_result() ), $response->get_csc_result_code() );
			}

			$response->set_status_message( implode( ', ', $messages ) );

			// we really don't care whether the reversal succeeded, though it should have
			return $response;

		} else {

			// mark the response as held
			$response->held();

			$messages = [];

			if ( $this->has_avs_validations() && 'hold' == $this->get_avs_action( $response->get_avs_result() ) ) {
				$messages[] = sprintf( __( 'AVS %s (result: %s)', 'woocommerce-gateway-moneris' ), $this->get_avs_error_message( $response->get_avs_result() ), $response->get_avs_result_code() );
			}

			if ( $this->has_csc_validations() && 'hold' == $this->get_csc_action( $response->get_csc_result() ) ) {
				$messages[] = sprintf( __( 'CSC %s (result: %s)', 'woocommerce-gateway-moneris' ), $this->get_csc_error_message( $response->get_csc_result() ), $response->get_csc_result_code() );
			}

			$message = __( 'Authorization', 'woocommerce-gateway-moneris' ).' '.implode( ', ', $messages );

			if ( $this->perform_credit_card_authorization( $order ) ) {
				// workaround
				$this->held_authorization_status_message = $message;
			} else {
				// this message will be added to the 'hold' order notes
				$response->set_status_message( $message );
			}

			return $response;
		}

		// success! update order record
		return parent::do_credit_card_transaction( $order, $response );
	}


	/**
	 * Performs the transaction to add the customer's payment method to their account.
	 *
	 * @since 3.0.0
	 *
	 * @param WC_Order $order order object
	 * @param Framework\SV_WC_Payment_Gateway_API_Create_Payment_Token_Response|null $response optional payment token transaction response
	 * @return array result with success/error message and request status (success/failure)
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	protected function do_add_payment_method_transaction( WC_Order $order, Framework\SV_WC_Payment_Gateway_API_Create_Payment_Token_Response $response = null ) {

		if ( $this->has_ticket_number() ) {

			$response = $this->get_checkout_api()->create_receipt_request( $this->get_ticket_number(), $order );

			if ( $response->transaction_approved() ) {

				$token = $response->get_payment_token();

				// set the token to the user account
				$this->get_payment_tokens_handler()->add_token( $order->get_user_id(), $token );

				// order note based on gateway type
				if ( $this->is_credit_card_gateway() ) {

					/* translators: Payment method as in a specific credit card. Placeholders: %1$s - card type (visa, mastercard, ...), %2$s - last four digits of the card, %3$s - card expiry date */
					$message = sprintf( esc_html__( 'Nice! New payment method added: %1$s ending in %2$s (expires %3$s)', 'woocommerce-plugin-framework' ),
						$token->get_type_full(),
						$token->get_last_four(),
						$token->get_exp_date()
					 );

				} elseif ( $this->is_echeck_gateway() ) {

					// account type (checking/savings) may or may not be available, which is fine

					/* translators: Payment method as in a specific e-check account. Placeholders: %1$s - account type (checking/savings), %2$s - last four digits of the account */
					$message = sprintf( esc_html__( 'Nice! New payment method added: %1$s account ending in %2$s', 'woocommerce-plugin-framework' ),
						$token->get_account_type(),
						$token->get_last_four()
					);

				} else {

					/* translators: Payment method as in a specific credit card, e-check or bank account */
					$message = esc_html__( 'Nice! New payment method added.', 'woocommerce-plugin-framework' );
				}

				// add transaction data to user meta
				$this->add_add_payment_method_transaction_data( $response );

				// add customer data, primarily customer ID to user meta
				$this->add_add_payment_method_customer_data( $order, $response );

				/*
				 * Fires after a new payment method is added by a customer.
				 *
				 * @since 3.0.0
				 *
				 * @param string $token_id new token ID
				 * @param int $user_id user ID
				 * @param SV_WC_Payment_Gateway_API_Response $response API response object
				 */
				do_action( 'wc_payment_gateway_'.$this->get_id().'_payment_method_added', $token->get_id(), $order->get_user_id(), $response );

				$result = ['message' => $message, 'success' => true];
			} else {
				if ( $response->get_status_code() && $response->get_status_message() ) {
					$message = sprintf( 'Status code %s: %s', $response->get_status_code(), $response->get_status_message() );
				} elseif ( $response->get_status_code() ) {
					$message = sprintf( 'Status code: %s', $response->get_status_code() );
				} elseif ( $response->get_status_message() ) {
					$message = sprintf( 'Status message: %s', $response->get_status_message() );
				} else {
					$message = 'Unknown Error';
				}

				$result = ['message' => $message, 'success' => false];
			}

			return $result;
		} else {
			parent::do_add_payment_method_transaction( $order, $response );
		}
	}


	/**
	 * Builds the Subscriptions integration class instance.
	 *
	 * @since 3.0.0
	 *
	 * @return WC_Moneris_Payment_Gateway_Integration_Subscription
	 */
	protected function build_subscriptions_integration() {

		require_once $this->get_plugin()->get_plugin_path() . '/src/integration/class-wc-moneris-payment-gateway-integration-subscriptions.php';

		return new WC_Moneris_Payment_Gateway_Integration_Subscription( $this );
	}


	/**
	 * Marks the given order as 'on-hold', set an order note and display a message
	 * to the customer.
	 *
	 * TODO: this should hopefully be a temporary override, until we figure out a
	 * better way to handle the messaging for held authorize-only orders in the
	 * Framework\SV_WC_Payment_Gateway::do_transaction() method
	 *
	 * @since 2.0.0
	 *
	 * @param \WC_Order $order order object
	 * @param string $message a message to display within the order note
	 * @param Framework\SV_WC_Payment_Gateway_API_Response $response optional transaction response object
	 */
	public function mark_order_as_held( $order, $message, $response = null ) {

		// reset the capture eligibility as this may be a new authorization
		// for an order that previously had its authorization reversed
		$this->update_order_meta( $order, 'auth_can_be_captured', 'yes' );

		if ( ! is_null( $this->held_authorization_status_message ) ) {
			$message = $this->held_authorization_status_message;
		}

		parent::mark_order_as_held( $order, $message, $response );
	}


	/**
	 * Performs a credit card authorization reversal for the given order.
	 *
	 * @since 2.0.0
	 *
	 * @param \WC_Order $order order object
	 * @return null|Framework\SV_WC_Payment_Gateway_API_Response the response of the reversal attempt
	 */
	public function do_credit_card_reverse_authorization( $order ) {

		try {

			$response = $this->get_api()->credit_card_authorization_reverse( $this->get_order_for_capture( $order ) );

			if ( $response->transaction_approved() ) {

				$message = sprintf(
					__( '%s%s Authorization Reversed', 'woocommerce-gateway-moneris' ),
					$this->get_method_title(),
					$this->is_test_environment() ? ' '.__( 'Test', 'woocommerce-gateway-moneris' ) : ''
				);

				// adds the transaction id (if any) to the order note
				if ( $response->get_transaction_id() ) {
					$message .= ' '.sprintf( __( '(Transaction ID %s)', 'woocommerce-gateway-moneris' ), $response->get_transaction_id() );
				}

				// cancel the order.  since this results in an update to the post object we need to unhook the save_post action, otherwise we can get boomeranged and change the status back to on-hold
				$this->unhook_woocommerce_process_shop_order_meta();

				$this->mark_order_as_cancelled( $order, $message, $response );

				// once an authorization has been reversed, it cannot be captured again
				$this->update_order_meta( $order, 'auth_can_be_captured', 'no' );

			} else {

				$message = sprintf(
					__( '%s%s Authorization Reversal Failed: %s - %s', 'woocommerce-gateway-moneris' ),
					$this->get_method_title(),
					$this->is_test_environment() ? ' '.__( 'Test', 'woocommerce-gateway-moneris' ) : '',
					$response->get_status_code(),
					$response->get_status_message()
				);

				if ( $response->is_authorization_invalid() ) {

					// already reversed or captured, cancel the order.  since this results in an update to the post object we need to unhook the save_post action, otherwise we can get boomeranged and change the status back to on-hold
					$this->unhook_woocommerce_process_shop_order_meta();

					$this->mark_order_as_cancelled( $order, $message, $response );

					// mark the capture as invalid
					$this->update_order_meta( $order, 'auth_can_be_captured', 'no' );
				} else {
					$order->add_order_note( $message );
				}
			}

			return $response;

		} catch ( Exception $e ) {

			$message = sprintf(
				__( '%s%s Authorization Reversal Failed: %s', 'woocommerce-gateway-moneris' ),
				$this->get_method_title(),
				$this->is_test_environment() ? ' '.__( 'Test', 'woocommerce-gateway-moneris' ) : '',
				$e->getMessage()
			);

			$order->add_order_note( $message );

			return null;
		}
	}


	/**
	 * Gets the order object with additional properties needed for capture.
	 *
	 * @since 2.7.0
	 *
	 * @param \WC_Order|int $order the order object or ID
	 * @param float|null $amount capture amount
	 * @return \WC_Order
	 */
	public function get_order_for_capture( $order, $amount = null ) {

		$order = parent::get_order_for_capture( $order );

		$order->capture->receipt_id = $this->get_order_meta( $order, 'receipt_id' );

		return $order;
	}


	/**
	 * Gets the order object with additional properties needed for refunds.
	 *
	 * @since 2.8.0
	 *
	 * @param WC_Order|int $order order being processed
	 * @param float $amount refund amount
	 * @param string $reason optional refund reason text
	 * @return WC_Order object with refund information attached
	 */
	protected function get_order_for_refund( $order, $amount, $reason ) {

		$order = parent::get_order_for_refund( $order, $amount, $reason );

		$order->refund->receipt_id = $this->get_order_meta( $order, 'receipt_id' );

		// Check whether the charge has already been captured by this gateway
		$charge_captured = $this->get_order_meta( $order, 'charge_captured' );

		if ( 'yes' === $charge_captured ) {
			// For orders authorised, then captured, the transaction ID should be the
			// one from the "capture" operation
			$capture_trans_id = $this->get_order_meta( $order, 'capture_trans_id' );

			if ( ! empty( $capture_trans_id ) ) {
				$order->refund->trans_id = $capture_trans_id;
			}
		}

		return $order;
	}


	/**
	 * Called after an unsuccessful transaction attempt.
	 *
	 * @since 2.0.0
	 *
	 * @param \WC_Order $order order object
	 * @param Framework\SV_WC_Payment_Gateway_API_Response $response the transaction response
	 * @return bool
	 */
	protected function do_transaction_failed_result( WC_Order $order, Framework\SV_WC_Payment_Gateway_API_Response $response ) {

		// missing token, meaning local token is invalid, so delete it from the local datastore
		if ( ( method_exists( $response->get_request(), 'get_type' ) &&
			( 'res_preauth_cc' == $response->get_request()->get_type() || 'res_purchase_cc' == $response->get_request()->get_type() ) )
			&& 983 == $response->get_status_code() ) {
			$this->get_payment_tokens_handler()->remove_token( $order->get_user_id(), $order->payment->token );
		}

		return parent::do_transaction_failed_result( $order, $response );
	}


	/**
	 * Unhooks the core WooCommerce process shop order meta, so we can update
	 * the order status without causing the core WooCommerce code to fire and
	 * undo our change.
	 *
	 * @since 2.0
	 */
	private function unhook_woocommerce_process_shop_order_meta() {

		// Complete the order.
		// Since this results in an update to the post object we need to unhook the save_post action,
		// otherwise we can get boomeranged and change the status back to on-hold.
		remove_action( 'woocommerce_process_shop_order_meta', 'WC_Meta_Box_Order_Data::save', 40 );
	}


	/**
	 * Adds any gateway-specific transaction data to the order.
	 *
	 * @since 2.0.0
	 *
	 * @param \WC_Order $order order object
	 * @param \WC_Moneris_API_Response $response API response object
	 */
	public function add_payment_gateway_transaction_data( $order, $response ) {

		// record the integration country (ca). This is persisted for historical purposes
		$this->update_order_meta( $order, 'integration', \WC_Moneris::INTEGRATION_CA );

		// record the receipt ID (order number)
		$this->update_order_meta( $order, 'receipt_id', $response->get_receipt_id() );

		// record the transaction reference number
		if ( $response->get_reference_num() ) {
			$this->update_order_meta( $order, 'reference_num', $response->get_reference_num() );
		}

		// record the avs result code
		if ( $response->get_avs_result_code() ) {
			$this->update_order_meta( $order, 'avs', $response->get_avs_result_code() );
		}

		// record the csc validation code
		if ( $response->get_csc_result_code() ) {
			$this->update_order_meta( $order, 'csc', $response->get_csc_result_code() );
		}

		// record the issuer id for subsequent transactions
		if ( $response->get_issuer_id() ) {
			$this->update_order_meta( $order, 'issuer_id', $response->get_issuer_id() );
		}

		// get the masked pan from the last response
		$masked_pan = $response->get_masked_pan();

		// record the last four digits of the credit card
		if ( ! empty( $masked_pan ) ) {

			$this->update_order_meta( $order, 'account_four', substr( $masked_pan, -4 ) );

		} elseif ( ! empty( $order->payment->last_four ) ) {

			// get the last four saved from the previous response
			$this->update_order_meta( $order, 'account_four', $order->payment->last_four );
		}

		// If we're configured to perform a credit card charge, but a pre-auth was performed, this likely indicates an AVS failure.
		// Mark the  charge as not captured so it can be managed through the admin.
		if ( $this->perform_credit_card_charge( $order )
			&& method_exists( $response, 'get_request' )
			&& method_exists( $response->get_request(), 'get_type' )
			&& in_array( $response->get_request()->get_type(), ['preauth', 'res_preauth_cc'] )
		) {
			$this->update_order_meta( $order, 'charge_captured', 'no' );
		}

		// store the original transaction ID to be used to generate the transaction URL
		if ( ! empty( $order->payment->auth_trans_id ) ) {
			$this->update_order_meta( $order, 'auth_trans_id', $order->payment->auth_trans_id );

			// use the core method here since \SV_WC_Payment_Gateway::update_order_meta() prefixes the gateway ID
			$order->set_transaction_id( $order->payment->auth_trans_id );
			$order->save_meta_data();
		}
	}


	/**
	 * Returns true if tokenization takes place after an authorization/charge transaction.
	 *
	 * Moneris has both a post-transaction tokenization request, and a dedicated tokenization request.
	 *
	 * @since 3.0.0
	 *
	 * @return bool true if there is a tokenization request that is issued after an authorization/charge transaction
	 */
	public function tokenize_with_sale(): bool {

		return true;
	}


	/**
	 * Moneris does not support tokenization before sale, not even for $0 transactions.
	 *
	 * @since 2.0
	 *
	 * @param \WC_Order $order
	 * @return bool
	 */
	protected function should_tokenize_before_sale( \WC_Order $order ): bool {

		return false;
	}


	/**
	 * Return the Payment Tokens Handler class instance.
	 *
	 * @since 2.5.0
	 *
	 * @return \WC_Gateway_Moneris_Payment_Tokens_Handler
	 */
	protected function build_payment_tokens_handler() {

		return new \WC_Gateway_Moneris_Payment_Tokens_Handler( $this );
	}


	/**
	 * Returns true if the AVS checks should be performed when processing a payment.
	 *
	 * @since 2.0
	 *
	 * @return bool true if AVS is enabled
	 */
	public function avs_enabled() {

		return 'yes' == $this->enable_avs;
	}


	/**
	 * Returns true if the Apple Pay is enabled.
	 *
	 * @since 3.0.0
	 *
	 * @return bool true if Enable Apple Pay is enabled
	 */
	public function apple_pay_enabled() {

		return 'yes' == $this->enable_apple_pay;
	}


	/**
	 * Returns true if the Google Pay is enabled.
	 *
	 * @since 3.0.0
	 *
	 * @return bool true if Enable Google Pay is enabled
	 */
	public function google_pay_enabled() {
		return 'yes' == $this->enable_google_pay;
	}


	/**
	 * Returns true if the digital wallets are enabled.
	 *
	 * @since 3.0.0
	 * @return bool true if digital wallets are enabled
	 */
	public function has_digital_wallets_enabled() {
		return $this->google_pay_enabled() || $this->apple_pay_enabled();
	}


	/**
	 * Returns true if either AVS or CSC checks should be performed when
	 * processing a payment.
	 *
	 * @since 2.0
	 *
	 * @return bool true if AVS or CSC is enabled
	 */
	public function has_efraud_validations() {
		return $this->has_avs_validations() || $this->has_csc_validations();
	}


	/**
	 * Returns true if the given card type supports eFraud (AVS/CSC) validations.
	 *
	 * @since 2.0
	 *
	 * @param string $card_type the card type
	 * @return bool true if the card type supports AVS and CSC validations
	 */
	private function card_supports_efraud_validations( $card_type ) {

		$valid_types = [
			Framework\SV_WC_Payment_Gateway_Helper::CARD_TYPE_VISA,
			Framework\SV_WC_Payment_Gateway_Helper::CARD_TYPE_MASTERCARD,
			Framework\SV_WC_Payment_Gateway_Helper::CARD_TYPE_AMEX,
			Framework\SV_WC_Payment_Gateway_Helper::CARD_TYPE_DISCOVER,
			Framework\SV_WC_Payment_Gateway_Helper::CARD_TYPE_JCB,
		];

		return in_array( $card_type, $valid_types, true );
	}


	/**
	 * Returns true if avs checks are enabled, and at least one check is not simply 'accept'.
	 *
	 * @since 2.0
	 *
	 * @return true if avs checks are enabled
	 */
	public function has_avs_validations() {
		return $this->avs_enabled() && ( 'accept' != $this->avs_neither_match || 'accept' != $this->avs_zip_match || 'accept' != $this->avs_street_match || 'accept' != $this->avs_not_verified );
	}


	/**
	 * Returns the single action to take based on the AVS and CSC responses.
	 *
	 * @since 2.0.0
	 *
	 * @param string $avs_code the standardized avs code, one of 'Z', 'A', 'N', 'Y', 'U' or null
	 * @param string $csc_code the standardized csc code, one of 'M', 'N', 'U', or null
	 * @param \WC_Order $order
	 * @param string $three_d_secure_result the standardized csc code, one of 'Y', 'N', or null
	 * @return string one of 'accept', 'reject', 'hold'
	 */
	private function get_efraud_action( $avs_code, $csc_code, $order, $three_d_secure_result = '' ) {

		$actions = [];

		if (  $avs_code && $this->avs_enabled() ) {
			$actions[] = $this->get_avs_action( $avs_code );
		}

		if ( $three_d_secure_result && $this->is_3d_secure_enabled() ) {
			$actions[] = $this->get_3d_secure_action( $three_d_secure_result );
		}

		// Only get the CSC action if there is a response code and:
		//     a. the csc is required
		//     b. this isn't a saved method transaction
		//     c. a csc code was passed in (for a saved payment method, from the checkout form)
		// This avoids rejections for situations where a CSC is not a factor,
		// like for a saved method without CSC enabled for saved cards, or
		// subscription renewals without CSC being required for all transactions
		if ( $csc_code && $this->csc_enabled() && ( $this->csc_required() || empty( $order->payment->token ) || Framework\SV_WC_Helper::get_posted_value( 'wc-moneris-csc' ) ) ) {
			$actions[] = $this->get_csc_action( $csc_code );
		}

		// rejection conquers all
		if ( in_array( 'reject', $actions ) ) {
			return 'reject';
		}

		// hold beats accept
		if ( in_array( 'hold', $actions ) ) {
			return 'hold';
		}

		// if all good
		return 'accept';
	}


	/**
	 * Returns the action to take based on the settings configuration and standardized $avs_code.
	 *
	 * @since 2.0
	 *
	 * @param string $avs_code the standardized avs code, one of 'Z', 'A', 'N', 'Y', 'U' or null
	 * @return string one of 'accept', 'reject', 'hold'
	 */
	private function get_avs_action( $avs_code ) {

		switch ( $avs_code ) {
			// zip match, locale no match
			case 'Z': return $this->avs_zip_match;

			// zip no match, locale match
			case 'A': return $this->avs_street_match;

			// zip no match, locale no match
			case 'N': return $this->avs_neither_match;

			// zip match, locale match
			case 'Y': return 'accept';

			// zip and locale could not be verified
			case 'U': return $this->avs_not_verified;
		}

		// unknown card type or unknown result, mark as approved
		return 'accept';
	}


	/**
	 * Returns the action to take for 3Ds secure 2.0 results.
	 *
	 * @since 3.4.0
	 *
	 * @param string|mixed|null $three_d_secure_result the standardized avs code, one of 'Y', any or null
	 * @return string one of 'accept', 'reject', 'hold'
	 */
	private function get_3d_secure_action( $three_d_secure_result ) : string {

		switch ( $three_d_secure_result ) {

			// 3DS checks pass
			case 'A':
			case 'Y':
				$three_d_secure_action = 'accept';
			break;

			default:
				$three_d_secure_action = 'reject';
			break;
		}

		/**
		 * Filters the 3D Secure action to take based on the result.
		 *
		 * @since 3.4.1
		 *
		 * @param string $three_d_secure_action
		 * @param string $three_d_secure_result
		 */
		return (string) apply_filters( 'wc_payment_gateway_' . $this->get_id() . '_three_d_secure_action', $three_d_secure_action, $three_d_secure_result );
	}


	/**
	 * Returns an error message based on the $avs_code.
	 *
	 * @since 2.0
	 *
	 * @param string $avs_code the unified AVS error code, one of 'Z', 'A', 'N', 'U'
	 * @return string message based on the code
	 */
	private function get_avs_error_message( $avs_code ) {

		switch ( $avs_code ) {
			// zip match, locale no match
			case 'Z': return __( 'postal code match, street address no match', 'woocommerce-gateway-moneris' );

			// zip no match, locale match
			case 'A': return __( 'postal code no match, street address match', 'woocommerce-gateway-moneris' );

			// zip no match, locale no match
			case 'N': return __( 'postal code no match, street address no match', 'woocommerce-gateway-moneris' );

			// zip and locale could not be verified
			case 'U': return __( 'could not be verified', 'woocommerce-gateway-moneris' );
		}

		return __( 'unknown error', 'woocommerce-gateway-moneris' );
	}


	/**
	 * Returns the action to take based on the settings configuration and
	 * standardized $csc_code.
	 *
	 * @since 2.0
	 *
	 * @param string|null|mixed $csc_code the standardized avs code, one of 'M', 'N', 'U', or null
	 * @return string one of 'accept', 'reject', 'hold'
	 */
	private function get_csc_action( $csc_code ) {

		switch ( $csc_code ) {
			// match
			case 'M':
			// unsupported card
			default:
				return 'accept';

			// no match
			case 'N':
				return $this->csc_not_match;

			// could not be verified, or unknown result code
			case 'U':
				return $this->csc_not_verified;
		}
	}


	/**
	 * Returns an error message based on the $csc_code.
	 *
	 * @since 2.0
	 *
	 * @param string $csc_code the unified CSC error code, one of 'N', 'U'
	 * @return string message based on the code
	 */
	private function get_csc_error_message( $csc_code ) {

		switch ( $csc_code ) {
			// no match
			case 'N':
				return __( 'no match', 'woocommerce-gateway-moneris' );

			// zip and locale could not be verified
			case 'U':
				return __( 'could not be verified', 'woocommerce-gateway-moneris' );
		}

		return __( 'invalid', 'woocommerce-gateway-moneris' );
	}


	/**
	 * Returns true if CSC checks are enabled, and at least one check is not simply 'accept'.
	 *
	 * @since 2.0
	 *
	 * @return bool, true if csc checks are enabled
	 */
	private function has_csc_validations() : bool {

		return $this->csc_enabled() && ( 'accept' !== $this->csc_not_match || 'accept' !== $this->csc_not_verified );
	}


	/**
	 * Returns true if the CSC is required for all transactions, including tokenized.
	 *
	 * @since 2.0
	 *
	 * @return bool true if the CSC is required for all transactions, even tokenized
	 */
	public function csc_required() : bool {

		return $this->csc_enabled() && 'yes' == $this->require_csc;
	}


	/**
	 * Returns true if 3D Secure 2.0 is required for all transactions.
	 *
	 * @TODO remove this method by version 4.0.0 or by December 2024
	 *
	 * @return bool
	 * @deprecated since 3.4.0
	 *
	 * @since 3.0.0
	 */
	public function is3dsEnabled() : bool {

		wc_deprecated_function( __METHOD__, '3.4.0', __CLASS__ . '::is_3d_secure_enabled()' );

		return $this->is_3d_secure_enabled();
	}


	/**
	 * Determines if 3D Secure is enabled.
	 *
	 * @since 3.4.0
	 *
	 * @return bool
	 */
	public function is_3d_secure_enabled() : bool {

		return 'yes' === $this->enable_3d_secure;
	}


	/** Subscriptions ******************************************************/


	/**
	 * Tweaks the labels shown when editing the payment method for a Subscription.
	 *
	 * @hooked from Framework\SV_WC_Payment_Gateway_Integration_Subscriptions
	 *
	 * @since 2.3.2
	 *
	 * @param array $meta payment meta
	 * @param \WC_Subscription $subscription subscription being edited, unused
	 * @return array
	 */
	public function subscriptions_admin_add_payment_meta( $meta, $subscription ) {

		if ( isset( $meta[ $this->get_id() ] ) ) {
			$meta[ $this->get_id() ]['post_meta'][ $this->get_order_meta_prefix() . 'payment_token' ]['label'] = __( 'Data Key', 'woocommerce-gateway-moneris' );
		}

		return $meta;
	}


	/**
	 * Returns meta keys to be excluded when copying over meta data when:.
	 *
	 * + a renewal order is created from a subscription
	 * + the user changes their payment method for a subscription
	 * + processing the upgrade from Subscriptions 1.5.x to 2.0.x
	 *
	 * @since 2.3.2
	 * @param array $meta_keys
	 * @return array
	 */
	public function subscriptions_get_excluded_order_meta_keys( $meta_keys ) {

		$meta_keys[] = $this->get_order_meta_prefix() . 'integration';
		$meta_keys[] = $this->get_order_meta_prefix() . 'receipt_id';
		$meta_keys[] = $this->get_order_meta_prefix() . 'reference_num';
		$meta_keys[] = $this->get_order_meta_prefix() . 'avs';
		$meta_keys[] = $this->get_order_meta_prefix() . 'csc';

		return $meta_keys;
	}


	/**
	 * Determines if this gateway with its current configuration supports subscriptions.
	 *
	 * Requiring CSC for all transactions removes support for subscriptions.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public function supports_subscriptions() {
		return parent::supports_subscriptions() && ! $this->csc_required();
	}


	/**
	 * Determines if this gateway with its current configuration supports pre-orders.
	 *
	 * Requiring CSC for all transactions removes support for pre-orders.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public function supports_pre_orders() {
		return parent::supports_pre_orders() && ! $this->csc_required();
	}


	/** Pay Page Hosted Tokenization methods **********************************/


	/**
	 * Mark the given order as failed and set the order note.
	 *
	 * @since 2.0
	 *
	 * @param WC_Order $order the order
	 * @param string $error_message a message to display inside the "Payment Failed" order note
	 */
	protected function mark_order_as_failed_quiet( $order, $error_message ) {

		$order_note = sprintf( _x( '%s Payment Failed (%s)', 'Order Note: (Payment method) Payment failed (error)', 'woocommerce-gateway-moneris' ), $this->get_method_title(), $error_message );

		// Mark order as failed if not already set, otherwise, make sure we add the order note so we can detect when someone fails to check out multiple times
		if ( ! $order->has_status( 'failed' ) ) {
			$order->update_status( 'failed', $order_note );
		} else {
			$order->add_order_note( $order_note );
		}

		$this->add_debug_message( $error_message, 'error' );

		// shhhh quiet like
		// wc_add_notice( __( 'An error occurred, please try again or try an alternate form of payment.', 'woocommerce-gateway-moneris' ), 'error' );
	}


	/** Getter methods ******************************************************/


	/**
	 * Gets the API instance.
	 *
	 * @since 2.0.0
	 *
	 * @return \WC_Moneris_API API instance
	 */
	public function get_api() {

		if ( isset( $this->api ) ) {
			return $this->api;
		}

		require_once $this->get_plugin()->get_plugin_path().'/src/api/class-wc-moneris-api.php';
		require_once $this->get_plugin()->get_plugin_path().'/src/api/class-wc-moneris-api-request.php';
		require_once $this->get_plugin()->get_plugin_path().'/src/api/class-wc-moneris-api-response.php';
		require_once $this->get_plugin()->get_plugin_path().'/src/api/class-wc-moneris-api-create-payment-token-response.php';
		require_once $this->get_plugin()->get_plugin_path().'/src/api/class-wc-moneris-api-delete-payment-token-response.php';
		require_once $this->get_plugin()->get_plugin_path().'/src/api/class-wc-moneris-api-receipt-response.php';

		require_once $this->get_plugin()->get_plugin_path().'/src/api/class-wc-moneris-api-response-message-helper.php';

		return $this->api = new WC_Moneris_API( $this->get_id(), $this->get_api_endpoint(), $this->get_store_id(), $this->get_api_token() );
	}


	/**
	 * Gets the Checkout API instance.
	 *
	 * @since 3.0.0
	 *
	 * @return \WC_Moneris_Checkout_API API instance
	 */
	public function get_checkout_api() {

		if (isset( $this->checkoutAPI )) {
			return $this->checkoutAPI;
		}

		require_once $this->get_plugin()->get_plugin_path().'/src/api/class-wc-moneris-api-request.php';
		require_once $this->get_plugin()->get_plugin_path().'/src/api/checkout/class-wc-moneris-checkout-api.php';
		require_once $this->get_plugin()->get_plugin_path().'/src/api/checkout/class-wc-moneris-checkout-api-request.php';
		require_once $this->get_plugin()->get_plugin_path().'/src/api/checkout/class-wc-moneris-checkout-api-response.php';
		require_once $this->get_plugin()->get_plugin_path().'/src/api/class-wc-moneris-api-response.php';
		require_once $this->get_plugin()->get_plugin_path().'/src/api/class-wc-moneris-api-delete-payment-token-response.php';

		require_once $this->get_plugin()->get_plugin_path().'/src/api/class-wc-moneris-api-response-message-helper.php';

		return $this->checkoutAPI = new WC_Moneris_Checkout_API( $this->get_id(), $this->get_store_id(), $this->get_api_token(), $this->get_checkout_id(), $this->is_test_environment() ? WC_Moneris_Checkout_API::ENVIRONMENT_STAGING : WC_Moneris_Checkout_API::ENVIRONMENT_PRODUCTION );
	}


	/**
	 * Gets the checkout block integration instance.
	 *
	 * @since 3.4.0
	 *
	 * @return Framework\Payment_Gateway\Blocks\Gateway_Checkout_Block_Integration
	 */
	public function get_checkout_block_integration_instance() : Framework\Payment_Gateway\Blocks\Gateway_Checkout_Block_Integration {

		if ( null === $this->moneris_checkout_credit_card_checkout_block_integration ) {

			require_once( $this->get_plugin()->get_plugin_path() . '/src/Blocks/Moneris_Checkout_Credit_Card_Checkout_Block_Integration.php' );

			$this->moneris_checkout_credit_card_checkout_block_integration = new Moneris_Checkout_Credit_Card_Checkout_Block_Integration( $this->get_plugin(), $this );
		}

		return $this->moneris_checkout_credit_card_checkout_block_integration;
	}


	/**
	 * Creates a checkout request for preload/receipt requests.
	 *
	 * @since 3.0.0
	 *
	 * @return null|WC_Moneris_Checkout_API_Response the response of the reversal attempt
	 */
	public function create_checkout_request() {

		try {

			/** @var WC_Moneris_Checkout_API_Response $response */
			$response = $this->get_checkout_api()->create_checkout_request();

			if ( ! $response->is_successful() ) {
				return null;
			}

			return $response;

		} catch ( Exception $exception ) {

			$this->get_plugin()->log( $exception->getMessage(), $this->get_id() );

			return null;
		}
	}


	/**
	 * Creates a checkout request for preload/receipt requests.
	 *
	 * @since 3.0.0
	 * @deprecated since 3.4.0
	 *
	 * @return null|WC_Moneris_Checkout_API_Response the response of the reversal attempt
	 */
	public function createCheckoutRequest() {

		wc_deprecated_function( __METHOD__, '3.4.0', __CLASS__ . '::create_checkout_request()' );

		return $this->create_checkout_request();
	}


	/**
	 * Returns the Moneris host given $environment and $integration.
	 *
	 * @version 3.0.0
	 *
	 * @param string|null $environment optional environment id, one of 'test' or 'production'. Defaults to currently configured environment
	 * @return string moneris host based on the environment and integration
	 */
	public function get_moneris_host( $environment = null ) : string {

		// get parameter defaults
		if ( is_null( $environment ) ) {
			$environment = $this->get_environment();
		}

		if ( $this->is_production_environment( $environment )) {
			return \WC_Moneris::PRODUCTION_URL_ENDPOINT_CA;
		} else {
			return WC_Moneris::TEST_URL_ENDPOINT_CA;
		}
	}


	/**
	 * Returns the configured payment form type, i.e. whether the payment form should be rendered inline on the Checkout page or on a separate Pay page.
	 *
	 * @since 3.3.0
	 * @return string the form type
	 */
	public function get_form_type() : string {
		return $this->form_type ?: self::FORM_TYPE_INLINE;
	}


	/**
	 * Returns the API endpoint based on the environment.
	 *
	 * @since 2.0
	 *
	 * @return string current API endpoint URL
	 */
	public function get_api_endpoint() : string {

		return $this->get_moneris_host() . '/gateway2/servlet/MpgRequest';
	}


	/**
	 * Returns the configured dynamic descriptor.
	 *
	 * @since 2.0
	 *
	 * @return string the dynamic descriptor
	 */
	public function get_dynamic_descriptor() : string {

		return substr( $this->dynamic_descriptor, 0, self::DYNAMIC_DESCRIPTOR_MAX_LENGTH );
	}


	/**
	 * Gets the currently configured store ID, based on the current environment.
	 *
	 * @since 2.0
	 * @version 3.0.0
	 *
	 * @return string the current store id
	 */
	public function get_store_id() : string {

		if ( $this->is_production_environment() ) {
			$store_id = $this->store_id;
		} else {
			$store_id = $this->ca_test_store_id;
		}

		return $store_id;
	}


	/**
	 * Gets the currently configured checkout ID, based on the current environment.
	 *
	 * @since 3.0.0
	 *
	 * @return string the current checkout id
	 */
	public function get_checkout_id() : string {

		if ( $this->is_production_environment() ) {
			$checkout_id = $this->checkout_id;
		} else {
			$checkout_id = $this->ca_test_checkout_id;
		}

		return $checkout_id ?? '';
	}


	/**
	 * Returns the currently configured API token, based on the current environment.
	 *
	 * @since 2.0
	 * @version 3.0.0
	 *
	 * @return string the current API token
	 */
	public function get_api_token() : string {

		if ( $this->is_production_environment() ) {
			$api_token = $this->api_token;
		} else {
			$api_token = $this->ca_test_api_token;

			// fallback on default value
			if ( empty( $api_token ) ) {
				$api_token = $this->get_default_test_api_token( $this->get_store_id() );
			}
		}

		return $api_token;
	}


	/**
	 * Gets a list of card types accepted by Moneris.
	 *
	 * @see Framework\SV_WC_Payment_Gateway_Direct::get_available_card_types()
	 *
	 * @since 3.2.3
	 *
	 * @return string[] array of accepted card types, ie 'VISA', 'MC', 'AMEX', etc
	 */
	public function get_card_types() : array {

		return [
			'VISA' => esc_html_x( 'Visa', 'credit card type', 'woocommerce-plugin-framework' ),
			'MC'   => esc_html_x( 'MasterCard', 'credit card type', 'woocommerce-plugin-framework' ),
			'AMEX' => esc_html_x( 'American Express', 'credit card type', 'woocommerce-plugin-framework' ),
			'DISC' => esc_html_x( 'Discover', 'credit card type', 'woocommerce-plugin-framework' ),
			'JCB'  => esc_html_x( 'JCB', 'credit card type', 'woocommerce-plugin-framework' ),
			'UPAY' => esc_html_x( 'UnionPay', 'credit card type', 'woocommerce-plugin-framework' ),
		];
	}


	/**
	 * Wrapper function to override framework method so card types can be displayed in admin payment token editor.
	 *
	 * The option for displaying card logos at checkout has moved to the Checkout Profile, so we no longer need to defer to $available_card_types from the gateway settings.
	 *
	 * @see Framework\SV_WC_Payment_Gateway::get_available_card_types()
	 *
	 * @since 3.2.3
	 *
	 * @return string[] array of accepted card types, ie 'VISA', 'MC', 'AMEX', etc
	 */
	public function get_available_card_types() : array {
		return $this->get_card_types();
	}


	/**
	 * Determines if the posted credit card fields are valid or not.
	 *
	 * @since 3.0.0
	 *
	 * @param bool $valid whether credit card fields are valid
	 * @return bool
	 */
	protected function validate_credit_card_fields( $valid ) : bool {

		return $this->has_ticket_number();
	}


	/**
	 * Gets the ticket number from the posted data.
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	public function get_ticket_number() : string {

		$ticket = Framework\SV_WC_Helper::get_posted_value( 'wc_' . $this->get_id() . '_ticket' );

		return is_string( $ticket ) ? $ticket : '';
	}


	/**
	 * Gets the ticket number from posted data.
	 *
	 * @since 3.0.0
	 * @deprecated since 3.4.0
	 *
	 * @TODO remove this method by version 4.0.0 or by January 2025 {unfulvio 2024-01-17}
	 *
	 * @return string
	 */
	public function getTicket() : string {

		wc_deprecated_function( __METHOD__, '3.4.0', __CLASS__ . '::get_ticket_number()' );

		return $this->get_ticket_number();
	}


	/**
	 * Determines if a ticket number was posted.
	 *
	 * @since 3.4.0
	 *
	 * @return bool
	 */
	public function has_ticket_number() : bool {

		return ! empty( $this->get_ticket_number() );
	}


	/**
	 * Determines if a ticket number was posted.
	 *
	 * @since 3.0.0
	 * @deprecated since 3.4.0
	 *
	 * @TODO remove this method by version 4.0.0 or by January 2025 {unfulvio 2024-01-17}
	 *
	 * @return bool
	 */
	public function hasTicketNumber() : bool {

		wc_deprecated_function( __METHOD__, '3.4.0', __CLASS__ . '::has_ticket_number()' );

		return $this->has_ticket_number();
	}


	/**
	 * Gets a test API token for the given store ID.
	 *
	 * @since 2.13.0
	 * @version 3.0.0
	 *
	 * @param string $store_id the store ID for one of Moneris test accounts
	 * @return string
	 */
	public function get_default_test_api_token( string $store_id ) : string {

		/* @link https://developer.moneris.com/en/More/Testing/Testing%20a%20Solution */
		switch ( $store_id ) {
			case 'monca00392':
				$api_token = 'qYdISUhHiOdfTr1CLNpN';
			break;
			case 'moncaqagt1':
				$api_token = 'mgtokenguy1';
			break;
			case 'moncaqagt2':
				$api_token = 'mgtokenguy2';
			break;
			case 'moncaqagt3':
				$api_token = 'mgtokenguy3';
			break;
			case 'moneris':
				$api_token = 'hurgle';
			break;
			case 'store1':
				$api_token = 'yesguy1';
			break;
			case 'store2':
			case 'store3':
			case 'store4':
			case 'store5':
			default:
				$api_token = 'yesguy';
			break;
		}

		return $api_token;
	}


	/**
	 * Initializes the payment form instance.
	 *
	 * @since 2.14.0
	 *
	 * @return \WC_Moneris_Payment_Form
	 */
	public function init_payment_form_instance() {
		return new \WC_Moneris_Payment_Form( $this );
	}


	/**
	 * Renders the payment fields.
	 *
	 * @since 3.2.0
	 *
	 * @see WC_Payment_Gateway::payment_fields()
	 * @see SV_WC_Payment_Gateway_Payment_Form class
	 */
	public function payment_fields(): void {

		if ( $this->is_pay_page_enabled() && ! is_add_payment_method_page() ) {

			$description = $this->get_description();

			if ( $description ) {
				echo wpautop( wptexturize( $description ) );
			}

		} else {

			parent::payment_fields();
		}
	}


	/**
	 * Outputs the payment form on the standalone payment page.
	 *
	 * @since 3.4.0
	 *
	 * @param int $order_id order object or ID
	 *
	 * @return void
	 */
	public function payment_page( $order_id ): void {

		?>
		<form id="order_review" method="post">
			<div id="payment">
				<div class="payment_box payment_method_<?php echo esc_attr( $this->get_id() ); ?>">
					<?php $this->get_payment_form_instance()->render(); ?>
				</div>
			</div>
			<button type="submit" id="place_order" class="button alt"><?php echo esc_html( $this->get_order_button_text() ); ?></button>
			<input type="radio" name="payment_method" value="<?php echo esc_attr( $this->get_id() ); ?>" />
			<input type="hidden" name="woocommerce_pay" value="1" />
			<?php wp_nonce_field( 'woocommerce-pay', 'woocommerce-pay-nonce' ); ?>
		</form>
		<?php
	}


	/**
	 * Returns true if this is a pay page type gateway.
	 *
	 * @since 3.2.0
	 *
	 * @return bool
	 */
	public function is_pay_page_gateway(): bool {

		return $this->is_pay_page_enabled();
	}


	/**
	 * Gets the order button text:
	 *
	 * Inline mode: "Place order"
	 * Pay page mode: "Continue to Payment"
	 *
	 * Overrides the parent method to allow using a different text for the pay page, while still maintaining the ability to filter the text.
	 *
	 * Note: this method depends on the gateway settings being loaded.
	 *
	 * @see \SV_WC_Payment_Gateway::get_order_button_text()
	 *
	 * @since 3.4.0
	 *
	 * @return string
	 */
	protected function get_order_button_text(): string {
		global $wp_query;

		$text = $this->is_pay_page_enabled() && ( ! $wp_query || ! is_checkout_pay_page() ) ? esc_html__( 'Continue to Payment', 'woocommerce-gateway-moneris' ) : esc_html__( 'Place order', 'woocommerce-gateway-moneris' );

		/**
		 * Payment Gateway Place Order Button Text Filter.
		 *
		 * Allow actors to modify the "place order" button text.
		 *
		 * @since 3.4.0
		 *
		 * @param string $text button text
		 * @param \WC_Gateway_Moneris_Checkout_Credit_Card $this instance
		 */
		return apply_filters( 'wc_payment_gateway_' . $this->get_id() . '_order_button_text', $text, $this );
	}


	/**
	 * Process a void.
	 *
	 * @since 2.8.0
	 *
	 * @param WC_Order $order order object (with refund class member already added)
	 * @return bool|WP_Error true on success, or a WP_Error object on failure/error
	 */
	protected function process_void( WC_Order $order ) {

		// Remove the action that triggers the reverse authorization. This will
		// prevent the operation from being performed twice
		remove_action( 'woocommerce_order_status_on-hold_to_cancelled', [$this, 'maybe_reverse_authorization'] );
		remove_action( 'woocommerce_order_action_'.$this->get_plugin()->get_id().'_reverse_authorization', [$this, 'maybe_reverse_authorization'] );

		/* In Moneris, voids for pre-auth transactions must be processed as credit
		 * card authorisation reversals. Such operation requires the same meta data
		 * that is used for capture, hence the call to get_order_for_capture().
		 *
		 * @link https://developer.moneris.com/Documentation/NA/E-Commerce%20Solutions/API/Purchase%20Correction?lang=php
		 */
		$order = $this->get_order_for_capture( $order );

		return parent::process_void( $order );
	}


	/**
	 * Whether the client is paying with already saved card.
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function is_card_already_saved(): bool {

		return ! empty( Framework\SV_WC_Helper::get_posted_value( 'wc-'.$this->get_id_dasherized().'-payment-token' ) );
	}


}
