<?php

/**
 * Get the EskimoEPOS settings and create a connection to the remote API
 *
 * @link       https://on.tinternet.co.uk
 * @package    Eskimo
 * @subpackage Eskimo/admin
 */

/**
 * EskimoEPOS API creation 
 *
 * Gets the EskimoEPOS API settings and creates the remote API connection
 *
 * @package    Eskimo
 * @subpackage Eskimo/admin
 * @author     Stephen Betley <on@tinternet.co.uk>
 */
final class Eskimo_EPOS {

	/**
	 * Plugin ID
	 *
	 * @var     string    $eskimo    The ID of this plugin
	 */
	private $eskimo;

	/**
	 * Plugin version
	 *
	 * @var     string    $version    The current version of this plugin
	 */
	private $version;

    /**
	 * Plugin debug mode 
	 *
	 * @var     bool    $debug    Plugin is in debug mode
	 */
	private $debug;

	/**
	 * Plugin base directory 
	 *
	 * @var      string    $base_dir  String path for the plugin directory
	 */
    private $base_dir;

    /**
     * API settings: Domain
     *
	 * @var     string    $domain    EPOS setting from WP options
     */
    private $domain;

    /**
     * API settings: Username
     *
	 * @var     string    $username    EPOS setting from WP options
     */
    private $username;

    /**
     * API settings: Password
     *
	 * @var     string    $password    EPOS settings from WP options
     */
    private $password;

	/**
	 * Initialize the class and set its properties
	 *
	 * @param   string    $eskimo     The name of this plugin
	 */
	public function __construct( $eskimo ) {

		// Set up class settings
		$this->eskimo       = $eskimo;
   		$this->version  	= ESKIMO_VERSION;
		$this->debug    	= ESKIMO_EPOS_DEBUG;
    	$this->base_dir		= plugin_dir_url( __FILE__ ); 
	}
    
    //----------------------------------------------
    // EskimoEPOS Settings & Connection
    //----------------------------------------------

    /**
     * Get & Validate API settings
     *
     * @return boolean
     */
    public function init() {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'epos' ); }

        // Get API settings
        $this->domain   = get_option( 'eskimo_epos_domain', '' );
        $this->username = get_option( 'eskimo_epos_username', '' );
        $this->password = get_option( 'eskimo_epos_password', '' );

        if ( $this->debug ) { eskimo_log( 'API SETTINGS domain[' . $this->domain . '] username[' . $this->username . '] password[' . $this->password . ']', 'epos' ); }

        // Validate settings
        if ( empty( $this->domain ) || empty( $this->username ) || empty( $this->password ) ) {
            if ( $this->debug ) { eskimo_log( 'Bad EPOS Settings', 'epos' ); }
            return add_action( 'admin_notices', [ $this, 'settings_error' ] );
        } 

        // Required Woocommerce REST API
        if ( false === woocommerce_rest_api_active() ) {
            if ( $this->debug ) { eskimo_log( 'Bad Woocommerce REST API Setting', 'epos' ); }
            return add_action( 'admin_notices', [ $this, 'rest_api_settings_error' ] );
        }
    }

    /**
     * Authenticate EPOS Access
     *
     * @return  boolean
     */
    protected function authenticate() {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'epos' ); }

        // authenticated?
        $auth = get_transient( 'eskimo_access_authenticated' );
        if ( $this->debug ) { eskimo_log( 'Auth[' . (int) $auth . ']', 'epos' ); }

        // validate token or create
        return ( false === $auth ) ? $this->get_access_token() : true; 
    }

    /**
     * Load current valid EPOS settings
     *
     * @return  array   Current EPOS API settings
     */
    public function get_oauth_params() {
        return [
            'domain'     => $this->domain,
            'username'   => $this->username,
            'password'   => $this->password,
            'grant_type' => 'password'
         ];
    }

    /**
     * Retrieve EPOS access token
     *
     * @return  boolean|object
     */
    protected function get_access_token() {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'epos' ); }

        // Get current settings
        $oauth_data = $this->get_oauth_params();
        if ( $this->debug ) { eskimo_log( 'oAuth[' . print_r( $oauth_data, true ) . ']', 'epos' ); }

        // Set access token
        $access_token_url = $oauth_data['domain'] . 'token';

		// Remote request for access token & expiry
		$response = wp_remote_post( $access_token_url, [ 'body' => $oauth_data ] );

		// Check the call worked
		if ( is_wp_error( $response ) ) {
            return $this->api_error( $response );
		}

		// Get the response body
		$body = wp_remote_retrieve_body( $response );

		// Check contents and parse
		if ( empty( $body ) ) {
            return $this->api_error( 'Empty Access Token' );
		}			

		// Set up token and details
		$access_response = json_decode( $body );

		// Retrieve expiry time in secs
		$api_expiry = absint( $access_response->expires_in );
		$api_token 	= trim( $access_response->access_token );

        // Set token data. Default to standard PHP.ini session timeout value
        $api_timeout = absint( ini_get('session.gc_maxlifetime') );
        $api_timeout = ( $api_timeout > 0 ) ? $api_timeout : 1440;

		// Last override with api data
		$api_timeout = ( $api_expiry > 0 ) ? $api_expiry : $api_timeout;
		
        if ( $this->debug ) { 
            eskimo_log( 'API Timeout[' . $api_timeout . '] API Token[' . $api_token . ']', 'epos' );
        }

        // Set API transients
        set_transient( 'eskimo_access_token', $api_token, $api_timeout );
        set_transient( 'eskimo_access_authenticated', true, $api_timeout );

        // OK, done
        return true;
    }
    
    /**
     * Create EPOS Connection requirements
     *
     * @return  boolean
     */
    public function connect() {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'epos' ); }
        return $this->authenticate();
    }

    //----------------------------------------------
    // Error Handling
    //----------------------------------------------

    /**
     * Curl API error
     *
	 * @param   object  cUrl instance
	 * @return	boolean
	 */ 
	public function api_error( $res ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'epos' ); }

        // Log if debugging is active
        if ( $this->debug ) {
			eskimo_log( 'cUrl Message[' . $res->get_error_message() . ']', 'epos' );
		}
		
        // OK, done
        return false;
	}

    /**
     * Bad EPOS Settings 
     */
    public function settings_error() {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'epos' ); }
        $error_msg = __( 'Eskimo EPOS Settings are required. Please update.', 'eskimo' );
        echo sprintf( '<div class="notice notice-warning"><p>%s</p></div>', $error_msg );
    }

    /**
     * Bad EPOS API Connection 
     */
    public function connect_error() {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'epos' ); }
        $error_msg = __( 'Error Creating Eskimo EPOS Connection', 'eskimo' );
        echo sprintf( '<div class="notice notice-error"><p>%s</p></div>', $error_msg );
    }

    /**
     * Bad REST API Settings 
     */
    public function rest_api_settings_error() {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'epos' ); }
        $error_msg = __( 'Woocommerce REST API is required. Please activate in Woocommerce settings.', 'eskimo' );
        echo sprintf( '<div class="notice notice-warning"><p>%s</p></div>', $error_msg );
    }
}
