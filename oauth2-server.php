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

add_action( 'rest_api_inti', 'oauth2_register_rest_routes' );
function oauth2_register_rest_routes(){

	register_rest_route( 'oauth2', '/authorize', array(
			'methods' => 'GET',
			'callback' => 'my_awesome_func',
	) );

	register_rest_route( 'oauth2', '/authorize', array(
			'methods' => 'GET',
			'callback' => 'my_awesome_func',
	) );
}