<?php
/**
 * The main plugin class.
 *
 * Copyright (C) 2018 Invincible Brands GmbH
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @package WCSSOT
 * @subpackage Includes
 * @since 0.0.1
 */

namespace WCSSOT;

use DateTime;
use DateTimeZone;
use Exception;
use WC_Order;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The main plugin class WCSSOT.
 *
 * @since 0.0.1
 *
 * @class WCSSOT
 */
final class WCSSOT {
	/** @var array $options */
	private $options = [];

	/** @var array $options_required */
	private $options_required = [
		'wcssot_api_base_url',
		'wcssot_api_access_key',
		'wcssot_tracking_page_base_url',
	];

	/** @var DateTimeZone $timezone */
	private $timezone;

	/** @var WCSSOT_API_Manager $api */
	private $api;

	/**
	 * WCSSOT constructor.
	 *
	 * @since 0.0.1
	 */
	public function __construct() {
		WCSSOT_Logger::debug( 'Initialising the main WCSSOT plugin class.' );
		$this->initialise_properties();
		$this->initialise_hooks();
	}

	/**
	 * Initialises the class properties.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	private function initialise_properties() {
		$this->set_options( get_option( 'wcssot_settings', [] ) );
		try {
			$this->set_timezone( new DateTimeZone( wc_timezone_string() ) );
		} catch ( Exception $exception ) {
			WCSSOT_Logger::error( 'Could not instantiate shop timezone for "' . wc_timezone_string() . '".' );

			return;
		}
		$this->set_api( new WCSSOT_API_Manager(
			$this->get_option( 'wcssot_api_base_url' ),
			$this->get_option( 'wcssot_api_access_key' )
		) );
	}

	/**
	 * Returns the specifies option key from the options property.
	 *
	 * @since 0.2.0
	 *
	 * @param string $option
	 * @param null $default
	 *
	 * @return mixed|null
	 */
	public function get_option( $option, $default = null ) {
		$options = $this->get_options();

		return isset( $options[ $option ] ) ? $options[ $option ] : $default;
	}

	/**
	 * Returns the options property.
	 *
	 * @since 0.2.0
	 *
	 * @return array
	 */
	public function get_options() {
		return $this->options;
	}

	/**
	 * Sets the options property.
	 *
	 * @since 0.2.0
	 *
	 * @param array $options
	 *
	 * @return void
	 */
	public function set_options( $options ) {
		$this->options = $options;
	}

	/**
	 * Initialises the required hooks for the plugin.
	 *
	 * @since 0.0.1
	 *
	 * @return void
	 */
	private function initialise_hooks() {
		WCSSOT_Logger::debug( 'Initialising hooks for the WCSSOT main class.' );
		add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
		if ( is_admin() ) {
			WCSSOT_Logger::debug( 'Initialising hooks for the administration panel.' );
			add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
			add_action( 'admin_init', [ $this, 'register_admin_settings' ] );
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
		}
		if ( ! $this->settings_exist() ) {
			return;
		}
		add_action( 'woocommerce_order_status_processing', [ $this, 'export_order' ], 10, 2 );
		add_action( 'woocommerce_order_status_completed', [ $this, 'export_shipment' ], 10, 2 );
		add_action( 'woocommerce_email_before_order_table', [ $this, 'render_tracking_information' ], 10, 1 );
	}

	/**
	 * Returns whether the required settings have been set.
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	private function settings_exist() {
		WCSSOT_Logger::debug( 'Checking if all required settings exist.' );
		foreach ( $this->options_required as $option_required ) {
			if ( empty( $this->options[ $option_required ] ) ) {
				WCSSOT_Logger::error( "The setting '$option_required' is missing from the options!" );

				return false;
			}
		}

		return true;
	}

	/**
	 * Renders the tracking information to the "Completed Order" email content.
	 *
	 * @since 0.4.1
	 *
	 * @param WC_Order $order
	 *
	 * @return void
	 */
	public function render_tracking_information( $order ) {
		$shipment_exported = $order->get_meta( 'wcssot_shipment_exported' );
		$tracking_link     = $order->get_meta( 'wcssot_order_tracking_link' );
		$carrier           = $this->get_shipping_carrier( $order );
		$tracking_code     = $this->get_shipping_tracking_code( $order );
		if ( empty( $shipment_exported ) || empty( $tracking_link ) || empty( $carrier ) ) {
			WCSSOT_Logger::error( 'Could not render tracking information for order #' . $order->get_id() . '.' );

			return;
		}
		$supported_carriers = $this->get_api()->get_supported_carriers();
		if ( empty( $supported_carriers[ $carrier ] ) ) {
			WCSSOT_Logger::error( 'Carrier information could not be found for order #' . $order->get_id() . '.' );

			return;
		}
		$carrier = $supported_carriers[ $carrier ]['name'];
		$text    = __(
			'<a href="%1$s" target="_blank">Click here to see the status of your %2$s shipment.</a>',
			'woocommerce-seven-senders-order-tracking'
		);
		$text    = wp_kses( $text, [
			'a' => [
				'href'   => [],
				'target' => [],
			]
		] );
		$text    = '<p>' . sprintf( $text, $tracking_link, $carrier ) . '<br />';
		$text    .= sprintf( esc_html__( 'Your tracking code is: %s', 'woocommerce-seven-senders-order-tracking' ), $tracking_code );
		$text    .= '</p>';

		echo $text;
	}

