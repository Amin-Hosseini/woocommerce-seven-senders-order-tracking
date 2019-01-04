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

	/**
	 * @var DateTimeZone $timezone The default timezone to use for all date objects in the plugin.
	 */
	private $timezone;

	/**
	 * @var WCSSOT_API_Manager $api The main API manager instance.
	 */
	private $api;

	/**
	 * @var array $order_meta_keys A list of meta keys used by the WC_Order object.
	 */
	private $order_meta_keys = [];

	/**
	 * @var WCSSOT_Options_Manager $options_manager The instance of the options manger class.
	 */
	private $options_manager;

	/**
	 * WCSSOT constructor.
	 *
	 * @since 0.0.1
	 */
	public function __construct() {
		WCSSOT_Logger::debug( 'Initialising the main WCSSOT plugin class.' );
		/**
		 * Fires before initialising the WCSSOT class.
		 *
		 * @since 0.6.0
		 *
		 * @param WCSSOT $wcssot The current class object.
		 */
		do_action( 'wcssot_before_init', $this );
		$this->load_textdomain();
		$this->initialise_properties();
		$this->initialise_hooks();
		/**
		 * Fires after initialising the WCSSOT class.
		 *
		 * @since 0.6.0
		 *
		 * @param WCSSOT $wcssot The current class object.
		 */
		do_action( 'wcssot_after_init', $this );
	}

	/**
	 * Loads the textdomain for the plugin.
	 *
	 * @since 0.0.1
	 *
	 * @return void
	 */
	public function load_textdomain() {
		/**
		 * Fires before loading the text domain.
		 *
		 * @since 0.6.0
		 *
		 * @param WCSSOT $wcssot The current class object.
		 */
		do_action( 'wcssot_before_load_textdomain', $this );
		WCSSOT_Logger::debug( "Loading the 'woocommerce-seven-senders-order-tracking' text domain." );
		load_plugin_textdomain(
			'woocommerce-seven-senders-order-tracking',
			false,
			plugin_dir_url( WCSSOT_PLUGIN_FILE ) . 'languages/'
		);
		/**
		 * Fires after loading the text domain.
		 *
		 * @since 0.6.0
		 *
		 * @param WCSSOT $wcssot The current class object.
		 */
		do_action( 'wcssot_after_load_textdomain', $this );
	}

	/**
	 * Initialises the class properties.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	private function initialise_properties() {
		/**
		 * Fires before initialising the class properties.
		 *
		 * @since 0.6.0
		 *
		 * @param WCSSOT $wcssot The current class object.
		 */
		do_action( 'wcssot_before_initialise_properties', $this );
		/**
		 * Filters the default options manager instance.
		 *
		 * @since 1.2.0
		 *
		 * @param WCSSOT_Options_Manager $instance The options manager instance.
		 * @param WCSSOT $wcssot The current class object.
		 */
		$this->set_options_manager(
			apply_filters( 'wcssot_set_default_options_manager', new WCSSOT_Options_Manager( $this ), $this )
		);
		/**
		 * Filters the default order meta keys used for the WC_Order object.
		 *
		 * @since 0.6.0
		 *
		 * @param array $keys The default order meta keys.
		 * @param WCSSOT $wcssot The current class object.
		 */
		$this->set_order_meta_keys( apply_filters( 'wcssot_set_default_order_meta_keys', [
			'wcssot_order_exported'         => 'wcssot_order_exported',
			'wcssot_order_tracking_link'    => 'wcssot_order_tracking_link',
			'wcssot_shipment_exported'      => 'wcssot_shipment_exported',
			'wcssot_shipping_carrier'       => 'wcssot_shipping_carrier',
			'wcssot_shipping_tracking_code' => 'wcssot_shipping_tracking_code',
		], $this ) );
		try {
			/**
			 * Filters the default timezone object.
			 *
			 * @since 0.6.0
			 *
			 * @param DateTimeZone $timezone The default timezone object.
			 * @param WCSSOT $wcssot The current class object.
			 */
			$this->set_timezone(
				apply_filters( 'wcssot_default_timezone', new DateTimeZone( wc_timezone_string() ), $this )
			);
		} catch ( Exception $exception ) {
			WCSSOT_Logger::error( 'Could not instantiate shop timezone for "' . wc_timezone_string() . '".' );

			return;
		}
		/**
		 * Filters the default API manager instance.
		 *
		 * @since 0.6.0
		 *
		 * @param WCSSOT_API_Manager $manager The default API manager instance.
		 * @param WCSSOT $wcssot The current class object.
		 */
		$this->set_api( apply_filters( 'wcssot_set_default_api', new WCSSOT_API_Manager(
			$this->get_options_manager()->get_option( 'wcssot_api_base_url' ),
			$this->get_options_manager()->get_option( 'wcssot_api_access_key' )
		), $this ) );
		/**
		 * Fires after initialising the class properties.
		 *
		 * @since 0.6.0
		 *
		 * @param WCSSOT $wcssot The current class object.
		 */
		do_action( 'wcssot_after_initialise_properties', $this );
	}

	/**
	 * Returns the instance of the options manager class.
	 *
	 * @since 1.2.0
	 *
	 * @return WCSSOT_Options_Manager $instance The instance of the options manager class.
	 */
	public function get_options_manager() {
		/**
		 * Filters the options manager instance to return.
		 *
		 * @since 1.2.0
		 *
		 * @param WCSSOT_Options_Manager $instance The options manager instance.
		 * @param WCSSOT $wcssot The current class object.
		 */
		return apply_filters( 'wcssot_get_options_manager', $this->options_manager, $this );
	}

	/**
	 * Sets the instance of the options manager class.
	 *
	 * @since 1.2.0
	 *
	 * @param WCSSOT_Options_Manager $instance The instance of the options manager class.
	 *
	 * @return void
	 */
	public function set_options_manager( $instance ) {
		/**
		 * Filters the instance of the options manager class.
		 *
		 * @since 1.2.0
		 *
		 * @param WCSSOT_Options_Manager $instance The instance of the options manager class.
		 * @param WCSSOT $wcssot The current class object.
		 */
		$this->options_manager = apply_filters( 'wcssot_set_options_manager', $instance, $this );
	}

	/**
	 * Initialises the required hooks for the plugin.
	 *
	 * @since 0.0.1
	 *
	 * @return void
	 */
	private function initialise_hooks() {
		/**
		 * Fires before initialising the hooks.
		 *
		 * @since 0.6.0
		 *
		 * @param WCSSOT $wcssot The current class object.
		 */
		do_action( 'wcssot_before_initialise_hooks', $this );
		WCSSOT_Logger::debug( 'Initialising hooks for the WCSSOT main class.' );
		/**
		 * Checks if all required settings are populated.
		 */
		if ( ! $this->get_options_manager()->settings_exist() ) {
			return;
		}
		add_action( 'woocommerce_order_status_processing', [ $this, 'export_order' ], 10, 2 );
		add_action( 'woocommerce_order_status_completed', [ $this, 'export_shipment' ], 10, 2 );
		add_action( 'woocommerce_email_before_order_table', [ $this, 'render_tracking_information' ], 10, 1 );
		/**
		 * Initialise hooks for the scheduled events.
		 */
		add_filter( 'cron_schedules', [ $this, 'get_weekly_cron_schedule' ], 10, 1 );
		/**
		 * Filters the name of the daily scheduled event hook.
		 *
		 * @since 2.0.0
		 *
		 * @param string $hook The name of the hook.
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current options manager class object.
		 */
		add_filter( apply_filters(
			'wcssot_daily_event_hook',
			'wcssot_daily_delivery_date_tracking',
			$this->get_options_manager()
		), [ $this, 'handle_daily_delivery_date_tracking_event' ] );
		/**
		 * Filters the name of the weekly scheduled event hook.
		 *
		 * @since 2.0.0
		 *
		 * @param string $hook The name of the hook.
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The options manager class object.
		 */
		add_filter( apply_filters(
			'wcssot_weekly_event_hook',
			'wcssot_weekly_delivery_date_tracking',
			$this->get_options_manager()
		), [ $this, 'handle_weekly_delivery_date_tracking_event' ] );
		/**
		 * Fires after initialising the hooks.
		 *
		 * @since 0.6.0
		 *
		 * @param WCSSOT $wcssot The current class object.
		 */
		do_action( 'wcssot_after_initialise_hooks', $this );
	}

	/**
	 * Handles the Daily Delivery Date Tracking event.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function handle_daily_delivery_date_tracking_event() {
		$this->sync_order_delivery_status(
			apply_filters( 'wcssot_sync_daily_orders_from_days_ago', 10, $this ),
			apply_filters( 'wcssot_sync_daily_orders_to_days_ago', 1, $this )
		);
	}

	/**
	 * Syncs the orders' delivery status using the Seven Senders API.
	 *
	 * @since 2.0.0
	 *
	 * @param int $from_days_ago The amount of days ago for the orders to start searching from.
	 * @param int $to_days_ago The amount of days ago for the orders to start searching to.
	 *
	 * @return void
	 */
	public function sync_order_delivery_status( $from_days_ago, $to_days_ago ) {
		$from_days_ago = absint( $from_days_ago );
		$to_days_ago   = absint( $to_days_ago );
		try {
			$timezone  = new DateTimeZone( wc_timezone_string() );
			$from_date = new DateTime( $from_days_ago . ' days ago', $timezone );
			$to_date   = new DateTime( $to_days_ago . ' days ago', $timezone );
		} catch ( Exception $exception ) {
			WCSSOT_Logger::error( 'Could not instantiate the date objects for the X days ago variables.' );

			return;
		}
		global $wpdb;
		$query  = $wpdb->prepare( "
			SELECT p.ID
			FROM {$wpdb->posts} AS p
			WHERE p.post_type = %s
			AND NULLIF((
				SELECT meta_value
				FROM {$wpdb->postmeta} AS pm
				WHERE pm.post_id = p.ID
				AND pm.meta_key = %s
			), '') IS NULL
			AND p.post_status IN ('wc-processing', 'wc-on-hold', 'wc-completed')
			AND p.post_date BETWEEN %s AND %s
		", [ 'shop_order', 'wcssot_delivered_at', $from_date->format( 'c' ), $to_date->format( 'c' ) ] );
		$orders = $wpdb->get_col( $query );
		if ( empty( $orders ) ) {
			WCSSOT_Logger::debug( 'No orders need the delivery date synchronized.' );

			return;
		}
		$params = [
			'state'              => 'completed',
			'order_date[before]' => $from_date->format( 'c' ),
			'order_date[after]'  => $to_date->format( 'c' ),
		];
		if ( count( $orders ) === 1 ) {
			$params['order_id'] = $orders[0];
		}
		try {
			$remote_orders = $this->get_api()->get_orders( $params );
		} catch ( Exception $exception ) {
			WCSSOT_Logger::error( 'Could not fetch order from the Seven Senders API.' );

			return;
		}
		/**
		 * @todo: Match the fetched orders with the ones in the DB and add the meta values for the delivery date.
		 */
	}

	/**
	 * Returns the API manager instance.
	 *
	 * @since 0.2.0
	 *
	 * @return WCSSOT_API_Manager The API manager instance to return.
	 */
	public function get_api() {
		/**
		 * Filters the API manager instance to return.
		 *
		 * @since 0.6.0
		 *
		 * @param WCSSOT_API_Manager $manager The manager instance to return.
		 * @param WCSSOT $wcssot The current class object.
		 */
		return apply_filters( 'wcssot_get_api', $this->api, $this );
	}

	/**
	 * Sets the API manager instance.
	 *
	 * @since 0.2.0
	 *
	 * @param WCSSOT_API_Manager $api The API manager instance to set.
	 *
	 * @return void
	 */
	public function set_api( $api ) {
		/**
		 * Filters the API manager instance to set.
		 *
		 * @since 0.6.0
		 *
		 * @param WCSSOT_API_Manager $manager The API manager instance to set.
		 * @param WCSSOT $wcssot The current class object.
		 */
		$this->api = apply_filters( 'wcssot_set_api', $api, $this );
	}

	/**
	 * Handles the Weekly Delivery Date Tracking event.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function handle_weekly_delivery_date_tracking_event() {
		$this->sync_order_delivery_status(
			apply_filters( 'wcssot_sync_weekly_orders_from_days_ago', 60, $this ),
			apply_filters( 'wcssot_sync_weekly_orders_to_days_ago', 15, $this )
		);
	}

	/**
	 * Registers the weekly schedule for the scheduled events.
	 *
	 * @since 2.0.0
	 *
	 * @param array $schedules The list of schedules already registered.
	 *
	 * @return array
	 */
	public function get_weekly_cron_schedule( $schedules ) {
		$schedules['weekly'] = [
			'interval' => 604800,
			'display'  => __( 'Once Weekly', 'woocommerce-seven-senders-order-tracking' ),
		];

		return $schedules;
	}

	/**
	 * Returns the options property.
	 *
	 * @since 0.2.0
	 * @deprecated 1.2.0 Use WCSSOT_Options_Manager->get_options().
	 * @see WCSSOT_Options_Manager->get_options()
	 *
	 * @return array The list of the plugin options.
	 */
	public function get_options() {
		return $this->get_options_manager()->get_options();
	}

	/**
	 * Sets the options property.
	 *
	 * @since 0.2.0
	 * @deprecated 1.2.0 Use WCSSOT_Options_Manager->set_options().
	 * @see WCSSOT_Options_Manager->set_options()
	 *
	 * @param array $options The options list to set.
	 *
	 * @return void
	 */
	public function set_options( $options ) {
		$this->get_options_manager()->set_options( $options );
	}

	/**
	 * Renders the tracking information to the "Completed Order" email content.
	 *
	 * @since 0.4.1
	 *
	 * @param WC_Order $order The order object to render the tracking information for.
	 *
	 * @return void
	 */
	public function render_tracking_information( $order ) {
		/**
		 * Filters whether the order status is valid to render the tracking information to the customer email.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $valid Whether the status is valid.
		 * @param WCSSOT $wcssot The current class object.
		 */
		if ( apply_filters( 'wcssot_is_order_status_valid', $order->get_status() !== 'completed', $order, $this ) ) {
			WCSSOT_Logger::debug(
				'Order status for order #' . $order->get_id() . ' is not valid to render the tracking information.'
			);

			return;
		}
		$shipment_exported = $this->is_shipment_exported( $order, true );
		$tracking_link     = $this->get_order_tracking_link( $order );
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
		$text    .= sprintf(
			esc_html__( 'Your tracking code is: %s', 'woocommerce-seven-senders-order-tracking' ),
			$tracking_code
		);
		$text    .= '</p>';

		/**
		 * Filters the tracking information text.
		 *
		 * @since 1.2.0
		 *
		 * @param string $text The already constructed text.
		 * @param string $tracking_link The link to the tracking page with the order's information.
		 * @param string $carrier The carrier name for the shipment.
		 * @param string $tracking_code The tracking code for the shipment.
		 * @param WC_Order $order The order of the shipment.
		 * @param WCSSOT $wcssot The current class object.
		 */
		$text = apply_filters(
			'wcssot_get_tracking_information_text',
			$text,
			$tracking_link,
			$carrier,
			$tracking_code,
			$order,
			$this
		);

		/**
		 * Filters the contents of the tracking information to render on the customer email.
		 *
		 * @since 0.6.0
		 *
		 * @param string $text The HTML to render.
		 * @param WC_Order $order The order that the email is being sent for.
		 * @param WCSSOT $wcssot The current class object.
		 */
		echo apply_filters( 'wcssot_render_tracking_information', $text, $order, $this );
	}

	/**
	 * Returns whether the shipment has been exported for the provided order.
	 *
	 * @since 0.5.0
	 *
	 * @param WC_Order $order The order to check whether the shipment is exported for.
	 * @param bool $refresh Whether to fetch the data from the database again.
	 *
	 * @return bool Whether the shipment has been exported for the order provided.
	 */
	private function is_shipment_exported( $order, $refresh ) {
		if ( $refresh ) {
			/**
			 * Filters whether the shipment has been exported for the order provided.
			 *
			 * @since 0.6.0
			 *
			 * @param bool $decision Whether the shipment has been exported.
			 * @param WC_Order $order The order object.
			 * @param bool $refresh Whether to fetch the fresh data.
			 * @param WCSSOT $wcssot The current class object.
			 */
			return apply_filters(
				'wcssot_is_shipment_exported',
				! empty( get_post_meta(
					$order->get_id(),
					$this->get_order_meta_key( 'wcssot_shipment_exported' ),
					true
				) ), $order, $refresh, $this );
		}

		/**
		 * Filters whether the shipment has been exported for the order provided.
		 *
		 * @since 0.6.0
		 *
		 * @param bool $decision Whether the shipment has been exported.
		 * @param WC_Order $order The order object.
		 * @param bool $refresh Whether to fetch the fresh data.
		 * @param WCSSOT $wcssot The current class object.
		 */
		return apply_filters(
			'wcssot_is_shipment_exported',
			! empty( $order->get_meta( $this->get_order_meta_key( 'wcssot_shipment_exported' ) ) ),
			$order,
			$refresh,
			$this
		);
	}

	/**
	 * Returns the order meta key requested.
	 *
	 * @since 0.6.0
	 *
	 * @param string $key The meta key requested.
	 *
	 * @return mixed The meta value requested.
	 */
	public function get_order_meta_key( $key ) {
		/**
		 * Filters the order meta key requested.
		 *
		 * @since 0.6.0
		 *
		 * @param string $association The key association requested.
		 * @param string $key The key requested.
		 * @param WCSSOT $wcssot The current class object.
		 */
		return apply_filters(
			'wcssot_get_order_meta_key',
			isset( $this->order_meta_keys[ $key ] ) ? $this->order_meta_keys[ $key ] : null,
			$key,
			$this
		);
	}

	/**
	 * Returns the order tracking link.
	 *
	 * @since 0.5.0
	 *
	 * @param WC_Order $order The order to return the tracking link for.
	 *
	 * @return string The tracking link for the order requested.
	 */
	private function get_order_tracking_link( $order ) {
		/**
		 * Filters the order tracking link.
		 *
		 * @since 0.6.0
		 *
		 * @param string $link The tracking link requested.
		 * @param WC_Order $order The order object the link belongs to.
		 * @param WCSSOT $wcssot The current class object.
		 */
		return apply_filters(
			'wcssot_get_order_tracking_link',
			(string) $order->get_meta( $this->get_order_meta_key( 'wcssot_order_tracking_link' ) ),
			$order,
			$this
		);
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
	private function get_shipping_carrier( $order ) {
		/**
		 * Filters the order shipping carrier.
		 *
		 * @since 0.6.0
		 *
		 * @param string $carrier The shipping carrier identifier for the order.
		 * @param WC_Order $order The order object.
		 * @param WCSSOT $wcssot The current class object.
		 */
		return apply_filters(
			'wcssot_get_shipping_carrier',
			(string) $order->get_meta( $this->get_order_meta_key( 'wcssot_shipping_carrier' ) ),
			$order,
			$this
		);
	}

	/**
	 * Returns the shipping tracking code for the provided order.
	 *
	 * @since 0.3.0
	 *
	 * @param WC_Order $order The order object to get the tracking code for.
	 *
	 * @return string The shipping tracking code requested.
	 */
	private function get_shipping_tracking_code( $order ) {
		/**
		 * Filters the shipping tracking code requested.
		 *
		 * @since 0.6.0
		 *
		 * @param string $code The tracking code requested.
		 * @param WC_Order $order The order object.
		 * @param WCSSOT $wcssot The current class object.
		 */
		return apply_filters(
			'wcssot_get_shipping_tracking_code',
			(string) $order->get_meta( $this->get_order_meta_key( 'wcssot_shipping_tracking_code' ) ),
			$order,
			$this
		);
	}

	/**
	 * Adds the administration menu page.
	 *
	 * @since 0.0.1
	 * @deprecated 1.2.0 Use WCSSOT_Options_Manager->add_admin_menu().
	 * @see WCSSOT_Options_Manager->add_admin_menu()
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		$this->get_options_manager()->add_admin_menu();
	}

	/**
	 * Renders the admin settings page.
	 *
	 * @since 0.0.1
	 * @deprecated 1.2.0 Use WCSSOT_Options_Manager->render_admin_page().
	 * @see WCSSOT_Options_Manager->render_admin_page()
	 *
	 * @return void
	 */
	public function render_admin_page() {
		$this->get_options_manager()->render_admin_page();
	}

	/**
	 * Registers the administration settings.
	 *
	 * @since 0.0.1
	 * @deprecated 1.2.0 Use WCSSOT_Options_Manager->register_admin_settings().
	 * @see WCSSOT_Options_Manager->register_admin_settings()
	 *
	 * @return void
	 */
	public function register_admin_settings() {
		$this->get_options_manager()->register_admin_settings();
	}

	/**
	 * Renders the API Credentials section.
	 *
	 * @since 0.0.1
	 * @deprecated 1.2.0 Use WCSSOT_Options_Manager->render_admin_api_credentials_section().
	 * @see WCSSOT_Options_Manager->render_admin_api_credentials_section()
	 *
	 * @return void
	 */
	public function render_admin_api_credentials_section() {
		$this->get_options_manager()->render_admin_api_credentials_section();
	}

	/**
	 * Renders the Tracking Page section.
	 *
	 * @since 0.0.1
	 * @deprecated 1.2.0 Use WCSSOT_Options_Manager->render_admin_tracking_page_section().
	 * @see WCSSOT_Options_Manager->render_admin_tracking_page_section()
	 *
	 * @return void
	 */
	public function render_admin_tracking_page_section() {
		$this->get_options_manager()->render_admin_tracking_page_section();
	}

	/**
	 * Renders the API Base URL setting field.
	 *
	 * @since 0.0.1
	 * @deprecated 1.2.0 Use WCSSOT_Options_Manager->render_admin_api_base_url_field().
	 * @see WCSSOT_Options_Manager->render_admin_api_base_url_field()
	 *
	 * @return void
	 */
	public function render_admin_api_base_url_field() {
		$this->get_options_manager()->render_admin_api_base_url_field();
	}

	/**
	 * Renders the API Access Key setting field.
	 *
	 * @since 0.0.1
	 * @deprecated 1.2.0 Use WCSSOT_Options_Manager->render_admin_api_access_key_field().
	 * @see WCSSOT_Options_Manager->render_admin_api_access_key_field()
	 *
	 * @return void
	 */
	public function render_admin_api_access_key_field() {
		$this->get_options_manager()->render_admin_api_access_key_field();
	}

	/**
	 * Renders the Tracking Page Base URL setting field.
	 *
	 * @since 0.0.1
	 * @deprecated 1.2.0 Use WCSSOT_Options_Manager->render_admin_tracking_page_base_url_field().
	 * @see WCSSOT_Options_Manager->render_admin_tracking_page_base_url_field()
	 *
	 * @return void
	 */
	public function render_admin_tracking_page_base_url_field() {
		$this->get_options_manager()->render_admin_tracking_page_base_url_field();
	}

	/**
	 * Enqueues all necessary assets for the administration panel plugin page.
	 *
	 * @since 0.0.1
	 * @deprecated 1.2.0 Use WCSSOT_Options_Manager->enqueue_admin_scripts().
	 * @see WCSSOT_Options_Manager->enqueue_admin_scripts()
	 *
	 * @param string $hook The hook that calls the enqueueing process.
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts( $hook = '' ) {
		$this->get_options_manager()->enqueue_admin_scripts( $hook );
	}

	/**
	 * Sanitize the administration settings page.
	 *
	 * @since 0.0.1
	 * @deprecated 1.2.0 Use WCSSOT_Options_Manager->sanitize_admin_settings().
	 * @see WCSSOT_Options_Manager->sanitize_admin_settings()
	 *
	 * @param array $input The input list to sanitise.
	 *
	 * @return array The sanitised input list.
	 */
	public function sanitize_admin_settings( $input = [] ) {
		return $this->get_options_manager()->sanitize_admin_settings( $input );
	}

	/**
	 * Exports the shipment to Seven Senders for the provided order.
	 *
	 * @since 0.3.0
	 *
	 * @param int $order_id The order ID to export the shipment for.
	 * @param \WC_Order $order The order object to export the shipment for.
	 *
	 * @return bool Whether the shipment has been exported.
	 * @throws Exception
	 */
	public function export_shipment( $order_id, $order ) {
		WCSSOT_Logger::debug( 'Exporting shipment for order #' . $order_id . '.' );
		/**
		 * Fires before exporting the order shipment.
		 *
		 * @since 0.6.0
		 *
		 * @param WC_Order $order The order object to export the shipment for.
		 * @param WCSSOT $wcssot The current class object.
		 */
		do_action( 'wcssot_before_export_shipment', $order, $this );
		if ( ! empty( $order->get_meta( $this->get_order_meta_key( 'wcssot_shipment_exported' ) ) ) ) {
			WCSSOT_Logger::debug( 'Shipment for order #' . $order_id . ' does not need to be exported.' );

			return true;
		}
		if ( ! $this->export_order( $order_id, $order ) ) {
			return false;
		}
		if ( ! $this->is_order_valid_for_shipment( $order ) ) {
			return false;
		}
		/**
		 * Filters the shipment data to pass to the Seven Senders API.
		 *
		 * @since 0.6.0
		 *
		 * @param array $data The shipment data.
		 * @param WC_Order $order The order object to export the data for.
		 * @param WCSSOT $wcssot The current class object.
		 */
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
		], $order, $this );
		if ( ! $this->get_api()->create_shipment( $shipment_data ) ) {
			return false;
		}
		update_post_meta( $order_id, $this->get_order_meta_key( 'wcssot_shipment_exported' ), true );
		if ( ! $this->get_api()->set_order_state( $order, 'in_preparation' ) ) {
			return false;
		}
		/**
		 * Fires after exporting the order shipment.
		 *
		 * @since 0.6.0
		 *
		 * @param WC_Order $order The order object.
		 * @param array $shipment_data The shipment data passed to the Seven Senders API.
		 * @param WCSSOT $wcssot The current class object.
		 */
		do_action( 'wcssot_after_export_shipment', $order, $shipment_data, $this );

		/**
		 * Filters whether the shipment has been exported.
		 *
		 * @since 0.6.0
		 *
		 * @param bool $decision Whether the shipment has been exported.
		 * @param array $shipment_data The shipment data passed to the Seven Senders API.
		 * @param WCSSOT $wcssot The current class object.
		 */
		return apply_filters( 'wcssot_shipment_exported', true, $order, $shipment_data, $this );
	}

	/**
	 * Exports the order data to Seven Senders.
	 *
	 * @since 0.2.0
	 *
	 * @param int $order_id The order ID of the order to export.
	 * @param WC_Order $order The order object of the order to export.
	 *
	 * @return bool Whether the order has been exported.
	 */
	public function export_order( $order_id, $order ) {
		WCSSOT_Logger::debug( 'Exporting order #' . $order_id . '.' );
		/**
		 * Fires before exporting the order.
		 *
		 * @since 0.6.0
		 *
		 * @param WC_Order $order The order object to export.
		 * @param WCSSOT $wcssot The current class object.
		 */
		do_action( 'wcssot_before_export_order', $order, $this );
		if (
			! $order->needs_processing()
			|| ! empty( $order->get_meta( $this->get_order_meta_key( 'wcssot_order_exported' ) ) )
		) {
			WCSSOT_Logger::debug( 'Order #' . $order_id . ' does not need to be exported.' );

			return true;
		}
		try {
			$order_date_created = new DateTime( $order->get_date_created(), $this->get_timezone() );
		} catch ( Exception $exception ) {
			WCSSOT_Logger::error(
				'Could not instantiate date object for order #' . $order_id . ' with date "'
				. $order->get_date_created() . '".'
			);

			return false;
		}
		/**
		 * Filters the order data to pass to the Seven Senders API.
		 *
		 * @since 0.6.0
		 *
		 * @param array $data The order data to export.
		 * @param WC_Order $order The order object of the order.
		 * @param WCSSOT $wcssot The current class object.
		 */
		$order_data = apply_filters( 'wcssot_get_order_data', [
			'order_id'   => $order->get_order_number(),
			'order_url'  => get_site_url(),
			'order_date' => $order_date_created->format( 'c' ),
		], $order, $this );
		if ( ! $this->get_api()->create_order( $order_data ) ) {
			return false;
		}
		update_post_meta( $order_id, $this->get_order_meta_key( 'wcssot_order_exported' ), true );
		update_post_meta(
			$order_id,
			$this->get_order_meta_key( 'wcssot_order_tracking_link' ),
			$this->get_tracking_link( $order->get_order_number() )
		);
		if ( ! $this->get_api()->set_order_state( $order, 'in_production' ) ) {
			return false;
		}
		/**
		 * Fires after exporting the order.
		 *
		 * @since 0.6.0
		 *
		 * @param WC_Order $order The order object that got exported.
		 * @param WCSSOT $wcssot The current class object.
		 */
		do_action( 'wcssot_after_export_order', $order, $this );

		/**
		 * Filters whether the order has been exported.
		 *
		 * @since 0.6.0
		 *
		 * @param bool $decision Whether the order has been exported.
		 * @param WC_Order $order The order object that was exported.
		 * @param array $order_data The order data that was exported.
		 * @param WCSSOT $wcssot The current class object.
		 */
		return apply_filters( 'wcssot_order_exported', true, $order, $order_data, $this );
	}

	/**
	 * Returns the timezone property.
	 *
	 * @since 0.2.0
	 *
	 * @return DateTimeZone The timezone object.
	 */
	public function get_timezone() {
		/**
		 * Filters the timezone object requested.
		 *
		 * @since 0.6.0
		 *
		 * @param DateTimeZone $timezone The timezone object that was requested.
		 * @param WCSSOT $wcssot The current class object.
		 */
		return apply_filters( 'wcssot_get_timezone', $this->timezone, $this );
	}

	/**
	 * Sets the timezone property.
	 *
	 * @since 0.2.0
	 *
	 * @param DateTimeZone $timezone The timezone object to set.
	 *
	 * @return void
	 */
	public function set_timezone( $timezone ) {
		/**
		 * Filters the timezone object to be set.
		 *
		 * @since 0.6.0
		 *
		 * @param DateTimeZone $timezone The timezone object to set.
		 * @param WCSSOT $wcssot The current class object.
		 */
		$this->timezone = apply_filters( 'wcssot_set_timezone', $timezone, $this );
	}

	/**
	 * Returns the tracking link for the provided order.
	 *
	 * @since 0.2.0
	 *
	 * @param string $order_number The order number for the tracking link.
	 *
	 * @return string The tracking link requested.
	 */
	private function get_tracking_link( $order_number ) {
		$link     = '';
		$base_url = $this->get_options_manager()->get_option( 'wcssot_tracking_page_base_url', '' );
		if ( ! empty( $base_url ) && ! empty( $order_number ) ) {
			$link = $base_url . '/' . $order_number;
		}

		/**
		 * Filters the tracking link requested.
		 *
		 * @since 0.6.0
		 *
		 * @param string $link The tracking link.
		 * @param string $order_number The order number for the tracking link.
		 * @param WCSSOT $wcssot The current class object.
		 */
		return apply_filters( 'wcssot_get_tracking_link', $link, $order_number, $this );
	}

	/**
	 * Returns whether the order provided is valid for shipment export.
	 *
	 * @since 0.3.0
	 *
	 * @param \WC_Order $order The order object to check.
	 *
	 * @return bool Whether the order provided is valid for shipment export.
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
		if ( ! $this->is_carrier_valid( $carrier, $order ) ) {
			WCSSOT_Logger::error(
				'The carrier "' . $carrier . '" is not supported for order #' . $order->get_id() . '.'
			);

			return false;
		}

		/**
		 * Filters whether the order is valid for shipment export.
		 *
		 * @since 0.6.0
		 *
		 * @param bool $valid Whether the order is valid.
		 * @param WC_Order $order The order object to check.
		 * @param WCSSOT $wcssot The current class object.
		 */
		return apply_filters( 'wcssot_is_order_valid_for_shipment', true, $order, $this );
	}

	/**
	 * Returns whether the provided carrier is supported.
	 *
	 * @since 0.3.0
	 *
	 * @param string $carrier The carrier identifier to check.
	 * @param WC_Order $order The order object for the carrier.
	 *
	 * @return bool Whether the provided carrier is supported.
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

		/**
		 * Filters whether the carrier is valid.
		 *
		 * @since 0.6.0
		 *
		 * @param bool $valid Whether the carrier provided is valid.
		 * @param string $carrier The carrier identifier to check.
		 * @param WC_Order $order The order object for the carrier.
		 * @param WCSSOT $wcssot The current class object.
		 */
		return apply_filters( 'wcssot_is_carrier_valid', true, $carrier, $order, $this );
	}

	/**
	 * Returns the planned pickup datetime for the provided order.
	 *
	 * @since 0.3.0
	 *
	 * @param WC_Order $order The order object to get the pickup time for.
	 *
	 * @return string The planned pickup datetime in standard format.
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

		/**
		 * Filters the planned pickup datetime.
		 *
		 * @since 0.6.0
		 *
		 * @param string $datetime The datetime string of the pickup time.
		 * @param WC_Order $order The order object to get the time for.
		 * @param string $carrier The carrier identifier to get the pickup time for.
		 * @param WCSSOT $wcssot The current class object.
		 */
		return apply_filters(
			'wcssot_get_planned_pickup_datetime',
			$datetime_str,
			$order,
			$this->get_shipping_carrier( $order ),
			$this
		);
	}

	/**
	 * Returns the recipient formatted address for the provided order.
	 *
	 * @since 0.3.0
	 *
	 * @param WC_Order $order The order object to get the address from.
	 *
	 * @return string The formatted address.
	 */
	private function get_recipient_address( $order ) {
		$address = implode( ', ', array_filter( [
			trim( $order->get_shipping_address_1() ),
			trim( $order->get_shipping_address_2() ),
		] ) );

		/**
		 * Filters the recipient address.
		 *
		 * @since 0.6.0
		 *
		 * @param string $address The recipient address requested.
		 * @param WC_Order $order The order object to get the address from.
		 * @param WCSSOT $wcssot The current class object.
		 */
		return apply_filters( 'wcssot_get_recipient_address', $address, $order, $this );
	}

	/**
	 * Returns the specified option key from the options property.
	 *
	 * @since 0.2.0
	 * @deprecated 1.2.0 Use WCSSOT_Options_Manager->get_option().
	 * @see WCSSOT_Options_Manager->get_option()
	 *
	 * @param string $option The option key to get.
	 * @param mixed $default The default value to return in case the option does not exist.
	 *
	 * @return mixed The option value requested.
	 */
	public function get_option( $option, $default = null ) {
		return $this->get_options_manager()->get_option( $option, $default );
	}

	/**
	 * Returns the options required property.
	 *
	 * @since 0.2.0
	 * @deprecated 1.2.0 Use WCSSOT_Options_Manager->get_options_required().
	 * @see WCSSOT_Options_Manager->get_options_required()
	 *
	 * @return array The list of options required.
	 */
	public function get_options_required() {
		return $this->get_options_manager()->get_options_required();
	}

	/**
	 * Sets the options required property.
	 *
	 * @since 0.2.0
	 * @deprecated 1.2.0 Use WCSSOT_Options_Manager->set_options_required().
	 * @see WCSSOT_Options_Manager->set_options_required()
	 *
	 * @param array $options_required The list of options required to set.
	 *
	 * @return void
	 */
	public function set_options_required( $options_required ) {
		$this->get_options_manager()->set_options_required( $options_required );
	}

	/**
	 * Returns the order meta keys.
	 *
	 * @since 0.6.0
	 *
	 * @return array The list of order meta keys.
	 */
	public function get_order_meta_keys() {
		/**
		 * Filters the list of order meta keys used by the plugin.
		 *
		 * @since 0.6.0
		 *
		 * @param array $keys The list of order meta keys.
		 * @param WCSSOT $wcssot The current class object.
		 */
		return apply_filters( 'wcssot_get_order_meta_keys', $this->order_meta_keys, $this );
	}

	/**
	 * Sets the order meta keys.
	 *
	 * @since 0.6.0
	 *
	 * @param array $order_meta_keys The list of order meta keys to set.
	 *
	 * @return void
	 */
	public function set_order_meta_keys( $order_meta_keys ) {
		/**
		 * Filters the list of order meta keys to set.
		 *
		 * @since 0.6.0
		 *
		 * @param array $keys The list of order meta keys to set.
		 * @param WCSSOT $wcssot The current class object.
		 */
		$this->order_meta_keys = apply_filters( 'wcssot_set_order_meta_keys', $order_meta_keys, $this );
	}
}