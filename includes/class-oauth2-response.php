<?php
/**
 * OAuth2 Server Response Class
 *
 * @todo Make $headers filterable
 *
 * @author Justin Greer <justin@justin-greer.com>
 * @package OAuth2 Server
 */

class OAuth2_Response {

  // response container
  var $response = null;

  // Response headers
  public static $headers = array(
    301 => 'Moved Permanently',
    302 => 'Found',
    400 => 'Bad Request',
    401 => '401 Unauthorized',
    402 => 'Payment Required',
    403 => 'Forbidden',
    404 => 'Not Found'
    );

  /**
   * [errorResponse description]
   * @param  [type]  $error             [description]
   * @param  [type]  $error_description [description]
   * @param  integer $error_code        [description]
   * @return [type]                     [description]
   */
  function setError($error, $error_description, $error_code=401) {
    header('HTTP/1.1 401 Unauthorized', true, $error_code);
    $errorMessage = array(
      'error' => $error,
      'error_description' => $error_description,
      'error_uri' => ''
      );
    $this->response = json_encode( $errorMessage );
  }

  /**
   * Simple Send functionality
   * @return [type] [description]
   */
  function send() {
    header('Content-Type: application/json');
    print $this->response;

    exit;
  }
}