	/**
	 * Returns the shipping carrier for the provided order.
	 *
	 * @since 0.3.0
	 *
	 * @param WC_Order $order
	 *
	 * @return string
	 */
	private function get_shipping_carrier( WC_Order $order ) {
		return (string) $order->get_meta( 'wcssot_shipping_carrier' );
	}

	/**
	 * Returns the shipping tracking code for the provided order.
	 *
	 * @since 0.3.0
	 *
	 * @param WC_Order $order
	 *
	 * @return string
	 */
	private function get_shipping_tracking_code( WC_Order $order ) {
		return (string) $order->get_meta( 'wcssot_shipping_tracking_code' );
	}

	/**
	 * Returns the API manager instance.
	 *
	 * @since 0.2.0
	 *
	 * @return WCSSOT_API_Manager
	 */
	public function get_api() {
		return $this->api;
	}

	/**
	 * Sets the API manager instance.
	 *
	 * @since 0.2.0
	 *
	 * @param WCSSOT_API_Manager $api
	 *
	 * @return void
	 */
	public function set_api( $api ) {
		$this->api = $api;
	}

	/**
	 * Adds the administration menu page.
	 *
	 * @since 0.0.1
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		WCSSOT_Logger::debug( 'Adding the main administration menu item for the plugin.' );
		add_menu_page(
			__( 'Seven Senders Order Tracking', 'woocommerce-seven-senders-order-tracking' ),
			__( 'Order Tracking', 'woocommerce-seven-senders-order-tracking' ),
			'manage_options',
			'wcssot',
			[ $this, 'render_admin_page' ],
			plugin_dir_url( WCSSOT_PLUGIN_FILE ) . 'admin/images/icon_wcssot.png',
			100
		);
	}

	/**
	 * Renders the admin settings page.
	 *
	 * @since 0.0.1
	 *
	 * @return void
	 */
	public function render_admin_page() {
		WCSSOT_Logger::debug( 'Rendering the administration panel settings page.' );
		if ( ! current_user_can( 'manage_options' ) ) {
			WCSSOT_Logger::debug( "User #" . get_current_user_id() . " (current) cannot view administration page." );

			return;
		}
		$description = __(
			'Interacts with the <a href="%s" target="_blank">Seven Senders API</a> to provide order tracking functionality to your WooCommerce shop.',
			'woocommerce-seven-senders-order-tracking'
		);
		$description = wp_kses( $description, [
			'a' => [
				'href'   => [],
				'target' => [],
			]
		] );
		?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <p><?php printf( $description, 'https://api.sevensenders.com/v2/docs.html' ); ?></p>
			<?php settings_errors( 'wcssot' ); ?>
            <form action="options.php" method="post" id="wcssot_form">
				<?php
				settings_fields( 'wcssot' );
				do_settings_sections( 'wcssot_settings' );
				submit_button( __( 'Save Settings', 'woocommerce-seven-senders-order-tracking' ) );
				?>
            </form>
        </div>
		<?php
	}

	/**
	 * Loads the textdomain for the plugin.
	 *
	 * @since 0.0.1
	 *
	 * @return void
	 */
	public function load_textdomain() {
		WCSSOT_Logger::debug( "Loading the 'woocommerce-seven-senders-order-tracking' text domain." );
		load_plugin_textdomain(
			'woocommerce-seven-senders-order-tracking',
			false,
			plugin_dir_url( WCSSOT_PLUGIN_FILE ) . 'languages/'
		);
	}

