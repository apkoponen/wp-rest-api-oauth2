<?php

/**
 * Based on WP REST API - OAuth 1.0a Server (https://github.com/WP-API/OAuth1).
 * Used under GPL3 license.
 */

/**
 * Helpers for header handling
 */
class WP_REST_OAuth2_Error_Helper {

  /**
   * Return an error array
   *
   * @param string $error_code
   * @param string|WP_Error $error_description
   * @return type
   */
  public static function get_error( $error_code, $error_description = '' ) {
	if ( is_wp_error( $error_description ) ) {
	  $error_description = array_pop( array_pop( $error_description->errors ) );
	} elseif ( empty( $error_description ) ) {
	  $error_description = self::get_error_description( $error_code );
	}
	$error = array(
		'error'				 => $error_code,
		'error_description'	 => $error_description
	);
	return $error;
  }

  /**
   *
   *
   * @param string $error_code
   * @return string Error description if it exists, otherwise empty string.
   */
  public static function get_error_description( $error_code ) {
	$error_descriptions	 = array(
		'invalid_request'			 => 'The request is missing a required parameter, includes an invalid parameter value, includes a parameter more than once, or is otherwise malformed.',
		'unauthorized_client'		 => 'The client is not authorized to request an authorization code using this method.',
		'access_denied'				 => 'The resource owner or authorization server denied the request.',
		'unsupported_response_type'	 => 'The authorization server does not support obtaining an authorization code using this method.',
		'invalid_scope'				 => 'The requested scope is invalid, unknown, or malformed.',
		'server_error'				 => 'The authorization server encountered an unexpected condition that prevented it from fulfilling the request.',
		'temporarily_unavailable'	 => 'The authorization server is currently unable to handle the request due to a temporary overloading or maintenance of the server.'
	);
	$error_description	 = ( isset( $error_descriptions[ $error_code ] ) ) ? isset( $error_descriptions[ $error_code ] ) : '';
	return $error_description;
  }

}
