<?php

/**
 * The admin-specific functionality of the plugin
 *
 * @link       https://on.tinternet.co.uk
 * @package    Eskimo
 * @subpackage Eskimo/admin
 */

/**
 * The admin UI specific functionality of the plugin
 *
 * @package    Eskimo
 * @subpackage Eskimo/admin
 * @author     Stephen Betley <on@tinternet.co.uk>
 */
final class Eskimo_Admin {

	/**
	 * Plugin ID
	 *
	 * @var	string		$eskimo		The ID of this plugin
	 */
	private $eskimo;

	/**
	 * Plugin version
	 *
	 * @var	string		$version	The current version of this plugin
	 */
	private $version;

    /**
	 * Plugin debug mode 
	 *
	 * @var	bool		$debug		Plugin debug mode - defaults to false
	 */
	private $debug;

	/**
	 * Script suffix for debugging 
	 *
	 * @var	string		$suffix		Script suffix for including minified file versions
	 */
	private $suffix;

	/**
	 * Is the plugin base directory 
	 *
	 * @var	string		$base_dir	string path for the plugin directory
	 */
    private $base_dir;

	/**
	 * Initialize the class and set its properties
	 *
	 * @param	string	$eskimo	The name of this plugin
	 */
	public function __construct( $eskimo ) {

		// Set up class settings
		$this->eskimo	= $eskimo;
   		$this->version  = ESKIMO_VERSION;
		$this->debug    = ESKIMO_TRACE;
	 	$this->base_dir	= plugin_dir_url( __FILE__ ); 
		$this->suffix	= ( true === $this->debug ) ? '' : '.min';
    }

    //----------------------------------------------
    // Core WP Settings
    //----------------------------------------------

	/**
	 * Register the stylesheets for the admin area
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->eskimo, plugin_dir_url( __FILE__ ) . 'assets/css/eskimo-admin.css', [], $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->eskimo, plugin_dir_url( __FILE__ ) . 'assets/js/eskimo-admin.js', [ 'jquery' ], $this->version, false );
    }

    //----------------------------------------------
    // Eskimo API Settings Tab
    //----------------------------------------------

    /**
     * Add a new Woocommerce Settings Tab
     */
    public function init() {

        // EPOS Category ID
        add_filter( 'woocommerce_settings_tabs_array', 				[ $this, 'add_settings_tab' ], 50 ); 
        add_action( 'woocommerce_settings_tabs_eskimo_settings', 	[ $this, 'set_tab_settings' ] ); 
        add_action( 'woocommerce_update_options_eskimo_settings', 	[ $this, 'update_tab_settings' ] ); 

        // EPOS Product ID
        add_filter( 'woocommerce_product_data_tabs', 				[ $this, 'custom_product_tab' ] );        
        add_action( 'admin_head', 									[ $this, 'custom_product_style' ] );
        add_action( 'woocommerce_product_data_panels', 				[ $this, 'category_product_custom_fields' ] ); 

        // EPOS Product List
        add_filter( 'manage_product_posts_columns', 				[ $this, 'posts_columns' ] );
        add_action( 'manage_product_posts_custom_column', 			[ $this, 'custom_columns' ], 10, 2 );

		// EPOS Account Password
		add_filter( 'woocommerce_admin_settings_sanitize_option_eskimo_epos_password', [ $this, 'update_settings_password' ], 10, 3 );
	}