	/**
	 * Registers the administration settings.
	 *
	 * @since 0.0.1
	 *
	 * @return void
	 */
	public function register_admin_settings() {
		WCSSOT_Logger::debug( "Registering the administration settings and adding all sections and fields." );
		register_setting(
			'wcssot',
			'wcssot_settings',
			[
				'sanitize_callback' => [ $this, 'sanitize_admin_settings' ]
			]
		);

		// Add the 'API Credentials' section
		add_settings_section(
			'wcssot_settings_api_credentials_section',
			__( 'API Credentials', 'woocommerce-seven-senders-order-tracking' ),
			[ $this, 'render_admin_api_credentials_section' ],
			'wcssot_settings'
		);
		add_settings_field(
			'wcssot_api_base_url',
			__( 'API Base URL', 'woocommerce-seven-senders-order-tracking' ),
			[ $this, 'render_admin_api_base_url_field' ],
			'wcssot_settings',
			'wcssot_settings_api_credentials_section',
			[
				'label_for' => 'wcssot_api_base_url',
			]
		);
		add_settings_field(
			'wcssot_api_access_key',
			__( 'API Access Key', 'woocommerce-seven-senders-order-tracking' ),
			[ $this, 'render_admin_api_access_key_field' ],
			'wcssot_settings',
			'wcssot_settings_api_credentials_section',
			[
				'label_for' => 'wcssot_api_access_key',
			]
		);

		// Add the 'Tracking Page' section
		add_settings_section(
			'wcssot_settings_tracking_page_section',
			__( 'Tracking Page', 'woocommerce-seven-senders-order-tracking' ),
			[ $this, 'render_admin_tracking_page_section' ],
			'wcssot_settings'
		);
		add_settings_field(
			'wcssot_tracking_page_base_url',
			__( 'Tracking Page Base URL', 'woocommerce-seven-senders-order-tracking' ),
			[ $this, 'render_admin_tracking_page_base_url_field' ],
			'wcssot_settings',
			'wcssot_settings_tracking_page_section',
			[
				'label_for' => 'wcssot_tracking_page_base_url',
			]
		);
	}

	/**
	 * Renders the API Credentials section.
	 *
	 * @since 0.0.1
	 *
	 * @return void
	 */
	public function render_admin_api_credentials_section() {
		WCSSOT_Logger::debug( "Rendering the 'API Credentials' section subtitle." );
		$text = __(
			'Enter your assigned API credentials <a href="%s" target="_blank">from the Seven Senders dashboard</a>.',
			'woocommerce-seven-senders-order-tracking'
		);
		$text = wp_kses( $text, [
			'a' => [
				'href'   => [],
				'target' => [],
			]
		] );
		?>
        <p><?php printf( $text, 'https://sendwise.sevensenders.com/settings/shop/integrations' ); ?></p>
		<?php
	}

	/**
	 * Renders the Tracking Page section.
	 *
	 * @since 0.0.1
	 *
	 * @return void
	 */
	public function render_admin_tracking_page_section() {
		WCSSOT_Logger::debug( "Rendering the 'Tracking Page' section subtitle." );
		?>
        <p><?php esc_html_e(
				'Enter the Seven Senders Tracking Page settings.',
				'woocommerce-seven-senders-order-tracking'
			); ?></p>
		<?php
	}

	/**
	 * Renders the API Base URL setting field.
	 *
	 * @since 0.0.1
	 *
	 * @return void
	 */
	public function render_admin_api_base_url_field() {
		WCSSOT_Logger::debug( "Rendering the 'API Base URL' field." );
		$placeholder = esc_attr__(
			'The Seven Senders API base URL...',
			'woocommerce-seven-senders-order-tracking'
		);
		?>
        <input type="text"
               name="wcssot_api_base_url"
               id="wcssot_api_base_url"
               class="wcssot_form_field wcssot_form_text_field"
               placeholder="<?php echo $placeholder; ?>"
               required="required"
               value="<?php echo( isset( $this->options['wcssot_api_base_url'] ) ? $this->options['wcssot_api_base_url'] : '' ); ?>"
        >
        <span class="wcssot_helper_text">/&lt;<?php esc_html_e( 'API Endpoint', 'woocommerce-seven-senders-order-tracking' ); ?>&gt;</span>
		<?php
	}

