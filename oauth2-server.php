<?php
/**
 * Plugin Name: WP REST OAuth2 Server
 * Plugin URI: http://rest.wp-oauth.com
 * Description: OAuth2 Flow for providing authorization access to WordPress REST API.
 * Version: 1.0.0
 * Author: justingreerbbi, ap.koponen
 * Requires at least: 4.4
 * License: GPL3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// Imitates the server
add_action( 'rest_api_init', array( 'WP_REST_OAuth2_Server', 'register_routes' ) );

/**
 * OAuth2 Rest Server Main Class
 */
class WP_REST_OAuth2_Server {

	/**
	 * Registers routes needed for the OAuth2 Server
	 *
	 * Note: This does not register and arguments with the routes since, the return for the errors violates the return
   * for that is required for OAuth2. 
	 * 
	 * @return [type] [description]
	 */
	static function register_routes() {
		require_once dirname(  __FILE__ ) . '/lib/class-wp-rest-oauth2-authorize-controller.php';
		require_once dirname(  __FILE__ ) . '/lib/class-wp-rest-oauth2-response-controller.php';
		require_once dirname(  __FILE__ ) . '/lib/class-wp-rest-oauth2-storage-controller.php';

		// Registers the authorize endpoint
		register_rest_route( 'oauth2/v1', '/authorize', array(
			'methods' => 'GET',
			'callback' => array( 'WP_REST_OAuth2_Authorize_Controller', 'validate' )
		) );
	}

}