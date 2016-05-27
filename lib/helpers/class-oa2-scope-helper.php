<?php

/**
 * Helpers for scope handling.
 *
 */
class OA2_Scope_Helper {

  /**
   * Validate that the given scope is a valid scope.
   *
   * @param string $scope
   * @return bool If the given $scope string is a valid scope string.
   */
  public static function validate_scope( $scope ) {
	// Run sanitize key onto each scope in the string.
	$valid_scope = implode(' ', array_map('sanitize_key', explode(' ', $scope)));

	return $scope === self::get_all_caps_scope() || $scope === $valid_scope;
  }

  /**
   * Parses the capabilities from a scope string
   *
   * @param string $scope
   * @return array Array of capabilities
   */
  public static function get_scope_capabilities( $scope ) {
	return explode(' ', $scope);
  }

  /**
   * Returns the scope for 'all capabilities'
   */
  public static function get_all_caps_scope() {
	return '*';
  }

}
