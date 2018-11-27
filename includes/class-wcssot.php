<?php
/**
 * The main plugin class.
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
 * @class WCSSOT
 */
final class WCSSOT {
	/**
	 * WCSSOT constructor.
	 *
	 * @since 0.0.1
	 */
	public function __construct() {
		$this->initialise_hooks();
	}

	/**
	 * Initialises the required hooks for the plugin.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	private function initialise_hooks() {
		add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
		if ( is_admin() ) {
			add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
			add_action( 'admin_init', [ $this, 'register_admin_settings' ] );
		}
	}

	/**
	 * Adds the administration menu page.
	 *
	 * @since 0.0.1
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
	 * @return void
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('wcssot');
                do_settings_sections('wcssot');
                submit_button( __('Save Settings', 'woocommerce-seven-senders-order-tracking'));
                ?>
            </form>
        </div>
		<?php
	}

	/**
	 * Loads the textdomain for the plugin.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'woocommerce-seven-senders-order-tracking', false, plugin_dir_url( WCSSOT_PLUGIN_FILE ) . 'languages/' );
	}

	/**
	 * Registers the administration settings.
     *
     * @since 0.0.1
     * @return void
	 */
	public function register_admin_settings() {
		register_setting( 'wcssot', 'wcssot_settings' );
		add_settings_section(
		        'wcssot_settings_api_section',
                __('API Credentials', 'woocommerce-seven-senders-order-tracking'),
                [ $this, 'render_admin_api_section' ],
                'wcssot'
        );
		add_settings_field(
		        'wcssot_api_base_url',
                __('API Base URL', 'woocommerce-seven-senders-order-tracking'),
                [ $this, 'render_admin_api_base_url_field' ],
            'wcssot',
            'wcssot_settings_api_section'
        );
	}

	/**
	 * Renders the API Credentials section.
     *
     * @todo Render helpful text describing the section settings.
     *
     * @since 0.0.1
     * @return void
	 */
	public function render_admin_api_section() {

    }

	/**
	 * Renders the API Base URL setting field.
     *
     * @todo Render the actual form field.
     *
     * @since 0.0.1
     * @return void
	 */
	public function render_admin_api_base_url_field() {

    }
}