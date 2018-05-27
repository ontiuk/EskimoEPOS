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
     * Retrieve data from remote API
     *
     * @param   string  		$api_url
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
    	$curl->setHeader( 'Content-Type', 'application/json' );
        $curl->setHeader( 'Authorization', 'Bearer ' . $access_token );
        $curl->get( $api_url, $api_opts );
        
		if ( $this->debug && $curl->error ) { 
			error_log( 'CURL GET Error [' . print_r( $curl->error, true ) . ']' ); 
		}

        return ( $curl->error ) ? false : $curl->response;    
	}

    /**
     * Retrieve media from remote API
     *
     * @param   string  		$api_url
     * @param   array|boolean   $api_opts Default false   
     * @return  object|array
     */
    protected function get_media( $api_url, $api_opts = false ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ' URL[' . $api_url . ']' ); }

        // Get access token & test for timeout
        $auth = get_transient( 'eskimo_access_authenticated' );
        $access_token = get_transient( 'eskimo_access_token' );
        if ( false === $auth ) { return false; }

        // Initialise response
        $curl = new Curl();
        $curl->setHeader( 'Authorization', 'Bearer ' . $access_token );
        $curl->get( $api_url, $api_opts );
        
		if ( $this->debug ) { error_log( 'CURL GET URL[' . $api_url . ']' ); }

		if ( $this->debug && $curl->error ) { 
			error_log( 'CURL GET Error [' . print_r( $curl->error, true ) . ']' ); 
		}

        return ( $curl->error ) ? false : $curl->response;    
    }
    
    /**
     * Send & Retrieve via remote API
     *
     * @param   string  		$api_url
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
        
		if ( $this->debug && $curl->error ) { 
			error_log( 'CURL POST Error [' . print_r( $curl->error, true ) . ']' ); 
		}
		
        return ( $curl->error ) ? false : $curl->response;    
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
    	return ( false === $api_data ) ? false : $api_data;
    }

    /**
     * Apply remote EskimoEPOS barcode action
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
    	return ( false === $api_data ) ? false : $api_data;
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
    	return ( false === $api_data ) ? false : $api_data;
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
    	return ( false === $api_data ) ? false : $api_data;
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
    	return ( false === $api_data ) ? false : $api_data;
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
    	return ( false === $api_data ) ? false : $api_data;
    }
    
    /**
     * Get product list by specific EPOS category ID
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
    	return ( false === $api_data ) ? false : $api_data;
    }
    
    //--------------------------------------------------
    //  Eskimo API Functions: Customers ImpEX
    //--------------------------------------------------

    /**
     * Get EPOS customer data by ID
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
    	return ( false === $api_data ) ? false : $api_data;
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
    	return ( false === $api_data ) ? false : $api_data;
    }

    /**
     * Get EPOS customer data 
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
    	return ( false === $api_data ) ? false : $api_data;
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
    	return ( false === $api_data ) ? false : $api_data;
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
    	return ( false === $api_data ) ? false : $api_data;
	}

    //--------------------------------------------------
    //  Eskimo API Functions: External Categories ImpEX
    //--------------------------------------------------

    /**
	 * Insert/Update remote EPOS external category and Item Specific information
	 *  - Used with  category information from eBay or Amazon
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
    	return ( false === $api_data ) ? false : $api_data;
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
    	return ( false === $api_data ) ? false : $api_data;
    }

    //--------------------------------------------------
    //  Eskimo API Functions: Image Links ImpEX
    //--------------------------------------------------

    /**
     * Retrieve EskimoEPOS product image links
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
    	return ( false === $api_data ) ? false : $api_data;
    }

	/**
     * Updates EskimoEPOS Image Link Cart IDs
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function image_links_cart_ID( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/ImageLinks/UpdateCartIDs';	
    	$api_opts   = json_encode( $api_opts ); 
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : $api_data;
    }

    //--------------------------------------------------
    //  Eskimo API Functions: Images ImpEX
    //--------------------------------------------------

    /**
     * Retrieve remote EskimoEPOS product images
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
    	return ( false === $api_data ) ? false : $api_data;
    }

	/**
     * Updates EskimoEPOS Images Cart IDs
     *
     * @param   array   		$api_opts 
     * @return  array|boolean
     */
    public function images_cart_ID( $api_opts ) {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__ ); }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
    	$api_url    = $oauth['domain'] . 'api/Images/UpdateCartIDs';	
    	$api_opts   = json_encode( $api_opts ); 
        $api_data   = $this->post_data( $api_url, $api_opts );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : $api_data;
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
    	return ( false === $api_data ) ? false : $api_data;
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
    	return ( false === $api_data ) ? false : $api_data;
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
    	return ( false === $api_data ) ? false : $api_data;
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
    	return ( false === $api_data ) ? false : $api_data;
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
    	return ( false === $api_data ) ? false : $api_data;
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
    	return ( false === $api_data ) ? false : $api_data;
	}

    /**
     * Retrieves a singular order class for a specific customer Order ID
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
    	return ( false === $api_data ) ? false : $api_data;
    }

    /**
     * Retrieves a singular order class for a specific mail order Order ID
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
    	return ( false === $api_data ) ? false : $api_data;
    }

    /**
     * Retrieves a singular order class for a specific customer Order ID
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
    	return ( false === $api_data ) ? false : $api_data;
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
    	return ( false === $api_data ) ? false : $api_data;
    }

    /**
     * Retrieves a singular order for a specific eBay Order ID
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
    	return ( false === $api_data ) ? false : $api_data;
	}
	
    /**
     * Retrieves Order Fulfiment Methods
     *
     * @return  array|boolean
     */
    public function orders_methods() {
        if ( $this->debug ) { error_log( __CLASS__ . ' : ' . __METHOD__  ); }

        // Get authentication parameters
        $oauth = $this->api->get_oauth_params();

        // Set remote url and get response
        $api_url    = $oauth['domain'] . 'api/Orders/FulfilmentMethods';	
        $api_data   = $this->get_data( $api_url );

        // Retrieve decoded data as array or false 
    	return ( false === $api_data ) ? false : $api_data;
	}

    /**
     * EskimoEPOS order search: Customer ID
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
    	return ( false === $api_data ) ? false : $api_data;
	}

   /**
     * Retrieves a singular order for a specific Amazon Order ID
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
    	return ( false === $api_data ) ? false : $api_data;
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
    	return ( false === $api_data ) ? false : $api_data;
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
    	return ( false === $api_data ) ? false : $api_data;
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
        $api_data   = $this->post_data( $api_url, $api_opts );

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
    	return ( false === $api_data ) ? false : $api_data;
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
    	return ( false === $api_data ) ? false : $api_data;
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
    	return ( false === $api_data ) ? false : $api_data;
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
    	return ( false === $api_data ) ? false : $api_data;
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
    	return ( false === $api_data ) ? false : $api_data;
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
    	return ( false === $api_data ) ? false : $api_data;
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
    	return ( false === $api_data ) ? false : $api_data;
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
    	return ( false === $api_data ) ? false : $api_data;
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
    	return ( false === $api_data ) ? false : $api_data;
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
    	return ( false === $api_data ) ? false : $api_data;
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
    	return ( false === $api_data ) ? false : $api_data;
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
    	return ( false === $api_data ) ? false : $api_data;
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
    	return ( false === $api_data ) ? false : $api_data;
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
    	return ( false === $api_data ) ? false : $api_data;
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
    	return ( false === $api_data ) ? false : $api_data;
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
    	return ( false === $api_data ) ? false : $api_data;
	}

    /**
     * Get EskimoEPOS Till menu functions
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
    	return ( false === $api_data ) ? false : $api_data;
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
    	return ( false === $api_data ) ? false : $api_data;
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
    	return ( false === $api_data ) ? false : $api_data;
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
    	return ( false === $api_data ) ? false : $api_data;
    }
}
