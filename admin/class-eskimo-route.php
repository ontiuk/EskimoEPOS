<?php

/**
 * WordPress REST API Endpoints and EskimoEPOS API integration
 *
 * @link       https://on.tinternet.co.uk
 * @package    Eskimo
 * @subpackage Eskimo/admin
 */

/**
 * WordPress REST API Endpoints and EskimoEPOS Integration
 * 
 * @package    Eskimo
 * @subpackage Eskimo/admin
 * @author     Stephen Betley <on@tinternet.co.uk>
 */
final class Eskimo_Route extends WP_REST_Controller {

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
	 * @var      object    $rest  Eskimo_REST instance
	 */
    private $rest;

	/**
	 * Initialize the class and set its properties
	 *
     * @param   object    $rest     Eskimo_REST instance
	 * @param   string    $eskimo   The name of this plugin
	 * @param   string    $version  The version of this plugin
	 * @param   string    $debug	Plugin debugging mode, default false
	 */
	public function __construct( Eskimo_REST $rest, $eskimo, $version, $debug = false ) {
        if ( $debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        $this->rest     = $rest;   
		$this->eskimo   = $eskimo;
		$this->version  = $version;
		$this->debug    = $debug;
    	$this->base_dir	= plugin_dir_url( __FILE__ ); 
    }

    //----------------------------------------------
    // WordPress REST Config
    //----------------------------------------------

    /**
     * Register the routes for the objects of the controller
     */
    public function register_routes() {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // First check: EskimoEPOS API enabled?
        $eskimo_api_enabled = get_option( 'eskimo_api_enabled', 'no' );
        if ( $eskimo_api_enabled !== 'yes' ) { return; }

        // Default EskimoEPOS REST namespace
        $namespace = 'eskimo/v1';

	    //----------------------------------------------
    	// WordPress REST Routes - Category
    	//----------------------------------------------

        // Categories: All
        register_rest_route( $namespace, '/categories', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_categories_all' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => []
            ] 
        ] );

