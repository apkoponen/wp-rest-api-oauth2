<?php
/**
 * Plugin Name: OAuth2 Server
 * Plugin URI: https://wp-oauth.com
 * Description: Simple OAuth2 Server for WordPress.
 * Version: 1.0.0d
 * Author: Justin Greer
 * Author URIL http://justin-greer.com
 * License: GPL3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Domain Path: /languages
 * Text Domain: oauth2-server
 *
 * OAuth2 Server is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with OAuth2 Server.
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/** Load what needs to be loaded before anything */
require_once( dirname( __FILE__ ) . '/includes/filters.php');

add_filter( 'template_redirect', 'oauth2_server_intercept', 1);

/**
 * Register our rewrite rules for the API on INT
 */
function oauth2_server_init() {
  oauth2_server_register_rewrites();

  global $wp;
  $qvars = apply_filters('oauth2_server_qvars', array('oauth2') );
  foreach( $qvars as $var ){
    $wp->add_query_var( $var ); 
  }
}
add_action( 'init', 'oauth2_server_init' );

/**
 * OAuth2 Server Rewrite
 * @param  [type] $rules [description]
 * @return [type]        [description]
 */
function oauth2_server_register_rewrites() {
  add_rewrite_rule('^oauth2/(.+)/?', 'index.php?oauth2=$matches[1]', 'top');
}

/**
 * OAuth2 Server API looks for calls being made to the OAuth2 Server and redirects them as needed
 * @return [type] [description]
 */
function oauth2_server_intercept() {
  global $wp_query;
  $qvars = apply_filters('oauth2_server_qvars', array('oauth2') );
  
  foreach( $qvars as $var ){
    if ( $wp_query->get( $var ) ){
      // Include the main OAuth2 Server API
      require_once( dirname(__FILE__) . '/includes/class-oauth2-server.php');
      $server = new OAuth2_Server();
    }
  }
}

/**
 * Register Consumer Post Type
 * @return [type] [description]
 */
function oauth2_server_setup_post_type() {
  register_post_type( 'oauth_consumer', array(
    'labels' => array(
      'name' => __( 'Consumer', 'oauth2-server' ),
      'singular_name' => __( 'Consumers', 'oauth2-server' ),
    ),
    'public' => false,
    'hierarchical' => false,
    'rewrite' => false,
    'delete_with_user' => true,
    'query_var' => false,
  ) );
}
add_action( 'init', 'oauth2_server_setup_post_type' );


///////////////////////////////////////////////////////////////////////////////
///
/// ACTIVATION / DEACTIVATION
///
///////////////////////////////////////////////////////////////////////////////

/** Activation */
register_activation_hook( __FILE__, 'oauth2_server_activation' );
function oauth2_server_activation() {
  if ( function_exists( 'is_multisite' ) && is_multisite() && $network_wide ) {
    $mu_blogs = wp_get_sites();
    foreach ( $mu_blogs as $mu_blog ) {
      switch_to_blog( $mu_blog['blog_id'] );
      oauth2_server_register_rewrites();
      flush_rewrite_rules();
    }
    restore_current_blog();
  } else {
    oauth2_server_register_rewrites();
    flush_rewrite_rules();
  }
}

/** Deactivation */
register_activation_hook( __FILE__, 'oauth2_server_deactivation' );
function oauth2_server_deactivation(){
  if ( function_exists( 'is_multisite' ) && is_multisite() && $network_wide ) {
    $mu_blogs = wp_get_sites();
    foreach ( $mu_blogs as $mu_blog ) {
      switch_to_blog( $mu_blog['blog_id'] );
      flush_rewrite_rules();
    }
    restore_current_blog();
  } else {
    flush_rewrite_rules();
  }
}