	/**
	 * Renders the API Access Key setting field.
	 *
	 * @since 0.0.1
	 *
	 * @return void
	 */
	public function render_admin_api_access_key_field() {
		WCSSOT_Logger::debug( "Rendering the 'API Access Key' field." );
		$placeholder = esc_attr__(
			'Your provided access key...',
			'woocommerce-seven-senders-order-tracking'
		);
		?>
        <input type="text"
               name="wcssot_api_access_key"
               id="wcssot_api_access_key"
               class="wcssot_form_field wcssot_form_text_field"
               placeholder="<?php echo $placeholder; ?>"
               required="required"
               value="<?php echo( isset( $this->options['wcssot_api_access_key'] ) ? $this->options['wcssot_api_access_key'] : '' ); ?>"
        >
		<?php
	}

	/**
	 * Renders the Tracking Page Base URL setting field.
	 *
	 * @since 0.0.1
	 *
	 * @return void
	 */
	public function render_admin_tracking_page_base_url_field() {
		WCSSOT_Logger::debug( "Rendering the 'Tracking Page Base URL' field." );
		$placeholder = esc_attr__(
			'The tracking page base URL...',
			'woocommerce-seven-senders-order-tracking'
		);
		?>
        <input type="text"
               name="wcssot_tracking_page_base_url"
               id="wcssot_tracking_page_base_url"
               class="wcssot_form_field wcssot_form_text_field"
               placeholder="<?php echo $placeholder; ?>"
               required="required"
               value="<?php echo( isset( $this->options['wcssot_tracking_page_base_url'] ) ? $this->options['wcssot_tracking_page_base_url'] : '' ); ?>"
        >
        <span class="wcssot_helper_text">/&lt;<?php esc_html_e( 'Order Number', 'woocommerce-seven-senders-order-tracking' ); ?>&gt;</span>
		<?php
	}

	/**
	 * Enqueues all necessary assets for the administration panel plugin page.
	 *
	 * @since 0.0.1
	 *
	 * @param string $hook
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts( $hook = '' ) {
		if ( $hook !== 'toplevel_page_wcssot' ) {
			return;
		}
		WCSSOT_Logger::debug( "Enqueueing all necessary scripts and styles for the administration panel page." );
		wp_enqueue_style(
			'wcssot_admin_css',
			plugins_url( 'admin/css/styles.css', WCSSOT_PLUGIN_FILE )
		);
		wp_enqueue_script(
			'wcssot_admin_js',
			plugins_url( 'admin/js/scripts.js', WCSSOT_PLUGIN_FILE )
		);
		wp_localize_script(
			'wcssot_admin_js',
			'wcssot',
			[
				'l10n' => [
					'loading_text' => esc_attr__(
						'Loading... Please wait.',
						'woocommerce-seven-senders-order-tracking'
					),
				],
			]
		);
	}

	/**
	 * Sanitize the administration settings page.
	 *
	 * @since 0.0.1
	 *
	 * @param array $input
	 *
	 * @return array
	 */
	public function sanitize_admin_settings( $input = [] ) {
		WCSSOT_Logger::debug( "Sanitising the settings input." );
		if (
			empty( $_POST['wcssot_api_base_url'] ) ||
			empty( $_POST['wcssot_api_access_key'] ) ||
			empty( $_POST['wcssot_tracking_page_base_url'] )
		) {
			add_settings_error( 'wcssot', 'wcssot_error', esc_html__(
				'One of the form fields is missing!',
				'woocommerce-seven-senders-order-tracking'
			) );
			WCSSOT_Logger::error( "One of the fields is missing." );

			return $input;
		}

		$api_base_url           = rtrim( trim( $_POST['wcssot_api_base_url'] ), '/' );
		$api_access_key         = trim( $_POST['wcssot_api_access_key'] );
		$tracking_page_base_url = rtrim( trim( $_POST['wcssot_tracking_page_base_url'] ), '/' );

		if ( ! wc_is_valid_url( $api_base_url ) ) {
			add_settings_error( 'wcssot', 'wcssot_error', sprintf( esc_html__(
				'The field "%s" contains an invalid URL.',
				'woocommerce-seven-senders-order-tracking'
			), 'API Base URL' ) );
			WCSSOT_Logger::error( "The 'API Base URL' field is invalid." );

			return $input;
		}

		if ( ! wc_is_valid_url( $tracking_page_base_url ) ) {
			add_settings_error( 'wcssot', 'wcssot_error', sprintf( esc_html__(
				'The field "%s" contains an invalid URL.',
				'woocommerce-seven-senders-order-tracking'
			), 'Tracking Page Base URL' ) );
			WCSSOT_Logger::error( "The 'Tracking Page Base URL' field is invalid." );

			return $input;
		}

		$input['wcssot_api_base_url']           = $api_base_url;
		$input['wcssot_api_access_key']         = $api_access_key;
		$input['wcssot_tracking_page_base_url'] = $tracking_page_base_url;

		add_settings_error( 'wcssot', 'wcssot_success', esc_html__(
			'The settings have been saved successfully!',
			'woocommerce-seven-senders-order-tracking'
		), 'updated' );

		return $input;
	}

