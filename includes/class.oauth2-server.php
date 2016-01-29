<?php
/**
 * OAuth2 Server Main Class
 *
 * @author Justin Greer <justin@justin-greer.com>
 * @package OAuth2 Server
 */

class OAuth2_Server {

  /**
   * [__construct description]
   */
  function __construct() {
    $this->createFromGlobals();

    print '<pre>';
    print_r( $this->request );
    exit;
  }

  /**
   * Build the the server request using global variables and wp_query
   * @return [type] [description]
   */
  public function createFromGlobals(){
    global $wp_query;
    
    $this->request = array(
      'query' => $wp_query->query,
      'request' => $_GET,
      'post'  => $_POST,
      'server' => $_SERVER
    );
  }

  /**
   * verify the incoming request is valid
   *
   * Run meta query depending on the route
   * @param  [type] $request [description]
   * @return Boolean         [description]
   */
  function verifyRequest( $request ){}

  /**
   * Retrives access token data
   * @param  [type] $token [description]
   * @return [type]        [description]
   */
  function getAccessTokenData( $token ) {}
}