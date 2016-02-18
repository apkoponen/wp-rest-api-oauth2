<?php
/**
 * OAuth 2.0 authenticator that checks access tokens and grants access based on them.
 *
 * Based on WP REST API - OAuth 1.0a Server (https://github.com/WP-API/OAuth1).
 * Used under GPL3 license.
 */
class WP_REST_OAuth2_Authenticator {

	/**
	 * Errors that occurred during authentication
	 * @var WP_Error|null|boolean True if succeeded, WP_Error if errored, null if not OAuth
	 */
	protected $auth_status = null;

	public function __construct() {
	  add_filter( 'determine_current_user', array( $this, 'authenticate' ) );
	  add_filter( 'rest_authentication_errors', array( $this, 'get_authentication_errors' ) );
	}

	/**
	 * Check OAuth2 authentication
	 *
	 * @param WP_User|null Already authenticated user (will be passed through), or null to perform OAuth authentication
	 * @return WP_User|null|WP_Error Authenticated user on success, null if no OAuth data supplied, error otherwise
	 */
	public function authenticate( $user ) {
		if ( ! empty( $user ) ) {
			return $user;
		}

		// Skip authentication for OAuth meta requests @TODO check if this applies
		if ( get_query_var( 'json_oauth_route' ) ) {
			return null;
		}

		$params = WP_REST_OAuth2_Header_Helper::get_authorization_parameters();
		if ( ! is_array( $params ) ) {
			$this->auth_status = $params;
			return null;
		}

		// Fetch user by token key
		$token = WP_REST_OAuth2_Access_Token::get_token( $params[ 'oauth2_token' ] );
		if ( is_wp_error( $token ) ) {
			$this->auth_status = $token;
			return null;
		}

		$this->auth_status = true;
		return $token[ 'user_id' ];
	}

	/**
	 * Report authentication errors to the JSON API
	 *
	 * @param WP_Error|mixed value Error from another authentication handler, null if we should handle it, or another value if not
	 * @return WP_Error|boolean|null {@see WP_JSON_Server::check_authentication}
	 */
	public function get_authentication_errors( $value ) {
		if ( $value !== null ) {
			return $value;
		}

		return $this->auth_status;
	}
}