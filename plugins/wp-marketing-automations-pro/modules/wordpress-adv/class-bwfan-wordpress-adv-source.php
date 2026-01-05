<?php

class BWFAN_WP_Adv_Source {
	private static $instance = null;

	public function __construct() {
		add_action( 'bwfan_wp_source_loaded', [ $this, 'include_event_files' ] );
	}

	/**
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @return BWFAN_WP_Adv_Source|null
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function include_event_files( $wp_source ) {
		$resource_dir = __DIR__ . '/events';
		if ( false === @file_exists( $resource_dir ) ) { //phpcs:ignore PHP_CodeSniffer - Generic.PHP.NoSilencedErrors, Generic.PHP.NoSilencedErrors
			return;
		}

		foreach ( glob( $resource_dir . '/class-*.php' ) as $_field_filename ) {
			$file_data = pathinfo( $_field_filename );
			if ( isset( $file_data['basename'] ) && 'index.php' === $file_data['basename'] ) {
				continue;
			}
			$event_class = require_once( $_field_filename );
			if ( ! is_string( $event_class ) || ! method_exists( $event_class, 'get_instance' ) ) {
				continue;
			}

			/** @var $event_obj BWFAN_Event */
			$event_obj = $event_class::get_instance();

			BWFAN_Load_Sources::$all_events[ $wp_source->get_name() ][ $event_obj->get_slug() ] = $event_obj->get_name();

			$event_obj->load_hooks();
			$event_obj->set_source_type( $wp_source->get_slug() );
			BWFAN_Load_Sources::register_events( $event_obj );
		}

	}
}

BWFAN_WP_Adv_Source::get_instance();
