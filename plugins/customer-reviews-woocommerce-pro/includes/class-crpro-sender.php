<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'CRPRO_Sender' ) ) :

	class CRPRO_Sender {

		public function __construct() {
			add_filter( 'cr_skip_reminder_internal', array( $this, 'skip_reminder_excluded_emails' ), 10, 2 );
			add_filter( 'cr_skip_reminder_internal', array( $this, 'skip_reminder_stop_reminders' ), 20, 2 );
			add_filter( 'cr_reminder_delay', array( $this, 'reminder_delay' ), 10, 3 );
			add_filter( 'cr_onsite_questions', array( $this, 'max_onsite_questions' ) );
			add_filter( 'cr_onsite_ratings', array( $this, 'max_onsite_ratings' ) );
			add_filter( 'cr_exclude_order_item', array( $this, 'exclude_order_item' ), 10, 3 );
			add_action( 'cr_wp_reminder_schedule', array( $this, 'schedule_reminders' ), 10, 2 );
			add_action( 'cr_wp_reminder_cancellation', array( $this, 'cancel_reminders' ), 10, 1 );
			add_action( 'cr_wp_reminder_refund', array( $this, 'refund_reminders' ), 10, 1 );
			add_filter( 'cr_reminders_table_type', array( $this, 'reminders_table_type' ), 10, 2 );
			add_filter( 'cr_reminders_table_type_name', array( $this, 'reminders_table_type_name' ), 10, 2 );
			add_filter( 'cr_reminders_table_type_log', array( $this, 'reminders_table_type_log' ), 10, 2 );
			add_filter( 'cr_reminders_log_type_desc', array( $this, 'reminders_log_type_desc' ), 10, 2 );
			add_action( 'cr_endpoint_review_posted', array( $this, 'endpoint_review_posted' ), 10, 1 );
			add_action( 'cr_manual_next_scheduled', array( $this, 'manual_next_scheduled' ), 10, 1 );
			add_action( 'cr_manual_unschedule', array( $this, 'manual_unschedule' ), 10, 1 );
			add_filter( 'cr_settings_email_template_subject', array( 'CRPRO_Emails_Settings', 'email_template_subject' ), 10, 2 );
			add_filter( 'cr_settings_email_template_heading', array( 'CRPRO_Emails_Settings', 'email_template_heading' ), 10, 2 );
			add_filter( 'cr_settings_email_template_body', array( 'CRPRO_Emails_Settings', 'email_template_body' ), 10, 2 );
		}

		public function skip_reminder_excluded_emails( $skip, $order ) {
			if ( $order && method_exists( $order, 'get_billing_email' ) ) {
				// check if there are any emails in the unsubscribe list
				$unsubscribe_list = get_option( 'ivole_unsubscribed_emails', array() );
				if ( ! is_array( $unsubscribe_list ) ) {
					$unsubscribe_list = array();
				}
				// check if there are any exclusion rules for emails
				$exclude_emails = trim( get_option( 'ivole_exclude_emails', '' ) );
				if (
					0 < count( $unsubscribe_list ) ||
					0 < strlen( $exclude_emails )
				) {
					$keywords = array();
					if ( 0 < strlen( $exclude_emails ) ) {
						$keywords = explode( ',', $exclude_emails );
					}

					// check if registered customers option is used
					$registered_customers = ( 'yes' === get_option( 'ivole_registered_customers', 'no' ) ) ? true : false;

					$email = '';
					$user = $order->get_user();
					if ( $registered_customers ) {
						if( $user ) {
							$email = $user->user_email;
						} else {
							$email = $order->get_billing_email();
						}
					} else {
						$email = $order->get_billing_email();
					}

					if ( $email ) {
						$email = mb_strtolower( trim( $email ) );

						// check if the email is in the list of unsubscribed emails
						if ( in_array( $email, $unsubscribe_list ) ) {
							$order->add_order_note(
								__( 'CR: a review reminder was not scheduled because the customer had unsubscribed from receiving emails.', 'customer-reviews-woocommerce-pro' )
							);
							return true;
						}

						// check if the email contains any of the keywords
						foreach( $keywords as $keyword ) {
							$keyword = trim( $keyword );
							if ( false !== strpos( $email, $keyword ) ) {
								$order->add_order_note(
									sprintf(
										__( 'CR: a review reminder was not scheduled because the email address contains a keyword \'%s\' that was configured as an exclusion in the settings.', 'customer-reviews-woocommerce-pro' ),
										$keyword
									)
								);
								return true;
							}
						}
					}
				}
			}
			return $skip;
		}

		public function reminder_delay( $timestamp, $order_id, $delay ) {
			// check send at setting
			$sendat = get_option( 'ivole_sending_time', 'nopref' );
			// check if there are any country-specific settings
			$countries = get_option( 'ivole_country_delays', '' );
			if( $countries && is_array( $countries ) && 0 < count( $countries ) ) {
				// find the shipping country from the order
				$shipping_country = apply_filters( 'woocommerce_get_base_location', get_option( 'woocommerce_default_country' ) );
				$order = wc_get_order( $order_id );
				if( method_exists( $order, 'get_shipping_country' ) ) {
					$tmp_shipping_country = $order->get_shipping_country();
					if( 0 < strlen( $tmp_shipping_country ) ) {
						$shipping_country = $tmp_shipping_country;
					}
				}
				if( isset( $countries[$shipping_country] ) ) {
					$delay = $countries[$shipping_country]['delay'];
					$sendat = $countries[$shipping_country]['sendat'];
					$timestamp = time() + $countries[$shipping_country]['delay'] * DAY_IN_SECONDS;
				}
			}
			// sending delay should be at least a day
			if( 1 <= $delay && 'nopref' !== $sendat ) {
				$dt = new DateTime();
				$dt->setTimestamp( $timestamp );
				$dt->setTimezone( wp_timezone() );
				if( 5 === strlen( $sendat ) ) {
					$dt->setTime(
						intval( substr( $sendat, 0, 2 ) ),
						intval( substr( $sendat, 3, 2 ) )
					);
					$timestamp = $dt->getTimestamp();
				}
			}

			return $timestamp;
		}

		public function max_onsite_questions( $max ) {
			return 100;
		}

		public function max_onsite_ratings( $max ) {
			return 100;
		}

		public function skip_reminder_stop_reminders( $skip, $order ) {
			$stop_reminders = get_option( 'ivole_stop_reminders', 'no' );
			if( 'wp' === get_option( 'ivole_scheduler_type', 'wp' )  ) {
				if (
					'cu' === $stop_reminders ||
					'pr' === $stop_reminders
				) {
					$email = '';
					$user = $order->get_user();

					// check if registered customers option is used
					$registered_customers = ( 'yes' === get_option( 'ivole_registered_customers', 'no' ) ) ? true : false;

					if ( $registered_customers ) {
						if( $user ) {
							$email = $user->user_email;
						} else {
							$email = $order->get_billing_email();
						}
					} else {
						$email = $order->get_billing_email();
					}

					if ( $email ) {
						$email = mb_strtolower( trim( $email ) );

						if ( 'cu' === $stop_reminders ) {
							// check if the customer has left any reviews earlier
							$args = array(
								'author_email' => $email,
								'meta_key' => 'rating',
								'type__not_in' => 'cr_qna',
								'count' => true
							);
							$count = get_comments( $args );
							if ( 0 < $count ) {
								$order->add_order_note(
									__( 'CR: a review reminder was not scheduled because the customer had already reviewed something else earlier.', 'customer-reviews-woocommerce-pro' )
								);
								return true;
							}
						} elseif ( 'pr' === $stop_reminders ) {
							// check if the customer has left any reviews for a specific product earlier
							$reviewed_everything = true;
							$items = $order->get_items();
							foreach ( $items as $item ) {
								if ( method_exists( $item, 'get_product_id' ) ) {
									$args = array(
										'author_email' => $email,
										'post__in' => array( $item->get_product_id() ),
										'meta_key' => 'rating',
										'type__not_in' => 'cr_qna',
										'count' => true
									);
									$count = get_comments( $args );
									if ( 0 === $count ) {
										$reviewed_everything = false;
									}
								}
							}
							if ( $reviewed_everything ) {
								$order->add_order_note(
									__( 'CR: a review reminder was not scheduled because the customer had already reviewed every product from this order earlier.', 'customer-reviews-woocommerce-pro' )
								);
								return true;
							}
						}
					}
				}
			}
			return $skip;
		}

		public function exclude_order_item( $exclude, $order, $item ) {
			if ( 'pr' === get_option( 'ivole_stop_reminders', 'no' ) ) {
				if ( method_exists( $item, 'get_product_id' ) ) {
					$email = '';
					$user = $order->get_user();

					// check if registered customers option is used
					$registered_customers = ( 'yes' === get_option( 'ivole_registered_customers', 'no' ) ) ? true : false;

					if ( $registered_customers ) {
						if( $user ) {
							$email = $user->user_email;
						} else {
							$email = $order->get_billing_email();
						}
					} else {
						$email = $order->get_billing_email();
					}

					if ( $email ) {
						$email = mb_strtolower( trim( $email ) );
						$args = array(
							'author_email' => $email,
							'post__in' => array( $item->get_product_id() ),
							'meta_key' => 'rating',
							'type__not_in' => 'cr_qna',
							'count' => true
						);
						$count = get_comments( $args );
						if ( 0 < $count ) {
							// if a customer has already reviewed this product before, exclude it from a review form
							$order->add_order_note(
								sprintf( __( 'CR: %s (ID: %s) was excluded from a review form because the customer had already reviewed it earlier.', 'customer-reviews-woocommerce-pro' ), $item->get_name(), $item->get_product_id() )
							);
							return true;
						}
					}
				}
			}
			return $exclude;
		}

		public function schedule_reminders( $order_id, $delay_channel ) {
			// schedule follow-up reminders if they are enabled in the settings
			if (
				is_array( $delay_channel ) &&
				1 < count( $delay_channel ) &&
				isset( $delay_channel[1]['delay'] )
			) {
				if ( ! wp_next_scheduled( 'ivole_send_reminder', array( $order_id, 2 ) ) ) {
					$delay = $delay_channel[1]['delay'];
					$timestamp = $this->country_delay_2nd_reminder(
						time() + $delay * DAY_IN_SECONDS,
						$order_id,
						$delay
					);
					$order = wc_get_order( $order_id );
					if ( false === wp_schedule_single_event( $timestamp, 'ivole_send_reminder', array( $order_id, 2 ) ) ) {
						$order->add_order_note( __( 'CR: a 2nd review reminder could not be scheduled.', 'customer-reviews-woocommerce-pro' ) );
					} else {
						$local_timestamp = get_date_from_gmt( date( 'Y-m-d H:i:s', $timestamp ), 'F j, Y g:i a (T)' );
						$order->add_order_note( sprintf( __( 'CR: a 2nd review reminder was successfully scheduled for %s.', 'customer-reviews-woocommerce-pro' ) , $local_timestamp ) );
					}
				} else {
					// a 2nd reminder for this order has already been scheduled
				}
			}
		}

		public function country_delay_2nd_reminder( $timestamp, $order_id, $delay2 ) {
			// check send at setting
			$sendat = get_option( 'ivole_sending_time', 'nopref' );
			// check if there are any country-specific settings
			$countries = get_option( 'ivole_country_delays', '' );
			if ( $countries && is_array( $countries ) && 0 < count( $countries ) ) {
				// find the shipping country from the order
				$shipping_country = apply_filters( 'woocommerce_get_base_location', get_option( 'woocommerce_default_country' ) );
				$order = wc_get_order( $order_id );
				if ( method_exists( $order, 'get_shipping_country' ) ) {
					$tmp_shipping_country = $order->get_shipping_country();
					if( 0 < strlen( $tmp_shipping_country ) ) {
						$shipping_country = $tmp_shipping_country;
					}
				}
				if ( isset( $countries[$shipping_country] ) ) {
					if (
						isset( $countries[$shipping_country]['delay2enbld'] ) &&
						$countries[$shipping_country]['delay2enbld'] &&
						isset( $countries[$shipping_country]['delay2'] )
					) {
						$delay2 = $countries[$shipping_country]['delay2'];
						$sendat = $countries[$shipping_country]['sendat'];
						$timestamp = time() + $countries[$shipping_country]['delay2'] * DAY_IN_SECONDS;
					}
				}
			}
			// sending delay should be at least a day
			if( 1 <= $delay2 && 'nopref' !== $sendat ) {
				$dt = new DateTime();
				$dt->setTimestamp( $timestamp );
				$dt->setTimezone( wp_timezone() );
				if( 5 === strlen( $sendat ) ) {
					$dt->setTime(
						intval( substr( $sendat, 0, 2 ) ),
						intval( substr( $sendat, 3, 2 ) )
					);
					$timestamp = $dt->getTimestamp();
				}
			}

			return $timestamp;
		}

		public function reminders_table_type( $type, $event ) {
			if (
				isset( $event['args'] ) &&
				is_array( $event['args'] ) &&
				1 < count( $event['args'] )
			) {
				if ( 2 === $event['args'][1] ) {
					$type = $event['args'][1];
				}
			}
			return $type;
		}

		public function reminders_table_type_name( $type_name, $type ) {
			if ( 2 === $type ) {
				$type_name = __( 'Automatic (2nd)', 'customer-reviews-woocommerce-pro' );
			}
			return $type_name;
		}

		public function reminders_table_type_log( $type_log, $type ) {
			if ( 2 === $type ) {
				$type_log = 'a2';
			}
			return $type_log;
		}

		public function reminders_log_type_desc( $description, $type ) {
			if ( 'a2' === $type ) {
				$description = __( 'Automatic (2nd)', 'customer-reviews-woocommerce-pro' );
			}
			return $description;
		}

		public function endpoint_review_posted( $order_id ) {
			// possible cron job arguments for sending reminders (1st or 2nd reminder)
			$cron_args = array(
				array(
					array( $order_id ),
					'a'
				),
				array(
					array( $order_id, 2 ),
					'a2'
				)
			);
			$verification = '';
			foreach ( $cron_args as $cron_arg ) {
				// check if a reminder is scheduled, then cancel it
				if ( wp_next_scheduled( 'ivole_send_reminder', $cron_arg[0] ) ) {
					wp_clear_scheduled_hook( 'ivole_send_reminder', $cron_arg[0] );
					// logging
					$ord = wc_get_order( $order_id );
					if ( ! $verification ) {
						$mailer = get_option( 'ivole_mailer_review_reminder', 'cr' );
						$verification = ( 'wp' === $mailer ) ? 'local' : 'verified';
					}
					$log = new CR_Reminders_Log();
					$l_result = $log->add(
						$order_id,
						$cron_arg[1],
						'email',
						array(
							200,
							__( 'Review reminder was canceled automatically after a customer posted a review', 'customer-reviews-woocommerce-pro' ),
							array(
								'data' => array(
									'email' => array(
										'to' => Ivole_Email::get_customer_email( $ord )
									),
									'customer' => array(
										'firstname' => $ord->get_billing_first_name(),
										'lastname' => $ord->get_billing_last_name()
									),
									'verification' => $verification,
									'language' => Ivole_Email::fetch_language_trnsl( $order_id, $ord )
								)
							)
						)
					);
					// end of logging
				}
			}
		}

		public function manual_next_scheduled( $order ) {
			$timestamp = wp_next_scheduled( 'ivole_send_reminder', array( $order->get_id(), 2 ) );
			if ( $timestamp ) {
				echo ';<br> ';
				if( $timestamp >= 0 ) {
					$local_timestamp = get_date_from_gmt( date( 'Y-m-d H:i:s', $timestamp ), get_option( 'date_format' ) . ', ' . get_option( 'time_format' ) . ' (T)' );
					echo esc_html( __( 'A 2nd reminder is scheduled for ', 'customer-reviews-woocommerce-pro' ) . $local_timestamp );
				} else {
					echo esc_html__( 'WP Cron error', 'customer-reviews-woocommerce-pro' );
				}
			}
		}

		public function manual_unschedule( $order_id ) {
			$timestamp = wp_next_scheduled( 'ivole_send_reminder', array( $order_id, 2 ) );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, 'ivole_send_reminder', array( $order_id, 2 ) );
			}
		}

		public function cancel_reminders( $order_id ) {
			$timestamp = wp_next_scheduled( 'ivole_send_reminder', array( $order_id, 2 ) );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, 'ivole_send_reminder', array( $order_id, 2 ) );
				$order = new WC_Order( $order_id );
				$order->add_order_note( __( 'CR: a 2nd reminder was cancelled because the order was cancelled.', 'customer-reviews-woocommerce-pro' ) );
			}
		}

		public function refund_reminders( $order_id ) {
			$timestamp = wp_next_scheduled( 'ivole_send_reminder', array( $order_id, 2 ) );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, 'ivole_send_reminder', array( $order_id, 2 ) );
				$order = new WC_Order( $order_id );
				$order->add_order_note( __( 'CR: a 2nd reminder was cancelled because the order was refunded.', 'customer-reviews-woocommerce-pro' ) );
			}
		}

	}

endif;
