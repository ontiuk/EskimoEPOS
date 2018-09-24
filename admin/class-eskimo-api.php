<?php

/**
 * Connect to the Eskimo EPOS and get/add json data via api calls
 *
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
        if ( $debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

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
     * Retrieve data from remote API via GET
     *
     * @param   string  		$api_url
     * @return  object|array
     */
    protected function get_data( $api_url ) {

        // Get access token & test for timeout
        $auth = get_transient( 'eskimo_access_authenticated' );
        $access_token = get_transient( 'eskimo_access_token' );
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

		// Check Error
		if ( $this->debug && is_wp_error( $request ) ) {
			error_log( 'cUrl Error [' . $request->get_error_code() . ' : ' . $request->get_error_message() . ']' );
		}

		// Return error or request response
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
        $auth = get_transient( 'eskimo_access_authenticated' );
        $access_token = get_transient( 'eskimo_access_token' );
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

		// Check Error
		if ( $this->debug && is_wp_error( $request ) ) {
			error_log( 'cUrl Error [' . $request->get_error_code() . ' : ' . $request->get_error_message() . ']' );
		}

		// Return error or request response
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
        $auth = get_transient( 'eskimo_access_authenticated' );
        $access_token = get_transient( 'eskimo_access_token' );
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

		// Check Error
		if ( $this->debug && is_wp_error( $request ) ) {
			error_log( 'cUrl Error [' . $request->get_error_code() . ' : ' . $request->get_error_message() . ']' );
		}

		// Return error or request response
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
        $auth = get_transient( 'eskimo_access_authenticated' );
        $access_token = get_transient( 'eskimo_access_token' );
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

		// Check Error
		if ( $this->debug && is_wp_error( $request ) ) {
			error_log( 'cUrl Error [' . $request->get_error_code() . ' : ' . $request->get_error_message() . ']' );
		}

		// Return error or request response
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
        $auth = get_transient( 'eskimo_access_authenticated' );
        $access_token = get_transient( 'eskimo_access_token' );
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

		// Return error or request response
		return ( is_wp_error( $request ) ) ? false : wp_remote_retrieve_response_code( $request ); 
	}

    //--------------------------------------------------
    //  Eskimo API Functions: Addresses ImpEX
    //--------------------------------------------------
	
    /**
	 * Insert remote EskimoEPOS Address information
	 * - Requires customer ID and Address Reference
     *
     * @param   string   		$cust_id 
     * @param   string   		$addr_ref 
     * @param   array   		$api_opts 
     * @return  array|boolean
     */       	
   	public function addresses_insert( $cust_id, $addr_ref, $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ . ' ' . $cust_id . ' ' . $addr_ref ); }

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
	 * - Requires customer ID and Address Reference
     *
     * @param   string   		$cust_id 
     * @param   string   		$addr_ref 
     * @param   array   		$api_opts 
     * @return  array|boolean
     */       	
   	public function addresses_update( $cust_id, $addr_ref, $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ . ' ' . $cust_id . ' ' . $addr_ref ); }

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
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */       	
   	public function addresses_search( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

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
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */       	
   	public function addresses_validation_summary( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

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
     *
     * @param   string   		$id 
     * @return  array|boolean
     */
   	public function addresses_validation_detailed( $id ) {
		if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ . ' : ID [' . $id . ']' ); }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/Addresses/AddressValidationDetailed/' . $id;
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
	}

    //--------------------------------------------------
    //  Eskimo API Functions: Amazon ImpEX
    //--------------------------------------------------

    /**
     * Download remote EskimoEPOS Amazon Orders not yet in Eskimo system
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */       	
   	public function amazon_download_orders( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

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
     *
     * @return  array|boolean
     */
   	public function amazon_get_order_info() {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ . ' ID: ' . $id ); }

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
     *
     * @return  array|boolean
     */
   	public function amazon_upload_product_info() {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get api response
        $api_url    = $oauth['domain'] . 'api/Amazon/UploadProductInfo';
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

    //--------------------------------------------------
    //  Eskimo API Functions: Barcode ImpEX
    //--------------------------------------------------

    /**
     * Retrieve remote EskimoEPOS barcode information
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */       	
   	public function barcode_get_info( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

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
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */       	
   	public function barcode_apply_action( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

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
    //  Eskimo API Functions: Category ImpEX
    //--------------------------------------------------

    /**
     * Retreive remote EPOS category list
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
     * Get a category by EPOS category id
     *
     * @param   string   		$id 
     * @return  array|boolean
     */
    public function categories_specific_ID( $id ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ . ' : ID [' . $id . ']' ); }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/Categories/SpecificID/' . $id;	
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }
    
    /**
     * Get categories by EPOS parent id
     *
     * @param   string   		$id 
     * @return  array|boolean
     */    
    public function categories_child_categories_ID( $id ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ . ' : ID [' . $id . ']' ); }

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
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */       	
   	public function categories_update_cart_ID( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/Categories/UpdateCartIDs';
    	$api_opts   = json_encode( $api_opts );
        $api_data   = $this->post_status( $api_url, $api_opts );

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
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
   	public function category_products_all( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

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
     * - Deprecated: Use products_specific_id
     *
     * @param   string   		$id 
     * @return  array|boolean
     */
    public function category_products_specific_category( $id ) {
		if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ . ' : ID [' . $id . ']' ); }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/CategoryProducts/SpecificCategory/' . $id;	
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

    //--------------------------------------------------
    //  Eskimo API Functions: Countries ImpEX
    //--------------------------------------------------

    /**
     * Retrieve a list of countries
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
   	public function countries_search( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

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
    //  Eskimo API Functions: Customers ImpEX
    //--------------------------------------------------

    /**
     * Get specific EPOS customer data by ID
     *
     * @param   string  		$id
     * @return  array|boolean
     */
   	public function customers_specific_ID( $id ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

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
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
   	public function customers_search( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

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
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
   	public function customers_customers_dump( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

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
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function customers_insert( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

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
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function customers_update( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

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
	 *  - Used with category information from eBay or Amazon
	 *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function external_categories_insert( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

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
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function external_categories_specific_ID( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

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
    //  Eskimo API Functions: Gift Cards ImpEX
    //--------------------------------------------------

    /**
     * Retrieve EskimoEPOS gift card balance
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function gift_cards_balance_enquiry( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

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
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function gift_cards_balance_increment( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

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
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function gift_cards_balance_redeem( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

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
    //  Eskimo API Functions: Image Links ImpEX
    //--------------------------------------------------

    /**
     * Retrieve EskimoEPOS product image links by batch
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function image_links_all( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

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
    //  Eskimo API Functions: Images ImpEX
    //--------------------------------------------------

    /**
     * Retrieve remote EskimoEPOS product images objects (max 10,000)
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function images_all( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

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
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function images_update_cart_ID( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

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
     *
     * @param   integer   		$id 
     * @return  array|boolean
     */
    public function images_image_data( $id ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ . ' : ID [' . $id . ']' ); }

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
     *
     * @param   integer   		$id 
     * @return  array|boolean
     */
    public function images_as_base64( $id ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ . ' : ID [' . $id . ']' ); }

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
	 *
     * @param   integer   		$image_id 
     * @param   string    		$token 
     * @return  array|boolean
     */
    public function images_image_url( $image_id, $token ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ . ' : Image ID [' . $image_id . ']' ); }

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
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function listings_all_unlisted( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

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
	 *
     * @param   string   		$key 
     * @return  array|boolean
     */
    public function listings_listing_image( $key ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ . ' : Key [' . $key . ']' ); }

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
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function listings_mark_as_listed( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

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
    //  Eskimo API Functions: Operators ImpEX
    //--------------------------------------------------

    /**
     * retrieve remote EskimoEPOS list of Operators (or Clerks)
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
   	public function operators_search( $api_opts ) {
		if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }
   
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
     * Retrieves a list of status codes that an order can be in.
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
     *
     * @param   integer   		$order_id 
     * @return  array|boolean
     */
    public function orders_website_order( $order_id ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ . ': Order ID[' . $order_id . ']' ); }

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
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
   	public function orders_search( $api_opts ) {
		if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }
   
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
     * Get product list
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
   	public function products_all( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

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
     *
     * @param   integer   		$id 
     * @return  array|boolean
     */
    public function products_specific_ID( $id ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ . ' : ID [' . $id . ']' ); }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/Products/SpecificID/' . $id;	
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }
    
    /**
     * Update remote EPOS product webID
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function products_update_cart_ID( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

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
    //  Eskimo API Functions: Sales ImpEX
    //--------------------------------------------------

    /**
     * Retrieve remote EPOS store receipt
     *
     * @return  array|boolean
     */
   	public function sales_retrieve_sale( $store, $till, $receipt ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/Sales/RetrieveSale/' . $store . '/' . $till . '/' . $receipt;
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
	}

    /**
     * Insert a sale into the Eskimo system.
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function sales_insert_sale( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

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
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function sales_search( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

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
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function sales_channels( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

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
     *
     * @param   string  		$id
     * @return  array|boolean
     */
    public function shops_specific_ID( $id ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ . ' : ID [' . $id . ']' ); }

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
     * Get EPOS SKUs
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
   	public function skus_all( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

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
     *
     * @param   string  		$code
     * @return  array|boolean
     */
   	public function skus_specific_code( $code ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ . ' : Code [' . $code . ']' ); }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/SKUs/SpecificSKUCode/' . $code;
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
	}

    /**
     * Get EPOS SKUs by specific EPOS product ID
     *
     * @param   string  		$id 
     * @return  array|boolean
     */
   	public function skus_specific_ID( $id ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ . ' : ID [' . $id . ']' ); }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/SKUs/SpecificIdentifier/' . $id;
    	$api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
	}

    //--------------------------------------------------
    //  Eskimo API Functions: StockTaking ImpEX
    //--------------------------------------------------

    /**
     * Retrieves a list of products and operators that can be used for stock taking.
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
	 *
     * @return  array|boolean
     */
   	public function stock_taking_increment_counts() {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/StockTaking/IncrementCounts';
        $api_data   = $this->post_data( $api_url);

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
	}

	/**
	 * Retrieves a distinct list of all areas counted in this stock take
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
     *
     * @return  array|boolean
     */
   	public function stock_taking_validate_stock_take() {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/StockTaking/ValidateStockTake';
        $api_data   = $this->post_data( $api_url);

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
	}

    //--------------------------------------------------
    //  Eskimo API Functions: Tax ImpEX
    //--------------------------------------------------

    /**
     * Get EPOS Tax Codes
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
    public function tax_codes_specific_ID( $id = false ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ . ' : ID [' . (int) $id . ']' ); }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/TaxCodes/SpecificID/' . $id;
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : json_decode( $api_data );
    }

    //--------------------------------------------------
    //  Eskimo API Functions: Tenders ImpEX
    //--------------------------------------------------

    /**
     * Get EskimoEPOS Tenders
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function tenders_all( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

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
    //  Eskimo API Functions: Till Menu ImpEX
    //--------------------------------------------------

    /**
     * Get EskimoEPOS Till menu areas
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function till_menu_areas( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

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
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function till_menu_products( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

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
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function till_menu_products_dump( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

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
     *
     * @return  array|boolean
     */
    public function till_menu_sections() {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

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
     *
     * @return  array|boolean
     */
    public function till_menu_product_search() {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

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
     * @return  array|boolean
     */
    public function till_menu_functions() {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

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
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function till_menu_send_order_items( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

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
