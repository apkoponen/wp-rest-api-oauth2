<?php
/**
 * OAuth 2.0 Refresh Token model.
 */
class WP_REST_OAuth2_Refresh_Token extends WP_REST_OAuth2_Token {
	/**
	 * Get the token type.
	 *
	 * @return string
	 */
	protected static function get_type() {
		return 'refresh';
	}
}

