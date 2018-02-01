<?php

/**
 * WordPress REST API Endpoints and EPOS API integration
 *
 * @link       https://on.tinternet.co.uk
 * @package    Eskimo
 * @subpackage Eskimo/admin
 */

/**
 * WordPress REST API Endpoints and EPOS Integration
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
     * @param   object    $rest       Eskimo_REST instance
	 * @param   string    $eskimo     The name of this plugin
	 * @param   string    $version    The version of this plugin
	 * @param   string    $version    Plugin debugging mode, default false
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
     *
     * @return void
     */
    public function register_routes() {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // First check is EPOS API enabled?
        $eskimo_api_enabled = get_option( 'eskimo_api_enabled', 'no' );
        if ( $eskimo_api_enabled !== 'yes' ) { return; }

        // Default Eskimo EPOS REST namespace
        $namespace = 'eskimo/v1';
        
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

        // Category ID
        register_rest_route( $namespace, '/category/(?P<cat_id>[\w-]+)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_categories_specific_id' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
                    'id' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return preg_match( '/(a-zA-Z0-9-_)+/', $param );
                        }
                    ],
                ]
            ] 
        ] );

        // Categories
        register_rest_route( $namespace, '/child-categories/(?P<cat_id>[\w-]+)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_categories_child_categories' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
                    'id' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return preg_match( '/(a-zA-Z0-9-_)+/', $param );
                        }
                    ],
                ]
            ] 
        ] );

        // Categories Update: Reset all categories Web_ID
        register_rest_route( $namespace, '/categories-update', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'update_categories_cart_id' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => []
            ] 
        ] );

        // Categories
        register_rest_route( $namespace, '/category-update/(?P<cat_id>[\w-]+)/(?P<cat_value>[\w-]+)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'update_category_cart_id' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
                    'cat_id' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return preg_match( '/(a-zA-Z0-9-_)+/', $param );
                        }
                    ],
                    'cat_value' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return preg_match( '/(a-zA-Z0-9-_)+/', $param );
                        }
                    ]
                ]
            ] 
        ] );

        // Category Products: Range - select 20 products from starting point 'start' - Deprecated - use /products
        register_rest_route( $namespace, '/category-products/(?P<start>[\d]+)/?(?P<records>[\d]*)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_category_products' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
                    'start' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return preg_match( '/(0-9)+/', $param );
                        }
                    ],
                    'records' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return preg_match( '/(0-9)+/', $param );
                        }
                    ]    
                ]
            ] 
        ] );

        // Category Products: All - Deprecated - use /products-all
        register_rest_route( $namespace, '/category-products-all', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_category_products_all' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => []
            ] 
        ] );


        // Products: Range
        register_rest_route( $namespace, '/products/(?P<start>[\d]+)/?(?P<records>[\d]*)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_products' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => []
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

        // Product: ID
        register_rest_route( $namespace, '/product/(?P<prod_id>[\w-]+)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_products_specific_id' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
                    'prod_id' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return preg_match( '/(a-zA-Z0-9-_)+/', $param );
                        }
                    ],
                ]
            ] 
        ] );

        // Products Update: Reset all products Web_ID
        register_rest_route( $namespace, '/products-update/?(?P<start>[\d]*)/?(?P<records>[\d]*)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'update_products_cart_id' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
                    'start' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return preg_match( '/(0-9)+/', $param );
                        }
                    ],
                    'records' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return preg_match( '/(0-9)+/', $param );
                        }
                    ]    
                ]
            ] 
        ] );

        // Product Update
        register_rest_route( $namespace, '/product-update/(?P<prod_id>[\w-]+)/(?P<prod_value>[\w-]+)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'update_product_cart_id' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
                    'prod_id' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return preg_match( '/(a-zA-Z0-9-_)+/', $param );
                        }
                    ],
                    'prod_value' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return preg_match( '/(a-zA-Z0-9-_)+/', $param );
                        }
                    ]
                ]
            ] 
        ] );

        // Orders: All
        register_rest_route( $namespace, '/orders', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_orders_create_all' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => []
            ] 
        ] );

        // Order: ID
        register_rest_route( $namespace, '/order/(?P<order_id>[\w-]+)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_order_update_id' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
                    'id' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return preg_match( '/(a-zA-Z0-9)+/', $param );
                        }
                    ],
                ]
            ] 
        ] );

        // Customers: All
        register_rest_route( $namespace, '/customers-create', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_customers_create_all' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => []
            ] 
        ] );

        // Customers: ID
        register_rest_route( $namespace, '/customer-create/(?P<customer_id>[\w-]+)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_customers_create_id' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
                    'id' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return preg_match( '/(a-zA-Z0-9)+/', $param );
                        }
                    ],
                ]
            ] 
        ] );

        // Customer: ID
        register_rest_route( $namespace, '/customer-update/(?P<customer_id>[\w-]+)', [
            [
                'methods'               => WP_REST_Server::READABLE,
                'callback'              => [ $this, 'get_customers_update_id' ],
                //'permission_callback'   => [ $this, 'rest_permissions_check' ],
                //'permission_callback'   => function() { return current_user_can( 'edit_posts' ); },
                'args'                  => [
                    'id' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return preg_match( '/(a-zA-Z0-9)+/', $param );
                        }
                    ],
                ]
            ] 
        ] );
    }

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
     * @return  WP_Error|object $prepared_item
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
     * Process epos categories import
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_categories_all( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Response data
        $data = [
            'route'     => 'categories',
            'params'    => 'all',
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
        $data['result'] = $this->rest->get_categories_all();
        if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // Process category update?
        if ( false !== $data['result'] && !empty( $data['result'] ) ) {
            $upd_cat_id = $this->rest->get_categories_update_cart_ID( $data['result'] );
            if ( $this->debug ) { error_log( 'Upd Cat ID[' . $upd_cat_id . ']' ); }
        }

        // REST output
        return new WP_REST_Response( $data, 200 );
    }

    /**
     * Process epos categories import
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_categories_specific_id( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Get ID param
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
        if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // Process category update?
        if ( false !== $data['result'] && !empty( $data['result'] ) ) {
            $upd_cat_id = $this->rest->get_categories_update_cart_ID( $data['result'] );
            if ( $this->debug ) { error_log( 'Upd Cat ID[' . $upd_cat_id . ']' ); }
        }

        // REST output
        return new WP_REST_Response( $data, 200 );
    }

    /**
     * Process epos categories import
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_categories_child_categories( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

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
        if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // Process category update?
        if ( false !== $data['result'] && !empty( $data['result'] ) ) {
            $upd_cat_id = $this->rest->get_categories_update_cart_ID( $data['result'] );
            if ( $this->debug ) { error_log( 'Upd Cat ID[' . $upd_cat_id . ']' ); }
        }

        // REST output
        return new WP_REST_Response( $data, 200 );
    }

    /**
     * Process epos categories import
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function update_categories_cart_id( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Response data
        $data = [
            'route'     => 'categories-update',
            'params'    => 'all',
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];
        
        // OK, process data
        $data['result'] = $this->rest->get_categories_cart_ID();
        if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // Process category update?
        $upd_cat_id = $this->rest->get_categories_update_cart_ID( $data['result'] );
        if ( $this->debug ) { error_log( 'Upd Cat ID[' . $upd_cat_id . ']' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
    }

    /**
     * Process epos categories import
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function update_category_cart_id( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Get ID param
        $cat_id     = str_replace( [ '-', '_' ], '|', $request->get_param( 'cat_id' ) );
        $cat_value  = $request->get_param( 'cat_value' );
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
                'Web_ID'             => ( $cat_value == 0 ) ? '0' : $cat_value
            ]
        ];

        if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // Process category update?
        $upd_cat_id = $this->rest->get_categories_update_cart_ID( $data['result'] );
        if ( $this->debug ) { error_log( 'Upd Cat ID[' . $upd_cat_id . ']' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
    }

    //----------------------------------------------
    // ImpEx CallBack Functions: Category Products
    //----------------------------------------------

    /**
     * Process epos category products import
     * - Deprecated
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_category_products_all( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Response data
        $data = [
            'route'     => 'category_products',
            'params'    => 'all',
            'range'     => '1,20',
            'nonce'     => wp_create_nonce( 'wp_rest' ),
        ];

        // OK, process data
        $data['result'] = $this->rest->get_category_products_all();
        if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // Process category update?
        if ( false !== $data['result'] && !empty( $data['result'] ) ) {
            $upd_prod_id = $this->rest->get_products_update_cart_ID( $data['result'] );
            if ( $this->debug ) { error_log( 'Upd Prod ID[' . $upd_prod_id . ']' ); }
        }

        // REST output
        return new WP_REST_Response( $data, 200 );
    }

    /**
     * Process epos categories import
     * - Deprecated
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_category_products( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Get ID param
        $start      = absint( $request->get_param( 'start' ) );
        $records    = absint( $request->get_param( 'records' ) );
        if ( $this->debug ) { error_log( 'Start[' . $start . '] Records[' . $records . ']' ); }

        // Validate Range
        if ( $start === 0 ) { return; }
        $records = ( $records === 0 || $records > 50 ) ? 20 : $records;

        // Response data
        $data = [
            'route'     => 'category_products',
            'params'    => 'range',
            'range'     => $start . ',' . $records,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];
        
        // OK, process data
        $data['result'] = $this->rest->get_category_products( $start, $records );
        if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // Process category update?
        if ( false !== $data['result'] && !empty( $data['result'] ) ) {
            $upd_prod_id = $this->rest->get_products_update_cart_ID( $data['result'] );
            if ( $this->debug ) { error_log( 'Upd Prod ID[' . $upd_prod_id . ']' ); }
        }

        // REST output
        return new WP_REST_Response( $data, 200 );
    }

    //----------------------------------------------
    // ImpEx CallBack Functions: Products
    //----------------------------------------------

    /**
     * Process EPOS products import
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
        if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // Process product update?
        if ( false !== $data['result'] && !empty( $data['result'] ) ) {
            $upd_prod_id = $this->rest->get_products_update_cart_ID( $data['result'] );
            if ( $this->debug ) { error_log( 'Upd Prod ID[' . $upd_prod_id . ']' ); }
        }

        // REST output
        return new WP_REST_Response( $data, 200 );
    }

    /**
     * Process EPOS products import
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
        $records = ( $records === 0 || $records > 50 ) ? 20 : $records;

        // Response data
        $data = [
            'route'     => 'products',
            'params'    => 'range',
            'range'     => $start . ',' . $records,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
        $data['result'] = $this->rest->get_products( $start, $records );
        if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // Process product update?
        if ( false !== $data['result'] && !empty( $data['result'] ) ) {
            $upd_prod_id = $this->rest->get_products_update_cart_ID( $data['result'] );
            if ( $this->debug ) { error_log( 'Upd Prod ID[' . $upd_prod_id . ']' ); }
        }

        // REST output
        return new WP_REST_Response( $data, 200 );
    }

    /**
     * Process EPOS products import
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function get_products_specific_id( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Get Prod ID param
        $prod_id = str_replace( [ '-', '_' ], '|', $request->get_param( 'prod_id' ) );
        if ( $this->debug ) { error_log( 'Prod ID[' . $prod_id . ']' ); }

        // Response data
        $data = [
            'route'     => 'product',
            'params'    => 'prod_id: ' . $prod_id,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
        $data['result'] = $this->rest->get_products_specific_ID( $prod_id );
        if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // Process product update?
        if ( false !== $data['result'] && !empty( $data['result'] ) ) {
            $upd_prod_id = $this->rest->get_products_update_cart_ID( $data['result'] );
            if ( $this->debug ) { error_log( 'Upd Prod ID[' . $upd_prod_id . ']' ); }
        }

        // REST output
        return new WP_REST_Response( $data, 200 );
    }

    /**
     * Process epos categories import
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function update_products_cart_id( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Get ID param
        $start      = absint( $request->get_param( 'start' ) );
        $records    = absint( $request->get_param( 'records' ) );
        if ( $this->debug ) { error_log( 'Start[' . $start . '] Records[' . $records . ']' ); }

        // Validate Range
        $start   = ( $start === 0 ) ? 1 : $start;
        $records = ( $records === 0 || $records > 100 ) ? 50 : $records;

        // Response data
        $data = [
            'route'     => 'products-update',
            'params'    => 'range',
            'range'     => $start . ',' . $records,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];

        // OK, process data
        $data['result'] = $this->rest->get_products_cart_ID( $start, $records );
        if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // Process product update?
        $upd_prod_id = $this->rest->get_products_update_cart_ID( $data['result'] );
        if ( $this->debug ) { error_log( 'Upd Prod ID[' . $upd_prod_id . ']' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
    }

    /**
     * Update an EPOS product Web_ID
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function update_product_cart_id( WP_REST_Request $request ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Get ID param
        $prod_id     = str_replace( [ '-', '_' ], '|', $request->get_param( 'prod_id' ) );
        $prod_value  = $request->get_param( 'prod_value' );
        if ( $this->debug ) { error_log( 'Prod ID[' . $prod_id . '][' . $prod_value . '][' . $cat_value . ']' ); }

        // Response data
        $data = [
            'route'     => 'product-update',
            'params'    => 'prod_id: ' . $prod_id . ' prod_value: ' . $prod_value . ' cat_value: ' . $cat_value,
            'nonce'     => wp_create_nonce( 'wp_rest' )
        ];
        
        // OK, process data
        $data['result'] = [
            [
                'Eskimo_Identifier' => $prod_id,
                'Web_ID'            => ( $prod_value == 0 ) ? '0' : $prod_value
            ]
        ];

        if ( $this->debug ) { error_log( 'Response[' . print_r( $data, true ) . ']' ); }

        // Process category update?
        $upd_prod_id = $this->rest->get_products_update_cart_ID( $data['result'] );
        if ( $this->debug ) { error_log( 'Upd Prod ID[' . $upd_prod_id . ']' ); }

        // REST output
        return new WP_REST_Response( $data, 200 );
    }

    //----------------------------------------------
    // ImpEx CallBack Functions: Category Products
    //----------------------------------------------

    /**
     * Synchronise web orders with EPOS
     * - No import required
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function update_orders_id( WP_REST_Request $request ) {

        // REST output
        return new WP_REST_Response( $data, 200 );
    }

    /**
     * Synchronise web order with EPOS
     * - No import required
     * 
     * @param   WP_REST_Request     $request Request object
     * @return  WP_REST_Response    Response object
     */
    public function update_order_id( WP_REST_Request $request ) {

        // REST output
        return new WP_REST_Response( $data, 200 );
    }

    //----------------------------------------------
    // API Timeout
    //----------------------------------------------

    /**
     * Override timeout limits on long scripts
     */
    protected function api_set_timeout() {
        set_time_limit( 0 );
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
        if ( $this->debug ) { error_log( $error ); }
    }

    /**
     * Log API Connection Error
     */
    protected function api_connect_error() {
        if ( $this->debug ) { error_log( 'API Error: Could Not Connect To API' ); }
    }

    /**
     * Log API REST Process Error
     */
    protected function api_rest_error() {
        if ( $this->debug ) { error_log( 'API Error: Could Not Retrieve REST data from API' ); }
    }
}
