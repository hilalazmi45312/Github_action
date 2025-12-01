<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once( 'class-crpro-local-forms.php' );
require_once( 'class-crpro-local-emails.php' );
require_once( 'class-crpro-unsubscribe.php' );
require_once( 'class-crpro-updater.php' );
require_once( 'class-crpro-settings-review-reminder.php' );
require_once( 'class-crpro-settings-forms.php' );
require_once( 'class-crpro-settings-emails.php' );
require_once( 'class-crpro-settings-messages.php' );
require_once( 'class-crpro-sender.php' );
require_once( 'class-crpro-settings-form-editor.php' );
require_once( 'class-crpro-settings-email-editor.php' );

if ( ! class_exists( 'CRPRO' ) ) :

	class CRPRO {
		const CRPRO_VERSION = '1.12.4';
		private $args;

		public function __construct( $args ) {
			$this->args = $args;
			if ( is_admin() ) {
				new CRPRO_Updater( $this->args );
				new CRPRO_Review_Reminder_Settings();
				new CRPRO_Forms_Settings();
				new CRPRO_Emails_Settings();
				new CRPRO_Messages_Settings();
				new CRPRO_Form_Editor_Settings();
				new CRPRO_Email_Editor_Settings();
				$this->add_plugin_row_meta();
			}
			new CRPRO_Sender();
			new CRPRO_Local_Emails();
			new CRPRO_Unsubscribe();
		}

		private function add_plugin_row_meta() {
			add_filter( 'plugin_action_links', array( $this, 'plugin_action_links' ), 10, 4 );
			add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
		}

		public function plugin_action_links( $actions, $plugin_file, $plugin_data, $context ) {
			if ( $this->args['plugin'] !== $plugin_file ) {
				return $actions;
			}
			$action_links = array(
				'settings' => '<a href="' . admin_url( 'admin.php?page=cr-reviews-settings' ) . '" aria-label="' . esc_attr__( 'View Customer Reviews settings', 'customer-reviews-woocommerce-pro' ) . '">' . esc_html__( 'Settings', 'customer-reviews-woocommerce-pro' ) . '</a>',
			);
			return array_merge( $action_links, $actions );
		}

		public function plugin_row_meta( $links, $file ) {
			if ( $this->args['plugin'] !== $file ) {
				return $links;
			}
			$row_meta = array(
				'docs'    => '<a href="' . esc_url( 'https://help.cusrev.com/' ) . '" aria-label="' . esc_attr__( 'View CusRev documentation', 'customer-reviews-woocommerce-pro' ) . '">' . esc_html__( 'Docs', 'customer-reviews-woocommerce-pro' ) . '</a>',
				'support' => '<a href="' . esc_url( 'https://help.cusrev.com/support/tickets/new' ) . '" aria-label="' . esc_attr__( 'Submit a ticket', 'customer-reviews-woocommerce-pro' ) . '">' . esc_html__( 'Dedicated support', 'customer-reviews-woocommerce-pro' ) . '</a>',
			);
			return array_merge( $links, $row_meta );
		}

		public static function deactivation_cleanup() {
			$crons = _get_cron_array();
			foreach ( $crons as $timestamp => $hooks ) {
				if ( isset( $hooks['ivole_send_reminder'] ) ) {
					foreach ( $hooks['ivole_send_reminder'] as $hash => $event ) {
						if ( 1 < count( $event['args'] ) ) {
							wp_clear_scheduled_hook( 'ivole_send_reminder', $event['args'] );
						}
					}
				}
			}
		}
	}

endif;