	/**
	 * Check for & process password update
	 *
	 * @param	string	$value
	 * @param	array	$option
	 * @param	string	$raw_value
	 * @return	string	$value
	 */
	public function update_settings_password( $value, $option, $raw_value ) {
		eskimo_log( __CLASS__ . ' : ' . __METHOD__, 'api' );

		// password only
		if ( 'eskimo_epos_password' !== $option['id'] ) { return; }

		// Get current password
		$old_password = get_option( 'eskimo_epos_password', '' );		

		// Test new password for length, reject if not OK 
		if ( strlen( $value ) < 6 ) { return $old_password; }

		// Test new password for structure, reject if not OK
		if ( ! preg_match( '/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])[\w~@#$%^&*+=`|{}:;!,.?\'\"()\[\]-_]+$/', $value ) ) { return $old_password; }

		// Process API update? Check vs existing password
		if ( $old_password === $value ) { return $value; }

		// Initiate REST call to update EPOS order status
		$rest_url = esc_url( home_url( '/wp-json' ) ) . '/eskimo/v1/account-password/' . urlencode( $old_password ) . '/' . urlencode( $value );
		if ( $this->debug ) { eskimo_log( 'Rest URL[' . $rest_url . ']', 'api' ); }
		$response = wp_remote_get( $rest_url, [ 'timeout' => 12 ] );

		// Check the call worked
		if ( is_wp_error( $response ) ) {
			return ( $this->debug ) ? eskimo_log( 'Account Password Old [' . $old_password . '] New [' . $value . '] API Error', 'api' ) : '';
		}

		// Get the response body
		$body = wp_remote_retrieve_body( $response );

		// Check contents and parse
		if ( empty( $body ) ) {
			return ( $this->debug ) ? eskimo_log( 'Empty Password Old [' . $old_password . '] New [' . $value . '] Account', 'api' ) : '';
		}			

		// Get the body data
		$data = json_decode( $body );

		// Get any message: no error, update succesful
		return ( empty( $data->result ) ) ? $value : $old_password;
	}

    /** 
     * Add a new settings tab to the WooCommerce settings tabs array
     * 
     * @param   array   $settings_tabs  
     * @return  array   $settings_tabs  
     */ 
    public function add_settings_tab( $settings_tabs ) { 
        $settings_tabs['eskimo_settings'] = __( 'Eskimo', 'eskimo' ); 
        return $settings_tabs; 
    }

    /**
     * Uses the WooCommerce admin fields API to output settings tab settings fields
     *
     * @uses woocommerce_admin_fields() 
     */ 
    public function set_tab_settings() {
        woocommerce_admin_fields( $this->get_tab_settings() ); 
    } 

    /** 
     * Uses the WooCommerce options API to save settings
     * 
     * @uses woocommerce_update_options() 
     */ 
    public function update_tab_settings() { 
        woocommerce_update_options( $this->get_tab_settings() ); 

		// Check for updated
    } 

    /**
     * Get all the settings for this plugin for @see woocommerce_admin_fields() function
     *
     * @return  array
     */
    protected function get_tab_settings() {

        // EPOS API Settings
        $epos = [
            'epos_start'    => [
                'name'      => __( 'Eskimo EPOS API', 'eskimo' ),
                'type'      => 'title',
                'desc'      => __( 'Your Eskimo EPOS API account settings.', 'eskimo' ),
                'id'        => 'eskimo_section_epos'
            ],
            'api'           => [
                'name'      => __( 'API Enabled', 'eskimo' ),
                'type'      => 'checkbox',
                'desc'      => __( 'Enable Eskimo EPOS REST API Endpoints', 'eskimo' ),
                'default'   => 'no',
                'id'        => 'eskimo_api_enabled'
            ],            
            'domain'        => [
                'name'      => __( 'API URL', 'eskimo' ),
                'type'      => 'text',
                'desc'      => '',
                'default'   => 'https://api.eskimoepos.com/',
                'id'        => 'eskimo_epos_domain'
            ],
            'username'      => [
                'name'      => __( 'API Username', 'eskimo' ),
                'type'      => 'text',
                'desc'      => '',
                'id'        => 'eskimo_epos_username'
            ],
            'password'      => [
                'name'      => __( 'API Password', 'eskimo' ),
                'type'      => 'text',
				'desc'      => 'Minimum 6 characters, maximum 100. Must contain one of each characters a-z, A-Z, 0-9<br />Can contain a-z, A-Z, 0-9, and special characters: ~ @ # $ % ^ & * + = ` | { } : ; , ! . ? \' " ( ) [ ] - _',
                'id'        => 'eskimo_epos_password'
			],
            'status'      	=> [
                'name'      => __( 'Order Status', 'eskimo' ),
				'type'      => 'select',
				'options'       => [
					'processing' => __( 'Processing', 'classworx' ),
					'completed'	 => __( 'Completed', 'classworx' )
				],
                'desc'      => '',
                'id'        => 'eskimo_epos_order'
            ],			
            'epos_end'       => [
                 'type'     => 'sectionend',
                 'id'       => 'eskimo_section_epos_end'
             ]
        ];

        // EPOS API Settings
        $api = [
            'api_start'     => [
                'name'      => __( 'Woocommerce Data Settings', 'eskimo' ),
                'type'      => 'title',
                'desc'      => __( 'Woocommerce Web ID settings for EPOS Product, Category, and Customer data.<br/>Prefix applied to the Woocommerce product, category & customer ID to make the WebID value assigned to EPOS synched data.' ),
                'id'        => 'eskimo_section_api'
            ],
            'category'      => [
                'name'      => __( 'Category WebID Prefix', 'eskimo' ),
                'type'      => 'text',
                'desc'      => '',
                'css'       => 'width:225px;',                
                'default'   => 'cxc-',
                'id'        => 'eskimo_api_category'
            ],
            'product'       => [
                'name'      => __( 'Product WebID Prefix', 'eskimo' ),
                'type'      => 'text',
                'desc'      => '',
                'css'       => 'width:225px;',                
                'default'   => 'cxp-',
                'id'        => 'eskimo_api_product'
            ],
            'customer'      => [
                'name'      => __( 'Customer WebID Prefix', 'eskimo' ),
                'type'      => 'text',
                'desc'      => '',
                'css'       => 'width:225px;',                
                'default'   => 'cxcu-',
                'id'        => 'eskimo_api_customer'
            ],
            'api_end'       => [
                 'type'     => 'sectionend',
                 'id'       => 'eskimo_section_api_end'
             ]
        ];

        return apply_filters( 'wc_eskimo_settings', $epos + $api );
    }

