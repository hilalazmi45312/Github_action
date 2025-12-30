<?php
/**
 * User Blocker Common Functions.
 *
 * @link              https://www.solwininfotech.com/
 * @since             1.0.0
 * @package           User_blocker
 */

if ( ! function_exists( 'ublk_latest_news_solwin_feed' ) ) {
	/**
	 * Add solwin news dashboard.
	 */
	function ublk_latest_news_solwin_feed() {
		// Register the new dashboard widget with the 'wp_dashboard_setup' action.
		add_action( 'wp_dashboard_setup', 'ublk_solwin_latest_news_with_product_details' );
		if ( ! function_exists( 'ublk_solwin_latest_news_with_product_details' ) ) {
			/**
			 * Latest News with Product Details.
			 */
			function ublk_solwin_latest_news_with_product_details() {
				add_screen_option(
					'layout_columns',
					array(
						'max'     => 3,
						'default' => 2,
					)
				);
				add_meta_box( 'wp_blog_designer_dashboard_widget', esc_html__( 'News From Solwin Infotech', 'user-blocker' ), 'ublk_solwin_dashboard_widget_news', 'dashboard', 'normal', 'high' );
			}
		}
		if ( ! function_exists( 'ublk_solwin_dashboard_widget_news' ) ) {
			/**
			 * Add solwin news dashboard widget.
			 */
			function ublk_solwin_dashboard_widget_news() {
				echo '<div class="rss-widget">'
					. '<div class="solwin-news"><p><strong>' . esc_html__( 'Solwin Infotech News', 'user-blocker' ) . '</strong></p>';
				wp_widget_rss_output(
					array(
						'url'          => 'https://www.solwininfotech.com/feed/',
						'title'        => esc_html__( 'News From Solwin Infotech', 'user-blocker' ),
						'items'        => 5,
						'show_summary' => 0,
						'show_author'  => 0,
						'show_date'    => 1,
					)
				);
				echo '</div>';
				$title     = '';
				$link      = '';
				$thumbnail = '';
				// get Latest product detail from xml file.
				$file = 'https://www.solwininfotech.com/documents/assets/latest_product.xml';
				echo '<div class="display-product">'
					. '<div class="product-detail"><p><strong>' . esc_html__( 'Latest Product', 'user-blocker' ) . '</strong></p>';
				$response = wp_remote_get( $file );
				if ( is_wp_error( $response ) ) {
					$error_message = $response->get_error_message();
					echo '<p>' . esc_html__( 'Something went wrong', 'user-blocker' ) . ' : ' . esc_html( $error_message ) . '.</p>';
				} else {
					$body                = wp_remote_retrieve_body( $response );
					$xml                 = simplexml_load_string( $body );
					$title               = $xml->item->name;
					$thumbnail           = $xml->item->img;
					$link                = $xml->item->link;
					$all_product_text    = $xml->item->viewalltext;
					$all_product_link    = $xml->item->viewalllink;
					$moretext            = $xml->item->moretext;
					$needsupporttext     = $xml->item->needsupporttext;
					$needsupportlink     = $xml->item->needsupportlink;
					$customservicetext   = $xml->item->customservicetext;
					$customservicelink   = $xml->item->customservicelink;
					$joinproductclubtext = $xml->item->joinproductclubtext;
					$joinproductclublink = $xml->item->joinproductclublink;
					echo '<div class="product-name"><a href="' . esc_url( $link ) . '" target="_blank">'
						. '<img alt="' . esc_attr( $title ) . '" src="' . esc_url( $thumbnail ) . '"> </a>'
						. '<a href="' . esc_url( $link ) . '" target="_blank">' . esc_html( $title ) . '</a>'
						. '<p><a href="' . esc_url( $all_product_link ) . '" target="_blank" class="button button-default">' . esc_html( $all_product_text ) . ' &RightArrow;</a></p>'
						. '<hr>'
						. '<p><strong>' . esc_html( $moretext ) . '</strong></p>'
						. '<ul>'
						. '<li><a href="' . esc_url( $needsupportlink ) . '" target="_blank">' . esc_html( $needsupporttext ) . '</a></li>'
						. '<li><a href="' . esc_url( $customservicelink ) . '" target="_blank">' . esc_html( $customservicetext ) . '</a></li>'
						. '<li><a href="' . esc_url( $joinproductclublink ) . '" target="_blank">' . esc_html( $joinproductclubtext ) . '</a></li>'
						. '</ul>'
						. '</div>';
				}
				echo '</div></div><div class="clear"></div>'
					. '</div>';
			}
		}
	}
}

if ( ! function_exists( 'ublk_footer' ) ) {
	/**
	 * Add Footer credit link.
	 */
	function ublk_footer() {
		$screen = get_current_screen();
		if ( 'toplevel_page_block_user' == $screen->id || 'admin_page_block_user_date' == $screen->id || 'admin_page_block_user_permenant' == $screen->id || 'user-blocker_page_blocked_user_list' == $screen->id || 'admin_page_datewise_blocked_user_list' == $screen->id || 'admin_page_permanent_blocked_user_list' == $screen->id || 'admin_page_all_type_blocked_user_list' == $screen->id ) {
			add_filter( 'admin_footer_text', 'ublk_remove_footer_admin' ); // change admin footer text.
		}
	}
}

if ( ! function_exists( 'ublk_remove_footer_admin' ) ) {
	/**
	 * Add rating html at footer of admin.
	 *
	 * @return html rating
	 */
	function ublk_remove_footer_admin() {
		ob_start();
		?>
		<p id="footer-left" class="alignleft">
			<?php esc_html_e( 'If you like', 'user-blocker' ); ?>&nbsp;
			<a href="https://wordpress.org/plugins/user-blocker/" target="_blank">
				<strong><?php esc_html_e( 'User Blocker', 'user-blocker' ); ?></strong>
			</a>
			<?php esc_html_e( 'please leave us a', 'user-blocker' ); ?>
			<a class="bdp-rating-link" data-rated="Thanks :)" target="_blank" href="https://wordpress.org/support/plugin/user-blocker/reviews/?filter=5#new-post">&#x2605;&#x2605;&#x2605;&#x2605;&#x2605;</a>
			<?php esc_html_e( 'rating. A huge thank you from Solwin Infotech in advance!', 'user-blocker' ); ?>
		</p>
		<?php
		return ob_get_clean();
	}
}

