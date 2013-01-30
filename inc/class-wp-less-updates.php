<?php
defined( 'ABSPATH' ) OR exit;


add_action( 'plugins_loaded', array( 'wp_less_updates', 'init' ), 8 );
/**
 * Updates
 * Everything that concerns updates.
 *
 * @author     Franz Josef Kaiser <wecodemore@gmail.com>
 * @link       http://unserkaiser.com/
 * @package    WP LESS
 * @subpackage Updates
 * @license    MIT
 * @version    2013-01-24.0841
 */
final class wp_less_updates
{
	/**
	 * @static
	 * @var    \wp_less_updates Reusable object instance.
	 */
	private static $instance;

	/**
	 * Creates an instance of the class
	 * @static
	 * @return \wp_less_updates
	 */
	public static function init()
	{
		null === self :: $instance AND self :: $instance = new self;
		return self :: $instance;
	}

	/**
	 * Constructor
	 * Conflict free file loading
	 */
	public function __construct()
	{
		add_filter( 'http_request_args', array( $this, 'exclude_wporg_repo' ), 5, 2 );
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
	public function exclude_wporg_repo( $r, $url )
	{
		// Not a plugin update request. Bail immediately.
		if ( 0 !== strpos( $url, 'http://api.wordpress.org/plugins/update-check' ) )
			return $r;

		$plugins = unserialize( $r['body']['plugins'] );
		unset( $plugins->plugins[ plugin_basename( __FILE__ ) ] );
		unset( $plugins->active[ array_search( plugin_basename( __FILE__ ), $plugins->active ) ] );
		$r['body']['plugins'] = serialize( $plugins );

		return $r;
	}
}