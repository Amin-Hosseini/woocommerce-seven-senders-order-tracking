<?php
/**
 * A custom logger for the WCSSOT plugin.
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
 * @since 0.2.0
 */

namespace WCSSOT;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The logger class for the WCSSOT plugin.
 *
 * @since 0.2.0
 *
 * @class WCSSOT_Logger
 */
class WCSSOT_Logger {
	/** @var array $supported_types */
	private static $supported_types = [
		'debug'   => 'DEBUG',
		'error'   => '<ERROR>',
		'warning' => '(WARNING)',
	];

	/**
	 * Uses the main logging method to log a debug message.
	 *
	 * @since 0.2.0
	 *
	 * @param string $message
	 *
	 * @return void
	 */
	public static function debug( $message ) {
		if ( apply_filters( 'wcssot_debug_logging_enabled', false ) ) {
			self::log( 'debug', $message );
		}
	}

	/**
	 * Logs the provided message with the provided type.
	 *
	 * @since 0.2.0
	 *
	 * @param string $type
	 * @param string $message
	 *
	 * @return void
	 */
	public static function log( $type, $message ) {
		$backtrace = debug_backtrace();
		$suffix    = '';
		if ( ! empty( $backtrace[1] ) ) {
			$suffix = ' (' . $backtrace[1]['file'] . ':' . $backtrace[1]['line'] . ')';
		}
		if ( isset( self::$supported_types[ $type ] ) && apply_filters( 'wcssot_logging_enabled', true ) ) {
			error_log( '[WCSSOT] ' . self::$supported_types[ $type ] . ': ' . (string) $message . $suffix );
		}
	}

	/**
	 * Uses the main logging method to log an error message.
	 *
	 * @since 0.2.0
	 *
	 * @param string $message
	 *
	 * @return void
	 */
	public static function error( $message ) {
		if ( apply_filters( 'wcssot_error_logging_enabled', true ) ) {
			self::log( 'error', $message );
		}
	}

	/**
	 * Uses the main logging method to log a warning message.
	 *
	 * @since 0.2.0
	 *
	 * @param string $message
	 *
	 * @return void
	 */
	public static function warning( $message ) {
		if ( apply_filters( 'wcssot_warning_logging_enabled', true ) ) {
			self::log( 'warning', $message );
		}
	}
}