<?php

/**
 * class BWFAN_Contact_User_Meta
 */
class BWFAN_Contact_User_Meta extends BWFAN_Merge_Tag {
	private static $instance = null;
	protected $support_v2 = true;
	protected $support_v1 = false;

	public function __construct() {
		$this->tag_name        = 'contact_user_meta';
		$this->tag_description = __( 'Contact User Meta', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_user_meta', array( $this, 'parse_shortcode' ) );
		add_shortcode( 'bwfan_contact_user_meta', array( $this, 'parse_shortcode' ) );
		$this->priority = 23;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Parse the merge tag and return its value.
	 *
	 * @param $attr
	 *
	 * @return mixed|string|void
	 */
	public function parse_shortcode( $attr ) {
		$get_data = BWFAN_Merge_Tag_Loader::get_data();

		if ( true === $get_data['is_preview'] ) {
			return $this->parse_shortcode_output( $this->get_dummy_preview(), $attr );
		}

		$key = $attr['key'];
		if ( empty( $key ) ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		$user_id    = 0;
		$email      = '';
		$meta_value = '';

		/** in case user ID  */
		if ( isset( $get_data['user_id'] ) && ! empty( $get_data['user_id'] ) ) {
			$user_id    = $get_data['user_id'];
			$meta_value = $this->get_user_data( $user_id, $key );
			if ( ! empty( $meta_value ) ) {
				$meta_value = is_array( $meta_value ) ? wp_json_encode( $meta_value ) : $meta_value;

				return $this->parse_shortcode_output( $meta_value, $attr );
			}
		}

		/** in case email */
		if ( isset( $get_data['email'] ) && ! empty( $get_data['email'] ) && ! is_email( trim( $get_data['email'] ) ) ) {
			$email = $get_data['email'];
			$user  = get_user_by( 'email', $email );

			if ( $user instanceof WP_USER ) {
				$meta_value = $this->get_user_data( $user_id, $key );
				if ( ! empty( $meta_value ) ) {
					$meta_value = is_array( $meta_value ) ? wp_json_encode( $meta_value ) : $meta_value;

					return $this->parse_shortcode_output( $meta_value, $attr );
				}
			}

		}

		/** then get the details using order */
		if ( ! $user_id || ! $email ) {
			$order = null;
			if ( isset( $get_data['wc_order'] ) ) {
				$order = $get_data['wc_order'];
			}
			if ( ! $order instanceof WC_Order && isset( $get_data['order_id'] ) ) {
				$order = wc_get_order( $get_data['order_id'] );
			}
			if ( $order instanceof WC_Order ) {
				if ( ! $user_id ) {
					$user_id = $order->get_user_id();
				}
				if ( ! $email ) {
					$email = $order->get_billing_email();
				}
			}
		}

		if ( ! empty( $user_id ) ) {
			$meta_value = $this->get_user_data( $user_id, $key );
			if ( ! empty( $meta_value ) ) {
				$meta_value = is_array( $meta_value ) ? wp_json_encode( $meta_value ) : $meta_value;

				return $this->parse_shortcode_output( $meta_value, $attr );
			}
		}

		/** get user id from email if still not getting from order */
		if ( empty( $user_id ) && ! empty( $email ) && is_email( trim( $email ) ) ) {
			$user = get_user_by( 'email', $email );
			if ( $user instanceof WP_User ) {
				$meta_value = $this->get_user_data( $user_id, $key );
				if ( ! empty( $meta_value ) ) {
					$meta_value = is_array( $meta_value ) ? wp_json_encode( $meta_value ) : $meta_value;

					return $this->parse_shortcode_output( $meta_value, $attr );
				}
			}
		}

		/** get user id from contact */
		if ( empty( $user_id ) ) {
			$cid = $get_data['cid'];

			if ( ! empty( $cid ) ) {
				$contact = new WooFunnels_Contact( '', '', '', $cid, '' );
				if ( ! $contact instanceof WooFunnels_Contact || ! $contact->get_id() > 0 ) {
					return $this->parse_shortcode_output( '', $attr );
				}

				$user_id    = $contact->get_wpid();
				$meta_value = $this->get_user_data( $user_id, $key );
				if ( ! empty( $meta_value ) ) {
					$meta_value = is_array( $meta_value ) ? wp_json_encode( $meta_value ) : $meta_value;

					return $this->parse_shortcode_output( $meta_value, $attr );
				}
			}
		}

		/** still empty user id then return blank */
		if ( empty( $user_id ) ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		// fetch the meta value of the required meta key for the user
		$meta_value = $this->get_user_data( $user_id, $key );
		$meta_value = is_array( $meta_value ) ? wp_json_encode( $meta_value ) : $meta_value;

		return $this->parse_shortcode_output( $meta_value, $attr );

	}

	/** get user data using user id and key
	 *
	 * @param $user_id
	 *
	 * @return false
	 */
	public function get_user_data( $user_id, $key ) {
		if ( empty( $user_id ) ) {
			return false;
		}
		$user_meta_data = get_user_meta( $user_id, $key, true );

		return $user_meta_data;
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 *
	 */
	public function get_dummy_preview() {
		return '-';
	}

	/**
	 * Return merge tag schema
	 *
	 * @return array[]
	 */
	public function get_setting_schema() {
		return [
			[
				'id'          => 'key',
				'label'       => __( 'Meta Key', 'wp-marketing-automations-pro' ),
				'type'        => 'text',
				'class'       => '',
				'placeholder' => '',
				'hint'        => __( 'Input the correct meta key in order to get the data', 'wp-marketing-automations-pro' ),
				'required'    => true,
				'toggler'     => array(),
			],
		];
	}
}

/**
 * Register this merge tag to a group.
 */
BWFAN_Merge_Tag_Loader::register( 'bwf_contact', 'BWFAN_Contact_User_Meta' );
