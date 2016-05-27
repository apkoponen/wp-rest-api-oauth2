<?php
/**
 * Based on WP REST API - OAuth 1.0a Server (https://github.com/WP-API/OAuth1).
 * Used under GPL3 license.
 */

class OA2_Admin {
	const BASE_SLUG = 'rest-oauth2-apps';

	/**
	 * Register the admin page
	 */
	public static function register() {
		/**
		 * Include anything we need that relies on admin classes/functions
		 */
		include_once dirname( __FILE__ ) . '/class-oa2-listtable.php';

		$hook = add_users_page(
			// Page title
			__( 'Registered OAuth 2.0 Applications', 'wp_rest_oauth2' ),

			// Menu title
			_x( 'Applications (OAuth 2.0)', 'menu title', 'wp_rest_oauth2' ),

			// Capability
			'list_users',

			// Menu slug
			self::BASE_SLUG,

			// Callback
			array( get_class(), 'dispatch' )
		);

		add_action( 'load-' . $hook, array( get_class(), 'load' ) );
	}

	/**
	 * Get the URL for an admin page.
	 *
	 * @param array|string $params Map of parameter key => value, or wp_parse_args string.
	 * @return string Requested URL.
	 */
	protected static function get_url( $params = array() ) {
		$url = admin_url( 'users.php' );
		$params = array( 'page' => self::BASE_SLUG ) + wp_parse_args( $params );
		return add_query_arg( urlencode_deep( $params ), $url );
	}

	/**
	 * Get the current page action.
	 *
	 * @return string One of 'add', 'edit', 'delete', or '' for default (list)
	 */
	protected static function current_action() {
		return isset( $_GET['action'] ) ? $_GET['action'] : '';
	}

	/**
	 * Load data for our page.
	 */
	public static function load() {
		switch ( self::current_action() ) {
			case 'add':
			case 'edit':
				return self::render_edit_page();

			case 'delete':
				return self::handle_delete();

			case 'regenerate':
				return self::handle_regenerate();

			default:
				global $wp_list_table;

				$wp_list_table = new OA2_ListTable();

				$wp_list_table->prepare_items();

				return;
		}

	}

	public static function dispatch() {
		switch ( self::current_action() ) {
			case 'add':
			case 'edit':
			case 'delete':
				return;

			default:
				return self::render();
		}
	}

	/**
	 * Render the list page.
	 */
	public static function render() {
		global $wp_list_table;

		?>
		<div class="wrap">
			<h2>
				<?php
				esc_html_e( 'Registered Applications (OAuth 2.0)', 'wp_rest_oauth2' );

				if ( current_user_can( 'create_users' ) ): ?>
					<a href="<?php echo esc_url( self::get_url( 'action=add' ) ) ?>"
						class="add-new-h2"><?php echo esc_html_x( 'Add New', 'application', 'wp_rest_oauth2' ); ?></a>
				<?php
				endif;
				?>
			</h2>
			<?php
			if ( ! empty( $_GET['deleted'] ) ) {
				echo '<div id="message" class="updated"><p>' . esc_html__( 'Deleted application.', 'wp_rest_oauth2' ) . '</p></div>';
			}
			?>

			<?php $wp_list_table->views(); ?>

			<form action="" method="get">

				<?php $wp_list_table->search_box( __( 'Search Applications', 'wp_rest_oauth2' ), 'wp_rest_oauth2' ); ?>

				<?php $wp_list_table->display(); ?>

			</form>

			<br class="clear" />

		</div>
		<?php
	}

	protected static function validate_parameters( $params ) {
		$valid = array();

		if ( empty( $params['name'] ) ) {
			return new WP_Error( 'rest_oauth2_missing_name', __( 'Consumer name is required', 'wp_rest_oauth2' ) );
		}
		$valid['name'] = wp_filter_post_kses( $params['name'] );

		if ( empty( $params['description'] ) ) {
			return new WP_Error( 'rest_oauth2_missing_description', __( 'Consumer description is required', 'wp_rest_oauth2' ) );
		}
		$valid['description'] = wp_filter_post_kses( $params['description'] );

		if ( empty( $params['redirect_uri'] ) ) {
			return new WP_Error( 'rest_oauth2_missing_description', __( 'Consumer redirect URI is required and must be a valid URL.', 'wp_rest_oauth2' ) );
		}
		if ( ! empty( $params['redirect_uri'] ) ) {
			$valid['redirect_uri'] = $params['redirect_uri'];
		}

		return $valid;
	}

