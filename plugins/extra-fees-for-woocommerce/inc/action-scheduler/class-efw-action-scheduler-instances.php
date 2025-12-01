<?php
/**
 * Action Scheduler Instances Class.
 *
 * @package Product Availability Slots/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'EFW_Action_Scheduler_Instances' ) ) {

	/**
	 * Class EFW_Action_Scheduler_Instances
	 */
	class EFW_Action_Scheduler_Instances {

		/**
		 * Action Schedulers.
		 *
		 * @var array
		 * */
		private static $action_schedulers = array();

		/**
		 * Get action schedulers.
		 *
		 * @var array
		 */
		public static function instance() {
			if ( ! self::$action_schedulers ) {
				self::load_action_schedulers();
			}

			return self::$action_schedulers;
		}

		/**
		 * Load action schedulers.
		 *
		 */
		public static function load_action_schedulers() {
			if ( ! class_exists( 'EFW_Action_Scheduler' ) ) {
				include_once EFW_PLUGIN_PATH . '/inc/abstracts/abstract-efw-action-scheduler.php';
			}

			$action_scheduler_classes = array(
				'efw-product-bulk-update-action' => 'EFW_Product_Bulk_Update_Action_Scheduler',
				'efw-import-plugin-settings' => 'EFW_Import_Plugin_Settings',
				'efw-report-update-action' => 'EFW_Report_Update_Action',
			);

			foreach ( $action_scheduler_classes as $file_name => $class_name ) {
				include 'class-' . $file_name . '.php'; // Include file.
				self::add_action_scheduler_class( new $class_name() ); // Add action scheduler class.
			}
		}

		/**
		 * Add action scheduler class.
		 *
		 * @param Object $object Action Scheduler Object.
		 * @return Object
		 */
		public static function add_action_scheduler_class( $object ) {
			self::$action_schedulers[ $object->get_id() ] = $object;
			return new self();
		}

		/**
		 * Get action scheduler by id.
		 *
		 * @param Integer $action_scheduler_id Action Scheduler ID.
		 * @return Object
		 */
		public static function get_action_scheduler_by_id( $action_scheduler_id ) {
			$action_schedulers = self::instance();
			return isset( $action_schedulers[ $action_scheduler_id ] ) ? $action_schedulers[ $action_scheduler_id ] : false;
		}
	}

}
