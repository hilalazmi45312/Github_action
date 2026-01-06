<?php

final class BWFAN_Pro_WC_Create_Coupon extends BWFAN_Action {

	private static $ins = null;

	protected function __construct() {
		$this->action_name     = __( 'Create Coupon', 'wp-marketing-automations-pro' );
		$this->action_desc     = __( 'This action creates a new personalized coupon for the customer', 'wp-marketing-automations-pro' );
		$this->required_fields = array( 'coupon', 'coupon_name' );
		$this->action_priority = 5;
		$this->support_v2      = true;
		$this->support_v1      = false;
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 *  Make all the data which is required by the current action.
	 * This data will be used while executing the task of this action.
	 *
	 * @param $automation_data
	 * @param $step_data
	 *
	 * @return array|void
	 */
	public function make_v2_data( $automation_data, $step_data ) {
		$data_to_set          = array();
		$data_to_set['email'] = $automation_data['global']['email'];

		if ( ! isset( $step_data['coupon_data'] ) || empty( $step_data['coupon_data'] ) ) {
			return;
		}
		$coupon_details = $step_data['coupon_data'];

		$data_to_set['coupon']           = [];
		$data_to_set['coupon_type']      = isset( $coupon_details['type'] ) ? $coupon_details['type'] : 'new';
		$data_to_set['coupon']['prefix'] = isset( $coupon_details['general'] ) && isset( $coupon_details['general']['coupon_prefix'] ) ? BWFAN_Common::decode_merge_tags( $coupon_details['general']['coupon_prefix'] ) : '';

		if ( $data_to_set['coupon_type'] === 'existing' ) {
			$data_to_set['coupon_id'] = isset( $coupon_details['coupon_id'] ) && isset( $coupon_details['coupon_id']['key'] ) && ! empty( $coupon_details['coupon_id']['key'] ) ? $coupon_details['coupon_id']['key'] : '';
		} else {
			$data_to_set['coupon']['discount_type'] = isset( $coupon_details['general'] ) && isset( $coupon_details['general']['discount_type'] ) ? $coupon_details['general']['discount_type'] : 'percent';
			$data_to_set['coupon']['coupon_amount'] = isset( $coupon_details['general'] ) && isset( $coupon_details['general']['amount'] ) ? $coupon_details['general']['amount'] : 0;

			$data_to_set['coupon']['free_shipping'] = isset( $coupon_details['general'] ) && isset( $coupon_details['general']['allow_shipping'] ) ? $coupon_details['general']['allow_shipping'] : 0;

			/** getting the restrictions info of the coupons */
			if ( isset( $coupon_details['restrictions'] ) ) {
				if ( isset( $coupon_details['restrictions']['min_spend'] ) ) {
					$data_to_set['coupon']['minimum_amount'] = $coupon_details['restrictions']['min_spend'];
				}

				if ( isset( $coupon_details['restrictions']['max_spend'] ) ) {
					$data_to_set['coupon']['maximum_amount'] = $coupon_details['restrictions']['max_spend'];
				}

				if ( isset( $coupon_details['restrictions']['exclude_sale_item'] ) ) {
					$data_to_set['coupon']['exclude_sale_items'] = $coupon_details['restrictions']['exclude_sale_item'];
				}

				/**  individual_use in coupon data */
				if ( isset( $coupon_details['restrictions']['individual_use'] ) ) {
					$data_to_set['coupon']['individual_use'] = $coupon_details['restrictions']['individual_use'];
				}

				/**  restrict_customer_email in coupon data */
				if ( isset( $coupon_details['restrictions']['restrict_customer_email'] ) ) {
					$data_to_set['coupon']['restrict_customer_email'] = $coupon_details['restrictions']['restrict_customer_email'];
				}

				/** passing the product ids in coupon data */
				if ( isset( $coupon_details['restrictions']['products'] ) && is_array( $coupon_details['restrictions']['products'] ) ) {
					$data_to_set['coupon']['product_ids'] = array_map( function ( $product ) {
						return isset( $product['id'] ) ? $product['id'] : "";
					}, $coupon_details['restrictions']['products'] );
				}

				/** passing the excluded product ids in coupon data */
				if ( isset( $coupon_details['restrictions']['exclude_products'] ) && is_array( $coupon_details['restrictions']['exclude_products'] ) ) {
					$data_to_set['coupon']['exclude_product_ids'] = array_map( function ( $product ) {
						return isset( $product['id'] ) ? $product['id'] : "";
					}, $coupon_details['restrictions']['exclude_products'] );
				}

				/** passing the category ids in coupon data */
				if ( isset( $coupon_details['restrictions']['categories'] ) && is_array( $coupon_details['restrictions']['categories'] ) ) {
					$data_to_set['coupon']['product_categories'] = array_map( function ( $category ) {
						return isset( $category['id'] ) ? $category['id'] : "";
					}, $coupon_details['restrictions']['categories'] );
				}

				/** passing the excluded category ids in coupon data */
				if ( isset( $coupon_details['restrictions']['exclude_categories'] ) && is_array( $coupon_details['restrictions']['exclude_categories'] ) ) {
					$data_to_set['coupon']['exclude_product_categories'] = array_map( function ( $category ) {
						return isset( $category['id'] ) ? $category['id'] : "";
					}, $coupon_details['restrictions']['exclude_categories'] );
				}
			}
		}

		/** set the coupon expiry details */
		if ( isset( $coupon_details['general'] ) && isset( $coupon_details['general']['expiry'] ) ) {
			$data_to_set['coupon']['expiry'] = $coupon_details['general']['expiry'];
		}

		if ( isset( $coupon_details['general'] ) && isset( $coupon_details['general']['specific_expiry_type'] ) ) {
			$data_to_set['coupon']['specific_expiry_type'] = $coupon_details['general']['specific_expiry_type'];
		}

		/** get details of the limit usage settings of coupon */
		if ( isset( $coupon_details['limits'] ) ) {
			if ( isset( $coupon_details['limits']['usage_limit_per_coupon'] ) ) {
				$data_to_set['coupon']['usage_limit'] = $coupon_details['limits']['usage_limit_per_coupon'];
			}

			if ( isset( $coupon_details['limits']['usage_limit_per_user'] ) ) {
				$data_to_set['coupon']['usage_limit_per_user'] = $coupon_details['limits']['usage_limit_per_user'];
			}

			if ( isset( $coupon_details['limits']['usage_limit_x_item'] ) ) {
				$data_to_set['coupon']['limit_usage_to_x_items'] = $coupon_details['limits']['usage_limit_x_item'];
			}
		}

		/** If opid (Optin form unique id) in automation data */
		if ( isset( $automation_data['global']['opid'] ) ) {
			$data_to_set['coupon']['opid'] = isset( $automation_data['global']['opid'] ) ? $automation_data['global']['opid'] : '';
		}

		if ( isset( $automation_data['global']['order_id'] ) ) {
			$data_to_set['coupon']['order_id'] = isset( $automation_data['global']['order_id'] ) ? $automation_data['global']['order_id'] : '';
		}

		return $data_to_set;
	}

	public function process_v2() {
		$data = $this->data;

		$save = apply_filters( 'bwfan_save_coupon', false );
		if ( $save ) {
			/** Check if coupon is already saved in automation meta */
			$coupon = $this->maybe_coupon_already_saved();
			if ( ! empty( $coupon ) ) {
				$data['coupon_name'] = $coupon;
				$this->set_data( $data );

				/** update new coupon value in automation contact row */
				$automation_contact_id = $data['automation_contact_id'];
				$this->update_automation_contact_row( $automation_contact_id, $coupon );

				return $this->prepare_response( wc_get_coupon_id_by_code( $coupon ) );
			}
		}

		/** Coupon name */
		$coupon_prefix  = $data['coupon']['prefix'];
		$dynamic_string = BWFAN_Common::get_dynamic_string( 6 );

		$coupon_name = $coupon_prefix . $dynamic_string;

		/** set new coupon meta */
		$new_coupon_meta['_is_bwfan_coupon']     = 1;
		$new_coupon_meta['_bwfan_automation_id'] = $data['automation_id'];
		$new_coupon_meta['_bwfan_step_id']       = $data['step_id'];
		$new_coupon_meta['expiry_date']          = '';
		$new_coupon_meta['date_expires']         = '';
		$new_coupon_meta['customer_email']       = array();

		$new_coupon_meta['usage_limit'] = isset( $data['coupon']['usage_limit'] ) ? $data['coupon']['usage_limit'] : '';
		$specific_expiry_type           = isset( $data['coupon']['specific_expiry_type'] ) ? intval( $data['coupon']['specific_expiry_type'] ) : 0;
		/** set coupon expiry dates */
		if ( $specific_expiry_type > 0 && $specific_expiry_type !== 3 && isset( $data['coupon']['expiry'] ) && ! empty( $data['coupon']['expiry'] ) && 0 < absint( $data['coupon']['expiry'] ) ) {
			$expiry                          = $this->get_expiry_dates_v2( $data['coupon']['expiry'], $specific_expiry_type );
			$new_coupon_meta['expiry_date']  = $expiry['expire_on'];
			$new_coupon_meta['date_expires'] = $expiry['expiry_timestamped'];
		}

		if ( isset( $data['coupon_type'] ) && $data['coupon_type'] === 'existing' ) {
			if ( ! isset( $data['coupon_id'] ) || empty( $data['coupon_id'] ) ) {
				return $this->error_response( __( 'Coupon ID is required for existing coupon type', 'wp-marketing-automations-pro' ) );
			}

			$coupon_id = $data['coupon_id'];
			$coupon    = new WC_Coupon( $coupon_id );

			if ( ! $coupon instanceof WC_Coupon ) {
				return $this->error_response( __( 'Coupon does not exist', 'wp-marketing-automations-pro' ) );
			}

			/** creating new coupon */
			$new_coupon_id = $this->create_coupon( $coupon_name, $new_coupon_meta );
			if ( is_array( $new_coupon_id ) && count( $new_coupon_id ) > 0 ) {
				/** Some error occurred while making coupon post */
				return $this->prepare_response( $coupon_id );
			}
			$new_coupon = new WC_Coupon( $new_coupon_id );
			// Get existing coupon meta
			$existing_coupon_meta = get_post_meta( $coupon_id );
			// Copy existing coupon meta to new coupon
			foreach ( $existing_coupon_meta as $key => $values ) {
				foreach ( $values as $value ) {
					// Ensure to exclude core meta keys that should not be duplicated
					if ( ! in_array( $key, array(
						'_edit_lock',
						'_edit_last',
						'date_expires',
						'usage_limit',
						'usage_count',
						'expiry_date',
						'_is_bwfan_coupon',
						'_bwfan_automation_id',
						'_bwfan_step_id'
					) ) ) {
						add_post_meta( $new_coupon_id, $key, maybe_unserialize( $value ) );
					}
				}
			}

			if ( isset( $data['coupon']['limit_usage_to_x_items'] ) && ! empty( $data['coupon']['limit_usage_to_x_items'] ) ) {
				$new_coupon->set_limit_usage_to_x_items( $data['coupon']['limit_usage_to_x_items'] );
			}

			if ( isset( $data['coupon']['usage_limit_per_user'] ) && ! empty( $data['coupon']['usage_limit_per_user'] ) ) {
				$new_coupon->set_usage_limit_per_user( $data['coupon']['usage_limit_per_user'] );
			}

			// Save the new coupon
			$new_coupon->save();
		} else {
			if ( ! isset( $data['coupon']['discount_type'] ) || ! array_key_exists( $data['coupon']['discount_type'], wc_get_coupon_types() ) ) {
				return $this->error_response( __( 'Invalid discount type: ' . $data['coupon']['discount_type'], 'wp-marketing-automations-pro' ) );
			}

			/** creating new coupon */
			$coupon_id = $this->create_coupon( $coupon_name, $new_coupon_meta );
			if ( is_array( $coupon_id ) && count( $coupon_id ) > 0 ) {
				/** Some error occurred while making coupon post */
				return $this->prepare_response( $coupon_id );
			}

			/** restricted coupon with customer email */
			if ( isset( $data['coupon']['restrict_customer_email'] ) && 1 === absint( $data['coupon']['restrict_customer_email'] ) ) {
				$this->handle_coupon_restriction( $coupon_id, $data['email'] );
			}

			/** passing other coupon details */
			$this->handle_other_coupon_restriction( $coupon_id, $data['coupon'] );
		}

		/** update new coupon value in automation contact row */
		$automation_contact_id = $data['automation_contact_id'];
		$this->update_automation_contact_row( $automation_contact_id, $coupon_name );

		/** using special property to end the current automation process for a contact */
		BWFAN_Common::$end_v2_current_contact_automation = true;

		do_action( 'bwfan_coupon_created_v2', $coupon_id, $coupon_name, $data );

		/** Set coupon name in data */
		$data['coupon_name'] = $coupon_name;
		$this->set_data( $data );

		if ( $save ) {
			/** Save coupon in optin form table or order meta */
			$this->save_coupon_in_object( $coupon_name );
		}

		return $this->prepare_response( $coupon_id );
	}

	/**
	 * update new coupon value in automation contact row
	 *
	 * @param $automation_contact_id
	 * @param $coupon_name
	 *
	 * @return void
	 */
	public function update_automation_contact_row( $automation_contact_id, $coupon_name ) {
		$row_data = $this->get_automation_contact_row( $automation_contact_id );
		if ( empty( $row_data ) ) {
			return;
		}
		$data                             = $this->data;
		$coupons_data                     = isset( $row_data['coupons'] ) && is_array( $row_data['coupons'] ) ? $row_data['coupons'] : [];
		$coupons_data[ $data['step_id'] ] = $coupon_name;
		$row_data['coupons']              = $coupons_data;

		BWFAN_Model_Automation_Contact::update( array(
			'data' => json_encode( $row_data )
		), array(
			'ID' => $automation_contact_id,
		) );

	}

	/**
	 * Save coupon in automation meta
	 *
	 * @param $coupon_name
	 *
	 * @return void
	 */
	public function save_coupon_in_object( $coupon_name ) {
		$automation_sid = $this->data['step_id'];
		$opid           = isset( $this->data['coupon'] ) && isset( $this->data['coupon']['opid'] ) ? $this->data['coupon']['opid'] : '';
		if ( ! empty( $opid ) ) {
			$data                               = $this->get_optin_data( $opid );
			$data['coupons'][ $automation_sid ] = $coupon_name;
			global $wpdb;
			$wpdb->update( "{$wpdb->prefix}bwf_optin_entries", [ 'data' => json_encode( $data ) ], [ 'opid' => $opid ] ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			return;
		}

		$order = wc_get_order( $this->data['coupon']['order_id'] );
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		$coupons                    = $order->get_meta( 'bwfan_coupons' );
		$coupons                    = is_array( $coupons ) ? $coupons : [];
		$coupons[ $automation_sid ] = $coupon_name;

		$order->update_meta_data( 'bwfan_coupons', $coupons );
		$order->save_meta_data();
	}

	/**
	 * @param $opid
	 *
	 * @return array
	 */
	public function get_optin_data( $opid ) {
		global $wpdb;
		$optin_data = $wpdb->get_var( $wpdb->prepare( "SELECT `data` FROM {$wpdb->prefix}bwf_optin_entries WHERE `opid`=%s", $opid ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$optin_data = json_decode( $optin_data, true );

		return ! is_array( $optin_data ) ? [] : $optin_data;
	}

	/**
	 * Get automation contact row data only
	 *
	 * @param $automation_contact_id
	 *
	 * @return mixed|void|null
	 */
	public function get_automation_contact_row( $automation_contact_id ) {
		if ( empty( $automation_contact_id ) ) {
			return;
		}

		$automation_contact_data = BWFAN_Model_Automation_Contact::get_data( $automation_contact_id );
		if ( empty( $automation_contact_data ) ) {
			return;
		}

		return json_decode( $automation_contact_data['data'], true );
	}

	public function handle_other_coupon_restriction( $coupon_id, $coupon_data ) {
		if ( empty( $coupon_data ) ) {
			return;
		}

		$coupon = new WC_Coupon( $coupon_id );
		if ( ! $coupon instanceof WC_Coupon ) {
			return;
		}

		/** set coupon type */
		$coupon->set_discount_type( $coupon_data['discount_type'] );
		$coupon->set_amount( $coupon_data['coupon_amount'] );
		$coupon->set_free_shipping( $coupon_data['free_shipping'] );

		if ( isset( $coupon_data['minimum_amount'] ) ) {
			$coupon->set_minimum_amount( $coupon_data['minimum_amount'] );
		}

		if ( isset( $coupon_data['maximum_amount'] ) ) {
			$coupon->set_maximum_amount( $coupon_data['maximum_amount'] );
		}

		if ( isset( $coupon_data['exclude_sale_items'] ) ) {
			$coupon->set_exclude_sale_items( $coupon_data['exclude_sale_items'] );
		}

		if ( isset( $coupon_data['individual_use'] ) ) {
			$coupon->set_individual_use( $coupon_data['individual_use'] );
		}

		if ( isset( $coupon_data['product_ids'] ) ) {
			$coupon->set_product_ids( $coupon_data['product_ids'] );
		}

		if ( isset( $coupon_data['exclude_product_ids'] ) ) {
			$coupon->set_excluded_product_ids( $coupon_data['exclude_product_ids'] );
		}

		if ( isset( $coupon_data['product_categories'] ) ) {
			$coupon->set_product_categories( $coupon_data['product_categories'] );
		}

		if ( isset( $coupon_data['exclude_product_categories'] ) ) {
			$coupon->set_excluded_product_categories( $coupon_data['exclude_product_categories'] );
		}

		if ( isset( $coupon_data['usage_limit'] ) ) {
			$coupon->set_usage_limit( $coupon_data['usage_limit'] );
		}

		if ( isset( $coupon_data['usage_limit_per_user'] ) ) {
			$coupon->set_usage_limit_per_user( $coupon_data['usage_limit_per_user'] );
		}

		if ( isset( $coupon_data['limit_usage_to_x_items'] ) ) {
			$coupon->set_limit_usage_to_x_items( $coupon_data['limit_usage_to_x_items'] );
		}

		$coupon->save();

		return $coupon;
	}

	/** set expiry date for newly created coupon
	 *
	 * @param $expiry
	 * @param $specific_expiry_type
	 *
	 * @return array
	 */
	public function get_expiry_dates_v2( $expiry, $specific_expiry_type ) {
		$dbj = new DateTime();
		if ( $specific_expiry_type == 2 ) { // in case specific date mention
			$exptime = strtotime( strval( $expiry ) );
			$dbj->setTimestamp( $exptime );
		} else {
			$expiry  += 1;
			$exptime = strtotime( "+{$expiry} days" );
			$dbj->setTimestamp( $exptime );
		}

		$exp_date         = $dbj->format( 'Y-m-d' );
		$exp_date_email   = date( 'Y-m-d', $exptime );
		$expiry_timestamp = $exptime;

		return array(
			'expiry'             => $exp_date,
			'expire_on'          => $exp_date_email,
			'expiry_timestamped' => $expiry_timestamp,
		);
	}

	public function create_coupon( $coupon_name, $meta_data ) {
		$args = array(
			'post_type'   => 'shop_coupon',
			'post_status' => 'publish',
			'post_title'  => $coupon_name,
		);

		$coupon_id = wp_insert_post( $args );
		if ( ! is_wp_error( $coupon_id ) ) {
			$meta_data['usage_count'] = 0;
			if ( is_array( $meta_data ) && count( $meta_data ) > 0 ) {
				foreach ( $meta_data as $key => $val ) {
					update_post_meta( $coupon_id, $key, $val );
				}
			}

			return $coupon_id;
		}

		return array(
			'err_msg' => $coupon_id->get_error_message(),
		);
	}

	public function prepare_response( $result ) {
		if ( is_array( $result ) && count( $result ) > 0 ) { // Error in coupon creation
			return $this->error_response( $result['err_msg'] );
		}

		if ( ! is_integer( $result ) ) {
			return $this->error_response( __( 'Coupon does not exist', 'wp-marketing-automations-pro' ) );
		}

		$get_type = get_post_field( 'post_type', $result );
		if ( 'shop_coupon' !== $get_type ) {
			return $this->error_response( __( 'Coupon does not exist', 'wp-marketing-automations-pro' ) );
		}

		return $this->success_message( "Coupon {$this->data['coupon_name']} created." );
	}

	/**
	 * @param $coupon_id
	 * @param $email
	 *
	 * @return void|WC_Coupon
	 */
	public function handle_coupon_restriction( $coupon_id, $email ) {
		if ( ! is_email( $email ) ) {
			return;
		}

		$coupon = new WC_Coupon( $coupon_id );
		if ( 0 === $coupon->get_id() ) {
			return;
		}

		$coupon->set_email_restrictions( [ $email ] );
		$coupon->save();

		return $coupon;
	}

	/**
	 * Execute the current action.
	 * Return 3 for successful execution , 4 for permanent failure.
	 *
	 * @param $action_data
	 *
	 * @return array
	 */
	public function execute_action( $action_data ) {
		$this->set_data( $action_data['processed_data'] );
		$result = $this->process();
		if ( $this->fields_missing ) {
			return array(
				'status'  => 4,
				'message' => $result['body'][0],
			);
		}

		if ( is_array( $result ) && count( $result ) > 0 ) { // Error in coupon creation
			return array(
				'status'  => 4,
				'message' => $result['err_msg'],
			);
		}

		if ( ! is_integer( $result ) ) {
			return array(
				'status'  => 4,
				'message' => __( 'Coupon does not exist', 'wp-marketing-automations-pro' ),
			);
		}
		$get_type = get_post_field( 'post_type', $result );
		if ( 'shop_coupon' !== $get_type ) {
			return array(
				'status'  => 4,
				'message' => __( 'Coupon does not exist', 'wp-marketing-automations-pro' ),
			);
		}

		return array(
			'status'  => 3,
			'message' => "Coupon {$this->data['coupon_name']} created."
		);
	}

	public function get_fields_schema() {
		return [
			[
				'id'                  => 'coupon_data',
				'type'                => 'create_coupon',
				'required'            => true,
				'coupon_type_options' => BWFAN_Common::prepared_field_options( wc_get_coupon_types() ),
			]
		];
	}

	public function get_desc_text( $data ) {
		$data = json_decode( wp_json_encode( $data ), true );
		if ( ! isset( $data['coupon_data'] ) || empty( $data['coupon_data'] ) || ! isset( $data['coupon_data']['general'] ) ) {
			return '';
		}

		return isset( $data['coupon_data']['general']['title'] ) ? $data['coupon_data']['general']['title'] : '';
	}

	/**
	 * Get saved coupon in automation meta
	 *
	 * @return false|mixed
	 */
	public function maybe_coupon_already_saved() {
		$sid      = $this->data['step_id'];
		$opid     = $this->data['coupon']['opid'] ?? '';
		$order_id = $this->data['coupon']['order_id'] ?? '';

		if ( empty( $opid ) && empty( $order_id ) ) {
			return false;
		}

		if ( ! empty( $opid ) ) {
			$optin_data = $this->get_optin_data( $opid );

			return $optin_data['coupons'][ $sid ] ?? '';
		}

		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return false;
		}
		$coupons = $order->get_meta( 'bwfan_coupons' );

		return $coupons[ $sid ] ?? '';
	}
}

/**
 * Register this action. Registering the action will make it eligible to see it on single automation screen in select actions dropdown.
 */
return 'BWFAN_Pro_WC_Create_Coupon';
