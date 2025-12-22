<?php
namespace FileBird\Classes;

use FileBird\Admin\Settings;
use FileBird\Classes\Helpers;

defined( 'ABSPATH' ) || exit;

class ActivePro {
	private $envato_login_url     = 'https://active.ninjateam.org/envato-login/';
	private $check_purchase_url   = 'https://active.ninjateam.org/wp-admin/admin-ajax.php?action=njt_validate_code';
	private $deactivate_code_url  = 'https://active.ninjateam.org/wp-admin/admin-ajax.php?action=njt_deactivate_code';
	private $license_old_site_url = 'https://active.ninjateam.org/wp-admin/admin-ajax.php?action=njt_license_old_site';

	private $update_checker;

	const FB_SLUG = 'filebird_pro';

	public function __construct() {
		add_filter( 'fbv_data', array( $this, 'localize_fbv_data' ) );
		add_action( 'wp_ajax_fb_login_envato_success', array( $this, 'ajax_login_envato_success' ) );
		add_action( 'wp_ajax_fbv_deactivate_license', array( $this, 'ajax_fbv_deactivate_license' ) );

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}

		add_action( 'init', array( $this, 'plugin_updater' ) );
		add_action( 'in_plugin_update_message-' . plugin_basename( NJFB_PLUGIN_FILE ), array( $this, 'in_plugin_update_message' ), 10, 2 );
		add_action( 'rest_api_init', array( $this, 'registerRestFields' ) );
	}

	public function plugin_updater() {
		$doing_cron = defined( 'DOING_CRON' ) && DOING_CRON;
		if ( ! current_user_can( 'manage_options' ) && ! $doing_cron ) {
			return;
		}
		$fb_token = '';
		$api_data = array(
			'version' => NJFB_VERSION,
			'slug'    => self::FB_SLUG,
			'license' => '',
			'author'  => 'FileBird',
			'beta'    => false,
		);

		if ( Helpers::isActivated() ) {
			$arr_token = array(
				'code'   => get_option( 'filebird_code', '' ),
				'email'  => get_option( 'filebird_email', '' ),
				'domain' => $this->get_domain(),
			);
			$fb_token  = base64_encode( http_build_query( $arr_token ) );

			$api_data['license'] = $arr_token['code'];
		}
		$this->update_checker = new PluginUpdater(
			'https://active.ninjateam.org/',
			NJFB_PLUGIN_FILE,
			$api_data,
			$fb_token
		);
	}

	public function registerRestFields() {
		register_rest_route(
			NJFB_REST_URL,
			'license-old-site',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'restGetLicenseOldSite' ),
				'permission_callback' => array( $this, 'resPermissionsCheck' ),
			)
		);
	}
	public function restGetLicenseOldSite() {
		$code  = get_option( 'filebird_code', '' );
		$email = get_option( 'filebird_email', '' );

		$site = '';

		if ( ! empty( $code ) && ! empty( $email ) ) {
			$response = wp_remote_post(
				add_query_arg( array(), $this->license_old_site_url ),
				array(
					'method'      => 'POST',
					'timeout'     => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking'    => true,
					'headers'     => array(),
					'body'        => array(
						'code'  => $code,
						'email' => $email,
					),
				)
			);
			if ( ! is_wp_error( $response ) ) {
				$json = json_decode( $response['body'], true );
				if ( is_array( $json ) && isset( $json['success'] ) && $json['success'] == true ) {
					$site = $json['data']['site'];
				}
			}
		}

		wp_send_json_success(
             array(
				 'site' => $site,
			 )
            );
	}
	public function resPermissionsCheck() {
		return current_user_can( 'upload_files' );
   	}
	public function in_plugin_update_message( $plugin_data, $version_info ) {
		if ( ! Helpers::isActivated() ) {
			if ( ! is_multisite() || is_main_site() ) {
				echo '&nbsp;<strong><a href="' . admin_url( 'admin.php?page=' . Settings::SETTING_PAGE_SLUG ) . '">' . esc_html__( 'Activate your license for automatic updates', 'filebird' ) . '</a></strong>.';
			}
		}
	}
	public function ajax_login_envato_success() {
		$check_nonce = check_ajax_referer( 'njt_filebird_login_envato', 'nonce', false );
		if ( $check_nonce === false ) {
			exit( esc_html__( 'Validation failed (Nonce Errors), please try again later. Or you can <a href="https://ninjateam.org/support" target="_blank"><strong>contact support</strong></a>.', 'filebird' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array( 'mess' => __( 'You do not have permission to perform this action.', 'filebird' ) ),
				403
			);
		}
		
		$purchase_code   = isset( $_GET['code'] ) ? sanitize_text_field( $_GET['code'] ) : '';
		$email           = isset( $_GET['email'] ) ? sanitize_text_field( $_GET['email'] ) : '';
		$success         = isset( $_GET['success'] ) ? sanitize_text_field( $_GET['success'] ) : '';
		$error           = isset( $_GET['error'] ) ? sanitize_text_field( $_GET['error'] ) : '';
		$old_domain      = isset( $_GET['old_domain'] ) ? sanitize_text_field( $_GET['old_domain'] ) : '';
		$is_dev_or_local = isset( $_GET['is_dev_or_local'] ) ? intval( $_GET['is_dev_or_local'] ) : 0;

		$email = str_replace( ' ', '+', $email );

		if ( $success == true ) {
			if ( $is_dev_or_local != 1 ) {
				$final_check = $this->remote_check_purchase_code( $purchase_code, $email );
			} else {
				$final_check = array(
					'success' => true,
					'data'    => array(
						'code'  => $purchase_code,
						'email' => $email,
					),
				);
			}

			if ( $final_check['success'] ) {
				foreach ( $final_check['data'] as $k => $v ) {
					update_option( 'filebird_' . $k, $v );
				}
				$this->clear_update_plugins_cache();
			}
		} else {
			update_option( 'filebird_activation_error', $error );
			update_option( 'filebird_activation_old_domain', $old_domain );
		}
		exit( '<script>window.close()</script>' );
	}
	public function ajax_fbv_deactivate_license() {
		check_ajax_referer( 'deactivate_license_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array( 'mess' => __( 'You do not have permission to perform this action.', 'filebird' ) ),
				403
			);
		}
		wp_remote_get(
            add_query_arg(
             array(
				 'code'   => get_option( 'filebird_code' ),
				 'email'  => get_option( 'filebird_email' ),
				 'domain' => $this->get_domain(),
			 ),
            $this->deactivate_code_url
            )
            );

		update_option( 'filebird_code', '' );
		update_option( 'filebird_email', '' );

		$this->clear_update_plugins_cache();

		wp_send_json_success();
		exit;
	}
	public function injectUpdate( $update ) {
		if ( Helpers::isActivated() ) {
			$arr      = array(
				'code'   => get_option( 'filebird_code', '' ),
				'email'  => get_option( 'filebird_email', '' ),
				'domain' => $this->get_domain(),
			);
			$fb_token = base64_encode( http_build_query( $arr ) );

			$update->download_url = add_query_arg(
				array(
					'fb_token' => $fb_token,
				),
				$update->download_url
			);
		} else {
			$update->download_url = null;
		}
		return $update;
	}
	private function clear_update_plugins_cache() {
		global $wpdb;
		$current               = get_site_transient( 'update_plugins' );
		$current->last_checked = strtotime( '-24 hours', time() );
		set_site_transient( 'update_plugins', $current );

		$wpdb->query( 'DELETE FROM ' . $wpdb->options . " WHERE option_name like 'filebird_pro_sl_%'" );
	}
	private function remote_check_purchase_code( $code, $email, $plugin = 'filebird' ) {
		$domain   = $this->get_domain();
		$response = wp_remote_post(
			add_query_arg( array(), $this->check_purchase_url ),
			array(
				'method'      => 'POST',
				'timeout'     => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking'    => true,
				'headers'     => array(),
				'body'        => array(
					'code'   => $code,
					'email'  => $email,
					'domain' => $domain,
					'plugin' => $plugin,
				),
			)
		);
		if ( ! is_wp_error( $response ) ) {
			$json = json_decode( $response['body'] );
			if ( $json->success ) {
				return array(
					'success' => true,
					'data'    => array(
						'code'            => $json->data->code,
						'email'           => $json->data->email,
						'supported_until' => $json->data->supported_until,
					),
				);
			}
			return array(
				'success' => false,
			);
		}
		return array(
			'success' => false,
		);
	}

	private function isSettingPage() {
		if ( function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			if ( is_object( $screen ) && $screen->base == Settings::getInstance()->getSettingHookSuffix() ) {
				return true;
			}
		}

		return false;
	}

	public function localize_fbv_data( $data ) {
		$return_args = array(
			'action' => 'fb_login_envato_success',
			'nonce'  => wp_create_nonce( 'njt_filebird_login_envato' ),
		);

		$return_url               = add_query_arg( $return_args, admin_url( 'admin-ajax.php' ) );
		$domain                   = $this->get_domain();
		$data['login_envato_url'] = esc_url(
			add_query_arg(
				array(
					'domain'     => $domain,
					'plugin'     => 'filebird',
					'return_url' => urlencode( $return_url ),
					'ip'         => Helpers::getIp(),
				),
				$this->envato_login_url
			)
		);

		$data['license']['status'] = Helpers::isActivated();

		if ( Helpers::isActivated() && $this->isSettingPage() ) {
			$data['license']['key'] = esc_html( get_option( 'filebird_code', '' ) );
		}

		$data['deactivate_license_nonce'] = wp_create_nonce( 'deactivate_license_nonce' );
		if ( ! isset( $data['i18n'] ) ) {
			$data['i18n'] = array();
		}
		$data['i18n']['active_to_update']                   = esc_html__( 'Please activate FileBird license to use this feature.', 'filebird' );
		$data['i18n']['deactivate_license_confirm_title']   = esc_html__( 'Deactivating license', 'filebird' );
		$data['i18n']['deactivate_license_confirm_content'] = esc_html__( 'Are you sure to deactivate the current license key? You will not get regular updates or any support for this site.', 'filebird' );
		$data['i18n']['deactivate_license_try_again']       = esc_html__( 'Please try again later!', 'filebird' );
		$data['i18n']['update_error']                       = esc_html__( 'Update failed: Your current FileBird license is being used on another site: {site}. To get this update, please deactivate the license on the other site first.', 'filebird' );
		return $data;
	}
	public static function renderHtml() {
		$str = '';

		$filebird_activation_error = get_option( 'filebird_activation_error', '' );
		if ( $filebird_activation_error != '' ) {
			update_option( 'filebird_activation_error', '' );
		}

		$filebird_activation_old_domain = get_option( 'filebird_activation_old_domain', '' );
		if ( $filebird_activation_old_domain != '' ) {
			update_option( 'filebird_activation_old_domain', '' );
		}

		$str .= Helpers::view(
             'particle/activation_fail',
			array(
				'filebird_activation_error'      => $filebird_activation_error,
				'filebird_activation_old_domain' => $filebird_activation_old_domain,
			)
		);
		if ( ! Helpers::isActivated() ) {
			$str .= Helpers::view( 'pages/settings/tab-active' );
		} else {
			$str .= Helpers::view( 'pages/settings/tab-activated' );
		}

		return $str;
	}
	private function get_domain() {
		return Helpers::getDomain();
	}
}