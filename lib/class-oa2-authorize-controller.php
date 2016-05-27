<?php

/**
 * Controller for the /authorize/ -endpoint
 */
class OA2_Authorize_Controller extends OA2_Server {

  // Validate Request
  static function validate( WP_REST_Request $request ) {
	if ( !is_ssl() && (!defined( 'WP_REST_OAUTH2_TEST_MODE' ) || !WP_REST_OAUTH2_TEST_MODE ) ) {
	  return new WP_Error( 'SSL is required' );
	}

	$request_query_params = $request->get_query_params();

	// Check that client exists
	$consumer = OA2_Client::get_by_client_id( $request_query_params[ 'client_id' ] );
	if ( is_wp_error( $consumer ) ) {
	  $error = OA2_Error_Helper::get_error( 'invalid_request' );
	  return new OA2_Response_Controller( $error );
	}

	// Check redirect_uri
	if ( empty( $request_query_params[ 'redirect_uri' ] ) ) {
	  $error = OA2_Error_Helper::get_error( 'invalid_request' );
	  return new OA2_Response_Controller( $error );
	}
	$redirect_uri = $request_query_params[ 'redirect_uri' ];
	if ( !empty( $consumer->redirect_uri ) && $redirect_uri !== $consumer->redirect_uri ) {
	  return new WP_Error( 'oauth2_redirect_uri_mismatch', __( 'The client redirect URI does not match the provided URI.', 'wp_rest_oauth2' ), array( 'status' => 400 ) );
	}

	// response_type MUST be 'code'
	if ( empty( $request_query_params[ 'response_type' ] ) ) {
	  $error = OA2_Error_Helper::get_error( 'invalid_request' );
	  self::redirect_with_data($redirect_uri, $error);
	}
	if ( $request_query_params[ 'response_type' ] !== 'code' ) {
	  $error = OA2_Error_Helper::get_error( 'unsupported_response_type' );
	  self::redirect_with_data($redirect_uri, $error);
	}

	// Validate scope
	if ( !empty( $request_query_params[ 'scope' ] ) && !OA2_Scope_Helper::validate_scope( $request_query_params[ 'scope' ] ) ) {
	  $error = OA2_Error_Helper::get_error( 'invalid_scope' );
	  self::redirect_with_data($redirect_uri, $error);
	}

	// Check if we're past authorization
	if ( empty( $request_query_params[ 'wp-submit' ] ) ) {
	  $login_url = site_url( 'wp-login.php?action=oauth2_authorize', 'https' );
	  $authorize_url = add_query_arg( array_map( 'rawurlencode', $request_query_params ), $login_url );
	  wp_safe_redirect( $authorize_url );
	  exit;
	} else {
	  // Check nonce to protect from CSRF (the login has to be active)
	  check_admin_referer( 'oauth2_authorization_' . $request_query_params[ 'redirect_uri' ] . '_' . $request_query_params[ 'client_id' ], '_oauth2_nonce' );
	  // Check if the user authorized the request
	  if ( $request_query_params[ 'wp-submit' ] !== 'authorize' ) {
		$error = OA2_Error_Helper::get_error( 'access_denied' );
		self::redirect_with_data($redirect_uri, $error);
	  }
	}

	// If nonce matches, we know the user.
	$user_id = get_current_user_id();

	// Set scope
	$scope = empty( $request_query_params[ 'scope' ] ) ? OA2_Scope_Helper::get_all_caps_scope() : $request_query_params[ 'scope' ];

	// Create authorization code
	$code = OA2_Authorization_Code::generate_code( $request_query_params[ 'client_id' ], $user_id, $request_query_params[ 'redirect_uri' ], time() + 30, $scope );

	if ( is_wp_error( $code ) ) {
	  $error = OA2_Error_Helper::get_error( 'invalid_request', $code );
	  self::redirect_with_data($redirect_uri, $error);
	}

	// if we made it this far, everything has checked out and we redirect with the code.
	$data = array(
		'code' => $code
	);

	// If the state is not empty, we need to return it as well
	if ( !empty( $request_query_params[ 'state' ] ) ) {
	  $data[ 'state' ] = $request_query_params[ 'state' ];
	}

	self::redirect_with_data($request_query_params[ 'redirect_uri' ], $data);
  }

  /**
   * Redirect using data as parameters.
   *
   * @param type $redirect_uri URI to redirect to.
   * @param type $data Data to rawurlencode and add as query args.
   */
  static public function redirect_with_data($redirect_uri, $data) {
	$urlencoded_data = array_map( 'rawurlencode', $data );
	$redirect_url = add_query_arg( $urlencoded_data, $redirect_uri );
	wp_redirect( $redirect_url );
	exit;
  }

  /**
   * Checks if required parameters
   *
   * @param  Array	  $request_query_params	  Key-value array to check.
   * @return Boolean  True if all exist
   */
  static public function required_params_exist( $request_query_params ) {
	$required_params = array( 'client_id', 'response_type', 'redirect_uri' );
	$required_missing = false;
	foreach( $required_params as $required_param ) {
	  if ( empty( $request_query_params[ $required_param ] ) ) {
		$required_missing = true;
	  }
	}
	$params_exist = !$required_missing;
	return $params_exist;
  }

}