    //----------------------------------------------
    // Eskimo EPOS Category Fields
    //----------------------------------------------

    /**
     * Add the Eskimo Category ID as field in Product_Cat
     *
     * @param   object  $tax	Taxonomy
     */
    public function add_eskimo_category_id( $tax ) {

        // Cat field default
        $cat_option = get_option( 'eskimo_api_category', '' );
    ?> 
        <div class="form-field term-group"> 
            <label for="eskimo_category_id"><?php _e( 'Eskimo Category ID', 'eskimo' ); ?></label> 
            <input type="text" name="eskimo_category_id" id="eskimo_category_id" value="<?php echo $cat_option; ?>"> 
        </div> 
    <?php        
    }

    /**
     * Eskimo Category ID: Edit
     *
	 * @param   object  $term	Term object
	 * @param	string	$tax	Taxonomy
     */
    public function edit_eskimo_category_id( $term, $tax ) { 

        // put the term ID into a variable 
        $t_id       = $term->term_id; 
        $term_image = get_term_meta( $t_id, 'eskimo_category_id', true );  
    ?> 
        <tr class="form-field"> 
            <th><label for="taxImage"><?php _e( 'Eskimo Category ID', 'eskimo' ); ?></label></th> 
            <td><input type="text" name="eskimo_category_id" id="eskimo_category_id" value="<?php echo esc_attr( $term_image ) ? esc_attr( $term_image ) : ''; ?>"></td> 
        </tr> 
    <?php 
    } 

    /** 
     * Eskimo Category ID: Save
     *
	 * @param   integer $term_id	Term ID
	 * @param	integer	$tt_id		Taxonomy Term ID
     */ 
    public function save_eskimo_category_id( $term_id, $tt_id ) { 

        // Check cat ID ok
        if ( isset( $_POST['eskimo_category_id'] ) && !empty( $_POST['eskimo_category_id'] ) ) { 
            $term_cat_id = sanitize_text_field( $_POST['eskimo_category_id'] ); 
            if ( $term_cat_id ) { 
                add_term_meta( $term_id, 'eskimo_category_id', $term_cat_id, true ); 
            } 
        }  
    }

