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
	 * @param  [type] $clientID [description]
	 * @return [type]           [description]
	 */
	static function validateClient ( $clientID ) {
		return true;
	}

	/**
	 * Generates a token 
	 * @param  integer $chars The length of the token in chars
	 * @return [type]         [description]
	 */
	static function generate_token( $chars = 32 ){
		return wp_generate_password( $chars, false );
	}

	/**
	 * Sets the Auth code in the database
	 */
	function setAuthCode ( $userData = null ) {
		$code = self::generate_token();
		$expires = strtotime( '+30 second' );
		update_option( 'oauth2_code_' . $code, $userData );

		return true;
	}

}