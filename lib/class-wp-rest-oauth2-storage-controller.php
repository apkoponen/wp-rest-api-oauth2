<?php
/**
 * OAuth2 Storage Controller
 * This class is a simple storage class that utilizes $wpdb and WordPress's options API
 */
class WP_REST_OAuth2_Storage_Controller extends WP_REST_OAuth2_Server {

  /**
   * Checks to see if the given client is registered and will return true is found and false is not found.
   *
   * @todo Once we have the structure in place we can finish writing this method
   *
   * @param  string $client_id [description]
   * @return Bool          [description]
   */
  static function validateClient( $client_id ) {
	$consumer = WP_REST_OAuth2_Client::get_by_client_id( $client_id );

	if ( is_wp_error( $consumer ) ) {
	  return false;
	}

	return true;
  }

   /**
   * Checks to see if the given client is registered and will return true is found and secret matches, false is not found.
   *
   * @todo Once we have the structure in place we can finish writing this method
   *
   * @param  string $client_id
   * @param  string $client_secret
   * @return Bool True on successfull authentication, false otherwise
   */
  static function authenticateClient( $client_id,  $client_secret) {
	$consumer = WP_REST_OAuth2_Client::get_by_client_id( $client_id );

	if ( is_wp_error( $consumer ) ||
		  $consumer->client_secret !== $client_secret ) {
	  return false;
	}

	return true;
  }

}