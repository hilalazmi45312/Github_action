<?php

use BWFCRM\Actions\Base;

/**
 * BWFCRM_Actions_Handler
 */
#[AllowDynamicProperties]
class BWFCRM_Actions_Handler {
	/**
	 * Class instance
	 */
	private static $ins = null;
	/**
	 * Registered actions list
	 *
	 * @var array
	 */
	private $_actions = [];
	/**
	 * Registered action list slug mapped with nice name
	 *
	 * @var array
	 */
	private $_nice_names = [];

	/**
	 * Registered Actions group list
	 *
	 * @var array
	 */
	private $_groups = [];

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'load_actions' ], 8 );
	}

	/**
	 * Returns class instance
	 *
	 * @return BWFCRM_Actions_Handler|null
	 */
	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self;
		}

		return self::$ins;
	}

	/**
	 * Load all actions
	 */
	public function load_actions() {
		$integration_dir = BWFAN_PRO_PLUGIN_DIR . '/modules';
		foreach ( glob( $integration_dir . '/*/crm-actions/class-*.php' ) as $_field_filename ) {
			$file_data = pathinfo( $_field_filename );
			if ( isset( $file_data['basename'] ) && 'index.php' === $file_data['basename'] ) {
				continue;
			}
			require_once( $_field_filename );
		}

		do_action( 'bwfcrm_actions_loaded' );
	}

	/**
	 * Function registers actions
	 *
	 * @param $slug
	 * @param $class
	 * @param $nice_name
	 */
	public function register_action( $slug, $class, $nice_name, $group = '', $group_label = '' ) {
		if ( empty( $slug ) ) {
			return;
		}

		$this->_actions[ $slug ]    = $class;
		$this->_nice_names[ $slug ] = ! empty( $nice_name ) ? $nice_name : $slug;
		if ( ! empty( $group ) && ! empty( $group_label ) ) {
			if ( isset( $this->_groups[ $group ] ) && isset( $this->_groups[ $group ]['actions'] ) && is_array( $this->_groups[ $group ]['actions'] ) ) {
				$actions = $this->_groups[ $group ]['actions'];
				array_push( $actions, $slug );
			} else {
				$actions = [ $slug ];
			}

			$this->_groups[ $group ] = [
				'label'   => $group_label,
				'actions' => $actions
			];
		}
	}

	/**
	 * Returns action schema data map with action slug
	 *
	 * @return array
	 */
	public function get_all_actions_schema_data() {
		$data = [];
		foreach ( $this->_actions as $slug => $action ) {
			$data[ $slug ] = $this->get_action_by_slug( $slug )->get_action_schema();
		}

		return $data;
	}

	/**
	 * Return action object by slug
	 *
	 * @param $slug
	 *
	 * @return |Base|null
	 */
	public function get_action_by_slug( $slug ) {
		if ( empty( $slug ) ) {
			return null;
		}

		$action_class = $this->_actions[ $slug ] ?? '';
		$action_obj   = class_exists( $action_class ) ? ( new $action_class ) : null;

		return $action_obj instanceof Base ? $action_obj : null;
	}

	/**
	 * Returns action array slug mapped with nice name
	 *
	 * @return array
	 */
	public function get_all_action_list( $support = 0 ) {
		if ( intval( $support ) === 0 ) {
			return $this->_nice_names;
		}

		$actions = [];
		foreach ( $this->_nice_names as $slug => $label ) {
			$actionObj = $this->get_action_by_slug( $slug );
			$supported = $actionObj->get_action_support();
			if ( in_array( $support, $supported ) ) {
				$actions[ $slug ] = $label;
			}
		}

		return $actions;
	}

	/**
	 * Return action group list
	 *
	 * @return array
	 */
	public function get_group_list() {
		return $this->_groups;
	}

	public function process_all_actions( $actions, $contact ) {
		$response = [];
		if ( empty( $actions ) ) {
			return $response;
		}

		$bulk_action_id = isset( $actions['id'] ) ? $actions['id'] : 0;
		if ( isset( $actions['id'] ) ) {
			unset( $actions['id'] );
		}

		foreach ( $actions as $action => $data ) {
			$action_obj = $this->get_action_by_slug( $action );

			if ( is_null( $action_obj ) ) {
				continue;
			}

			if ( 'end_automation' === $action ) {
				$data['from']           = 'Bulk Action';
				$data['bulk_action_id'] = $bulk_action_id;
			}

			$response[ $action ] = $action_obj->handle_action( $contact, $data );
		}

		return $response;
	}
}

/**
 * Register action handler to BWFCRM_Core
 */
if ( class_exists( 'BWFCRM_Actions_Handler' ) ) {
	BWFCRM_Core::register( 'actions', 'BWFCRM_Actions_Handler' );
}
