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

/**
 * Add a log message
 *
 * @param string $message
 */
function eskimo_log( $message = '', $level = '' ) {
	
	// Master switch
	if ( ! defined( 'ESKIMO_DEBUG' ) || ! ESKIMO_DEBUG ) { return; }

	// Bad call
	if ( empty( $message ) ) { return; }
	$message = sanitize_text_field( $message );
	$level 	 = sanitize_text_field( $level );

	// Log message
	$logger = eskimo_get_logger();
	$logger->log( $message, $level );
}

/**
 * Get a shared Eskimo logger instance. Cloned from Woocommerce
 *
 * Use the eskimo_logging_class filter to change the logging class. You may provide one of the following:
 *     - a class name which will be instantiated as `new $class` with no arguments
 *     - an instance which will be used directly as the logger
 * In either case, the class or instance *must* implement Eskimo_Logger_Interface.
 *
 * @see Eskimo_Logger_Interface
 *
 * @return Eskimo_Logger
 */
function eskimo_get_logger() {

	// Store logger instance
    static $logger = null;

	// Filterable logging clas
    $class = apply_filters( 'eskimo_logging_class', 'Eskimo_Logger' );

	// Check is pre-existing instance
    if ( null !== $logger && is_string( $class ) && is_a( $logger, $class ) ) {
        return $logger;
    }

	// Class check
    $implements = class_implements( $class );

	// Set logger or error
    if ( is_array( $implements ) && in_array( 'Eskimo_Logger_Interface', $implements, true ) ) {
        $logger = is_object( $class ) ? $class : new $class();
    } else {
        _doing_it_wrong(
            __FUNCTION__,
            sprintf(
                /* translators: 1: class name 2: eskimo_logging_class 3: Eskimo_Logger_Interface */
                __( 'The class %1$s provided by %2$s filter must implement %3$s.', 'eskimo' ),
                '<code>' . esc_html( is_object( $class ) ? get_class( $class ) : $class ) . '</code>',
                '<code>Eskimo_logging_class</code>',
                '<code>Eskimo_Logger_Interface</code>'
            ),
            '1.3.0'
        );

        $logger = is_a( $logger, 'Eskimo_Logger' ) ? $logger : new Eskimo_Logger();
    }

    return $logger;
}

/**
 * Trigger logging cleanup using the logging class.
 * - cloned from wc_cleanup_logs
 */
function eskimo_cleanup_logs() {

	// Get logger interface
    $logger = eskimo_get_logger();

	// Test logger and process clean-up
    if ( is_callable( array( $logger, 'clear_expired_logs' ) ) ) {
        $logger->clear_expired_logs();
    }
}
add_action( 'eskimo_cleanup_logs', 'eskimo_cleanup_logs' );

/**
 * Registers the default log handler. Currently logs to file only.
 *
 * @param array $handlers
 * @return array
 */
function eskimo_register_default_log_handler( $handler ) {

	// Set handler
	$handler_class = ( defined( 'ESKIMO_LOG_HANDLER' ) && class_exists( ESKIMO_LOG_HANDLER ) ) ? ESKIMO_LOG_HANDLER : 'Eskimo_Log_Handler';
	$handler = new $handler_class;

	return $handler;
}
add_filter( 'eskimo_register_log_handler', 'eskimo_register_default_log_handler' );
