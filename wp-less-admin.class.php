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
		add_management_page( 'WP LESS', 'WP LESS', 'manage_options', 'wpless', array( $this, 'display' ) );
	}

	public function display() {
		?>
		<div class="wrap about-wrap">
			<h2>WP-Less</h2>

			<?php
			$recent_messages = get_option('wpless-recent-messages');
			foreach ( $recent_messages as $message ) {
				echo '<p>'.$message.'</p>';
			}
			?>
		</div>
	<?php
	}

} 