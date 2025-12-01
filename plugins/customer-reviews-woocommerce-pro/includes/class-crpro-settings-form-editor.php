<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'CRPRO_Form_Editor_Settings' ) ) :

	class CRPRO_Form_Editor_Settings {

		public function __construct() {
			add_action( 'wp_ajax_crpro_fetch_local_form', array( $this, 'fetch_local_form' ) );
			add_action( 'wp_ajax_crpro_fetch_form_translations', array( $this, 'fetch_form_translations' ) );
			add_action( 'wp_ajax_crpro_save_local_form', array( $this, 'save_local_form' ) );
			add_action( 'wp_ajax_crpro_local_form_status', array( $this, 'local_form_status' ) );
			add_action( 'wp_ajax_crpro_enable_local_form', array( $this, 'enable_local_form' ) );
			add_action( 'wp_ajax_crpro_preview_local_form', array( $this, 'preview_local_form' ) );
			add_action( 'wp_ajax_crpro_upload_form_logo', array( $this, 'upload_form_logo' ) );
		}

		public function fetch_local_form() {
			check_ajax_referer( 'crpro-form-editor', 'crpro_nonce' );

			$return = array(
				'languages' => array(),
				'template' => '',
				'shopName' => '',
				'termsUrl' => '',
				'shopUrl' => '',
				'defaultProductImg' => '',
			);

			if( current_user_can( 'manage_options' ) ) {
				if( isset( $_POST['language'] ) && $_POST['language'] ) {
					$language = strtoupper( $_POST['language'] );
					$current_option = get_option( 'ivole_form_template', array() );
					$languages = array();
					$template = '';

					if( isset( $current_option[ 'template' ] ) ) {
						$languages = array_keys( $current_option[ 'template' ] );
						$languages = array_map( 'strtolower', $languages );

						if( isset( $current_option['template'][$language] ) ) {
							$template = $current_option['template'][$language];
						}
					}

					$wc_terms_page = wc_get_page_id( 'terms' );
					if( $wc_terms_page ) {
						$wc_terms_page = get_permalink( $wc_terms_page );
					} else {
						$wc_terms_page = '';
					}

					// support for custom language codes from WPML or other translation plugins
					$additional_languages = array();
					$wpml_languages = apply_filters( 'wpml_active_languages', null );
					if (
						$wpml_languages &&
						is_array( $wpml_languages )
					) {
						$wpml_suffix = ' - WPML';
						foreach ( $wpml_languages as $lang ) {
							if (
								isset( $lang['code'] ) &&
								isset( $lang['native_name'] )
							) {
								$alang = new stdClass();
								$alang->name = $lang['native_name'] . $wpml_suffix;
								$code = $lang['code'];
								if ( class_exists( 'CR_Email_Func' ) ) {
									$code = CR_Email_Func::cr_map_language( $lang['code'] );
								}
								$alang->code = strtolower( $code );
								$additional_languages[] = $alang;
							}
						}
					}

					$return = array(
						'languages' => $languages,
						'template' => $template,
						'shopName' => self::get_shop_name(),
						'termsUrl' => $wc_terms_page,
						'shopUrl' => get_option( 'home' ),
						'defaultProductImg' => plugin_dir_url( dirname( __FILE__ ) ) . 'img/test-product.jpeg',
						'additionalLanguages' => $additional_languages
					);
				}
			}

			wp_send_json( $return );
		}

		public function save_local_form() {
			check_ajax_referer( 'crpro-form-editor', 'crpro_nonce' );

			$return = array( 'return' => 1, 'message' => 'Template could not be updated' );

			if( current_user_can( 'manage_options' ) ) {
				if(
					isset( $_POST['language'] ) &&
					$_POST['language'] &&
					isset( $_POST['template'] ) &&
					$_POST['template']
				) {
					$_POST['language'] = strtoupper( $_POST['language'] );
					$current_option = get_option( 'ivole_form_template', array() );
					if( isset( $current_option['template'] ) ) {
						$current_option['template'][$_POST['language']] = stripslashes( $_POST['template'] );
					} else {
						$current_option['template'] = array(
							$_POST['language'] => stripslashes( $_POST['template'] )
						);
					}
					update_option( 'ivole_form_template', $current_option, false );
					$return = array( 'return' => 0, 'message' => 'Template has been updated' );
				}
			}

			wp_send_json( $return );
		}

		public function fetch_form_translations() {
			check_ajax_referer( 'crpro-form-editor', 'crpro_nonce' );

			$return = array();

			if( current_user_can( 'manage_options' ) ) {
				if( isset( $_POST['language'] ) && $_POST['language'] ) {
					$language = strtoupper( $_POST['language'] );
					$translations_json = file_get_contents( plugin_dir_path( dirname( __FILE__ ) ) . 'misc/form_translations.json' );
					if( $translations_json ) {
						$translations = json_decode( $translations_json );
						if( null !== $translations ) {
							$translation = array_filter( $translations, function( $el ) use( $language ) {
								if( is_object( $el ) && property_exists( $el, 'language' ) ) {
									if( $language === $el->language ) {
										return true;
									}
								}
								return false;
							} );
							// fallback option when no translations are available for the provided language
							if ( 0 === count( $translation ) ) {
								$language = 'EN';
								$translation = array_filter( $translations, function( $el ) use( $language ) {
									if( is_object( $el ) && property_exists( $el, 'language' ) ) {
										if( $language === $el->language ) {
											return true;
										}
									}
									return false;
								} );
							}
							foreach( $translation as $tr ) {
								$return[$tr->element] = $tr->text;
							}
						}
					}
				}
			}

			wp_send_json( $return );
		}

		public function local_form_status() {
			check_ajax_referer( 'crpro-form-editor', 'crpro_nonce' );

			$return = new stdClass();

			if( current_user_can( 'manage_options' ) ) {
				$return->templates = new stdClass();
				$return->templates->{'review-form'} = new stdClass();
				$return->templates->{'review-form'}->enabled = false;

				$current_option = get_option( 'ivole_form_template', array() );
				if( isset( $current_option[ 'enabled' ] ) && $current_option[ 'enabled' ] ) {
					$return->templates->{'review-form'}->enabled = true;
				}
			}

			wp_send_json( $return );
		}

		public function enable_local_form() {
			check_ajax_referer( 'crpro-form-editor', 'crpro_nonce' );

			$return = array( 'return' => -1, 'message' => 'Error' );

			if( current_user_can( 'manage_options' ) ) {
				$current_option = get_option( 'ivole_form_template', array() );
				$enabled = false;
				if( isset( $_POST[ 'enabled' ] ) && 'true' === strtolower( $_POST[ 'enabled' ] ) ) {
					$enabled = true;
				}
				if( is_array( $current_option ) ) {
					$current_option[ 'enabled' ] = $enabled;
				} else {
					$current_option = array(
						'enabled' => $enabled
					);
				}
				update_option( 'ivole_form_template', $current_option, false );
				$return = array( 'return' => 0, 'message' => 'Form was enabled / disabled' );
			}

			wp_send_json( $return );
		}

		public function preview_local_form() {
			check_ajax_referer( 'crpro-form-editor', 'crpro_nonce' );

			$return = array(
				'previewUrl' => ''
			);

			if( current_user_can( 'manage_options' ) ) {
				if( isset( $_POST['template'] ) && $_POST['template'] ) {
					$template = stripslashes( $_POST['template'] );
					$template = json_decode( $template );
					if(
						null !== $template &&
						class_exists( 'CR_Local_Forms' ) &&
						method_exists( 'CR_Local_Forms', 'test_form_for_preview' )
					) {
						$form = CR_Local_Forms::test_form_for_preview( $template );
						if( 0 === $form['code'] ) {
							$return[ 'previewUrl' ] = $form['text'];
						}
					}
				}
			}

			wp_send_json( $return );
		}

		public function upload_form_logo() {
			check_ajax_referer( 'crpro-form-editor', 'crpro_nonce' );

			$return = new stdClass();
			$return->url = '';
			$return->width = 0;
			$return->height = 0;
			$max_size = 400;

			if( current_user_can( 'manage_options' ) ) {
				if( isset( $_FILES ) && is_array( $_FILES ) && 0 < count( $_FILES ) ) {
					// upload the file
					$upload = wp_handle_upload( $_FILES[ 'file' ], array( 'test_form' => false ) );
					if( $upload && ! isset( $upload['error'] ) ) {
						// resize the file
						$img_editor = wp_get_image_editor( $upload[ 'file' ] );
						if( ! is_wp_error( $img_editor ) ) {
							$dm = $img_editor->get_size();
							if ( $dm[ 'width' ] > $max_size || $dm[ 'height' ] > $max_size ) {
								// max width or height should be 400px
								if( $dm[ 'width' ] > $dm[ 'height' ] ) {
									$res = $img_editor->resize( $max_size, null, false );
								} else {
									$res = $img_editor->resize( null, $max_size, false );
								}
							} else {
								$res = $dm;
							}
							// save the resized file using the same name
							if( ! is_wp_error( $res ) ) {
								$res = $img_editor->save( $upload[ 'file' ] );
								if( ! is_wp_error( $res ) ) {
									$return->url = $upload[ 'url' ];
									$return->width = $res[ 'width' ];
									$return->height = $res[ 'height' ];
								}
							}
						}
					}
				}
			}

			wp_send_json( $return );
		}

		public static function get_shop_name() {
			return get_option( 'ivole_shop_name', get_bloginfo( 'name', 'display' ) );
		}

	}

endif;
