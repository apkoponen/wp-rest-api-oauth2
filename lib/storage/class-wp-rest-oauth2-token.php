<?php

abstract class WP_REST_OAuth2_Token {

  const TOKEN_KEY_LENGTH = 32;
  
  /**
   * Get the token type.
   *
   * Must be overridden in subclass.
   *
   * @return string
   */
  protected static function get_type() {
	  return new WP_Error( 'json_oauth2_token_missing_type', __( 'Overridden class must implement get_type', 'rest_oauth2' ) );
  }

  /**
   * Retrieve a token's data
   *
   * @param string $oauth_token Token
   * @return array|null Token data on success, null otherwise
   */
  public static function get_token( $oauth_token ) {
	$post_id = self::get_post_id_by_title( $oauth_token );

	if ( empty( $post_id ) ) {
	  return null;
	}

	$token = self::get_token_by_id( $post_id );

	return $token;
  }

  /**
   * Retrieve token based on post id
   *
   * @param type $post_id
   */
  public static function get_token_by_id( $post_id ) {
	$class = function_exists( 'get_called_class' ) ? get_called_class() : self::get_called_class();
	$token_type = call_user_func( array( $class, 'get_type' ) );

	$post = get_post( $post_id );

	if ( empty( $post ) || $post->post_type !== 'oauth2_' . $token_type . '_token' ) {
	  return null;
	}

	// Populate token data
	$token_data = array(
		'token'		 => $post->post_title,
		'type'		 => $token_type,
		'post_id'	 => $post->ID,
		'client_id'	 => get_post_meta( $post_id, 'client_id', true ),
		'user_id'	 => $post->post_author,
		'expires'	 => intval( get_post_meta( $post_id, 'expires', true ) ), // 0 on false
		'scope'		 => get_post_meta( $post_id, 'scope', true )
	);

	return $token_data;
  }

  /**
   * Generate a new token
   *
   * @param int $client_id
   * @param int $user_id
   * @param int $expires
   * @param string $scope Comma separated list of allowed capabilities
   * @return WP_Error|array OAuth token data on success, error otherwise
   */
  public static function generate_token( $client_id, $user_id, $expires = 0, $scope = '*' ) {
	$class = function_exists( 'get_called_class' ) ? get_called_class() : self::get_called_class();
	$token_type = call_user_func( array( $class, 'get_type' ) );

	// Issue access token
	$token	 = apply_filters( 'json_oauth2_' . $token_type . '_token', wp_generate_password( self::TOKEN_KEY_LENGTH, false ) );

	// Check that client exists
	$consumer = WP_REST_OAuth2_Client::get_by_client_id( $client_id );
	if( is_wp_error( $consumer ) ) {
	  return $consumer;
	}

	// Setup data for filtering
	$unfiltered_data	 = array(
		'token'		 => $token,
		'type'		 => $token_type,
		'user_id'	 => intval( $user_id ),
		'client_id'	 => get_post_meta( $consumer->ID, 'client_id', true ),
		'expires'	 => intval( $expires ),
		'scope'		 => $scope
	);
	$data	 = apply_filters( 'json_oauth2_' . $token_type . '_token_data', $unfiltered_data );

	// Insert token to DB
	$new_id = wp_insert_post( array(
		'post_title' => $data['token'],
		'post_type' => 'oauth2_' . $data['type'] . '_token',
		'post_author' => $data['user_id']
	));

	if( empty( $new_id ) || is_wp_error( $new_id ) ) {
	  return new WP_Error( 'json_oauth2_token_save_failed', __( 'Could not save new token.', 'rest_oauth2' ), array( 'status' => 401 ) );
	}

	// Add metas
	$metas = array('client_id', 'expires', 'scope');
	foreach( $metas as $meta ) {
	  add_post_meta($new_id, $meta, $data[$meta]);
	}

	return $data;
  }


  /**
   * Revoke a token
   *
   * @param string $token Access token
   * @return array|false|WP_Post|WP_Error WP_Error or False on failure.
   */
  public static function revoke_token( $token ) {
	$class = function_exists( 'get_called_class' ) ? get_called_class() : self::get_called_class();
	$token_type = call_user_func( array( $class, 'get_type' ) );

	$data = self::get_token( $token );

	if ( empty( $data ) ) {
	  return new WP_Error( 'json_oauth2_invalid_token', __( 'The ' . $token_type . ' token does not exist', 'rest_oauth1' ), array( 'status' => 401 ) );
	}

	return wp_delete_post( $data['post_id'], true );
  }

  /**
   * Return post id based on title
   *
   * @global type $wpdb
   * @return int|null Post ID, or null on failure
   */
  public static function get_post_id_by_title( $post_title ) {
	global $wpdb;

	$query	 = $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = '%s'", $post_title );
	return $wpdb->get_var( $query );
  }

}

