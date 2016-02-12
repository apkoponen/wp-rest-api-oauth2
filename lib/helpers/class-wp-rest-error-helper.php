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
   * Get error code default description.
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
		'temporarily_unavailable'	 => 'The authorization server is currently unable to handle the request due to a temporary overloading or maintenance of the server.',
		'invalid_client'			 => 'Client authentication failed (e.g., unknown client, no client authentication included, or unsupported authentication method).',
		'invalid_grant'				 => 'The provided authorization grant (e.g., authorization code, resource owner credentials) or refresh token is invalid, expired, revoked, does not match the redirection URI used in the authorization request, or was issued to another client.',
		'unauthorized_client'		 => 'The authenticated client is not authorized to use this authorization grant type.',
		'unsupported_grant_type'	 => 'The authorization grant type is not supported by the authorization server.',
		'invalid_credentials'		 => 'The user credentials were incorrect.' // Not is Spec
	);
	$error_description	 = ( isset( $error_descriptions[ $error_code ] ) ) ? $error_descriptions[ $error_code ] : '';
	return $error_description;
  }

  /**
   * Get error code default status code.
   *
   * @param string $error_code
   * @return int Error status code if it exists, otherwise 400.
   */
  public static function get_error_status_code( $error_code ) {
	$error_descriptions	 = array(
		'invalid_request'			 => 400,
		'unauthorized_client'		 => 400,
		'access_denied'				 => 401,
		'unsupported_response_type'	 => 400,
		'invalid_scope'				 => 400,
		'server_error'				 => 500,
		'temporarily_unavailable'	 => 503,
		'invalid_client'			 => 401,
		'invalid_grant'				 => 400,
		'unauthorized_client'		 => 400,
		'unsupported_grant_type'	 => 400,
		'invalid_credentials'		 => 401
	);
	$error_description	 = ( isset( $error_descriptions[ $error_code ] ) ) ? isset( $error_descriptions[ $error_code ] ) : 400;
	return $error_description;
  }

}
