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
	}
    
    //----------------------------------------------
    //  Cron Functions - New Categories
    //----------------------------------------------

	/**
	 * Import & update new categories
	 */
	public function categories_new() {

		// skus import?
	    $path	= strtolower( filter_input( INPUT_GET, 'eskimo_path', FILTER_SANITIZE_STRING ) );
    	$new  	= absint( filter_input( INPUT_GET, 'eskimo_new', FILTER_SANITIZE_NUMBER_INT ) );

	    // check & run
		if ( $new === 0 || $path !== 'categories' ) { return; }

		// Long form process		
		$this->categories_do_new();
	}

	/**
	 * Process new categories
	 */
	public function categories_do_new() {

		// Timeout
		set_time_limit( 900 );

		if ( $this->debug ) { eskimo_log( '=== Categories Do New ===', 'cron' ); }

		// Initiate REST call to update EPOS order status
		$rest_url = esc_url( home_url( '/wp-json' ) ) . '/eskimo/v1/categories-new';
		if ( $this->debug ) { eskimo_log( 'REST URL [' . $rest_url . ']', 'cron' ); }

		// Get category list
		$response 	= wp_remote_get( $rest_url, [ 'timeout' => 60 ] );
		$data 		= json_decode( wp_remote_retrieve_body( $response ), true );
		if ( is_null( $data ) ) { return; }
		$categories	= $data['result'];

		// Bad call?
		if ( ! is_array( $categories ) || empty( $categories ) ) {
			if ( $this->debug ) {				
				return ( empty( $categories ) ) ? eskimo_log( 'REST No New Categories Found', 'cron' ) : eskimo_log( 'REST Result [' . $categories . ']', 'cron' );
			} else { return; }
		}

		$results = [];
		foreach ( $categories as $category ) {

			// Set up category
			$cat_id = explode( '|', $category['Eskimo_Category_ID'] );
			if ( count( $cat_id ) < 2 ) { continue; }

			// Construct REST url
			$rest_url = esc_url( home_url( '/wp-json' ) ) . '/eskimo/v1/category-update/' . $cat_id[0] . '/' . $cat_id[1] . '/' . $category['Web_ID'];
			if ( $this->debug ) { eskimo_log( 'REST URL [' . $rest_url . ']', 'cron' ); }

			// Get update response
			$response = wp_remote_get( $rest_url, [ 'timeout' => 60 ] );
			$data 		= json_decode( wp_remote_retrieve_body( $response ), true );
			$result 	= $data['result'];

			$results[] = $result;
		}

		if ( $this->debug ) { eskimo_log( 'Category UPD[' . print_r( $results, true ) . ']', 'cron' ); }
	}

    //----------------------------------------------
    //  Cron Functionality - New Products
    //----------------------------------------------

	/**
	 * Import & update new products
	 */
	public function products_new() {

		// skus import?
	    $path		= strtolower( filter_input( INPUT_GET, 'eskimo_path', FILTER_SANITIZE_STRING ) );
    	$new  		= absint( filter_input( INPUT_GET, 'eskimo_new', FILTER_SANITIZE_NUMBER_INT ) );
	 	$route	 	= strtolower( filter_input( INPUT_GET, 'eskimo_route', FILTER_SANITIZE_STRING ) );
    	$created	= absint( filter_input( INPUT_GET, 'eskimo_created', FILTER_SANITIZE_NUMBER_INT ) );

	    // check & run
		if ( $new === 0 || $path !== 'products' || empty( $route ) || $created === 0 ) { return; }

		// Long form process		
		$this->products_do_new( $route, $created );
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
		if ( $this->debug ) { eskimo_log( '=== Products Do New ===: Date[' . $dt->format( 'Y-m-d H:i:s') . '] ===', 'cron' ); }

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
		if ( $this->debug ) { eskimo_log( 'Route[' . $route . '] Created[' . $created . ']', 'cron' ); }

		// Valid path & route
		if ( ! in_array( $route, $routes ) ) { return; }

		// Initiate REST call to update EPOS order status
		$rest_url = ( $route === 'all' ) ? esc_url( home_url( '/wp-json' ) ) . '/eskimo/v1/products-new/' . $route :
										   esc_url( home_url( '/wp-json' ) ) . '/eskimo/v1/products-new/' . $route . '/' . $created;
		if ( $this->debug ) { eskimo_log( 'REST URL [' . $rest_url . ']', 'cron' ); }

		// Retrieve product list
		$response 	= wp_remote_get( $rest_url, [ 'timeout' => 120 ] );
		$data 		= json_decode( wp_remote_retrieve_body( $response ), true );
		$products 	= $data['result'];

		// Bad call
		if ( ! is_array( $products ) || empty( $products ) ) {
			if ( $this->debug ) {				
				return ( empty( $products ) ) ? eskimo_log( 'REST No New Products Found', 'cron' ) : eskimo_log( 'REST Result [' . $products . ']' );
			} else { return; }
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

			if ( $this->debug ) { eskimo_log( 'REST URL [' . $rest_url . ']', 'cron' ); }

			// Retrieve 
			$response = wp_remote_get( $rest_url, [ 'timeout' => 120 ] );
			$data 		= json_decode( wp_remote_retrieve_body( $response ), true );
			$result 	= $data['result'];

			$results[] = $result;
		}

		if ( $this->debug ) { eskimo_log( 'Product UPD[' . print_r( $results, true ) . ']', 'cron' ); }
	}

    //----------------------------------------------
    //  Cron Functionality - Modified SKUs
    //----------------------------------------------

	/**
	 * Import & update modified SKUs
	 */
	public function skus_modified() {

		// skus import?
    	$skus		= absint( filter_input( INPUT_GET, 'eskimo_skus', FILTER_SANITIZE_NUMBER_INT ) );
	    $path		= strtolower( filter_input( INPUT_GET, 'eskimo_path', FILTER_SANITIZE_STRING ) );
    	$route	 	= strtolower( filter_input( INPUT_GET, 'eskimo_route', FILTER_SANITIZE_STRING ) );
    	$modified	= absint( filter_input( INPUT_GET, 'eskimo_modified', FILTER_SANITIZE_NUMBER_INT ) );
    	$start		= absint( filter_input( INPUT_GET, 'eskimo_start', FILTER_SANITIZE_NUMBER_INT ) );
		$records	= absint( filter_input( INPUT_GET, 'eskimo_records', FILTER_SANITIZE_NUMBER_INT ) );
		$all		= absint( filter_input( INPUT_GET, 'eskimo_all', FILTER_SANITIZE_NUMBER_INT ) );

	    // check & run
		if ( $skus === 0 || empty( $path ) || empty( $route ) || $modified === 0 ) { return; }

		// Long form process		
		if ( $all === 1 ) {
			$modified	= ( $modified === 0 ) ? 1 : $modified;
			$this->skus_do_modified_all( $path, $route, $modified );
		} else {
			$modified	= ( $modified === 0 ) ? 1 : $modified;
			$start 		= ( $start === 0 ) ? 1 : $start;
			$records 	= ( $records === 0 ) ? 250 : $records;
			$this->skus_do_modified( $path, $route, $modified, $start, $records );
		}
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
		if ( $this->debug ) { eskimo_log( '=== SKUS Do Modified: Date[' . $dt->format( 'Y-m-d H:i:s') . '] ===', 'cron' ); }

		// Valid paths
		$paths 	= [ 'all', 'stock', 'price' ];
		$routes = [ 'seconds', 'minutes', 'hours', 'days', 'weeks', 'months' ];

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
		$records	= ( $records === 0 || $records > 2500 ) ? $api_defaults['records'] : $records;
		if ( $this->debug ) { eskimo_log( 'Path[' . $path . '] Route[' . $route . '] Modified[' . $modified . '] Start[' . $start . '] Records[' . $records . ']', 'cron' ); }

		// Valid path & route
		if ( ! in_array( $path, $paths ) || ! in_array( $route, $routes ) ) { return; }

		// Initiate REST call to update EPOS order status
		$rest_url = esc_url( home_url( '/wp-json' ) ) . '/eskimo/v1/skus-modified/' . $path . '/' . $route . '/' . $modified . '/' . $start . '/' . $records;
		if ( $this->debug ) { eskimo_log( 'REST URL [' . $rest_url . ']', 'cron' ); }

		// Retrieve SKU list
		$response 	= wp_remote_get( $rest_url, [ 'timeout' => 120 ] );
		$data 		= json_decode( wp_remote_retrieve_body( $response ), true );

		// Bad call
		if ( is_null( $data ) ) {
			return ( $this->debug ) ? eskimo_log( 'REST API call error', 'cron' ) : '';
		} else {
			$skus = $data['result'];
			if ( ! is_array( $skus ) || empty( $skus ) ) {
				return ( $this->debug ) ? eskimo_log( 'REST SKUs not found', 'cron' ) : '';
			}
		}

		// Get unique products
		$products = [];
		foreach ( $skus as $sku ) {	
			$products[] = $sku['Eskimo_Product_Identifier'];
		}
		$products = array_values( array_unique( $products, SORT_STRING ) );
		if ( $this->debug ) { eskimo_log( 'Products[' . print_r( $products, true ) . ']', 'cron' ); }

		$results = [];
		foreach ( $products as $product ) {

			// Set up product
			$product_parts = explode( '|', $product );
			if ( count( $product_parts ) < 3 ) { continue; }

			// Construct REST url
			$rest_url = ( empty( $product_parts[2] ) ) ? esc_url( home_url( '/wp-json' ) ) . '/eskimo/v1/product-import/adjust/' . $product_parts[0] . '/' . $product_parts[1] :
														 esc_url( home_url( '/wp-json' ) ) . '/eskimo/v1/product-import/adjust/' . $product_parts[0] . '/' . $product_parts[1] . '/' . $product_parts[2];
			if ( $this->debug ) { eskimo_log( 'REST URL [' . $rest_url . ']', 'cron' ); }

			// Retrieve import result
			$response = wp_remote_get( $rest_url, [ 'timeout' => 900 ] );
			$data 		= json_decode( wp_remote_retrieve_body( $response ), true );
			$result 	= $data['result'];

			$results[] = $result;
			sleep(6);
		}

		if ( $this->debug ) { eskimo_log( 'Prod UPD[' . print_r( $results, true ) . ']', 'cron' ); }
	}
	
	/**
	 * Process modified SKUs
	 *
	 * @param	string $path
	 * @param	string $route
	 * @param	string $modified
	 * @param	string $start
	 * @param	string $records
	 */
	public function skus_do_modified_all( $path, $route = 'hours', $modified = 1 ) {

		// Timeout
		set_time_limit( 900 );

		// Set Cron Header
		$dt = new DateTime;
		if ( $this->debug ) { eskimo_log( '=== SKUS Do Modified ALL: Cron Date[' . $dt->format( 'Y-m-d H:i:s') . '] ===', 'cron' ); }

		// Valid paths
		$paths = [ 'all', 'stock', 'price' ];
		$routes = [ 'seconds', 'minutes', 'hours', 'days', 'weeks', 'months' ];

		// Defaults
		$api_defaults = [
			'path'		=> 'all',
		   	'route'		=> 'hours', 
			'modified' 	=> 1, 
		];

		// Sanity
		$path		= sanitize_text_field( $path );
		$route		= sanitize_text_field( $route );
		$modified	= absint( $modified );
		$start 		= 1;
		$records	= 1000;

		// Set some defaults
		$path 		= ( empty( $path ) ) ? $api_defaults['path'] : $path;
		$route 		= ( empty( $route ) ) ? $api_defaults['route'] : $route;
		$modified	= ( $modified === 0 ) ? $api_defaults['modified'] : $modified;
		if ( $this->debug ) { skimo_log( 'Path[' . $path . '] Route[' . $route . '] Modified[' . $modified . '] Start[' . $start . '] Records[' . $records . ']', 'cron' ); }

		// Valid path & route
		if ( ! in_array( $path, $paths ) || ! in_array( $route, $routes ) ) { return; }

		// Initiate REST call to update EPOS order status
		$results = [];
		do {
			
			$rest_url = esc_url( home_url( '/wp-json' ) ) . '/eskimo/v1/skus-modified/' . $path . '/' . $route . '/' . $modified . '/' . $start . '/' . $records;
			if ( $this->debug ) { eskimo_log( 'REST URL [' . $rest_url . ']', 'cron' ); }

			$response 	= wp_remote_get( $rest_url, [ 'timeout' => 120 ] );
			$data 		= json_decode( wp_remote_retrieve_body( $response ), true );
			$result 	= $data['result'];

			// Trigger end of loop
			if ( ! is_array( $result ) || empty( $result ) ) { break; }

			// Add on result
			$results = array_merge( $result, $results );

			// Iterate start & records
			$start += $records;
			if ( $this->debug ) { eskimo_log( 'Loop: [' . $start . '] Records[' . $records . ']', 'cron' ); }

		} while( true );

		// No results?
		if ( empty( $results ) ) {
			return ( $this->debug ) ? eskimo_log( 'REST SKUs not found', 'cron' ) : '';
		}

		// Get unique products
		$products = [];
		foreach ( $results as $k => $r ) {	
			$products[] = $r['Eskimo_Product_Identifier'];
		}
		$products = array_values( array_unique( $products, SORT_STRING ) );
		if ( $this->debug ) { eskimo_log( print_r( $products, true ), 'cron' ); }

		$results = [];
		foreach ( $products as $product ) {
			
			$product_parts = explode( '|', $product );
			if ( count( $product_parts ) < 3 ) { continue; }

			$rest_url = ( empty( $product_parts[2] ) ) ? esc_url( home_url( '/wp-json' ) ) . '/eskimo/v1/product-import/adjust/' . $product_parts[0] . '/' . $product_parts[1] :
														 esc_url( home_url( '/wp-json' ) ) . '/eskimo/v1/product-import/adjust/' . $product_parts[0] . '/' . $product_parts[1] . '/' . $product_parts[2];
			if ( $this->debug ) { eskimo_log( 'REST URL [' . $rest_url . ']' ); }

			$response = wp_remote_get( $rest_url, [ 'timeout' => 900 ] );
			$data 		= json_decode( wp_remote_retrieve_body( $response ), true );
			$result 	= $data['result'];

			$results[] = $result;

			sleep(6);
		}
		
		if ( $this->debug ) { eskimo_log( 'Products UPD[' . print_r( $results, true ) . ']', 'cron' ); }
 	}

    //----------------------------------------------
    //  Cron Functionality - Modified Products
    //----------------------------------------------

	/**
	 * Import & update modified SKU products by batch
	 */
	public function skus_modified_products() {

		// skus import?
    	$products	= absint( filter_input( INPUT_GET, 'eskimo_products', FILTER_SANITIZE_NUMBER_INT ) );
   		$date		= filter_input( INPUT_GET, 'eskimo_date', FILTER_SANITIZE_STRING );
		$route		= filter_input( INPUT_GET, 'eskimo_route', FILTER_SANITIZE_STRING );

		// check & run
		if ( $products === 0 || empty( $data ) || empty( $route ) ) { return; }

		// Long form process
		$this->skus_do_modified_products( $route, $data );
	}

	/**
	 * Process modified SKU products
	 * - One-off scheduled run from comma separated list of product EPOS IDs
	 *
	 * @param	string $route
	 * @param	string $list
	 */
	public function skus_do_modified_products( $route, $data ) {
	
		// Timeout
		set_time_limit(900);

		// Get unique products
		switch ( $route ) {
			case 'list':
				$products = explode( ',', $data );
				break;
			case 'file':
				$file_name = WP_CONTENT_DIR . '/uploads/eskimo/' . $file;
				if ( file_exists( $file_name ) && is_readable( $file_name ) ) { 
					$products = array_map( 'str_getcsv', file( $file_name ) );
				} else { $products = []; }
				break;
			default:
				return;
		}

		// First test		
		if ( empty( $products ) ) { return; }

		//Ok, process
		if ( $this->debug ) { eskimo_log( '=== SKU Do Modified Products[' . count( $products ) . '] ===', 'cron' ); }
				
		$results = [];
		foreach ( $products as $k => $product ) {

			// Set up product
			$product_parts = explode( '|', $product );
			if ( count( $product_parts ) < 3 ) { continue; }

			// Construct product REST Url
			$rest_url = ( empty( $product_parts[2] ) ) ? esc_url( home_url( '/wp-json' ) ) . '/eskimo/v1/product-import/adjust/' . $product_parts[0] . '/' . $product_parts[1] :
														 esc_url( home_url( '/wp-json' ) ) . '/eskimo/v1/product-import/adjust/' . $product_parts[0] . '/' . $product_parts[1] . '/' . $product_parts[2];
			if ( $this->debug ) { eskimo_log( 'REST URL [' . $rest_url . ']', 'cron' ); }

			// Process product import
			$response = wp_remote_get( $rest_url, [ 'timeout' => 120 ] );
			$data 		= json_decode( wp_remote_retrieve_body( $response ), true );
			$result 	= $data['result'];

			$results[] = $result;
		}
		
		if ( $this->debug ) { eskimo_log( 'Products UPD[' . print_r( $results, true ) . ']', 'cron' ); }
	}

    //----------------------------------------------
    //  Cron Functionality - Expired SKUs
    //----------------------------------------------

	/**
	 * Delete merchant deals
	 */
	public function skus_expired() {

		// expire delete?
	    $file	= strtolower( filter_input( INPUT_GET, 'eskimo_file', FILTER_SANITIZE_STRING ) );
		$delete = (int) filter_input( INPUT_GET, 'eskimo_delete', FILTER_SANITIZE_NUMBER_INT );
		$image 	= (int) filter_input( INPUT_GET, 'eskimo_image', FILTER_SANITIZE_NUMBER_INT );

		// check & run
		if ( $delete === 0 || empty( $file ) ) { return; }
		$this->skus_do_expired( $file, $image );
	}

	/**
	 * Delete expired posts from file
	 *
	 * @param	string	$file
	 * @param	integer	$image
	 */
	public function skus_do_expired( $file, $image ) { 

		global $wpdb;

		// Validate & get file
		if ( empty( $file ) ) { return; }

		if ( $this->debug ) { eskimo_log( '=== SKUs Do Expired: File[' . $file . '][' . $image . '] OK ===', 'cron' ); }

		// Set filepath
		$file_name = WP_CONTENT_DIR . '/uploads/eskimo/' . $file;
		if ( $this->debug ) { eskimo_log( 'FileName[' . $file_name . ']', 'cron' ); }

		// Validate 
		if( ! file_exists( $file_name ) || ! is_readable( $file_name ) ) { return; }
		if ( $this->debug ) { eskimo_log( 'FileName OK', 'cron' ); }
		
		// Get SKUs
		$skus = array_map( 'str_getcsv', file( $file_name ) );

		// Get posts list from SKUs
		$posts = [];
		foreach ( $skus as $k => $v ) {
			$post = $this->skus_get_post( $v[0] );
			if ( false === $post ) { continue; }
			$posts[] = $post;
		}

		// Validate posts
		if ( empty( $posts ) ) { return; }
		if ( $this->debug ) { eskimo_log( 'Product Count [' . count( $posts ) . '][' . print_r( $posts, true ) . ']', 'cron' ); }

		$count = 0;
		foreach ( $posts as $post_id ) {

			// delete merchant image
			if ( $image ) {
				$this->skus_expired_media( $post_id );
			}
				
			// delete post
			$ret = wp_delete_post( $post_id, true );
			if ( $ret !== false ) { $count ++; }

			// slowly
			if ( $count % 25 === 0 ) {
				if ( $this->debug ) { eskimo_log( 'Deleted... [' . $count . ']', 'cron' ); }
				sleep( 10 );
			}
		}

		if ( $this->debug ) { eskimo_log( 'Done...[' . $count . ']', 'cron' ); }
	}

	/**
	 * Get products from SKU ID
	 *
	 * @param	integer			$sku_id
	 * @return	boolean|false
	 */
	protected function skus_get_post( $sku_id ) {
        
        // Set up query
        $args = [
            'post_type'     => [ 'product_variation', 'product' ],
            'post_status'   => 'publish',
            'nopaging'      => true,
            'cache_results' => false
        ];

        // Test array or string
        $args['meta_query'] = [
            [
		        'key'     => '_sku',
		        'value'   => $sku_id,
		        'compare' => '='
            ]
        ];

        // Process query
        $the_query = new WP_Query( $args );

        // Found post sku?
        return ( $the_query->found_posts > 0 ) ? $the_query->posts[0]->ID : false;
	}

	/**
	 * Delete associated media attachments
	 *
	 * @param integer	$post_id
	 */
	protected function skus_expired_media( $post_id ) {

		// Get child attachment files
		$media = get_children( [
			'post_parent' => $post_id,
			'post_type'   => 'attachment',
			'numberposts' => -1,
			'post_status' => 'any' 
		] );
		if ( empty( $media ) ) { return; }

		// Iterate and delete
		$count = 0;
		foreach( $media as $post ) {
			wp_delete_attachment( $post->ID, true );
			$count++;
		}

		if ( $this->debug ) { eskimo_log( 'Deleted Images... [' . $count . ']', 'cron' ); }
	}

	//----------------------------------------------
    //  Cron Functionality - Product Variations
    //----------------------------------------------

	/**
	 * Get variable products with no variations
	 */
	public function skus_product_variations() {

		// variations?
		$variation 	= (int) filter_input( INPUT_GET, 'eskimo_var', FILTER_SANITIZE_NUMBER_INT );
		$process	= (int) filter_input( INPUT_GET, 'eskimo_process', FILTER_SANITIZE_NUMBER_INT );
		$delete		= (int) filter_input( INPUT_GET, 'eskimo_delete', FILTER_SANITIZE_NUMBER_INT );

		// check & run
		if ( $variation === 0 || $process === 0 ) { return; }
		$this->skus_do_product_variations();
	}

	/**
	 * Variable products with no variations
	 *
	 * @param	integer	$delete default 0
	 */
	public function skus_do_product_variations( $delete = 0 ) { 
	
		$products = $this->skus_variable_products();
		if ( false === $products ) { return; }
		
		if ( $this->debug ) { eskimo_log( '=== SKUs Product Variations [' . count( $products ) . '] ===', 'cron' ); }

		// Get variable products with variations
		foreach ( $products as $k => $product ) {
			$variants = $this->skus_variations( $product->get_id() );
			if ( $this->debug ) { eskimo_log( 'Product ID[' . $product->get_id() . '] Variants[' . $variants . ']', 'cron' ); }
			if ( $variants > 0 ) { 
				unset( $products[$k] );
				continue; 
			}
		}

		// Delete Products
		if ( empty( $products ) ) { return; }

		$count = 0;
		$deleted = $orphan = [];
		foreach ( $products as $k => $product ) {

			// Set product
			$product_id = $product->get_id();;

			// delete post
			if ( $delete ) {
				$ret = wp_delete_post( $product_id, true );
				if ( $ret !== false ) { 
					$count ++; 
					$deleted[] = $product_id;
				}
			} else {
				$orphan[] = $product_id;
			}

			// slowly
			if ( $delete && $count % 25 === 0 ) {
				if ( $this->debug ) { eskimo_log( 'Deleted... [' . $count . ']', 'cron' ); }
				sleep( 6 );
			}
		}

		if ( $this->debug ) {
			if ( $delete ) {	
				eskimo_log( 'Done...[' . $count . '][' . print_r( $deleted, true ). ']', 'cron' );
			} else {
				eskimo_log( 'Done...[' . print_r( $orphan, true ). ']', 'cron' );
			}
		}
	}

	/**
	 * Get product from SKU
	 *
	 * @param	integer			$limit
	 * @return	boolean|false
	 */
	protected function skus_variable_products() {
        
		// Set up query
		$args = [
			'status' 	=> 'publish', 
			'limit' 	=> -1, 
			'type' 		=> 'variable' 
		]; 

		// Process query
		$products = wc_get_products( $args );

		// Return
		return ( count( $products ) > 0 ) ? $products : false;
	}

	/**
	 * Delete associated media attachments
	 *
	 * @param integer	$post_id
	 */
	protected function skus_variations( $product_id ) {

		global $wpdb;

		$q = 'SELECT count( p.ID ) FROM wp_posts p WHERE p.post_type = "product_variation" AND p.post_parent = %d'; 
		$qs = $wpdb->prepare( $q, $product_id );	

		return (int) $wpdb->get_var( $qs );
	}
}
