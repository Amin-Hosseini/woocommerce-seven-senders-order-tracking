<?php
/**
 * The plugin options manager class.
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
 * @since 1.2.0
 */

namespace WCSSOT;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The plugin options manager class.
 *
 * @since 1.2.0
 *
 * @class WCSSOT_Options_Manager
 */
class WCSSOT_Options_Manager {
	/**
	 * @var array $options_required A list of option IDs that are required by the plugin.
	 */
	private $options_required = [];

	/**
	 * @var WCSSOT $wcssot The instance of the main plugin class object.
	 */
	private $wcssot;

	/**
	 * WCSSOT_Options_Manager constructor.
	 *
	 * @since 1.2.0
	 *
	 * @param WCSSOT $wcssot The instance of the main plugin class.
	 */
	public function __construct( $wcssot ) {
		WCSSOT_Logger::debug( 'Initialising the options manager class.' );
		/**
		 * Fires before initialising the WCSSOT_Options_Manager class.
		 *
		 * @since 1.2.0
		 *
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		do_action( 'wcssot_options_manager_before_init', $this );
		$this->wcssot = $wcssot;
		$this->initialise_properties();
		/**
		 * Fires after initialising the WCSSOT class.
		 *
		 * @since 1.2.0
		 *
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		do_action( 'wcssot_options_manager_after_init', $this );
	}

	/**
	 * Initialises the class properties.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	private function initialise_properties() {
		/**
		 * Fires before initialising the class properties.
		 *
		 * @since 1.2.0
		 *
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		do_action( 'wcssot_options_manager_before_initialise_properties', $this );
		/**
		 * Filters the default options required by the plugin.
		 *
		 * @since 0.6.0
		 * @since 1.2.0 Moved from the main plugin class and added an extra parameter for the current object instance.
		 *
		 * @param array $options The default options required.
		 * @param WCSSOT $wcssot The current class object.
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		$this->set_options_required( apply_filters( 'wcssot_set_default_options_required', [
			'wcssot_api_base_url',
			'wcssot_api_access_key',
			'wcssot_tracking_page_base_url',
		], $this->wcssot, $this ) );
		/**
		 * Fires after initialising the class properties.
		 *
		 * @since 1.2.0
		 *
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		do_action( 'wcssot_options_manager_after_initialise_properties', $this );
	}

	/**
	 * Returns the options required property.
	 *
	 * @since 1.2.0
	 *
	 * @return array The list of options required.
	 */
	public function get_options_required() {
		/**
		 * Filters the list of options required by the plugin.
		 *
		 * @since 0.6.0
		 * @since 1.2.0 Moved from the main plugin class and added an extra parameter for the current object instance.
		 *
		 * @param array $options The list of options required.
		 * @param WCSSOT $wcssot The current class object.
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		return apply_filters( 'wcssot_get_options_required', $this->options_required, $this->wcssot, $this );
	}

	/**
	 * Sets the options required property.
	 *
	 * @since 1.2.0
	 *
	 * @param array $options_required The list of options required to set.
	 *
	 * @return void
	 */
	public function set_options_required( $options_required ) {
		/**
		 * Filters the list of options required to set.
		 *
		 * @since 0.6.0
		 * @since 1.2.0 Moved from the main plugin class and added an extra parameter for the current object instance.
		 *
		 * @param array $options The list of options required to set.
		 * @param WCSSOT $wcssot The current class object.
		 * @param WCSSOT_Options_Manager $wcssot_options_manager The current class object.
		 */
		$this->options_required = apply_filters(
			'wcssot_set_options_required',
			$options_required,
			$this->wcssot,
			$this
		);
	}
}