if ( ! function_exists( 'ublk_enqueue_style_script' ) ) {
	/**
	 * Enqueue admin panel required css.
	 */
	function ublk_enqueue_style_script() {
		global $screen;
		$screen = get_current_screen();
		?>
		<script type="text/javascript">
			var adminURL = '<?php echo esc_attr( admin_url() ); ?>';
		</script> 
		<?php
		$plugin_data     = get_plugin_data( UB_PLUGIN_DIR . '/user_blocker.php', $markup = true, $translate = true );
		$current_version = $plugin_data['Version'];
		$old_version     = get_option( 'ublk_version' );
		if ( $old_version != $current_version ) {
			update_option( 'is_user_subscribed_cancled', '' );
			update_option( 'ublk_version', $current_version );
		}
		if ( 'yes' != get_option( 'is_user_subscribed' ) && 'yes' != get_option( 'is_user_subscribed_cancled' ) ) {
			wp_enqueue_script( 'thickbox' );
			wp_enqueue_style( 'thickbox' );
		}

		if ( ( isset( $_GET['page'] ) && ( 'all_type_blocked_user_list' == $_GET['page'] || 'permanent_blocked_user_list' == $_GET['page'] || 'datewise_blocked_user_list' == $_GET['page'] || 'blocked_user_list' == $_GET['page'] || 'block_user' == $_GET['page'] || 'block_user_date' == $_GET['page'] || 'block_user_permenant' == $_GET['page'] || 'welcome_block_user' == $_GET['page'] || 'user_blocker_settings' == $_GET['page'] ) ) || 'plugins' == $screen->id ) {
			wp_register_script( 'timepicker-addon', UB_PLUGIN_URL . '/script/jquery-ui-timepicker-addon.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-datepicker' ), '1.6.3', false );
			wp_enqueue_script( 'timepicker-addon' );
			wp_register_script( 'timepicker', UB_PLUGIN_URL . '/script/jquery.timepicker.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-datepicker' ), '1.14.0', false );
			wp_enqueue_script( 'timepicker' );
			wp_register_script( 'datepair', UB_PLUGIN_URL . '/script/datepair.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-datepicker' ), '0.4.17', false );
			wp_enqueue_script( 'datepair' );
			wp_register_script( 'admin_script', UB_PLUGIN_URL . '/script/admin_script.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-datepicker' ), '1.5.6', false );
			wp_enqueue_script( 'admin_script' );

			wp_register_style( 'timepicker', UB_PLUGIN_URL . '/css/jquery.timepicker.css', array(), '1.14.0', false );
			wp_enqueue_style( 'timepicker' );
			wp_register_style( 'jqueryUI', UB_PLUGIN_URL . '/css/jquery-ui.css', array(), '1.13.2', false );
			wp_enqueue_style( 'jqueryUI' );
			wp_register_style( 'timepicker-addon', UB_PLUGIN_URL . '/css/jquery-ui-timepicker-addon.css', array(), '1.6.3', false );
			wp_enqueue_style( 'timepicker-addon' );
			wp_register_style( 'admin_style', UB_PLUGIN_URL . '/css/admin_style.css', array(), '1.5.6', false );
			wp_enqueue_style( 'admin_style' );
		}

		if ( 'dashboard' == $screen->id ) {
			wp_register_style( 'admin_style', UB_PLUGIN_URL . '/css/admin_style.css', array(), '1.5.7', false );
			wp_enqueue_style( 'admin_style' );
		}
		if ( is_rtl() ) {
			wp_enqueue_style( 'admin_style_rtl', UB_PLUGIN_URL . '/css/admin_style_rtl.css', array(), '1.5.6', false );
		}
	}
}

if ( ! function_exists( 'ublk_get_user_blocker_total_downloads' ) ) {
	/**
	 * Get User Blocker Total Downloads.
	 */
	function ublk_get_user_blocker_total_downloads() {
		// Set the arguments. For brevity of code, I will set only a few fields.
		$plugins  = '';
		$response = '';
		$args     = array(
			'author' => 'solwininfotech',
			'fields' => array(
				'downloaded'   => true,
				'downloadlink' => true,
			),
		);
		// Make request and extract plug-in object. Action is query_plugins.
		$response = wp_remote_get(
			'http://api.wordpress.org/plugins/info/1.0/',
			array(
				'body' => array(
					'action'  => 'query_plugins',
					'request' => maybe_serialize( (object) $args ),
				),
			)
		);
		if ( ! is_wp_error( $response ) ) {
			$returned_object = maybe_unserialize( wp_remote_retrieve_body( $response ) );
			$plugins         = $returned_object->plugins;
		}
		$current_slug = 'user-blocker';
		if ( $plugins ) {
			foreach ( $plugins as $plugin ) {
				if ( $current_slug == $plugin->slug ) {
					if ( $plugin->downloaded ) {
						?>
						<span class="total-downloads">
							<span class="download-number"><?php echo esc_html( $plugin->downloaded ); ?></span>
						</span>
						<?php
					}
				}
			}
		}
	}
}

$wp_current_version = get_bloginfo( 'version' );
if ( $wp_current_version > 3.8 ) {
	if ( ! function_exists( 'ublk_wp_user_blocker_star_rating' ) ) {
		/**
		 * User Blocker Star Rating.
		 *
		 * @param array $args Arguments.
		 */
		function ublk_wp_user_blocker_star_rating( $args = array() ) {
			$plugins  = '';
			$response = '';
			$args     = array(
				'author' => 'solwininfotech',
				'fields' => array(
					'downloaded'   => true,
					'downloadlink' => true,
				),
			);
			// Make request and extract plug-in object. Action is query_plugins.
			$response = wp_remote_get(
				'http://api.wordpress.org/plugins/info/1.0/',
				array(
					'body' => array(
						'action'  => 'query_plugins',
						'request' => maybe_serialize( (object) $args ),
					),
				)
			);
			if ( ! is_wp_error( $response ) ) {
				$returned_object = maybe_unserialize( wp_remote_retrieve_body( $response ) );
				$plugins         = $returned_object->plugins;
			}
			$current_slug = 'user-blocker';
			if ( $plugins ) {
				foreach ( $plugins as $plugin ) {
					if ( $current_slug == $plugin->slug ) {
						$rating = $plugin->rating * 5 / 100;
						if ( $rating > 0 ) {
							$args = array(
								'rating' => $rating,
								'type'   => 'rating',
								'number' => $plugin->num_ratings,
							);
							wp_star_rating( $args );
						}
					}
				}
			}
		}
	}
}

