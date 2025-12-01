<?php
/**
 * Admin functions.
 *
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'dey_page_screen_ids' ) ) {

	/**
	 * Get the page screen IDs.
	 *
	 * @since 1.0.0
	 * @static array $dey_page_screen_ids Page screen IDs.
	 * @return array
	 */
	function dey_page_screen_ids() {
		static $dey_screen_ids;
		if ( $dey_screen_ids ) {
			return $dey_screen_ids;
		}

		$screen_id = sanitize_title( DEY()->menu_name() );

		/**
		 * This hook is used to alter the page screen IDs.
		 *
		 * @since 1.0.0
		 */
		$dey_screen_ids = apply_filters(
			'dey_page_screen_ids',
			array(
				DEY_Register_Post_Types::ORDER_DELIVERY_POSTTYPE,
				DEY_Register_Post_Types::ORDER_LOCAL_PICKUP_POSTTYPE,
				DEY_Register_Post_Types::PRODUCT_DELIVERY_POSTTYPE,
				DEY_Register_Post_Types::PRODUCT_LOCAL_PICKUP_POSTTYPE,
				DEY_Register_Post_Types::ORDER_TIP_POSTTYPE,
				DEY_Register_Post_Types::TIME_SLOTS_POSTTYPE,
				DEY_Register_Post_Types::HOLIDAY_POSTTYPE,
				DEY_Register_Post_Types::SPECIAL_DAYS_POSTTYPE,
				DEY_Register_Post_Types::PICKUP_LOCATIONS_POSTTYPE,
				DEY_Register_Post_Types::SCHEDULER_RULE_POSTTYPE,
				'dey_calender',
				'product',
				$screen_id . '_page_dey_settings',
				$screen_id . '_page_dey_calender',
			)
		);

		return $dey_screen_ids;
	}
}

if ( ! function_exists( 'dey_current_page_screen_id' ) ) {

	/**
	 * Get the current page screen ID.
	 *
	 * @since 3.5.0
	 * @static string $dey_current_screen_id
	 * @return string
	 */
	function dey_current_page_screen_id() {
		static $dey_current_screen_id;
		if ( $dey_current_screen_id ) {
			return $dey_current_screen_id;
		}

		$dey_current_screen_id = false;
		if ( ! empty( $_REQUEST['screen'] ) ) {
			$dey_current_screen_id = wc_clean( wp_unslash( $_REQUEST['screen'] ) );
		} elseif ( function_exists( 'get_current_screen' ) ) {
			$screen                = get_current_screen();
			$dey_current_screen_id = isset( $screen, $screen->id ) && is_object( $screen ) ? $screen->id : '';
		}

		$dey_current_screen_id = str_replace( 'edit-', '', $dey_current_screen_id );

		return $dey_current_screen_id;
	}
}

if ( ! function_exists( 'dey_get_wc_categories' ) ) {

	/**
	 * Get the WC Categories.
	 *
	 * @return array
	 */
	function dey_get_wc_categories() {
		static $dey_categories;
		if ( isset( $dey_categories ) ) {
			return $dey_categories;
		}

		$dey_categories = array();
		$wc_categories  = get_terms( 'product_cat' );

		if ( ! dey_check_is_array( $wc_categories ) ) {
			return $dey_categories;
		}

		foreach ( $wc_categories as $category ) {
			$dey_categories[ $category->term_id ] = $category->name;
		}

		return $dey_categories;
	}
}

if ( ! function_exists( 'dey_get_wp_user_roles' ) ) {

	/**
	 * Get the WordPress User Roles.
	 *
	 * @since 1.0.0
	 * @static array $dey_user_roles User Roles.
	 * @global $wp_roles WordPress roles.
	 * @return array
	 */
	function dey_get_wp_user_roles() {
		static $dey_user_roles;
		if ( isset( $dey_user_roles ) ) {
			return $dey_user_roles;
		}

		global $wp_roles;
		$dey_user_roles = array();

		if ( ! isset( $wp_roles->roles ) || ! dey_check_is_array( $wp_roles->roles ) ) {
			return $dey_user_roles;
		}

		foreach ( $wp_roles->roles as $slug => $role ) {
			$dey_user_roles[ $slug ] = $role['name'];
		}

		return $dey_user_roles;
	}
}

