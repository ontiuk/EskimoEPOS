<?php

/**
 * Connect to the Eskimo EPOS and get/add json data via api calls
 * - Implements all available API calls, though not all Woocommerce related or used
 *
 * @link       https://on.tinternet.co.uk
 * @package    Eskimo
 * @subpackage Eskimo/admin
 */

/**
 * Get/Set API JSON data
 *
 * Connects to the Eskimo EPOS via cUrl & retrieves JSON data from API
 *
 * @package    Eskimo
 * @subpackage Eskimo/admin
 * @author     Stephen Betley <on@tinternet.co.uk>
 */
final class Eskimo_API {
    
	/**
	 * The ID of this plugin
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
	 * @var     bool    $debug    Plugin debug mode - defaults to false
	 */
	private $debug;

	/**
	 * Plugin base directory
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
	 */
	public function __construct( Eskimo_EPOS $api, $eskimo ) {

		// Set up class settings
        $this->api      = $api; 
		$this->eskimo   = $eskimo;
   		$this->version  = ESKIMO_VERSION;
		$this->debug    = ESKIMO_API_DEBUG;
    	$this->base_dir	= plugin_dir_url( __FILE__ ); 

		if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }
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
     * Retrieve data from remote API via GET
     *
     * @param   string  		$api_url
     * @return  object|array
     */
    protected function get_data( $api_url ) {

        // Get access token & test for timeout
        $auth 			= get_transient( 'eskimo_access_authenticated' );
        $access_token 	= get_transient( 'eskimo_access_token' );
        if ( false === $auth ) { return false; }

		// GET Request
		$request = wp_remote_get( $api_url,
			[ 
				'timeout' 		=> 60,
				'user-agent'	=> 'Eskimo/1.0',
            	'headers' => [
					'Accept' 		=> 'application/json',
					'Authorization'	=> 'Bearer ' . $access_token
				]
			]
		);

		// Check WP Error
		if ( $this->debug && is_wp_error( $request ) ) {
			error_log( 'WP cUrl Error [' . $request->get_error_code() . ' : ' . $request->get_error_message() . ']' );
		}

		// Return request response or false if error
		return ( is_wp_error( $request ) ) ? false : wp_remote_retrieve_body( $request ); 
	}

    /**
	 * Retrieve media from remote API
	 *
     * @param   string  		$api_url
     * @param   array|boolean   $api_opts default false   
     * @return  object|array
     */
    protected function get_media( $api_url, $api_opts = false ) {

        // Get access token & test for timeout
        $auth 			= get_transient( 'eskimo_access_authenticated' );
        $access_token 	= get_transient( 'eskimo_access_token' );
        if ( false === $auth ) { return false; }

		// GET Request
		$request = wp_remote_get( $api_url,
			[ 
				'timeout' 		=> 12,
				'user-agent'	=> 'Eskimo/1.0',
            	'headers' => [
					'Accept' 		=> 'application/jpg, application/png',
					'Authorization'	=> 'Bearer ' . $access_token
				]
			]
		);

		// Check WP Error
		if ( $this->debug && is_wp_error( $request ) ) {
			error_log( 'cUrl Error [' . $request->get_error_code() . ' : ' . $request->get_error_message() . ']' );
		}

		// Return request response or false if error
		return ( is_wp_error( $request ) ) ? false : wp_remote_retrieve_body( $request ); 
    }