if ( ! function_exists( 'ublk_display_support_section' ) ) {
	/**
	 * Display html of support section at right side.
	 */
	function ublk_display_support_section() {
		?>
		<div class="td-admin-sidebar">
			<div class="td-help">
				<h2><?php esc_html_e( 'Help to improve this plugin!', 'user-blocker' ); ?></h2>
				<div class="help-wrapper">
					<span><?php esc_html_e( 'Enjoyed this plugin?', 'user-blocker' ); ?></span>
					<span><?php esc_html_e( ' You can help by', 'user-blocker' ); ?>
						<a href="https://wordpress.org/support/plugin/user-blocker/reviews/?filter=5#new-post" target="_blank">
							<?php esc_html_e( ' rating this plugin on wordpress.org', 'user-blocker' ); ?>
						</a>
					</span>
					<div class="td-total-download">
						<?php esc_html_e( 'Downloads:', 'user-blocker' ); ?><?php ublk_get_user_blocker_total_downloads(); ?>
						<?php
						$wp_current_version = get_bloginfo( 'version' );
						if ( $wp_current_version > 3.8 ) {
							ublk_wp_user_blocker_star_rating();
						}
						?>
					</div>
				</div>
			</div>
			<div class="useful_plugins">
				<h2><?php esc_html_e( 'Our Other Works', 'user-blocker' ); ?></h2>
				<div class="help-wrapper">
					<div class="pro-content">
						<strong class="ual_advertisement_legend"><?php esc_html_e( 'Blog Designer Pro', 'user-blocker' ); ?></strong>
						<ul class="advertisementContent">
							<li><?php esc_html_e( '50+ Beautiful Blog Templates', 'user-blocker' ); ?></li>
							<li><?php esc_html_e( '10+ Unique Timeline Templates', 'user-blocker' ); ?></li>
							<li><?php esc_html_e( '200+ Blog Layout Variations', 'user-blocker' ); ?></li>
							<li><?php esc_html_e( 'Single Post Design options', 'user-blocker' ); ?></li>
							<li><?php esc_html_e( 'Category, Tag, Author Layouts', 'user-blocker' ); ?></li>
							<li><?php esc_html_e( 'Post Type & Taxonomy Filter', 'user-blocker' ); ?></li>
							<li><?php esc_html_e( '800+ Google Font Support', 'user-blocker' ); ?></li>
							<li><?php esc_html_e( '600+ Font Awesome Icons Support', 'user-blocker' ); ?></li>
						</ul>
						<p class="pricing_change"><?php esc_html_e( 'Now only at', 'user-blocker' ); ?> <ins>$59</ins></p>
					</div>
					<div class="pre-book-pro">
						<a href="https://codecanyon.net/item/blog-designer-pro-for-wordpress/17069678?ref=solwin" target="_blank">
							<?php esc_html_e( 'Buy Now on Codecanyon', 'user-blocker' ); ?>
						</a>
					</div>
				</div>
			</div>
			<div class="td-support">
				<h3><?php esc_html_e( 'Need Support?', 'user-blocker' ); ?></h3>
				<div class="help-wrapper">
					<span><?php esc_html_e( 'Check out the', 'user-blocker' ); ?>
						<a href="https://wordpress.org/plugins/user-blocker/faq/" target="_blank"><?php esc_html_e( 'FAQs', 'user-blocker' ); ?></a>
						<?php esc_html_e( 'and', 'user-blocker' ); ?>
						<a href="https://wordpress.org/support/plugin/user-blocker/" target="_blank"><?php esc_html_e( 'Support Forums', 'user-blocker' ); ?></a>
					</span>
				</div>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'ublk_time_to_twenty_four_hour' ) ) {
	/**
	 * Display Time to 24 hours Format.
	 *
	 * @param string $time Time.
	 */
	function ublk_time_to_twenty_four_hour( $time ) {
		if ( '' != $time ) {
			$time = gmdate( 'H:i:s', strtotime( $time ) );
		}
		return $time;
	}
}

if ( ! function_exists( 'ublk_time_to_twelve_hour' ) ) {
	/**
	 * Display Time to 12 hours Format.
	 *
	 * @param string $time Time.
	 */
	function ublk_time_to_twelve_hour( $time ) {
		if ( '' != $time ) {
			$time = gmdate( 'h:i A', strtotime( $time ) );
		}
		return $time;
	}
}

if ( ! function_exists( 'ublk_date_time_to_twelve_hour' ) ) {
	/**
	 * Display Time to 12 hours Format.
	 *
	 * @param string $date_time Date & Time.
	 */
	function ublk_date_time_to_twelve_hour( $date_time ) {
		if ( '' != $date_time ) {
			$date_time = gmdate( 'm/d/Y h:i A', strtotime( $date_time ) );
		}
		return $date_time;
	}
}

if ( ! function_exists( 'ublk_disp_msg' ) ) {
	/**
	 * Display Time to 12 hours Format.
	 *
	 * @param string $msg Message.
	 */
	function ublk_disp_msg( $msg ) {
		$msg = stripslashes( nl2br( $msg ) );
		return $msg;
	}
}


if ( ! function_exists( 'ublk_get_time_record' ) ) {
	/**
	 * Display Time to 12 hours Format.
	 *
	 * @param string $day Day.
	 * @param string $block_day Block Day.
	 */
	function ublk_get_time_record( $day, $block_day ) {
		if ( array_key_exists( $day, $block_day ) ) {
			$from_time = $block_day[ $day ]['from'];
			$to_time   = $block_day[ $day ]['to'];
			if ( '' == $from_time ) {
				esc_html_e( 'not set', 'user-blocker' );
			} else {
				echo esc_html( ublk_time_to_twelve_hour( $from_time ) );
			}
			if ( '' != $from_time && '' != $to_time ) {
				echo ' ' . esc_html__( 'to', 'user-blocker' ) . ' ' . esc_html( ublk_time_to_twelve_hour( $to_time ) );
			}
		} else {
			esc_html_e( 'not set', 'user-blocker' );
		}
	}
}

if ( ! function_exists( 'ublk_settings_link' ) ) {
	/**
	 * Display Settings Link.
	 *
	 * @param array $actions Actions.
	 */
	function ublk_settings_link( $actions ) {
		$settings_link = '<a href="' . admin_url( 'admin.php?page=block_user' ) . '">' . esc_html__( 'Settings', 'user-blocker' ) . '</a>';
		array_unshift( $actions, $settings_link );
		return $actions;
	}
}

if ( ! function_exists( 'ublk_session_start' ) ) {
	/**
	 * Start session if not started.
	 */
	function ublk_session_start() {
		if ( session_status() != PHP_SESSION_ACTIVE ) {
			session_start( array( 'read_and_close' => true ) );
		}
	}
}

