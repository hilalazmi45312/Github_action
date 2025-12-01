<?php

/**
 * Fired during plugin activation
 *
 * @link       https://theeventprime.com
 * @since      1.0.0
 *
 * @package    Eventprime_Event_Calendar_Management
 * @subpackage Eventprime_Event_Calendar_Management/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Eventprime_Event_Calendar_Management
 * @subpackage Eventprime_Event_Calendar_Management/includes
 * @author     EventPrime <support@metagauss.com>
 */
class Eventprime_Event_Calendar_Management_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public function activate() {
           if ( is_multisite() ) {
                    $blog_id = get_current_blog_id();
                    switch_to_blog( $blog_id );
                    if( ! get_option( 'ep_update_revamp_version' ) ) {
                            $this->ep_update_new_global_settings();
                    }
                    $this->ep_update_global_settings();
                    $this->create_pages();
                    $this->default_settings();
                    $this->ep_create_table();
                    restore_current_blog();
                    // set custom capabilities
                    $this->ep_add_custom_capabilities();
            } else {
                    if( ! get_option( 'ep_update_revamp_version' ) ) {
                            $this->ep_update_new_global_settings();
                    }
                    $this->ep_update_global_settings();
                    $this->create_pages();
                    $this->default_settings();
                    $this->ep_create_table();
                    // set custom capabilities
                    $this->ep_add_custom_capabilities();
            }

