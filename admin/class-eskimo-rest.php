<?php

/**
 * Import & Export via the EskimoEPOS API
 *
 * @link       https://on.tinternet.co.uk
 * @package    Eskimo
 * @subpackage Eskimo/admin
 */

/**
 * EskimoEPOS product, category, customer, and order processing and sync
 * 
 * @package    Eskimo
 * @subpackage Eskimo/admin
 * @author     Stephen Betley <on@tinternet.co.uk>
 */
final class Eskimo_REST {

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
	 * @var      string    $base_dir  string path for the plugin directory 
	 */
    private $base_dir;

	/**
	 * The remote API initiation class 
	 *
	 * @var      object    $api  Eskimo_API instance
	 */
    private $api;

	/**
	 * The Woocommerce processing class 
	 *
	 * @var      object    $wc  Eskimo_WC instance
	 */
    private $wc;

	/**
	 * Initialize the class and set its properties
	 *
	 * @param	object		$api		The API object		
	 * @param   string 		$eskimo     The name of this plugin
	 */
	public function __construct( Eskimo_API $api, $eskimo ) {

		// Set up class settings
        $this->api      = $api;
        $this->wc       = new Eskimo_WC( $eskimo );
   		$this->version  = ESKIMO_VERSION;
		$this->debug    = ESKIMO_REST_DEBUG;
    	$this->base_dir	= plugin_dir_url( __FILE__ ); 
	}


    //----------------------------------------------
    // EPOS Account Updates & Info
    //----------------------------------------------

	/**
	 * Get remote API account info
	 *
	 * @return string
	 */
	public function get_account_user_info() {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

        // Test API connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }
        
        // Get remote data
		$api_data = $this->api->account_user_info();

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
		}

