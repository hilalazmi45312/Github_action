<?php
/**
 * Product local pickup.
 *
 * @since 3.5.0
 * */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'DEY_Product_Local_Pickup' ) ) {

	/**
	 * Class.
	 *
	 * @since 3.5.0
	 */
	class DEY_Product_Local_Pickup extends DEY_Post {

		/**
		 * Post type.
		 *
		 * @since 3.5.0
		 * @var string
		 */
		protected $post_type = DEY_Register_Post_Types::PRODUCT_LOCAL_PICKUP_POSTTYPE;

		/**
		 * Post status.
		 *
		 * @since 3.5.0
		 * @var string
		 */
		protected $post_status = 'dey_pending_payment';

		/**
		 * Product ID.
		 *
		 * @since 3.5.0
		 * @var int
		 */
		protected $product_id;

		/**
		 * Created date.
		 *
		 * @since 3.5.0
		 * @var string
		 */
		protected $created_date;

		/**
		 * Product.
		 *
		 * @since 3.5.0
		 * @var object
		 */
		protected $product;

		/**
		 * Order.
		 *
		 * @since 3.5.0
		 * @var object
		 */
		protected $order;

		/**
		 * Meta data keys.
		 *
		 * @since 3.5.0
		 * @var array
		 */
		protected $meta_data_keys = array(
			'dey_order_id'                   => '',
			'dey_quantity'                   => '',
			'dey_pickup_charge'              => '',
			'dey_pickup_date'                => '',
			'dey_pickup_date_gmt'            => '',
			'dey_pickup_last_date'           => '',
			'dey_pickup_last_date_gmt'       => '',
			'dey_timezone'                   => '',
			'dey_pickup_time_mode'           => '',
			'dey_time_slot_from'             => '',
			'dey_time_slot_to'               => '',
			'dey_time_slot_id'               => '',
			'dey_special_day_id'             => '',
			'dey_currency'                   => '',
			'dey_user_id'                    => '',
			'dey_user_name'                  => '',
			'dey_user_email'                 => '',
			'dey_pickup_email_reminder_sent' => '',
			'dey_pickup_location'            => '',
			'dey_pickup_charge_details'      => '',
		);

		/**
		 * Prepare extra post data.
		 *
		 * @since 3.5.0
		 * @return void
		 */
		protected function load_extra_postdata() {
			$this->product_id   = $this->post->post_parent;
			$this->created_date = $this->post->post_date_gmt;
		}

		/**
		 * Get the product.
		 *
		 * @since 3.5.0
		 * @return object|bool
		 */
		public function get_product() {
			if ( isset( $this->product ) ) {
				return $this->product;
			}

			$this->product = wc_get_product( $this->get_product_id() );

			return $this->product;
		}

		/**
		 * Get the product name.
		 *
		 * @since 3.5.0
		 * @param bool $linkable Whether to return the product name as a link or not.
		 * @return string|html
		 * */
		public function get_product_name( $linkable = false ) {
			if ( ! is_object( $this->get_product() ) ) {
				return '';
			}

			if ( ! $linkable ) {
				return $this->get_product()->get_title();
			}

			return sprintf( '<a href="%s">%s</a>', esc_url( $this->get_product()->get_permalink() ), esc_html( $this->get_product()->get_title() ) );
		}

		/**
		 * Get the order.
		 *
		 * @since 3.5.0
		 * @return object|bool
		 */
		public function get_order() {
			if ( isset( $this->order ) ) {
				return $this->order;
			}

			$this->order = wc_get_order( $this->get_order_id() );

			return $this->order;
		}

		/**
		 * Is time slot mode?.
		 *
		 * @since 3.5.0
		 * @return bool
		 */
		public function is_time_slot_mode() {
			return ( '3' === $this->get_pickup_time_mode() );
		}

		/**
		 * Get the formatted created datetime.
		 *
		 * @since 3.5.0
		 * @return string
		 */
		public function get_formatted_created_date() {
			return DEY_Date_Time::get_wp_format_datetime_from_gmt( $this->get_created_date() );
		}

		/**
		 * Get the formatted pickup date.
		 *
		 * @since 3.5.0
		 * @return string
		 */
		public function get_formatted_pickup_date() {
			return dey_format_pickup_date( $this->get_pickup_date(), $this->get_pickup_time_mode() );
		}

		/**
		 * Get the formatted time slot from.
		 *
		 * @since 3.5.0
		 * @return string
		 */
		public function get_formatted_time_slot_from() {
			return dey_format_pickup_time_slot( $this->get_time_slot_from() );
		}

		/**
		 * Get the formatted time slot to.
		 *
		 * @since 3.5.0
		 * @return string
		 */
		public function get_formatted_time_slot_to() {
			return dey_format_pickup_time_slot( $this->get_time_slot_to() );
		}

		/**
		 * Display the time slots.
		 *
		 * @since 3.5.0
		 * @param string $separator The separator.
		 * @return string
		 */
		public function get_formatted_time_slots( $separator = ' - ' ) {
			if ( ! $this->is_time_slot_mode() ) {
				return '';
			}

			return dey_format_product_pickup_time_slots( $this->get_time_slot_from(), $this->get_time_slot_to(), $this->get_time_slot_id(), $separator );
		}

		/**
		 * Get the formatted expected local pickup date message.
		 *
		 * @since 3.5.0
		 * @return string
		 */
		public function get_formatted_expected_local_pickup_date_msg() {
			return dey_get_expected_product_local_pickup_date_message( $this->get_pickup_date(), $this->get_pickup_last_date() );
		}

		/**
		 * Get the formatted pickup charge.
		 *
		 * @since 3.5.0
		 * @return string
		 */
		public function get_formatted_pickup_charge() {
			return dey_price( $this->get_pickup_charge(), array( 'currency' => $this->get_currency() ) );
		}

		/**
		 * Get the order product quantity.
		 *
		 * @since 3.5.0
		 * @return string
		 */
		public function get_order_product_quantity() {
			if ( $this->get_quantity() ) {
				return $this->get_quantity();
			}

			// Return empty if the order does not exist.
			if ( ! is_object( $this->get_order() ) ) {
				return '';
			}

			$quantity = '';
			foreach ( $this->get_order()->get_items() as $item_id => $item ) {
				if ( ! isset( $item['dey_product_pickup_id'] ) ) {
					continue;
				}

				if ( $item['dey_product_pickup_id'] != $this->get_id() ) {
					continue;
				}

				$quantity = $item['quantity'];
			}

			return $quantity;
		}

		/**
		 * Get pickup location name.
		 *
		 * @since 3.5.0
		 * @return string
		 */
		public function get_pickup_location_name() {
			if ( ! dey_check_is_array( $this->get_pickup_location() ) ) {
				return '';
			}

			$pickup_location = $this->get_pickup_location();
			if ( ! isset( $pickup_location['name'] ) ) {
				return '';
			}

			return $pickup_location['name'];
		}

		/**
		 * Get formatted pickup address.
		 *
		 * @since 3.5.0
		 * @return string
		 */
		public function get_formatted_pickup_address() {
			if ( ! dey_check_is_array( $this->get_pickup_location() ) ) {
				return '';
			}

			$pickup_location = $this->get_pickup_location();
			if ( ! isset( $pickup_location['address'] ) || ! dey_check_is_array( $pickup_location['address'] ) ) {
				return '';
			}

			return implode( ', ', array_filter( $pickup_location['address'] ) );
		}

		/**
		 * Get pickup location admin emails.
		 *
		 * @since 3.5.0
		 * @return string
		 */
		public function get_pickup_location_admin_emails() {
			if ( ! dey_check_is_array( $this->get_pickup_location() ) ) {
				return '';
			}

			$pickup_location = $this->get_pickup_location();
			if ( ! isset( $pickup_location['admin_emails'] ) ) {
				return '';
			}

			return $pickup_location['admin_emails'];
		}

		/**
		 * Is as soon as possible time slot?.
		 *
		 * @since 3.7.0
		 * @return boolean
		 */
		public function is_as_soon_as_possible_time_slot() {
			return ( 'soon' === $this->get_time_slot_id() );
		}

		/**
		 * ----------------------------------------------------------------
		 * Setters.
		 * ----------------------------------------------------------------
		 * Functions for setting product local pickup data.
		 */

		/**
		 * Set product ID
		 *
		 * @since 3.5.0
		 * @param int $product_id Product ID.
		 * @return void
		 */
		public function set_product_id( $product_id ) {
			$this->product_id = $product_id;
		}

		/**
		 * Set created date.
		 *
		 * @since 3.5.0
		 * @param string $created_date Created date.
		 * @return void
		 */
		public function set_created_date( $created_date ) {
			$this->created_date = $created_date;
		}

		/**
		 * Set order ID.
		 *
		 * @since 3.5.0
		 * @param int $order_id Order ID.
		 * @return void
		 */
		public function set_order_id( $order_id ) {
			$this->set_prop( 'dey_order_id', $order_id );
		}

		/**
		 * Set local pickup charge.
		 *
		 * @since 3.5.0
		 * @param int|float $pickup_charge Pickup charge.
		 * @return void
		 */
		public function set_pickup_charge( $pickup_charge ) {
			$this->set_prop( 'dey_pickup_charge', $pickup_charge );
		}

		/**
		 * Set pickup charge details.
		 *
		 * @since 3.5.0
		 * @param array $price_details Price details.
		 * @return void
		 */
		public function set_pickup_charge_details( $price_details ) {
			$this->set_prop( 'dey_pickup_charge_details', $price_details );
		}

		/**
		 * Set quantity.
		 *
		 * @since 3.5.0
		 * @param int $quantity Quantity.
		 * @return void
		 */
		public function set_quantity( $quantity ) {
			$this->set_prop( 'dey_quantity', $quantity );
		}

		/**
		 * Set pickup date.
		 *
		 * @since 3.5.0
		 * @param string $pickup_date Pickup date.
		 * @return void
		 */
		public function set_pickup_date( $pickup_date ) {
			$this->set_prop( 'dey_pickup_date', $pickup_date );
		}

		/**
		 * Set pickup date GMT.
		 *
		 * @since 3.5.0
		 * @param string $pickup_date_gmt Pickup date GMT.
		 * @return void
		 */
		public function set_pickup_date_gmt( $pickup_date_gmt ) {
			$this->set_prop( 'dey_pickup_date_gmt', $pickup_date_gmt );
		}

		/**
		 * Set pickup last date.
		 *
		 * @since 3.5.0
		 * @param string $pickup_last_date Pickup last date.
		 * @return void
		 */
		public function set_pickup_last_date( $pickup_last_date ) {
			$this->set_prop( 'dey_pickup_last_date', $pickup_last_date );
		}

		/**
		 * Set pickup last date GMT.
		 *
		 * @since 3.5.0
		 * @param string $pickup_last_date_gmt Pickup last date GMT.
		 * @return void
		 */
		public function set_pickup_last_date_gmt( $pickup_last_date_gmt ) {
			$this->set_prop( 'dey_pickup_last_date_gmt', $pickup_last_date_gmt );
		}

		/**
		 * Set time zone.
		 *
		 * @since 3.5.0
		 * @param string $timezone Timezone.
		 * @return void
		 */
		public function set_timezone( $timezone ) {
			$this->set_prop( 'dey_timezone', $timezone );
		}

		/**
		 * Set pickup time mode.
		 *
		 * @since 3.5.0
		 */
		public function set_pickup_time_mode( $value ) {
			$this->set_prop( 'dey_pickup_time_mode', $value );
		}

		/**
		 * Set time slot from.
		 *
		 * @since 3.5.0
		 */
		public function set_time_slot_from( $value ) {
			$this->set_prop( 'dey_time_slot_from', $value );
		}

		/**
		 * Set time slot to.
		 *
		 * @since 3.5.0
		 */
		public function set_time_slot_to( $value ) {
			$this->set_prop( 'dey_time_slot_to', $value );
		}

		/**
		 * Set time slot ID.
		 *
		 * @since 3.5.0
		 */
		public function set_time_slot_id( $value ) {
			$this->set_prop( 'dey_time_slot_id', $value );
		}

		/**
		 * Set special day ID.
		 *
		 * @since 3.5.0
		 */
		public function set_special_day_id( $value ) {
			$this->set_prop( 'dey_special_day_id', $value );
		}

		/**
		 * Set currency.
		 *
		 * @since 3.5.0
		 */
		public function set_currency( $value ) {
			$this->set_prop( 'dey_currency', $value );
		}

		/**
		 * Set user ID.
		 *
		 * @since 3.5.0
		 */
		public function set_user_id( $value ) {
			$this->set_prop( 'dey_user_id', $value );
		}

		/**
		 * Set user name.
		 *
		 * @since 3.5.0
		 */
		public function set_user_name( $value ) {
			$this->set_prop( 'dey_user_name', $value );
		}

		/**
		 * Set user email.
		 *
		 * @since 3.5.0
		 */
		public function set_user_email( $value ) {
			$this->set_prop( 'dey_user_email', $value );
		}

		/**
		 * Set pickup email reminder sent.
		 *
		 * @since 3.5.0
		 */
		public function set_pickup_email_reminder_sent( $value ) {
			$this->set_prop( 'dey_pickup_email_reminder_sent', $value );
		}

		/**
		 * Set pickup location data.
		 *
		 * @since 3.5.0
		 * @param array $pickup_location Pickup location data.
		 * @return void
		 */
		public function set_pickup_location_id( $pickup_location ) {
			$this->set_prop( 'dey_pickup_location', $location_id );
		}

		/**
		 * ----------------------------------------------------------------
		 * Getters.
		 * ----------------------------------------------------------------
		 * Functions for getting product local pickup data.
		 */

		/**
		 * Get product ID.
		 *
		 * @since 3.5.0
		 * @return string
		 */
		public function get_product_id() {
			return $this->product_id;
		}

		/**
		 * Get created date.
		 *
		 * @since 3.5.0
		 * @return string
		 */
		public function get_created_date() {
			return $this->created_date;
		}

		/**
		 * Get order ID.
		 *
		 * @since 3.5.0
		 * @return string
		 */
		public function get_order_id() {
			return $this->get_prop( 'dey_order_id' );
		}

		/**
		 * Get quantity.
		 *
		 * @since 3.5.0
		 * @return string
		 */
		public function get_quantity() {
			return $this->get_prop( 'dey_quantity' );
		}

		/**
		 * Get pickup charge.
		 *
		 * @since 3.5.0
		 * @return string
		 */
		public function get_pickup_charge() {
			return $this->get_prop( 'dey_pickup_charge' );
		}

		/**
		 * Get pickup charge details.
		 *
		 * @since 3.5.0
		 * @return array
		 */
		public function get_pickup_charge_details() {
			return $this->get_prop( 'dey_pickup_charge_details' );
		}

		/**
		 * Get pickup date.
		 *
		 * @since 3.5.0
		 * @return string
		 */
		public function get_pickup_date() {
			return $this->get_prop( 'dey_pickup_date' );
		}

		/**
		 * Get pickup date GMT.
		 *
		 * @since 3.5.0
		 * @return string
		 */
		public function get_pickup_date_gmt() {
			return $this->get_prop( 'dey_pickup_date_gmt' );
		}

		/**
		 * Get pickup last date.
		 *
		 * @since 3.5.0
		 * @return string
		 */
		public function get_pickup_last_date() {
			return $this->get_prop( 'dey_pickup_last_date' );
		}

		/**
		 * Get pickup last date GMT.
		 *
		 * @since 3.5.0
		 * @return string
		 */
		public function get_delivery_last_date_gmt() {
			return $this->get_prop( 'dey_delivery_last_date_gmt' );
		}

		/**
		 * Get time zone.
		 *
		 * @since 3.5.0
		 * @return string
		 */
		public function get_timezone() {
			return $this->get_prop( 'dey_timezone' );
		}

		/**
		 * Get pickup time mode.
		 *
		 * @since 3.5.0
		 * @return string
		 */
		public function get_pickup_time_mode() {
			return $this->get_prop( 'dey_pickup_time_mode' );
		}

		/**
		 * Get time slot from.
		 *
		 * @since 3.5.0
		 * @return string
		 */
		public function get_time_slot_from() {
			return $this->get_prop( 'dey_time_slot_from' );
		}

		/**
		 * Get time slot to.
		 *
		 * @since 3.5.0
		 * @return string
		 */
		public function get_time_slot_to() {
			return $this->get_prop( 'dey_time_slot_to' );
		}

		/**
		 * Get time slot ID.
		 *
		 * @since 3.5.0
		 * @return string|int
		 */
		public function get_time_slot_id() {
			return $this->get_prop( 'dey_time_slot_id' );
		}

		/**
		 * Get special day ID.
		 *
		 * @since 3.5.0
		 * @return string|int
		 */
		public function get_special_day_id() {
			return $this->get_prop( 'dey_special_day_id' );
		}

		/**
		 * Get currency.
		 *
		 * @since 3.5.0
		 * @return string
		 */
		public function get_currency() {
			return $this->get_prop( 'dey_currency' );
		}

		/**
		 * Get user ID.
		 *
		 * @since 3.5.0
		 * @return string|int
		 */
		public function get_user_id() {
			return $this->get_prop( 'dey_user_id' );
		}

		/**
		 * Get user name.
		 *
		 * @since 3.5.0
		 * @return string
		 */
		public function get_user_name() {
			return $this->get_prop( 'dey_user_name' );
		}

		/**
		 * Get user email.
		 *
		 * @since 3.5.0
		 * @return string
		 */
		public function get_user_email() {
			return $this->get_prop( 'dey_user_email' );
		}

		/**
		 * Get pickup email reminder sent.
		 *
		 * @since 3.5.0
		 * @return string
		 */
		public function get_pickup_email_reminder_sent() {
			return $this->get_prop( 'dey_pickup_email_reminder_sent' );
		}

		/**
		 * Get pickup location data.
		 *
		 * @since 3.5.0
		 * @return array
		 */
		public function get_pickup_location() {
			return $this->get_prop( 'dey_pickup_location' );
		}
	}
}
