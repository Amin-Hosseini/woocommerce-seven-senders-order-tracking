<?php
/**
 * PSR-4 autoloader implementation from https://www.php-fig.org/psr/psr-4/examples/.
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
 * @since 0.2.0
 *
 * @package WCSSOT
 */

/**
 * Loads the specified class if it is included in the project namespace.
 *
 * @since 0.2.0
 *
 * @param $class
 *
 * @return void
 */
function wcssot_autoloader( $class ) {
	$prefix = 'WCSSOT\\';

	$base_dir = __DIR__ . '/includes/';
	$len      = strlen( $prefix );
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		return;
	}

	$relative_class = 'class-' . str_replace( '_', '-', strtolower( substr( $class, $len ) ) );
	$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

	if ( file_exists( $file ) ) {
		require_once( $file );
	}
}