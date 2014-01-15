<?php
/**
Plugin Name:  LESS CSS
Plugin URI:   https://github.com/sanchothefat/wp-less/
Description:  Allows you to enqueue <code>.less</code> files and have them automatically compiled whenever a change is detected.
Author:       Robert O'Rourke
Contributors: Franz Josef Kaiser, Tom Willmot, Rarst
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

if ( ! class_exists( 'wp_less' ) ) {
	// add on init to support theme customiser in v3.4
	add_action( 'init', array( 'wp_less', 'instance' ) );

/**
 * Enables the use of LESS in WordPress
 *
 * See README.md for usage information
 *
 * @author  Robert "sancho the fat" O'Rourke
 * @link    http://sanchothefat.com/
 * @package WP LESS
 * @license MIT
 * @version 2012-06-13.1701
 */
class wp_less {
	/**
	 * @static
	 * @var    \wp_less Reusable object instance.
	 */
	protected static $instance = null;


	/**
	 * Creates a new instance. Called on 'after_setup_theme'.
	 * May be used to access class methods from outside.
	 *
	 * @see    __construct()
	 * @static
	 * @return \wp_less
	 */
	public static function instance() {
		null === self :: $instance AND self :: $instance = new self;
		return self :: $instance;
	}


	/**
	 * @var array Array store of callable functions used to extend the parser
	 */
	public $registered_functions = array();


	/**
	 * @var array Array store of function names to be removed from the compiler class
	 */
	public $unregistered_functions = array();


	/**
	 * @var array Variables to be passed into the compiler
	 */
	public $vars = array();


	/**
	 * @var string Compression class to use
	 */
	public $compression = 'compressed';


	/**
	 * @var bool Whether to preserve comments when compiling
	 */
	public $preserve_comments = false;


	/**
	 * @var array Default import directory paths for lessc to scan
	 */
	public $import_dirs = array();


	/**
	 * Constructor
	 */
	public function __construct() {

		// every CSS file URL gets passed through this filter
		add_filter( 'style_loader_src', array( $this, 'parse_stylesheet' ), 100000, 2 );

		// editor stylesheet URLs are concatenated and run through this filter
		add_filter( 'mce_css', array( $this, 'parse_editor_stylesheets' ), 100000 );

		// exclude from official repo update check
		add_filter( 'http_request_args', array( $this, 'http_request_args' ), 5, 2 );
	}

	/**
	 * Exclude from official repo update check.
	 *
	 * @link   http://markjaquith.wordpress.com/2009/12/14/excluding-your-plugin-or-theme-from-update-checks/
	 *
	 * @param  array  $r
	 * @param  string $url
	 * @return array
	 */
	public function http_request_args( $r, $url ) {

		if ( 0 !== strpos( $url, 'http://api.wordpress.org/plugins/update-check' ) )
			return $r; // Not a plugin update request. Bail immediately.

		$plugins = unserialize( $r[ 'body' ][ 'plugins' ] );
		unset( $plugins->plugins[plugin_basename( __FILE__ )] );
		unset( $plugins->active[ array_search( plugin_basename( __FILE__ ), $plugins->active ) ] );
		$r[ 'body' ][ 'plugins' ] = serialize( $plugins );

		return $r;
	}


	/**
	 * Lessify the stylesheet and return the href of the compiled file
	 *
	 * @param  string $src    Source URL of the file to be parsed
	 * @param  string $handle An identifier for the file used to create the file name in the cache
	 * @return string         URL of the compiled stylesheet
	 */
	public function parse_stylesheet( $src, $handle ) {

		// we only want to handle .less files
		if ( ! preg_match( '/\.less(\.php)?$/', preg_replace( '/\?.*$/', '', $src ) ) )
			return $src;

		// get file path from $src
		if ( ! strstr( $src, '?' ) ) $src .= '?'; // prevent non-existent index warning when using list() & explode()

		// Match the URL schemes between WP_CONTENT_URL and $src,
		// so the str_replace further down will work
		$src_scheme = parse_url( $src, PHP_URL_SCHEME );
		$wp_content_url_scheme = parse_url( WP_CONTENT_URL, PHP_URL_SCHEME );
		if ( $src_scheme != $wp_content_url_scheme )
			$src = set_url_scheme( $src, $wp_content_url_scheme );

		list( $less_path, $query_string ) = explode( '?', str_replace( WP_CONTENT_URL, WP_CONTENT_DIR, $src ) );

		// output css file name
		$css_path = trailingslashit( $this->get_cache_dir() ) . "{$handle}.css";

		// automatically regenerate files if source's modified time has changed or vars have changed
		try {

			// initialise the parser
			$less = new lessc;

			// load the cache
			$cache_path = "{$css_path}.cache";

			if ( file_exists( $cache_path ) )
				$cache = unserialize( file_get_contents( $cache_path ) );

			// vars to pass into the compiler - default @themeurl var for image urls etc...
			$this->vars[ 'themeurl' ] = '~"' . get_stylesheet_directory_uri() . '"';
			$this->vars[ 'lessurl' ]  = '~"' . dirname( $src ) . '"';
			$this->vars = apply_filters( 'less_vars', $this->vars, $handle );

			// If the cache or root path in it are invalid then regenerate
			if ( empty( $cache ) || empty( $cache['less']['root'] ) || ! file_exists( $cache['less']['root'] ) )
				$cache = array( 'vars' => $this->vars, 'less' => $less_path );

			// less config
			$less->setFormatter( apply_filters( 'less_compression', $this->compression ) );
			$less->setPreserveComments( apply_filters( 'less_preserve_comments', $this->preserve_comments ) );
			$less->setVariables( $this->vars );

			// add directories to scan for imports
			$import_dirs = apply_filters( 'less_import_dirs', $this->import_dirs );
			if ( ! empty( $import_dirs ) ) {
				foreach( (array)$import_dirs as $dir )
					$less->addImportDir( $dir );
			}

			// register and unregister functions
			foreach( $this->registered_functions as $name => $callable )
				$less->registerFunction( $name, $callable );

			foreach( $this->unregistered_functions as $name )
				$less->unregisterFunction( $name );

			// allow devs to mess around with the less object configuration
			do_action_ref_array( 'lessc', array( &$less ) );

			// $less->cachedCompile only checks for changed file modification times
			// if using the theme customiser (changed variables not files) then force a compile
			if ( $this->vars !== $cache[ 'vars' ] ) {
				$force = true;
			} else {
				$force = false;
			}
			$less_cache = $less->cachedCompile( $cache[ 'less' ], apply_filters( 'less_force_compile', $force ) );

			if ( empty( $cache ) || empty( $cache[ 'less' ][ 'updated' ] ) || $less_cache[ 'updated' ] > $cache[ 'less' ][ 'updated' ] || $this->vars !== $cache[ 'vars' ] ) {
				file_put_contents( $cache_path, serialize( array( 'vars' => $this->vars, 'less' => $less_cache ) ) );
				file_put_contents( $css_path, $less_cache[ 'compiled' ] );
			}
		} catch ( exception $ex ) {
			wp_die( $ex->getMessage() );
		}

		// restore query string it had if any
		$url = trailingslashit( $this->get_cache_dir( false ) ) . "{$handle}.css" . ( ! empty( $query_string ) ? "?{$query_string}" : '' );

		// restore original url scheme
		$url = set_url_scheme( $url, $src_scheme );

		return add_query_arg( 'ver', $less_cache[ 'updated' ], $url );
	}


