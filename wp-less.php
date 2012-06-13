<?php
/**
Plugin Name: LESS CSS Auto Compiler
Plugin URI: https://github.com/sanchothefat/wp-less/
Description: Allows you to enqueue .less files and have them automatically compiled whenever a change is detected.
Author: Robert O'Rourke
Version: 1.0
Author URI: http://interconnectit.com
License: WTFPL
*/


/**
 * Enables the use of LESS in WordPress
 *
 * See README.md for usage information
 *
 * Licensed under WTFPL
 */


if ( ! class_exists( 'lessc' ) ) {

	// load LESS parser
	require_once( 'lessc/lessc.inc.php' );

}

if ( ! class_exists( 'wp_less' ) ) {

	class wp_less {

		function __construct() {

			// every CSS file URL gets passed through this filter
			add_filter( 'style_loader_src', array( &$this, 'parse_stylesheet' ), 100000, 2 );

			// editor stylesheet URLs are concatenated and run through this filter
			add_filter( 'mce_css', array( &$this, 'parse_editor_stylesheets' ), 100000 );

		}

		/**
		 * Lessify the stylesheet and return the href of the compiled file
		 *
		 * @param String $src	Source URL of the file to be parsed
		 * @param String $handle	An identifier for the file used to create the file name in the cache
		 *
		 * @return String    URL of the compiled stylesheet
		 */
		function parse_stylesheet( $src, $handle ) {

			// we only want to handle .less files
			if ( ! strstr( $src, '.less' ) )
				return $src;

			// get file path from $src
			if ( ! strstr( $src, '?' ) )
				$src .= '?';
			list( $less_path, $query_string ) = explode( '?', str_replace( WP_CONTENT_URL, WP_CONTENT_DIR, $src ) );

			// output css file name
			$css_path = $this->get_cache_dir() . "/$handle.css";

			// vars to pass into the compiler - default @themeurl var for image urls etc...
			$vars = apply_filters( 'less_vars', array( 'themeurl' => '~"' . get_stylesheet_directory_uri() . '"' ), $handle );
			$vars = apply_filters( "less_vars_$handle", $vars );

			// automatically regenerate files if source's modified time has changed or vars have changed
			try {
			
				// load the cache
				$cache_path = "$css_path.cache";
				
				if ( file_exists( $cache_path ) )
					$full_cache = unserialize( file_get_contents( $cache_path ) );
				
				// If the root path in the cache is wrong then regenerate
				if ( ! isset( $full_cache['less']['root'] ) || ! file_exists( $full_cache['less']['root'] ) )
					$full_cache = array( 'vars' => $vars, 'less' => $less_path );
					
				$less_cache = lessc::cexecute( $full_cache[ 'less' ] );
				
				if ( ! is_array( $less_cache ) || $less_cache['updated'] > $full_cache[ 'less' ]['updated'] || $vars !== $full_cache[ 'vars' ] ) {
					$less = new lessc( $less_path );
					file_put_contents( $cache_path, serialize( array( 'vars' => $vars, 'less' => $less_cache ) ) );
					file_put_contents( $css_path, $less->parse( null, $vars ) );
				}

			} catch ( exception $ex ) {
			  wp_die( $ex->getMessage() );
			}

			// return the compiled stylesheet with the query string it had if any
			return $this->get_cache_dir( false ) . "/$handle.css" . ( ! empty( $query_string ) ? '?' . $query_string : '' );

		}

		/**
		 * Compile editor stylesheets registered via add_editor_style()
		 *
		 * @param String $mce_css comma separated list of CSS file URLs
		 *
		 * @return String    New comma separated list of CSS file URLs
		 */
		function parse_editor_stylesheets( $mce_css ) {

			// extract CSS file URLs
			$style_sheets = explode( ',', $mce_css );

			if ( count( $style_sheets ) ) {
				$compiled_css = array();

				// loop through editor styles, any .less files will be compiled and the compiled URL returned
				foreach( $style_sheets as $style_sheet )
					$compiled_css[] = $this->parse_stylesheet( $style_sheet, $this->url_to_handle( "$style_sheet" ) );

				$mce_css = implode( ',', $compiled_css );
			}

			// return new URLs
			return $mce_css;

		}

		/**
		 * Get a nice handle to use for the compiled CSS file name
		 *
		 * @param String $url 	File URL to generate a handle from
		 *
		 * @return String    Sanitised string to use for handle
		 */
		function url_to_handle( $url ) {

			$url = parse_url( $url );
			$url = str_replace( '.less', '', basename( $url[ 'path' ] ) );
			$url = str_replace( '/', '-', $url );

			return sanitize_key( $url );

		}


		/**
		 * Get (and create if unavailable) the compiled CSS cache directory
		 *
		 * @param Bool $path 	If true this method returns the cache's system path. Set to false to return the cache URL
		 *
		 * @return String 	The system path or URL of the cache folder
		 */
		function get_cache_dir( $path = true ) {

			// get path and url info
			$upload_dir = wp_upload_dir();

			if ( $path ) {
				$dir = apply_filters( 'wp_less_cache_path', trailingslashit( $upload_dir[ 'basedir' ] ) . 'wp-less-cache' );
				// create folder if it doesn't exist yet
				if ( ! file_exists( $dir ) )
					wp_mkdir_p( $dir );
			} else {
				$dir = apply_filters( 'wp_less_cache_url', trailingslashit( $upload_dir[ 'baseurl' ] ) . 'wp-less-cache' );
			}

			return rtrim( $dir, '/' );

		}


		/**
		 * Escape a string that has non alpha numeric characters variable for use within .less stylesheets
		 *
		 * @param string $str The string to escape
		 *
		 * @return string    String ready for passing into the compiler
		 */
		function sanitize_string( $str ) {
			return '~"' . $str . '"';
		}

	}

	// initialise
	$wp_less = new wp_less();

}

?>
