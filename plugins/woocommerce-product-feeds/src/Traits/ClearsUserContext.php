<?php

namespace Ademti\WoocommerceProductFeeds\Traits;

trait ClearsUserContext {

	/**
	 * @var int|null
	 */
	protected ?int $original_user_id;

	/**
	 * Ensures that product data is generated without any user context.
	 *
	 * @return void
	 */
	protected function clear_user_context(): void {
		$this->original_user_id = wp_get_current_user()->ID;
		// If we have a current user, clear the context by setting to the anonymous user.
		if ( $this->original_user_id !== 0 ) {
			// phpcs:ignore Generic.PHP.ForbiddenFunctions.Discouraged
			wp_set_current_user( 0 );
		}
	}

	/**
	 * Restores the user context after generation.
	 *
	 * @return void
	 */
	protected function restore_user_context(): void {
		if ( ! empty( $this->original_user_id ) ) {
			// phpcs:ignore Generic.PHP.ForbiddenFunctions.Discouraged
			wp_set_current_user( $this->original_user_id );
		}
	}
}
