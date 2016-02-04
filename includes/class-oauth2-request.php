<?php
/**
 * OAuth2 Server Request Class
 *
 * @author Justin Greer <justin@justin-greer.com>
 * @package OAuth2 Server
 */

class OAuth2_Request {

  /**
   * [createFromGlobals description]
   * @return [type] [description]
   */
  public static function createFromGlobals(){
    global $wp_query;
    
    return array(
      'query' => $wp_query->query,
      'request' => $_GET,
      'post'  => $_POST,
      'server' => $_SERVER
    );
  }

  /**
   * The server MUST verifier the identify of the resource. Doing this is simple enough. Check 
   * the client_id and ensure that all required parameters are presented.
   *
   * Validation of any token is not done here.
   *
   * @todo Remove route check. It has been moved to the main server class.
   *
   * @see  https://tools.ietf.org/html/draft-ietf-oauth-v2-31#section-3.1
   * @return [type] [description]
   */
  static function verifyRequest( $request ) {
    
    // Routes are filterable.
    $routes = apply_filters('oauth2_server_routes', array(
      'authorize' => '_oauth2_server_authorize_controller',
      'token' => '_oauth2_server_token_controller')
    );

    // Loop through the routes and make sure the route is registered
    foreach( $request['query'] as $route) {
      if( !array_key_exists($route, $routes) )  {
        $response = new OAuth2_Response();
        $response->setError('unknown_route', 'Unknown route requested');
        $response->send();
      }
    }

    $responseType = self::getResponseType( $request );

    print_r( $responseType); exit;

    // Always default to returning false.
    return false;
  }

  /**
   * [getResponseType description]
   * @param  [type] $request [description]
   * @return [type]          [description]
   */
  static function getResponseType( $request ) {
    if(!isset($request['request']['response_type']))
      return false;

    return $request['request']['response_type'];
  }
}