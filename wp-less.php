<?php
/**
 * Enables the use of LESS in WordPress
 *
 * See README.md for usage information
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
			preg_match( "/^(.*?\/wp-content\/)([^\?]+)(.*)$/", $src, $src_bits );
			$less_path = WP_CONTENT_DIR . '/' . $src_bits[ 2 ];

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
				else
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
			return $this->get_cache_dir( false ) . "/$handle.css" . ( isset( $src_bits[ 3 ] ) ? $src_bits[ 3 ] : '' );

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

			$url = preg_replace( "/^.*?\/wp-content\/themes\//", '', $url );
			$url = str_replace( '.less', '', $url );
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
				$dir = str_replace( $upload_dir[ 'subdir' ], '', $upload_dir[ 'path' ] ) . '/wp-less-cache';
				// create folder if it doesn't exist yet
				if ( ! file_exists( $dir ) )
					wp_mkdir_p( $dir );
			} else {
				$dir = str_replace( $upload_dir[ 'subdir' ], '', $upload_dir[ 'url' ] ) . '/wp-less-cache';
			}

			return $dir;

		}

	}

	// initialise
	$wp_less = new wp_less();

}

?>
