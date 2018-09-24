<?php

/**
 * The public-facing functionality of the plugin
 *
 * @link       https://on.tinternet.co.uk
 * @package    Eskimo
 * @subpackage Eskimo/public
 */

/**
 * The public-facing functionality of the plugin
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript
 *
 * @package    Eskimo
 * @subpackage Eskimo/public
 * @author     Stephen Betley <on@tinternet.co.uk>
 */
class Eskimo_Public {

	/**
	 * The ID of this plugin
     *
	 * @var      string    $plugin_name    The ID of this plugin
	 */
	private $plugin_name;

	/**
	 * The version of this plugin
	 *
	 * @var      string    $version    The current version of this plugin
	 */
	private $version;

	/**
	 * Initialize the class and set its properties
	 *
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'assets/css/eskimo-public.css', [], $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'assets/js/eskimo-public.js', array( 'jquery' ), $this->version, false );
	}
}
