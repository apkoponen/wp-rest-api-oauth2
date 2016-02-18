<?php
/**
 * Since the OAuth2 Server needs to response a bit differently than REST API does, this class
 * over rides the REST API response controller and builds our own. 
 *
 * This class is meant to be called once for ever response needed. This means that each time 
 * a response is needed, a new instance of OAuth2_Response_Controller must be called.
 *
 * Extending the WP_REST_Response class allows us to EXTEND at a later time with out restructuring
 */
class WP_REST_OAuth2_Response_Controller extends WP_REST_Response {}