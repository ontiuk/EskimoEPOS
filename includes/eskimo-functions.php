<?php

/**
 * Various required dependency checking functions
 *
 * @package     Eskimo
 * @subpackage  Eskimo/includes
 */
 
/**
 * Get a list of active plugins 
 *
 * @return  array
 */
if ( ! function_exists( 'get_active_plugins') ){ 
	function get_active_plugins(){ 
		$active_plugins = (array) get_option( 'active_plugins', [] );
		if ( is_multisite() ) $active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', [] ) );
		return $active_plugins; 
	}
}

/**
 * Is WooCommerce Active
 */
if ( ! function_exists( 'is_woocommerce_active' ) ) {
	function is_woocommerce_active() {
		$active_plugins = get_active_plugins(); 		
		return in_array( 'woocommerce/woocommerce.php', $active_plugins ) || array_key_exists( 'woocommerce/woocommerce.php', $active_plugins );
	}
}

/**
 * WooCommerce Required Notice 
 */
if ( ! function_exists( 'woocommerce_required_notice' ) ) {
    function woocommerce_required_notice() {
        $error_msg = __( '<b>WooCommerce not found.</b>. Eskimo EPOS requires a minimum of WooCommerce v3.0.', 'eskimo' );
		echo sprintf( '<div class="notice notice-error"><p>%s</p></div>' );	
	}
}

/**
 * Woocommerce REST API active
 */
if ( ! function_exists( 'woocommerce_rest_api_active' ) ) {
    function woocommerce_rest_api_active() {
        // First test: woocommerce ok
        if ( !is_woocommerce_active() ) { return false; }

        // Option
        $woocommerce_api_enabled = get_option( 'woocommerce_api_enabled', 'no' );
        return ( $woocommerce_api_enabled === 'yes' ) ? true : false;
    }
}    
