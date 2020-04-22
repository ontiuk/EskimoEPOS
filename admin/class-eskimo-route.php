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
	 * The Plugin ID
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
	 * @var      object    $rest  Eskimo_REST instance
	 */
    private $rest;

	/**
	 * Initialize the class and set its properties
	 *
     * @param   object    $rest     Eskimo_REST instance
	 * @param   string    $eskimo   The name of this plugin
	 */
	public function __construct( Eskimo_REST $rest, $eskimo ) {
   
		// Set up class settings
        $this->rest     = $rest;   
		$this->eskimo   = $eskimo;
   		$this->version  = ESKIMO_VERSION;
		$this->debug    = ESKIMO_REST_DEBUG;
    	$this->base_dir	= plugin_dir_url( __FILE__ ); 
	}

    //----------------------------------------------
    // WordPress REST Config
    //----------------------------------------------

    /**
	 * Register the routes for the objects of the controller
	 * - Category
	 * - Category Product
	 * - Product
	 * - Customer
	 * - Order
	 * - SKU
     */
    public function register_routes() {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

        // First check: EskimoEPOS API enabled?
        $eskimo_api_enabled = get_option( 'eskimo_api_enabled', 'no' );
        if ( $eskimo_api_enabled !== 'yes' ) { return; }

        // Default EskimoEPOS REST namespace
        $namespace = 'eskimo/v1';

	    //----------------------------------------------
    	// WordPress REST Routes - Category
    	//----------------------------------------------

        // Categories: Retrieve All EskimoEPOS Categories
        register_rest_route( $namespace, '/categories', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_categories_all' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => []
            ] 
        ] );

        // Categories: Retrieve New EskimoEPOS Categories
        register_rest_route( $namespace, '/categories-new', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_categories_new' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => []
			]
		] );

        // Category: Retrieve Single EskimoEPOS Category By Cat ID
        register_rest_route( $namespace, '/category/(?P<cat_id>[\d]+)/(?P<cat_type>[\w]+)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_categories_specific_id' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
					'required'	=> true,
                    'cat_id' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return is_numeric( $param );
                        }
                    ],
					'cat_type' 	=> [
                        'validate_callback' => function( $param, $request, $key ) {
                            return (bool) preg_match( '/^[a-zA-Z0-9]+$/', $param );
                        }
                    ]
                ]
            ] 
        ] );

        // Categories: Retrieve Child Categories By Parent EskimoEPOS Cat ID
        register_rest_route( $namespace, '/child-categories/(?P<cat_id>[\d]+)/(?P<cat_type>[\w]+)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_categories_child_categories' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
					'required'	=> true,
                    'cat_id' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return is_numeric( $param );
                        }
                    ],
					'cat_type' 	=> [
                        'validate_callback' => function( $param, $request, $key ) {
                            return (bool) preg_match( '/^[a-zA-Z0-9]+$/', $param );
                        }
                    ]
                ]
            ] 
		] );
		
        // Categories: Update All Imported EskimoEPOS Category Web_ID values
        register_rest_route( $namespace, '/categories-update', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'update_categories_web_id' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => []
            ] 
		] );

        // Categories: Reset All EskimoEPOS Category Web_ID values to blank
        register_rest_route( $namespace, '/categories-reset', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'update_categories_cart_id' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => []
            ] 
        ] );

        // Categories: Update All Imported EskimoEPOS Category Meta ID values
        register_rest_route( $namespace, '/categories-meta', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'update_categories_meta_id' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => []
            ] 
		] );

        // Category: Update Single EskimoEPOS Category Web_ID
        register_rest_route( $namespace, '/category-update/(?P<cat_id>[\d]+)/(?P<cat_type>[\w]+)/?(?P<cat_value>[\w\-_]*)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'update_category_cart_id' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
                    'cat_id' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return is_numeric( $param );
                        }
                    ],
					'cat_type' 	=> [
                        'validate_callback' => function( $param, $request, $key ) {
                            return (bool) preg_match( '/^[a-zA-Z0-9]+$/', $param );
                        }
                    ],
                    'cat_value' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return ( empty( $param ) ) ? true : (bool) preg_match( '/[a-zA-Z0-9\-_]+/', $param );
                        }
                    ]
                ]
            ] 
        ] );

	    //----------------------------------------------
    	// WordPress REST Routes - Category Products
    	//----------------------------------------------

        // Category Products: Retrieve EskimoEPOS Category Products By Range - Deprecated - Use 'products' Endpoint
        register_rest_route( $namespace, '/category-products/(?P<start>[\d]+)/?(?P<records>[\d]*)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_category_products' ],
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

        // Category Product: Retrieve EskimoEPOS Category Products By Cat ID - Deprecated - Use 'product' Endpoint
        register_rest_route( $namespace, '/category-product/(?P<cat_id>[\d]+)/(?P<cat_type>[\w\-_]+)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_category_products_specific_category_id' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
					'required'	=> true,
                    'cat_id' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return is_numeric( $param );
                        }
                    ],
					'cat_type' 	=> [
                        'validate_callback' => function( $param, $request, $key ) {
                            return (bool) preg_match( '/^[a-zA-Z0-9\-_]+$/', $param );
                        }
                    ]
                ]
            ] 
        ] );
		
	    //----------------------------------------------
    	// WordPress REST Routes - Products
    	//----------------------------------------------

        // Products: Retrieve EskimoEPOS Products By Range
        register_rest_route( $namespace, '/products/?(?P<start>[\d]*)/?(?P<records>[\d]*)', [
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

        // Products: Retrieve EskimoEPOS Products - Warning: Resource Intensive Use Carefully!
        register_rest_route( $namespace, '/products-all', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_products_all' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => []
            ] 
		] );

        // Products: Retrieve New EskimoEPOS Products By Date Range
        register_rest_route( $namespace, '/products-new/(?P<route>[\w]+)/?(?P<created>[\d]*)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_products_new' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
                    'route' => [
						'validate_callback' => function( $param, $request, $key ) {
							return (bool) preg_match( '/^(hours|days|weeks|months|timestamp|all)$/', $param );
                        }
                    ],
                    'created' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return ( empty( $param ) ) ? true : is_numeric( $param );
                        }
                    ]    
                ]
            ] 
        ] );

        // Products: Retrieve EskimoEPOS Products By Range And Last Modified Date
        register_rest_route( $namespace, '/products-modified/(?P<route>[\w]+)/?(?P<modified>[\d]*)/?(?P<start>[\d]*)/?(?P<records>[\d]*)', [
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
                            return ( empty( $param ) ) ? true : is_numeric( $param );
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

        // Product: Retrieve Single EskimoEPOS Product By Prod ID
        register_rest_route( $namespace, '/product/(?P<prod_id>[\d]+)/(?P<style_ref>[\w]+)/?(?P<trade_id>[\w\-_]*)/?(?P<import>[\d]*)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_products_specific_id' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
					'prod_id' => [
						'validate_callback' => function( $param, $request, $key ) {
							return ( empty( $param ) ) ? true : is_numeric( $param );
						}
					],
					'style_ref' => [
						'validate_callback' => function( $param, $request, $key ) {
							return (bool) preg_match( '/^[a-zA-Z0-9]+$/', $param );
						}
					],
					'trade_id' => [
						'validate_callback' => function( $param, $request, $key ) {
							return ( empty( $param ) ) ? true : (bool) preg_match( '/^[a-zA-Z0-9\-_]+$/', $param );
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

        // Product: Update All EskimoEPOS Product Web_IDs from Woocommerce IDs
        register_rest_route( $namespace, '/products-update', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'update_products_web_id' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => []
            ] 
		] );

        // Product: Reset All EskimoEPOS Product Web_IDs
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

        // Product: Update Single EskimoEPOS Product Web_ID From Woocommerce ID
        register_rest_route( $namespace, '/product-update/(?P<prod_id>[\d]+)/(?P<style_ref>[\w]+)/?(?P<trade_id>[\w\-_]*)/?(?P<prod_value>[\w\-_]*)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'update_product_cart_id' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
					'prod_id' => [
						'validate_callback' => function( $param, $request, $key ) {
							return ( empty( $param ) ) ? true : is_numeric( $param );
						}
					],
					'style_ref' => [
						'validate_callback' => function( $param, $request, $key ) {
							return (bool) preg_match( '/^[a-zA-Z0-9]+$/', $param );
						}
					],
					'trade_id' => [
						'validate_callback' => function( $param, $request, $key ) {
							return ( empty( $param ) ) ? true : (bool) preg_match( '/^[a-zA-Z0-9\-_]+$/', $param );
						}
					],
                    'prod_value' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return ( empty( $param ) ) ? true : (bool) preg_match( '/^[a-zA-Z0-9\-_]+$/', $param );
                        }
                    ]
                ]
            ] 
        ] );

        // Product: Update EskimoEPOS Product Data By Type And Prod ID 
        register_rest_route( $namespace, '/product-import/(?P<prod_type>[\w]+)/(?P<prod_id>[\d]+)/(?P<style_ref>[\w]+)/?(?P<trade_id>[\w\-_]*)', [
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
							return ( empty( $param ) ) ? true : is_numeric( $param );
						}
					],
					'style_ref' => [
						'validate_callback' => function( $param, $request, $key ) {
							return (bool) preg_match( '/^[a-zA-Z0-9]+$/', $param );
						}
					],
					'trade_id' => [
						'validate_callback' => function( $param, $request, $key ) {
                            return ( empty( $param ) ) ? true : (bool) preg_match( '/^[a-zA-Z0-9\-_]+$/', $param );
						}
					]
                ]
            ] 
		] );
		
        // Product: Update EskimoEPOS Product Data By Type And Prod ID 
        register_rest_route( $namespace, '/product-adjust/(?P<prod_id>[\d]+)/(?P<style_ref>[\w]+)/?(?P<trade_id>[\w\-_]*)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_products_import_id' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
   					'prod_id' => [
						'validate_callback' => function( $param, $request, $key ) {
							return ( empty( $param ) ) ? true : is_numeric( $param );
						}
					],
					'style_ref' => [
						'validate_callback' => function( $param, $request, $key ) {
							return (bool) preg_match( '/^[a-zA-Z0-9]+$/', $param );
						}
					],
					'trade_id' => [
						'validate_callback' => function( $param, $request, $key ) {
                            return ( empty( $param ) ) ? true : (bool) preg_match( '/^[a-zA-Z0-9\-_]+$/', $param );
						}
					]
                ]
            ] 
		] );
		
        // Product: Custom Update EskimoEPOS Product Trade Data By Type And Prod ID 
        register_rest_route( $namespace, '/product-trade/(?P<prod_id>[\d]+)/(?P<style_ref>[\w]+)/(?P<trade_id>[\w\-_]+)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_products_trade_id' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
                    'prod_id' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return ( empty( $param ) ) ? true : is_numeric( $param );
                        }
                    ],
   					'style_ref' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return (bool) preg_match( '/^[a-zA-Z0-9]+$/', $param );
                        }
                    ],
                    'trade_id' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return (bool) preg_match( '/^[a-zA-Z0-9\-_]+$/', $param );
                        }
					]
                ]
            ] 
        ] );

	    //----------------------------------------------
    	// WordPress REST Routes - Customers
    	//----------------------------------------------

        // Customer: Import EskimoEPOS Customers By Cust ID 
        register_rest_route( $namespace, '/customer/(?P<cust_id>[\d\-]+)/?(?P<import>[\d]*)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_customers_specific_id' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
                    'cust_id' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return (bool) preg_match( '/^\d{3}[-]\d{6}$/', $param );
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
		
        // Customer: Check If An EskimoEPOS Customer Exists By Customer Email
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

        // Customer: Export Woocommerce User To EskimoEPOS Customer By Woocommerce User ID
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

        // Customer: Update EskimoEPOS Customer By Woocommerce User ID
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

        // Customer: Retrieve EskimoEPOS Customer Titles 
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
        register_rest_route( $namespace, '/order/(?P<order_id>[\w\-]+)/?(?P<import>[\d]*)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_orders_website_order' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
                    'order_id' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return preg_match( '/^[a-zA-Z0-9\-]+$/', $param );
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

        // Order: Export Order To EskimoEPOS By Woocommerce Order ID
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
		
        // Order: Export Order To EskimoEPOS By Woocommerce Order ID
        register_rest_route( $namespace, '/order-return/(?P<order_id>[\d]+)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_orders_return' ],
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

        // Order: Retrieve EskimoEPOS Fulfilment Methods
        register_rest_route( $namespace, '/order-methods', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_orders_fulfilment_methods' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => []
            ] 
		] );

        // Order: Search EskimoEPOS Orders By Cust ID
        register_rest_route( $namespace, '/order-search/customer/(?P<cust_id>[\d\-]+)/?(?P<date_from>[\d\-]*)/?(?P<date_to>[\d\-]*)', [
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
                    ],
                    'date_from' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return ( empty( $param ) ) ? true : (bool) preg_match( '/^\d{4}[-]\d{2}[-]\d{2}$/', $param );
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

        // Order: Search EskimoEPOS Orders By Type
        register_rest_route( $namespace, '/order-search/type/(?P<type_id>[\d]+)/?(?P<date_from>[\d\-]*)/?(?P<date_to>[\d\-]*)', [
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
                    ],
                    'date_from' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return ( empty( $param ) ) ? true : (bool) preg_match( '/^\d{4}[-]\d{2}[-]\d{2}$/', $param );
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

        // Order: Search EskimoEPOS Orders By Date Range
        register_rest_route( $namespace, '/order-search/date/(?P<route>[\w]+)/(?P<date_from>[\d\-]+)/?(?P<date_to>[\d\-]*)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_orders_search_date' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
				'args'                  => [
                    'route' => [
						'validate_callback' => function( $param, $request, $key ) {
                            return (bool) preg_match( '/^(from|to|range|on)$/', $param );
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

        // SKUs: Retrieve EskimoEPOS SKUs By Range
        register_rest_route( $namespace, '/skus/(?P<path>[\w]+)/?(?P<start>[\d]*)/?(?P<records>[\d]*)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_skus' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
                    'path' => [
						'validate_callback' => function( $param, $request, $key ) {
							return (bool) preg_match( '/^(all|batch)$/', $param );
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

        // SKUs: Retrieve EskimoEPOS SKUs By Range And Last Modified Date
        register_rest_route( $namespace, '/skus-modified/(?P<path>[\w]+)/(?P<route>[\w]+)/?(?P<modified>[\d]*)/?(?P<start>[\d]*)/?(?P<records>[\d]*)/?(?P<import>[\d]*)', [
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
                            return ( empty( $param ) ) ? true : is_numeric( $param );
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
		
        // SKUs: Retrieve orphaned EskimoEPOS SKUs By Range
        register_rest_route( $namespace, '/skus-orphan/?(?P<start>[\d]*)/?(?P<records>[\d]*)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_skus_orphan' ],
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

        // SKU: Retrieve Product SKUs By Prod ID
        register_rest_route( $namespace, '/skus-product/(?P<prod_id>[\d]+)/(?P<style_ref>[\w\-_]+)/?(?P<trade_id>[\w\-_]*)/?(?P<import>[\d]?)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_skus_specific_id' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
					'prod_id' => [
						'validate_callback' => function( $param, $request, $key ) {
							return ( empty( $param ) ) ? true : is_numeric( $param );
						}
					],
					'style_ref' => [
						'validate_callback' => function( $param, $request, $key ) {
							return (bool) preg_match( '/^[a-zA-Z0-9\-_]+$/', $param );
						}
					],
					'trade_id' => [
						'validate_callback' => function( $param, $request, $key ) {
	                         return ( empty( $param ) ) ? true : (bool) preg_match( '/^[a-zA-Z0-9\-_]+$/', $param );
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

        // SKU: Retrieve Single SKU By SKU Code
        register_rest_route( $namespace, '/sku/(?P<sku_id>[\w\-_]+)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_skus_specific_code' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
                    'sku_id' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return (bool) preg_match( '/^[a-zA-Z0-9\-_]+$/', $param );
                        }
                    ]
                ]
            ] 
        ] );
	}

    //----------------------------------------------
    // REST Functions
    //----------------------------------------------

    /**
     * Rest permissions post check minimum: admin level access
	 * todo: see woocommerce rest functions
	 * 
     * @param   object  $request    WC_REST_Request Instance
     * @return  boolean
     */
    public function rest_permissions_post_check( $request ) {
        $auth = $request->get_param( '_wp_rest_nonce' );
        return wp_verify_nonce( $auth, 'wp_rest' );
        //return current_user_can( 'edit_posts' );
    }



    /**
     * Rest permissions check minimum: admin level access
	 * todo: see woocommerce rest functions
     *
     * @param   object  $request    WC_REST_Request Instance
     * @return  boolean
     */
    public function rest_permissions_user_check( $request ) {
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
    // REST ImpEx CallBack Functions: Categories
    //----------------------------------------------

    /**
	 * Process EskimoEPOS categories import
	 * - Retrieve All Categories
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_categories_all( WP_REST_Request $request ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

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

		if ( $this->debug ) { eskimo_log( 'Response[' . print_r( $data, true ) . ']', 'rest' ); }

        // Process category Web_ID update
		$upd_cat_id = $this->rest->get_categories_update_cart_ID( $data['result'] );
		if ( is_wp_error( $upd_cat_id ) ) {
			return $this->rest_error( $upd_cat_id, $data );
		} 

		if ( $this->debug ) { eskimo_log( 'UPD Cat ID[' . $upd_cat_id . ']', 'rest' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
	}

    /**
	 * Process EskimoEPOS categories import
	 * - New Categories with Web_ID Check
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_categories_new( WP_REST_Request $request ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

        // Force timeout limit 0
        $this->api_set_timeout();

        // Response data
        $data = [
            'route'     => 'categories',
            'params'    => 'new',
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
        $data['result'] = $this->rest->get_categories_all();

		// WP Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}
		
        // Process category Web_ID update
		$upd_cat_id = $this->rest->get_categories_update_cart_ID( $data['result'] );
		if ( is_wp_error( $upd_cat_id ) ) {
			return $this->rest_error( $upd_cat_id, $data );
		} 

		if ( $this->debug ) { eskimo_log( 'Response[' . print_r( $data, true ) . ']', 'rest' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
    }

    /**
	 * Process EskimoEPOS categories import by ID
	 * - Single CatID with Web_ID check
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_categories_specific_id( WP_REST_Request $request ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

        // Force timeout limit 0
        $this->api_set_timeout();

        // Get Cat ID
		$cat_id 	= absint( $request->get_param( 'cat_id' ) );
		$cat_type 	= sanitize_key( $request->get_param( 'cat_type' ) );

		// Construct Cat EPOS ID
		$cat_epos_id = $cat_id . ESKIMO_REST_DELIMINATOR . $cat_type;

		if ( $this->debug ) { eskimo_log( 'Cat EPOS ID[' . $cat_epos_id . ']', 'rest' ); }

        // Response data
        $data = [
            'route'     => 'category',
            'params'    => 'cat_epos_id: ' . $cat_epos_id,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
		$data['result'] = $this->rest->get_categories_specific_ID( $cat_epos_id );

		// WP Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}

        if ( $this->debug ) { eskimo_log( 'Response[' . print_r( $data, true ) . ']', 'rest' ); }

        // Process category Web_ID Update
		$upd_cat_id = $this->rest->get_categories_update_cart_ID( $data['result'] );
		if ( is_wp_error( $upd_cat_id ) ) {
			return $this->rest_error( $upd_cat_id, $data );
		} 

		if ( $this->debug ) { eskimo_log( 'UPD Cat EPOS ID[' . $upd_cat_id . ']', 'rest' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
    }

    /**
	 * Process EskimoEPOS Child Categories import by parent ID
	 * - Parent child categories with Web_ID check 
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_categories_child_categories( WP_REST_Request $request ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

        // Force timeout limit 0
        $this->api_set_timeout();

        // Get Cat ID
		$cat_id 	= absint( $request->get_param( 'cat_id' ) );
		$cat_type 	= sanitize_key( $request->get_param( 'cat_type' ) );

		// Construct Cat EPOS ID
		$cat_epos_id = $cat_id . ESKIMO_REST_DELIMINATOR . $cat_type;

		if ( $this->debug ) { eskimo_log( 'Cat EPOS ID[' . $cat_epos_id . ']', 'rest' ); }

        // Response data
        $data = [
            'route'     => 'child-categories',
            'params'    => 'cat_epos_id: ' . $cat_epos_id,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];
        
        // OK, process data
        $data['result'] = $this->rest->get_categories_child_categories_ID( $cat_epos_id );

		// WP Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}

        if ( $this->debug ) { eskimo_log( 'Response[' . print_r( $data, true ) . ']', 'rest' ); }

        // Process category Web_ID update
		$upd_cat_id = $this->rest->get_categories_update_cart_ID( $data['result'] );
		if ( is_wp_error( $upd_cat_id ) ) {
			return $this->rest_error( $upd_cat_id, $data );
		} 
		
		if ( $this->debug ) { eskimo_log( 'UPD Cat EPOS ID[' . $upd_cat_id . ']', 'rest' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
    }

    /**
	 * Process EskimoEPOS Categories Web_ID update
	 * - Update remote category Web_ID from Woocommerce EPOS_Category_ID meta data value
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function update_categories_web_id( WP_REST_Request $request ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

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
		
		if ( $this->debug ) { eskimo_log( 'Response[' . print_r( $data, true ) . ']', 'rest' ); }
   
		// Batch update
		$batch = [];
		foreach ( $data['result'] as $result ) {
			$batch[] = $result;

			$batch_count = count( $batch );
			if ( $batch_count === 25 ) {
		
				if ( $this->debug ) { eskimo_log( 'Response[' . print_r( $batch, true ) . ']', 'rest' ); }

				// Process category Web_ID Update
				$upd_cat_id = $this->rest->get_categories_update_cart_ID( $batch );
				if ( is_wp_error( $upd_cat_id ) ) {
					return $this->rest_error( $upd_cat_id, $data );
				} 
		
		        if ( $this->debug ) { eskimo_log( 'UPD Cat ID[' . $upd_cat_id . ']', 'rest' ); }

				$batch = [];
				sleep(6);
			}
		}

        // REST output
        return new WP_REST_Response( $data, 200 );
	}

    /**
     * Reset All EskimoEPOS Category Web_ID values
	 * - Reset all remote category Web_ID values to blank
	 *
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function update_categories_cart_id( WP_REST_Request $request ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

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
		
        if ( $this->debug ) { eskimo_log( 'Response[' . print_r( $data, true ) . ']', 'rest' ); }

        // Process category Web_ID Update
		$upd_cat_id = $this->rest->get_categories_update_cart_ID( $data['result'] );
		if ( is_wp_error( $upd_cat_id ) ) {
			return $this->rest_error( $upd_cat_id, $data );
		} 
		
        if ( $this->debug ) { eskimo_log( 'Reset Cat ID[' . $upd_cat_id . ']', 'rest' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
	}

    /**
	 * Process EskimoEPOS Categories meta ID update
	 * - Synchronise internal Woocommerce category data for product count
	 * - No API Call
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function update_categories_meta_id( WP_REST_Request $request ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

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
		
		if ( $this->debug ) { eskimo_log( 'Response[' . print_r( $data, true ) . ']', 'rest' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
	}

    /**
	 * Process EskimoEPOS Category Web_ID update
	 * - Manually update single remote category Web_ID by value
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function update_category_cart_id( WP_REST_Request $request ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

        // Force timeout limit 0
        $this->api_set_timeout();

        // Get Cat ID
		$cat_id 	= absint( $request->get_param( 'cat_id' ) );
		$cat_type 	= sanitize_key( $request->get_param( 'cat_type' ) );
        $cat_value  = sanitize_text_field( $request->get_param( 'cat_value' ) );

		// Construct Cat EPOS ID
		$cat_epos_id = $cat_id . ESKIMO_REST_DELIMINATOR . $cat_type;

		if ( $this->debug ) { eskimo_log( 'Cat EPOS ID[' . $cat_epos_id . '][' . $cat_value . ']', 'rest' ); }

        // Response data
        $data = [
            'route'     => 'category-update',
            'params'    => 'cat_id: ' . $cat_epos_id . ' cat_value: ' . $cat_value,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];
        
        // OK, process data
        $data['result'] = [
            [
                'Eskimo_Category_ID' => $cat_epos_id,
                'Web_ID'             => ( $cat_value === '0' ) ? '' : $cat_value
            ]
        ];

        if ( $this->debug ) { eskimo_log( 'Response[' . print_r( $data, true ) . ']', 'rest' ); }

        // Process category Web_ID update
        $upd_cat_id = $this->rest->get_categories_update_cart_ID( $data['result'] );
		if ( is_wp_error( $upd_cat_id ) ) {
			return $this->rest_error( $upd_cat_id, $data );
		} 
		
        if ( $this->debug ) { eskimo_log( 'UPD Cat EPOS ID[' . $upd_cat_id . ']', 'rest' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
    }

    //--------------------------------------------------
    // REST ImpEx CallBack Functions: Category Products
    //--------------------------------------------------

    /**
	 * Process EskimoEPOS category products import 
	 * - Deprecated, use /products
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_category_products( WP_REST_Request $request ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

        // Force timeout limit 0
        $this->api_set_timeout();

        // Get ID param
        $start      = absint( $request->get_param( 'start' ) );
        $records    = absint( $request->get_param( 'records' ) );
        if ( $this->debug ) { eskimo_log( 'Start[' . $start . '] Records[' . $records . ']', 'rest' ); }

        // Validate Range
		$start	 	= ( $start === 0 ) ? 1 : $start;
        $records 	= ( $records === 0 || $records > 250 ) ? 250 : $records;

        // Response data
        $data = [
            'route'     => 'category-products',
            'params'    => 'range',
            'range'     => $start . ',' . $records,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];
        
        // OK, process data
        $data['result'] = $this->rest->get_category_products( $start, $records );

		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}
		
        if ( $this->debug ) { eskimo_log( 'Response[' . print_r( $data, true ) . ']', 'rest' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
	}

    /**
	 * Retrieve EskimoEPOS category products by ID
	 * - Deprecated, use /product
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_category_products_specific_category_id( WP_REST_Request $request ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

        // Force timeout limit 0
        $this->api_set_timeout();

        // Get Cat ID
		$cat_id 	= absint( $request->get_param( 'cat_id' ) );
		$cat_type 	= sanitize_key( $request->get_param( 'cat_type' ) );

		// Construct Cat EPOS ID
		$cat_epos_id = $cat_id . ESKIMO_REST_DELIMINATOR . $cat_type;

		if ( $this->debug ) { eskimo_log( 'Cat EPOS ID[' . $cat_epos_id . '][' . $cat_value . ']', 'rest' ); }

        // Response data
        $data = [
            'route'     => 'category-product',
            'params'    => 'cat_epos_id: ' . $cat_epos_id,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];
        
        // OK, process data
		$data['result'] = $this->rest->get_category_products_specific_category_ID( $cat_epos_id );

		// WP Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}

        if ( $this->debug ) { eskimo_log( 'Response[' . print_r( $data, true ) . ']', 'rest' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
    }

    //----------------------------------------------
    // REST ImpEx CallBack Functions: Products
    //----------------------------------------------

    /**
	 * Process EskimoEPOS products import
	 * - Retrieve products by batch with Web_ID check
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_products( WP_REST_Request $request ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

        // Force timeout limit 0
        $this->api_set_timeout();

        // Get ID param
        $start      = absint( $request->get_param( 'start' ) );
        $records    = absint( $request->get_param( 'records' ) );
        if ( $this->debug ) { eskimo_log( 'Start[' . $start . '] Records[' . $records . ']', 'rest' ); }

        // Validate Range
        $start		= ( $start === 0 ) ? 1 : $start;
        $records 	= ( $records === 0 || $records > 50 ) ? 50 : $records;

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

		if ( $this->debug ) { eskimo_log( 'Response[' . print_r( $data, true ) . ']', 'rest' ); }

        // Process product update?
		$upd_prod_id = $this->rest->get_products_update_cart_ID( $data['result'] );
		if ( is_wp_error( $upd_prod_id ) ) {
			return $this->rest_error( $upd_prod_id, $data );
		} 
		
		if ( $this->debug ) { eskimo_log( 'UPD Prod ID[' . $upd_prod_id . ']', 'rest' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
    }

    /**
	 * Process EskimoEPOS products import
	 * - Retrieve all products with Web_ID check - Warning very resource intensive, use with caution
	 * - Small dataset usage only, use /products with batches for large datasets
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_products_all( WP_REST_Request $request ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

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

		if ( $this->debug ) { eskimo_log( 'Response[' . print_r( $data, true ) . ']', 'rest' ); }

        // Process product update?
		$upd_prod_id = $this->rest->get_products_update_cart_ID( $data['result'] );
		if ( is_wp_error( $upd_prod_id ) ) {
			return $this->rest_error( $upd_prod_id, $data );
		} 

		if ( $this->debug ) { eskimo_log( 'UPD Prod ID[' . $upd_prod_id . ']', 'rest' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
	}

    /**
	 * Process EskimoEPOS new products import by timeframe
	 * - Retrieve products with Web_ID check using starting timestamp
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_products_new( WP_REST_Request $request ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

        // Force timeout limit 0
        $this->api_set_timeout();

        // Get ID param
        $route   	= sanitize_text_field( $request->get_param( 'route' ) );
		$created   	= absint( $request->get_param( 'created' ) );

		// Sanitize created
		$created = ( $created === 0 ) ? 1 : $created;

        if ( $this->debug ) { eskimo_log( 'Route[' . $route . '] Created[' . $created . ']', 'rest' ); }

        // Response data
        $data = [
			'route'     => 'products-new',
            'params'    => 'route[' . $route . '] created[' . $created . ']',
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
        $data['result'] = $this->rest->get_products_new( $route, $created );

		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		

		if ( $this->debug ) { eskimo_log( 'Response[' . print_r( $data, true ) . ']', 'rest' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
    }	

    /**
     * Process EskimoEPOS products import by timeframe
	 * - Retrieve modified remote products with starting timestamp
	 *
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_products_modified( WP_REST_Request $request ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

        // Force timeout limit 0
        $this->api_set_timeout();

        // Get ID param
        $route   	= sanitize_text_field( $request->get_param( 'route' ) );
        $modified   = absint( $request->get_param( 'modified' ) );
        $start      = absint( $request->get_param( 'start' ) );
        $records    = absint( $request->get_param( 'records' ) );
        if ( $this->debug ) { eskimo_log( 'Route[' . $route . '] Modified[' . $modified . '] Start[' . $start . '] Records[' . $records . ']', 'rest' ); }

        // Validate Range
        $modified 	= ( $modified === 0 ) ? 1 : $modified;
        $start 		= ( $start === 0 ) ? 1 : $start;
        $records 	= ( $records === 0 || $records > 2500 ) ? 250 : $records;

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

		if ( $this->debug ) { eskimo_log( 'Response[' . print_r( $data, true ) . ']', 'rest' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
    }	

    /**
	 * Process EskimoEPOS product import by ID
	 * - Retrieve single remote product with Web_ID check
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_products_specific_id( WP_REST_Request $request ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

        // Force timeout limit 0
        $this->api_set_timeout();

     	// Get Prod ID param
		$prod_id	= absint( $request->get_param( 'prod_id' ) );
        $style_ref 	= sanitize_text_field( $request->get_param( 'style_ref' ) );
		$trade_id 	= sanitize_text_field( $request->get_param( 'trade_id' ) );
		$import		= absint( $request->get_param( 'import' ) );

		// Sanitize trade id
		$trade_id 	= ( $trade_id === '0' ) ? '' : $trade_id;
		$import		= ( $import === 0 ) ? false : true;

		// Construct & sanitize prod id
		$prod_epos_id = str_replace( ' ', '', $prod_id . ESKIMO_REST_DELIMINATOR . $style_ref . ESKIMO_REST_DELIMINATOR . $trade_id . ESKIMO_REST_DELIMINATOR );

        if ( $this->debug ) { eskimo_log( 'Prod EPOS ID[' . $prod_epos_id . ']', 'rest' ); }

        // Response data
        $data = [
            'route'     => 'product',
            'params'    => 'prod_epos_id: ' . $prod_epos_id,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
        $data['result'] = $this->rest->get_products_specific_ID( $prod_epos_id, $import );

		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		

		if ( $this->debug ) { eskimo_log( 'Response[' . print_r( $data, true ) . ']', 'rest' ); }

		// Process product update?
		if ( $import ) {
			$upd_prod_id = $this->rest->get_products_update_cart_ID( $data['result'] );
			if ( is_wp_error( $upd_prod_id ) ) {
				return $this->rest_error( $upd_prod_id, $data );
			} 
		}
	
		if ( $this->debug ) { 
			if ( true === $import ) {
				eskimo_log( 'UPD Prod EPOS ID[' . $upd_prod_id . ']', 'rest' );
			} else {
				eskimo_log( 'Response[' . print_r( $data, true ) . ']', 'rest' );
			}
		}

        // REST output
        return new WP_REST_Response( $data, 200 );
	}
	
    /**
	 * Process EskimoEPOS Products Web_ID update
	 * - Update all remote products Web_ID from Woocommerce product
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function update_products_web_id( WP_REST_Request $request ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

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
		
		if ( $this->debug ) { eskimo_log( 'Response[' . print_r( $data, true ) . ']', 'rest' ); }
   
        // Process product update?
        $upd_prod_id = $this->rest->get_products_update_cart_ID( $data['result'] );
		if ( is_wp_error( $upd_prod_id ) ) {
			return $this->rest_error( $upd_prod_id, $data );
		} 

		if ( $this->debug ) { eskimo_log( 'UPD Prod ID[' . $upd_prod_id . ']', 'rest' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
	}

    /**
	 * Process EskimoEPOS products Web_ID updates
	 * - Reset all remote products Web_ID from woocommerce product by range
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function update_products_cart_id( WP_REST_Request $request ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

        // Force timeout limit 0
        $this->api_set_timeout();

        // Get ID param
        $start      = absint( $request->get_param( 'start' ) );
        $records    = absint( $request->get_param( 'records' ) );

        // Validate Range
        $start   = ( $start === 0 ) ? 1 : $start;
        $records = ( $records === 0 || $records > 250 ) ? 250 : $records;

        if ( $this->debug ) { eskimo_log( 'Start[' . $start . '] Records[' . $records . ']', 'rest' ); }

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
		
        if ( $this->debug ) { eskimo_log( 'Response[' . print_r( $data, true ) . ']', 'rest' ); }

        // Process product update?
        $upd_prod_id = $this->rest->get_products_update_cart_ID( $data['result'] );
		if ( is_wp_error( $upd_prod_id ) ) {
			return $this->rest_error( $upd_prod_id, $data );
		} 

		if ( $this->debug ) { eskimo_log( 'UPD Prod ID[' . $upd_prod_id . ']', 'rest' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
    }

    /**
	 * Update an EskimoEPOS product Web_ID
	 * - Set a remote product Web_ID by value
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function update_product_cart_id( WP_REST_Request $request ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

        // Force timeout limit 0
        $this->api_set_timeout();

     	// Get Prod ID param
		$prod_id	= absint( $request->get_param( 'prod_id' ) );
        $style_ref 	= sanitize_text_field( $request->get_param( 'style_ref' ) );
		$trade_id 	= sanitize_text_field( $request->get_param( 'trade_id' ) );
		$prod_value	= sanitize_text_field( $request->get_param( 'prod_value' ) );

		// Sanitize trade id
		$trade_id = ( $trade_id === '0' ) ? '' : $trade_id;

		// Construct & sanitize prod id
		$prod_epos_id = str_replace( ' ', '', $prod_id . ESKIMO_REST_DELIMINATOR . $style_ref . ESKIMO_REST_DELIMINATOR . $trade_id . ESKIMO_REST_DELIMINATOR );
		
        if ( $this->debug ) { eskimo_log( 'Prod EPOS ID[' . $prod_epos_id . '] Value[' . $prod_value . ']', 'rest' ); }

        // Response data
        $data = [
            'route'     => 'product-update',
            'params'    => 'prod_epos_id: ' . $prod_epos_id . ' prod_value: ' . $prod_value,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];
        
        // OK, process data
        $data['result'] = [
            [
                'Eskimo_Identifier' => $prod_epos_id,
                'Web_ID'            => ( $prod_value === '0' ) ? '' : $prod_value
            ]
        ];

        if ( $this->debug ) { eskimo_log( 'Response[' . print_r( $data, true ) . ']', 'rest' ); }

        // Process category update?
        $upd_prod_id = $this->rest->get_products_update_cart_ID( $data['result'] );
		if ( is_wp_error( $upd_prod_id ) ) {
			return $this->rest_error( $upd_prod_id, $data );
		} 

		if ( $this->debug ) { eskimo_log( 'UPD Prod ID[' . $upd_prod_id . ']', 'rest' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
    }

    /**
	 * Process EskimoEPOS product import by ID
	 * - Retrieve remore product data by type for update
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_products_import_id( WP_REST_Request $request ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

        // Force timeout limit 0
        $this->api_set_timeout();

     	// Get Prod ID & Type params
        $prod_type 	= sanitize_text_field( $request->get_param( 'prod_type' ) );
		$prod_id	= absint( $request->get_param( 'prod_id' ) );
        $style_ref 	= sanitize_text_field( $request->get_param( 'style_ref' ) );
		$trade_id 	= sanitize_text_field( $request->get_param( 'trade_id' ) );

		// Sanitize import route: default adjust
		$prod_type = ( empty( $prod_type ) ) ? 'adjust' : $prod_type;

		// Sanitize trade id
		$trade_id = ( $trade_id === '0' ) ? '' : $trade_id;

		// Construct & sanitize prod id
		$prod_epos_id = str_replace( ' ', '', $prod_id . ESKIMO_REST_DELIMINATOR . $style_ref . ESKIMO_REST_DELIMINATOR . $trade_id . ESKIMO_REST_DELIMINATOR );
		
        if ( $this->debug ) { eskimo_log( 'Prod EPOS ID[' . $prod_epos_id . '] Type[' . $prod_type . ']', 'rest' ); }

        // Response data
        $data = [
            'route'     => 'product-import',
            'path'     	=> $prod_type,
            'params'    => 'prod_epos_id: ' . $prod_epos_id,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
        $data['result'] = $this->rest->get_products_import_ID( $prod_epos_id, $prod_type );

		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		

		if ( $this->debug ) { eskimo_log( 'Response[' . print_r( $data, true ) . ']', 'rest' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
	}

    /**
	 * Process EskimoEPOS product trade update by ID
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_products_trade_id( WP_REST_Request $request ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest'); }

        // Force timeout limit 0
        $this->api_set_timeout();

        // Get Prod ID param
		$prod_id	= absint( $request->get_param( 'prod_id' ) );
        $style_ref 	= sanitize_text_field( $request->get_param( 'style_ref' ) );
		$trade_id 	= sanitize_text_field( $request->get_param( 'trade_id' ) );

		// Sanitize trade id
		$trade_id = ( $trade_id === '0' ) ? '' : $trade_id;

		// Sanitize refs
		$prod_ref 	= str_replace( ' ', '', $prod_id . ESKIMO_REST_DELIMINATOR . $style_ref . ESKIMO_REST_DELIMINATOR . ESKIMO_REST_DELIMINATOR );
		$trade_ref 	= str_replace( ' ', '', $prod_id . ESKIMO_REST_DELIMINATOR . $style_ref . ESKIMO_REST_DELIMINATOR . $trade_id . ESKIMO_REST_DELIMINATOR );

        if ( $this->debug ) { eskimo_log( 'Prod Ref[' . $prod_ref . '] Trade Ref[' . $trade_ref . ']', 'rest' ); }

        // Response data
        $data = [
            'route'     => 'product-trade',
            'params'    => 'prod_ref: ' . $prod_ref . ' trade_ref: ' . $trade_ref,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

		// Get and update product Web_ID
        $data['result'] = $this->rest->get_products_trade_ID( $prod_ref, $trade_ref, true );

        // REST output
   		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		

        if ( $this->debug ) { eskimo_log( 'Response[' . print_r( $data, true ) . ']', 'rest' ); }

        // Process category update?
        $upd_prod_id = $this->rest->get_products_update_cart_ID( $data['result'] );
		if ( is_wp_error( $upd_prod_id ) ) {
			return $this->rest_error( $upd_prod_id, $data );
		} 

		if ( $this->debug ) { eskimo_log( 'UPD Prod ID[' . $upd_prod_id . ']', 'rest' ); }

        // OK, process data import & update
        $data['result'] = $this->rest->get_products_import_ID( $trade_ref, 'adjust' );

		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		

		if ( $this->debug ) { eskimo_log( 'Response[' . print_r( $data, true ) . ']', 'rest' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
	}

    //----------------------------------------------
    // REST ImpEx CallBack Functions: Customers
    //----------------------------------------------

	/**
	 * Get and import an EskimoEPOS customer by ID
	 * - Retrieve remote customer and import to Woucommerce user
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
	public function get_customers_specific_id( WP_REST_Request $request ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

        // Force timeout limit 0
        $this->api_set_timeout();

		// Get ID param
        $cust_id  	= sanitize_key( $request->get_param( 'cust_id' ) );
        $import  	= absint( $request->get_param( 'import' ) );
        if ( $this->debug ) { eskimo_log( 'Customer ID[' . $cust_id . '] Import[' . $import . ']', 'rest' ); }

        // Response data
        $data = [
            'route'     => 'customer',
            'params'    => 'ID: ' . $cust_id,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

		// Validate
		$import = ( $import === 1 ) ? true : false;

        // OK, process data
		$data['result'] = $this->rest->get_customers_specific_ID( $cust_id, $import );
		
		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		

		if ( $this->debug ) { eskimo_log( 'Response[' . print_r( $data, true ) . ']', 'rest' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Check for an EskimoEPOS customer by type: email, id
	 * - Retrieve remote customer by email and import to Woocommerce user
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
	public function get_customers_specific_email( WP_REST_Request $request ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

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
	        if ( $this->debug ) { eskimo_log( 'Invalid email[' . $cust_email . ']', 'rest' ); }
	        return new WP_REST_Response( $data, 200 );
		}
        if ( $this->debug ) { eskimo_log( 'Customer Email[' . $cust_email . ']', 'rest' ); }

        // OK, process data
        $data['result'] = $this->rest->get_customers_specific_email( $cust_email );

		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		

		if ( $this->debug ) { eskimo_log( 'Response[' . print_r( $data, true ) . ']', 'rest' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Get and insert a Woocommerce customer ID to EskimoEPOS customer
	 * - Export Woocommerce user to remote customer with identifier check
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
	public function get_customers_insert( WP_REST_Request $request ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

        // Force timeout limit 0
        $this->api_set_timeout();

		// Get ID param
        $cust_id  = absint( $request->get_param( 'cust_id' ) );
        if ( $this->debug ) { eskimo_log( 'Customer ID[' . $cust_id . ']', 'rest' ); }

        // Response data
        $data = [
            'route'     => 'customer-insert',
            'params'    => 'Cust ID: ' . $cust_id,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
        $data['result'] = $this->rest->get_customers_insert( $cust_id );

		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		
		
        if ( $this->debug ) { eskimo_log( 'Response[' . print_r( $data, true ) . ']', 'rest' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Get and update a Woocommerce customer to EskimoEPOS customer
	 * = Update remote customer from Woocommerce user id
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
	public function get_customers_update( WP_REST_Request $request ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

        // Force timeout limit 0
        $this->api_set_timeout();

		// Get ID param
        $cust_id  = absint( $request->get_param( 'cust_id' ) );
        if ( $this->debug ) { eskimo_log( 'Customer ID[' . $cust_id . ']', 'rest' ); }

        // Response data
        $data = [
            'route'     => 'customer-update',
            'params'    => 'Cust ID: ' . $cust_id,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
        $data['result'] = $this->rest->get_customers_update( $cust_id );

		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		

		if ( $this->debug ) { eskimo_log( 'Response[' . print_r( $data, true ) . ']', 'rest' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Get EskimoEPOS customer titles
	 * - Retrieve remote customer titles, not currently used
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
	public function get_customers_titles( WP_REST_Request $request ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

        // Force timeout limit 0
        $this->api_set_timeout();

        // Response data
        $data = [
            'route'     => 'customer-titles',
            'params'    => 'none',
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
        $data['result'] = $this->rest->get_customers_titles();

		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		

		if ( $this->debug ) { eskimo_log( 'Response[' . print_r( $data, true ) . ']', 'rest' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
	}

    //----------------------------------------------
    // REST ImpEx CallBack Functions: Orders
    //----------------------------------------------

    /**
	 * Process EskimoEPOS WebOrder for Woocommerce import
	 * - Retrieve remote website order for import, not currently used
	 * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_orders_website_order( WP_REST_Request $request ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

        // Force timeout limit 0
        $this->api_set_timeout();

		// Get ID param
        $order_id  	= sanitize_text_field( $request->get_param( 'order_id' ) );
        $import  	= absint( $request->get_param( 'import' ) );
        if ( $this->debug ) { eskimo_log( 'Order ID[' . $order_id . '] Import[' . $import . ']', 'rest' ); }

        // Response data
        $data = [
            'route'     => 'order',
            'params'    => 'Order ID: ' . $order_id,
            'nonce'     => wp_create_nonce( 'wp_rest' )
		];

		// Sanity
		$import = ( $import === 1 ) ? true : false;

        // OK, process data
		$data['result'] = $this->rest->get_orders_website_order( $order_id, $import );
		
		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		

		if ( $this->debug ) { eskimo_log( 'Response[' . print_r( $data, true ) . ']', 'rest' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
    }

    /**
	 * Process Web Order into EskimoEPOS order
	 * - Export Woocommerce order data to new remote web order
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_orders_insert( WP_REST_Request $request ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

        // Force timeout limit 0
        $this->api_set_timeout();

		// Get ID param
        $order_id  = absint( $request->get_param( 'order_id' ) );
        if ( $this->debug ) { eskimo_log( 'Order ID #[' . $order_id . ']', 'rest' ); }

        // Response data
        $data = [
            'route'     => 'order-insert',
            'params'    => 'Order ID: #' . $order_id,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
        $data['result'] = $this->rest->get_orders_insert( $order_id );

		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		

		if ( $this->debug ) { eskimo_log( 'Response[' . print_r( $data, true ) . ']', 'rest' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
	}
	
    /**
	 * Process Web Return into EskimoEPOS order
	 * - Export Woocommerce order data to new remote web order
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_orders_return( WP_REST_Request $request ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

        // Force timeout limit 0
        $this->api_set_timeout();

		// Get ID param
        $order_id  = absint( $request->get_param( 'order_id' ) );
        if ( $this->debug ) { eskimo_log( 'Order ID #[' . $order_id . ']', 'rest' ); }

        // Response data
        $data = [
            'route'     => 'order-return',
            'params'    => 'Order ID: #' . $order_id,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
        $data['result'] = $this->rest->get_orders_return( $order_id );

		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		

		if ( $this->debug ) { eskimo_log( 'Response[' . print_r( $data, true ) . ']', 'rest' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
	}

    /**
	 * Process EskimoEPOS order fulfilment methods 
	 * - Retrieve remote order fulfilment methods, not currently used
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_orders_fulfilment_methods( WP_REST_Request $request ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

        // Force timeout limit 0
        $this->api_set_timeout();

        // Response data
        $data = [
            'route'     => 'order-methods',
            'params'    => 'none',
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
        $data['result'] = $this->rest->get_orders_fulfilment_methods();

		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		

		if ( $this->debug ) { eskimo_log( 'Response[' . print_r( $data, true ) . ']', 'rest' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
    }
	
    /**
	 * Process EskimoEPOS Order Search: Customer ID
	 * - Retrieve remote web orders by cust ID, not currently used
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_orders_search_id( WP_REST_Request $request ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

        // Force timeout limit 0
        $this->api_set_timeout();

		// Get ID param
        $cust_id  	= sanitize_text_field( $request->get_param( 'cust_id' ) );
        $date_from	= sanitize_text_field( $request->get_param( 'date_from' ) );
        $date_to	= sanitize_text_field( $request->get_param( 'date_to' ) );
		if ( $this->debug ) { eskimo_log( 'Customer ID #[' . $cust_id . '] DateFrom[' . $date_from . '] DateTo[' . $date_to . ']', 'rest' ); }

        // Response data
        $data = [
			'route'     => 'order-search',
			'path'		=> 'customer',
            'params'    => 'Cust ID: #' . $cust_id . ' From: ' . $date_from . ' To: ' . $date_to,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
        $data['result'] = $this->rest->get_orders_search_ID( $cust_id, $date_from, $date_to );

		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		

		if ( $this->debug ) { eskimo_log( 'Response[' . print_r( $data, true ) . ']', 'rest' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Process EskimoEPOS Order Search: Type
	 * - Retrieve remote web orders by type, not currently used
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_orders_search_type( WP_REST_Request $request ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

        // Force timeout limit 0
        $this->api_set_timeout();

		// Get ID param
        $type_id  	= sanitize_text_field( $request->get_param( 'type_id' ) );
        $date_from	= sanitize_text_field( $request->get_param( 'date_from' ) );
        $date_to	= sanitize_text_field( $request->get_param( 'date_to' ) );
        if ( $this->debug ) { eskimo_log( 'Type ID #[' . $type_id . ']', 'rest' ); }

        // Response data
        $data = [
			'route'     => 'order-search',
			'path'		=> 'type',
            'params'    => 'Type ID: #' . $type_id . ' From: ' . $date_from . ' To: ' . $date_to,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
        $data['result'] = $this->rest->get_orders_search_type( $type_id, $date_from, $date_to );

		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		

		if ( $this->debug ) { eskimo_log( 'Response[' . print_r( $data, true ) . ']', 'rest' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Process EskimoEPOS Order Search: Date
	 * - Retrieve remote web orders by date, not currently used
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_orders_search_date( WP_REST_Request $request ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

        // Force timeout limit 0
        $this->api_set_timeout();

		// Get ID param
        $route 		= sanitize_text_field( $request->get_param( 'route' ) );
        $date_from	= sanitize_text_field( $request->get_param( 'date_from' ) );
        $date_to	= sanitize_text_field( $request->get_param( 'date_to' ) );
        if ( $this->debug ) { eskimo_log( 'Route[' . $route . '] DateFrom[' . $date_from . '] DateTo[' . $date_to . ']', 'rest' ); }

        // Response data
        $data = [
			'route'     => 'order-search',
			'path'     	=> 'date',
			'method'	=> $route,
            'params'    => ( $route === 'on' ) ? 'On: ' . $date_from : 'From: ' . $date_from . ' To: ' . $date_to,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
        $data['result'] = $this->rest->get_orders_search_date( $route, $date_from, $date_to );

		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		

		if ( $this->debug ) { eskimo_log( 'Response[' . print_r( $data, true ) . ']', 'rest' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
	}

    //----------------------------------------------
    // REST ImpEx CallBack Functions: SKUs
    //----------------------------------------------

    /**
	 * Process EskimoEPOS SKUs import
	 * - Retrieve remote SKUs with associated product, not currently used
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_skus( WP_REST_Request $request ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

        // Force timeout limit 0
        $this->api_set_timeout();

        // Get ID param
        $path   	= sanitize_text_field( $request->get_param( 'path' ) );
        $start      = absint( $request->get_param( 'start' ) );
        $records    = absint( $request->get_param( 'records' ) );
        if ( $this->debug ) { eskimo_log( 'Path[' . $path . '] Start[' . $start . '] Records[' . $records . ']', 'rest' ); }

        // Validate Range
        $start		= ( $start === 0 ) ? 1 : $start;
        $records 	= ( $records === 0 || $records > 2500 ) ? 250 : $records;

        // Response data
        $data = [
            'route'     => 'skus',
            'params'    => 'range',
            'range'     => $path . ',' . $start . ',' . $records,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
        $data['result'] = $this->rest->get_skus( $path, $start, $records );

		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		

		if ( $this->debug ) { eskimo_log( 'Response[' . print_r( $data, true ) . ']', 'rest' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
    }

    /**
	 * Process EskimoEPOS SKUs import by timeframe
	 * - Retrieve remote SKUs with associated product by modified timeframe and batch
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_skus_modified( WP_REST_Request $request ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

        // Force timeout limit 0
        $this->api_set_timeout();

        // Get ID param
        $path   	= sanitize_text_field( $request->get_param( 'path' ) );
        $route   	= sanitize_text_field( $request->get_param( 'route' ) );
        $modified   = absint( $request->get_param( 'modified' ) );
        $start      = absint( $request->get_param( 'start' ) );
        $records    = absint( $request->get_param( 'records' ) );
        $import     = absint( $request->get_param( 'import' ) );
        if ( $this->debug ) { eskimo_log( 'Route[' . $route . '] Modified[' . $modified . '] Start[' . $start . '] Records[' . $records . '] Import[' . $import . ']', 'rest' ); }

        // Validate Range
        $modified 	= ( $modified === 0 ) ? 1 : $modified;
        $start 		= ( $start === 0 ) ? 1 : $start;
        $records 	= ( $records === 0 || $records > 2500 ) ? 250 : $records;
		$import		= ( $import === 1 ) ? true : false;

        // Response data
        $data = [
			'route'     => 'skus-modified',
            'params'    => 'range',
            'range'     => $path . ',' . $route . ',' . $modified . ',' . $start . ',' . $records . ',' . (int) $import,
            'nonce'     => wp_create_nonce( 'wp_rest' )
		];

        // OK, process data
        $data['result'] = $this->rest->get_skus_modified( $path, $route, $modified, $start, $records, $import );

		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		

		if ( $this->debug ) { eskimo_log( 'Response[' . print_r( $data, true ) . ']', 'rest' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
	}
	
    /**
	 * Process EskimoEPOS SKUs import
	 * - Retrieve remote SKUs with associated product, not currently used
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_skus_orphan( WP_REST_Request $request ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

        // Force timeout limit 0
        $this->api_set_timeout();

        // Get ID param
        $start      = absint( $request->get_param( 'start' ) );
        $records    = absint( $request->get_param( 'records' ) );
        if ( $this->debug ) { eskimo_log( 'Start[' . $start . '] Records[' . $records . ']', 'rest' ); }

        // Validate Range
        $start		= ( $start === 0 ) ? 1 : $start;
        $records 	= ( $records === 0 || $records > 1000 ) ? 250 : $records;

        // Response data
        $data = [
            'route'     => 'skus',
            'params'    => 'range',
            'range'     => $start . ',' . $records,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
        $data['result'] = $this->rest->get_skus_orphan( $start, $records );

		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		

		if ( $this->debug ) { eskimo_log( 'Response[' . print_r( $data, true ) . ']', 'rest' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
    }

    /**
	 * Process EskimoEPOS SKU import
	 * - Retrieve single remote SKU by SKU code
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_skus_specific_code( WP_REST_Request $request ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

        // Force timeout limit 0
        $this->api_set_timeout();

        // Get Prod ID param
        $sku_id =  sanitize_text_field( $request->get_param( 'sku_id' ) );
        if ( $this->debug ) { eskimo_log( 'SKU ID[' . $sku_id . ']', 'rest' ); }

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

		if ( $this->debug ) { eskimo_log( 'Response[' . print_r( $data, true ) . ']', 'rest' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
	}

    /**
	 * Process EskimoEPOS SKU import by product
	 * - Retrieve remote SKUs by associated Prod ID 
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_skus_specific_id( WP_REST_Request $request ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'rest' ); }

        // Force timeout limit 0
        $this->api_set_timeout();

     	// Get Prod ID & Type params
		$prod_id	= absint( $request->get_param( 'prod_id' ) );
        $style_ref 	= sanitize_text_field( $request->get_param( 'style_ref' ) );
		$trade_id 	= sanitize_text_field( $request->get_param( 'trade_id' ) );
        $import  	= absint( $request->get_param( 'import' ) );

		// Sanitize trade id
		$trade_id = ( $trade_id === '0' ) ? '' : $trade_id;

		// Construct & sanitize prod id
		$prod_epos_id = str_replace( ' ', '', $prod_id . ESKIMO_REST_DELIMINATOR . $style_ref . ESKIMO_REST_DELIMINATOR . $trade_id . ESKIMO_REST_DELIMINATOR );
		
        if ( $this->debug ) { eskimo_log( 'SKU Prod EPOS ID[' . $prod_epos_id . '] Import[' . $import . ']', 'rest' ); }

        // Response data
        $data = [
			'route'     => 'sku',
			'path'		=> 'product',
            'params'    => 'prod_epos_id: ' . $prod_epos_id . ', import: ' . $import,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
        $data['result'] = $this->rest->get_skus_specific_ID( $prod_epos_id, $import );

		// Error?
		if ( is_wp_error( $data['result'] ) ) {
			return $this->rest_error( $data['result'], $data );
		}		

		if ( $this->debug ) { eskimo_log( 'Response[' . print_r( $data, true ) . ']', 'rest' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
	}
	
    //----------------------------------------------
    // REST API Timeout
    //----------------------------------------------

    /**
     * Override timeout limits on long scripts
     */
    protected function api_set_timeout( $timeout = 0 ) {
        set_time_limit( $timeout );
    }

    //----------------------------------------------
    // REST API Errors
    //----------------------------------------------

    /**
     * Log API Error
     *
	 * @param   string  $error
	 * @return	object
     */
    protected function api_error( $error ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': ' . $error, 'rest' ); }
		return new WP_Error( 'data', $error );
    }

    /**
	 * Log API Connection Error
	 * 
	 * @return	object
     */
    protected function api_connect_error() {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': ' . __( 'API Error: Could Not connect to API', 'eskimo' ), 'rest' ); }
		return new WP_Error( 'api', __( 'API Error: Could Not connect to API', 'eskimo' ) );
    }

    /**
	 * Log API REST Process Error
	 * 
	 * @return	object
     */
    protected function api_rest_error() {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': ' . __( 'API Error: Could Not Retrieve REST data from API', 'eskimo' ), 'rest' ); }
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
		if ( $this->debug ) { eskimo_log( 'Response[' . print_r( $data, true ) . ']', 'rest' ); }
		return new WP_REST_Response( $data, 200 );
	}
}
