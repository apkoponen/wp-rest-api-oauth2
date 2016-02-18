<?php
/**
 * OAuth 2.0 specific Client model.
 * 
 * Based on WP REST API - OAuth 1.0a Server (https://github.com/WP-API/OAuth1).
 * Used under GPL3 license.
 */
class WP_REST_OAuth2_Client extends WP_REST_OAuth_Client {
	const CONSUMER_KEY_LENGTH = 12;
	const CONSUMER_SECRET_LENGTH = 32;

	/**
	 * Regenerate the secret for the client.
	 *
	 * @return bool|WP_Error True on success, error otherwise.
	 */
	public function regenerate_secret() {
		$params = array(
			'meta' => array(
				'client_secret' => wp_generate_password( self::CONSUMER_SECRET_LENGTH, false ),
			),
		);

		return $this->update( $params );
	}

	/**
	 * Get the client type.
	 *
	 * @return string
	 */
	protected static function get_type() {
		return 'oauth2';
	}

	/**
	 * Return errors, because we do not use keys
	 *
	 * @param type $key
	 * @return \WP_Error
	 */
	public static function get_by_key($key) {
	  return new WP_Error( 'rest_client_keys_not_used', __( 'OAuth 2.0 does not use Client Keys. Use get_by_client_id instead.', 'wp_rest_oauth2' ) );
	}

	/**
	 * Get a client by client ID.
	 *
	 * @param string $type Client type.
	 * @param string $client_id Client ID.
	 * @return WP_Post|WP_Error
	 */
	public static function get_by_client_id( $client_id ) {
		$class = function_exists( 'get_called_class' ) ? get_called_class() : self::get_called_class();
		$type = call_user_func( array( $class, 'get_type' ) );

		$query = new WP_Query();
		$consumers = $query->query( array(
			'post_type' => 'json_consumer',
			'post_status' => 'any',
			'meta_query' => array(
				array(
					'key' => 'client_id',
					'value' => $client_id,
				),
				array(
					'key' => 'type',
					'value' => $type,
				),
			),
		) );

		if ( empty( $consumers ) || empty( $consumers[0] ) ) {
			return new WP_Error( 'json_consumer_notfound', __( 'Client ID is invalid', 'wp_rest_oauth2' ), array( 'status' => 401 ) );
		}

		return $consumers[0];
	}

	/**
	 * Get clients
	 *
	 * @param array $additional_args WP_Query args
	 * @return \WP_Query
	 */
	public static function get_clients_query( $additional_args = array() ) {
		$defaults = array(
			'post_type' => 'json_consumer',
			'post_status' => 'any',
			'meta_query' => array(
				array(
					'key' => 'type',
					'value' => 'oauth2',
				),
			)
		);

		$args = wp_parse_args( $additional_args, $defaults);
		
		return new WP_Query($args);
	}

	/**
	 * Get clients
	 *
	 * @param array $additional_args WP_Query args
	 * @return array Array of WP_Posts
	 */
	public static function get_clients( $additional_args = array() ) {
		$query = self::get_clients_query($additional_args);

		return $query->posts;
	}

	/**
	 * Add extra meta to a post.
	 *
	 * Adds the client_id and client_secret for a client to the meta on creation. Only adds
	 * them if they're not set, allowing them to be overridden for consumers
	 * with a pre-existing pair (such as via an import).
	 *
	 * @param array $meta Metadata for the post.
	 * @param array $params Parameters used to create the post.
	 * @return array Metadata to actually save.
	 */
	protected static function add_extra_meta( $meta, $params ) {
		if ( empty( $meta['client_id'] ) && empty( $meta['client_secret'] ) ) {
			$meta['client_id'] = wp_generate_password( self::CONSUMER_KEY_LENGTH, false );
			$meta['client_secret'] = wp_generate_password( self::CONSUMER_SECRET_LENGTH, false );
		}
		return parent::add_extra_meta( $meta, $params );
	}
}
