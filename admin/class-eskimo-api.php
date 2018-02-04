<?php

/**
 * Connect to the Eskimo EPOS and get/add json data via api calls
 *
 * @link       https://on.tinternet.co.uk
 * @package    Eskimo
 * @subpackage Eskimo/admin
 */

// Curl class namespace
use \Curl\Curl;

/**
 * Get/Set API JSON data
 *
 * Connects to the Eskimo EPOS via cUrl & retrieves JSON data from API
 *
 * @package    Eskimo
 * @subpackage Eskimo/admin
 * @author     Stephen Betley <on@tinternet.co.uk>
 */
class Eskimo_API {
    
	/**
	 * The ID of this plugin.
	 *
	 * @var     string    $eskimo    The ID of this plugin
	 */
	private $eskimo;

	/**
	 * The version of this plugin.
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
	 * @var      string    $base_dir  string path for the plugin directory
	 */
    private $base_dir;

	/**
	 * Eskimo EPOS API handler
	 *
	 * @var     object    $eskimo    Eskimo_EPOS instance
	 */
	private $api;

	/**
	 * Initialize the class and set its properties
     *
     * @param   object  $api        Eskimo_EPOS instance
	 * @param   string  $eskimo     The name of this plugin
	 * @param   string  $version    The version of this plugin
	 * @param   boolean $debug      Plugin debugging mode, default false
	 */
	public function __construct( Eskimo_EPOS $api, $eskimo, $version, $debug = false ) {
        if ( $debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        $this->api      = $api; 
		$this->eskimo   = $eskimo;
		$this->version  = $version;
		$this->debug    = $debug;
    	$this->base_dir	= plugin_dir_url( __FILE__ ); 
    }

    /**
     * Create API connection TEMP
     */
    public function init() {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }
        return $this->api->connect();
    }

    //----------------------------------------------
    // Eskimo API Data
    //----------------------------------------------

    /**
     * Retrieve data from remote API
     *
     * @param   string  $api_url
     * @param   array|boolean   $api_opts Default false   
     * @return  object|array
     */
    protected function get_data( $api_url, $api_opts = false ) {

        // Get access token & test for timeout
        $auth = get_transient( 'eskimo_access_authenticated' );
        $access_token = get_transient( 'eskimo_access_token' );
        if ( false === $auth ) { return false; }

        // Initialise response
        $curl = new Curl();
        $curl->setHeader( 'Authorization', 'Bearer ' . $access_token );
        $curl->get( $api_url, $api_opts );
        
        return ( $curl->error ) ? false : $curl->response;    
    }
    
    /**
     * Send & Retrieve via remote API
     *
     * @param   string  $api_url
     * @param   array|boolean   $api_opts Default false   
     * @return  object|array
     */    
    protected function post_data( $api_url, $api_opts = false ) {

        // Get access token & test for timeout
        $auth = get_transient( 'eskimo_access_authenticated' );
        $access_token = get_transient( 'eskimo_access_token' );
        if ( false === $auth ) { return false; }

        // Initialise response
        $curl = new Curl();
    	$curl->setHeader( 'Content-Type', 'application/json' );
        $curl->setHeader( 'Authorization', 'Bearer ' . $access_token );
        $curl->post( $api_url, $api_opts );
        
        return ( $curl->error ) ? $this->api_error( $curl->response ) : $curl->response;    
    }

    //--------------------------------------------------
    //  Eskimo API Functions: Category ImpEX
    //--------------------------------------------------

    /**
     * Retreive remote EPOS category list
     *
     * @return  array|boolean
     */
   	public function categories_all() {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Test file
//        $url = plugin_dir_url( __FILE__ ) . 'assets/epos/categories_all.json';
//        $file = file_get_contents( $url );
//        return json_decode( $file );

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get api response
        $api_url    = $oauth['domain'] . 'api/Categories/All';
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : $api_data;
    }

    /**
     * Get a category by EPOS category id
     *
     * @param   string   $id 
     * @return  array|boolean
     */
    public function categories_specific_ID( $id ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Test file
//        $url = plugin_dir_url( __FILE__ ) . 'assets/epos/categories_id.json';
//        $file = file_get_contents( $url );
//        return json_decode( $file );

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/Categories/SpecificID/' . $id;	
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : $api_data;
    }
    
    /**
     * Get categories by EPOS parent id
     *
     * @param   string   $id 
     * @return  array|boolean
     */    
    public function categories_child_categories_ID( $id ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Test file
//        $url = plugin_dir_url( __FILE__ ) . 'assets/epos/categories_child.json';
//        $file = file_get_contents( $url );
//        return json_decode( $file );

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/Categories/ChildCategories/' . $id;	
    	$api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : $api_data;
    }
   	
    /**
     * Update remote EPOS category webID field
     *
     * @param   array   $api_opts 
     * @return  array|boolean
     */       	
   	public function categories_update_cart_ID( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/Categories/UpdateCartIDs';
    	$api_opts   = json_encode( $api_opts );
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : $api_data;
    }

    //--------------------------------------------------
    //  Eskimo API Functions: Category Product ImpEX
    //--------------------------------------------------

    /**
     * Get product list orderded by category
     * - Deprecated: Use products_all or products_range
     *
     * @param   array   $api_opts 
     * @return  array|boolean
     */
   	public function category_products_all( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Test file
//        $url = plugin_dir_url( __FILE__ ) . 'assets/epos/category_products_all.json';
//        $file = file_get_contents( $url );
//        return json_decode( $file );

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/CategoryProducts/All';	
    	$api_opts   = json_encode( $api_opts ); 
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : $api_data;
    }
    