if ( ! function_exists( 'ublk_subscribe_mail' ) ) {
	/**
	 * Subscribe email form.
	 */
	function ublk_subscribe_mail() {
		?>
		<div id="sol_deactivation_widget_cover_ublk" style="display:none;">
			<div class="sol_deactivation_widget">
				<h3><?php esc_html_e( 'If you have a moment, please let us know why you are deactivating. We would like to help you in fixing the issue.', 'user-blocker' ); ?></h3>
				<form id="frmDeactivationublk" name="frmDeactivation" method="post" action="">
					<ul class="sol_deactivation_reasons_ublk">
						<?php $i = 1; ?>
						<li>
							<input class="sol_deactivation_reasons" name="sol_deactivation_reasons_ublk" type="radio" value="<?php echo esc_attr( $i ); ?>" id="ublk_reason_<?php echo esc_attr( $i ); ?>">
							<label for="ublk_reason_<?php echo esc_attr( $i ); ?>"><?php esc_html_e( 'The plugin suddenly stopped working', 'user-blocker' ); ?></label>
						</li>
						<?php $i++; ?>
						<li>
							<input class="sol_deactivation_reasons" name="sol_deactivation_reasons_ublk" type="radio" value="<?php echo esc_attr( $i ); ?>" id="ublk_reason_<?php echo esc_attr( $i ); ?>">
							<label for="ublk_reason_<?php echo esc_attr( $i ); ?>"><?php esc_html_e( 'The plugin was not working', 'user-blocker' ); ?></label>
						</li>
						<?php $i++; ?>
						<li>
							<input class="sol_deactivation_reasons" name="sol_deactivation_reasons_ublk" type="radio" value="<?php echo esc_attr( $i ); ?>" id="ublk_reason_<?php echo esc_attr( $i ); ?>">
							<label for="ublk_reason_<?php echo esc_attr( $i ); ?>"><?php esc_html_e( 'Found other better plugin than this plugin', 'user-blocker' ); ?></label>
						</li>
						<?php $i++; ?>
						<li>
							<input class="sol_deactivation_reasons" name="sol_deactivation_reasons_ublk" type="radio" value="<?php echo esc_attr( $i ); ?>" id="ublk_reason_<?php echo esc_attr( $i ); ?>">
							<label for="ublk_reason_<?php echo esc_attr( $i ); ?>"><?php esc_html_e( 'The plugin broke my site completely', 'user-blocker' ); ?></label>
						</li>
						<?php $i++; ?>
						<li>
							<input class="sol_deactivation_reasons" name="sol_deactivation_reasons_ublk" type="radio" value="<?php echo esc_attr( $i ); ?>" id="ublk_reason_<?php echo esc_attr( $i ); ?>">
							<label for="ublk_reason_<?php echo esc_attr( $i ); ?>"><?php esc_html_e( 'No any reason', 'user-blocker' ); ?></label>
						</li>
						<?php $i++; ?>
						<li>
							<input class="sol_deactivation_reasons" name="sol_deactivation_reasons_ublk" type="radio" value="<?php echo esc_attr( $i ); ?>" id="ublk_reason_<?php echo esc_attr( $i ); ?>">
							<label for="ublk_reason_<?php echo esc_attr( $i ); ?>"><?php esc_html_e( 'Other', 'user-blocker' ); ?></label><br />
							<input style="display:none;width: 90%" value="" type="text" name="sol_deactivation_reason_other_ublk" class="sol_deactivation_reason_other_ublk" />
						</li>
					</ul>
					<p>
						<input type='checkbox' class='ublk_agree' id='ublk_agree_gdpr_deactivate' value='1' />
						<label for='ublk_agree_gdpr_deactivate' class='ublk_agree_gdpr_lbl'><?php esc_html_e( 'By clicking this button, you agree with the storage and handling of your data as mentioned above by this website. (GDPR Compliance)', 'user-blocker' ); ?></label>
					</p>
					<a onclick='ublk_submit_optin("deactivate")' class="button button-secondary">
					<?php
					esc_html_e( 'Submit', 'user-blocker' );
					echo ' &amp; ';
					esc_html_e( 'Deactivate', 'user-blocker' );
					?>
					</a>
					<input type="submit" name="sbtDeactivationFormClose" id="sbtDeactivationFormCloseual" class="button button-primary" value="<?php esc_html_e( 'Cancel', 'user-blocker' ); ?>" />
					<a href="javascript:void(0)" class="ublk-deactivation" aria-label="<?php esc_html_e( 'Deactivate User Blocker', 'user-blocker' ); ?>">
					<?php
					esc_html_e( 'Skip', 'user-blocker' );
					echo ' &amp; ';
					esc_html_e( ' Deactivate', 'user-blocker' );
					?>
					</a>
				</form>
				<div class="support-ticket-section">
					<h3><?php esc_html_e( 'Would you like to give us a chance to help you?', 'user-blocker' ); ?></h3>
					<img src="<?php echo esc_url( UB_PLUGIN_URL ) . '/images/support-ticket.png'; ?>">
					<a target='_blank' href="<?php echo esc_url( 'http://support.solwininfotech.com/' ); ?>"><?php esc_html_e( 'Create a support ticket', 'user-blocker' ); ?></a>
				</div>
			</div>
		</div>
		<a style="display:none" href="#TB_inline?height=800&inlineId=sol_deactivation_widget_cover_ublk" class="thickbox" id="deactivation_thickbox_ublk"></a>
		<?php
	}
}


if ( ! function_exists( 'ublk_user_category_dropdown' ) ) {
	/**
	 * User Category Dropdown.
	 *
	 * @param string $cmb_user_by User By.
	 */
	function ublk_user_category_dropdown( $cmb_user_by ) {
		?>
		<label><strong><?php esc_html_e( 'Select User/Category: ', 'user-blocker' ); ?></strong></label>
		<select name="cmbUserBy" id="cmbUserBy" onchange="changeUserBy();">
			<option <?php echo selected( $cmb_user_by, 'username' ); ?> value="username"><?php esc_html_e( 'Username', 'user-blocker' ); ?></option>
			<option <?php echo selected( $cmb_user_by, 'role' ); ?> value="role"><?php esc_html_e( 'Role', 'user-blocker' ); ?></option>
		</select>
		<?php
	}
}

if ( ! function_exists( 'ublk_blocked_user_category_dropdown' ) ) {
	/**
	 * Blocked User Category Dropdown.
	 *
	 * @param string $display Display.
	 */
	function ublk_blocked_user_category_dropdown( $display ) {
		?>
		<label><strong><?php esc_html_e( 'Select User/Category: ', 'user-blocker' ); ?></strong></label>
		<select name="display" id="display_status">
			<option value="users" <?php echo selected( $display, 'users' ); ?>><?php esc_html_e( 'Users', 'user-blocker' ); ?></option>
			<option value="roles" <?php echo selected( $display, 'roles' ); ?>><?php esc_html_e( 'Roles', 'user-blocker' ); ?></option>
		</select>
		<?php
	}
}

