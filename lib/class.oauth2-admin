<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * Handles admin views
 */
class OAuth2_Admin {

  function __construct() {
	add_filter( 'parent_file', array($this, 'select_parent_menu') );
	add_filter( 'submenu_file',  array($this, 'select_submenu'), 10, 2 );
	add_action( 'all_admin_notices', array($this, 'custom_tabs'), 100 );
	add_action( 'admin_menu', array($this, 'register_settings_page') );
  }

  /**
   * Set correct parent menu for CPT admin.
   */
  function select_parent_menu( $parent_file ) {
	if ( $this->is_oauth2_screen() ) {
	  $parent_file = 'options-general.php';
	}
	return $parent_file;
  }

  /**
   * Set correct submenu for CPT admin.
   */
  function select_submenu( $submenu_file, $parent_file ) {
	if ( $this->is_oauth2_screen() ) {
	  $submenu_file = 'wp-oauth2-settings';
	}
	return $submenu_file;
  }

  /**
   * Check if we're on OAuth 2.0 related settings screen.
   */
  function is_oauth2_screen() {
	$current_screen		 = get_current_screen();
	$oauth2_post_types	 = array(
		'oauth2_consumer',
		'oauth2_access_token',
		'oauth2_refresh_token',
	);
	return ($current_screen->id === 'settings_page_wp-oauth2-settings') || (isset( $current_screen->post_type ) && in_array( $current_screen->post_type, $oauth2_post_types ));
  }

  /**
   * Add custom tabs to the top of all OAuth 2.0 admin views
   */
  function custom_tabs() {
	if ( $this->is_oauth2_screen() ) {
	  ?>
		<div class="wrap">
		  <h1>OAuth 2.0</h1>
		  <h2 class="nav-tab-wrapper">
			<?php
			$current_screen		 = get_current_screen();
			$tabs = array(
				'options-general.php?page=wp-oauth2-settings' => array(
					'label' => __( 'Settings', 'wp-oauth2' ),
					'is_active' => ($current_screen->id === 'settings_page_wp-oauth2-settings')
				),
				'edit.php?post_type=oauth2_consumer' => array(
					'label' => __( 'Consumers', 'wp-oauth2' ),
					'is_active' => (isset( $current_screen->post_type ) && $current_screen->post_type == 'oauth2_consumer')
				),
				'edit.php?post_type=oauth2_access_token' => array(
					'label' => __( 'Access Tokens', 'wp-oauth2' ),
					'is_active' => (isset( $current_screen->post_type ) && $current_screen->post_type == 'oauth2_access_token')
				),
				'edit.php?post_type=oauth2_refresh_token' => array(
					'label' => __( 'Refresh Tokens', 'wp-oauth2' ),
					'is_active' => (isset( $current_screen->post_type ) && $current_screen->post_type == 'oauth2_refresh_token')
				)
			);
			?>

			<?php
			foreach ( $tabs as $url => $settings ) {
			  echo '<a href="' . admin_url( $url ) . '" class="nav-tab ' . ( $settings['is_active'] ? 'nav-tab-active' : '' ) . '">' . $settings['label'] . '</a>';
			}
			?>
		  </h2>
		</div>
	  <?php
	}
  }

  /**
   * Register the settings page.
   */
  function register_settings_page() {
	add_submenu_page(
	'options-general.php', __( 'OAuth 2.0', 'wp-oauth2' ), __( 'OAuth 2.0', 'wp-oauth2' ), 'manage_options', 'wp-oauth2-settings', array($this, 'settings_page_contents') );
  }

  /**
   * Output settings page contents.
   */
  function settings_page_contents() {

  }
}
new OAuth2_Admin();
