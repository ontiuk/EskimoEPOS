<?php
/**
 * Provides logging capabilities for debugging purposes.
 *
 * @version        2.0.0
 * @package        WooCommerce/Classes
 */

defined( 'ABSPATH' ) || exit;

// Load required interface
require_once 'interface-eskimo-logger.php';
require_once 'class-eskimo-log-handler.php';

/**
 * Eskimo_Logger class.
 */
class Eskimo_Logger implements Eskimo_Logger_Interface {

    /**
     * Stores registered log handlers.
     *
     * @var string
     */
    protected $handler;

	/**
	 * Valid debugging contexts
	 */
    /**
	 * Constructor for the logger.
	 * - If $handler is not provided, the filter 'eskimo_register_log_handler' will be used to define the handler. 
	 * - If $handler is provided, the filter will not be applied and the handler will be used directly.
     *
     * @param array  $handler Optional. Log handler. 
     */
	public function __construct( $handler = null) {

		// Set log handler
		$handler = ( null === $handler ) ? apply_filters( 'eskimo_register_log_handler', '' ) : $handler;

		// Check a valid handler
		$implements = class_implements( $handler );
		if ( is_object( $handler ) && is_array( $implements ) && in_array( 'Eskimo_Log_Handler_Interface', $implements, true ) ) {
			$this->handler  = $handler;
   		} else {
			_doing_it_wrong(
				__METHOD__,
				sprintf(
					/* translators: 1: class name 2: WC_Log_Handler_Interface */
					__( 'The provided handler %1$s does not implement %2$s.', 'eskimo' ),
					'<code>' . esc_html( is_object( $handler ) ? get_class( $handler ) : $handler ) . '</code>',
					'<code>WC_Log_Handler_Interface</code>'
				),
				'1.3.0'
			);
		}
    }

    /**
     * Add a log entry.
     *
     * @param string $message Log message.
     * @param array  $level Optional. Additional information for log handler.
     */
    public function log( $message, $level = '' ) {

		// Set up and process log
		$timestamp = current_time( 'timestamp', 1 );
		$message   = apply_filters( 'eskimo_logger_log_message', $message, $level);
		$this->handler->handle( $timestamp, $message, $level );
    }

    /**
     * Clear entries for a chosen file/source.
     *
     * @param string $source Source/handle to clear.
     * @return bool
     */
	public function clear( $handle ) {
		
		// Test and process
		if ( is_callable( [ $this->handler, 'clear' ] ) ) {
			$this->handler->clear( $handle );
        }
        return true;
    }

    /**
     * Clear all logs older than a defined number of days. Defaults to 30 days.
     */
    public function clear_expired_logs() {

		// Set logger storage, defaults to 30 days
		$days      = absint( apply_filters( 'eskimo_logger_days_to_retain_logs', 30 ) );
        $timestamp = strtotime( "-{$days} days" );

		// Delete logs
		if ( is_callable( [ $this->handler, 'delete_logs_before_timestamp' ] ) ) {
			$this->handler->delete_logs_before_timestamp( $timestamp );
		}
    }
}
