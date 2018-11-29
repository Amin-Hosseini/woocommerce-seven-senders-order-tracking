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

	/**
	 * WCSSOT constructor.
	 *
	 * @since 0.0.1
	 */
	public function __construct() {
		$this->options = get_option( 'wcssot_settings', [] );
		$this->initialise_hooks();
	}

	/**
	 * Initialises the required hooks for the plugin.
	 *
	 * @since 0.0.1
	 *
	 * @return void
	 */
	private function initialise_hooks() {
		add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
		if ( is_admin() ) {
			add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
			add_action( 'admin_init', [ $this, 'register_admin_settings' ] );
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
		}
		if ( ! $this->settings_exist() ) {
			return;
		}
	}

	/**
     * Returns whether the required settings have been set.
     *
     * @since 0.1.0
     *
	 * @return bool
	 */
	private function settings_exist() {
		foreach ( $this->options_required as $option_required ) {
			if ( empty( $this->options[ $option_required ] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Adds the administration menu page.
	 *
	 * @since 0.0.1
	 *
	 * @return void
	 */
	public function add_admin_menu() {
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
		if ( ! current_user_can( 'manage_options' ) ) {
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
		$placeholder = esc_attr__(
			'Your 32-character long access key...',
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
		if (
			empty( $_POST['wcssot_api_base_url'] ) ||
			empty( $_POST['wcssot_api_access_key'] ) ||
			empty( $_POST['wcssot_tracking_page_base_url'] )
		) {
			add_settings_error( 'wcssot', 'wcssot_error', esc_html__(
				'One of the form fields is missing!',
				'woocommerce-seven-senders-order-tracking'
			) );

			return $input;
		}

		$api_base_url           = trim( $_POST['wcssot_api_base_url'] );
		$api_access_key         = trim( $_POST['wcssot_api_access_key'] );
		$tracking_page_base_url = trim( $_POST['wcssot_tracking_page_base_url'] );

		if ( ! wc_is_valid_url( $api_base_url ) ) {
			add_settings_error( 'wcssot', 'wcssot_error', sprintf( esc_html__(
				'The field "%s" contains an invalid URL.',
				'woocommerce-seven-senders-order-tracking'
			), 'API Base URL' ) );

			return $input;
		}

		if ( ! wc_is_valid_url( $tracking_page_base_url ) ) {
			add_settings_error( 'wcssot', 'wcssot_error', sprintf( esc_html__(
				'The field "%s" contains an invalid URL.',
				'woocommerce-seven-senders-order-tracking'
			), 'Tracking Page Base URL' ) );

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
}