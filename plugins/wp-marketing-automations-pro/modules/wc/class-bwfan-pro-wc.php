<?php

final class BWFAN_PRO_WC {

	private static $instance = null;
	private $action_dir = __DIR__;

	/**
	 * Constructor
	 *
	 * @access public
	 */
	private function __construct() {
		add_action( 'bwfan_wc_actions_loaded', [ $this, 'load_actions' ] );
		add_action( 'bwfan_wc_events_loaded', [ $this, 'load_events' ] );
	}

	/**
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @return BWFAN_PRO_WC
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @param $integration BWFAN_Integration
	 */
	public function load_actions( $integration ) {
		$resource_dir = $this->action_dir . '/actions';

		if ( file_exists( $resource_dir ) ) {
			foreach ( glob( $resource_dir . '/class-*.php' ) as $_field_filename ) {
				$file_data = pathinfo( $_field_filename );
				if ( isset( $file_data['basename'] ) && 'index.php' === $file_data['basename'] ) {
					continue;
				}
				$action_class = require_once( $_field_filename );
				if ( ! is_null( $action_class ) && method_exists( $action_class, 'get_instance' ) ) {
					/**
					 * @var $action_obj BWFAN_Action
					 */
					$action_obj = $action_class::get_instance();
					$action_obj->load_hooks();
					if ( method_exists( $action_obj, 'admin_enqueue_assets' ) && is_admin() && BWFAN_Common::is_automation_v1_active() && BWFAN_Common::is_autonami_page() ) {
						// Add action to avoid enqueueing assets on every admin page load
						add_action( 'admin_enqueue_scripts', array( $action_obj, 'admin_enqueue_assets' ), 98 );
					}
					$action_obj->set_integration_type( $integration->get_slug() );
					BWFAN_Load_Integrations::register_actions( $action_obj );
				}
			}
		}
	}

	public function load_events() {
		$event_dir = __DIR__ . '/events';
		foreach ( glob( $event_dir . '/class-*.php' ) as $_field_filename ) {
			$file_data = pathinfo( $_field_filename );
			if ( isset( $file_data['basename'] ) && 'index.php' === $file_data['basename'] ) {
				continue;
			}

			$event_class = require_once( $_field_filename );

			if ( ! is_null( $event_class ) && method_exists( $event_class, 'get_instance' ) ) {
				/**
				 * @var $event_obj BWFAN_Event
				 */
				$event_obj     = $event_class::get_instance();
				$source_object = BWFAN_WC_Source::get_instance();

				BWFAN_Load_Sources::$all_events[ $source_object->get_name() ][ $event_obj->get_slug() ] = $event_obj->get_name();
				if ( isset( $global_settings[ 'bwfan_stop_event_' . $event_obj->get_slug() ] ) && ! empty( $global_settings[ 'bwfan_stop_event_' . $event_obj->get_slug() ] ) ) {
					continue;
				}

				$event_obj->load_hooks();
				if ( method_exists( $event_obj, 'admin_enqueue_assets' ) && is_admin() && BWFAN_Common::is_automation_v1_active() && BWFAN_Common::is_autonami_page() ) {
					// Add action to avoid enqueueing assets on every admin page load
					add_action( 'admin_enqueue_scripts', array( $event_obj, 'admin_enqueue_assets' ), 98 );
				}
				$event_obj->set_source_type( $source_object->get_slug() );
				BWFAN_Load_Sources::register_events( $event_obj );
			}
		}
	}

}

/**
 * Register this class as an integration.
 */
if ( bwfan_is_woocommerce_active() ) {
	BWFAN_Load_Integrations::register( 'BWFAN_PRO_WC' );
}
