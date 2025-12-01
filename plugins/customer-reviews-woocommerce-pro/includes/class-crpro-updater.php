<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'CRPRO_Updater' ) ) :

	class CRPRO_Updater {

		private $plugin_slug;
		private $plugin_plugin;
		private $download_api;
		private $json_link;
		const CACHE_KEY = 'crpro-updater-json-cache';
		const DOWNLOAD_CACHE_KEY = 'crpro-updater-download-cache';
		const DOWNLOAD_ERROR = 'crpro-updater-download-error';

		public function __construct( $args ) {
			$this->plugin_slug = $args['slug'];
			$this->plugin_plugin = $args['plugin'];
			$this->download_api = 'https://api.cusrev.com/v1/production/wp-download/';
			$this->json_link = 'https://www.cusrev.com/download/info.json';
			add_filter( 'plugins_api', array( $this, 'get_info' ), 10, 3 );
			add_filter( 'site_transient_update_plugins', array( $this, 'update' ) );
			add_action( 'upgrader_process_complete', array( $this, 'clear_transient' ), 10, 2 );
			add_filter( 'upgrader_pre_download', array( $this, 'upgrader_license_check' ), 10, 4 );
			add_action( 'cr_register_license_ok', array( $this, 'register_license' ), 10, 1 );
		}

		public function get_info( $result, $action, $args ) {
			if( 'plugin_information' !== $action ) {
				return $result;
			}
			if( $this->plugin_slug !== $args->slug ) {
				return $result;
			}

			$json_info = $this->download_json();
			if( ! $json_info ) {
				return $result;
			}

			$res = new stdClass();

			$res->name = $json_info->name;
			$res->slug = $json_info->slug;
			$res->version = $json_info->version;
			$res->tested = $json_info->tested;
			$res->requires = $json_info->requires;
			$res->author = $json_info->author;
			$res->author_profile = $json_info->author_profile;
			$res->download_link = $json_info->download_url;
			$res->trunk = $json_info->download_url;
			$res->requires_php = $json_info->requires_php;
			$res->last_updated = $json_info->last_updated;

			$res->sections = array(
				'description' => $json_info->sections->description,
				'installation' => $json_info->sections->installation,
				'changelog' => $json_info->sections->changelog
			);

			if( ! empty( $json_info->icons ) ) {
				$res->icons = array(
					'low' => $json_info->icons->low,
					'high' => $json_info->icons->high
				);
			}

			if( ! empty( $json_info->banners ) ) {
				$res->banners = array(
					'low' => $json_info->banners->low,
					'high' => $json_info->banners->high
				);
			}

			return $res;
		}

		public function download_json() {
			$remote = get_transient( self::CACHE_KEY );

			if( false === $remote ) {
				$remote = wp_remote_get(
					$this->json_link,
					array(
						'timeout' => 10,
						'headers' => array(
							'Accept' => 'application/json'
						)
					)
				);

				if(
					is_wp_error( $remote )
					|| 200 !== wp_remote_retrieve_response_code( $remote )
					|| empty( wp_remote_retrieve_body( $remote ) )
				) {
					return false;
				}

				set_transient( self::CACHE_KEY, $remote, DAY_IN_SECONDS );
			}

			$remote = json_decode( wp_remote_retrieve_body( $remote ) );

			return $remote;
		}

		public function update( $transient ) {
			// if ( empty($transient->checked ) ) {
			// 	return $transient;
			// }

			if ( ! $transient ) {
				return $transient;
			}

			$remote = $this->download_json();

			$res = new stdClass();
			$res->slug = $this->plugin_slug;
			$res->plugin = $this->plugin_plugin;
			if( $remote ) {
				$res->icons = array(
					"1x" => $remote->icons->low,
					"2x" => $remote->icons->high
				);
				$res->banners = array(
					"high" => $remote->banners->high,
					"low" => $remote->banners->low
				);
			}

			if(
				$remote
				&& version_compare( CRPRO::CRPRO_VERSION, $remote->version, '<' )
				&& version_compare( $remote->requires, get_bloginfo( 'version' ), '<=' )
				&& version_compare( $remote->requires_php, PHP_VERSION, '<' )
			) {
				$res->new_version = $remote->version;
				$res->tested = $remote->tested;
				$res->package = $this->get_download_link();
				$transient->response[ $this->plugin_plugin ] = $res;
			} else {
				$res->new_version = CRPRO::CRPRO_VERSION;
				$res->tested = '';
				$res->package = '';
				$transient->no_update[ $this->plugin_plugin ] = $res;
			}

			return $transient;
		}

		public function clear_transient( $upgrader, $hook_extra ) {
			if( 'update' === $hook_extra['action'] && 'plugin' === $hook_extra[ 'type' ] ) {
				delete_transient( self::CACHE_KEY );
				delete_transient( self::DOWNLOAD_CACHE_KEY );
				delete_transient( self::DOWNLOAD_ERROR );
			}
		}

		private function get_download_link() {
			$download_link = get_transient( self::DOWNLOAD_CACHE_KEY );

			if( false === $download_link ) {
				$error_download = get_transient( self::DOWNLOAD_ERROR );
				if ( $error_download ) {
					$download_link = '';
					return $download_link;
				}
				$license_key = trim( get_option( 'ivole_license_key', '' ) );
				$download_res = wp_remote_get(
					$this->download_api . $license_key,
					array(
						'timeout' => 10,
						'headers' => array(
							'Accept' => 'application/json'
						)
					)
				);

				if(
					is_wp_error( $download_res )
					|| 200 !== wp_remote_retrieve_response_code( $download_res )
					|| empty( wp_remote_retrieve_body( $download_res ) )
				) {
					if ( 400 === wp_remote_retrieve_response_code( $download_res ) ) {
						set_transient( self::DOWNLOAD_ERROR, $license_key, HOUR_IN_SECONDS * 3 );
					}
					$download_link = '';
					return $download_link;
				}

				$download_res = json_decode( wp_remote_retrieve_body( $download_res ) );
				if( 'ok' === $download_res->status ) {
					$download_link = $download_res->downloadUrl;
					set_transient( self::DOWNLOAD_CACHE_KEY, $download_link, HOUR_IN_SECONDS * 3 );
				} else {
					$download_link = '';
				}
			}

			return $download_link;
		}

		public function upgrader_license_check( $reply, $package, $upgrader, $hook_extra ) {
			if (
				$hook_extra &&
				is_array( $hook_extra ) &&
				isset( $hook_extra['plugin'] ) &&
				$this->plugin_plugin === $hook_extra['plugin'] &&
				empty( $package )
			) {
				$reply = new WP_Error( 'crpro_no_package', __( 'Update package not available. Please check the license key.', 'customer-reviews-woocommerce-pro' ) );
			}
			return $reply;
		}

		public function register_license( $result ) {
			delete_transient( self::DOWNLOAD_ERROR );
		}

	}

endif;
