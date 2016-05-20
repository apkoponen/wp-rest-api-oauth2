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

  /**
   * @var null|array Null if not authenticated using token, otherwise the token used for authentication.
   */
  protected $authorized_token = null;

  public function __construct() {
	add_filter( 'determine_current_user', array( $this, 'authenticate' ) );
	add_filter( 'rest_authentication_errors', array( $this, 'get_authentication_errors' ) );
	add_action( 'init', array( $this, 'force_reauthentication' ), 100 );
	// Add scope limiting with the highest maximum priority.
	add_action( 'user_has_cap', array( $this, 'limit_to_scope_caps' ), PHP_INT_MAX, 4 );
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

	// Check if token is in request
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
	$this->authorized_token = $token;
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

  /**
   * A filter for user_has_cap that limits the authentication to the scope capabilities.
   *
   * This is currently the only way to have limited scopes in WordPress. However, it has limitations, as we cannot
   * guarantee that the request is made in "the context of the current user". If a plugin is checking the capabilities
   * of the user that is same user that was authorized with the token in a REST API request, the capability information
   * is wrong. This would be a problem e.g. if has plugin is offers an endpoint for listing the capabilities of all
   * users and a request to access the endpoint is made with an access token that has a limited scope.
   *
   * @param array   $allcaps An array of all the user's capabilities.
   * @param array   $caps    Actual capabilities for meta capability.
   * @param array   $args    Optional parameters passed to has_cap(), typically object ID.
   * @param WP_User $user    The user object.
   * @return array An array of capabilities filtered by the token
   */
  public function limit_to_scope_caps( $allcaps, $caps, $args, $user ) {
	// Filter only if token authorization has been done, the token id matches the token user_id,
	// and token has limited scope.
	if ( !empty( $this->auth_status ) && !empty( $this->authorized_token ) &&
			$user->ID === absint($this->authorized_token[ 'user_id' ]) &&
			$this->authorized_token[ 'scope' ] !== WP_REST_OAuth2_Scope_Helper::get_all_caps_scope() ) {
	  $scope_capabilities = WP_REST_OAuth2_Scope_Helper::get_scope_capabilities( $this->authorized_token[ 'scope' ] );

	  // Only allow capabilities that are in scope and are allowed for user
	  $allowed_capabilities = [];
	  foreach($scope_capabilities as $capability) {
		$allowed_capabilities[$capability] = !empty( $allcaps[$capability] );
	  }

	  return $allowed_capabilities;
	}

	return $allcaps;
  }

}
