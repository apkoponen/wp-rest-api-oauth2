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

// Load helpers
include_once( dirname( __FILE__ ) . '/lib/helpers/class-wp-rest-header-helper.php' );
include_once( dirname( __FILE__ ) . '/lib/helpers/class-wp-rest-error-helper.php' );

// Load core classes
include_once( dirname( __FILE__ ) . '/lib/storage/class-wp-rest-oauth-client.php' );
include_once( dirname( __FILE__ ) . '/lib/storage/class-wp-rest-oauth2-client.php' );
include_once( dirname( __FILE__ ) . '/lib/storage/class-wp-rest-oauth2-token.php' );
include_once( dirname( __FILE__ ) . '/lib/storage/class-wp-rest-oauth2-access-token.php' );
include_once( dirname( __FILE__ ) . '/lib/storage/class-wp-rest-oauth2-refresh-token.php' );
include_once( dirname( __FILE__ ) . '/lib/storage/class-wp-rest-oauth2-authorization-code.php' );

// Initiate admin
include_once( dirname( __FILE__ ) . '/admin.php' );

// Initiate the server
add_action( 'rest_api_init', array( 'WP_REST_OAuth2_Server', 'register_routes' ) );
add_action( 'init', array( 'WP_REST_OAuth2_Server', 'register_storage' ) );
add_filter( 'rest_index', array( 'WP_REST_OAuth2_Server', 'add_routes_to_index' ) );
add_action( 'rest_api_init', array( 'WP_REST_OAuth2_Server', 'init_autheticator' ) );


/**
 * OAuth2 Rest Server Main Class.
 *
 * This class is used to
 */
class WP_REST_OAuth2_Server {

  public static $authenticator = null;

  /**
   * Registers routes needed for the OAuth2 Server
   *
   * @todo  Deturmine if we really want to call the args parameter when registering the route.
   * The REST API does not return the correct format needed if we run them through the args.
   * Currently validation is done in the authorize controller but there may be be better way of doing it.
   * 
   */
  static function register_routes() {
	require_once dirname( __FILE__ ) . '/lib/class-wp-rest-oauth2-authorize-controller.php';
	require_once dirname( __FILE__ ) . '/lib/class-wp-rest-oauth2-token-controller.php';
	require_once dirname( __FILE__ ) . '/lib/class-wp-rest-oauth2-response-controller.php';
	require_once dirname( __FILE__ ) . '/lib/class-wp-rest-oauth2-storage-controller.php';

	// Registers the authorize endpoint
	register_rest_route( 'oauth2/v1', '/authorize', array(
		'methods'	 => 'GET',
		'callback'	 => array( 'WP_REST_OAuth2_Authorize_Controller', 'validate' ),
		/*'args'		 => array(
			'client_id'		 => array(
				'required' => true
			),
			'response_type'	 => array(
				'required' => true,
			),
			'redirect_uri'	 => array(
				'required' => true,
			),
			'scope'			 => array(
				'required' => false
			),
			'state'			 => array(
				'required' => false,
			)
		)*/
	) );

	// Registers the token endpoint
	register_rest_route( 'oauth2/v1', '/token', array(
		'methods'	 => 'POST',
		'callback'	 => array( 'WP_REST_OAuth2_Token_Controller', 'validate' ),
		/*'args'		 => array(
			'client_id'		 => array(
				'required' => true,
			),
			'client_secret'	  => array(
				'required' => true,
			),
			'grant_type'	 => array(
				'required' => true,
			),
			'redirect_uri'	 => array(
				'required' => true,
			)
		)*/
	) );
  }

  /* Register routes to authentication on rest index
   *
   * @param object $response_object WP_REST_Response Object
   * @return object Filtered WP_REST_Response object
   */
  static function add_routes_to_index( $response_object ) {
	if ( empty( $response_object->data[ 'authentication' ] ) ) {
	  $response_object->data[ 'authentication' ] = array();
	}

	$response_object->data[ 'authentication' ][ 'oauth2' ] = array(
		'authorize'	 => get_rest_url(null, '/oauth2/v1/authorize' ),
		'token'	 => get_rest_url(null, '/oauth2/v1/token' ),
		'version'	 => '0.1',
	);
	return $response_object;
  }

  /**
   * Register the CPTs needed for storage.
   */
  static function register_storage() {
	register_post_type( 'json_consumer', array(
		'labels' => array(
			'name' => __( 'Consumers', 'wp-oauth2' ),
			'singular_name' => __( 'Consumer', 'wp-oauth2' ),
		),
		'public' => false,
		'hierarchical' => false,
		'rewrite' => false,
		'delete_with_user' => true,
		'query_var' => false,
	) );

	register_post_type( 'oauth2_access_token', array(
		'labels' => array(
			'name' => __( 'Access tokens', 'wp-oauth2' ),
			'singular_name' => __( 'Access token', 'wp-oauth2' ),
		),
		'public' => false,
		'hierarchical' => false,
		'rewrite' => false,
		'delete_with_user' => true,
		'query_var' => false,
	) );

	register_post_type( 'oauth2_refresh_token', array(
		'labels' => array(
			'name' => __( 'Refresh tokens', 'wp-oauth2' ),
			'singular_name' => __( 'Refresh token', 'wp-oauth2' ),
		),
		'public' => false,
		'hierarchical' => false,
		'rewrite' => false,
		'delete_with_user' => true,
		'query_var' => false,
	) );
  }

  /**
   * Setup the authenticator.
   */
  static function init_autheticator() {
	include_once( dirname( __FILE__ ) . '/lib/class-wp-rest-oauth2-authenticator.php' );

	self::$authenticator = new WP_REST_OAuth2_Authenticator();
  }
}