if ( ! function_exists( 'dey_get_allowed_setting_tabs' ) ) {

	/**
	 * Get the setting tabs.
	 *
	 * @return array
	 */
	function dey_get_allowed_setting_tabs() {
		/**
		 * This hook is used to alter the settings tabs.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_settings_tabs_array', array() );
	}
}

if ( ! function_exists( 'dey_get_settings_page_url' ) ) {

	/**
	 * Get the settings page URL.
	 *
	 * @return URL
	 */
	function dey_get_settings_page_url( $args = array() ) {
		$url = add_query_arg( array( 'page' => 'dey_settings' ), admin_url( 'admin.php' ) );
		if ( dey_check_is_array( $args ) ) {
			$url = add_query_arg( $args, $url );
		}

		return $url;
	}
}

if ( ! function_exists( 'dey_get_calender_page_url' ) ) {

	/**
	 * Get the calender page URL.
	 *
	 * @return URL
	 */
	function dey_get_calender_page_url( $args = array() ) {
		$url = add_query_arg( array( 'page' => 'dey_calender' ), admin_url( 'admin.php' ) );
		if ( dey_check_is_array( $args ) ) {
			$url = add_query_arg( $args, $url );
		}

		return $url;
	}
}

if ( ! function_exists( 'dey_get_edit_post_link' ) ) {

	/**
	 * Get the edit post link.
	 *
	 * @return string
	 */
	function dey_get_edit_post_link( $post_id, $name ) {
		return '<a href="' . esc_url(
			add_query_arg(
				array(
					'post'   => $post_id,
					'action' => 'edit',
				),
				admin_url( 'post.php' )
			)
		) . '" >' . $name . '</a>';
	}
}

if ( ! function_exists( 'dey_get_products_link' ) ) {

	/**
	 * Get the products link.
	 *
	 * @return string
	 */
	function dey_get_products_link( $product_ids, $link = true ) {
		$products_link = '';
		if ( ! dey_check_is_array( $product_ids ) ) {
			return $products_link;
		}

		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );

			// Return if the product does not exist.
			if ( ! $product ) {
				continue;
			}

			if ( $link ) {
				$products_link .= dey_get_edit_post_link( $product_id, $product->get_name() ) . ' , ';
			} else {
				$products_link .= $product->get_name();
			}
		}

		return rtrim( $products_link, ' , ' );
	}
}

if ( ! function_exists( 'dey_get_order_products_link' ) ) {

	/**
	 * Get the order products link.
	 *
	 * @return string
	 */
	function dey_get_order_products_link( $product_ids, $link = true ) {
		$products_link = '';
		if ( ! dey_check_is_array( $product_ids ) ) {
			return $products_link;
		}

		foreach ( $product_ids as $product_id => $quantity ) {
			$product = wc_get_product( $product_id );

			// Return if the product does not exist.
			if ( ! $product ) {
				continue;
			}

			$product_name = $product->get_name() . ' x ' . $quantity;

			if ( $link ) {
				$products_link .= dey_get_edit_post_link( $product_id, $product_name ) . ' , ';
			} else {
				$products_link .= $product_name . ', ';
			}
		}

		return rtrim( $products_link, ' , ' );
	}
}

if ( ! function_exists( 'dey_relative_date_picker_options' ) ) {

	/**
	 * Relative date picker options.
	 *
	 * @return array
	 */
	function dey_relative_date_picker_options( $type = 'full' ) {
		$options = array(
			'seconds' => __( 'Seconds', 'delivery-slots-for-woocommerce' ),
			'minutes' => __( 'Minutes', 'delivery-slots-for-woocommerce' ),
			'hours'   => __( 'Hours', 'delivery-slots-for-woocommerce' ),
			'days'    => __( 'Days', 'delivery-slots-for-woocommerce' ),
		);

		switch ( $type ) {
			case '1':
				unset( $options['seconds'] );
				break;

			case '2':
				unset( $options['seconds'] );
				unset( $options['minutes'] );
				break;

			case '3':
				unset( $options['hours'] );
				unset( $options['days'] );
				break;
		}

		return $options;
	}
}

