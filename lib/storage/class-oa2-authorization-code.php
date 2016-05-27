<?php
/**
 * OAuth 2.0 Authorization code model.
 */
abstract class OA2_Authorization_Code {

  const AUTHORIZATION_CODE_LENGTH = 32;

  /**
   * Retrieve an authorization code's data
   *
   * @param string $code
   * @return array|false Code data on success, false otherwise
   */
  public static function get_authorization_code( $code ) {
	return get_option( 'wp_rest_oauth2_code_' . self::hash_code( $code ) );
  }

  /**
   * Generate a new code
   *
   * @param int $client_id
   * @param int $user_id
   * @param int $redirect_uri
   * @param int $expires Expiration as timestamp, defaults to 30s from current time.
   * @param string $scope Comma separated list of allowed capabilities
   * @return WP_Error|array OAuth token data on success, error otherwise
   */
  public static function generate_code( $client_id, $user_id, $redirect_uri, $expires = 0, $scope = '*' ) {
	if ( intval( $expires ) === 0 ) {
	  $expires = time() + 30; // Authorization codes will always expire.
	}

	// Generate code
	$code = apply_filters( 'wp_rest_oauth2_authorization_code', wp_generate_password( self::AUTHORIZATION_CODE_LENGTH, false ) );

	// Check that client exists
	$consumer = OA2_Client::get_by_client_id( $client_id );
	if ( is_wp_error( $consumer ) ) {
	  return $consumer;
	}

	// Check that redirect_uri matches
	if( !empty( $consumer->redirect_uri ) &&  $redirect_uri !== $consumer->redirect_uri ) {
	  return new WP_Error( 'oauth2_redirect_uri_mismatch', __( 'The client redirect URI does not match the provided URI.', 'wp_rest_oauth2' ), array( 'status' => 400 ) );
	}

	// Hash code in order to prevent leaking from DB
	$code_hash = self::hash_code($code);

	// Setup data for filtering
	$unfiltered_data = array(
		'hash'			 => $code_hash,
		'user_id'		 => intval( $user_id ),
		'redirect_uri'	 => $redirect_uri,
		'client_id'		 => get_post_meta( $consumer->ID, 'client_id', true ),
		'expires'		 => intval( $expires ),
		'scope'			 => $scope
	);
	$data			 = apply_filters( 'wp_rest_oauth2_authorization_code_data', $unfiltered_data );

	add_option( 'wp_rest_oauth2_code_' . $code_hash , $data );

	return $code;
  }

  /**
   * Revoke an authorization code
   *
   * @param string $code
   * @return bool True, if code is successfully deleted. False on failure.
   */
  public static function revoke_code( $code ) {
	return delete_option( 'wp_rest_oauth2_code_' . self::hash_code( $code ) );
  }

  /**
   * Delete expired codes
   */
  public static function expire_codes() {
	$results = $wpdb->get_col( "SELECT option_value FROM {$wpdb->options} WHERE option_name LIKE 'wp_rest_oauth2_code_%'", 0 );
	$codes	 = array_map( 'unserialize', $results );

	foreach ( $codes as $code ) {
	  if ( $code[ 'expires' ] >= time() ) {
		delete_option( 'wp_rest_oauth2_code_' . $code[ 'hash' ] );
	  }
	}
  }

  /**
   * Hash a code for DB
   *
   * @param string $code
   * @return string Hash
   */
  public static function hash_code( $code ) {
	$code_hash = wp_hash($code);
	return $code_hash;
  }

}
