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

	/** @var array $supported_carriers */
	private static $supported_carriers;

	/** @var bool $authenticated */
	private static $authenticated = false;

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
		do_action( 'wcssot_api_manager_before_init', $api_base_url, $api_access_key, $this );
		$this->set_api_base_url( $api_base_url );
		$this->set_api_access_key( $api_access_key );
		do_action( 'wcssot_api_manager_after_init', $api_base_url, $api_access_key, $this );
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
	public function get_orders( $params ) {
		WCSSOT_Logger::debug( 'Fetching orders from the API.' );
		do_action( 'wcssot_api_manager_before_get_orders', $params, $this );

		return apply_filters(
			'wcssot_api_manager_get_orders',
			$this->request( [], 'orders', 'GET', $params ),
			$params,
			$this
		);
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
	 * @param bool $authenticate
	 *
	 * @return array
	 * @throws Exception
	 */
	private function request( $data, $endpoint, $method, $params = [], $authenticate = true ) {
		WCSSOT_Logger::debug( 'Initialising request to the API for the "' . $endpoint . '" endpoint.' );
		do_action( 'wcssot_api_manager_before_request', $data, $endpoint, $method, $params, $authenticate, $this );
		if ( $authenticate && ! self::$authenticated ) {
			$this->authenticate();
		}
		$headers  = array_merge( [
			'Content-Type' => 'application/json'
		], $this->get_authorization_headers() );
		$response = wp_safe_remote_request( $this->get_endpoint_url( $endpoint, $params ), apply_filters(
			'wcssot_api_manager_request_arguments',
			[
				'method'     => $method,
				'headers'    => $headers,
				'body'       => ! empty( $data ) ? json_encode( $data ) : '',
				'timeout'    => 10,
				'blocking'   => true,
				'user-agent' => 'WooCommerce ' . WC()->version . '; ' . get_site_url(),
			], $data, $endpoint, $method, $params, $authenticate, $this ) );
		WCSSOT_Logger::debug( 'Sent request to the "' . $endpoint . '" endpoint.' );
		if ( is_wp_error( $response ) ) {
			WCSSOT_Logger::error( 'The request to the "' . $endpoint . '" endpoint resulted in an error.' );
			throw new Exception( $response->get_error_message() );
		}
		$response_code = wp_remote_retrieve_response_code( $response );
		if (
			$response_code === 401
			&& self::$recursion_lock ++ < apply_filters(
				'wcssot_api_manager_authentication_tries',
				5,
				self::$recursion_lock,
				$response,
				$data,
				$endpoint,
				$method,
				$params,
				$authenticate,
				$this
			)
		) {
			WCSSOT_Logger::debug(
				'Attempting to authenticate with the Seven Senders API. (Try #' . self::$recursion_lock . ')'
			);
			$this->authenticate();

			return $this->request( $data, $endpoint, $method );
		}
		if (
		! apply_filters(
			'wcssot_api_manager_is_valid_response_code',
			! (
				$response_code < 200 || $response_code > 299
			),
			$response_code,
			$response,
			$data,
			$endpoint,
			$method,
			$params,
			$authenticate,
			$this
		)
		) {
			WCSSOT_Logger::error( 'The API responded with an invalid HTTP code "' . $response_code . '".' );
			throw new Exception( 'The API responded with an invalid HTTP code "' . $response_code . '".' );
		}
		do_action( 'wcssot_api_manager_after_request', $data, $endpoint, $method, $params, $authenticate, $this );

		return apply_filters(
			'wcssot_api_manager_request',
			$response,
			$data,
			$endpoint,
			$method,
			$params,
			$authenticate,
			$this
		);
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
		do_action( 'wcssot_api_manager_before_authenticate', $this );
		try {
			$response = $this->request( apply_filters( 'wcssot_api_manager_authenticate_request_params', [
				'access_key' => $this->get_api_access_key()
			], $this ), 'token', 'POST', [], false );
		} catch ( Exception $exception ) {
			WCSSOT_Logger::error( 'Could not authenticate app with access key "' . $this->get_api_access_key() . '".' );

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
		$this->set_authorization_bearer( $body['token'] );
		self::$authenticated = true;
		do_action( 'wcssot_api_manager_after_authenticate', $this );
	}

	/**
	 * Returns the API access key property.
	 *
	 * @since 0.2.0
	 *
	 * @return string
	 */
	public function get_api_access_key() {
		return apply_filters( 'wcssot_api_manager_get_api_access_key', $this->api_access_key, $this );
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
	public function set_api_access_key( $api_access_key ) {
		$this->api_access_key = apply_filters( 'wcssot_api_manager_set_api_access_key', $api_access_key, $this );
	}

	/**
	 * Returns the authorization headers.
	 *
	 * @since 0.2.0
	 *
	 * @return array
	 */
	private function get_authorization_headers() {
		$headers = [];

		if ( ! empty( $this->get_authorization_bearer() ) ) {
			$headers['Authorization'] = 'Bearer ' . $this->get_authorization_bearer();
		}

		return apply_filters( 'wcssot_api_manager_get_authorization_headers', $headers, $this );
	}

	/**
	 * Returns the authorization bearer.
	 *
	 * @since 0.2.0
	 *
	 * @return string
	 */
	public function get_authorization_bearer() {
		return apply_filters( 'wcssot_api_manager_get_authorization_bearer', $this->authorization_bearer, $this );
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
	public function set_authorization_bearer( $authorization_bearer ) {
		$this->authorization_bearer = apply_filters(
			'wcssot_api_manager_set_authorization_bearer',
			$authorization_bearer,
			$this
		);
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
	public function get_endpoint_url( $endpoint, $params = [] ) {
		$url = $this->get_api_base_url() . '/' . $endpoint;
		if ( ! empty( $params ) ) {
			$url .= '?' . http_build_query( $params );
		}

		return apply_filters( 'wcssot_api_manager_get_endpoint_url', $url, $endpoint, $params, $this );
	}

	/**
	 * Returns the API base URL property.
	 *
	 * @since 0.2.0
	 *
	 * @return string
	 */
	public function get_api_base_url() {
		return apply_filters( 'wcssot_apimanager_get_api_base_url', $this->api_base_url, $this );
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
	public function set_api_base_url( $api_base_url ) {
		$this->api_base_url = apply_filters( 'wcssot_api_manager_set_api_base_url', $api_base_url, $this );
	}

	/**
	 * Creates a new order entry in Seven Senders.
	 *
	 * @param array $data
	 *
	 * @return bool
	 */
	public function create_order( $data ) {
		WCSSOT_Logger::debug( 'Creating a new order entry for order #' . $data['order_id'] . '.' );
		do_action( 'wcssot_api_manager_before_create_order', $data, $this );
		try {
			$response = $this->request( $data, 'orders', 'POST' );
		} catch ( Exception $exception ) {
			WCSSOT_Logger::error( 'Could not create order entry of order #' . $data['order_id'] . '.' );

			return false;
		}
		WCSSOT_Logger::debug(
			'Successfully created the order and received the following response: ' . $response['body']
		);
		do_action( 'wcssot_api_manager_after_create_order', $response, $data, $this );

		return apply_filters( 'wcssot_api_manager_created_order', true, $response, $data, $this );
	}

	/**
	 * Sets the order state in Seven Senders.
	 *
	 * @since 0.2.0
	 *
	 * @param \WC_Order $order
	 * @param string $state
	 *
	 * @return bool
	 */
	public function set_order_state( $order, $state ) {
		WCSSOT_Logger::debug( 'Setting order state to "' . $state . '" for order #' . $order->get_id() . '.' );
		do_action( 'wcssot_api_manager_before_set_order_state', $order, $state, $this );
		try {
			$response = $this->request( apply_filters( 'wcssot_api_manager_set_order_state_request_params', [
				'order_id' => $order->get_order_number(),
				'state'    => $state,
				'datetime' => current_time( 'c' ),
			], $order, $state, $this ), 'order_states', 'POST' );
		} catch ( Exception $exception ) {
			WCSSOT_Logger::error( 'Could not set state for order #' . $order->get_id() . '.' );

			return false;
		}
		WCSSOT_Logger::debug(
			'Successfully set the order state to "' . $state . '" and received the following response: '
			. $response['body']
		);
		do_action( 'wcssot_api_manager_after_set_order_state', $response, $order, $state, $this );

		return apply_filters( 'wcssot_api_manager_set_order_state', true, $response, $order, $state, $this );
	}

	/**
	 * Returns the Seven Senders supported carriers list.
	 *
	 * @since 0.3.0
	 *
	 * @return array
	 */
	public function get_supported_carriers() {
		WCSSOT_Logger::debug( 'Trying to get supported carriers.' );
		do_action( 'wcssot_api_manager_before_get_supported_carriers', $this );
		if ( ! empty( self::$supported_carriers ) ) {
			WCSSOT_Logger::debug( 'Returning already fetched supported carriers.' );

			return self::$supported_carriers;
		}
		WCSSOT_Logger::debug( 'Trying to fetch supported carriers from Seven Senders.' );
		$carriers = apply_filters( 'wcssot_api_manager_default_carriers', [], $this );
		if ( empty( $carriers ) ) {
			try {
				$response = $this->request( [], 'carriers', 'GET' );
				if ( empty( $response['body'] ) ) {
					throw new Exception( 'Response body is empty.' );
				}
				$body = json_decode( $response['body'], true );
				if ( empty( $body ) ) {
					throw new Exception( 'Body contents are empty.' );
				}
				foreach ( $body as $entry ) {
					$carriers[ $entry['code'] ] = $entry;
				}
				do_action( 'wcssot_api_manager_after_get_supported_carriers', $carriers, $response, $this );
			} catch ( Exception $exception ) {
				WCSSOT_Logger::error( 'Could not fetch supported carriers from Seven Senders.' );
			}
		}

		return apply_filters(
			'wcssot_api_manager_get_supported_carriers',
			self::$supported_carriers = $carriers,
			$this
		);
	}

	/**
	 * Creates a new shipment entry in Seven Senders with the provided data.
	 *
	 * @since 0.3.0
	 *
	 * @param array $data
	 *
	 * @return bool
	 */
	public function create_shipment( $data ) {
		WCSSOT_Logger::debug( 'Creating a new shipment entry for order #' . $data['order_id'] . '.' );
		do_action( 'wcssot_api_manager_before_create_shipment', $data, $this );
		try {
			$response = $this->request( $data, 'shipments', 'POST' );
		} catch ( Exception $exception ) {
			WCSSOT_Logger::error( 'Could not create shipment entry of order #' . $data['order_id'] . '.' );

			return false;
		}
		WCSSOT_Logger::debug(
			'Successfully created the shipment and received the following response: ' . $response['body']
		);
		do_action( 'wcssot_api_manager_after_create_shipment', $response, $data, $this );

		return apply_filters( 'wcssot_api_manager_created_shipment', true, $response, $data, $this );
	}
}