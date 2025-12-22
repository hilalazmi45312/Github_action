<?php
/**
 * Class to handle combine coupon restrictions
 *
 * @author      StoreApps
 * @category    Admin
 * @package     wocommerce-smart-coupons/includes
 * @version     1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_SC_Combo_Coupons' ) ) {

	/**
	 * Class WC_SC_Combo_Coupons
	 */
	class WC_SC_Combo_Coupons {

		/**
		 * Variable to hold instance of this class
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Constructor
		 */
		public function __construct() {
			add_action( 'woocommerce_coupon_options_usage_restriction', array( $this, 'add_combine_coupon_fields' ), 15 );
			add_action( 'woocommerce_coupon_options_save', array( $this, 'save_combine_coupon_fields' ), 10, 2 );
			add_filter( 'woocommerce_coupon_is_valid', array( $this, 'validate_combine_coupons' ), 10, 2 );
			add_action( 'woocommerce_applied_coupon', array( $this, 'maybe_store_combine_coupon_session' ) );
			add_action( 'woocommerce_removed_coupon', array( $this, 'maybe_clear_combine_coupon_rules_from_session' ) );
			add_action( 'wp_ajax_wc_sc_combine_coupon_search', array( $this, 'combine_coupon_ajax_search' ) );
		}

		/**
		 * Get single instance of this class
		 *
		 * @return WC_SC_Combo_Coupons class Singleton object of this class
		 */
		public static function get_instance() {
			// Check if instance is already exists.
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Handle call to functions which is not available in this class
		 *
		 * @param string $function_name The function name.
		 * @param array  $arguments Array of arguments passed while calling $function_name.
		 * @return mixed of function call
		 */
		public function __call( $function_name = '', $arguments = array() ) {

			global $woocommerce_smart_coupon;

			if ( ! is_callable( array( $woocommerce_smart_coupon, $function_name ) ) ) {
				return;
			}

			if ( ! empty( $arguments ) ) {
				return call_user_func_array( array( $woocommerce_smart_coupon, $function_name ), $arguments );
			} else {
				return call_user_func( array( $woocommerce_smart_coupon, $function_name ) );
			}

		}

		/**
		 * Add combine coupon fields to coupon data meta box.
		 *
		 * @param int $coupon_id The coupon ID.
		 */
		public function add_combine_coupon_fields( $coupon_id ) {

			if ( ! is_numeric( $coupon_id ) || $coupon_id <= 0 ) {
				return; // Ensure we have a valid coupon ID.
			}

			// Get previously saved allowed and blocked coupons.
			$wc_sc_combined_coupons_allowed = $this->get_post_meta( $coupon_id, 'wc_sc_combined_coupons_allowed', true );
			$wc_sc_combined_coupons_blocked = $this->get_post_meta( $coupon_id, 'wc_sc_combined_coupons_blocked', true );

			// Ensure both are arrays.
			$wc_sc_combined_coupons_allowed = is_array( $wc_sc_combined_coupons_allowed ) ? $wc_sc_combined_coupons_allowed : array();
			$wc_sc_combined_coupons_blocked = is_array( $wc_sc_combined_coupons_blocked ) ? $wc_sc_combined_coupons_blocked : array();

			echo '<div class="options_group smart-coupons-field">';

			// Coupons that can be used together.
			woocommerce_wp_select(
				array(
					'id'                => 'wc_sc_combined_coupons_allowed',
					'name'              => 'wc_sc_combined_coupons_allowed[]',
					'label'             => _x( 'Coupon(s) can be used with', 'Coupon field label', 'woocommerce-smart-coupons' ),
					'description'       => _x( 'Select coupons that can be used in conjunction with this coupon.', 'Coupon field description', 'woocommerce-smart-coupons' ),
					'desc_tip'          => true,
					'style'             => 'width: 50%;',
					'custom_attributes' => array(
						'multiple'         => 'multiple',
						'data-placeholder' => _x( 'Search for a coupons…', 'Coupon field placeholder', 'woocommerce-smart-coupons' ),
					),
					'options'           => $this->get_saved_coupons_by_ids( $wc_sc_combined_coupons_allowed ),
					'value'             => $wc_sc_combined_coupons_allowed,
				)
			);

			echo '</div>';

			echo '<div class="options_group smart-coupons-field">';

			// Coupons that cannot be used together.
			woocommerce_wp_select(
				array(
					'id'                => 'wc_sc_combined_coupons_blocked',
					'name'              => 'wc_sc_combined_coupons_blocked[]',
					'label'             => _x( 'Coupon(s) can’t be used with', 'Coupon field label', 'woocommerce-smart-coupons' ),
					'description'       => _x( 'Select coupons that cannot be used in conjunction with this coupon.', 'Coupon field description', 'woocommerce-smart-coupons' ),
					'desc_tip'          => true,
					'style'             => 'width: 50%;',
					'custom_attributes' => array(
						'multiple'         => 'multiple',
						'data-placeholder' => _x( 'Search for a coupons…', 'Coupon field placeholder', 'woocommerce-smart-coupons' ),
					),
					'options'           => $this->get_saved_coupons_by_ids( $wc_sc_combined_coupons_blocked ),
					'value'             => $wc_sc_combined_coupons_blocked,
				)
			);

			echo '</div>';

			$nonce       = wp_create_nonce( 'wc_sc_nonce_combine_coupon_search' );
			$placeholder = _x( 'Search for a coupons…', 'Coupon field placeholder', 'woocommerce-smart-coupons' );
			$ajax_url    = admin_url( 'admin-ajax.php' );

			$inline_js = <<<JS
				(function($) {
					function initSmartCouponsCombineCouponSearch(selector) {
						$(selector).select2({
							placeholder: function() {
								return $(this).data('placeholder') || "$placeholder";
							},
							minimumInputLength: 2,
							ajax: {
								url: "$ajax_url",
								dataType: 'json',
								delay: 250,
								data: function(params) {
									return {
										action: 'wc_sc_combine_coupon_search',
										q: params.term,
										security: "$nonce",
									};
								},
								processResults: function(data) {
									return {
										results: data
									};
								},
								cache: true
							},
							width: 'resolve'
						});
					}

					initSmartCouponsCombineCouponSearch('#wc_sc_combined_coupons_allowed');
					initSmartCouponsCombineCouponSearch('#wc_sc_combined_coupons_blocked');
				})(jQuery);
JS;

			wp_add_inline_script( 'wc-smart-admin-user-restriction-coupons', $inline_js );
		}

		/**
		 * Helper function to sync mutual relationship.
		 *
		 * @param array  $new_ids        Newly selected coupons.
		 * @param array  $prev_ids       Previously selected coupons.
		 * @param string $field_key     Meta key to update (allowed/blocked).
		 * @param int    $post_id   Current coupon's ID.
		 */
		private function helper_sync_meta( $new_ids, $prev_ids, $field_key, $post_id ) {
			// Coupons to which we need to add this coupon.
			$to_add = array_diff( $new_ids, $prev_ids );

			// Coupons from which we need to remove this coupon.
			$to_remove = array_diff( $prev_ids, $new_ids );

			// Add current coupon to other coupons' meta if not already there.
			foreach ( $to_add as $id ) {
				$existing = (array) $this->get_post_meta( $id, $field_key, true );

				if ( ! in_array( $post_id, $existing, true ) ) {
					$existing[] = $post_id;
					$this->update_post_meta( $id, $field_key, $existing );
				}
			}

			// Remove current coupon from others where it no longer applies.
			foreach ( $to_remove as $id ) {
				$existing = (array) $this->get_post_meta( $id, $field_key, true );

				if ( in_array( $post_id, $existing, true ) ) {
					$updated = array_diff( $existing, array( $post_id ) );
					$this->update_post_meta( $id, $field_key, $updated );
				}
			}
		}

		/**
		 * Save combine coupon meta fields and ensure mutual sync.
		 *
		 * @param int       $post_id Post ID of the current coupon.
		 * @param WC_Coupon $coupon  Coupon object.
		 */
		public function save_combine_coupon_fields( $post_id, $coupon ) {
			try {
				// Helper to sanitize and prepare IDs from POST for a given meta key.
				$sanitize_ids = function( $key ) use ( $post_id ) {
					$ids = isset( $_POST[ $key ] ) ? array_filter( array_map( 'absint', (array) wc_clean( wp_unslash( $_POST[ $key ] ) ) ) ) : array(); //phpcs:ignore

					// Remove self-reference (a coupon shouldn't reference itself).
					return array_diff( $ids, array( $post_id ) );
				};

				// Get new allowed and blocked coupon IDs from POST.
				$new_allowed_ids = $sanitize_ids( 'wc_sc_combined_coupons_allowed' );
				$new_blocked_ids = $sanitize_ids( 'wc_sc_combined_coupons_blocked' );

				// Prevent overlap between allowed and blocked lists.
				$new_allowed_ids = array_diff( $new_allowed_ids, $new_blocked_ids );
				$new_blocked_ids = array_diff( $new_blocked_ids, $new_allowed_ids );

				// Fetch previously saved values to calculate what changed.
				$prev_allowed_ids = $this->get_post_meta( $post_id, 'wc_sc_combined_coupons_allowed', true );
				$prev_blocked_ids = $this->get_post_meta( $post_id, 'wc_sc_combined_coupons_blocked', true );

				// Ensure previous values are arrays.
				$prev_allowed_ids = is_array( $prev_allowed_ids ) ? $prev_allowed_ids : array();
				$prev_blocked_ids = is_array( $prev_blocked_ids ) ? $prev_blocked_ids : array();

				// Update the meta for the current coupon.
				$this->update_post_meta( $post_id, 'wc_sc_combined_coupons_allowed', $new_allowed_ids );
				$this->update_post_meta( $post_id, 'wc_sc_combined_coupons_blocked', $new_blocked_ids );

				// Sync both directions for allowed and blocked coupons.
				$this->helper_sync_meta( $new_allowed_ids, $prev_allowed_ids, 'wc_sc_combined_coupons_allowed', $post_id );
				$this->helper_sync_meta( $new_blocked_ids, $prev_blocked_ids, 'wc_sc_combined_coupons_blocked', $post_id );
			} catch ( \Throwable $e ) {
				$this->sc_block_catch_error( $e );
			}
		}

		/**
		 * Validate if the coupon can be combined with other applied coupons.
		 *
		 * @param bool      $is_valid Whether the coupon is valid so far.
		 * @param WC_Coupon $coupon   The coupon being validated.
		 * @return bool
		 * @throws Exception When coupon combination is not allowed.
		 */
		public function validate_combine_coupons( $is_valid, $coupon ) {
			if ( ! $is_valid || ! ( $coupon instanceof WC_Coupon ) ) {
				return $is_valid;
			}

			if ( ! function_exists( 'WC' ) || ! WC()->session ) {
				return $is_valid;
			}

			// WooCommerce version compatibility for coupon code and ID.
			$is_wc_3_plus = $this->is_wc_gte_30();
			$coupon_code  = $is_wc_3_plus && is_callable( array( $coupon, 'get_code' ) ) ? $coupon->get_code() : ( $coupon->code ?? '' );
			$coupon_id    = $is_wc_3_plus && is_callable( array( $coupon, 'get_id' ) ) ? $coupon->get_id() : ( $coupon->id ?? 0 );

			if ( empty( $coupon_code ) || empty( $coupon_id ) ) {
				return $is_valid;
			}

			// Use only coupons that have allowed/blocked rules present in the session.
			$session_data = WC()->session->get( 'wc_sc_combined_coupons', array() );
			if ( empty( $session_data ) ) {
				return $is_valid; // No special combine rules in play.
			}

			$current_allowed = array_map( 'absint', (array) ( $session_data[ $coupon_code ]['allowed'] ?? array() ) );
			$current_blocked = array_map( 'absint', (array) ( $session_data[ $coupon_code ]['disallowed'] ?? array() ) );

			// Get all session-tracked coupons except the one being validated.
			$other_coupon_codes = array_diff( array_keys( $session_data ), array( $coupon_code ) );
			if ( empty( $other_coupon_codes ) ) {
				return $is_valid; // Only coupon in the session: always allowed.
			}

			$is_allowed_by_others = false;

			foreach ( $other_coupon_codes as $other_code ) {
				$other_data    = $session_data[ $other_code ];
				$other_allowed = array_map( 'absint', (array) ( $other_data['allowed'] ?? array() ) );
				$other_blocked = array_map( 'absint', (array) ( $other_data['disallowed'] ?? array() ) );

				// Grab other coupon ID for checks.
				$other_coupon = new WC_Coupon( $other_code );
				$other_id     = $is_wc_3_plus && is_callable( array( $other_coupon, 'get_id' ) ) ? $other_coupon->get_id() : ( $other_coupon->id ?? 0 );

				// 1. If either coupon blocks the other – fail immediately.
				if ( in_array( $coupon_id, $other_blocked, true ) || in_array( $other_id, $current_blocked, true ) ) {
					/* translators: Coupon cannot be combined error message */
					throw new Exception( _x( 'This coupon cannot be used together with another applied coupon.', 'Error message when coupon cannot be combined', 'woocommerce-smart-coupons' ) );
				}

				// 2. If either side allows – flag as allowed.
				if ( in_array( $other_id, $current_allowed, true ) || in_array( $coupon_id, $other_allowed, true ) ) {
					$is_allowed_by_others = true;
				}
			}

			// If the current coupon has an allowed list but none matched – deny.
			if ( ! empty( $current_allowed ) && ! $is_allowed_by_others ) {
				/* translators: Coupon cannot be combined error message */
				throw new Exception( _x( 'This coupon cannot be combined with the applied coupon(s).', 'Error message when coupon cannot be combined', 'woocommerce-smart-coupons' ) );
			}

			// If current has no rules, but another in session has "allowed" rules and doesn't allow this coupon – deny.
			if ( empty( $current_allowed ) && empty( $current_blocked ) && ! $is_allowed_by_others ) {
				foreach ( $other_coupon_codes as $other_code ) {
					$other_data    = $session_data[ $other_code ];
					$other_allowed = array_map( 'absint', (array) ( $other_data['allowed'] ?? array() ) );
					if ( ! empty( $other_allowed ) && ! in_array( $coupon_id, $other_allowed, true ) ) {
						/* translators: Coupon cannot be combined error message */
						throw new Exception( _x( 'This coupon cannot be used with one of the other applied coupons.', 'Error message when coupon cannot be combined', 'woocommerce-smart-coupons' ) );
					}
				}
			}

			return $is_valid;
		}


		/**
		 * Store coupon meta to WC session
		 * This is used to store allowed and blocked coupons for the applied coupon.
		 *
		 * @param string $coupon_code The coupon code to store in session.
		 * @return void
		 */
		public function maybe_store_combine_coupon_session( $coupon_code ) {
			try {
				// Validate the coupon code and session.
				if ( ! is_string( $coupon_code ) || empty( $coupon_code ) || ! function_exists( 'WC' ) || ! WC()->session ) {
					return;
				}

				// Get the coupon object.
				$coupon = new WC_Coupon( $coupon_code );

				if ( ! is_object( $coupon ) || ! $coupon instanceof WC_Coupon ) {
					return;
				}

				if ( $this->is_wc_gte_30() ) {
					// For WooCommerce 3.0 and above, we can use the get_code() method.
					$coupon_id = is_callable( array( $coupon, 'get_id' ) ) ? $coupon->get_id() : 0;
				} else {
					// For older versions, we use the deprecated method.
					$coupon_id = ( ! empty( $coupon->id ) ) ? $coupon->id : 0;
				}

				// Get allowed and blocked coupons.
				$allowed_coupons = $this->get_post_meta( $coupon_id, 'wc_sc_combined_coupons_allowed', true );
				$blocked_coupons = $this->get_post_meta( $coupon_id, 'wc_sc_combined_coupons_blocked', true );

				// Ensure both are arrays.
				$allowed_coupons = is_array( $allowed_coupons ) ? $allowed_coupons : array();
				$blocked_coupons = is_array( $blocked_coupons ) ? $blocked_coupons : array();

				// If there are no allowed or blocked coupons, do not store in session.
				if ( ! empty( $allowed_coupons ) || ! empty( $blocked_coupons ) ) {
					$session_data = WC()->session->get( 'wc_sc_combined_coupons', array() );

					$session_data[ $coupon_code ] = array(
						'allowed'    => $allowed_coupons,
						'disallowed' => $blocked_coupons,
					);
					// Store in session.
					WC()->session->set( 'wc_sc_combined_coupons', $session_data );
				}
			} catch ( \Throwable $e ) {
				$this->sc_block_catch_error( $e );
			}
		}

		/**
		 * Cleanup WC session on coupon removal
		 * This is used to remove the coupon from session data when it is removed.
		 *
		 * @param string $coupon_code The coupon code to clear from session.
		 * @return void
		 */
		public function maybe_clear_combine_coupon_rules_from_session( $coupon_code ) {
			// Get the session data for combined coupons.
			$session_data = function_exists( 'WC' ) && WC()->session ? WC()->session->get( 'wc_sc_combined_coupons', array() ) : array();

			if ( ! is_string( $coupon_code ) || empty( $coupon_code ) || ! isset( $session_data[ $coupon_code ] ) ) {
				return;
			}

			// Remove the coupon from session data.
			unset( $session_data[ $coupon_code ] );

			// Update the session.
			WC()->session->set( 'wc_sc_combined_coupons', $session_data );
		}

		/**
		 * Get all coupons as [ ID => Title ] pairs for select options.
		 * Fetches data directly for performance and backwards compatibility.
		 *
		 * @return void Sends a JSON response.
		 */
		public function combine_coupon_ajax_search() {
			try {
				$security = isset( $_GET['security'] ) ? sanitize_text_field( wp_unslash( $_GET['security'] ) ) : '';

				if ( empty( $security ) || ! wp_verify_nonce( $security, 'wc_sc_nonce_combine_coupon_search' ) ) {
					wp_send_json_error( array( 'message' => _x( 'Invalid nonce', 'Error message for invalid nonce', 'woocommerce-smart-coupons' ) ), 403 );
				}

				if ( ! current_user_can( 'edit_products' ) ) {
					wp_send_json_error( _x( 'Unauthorized', 'Error message for unauthorized access', 'woocommerce-smart-coupons' ) );
				}

				// Get the search query.
				$query = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';

				$args = array(
					'posts_per_page' => -1,
					'post_status'    => 'publish',
					'post_type'      => 'shop_coupon',
					's'              => $query,
					'orderby'        => 'title',
					'order'          => 'ASC',
					'fields'         => 'ids',
				);

				$coupon_ids = get_posts( $args );

				$options = array();

				foreach ( $coupon_ids as $coupon_id ) {
					$title = get_the_title( $coupon_id ); // Get the coupon title.
					if ( $title ) {
						$options[] = array(
							'id'   => $coupon_id,
							'text' => $title,
						);
					}
				}

				wp_send_json( $options );
			} catch ( \Throwable $e ) {
				$this->sc_block_catch_error( $e );
				wp_send_json_error( array( 'message' => _x( 'An unexpected error occurred.', 'Error message', 'woocommerce-smart-coupons' ) ), 500 );
			}
		}

		/**
		 * Get coupon posts for saved coupon IDs.
		 *
		 * @param array $coupon_ids Array of saved coupon post IDs.
		 * @return array Array of coupon objects (ID and title).
		 */
		private function get_saved_coupons_by_ids( $coupon_ids = array() ) {
			if ( empty( $coupon_ids ) || ! is_array( $coupon_ids ) ) {
				return array();
			}

			$args = array(
				'post_type'      => 'shop_coupon',
				'post__in'       => $coupon_ids,
				'posts_per_page' => -1,
				'orderby'        => 'post__in', // preserve original order.
				'fields'         => 'ids', // If you only need IDs, or omit this to get full posts.
			);

			$coupon_post_ids = get_posts( $args );

			$coupons = array();
			foreach ( $coupon_post_ids as $coupon_id ) {
				$coupons[ $coupon_id ] = get_the_title( $coupon_id ); // Get the coupon title.
			}

			return $coupons;
		}

	}

	new WC_SC_Combo_Coupons();
}
