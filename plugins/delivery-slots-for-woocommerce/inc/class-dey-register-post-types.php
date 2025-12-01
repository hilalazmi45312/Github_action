<?php
/**
 * Custom Post Type.
 *
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'DEY_Register_Post_Types' ) ) {

	/**
	 * Class.
	 */
	class DEY_Register_Post_Types {

		/**
		 * Order Delivery Post Type.
		 *
		 * @var string
		 */
		const ORDER_DELIVERY_POSTTYPE = 'dey_order_delivery';

		/**
		 * Order Local Pickup Post Type.
		 *
		 * @since 3.0.0
		 * @var string
		 */
		const ORDER_LOCAL_PICKUP_POSTTYPE = 'dey_order_pickup';

		/**
		 * Product Delivery Post Type.
		 *
		 * @var string
		 */
		const PRODUCT_DELIVERY_POSTTYPE = 'dey_product_delivery';

		/**
		 * Product local pickup Post Type.
		 *
		 * @since 3.5.0
		 * @var string
		 */
		const PRODUCT_LOCAL_PICKUP_POSTTYPE = 'dey_product_pickup';

		/**
		 * Order Tip Type.
		 *
		 * @var string
		 */
		const ORDER_TIP_POSTTYPE = 'dey_order_tip';

		/**
		 * Time Slots Post Type.
		 *
		 * @var string
		 */
		const TIME_SLOTS_POSTTYPE = 'dey_time_slots';

		/**
		 * Holiday Type.
		 *
		 * @var string
		 */
		const HOLIDAY_POSTTYPE = 'dey_holiday';

		/**
		 * Specific Days Type.
		 *
		 * @var string
		 */
		const SPECIAL_DAYS_POSTTYPE = 'dey_special_days';

		/**
		 * Pickup Locations Post Type.
		 *
		 * @var string
		 */
		const PICKUP_LOCATIONS_POSTTYPE = 'dey_pickup_locations';

		/**
		 * Scheduler rule post type.
		 *
		 * @since 4.0.0
		 * @var string
		 */
		const SCHEDULER_RULE_POSTTYPE = 'dey_scheduler_rule';

		/**
		 * Class initialization.
		 */
		public static function init() {
			add_action( 'init', array( __CLASS__, 'register_custom_post_types' ) );
		}

		/**
		 * Register the custom post types.
		 *
		 * @return void
		 */
		public static function register_custom_post_types() {
			if ( ! is_blog_installed() ) {
				return;
			}

			$custom_post_types = array(
				self::ORDER_DELIVERY_POSTTYPE       => array( 'DEY_Register_Post_Types', 'order_delivery_post_type_args' ),
				self::ORDER_LOCAL_PICKUP_POSTTYPE   => array( 'DEY_Register_Post_Types', 'order_local_pickup_post_type_args' ),
				self::PRODUCT_DELIVERY_POSTTYPE     => array( 'DEY_Register_Post_Types', 'product_delivery_post_type_args' ),
				self::PRODUCT_LOCAL_PICKUP_POSTTYPE => array( 'DEY_Register_Post_Types', 'product_local_pickup_post_type_args' ),
				self::ORDER_TIP_POSTTYPE            => array( 'DEY_Register_Post_Types', 'order_tip_post_type_args' ),
				self::TIME_SLOTS_POSTTYPE           => array( 'DEY_Register_Post_Types', 'time_slots_post_type_args' ),
				self::HOLIDAY_POSTTYPE              => array( 'DEY_Register_Post_Types', 'holiday_post_type_args' ),
				self::SPECIAL_DAYS_POSTTYPE         => array( 'DEY_Register_Post_Types', 'specific_days_post_type_args' ),
				self::PICKUP_LOCATIONS_POSTTYPE     => array( 'DEY_Register_Post_Types', 'pickup_locations_post_type_args' ),
				self::SCHEDULER_RULE_POSTTYPE       => array( 'DEY_Register_Post_Types', 'scheduler_rule_post_type_args' ),
			);

			/**
			 * This hook is used to alter the custom post types.
			 *
			 * @since 1.0
			 */
			$custom_post_types = apply_filters( 'dey_add_custom_post_types', $custom_post_types );

			// Return if no post type has to register.
			if ( ! dey_check_is_array( $custom_post_types ) ) {
				return;
			}

			foreach ( $custom_post_types as $post_type => $args_function ) {

				$args = array();
				if ( $args_function ) {
					$args = call_user_func_array( $args_function, $args );
				}

				// Check if the already current post type is register.
				if ( post_type_exists( $post_type ) ) {
					continue;
				}

				// Register the custom post type.
				register_post_type( $post_type, $args );
			}
		}

		/**
		 * Prepare the order delivery post type arguments.
		 *
		 * @return array
		 */
		public static function order_delivery_post_type_args() {
			/**
			 * This hook is used to alter the order delivery post type arguments.
			 *
			 * @since 1.0
			 */
			return apply_filters(
				'dey_order_delivery_post_type_args',
				array(
					'labels'              => array(
						'name'               => __( 'Order Deliveries', 'delivery-slots-for-woocommerce' ),
						'singular_name'      => __( 'Order Delivery', 'delivery-slots-for-woocommerce' ),
						'menu_name'          => __( 'Order Deliveries', 'delivery-slots-for-woocommerce' ),
						'search_items'       => __( 'Search Order Delivery', 'delivery-slots-for-woocommerce' ),
						'not_found'          => __( 'No records found.', 'delivery-slots-for-woocommerce' ),
						'not_found_in_trash' => __( 'No records found in Trash.', 'delivery-slots-for-woocommerce' ),
					),
					'description'         => __( 'This is where store order deliveries are stored.', 'delivery-slots-for-woocommerce' ),
					'public'              => false,
					'hierarchical'        => false, // Hierarchical causes memory issues - WP loads all records!
					'exclude_from_search' => true,
					'publicly_queryable'  => false,
					'show_ui'             => true,
					'show_in_menu'        => 'dey_delivery',
					'capability_type'     => 'post',
					'capabilities'        => array( 'create_posts' => 'do_not_allow' ),
					'map_meta_cap'        => true,
					'supports'            => false,
					'has_archive'         => false,
					'rewrite'             => false,
				)
			);
		}

		/**
		 * Prepare the order local pickup post type arguments.
		 *
		 * @since 3.0.0
		 * @return array
		 */
		public static function order_local_pickup_post_type_args() {
			/**
			 * This hook is used to alter the order local pickup post type arguments.
			 *
			 * @since 1.0
			 */
			return apply_filters(
				'dey_order_local_pickup_post_type_args',
				array(
					'labels'              => array(
						'name'               => __( 'Order Local Pickups', 'delivery-slots-for-woocommerce' ),
						'singular_name'      => __( 'Order Local Pickup', 'delivery-slots-for-woocommerce' ),
						'menu_name'          => __( 'Order Local Pickups', 'delivery-slots-for-woocommerce' ),
						'search_items'       => __( 'Search Order Local Pickup', 'delivery-slots-for-woocommerce' ),
						'not_found'          => __( 'No records found.', 'delivery-slots-for-woocommerce' ),
						'not_found_in_trash' => __( 'No records found in Trash.', 'delivery-slots-for-woocommerce' ),
					),
					'description'         => __( 'This is where store order local pickups are stored.', 'delivery-slots-for-woocommerce' ),
					'public'              => false,
					'hierarchical'        => false, // Hierarchical causes memory issues - WP loads all records!
					'exclude_from_search' => true,
					'publicly_queryable'  => false,
					'show_ui'             => true,
					'show_in_menu'        => 'dey_delivery',
					'capability_type'     => 'post',
					'capabilities'        => array( 'create_posts' => 'do_not_allow' ),
					'map_meta_cap'        => true,
					'supports'            => false,
					'has_archive'         => false,
					'rewrite'             => false,
				)
			);
		}

		/**
		 * Prepare the product delivery post type arguments.
		 *
		 * @return array
		 */
		public static function product_delivery_post_type_args() {
			/**
			 * This hook is used to alter the product delivery post type arguments.
			 *
			 * @since 1.0
			 */
			return apply_filters(
				'dey_product_delivery_post_type_args',
				array(
					'labels'              => array(
						'name'               => __( 'Product Deliveries', 'delivery-slots-for-woocommerce' ),
						'singular_name'      => __( 'Product Delivery', 'delivery-slots-for-woocommerce' ),
						'menu_name'          => __( 'Product Deliveries', 'delivery-slots-for-woocommerce' ),
						'search_items'       => __( 'Search Product Delivery', 'delivery-slots-for-woocommerce' ),
						'not_found'          => __( 'No records found.', 'delivery-slots-for-woocommerce' ),
						'not_found_in_trash' => __( 'No records found in Trash.', 'delivery-slots-for-woocommerce' ),
					),
					'description'         => __( 'This is where store order deliveries are stored.', 'delivery-slots-for-woocommerce' ),
					'public'              => false,
					'hierarchical'        => false, // Hierarchical causes memory issues - WP loads all records!
					'exclude_from_search' => true,
					'publicly_queryable'  => false,
					'show_ui'             => true,
					'show_in_menu'        => 'dey_delivery',
					'capability_type'     => 'post',
					'capabilities'        => array( 'create_posts' => 'do_not_allow' ),
					'map_meta_cap'        => true,
					'supports'            => false,
					'has_archive'         => false,
					'rewrite'             => false,
				)
			);
		}

		/**
		 * Prepare the product local pickup post type arguments.
		 *
		 * @since 3.5.0
		 * @return array
		 */
		public static function product_local_pickup_post_type_args() {
			/**
			 * This hook is used to alter the product local pickup post type arguments.
			 *
			 * @since 3.5.0
			 */
			return apply_filters(
				'dey_product_local_pickup_post_type_args',
				array(
					'labels'              => array(
						'name'               => __( 'Product Local Pickup', 'delivery-slots-for-woocommerce' ),
						'singular_name'      => __( 'Product Pickup', 'delivery-slots-for-woocommerce' ),
						'menu_name'          => __( 'Product Local Pickups', 'delivery-slots-for-woocommerce' ),
						'search_items'       => __( 'Search Product Local Pickup', 'delivery-slots-for-woocommerce' ),
						'not_found'          => __( 'No records found.', 'delivery-slots-for-woocommerce' ),
						'not_found_in_trash' => __( 'No records found in Trash.', 'delivery-slots-for-woocommerce' ),
					),
					'description'         => __( 'This is where store order local pickups are stored.', 'delivery-slots-for-woocommerce' ),
					'public'              => false,
					'hierarchical'        => false, // Hierarchical causes memory issues - WP loads all records!
					'exclude_from_search' => true,
					'publicly_queryable'  => false,
					'show_ui'             => true,
					'show_in_menu'        => 'dey_delivery',
					'capability_type'     => 'post',
					'capabilities'        => array( 'create_posts' => 'do_not_allow' ),
					'map_meta_cap'        => true,
					'supports'            => false,
					'has_archive'         => false,
					'rewrite'             => false,
				)
			);
		}

		/**
		 * Prepare the order tip post type arguments.
		 *
		 * @return array
		 */
		public static function order_tip_post_type_args() {
			/**
			 * This hook is used to alter the order tip post type arguments.
			 *
			 * @since 1.0
			 */
			return apply_filters(
				'dey_order_tip_post_type_args',
				array(
					'labels'              => array(
						'name'               => __( 'Tip', 'delivery-slots-for-woocommerce' ),
						'singular_name'      => __( 'Tip', 'delivery-slots-for-woocommerce' ),
						'menu_name'          => __( 'Tip', 'delivery-slots-for-woocommerce' ),
						'search_items'       => __( 'Search Tip', 'delivery-slots-for-woocommerce' ),
						'not_found'          => __( 'No records found.', 'delivery-slots-for-woocommerce' ),
						'not_found_in_trash' => __( 'No records found in Trash.', 'delivery-slots-for-woocommerce' ),
					),
					'description'         => __( 'This is where store tips are stored.', 'delivery-slots-for-woocommerce' ),
					'public'              => false,
					'hierarchical'        => false, // Hierarchical causes memory issues - WP loads all records!
					'exclude_from_search' => true,
					'publicly_queryable'  => false,
					'show_ui'             => true,
					'show_in_menu'        => 'dey_delivery',
					'capability_type'     => 'post',
					'capabilities'        => array( 'create_posts' => 'do_not_allow' ),
					'map_meta_cap'        => true,
					'supports'            => false,
					'has_archive'         => false,
					'rewrite'             => false,
				)
			);
		}

		/**
		 * Prepare the time slots post type arguments.
		 *
		 * @return array
		 */
		public static function time_slots_post_type_args() {
			/**
			 * This hook is used to alter the time slot post type arguments.
			 *
			 * @since 1.0
			 */
			return apply_filters(
				'dey_time_slots_post_type_args',
				array(
					'labels'              => array(
						'name'               => __( 'Time Slots', 'delivery-slots-for-woocommerce' ),
						'singular_name'      => __( 'Time Slot', 'delivery-slots-for-woocommerce' ),
						'menu_name'          => __( 'Time Slots', 'delivery-slots-for-woocommerce' ),
						'add_new'            => __( 'Add New', 'delivery-slots-for-woocommerce' ),
						'add_new_item'       => __( 'Add New Time Slot', 'delivery-slots-for-woocommerce' ),
						'new_item'           => __( 'New Time Slot', 'delivery-slots-for-woocommerce' ),
						'edit_item'          => __( 'Edit Time Slot', 'delivery-slots-for-woocommerce' ),
						'view_item'          => __( 'View Time Slot', 'delivery-slots-for-woocommerce' ),
						'all_items'          => __( 'Time Slots', 'delivery-slots-for-woocommerce' ),
						'search_items'       => __( 'Search Time Slot', 'delivery-slots-for-woocommerce' ),
						'not_found'          => __( 'No records found.', 'delivery-slots-for-woocommerce' ),
						'not_found_in_trash' => __( 'No records found in Trash.', 'delivery-slots-for-woocommerce' ),
					),
					'description'         => __( 'This is where store time slots are stored.', 'delivery-slots-for-woocommerce' ),
					'public'              => false,
					'hierarchical'        => false, // Hierarchical causes memory issues - WP loads all records!
					'exclude_from_search' => true,
					'publicly_queryable'  => false,
					'show_ui'             => true,
					'show_in_menu'        => 'dey_delivery',
					'capability_type'     => 'post',
					'map_meta_cap'        => true,
					'supports'            => array( 'title' ),
					'has_archive'         => false,
					'rewrite'             => false,
				)
			);
		}

		/**
		 * Prepare the holiday post type arguments.
		 *
		 * @return array
		 */
		public static function holiday_post_type_args() {
			/**
			 * This hook is used to alter the holiday post type arguments.
			 *
			 * @since 1.0
			 */
			return apply_filters(
				'dey_holiday_post_type_args',
				array(
					'labels'              => array(
						'name'               => __( 'Holidays', 'delivery-slots-for-woocommerce' ),
						'singular_name'      => __( 'Holiday', 'delivery-slots-for-woocommerce' ),
						'menu_name'          => __( 'Holidays', 'delivery-slots-for-woocommerce' ),
						'add_new'            => __( 'Add New', 'delivery-slots-for-woocommerce' ),
						'add_new_item'       => __( 'Add New Holiday', 'delivery-slots-for-woocommerce' ),
						'new_item'           => __( 'New Holiday', 'delivery-slots-for-woocommerce' ),
						'edit_item'          => __( 'Edit Holiday', 'delivery-slots-for-woocommerce' ),
						'view_item'          => __( 'View Holiday', 'delivery-slots-for-woocommerce' ),
						'all_items'          => __( 'Holidays', 'delivery-slots-for-woocommerce' ),
						'search_items'       => __( 'Search Holiday', 'delivery-slots-for-woocommerce' ),
						'not_found'          => __( 'No records found.', 'delivery-slots-for-woocommerce' ),
						'not_found_in_trash' => __( 'No records found in Trash.', 'delivery-slots-for-woocommerce' ),
					),
					'description'         => __( 'This is where store holidays are stored.', 'delivery-slots-for-woocommerce' ),
					'public'              => false,
					'hierarchical'        => false, // Hierarchical causes memory issues - WP loads all records!
					'exclude_from_search' => true,
					'publicly_queryable'  => false,
					'show_ui'             => true,
					'show_in_menu'        => 'dey_delivery',
					'capability_type'     => 'post',
					'map_meta_cap'        => true,
					'supports'            => array( 'title' ),
					'has_archive'         => false,
					'rewrite'             => false,
				)
			);
		}

		/**
		 * Prepare the specific days post type arguments.
		 *
		 * @return array
		 */
		public static function specific_days_post_type_args() {
			/**
			 * This hook is used to alter the special day post type arguments.
			 *
			 * @since 1.0
			 */
			return apply_filters(
				'dey_special_days_post_type_args',
				array(
					'labels'              => array(
						'name'               => __( 'Specific Dates', 'delivery-slots-for-woocommerce' ),
						'singular_name'      => __( 'Specific Date', 'delivery-slots-for-woocommerce' ),
						'menu_name'          => __( 'Specific Dates', 'delivery-slots-for-woocommerce' ),
						'add_new'            => __( 'Add New', 'delivery-slots-for-woocommerce' ),
						'add_new_item'       => __( 'Add New Specific Date', 'delivery-slots-for-woocommerce' ),
						'new_item'           => __( 'New Specific Date', 'delivery-slots-for-woocommerce' ),
						'edit_item'          => __( 'Edit Specific Date', 'delivery-slots-for-woocommerce' ),
						'view_item'          => __( 'View Specific Date', 'delivery-slots-for-woocommerce' ),
						'all_items'          => __( 'Specific Dates', 'delivery-slots-for-woocommerce' ),
						'search_items'       => __( 'Search Specific Date', 'delivery-slots-for-woocommerce' ),
						'not_found'          => __( 'No records found.', 'delivery-slots-for-woocommerce' ),
						'not_found_in_trash' => __( 'No records found in Trash.', 'delivery-slots-for-woocommerce' ),
					),
					'description'         => __( 'This is where store specific days are stored.', 'delivery-slots-for-woocommerce' ),
					'public'              => false,
					'hierarchical'        => false, // Hierarchical causes memory issues - WP loads all records!
					'exclude_from_search' => true,
					'publicly_queryable'  => false,
					'show_ui'             => true,
					'show_in_menu'        => 'dey_delivery',
					'capability_type'     => 'post',
					'map_meta_cap'        => true,
					'supports'            => array( 'title' ),
					'has_archive'         => false,
					'rewrite'             => false,
				)
			);
		}

		/**
		 * Prepare the pickup locations post type arguments.
		 *
		 * @return array
		 */
		public static function pickup_locations_post_type_args() {
			/**
			 * This hook is used to alter the pickup locations post type arguments.
			 *
			 * @since 1.0
			 */
			return apply_filters(
				'dey_pickup_locations_post_type_args',
				array(
					'labels'              => array(
						'name'               => __( 'Pickup Locations', 'delivery-slots-for-woocommerce' ),
						'singular_name'      => __( 'Pickup Location', 'delivery-slots-for-woocommerce' ),
						'menu_name'          => __( 'Pickup Locations', 'delivery-slots-for-woocommerce' ),
						'add_new'            => __( 'Add New', 'delivery-slots-for-woocommerce' ),
						'add_new_item'       => __( 'Add New Pickup Location', 'delivery-slots-for-woocommerce' ),
						'new_item'           => __( 'New Pickup Location', 'delivery-slots-for-woocommerce' ),
						'edit_item'          => __( 'Edit Pickup Location', 'delivery-slots-for-woocommerce' ),
						'view_item'          => __( 'View Pickup Location', 'delivery-slots-for-woocommerce' ),
						'all_items'          => __( 'Pickup Locations', 'delivery-slots-for-woocommerce' ),
						'search_items'       => __( 'Search Pickup Location', 'delivery-slots-for-woocommerce' ),
						'not_found'          => __( 'No records found.', 'delivery-slots-for-woocommerce' ),
						'not_found_in_trash' => __( 'No records found in Trash.', 'delivery-slots-for-woocommerce' ),
					),
					'description'         => __( 'This is where store pickup locations are stored.', 'delivery-slots-for-woocommerce' ),
					'public'              => false,
					'hierarchical'        => false, // Hierarchical causes memory issues - WP loads all records!
					'exclude_from_search' => true,
					'publicly_queryable'  => false,
					'show_ui'             => true,
					'show_in_menu'        => 'dey_delivery',
					'capability_type'     => 'post',
					'map_meta_cap'        => true,
					'supports'            => array( 'title' ),
					'has_archive'         => false,
					'rewrite'             => false,
				)
			);
		}

		/**
		 * Prepare the scheduler rule post type arguments.
		 *
		 * @since 4.0.0
		 * @return array
		 */
		public static function scheduler_rule_post_type_args() {
			/**
			 * This hook is used to alter the scheduler rule post type arguments.
			 *
			 * @since 4.0.0
			 */
			return apply_filters(
				'dey_scheduler_rule_post_type_args',
				array(
					'labels'              => array(
						'name'               => __( 'Scheduler Rules', 'delivery-slots-for-woocommerce' ),
						'singular_name'      => __( 'Scheduler Rule', 'delivery-slots-for-woocommerce' ),
						'menu_name'          => __( 'Scheduler Rules', 'delivery-slots-for-woocommerce' ),
						'add_new'            => __( 'Add New', 'delivery-slots-for-woocommerce' ),
						'add_new_item'       => __( 'Add New Scheduler Rule', 'delivery-slots-for-woocommerce' ),
						'new_item'           => __( 'New Scheduler Rule', 'delivery-slots-for-woocommerce' ),
						'edit_item'          => __( 'Edit Scheduler Rule', 'delivery-slots-for-woocommerce' ),
						'view_item'          => __( 'View Scheduler Rule', 'delivery-slots-for-woocommerce' ),
						'all_items'          => __( 'Scheduler Rules', 'delivery-slots-for-woocommerce' ),
						'search_items'       => __( 'Search Scheduler Rule', 'delivery-slots-for-woocommerce' ),
						'not_found'          => __( 'No records found.', 'delivery-slots-for-woocommerce' ),
						'not_found_in_trash' => __( 'No records found in Trash.', 'delivery-slots-for-woocommerce' ),
					),
					'description'         => __( 'This is where store scheduler rules are stored.', 'delivery-slots-for-woocommerce' ),
					'public'              => false,
					'hierarchical'        => false, // Hierarchical causes memory issues - WP loads all records!
					'exclude_from_search' => true,
					'publicly_queryable'  => false,
					'show_ui'             => true,
					'show_in_menu'        => 'dey_delivery',
					'capability_type'     => 'post',
					'map_meta_cap'        => true,
					'supports'            => array( 'title' ),
					'has_archive'         => false,
					'rewrite'             => false,
				)
			);
		}
	}

	DEY_Register_Post_Types::init();
}