	/**
	 * Exports the shipment to Seven Senders for the provided order.
	 *
	 * @since 0.3.0
	 *
	 * @param int $order_id
	 * @param \WC_Order $order
	 *
	 * @return bool
	 */
	public function export_shipment( $order_id, $order ) {
		WCSSOT_Logger::debug( 'Exporting shipment for order #' . $order_id . '.' );

		if ( ! empty( $order->get_meta( 'wcssot_shipment_exported' ) ) ) {
			WCSSOT_Logger::debug( 'Shipment for order #' . $order_id . ' does not need to be exported.' );

			return true;
		}
		if ( ! $this->export_order( $order_id, $order ) ) {
			return false;
		}
		if ( ! $this->is_order_valid_for_shipment( $order ) ) {
			return false;
		}
		$shipment_data = apply_filters( 'wcssot_get_shipment_data', [
			"tracking_code"           => $this->get_shipping_tracking_code( $order ),
			"reference_number"        => "",
			"pickup_point_selected"   => false,
			"planned_pickup_datetime" => $this->get_planned_pickup_datetime( $order ),
			"comment"                 => "",
			"warehouse_address"       => "",
			"warehouse"               => "Primary Warehouse",
			"shipment_tag"            => [],
			"recipient_address"       => $this->get_recipient_address( $order ),
			"return_parcel"           => false,
			"trackable"               => true,
			"order_id"                => (string) $order->get_order_number(),
			"carrier"                 => [
				"name"    => $this->get_shipping_carrier( $order ),
				"country" => $order->get_shipping_country(),
			],
			"carrier_service"         => "standard",
			"recipient_first_name"    => $order->get_shipping_first_name(),
			"recipient_last_name"     => $order->get_shipping_last_name(),
			"recipient_company_name"  => $order->get_shipping_company(),
			"recipient_email"         => $order->get_billing_email(),
			"recipient_zip"           => $order->get_shipping_postcode(),
			"recipient_city"          => $order->get_shipping_city(),
			"recipient_country"       => $order->get_shipping_country(),
			"recipient_phone"         => $order->get_billing_phone(),
			"weight"                  => 0,
		], $order );
		if ( ! $this->get_api()->create_shipment( $shipment_data ) ) {
			return false;
		}
		update_post_meta( $order_id, 'wcssot_shipment_exported', true );
		if ( ! $this->get_api()->set_order_state( $order, 'in_preparation' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Exports the order data to Seven Senders.
	 *
	 * @since 0.2.0
	 *
	 * @param int $order_id
	 * @param WC_Order $order
	 *
	 * @return bool
	 */
	public function export_order( $order_id, $order ) {
		if ( ! $order->needs_processing() || ! empty( $order->get_meta( 'wcssot_order_exported' ) ) ) {
			WCSSOT_Logger::debug( 'Order #' . $order_id . ' does not need to be exported.' );

			return true;
		}
		try {
			$order_date_created = new DateTime( $order->get_date_created(), $this->get_timezone() );
		} catch ( Exception $exception ) {
			WCSSOT_Logger::error( 'Could not instantiate date object for order #' . $order_id . ' with date "' . $order->get_date_created() . '".' );

			return false;
		}
		$order_data = apply_filters( 'wcssot_get_order_data', [
			'order_id'   => $order->get_order_number(),
			'order_url'  => get_site_url(),
			'order_date' => $order_date_created->format( 'c' ),
		], $order );
		if ( ! $this->get_api()->create_order( $order_data ) ) {
			return false;
		}
		update_post_meta( $order_id, 'wcssot_order_exported', true );
		update_post_meta( $order_id, 'wcssot_order_tracking_link', $this->get_tracking_link( $order->get_order_number() ) );
		if ( ! $this->get_api()->set_order_state( $order, 'in_production' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Returns the timezone property.
	 *
	 * @since 0.2.0
	 *
	 * @return DateTimeZone
	 */
	public function get_timezone() {
		return $this->timezone;
	}

	/**
	 * Sets the timezone property.
	 *
	 * @since 0.2.0
	 *
	 * @param DateTimeZone $timezone
	 *
	 * @return void
	 */
	public function set_timezone( $timezone ) {
		$this->timezone = $timezone;
	}

	/**
	 * Returns the tracking link for the provided order.
	 *
	 * @since 0.2.0
	 *
	 * @param string $order_number
	 *
	 * @return string
	 */
	private function get_tracking_link( $order_number ) {
		$link = '';

		$base_url = $this->get_option( 'wcssot_tracking_page_base_url', '' );
		if ( ! empty( $base_url ) && ! empty( $order_number ) ) {
			$link = $base_url . '/' . $order_number;
		}

		return $link;
	}

	/**
	 * Returns whether the order provided is valid for shipment export.
	 *
	 * @since 0.3.0
	 *
	 * @param \WC_Order $order
	 *
	 * @return bool
	 */
	private function is_order_valid_for_shipment( $order ) {
		WCSSOT_Logger::debug( 'Checking if order #' . $order->get_id() . ' is valid for shipment export.' );
		$carrier = $this->get_shipping_carrier( $order );
		if ( empty( $carrier ) ) {
			WCSSOT_Logger::error( 'Order #' . $order->get_id() . ' does not have an assigned shipping carrier.' );

			return false;
		}
		$tracking_code = $this->get_shipping_tracking_code( $order );
		if ( empty( $tracking_code ) ) {
			WCSSOT_Logger::error( 'Order #' . $order->get_id() . ' does not have an assigned shipping tracking code.' );

			return false;
		}
		if ( ! apply_filters( 'wcssot_is_carrier_valid', $this->is_carrier_valid( $carrier, $order ), $carrier, $order ) ) {
			WCSSOT_Logger::error( 'The carrier "' . $carrier . '" is not supported for order #' . $order->get_id() . '.' );

			return false;
		}

		return true;
	}

	/**
	 * Returns whether the provided carrier is supported.
	 *
	 * @since 0.3.0
	 *
	 * @param string $carrier
	 * @param WC_Order $order
	 *
	 * @return bool
	 */
	private function is_carrier_valid( $carrier, $order ) {
		$supported_carriers = $this->get_api()->get_supported_carriers();
		if ( ! isset( $supported_carriers[ $carrier ] ) ) {
			return false;
		}
		if (
			empty( $order->get_shipping_country() )
			|| ! in_array( strtoupper( $order->get_shipping_country() ), $supported_carriers[ $carrier ]['countries'] )
		) {
			return false;
		}

		return true;
	}

	/**
	 * Returns the planned pickup datetime for the provided order.
	 *
	 * @since 0.3.0
	 *
	 * @param WC_Order $order
	 *
	 * @return string
	 */
	private function get_planned_pickup_datetime( $order ) {
		$datetime_str = '';
		try {
			$datetime = new DateTime( '+1 weekday', $this->get_timezone() );
			$datetime->setTime( 12, 0, 0, 0 );
			$datetime_str = $datetime->format( 'c' );
		} catch ( Exception $exception ) {
			WCSSOT_Logger::error( 'Could not calculate planned pickup datetime for order #' . $order->get_id() . '.' );
		}

		return apply_filters( 'wcssot_get_planned_pickup_datetime', $datetime_str, $order, $this->get_shipping_carrier( $order ) );
	}

	/**
	 * Returns the recipient formatted address for the provided order.
	 *
	 * @since 0.3.0
	 *
	 * @param WC_Order $order
	 *
	 * @return string
	 */
	private function get_recipient_address( WC_Order $order ) {
		$address = implode( ', ', array_filter( [
			trim( $order->get_shipping_address_1() ),
			trim( $order->get_shipping_address_2() ),
		] ) );

		return $address;
	}

	/**
	 * Returns the options required property.
	 *
	 * @since 0.2.0
	 *
	 * @return array
	 */
	public function get_options_required() {
		return $this->options_required;
	}

	/**
	 * Sets the options required property.
	 *
	 * @since 0.2.0
	 *
	 * @param array $options_required
	 *
	 * @return void
	 */
	public function set_options_required( $options_required ) {
		$this->options_required = $options_required;
	}
}