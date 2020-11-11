<?php

/**
 * Import & Export from Woocommerce via the EskimoEPOS API Data
 *
 * @link       https://on.tinternet.co.uk
 * @package    Eskimo
 * @subpackage Eskimo/admin
 */

/**
 * EskimoEPOS product, category, customer and order processing and sync
 * 
 * Woocommerce import and export for caregories, products, customers and orders
 *
 * @package    Eskimo
 * @subpackage Eskimo/admin
 * @author     Stephen Betley <on@tinternet.co.uk>
 */
final class Eskimo_WC {

	/**
	 * The ID of this plugin
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
	 * Initialize the class and set its properties
	 *
	 * @param   string    $eskimo     The name of this plugin
	 */
	public function __construct( $eskimo ) {
   
		// Set up class settings
		$this->eskimo   	= $eskimo;
   		$this->version  	= ESKIMO_VERSION;
		$this->debug    	= ESKIMO_WC_DEBUG;
	 	$this->base_dir		= plugin_dir_url( __FILE__ ); 
	}

    //----------------------------------------------
    // Woocommerce Category Import
    //----------------------------------------------

    /**
     * Get EskimoEPOS API categories & import to Woocommerce
     *
     * @param   array   		$api_data
	 * @return	object|array
     */
    public function get_categories_all( $api_data ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'wc' ); }

        // Validate API data
        if ( empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // Process data
        if ( $this->debug ) { eskimo_log( 'Process Categories[' . count( $api_data ) . ']', 'wc' ); }

        // Web_ID
        $web_prefix = get_option( 'eskimo_api_category' ); 
        if ( $this->debug ) { eskimo_log( 'Web Prefix[' . $web_prefix . ']', 'wc' ); }

        // Process parent & child categories
        $parent = $child = [];

        // Get parent & child categories
        foreach ( $api_data as $api_cat ) {

            // Product Categories Only... Change depending on implementation
            if ( ! preg_match( '/product$/i', $api_cat->Eskimo_Category_ID ) ) { continue; }
            
            // Already with a Web_ID so pre-existing in WC & Temp 'zero' reset
            if ( ! empty( $api_cat->Web_ID ) && $api_cat->Web_ID !== '0' ) {
                if ( $this->debug ) { eskimo_log( 'Cat ID[' . $api_cat->Eskimo_Category_ID . '] Exists [' . $api_cat->Web_ID . ']', 'wc' ); }
                continue; 
            }

            // Use category: parent or child?
            if ( empty( $api_cat->ParentID ) ) {
                $parent[] = $api_cat;
            } else {
				$child[] = $api_cat;
			}
        }
        if ( $this->debug ) { eskimo_log( 'EPOS Cats: Parent[' . count( $parent ) . '] Child[' . count( $child ) . ']', 'wc' ); }

        // Return data
        $result = [];

        // Process parent categories first
        foreach ( $parent as $api_cat ) {

            // Insert term
            $cat_term = $this->add_product_category_rest( $api_cat );
            if ( empty( $cat_term ) || is_wp_error( $cat_term ) ) {
                if ( $this->debug ) { eskimo_log( 'Bad parent term insert ID[' . $api_cat->Eskimo_Category_ID . '][' . $api_cat->ShortDescription . ']', 'wc' ); }
                continue;
            }
            
            if ( $this->debug ) { eskimo_log( 'Cat Term[' . print_r( $cat_term, true ) . ']', 'wc' ); }

            // Update Eskimo CatID
            $term_meta_id = $this->add_term_eskimo_cat_id( $cat_term['id'], $api_cat );
            if ( false === $term_meta_id || is_wp_error( $term_meta_id ) ) {
                if ( $this->debug ) { eskimo_log( 'Bad term meta insert[' . $cat_term['id'] . ']', 'wc' ); }
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
                if ( $this->debug ) { eskimo_log( 'Bad child term insert ID[' . $api_cat->Eskimo_Category_ID . '][' . $api_cat->ShortDescription . ']', 'wc' ); }
                continue;
            }

            if ( $this->debug ) { eskimo_log( 'Cat Term[' . print_r( $cat_term, true )  . ']', 'wc' ); }

            // Update Eskimo CatID
            $term_meta_id = $this->add_term_eskimo_cat_id( $cat_term['id'], $api_cat );
            if ( false === $term_meta_id || is_wp_error( $term_meta_id ) ) {
                if ( $this->debug ) { eskimo_log( 'Bad term meta insert[' . $cat_term['id'] . ']', 'wc' ); }
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
     * Get EskimoEPOS category by ID & import to Woocommerce
     *
     * @param   array   		$api_cat
	 * @return	object|array
     */
    public function get_categories_specific_ID( $api_cat ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'wc' ); }

        // Validate API data
        if ( empty( $api_cat ) ) {
            return $this->api_rest_error();
        }

        // Process data
        if ( $this->debug ) { eskimo_log( 'Process Category[' . $api_cat->Eskimo_Category_ID . ']', 'wc' ); }

        // Web_ID
        $web_prefix = get_option( 'eskimo_api_category' ); 
        if ( $this->debug ) { eskimo_log( 'Web Prefix[' . $web_prefix . ']', 'wc' ); }

        // Product Categories Only
        if ( ! preg_match( '/product$/i', $api_cat->Eskimo_Category_ID ) ) { return false; }
            
        // Already with a Web_ID so pre-existing in WC
        if ( ! empty( $api_cat->Web_ID ) && $api_cat->Web_ID !== '0' ) { 
            if ( $this->debug ) { eskimo_log( 'Cat ID[' . $api_cat->Eskimo_Category_ID . '] Exists [' . $api_cat->Web_ID . ']', 'wc' ); }
            return $this->api_error( 'Cat ID[' . $api_cat->Eskimo_Category_ID . '] Already Imported [' . $api_cat->Web_ID . ']' );
        }

        // Parent or Child
        $parent = ( empty( $api_cat->ParentID ) ) ? true : false;

        // Insert term
        $cat_term = $this->add_product_category_rest( $api_cat, !$parent );
        if ( empty( $cat_term ) || is_wp_error( $cat_term ) ) {
            if ( $this->debug ) { eskimo_log( 'Bad term insert ID[' . $api_cat->Eskimo_Category_ID . '][' . $api_cat->ShortDescription . ']', 'wc' ); }
			return $this->api_error( 'Bad category term insert ID[' . $api_cat->Eskimo_Category_ID . '][' . $api_cat->ShortDescription . ']' );
        }
        
        if ( $this->debug ) { eskimo_log( 'Cat Term[' . print_r( $cat_term, true )  . ']', 'wc' ); }

        // Update Eskimo CatID
        $term_meta_id = $this->add_term_eskimo_cat_id( $cat_term['id'], $api_cat );
        if ( false === $term_meta_id || is_wp_error( $term_meta_id ) ) {
            if ( $this->debug ) { eskimo_log( 'Bad term meta insert[' . $cat_term['id'] . ']', 'wc' ); }
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
     * Get EskimoEPOS API categories by parent ID and import to Woocommerce
     *
     * @param   array   		$api_data
	 * @return	object|array
     */
    public function get_categories_child_categories_ID( $api_data ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'wc' ); }

        // Validate API data
        if ( empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // Web_ID
        $web_prefix = get_option( 'eskimo_api_category' ); 
        if ( $this->debug ) { eskimo_log( 'Web Prefix[' . $web_prefix . ']', 'wc' ); }

        // Process parent & child categories
        $child = [];

        // Get parent & child categories
        foreach ( $api_data as $api_cat ) {

            // Product Categories Only
            if ( ! preg_match( '/product$/i', $api_cat->Eskimo_Category_ID ) ) { continue; }
            
            // Already with a Web_ID so pre-existing in WC
            if ( !empty( $api_cat->Web_ID ) && $api_cat->Web_ID !== '0' ) { 
                if ( $this->debug ) { eskimo_log( 'Cat ID[' . $api_cat->Eskimo_Category_ID . '] Exists [' . $api_cat->Web_ID . ']', 'wc' ); }
                continue; 
            }

            // Should have a parent wft!
            if ( empty( $api_cat->ParentID ) ) { continue; }

            // Log cat
            $child[] = $api_cat;
        }
        if ( $this->debug ) { eskimo_log( 'EPOS Cats: [' . count( $child ) . ']', 'wc' ); }

        // Return data
        $result = [];

        // Process child categories last
        foreach ( $child as $api_cat ) {

            // Insert term
            $cat_term = $this->add_product_category_rest( $api_cat, true );
            if ( empty( $cat_term ) || is_wp_error( $cat_term ) ) {
                if ( $this->debug ) { eskimo_log( 'Bad child term insert ID[' . $api_cat->Eskimo_Category_ID . '][' . $api_cat->ShortDescription . ']', 'wc' ); }
                continue;
            }
            if ( $this->debug ) { eskimo_log( 'Cat Term[' . print_r( $cat_term, true )  . ']', 'wc' ); }

            // Update Eskimo CatID
            $term_meta_id = $this->add_term_eskimo_cat_id( $cat_term['id'], $api_cat );
            if ( false === $term_meta_id || is_wp_error( $term_meta_id ) ) {
                if ( $this->debug ) { eskimo_log( 'Bad term meta insert[' . $cat_term['id'] . ']', 'wc' ); }
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
     * Get category data for EskimoEPOS Web_ID update
     *
     * @param   array   $api_data
     * @return  boolean
     */
    public function get_categories_cart_ID( $api_data ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'wc' ); }

        // Validate API data
        if ( empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // Process data
        if ( $this->debug ) { eskimo_log( 'Process Categories ID[' . count( $api_data ) . ']', 'wc' ); }

        // Process parent & child categories
        $categories = [];

        // Get parent & child categories
        foreach ( $api_data as $api_cat ) {

            // Product Categories Only
            if ( ! preg_match( '/product$/i', $api_cat->Eskimo_Category_ID ) ) { continue; }
            
            // Already with a Web_ID so pre-existing in WC
            if ( empty( $api_cat->Web_ID ) ) { 
                if ( $this->debug ) { eskimo_log( 'Cat ID[' . $api_cat->Eskimo_Category_ID . '] NOT Exists Web_ID', 'wc' ); }
                continue; 
            }

            // Add cat
            $categories[] = $api_cat;
        }
        if ( $this->debug ) { eskimo_log( 'EPOS Cats:' . count( $categories ), 'wc' ); }

        // Return data
        $result = [];

        // Process parent categories first
        foreach ( $categories as $api_cat ) {

            // Load into response list. Zero Reset
            $result[] = [
                'Eskimo_Category_ID'    => $api_cat->Eskimo_Category_ID,
                'Web_ID'                => ''
            ];
        }

        // OK, done
        return $result;
	}

	/**
	 * Retrieve current Woocommerce product category data
	 *
	 * @return	array
	 */
	public function get_categories_web_ID() {

		// Default category args
		$args = [
			'taxonomy'     => 'product_cat',
			'orderby'      => 'name',
			'show_count'   => 0,
			'pad_counts'   => 0,
			'hierarchical' => 0,
			'title_li'     => '',
			'hide_empty'   => 0
		];

		// Get the cats
		$the_cats 	= get_categories( $args );
        $web_prefix = get_option( 'eskimo_api_category' ); 

		// Construct web_id results
        $result = [];
		foreach ( $the_cats as $cat ) {

			$eskimo_cat_id 	= get_term_meta( $cat->term_id, 'eskimo_category_id', true );
			if ( empty( $eskimo_cat_id ) ) { continue; }

			$result[] = [
                'Eskimo_Category_ID'    => $eskimo_cat_id,
                'Web_ID'                => ( empty( $web_prefix ) ) ? $cat->term_id : $web_prefix . $cat->term_id
			];			
		}

		return $result;
	}

    /**
     * Get EskimoEPOS category meta data
     *
     * @param   array   $api_data
     * @return  boolean
     */
    public function get_categories_meta_ID( $api_data ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'wc' ); }

        // Validate API data
        if ( empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // Process data
        if ( $this->debug ) { eskimo_log( 'Process Categories ID[' . count( $api_data ) . ']', 'wc' ); }

        // Process parent & child categories
        $categories = [];

        // Get parent & child categories
        foreach ( $api_data as $api_cat ) {

            // Product Categories Only
            if ( ! preg_match( '/product$/i', $api_cat->Eskimo_Category_ID ) ) { continue; }
            
            // Add cat
            $categories[] = $api_cat;
		}
		
		if ( $this->debug ) { 
			eskimo_log( 'EPOS Cats:' . count( $categories ), 'wc' ); 
			eskimo_log( print_r( $categories, true ), 'wc' );
		}

        // Return data
        $result = [];

		// Get category data
		foreach ( $categories as $category ) {
			$slug = sanitize_title( $category->ShortDescription );
			eskimo_log( 'Slug:' . $slug . ']', 'wc' ); 

			$term = get_term_by('slug', $slug, 'product_cat');
			
			$results[] = [
				'eskimo_category_id' => $category->Eskimo_Category_ID, 
				'category_id'		 => $term->term_id
			];
		}

		if ( empty( $results ) ) { 
			return $this->api_error( 'No Categories to process' );
		}

		// Update meta data
		foreach ( $results as $result ) {
			update_term_meta( $result['category_id'], 'eskimo_category_id', sanitize_text_field( $result['eskimo_category_id'] ) );
		}

        // OK, done
        return $results;
	}

    //----------------------------------------------
    // Woocommerce Product Import
    //----------------------------------------------

    /**
     * Get EskimoEPOS API products by category
     *
     * @param   array   		$api_data
     * @return  object|array
     */
    public function get_products_all( $api_data ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'wc' ); }

         // Validate API data
        if ( empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // Process data
        if ( $this->debug ) { eskimo_log( 'Process Products All[' . count( $api_data ) . ']', 'wc' ); }

        // Web_ID
        $web_prefix = get_option( 'eskimo_api_product' ); 
        if ( $this->debug ) { eskimo_log( 'Web Prefix[' . $web_prefix . ']', 'wc' ); }

        // Process products
        $products = [];

        // Get products list
        foreach ( $api_data as $api_prod ) {

            // Product Categories Only
            if ( ! preg_match( '/product$/i', $api_prod->eskimo_category_id ) ) { continue; }

            // Dodgy Title?
            if ( empty( $api_prod->title ) ) {
                if ( $this->debug ) { eskimo_log( 'Prod ID[' . $api_prod->eskimo_category_id . '] Title NOT Exists', 'wc' ); }
                continue; 
            }

            // Requires that the Eskimo Category has been imported
            if ( empty( $api_prod->web_category_id ) || $api_prod->web_category_id === '0' ) { 
                if ( $this->debug ) { eskimo_log( 'Cat ID[' . $api_prod->eskimo_category_id . '] NOT Exists Cat Web_ID', 'wc' ); }
                continue; 
            }

            // Requires that the Eskimo Product has NOT been imported
            if ( !empty( $api_prod->web_id ) && $api_prod->web_id !== '0' ) { 
                if ( $this->debug ) { eskimo_log( 'Prod ID[' . $api_prod->eskimo_identifier . '] Web_ID Exists [' . $api_prod->web_id . ']', 'wc' ); }
                continue; 
            }

            // Required valid product sku data
            if ( empty( $api_prod->sku ) ) { 
                if ( $this->debug ) { eskimo_log( 'Product SKU Not Set ID[' . $api_prod->eskimo_identifier . ']', 'wc' ); }
                continue; 
            }

            // OK add products
            $products[] = $api_prod;
        }

        if ( $this->debug ) { eskimo_log( 'EPOS Prods: [' . count( $products ) . ']', 'wc' ); }

        // Something to do?        
        if ( empty( $products ) ) { return $this->api_error( 'No Products To Process' ); }

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
                if ( $this->debug ) { eskimo_log( 'Bad post meta insert[' . $prod['id'] . ']', 'wc' ); }
			}

            // Update Eskimo ProdID
            $prod_meta_id = $this->add_post_meta_extra( $prod['id'], $api_prod );
            if ( false === $prod_meta_id || is_wp_error( $prod_meta_id ) ) {
                if ( $this->debug ) { eskimo_log( 'Bad post meta extra insert[' . $prod['id'] . ']', 'wc' ); }
			}

            // Update Eskimo Style
            $prod_meta_id = $this->add_post_meta_more( $prod['id'], $api_prod );
            if ( false === $prod_meta_id || is_wp_error( $prod_meta_id ) ) {
                if ( $this->debug ) { eskimo_log( 'Bad post meta more insert[' . $prod['id'] . ']', 'wc' ); }
            }

			// Update Eskimo ProdID
            $prod_meta_id = $this->add_post_meta_date( $prod['id'], $api_prod );
            if ( false === $prod_meta_id || is_wp_error( $prod_meta_id ) ) {
                if ( $this->debug ) { eskimo_log( 'Bad post meta date insert[' . $prod['id'] . ']', 'wc' ); }
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
     * Get EskimoEPOS API products by category
     *
	 * @param   array	 		$api_data
     * @return  object|array
     */
    public function get_products_new( $api_data ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'wc' ); }

         // Validate API data
        if ( empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // Process data
        if ( $this->debug ) { eskimo_log( 'Process Products New[' . count( $api_data ) . ']', 'wc' ); }

        // Process products
        $products = [];

        // Get products list
        foreach ( $api_data as $api_prod ) {

            // Product Categories Only
            if ( ! preg_match( '/product$/i', $api_prod->eskimo_category_id ) ) { continue; }

            // Dodgy Title?
            if ( empty( $api_prod->title ) ) {
                if ( $this->debug ) { eskimo_log( 'Prod ID[' . $api_prod->eskimo_category_id . '] Title NOT Exists', 'wc' ); }
                continue; 
            }
				
			// Requires that the Eskimo Category has been imported
            if ( empty( $api_prod->web_category_id ) || $api_prod->web_category_id === '0' ) { 
                if ( $this->debug ) { eskimo_log( 'Cat ID[' . $api_prod->eskimo_category_id . '] NOT Exists Cat Web_ID', 'wc' ); }
                continue; 
            }

            // Requires that the Eskimo Product has NOT been imported
            if ( !empty( $api_prod->web_id ) && $api_prod->web_id !== '0' ) { 
                if ( $this->debug ) { eskimo_log( 'Prod ID[' . $api_prod->eskimo_identifier . '] Web_ID Exists [' . $api_prod->web_id . ']', 'wc' ); }
                continue; 
			}

            // OK add products
            $products[] =  $api_prod->eskimo_identifier;
		}

		// New product count
		if ( $this->debug ) { 
			eskimo_log( 'New Prod Count[' . count( $products ) . '] Products[' . print_r( $products, true ) . ']', 'wc' );
		}

        // OK, done
        return $products;
    }

    /**
     * Get EskimoEPOS API product by ID
     *
     * @param   array   		$api_data
     * @return  object|array
     */
    public function get_products_specific_ID( $api_prod ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'wc' ); }

        // Validate API data
        if ( empty( $api_prod ) ) {
            return $this->api_rest_error();
        }

        // Process data
        if ( $this->debug ) { eskimo_log( 'Process Product [' . $api_prod->eskimo_category_id . '] SKUs[' . count( $api_prod->sku ) . ']', 'wc' ); }

        // Web_ID
        $web_prefix = get_option( 'eskimo_api_product' ); 
        if ( $this->debug ) { eskimo_log( 'Web Prefix[' . $web_prefix . ']', 'wc' ); }

        // Product Categories Only
        if ( ! preg_match( '/product$/i', $api_prod->eskimo_category_id ) ) { return false; }

        // Dodgy Title?
        if ( empty( $api_prod->title ) ) {
            if ( $this->debug ) { eskimo_log( 'Prod ID[' . $api_prod->eskimo_category_id . '] Title NOT Exists', 'wc' ); }
            return $this->api_error( 'Prod ID[' . $api_prod->eskimo_category_id . '] Title DOES NOT Exists' ); 
        }

        // Requires that the Eskimo Category has been imported
        if ( empty( $api_prod->web_category_id ) || $api_prod->web_category_id === '0' ) { 
            if ( $this->debug ) { eskimo_log( 'Cat ID[' . $api_cat->eskimo_category_id . '] NOT Exists Cat Web_ID', 'wc' ); }
            return $this->api_error( 'Cat ID[' . $api_cat->eskimo_category_id . '] NOT Exists Cat Web_ID' );; 
        }

        // Requires that the Eskimo Product has NOT been imported
        if ( !empty( $api_prod->web_id ) && $api_prod->web_id !== '0' ) { 
            if ( $this->debug ) { eskimo_log( 'Prod ID[' . $api_prod->eskimo_identifier . '] Web_ID Exists [' . $api_prod->web_id . ']', 'wc' ); }
            return $this->api_error( 'Prod ID[' . $api_prod->eskimo_identifier . '] Web_ID Exists [' . $api_prod->web_id . ']' ); 
        }

        // Required valid product sku data
        if ( empty( $api_prod->sku ) ) { 
            if ( $this->debug ) { eskimo_log( 'Product SKU Not Set ID[' . $api_prod->eskimo_identifier . ']', 'wc' ); }
            return $this->api_error( 'Product SKU Not Set ID[' . $api_prod->eskimo_identifier . ']' );; 
        }

        // Insert product post
        $prod = $this->add_category_product_rest( $api_prod );
        if ( false === $prod || is_wp_error ( $prod ) ) { return $this->api_error( 'Bad Category Product Insert' ); }

        // Update Eskimo ProdID
        $prod_meta_id = $this->add_post_meta_eskimo_id( $prod['id'], $api_prod );
        if ( false === $prod_meta_id || is_wp_error( $prod_meta_id ) ) {
            if ( $this->debug ) { eskimo_log( 'Bad post meta insert[' . $prod['id'] . ']', 'wc' ); }
        }

		// Update Eskimo ProdID
		$prod_meta_id = $this->add_post_meta_extra( $prod['id'], $api_prod );
		if ( false === $prod_meta_id || is_wp_error( $prod_meta_id ) ) {
			if ( $this->debug ) { eskimo_log( 'Bad post meta extra insert[' . $prod['id'] . ']', 'wc' ); }
		}
		
        // Update Eskimo Style
        $prod_meta_id = $this->add_post_meta_more( $prod['id'], $api_prod );
        if ( false === $prod_meta_id || is_wp_error( $prod_meta_id ) ) {
            if ( $this->debug ) { eskimo_log( 'Bad post meta more insert[' . $prod['id'] . ']', 'wc' ); }
        }

		// Update Eskimo ProdID
		$prod_meta_id = $this->add_post_meta_date( $prod['id'], $api_prod );
		if ( false === $prod_meta_id || is_wp_error( $prod_meta_id ) ) {
			if ( $this->debug ) { eskimo_log( 'Bad post meta date insert[' . $prod['id'] . ']', 'wc' ); }
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
     * Get EskimoEPOS API product by ID
     *
     * @param   array   		$api_data
     * @param   array   		$path
     * @return  object|array
     */
    public function get_products_import_ID( $api_prod, $path ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ':' . $path, 'wc' ); }

        // Validate API data
        if ( empty( $api_prod ) ) {
            return $this->api_rest_error();
        }

        // Process data
        if ( $this->debug ) { eskimo_log( 'Process Product SKUs[' . count( $api_prod->sku ) . '] path[' . $path . ']', 'wc' ); }

        // Product Categories Only
        if ( ! preg_match( '/product$/i', $api_prod->eskimo_category_id ) ) { return false; }

        // Dodgy Title?
        if ( empty( $api_prod->title ) ) {
            if ( $this->debug ) { eskimo_log( 'Prod ID[' . $api_prod->eskimo_category_id . '] Title NOT Exists', 'wc' ); }
            return $this->api_error( 'Prod ID[' . $api_prod->eskimo_category_id . '] Title NOT Exists' );
        }

        // Requires that the Eskimo Category has been imported
        if ( empty( $api_prod->web_category_id ) || $api_prod->web_category_id === '0' ) { 
            if ( $this->debug ) { eskimo_log( 'Cat ID[' . $api_prod->eskimo_category_id . '] NOT Exists: Cat Web_ID', 'wc' ); }
            return $this->api_error( 'Cat ID[' . $api_prod->eskimo_category_id . '] NOT Exists: Cat Web_ID' ); 
        }

        // Requires that the Eskimo Product has NOT been imported
        if ( empty( $api_prod->web_id ) || $api_prod->web_id == '0' ) { 
            if ( $this->debug ) { eskimo_log( 'Prod ID[' . $api_prod->eskimo_identifier . '] Web_ID Not Exists [' . $api_prod->web_id . ']', 'wc' ); }
            return $this->api_error( 'Prod ID[' . $api_prod->eskimo_category_id . '] Title NOT Exists' );
        }

        // Required valid product sku data
        if ( empty( $api_prod->sku ) ) { 
            if ( $this->debug ) { eskimo_log( 'Product SKU Not Set ID[' . $api_prod->eskimo_identifier . ']', 'wc' ); }
            return $this->api_error( 'Prod ID[' . $api_prod->eskimo_category_id . '] Title NOT Exists' );
        }

		// Due process by path  
        $prod = $this->update_category_product_rest( $api_prod, $path );
        if ( false === $prod || is_wp_error ( $prod ) ) { return $this->api_error( 'Category Product Update Error' ); }

        // OK, done 
        return [
            [
				'Eskimo_Identifier' => $api_prod->eskimo_identifier,
                'Web_ID'            => $api_prod->web_id
            ]
        ];
	}

    /**
     * Get EskimoEPOS API product by ID
     *
     * @param   string   		$prod_ref
     * @param   string   		$trade_ref
     * @return  object|array
     */
	public function get_products_trade_ID( $prod_ref, $trade_ref ) {
		if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': StyleRef[' . str_replace( '|', '', $prod_ref ) . ']', 'wc' ); }

		// Default category args
		$args = [
			'post_type'			=> 'product',
			'posts_per_page'	=> 1,
			'post_status'		=> 'publish',
   			'cache_results' 	=> false,
			'meta_key'			=> '_eskimo_product_id',
			'meta_value'		=> $prod_ref
        ];

        // Process query
        $the_query = new WP_Query( $args );

        // Found post sku?
		if ( $the_query->found_posts === 0 ) {
			return $this->api_error( 'No product found for prod ref[' . $prod_ref . ']' );
		}

		// Get product
		$product = $the_query->posts[0];

		// Get the product prefix
        $web_prefix = get_option( 'eskimo_api_product' ); 

        if ( $this->debug ) { eskimo_log( 'Products[' . $the_query->found_posts . '] ProdID[' . $product->ID . '] Prefix[' . $web_prefix . ']', 'wc' ); }

		// Ok, update post meta
		$update = update_post_meta( $product->ID, '_eskimo_product_id', $trade_ref );
		if ( false === $update ) {
			return $this->api_error( 'Bad post meta update for prod ref[' . $prod_ref . ']' );
		}
	
		// ok, done
		return [
			[
				'Eskimo_Identifier' => $trade_ref,
                'Web_ID'            => ( empty( $web_prefix ) ) ? $product->ID : $web_prefix . $product->ID
			]
		];			
	}

    /**
     * Get EskimoEPOS API products by category
     *
     * @param   array   		$api_data
     * @return  object|array
     */
    public function get_products_cart_ID( $api_data ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'wc' ); }

         // Validate API data
        if ( empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // Process data
        if ( $this->debug ) { eskimo_log( 'Process Products Cart ID[' . count( $api_data ) . ']', 'wc' ); }

        // Process products
        $products = [];

        // Get parent & child categories
        foreach ( $api_data as $api_prod ) {

            // Product Categories Only
            if ( ! preg_match( '/product$/i', $api_prod->eskimo_category_id ) ) { continue; }

            // Requires that the Eskimo Category has been imported
            if ( empty( $api_prod->web_category_id ) ) { 
                if ( $this->debug ) { eskimo_log( 'Prod Cat ID[' . $api_prod->eskimo_category_id . '] NOT Exists Cat Web_ID', 'wc' ); }
                continue; 
            }

            // Requires that the Eskimo Product has NOT been imported
            if ( empty( $api_prod->web_id ) || $api_prod->web_id === '0' ) { 
                if ( $this->debug ) { eskimo_log( 'Prod ID[' . $api_prod->eskimo_identifier . '] Not Exists Web_ID', 'wc' ); }
                continue; 
            }

            // OK add products
            $products[] = $api_prod;
        }

        if ( $this->debug ) { eskimo_log( 'EPOS Prods: [' . count( $products ) . ']', 'wc' ); }

        // Something to do?        
        if ( empty( $products ) ) { return $this->api_error( 'No Products To Process' ); }

        // Return data
        $result = [];

        // Process parent categories first
        foreach ( $products as $api_prod ) {

            // Load into response list. Temp zero reset
            $result[] = [
                'Eskimo_Identifier' => $api_prod->eskimo_identifier,
                'Web_ID'            => ''
            ];
        }

        // OK, done
        return $result;
	}

	/**
	 * Retrieve current Woocommerce products
	 *
	 * @return	array
	 */
	public function get_products_web_ID() {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'wc' ); }

		// Default category args
		$args = [
			'post_type'			=> 'product',
			'posts_per_page'	=> -1,
			'post_status'		=> 'publish',
            'nopaging'      	=> true,
            'cache_results' 	=> false
        ];

        // Process query
        $the_query = new WP_Query( $args );

        // Found post sku?
		if ( $the_query->found_posts === 0 ) {
			return $this->api_error( 'No products found' );
		}

        if ( $this->debug ) { eskimo_log( 'Products[' . $the_query->found_posts . ']', 'wc' ); }

		// Get the product prefix
        $web_prefix = get_option( 'eskimo_api_product' ); 

		// Construct web_id results
        $result = [];
		foreach ( $the_query->posts as $product ) {

			$eskimo_prod_id = get_post_meta( $product->ID, '_eskimo_product_id', true );
	        if ( $this->debug ) { eskimo_log( 'Product[' . $product->ID . '][' . $eskimo_prod_id . ']', 'wc' ); }
			
			if ( empty( $eskimo_prod_id ) ) { continue; }

			$result[] = [
                'Eskimo_Identifier'	=> $eskimo_prod_id,
                'Web_ID'            => ( empty( $web_prefix ) ) ? $product->ID : $web_prefix . $product->ID
			];			
		}

		return $result;
	}

    /**
     * Get EskimoEPOS API product by ID
     *
     * @param   string   		$path
     * @param   string   		$product_id
     * @return  object|array
     */
	public function get_products_stock( $path, $product_id ) {
		if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': Path[' . $path . '] ID[' . $product_id . ']', 'wc' ); }

		// Get product
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return $this->api_error( 'Invalid Product for ID [' . $product_id . ']' );
		}
	
		// Process simple & multi
		// if path === adjust / multi, split functions?
		
		// Get stock $ SKU
		$product_sku = $product->get_sku();
		$product_qty = $product->get_stock_quantity();
		if ( $this->debug ) { eskimo_log( 'Product ID [' . $product_id . '] Stock: SKU[' . $product_sku . '] Qty[' . $product_qty . ']', 'wc' ); }

		// No SKU?
		if ( empty( $product_sku ) ) { 
			return $this->api_error( 'Invalid SKU for ID [' . $product_id . ']' );
		}

		// ok, done
		return [
			'Adjustment' => [
				'SKU_Code' 			=> $product_sku,
				'StockLocation'		=> 1, // 1,2,3
				'AdjustmentType'	=> 1, // 1: Update total, 2: Update add to total
				'Amount'			=> $product_qty,
				'AdjustmentReason' 	=> 'Web Adjust'
			],
  			'ReturnStockLevels'		=> true
		];			
	}

    //----------------------------------------------
    // Woocommerce Customer Import & Export
    //----------------------------------------------

    /**
     * Get EskimoEPOS API customer data
     *
     * @param   array   		$api_data
     * @return  object|array
     */
    public function get_customers_specific_ID( $api_data ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'wc' ); }

        // Validate API data
        if ( empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // Process data
        if ( $this->debug ) { eskimo_log( 'Process Customer[' . $api_data->EmailAddress . ']', 'wc' ); }

		// Email exists?
		$email = filter_var( $api_data->EmailAddress, FILTER_SANITIZE_EMAIL );
		if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
			return $this->api_error( 'Invalid Customer Email[' . esc_html( $email ) . ']' );
		}

		// Set up address
		if ( ! empty( $api_data->Address ) ) {
			$addr 	= $explode( "\r\n", $api_data->Address );
			$addr_1 = array_shift( $addr );
			$city	= ( count( $addr ) ) ? $addr[0] : '';
		} else {
			$addr_1 = $city = '';
		}
        if ( $this->debug ) { eskimo_log( 'Customer Addr[' . $addr_1 . '] city[' . $city . ']', 'wc' ); }

		// Set up user with format forename.surname
		$username = $api_data->Forename . '.' . $api_data->Surname;

        if ( $this->debug ) { eskimo_log( 'Create User: Email[' . $email . '] Username[' . $username . ']', 'wc' ); }

		// Generate WC user if possible - autogenerate password
		$user_id = wc_create_new_customer( $email, $username );
		if ( is_wp_error( $user_id ) ) {
			return $this->api_error( $user_id->get_error_message() );
		}
        if ( $this->debug ) { eskimo_log( 'User ID[' . $user_id . ']', 'wc' ); }

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
        if ( $this->debug ) { eskimo_log( 'OK Customer ID[' . $user_id . ']', 'wc' ); }

		// User meta
		add_user_meta( $user_id, 'epos_id', $api_data->ID );
		add_user_meta( $user_id, 'epos_notes', $api_data->Notes );
		add_user_meta( $user_id, 'epos_active', (int) $api_data->ActiveAccount );
		add_user_meta( $user_id, 'epos_title', ( empty( $api_data->TitleID ) ) ? '' : (int) $api_data->TitleID );
		add_user_meta( $user_id, 'epos_country', $api_data->CountryCode );

		// OK, got new user, add billing details
		add_user_meta( $user_id, 'billing_first_name', $api_data->Forename );
		add_user_meta( $user_id, 'billing_last_name', $api_data->Surname );
		add_user_meta( $user_id, 'billing_company', $api_data->CompanyName );
		add_user_meta( $user_id, 'billing_address_1', $addr_1 );
		add_user_meta( $user_id, 'billing_city', $city );
		add_user_meta( $user_id, 'billing_postcode', $api_data->PostCode );
		add_user_meta( $user_id, 'billing_state', '' );
		add_user_meta( $user_id, 'billing_country', $api_data->CountryCode ); //GB 
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
		add_user_meta( $user_id, 'shipping_state', '' );
		add_user_meta( $user_id, 'shipping_country', $api_data->CountryCode ); //GB
		add_user_meta( $user_id, 'shipping_email', $api_data->EmailAddress );
		add_user_meta( $user_id, 'shipping_phone', $api_data->Telephone );
		add_user_meta( $user_id, 'shipping_mobile', $api_data->Mobile );

        // OK, done
        return 'User ID[' . $user_id . '] Username[' . $username . ']';
	}

    /**
     * Get customer data for insert by ID
     *
     * @param   integer   		$id
     * @return  object|array
     */
    public function get_customers_insert_ID( $id ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ' ID[' . $id . ']', 'wc' ); }

        // Validate API data
        if ( empty( $id ) || $id <= 0 ) {
            return $this->api_error( 'Insert: Invalid user ID' );
        }

        // Process data
        if ( $this->debug ) { eskimo_log( 'Process Customer ID[' . $id . ']', 'wc' ); }

		// User?
		$user_data = get_user_by( 'ID', $id );
		if ( false === $user_data ) { return $this->api_error( 'Invalid WP user ID[' . $id . ']' ); }

		// OK, got user id
		$user_id = absint( $user_data->ID );
		if ( $user_data === 0 ) { return $this->api_error( 'Invalid WP user ID[' . $user_id . ']' ); }

		// First test... EPOS ID
		$epos_id = get_user_meta( $user_id, 'epos_id', true );
		if ( ! empty( $epos_id ) ) { return $this->api_error( 'EPOS user exists ID[' . $user_id . '] EPOS ID[' . $epos_id . ']' ); }

		// Pre-existing
		$active 	= get_user_meta( $user_id, 'epos_active', true );
		$titleID	= get_user_meta( $user_id, 'epos_title', true );
		$country 	= get_user_meta( $user_id, 'epos_country', true );

		// Set up data
		$data = [ 
			'ActiveAccount' => ( $active === '' ) ? true : (boolean) $active,
			'EmailAddress'  => $user_data->user_email,
			'TitleID'		=> ( $titleID === '' ) ? 1 : (int) $title,
			'CountryCode'	=> ( empty( $country ) ) ? 'GB' : $country
		];

		// Set up address
		$addr = [];
		$addr[]	= get_user_meta( $user_id, 'billing_address_1', true );
		$addr[]	= get_user_meta( $user_id, 'billing_address_2', true );
		$addr[]	= get_user_meta( $user_id, 'billing_city', true );
		$address = join( "\r\n", array_filter( $addr ) );  

		// Get meta data... assume billing rules
		$data['Forename']		= get_user_meta( $user_id, 'billing_first_name', true );
		$data['Surname']		= get_user_meta( $user_id, 'billing_last_name', true );
		$data['CompanyName']	= get_user_meta( $user_id, 'billing_company', true );
		$data['Notes']			= get_user_meta( $user_id, 'epos_notes', true );
		$data['Address']		= $address;
		$data['Postcode']		= get_user_meta( $user_id, 'billing_postcode', true );
		$data['Telephone']		= get_user_meta( $user_id, 'billing_phone', true );
		$data['Mobile']			= get_user_meta( $user_id, 'billing_mobile', true );

        // OK, done
        return $data;
	}

    /**
     * Get customer data for update by ID
     *
     * @param   integer   		$id
     * @return  object|array
     */
    public function get_customers_update_ID( $id ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ' ID[' . $id . ']', 'wc' ); }

        // Validate API data
        if ( empty( $id ) ) {
            return $this->api_error( 'Insert: Invalid user ID' );
        }

        // Process data
        if ( $this->debug ) { eskimo_log( 'Process Customer ID[' . $id . ']', 'wc' ); }

		$user_data = get_user_by( 'ID', $id );
		if ( false === $user_data ) { return $this->api_error( 'Invalid WP user ID[' . $id . ']' ); }

		// OK, got user id
		$user_id = absint( $user_data->ID );
		if ( $user_data === 0 ) { return $this->api_error( 'Invalid WP user ID[' . $user_id . ']' ); }

		// First test... EPOS ID
		$epos_id = get_user_meta( $user_id, 'epos_id', true );
		if ( empty( $epos_id ) ) { return $this->api_error( 'EPOS user not exists ID[' . $user_id . ']' ); }

		// Pre-existing
		$active 	= get_user_meta( $user_id, 'epos_active', true );
		$titleID	= get_user_meta( $user_id, 'epos_title', true );
		$country 	= get_user_meta( $user_id, 'epos_country', true );

		// Set up data
		$data = [ 
			'ActiveAccount' => ( $active === '' ) ? true : (boolean) $active,
			'ID'			=> $epos_id,
			'EmailAddress'  => $user_data->user_email,
			'TitleID'		=> ( $titleID === '' ) ? 1 : (int) $title,
			'CountryCode'	=> ( empty( $country ) ) ? 'GB' : $country
		];

		// Set up address
		$addr = [];
		$addr[]	= get_user_meta( $user_id, 'billing_address_1', true );
		$addr[]	= get_user_meta( $user_id, 'billing_address_2', true );
		$addr[]	= get_user_meta( $user_id, 'billing_city', true );
		$address = join( "\r\n", array_filter( $addr ) );  

		// Get meta data... assume billing rules
		$data['Forename']		= get_user_meta( $user_id, 'billing_first_name', true );
		$data['Surname']		= get_user_meta( $user_id, 'billing_last_name', true );
		$data['CompanyName']	= get_user_meta( $user_id, 'billing_company', true );
		$data['Notes']			= get_user_meta( $user_id, 'epos_notes', true );
		$data['Address']		= $address; 
		$data['Postcode']		= get_user_meta( $user_id, 'billing_postcode', true );
		$data['Telephone']		= get_user_meta( $user_id, 'billing_phone', true );
		$data['Mobile']			= get_user_meta( $user_id, 'billing_mobile', true );

        // OK, done
        return $data;
	}

    /**
     * Insert EskimoEPOS data user for update
     *
     * @param   array   		$id
	 * @param   array   		$data
	 * @param	boolean			$update
     * @return  object|boolean
     */
    public function get_customers_epos_ID( $id, $data, $update = false ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ' ID[' . $id . '] UPD[' . (int) $update . ']', 'wc' ); }

        // Validate API data
        if ( empty( $data ) ) {
            return $this->api_error( 'Insert: Invalid EPOS data' );
        }

        // Process data
        if ( $this->debug ) { eskimo_log( 'Process Customer ID[' . $id . ']EPOS ID[' . $data->ID . ']', 'wc' ); }

		// Process update
		return ( $update === true ) ? ( update_user_meta( $id, 'epos_id', $data->ID ) ) ? 'ID[' . $id . '] EPOS ID[' . $data->ID . ']' : false : $data->ID;
	}

    //----------------------------------------------
    // Woocommerce SKUs
    //----------------------------------------------

    /**
     * Get EskimoEPOS API skus
     *
     * @param   array   		$api_data
     * @param   boolean			$import default true
     * @return  object|array
     */
    public function get_skus_all( $api_data, $import = true ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'wc' ); }

         // Validate API data
        if ( empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // Process data
        if ( $this->debug ) { eskimo_log( 'Process SKUs All[' . count( $api_data ) . '] Import[' . (int) $import . ']', 'wc' ); }

        // Process products
        $skus = [];

        // Get products list
        foreach ( $api_data as $api_sku ) {

            // Required valid product sku data
            if ( empty( $api_sku->eskimo_product_identifier ) ) { 
                if ( $this->debug ) { eskimo_log( 'SKU Product Not Set ID[' . $api_sku->eskimo_product_identifier . ']', 'wc' ); }
                continue; 
            }

			// SKU already imported?
			if ( true === $import ) {
				$sku_exists = $this->get_sku_by_id( $api_sku->sku_code );
				if ( true === $sku_exists ) { continue; }
			}
			
            // OK add products
            $skus[] = $api_sku;
        }

        if ( $this->debug ) { eskimo_log( 'EPOS SKUs: [' . count( $skus ) . ']', 'wc' ); }

        // Something to do?        
        if ( empty( $skus ) ) { return $this->api_error( 'No Product SKUs To Process' ); }

        // Return data
        $result = [];

        // Process parent categories first
        foreach ( $skus as $api_sku ) {

            // Load into response list
            $result[] = [
                'Eskimo_Product_Identifier' => $api_sku->eskimo_product_identifier,
				'SKU'						=> $api_sku->sku_code,
				'StockAmount'				=> $api_sku->StockAmount,
				'SellPrice'					=> $api_sku->SellPrice,
				'TaxCodeID'					=> $api_sku->TaxCodeID
            ];
        }

        // OK, done
        return $result;
	}
	
    /**
     * Get EskimoEPOS API skus
     *
     * @param   array   		$api_data
     * @return  object|array
     */
    public function get_skus_orphan( $api_data ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'wc' ); }

         // Validate API data
        if ( empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // Process data
        if ( $this->debug ) { eskimo_log( 'Process SKUs Orphan[' . count( $api_data ) . ']', 'wc' ); }

        // Process products
        $skus = [];

        // Get products list
        foreach ( $api_data as $api_sku ) {

            // Valid product sku data
            if ( ! empty( $api_sku->eskimo_product_identifier ) ) { continue; }
			
            // OK add products
            $skus[] = $api_sku;
        }

        if ( $this->debug ) { eskimo_log( 'EPOS SKUs: [' . count( $skus ) . ']', 'wc' ); }

        // Something to do?        
        if ( empty( $skus ) ) { return $this->api_error( 'No Orphaned Product SKUs To Process' ); }

        // Return data
        $result = [];

        // Process parent categories first
        foreach ( $skus as $api_sku ) {

            // Load into response list
            $result[] = [
				'SKU'						=> $api_sku->sku_code,
				'StockAmount'				=> $api_sku->StockAmount,
				'SellPrice'					=> $api_sku->SellPrice,
				'TaxCodeID'					=> $api_sku->TaxCodeID
            ];
        }

        // OK, done
        return $result;
	}

    /**
     * Get EskimoEPOS API product by ID
     *
     * @param   array   		$api_data
     * @return  object|array
     */
    public function get_skus_specific_code( $api_data ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'wc' ); }

         // Validate API data
        if ( empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // Process data
        if ( $this->debug ) { eskimo_log( 'Process SKUs All[' . count( $api_data ) . ']', 'wc' ); }

        // Web_ID
        $web_prefix = get_option( 'eskimo_api_product' ); 
        if ( $this->debug ) { eskimo_log( 'Web Prefix[' . $web_prefix . ']', 'wc' ); }

        // Process products
        $skus = [];

        // Get products list
        foreach ( $api_data as $api_sku ) {

            // Required valid product sku data
            if ( empty( $api_sku->eskimo_product_identifier ) ) { 
                if ( $this->debug ) { eskimo_log( 'SKU Product Not Set ID[' . $api_sku->eskimo_product_identifier . ']', 'wc' ); }
                continue; 
            }

			// SKU already imported?
			$sku_exists = $this->get_sku_by_id( $api_sku->sku_code );
			if ( true === $sku_exists ) { continue; }
			
            // OK add products
            $skus[] = $api_sku;
        }

        if ( $this->debug ) { eskimo_log( 'EPOS SKUs: [' . count( $skus ) . ']', 'wc' ); }

        // Something to do?        
        if ( empty( $skus ) ) { return $this->api_error( 'No Product SKUs To Process' ); }

        // Return data
        $result = [];

        // Process parent categories first
        foreach ( $skus as $api_sku ) {

            // Load into response list
            $result[] = [
                'Eskimo_Product_Identifier' => $api_sku->eskimo_product_identifier,
				'SKU'						=> $api_sku->sku_code,
				'StockAmount'				=> $api_sku->StockAmount,
				'SellPrice'					=> $api_sku->SellPrice,
				'TaxCodeID'					=> $api_sku->TaxCodeID
            ];
        }

        // OK, done
        return $result;
	}

    /**
     * Get EskimoEPOS API product by ID
     *
     * @param   array   		$api_data
     * @return  object|array
     */
    public function get_skus_specific_ID( $api_data ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'wc' ); }

         // Validate API data
        if ( empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // Process data
        if ( $this->debug ) { eskimo_log( 'Process SKUs ID[' . count( $api_data ) . ']', 'wc' ); }

        // Web_ID
        $web_prefix = get_option( 'eskimo_api_product' ); 
        if ( $this->debug ) { eskimo_log( 'Web Prefix[' . $web_prefix . ']', 'wc' ); }

        // Process products
        $skus = [];

        // Get products list
        foreach ( $api_data as $api_sku ) {

            // Required valid product sku data
            if ( empty( $api_sku->eskimo_product_identifier ) ) { 
                if ( $this->debug ) { eskimo_log( 'SKU Product Not Set ID[' . $api_sku->eskimo_product_identifier . ']', 'wc' ); }
                continue; 
            }

			// SKU already imported?
			$sku_exists = $this->get_sku_by_id( $api_sku->sku_code );
			if ( true === $sku_exists ) { continue; }
			
            // OK add products
            $skus[] = $api_sku;
        }

        if ( $this->debug ) { eskimo_log( 'EPOS SKUs: [' . count( $skus ) . ']', 'wc' ); }

        // Something to do?        
        if ( empty( $skus ) ) { return $this->api_error( 'No Product SKUs To Process' ); }

        // Return data
        $result = [];

        // Process parent categories first
        foreach ( $skus as $api_sku ) {

            // Load into response list
            $result[] = [
                'Eskimo_Product_Identifier' => $api_sku->eskimo_product_identifier,
				'SKU'						=> $api_sku->sku_code,
				'StockAmount'				=> $api_sku->StockAmount,
				'SellPrice'					=> $api_sku->SellPrice,
				'TaxCodeID'					=> $api_sku->TaxCodeID
            ];
        }

        // OK, done
        return $result;
    }

    //----------------------------------------------
    // Woocommerce Orders Import & Export
    //----------------------------------------------

    /**
	 * Get EskimoEPOS API web order data
     *
     * @param   array   		$api_data
     * @return  object|array
     */
    public function get_orders_website_order( $api_data ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'wc' ); }

        // Validate API data
        if ( empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // Process data
        if ( $this->debug ) { eskimo_log( 'Process Order[' . count( $api_data ) . ']', 'wc' ); }

		// Validate content? order, customer
		return $this->api_error( 'Order Import Not Yet Implemented' );
		
		// Set up order 	
		if ( $this->debug ) { eskimo_log( 'Order [' . ']', 'wc' ); }

		// Set up customer
		$username = $api_data->Forename . '.' . $api_data->Surname;
			
		// Generate WC user if possible - autogenerate password
		$order_id = wc_create_order( $email, $username );
		if ( is_wp_error( $user_id ) ) {
			return $this->api_error( $order_id->get_error_message() );
		}

     	if ( $this->debug ) { eskimo_log( 'OK Order ID[' . $order_id . ']', 'wc' ); }

		// Order meta
		add_post_meta( $user_id, 'epos_id', $api_data->ID );

		// OK, done
        return 'Order ID[' . $order_id . ']';
	}

    /**
     * Get woocommerce order data for EPOS insert
     *
 	 * @param   array   		$order_id
     * @return  object|array
     */
    public function get_orders_insert_ID( $order_id ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ' ID[' . $order_id . ']', 'wc' ); }

		// Validate API data
		$order_id = absint( $order_id );
		if ( $order_id === 0 ) {
            return $this->api_error( 'Insert: Invalid Order ID' );
        }

        // Process data
        if ( $this->debug ) { eskimo_log( 'Process Order ID[' . $order_id . ']', 'wc' ); }

		// Woocommerce order required
		$order = wc_get_order( $order_id );
		if ( false === $order || ! is_a( $order, 'WC_Order' ) ) { return $this->api_error( 'Invalid Order ID[' . $order_id . ']' ); }

		// OK, got order id
		$order_id = absint( $order->get_id() );
		if ( $order_id === 0 ) { return $this->api_error( 'Invalid WC Order ID[' . $order_id . ']' ); }

		// Get Order Status
		$order_status = $order->get_status();
		if ( ! in_array( $order_status, [ 'processing', 'completed' ] ) ) { return $this->api_error( 'Order status invalid[' . $order_status . ']' ); }

		// Check if already sent: EPOS ID
		$web_order_id = get_post_meta( $order_id, '_web_order_id', true );
		if ( ! empty( $web_order_id ) ) { return $this->api_error( 'EPOS Order exists ID[' . $order_id . '] EPOS Web Order ID[' . $web_order_id . ']' ); }

		// Get the customer
		$cust_id = $order->get_customer_id();
		if ( $this->debug ) { eskimo_log( 'Order User: ' . $cust_id, 'wc' ); }
		
		// Guest Checkout
		if ( $cust_id === 0 ) {
			$guest_user = get_user_by( 'email', apply_filters( 'eskimo_guest_user_email', 'guest@classworx.co.uk' ) );
			if ( ! $guest_user ) { return $this->api_error( 'EskimoEPOS Invalid Customer' ); }
			$cust_id = $guest_user->ID;
		}
		if ( $this->debug ) { eskimo_log( 'Order User UPD: ' . $cust_id, 'wc' ); }

		// Customer meta
		$epos_id = get_user_meta( $cust_id, 'epos_id', true );
		if ( empty( $epos_id ) ) { return $this->api_error( 'EskimoEPOS Customer ID Does Not Exist' ); }

		// Order reference
		$epos_ei = get_option( 'eskimo_api_customer' );
		$epos_ei = $epos_ei . $epos_id . '-' . $cust_id . '-' . $order_id;  
		if ( $this->debug ) { eskimo_log( 'Customer ID: [' . $cust_id . '] EPOS Customer ID[' . $epos_id . '] EPOS API ID[' . $epos_ei . ']', 'wc' ); }

		// Get order items
		$order_items = $order->get_items();
		if ( $this->debug ) { eskimo_log( 'Order Items: ' . count( $order_items ), 'wc' ); }

		// Get any order discounts
		$order_discounts = $order->get_coupon_codes(); 

		// Initiate coupons, normally one max, but can layer multiple
		$order_coupons = [];

		// Construct coupon list
		foreach ( $order_discounts as $coupon_code ) {

			// Retrieving the coupon ID
    		$coupon_post_obj = get_page_by_title( $coupon_code, OBJECT, 'shop_coupon' );
			$coupon_id       = $coupon_post_obj->ID;

			// Get an instance of WC_Coupon
			$coupon = new WC_Coupon( $coupon_id );
			if ( $this->debug ) { eskimo_log( 'Coupon: Type[' . $coupon->get_discount_type() . '] Amount[' . $coupon->get_amount() . ']', 'wc' ); }
		
			// Add coupon to list
			$order_coupons[] = $coupon;
		}

		// Order Notes
		$order_notes = $order->get_customer_order_notes(); 
		if ( is_array( $order_notes ) && ! empty( $order_notes ) ) {
			$order_note = '';
			foreach ( $order_notes as $n ) {
				eskimo_log( 'Note: [' . gettype( $n ) . '][' . print_r( $n, true ) . ']', 'wc' );
				$order_note .= $n->comment_content; 
			}
		} else {
			$order_note = get_post( $order_id )->post_excerpt;
		}

		// Set up invoice and order amount: vat respective
		$order_invoice_amount = ( true ===  $order->get_prices_include_tax() ) ? $order->get_total() : $order->get_total() - $order->get_total_tax();
		if ( $this->debug ) { eskimo_log( 'Order: Invoice[' . number_format( $order_invoice_amount, wc_get_price_decimals(), '.', '' ) . ']', 'wc' ); }

		// Initiate order data
		$data = [
			'order_id' 				=> $order_id,
			'ExternalIdentifier'	=> $epos_ei,
			'OrderType'				=> 2, // WebOrder
			'eskimo_customer_id' 	=> $epos_id,
			'order_date' 			=> $order->get_date_created()->date('c')
		];
		if ( $this->debug ) { eskimo_log( 'Order: Data[' . print_r( $data, true ) . ']', 'wc' ); }

		// Set up order items & totals
		$items = [];
		$cart_total = $discount_total = 0;

		// Iterating through each WC_Order_Item_Product objects
		foreach ( $order_items as $k => $order_item ) {

			// Set up item
			$item = [];

			// Get item product
			$product_id = $order_item->get_product_id(); 
			$product 	= $order_item->get_product(); 

			// Requires a valid SKU
			$product_sku = $product->get_sku();
			if ( empty( $product_sku ) ) {
				if ( $this->debug ) { eskimo_log( 'Product: ID[' . $product_id. '] Invalid SKU', 'wc' ); }
				continue;
			}

			// Basic item details
			$item['sku_code'] 				= $product_sku;
			$item['qty_purchased']			= $order_item->get_quantity();
			$item['item_note']				= null;			
			$item['item_description']		= null;			

			// Any discounts?
			if ( ! empty( $order_coupons ) ) {

				// Apply sequentially?
				$discount_sequential = get_option( 'woocommerce_calc_discounts_sequentially', 'no' );

				// Get base price & discount
				$item['unit_price']				= $product->get_price();
				$item['line_discount_amount']	= 0;

				// Iterate through coupons
				foreach ( $order_coupons as $k => $coupon ) {

					// How to apply multiple discounts					
					$price_to_discount = ( 'yes' === $discount_sequential ) ? $item['unit_price'] : $product->get_price();

					// Price based discount with rounding
					if ( $coupon->get_discount_type() === 'percent' ) {
						$price_to_discount = absint( round( wc_add_number_precision( $price_to_discount ) ) );
						$discount = wc_remove_number_precision( wc_round_discount( $price_to_discount * ( $coupon->get_amount() / 100 ), 0 ) );
					} else {
						$discount = wc_round_discount( $price_to_discount - $coupon->get_amount() );
					}

					// Set up new price & running discount
					$item['unit_price']				-= $discount;
					$item['line_discount_amount']	+= $discount;
				}
			} else {				
				$item['unit_price']				= $product->get_price();
				$item['line_discount_amount']	= 0;
			}

			// Running order totals, price & qty
			$cart_total 	+= ( $item['unit_price'] * $order_item->get_quantity() );
			$discount_total += ( $item['line_discount_amount'] * $order_item->get_quantity() );

			// Format item line totals
			$item['unit_price']				= number_format( $item['unit_price'], wc_get_price_decimals(), '.', '' );
			$item['line_discount_amount']	= number_format( $item['line_discount_amount'], wc_get_price_decimals(), '.', '' );
			 
			// Construct items
			$items[] = $item;
		}

		// Set up invoice data: cart total + shipping total
		$data['invoice_amount']	= number_format( $cart_total + $order->get_shipping_total(), wc_get_price_decimals(), '.', '' );
		$data['amount_paid']	= number_format( $cart_total + $order->get_shipping_total(), wc_get_price_decimals(), '.', '' );
		if ( $this->debug ) { eskimo_log( 'Invoice: [' . $order_invoice_amount . '][' . $data['invoice_amount'] . ']', 'wc' ); }

		// Set up billing address
		$billing = [
			'FAO'			=>	$order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . ' ' . $order->get_billing_company(),
			'AddressLine1' 	=>	$order->get_billing_address_1(),
			'AddressLine2' 	=>	$order->get_billing_address_2(),
			'AddressLine3' 	=>	null,
			'PostalTown'	=>	$order->get_billing_city(),
			'County'		=>	$order->get_billing_state(),
			'CountryCode'	=>	$order->get_billing_country(),
			'PostCode'		=>	$order->get_billing_postcode()
		];

		// Set up shipping address
		$shipping = [
			'FAO'			=>	$order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name() . ' ' . $order->get_shipping_company(),
			'AddressLine1' 	=>	$order->get_shipping_address_1(),
			'AddressLine2' 	=>	$order->get_shipping_address_2(),
			'AddressLine3' 	=>	null,
			'PostalTown'	=>	$order->get_shipping_city(),
			'County'		=>	$order->get_shipping_state(),
			'CountryCode'	=>	$order->get_shipping_country(),
			'PostCode'		=>	$order->get_shipping_postcode()
		];

		// Address checks
		$billing_name 	= $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
		$billing_addr 	= $order->get_billing_address_1();
		$shipping_name 	= $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name();
		$shipping_addr 	= $order->get_shipping_address_1();

		// Check if shipping & billing the same, only need invoice addr if true
		if ( $billing_name == $shipping_name && $billing_addr == $shipping_addr ) {
			$data['InvoiceAddress']		= $billing;
		} else {
			$data['DeliveryAddress']	= $shipping;
			$data['InvoiceAddress']		= $billing;
		}

		// Get Order Shipping
		$shipping_method 	= $order->get_shipping_method();
		if ( $this->debug ) { eskimo_log( 'Shipping: [' . $shipping_method . ']', 'wc' ); }

		$data['OrderedItems']			= $items;
		$data['CustomerReference'] 		= null;
		$data['DeliveryNotes'] 			= $order_note;
		$data['ShippingRateID'] 		= ( $shipping_method === 'Flat Rate' ) ? 2 : 1; // 1: Click&Collect 2: FlatRate 
		$data['ShippingAmountGross'] 	= $order->get_shipping_total();

        // OK, done
        return $data;
	}

    /**
     * Get woocommerce order data for EPOS return
     *
 	 * @param   array   		$order_id
 	 * @param   array   		$refund_id
     * @return  object|array
     */
    public function get_orders_return_ID( $order_id, $return_id ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ' ID #[' . $order_id . '][' . $return_id . ']', 'wc' ); }

		// Validate API data
		$order_id 	= absint( $order_id );
		$return_id 	= absint( $return_id );
		if ( $order_id === 0 || $return_id === 0 ) {
            return $this->api_error( 'Insert: Invalid Order or return ID #[' . $order_id . '][' . $return_id . ']' );
        }

        // Process data
        if ( $this->debug ) { eskimo_log( 'Process Return ID #[' . $return_id . '] for Order #[' . $order_id . ']', 'wc' ); }

		// Woocommerce order required
		$order = wc_get_order( $order_id );
		if ( false === $order || ! is_a( $order, 'WC_Order' ) ) { return $this->api_error( 'Invalid Order ID #[' . $order_id . ']' ); }

		// OK, got order id
		$order_id = absint( $order->get_id() );
		if ( $order_id === 0 ) { return $this->api_error( 'Invalid WC Order ID #[' . $order_id . ']' ); }

		// Verify Order Status: Refunded
		$order_status = $order->get_status();
		if ( 'refunded' !== $order_status ) { return $this->api_error( 'Order status invalid [' . $order_status . ']' ); }

		// Get the order returns / refunds [ WC_Order_Refund ]
		$order_returns = $order->get_refunds();
		if ( $this->debug ) { eskimo_log( 'Order Returns: ' . count( $order_returns ), 'wc' ); }

		// No returns? Should have if return_id is valid
		if ( count( $order_returns ) === 0 ) { return $this->api_error( 'No Returns For Order ID #[' . $order_id . ']' ); }

		// Woocommerce valid return required
		$return = wc_get_order( $return_id );
		if ( false === $return || ! is_a( $return, 'WC_Order_Refund' ) ) { return $this->api_error( 'Invalid Return ID #[' . $return_id . ']' ); }

		// OK, got return id
		$return_id = absint( $return->get_id() );
		if ( $return_id === 0 ) { return $this->api_error( 'Invalid WC Return ID #[' . $return_id . ']' ); }

		// Get Refund Status: Completed
		$return_status = $return->get_status();
		if ( 'completed' !== $return_status ) { return $this->api_error( 'Return status invalid [' . $order_status . ']' ); }

		// Get the customer & guest checkout if needed
		$cust_id = $order->get_customer_id();
		if ( $cust_id === 0 ) {
			$guest_user = get_user_by( 'email', apply_filters( 'eskimo_guest_user_email', 'guest@classworx.co.uk' ) );
			if ( ! $guest_user ) { return $this->api_error( 'EskimoEPOS Invalid Customer' ); }
			$cust_id = $guest_user->ID;
		}
		if ( $this->debug ) { eskimo_log( 'Order Customer: ' . $cust_id, 'wc' ); }

		// Customer meta
		$epos_id = get_user_meta( $cust_id, 'epos_id', true );
		if ( empty( $epos_id ) ) { return $this->api_error( 'EskimoEPOS Customer ID Does Not Exist' ); }

		// Order reference
		$epos_ei = get_option( 'eskimo_api_customer' );
		$epos_order_ei 	= $epos_ei . $epos_id . '-' . $cust_id . '-' . $order_id;  
		$epos_return_ei = $epos_ei . $epos_id . '-' . $cust_id . '-' . $return_id;  
		if ( $this->debug ) { eskimo_log( 'Customer ID: [' . $cust_id . '] EPOS Customer ID[' . $epos_id . '] EPOS API ID[' . $epos_ei . ']', 'wc' ); }

		// Set return date
		$dt = new DateTime();

		// Initiate order data
		$data = [
			'GoodwillGestureAmount' 	=> 0,
			'CarriageAmountGross' 		=> $order->get_shipping_total(),
			'OrderExternalIdentifier'	=> $epos_order_ei,
			'ReturnExternalIdentifier'	=> $epos_return_ei,
			'ReturnDate' 				=> $dt->format('c'),
			'Order_ID' 					=> $order_id,
			'OrderType'					=> 2, // WebOrder
		];

		// Set up order items & totals
		$items = [];

		if ( $this->debug ) { eskimo_log( 'Items [' . count( $order->get_items() ) . ']' ); }

		// Loop through the order refund line items
   		foreach( $order->get_items() as $item_id => $order_item ) {

			// Set up item
			$item = [];

			// Check if it has returns, convert to positive
			$item_qty_refunded = abs( $order->get_qty_refunded_for_item( $item_id ) );
			if ( $this->debug ) { eskimo_log( 'Refunded ID[' . $item_id . '] Qty [' . $item_qty_refunded . ']' ); }
			if ( $item_qty_refunded === 0 ) { continue; }
		
			// First test, product must still be valid
			$product_id = $order_item->get_product_id();
			if ( $product_id === 0 ) {
				if ( $this->debug ) { eskimo_log( 'Product for item ID [' . $item_id . '] invalid', 'wc' ); }
				continue;
			}

			// Get product: WC_Product
			$product = $order_item->get_product();
			if ( $this->debug ) { eskimo_log( 'Product #[' . $product->get_id() . '][' . $product_id . '] Qty[' . $item_qty_refunded . '] SKU[' . $product->get_sku() . ']', 'wc' ); }

			// Requires a valid SKU
			$product_sku = $product->get_sku();
			if ( empty( $product_sku ) ) {
				if ( $this->debug ) { eskimo_log( 'Product: ID[' . $product_id. '] Invalid SKU [' . $product_sku . ']', 'wc' ); }
				continue;
			}

			// Basic item details
			$item['SKU'] = $product_sku;
			$item['Qty'] = $item_qty_refunded;

			// Construct items
			$items[] = $item;
	    }

		// No returns?
		if ( empty( $items ) ) { return $this->api_error( 'No Returns Items To Process' ); }
			
		// Set refund products: sku & qty
		$data['RefundProducts']	= $items;

        // OK, done
        return $data;
	}

    /**
     * Insert EskimoEPOS extermal reference update
     *
     * @param   array   		$id
	 * @param   array   		$data
	 * @param	boolean			$update default false
	 * @param	boolean			$return	default true
     * @return  boolean
     */
    public function get_orders_epos_ID( $id, $data, $update = false, $return = false ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ' ID[' . $id . '] UPD[' . (int) $update . '] Order[' . $data['ExternalIdentifier'] . ']', 'wc' ); }

        // Validate API data
        if ( empty( $data ) ) {
            return $this->api_error( 'Insert: Invalid EPOS data' );
        }

        // Process data
        if ( $this->debug ) { eskimo_log( 'Process Order ID[' . $id . ']EPOS ID[' . $data['ExternalIdentifier'] . ']', 'wc' ); }

		// Set field
		$web_field = ( true === $return ) ? '_web_return_id' : '_web_order_id';

		// Process update
		return ( $update === true ) ? ( update_post_meta( $id, $web_field, $data['ExternalIdentifier'] ) ) ? 'ID[' . $id . '] EPOS WebOrder ID[' . $data['ExternalIdentifier'] . ']' : false : $data['ExternalIdentifier'];
	}

    //----------------------------------------------
    // Woocommerce Product Images
    //----------------------------------------------

    /**
     * Get EskimoEPOS API product image links
     *
     * @param   array   		$api_data
     * @return  object|boolean
     */
    public function get_image_links_all( $api_data ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'wc' ); }

        // Validate API data
        if ( empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // Process data
        if ( $this->debug ) { eskimo_log( 'Process Image Links[' . count( $api_data ) . ']', 'wc' ); }

        // OK, done
        return true;
    }

    /**
     * Get EskimoEPOS API product images
     *
     * @param   array   		$api_data
     * @return  object|boolean
     */
    public function get_images_all( $api_data ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'wc' ); }

        // Validate API data
        if ( empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // Process data
        if ( $this->debug ) { eskimo_log( 'Process Images All[' . count( $api_data ) . ']', 'wc' ); }

        // OK, done
        return true;
    }

    //----------------------------------------------
    // Woocommerce Miscellaneous ImpEx
    //----------------------------------------------

    /**
     * Get EskimoEPOS API Tax Codes optionally by ID
     *
     * @param   array   		$api_data
     * @return  object|boolean
     */
    public function get_tax_codes( $api_data ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'wc' ); }

        // Validate API data
        if ( empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // Process data
        if ( $this->debug ) { eskimo_log( 'Process Tax Codes[' . count( $api_data ) . ']', 'wc' ); }

        // OK, done
        return true;
    }

    /**
     * Get EskimoEPOS API shops
     *
     * @param   array   		$api_data
     * @return  object|boolean
     */
    public function get_shops_all( $api_data ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'wc' ); }

        // Validate API data
        if ( empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // Process data
        if ( $this->debug ) { eskimo_log( 'Process Shops All[' . count( $api_data ) . ']', 'wc' ); }

        // OK, done
        return true;
    }

    /**
     * Get EskimoEPOS API Shops by ID
     *
     * @param   array   		$api_data
     * @return  object|boolean
     */
    public function get_shops_specific_ID( $api_data ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'wc' ); }

        // Validate API data
        if ( empty( $api_data ) ) {
            return $this->api_rest_error();
        }

        // Process data
        if ( $this->debug ) { eskimo_log( 'Process Shops ID[' . count( $api_data ) . ']', 'wc' ); }

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
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ' Parent[' . (int) $parent . ']', 'wc' ); }
        
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
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ' Parent[' . (int) $parent . ']', 'wc' ); }

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

        if ( $this->debug && is_wp_error( $res ) ) { eskimo_log( 'Error:' .  $res->get_error_message() . ']', 'wc' ); }

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
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': CatID[' . $cat_id . ']', 'wc' ); }
        return update_term_meta( $cat_id, 'eskimo_category_id', sanitize_text_field( $api_cat->Eskimo_Category_ID ) );
    }

    /**
     * Check if the EPOS category has a parent term and get the WC value
     * - Should be no surprises here, parents should all be pre-processed
     * - Children with orphan parent EPOS cat treated as bad data & logged
     *
     * @param   object  $data   Category data
     */
    protected function get_parent_category_id( $data ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'wc' ); }
        
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

        if ( $this->debug && is_wp_error( $terms ) ) { eskimo_log( 'Error:' .  $res->get_error_message() . ']', 'wc' ); }

        // No terms or Error
        if ( empty( $terms ) || is_wp_error( $terms ) ) {
            if ( $this->debug ) { eskimo_log( 'Bad Parent Term[' . $data->Eskimo_Category_ID . ']', 'wc' ); }
            return 0;
        }

        if ( $this->debug ) { eskimo_log( 'Parent Terms[' . print_r( $terms, true ) . ']', 'wc' ); }

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
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'wc' ); }

        // Set product type: simple or variable by sku count
        $type = $this->get_product_type( $data );
        if ( $this->debug ) { eskimo_log( 'Type[' . $type . ']', 'wc' ); }

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
     * @param   object  		$data
     * @return  boolean|object
     */
    protected function add_category_product_simple( $data ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'wc' ); }

        // Single SKU OK?
        $sku = array_shift( $data->sku );
        if ( $this->debug ) { eskimo_log( 'SKU:' . print_r( $sku, true ), 'wc' ); }
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

        if ( $this->debug ) { eskimo_log( print_r( $args, true ), 'wc' ); }

        // Set up REST process
        $products_controller = new WC_REST_Products_Controller();
        $wp_rest_request = new WP_REST_Request( 'POST' );
        $wp_rest_request->set_body_params( $args );
        $res = $products_controller->create_item( $wp_rest_request );
        
        if ( $this->debug && is_wp_error( $res ) ) { eskimo_log( 'Error:' .  $res->get_error_message() . ']', 'wc' ); }

        return is_wp_error( $res ) ? false : $res->data;
    }

    /**
     * Add category product: Simple Product
     *
     * @param   object  		$data
     * @return  boolean|object
     */
    protected function add_category_product_variable( $data ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'wc' ); }
       
        // Initial check. Already posted SKU?
        $skus = array_map( function( $sku ) { return $sku->sku_code; }, $data->sku );
        if ( $this->debug ) { eskimo_log( 'SKUs[' . print_r( $skus, true ) . ']', 'wc' ); }
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

        if ( $this->debug ) { eskimo_log( print_r( $args, true ), 'wc' ); }

        // Set up REST process
        $wp_rest_request = new WP_REST_Request( 'POST' );
        $wp_rest_request->set_body_params( $args );
        
		// Set up REST product controller for insert
        $products_controller = new WC_REST_Products_Controller();
        $res = $products_controller->create_item( $wp_rest_request );
        
        // Add product variations
        foreach ( $data->sku as $sku ) {
            $sku->product_id = $res->data['id'];
            $res_var = $this->add_category_product_variation( $sku, $data );
        }

        if ( $this->debug && is_wp_error( $res ) ) { eskimo_log( 'Error:' .  $res->get_error_message() . ']', 'wc' ); }

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
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': path[' . $path . ']', 'wc' ); }

        // Set product type: simple or variable by sku count
        $type = $this->get_product_type( $data );
        if ( $this->debug ) { eskimo_log( 'Type[' . $type . ']', 'wc' ); }

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
	 * @param   object  		$data
	 * @param	string			$path
     * @return  boolean|object
     */
    protected function update_category_product_simple( $data, $path ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': path[' . $path . ']', 'wc' ); }

        // Single SKU OK?
        $sku = array_shift( $data->sku );
        if ( $this->debug ) { eskimo_log( 'SKU:' . print_r( $sku, true ), 'wc' ); }
        if ( false === $this->get_product_check_sku( $sku->sku_code ) ) { return false; }        

		// Get product
		$product_id = $this->get_product_by_sku( $sku->sku_code, false );
        if ( $this->debug ) { eskimo_log( 'product ID[' . $product_id . ']', 'wc' ); }
		if ( false === $product_id ) { return false; }
		
		// Update product attributes, sku, stock for Simple products
		$args = $this->update_category_product_simple_args( $path, $data, $sku, $product_id );
        if ( $this->debug ) { eskimo_log( print_r( $args, true ), 'wc' ); }

		// Set up REST process
		$wp_rest_request = new WP_REST_Request( 'POST' );
        $wp_rest_request->set_body_params( $args );

		// Set up REST product controller for update
        $products_controller = new WC_REST_Products_Controller();
        $res = $products_controller->update_item( $wp_rest_request );
        
        if ( $this->debug && is_wp_error( $res ) ) { eskimo_log( 'Error:' .  $res->get_error_message() . ']', 'wc' ); }

        return is_wp_error( $res ) ? false : $res->data;
    }

    /**
     * Add category product: Simple Product
     *
	 * @param   object  		$data
	 * @param	string			$path
     * @return  boolean|object
     */
    protected function update_category_product_variable( $data, $path ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': path[' . $path . ']', 'wc' ); }
       
        // Initial check. Already posted SKU?
        $skus = array_map( function( $sku ) { return $sku->sku_code; }, $data->sku );
        if ( $this->debug ) { eskimo_log( 'SKUs[' . print_r( $skus, true ) . ']', 'wc' ); }
        if ( false === $this->get_product_check_sku( $skus, true ) ) { return false; }

		// Get product
		$product_id = $this->get_product_by_id( $data->eskimo_identifier, false );
        if ( $this->debug ) { eskimo_log( 'product ID[' . $product_id . '][' . $data->eskimo_identifier . ']', 'wc' ); }
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
        if ( $this->debug ) { eskimo_log( print_r( $args, true ), 'wc' ); }

        // Set up REST process
        $wp_rest_request = new WP_REST_Request( 'POST' );
        $wp_rest_request->set_body_params( $args );

		// Set up REST product controller for update		
        $products_controller = new WC_REST_Products_Controller();
        $res = $products_controller->update_item( $wp_rest_request );

        // Add product variations
        foreach ( $data->sku as $sku ) {
            $sku->product_id = $product_id;
            $res_var = $this->update_category_product_variation( $sku, $data, $path );
        }

        if ( $this->debug && is_wp_error( $res ) ) { eskimo_log( 'Error:' .  $res->get_error_message() . ']', 'wc' ); }

        return is_wp_error( $res ) ? false : $res->data;
	}

    /**
     * Add a variation to a just created variable product
     *
	 * @param   object  		$sku
	 * @param   object  		$data
	 * @param	string			$path
     * @return  object|boolean
     */
    protected function update_category_product_variation( $sku, $data, $path ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $sku->product_id . ']', 'wc' ); }

		// Get product
		$product_id = $this->get_product_by_sku( $sku->sku_code, true );
        if ( $this->debug ) { eskimo_log( 'product ID[' . $product_id . ']', 'wc' ); }
		if ( false === $product_id ) { return false; }

		// Update product attributes, sku, stock for Variable products
		$args = $this->update_category_product_variable_args( $path, $data, $sku, $product_id );
        if ( $this->debug ) { eskimo_log( print_r( $args, true ), 'wc' ); }
		
		// Set up REST process
		$wp_rest_request = new WP_REST_Request( 'POST' );
        $wp_rest_request->set_body_params( $args );

		// Set up product controller for update
        $products_controller = new WC_REST_Product_Variations_Controller();
        $res = $products_controller->update_item( $wp_rest_request );
        
        if ( $this->debug && is_wp_error( $res ) ) { eskimo_log( 'Error:' .  $res->get_error_message() . ']', 'wc' ); }
        
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
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': path[' . $path . '] productID[' . $product_id . ']', 'wc' ); }

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
			case 'adjust':
		        $args['stock_quantity'] = $sku->StockAmount;
				$args['regular_price']  = $data->from_price;	
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
				'id' 		 => $product_id, 
				'product_id' => $sku->product_id 
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
			case 'adjust':
				$args['stock_quantity'] = $sku->StockAmount;
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
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'wc' ); }
        
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
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $id . ']', 'wc' ); }
        
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
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': variable[' . (int) $variable . ']', 'wc' ); }
        
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
		if ( $this->debug ) { eskimo_log( 'Found[' . $the_query->found_posts . ']', 'wc' ); }

        // Found post sku?
        return ( $the_query->found_posts > 0 ) ? true : false;
	}

    /**
     * Get product ID by SKU
     *
     * @param   string  $code
     * @return  boolean
     */
    protected function get_sku_by_id( $code ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ' SKU: [' . $code . ']', 'wc' ); }
        
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
		        'value'   => $code,
		        'compare' => '='
            ]
        ];

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
	 * @param   object  		$sku
	 * @param	array			$data
     * @return  object|boolean
     */
    protected function add_category_product_variation( $sku, $data ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': ID[' . $sku->product_id . ']', 'wc' ); }

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

        if ( $this->debug ) { eskimo_log( print_r( $args, true ), 'wc' ); }

		// Set up REST process
        $wp_rest_request = new WP_REST_Request( 'POST' );
        $wp_rest_request->set_body_params( $args );

		// Set up REST product controller for update
        $products_controller = new WC_REST_Product_Variations_Controller();
        $res = $products_controller->create_item( $wp_rest_request );
        
        if ( $this->debug && is_wp_error( $res ) ) { eskimo_log( 'Error:' .  $res->get_error_message() . ']', 'wc' ); }
        
        return is_wp_error( $res ) ? false : $res->data;
    }

    /**
     * Update new product post with custom Eskimo Product ID
     *
     * @param   integer $cat_id
	 * @param   object  $api_cat
	 * @return	boolean
     */
    protected function add_post_meta_eskimo_id( $prod_id, $api_prod ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': ProdID[' . $prod_id . ']', 'wc' ); }
        $cat_id     = add_post_meta( $prod_id, '_eskimo_category_id', sanitize_text_field( $api_prod->eskimo_category_id ) );
        $post_id    = add_post_meta( $prod_id, '_eskimo_product_id', sanitize_text_field( $api_prod->eskimo_identifier ) );
        return ( $cat_id && $post_id ) ? true : false;
	}

	/**
	 * Add extra product post meta
	 * 
     * @param   integer $cat_id
	 * @param   object  $api_cat
	 * @return 	boolean
	 */
	protected function add_post_meta_extra( $prod_id, $api_prod ) {
    	if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': ProdID[' . $prod_id . ']', 'wc' ); }
		$meta_keywords 		= add_post_meta( $prod_id, '_meta_keywords', sanitize_text_field( $api_prod->meta_keywords ) );
		$meta_description 	= add_post_meta( $prod_id, '_meta_description', sanitize_text_field( $api_prod->meta_description ) );
        return ( $meta_keywords && $meta_description ) ? true : false;
	}

	/**
	 * Add more product post meta
	 * 
     * @param   integer $cat_id
	 * @param   object  $api_cat
	 * @return 	boolean
	 */
	protected function add_post_meta_more( $prod_id, $api_prod ) {
    	if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': ProdID[' . $prod_id . ']', 'wc' ); }
		$style_ref 	= add_post_meta( $prod_id, '_eskimo_style_reference', sanitize_text_field( $api_prod->style_reference ) );
		$addfield04	= add_post_meta( $prod_id, '_addfield04', sanitize_text_field( $api_prod->addfield04 ) );
        return ( $style_ref ) ? true : false;
	}

	/**
	 * Add extra product post meta
	 * 
     * @param   integer $cat_id
	 * @param   object  $api_cat
	 * @return 	boolean
	 */
	protected function add_post_meta_date( $prod_id, $api_prod ) {
    	if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': ProdID[' . $prod_id . ']', 'wc' ); }
		$date_created 	= add_post_meta( $prod_id, '_date_created', $api_prod->date_created );
		$last_updated	= add_post_meta( $prod_id, '_last_updated', $api_prod->last_updated );
        return ( $date_created && $last_updated ) ? true : false;
	}

    /**
     * Get tax class mapping
     *
     * @param   object  $data
     * @return  string
     */
    protected function get_product_tax_class( $sku ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'wc' ); }

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
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': Name[' . $name . ']', 'wc' ); }

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
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ':  Variation[' . (int) $variation . ']', 'wc' ); }

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
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'wc' ); }

        // SKU's should be in object
        if ( !isset( $data->sku ) ) {
            if ( $this->debug ) { eskimo_log( 'Bad Product SKU[' . $data->eskimo_identifier . ']', 'wc' ); }
            return false;
        }
        
        // Set SKU count
        $sku_count = count( $data->sku );
        if ( $this->debug) { eskimo_log( 'SKU Product[' . $data->eskimo_identifier . '] Count[' . count( $data->sku ) . ']', 'wc' ); }

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
	 * @return	array
     */
    protected function get_category_by_id( $data ) {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__, 'wc' ); }
        
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

        if ( $this->debug && is_wp_error( $terms ) ) { eskimo_log( 'Error:' .  $terms->get_error_message() . ']', 'wc' ); }

        // No terms or Error
        if ( empty( $terms ) || is_wp_error( $terms ) ) {
            if ( $this->debug ) { eskimo_log( 'Bad Product Category Term[' . $data->eskimo_category_id . ']', 'wc' ); }
            return false;
        }

        if ( $this->debug ) { eskimo_log( 'Product Category Terms[' . print_r( $terms, true ) . ']', 'wc' ); }

        // Structure cats
        $cats = [];
        foreach ( $terms as $k=>$t ) {
            $cats[] = [ 'id' => $t->term_id ];
        }

        if ( $this->debug ) { eskimo_log( 'Cats[' . print_r( $cats, true ) . ']', 'wc' ); }

        return $cats;
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
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': Error[' . $error . ']', 'wc' ); }
		return new WP_Error( 'data', $error );
	}

    /**
	 * Log API REST Process Error
	 * 
	 * @return	object
     */
    protected function api_rest_error() {
        if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ': ' . __( 'API Error: Could Not Process REST data from API', 'eskimo' ), 'wc' ); }
		return new WP_Error( 'rest', __( 'API Error: Could Not Process REST data from API', 'eskimo' ) );
	}

	/**
	 * External identifier for WebOrder
	 *
	 * @param 	integer $length default 10
	 * @param 	integer $start default 0
	 * @return 	string
	 */
	protected function keygen( $length = 10, $start = 0 ) {
		return substr( str_shuffle( sha1( microtime( true ). mt_rand( 10000,90000 ) ) ), $start, $length );
	}
}
