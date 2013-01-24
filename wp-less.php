<?php
defined( 'ABSPATH' ) OR exit;
/**
Plugin Name:  LESS CSS
Plugin URI:   https://github.com/sanchothefat/wp-less/
Description:  Allows you to enqueue <code>.less</code> files and have them automatically compiled whenever a change is detected.
Author:       Robert O'Rourke
Contributors: Franz Josef Kaiser, Tom Willmot, Andrey "Rarst" Savchenko
Version:      0.3.0
Author URI:   http://interconnectit.com
License:      MIT
 */


add_action( 'plugins_loaded', array( 'wp_less_bootstrap', 'init' ), 5 );
/**
 * Loader/Bootstrap
 * Adds all needed internal & 3rd party vendor files and assets.
 *
 * @author     Franz Josef Kaiser <wecodemore@gmail.com>
 * @link       http://unserkaiser.com/
 * @package    WP LESS
 * @subpackage Bootstrap
 * @license    MIT
 * @version    2013-01-24.0841
 */
final class wp_less_bootstrap
{
	/**
	 * @static
	 * @var    \wp_less_bootstrap Reusable object instance.
	 */
	private static $instance;

	/**
	 * @var array Array of files as 'location' => 'ClassName'
	 */
	private $includes = array(
		 'lessc/lessc.inc'   => 'lessc'
		,'inc/wp-less.class' => 'wp_less'
		,'inc/api'           => ''
		,'inc/updates.class' => 'wp_less_updates'
	);

	/**
	 * Creates an instance of the class
	 * @static
	 * @return \wp_less_bootstrap
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
		foreach ( $this->includes as $file => $class )
		{
			if (
				! empty( $class )
				AND ! class_exists( $class )
			)
				require_once plugin_dir_path( __FILE__ )."{$file}.php";
		}
	}
}