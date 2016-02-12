<?php 
/**
 * @todo 
 */
class OAuth2_Token_Controller extends OAuth2_Rest_Server {

  // Validate Request
  static function validate ( WP_REST_Request $request ) {
	$data = array(
		"access_token" => "asdfghjkllö123447",
		"expires_in" => 3600,
		"token_type" => "Bearer"
	);

	return new OAuth2_Response_Controller( $data );


    // Check if the client ID and response type is set
    if ( ! isset( $request[ 'response_type' ] ) || ! isset( $request[ 'client_id' ] ) ) {
      $error = array(
        'error' => 'invalid_request',
        'error_description' => 'The request is missing a required parameter, includes an invalid parameter value, includes a parameter more than once, or is otherwise malformed.'
        );

      return new OAuth2_Response_Controller( $error );
    }
    
    // Check id client ID is valid.
    // We may be able to move this up in the first check as well
    if ( ! OAuth2_Storage_Controller::validateClient( $request[ 'client_id' ] ) ) {
      $error = array(
        'error' => 'invalid_request',
        'error_description' => 'The request is missing a required parameter, includes an invalid parameter value, includes a parameter more than once, or is otherwise malformed.'
        );

      return new OAuth2_Response_Controller( $error );
    }

    // Response type MUST be 'code'
    if ( ! self::validateResponseType( $request[ 'response_type' ] ) ) {
      $error = WP_REST_OAuth2_Error_Helper::get_error( 'unsupported_response_type' );
      return new OAuth2_Response_Controller( $error );
    }

    $user_id = apply_filters( 'determine_current_user', false );

    if ( ! $user_id ) {
      global $wp;
      $current_url = add_query_arg( $wp->query_string . http_build_query( $request->get_params() ), '', site_url( $wp->request ) );
      wp_redirect( wp_login_url( $current_url ) ); 
      
      exit; 
    }

    $code = WP_REST_OAuth2_Authorization_Code::generate_code( $request[ 'client_id' ], $user_id, $request[ 'redirect_uri' ], time() + 30 );

	if( is_wp_error( $code ) ) {
	  $error = WP_REST_OAuth2_Error_Helper::get_error( 'invalid_request', $code);
	  
      return new OAuth2_Response_Controller( $error );
	}
	
    // if we made it this far, everything has checked out and we can begin our logged in check and authorize process.
    $data = array( 
      'code' => $code[ 'code' ]
    );

    // If the state is not null, we need to return is as well
    if ( ! is_null( self::$state ) ) {
      $data[ 'state' ] = self::$state;
    }

	$redirect_url = add_query_arg($data, $request->get_param('redirect_uri'));
	wp_redirect($redirect_url);
	exit;
    
    return new OAuth2_Response_Controller( $data );
  } 

}