<?php
/**
 * Log Handler Interface. Cloned from Woocommerce.
 *
 * @package Eskimo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Eskimo Log Handler Interface
 *
 * Functions that must be defined to correctly fulfill log handler API.
 */
interface Eskimo_Log_Handler_Interface {

    /**
     * Handle a log entry.
     *
     * @param int    $timestamp Log timestamp.
     * @param string $message Log message.
     * @param array  $level Additional information for log handler.
     *
     * @return bool False if value was not handled and true if value was handled.
     */
    public function handle( $timestamp, $message, $level );
}
