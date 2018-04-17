<?php

/**
 * Import & Export from Woocommerce via the Eskimo EPOS API Data
 *
 * @link       https://on.tinternet.co.uk
 * @package    Eskimo
 * @subpackage Eskimo/admin
 */

/**
 * Eskimo EPOS product, category, customer and order processing and sync
 * 
 * Woocommerce import and export for caregories, products, customers and orders
 *
 * @package    Eskimo
 * @subpackage Eskimo/admin
 * @author     Stephen Betley <on@tinternet.co.uk>
 */
class Eskimo_WC {

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
	 * Initialize the class and set its properties
	 *
	 * @param   string    $eskimo     The name of this plugin
	 * @param   string    $version    The version of this plugin
	 * @param   string    $version    Plugin debugging mode, default false
	 */
	public function __construct( $eskimo, $version, $debug = false ) {
        if ( $debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

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
     * @param   array   $api_data
     * @return  boolean
     */
    public function get_categories_all( $api_data ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Validate API data
        if ( empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // Process data
        if ( $this->debug ) { error_log( 'Process Categories[' . count( $api_data ) . ']' ); }

        // Web_ID
        $web_prefix = get_option( 'eskimo_api_category' ); 
        if ( $this->debug ) { error_log( 'Web Prefix[' . $web_prefix . ']' ); }

        // Process parent & child categories
        $parent = $child = [];

        // Get parent & child categories
        foreach ( $api_data as $api_cat ) {

            // Product Categories Only
            if ( !preg_match( '/product$/', $api_cat->Eskimo_Category_ID ) ) { continue; }
            
            // Already with a Web_ID so pre-existing in WC & Temp 'zero' reset
            if ( !empty( $api_cat->Web_ID ) && $api_cat->Web_ID !== '0' ) {
                if ( $this->debug ) { error_log( 'Cat ID[' . $api_cat->Eskimo_Category_ID . '] Exists [' . $api_cat->Web_ID . ']' ); }
                continue; 
            }

            // Use category
            if ( empty( $api_cat->ParentID ) ) {
                $parent[] = $api_cat;
            } else {
				$child[] = $api_cat;
			}
        }
        if ( $this->debug ) { error_log( 'EPOS Cats: Parent[' . count( $parent ) . '] Child[' . count( $child ) . ']' ); }

        // Return data
        $result = [];

        // Process parent categories first
        foreach ( $parent as $api_cat ) {

            // Insert term
            $cat_term = $this->add_product_category_rest( $api_cat );
            if ( empty( $cat_term ) || is_wp_error( $cat_term ) ) {
                if ( $this->debug ) { error_log( 'Bad parent term insert ID[' . $api_cat->Eskimo_Category_ID . '][' . $api_cat->ShortDescription . ']' ); }
                continue;
            }
            
            if ( $this->debug ) { error_log( 'Cat Term[' . print_r( $cat_term, true )  . ']' ); }

            // Update Eskimo CatID
            $term_meta_id = $this->add_term_eskimo_cat_id( $cat_term['id'], $api_cat );
            if ( false === $term_meta_id || is_wp_error( $term_meta_id ) ) {
                if ( $this->debug ) { error_log( 'Bad term meta insert[' . $cat_term['id'] . ']' ); }
                continue;
            }

            // Load into response list
            $result[] = [
                'Eskimo_Category_ID'    => $api_cat->Eskimo_Category_ID,
                'Web_ID'                => ( empty( $web_prefix ) ) ? $cat_term['id'] : $web_prefix . $cat_term['id']
            ];
        }

        // Process child categories last
        foreach ( $child as $api_cat ) {

            // Insert term
            $cat_term = $this->add_product_category_rest( $api_cat, true );
            if ( empty( $cat_term ) || is_wp_error( $cat_term ) ) {
                if ( $this->debug ) { error_log( 'Bad child term insert ID[' . $api_cat->Eskimo_Category_ID . '][' . $api_cat->ShortDescription . ']' ); }
                continue;
            }

            if ( $this->debug ) { error_log( 'Cat Term[' . print_r( $cat_term, true )  . ']' ); }

            // Update Eskimo CatID
            $term_meta_id = $this->add_term_eskimo_cat_id( $cat_term['id'], $api_cat );
            if ( false === $term_meta_id || is_wp_error( $term_meta_id ) ) {
                if ( $this->debug ) { error_log( 'Bad term meta insert[' . $cat_term['id'] . ']' ); }
                continue;
            }

            // Load into response list
            $result[] = [
                'Eskimo_Category_ID'    => $api_cat->Eskimo_Category_ID,
                'Web_ID'                => ( empty( $web_prefix ) ) ? $cat_term['id'] : $web_prefix . $cat_term['id']
            ];
        }

        // OK, done
        return $result;
    }

    /**
     * Get remote API category by ID
     *
     * @param   array   $api_data
     * @return  boolean
     */
    public function get_categories_specific_ID( $api_cat ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Validate API data
        if ( empty( $api_cat ) ) {
            return $this->api_rest_error();
        }

        // Process data
        if ( $this->debug ) { error_log( 'Process Category[' . count( $api_cat ) . ']' ); }

        // Web_ID
        $web_prefix = get_option( 'eskimo_api_category' ); 
        if ( $this->debug ) { error_log( 'Web Prefix[' . $web_prefix . ']' ); }

        // Product Categories Only
        if ( !preg_match( '/product$/', $api_cat->Eskimo_Category_ID ) ) { return false; }
            
        // Already with a Web_ID so pre-existing in WC
        if ( !empty( $api_cat->Web_ID ) && $api_cat->Web_ID !== '0' ) { 
            if ( $this->debug ) { error_log( 'Cat ID[' . $api_cat->Eskimo_Category_ID . '] Exists [' . $api_cat->Web_ID . ']' ); }
            return false; 
        }

        // Parent or Child
        $parent = ( empty( $api_cat->ParentID ) ) ? true : false;

        // Insert term
        $cat_term = $this->add_product_category_rest( $api_cat, !$parent );
        if ( empty( $cat_term ) || is_wp_error( $cat_term ) ) {
            if ( $this->debug ) { error_log( 'Bad term insert ID[' . $api_cat->Eskimo_Category_ID . '][' . $api_cat->ShortDescription . ']' ); }
            return false;
        }
        
        if ( $this->debug ) { error_log( 'Cat Term[' . print_r( $cat_term, true )  . ']' ); }

        // Update Eskimo CatID
        $term_meta_id = $this->add_term_eskimo_cat_id( $cat_term['id'], $api_cat );
        if ( false === $term_meta_id || is_wp_error( $term_meta_id ) ) {
            if ( $this->debug ) { error_log( 'Bad term meta insert[' . $cat_term['id'] . ']' ); }
        }

        // OK, done 
        return [
            [
                'Eskimo_Category_ID'    => $api_cat->Eskimo_Category_ID,
                'Web_ID'                => ( empty( $web_prefix ) ) ? $cat_term['id'] : $web_prefix . $cat_term['id']
            ]
        ];
    }

    /**
     * Get remote API categories by parent ID
     *
     * @param   array   $api_data
     * @return  boolean
     */
    public function get_categories_child_categories_ID( $api_data ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Validate API data
        if ( empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // Web_ID
        $web_prefix = get_option( 'eskimo_api_category' ); 
        if ( $this->debug ) { error_log( 'Web Prefix[' . $web_prefix . ']' ); }

        // Process parent & child categories
        $child = [];

        // Get parent & child categories
        foreach ( $api_data as $api_cat ) {

            // Product Categories Only
            if ( !preg_match( '/product$/', $api_cat->Eskimo_Category_ID ) ) { continue; }
            
            // Already with a Web_ID so pre-existing in WC
            if ( !empty( $api_cat->Web_ID ) && $api_cat->Web_ID !== '0' ) { 
                if ( $this->debug ) { error_log( 'Cat ID[' . $api_cat->Eskimo_Category_ID . '] Exists [' . $api_cat->Web_ID . ']' ); }
                continue; 
            }

            // Should have a parent wft!
            if ( empty( $api_cat->ParentID ) ) { continue; }

            // Log cat
            $child[] = $api_cat;
        }
        if ( $this->debug ) { error_log( 'EPOS Cats: [' . count( $child ) . ']' ); }

        // Return data
        $result = [];

        // Process child categories last
        foreach ( $child as $api_cat ) {

            // Insert term
            $cat_term = $this->add_product_category_rest( $api_cat, true );
            if ( empty( $cat_term ) || is_wp_error( $cat_term ) ) {
                if ( $this->debug ) { error_log( 'Bad child term insert ID[' . $api_cat->Eskimo_Category_ID . '][' . $api_cat->ShortDescription . ']' ); }
                continue;
            }
            if ( $this->debug ) { error_log( 'Cat Term[' . print_r( $cat_term, true )  . ']' ); }

            // Update Eskimo CatID
            $term_meta_id = $this->add_term_eskimo_cat_id( $cat_term['id'], $api_cat );
            if ( false === $term_meta_id || is_wp_error( $term_meta_id ) ) {
                if ( $this->debug ) { error_log( 'Bad term meta insert[' . $cat_term['id'] . ']' ); }
                continue;
            }

            // Load into response list
            $result[] = [
                'Eskimo_Category_ID'    => $api_cat->Eskimo_Category_ID,
                'Web_ID'                => ( empty( $web_prefix ) ) ? $cat_term['id'] : $web_prefix . $cat_term['id']
            ];
        }

        // OK, done
        return $result;
    }

    /**
     * Get remote API categories
     *
     * @param   array   $api_data
     * @return  boolean
     */
    public function get_categories_cart_ID( $api_data ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Validate API data
        if ( empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // Process data
        if ( $this->debug ) { error_log( 'Process Categories ID[' . count( $api_data ) . ']' ); }

        // Process parent & child categories
        $categories = [];

        // Get parent & child categories
        foreach ( $api_data as $api_cat ) {

            // Product Categories Only
            if ( !preg_match( '/product$/', $api_cat->Eskimo_Category_ID ) ) { continue; }
            
            // Already with a Web_ID so pre-existing in WC
            if ( empty( $api_cat->Web_ID ) ) { 
                if ( $this->debug ) { error_log( 'Cat ID[' . $api_cat->Eskimo_Category_ID . '] NOT Exists Web_ID' ); }
                continue; 
            }

            // Add cat
            $categories[] = $api_cat;
        }
        if ( $this->debug ) { error_log( 'EPOS Cats:' . count( $categories ) ); }

        // Return data
        $result = [];

        // Process parent categories first
        foreach ( $categories as $api_cat ) {

            // Load into response list. Zero Reset
            $result[] = [
                'Eskimo_Category_ID'    => $api_cat->Eskimo_Category_ID,
                'Web_ID'                => '0'
            ];
        }

        // OK, done
        return $result;
    }

    //----------------------------------------------
    // Woocommerce Category Product Import
    //----------------------------------------------

    /**
     * Get remote API products by category
     * - Deprecated
     * 
     * @param   array   $api_data
     * @return  boolean
     */
    public function get_category_products_all( $api_data ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Validate API data
        if ( empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // Process data
        if ( $this->debug ) { error_log( 'Process Category Products All[' . count( $api_data ) . ']' ); }

        // Web_ID
        $web_prefix = get_option( 'eskimo_api_product' ); 
        if ( $this->debug ) { error_log( 'Web Prefix[' . $web_prefix . ']' ); }

        // Process products
        $products = [];

        // Get parent & child categories
        foreach ( $api_data as $api_cat ) {

            // Product Categories Only
            if ( !preg_match( '/product$/', $api_cat->eskimo_category_id ) ) { continue; }

            // Requires that the Eskimo Category has been imported
            if ( empty( $api_cat->web_category_id ) || $api_cat->web_category_id === '0' ) { 
                if ( $this->debug ) { error_log( 'Cat ID[' . $api_cat->eskimo_category_id . '] NOT Exists Web_ID' ); }
                continue; 
            }

            // Requires that the Eskimo Product has NOT been imported
            if ( !empty( $api_cat->web_product_id ) && $api_cat->web_product_id !== '0' ) { 
                if ( $this->debug ) { error_log( 'Prod ID[' . $api_cat->eskimo_product_identifier . '] Exists [' . $api_cat->web_product_id . ']' ); }
                continue; 
            }

            // Required valid product data
            if ( empty( $api_cat->product ) ) { 
                if ( $this->debug ) { error_log( 'Product Not Set ID[' . $api_cat->eskimo_product_identifier . ']' ); }
                continue; 
            }

            // OK add products
            $products[] = $api_cat->product;
        }

        if ( $this->debug ) { error_log( 'EPOS Cat Prods: [' . count( $products ) . ']' ); }

        // Something to do?        
        if ( empty( $products ) ) { return false; }

        if ( $this->debug ) { error_log( print_r( $products, true ) ); }

        // Return data
        $result = [];

        // Process parent categories first
        foreach ( $products as $api_prod ) {

            // Insert product post
            $cat_prod = $this->add_category_product_rest( $api_prod );
            if ( false === $cat_prod || is_wp_error ( $cat_prod ) ) { continue; }

            // Update Eskimo ProdID
            $prod_meta_id = $this->add_post_meta_eskimo_id( $cat_prod['id'], $api_prod );
            if ( false === $prod_meta_id || is_wp_error( $prod_meta_id ) ) {
                if ( $this->debug ) { error_log( 'Bad post meta insert[' . $cat_prod['id'] . ']' ); }
            }

            // Load into response list
            $result[] = [
                'Eskimo_Identifier' => $api_prod->eskimo_identifier,
                'Web_ID'            => ( empty( $web_prefix ) ) ? $cat_prod['id'] : $web_prefix . $cat_prod['id']
            ];
        }

        // OK, done
        return $result;
    }

    /**
     * Get remote API category by ID
     * - Not yet implemented
     *
     * @param   array   $api_data
     * @return  boolean
     */
    public function get_category_products_specific_category( $api_data ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Validate API data
        if ( empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // Process data
        if ( $this->debug ) { 
            error_log( 'Process Category Products[' . count( $api_data ) . ']' ); 
            error_log( print_r( $api_data, true ) );
        }

        // OK, done
        return true;
    }

    //----------------------------------------------
    // Woocommerce Product Import
    //----------------------------------------------

    /**
     * Get remote API products by category
     *
     * @param   array   $api_data
     * @return  boolean
     */
    public function get_products_all( $api_data ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

         // Validate API data
        if ( empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // Process data
        if ( $this->debug ) { error_log( 'Process Products All[' . count( $api_data ) . ']' ); }

        // Web_ID
        $web_prefix = get_option( 'eskimo_api_product' ); 
        if ( $this->debug ) { error_log( 'Web Prefix[' . $web_prefix . ']' ); }

        // Process products
        $products = [];

        // Get products list
        foreach ( $api_data as $api_prod ) {

            // Product Categories Only
            if ( !preg_match( '/product$/', $api_prod->eskimo_category_id ) ) { continue; }

            // Dodgy Title?
            if ( empty( $api_prod->title ) ) {
                if ( $this->debug ) { error_log( 'Prod ID[' . $api_prod->eskimo_category_id . '] Title NOT Exists' ); }
                continue; 
            }

            // Requires that the Eskimo Category has been imported
            if ( empty( $api_prod->web_category_id ) || $api_prod->web_category_id === '0' ) { 
                if ( $this->debug ) { error_log( 'Cat ID[' . $api_prod->eskimo_category_id . '] NOT Exists Cat Web_ID' ); }
                continue; 
            }

            // Requires that the Eskimo Product has NOT been imported
            if ( !empty( $api_prod->web_id ) && $api_prod->web_id !== '0' ) { 
                if ( $this->debug ) { error_log( 'Prod ID[' . $api_prod->eskimo_identifier . '] Web_ID Exists [' . $api_prod->web_id . ']' ); }
                continue; 
            }

            // Required valid product sku data
            if ( empty( $api_prod->sku ) ) { 
                if ( $this->debug ) { error_log( 'Product SKU Not Set ID[' . $api_prod->eskimo_identifier . ']' ); }
                continue; 
            }

            // OK add products
            $products[] = $api_prod;
        }

        if ( $this->debug ) { error_log( 'EPOS Prods: [' . count( $products ) . ']' ); }

        // Something to do?        
        if ( empty( $products ) ) { return false; }

        // Return data
        $result = [];

        // Process parent categories first
        foreach ( $products as $api_prod ) {

            // Insert product post
            $prod = $this->add_category_product_rest( $api_prod );
            if ( false === $prod || is_wp_error ( $prod ) ) { continue; }

            // Update Eskimo ProdID
            $prod_meta_id = $this->add_post_meta_eskimo_id( $prod['id'], $api_prod );
            if ( false === $prod_meta_id || is_wp_error( $prod_meta_id ) ) {
                if ( $this->debug ) { error_log( 'Bad post meta insert[' . $prod['id'] . ']' ); }
            }

            // Load into response list
            $result[] = [
                'Eskimo_Identifier' => $api_prod->eskimo_identifier,
                'Web_ID'            => ( empty( $web_prefix ) ) ? $prod['id'] : $web_prefix . $prod['id']
            ];
        }

        // OK, done
        return $result;
    }

    /**
     * Get remote API product by ID
     *
     * @param   array   $api_data
     * @return  boolean
     */
    public function get_products_specific_ID( $api_prod ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Validate API data
        if ( empty( $api_prod ) ) {
            return $this->api_rest_error();
        }

        // Process data
        if ( $this->debug ) { error_log( 'Process Product[' . count( $api_prod ) . ']' ); }

        // Web_ID
        $web_prefix = get_option( 'eskimo_api_product' ); 
        if ( $this->debug ) { error_log( 'Web Prefix[' . $web_prefix . ']' ); }

        // Product Categories Only
        if ( !preg_match( '/product$/', $api_prod->eskimo_category_id ) ) { return false; }

        // Dodgy Title?
        if ( empty( $api_prod->title ) ) {
            if ( $this->debug ) { error_log( 'Prod ID[' . $api_prod->eskimo_category_id . '] Title NOT Exists' ); }
            return false; 
        }

        // Requires that the Eskimo Category has been imported
        if ( empty( $api_prod->web_category_id ) || $api_prod->web_category_id === '0' ) { 
            if ( $this->debug ) { error_log( 'Cat ID[' . $api_cat->eskimo_category_id . '] NOT Exists Cat Web_ID' ); }
            return false; 
        }

        // Requires that the Eskimo Product has NOT been imported
        if ( !empty( $api_prod->web_id ) && $api_prod->web_id !== '0' ) { 
            if ( $this->debug ) { error_log( 'Prod ID[' . $api_prod->eskimo_identifier . '] Web_ID Exists [' . $api_prod->web_id . ']' ); }
            return false; 
        }

        // Required valid product sku data
        if ( empty( $api_prod->sku ) ) { 
            if ( $this->debug ) { error_log( 'Product SKU Not Set ID[' . $api_prod->eskimo_identifier . ']' ); }
            return false; 
        }

        // Insert product post
        $prod = $this->add_category_product_rest( $api_prod );
        if ( false === $prod || is_wp_error ( $prod ) ) { return false; }

        // Update Eskimo ProdID
        $prod_meta_id = $this->add_post_meta_eskimo_id( $prod['id'], $api_prod );
        if ( false === $prod_meta_id || is_wp_error( $prod_meta_id ) ) {
            if ( $this->debug ) { error_log( 'Bad post meta insert[' . $prod['id'] . ']' ); }
        }

        // OK, done 
        return [
            [
                'Eskimo_Identifier' => $api_prod->eskimo_identifier,
                'Web_ID'            => ( empty( $web_prefix ) ) ? $prod['id'] : $web_prefix . $prod['id']
            ]
        ];
    }

    /**
     * Get remote API product by ID
     *
     * @param   array   $api_data
     * @param   array   $path
     * @return  boolean
     */
    public function get_products_import_ID( $api_prod, $path ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ':' . $path ); }

        // Validate API data
        if ( empty( $api_prod ) ) {
            return $this->api_rest_error();
        }

        // Process data
        if ( $this->debug ) { error_log( 'Process Product[' . count( $api_prod ) . '] path[' . $path . ']' ); }

        // Product Categories Only
        if ( !preg_match( '/product$/', $api_prod->eskimo_category_id ) ) { return false; }

        // Dodgy Title?
        if ( empty( $api_prod->title ) ) {
            if ( $this->debug ) { error_log( 'Prod ID[' . $api_prod->eskimo_category_id . '] Title NOT Exists' ); }
            return false; 
        }

        // Requires that the Eskimo Category has been imported
        if ( empty( $api_prod->web_category_id ) || $api_prod->web_category_id === '0' ) { 
            if ( $this->debug ) { error_log( 'Cat ID[' . $api_cat->eskimo_category_id . '] NOT Exists Cat Web_ID' ); }
            return false; 
        }

        // Requires that the Eskimo Product has NOT been imported
        if ( empty( $api_prod->web_id ) || $api_prod->web_id == '0' ) { 
            if ( $this->debug ) { error_log( 'Prod ID[' . $api_prod->eskimo_identifier . '] Web_ID Not Exists [' . $api_prod->web_id . ']' ); }
            return false; 
        }

        // Required valid product sku data
        if ( empty( $api_prod->sku ) ) { 
            if ( $this->debug ) { error_log( 'Product SKU Not Set ID[' . $api_prod->eskimo_identifier . ']' ); }
            return false; 
        }

		// Due process by path  
        $prod = $this->update_category_product_rest( $api_prod, $path );
        if ( false === $prod || is_wp_error ( $prod ) ) { return false; }

        // OK, done 
        return [
            [
				'Eskimo_Identifier' => $api_prod->eskimo_identifier,
                'Web_ID'            => $api_prod->web_id
            ]
        ];
    }

    /**
     * Get remote API products by category
     *
     * @param   array   $api_data
     * @return  boolean
     */
    public function get_products_cart_ID( $api_data ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

         // Validate API data
        if ( empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // Process data
        if ( $this->debug ) { error_log( 'Process Products Cart ID[' . count( $api_data ) . ']' ); }

        // Process products
        $products = [];

        // Get parent & child categories
        foreach ( $api_data as $api_prod ) {

            // Product Categories Only
            if ( !preg_match( '/product$/', $api_prod->eskimo_category_id ) ) { continue; }

            // Requires that the Eskimo Category has been imported
            if ( empty( $api_prod->web_category_id ) ) { 
                if ( $this->debug ) { error_log( 'Prod Cat ID[' . $api_prod->eskimo_category_id . '] NOT Exists Cat Web_ID' ); }
                continue; 
            }

            // Requires that the Eskimo Product has NOT been imported
            if ( empty( $api_prod->web_id ) || $api_prod->web_id === '0' ) { 
                if ( $this->debug ) { error_log( 'Prod ID[' . $api_prod->eskimo_identifier . '] Not Exists Web_ID' ); }
                continue; 
            }

            // OK add products
            $products[] = $api_prod;
        }

        if ( $this->debug ) { error_log( 'EPOS Prods: [' . count( $products ) . ']' ); }

        // Something to do?        
        if ( empty( $products ) ) { return false; }

        // Return data
        $result = [];

        // Process parent categories first
        foreach ( $products as $api_prod ) {

            // Load into response list. Temp zero reset
            $result[] = [
                'Eskimo_Identifier' => $api_prod->eskimo_identifier,
                'Web_ID'            => '0'
            ];
        }

        // OK, done
        return $result;
    }

    //----------------------------------------------
    // Woocommerce Customer Import & Export
    //----------------------------------------------

    /**
     * Get remote API customer data
     *
     * @param   array   $api_data
     * @return  boolean
     */
    public function get_customers_specific_ID( $api_data ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Validate API data
        if ( empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // Process data
        if ( $this->debug ) { error_log( 'Process Customer[' . count( $api_data ) . ']' ); }

		// Email exists?
		$email = filter_var( $api_data->EmailAddress, FILTER_SANITIZE_EMAIL );
		if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
			return $this->api_error( 'Invalid Customer Email[' . esc_html( $email ) . ']' );
		}

		// Set up address	
		$addr 	= explode( PHP_EOL, $api_data->Address );
		$addr_1 = array_shift( $addr );
		$city	= ( count( $addr ) ) ? $addr[0] : '';
        if ( $this->debug ) { error_log( 'Customer Addr[' . $addr_1 . '] city[' . $city . ']' ); }

		// Set up user with format forename.surname
		$username = $api_data->Forename . '.' . $api_data->Surname;
			
		// Generate WC user if possible - autogenerate password
		$user_id = wc_create_new_customer( $email, $username );
		if ( is_wp_error( $user_id ) ) {
			return $this->api_error( $user_id->get_error_message() );
		}

		// Tweak user
		$user_info = [
			'ID' 		 => $user_id, 
			'first_name' => $api_data->Forename,
			'last_name'  => $api_data->Surname
		];

		$user_id = wp_update_user( $user_info );
		if ( is_wp_error( $user_id ) ) {
			return $this->api_error( $user_id->get_error_message() );
		}
        if ( $this->debug ) { error_log( 'OK Customer ID[' . $user_id . ']' ); }

		// User meta
		add_user_meta( $user_id, 'epos_id', $api_data->ID );
		add_user_meta( $user_id, 'epos_notes', $api_data->Notes );

		// OK, got new user, add billing details
		add_user_meta( $user_id, 'billing_first_name', $api_data->Forename );
		add_user_meta( $user_id, 'billing_last_name', $api_data->Surname );
		add_user_meta( $user_id, 'billing_company', $api_data->CompanyName );
		add_user_meta( $user_id, 'billing_address_1', $addr_1 );
		add_user_meta( $user_id, 'billing_city', $city );
		add_user_meta( $user_id, 'billing_postcode', $api_data->PostCode );
		add_user_meta( $user_id, 'billing_country', 'GB' );
		add_user_meta( $user_id, 'billing_email', $api_data->EmailAddress );
		add_user_meta( $user_id, 'billing_phone', $api_data->Telephone );
		add_user_meta( $user_id, 'billing_mobile', $api_data->Mobile );

		// OK, got new user, add shipping details, assume same as billing
		add_user_meta( $user_id, 'shipping_first_name', $api_data->Forename );
		add_user_meta( $user_id, 'shipping_last_name', $api_data->Surname );
		add_user_meta( $user_id, 'shipping_company', $api_data->CompanyName );
		add_user_meta( $user_id, 'shipping_address_1', $addr_1 );
		add_user_meta( $user_id, 'shipping_city', $city );
		add_user_meta( $user_id, 'shipping_postcode', $api_data->PostCode );
		add_user_meta( $user_id, 'shipping_country', 'GB' );
		add_user_meta( $user_id, 'shipping_email', $api_data->EmailAddress );
		add_user_meta( $user_id, 'shipping_phone', $api_data->Telephone );
		add_user_meta( $user_id, 'shipping_mobile', $api_data->Mobile );

        // OK, done
        return 'User ID[' . $user_id . '] Username[' . $username . ']';
	}

    /**
     * Get remote API customer data for insert
     *
     * @param   array   $api_data
     * @return  boolean
     */
    public function get_customers_insert_ID( $id = '' ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ' ID[' . $id . ']' ); }

        // Validate API data
        if ( empty( $id ) || $id <= 0 ) {
            return $this->api_error( 'Insert: Invalid user ID' );
        }

        // Process data
        if ( $this->debug ) { error_log( 'Process Customer ID[' . $id . ']' ); }

		// User?
		$user_data = get_user_by( 'ID', $id );
		if ( false === $user_data ) { return $this->api_error( 'Invalid WP user ID[' . $id . ']' ); }

		// OK, got user id
		$user_id = absint( $user_data->ID );
		if ( $user_data === 0 ) { return $this->api_error( 'Invalid WP user ID[' . $user_id . ']' ); }

		// First test... EPOS ID
		$epos_id = get_user_meta( $user_id, 'epos_id', true );
		if ( ! empty( $epos_id ) ) { return $this->api_error( 'EPOS user exists ID[' . $user_id . '] EPOS ID[' . $epos_id . ']' ); }

		// Set up data
		$data = [ 
			'ActiveAccount' => true,
			'EmailAddress'  => $user_data->user_email,
			'TitleID'		=> 1,
			'CountryCode'	=> 'GB'
		];

		// Set up address
		$addr_1	= get_user_meta( $user_id, 'billing_address_1', true );
		$addr_2	= get_user_meta( $user_id, 'billing_address_2', true );
		$city 	= get_user_meta( $user_id, 'billing_city', true );
		$state 	= get_user_meta( $user_id, 'billing_state', true );

		// Get meta data... assume billing rules
		$data['Forename']		= get_user_meta( $user_id, 'billing_first_name', true );
		$data['Surname']		= get_user_meta( $user_id, 'billing_last_name', true );
		$data['CompanyName']	= get_user_meta( $user_id, 'billing_company', true );
		$data['Notes']			= get_user_meta( $user_id, 'epos_notes', true );
		$data['Address']		= $address = $addr_1 . '\r\n' . $addr_2 . '\r\n' . $city . '\r\n' . $state; 
		$data['Postcode']		= get_user_meta( $user_id, 'billing_postcode', true );
		$data['Telephone']		= get_user_meta( $user_id, 'billing_phone', true );
		$data['Mobile']			= get_user_meta( $user_id, 'billing_mobile', true );

        // OK, done
        return $data;
	}

    /**
     * Get remote API customer data
     *
     * @param   array   $api_data
     * @return  boolean
     */
    public function get_customers_update_ID( $id = '' ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . 'ID[' . $id . ']' ); }

        // Validate API data
        if ( empty( $id ) ) {
            return $this->api_error( 'Insert: Invalid user ID' );
        }

        // Process data
        if ( $this->debug ) { error_log( 'Process Customer ID[' . $id . ']' ); }

		$user_data = get_user_by( 'ID', $id );
		if ( false === $user_data ) { return $this->api_error( 'Invalid WP user ID[' . $id . ']' ); }

		// OK, got user id
		$user_id = absint( $user_data->ID );
		if ( $user_data === 0 ) { return $this->api_error( 'Invalid WP user ID[' . $user_id . ']' ); }

		// First test... EPOS ID
		$epos_id = get_user_meta( $user_id, 'epos_id', true );
		if ( empty( $epos_id ) ) { return $this->api_error( 'EPOS user not exists ID[' . $user_id . ']' ); }

		// Set up data
		$data = [ 
			'ActiveAccount' => true,
			'ID'			=> $epos_id,
			'EmailAddress'  => $user_data->user_email,
			'TitleID'		=> 1,
			'CountryCode'	=> 'GB'
		];

		// Set up address
		$addr_1	= get_user_meta( $user_id, 'billing_address_1', true );
		$city 	= get_user_meta( $user_id, 'billing_city', true );

		// Get meta data... assume billing rules
		$data['Forename']		= get_user_meta( $user_id, 'billing_first_name', true );
		$data['Surname']		= get_user_meta( $user_id, 'billing_last_name', true );
		$data['CompanyName']	= get_user_meta( $user_id, 'billing_company', true );
		$data['Notes']			= get_user_meta( $user_id, 'epos_notes', true );
		$data['Address']		= $address = $addr_1 .  '\r\n' . $city; 
		$data['Postcode']		= get_user_meta( $user_id, 'billing_postcode', true );
		$data['Telephone']		= get_user_meta( $user_id, 'billing_phone', true );
		$data['Mobile']			= get_user_meta( $user_id, 'billing_mobile', true );

        // OK, done
        return $data;
	}

    /**
     * Post insert EPOS data user update
     *
     * @param   array   $id
     * @param   array   $data
     * @return  boolean
     */
    public function get_customers_epos_ID( $id, $data, $update = false ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ' ID[' . $id . '] UPD[' . (int) $update . ']' ); }

        // Validate API data
        if ( empty( $data ) ) {
            return $this->api_error( 'Insert: Invalid EPOS data' );
        }

        // Process data
        if ( $this->debug ) { error_log( 'Process Customer ID[' . $id . ']EPOS ID[' . $data->ID . ']' ); }

		// Process update
		return ( $update === true ) ? ( update_user_meta( $id, 'epos_id', $data->ID ) ) ? 'ID[' . $id . '] EPOS ID[' . $data->ID . ']' : false : $data->ID;
	}

    //----------------------------------------------
    // Woocommerce Customer Import & Export
    //----------------------------------------------

    /**
     * Get remote API order data
     *
     * @param   array   $api_data
     * @return  boolean
     */
    public function get_orders_specific_ID( $api_data ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Validate API data
        if ( empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // Process data
        if ( $this->debug ) { error_log( 'Process Order[' . count( $api_data ) . ']' ); }

		// Validate content? order, customer

		// Set up order 	
		if ( $this->debug ) { error_log( 'Order [' . ']' ); }

		// Set up customer
		$username = $api_data->Forename . '.' . $api_data->Surname;
			
		// Generate WC user if possible - autogenerate password
		$order_id = wc_create_order( $email, $username );
		if ( is_wp_error( $user_id ) ) {
			return $this->api_error( $order_id->get_error_message() );
		}

		// Order lines / products

		// Order meta data

        if ( $this->debug ) { error_log( 'OK Order ID[' . $order_id . ']' ); }

		// Order meta
		add_post_meta( $user_id, 'epos_id', $api_data->ID );

		// OK, done
        return 'Order ID[' . $order_id . ']';
	}

    /**
     * Get woocommerce order data for EPOS insert
     *
     * @param   array   $api_data
     * @return  boolean
     */
    public function get_orders_insert_ID( $id = '' ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ' ID[' . $id . ']' ); }

        // Validate API data
        if ( empty( $id ) || $id <= 0 ) {
            return $this->api_error( 'Insert: Invalid user ID' );
        }

        // Process data
        if ( $this->debug ) { error_log( 'Process Order ID[' . $id . ']' ); }

		// Order?
		$order = wc_get_order( $id );
		if ( false === $order ) { return $this->api_error( 'Invalid Order ID[' . $id . ']' ); }

		// OK, got user id
		$order_id = absint( $order->get_id() );
		if ( $order_id === 0 ) { return $this->api_error( 'Invalid WC Order ID[' . $order_id . ']' ); }

		// First test... EPOS ID
		$web_order_id = get_post_meta( $order_id, '_web_order_id', true );
		if ( ! empty( $web_order_id ) ) { return $this->api_error( 'EPOS Order exists ID[' . $order_id . '] EPOS Web Order ID[' . $web_order_id . ']' ); }

		// Get order items
		$order_items = $order->get_items();
		if ( $this->debug ) { error_log( 'Order Items: ' . count( $order_items ) ); }
		
		// Get the customer
		$cust_id 	= $order->get_customer_id();
		$epos_id 	= get_user_meta( $cust_id, 'epos_id', true );

		// Order reference
		$epos_ei = get_option( 'eskimo_api_customer' );
		$epos_ei = ( empty( $epos_ei ) ) ? $epos_id . '-' . $cust_id . '-' . $order_id : $epos_ei . $epos_id . '-' . $cust_id . '-' . $order_id;  
		if ( $this->debug ) { error_log( 'Customer ID: [' . $cust_id . '] EPOS ID[' . $epos_id . ']' ); }

		// Notes
		$order_note = ( $order->get_customer_order_notes() ) ? $order->get_customer_order_notes()[0]->comment_content : '';
		if ( empty( $order_note ) ) {
			$order_note = get_the_excerpt( $order_id );
		}

		// Set up data
		$data = [
			'order_id' 				=> $order_id,
			'eskimo_customer_id' 	=> $epos_id,
			'order_date' 			=> $order->get_date_completed()->date('Y-m-d H:i:s'),
			'invoice_amount' 		=> $order->get_total(),
			'amount_paid' 			=> $order->get_total(),
			'OrderType'				=> 2, //WebOrder,
			'ExternalIdentifier'	=> $epos_ei
		];

		// Set up order items
		$items = [];

		// Iterating through each WC_Order_Item_Product objects
		foreach ( $order_items as $k => $order_item ) {
			$item = [];
 			
			$product_id = $order_item->get_product_id(); 
			$product 	= $order_item->get_product(); 
   
			$item['sku_code'] 				= $product->get_sku();
			$item['qty_purchased']			= $order_item->get_quantity();
			$item['unit_price']				= $product->get_price();
			$item['line_discount_amount']	= $order_item->get_total() - $order_item->get_subtotal();
			$item['item_note']				= null;			
			$items[] = $item;
		}

		// Set up shipping
		$shipping = [
			'FAO'			=>	$order->get_shipping_first_name() . ' ' . $order->get_billing_last_name(),
			'AddressLine1' 	=>	$order->get_shipping_address_1(),
			'AddressLine2' 	=>	$order->get_shipping_address_2(),
			'AddressLine3' 	=>	null,
			'PostalTown'	=>	$order->get_shipping_city(),
			'County'		=>	$order->get_shipping_state(),
			'CountryCode'	=>	$order->get_shipping_country(),
			'PostCode'		=>	$order->get_shipping_postcode()
		];

		// Set up billing
		$billing = [
			'FAO'			=>	$order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
			'AddressLine1' 	=>	$order->get_billing_address_1(),
			'AddressLine2' 	=>	$order->get_billing_address_2(),
			'AddressLine3' 	=>	null,
			'PostalTown'	=>	$order->get_billing_city(),
			'County'		=>	$order->get_billing_state(),
			'CountryCode'	=>	$order->get_billing_country(),
			'PostCode'		=>	$order->get_billing_postcode()
		];

		$data['DeliveryAddress']		= $shipping;
		$data['InvoiceAddress']			= $billing;
		$data['OrderedItems']			= $items;
		$data['CustomerReference'] 		= null;
		$data['DeliveryNotes'] 			= $order_note;
		$data['ShippingRateID'] 		= 1;
		$data['ShippingAmountGross'] 	= $order->get_shipping_total();

        // OK, done
        return $data;
	}

