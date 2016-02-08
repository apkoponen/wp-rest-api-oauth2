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

add_action( 'rest_api_init', array( 'OAuth2_Rest_Server', 'register_routes' ) );
add_action( 'init', array( 'OAuth2_Rest_Server', 'register_storage' ) );

/**
 * OAuth2 Rest Server Main Class
 */
class OAuth2_Rest_Server {

  /**
   * Registers routes needed for the OAuth2 Server
   *
   * @todo  Deturmine if we really want to call the args parameter when registering the route.
   * The REST API does not return the correct format needed if we run them through the args.
   * Currently validation is done in the authorize controller but there may be be batter way of doing it.
   * 
   * @return [type] [description]
   */
  static function register_routes() {
	require_once dirname( __FILE__ ) . '/lib/class.oauth2-authorize-controller.php';
	require_once dirname( __FILE__ ) . '/lib/class.oauth2-response-controller.php';
	require_once dirname( __FILE__ ) . '/lib/class.oauth2-storage-controller.php';

	// Registers the authorize endpoint
	register_rest_route( 'oauth2/v1', '/authorize', array(
		'methods'	 => 'GET',
		'callback'	 => array( 'OAuth2_Authorize_Controller', 'validate' ),
		'args'		 => array(
			'client_id'		 => array(
				'required' => false,
			//'validate_callback' => 'oauth2_validate_authorize_request'
			),
			'response_type'	 => array(
				'required' => false,
			//'validate_callback' => array( 'OAuth2_Authorize_Controller', 'validateResponseType' )
			),
			'redirect_uri'	 => array(
				'required' => false,
			//'validate_callback' => 'oauth2_validate_redirect_uri'
			),
			'scope'			 => array(
				'required' => false
			),
			'state'			 => array(
				'required' => false,
			//'validate_callback' => 'oauth2_set_state'
			)
		)
	) );
  }

  /**
   * Register the CPTs needed for storage.
   *
   */
  static function register_storage() {
	require_once dirname( __FILE__ ) . '/lib/class.oauth2-admin.php';

	register_post_type( 'oauth2_consumer', array(
		'labels'			 => array(
			'name'			 => __( 'Consumers', 'wp-oauth2' ),
			'singular_name'	 => __( 'Consumer', 'wp-oauth2' ),
		),
		'supports'			 => array( 'title' ),
		'public'			 => false,
		'show_ui'			 => true,
		'show_in_menu'		 => false,
		'show_in_admin_bar'	 => false,
		'hierarchical'		 => false,
		'rewrite'			 => false,
		'delete_with_user'	 => true,
		'query_var'			 => false,
	) );

	register_post_type( 'oauth2_access_token', array(
		'labels'			 => array(
			'name'			 => __( 'Access Tokens', 'wp-oauth2' ),
			'singular_name'	 => __( 'Access Token', 'wp-oauth2' ),
		),
		'supports'			 => array( 'title' ),
		'public'			 => false,
		'show_ui'			 => true,
		'show_in_menu'		 => false,
		'show_in_admin_bar'	 => false,
		'hierarchical'		 => false,
		'rewrite'			 => false,
		'delete_with_user'	 => true,
		'query_var'			 => false,
	) );

	register_post_type( 'oauth2_refresh_token', array(
		'labels'			 => array(
			'name'			 => __( 'Refresh Tokens', 'wp-oauth2' ),
			'singular_name'	 => __( 'Refresh Token', 'wp-oauth2' ),
		),
		'supports'			 => array( 'title' ),
		'public'			 => false,
		'show_ui'			 => true,
		'show_in_menu'		 => false,
		'show_in_admin_bar'	 => false,
		'hierarchical'		 => false,
		'rewrite'			 => false,
		'delete_with_user'	 => true,
		'query_var'			 => false,
	) );
  }
}
