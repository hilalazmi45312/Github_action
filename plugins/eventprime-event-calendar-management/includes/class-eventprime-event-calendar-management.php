<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://theeventprime.com
 * @since      1.0.0
 *
 * @package    Eventprime_Event_Calendar_Management
 * @subpackage Eventprime_Event_Calendar_Management/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Eventprime_Event_Calendar_Management
 * @subpackage Eventprime_Event_Calendar_Management/includes
 * @author     EventPrime <support@metagauss.com>
 */
class Eventprime_Event_Calendar_Management {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Eventprime_Event_Calendar_Management_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'EVENTPRIME_VERSION' ) ) {
			$this->version = EVENTPRIME_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'eventprime-event-calendar-management';

		$this->load_dependencies();
                $this->define_global_hooks();
		$this->set_locale();
		$this->define_gutenberg_block_hooks();
		$this->define_admin_hooks();
		$this->define_public_hooks();
                $this->define_paypal_hook();
                $this->add_ajax_request();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Eventprime_Event_Calendar_Management_Loader. Orchestrates the hooks of the plugin.
	 * - Eventprime_Event_Calendar_Management_i18n. Defines internationalization functionality.
	 * - Eventprime_Event_Calendar_Management_Admin. Defines all hooks for the admin area.
	 * - Eventprime_Event_Calendar_Management_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
                require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-eventprime-event-calendar-management-activator.php';
                require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-eventprime-event-calendar-management-deactivator.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-eventprime-event-calendar-management-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-eventprime-event-calendar-management-i18n.php';

                require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-eventprime-dbhandler.php';
                
                require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-eventprime-functions.php';
                
                require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-eventprime-requests.php';
                
                require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-eventprime-html-generator.php';
                
                require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-eventprime-sanitizer.php';
                
                require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ep-paypal-service.php';
                
                require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ep-ajax.php';
                
                require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ep-user-controller.php';
                
                require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ep-notification-service.php';
		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-eventprime-event-calendar-management-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-eventprime-event-calendar-management-public.php';
                
                require_once plugin_dir_path( dirname( __FILE__)) . 'includes/class-eventprime-global-settings.php';
                
                require_once plugin_dir_path( dirname( __FILE__)) . 'includes/class-eventprime-admin-settings.php';
                
                require_once plugin_dir_path( dirname( __FILE__)) . 'includes/class-ep-admin-notices.php';
                
                require_once plugin_dir_path( dirname( __FILE__)) . 'includes/class-eventprime-license.php';
                require_once plugin_dir_path( dirname( __FILE__)) . 'includes/class-metagauss-license-migrator.php';
                
                require_once plugin_dir_path( dirname( __FILE__)) . 'includes/class-ep-bookings.php';
                
