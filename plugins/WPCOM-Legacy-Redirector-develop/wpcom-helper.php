<?php
/**
 * Amendments for when this plugin runs on WordPress.com.
 *
 * @package Automattic\LegacyRedirector
 */

// Do not allow inserts to be enabled on the front end on WordPress.com.
add_filter( 'wpcom_legacy_redirector_allow_insert', '__return_false', 9999 );