if ( ! function_exists( 'dey_parse_relative_date_option' ) ) {

	/**
	 * Parse the relative date option.
	 *
	 * @return array.
	 */
	function dey_parse_relative_date_option( $raw_value, $option_type ) {
		$options = dey_relative_date_picker_options( $option_type );

		return wp_parse_args(
			(array) $raw_value,
			array(
				'number' => '',
				'unit'   => reset( $options ),
			)
		);
	}
}

if ( ! function_exists( 'dey_business_day_default_option' ) ) {

	/**
	 * Default Values for business day option.
	 *
	 * @since 3.2.0
	 * @return array.
	 */
	function dey_business_day_default_option() {
		return array(
			'1' => array(
				'enable'       => 'yes',
				'opening_time' => '09:00',
				'closing_time' => '18:00',
			),
			'2' => array(
				'enable'       => 'yes',
				'opening_time' => '09:00',
				'closing_time' => '18:00',
			),
			'3' => array(
				'enable'       => 'yes',
				'opening_time' => '09:00',
				'closing_time' => '18:00',
			),
			'4' => array(
				'enable'       => 'yes',
				'opening_time' => '09:00',
				'closing_time' => '18:00',
			),
			'5' => array(
				'enable'       => 'yes',
				'opening_time' => '09:00',
				'closing_time' => '18:00',
			),
			'6' => array(
				'enable'       => 'yes',
				'opening_time' => '09:00',
				'closing_time' => '18:00',
			),
			'7' => array(
				'enable'       => 'yes',
				'opening_time' => '09:00',
				'closing_time' => '18:00',
			),
		);
	}
}

if ( ! function_exists( 'dey_parse_business_day_option' ) ) {

	/**
	 * Parse the business day option.
	 *
	 * @since 3.2.0
	 * @param string $raw_value The business day.
	 * @return array.
	 */
	function dey_parse_business_day_option( $raw_value, $default_args ) {

		$parsed_args = $default_args;

		foreach ( $raw_value as $key => $value ) {
			if ( is_array( $value ) && isset( $parsed_args[ $key ] ) ) {
				$parsed_args[ $key ] = dey_parse_business_day_option( $value, $parsed_args[ $key ] );
			} else {
				$parsed_args[ $key ] = $value;
			}
		}

		return $parsed_args;
	}
}