    /**
     * Get product list by specific EPOS category ID
     *
     * @param   string   $id 
     * @return  array|boolean
     */
    public function category_products_specific_category( $id ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/CategoryProducts/SpecificCategory/' . $id;	
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : $api_data;
    }
    
    /**
     * Get product list
     *
     * @param   array   $api_opts 
     * @return  array|boolean
     */
   	public function products_all( $api_opts ) {

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/Products/All';	
    	$api_opts   = json_encode( $api_opts ); 
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : $api_data;
    }

    /**
     * Get product list by EPOS product ID
     *
     * @param   integer   $id 
     * @return  array|boolean
     */
    public function products_specific_ID( $id ) {
        error_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . ']' ); 

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/Products/SpecificID/' . $id;	
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : $api_data;
    }
    
    /**
     * Update remote EPOS product webID
     *
     * @param   array   $api_opts 
     * @return  array|boolean
     */
    public function products_update_cart_ID( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/Products/UpdateCartIDs';
    	$api_opts   = json_encode( $api_opts );
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : $api_data;
    }

    /**
     * Get EPOS Tax Codes optionally by ID
     *
     * @param   boolean $id
     * @return  array|boolean
     */
    public function tax_codes( $id = false ) {

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = ( false !== $id ) ? $oauth['domain'] . 'api/TaxCodes/SpecificID/' . $id : $oauth['domain'] . 'api/TaxCodes/All';
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : $api_data;
    }

    /**
     * Get EPOS SKUs
     *
     * @return  array|boolean
     */
   	public function sku_all() {

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/SKUs/All';
        $api_data   = $this->post_data( $api_url);

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : $api_data;
    }

    /**
     * Get EPOS SKUs by specific EPOS product ID
     *
     * @param   string  $id 
     * @return  array|boolean
     */
   	public function sku_specific_ID( $id ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . 'ID[' . $id . ']' ); }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/SKUs/SpecificIdentifier/' . $id;
    	$api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : $api_data;
    }

    /**
     * Get EPOS SKU by specific sku code
     *
     * @param   string  $id
     * @return  array|boolean
     */
   	public function sku_specific_code( $id ) {

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/SKUs/SpecificSKUCode/' . $id;
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : $api_data;
    }

    /**
     * Get EPOS product image links
     *
     * @param   array $api_opts
     * @return  array|boolean
     */
    public function image_links_all( $api_opts ) {

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/ImageLinks/All';	
    	$api_opts   = json_encode( $api_opts ); 
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : $api_data;
    }

    /**
     * Get EPOS product images
     *
     * @param   array $api_opts
     * @return  array|boolean
     */
    public function images_all( $api_opts ) {

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/Images/All';	
    	$api_opts   = json_encode($api_opts); 
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : $api_data;
    }

    /**
     * Get all current EPOS shops
     *
     * @return  array|boolean
     */
   	public function shops_all() {

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/Shops/All';
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : $api_data;
    }

    /**
     * Get an EPOS shop by specific ID
     *
     * @param   string  $id
     * @return  array|boolean
     */
    public function shops_specific_ID( $id ) {

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/Shops/SpecificID/' . $id;	
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : $api_data;
    }

    /**
     * Get EPOS customer data by ID
     *
     * @param   string  $id
     * @return  array|boolean
     */
   	public function customers_specific_ID( $id ) {

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/Customers/SpecificID/' . $id;	
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : $api_data;
    }

    /**
     * Create remote EPOS customer from web data
     *
     * @param   array   $api_opts
     * @return  array|boolean
     */
    public function customers_create( $api_opts ) {

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/Customers/Insert';	
    	$api_opts   = json_encode( $api_opts ); 
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : $api_data;
    }

    /**
     * Update remote EPOS customer by ID or Email
     *
     * @param   array $api_opts
     * @return  array|boolean
     */
    public function customers_update( $api_opts ) {

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/Customers/Update';	
    	$api_opts   = json_encode( $api_opts ); 
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : $api_data;
    }
    
    /**
     * Insert remote EPOS order from web data post paid
     *
     * @param   array $api_opts
     * @return  array|boolean
     */
   	public function orders_insert( $api_opts ) {

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/Orders/Insert';	
    	$api_opts   = json_encode( $api_opts );
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : $api_data;
    }   

    //----------------------------------------------
    // Eskimo API Error Handling
    //----------------------------------------------

    /**
     * Curl API error
     *
     * @param object cUrl instance
     */ 
    public function api_error( $curl ) {

        // Log errors if debugging active
        if ( $this->debug ) {
            $request_headers    = ( isset( $curl->request_headers ) ) ? $curl->request_headers : '';
            $response_headers   = ( isset( $curl->response_headers ) ) ? $curl->response_headers : '';
            if ( $this->debug ) { 
                error_log( 'cUrl Headers[' . print_r( $request_headers, true ) . ']' );
                error_log( 'cUrl Response[' . print_r( $response_headers, true ) . ']' );        
                error_log( 'cUrl Message[' . $curl->Message . ']' );
            }
            if ( isset( $curl->ExceptionMessage ) ) {
                error_log( 'cUrl Exception[' . $curl->ExceptionMessage . ']' );
            }
        }

        return false;
    }
}
