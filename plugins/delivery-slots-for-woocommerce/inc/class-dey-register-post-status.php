<?php
/**
 * Register Custom Post Status.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'DEY_Register_Post_Status' ) ) {

	/**
	 * Class.
	 */
	class DEY_Register_Post_Status {

		/**
		 * Class initialization.
		 */
		public static function init() {
			add_action( 'init', array( __CLASS__, 'register_custom_post_status' ) );
		}

		/**
		 * Register custom post status.
		 *
		 * @return void
		 */
		public static function register_custom_post_status() {
			$custom_post_statuses = array(
				'dey_pending_payment' => array( 'DEY_Register_Post_Status', 'pending_payment_post_status_args' ),
				'dey_upcoming'        => array( 'DEY_Register_Post_Status', 'upcoming_post_status_args' ),
				'dey_failed'          => array( 'DEY_Register_Post_Status', 'failed_post_status_args' ),
				'dey_delivered'       => array( 'DEY_Register_Post_Status', 'delivered_post_status_args' ),
				'dey_picked_up'       => array( 'DEY_Register_Post_Status', 'picked_up_post_status_args' ),
				'dey_paid'            => array( 'DEY_Register_Post_Status', 'paid_post_status_args' ),
				'dey_active'          => array( 'DEY_Register_Post_Status', 'active_post_status_args' ),
				'dey_inactive'        => array( 'DEY_Register_Post_Status', 'inactive_post_status_args' ),
			);

			/**
			 * This hook is used to alter the custom post statuses.
			 *
			 * @since 1.0
			 */
			$custom_post_statuses = apply_filters( 'dey_add_custom_post_status', $custom_post_statuses );

			// Return if no post status have to register.
			if ( ! dey_check_is_array( $custom_post_statuses ) ) {
				return;
			}

			foreach ( $custom_post_statuses as $post_status => $args_function ) {
				$args = array();
				if ( $args_function ) {
					$args = call_user_func_array( $args_function, array() );
				}

				// Register post status.
				register_post_status( $post_status, $args );
			}
		}

		/**
		 * Pending Payment custom post status arguments.
		 *
		 * @return array
		 */
		public static function pending_payment_post_status_args() {
			/**
			 * This hook is used to alter the pending payment post status arguments.
			 *
			 * @since 1.0
			 */
			return apply_filters(
				'dey_pending_payment_post_status_args',
				array(
					'label'                     => __( 'Pending Payment', 'delivery-slots-for-woocommerce' ),
					'public'                    => true,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: count */
					'label_count'               => _n_noop( 'Pending Payment <span class="count">(%s)</span>', 'Pending Payments <span class="count">(%s)</span>', 'delivery-slots-for-woocommerce' ),
				)
			);
		}

		/**
		 * Upcoming custom post status arguments.
		 *
		 * @return array
		 */
		public static function upcoming_post_status_args() {
			/**
			 * This hook is used to alter the upcoming post status arguments.
			 *
			 * @since 1.0
			 */
			return apply_filters(
				'dey_upcoming_post_status_args',
				array(
					'label'                     => __( 'Upcoming', 'delivery-slots-for-woocommerce' ),
					'public'                    => true,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: count */
					'label_count'               => _n_noop( 'Upcoming <span class="count">(%s)</span>', 'Upcomings <span class="count">(%s)</span>', 'delivery-slots-for-woocommerce' ),
				)
			);
		}

		/**
		 * Failed custom post status arguments.
		 *
		 * @return array
		 */
		public static function failed_post_status_args() {
			/**
			 * This hook is used to alter the failed post status arguments.
			 *
			 * @since 1.0
			 */
			return apply_filters(
				'dey_failed_post_status_args',
				array(
					'label'                     => __( 'Failed', 'delivery-slots-for-woocommerce' ),
					'public'                    => true,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: count */
					'label_count'               => _n_noop( 'Failed <span class="count">(%s)</span>', 'Faileds <span class="count">(%s)</span>', 'delivery-slots-for-woocommerce' ),
				)
			);
		}

		/**
		 * Delivered custom post status arguments.
		 *
		 * @return array
		 */
		public static function delivered_post_status_args() {
			/**
			 * This hook is used to alter the delivered post status arguments.
			 *
			 * @since 1.0
			 */
			return apply_filters(
				'dey_delivered_post_status_args',
				array(
					'label'                     => __( 'Delivered', 'delivery-slots-for-woocommerce' ),
					'public'                    => true,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: count */
					'label_count'               => _n_noop( 'Delivered <span class="count">(%s)</span>', 'Delivered <span class="count">(%s)</span>', 'delivery-slots-for-woocommerce' ),
				)
			);
		}

		/**
		 * Picked up custom post status arguments.
		 *
		 * @return array
		 */
		public static function picked_up_post_status_args() {
			/**
			 * This hook is used to alter the picked up post status arguments.
			 *
			 * @since 2.2
			 */
			return apply_filters(
				'dey_picked_up_post_status_args',
				array(
					'label'                     => __( 'Picked up', 'delivery-slots-for-woocommerce' ),
					'public'                    => true,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: count */
					'label_count'               => _n_noop( 'Picked up <span class="count">(%s)</span>', 'Picked up <span class="count">(%s)</span>', 'delivery-slots-for-woocommerce' ),
				)
			);
		}

		/**
		 * Paid custom post status arguments.
		 *
		 * @return array
		 */
		public static function paid_post_status_args() {
			/**
			 * This hook is used to alter the paid post status arguments.
			 *
			 * @since 1.0
			 */
			return apply_filters(
				'dey_paid_post_status_args',
				array(
					'label'                     => __( 'Paid', 'delivery-slots-for-woocommerce' ),
					'public'                    => true,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: count */
					'label_count'               => _n_noop( 'Paid <span class="count">(%s)</span>', 'Paid <span class="count">(%s)</span>', 'delivery-slots-for-woocommerce' ),
				)
			);
		}

		/**
		 * Active custom post status arguments.
		 *
		 * @since 4.0.0
		 * @return array
		 */
		public static function active_post_status_args() {
			/**
			 * This hook is used to alter the active post status arguments.
			 *
			 * @since 4.0.0
			 */
			return apply_filters(
				'dey_active_post_status_args',
				array(
					'label'                     => __( 'Active', 'delivery-slots-for-woocommerce' ),
					'public'                    => true,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: count */
					'label_count'               => _n_noop( 'Active <span class="count">(%s)</span>', 'Active <span class="count">(%s)</span>', 'delivery-slots-for-woocommerce' ),
				)
			);
		}

		/**
		 * Inactive custom post status arguments.
		 *
		 * @since 4.0.0
		 * @return array
		 */
		public static function inactive_post_status_args() {
			/**
			 * This hook is used to alter the inactive post status arguments.
			 *
			 * @since 4.0.0
			 */
			return apply_filters(
				'dey_inactive_post_status_args',
				array(
					'label'                     => __( 'In-Active', 'delivery-slots-for-woocommerce' ),
					'public'                    => true,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: count */
					'label_count'               => _n_noop( 'In-Active <span class="count">(%s)</span>', 'In-Active <span class="count">(%s)</span>', 'delivery-slots-for-woocommerce' ),
				)
			);
		}
	}

	DEY_Register_Post_Status::init();
}
