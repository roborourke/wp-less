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


if( ! class_exists( 'wp_less' ) )
	require dirname( __FILE__ ) . '/inc/class-wp-less.php';

// add on init to support theme customiser in v3.4
add_action( 'init', array( 'wp_less', 'instance' ) );
