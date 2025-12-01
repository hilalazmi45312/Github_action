<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class BWFAN_Contact_Confirmation {
	public static function maybe_create_confirmation_page() {
		$settings = get_option( 'bwfan_global_settings', array() );
		if ( is_array( $settings ) && isset( $setings['bwfan_contact_confirmation_page'] ) ) {
			$post = get_post( absint( $settings['bwfan_contact_confirmation_page'] ) );
			if ( $post instanceof WP_Post && absint( $post->ID ) > 0 ) {
				return;
			}
		}

		$new_page = wp_insert_post( array(
			'post_title'   => __( "Confirmation", 'wp-marketing-automations-pro' ),
			'post_content' => "Thanks for subscribing.",
			'post_status'  => 'publish',
			'post_type'    => 'page',
		) );

		if ( empty( $new_page ) || is_wp_error( $new_page ) ) {
			$message = empty( $new_page ) ? 'Unable to create new Confirmation page' : $new_page->get_error_message();
			BWFAN_Core()->logger->log( $message, 'contact_confirmation' );
		}

		$settings                                    = ! is_array( $settings ) ? [] : $settings;
		$settings['bwfan_contact_confirmation_page'] = $new_page;
		update_option( 'bwfan_global_settings', $settings );
	}
}