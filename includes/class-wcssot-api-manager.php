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

	/** @var int $recursion_lock */
	private static $recursion_lock = 0;

	/** @var string $api_base_url */
	private $api_base_url;

	/** @var string $api_access_key */
	private $api_access_key;

	/** @var string $authorization_bearer */
	private $authorization_bearer;

	/**
	 * WCSSOT_API_Manager constructor.
	 *
	 * @since 0.2.0
	 *
	 * @param string $api_base_url
	 * @param string $api_access_key
	 */
	public function __construct( $api_base_url, $api_access_key ) {
		WCSSOT_Logger::debug( 'Initialising the API manager class.' );
		$this->setApiBaseUrl( $api_base_url );
		$this->setApiAccessKey( $api_access_key );
	}

	/**
	 * Returns a list of orders based on the provided parameters.
	 *
	 * @since 0.2.0
	 *
	 * @param $params
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getOrders( $params ) {
		WCSSOT_Logger::debug( 'Fetching orders from the API.' );

		return $this->request( [], 'orders', 'GET', $params );
	}

	/**
	 * Sends an API request with the provided parameters and returns the received response.
	 *
	 * @since 0.2.0
	 *
	 * @param array $data
	 * @param string $endpoint
	 * @param string $method
	 * @param array $params
	 *
	 * @return array
	 * @throws Exception
	 */
	private function request( $data, $endpoint, $method, $params = [] ) {
		WCSSOT_Logger::debug( 'Initialising request to the API for the "' . $endpoint . '" endpoint.' );
		$headers = array_merge( [
			'Content-Type' => 'application/json'
		], $this->getAuthorizationHeaders() );

		$response = wp_safe_remote_request( $this->getEndpointUrl( $endpoint, $params ), [
			'method'     => $method,
			'headers'    => $headers,
			'body'       => json_encode( $data ),
			'timeout'    => 10,
			'blocking'   => true,
			'user-agent' => 'WooCommerce ' . WC()->version . '; ' . get_site_url(),
		] );

		WCSSOT_Logger::debug( 'Sent request to the "' . $endpoint . '" endpoint.' );

		if ( is_wp_error( $response ) ) {
			WCSSOT_Logger::error( 'The request to the "' . $endpoint . '" endpoint resulted in an error.' );
			throw new Exception( $response->get_error_message() );
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( $response_code === 401 && self::$recursion_lock ++ < 5 ) {
			WCSSOT_Logger::debug( 'Attempting to authenticate with the Seven Senders API. (Try #' . self::$recursion_lock . ')' );

			$this->authenticate();

			return $this->request( $data, $endpoint, $method );
		}

		if ( $response_code < 200 || $response_code > 299 ) {
			WCSSOT_Logger::error( 'The API responded with an invalid HTTP code "' . $response_code . '".' );
			throw new Exception( 'The API responded with an invalid HTTP code "' . $response_code . '".' );
		}

		return $response;
	}

	/**
	 * Returns the authorization headers.
	 *
	 * @since 0.2.0
	 *
	 * @return array
	 */
	private function getAuthorizationHeaders() {
		$headers = [];

		if ( ! empty( $this->getAuthorizationBearer() ) ) {
			$headers['Authorization'] = 'Bearer ' . $this->getAuthorizationBearer();
		}

		return $headers;
	}

	/**
	 * Returns the authorization bearer.
	 *
	 * @since 0.2.0
	 *
	 * @return string
	 */
	public function getAuthorizationBearer() {
		return $this->authorization_bearer;
	}

	/**
	 * Sets the authorization bearer.
	 *
	 * @since 0.2.0
	 *
	 * @param string $authorization_bearer
	 *
	 * @return void
	 */
	public function setAuthorizationBearer( $authorization_bearer ) {
		$this->authorization_bearer = $authorization_bearer;
	}

	/**
	 * Returns the full URL for the provided endpoint.
	 *
	 * @since 0.2.0
	 *
	 * @param string $endpoint
	 * @param array $params
	 *
	 * @return string
	 */
	public function getEndpointUrl( $endpoint, $params = [] ) {
		$url = $this->getApiBaseUrl() . '/' . $endpoint;
		if ( ! empty( $params ) ) {
			$url .= '?' . http_build_query( $params );
		}

		return $url;
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
	 * Authenticates the app to the Seven Senders API.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 * @throws Exception
	 */
	private function authenticate() {
		WCSSOT_Logger::debug( 'Authenticating the app to the Seven Senders API.' );
		try {
			$response = $this->request( [
				'access_key' => $this->getApiAccessKey()
			], 'token', 'POST' );
		} catch ( Exception $exception ) {
			WCSSOT_Logger::error( 'Could not authenticate app with access key "' . $this->getApiAccessKey() . '".' );

			return;
		}
		if ( empty( $response['body'] ) ) {
			WCSSOT_Logger::error( 'The body of the authentication response is missing!' );
			throw new Exception( "The body of the authentication response is missing!" );
		}
		$body = json_decode( $response['body'], true );
		if ( empty( $body['token'] ) ) {
			WCSSOT_Logger::error( 'The token is missing from the authentication response!' );
			throw new Exception( "The token is missing from the authentication response!" );
		}
		$this->setAuthorizationBearer( $body['token'] );
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

	/**
	 * Creates a new order entry in Seven Senders.
	 *
	 * @param array $data
	 *
	 * @return bool
	 */
	public function createOrder( $data ) {
		WCSSOT_Logger::debug( 'Creating a new order entry for order #' . $data['order_id'] . '.' );
		try {
			$response = $this->request( $data, 'orders', 'POST' );
		} catch ( Exception $exception ) {
			WCSSOT_Logger::error( 'Could not create order entry of order #' . $data['order_id'] . '.' );

			return false;
		}
		WCSSOT_Logger::debug( 'Successfully created the order and received the following response: ' . $response['body'] );

		return true;
	}
}