if ( ! function_exists( 'dey_get_date_filter_values' ) ) {

	/**
	 * Get the date filter values.
	 *
	 * @return array
	 */
	function dey_get_date_filter_values( $day_filter, $selected_from_date, $selected_to_date ) {
		$values = array(
			'from_date' => false,
			'to_date'   => false,
			'from_time' => false,
			'to_time'   => false,
		);

		switch ( $day_filter ) {
			case '8':
				$date_object         = DEY_Date_Time::get_date_time_object( 'now' );
				$values['to_date']   = $date_object->format( 'Y-m-d 23:59:59' );
				$values['from_date'] = $date_object->modify( '-1months' )->format( 'Y-m-d 00:00:00' );
				break;

			case '7':
				$date_object         = DEY_Date_Time::get_date_time_object( 'now' );
				$values['to_date']   = $date_object->format( 'Y-m-d 23:59:59' );
				$values['from_date'] = $date_object->modify( '-1weeks' )->format( 'Y-m-d 00:00:00' );
				break;

			case '6':
				$date_object         = DEY_Date_Time::get_date_time_object( 'now' )->modify( '-1days' );
				$values['from_date'] = $date_object->format( 'Y-m-d 00:00:00' );
				$values['to_date']   = $date_object->format( 'Y-m-d 23:59:59' );
				break;

			case '5':
				if ( $selected_from_date ) {
					$from_date_object    = DEY_Date_Time::get_date_time_object( $selected_from_date );
					$values['from_date'] = $from_date_object->format( 'Y-m-d 00:00:00' );
					$values['from_time'] = ( $from_date_object->format( 'G' ) ) ? $from_date_object->format( 'H:i' ) : false;
				}

				if ( $selected_to_date ) {
					$to_date_object    = DEY_Date_Time::get_date_time_object( $selected_to_date );
					$values['to_date'] = $to_date_object->format( 'Y-m-d 23:59:59' );
					$values['to_time'] = ( $to_date_object->format( 'G' ) ) ? $to_date_object->format( 'H:i' ) : false;
				}

				break;

			case '4':
				$date_object         = DEY_Date_Time::get_date_time_object( 'now' );
				$values['from_date'] = $date_object->format( 'Y-m-d 00:00:00' );
				$values['to_date']   = $date_object->modify( '+1months' )->format( 'Y-m-d 23:59:59' );
				break;

			case '3':
				$date_object         = DEY_Date_Time::get_date_time_object( 'now' );
				$values['from_date'] = $date_object->format( 'Y-m-d 00:00:00' );
				$values['to_date']   = $date_object->modify( '+1weeks' )->format( 'Y-m-d 23:59:59' );
				break;

			case '2':
				$date_object         = DEY_Date_Time::get_date_time_object( 'now' )->modify( '+1days' );
				$values['from_date'] = $date_object->format( 'Y-m-d 00:00:00' );
				$values['to_date']   = $date_object->format( 'Y-m-d 23:59:59' );
				break;

			case '1':
				$date_object         = DEY_Date_Time::get_date_time_object( 'now' );
				$values['from_date'] = $date_object->format( 'Y-m-d 00:00:00' );
				$values['to_date']   = $date_object->format( 'Y-m-d 23:59:59' );
				break;
		}

		return $values;
	}
}

if ( ! function_exists( 'dey_get_delivery_meta_query_args' ) ) {

	/**
	 * Get the delivery meta query arguments.
	 *
	 * @return array
	 */
	function dey_get_delivery_meta_query_args( $day_filter, $selected_from_date, $selected_to_date ) {
		$meta_query = array();
		$values     = dey_get_date_filter_values( $day_filter, $selected_from_date, $selected_to_date );

		if ( $values['from_date'] ) {
			$meta_query[] = array(
				'key'     => 'dey_delivery_date',
				'value'   => $values['from_date'],
				'compare' => '>=',
				'type'    => 'DATETIME',
			);
		}

		if ( $values['to_date'] ) {
			$meta_query[] = array(
				'key'     => 'dey_delivery_date',
				'value'   => $values['to_date'],
				'compare' => '<=',
				'type'    => 'DATETIME',
			);
		}

		if ( $values['from_time'] ) {
			$meta_query[] = array(
				'key'     => 'dey_time_slot_from',
				'value'   => $values['from_time'],
				'compare' => '>=',
				'type'    => 'TIME',
			);
		}

		if ( $values['to_time'] ) {
			$meta_query[] = array(
				'key'     => 'dey_time_slot_to',
				'value'   => $values['to_time'],
				'compare' => '<=',
				'type'    => 'TIME',
			);
		}

		return $meta_query;
	}
}

