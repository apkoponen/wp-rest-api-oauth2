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
   * @return array|\WP_Error Token data on success, WP_Error otherwise
   */
  public static function get_token( $oauth_token ) {
	$class = function_exists( 'get_called_class' ) ? get_called_class() : self::get_called_class();
	$post_id = $class::get_post_id_by_title( $oauth_token );

	if ( empty( $post_id ) ) {
	  return new WP_Error( 'rest_oauth2_token_not_exists', __( 'Token does not exist.', 'rest_oauth2' ), array( 'status' => 401 ) );
	}

	$token = $class::get_token_by_id( $post_id );

	return $token;
  }

  /**
   * Retrieve token based on post ID
   *
   * @param type $post_id
   * @return array|\WP_Error Key-value array on success, WP_Error otherwise.
   */
  public static function get_token_by_id( $post_id ) {
	$class = function_exists( 'get_called_class' ) ? get_called_class() : self::get_called_class();
	$token_type = $class::get_type();

	$post = get_post( $post_id );

	if ( !$class::post_is_token( $post ) ) {
	  return new WP_Error( 'rest_oauth2_token_id_not_valid', __( 'Token with given post ID does not exist.', 'rest_oauth2' ), array( 'status' => 401 ) );
	}

	$expires = intval( get_post_meta( $post_id, 'expires', true ) ); // 0 on false

	if( $expires > 0 && $expires < time() ) {
	  $class::revoke_token_by_id( $post_id );
	  return new WP_Error( 'rest_oauth2_token_expired', __( 'Token has expired.', 'rest_oauth2' ), array( 'status' => 401 ) );
	}

	// Populate token data
	$token_data = array(
		'token'		 => $post->post_title,
		'type'		 => $token_type,
		'post_id'	 => $post->ID,
		'client_id'	 => get_post_meta( $post_id, 'client_id', true ),
		'user_id'	 => $post->post_author,
		'expires'	 => $expires,
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
	$token_type = $class::get_type();

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
	  return new WP_Error( 'rest_oauth2_token_save_failed', __( 'Could not save new token.', 'rest_oauth2' ), array( 'status' => 401 ) );
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
   * @param string $oauth_token Access token
   * @return array|false|WP_Post|WP_Error WP_Error or False on failure.
   */
  public static function revoke_token( $oauth_token ) {
	$class = function_exists( 'get_called_class' ) ? get_called_class() : self::get_called_class();
	$post_id = $class::get_post_id_by_title( $oauth_token );

	if ( empty( $post_id ) ) {
	  return new WP_Error( 'rest_oauth2_token_not_exists', __( 'Token does not exist.', 'rest_oauth2' ), array( 'status' => 401 ) );
	}

	$result = $class::revoke_token_by_id( $post_id );

	return $result;
  }

  /**
   * Revokes a token by ID.
   *
   * @param type $post_id
   * @return array|false|WP_Post|WP_Error WP_Error or False on failure.
   */
  public static function revoke_token_by_id( $post_id ) {
	$class = function_exists( 'get_called_class' ) ? get_called_class() : self::get_called_class();

	$post = get_post( $post_id );

	if ( !$class::post_is_token( $post ) ) {
	  return new WP_Error( 'rest_oauth2_token_id_not_valid', __( 'Token with given post ID does not exist.', 'rest_oauth2' ), array( 'status' => 401 ) );
	}

	return wp_delete_post( $post_id, true );
  }

  /**
   * Check if given post object is a valid token.
   *
   * @param WP_Post $post
   * @return boolean
   */
  public static function post_is_token( $post ) {
	$class = function_exists( 'get_called_class' ) ? get_called_class() : self::get_called_class();
	$token_type = $class::get_type();

	if ( empty( $post ) || $post->post_type !== 'oauth2_' . $token_type . '_token' ) {
	  return false;
	}
	return true;
  }

  /**
   * Return post id based on title
   *
   * @global type $wpdb
   * @param string $post_title
   * @return int|null Post ID, or null on failure
   */
  public static function get_post_id_by_title( $post_title ) {
	global $wpdb;

	$query	 = $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = '%s' LIMIT 1", $post_title );
	return $wpdb->get_var( $query );
  }

  /**
  * Get tokens
  *
  * @param array $additional_args WP_Query args
  * @return \WP_Query
  */
  public static function get_tokens_query( $additional_args = array() ) {
	$class = function_exists( 'get_called_class' ) ? get_called_class() : self::get_called_class();
	$token_type = $class::get_type();

	$defaults = array(
		'post_type'		 => 'oauth2_' . $token_type . '_token',
		'post_status'	 => 'any',
		'posts_per_page' => -1
	);

	$args = wp_parse_args( $additional_args, $defaults );

	return new WP_Query( $args );
  }

  /**
   * Get clients
   *
   * @param array $additional_args WP_Query args
   * @return array Array of WP_Posts
   */
  public static function get_tokens( $additional_args = array() ) {
	$query = self::get_tokens_query( $additional_args );

	return $query->posts;
  }
}

