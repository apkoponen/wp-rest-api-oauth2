<?php
/**
 * Controller for the /token/ -endpoint
 */
class WP_REST_OAuth2_Token_Controller extends WP_REST_OAuth2_Server {

  // Validate Request
  static function validate ( WP_REST_Request $request ) {
	if ( !is_ssl() AND ( defined( 'WP_REST_OAUTH2_TEST_MODE' ) && !WP_REST_OAUTH2_TEST_MODE ) ) {
	  return new WP_Error( 'SSL is required' );
	}

	// Check if required params exist
	$required_params = array( 'client_id', 'client_secret', 'grant_type' );

	$required_missing = false;
	$request_body_params = $request->get_body_params();
	foreach( $required_params as $required_param ) {
	  if ( empty( $request_body_params[ $required_param ] ) ) {
		$required_missing = true;
	  }
	}

	if ( $required_missing ) {
	  $error = WP_REST_OAuth2_Error_Helper::get_error( 'invalid_request' );

	  return new WP_REST_OAuth2_Response_Controller( $error );
	}

	// Check that client credentials are valid
	// We may be able to move this up in the first check as well
	if ( !WP_REST_OAuth2_Storage_Controller::authenticateClient( $request_body_params[ 'client_id' ], $request_body_params[ 'client_secret' ] ) ) {
	  $error = WP_REST_OAuth2_Error_Helper::get_error( 'invalid_request' );

	  return new WP_REST_OAuth2_Response_Controller( $error );
	}

	$supported_grant_types = apply_filters( 'wp_rest_oauth2_grant_types', array( 'authorization_code', 'refresh_token' ) );

	if ( !in_array( $request_body_params[ 'grant_type' ], $supported_grant_types ) ) {
	  $error = WP_REST_OAuth2_Error_Helper::get_error( 'unsupported_grant_type' );
	  return new WP_REST_OAuth2_Response_Controller( $error );
	}

	$class = function_exists( 'get_called_class' ) ? get_called_class() : self::get_called_class();

	switch( $request_body_params[ 'grant_type' ] ) {
	  case 'authorization_code':
		return $class::handleAuthorizationCode( $request );
	  case 'refresh_token':
		return $class::handleRefreshToken( $request );
	  default:
		return apply_filters( 'wp_rest_oauth2_grant_type_' . $request_body_params[ 'grant_type' ], null, $request );
	}
  }

  /**
   * Handle grant_type 'authorization_code'
   *
   * @param WP_REST_Request $request
   * @return \WP_REST_OAuth2_Response_Controller
   */
  static function handleAuthorizationCode ( WP_REST_Request $request ) {
	$request_body_params = $request->get_body_params();

	// Check that redirect_uri is set
	if ( empty( $request_body_params[ 'redirect_uri' ] ) ) {
	  $error = WP_REST_OAuth2_Error_Helper::get_error( 'invalid_request' );
	  return new WP_REST_OAuth2_Response_Controller( $error );
	}

	$code = WP_REST_OAuth2_Authorization_Code::get_authorization_code( $request_body_params[ 'code' ] );

	// Authorization code MUST exist
	if ( empty( $code ) ) {
	  $error = WP_REST_OAuth2_Error_Helper::get_error( 'invalid_request' );
	  return new WP_REST_OAuth2_Response_Controller( $error );
	}

	// Authorization code redirect URI and client MUST match, and the code should not have expired
	$is_valid_redirect_uri = !empty( $code[ 'redirect_uri' ] ) && $code[ 'redirect_uri' ] === $request_body_params[ 'redirect_uri' ];
	$is_valid_client = $code[ 'client_id' ] === $request_body_params[ 'client_id' ];
	$code_has_expired = $code[ 'expires' ] < time();
	if ( !$is_valid_redirect_uri || !$is_valid_client || $code_has_expired ) {
	  $error = WP_REST_OAuth2_Error_Helper::get_error( 'invalid_request' );

	  return new WP_REST_OAuth2_Response_Controller( $error );
	}

	// Codes are single use, remove it
	if ( !WP_REST_OAuth2_Authorization_Code::revoke_code( $request_body_params[ 'code' ] ) ) {
	  $error = WP_REST_OAuth2_Error_Helper::get_error( 'server_error' );

	  return new WP_REST_OAuth2_Response_Controller( $error );
	}

	// Store authorization code hash to access token (for possible revocation).
	$extra_metas = array(
		'authorization_code' => $code[ 'hash' ]
	);
	$access_token = WP_REST_OAuth2_Access_Token::generate_token( $code[ 'client_id' ], $code[ 'user_id' ], time() + MONTH_IN_SECONDS, $code[ 'scope' ], $extra_metas );

	// Store authorization code and access token hash to refresh token (for possible revocation).
	$extra_metas[ 'access_token' ] = $access_token[ 'hash' ];
	$refresh_token = WP_REST_OAuth2_Refresh_Token::generate_token( $code[ 'client_id' ], $code[ 'user_id' ], time() + YEAR_IN_SECONDS, $code[ 'scope' ], $extra_metas );

	$data = array(
		"access_token" => $access_token[ 'token' ],
		"expires_in" => MONTH_IN_SECONDS,
		"token_type" => "Bearer",
		"refresh_token" => $refresh_token[ 'token' ]
	);

	return new WP_REST_OAuth2_Response_Controller( $data );
  }

