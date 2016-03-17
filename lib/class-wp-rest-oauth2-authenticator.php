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
	add_action( 'init', array( $this, 'force_reauthentication' ) , 100 );
  }

  /**
   * Check OAuth2 authentication
   *
   * @param WP_User|null Already authenticated user (will be passed through), or null to perform OAuth authentication
   * @return WP_User|null|WP_Error Authenticated user on success, null if no OAuth data supplied, error otherwise
   */
  public function authenticate( $user ) {
	if ( !empty( $user ) ) {
	  return $user;
	}

	$bearer_token = WP_REST_OAuth2_Header_Helper::get_authorization_bearer();
	if ( empty( $bearer_token ) ) {
	  $this->auth_status = $bearer_token;
	  return null;
	}

	// Fetch user by token key
	$token = WP_REST_OAuth2_Access_Token::get_token( $bearer_token );
	if ( is_wp_error( $token ) ) {
	  $this->auth_status = $token;
	  return null;
	}

	$this->auth_status = true;
	return $token[ 'user_id' ];
  }

  /**
   * Force reauthentication after we've registered our handler
   *
   * We could have checked authentication before OAuth2 was loaded. If so, let's
   * try and reauthenticate now that OAuth is loaded.
   */
  function force_reauthentication() {
	if ( is_user_logged_in() ) {
	  // Another handler has already worked successfully, no need to
	  // reauthenticate.
	  return;
	}

	// Force reauthentication
	global $current_user;
	$current_user = null;

	wp_get_current_user();
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
