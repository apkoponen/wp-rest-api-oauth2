<?php
login_header(
 __( 'Authorize', 'oauth' ), '', $errors
);

$current_user	 = wp_get_current_user();
$url			 = get_rest_url( null, '/oauth2/v1/authorize/', 'https' );
?>

<style>

  .login-title {
	margin-bottom: 15px;
  }

  .login-info .avatar {
	margin-right: 15px;
	margin-bottom: 15px;
	float: left;
  }

  #login form .login-info p {
	margin-bottom: 15px;
  }

  .login-scope {
	clear: both;
	margin-bottom: 15px;
  }

  .login-scope h4 {
	margin-bottom: 10px;
  }

  .login-scope ul {
	margin-bottom: 10px;
	margin-left: 1.5em;
  }

  .submit {
	clear: both;
  }

  .submit .button {
	margin-right: 10px;
	float: left;
  }

</style>

<form name="oauth2_authorize_form" id="oauth2_authorize_form" action="<?php echo esc_url( $url ); ?>" method="get">

  <h2 class="login-title"><?php echo esc_html( sprintf( __( 'Authorize %1$s', 'wp_rest_oauth2' ), $consumer->post_title ) ) ?></h2>

  <div class="login-info">

	<?php echo get_avatar( $current_user->ID, '78' ); ?>

	<p><?php
	  printf(
	  __( 'Howdy <strong>%1$s</strong>,<br/> <em>"%2$s"</em> would like to connect to <em>"%3$s"</em>.', 'wp_rest_oauth2' ), $current_user->user_login, $consumer->post_title, get_bloginfo( 'name' )
	  )
	  ?></p>
  </div>


  <div class="login-scope">
	<h4><?php _e( 'Capabilities to be granted:', 'wp_rest_oauth2' ); ?></h4>
	<ul>
	  <?php if ( $scope === OA2_Scope_Helper::get_all_caps_scope() ) : ?>
  	  <li><?php _e( 'All of you capabilities ( * ).', 'wp_rest_oauth2' ); ?></li>
		<?php
	  else:
		$capabilities = OA2_Scope_Helper::get_scope_capabilities( $scope );
		foreach ( $capabilities as $capability ):
		  $description = apply_filters('oauth2_capability_description', $capability, $capability);
		  ?>
		  <li><?php printf('%s ( %s )', $description, $capability); ?></li>
		  <?php
		endforeach;
		?>
	  <?php endif; ?>
	</ul>

	<small><?php
	  printf(
	  __( '"%1$s" will be allowed to use these capabilities on your behalf.' ), $consumer->post_title
	  )
	  ?></small>
  </div>

  <?php
  /**
   * Fires inside the lostpassword <form> tags, before the hidden fields.
   *
   * @since 2.1.0
   */
  do_action( 'oauth2_authorize_form', $consumer );
  ?>
  <p class="submit">
	<button type="submit" name="wp-submit" value="authorize" class="button button-primary button-large"><?php _e( 'Authorize', 'wp_rest_oauth2' ); ?></button>
	<button type="submit" name="wp-submit" value="cancel" class="button button-large"><?php _e( 'Cancel' ); ?></button>
  </p>

</form>

<p id="nav">
  <a href="<?php echo esc_url( wp_login_url( $url, true ) ); ?>"><?php _e( 'Switch user', 'wp_rest_oauth2' ) ?></a>
  <?php
  if ( get_option( 'users_can_register' ) ) :
	$registration_url = sprintf( '<a href="%s">%s</a>', esc_url( wp_registration_url() ), __( 'Register' ) );
	/**
	 * Filter the registration URL below the login form.
	 *
	 * @since 1.5.0
	 *
	 * @param string $registration_url Registration URL.
	 */
	echo ' | ' . apply_filters( 'register', $registration_url );
  endif;
  ?>
</p>

<?php
login_footer();