  /**
   * Handle grant_type 'refresh_token'
   *
   * @param WP_REST_Request $request
   * @return \WP_REST_OAuth2_Response_Controller
   */
  static function handleRefreshToken ( WP_REST_Request $request ) {
	$request_body_params = $request->get_body_params();
	$request_refresh_token = $request_body_params[ 'refresh_token' ];
	$refresh_token = WP_REST_OAuth2_Refresh_Token::get_token( $request_refresh_token );

	// Refresh token MUST exist
	if ( is_wp_error( $refresh_token ) ) {
	  $error = WP_REST_OAuth2_Error_Helper::get_error( 'invalid_grant' );

	  return new WP_REST_OAuth2_Response_Controller( $error );
	}

	// Refresh token client MUST match, and the code should not have expired
	$is_valid_client = $refresh_token[ 'client_id' ] === $request_body_params[ 'client_id' ];
	if ( !$is_valid_client ) {
	  $error = WP_REST_OAuth2_Error_Helper::get_error( 'invalid_request' );

	  return new WP_REST_OAuth2_Response_Controller( $error );
	}

	$code_has_expired = $refresh_token[ 'expires' ] < time();
	if ( $code_has_expired ) {
	  $error = WP_REST_OAuth2_Error_Helper::get_error( 'invalid_grant' );

	  return new WP_REST_OAuth2_Response_Controller( $error );
	}

	// Requested scope should be a subset of the Refresh Token scope.
	$scope = $refresh_token[ 'scope' ];
	if ( isset( $request_body_params[ 'scope' ] ) ) {
	  $scope = $request_body_params[ 'scope' ];
	  if ( $refresh_token[ 'scope' ] !== WP_REST_OAuth2_Scope_Helper::get_all_caps_scope() ) {
		if ( $request_body_params[ 'scope' ] === WP_REST_OAuth2_Scope_Helper::get_all_caps_scope() ) {
		  $scope_allowed = false;
		} else {
		  $scope_allowed = true;
		  $refresh_token_scopes = WP_REST_OAuth2_Scope_Helper::get_scope_capabilities( $refresh_token[ 'scope' ] );
		  $requested_scopes = WP_REST_OAuth2_Scope_Helper::get_scope_capabilities( $request_body_params[ 'scope' ] );

		  foreach( $requested_scopes as $requested_scope ) {
			if ( !in_array( $requested_scope, $refresh_token_scopes ) ) {
			  $scope_allowed = false;
			  break;
			}
		  }
		}

		if ( !$scope_allowed ) {
		  $error = WP_REST_OAuth2_Error_Helper::get_error( 'invalid_scope' );

		  return new WP_REST_OAuth2_Response_Controller( $error );
		}
	  }
	}

	// Refresh tokens are single use, remove the token
	if ( !WP_REST_OAuth2_Refresh_Token::revoke_token( $request_refresh_token ) ) {
	  $error = WP_REST_OAuth2_Error_Helper::get_error( 'server_error' );

	  return new WP_REST_OAuth2_Response_Controller( $error );
	}

	// Store refresh token to access token (for possible revocation).
	$extra_metas = array(
		'refresh_token' => $refresh_token[ 'hash' ]
	);
	$access_token = WP_REST_OAuth2_Access_Token::generate_token( $refresh_token[ 'client_id' ], $refresh_token[ 'user_id' ], time() + MONTH_IN_SECONDS, $scope, $extra_metas );

	// Store refresh token and access token hash to refresh token (for possible revocation).
	$extra_metas[ 'access_token' ] = $access_token[ 'hash' ];
	$new_refresh_token = WP_REST_OAuth2_Refresh_Token::generate_token( $refresh_token[ 'client_id' ], $refresh_token[ 'user_id' ], time() + YEAR_IN_SECONDS, $scope, $extra_metas );

	$data = array(
		"access_token" => $access_token[ 'token' ],
		"expires_in" => MONTH_IN_SECONDS,
		"token_type" => "Bearer",
		"refresh_token" => $new_refresh_token[ 'token' ]
	);

	return new WP_REST_OAuth2_Response_Controller( $data );
  }

}