	/**
	 * Handle submission of the add page
	 *
	 * @return array|null List of errors. Issues a redirect and exits on success.
	 */
	protected static function handle_edit_submit( $consumer ) {
		$messages = array();
		if ( empty( $consumer ) ) {
			$did_action = 'add';
			check_admin_referer( 'rest-oauth2-add' );
		}
		else {
			$did_action = 'edit';
			check_admin_referer( 'rest-oauth2-edit-' . $consumer->ID );
		}

		// Check that the parameters are correct first
		$params = self::validate_parameters( wp_unslash( $_POST ) );
		if ( is_wp_error( $params ) ) {
			$messages[] = $params->get_error_message();
			return $messages;
		}

		if ( empty( $consumer ) ) {
			//$authenticator = new OA2();

			// Create the consumer
			$data = array(
				'name' => $params['name'],
				'description' => $params['description'],
				'meta' => array(
					'redirect_uri' => $params['redirect_uri'],
				),
			);
			$consumer = $result = OA2_Client::create( $data );
		}
		else {
			// Update the existing consumer post
			$data = array(
				'name' => $params['name'],
				'description' => $params['description'],
				'meta' => array(
					'redirect_uri' => $params['redirect_uri'],
				),
			);
			$result = $consumer->update( $data );
		}

		if ( is_wp_error( $result ) ) {
			$messages[] = $result->get_error_message();

			return $messages;
		}

		// Success, redirect to alias page
		$location = self::get_url(
			array(
				'action'     => 'edit',
				'id'         => $consumer->ID,
				'did_action' => $did_action,
			)
		);
		wp_safe_redirect( $location );
		exit;
	}

	/**
	 * Output alias editing page
	 */
	public static function render_edit_page() {
		if ( ! current_user_can( 'edit_users' ) ) {
			wp_die( __( 'You do not have permission to access this page.', 'wp_rest_oauth2' ) );
		}

		// Are we editing?
		$consumer = null;
		$form_action = self::get_url('action=add');
		if ( ! empty( $_REQUEST['id'] ) ) {
			$id = absint( $_REQUEST['id'] );
			$consumer = OA2_Client::get( $id );
			if ( is_wp_error( $consumer ) || empty( $consumer ) ) {
				wp_die( __( 'Invalid consumer ID.', 'wp_rest_oauth2' ) );
			}

			$form_action = self::get_url( array( 'action' => 'edit', 'id' => $id ) );
			$regenerate_action = self::get_url( array( 'action' => 'regenerate', 'id' => $id ) );
		}

		// Handle form submission
		$messages = array();
		if ( ! empty( $_POST['submit'] ) ) {
			$messages = self::handle_edit_submit( $consumer );
		}
		if ( ! empty( $_GET['did_action'] ) ) {
			switch ( $_GET['did_action'] ) {
				case 'edit':
					$messages[] = __( 'Updated application.', 'wp_rest_oauth2' );
					break;

				case 'regenerate':
					$messages[] = __( 'Regenerated secret.', 'wp_rest_oauth2' );
					break;

				default:
					$messages[] = __( 'Successfully created application.', 'wp_rest_oauth2' );
					break;
			}
		}

		$data = array();

		if ( empty( $consumer ) || ! empty( $_POST['_wpnonce'] ) ) {
			foreach ( array( 'name', 'description', 'redirect_uri' ) as $key ) {
				$data[ $key ] = empty( $_POST[ $key ] ) ? '' : wp_unslash( $_POST[ $key ] );
			}
		}
		else {
			$data['name'] = $consumer->post_title;
			$data['description'] = $consumer->post_content;
			$data['redirect_uri'] = $consumer->redirect_uri;
		}

		// Header time!
		global $title, $parent_file, $submenu_file;
		$title = $consumer ? __( 'Edit Application', 'wp_rest_oauth2' ) : __( 'Add Application', 'wp_rest_oauth2' );
		$parent_file = 'users.php';
		$submenu_file = self::BASE_SLUG;

		include( ABSPATH . 'wp-admin/admin-header.php' );
	?>

	<div class="wrap">
		<h2 id="edit-site"><?php echo esc_html( $title ) ?></h2>

		<?php
		if ( ! empty( $messages ) ) {
			foreach ( $messages as $msg )
				echo '<div id="message" class="updated"><p>' . esc_html( $msg ) . '</p></div>';
		}
		?>

		<form method="post" action="<?php echo esc_url( $form_action ) ?>">
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="oauth-name"><?php echo esc_html_x( 'Consumer Name', 'field name', 'wp_rest_oauth2' ) ?></label>
					</th>
					<td>
						<input type="text" class="regular-text"
							name="name" id="oauth-name"
							value="<?php echo esc_attr( $data['name'] ) ?>" />
						<p class="description"><?php esc_html_e( 'This is shown to users during authorization and in their profile.', 'wp_rest_oauth2' ) ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="oauth-description"><?php echo esc_html_x( 'Description', 'field name', 'wp_rest_oauth2' ) ?></label>
					</th>
					<td>
						<textarea class="regular-text" name="description" id="oauth-description"
							cols="30" rows="5" style="width: 500px"><?php echo esc_textarea( $data['description'] ) ?></textarea>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="oauth-redirect_uri"><?php echo esc_html_x( 'Redirect URI', 'field name', 'wp_rest_oauth2' ) ?></label>
					</th>
					<td>
						<input type="text" class="regular-text"
							name="redirect_uri" id="oauth-redirect_uri"
							value="<?php echo esc_attr( $data['redirect_uri'] ) ?>" />
						<p class="description"><?php esc_html_e( "Your application's redirect URI. The redirect URI passed to the authorization endpoint must match the scheme, host, port, and path of this URI.", 'wp_rest_oauth2' ) ?></p>
					</td>
				</tr>
			</table>

			<?php

			if ( empty( $consumer ) ) {
				wp_nonce_field( 'rest-oauth2-add' );
				submit_button( __( 'Add Consumer', 'wp_rest_oauth2' ) );
			}
			else {
				echo '<input type="hidden" name="id" value="' . esc_attr( $consumer->ID ) . '" />';
				wp_nonce_field( 'rest-oauth2-edit-' . $consumer->ID );
				submit_button( __( 'Save Consumer', 'wp_rest_oauth2' ) );
			}

			?>
		</form>

		<?php if ( ! empty( $consumer ) ): ?>
			<form method="post" action="<?php echo esc_url( $regenerate_action ) ?>">
				<h3><?php esc_html_e( 'OAuth 2.0 Credentials', 'wp_rest_oauth2' ) ?></h3>

				<table class="form-table">
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Client ID', 'wp_rest_oauth2' ) ?>
						</th>
						<td>
							<code><?php echo esc_html( $consumer->client_id ) ?></code>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Client Secret', 'wp_rest_oauth2' ) ?>
						</th>
						<td>
							<code><?php echo esc_html( $consumer->client_secret ) ?></code>
						</td>
					</tr>
				</table>

				<?php
				wp_nonce_field( 'rest-oauth2-regenerate:' . $consumer->ID );
				submit_button( __( 'Regenerate Secret', 'wp_rest_oauth2' ), 'delete' );
				?>
			</form>
		<?php endif ?>
	</div>

	<?php
	}

