<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area
 *
 * @link       https://on.tinternet.co.uk
 * @package    Eskimo
 * @subpackage Eskimo/includes
 */

/**
 * The core plugin class
 *
 * Define internationalization, admin-specific hooks, and public-facing site hooks
 *
 * Also maintains the unique identifier of this plugin as well as the current version of the plugin
 *
 * @package    Eskimo
 * @subpackage Eskimo/includes
 * @author     Stephen Betley <on@tinternet.co.uk>
 */
class Eskimo {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power the plugin
	 *
	 * @var      Eskimo_Loader    $loader    Maintains and registers all hooks for the plugin
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin
	 *
	 * @var      string    $eskimo    The string used to uniquely identify this plugin
	 */
	protected $eskimo;

	/**
	 * The current version of the plugin
	 *
	 * @var      string    $version    The current version of the plugin
	 */
	protected $version;

	/**
	 * Is the plugin base directory 
	 *
	 * @var      string    $base_dir  string path for the plugin directory 
	 */
	private $base_dir;
    
    /**
	 * Is the plugin in debug mode 
	 *
	 * @var      bool    $debug    plugin is in debug mode 
	 */
    private $debug;

	/**
	 * Define the core functionality of the plugin
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site
	 */
	public function __construct() {
    
		$this->eskimo   = 'eskimo';
   		$this->version  = ESKIMO_VERSION;
		$this->debug    = ESKIMO_DEBUG;

		$this->load_dependencies();
		$this->set_locale();
    
		// Admin Objects
        $this->eskimo_admin = new Eskimo_Admin( $this->get_plugin_name(), $this->get_version(), $this->get_debug() );

		// Public Objects
		$this->eskimo_public = new Eskimo_Public( $this->get_plugin_name(), $this->get_version(), $this->get_debug() );

		// Shared Objects
        $this->eskimo_epos  = new Eskimo_EPOS( $this->get_plugin_name(), $this->get_version(), $this->get_debug() );
        $this->eskimo_api   = new Eskimo_API( $this->eskimo_epos, $this->get_plugin_name(), $this->get_version(), $this->get_debug() );
        $this->eskimo_rest  = new Eskimo_REST( $this->eskimo_api, $this->get_plugin_name(), $this->get_version(), $this->get_debug() );
        $this->eskimo_route = new Eskimo_Route( $this->eskimo_rest, $this->get_plugin_name(), $this->get_version(), $this->get_debug() );
   
		// Cart Object
        $this->eskimo_cart  = new Eskimo_Cart( $this->get_plugin_name(), $this->get_version() );

        $this->define_admin_hooks();
		$this->define_public_hooks();
        $this->define_shared_hooks();
	}

	/**
	 * Load the required dependencies for this plugin
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Eskimo_Loader: Orchestrates the hooks of the plugin
	 * - Eskimo_i18n:   Defines internationalization functionality
	 * - Eskimo_Admin:  Defines all hooks for the admin area
	 * - Eskimo_Public: Defines all hooks for the public side of the site
	 *
	 * Create an instance of the loader which will be used to register the hooks with WordPress
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the core plugin
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-eskimo-loader.php';

		/**
		 * The class responsible for defining internationalization functionality of the plugin
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-eskimo-i18n.php';

        /**
		 *  Shared class for use throughout the plugin 
		 */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-eskimo-utils.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-eskimo-cart.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-eskimo-admin.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-eskimo-epos.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-eskimo-api.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-eskimo-rest.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-eskimo-route.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-eskimo-wc.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing side of the site
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-eskimo-public.php';

        /**
         * Register the Loader
         */
		$this->loader = new Eskimo_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization
	 *
	 * Uses the Eskimo_i18n class in order to set the domain and to register the hook with WordPress
	 */
	private function set_locale() {

		$plugin_i18n = new Eskimo_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality of the plugin
	 */
	private function define_admin_hooks() {

	//	$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $this->eskimo_admin, 'enqueue_scripts' );
        
        // Woocommerce Eskimo Settings & Tab
        $this->loader->add_action( 'init', $this->eskimo_admin, 'init' );

        // Product Category Custom Category ID
		$this->loader->add_action( 'product_cat_add_form_fields', $this->eskimo_admin, 'add_eskimo_category_id',  10, 2  );
		$this->loader->add_action( 'product_cat_edit_form_fields', $this->eskimo_admin, 'edit_eskimo_category_id',  10, 2  );
		$this->loader->add_action( 'created_product_cat', $this->eskimo_admin, 'save_eskimo_category_id',  10, 2  );
		$this->loader->add_action( 'edited_product_cat', $this->eskimo_admin, 'update_eskimo_category_id',  10, 2  );

        $this->loader->add_action( 'manage_edit-product_cat_columns', $this->eskimo_admin, 'add_eskimo_category_column' );
        $this->loader->add_action( 'manage_product_cat_custom_column', $this->eskimo_admin, 'add_eskimo_category_column_content',  10, 3  );
        $this->loader->add_action( 'manage_edit-product_cat_sortable_columns', $this->eskimo_admin, 'add_eskimo_category_column_sortable' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality of the plugin
	 */
	private function define_public_hooks() {

//		$this->loader->add_action( 'wp_enqueue_scripts', $this->eskimo_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $this->eskimo_public, 'enqueue_scripts' );

        // REST API Init
		$this->loader->add_action( 'rest_api_init', $this->eskimo_route, 'register_routes' );
	}

	/**
	 * Register all of the hooks related to shared functionality of the plugin.
	 */
	private function define_shared_hooks() {
    
        // Woocommerce Settings & Eskimo EPOS Integration
		$this->loader->add_action( 'init', $this->eskimo_epos, 'init' );

		// Woocommerce customers & orders 
		$this->loader->add_action( 'woocommerce_created_customer', $this->eskimo_cart, 'customer_created', 99 );
//		$this->loader->add_action( 'profile_update', $this->eskimo_cart, 'customer_updated', 99 );
	
		// Order Processing Tests - Actions
		$order_status = get_option( 'eskimo_epos_order', '' );
		if ( $this->debug ) { error_log( 'EPOS Order Status[' . $order_status . ']' ); } 
		if ( $order_status === 'completed' ) {
			$this->loader->add_action( 'woocommerce_order_status_completed', $this->eskimo_cart, 'order_status_completed' );
		} else {
			$this->loader->add_action( 'woocommerce_order_status_processing', $this->eskimo_cart, 'order_status_processing' );
		}
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality
	 *
	 * @return    string    The name of the plugin
	 */
	public function get_plugin_name() {
		return $this->eskimo;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin
	 *
	 * @return    Eskimo_Loader    Orchestrates the hooks of the plugin
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin
	 *
	 * @return    string    The version number of the plugin
	 */
	public function get_version() {
		return $this->version;
	}

    /**
	 * Retrieve the debug status of the plugin
	 *
	 * @return    bool    The debug status of the plugin
	 */
	public function get_debug() {
		return $this->debug;
	}

	/**
	 * Get the plugin path 
	 *
	 * @return    string    The path to the plugin dir
	*/
	public static function get_path( ){ 	
		return plugin_dir_path( dirname( __FILE__ ) ); 
	}

	/**
	 * Load the plugin optional settngs
	 *
	 * @return    array    The plugin settings options
	*/
	public function load_settings( $options ) {
        $eskimo_options = get_option( 'eskimo_options' ); 
        $options = [];

        return $options;
    }
}
