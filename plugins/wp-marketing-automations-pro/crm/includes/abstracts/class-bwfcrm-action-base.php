<?php

namespace BWFCRM\Actions;

/**
 * Actions base class
 */
#[\AllowDynamicProperties]
abstract class Base {

	/** Handle action response type */
	public static $RESPONSE_FAILED = 1;
	public static $RESPONSE_SUCCESS = 2;
	public static $RESPONSE_SKIPPED = 3;

	/**
	 * Action slug
	 *
	 * @var string
	 */
	protected $slug = '';

	/**
	 * Action nice name
	 *
	 * @var string
	 */
	protected $nice_name = '';

	/**
	 * Action group
	 *
	 * @var string
	 */
	protected $group = '';

	/**
	 * Action group nice name
	 *
	 * @var string
	 */
	protected $group_label = '';

	/**
	 * Action priority to show
	 *
	 * @var int
	 */
	protected $priority = 10;

	/**
	 * Actions support 1 - link triggers, 2 - bulk actions
	 *
	 * @var array
	 */
	protected $support = [];

	/**
	 * Autonami Event slug
	 * @var string
	 */
	protected $event_slug = '';

	/**
	 * Returns action slug
	 *
	 * @return string
	 */
	public function get_action_slug() {
		return $this->slug;
	}

	/**
	 * Returns Actions nice name
	 *
	 * @return string
	 */
	public function get_action_nice_name() {
		return $this->nice_name;
	}

	/**
	 * Return Action Group slug
	 *
	 * @return string
	 */
	public function get_action_group() {
		return $this->group;
	}

	/**
	 * Return Action Group nicename
	 *
	 * @return string
	 */
	public function get_action_group_nicename() {
		return $this->group_label;
	}

	/**
	 * Return Action Group priority
	 *
	 * @return int
	 */
	public function get_action_priority() {
		return $this->priority;
	}

	/**
	 * Return Action Group supported features
	 *
	 * @return array
	 */
	public function get_action_support() {
		return $this->support;
	}

	/**
	 * Returns action event slug
	 *
	 * @return string
	 */
	public function get_action_event_slug() {
		return $this->event_slug;
	}

	/**
	 * Abstract function to get schema
	 */
	abstract public function get_action_schema();

	/**
	 * Abstract function to process action
	 */
	abstract public function handle_action( $contact, $data );
}
