<?php
/**
 * OAuth2 Storage Controller
 * This class is a simple storage class that utilizes $wpdb and WordPress's options API
 */
class OAuth2_Storage_Controller extends OAuth2_Rest_Server {

  /**
   * Checks to see if the given client is registered and will return true is found and false is not found.
   *
   * @todo Once we have the structure in place we can finish writing this method
   * 
   * @param  [type] $clientID [description]
   * @return [type]           [description]
   */
  static function validateClient ( $clientID ) {

    return true;
  }

}