    /** 
     * Eskimo Category ID: Update
     *
     * @param   integer $term_id	Term ID
     */ 
    public function update_eskimo_category_id( $term_id ) { 

        if ( isset( $_POST['eskimo_category_id'] ) && !empty( $_POST['eskimo_category_id'] ) ) { 
            $term_cat_id = sanitize_text_field( $_POST['eskimo_category_id'] ); 
            if ( $term_cat_id ) { 
                update_term_meta( $term_id, 'eskimo_category_id', $term_cat_id ); 
            } 
        }  
    }   

    /**
     * Add the Eskimo Category ID to columns
	 *
	 * @param	array	$columns
     * @return 	array	$columns
     */
    public function add_eskimo_category_column( $columns ){
        $columns['eskimo_category_id'] = __( 'Eskimo ID', 'eskimo' );
        return $columns;
    }

    /**
     * Add the Eskimo Category ID column content
	 *
	 * @param	$string		$content
	 * @param	$string		$column_name
	 * @param	integer		$term_id
     * @return 	string		$content
     */
    public function add_eskimo_category_column_content( $content, $column_name, $term_id ){

        // Test column id
        if ( $column_name !== 'eskimo_category_id' ) {
            return $content;
        }

        $term_id 			= absint( $term_id );
        $eskimo_category_id = get_term_meta( $term_id, 'eskimo_category_id', true );

        if ( !empty( $eskimo_category_id ) ) {
            $content .= esc_attr( $eskimo_category_id );
        }

        return $content;
    }

    /**
     * Make the Eskimo Category ID admin column sortable
     *
     * @param   array 	$sortable
     * @return  array 	$sortable
     */
    public function add_eskimo_category_column_sortable( $sortable ){
        $sortable['eskimo_category_id'] = 'eskimo_category_id';
        return $sortable;
    }

    //----------------------------------------------
    // Eskimo EPOS Product Fields
    //----------------------------------------------

    /** 
     * Add a custom product tab for Eskimo content
     *
     * @param   array   $tabs
     * @return  array	$tabs   
     */ 
    public function custom_product_tab( $tabs ) { 

        // Set up Eskimo Tab
        $tabs['eskimo'] = [ 
    	    'label'		=> __( 'Eskimo', 'eskimo' ),
	   	    'target'	=> 'eskimo_product_data',
            'class'		=> [ 'show_if_simple', 'show_if_variable', 'hide_if_grouped', 'hide_if_external' ],
            'priority'  => 65
        ];

     	return $tabs;  
    }

    /**
     * Custom product tab icon
     */
    public function custom_product_style() { 
        echo '<style>#woocommerce-product-data ul.wc-tabs li.eskimo_tab a:before { font-family: WooCommerce; content: "\e600"; }</style>';
    } 

