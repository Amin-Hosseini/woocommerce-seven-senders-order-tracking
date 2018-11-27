<?php
/**
 * The main plugin class.
 *
 * @package WCSSOT
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
		if ( is_admin() ) {
			add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
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
			'Seven Senders Order Tracking',
			'Order Tracking',
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
        </div>
		<?php
	}
}