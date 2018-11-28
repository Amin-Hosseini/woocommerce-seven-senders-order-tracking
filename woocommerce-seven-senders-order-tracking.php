<?php
/**
 * Plugin Name:  WooCommerce Seven Senders Order Tracking
 * Plugin URI:   https://github.com/hypeventures/woocommerce-seven-senders-order-tracking
 * Description:  Interacts with the Seven Senders API to provide order tracking.
 * Version:      0.1.0
 * Author:       Kostas Stergiannis <kostas@invinciblebrands.com>
 * Author URI:   https://www.invinciblebrands.com/
 * License:      GPL3
 * License URI:  https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:  woocommerce-seven-senders-order-tracking
 * Domain Path:  /languages
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
 * @since 0.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define WCSSOT_PLUGIN_FILE
if ( ! defined( 'WCSSOT_PLUGIN_FILE' ) ) {
	define( 'WCSSOT_PLUGIN_FILE', __FILE__ );
}

/**
 * Hooks after WooCommerce has finished loading and initialises the main plugin class.
 *
 * @since 0.0.1
 *
 */
add_action( 'woocommerce_loaded', 'wcssot_init' );

/**
 * Initialises the main plugin class.
 *
 * @since 0.0.1
 * @return void
 */
function wcssot_init() {
	// Include the main plugin class.
	if ( ! class_exists( 'WCSSOT' ) ) {
		include_once dirname( __FILE__ ) . '/includes/class-wcssot.php';
	}
	if ( empty( $_GLOBALS['WCSSOT'] ) ) {
		$_GLOBALS['WCSSOT'] = new WCSSOT();
	}
}