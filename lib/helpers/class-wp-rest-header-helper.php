<?php
/**
 * Based on WP REST API - OAuth 1.0a Server (https://github.com/WP-API/OAuth1).
 * Used under GPL3 license.
 */

/**
 * Helpers for header handling
 */
class WP_REST_OAuth2_Header_Helper {

  /**
   * Parse the Authorization header into parameters
   *
   * @param string $header Authorization header value (not including "Authorization: " prefix)
   * @return array|boolean Map of parameter values, false if not an OAuth header
   */
  public static function parse_header( $header ) {
	if ( substr( $header, 0, 6 ) !== 'OAuth2 ' ) {
	  return false;
	}

	// From OAuth PHP library, used under MIT license
	$params = array();
	if ( preg_match_all( '/(oauth2_[a-z_-]*)=(:?"([^"]*)"|([^,]*))/', $header, $matches ) ) {
	  foreach ( $matches[ 1 ] as $i => $h ) {
		$params[ $h ] = urldecode( empty( $matches[ 3 ][ $i ] ) ? $matches[ 4 ][ $i ] : $matches[ 3 ][ $i ]  );
	  }
	  if ( isset( $params[ 'realm' ] ) ) {
		unset( $params[ 'realm' ] );
	  }
	}
	return $params;
  }

  /**
   * Get the authorization header
   *
   * On certain systems and configurations, the Authorization header will be
   * stripped out by the server or PHP. Typically this is then used to
   * generate `PHP_AUTH_USER`/`PHP_AUTH_PASS` but not passed on. We use
   * `getallheaders` here to try and grab it out instead.
   *
   * @return string|null Authorization header if set, null otherwise
   */
  public static function get_authorization_header() {
	if ( !empty( $_SERVER[ 'HTTP_AUTHORIZATION' ] ) ) {
	  return wp_unslash( $_SERVER[ 'HTTP_AUTHORIZATION' ] );
	}

	if ( function_exists( 'getallheaders' ) ) {
	  $headers = getallheaders();

	  // Check for the authoization header case-insensitively
	  foreach ( $headers as $key => $value ) {
		if ( strtolower( $key ) === 'authorization' ) {
		  return $value;
		}
	  }
	}

	return null;
  }

  /**
   * Get authorization parameters starting with oauth2_ from Authorization header
   *
   * @return array
   */
  public static function get_authorization_parameters() {
	$header			 = $this->get_authorization_header();
	$header_params	 = null;

	if ( !empty( $header ) ) {
	  // Trim leading spaces
	  $header = trim( $header );

	  $header_params = $this->parse_header( $header );
	}

	return $header_params;
  }

}