	public static function handle_delete() {
		if ( empty( $_GET['id'] ) ) {
			return;
		}

		$id = $_GET['id'];
		check_admin_referer( 'rest-oauth2-delete:' . $id );

		if ( ! current_user_can( 'delete_post', $id ) ) {
			wp_die(
				'<h1>' . __( 'Cheatin&#8217; uh?', 'wp_rest_oauth2' ) . '</h1>' .
				'<p>' . __( 'You are not allowed to delete this application.', 'wp_rest_oauth2' ) . '</p>',
				403
			);
		}

		$client = OA2_Client::get( $id );
		if ( is_wp_error( $client ) ) {
			wp_die( $client );
			return;
		}

		if ( ! $client->delete() ) {
			$message = 'Invalid consumer ID';
			wp_die( $message );
			return;
		}

		wp_safe_redirect( self::get_url( 'deleted=1' ) );
		exit;
	}

	public static function handle_regenerate() {
		if ( empty( $_GET['id'] ) ) {
			return;
		}

		$id = $_GET['id'];
		check_admin_referer( 'rest-oauth2-regenerate:' . $id );

		if ( ! current_user_can( 'edit_post', $id ) ) {
			wp_die(
				'<h1>' . __( 'Cheatin&#8217; uh?', 'wp_rest_oauth2' ) . '</h1>' .
				'<p>' . __( 'You are not allowed to edit this application.', 'wp_rest_oauth2' ) . '</p>',
				403
			);
		}

		$client = OA2_Client::get( $id );
		$client->regenerate_secret();

		wp_safe_redirect( self::get_url( array( 'action' => 'edit', 'id' => $id, 'did_action' => 'regenerate' ) ) );
		exit;
	}
}
