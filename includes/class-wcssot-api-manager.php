<?php
/**
 * Contains the Seven Senders API manager class.
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

	/**
	 * @var int $recursion_lock The amount of times to try authenticating a request.
	 */
	private static $recursion_lock = 0;

	/**
	 * @var array $supported_carriers The list of carriers supported by Seven Senders.
	 */
	private static $supported_carriers;

	/**
	 * @var bool $authenticated Whether the plugins has been authenticated in the current request cycle.
	 */
	private static $authenticated = false;

	/**
	 * @var string $api_base_url The base URL of the API.
	 */
	private $api_base_url;

	/**
	 * @var string $api_access_key The access key used for authentication.
	 */
	private $api_access_key;

	/**
	 * @var string $authorization_bearer The authorisation bearer key.
	 */
	private $authorization_bearer;

	/**
	 * WCSSOT_API_Manager constructor.
	 *
	 * @since 0.2.0
	 *
	 * @param string $api_base_url The API base URL.
	 * @param string $api_access_key The API access key for authorisation.
	 */
	public function __construct( $api_base_url, $api_access_key ) {
		WCSSOT_Logger::debug( 'Initialising the API manager class.' );
		/**
		 * Fires before initialising the API manager.
		 *
		 * @since 0.6.0
		 *
		 * @param string $api_base_url The API base URl.
		 * @param string $api_access_key The API access key.
		 * @param WCSSOT_API_Manager $manager The current class object.
		 */
		do_action( 'wcssot_api_manager_before_init', $api_base_url, $api_access_key, $this );
		$this->set_api_base_url( $api_base_url );
		$this->set_api_access_key( $api_access_key );
		/**
		 * Fires after initialising the API manager.
		 *
		 * @since 0.6.0
		 *
		 * @param string $api_base_url The API base URl.
		 * @param string $api_access_key The API access key.
		 * @param WCSSOT_API_Manager $manager The current class object.
		 */
		do_action( 'wcssot_api_manager_after_init', $api_base_url, $api_access_key, $this );
	}

	/**
	 * Returns a list of orders based on the provided parameters.
	 *
	 * @since 0.2.0
	 *
	 * @param array $params The list of parameters to fetch the orders by.
	 *
	 * @return array The response from the Seven Senders API.
	 * @throws Exception
	 */
	public function get_orders( $params ) {
		WCSSOT_Logger::debug( 'Fetching orders from the API.' );
		/**
		 * Fires before getting the orders from the API.
		 *
		 * @since 0.6.0
		 *
		 * @param array $params The list of parameters to fetch the orders by.
		 * @param WCSSOT_API_Manager $manager The current class object.
		 */
		do_action( 'wcssot_api_manager_before_get_orders', $params, $this );

		/**
		 * Filters the response from the API after fetching the orders.
		 *
		 * @since 0.6.0
		 *
		 * @param array $response The response from the API.
		 * @param array $params The list of parameters.
		 * @param WCSSOT_API_Manager $manager The current class object.
		 */
		$response = apply_filters(
			'wcssot_api_manager_get_orders',
			$this->request( [], 'orders', 'GET', $params ),
			$params,
			$this
		);
		if ( empty( $response['body'] ) ) {
			throw new Exception( 'Response body is empty.' );
		}
		$body = json_decode( $response['body'], true );
		/**
		 * Filters the default value to return in case no orders are fetched.
		 *
		 * @since 2.0.0
		 *
		 * @param array $orders The default value to return.
		 * @param array $params The list of parameters.
		 * @param WCSSOT_API_Manager $manager The current class object.
		 */
		$orders = apply_filters(
			'wcssot_api_manager_get_orders_default_value',
			[],
			$params,
			$this
		);
		if ( empty( $body ) ) {
			return $orders;
		}
		foreach ( $body as $entry ) {
			$orders[] = $entry;
		}
		/**
		 * Fires after getting the orders from the API.
		 *
		 * @since 2.0.2
		 *
		 * @param array $params The list of parameters to fetch the orders by.
		 * @param WCSSOT_API_Manager $manager The current class object.
		 */
		do_action( 'wcssot_api_manager_after_get_orders', $params, $this );

		/**
		 * Filters the list of orders from the API after fetching them.
		 *
		 * @since 2.0.0
		 *
		 * @param array $orders The list of orders from the API.
		 * @param array $params The list of parameters.
		 * @param WCSSOT_API_Manager $manager The current class object.
		 */
		return apply_filters(
			'wcssot_api_manager_get_orders_list',
			$orders,
			$params,
			$this
		);
	}

	/**
	 * Sends an API request with the provided parameters and returns the received response.
	 *
	 * @since 0.2.0
	 *
	 * @param array $data The list of parameters to request.
	 * @param string $endpoint The endpoint to use for the request.
	 * @param string $method The HTTP method to use for the request.
	 * @param array $params The list of parameters to add.
	 * @param bool $authenticate Whether to authenticate before requesting.
	 *
	 * @return array The response from the API.
	 * @throws Exception
	 */
	private function request( $data, $endpoint, $method, $params = [], $authenticate = true ) {
		WCSSOT_Logger::debug( 'Initialising request to the API for the "' . $endpoint . '" endpoint.' );
		/**
		 * Fires before sending the request to the API.
		 *
		 * @since 0.6.0
		 *
		 * @param array $data The list of parameters to request.
		 * @param string $endpoint The endpoint to use for the request.
		 * @param string $method The HTTP method to use for the request.
		 * @param array $params The list of parameters to add.
		 * @param bool $authenticate Whether to authenticate before requesting.
		 * @param WCSSOT_API_Manager $manager The current class object.
		 */
		do_action( 'wcssot_api_manager_before_request', $data, $endpoint, $method, $params, $authenticate, $this );
		if ( $authenticate && ! self::$authenticated ) {
			$this->authenticate();
		}
		$headers = array_merge( [
			'Content-Type' => 'application/json'
		], $this->get_authorization_headers() );
		/**
		 * Filters the list of request parameters.
		 *
		 * @since 0.6.0
		 *
		 * @param array $args The list of parameters.
		 * @param array $data The list of parameters to request.
		 * @param string $endpoint The endpoint to use for the request.
		 * @param string $method The HTTP method to use for the request.
		 * @param array $params The list of parameters to add.
		 * @param bool $authenticate Whether to authenticate before requesting.
		 * @param WCSSOT_API_Manager $manager The current class object.
		 */
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
            /**
             * Allows 3rd parties to analyse api wp response errors.
             *
             * @param \WP_Error $response  WP error object aka request response.
             * @param array $data          The list of parameters to request.
             * @param string $endpoint     The endpoint to use for the request.
             * @param string $method       The HTTP method to use for the request.
             * @param array $params        The list of parameters to add.
             */
			do_action(
			    'wcssot_api_manager_request_wp_error_action',
                $response,
                $data,
                $endpoint,
                $method,
                $params
            );
			throw new Exception( $response->get_error_message() );
		}
		$response_code = wp_remote_retrieve_response_code( $response );
		/**
		 * Filters the number of authentication tries.
		 *
		 * @since 0.6.0
		 *
		 * @param int $tries The limit of number of tries.
		 * @param int $lock The number of tries already tried.
		 * @param array $response The response returned from the API.
		 * @param array $data The list of parameters to request.
		 * @param string $endpoint The endpoint to use for the request.
		 * @param string $method The HTTP method to use for the request.
		 * @param array $params The list of parameters to add.
		 * @param bool $authenticate Whether to authenticate before requesting.
		 * @param WCSSOT_API_Manager $manager The current class object.
		 */
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
		/**
		 * Filters whether the response code is valid.
		 *
		 * @since 0.6.0
		 *
		 * @param bool $valid Whether the code is valid.
		 * @param int $code The response code.
		 * @param array $response The response from the API.
		 * @param array $data The list of parameters to request.
		 * @param string $endpoint The endpoint to use for the request.
		 * @param string $method The HTTP method to use for the request.
		 * @param array $params The list of parameters to add.
		 * @param bool $authenticate Whether to authenticate before requesting.
		 * @param WCSSOT_API_Manager $manager The current class object.
		 */
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
            /**
             * Allows 3rd parties to analyse api http response errors.
             *
             * @param int $code         The response code.
             * @param array $response   The response from the API.
             * @param array $data       The list of parameters to request.
             * @param string $endpoint  The endpoint to use for the request.
             * @param string $method    The HTTP method to use for the request.
             * @param array $params     The list of parameters to add.
             */
            do_action(
                'wcssot_api_manager_request_http_error_action',
                $response_code,
                $response,
                $data,
                $endpoint,
                $method,
                $params
            );
			throw new Exception( 'The API responded with an invalid HTTP code "' . $response_code . '".' );
		}
		/**
		 * Fires after the request was sent to the API.
		 *
		 * @since 0.6.0
		 *
		 * @param array $data The list of parameters to request.
		 * @param string $endpoint The endpoint to use for the request.
		 * @param string $method The HTTP method to use for the request.
		 * @param array $params The list of parameters to add.
		 * @param bool $authenticate Whether to authenticate before requesting.
		 * @param WCSSOT_API_Manager $manager The current class object.
		 */
		do_action( 'wcssot_api_manager_after_request', $data, $endpoint, $method, $params, $authenticate, $this );

		/**
		 * Filters the response from the API.
		 *
		 * @since 0.6.0
		 *
		 * @param array $response The response from the API.
		 * @param array $data The list of parameters to request.
		 * @param string $endpoint The endpoint to use for the request.
		 * @param string $method The HTTP method to use for the request.
		 * @param array $params The list of parameters to add.
		 * @param bool $authenticate Whether to authenticate before requesting.
		 * @param WCSSOT_API_Manager $manager The current class object.
		 */
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
		/**
		 * Fires before authenticating the plugin with the API.
		 *
		 * @since 0.6.0
		 *
		 * @param WCSSOT_API_Manager $manager The current class object.
		 */
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
		/**
		 * Fires after authenticating with the API.
		 *
		 * @since 0.6.0
		 *
		 * @param WCSSOT_API_Manager $manager The current class object.
		 */
		do_action( 'wcssot_api_manager_after_authenticate', $this );
	}

	/**
	 * Returns the API access key property.
	 *
	 * @since 0.2.0
	 *
	 * @return string The API access key.
	 */
	public function get_api_access_key() {
		/**
		 * Filters the API access key.
		 *
		 * @since 0.6.0
		 *
		 * @param string $key The API access key.
		 * @param WCSSOT_API_Manager $manager The current class object.
		 */
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
		/**
		 * Filters the API access key to set.
		 *
		 * @since 0.6.0
		 *
		 * @param string $key The API access key.
		 * @param WCSSOT_API_Manager $manager The current class object.
		 */
		$this->api_access_key = apply_filters( 'wcssot_api_manager_set_api_access_key', $api_access_key, $this );
	}

	/**
	 * Returns the authorization headers.
	 *
	 * @since 0.2.0
	 *
	 * @return array The authorisation headers.
	 */
	private function get_authorization_headers() {
		$headers = [];

		if ( ! empty( $this->get_authorization_bearer() ) ) {
			$headers['Authorization'] = 'Bearer ' . $this->get_authorization_bearer();
		}

		/**
		 * Filters the authorisation headers.
		 *
		 * @since 0.6.0
		 *
		 * @param array $headers The authorisation headers.
		 * @param WCSSOT_API_Manager $manager The current class object.
		 */
		return apply_filters( 'wcssot_api_manager_get_authorization_headers', $headers, $this );
	}

	/**
	 * Returns the authorization bearer.
	 *
	 * @since 0.2.0
	 *
	 * @return string The authorisation bearer.
	 */
	public function get_authorization_bearer() {
		/**
		 * Filters the authorisation bearer.
		 *
		 * @since 0.6.0
		 *
		 * @param string $bearer The authorisation bearer.
		 * @param WCSSOT_API_Manager $manager The current class object.
		 */
		return apply_filters( 'wcssot_api_manager_get_authorization_bearer', $this->authorization_bearer, $this );
	}

	/**
	 * Sets the authorization bearer.
	 *
	 * @since 0.2.0
	 *
	 * @param string $authorization_bearer The authorisation bearer to set.
	 *
	 * @return void
	 */
	public function set_authorization_bearer( $authorization_bearer ) {
		/**
		 * Filters the authorisation bearer to set.
		 *
		 * @since 0.6.0
		 *
		 * @param string $authorization_bearer The authorisation bearer to set.
		 * @param WCSSOT_API_Manager $manager The current class object.
		 */
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
	 * @param string $endpoint The API endpoint.
	 * @param array $params The URL arguments to add.
	 *
	 * @return string The final API endpoint URL.
	 */
	public function get_endpoint_url( $endpoint, $params = [] ) {
		$url = $this->get_api_base_url() . '/' . $endpoint;
		if ( ! empty( $params ) ) {
			$url .= '?' . http_build_query( $params );
		}

		/**
		 * Filters the API endpoint URL.
		 *
		 * @since 0.6.0
		 *
		 * @param string $url The final API endpoint URL.
		 * @param string $endpoint The API endpoint to get.
		 * @param array $params The list of arguments for the URL.
		 * @param WCSSOT_API_Manager $manager The current class object.
		 */
		return apply_filters( 'wcssot_api_manager_get_endpoint_url', $url, $endpoint, $params, $this );
	}

	/**
	 * Returns the API base URL property.
	 *
	 * @since 0.2.0
	 *
	 * @return string The API base URL.
	 */
	public function get_api_base_url() {
		/**
		 * Filters the API base URL requested.
		 *
		 * @since 0.6.0
		 *
		 * @param string $base_url The API base URL to get.
		 * @param WCSSOT_API_Manager $manager The current class object.
		 */
		return apply_filters( 'wcssot_apimanager_get_api_base_url', $this->api_base_url, $this );
	}

	/**
	 * Sets the API base URL property.
	 *
	 * @since 0.2.0
	 *
	 * @param string $api_base_url The API base URL to set.
	 *
	 * @return void
	 */
	public function set_api_base_url( $api_base_url ) {
		/**
		 * Filters the API base URL to set.
		 *
		 * @since 0.6.0
		 *
		 * @param string $api_base_url The API base URL to set.
		 * @param WCSSOT_API_Manager $manager The current class object.
		 */
		$this->api_base_url = apply_filters( 'wcssot_api_manager_set_api_base_url', $api_base_url, $this );
	}

	/**
	 * Creates a new order entry in Seven Senders.
	 *
	 * @param array $data The data of the order to pass to the API.
	 *
	 * @return bool Whether the order has been created.
	 */
	public function create_order( $data ) {
		WCSSOT_Logger::debug( 'Creating a new order entry for order #' . $data['order_id'] . '.' );
		/**
		 * Fires before creating the order.
		 *
		 * @since 0.6.0
		 *
		 * @param array $data The data to pass to the request.
		 * @param WCSSOT_API_Manager $manager The current class object.
		 */
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
		/**
		 * Fires after creating the order.
		 *
		 * @since 0.6.0
		 *
		 * @param array $data The data to pass to the request.
		 * @param WCSSOT_API_Manager $manager The current class object.
		 */
		do_action( 'wcssot_api_manager_after_create_order', $response, $data, $this );

		/**
		 * Filters whether the order has been created.
		 *
		 * @since 0.6.0
		 *
		 * @param bool $created Whether the order has been created.
		 * @param array $response The API response.
		 * @param array $data The data passed to the API.
		 * @param WCSSOT_API_Manager $manager The current class object.
		 */
		return apply_filters( 'wcssot_api_manager_created_order', true, $response, $data, $this );
	}

	/**
	 * Sets the order state in Seven Senders.
	 *
	 * @since 0.2.0
	 *
	 * @param \WC_Order $order The order object to set the state for.
	 * @param string $state The state to set.
	 *
	 * @return bool Whether the order state has been set.
	 */
	public function set_order_state( $order, $state ) {
		WCSSOT_Logger::debug( 'Setting order state to "' . $state . '" for order #' . $order->get_id() . '.' );
		/**
		 * Fires before setting the order state.
		 *
		 * @since 0.6.0
		 *
		 * @param \WC_Order $order The order object to set the state for.
		 * @param string $state The state to set.
		 * @param WCSSOT_API_Manager $manager The current class object.
		 */
		do_action( 'wcssot_api_manager_before_set_order_state', $order, $state, $this );
		try {
			/**
			 * Filters the order state request parameters.
			 *
			 * @since 0.6.0
			 *
			 * @param array $params The parameters for the request.
			 * @param string $state The state to set.
			 * @param WCSSOT_API_Manager $manager The current class object.
			 */
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
		/**
		 * Fires after the order state has been set.
		 *
		 * @since 0.6.0
		 *
		 * @param array $response The response from the API.
		 * @param \WC_Order $order The order object.
		 * @param string $state The state that got set.
		 * @param WCSSOT_API_Manager $manager The current class object.
		 */
		do_action( 'wcssot_api_manager_after_set_order_state', $response, $order, $state, $this );

		/**
		 * Filters whether the order state has been set.
		 *
		 * @since 0.6.0
		 *
		 * @param bool $set Whether the order state has been set.
		 * @param array $response The response from the API.
		 * @param string $state The state that got set.
		 * @param WCSSOT_API_Manager $manager The current class object.
		 */
		return apply_filters( 'wcssot_api_manager_set_order_state', true, $response, $order, $state, $this );
	}

	/**
	 * Returns the Seven Senders supported carriers list.
	 *
	 * @since 0.3.0
	 *
	 * @return array The list of supported carriers.
	 */
	public function get_supported_carriers() {
		WCSSOT_Logger::debug( 'Trying to get supported carriers.' );
		/**
		 * Fires before getting the supported carriers.
		 *
		 * @since 0.6.0
		 *
		 * @param WCSSOT_API_Manager $manager The current class object.
		 */
		do_action( 'wcssot_api_manager_before_get_supported_carriers', $this );
		if ( ! empty( self::$supported_carriers ) ) {
			WCSSOT_Logger::debug( 'Returning already fetched supported carriers.' );

			return self::$supported_carriers;
		}
		WCSSOT_Logger::debug( 'Trying to fetch supported carriers from Seven Senders.' );
		/**
		 * Filters the default list of carriers.
		 *
		 * @since 0.6.0
		 *
		 * @param array $carriers The default list of carriers.
		 * @param WCSSOT_API_Manager $manager The current class object.
		 */
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
				/**
				 * Fires after getting the supported carriers.
				 *
				 * @since 0.6.0
				 *
				 * @param array $carriers The list of supported carriers.
				 * @param array $response The response from the API.
				 * @param WCSSOT_API_Manager $manager The current class object.
				 */
				do_action( 'wcssot_api_manager_after_get_supported_carriers', $carriers, $response, $this );
			} catch ( Exception $exception ) {
				WCSSOT_Logger::error( 'Could not fetch supported carriers from Seven Senders.' );
			}
		}

		/**
		 * Filters the supported carriers.
		 *
		 * @since 0.6.0
		 *
		 * @param array $carriers The list of supported carriers.
		 * @param WCSSOT_API_Manager $manager The current class object.
		 */
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
	 * @param array $data The data to pass to the API.
	 *
	 * @return bool Whether the shipment has been created.
	 */
	public function create_shipment( $data ) {
		WCSSOT_Logger::debug( 'Creating a new shipment entry for order #' . $data['order_id'] . '.' );
		/**
		 * Fires before creating the shipment.
		 *
		 * @since 0.6.0
		 *
		 * @param array $data The data to pass to the API.
		 * @param WCSSOT_API_Manager $manager The current class object.
		 */
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
		/**
		 * Fires after creating the shipment.
		 *
		 * @since 0.6.0
		 *
		 * @param array $response The response from the API.
		 * @param array $data The data passed to the API.
		 * @param WCSSOT_API_Manager $manager The current class object.
		 */
		do_action( 'wcssot_api_manager_after_create_shipment', $response, $data, $this );

		/**
		 * Filters whether the shipment has been created.
		 *
		 * @since 0.6.0
		 *
		 * @param bool $created Whether the shipment has been created.
		 * @param array $response The response from the API.
		 * @param array $data The data passed to the API.
		 * @param WCSSOT_API_Manager $manager The current class object.
		 */
		return apply_filters( 'wcssot_api_manager_created_shipment', true, $response, $data, $this );
	}
}