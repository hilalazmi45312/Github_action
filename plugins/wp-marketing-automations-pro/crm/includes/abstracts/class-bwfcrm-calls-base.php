<?php

namespace BWFCRM\Calls;

/**
 * Calls base class
 */
#[\AllowDynamicProperties]
abstract class Base {

	/** Handle action response type */
	public static $RESPONSE_FAILED  = 1;
	public static $RESPONSE_SUCCESS = 2;
	public static $RESPONSE_SKIPPED = 3;

	/**
	 * Abstract function to process call
	 *
	 * @param $contact
	 * @param $data
	 */
	abstract public function process_call( $contact, $data );
}