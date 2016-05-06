<?php 
/**
 * WP_REST_OAuth2_Authorize_Controller is used for authorizing a user using the web based Auth Code grant type.
 * 
 */
class WP_REST_OAuth2_Authorize_Controller extends WP_REST_OAuth2_Server {

 /**
   * State property
   * @var string
   */
  static public $state;

  // Validate Request
  static function validate ( WP_REST_Request $request ) {
	if ( !is_ssl() AND ( defined( 'WP_REST_OAUTH2_TEST_MODE' ) && !WP_REST_OAUTH2_TEST_MODE ) ) {
	  return new WP_Error( 'SSL is required' );
	}

    // Set state if provided
    self::setState( $request );

    // Check if required params exist
	$request_query_params = $request->get_query_params();
	$required_exist = self::requiredParamsExist( $request_query_params );

	if ( !$required_exist ) {
	  $error = WP_REST_OAuth2_Error_Helper::get_error( 'invalid_request' );
	  return new WP_REST_OAuth2_Response_Controller( $error );
	}

    // Check id client ID is valid.
    // We may be able to move this up in the first check as well
    if ( ! WP_REST_OAuth2_Storage_Controller::validateClient( $request_query_params[ 'client_id' ] ) ) {
	  $error = WP_REST_OAuth2_Error_Helper::get_error( 'invalid_request' );

      return new WP_REST_OAuth2_Response_Controller( $error );
    }

    // Response type MUST be 'code'
    if ( ! self::validateResponseType( $request_query_params[ 'response_type' ] ) ) {
      $error = WP_REST_OAuth2_Error_Helper::get_error( 'unsupported_response_type' );
      return new WP_REST_OAuth2_Response_Controller( $error );
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
		$error = WP_REST_OAuth2_Error_Helper::get_error( 'access_denied' );
		return new WP_REST_OAuth2_Response_Controller( $error );
	  }
	}

	$user_id = apply_filters( 'determine_current_user', false );
    if ( ! $user_id ) {
      global $wp;
      $current_url = add_query_arg( $wp->query_string . http_build_query( $request_query_params ), '', site_url( $wp->request ) );
      wp_redirect( wp_login_url( $current_url ) );
      exit;
    }

	// Set scope
	$scope = empty( $request_query_params['scope'] ) ? '*' : $request_query_params['scope'];

	// Create authorization code
    $code = WP_REST_OAuth2_Authorization_Code::generate_code( $request[ 'client_id' ], $user_id, $request[ 'redirect_uri' ], time() + 30, $scope );

	if( is_wp_error( $code ) ) {
	  $error = WP_REST_OAuth2_Error_Helper::get_error( 'invalid_request', $code);
      return new WP_REST_OAuth2_Response_Controller( $error );
	}

    // if we made it this far, everything has checked out and we can begin our logged in check and authorize process.
    $data = array(
      'code' => $code[ 'code' ],
    );

    // If the state is not null, we need to return is as well
    if ( ! is_null( self::$state ) ) {
      $data[ 'state' ] = self::$state;
    }

	$data = array_map( 'rawurlencode', $data );
	$redirect_url = add_query_arg( $data, $request_query_params[ 'redirect_uri' ] );
	wp_redirect( $redirect_url );
	exit;

    return new WP_REST_OAuth2_Response_Controller( $data );
  }

  /**
   * Validates the response type
   *
   * According to OAuth2 Draft, the only supported response type for an auth code flow is code.
   *
   * @see  https://tools.ietf.org/html/draft-ietf-oauth-v2-31#section-4.1.1
   *
   * @param  [type] $response [description]
   * @return [type]           [description]
   */
  static public function validateResponseType( $response ) {
    if ( 'code' !== $response ) {
      return false;
    }
    return true;
  }

  /**
   * Checks if required parameters
   *
   * @param  Array	  $request_query_params	  Key-value array to check.
   * @return Boolean  True if all exist
   */
  static public function requiredParamsExist( $request_query_params ) {
    $required_params = array('client_id', 'response_type', 'redirect_uri');
	$required_missing = false;
	foreach($required_params as $required_param) {
	  if( empty( $request_query_params[ $required_param ] ) ) {
		$required_missing = true;
	  }
	}
	$params_exist = !$required_missing;
	return $params_exist;
  }


  static public function setState ( $request ) {
    self::$state = isset( $request[ 'state' ] ) ? $request[ 'state' ] : null;
  }






}