<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'CRPRO_Review_Reminder_Settings' ) ) :

	class CRPRO_Review_Reminder_Settings {

		private static $help_country;
		private static $help_delay;
		private static $help_delay2;
		private static $help_send_at;
		private static $button_edit;
		private static $button_delete;
		private static $not_set;
		private static $def_sending_delay = 5;
		private static $def_sending_delay2 = 10;
		private static $sending_delay_error0;
		private static $sending_delay_error1;

		public function __construct() {
			self::$help_country = __( 'A country where a customer\'s order is shipped to.', 'customer-reviews-woocommerce-pro' );
			self::$help_delay = __( 'Emails will be sent N days after the order status is changed to \'Completed\' or other value specified in the settings. N is a sending delay that needs to be defined in this field.', 'customer-reviews-woocommerce-pro' );
			self::$help_delay2 = __( 'Second reminder emails will be sent N days after the order status is changed to \'Completed\' or other value specified in the settings. N is a sending delay that needs to be defined in this field. If a customer leaves a review after the first reminder, the second reminder will not be sent.', 'customer-reviews-woocommerce-pro' );
			self::$help_send_at = __( 'Time for sending review reminders. It is recommended to choose time when your customers are more likely to check their emails.', 'customer-reviews-woocommerce-pro' );
			self::$not_set = __( 'Not set', 'customer-reviews-woocommerce-pro' );
			self::$sending_delay_error0 = __( 'The sending delay field cannot be blank.', 'customer-reviews-woocommerce-pro' );
			self::$sending_delay_error1 = __( 'The sending delays could not be saved correctly because the time set for a later review reminder was shorter than the time set for an earlier one.', 'customer-reviews-woocommerce-pro' );

			self::$button_edit = '<button type="button" class="crpro-countries-button-edit"><span class="dashicons dashicons-edit"></span></button>';
			self::$button_delete = '<button type="button" class="crpro-countries-button-delete"><span class="dashicons dashicons-no-alt"></span></button>';

			add_filter( 'cr_settings_review_reminder', array( $this, 'extra_review_reminder_settings' ) );
			add_filter( 'cr_settings_review_reminder_sections', array( $this, 'display_section' ), 10, 2 );
			add_filter( 'cr_settings_review_reminder_sections_save', array( $this, 'save_countries_section' ), 10, 2 );
			add_action( 'woocommerce_admin_field_crpro_country_delays', array( $this, 'show_country_delays' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );
			add_action( 'cr_save_settings_review_reminder', array( $this, 'save_settings' ) );
			add_filter( 'cr_available_channels', array( $this, 'extra_channels' ) );
			add_filter( 'cr_max_sending_delays', array( $this, 'max_sending_delays' ) );
			add_filter( 'cr_sending_delays', array( $this, 'sending_delays' ) );
			add_filter( 'cr_sending_delay_label', array( $this, 'delay_label' ), 10, 2 );
			add_filter( 'cr_sending_delay_enabled', array( $this, 'delay_enabled' ), 10, 5 );
			add_filter( 'cr_save_sending_delays', array( $this, 'save_delays' ) );
		}

		public function extra_review_reminder_settings( $settings ) {
			if( 'wp' === get_option( 'ivole_scheduler_type', 'wp' )  ) {
				// Send At
				$times = self::get_send_at_options();
				$settings[16] = array(
					'title' => __( 'Send At', 'customer-reviews-woocommerce-pro' ),
					'type' => 'select',
					'desc' => self::$help_send_at,
					'default'  => 'nopref',
					'id' => 'ivole_sending_time',
					'desc_tip' => true,
					'class'    => 'wc-enhanced-select',
					'options'  => $times,
					'autoload' => false
				);
				$settings[17] = array(
					'title' => __( 'Country-Specific Sending Delays', 'customer-reviews-woocommerce-pro' ),
					'type' => 'crpro_country_delays',
					'desc' => __( 'Customize sending delays and times for review reminders depending on countries where customers are based.', 'customer-reviews-woocommerce-pro' ),
					'desc_tip' => true
				);
				$settings[65] = array(
					'title' => __( 'Stop Review Reminders After', 'customer-reviews-woocommerce-pro' ),
					'type' => 'select',
					'desc' => __( 'Use this option to stop sending new review invitations to customers after they reviewed one of their previous orders. The limit can be set on a customer level to stop sending new review invitations after a customer reviewed anything from your store. Or it can be set on a customer and product level to exclude products previously reviewed by a customer from new review invitations.', 'customer-reviews-woocommerce-pro' ),
					'default'  => 'no',
					'id' => 'ivole_stop_reminders',
					'desc_tip' => true,
					'class'    => 'wc-enhanced-select',
					'css'      => 'min-width:300px;',
					'options'  => array(
						'no' => __( 'No Limit', 'customer-reviews-woocommerce-pro' ),
						'cu' => __( 'One Review per Customer', 'customer-reviews-woocommerce-pro' ),
						'pr' => __( 'One Review per Product and Customer', 'customer-reviews-woocommerce-pro' )
					),
					'autoload' => false
				);
			}

			// Exclude Emails
			$settings[86] = array(
				'title'   => __( 'Exclude Emails', 'customer-reviews-woocommerce-pro' ),
				'desc'    => __( 'A comma-separated list of keywords that will be used to check customer emails. If an email contains any of the keywords, the plugin will not send a review reminder to that customer. For example, \'keyword1,keyword2\' setting will prevent sending review reminders to customers whose emails contain \'keyword1\' or \'keyword2\'.', 'customer-reviews-woocommerce-pro' ),
				'desc_tip' => true,
				'id'      => 'ivole_exclude_emails',
				'default' => '',
				'type'    => 'text'
			);

			return $settings;
		}

		public function show_country_delays( $field ) {
			$countries = get_option( 'ivole_country_delays', '' );
			$country_settings_summary = esc_html( __( 'No country-specific settings maintained', 'customer-reviews-woocommerce-pro' ) );
			if( is_array( $countries ) && 0 < count( $countries ) ) {
				$count_countries = count( $countries );
				$country_settings_summary = esc_html( sprintf( _n( 'Settings for %d country maintained', 'Settings for %d countries maintained', $count_countries, 'customer-reviews-woocommerce-pro' ), $count_countries ) );
			}
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $field['id'] ); ?>"><?php echo esc_html( $field['title'] ); ?>
						<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $field['desc'] ); ?>"></span>
					</label>
				</th>
				<td class="forminp forminp-<?php echo sanitize_title( $field['type'] ) ?>">
					<div>
						<span class="cr-settings-manage-span"><?php echo $country_settings_summary; ?></span>
						<a class="button cr-settings-manage-button" href="<?php echo esc_url( admin_url( 'admin.php?page=cr-reviews-settings&tab=review_reminder&section=countries' ) ); ?>"><?php echo esc_html( __( 'Manage', 'customer-reviews-woocommerce-pro' ) ); ?></a>
					</div>
				</td>
			</tr>
			<?php
		}

		public function display_section( $section, $section_slug ) {
			if( 'countries' === $section_slug ) {
				ob_start();

				echo '<h2>' . esc_html( __( 'Country-Specific Sending Delays', 'customer-reviews-woocommerce-pro' ) );
				wc_back_link( __( 'Return to settings', 'customer-reviews-woocommerce-pro' ), admin_url( 'admin.php?page=cr-reviews-settings&tab=review_reminder' ) );
				echo '<button type="button" class="page-title-action cr-button-heading crpro-button-add-country">' . __( 'Add Country', 'customer-reviews-woocommerce-pro' ) . '</button>';
				echo '</h2>';

				echo '<table class="form-table">';
				self::display_countries_table();
				echo '</table>';

				self::output_modal_template();
				self::output_modal_delete_confirmation();

				$section = ob_get_clean();
			}

			return $section;
		}

		public function save_countries_section( $section, $section_slug ) {
			if( 'countries' === $section_slug ) {
				$section = true;
			}
			return $section;
		}

		public static function display_countries_table() {
			$countries = get_option( 'ivole_country_delays', array() );
			?>
			<tr valign="top">
				<td class="crpro-countries-settings-wrapper cr-settings-table-td" colspan="2">
					<input type="hidden" name="ivole_country_delays" id="ivole_country_delays" value="<?php echo esc_attr( json_encode( $countries ) ); ?>" />
					<table class="widefat crpro-countries-table" cellspacing="0">
						<thead>
							<tr>
								<?php
								$columns = array(
										'country' => array(
										'title' => __( 'Country', 'customer-reviews-woocommerce-pro' ),
										'help' => self::$help_country
									),
									'delay' => array(
										'title' => __( 'Sending Delay (Days)', 'customer-reviews-woocommerce-pro' ),
										'help' => self::$help_delay
									),
									'delay2' => array(
										'title' => __( '2nd Sending Delay (Days)', 'customer-reviews-woocommerce-pro' ),
										'help' => self::$help_delay2
									),
									'sendat' => array(
										'title' => __( 'Send At', 'customer-reviews-woocommerce-pro' ),
										'help' => self::$help_send_at
									),
									'actions' => array(
										'title' => __( 'Action', 'customer-reviews-woocommerce-pro' ),
										'help' => ''
									)
								);
								foreach( $columns as $key => $column ) {
									echo '<th class="crpro-countries-settings-' . esc_attr( $key ) . '">';
									echo	esc_html( $column['title'] );
									if( $column['help'] ) {
										echo '<span class="woocommerce-help-tip" data-tip="' . esc_attr( $column['help'] ) . '"></span>';
									}
									echo '</th>';
								}
								?>
							</tr>
						</thead>
						<tbody>
							<?php
							if( is_array( $countries ) && 0 < count( $countries ) ) {
								$times = self::get_send_at_options();

								foreach( $countries as $country_key => $country ) {
									echo '<tr class="crpro-countries-tr">';

									foreach( $columns as $key => $column ) {
										switch( $key ) {
											case 'country':
												echo '<td data-country="' . $country_key . '">' . WC()->countries->countries[$country_key] . '</td>';
												break;
											case 'delay':
												echo '<td>' . $country['delay'] . '</td>';
												break;
											case 'delay2':
												$delay2enbld = false;
												$delay2 = self::$not_set;
												if ( isset( $country['delay2enbld'] ) && $country['delay2enbld'] ) {
													$delay2enbld = boolval( $country['delay2enbld'] );
												}
												if ( $delay2enbld && isset( $country['delay2'] ) && $country['delay2'] ) {
													$delay2 = $country['delay2'];
												}
												echo '<td data-delay2enbld="' . esc_attr( $delay2enbld ) . '">' . $delay2 . '</td>';
												break;
											case 'sendat':
												echo '<td data-sendat="' . $country['sendat'] . '">' . $times[$country['sendat']] . '</td>';
												break;
											case 'actions':
												echo '<td>' . self::$button_edit . self::$button_delete . '</td>';
												break;
											default:
												break;
										}
									}

									echo '</tr>';
								}
							} else {
								// no countries yet
								echo '<tr class="crpro-countries-empty"><td colspan="4">' . 'No settings yet' . '</td></tr>';
							}
							?>
						</tbody>
					</table>
				</td>
			</tr>
			<?php
		}

		public static function output_modal_template() {
			$countries_list = array();
			if( class_exists( 'WC_Countries' ) ) {
				$countries = new WC_Countries();
				$countries_list = $countries->get_countries();
			}
			$times = self::get_send_at_options();
			?>
			<div class="crpro-countries-modal-cont">
				<div class="crpro-countries-modal">
					<div class="crpro-countries-modal-internal">
						<div class="crpro-countries-modal-topbar">
							<h3 class="crpro-countries-modal-title"><?php _e( 'Add Country Settings', 'customer-reviews-woocommerce-pro' ); ?></h3>
							<button type="button" class="crpro-countries-modal-close-top">
								<span>×</span>
							</button>
						</div>
						<div class="crpro-countries-modal-section">
							<div class="crpro-countries-modal-section-row">
								<label for="crpro_select_country">
									<?php _e( 'Country', 'customer-reviews-woocommerce-pro' ); ?>
									<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( self::$help_country ); ?>"></span>
								</label>
								<select id="crpro_select_country">
									<?php
									foreach( $countries_list as $key => $value ) {
										echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $value ) . '</option>';
									}
									?>
								</select>
							</div>
							<div class="crpro-countries-modal-section-row">
								<table class="widefat crpro-countries-modal-delays">
									<thead>
										<tr>
											<th></th>
											<th>
												<?php _e( 'Enabled', 'customer-reviews-woocommerce-pro' ); ?>
											</th>
											<th>
												<?php _e( 'Delay (Days)', 'customer-reviews-woocommerce-pro' ); ?>
												<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( self::$help_delay ); ?>"></span>
											</th>
										</tr>
									</thead>
									<tbody>
										<tr>
											<td>
												<?php _e( 'Review Reminder', 'customer-reviews-woocommerce-pro' ); ?>
											</td>
											<td class="crpro-countries-modal-delays-td"></td>
											<td class="crpro-countries-modal-delays-td">
												<input type="number" id="crpro_sending_delay" value="<?php echo esc_attr( self::$def_sending_delay ) ?>" min="0" max="999">
											</td>
										</tr>
										<tr>
											<td>
												<?php _e( '2nd Review Reminder', 'customer-reviews-woocommerce-pro' ); ?>
											</td>
											<td class="crpro-countries-modal-delays-td">
												<input type="checkbox" id="crpro_sending_delay2_enbld" value="1">
											</td>
											<td class="crpro-countries-modal-delays-td">
												<input type="number" id="crpro_sending_delay2" value="<?php echo esc_attr( self::$def_sending_delay2 ) ?>" min="0" max="999">
											</td>
										</tr>
									</tbody>
								</table>
							</div>
							<div class="crpro-countries-modal-section-row">
								<label for="crpro_send_at">
									<?php _e( 'Send At', 'customer-reviews-woocommerce-pro' ); ?>
									<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( self::$help_send_at ); ?>"></span>
								</label>
								<select id="crpro_send_at">
									<?php
									foreach( $times as $key => $value ) {
										echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $value ) . '</option>';
									}
									?>
								</select>
							</div>
						</div>
						<div class="crpro-countries-modal-bottombar">
							<div class="crpro-countries-modal-error"></div>
							<button type="button" class="crpro-countries-modal-cancel"><?php echo esc_html( __( 'Cancel', 'customer-reviews-woocommerce-pro' ) ); ?></button>
							<button type="button" class="crpro-countries-modal-save"><?php echo esc_html( __( 'Confirm', 'customer-reviews-woocommerce-pro' ) ); ?></button>
						</div>
					</div>
				</div>
			</div>
			<?php
		}

		public static function output_modal_delete_confirmation() {
			?>
			<div class="crpro-delete-modal-cont">
				<div class="crpro-delete-modal">
					<div class="crpro-delete-modal-internal">
						<div class="crpro-countries-modal-topbar">
							<h3 class="crpro-countries-modal-title"></h3>
							<button type="button" class="crpro-countries-modal-close-top">
								<span>×</span>
							</button>
						</div>
						<div class="crpro-countries-modal-section">
							<div class="crpro-countries-modal-section-row">
								<?php echo esc_html( __( 'Would you like to delete settings for this country?' ) ); ?>
							</div>
						</div>
						<div class="crpro-countries-modal-bottombar">
							<button type="button" class="crpro-countries-modal-cancel"><?php echo esc_html( __( 'Cancel', 'customer-reviews-woocommerce-pro' ) ); ?></button>
							<button type="button" class="crpro-countries-modal-save"><?php echo esc_html( __( 'Confirm', 'customer-reviews-woocommerce-pro' ) ); ?></button>
						</div>
					</div>
				</div>
			</div>
			<?php
		}

		public function enqueue_scripts_styles( $hook_suffix ) {
			// general scripts and styles
			wp_enqueue_style( 'crpro-admin-settings', plugins_url( '/css/admin-add-on.css', dirname( __FILE__ ) ), array(), CRPRO::CRPRO_VERSION  );
			wp_enqueue_script( 'crpro-admin-settings', plugins_url( '/js/admin-settings-add-on.js' , dirname( __FILE__ ) ), array( 'jquery' ), CRPRO::CRPRO_VERSION );
			wp_localize_script( 'crpro-admin-settings', 'crpro_object', array(
				'modal_new' => __( 'Add Country Settings', 'customer-reviews-woocommerce-pro' ),
				'modal_edit' => __( 'Edit Country Settings', 'customer-reviews-woocommerce-pro' ),
				'button_edit' => self::$button_edit,
				'button_delete' => self::$button_delete,
				'not_set' => self::$not_set,
				'def_sending_delay' => self::$def_sending_delay,
				'def_sending_delay2' => self::$def_sending_delay2,
				'sending_delay_error0' => self::$sending_delay_error0,
				'sending_delay_error1' => self::$sending_delay_error1
			) );
		}

		public static function get_send_at_options() {
			$times = array( 'nopref' => 'No preference' );
			for( $i=0; $i<24; $i++ ) {
				$times[sprintf( '%02d:00', $i )] = sprintf( '%02d:00 (%s)', $i, wp_timezone_string() );
			}
			return $times;
		}

		public function save_settings() {
			if ( ! empty( $_POST ) && isset( $_POST['ivole_country_delays'] ) ) {
				$_POST['ivole_country_delays'] = json_decode( stripslashes( $_POST['ivole_country_delays'] ), true );
				if( class_exists( 'WC_Admin_Settings' ) ) {
					$settings = array(
						array(
							'id' => 'ivole_country_delays',
							'type' => 'crpro_country_delays',
							'autoload' => false
						)
					);
					WC_Admin_Settings::save_fields( $settings );
				}
			}
		}

		public function extra_channels( $channels ) {
			if ( is_array( $channels ) ) {
				$channels[] = array(
					'id' => 'wa',
					'desc' => __( 'WhatsApp', 'customer-reviews-woocommerce-pro' )
				);
			}
			return $channels;
		}

		public function max_sending_delays( $max ) {
			return 2;
		}

		public function sending_delays( $reminders ) {
			if ( is_array( $reminders ) && 2 > count( $reminders ) ) {
				$reminders[] = array(
					'enabled' => false,
					'delay' => 10,
					'channel' => 'email'
				);
			}
			return $reminders;
		}

		public function delay_label( $label, $count ) {
			switch ( $count ) {
				case 1:
					$label = __( 'Second Reminder', 'customer-reviews-woocommerce-pro' );
					break;
				default:
					break;
			}
			return $label;
		}

		public function delay_enabled( $enabled, $count, $field, $key, $reminder ) {
			if ( 0 < $count ) {
				$is_enabled = false;
				if ( isset( $reminder['enabled'] ) && $reminder['enabled'] ) {
					$is_enabled = true;
				}
				$enabled = '<input name="' . esc_attr( $field['type'] . '_' . $key . '_' . $count ) .
					'" id="' . esc_attr( $field['type'] . '_' . $key . '_' . $count ) .
					'" type="checkbox" value="1" ' . checked( $is_enabled, true, false ) . '/>';
			}
			return $enabled;
		}

		public function save_delays( $delays ) {
			$cnt = count( $delays );
			if ( is_array( $delays ) && 1 < $cnt ) {
				$previous = 0;
				$display_error = false;
				for ( $i = 0; $i < $cnt; $i++ ) {
					if ( 0 < $i && $delays[$i]['delay'] <= $previous ) {
						$delays[$i]['delay'] = $previous + 1;
						$display_error = true;
					}
					$previous = $delays[$i]['delay'];
				}
				if ( $display_error ) {
					WC_Admin_Settings::add_error( self::$sending_delay_error1 );
				}
			}
			return $delays;
		}

	}

endif;