if ( ! function_exists( 'ublk_role_selection_dropdown' ) ) {
	/**
	 * Role Selection Dropdown.
	 *
	 * @param string $display_users Display Users.
	 * @param array  $get_roles Get Roles.
	 * @param string $srole Roles.
	 */
	function ublk_role_selection_dropdown( $display_users, $get_roles, $srole ) {
		?>
		<div style="margin-left: 15px; display: inline-block; vertical-align: middle;">
			<div class="filter_div" 
			<?php
			if ( 1 == $display_users ) {
				echo 'style="display: block;"';
			} else {
				echo 'style="display: none;"';
			}
			?>
			>
				<label><strong><?php esc_html_e( 'Select Role: ', 'user-blocker' ); ?></strong></label>
				<select id="srole" name="srole" onchange="searchUser();">
					<option value=""><?php esc_html_e( 'All Roles', 'user-blocker' ); ?></option>
					<?php
					if ( $get_roles ) {
						foreach ( $get_roles as $key => $value ) {
							if ( 'administrator' == $key ) {
								continue;
							}
							?>
							<option <?php echo selected( $key, $srole ); ?> value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( ucfirst( $value['name'] ) ); ?></option>
							<?php
						}
					}
					?>
				</select>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'ublk_blocked_role_selection_dropdown' ) ) {
	/**
	 * Blocked Role Selection Dropdown.
	 *
	 * @param string $display Display.
	 * @param array  $get_roles Get Roles.
	 * @param string $srole Roles.
	 */
	function ublk_blocked_role_selection_dropdown( $display, $get_roles, $srole ) {
		?>
		<div style="margin-left: 15px; display: inline-block; vertical-align: middle;">
			<div class="filter_div" 
			<?php
			if ( 'roles' == $display ) {
				echo 'style="display: none"';}
			?>
			>
				<label><strong><?php esc_html_e( 'Select Role: ', 'user-blocker' ); ?></strong></label>
				<select id="srole" name="role" onchange="searchUser();">
					<option value=""><?php esc_html_e( 'All Roles', 'user-blocker' ); ?></option>
		<?php
		if ( $get_roles ) {
			foreach ( $get_roles as $key => $value ) {
				if ( 'administrator' == $key ) {
					continue;
				}
				?>
					<option <?php echo selected( $key, $srole ); ?> value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( ucfirst( $value['name'] ) ); ?></option>
				<?php
			}
		}
		?>
				</select>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'ublk_pagination' ) ) {
	/**
	 * Pagination.
	 *
	 * @param string $display_users Display Users.
	 * @param string $total_pages Total Pages.
	 * @param string $total_items Total Items.
	 * @param string $paged Paged.
	 * @param string $prev_page Previous Page.
	 * @param string $next_page Next Pages.
	 * @param string $srole Role.
	 * @param string $txt_username Username.
	 * @param string $orderby Order By.
	 * @param string $order Order.
	 * @param string $tab Tab.
	 */
	function ublk_pagination( $display_users, $total_pages, $total_items, $paged, $prev_page, $next_page, $srole, $txt_username, $orderby, $order, $tab ) {
		?>
		<div class="filter_div" style="float: right; 
		<?php
		if ( 1 == $display_users ) {
			echo 'display: block;';
		} else {
			echo 'display: none;';
		}
		?>
		">
			<div class="tablenav-pages" 
			<?php
			if ( (int) $total_pages <= 1 ) {
				echo 'style="display: none;"';
			}
			?>
			>
				<span class="displaying-num">
				<?php
				echo esc_html( $total_items . ' ' );
				esc_html_e( 'items', 'user-blocker' );
				?>
			</span>
				<span class="pagination-links">
					<a class="first-page 
					<?php
					if ( '1' == $paged ) {
						echo 'disabled';}
					?>
					" href="<?php echo '?page=' . esc_url( $tab ) . '&paged=1&srole=' . esc_attr( $srole ) . '&txtUsername=' . esc_attr( $txt_username ); ?>&orderby=<?php echo esc_attr( $orderby ); ?>&order=<?php echo esc_attr( $order ); ?>" title="<?php esc_html_e( 'Go to the first page', 'user-blocker' ); ?>">&laquo;</a>
					<a class="prev-page 
					<?php
					if ( '1' == $paged ) {
						echo 'disabled';}
					?>
					" href="<?php echo '?page=' . esc_url( $tab ) . '&paged=' . esc_attr( $prev_page ) . '&srole=' . esc_attr( $srole ) . '&txtUsername=' . esc_attr( $txt_username ); ?>&orderby=<?php echo esc_attr( $orderby ); ?>&order=<?php echo esc_attr( $order ); ?>" title="<?php esc_html_e( 'Go to the previous page', 'user-blocker' ); ?>">&lsaquo;</a>
					<span class="paging-input">
						<input class="current-page" type="text" size="1" value="<?php echo esc_attr( $paged ); ?>" name="paged" title="<?php esc_html_e( 'Current page', 'user-blocker' ); ?>">
			<?php esc_html_e( 'of', 'user-blocker' ); ?>
						<span class="total-pages"><?php echo esc_html( $total_pages ); ?></span>
					</span>
					<a class="next-page 
					<?php
					if ( $paged == $total_pages ) {
						echo 'disabled';}
					?>
					" href="<?php echo '?page=' . esc_url( $tab ) . '&paged=' . esc_attr( $next_page ) . '&srole=' . esc_attr( $srole ) . '&txtUsername=' . esc_attr( $txt_username ); ?>&orderby=<?php echo esc_attr( $orderby ); ?>&order=<?php echo esc_attr( $order ); ?>" title="<?php esc_html_e( 'Go to the next page', 'user-blocker' ); ?>">&rsaquo;</a>
					<a class="last-page 
					<?php
					if ( $paged == $total_pages ) {
						echo 'disabled';}
					?>
					" href="<?php echo '?page=' . esc_url( $tab ) . '&paged=' . esc_attr( $total_pages ) . '&srole=' . esc_attr( $srole ) . '&txtUsername=' . esc_attr( $txt_username ); ?>&orderby=<?php echo esc_attr( $orderby ); ?>&order=<?php echo esc_attr( $order ); ?>" title="<?php esc_html_e( 'Go to the last page', 'user-blocker' ); ?>">&raquo;</a>
				</span>
				<input style="display: none;" id="sbtPages" class="button" type="submit" value="sbtPages" name="filter_action">
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'ublk_blocked_pagination' ) ) {
	/**
	 * Pagination.
	 *
	 * @param string $total_pages Total Pages.
	 * @param string $total_items Total Items.
	 * @param string $paged Paged.
	 * @param string $prev_page Previous Page.
	 * @param string $next_page Next Pages.
	 * @param string $srole Role.
	 * @param string $txt_username Username.
	 * @param string $orderby Order By.
	 * @param string $order Order.
	 * @param string $tab Tab.
	 */
	function ublk_blocked_pagination( $total_pages, $total_items, $paged, $prev_page, $next_page, $srole, $txt_username, $orderby, $order, $tab ) {
		if ( isset( $_GET['page'] ) && ( 'blocked_user_list' == $_GET['page'] ) ) {
			?>
			<div class="tablenav-pages" 
			<?php
			if ( (int) $total_pages <= 1 ) {
				echo 'style="display: none;"';
			}
			?>
			>
				<span class="displaying-num"><?php echo esc_attr( $total_items ); ?> <?php esc_html_e( 'items', 'user-blocker' ); ?></span>
				<span class="pagination-links">
					<a class="first-page 
					<?php
					if ( '1' == $paged ) {
						echo 'disabled';}
					?>
					" href="<?php echo '?page=blocked_user_list&paged=1&role=' . esc_attr( $srole ) . '&txtUsername=' . esc_attr( $txt_username ); ?>&orderby=<?php echo esc_attr( $orderby ); ?>&order=<?php echo esc_attr( $order ); ?>" title="<?php esc_html_e( 'Go to the first page', 'user-blocker' ); ?>">&laquo;</a>
					<a class="prev-page 
					<?php
					if ( '1' == $paged ) {
						echo 'disabled';}
					?>
					" href="<?php echo '?page=blocked_user_list&paged=' . esc_attr( $prev_page ) . '&role=' . esc_attr( $srole ) . '&txtUsername=' . esc_attr( $txt_username ); ?>&orderby=<?php echo esc_attr( $orderby ); ?>&order=<?php echo esc_attr( $order ); ?>" title="<?php esc_html_e( 'Go to the previous page', 'user-blocker' ); ?>">&lsaquo;</a>
					<span class="paging-input">
						<input class="current-page" type="text" size="1" value="<?php echo esc_attr( $paged ); ?>" name="paged" title="Current page">
						<?php esc_html_e( 'of', 'user-blocker' ); ?>
						<span class="total-pages"><?php echo esc_html( $total_pages ); ?></span>
					</span>
					<a class="next-page 
					<?php
					if ( $paged == $total_pages ) {
						echo 'disabled';}
					?>
					" href="<?php echo '?page=blocked_user_list&paged=' . esc_attr( $next_page ) . '&role=' . esc_attr( $srole ) . '&txtUsername=' . esc_attr( $txt_username ); ?>&orderby=<?php echo esc_attr( $orderby ); ?>&order=<?php echo esc_attr( $order ); ?>" title="<?php esc_html_e( 'Go to the next page', 'user-blocker' ); ?>">&rsaquo;</a>
					<a class="last-page 
					<?php
					if ( $paged == $total_pages ) {
						echo 'disabled';}
					?>
					" href="<?php echo '?page=blocked_user_list&paged=' . esc_attr( $total_pages ) . '&role=' . esc_attr( $srole ) . '&txtUsername=' . esc_attr( $txt_username ); ?>&orderby=<?php echo esc_attr( $orderby ); ?>&order=<?php echo esc_attr( $order ); ?>" title="<?php esc_html_e( 'Go to the last page', 'user-blocker' ); ?>">&raquo;</a>
				</span>
				<input style="display: none;" id="sbtPages" class="button" type="submit" value="sbtPages" name="filter_action">
			</div>
		<?php } elseif ( isset( $_GET['page'] ) && ( 'datewise_blocked_user_list' == $_GET['page'] ) ) { ?>
			<div class="tablenav-pages" 
			<?php
			if ( (int) $total_pages <= 1 ) {
				echo 'style="display: none;"';
			}
			?>
			>
				<span class="displaying-num"><?php echo esc_attr( $total_items ); ?> <?php esc_html_e( 'items', 'user-blocker' ); ?></span>
				<span class="pagination-links">
					<a class="first-page 
					<?php
					if ( '1' == $paged ) {
						echo 'disabled';}
					?>
					" href="<?php echo '?page=datewise_blocked_user_list&paged=1&role=' . esc_attr( $srole ) . '&txtUsername=' . esc_attr( $txt_username ); ?>&orderby=<?php echo esc_attr( $orderby ); ?>&order=<?php echo esc_attr( $order ); ?>" title="<?php esc_html_e( 'Go to the first page', 'user-blocker' ); ?>">&laquo;</a>
					<a class="prev-page 
					<?php
					if ( '1' == $paged ) {
						echo 'disabled';}
					?>
					" href="<?php echo '?page=datewise_blocked_user_list&paged=' . esc_attr( $prev_page ) . '&role=' . esc_attr( $srole ) . '&txtUsername=' . esc_attr( $txt_username ); ?>&orderby=<?php echo esc_attr( $orderby ); ?>&order=<?php echo esc_attr( $order ); ?>" title="<?php esc_html_e( 'Go to the previous page', 'user-blocker' ); ?>">&lsaquo;</a>
					<span class="paging-input">
						<input class="current-page" type="text" size="1" value="<?php echo esc_attr( $paged ); ?>" name="paged" title="Current page">
						<?php esc_html_e( 'of', 'user-blocker' ); ?>
						<span class="total-pages"><?php echo esc_attr( $total_pages ); ?></span>
					</span>
					<a class="next-page 
					<?php
					if ( $paged == $total_pages ) {
						echo 'disabled';}
					?>
					" href="<?php echo '?page=datewise_blocked_user_list&paged=' . esc_attr( $next_page ) . '&role=' . esc_attr( $srole ) . '&txtUsername=' . esc_attr( $txt_username ); ?>&orderby=<?php echo esc_attr( $orderby ); ?>&order=<?php echo esc_attr( $order ); ?>" title="<?php esc_html_e( 'Go to the next page', 'user-blocker' ); ?>">&rsaquo;</a>
					<a class="last-page 
					<?php
					if ( $paged == $total_pages ) {
						echo 'disabled';}
					?>
					" href="<?php echo '?page=datewise_blocked_user_list&paged=' . esc_attr( $total_pages ) . '&role=' . esc_attr( $srole ) . '&txtUsername=' . esc_attr( $txt_username ); ?>&orderby=<?php echo esc_attr( $orderby ); ?>&order=<?php echo esc_attr( $order ); ?>" title="<?php esc_html_e( 'Go to the last page', 'user-blocker' ); ?>">&raquo;</a>
				</span>
				<input style="display: none;" id="sbtPages" class="button" type="submit" value="sbtPages" name="filter_action">
			</div>
		<?php } elseif ( isset( $_GET['page'] ) && ( 'permanent_blocked_user_list' == $_GET['page'] ) ) { ?>
			<div class="tablenav-pages" 
			<?php
			if ( (int) $total_pages <= 1 ) {
				echo 'style="display: none;"';
			}
			?>
										>
				<span class="displaying-num"><?php echo esc_html( $total_items ); ?> <?php esc_html_e( 'items', 'user-blocker' ); ?></span>
				<span class="pagination-links">
					<a class="first-page 
					<?php
					if ( '1' == $paged ) {
						echo 'disabled';}
					?>
					" href="<?php echo '?page=permanent_blocked_user_list&paged=1&role=' . esc_attr( $srole ) . '&txtUsername=' . esc_attr( $txt_username ); ?>&orderby=<?php echo esc_attr( $orderby ); ?>&order=<?php echo esc_attr( $order ); ?>" title="<?php esc_html_e( 'Go to the first page', 'user-blocker' ); ?>">&laquo;</a>
					<a class="prev-page 
					<?php
					if ( '1' == $paged ) {
						echo 'disabled';}
					?>
					" href="<?php echo '?page=permanent_blocked_user_list&paged=' . esc_attr( $prev_page ) . '&role=' . esc_attr( $srole ) . '&txtUsername=' . esc_attr( $txt_username ); ?>&orderby=<?php echo esc_attr( $orderby ); ?>&order=<?php echo esc_attr( $order ); ?>" title="<?php esc_html_e( 'Go to the previous page', 'user-blocker' ); ?>">&lsaquo;</a>
					<span class="paging-input">
						<input class="current-page" type="text" size="1" value="<?php echo esc_attr( $paged ); ?>" name="paged" title="Current page">
						<?php esc_html_e( 'of', 'user-blocker' ); ?>
						<span class="total-pages"><?php echo esc_html( $total_pages ); ?></span>
					</span>
					<a class="next-page 
					<?php
					if ( $paged == $total_pages ) {
						echo 'disabled';}
					?>
					" href="<?php echo '?page=permanent_blocked_user_list&paged=' . esc_attr( $next_page ) . '&role=' . esc_attr( $srole ) . '&txtUsername=' . esc_attr( $txt_username ); ?>&orderby=<?php echo esc_attr( $orderby ); ?>&order=<?php echo esc_attr( $order ); ?>" title="<?php esc_html_e( 'Go to the next page', 'user-blocker' ); ?>">&rsaquo;</a>
					<a class="last-page 
					<?php
					if ( $paged == $total_pages ) {
						echo 'disabled';}
					?>
					" href="<?php echo '?page=permanent_blocked_user_list&paged=' . esc_attr( $total_pages ) . '&role=' . esc_attr( $srole ) . '&txtUsername=' . esc_attr( $txt_username ); ?>&orderby=<?php echo esc_attr( $orderby ); ?>&order=<?php echo esc_attr( $order ); ?>" title="<?php esc_html_e( 'Go to the last page', 'user-blocker' ); ?>">&raquo;</a>
				</span>
				<input style="display: none;" id="sbtPages" class="button" type="submit" value="sbtPages" name="filter_action">
			</div>

		<?php } else { ?>
			<div class="tablenav-pages" 
			<?php
			if ( (int) $total_pages <= 1 ) {
				echo 'style="display: none;"';
			}
			?>
			>
				<span class="displaying-num"><?php echo esc_attr( $total_items ); ?> <?php esc_html_e( 'items', 'user-blocker' ); ?></span>
				<span class="pagination-links">
					<a class="first-page 
					<?php
					if ( '1' == $paged ) {
						echo 'disabled';}
					?>
					" href="<?php echo '?page=all_type_blocked_user_list&paged=1&role=' . esc_attr( $srole ) . '&txtUsername=' . esc_attr( $txt_username ); ?>&orderby=<?php echo esc_attr( $orderby ); ?>&order=<?php echo esc_attr( $order ); ?>" title="<?php esc_html_e( 'Go to the first page', 'user-blocker' ); ?>">&laquo;</a>
					<a class="prev-page 
					<?php
					if ( '1' == $paged ) {
						echo 'disabled';}
					?>
					" href="<?php echo '?page=all_type_blocked_user_list&paged=' . esc_attr( $prev_page ) . '&role=' . esc_attr( $srole ) . '&txtUsername=' . esc_attr( $txt_username ); ?>&orderby=<?php echo esc_attr( $orderby ); ?>&order=<?php echo esc_attr( $order ); ?>" title="<?php esc_html_e( 'Go to the previous page', 'user-blocker' ); ?>">&lsaquo;</a>
					<span class="paging-input">
						<input class="current-page" type="text" size="1" value="<?php echo esc_attr( $paged ); ?>" name="paged" title="Current page">
						<?php esc_html_e( 'of', 'user-blocker' ); ?>
						<span class="total-pages"><?php echo esc_html( $total_pages ); ?></span>
					</span>
					<a class="next-page 
					<?php
					if ( $paged == $total_pages ) {
						echo 'disabled';}
					?>
					" href="<?php echo '?page=all_type_blocked_user_list&paged=' . esc_attr( $next_page ) . '&role=' . esc_attr( $srole ) . '&txtUsername=' . esc_attr( $txt_username ); ?>&orderby=<?php echo esc_attr( $orderby ); ?>&order=<?php echo esc_attr( $order ); ?>" title="<?php esc_html_e( 'Go to the next page', 'user-blocker' ); ?>">&rsaquo;</a>
					<a class="last-page 
					<?php
					if ( $paged == $total_pages ) {
						echo 'disabled';}
					?>
					" href="<?php echo '?page=all_type_blocked_user_list&paged=' . esc_attr( $total_pages ) . '&role=' . esc_attr( $srole ) . '&txtUsername=' . esc_attr( $txt_username ); ?>&orderby=<?php echo esc_attr( $orderby ); ?>&order=<?php echo esc_attr( $order ); ?>" title="<?php esc_html_e( 'Go to the last page', 'user-blocker' ); ?>">&raquo;</a>
				</span>
				<input style="display: none;" id="sbtPages" class="button" type="submit" value="sbtPages" name="filter_action">
			</div>
			<?php
		}
	}
}

