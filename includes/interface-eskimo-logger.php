<?php

/**
 * Eskimo Logger Interface
 *
 * Functions that must be defined to correctly fulfill logger API.
 * @see WC_Logger_interface	Cloned from Woocommerce
 */
interface Eskimo_Logger_Interface {

    /**
     * Add a log entry.
     *
     * @param string $message Log message.
     * @param array  $level Optional. Additional information for log handler.
     */
    public function log( $message, $level = '' );
}
