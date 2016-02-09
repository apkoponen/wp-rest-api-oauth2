<?php

class WP_REST_OAuth2_CLI extends WP_CLI_Command {

	/**
	 * ## OPTIONS
	 *
	 * [--name=<name>]
	 * : Consumer name
	 *
	 * [--description=<description>]
	 * : Consumer description
	 */
	public function add( $_, $args ) {
		$consumer = WP_REST_OAuth2_Client::create( $args );
		WP_CLI::line( sprintf( 'Post ID: %d',     $consumer->ID ) );
		WP_CLI::line( sprintf( 'Client ID: %s',    $consumer->client_id ) );
		WP_CLI::line( sprintf( 'Client secret: %s', $consumer->client_secret ) );
	}
}