<?php
/**
 * Plugin Name:  WooCommerce Seven Senders Order Tracking
 * Plugin URI:   https://github.com/hypeventures/woocommerce-seven-senders-order-tracking
 * Description:  Interacts with the Seven Senders API to provide order tracking functionality to your WooCommerce shop.
 * Version:      2.0.3
 * Author:       Invincible Brands <dev@invinciblebrands.com>
 * Author URI:   https://www.invinciblebrands.com/
 * License:      GPL3
 * License URI:  https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:  woocommerce-seven-senders-order-tracking
 * Domain Path:  /languages
 *
 * Copyright (C) 2018-2019 Invincible Brands GmbH
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
 * @since 0.0.1
 */

use WCSSOT\WCSSOT;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Require the project autoloader.
 */
require_once( 'autoload.php' );

/**
 * Define WCSSOT_PLUGIN_FILE containing the entrypoint file name.
 */
if ( ! defined( 'WCSSOT_PLUGIN_FILE' ) ) {
	define( 'WCSSOT_PLUGIN_FILE', __FILE__ );
}

/**
 * Hooks after WooCommerce has finished loading and initialises the main plugin class.
 *
 * @since 0.0.1
 * @since 1.1.0 Changed action hook to `plugins_loaded` from `woocommerce_loaded`.
 */
add_action( 'plugins_loaded', 'wcssot_init' );

/**
 * Registers the activation hook to run after the plugin has been activated.
 *
 * @since 0.1.0
 */
register_activation_hook( __FILE__, 'wcssot_install' );

/**
 * Initialises the main plugin class.
 *
 * @since 0.0.1
 *
 * @return void
 */
function wcssot_init() {
	if ( ! defined( 'WC_VERSION' ) || version_compare( WC_VERSION, '3.5.0' ) < 0 ) {
		return;
	}
	try {
		spl_autoload_register( 'wcssot_autoloader' );
	} catch ( Exception $exception ) {
		error_log( '[WCSSOT] ERROR: Could not register project autoloader.' );
	}

	if ( empty( $GLOBALS['WCSSOT'] ) ) {
		$GLOBALS['WCSSOT'] = new WCSSOT();
	}
}

/**
 * Provisions the plugin settings by adding the defaults to the option.
 *
 * @since 0.1.0
 *
 * @return void
 */
function wcssot_install() {
	/**
	 * Adds the default settings to the option.
	 */
	add_option( 'wcssot_settings', [
		'wcssot_api_base_url' => 'https://api.sevensenders.com/v2'
	] );
}