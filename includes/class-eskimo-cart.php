<?php 
/**
 * The plugin woocommerce eskimoEPOS class 
 *
 * This is used to define EskimoEPOS woocommerce functionality for the plugin
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
	 * @var      string    $base_dir  String path for the plugin directory
	 */
    private $base_dir;

	/**
	 * Initialize the class and set its properties
	 *
	 * @param   string    $eskimo     The name of this plugin
	 * @param   string    $version    The version of this plugin
	 * @param   string    $version    Plugin debugging mode, default false
	 */
	public function __construct( $eskimo, $version ) {

		$this->eskimo       = $eskimo;
		$this->version      = $version;
		$this->debug        = ESKIMO_CART_DEBUG;
		$this->base_dir		= plugin_dir_url( __FILE__ ); 
		
        if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ ); }
	}
    
    //----------------------------------------------
    //  Customer Processing Functions 
    //----------------------------------------------

	/**
	 * Customer created processing - update EPOS
	 */
	public function customer_created( $cust_id ) {
		if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ' Customer ID[' . $cust_id . ']' ); }
	
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
			$response = wp_remote_get( $rest_url );
			$data = $response['body'];
			if ( $data === true ) {
				return ( $this->debug ) ? error_log( 'Customer ID [' . $cust_id . '] Email[' . $user_email . '] Exists' ) : '';
			} else {
				$data = json_decode( $response['body'] );
				if ( $this->debug ) { error_log( 'Customer EPOS [' . $data->params . '] Result[' . $data->result . ']' ); }
			}

			// Create it if not...
			$rest_url = esc_url( home_url( '/wp-json' ) ) . '/eskimo/v1/customer-insert/' . $cust_id;
			$response = wp_remote_get( $rest_url, [ 'timeout' => 10 ] );
			$data = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( $this->debug ) { error_log( 'EPOS Customer:  Route[' . $data['route'] . '] Params[' . $data['params'] . '] Result[' . $data['result'] . ']' ); }
		}
	}
	
	/**
	 * Customer update processing - update EPOS
	 */
	public function customer_updated( $cust_id ) {
		if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ' Customer ID[' . $cust_id . ']' ); }

		// Get user & role
		$user_role = get_user_by( 'ID', $cust_id )->roles[0];
		
		// Initiate REST call to update EPOS order status
		if ( $user_role === 'customer' ) {
			$rest_url = esc_url( home_url( '/wp-json' ) ) . '/eskimo/v1/customer-update/' . $cust_id;
			$response = wp_remote_get( $rest_url, [ 'timeout' => 10 ] );
			$data = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( $this->debug ) { error_log( 'EPOS Customer: Route[' . $data['route'] . '] Params[' . $data['params'] . '] Result[' . $data['result'] . ']' ); }
		}
	}

    //----------------------------------------------
    //  Order Processing Functions
    //----------------------------------------------

	/**
	 * Trigger automatic export of order data to EskimoEPOS on order creation
	 *
	 * @param integer $order_id
	 */
	public function new_order_created( $order_id ) {
		if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ' Order ID[' . $order_id . ']' ); }

		$order	= wc_get_order( $order_id );
		$user 	= $order->get_user();

		// We don't do anything for guest checkout
		if ( false === $user ) {
			return ( $this->debug ) ? error_log( 'No user for this order[' . $order_id . ']' ) : '';
		}

		// OK, get user ID
		$user_id = $order->get_user_id();
		if ( $this->debug ) { error_log( 'New Order ID[' . $order_id . '] Status[' . $order->get_status() . '] User[' . $user_id . ']' ); }

		// Initiate REST call to update EPOS order status
		$rest_url = esc_url( home_url( '/wp-json' ) ) . '/eskimo/v1/customer-insert/' . $user_id;
		$response = wp_remote_get( $rest_url, [ 'timeout' => 10 ] );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $this->debug ) { error_log( 'EPOS Order: Route[' . $data['route'] . '] Params[' . $data['params'] . '] Result[' . $data['result'] . ']' ); }
	}

	/**
	 * Trigger automatic export of order data to EskimoEPOS on order processing ( post payment )
	 *
	 * @param integer $order_id
	 */
	public function order_status_processing( $order_id ) {
   		if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ' ID[' . $order_id . ']' ); }
		
		$order 		 = wc_get_order( $order_id );
		$order_items = $order->get_items();  
		$user 		 = $order->get_user();
		$user_id 	 = $order->get_user_id();

		// We don't do anything for guest checkout
		if ( false === $user ) {
			// OK, get dummy guest account
			$guest_user = get_user_by( 'email', apply_filters( 'ipress_guest_user_email', 'guest@trutexmacclesfield.com' ) );
			if ( false ===  $guest_user ) {
				return ( $this->debug ) ? error_log( 'No user for this order[' . $order_id . ']' ) : '';
			}
		}
		if ( $this->debug ) { error_log( 'order_status_processing id[' . $order_id . '] user[' . $user_id . ']items[' . count( $order_items ) . ']status[' . $order->get_status() . ']' ); }

		// Initiate REST call to update EPOS order status
		$rest_url = esc_url( home_url( '/wp-json' ) ) . '/eskimo/v1/order-insert/' . $order_id;
		$response = wp_remote_get( $rest_url, [ 'timeout' => 10 ] );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $this->debug ) { error_log( 'EPOS Order: Route[' . $data['route'] . '] Params[' . $data['params'] . '] Result[' . $data['result'] . ']' ); }
	}	

	/**
	 * Trigger automatic export of order data to EskimoEPOS on order completion
	 *
	 * @param integer $order_id
	 */
	public function order_status_completed( $order_id ) {
		if ( $this->debug ) { error_log( __CLASS__ . ':' . __METHOD__ . ' ID[' . $order_id . ']' ); }
		
		$order 		 = wc_get_order( $order_id );
		$order_items = $order->get_items();  
		$user 		 = $order->get_user();
		$user_id 	 = $order->get_user_id();

		// We don't do anything for guest checkout
		if ( false === $user ) {
			// OK, get dummy guest account
			$guest_user = get_user_by( 'email', apply_filters( 'ipress_guest_user_email', 'guest@trutexmacclesfield.com' ) );
			if ( false ===  $guest_user ) {
				return ( $this->debug ) ? error_log( 'No user for this order[' . $order_id . ']' ) : '';
			}
		}
		if ( $this->debug ) { error_log( 'order_status_completed id[' . $order_id . '] user[' . $user_id . ']items[' . count( $order_items ) . ']status[' . $order->get_status() . ']' ); }

		// Initiate REST call to update EPOS order status
		$rest_url = esc_url( home_url( '/wp-json' ) ) . '/eskimo/v1/order-insert/' . $order_id;
		$response = wp_remote_get( $rest_url, [ 'timeout' => 10 ] );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $this->debug ) { error_log( 'EPOS Order: Route[' . $data['route'] . '] Params[' . $data['params'] . '] Result[' . $data['result'] . ']' ); }
	}
}
