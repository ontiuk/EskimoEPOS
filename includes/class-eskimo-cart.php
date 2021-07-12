<?php 

/**
 * The plugin woocommerce eskimoEPOS class 
 *
 * This is used to define EskimoEPOS woocommerce functionality for the plugin
 * 
 * @package    Eskimo
 * @subpackage Eskimo/includes
 * @link       https://on.tinternet.co.uk
 */

/**
 * The plugin woocommerce eskimoEPOS class 
 *
 * @package    Eskimo
 * @subpackage Eskimo/includes
 * @author     Stephen Betley <on@tinternet.co.uk>
 */
final class Eskimo_Cart { 

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
		$this->debug        = ESKIMO_CART_DEBUG;
		$this->base_dir		= plugin_dir_url( __FILE__ ); 

		// Set guest user?
		if ( defined( 'ESKIMO_MODE' ) && 'test' === ESKIMO_MODE ) {
			add_filter( 'ipress_guest_user_email', function() {
				return 'guest@trutexmacclesfield.com';
			} );
		}
	}
    
    //----------------------------------------------
    //  Customer Processing Functions 
    //----------------------------------------------

	/**
	 * Customer created processing - update EPOS
	 *
	 * @param	string	$cust_id
	 */
	public function customer_created( $cust_id ) {
		if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ' Customer ID[' . $cust_id . ']', 'cart' ); }
	
		// Not for guest
		if ( $cust_id === 0 ) { return; }

		// Get user & role
		$user = get_user_by( 'ID', $cust_id );
		$user_email = $user->email; 
		$user_role 	= $user->roles[0];

		// Initiate REST call to update EPOS order status
		if ( $user_role === 'customer' ) {

			// Check it exists...
			$rest_url = esc_url( home_url( '/wp-json' ) ) . '/eskimo/v1/customer-exists/' . $user_email;
			if ( $this->debug ) { eskimo_log( 'Rest URL[' . $rest_url . ']', 'cart' ); }
			$response = wp_remote_get( $rest_url );

			// Check the call worked
			if ( is_wp_error( $response ) ) {
				return ( $this->debug ) ? eskimo_log( 'Exists Customer ID [' . $cust_id . '] Email[' . $user_email . '] API Error', 'cart' ) : '';
			}

			// Get the response body
			$body = wp_remote_retrieve_body( $response );

			// Check contents and parse
			if ( empty( $body ) ) {
				return ( $this->debug ) ? eskimo_log( 'Empty Customer ID [' . $cust_id . '] Email[' . $user_email . '] API Error', 'cart' ) : '';
			} else {

				// Get the body data
				$data = json_decode( $body );
				if ( $this->debug ) { eskimo_log( 'Customer EPOS [' . $data->params . '] Result[' . $data->result . ']', 'cart' ); }

				// Check if exists?
				if ( true === (bool) $data->result ) {
					return ( $this->debug ) ? eskimo_log( 'Customer ID [' . $cust_id . '] Email[' . $user_email . '] Exists', 'cart' ) : '';
				}
			}

			// Create it if not...
			$rest_url = esc_url( home_url( '/wp-json' ) ) . '/eskimo/v1/customer-insert/' . $cust_id;
			if ( $this->debug ) { eskimo_log( 'Rest URL[' . $rest_url . ']', 'cart' ); }
			$response = wp_remote_get( $rest_url, [ 'timeout' => 12 ] );

			// Check the call worked
			if ( is_wp_error( $response ) ) {
				return ( $this->debug ) ? eskimo_log( 'Insert Customer ID [' . $cust_id . '] Email[' . $user_email . '] API Error', 'cart' ) : '';
			}

			// Get the response body
			$body = wp_remote_retrieve_body( $response );

			// Check contents and parse
			if ( empty( $body ) ) {
				return ( $this->debug ) ? eskimo_log( 'Empty Customer ID [' . $cust_id . '] Email[' . $user_email . '] Insert', 'cart' ) : '';
			}			

			// Get the body data
			$data = json_decode( $body );
			if ( $this->debug ) { eskimo_log( 'EPOS Customer:  Route[' . $data->route . '] Params[' . $data->params . '] Result[' . $data->result . ']', 'cart' ); }
		}
	}
	
	/**
	 * Customer update processing - update EPOS
	 * 
	 * @param	string	$cust_id
	 */
	public function customer_updated( $cust_id ) {
		if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ' Customer ID[' . $cust_id . ']', 'cart' ); }

		// Get user & role
		$user_role = get_user_by( 'ID', $cust_id )->roles[0];
		
		// Initiate REST call to update EPOS order status
		if ( $user_role === 'customer' ) {

			// Set up the customer call
			$rest_url = esc_url( home_url( '/wp-json' ) ) . '/eskimo/v1/customer-update/' . $cust_id;
			if ( $this->debug ) { eskimo_log( 'Rest URL[' . $rest_url . ']', 'cart' ); }
			$response = wp_remote_get( $rest_url, [ 'timeout' => 12 ] );

			// Check the call worked
			if ( is_wp_error( $response ) ) {
				return ( $this->debug ) ? eskimo_log( 'Updated Customer ID [' . $cust_id . '] API Error', 'cart' ) : '';
			}

			// Get the response body
			$body = wp_remote_retrieve_body( $response );

			// Check contents and parse
			if ( empty( $body ) ) {
				return ( $this->debug ) ? eskimo_log( 'Empty Customer ID [' . $cust_id . '] Updated', 'cart' ) : '';
			}			

			// Get the body data
			$data = json_decode( $body );
			if ( $this->debug ) { eskimo_log( 'EPOS Customer: Route[' . $data->route . '] Params[' . $data->params . '] Result[' . $data->result . ']', 'cart' ); }
		}
	}

    //----------------------------------------------
    //  Order Processing Functions
    //----------------------------------------------

	/**
	 * Trigger automatic export of user to EskimoEPOS on order creation
	 *
	 * @param integer $order_id
	 */
	public function new_order_created( $order_id ) {
		if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ' Order ID[' . $order_id . ']', 'cart' ); }

		// Get order & user details
		$order	= wc_get_order( $order_id );
		$user 	= $order->get_user();

		// We don't do anything for guest checkout
		if ( false === $user ) {
			return ( $this->debug ) ? eskimo_log( 'No user for this order[' . $order_id . ']', 'cart' ) : '';
		}

		// OK, get user ID
		$user_id = $order->get_user_id();
		if ( $this->debug ) { eskimo_log( 'New Order ID[' . $order_id . '] Status[' . $order->get_status() . '] User[' . $user_id . ']', 'cart' ); }

		// Initiate REST call to update EPOS order status
		$rest_url = esc_url( home_url( '/wp-json' ) ) . '/eskimo/v1/customer-insert/' . $user_id;
		if ( $this->debug ) { eskimo_log( 'Rest URL[' . $rest_url . ']', 'cart' ); }
		$response = wp_remote_get( $rest_url, [ 'timeout' => 12 ] );

		// Check the call worked
		if ( is_wp_error( $response ) ) {
			return ( $this->debug ) ? eskimo_log( 'Created Order ID [' . $order_id . '] UserID [' . $user_id . '] API Error', 'cart' ) : '';
		}

		// Get the response body
		$body = wp_remote_retrieve_body( $response );

		// Check contents and parse
		if ( empty( $body ) ) {
			return ( $this->debug ) ? eskimo_log( 'Empty Order ID [' . $order_id . '] UserID [' . $user_id . '] Created', 'cart' ) : '';
		}			

		// Get the body data
		$data = json_decode( $body );
		if ( $this->debug ) { eskimo_log( 'EPOS Order: Route[' . $data->route . '] Params[' . $data->params . '] Result[' . $data->result . ']', 'cart' ); }
	}

	/**
	 * Trigger automatic export of order data to EskimoEPOS on order processing ( post payment )
	 *
	 * @param integer $order_id
	 */
	public function order_status_processing( $order_id ) {
   		if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ' ID[' . $order_id . ']', 'cart' ); }

		// Get valid order details		
		$order = wc_get_order( $order_id );
		if ( false === $order || ! is_a( $order, 'WC_Order' ) ) { 
			return ( $this->debug ) ? eskimo_log( 'Invalid Order ID[' . $id . ']', 'cart' ) : '';
		}

		// Get customer ID
		$customer_id = $order->get_customer_id();

		// Guest checkout via dummy guest account, or valid customer ID
		if ( $customer_id === 0 ) {
			$customer = get_user_by( 'email', apply_filters( 'ipress_guest_user_email', 'guest@classworx.co.uk' ) );
			if ( false ===  $customer ) {
				return ( $this->debug ) ? eskimo_log( 'No guest user for this order[' . $order_id . ']', 'cart' ) : '';
			}
		} else {
			$customer = get_user_by( 'ID', $customer_id );
			if ( false ===  $customer ) {
				return ( $this->debug ) ? eskimo_log( 'Invalid customer for this order[' . $order_id . ']', 'cart' ) : '';
			}
		}

		if ( $this->debug ) { eskimo_log( 'order_status_processing ID[' . $order_id . '] user[' . $customer->user_email . '] items[' . count( $order->get_items() ) . '] status[' . $order->get_status() . ']', 'cart' ); }

		// Initiate REST call to update EPOS order status
		$rest_url = esc_url( home_url( '/wp-json' ) ) . '/eskimo/v1/order-insert/' . $order_id;
		if ( $this->debug ) { eskimo_log( 'Rest URL[' . $rest_url . ']', 'cart' ); }
		$response = wp_remote_get( $rest_url, [ 'timeout' => 12 ] );

		// Check the call worked
		if ( is_wp_error( $response ) ) {
			return ( $this->debug ) ? eskimo_log( 'Created Order ID [' . $order_id . '] Cust ID [' . $customer_id . '] API Error', 'cart' ) : '';
		}

		// Get the response body
		$body = wp_remote_retrieve_body( $response );

		// Check contents and parse
		if ( empty( $body ) ) {
			return ( $this->debug ) ? eskimo_log( 'Empty Order ID [' . $order_id . '] Cust ID [' . $customer_id . '] Created', 'cart' ) : '';
		}			

		// Get the body data
		$data = json_decode( $body );
		if ( $this->debug ) { eskimo_log( 'EPOS Order: Route[' . $data->route . '] Params[' . $data->params . '] Result[' . $data->result . ']', 'cart' ); }
	}	

	/**
	 * Trigger automatic export of order data to EskimoEPOS on order completion
	 *
	 * @param integer $order_id
	 */
	public function order_status_completed( $order_id ) {
		if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ' ID[' . $order_id . ']', 'cart' ); }
		
		// Get valid order details		
		$order = wc_get_order( $order_id );
		if ( false === $order || ! is_a( $order, 'WC_Order' ) ) { 
			return ( $this->debug ) ? eskimo_log( 'Invalid Order ID[' . $id . ']', 'cart' ) : '';
		}
		
		// Get customer ID
		$customer_id = $order->get_customer_id();

		// Guest checkout via dummy guest account, or valid customer ID
		if ( $customer_id === 0 ) {
			$customer = get_user_by( 'email', apply_filters( 'ipress_guest_user_email', 'guest@classworx.co.uk' ) );
			if ( false ===  $customer ) {
				return ( $this->debug ) ? eskimo_log( 'No guest user for this order[' . $order_id . ']', 'cart' ) : '';
			}
		} else {
			$customer = get_user_by( 'ID', $customer_id );
			if ( false ===  $customer ) {
				return ( $this->debug ) ? eskimo_log( 'Invalid customer for this order[' . $order_id . ']', 'cart' ) : '';
			}
		}

		if ( $this->debug ) { eskimo_log( 'order_status_processing ID[' . $order_id . '] user[' . $customer->user_email . '] items[' . count( $order->get_items() ) . '] status[' . $order->get_status() . ']', 'cart' ); }

		// Initiate REST call to update EPOS order status
		$rest_url = esc_url( home_url( '/wp-json' ) ) . '/eskimo/v1/order-insert/' . $order_id;
		if ( $this->debug ) { eskimo_log( 'Rest URL[' . $rest_url . ']', 'cart' ); }
		$response = wp_remote_get( $rest_url, [ 'timeout' => 12 ] );

		// Check the call worked
		if ( is_wp_error( $response ) ) {
			return ( $this->debug ) ? eskimo_log( 'Completed Order ID [' . $order_id . '] Cust ID [' . $customer_id . '] API Error', 'cart' ) : '';
		}

		// Get the response body
		$body = wp_remote_retrieve_body( $response );

		// Check contents and parse
		if ( empty( $body ) ) {
			return ( $this->debug ) ? eskimo_log( 'Empty Order ID [' . $order_id . '] Cust ID [' . $customer_id . '] Completed', 'cart' ) : '';
		}			

		// Get the body data
		$data = json_decode( $body );
		if ( $this->debug ) { eskimo_log( 'EPOS Order: Route[' . $data->route . '] Params[' . $data->params . '] Result[' . $data->result . ']', 'cart' ); }
	}

	/**
	 * Trigger automatic export of order data to EskimoEPOS on order refunded ( post refund )
	 *
	 * @param integer $order_id
	 * @param integer $refund_id
	 */
	public function order_status_refunded( $order_id, $refund_id ) {
   		if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ' ID[' . $order_id . '][' . $refund_id . ']', 'cart' ); }

		// Get valid order details		
		$order = wc_get_order( $order_id );
		if ( false === $order || ! is_a( $order, 'WC_Order' ) ) { 
			return ( $this->debug ) ? eskimo_log( 'Invalid Order ID[' . $id . ']', 'cart' ) : '';
		}

		// Get customer ID
		$customer_id = $order->get_customer_id();

		// Guest checkout via dummy guest account, or valid customer ID
		if ( $customer_id === 0 ) {
			$customer = get_user_by( 'email', apply_filters( 'ipress_guest_user_email', 'guest@classworx.co.uk' ) );
			if ( false ===  $customer ) {
				return ( $this->debug ) ? eskimo_log( 'No guest user for this order[' . $order_id . ']', 'cart' ) : '';
			}
		} else {
			$customer = get_user_by( 'ID', $customer_id );
			if ( false ===  $customer ) {
				return ( $this->debug ) ? eskimo_log( 'Invalid customer for this order[' . $order_id . ']', 'cart' ) : '';
			}
		}

		if ( $this->debug ) { eskimo_log( 'order_status_refunded ID[' . $order_id . '][' . $refund_id . '] user[' . $customer->user_email . '] items[' . count( $order->get_items() ) . '] status[' . $order->get_status() . ']', 'cart' ); }

		// Initiate REST call to update EPOS order status
		$rest_url = esc_url( home_url( '/wp-json' ) ) . '/eskimo/v1/order-return/' . $order_id . '/' . $refund_id;
		if ( $this->debug ) { eskimo_log( 'Rest URL[' . $rest_url . ']', 'cart' ); }
		$response = wp_remote_get( $rest_url, [ 'timeout' => 12 ] );

		// Check the call worked
		if ( is_wp_error( $response ) ) {
			return ( $this->debug ) ? eskimo_log( 'Return Order ID [' . $order_id . '] Refund ID [' . $refund_id . '] Cust ID [' . $customer_id . '] API Error', 'cart' ) : '';
		}

		// Get the response body
		$body = wp_remote_retrieve_body( $response );

		// Check contents and parse
		if ( empty( $body ) ) {
			return ( $this->debug ) ? eskimo_log( 'Empty Order ID [' . $order_id . '] Refund ID [' . $refund_id . '] Cust ID [' . $customer_id . '] Return', 'cart' ) : '';
		}			

		// Get the body data
		$data = json_decode( $body );
		if ( $this->debug ) { eskimo_log( 'EPOS Refund: Route[' . $data->route . '] Params[' . $data->params . '] Result[' . print_r( $data->result, true ) . ']', 'cart' ); }
	}

	/**
	 * Product update stock
	 *
	 * @param	object	$product	WC_Product
	 */
	public function	product_update_variation_stock ( WC_Product $product ) {
		if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ' ID[' . $product->get_id() . '] Type[' . $product->get_type() . ']', 'cart' ); }
		
		// Simple or Variable?
		if ( 'variation' !== $product->get_type() ) {
			if ( $this->debug ) { eskimo_log( 'Product ID [' . $product->get_id() . '] Type[' . $product->get_type() . ']', 'cart' ); }
		}

		// Set product ID
		$product_id = $product->get_id();		
	}

	/**
	 * Product update stock
	 *
	 * @param	object	$product	WC_Product
	 */
	public function	product_update_simple_stock ( WC_Product $product ) {
		if ( $this->debug ) { eskimo_log( __CLASS__ . ':' . __METHOD__ . ' ID[' . $product->get_id() . '] Type[' . $product->get_type() . ']', 'cart' ); }

		// Simple or Variable?
		if ( 'simple' !== $product->get_type() ) {
			if ( $this->debug ) { eskimo_log( 'Product ID [' . $product->get_id() . '] Type[' . $product->get_type() . ']', 'cart' ); }
		}
		
		// Set product ID
		$product_id = $product->get_id();
		
		// Check stock management
		$stock_manage = $product->get_manage_stock();
		if ( $this->debug ) { eskimo_log( 'Stock [' . (int)$stock_manage . ']', 'cart' ); }
		if ( false === $stock_manage ) { return; }

		// Get stock $ SKU
		$product_sku = $product->get_sku();
		$product_qty = $product->get_stock_quantity();
		if ( $this->debug ) { eskimo_log( 'Product ID [' . $product_id . '] Stock: SKU[' . $product_sku . '] Qty[' . $product_qty . ']', 'cart' ); }

		// No SKU?
		if ( empty( $product_sku ) ) { return; }

		// Initiate REST call to update EPOS order status
		$rest_url = esc_url( home_url( '/wp-json' ) ) . '/eskimo/v1/product-stock/adjust/' . $product_id;
		if ( $this->debug ) { eskimo_log( 'Rest URL[' . $rest_url . ']', 'cart' ); }
		$response = wp_remote_get( $rest_url, [ 'timeout' => 12 ] );

		// Check the call worked
		if ( is_wp_error( $response ) ) {
			return ( $this->debug ) ? eskimo_log( 'Stock Product ID [' . $product_id . '] Stock: SKU[' . $product_sku . '] Qty[' . $product_qty . '] API Error', 'cart' ) : '';
		}

		// Get the response body
		$body = wp_remote_retrieve_body( $response );

		// Check contents and parse
		if ( empty( $body ) ) {
			return ( $this->debug ) ? eskimo_log( 'Empty Product ID [' . $product_id . '] Stock: SKU[' . $product_sku . '] Qty[' . $product_qty . '] Stock', 'cart' ) : '';
		}			

		// Get the body data
		$data = json_decode( $body );

		// Valid result
		if ( $this->debug ) { 
			$result = $data->result;

			// Check result type		
			if ( array_key_exists( 'identifier', $result ) ) {
				eskimo_log( 'EPOS Stock Adjust: Route[' . $data->route . '] Params[' . $data->params . '] Result[' . print_r( $data->result, true ) . ']', 'cart' ); 
			} else {
				$msg = $result->message . ': ' . print_r( $result->ModelState, true ); 
				eskimo_log( 'EPOS Stock Adjust: Route[' . $data->route . '] Params[' . $data->params . '] Result[' . $data->result . ']', 'cart' );
			}
		}
	}
}