    /**
     * Send & Retrieve via remote API via POST
     *
     * @param   string  		$api_url
     * @param   array|boolean   $api_opts default false   
     * @return  object|array
	 */    
    protected function post_data( $api_url, $api_opts = false ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ' URL[' . $api_url . ']' ); }

        // Get access token & test for timeout
        $auth 			= get_transient( 'eskimo_access_authenticated' );
        $access_token 	= get_transient( 'eskimo_access_token' );
        if ( false === $auth ) { return false; }

		// GET Request
		$request = wp_remote_post( $api_url,
			[ 
				'timeout' 		=> 60,
				'method'		=> 'POST',
				'user-agent'	=> 'Eskimo/1.0',
            	'headers' => [
					'Content-Type' 	=> 'application/json',
					'Accept' 		=> 'application/json',
					'Authorization'	=> 'Bearer ' . $access_token
				],
				'body'	=> $api_opts
			]
		);

		// Check WP Error
		if ( $this->debug && is_wp_error( $request ) ) {
			error_log( 'cUrl Error [' . $request->get_error_code() . ' : ' . $request->get_error_message() . ']' );
		}

		// Return request response or false if error
		return ( is_wp_error( $request ) ) ? false : wp_remote_retrieve_body( $request ); 
	}
	
    /**
     * Send & Retrieve via remote API via POST
     *
     * @param   string  		$api_url
     * @param   array|boolean   $api_opts default false   
     * @return  object|array
	 */    
    protected function post_raw( $api_url, $api_opts = false ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ' URL[' . $api_url . ']' ); }

        // Get access token & test for timeout
        $auth 			= get_transient( 'eskimo_access_authenticated' );
        $access_token 	= get_transient( 'eskimo_access_token' );
        if ( false === $auth ) { return false; }

		// GET Request
		$request = wp_remote_post( $api_url,
			[ 
				'timeout' 		=> 60,
				'method'		=> 'POST',
				'user-agent'	=> 'Eskimo/1.0',
            	'headers' => [
					'Content-Type' 	=> 'application/json',
					'Accept' 		=> 'application/json',
					'Authorization'	=> 'Bearer ' . $access_token
				],
				'body'	=> $api_opts
			]
		);

		// Check WP Error
		if ( $this->debug && is_wp_error( $request ) ) {
			error_log( 'cUrl Error [' . $request->get_error_code() . ' : ' . $request->get_error_message() . ']' );
		}

		// Return request response or false if error
		return ( is_wp_error( $request ) ) ? false : $request; 
	}

    /**
     * Send & Retrieve via remote API via POST
     *
     * @param   string  		$api_url
     * @param   array|boolean   $api_opts default false   
     * @return  object|array
	 */    
    protected function post_status( $api_url, $api_opts = false ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ' URL[' . $api_url . ']' ); }

        // Get access token & test for timeout
        $auth 			= get_transient( 'eskimo_access_authenticated' );
        $access_token 	= get_transient( 'eskimo_access_token' );
        if ( false === $auth ) { return false; }

		// GET Request
		$request = wp_remote_post( $api_url,
			[ 
				'timeout' 		=> 10,
				'method'		=> 'POST',
            	'headers' => [
					'Content-Type' 	=> 'application/json',
					'Accept' 		=> 'application/json',
					'Authorization'	=> 'Bearer ' . $access_token
				],
				'body'	=> $api_opts
			]
		);

		// Check Error
		if ( $this->debug && is_wp_error( $request ) ) {
			error_log( 'cUrl Error [' . $request->get_error_code() . ' : ' . $request->get_error_message() . ']' );
		}

		// Return request response or false if error
		return ( is_wp_error( $request ) ) ? false : wp_remote_retrieve_response_code( $request ); 
	}

    //--------------------------------------------------
    //  Eskimo API Functions: Addresses REST ImpEx
    //--------------------------------------------------
	
    /**
	 * Insert remote EskimoEPOS Address information
	 * - POST api/Addresses/Insert/{CustomerID}/{AddressRef}
	 * - Requires customer ID and Address Reference
     *
     * @param   string   		$cust_id 
     * @param   string   		$addr_ref 
     * @param   array   		$api_opts 
     * @return  array|boolean
     */       	
   	public function addresses_insert( $cust_id, $addr_ref, $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ . ' ' . $cust_id . ' ' . $addr_ref ); }

		// Basic parameter check
		if ( empty( $cust_id ) || empty( $addr_ref ) || empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/Addresses/Insert/' . $cust_id . '/' . $addr_ref;
    	$api_opts   = json_encode( $api_opts );
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );	
	}

    /**
	 * Update remote EskimoEPOS Address information
	 * - POST api/Addresses/Update/{CustomerID}/{AddressRef}
	 * - Requires customer ID and Address Reference
     *
     * @param   string   		$cust_id 
     * @param   string   		$addr_ref 
     * @param   array   		$api_opts 
     * @return  array|boolean
     */       	
   	public function addresses_update( $cust_id, $addr_ref, $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ . ' ' . $cust_id . ' ' . $addr_ref ); }

		// Basic parameter check
		if ( empty( $cust_id ) || empty( $addr_ref ) || empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/Addresses/Update/' . $cust_id . '/' . $addr_ref;
    	$api_opts   = json_encode( $api_opts );
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );	
	}

    /**
	 * Search remote EskimoEPOS Address information
	 * - POST api/Addresses/Search
	 * 
     * @param   array   		$api_opts 
     * @return  array|boolean
     */       	
   	public function addresses_search( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/Addresses/Search';
    	$api_opts   = json_encode( $api_opts );
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );	
	}

    /**
	 * Return remote EskimoEPOS Address description based on postcode
	 * - POST api/Addresses/AddressValidationSummary
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */       	
   	public function addresses_validation_summary( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/Addresses/AddressValidationSummary';
    	$api_opts   = json_encode( $api_opts );
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );	
	}

   /**
	* Return remote EskimoEPOS Address description based on postcode
	* - GET api/Addresses/AddressValidationDetailed/{id}
    *
    * @param   string   		$id 
    * @return  array|boolean
    */
   	public function addresses_validation_detailed( $id ) {
		if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ . ' : ID [' . $id . ']' ); }

		// Basic parameter check
		if ( empty( $id ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/Addresses/AddressValidationDetailed/' . $id;
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
	}

    //--------------------------------------------------
    //  Eskimo API Functions: Amazon REST ImpEx
    //--------------------------------------------------

    /**
	 * Download remote EskimoEPOS Amazon Orders not yet in Eskimo system
	 * - POST api/Amazon/DownloadOrders
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */       	
   	public function amazon_download_orders( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/Amazon/DownloadOrders';
    	$api_opts   = json_encode( $api_opts );
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

    /**
	 * Update remote EPOS stock levels of all FBA products
	 * GET api/Amazon/DownloadFBAInventory
     *
     * @return  array|boolean
     */
   	public function amazon_download_FBA_inventory() {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get api response
        $api_url    = $oauth['domain'] . 'api/Amazon/DownloadFBAInventory';
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

    /**
	 * Retreives order info from Amazon
	 * - GET api/Amazon/GetOrderInfo/{id}
     *
	 * @param	string			$id
     * @return  array|boolean
     */
   	public function amazon_get_order_info( $id ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ . ' ID: ' . $id ); }

		// Basic parameter check
		if ( empty( $id ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get api response
        $api_url    = $oauth['domain'] . 'api/Amazon/GetOrderInfo/' . $id;
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }
		
    /**
	 * Remote EPOS Amazon Product Info
	 * - POST api/Amazon/UploadProductInfo
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
   	public function amazon_upload_product_info( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get api response
        $api_url    = $oauth['domain'] . 'api/Amazon/UploadProductInfo';
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

    //--------------------------------------------------
    //  Eskimo API Functions: Barcode REST ImpEx
    //--------------------------------------------------

    /**
	 * Retrieve remote EskimoEPOS barcode information
	 * - POST api/Barcode/GetInfo
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */       	
   	public function barcode_get_info( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/Barcode/GetInfo';
    	$api_opts   = json_encode( $api_opts );
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

    /**
	 * Apply remote EskimoEPOS note of an action for a particular barcode 
	 * - POST api/Barcode/ApplyAction
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */       	
   	public function barcode_apply_action( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/Barcode/ApplyAction';
    	$api_opts   = json_encode( $api_opts );
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

    //--------------------------------------------------
    //  Eskimo API Functions: Category REST ImpEx
    //--------------------------------------------------

    /**
	 * Retreive remote EPOS category list
	 * - GET api/Categories/All
     *
     * @return  array|boolean
     */
   	public function categories_all() {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get api response
        $api_url    = $oauth['domain'] . 'api/Categories/All';
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

    /**
	 * Get a category by EPOS category ID
	 * - GET api/Categories/SpecificID/{id}
     *
     * @param   string   		$id 
     * @return  array|boolean
     */
    public function categories_specific_ID( $id ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ . ' : ID [' . $id . ']' ); }

		// Basic parameter check
		if ( empty( $id ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/Categories/SpecificID/' . $id;	
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }
    
    /**
	 * Get categories by EPOS parent category ID
	 * - GET api/Categories/ChildCategories/{id}
     *
     * @param   string   		$id 
     * @return  array|boolean
     */    
    public function categories_child_categories_ID( $id ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ . ' : ID [' . $id . ']' ); }

		// Basic parameter check
		if ( empty( $id ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/Categories/ChildCategories/' . $id;	
    	$api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }
   	
    /**
	 * Update remote EPOS category webID field
	 * - POST api/Categories/UpdateCartIDs
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */       	
   	public function categories_update_cart_ID( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/Categories/UpdateCartIDs';
    	$api_opts   = json_encode( $api_opts );
        $api_data   = $this->post_status( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : $api_data;
    }

    //------------------------------------------------------
    //  Eskimo API Functions: Category Product REST ImpEx
    //------------------------------------------------------

    /**
	 * Get product list orderded by category
	 * - POST api/CategoryProducts/All
     * - Deprecated: Use products_all or products_range
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
   	public function category_products_all( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/CategoryProducts/All';	
    	$api_opts   = json_encode( $api_opts ); 
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }
    
    /**
	 * Get product list by specific EPOS category ID
	 * - GET api/CategoryProducts/SpecificCategory/{id}
     * - Deprecated: Use products_specific_id
     *
     * @param   string   		$id 
     * @return  array|boolean
     */
    public function category_products_specific_category( $id ) {
		if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ . ' : ID [' . $id . ']' ); }

		// Basic parameter check
		if ( empty( $id ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/CategoryProducts/SpecificCategory/' . $id;	
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

    //--------------------------------------------------
    //  Eskimo API Functions: Countries REST ImpEx
    //--------------------------------------------------

    /**
     * Retrieve a list of countries
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
   	public function countries_search( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/Countries/Search';	
    	$api_opts   = json_encode( $api_opts ); 
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

    //--------------------------------------------------
    //  Eskimo API Functions: Customers REST ImpEx
    //--------------------------------------------------

    /**
	 * Get specific EPOS customer data by ID
	 * - GET api/Customers/SpecificID/{ID}
     *
     * @param   string  		$id
     * @return  array|boolean
     */
   	public function customers_specific_ID( $id ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $id ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/Customers/SpecificID/' . $id;	
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
	}

    /**
	 * Get EPOS customer titles
	 * - GET api/Customers/Titles
     *
     * @return  array|boolean
     */
   	public function customers_titles() {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/Customers/Titles';	
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

    /**
	 * Get EPOS customer data by matching records
	 * - POST api/Customers/Search
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
   	public function customers_search( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/Customers/Search';	
    	$api_opts   = json_encode( $api_opts ); 
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
	}

	/**
	 * Get EPOS customer data for export
	 * - POST api/Customers/CustomersDump	
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
   	public function customers_customers_dump( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/Customers/CustomersDump';	
    	$api_opts   = json_encode( $api_opts ); 
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

    /**
	 * Create remote EPOS customer from web data
	 * - POST api/Customers/Insert	
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function customers_insert( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/Customers/Insert';	
    	$api_opts   = json_encode( $api_opts ); 
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

    /**
	 * Update remote EPOS customer by ID or Email
	 * - POST api/Customers/Update
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function customers_update( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/Customers/Update';	
    	$api_opts   = json_encode( $api_opts ); 
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
	}

    //--------------------------------------------------
    //  Eskimo API Functions: External Categories ImpEX
    //--------------------------------------------------

    /**
	 * Insert/Update remote EPOS external category and Item Specific information
	 *  - POST api/ExternalCategories/Insert
	 *  - Used with category information from eBay or Amazon
	 *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function external_categories_insert( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/ExternalCategories/Insert';	
    	$api_opts   = json_encode( $api_opts ); 
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

	/**
	 * Retrieves a remote EskimoEPOS singular external category class for a specific ID and Type
	 * - POST api/ExternalCategories/SpecificID
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function external_categories_specific_ID( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/ExternalCategories/SpecificID';	
    	$api_opts   = json_encode( $api_opts ); 
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
	}

    //--------------------------------------------------
    //  Eskimo API Functions: Gift Cards REST ImpEx
    //--------------------------------------------------

    /**
	 * Retrieve EskimoEPOS gift card balance
	 * - POST api/GiftCards/BalanceEnquiry
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function gift_cards_balance_enquiry( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/GiftCards/BalanceEnquiry';	
    	$api_opts   = json_encode( $api_opts ); 
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

    /**
	 * Remote EskimoEPOS gift card update
	 * - POST api/GiftCards/Increment
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function gift_cards_balance_increment( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/GiftCards/Increment';	
    	$api_opts   = json_encode( $api_opts ); 
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

    /**
	 * Remote EskimoEPOS gift card redeem
	 * - POST api/GiftCards/Redeem
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function gift_cards_balance_redeem( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/GiftCards/Redeem';	
    	$api_opts   = json_encode( $api_opts ); 
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

    //--------------------------------------------------
    //  Eskimo API Functions: Image Links REST ImpEx
    //--------------------------------------------------

    /**
	 * Retrieve EskimoEPOS product image links by batch
	 * - POST api/ImageLinks/All
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function image_links_all( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/ImageLinks/All';	
    	$api_opts   = json_encode( $api_opts ); 
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

	/**
	 * Updates EskimoEPOS Image Link Cart IDs
	 * - POST api/ImageLinks/UpdateCartIDs
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function image_links_update_cart_ID( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/ImageLinks/UpdateCartIDs';	
    	$api_opts   = json_encode( $api_opts ); 
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

    //--------------------------------------------------
    //  Eskimo API Functions: Images REST ImpEx
    //--------------------------------------------------

    /**
	 * Retrieve remote EskimoEPOS product images objects (max 10,000)
	 * - POST api/Images/All
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function images_all( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/Images/All';	
    	$api_opts   = json_encode($api_opts); 
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

	/**
	 * Updates EskimoEPOS Images Cart IDs
	 * - POST api/Images/UpdateCartIDs
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function images_update_cart_ID( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/Images/UpdateCartIDs';	
    	$api_opts   = json_encode( $api_opts ); 
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

    /**
	 * Retrieve image data for a singular image
	 * - GET api/Images/ImageData/{id}
     *
     * @param   integer   		$id 
     * @return  array|boolean
     */
    public function images_image_data( $id ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ . ' : ID [' . $id . ']' ); }

		// Basic parameter check
		if ( empty( $id ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/Images/ImageData/' . $id;	
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

    /**
	 * Retrieve image data for a singular image as base64
	 * - GET api/Images/AsBase64/{id}	
     *
     * @param   integer   		$id 
     * @return  array|boolean
     */
    public function images_as_base64( $id ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ . ' : ID [' . $id . ']' ); }

		// Basic parameter check
		if ( empty( $id ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/Images/AsBase64/' . $id;	
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

	/** 
	 * Retrieve image data / url from direct link 
	 * - GET api/Images/ImageURL?token={token}&image_id={image_id}
	 *
     * @param   integer   		$image_id 
     * @param   string    		$token 
     * @return  array|boolean
     */
    public function images_image_url( $image_id, $token ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ . ' : Image ID [' . $image_id . ']' ); }

		// Basic parameter check
		if ( empty( $image_id ) || empty( $token ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
		$api_url 	= $oauth['domain'] . 'api/Images/ImageURL';	
		$api_url 	= add_query_arg( [ 'token' => $token, 'image_id' => $image_id ], $api_url );
		$api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
	}

    //--------------------------------------------------
    //  Eskimo API Functions: Listings ImpEX
    //--------------------------------------------------
	
	/**
	 * Retrieves a list of all items that have not yet been listed externally
	 * - POST api/Listings/AllUnlisted
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function listings_all_unlisted( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/Listings/AllUnlisted';	
    	$api_opts   = json_encode( $api_opts ); 
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

	/** 
	 * Retrieve image data / url from direct link 
	 * - GET api/Listings/ListingImage?strDataKey={strDataKey}
	 *
     * @param   string   		$key 
     * @return  array|boolean
     */
    public function listings_listing_image( $key ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ . ' : Key [' . $key . ']' ); }

		// Basic parameter check
		if ( empty( $key ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
		$api_url 	= $oauth['domain'] . 'api/Listings/ListingImage';	
		$api_url 	= add_query_arg( [ 'strDataKey' => $key ], $api_url );
		$api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
	}

	/**
	 * Marks a listing a 'listed' 
	 * - POST api/Listings/MarkAsListed
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function listings_mark_as_listed( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/Listings/MarkAsListed';	
    	$api_opts   = json_encode( $api_opts ); 
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

    //--------------------------------------------------
    //  Eskimo API Functions: MeasureUnits REST ImpEx
    //--------------------------------------------------

    /**
	 * Retrieves a list of all the measurement units setup in Eskimo
	 * - GET api/MeasureUnits/All	
     *
     * @return  array|boolean
     */
    public function measure_units() {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/MeasureUnits/All';	
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

    //--------------------------------------------------
    //  Eskimo API Functions: Operators ImpEx
    //--------------------------------------------------

    /**
	 * retrieve remote EskimoEPOS list of Operators (or Clerks)
	 * - POST api/Operators/Search
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
   	public function operators_search( $api_opts ) {
		if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/Operators/Search';
    	$api_opts   = json_encode( $api_opts ); 
		$api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
	}

    //--------------------------------------------------
    //  Eskimo API Functions: Orders ImpEX
    //--------------------------------------------------

    /**
	 * Insert remote EskimoEPOS order from web sale
	 * - POST api/Orders/Insert
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
   	public function orders_insert( $api_opts ) {
		if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }
   
        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/Orders/Insert';
    	$api_opts   = json_encode( $api_opts ); 
		$api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
	}

    /**
	 * Retrieves a list of status codes that an order can be in
	 * - GET api/Orders/StatusCodes
     *
     * @return  array|boolean
     */
    public function orders_status_codes() {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/Orders/StatusCodes';	
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

    /**
	 * Retrieves a singular order class for a specific customer Order ID
	 * - Deprecated: No longer part of the EskimoEPOS API
     *
     * @param   integer   		$order_id 
     * @return  array|boolean
     */
    public function orders_customer_sale( $order_id ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ . ': Order ID[' . $order_id . ']' ); }

		// Basic parameter check
		if ( empty( $order_id ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/Orders/CustomerSale/' . $order_id;	
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

    /**
     * Retrieves a singular order class for a specific mail order Order ID
	 * - Deprecated: No longer part of the EskimoEPOS API
     *
     * @param   integer   		$order_id 
     * @return  array|boolean
     */
    public function orders_mail_order( $order_id ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ . ': Order ID[' . $order_id . ']' ); }

		// Basic parameter check
		if ( empty( $order_id ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/Orders/MailOrder/' . $order_id;	
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

    /**
     * Retrieves a singular order class for a specific customer Order ID
	 * - Deprecated: No longer part of the EskimoEPOS API
     *
     * @param   integer   		$order_id 
     * @return  array|boolean
     */
    public function orders_customer_order( $order_id ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ . ': Order ID[' . $order_id . ']' ); }

		// Basic parameter check
		if ( empty( $order_id ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/Orders/CustomerOrder/' . $order_id;	
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }
	
    /**
	 * Retrieves a singular order class for a specific Web Order ID
	 * - GET api/Orders/WebsiteOrder/{ID}
     *
     * @param   integer   		$order_id 
     * @return  array|boolean
     */
    public function orders_website_order( $order_id ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ . ': Order ID[' . $order_id . ']' ); }

		// Basic parameter check
		if ( empty( $order_id ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/Orders/WebsiteOrder/' . $order_id;
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

    /**
     * Retrieves a singular order for a specific eBay Order ID
	 * - Deprecated: No longer part of the EskimoEPOS API
     *
     * @param   integer   		$order_id 
     * @return  array|boolean
     */
    public function orders_ebay_order( $order_id ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ . ': Order ID[' . $order_id . ']' ); }

		// Basic parameter check
		if ( empty( $order_id ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/Orders/eBayOrder/' . $order_id;	
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
	}
	
    /**
	 * Retrieves Order Fulfiment Methods
	 * - GET api/Orders/FulfilmentMethods
     *
     * @return  array|boolean
     */
    public function orders_fulfilment_methods() {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__  ); }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/Orders/FulfilmentMethods';	
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
	}

    /**
	 * EskimoEPOS order search by criteria
	 * - POST api/Orders/Search
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
   	public function orders_search( $api_opts ) {
		if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/Orders/Search';
    	$api_opts   = json_encode( $api_opts ); 
		$api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
	}

   /**
     * Retrieves a singular order for a specific Amazon Order ID
	 * - Deprecated: No longer part of the EskimoEPOS API
     *
     * @param   integer   		$order_id 
     * @return  array|boolean
     */
    public function orders_amazon_order( $order_id ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ . ' : Order ID[' . $order_id . ']' ); }

		// Basic parameter check
		if ( empty( $order_id ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/Orders/AmazonOrder/' . $order_id;	
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
	}

    //--------------------------------------------------
    //  Eskimo API Functions: Products ImpEX
    //--------------------------------------------------

    /**
	 * Get product list by batch
	 * - POST api/Products/All
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
   	public function products_all( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/Products/All';	
    	$api_opts   = json_encode( $api_opts ); 
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

    /**
	 * Get product list by EskimoEPOS product ID
	 * - GET api/Products/SpecificID/{id}
     *
     * @param   integer   		$id 
     * @return  array|boolean
     */
    public function products_specific_ID( $id ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ . ' : ID [' . $id . ']' ); }

		// Basic parameter check
		if ( empty( $id ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/Products/SpecificID/' . $id;	
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }
    
    /**
	 * Update remote EPOS product web_ID
	 * - POST api/Products/UpdateCartIDs
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function products_update_cart_ID( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/Products/UpdateCartIDs';
    	$api_opts   = json_encode( $api_opts );
        $api_data   = $this->post_status( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : $api_data;
    }

    //--------------------------------------------------
    //  Eskimo API Functions: Sales ImpEx
    //--------------------------------------------------

    /**
	 * Retrieve remote EPOS store receipt
	 * - GET api/Sales/RetrieveSale/{Store}/{Till}/{Receipt}
     *
     * @return  array|boolean
     */
   	public function sales_retrieve_sale( $store, $till, $receipt ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $store ) || empty( $till ) || empty( $receipt ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/Sales/RetrieveSale/' . $store . '/' . $till . '/' . $receipt;
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
	}

    /**
	 * Insert a sale into the Eskimo system
	 * - POST api/Sales/InsertSale
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function sales_insert_sale( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/Sales/InsertSale';
    	$api_opts   = json_encode( $api_opts );
        $api_data   = $this->post_status( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : $api_data;
    }

    /**
	 * Retrieve remote Eskimo list of sales based on the search criteria
	 * - POST api/Sales/Search
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function sales_search( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/Sales/Search';
    	$api_opts   = json_encode( $api_opts );
        $api_data   = $this->post_status( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : $api_data;
    }

    /**
	 * Retrieve remote Eskimo list of sales channels
	 * - POST api/Sales/Channels
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function sales_channels( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/Sales/Channels';
    	$api_opts   = json_encode( $api_opts );
        $api_data   = $this->post_status( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : $api_data;
    }

    //--------------------------------------------------
    //  Eskimo API Functions: Shops ImpEX
    //--------------------------------------------------

    /**
	 * Get all current EPOS shops
	 * - GET api/Shops/All
     *
     * @return  array|boolean
     */
   	public function shops_all() {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/Shops/All';
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

    /**
	 * Get an EPOS shop by specific ID
	 * - GET api/Shops/SpecificID/{id}
     *
     * @param   string  		$id
     * @return  array|boolean
     */
    public function shops_specific_ID( $id ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ . ' : ID [' . $id . ']' ); }

		// Basic parameter check
		if ( empty( $id ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/Shops/SpecificID/' . $id;	
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
	}

    //--------------------------------------------------
    //  Eskimo API Functions: SKU ImpEX
    //--------------------------------------------------

    /**
	 * Get EPOS SKUs by batch
	 * - POST api/SKUs/All
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
   	public function skus_all( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/SKUs/All';
    	$api_opts   = json_encode( $api_opts ); 
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

    /**
	 * Get EPOS SKU by specific sku code
	 * - GET api/SKUs/SpecificSKUCode/{id}
     *
     * @param   string  		$id
     * @return  array|boolean
     */
   	public function skus_specific_code( $id ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ . ' : Code [' . $id . ']' ); }

		// Basic parameter check
		if ( empty( $id ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/SKUs/SpecificSKUCode/' . $id;
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
	}

    /**
	 * Get EPOS SKUs by specific EPOS product ID
	 * - GET api/SKUs/SpecificIdentifier/{id}
     *
     * @param   string  		$id 
     * @return  array|boolean
     */
   	public function skus_specific_ID( $id ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ . ' : ID [' . $id . ']' ); }

		// Basic parameter check
		if ( empty( $id ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/SKUs/SpecificIdentifier/' . $id;
    	$api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
	}

    //--------------------------------------------------
    //  Eskimo API Functions: StockTaking REST ImpEx
    //--------------------------------------------------

    /**
	 * Retrieves a list of products and operators that can be used for stock taking
	 * - GET api/StockTaking/GetProductData
     *
     * @return  array|boolean
     */
   	public function stock_taking_get_product_data() {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/StockTaking/GetProductData';
    	$api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
	}

    /**
	 * Increments the counted figure for a collection of products
	 * - POST api/StockTaking/IncrementCounts
	 *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
   	public function stock_taking_increment_counts( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/StockTaking/IncrementCounts';
    	$api_opts   = json_encode( $api_opts ); 
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
	}

	/**
	 * Retrieves a distinct list of all areas counted in this stock take
	 * - GET api/StockTaking/RetrieveAreas
	 *
     * @return  array|boolean
     */
   	public function stock_taking_retrieve_areas() {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/StockTaking/RetrieveAreas';
    	$api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
	}

	/**
	 * Retrieves a list of all counts. This can be used to match up with local data
	 * - POST api/StockTaking/ValidateStockTake
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
   	public function stock_taking_validate_stock_take( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/StockTaking/ValidateStockTake';
    	$api_opts   = json_encode( $api_opts ); 
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
	}

    //--------------------------------------------------
    //  Eskimo API Functions: Tax REST ImpEx
    //--------------------------------------------------

    /**
	 * Get EPOS Tax Codes
	 * - GET api/TaxCodes/All
     *
     * @return  array|boolean
     */
    public function tax_codes_all() {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/TaxCodes/All';
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

    /**
     * Get EPOS Tax Codes optionally by ID
     *
     * @param   boolean 		$id
     * @return  array|boolean
     */
    public function tax_codes_specific_ID( $id ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ . ' : ID [' . (int) $id . ']' ); }

		// Basic parameter check
		$id = absint( $id );
		if ( $id <= 0 ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/TaxCodes/SpecificID/' . $id;
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

    //--------------------------------------------------
    //  Eskimo API Functions: Tenders REST ImpEx
    //--------------------------------------------------

    /**
	 * Get EskimoEPOS Tenders
	 * - POST api/Tenders/All
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function tenders_all( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/Tenders/All';
    	$api_opts   = json_encode( $api_opts ); 
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

    //--------------------------------------------------
    //  Eskimo API Functions: Till Menu REST ImpEx
    //--------------------------------------------------

    /**
	 * Get EskimoEPOS Till menu areas
	 * - POST api/TillMenu/Areas
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function till_menu_areas( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/TillMenu/Areas';
    	$api_opts   = json_encode( $api_opts ); 
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
	}

    /**
	 * Get EskimoEPOS Till menu products
	 * - POST api/TillMenu/Products
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function till_menu_products( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/TillMenu/Products';
    	$api_opts   = json_encode( $api_opts ); 
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
	}

    /**
	 * Get EskimoEPOS Till menu products
	 * - POST api/TillMenu/ProductsDump
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function till_menu_products_dump( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/TillMenu/ProductsDump';
    	$api_opts   = json_encode( $api_opts ); 
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

    /**
	 * Get EskimoEPOS Till menu sections
	 * - POST api/TillMenu/Sections
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function till_menu_sections( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/TillMenu/Sections';
    	$api_opts   = json_encode( $api_opts ); 
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
	}

    /**
	 * Get EskimoEPOS Till menu product search
	 * - POST api/TillMenu/ProductSearch
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function till_menu_product_search( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
		$api_url    = $oauth['domain'] . 'api/TillMenu/ProductSearch';
    	$api_opts   = json_encode( $api_opts ); 
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
	}

    /**
	 * Get EskimoEPOS Till menu functions
	 * Deprecated: No longer part of the EskimoEPOS API
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function till_menu_functions( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
		$api_url    = $oauth['domain'] . 'api/TillMenu/ProductSearch';
    	$api_opts   = json_encode( $api_opts ); 
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

	/**
	 * Get EskimoEPOS Till menu source codes
	 * - GET api/TillMenu/SourceCodes
     *
     * @return  array|boolean
     */
    public function till_menu_source_codes() {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/TillMenu/SourceCodes';
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

	/**
	 * Get EskimoEPOS Till menu unit info
	 * - GET api/TillMenu/UnitInfo
     *
     * @return  array|boolean
     */
    public function till_menu_unit_info() {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/TillMenu/UnitInfo';
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

	/**
	 * Get EskimoEPOS Till menu sent orders
	 * - POST api/TillMenu/SendOrderItems
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function till_menu_send_order_items( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

		// Basic parameter check
		if ( empty( $api_opts ) ) { return false; }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/TillMenu/SendOrderItems';
    	$api_opts   = json_encode( $api_opts ); 
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }
}
