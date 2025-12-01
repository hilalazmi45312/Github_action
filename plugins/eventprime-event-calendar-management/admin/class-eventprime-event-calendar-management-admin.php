<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://theeventprime.com
 * @since      1.0.0
 *
 * @package    Eventprime_Event_Calendar_Management
 * @subpackage Eventprime_Event_Calendar_Management/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Eventprime_Event_Calendar_Management
 * @subpackage Eventprime_Event_Calendar_Management/admin
 * @author     EventPrime <support@metagauss.com>
 */
class Eventprime_Event_Calendar_Management_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;
    private $processing;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {

        $this->plugin_name = $plugin_name;
        $this->version     = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Eventprime_Event_Calendar_Management_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Eventprime_Event_Calendar_Management_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        wp_register_style( 'em-meta-box-admin-custom-css', plugin_dir_url( __FILE__ ) . 'css/em-admin-metabox-custom.css', false, $this->version );
        wp_enqueue_style( 'ep-admin-utility-style', plugin_dir_url( __FILE__ ) . 'css/ep-admin-common-utility.css', false, $this->version );
        wp_enqueue_style( 'ep-material-fonts', plugin_dir_url( __FILE__ ) . 'css/ep-material-fonts-icon.css', array(), $this->version );
        wp_register_style( 'em-admin-select2-css', plugin_dir_url( __FILE__ ) . 'css/select2.min.css', false, $this->version );
        wp_register_style( 'em-admin-jquery-ui', plugin_dir_url( __FILE__ ) . 'css/jquery-ui.min.css', false, $this->version );
        // Ui Timepicker css
        wp_register_style( 'em-admin-jquery-timepicker', plugin_dir_url( __FILE__ ) . 'css/jquery.timepicker.min.css', false, $this->version );
        // register toast
        wp_register_style( 'ep-toast-css', plugin_dir_url( __FILE__ ) . 'css/jquery.toast.min.css', false, $this->version );
        // Blocks style for admin
        wp_register_style( 'ep-admin-blocks-style', plugin_dir_url( __FILE__ ) . 'css/ep-admin-blocks-style.css', false, $this->version );
        wp_register_style( 'em-type-admin-custom-css', plugin_dir_url( __FILE__ ) . 'css/event-types/em-type-admin-custom.css', false, $this->version );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_register_script( 'em-admin-jscolor', plugin_dir_url( __FILE__ ) . 'js/jscolor.min.js', false, $this->version );
        wp_register_script( 'em-admin-select2-js', plugin_dir_url( __FILE__ ) . 'js/select2.full.min.js', false, $this->version );
        wp_register_script( 'em-admin-timepicker-js', plugin_dir_url( __FILE__ ) . 'js/jquery.timepicker.min.js', false, $this->version,true );
        wp_register_script( 'ep-toast-js', plugin_dir_url( __FILE__ ) . 'js/jquery.toast.min.js', array( 'jquery' ), $this->version );
        wp_register_script( 'ep-toast-message-js', plugin_dir_url( __FILE__ ) . 'js/toast-message.js', array( 'jquery' ), $this->version );
        wp_register_script( 'eventprime-admin-blocks-js', plugin_dir_url( __FILE__ ) . 'js/blocks/index.js', array( 'wp-blocks', 'wp-editor', 'wp-i18n', 'wp-element', 'wp-components' ), $this->version );
        wp_register_script( 'em-meta-box-admin-custom-js', plugin_dir_url( __FILE__ ) . 'js/em-admin-metabox-custom.js', array( 'jquery', 'jquery-ui-datepicker', 'jquery-ui-dialog', 'jquery-ui-accordion', 'jquery-ui-sortable' ), $this->version );
        wp_register_script( 'em-type-admin-custom-js', plugin_dir_url( __FILE__ ) . 'js/event-types/em-type-admin-custom.js', array( 'jquery', 'jquery-ui-datepicker', 'jquery-ui-dialog', 'jquery-ui-accordion', 'jquery-ui-sortable' ), $this->version );
        wp_register_script( 'ep-admin-utility-script', plugin_dir_url( __FILE__ ) . 'js/ep-admin-common-utility.js', array( 'jquery', 'jquery-ui-tooltip', 'jquery-ui-dialog' ), $this->version );
            
        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/eventprime-event-calendar-management-admin.js', array( 'jquery' ), $this->version, true );
        $ep_dismissable_notice_nonce = wp_create_nonce( 'ep_dismissable_notice_nonce' );
        wp_localize_script(
            $this->plugin_name,
            'ep_ajax_object',
            array(
				'nonce'    => $ep_dismissable_notice_nonce,
				'ajax_url' => admin_url( 'admin-ajax.php' ),
			)
        );

        $ep_functions = new Eventprime_Basic_Functions();
        $current_page = $ep_functions->eventprime_check_is_ep_dashboard_page();
        //print_r($current_page);die;
        if ( $current_page == 'event_edit' ) {
            wp_enqueue_script( 'jquery' );
            wp_enqueue_script( 'jquery-ui-core' );
            wp_enqueue_script( 'jquery-ui-tabs' );
            wp_enqueue_Script( 'jquery-ui-sortable' );
            wp_enqueue_script( 'jquery-ui-autocomplete' );
            wp_enqueue_script( 'thickbox' );
            wp_enqueue_script( 'jquery-ui-dialog' );
            wp_enqueue_script( 'media-upload' );
            wp_enqueue_script( 'jquery-form' );
            wp_enqueue_script( 'jquery-ui-datepicker' );
            wp_enqueue_media();
            wp_enqueue_script( 'ep-common-script', plugin_dir_url( __FILE__ ) . 'js/ep-common-script.js', array( 'jquery' ), $this->version );

            // localized global settings
            $global_settings = $ep_functions->ep_get_global_settings();
            $currency_symbol = $ep_functions->ep_currency_symbol();
            wp_localize_script(
                'ep-common-script',
                'eventprime',
                array(
					'global_settings' => $global_settings,
					'currency_symbol' => $currency_symbol,
					'ajaxurl'         => admin_url( 'admin-ajax.php' ),
					'trans_obj'       => $ep_functions->ep_define_common_field_errors(),
				)
            );
            wp_enqueue_script( 'ep-admin-utility-script', plugin_dir_url( __FILE__ ) . 'js/ep-admin-common-utility.js', array( 'jquery', 'jquery-ui-tooltip', 'jquery-ui-dialog' ), $this->version );
            wp_localize_script(
                'ep-admin-utility-script',
                'ep_admin_utility_script',
                array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
				)
            );

            $performer_data = array();
			//      $performers = EventM_Factory_Service::ep_get_instance( 'EventM_Performer_Controller_List' );
			//      if( ! empty( $performers ) ) {
			//          $fields = ['id', 'name'];
			//          $performer_data = $performers->get_performer_field_data( $fields );
			//      }
            // check if attendee list extension enabled
            $enabled_attendees_list = 0;
            $extensions             = $ep_functions->ep_get_activate_extensions();
            if ( !empty( $extensions ) && in_array( 'Eventprime_Attendees_List', $extensions ) ) {
                $enabled_attendees_list = 1;
            }
            wp_localize_script(
                'em-meta-box-admin-custom-js',
                'em_event_meta_box_object',
                array(
					'before_event_scheduling'          => esc_html__( 'Please choose start & end date before enable scheduling!', 'eventprime-event-calendar-management' ),
					'before_event_recurrence'          => esc_html__( 'Please choose start & end date before enable recurrence!', 'eventprime-event-calendar-management' ),
					'add_schedule_btn'                 => esc_html__( 'Add New Hourly Schedule', 'eventprime-event-calendar-management' ),
					'add_day_title_label'              => esc_html__( 'Title', 'eventprime-event-calendar-management' ),
					'start_time_label'                 => esc_html__( 'Start Time', 'eventprime-event-calendar-management' ),
					'end_time_label'                   => esc_html__( 'End Time', 'eventprime-event-calendar-management' ),
					'description_label'                => esc_html__( 'Description', 'eventprime-event-calendar-management' ),
					'remove_label'                     => esc_html__( 'Remove', 'eventprime-event-calendar-management' ),
					'material_icons'                   => $ep_functions->get_material_icons(),
					'icon_text'                        => esc_html__( 'Icon', 'eventprime-event-calendar-management' ),
					'icon_color_text'                  => esc_html__( 'Icon Color', 'eventprime-event-calendar-management' ),
					'performers_data'                  => $performer_data,
					'additional_date_text'             => esc_html__( 'Date', 'eventprime-event-calendar-management' ),
					'additional_time_text'             => esc_html__( 'Time', 'eventprime-event-calendar-management' ),
					'optional_text'                    => esc_html__( '(Optional)', 'eventprime-event-calendar-management' ),
					'additional_label_text'            => esc_html__( 'Label', 'eventprime-event-calendar-management' ),
					'countdown_activate_text'          => esc_html__( 'Activates', 'eventprime-event-calendar-management' ),
					'countdown_activated_text'         => esc_html__( 'Activated', 'eventprime-event-calendar-management' ),
					'countdown_on_text'                => esc_html__( 'On', 'eventprime-event-calendar-management' ),
					'countdown_ends_text'              => esc_html__( 'Ends', 'eventprime-event-calendar-management' ),
					'countdown_activates_on'           => array(
						'right_away'    => esc_html__( 'Right Away', 'eventprime-event-calendar-management' ),
						'custom_date'   => esc_html__( 'Custom Date', 'eventprime-event-calendar-management' ),
						'event_date'    => esc_html__( 'Event Date', 'eventprime-event-calendar-management' ),
						'relative_date' => esc_html__( 'Relative Date', 'eventprime-event-calendar-management' ),
					),
					'countdown_days_options'           => array(
						'before' => esc_html__( 'Days Before', 'eventprime-event-calendar-management' ),
						'after'  => esc_html__( 'Days After', 'eventprime-event-calendar-management' ),
					),
					'countdown_event_options'          => array(
						'event_start' => esc_html__( 'Event Start', 'eventprime-event-calendar-management' ),
						'event_ends'  => esc_html__( 'Event Ends', 'eventprime-event-calendar-management' ),
					),
					'ticket_capacity_text'             => esc_html__( 'Capacity', 'eventprime-event-calendar-management' ),
					'add_ticket_text'                  => esc_html__( 'Add Ticket Type', 'eventprime-event-calendar-management' ),
					'add_text'                         => esc_html__( 'Add', 'eventprime-event-calendar-management' ),
					'edit_text'                        => esc_html__( 'Edit', 'eventprime-event-calendar-management' ),
					'update_text'                      => esc_html__( 'Update', 'eventprime-event-calendar-management' ),
					'add_ticket_category_text'         => esc_html__( 'Add Tickets Category', 'eventprime-event-calendar-management' ),
					'price_text'                       => esc_html__( 'Fee Per Ticket', 'eventprime-event-calendar-management' ),
					'offer_text'                       => esc_html__( 'Offer', 'eventprime-event-calendar-management' ),
					'no_ticket_found_error'            => esc_html__( 'You have not added any tickets for this event. Therefore, bookings for this event will be turned off.', 'eventprime-event-calendar-management' ),
					'max_capacity_error'               => esc_html__( 'Max allowed capacity is', 'eventprime-event-calendar-management' ),
					'max_less_then_min_error'          => esc_html__( 'Maximum tickets number can\'t be less then minimum tickets number.', 'eventprime-event-calendar-management' ),
					'required_text'                    => esc_html__( 'Required', 'eventprime-event-calendar-management' ),
					'one_checkout_field_req'           => esc_html__( 'Please select atleast one attendee field.', 'eventprime-event-calendar-management' ),
					'no_name_field_option'             => esc_html__( 'Please select name field option.', 'eventprime-event-calendar-management' ),
					'some_issue_found'                 => esc_html__( 'Some issue found. Please refresh the page and try again later.', 'eventprime-event-calendar-management' ),
					'fixed_field_not_selected'         => esc_html__( 'Please selecte booking field.', 'eventprime-event-calendar-management' ),
					'fixed_field_term_option_required' => esc_html__( 'Please select one terms option.', 'eventprime-event-calendar-management' ),
					'repeat_child_event_prompt'        => esc_html__( 'The event have multiple recurrences. They will be deleted after update event.', 'eventprime-event-calendar-management' ),
					'empty_event_title'                => esc_html__( 'Event title is required.', 'eventprime-event-calendar-management' ),
					'empty_start_date'                 => esc_html__( 'Event start date is required.', 'eventprime-event-calendar-management' ),
					'end_date_less_from_start'         => esc_html__( 'Event end date can not be less than event start date.', 'eventprime-event-calendar-management' ),
					'same_event_start_and_end'         => esc_html__( 'Event end date & time should not be same with event start date & time.', 'eventprime-event-calendar-management' ),
					'end_time_but_no_start_time'       => esc_html__( 'You have entered end time but not start time.', 'eventprime-event-calendar-management' ),
					'offer_not_save_error_text'        => esc_html__( 'You have an unsaved offer.', 'eventprime-event-calendar-management' ),
					'offer_per_more_then_100'          => esc_html__( 'Discount value can\'t be more then 100.', 'eventprime-event-calendar-management' ),
					'all_site_data'                    => $ep_functions->ep_get_all_pages_list(),
					'end_time_less_start_time'         => esc_html__( 'Event end time can not be less then event start time.', 'eventprime-event-calendar-management' ),
					'show_in_attendees_list_text'      => esc_html__( 'Add to Attendees List', 'eventprime-event-calendar-management' ),
					'enabled_attendees_list'           => $enabled_attendees_list,
					'one_booking_field_req'            => esc_html__( 'Please select atleast one booking field.', 'eventprime-event-calendar-management' ),
					'min_ticket_no_zero_error'         => esc_html__( 'The minimum ticket quantity per order must be greater than zero.', 'eventprime-event-calendar-management' ),
					'max_ticket_no_zero_error'         => esc_html__( 'The maximum ticket quantity per order must be greater than zero.', 'eventprime-event-calendar-management' ),
				)
            );

            wp_enqueue_style( 'em-admin-jquery-ui' );
            wp_enqueue_script( 'em-admin-jscolor' );
            wp_enqueue_style( 'em-admin-select2-css' );
            wp_enqueue_script( 'em-admin-select2-js' );
            wp_enqueue_style( 'em-admin-jquery-timepicker' );
            wp_enqueue_script( 'em-admin-timepicker-js' );
            wp_enqueue_script( 'em-meta-box-admin-custom-js' );
            wp_enqueue_style( 'em-meta-box-admin-custom-css' );
            wp_enqueue_style( 'ep-toast-css' );
            wp_enqueue_script( 'ep-toast-js' );
            wp_enqueue_script( 'ep-toast-message-js' );
            wp_localize_script(
                'ep-toast-message-js', 
                'eventprime_toast', 
                array(
                   'error'=> esc_html__( 'Error', 'eventprime-event-calendar-management' ),
                   'success'=> esc_html__( 'Success', 'eventprime-event-calendar-management' ),
                   'warning'=> esc_html__( 'Warning', 'eventprime-event-calendar-management' ),
                )
            );
            wp_enqueue_script( 'ep-event-taxonomy', plugin_dir_url( __FILE__ ) . 'js/ep-event-taxonomy.js', array( 'jquery' ), $this->version );
        }

        if ( $current_page == 'ep-settings' || $current_page == 'ep-bulk-emails' ) {
            wp_register_script( 'em-admin-select2-js', plugin_dir_url( __FILE__ ) . 'js/select2.full.min.js', false, $this->version );
            wp_enqueue_script( 'em-admin-jscolor' );
            wp_enqueue_style( 'em-admin-select2-css' );
            wp_enqueue_script( 'em-admin-select2-js' );
            $tab = '';
            if (isset($_GET['tab']) && isset($_GET['tab_nonce'])) {
                // Verify the nonce
                if (wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['tab_nonce'])), 'ep_settings_tab'))
                {
                    // Sanitize the 'tab' value
                    $tab = sanitize_text_field(wp_unslash($_GET['tab']));
                    if ( isset( $tab ) && $tab === 'license' ) {
                        wp_register_script( 'ep-toast-js', plugin_dir_url( __FILE__ ) . 'js/jquery.toast.min.js', array( 'jquery' ), $this->version );
                        wp_register_script( 'ep-toast-message-js', plugin_dir_url( __FILE__ ) . 'js/toast-message.js', array( 'jquery' ), $this->version );

                        wp_enqueue_script( 'ep-admin-license-page-js', plugin_dir_url( __FILE__ ) . 'js/ep-admin-license.js', array( 'jquery' ), $this->version );
                        $params = array(
                            'ep_license_nonce' => wp_create_nonce( 'ep-license-nonce' ),
                        );

                        wp_localize_script( 'ep-admin-license-page-js', 'ep_admin_license_settings', $params );
                    }
                }
            }
            wp_enqueue_script( 'ep-common-script', plugin_dir_url( __FILE__ ) . 'js/ep-common-script.js', array( 'jquery' ), $this->version );

            wp_enqueue_style( 'ep-toast-css' );
            wp_enqueue_script( 'ep-toast-js' );
            wp_enqueue_script( 'ep-toast-message-js' );
            wp_localize_script(
                'ep-toast-message-js', 
                'eventprime_toast', 
                array(
                   'error'=> esc_html__( 'Error', 'eventprime-event-calendar-management' ),
                   'success'=> esc_html__( 'Success', 'eventprime-event-calendar-management' ),
                   'warning'=> esc_html__( 'Warning', 'eventprime-event-calendar-management' ),
                )
            );
            // localized global settings
            $global_settings = $ep_functions->ep_get_global_settings();
            $currency_symbol = $ep_functions->ep_currency_symbol();
            wp_localize_script(
                'ep-common-script',
                'eventprime',
                array(
					'global_settings' => $global_settings,
					'currency_symbol' => $currency_symbol,
					'ajaxurl'         => admin_url( 'admin-ajax.php' ),
					'trans_obj'       => $ep_functions->ep_define_common_field_errors(),
				)
            );
            wp_enqueue_script(
                'ep-admin-settings-js',
                plugin_dir_url( __FILE__ ) . 'js/ep-admin-settings.js',
                array( 'jquery', 'jquery-ui-core', 'jquery-ui-tooltip', 'jquery-effects-highlight', 'jquery-ui-sortable', 'jquery-ui-datepicker' ),
                $this->version
            );
            wp_enqueue_script( 'ep-admin-utility-script', plugin_dir_url( __FILE__ ) . 'js/ep-admin-common-utility.js', array( 'jquery', 'jquery-ui-tooltip', 'jquery-ui-dialog' ), $this->version );
            wp_localize_script(
                'ep-admin-utility-script',
                'ep_admin_utility_script',
                array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
				)
            );
            $params = array(
                'save_checkout_fields_nonce'      => wp_create_nonce( 'save-checkout-fields' ),
                'delete_checkout_fields_nonce'    => wp_create_nonce( 'delete-checkout-fields' ),
                'edit_checkout_field_title'       => esc_html__( 'Edit Field', 'eventprime-event-calendar-management' ),
                'delete_checkout_field_message'   => esc_html__( 'Are you sure you want to delete this field?', 'eventprime-event-calendar-management' ),
                'edit_text'                       => esc_html__( 'Edit', 'eventprime-event-calendar-management' ),
                'delete_text'                     => esc_html__( 'Delete', 'eventprime-event-calendar-management' ),
                'default_payment_processor_nonce' => wp_create_nonce( 'ep-default-payment-processor' ),
                'activate_payment'                => esc_html__( 'Please activate the', 'eventprime-event-calendar-management' ),
                'payment_text'                    => esc_html__( 'payment', 'eventprime-event-calendar-management' ),
            );
            wp_localize_script( 'ep-admin-settings-js', 'ep_admin_settings', $params );

            wp_enqueue_style(
                'ep-admin-settings-css',
                plugin_dir_url( __FILE__ ) . 'css/ep-admin-settings.css',
                false,
                $this->version
            );
        }
        if ( $current_page == 'ep-extensions' ) {
            wp_enqueue_script( 'ep-admin-utility-script', plugin_dir_url( __FILE__ ) . 'js/ep-admin-common-utility.js', array( 'jquery', 'jquery-ui-tooltip', 'jquery-ui-dialog' ), $this->version );
        }
        //print_r($current_page);die;
        if ( $current_page == 'em_event_type' ) {
            wp_enqueue_media();
            wp_enqueue_style( 'em-admin-jquery-ui' );
            wp_enqueue_script( 'jquery-ui-slider' );
            wp_enqueue_script( 'em-admin-jscolor' );
            wp_enqueue_script( 'em-type-admin-custom-js' );
            wp_localize_script(
                'em-type-admin-custom-js',
                'em_type_object',
                array(
					'media_title'  => esc_html__( 'Choose Image', 'eventprime-event-calendar-management' ),
					'media_button' => esc_html__( 'Use image', 'eventprime-event-calendar-management' ),
				)
            );
            wp_enqueue_style( 'em-type-admin-custom-css' );
        }

        if ( $current_page == 'em_venue' ) {

            wp_enqueue_media();
            wp_enqueue_script( 'jquery-ui-datepicker' );

            wp_enqueue_script(
                'em-venue-admin-custom-js',
                plugin_dir_url( __FILE__ ) . 'js/event-venues/em-venue-admin-custom.js',
                false,
                $this->version
            );

            wp_localize_script(
                'em-venue-admin-custom-js',
                'em_venue_object',
                array(
					'media_title'  => esc_html__( 'Choose Image', 'eventprime-event-calendar-management' ),
					'media_button' => esc_html__( 'Use image', 'eventprime-event-calendar-management' ),
				)
            );

            wp_enqueue_style(
                'em-venue-admin-custom-css',
                plugin_dir_url( __FILE__ ) . 'css/event-venues/em-venue-admin-custom.css',
                false,
                $this->version
            );

            wp_enqueue_style(
                'em-venue-admin-jquery-ui',
                plugin_dir_url( __FILE__ ) . 'css/jquery-ui.min.css',
                false,
                $this->version
            );

            wp_enqueue_style( 'em-meta-box-admin-custom-css' );

            wp_register_script( 'em-google-map', plugin_dir_url( __FILE__ ) . 'js/em-map.js', array( 'jquery' ), $this->version );
            $gmap_api_key = $ep_functions->ep_get_global_settings( 'gmap_api_key' );
            if ( $gmap_api_key ) {
                wp_enqueue_script(
                    'google_map_key',
                    'https://maps.googleapis.com/maps/api/js?key=' . $gmap_api_key . '&libraries=places,marker&callback=Function.prototype',
                    array(),
                    $this->version
                );
            }
            wp_localize_script(
                'em-venue-admin-custom-js',
                'eventprime',
                array(
					'gmap_api_key' => $gmap_api_key,
				)
            );
        }

        if ( $current_page == 'em_event_organizer' ) {
            wp_enqueue_media();

            wp_enqueue_script( 'ep-common-script', plugin_dir_url( __FILE__ ) . 'js/ep-common-script.js', array( 'jquery' ), $this->version );

            // localized global settings
            $global_settings = $ep_functions->ep_get_global_settings();
            $currency_symbol = $ep_functions->ep_currency_symbol();
            wp_localize_script(
                'ep-common-script',
                'eventprime',
                array(
					'global_settings' => $global_settings,
					'currency_symbol' => $currency_symbol,
					'ajaxurl'         => admin_url( 'admin-ajax.php' ),
					'trans_obj'       => $ep_functions->ep_define_common_field_errors(),
				)
            );

            wp_enqueue_script(
                'em-organizer-admin-custom-js',
                plugin_dir_url( __FILE__ ) . 'js/em-organizer-admin-custom.js',
                false,
                $this->version
            );

            wp_localize_script(
                'em-organizer-admin-custom-js',
                'em_organizer_object',
                array(
					'media_title'       => esc_html__( 'Choose Image', 'eventprime-event-calendar-management' ),
					'media_button'      => esc_html__( 'Use image', 'eventprime-event-calendar-management' ),
					'max_field_warning' => esc_html__( 'Maximum limit reached for adding', 'eventprime-event-calendar-management' ),
				)
            );
            wp_enqueue_style(
                'em-organizer-admin-custom-css',
                plugin_dir_url( __FILE__ ) . 'css/em-organizer-admin-custom.css',
                false,
                $this->version
            );
            wp_enqueue_style( 'ep-toast-css' );
            wp_enqueue_script( 'ep-toast-js' );
            wp_enqueue_script( 'ep-toast-message-js' );
            wp_localize_script(
            'ep-toast-message-js', 
            'eventprime_toast', 
            array(
               'error'=> esc_html__( 'Error', 'eventprime-event-calendar-management' ),
               'success'=> esc_html__( 'Success', 'eventprime-event-calendar-management' ),
               'warning'=> esc_html__( 'Warning', 'eventprime-event-calendar-management' ),
            )
        );
        }

        if ( $current_page == 'performer_edit' ) {
            wp_enqueue_script( 'ep-common-script', plugin_dir_url( __FILE__ ) . 'js/ep-common-script.js', array( 'jquery' ), $this->version );

            // localized global settings
            $global_settings = $ep_functions->ep_get_global_settings();
            $currency_symbol = $ep_functions->ep_currency_symbol();
            wp_localize_script(
                'ep-common-script',
                'eventprime',
                array(
					'global_settings' => $global_settings,
					'currency_symbol' => $currency_symbol,
					'ajaxurl'         => admin_url( 'admin-ajax.php' ),
					'trans_obj'       => $ep_functions->ep_define_common_field_errors(),
				)
            );
            wp_enqueue_script( 'ep-admin-utility-script', plugin_dir_url( __FILE__ ) . 'js/ep-admin-common-utility.js', array( 'jquery', 'jquery-ui-tooltip', 'jquery-ui-dialog' ), $this->version );
            wp_localize_script(
                'ep-admin-utility-script',
                'ep_admin_utility_script',
                array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
				)
            );

            wp_enqueue_media();
            wp_register_style(
                'em-performer-meta-box-css',
                plugin_dir_url( __FILE__ ) . 'css/em-admin-performer-metabox-custom.css',
                false,
                $this->version
            );

            wp_register_script(
                'em-performer-meta-box-js',
                plugin_dir_url( __FILE__ ) . 'js/em-admin-performer-metabox-custom.js',
                array( 'jquery' ),
                $this->version
            );

            wp_localize_script(
                'em-performer-meta-box-js',
                'em_performer_meta_box_object',
                array(
					'max_field_warning' => esc_html__( 'Maximum limit reached for adding', 'eventprime-event-calendar-management' ),
						//'remove_label' => esc_html__( 'Remove', 'eventprime-event-calendar-management' ),
				)
            );
            wp_enqueue_style( 'ep-toast-css' );
            wp_enqueue_script( 'ep-toast-js' );
            wp_enqueue_script( 'ep-toast-message-js' );
            wp_localize_script(
                'ep-toast-message-js', 
                'eventprime_toast', 
                array(
                   'error'=> esc_html__( 'Error', 'eventprime-event-calendar-management' ),
                   'success'=> esc_html__( 'Success', 'eventprime-event-calendar-management' ),
                   'warning'=> esc_html__( 'Warning', 'eventprime-event-calendar-management' ),
                )
            );
        }

        if ( $current_page == 'bookings' || $current_page == 'booking_edit' ) {
            $global_settings = $ep_functions->ep_get_global_settings();
            $currency_symbol = $ep_functions->ep_currency_symbol();
            wp_enqueue_style( 'em-admin-jquery-ui' );
            wp_enqueue_style(
                'em-bookings-css',
                plugin_dir_url( __FILE__ ) . 'css/ep-abmin-booking-style.css',
                false,
                $this->version
            );

            wp_enqueue_script( 'ep-admin-utility-script', plugin_dir_url( __FILE__ ) . 'js/ep-admin-common-utility.js', array( 'jquery', 'jquery-ui-tooltip', 'jquery-ui-dialog' ), $this->version );
            wp_enqueue_script( 'ep-common-script', plugin_dir_url( __FILE__ ) . 'js/ep-common-script.js', array( 'jquery' ), $this->version );
            wp_localize_script(
                'ep-common-script',
                'eventprime',
                array(
                    'global_settings' => $global_settings,
                    'currency_symbol' => $currency_symbol,
                    'ajaxurl'         => admin_url( 'admin-ajax.php' ),
                    'trans_obj'       => $ep_functions->ep_define_common_field_errors(),
                )
            );

            // booking js
            wp_enqueue_script(
                'em-booking-js',
                plugin_dir_url( __FILE__ ) . 'js/ep-event-booking-admin.js',
                array( 'jquery', 'jquery-ui-datepicker' ),
                $this->version
            );

            $nonce = wp_create_nonce( 'ep_booking_nonce' );
            wp_localize_script(
                'em-booking-js',
                'ep_booking_obj',
                array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => $nonce,
                )
            );

        }

        if ( $current_page == 'ep-events-reports' ) {
            wp_enqueue_style(
                'ep-daterangepicker-css',
                plugin_dir_url( __FILE__ ) . 'css/daterangepicker.css',
                false,
                $this->version
            );
            wp_enqueue_script(
                'ep-moment-js',
                plugin_dir_url( __FILE__ ) . 'js/moment.min.js',
                array( 'jquery' ),
                $this->version
            );
            wp_enqueue_script(
                'ep-daterangepicker-js',
                plugin_dir_url( __FILE__ ) . 'js/daterangepicker.min.js',
                array( 'jquery' ),
                $this->version
            );
            $global_settings = $ep_functions->ep_get_global_settings();
            wp_localize_script(
                'ep-daterangepicker-js',
                'eventprime',
                array(
					'global_settings' => $global_settings,
				)
            );

            wp_enqueue_script( 'ep-common-script', plugin_dir_url( __FILE__ ) . 'js/ep-common-script.js', array( 'jquery' ), $this->version );
            // localized global settings
            $global_settings = $ep_functions->ep_get_global_settings();
            $currency_symbol = $ep_functions->ep_currency_symbol();
            wp_localize_script(
                'ep-common-script',
                'eventprime',
                array(
					'global_settings' => $global_settings,
					'currency_symbol' => $currency_symbol,
					'ajaxurl'         => admin_url( 'admin-ajax.php' ),
					'trans_obj'       => $ep_functions->ep_define_common_field_errors(),
				)
            );

            wp_enqueue_script( 'google_charts', 'https://www.gstatic.com/charts/loader.js', array( 'jquery' ) );
            wp_enqueue_style( 'ep-admin-reports', plugin_dir_url( __FILE__ ) . 'css/ep-admin-reports.css', false, $this->version );
            wp_enqueue_script(
                'ep-advanced-reports',
                plugin_dir_url( __FILE__ ) . 'js/ep-admin-reports.js',
                array( 'jquery' ),
                $this->version
            );
        }

        if ( $current_page=='ep-event-attendees-list' ) {
            wp_enqueue_script( 'ep-admin-utility-script', plugin_dir_url( __FILE__ ) . 'js/ep-admin-common-utility.js', array( 'jquery', 'jquery-ui-tooltip', 'jquery-ui-dialog' ), $this->version );
            wp_localize_script(
                'ep-admin-utility-script',
                'ep_admin_utility_script',
                array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
				)
            );
        }
    }

    /**
     * Register plugin's default taxonomies
     */
    public function register_taxonomies() {
        if ( !is_blog_installed() ) {
            return;
        }

        if ( taxonomy_exists( 'em_event_type' ) ) {
            return;
        }
        $ep_functions = new Eventprime_Basic_Functions();

        do_action( 'eventprime_register_taxonomy' );

        $plural_type_text   = $ep_functions->ep_global_settings_button_title( 'Event-Types' );
        $singular_type_text = $ep_functions->ep_global_settings_button_title( 'Event-Type' );

        if ( $plural_type_text == 'Event-Types' ) {
            $plural_type_text = 'Types';
        }

        register_taxonomy(
            'em_event_type',
            'em_event',
            array(
                'label'              => $plural_type_text,
                'labels'             => array(
                    'name'              => $plural_type_text,
                    'singular_name'     => $singular_type_text,
                    'menu_name'         => esc_html( $plural_type_text ),
                    /* translators: %s is the plural form of the event type. */
                    'search_items'      => sprintf( esc_html__( 'Search %s', 'eventprime-event-calendar-management' ), $plural_type_text ),
                    /* translators: %s is the plural form of the event type. */
                    'all_items'         => sprintf( esc_html__( 'All %s', 'eventprime-event-calendar-management' ), $plural_type_text ),
                    /* translators: %s is the singular form of the event type. */
                    'parent_item'       => sprintf( esc_html__( 'Parent %s', 'eventprime-event-calendar-management' ), $singular_type_text ),
                    /* translators: %s is the singular form of the event type. */
                    'parent_item_colon' => sprintf( esc_html__( 'Parent %s:', 'eventprime-event-calendar-management' ), $singular_type_text ),
                    /* translators: %s is the singular form of the event type. */
                    'edit_item'         => sprintf( esc_html__( 'Edit %s', 'eventprime-event-calendar-management' ), $singular_type_text ),
                    /* translators: %s is the singular form of the event type. */
                    'update_item'       => sprintf( esc_html__( 'Update %s', 'eventprime-event-calendar-management' ), $singular_type_text ),
                    /* translators: %s is the singular form of the event type. */
                    'add_new_item'      => sprintf( esc_html__( 'Add New %s', 'eventprime-event-calendar-management' ), $singular_type_text ),
                    /* translators: %s is the singular form of the event type. */
                    'new_item_name'     => sprintf( esc_html__( 'New %s', 'eventprime-event-calendar-management' ), $singular_type_text ),
                    /* translators: %s is the plural form of the event type. */
                    'not_found'         => sprintf( esc_html__( 'No %s found', 'eventprime-event-calendar-management' ), $plural_type_text ),
                ),
                'public'             => false, // Hide from public queries
                'publicly_queryable' => false, // Disable querying on front end
                'show_ui'            => true,
                'show_in_nav_menus'  => true,
                'show_in_menu'       => true,
                'query_var'          => true,
                'hierarchical'       => true,
                'single_value'      => true, 
                'show_in_quick_edit' => false,
                'capabilities'       => array(
                    'manage_terms' => 'manage_em_event_terms',
                    'edit_terms'   => 'edit_em_event_terms',
                    'delete_terms' => 'delete_em_event_terms',
                    'assign_terms' => 'assign_em_event_terms',
                ),
                'rewrite'            => array(
                    'slug'       => $ep_functions->ep_get_seo_page_url( 'event-type' ),
                    //'ep_mask'    => EP_EM_EVENTS,
                    'with_front' => true,
                ),
               // 'meta_box_cb'        => array( $ep_functions, 'custom_em_event_type_dropdown' ),
                //'show_in_rest' => true,
            )
        );

        $plural_venue_text   = $ep_functions->ep_global_settings_button_title( 'Venues' );
        $singular_venue_text = $ep_functions->ep_global_settings_button_title( 'Venue' );

        register_taxonomy(
            'em_venue',
            'em_event',
            array(
                'label'              => $plural_venue_text,
                'labels'             => array(
                    'name'              => $plural_venue_text,
                    'singular_name'     => $singular_venue_text,
                    'menu_name'         => esc_html( $plural_venue_text ),
                    /* translators: %s is the plural form of the venue. */
                    'search_items'      => sprintf( esc_html__( 'Search %s', 'eventprime-event-calendar-management' ), $plural_venue_text ),
                    /* translators: %s is the plural form of the venue. */
                    'all_items'         => sprintf( esc_html__( 'All %s', 'eventprime-event-calendar-management' ), $plural_venue_text ),
                    /* translators: %s is the singular form of the venue. */
                    'parent_item'       => sprintf( esc_html__( 'Parent %s', 'eventprime-event-calendar-management' ), $singular_venue_text ),
                    /* translators: %s is the singular form of the venue. */
                    'parent_item_colon' => sprintf( esc_html__( 'Parent %s:', 'eventprime-event-calendar-management' ), $singular_venue_text ),
                    /* translators: %s is the singular form of the venue. */
                    'edit_item'         => sprintf( esc_html__( 'Edit %s', 'eventprime-event-calendar-management' ), $singular_venue_text ),
                    /* translators: %s is the singular form of the venue. */
                    'update_item'       => sprintf( esc_html__( 'Update %s', 'eventprime-event-calendar-management' ), $singular_venue_text ),
                    /* translators: %s is the singular form of the venue. */
                    'add_new_item'      => sprintf( esc_html__( 'Add New %s', 'eventprime-event-calendar-management' ), $singular_venue_text ),
                    /* translators: %s is the singular form of the venue. */
                    'new_item_name'     => sprintf( esc_html__( 'New %s', 'eventprime-event-calendar-management' ), $singular_venue_text ),
                    /* translators: %s is the plural form of the venue. */
                    'not_found'         => sprintf( esc_html__( 'No %s found', 'eventprime-event-calendar-management' ), $plural_venue_text ),
                ),
                'show_ui'            => true,
                'public'             => false, // Hide from public queries
                'publicly_queryable' => false, // Disable querying on front end
                'show_in_nav_menus'  => true,
                'show_in_menu'       => true,
                'query_var'          => true,
                'hierarchical'       => true,
                'show_in_quick_edit' => false,
                'rewrite'            => array(
                    'slug'       => $ep_functions->ep_get_seo_page_url( 'venue' ),
                    //'ep_mask'  => EP_EM_EVENTS,
                    'with_front' => true,
                ),
                'capabilities'       => array(
                    'manage_terms' => 'manage_em_event_terms',
                    'edit_terms'   => 'edit_em_event_terms',
                    'delete_terms' => 'delete_em_event_terms',
                    'assign_terms' => 'assign_em_event_terms',
                ),
                //'meta_box_cb' => array($ep_functions, 'custom_em_venue_dropdown'),
                //'meta_box_cb'        => array( $ep_functions, 'ep_taxonomy_select_meta_box' ),
                //'show_in_rest'      => true,
            )
        );

        $plural_organizer_text   = $ep_functions->ep_global_settings_button_title( 'Organizers' );
        $singular_organizer_text = $ep_functions->ep_global_settings_button_title( 'Organizer' );

        register_taxonomy(
            'em_event_organizer',
            'em_event',
            array(
                'label'              => $plural_organizer_text,
                'labels'             => array(
                    'name'              => $plural_organizer_text,
                    'singular_name'     => $singular_organizer_text,
                    'menu_name'         => esc_html( $plural_organizer_text ),
                    /* translators: %s is the plural form of the event organizer. */
                    'search_items'      => sprintf( esc_html__( 'Search %s', 'eventprime-event-calendar-management' ), $plural_organizer_text ),
                    /* translators: %s is the plural form of the event organizer. */
                    'all_items'         => sprintf( esc_html__( 'All %s', 'eventprime-event-calendar-management' ), $plural_organizer_text ),
                    /* translators: %s is the singular form of the event organizer. */
                    'parent_item'       => sprintf( esc_html__( 'Parent %s', 'eventprime-event-calendar-management' ), $singular_organizer_text ),
                    /* translators: %s is the singular form of the event organizer. */
                    'parent_item_colon' => sprintf( esc_html__( 'Parent %s:', 'eventprime-event-calendar-management' ), $singular_organizer_text ),
                    /* translators: %s is the singular form of the event organizer. */
                    'edit_item'         => sprintf( esc_html__( 'Edit %s', 'eventprime-event-calendar-management' ), $singular_organizer_text ),
                    /* translators: %s is the singular form of the event organizer. */
                    'update_item'       => sprintf( esc_html__( 'Update %s', 'eventprime-event-calendar-management' ), $singular_organizer_text ),
                    /* translators: %s is the singular form of the event organizer. */
                    'add_new_item'      => sprintf( esc_html__( 'Add New %s', 'eventprime-event-calendar-management' ), $singular_organizer_text ),
                    /* translators: %s is the singular form of the event organizer. */
                    'new_item_name'     => sprintf( esc_html__( 'New %s', 'eventprime-event-calendar-management' ), $singular_organizer_text ),
                    /* translators: %s is the plural form of the event organizer. */
                    'not_found'         => sprintf( esc_html__( 'No %s found', 'eventprime-event-calendar-management' ), $plural_organizer_text ),
                ),
                'show_ui'            => true,
                'public'             => false, // Hide from public queries
                'publicly_queryable' => false, // Disable querying on front end
                'query_var'          => true,
                'show_in_nav_menus'  => true,
                'show_in_menu'       => true,
                'hierarchical'       => true,
                'show_in_quick_edit' => false,
                'capabilities'       => array(
                    'manage_terms' => 'manage_em_event_terms',
                    'edit_terms'   => 'edit_em_event_terms',
                    'delete_terms' => 'delete_em_event_terms',
                    'assign_terms' => 'assign_em_event_terms',
                ),
                'rewrite'            => array(
                    'slug'       => $ep_functions->ep_get_seo_page_url( 'organizer' ),
                    //'ep_mask'  => EP_EM_EVENTS,
                    'with_front' => true,
                ),
                //'show_in_rest'      => true,
            )
        );

        do_action( 'eventprime_after_register_taxonomy' );
    }

    /**
     * Register core post types.
     */
    public function register_post_types() {
        if ( !is_blog_installed() || post_type_exists( 'em_event' ) ) {
            return;
        }
        $ep_functions = new Eventprime_Basic_Functions();

        do_action( 'eventprime_register_post_type' );

        $support = array( 'title', 'editor', 'thumbnail', 'custom-fields', 'publicize', 'wpcom-markdown', 'comments' );

        register_post_type(
            'em_event',
            array(
				'labels'              => array(
					'name'                  => esc_html__( 'Events', 'eventprime-event-calendar-management' ),
					'singular_name'         => esc_html__( 'Event', 'eventprime-event-calendar-management' ),
					'add_new'               => esc_html__( 'Add New', 'eventprime-event-calendar-management' ),
					'add_new_item'          => esc_html__( 'Add New Event', 'eventprime-event-calendar-management' ),
					'edit'                  => esc_html__( 'Edit', 'eventprime-event-calendar-management' ),
					'edit_item'             => esc_html__( 'Edit Event', 'eventprime-event-calendar-management' ),
					'new_item'              => esc_html__( 'New Event', 'eventprime-event-calendar-management' ),
					'view'                  => esc_html__( 'View Event', 'eventprime-event-calendar-management' ),
					'view_item'             => esc_html__( 'View Event', 'eventprime-event-calendar-management' ),
					'not_found'             => esc_html__( 'No Events found', 'eventprime-event-calendar-management' ),
					'not_found_in_trash'    => esc_html__( 'No Events found in trash', 'eventprime-event-calendar-management' ),
					'featured_image'        => esc_html__( 'Event Image', 'eventprime-event-calendar-management' ),
					'set_featured_image'    => esc_html__( 'Set event image', 'eventprime-event-calendar-management' ),
					'remove_featured_image' => esc_html__( 'Remove event image', 'eventprime-event-calendar-management' ),
					'use_featured_image'    => esc_html__( 'Use as event image', 'eventprime-event-calendar-management' ),
					'menu_name'             => esc_html__( 'All Events', 'eventprime-event-calendar-management' ),
					'search_items'          => esc_html__( 'Search Event', 'eventprime-event-calendar-management' ),
				),
				'description'         => esc_html__( 'Here you can add new events.', 'eventprime-event-calendar-management' ),
				'public'              => true,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_nav_menus'   => true,
				'show_in_menu'        => true,
				'has_archive'         => false,
				'capability_type'     => 'em_event',
				'map_meta_cap'        => true,
				'exclude_from_search' => false,
				'hierarchical'        => false,
				'query_var'           => true,
				'menu_icon'           => 'dashicons-tickets-alt',
				'supports'            => array( 'title', 'editor', 'thumbnail', 'custom-fields', 'publicize', 'wpcom-markdown', 'comments' ),
				'rewrite'             => array(
					'slug'       => $ep_functions->ep_get_seo_page_url( 'event' ),
					'with_front' => true,
				),
                                //'show_in_rest'        => true,
			)
        );

        $plural_performer_text   = $ep_functions->ep_global_settings_button_title( 'Performers' );
        $singular_performer_text = $ep_functions->ep_global_settings_button_title( 'Performer' );

        register_post_type(
            'em_performer',
            array(
				'labels'              => array(
					'name'                  => $plural_performer_text,
					'singular_name'         => $singular_performer_text,
					/* translators: %s is the singular form of performer. */
					'add_new'               => sprintf( esc_html__( 'Add %s', 'eventprime-event-calendar-management' ), $singular_performer_text ),
					/* translators: %s is the singular form of performer. */
					'add_new_item'          => sprintf( esc_html__( 'Add New %s', 'eventprime-event-calendar-management' ), $singular_performer_text ),
					/* translators: %s is the singular form of performer. */
					'edit'                  => sprintf( esc_html__( 'Edit %s', 'eventprime-event-calendar-management' ), $singular_performer_text ),
					/* translators: %s is the singular form of performer. */
					'edit_item'             => sprintf( esc_html__( 'Edit %s', 'eventprime-event-calendar-management' ), $singular_performer_text ),
					/* translators: %s is the singular form of performer. */
					'new_item'              => sprintf( esc_html__( 'New %s', 'eventprime-event-calendar-management' ), $singular_performer_text ),
					/* translators: %s is the singular form of performer. */
					'view'                  => sprintf( esc_html__( 'View %s', 'eventprime-event-calendar-management' ), $singular_performer_text ),
					/* translators: %s is the singular form of performer. */
					'view_item'             => sprintf( esc_html__( 'View %s', 'eventprime-event-calendar-management' ), $singular_performer_text ),
					/* translators: %s is the singular form of performer. */
					'not_found'             => sprintf( esc_html__( 'No %s found', 'eventprime-event-calendar-management' ), $singular_performer_text ),
					/* translators: %s is the singular form of performer. */
					'not_found_in_trash'    => sprintf( esc_html__( 'No %s found in trash', 'eventprime-event-calendar-management' ), $singular_performer_text ),
					/* translators: %s is the singular form of performer. */
					'featured_image'        => sprintf( esc_html__( '%s Image', 'eventprime-event-calendar-management' ), $singular_performer_text ),
					/* translators: %s is the singular form of performer. */
					'set_featured_image'    => sprintf( esc_html__( 'Set %s image', 'eventprime-event-calendar-management' ), $singular_performer_text ),
					/* translators: %s is the singular form of performer. */
					'remove_featured_image' => sprintf( esc_html__( 'Remove %s image', 'eventprime-event-calendar-management' ), $singular_performer_text ),
					/* translators: %s is the singular form of performer. */
					'use_featured_image'    => sprintf( esc_html__( 'Use as %s image', 'eventprime-event-calendar-management' ), $singular_performer_text ),
					'menu_name'             => $plural_performer_text,
				),
				/* translators: %s is the plural form of performer. */
				'description'         => sprintf( esc_html__( 'Here you can add new %s.', 'eventprime-event-calendar-management' ), strtolower( $plural_performer_text ) ),
				'public'              => true,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_nav_menus'   => true,
				'show_in_menu'        => 'edit.php?post_type=em_event',
				'has_archive'         => false,
				'map_meta_cap'        => true,
				'exclude_from_search' => false,
				'hierarchical'        => false,
				'query_var'           => true,
				'menu_icon'           => 'dashicons-businessperson',
				'supports'            => $support,
				'capability_type'     => 'em_performer',
				'rewrite'             => array(
					'slug'       => $ep_functions->ep_get_seo_page_url( 'performer' ),
					'with_front' => true,
				),
            )
        );

        register_post_type(
            'em_booking',
            array(
				'labels'              => array(
					'name'                  => esc_html__( 'Bookings', 'eventprime-event-calendar-management' ),
					'singular_name'         => esc_html__( 'Booking', 'eventprime-event-calendar-management' ),
					'add_new'               => esc_html__( 'Add Booking', 'eventprime-event-calendar-management' ),
					'add_new_item'          => esc_html__( 'Add New Booking', 'eventprime-event-calendar-management' ),
					'edit'                  => esc_html__( 'Edit', 'eventprime-event-calendar-management' ),
					'edit_item'             => esc_html__( 'Edit Booking', 'eventprime-event-calendar-management' ),
					'new_item'              => esc_html__( 'New Booking', 'eventprime-event-calendar-management' ),
					'view'                  => esc_html__( 'View Booking', 'eventprime-event-calendar-management' ),
					'view_item'             => esc_html__( 'View Booking', 'eventprime-event-calendar-management' ),
					'not_found'             => esc_html__( 'No Booking found', 'eventprime-event-calendar-management' ),
					'not_found_in_trash'    => esc_html__( 'No Booking found in trash', 'eventprime-event-calendar-management' ),
					'featured_image'        => esc_html__( 'Booking Image', 'eventprime-event-calendar-management' ),
					'set_featured_image'    => esc_html__( 'Set Booking image', 'eventprime-event-calendar-management' ),
					'remove_featured_image' => esc_html__( 'Remove Booking image', 'eventprime-event-calendar-management' ),
					'use_featured_image'    => esc_html__( 'Use as Booking image', 'eventprime-event-calendar-management' ),
					'menu_name'             => esc_html__( 'Bookings', 'eventprime-event-calendar-management' ),
				),
				'description'         => esc_html__( 'Here you can add new bookings.', 'eventprime-event-calendar-management' ),
				'public'              => false,
				'publicly_queryable'  => false,
				'show_ui'             => true,
				'show_in_nav_menus'   => false,
				'show_in_menu'        => 'edit.php?post_type=em_event',
				'has_archive'         => false,
				'map_meta_cap'        => false,
				'exclude_from_search' => false,
				'hierarchical'        => false,
				'query_var'           => false,
				'supports'            => $support,
				'show_in_nav_menus'   => false,
				'capability_type'     => 'em_booking',
				'capabilities'        => array(
					'create_posts' => false,
				),
				'rewrite'             => array(
					'slug'       => 'booking',
					'with_front' => true,
				),
			)
        );

        do_action( 'eventprime_after_register_post_type' );

        flush_rewrite_rules();
    }

    /**
     * Register our custom post statuses, used for event status.
     */
    public function register_post_status() {
        register_post_status(
            'emexpired',
            array(
				'label'                     => _x( 'EM Expired', 'Event status', 'eventprime-event-calendar-management' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s is the number of expired events. */
				'label_count'               => _n_noop( 'EM Expired <span class="count">(%s)</span>', 'EM Expired <span class="count">(%s)</span>', 'eventprime-event-calendar-management' ),
            )
        );

        register_post_status(
            'expired',
            array(
				'label'                     => _x( 'Expired', 'Event status', 'eventprime-event-calendar-management' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s is the number of expired events. */
				'label_count'               => _n_noop( 'Expired <span class="count">(%s)</span>', 'Expired <span class="count">(%s)</span>', 'eventprime-event-calendar-management' ),
            )
        );

        register_post_status(
            'cancelled',
            array(
				'label'                     => _x( 'Cancelled', 'Event status', 'eventprime-event-calendar-management' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s is the number of cancelled events. */
				'label_count'               => _n_noop( 'Cancelled <span class="count">(%s)</span>', 'Cancelled <span class="count">(%s)</span>', 'eventprime-event-calendar-management' ),
            )
        );

        register_post_status(
            'pending',
            array(
				'label'                     => _x( 'Pending', 'Event status', 'eventprime-event-calendar-management' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s is the number of pending events. */
				'label_count'               => _n_noop( 'Pending <span class="count">(%s)</span>', 'Pending <span class="count">(%s)</span>', 'eventprime-event-calendar-management' ),
            )
        );

        register_post_status(
            'refunded',
            array(
				'label'                     => _x( 'Refunded', 'Event status', 'eventprime-event-calendar-management' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s is the number of refunded events. */
				'label_count'               => _n_noop( 'Refunded <span class="count">(%s)</span>', 'Refunded <span class="count">(%s)</span>', 'eventprime-event-calendar-management' ),
            )
        );

        register_post_status(
            'completed',
            array(
				'label'                     => _x( 'Completed', 'Event status', 'eventprime-event-calendar-management' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s is the number of completed events. */
				'label_count'               => _n_noop( 'Completed <span class="count">(%s)</span>', 'Completed <span class="count">(%s)</span>', 'eventprime-event-calendar-management' ),
            )
        );
    }

    /**
     * Remove default meta boxes
     */
    public function ep_event_remove_meta_boxes() {
        remove_meta_box( 'postexcerpt', 'em_event', 'normal' );
        remove_meta_box( 'commentsdiv', 'em_event', 'normal' );
        remove_meta_box( 'commentstatusdiv', 'em_event', 'side' );
        remove_meta_box( 'commentstatusdiv', 'em_event', 'normal' );
        remove_meta_box( 'postcustom', 'em_event', 'normal' );
        remove_meta_box( 'pageparentdiv', 'em_event', 'side' );
    }

    /**
     * Register meta box for event
     */
    public function ep_event_register_meta_boxes() {
        $ep_functions = new Eventprime_Basic_Functions();

        add_meta_box(
            'ep_event_register_meta_boxes',
            esc_html__( 'Event Settings', 'eventprime-event-calendar-management' ),
            array( $this, 'ep_add_event_setting_box' ),
            'em_event',
            'normal',
            'high'
        );
        add_meta_box(
            'ep_event-stats',
            esc_html__( 'Summary', 'eventprime-event-calendar-management' ),
            array( $this, 'ep_add_event_stats_box' ),
            'em_event',
            'side',
            'high'
        );
        $plural_performer_text = $ep_functions->ep_global_settings_button_title( 'Performers' );
        add_meta_box(
            'ep_event-performers',
            $plural_performer_text,
            array( $this, 'ep_add_event_performer_box' ),
            'em_event',
            'side',
            'low'
        );
        add_meta_box(
            'ep_event-gallery-images',
            esc_html__( 'Event gallery', 'eventprime-event-calendar-management' ),
            array( $this, 'ep_add_event_gallery_box' ),
            'em_event',
            'side',
            'low'
        );
        do_action( 'ep_event_register_custom_meta_boxes' );
    }

    /**
     * Remove default meta boxes
     */
    public function ep_bookings_remove_meta_boxes() {
        remove_meta_box( 'postexcerpt', array( 'em_booking', 'em_event' ), 'normal' );
        remove_meta_box( 'commentsdiv', array( 'em_booking', 'em_event' ), 'normal' );
        remove_meta_box( 'commentstatusdiv', array( 'em_booking', 'em_event' ), 'side' );
        remove_meta_box( 'commentstatusdiv', array( 'em_booking', 'em_event' ), 'normal' );
        remove_meta_box( 'postcustom', array( 'em_booking', 'em_event' ), 'normal' );
        remove_meta_box( 'pageparentdiv', array( 'em_booking', 'em_event' ), 'side' );
        remove_meta_box( 'postimagediv', array( 'em_booking' ), 'side' );
        remove_meta_box( 'postdivrich', array( 'em_booking' ), 'normal' );
        remove_meta_box( 'submitdiv', array( 'em_booking' ), 'side' );
    }

    /**
     * Register meta box for booking
     */
    public function ep_bookings_register_meta_boxes() {
        $ep_functions = new Eventprime_Basic_Functions();
        add_meta_box(
            'ep_booking_general',
            esc_html__( 'General Details', 'eventprime-event-calendar-management' ),
            array( $this, 'ep_general_booking_box' ),
            'em_booking',
            'normal',
            'high'
        );

        add_meta_box(
            'ep_booking_tickets',
            esc_html__( 'Event Tickets', 'eventprime-event-calendar-management' ),
            array( $this, 'ep_tickets_booking_box' ),
            'em_booking',
            'normal',
            'low'
        );
        add_meta_box(
            'ep_tickets_attendies',
            esc_html__( 'Tickets Attendees', 'eventprime-event-calendar-management' ),
            array( $this, 'ep_tickets_attendies_box' ),
            'em_booking',
            'normal',
            'low'
        );
        add_meta_box(
            'ep_booking_notes',
            esc_html__( 'Booking Notes', 'eventprime-event-calendar-management' ),
            array( $this, 'ep_booking_notes_box' ),
            'em_booking',
            'side',
            'low'
        );

        add_meta_box(
            'ep_tickets_booking_fields',
            esc_html__( 'Booking Fields Data', 'eventprime-event-calendar-management' ),
            array( $this, 'ep_tickets_booking_fields_box' ),
            'em_booking',
            'normal',
            'low'
        );

        do_action( 'ep_bookings_register_meta_boxes_addon' );

        add_meta_box(
            'ep_transacton_log',
            esc_html__( 'Transaction Log', 'eventprime-event-calendar-management' ),
            array( $this, 'ep_transacton_log_box' ),
            'em_booking',
            'normal',
            'low'
        );

        add_meta_box(
            'ep_event_register_meta_boxes',
            esc_html__( 'Event Settings', 'eventprime-event-calendar-management' ),
            array( $this, 'ep_add_event_setting_box' ),
            'em_event',
            'normal',
            'high'
        );
        add_meta_box(
            'ep_event-stats',
            esc_html__( 'Summary', 'eventprime-event-calendar-management' ),
            array( $this, 'ep_add_event_stats_box' ),
            'em_event',
            'side',
            'high'
        );
        $plural_performer_text = $ep_functions->ep_global_settings_button_title( 'Performers' );
        add_meta_box(
            'ep_event-performers',
            $plural_performer_text,
            array( $this, 'ep_add_event_performer_box' ),
            'em_event',
            'side',
            'low'
        );
        add_meta_box(
            'ep_event-gallery-images',
            esc_html__( 'Event gallery', 'eventprime-event-calendar-management' ),
            array( $this, 'ep_add_event_gallery_box' ),
            'em_event',
            'side',
            'low'
        );

        do_action( 'ep_event_register_custom_meta_boxes' );
    }

    /*
     * General Booking Section
     */

    public function ep_general_booking_box( $post ): void {
        if ( $post->post_type == 'em_booking' ) {
            // wp_enqueue_style( 'em-bookings-css' );
            // wp_enqueue_script( 'em-bookings-js' );

            wp_nonce_field( 'ep_save_booking_data', 'ep_booking_meta_nonce' );
            include 'partials/metaboxes/meta-box-booking-general.php';
        }
    }

    /*
     * General Booking Section
     */

    public function ep_tickets_booking_box( $post ) {
        include 'partials/metaboxes/meta-box-booking-tickets.php';
    }

    /*
     * Attendies Section
     */

    public function ep_tickets_attendies_box( $post ) {
        wp_nonce_field( 'ep_booking_attendee_data', 'ep_booking_attendee_data_nonce' );
        include 'partials/metaboxes/meta-box-booking-attendees.php';
    }

    /*
     * General Notes Section
     */

    public function ep_booking_notes_box( $post ) {
        include 'partials/metaboxes/meta-box-booking-notes.php';
    }

    /*
     * Action button
     */

    public function ep_booking_actions_box( $post ) {
        include 'partials/metaboxes/meta-box-booking-action.php';
    }

    /*
     * Transaction log section
     */

    public function ep_transacton_log_box( $post ) {
        include 'partials/metaboxes/meta-box-transaction-log.php';
    }

    /**
     * Show booking fields data
     */
    public function ep_tickets_booking_fields_box( $post ) {
        include 'partials/metaboxes/meta-box-booking-fields-data.php';
    }

    public function ep_add_event_setting_box( $post ) {
        if ( $post->post_type == 'em_event' ) {

            // enqueue custom scripts and styles from extension
            do_action( 'ep_event_enqueue_custom_scripts' );
            wp_nonce_field( 'ep_save_event_data', 'ep_event_meta_nonce' );
            include 'partials/metaboxes/meta-box-panel-html.php';
        }
    }

    /**
     * Return tabs data
     *
     * @return array
     */
    public function get_ep_event_meta_tabs() {
        $ep_functions = new Eventprime_Basic_Functions();
        $tabs         = apply_filters(
            'ep_event_meta_tabs',
            array(
				'datetime'       => array(
					'label'    => esc_html__( 'Date & Time', 'eventprime-event-calendar-management' ),
					'target'   => 'ep_event_datetime_data',
					'class'    => array( 'ep_event_date_time' ),
					'priority' => 10,
				),
				'booking'        => array(
					'label'    => esc_html__( 'Bookings', 'eventprime-event-calendar-management' ),
					'target'   => 'ep_event_booking_data',
					'class'    => array( 'ep_event_bookings' ),
					'priority' => 20,
				),
				'ticket'         => array(
					'label'    => esc_html__( 'Tickets', 'eventprime-event-calendar-management' ),
					'target'   => 'ep_event_ticket_data',
					'class'    => array( 'ep_event_tickets' ),
					'priority' => 30,
				),
				'recurrence'     => array(
					'label'    => esc_html__( 'Repeat', 'eventprime-event-calendar-management' ),
					'target'   => 'ep_event_recurrence_data',
					'class'    => array( 'ep_event_recurrence' ),
					'priority' => 50,
				),
				'checkoutfields' => array(
					'label'    => esc_html__( 'Checkout Fields', 'eventprime-event-calendar-management' ),
					'target'   => 'ep_event_checkout_fields_data',
					'class'    => array( 'ep_event_checkout_fields' ),
					'priority' => 70,
				),
				'social'         => array(
					'label'    => esc_html__( 'Social Information', 'eventprime-event-calendar-management' ),
					'target'   => 'ep_event_social_data',
					'class'    => array( 'ep_event_social_info' ),
					'priority' => 80,
				),
				'results'        => array(
					'label'    => esc_html__( 'Results', 'eventprime-event-calendar-management' ),
					'target'   => 'ep_event_results_data',
					'class'    => array( 'ep_event_results' ),
					'priority' => 90,
				),
				'othersettings'  => array(
					'label'    => esc_html__( 'Other Settings', 'eventprime-event-calendar-management' ),
					'target'   => 'ep_event_other_settings_data',
					'class'    => array( 'ep_event_other_settings' ),
					'priority' => 200,
				),
                                'restrictions'  => array(
                                                        'label'    => esc_html__( 'Restrictions', 'eventprime-event-calendar-management' ),
                                                        'target'   => 'ep_event_restrictions_data',
                                                        'class'    => array( 'ep_event_restrictions' ),
                                                        'priority' => 100,
                                                ),
                'event_theme'  => array(
                                                        'label'    => esc_html__( 'Event Layout Template', 'eventprime-event-calendar-management' ),
                                                        'target'   => 'ep_event_theme_data',
                                                        'class'    => array( 'ep_event_theme' ),
                                                        'priority' => 100,
                                                ),
			)
        );
        // Sort tabs based on priority.
        uasort( $tabs, array( $ep_functions, 'event_data_tabs_sort' ) );
        return apply_filters( 'eventprime_event_meta_tabs', $tabs );
    }

    public function ep_event_tab_content() {
        global $post, $thepostid;
        $ep_functions      = new Eventprime_Basic_Functions();
        $html_generator    = new Eventprime_html_Generator();
        $single_event_data = $ep_functions->get_single_event( $post->ID, $post );
        include 'partials/metaboxes/meta-box-date-panel-html.php';
        include 'partials/metaboxes/meta-box-recurrence-panel-html.php';
        include 'partials/metaboxes/meta-box-schedule-panel-html.php';
        include 'partials/metaboxes/meta-box-checkout-fields-panel-html.php';
        include 'partials/metaboxes/meta-box-countdown-panel-html.php';
        include 'partials/metaboxes/meta-box-tickets-panel-html.php';
        include 'partials/metaboxes/meta-box-other-settings-panel-html.php';
        include 'partials/metaboxes/meta-box-social-panel-html.php';
        include 'partials/metaboxes/meta-box-results-panel-html.php';
        include 'partials/metaboxes/meta-box-bookings-panel-html.php';
        include 'partials/metaboxes/meta-box-restrictions-panel-html.php';
        include 'partials/metaboxes/meta-box-event-theme-panel-html.php';
        do_action( 'ep_event_tab_content' );
    }

    /**
     * Load wp editor
     */
    /*
     * Add Event Stats
     */

    public function ep_add_event_stats_box() {
        global $post;
        $html_generator = new Eventprime_html_Generator();
        $html_generator->ep_add_event_statisticts_data( $post );
        do_action( 'ep_event_stats_list', $post );
    }

    public function ep_add_event_performer_box() {
        global $post;
        $dbhandler     = new EP_DBhandler();
        $performer_ids = array();
        if ( !empty( $post ) && isset( $post->ID ) && !empty( $post->ID ) ) {
            $performer_ids = get_post_meta( $post->ID, 'em_performer', true );
        }

        $performers = $dbhandler->eventprime_get_all_posts( 'em_performer', 'posts' );
        if ( !empty( $performers ) ) {
            ?>
            <div id="taxonomy-post_tag" class="categorydiv">
                <ul>
                <?php
				foreach ( $performers as $performer ) {
					$checked = '';
					if ( !empty( $performer_ids ) && in_array( $performer->ID, $performer_ids ) ) {
						$checked = 'checked="checked"';
					}
					?>
                        <li id="<?php echo esc_attr( $performer->ID ); ?>">
                            <label>
                                <input type="checkbox" name="em_performers[]" id="<?php echo esc_attr( $performer->ID ); ?>" value="<?php echo esc_attr( $performer->ID ); ?>" <?php echo esc_attr( $checked ); ?> /> <?php echo esc_html( $performer->post_title ); ?>
                            </label>
                        </li>
                        <?php
				}
				?>
                </ul>
            </div>
            <?php
        }
    }

    /**
     * Add event gallery meta box
     */
    public function ep_add_event_gallery_box() {
        global $post;
        $em_gallery_image_ids = get_post_meta( $post->ID, 'em_gallery_image_ids', true );
        if ( !empty( $em_gallery_image_ids ) ) {
            $em_gallery_image_ids = explode( ',', $em_gallery_image_ids );
        } else {
            $em_gallery_image_ids = array();
        }
		?>
        <div id="ep_event_gallery_container">
            <ul class="ep_gallery_images ep-d-flex ep-align-items-center ep-content-left">
            <?php
			$attachments         = array_filter( $em_gallery_image_ids );
			$update_meta         = false;
			$updated_gallery_ids = array();
			if ( !empty( $attachments ) ) {
				foreach ( $attachments as $attachment_id ) {
					$attachment = wp_get_attachment_image( $attachment_id, 'thumbnail' );
					// if attachment is empty skip.
					if ( empty( $attachment ) ) {
						$update_meta = true;
						continue;
					}
					?>
                        <li class="ep-gal-img" data-attachment_id="<?php echo esc_attr( $attachment_id ); ?>">
                        <?php echo wp_kses_post( $attachment ); ?>
                            <div class="ep-gal-img-delete"><span class="em-event-gallery-remove dashicons dashicons-trash"></span></div>
                        </li>
                        <?php
                        // rebuild ids to be saved.
                        $updated_gallery_ids[] = $attachment_id;
				}
                    // need to update product meta to set new gallery ids
				if ( $update_meta ) {
					update_post_meta( $post->ID, 'em_gallery_image_ids', implode( ',', $updated_gallery_ids ) );
				}
			}
			?>
            </ul>
            <input type="hidden" id="em_gallery_image_ids" name="em_gallery_image_ids" value="<?php echo esc_attr( implode( ',', $updated_gallery_ids ) ); ?>" />
        </div>
        <p class="ep_add_event_gallery hide-if-no-js">
            <a href="#" 
               data-choose="<?php esc_attr_e( 'Add images to event gallery', 'eventprime-event-calendar-management' ); ?>" 
               data-update="<?php esc_attr_e( 'Add to gallery', 'eventprime-event-calendar-management' ); ?>" 
               data-delete="<?php esc_attr_e( 'Delete image', 'eventprime-event-calendar-management' ); ?>" 
               data-text="<?php esc_attr_e( 'Delete', 'eventprime-event-calendar-management' ); ?>"
               >
        <?php esc_html_e( 'Add event gallery images', 'eventprime-event-calendar-management' ); ?>
            </a>
        </p>
        <?php
    }

    public function ep_save_event_meta_boxes( $post_id, $wp_post ) {
        // $post_id and $post are required
        //print_r($wp_post->post_type);die;
        if ( $wp_post->post_type!=='em_event' ) {
            return;
        }
        if ( isset( $this->processing ) && $this->processing == true ) {
            return;
        }
        $this->processing = true;
        if ( empty( $post_id ) || empty( $wp_post ) ) {
            return;
        }
        // Dont' save meta boxes for revisions or autosaves.
        if ( defined( 'DOING_AUTOSAVE' ) and DOING_AUTOSAVE ) {
            return false;
        }
        // Check user has permission to edit.
        if ( !current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        
        // Check if the nonce exists
        if ( empty( $_POST['ep_event_meta_nonce'] ) ) {
            return; // Exit if the nonce is not set
        }

        // Sanitize the nonce
        $nonce = sanitize_text_field( wp_unslash( $_POST['ep_event_meta_nonce'] ) );

        // Verify the nonce
        if ( ! wp_verify_nonce( $nonce, 'ep_save_event_data' ) ) {
            return; // Exit if nonce verification fails
        }

        // If nonce is valid, continue processing the form data
        $sanitizer = new EventPrime_sanitizer();
        $dbhandler = new EP_DBhandler();
        $post      = $sanitizer->sanitize( $_POST );

        // Proceed with updating post meta
        $dbhandler->eventprime_update_event_post_meta( $post_id, $post, $wp_post );
    }

    /**
     * Add columns to event list table
     */
    public function ep_filter_event_columns( $columns ) {
        $ep_functions = new Eventprime_Basic_Functions();
        unset( $columns['comments'] );
        unset( $columns['date'] );
        unset( $columns['tags'] );
        $columns['title']        = esc_html__( 'Title', 'eventprime-event-calendar-management' );
        $singular_type_text      = $ep_functions->ep_global_settings_button_title( 'Event-Type' );
        $columns['event_type']   = $singular_type_text;
        $singular_venue_text     = $ep_functions->ep_global_settings_button_title( 'Venue' );
        $columns['venue']        = $singular_venue_text;
        $singular_organizer_text = $ep_functions->ep_global_settings_button_title( 'Organizer' );
        $columns['organizer']    = $singular_organizer_text;
        $singular_performer_text = $ep_functions->ep_global_settings_button_title( 'Performer' );
        $columns['performer']    = $singular_performer_text;
        $columns['start_date']   = esc_html__( 'Start Date', 'eventprime-event-calendar-management' );
        $columns['end_date']     = esc_html__( 'End Date', 'eventprime-event-calendar-management' );
        $columns['repeat']       = esc_html__( 'Repeat', 'eventprime-event-calendar-management' );
        return $columns;
    }

    /**
     * Add column content
     */
    public function ep_filter_event_columns_content( $column_name, $post_id ) {
        $ep_functions = new Eventprime_Basic_Functions();
        if ( $column_name == 'venue' ) {
            $id       = get_post_meta( $post_id, 'em_venue', true );
            $venue_id = $ep_functions->ep_get_filter_taxonomy_id($id);
            $venue    = get_term( $venue_id );
            echo ( isset( $venue->name ) && 'uncategorized' !== $venue->slug ? esc_html( $venue->name ) : '----' );
        } elseif ( $column_name == 'event_type' ) {
            $event_type_term_id = get_post_meta( $post_id, 'em_event_type', true );  
            $filter_term_id  = $ep_functions->ep_get_filter_taxonomy_id($event_type_term_id);
            $event_type = get_term($filter_term_id);
            echo ( isset( $event_type->name ) && 'uncategorized' !== $event_type->slug ? esc_html( $event_type->name ) : '----' );
        } elseif ( $column_name == 'organizer' ) {
            $organizers     = get_post_meta( $post_id, 'em_organizer', true );
            $organizer_name = array();
            if ( !empty( $organizers ) ) {
                foreach ( $organizers as $organizer ) {
                    $org = get_term( $organizer );
                    if ( !empty( $org ) && !empty( $org->name ) ) {
                        $organizer_name[] = $org->name;
                    }
                }
            }
            echo ( !empty( $organizer_name ) ? esc_html( implode( ', ', $organizer_name ) ) : '----' );
        } elseif ( $column_name == 'performer' ) {
            $performers     = get_post_meta( $post_id, 'em_performer', true );
            $performer_name = array();
            if ( !empty( $performers ) ) {
                foreach ( $performers as $performer ) {
                    $per = get_the_title( $performer );
                    if ( !empty( $per ) ) {
                        $performer_name[] = $per;
                    }
                }
            }
            echo ( !empty( $performer_name ) ? esc_html( implode( ', ', $performer_name ) ) : '----' );
        } elseif ( $column_name == 'start_date' ) {
            $start_date = get_post_meta( $post_id, 'em_start_date', true );
            if ( !empty( $start_date ) ) {
                $start_date = $ep_functions->ep_timestamp_to_date( $start_date );
            }
            $em_start_time = get_post_meta( $post_id, 'em_start_time', true );
            if ( !empty( $em_start_time ) ) {
                $start_date .= ' ' . $em_start_time;
            }
            echo ( !empty( $start_date ) ? esc_html( $start_date ) : '----' );
        } elseif ( $column_name == 'end_date' ) {
            $end_date = get_post_meta( $post_id, 'em_end_date', true );
            if ( !empty( $end_date ) ) {
                $end_date = $ep_functions->ep_timestamp_to_date( $end_date );
            }
            $em_end_time = get_post_meta( $post_id, 'em_end_time', true );
            if ( !empty( $em_end_time ) ) {
                $end_date .= ' ' . $em_end_time;
            }
            echo ( !empty( $end_date ) ? esc_html( $end_date ) : '----' );
        } elseif ( $column_name == 'repeat' ) {
            $post_parent = wp_get_post_parent_id( $post_id );
            if ( empty( $post_parent ) ) {
                $em_recurrence_interval = get_post_meta( $post_id, 'em_recurrence_interval', true );
                if ( !empty( $em_recurrence_interval ) && strpos( $em_recurrence_interval, '_' ) === true ) {
                    $em_recurrence_interval = implode( ' ', explode( '_', $em_recurrence_interval ) );
                }
                echo ( !empty( $em_recurrence_interval ) ? esc_html( ucwords( $em_recurrence_interval ) ) : '----' );
            } else {
                $parent_post_url = admin_url( 'post.php?post=' . $post_parent . '&action=edit' );
                echo '<a href="' . esc_url( $parent_post_url ) . '" title="' . esc_attr__( 'Show Parent Event' ) . '" target1="_blank"><span class="dashicons dashicons-networking"></span></a>';
            }
        }
    }

    public function ep_sortable_event_columns( $columns ) {
        $columns['start_date'] = array( 'start_date', 'asc' );
        return $columns;
    }

    public function ep_sort_events_date( $query ) {
        if ( !is_admin() ) {
            return;
        }
        $orderby = $query->get( 'orderby' );
        switch ( $orderby ) {
            case 'start_date':
                $query->set( 'meta_key', 'em_start_date' );
                $query->set( 'orderby', 'meta_value_num' );
                break;
            default:
                break;
        }
    }

    public function set_default_payment_processor() {
        if ( isset( $_POST['security'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'ep-default-payment-processor' ) ) {
            $global_settings      = new Eventprime_Global_Settings();
            $global_settings_data = $global_settings->ep_get_settings();
            $form_data            = $_POST;
            if ( isset( $form_data ) && isset( $form_data['ep_default_payment_processor'] ) && !empty( $form_data['ep_default_payment_processor'] ) ) {
                $global_settings_data->default_payment_processor = $form_data['ep_default_payment_processor'];
                $global_settings->ep_save_settings( $global_settings_data );
            }
            wp_send_json_success();
        } else {
            wp_send_json_error( array( 'message' => esc_html__( 'Security check failed. Please refresh the page and try again later.', 'eventprime-event-calendar-management' ) ) );
        }
    }

    /**
     * save checkout field
     */
    public function save_checkout_field() {

         $response = array();
		if ( isset( $_POST['security'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'save-checkout-fields' ) ) {
            $sanitizer = new EventPrime_sanitizer();
            if ( isset( $_POST['data'] ) ) {
                $post_data = $sanitizer->sanitize( wp_unslash( $_POST['data'] ) );
                parse_str( $post_data, $data );
            }
            if ( !isset( $data['em_checkout_field_label'] ) || empty( $data['em_checkout_field_label'] ) ) {
                $response['message'] = esc_html__( 'Label should not be empty', 'eventprime-event-calendar-management' );
                wp_send_json_error( $response );
            }
            if ( !isset( $data['em_checkout_field_type'] ) || empty( $data['em_checkout_field_type'] ) ) {
                $response['message'] = esc_html__( 'Type should not be empty', 'eventprime-event-calendar-management' );
                wp_send_json_error( $response );
            }
            try {
                $dbhandler          = new EP_DBhandler();
                $save_data          = array();
                $save_data['label'] = sanitize_text_field( $data['em_checkout_field_label'] );
                $save_data['type']  = sanitize_text_field( $data['em_checkout_field_type'] );
                // for option data
                $save_data['option_data'] = '';
                $option_data              = ( !empty( $data['ep_checkout_field_option_value'] ) ? $data['ep_checkout_field_option_value'] : '' );
                // set selected value
                if ( !empty( $data['ep_checkout_field_option_value_selected'] ) ) {
                    $option_index                             = $data['ep_checkout_field_option_value_selected'];
                    $option_data[ $option_index ]['selected'] = 1;
                }
                if ( !empty( $option_data ) ) {
                    $save_data['option_data'] = maybe_serialize( $option_data );
                }
                if ( empty( $data['em_checkout_field_id'] ) ) {
                    $save_data['priority']   = 1;
                    $save_data['status']     = 1;
                    $save_data['created_by'] = get_current_user_id();
                    $save_data['created_at'] = wp_date( 'Y-m-d H:i:s', time() );

                    $field_id            = $dbhandler->insert_row( 'CHECKOUT_FIELDS', $save_data );
                    $response['message'] = esc_html__( 'Field Saved Successfully.', 'eventprime-event-calendar-management' );
                } else {
                    $field_id                     = absint( $data['em_checkout_field_id'] );
                    $save_data['updated_at']      = wp_date( 'Y-m-d H:i:s', time() );
                    $save_data['last_updated_by'] = get_current_user_id();
                    $result                       = $dbhandler->update_row( 'CHECKOUT_FIELDS', 'id', $field_id, $save_data );
                    $response['message']          = esc_html__( 'Field Updated Successfully.', 'eventprime-event-calendar-management' );
                }
                $save_data['field_id']  = $field_id;
                $response['field_data'] = $save_data;
            } catch ( Exception $e ) {
                wp_send_json_error( array( 'error' => $e->getMessage() ) );
            }
            wp_send_json_success( $response );
		} else {
			wp_send_json_error( array( 'message' => esc_html__( 'Security check failed. Please refresh the page and try again later.', 'eventprime-event-calendar-management' ) ) );
		}
    }

    // delete the checkout field
    public function delete_checkout_field() {
        if ( isset( $_POST['security'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'delete-checkout-fields' ) ) {
            $response = array();
            if ( isset( $_POST['field_id'] ) && !empty( $_POST['field_id'] ) ) {
                $id        = sanitize_text_field( wp_unslash( $_POST['field_id'] ) );
                $dbhandler = new EP_DBhandler();
                $dbhandler->remove_row( 'CHECKOUT_FIELDS', 'id', $id );
                $response['message'] = esc_html__( 'Field Deleted Successfully.', 'eventprime-event-calendar-management' );
            } else {
                $response['message'] = esc_html__( 'Some Data Missing.', 'eventprime-event-calendar-management' );
            }
            wp_send_json_success( $response );
        } else {
            wp_send_json_error( array( 'message' => esc_html__( 'Security check failed. Please refresh the page and try again later.', 'eventprime-event-calendar-management' ) ) );
        }
    }

    public function eventprime_activate_license() {
        if ( isset( $_POST['nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ep-license-nonce' ) ) {
            $sanitizer = new EventPrime_sanitizer();
            $form_data = $sanitizer->sanitize( $_POST );
            $response  = array();
            if ( isset( $form_data ) && isset( $form_data['ep_license_activate'] ) && !empty( $form_data['ep_license_activate'] ) ) {
                $license_controller = new EventPrime_License();
                $response           = $license_controller->ep_activate_license_settings( $form_data );
            }

            wp_send_json_success( $response );
        } else {
            wp_send_json_error( array( 'message' => esc_html__( 'Security check failed. Please refresh the page and try again later.', 'eventprime-event-calendar-management' ) ) );
        }
    }

    public function eventprime_deactivate_license() {
        if ( isset( $_POST['security'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'ep-license-nonce' ) ) {
            $sanitizer = new EventPrime_sanitizer();
            $form_data = $sanitizer->sanitize( $_POST );
            $response  = array();
            if ( isset( $form_data ) && isset( $form_data['ep_license_deactivate'] ) && !empty( $form_data['ep_license_deactivate'] ) ) {
                $license_controller = new EventPrime_License();
                $response           = $license_controller->ep_deactivate_license_settings( $form_data );
            }
            wp_send_json_success( $response );
        } else {
            wp_send_json_error( array( 'message' => esc_html__( 'Security check failed. Please refresh the page and try again later.', 'eventprime-event-calendar-management' ) ) );
        }
    }

    /*
     * Event Types start
     */

    public function add_event_type_fields() {
        include 'partials/event-types/ep-add-event-types.php';
    }

    public function edit_event_type_fields( $term ) {
        include 'partials/event-types/ep-edit-event-types.php';
    }

    /**
     * Add sorting on the ID column
     *
     * @param mixed $columns Columns array.
     * @return array
     */
    public function add_event_type_custom_columns( $columns ) {
        $new_columns = array();

        if ( isset( $columns['cb'] ) ) {
            $new_columns['cb'] = $columns['cb'];
            unset( $columns['cb'] );
        }

        $new_columns['id'] = esc_html__( 'ID', 'eventprime-event-calendar-management' );

        $new_columns['image_id'] = esc_html__( 'Image', 'eventprime-event-calendar-management' );

        $columns = array_merge( $new_columns, $columns );

        $columns['color'] = esc_html__( 'Background', 'eventprime-event-calendar-management' );

        $columns['type_text_color'] = esc_html__( 'Text', 'eventprime-event-calendar-management' );

        // rename Count to Events
        $columns['posts'] = esc_html__( 'Events', 'eventprime-event-calendar-management' );

        return $columns;
    }

    public function add_event_type_custom_column( $columns, $column, $id ) {
        if ( 'id' === $column ) {
            $columns .= '<span class="id-block">' . esc_html( $id ) . '</span>';
        }

        if ( 'image_id' === $column ) {
            $image_id = get_term_meta( $id, 'em_image_id', true );
            if ( $image_id ) {
                $image    = wp_get_attachment_thumb_url( $image_id );
                $image    = str_replace( ' ', '%20', $image );
                $columns .= '<img src="' . esc_url( $image ) . '" alt="' . esc_attr__( 'Image', 'eventprime-event-calendar-management' ) . '" class="wp-post-image" height="48" width="48" />';
            }
        }

        if ( 'color' === $column ) {
            $color = get_term_meta( $id, 'em_color', true );
            if ( $color ) {
                $columns .= '<span class="color-block" style="background-color: ' . $color . '"></span>';
            }
        }

        if ( 'type_text_color' === $column ) {
            $type_text_color = get_term_meta( $id, 'em_type_text_color', true );
            if ( $type_text_color ) {
                $columns .= '<span class="color-block" style="background-color: ' . $type_text_color . '"></span>';
            }
        }

        if ( 'handle' === $column ) {
            $columns .= '<input type="hidden" name="term_id" value="' . esc_attr( $id ) . '" />';
        }

        return $columns;
    }


    // Save the Event Type
    public function em_create_event_type_data( $term_id ) {
         if ( ! isset( $_POST['em_event_type_nonce_field'] ) || 
            ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['em_event_type_nonce_field'] ) ), 'em_event_type_nonce_action' ) ) {
           // If nonce verification fails, return without processing the data
           return;
       }
    
        if ( isset( $_POST['tax_ID'] ) && !empty( $_POST['tax_ID'] ) ) {
            return;
        }
        $color           = isset( $_POST['em_color'] ) ? sanitize_text_field( wp_unslash( $_POST['em_color'] ) ) : '';
        $type_text_color = isset( $_POST['em_type_text_color'] ) ? sanitize_text_field( wp_unslash( $_POST['em_type_text_color'] ) ) : '';
        $image_id        = isset( $_POST['em_image_id'] ) ? sanitize_text_field( wp_unslash( $_POST['em_image_id'] ) ) : '';
        $is_featured     = isset( $_POST['em_is_featured'] ) ? 1 : '0';
        $em_age_group    = isset( $_POST['em_age_group'] ) ? sanitize_text_field( wp_unslash( $_POST['em_age_group'] ) ) : 'all';
        $custom_group    = '';
        if ( $em_age_group == 'custom_group' ) {
            $custom_group = isset( $_POST['em_custom_group'] ) ? sanitize_text_field( wp_unslash( $_POST['em_custom_group'] ) ) : '';
        }
        update_term_meta( $term_id, 'em_color', $color );
        update_term_meta( $term_id, 'em_type_text_color', $type_text_color );
        update_term_meta( $term_id, 'em_image_id', $image_id );
        update_term_meta( $term_id, 'em_is_featured', $is_featured );
        update_term_meta( $term_id, 'em_age_group', $em_age_group );
        if ( !empty( $custom_group ) ) {
            update_term_meta( $term_id, 'em_custom_group', $custom_group );
        }
        if ( !metadata_exists( 'term', $term_id, 'em_status' ) ) {
            update_term_meta( $term_id, 'em_status', 1 );
        }
    }

    /*
     * Event Venue start
     */

    // Save the Venue
    public function em_create_event_venue_data( $term_id ) {
        if ( ! isset( $_POST['em_event_venue_nonce_field'] ) || 
            ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['em_event_venue_nonce_field'] ) ), 'em_event_venue_nonce_action' ) ) {
           // If nonce verification fails, return without processing the data
           return;
       }
        if ( isset( $_POST['tax_ID'] ) && !empty( $_POST['tax_ID'] ) ) {
            return;
        }
        $em_address                     = isset( $_POST['em_address'] ) ? sanitize_text_field( wp_unslash( $_POST['em_address'] ) ) : '';
        $em_lat                         = isset( $_POST['em_lat'] ) ? sanitize_text_field( wp_unslash( $_POST['em_lat'] ) ) : '';
        $em_lng                         = isset( $_POST['em_lng'] ) ? sanitize_text_field( wp_unslash( $_POST['em_lng'] ) ) : '';
        $em_locality                    = isset( $_POST['em_locality'] ) ? sanitize_text_field( wp_unslash( $_POST['em_locality'] ) ) : '';
        $em_state                       = isset( $_POST['em_state'] ) ? sanitize_text_field( wp_unslash( $_POST['em_state'] ) ) : '';
        $em_country                     = isset( $_POST['em_country'] ) ? sanitize_text_field( wp_unslash( $_POST['em_country'] ) ) : '';
        $em_postal_code                 = isset( $_POST['em_postal_code'] ) ? sanitize_text_field( wp_unslash( $_POST['em_postal_code'] ) ) : '';
        $em_zoom_level                  = isset( $_POST['em_zoom_level'] ) ? sanitize_text_field( wp_unslash( $_POST['em_zoom_level'] ) ) : '';
        $em_place_id                    = isset( $_POST['em_place_id'] ) ? sanitize_text_field( wp_unslash( $_POST['em_place_id'] ) ) : '';
        $em_established                 = isset( $_POST['em_established'] ) ? sanitize_text_field( wp_unslash( $_POST['em_established'] ) ) : '';
        $em_type                        = isset( $_POST['em_type'] ) ? sanitize_text_field( wp_unslash( $_POST['em_type'] ) ) : '';
        $em_seating_organizer           = isset( $_POST['em_seating_organizer'] ) ? sanitize_text_field( wp_unslash( $_POST['em_seating_organizer'] ) ) : '';
        $em_facebook_page               = isset( $_POST['em_facebook_page'] ) ? sanitize_text_field( wp_unslash( $_POST['em_facebook_page'] ) ) : '';
        $em_instagram_page              = isset( $_POST['em_instagram_page'] ) ? sanitize_text_field( wp_unslash( $_POST['em_instagram_page'] ) ) : '';
        $em_gallery_images              = isset( $_POST['em_gallery_images'] ) ? explode( ',', sanitize_text_field( wp_unslash( $_POST['em_gallery_images'] ) ) ) : '';
        $em_is_featured                 = isset( $_POST['em_is_featured'] ) ? 1 : '0';
        $em_display_address_on_frontend = isset( $_POST['em_display_address_on_frontend'] ) ? 1 : 0;

        update_term_meta( $term_id, 'em_address', $em_address );
        update_term_meta( $term_id, 'em_lat', $em_lat );
        update_term_meta( $term_id, 'em_lng', $em_lng );
        update_term_meta( $term_id, 'em_locality', $em_locality );
        update_term_meta( $term_id, 'em_state', $em_state );
        update_term_meta( $term_id, 'em_country', $em_country );
        update_term_meta( $term_id, 'em_postal_code', $em_postal_code );
        update_term_meta( $term_id, 'em_zoom_level', $em_zoom_level );
        update_term_meta( $term_id, 'em_place_id', $em_place_id );
        update_term_meta( $term_id, 'em_established', $em_established );
        update_term_meta( $term_id, 'em_type', $em_type );
        update_term_meta( $term_id, 'em_seating_organizer', $em_seating_organizer );
        update_term_meta( $term_id, 'em_facebook_page', $em_facebook_page );
        update_term_meta( $term_id, 'em_instagram_page', $em_instagram_page );
        update_term_meta( $term_id, 'em_gallery_images', $em_gallery_images );
        update_term_meta( $term_id, 'em_is_featured', $em_is_featured );
        update_term_meta( $term_id, 'em_display_address_on_frontend', $em_display_address_on_frontend );
        if ( !metadata_exists( 'term', $term_id, 'em_status' ) ) {
            update_term_meta( $term_id, 'em_status', 1 );
        }
        do_action( "ep_after_save_venue_data", $term_id, $_POST ); 
    }

    public function add_event_venue_fields() {
        include 'partials/event-venues/ep-add-event-venues.php';
    }

    public function edit_event_venue_fields( $term ) {
        include 'partials/event-venues/ep-edit-event-venues.php';
    }

    public function add_venue_custom_columns( $columns ) {
        $new_columns = array();

        if ( isset( $columns['cb'] ) ) {
            $new_columns['cb'] = $columns['cb'];
            unset( $columns['cb'] );
        }

        $new_columns['id'] = esc_html__( 'ID', 'eventprime-event-calendar-management' );

        $new_columns['image_id'] = esc_html__( 'Image', 'eventprime-event-calendar-management' );

        $columns = array_merge( $new_columns, $columns );

        // rename Count to Events
        $columns['posts'] = esc_html__( 'Events', 'eventprime-event-calendar-management' );

        return $columns;
    }

    public function add_venue_custom_column( $columns, $column, $id ) {
        if ( 'id' === $column ) {
            $columns .= '<span class="id-block">' . esc_html( $id ) . '</span>';
        }

        if ( 'image_id' === $column ) {
            $image_id = get_term_meta( $id, 'em_gallery_images', true );
            $image_id = ( is_array( $image_id ) && count( $image_id ) > 0 ) ? $image_id[0] : $image_id;
            if ( $image_id ) {
                $image    = wp_get_attachment_thumb_url( $image_id );
                $image    = str_replace( ' ', '%20', $image );
                $columns .= '<img src="' . esc_url( $image ) . '" alt="' . esc_attr__( 'Image', 'eventprime-event-calendar-management' ) . '" class="wp-post-image" height="48" width="48" />';
            }
        }

        return $columns;
    }

   
    

    public function add_event_organizer_fields() {
        include 'partials/organizer/ep-add-organizer.php';
    }

    public function edit_event_organizer_fields( $term ) {
        include 'partials/organizer/ep-edit-organizer.php';
    }

    public function show_validation_error() {
        echo '<div class="error"><p>Term creation or update failed due to validation errors. Please check your input.</p></div>';
    }

    public function validate_term_data($post) {
        $ep_functions        = new Eventprime_Basic_Functions();
        $error               = false;
        $em_organizer_phones = $em_organizer_emails = $em_organizer_websites = array();
        // check for valid phone number
        if ( isset( $post['em_organizer_phones'] ) && count( $post['em_organizer_phones'] ) > 0 ) {
            foreach ( $post['em_organizer_phones'] as $phone ) {
                if ( !empty( $phone ) ) {
                    $phone_no = $ep_functions->is_valid_phone( sanitize_text_field( $phone ) );
                    if ( $phone_no ) {
                        $em_organizer_phones[] = $phone;
                    } else {
                        $error = true;
                    }
                }
            }
        }
        // check for valid email
        if ( isset( $post['em_organizer_emails'] ) && count( $post['em_organizer_emails'] ) > 0 ) {
            foreach ( $post['em_organizer_emails'] as $email ) {
                if ( !empty( $email ) ) {
                    $email = sanitize_email( $email );
                    if ( !empty( $email ) ) {
                        if ( filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
                            $em_organizer_emails[] = $email;
                        } else {
                            $error = true;
                        }
                    }
                }
            }
        }
        // check for valid website URL
        if ( isset( $post['em_organizer_websites'] ) && count( $post['em_organizer_websites'] ) > 0 ) {
            foreach ( $post['em_organizer_websites'] as $website ) {
                if ( !empty( $website ) ) {
                    $site_url_valid = $ep_functions->is_valid_site_url( sanitize_text_field( $website ) );
                    if ( $site_url_valid ) {
                        $em_organizer_websites[] = $website;
                    } else {
                        $error = true;
                    }
                }
            }
        }

        return $error;
    }

    public function em_create_event_organizer_data( $term_id ) {
        
        if ( ! isset( $_POST['em_event_organizer_nonce_field'] ) || 
            ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['em_event_organizer_nonce_field'] ) ), 'em_event_organizer_nonce_action' ) ) {
           // If nonce verification fails, return without processing the data
           return;
       }
        
        $taxonomy     = 'em_event_organizer';
        $ep_functions = new Eventprime_Basic_Functions();
        $sanitizer = new EventPrime_sanitizer;
        $post = $sanitizer->sanitize($_POST);
        $is_error     = $this->validate_term_data($post);

        if ( $is_error ) {
            // Validation failed, prevent term creation or updating
            //add_action('admin_notices', array($this, 'show_validation_error'));

            wp_delete_term( $term_id, $taxonomy );

            clean_term_cache( $term_id, $taxonomy );
            return new WP_Error( 'invalid_data', esc_html__( 'Invalid Data.' ) );

            //return;            // Redirect back to the taxonomy page
            //wp_redirect(admin_url("edit-tags.php?taxonomy=$taxonomy"));
            //exit();
        } else {
            $em_organizer_phones = $em_organizer_emails = $em_organizer_websites = array();
            // Sanitize and validate phone numbers
            if ( isset( $_POST['em_organizer_phones'] ) && !empty( $_POST['em_organizer_phones'] ) ) {
                $em_organizer_phones_raw = array_map( 'sanitize_text_field', wp_unslash( $_POST['em_organizer_phones'] ) );

                foreach ( $em_organizer_phones_raw as $phone ) {
                    if ( !empty( $phone ) && $ep_functions->is_valid_phone( $phone ) ) {
                        $em_organizer_phones[] = $phone;
                    }
                }
            }

            // Sanitize and validate emails
            if ( isset( $_POST['em_organizer_emails'] ) && !empty( $_POST['em_organizer_emails'] ) ) {
                $em_organizer_emails_raw = array_map( 'sanitize_email', wp_unslash( $_POST['em_organizer_emails'] ) );

                foreach ( $em_organizer_emails_raw as $email ) {
                    if ( !empty( $email ) && filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
                        $em_organizer_emails[] = $email;
                    }
                }
            }

            // Sanitize and validate website URLs
            if ( isset( $_POST['em_organizer_websites'] ) && !empty( $_POST['em_organizer_websites'] ) ) {
                $em_organizer_websites_raw = array_map( 'sanitize_text_field', wp_unslash( $_POST['em_organizer_websites'] ) );

                foreach ( $em_organizer_websites_raw as $website ) {
                    if ( !empty( $website ) && $ep_functions->is_valid_site_url( $website ) ) {
                        $em_organizer_websites[] = $website;
                    }
                }
            }
            
            $em_organizer_phones   = ( !empty( $em_organizer_phones ) ? $em_organizer_phones : '' );
            $em_organizer_emails   = ( !empty( $em_organizer_emails ) ? $em_organizer_emails : '' );
            $em_organizer_websites = ( !empty( $em_organizer_websites ) ? $em_organizer_websites : '' );
            $em_image_id           = isset( $_POST['em_image_id'] ) ? sanitize_text_field( wp_unslash( $_POST['em_image_id'] ) ) : '';
            $em_is_featured        = isset( $_POST['em_is_featured'] ) ? 1 : '0';
            //$em_social_links       = isset( $_POST['em_social_links'] ) ? wp_kses_post( wp_unslash( $_POST['em_social_links'] ) ) : '';
            $em_social_links = isset( $_POST['em_social_links'] ) && is_array( $_POST['em_social_links'] ) ? array_map( 'sanitize_url', array_map( 'wp_unslash', $_POST['em_social_links'] ) ) : array();
            update_term_meta( $term_id, 'em_organizer_phones', $em_organizer_phones );
            update_term_meta( $term_id, 'em_organizer_emails', $em_organizer_emails );
            update_term_meta( $term_id, 'em_organizer_websites', $em_organizer_websites );
            update_term_meta( $term_id, 'em_image_id', $em_image_id );
            update_term_meta( $term_id, 'em_is_featured', $em_is_featured );
            update_term_meta( $term_id, 'em_social_links', $em_social_links );
            if ( !metadata_exists( 'term', $term_id, 'em_status' ) ) {
                update_term_meta( $term_id, 'em_status', 1 );
            }
            do_action( "ep_after_save_organizer_data", $term_id, $post );
        }
    }

    public function em_edit_event_organizer_data( $term_id ) {
        if ( ! isset( $_POST['em_event_organizer_nonce_field'] ) || 
            ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['em_event_organizer_nonce_field'] ) ), 'em_event_organizer_nonce_action' ) ) {
           // If nonce verification fails, return without processing the data
           return;
       }
        $ep_functions = new Eventprime_Basic_Functions();
        $sanitizer = new EventPrime_sanitizer;
        $taxonomy     = 'em_event_organizer';
        $post = $sanitizer->sanitize($_POST);
        $is_error     = $this->validate_term_data($post);

        if ( $is_error ) {
            // Validation failed, prevent term creation or updating
            //add_action('admin_notices', array($this, 'show_validation_error'));

            clean_term_cache( $term_id, $taxonomy );
            return new WP_Error( 'invalid_data', esc_html__( 'Invalid Data.' ) );
            //return;            // Redirect back to the taxonomy page
        } else {
            $em_organizer_phones = $em_organizer_emails = $em_organizer_websites = array();
            // check for valid phone number
            if ( isset( $_POST['em_organizer_phones'] ) && !empty( $_POST['em_organizer_phones'] ) ) {
                // Unsanitize the raw data and sanitize each phone number
                $em_organizer_phones_raw = array_map( 'sanitize_text_field', wp_unslash( $_POST['em_organizer_phones'] ) );

                foreach ( $em_organizer_phones_raw as $phone ) {
                    if ( !empty( $phone ) ) {
                        // Validate the phone number using a custom validation function
                        $phone_no = $ep_functions->is_valid_phone( $phone );
                        if ( $phone_no ) {
                            $em_organizer_phones[] = $phone; // Assuming valid phone number is returned by is_valid_phone()
                        }
                    }
                }
            }
            // check for valid email
            if ( isset( $_POST['em_organizer_emails'] ) && !empty( $_POST['em_organizer_emails'] ) ) {
                // Unsanitize the raw data and sanitize each email
                $em_organizer_emails_raw = array_map( 'sanitize_email', wp_unslash( $_POST['em_organizer_emails'] ) );

                foreach ( $em_organizer_emails_raw as $email ) {
                    if ( !empty( $email ) && filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
                        $em_organizer_emails[] = $email;
                    }
                }
            }

            // Check for valid website URL
            if ( isset( $_POST['em_organizer_websites'] ) && !empty( $_POST['em_organizer_websites'] ) ) {
                // Unsanitize the raw data
                $em_organizer_websites_raw = array_map( 'sanitize_url', wp_unslash( $_POST['em_organizer_websites'] ) );

                foreach ( $em_organizer_websites_raw as $website ) {
                    if ( !empty( $website ) ) {
                        // Validate the site URL
                        $site_url_valid = $ep_functions->is_valid_site_url( $website );
                        if ( $site_url_valid ) {
                            $em_organizer_websites[] = sanitize_url( $website ); // Sanitize and store the valid URL
                        }
                    }
                }
            }

            $em_organizer_phones   = ( !empty( $em_organizer_phones ) ? $em_organizer_phones : '' );
            $em_organizer_emails   = ( !empty( $em_organizer_emails ) ? $em_organizer_emails : '' );
            $em_organizer_websites = ( !empty( $em_organizer_websites ) ? $em_organizer_websites : '' );
            $em_image_id           = isset( $_POST['em_image_id'] ) ? sanitize_text_field( wp_unslash( $_POST['em_image_id'] ) ) : '';
            $em_is_featured        = isset( $_POST['em_is_featured'] ) ? 1 : '0';
            $em_social_links       = isset( $_POST['em_social_links'] ) ? sanitize_text_field( wp_unslash( $_POST['em_social_links'] ) ) : '';
            $em_social_links = isset( $_POST['em_social_links'] ) && is_array( $_POST['em_social_links'] ) ? array_map( 'sanitize_url', array_map( 'wp_unslash', $_POST['em_social_links'] ) ) : array();
            
            update_term_meta( $term_id, 'em_organizer_phones', $em_organizer_phones );
            update_term_meta( $term_id, 'em_organizer_emails', $em_organizer_emails );
            update_term_meta( $term_id, 'em_organizer_websites', $em_organizer_websites );
            update_term_meta( $term_id, 'em_image_id', $em_image_id );
            update_term_meta( $term_id, 'em_is_featured', $em_is_featured );
            update_term_meta( $term_id, 'em_social_links', $em_social_links );
            if ( !metadata_exists( 'term', $term_id, 'em_status' ) ) {
                update_term_meta( $term_id, 'em_status', 1 );
            }
            do_action( "ep_after_edit_organizer_data", $term_id, $post );
        }
    }

    /**
     * Custom column added to organizer admin
     *
     * @param mixed $columns Columns array.
     * @return array
     */
    public function add_event_organizer_custom_columns( $columns ) {
        $new_columns = array();

        if ( isset( $columns['cb'] ) ) {
            $new_columns['cb'] = $columns['cb'];
            unset( $columns['cb'] );
        }

        $new_columns['id'] = esc_html__( 'ID', 'eventprime-event-calendar-management' );

        $new_columns['image_id'] = esc_html__( 'Image', 'eventprime-event-calendar-management' );

        $columns = array_merge( $new_columns, $columns );

        $columns['phone'] = esc_html__( 'Phone', 'eventprime-event-calendar-management' );

        $columns['email'] = esc_html__( 'Email', 'eventprime-event-calendar-management' );

        // rename Count to Events
        $columns['posts'] = esc_html__( 'Events', 'eventprime-event-calendar-management' );

        return $columns;
    }

    /**
     * Custom column value added to organizer admin
     *
     * @param string $columns Column HTML output.
     * @param string $column Column name.
     * @param int    $id Term ID.
     *
     * @return string
     */
    public function add_event_organizer_custom_column( $columns, $column, $id ) {
        if ( 'id' === $column ) {
            $columns .= '<span class="id-block">' . esc_html( $id ) . '</span>';
        }

        if ( 'image_id' === $column ) {
            $image_id = get_term_meta( $id, 'em_image_id', true );
            if ( $image_id ) {
                $image = wp_get_attachment_thumb_url( $image_id );
                if ( $image ) {
                    $image    = str_replace( ' ', '%20', $image );
                    $columns .= '<img src="' . esc_url( $image ) . '" alt="' . esc_attr__( 'Image', 'eventprime-event-calendar-management' ) . '" class="wp-post-image" height="48" width="48" />';
                }
            }
        }

        if ( 'phone' === $column ) {
            $em_organizer_phones = get_term_meta( $id, 'em_organizer_phones', true );
            if ( $em_organizer_phones ) {
                $columns .= '<span class="phone-block">' . implode( ',', $em_organizer_phones ) . '</span>';
            }
        }

        if ( 'email' === $column ) {
            $em_organizer_emails = get_term_meta( $id, 'em_organizer_emails', true );
            if ( $em_organizer_emails ) {
                $columns .= '<span class="email-block">' . implode( ',', $em_organizer_emails ) . '</span>';
            }
        }

        return $columns;
    }

    public function ep_performer_register_meta_boxes() {
        $ep_functions    = new Eventprime_Basic_Functions();
        $performers_text = $ep_functions->ep_global_settings_button_title( 'Performers' );
        $performer_text  = $ep_functions->ep_global_settings_button_title( 'Performer' );
        add_meta_box(
            'ep_performer_register_meta_boxes',
            sprintf( esc_html__( '%s Settings', 'eventprime-event-calendar-management' ), esc_html( $performers_text ) ),
            array( $this, 'ep_add_performer_setting_box' ),
            'em_performer',
            'normal',
            'high'
        );

        add_meta_box(
            'ep_performer-gallery-images',
            sprintf( esc_html__( '%s gallery', 'eventprime-event-calendar-management' ), esc_html( $performer_text ) ),
            array( $this, 'ep_add_performer_gallery_box' ),
            'em_performer',
            'side',
            'low'
        );
    }

    /**
     * Add performer setting details
     *
     * @param $post
     */
    public function ep_add_performer_setting_box( $post ): void {
        if ( $post->post_type == 'em_performer' ) {
            wp_enqueue_style( 'em-performer-meta-box-css' );
            wp_enqueue_script( 'em-performer-meta-box-js' );

            wp_nonce_field( 'ep_save_performer_data', 'ep_performer_meta_nonce' );
            include 'partials/performer/metaboxes/meta-box-panel-html.php';
        }
    }

    /**
     * Return tabs data
     *
     * @return array
     */
    public function get_ep_performer_meta_tabs() {
        $tabs = apply_filters(
            'ep_performer_meta_tabs',
            array(
				'settings' => array(
					'label'    => esc_html__( 'Settings', 'eventprime-event-calendar-management' ),
					'target'   => 'ep_performer_settings_data',
					'class'    => array( 'ep_performer_settings' ),
					'priority' => 10,
				),
				'personal' => array(
					'label'    => esc_html__( 'Personal Information', 'eventprime-event-calendar-management' ),
					'target'   => 'ep_performer_personal_data',
					'class'    => array( 'ep_performer_personal_info' ),
					'priority' => 20,
				),
				'social'   => array(
					'label'    => esc_html__( 'Social Information', 'eventprime-event-calendar-management' ),
					'target'   => 'ep_performer_social_data',
					'class'    => array( 'ep_performer_social_info' ),
					'priority' => 30,
				),
			)
        );

        // Sort tabs based on priority.
        //uasort( $tabs, array( __CLASS__, 'event_data_tabs_sort' ) );

        return $tabs;
    }

    /**
     * Show the tab contents
     */
    public function ep_performer_tab_content() {
        global $post;
        $ep_functions = new Eventprime_Basic_Functions();
        include 'partials/performer/metaboxes/meta-box-settings-panel-html.php';
        include 'partials/performer/metaboxes/meta-box-personal-panel-html.php';
        include 'partials/performer/metaboxes/meta-box-social-panel-html.php';
    }

    /**
     * Save performers data
     *
     * @param int    $post_id Post ID.
     * @param object $post Post object.
     */
    public function ep_save_meta_boxes( $post_id, $post ) {
        $ep_functions = new Eventprime_Basic_Functions();
        $post_id      = absint( $post_id );

        // $post_id and $post are required
        if ( empty( $post_id ) || empty( $post ) ) {
            return;
        }

        // Dont' save meta boxes for revisions or autosaves.
        if ( defined( 'DOING_AUTOSAVE' ) and DOING_AUTOSAVE ) {
            return false;
        }

        // Check the nonce.
        if ( empty( $_POST['ep_performer_meta_nonce'] ) || !wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ep_performer_meta_nonce'] ) ), 'ep_save_performer_data' ) ) {
            return;
        }

        // Check the post being saved == the $post_id to prevent triggering this call for other save_post events.
        if ( empty( $_POST['post_ID'] ) || absint( wp_unslash( $_POST['post_ID'] ) ) !== $post_id ) {
            return;
        }

        // Check user has permission to edit.
        if ( !current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        //self::$ep_saved_meta_boxes = true;
        $error            = false;
        $em_type          = isset( $_POST['em_type'] ) ? sanitize_text_field( wp_unslash( $_POST['em_type'] ) ) : '';
        $em_role          = isset( $_POST['em_role'] ) ? sanitize_text_field( wp_unslash( $_POST['em_role'] ) ) : '';
        $em_display_front = isset( $_POST['em_display_front'] ) ? 1 : 0;
        $em_is_featured   = isset( $_POST['em_is_featured'] ) && !empty( $_POST['em_is_featured'] ) ? 1 : 0;
        $em_social_links  = $em_performer_phones = $em_performer_emails = $em_performer_websites = array();
        $em_social_links = isset( $_POST['em_social_links'] ) && is_array( $_POST['em_social_links'] ) ? array_map( 'sanitize_url', array_map( 'wp_unslash', $_POST['em_social_links'] ) ) : array();
        if ( isset( $_POST['em_performer_phones'] ) && !empty( $_POST['em_performer_phones'] ) ) {
            // Unsanitize the raw data
            
            $em_performer_phones_raw = array_map( 'sanitize_text_field', wp_unslash( $_POST['em_performer_phones'] ) );
            
            foreach ( $em_performer_phones_raw as $phone ) {
                if ( !empty( $phone ) ) {
                    // Validate the phone number using a custom function
                    $phone_no = $ep_functions->is_valid_phone( $phone );
                    if ( $phone_no ) {
                        $em_performer_phones[] = $phone; // Assuming valid phone number is returned by is_valid_phone()
                    }
                }
            }
            
        }


        if ( isset( $_POST['em_performer_emails'] ) && !empty( $_POST['em_performer_emails'] ) ) {
            // Unsanitize the raw data and then sanitize each email
            $em_performer_emails_raw = array_map( 'sanitize_email', wp_unslash( $_POST['em_performer_emails'] ) );

            foreach ( $em_performer_emails_raw as $email ) {
                if ( !empty( $email ) && filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
                    $em_performer_emails[] = $email;
                }
            }
        }


        if ( isset( $_POST['em_performer_websites'] ) && !empty( $_POST['em_performer_websites'] ) ) {
            // Sanitize the entire input array
            $em_performer_websites_raw = array_map( 'sanitize_text_field', wp_unslash( $_POST['em_performer_websites']) );

            foreach ( $em_performer_websites_raw as $website ) {
                if ( !empty( $website ) ) {
                    $site_url_valid = $ep_functions->is_valid_site_url( $website );
                    if ( $site_url_valid ) {
                        $em_performer_websites[] = sanitize_url( $website );
                    }
                }
            }
        }


        $em_performer_gallery  = isset( $_POST['em_performer_gallery'] ) ? explode( ',', sanitize_text_field( wp_unslash( $_POST['em_performer_gallery'] ) ) ) : array();
        $performer_post_status = ( !empty( $post->post_status ) && $post->post_status !== 'draft' ) ? $post->post_status : 'publish';
        update_post_meta( $post_id, 'em_type', $em_type );
        update_post_meta( $post_id, 'em_role', $em_role );
        update_post_meta( $post_id, 'em_display_front', $em_display_front );
        update_post_meta( $post_id, 'em_is_featured', $em_is_featured );
        update_post_meta( $post_id, 'em_social_links', $em_social_links );
        update_post_meta( $post_id, 'em_performer_phones', $em_performer_phones );
        update_post_meta( $post_id, 'em_performer_emails', $em_performer_emails );
        update_post_meta( $post_id, 'em_performer_websites', $em_performer_websites );
        update_post_meta( $post_id, 'em_performer_gallery', $em_performer_gallery );
        update_post_meta( $post_id, 'em_created_by', $post->post_author );
        if ( !metadata_exists( 'post', $post_id, 'em_status' ) ) {
            update_post_meta( $post_id, 'em_status', 1 );
        }

		//      //publish the performer
		//      $performer_post = array(
		//          'ID'          => $post_id,
		//          'post_type'   => 'em_performer',
		//          'post_status' => $performer_post_status,
		//      );
		//        // Update the post into the database
		//      wp_update_post( $performer_post );

        do_action( 'ep_after_save_performer_data', $post_id, $post );
    }

    /**
     * Remove default meta boxes
     */
    public function ep_performer_remove_meta_boxes() {
        remove_meta_box( 'postexcerpt', 'em_performer', 'normal' );
        remove_meta_box( 'commentsdiv', 'em_performer', 'normal' );
        remove_meta_box( 'commentstatusdiv', 'em_performer', 'side' );
        remove_meta_box( 'commentstatusdiv', 'em_performer', 'normal' );
        remove_meta_box( 'postcustom', 'em_performer', 'normal' );
        remove_meta_box( 'pageparentdiv', 'em_performer', 'side' );
    }

    /**
     * Add performer gallery meta box
     */
    public function ep_add_performer_gallery_box() {
        global $post;
        $ep_functions         = new Eventprime_Basic_Functions();
        $performers_text      = $ep_functions->ep_global_settings_button_title( 'Performers' );
        $performer_text       = $ep_functions->ep_global_settings_button_title( 'Performer' );
        $em_performer_gallery = get_post_meta( $post->ID, 'em_performer_gallery', true );
        if ( empty( $em_performer_gallery ) ) {
            $em_performer_gallery = array();
        }
        if ( !empty( $em_performer_gallery ) && !is_array( $em_performer_gallery ) ) {
            $em_performer_gallery = explode( ',', $em_performer_gallery );
        }
        ?>
        <div id="ep_performer_gallery_container">
            <ul class="ep_gallery_images ep-d-flex ep-align-items-center ep-content-left">
        <?php
        $attachments         = array_filter( $em_performer_gallery );
        $update_meta         = false;
        $updated_gallery_ids = array();

        if ( !empty( $attachments ) ) {
            foreach ( $attachments as $attachment_id ) {
                $attachment = wp_get_attachment_image( $attachment_id, 'thumbnail' );
                // if attachment is empty skip.
                if ( empty( $attachment ) ) {
                    $update_meta = true;
                    continue;
                }
                ?>
                        <li class="ep-gal-img" data-attachment_id="<?php echo esc_attr( $attachment_id ); ?>">
                <?php echo wp_kses_post( $attachment ); ?>
                            <div class="ep-gal-img-delete"><span class="em-performer-gallery-remove dashicons dashicons-trash"></span></div>
                        </li>
                <?php
                // rebuild ids to be saved.
                $updated_gallery_ids[] = $attachment_id;
            }

            // need to update product meta to set new gallery ids
            if ( $update_meta ) {
                update_post_meta( $post->ID, 'em_performer_gallery', implode( ',', $updated_gallery_ids ) );
            }
        }
        ?>
            </ul>

            <input type="hidden" id="em_performer_gallery" name="em_performer_gallery" value="<?php echo esc_attr( implode( ',', $updated_gallery_ids ) ); ?>" />

        </div>
        <p class="ep_add_performer_gallery hide-if-no-js">
            <a href="#" 
                data-choose="<?php echo esc_attr( sprintf( __( 'Add images to %s gallery', 'eventprime-event-calendar-management' ), strtolower( $performer_text ) ) ); ?>" 
                data-update="<?php esc_attr_e( 'Add to gallery', 'eventprime-event-calendar-management' ); ?>" 
                data-delete="<?php esc_attr_e( 'Delete image', 'eventprime-event-calendar-management' ); ?>" 
                data-text="<?php esc_attr_e( 'Delete', 'eventprime-event-calendar-management' ); ?>"
                >
        <?php esc_html_e( sprintf( __( 'Add %s gallery images', 'eventprime-event-calendar-management' ), strtolower( $performer_text ) ) ); ?>

            </a>
        </p>
        <?php
    }

    /*
     * Adding Performer role in List Column
     */

    public function ep_performer_posts_columns( $defaults ) {
        $offset           = 2;
        $performer_column = array(
            'ep_perfomer_role' => esc_html__( 'Role', 'eventprime-event-calendar-management' ),
        );
        return array_merge( array_slice( $defaults, 0, $offset ), $performer_column, array_slice( $defaults, $offset, null ) );
    }

    public function ep_performer_posts_custom_columns( $column_name, $post_id ) {
        if ( $column_name == 'ep_perfomer_role' ) {
            $role = get_post_meta( $post_id, 'em_role', true );
            if ( !empty( $role ) ) {
                echo esc_html( $role );
            } else {
                echo '---';
            }
        }
    }

    /*
     * Remove Editor
     */

    public function remove_defult_fields() {
        $args_completed = array(
            'label'                     => _x( 'Completed', 'Completed', 'z' ),
            /* translators: %s is the number of completed bookings */
            'label_count'               => _n_noop( 'Completed (%s)', 'Completed (%s)', 'z' ),
            'public'                    => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'exclude_from_search'       => false,
            'post_type'                 => array( 'em_booking' ),
        );
        $args_cancelled = array(
            'label'                     => _x( 'Cancelled', 'Cancelled', 'z' ),
            /* translators: %s is the number of cancelled bookings */
            'label_count'               => _n_noop( 'Cancelled (%s)', 'Cancelled (%s)', 'z' ),
            'public'                    => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'exclude_from_search'       => false,
            'post_type'                 => array( 'em_booking' ),
        );
        $args_refunded  = array(
            'label'                     => _x( 'Refunded', 'Refunded', 'z' ),
            /* translators: %s is the number of refunded bookings */
            'label_count'               => _n_noop( 'Refunded (%s)', 'Refunded (%s)', 'z' ),
            'public'                    => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'exclude_from_search'       => false,
            'post_type'                 => array( 'em_booking' ),
        );
        $args_pending   = array(
            'label'                     => _x( 'Pending', 'Pending', 'z' ),
            /* translators: %s is the number of pending bookings */
            'label_count'               => _n_noop( 'Pending (%s)', 'Pending (%s)', 'z' ),
            'public'                    => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'exclude_from_search'       => false,
            'post_type'                 => array( 'em_booking' ),
        );
        $args_failed   = array(
            'label'                     => _x( 'Failed', 'Failed', 'z' ),
            /* translators: %s is the number of pending bookings */
            'label_count'               => _n_noop( 'Failed (%s)', 'Failed (%s)', 'z' ),
            'public'                    => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'exclude_from_search'       => false,
            'post_type'                 => array( 'em_booking' ),
        );

        register_post_status( 'completed', $args_completed );
        register_post_status( 'cancelled', $args_cancelled );
        register_post_status( 'refunded', $args_refunded );
        register_post_status( 'pending', $args_pending );
        register_post_status( 'failed', $args_failed );
        remove_post_type_support( 'em_booking', 'editor' );
        remove_post_type_support( 'em_booking', 'title' );
    }

    /*
     * Adding Sponsor logo in List Column
     */

    public function ep_filter_booking_columns( $defaults ) {
        $offset = 2;
        unset( $defaults['comments'] );
        unset( $defaults['date'] );
        unset( $defaults['title'] );
        $sponsor_column = array(
            'ep_title'          => esc_html__( 'Event', 'eventprime-event-calendar-management' ),
            'ep_booking_id'     => esc_html__( 'Booking ID', 'eventprime-event-calendar-management' ),
            'ep_user_email'     => esc_html__( 'User Email', 'eventprime-event-calendar-management' ),
            'ep_event_date'     => esc_html__( 'Booking Date', 'eventprime-event-calendar-management' ),
            'ep_attendees'      => esc_html__( 'No. Of Attendees', 'eventprime-event-calendar-management' ),
            'ep_status'         => esc_html__( 'Booking Status', 'eventprime-event-calendar-management' ),
            'ep_gateway'        => esc_html__( 'Payment Gateway', 'eventprime-event-calendar-management' ),
            'ep_payment_status' => esc_html__( 'Payment Status', 'eventprime-event-calendar-management' ),
        );
        return array_merge( array_slice( $defaults, 0, $offset ), $sponsor_column, array_slice( $defaults, $offset, null ) );
    }

    /*
     * Adding Booking list content
     */

    public function ep_filter_booking_columns_content( $column_name, $post_id ) {
        $booking_controller = new EventPrime_Bookings();
        $ep_functions       = new Eventprime_Basic_Functions();
        $booking            = $booking_controller->load_booking_detail( $post_id, false );
        $booking_event_id   = $booking->em_event;
        $payment_method     = '';
        if ( !empty( $booking->em_payment_method ) ) {
            $payment_method = ucfirst( $booking->em_payment_method );
        } else {
            if ( !empty( $booking->em_order_info['payment_gateway'] ) ) {
                $payment_method = ucfirst( $booking->em_order_info['payment_gateway'] );
            }
        }
        if ( $column_name == 'ep_booking_id' ) {
            ?>
            <strong>
            <?php echo '#' . absint( $post_id ); ?>
            </strong>
            <?php
        }
        if ( $column_name == 'ep_title' ) {
            $oldtitle = get_the_title();
            if ( !empty( $oldtitle ) ) {
                ?>
                <strong>
                    <a class="row-title" href="<?php echo esc_url( get_edit_post_link() ); ?>">
                <?php echo esc_html( $oldtitle ); ?>
                <?php do_action( 'ep_booking_list_content_after_event_title', $booking ); ?>
                    </a>
                </strong>
                <?php
            } else {
                $booking_em_name = get_post_meta( $booking_event_id, 'em_name', true );
                if ( !empty( $booking_em_name ) ) {
                    ?>
                    <strong>
                        <a class="row-title" href="<?php echo esc_url( get_edit_post_link() ); ?>">
                    <?php echo esc_html( $booking_em_name ); ?>
                    <?php do_action( 'ep_booking_list_content_after_event_title', $booking ); ?>
                        </a>
                    </strong>
                    <?php
                }
            }
        }
        if ( $column_name == 'ep_user_email' ) {
            ?>
            <span>
            <?php
            $user_id = isset( $booking->em_user ) ? (int) $booking->em_user : 0;
            if ( $user_id ) {
                $user = get_userdata( $user_id );
                echo esc_html( $user->user_email );
            } else {
                $order_info = $booking->em_order_info;
                if ( !empty( $order_info ) && !empty( $order_info['user_email'] ) ) {
                    echo esc_html( $order_info['user_email'] );
                } else {
                    echo esc_html__( 'Guest', 'eventprime-event-calendar-management' );
                }
            }
            ?>
            </span>
			<?php
        }

		if ( $column_name == 'ep_event_date' ) {
                    if(isset($booking->post_data->post_date))
                    {
                        echo esc_html($booking->post_data->post_date);
                    }
			/*$em_start_date = get_post_meta( $booking, 'em_start_date', true );
			if ( !empty( $em_start_date ) ) {
				?>
                <span>
                <?php
                echo esc_html( $ep_functions->ep_timestamp_to_date( $em_start_date, 'dS M Y', 1 ) );
                $em_start_time = get_post_meta( $booking_event_id, 'em_start_time', true );
                if ( !empty( $em_start_time ) ) {
                    echo ', ' . esc_html( $em_start_time );
                }
                ?>
                </span>
                <?php
            } else {
                echo '--';
            } */
        }
        if ( $column_name == 'ep_attendees' ) {
            if ( !empty( $booking->em_attendee_names ) && count( $booking->em_attendee_names ) > 0 ) {
                $attendee_count = 0;
                if ( isset( $booking->em_old_ep_booking ) && $booking->em_old_ep_booking == 1 ) {
                    $attendee_count = $booking->em_order_info['quantity'];
                } else {
                    foreach ( $booking->em_attendee_names as $attendee_data ) {
                        if ( !empty( $attendee_data ) ) {
                            $attendee_data = (array) $attendee_data;
                        }
                        $attendee_count += count( $attendee_data );
                    }
                }
                echo absint( $attendee_count );
            } else {
                echo '--';
            }
        }
        if ( $column_name === 'ep_status' ) {
            if ( !empty( $booking->em_status ) ) {
                if ( $booking->em_status == 'publish' || $booking->em_status == 'completed' ) {
                    ?>
                    <span class="ep-booking-status ep-status-confirmed">
                        <?php esc_html_e( 'Completed', 'eventprime-event-calendar-management' ); ?>
                        <span class="ep-booking-status-icons dashicons dashicons-yes"></span>
                    </span>
                    <?php
                }
                if ( $booking->em_status == 'pending' ) {
                    ?>
                    <span class="ep-booking-status ep-status-pending">
                    <?php esc_html_e( 'Pending', 'eventprime-event-calendar-management' ); ?>
                    </span> 
                    <?php
                }
                if ( $booking->em_status == 'cancelled' ) {
                    ?>
                    <span class="ep-booking-status ep-status-cancelled">
                    <?php esc_html_e( 'Cancelled', 'eventprime-event-calendar-management' ); ?>
                    </span>
                    <?php
                }
                if ( $booking->em_status == 'refunded' ) {
                    ?>
                    <span class="ep-booking-status ep-status-refunded">
                    <?php esc_html_e( 'Refunded', 'eventprime-event-calendar-management' ); ?>
                    </span>
                    <?php
                }
                if ( $booking->em_status == 'draft' ) {
                    ?>
                    <span class="ep-booking-status ep-status-draft">
                    <?php esc_html_e( 'Draft', 'eventprime-event-calendar-management' ); ?>
                    </span>
                    <?php
                }
                if ( $booking->em_status == 'failed' ) {
                    ?>
                    <span class="ep-booking-status ep-status-failed">
                    <?php esc_html_e( 'Failed', 'eventprime-event-calendar-management' ); ?>
                    </span>
                    <?php
                }
            } else {
                $booking_status = $booking->post_data->post_status;
                if ( !empty( $booking_status ) ) {
                    $status = $ep_functions->ep_get_booking_status();
                    ?>
                    <span class="ep-booking-status ep-status-<?php echo esc_attr( $booking_status ); ?>">
                        <?php echo esc_html( $status[ $booking_status ] ); ?>
                    </span>
                    <?php
                } else {
                    echo '--';
                }
            }
        }
        if ( $column_name == 'ep_gateway' ) {
            if ( !empty( $payment_method ) ) {
                echo esc_html( $payment_method );
            } else {
                echo '--';
            }
        }
        if ( $column_name == 'ep_payment_status' ) {
            $payment_log = isset( $booking->em_payment_log ) ? $booking->em_payment_log : array();
            if ( !empty( $payment_log ) ) {
                if ( strtolower( $payment_method ) == 'offline' ) {
                    echo isset( $payment_log['offline_status'] ) ? esc_html( $payment_log['offline_status'] ) : '';
                } else {
                    $payment_status = isset($payment_log['payment_status']) ? $payment_log['payment_status'] : '';
                    if ( !empty( $payment_status ) ) {
                        if ( $payment_status == 'completed' ) {
                            echo esc_html( 'Received' );
                        } else {
                            echo esc_html( ucfirst( $payment_status ) );
                        }
                    }
                }
            } else {
                echo '--';
            }
        }
    }

    public function ep_remove_actions( $actions, $post ) {
        if ( $post->post_type == 'em_booking' ) {
            unset( $actions['edit'] );
            unset( $actions['trash'] );
            unset( $actions['view'] );
            unset( $actions['inline hide-if-no-js'] );
        }
        return $actions;
    }

    /*
     * Adding Filter to booking
     */

    public function ep_booking_filters() {
        global $typenow;

        if ( $typenow == 'em_booking' ) {
            $payment_method          = apply_filters( 'ep_payments_gateways_list', array() );
            $selected_payment_method = $start_date = $end_date = '';
            $selected_event = '';
            //$ep_functions = new Eventprime_Basic_Functions;
            $db_handler = new EP_DBhandler();
            $events     = $db_handler->ep_get_all_event_minimum_data();
            if ( isset( $_GET['ep_booking_filter_nonce_field'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['ep_booking_filter_nonce_field'] ) ), 'ep_booking_filter_nonce_action' ) ) 
            {
                // Only process the event_id if nonce verification is successful
                if ( isset( $_GET['event_id'] ) ) {
                    $selected_event = absint( sanitize_text_field( wp_unslash( $_GET['event_id'] ) ) );
                }
                
                if ( isset( $_GET['em_start_date'] ) && preg_match( '/^[0-9-]+$/', sanitize_text_field(wp_unslash( $_GET['em_start_date'] )) ) ) {
                        $start_date = sanitize_text_field( wp_unslash( $_GET['em_start_date'] ) );
                }
                
                if ( isset( $_GET['em_end_date'] ) && preg_match( '/^[0-9-]+$/', sanitize_text_field(wp_unslash( $_GET['em_end_date'] ) ) )) {
                        $end_date = sanitize_text_field( wp_unslash( $_GET['em_end_date'] ) );
                }

                if ( isset( $_GET['payment_method'] ) ) {
                        $selected_payment_method = sanitize_text_field( wp_unslash( $_GET['payment_method'] ) );
                }
                
            }
            
            wp_nonce_field( 'ep_booking_filter_nonce_action', 'ep_booking_filter_nonce_field' ); ?>
            <select name="event_id" id="ep_event_id">
                <option value="all"><?php esc_html_e( 'All Events', 'eventprime-event-calendar-management' ); ?></option>
            <?php
            if ( isset( $events ) && !empty( $events ) ) {
                foreach ( $events as $event ) {
					?>
                    <option value="<?php echo esc_attr( $event->ID ); ?>" <?php selected( $event->ID, $selected_event ); ?>><?php echo esc_attr( $event->post_title ); ?></option>
					<?php
				}
			}
            ?>
            </select>
            <input type="hidden" id="ep_booking_event_id" value="<?php echo !empty( $selected_event ) ? esc_attr( $selected_event ) : 'all'; ?>">
            <?php
            
            ?>
            <select name="payment_method" id="ep_booking_payment">
                <option value="all"><?php esc_html_e( 'All Payment Methods', 'eventprime-event-calendar-management' ); ?></option>
            <?php foreach ( $payment_method as $key => $payment ) { ?>
                    <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $selected_payment_method ); ?>><?php echo esc_attr( $payment['method'] ); ?></option>
                <?php } ?>
            </select>
            <?php do_action( 'ep_add_new_booking_filters' ); ?>
            <?php $today = date('Y-m-d'); ?>
            <span><?php esc_html_e( 'Start Date', 'eventprime-event-calendar-management' ); ?></span>
            <input type="date" id="ep_booking_start_date" name="em_start_date" value="<?php echo isset( $_GET['em_start_date'] ) ? esc_attr( $start_date ) : ''; ?>" placeholder="<?php esc_html_e( 'Start Date', 'eventprime-event-calendar-management' ); ?>" max="<?php echo esc_attr($today); ?>"/>

            <span><?php esc_html_e( 'End Date', 'eventprime-event-calendar-management' ); ?></span>
            <input type="date" id="ep_booking_end_date" name="em_end_date" value="<?php echo isset( $_GET['em_end_date'] ) ? esc_attr( $end_date ) : ''; ?>" placeholder="<?php esc_html_e( 'End Date', 'eventprime-event-calendar-management' ); ?>" max="<?php echo esc_attr($today); ?>"/>
                                                                                             <?php

        }
    }

    /*
     * Modify Filter Query
     */

    public function ep_booking_filters_argu( $query ) {
    global $pagenow;

    // Verify nonce before processing the query
    if ( isset( $_GET['ep_booking_filter_nonce_field'] ) && 
         !wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['ep_booking_filter_nonce_field'] ) ), 'ep_booking_filter_nonce_action' ) ) {
        return $query; // Nonce verification failed, return the original query
    }

    $post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : '';

    if ( is_admin() && $pagenow == 'edit.php' && $post_type == 'em_booking' ) {
        $meta_query = array(); // Initialize meta_query array

        // Filter by Event ID
        if ( isset( $_GET['event_id'] ) && $_GET['event_id'] != 'all' ) {
            $meta_query[] = array(
                'key'     => 'em_event',
                'value'   => absint( sanitize_text_field( wp_unslash( $_GET['event_id'] ) ) ),
                'compare' => '='
            );
        }

        // Filter by Payment Method
        if ( isset( $_GET['payment_method'] ) && $_GET['payment_method'] != 'all' ) {
            $meta_query[] = array(
                'key'     => 'em_payment_method',
                'value'   => sanitize_text_field( wp_unslash( $_GET['payment_method'] ) ),
                'compare' => '='
            );
        }

        // Filter by Start Date
        if ( isset( $_GET['em_start_date'] ) && !empty( $_GET['em_start_date'] ) ) {
            $start_date = sanitize_text_field( wp_unslash( $_GET['em_start_date'] ) );
            $meta_query[] = array(
                'key'     => 'em_date',
                'value'   => strtotime( $start_date ), // Ensure this matches stored format
                'compare' => '>=',
                'type'    => 'NUMERIC'
            );
        }

        // Filter by End Date
        if ( isset( $_GET['em_end_date'] ) && !empty( $_GET['em_end_date'] ) ) {
            $end_date = sanitize_text_field( wp_unslash( $_GET['em_end_date'] ) );
            $meta_query[] = array(
                'key'     => 'em_date',
                'value'   => strtotime( $end_date ), // Ensure this matches stored format
                'compare' => '<=',
                'type'    => 'NUMERIC'
            );
        }

        $meta_query = apply_filters( 'ep_new_add_booking_filter_query', $meta_query );

        // Apply the meta_query only if filters were set
        if ( !empty( $meta_query ) ) {
            $query->set( 'meta_query', $meta_query );
        }
    }

    return $query;
}

    
    public function ep_booking_filters_argu_old( $query ) {
        global $pagenow;
        
        // Verify the nonce before processing the query
        if ( isset( $_GET['ep_booking_filter_nonce_field'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['ep_booking_filter_nonce_field'] ) ), 'ep_booking_filter_nonce_action' ) ) {
            return $query; // Nonce verification failed, return the original query
        }
    
        $post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : '';
        if ( is_admin() && $pagenow == 'edit.php' && $post_type == 'em_booking' && isset( $_GET['event_id'] ) && $_GET['event_id'] != 'all' ) {
            $query->query_vars['meta_key']     = 'em_event';
            $query->query_vars['meta_value']   = absint( sanitize_text_field( wp_unslash( $_GET['event_id'] ) ) );
            $query->query_vars['meta_compare'] = '=';
        }
        if ( is_admin() && $pagenow == 'edit.php' && $post_type == 'em_booking' && isset( $_GET['payment_method'] ) && $_GET['payment_method'] != 'all' ) {
            $query->query_vars['meta_key']     = 'em_payment_method';
            $query->query_vars['meta_value']   = trim(sanitize_text_field(  wp_unslash( $_GET['payment_method'] ) ) );
            $query->query_vars['meta_compare'] = '=';
        }

        if ( is_admin() && $pagenow == 'edit.php' && $post_type == 'em_booking' && isset( $_GET['em_start_date'] ) && !empty( $_GET['em_start_date'] ) ) {
            $start_date                        = sanitize_text_field( wp_unslash( $_GET['em_start_date'] ) );
            $query->query_vars['meta_key']     = 'em_date';
            $query->query_vars['meta_value']   = strtotime( $start_date );
            $query->query_vars['meta_compare'] = '>=';
            $query->query_vars['meta_type']    = 'NUMERIC';
        }

        if ( is_admin() && $pagenow == 'edit.php' && $post_type == 'em_booking' && isset( $_GET['em_end_date'] ) && !empty( $_GET['em_end_date'] ) ) {
            $end_date                          = sanitize_text_field( wp_unslash( $_GET['em_end_date'] ) );
            $query->query_vars['meta_key']     = 'em_date';
            $query->query_vars['meta_value']   = strtotime( $end_date );
            $query->query_vars['meta_compare'] = '<=';
            $query->query_vars['meta_type']    = 'NUMERIC';
        }

        $query = apply_filters( 'ep_add_booking_filter_query', $query );
        return $query;
    }

    /*
     * Remove Date Filter
     */

    public function ep_booking_filters_remove_date( $months ) {
        global $typenow;
        if ( $typenow == 'em_booking' ) {
            return array();
        }
        return $months;
    }

    /*
     * Add Export Option in Bulk Actions
     */

    public function ep_export_booking_bulk_list( $bulk_actions ) {
        $bulk_actions['ep_export_booking'] = esc_html__( 'Export', 'eventprime-event-calendar-management' );
        return $bulk_actions;
    }

    /*
     * Handle Exports
     */

    public function ep_export_booking_bulk_action_handle( $redirect_url, $action, $post_ids ) {
        $booking_controller = new EventPrime_Bookings();
        if ( $action == 'ep_export_booking' ) {
            if ( !empty( $post_ids ) && count( $post_ids ) > 0 ) {
                $booking_controller->export_bookings_bulk_action( 'selected_export', $post_ids );
            }
        }
        return $redirect_url;
    }

    

    /*
     * Add Export all Button
     */

    public function ep_add_booking_export_btn() {
        global $current_screen;

        // Not our post type, exit earlier
        // You can remove this if condition if you don't have any specific post type to restrict to.
        if ( 'em_booking' != $current_screen->post_type ) {
            return;
        }
        $export_all_btn = esc_html__( 'Export All', 'eventprime-event-calendar-management' );
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                let exp_btn_label = '<?php echo esc_html( $export_all_btn ); ?>';
                let exp_btn = '<a id="ep_export_booking_all_btn" class="ep_export_booking_all_btn add-new-h2">' + exp_btn_label + '</a>';
                $($(".wrap h1.wp-heading-inline")[0]).append(exp_btn);
            });
        </script>
        <?php
    }

    public function ep_admin_menus() {
        $user               = wp_get_current_user();
        $em_user_caps_list  = array_keys( $user->allcaps );
        $ep_user_menus_caps = 'manage_options';
        if ( in_array( 'publish_em_events', $em_user_caps_list ) ) {
            $ep_user_menus_caps = 'publish_em_events';
        }
        add_menu_page( esc_html__( 'EventPrime', 'eventprime-event-calendar-management' ), esc_html__( 'EventPrime', 'eventprime-event-calendar-management' ), $ep_user_menus_caps, 'edit.php?post_type=em_event', '', 'dashicons-tickets-alt', '25' );

        remove_menu_page( 'edit.php?post_type=em_event' );

        add_submenu_page( 'edit.php?post_type=em_event', esc_html__( 'Calendar', 'eventprime-event-calendar-management' ), esc_html__( 'Calendar', 'eventprime-event-calendar-management' ), $ep_user_menus_caps, 'ep-event-calendar', array( $this, 'ep_event_calendar' ), 2 );

        add_submenu_page( 'edit.php?post_type=em_event', esc_html__( 'Reports', 'eventprime-event-calendar-management' ), esc_html__( 'Reports', 'eventprime-event-calendar-management' ), $ep_user_menus_caps, 'ep-events-reports', array( $this, 'eventprime_reports' ), class_exists( 'Eventprime_Event_Sponsor' ) ? 10 : 9 );
        add_submenu_page( 'edit.php?post_type=em_event', esc_html__( 'Email', 'eventprime-event-calendar-management' ), esc_html__( 'Email', 'eventprime-event-calendar-management' ), $ep_user_menus_caps, 'ep-bulk-emails', array( $this, 'bulk_emails_page' ), 13 );
        add_submenu_page( 'edit.php?post_type=em_event', esc_html__( 'Shortcodes', 'eventprime-event-calendar-management' ), esc_html__( 'Shortcodes', 'eventprime-event-calendar-management' ), $ep_user_menus_caps, 'ep-publish-shortcodes', array( $this, 'eventprime_publish_shortcodes' ) );
        do_action( 'ep_admin_menus' );
        add_submenu_page( 'edit.php?post_type=em_event', esc_html__( 'EventPrime settings', 'eventprime-event-calendar-management' ), esc_html__( 'Settings', 'eventprime-event-calendar-management' ), 'manage_options', 'ep-settings', array( $this, 'ep_settings_page' ) );
        add_submenu_page( 'edit.php?post_type=em_event', esc_html__( 'Extensions', 'eventprime-event-calendar-management' ), esc_html__( 'Extensions', 'eventprime-event-calendar-management' ), $ep_user_menus_caps, 'ep-extensions', array( $this, 'eventprime_extensions' ) );
        // attendees list page
        add_submenu_page( 'ep_hidden_menu', esc_html__( 'Attendees List', 'eventprime-event-calendar-management' ), esc_html__( 'Attendees List', 'eventprime-event-calendar-management' ), $ep_user_menus_caps, 'ep-event-attendees-list', array( $this, 'ep_show_event_attendees_list' ) );
    }

    /**
     * Show attendees lists of the event
     */
    public function ep_show_event_attendees_list() {
        
        if ( isset( $_GET['ep_attendee_page_filter_nonce_field'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['ep_attendee_page_filter_nonce_field'] ) ), 'ep_attendee_page_filter_nonce_action' ) ) 
        {
            if ( isset( $_GET['event_id'] ) && ! empty( $_GET['event_id'] ) ) {
                $event_id                          = absint( wp_unslash( $_GET['event_id'] ) );
                $em_event_checkout_attendee_fields = get_post_meta( $event_id, 'em_event_checkout_attendee_fields', true );
                $attendee_fileds_data              = ( ! empty( $em_event_checkout_attendee_fields['em_event_checkout_fields_data'] ) ? $em_event_checkout_attendee_fields['em_event_checkout_fields_data'] : array() );
                $filter_value = isset($_GET['attendee_check_in_filter']) ? sanitize_text_field( wp_unslash($_GET['attendee_check_in_filter'])) : '';
                $user_filter_value = isset($_GET['ep_attendee_page_user_filter']) ? sanitize_text_field( wp_unslash( $_GET['ep_attendee_page_user_filter'])) : '';

                include_once 'partials/event-attendee-list.php';
            }
        }
        else
        {
            esc_html_e( 'Security check failed. Please refresh the page and try again later.', 'eventprime-event-calendar-management' );
        }
    }

    /*
     * Create reports tabs html
     */

    public function eventprime_reports() {
        $active_tab = 'booking';
        if ( isset( $_GET['ep_report_tab_nonce_field'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['ep_report_tab_nonce_field'] ) ), 'ep_report_tab_nonce_action' ) ) 
        {
            $active_tab = ( isset( $_GET['tab'] ) && array_key_exists( sanitize_text_field( wp_unslash($_GET['tab'] )), $this->ep_get_reports_tabs() ) ) ? sanitize_text_field( wp_unslash($_GET['tab'] )) : 'booking';
        }
        ?>
        <div class="wrap ep-admin-reports-tabs">
            <form method="post" id="ep_reports" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
                <h2 class="nav-tab-wrapper">
        <?php
        $tab_url = remove_query_arg( array( 'section', 'sub_tab' ) );
        foreach ( $this->ep_get_reports_tabs() as $tab_id => $tab_name ) {
            $tab_url = add_query_arg(
                array( 'tab' => $tab_id,
                'ep_report_tab_nonce_field' => wp_create_nonce( 'ep_report_tab_nonce_action' )),    
                $tab_url
            );
            $active  = $active_tab == $tab_id ? ' nav-tab-active' : '';
            if ( $tab_name['status'] == 'active' ) {
                echo '<a href="' . esc_url( $tab_url ) . '" title="' . esc_attr( $tab_name['label'] ) . '" class="nav-tab' . esc_attr( $active ) . '">';
                echo esc_html( $tab_name['label'] );
                echo '</a>';
            }
        }
        ?>
                </h2>
                    <?php $this->ep_get_reports_tabs_content( $active_tab ); ?>
            </form>
        </div>
                    <?php
                    do_action( 'ep_add_custom_banner' );
	}

                /**
                 * EventPrime reports tabs
                 * return array
                 */
	public function ep_get_reports_tabs() {
		$ep_functions    = new Eventprime_Basic_Functions();
		$tabs            = array();
		$extensions      = $ep_functions->ep_get_activate_extensions();
		$tabs['booking'] = array(
			'label'  => esc_html__( 'Booking', 'eventprime-event-calendar-management' ),
			'status' => 'active',
		);
		if ( !in_array( 'Eventprime_Advanced_Reports', $extensions ) ) {
			$tabs['payment']  = array(
				'label'  => esc_html__( 'Payments', 'eventprime-event-calendar-management' ),
				'status' => 'active',
			);
			$tabs['attendee'] = array(
				'label'  => esc_html__( 'Attendees', 'eventprime-event-calendar-management' ),
				'status' => 'active',
			);
		}
		return apply_filters( 'ep_admin_reports_tabs', $tabs );
	}

                /**
                 * Return reports tabs content
                 */
	public function ep_get_reports_tabs_content( $active_tab ) {
		$report_tabs = array_keys( $this->ep_get_reports_tabs() );
		if ( in_array( $active_tab, $report_tabs ) ) {
			do_action( 'ep_reports_tabs_content', $active_tab );
		}

	}

                /*
                 * Show tabs content based on tab key
                 * @param $tab_key
                 * return html
                 */

	public function ep_reports_tabs_content( $active_tab ) {
		$ep_functions       = new Eventprime_Basic_Functions();
		$events_lists       = $ep_functions->ep_get_events( array( 'id', 'name' ) );
		$data               = new stdClass();
		$bookings           = new stdClass();
		$extensions         = $ep_functions->ep_get_activate_extensions();
		$report_controllers = new EventM_Report_Controller_List();
		$bookings_data      = $report_controllers->ep_booking_reports();
		if ( $active_tab == 'booking' ) {
			include 'partials/reports/bookings.php';
		}
		if ( !in_array( 'Eventprime_Advanced_Reports', $extensions ) ) {
			if ( $active_tab == 'payment' ) {
				include 'partials/reports/payments.php';
			}
			if ( $active_tab == 'attendee' ) {
				include 'partials/reports/attendees.php';
			}
		}
	}

	public function ep_booking_reports_stat( $bookings_data ) {
		include 'partials/reports/parts/bookings/stat.php';
	}

                /*
                 * @param $bookings_data
                 * return html
                 */

	public function ep_booking_reports_booking_list( $bookings_data ) {
		include 'partials/reports/parts/bookings/booking-list.php';
	}

                /*
                 * @param $bookings_data
                 * return html
                 */

	public function ep_booking_reports_booking_list_load_more( $bookings_data ) {
		include 'partials/reports/parts/bookings/load-more-booking-list.php';
	}

	public function ep_settings_page() {
		$global_class = new Eventprime_Global_Settings();
		$global_class->ep_get_settings_html();
		do_action( 'ep_add_custom_banner' );
	}

	public function admin_menu_separator( $parent_file ) {
		$menu    = &$GLOBALS['menu'];
		$submenu = &$GLOBALS['submenu'];
		//epd($submenu);
		$available_sub_menus = array();
		foreach ( $submenu as $key => $item ) {
			foreach ( $item as $index => $data ) {
				$available_sub_menus[] = $data[2];
				if ( strpos( $data[2], 'em_event_type2' ) !== false ) {
					$data[4]                   = 'ep-show-divider';
					$submenu[ $key ][ $index ] = $data;
				} elseif ( strpos( $data[2], 'em_performer' ) !== false ) {
					// $data[4] = 'ep-show-divider';
					$submenu[ $key ][ $index ] = $data;
				}
			}
			foreach ( $item as $index => $data ) {
				if ( in_array( 'ep-settings', $available_sub_menus ) ) {
					if ( strpos( $data[2], 'ep-settings' ) !== false ) {
						// $data[4] = 'ep-show-divider';
						$submenu[ $key ][ $index ] = $data;
					}
				}
			}
		}
		return $parent_file;
	}

	public function ep_setting_form_submit() {
		// Check the nonce.
		if ( empty( $_POST['ep_global_settings_nonce'] ) || !wp_verify_nonce(sanitize_text_field(wp_unslash( $_POST['ep_global_settings_nonce'] )), 'ep_save_global_settings' ) ) {
			return;
		}

		$settings = new EventPrime_Admin_settings();
		$settings->save_settings();
	}

	public function ep_setting_submit_button_callback() {
		$tabs_list = array( 'forms', 'extensions', 'checkoutfields', 'license' );
                $tabs = $section = '';
                if(isset($_GET['tab_nonce']) && wp_verify_nonce(sanitize_text_field( wp_unslash( $_GET['tab_nonce'] )), 'ep_settings_tab'))
                {
                    $tabs      = isset( $_GET['tab'] ) && !empty( $_GET['tab'] ) ? sanitize_text_field(wp_unslash( $_GET['tab'] )) : '';
                    $section   = isset( $_GET['section'] ) && !empty( $_GET['section'] ) ? sanitize_text_field(wp_unslash( $_GET['section'] )) : '';
                }
                 if ( (empty( $section ) && !in_array( $tabs, $tabs_list )) || !empty( $section ) ) {
                     ?>
                    <p class="submit">
                        <input type="hidden" name="action" value="ep_setting_form">
                        <button name="save" class="button-primary ep-save-button" type="submit" value="<?php esc_attr_e( 'Save Changes', 'eventprime-event-calendar-management' ); ?>">
                    <?php esc_html_e( 'Save Changes', 'eventprime-event-calendar-management' ); ?>
                        </button>
                    <?php wp_nonce_field( 'ep_save_global_settings', 'ep_global_settings_nonce' ); ?>
                    </p>
                    <?php
                 }
		
	}

	public function eventprime_extensions() {
		include 'partials/menus/eventprime-extensions.php';
	}

	public function ep_setting_extensions_list() {
		$extension_settings = array();
		return apply_filters( 'ep_extensions_settings', $extension_settings );
	}

	public function bulk_emails_page() {
		include 'partials/menus/eventprime-bulk-emails.php';
		do_action( 'ep_add_custom_banner' );
	}

	public function eventprime_publish_shortcodes() {
		include 'partials/menus/eventprime-publish-shortcodes.php';
		do_action( 'ep_add_custom_banner' );
	}

            /**
             * Before dekete bookings
             */
	public function ep_before_delete_event_bookings( $postid, $post ) {
		if ( 'em_booking' !== $post->post_type ) {
			return;
		}

		global $wpdb;
		// start process of delete event and event data
		$booking_controllers = new EventPrime_Bookings();
		$ep_functions        = new Eventprime_Basic_Functions();

		$booking = $booking_controllers->load_booking_detail( $postid );
		if ( empty( $booking ) ) {
			return;
		}

		// if booked event is seating type, then on delete booking, booked seats should be free
		$event_data = $booking->event_data;
		if ( !empty( $event_data ) ) {
			$event_venue_type = '';
			if ( !empty( $event_data->venue_details ) && !empty( $event_data->venue_details->em_type ) ) {
				$event_venue_type = $event_data->venue_details->em_type;
			}
			if ( $event_venue_type == 'seats' ) {
				$event_id = $booking->em_event;
				// get event seat data
				$event_seat_data = get_post_meta( $event_id, 'em_seat_data', true );
				if ( !empty( $event_seat_data ) ) {
					$event_seat_data = maybe_unserialize( $event_seat_data );
					$em_order_info   = $booking->em_order_info;
					if ( !empty( $em_order_info ) ) {
						$tickets_data = $em_order_info['tickets'];
						if ( !empty( $tickets_data ) && count( $tickets_data ) > 0 ) {
							$extensions         = $ep_functions->ep_get_activate_extensions();
							$seating_controller = new EventM_Live_Seating_List_Controller();
							foreach ( $tickets_data as $tickets ) {
								if ( empty( $tickets->seats ) ) {
									continue;
								}
								foreach ( $tickets->seats as $seat_datas ) {
									$area_id = $seat_datas->area_id;
									// get event seat area from order seat area
									$area_seat_data = $event_seat_data->{$area_id};
									if ( !empty( $area_seat_data ) ) {
										$order_seats = $seat_datas->seat_data;
										if ( !empty( $order_seats ) ) {
											foreach ( $order_seats as $order_seat ) {
												if ( !empty( $order_seat->uid ) ) {
													$seat_uid  = $order_seat->uid;
													$seat_uid  = explode( '-', $seat_uid );
													$row_index = $seat_uid[0];
													$col_index = $seat_uid[1];
													if ( !empty( $area_seat_data->seats[ $row_index ][ $col_index ] ) ) {
														$area_seat_data->seats[ $row_index ][ $col_index ]->type = 'general';
														if ( !empty( $seating_controller ) && in_array( 'Eventprime_Live_Seating', $extensions ) ) {
															$area_seat_data->seats[ $row_index ][ $col_index ]->seatColor = $seating_controller->get_ticket_available_color( $area_seat_data->seats[ $row_index ][ $col_index ]->ticket_id, $event_id );
														} else {
															$area_seat_data->seats[ $row_index ][ $col_index ]->seatColor = '#8cc600';
														}
													}
												}
											}
											$event_seat_data->{$area_id} = $area_seat_data;
											update_post_meta( $event_id, 'em_seat_data', maybe_serialize( $event_seat_data ) );
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}

    public function ep_event_calendar() {
        $ep_functions = new Eventprime_Basic_Functions();
        wp_enqueue_media();
        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_script( 'em-meta-box-admin-custom-js' );
        wp_enqueue_style( 'em-meta-box-admin-custom-css' );
        wp_enqueue_style( 'em-admin-jquery-timepicker' );
	    wp_enqueue_script( 'em-admin-timepicker-js' );
        //wp_enqueue_script( 'jquery-ui-slider' );
        wp_enqueue_style(
            'em-admin-calendar-jquery-ui',
            plugin_dir_url( __FILE__ ) . 'css/jquery-ui.min.css',
            false,
            $this->version
        );

        // load calendar library
        wp_enqueue_style(
            'ep-admin-calendar-event-calendar-css',
            plugin_dir_url( __FILE__ ) . 'css/ep-calendar.min.css',
            false,
            $this->version
        );

        wp_enqueue_script(
            'ep-admin-calendar-event-moment-js',
            plugin_dir_url( __FILE__ ) . 'js/moment.min.js',
            array( 'jquery' ),
            $this->version
        );

        wp_enqueue_script(
            'ep-front-event-calendar-js',
            plugin_dir_url( __FILE__ ) . 'js/ep-calendar.min.js',
            array( 'jquery' ),
            $this->version
        );

        wp_enqueue_script(
            'ep-admin-calendar-event-fulcalendar-moment-js',
            plugin_dir_url( __FILE__ ) . 'js/fullcalendar-moment.min.js',
            array( 'jquery' ),
            $this->version
        );

        wp_enqueue_script(
            'ep-admin-calendar-event-fulcalendar-local-js',
            plugin_dir_url( __FILE__ ) . 'js/locales-all.js',
            array( 'jquery' ),
            $this->version
        );
        wp_enqueue_script(
            'ep-admin-calendar-event-toast-main-js',
            plugin_dir_url( __FILE__ ) . 'js/jquery.toast.min.js',
            array( 'jquery' ),
            $this->version
        );
        wp_enqueue_script(
            'ep-admin-calendar-event-toast-js',
            plugin_dir_url( __FILE__ ) . 'js/toast-message.js',
            array( 'jquery' ),
            $this->version
        );
        wp_enqueue_style(
            'ep-admin-calendar-toast-css',
            plugin_dir_url( __FILE__ ) . 'css/jquery.toast.min.css',
            false,
            $this->version
        );
        wp_enqueue_style(
            'ep-admin-calendar-events-css',
            plugin_dir_url( __FILE__ ) . 'css/ep-admin-calendar-events.css',
            false,
            $this->version
        );
        wp_enqueue_script(
            'ep-admin-calendar-events-js',
            plugin_dir_url( __FILE__ ) . 'js/ep-admin-calendar-events.js',
            array( 'jquery' ),
            $this->version
        );
        // get calendar events
        $events_posts              = array();//$ep_functions->get_multiple_events_post_data( array( 'orderby' => 'meta_value_num', 'post_status' => array( 'publish', 'draft' ) ) );
        $events_data['events']     = $events_posts;
        $events_data['event_atts'] = array();
        $cal_events                = array();
        if ( ! empty( $events_data['events']->posts ) ) {
            //$cal_events = $ep_functions->get_admin_calendar_view_event( $events_data['events']->posts );
        }

        $global_settings = $ep_functions->ep_get_global_settings();
        wp_localize_script(
            'ep-admin-calendar-events-js',
            'eventprime',
            array(
				'global_settings' => $global_settings,

			)
        );

        wp_localize_script(
            'ep-admin-calendar-events-js',
            'em_admin_calendar_event_object',
            array(
                '_nonce'                      => wp_create_nonce( 'ep-frontend-nonce' ),
                'ajaxurl'                     => admin_url( 'admin-ajax.php', null ),
                'filters_applied_text'        => esc_html__( 'Filters Applied', 'eventprime-event-calendar-management' ),
                'nonce_error'                 => esc_html__( 'Please refresh the page and try again.', 'eventprime-event-calendar-management' ),
                'event_attributes'            => $events_data['event_atts'],
                'start_of_week'               => get_option( 'start_of_week' ),
                'cal_events'                  => $cal_events,
                'local'                       => $ep_functions->ep_get_calendar_locale(),
                'errors'                      => array(
                    'title'       => esc_html__( 'Event Title is required.', 'eventprime-event-calendar-management' ),
                    'start_date'  => esc_html__( 'Event Title is required.', 'eventprime-event-calendar-management' ),
                    'end_date'    => esc_html__( 'Event Title is required.', 'eventprime-event-calendar-management' ),
                    'date_error'  => esc_html__( 'Event start date should not greater than end date.', 'eventprime-event-calendar-management' ),
                    'event_price' => esc_html__( 'Event price is required.', 'eventprime-event-calendar-management' ),
                    'quantity'    => esc_html__( 'Event quantity is required.', 'eventprime-event-calendar-management' ),
                    'popup_new'   => esc_html__( 'Add New Event', 'eventprime-event-calendar-management' ),
                    'popup_edit'  => esc_html__( 'Edit Event', 'eventprime-event-calendar-management' ),
                ),
                'image_title'                 => esc_html__( 'Insert logo', 'eventprime-event-calendar-management' ),
                'image_text'                  => esc_html__( 'Use this image', 'eventprime-event-calendar-management' ),
                'add_event_message'           => esc_html__( 'Click on a date to add a new event.', 'eventprime-event-calendar-management' ),
                'frontend_label'              => esc_html__( 'Frontend', 'eventprime-event-calendar-management' ),
                'frontend_event_page'         => get_permalink( $ep_functions->ep_get_global_settings( 'events_page' ) ),
                'list_week_btn_text'          => esc_html__( 'Agenda', 'eventprime-event-calendar-management' ),
                'hide_time_on_front_calendar' => $ep_functions->ep_get_global_settings( 'hide_time_on_front_calendar' ),
            )
        );
        $event_types = $ep_functions->ep_get_event_types( array( 'id', 'name' ) );
        $performers  = $ep_functions->ep_get_performers( array( 'id', 'name' ) );
        $organizers  = $ep_functions->ep_get_organizers( array( 'id', 'name' ) );
        $venues      = $ep_functions->ep_get_venues( array( 'id', 'name' ) );
        include 'partials/settings/settings-admin-calendar.php';
        do_action( 'ep_add_custom_banner' );
    }

    // Migration page
    public function eventprime_revamp_migration() {
		wp_safe_redirect( admin_url( 'edit.php?post_type=em_event' ) );
    }

    /**
     * Redirect plugin after activate
     */
    public function plugin_redirect() {
        if ( get_option( 'event_magic_do_activation_redirect', false ) ) {
            delete_option( 'event_magic_do_activation_redirect' );
            wp_safe_redirect( admin_url( 'edit.php?post_type=em_event' ) );
            exit;
        }
    }
    // Register and load the widget

    public function em_load_calendar_widget() {
        register_widget( 'eventm_calendar' );

    }
    public function em_load_event_countdown() {
        register_widget( 'EventM_Event_Countdown' );
    }
    public function em_load_slider_widget() {
        register_widget( 'eventm_slider' );
    }
    public function em_load_featured_organizer() {
		register_widget( 'eventm_featured_organizer' );
    }
    public function em_load_featured_performer() {
        register_widget( 'eventm_featured_performer' );
    }
    public function em_load_featured_type() {
        register_widget( 'eventm_featured_type' );
    }
    public function em_load_featured_venue() {
        register_widget( 'eventm_featured_venue' );
    }
    public function em_load_popular_organizer() {
        register_widget( 'eventm_popular_organizer' );
    }
    public function em_load_popular_performer() {
        register_widget( 'eventm_popular_performer' );
    }
    public function em_load_popular_type() {
        register_widget( 'eventm_popular_type' );
    }
    public function em_load_popular_venue() {
        register_widget( 'eventm_popular_venue' );
    }

    /**
     * Premium banner
     */
    public function ep_add_custom_banner() {
        include_once 'partials/custom-banner.php';
    }

    public function ep_add_custom_support_text() {
         include_once 'partials/custom-support-text.php';
    }

    public function ep_deactivation_feedback_form() {
        // Enqueue feedback form scripts and render HTML on the Plugins backend page
        if ( get_current_screen()->parent_base == 'plugins' ) {

            wp_enqueue_style( 'ep-admin-utility-style', plugin_dir_url( __FILE__ ) . 'css/ep-admin-common-utility.css', false, $this->version );
            wp_enqueue_script( 'ep-admin-utility-script', plugin_dir_url( __FILE__ ) . 'js/ep-admin-common-utility.js', array( 'jquery', 'jquery-ui-tooltip', 'jquery-ui-dialog' ), $this->version );

            wp_enqueue_script(
                'ep-plugin-feedback-js',
                plugin_dir_url( __FILE__ ) . 'js/ep-plugin-feedback.js',
                array( 'jquery' ),
                EVENTPRIME_VERSION
            );
            wp_localize_script(
                'ep-plugin-feedback-js',
                'ep_feedback',
                array(
                    'ajaxurl'        => admin_url( 'admin-ajax.php' ),
                    'option_error'   => esc_html__( 'Please select one option', 'eventprime-event-calendar-management' ),
                    'feedback_nonce' => wp_create_nonce( 'ep-plugin-deactivation-nonce' ),
                )
            );
            include_once __DIR__ . '/partials/plugin-feedback.php';
        }
    }

    public function ep_in_plugin_update_message( $args, $response ) {
         $show_additional_notice = false;
        if ( isset( $args['new_version'] ) ) {
                $old_version_array = explode( '.', EVENTPRIME_VERSION );
                $new_version_array = explode( '.', $args['new_version'] );

			if ( $old_version_array[0] < $new_version_array[0] ) {
					$show_additional_notice = true;
			} else {
				if ( $old_version_array[1] < $new_version_array[1] ) {
						$show_additional_notice = true;
				}
			}
		}

        if ( $show_additional_notice ) {
                ob_start();
			?>

                <style type="text/css">
                        .ep_plugin_upgrade_notice {
                                font-weight: 400;
                                color: #fff;
                                background: #d53221;
                                padding: 1em;
                                margin: 9px 0;
                                display: flex;
                                box-sizing: border-box;
                                -webkit-box-sizing: border-box;
                                -moz-box-sizing: border-box;

                        }

                        .ep_plugin_upgrade_notice:before {
                                content: "\f488";
                                display: flex;
                                font: 400 32px/1 dashicons;
                                speak: none;
                                margin: 0 8px 0 -2px;
                                -webkit-font-smoothing: antialiased;
                                -moz-osx-font-smoothing: grayscale;
                                align-items: center;
                        }

                        .ep_plugin_upgrade_notice a{
                            color:yellow;
                        }

                </style>

                <span class="ep_plugin_upgrade_notice">
                   <span class=""> 
			<?php
			/* translators: %s is the new version number */
			printf(
                esc_html__( 'Another exciting EventPrime release is ready! Please check the change log and the release notes for version <strong>%s</strong> for detailed information before updating.', 'eventprime-event-calendar-management' ),
                esc_html( $args['new_version'] )
			);
			?>
</span>
                </span>

                <?php
                ob_get_flush();
        }
    }

    public function ep_print_notices() {
        $admin_notices = new EventM_Admin_Notices();
        $admin_notices->ep_print_notices();
    }

    public function ep_conflict_notices() {
		if ( defined( 'EM_VERSION' ) ) {
            ?>
                <div class="notice notice-info" id="ep_dismissible_plugin">
            <p><?php esc_html_e( 'Using EventPrime with Events Manager may cause conflicts with shortcodes or slugs. If you experience issues, consider disabling Events Manager to resolve the conflict.', 'eventprime-event-calendar-management' ); ?></p>
            </div>
            <?php
		}

		if ( defined( 'TRIBE_EVENTS_FILE' ) ) {
			?>
                <div class="notice notice-info" id="ep_dismissible_plugin">
            <p><?php esc_html_e( 'Using EventPrime with The Events Calendar may cause conflicts with shortcodes or slugs. If you experience issues, consider disabling The Events Calendar to resolve the conflict.', 'eventprime-event-calendar-management' ); ?></p>
            </div>
            <?php
		}
    }

    public function ep_dismissible_notice() {
         global $pagenow;
        $ep_functions = new Eventprime_Basic_Functions();
        $notice_name  = get_option( 'ep_dismissible_plugin_notice_4080', '0' );
        $current_page = $ep_functions->is_eventprime_plugin_page();
        if ( $notice_name == '1' ) {
                return;
        }

        if ( $current_page===true || $pagenow === 'plugins.php' ) {
            ?>
            <div class="notice notice-info is-dismissible ep-dismissible" id="ep_dismissible_plugin_notice_4080">
                <p><strong><?php esc_html_e('EventPrime Frontend Update: Please Review Your Layout','eventprime-event-calendar-management'); ?></strong><br/><?php esc_html_e( 'We’ve updated EventPrime for better compatibility with WordPress. Some event page elements, like widgets, may have been reset. Please take a quick look at your event pages to ensure everything looks right.', 'eventprime-event-calendar-management' ); ?><br/><?php esc_html_e('Thank you!', 'eventprime-event-calendar-management' );?></p>
            </div>
            <?php
        }
    }
    
    public function ep_dismissible_buddybot_promotion() {
         global $pagenow;
        $ep_functions = new Eventprime_Basic_Functions();
        $notice_name  = get_option( 'ep_dismissible_buddybot_promotion', '0' );
        $current_page = $ep_functions->is_eventprime_plugin_page();
        if ( $notice_name == '1' || defined('BUDDYBOT_PLUGIN_VERSION') || version_compare( PHP_VERSION, '8.0', '<=' )) {
                return;
        }

        if ( $pagenow !== 'plugins.php' ) {
             $install_url = wp_nonce_url(
                    self_admin_url('update.php?action=install-plugin&plugin=buddybot-ai-custom-ai-assistant-and-chat-agent'),
                    'install-plugin_buddybot-ai-custom-ai-assistant-and-chat-agent' 
                );
            ?>        
            <div class="notice notice-info is-dismissible ep-dismissible" id="ep_dismissible_buddybot_promotion">
                <p>
                    <span>
                        <a href="<?php echo esc_url($install_url); ?>" 
                      class="button button-primary thickbox" 
                      aria-label="<?php esc_attr_e('Install BuddyBot Plugin','eventprime-event-calendar-management'); ?>">
                       <?php esc_html_e('Click here','eventprime-event-calendar-management'); ?>
                   </a>
                    </span>
                    <?php esc_html_e('to try BuddyBot help your visitors find answers fast with an AI chatbot trained on your WordPress content. Built by the EventPrime team.', 'eventprime-event-calendar-management'); ?>
                 
                </p>

            </div>
                
                
                
            <?php
        }
    }

    public function ep_dismissible_notice_ajax() {
         $nonce      = filter_input( INPUT_POST, 'nonce' );
        $notice_name = filter_input( INPUT_POST, 'notice_name' );
        if ( ! isset( $nonce ) || ! wp_verify_nonce( $nonce, 'ep_dismissable_notice_nonce' ) ) {
                die( esc_html__( 'Failed security check', 'eventprime-event-calendar-management' ) );
        }

        if ( current_user_can( 'manage_options' ) ) {
            $notice_name = sanitize_text_field( $notice_name );
            if ( isset( $notice_name ) ) {
                    update_option( $notice_name, '1' );
            }
        }
        die;
    }

    public function ep_add_custom_view_link( $actions, $post ) {
        $ep_functions = new Eventprime_Basic_Functions();
        $array        = array( 'em_event', 'em_performer', 'em_sponsor' );
        if ( in_array( $post->post_type, $array ) && $post->post_status=='publish' ) {
            if ( $post->post_type=='em_event' ) {
                $custom_url = $ep_functions->ep_get_custom_page_url( 'events_page', $post->ID, 'event' );
            }
            if ( $post->post_type=='em_performer' ) {
                $custom_url = $ep_functions->get_performer_single_url( $post->ID );
            }
            if ( $post->post_type=='em_sponsor' ) {
                $custom_url = $ep_functions->ep_get_custom_page_url( 'sponsor_page', $post->ID, 'sponsor' );
            }
            $actions['view_custom'] = '<a href="' . $custom_url . '" target="_blank">View</a>';
        }
        return $actions;
    }

    public function ep_add_custom_view_link_to_admin_bar( $wp_admin_bar ) {
        global $post;
        $ep_functions = new Eventprime_Basic_Functions();
        $array        = array( 'em_event', 'em_performer', 'em_sponsor' );
        if ( isset( $post ) && in_array( $post->post_type, $array ) && $post->post_status == 'publish' ) {
            if ( $post->post_type == 'em_event' ) {
                $custom_url = $ep_functions->ep_get_custom_page_url( 'events_page', $post->ID, 'event' );
                $title      = 'View Event';
            }
            if ( $post->post_type == 'em_performer' ) {
                $custom_url = $ep_functions->get_performer_single_url( $post->ID );
                $title      = 'View Performer';
            }
            if ( $post->post_type == 'em_sponsor' ) {
                $custom_url = $ep_functions->ep_get_custom_page_url( 'sponsor_page', $post->ID, 'sponsor' );
                $title      = 'View Sponsor';
            }
            $wp_admin_bar->add_node(
                array(
					'id'    => 'custom_view_link',
					'title' => $title,
					'href'  => $custom_url,
					'meta'  => array(
						'target' => '_blank',
						'class'  => 'custom-view-link',
					),
                )
            );
        }
    }

    // Add custom view link for custom taxonomy
    public function ep_add_custom_taxonomy_view_link( $actions, $tag ) {
        $ep_functions = new Eventprime_Basic_Functions();
        $array        = array( 'em_event_organizer', 'em_event_type', 'em_venue' );
        if ( in_array( $tag->taxonomy, $array ) ) {
            if ( $tag->taxonomy=='em_event_organizer' ) {
                $custom_url = $ep_functions->ep_get_custom_page_url( 'event_organizers', $tag->term_id, 'organizer', 'term' );
            }
            if ( $tag->taxonomy=='em_event_type' ) {
                $custom_url = $ep_functions->ep_get_custom_page_url( 'event_types', $tag->term_id, 'event_type', 'term' );
            }
            if ( $tag->taxonomy=='em_venue' ) {
                $custom_url = $ep_functions->ep_get_custom_page_url( 'venues_page', $tag->term_id, 'venue', 'term' );
            }
            $actions['view_custom'] = '<a href="' . $custom_url . '" target="_blank">View</a>';
        }
        return $actions;
    }

    /*
    * Duplicate Event
    */
    public function ep_register_duplicate_event_actions( $bulk_actions ) {
        $bulk_actions['duplicate_event'] = esc_html__( 'Duplicate Event', 'eventprime-event-calendar-management' );
        return $bulk_actions;
    }

    /**
     * Duplicate event callback
     */

    public function ep_duplicate_event_bulk_action_handler( $redirect, $doaction, $object_ids ) {
        if ( $doaction !== 'duplicate_event' ) {
            return $redirect;
        }
        if ( ! empty( $object_ids ) ) {
                $basic_functions = new Eventprime_Basic_Functions();
                $dbhandler       = new EP_DBhandler();
                $ep_activator    = new Eventprime_Event_Calendar_Management_Activator();
                foreach ( $object_ids as $event_id ) {
                        $post_id = $basic_functions->eventprime_duplicate_post( $event_id );
                        $event = $basic_functions->get_single_event_detail($post_id);
                        // fetch ticket category and ticket data from custom tables
                        $em_ticket_category_data = $basic_functions->get_event_ticket_category( $event_id );
                        $solo_tickets            = $basic_functions->get_event_solo_ticket( $event_id );
                        // save category
                        if ( isset( $em_ticket_category_data ) && ! empty( $em_ticket_category_data ) ) {
                                $cat_priority = 1;
                                foreach ( $em_ticket_category_data as $cat ) {
                                        $save_data               = array();
                                        $save_data['event_id']   = $post_id;
                                        $save_data['name']       = $cat->name;
                                        $save_data['capacity']   = $cat->capacity;
                                        $save_data['priority']   = 1;
                                        $save_data['status']     = 1;
                                        $save_data['created_by'] = get_current_user_id();
                                        $save_data['created_at'] = wp_date( 'Y-m-d H:i:s', time() );
                                        foreach ( $save_data as $key => $value ) {
                                                $arg[] = $ep_activator->get_db_table_field_type( 'TICKET_CATEGORIES', $key );
                                        }
                                        $cat_id = $dbhandler->insert_row( 'TICKET_CATEGORIES', $save_data, $arg );
                                        $cat_priority++;
                                        //save tickets
                                        if ( isset( $cat->tickets ) && ! empty( $cat->tickets ) ) {
                                                $cat_ticket_priority = 1;
                                                foreach ( $cat->tickets as $ticket ) {
                                                        $ticket_data                   = array();
                                                        $ticket_data['category_id']    = $cat_id;
                                                        $ticket_data['event_id']       = $post_id;
                                                        $ticket_data['name']           = addslashes( $ticket->name );
                                                        $ticket_data['description']    = isset( $ticket->description ) ? addslashes( $ticket->description ) : '';
                                                        $ticket_data['price']          = isset( $ticket->price ) ? $ticket->price : 0;
                                                        $ticket_data['special_price']  = '';
                                                        $ticket_data['capacity']       = isset( $ticket->capacity ) ? absint( $ticket->capacity ) : 0;
                                                        $ticket_data['is_default']     = 1;
                                                        $ticket_data['is_event_price'] = 0;
                                                        $ticket_data['icon']           = isset( $ticket->icon ) ? absint( $ticket->icon ) : '';
                                                        $ticket_data['priority']       = $cat_ticket_priority;
                                                        $ticket_data['status']         = 1;
                                                        $ticket_data['created_at']     = wp_date( 'Y-m-d H:i:s', time() );
                                                        // new
                                                        $ticket_data['additional_fees']        = ( isset( $ticket->ep_additional_ticket_fee_data ) && ! empty( $ticket->ep_additional_ticket_fee_data ) ) ? wp_json_encode( $ticket->ep_additional_ticket_fee_data ) : '';
                                                        $ticket_data['allow_cancellation']     = isset( $ticket->allow_cancellation ) ? absint( $ticket->allow_cancellation ) : 0;
                                                        $ticket_data['show_remaining_tickets'] = isset( $ticket->show_remaining_tickets ) ? absint( $ticket->show_remaining_tickets ) : 0;
                                                        // date
                                                        $start_date = array();
                                                        if ( isset( $ticket->booking_starts ) && ! empty( $ticket->booking_starts ) ) {
                                                                $booking_starts             = json_decode( $ticket->booking_starts );
                                                                $start_date['booking_type'] = $booking_starts->booking_type;
                                                                if ( $booking_starts->booking_type == 'custom_date' ) {
                                                                        if ( isset( $booking_starts->start_date ) && ! empty( $booking_starts->start_date ) ) {
                                                                                $start_date['start_date'] = $booking_starts->start_date;
                                                                        }
                                                                        if ( isset( $booking_starts->start_time ) && ! empty( $booking_starts->start_time ) ) {
                                                                                $start_date['start_time'] = $booking_starts->start_time;
                                                                        }
                                                                } elseif ( $booking_starts->booking_type == 'event_date' ) {
                                                                        $start_date['event_option'] = $booking_starts->event_option;
                                                                } elseif ( $booking_starts->booking_type == 'relative_date' ) {
                                                                        if ( isset( $booking_starts->days ) && ! empty( $booking_starts->days ) ) {
                                                                                $start_date['days'] = $booking_starts->days;
                                                                        }
                                                                        if ( isset( $booking_starts->days_option ) && ! empty( $booking_starts->days_option ) ) {
                                                                                $start_date['days_option'] = $booking_starts->days_option;
                                                                        }
                                                                        $start_date['event_option'] = $booking_starts->event_option;
                                                                }
                                                        }
                                                        $ticket_data['booking_starts'] = wp_json_encode( $start_date );
                                                        // end date
                                                        $end_date = array();
                                                        if ( isset( $ticket->booking_ends ) && ! empty( $ticket->booking_ends ) ) {
                                                                $booking_ends             = json_decode( $ticket->booking_ends );
                                                                $end_date['booking_type'] = $booking_ends->booking_type;
                                                                if ( $booking_ends->booking_type == 'custom_date' ) {
                                                                        if ( isset( $booking_ends->end_date ) && ! empty( $booking_ends->end_date ) ) {
                                                                                $end_date['end_date'] = $booking_ends->end_date;
                                                                        }
                                                                        if ( isset( $booking_ends->end_time ) && ! empty( $booking_ends->end_time ) ) {
                                                                                $end_date['end_time'] = $booking_ends->end_time;
                                                                        }
                                                                } elseif ( $booking_ends->booking_type == 'event_date' ) {
                                                                        $end_date['event_option'] = $booking_ends->event_option;
                                                                } elseif ( $booking_ends->booking_type == 'relative_date' ) {
                                                                        if ( isset( $booking_ends->end_date ) && ! empty( $booking_ends->end_date ) ) {
                                                                                $end_date['days'] = $booking_ends->end_date;
                                                                        }
                                                                        if ( isset( $booking_ends->end_time ) && ! empty( $booking_ends->end_time ) ) {
                                                                                $end_date['days_option'] = $booking_ends->end_time;
                                                                        }
                                                                        $end_date['event_option'] = $booking_ends->event_option;
                                                                }
                                                        }
                                                        $ticket_data['booking_ends']              = wp_json_encode( $end_date );
                                                        $ticket_data['show_ticket_booking_dates'] = ( isset( $ticket->show_ticket_booking_dates ) ) ? 1 : 0;
                                                        $ticket_data['min_ticket_no']             = isset( $ticket->min_ticket_no ) ? $ticket->min_ticket_no : 0;
                                                        $ticket_data['max_ticket_no']             = isset( $ticket->max_ticket_no ) ? $ticket->max_ticket_no : 0;
                                                        // offer
                                                        if ( isset( $ticket->offers ) && ! empty( $ticket->offers ) ) {
                                                                $ticket_data['offers'] = $ticket->offers;
                                                        }
                                                        $ticket_data['multiple_offers_option']       = ( isset( $ticket->multiple_offers_option ) && !empty( $ticket->multiple_offers_option ) ) ? $ticket->multiple_offers_option : '';
                                                        $ticket_data['multiple_offers_max_discount'] = ( isset( $ticket->multiple_offers_max_discount ) && !empty( $ticket->multiple_offers_max_discount ) ) ? $ticket->multiple_offers_max_discount : '';
                                                        $ticket_data['ticket_template_id']           = ( isset( $ticket->ticket_template_id ) && !empty( $ticket->ticket_template_id ) ) ? $ticket->ticket_template_id : '';

                                                        $format = array();
                                                        foreach ( $ticket_data as $key => $value ) {
                                                                $format[] = $ep_activator->get_db_table_field_type( 'TICKET', $key );
                                                        }
                                                        $result = $dbhandler->insert_row( 'TICKET', $ticket_data, $format );
                                                        $cat_ticket_priority++;
                                                }
                                                update_post_meta( $post_id, 'em_enable_booking', 'bookings_on' );
                                        }
                                }
                        }
                        // save tickets
                        if ( isset( $solo_tickets ) && ! empty( $solo_tickets ) ) {
                                $tic = 0;
                                foreach ( $solo_tickets as $ticket ) {
                                        $ticket_data                   = array();
                                        $ticket_data['category_id']    = 0;
                                        $ticket_data['event_id']       = $post_id;
                                        $ticket_data['name']           = addslashes( $ticket->name );
                                        $ticket_data['description']    = isset( $ticket->description ) ? addslashes( $ticket->description ) : '';
                                        $ticket_data['price']          = isset( $ticket->price ) ? $ticket->price : 0;
                                        $ticket_data['special_price']  = '';
                                        $ticket_data['capacity']       = isset( $ticket->capacity ) ? absint( $ticket->capacity ) : 0;
                                        $ticket_data['is_default']     = 1;
                                        $ticket_data['is_event_price'] = 0;
                                        $ticket_data['icon']           = isset( $ticket->icon ) ? absint( $ticket->icon ) : '';
                                        $ticket_data['priority']       = $tic;
                                        $ticket_data['status']         = 1;
                                        $ticket_data['created_at']     = wp_date( 'Y-m-d H:i:s', time() );
                                        // new
                                        $ticket_data['additional_fees']        = ( isset( $ticket->ep_additional_ticket_fee_data ) && ! empty( $ticket->ep_additional_ticket_fee_data ) ) ? wp_json_encode( $ticket->ep_additional_ticket_fee_data ) : '';
                                        $ticket_data['allow_cancellation']     = isset( $ticket->allow_cancellation ) ? absint( $ticket->allow_cancellation ) : 0;
                                        $ticket_data['show_remaining_tickets'] = isset( $ticket->show_remaining_tickets ) ? absint( $ticket->show_remaining_tickets ) : 0;
                                        // date
                                        $start_date = array();
                                        if ( isset( $ticket->booking_starts ) && ! empty( $ticket->booking_starts ) ) {
                                                $booking_starts             = json_decode( $ticket->booking_starts );
                                                $start_date['booking_type'] = $booking_starts->booking_type;
                                                if ( $booking_starts->booking_type == 'custom_date' ) {
                                                        if ( isset( $booking_starts->start_date ) && ! empty( $booking_starts->start_date ) ) {
                                                                $start_date['start_date'] = $booking_starts->start_date;
                                                        }
                                                        if ( isset( $booking_starts->start_time ) && ! empty( $booking_starts->start_time ) ) {
                                                                $start_date['start_time'] = $booking_starts->start_time;
                                                        }
                                                } elseif ( $booking_starts->booking_type == 'event_date' ) {
                                                        $start_date['event_option'] = $booking_starts->event_option;
                                                } elseif ( $booking_starts->booking_type == 'relative_date' ) {
                                                        if ( isset( $booking_starts->days ) && ! empty( $booking_starts->days ) ) {
                                                                $start_date['days'] = $booking_starts->days;
                                                        }
                                                        if ( isset( $booking_starts->days_option ) && ! empty( $booking_starts->days_option ) ) {
                                                                $start_date['days_option'] = $booking_starts->days_option;
                                                        }
                                                        $start_date['event_option'] = $booking_starts->event_option;
                                                }
                                        }
                                        $ticket_data['booking_starts'] = wp_json_encode( $start_date );
                                        // end date
                                        $end_date = array();
                                        if ( isset( $ticket->booking_ends ) && ! empty( $ticket->booking_ends ) ) {
                                                $booking_ends             = json_decode( $ticket->booking_ends );
                                                $end_date['booking_type'] = $booking_ends->booking_type;
                                                if ( $booking_ends->booking_type == 'custom_date' ) {
                                                        if ( isset( $booking_ends->end_date ) && ! empty( $booking_ends->end_date ) ) {
                                                                $end_date['end_date'] = $booking_ends->end_date;
                                                        }
                                                        if ( isset( $booking_ends->end_time ) && ! empty( $booking_ends->end_time ) ) {
                                                                $end_date['end_time'] = $booking_ends->end_time;
                                                        }
                                                } elseif ( $booking_ends->booking_type == 'event_date' ) {
                                                        $end_date['event_option'] = $booking_ends->event_option;
                                                } elseif ( $booking_ends->booking_type == 'relative_date' ) {
                                                        if ( isset( $booking_ends->end_date ) && ! empty( $booking_ends->end_date ) ) {
                                                                $end_date['days'] = $booking_ends->end_date;
                                                        }
                                                        if ( isset( $booking_ends->end_time ) && ! empty( $booking_ends->end_time ) ) {
                                                                $end_date['days_option'] = $booking_ends->end_time;
                                                        }
                                                        $end_date['event_option'] = $booking_ends->event_option;
                                                }
                                        }
                                        $ticket_data['booking_ends']              = wp_json_encode( $end_date );
                                        $ticket_data['show_ticket_booking_dates'] = ( isset( $ticket->show_ticket_booking_dates ) ) ? 1 : 0;
                                        $ticket_data['min_ticket_no']             = isset( $ticket->min_ticket_no ) ? $ticket->min_ticket_no : 0;
                                        $ticket_data['max_ticket_no']             = isset( $ticket->max_ticket_no ) ? $ticket->max_ticket_no : 0;
                                        // offer
                                        if ( isset( $ticket->offers ) && ! empty( $ticket->offers ) ) {
                                                $ticket_data['offers'] = $ticket->offers;
                                        }
                                        $ticket_data['multiple_offers_option']       = ( isset( $ticket->multiple_offers_option ) && !empty( $ticket->multiple_offers_option ) ) ? $ticket->multiple_offers_option : '';
                                        $ticket_data['multiple_offers_max_discount'] = ( isset( $ticket->multiple_offers_max_discount ) && !empty( $ticket->multiple_offers_max_discount ) ) ? $ticket->multiple_offers_max_discount : '';
                                        $ticket_data['ticket_template_id']           = ( isset( $ticket->ticket_template_id ) && !empty( $ticket->ticket_template_id ) ) ? $ticket->ticket_template_id : '';

                                        $format = array();
                                        foreach ( $ticket_data as $key => $value ) {
                                                $format[] = $ep_activator->get_db_table_field_type( 'TICKET', $key );
                                        }
                                        $result = $dbhandler->insert_row( 'TICKET', $ticket_data, $format );
                                        $tic++;
                                }
                        }

                        do_action( 'ep_duplicate_event_extension_data', $event, $post_id );
                }
        }
         return $redirect;
    }

    public function add_eventprime_admin_footer_banner() {
         $ep_functions = new Eventprime_Basic_Functions();
        $current_page  = $ep_functions->eventprime_check_is_ep_dashboard_page();
        $array         = array( 'events', 'ep-event-calendar', 'em_event_type', 'em_venue', 'em_event_organizer', 'performers', 'performer_edit', 'bookings', 'booking_edit', 'ep-events-reports', 'ep-bulk-emails', 'ep-publish-shortcodes', 'ep-import-export', 'ep-settings' );
        if ( !empty( $current_page ) && in_array( $current_page, $array ) ) {
            do_action( 'ep_add_custom_support_text' );
        }
    }

	public function ep_events_filters() {
         global $typenow;
        $ep_functions = new Eventprime_Basic_Functions();
        $filter_types = array(
			'publish_date' => esc_html__( 'Created Date', 'eventprime-event-calendar-management' ),
			'event_date'   => esc_html__( 'Event Date', 'eventprime-event-calendar-management' ),
        );
        if ( $typenow == 'em_event' ) {
            wp_enqueue_style(
                'ep-daterangepicker-css',
                plugin_dir_url( __FILE__ ) . 'css/daterangepicker.css',
                false,
                EVENTPRIME_VERSION
            );
            wp_enqueue_script(
                'ep-daterangepicker-js',
                plugin_dir_url( __FILE__ ) . 'js/daterangepicker.min.js',
                array( 'jquery' ),
                EVENTPRIME_VERSION
            );
            wp_enqueue_script(
                'ep-events-list-js',
                plugin_dir_url( __FILE__ ) . 'js/ep-admin-events-list.js',
                array( 'jquery' ),
                EVENTPRIME_VERSION
            );

            wp_localize_script(
                'ep-events-list-js',
                'eventprime_events_list',
                array(
                    'datepicker_format' => $ep_functions->ep_get_global_settings( 'datepicker_format' ),
                )
            );

            $selected_filter = 'publish_date';
            $filter_date = '';
            if ( isset( $_GET['ep_events_filter_nonce_field'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['ep_events_filter_nonce_field'] ) ), 'ep_events_filter_nonce_action' ) ) 
            {
                if ( isset( $_GET['filter_type'] ) ) {
                    $selected_filter = sanitize_text_field( wp_unslash($_GET['filter_type'] ));
                }
                
                if ( isset( $_GET['ep_filter_date'] ) && ! empty( $_GET['ep_filter_date'] ) ) {
                    $filter_date = sanitize_text_field( wp_unslash($_GET['ep_filter_date'] ));
                }
            }
            wp_nonce_field( 'ep_events_filter_nonce_action', 'ep_events_filter_nonce_field' ); ?>
            <span><?php esc_html_e( 'Filter by', 'eventprime-event-calendar-management' ); ?>
                <select name="filter_type" id="filter_type">
                    <?php foreach ( $filter_types as $key => $type ) { ?>
                        <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $selected_filter ); ?>><?php echo esc_attr( $type ); ?></option>
                    <?php } ?>
                </select>
            </span>
            <span>
                <?php
                esc_html_e( 'Date', 'eventprime-event-calendar-management' );
                ?>
                <input id="event_date_picker" type="text" name="ep_filter_date" value="<?php echo esc_attr( $filter_date ); ?>" placeholder="<?php esc_attr_e( 'Select Date', 'eventprime-event-calendar-management' ); ?>" autocomplete="off"/>
            </span>
            <?php
        }
    }
    
    public function ep_events_filters_starter_guide() {
         global $typenow;
        if ( $typenow == 'em_event' ) {
            ?>
            <div class="alignright actions ep-starter-guide-btn">
                <a href="https://theeventprime.com/starter-guide/" target="_blank" class=" button action">Starter Guide</a>
            </div>
            <?php
        }
    }

    /*
    * Modify Filter Query
    */
    public function ep_events_filters_arguments( $query ) {
         // Ensure WordPress core is fully loaded and we are in admin
        if ( ! function_exists( 'wp_verify_nonce' ) || ! is_admin() ) {
            return $query;
        }

        // Verify the nonce before processing the query
        if ( empty( $_GET['ep_events_filter_nonce_field'] ) || 
            ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['ep_events_filter_nonce_field'] ) ), 'ep_events_filter_nonce_action' ) ) {
            return $query; // Nonce verification failed, return original query
        }
        
        global $pagenow;
        $basic_function = new Eventprime_Basic_Functions();
        $post_type      = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash($_GET['post_type']) ) : '';
        if ( is_admin() && $pagenow == 'edit.php' && $post_type == 'em_event' && isset( $_GET['filter_type'] ) && sanitize_text_field(wp_unslash( $_GET['filter_type'] )) == 'event_date' ) {
            if ( isset( $_GET['ep_filter_date'] ) && ! empty( $_GET['ep_filter_date'] ) ) {
                $date_range = sanitize_text_field(wp_unslash( $_GET['ep_filter_date']) );
                $dates      = explode( ' - ', $date_range );
                $start_date = isset( $dates[0] ) && ! empty( $dates[0] ) ? $dates[0] : '';
                $end_date   = isset( $dates[1] ) && ! empty( $dates[1] ) ? $dates[1] : '';

                if ( ! empty( $start_date ) ) {
                    $start_meta                        = array(
                        'key'     => 'em_start_date',
                        'value'   => $basic_function->ep_date_to_timestamp( $start_date ),
                        'compare' => '>=',
                        'type'    => 'NUMERIC',
                    );
                    $query->query_vars['meta_query'][] = $start_meta;
                }
                if ( ! empty( $end_date ) ) {
                    $end_meta                          = array(
                        'key'     => 'em_end_date',
                        'value'   => $basic_function->ep_datetime_to_timestamp( $end_date . ' 11:59PM' ),
                        'compare' => '<=',
                        'type'    => 'NUMERIC',
                    );
                    $query->query_vars['meta_query'][] = $end_meta;
                }
            } else {
                $start_meta                        = array(
                    'key'     => 'em_start_date',
                    'value'   => $basic_function->ep_get_current_timestamp(),
                    'compare' => '>=',
                    'type'    => 'NUMERIC',
                );
                $query->query_vars['meta_query'][] = $start_meta;
            }
        }
        $post_type = isset( $_GET['post_type'] ) ? sanitize_text_field(wp_unslash( $_GET['post_type'] )) : '';
        if ( is_admin() && $pagenow == 'edit.php' && $post_type == 'em_event' && isset( $_GET['filter_type'] ) && sanitize_text_field( wp_unslash($_GET['filter_type'] )) == 'publish_date' ) {
            if ( isset( $_GET['ep_filter_date'] ) && ! empty( $_GET['ep_filter_date'] ) ) {
                $date_range = sanitize_text_field( wp_unslash($_GET['ep_filter_date'] ));
                $dates      = explode( ' - ', $date_range );
                $start_date = isset( $dates[0] ) && ! empty( $dates[0] ) ? $dates[0] : '';
                $end_date   = isset( $dates[1] ) && ! empty( $dates[1] ) ? $dates[1] : '';
                if ( ! empty( $start_date ) ) {
                    if ( strpos( $start_date, '/' ) !== false ) {
                        $start_date =  str_replace( '/', '-', $start_date );
                    } elseif ( strpos( $start_date, '.' ) !== false ) {
                        $start_date =  str_replace( '.', '-', $start_date );
                    }
                    $start_date                        = gmdate( 'd-m-Y', strtotime( $start_date ) );
                    $start_publish                     = array(
                        'after'     => $start_date,
                        'inclusive' => true,
                    );
                    $query->query_vars['date_query'][] = $start_publish;
                }
                if ( ! empty( $end_date ) ) {
                    if ( strpos( $end_date, '/' ) !== false ) {
                        $end_date =  str_replace( '/', '-', $end_date );
                    } elseif ( strpos( $end_date, '.' ) !== false ) {
                        $end_date =  str_replace( '.', '-', $end_date );
                    }
                    $end_date                          = gmdate( 'd-m-Y', strtotime( '+1 day', strtotime( $end_date ) ) );
                    $end_publish                       = array(
                        'before'    => $end_date,
                        'inclusive' => true,
                    );
                    $query->query_vars['date_query'][] = $end_publish;
                }
                //epd( $query->query_vars['date_query']);
            } else {
                if ( $query->get( 'orderby' ) == '' ) {
                    $query->set( 'orderby', 'publish_date' );
                }
                if ( $query->get( 'order' ) == '' ) {
                    $query->set( 'order', 'desc' );
                }
            }
        }
        return $query;
    }
    
    public function deregister_acf_timepicker_on_custom_post()
    {
        // Check if we're on the edit screen of a specific custom post type
        global $post;
        if (isset($post) && $post->post_type == 'em_event') {
            // Deregister the ACF timepicker script
            wp_deregister_script('acf-timepicker'); // Replace 'acf-timepicker' with the actual handle used by ACF
        }
    }
    
    public function allow_single_term_selection($post_id, $post, $update) {
        // Avoid autosave and revisions
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if ($post->post_type !== 'em_event') return;

        // Define the taxonomies for single selection
        $taxonomies = array('em_event_type', 'em_venue');

        foreach ($taxonomies as $taxonomy) {
            // Get the selected terms
            $terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'ids'));

            if (!empty($terms) && count($terms) > 1) {
                // Keep only the first selected term
                $latest_term = end($terms);
                wp_set_object_terms($post_id, array($latest_term), $taxonomy);
            }
        }
    }
    
    public function ep_privacy_personal_data_exporters($exporters)
    {
        $basic_function = new Eventprime_Basic_Functions();
        $exporters['eventprime'] = array(
            'exporter_friendly_name' => __( 'EventPrime Bookings', 'eventprime-event-calendar-management' ),
            'callback'               => array($basic_function,'ep_privacy_export_personal_data'),
        );

        return $exporters;
    }
    
    /**
    * Export personal data for a user’s EventPrime bookings.
    *
    * Called by WP core privacy tools.
    *
    * @param string $email_address User e-mail.
    * @param int    $page          Page number (starts at 1, 500 items per page).
    * @return array                ['data'=>[], 'done'=>bool]
    */
    
    public function ep_privacy_personal_data_erasers($erasers)
    {
       $basic_function = new Eventprime_Basic_Functions();
        $erasers['eventprime'] = array(
        'eraser_friendly_name' => __( 'EventPrime Bookings', 'eventprime-event-calendar-management' ),
        'callback'             => array($basic_function,'ep_privacy_delete_personal_data'),
        );
        return $erasers;
    }
    
    public function ep_update_retention_cron_schedule() 
    {
        $ep_functions = new Eventprime_Basic_Functions();
        $settings = $ep_functions->ep_get_global_settings();

        $enabled     = ! empty( $settings->enable_gdpr_tools ) && ! empty( $settings->gdpr_retention_period );
        $days        = isset( $settings->gdpr_retention_period ) ? absint( $settings->gdpr_retention_period ) : 0;

        $hook_name = 'ep_gdpr_cleanup_hook';

        if ( $enabled && $days > 0 ) {
            // Schedule if not already scheduled
            if ( ! wp_next_scheduled( $hook_name ) ) {
                wp_schedule_event( time(), 'daily', $hook_name );
            }
        } else {
            // Unschedule if already scheduled
            $timestamp = wp_next_scheduled( $hook_name );
            if ( $timestamp ) {
                wp_unschedule_event( $timestamp, $hook_name );
            }
        }
    }

    public function ep_gdpr_cleanup_old_bookings() 
    {
        $ep_functions = new Eventprime_Basic_Functions();
        $settings     = $ep_functions->ep_get_global_settings();

        if ( empty( $settings->enable_gdpr_tools ) || empty( $settings->gdpr_retention_period ) ) {
            return;
        }

        $retention_days = absint( $settings->gdpr_retention_period );
        if ( $retention_days <= 0 ) return;

        $cutoff_date = date( 'Y-m-d H:i:s', strtotime( '-' . $retention_days . ' days' ) );

        $query = new WP_Query( array(
            'post_type'      => 'em_booking',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'date_query'     => array(
                array(
                    'column' => 'post_date',
                    'before' => $cutoff_date,
                    'inclusive' => false,
                ),
            ),
            'fields' => 'ids',
        ) );

        if ( empty( $query->posts ) ) return;

        foreach ( $query->posts as $booking_id ) {
            // Meta keys to process
            $meta_keys = array(
                'em_order_info',
                'em_payment_log',
                'em_attendee_names',
                'em_booking_fields_data'
            );

            foreach ( $meta_keys as $meta_key ) {
                $original_value = get_post_meta( $booking_id, $meta_key, true );
                if ( empty( $original_value ) ) {
                    continue;
                }
                
                 // Directly delete specific meta keys
                if ( in_array( $meta_key, [ 'em_attendee_names', 'em_booking_fields_data' ,'em_payment_log'], true ) ) {
                    delete_post_meta( $booking_id, $meta_key );
                    continue;
                }

                $data = maybe_unserialize( $original_value );

                // Only proceed if it's an array
                if ( is_array( $data ) ) {
                    // List of sensitive keys to remove
                    $sensitive_keys = array(
                       'user_name','user_phone', 'email', 'user_email', 'first_name', 'last_name', 'phone', 'full_name', 'name','billing_address','shipping_address','guest_booking_custom_data','ep_booking_attendee_fields','ep_rg_field_first_name','ep_rg_field_last_name','ep_rg_field_user_name','ep_rg_field_email','ep_rg_field_password','payee','payer','shipping'
                    );

                    foreach ( $sensitive_keys as $s_key ) {
                        if ( isset( $data[ $s_key ] ) ) {
                            unset( $data[ $s_key ] );
                        }
                    }

                    // If nested array, clean deeply (1 level deep max for now)
                    foreach ( $data as $key => &$value ) {
                        if ( is_array( $value ) ) {
                            foreach ( $sensitive_keys as $s_key ) {
                                if ( isset( $value[ $s_key ] ) ) {
                                    unset( $value[ $s_key ] );
                                }
                            }
                        }
                    }
                    unset( $value ); // clear ref
                }

                update_post_meta( $booking_id, $meta_key, $data );
                
            }

            // Mark this booking as anonymized
            update_post_meta( $booking_id, 'ep_gdpr_anonymized', 1 );
        }
    }




}
