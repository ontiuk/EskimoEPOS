<?php

/**
 * Get the Eskimo EPOS settings and create a connection to the remote API
 *
 * @link       https://on.tinternet.co.uk
 * @package    Eskimo
 * @subpackage Eskimo/admin
 */

// Curl class namespace
use \Curl\Curl;

/**
 * Eskimo EPOS API creation 
 *
 * Gets the Eskimo API settings and creates the remote API connection
 *
 * @package    Eskimo
 * @subpackage Eskimo/admin
 * @author     Stephen Betley <on@tinternet.co.uk>
 */
class Eskimo_EPOS {

	/**
	 * The ID of this plugin
	 *
	 * @var     string    $eskimo    The ID of this plugin
	 */
	private $eskimo;

	/**
	 * The version of this plugin
	 *
	 * @var     string    $version    The current version of this plugin
	 */
	private $version;

    /**
	 * Is the plugin in debug mode 
	 *
	 * @var     bool    $debug    Plugin is in debug mode
	 */
	private $debug;

	/**
	 * Is the plugin base directory 
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
	 * @param   string    $version    The version of this plugin
	 * @param   string    $version    Plugin debugging mode, default false
	 */
	public function __construct( $eskimo, $version, $debug = false ) {
        if ( $debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

		$this->eskimo       = $eskimo;
		$this->version      = $version;
		$this->debug        = $debug;
    	$this->base_dir		= plugin_dir_url( __FILE__ ); 
	}
    
    //----------------------------------------------
    // Eskimo EPOS Settings & Connection
    //----------------------------------------------

    /**
     * Get & Validate API settings
     *
     * @return boolean
     */
    public function init() {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Get API settings
        $this->domain   = get_option( 'eskimo_epos_domain', '' );
        $this->username = get_option( 'eskimo_epos_username', '' );
        $this->password = get_option( 'eskimo_epos_password', '' );

        if ( $this->debug ) { error_log( 'API SETTINGS domain[' . $this->domain . '] username[' . $this->username . '] password[' . $this->password . ']' ); }

        // Validate settings
        if ( empty( $this->domain ) || empty( $this->username ) || empty( $this->password ) ) {
            if ( $this->debug ) { error_log( 'Bad EPOS Settings' ); }
            return add_action( 'admin_notices', [ $this, 'settings_error' ] );
        } 

        // Required Woocommerce REST API
        if ( false === woocommerce_rest_api_active() ) {
            if ( $this->debug ) { error_log( 'Bad Woocommerce REST API Setting' ); }
            return add_action( 'admin_notices', [ $this, 'rest_api_settings_error' ] );
        }
    }

    /**
     * Authenticate EPOS Access
     *
     * @return  boolean
     */
    protected function authenticate() {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // authenticated?
        $auth = get_transient( 'eskimo_access_authenticated' );
        if ( $this->debug ) { error_log( 'Auth[' . (int) $auth . ']' ); }

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
     * @return  boolean
     */
    protected function get_access_token() {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Get current settings
        $oauth_data = $this->get_oauth_params();
        if ( $this->debug ) { error_log( 'oAuth[' . print_r( $oauth_data, true ) . ']' ); }

        // Set access token
        $access_token_url = $oauth_data['domain'] . 'token';

        // Set up remote connection
        $curl = new Curl();
        $curl->post( $access_token_url, $oauth_data );

        // Bad response?
        if ( $curl->error ) {
            return $this->api_error( $curl );
        } 

        // Set token data. Default to standard PHP.ini session timeout value
        $api_timeout = absint( ini_get('session.gc_maxlifetime') );
        $api_timeout = ( $api_timeout > 0 ) ? $api_timeout : 1440;

        if ( $this->debug ) { 
            error_log( 'API Timeout[' . $api_timeout . ']' );
            error_log( 'API Token[' . $curl->response->access_token . ']' );
        }

        // Set WordPress transients        
        set_transient( 'eskimo_access_token', $curl->response->access_token, $api_timeout );
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
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }
        return $this->authenticate();
    }

    //----------------------------------------------
    // Error Handling
    //----------------------------------------------

    /**
     * Curl API error
     *
     * @param   object  cUrl instance
     */ 
    public function api_error( $curl ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Log if debugging is active
        if ( $this->debug ) {
            $request_headers    = ( isset( $curl->request_headers ) ) ? $curl->request_headers : '';
            $response_headers   = ( isset( $curl->response_headers ) ) ? $curl->response_headers : '';
            if ( $this->debug ) {
                error_log( 'cUrl Headers[' . print_r( $request_headers, true ) . ']' );
                error_log( 'cUrl Response[' . print_r( $response_headers, true ) . ']' );        
                error_log( 'cUrl Message[' . $curl->Message . ']' );
            }

            if ( isset( $curl->ExceptionMessage ) ) {
                if ( $this->debug ) { error_log( 'cUrl Exception[' . $curl->ExceptionMessage . ']' ); }
            }
        }
        
        // OK, done
        return false;
    }

    /**
     * Bad EPOS Settings 
     */
    public function settings_error() {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }
        $error_msg = __( 'Eskimo EPOS Settings are required. Please update.', 'eskimo' );
        echo sprintf( '<div class="notice notice-warning"><p>%s</p></div>', $error_msg );
    }

    /**
     * Bad EPOS API Connection 
     */
    public function connect_error() {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }
        $error_msg = __( 'Error Creating Eskimo EPOS Connection', 'eskimo' );
        echo sprintf( '<div class="notice notice-error"><p>%s</p></div>', $error_msg );
    }

    /**
     * Bad REST API Settings 
     */
    public function rest_api_settings_error() {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }
        $error_msg = __( 'Woocommerce REST API is required. Please activate in Woocommerce settings.', 'eskimo' );
        echo sprintf( '<div class="notice notice-warning"><p>%s</p></div>', $error_msg );
    }
}
