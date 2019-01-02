<?php
/**
 * The plugin options manager class.
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
 * @since 1.2.0
 */

namespace WCSSOT;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The plugin options manager class.
 *
 * @since 1.2.0
 *
 * @class WCSSOT_Options_Manager
 */
class WCSSOT_Options_Manager {
	/**
	 * @var array $options The list of options set/used by the plugin.
	 */
	private $options = [];

	/**
	 * @var array $options_required A list of option IDs that are required by the plugin.
	 */
	private $options_required = [];

	/**
	 * @var WCSSOT $wcssot The instance of the main plugin class object.
	 */
	private $wcssot;

	/**
	 * WCSSOT_Options_Manager constructor.
	 *
	 * @since 1.2.0
	 *
	 * @param WCSSOT $wcssot The instance of the main plugin class.
	 */
	public function __construct( $wcssot ) {
		WCSSOT_Logger::debug( 'Initialising the options manager class.' );
		/**
		 * Fires before initialising the WCSSOT_Options_Manager class.
		 *
		 * @since 1.2.0
		 *
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		do_action( 'wcssot_options_manager_before_init', $this );
		$this->wcssot = $wcssot;
		$this->initialise_properties();
		$this->initialise_hooks();
		/**
		 * Fires after initialising the WCSSOT class.
		 *
		 * @since 1.2.0
		 *
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		do_action( 'wcssot_options_manager_after_init', $this );
	}

	/**
	 * Initialises the class properties.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	private function initialise_properties() {
		/**
		 * Fires before initialising the class properties.
		 *
		 * @since 1.2.0
		 *
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		do_action( 'wcssot_options_manager_before_initialise_properties', $this );
		/**
		 * Filters the default options required by the plugin.
		 *
		 * @since 0.6.0
		 * @since 1.2.0 Moved from the main plugin class and added an extra parameter for the current object instance.
		 *
		 * @param array $options The default options required.
		 * @param WCSSOT $wcssot The main plugin class object.
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		$this->set_options_required( apply_filters( 'wcssot_set_default_options_required', [
			'wcssot_api_base_url',
			'wcssot_api_access_key',
			'wcssot_tracking_page_base_url',
		], $this->wcssot, $this ) );
		$this->set_options( get_option( 'wcssot_settings', [] ) );
		/**
		 * Fires after initialising the class properties.
		 *
		 * @since 1.2.0
		 *
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		do_action( 'wcssot_options_manager_after_initialise_properties', $this );
	}

	/**
	 * Initialises the required hooks for the options manager.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	private function initialise_hooks() {
		/**
		 * Fires before initialising the hooks.
		 *
		 * @since 1.2.0
		 *
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		do_action( 'wcssot_options_manager_before_initialise_hooks', $this );
		WCSSOT_Logger::debug( 'Initialising hooks for the WCSSOT_Options_Manager class.' );
		/**
		 * Filters whether to add the administration hooks.
		 *
		 * @since 0.6.0
		 * @since 1.2.0 Moved from the main plugin class and added an extra parameter for the current object instance.
		 *
		 * @param bool $decision Whether to add the administration hooks.
		 * @param WCSSOT $wcssot The main plugin class object.
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		if ( is_admin() && apply_filters( 'wcssot_add_admin_action_hooks', true, $this->wcssot, $this ) ) {
			WCSSOT_Logger::debug( 'Initialising hooks for the administration panel.' );
			add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
			add_action( 'admin_init', [ $this, 'register_admin_settings' ] );
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
		}
		/**
		 * Fires after initialising the hooks.
		 *
		 * @since 1.2.0
		 *
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		do_action( 'wcssot_options_manager_after_initialise_hooks', $this );
	}

	/**
	 * Returns the specified option key from the options property.
	 *
	 * @since 1.2.0
	 *
	 * @param string $option The option key to get.
	 * @param mixed $default The default value to return in case the option does not exist.
	 *
	 * @return mixed The option value requested.
	 */
	public function get_option( $option, $default = null ) {
		$options = $this->get_options();

		/**
		 * Filters the option requested.
		 *
		 * @since 0.6.0
		 * @since 1.2.0 Moved from the main plugin class and added an extra parameter for the current object instance.
		 *
		 * @param mixed $value The option requested.
		 * @param string $option The option key requested.
		 * @param mixed $default The default value to return in case the option does not exist.
		 * @param WCSSOT $wcssot The main plugin class object.
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		return apply_filters(
			'wcssot_get_option',
			( isset( $options[ $option ] ) ? $options[ $option ] : $default ),
			$option,
			$default,
			$this->wcssot,
			$this
		);
	}

	/**
	 * Returns the options property.
	 *
	 * @since 1.2.0
	 *
	 * @return array The list of the plugin options.
	 */
	public function get_options() {
		/**
		 * Filters the plugin options list.
		 *
		 * @since 0.6.0
		 * @since 1.2.0 Moved from the main plugin class and added an extra parameter for the current object instance.
		 *
		 * @param array $options The list of the plugin options.
		 * @param WCSSOT $wcssot The main plugin class object.
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		return apply_filters( 'wcssot_get_options', $this->options, $this->wcssot, $this );
	}

	/**
	 * Sets the options property.
	 *
	 * @since 1.2.0
	 *
	 * @param array $options The options list to set.
	 *
	 * @return void
	 */
	public function set_options( $options ) {
		/**
		 * Filters the options to be set.
		 *
		 * @since 0.6.0
		 * @since 1.2.0 Moved from the main plugin class and added an extra parameter for the current object instance.
		 *
		 * @param array $options The options to be set.
		 * @param WCSSOT $wcssot The main plugin class object.
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		$this->options = apply_filters( 'wcssot_set_options', $options, $this->wcssot, $this );
	}

	/**
	 * Returns whether the required settings have been set.
	 *
	 * @since 1.2.0
	 *
	 * @return bool Whether the required settings are populated.
	 */
	public function settings_exist() {
		WCSSOT_Logger::debug( 'Checking if all required settings exist.' );
		$exist            = true;
		$options_required = $this->get_options_required();
		$options          = $this->get_options();
		foreach ( $options_required as $option_required ) {
			if ( empty( $options[ $option_required ] ) ) {
				WCSSOT_Logger::error( "The setting '$option_required' is missing from the options!" );
				$exist = false;
				break;
			}
		}

		/**
		 * Filters the decision whether the settings exist.
		 *
		 * @since 0.6.0
		 * @since 1.2.0 Moved from the main plugin class and added an extra parameter for the current object instance.
		 *
		 * @param bool $exist Whether the required settings exist.
		 * @param WCSSOT $wcssot The main plugin class object.
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		return apply_filters( 'wcssot_settings_exist', $exist, $this->wcssot, $this );
	}

	/**
	 * Returns the options required property.
	 *
	 * @since 1.2.0
	 *
	 * @return array The list of options required.
	 */
	public function get_options_required() {
		/**
		 * Filters the list of options required by the plugin.
		 *
		 * @since 0.6.0
		 * @since 1.2.0 Moved from the main plugin class and added an extra parameter for the current object instance.
		 *
		 * @param array $options The list of options required.
		 * @param WCSSOT $wcssot The main plugin class object.
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		return apply_filters( 'wcssot_get_options_required', $this->options_required, $this->wcssot, $this );
	}

	/**
	 * Sets the options required property.
	 *
	 * @since 1.2.0
	 *
	 * @param array $options_required The list of options required to set.
	 *
	 * @return void
	 */
	public function set_options_required( $options_required ) {
		/**
		 * Filters the list of options required to set.
		 *
		 * @since 0.6.0
		 * @since 1.2.0 Moved from the main plugin class and added an extra parameter for the current object instance.
		 *
		 * @param array $options The list of options required to set.
		 * @param WCSSOT $wcssot The main plugin class object.
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		$this->options_required = apply_filters(
			'wcssot_set_options_required',
			$options_required,
			$this->wcssot,
			$this
		);
	}

	/**
	 * Adds the administration menu page.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		/**
		 * Fires before adding the admin menu.
		 *
		 * @since 0.6.0
		 * @since 1.2.0 Moved from the main plugin class and added an extra parameter for the current object instance.
		 *
		 * @param WCSSOT $wcssot The main plugin class object.
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		do_action( 'wcssot_before_add_admin_menu', $this->wcssot, $this );
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
		/**
		 * Fires after adding the admin menu.
		 *
		 * @since 0.6.0
		 * @since 1.2.0 Moved from the main plugin class and added an extra parameter for the current object instance.
		 *
		 * @param WCSSOT $wcssot The main plugin class object.
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		do_action( 'wcssot_after_add_admin_menu', $this->wcssot, $this );
	}

	/**
	 * Renders the admin settings page.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function render_admin_page() {
		/**
		 * Fires before rendering the administration page.
		 *
		 * @since 0.6.0
		 * @since 1.2.0 Moved from the main plugin class and added an extra parameter for the current object instance.
		 *
		 * @param WCSSOT $wcssot The main plugin class object.
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		do_action( 'wcssot_before_render_admin_page', $this->wcssot, $this );
		WCSSOT_Logger::debug( 'Rendering the administration panel settings page.' );
		if ( ! current_user_can( 'manage_options' ) ) {
			WCSSOT_Logger::debug( "User #" . get_current_user_id() . " (current) cannot view administration page." );

			return;
		}
		$description = __(
			'Interacts with the <a href="%s" target="_blank">Seven Senders API</a> to provide order tracking'
			. ' functionality to your WooCommerce shop.',
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
		/**
		 * Fires after rendering the administration page.
		 *
		 * @since 0.6.0
		 * @since 1.2.0 Moved from the main plugin class and added an extra parameter for the current object instance.
		 *
		 * @param WCSSOT $wcssot The main plugin class object.
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		do_action( 'wcssot_after_render_admin_page', $this->wcssot, $this );
	}

	/**
	 * Registers the administration settings.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function register_admin_settings() {
		/**
		 * Fires before registering the administration settings.
		 *
		 * @since 0.6.0
		 * @since 1.2.0 Moved from the main plugin class and added an extra parameter for the current object instance.
		 *
		 * @param WCSSOT $wcssot The main plugin class object.
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		do_action( 'wcssot_before_register_admin_settings', $this->wcssot, $this );
		WCSSOT_Logger::debug( "Registering the administration settings and adding all sections and fields." );
		register_setting(
			'wcssot',
			'wcssot_settings',
			[
				'sanitize_callback' => [ $this, 'sanitize_admin_settings' ]
			]
		);

		/**
		 * Add the 'API Credentials' section
		 */
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

		/**
		 * Add the 'Tracking Page' section
		 */
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

		/**
		 * Add the 'Delivery Date Tracking' section
		 */
		add_settings_section(
			'wcssot_settings_delivery_date_tracking_section',
			__( 'Delivery Date Tracking', 'woocommerce-seven-senders-order-tracking' ),
			[ $this, 'render_admin_delivery_date_tracking_section' ],
			'wcssot_settings'
		);
		add_settings_field(
			'wcssot_delivery_date_tracking_enabled',
			__( 'Tracking Enabled', 'woocommerce-seven-senders-order-tracking' ),
			[ $this, 'render_admin_delivery_date_tracking_enabled_field' ],
			'wcssot_settings',
			'wcssot_settings_delivery_date_tracking_section',
			[
				'label_for' => 'wcssot_delivery_date_tracking_enabled',
			]
		);
		/**
		 * Fires after registering the administration settings.
		 *
		 * @since 0.6.0
		 * @since 1.2.0 Moved from the main plugin class and added an extra parameter for the current object instance.
		 *
		 * @param WCSSOT $wcssot The main plugin class object.
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		do_action( 'wcssot_after_register_admin_settings', $this->wcssot, $this );
	}

	/**
	 * Sanitize the administration settings page.
	 *
	 * @since 1.2.0
	 *
	 * @param array $input The input list to sanitise.
	 *
	 * @return array The sanitised input list.
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
		/**
		 * Filters whether the API Base URL input is valid.
		 *
		 * @since 0.6.0
		 * @since 1.2.0 Moved from the main plugin class and added an extra parameter for the current object instance.
		 *
		 * @param bool $valid Whether the field is valid.
		 * @param string $api_base_url The API Base URL value.
		 * @param array $input The input list to sanitise.
		 * @param WCSSOT $wcssot The main plugin class object.
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		if ( ! apply_filters(
			'wcssot_is_api_base_url_valid',
			wc_is_valid_url( $api_base_url ),
			$api_base_url,
			$input,
			$this->wcssot,
			$this
		) ) {
			add_settings_error( 'wcssot', 'wcssot_error', sprintf( esc_html__(
				'The field "%s" contains an invalid URL.',
				'woocommerce-seven-senders-order-tracking'
			), 'API Base URL' ) );
			WCSSOT_Logger::error( "The 'API Base URL' field is invalid." );

			return $input;
		}
		/**
		 * Filters whether the API Access Key field is valid.
		 *
		 * @since 0.6.0
		 * @since 1.2.0 Moved from the main plugin class and added an extra parameter for the current object instance.
		 *
		 * @param bool $valid Whether the field is valid.
		 * @param string $api_access_key The API Access Key value.
		 * @param array $input The input list to be sanitise.
		 * @param WCSSOT $wcssot The main plugin class object.
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		if ( ! apply_filters( 'wcssot_is_api_access_key_valid', true, $api_access_key, $input, $this->wcssot, $this ) ) {
			add_settings_error( 'wcssot', 'wcssot_error', sprintf( esc_html__(
				'The field "%s" is invalid.',
				'woocommerce-seven-senders-order-tracking'
			), 'API Access Key' ) );
			WCSSOT_Logger::error( "The 'API Access Key' field is invalid." );

			return $input;
		}
		/**
		 * Filters whether the Tracking Page Base URL field is valid.
		 *
		 * @since 0.6.0
		 * @since 1.2.0 Moved from the main plugin class and added an extra parameter for the current object instance.
		 *
		 * @param bool $valid Whether the field is valid.
		 * @param string $tracking_page_base_url The Tracking Page Base URL value.
		 * @param array $input The input list to sanitise.
		 * @param WCSSOT $wcssot The main plugin class object.
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		if ( ! apply_filters(
			'wcssot_is_tracking_page_base_url_valid',
			wc_is_valid_url( $tracking_page_base_url ),
			$tracking_page_base_url,
			$input,
			$this->wcssot,
			$this
		) ) {
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
		/**
		 * Filters the sanitised input list.
		 *
		 * @since 0.6.0
		 * @since 1.2.0 Moved from the main plugin class and added an extra parameter for the current object instance.
		 *
		 * @param array $input The sanitised input list.
		 * @param WCSSOT $wcssot The main plugin class object.
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		return apply_filters( 'wcssot_sanitize_admin_settings', $input, $this->wcssot, $this );
	}

	/**
	 * Renders the API Credentials section.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function render_admin_api_credentials_section() {
		/**
		 * Fires before rendering the API Credentials section.
		 *
		 * @since 0.6.0
		 * @since 1.2.0 Moved from the main plugin class and added an extra parameter for the current object instance.
		 *
		 * @param WCSSOT $wcssot The main plugin class object.
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		do_action( 'wcssot_before_render_admin_api_credentials_section', $this->wcssot, $this );
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
		/**
		 * Fires after rendering the API Credentials section.
		 *
		 * @since 0.6.0
		 * @since 1.2.0 Moved from the main plugin class and added an extra parameter for the current object instance.
		 *
		 * @param WCSSOT $wcssot The main plugin class object.
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		do_action( 'wcssot_after_render_admin_api_credentials_section', $this->wcssot, $this );
	}

	/**
	 * Renders the Tracking Page section.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function render_admin_tracking_page_section() {
		/**
		 * Fires before rendering the Tracking Page section.
		 *
		 * @since 0.6.0
		 * @since 1.2.0 Moved from the main plugin class and added an extra parameter for the current object instance.
		 *
		 * @param WCSSOT $wcssot The main plugin class object.
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		do_action( 'wcssot_before_render_admin_tracking_page_section', $this->wcssot, $this );
		WCSSOT_Logger::debug( "Rendering the 'Tracking Page' section subtitle." );
		?>
        <p><?php esc_html_e(
				'Enter the Seven Senders Tracking Page settings.',
				'woocommerce-seven-senders-order-tracking'
			); ?></p>
		<?php
		/**
		 * Fires after rendering the Tracking Page section.
		 *
		 * @since 0.6.0
		 * @since 1.2.0 Moved from the main plugin class and added an extra parameter for the current object instance.
		 *
		 * @param WCSSOT $wcssot The main plugin class object.
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		do_action( 'wcssot_after_render_admin_tracking_page_section', $this->wcssot, $this );
	}

	/**
	 * Renders the API Base URL setting field.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function render_admin_api_base_url_field() {
		/**
		 * Fires before rendering the API Base URL field.
		 *
		 * @since 0.6.0
		 * @since 1.2.0 Moved from the main plugin class and added an extra parameter for the current object instance.
		 *
		 * @param WCSSOT $wcssot The main plugin class object.
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		do_action( 'wcssot_before_render_admin_api_base_url_field', $this->wcssot, $this );
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
               value="<?php
		       echo $this->get_option( 'wcssot_api_base_url', '' );
		       ?>"
        >
        <span class="wcssot_helper_text">/&lt;<?php esc_html_e(
				'API Endpoint',
				'woocommerce-seven-senders-order-tracking'
			); ?>&gt;</span>
		<?php
		/**
		 * Fires after rendering the API Base URL field.
		 *
		 * @since 0.6.0
		 * @since 1.2.0 Moved from the main plugin class and added an extra parameter for the current object instance.
		 *
		 * @param WCSSOT $wcssot The main plugin class object.
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		do_action( 'wcssot_after_render_admin_api_base_url_field', $this->wcssot, $this );
	}

	/**
	 * Renders the API Access Key setting field.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function render_admin_api_access_key_field() {
		/**
		 * Fires before rendering the API Access Key field.
		 *
		 * @since 0.6.0
		 * @since 1.2.0 Moved from the main plugin class and added an extra parameter for the current object instance.
		 *
		 * @param WCSSOT $wcssot The main plugin class object.
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		do_action( 'wcssot_before_render_admin_api_access_key_field', $this->wcssot, $this );
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
               value="<?php
		       echo $this->get_option( 'wcssot_api_access_key', '' );
		       ?>"
        >
		<?php
		/**
		 * Fires after rendering the API Access Key field.
		 *
		 * @since 0.6.0
		 * @since 1.2.0 Moved from the main plugin class and added an extra parameter for the current object instance.
		 *
		 * @param WCSSOT $wcssot The main plugin class object.
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		do_action( 'wcssot_after_render_admin_api_access_key_field', $this->wcssot, $this );
	}

	/**
	 * Renders the Tracking Page Base URL setting field.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function render_admin_tracking_page_base_url_field() {
		/**
		 * Fires before rendering the Tracking Page Base URL field.
		 *
		 * @since 0.6.0
		 * @since 1.2.0 Moved from the main plugin class and added an extra parameter for the current object instance.
		 *
		 * @param WCSSOT $wcssot The main plugin class object.
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		do_action( 'wcssot_before_render_admin_tracking_page_base_url_field', $this->wcssot, $this );
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
               value="<?php
		       echo $this->get_option( 'wcssot_tracking_page_base_url', '' );
		       ?>"
        >
        <span class="wcssot_helper_text">/&lt;<?php
			esc_html_e( 'Order Number', 'woocommerce-seven-senders-order-tracking' );
			?>&gt;</span>
		<?php
		/**
		 * Fires after rendering the Trackign Page Base URL field.
		 *
		 * @since 0.6.0
		 * @since 1.2.0 Moved from the main plugin class and added an extra parameter for the current object instance.
		 *
		 * @param WCSSOT $wcssot The main plugin class object.
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		do_action( 'wcssot_after_render_admin_tracking_page_base_url_field', $this->wcssot, $this );
	}

	/**
	 * Renders the Delivery Date Tracking section.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function render_admin_delivery_date_tracking_section() {
		/**
		 * Fires before rendering the Delivery Date Tracking section.
		 *
		 * @since 2.0.0
		 *
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		do_action( 'wcssot_before_render_admin_delivery_date_tracking_section', $this );
		WCSSOT_Logger::debug( "Rendering the 'Delivery Date Tracking' section subtitle." );
		?>
        <p><?php esc_html_e(
				'Use this section to set up the delivery date tracking feature.',
				'woocommerce-seven-senders-order-tracking'
			); ?></p>
		<?php
		/**
		 * Fires after rendering the Delivery Date Tracking section.
		 *
		 * @since 2.0.0
		 *
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		do_action( 'wcssot_after_render_admin_delivery_date_tracking_section', $this );
	}

	/**
	 * Renders the Delivery Date Tracking Enabled setting field.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function render_admin_delivery_date_tracking_enabled_field() {
		/**
		 * Fires before rendering the Tracking Enabled field.
		 *
		 * @since 2.0.0
		 *
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		do_action( 'wcssot_before_render_admin_delivery_date_tracking_enabled_field', $this );
		WCSSOT_Logger::debug( "Rendering the 'Tracking Enabled' field." );
		?>
        <input type="checkbox"
               name="wcssot_delivery_date_tracking_enabled"
               id="wcssot_delivery_date_tracking_enabled"
               class="wcssot_form_field wcssot_form_checkbox"
        >
		<?php
		/**
		 * Fires after rendering the Delivery Date Tracking Enabled field.
		 *
		 * @since 2.0.0
         *
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		do_action( 'wcssot_after_render_admin_delivery_date_tracking_enabled_field', $this->wcssot, $this );
	}

	/**
	 * Enqueues all necessary assets for the administration panel plugin page.
	 *
	 * @since 1.2.0
	 *
	 * @param string $hook The hook that calls the enqueueing process.
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts( $hook = '' ) {
		if ( $hook !== 'toplevel_page_wcssot' ) {
			return;
		}
		WCSSOT_Logger::debug( "Enqueueing all necessary scripts and styles for the administration panel page." );
		/**
		 * Fires before enqueueing the administration scripts.
		 *
		 * @since 0.6.0
		 * @since 1.2.0 Moved from the main plugin class and added an extra parameter for the current object instance.
		 *
		 * @param WCSSOT $wcssot The main plugin class object.
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		do_action( 'wcssot_before_enqueue_admin_scripts', $this->wcssot, $this );
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
		/**
		 * Fires after enqueueing the administration scripts.
		 *
		 * @since 0.6.0
		 * @since 1.2.0 Moved from the main plugin class and added an extra parameter for the current object instance.
		 *
		 * @param WCSSOT $wcssot The main plugin class object.
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		do_action( 'wcssot_after_enqueue_admin_scripts', $this->wcssot, $this );
	}
}