    /**
     * Post insert EPOS reference update
     *
     * @param   array   $id
     * @param   array   $data
     * @return  boolean
     */
    public function get_orders_epos_ID( $id, $data, $update = false ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ' ID[' . $id . '] UPD[' . (int) $update . ']' ); }

        // Validate API data
        if ( empty( $data ) ) {
            return $this->api_error( 'Insert: Invalid EPOS data' );
        }

        // Process data
        if ( $this->debug ) { error_log( 'Process Order ID[' . $id . ']EPOS ID[' . $data->ExternalIdentifier . ']' ); }

		// Process update
		return ( $update === true ) ? ( update_post_meta( $id, '_web_order_id', $data->ExternalIdentifier ) ) ? 'ID[' . $id . '] EPOS WebOrder ID[' . $data->ExternalIdentifier . ']' : false : $data->ExternalIdentifier;
	}

    //----------------------------------------------
    // Woocommerce SKU Import
    //----------------------------------------------

    /**
     * Get remote API SKUs
     *
     * @param   array   $api_data
     * @return  boolean
     */
    public function get_sku_all( $api_data ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Validate API data
        if ( empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // Process data
        if ( $this->debug ) { error_log( 'Process Product SKU All[' . count( $api_data ) . ']' ); }

        // OK, done
        return true;
    }

    /**
     * Get remote API SKUs by ID
     *
     * @param   array   $api_data
     * @return  boolean
     */
    public function get_sku_specific_ID( $api_data ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Validate API data
        if ( empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // Process data
        if ( $this->debug ) { error_log( 'Process Product SKU[' . count( $api_data ) . ']' ); }

        // OK, done
        return true;
    }

    /**
     * Get remote API SKU by product ID
     *
     * @param   array   $api_data
     * @return  boolean
     */
    public function get_sku_specific_code( $api_data ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Validate API data
        if ( empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // Process data
        if ( $this->debug ) { error_log( 'Process SKU Code[' . count( $api_data ) . ']' ); }

        // OK, done
        return true;
    }

    //----------------------------------------------
    // Woocommerce Product Images
    //----------------------------------------------

    /**
     * Get remote API product image links
     * - Not yet implemented
     *
     * @param   array   $api_data
     * @return  boolean
     */
    public function get_image_links_all( $api_data ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Validate API data
        if ( empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // Process data
        if ( $this->debug ) { error_log( 'Process Image Links[' . count( $api_data ) . ']' ); }

        // OK, done
        return true;
    }

    /**
     * Get remote API product images
     * - Not yet implemented
     *
     * @param   array   $api_data
     * @return  boolean
     */
    public function get_images_all( $api_data ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Validate API data
        if ( empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // Process data
        if ( $this->debug ) { error_log( 'Process Images All[' . count( $api_data ) . ']' ); }

        // OK, done
        return true;
    }

    //----------------------------------------------
    // Woocommerce Miscellaneous ImpEx
    //----------------------------------------------

    /**
     * Get remote API Tax Codes optionally by ID
     * - Not yet implemented
     *
     * @param   array   $api_data
     * @return  boolean
     */
    public function get_tax_codes( $api_data ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Validate API data
        if ( empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // Process data
        if ( $this->debug ) { error_log( 'Process Tax Codes[' . count( $api_data ) . ']' ); }

        // OK, done
        return true;
    }

    /**
     * Get remote API shops
     * - Not yet implemented
     *
     * @param   array   $api_data
     * @return  boolean
     */
    public function get_shops_all( $api_data ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Validate API data
        if ( empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // Process data
        if ( $this->debug ) { error_log( 'Process Shops All[' . count( $api_data ) . ']' ); }

        // OK, done
        return true;
    }

    /**
     * Get remote API Shops by ID
     * - Not yet implemented
     *
     * @param   array   $api_data
     * @return  boolean
     */
    public function get_shops_specific_ID( $api_data ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Validate API data
        if ( empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // Process data
        if ( $this->debug ) { error_log( 'Process Shops ID[' . count( $api_data ) . ']' ); }

        // OK, done
        return true;
    }

    //----------------------------------------------
    // Ancillary Functions: Categories
    //----------------------------------------------

    /**
     * Insert category into WooCommerce and return the new term details
     *
     * @param   object  $data
     * @param   boolean $parent
     * @return  object  New term or error
     */
    protected function add_product_cat_term( $data, $parent = false ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ' Parent[' . (int) $parent . ']' ); }
        
        // Set term args
        $args = [ 'description' => $data->LongDescription ];            

        // Get parent cat_id
        $args['parent'] = ( $parent ) ? $this->get_parent_category_id( $data ) : 0;

        // Insert term
        return wp_insert_term( $data->ShortDescription, 'product_cat', $args );
    }

    /**
     * Insert category into WooCommerce and return the new term details
     *
     * @param   object  $data
     * @param   boolean $parent
     * @return  object  new term or error
     */
    protected function add_product_category_rest( $data, $parent = false ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ' Parent[' . (int) $parent . ']' ); }

        // Set term args
        $args = [ 
            'name'        => $data->ShortDescription,
            'description' => ( empty( $data->LongDescription ) ) ? ucwords( $data->ShortDescription ) : $data->LongDescription
        ];            

        // Get parent cat_id
        if ( $parent ) {
            $args['parent'] = $this->get_parent_category_id( $data );
        }

        $products_controller = new WC_REST_Product_Categories_Controller();
        $wp_rest_request = new WP_REST_Request( 'POST' );
        $wp_rest_request->set_body_params( $args );
        $res = $products_controller->create_item( $wp_rest_request );

        if ( $this->debug && is_wp_error( $res ) ) { error_log( 'Error:' .  $res->get_error_message() . ']' ); }

        // OK, done 
        return is_wp_error( $res ) ? false : $res->data;
    }

    /**
     * Update new product cat term with custom Eskimo Cat ID
     *
     * @param   integer $cat_id
     * @param   object  $api_cat
     */
    protected function add_term_eskimo_cat_id( $cat_id, $api_cat ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': CatID[' . $cat_id . ']' ); }
        return add_term_meta( $cat_id, 'eskimo_category_id', sanitize_text_field( $api_cat->Eskimo_Category_ID ), true );
    }

    /**
     * Check if the EPOS category has a parent term and get the WC value
     * - Should be no surprises here, parents should all be pre-processed
     * - Children with orphan parent EPOS cat treated as bad data & logged
     *
     * @param   object  $data   Category data
     */
    protected function get_parent_category_id( $data ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }
        
        // Get terms with the epos category id... should be 1 or 0
        $args = [
            'hide_empty' => false, 
            'meta_query' => [
                [
                    'key'       => 'eskimo_category_id',
                    'value'     => $data->ParentID,
                    'compare'   => '='
                ]
            ]
        ];

        // Get terms from product_cat taxonomy
        $terms = get_terms( 'product_cat', $args );

        if ( $this->debug && is_wp_error( $terms ) ) { error_log( 'Error:' .  $res->get_error_message() . ']' ); }

        // No terms or Error
        if ( empty( $terms ) || is_wp_error( $terms ) ) {
            if ( $this->debug ) { error_log( 'Bad Parent Term[' . $data->Eskimo_Category_ID . ']' ); }
            return 0;
        }

        if ( $this->debug ) { error_log( 'Parent Terms[' . print_r( $terms, true ) . ']' ); }

        // OK, use parent term
        return $terms[0]->term_id;
    }

    //----------------------------------------------
    // Ancillary Functions: Products
    //----------------------------------------------

    /**
     * Insert category into WooCommerce and return the new term details
     *
     * @param   object  $data
     * @param   boolean $parent
     * @return  object  new term or error
     */
    protected function add_category_product_rest( $data ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Set product type: simple or variable by sku count
        $type = $this->get_product_type( $data );
        if ( $this->debug ) { error_log( 'Type[' . $type . ']' ); }

        // Treat simple & variable products a bit differently
        switch( $type ) {
            case 'simple':
                return $this->add_category_product_simple( $data );
            case 'variable':
                return $this->add_category_product_variable( $data );
            default: // Bad SKU
                return false;                
        }
    }

    /**
     * Add category product: Simple Product
     *
     * @param   object  $data
     * @return  boolean|object
     */
    protected function add_category_product_simple( $data ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // Single SKU OK?
        $sku = array_shift( $data->sku );
        if ( $this->debug ) { error_log( 'SKU:' . print_r( $sku, true ) ); }
        if ( true === $this->get_product_check_sku( $sku->sku_code ) ) { return false; }        

        // Set term args
        $args = [ 
            'name'              => $data->title,
            'type'              => 'simple',
            'description'       => ( empty( $data->long_description ) ) ? $data->short_description : $data->long_description,
            'short_description' => $data->short_description,
            'regular_price'     => $data->from_price,
            'manage_stock'      => true,
            'parent_id'         => 0
        ];            

        // Set product category
        $cat_id = $this->get_category_by_id( $data );
        $args['categories'] = ( false === $cat_id ) ? [] : $cat_id; 

        // Update product attributes, sku, stock for Simple products
        $args['sku']            = $sku->sku_code;
        $args['stock_quantity'] = $sku->StockAmount;
        $args['tax_class']      = $this->get_product_tax_class( $sku );
        $args['attributes']     = $this->get_product_attributes( $sku );

        if ( $this->debug ) { error_log( print_r( $args, true ) ); }

        // Set up REST process
        $products_controller = new WC_REST_Products_Controller();
        $wp_rest_request = new WP_REST_Request( 'POST' );
        $wp_rest_request->set_body_params( $args );
        $res = $products_controller->create_item( $wp_rest_request );
        
        if ( $this->debug && is_wp_error( $res ) ) { error_log( 'Error:' .  $res->get_error_message() . ']' ); }

        return is_wp_error( $res ) ? false : $res->data;
    }

    /**
     * Add category product: Simple Product
     *
     * @param   object  $data
     * @return  boolean|object
     */
    protected function add_category_product_variable( $data ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }
       
        // Initial check. Already posted SKU?
        $skus = array_map( function( $sku ) { return $sku->sku_code; }, $data->sku );
        if ( $this->debug ) { error_log( 'SKUs[' . print_r( $skus, true ) . ']' ); }
        if ( true === $this->get_product_check_sku( $skus, true ) ) { return false; }

        // Set term args @todo: set_manage_stock as option for variations
        $args = [ 
            'name'              => $data->title,
            'type'              => 'variable',
            'description'       => ( empty( $data->long_description ) ) ? $data->short_description : $data->long_description,
            'short_description' => $data->short_description,
            'manage_stock'      => false, 
            'parent_id'         => 0
        ];            

        // Set product category
        $cat_id = $this->get_category_by_id( $data );
        $args['categories'] = ( false === $cat_id ) ? [] : $cat_id; 

        // Set variant globals
        $args['stock_quantity']     = $this->get_product_variable_stock( $data );
        $args['attributes']         = $this->get_product_variable_attributes( $data );
        $args['default_attributes'] = $this->get_product_variable_attributes_default( $data );

        if ( $this->debug ) { error_log( print_r( $args, true ) ); }

        // Set up REST process
        $wp_rest_request = new WP_REST_Request( 'POST' );
        $wp_rest_request->set_body_params( $args );
        
        $products_controller = new WC_REST_Products_Controller();
        $res = $products_controller->create_item( $wp_rest_request );
        
        // Add product variations
        foreach ( $data->sku as $sku ) {
            $sku->product_id = $res->data['id'];
            $res_var = $this->add_category_product_variation( $sku, $data );
        }

        if ( $this->debug && is_wp_error( $res ) ) { error_log( 'Error:' .  $res->get_error_message() . ']' ); }

        return is_wp_error( $res ) ? false : $res->data;
	}

    /**
     * Insert category into WooCommerce and return the new term details
     *
	 * @param   object  $data
	 * @param	string	$path
     * @param   boolean $parent
     * @return  object  new term or error
     */
    protected function update_category_product_rest( $data, $path ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': path[' . $path . ']' ); }

        // Set product type: simple or variable by sku count
        $type = $this->get_product_type( $data );
        if ( $this->debug ) { error_log( 'Type[' . $type . ']' ); }

        // Treat simple & variable products a bit differently
        switch( $type ) {
            case 'simple':
                return $this->update_category_product_simple( $data, $path );
            case 'variable':
                return $this->update_category_product_variable( $data, $path );
            default: // Bad SKU
                return false;                
        }
    }

    /**
     * Add category product: Simple Product
     *
	 * @param   object  $data
	 * @param	string	$path
     * @return  boolean|object
     */
    protected function update_category_product_simple( $data, $path ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': path[' . $path . ']' ); }

        // Single SKU OK?
        $sku = array_shift( $data->sku );
        if ( $this->debug ) { error_log( 'SKU:' . print_r( $sku, true ) ); }
        if ( false === $this->get_product_check_sku( $sku->sku_code ) ) { return false; }        

		// Get product
		$product_id = $this->get_product_by_sku( $sku->sku_code, false );
        if ( $this->debug ) { error_log( 'product ID[' . $product_id . ']' ); }
		if ( false === $product_id ) { return false; }
		
		// Update product attributes, sku, stock for Simple products
		$args = $this->update_category_product_simple_args( $path, $data, $sku, $product_id );
        if ( $this->debug ) { error_log( print_r( $args, true ) ); }

        // Set up REST process
        $products_controller = new WC_REST_Products_Controller();
        $wp_rest_request = new WP_REST_Request( 'POST' );
        $wp_rest_request->set_body_params( $args );
        $res = $products_controller->update_item( $wp_rest_request );
        
        if ( $this->debug && is_wp_error( $res ) ) { error_log( 'Error:' .  $res->get_error_message() . ']' ); }

        return is_wp_error( $res ) ? false : $res->data;
    }

    /**
     * Add category product: Simple Product
     *
	 * @param   object  $data
	 * @param	string	$path
     * @return  boolean|object
     */
    protected function update_category_product_variable( $data, $path ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': path[' . $path . ']' ); }
       
        // Initial check. Already posted SKU?
        $skus = array_map( function( $sku ) { return $sku->sku_code; }, $data->sku );
        if ( $this->debug ) { error_log( 'SKUs[' . print_r( $skus, true ) . ']' ); }
        if ( false === $this->get_product_check_sku( $skus, true ) ) { return false; }

		// Get product
		$product_id = $this->get_product_by_id( $data->eskimo_identifier, false );
        if ( $this->debug ) { error_log( 'product ID[' . $product_id . '][' . $data->eskimo_identifier . ']' ); }
		if ( false === $product_id ) { return false; }

        // Set term args
		if ( $path === 'all' ) {
	        $args = [ 
    	        'name'              => $data->title,
        	    'type'              => 'variable',
            	'description'       => ( empty( $data->long_description ) ) ? $data->short_description : $data->long_description,
            	'short_description' => $data->short_description,
				'parent_id'         => 0,
				'id'				=> $product_id
        	];            
		} else { $args = [ 'id' => $product_id ]; }

		// Set product category
		if ( $path === 'all' || $path === 'category' || $path === 'categories' ) {
			$cat_id = $this->get_category_by_id( $data );
    	    $args['categories'] = ( false === $cat_id ) ? [] : $cat_id; 
		}

		// Set variant globals
		if ( $path === 'stock' ) {
	        $args['stock_quantity'] = $this->get_product_variable_stock( $data );
		}
        if ( $this->debug ) { error_log( print_r( $args, true ) ); }

        // Set up REST process
        $wp_rest_request = new WP_REST_Request( 'POST' );
        $wp_rest_request->set_body_params( $args );
        
        $products_controller = new WC_REST_Products_Controller();
        $res = $products_controller->update_item( $wp_rest_request );

        // Add product variations
        foreach ( $data->sku as $sku ) {
            $sku->product_id = $product_id;
            $res_var = $this->update_category_product_variation( $sku, $data, $path );
        }

        if ( $this->debug && is_wp_error( $res ) ) { error_log( 'Error:' .  $res->get_error_message() . ']' ); }

        return is_wp_error( $res ) ? false : $res->data;
	}

    /**
     * Add a variation to a just created variable product
     *
	 * @param   object  $sku
	 * @param   object  $data
	 * @param	string	$path
     * @return  object|boolean
     */
    protected function update_category_product_variation( $sku, $data, $path ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $sku->product_id . ']' ); }

		// Get product
		$product_id = $this->get_product_by_sku( $sku->sku_code, true );
        if ( $this->debug ) { error_log( 'product ID[' . $product_id . ']' ); }
		if ( false === $product_id ) { return false; }

		// Update product attributes, sku, stock for Variable products
		$args = $this->update_category_product_variable_args( $path, $data, $sku, $product_id );
        if ( $this->debug ) { error_log( print_r( $args, true ) ); }

        // Set up REST process
        $products_controller = new WC_REST_Product_Variations_Controller();
        $wp_rest_request = new WP_REST_Request( 'POST' );
        $wp_rest_request->set_body_params( $args );
        $res = $products_controller->update_item( $wp_rest_request );
        
        if ( $this->debug && is_wp_error( $res ) ) { error_log( 'Error:' .  $res->get_error_message() . ']' ); }
        
        return is_wp_error( $res ) ? false : $res->data;
    }

	/**
	 * Generate args list for product update - simple
	 *
	 * @param	string	$path
	 * @param	object	$data
	 * @param	object	$sku
	 * @param	integer	$product_id
	 * @return 	array
	 */
	protected function update_category_product_simple_args( $path, $data, $sku, $product_id ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': path[' . $path . '] productID[' . $product_id . ']' ); }

        // Set term args
		if ( $path === 'all' ) {
			$args = [ 
				'id'				=> $product_id,
    	        'name'              => $data->title,
        	    'type'              => 'simple',
            	'description'       => ( empty( $data->long_description ) ) ? $data->short_description : $data->long_description,
	            'short_description' => $data->short_description,
    	        'regular_price'     => $data->from_price,
        	    'manage_stock'      => true,
				'parent_id'         => 0,
				'sku'				=> $sku->sku_code,
    	    	'stock_quantity' 	=> $sku->StockAmount,
        		'tax_class'      	=> $this->get_product_tax_class( $sku ),
        		'attributes'     	=> $this->get_product_attributes( $sku ),
			];
			$cat_id = $this->get_category_by_id( $data );
        	$args['categories'] = ( false === $cat_id ) ? [] : $cat_id; 
		} else { $args = [ 'id' => $product_id ]; }

		// Targetted
		switch( $path ) {
			case 'stock':
		        $args['stock_quantity'] = $sku->StockAmount;
				break;
			case 'tax':
		        $args['tax_class'] = $this->get_product_tax_class( $sku );
				break;
			case 'price':
				$args['regular_price'] = $data->from_price;	
				break;
			case 'category':
			case 'categories':
		    	$cat_id = $this->get_category_by_id( $data );
				$args['categories'] = ( false === $cat_id ) ? [] : $cat_id;
			   	break;	
			default:
				return $args;
		}

		return $args;
	}

	/**
	 * Generate args list for product update - simple
	 *
	 * @param	string	$path
	 * @param	object	$data
	 * @param	object	$sku
	 * @param	integer	$product_id
	 * @return 	array
	 */
	protected function update_category_product_variable_args( $path, $data, $sku, $product_id ) {

		// Set variation args
		if ( $path === 'all' ) {
			$args = [
			   	'id' 				=> $product_id,	
    	        'product_id'        => $sku->product_id,
        	    'name'              => $data->title,
            	'description'       => ( empty( $data->long_description ) ) ? $data->short_description : $data->long_description,
	            'regular_price'     => $sku->SellPrice,
    	        'manage_stock'      => true
        	];            
		} else { 
			$args = [ 
				'product_id' => $sku->product_id, 
				'id' => $product_id 
			]; 
		}
			
		// Update product stock for Simple products
		switch ( $path ) {
			case 'stock':
				$args['stock_quantity'] = $sku->StockAmount;
				break;
			case 'tax':
				$args['tax_class'] = $this->get_product_tax_class( $sku );
				break;
			case 'price':
				$args['regular_price'] = $sku->SellPrice;
				break;
		}

		return $args;
	}

    /**
     * Get product ID by SKU
     *
     * @param   string  $code
     * @param   boolean $variation
     * @return  boolean
     */
    protected function get_product_by_sku( $code, $variation = false ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }
        
        // Set up query
        $args = [
            'post_type'     => ( $variation ) ? 'product_variation' : 'product',
            'post_status'   => 'publish',
            'nopaging'      => true,
            'cache_results' => false
        ];

        // Test array or string
        $args['meta_query'] = [
            [
		        'key'     => '_sku',
		        'value'   => $code,
		        'compare' => '='
            ]
        ];

        // Process query
        $the_query = new WP_Query( $args );

        // Found post sku?
        return ( $the_query->found_posts > 0 ) ? $the_query->posts[0]->ID : false;
	}

    /**
     * Get product ID by EPOS ID
     *
     * @param   string  $code
     * @param   boolean $variation
     * @return  boolean
     */
    protected function get_product_by_id( $id, $variation = false ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . ']'  ); }
        
        // Set up query
        $args = [
            'post_type'     => ( $variation ) ? 'product_variation' : 'product',
            'post_status'   => 'publish',
            'nopaging'      => true,
            'cache_results' => false
        ];

        // Test array or string
        $args['meta_query'] = [
            [
		        'key'     => '_eskimo_product_id',
		        'value'   => $id,
		        'compare' => '='
            ]
        ];

        // Process query
        $the_query = new WP_Query( $args );

        // Found post sku?
        return ( $the_query->found_posts > 0 ) ? $the_query->posts[0]->ID : false;
	}

    /**
     * Base SKU import check to test if product SKU exists
     *
     * @param   string  $code
     * @param   boolean $variable
     * @return  boolean
     */
    protected function get_product_check_sku( $code, $variable = false ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': variable[' . (int) $variable . ']' ); }
        
        // Set up query
        $args = [
            'post_type'     => ( $variable ) ? 'product_variation' : 'product',
            'post_status'   => 'publish',
            'nopaging'      => true,
            'cache_results' => false
        ];

        // Test array or string
        if ( $variable ) {    
            $args['meta_query'] = [
                [
			        'key'     => '_sku',
			        'value'   => $code,
			        'compare' => 'IN'
                ]
            ];
        } else {
            $args['meta_query'] = [
                [
			        'key'     => '_sku',
			        'value'   => $code,
			        'compare' => '='
                ]
            ];
        }

        // Process query
        $the_query = new WP_Query( $args );
		if ( $this->debug ) { error_log( 'Found[' . $the_query->found_posts . ']' ); }

        // Found post sku?
        return ( $the_query->found_posts > 0 ) ? true : false;
    }

    /**
     * Get the global variant stock amount from SKU list
     *
     * @param   object $data
     * @return  integer
     */
    protected function get_product_variable_stock( $data ) {

        // Init stock
        $stock = 0;

        // Iterate SKU and get stock values
        foreach ( $data->sku as $sku ) {
            $stock += (int) $sku->StockAmount;
        }

        // OK, done
        return $stock;
    }

    /**
     * Get the global variant attributes: Colour & Size
     *
     * @param   object  $data
     * @return  array
     */
    protected function get_product_variable_attributes( $data ) {

        // Colour
        $colour = [
            'id'        => $this->get_product_attribute_id( 'colour' ),
            'visible'   => true, 
            'variation' => true, 
            'options'   => array_unique( array_map( function($a) { return $a->ColourName; }, $data->sku ) )
        ];

        // Add size
        $size = [
            'id'        => $this->get_product_attribute_id( 'size' ),
            'visible'   => true, 
            'variation' => true, 
            'options'   => array_unique( array_map( function($a) { return $a->Size; }, $data->sku ) )
        ];

        // OK done
        return [ $colour, $size ];
    }

    /**
     * Get the global variant attributes: Colour & Size
     *
     * @param   object  $data
     * @return  array
     */
    protected function get_product_variable_attributes_default( $data ) {

        // Get first SKU
        $sku = $data->sku[0];

        // Colour
        $colour = [
            'id'        => $this->get_product_attribute_id( 'colour' ),
            'option'    => $sku->ColourName
        ];

        // Add size
        $size = [
            'id'        => $this->get_product_attribute_id( 'size' ),
            'option'    => $sku->Size
        ];

        // OK done
        return [ $colour, $size ];
    }

    /**
     * Add a variation to a just created variable product
     *
     * @param   object  $sku
     * @return  object|boolean
     */
    protected function add_category_product_variation( $sku, $data ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $sku->product_id . ']' ); }

        // Set variation args
        $args = [ 
            'product_id'        => $sku->product_id,
            'name'              => $data->title,
            'description'       => ( empty( $data->long_description ) ) ? $data->short_description : $data->long_description,
            'regular_price'     => $sku->SellPrice,
            'manage_stock'      => true
        ];            

        // Update product attributes, sku, stock for Simple products
        $args['sku']            = $sku->sku_code;
        $args['stock_quantity'] = $sku->StockAmount;
        $args['tax_class']      = $this->get_product_tax_class( $sku );
        $args['attributes']     = $this->get_product_attributes( $sku, true );

        if ( $this->debug ) { error_log( print_r( $args, true ) ); }

        // Set up REST process
        $products_controller = new WC_REST_Product_Variations_Controller();
        $wp_rest_request = new WP_REST_Request( 'POST' );
        $wp_rest_request->set_body_params( $args );
        $res = $products_controller->create_item( $wp_rest_request );
        
        if ( $this->debug && is_wp_error( $res ) ) { error_log( 'Error:' .  $res->get_error_message() . ']' ); }
        
        return is_wp_error( $res ) ? false : $res->data;
    }

    /**
     * Update new product post with custom Eskimo Product ID
     *
     * @param   integer $cat_id
     * @param   object  $api_cat
     */
    protected function add_post_meta_eskimo_id( $prod_id, $api_prod ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': ProdID[' . $prod_id . ']' ); }
        $cat_id     = add_post_meta( $prod_id, '_eskimo_category_id', sanitize_text_field( $api_prod->eskimo_category_id ) );
        $post_id    = add_post_meta( $prod_id, '_eskimo_product_id', sanitize_text_field( $api_prod->eskimo_identifier ) );
        return ( $cat_id && $post_id ) ? true : false;
    }

    /**
     * Get tax class mapping
     *
     * @param   object  $data
     * @return  string
     */
    protected function get_product_tax_class( $sku ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // EPOS defaults
        $epos = [
            '1' => 'standard', 		// Standard
            '2'	=> 'zero-rate',		// Zero Rated
            '3'	=> 'reduced-rate',	// Reduced Rate
        ];

        // Get tax code as string
        $tax_code = trim( $sku->TaxCodeID );

        // Map tax code
        return ( array_key_exists( $tax_code, $epos ) ) ? $epos[$tax_code] : '';
    }

    /**
     * Retreive an attribute ID from name
     *
     * @param   string  $name
     * @return  integer
     */
    protected function get_product_attribute_id( $name ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ': Name[' . $name . ']' ); }

        // Get attribute list
        $attr = wc_get_attribute_taxonomies();

        // Iterate and get id
        foreach ( $attr as $k=>$v ) {
            if ( $name === $v->attribute_name ) {
                return $v->attribute_id;
            }
        }
        return 0;
    }

    /**
     * Add the product attributes ffrom sku data
     * - Uses the 2 predefined attributes: colour & size
     * 
     * @param   object  $sku
     * @return  array
     */
    protected function get_product_attributes( $sku, $variation = false ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ':  Variation[' . (int) $variation . ']' ); }

        // Add color
        $colour = [
            'id'        => $this->get_product_attribute_id( 'colour' ),
            'visible'   => true
        ];

        // Add size
        $size = [
            'id'        => $this->get_product_attribute_id( 'size' ),
            'visible'   => true
        ];

        // Simple or Variation options
        if ( $variation ) {
            $colour['option']  = $sku->ColourName;
            $size['option']    = $sku->Size;
        } else {
            $colour['options'] = [ $sku->ColourName ];
            $size['options']   = [ $sku->Size ];
        }

        // OK, done
        return [ $colour, $size ];
    }

    /**
     * Get the product type by sku count
     * - If sku's then variable otherwise simple
     * - Generally all will be variable with 1+ SKU
     *
     * @param   object $data
     * @return  string 
     */
    protected function get_product_type( $data ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }

        // SKU's should be in object
        if ( !isset( $data->sku ) ) {
            if ( $this->debug ) { error_log( 'Bad Product SKU[' . $data->eskimo_identifier . ']' ); }
            return false;
        }
        
        // Set SKU count
        $sku_count = count( $data->sku );
        if ( $this->debug) { error_log( 'SKU Product[' . $data->eskimo_identifier . '] Count[' . count( $data->sku ) . ']' ); }

        // Bad SKU
        if ( 0 === $sku_count ) { return false; }

        // How many SKU?
        return ( $sku_count > 1 ) ? 'variable' : 'simple';
    }

    /**
     * Check if the EPOS category has a WC term and get the WC value
     * - Should be no surprises here, parents should all be pre-processed
     * - Children with orphan parent EPOS cat treated as bad data & logged
     *
     * @param   object  $data   Category data
     */
    protected function get_category_by_id( $data ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }
        
        // Get terms with the epos category id... should be 1 or 0
        $args = [
            'hide_empty' => false, 
            'meta_query' => [
                [
                    'key'       => 'eskimo_category_id',
                    'value'     => $data->eskimo_category_id,
                    'compare'   => '='
                ]
            ]
        ];

        // Get terms from product_cat taxonomy
        $terms = get_terms( 'product_cat', $args );

        if ( $this->debug && is_wp_error( $terms ) ) { error_log( 'Error:' .  $terms->get_error_message() . ']' ); }

        // No terms or Error
        if ( empty( $terms ) || is_wp_error( $terms ) ) {
            if ( $this->debug ) { error_log( 'Bad Product Category Term[' . $data->eskimo_category_id . ']' ); }
            return false;
        }

        if ( $this->debug ) { error_log( 'Product Category Terms[' . print_r( $terms, true ) . ']' ); }

        // Structure cats
        $cats = [];
        foreach ( $terms as $k=>$t ) {
            $cats[] = [ 'id' => $t->term_id ];
        }

        if ( $this->debug ) { error_log( 'Cats[' . print_r( $cats, true ) . ']' ); }

        return $cats;
    }

    //----------------------------------------------
    // API Error
    //----------------------------------------------

    /**
     * Log API REST Process Error
     */
    protected function api_rest_error() {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }
		if ( $this->debug ) { error_log( __( 'API Error: Could Not Process REST data from API', 'eskimo' ) ); }
		return __( 'API Error: Could Not Process REST data from API', 'eskimo' );
	}

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
	 * External identifier for WebOrder
	 *
	 * @param integer $length default 10
	 * @param integer $start default 0
	 * @return string
	 */
	protected function keygen( $length = 10, $start = 0 ) {
		return substr( str_shuffle( sha1( microtime( true ). mt_rand( 10000,90000 ) ) ), $start, $length );
	}
}
