<?php
/**
 * BWFCRM_Custom_Filter_Base Abstract Class
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BWFCRM_Custom_Filter_Base
 */
#[AllowDynamicProperties]
abstract class BWFCRM_Custom_Filter_Base {
	public $name = '';

	/** Helper Functions */
	public function maybe_get_current_filter( $custom_filters ) {
		if ( ! is_array( $custom_filters ) ) {
			return false;
		}

		foreach ( $custom_filters as $filter ) {
			if ( ! is_array( $filter ) || ! isset( $filter['key'] ) || $this->name !== $filter['key'] ) {
				continue;
			}

			return $filter;
		}

		return false;
	}

	/** Check if current filter exists in an array of filters */
	public function is_current_filter_exists( $custom_filters ) {
		$filter = $this->maybe_get_current_filter( $custom_filters );
		return false !== $filter;
	}
}
