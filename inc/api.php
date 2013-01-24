<?php
# PUBLIC API #
defined( 'ABSPATH' ) OR exit;


// Handles LESS functions
if ( ! function_exists( 'register_less_function' ) && ! function_exists( 'unregister_less_function' ) )
{
	/**
	 * Register additional functions you can use in your less stylesheets. You have access
	 * to the full WordPress API here so there's lots you could do.
	 *
	 * @param  string $name     The name of the function
	 * @param  string $callable (callback) A callable method or function recognisable by call_user_func
	 * @return void
	 */
	function register_less_function( $name, $callable )
	{
		$less = wp_less :: instance();
		$less->register( $name, $callable );
	}

	/**
	 * Remove any registered lessc functions
	 *
	 * @param  string $name The function name to remove
	 * @return void
	 */
	function unregister_less_function( $name )
	{
		$less = wp_less :: instance();
		$less->unregister( $name );
	}
}

// Handles LESS vars
if ( ! function_exists( 'add_less_var' ) && ! function_exists( 'remove_less_var' ) )
{
	/**
	 * A simple method of adding less vars via a function call
	 *
	 * @param  string $name  The name of the function
	 * @param  string $value A string that will converted to the appropriate variable type
	 * @return void
	 */
	function add_less_var( $name, $value )
	{
		$less = wp_less :: instance();
		$less->add_var( $name, $value );
	}

	/**
	 * Remove less vars by array key
	 *
	 * @param  string $name The array key of the variable to remove
	 * @return void
	 */
	function remove_less_var( $name )
	{
		$less = wp_less :: instance();
		$less->remove_var( $name );
	}
}