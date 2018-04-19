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
class Eskimo_REST {

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
        if ( false === $api_data || empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_count = count( $api_data );
        if ( $this->debug ) { error_log( 'Category Count[' . $api_count . ']' ); }

        // Process Woocommerce Import 
        return $this->wc->get_categories_all( $api_data );
    }

    /**
     * Get remote API category by ID
     *
     * @param   string  $id default empty
     * @return  boolean
     */
    public function get_categories_specific_ID( $id = '' ) {
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
        if ( false === $api_data || empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_count = count( $api_data );
        if ( $this->debug ) { error_log( 'Category Count[' . $api_count . ']' ); }

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
        if ( false === $api_data || empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_count = count( $api_data );
        if ( $this->debug ) { error_log( 'Child Category Count[' . $api_count . ']' ); }

        // Process Woocommerce Import
        return $this->wc->get_categories_child_categories_ID( $api_data );
    }

    //----------------------------------------------
    // Woocommerce Category Product Import
    //----------------------------------------------

    /**
     * Get remote API products by category
     * - Requires 2 parameters: StartPosition & RecordCount
     * - Deprecated: Use get_products via /products
     *
     * @param   integer $start      default 1  
     * @param   integer $records    default 20
     * @return  boolean
     */
    public function get_category_products( $start = 1, $records = 20 ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': Start[' . $start . '] Records[' . $records . ']' ); }

        // Sensible defaults
        $api_defaults = [
            'StartPosition' => 1,
            'RecordCount'   => 20
        ];

        // Pre-Sanitize
        $start   = absint( $start );
        $records = absint( $records );

        // Validate options & set a sensible batch range
        $api_opts['StartPosition']  = ( $start > 0 ) ? $start : $api_defaults['StartPosition'];
        $api_opts['RecordCount']    = ( $records > 0 && $records <= 50 ) ? $records : $api_defaults['RecordCount'];
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
        if ( false === $api_data || empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_count = count( $api_data );
        if ( $this->debug ) { error_log( 'Category Product Count[' . $api_count . '] Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . ']' ); }
            
        // Nothing to do here...
        if ( $api_count === 0 ) { return; }

        // Get products data per batch item
        foreach ( $api_data as $k=>$v ) {

            // Add Product
            $api_product = $this->get_products_specific_ID( $v->eskimo_product_identifier, false );
            $v->product = ( $api_product !== false ) ? $api_product : [];                

            // Has SKU's
            $v->product->sku = $this->get_sku_specific_ID( $v->eskimo_product_identifier, false );
        }

        if ( $this->debug ) { error_log( print_r( $api_data, true ) ); }

        // Process Woocommerce Import
        $api_results = $this->wc->get_category_products_all( $api_data );

        // Log products for update
        if ( false !== $api_results && !empty( $api_results ) ) {
            foreach ( $api_results as $result ) { $results[] = $result; }
        }

        // Return results for Web_ID update
        return $results;
    }

    /**
     * Get remote API products by category
     * - Requires 2 parameters: StartPosition & RecordCount
     * - deprecated: use get_products_all via /products-all
     *
     * @param   integer $start      default 1  
     * @param   integer $records    default 20
     * @return  boolean
     */
    public function get_category_products_all( $start = 1, $records = 20 ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': Start[' . $start . '] Records[' . $records . ']' ); }

        // Sensible defaults
        $api_defaults = [
            'StartPosition' => 1,
            'RecordCount'   => 20
        ];

        // Pre-Sanitize
        $start   = absint( $start );
        $records = absint( $records );

        // Validate options & set a sensible batch range
        $api_opts['StartPosition']  = ( $start > 0 ) ? $start : $api_defaults['StartPosition'];
        $api_opts['RecordCount']    = ( $records > 0 && $records <= 50 ) ? $records : $api_defaults['RecordCount'];
        if ( $this->debug ) { error_log( 'Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . ']' ); }
                    
        // Test connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Batched Results
        $results = [];
        
        // Process products by batches until done...
        do {
            
            // Get remote data
            $api_data = $this->api->category_products_all( $api_opts );

            // Validate API data
            if ( false === $api_data || empty( $api_data ) ) {
                return $this->api_rest_error();
            }

            // OK process data
            $api_count = count( $api_data );
            if ( $this->debug ) { error_log( 'Category Product Count[' . $api_count . '] Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . ']' ); }
            
            // Nothing to do here...
            if ( $api_count === 0 ) { break; }

            // Get products data per batch item
            foreach ( $api_data as $k=>$v ) {
                // Add Product
                $api_product = $this->get_products_specific_ID( $v->eskimo_product_identifier, false );
                $v->product = ( $api_product !== false ) ? $api_product : [];                

                // Has SKU's
                $v->product->sku = $this->get_sku_specific_ID( $v->eskimo_product_identifier, false );
            }

            if ( $this->debug ) { error_log( print_r( $api_data, true ) ); }

            // Process Woocommerce Import
            $api_results = $this->wc->get_category_products_all( $api_data );

            // Log products for update
            if ( false !== $api_results && !empty( $api_results ) ) {
                foreach ( $api_results as $result ) { $results[] = $result; }
            }

            // Update loop position
            $api_opts['StartPosition'] += $api_opts['RecordCount'];

        } while ( $api_count > 0 );

        // Return results for Web_ID update
        return $results;
    }

    /**
     * Get remote API category by ID
     * - Deprecated: use get_product_specific_ID
     * 
     * @param   string  $id default ''
     * @return  boolean
     */
    public function get_category_products_specific_category( $id = '' ) {
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
        $api_count = count( $api_data );
        if ( $this->debug ) { error_log( 'Category Product Count[' . $api_count . ']' ); }

        // Process Woocommerce Import
        return $this->wc->get_category_products_specific_category( $api_data );
    }

    //----------------------------------------------
    // Woocommerce Product Import
    //----------------------------------------------

    /**
     * Get remote API products
     *
     * @param   integer $start      default 1  
     * @param   integer $records    default 20
     * @param   integer $records    default 2000-01-01
     * @return  boolean
     */
    public function get_products( $start = 1, $records = 20, $timestamp = '2000-01-01' ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': Start[' . $start . '] Records[' . $records . '] timestamp[' . $timestamp . ']' ); }

        // Sensible defaults
        $api_defaults = [
            'StartPosition' => 1,
            'RecordCount'   => 20,
            'TimestampFrom' => '2000-01-01'
        ];

        // Pre-Sanitize
        $start   = absint( $start );
        $records = absint( $records );
        $timestamp = trim( $timestamp );

        // Test connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Validate date format, if not the actual date
        $date_pattern = '^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$';

        // Validate Opts
        $api_opts = [];
        $api_opts['StartPosition']  = ( $start > 0 ) ? (int) $start : $api_defaults['StartPosition'];
        $api_opts['RecordCount']    = ( $records > 0 && $records <= 50) ? (int) $records : $api_defaults['RecordCount'];
        $api_opts['TimestampFrom']  = ( preg_match( '/' . $date_pattern . '/', $timestamp ) ) ? $timestamp . 'T00:00:00' : $api_defaults['TimestampFrom'] . 'T00:00:00';
        if ( $this->debug ) { error_log( 'Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . '] Timestamp[' . $api_opts['TimestampFrom'] . ']' ); }

        // Get remote data
        $api_data = $this->api->products_all( $api_opts );

        // Validate API data
        if ( false === $api_data || empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_count = count( $api_data );
        if ( $this->debug ) { error_log( 'Products Count[' . $api_count . '] Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . '] Timestamp [' . $api_opts['TimestampFrom'] . ']' ); }

        // Nothing to do here...
        if ( $api_count === 0 ) { return; }

        // Get products data per batch item & add product SKU
        foreach ( $api_data as $k=>$v ) {
            $v->sku = $this->get_sku_specific_ID( $v->eskimo_identifier, false );
        }

        // Process data
        if ( $this->debug ) { error_log( print_r( $api_data, true ) ); }

        // Process Woocommerce Import
        $api_results = $this->wc->get_products_all( $api_data );

        // Batched Results
        $results = [];

        // Log products for update
        if ( false !== $api_results && !empty( $api_results ) ) {
            foreach ( $api_results as $result ) { $results[] = $result; }
        }

        // Return results for Web_ID update
        return $results;
    }

    /**
     * Get remote API products
     * 
     * @param   integer $start      default 1  
     * @param   integer $records    default 20
     * @param   string  $timestmp   default 2000-01-01
     * @return  boolean
     */
    public function get_products_all( $start = 1, $records = 20, $timestamp = '2000-01-01' ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': Start[' . $start . '] Records[' . $records . '] timestamp[' . $timestamp . ']' ); }

        // Sensible defaults
        $api_defaults = [
            'StartPosition' => 1,
            'RecordCount'   => 20,
            'TimestampFrom' => '2000-01-01'
        ];

        // Pre-Sanitize
        $start   = absint( $start );
        $records = absint( $records );
        $timestamp = trim( $timestamp );

        // Test connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Validate date format, if not the actual date
        $date_pattern = '^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$';

        // Validate Opts
        $api_opts = [];
        $api_opts['StartPosition']  = ( $start > 0 ) ? (int) $start : $api_defaults['StartPosition'];
        $api_opts['RecordCount']    = ( $records > 0 && $records <= 50) ? (int) $records : $api_defaults['RecordCount'];
        $api_opts['TimestampFrom']  = ( preg_match( '/' . $date_pattern . '/', $timestamp ) ) ? $timestamp . 'T00:00:00' : $api_defaults['TimestampFrom'] . 'T00:00:00';
        if ( $this->debug ) { error_log( 'Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . '] Timestamp[' . $api_opts['TimestampFrom'] . ']' ); }

        // Batched Results
        $results = [];

        // Iterate batched results
        do { 
    
            // Get remote data
            $api_data = $this->api->products_all( $api_opts );

            // Validate API data
            if ( false === $api_data || empty( $api_data ) ) {
                return $this->api_rest_error();
            }

            // OK process data
            $api_count = count( $api_data );
            if ( $this->debug ) { error_log( 'Products Count[' . $api_count . '] Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . '] Timestamp [' . $api_opts['TimestampFrom'] . ']' ); }

            // Nothing to do here...
            if ( $api_count === 0 ) { break; }

            // Get products data per batch item & add product SKU
            foreach ( $api_data as $k=>$v ) {
                $v->sku = $this->get_sku_specific_ID( $v->eskimo_identifier, false );
            }

            // Process data
            if ( $this->debug ) { error_log( print_r( $api_data, true ) ); }

            // Process Woocommerce Import
            $api_results = $this->wc->get_products_all( $api_data );

            // Log products for update
            if ( false !== $api_results && !empty( $api_results ) ) {
                foreach ( $api_results as $result ) { $results[] = $result; }
            }

            // Update loop position
            $api_opts['StartPosition'] += $api_opts['RecordCount'];
            if ( $this->debug ) { error_log( 'Start[' . $api_opts['StartPosition'] . ']' ); }

        } while ( true );

        // Return results for Web_ID update
        return $results;
    }

    /**
     * Get remote API product by ID
     *
     * @param   string  $id
     * @param   boolean $force  Force import default true
     * @return  boolean
     */
    public function get_products_specific_ID( $id = '', $force = true ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . '] Force[' . (int) $force . ']' ); }

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
        if ( false === $api_data || empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_count = count( $api_data );
        if ( $this->debug ) { error_log( 'Product ID [' . $id . '] Count[' . $api_count . ']' ); }

        // Add Product SKU
        $api_data->sku = $this->get_sku_specific_ID( $api_data->eskimo_identifier, false );
        if ( $this->debug ) { error_log( print_r( $api_data, true ) ); }

        // Process Woocommerce Import
        return ( $force ) ? $this->wc->get_products_specific_ID( $api_data ) : $api_data;
	}

    /**
     * Get remote API product by ID
     *
     * @param   string  $id
     * @param   boolean $force  Force import default true
     * @return  boolean
     */
    public function get_products_import_ID( $id = '', $path = '', $force = true ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . '] Path[' . $path . '] Force[' . (int) $force . ']' ); }

		// Valid paths
		$paths = [ 'stock', 'tax', 'price',	'category', 'categories' ];
		
		// Test paths
		if ( empty( $path ) || !in_array( $path, $paths ) ) {
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
        if ( false === $api_data || empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_count = count( $api_data );
        if ( $this->debug ) { error_log( 'Product ID [' . $id . '] Path [' . $path . '] Count[' . $api_count . ']' ); }

        // Add Product SKU
        $api_data->sku = $this->get_sku_specific_ID( $api_data->eskimo_identifier, false );
        if ( $this->debug ) { error_log( print_r( $api_data, true ) ); }

        // Process Woocommerce Import
        return ( $force ) ? $this->wc->get_products_import_ID( $api_data, $path ) : $api_data;
	}

    //----------------------------------------------
    // Woocommerce Category & Product WebID Export
    //----------------------------------------------

    /**
     * Get remote API categories for Web_ID update
     *
     * @return  boolean
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
        if ( false === $api_data || empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_count = count( $api_data );
        if ( $this->debug ) { error_log( 'Category Count[' . $api_count . ']' ); }

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
            return $this->api_error( 'Invalid Category API Data' );
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
        $api_count = count( $api_response );
        if ( $this->debug ) { error_log( 'Child EPOS Category Count[' . $api_count . ']' ); }

        // Process data
        if ( $this->debug ) { error_log( print_r( $api_response, true ) ); }

        // Default OK
        return true;
    }

    /**
     * Get remote API products for Web_ID update
     * 
     * @param   integer $start      default 1  
     * @param   integer $records    default 50
     * @param   string  $timestamp  default 2000-01-01
     * @return  boolean
     */
    public function get_products_cart_ID( $start = 1, $records = 50, $timestamp = '2000-01-01' ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': Start[' . $start . '] Records[' . $records . '] timestamp[' . $timestamp . ']' ); }

        // Sensible defaults
        $api_defaults = [
            'StartPosition' => 1,
            'RecordCount'   => 50,
            'TimestampFrom' => '2000-01-01'
        ];

        // Pre-Sanitize
        $start   = absint( $start );
        $records = absint( $records );
        $timestamp = trim( $timestamp );

        // Test connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Validate date format, if not the actual date
        $date_pattern = '^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$';

        // Validate Opts
        $api_opts = [];
        $api_opts['StartPosition']  = ( $start > 0 ) ? (int) $start : $api_defaults['StartPosition'];
        $api_opts['RecordCount']    = ( $records > 0 && $records <= 100) ? (int) $records : $api_defaults['RecordCount'];
        $api_opts['TimestampFrom']  = ( preg_match( '/' . $date_pattern . '/', $timestamp ) ) ? $timestamp . 'T00:00:00' : $api_defaults['TimestampFrom'] . 'T00:00:00';
        if ( $this->debug ) { error_log( 'Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . '] Timestamp[' . $api_opts['TimestampFrom'] . ']' ); }

        // Batched Results
        $results = [];

        // Get remote data
        $api_data = $this->api->products_all( $api_opts );

        // Validate API data
        if ( false === $api_data || empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_count = count( $api_data );
        if ( $this->debug ) { error_log( 'Products Count[' . $api_count . '] Start[' . $api_opts['StartPosition'] . '] Records[' . $api_opts['RecordCount'] . '] Timestamp [' . $api_opts['TimestampFrom'] . ']' ); }

        // Nothing to do here...
        if ( $api_count === 0 ) { return false; }

        // Process data
        if ( $this->debug ) { error_log( print_r( $api_data, true ) ); }

        // Process Woocommerce Import
        $api_results = $this->wc->get_products_cart_ID( $api_data );

        // Log products for update
        if ( false !== $api_results && !empty( $api_results ) ) {
            foreach ( $api_results as $result ) { $results[] = $result; }
        }

        // Return results for Web_ID update
        return $results;
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
            return $this->api_error( 'Invalid Product API Otions' );
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
        $api_count = count( $api_response );
        if ( $this->debug ) { error_log( 'EPOS Product Count[' . $api_count . ']' ); }

        // Process data
        if ( $this->debug ) { error_log( print_r( $api_response, true ) ); }

        // Default OK
        return true;
    }

    //----------------------------------------------
    // Woocommerce Customer Import & Export
    //----------------------------------------------

    /**
     * Get remote API customer data
     *
     * @param   string  $id default ''
     * @return  boolean | string
     */
    public function get_customers_specific_ID( $id = '' ) {
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
        $api_data = $this->api->customers_specific_ID( $id, false );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_count = count( $api_data );
		if ( $this->debug ) { 
			error_log( 'Customer Count[' . $api_count . ']' );
			error_log( 'Customer Data: ' . print_r( $api_data, true ) ); 
		}

        // Process user update
        return $this->wc->get_customers_specific_ID( $api_data, true );
    }

    /**
     * Insert WC user to EskimoEPOS
     *
     * @param   string  $id default ''
     * @return  boolean | string
     */
    public function get_customers_create( $id = '' ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . ']' ); }

        // Test Options
        if ( empty( $id ) || $id <= 0 ) {
            return $this->api_error( 'Invalid Customer ID[' . $id . ']' );
        }

        // Validate Customer Data
		$api_opts = $this->wc->get_customers_insert_ID( $id );
        if ( ! is_array( $api_opts ) ) {
			return $this->api_error( $api_opts );
		}
		if ( $this->debug ) { error_log( 'Customer Data: ' . print_r( $api_opts, true ) ); }

        // Test connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }
		
        // Get remote data
        $api_data = $this->api->customers_create( $api_opts );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_count = count( $api_data );
		if ( $this->debug ) { 
			error_log( 'Customer Count[' . $api_count . ']' );
			error_log( 'Customer Data: ' . print_r( $api_data, true ) ); 
		}

        // Process data
        if ( $this->debug ) { error_log( print_r( $api_data, true ) ); }

        // Default OK
        return $this->wc->get_customers_epos_ID( $id, $api_data, true );
    }

