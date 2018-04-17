<?php

/**
 * Plugin Name:       EskimoEPOS
 * Plugin URI:        https://github.com/ontiuk
 * Description:       Connect to Eskimo EPOS via Eskimo API and resistered Eskimo Reporting / EPOS account
 * Version:           1.1.0
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
 * WC requires at least: 3.0.0
 * WC tested up to: 3.2.3
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
You have purchased a support contract for the duration of one year from the date 
of purchase that entitles you access to updates of WC Vendors Pro and support 
for WC Vendors Pro. 
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) { die; }

/**
 * Required functions
 */
require_once( 'includes/eskimo-functions.php' );

// Base dependency test
if ( is_woocommerce_active() ) { 
    
    /**
     * Currently Eskimo EPOS plugin version
     */
    define( 'ESKIMO_VERSION', '1.1.0' );

    /**
     * Currently Eskimo debugging state
     */
    define( 'ESKIMO_DEBUG', true );

    /**
     * The code that runs during plugin activation
     * This action is documented in includes/class-eskimo-activator.php
     */
    function activate_eskimo() {
	    require_once plugin_dir_path( __FILE__ ) . 'includes/class-eskimo-activator.php';
	    Eskimo_Activator::activate( __FILE__ );
    }

    /**
     * The code that runs during plugin deactivation
     * This action is documented in includes/class-eskimo-deactivator.php
     */
    function deactivate_eskimo() {
	    require_once plugin_dir_path( __FILE__ ) . 'includes/class-eskimo-deactivator.php';
	    Eskimo_Deactivator::deactivate();
    }

    // Register activation and deactivation hooks
    register_activation_hook( __FILE__, 'activate_eskimo' );
    register_deactivation_hook( __FILE__, 'deactivate_eskimo' );

    /**
     * Include the core cUrl class & dependencies
     */
    require_once plugin_dir_path( __FILE__ ) . 'includes/lib/curl.php';

    /**
     * The core plugin class that is used to define internationalization,
     * admin-specific hooks, and public-facing site hooks
     */
    require plugin_dir_path( __FILE__ ) . 'includes/class-eskimo.php';

    /**
     * Begins execution of the plugin
     */
    function run_eskimo() {
	    $plugin = new Eskimo();
    	$plugin->run();
    }
    run_eskimo();
} else {
    // Dependency fatal warning notice
	add_action( 'admin_notices', 'woocommerce_required_notice' ); 
}

// End