                require_once plugin_dir_path( dirname( __FILE__)) . 'includes/class-ep-report-controller-list.php';
                require_once plugin_dir_path(dirname( __FILE__)) . 'includes/class-ep-widgets.php';
                include_once  plugin_dir_path(dirname( __FILE__)) . 'includes/blocks/class-ep-magic-blocks.php';
		$this->loader = new Eventprime_Event_Calendar_Management_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Eventprime_Event_Calendar_Management_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Eventprime_Event_Calendar_Management_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}
        private function define_gutenberg_block_hooks() {
		$plugin_block = new EventM_Magic_Blocks;
		$this->loader->add_action( 'init', $plugin_block, 'create_block_ep_blocks_block_init' );
                $this->loader->add_action( 'init', $plugin_block, 'eventprime_block_register' );
                $this->loader->add_action( 'rest_api_init', $plugin_block, 'ep_register_rest_route'  );
		// $this->loader->add_action( 'rest_api_init', $plugin_block, 'pm_register_rest_route' );
		// $this->loader->add_action('block_categories_all',  $plugin_block, 'profilegrid_add_block_categories');
	}
        
        private function define_global_hooks() {
                    $global_settings = new Eventprime_Global_Settings;
		  $this->loader->add_filter( 'plugins_loaded', $this, 'ep_on_plugins_loaded' );
                  $this->loader->add_action( 'plugins_loaded', 'Metagauss_License_Migrator', 'maybe_run', 20 );
                  $this->loader->add_filter( 'ep_payments_gateways_list', $global_settings, 'ep_payments_gateways_list');
                  $this->loader->add_filter('em_cpt_event',$global_settings, 'filer_eventmanager_post');
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Eventprime_Event_Calendar_Management_Admin( $this->get_plugin_name(), $this->get_version() );
                $this->loader->add_action('admin_enqueue_scripts', $plugin_admin,'deregister_acf_timepicker_on_custom_post',999);
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
                $this->loader->add_action( 'init',$plugin_admin, 'register_taxonomies',5);
		$this->loader->add_action( 'init',$plugin_admin, 'register_post_types',4);
		$this->loader->add_action( 'init',$plugin_admin, 'register_post_status',9);
                $this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'ep_event_remove_meta_boxes', 10 );
                $this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'ep_event_register_meta_boxes', 1 );
                $this->loader->add_action( 'admin_menu', $plugin_admin, 'ep_admin_menus'  );
                $this->loader->add_action( 'admin_init', $plugin_admin, 'plugin_redirect' );
                // $this->loader->add_action( 'admin_init', $plugin_admin, 'ep_print_notices' );
                $this->loader->add_action( 'admin_notices', $plugin_admin, 'ep_print_notices' );
                /* 
                 * Event Types start
                 */
                // add and edit form fields
                $this->loader->add_action('em_event_type_add_form_fields', $plugin_admin, 'add_event_type_fields' );
                $this->loader->add_action('em_event_type_edit_form_fields', $plugin_admin, 'edit_event_type_fields', 10);
                // add custom column
                $this->loader->add_filter( 'manage_edit-em_event_type_columns', $plugin_admin, 'add_event_type_custom_columns' );
                $this->loader->add_filter( 'manage_em_event_type_custom_column', $plugin_admin, 'add_event_type_custom_column', 10, 3 );
                
                // save event type
                $this->loader->add_action( 'created_em_event_type', $plugin_admin, 'em_create_event_type_data');
                // edit event type
                $this->loader->add_action( 'edited_em_event_type', $plugin_admin, 'em_create_event_type_data');
                /* 
                 * Event Types end 
                 */
        // add banner
		add_action( 'load-edit-tags.php', function(){
			$screen = get_current_screen();
			if( 'edit-em_event_type' === $screen->id ) {
				add_action( 'after-em_event_type-table', function(){
					do_action( 'ep_add_custom_banner' );
				});
			}
                        
                        if( 'edit-em_venue' === $screen->id ) {
				add_action( 'after-em_venue-table', function(){
					do_action( 'ep_add_custom_banner' );
				});
			}
                        
                        if( 'edit-em_event_organizer' === $screen->id ) {
				add_action( 'after-em_event_organizer-table', function(){
					do_action( 'ep_add_custom_banner' );
				});
			}
                        
		});
                
                add_action( 'load-edit.php', function(){
			$screen = get_current_screen();
                        //print_r($screen->id);
			if( 'edit-em_performer' === $screen->id || 'edit-em_booking' === $screen->id ) {
				  add_action('admin_footer', function() {
                                        do_action('ep_add_custom_banner');
                                   });
			}

		});
                
                add_action('load-post.php', function() {
                    $screen = get_current_screen();

                    // Check for the individual event edit screen
                    if ('em_performer' === $screen->post_type || 'em_booking' === $screen->post_type) {
                        add_action('admin_footer', function() {
                            do_action('ep_add_custom_banner');
                        });
                    }
                });

                add_action('load-post-new.php', function() {
                    $screen = get_current_screen();

                    // Check for the new event creation screen
                    if ('em_performer' === $screen->post_type || 'em_booking' === $screen->post_type) {
                        add_action('admin_footer', function() {
                            do_action('ep_add_custom_banner');
                        });
                    }
                });
                
                add_action( 'admin_footer-edit.php', function() {
                    $screen = get_current_screen();
                    if ( $screen->post_type === 'em_event' ) {
                        ?>
                        <script type="text/javascript">
                            jQuery(document).ready(function($) {
                                // Select the notices inside the wrap
                                var $notices = $('.wrap > .notice, .wrap > .update-nag');

                                if ($notices.length) {
                                    // Move them above the page title
                                    $notices.each(function() {
                                        $('#wpbody-content .wrap').before($(this));
                                    });
                                }
                            });
                        </script>
                        <?php
                    }
                });
                
                
                
                
                
                /*
                 * Event Venues Start
                 */
                // add and edit form fields
                $this->loader->add_action('em_venue_add_form_fields', $plugin_admin, 'add_event_venue_fields' );
		$this->loader->add_action('em_venue_edit_form_fields', $plugin_admin, 'edit_event_venue_fields', 10);
                $this->loader->add_filter( 'manage_edit-em_venue_columns', $plugin_admin, 'add_venue_custom_columns' );
                $this->loader->add_filter( 'manage_em_venue_custom_column',$plugin_admin, 'add_venue_custom_column' , 10, 3 );
                
                // save event venue
                $this->loader->add_action( 'created_em_venue',$plugin_admin, 'em_create_event_venue_data');
                // edit event venue
                $this->loader->add_action( 'edited_em_venue', $plugin_admin, 'em_create_event_venue_data');
               
                 /*
                 * Event Organizer Start
                 */
                // add and edit form fields
                $this->loader->add_action('em_event_organizer_add_form_fields', $plugin_admin, 'add_event_organizer_fields');
                $this->loader->add_action('em_event_organizer_edit_form_fields', $plugin_admin, 'edit_event_organizer_fields', 10);
                // save event organizer
                $this->loader->add_action('created_em_event_organizer', $plugin_admin, 'em_create_event_organizer_data');
                // edit event organizer
                $this->loader->add_action('edited_em_event_organizer', $plugin_admin, 'em_edit_event_organizer_data');
                // add custom column
                $this->loader->add_filter('manage_edit-em_event_organizer_columns', $plugin_admin, 'add_event_organizer_custom_columns');
                $this->loader->add_filter('manage_em_event_organizer_custom_column', $plugin_admin, 'add_event_organizer_custom_column', 10, 3);
                
                
                
                $this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'ep_performer_remove_meta_boxes', 10 );
		$this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'ep_performer_register_meta_boxes', 1 );
                $this->loader->add_action( 'save_post', $plugin_admin, 'ep_save_meta_boxes', 1, 2 );
		$this->loader->add_filter( 'manage_em_performer_posts_columns', $plugin_admin,'ep_performer_posts_columns', 1 );
		$this->loader->add_action( 'manage_em_performer_posts_custom_column', $plugin_admin,'ep_performer_posts_custom_columns', 1, 2 );
                
                $this->loader->add_action( 'init', $plugin_admin, 'remove_defult_fields', 99);
                $this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'ep_bookings_remove_meta_boxes' , 10 );
                $this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'ep_bookings_register_meta_boxes', 1 );
                $this->loader->add_filter( 'post_row_actions',$plugin_admin, 'ep_remove_actions', 10, 2 );
                $this->loader->add_filter( 'manage_em_booking_posts_columns', $plugin_admin, 'ep_filter_booking_columns'  );
                $this->loader->add_action( 'manage_em_booking_posts_custom_column', $plugin_admin, 'ep_filter_booking_columns_content', 10, 2 );
                $this->loader->add_action( 'restrict_manage_posts', $plugin_admin,'ep_booking_filters' );
                $this->loader->add_filter( 'parse_query', $plugin_admin, 'ep_booking_filters_argu' );
                $this->loader->add_filter( 'months_dropdown_results', $plugin_admin,'ep_booking_filters_remove_date' );

                $this->loader->add_filter( 'bulk_actions-edit-em_booking', $plugin_admin,'ep_export_booking_bulk_list', 10, 1 );
                $this->loader->add_filter( 'handle_bulk_actions-edit-em_booking', $plugin_admin, 'ep_export_booking_bulk_action_handle', 10, 3 );
                
                $this->loader->add_action( 'admin_head-edit.php',$plugin_admin, 'ep_add_booking_export_btn');
                $this->loader->add_action( 'before_delete_post', $plugin_admin, 'ep_before_delete_event_bookings', 99, 2 );

                $this->loader->add_action( 'save_post', $plugin_admin, 'ep_save_event_meta_boxes', 1, 2 );
                $this->loader->add_filter( 'manage_em_event_posts_columns', $plugin_admin, 'ep_filter_event_columns'  );
		$this->loader->add_action( 'manage_em_event_posts_custom_column', $plugin_admin, 'ep_filter_event_columns_content', 10, 2 );
		$this->loader->add_filter( 'manage_edit-em_event_sortable_columns',$plugin_admin, 'ep_sortable_event_columns', 10, 1 );
		$this->loader->add_action( 'pre_get_posts',  $plugin_admin, 'ep_sort_events_date' , 10, 1 );
                $this->loader->add_action( 'ep_setting_submit_button', $plugin_admin, 'ep_setting_submit_button_callback' );
                $this->loader->add_action( 'admin_post_ep_setting_form',$plugin_admin,  'ep_setting_form_submit'  );
                $this->loader->add_filter( 'parent_file', $plugin_admin, 'admin_menu_separator');
                
                $this->loader->add_action( 'ep_reports_tabs_content', $plugin_admin, 'ep_reports_tabs_content', 10, 1 );
                $this->loader->add_action( 'ep_bookings_report_stat', $plugin_admin, 'ep_booking_reports_stat', 10 );
                $this->loader->add_action( 'ep_bookings_report_bookings_list', $plugin_admin, 'ep_booking_reports_booking_list', 10, 1 );
                $this->loader->add_action( 'ep_booking_reports_booking_list_load_more', $plugin_admin, 'ep_booking_reports_booking_list_load_more', 10, 1);
                
                /*$this->loader->add_action('wp_ajax_ep_set_default_payment_processor',$plugin_admin,'set_default_payment_processor');
                $this->loader->add_action('wp_ajax_ep_submit_payment_setting',$plugin_admin,'submit_payment_setting');
                $this->loader->add_action('wp_ajax_ep_save_checkout_field',$plugin_admin,'save_checkout_field');
                $this->loader->add_action('wp_ajax_ep_delete_checkout_field',$plugin_admin,'delete_checkout_field');
                $this->loader->add_action('wp_ajax_ep_eventprime_deactivate_license',$plugin_admin,'eventprime_deactivate_license');
                $this->loader->add_action('wp_ajax_ep_eventprime_activate_license',$plugin_admin,'eventprime_activate_license');
                 * 
                 */

                //  add widgests
                $this->loader->add_action('widgets_init', $plugin_admin, 'em_load_calendar_widget', 0);
                $this->loader->add_action('widgets_init', $plugin_admin, 'em_load_event_countdown');
                $this->loader->add_action('widgets_init', $plugin_admin, 'em_load_slider_widget');
                $this->loader->add_action('widgets_init', $plugin_admin, 'em_load_featured_organizer');
                $this->loader->add_action('widgets_init', $plugin_admin, 'em_load_featured_performer');
                $this->loader->add_action('widgets_init', $plugin_admin, 'em_load_featured_type');
                $this->loader->add_action('widgets_init', $plugin_admin, 'em_load_featured_venue');
                $this->loader->add_action('widgets_init', $plugin_admin, 'em_load_popular_organizer');
                $this->loader->add_action('widgets_init', $plugin_admin, 'em_load_popular_performer');
                $this->loader->add_action('widgets_init', $plugin_admin, 'em_load_popular_type');
                $this->loader->add_action('widgets_init', $plugin_admin, 'em_load_popular_venue');
                // add premium banner
                $this->loader->add_action( 'ep_add_custom_banner', $plugin_admin, 'ep_add_custom_banner'  );
                $this->loader->add_action( 'ep_add_custom_support_text', $plugin_admin, 'ep_add_custom_support_text'  );
                $this->loader->add_action( 'admin_footer', $plugin_admin,'add_eventprime_admin_footer_banner',100 );
                // EP Deactivation form 
                $this->loader->add_action( 'admin_footer', $plugin_admin,'ep_deactivation_feedback_form' );
                
                //$this->loader->add_action( 'in_plugin_update_message-'.EP_PLUGIN_BASE, $plugin_admin,'ep_in_plugin_update_message',10,2 );
                
                $this->loader->add_action( 'wp_ajax_ep_dismissible_notice', $plugin_admin, 'ep_dismissible_notice_ajax' );
		
                //$this->loader->add_action( 'admin_notices', $plugin_admin, 'ep_dismissible_notice' );
                
                //$this->loader->add_action( 'admin_notices', $plugin_admin, 'ep_dismissible_buddybot_promotion' );
                
                
                //$this->loader->add_action( 'admin_notices', $plugin_admin, 'ep_conflict_notices' );
                
                //$this->loader->add_filter('post_row_actions', $plugin_admin, 'ep_add_custom_view_link', 10, 2);
                
                //$this->loader->add_action('admin_bar_menu', $plugin_admin, 'ep_add_custom_view_link_to_admin_bar', 100);
                
                $this->loader->add_filter('tag_row_actions', $plugin_admin, 'ep_add_custom_taxonomy_view_link', 10, 2);
                 // add duplicate event option in bulk actions
                $this->loader->add_filter( 'bulk_actions-edit-em_event', $plugin_admin, 'ep_register_duplicate_event_actions' );
                // handle duplicate event bulk action
                $this->loader->add_filter( 'handle_bulk_actions-edit-em_event',$plugin_admin, 'ep_duplicate_event_bulk_action_handler' , 10, 3 );
                
                $this->loader->add_action( 'restrict_manage_posts', $plugin_admin, 'ep_events_filters' );
                $this->loader->add_action( 'restrict_manage_posts', $plugin_admin, 'ep_events_filters_starter_guide' );
                
                $this->loader->add_filter( 'parse_query', $plugin_admin, 'ep_events_filters_arguments', 100, 1 );
                $this->loader->add_action('save_post', $plugin_admin,'allow_single_term_selection', 10, 3);
                $this->loader->add_filter('wp_privacy_personal_data_erasers', $plugin_admin, 'ep_privacy_personal_data_erasers',10,1);
                $this->loader->add_filter('wp_privacy_personal_data_exporters',$plugin_admin,'ep_privacy_personal_data_exporters',10,1);
                $this->loader->add_action('ep_update_retention_cron_schedule',$plugin_admin,'ep_update_retention_cron_schedule');
                $this->loader->add_action( 'ep_gdpr_cleanup_hook', $plugin_admin,'ep_gdpr_cleanup_old_bookings' );

                
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Eventprime_Event_Calendar_Management_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
                $this->loader->add_action( 'init', $plugin_public, 'register_shortcodes' );
                $this->loader->add_action( 'ep_event_view_event_booking_button', $plugin_public, 'ep_event_add_event_booking_button', 10, 1 );
                //$this->loader->add_filter( 'the_content', $plugin_public, 'ep_load_single_template_dynamic',1000000000 );
                //$this->loader->add_filter( 'archive_template', $plugin_public, 'ep_taxonomy_archive_template',1000000000 );
                $this->loader->add_action('pre_get_posts',$plugin_public, 'ep_modify_taxonomy_archive_query',99999999);
                //$this->loader->add_filter('template_include',$plugin_public, 'ep_load_single_template',1000);
                $this->loader->add_filter( 'the_content', $plugin_public, 'ep_load_single_template_dynamic',1000000000 );
                $this->loader->add_filter('post_thumbnail_html', $plugin_public,'remove_thumbnail_on_event_post_type', 10, 5);
                
                $this->loader->add_action( 'ep_events_list_before_render_content', $plugin_public, 'ep_show_timezone_related_message' , 10 );
                $this->loader->add_action( 'ep_events_list_before_render_content', $plugin_public, 'ep_event_add_hidden_variables', 20  );
                // wishlist icon
                $this->loader->add_action( 'ep_event_view_wishlist_icon', $plugin_public, 'ep_event_add_wishlist_icon', 10, 2 );
                // social sharing icon
                $this->loader->add_action( 'ep_event_view_social_sharing_icon', $plugin_public, 'ep_event_add_social_sharing_icon' , 10, 2 );
                $this->loader->add_action( 'ep_event_view_event_dates', $plugin_public, 'ep_event_add_event_dates' , 10, 2 );
                $this->loader->add_action( 'ep_event_view_event_price', $plugin_public, 'ep_event_add_event_price' , 10, 2 );
                // weather widget on the detail page
                $this->loader->add_action( 'ep_event_detail_weather_data', $plugin_public, 'ep_event_detail_add_weather_widget' , 10, 1 );
                $this->loader->add_action( 'body_class', $plugin_public, 'ep_add_body_class', 1 );
                $this->loader->add_action( 'ep_events_booking_count_slider', $plugin_public, 'ep_event_booking_count', 10, 1);
                // modify post message
                $this->loader->add_filter( 'bulk_post_updated_messages', $plugin_public, 'ep_bulk_post_updated_messages_filter', 10, 2 );
                // single event page event dates
                $this->loader->add_action( 'ep_event_detail_right_event_dates_section', $plugin_public, 'ep_event_detail_right_event_dates_section' , 10 );
                // dequeue scripts
                $this->loader->add_action( 'ep_dequeue_event_scripts', $plugin_public, 'ep_dequeue_event_scripts' , 10 );
                // loader
                $this->loader->add_action( 'ep_add_loader_section', $plugin_public, 'ep_add_loader_section' , 10 );
                $this->loader->add_action( 'ep_add_internal_loader_section', $plugin_public, 'ep_add_internal_loader_section' ,10, 2 );
                
                $this->loader->add_action( 'wp_head', $plugin_public, 'ep_custom_styles' , 100 );
                // after update parent event status
                //$this->loader->add_action( 'ep_update_parent_event_status', $plugin_public, 'ep_update_parent_event_status' , 10, 2 );
                // after save event data
                //$this->loader->add_action( 'ep_after_save_event_data', $plugin_public, 'ep_update_event_data_after_save' , 10, 2 );
                // calendar icon
                $this->loader->add_action( 'ep_event_view_calendar_icon', $plugin_public, 'ep_event_add_calendar_icon' , 10, 2 );
                $this->loader->add_action( 'transition_post_status', $plugin_public, 'ep_frontend_event_publish', 10, 3);

                $this->loader->add_action('init',$plugin_public, 'ep_apply_slug_rules',10,0);
                $this->loader->add_filter('query_vars',$plugin_public,'ep_filter_query_vars',10,1);

                // Redirect to Login page upon Logging out 
                $this->loader->add_filter( 'logout_redirect', $plugin_public, 'ep_handle_logout_redirect', 10, 0 );
                $this->loader->add_action('ep_event_booking_event_total',$plugin_public,'eventprime_checkout_total_html_block',10,4);
                $this->loader->add_filter('get_the_post_navigation',$plugin_public,'ep_remove_post_navigation');
                $this->loader->add_action('template_redirect', $plugin_public,'ep_remove_post_navigation_action');
                // iCal download
                $this->loader->add_action( 'init', $plugin_public, 'get_ical_file', 9999 ); // iCal file download.
                $this->loader->add_action('ep_event_booking_before_checkout_button', $plugin_public, 'ep_gdpr_consent_checkbox',1,1);
                $this->loader->add_action('wp_footer', $plugin_public, 'ep_show_gdpr_badge_on_footer');

                
        }
        
        private function add_ajax_request() {
            $plugin_public = new EventM_Ajax_Service();
            $ajax_requests = array(
                'save_checkout_field'               => false,
                'load_more_events'                  => true,
                'delete_checkout_field'             => false,
                'submit_payment_setting'            => false,
                'submit_login_form'                 => true,
                'submit_register_form'              => true,
                'load_more_event_types'             => true,
                'load_more_event_performer'         => true,
                'load_more_event_venue'             => true,
                'load_more_event_organizer'         => true,
                'load_event_single_page'            => true,
                'save_event_booking'                => true,
                'booking_timer_complete'            => true,
                'paypal_sbpr'                       => true,
                'event_booking_cancel'              => false,
                'booking_add_notes'                 => false,
                'booking_update_status'             => false,
                'event_wishlist_action'             => true,
                'save_frontend_event_submission'    => true,
                'load_event_dates'                  => true,
                'load_more_upcomingevent_performer' => true,
                'load_more_upcomingevent_venue'     => true,
                'load_more_upcomingevent_organizer' => true,
                'load_more_upcomingevent_eventtype' => true,
                'filter_event_data'                 => true,
                'load_event_offers_date'            => true,
                'update_user_timezone'              => true,
                'validate_user_details_booking'     => true,
                'get_attendees_email_by_event_id'   => false,
                'send_attendees_email'              => false,
                'upload_file_media'                 => true,
                'rg_check_user_name'                => true,
                'rg_check_email'                    => true,
                'export_submittion_attendees'       => true,
                'eventprime_run_migration'          => false,
                'eventprime_cancel_migration'       => false,
                'reload_checkout_user_section'      => false,
                'eventprime_reports_filter'         => false,
                'set_default_payment_processor'     => false,
                'booking_export_all'                => false,
                'calendar_event_create'             => false,
                'calendar_events_drag_event_date'   => false,
                'calendar_events_delete'            => false,
                'eventprime_activate_license'       => false,
                'eventprime_deactivate_license'     => false,
                'update_event_booking_action'       => false,
                'event_print_all_attendees'         => false,
                'load_edit_booking_attendee_data'   => false,
                'sanitize_input_field_data'         => true,
                'send_plugin_deactivation_feedback' => false,
                'delete_user_fes_event'             => false, 
                'cancel_current_booking_process'    => true,
                'edit_booking_attendee_data_save'   => false,
                'get_calendar_event'                => true,
                'check_offer_applied'               => true,
                'update_tickets_data'               => true,
                'export_user_bookings_data'         => false,
                'request_data_erasure'              => false,
                'request_data_export'               => false,
                'delete_user_bookings_data'         => false,
                'delete_guest_booking_data'         => true,
                
                'save_license_settings'             => false,
                'install_remote_plugin'             => false,
                'activate_plugin'             => false,
                'deactivate_plugin'             => false,
                'deactivate_bundle_license' =>false,
                'upload_license_file' =>false,
                'check_license_status' => false,
                

            );

            foreach ( $ajax_requests as $action => $nopriv ) {
                $this->loader->add_action( 'wp_ajax_ep_' . $action, $plugin_public, $action );
                if ( $nopriv ) {
                    $this->loader->add_action( 'wp_ajax_nopriv_ep_' . $action, $plugin_public, $action );
                }
            }
        }
        
        private function define_paypal_hook()
        {
            $plugin_paypal = new EventM_Paypal_Service();

            $this->loader->add_action( 'ep_front_checkout_payment_processors', $plugin_paypal, 'show_payment_option_on_front',10,1 );
            $this->loader->add_action( 'ep_front_checkout_payment_processors_button', $plugin_paypal, 'show_payment_option_button_on_front', 10, 1 );
        
        }
        

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Eventprime_Event_Calendar_Management_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
        
        public function ep_on_plugins_loaded() {
		add_option( 'emagic_db_version', EM_DB_VERSION );
		$existing_pg_db_version = floatval( get_option( 'emagic_db_version', '1.0' ) );
		if ( $existing_pg_db_version < EM_DB_VERSION ) {
			$ep_activator = new Eventprime_Event_Calendar_Management_Activator();
			$ep_activator->upgrade_db();
		}
	}

}
