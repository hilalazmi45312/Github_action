<?php
/**
 * Promo Notice.
 *
 * @link              https://www.solwininfotech.com/
 * @since             1.0.0
 * @package           User_blocker
 */

add_action( 'plugins_loaded', 'ublk_load_plugin' );

if ( ! function_exists( 'ublk_load_plugin' ) ) {
	/**
	 * Load the Plugin.
	 */
	function ublk_load_plugin() {

		$user_blocking['promo_time'] = get_option( 'user_blocking_promo_time' );
		if ( empty( $user_blocking['promo_time'] ) ) {
			$user_blocking['promo_time'] = time();
			update_option( 'user_blocking_promo_time', $user_blocking['promo_time'] );
		}

		if ( ! empty( $user_blocking['promo_time'] ) && $user_blocking['promo_time'] > 0 && $user_blocking['promo_time'] < ( time() - ( 60 * 60 * 24 * 3 ) ) ) {
			add_action( 'admin_notices', 'ublk_promo' );
		}

		if ( isset( $_GET['user_blocking_promo'] ) && 0 == (int) $_GET['user_blocking_promo'] ) {
			update_option( 'user_blocking_promo_time', ( 0 - time() ) );
			die( 'DONE' );
		}
	}
}

if ( ! function_exists( 'ublk_promo' ) ) {

	/**
	 * Show the promo.
	 */
	function ublk_promo() {
		?>
		<div class="notice notice-success" id="user_blocking_promo" style="min-height:120px">
			<a class="user_blocking_promo-close" href="javascript:" aria-label="Dismiss this Notice">
				<span class="dashicons dashicons-dismiss"></span> <?php esc_html_e( 'Dismiss', 'user-blocker' ); ?>
			</a>
			<img src="<?php echo esc_url( UB_PLUGIN_URL ); ?>/images/logo-200.png" style="float:left; margin:10px 20px 10px 10px" width="100" />
			<p style="font-size:16px"><?php echo esc_html__( 'We are glad you like', 'user-blocker' ) . ' <strong>' . esc_html__( 'User Blocker', 'user-blocker' ) . '</strong> ' . esc_html__( 'plugin and have been using it since the past few days. It is time to take the next step.', 'user-blocker' ); ?></p>
			<p>
				<a class="user_blocking_button user_blocking_button2" target="_blank" href="https://wordpress.org/support/plugin/user-blocker/reviews/?filter=5">
				<?php
				esc_html_e( 'Rate it', 'user-blocker' );
				echo " 5★'s";
				?>
				</a>
				<a class="user_blocking_button user_blocking_button3" target="_blank" href="https://www.facebook.com/SolwinInfotech/"><?php esc_html_e( 'Like Us on Facebook', 'user-blocker' ); ?></a>
				<a class="user_blocking_button user_blocking_button4" target="_blank" href="https://twitter.com/home?status=<?php esc_html_e( 'I use #userblocker to secure my #WordPress site from spam users.', 'user-blocker' ); ?>"><?php esc_html_e( 'Tweet about User Blocker ', 'user-blocker' ); ?></a>
			</p>
		</div>
		<?php
	}
}
