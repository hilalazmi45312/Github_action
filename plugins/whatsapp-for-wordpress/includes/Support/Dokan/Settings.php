<?php

namespace NTA_WhatsApp\Support\Dokan;

defined( 'ABSPATH' ) || exit;

class Settings {

	protected static $instance = null;

	public static function getInstance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->doHooks();
		}

		return self::$instance;
	}

	private function __construct() {
	}

	private function doHooks() {
		if ( class_exists( 'WeDevs_Dokan' ) && defined( 'NTA_WHATSAPP_VERSION' ) ) {
			add_action( 'dokan_dashboard_content_before', array( $this, 'enqueueWhatsAppScripts' ) );
			add_filter( 'dokan_get_dashboard_settings_nav', array( $this, 'addWhatsAppSettingMenu' ) );
			add_filter( 'dokan_dashboard_settings_heading_title', array( $this, 'addWhatsAppHeadingTitle' ), 10, 2 );
			add_action( 'dokan_settings_content', array( $this, 'addWhatsAppSettingContent' ) );
			add_action( 'wp_ajax_dokan_save_wa_setting', array( $this, 'saveWhatsAppSetting' ) );
		} else {
			return;
		}
	}

	public function addWhatsAppSettingMenu( $urls ) {
		$urls['whatsapp'] = array(
			'title'      => __( 'WhatsApp', 'ninjateam-whatsapp' ),
			'icon'       => '<i class="fab fa-whatsapp"></i>',
			'url'        => dokan_get_navigation_url( 'settings/whatsapp' ),
			'pos'        => 108,
			'permission' => 'dokan_view_store_settings_menu',
		);

		return $urls;
	}

	public function enqueueWhatsAppScripts() {
		$settings = get_query_var( 'settings' );

		if ( 'whatsapp' !== $settings ) {
			return;
		}
		wp_enqueue_style( 'dokan-whatsapp-setting', NTA_WHATSAPP_PLUGIN_URL . 'assets/css/dokan-admin.css', array(), '1.0' );
		wp_enqueue_script( 'dokan-whatsapp-settings', NTA_WHATSAPP_PLUGIN_URL . 'assets/js/dokan-admin.js', array(), '1.0', true );
		wp_localize_script(
			'dokan-whatsapp-settings',
			'settingWADokan',
			array(
				'adminAjax'      => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( 'dokan-wa-setting' ),
				'successMessage' => esc_html__( 'Your information has been saved successfully.', 'ninjateam-whatsapp' ),
				'errorMessage'   => esc_html__( 'Oop! Something went wrong.', 'ninjateam-whatsapp' ),

			)
		);
	}

	public function addWhatsAppHeadingTitle( $header, $queryVars ) {
		if ( 'whatsapp' === $queryVars ) {
			$header = __( 'WhatsApp Integrated', 'ninjateam-whatsapp' );
		}

		return $header;
	}

	public function addWhatsAppSettingContent() {
		$settings = get_query_var( 'settings' );

		if ( 'whatsapp' !== $settings ) {
			return;
		}
		$params = array();

		require NTA_WHATSAPP_PLUGIN_DIR . 'views/dokan-settings.php';
	}

	public function saveWhatsAppSetting() {
		if ( ! isset( $_POST['dokan_wa_setting_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['dokan_wa_setting_nonce'] ), 'dokan-wa-setting' ) ) {
			return;
		}

		$vendorId = get_current_user_id();

		if ( empty( $vendorId ) ) {
			return;
		}

		$waNumber             = isset( $_POST['waNumber'] ) ? wp_kses_post( $_POST['waNumber'] ) : '';
		$waTitle              = isset( $_POST['waTitle'] ) ? wp_kses_post( $_POST['waTitle'] ) : '';
		$waPredefinedText     = isset( $_POST['waPredefinedText'] ) ? wp_kses_post( $_POST['waPredefinedText'] ) : '';
		$waBtnLabel           = isset( $_POST['waBtnLabel'] ) ? wp_kses_post( $_POST['waBtnLabel'] ) : '';
		$waButtonPosition     = isset( $_POST['waButtonPosition'] ) ? sanitize_text_field( $_POST['waButtonPosition'] ) : '';
		$waDisplayFloatWidget = isset( $_POST['waDisplayFloatWidget'] ) && 'true' == sanitize_text_field( $_POST['waDisplayFloatWidget'] ) ? true : false;
		update_user_meta( $vendorId, 'whatsapp_button_position', $waButtonPosition );
		update_user_meta( $vendorId, 'whatsapp_display_floating_widget', $waDisplayFloatWidget );
		// Create post object
		$userWAId    = get_user_meta( $vendorId, 'dokan_whatsapp_id', true );
		$post        = get_post( $userWAId );
		$accountInfo = array(
			'number'            => $waNumber,
			'title'             => $waTitle,
			'predefinedText'    => $waPredefinedText,
			'willBeBackText'    => 'I will be back in [njwa_time_work]',
			'dayOffsText'       => 'I will be back soon',
			'isAlwaysAvailable' => 'ON',
			'daysOfWeekWorking' => array(
				'sunday'    => array(
					'isWorkingOnDay' => 'OFF',
					'workHours'      => array(
						array(
							'startTime' => '08:00',
							'endTime'   => '17:30',
						),
					),
				),
				'monday'    => array(
					'isWorkingOnDay' => 'OFF',
					'workHours'      => array(
						array(
							'startTime' => '08:00',
							'endTime'   => '17:30',
						),
					),
				),
				'tuesday'   => array(
					'isWorkingOnDay' => 'OFF',
					'workHours'      => array(
						array(
							'startTime' => '08:00',
							'endTime'   => '17:30',
						),
					),
				),
				'wednesday' => array(
					'isWorkingOnDay' => 'OFF',
					'workHours'      => array(
						array(
							'startTime' => '08:00',
							'endTime'   => '17:30',
						),
					),
				),
				'thursday'  => array(
					'isWorkingOnDay' => 'OFF',
					'workHours'      => array(
						array(
							'startTime' => '08:00',
							'endTime'   => '17:30',
						),
					),
				),
				'friday'    => array(
					'isWorkingOnDay' => 'OFF',
					'workHours'      => array(
						array(
							'startTime' => '08:00',
							'endTime'   => '17:30',
						),
					),
				),
				'saturday'  => array(
					'isWorkingOnDay' => 'OFF',
					'workHours'      => array(
						array(
							'startTime' => '08:00',
							'endTime'   => '17:30',
						),
					),
				),
			),
		);
		$buttonStyle = array(
			'type'            => 'round',
			'backgroundColor' => '#2DB742',
			'textColor'       => '#fff',
			'label'           => $waBtnLabel,
			'width'           => 300,
			'height'          => 64,
		);
		if ( empty( $userWAId ) || empty( $post ) ) {
			$waPostType = array(
				'post_status' => 'publish',
				'post_type'   => 'whatsapp-accounts',
			);
			$waId       = wp_insert_post( $waPostType );
			update_user_meta( $vendorId, 'dokan_whatsapp_id', $waId );
			update_post_meta( $waId, 'nta_wa_account_info', $accountInfo );
			update_post_meta( $waId, 'nta_wa_button_styles', $buttonStyle );
			update_post_meta( $waId, 'nta_wa_widget_show', 'OFF' );
			update_post_meta( $waId, 'nta_wa_widget_position', 0 );
			update_post_meta( $waId, 'nta_wa_wc_show', 'OFF' );
			update_post_meta( $waId, 'nta_wa_wc_position', 0 );
		} else {
			update_post_meta( $userWAId, 'nta_wa_account_info', $accountInfo );
			update_post_meta( $userWAId, 'nta_wa_button_styles', $buttonStyle );
		}
		wp_send_json_success();
	}
}