    /**
     * Category ID and Product ID dsplay fields
     */
    public function category_product_custom_fields() {
  
        global $post; 

        // Category & Product IDs
        $eskimo_category_id = get_post_meta( $post->ID, '_eskimo_category_id', true );
        $eskimo_product_id  = get_post_meta( $post->ID, '_eskimo_product_id', true );

        // Translate to Category Web_ID
        $cat_list 	= wp_get_post_terms( $post->ID, 'product_cat', [ 'fields' => 'ids' ] );
		$web_prefix = get_option( 'eskimo_api_category' ); 
		$eskimo_category_web_id = ( $cat_list && !is_wp_error( $cat_list ) ) ? $web_prefix . $cat_list[0] : '';

        // Get Product Web_ID
        $web_prefix = get_option( 'eskimo_api_product' ); 
        $eskimo_product_web_id = $web_prefix . $post->ID;              

    ?>
            <div id='eskimo_product_data' class='panel woocommerce_options_panel'>
                <div class='options_group'>
    <?php 
            woocommerce_wp_text_input( [ 
	    		'id'			=> '_eskimo_category_id', 
                'label'			=> __( 'Eskimo Category ID', 'eskimo' ),
                'value'         => $eskimo_category_id, 
 			    'desc_tip'		=> 'true', 
 			    'description'	=> __( 'The Eskimo EPOS Category ID', 'eskimo' ), 
 			    'type' 			=> 'text', 
            ] ); 

            woocommerce_wp_text_input( [ 
	    		'id'			=> '_eskimo_product_id', 
 		    	'label'			=> __( 'Eskimo Product ID', 'eskimo' ), 
                'value'         => $eskimo_product_id, 
 			    'desc_tip'		=> 'true', 
 			    'description'	=> __( 'The Eskimo EPOS Product ID', 'eskimo' ), 
 			    'type' 			=> 'text', 
            ] ); 

            woocommerce_wp_text_input( [ 
	    		'id'			=> '_eskimo_category_web_id', 
                'label'			=> __( 'Eskimo Category Web_ID', 'eskimo' ),
                'value'         => $eskimo_category_web_id, 
 			    'desc_tip'		=> 'true', 
 			    'description'	=> __( 'The Eskimo EPOS Category Web_ID', 'eskimo' ), 
 			    'type' 			=> 'text', 
            ] ); 

            woocommerce_wp_text_input( [ 
	    		'id'			=> '_eskimo_product_web_id', 
 		    	'label'			=> __( 'Eskimo Product Web_ID', 'eskimo' ), 
                'value'         => $eskimo_product_web_id, 
 			    'desc_tip'		=> 'true', 
 			    'description'	=> __( 'The Eskimo EPOS Product Web_ID', 'eskimo' ), 
 			    'type' 			=> 'text', 
            ] ); 

     ?>
                </div> 
         	</div>
    <?php 
    } 

    //----------------------------------------------
    // EskimoEPOS REST ImpEx Menu
    //----------------------------------------------

    /**
	 * Display the EPOS data in the product listings
	 *
	 * @param	array	$defaults
	 * @return	array	$cols
     */
    public function posts_columns( $defaults ){

        // New cols
        $epos = [
            'epos_product_id'   => __('EPOS ID'),
            'epos_category_id'  => __('EPOS Cat ID')
        ];

        // Reconstruct
        $def_start  = array_slice( $defaults, 0, 2 );
        $def_end    = array_slice( $defaults, 3 );
        $cols 		= array_merge( $def_start, $epos, $def_end );

        return $cols;
    }

    /**
     * Custom columns: EskimoEPOS Category ID & Product ID
     *
     * @param   string  $column
     * @param   integer $post_id
     */
    public function custom_columns( $column, $post_id ) {
    
        // Category & Product IDs
        $eskimo_category_id = get_post_meta( $post_id, '_eskimo_category_id', true );
        $eskimo_product_id  = get_post_meta( $post_id, '_eskimo_product_id', true );
        
        // Test Column
        switch ( $column ) {
            case 'epos_product_id':
			    echo $eskimo_product_id;
			    break;
            case 'epos_category_id':
			    echo $eskimo_category_id;
			    break;
	    }
    }

    /**
     * Join posts and postmeta tables
	 * http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_join
	 *
	 * @param	string	$join
	 * @return	string	$join
     */
    public function cf_search_join( $join ) {

        global $wpdb;

        // Admin search
        if ( is_admin() && is_search() ) {    
            $join .=' LEFT JOIN '.$wpdb->postmeta. ' ON '. $wpdb->posts . '.ID = ' . $wpdb->postmeta . '.post_id ';
        }

        return $join;
    }

    /**
	 * Modify the search query with posts_where
	 * http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_where
	 *
	 * @param	string	$where
	 * @return	string	$where
     */
    public function cf_search_where( $where ) {
    
        global $pagenow, $wpdb;
    
        if ( is_admin() && is_search() ) {
            $where = preg_replace(
                "/\(\s*" . $wpdb->posts . ".post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
                "(" . $wpdb->posts . ".post_title LIKE $1) OR (" . $wpdb->postmeta . ".meta_value LIKE $1)", $where );
        }

        return $where;
    }

    /**
     * Prevent duplicates
     * http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_distinct
	 *
	 * @param	string	$where
	 * @return	string	$where
     */
    public function cf_search_distinct( $where ) {
        global $wpdb;

        if ( is_admin() && is_search() ) {
            return "DISTINCT";
        }

        return $where;
    }
}
