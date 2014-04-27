<?php

class wp_less_admin {

	/**
	 * @static
	 * @var    \wp_less_admin Reusable object instance.
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


	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_pages' ) );
	}

	public function add_pages() {
		add_management_page( 'WP Less', 'WP Less', 'manage_options', 'wpless', array( $this, 'display' ) );
	}

	public function display() {
		echo 'wp less';
		?>
		<div class="wrap about-wrap">
			<h2>WP-Less</h2>

		</div>
	<?php
	}

} 