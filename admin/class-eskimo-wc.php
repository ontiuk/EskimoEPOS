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

        // OK, done
        return true;
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

        // Set term args
        $args = [ 
            'name'              => $data->title,
            'type'              => 'variable',
            'description'       => ( empty( $data->long_description ) ) ? $data->short_description : $data->long_description,
            'short_description' => $data->short_description,
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
     * Base SKU import check to test if product SKU exists
     *
     * @param   string  $code
     * @param   boolean $variable
     * @return  boolean
     */
    protected function get_product_check_sku( $code, $variable = false ) {
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }
        
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
            '1' => 'Standard',
            '2'	=> 'Zero Rated'
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
    }
}
