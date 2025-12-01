<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'CRPRO_Email_Editor_Settings' ) ) :

	class CRPRO_Email_Editor_Settings {

		public function __construct() {
			add_action( 'wp_ajax_crpro_fetch_email_template', array( $this, 'fetch_email_template' ) );
			add_action( 'wp_ajax_crpro_save_email_template', array( $this, 'save_email_template' ) );
			add_action( 'wp_ajax_crpro_email_template_status', array( $this, 'email_template_status' ) );
			add_action( 'wp_ajax_crpro_enable_email_template', array( $this, 'enable_email_template' ) );
			add_action( 'wp_ajax_crpro_upload_email_image', array( $this, 'upload_email_image' ) );
		}

		public function fetch_email_template() {
			check_ajax_referer( 'crpro-email-editor', 'crpro_nonce' );

			$return = array(
				'languages' => array(),
				'template' => '',
				'subject' => '',
				'shopName' => '',
				'unsubscribeTestUrl' => '',
				'testProductImg' => ''
			);

			if( current_user_can( 'manage_options' ) ) {
				$unsubscribe_url = '';
				if(
					isset( $_POST['language'] ) &&
					$_POST['language'] &&
					isset( $_POST['type'] ) &&
					$_POST['type']
				) {
					$language = strtoupper( $_POST['language'] );
					$settings_table = self::get_settings_table( $_POST['type'] );
					if( $settings_table ) {
						$template = '';
						$subject = '';
						$current_option = get_option( $settings_table, array() );
						$languages = array();
						if( isset( $current_option[ 'email' ] ) ) {
							$languages = array_keys( $current_option[ 'email' ] );
							$languages = array_map( 'strtolower', $languages );

							if(
								isset( $current_option['email'][$language] ) &&
								isset( $current_option['email'][$language]['template'] )
							) {
								$template = str_replace( "\r\n", "\n", $current_option['email'][$language]['template'] );
							}

							if(
								isset( $current_option['email'][$language] ) &&
								isset( $current_option['email'][$language]['subject'] )
							) {
								$subject = $current_option['email'][$language]['subject'];
							}

							$unsubscribe_page = get_option( 'ivole_unsubscribe_page', '' );
							if( $unsubscribe_page ) {
								$unsubscribe_url = get_permalink( $unsubscribe_page );
								$query_args = array(
									'cr-test' => 'yes'
								);
								$unsubscribe_url = esc_url( add_query_arg( $query_args, $unsubscribe_url ) );
							}
						}

						$return = array(
							'languages' => $languages,
							'template' => $template,
							'subject' => $subject,
							'shopName' => CRPRO_Form_Editor_Settings::get_shop_name(),
							'unsubscribeTestUrl' => $unsubscribe_url,
							'testProductImg' => plugin_dir_url( dirname( __FILE__ ) ) . 'img/test-product.jpeg'
						);
					}
				}
			}

			wp_send_json( $return );
		}

		public function save_email_template() {
			check_ajax_referer( 'crpro-email-editor', 'crpro_nonce' );

			$return = array( 'return' => 1, 'message' => 'Template could not be updated' );

			if( current_user_can( 'manage_options' ) ) {
				if(
					isset( $_POST['type'] ) &&
					$_POST['type'] &&
					isset( $_POST['language'] ) &&
					$_POST['language'] &&
					isset( $_POST['enabled'] ) &&
					isset( $_POST['subject'] ) &&
					$_POST['subject'] &&
					isset( $_POST['template'] ) &&
					$_POST['template'] &&
					isset( $_POST['templateInline'] ) &&
					$_POST['templateInline']
				) {
					$settings_table = self::get_settings_table( $_POST['type'] );
					if( $settings_table ) {
						$_POST['language'] = strtoupper( $_POST['language'] );
						$current_option = get_option( $settings_table, array() );
						$current_option['enabled'] = $_POST['enabled'] ? true : false;
						if( isset( $current_option['email'] ) ) {
							$current_option['email'][$_POST['language']] = array(
								'subject' => stripslashes( $_POST['subject'] ),
								'template' => stripslashes( $_POST['template'] ),
								'templateInline' => stripslashes( $_POST['templateInline'] )
							);
						} else {
							$current_option['email'] = array(
								$_POST['language'] => array(
									'subject' => stripslashes( $_POST['subject'] ),
									'template' => stripslashes( $_POST['template'] ),
									'templateInline' => stripslashes( $_POST['templateInline'] )
								)
							);
						}
						update_option( $settings_table, $current_option, false );
						$return = array( 'return' => 0, 'message' => 'Template has been updated' );
					}
				}
			}

			wp_send_json( $return );
		}

		public function email_template_status() {
			check_ajax_referer( 'crpro-email-editor', 'crpro_nonce' );

			$return = new stdClass();

			if( current_user_can( 'manage_options' ) ) {
				$return->templates = new stdClass();
				$return->templates->{'review-reminder'} = new stdClass();
				$return->templates->{'review-reminder'}->enabled = false;
				$return->templates->{'review-reminder-2'} = new stdClass();
				$return->templates->{'review-reminder-2'}->enabled = false;
				$return->templates->{'coupon-after-review'} = new stdClass();
				$return->templates->{'coupon-after-review'}->enabled = false;

				$current_option = get_option( self::get_settings_table( 'review-reminder' ), array() );
				if( isset( $current_option[ 'enabled' ] ) && $current_option[ 'enabled' ] ) {
					$return->templates->{'review-reminder'}->enabled = true;
				}
				$current_option = get_option( self::get_settings_table( 'review-reminder-2' ), array() );
				if( isset( $current_option[ 'enabled' ] ) && $current_option[ 'enabled' ] ) {
					$return->templates->{'review-reminder-2'}->enabled = true;
				}
				$current_option = get_option( self::get_settings_table( 'coupon-after-review' ), array() );
				if( isset( $current_option[ 'enabled' ] ) && $current_option[ 'enabled' ] ) {
					$return->templates->{'coupon-after-review'}->enabled = true;
				}
			}

			wp_send_json( $return );
		}

		public function enable_email_template() {
			check_ajax_referer( 'crpro-email-editor', 'crpro_nonce' );

			$return = array( 'return' => -1, 'message' => 'Error' );

			if( current_user_can( 'manage_options' ) ) {
				if(
					isset( $_POST[ 'enabled' ] ) &&
					isset( $_POST[ 'type' ] ) &&
					$_POST[ 'type' ]
				) {
					$settings_table = self::get_settings_table( $_POST['type'] );
					$current_option = get_option( $settings_table, array() );

					$enabled = false;
					if( 'true' === strtolower( $_POST[ 'enabled' ] ) ) {
						$enabled = true;
					}

					if( is_array( $current_option ) ) {
						$current_option[ 'enabled' ] = $enabled;
					} else {
						$current_option = array(
							'enabled' => $enabled
						);
					}
					update_option( $settings_table, $current_option, false );
					$return = array( 'return' => 0, 'message' => 'Email template was enabled / disabled' );
				}
			}

			wp_send_json( $return );
		}

		public function upload_email_image() {
			check_ajax_referer( 'crpro-email-editor', 'crpro_nonce' );

			$return = array( 'location' => '' );

			if( current_user_can( 'manage_options' ) ) {
				if( isset( $_FILES ) && is_array( $_FILES ) && 0 < count( $_FILES ) ) {
					// upload the file
					$upload = wp_handle_upload( $_FILES[ 'file' ], array( 'test_form' => false ) );
					if( $upload && ! isset( $upload['error'] ) ) {
						// resize the file
						$img_editor = wp_get_image_editor( $upload[ 'file' ] );
						if( ! is_wp_error( $img_editor ) ) {
							$dm = $img_editor->get_size();
							// max width or height should be 1200px
							$max_dimension = 1200;
							$res = 0;
							if( $dm[ 'width' ] > $dm[ 'height' ] && $max_dimension < $dm[ 'width' ] ) {
								$res = $img_editor->resize( 200, null, false );
							} elseif ( $dm[ 'width' ] < $dm[ 'height' ] && $max_dimension < $dm[ 'height' ] ) {
								$res = $img_editor->resize( null, 200, false );
							}
							// save the resized file using the same name
							if( ! is_wp_error( $res ) ) {
								$res = $img_editor->save( $upload[ 'file' ] );
								if( ! is_wp_error( $res ) ) {
									$return['location'] = $upload[ 'url' ];
								}
							}
						}
					}
				}
			}

			wp_send_json( $return );
		}

		private static function get_settings_table( $type ) {
			$settings_table = '';
			switch( $type ) {
				case 'review-reminder':
					$settings_table = 'ivole_email_review_reminder';
					break;
				case 'review-reminder-2':
					$settings_table = 'ivole_email_review_reminder_2';
					break;
				case 'coupon-after-review':
					$settings_table = 'ivole_email_review_discount';
					break;
				default:
					break;
			}
			return $settings_table;
		}

	}

endif;
