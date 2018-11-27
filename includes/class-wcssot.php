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

	}
}