            update_option( 'event_magic_do_activation_redirect', true );
	}
        
        public function ep_update_global_settings() {
            $global_settings = new Eventprime_Global_Settings;
            $setting_options = (object)$global_settings->setting_options;
            $global_options = (object)get_option( 'em_global_settings' );
            $default_settings = (object) array_merge((array) $setting_options, (array) $global_options);
            update_option('em_global_settings', $default_settings );
        }
        
        public function upgrade_db()
        {
            
        }
        
   
        
        public function get_db_table_name( $identifier ) {
		global $wpdb;
		$plugin_prefix    = $wpdb->prefix . 'eventprime_';
		$alternate_plugin_prefix = $wpdb->prefix . 'em_';
                $wordpress_prefix = $wpdb->prefix;

		switch ( $identifier ) {
			case 'CHECKOUT_FIELDS':
				$table_name = $plugin_prefix . 'checkout_fields';
				break;
                        case 'TICKET_CATEGORIES':
				$table_name = $plugin_prefix . 'ticket_categories';
				break;
                        case 'TICKET':
                            $table_name = $alternate_plugin_prefix . 'price_options';
				break;
                        case 'POSTS':
                            $table_name = $wordpress_prefix . 'posts';
				break;
			default:
				$classname = "EP_Helper_$identifier";
				if ( class_exists( $classname ) ) {
					$externalclass = new $classname();
					$table_name    = $externalclass->get_db_table_name( $identifier );
				} else {
					return false; }
		}
		return $table_name;
	}
        
        public function get_db_table_unique_field_name( $identifier ) {

		switch ( $identifier ) {
			case 'CHECKOUT_FIELDS':
				$unique_field_name = 'id';
				break;
			case 'TICKET_CATEGORIES':
				$unique_field_name = 'id';
				break;
                        case 'TICKET':
				$unique_field_name = 'id';
				break;
			default:
				$classname = "EP_Helper_$identifier";
				if ( class_exists( $classname ) ) {
					$externalclass     = new $classname();
					$unique_field_name = $externalclass->get_db_table_unique_field_name( $identifier );
				} else {
					return false; }
		}
		return $unique_field_name;
	}

	public function get_db_table_field_type( $identifier, $field ) {
		$functionname = 'get_field_format_type_' . $identifier;
		if ( method_exists( 'Eventprime_Event_Calendar_Management_Activator', $functionname ) ) {
			$format = $this->$functionname( $field );
		} else {
			$classname = "EP_Helper_$identifier";
			if ( class_exists( $classname ) ) {
				$externalclass = new $classname();
				$format        = $externalclass->get_db_table_field_type( $identifier, $field );
			} else {
				return false; }
		}
		return $format;
	}
        
        public function get_field_format_type_CHECKOUT_FIELDS( $field ) {
		switch ( $field ) {
			case 'id':
				$format = '%d';
				break;
			case 'priority':
				$format = '%d';
				break;
                        case 'status':
                                $format = '%d';
				break;
                        case 'created_by':
                                $format = '%d';
				break;
                        case 'last_updated_by':
                                $format = '%d';
				break;
			default:
				$format = '%s';
                                break;
		}
		return $format;
	}

        public function get_field_format_type_TICKET_CATEGORIES( $field ) {
		switch ( $field ) {
			case 'name':
				$format = '%s';
				break;
			case 'created_at':
				$format = '%s';
				break;
			case 'updated_at':
					$format = '%s';
				break;
			case 'cat_misc_data':
					$format = '%s';
				break;
			default:
				$format = '%d';
                                break;
		}
		return $format;
	}

        public function get_field_format_type_TICKET( $field ) {
		switch ( $field ) {
			case 'id':
				$format = '%d';
				break;
			case 'event_id':
				$format = '%d';
				break;
                        case 'capacity':
                                $format = '%d';
				break;
                        case 'is_default':
                                $format = '%d';
				break;
                        case 'is_event_price':
                                $format = '%d';
				break;
                        case 'priority':
                                $format = '%d';
				break;
                        case 'capacity_progress_bar':
                                $format = '%d';
				break;
                        case 'status':
                                $format = '%d';
				break;
                        case 'parent_price_option_id':
                                $format = '%d';
				break;
                        case 'category_id':
                                $format = '%d';
				break;
                        case 'allow_cancellation':
                                $format = '%d';
				break;
                        case 'show_remaining_tickets':
                                $format = '%d';
				break;
                        case 'show_ticket_booking_dates':
                                $format = '%d';
				break;
                        case 'ticket_template_id':
                                $format = '%d';
				break;
			default:
				$format = '%s';
                                break;
		}
		return $format;
	}

        
        /**
	 * Update the global settings
	 */
	public function ep_update_new_global_settings() {
		$global_options = (object)get_option( 'em_global_settings' );
		if( ! empty( $global_options ) ) {
			foreach( $global_options as $key => $val ){
				$global_options->$key = $val;
			}
        	update_option('em_global_settings', $global_options );
                update_option( 'ep_update_revamp_version', 1 );
		update_option( 'ep_db_need_to_run_migration', 0 );
		}
	}


        /**
	 * Method for create default pages
	 */
        
        
        public function create_pages() {
		$global_options = get_option('em_global_settings');
		if( empty( $global_options ) ) {
			$global_settings = new Eventprime_Global_Settings;
                        $global_options = $global_settings->ep_get_settings();
		}
                
		$pages['em_performers']           = array(
			'post_type'    => 'page',
			'post_title'   => 'Performers',
			'post_status'  => 'publish',
			'post_name'    => '',
			'post_content' => '[em_performers]',
		);
		$pages['em_sites']              = array(
			'post_type'    => 'page',
			'post_title'   => 'Venues',
			'post_status'  => 'publish',
			'post_name'    => '',
			'post_content' => '[em_sites]',
		);
		$pages['em_events']             = array(
			'post_type'    => 'page',
			'post_title'   => 'Events',
			'post_status'  => 'publish',
			'post_name'    => 'all-events',
			'post_content' => '[em_events]',
		);
		$pages['em_booking']              = array(
			'post_type'    => 'page',
			'post_title'   => 'Booking',
			'post_status'  => 'publish',
			'post_name'    => '',
			'post_content' => '[em_booking]',
		);
		$pages['em_profile']            = array(
			'post_type'    => 'page',
			'post_title'   => 'User Profile',
			'post_status'  => 'publish',
			'post_name'    => '',
			'post_content' => '[em_profile]',
		);
		$pages['em_event_types']    = array(
			'post_type'    => 'page',
			'post_title'   => 'Event Types',
			'post_status'  => 'publish',
			'post_name'    => '',
			'post_content' => '[em_event_types]',
		);
		$pages['em_event_submit_form']        = array(
			'post_type'    => 'page',
			'post_title'   => 'Submit Event',
			'post_status'  => 'publish',
			'post_name'    => '',
			'post_content' => '[em_event_submit_form]',
		);
                $pages['em_booking_details']      = array(
                        'post_type'    => 'page',
                        'post_title'   => 'Booking Details',
                        'post_status'  => 'publish',
                        'post_name'    => '',
                        'post_content' => '[em_booking_details]',
                );
                $pages['em_event_organizers'] = array(
                        'post_type'    => 'page',
                        'post_title'   => 'Event Organizers',
                        'post_status'  => 'publish',
                        'post_name'    => '',
                        'post_content' => '[em_event_organizers]',
                );
                $pages['em_login'] = array(
                        'post_type'    => 'page',
                        'post_title'   => 'Login',
                        'post_status'  => 'publish',
                        'post_name'    => '',
                        'post_content' => '[em_login]',
                );
                $pages['em_register'] = array(
                        'post_type'    => 'page',
                        'post_title'   => 'Register',
                        'post_status'  => 'publish',
                        'post_name'    => '',
                        'post_content' => '[em_register]',
                );
				// The Query
				foreach ( $pages as $key => $page ) {
					$string   = '[' . $key;
					$my_query = new WP_Query(
						array(
							'post_type'   => 'any',
							'post_status' => 'publish',
							's'           => $string,
							'fields'      => 'ids',
						)
					);
					if ( empty( $my_query->posts ) ) {
						$page_id[ $key ] = wp_insert_post( $page );  
                                        } 
				}
                                if(isset($page_id) && !empty($page_id))
                                {
                                    foreach ( $page_id as $key => $id ) {
					if ( $key == 'em_performers' ) {
						$field = 'performers_page';
					}
					if ( $key == 'em_sites' ) {
						$field = 'venues_page';
					}
					if ( $key == 'em_events' ) {
						$field = 'events_page';
					}
					if ( $key == 'em_booking' ) {
						$field = 'booking_page';
					}
					if ( $key == 'em_profile' ) {
						$field = 'profile_page';
					}
					if ( $key == 'em_event_types' ) {
						$field = 'event_types';
					}
					if ( $key == 'em_event_submit_form' ) {
						$field = 'event_submit_form';
					}
					if ( $key == 'em_booking_details' ) {
						$field = 'booking_details_page';
					}
					if ( $key == 'em_event_organizers' ) {
						$field = 'event_organizers';
					}
                                        if ( $key == 'em_login' ) {
						$field = 'login_page';
					}
                                        if ( $key == 'em_register' ) {
						$field = 'register_page';
					}
					$global_options->$field = $id;
					
				}
                                    update_option( 'em_global_settings',$global_options );
                                }
				
	}
        
        
	

	/**
	 * Default set Global Settings
	 */
	public function default_settings() {
		global $wp_rewrite;
                $ep_functions = new Eventprime_Basic_Functions;
		$this->default_notifications();
		
		$global_options = get_option( 'em_global_settings' );
		if( ! $ep_functions->ep_get_global_settings( 'payment_test_mode' ) ) {
			$global_options->payment_test_mode = 1;
		}
		if( ! $ep_functions->ep_get_global_settings( 'currency' ) ) {
			$global_options->currency = 'USD';
		}
		if( ! $ep_functions->ep_get_global_settings( 'event_tour' ) ) {
			$global_options->event_tour = 0;
		}
		if( ! $ep_functions->ep_get_global_settings( 'is_visit_welcome_page' ) ) {
			$global_options->is_visit_welcome_page = 0;
		}
		if( ! $ep_functions->ep_get_global_settings( 'dashboard_hide_past_events' ) ) {
			$global_options->dashboard_hide_past_events = 0;
		}
		if( ! $ep_functions->ep_get_global_settings( 'disable_filter_options' ) ) {
			$global_options->disable_filter_options = 0;
		}
		if( ! $ep_functions->ep_get_global_settings( 'front_switch_view_option' ) ) {
			$global_options->front_switch_view_option = array( 'month', 'week', 'day', 'listweek', 'square_grid', 'staggered_grid', 'slider', 'rows' );
		}
		if( ! $ep_functions->ep_get_global_settings( 'default_cal_view' ) ) {
			$global_options->default_cal_view = 'month';
		}
		if( ! $ep_functions->ep_get_global_settings( 'frontend_submission_sections' ) ) {
			$global_options->frontend_submission_sections = array( 'fes_event_featured_image' => 1, 'fes_event_booking' => 1, 'fes_event_link' => 1, 'fes_event_type' => 1, 'fes_event_location' => 1, 'fes_event_performer' => 1, 'fes_event_organizer' => 1, 'fes_event_more_options' => 1, 'fes_event_text_color' => 1 );
		}
                if( ! $ep_functions->ep_get_global_settings( 'datepicker_format' ) ) {
			$global_options->datepicker_format = 'yy-mm-dd&Y-m-d';
		}

		// Update settings.
		update_option('em_global_settings', $global_options );
		$wp_rewrite->flush_rules();

		// Update DB version.
		if( empty( get_option( 'emagic_db_version' ) ) ) {
			add_option( 'emagic_db_version', EVENTPRIME_VERSION);
		} else{
			update_option( 'emagic_db_version', EVENTPRIME_VERSION );
		}
                
                add_option( 'ep_encrypt_secret_key',wp_generate_password( 16, false ) );
		add_option( 'ep_encrypt_secret_iv', wp_generate_password( 16, false ) );
                
	}

	/**
	 * Default email notification
	 */
	public function default_notifications() {
                $ep_functions = new Eventprime_Basic_Functions;
		$global_options = get_option('em_global_settings' );

		$booking_pending_email = $ep_functions->ep_get_global_settings( 'booking_pending_email' );
		if ( empty( $booking_pending_email ) ) {
			ob_start();
			include plugin_dir_path( EP_PLUGIN_FILE ) . 'admin/partials/settings/emailers/mail/pending.php';
			$global_options->booking_pending_email = ob_get_clean();
		}

		$booking_confirmed_email = $ep_functions->ep_get_global_settings( 'booking_confirmed_email' );
		if ( empty( $booking_confirmed_email ) ) {
			ob_start();
			include plugin_dir_path( EP_PLUGIN_FILE ) . 'admin/partials/settings/emailers/mail/customer.php';
			$global_options->booking_confirmed_email = ob_get_clean();
		}

		$booking_cancelation_email = $ep_functions->ep_get_global_settings( 'booking_cancelation_email' );
		if ( empty( $booking_cancelation_email ) ) {
			ob_start();
			include plugin_dir_path( EP_PLUGIN_FILE ) . 'admin/partials/settings/emailers/mail/cancellation.php';
			$global_options->booking_cancelation_email = ob_get_clean();
		}

		$reset_password_mail = $ep_functions->ep_get_global_settings( 'reset_password_mail' );
		if ( empty( $reset_password_mail ) ) {
			ob_start();
			include plugin_dir_path( EP_PLUGIN_FILE ) . 'admin/partials/settings/emailers/mail/reset_user_password.php';
			$global_options->reset_password_mail = ob_get_clean();
		}

		$registration_email_content = $ep_functions->ep_get_global_settings( 'registration_email_content' );
		if ( empty( $registration_email_content ) ) {
			ob_start();
			include plugin_dir_path( EP_PLUGIN_FILE ) . 'admin/partials/settings/emailers/mail/registration.php';
			$global_options->registration_email_content = ob_get_clean();
		}

		$booking_refund_email =$ep_functions->ep_get_global_settings( 'booking_refund_email' );
		if ( empty( $booking_refund_email ) ) {
			ob_start();
			include plugin_dir_path( EP_PLUGIN_FILE ) . 'admin/partials/settings/emailers/mail/refund.php';
			$global_options->booking_refund_email = ob_get_clean();
		}

		$event_submitted_email =$ep_functions->ep_get_global_settings( 'event_submitted_email' );
		if ( empty( $event_submitted_email ) ) {
			ob_start();
			include plugin_dir_path( EP_PLUGIN_FILE ) . 'admin/partials/settings/emailers/mail/event_submitted.php';
			$global_options->event_submitted_email = ob_get_clean();
		}

		$event_approved_email =$ep_functions->ep_get_global_settings( 'event_approved_email' );
		if ( empty( $event_approved_email ) ) {
			ob_start();
			include plugin_dir_path( EP_PLUGIN_FILE ) . 'admin/partials/settings/emailers/mail/event_approved.php';
			$global_options->event_approved_email = ob_get_clean();
		}
                
        $admin_booking_confirmed_email =$ep_functions->ep_get_global_settings( 'admin_booking_confirmed_email' );
		if ( empty( $admin_booking_confirmed_email ) ) {
			ob_start();
			include plugin_dir_path( EP_PLUGIN_FILE ) . 'admin/partials/settings/emailers/mail/admin_confirm.php';
			$global_options->admin_booking_confirmed_email = ob_get_clean();
		}

        $admin_booking_pending_email =$ep_functions->ep_get_global_settings( 'booking_pending_admin_email' );
		if ( empty( $admin_booking_pending_email ) ) {
			ob_start();
			include plugin_dir_path( EP_PLUGIN_FILE ) . 'admin/partials/settings/emailers/mail/admin_pending.php';
			$global_options->booking_pending_admin_email = ob_get_clean();
		}

		// Update settings.
		update_option('em_global_settings', $global_options );
	}
        
        
        public function ep_check_updated_data() 
        {
            $db_ver = get_option( 'emagic_db_version' );
            // Update DB version.
            if( empty( get_option( 'emagic_db_version' ) ) ) {
                    add_option( 'emagic_db_version', EVENTPRIME_VERSION );
            } else{
                    if( $db_ver < EVENTPRIME_VERSION ) {
                            $this->ep_create_table();
                            $this->ep_add_custom_capabilities();
                            $this->create_pages();
                            $this->default_settings();
                    }
                    update_option( 'emagic_db_version', EVENTPRIME_VERSION );
                    $this->update_event_date_time_meta_data();
            }
		
        }

	/**
	 * Create custom tables
	 */
	public function ep_create_table() {
        global $wpdb;
        if( version_compare( get_bloginfo('version'), '6.1')  < 0 ){
            require_once( ABSPATH . 'wp-includes/wp-db.php' );
		} else{
            require_once( ABSPATH . 'wp-includes/class-wpdb.php' );
		}
        
        $charset_collate = $wpdb->get_charset_collate();
        $price_options_table = $this->get_db_table_name( 'TICKET' ); 
        $checkout_fields_table = $this->get_db_table_name( 'CHECKOUT_FIELDS' ); 
        $ticket_category_table = $this->get_db_table_name( 'TICKET_CATEGORIES' );
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $sql = "CREATE TABLE `{$price_options_table}` (
			`id` bigint(20) NOT NULL AUTO_INCREMENT,
			`event_id` bigint(20) NOT NULL,
			`name` varchar(255) DEFAULT NULL,
			`description` longtext DEFAULT NULL,
			`start_date` datetime DEFAULT NULL,
			`end_date` datetime DEFAULT NULL,
			`price` varchar(50) DEFAULT NULL,
			`special_price` varchar(50) DEFAULT NULL,
			`capacity` integer(11) DEFAULT NULL,
			`is_default` tinyint(2) DEFAULT 0 NOT NULL,
			`is_event_price` tinyint(2) DEFAULT 0 NOT NULL,
			`icon` longtext DEFAULT NULL,
			`priority` integer(11) DEFAULT NULL,
			`capacity_progress_bar` tinyint(2) DEFAULT 0 NOT NULL,
			`status` tinyint(2) DEFAULT 1 NOT NULL,
			`created_at` datetime NOT NULL,
			`updated_at` datetime DEFAULT NULL,
			`variation_color` varchar(20) DEFAULT NULL,
			`seat_data` longtext DEFAULT NULL,
			`parent_price_option_id` integer(11) DEFAULT 0 NOT NULL,
			`category_id` integer(11) DEFAULT 0 NOT NULL,
			`additional_fees` longtext DEFAULT NULL,
			`allow_cancellation` tinyint(2) DEFAULT 0 NOT NULL,
			`show_remaining_tickets` tinyint(2) DEFAULT 0 NOT NULL,
			`show_ticket_booking_dates` tinyint(2) DEFAULT 0 NOT NULL,
			`min_ticket_no` varchar(50) DEFAULT NULL,
			`max_ticket_no` varchar(50) DEFAULT NULL,
			`visibility` longtext DEFAULT NULL,
			`offers` longtext DEFAULT NULL,
			`booking_starts` longtext DEFAULT NULL,
			`booking_ends` longtext DEFAULT NULL,
			`multiple_offers_option` longtext DEFAULT NULL,
			`multiple_offers_max_discount` longtext DEFAULT NULL,
			`ticket_template_id` integer(11) DEFAULT NULL,
			PRIMARY KEY (`id`)
		){$charset_collate}";

		dbDelta( $sql );
			
		$sql = "CREATE TABLE `{$checkout_fields_table}` (
			`id` bigint(20) NOT NULL AUTO_INCREMENT,
			`type` varchar(50) DEFAULT NULL,
			`label` varchar(255) DEFAULT NULL,
			`option_data` longtext DEFAULT NULL,
			`priority` integer(11) DEFAULT NULL,
			`status` tinyint(2) DEFAULT 1 NOT NULL,
			`created_by` integer(11) DEFAULT NULL,
			`last_updated_by` integer(11) DEFAULT NULL,
			`created_at` datetime NOT NULL,
			`updated_at` datetime DEFAULT NULL,
			PRIMARY KEY (`id`)
		){$charset_collate}";

		dbDelta( $sql );

		$sql = "CREATE TABLE `{$ticket_category_table}` (
			`id` bigint(20) NOT NULL AUTO_INCREMENT,
			`event_id` integer(11) NOT NULL,
			`parent_id` integer(11) DEFAULT NULL,
			`name` varchar(100) DEFAULT NULL,
			`capacity` integer(100) DEFAULT NULL,
			`priority` integer(11) DEFAULT NULL,
			`status` tinyint(2) DEFAULT 1 NOT NULL,
			`created_by` integer(11) DEFAULT NULL,
			`last_updated_by` integer(11) DEFAULT NULL,
			`created_at` datetime NOT NULL,
			`updated_at` datetime DEFAULT NULL,
			PRIMARY KEY (`id`)
		){$charset_collate}";
        
		dbDelta( $sql );
        
		// add price options table new columns
		$this->add_new_price_options_column( $price_options_table );
		
    }

	/**
	 * Add custom user capabilities
	 */
	public function ep_add_custom_capabilities() {
		global $wp_roles;

		if ( ! class_exists( 'WP_Roles' ) ) {
			return;
		}

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}

		$capabilities = $this->get_ep_core_caps();

		foreach ( $capabilities as $cap_group ) {
			foreach ( $cap_group as $cap ) {
				$wp_roles->add_cap( 'administrator', $cap );
			}
		}
	}

	/**
	 * EventPrime core capabilities
	 */
	public function get_ep_core_caps() {
		$capabilities = array();
		$capability_types = array( 'em_event', 'em_performer', 'em_booking');
                
		foreach ( $capability_types as $capability_type ) {
			$capabilities[ $capability_type ] = array(
				// Post type.
				"edit_{$capability_type}",
				"read_{$capability_type}",
				"delete_{$capability_type}",
				"edit_{$capability_type}s",
				"edit_others_{$capability_type}s",
				"publish_{$capability_type}s",
				"read_private_{$capability_type}s",
				"delete_{$capability_type}s",
				"delete_private_{$capability_type}s",
				"delete_published_{$capability_type}s",
				"delete_others_{$capability_type}s",
				"edit_private_{$capability_type}s",
				"edit_published_{$capability_type}s",

				// Terms.
				"manage_{$capability_type}_terms",
				"edit_{$capability_type}_terms",
				"delete_{$capability_type}_terms",
				"assign_{$capability_type}_terms",
			);
		}
		return apply_filters('ep_core_capabilities', $capabilities);
	}

	/**
	 * Add new columns for price options table
	 */
	public function add_new_price_options_column( $price_options_table ) {
		global $wpdb;
		// add variation color column in variation table
        $db_name = $wpdb->dbname;
        $column_name = 'variation_color';
        $ep_set_variation_price = $wpdb->get_results($wpdb->prepare("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ",$db_name, $price_options_table, $column_name ));
		if ( empty( $ep_set_variation_price ) ) {
			$add_color_column = "ALTER TABLE `{$price_options_table}` ADD `variation_color` VARCHAR(20) NULL DEFAULT NULL ";
    		$wpdb->query( $add_color_column );
    	
			$column_name = 'seat_data';
			$add_seat_data_column = $wpdb->get_results($wpdb->prepare("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ",$db_name, $price_options_table, $column_name ));
			if ( empty( $add_seat_data_column ) ) {
				$add_seat_data_column = "ALTER TABLE `{$price_options_table}` ADD `seat_data` Longtext NULL DEFAULT NULL ";
				$wpdb->query( $add_seat_data_column );
			}
			$column_name = 'parent_price_option_id';
			$add_parent_price_option_id = $wpdb->get_results($wpdb->prepare("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ",$db_name, $price_options_table, $column_name ));
			if ( empty( $add_parent_price_option_id ) ) {
				$add_parent_price_option_id = "ALTER TABLE `{$price_options_table}` ADD `parent_price_option_id` integer(11) DEFAULT 0 NOT NULL ";
				$wpdb->query( $add_parent_price_option_id );
			}

			// add new tickets column in price options table
			$column_name = 'category_id';
			$add_category_id = $wpdb->get_results($wpdb->prepare("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ",$db_name, $price_options_table, $column_name ));
			if ( empty( $add_category_id ) ) {
				$add_category_id = "ALTER TABLE `{$price_options_table}` ADD `category_id` integer(11) DEFAULT 0 NOT NULL ";
				$wpdb->query( $add_category_id );
			
				$column_name = 'additional_fees';
				$add_additional_fees = $wpdb->get_results($wpdb->prepare("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ",$db_name, $price_options_table, $column_name ));
				if ( empty( $add_additional_fees ) ) {
					$add_additional_fees = "ALTER TABLE `{$price_options_table}` ADD `additional_fees` longtext DEFAULT NULL ";
					$wpdb->query( $add_additional_fees );
				}
				$column_name = 'allow_cancellation';
				$add_allow_cancellation = $wpdb->get_results($wpdb->prepare("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ",$db_name, $price_options_table, $column_name ));
				if ( empty( $add_allow_cancellation ) ) {
					$add_allow_cancellation = "ALTER TABLE `{$price_options_table}` ADD `allow_cancellation` tinyint(2) DEFAULT 0 NOT NULL ";
					$wpdb->query( $add_allow_cancellation );
				}
				$column_name = 'show_remaining_tickets';
				$add_show_remaining_tickets = $wpdb->get_results($wpdb->prepare("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ",$db_name, $price_options_table, $column_name ));
				if ( empty( $add_show_remaining_tickets ) ) {
					$add_show_remaining_tickets = "ALTER TABLE `{$price_options_table}` ADD `show_remaining_tickets` tinyint(2) DEFAULT 0 NOT NULL ";
					$wpdb->query( $add_show_remaining_tickets );
				}
				$column_name = 'show_ticket_booking_dates';
				$add_show_ticket_booking_dates = $wpdb->get_results($wpdb->prepare("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ",$db_name, $price_options_table, $column_name ));
				if ( empty( $add_show_ticket_booking_dates ) ) {
					$add_show_ticket_booking_dates = "ALTER TABLE `{$price_options_table}` ADD `show_ticket_booking_dates` tinyint(2) DEFAULT 0 NOT NULL ";
					$wpdb->query( $add_show_ticket_booking_dates );
				}
				$column_name = 'min_ticket_no';
				$add_min_ticket_no = $wpdb->get_results($wpdb->prepare("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ",$db_name, $price_options_table, $column_name ));
				if ( empty( $add_min_ticket_no ) ) {
					$add_min_ticket_no = "ALTER TABLE `{$price_options_table}` ADD `min_ticket_no` varchar(50) DEFAULT NULL ";
					$wpdb->query( $add_min_ticket_no );
				}
				$column_name = 'max_ticket_no';
				$add_max_ticket_no = $wpdb->get_results($wpdb->prepare("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ",$db_name, $price_options_table, $column_name ));
				if ( empty( $add_max_ticket_no ) ) {
					$add_max_ticket_no = "ALTER TABLE `{$price_options_table}` ADD `max_ticket_no` varchar(50) DEFAULT NULL ";
					$wpdb->query( $add_max_ticket_no );
				}
				$column_name = 'visibility';
				$add_visibility = $wpdb->get_results($wpdb->prepare("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ",$db_name, $price_options_table, $column_name ));
				if ( empty( $add_visibility ) ) {
					$add_visibility = "ALTER TABLE `{$price_options_table}` ADD `visibility` longtext DEFAULT NULL ";
					$wpdb->query( $add_visibility );
				}
				$column_name = 'offers';
				$add_offers = $wpdb->get_results($wpdb->prepare("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ",$db_name, $price_options_table, $column_name ));
				if ( empty( $add_offers ) ) {
					$add_offers = "ALTER TABLE `{$price_options_table}` ADD `offers` longtext DEFAULT NULL ";
					$wpdb->query( $add_offers );
				}
				$column_name = 'booking_starts';
				$add_booking_starts = $wpdb->get_results($wpdb->prepare("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ",$db_name, $price_options_table, $column_name ));
				if ( empty( $add_booking_starts ) ) {
					$add_booking_starts = "ALTER TABLE `{$price_options_table}` ADD `booking_starts` longtext DEFAULT NULL ";
					$wpdb->query( $add_booking_starts );
				}
				$column_name = 'booking_ends';
				$add_booking_ends = $wpdb->get_results($wpdb->prepare("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ",$db_name, $price_options_table, $column_name ));
				if ( empty( $add_booking_ends ) ) {
					$add_booking_ends = "ALTER TABLE `{$price_options_table}` ADD `booking_ends` longtext DEFAULT NULL ";
					$wpdb->query( $add_booking_ends );
				}
				$column_name = 'multiple_offers_option';
				$add_multiple_offers_option = $wpdb->get_results($wpdb->prepare("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ",$db_name, $price_options_table, $column_name ));
				if ( empty( $add_multiple_offers_option ) ) {
					$add_multiple_offers_option = "ALTER TABLE `{$price_options_table}` ADD `multiple_offers_option` longtext DEFAULT NULL ";
					$wpdb->query( $add_multiple_offers_option );
				}
				$column_name = 'multiple_offers_max_discount';
				$add_multiple_offers_max_discount = $wpdb->get_results($wpdb->prepare("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ",$db_name, $price_options_table, $column_name ));
				if ( empty( $add_multiple_offers_max_discount ) ) {
					$add_multiple_offers_max_discount = "ALTER TABLE `{$price_options_table}` ADD `multiple_offers_max_discount` longtext DEFAULT NULL ";
					$wpdb->query( $add_multiple_offers_max_discount );
				}
				$column_name = 'ticket_template_id';
				$add_ticket_template_id = $wpdb->get_results($wpdb->prepare("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ",$db_name, $price_options_table, $column_name ));
				if ( empty( $add_ticket_template_id ) ) {
					$add_ticket_template_id = "ALTER TABLE `{$price_options_table}` ADD `ticket_template_id` integer(10) DEFAULT NULL ";
					$wpdb->query( $add_ticket_template_id );
				}
			}
		}
	}
        
        /**
	 * Activate old extensions
	 */
	public function ep_deactivate_old_extensions() {
		$deactivate_extension_list = array();
		$installed_plugins = get_plugins();
		$installed_plugin_file = $installed_plugin_url = array();
		if( ! empty( $installed_plugins ) ) {
			foreach ( $installed_plugins as $key => $value ) {
				$exp = explode( '/', $key );
				$installed_plugin_file[] = end( $exp );
				$installed_plugin_url[] = $key;
			}
		}

		if( in_array( 'event-seating.php', $installed_plugin_file ) ) {
			if( class_exists("EM_Seating") ) {
				$file_key = array_search( 'event-seating.php', $installed_plugin_file );
				$deactivate_extension_list[] = $installed_plugin_url[$file_key];
			}
		}

		if( in_array( 'event-analytics.php', $installed_plugin_file ) ) {
			if( class_exists("EM_Analytics") ) {
				$file_key = array_search( 'event-analytics.php', $installed_plugin_file );
				$deactivate_extension_list[] = $installed_plugin_url[$file_key];
			}
		}

		if( in_array( 'event-sponser.php', $installed_plugin_file ) ) {
			if( class_exists("EM_Sponser") ) {
				$file_key = array_search( 'event-sponser.php', $installed_plugin_file );
				$deactivate_extension_list[] = $installed_plugin_url[$file_key];
			}
		}

		if( in_array( 'event-stripe.php', $installed_plugin_file ) ) {
			if( class_exists("EM_Stripe") ) {
				$file_key = array_search( 'event-stripe.php', $installed_plugin_file );
				$deactivate_extension_list[] = $installed_plugin_url[$file_key];
			}
		}

		if( in_array( 'eventprime-offline.php', $installed_plugin_file ) ) {
			if( class_exists("EM_Offline") ) {
				$file_key = array_search( 'eventprime-offline.php', $installed_plugin_file );
				$deactivate_extension_list[] = $installed_plugin_url[$file_key];
			}
		}

		if( in_array( 'eventprime-recurring-events.php', $installed_plugin_file ) ) {
			if( class_exists("EventPrime_Recurring_Events") ) {
				$file_key = array_search( 'eventprime-recurring-events.php', $installed_plugin_file );
				$deactivate_extension_list[] = $installed_plugin_url[$file_key];
			}
		}

		if( in_array( 'eventprime-attendees-list.php', $installed_plugin_file ) ) {
			if( class_exists("EM_Attendees_List") ) {
				$file_key = array_search( 'eventprime-attendees-list.php', $installed_plugin_file );
				$deactivate_extension_list[] = $installed_plugin_url[$file_key];
			}
		}

		if( in_array( 'event-coupons.php', $installed_plugin_file ) ) {
			if( class_exists("EM_Coupons") ) {
				$file_key = array_search( 'event-coupons.php', $installed_plugin_file );
				$deactivate_extension_list[] = $installed_plugin_url[$file_key];
			}
		}

		if( in_array( 'event-guest-booking.php', $installed_plugin_file ) ) {
			if( class_exists("EM_Guest_Booking") ) {
				$file_key = array_search( 'event-guest-booking.php', $installed_plugin_file );
				$deactivate_extension_list[] = $installed_plugin_url[$file_key];
			}
		}

		if( in_array( 'eventprime-more-widgets.php', $installed_plugin_file ) ) {
			if( class_exists("EM_List_Widget") ) {
				$file_key = array_search( 'eventprime-more-widgets.php', $installed_plugin_file );
				$deactivate_extension_list[] = $installed_plugin_url[$file_key];
			}
		}

		if( in_array( 'event-attendees-booking.php', $installed_plugin_file ) ) {
			if( class_exists("EM_Attendees_Booking") ) {
				$file_key = array_search( 'event-attendees-booking.php', $installed_plugin_file );
				$deactivate_extension_list[] = $installed_plugin_url[$file_key];
			}
		}

		if( in_array( 'event-wishlist.php', $installed_plugin_file ) ) {
			if( class_exists("EM_Wishlist") ) {
				$file_key = array_search( 'event-wishlist.php', $installed_plugin_file );
				$deactivate_extension_list[] = $installed_plugin_url[$file_key];
			}
		}

		if( in_array( 'eventprime-event-comments.php', $installed_plugin_file ) ) {
			if( class_exists("EM_Event_Comments") ) {
				$file_key = array_search( 'eventprime-event-comments.php', $installed_plugin_file );
				$deactivate_extension_list[] = $installed_plugin_url[$file_key];
			}
		}

		if( in_array( 'automatic-discounts.php', $installed_plugin_file ) ) {
			if( class_exists("EM_Automatic_Discounts") ) {
				$file_key = array_search( 'automatic-discounts.php', $installed_plugin_file );
				$deactivate_extension_list[] = $installed_plugin_url[$file_key];
			}
		}

		if( in_array( 'google-import-export.php', $installed_plugin_file ) ) {
			if( class_exists("EM_Google_Import_Export_Events") ) {
				$file_key = array_search( 'google-import-export.php', $installed_plugin_file );
				$deactivate_extension_list[] = $installed_plugin_url[$file_key];
			}
		}

		if( in_array( 'events-import-export.php', $installed_plugin_file ) ) {
			if( class_exists("EM_Events_Import_Export") ) {
				$file_key = array_search( 'events-import-export.php', $installed_plugin_file );
				$deactivate_extension_list[] = $installed_plugin_url[$file_key];
			}
		}

		if( in_array( 'eventprime-mailpoet.php', $installed_plugin_file ) ) {
			if( class_exists("EM_MailPoet") ) {
				$file_key = array_search( 'eventprime-mailpoet.php', $installed_plugin_file );
				$deactivate_extension_list[] = $installed_plugin_url[$file_key];
			}
		}
		
		if( in_array( 'woocommerce-integration.php', $installed_plugin_file ) ) {
			if( class_exists("EM_Woocommerce_Integration") ) {
				$file_key = array_search( 'woocommerce-integration.php', $installed_plugin_file );
				$deactivate_extension_list[] = $installed_plugin_url[$file_key];
			}
		}

		if( in_array( 'eventprime-zoom-meetings.php', $installed_plugin_file ) ) {
			if( class_exists("EM_Zoom_Meetings") ) {
				$file_key = array_search( 'eventprime-zoom-meetings.php', $installed_plugin_file );
				$deactivate_extension_list[] = $installed_plugin_url[$file_key];
			}
		}

		if( in_array( 'event-zapier.php', $installed_plugin_file ) ) {
			if( class_exists("EM_Zapier_Integration") ) {
				$file_key = array_search( 'event-zapier.php', $installed_plugin_file );
				$deactivate_extension_list[] = $installed_plugin_url[$file_key];
			}
		}

		if( in_array( 'event-invoices.php', $installed_plugin_file ) ) {
			if( class_exists("Eventprime_Event_Invoices") ) {
				$file_key = array_search( 'event-invoices.php', $installed_plugin_file );
				$deactivate_extension_list[] = $installed_plugin_url[$file_key];
			}
		}

		if( in_array( 'sms-integration.php', $installed_plugin_file ) ) {
			if( class_exists("EM_SMS_Integration") ) {
				$file_key = array_search( 'sms-integration.php', $installed_plugin_file );
				$deactivate_extension_list[] = $installed_plugin_url[$file_key];
			}
		}

		if( ! empty( $deactivate_extension_list ) ) {
			if( empty( get_option( 'ep_deactivate_extensions_on_migration' ) ) ) {
				add_option( 'ep_deactivate_extensions_on_migration', $deactivate_extension_list );
			} else{
				update_option( 'ep_deactivate_extensions_on_migration', $deactivate_extension_list );
			}
			foreach( $deactivate_extension_list as $ext_list ) {
				deactivate_plugins( $ext_list );
			}
		}
	}

	/**
	 * Add new meta for date time and update existing events
	 */
	public function update_event_date_time_meta_data() {
		$check_for_event_date_time = get_option( 'ep_update_event_date_time_meta' );
		if( empty( $check_for_event_date_time ) || $check_for_event_date_time != 2 ) {
			$default = array(
				'post_status' => 'any',
				'post_type'   => 'em_event',
				'numberposts' => -1,
			);
			$posts = get_posts( $default );
			if( ! empty( $posts ) ) {
				foreach( $posts as $post ){
					$event_id = $post->ID;
					if( ! empty( $event_id ) ) {
						$merge_start_date_time = $merge_end_date_time = '';
						// start date
						$start_date    = get_post_meta( $event_id, 'em_start_date', true );
						$start_date = (int)$start_date;
						if( ! empty( $start_date ) ) {
							$start_time    = get_post_meta( $event_id, 'em_start_time', true );
							if( empty( $start_time ) ) {
								$start_time = '12:00 AM';
							}
							$convert_start_date = gmdate( 'Y-m-d', $start_date );
							$new_start_date = $convert_start_date . ' ' . $start_time;
							$merge_start_date_time = strtotime( $new_start_date );
							if( ! empty( $merge_start_date_time ) ) {
								update_post_meta( $event_id, 'em_start_date_time', $merge_start_date_time );
							}
							// end date
							$end_date      = get_post_meta( $event_id, 'em_end_date', true );
							$end_date = (int)$end_date;
							$end_time      = get_post_meta( $event_id, 'em_end_time', true );
							if( empty( $end_time ) ) {
								$end_time = '11:59 PM';
							}
							$convert_end_date = gmdate( 'Y-m-d', $end_date );
							$new_end_date = $convert_end_date . ' ' . $end_time;
							$merge_end_date_time = strtotime( $new_end_date );
							if( ! empty( $merge_end_date_time ) ) {
								update_post_meta( $event_id, 'em_end_date_time', $merge_end_date_time );
							}
						}
					}
				}
				update_option( 'ep_update_event_date_time_meta', 2 );
			}
		}
	}



}
