<?php
/**
 * OAuth2 Server Main Class
 *
 * @todo __construct method should handle the error messages and response. We should think about moving error 
 * response from the response class and to this class.
 *
 * @author Justin Greer <justin@justin-greer.com>
 * @package OAuth2 Server
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

require_once( dirname( __FILE__ ) . '/class-oauth2-request.php');
require_once( dirname( __FILE__ ) . '/class-oauth2-response.php');

class OAuth2_Server {
  
  /**
   * [__construct description]
   */
  function __construct() {
    
    $this->request = OAuth2_Request::createFromGlobals();
    if( !OAuth2_Request::verifyRequest( $this->request ) ) {
      $response = new OAuth2_Response();
      $response->setError('unknown_error', 'Unknown server error');
      $response->send();
    }

    exit('asd');
  }

  /**
   * Retrieves the route from the current object.
   * @return [type] [description]
   */
  function getRouteFromRequest() {}
}

/**
 * If request is AUTHORIZE
 *
 *  - check response_type
 *
 * If response type code is present the request is "authorization code". Follow the steps below:
 *
 *  1. Check if the server is using an TLS/SSL connection.
 *  2. Check if the user is logged in, redirect to login screen and start process over if not.
 *  3. Present the user a screen where they can authorize said application access to their data on the server.
 *  4. If the user allows, a code is generated with scopes and the users browser is redirected back to the client and the client asks for an access token using the code.
 */