        // Category: ID
        register_rest_route( $namespace, '/category/(?P<cat_id>[\w-_]+)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_categories_specific_id' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
					'required'	=> true,
					'cat_id' 	=> [
                        'validate_callback' => function( $param, $request, $key ) {
                            return (bool) preg_match( '/^[a-zA-Z0-9-_]+$/', $param );
                        }
                    ]
                ]
            ] 
        ] );

        // Categories: Child By Parent ID
        register_rest_route( $namespace, '/child-categories/(?P<cat_id>[\w-_]+)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_categories_child_categories' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
					'required'	=> true,
                    'cat_id' 	=> [
                        'validate_callback' => function( $param, $request, $key ) {
                            return (bool) preg_match( '/^[a-zA-Z0-9-_]+$/', $param );
                        }
                    ]
                ]
            ] 
        ] );

        // Categories: Update All EskimoEPOS Category Web_IDs
        register_rest_route( $namespace, '/categories-update', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'update_categories_web_id' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => []
            ] 
		] );

        // Categories: Reset All EskimoEPOS Category Web_IDs
        register_rest_route( $namespace, '/categories-reset', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'update_categories_cart_id' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => []
            ] 
        ] );

        // Categories: Update All EskimoEPOS Category Meta IDs
        register_rest_route( $namespace, '/categories-meta', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'update_categories_meta_id' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => []
            ] 
		] );

        // Category: Update EskimoEPOS Category Web_ID
        register_rest_route( $namespace, '/category-update/(?P<cat_id>[\w-_]+)/(?P<cat_value>[\w-_]+)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'update_category_cart_id' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
                    'cat_id' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return (bool) preg_match( '/[a-zA-Z0-9-_]+/', $param );
                        }
                    ],
                    'cat_value' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return (bool) preg_match( '/[a-zA-Z0-9-_]+/', $param );
                        }
                    ]
                ]
            ] 
        ] );

	    //----------------------------------------------
    	// WordPress REST Routes - Category Products
    	//----------------------------------------------

        // Category Products: Select Products By Range - Deprecated - Use 'products' Endpoint
        register_rest_route( $namespace, '/category-products/(?P<start>[\d]+)/?(?P<records>[\d]*)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_category_products_all' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
                    'start' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return is_numeric( $param );
                        }
                    ],
                    'records' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return ( empty( $param ) ) ? true : is_numeric( $param );
                        }
                    ]    
                ]
            ] 
		] );

        // Category: ID
        register_rest_route( $namespace, '/category-product/(?P<cat_id>[\w-_]+)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_category_products_specific_category' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
					'required'	=> true,
					'cat_id' 	=> [
                        'validate_callback' => function( $param, $request, $key ) {
                            return (bool) preg_match( '/^[a-zA-Z0-9-_]+$/', $param );
                        }
                    ]
                ]
            ] 
        ] );

	    //----------------------------------------------
    	// WordPress REST Routes - Products
    	//----------------------------------------------

        // Products: Select Products By Range
        register_rest_route( $namespace, '/products/(?P<start>[\d]+)/?(?P<records>[\d]*)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_products' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
                    'start' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return is_numeric( $param );
                        }
                    ],
                    'records' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return ( empty( $param ) ) ? true : is_numeric( $param );
                        }
                    ]    
                ]
            ] 
		] );

        // Products: All
        register_rest_route( $namespace, '/products-all', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_products_all' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => []
            ] 
        ] );

        // Products Modified: Select Products By Range And Last Modified Date
        register_rest_route( $namespace, '/products-modified/(?P<route>[\w]+)/(?P<modified>[\d]+)/?(?P<start>[\d]*)/?(?P<records>[\d]*)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_products_modified' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
                    'route' => [
						'validate_callback' => function( $param, $request, $key ) {
							return (bool) preg_match( '/^(seconds|minutes|hours|days|weeks|months|timestamp)$/', $param );
                        }
                    ],
                    'modified' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return is_numeric( $param );
                        }
                    ],
                    'start' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return ( empty( $param ) ) ? true : is_numeric( $param );
                        }
                    ],
                    'records' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return ( empty( $param ) ) ? true : is_numeric( $param );
                        }
                    ]    
                ]
            ] 
        ] );

        // Product: ID
        register_rest_route( $namespace, '/product/(?P<prod_id>[\w-_]+)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_products_specific_id' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
                    'prod_id' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return (bool) preg_match( '/^[a-zA-Z0-9-_]+$/', $param );
                        }
                    ],
                ]
            ] 
		] );

        // Products Update: Update All EskimoEPOS Products Web_IDs from Woocommerce
        register_rest_route( $namespace, '/products-update', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'update_products_web_id' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => []
            ] 
		] );

        // Products Reset: Reset All EskimoEPOS Product Web_IDs
        register_rest_route( $namespace, '/products-reset/?(?P<start>[\d]*)/?(?P<records>[\d]*)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'update_products_cart_id' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
                    'start' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return ( empty( $param ) ) ? true : is_numeric( $param );
                        }
                    ],
                    'records' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return ( empty( $param ) ) ? true : is_numeric( $param );
                        }
                    ]    
                ]
            ] 
        ] );

        // Product Update: Update EskimoEPOS Product Web_ID
        register_rest_route( $namespace, '/product-update/(?P<prod_id>[\w-_]+)/(?P<prod_value>[\w-_]+)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'update_product_cart_id' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
                    'prod_id' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return (bool) preg_match( '/^[a-zA-Z0-9-_]+$/', $param );
                        }
                    ],
                    'prod_value' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return (bool) preg_match( '/^[a-zA-Z0-9-_]+$/', $param );
                        }
                    ]
                ]
            ] 
        ] );

        // Product Import: ID
        register_rest_route( $namespace, '/product-import/(?P<prod_type>[\w-_]+)/(?P<prod_id>[\w-_]+)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_products_import_id' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
   					'prod_type' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return (bool) preg_match( '/^(stock|price|tax|category|adjust|all)$/', $param );
                        }
                    ],
                    'prod_id' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return (bool) preg_match( '/^[a-zA-Z0-9-_]+$/', $param );
                        }
                    ]
                ]
            ] 
        ] );

	    //----------------------------------------------
    	// WordPress REST Routes - Customers
    	//----------------------------------------------

        // Customer: Import EskimoEPOS customer By ID 
        register_rest_route( $namespace, '/customer/(?P<cust_id>[\d-]+)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_customers_specific_ID' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
                    'cust_id' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return (bool) preg_match( '/^\d{3}[-]\d{6}$/', $param );
                        }
                    ]
                ]
            ] 
		] );
		
        // Customer Exists: Get EskimoEPOS Customer By Email
        register_rest_route( $namespace, '/customer-exists/(?P<cust_email>[\w.\@\.]+)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_customers_specific_email' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
                    'cust_email' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return is_email( $param );
                        }
                    ]
                ]
            ] 
        ] );

        // Customer Insert: Export Woocommerce User To EskimoEPOS By ID
        register_rest_route( $namespace, '/customer-insert/(?P<cust_id>[\d]+)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_customers_insert' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
                    'cust_id' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return is_numeric( $param );
                        }
                    ]
                ]
            ] 
        ] );

        // Customer Update: Update EskimoEPOS By Woocommerce User ID
        register_rest_route( $namespace, '/customer-update/(?P<cust_id>[\d]+)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_customers_update' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
                    'cust_id' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return is_numeric( $param );
                        }
                    ]
                ]
            ] 
        ] );

        // Customer Titles in EskimoEPOS
        register_rest_route( $namespace, '/customer-titles', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_customers_titles' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => []
            ] 
        ] );

	    //----------------------------------------------
    	// WordPress REST Routes - Orders
    	//----------------------------------------------

        // Order: Import EskimoEPOS Order To Woocommerce By ID
        register_rest_route( $namespace, '/order/(?P<order_id>[\w.-]+)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_orders_website_order' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
                    'order_id' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return preg_match( '/^[a-zA-Z0-9-_]+$/', $param );
                        }
                    ]
                ]
            ] 
		] );

        // Order: Export Woocommerce Order To EskimoEPOS By ID
        register_rest_route( $namespace, '/order-insert/(?P<order_id>[\d]+)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_orders_insert' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
                    'order_id' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return is_numeric( $param );
                        }
                    ]
                ]
            ] 
		] );

        // Order: Fulfilment Methods
        register_rest_route( $namespace, '/order-methods', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_orders_methods' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => []
            ] 
		] );

        // Orders: Search - Customer
        register_rest_route( $namespace, '/order-search/customer/(?P<cust_id>[\d-]+)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_orders_search_id' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
				'args'                  => [
                    'cust_id' => [
						'validate_callback' => function( $param, $request, $key ) {
                            return (bool) preg_match( '/^\d{3}[-]\d{6}$/', $param );
                        }
                    ]
                ]
            ] 
        ] );

        // Orders: Search - Date, Customer, Type
        register_rest_route( $namespace, '/order-search/type/(?P<type_id>[\d]+)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_orders_search_type' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
				'args'                  => [
                    'type_id' => [
						'validate_callback' => function( $param, $request, $key ) {
							return is_numeric( $param );
                        }
                    ]
                ]
            ] 
        ] );

        // Orders: Search - Date
        register_rest_route( $namespace, '/order-search/date/(?P<route>[\w]+)/(?P<date_from>[\d-]+)/?(?P<date_to>[\d-]*)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_orders_search_date' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
				'args'                  => [
                    'route' => [
						'validate_callback' => function( $param, $request, $key ) {
                            return (bool) preg_match( '/^(from|to|range)$/', $param );
                     }
                    ],
                    'date_from' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return (bool) preg_match( '/^\d{4}[-]\d{2}[-]\d{2}$/', $param );
                        }
                    ],
                    'date_to' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return ( empty( $param ) ) ? true : (bool) preg_match( '/^\d{4}[-]\d{2}[-]\d{2}$/', $param );
                        }
                    ]
                ]
            ] 
        ] );
		
	    //----------------------------------------------
    	// WordPress REST Routes - SKUs
    	//----------------------------------------------

        // SKUs: Select EskimoEPOS SKUs By Range
        register_rest_route( $namespace, '/skus/(?P<start>[\d]+)/?(?P<records>[\d]*)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_skus' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
                    'start' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return is_numeric( $param );
                        }
                    ],
                    'records' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return ( empty( $param ) ) ? true : is_numeric( $param );
                        }
                    ]    
                ]
            ] 
		] );

        // SKUs Modified: Select SKUs By Range And Last Modified Date
        register_rest_route( $namespace, '/skus-modified/(?P<path>[\w]+)/(?P<route>[\w]+)/(?P<modified>[\d]+)/?(?P<start>[\d]*)/?(?P<records>[\d]*)/?(?P<import>[\d]*)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_skus_modified' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
                    'path' => [
						'validate_callback' => function( $param, $request, $key ) {
							return (bool) preg_match( '/^(all|stock|price)$/', $param );
                        }
                    ],
                    'route' => [
						'validate_callback' => function( $param, $request, $key ) {
							return (bool) preg_match( '/^(seconds|minutes|hours|days|weeks|months|timestamp)$/', $param );
                        }
                    ],
                    'modified' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return is_numeric( $param );
                        }
                    ],
                    'start' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return ( empty( $param ) ) ? true : is_numeric( $param );
                        }
                    ],
                    'records' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return ( empty( $param ) ) ? true : is_numeric( $param );
                        }
                    ],    
                    'import' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return ( empty( $param ) ) ? true : is_numeric( $param );
                        }
                    ]    
                ]
            ] 
        ] );

        // SKUs: ID
        register_rest_route( $namespace, '/sku/(?P<sku_id>[\w-_]+)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_skus_specific_code' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
                    'sku_id' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return (bool) preg_match( '/^[a-zA-Z0-9-_]+$/', $param );
                        }
                    ]
                ]
            ] 
        ] );

        // SKU Product: Product SKUs By Product ID
        register_rest_route( $namespace, '/sku-product/(?P<prod_id>[\w-_]+)/?(?P<import>[\d]?)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_skus_specific_id' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
                    'prod_id' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return (bool) preg_match( '/^[a-zA-Z0-9-_]+$/', $param );
                        }
                    ],
                    'import' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return ( empty( $param ) ) ? true : is_numeric( $param );
                        }
                    ]
                ]
            ] 
		] );
    }

    //----------------------------------------------
    // Class Functions
    //----------------------------------------------

    /**
     * Rest permissions check minimum: admin level access
     *
     * @param   object  $request    WC_REST_Request Instance
     * @return  boolean
     */
    public function rest_permissions_check( $request ) {
        $auth = $request->get_param( '_wp_rest_nonce' );
        return wp_verify_nonce( $auth, 'wp_rest' );
        //return current_user_can( 'edit_posts' );
    }

    /**
     * Prepare the item for create or update operation
     *
     * @param   WP_REST_Request $request Request object
     * @return  WP_Error|array $prepared_item
     */
    protected function prepare_item_for_database( $request ) {
        return [];
    }
 
    /**
     * Prepare the item for the REST response
     *
     * @param   mixed $item WordPress representation of the item.
     * @param   WP_REST_Request $request Request object.
     * @return  array
     */
    public function prepare_item_for_response( $item, $request ) {
        return [];
    }

    //----------------------------------------------
    // ImpEx CallBack Functions: Categories
    //----------------------------------------------

    /**
     * Process EskimoEPOS categories import
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_categories_all( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Force timeout limit 0
        $this->api_set_timeout();

        // Response data
        $data = [
            'route'     => 'categories',
            'params'    => 'all',
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
        $data['result'] = $this->rest->get_categories_all();

		// WP Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}

		if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // Process category Web_ID update
		$upd_cat_id = $this->rest->get_categories_update_cart_ID( $data['result'] );
		if ( is_wp_error( $upd_cat_id ) ) {
			return $this->rest_error( $upd_cat_id, $data );
		} 

		if ( $this->debug ) { error_log( 'UPD Cat ID[' . $upd_cat_id . ']' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
    }

    /**
     * Process EskimoEPOS categories import by ID
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_categories_specific_id( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Force timeout limit 0
        $this->api_set_timeout();

        // Get Cat ID
        $cat_id = str_replace( [ '-', '_' ], '|', $request->get_param( 'cat_id' ) );
        if ( $this->debug ) { error_log( 'Cat ID[' . $cat_id . ']' ); }

        // Response data
        $data = [
            'route'     => 'category',
            'params'    => 'cat_id: ' . $cat_id,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];
        
        // OK, process data
		$data['result'] = $this->rest->get_categories_specific_ID( $cat_id );

		// WP Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}

        if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // Process category Web_ID Update
		$upd_cat_id = $this->rest->get_categories_update_cart_ID( $data['result'] );
		if ( is_wp_error( $upd_cat_id ) ) {
			return $this->rest_error( $upd_cat_id, $data );
		} 

		if ( $this->debug ) { error_log( 'UPD Cat ID[' . $upd_cat_id . ']' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
    }

    /**
     * Process EskimoEPOS Child Categories import by parent ID
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_categories_child_categories( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Force timeout limit 0
        $this->api_set_timeout();

        // Get ID param
        $cat_id = str_replace( [ '-', '_' ], '|', $request->get_param( 'cat_id' ) );
        if ( $this->debug ) { error_log( 'Cat ID[' . $cat_id . ']' ); }

        // Response data
        $data = [
            'route'     => 'child-category',
            'params'    => 'cat_id: ' . $cat_id,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];
        
        // OK, process data
        $data['result'] = $this->rest->get_categories_child_categories_ID( $cat_id );

		// WP Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}

        if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // Process category Web_ID update
		$upd_cat_id = $this->rest->get_categories_update_cart_ID( $data['result'] );
		if ( is_wp_error( $upd_cat_id ) ) {
			return $this->rest_error( $upd_cat_id, $data );
		} 
		
		if ( $this->debug ) { error_log( 'UPD Cat ID[' . $upd_cat_id . ']' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
    }

    /**
     * Process EskimoEPOS Categories Web_ID update
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function update_categories_web_id( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Force timeout limit 0
        $this->api_set_timeout();

        // Response data
        $data = [
            'route'     => 'categories-update',
            'params'    => 'all',
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];
        
        // OK, process data
		$data['result'] = $this->rest->get_categories_web_ID();

		// WP Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}
		
		if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }
   
		// Batch update
		$batch = [];
		foreach ( $data['result'] as $result ) {
			$batch[] = $result;

			$batch_count = count( $batch );
			if ( $batch_count === 25 ) {
		
				if ( $this->debug ) { error_log( 'Response[' . print_r( $batch, true ) . ']' ); }

				// Process category Web_ID Update
				$upd_cat_id = $this->rest->get_categories_update_cart_ID( $batch );
				if ( is_wp_error( $upd_cat_id ) ) {
					return $this->rest_error( $upd_cat_id, $data );
				} 
		
		        if ( $this->debug ) { error_log( 'UPD Cat ID[' . $upd_cat_id . ']' ); }

				$batch = [];
				sleep(6);
			}
		}

        // REST output
        return new WP_REST_Response( $data, 200 );
	}

    /**
     * Process EskimoEPOS Categories Web_ID reset
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function update_categories_cart_id( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Force timeout limit 0
        $this->api_set_timeout();

        // Response data
        $data = [
            'route'     => 'categories-reset',
            'params'    => 'all',
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];
        
        // OK, process data - reset
		$data['result'] = $this->rest->get_categories_cart_ID();

		// WP Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}
		
        if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // Process category Web_ID Update
		$upd_cat_id = $this->rest->get_categories_update_cart_ID( $data['result'] );
		if ( is_wp_error( $upd_cat_id ) ) {
			return $this->rest_error( $upd_cat_id, $data );
		} 
		
        if ( $this->debug ) { error_log( 'Reset Cat ID[' . $upd_cat_id . ']' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
	}

    /**
     * Process EskimoEPOS Categories meta ID update
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function update_categories_meta_id( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Force timeout limit 0
        $this->api_set_timeout();

        // Response data
        $data = [
            'route'     => 'categories-meta',
            'params'    => 'all',
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];
        
        // OK, process data
		$data['result'] = $this->rest->get_categories_meta_ID();

		// WP Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}
		
		if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
	}

    /**
     * Process EskimoEPOS Category Web_ID update
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function update_category_cart_id( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Force timeout limit 0
        $this->api_set_timeout();

        // Get ID param
        $cat_id     = str_replace( [ '-', '_' ], '|', sanitize_text_field( $request->get_param( 'cat_id' ) ) );
        $cat_value  = sanitize_text_field( $request->get_param( 'cat_value' ) );
        if ( $this->debug ) { error_log( 'Cat ID[' . $cat_id . '][' . $cat_value . ']' ); }

        // Response data
        $data = [
            'route'     => 'category-update',
            'params'    => 'cat_id: ' . $cat_id . ' cat_value: ' . $cat_value,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];
        
        // OK, process data
        $data['result'] = [
            [
                'Eskimo_Category_ID' => $cat_id,
                'Web_ID'             => ( $cat_value === '0' ) ? '' : $cat_value
            ]
        ];

        if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // Process category Web_ID update
        $upd_cat_id = $this->rest->get_categories_update_cart_ID( $data['result'] );
		if ( is_wp_error( $upd_cat_id ) ) {
			return $this->rest_error( $upd_cat_id, $data );
		} 
		
        if ( $this->debug ) { error_log( 'UPD Cat ID[' . $upd_cat_id . ']' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
    }

    //----------------------------------------------
    // ImpEx CallBack Functions: Category Products
    //----------------------------------------------

    /**
	 * Process EskimoEPOS category products import 
	 * - Deprecated, use /products-all
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_category_products_all( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Force timeout limit 0
        $this->api_set_timeout();

        // Get ID param
        $start      = absint( $request->get_param( 'start' ) );
        $records    = absint( $request->get_param( 'records' ) );
        if ( $this->debug ) { error_log( 'Start[' . $start . '] Records[' . $records . ']' ); }

        // Validate Range
		$start	 	= ( $start === 0 ) ? 1 : $start;
        $records 	= ( $records === 0 || $records > 250 ) ? 250 : $records;

        // Response data
        $data = [
            'route'     => 'category_products',
            'params'    => 'range',
            'range'     => $start . ',' . $records,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];
        
        // OK, process data
        $data['result'] = $this->rest->get_category_products_all( $start, $records );

		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}
		
        if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
	}

    /**
     * Retrieve EskimoEPOS category products by ID
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_category_products_specific_category( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Force timeout limit 0
        $this->api_set_timeout();

        // Get Cat ID
        $cat_id = str_replace( [ '-', '_' ], '|', $request->get_param( 'cat_id' ) );
        if ( $this->debug ) { error_log( 'Cat ID[' . $cat_id . ']' ); }

        // Response data
        $data = [
            'route'     => 'category-product',
            'params'    => 'cat_id: ' . $cat_id,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];
        
        // OK, process data
		$data['result'] = $this->rest->get_category_products_specific_category( $cat_id );

		// WP Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}

        if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
    }

    //----------------------------------------------
    // ImpEx CallBack Functions: Products
    //----------------------------------------------

    /**
     * Process EskimoEPOS products import
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_products( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Force timeout limit 0
        $this->api_set_timeout();

        // Get ID param
        $start      = absint( $request->get_param( 'start' ) );
        $records    = absint( $request->get_param( 'records' ) );
        if ( $this->debug ) { error_log( 'Start[' . $start . '] Records[' . $records . ']' ); }

        // Validate Range
        if ( $start === 0 ) { return; }
        $records = ( $records === 0 || $records > 50 ) ? 50 : $records;

        // Response data
        $data = [
            'route'     => 'products',
            'params'    => 'range',
            'range'     => $start . ',' . $records,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
        $data['result'] = $this->rest->get_products( $start, $records );

		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		

		if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // Process product update?
		$upd_prod_id = $this->rest->get_products_update_cart_ID( $data['result'] );
		if ( is_wp_error( $upd_prod_id ) ) {
			return $this->rest_error( $upd_prod_id, $data );
		} 
		
		if ( $this->debug ) { error_log( 'UPD Prod ID[' . $upd_prod_id . ']' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
    }

    /**
     * Process EskimoEPOS products import
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_products_all( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Force timeout limit 0
        $this->api_set_timeout();

        // Response data
        $data = [
            'route'     => 'products',
            'params'    => 'all',
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
        $data['result'] = $this->rest->get_products_all();

		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		

		if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // Process product update?
		$upd_prod_id = $this->rest->get_products_update_cart_ID( $data['result'] );
		if ( is_wp_error( $upd_prod_id ) ) {
			return $this->rest_error( $upd_prod_id, $data );
		} 

		if ( $this->debug ) { error_log( 'UPD Prod ID[' . $upd_prod_id . ']' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
	}

    /**
     * Process EskimoEPOS products import by timeline
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_products_modified( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Force timeout limit 0
        $this->api_set_timeout();

        // Get ID param
        $route   	= sanitize_text_field( $request->get_param( 'route' ) );
        $modified   = absint( $request->get_param( 'modified' ) );
        $start      = absint( $request->get_param( 'start' ) );
        $records    = absint( $request->get_param( 'records' ) );
        if ( $this->debug ) { error_log( 'Route[' . $route . '] Modified[' . $modified . '] Start[' . $start . '] Records[' . $records . ']' ); }

        // Validate Range
        $start = ( $start === 0 ) ? 1 : $start;
        $records = ( $records === 0 || $records > 250 ) ? 250 : $records;

        // Response data
        $data = [
			'route'     => 'products-modified',
            'params'    => 'range',
            'range'     => $route . ',' . $modified . ',' . $start . ',' . $records,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
        $data['result'] = $this->rest->get_products_modified( $route, $modified, $start, $records );

		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		

		if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
    }	

    /**
     * Process EskimoEPOS product import by ID
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_products_specific_id( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Force timeout limit 0
        $this->api_set_timeout();

        // Get Prod ID param
        $prod_id = str_replace( [ '-', '_' ], '|', sanitize_text_field( $request->get_param( 'prod_id' ) ) );
        if ( $this->debug ) { error_log( 'Prod ID[' . $prod_id . ']' ); }

        // Response data
        $data = [
            'route'     => 'product',
            'params'    => 'prod_id: ' . $prod_id,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
        $data['result'] = $this->rest->get_products_specific_ID( $prod_id );

		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		

		if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // Process product update?
		$upd_prod_id = $this->rest->get_products_update_cart_ID( $data['result'] );
		if ( is_wp_error( $upd_prod_id ) ) {
			return $this->rest_error( $upd_prod_id, $data );
		} 

		if ( $this->debug ) { error_log( 'UPD Prod ID[' . $upd_prod_id . ']' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
	}

    /**
     * Process EskimoEPOS product import by ID
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_products_import_id( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Force timeout limit 0
        $this->api_set_timeout();

        // Get Prod ID param
        $prod_id 	= str_replace( [ '-', '_' ], '|', sanitize_text_field( $request->get_param( 'prod_id' ) ) );
        $prod_type 	= sanitize_text_field( $request->get_param( 'prod_type' ) );
        if ( $this->debug ) { error_log( 'Prod ID[' . $prod_id . ']Path[' . $prod_type . ']' ); }

        // Response data
        $data = [
            'route'     => 'product-import',
            'path'     	=> $prod_type,
            'params'    => 'prod_id: ' . $prod_id,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
        $data['result'] = $this->rest->get_products_import_ID( $prod_id, $prod_type );

		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		

		if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
	}

    /**
     * Process EskimoEPOS Products Web_ID update
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function update_products_web_id( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Force timeout limit 0
        $this->api_set_timeout();

        // Response data
        $data = [
            'route'     => 'products-update',
            'params'    => 'all',
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
		$data['result'] = $this->rest->get_products_web_ID();

		// WP Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}
		
		if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }
   
        // Process product update?
        $upd_prod_id = $this->rest->get_products_update_cart_ID( $data['result'] );
		if ( is_wp_error( $upd_prod_id ) ) {
			return $this->rest_error( $upd_prod_id, $data );
		} 

		if ( $this->debug ) { error_log( 'UPD Prod ID[' . $upd_prod_id . ']' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
	}

    /**
     * Process EskimoEPOS products Web_ID updates
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function update_products_cart_id( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Force timeout limit 0
        $this->api_set_timeout();

        // Get ID param
        $start      = absint( $request->get_param( 'start' ) );
        $records    = absint( $request->get_param( 'records' ) );
        if ( $this->debug ) { error_log( 'Start[' . $start . '] Records[' . $records . ']' ); }

        // Validate Range
        $start   = ( $start === 0 ) ? 1 : $start;
        $records = ( $records === 0 || $records > 250 ) ? 250 : $records;

        // Response data
        $data = [
            'route'     => 'products-reset',
            'params'    => 'range',
            'range'     => $start . ',' . $records,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
		$data['result'] = $this->rest->get_products_cart_ID( $start, $records );

		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		
		
        if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // Process product update?
        $upd_prod_id = $this->rest->get_products_update_cart_ID( $data['result'] );
		if ( is_wp_error( $upd_prod_id ) ) {
			return $this->rest_error( $upd_prod_id, $data );
		} 

		if ( $this->debug ) { error_log( 'UPD Prod ID[' . $upd_prod_id . ']' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
    }

    /**
     * Update an EskimoEPOS product Web_ID
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function update_product_cart_id( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Force timeout limit 0
        $this->api_set_timeout();

        // Get ID param
        $prod_id     = str_replace( [ '-', '_' ], '|', sanitize_text_field( $request->get_param( 'prod_id' ) ) );
        $prod_value  = sanitize_text_field( $request->get_param( 'prod_value' ) );
        if ( $this->debug ) { error_log( 'Prod ID[' . $prod_id . '][' . $prod_value . ']' ); }

        // Response data
        $data = [
            'route'     => 'product-update',
            'params'    => 'prod_id: ' . $prod_id . ' prod_value: ' . $prod_value,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];
        
        // OK, process data
        $data['result'] = [
            [
                'Eskimo_Identifier' => $prod_id,
                'Web_ID'            => ( $prod_value === '0' ) ? '' : $prod_value
            ]
        ];

        if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // Process category update?
        $upd_prod_id = $this->rest->get_products_update_cart_ID( $data['result'] );
		if ( is_wp_error( $upd_prod_id ) ) {
			return $this->rest_error( $upd_prod_id, $data );
		} 

		if ( $this->debug ) { error_log( 'UPD Prod ID[' . $upd_prod_id . ']' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
    }

    //----------------------------------------------
    // ImpEx CallBack Functions: Customers
    //----------------------------------------------

	/**
     * Get and import an EskimoEPOS customer by ID
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
	public function get_customers_specific_ID( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Force timeout limit 0
        $this->api_set_timeout();

		// Get ID param
        $cust_id  = sanitize_text_field( $request->get_param( 'cust_id' ) );
        if ( $this->debug ) { error_log( 'Customer ID[' . $cust_id . ']' ); }

        // Response data
        $data = [
            'route'     => 'customer',
            'params'    => 'ID: ' . $cust_id,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
		$data['result'] = $this->rest->get_customers_specific_ID( $cust_id );
		
		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		

		if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
	}

	/**
     * Get and import an EskimoEPOS customer by type: email, id
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
	public function get_customers_specific_email( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Force timeout limit 0
        $this->api_set_timeout();

		// Get email
		$cust_email = filter_var( $request->get_param( 'cust_email' ), FILTER_SANITIZE_EMAIL );

        // Response data
        $data = [
            'route'     => 'customer-exists',
            'params'    => 'Email: ' . $cust_email,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

		// Validate e-mail
		if ( ! filter_var( $cust_email, FILTER_VALIDATE_EMAIL ) ) {
			$data['result'] = 'Invalid Email[' . $cust_email . ']';
	        if ( $this->debug ) { error_log( 'Invalid email[' . $cust_email . ']' ); }
	        return new WP_REST_Response( $data, 200 );
		}
        if ( $this->debug ) { error_log( 'Customer Email[' . $cust_email . ']' ); }

        // OK, process data
        $data['result'] = $this->rest->get_customers_specific_email( $cust_email );

		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		

		if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
	}

	/**
     * Get and insert a Woocommerce customer ID to EskimoEPOS customer
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
	public function get_customers_insert( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Force timeout limit 0
        $this->api_set_timeout();

		// Get ID param
        $cust_id  = absint( $request->get_param( 'cust_id' ) );
        if ( $this->debug ) { error_log( 'Customer ID[' . $cust_id . ']' ); }

        // Response data
        $data = [
            'route'     => 'customer_insert',
            'params'    => 'ID: ' . $cust_id,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
        $data['result'] = $this->rest->get_customers_insert( $cust_id );

		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		
		
        if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
	}

	/**
     * Get and update a Woocommerce customer to EskimoEPOS customer by type: email, id
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
	public function get_customers_update( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Force timeout limit 0
        $this->api_set_timeout();

		// Get ID param
        $cust_id  = absint( $request->get_param( 'cust_id' ) );
        if ( $this->debug ) { error_log( 'Customer ID[' . $cust_id . ']' ); }

        // Response data
        $data = [
            'route'     => 'customer_update',
            'params'    => 'ID: ' . $cust_id,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
        $data['result'] = $this->rest->get_customers_update( $cust_id );

		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		

		if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
	}

	/**
     * Get EskimoEPOS customer titles
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
	public function get_customers_titles( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Force timeout limit 0
        $this->api_set_timeout();

        // Response data
        $data = [
            'route'     => 'customer_titles',
            'params'    => 'none',
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
        $data['result'] = $this->rest->get_customers_titles();

		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		

		if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
	}

    //----------------------------------------------
    // ImpEx CallBack Functions: Orders
    //----------------------------------------------

    /**
     * Retreive EskimoEPOS WebOrder for Woocommerce import
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_orders_website_order( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Force timeout limit 0
        $this->api_set_timeout();

		// Get ID param
        $order_id  = sanitize_text_field( $request->get_param( 'order_id' ) );
        if ( $this->debug ) { error_log( 'Order ID[' . $order_id . ']' ); }

        // Response data
        $data = [
            'route'     => 'order',
            'params'    => 'Order ID: ' . $order_id,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
		$data['result'] = $this->rest->get_orders_website_order( $order_id );
		
		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		

		if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
    }

    /**
     * Insert WebOrder from Woocommerce Order into EskimoEPOS order
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_orders_insert( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Force timeout limit 0
        $this->api_set_timeout();

		// Get ID param
        $order_id  = absint( $request->get_param( 'order_id' ) );
        if ( $this->debug ) { error_log( 'Order ID #[' . $order_id . ']' ); }

        // Response data
        $data = [
            'route'     => 'order_insert',
            'params'    => 'Order ID: #' . $order_id,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
        $data['result'] = $this->rest->get_orders_insert( $order_id );

		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		

		if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
	}

    /**
     * Retreiv EskimoEPOS order fulfilment methods 
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_orders_methods( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Force timeout limit 0
        $this->api_set_timeout();

        // Response data
        $data = [
            'route'     => 'order_methods',
            'params'    => 'none',
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
        $data['result'] = $this->rest->get_orders_methods();

		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		

		if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
    }
	
    /**
     * EskimoEPOS Order Search: Customer ID 
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_orders_search_id( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Force timeout limit 0
        $this->api_set_timeout();

		// Get ID param
        $cust_id  = sanitize_text_field( $request->get_param( 'cust_id' ) );
        if ( $this->debug ) { error_log( 'Customer ID #[' . $cust_id . ']' ); }

        // Response data
        $data = [
			'route'     => 'order-search',
			'path'		=> 'customer',
            'params'    => 'Customer ID: #' . $cust_id,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
        $data['result'] = $this->rest->get_orders_search_ID( $cust_id );

		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		

		if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
	}

	/**
     * EskimoEPOS Order Search: Type
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_orders_search_type( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Force timeout limit 0
        $this->api_set_timeout();

		// Get ID param
        $type_id  = sanitize_text_field( $request->get_param( 'type_id' ) );
        if ( $this->debug ) { error_log( 'Type ID #[' . $type_id . ']' ); }

        // Response data
        $data = [
			'route'     => 'order-search',
			'path'		=> 'type',
            'params'    => 'Type ID: #' . $type_id,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
        $data['result'] = $this->rest->get_orders_search_type( $type_id );

		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		

		if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
	}

	/**
     * EskimoEPOS Order Search: Date 
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_orders_search_date( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Force timeout limit 0
        $this->api_set_timeout();

		// Get ID param
        $route 		= sanitize_text_field( $request->get_param( 'route' ) );
        $date_from	= sanitize_text_field( $request->get_param( 'date_from' ) );
        $date_to	= sanitize_text_field( $request->get_param( 'date_to' ) );
        if ( $this->debug ) { error_log( 'Route[' . $route . '] DateFrom[' . $date_from . '] DateTo[' . $date_to . ']' ); }

        // Response data
        $data = [
			'route'     => 'order-search',
			'path'     	=> 'date',
			'method'	=> $route,
            'params'    => 'From: ' . $date_from . ' To: ' . $date_to,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
        $data['result'] = $this->rest->get_orders_search_date( $route, $date_from, $date_to );

		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		

		if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
	}

    //----------------------------------------------
    // ImpEx CallBack Functions: SKUs
    //----------------------------------------------

    /**
     * Process EskimoEPOS skus import
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_skus( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Force timeout limit 0
        $this->api_set_timeout();

        // Get ID param
        $start      = absint( $request->get_param( 'start' ) );
        $records    = absint( $request->get_param( 'records' ) );
        if ( $this->debug ) { error_log( 'Start[' . $start . '] Records[' . $records . ']' ); }

        // Validate Range
        if ( $start === 0 ) { return; }
        $records = ( $records === 0 || $records > 100 ) ? 100 : $records;

        // Response data
        $data = [
            'route'     => 'skus',
            'params'    => 'range',
            'range'     => $start . ',' . $records,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
        $data['result'] = $this->rest->get_skus( $start, $records );

		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		

		if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
    }

    /**
     * Process EskimoEPOS skus import by timeline
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_skus_modified( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Force timeout limit 0
        $this->api_set_timeout();

        // Get ID param
        $path   	= sanitize_text_field( $request->get_param( 'path' ) );
        $route   	= sanitize_text_field( $request->get_param( 'route' ) );
        $modified   = absint( $request->get_param( 'modified' ) );
        $start      = absint( $request->get_param( 'start' ) );
        $records    = absint( $request->get_param( 'records' ) );
        $import     = absint( $request->get_param( 'import' ) );
        if ( $this->debug ) { error_log( 'Route' . $route . '] Modified' . $modified . '] Start[' . $start . '] Records[' . $records . '] Import[' . $import . ']' ); }

        // Validate Range
        $start 		= ( $start === 0 ) ? 1 : $start;
        $records 	= ( $records === 0 || $records > 1000 ) ? 1000 : $records;

        // Response data
        $data = [
			'route'     => 'skus-modified',
            'params'    => 'range',
            'range'     => $path . ',' . $route . ',' . $modified . ',' . $start . ',' . $records . ',' . $import,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
        $data['result'] = $this->rest->get_skus_modified( $path, $route, $modified, $start, $records, $import );

		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		

		if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
    }	

    /**
     * Process EskimoEPOS sku import
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_skus_specific_code( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Force timeout limit 0
        $this->api_set_timeout();

        // Get Prod ID param
        $sku_id =  sanitize_text_field( $request->get_param( 'sku_id' ) );
        if ( $this->debug ) { error_log( 'SKU ID[' . $sku_id . ']' ); }

        // Response data
        $data = [
			'route'     => 'sku',
			'path'		=> 'code',
            'params'    => 'sku_id: ' . $sku_id,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
        $data['result'] = $this->rest->get_skus_specific_code( $sku_id );

		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		

		if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
	}

    /**
     * Process EskimoEPOS sku import by product
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_skus_specific_id( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Force timeout limit 0
        $this->api_set_timeout();

        // Get Prod ID param
        $prod_id = str_replace( [ '-', '_' ], '|', sanitize_text_field( $request->get_param( 'prod_id' ) ) );
        $import  = absint( $request->get_param( 'import' ) );
        if ( $this->debug ) { error_log( 'SKU Prod ID[' . $prod_id . '] Import[' . $import . ']' ); }

        // Response data
        $data = [
			'route'     => 'sku',
			'path'		=> 'product',
            'params'    => 'prod_id: ' . $prod_id . ', import: ' . $import,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
        $data['result'] = $this->rest->get_skus_specific_ID( $prod_id, $import );

		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		

		if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
	}
	
    //----------------------------------------------
    // API Timeout
    //----------------------------------------------

    /**
     * Override timeout limits on long scripts
     */
    protected function api_set_timeout( $timeout = 0 ) {
        set_time_limit( $timeout );
    }

    //----------------------------------------------
    // API Error
    //----------------------------------------------

    /**
     * Log API Error
     *
	 * @param   string  $error
	 * @return	object
     */
    protected function api_error( $error ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': ' . $error ); }
		return new WP_Error( 'data', $error );
    }

    /**
	 * Log API Connection Error
	 * 
	 * @return	object
     */
    protected function api_connect_error() {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': ' . __( 'API Error: Could Not connect to API', 'eskimo' ) ); }
		return new WP_Error( 'api', __( 'API Error: Could Not connect to API', 'eskimo' ) );
    }

    /**
	 * Log API REST Process Error
	 * 
	 * @return	object
     */
    protected function api_rest_error() {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': ' . __( 'API Error: Could Not Retrieve REST data from API', 'eskimo' ) ); }
		return new WP_Error( 'rest', __( 'API Error: Could Not Retrieve REST data from API', 'eskimo' ) );
	}

	/**
	 * Process REST Error
	 *
	 * @param	string 	$result
	 * @param	array	$data
	 * @return 	object 	WP_REST_Response
	 */
	protected function rest_error( $result, $data ) {
		$data['result'] = strtoupper( $result->get_error_code() ) . ': ' . $result->get_error_message(); 
		if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }
		return new WP_REST_Response( $data, 200 );
	}
}
