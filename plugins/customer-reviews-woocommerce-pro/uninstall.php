<?php
/*
	Uninstalling Customer Reviews for WooCommerce Pro
*/

// if uninstall.php is not called by WordPress, die
if( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

$crpro_uninstall_options = array(
	'ivole_form_template',
	'ivole_sending_time',
	'ivole_country_delays',
	'ivole_email_review_reminder',
	'ivole_email_review_reminder_2',
	'ivole_email_review_discount',
	'ivole_unsubscribed_emails',
	'ivole_unsubscribe_page',
	'ivole_stop_reminders',
	'ivole_exclude_emails',
	'ivole_email_subject_2',
	'ivole_email_heading_2',
	'ivole_email_body_2',
	'ivole_email_color_bg_2',
	'ivole_email_color_text_2'
);

foreach ( $crpro_uninstall_options as $uninstall_option ) {
	delete_option( $uninstall_option );
}

wp_cache_flush();
