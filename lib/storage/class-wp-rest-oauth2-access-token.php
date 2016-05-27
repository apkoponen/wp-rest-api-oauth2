<?php
/**
 * OAuth 2.0 Access Token model.
 */
class OA2_Access_Token extends OA2_Token {
	/**
	 * Get the token type.
	 *
	 * @return string
	 */
	protected static function get_type() {
		return 'access';
	}
}


