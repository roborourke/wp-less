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
	 * @return \wp_less_admin
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
		<div class="wrap">
			<h2>WP-LESS</h2>

			<p>Here are messages from attempts to build/rebuild stylesheets. Only the last 20 messages are kept.</p>

			<table class="wp-list-table widefat fixed">
				<thead>
					<th width="120px">Time</th>
					<th>Message</th>
				</thead>
				<tbody>
				<?php
				$recent_messages = get_option('wpless-recent-messages');
				if ( !empty( $recent_messages ) ) {
					foreach ( $recent_messages as $message ) {
						echo '<tr>';
						if ( is_array( $message ) ) {
							echo '<td>'.date( 'D, d M Y H:i:s', absint( $message['time'] ) ).'</td>';
							echo '<td>'.wp_kses( $message['payload'] ).'</td>';
						} else {
							echo '<td colspan="2">'.wp_kses( $message ).'</td>';
						}
						echo '</tr>';
					}
				} else {
					echo '<tr><td colspan="2">No messages</td></tr>';
				}
				?>
				</tbody>
			</table>
		</div>
	<?php
	}

} 
