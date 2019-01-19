<?php

/**
 * Fired during plugin activation
 *
 * @link       https://on.tinternet.co.uk
 * @package    Eskimo
 * @subpackage Eskimo/includes
 */

/**
 * Fired during plugin activation
 * - Defines all code necessary to run during the plugin's activation
 *
 * @package    Eskimo
 * @subpackage Eskimo/includes
 * @author     Stephen Betley <on@tinternet.co.uk>
 */
final class Eskimo_Activator {

	/**
     * Plugin activation requirements 
     *
     * @param   string  $plugin_file
	 */
	public static function activate( $plugin_file ) {

        // Set current PHP version
        $php_version = phpversion();

        /**
         * Eskimo requires PHP 5.4 to function, every host should be here by now!
         */
        if ( version_compare( $php_version, '5.6', '<' ) ) {
			deactivate_plugins( $plugin_file );
			wp_die( __( 'Eskimo EPOS requires PHP 5.6 or newer to function.  Please call your webhosting company and have them upgrade your hosting account to a version of PHP 5.6 or later.', 'eskimo' ) );
		}        

        /**
		 *  Requires woocommerce to be installed and active 
		 */
		if ( ! class_exists( 'WooCommerce' ) ) { 
			deactivate_plugins( $plugin_file );
			wp_die( __( 'Eskimo EPOS requires WooCommerce to run. Please install WooCommerce and activate before attempting to activate again.', 'eskimo' ) );
		}
    }

    /**
	 * Output a message if Eskimo EPOS isn't active 
	 */
	public static function installation_fail(){ 
		echo '<div class="error"><p>' . __( '<b>Eskimo EPOS is disabled</b>. Eskimo EPOS requires Woocommerce 3.0.0 or higher to operate.', 'eskimo' ) . '</p></div>';
	}
}