	/**
	 * Compile editor stylesheets registered via add_editor_style()
	 *
	 * @param  string $mce_css Comma separated list of CSS file URLs
	 * @return string $mce_css New comma separated list of CSS file URLs
	 */
	public function parse_editor_stylesheets( $mce_css ) {

		// extract CSS file URLs
		$style_sheets = explode( ",", $mce_css );

		if ( count( $style_sheets ) ) {
			$compiled_css = array();

			// loop through editor styles, any .less files will be compiled and the compiled URL returned
			foreach( $style_sheets as $style_sheet )
				$compiled_css[] = $this->parse_stylesheet( $style_sheet, $this->url_to_handle( $style_sheet ) );

			$mce_css = implode( ",", $compiled_css );
		}

		// return new URLs
		return $mce_css;
	}


	/**
	 * Get a nice handle to use for the compiled CSS file name
	 *
	 * @param  string $url File URL to generate a handle from
	 * @return string $url Sanitized string to use for handle
	 */
	public function url_to_handle( $url ) {

		$url = parse_url( $url );
		$url = str_replace( '.less', '', basename( $url[ 'path' ] ) );
		$url = str_replace( '/', '-', $url );

		return sanitize_key( $url );
	}


	/**
	 * Get (and create if unavailable) the compiled CSS cache directory
	 *
	 * @param  bool   $path If true this method returns the cache's system path. Set to false to return the cache URL
	 * @return string $dir  The system path or URL of the cache folder
	 */
	public function get_cache_dir( $path = true ) {

		// get path and url info
		$upload_dir = wp_upload_dir();

		if ( $path ) {
			$dir = apply_filters( 'wp_less_cache_path', path_join( $upload_dir[ 'basedir' ], 'wp-less-cache' ) );
			// create folder if it doesn't exist yet
			wp_mkdir_p( $dir );
		} else {
			$dir = apply_filters( 'wp_less_cache_url', path_join( $upload_dir[ 'baseurl' ], 'wp-less-cache' ) );
		}

		return rtrim( $dir, '/' );
	}


	/**
	 * Escape a string that has non alpha numeric characters variable for use within .less stylesheets
	 *
	 * @param  string $str The string to escape
	 * @return string $str String ready for passing into the compiler
	 */
	public function sanitize_string( $str ) {

		return '~"' . $str . '"';
	}


	/**
	 * Adds an interface to register lessc functions. See the documentation
	 * for details: http://leafo.net/lessphp/docs/#custom_functions
	 *
	 * @param  string $name     The name for function used in the less file eg. 'makebluer'
	 * @param  string $callable (callback) Callable method or function that returns a lessc variable
	 * @return void
	 */
	public function register( $name, $callable ) {
		$this->registered_functions[ $name ] = $callable;
	}

	/**
	 * Unregisters a function
	 *
	 * @param  string $name The function name to unregister
	 * @return void
	 */
	public function unregister( $name ) {
		$this->unregistered_functions[ $name ] = $name;
	}


	/**
	 * Add less var prior to compiling
	 *
	 * @param  string $name  The variable name
	 * @param  string $value The value for the variable as a string
	 * @return void
	 */
	public function add_var( $name, $value ) {
		if ( is_string( $name ) )
			$this->vars[ $name ] = $value;
	}

	/**
	 * Removes a less var
	 *
	 * @param  string $name Name of the variable to remove
	 * @return void
	 */
	public function remove_var( $name ) {
		if ( isset( $this->vars[ $name ] ) )
			unset( $this->vars[ $name ] );
	}
} // END class

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

} // endif;
