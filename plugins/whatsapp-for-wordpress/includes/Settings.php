<?php
namespace NTA_WhatsApp;

use NTA_WhatsApp\Helper;
use NTA_WhatsApp\Fields;
use NTA_WhatsApp\PostType;

defined( 'ABSPATH' ) || exit;
/**
 * Settings Page
 */
class Settings {

	protected $option;
	protected $option_group            = 'nta_whatsapp_group';
	protected $option_design           = 'nta_whatsapp_design';
	protected $option_button_group     = 'nta_whatsapp_button_group';
	protected $option_woo_button_group = 'nta_wa_woo_button_group';
	protected $option_ga_group         = 'nta_wa_ga_group';

	protected $settings;

	private $floatingWidgetSlug = '';
	private $settingSlug        = '';

	protected static $instance = null;

	public static function getInstance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
			self::$instance->doHooks();
		}
		return self::$instance;
	}

	private function doHooks() {
		add_action( 'admin_init', array( $this, 'register_setting' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'admin_footer', array( $this, 'admin_footer' ) );

		add_action( 'wp_ajax_njt_wa_set_account_position', array( $this, 'set_account_position' ) );
		add_action( 'wp_ajax_njt_wa_load_accounts_ajax', array( $this, 'load_accounts_ajax' ) );
		add_action( 'wp_ajax_njt_wa_set_account_status', array( $this, 'set_account_status' ) );

		add_action( 'wp_ajax_njt_wa_save_display_setting', array( $this, 'save_display_setting' ) );
		add_action( 'wp_ajax_njt_wa_save_design_setting', array( $this, 'save_design_setting' ) );
		add_action( 'wp_ajax_njt_wa_save_woocommerce_setting', array( $this, 'save_woocommerce_setting' ) );
		add_action( 'wp_ajax_njt_wa_save_analytics_setting', array( $this, 'save_analytics_setting' ) );
		add_action( 'wp_ajax_njt_wa_save_url_setting', array( $this, 'save_url_setting' ) );
		add_action( 'wp_ajax_njt_wa_save_accounts_setting', array( $this, 'save_accounts_setting' ) );
		add_action( 'wp_ajax_njt_wa_save_user_role_setting', array( $this, 'save_user_role_setting' ) );
		add_action( 'wp_ajax_njt_wa_get_posts', array( $this, 'get_posts' ) );

		add_filter( 'plugin_action_links_' . NTA_WHATSAPP_BASE_NAME, array( $this, 'addActionLinks' ) );
		add_filter( 'plugin_row_meta', array( $this, 'pluginRowMeta' ), 10, 2 );
	}

	public function __construct() {
	}

	public function addActionLinks( $links ) {
		$links = array_merge(
			array(
				'<a href="' . esc_url( admin_url( '/admin.php?page=nta_whatsapp_floating_widget' ) ) . '">' . __( 'Settings', 'ninjateam-whatsapp' ) . '</a>',
			),
			$links
		);

		return $links;
	}

	public function pluginRowMeta( $links, $file ) {
		if ( strpos( $file, 'whatsapp.php' ) !== false ) {
			$new_links = array(
				'doc' => '<a href="https://ninjateam.org/wordpress-whatsapp-chat-tutorial/" target="_blank">' . __( 'Documentation', 'filebird' ) . '</a>',
			);

			$links = array_merge( $links, $new_links );
		}

		return $links;
	}

	public function admin_menu() {
		$edit_account_link = 'post-new.php?post_type=whatsapp-accounts';

		add_menu_page( 'NTA Whatsapp', 'WhatsApp', 'nta_wa_manage', 'nta_whatsapp', array( $this, 'create_page_setting_widget' ), NTA_WHATSAPP_PLUGIN_URL . 'assets/img/whatsapp-menu.svg', 60 );
		add_submenu_page( 'nta_whatsapp', __( 'Add New account', 'ninjateam-whatsapp' ), __( 'Add New account', 'ninjateam-whatsapp' ), 'nta_wa_manage', $edit_account_link );
		$this->floatingWidgetSlug = add_submenu_page( 'nta_whatsapp', __( 'Floating Widget', 'ninjateam-whatsapp' ), __( 'Floating Widget', 'ninjateam-whatsapp' ), 'nta_wa_manage', 'nta_whatsapp_floating_widget', array( $this, 'floating_widget_view' ) );
		$this->settingSlug        = add_submenu_page( 'nta_whatsapp', __( 'Settings', 'ninjateam-whatsapp' ), __( 'Settings', 'ninjateam-whatsapp' ), 'manage_options', 'nta_whatsapp_setting', array( $this, 'create_page_setting_widget' ) );
	}

	public function admin_footer() {
		$screen = get_current_screen();
		if ( $screen->base !== $this->floatingWidgetSlug ) {
			return;
		}
		require NTA_WHATSAPP_PLUGIN_DIR . 'views/design-preview.php';
	}

	public function admin_enqueue_scripts( $hook_suffix ) {
		if ( $hook_suffix === 'edit.php' || $hook_suffix === 'post-new.php' || $hook_suffix === 'post.php' ) {
			if ( get_post_type() !== 'whatsapp-accounts' ) {
				return;
			}
		} elseif ( ! in_array( $hook_suffix, array( $this->settingSlug, $this->floatingWidgetSlug ) ) ) {
				return;
		}

		wp_register_style( 'nta-css', NTA_WHATSAPP_PLUGIN_URL . 'assets/css/admin.css', array( 'wp-color-picker' ), NTA_WHATSAPP_VERSION );
		wp_enqueue_style( 'nta-css' );

		wp_register_style( 'nta-tippy-css', NTA_WHATSAPP_PLUGIN_URL . 'assets/css/tooltip.css', array(), NTA_WHATSAPP_VERSION );
		wp_enqueue_style( 'nta-tippy-css' );

		wp_dequeue_style( 'woosea_jquery_ui-css' );

		wp_register_style( 'nta-wa-widget', NTA_WHATSAPP_PLUGIN_URL . 'assets/dist/css/style.css', array(), NTA_WHATSAPP_VERSION );
		wp_enqueue_style( 'nta-wa-widget' );
		wp_enqueue_style( 'ui-range', NTA_WHATSAPP_PLUGIN_URL . 'assets/libs/ui-range.css', array(), NTA_WHATSAPP_VERSION );

		if ( function_exists( 'wp_timezone_string' ) ) {
			$timezone = wp_timezone_string();
		} else {
			$timezone = Helper::wp_timezone_string();
		}

		wp_register_script(
			'nta-wa-js',
			NTA_WHATSAPP_PLUGIN_URL . 'assets/dist/js/app.js',
			array(
				'jquery',
				'wp-color-picker',
				'backbone',
				'underscore',
				'jquery-ui-tabs',
				'jquery-ui-sortable',
				'jquery-ui-autocomplete',
			),
			NTA_WHATSAPP_VERSION,
			true
		);
		wp_localize_script(
			'nta-wa-js',
			'njt_wa',
			array(
				'url'      => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'njt-wa-nonce' ),
				'settings' => array(
					'widget' => array(
						'styles' => Fields::getWidgetStyles(),
					),
				),
				'timezone' => $timezone,
				'i18n'     => array(
					'select_post' => __( 'Select posts to display the widget', 'ninjateam-whatsapp' ),
				),
			)
		);
		wp_enqueue_script( 'nta-wa-js' );
		wp_enqueue_script( 'jquery-validate', NTA_WHATSAPP_PLUGIN_URL . 'assets/libs/jquery.validate.min.js', array( 'jquery' ), NTA_WHATSAPP_VERSION );
		wp_dequeue_script( 'wqm-select2-script' );
		wp_dequeue_style( 'wqm-select2-styles' );
		wp_enqueue_script( 'select2-js', NTA_WHATSAPP_PLUGIN_URL . 'assets/libs/select2.full.min.js', array( 'jquery' ), NTA_WHATSAPP_VERSION );
		wp_enqueue_style( 'select2-css', NTA_WHATSAPP_PLUGIN_URL . 'assets/libs/select2.min.css', array(), NTA_WHATSAPP_VERSION );
	}

	public function page_display_settings_section_callback() {
		global $wpdb;
		$option                 = Fields::getWidgetDisplay();
		$option['time_symbols'] = explode( ':', $option['time_symbols'] );
		$pages                  = $wpdb->get_results( "Select ID, post_title from {$wpdb->posts} where post_type = 'page' and post_status = 'publish'" );
		require NTA_WHATSAPP_PLUGIN_DIR . 'views/display-settings.php';
	}

	public function page_design_settings_section_callback() {
		$option                    = Fields::getWidgetStyles();
		$editor_settings           = array(
			'media_buttons' => false,
			'textarea_rows' => get_option( 'default_post_edit_rows', 5 ),
			'quicktags'     => false,
			'teeny'         => true,
		);
		$editor_settings_quicktags = array(
			'media_buttons' => false,
			'textarea_rows' => get_option( 'default_post_edit_rows', 5 ),
			'quicktags'     => true,
			'teeny'         => true,
		);
		require NTA_WHATSAPP_PLUGIN_DIR . 'views/design-settings.php';
	}

	public function page_selected_accounts_section_callback() {
		require NTA_WHATSAPP_PLUGIN_DIR . 'views/selected-accounts.php';
	}

	public function woocommerce_button_callback() {
		$option = Fields::getWoocommerceSetting();

		$activate_dokan_plugin = false;

		if ( class_exists( 'WeDevs_Dokan' ) && defined( 'NTA_WHATSAPP_VERSION' ) ) {
			$activate_dokan_plugin = true;
		}

		if ( ! $activate_dokan_plugin ) {
			if ( isset( $option['enableDokanVendorButton'] ) && 'ON' === $option['enableDokanVendorButton'] ) {
				$option['enableDokanVendorButton'] = 'OFF';
				update_option( 'nta_wa_woocommerce', $option );
			}
		}

		require NTA_WHATSAPP_PLUGIN_DIR . 'views/woocommerce-button.php';
	}

	public function analytics_callback() {
		$option = Fields::getAnalyticsSetting();
		require NTA_WHATSAPP_PLUGIN_DIR . 'views/analytics.php';
	}

	public function url_callback() {
		$option = Fields::getURLSettings();
		require NTA_WHATSAPP_PLUGIN_DIR . 'views/url-settings.php';
	}

	public function accounts_callback() {
		$option = Fields::getAccountsSettings();
		require NTA_WHATSAPP_PLUGIN_DIR . 'views/accounts-settings.php';
	}

	public function user_role_callback() {
		$option = Fields::getUserRoleSettings();
		require NTA_WHATSAPP_PLUGIN_DIR . 'views/user-role-settings.php';
	}

	public function create_page_setting_widget() {
		require NTA_WHATSAPP_PLUGIN_DIR . 'views/settings.php';
	}

	public function floating_widget_view() {
		require NTA_WHATSAPP_PLUGIN_DIR . 'views/floating-widget-settings.php';
	}

	public function register_setting() {
		register_setting( $this->option_group, 'nta_whatsapp_setting' );
		register_setting( $this->option_design, 'nta_whatsapp_setting' );
		register_setting( $this->option_woo_button_group, 'nta_wa_woobutton_setting', array( $this, 'save_woobutton_setting' ) );
		register_setting( $this->option_ga_group, 'nta_wa_ga_setting', array( $this, 'save_ga_setting' ) );

		add_settings_section( 'page_selected_accounts_section', '', array( $this, 'page_selected_accounts_section_callback' ), 'floating-widget-whatsapp-1' );
		add_settings_section( 'page_design_settings_section', '', array( $this, 'page_design_settings_section_callback' ), 'floating-widget-whatsapp-2' );
		add_settings_section( 'page_display_settings_section', '', array( $this, 'page_display_settings_section_callback' ), 'floating-widget-whatsapp-3' );
		add_settings_section( 'nta_woocommerce_button', '', array( $this, 'woocommerce_button_callback' ), 'settings-whatsapp-1' );
		add_settings_section( 'nta_analytics', '', array( $this, 'analytics_callback' ), 'settings-whatsapp-2' );
		add_settings_section( 'nta_url', '', array( $this, 'url_callback' ), 'settings-whatsapp-3' );
		add_settings_section( 'nta_accounts', '', array( $this, 'accounts_callback' ), 'settings-whatsapp-4' );
		add_settings_section( 'nta_user_role', '', array( $this, 'user_role_callback' ), 'settings-whatsapp-5' );
	}

	public function save_woobutton_setting() {
		$new_input = array();

		$new_input['nta_woo_button_position'] = sanitize_text_field( $_POST['nta_woo_button_position'] );
		$new_input['nta_woo_button_status']   = isset( $_POST['nta_woo_button_status'] ) ? 'ON' : 'OFF';
		return $new_input;
	}

	public function save_ga_setting() {
		if ( isset( $_POST['nta_wa_ga_status'] ) ) {
			return '1';
		}
		return '0';
	}

	public function save_display_setting() {
		check_ajax_referer( 'njt-wa-nonce', 'nonce', true );
		$new_input = array();

		$excludePages = Helper::sanitize_array( $_POST['excludePages'] );
		$includePages = Helper::sanitize_array( $_POST['includePages'] );
		$includePosts = Helper::sanitize_array( $_POST['includePosts'] );
		$excludePosts = Helper::sanitize_array( $_POST['excludePosts'] );

		$new_input                         = Fields::getWidgetDisplay();
		$new_input['displayCondition']     = sanitize_text_field( $_POST['displayCondition'] );
		$new_input['displayPostCondition'] = sanitize_text_field( $_POST['displayPostCondition'] );
		$new_input['excludePages']         = empty( $excludePages ) ? array() : $excludePages;
		$new_input['includePages']         = empty( $includePages ) ? array() : $includePages;
		$new_input['includePosts']         = empty( $includePosts ) ? array() : $includePosts;
		$new_input['excludePosts']         = empty( $excludePosts ) ? array() : $excludePosts;
		$new_input['showOnDesktop']        = isset( $_POST['showOnDesktop'] ) ? 'ON' : 'OFF';
		$new_input['showOnMobile']         = isset( $_POST['showOnMobile'] ) ? 'ON' : 'OFF';

		$time_symbols              = Helper::sanitize_array( $_POST['time_symbols'] );
		$new_input['time_symbols'] = wp_unslash( $time_symbols['hourSymbol'] ) . ':' . wp_unslash( $time_symbols['minSymbol'] );

		update_option( 'nta_wa_widget_display', $new_input );
		wp_send_json_success();
	}

	public function save_design_setting() {
		check_ajax_referer( 'njt-wa-nonce', 'nonce', true );

		$new_input = array();

		$new_input                        = Fields::getWidgetStyles();
		$new_input['title']               = sanitize_text_field( wp_unslash( $_POST['title'] ) );
		$new_input['textColor']           = sanitize_hex_color( $_POST['textColor'] );
		$new_input['titleSize']           = sanitize_text_field( $_POST['titleSize'] );
		$new_input['accountNameSize']     = sanitize_text_field( $_POST['accountNameSize'] );
		$new_input['descriptionTextSize'] = sanitize_text_field( $_POST['descriptionTextSize'] );
		$new_input['regularTextSize']     = sanitize_text_field( $_POST['regularTextSize'] );
		$new_input['backgroundColor']     = sanitize_hex_color( $_POST['backgroundColor'] );
		$new_input['description']         = wp_kses_post( wp_unslash( $_POST['description'] ) );
		$new_input['responseText']        = wp_kses_post( wp_unslash( $_POST['responseText'] ) );
		$new_input['scrollHeight']        = sanitize_text_field( $_POST['scrollHeight'] );
		$new_input['isShowScroll']        = isset( $_POST['isShowScroll'] ) ? 'ON' : 'OFF';
		$new_input['isShowResponseText']  = isset( $_POST['isShowResponseText'] ) ? 'ON' : 'OFF';

		$new_input['btnLabel']          = wp_kses_post( wp_unslash( $_POST['btnLabel'] ) ); // It can be an html tag
		$new_input['btnPosition']       = sanitize_text_field( $_POST['btnPosition'] );
		$new_input['btnLabelWidth']     = sanitize_text_field( $_POST['btnLabelWidth'] );
		$new_input['btnLeftDistance']   = sanitize_text_field( $_POST['btnLeftDistance'] );
		$new_input['btnRightDistance']  = sanitize_text_field( $_POST['btnRightDistance'] );
		$new_input['btnBottomDistance'] = sanitize_text_field( $_POST['btnBottomDistance'] );
		$new_input['isShowBtnLabel']    = isset( $_POST['isShowBtnLabel'] ) ? 'ON' : 'OFF';

		$new_input['isShowGDPR']  = isset( $_POST['isShowGDPR'] ) ? 'ON' : 'OFF';
		$new_input['gdprContent'] = wp_kses_post( wp_unslash( $_POST['gdprContent'] ) );
		$new_input['widgetType']  = sanitize_text_field( $_POST['widgetType'] );

		update_option( 'nta_wa_widget_styles', $new_input );
		wp_send_json_success();
	}

	public function save_woocommerce_setting() {
		check_ajax_referer( 'njt-wa-nonce', 'nonce', true );

		$new_input = array();

		$new_input                            = Fields::getWoocommerceSetting();
		$new_input['position']                = sanitize_text_field( $_POST['position'] );
		$new_input['isShow']                  = isset( $_POST['isShow'] ) ? 'ON' : 'OFF';
		$new_input['enableDokanVendorButton'] = isset( $_POST['enableDokanVendorButton'] ) ? 'ON' : 'OFF';

		update_option( 'nta_wa_woocommerce', $new_input );
		wp_send_json_success();
	}

	public function save_analytics_setting() {
		check_ajax_referer( 'njt-wa-nonce', 'nonce', true );

		$new_input = array();

		$new_input                     = Fields::getAnalyticsSetting();
		$new_input['enabledGoogle']    = isset( $_POST['enabledGoogle'] ) ? 'ON' : 'OFF';
		$new_input['enabledFacebook']  = isset( $_POST['enabledFacebook'] ) ? 'ON' : 'OFF';
		$new_input['enabledGoogleGA4'] = isset( $_POST['enabledGoogleGA4'] ) ? 'ON' : 'OFF';

		update_option( 'nta_wa_analytics', $new_input );
		wp_send_json_success();
	}

	public function save_url_setting() {
		check_ajax_referer( 'njt-wa-nonce', 'nonce', true );

		$new_input = array();

		$new_input                 = Fields::getURLSettings();
		$new_input['openInNewTab'] = isset( $_POST['openInNewTab'] ) ? 'ON' : 'OFF';
		$new_input['onDesktop']    = sanitize_text_field( $_POST['onDesktop'] );
		$new_input['onMobile']     = sanitize_text_field( $_POST['onMobile'] );

		update_option( 'nta_wa_url', $new_input );
		wp_send_json_success();
	}

	public function save_accounts_setting() {
		check_ajax_referer( 'njt-wa-nonce', 'nonce', true );

		$new_input = array();

		$new_input                      = Fields::getAccountsSettings();
		$new_input['hideOfflineAgents'] = isset( $_POST['hideOfflineAgents'] ) ? 'ON' : 'OFF';

		update_option( 'nta_wa_accounts', $new_input );
		wp_send_json_success();
	}

	public function save_user_role_setting() {
		check_ajax_referer( 'njt-wa-nonce', 'nonce', true );

		$new_input = array( 'administrator' => true );

		if ( isset( $_POST['userRole'] ) ) {
			$userRole = Helper::sanitize_array( $_POST['userRole'] );
			foreach ( $userRole as $role ) {
				$new_input[ $role ] = true;
				// Add capability to user role
				$role = get_role( $role );
				$role->add_cap( 'nta_wa_manage' );
			}
		}
		$old_settings     = Fields::getUserRoleSettings();
		$roles_remove_cap = array_diff_key( $old_settings, $new_input );
		foreach ( $roles_remove_cap as $role => $value ) {
			$role = get_role( $role );
			$role->remove_cap( 'nta_wa_manage' );
		}
		update_option( 'nta_wa_user_role', $new_input );

		wp_send_json_success();
	}

	public function set_account_position() {
		check_ajax_referer( 'njt-wa-nonce', 'nonce', true );

		$positions = Helper::sanitize_array( $_POST['positions'] );
		$type      = sanitize_text_field( $_POST['type'] );

		foreach ( $positions as $index => $id ) {
			update_post_meta( $id, "nta_wa_{$type}", $index );
		}

		wp_send_json_success();
	}

	public function load_accounts_ajax() {
		check_ajax_referer( 'njt-wa-nonce', 'nonce', true );
		$postType     = PostType::getInstance();
		$accountsList = $postType->get_posts();
		$results      = array_map(
			function ( $account ) {
				$meta        = get_post_meta( $account->ID, 'nta_wa_account_info', true );
				$avatar      = get_the_post_thumbnail_url( $account->ID );
				$wg_show     = get_post_meta( $account->ID, 'nta_wa_widget_show', true );
				$wg_position = get_post_meta( $account->ID, 'nta_wa_widget_position', true );
				$wc_show     = get_post_meta( $account->ID, 'nta_wa_wc_show', true );
				$wc_position = get_post_meta( $account->ID, 'nta_wa_wc_position', true );

				return array_merge(
					array(
						'accountId'       => $account->ID,
						'accountName'     => $account->post_title,
						'edit_link'       => get_edit_post_link( $account->ID ),
						'avatar'          => $avatar !== false ? $avatar : '',
						'widget_show'     => empty( $wg_show ) ? 'OFF' : $wg_show,
						'widget_position' => $wg_position,
						'wc_show'         => empty( $wc_show ) ? 'OFF' : $wc_show,
						'wc_position'     => $wc_position,
					),
					$meta
				);
			},
			$accountsList
		);
		wp_send_json_success( $results );
	}

	public function set_account_status() {
		check_ajax_referer( 'njt-wa-nonce', 'nonce', true );
		$id     = sanitize_text_field( $_POST['accountId'] );
		$type   = sanitize_text_field( $_POST['type'] );
		$status = sanitize_text_field( $_POST['status'] );

		$wg_position = get_post_meta( $id, 'nta_wa_widget_position', true );
		$wc_position = get_post_meta( $id, 'nta_wa_wc_position', true );

		if ( '' === $wg_position ) {
			update_post_meta( $id, 'nta_wa_widget_position', 0 );
		}

		if ( '' === $wc_position ) {
			update_post_meta( $id, 'nta_wa_wc_position', 0 );
		}

		update_post_meta( $id, "nta_wa_{$type}", $status );
		wp_send_json_success();
	}

	public function get_posts() {
		ob_start();

		check_ajax_referer( 'njt-wa-nonce', 'nonce', true );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		$search_text = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';
		$limit       = isset( $_GET['limit'] ) ? absint( wp_unslash( $_GET['limit'] ) ) : -1;
		$exclude_ids = ! empty( $_GET['exclude'] ) ? array_map( 'absint', (array) wp_unslash( $_GET['exclude'] ) ) : array();

		$args = array(
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'posts_per_page'         => $limit,
			'post_type'              => 'post',
			'post_status'            => array( 'publish', 'private', 'draft' ),
			'post__not_in'           => $exclude_ids,
		);

		if ( is_numeric( $search_text ) ) {
			$args['post__in'] = array( absint( $search_text ) );
		} else {
			$args['s'] = $search_text;
		}

		$search_results_query = new \WP_Query( $args );

		$posts_results = array();
		foreach ( $search_results_query->get_posts() as $post ) {
			$posts_results[ $post->ID ] = sprintf(
				__( '%1$s (ID: %2$s)', 'ninjateam-whatsapp' ),
				get_the_title( $post ),
				$post->ID
			);
		}

		wp_send_json( $posts_results );
	}
}
