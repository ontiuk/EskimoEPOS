<?php 

/**
 * EskimoEPOS cron class 
 *
 * Define EskimoEPOS cron functionality for the plugin
 * 
 * @package    	Eskimo
 * @subpackage 	Eskimo/includes
 * @link		https://on.tinternet.co.uk     
 */

/**
 * EskimoEPOS cron class 
 * 
 * @package    Eskimo
 * @subpackage Eskimo/includes
 * @author     Stephen Betley <on@tinternet.co.uk>
 */

final class Eskimo_Cron { 

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
	 * Initialize the class and set its properties
	 *
	 * @param   string    $eskimo     The name of this plugin
	 */
	public function __construct( $eskimo ) {

		// Set up class settings
		$this->eskimo       = $eskimo;
   		$this->version		= ESKIMO_VERSION;
		$this->debug        = ESKIMO_CRON_DEBUG;
		$this->base_dir		= plugin_dir_url( __FILE__ ); 

		if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }
	}
    
    //----------------------------------------------
    //  Cron Functions 
    //----------------------------------------------

	/**
	 * Process new categories
	 */
	public function categories_do_new() {

		// Timeout
		set_time_limit( 900 );

		if ( $this->debug ) { error_log( '=== Categories Do New ===' ); }

		// Initiate REST call to update EPOS order status
		$rest_url = esc_url( home_url( '/wp-json' ) ) . '/eskimo/v1/categories-new';
		if ( $this->debug ) { error_log( 'REST URL [' . $rest_url . ']' ); }

		// Get category list
		$response 	= wp_remote_get( $rest_url, [ 'timeout' => 60 ] );
		$data 		= json_decode( wp_remote_retrieve_body( $response ), true );
		$categories	= $data['result'];

		// Bad call?
		if ( ! is_array( $categories ) ) {				
			return ( $this->debug ) ? error_log( 'REST Result [' . $categories . ']' ) : '';
		}

		// Nothing returned
		if ( empty( $categories ) ) {
			return ( $this->debug ) ? error_log( 'REST No New Categories Found' ) : ''; 
		}

		$results = [];
		foreach ( $categories as $category ) {

			// Set up category
			$cat_id = explode( '|', $category['Eskimo_Category_ID'] );
			if ( count( $cat_id ) < 2 ) { continue; }

			// Construct REST url
			$rest_url = esc_url( home_url( '/wp-json' ) ) . '/eskimo/v1/category-update/' . $cat_id[0] . '/' . $cat_id[1] . '/' . $category['Web_ID'];
			if ( $this->debug ) { error_log( 'REST URL [' . $rest_url . ']' ); }

			// Get update response
			$response = wp_remote_get( $rest_url, [ 'timeout' => 60 ] );
			$data 		= json_decode( wp_remote_retrieve_body( $response ), true );
			$result 	= $data['result'];

			$results[] = $result;
		}

		if ( $this->debug ) { error_log( 'Category UPD[' . print_r( $results, true ) . ']' ); }
	}

	/**
	 * Process new products
	 *
	 * @param	string	$route		default days - hours, days, weeks, months, all
	 * @param	integer	$created	default 7
	 */
	public function products_do_new( $route = 'days', $created = 7 ) {

		// Timeout
		set_time_limit( 900 );

		// Set Cron Header
		$dt = new DateTime;
		if ( $this->debug ) {	error_log( '=== Products Do New ===: Date[' . $dt->format( 'Y-m-d H:i:s') . '] ===' ); }

		// Valid paths
		$routes = [ 'hours', 'days', 'weeks', 'months', 'all' ];

		// Set defaults
		$api_defaults = [
		   	'route'		=> 'days', 
			'created' 	=> 7 
		];

		// Sanity
		$route		= sanitize_text_field( $route );
		$created	= absint( $created );

		// Set some defaults
		$route 		= ( empty( $route ) ) ? $api_defaults['route'] : $route;
		$created	= ( $created === 0 ) ? $api_defaults['created'] : $created;
		if ( $this->debug ) { error_log( 'Products Do Modified: Route[' . $route . '] Created[' . $created . ']' ); }

		// Valid path & route
		if ( ! in_array( $route, $routes ) ) { return; }

		// Initiate REST call to update EPOS order status
		$rest_url = ( $route === 'all' ) ? esc_url( home_url( '/wp-json' ) ) . '/eskimo/v1/products-new/' . $route :
										   esc_url( home_url( '/wp-json' ) ) . '/eskimo/v1/products-new/' . $route . '/' . $created;
		if ( $this->debug ) {	error_log( 'REST URL [' . $rest_url . ']' ); }

		// Retrieve product list
		$response 	= wp_remote_get( $rest_url, [ 'timeout' => 120 ] );
		$data 		= json_decode( wp_remote_retrieve_body( $response ), true );
		$products 	= $data['result'];

		// Bad call
		if ( ! is_array( $products ) ) {
			return ( $this->debug ) ? error_log( 'REST Result [' . $products . ']' ) : '';
		}

		// Nothing to do?
		if ( empty( $result ) ) {
			return ( $this->debug ) ? error_log( 'REST No New Products Found' ) : '';
		}

		// Import
		$results = [];
		foreach ( $products as $product ) {

			// Set up product
			$product_parts = explode( '|', $product );
			if ( count( $product_parts ) < 3 ) { continue; }

			// Construct REST url
			$rest_url = ( empty( $product_parts[2] ) ) ? esc_url( home_url( '/wp-json' ) ) . '/eskimo/v1/product/' . $product_parts[0] . '/' . $product_parts[1] :
														 esc_url( home_url( '/wp-json' ) ) . '/eskimo/v1/product/' . $product_parts[0] . '/' . $product_parts[1] . '/' . $product_parts[2];

			if ( $this->debug ) { error_log( 'REST URL [' . $rest_url . ']' ); }

			// Retrieve 
			$response = wp_remote_get( $rest_url, [ 'timeout' => 120 ] );
			$data 		= json_decode( wp_remote_retrieve_body( $response ), true );
			$result 	= $data['result'];

			$results[] = $result;
		}

		if ( $this->debug ) {	error_log( 'Product UPD[' . print_r( $results, true ) . ']' ); }
	}

	/**
	 * Process modified SKUs by parent product
	 *
	 * @param	string $path		
	 * @param	string $route		default hours
	 * @param	string $modified	default 1
	 * @param	string $start		default 1
	 * @param	string $records		default 250
	 */
	public function skus_do_modified( $path, $route = 'hours', $modified = 1, $start = 1, $records = 250 ) {

		// Timeout
		set_time_limit( 900 );

		// Set Cron Header
		$dt = new DateTime;
		if ( $this->debug ) { error_log( '=== SKUS Do Modified: Date[' . $dt->format( 'Y-m-d H:i:s') . '] ===' ); }

		// Valid paths
		$paths 	= [ 'all', 'stock', 'price' ];
		$routes = [ 'hours', 'days', 'weeks', 'months' ];

		$api_defaults = [
			'path'		=> 'all',
		   	'route'		=> 'hours', 
			'modified' 	=> 1, 
			'start' 	=> 1,
			'records' 	=> 250
		];

		// Sanity
		$path		= sanitize_text_field( $path );
		$route		= sanitize_text_field( $route );
		$modified	= absint( $modified );
		$start 		= absint( $start );
		$records	= absint( $records );

		// Set some defaults
		$path 		= ( empty( $path ) ) 	? $api_defaults['path'] 	: $path;
		$route 		= ( empty( $route ) ) 	? $api_defaults['route'] 	: $route;
		$modified	= ( $modified === 0 ) 	? $api_defaults['modified'] : $modified;
		$start		= ( $start === 0 ) 		? $api_defaults['start'] 	: $start;
		$records	= ( $records === 0 || $records > 250 ) ? $api_defaults['records'] : $records;
		if ( $this->debug ) { error_log( 'SKUS Do Modified: Path[' . $path . '] Route[' . $route . '] Modified[' . $modified . '] Start[' . $start . '] Records[' . $records . ']' ); }

		// Valid path & route
		if ( ! in_array( $path, $paths ) || ! in_array( $route, $routes ) ) { return; }

		// Initiate REST call to update EPOS order status
		$rest_url = esc_url( home_url( '/wp-json' ) ) . '/eskimo/v1/skus-modified/' . $path . '/' . $route . '/' . $modified . '/' . $start . '/' . $records;
		if ( $this->debug ) { error_log( 'REST URL [' . $rest_url . ']' ); }

		// Retrieve SKU list
		$response 	= wp_remote_get( $rest_url, [ 'timeout' => 120 ] );
		$data 		= json_decode( wp_remote_retrieve_body( $response ), true );
		$skus 		= $data['result'];

		// Bad call
		if ( ! is_array( $skus ) ) {
			return ( $this->debug ) ? error_log( 'REST Result [' . $skus . ']' ) : '';
		}

		// Nothing to do...
		if ( empty( $skus ) ) {
			return ( $this->debug ) ? error_log( 'REST No SKUs Found' ) : '';
		}

		// Get unique products
		$products = [];
		foreach ( $skus as $sku ) {	
			$products[] = $sku['Eskimo_Product_Identifier'];
		}
		$products = array_values( array_unique( $products, SORT_STRING ) );
		if ( $this->debug ) { error_log( print_r( $products, true ) ); }

		$results = [];
		foreach ( $products as $product ) {

			// Set up product
			$product_parts = explode( '|', $product );
			if ( count( $product_parts ) < 3 ) { continue; }

			// Construct REST url
			$rest_url = ( empty( $product_parts[2] ) ) ? esc_url( home_url( '/wp-json' ) ) . '/eskimo/v1/product-import/adjust/' . $product_parts[0] . '/' . $product_parts[1] :
														 esc_url( home_url( '/wp-json' ) ) . '/eskimo/v1/product-import/adjust/' . $product_parts[0] . '/' . $product_parts[1] . '/' . $product_parts[2];
			if ( $this->debug ) {	error_log( 'REST URL [' . $rest_url . ']' ); }

			// Retrieve import result
			$response = wp_remote_get( $rest_url, [ 'timeout' => 900 ] );
			$data 		= json_decode( wp_remote_retrieve_body( $response ), true );
			$result 	= $data['result'];

			$results[] = $result;
			sleep(6);
		}

		if ( $this->debug ) {	error_log( 'Prod UPD[' . print_r( $results, true ) . ']' ); }
	}

	/**
	 * Process modified SKU products
	 * - One-off scheduled run from comma separated list of product EPOS IDs
	 *
	 * @param	string $list
	 */
	public function products_do_modified( $list ) {
	
		// Timeout
		set_time_limit(900);

		// Get unique products
		$products = explode( ',', $list );
		if ( empty( $products ) ) { return; }

		if ( $this->debug ) {	error_log( '=== SKU Do Modified Products[' . count( $products ) . '] ===' ); }
				
		$results = [];
		foreach ( $products as $k=>$product ) {

			// Set up product
			$product_parts = explode( '|', $product );
			if ( count( $product_parts ) < 3 ) { continue; }

			// Construct product REST Url
			$rest_url = ( empty( $product_parts[2] ) ) ? esc_url( home_url( '/wp-json' ) ) . '/eskimo/v1/product-import/adjust/' . $product_parts[0] . '/' . $product_parts[1] :
														 esc_url( home_url( '/wp-json' ) ) . '/eskimo/v1/product-import/adjust/' . $product_parts[0] . '/' . $product_parts[1] . '/' . $product_parts[2];
			if ( $this->debug ) {	error_log( 'REST URL [' . $rest_url . ']' ); }

			// Process product import
			$response = wp_remote_get( $rest_url, [ 'timeout' => 120 ] );
			$data 		= json_decode( wp_remote_retrieve_body( $response ), true );
			$result 	= $data['result'];

			$results[] = $result;
		}
		
		if ( $this->debug ) {	error_log( 'Products UPD[' . print_r( $results, true ) . ']' ); }
	}
}
