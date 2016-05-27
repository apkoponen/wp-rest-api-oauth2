<?php
/**
 * OAuth 2.0 Refresh Token model.
 */
class OA2_Refresh_Token extends OA2_Token {
	/**
	 * Get the token type.
	 *
	 * @return string
	 */
	protected static function get_type() {
		return 'refresh';
	}
}