    /**
     * Update WC user to EskimoEPOS
     *
     * @param   string  $id default ''
     * @return  boolean | string
     */
    public function get_customers_update( $id = '' ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . ']' ); }

        // Test Options
        if ( empty( $id ) || $id <= 0 ) {
            return $this->api_error( 'Invalid Customer ID[' . $id . ']' );
        }

        // Validate Customer Data
		$api_opts = $this->wc->get_customers_update_ID( $id );
        if ( ! is_array( $api_opts ) ) {
			return $this->api_error( $api_opts );
		}
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
        $api_count = count( $api_data );
		if ( $this->debug ) { 
			error_log( 'Customer Count[' . $api_count . ']' );
			error_log( 'Customer Data: ' . print_r( $api_data, true ) ); 
		}

        // Process data
        if ( $this->debug ) { error_log( print_r( $api_data, true ) ); }

        // Default OK
        return $this->wc->get_customers_epos_ID( $id, $api_data, false );
    }

    //----------------------------------------------
    // Woocommerce Order Export
    //----------------------------------------------

    /**
	 * Import EskimoEPOS WebOrder into Woocommerce
	 * - not yet implemented
     *
     * @param   array   $id
     * @return  boolean
     */
    public function get_orders_specific_ID( $id = '' ) {
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
        $api_data = $this->api->orders_specific_ID( $id, false );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_count = count( $api_data );
		if ( $this->debug ) { 
			error_log( 'Order Count[' . $api_count . ']' );
			error_log( 'Order Data: ' . print_r( $api_data, true ) ); 
		}

        // Process user update
        return $this->wc->get_orders_specific_ID( $api_data, true );
    }

    /**
     * Export Woocommerce order to EskimoEPOS WebOrder
     *
     * @param   array   $id
     * @return  boolean
     */
    public function get_orders_create( $id = '' ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . ']' ); }

        // Test Options
        if ( empty( $id ) || $id <= 0 ) {
            return $this->api_error( 'Invalid Order ID[' . $id . ']' );
        }

        // Validate Order Data
		$api_opts = $this->wc->get_orders_insert_ID( $id );
		//$api_opts = $this->get_order_data();
        if ( ! is_array( $api_opts ) ) {
			return $this->api_error( $api_opts );
		}
		if ( $this->debug ) { error_log( 'Order Data: ' . print_r( $api_opts, true ) ); }

        // Test connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Get remote data
        $api_data = $this->api->orders_insert( $api_opts );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_count = count( $api_data );
		if ( $this->debug ) { 
			error_log( 'Orders Count[' . $api_count . ']' );
			error_log( 'Orders Data: ' . print_r( $api_data, true ) ); 
		}

        // Process data
        if ( $this->debug ) { error_log( print_r( $api_data, true ) ); }

        // Default OK
        return $this->wc->get_orders_epos_ID( $id, $api_data, true );
    }

    //----------------------------------------------
    // Woocommerce SKU Import
    //----------------------------------------------

    /**
     * Get remote API SKUs
     *
     * @return  boolean
     */
    public function get_sku_all() {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Test connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Get remote data
        $api_data = $this->api->sku_all();

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_count = count( $api_data );
        if ( $this->debug ) { error_log( 'Product SKU Count[' . $api_count . ']' ); }

        // Process data
        if ( $this->debug ) { error_log( print_r( $api_data, true ) ); }

        // Process Woocommerce Import
        return $this->wc->get_sku_all( $api_data );
    }

    /**
     * Get remote API SKUs by ID
     *
     * @param   string  $id     default ''  
     * @param   integer $import default true
     * @return  boolean
     */
    public function get_sku_specific_ID( $id = '', $import = true ) {
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
        $api_data = $this->api->sku_specific_ID( $id );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_count = count( $api_data );
        if ( $this->debug ) { error_log( 'SKU Count[' . $api_count . ']' ); }

        // SKU Count?
        if ( false === $import ) { return $api_data; }
        
        // Process data
        if ( $this->debug ) { error_log( print_r( $api_data, true ) ); }
    
        // Process Woocommerce Import
        return $this->wc->get_sku_specific_ID( $api_data );
    }

    /**
     * Get remote API SKU by product ID
     *
     * @param   string  $id     default ''
     * @return  boolean
     */
    public function get_sku_specific_code( $id = '' ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . ']' ); }

        // Get Product ID
        $id = '';

        // Test connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Get remote data
        $api_data = $this->api->sku_specific_code( $id );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_count = count( $api_data );
        if ( $this->debug ) { error_log( 'Product SKU Count[' . $api_count . ']' ); }

        // Process data
        if ( $this->debug ) { error_log( print_r( $api_data, true ) ); }

        // Process Woocommerce Import
        return $this->wc->get_sku_specific_code( $api_data );
    }

    //----------------------------------------------
    // Woocommerce Product Images
    //----------------------------------------------

    /**
     * Get remote API product image links
     * - Not yet impemented
     * 
     * @return  boolean
     */
    public function get_image_links_all() {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . ']' ); }

        // Sensible defaults
        $api_defaults = [
            'StartPosition' => 1,
            'RecordCount'   => 20,
            'TimestampFrom' => '2000-01-01'
        ];

        // Demo defaults
        $api_opts = [
            'StartPosition' => 1,
            'RecordCount'   => 20,
            'TimestampFrom' => '2000-01-01'
        ];

        // Test Options
        if ( !is_array( $api_opts ) || empty( $api_opts ) ) {
            return $this->api_error( 'Invalid Image Links API Opts' );
        }

        // Test connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Validate date format, if not actual date
        $date_pattern = '^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$';

        // Validate Opts
        $api_opts['StartPosition']  = ( isset( $api_opts['StartPosition'] ) && $api_opts['StartPosition'] > 0 ) ? (int) $api_opts['StartPosition'] : $api_defaults['StartPosition'];
        $api_opts['RecordCount']    = ( isset( $api_opts['RecordCount'] ) && $api_opts['RecordCount'] > 0 ) ? (int) $api_opts['RecordCount'] : $api_defaults['RecordCount'];
        $api_opts['TimestampFrom']  = ( isset( $api_opts['TimestampFrom'] ) && preg_match( '/' . $date_pattern . '/', $api_opts['TimestampFrom'] ) ) ? $api_opts['TimestampFrom'] . 'T00:00:00' : '2000-01-01T00:00:00';

        // Get remote data
        $api_data = $this->api->image_links_all( $api_opts );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_count = count( $api_data );
        if ( $this->debug ) { error_log( 'Image Links Count[' . $api_count . ']' ); }

        // Process data
        if ( $this->debug ) { error_log( print_r( $api_data, true ) ); }
        
        // Process Woocommerce Import
        return $this->wc->get_image_links_all( $api_data );
    }

    /**
     * Get remote API product images
     * - not yet implemented
     * 
     * @return  boolean
     */
    public function get_images_all() {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . ']' ); }

        // Sensible defaults
        $api_defaults = [
            'StartPosition' => 1,
            'RecordCount'   => 20,
            'TimestampFrom' => '2000-01-01'
        ];

        // Demo defaults
        $api_opts = [
            'StartPosition' => 1,
            'RecordCount'   => 20,
            'TimestampFrom' => '2000-01-01'
        ];

        // Test Options
        if ( !is_array( $api_opts ) || empty( $api_opts ) ) {
            return $this->api_error( 'Invalid Images API Opts' );
        }

        // Test connection
        if ( false === $this->api->init() ) {
            return $this->api_connect_error();
        }

        // Validate date format, if not actual date
        $date_pattern = '^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$';

        // Validate Opts
        $api_opts['StartPosition']  = ( isset( $api_opts['StartPosition'] ) && $api_opts['StartPosition'] > 0 ) ? (int) $api_opts['StartPosition'] : $api_defaults['StartPosition'];
        $api_opts['RecordCount']    = ( isset( $api_opts['RecordCount'] ) && $api_opts['RecordCount'] > 0 ) ? (int) $api_opts['RecordCount'] : $api_defaults['RecordCount'];
        $api_opts['TimestampFrom']  = ( isset( $api_opts['TimestampFrom'] ) && preg_match( '/' . $date_pattern . '/', $api_opts['TimestampFrom'] ) ) ? $api_opts['TimestampFrom'] . 'T00:00:00' : '2000-01-01T00:00:00';

        // Get remote data
        $api_data = $this->api->images_all( $api_opts );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_count = count( $api_data );
        if ( $this->debug ) { error_log( 'Image Links Count[' . $api_count . ']' ); }

        // Process data
        if ( $this->debug ) { error_log( print_r( $api_data, true ) ); }

        // Process Woocommerce Import
        return $this->wc->get_images_all( $api_data );
    }

    //----------------------------------------------
    // Woocommerce Miscellaneous ImpEx
    //----------------------------------------------

    /**
     * Get remote API Tax Codes optionally by ID
     * - not yet implemented
     *
     * @param   string  $id     default ''
     * @return  boolean
     */
    public function get_tax_codes( $id = '' ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . ']' ); }

        // Get remote data
        $api_data = ( empty( $id ) ) ? $this->api->tax_codes() : $this->api->tax_codes( $id );

        // Validate API data
        if ( false === $api_data ) {
            return $this->api_rest_error();
        }

        // OK process data
        $api_count = count( $api_data );
        if ( $this->debug ) { error_log( 'Tax Code Count[' . $api_count . ']' ); }

        // Process data
        if ( $this->debug ) { error_log( print_r( $api_data, true ) ); }

        // Process Woocommerce Import
        return $this->wc->get_tax_codes( $api_data );
    }

    /**
     * Get remote API shops
     * - not yet implemented
     *
     * @return boolean
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
        $api_count = count( $api_data );
        if ( $this->debug ) { error_log( 'Shopss Count[' . $api_count . ']' ); }

        // Process data
        if ( $this->debug ) { error_log( print_r( $api_data, true ) ); }

        // Process Woocommerce Import
        return $this->wc->get_shops_all( $api_data );
    }

    /**
     * Get remote API product images
     * - not yet implemented
     *
     * @param   string  $id     default ''
     * @return  boolean
     */
    public function get_shops_specific_ID( $id = '' ) {
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
        $api_count = count( $api_data );
        if ( $this->debug ) { error_log( 'Shops Count[' . $api_count . ']' ); }

        // Process data
        if ( $this->debug ) { error_log( print_r( $api_data, true ) ); }

        // Process Woocommerce Import
        return $this->wc->get_shops_specific_ID( $api_data );
    }

    //----------------------------------------------
    // API Error
    //----------------------------------------------

    /**
     * Log API Error
     *
     * @param   string  $error
     */
    protected function api_error( $error ) {
        if ( $this->debug ) { 
            error_log( __CLASS__ . ':' . __METHOD__ . ': Error[' . $error . ']' );
            error_log( $error ); 
		}
		return $error;
    }

    /**
     * Log API Connection Error
     */
    protected function api_connect_error() {
        if ( $this->debug ) { 
            error_log( __CLASS__ . ':' . __METHOD__ );
            error_log( __( 'API Error: Could Not Connect To API', 'eskimo' ) ); 
		}
		return __( 'API Error: Could Not Connect To API', 'eskimo' );
    }

    /**
     * Log API REST Process Error
     */
    protected function api_rest_error() {
        if ( $this->debug ) { 
            error_log( __CLASS__ . ':' . __METHOD__ ); 
            error_log( __( 'API Error: Could Not Retrieve REST data from API', 'eskimo' ) ); 
		}
		return __( 'API Error: Could Not Retrieve REST data from API', 'eskimo' ); 
    }
}
