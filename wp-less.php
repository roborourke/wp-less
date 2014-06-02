<?php
/**
Plugin Name:  LESS CSS
Plugin URI:   https://github.com/sanchothefat/wp-less/
Description:  Allows you to enqueue <code>.less</code> files and have them automatically compiled whenever a change is detected.
Author:       Robert O'Rourke
Contributors: Franz Josef Kaiser,Tom Willmot, Rarst, Tom J Nowell, Code For The PeopleGIT S
Version:      2.1
Author URI:   http://interconnectit.com
License:      MIT
*/

// Busted! No direct file access
! defined( 'ABSPATH' ) AND exit;

// load the autoloader if it's present
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require __DIR__ . '/vendor/autoload.php';
} else if ( file_exists( __DIR__.'/vendor/leafo/lessphp/lessc.inc.php' ) ) {
	// load LESS parser
	require_once( __DIR__.'/vendor/leafo/lessphp/lessc.inc.php' );
}

require_once( 'wp-less.class.php');
require_once( 'wp-less-admin.class.php' );


$admin_page = new wp_less_admin();
// add on init to support theme customiser in v3.4
add_action( 'init', array( 'wp_less', 'instance' ) );

if ( ! function_exists( 'register_less_function' ) && ! function_exists( 'unregister_less_function' ) ) {
	/**
	 * Register additional functions you can use in your less stylesheets. You have access
	 * to the full WordPress API here so there's lots you could do.
	 *
	 * @param  string $name     The name of the function
	 * @param  string $callable (callback) A callable method or function recognisable by call_user_func
	 * @return void
	 */
	function register_less_function( $name, $callable ) {
		$less = wp_less::instance();
		$less->register( $name, $callable );
	}

	/**
	 * Remove any registered lessc functions
	 *
	 * @param  string $name The function name to remove
	 * @return void
	 */
	function unregister_less_function( $name ) {
		$less = wp_less::instance();
		$less->unregister( $name );
	}
}

if ( ! function_exists( 'add_less_var' ) && ! function_exists( 'remove_less_var' ) ) {
	/**
	 * A simple method of adding less vars via a function call
	 *
	 * @param  string $name  The name of the function
	 * @param  string $value A string that will converted to the appropriate variable type
	 * @return void
	 */
	function add_less_var( $name, $value ) {
		$less = wp_less::instance();
		$less->add_var( $name, $value );
	}

	/**
	 * Remove less vars by array key
	 *
	 * @param  string $name The array key of the variable to remove
	 * @return void
	 */
	function remove_less_var( $name ) {
		$less = wp_less::instance();
		$less->remove_var( $name );
	}
}

