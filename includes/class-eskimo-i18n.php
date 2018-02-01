<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation
 *
 * @link       https://on.tinternet.co.uk
 * @package    Eskimo
 * @subpackage Eskimo/includes
 */

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation
 *
 * @package    Eskimo
 * @subpackage Eskimo/includes
 * @author     Stephen Betley <on@tinternet.co.uk>
 */
class Eskimo_i18n {

	/**
	 * Load the plugin text domain for translation.
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'eskimo',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}
}
