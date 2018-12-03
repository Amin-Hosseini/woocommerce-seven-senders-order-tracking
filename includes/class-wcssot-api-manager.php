<?php
/**
 * Contains the Seven Senders API manager class.
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

use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The Seven Senders API manager class responsible for all operations with the gateway.
 *
 * @since 0.2.0
 *
 * @class WCSSOT_API_Manager
 */
class WCSSOT_API_Manager {

	/** @var string $api_base_url */
	private $api_base_url;

	/** @var string $api_access_key */
	private $api_access_key;

	/**
	 * WCSSOT_API_Manager constructor.
	 *
	 * @since 0.2.0
	 *
	 * @param string $api_base_url
	 * @param string $api_access_key
	 */
	public function __construct( $api_base_url, $api_access_key ) {
		$this->setApiBaseUrl( $api_base_url );
		$this->setApiAccessKey( $api_access_key );
		try {
			$this->authenticate();
		} catch ( Exception $exception ) {
			WCSSOT_Logger::error( 'Could not authenticate to the Seven Senders API at "' . $this->getApiBaseUrl() . '" with access key "' . $this->getApiAccessKey() . '".' );
		}
	}

	/**
	 * Authenticates the app to the Seven Senders API.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	private function authenticate() {
		WCSSOT_Logger::debug( 'Authenticating the app to the Seven Senders API.' );
	}

	/**
	 * Returns the API base URL property.
	 *
	 * @since 0.2.0
	 *
	 * @return string
	 */
	public function getApiBaseUrl() {
		return $this->api_base_url;
	}

	/**
	 * Sets the API base URL property.
	 *
	 * @since 0.2.0
	 *
	 * @param string $api_base_url
	 *
	 * @return void
	 */
	public function setApiBaseUrl( $api_base_url ) {
		$this->api_base_url = $api_base_url;
	}

	/**
	 * Returns the API access key property.
	 *
	 * @since 0.2.0
	 *
	 * @return string
	 */
	public function getApiAccessKey() {
		return $this->api_access_key;
	}

	/**
	 * Sets the API access key property.
	 *
	 * @since 0.2.0
	 *
	 * @param string $api_access_key
	 *
	 * @return void
	 */
	public function setApiAccessKey( $api_access_key ) {
		$this->api_access_key = $api_access_key;
	}
}