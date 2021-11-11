<?php

/**
 * Plugin Name:       EskimoEPOS
 * Plugin URI:        https://github.com/ontiuk
 * Description:       Connect to Eskimo EPOS via Eskimo API and resistered Eskimo Reporting / EPOS account
 * Version:           1.4.7
 * Author:            Stephen Betley
 * Author URI:        https://on.tinternet.co.uk
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       eskimo
 * Domain Path:       /languages
 *
 * @link              https://on.tinternet.co.uk
 * @package           Eskimo
 *
 * WC requires at least: 5.0.0
 * WC tested up to: 5.9.0
 * 
EskimoEPOS is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
EskimoEPOS is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with EskimoEPOS. If not, see http://www.gnu.org/licenses/gpl-2.0.txt.
*/

// If this file is called directly, abort
defined( 'WPINC' ) || die;

// Required functions
require_once 'includes/eskimo-functions.php';

// Base dependency test
if ( is_woocommerce_active() ) { 

	// The plugin name
	define( 'ESKIMO_NAME', 'eskimo' );
	
    // Currently Eskimo EPOS plugin version
    define( 'ESKIMO_VERSION', 		'1.4.5' );

    // Eskimo debugging mode
	define( 'ESKIMO_DEBUG', 		true );

	// Eskimo debug settings
	define( 'ESKIMO_TRACE', 		true );
	define( 'ESKIMO_WC_DEBUG', 		true );
	define( 'ESKIMO_REST_DEBUG', 	true );
    define( 'ESKIMO_CART_DEBUG', 	true );
    define( 'ESKIMO_API_DEBUG', 	true );
    define( 'ESKIMO_CRON_DEBUG', 	true );
    define( 'ESKIMO_EPOS_DEBUG', 	false );

	// Default Eskimo log dir: currently same as WP_DEBUG
	define( 'ESKIMO_LOG_DIR', WP_CONTENT_DIR );

	// Rest Route deliminator
	define( 'ESKIMO_REST_DELIMINATOR', '|' );
	
	// Development development mode: sets guest user
	define( 'ESKIMO_MODE', 'live' ); // live/test

	/**
	 * Run plugin activation 
	 * - documented in includes/class-eskimo-activator.php
     */
    function activate_eskimo() {
	    require_once plugin_dir_path( __FILE__ ) . 'includes/class-eskimo-activator.php';
	    Eskimo_Activator::activate( __FILE__ );
    }

    /**
	 * Run plugin deactivation
	 * - documented in includes/class-eskimo-deactivator.php
     */
    function deactivate_eskimo() {
	    require_once plugin_dir_path( __FILE__ ) . 'includes/class-eskimo-deactivator.php';
	    Eskimo_Deactivator::deactivate();
    }

    // Register activation and deactivation hooks
    register_activation_hook( __FILE__, 'activate_eskimo' );
    register_deactivation_hook( __FILE__, 'deactivate_eskimo' );

    /**
	 * The core plugin class 
	 * - initializes admin, shared, and public site hooks
	 * - internationalization
     */
    require plugin_dir_path( __FILE__ ) . 'includes/class-eskimo.php';

    // Begins execution of the plugin
    function run_eskimo() {
	    $eskimo = new Eskimo();
    	$eskimo->run();
	}

	// Initialize plugin
	run_eskimo();
	
} else {
    // Dependency: fatal warning notice, woocommerce required
	add_action( 'admin_notices', 'woocommerce_required_notice' ); 
}

// End
