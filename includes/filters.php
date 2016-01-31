<?php
/**
 * OAuth2 Server Core Filters
 *
 * @author Justin Greer <justin@justin-greer.com>
 * @package OAuth2 Server
 */

//add_filter('oauth2_server_routes', 'oauth2_server_routes_test');
function oauth2_server_routes_test( $routes ) {
  $routes[] = 'test';

  return $routes;
}