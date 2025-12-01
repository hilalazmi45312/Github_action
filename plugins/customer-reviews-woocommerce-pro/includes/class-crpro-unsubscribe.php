<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'CRPRO_Unsubscribe' ) ) :

	class CRPRO_Unsubscribe {

		public function __construct() {
			add_shortcode( 'cusrev_unsubscribe', array( $this, 'display_shortcode' ) );
			add_action( 'wp_ajax_crpro_unsubscribe', array( $this, 'unsubscribe_callback' ) );
			add_action( 'wp_ajax_nopriv_crpro_unsubscribe', array( $this, 'unsubscribe_callback' ) );
		}

		public function display_shortcode( $atts, $content, $shortcode_tag ) {
			$crpro_unsubscribe_email = '';
			$crpro_unsubscribe_test = false;
			if( isset( $_GET['cr-unsubscribe'] ) && $_GET['cr-unsubscribe'] ) {
				if( filter_var( $_GET['cr-unsubscribe'], FILTER_VALIDATE_EMAIL ) ) {
					$crpro_unsubscribe_email = $_GET['cr-unsubscribe'];
				}
			}
			if( isset( $_GET['cr-test'] ) && $_GET['cr-test'] ) {
				$crpro_unsubscribe_test = true;
			}
			$template = wc_locate_template(
				'unsubscribe.php',
				'customer-reviews-woocommerce-pro',
				__DIR__ . '/../templates/'
			);
			ob_start();
			include( $template );
			$output = ob_get_clean();
			$output = $output . $this->inline_script( $crpro_unsubscribe_test );
			return $output;
		}

		private function inline_script( $is_test ) {
			$script  = '<script>';
			$script .= '( function() {';
			$script .= 'jQuery(document).ready( function() {';

			// unsubscribe button click
			$script .= 'jQuery(".crpro-unsubscribe-button").on( "click", function( e ) {';
			$script .= 'jQuery(this).closest(".crpro-unsubscribe-cont").find(".crpro-unsubscribe-success").css("visibility", "hidden");';
			$script .= 'jQuery(this).closest(".crpro-unsubscribe-cont").find(".crpro-unsubscribe-success").show();';
			$script .= 'jQuery(this).closest(".crpro-unsubscribe-cont").find(".crpro-unsubscribe-error").hide();';
			$script .= 'jQuery(this).text( "' . __( 'Processing...', 'customer-reviews-woocommerce-pro' ) . '" );';
			$script .= 'jQuery(this).prop( "disabled", true );';
			$script .= 'let crproData = {';
			$script .= '"action": "crpro_unsubscribe",';
			$script .= '"email": jQuery(this).closest(".crpro-unsubscribe-cont").find(".crpro-unsubscribe-email").val(),';
			if( $is_test ) {
				$script .= '"test": "yes",';
			} else {
				$script .= '"test": "no",';
			}
			$script .= '"_ajax_nonce": jQuery(this).data("nonce")';
			$script .= '};';
			$script .= 'jQuery.post( {';
			$script .= 'url: "' . admin_url( 'admin-ajax.php' ) . '",';
			$script .= 'data: crproData,';
			$script .= 'context: this,';
			$script .= 'dataType: "json",';
			$script .= 'success: function( response ) {';
			$script .= 'jQuery(this).prop( "disabled", false );';
			$script .= 'jQuery(this).text( "' . __( 'Unsubscribe', 'customer-reviews-woocommerce-pro' ) . '" );';
			$script .= 'if( 0 === response ) {';
			$script .= 'jQuery(this).closest(".crpro-unsubscribe-cont").find(".crpro-unsubscribe-success").show();';
			$script .= 'jQuery(this).closest(".crpro-unsubscribe-cont").find(".crpro-unsubscribe-success").css("visibility", "visible");';
			$script .= '} else {';
			$script .= 'jQuery(this).closest(".crpro-unsubscribe-cont").find(".crpro-unsubscribe-success").hide();';
			$script .= 'jQuery(this).closest(".crpro-unsubscribe-cont").find(".crpro-unsubscribe-error").show();';
			$script .= '}';
			$script .= '}';
			$script .= '} );';
			$script .= '} );';

			$script .= '} );';
			$script .= '} () );';
			$script .= '</script>';
			return $script;
		}

		public function unsubscribe_callback() {
			check_ajax_referer( 'crprounsubscribe' );
			if( isset( $_POST['email'] ) && 0 < strlen( trim( $_POST['email'] ) ) ) {
				if( isset( $_POST['test'] ) && 'yes' === $_POST['test'] ) {
					wp_send_json( 0 );
				} else {
					$email = mb_strtolower( trim( $_POST['email'] ) );
					$unsubscribe_list = get_option( 'ivole_unsubscribed_emails', array() );
					if( in_array( $email, $unsubscribe_list ) ) {
						wp_send_json( 0 );
					} else {
						$unsubscribe_list[] = $email;
						update_option( 'ivole_unsubscribed_emails', $unsubscribe_list, false );
						wp_send_json( 0 );
					}
				}
			}
			wp_send_json( -1 );
		}

	}

endif;
