<?php

/**
 * @TODO Store used access tokens in order to check reuse.
 */
class WP_REST_OAuth2_Token_Controller extends WP_REST_OAuth2_Server {

  // Validate Request
  static function validate( WP_REST_Request $request ) {

	// Check if required params exist
	$required_params = array( 'client_id', 'client_secret', 'grant_type', 'redirect_uri' );

	$required_missing = false;
	foreach ( $required_params as $required_param ) {
	  if ( empty( $request->get_param( $required_param ) ) ) {
		$required_missing = true;
	  }
	}

	if ( $required_missing ) {
	  $error = WP_REST_OAuth2_Error_Helper::get_error( 'invalid_request' );

	  return new WP_REST_OAuth2_Response_Controller( $error );
	}

	// Check that client credentials are valid
	// We may be able to move this up in the first check as well
	if ( !OAuth2_Storage_Controller::authenticateClient( $request[ 'client_id' ], $request[ 'client_secret' ] ) ) {
	  $error = WP_REST_OAuth2_Error_Helper::get_error( 'invalid_request' );

	  return new WP_REST_OAuth2_Response_Controller( $error );
	}

	$supported_grant_types = apply_filters( 'wp_rest_oauth2_grant_types', array( 'authorization_code' ) );

	if ( !in_array( $request[ 'grant_type' ], $supported_grant_types ) ) {
	  $error = WP_REST_OAuth2_Error_Helper::get_error( 'unsupported_grant_type' );
	  return new WP_REST_OAuth2_Response_Controller( $error );
	}

	$code = WP_REST_OAuth2_Authorization_Code::get_authorization_code( $request[ 'code' ] );

	// Authorization code MUST exists
	if ( empty( $code ) ) {
	  $error = WP_REST_OAuth2_Error_Helper::get_error( 'invalid_request' );

	  return new WP_REST_OAuth2_Response_Controller( $error );
	}

	// Authorization code redirect URI and client MUST match, and the code should not have expired
	$is_valid_redirect_uri = !empty( $code[ 'redirect_uri' ] ) && $code[ 'redirect_uri' ] === $request[ 'redirect_uri' ];
	$is_valid_client = $code[ 'client_id' ] === $request[ 'client_id' ];
	$code_has_expired = $code[ 'expires' ] < time();
	if ( !$is_valid_redirect_uri || !$is_valid_client || $code_has_expired ) {
	  $error = WP_REST_OAuth2_Error_Helper::get_error( 'invalid_request' );

	  return new WP_REST_OAuth2_Response_Controller( $error );
	}



	// Codes are single use, remove it
	if ( !WP_REST_OAuth2_Authorization_Code::revoke_code( $code[ 'code' ] ) ) {
	  	$error = WP_REST_OAuth2_Error_Helper::get_error( 'server_error' );
		
		return new WP_REST_OAuth2_Response_Controller( $error );
	}

	// Store authorization code to access token (for possible revocation).
	$extra_metas = array(
		'authorization_code' => $code[ 'code' ]
	);
	$expires = time() + MONTH_IN_SECONDS;
	$access_token = WP_REST_OAuth2_Access_Token::generate_token( $code[ 'client_id' ], $code[ 'user_id' ], $expires, $code[ 'scope' ], $extra_metas );

	// Store authorization code and access token to refresh token (for possible revocation).
	$extra_metas['access_token'] = $access_token[ 'token' ];
	$refresh_token = WP_REST_OAuth2_Refresh_Token::generate_token( $code[ 'client_id' ], $code[ 'user_id' ], 0, $code[ 'scope' ], $extra_metas );

	$data = array(
		"access_token"	 => $access_token[ 'token' ],
		"expires_in"	 => MONTH_IN_SECONDS,
		"token_type"	 => "Bearer",
		"refresh_token"  => $refresh_token[ 'token' ]
	);

	return new WP_REST_OAuth2_Response_Controller( $data );
  }

}
