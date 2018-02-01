<?php 
/**
 * The plugin utils class 
 *
 * This is used to define utility helpers for the plugin
 * 
 * @package    Eskimo
 * @subpackage Eskimo/includes
 * @author     Stephen Betley <on@tinternet.co.uk>
 * @since      1.0.0
 */

class Eskimo_Utils { 
	
	/**
	 *  Sort a multi dimensional array by nested array key 
	 * 
	 * @param   array 	$array the array to sort 
	 * @param 	string $key the array key to sort on 
	 * @param 	const 	$order the sort order 
	 * @link    http://stackoverflow.com/a/16306693/977610
	 */
	public static function array_sort( $array, $key, $order = SORT_ASC ) {

	    $new_array = [];
	    $sortable_array = [];

	    if ( count( $array ) > 0 ) {
	        foreach ( $array as $k => $v ) {
	            if (is_array($v)) {
	                foreach ( $v as $k2 => $v2 ) {
	                    if ( $k2 == $key ) {
	                        $sortable_array[ $k ] = $v2;
	                    }
	                }
	            } else {
	                $sortable_array[ $k ] = $v;
	            }
	        }

	        switch ( $order ) {
	            case SORT_ASC:
	                asort( $sortable_array );
	                break;
	            case SORT_DESC:
	                arsort( $sortable_array );
	                break;
	        }

	        foreach ( $sortable_array as $k => $v ) {
	            $new_array[ $k ] = $array[ $k ];
	        }
	    }

	    return $new_array;

	}
}