		// Return account details
		return ( empty( $api_data ) ) ? $this->api_error( 'No Account Info Found' ) : $api_data;
	}
	
	/**
	 * Update remote API account password
	 *
	 * @param	string	$old_password
	 * @param	string	$new_password
	 * @return 	string
	 */
	public function get_account_password( $old_password, $new_password ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ' Old [' . $old_password . '] New [' . $new_password . ']', 'rest' ); }

        // Test API connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

		// Initial check
		if ( $old_password === $new_password ) {
            return $this->api_error( 'Current and New Passwords Identical' );
		}

		// Check string length: Old Password
		if ( strlen( $old_password ) < 6 || strlen( $old_password ) > 100 ) {
            return $this->api_error( 'Old Password Wrong Length 6-100 [' . strlen( $old_password ) . ']' );
		}

		// Check string length: New Password
		if ( strlen( $new_password ) < 6 || strlen( $new_password ) > 100 ) {
            return $this->api_error( 'New Password Wrong Length 6-100 [' . strlen( $old_password ) . ']' );
		}

		// Construct API data
		$api_opts = [
			'OldPassword' 		=> $old_password,
			'NewPassword'		=> $new_password,	
			'ConfirmPassword'	=> $new_password
		];

        // Get remote data
        $api_data = $this->api->account_password( $api_opts );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
		}

		// Return response: empty return deemed successful
		return $api_data;
	}

    //----------------------------------------------
    // Woocommerce Category Import
    //----------------------------------------------

    /**
     * Get remote API categories
     *
     * @return boolean
     */
    public function get_categories_all() {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

        // Test API connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }
        
        // Get remote data
        $api_data = $this->api->categories_all();

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
		}

        // OK process data
        $api_count = $this->api_count( $api_data );
		if ( $this->debug ) { eskimo_log( 'Category Count[' . $api_count . ']', 'rest' ); }

		// No API data
        if ( $api_count === 0 ) {
            return $this->api_error( 'No Results Returned' );
        }

        // Process Woocommerce Import 
        return $this->wc->get_categories_all( $api_data );
    }

    /**
     * Get remote API category by ID
     *
     * @param   string  $id default empty
     * @return  boolean
     */
    public function get_categories_specific_ID( $id ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . ']', 'rest' ); }

        // Validate Category ID
        if ( empty( $id ) ) {
            return $this->api_error( 'Invalid Category ID' );
        }

        // Final sanity
        $id = sanitize_text_field( $id );

        // Test API connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Get remote data
        $api_data = $this->api->categories_specific_ID( $id );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
		}

        // OK process data
        $api_data = $this->api_has_data( $api_data );
        if ( $this->debug ) { eskimo_log( 'Cateory ID [' . $id . '] Data[' . gettype( $api_data ) . ']', 'rest' ); }

		// No API data or invalid
        if ( false === $api_data ) {
            return $this->api_error( 'No Results Returned' );
        }

        if ( $this->debug ) { eskimo_log( 'Cat[' . print_r( $api_data, true ) . ']', 'rest' ); }

        // Process Woocommerce Import
        return $this->wc->get_categories_specific_ID( $api_data );
    }

    /**
     * Get remote API category by parent ID
     *
     * @param   string  $id default empty
     * @return  boolean
     */
    public function get_categories_child_categories_ID( $id = '' ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . ']', 'rest' ); }

        // Validate Category ID
        if ( empty( $id ) ) {
            return $this->api_error( 'Invalid Category Parent ID' );
        }

        // Final sanity
        $id = sanitize_text_field( $id );

        // Test API connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Get remote data
        $api_data = $this->api->categories_child_categories_ID( $id );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
		}

        // OK process data
        $api_count = $this->api_count( $api_data );
        if ( $this->debug ) { eskimo_log( 'Child Category Count[' . $api_count . ']', 'rest' ); }

		// No API data
        if ( $api_count === 0 ) {
            return $this->api_error( 'No Results Returned' );
        }

        // Process Woocommerce Import
        return $this->wc->get_categories_child_categories_ID( $api_data );
    }

    //----------------------------------------------
    // Woocommerce Category Product Import
    //----------------------------------------------

    /**
     * Get remote API products by category
     * - Requires 2 parameters: StartPosition & RecordCount
     * - Deprecated, use /Products/All to import products
     *
     * @param   integer $start      default 1  
     * @param   integer $records    default 25
     * @return  boolean
     */
    public function get_category_products( $start = 1, $records = 250 ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': Start[' . $start . '] Records[' . $records . ']', 'rest' ); }

        // Sensible defaults
        $api_defaults = [
            'StartPosition' => 1,
            'RecordCount'   => 250
        ];

        // Pre-Sanitize
        $start   = absint( $start );
        $records = absint( $records );

        // Validate options & set a sensible batch range
        $api_opts['StartPosition']  = ( $start === 0 ) ? $api_defaults['StartPosition'] : $start;
        $api_opts['RecordCount']    = ( $records === 0 || $records > $api_defaults['RecordCount'] ) ? $api_defaults['RecordCount'] : $records;
        if ( $this->debug ) { eskimo_log( 'Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . ']', 'rest' ); }
                    
        // Test API connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Batched Results
        $results = [];
        
        // Get remote data
        $api_data = $this->api->category_products( $api_opts );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
		}

        // OK process data
        $api_count = $this->api_count( $api_data );
        if ( $this->debug ) { eskimo_log( 'Category Product Count[' . $api_count . '] Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . ']', 'rest' ); }
            
		// No API data
        if ( $api_count === 0 ) {
            return $this->api_error( 'No Results Returned' );
        }

        if ( $this->debug ) { eskimo_log( print_r( $api_data, true ), 'rest' ); }

        // Return data set
        return $api_data;
    }

    /**
	 * Get remote API category by ID
	 * - Performs no import, returns API data
     * 
     * @param   string  $id
     * @return  boolean
     */
    public function get_category_products_specific_category_ID( $id ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . ']', 'rest' ); }

        // Test Category ID
        if ( empty( $id ) ) {
            return $this->api_error( 'Invalid Category ID' );
        }

        // Final sanity
        $id = sanitize_text_field( $id );

        // Test API connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Get remote data
        $api_data = $this->api->category_products_specific_category_ID( $id );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
		}

        // OK process data
        $api_count = $this->api_count( $api_data );
        if ( $this->debug ) { eskimo_log( 'Category Product Count[' . $api_count . ']', 'rest' ); }

		// No API data
        if ( $api_count === 0 ) {
            return $this->api_error( 'No Results Returned' );
		}

        // Return data set
        return $api_data;
    }

    //----------------------------------------------
    // Woocommerce Product Import
    //----------------------------------------------

    /**
     * Get remote API products
     *
     * @param   integer $start      default 1  
     * @param   integer $records    default 25
     * @return  boolean
     */
    public function get_products( $start = 1, $records = 25 ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': Start[' . $start . '] Records[' . $records . ']', 'rest' ); }

        // Sensible defaults
        $api_defaults = [
            'StartPosition' => 1,
			'RecordMax'   	=> 50,
			'RecordDefault'	=> 25
        ];

        // Pre-Sanitize
        $start   = absint( $start );
        $records = absint( $records );

        // Test API connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Validate Opts
        $api_opts = [];
        $api_opts['StartPosition']  = ( $start === 0 ) ? $api_defaults['StartPosition'] : $start;
        $api_opts['RecordCount']    = ( $records === 0 || $records > $api_defaults['RecordMax'] ) ? $api_defaults['RecordDefault'] : $records;
        if ( $this->debug ) { eskimo_log( 'Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . ']', 'rest' ); }

        // Get remote data
        $api_data = $this->api->products_all( $api_opts );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
		}

        // OK process data
        $api_count = $this->api_count( $api_data );
        if ( $this->debug ) { eskimo_log( 'Products Count[' . $api_count . '] Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . ']', 'rest' ); }

		// No API data
        if ( $api_count === 0 ) {
            return $this->api_error( 'No Results Returned' );
        }

        // Get products data per batch item & add product SKU
		foreach ( $api_data as $k=>$v ) {
			$sku = $this->get_skus_specific_ID( $v->eskimo_identifier, false );
			if ( is_wp_error( $sku ) ) { continue; }
            $v->sku = $sku;
        }

        // Process data
        if ( $this->debug ) { eskimo_log( print_r( $api_data, true ), 'rest' ); }

        // Process Woocommerce Import for Web_ID update
		return $this->wc->get_products_all( $api_data );
	}

    /**
     * Get remote API products
     * 
     * @return  boolean
     */
    public function get_products_all() {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

        // Test API connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Validate Opts
        $api_opts = [];
        $api_opts['StartPosition']  = 1;
        $api_opts['RecordCount']    = 25;
        if ( $this->debug ) { eskimo_log( 'Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . ']', 'rest' ); }

        // Batched Results
        $results = [];

        // Iterate batched results
        do { 
    
            // Get remote data
            $api_data = $this->api->products_all( $api_opts );

			// Validate API data
			if ( false === $api_data ) {
				return $this->api_rest_error();
			}

            // OK process data
            $api_count =  $this->api_count( $api_data );
            if ( $this->debug ) { eskimo_log( 'Products Count[' . $api_count . '] Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . ']', 'rest' ); }

            // Nothing to do here...
            if ( $api_count === 0 ) { break; }

            // Get products data per batch item & add product SKU
            foreach ( $api_data as $k=>$v ) {
   				$sku = $this->get_skus_specific_ID( $v->eskimo_identifier, false );
				if ( is_wp_error( $sku ) ) { continue; }
        	    $v->sku = $sku;
            }

            // Process data
            if ( $this->debug ) { eskimo_log( print_r( $api_data, true ), 'rest' ); }

            // Process Woocommerce Import
            $api_results = $this->wc->get_products_all( $api_data );

            // Update loop position
            $api_opts['StartPosition'] += $api_opts['RecordCount'];

			// Log products for update
			if ( is_wp_error( $api_results ) || empty( $api_results ) ) { continue; }
			foreach ( $api_results as $result ) { $results[] = $result; }

        } while ( true );

        // Return results for Web_ID update
        return $results;
	}
	
    /**
     * Get remote API products
     * 
     * @param   string  $route 
     * @param   integer $created 
     * @return  boolean
     */
    public function get_products_new( $route, $created ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': Route[' . $route . '] Created[' . $created . ']', 'rest' ); }

		// Pre-Sanitize
		$route 		= sanitize_text_field( $route );
		$created	= absint( $created );

        // Test API connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

		// Get modified from timeframe
		$timestamp_from = ( $route === 'all' ) ? 0 : $this->get_modified_time( $route, $created );
		if ( is_wp_error( $timestamp_from ) ) { return $timestamp_from; }

        // Validate Opts
        $api_opts = [];
        $api_opts['StartPosition']  = 1;
        $api_opts['RecordCount']    = 25;
        if ( $this->debug ) { eskimo_log( 'Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . ']', 'rest' ); }

		// Set timestamp		
		if ( $timestamp_from !== 0 ) {
			$api_opts['TimeStampFrom']	=  $timestamp_from;				
    	    if ( $this->debug ) { eskimo_log( 'TimeStamp[' . $api_opts['TimeStampFrom'] . ']', 'rest' ); }
		}

        // Batched Results
        $results = [];

        // Iterate batched results
        do { 
    
            // Get remote data
            $api_data = $this->api->products_all( $api_opts );

			// Validate API data
			if ( false === $api_data ) {
				return $this->api_rest_error();
			}

            // OK process data
            $api_count =  $this->api_count( $api_data );
            if ( $this->debug ) { eskimo_log( 'Products Count[' . $api_count . '] Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . ']', 'rest' ); }

            // Nothing to do here...
            if ( $api_count === 0 ) { break; }

            // Process data
            if ( $this->debug ) { eskimo_log( print_r( $api_data, true ), 'rest' ); }

            // Process Woocommerce Import
            $api_products = $this->wc->get_products_new( $api_data );

            // Update loop position
            $api_opts['StartPosition'] += $api_opts['RecordCount'];

			// Log products for update
			if ( is_wp_error( $api_products ) || empty( $api_products ) ) { continue; }
			foreach ( $api_products as $result ) { $results[] = $result; }

			// Done enough?
			if ( $api_count <= $api_opts['RecordCount'] ) { break; }

        } while ( true );

        // Return results for Web_ID update
        return $results;
	}

    /**
     * Get remote API products
     *
     * @param   integer $start      default 1  
     * @param   integer $records    default 25
     * @param   integer $records    default 2000-01-01
     * @return  boolean
     */
    public function get_products_modified( $route, $modified, $start = 1, $records = 250 ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': Route[' . $route . '] Modified[' . $modified . '] Start[' . $start . '] Records[' . $records . ']', 'rest' ); }

        // Sensible defaults
        $api_defaults = [
            'StartPosition' => 1,
			'RecordMax'   	=> 2500,
			'RecordDefault'	=> 250
        ];

		// Pre-Sanitize
		$route 		= sanitize_text_field( $route );
		$modified	= absint( $modified );
        $start   	= absint( $start );
        $records 	= absint( $records );

        // Test API connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

		// Get modified from timeframe
		$timestamp_from = $this->get_modified_time( $route, $modified );
		if ( is_wp_error( $timestamp_from ) ) { return $timestamp_from; }
		
        // Validate Opts
		$api_opts = [];
        $api_opts['StartPosition']  = ( $start === 0 ) ? $api_defaults['StartPosition'] : $start;
		$api_opts['RecordCount']    = ( $records === 0 || $records > $api_defaults['RecordMax'] ) ? $api_defaults['RecordDefault'] : $records;
		$api_opts['TimeStampFrom']	=  $timestamp_from;				
        if ( $this->debug ) { eskimo_log( 'Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . '] TimeStamp[' . $api_opts['TimeStampFrom'] . ']', 'rest' ); }

        // Get remote data
        $api_data = $this->api->products_all( $api_opts );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
		}

        // OK process data
        $api_count = $this->api_count( $api_data );
        if ( $this->debug ) { eskimo_log( 'Products Count[' . $api_count . '] Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . '] TimeStamp [' . $api_opts['TimeStampFrom'] . ']', 'rest' ); }

		// No API data
        if ( $api_count === 0 ) {
            return $this->api_error( 'No Results Returned' );
        }

		// Get products data per batch item & add product SKU
		$api_products = [];
		foreach ( $api_data as $k=>$v ) {
			$api_products[] = $v->eskimo_identifier;
        }

        // Process data
        if ( $this->debug ) { eskimo_log( print_r( $api_products, true ), 'rest' ); }

		// OK
		return $api_products;
    }

    /**
     * Get remote API product by ID
     *
     * @param   string  $id
     * @param   boolean $import  Force import default true
     * @return  boolean
     */
    public function get_products_specific_ID( $id, $import = true ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . '] Import[' . (int) $import . ']', 'rest' ); }

        // Test Product
        if ( empty( $id ) ) {
            return $this->api_error( 'Invalid Product ID' );
        }

		// Final sanity
		$id = sanitize_text_field( $id );

        // Test API connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Get remote data
        $api_data = $this->api->products_specific_ID( $id );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
		}

        // OK process data
        $api_data = $this->api_has_data( $api_data );
        if ( $this->debug ) { eskimo_log( 'Product ID [' . $id . '] Data[' . gettype( $api_data ) . ']', 'rest' ); }

		// No API data or invalid
        if ( false === $api_data ) {
            return $this->api_error( 'No Results Returned' );
        }

		// Get Product SKU
		$sku = $this->get_skus_specific_ID( $api_data->eskimo_identifier, false );
		if ( is_wp_error( $sku ) ) { return $sku; }

		// Add Product SKU
		$api_data->sku = $sku;
        if ( $this->debug ) { eskimo_log( print_r( $api_data, true ), 'rest' ); }

        // Process Woocommerce Import
        return ( $import ) ? $this->wc->get_products_specific_ID( $api_data ) : $api_data;
	}

    /**
     * Get remote API product by ID
     *
     * @param   string  $id
     * @param   boolean $import  Force import default true
     * @return  boolean
     */
    public function get_products_import_ID( $id, $path, $import = true ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . '] Path[' . $path . '] Import[' . (int) $import . ']', 'rest' ); }

		// Valid paths
		$paths = [ 'stock', 'tax', 'price', 'category', 'adjust', 'all' ];
		
		// Test paths
		if ( empty( $path ) || ! in_array( $path, $paths ) ) {
            return $this->api_error( 'Invalid Product Path[' . $path . ']' );
		}			

        // Test Product
        if ( empty( $id ) ) {
            return $this->api_error( 'Invalid Product ID' );
        }

        // Test API connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Get remote data
        $api_data = $this->api->products_specific_ID( $id );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
		}

        // OK process data
        $api_data = $this->api_has_data( $api_data );
        if ( $this->debug ) { eskimo_log( 'Product ID [' . $id . '] Data[' . gettype( $api_data ) . ']', 'rest' ); }

		// No API data or invalid
        if ( false === $api_data ) {
            return $this->api_error( 'No Results Returned' );
        }

        // Add Product SKU
        $api_data->sku = $this->get_skus_specific_ID( $api_data->eskimo_identifier, false );
		if ( is_wp_error( $api_data->sku ) ) { return $api_data->sku; }

        if ( $this->debug ) { eskimo_log( print_r( $api_data, true ), 'rest' ); }

        // Process Woocommerce Import
        return ( $import ) ? $this->wc->get_products_import_ID( $api_data, $path ) : $api_data;
	}

    /**
     * Get & update local product by ID
     *
     * @param   string  $prod_ref
     * @param   string  $trade_ref
     * @return  boolean
     */
    public function get_products_trade_ID( $prod_ref, $trade_ref) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': Prod[' . $prod_ref . '] Trade[' . $trade_ref . ']', 'rest' ); }

        // Test Product
        if ( empty( $prod_ref ) || empty( $trade_ref ) ) {
            return $this->api_error( 'Invalid Product ID' );
        }

        // Process Woocommerce Import
        return $this->wc->get_products_trade_ID( $prod_ref, $trade_ref );
	}

    /**
     * Update remote API SKU stock
     *
     * @param   string  		$path_id  
     * @param   string  		$prod_id  
     * @return  object|array
     */
    public function get_products_stock( $path, $prod_id ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': Product Stock: Path[' . $path . '] ID[' . $prod_id . ']', 'rest' ); }

        // Test Product
        if ( empty( $prod_id ) ) {
            return $this->api_error( 'Invalid SKU IDs' );
        }

        // Process Woocommerce stock by product ID
        $api_opts = $this->wc->get_products_stock( $path, $prod_id ); 
        if ( is_wp_error( $api_opts ) ) { return $api_opts; }

		if ( $this->debug ) { eskimo_log( 'Stock [' . print_r( $api_opts, true ) . ']', 'cart' ); }

        // Test API connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Get remote data
        $api_data = ( $path === 'adjust' ) ? $this->api->stock_adjust( $api_opts ) : $this->api->stock_multi_adjust( $api_opts ); 

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // Return data
        return $api_data;
    }
	
    //----------------------------------------------
    // Woocommerce Category & Product WebID Export
    //----------------------------------------------

	/**
	 * Get Woocommerce categories and Update EskimoEPOS Web_IDs
	 *
	 * @return	object|array
	 */
	public function get_categories_web_ID() {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

		// Get Woocommerce categories
		$cat_web_ids = $this->wc->get_categories_web_ID();
		return ( empty( $cat_web_ids ) ) ? $this->api_error( 'No Product Categories Found' ) : $cat_web_ids;
	}

    /**
     * Get remote API categories for meta ID reset
	 *
     * @return  object|array
     */
    public function get_categories_meta_ID() {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

        // Test API connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }
        
        // Get remote data
        $api_data = $this->api->categories_all();

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_count = $this->api_count( $api_data );
        if ( $this->debug ) { eskimo_log( 'Category Count[' . $api_count . ']', 'rest' ); }

		// No API data
        if ( $api_count === 0 ) {
            return $this->api_error( 'No Results Returned' );
        }

        // Process Woocommerce Import 
        return $this->wc->get_categories_meta_ID( $api_data );
    }

    /**
     * Get remote API categories for Web_ID reset
	 *
     * @return  object|array
     */
    public function get_categories_cart_ID() {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

        // Test API connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }
        
        // Get remote data
        $api_data = $this->api->categories_all();

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_count = $this->api_count( $api_data );
        if ( $this->debug ) { eskimo_log( 'Category Count[' . $api_count . ']', 'rest' ); }

		// No API data
        if ( $api_count === 0 ) {
            return $this->api_error( 'No Results Returned' );
        }

        // Process Woocommerce Import 
        return $this->wc->get_categories_cart_ID( $api_data );
    }

    /**
     * Update EskimoEPOS category Web_ID Update
     *
     * @param   array   $api_data
     * @return  boolean
     */
    public function get_categories_update_cart_ID( $api_data ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

        // Validate Options
        if ( empty( $api_data ) ) {
            return $this->api_error( 'No Category API Data To Process' );
        }

        // Test API connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Get remote data
        $api_response = $this->api->categories_update_cart_ID( $api_data );

        // Validate API data
        if ( false === $api_response ) {
            return $this->api_rest_error();
        }

		// OK process data
        $api_has_status = $this->api_has_status( $api_response );
        if ( $this->debug ) { eskimo_log( 'EPOS Categories UPD Status [' . $api_response . '][' . (int)$api_has_status . ']', 'rest' ); }

		// No API data or invalid
        if ( false === $api_has_status ) {
            return $this->api_error( 'Bad Process Update' );
        }

        // Default OK
        return true;
	}

	/**
	 * Get Woocommerce products and Update EskimoEPOS Web_IDs
	 *
	 * @return	object|array
	 */
	public function get_products_web_ID() {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

		// Get Woocommerce categories
		$prod_web_ids = $this->wc->get_products_web_ID();
		return ( empty( $prod_web_ids ) ) ? $this->api_error( 'No Products Found' ) : $prod_web_ids;
	}

    /**
     * Get remote API products for Web_ID update
     * 
     * @param   integer $start      default 1  
     * @param   integer $records    default 250
     * @return  boolean
     */
    public function get_products_cart_ID( $start = 1, $records = 250 ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': Start[' . $start . '] Records[' . $records . ']', 'rest' ); }

        // Sensible defaults
        $api_defaults = [
            'StartPosition' => 1,
            'RecordCount'   => 250
        ];

        // Pre-Sanitize
        $start   = absint( $start );
        $records = absint( $records );

        // Test API connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Validate Opts
        $api_opts = [];
        $api_opts['StartPosition']  = ( $start === 0 ) ? $api_defaults['StartPosition'] : $start;
        $api_opts['RecordCount']    = ( $records === 0 || $records > 250 ) ? $api_defaults['RecordCount'] : $records;
        if ( $this->debug ) { eskimo_log( 'Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . ']', 'rest' ); }

        // Batched Results
        $results = [];

        // Get remote data
        $api_data = $this->api->products_all( $api_opts );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_count = $this->api_count( $api_data );
        if ( $this->debug ) { eskimo_log( 'Products Count[' . $api_count . '] Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . ']', 'rest' ); }

		// No API data
        if ( $api_count === 0 ) {
            return $this->api_error( 'No Results Returned' );
        }

        // Process Woocommerce Import
        return $this->wc->get_products_cart_ID( $api_data );
    }

    /**
     * Update EskimoEPOS product WebIDs
     *
     * @param   array   $api_data
     * @return  boolean
     */
    public function get_products_update_cart_ID( $api_data ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

        // Validate Options
        if ( empty( $api_data ) ) {
            return $this->api_error( 'Invalid Product API Options' );
        }

        // Test API connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Get remote data
        $api_response = $this->api->products_update_cart_ID( $api_data );

        // Validate API data
        if ( false === $api_response ) {
            return $this->api_rest_error();
        }

		// OK process data
        $api_has_status = $this->api_has_status( $api_response );
        if ( $this->debug ) { eskimo_log( 'EPOS Product UPD Status [' . $api_response . '][' . (int)$api_has_status . ']', 'rest' ); }

		// No API data or invalid
        if ( false === $api_has_status ) {
            return $this->api_error( 'Bad Process Update' );
        }

        // Default OK
        return true;
    }

    //----------------------------------------------
    // Woocommerce Customer Import & Export
    //----------------------------------------------

    /**
     * Get remote API customer data
     *
	 * @param   string			$id
	 * @param	boolean			$import	default false
     * @return  object|array
     */
    public function get_customers_specific_ID( $id, $import = false ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . ']', 'rest' ); }

        // Test Options
        if ( empty( $id ) ) {
            return $this->api_error( 'Invalid Customer ID' );
        }

        // Test API connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
		}

		// Final sanity
		$id = sanitize_key( $id );

        // Get remote data
        $api_data = $this->api->customers_specific_ID( $id );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_data = $this->api_has_data( $api_data );
        if ( $this->debug ) { eskimo_log( 'Customer ID [' . $id . '] Data[' . gettype( $api_data ) . ']', 'rest' ); }

		// No API data or invalid
        if ( false === $api_data ) {
            return $this->api_error( 'No Results Returned' );
        }

        // Process user update
        return ( true === $import ) ? $this->wc->get_customers_specific_ID( $api_data, true ) : $api_data;
	}

    /**
     * Get remote API customer data
     *
     * @param   string  		$email
     * @return  object|array
     */
    public function get_customers_specific_email( $email ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': Email[' . $email . ']', 'rest' ); }

        // Test Options
        if ( empty( $email )) {
            return $this->api_error( 'Invalid Customer Email' );
        }

        // Test API connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
		}

		// Final sanity
		$email = sanitize_email( $email );

		// Set API opts
		$api_opts = [ 'EmailAddress' => $email ];
		
        // Get remote data
        $api_data = $this->api->customers_search( $api_opts );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_count = $this->api_count( $api_data );
		if ( $this->debug ) { eskimo_log( 'Customer Count[' . $api_count . ']', 'rest' ); }

        // Process return
        return ( $api_count > 0 ) ? true : false;
    }

    /**
     * Insert WC user to EskimoEPOS
     *
     * @param   string  		$id
     * @return  object|array
     */
    public function get_customers_insert( $id ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . ']', 'rest' ); }

		// Final sanity
		$id = absint( $id );
		if ( $id === 0 ) {
            return $this->api_error( 'Invalid Customer ID[' . $id . ']' );
        }

        // Validate Customer Data
		$api_opts = $this->wc->get_customers_insert_ID( $id );
        if ( is_wp_error( $api_opts ) ) { return $api_opts; }

		if ( $this->debug ) { eskimo_log( 'Customer Data: ' . print_r( $api_opts, true ), 'rest' ); }

        // Test connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }
		
        // Get remote data
        $api_data = $this->api->customers_insert( $api_opts );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_data = $this->api_has_data( $api_data );
        if ( $this->debug ) { eskimo_log( 'Cateory ID [' . $id . '] Data[' . gettype( $api_data ) . ']', 'rest' ); }

		// No API data or invalid
        if ( false === $api_data ) {
            return $this->api_error( 'No Results Returned' );
        }

        // Default OK
        return $this->wc->get_customers_epos_ID( $id, $api_data, true );
    }

    /**
     * Update WC user to EskimoEPOS
     *
     * @param   string  		$id
     * @return  object|array
     */
    public function get_customers_update( $id ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . ']', 'rest' ); }

   		// Final sanity
		$id = absint( $id );
		if ( $id === 0 ) {
            return $this->api_error( 'Invalid Customer ID[' . $id . ']' );
        }

        // Validate Customer Data
		$api_opts = $this->wc->get_customers_update_ID( $id );
        if ( is_wp_error( $api_opts ) ) { return $api_opts;	}

		if ( $this->debug ) { eskimo_log( 'Customer Data: ' . print_r( $api_opts, true ), 'rest' ); }

        // Test API connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }
		
        // Get remote data
        $api_data = $this->api->customers_update( $api_opts );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_data = $this->api_has_data( $api_data );
        if ( $this->debug ) { eskimo_log( 'Customer ID [' . $id . '] Data[' . gettype( $api_data ) . ']', 'rest' ); }

		// No API data or invalid
        if ( false === $api_data ) {
            return $this->api_error( 'No Results Returned' );
        }

        // Default OK
        return $this->wc->get_customers_epos_ID( $id, $api_data, false );
	}

    /**
     * Get remote API customer data
     *
     * @return  object|array
     */
    public function get_customers_titles() {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

        // Test API connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Get remote data
        $api_data = $this->api->customers_titles();

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_count = $this->api_count( $api_data );
		if ( $this->debug ) { eskimo_log( 'Titles Count[' . $api_count . ']', 'rest' ); }

        // Process return
        return $api_data;
    }

    //----------------------------------------------
    // Woocommerce Order Export
    //----------------------------------------------

    /**
	 * Import EskimoEPOS WebOrder into Woocommerce
     *
	 * @param   array   		$id
	 * @param	boolean			$import	default false
     * @return  object|array
     */
    public function get_orders_website_order( $id, $import = false ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . ']', 'rest' ); }

        // Test Options
        if ( empty( $id )) {
            return $this->api_error( 'Invalid Order ID' );
        }

        // Test API connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

		// Final sanity
		$id = sanitize_text_field( $id );

        // Get remote data
        $api_data = $this->api->orders_website_order( $id );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_data = $this->api_has_data( $api_data );
        if ( $this->debug ) { eskimo_log( 'Web Order: ID [' . $id . '] Data[' . gettype( $api_data ) . ']', 'rest' ); }

		// No API data or invalid
        if ( false === $api_data ) {
            return $this->api_error( 'No Results Returned' );
        }

        // Process order update
        return ( true === $import ) ? $this->wc->get_orders_website_order( $api_data, true ) : $api_data;
    }

    /**
     * Export Woocommerce order to EskimoEPOS WebOrder
     *
     * @param   array   		$order_id
     * @return  object|array
     */
    public function get_orders_insert( $order_id ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': Order ID #[' . $order_id . ']', 'rest' ); }

		// Sanitize
		$order_id = absint( $order_id );
	 	if ( $order_id === 0 ) { return $this->api_error( 'Invalid Order ID #[' . $order_id . ']' ); }

        // Get Order Data
		$api_opts = $this->wc->get_orders_insert_ID( $order_id );
		if ( is_wp_error( $api_opts ) ) { return $api_opts;	}

		if ( $this->debug ) { eskimo_log( 'Order Data: ' . print_r( $api_opts, true ), 'rest' ); }

        // Test API connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Insert order to EPOS
        $api_data = $this->api->orders_insert( $api_opts );
		if ( $this->debug ) { eskimo_log( 'API Data: ' . print_r( $api_data, true ), 'rest' ); }

		// Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_data = $this->api_has_data( $api_data );
        if ( $this->debug ) { eskimo_log( 'Web Order: ID [' . $order_id . '] Data[' . gettype( $api_data ) . ']', 'rest' ); }

		// No API data or invalid
        if ( false === $api_data ) {
            return $this->api_error( 'No Results Returned' );
        }

		// Check for API error message
		if ( true === $this->api_has_message( $api_data ) ) {

			// Construct error
			$message = ( property_exists( $api_data, 'message' ) ) ? $api_data->message : '';
			if ( property_exists( $api_data, 'ModelState' ) && property_exists( $api_data->ModelState, 'Error Message' ) ) {
				$error_message = $api_data->ModelState->{'Error Message'};
				$message .= ' ' . $error_message[0];
			}

            return $this->api_error( $message );
		}

        // Generate woocommerce meta data reference 
        return $this->wc->get_orders_epos_ID( $order_id, $api_data, true );
	}
	
    /**
     * Export Woocommerce return to EskimoEPOS WebOrder
     *
     * @param   array   		$order_id
     * @param   array   		$return_id
     * @return  object|array
     */
    public function get_orders_return( $order_id, $return_id ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': ID #[' . $order_id . '][' . $return_id . ']', 'rest' ); }

		// Sanitize
		$order_id 	= absint( $order_id );
		$return_id 	= absint( $return_id );
	 	if ( $order_id === 0 || $return_id === 0 ) { return $this->api_error( 'Invalid Order or Return ID #[' . $order_id . '][' . $return_id . ']' ); }

        // Get Order Data
		$api_opts = $this->wc->get_orders_return_ID( $order_id, $return_id );
		if ( is_wp_error( $api_opts ) ) { return $api_opts;	}

		if ( $this->debug ) { eskimo_log( 'Return Data: ' . print_r( $api_opts, true ), 'rest' ); }

        // Test API connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Insert order to EPOS
        $api_data = $this->api->orders_insert_return( $api_opts );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_data = $this->api_has_data( $api_data );

		// No API data or invalid
        if ( false === $api_data ) {
            return $this->api_error( 'No Results Returned' );
        }

        // Return API data 
        return $api_data;
	}

    /**
     * Retrieve EskimoEPOS Fulfilment Methods
     *
     * @return  object|array
     */
    public function get_orders_fulfilment_methods() {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

        // Test connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Insert order to EPOS
        $api_data = $this->api->orders_fulfilment_methods();

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_count = $this->api_count( $api_data );
		if ( $this->debug ) { eskimo_log( 'Order Methods Count[' . $api_count . ']', 'rest' ); }

		// No API data
        if ( $api_count === 0 ) {
            return $this->api_error( 'No Results Returned' );
        }

        return $api_data;
	}

    /**
     * Search EskimoEPOS Orders: Customer ID
     *
     * @param   array   		$id
     * @return  object|array
     */
    public function get_orders_search_ID( $id, $date_from = '', $date_to = '' ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . ']', 'rest' ); }

		// Sanitize
		$id = sanitize_text_field( $id );
	 	if ( empty( $id ) ) { return $this->api_error( 'Invalid Order ID' ); }

        // Test API connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
		}

		// Dates
		$date_from 	= ( empty( $date_from ) ) ? '' : sanitize_text_field( $date_from );
		$date_to 	= ( empty( $date_to ) ) ? '' : sanitize_text_field( $date_to );

		// Dates
		$dt 	= ( empty( $date_from ) ) ? new DateTime( '2000-01-01') : new DateTime( $date_from );
		$from 	= $dt->format('c');
		$to 	= ( empty( $date_to ) ) ? $dt->setTimestamp( time() )->format('c') : $dt->setTimestamp( strtotime( $date_to ) )->format('c');
		if ( $this->debug ) { eskimo_log( 'Customer ID[' . $id . '] From[' . $from . '] To[' . $to . ']', 'rest' ); }

		$api_opts = [ 
			'FromDate'					=> $from,
			'ToDate' 					=> $to,
			'OrderType' 				=> 2, //WebOrder
			'IncludeCustomerDetails' 	=> false,
			'IncludeProductDetails'	 	=> false,
			'CustomerID' 				=> $id			
		];

        // Insert order to EPOS
        $api_data = $this->api->orders_search( $api_opts );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_count = $this->api_count( $api_data );
		if ( $this->debug ) { eskimo_log( 'Orders Count[' . $api_count . ']', 'rest' ); }

		// No API data
        if ( $api_count === 0 ) {
            return $this->api_error( 'No Results Returned' );
        }

        return $api_data;
	}

    /**
     * Search EskimoEPOS Orders: Order Type
     *
     * @param   array   		$id
     * @return  object|array
     */
    public function get_orders_search_type( $id, $date_from = '', $date_to = ''  ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . ']', 'rest' ); }

		// Sanitize
		$id = absint( $id );
	 	if ( $id === 0 ) { return $this->api_error( 'Invalid Order ID' ); }

        // Test API connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
		}
		
		// Dates
		$date_from 	= ( empty( $date_from ) ) ? '' : sanitize_text_field( $date_from );
		$date_to 	= ( empty( $date_to ) ) ? '' : sanitize_text_field( $date_to );

		// Dates
		$dt 	= ( empty( $date_from ) ) ? new DateTime( '2000-01-01') : new DateTime( $date_from );
		$from 	= $dt->format('c');
		$to 	= ( empty( $date_to ) ) ? $dt->setTimestamp( time() )->format('c') : $dt->setTimestamp( strtotime( $date_to ) )->format('c');
		if ( $this->debug ) { eskimo_log( 'Customer ID[' . $id . '] From[' . $from . '] To[' . $to . ']', 'rest' ); }

		$api_opts = [ 
			'FromDate'  				=> $from,
			'ToDate' 					=> $to,
			'OrderType' 				=> $id,
			'IncludeCustomerDetails' 	=> false,
			'IncludeProductDetails'	 	=> false
		];

        // Insert order to EPOS
        $api_data = $this->api->orders_search( $api_opts );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_count = $this->api_count( $api_data );
		if ( $this->debug ) { eskimo_log( 'Orders Count[' . $api_count . ']', 'rest' ); }

		// No API data
        if ( $api_count === 0 ) {
            return $this->api_error( 'No Results Returned' );
        }

        return $api_data;
	}

    /**
     * Search EskimoEPOS Orders: Order Date
     *
     * @param   array   		$id
     * @return  object|array
     */
    public function get_orders_search_date( $route, $date_1 = '', $date_2 = '' ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': Route[' . $route . '] Date 1[' . $date_1 . '] Date 2[' . $date_2 . ']', 'rest' ); }

		// Valid Routes
		$routes = [ 'from', 'to', 'range', 'on' ];
		
		// Sanitize
		$route = sanitize_text_field( $route );
	 	if ( ! in_array( $route, $routes ) ) { return $this->api_error( 'Invalid Order Date Route[' . $route . ']' ); }

		// Dates
		$date_1 = sanitize_text_field( $date_1 );
		$date_2 = sanitize_text_field( $date_2 );
		$now	= current_time( 'timestamp' );

		// Validate
		if ( empty( $date_1 ) ) { return $this->api_error( 'Invalid Order Date 1' ); }
		if ( $route === 'range' && empty( $date_2 ) ) { return $this->api_error( 'Invalid Order Date 2' ); }

        // Test API connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

		// Dates
		switch ( $route ) {
			case 'from': // Use first date
				$dt 	= new DateTime( $date_1 );
				$from 	= $dt->format('c');
				$to 	= $dt->setTimestamp( $now )->format('c');
				break;
			case 'to':
				$dt 	= new DateTime( '2000-01-01' );
				$from 	= $dt->format('c');
				$to 	= $dt->setTimestamp( strtotime( $to ) )->format('c');
				break;
			case 'range':
				$dt 	= new DateTime( $date_1 );
				$from 	= $dt->format('c');
				$to 	= $dt->setTimestamp( strtotime( $date_2 ) )->format('c');
				break;
			case 'on':
				$dt 	= new DateTime( $date_1 );
				$from 	= $dt->format('c');
				$to 	= $dt->setTimestamp( strtotime( 'tomorrow' ) )->format('c');
				break;
			default:
	            return $this->api_rest_error();			
		}
        if ( $this->debug ) { eskimo_log( 'Route[' . ucfirst( $route ) . '] From[' . $from . '] To[' . $to . ']', 'rest' ); }

		$api_opts = [ 
			'FromDate'  				=> $from,
			'ToDate' 					=> $to,
			'OrderType'					=> 2, //WebOrder
			'IncludeCustomerDetails' 	=> false,
			'IncludeProductDetails'	 	=> false
		];

        // Insert order to EPOS
        $api_data = $this->api->orders_search( $api_opts );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_count = $this->api_count( $api_data );
		if ( $this->debug ) { eskimo_log( 'Orders Count[' . $api_count . ']', 'rest' ); }

		// No API data
        if ( $api_count === 0 ) {
            return $this->api_error( 'No Results Returned' );
        }

        return $api_data;
	}

    //----------------------------------------------
    // Woocommerce SKU Import
    //----------------------------------------------

    /**
     * Get remote API SKUs
     *
     * @param   string 	$path      	default 'all'  
     * @param   integer $start      default 1  
     * @param   integer $records    default 250
     * @param   integer $records    default 2000-01-01
     * @return  object|array
     */
    public function get_skus( $path = 'all', $start = 1, $records = 250 ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': Path [' . $path . '] Start[' . $start . '] Records[' . $records . ']', 'rest' ); }

        // Sensible defaults
        $api_defaults = [
            'StartPosition' => 1,
			'RecordMax'   	=> 2500,
			'RecordDefault'	=> 250
        ];

		// Set Time
		$dt = new DateTime('2000-01-01');		
        $TimeStampFrom = $dt->format('c');

		// Pre-Sanitize
        $start   = absint( $start );
        $records = absint( $records );

        // Test API connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Validate Opts
        $api_opts = [];
        $api_opts['StartPosition']  = ( $start === 0 ) ? $api_defaults['StartPosition'] : $start;
		$api_opts['RecordCount']    = ( $records === 0 || $records > $api_defaults['RecordMax'] ) ? $api_defaults['RecordDefault'] : $records;
		$api_opts['TimeStampFrom']	= '2000-01-01T00:00:00';
        if ( $this->debug ) { eskimo_log( 'Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . '] TimeStampFrom[' . $TimeStampFrom . ']', 'rest' ); }

        // Get remote data
        $api_data = $this->api->skus_all( $api_opts );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
		}

        // OK process data
        $api_count = $this->api_count( $api_data );
        if ( $this->debug ) { eskimo_log( 'SKU Count[' . $api_count . '] Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . ']', 'rest' ); }

		// No API data
        if ( $api_count === 0 ) {
            return $this->api_error( 'No Results Returned' );
        }

        // Process data
        if ( $this->debug ) { eskimo_log( print_r( $api_data, true ), 'rest' ); }

        // Process Woocommerce Import for Web_ID update
		return ( $path === 'batch' ) ? $this->wc->get_skus_all( $api_data, false ) : $this->wc->get_skus_all( $api_data );
	}
	
	/**
	 * Retrieve SKUs by modified date
	 *
	 * @param	string			$path
	 * @param	string			$route
	 * @param	integer			$modified
	 * @param	integer			$start
	 * @param	integer			$records
	 * @param	boolean			$import
     * @return  object|array
	 */
	public function get_skus_modified( $path, $route, $modified, $start, $records = 250, $import = false ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': Path[' . $path . '] Route[' . $route . '] Modified[' . $modified . '] Start[' . $start . '] Records[' . $records . '] Import[' . (int) $import . ']', 'rest' ); }

        // Sensible defaults
        $api_defaults = [
            'StartPosition' => 1,
			'RecordMax'   	=> 2500,
			'RecordDefault'	=> 250
        ];

		// Pre-Sanitize
		$path 		= sanitize_text_field( $path );
		$route 		= sanitize_text_field( $route );
		$modified	= absint( $modified );
        $start   	= absint( $start );
		$records 	= absint( $records );

        // Test API connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

		// Set stock adjustments
		$stock_adjust = $this->set_stock_adjust( $path );
		if ( is_wp_error( $stock_adjust ) ) { return $stock_adjust; }

		// Set modified from timeframe
		$timestamp_from = $this->get_modified_time( $route, $modified );
		if ( is_wp_error( $timestamp_from ) ) { return $timestamp_from; }

        // Validate Opts
        $api_opts = [];
        $api_opts['StartPosition']  = ( $start === 0 ) ? $api_defaults['StartPosition'] : $start;
		$api_opts['RecordCount']    = ( $records === 0 || $records > $api_defaults['RecordMax'] ) ? $api_defaults['RecordDefault'] : $records;
		$api_opts['TimeStampFrom']	= $timestamp_from;				
		if ( true === $stock_adjust ) { $api_opts['IncludeStockAdjustments'] = true; }
        if ( $this->debug ) { eskimo_log( 'Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . '] TimeStamp[' . $api_opts['TimeStampFrom'] . '] StockAdjust[' . (int) $stock_adjust . ']', 'rest' ); }

        // Get remote data
        $api_data = $this->api->skus_all( $api_opts );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
		}

        // OK process data
        $api_count = $this->api_count( $api_data );
        if ( $this->debug ) { eskimo_log( 'SKU Count[' . $api_count . '] Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . ']', 'rest' ); }

		// No API data
        if ( $api_count === 0 ) {
            return $this->api_error( 'No Results Returned' );
        }

		// Process Woocommerce SKUs
		$api_products = $this->wc->get_skus_all( $api_data, false );

		// No import	
		return ( true === $import ) ? $this->get_skus_product_import( $api_products ) : $api_products;
	}

    /**
     * Get remote API SKUs
     *
     * @param   integer $start      default 1  
     * @param   integer $records    default 250
     * @param   integer $records    default 2000-01-01
     * @return  object|array
     */
    public function get_skus_orphan( $start = 1, $records = 250 ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': Start[' . $start . '] Records[' . $records . ']', 'rest' ); }

        // Sensible defaults
        $api_defaults = [
            'StartPosition' => 1,
			'RecordMax'   	=> 1000,
			'RecordDefault'	=> 250
        ];

		// Set Time
		$dt = new DateTime('2000-01-01');		
        $TimeStampFrom = $dt->format('c');

		// Pre-Sanitize
        $start   = absint( $start );
        $records = absint( $records );

        // Test API connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Validate Opts
        $api_opts = [];
        $api_opts['StartPosition']  = ( $start === 0 ) ? $api_defaults['StartPosition'] : $start;
		$api_opts['RecordCount']    = ( $records === 0 || $records > $api_defaults['RecordMax'] ) ? $api_defaults['RecordDefault'] : $records;
		$api_opts['TimeStampFrom']	= '2000-01-01T00:00:00';
        if ( $this->debug ) { eskimo_log( 'Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . '] TimeStampFrom[' . $TimeStampFrom . ']', 'rest' ); }

        // Get remote data
        $api_data = $this->api->skus_all( $api_opts );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
		}

        // OK process data
        $api_count = $this->api_count( $api_data );
        if ( $this->debug ) { eskimo_log( 'SKU Count[' . $api_count . '] Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . ']', 'rest' ); }

		// No API data
        if ( $api_count === 0 ) {
            return $this->api_error( 'No Results Returned' );
        }

        // Process data
        if ( $this->debug ) { eskimo_log( print_r( $api_data, true ), 'rest' ); }

        // Process Woocommerce Import for Web_ID update, no import signal
		return $this->wc->get_skus_orphan( $api_data );
	}
	
	/**
	 * Import SKUs by product
	 *
	 * @param 	array 			$api_products
     * @return  object|array
	 */	
	protected function get_skus_product_import( $api_products ) {
		if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

		// Get unique products
		$products = [];
		foreach ( $api_products as $k=>$r ) {	
			$products[] = $r['Eskimo_Product_Identifier'];
		}
		$products = array_values( array_unique( $products, SORT_STRING ) );
		if ( $this->debug ) { eskimo_log( print_r( $products, true ), 'rest' ); }

		$results = [];
		foreach ( $products as $k=>$product ) {
			$results[] = $this->get_products_import_ID( $product, 'adjust' );
			break;
		}

		// Ok
		return $results;
	}

    /**
     * Get remote API SKU by product ID
     *
     * @param   string  		$id
     * @return  object|array
     */
    public function get_skus_specific_code( $id ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': SKU ID[' . $id . ']', 'rest' ); }

        // Test Product
        if ( empty( $id ) ) {
            return $this->api_error( 'Invalid SKU ID' );
        }

        // Test API connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Get remote data
        $api_data = $this->api->skus_specific_code( $id );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_count = $this->api_count( $api_data );
		if ( $this->debug ) { eskimo_log( 'Product SKU Count[' . $api_count . ']', 'rest' ); }

		// No API data
        if ( $api_count === 0 ) {
            return $this->api_error( 'No Results Returned' );
        }

        // Process Woocommerce Import
        return $this->wc->get_skus_specific_code( $api_data );
    }

    /**
     * Get remote API SKUs by ID
     *
     * @param   string  		$id
     * @param   integer 		$import default true
     * @return  object|array
     */
    public function get_skus_specific_ID( $id, $import = true ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . '] Import[' . (int) $import . ']', 'rest' ); }

        // Test SKU
        if ( empty( $id ) ) {
            return $this->api_error( 'Invalid SKU ID' );
		}

		// Final sanity
		$id = sanitize_text_field( $id );

        // Test API connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Get remote data
        $api_data = $this->api->skus_specific_ID( $id );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_count = $this->api_count( $api_data );
        if ( $this->debug ) { eskimo_log( 'Product SKU Count[' . $api_count . ']', 'rest' ); }

		// No API data
        if ( $api_count === 0 ) {
            return $this->api_error( 'No Results Returned' );
		}
		
        // Process Woocommerce Import
        return ( $import ) ? $this->wc->get_skus_specific_ID( $api_data ) : $api_data;
	}

    /**
     * Get remote API SKUs by ID
     *
     * @param   string  		$prod_id  
     * @return  object|array
     */
    public function get_skus_product_ID( $prod_id ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': Prod ID[' . $prod_id . ']', 'rest' ); }

        // Test Product
        if ( empty( $prod_id ) ) {
            return $this->api_error( 'Invalid Product ID' );
        }

        // Test API connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Get remote data
        $api_data = $this->api->sku_specific_ID( $prod_id );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_count = $this->api_count( $api_data );
        if ( $this->debug ) { eskimo_log( 'SKU Count[' . $api_count . ']', 'rest' ); }
        
        // Process Woocommerce Import
        return ( $import ) ? $this->wc->get_skus_product_ID( $api_data ) : $api_data;
    }

    //----------------------------------------------
    // Woocommerce Product Images
    //----------------------------------------------

    /**
     * Get remote API product image links
	 * 
	 * @param	integer			$start
	 * @param	integer			$records
     * @return  object|array
     */
    public function get_image_links_all( $start = 1, $records = 100) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': Start[' . $start . '] Records[' . $records . ']', 'rest' ); }

        // Sensible defaults
        $api_defaults = [
            'StartPosition' => 1,
            'RecordCount'   => 100
        ];

        // Pre-Sanitize
        $start   = absint( $start );
        $records = absint( $records );

        // Test API connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Validate Opts
        $api_opts = [];
        $api_opts['StartPosition']  = ( $start === 0 ) ? $api_defaults['StartPosition'] : $start;
        $api_opts['RecordCount']    = ( $records === 0 || $records > 100 ) ? $api_defaults['RecordCount'] : $records;
        if ( $this->debug ) { eskimo_log( 'Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . ']', 'rest' ); }

        // Get remote data
        $api_data = $this->api->image_links_all( $api_opts );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_count = $this->api_count( $api_data );
		if ( $this->debug ) { eskimo_log( 'Image Links Count[' . $api_count . ']', 'rest' );	}

		// No API data
        if ( $api_count === 0 ) {
            return $this->api_error( 'No Results Returned' );
        }

        // Process Woocommerce Import
        return $this->wc->get_image_links_all( $api_data );
    }

    /**
     * Get remote API product images
     * 
	 * @param	integer			$start
	 * @param	integer			$records
     * @return  object|array
     */
    public function get_images_all( $start = 1, $records = 100 ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': Start[' . $start . '] Records[' . $records . ']', 'rest' ); }

        // Sensible defaults
        $api_defaults = [
            'StartPosition' => 1,
            'RecordCount'   => 100
        ];

        // Pre-Sanitize
        $start   = absint( $start );
        $records = absint( $records );

        // Test API connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Validate Opts
        $api_opts = [];
        $api_opts['StartPosition']  = ( $start === 0 ) ? $api_defaults['StartPosition'] : $start;
        $api_opts['RecordCount']    = ( $records === 0 || $records > 100 ) ? $api_defaults['RecordCount'] : $records;
        if ( $this->debug ) { eskimo_log( 'Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . ']', 'rest' ); }


        // Get remote data
        $api_data = $this->api->images_all( $api_opts );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_count = $this->api_count( $api_data );
		if ( $this->debug ) { eskimo_log( 'Image Links Count[' . $api_count . ']', 'rest' ); }

		// No API data
        if ( $api_count === 0 ) {
            return $this->api_error( 'No Results Returned' );
        }

        // Process Woocommerce Import
        return $this->wc->get_images_all( $api_data );
    }

    //----------------------------------------------
    // Woocommerce Miscellaneous ImpEx
    //----------------------------------------------

    /**
     * Get remote API Tax Codes optionally by ID
     *
     * @param   string  		$id
     * @return  object|array
     */
    public function get_tax_codes( $id ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . ']', 'rest' ); }

        // Get remote data
        $api_data = ( empty( $id ) ) ? $this->api->tax_codes_all() : $this->api->tax_codes_specificID( $id );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_count = $this->api_count( $api_data );
		if ( $this->debug ) { eskimo_log( 'Tax Code Count[' . $api_count . ']', 'rest' ); }

		// No API data
        if ( $api_count === 0 ) {
            return $this->api_error( 'No Results Returned' );
        }

        // Process Woocommerce Import
        return $this->wc->get_tax_codes( $api_data );
    }

    /**
     * Get remote API shops
     *
     * @return  object|array
     */
    public function get_shops_all() {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

        // Test API connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Get remote data
        $api_data = $this->api->shops_all();

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_count = $this->api_count( $api_data );
		if ( $this->debug ) { eskimo_log( 'Shopss Count[' . $api_count . ']', 'rest' ); }

		// No API data
        if ( $api_count === 0 ) {
            return $this->api_error( 'No Results Returned' );
        }

        // Process Woocommerce Import
        return $this->wc->get_shops_all( $api_data );
    }

    /**
     * Get remote API product images
     *
     * @param   string  		$id
     * @return  object|array
     */
    public function get_shops_specific_ID( $id ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . ']', 'rest' ); }

        // Test Options
        if ( empty( $id ) ) {
            return $this->api_error( 'Invalid Shops ID' );
        }

        // Test API connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Get remote data
        $api_data = $this->api->shops_specific_ID();

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_count = $this->api_count( $api_data );
		if ( $this->debug ) { eskimo_log( 'Shops Count[' . $api_count . ']', 'rest' );	}

		// No API data
        if ( $api_count === 0 ) {
            return $this->api_error( 'No Results Returned' );
        }

        // Process Woocommerce Import
        return $this->wc->get_shops_specific_ID( $api_data );
    }

    //----------------------------------------------
    // Helper Functions
    //----------------------------------------------

	/**
	 * Stock adjustments at SKU level
	 *
	 * @param	string			$path
     * @return  object|array
	 */
	protected function set_stock_adjust( $path ) {

		// Valid paths
		$paths = [ 'all', 'stock', 'price' ];

		// Validate
		if ( ! in_array( $path, $paths ) ) { return $this->api_error( 'Invalid Path[' . $path . ']' ); }

		// Stock Adjust?
		return ( $path === 'stock' || $path === 'all' ) ? true : false;
	}

	/**
	 * Generate the modified from time
	 *
	 * @param	string			$route
	 * @param	integer			$modified
     * @return  object|array
	 */
	protected function get_modified_time( $route, $modified ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': Route[' . $route . '] Modified[' . $modified . ']', 'rest' ); }
		
		// Valid routes
		$routes = [ 'seconds', 'minutes', 'hours', 'days', 'weeks', 'months', 'timestamp' ];

		// Test routes
		if ( ! in_array( $route, $routes ) ) { 
            return $this->api_error( 'Invalid Route[' . $route . ']' );
		}

		// Set modified from timeframe
		$dt = new DateTime;
		$dt->setTimestamp( current_time( 'timestamp' ) );
		switch( $route ) {
			case 'seconds':
				$date_interval = 'PT' . $modified . 'S';
				$dt->sub( new DateInterval( $date_interval ) );
				break;
			case 'minutes':
				$date_interval = 'PT' . $modified . 'M';
				$dt->sub( new DateInterval( $date_interval ) );
				break;
			case 'hours':
				$date_interval = 'PT' . $modified . 'H';
				$dt->sub( new DateInterval( $date_interval ) );
				break;
			case 'days':
				$date_interval = 'P' . $modified . 'D';
				$dt->sub( new DateInterval( $date_interval ) );
				break;
			case 'weeks':
				$date_interval = 'P' . $modified . 'W';
				$dt->sub( new DateInterval( $date_interval ) );
				break;
			case 'months':
				$date_interval = 'P' . $modified . 'M';
				$dt->sub( new DateInterval( $date_interval ) );
				break;
			case 'timestamp':
				$dt->setTimestamp( $modified ); 
				break;
		}

		return $dt->format( 'c' );
	}

	/**
	 * Generate the created datetime
	 *
	 * @param	string			$route
	 * @param	integer			$created
     * @return  object|array
	 */
	protected function get_created_time( $route, $created ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': Route[' . $route . '] Created[' . $created . ']', 'rest' ); }
		
		// Valid routes
		$routes = [ 'hours', 'days', 'weeks', 'months', 'timestamp' ];

		// Test routes
		if ( ! in_array( $route, $routes ) ) { 
            return $this->api_error( 'Invalid Route[' . $route . ']' );
		}

		// Set modified from timeframe
		$dt = new DateTime;
		$dt->setTimestamp( current_time( 'timestamp' ) );
		switch( $route ) {
			case 'hours':
				$date_interval = 'PT' . $created . 'H';
				$dt->sub( new DateInterval( $date_interval ) );
				break;
			case 'days':
				$date_interval = 'P' . $created . 'D';
				$dt->sub( new DateInterval( $date_interval ) );
				break;
			case 'weeks':
				$date_interval = 'P' . $created . 'W';
				$dt->sub( new DateInterval( $date_interval ) );
				break;
			case 'months':
				$date_interval = 'P' . $created . 'M';
				$dt->sub( new DateInterval( $date_interval ) );
				break;
			case 'timestamp':
				$dt->setTimestamp( $created ); 
				break;
		}

		return $dt->format( 'Y-m-d H:i:s' );
	}
	
    //----------------------------------------------
    // API Functions
    //----------------------------------------------

	/**
	 * Test API return
	 *
	 * @param	string	$api_data
	 * @return 	boolean|object
	 */	
	private function api_has_data( $api_data ) {
		return ( empty( $api_data ) || ! is_object( $api_data ) ) ? false : $api_data; 
	}

	/**
	 * Test API return
	 *
	 * @param	object	$api_data
	 * @return 	boolean
	 */	
	private function api_has_status( $api_status ) {
		return ( $api_status === 200 ) ? true : false;
	}

	/**
	 * Check if the returned API call has an error message
	 * - Test for the message property
	 *
	 * @param	object	$api_data
	 * @return 	boolean
	 */
	private function api_has_message( $api_data ) {
		return property_exists( $api_data, 'Message' );
	}

	/**
	 * Count an object
	 *
	 * @param 	object|array $api_data
	 * @return 	integer
	 */
	private function api_count( $api_data ) {

		// first check
		if ( empty( $api_data ) ) { return 0; }

		// just do a quick count
		return ( is_array( $api_data ) ) ? count( $api_data ) : count( (array) $api_data );
	}

    //----------------------------------------------
    // API Error
    //----------------------------------------------

    /**
     * Log API Error
     *
	 * @param   string  $error
     * @return  object
     */
    protected function api_error( $error ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': Error[' . $error . ']', 'rest' ); }
		return new WP_Error( 'data', $error );
    }

    /**
	 * Log API Connection Error
	 * 
     * @return  object
     */
    protected function api_connect_error() {
		if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': ' . __( 'API Error: Could Not Connect To API', 'eskimo' ), 'rest' ); }
		return new WP_Error( 'api', __( 'API Error: Could Not Connect To API', 'eskimo' ) );
    }

    /**
     * Log API REST Process Error
	 * 
     * @return  object
	 */
    protected function api_rest_error() {
		if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': ' . __( 'API Error: Could Not Retrieve REST data from API', 'eskimo' ), 'rest' ); }
		return new WP_Error( 'rest', __( 'API Error: Could Not Retrieve REST data from API', 'eskimo' ) ); 
    }
}