if ( ! function_exists( 'ublk_search_field' ) ) {
	/**
	 * Search Field.
	 *
	 * @param string $display Display.
	 * @param string $txt_username Username.
	 * @param string $tab Tab.
	 */
	function ublk_search_field( $display, $txt_username, $tab ) {
		?>
		<div class="actions">
			<div class="filter_div" 
			<?php
			if ( 'roles' == $display ) {
				echo 'style="display: none"';}
			?>
			>
				<input type="hidden" value="<?php echo esc_attr( $tab ); ?>" name="page" />
				<input type="text" id="txtUsername" value="<?php echo esc_attr( $txt_username ); ?>" placeholder="<?php esc_attr_e( 'Username or Email or Name', 'user-blocker' ); ?>" name="txtUsername" />
				<input id="filter_action" class="button" type="submit" value="<?php esc_html_e( 'Search', 'user-blocker' ); ?>" name="filter_action">
				<a class="button" href="<?php echo '?page=' . esc_attr( $tab ); ?>" style="margin-left: 10px;"><?php esc_html_e( 'Reset', 'user-blocker' ); ?></a>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'ublk_user_search_field' ) ) {
	/**
	 * Search Field.
	 *
	 * @param string $display_users Display Users.
	 * @param string $txt_username Username.
	 * @param string $tab Tab.
	 */
	function ublk_user_search_field( $display_users, $txt_username, $tab ) {
		?>
		<div class="actions">
			<div class="filter_div" 
			<?php
			if ( 1 == $display_users ) {
				echo 'style="display: block;"';
			} else {
				echo 'style="display: none;"';
			}
			?>
			>
				<input type="hidden" value="<?php echo esc_attr( $tab ); ?>" name="page" />
				<input type="text" id="txtUsername" value="<?php echo esc_attr( $txt_username ); ?>" placeholder="<?php esc_attr_e( 'Username or Email or Name', 'user-blocker' ); ?>" name="txtUsername" />
				<input id="filter_action" class="button" type="submit" value="<?php esc_html_e( 'Search', 'user-blocker' ); ?>" name="filter_action">
				<a class="button" href="<?php echo '?page=' . esc_attr( $tab ) . '&resetsearch=1'; ?>" style="margin-left: 10px;"><?php esc_html_e( 'Reset', 'user-blocker' ); ?></a>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'ublk_bulk_actions_dropdown' ) ) {
	/**
	 * Bulk Actions Dropdown.
	 *
	 * @param string $display Display.
	 * @param string $txt_username Username.
	 * @param int    $user_id User ID.
	 */
	function ublk_bulk_actions_dropdown( $display, $txt_username, $user_id ) {
		?>
		<div class="ublk_actions">
			<select name="ublk_bulk_actions" id="ublk_bulk_actions">
				<option value=""><?php esc_html_e( 'Bulk Actions', 'user-blocker' ); ?></option>
				<option value="edit" <?php echo selected( $display, 'edit' ); ?>><?php esc_html_e( 'Edit', 'user-blocker' ); ?></option>
			</select>
			<a class="ublk_bulk_btn button" href="#" style="margin-left: 10px;"><?php esc_html_e( 'Apply', 'user-blocker' ); ?></a>
			<input type="hidden" name="blk_username_role" id="blk_username_role">
		</div>
		<?php
	}
}

