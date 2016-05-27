<?php
/**
 * Helpers for header handling.
 *
 * Based on WP REST API - OAuth 1.0a Server (https://github.com/WP-API/OAuth1).
 * Used under GPL3 license.
 */
class OA2_Header_Helper {

  /**
   * Parse the Authorization header for Bearer token
   *
   * @param string $header Authorization header value (not including "Authorization: " prefix)
   * @return string|boolean Token as a string, false if not a Bearer-header
   */
  public static function parse_header( $header ) {
	if ( substr( $header, 0, 7 ) !== 'Bearer ' ) {
	  return false;
	}

	if ( preg_match( '/Bearer (.*)/', $header, $matches ) ) {
	  return $matches[1];
	}
	return false;
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
   * Get the Bearer token from Authorization header
   *
   * @return array
   */
  public static function get_authorization_bearer() {
	$header			 = self::get_authorization_header();
	$header_params	 = null;

	if ( !empty( $header ) ) {
	  // Trim leading spaces
	  $header = trim( $header );

	  $header_params = self::parse_header( $header );
	}

	return $header_params;
  }

}