if ( ! function_exists( 'dey_get_pickup_meta_query_args' ) ) {

	/**
	 * Get the pickup meta query arguments.
	 *
	 * @since 1.0.0
	 * @param string $day_filter Day filter.
	 * @param string $selected_from_date Selected from date.
	 * @param string $selected_to_date Selected to date.
	 * @return array
	 */
	function dey_get_pickup_meta_query_args( $day_filter, $selected_from_date, $selected_to_date ) {
		$meta_query = array();
		$values     = dey_get_date_filter_values( $day_filter, $selected_from_date, $selected_to_date );

		if ( $values['from_date'] ) {
			$meta_query[] = array(
				'key'     => 'dey_pickup_date',
				'value'   => $values['from_date'],
				'compare' => '>=',
				'type'    => 'DATETIME',
			);
		}

		if ( $values['to_date'] ) {
			$meta_query[] = array(
				'key'     => 'dey_pickup_date',
				'value'   => $values['to_date'],
				'compare' => '<=',
				'type'    => 'DATETIME',
			);
		}

		if ( $values['from_time'] ) {
			$meta_query[] = array(
				'key'     => 'dey_time_slot_from',
				'value'   => $values['from_time'],
				'compare' => '>=',
				'type'    => 'TIME',
			);
		}

		if ( $values['to_time'] ) {
			$meta_query[] = array(
				'key'     => 'dey_time_slot_to',
				'value'   => $values['to_time'],
				'compare' => '<=',
				'type'    => 'TIME',
			);
		}

		return $meta_query;
	}
}

if ( ! function_exists( 'dey_get_order_tip_date_query_args' ) ) {

	/**
	 * Get the order tip date query arguments.
	 *
	 * @return array
	 */
	function dey_get_order_tip_date_query_args( $day_filter, $selected_from_date, $selected_to_date ) {
		$date_query = array();
		$values     = dey_get_date_filter_values( $day_filter, $selected_from_date, $selected_to_date );

		if ( $values['from_date'] ) {
			$date_query[] = array(
				'column' => 'post_date_gmt',
				'after'  => $values['from_date'],
			);
		}

		if ( $values['to_date'] ) {
			$date_query[] = array(
				'column' => 'post_date_gmt',
				'before' => $values['to_date'],
			);
		}

		return $date_query;
	}
}

if ( ! function_exists( 'dey_order_tip_type_name' ) ) {

	/**
	 * Get the order tip type name.
	 *
	 * @return string
	 */
	function dey_order_tip_type_name( $type ) {
		switch ( $type ) {
			case 'custom':
				$name = __( 'Custom', 'delivery-slots-for-woocommerce' );
				break;
			default:
				$name = __( 'Predefined', 'delivery-slots-for-woocommerce' );
				break;
		}

		return $name;
	}
}

if ( ! function_exists( 'dey_get_wc_product_tags' ) ) {

	/**
	 * Get the WC product tags.
	 *
	 * @since 4.0.0
	 * @static array $product_tags
	 * @return array
	 */
	function dey_get_wc_product_tags() {
		static $product_tags;
		if ( isset( $product_tags ) ) {
			return $product_tags;
		}

		$product_tags    = array();
		$wc_product_tags = get_terms( 'product_tag' );
		if ( ! dey_check_is_array( $wc_product_tags ) ) {
			return $product_tags;
		}

		foreach ( $wc_product_tags as $product_tag ) {
			$product_tags[ $product_tag->term_id ] = $product_tag->name;
		}

		return $product_tags;
	}
}

if ( ! function_exists( 'dey_format_restriction_rule_data' ) ) {

	/**
	 * Format the restriction rule data.
	 *
	 * @since 4.0.0
	 * @param array $rule_data Rule data.
	 * @return array
	 */
	function dey_format_restriction_rule_data( $rule_data ) {
		return wp_parse_args( $rule_data, dey_get_default_restriction_rule_data() );
	}
}

if ( ! function_exists( 'dey_format_order_pickup_location_restriction_rule_data' ) ) {

	/**
	 * Format the order pickup location restriction rule data.
	 *
	 * @since 4.0.0
	 * @param array $rule_data Rule data.
	 * @return array
	 */
	function dey_format_order_pickup_location_restriction_rule_data( $rule_data ) {
		return wp_parse_args( $rule_data, dey_get_default_order_pickup_location_restriction_rule_data() );
	}
}