add_action( 'delete_user', 'ublk_update_block_user_role', 15, 1 );
add_action( 'user_register', 'ublk_update_block_user_role', 20, 1 );
add_action( 'edit_user_profile_update', 'ublk_update_block_user_role', 25, 1 );

if ( ! function_exists( 'ublk_update_block_user_role' ) ) {
	/**
	 * Update Block User-Role.
	 *
	 * @param int $user_id User ID.
	 */
	function ublk_update_block_user_role( $user_id ) {
		$nonce             = ( isset( $_POST['_wpnonce'] ) && ! empty( $_POST['_wpnonce'] ) ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
		$create_user_nonce = ( isset( $_POST['_wpnonce_create-user'] ) && ! empty( $_POST['_wpnonce_create-user'] ) ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce_create-user'] ) ) : '';
		$add_user_nonce    = ( isset( $_POST['_wpnonce_add-user'] ) && ! empty( $_POST['_wpnonce_add-user'] ) ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce_add-user'] ) ) : '';

		if (
			wp_verify_nonce( $nonce, 'update-user_' . $user_id ) ||
			wp_verify_nonce( $create_user_nonce, 'create-user' ) ||
			wp_verify_nonce( $add_user_nonce, 'add-user' ) ||
			wp_verify_nonce( $nonce, 'delete-users' ) ||
			wp_verify_nonce( $nonce, 'remove-users' )
		) {
			$user_data = get_user_by( 'id', $user_id );
			if ( isset( $_POST['role'] ) ) {
				$role = sanitize_text_field( wp_unslash( $_POST['role'] ) );
			}
			if ( '' == $role ) {
				if ( ! empty( $user_data->roles ) && is_array( $user_data->roles ) ) {
					$role = $user_data->roles[0];
				}
			}

			$block_msg_day  = get_option( $role . '_block_msg_day' );
			$block_msg_date = get_option( $role . '_block_msg_date' );
			$block_url_day  = get_option( $role . '_block_url_day' );
			$block_url_date = get_option( $role . '_block_url_date' );

			$is_active = get_user_meta( $user_id, 'is_active', true );
			if ( 'n' != $is_active ) {
				if ( ! empty( $block_msg_day ) ) {
					update_user_meta( $user_id, 'block_msg_day', esc_html( $block_msg_day ) );
				} else {
					update_user_meta( $user_id, 'block_msg_day', '' );
				}
				if ( ! empty( $block_msg_date ) ) {
					update_user_meta( $user_id, 'block_msg_date', esc_html( $block_msg_date ) );
				} else {
					update_user_meta( $user_id, 'block_msg_date', '' );
				}
				if ( ! empty( $block_url_day ) ) {
					update_user_meta( $user_id, 'block_url_day', esc_url( $block_url_day ) );
				} else {
					update_user_meta( $user_id, 'block_url_day', '' );
				}
				if ( ! empty( $block_url_date ) ) {
					update_user_meta( $user_id, 'block_url_date', esc_url( $block_url_date ) );
				} else {
					update_user_meta( $user_id, 'block_url_date', '' );
				}
			}
		}
	}
}

/**
 * Block User Settings Page.
 */
function ublk_block_user_setting_page() {

	$msg       = '';
	$msg_class = '';
	if ( isset( $_POST['submit_display'] ) && isset( $_POST['_wp_ub_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wp_ub_settings_nonce'] ) ), '_wp_ub_settings_action' ) ) {
		$msg_class = 'updated';
		if ( isset( $_POST['ub_delete_data'] ) ) {
			update_option( 'ub_delete_data', '1' );
		} else {
			update_option( 'ub_delete_data', '0' );
		}
		$msg = esc_html__( 'User blocker plugin setting has been updated successfully', 'user-blocker' );
	}
	$ub_delete_data = get_option( 'ub_delete_data', 0 );
	?>
	<div class="wrap">
	<?php
	if ( '' != $msg ) {
		?>
			<div class="ublocker-notice <?php echo esc_attr( $msg_class ); ?>">
				<p><?php echo esc_html( $msg ); ?></p>
			</div>
					<?php
	}
	?>
		<h2 class="ublocker-page-title mg-bpttom-20"><?php esc_html_e( 'User Blocker Plugin Settings', 'user-blocker' ); ?></h2>
		<div class="cover_form">
			<form id="frmubsetting" name="frmubsetting" method="post" action="">
				<div class="ub-setting-wrap">
					<div class="ub-left-setting">
						<h4><?php esc_html_e( 'Delete data on deletion of plugin', 'user-blocker' ); ?></h4>
					</div>
					<div class="ub-right-setting">
						<input id="ub_delete_data" type="checkbox" value="1" <?php checked( '1', $ub_delete_data ); ?> name="ub_delete_data">&nbsp;<label for="ub_delete_data"><?php echo esc_html_e( 'Delete data on deletion of plugin.', 'user-blocker' ); ?></label>
					</div>
				</div>
	<?php wp_nonce_field( '_wp_ub_settings_action', '_wp_ub_settings_nonce' ); ?>
				<p class="submit">
					<input id="submit" class="button button-primary" type="submit" value="<?php esc_html_e( 'Save Changes', 'user-blocker' ); ?>" name="submit_display">
				</p>
			</form>
		</div>
	</div>
	<?php
}
