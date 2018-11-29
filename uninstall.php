<?php
/**
 * The plugin uninstallation file responsible for cleaning up after the plugin has been removed.
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
 * @since 0.1.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit; // Exit if not called by Wordpress.
}

/**
 * Delete only the option set for the plugin settings.
 * All postmeta data will stay for future use and backwards compatibility.
 */
delete_option('wcssot_settings');