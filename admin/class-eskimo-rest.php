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
	 * @param   string    $eskimo     The name of this plugin
	 * @param   string    $version    The version of this plugin
	 * @param   string    $version    Plugin debugging mode, default false
	 */
	public function __construct( Eskimo_API $api, $eskimo, $version, $debug = false ) {
        if ( $debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        $this->api      = $api;
        $this->wc       = new Eskimo_WC( $eskimo, $version, $debug );
		$this->eskimo   = $eskimo;
		$this->version  = $version;
		$this->debug    = $debug;
    	$this->base_dir	= plugin_dir_url( __FILE__ ); 
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
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Test connection
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
		if ( $this->debug ) { error_log( 'Category Count[' . $api_count . ']' ); }

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
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . ']' ); }

        // Validate Category
        if ( empty( $id ) ) {
            return $this->api_error( 'Invalid Category ID' );
        }

        // Test connection
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
        if ( $this->debug ) { error_log( 'Cateory ID [' . $id . '] Data[' . gettype( $api_data ) . ']' ); }

		// No API data or invalid
        if ( false === $api_data ) {
            return $this->api_error( 'No Results Returned' );
        }

        if ( $this->debug ) { error_log( 'Cat[' . print_r( $api_data, true ) . ']' ); }

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
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . ']' ); }

        // Validate Category
        if ( empty( $id ) ) {
            return $this->api_error( 'Invalid Category Parent ID' );
        }

        // Test connection
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
        if ( $this->debug ) { error_log( 'Child Category Count[' . $api_count . ']' ); }

		// No API data
        if ( $api_count === 0 ) {
            return $this->api_error( 'No Results Returned' );
        }

        // Process Woocommerce Import
        return $this->wc->get_categories_child_categories_ID( $api_data );
    }

    //----------------------------------------------
    // Woocommerce Category Products
    //----------------------------------------------

    /**
     * Get remote API products by category
     * - Requires 2 parameters: StartPosition & RecordCount
     * - No Import
     *
     * @param   integer $start      default 1  
     * @param   integer $records    default 25
     * @return  boolean
     */
    public function get_category_products_all( $start = 1, $records = 25 ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': Start[' . $start . '] Records[' . $records . ']' ); }

        // Sensible defaults
        $api_defaults = [
            'StartPosition' => 1,
            'RecordCount'   => 25
        ];

        // Pre-Sanitize
        $start   = absint( $start );
        $records = absint( $records );

        // Validate options & set a sensible batch range
        $api_opts['StartPosition']  = ( $start === 0 ) ? $api_defaults['StartPosition'] : $start;
        $api_opts['RecordCount']    = ( $records === 0 || $records > 25 ) ? $api_defaults['RecordCount'] : $records;
        if ( $this->debug ) { error_log( 'Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . ']' ); }
                    
        // Test connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Batched Results
        $results = [];
        
        // Get remote data
        $api_data = $this->api->category_products_all( $api_opts );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
		}

        // OK process data
        $api_count = $this->api_count( $api_data );
        if ( $this->debug ) { error_log( 'Category Product Count[' . $api_count . '] Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . ']' ); }
            
		// No API data
        if ( $api_count === 0 ) {
            return $this->api_error( 'No Results Returned' );
        }

        if ( $this->debug ) { error_log( print_r( $api_data, true ) ); }

        // Return data set
        return $api_data;
    }

    /**
     * Get remote API category by ID
     * 
     * @param   string  $id
     * @return  boolean
     */
    public function get_category_products_specific_category( $id ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . ']' ); }

        // Test Category
        if ( empty( $id ) ) {
            return $this->api_error( 'Invalid Category ID' );
        }

        // Test connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Get remote data
        $api_data = $this->api->category_products_specific_category( $id );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
		}

        // OK process data
        $api_count = $this->api_count( $api_data );
        if ( $this->debug ) { error_log( 'Category Product Count[' . $api_count . ']' ); }

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
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': Start[' . $start . '] Records[' . $records . ']' ); }

        // Sensible defaults
        $api_defaults = [
            'StartPosition' => 1,
            'RecordCount'   => 25
        ];

        // Pre-Sanitize
        $start   = absint( $start );
        $records = absint( $records );

        // Test connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Validate Opts
        $api_opts = [];
        $api_opts['StartPosition']  = ( $start === 0 ) ? $api_defaults['StartPosition'] : $start;
        $api_opts['RecordCount']    = ( $records === 0 || $records > 50 ) ? $api_defaults['RecordCount'] : $records;
        if ( $this->debug ) { error_log( 'Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . ']' ); }

        // Get remote data
        $api_data = $this->api->products_all( $api_opts );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
		}

        // OK process data
        $api_count = $this->api_count( $api_data );
        if ( $this->debug ) { error_log( 'Products Count[' . $api_count . '] Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . ']' ); }

		// No API data
        if ( $api_count === 0 ) {
            return $this->api_error( 'No Results Returned' );
        }

        // Get products data per batch item & add product SKU
        foreach ( $api_data as $k=>$v ) {
            $v->sku = $this->get_skus_specific_ID( $v->eskimo_identifier, false );
        }

        // Process data
        if ( $this->debug ) { error_log( print_r( $api_data, true ) ); }

        // Process Woocommerce Import for Web_ID update
		return $this->wc->get_products_all( $api_data );
	}

    /**
     * Get remote API products
     * 
     * @param   string  $route 
     * @param   integer $created 
     * @return  boolean
     */
    public function get_products_new( $route, $created ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': Route[' . $route . '] Created[' . $created . ']' ); }

		// Pre-Sanitize
		$route 		= sanitize_text_field( $route );
		$created	= absint( $created );

     	// Test connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

		// Get modified from timeframe
		$timestamp_from = ( $route === 'all' ) ? 0 : $this->get_modified_time( $route, $created );
		if ( is_wp_error( $timestamp_from ) ) { return $timestamp_from; }

        // Validate Opts
        $api_opts = [];
        $api_opts['StartPosition']  = 1;
        $api_opts['RecordCount']    = 100;
        if ( $this->debug ) { error_log( 'Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . ']' ); }

		// Set timestamp		
		if ( $timestamp_from !== 0 ) {
			$api_opts['TimeStampFrom']	=  $timestamp_from;				
    	    if ( $this->debug ) { error_log( 'TimeStamp[' . $api_opts['TimeStampFrom'] . ']' ); }
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
            if ( $this->debug ) { error_log( 'Products Count[' . $api_count . '] Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . ']' ); }

            // Nothing to do here...
            if ( $api_count === 0 ) { break; }

            // Process data
            if ( $this->debug ) { error_log( print_r( $api_data, true ) ); }

            // Process Woocommerce Import
            $api_products = $this->wc->get_products_new( $api_data );

            // Update loop position
            $api_opts['StartPosition'] += $api_opts['RecordCount'];

			// Log products for update
			if ( is_wp_error( $api_products ) || empty( $api_products ) ) { continue; }
			foreach ( $api_products as $result ) { $results[] = $result; }

        } while ( true );

        // Return results for Web_ID update
        return $results;
	}

    /**
     * Get remote API products
     * 
     * @return  boolean
     */
    public function get_products_all() {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Test connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Validate Opts
        $api_opts = [];
        $api_opts['StartPosition']  = 1;
        $api_opts['RecordCount']    = 25;
        if ( $this->debug ) { error_log( 'Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . ']' ); }

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
            if ( $this->debug ) { error_log( 'Products Count[' . $api_count . '] Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . ']' ); }

            // Nothing to do here...
            if ( $api_count === 0 ) { break; }

            // Get products data per batch item & add product SKU
            foreach ( $api_data as $k=>$v ) {
                $v->sku = $this->get_skus_specific_ID( $v->eskimo_identifier, false );
            }

            // Process data
            if ( $this->debug ) { error_log( print_r( $api_data, true ) ); }

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
     * @param   integer $start      default 1  
     * @param   integer $records    default 25
     * @param   integer $records    default 2000-01-01
     * @return  boolean
     */
    public function get_products_modified( $route, $modified, $start = 1, $records = 25 ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': Route[' . $route . '] Modified[' . $modified . '] Start[' . $start . '] Records[' . $records . ']' ); }

        // Sensible defaults
        $api_defaults = [
            'StartPosition' => 1,
            'RecordCount'   => 100,
            'TimeStampFrom' => date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - 3600 )
        ];

		// Pre-Sanitize
		$route 		= sanitize_text_field( $route );
		$modified	= absint( $modified );
        $start   	= absint( $start );
        $records 	= absint( $records );

     	// Test connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

		// Get modified from timeframe
		$timestamp_from = $this->get_modified_time( $route, $modified );
		if ( is_wp_error( $timestamp_from ) ) { return $timestamp_from; }
		
        // Validate Opts
        $api_opts = [];
        $api_opts['StartPosition']  = ( $start === 0 ) ? $api_defaults['StartPosition'] : $start;
		$api_opts['RecordCount']    = ( $records === 0 || $records > 100 ) ? $api_defaults['RecordCount'] : $records;
		$api_opts['TimeStampFrom']	=  $timestamp_from;				
        if ( $this->debug ) { error_log( 'Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . '] TimeStamp[' . $api_opts['TimeStampFrom'] . ']' ); }

        // Get remote data
        $api_data = $this->api->products_all( $api_opts );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
		}

        // OK process data
        $api_count = $this->api_count( $api_data );
        if ( $this->debug ) { error_log( 'Products Count[' . $api_count . '] Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . '] TimeStamp [' . $api_opts['TimeStampFrom'] . ']' ); }

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
        if ( $this->debug ) { error_log( print_r( $api_products, true ) ); }

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
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . '] Import[' . (int) $import . ']' ); }

        // Test Product
        if ( empty( $id ) ) {
            return $this->api_error( 'Invalid Product ID' );
        }

        // Test connection
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
        if ( $this->debug ) { error_log( 'Product ID [' . $id . '] Data[' . gettype( $api_data ) . ']' ); }

		// No API data or invalid
        if ( false === $api_data ) {
            return $this->api_error( 'No Results Returned' );
        }

        // Add Product SKU
        $api_data->sku = $this->get_skus_specific_ID( $api_data->eskimo_identifier, false );
        if ( $this->debug ) { error_log( print_r( $api_data, true ) ); }

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
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . '] Path[' . $path . '] Import[' . (int) $import . ']' ); }

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

        // Test connection
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
        if ( $this->debug ) { error_log( 'Product ID [' . $id . '] Data[' . gettype( $api_data ) . ']' ); }

		// No API data or invalid
        if ( false === $api_data ) {
            return $this->api_error( 'No Results Returned' );
        }

        // Add Product SKU
        $api_data->sku = $this->get_skus_specific_ID( $api_data->eskimo_identifier, false );
        if ( $this->debug ) { error_log( print_r( $api_data, true ) ); }

        // Process Woocommerce Import
        return ( $import ) ? $this->wc->get_products_import_ID( $api_data, $path ) : $api_data;
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
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

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
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Test connection
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
        if ( $this->debug ) { error_log( 'Category Count[' . $api_count . ']' ); }

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
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Test connection
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
        if ( $this->debug ) { error_log( 'Category Count[' . $api_count . ']' ); }

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
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Validate Options
        if ( empty( $api_data ) ) {
            return $this->api_error( 'No Category API Data To Process' );
        }

        // Test connection
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
        if ( $this->debug ) { error_log( 'EPOS Categories UPD Status [' . $api_response . '][' . (int)$api_has_status . ']' ); }

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
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

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
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': Start[' . $start . '] Records[' . $records . ']' ); }

        // Sensible defaults
        $api_defaults = [
            'StartPosition' => 1,
            'RecordCount'   => 250
        ];

        // Pre-Sanitize
        $start   = absint( $start );
        $records = absint( $records );

        // Test connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Validate Opts
        $api_opts = [];
        $api_opts['StartPosition']  = ( $start === 0 ) ? $api_defaults['StartPosition'] : $start;
        $api_opts['RecordCount']    = ( $records === 0 || $records > 250 ) ? $api_defaults['RecordCount'] : $records;
        if ( $this->debug ) { error_log( 'Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . ']' ); }

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
        if ( $this->debug ) { error_log( 'Products Count[' . $api_count . '] Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . ']' ); }

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
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Validate Options
        if ( empty( $api_data ) ) {
            return $this->api_error( 'Invalid Product API Options' );
        }

        // Test connection
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
        if ( $this->debug ) { error_log( 'EPOS Product UPD Status [' . $api_response . '][' . (int)$api_has_status . ']' ); }

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
     * @return  object|array
     */
    public function get_customers_specific_ID( $id ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . ']' ); }

        // Test Options
        if ( empty( $id )) {
            return $this->api_error( 'Invalid Customer ID' );
        }

        // Test connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Get remote data
        $api_data = $this->api->customers_specific_ID( $id );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_data = $this->api_has_data( $api_data );
        if ( $this->debug ) { error_log( 'Customer ID [' . $id . '] Data[' . gettype( $api_data ) . ']' ); }

		// No API data or invalid
        if ( false === $api_data ) {
            return $this->api_error( 'No Results Returned' );
        }

        // Process user update
        return $this->wc->get_customers_specific_ID( $api_data, true );
	}

    /**
     * Get remote API customer data
     *
     * @param   string  		$email
     * @return  object|array
     */
    public function get_customers_specific_email( $email ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': Email[' . $email . ']' ); }

        // Test Options
        if ( empty( $email )) {
            return $this->api_error( 'Invalid Customer Email' );
        }

        // Test connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

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
		if ( $this->debug ) { error_log( 'Customer Count[' . $api_count . ']' ); }

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
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . ']' ); }

		// Test Options
		$id = absint( $id );
		if ( $id === 0 ) {
            return $this->api_error( 'Invalid Customer ID[' . $id . ']' );
        }

        // Validate Customer Data
		$api_opts = $this->wc->get_customers_insert_ID( $id );
        if ( is_wp_error( $api_opts ) ) { return $api_opts; }

		if ( $this->debug ) { error_log( 'Customer Data: ' . print_r( $api_opts, true ) ); }

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
        if ( $this->debug ) { error_log( 'Cateory ID [' . $id . '] Data[' . gettype( $api_data ) . ']' ); }

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
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . ']' ); }

   		// Test Options
		$id = absint( $id );
		if ( $id === 0 ) {
            return $this->api_error( 'Invalid Customer ID[' . $id . ']' );
        }

        // Validate Customer Data
		$api_opts = $this->wc->get_customers_update_ID( $id );
        if ( is_wp_error( $api_opts ) ) { return $api_opts;	}

		if ( $this->debug ) { error_log( 'Customer Data: ' . print_r( $api_opts, true ) ); }

        // Test connection
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
        if ( $this->debug ) { error_log( 'Customer ID [' . $id . '] Data[' . gettype( $api_data ) . ']' ); }

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
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Test connection
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
		if ( $this->debug ) { error_log( 'Titles Count[' . $api_count . ']' ); }

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
     * @return  object|array
     */
    public function get_orders_website_order( $id ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . ']' ); }

        // Test Options
        if ( empty( $id )) {
            return $this->api_error( 'Invalid Order ID' );
        }

        // Test connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Get remote data
        $api_data = $this->api->orders_website_order( $id );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_data = $this->api_has_data( $api_data );
        if ( $this->debug ) { error_log( 'Web Order: ID [' . $id . '] Data[' . gettype( $api_data ) . ']' ); }

		// No API data or invalid
        if ( false === $api_data ) {
            return $this->api_error( 'No Results Returned' );
        }

        // Process order update
        return $this->wc->get_orders_website_order( $api_data, true );
    }

    /**
     * Export Woocommerce order to EskimoEPOS WebOrder
     *
     * @param   array   		$id
     * @return  object|array
     */
    public function get_orders_insert( $id ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . ']' ); }

		// Sanitize
		$id = absint( $id );
	 	if ( $id === 0 ) { return $this->api_error( 'Invalid Order ID[' . $id . ']' ); }

        // Get Order Data
		$api_opts = $this->wc->get_orders_insert_ID( $id );
		if ( is_wp_error( $api_opts ) ) { return $api_opts;	}

		if ( $this->debug ) { error_log( 'Order Data: ' . print_r( $api_opts, true ) ); }

        // Test connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Insert order to EPOS
        $api_data = $this->api->orders_insert( $api_opts );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_data = $this->api_has_data( $api_data );
        if ( $this->debug ) { error_log( 'Web Order: ID [' . $id . '] Data[' . gettype( $api_data ) . ']' ); }

		// No API data or invalid
        if ( false === $api_data ) {
            return $this->api_error( 'No Results Returned' );
        }

        // Generate woocommerce meta data reference 
        return $this->wc->get_orders_epos_ID( $id, $api_data, true );
	}

    /**
     * Retrieve EskimoEPOS Fulfilment Methods
     *
     * @return  object|array
     */
    public function get_orders_fulfilment_methods() {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Test connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Insert order to EPOS
        $api_data = $this->api->orders_methods();

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_count = $this->api_count( $api_data );
		if ( $this->debug ) { error_log( 'Order Methods Count[' . $api_count . ']' ); }

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
    public function get_orders_search_ID( $id ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . ']' ); }

		// Sanitize
		$id = sanitize_text_field( $id );
	 	if ( empty( $id ) ) { return $this->api_error( 'Invalid Order ID' ); }

        // Test connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
		}

		// Dates
		$dt = new DateTime( '2000-01-01');
		$from = $dt->format('c');
		$dt->setTimestamp( time() );
		$to = $dt->format('c');

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
		if ( $this->debug ) { error_log( 'Orders Count[' . $api_count . ']' ); }

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
    public function get_orders_search_type( $id ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . ']' ); }

		// Sanitize
		$id = absint( $id );
	 	if ( $id === 0 ) { return $this->api_error( 'Invalid Order ID' ); }

        // Test connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
		}
		
		// Dates
		$now	= current_time( 'timestamp' );
		$dt 	= new DateTime( '2000-01-01');
		$from 	= $dt->format('c');
		$to 	= $dt->setTimestamp( $now )->format('c');

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
		if ( $this->debug ) { error_log( 'Orders Count[' . $api_count . ']' ); }

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
    public function get_orders_search_date( $route, $date_1, $date_2 ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . ']' ); }

		// Valid Routes
		$routes = [ 'from', 'to', 'range' ];
		
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

        // Test connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

		// Dates
		switch ( $route ) {
			case 'from':
				$dt 	= new DateTime( $from );
				$from 	= $dt->format('c');
				$to 	= $dt->setTimestamp( $now )->format('c');
				break;
			case 'to':
				$dt 	= new DateTime( '2000-01-01' );
				$from 	= $dt->format('c');
				$to 	= $dt->setTimestamp( strtotime( $to ) )->format('c');
				break;
			case 'range':
				$dt 	= new DateTime( $from );
				$from 	= $dt->format('c');
				$to 	= $dt->setTimestamp( strtotime( $to ) )->format('c');
				break;
		}
        if ( $this->debug ) { error_log( 'Route[' . $route . ' FromDate[' . $from . '] ToDate[' . $to . ']' ); }

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
		if ( $this->debug ) { error_log( 'Orders Count[' . $api_count . ']' ); }

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
     * @param   integer $start      default 1  
     * @param   integer $records    default 20
     * @param   integer $records    default 2000-01-01
     * @return  object|array
     */
    public function get_skus( $start = 1, $records = 100 ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': Start[' . $start . '] Records[' . $records . ']' ); }

        // Sensible defaults
        $api_defaults = [
            'StartPosition' => 1,
			'RecordCount'   => 100
        ];

		// Set Time
		$dt = new DateTime('2000-01-01');		
        $TimeStampFrom = $dt->format('c');

		// Pre-Sanitize
        $start   = absint( $start );
        $records = absint( $records );

        // Test connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Validate Opts
        $api_opts = [];
        $api_opts['StartPosition']  = ( $start === 0 ) ? $api_defaults['StartPosition'] : $start;
		$api_opts['RecordCount']    = ( $records === 0 || $records > 100 ) ? $api_defaults['RecordCount'] : $records;
		$api_opts['TimeStampFrom']	= '2000-01-01T00:00:00';
        if ( $this->debug ) { error_log( 'Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . '] TimeStampFrom[' . $TimeStampFrom . ']' ); }

        // Get remote data
        $api_data = $this->api->skus_all( $api_opts );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
		}

        // OK process data
        $api_count = $this->api_count( $api_data );
        if ( $this->debug ) { error_log( 'SKU Count[' . $api_count . '] Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . ']' ); }

		// No API data
        if ( $api_count === 0 ) {
            return $this->api_error( 'No Results Returned' );
        }

        // Process data
        if ( $this->debug ) { error_log( print_r( $api_data, true ) ); }

        // Process Woocommerce Import for Web_ID update
		return $this->wc->get_skus_all( $api_data );
	}

	/**
	 * Retrieve SKUs by modified date
	 *
	 * @param	string			$path
	 * @param	string			$route
	 * @param	integer			$modified
	 * @param	integer			$start
	 * @param	integer			$records
	 * @param	integer			$import
     * @return  object|array
	 */
	public function get_skus_modified( $path, $route, $modified, $start, $records, $import = 0 ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': Path[' . $path . '] Route[' . $route . '] Modified[' . $modified . '] Start[' . $start . '] Records[' . $records . ']' ); }

        // Sensible defaults
        $api_defaults = [
            'StartPosition' => 1,
            'RecordCount'   => 1000
        ];

		// Pre-Sanitize
		$path 		= sanitize_text_field( $path );
		$route 		= sanitize_text_field( $route );
		$modified	= absint( $modified );
        $start   	= absint( $start );
		$records 	= absint( $records );
		$import		= absint( $import );

     	// Test connection
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
		$api_opts['RecordCount']    = ( $records === 0 || $records > 1000 ) ? $api_defaults['RecordCount'] : $records;
		$api_opts['TimeStampFrom']	= $timestamp_from;				
		if ( true === $stock_adjust ) { $api_opts['IncludeStockAdjustments'] = true; }
        if ( $this->debug ) { error_log( 'Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . '] TimeStamp[' . $api_opts['TimeStampFrom'] . '] StockAdjust[' . (int) $stock_adjust . ']' ); }

        // Get remote data
        $api_data = $this->api->skus_all( $api_opts );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
		}

        // OK process data
        $api_count = $this->api_count( $api_data );
        if ( $this->debug ) { error_log( 'SKU Count[' . $api_count . '] Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . ']' ); }

		// No API data
        if ( $api_count === 0 ) {
            return $this->api_error( 'No Results Returned' );
        }

		// Process Woocommerce SKUs
		$api_products = $this->wc->get_skus_all( $api_data, false );

		// No import	
		return ( $import === 0 ) ? $api_products : $this->get_skus_product_import( $api_products );
	}

	/**
	 * Import SKUs by product
	 *
	 * @param 	array 			$api_products
     * @return  object|array
	 */	
	protected function get_skus_product_import( $api_products ) {
		if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

		// Get unique products
		$products = [];
		foreach ( $api_products as $k=>$r ) {	
			$products[] = $r['Eskimo_Product_Identifier'];
		}
		$products = array_values( array_unique( $products, SORT_STRING ) );
		if ( $this->debug ) { error_log( print_r( $products, true ) ); }

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
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': SKU ID[' . $id . ']' ); }

        // Test Product
        if ( empty( $id ) ) {
            return $this->api_error( 'Invalid SKU ID' );
        }

        // Test connection
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
		if ( $this->debug ) { error_log( 'Product SKU Count[' . $api_count . ']' ); }

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
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . '] Import[' . (int) $import . ']' ); }

        // Test SKU
        if ( empty( $id ) ) {
            return $this->api_error( 'Invalid SKU ID' );
        }

        // Test connection
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
        if ( $this->debug ) { error_log( 'Product SKU Count[' . $api_count . ']' ); }

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
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': Prod ID[' . $prod_id . ']' ); }

        // Test Product
        if ( empty( $prod_id ) ) {
            return $this->api_error( 'Invalid Product ID' );
        }

        // Test connection
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
        if ( $this->debug ) { error_log( 'SKU Count[' . $api_count . ']' ); }
        
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
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': Start[' . $start . '] Records[' . $records . ']' ); }

        // Sensible defaults
        $api_defaults = [
            'StartPosition' => 1,
            'RecordCount'   => 100
        ];

        // Pre-Sanitize
        $start   = absint( $start );
        $records = absint( $records );

        // Test connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Validate Opts
        $api_opts = [];
        $api_opts['StartPosition']  = ( $start === 0 ) ? $api_defaults['StartPosition'] : $start;
        $api_opts['RecordCount']    = ( $records === 0 || $records > 100 ) ? $api_defaults['RecordCount'] : $records;
        if ( $this->debug ) { error_log( 'Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . ']' ); }

        // Get remote data
        $api_data = $this->api->image_links_all( $api_opts );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_count = $this->api_count( $api_data );
		if ( $this->debug ) { error_log( 'Image Links Count[' . $api_count . ']' );	}

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
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': Start[' . $start . '] Records[' . $records . ']' ); }

        // Sensible defaults
        $api_defaults = [
            'StartPosition' => 1,
            'RecordCount'   => 100
        ];

        // Pre-Sanitize
        $start   = absint( $start );
        $records = absint( $records );

        // Test connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Validate Opts
        $api_opts = [];
        $api_opts['StartPosition']  = ( $start === 0 ) ? $api_defaults['StartPosition'] : $start;
        $api_opts['RecordCount']    = ( $records === 0 || $records > 100 ) ? $api_defaults['RecordCount'] : $records;
        if ( $this->debug ) { error_log( 'Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . ']' ); }


        // Get remote data
        $api_data = $this->api->images_all( $api_opts );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_count = $this->api_count( $api_data );
		if ( $this->debug ) { error_log( 'Image Links Count[' . $api_count . ']' );	}

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
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . ']' ); }

        // Get remote data
        $api_data = ( empty( $id ) ) ? $this->api->tax_codes_all() : $this->api->tax_codes_specificID( $id );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_count = $this->api_count( $api_data );
		if ( $this->debug ) { error_log( 'Tax Code Count[' . $api_count . ']' ); }

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
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Test connection
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
		if ( $this->debug ) { error_log( 'Shopss Count[' . $api_count . ']' ); }

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
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . ']' ); }

        // Test Options
        if ( empty( $id ) ) {
            return $this->api_error( 'Invalid Shops ID' );
        }

        // Test connection
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
		if ( $this->debug ) { error_log( 'Shops Count[' . $api_count . ']' );	}

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
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': Route[' . $route . '] Modified[' . $modified . ']' ); }
		
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
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': Route[' . $route . '] Created[' . $created . ']' ); }
		
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
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': Error[' . $error . ']' ); }
		return new WP_Error( 'data', $error );
    }

    /**
	 * Log API Connection Error
	 * 
     * @return  object
     */
    protected function api_connect_error() {
		if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': ' . __( 'API Error: Could Not Connect To API', 'eskimo' ) );	}
		return new WP_Error( 'api', __( 'API Error: Could Not Connect To API', 'eskimo' ) );
    }

    /**
     * Log API REST Process Error
	 * 
     * @return  object
	 */
    protected function api_rest_error() {
		if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': ' . __( 'API Error: Could Not Retrieve REST data from API', 'eskimo' ) ); }
		return new WP_Error( 'rest', __( 'API Error: Could Not Retrieve REST data from API', 'eskimo' ) ); 
    }
}
