<?php
/**
 * Administration UI and utilities
 * 
 * Based on WP REST API - OAuth 1.0a Server (https://github.com/WP-API/OAuth1).
 * Used under GPL3 license.
 */

require dirname( __FILE__ ) . '/lib/class-wp-rest-oauth2-admin.php';

add_action( 'admin_menu', array( 'WP_REST_OAuth2_Admin', 'register' ) );

add_action( 'personal_options', 'rest_oauth2_profile_section', 50 );

add_action( 'all_admin_notices', 'rest_oauth2_profile_messages' );

add_action( 'personal_options_update',  'rest_oauth2_profile_save', 10, 1 );
add_action( 'edit_user_profile_update', 'rest_oauth2_profile_save', 10, 1 );

function rest_oauth2_profile_section( $user ) {
	global $wpdb;

	// Get user's tokens that haven't expired
	$query_args = array(
		'author' => $user->ID,
		'meta_query' => array(
			array(
				'key' => 'expires',
				'value'   => time(),
				'compare' => '>',
				'type' => 'NUMERIC'
			)
		)
	);
	$access_tokens = WP_REST_OAuth2_Access_Token::get_tokens( $query_args );
	?>
		<table class="form-table">
			<tbody>
			<tr>
				<th scope="row"><?php _e( 'Authorized Applications (OAuth 2.0)', 'wp_rest_oauth2' ) ?></th>
				<td>
					<?php if ( ! empty( $access_tokens ) ): ?>
						<table class="widefat">
							<thead>
							<tr>
								<th style="padding-left:10px;"><?php esc_html_e( 'Application Name', 'wp_rest_oauth2' ); ?></th>
								<th></th>
							</tr>
							</thead>
							<tbody>
							<?php foreach ( $access_tokens as $access_token ): ?>
								<?php
								$application = WP_REST_OAuth2_Client::get_by_client_id( $access_token->client_id );
								?>
								<tr>
									<td><?php echo esc_html( $application->post_title ) ?></td>
									<td><button class="button" name="rest_oauth2_revoke" value="<?php echo esc_attr( $access_token->ID ) ?>"><?php esc_html_e( 'Revoke', 'wp_rest_oauth2' ) ?></button>
								</tr>

							<?php endforeach ?>
							</tbody>
						</table>
					<?php else: ?>
						<p class="description"><?php esc_html_e( 'No applications authorized.', 'wp_rest_oauth2' ) ?></p>
					<?php endif ?>
				</td>
			</tr>
			</tbody>
		</table>
	<?php
}

function rest_oauth2_profile_messages() {
	global $pagenow;
	if ( $pagenow !== 'profile.php' && $pagenow !== 'user-edit.php' ) {
		return;
	}

	if ( ! empty( $_GET['rest_oauth2_revoked'] ) ) {
		echo '<div id="message" class="updated"><p>' . __( 'Token revoked.', 'wp_rest_oauth2' ) . '</p></div>';
	}
	if ( ! empty( $_GET['rest_oauth2_revocation_failed'] ) ) {
		echo '<div id="message" class="error"><p>' . __( 'Unable to revoke token.', 'wp_rest_oauth2' ) . '</p></div>';
	}
	if ( ! empty( $_GET['rest_oauth2_token_not_exists'] ) ) {
		echo '<div id="message" class="error"><p>' . __( 'Token not found.', 'wp_rest_oauth2' ) . '</p></div>';
	}
}

function rest_oauth2_profile_save( $user_id ) {
	if ( empty( $_POST['rest_oauth2_revoke'] ) ) {
		return;
	}

	$post_id = intval( $_POST['rest_oauth2_revoke'] );
	$token = WP_REST_OAuth2_Access_Token::get_token_by_id( $post_id );

	// Check that the request is valid and the user has access.
	if ( is_wp_error( $token ) || 
		( !current_user_can( 'edit_post', $post_id ) && $token[ 'user_id' ] !== get_current_user_id() ) ) {
		wp_die(
			'<h1>' . __( 'Cheatin&#8217; uh?', 'wp_rest_oauth2' ) . '</h1>' .
			'<p>' . __( 'You are not allowed to edit this token or the token does not exist.', 'wp_rest_oauth2' ) . '</p>',
			403
		);
	}

	$result = WP_REST_OAuth2_Access_Token::revoke_token( $token[ 'token' ] );

	if ( is_wp_error( $result ) || $result === false ) {
		$redirect = add_query_arg( 'rest_oauth2_revocation_failed', true, get_edit_user_link( $user_id ) );
	}
	else {
		$redirect = add_query_arg( 'rest_oauth2_revoked', $post_id, get_edit_user_link( $user_id ) );
	}

	wp_redirect($redirect);
	exit;
}