if ( ! function_exists( 'dey_display_action' ) ) {
	/**
	 * Display the post action.
	 *
	 * @since 4.0.0
	 * @param string $status Status.
	 * @param int    $id Post ID.
	 * @param string $current_url Current URL.
	 * @param bool   $action Action.
	 * @return string|HTML
	 */
	function dey_display_action( $status, $id, $current_url, $action = false ) {
		switch ( $status ) {
			case 'edit':
				$status_name = '<span class="dashicons dashicons-edit"></span>';
				$title       = __( 'Edit', 'delivery-slots-for-woocommerce' );
				break;

			case 'active':
				$status_name = '<img src="' . esc_url( DEY_PLUGIN_URL . '/assets/images/button-off.png' ) . '"/>';
				$title       = __( 'Activate', 'delivery-slots-for-woocommerce' );
				break;

			case 'inactive':
				$status_name = '<img src="' . esc_url( DEY_PLUGIN_URL . '/assets/images/button-on.png' ) . '"/>';
				$title       = __( 'Deactivate', 'delivery-slots-for-woocommerce' );
				break;

			case 'duplicate':
				$status_name = '<img src="' . esc_url( DEY_PLUGIN_URL . '/assets/images/copy.png' ) . '"/>';
				$title       = __( 'Duplicate', 'delivery-slots-for-woocommerce' );
				break;

			case 'delete':
				$status_name = '<span class="dashicons dashicons-trash"></span>';
				$title       = __( 'Delete Permanently', 'delivery-slots-for-woocommerce' );
				break;
		}

		$section_name = $action ? 'action' : 'section';

		switch ( $status ) {
			case 'active':
			case 'inactive':
				$status_post = 'active' === $status ? 'activate-post_' : 'deactivate-post_';
				return '<a href="' . esc_url( wp_nonce_url( $current_url, $status_post . $id ) ) . '" title="' . $title . '">' . $status_name . '</a>';

			case 'delete':
				return '<a class="dey-action dey-delete-post" data-action="' . $status . '" href="' . get_delete_post_link($id, '', true) . '" title="' . $title . '">' . $status_name . '</a>';

			case 'duplicate':
				$custom_post_link = wp_nonce_url(add_query_arg( array( 'post' => $id, 'action' => $status ), $current_url ), "$status-post_{$id}");
				return '<a class="dey-action dey-duplicate-post dey-' . $status . '-post" data-action="' . $status . '" href="' . $custom_post_link . '" title="' . $title . '">' . $status_name . '</a>';

			default:
				return '<a href="' . esc_url( add_query_arg( array( $section_name => $status, 'post' => $id ), $current_url ) ) . '" title="' . $title . '">' . $status_name . '</a>';
		}
	}
}

if ( ! function_exists( 'dey_get_countries' ) ) {

	/**
	 * Get the all countries.
	 *
	 * @since 4.0.0
	 * @return array
	 */
	function dey_get_countries() {
		return is_object( WC()->countries ) ? WC()->countries->get_countries() : array();
	}
}

if ( ! function_exists( 'dey_get_shipping_methods' ) ) {

	/**
	 * Get shipping methods.
	 *
	 * @since 4.0.0
	 * @return array
	 */
	function dey_get_shipping_methods() {
		$shipping_zones          = WC_Shipping_Zones::get_zones();
		$active_shipping_methods = array( '' => __( 'All Shipping', 'delivery-slots-for-woocommerce' ) );

		if ( dey_check_is_array( $shipping_zones ) ) {
			foreach ( $shipping_zones as $zone ) {
				$shipping_zone = new WC_Shipping_Zone( $zone['zone_id'] );
				if ( ! is_object( $shipping_zone ) ) {
					continue;
				}

				$zone_shipping_methods = $shipping_zone->get_shipping_methods();
				if ( ! dey_check_is_array( $zone_shipping_methods ) ) {
					continue;
				}

				foreach ( $zone_shipping_methods as $shipping_method_id => $shipping_method ) {
					if ( $shipping_method->is_enabled() ) {
						$active_shipping_methods[ $shipping_method_id ] = $shipping_zone->get_zone_name() . ' - ' . $shipping_method->get_title();
					}
				}
			}
		}

		/**
		* This hook is used to alter the active shipping methods.
		*
		* @since 4.0.0
		*/
		return apply_filters( 'dey_active_shipping_methods', $active_shipping_methods );
	}
}
