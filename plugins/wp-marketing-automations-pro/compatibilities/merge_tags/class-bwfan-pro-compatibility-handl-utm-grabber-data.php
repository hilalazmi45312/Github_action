<?php

/**
 * Plugin: https://wordpress.org/plugins/woo-advanced-shipment-tracking
 * Class BWFAN_Handl_Utm_Grabber_Data
 */

class BWFAN_Handl_Utm_Grabber_Data extends Cart_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'handl_utm_grabber_data';
		$this->tag_description = __( 'HandL UTM Grabber Data', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_handl_utm_grabber_data', array( $this, 'parse_shortcode' ) );
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Show the html in popup for the merge tag.
	 */
	public function get_view() {
		$handle_utm_fields = $this->get_view_data();
		$this->get_back_button();
		?>
        <div class="bwfan_mtag_wrap">
            <div class="bwfan_label">
                <label for="" class="bwfan-label-title"><?php esc_html_e( 'Select Handl UTM Grabber', 'wp-marketing-automations-pro' ); ?></label>
            </div>
            <div class="bwfan_label_val">
                <select id="" class="bwfan-input-wrapper bwfan_tag_select" name="data">
					<?php
					foreach ( $handle_utm_fields as $slug => $name ) {
						echo '<option value="' . esc_attr__( $slug ) . '">' . esc_attr__( $name ) . '</option>';
					}
					?>
                </select>
            </div>
        </div>
		<?php

		if ( $this->support_fallback ) {
			$this->get_fallback();
		}

		$this->get_preview();
		$this->get_copy_button();
	}

	public function get_view_data() {
		$handler_merge_key = array(
			'utm_campaign'       => __( 'UTM Campaign', 'wp-marketing-automations-pro' ),
			'utm_source'         => __( 'UTM Source', 'wp-marketing-automations-pro' ),
			'utm_term'           => __( 'UTM Term', 'wp-marketing-automations-pro' ),
			'utm_medium'         => __( 'UTM Medium', 'wp-marketing-automations-pro' ),
			'utm_content'        => __( 'UTM Content', 'wp-marketing-automations-pro' ),
			'gclid'              => __( 'Gclid', 'wp-marketing-automations-pro' ),
			'handl_original_ref' => __( 'Handl Original Reference', 'wp-marketing-automations-pro' ),
			'handl_landing_page' => __( 'Handl Landing Page', 'wp-marketing-automations-pro' ),
			'handl_ip'           => __( 'Handl IP', 'wp-marketing-automations-pro' ),
			'handl_ref'          => __( 'Handl Reference', 'wp-marketing-automations-pro' ),
			'handl_url'          => __( 'Handl URL', 'wp-marketing-automations-pro' ),
		);

		return apply_filters( 'bwfan_external_handl_utm_grabber_key', $handler_merge_key );
	}

	/**
	 * Parse the merge tag and return its value.
	 *
	 * @param $attr
	 *
	 * @return mixed|string|void
	 */
	public function parse_shortcode( $attr ) {
		if ( true === BWFAN_Merge_Tag_Loader::get_data( 'is_preview' ) ) {
			return $this->get_dummy_preview();
		}

		$cart_details  = BWFAN_Merge_Tag_Loader::get_data( 'cart_details' );
		$checkout_data = isset( $cart_details['checkout_data'] ) ? $cart_details['checkout_data'] : '';

		$key = ! isset( $attr['data'] ) ? 'utm_campaign' : $attr['data'];

		/** getting the grabber data from checkout */
		if ( ! empty( $checkout_data ) ) {
			$field_value   = '';
			$checkout_data = json_decode( $checkout_data, true );
			$field_value   = '';
			if ( isset( $checkout_data['handle_utm_grabber'][ $key ] ) ) {
				$field_value = $checkout_data['handle_utm_grabber'][ $key ];
			}

			return $this->parse_shortcode_output( $field_value, $attr );
		}

		/** if order id available then get the details */
		$order_id = $this->get_order_id();
		if ( ! empty( $order_id ) ) {
			$order = wc_get_order( $order_id );
			if ( ! $order instanceof WC_Order ) {
				return $this->parse_shortcode_output( '', $attr );
			}

			$field_value = $order->get_meta( $key );

			return $this->parse_shortcode_output( $field_value, $attr );
		}

		return $this->parse_shortcode_output( '', $attr );
	}

	public function get_order_id() {
		$order_id = absint( BWFAN_Merge_Tag_Loader::get_data( 'wc_order_id' ) );
		$order_id = ( 0 === $order_id ) ? absint( BWFAN_Merge_Tag_Loader::get_data( 'order_id' ) ) : $order_id;

		if ( 0 === $order_id ) {
			$order    = BWFAN_Merge_Tag_Loader::get_data( 'wc_order' );
			$order_id = $order instanceof WC_Order ? absint( BWFAN_Woocommerce_Compatibility::get_order_id( $order ) ) : $order_id;
		}

		return $order_id;
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 */
	public function get_dummy_preview() {
		return '';
	}

	/**
	 * Returns merge tag schema
	 *
	 * @return array[]
	 */
	public function get_setting_schema() {
		$utm_data = $this->get_view_data();
		$options  = [];
		foreach ( $utm_data as $utm_key => $data ) {
			$options[] = [
				'value' => $utm_key,
				'label' => $data,
			];
		}

		return [
			[
				'id'          => 'data',
				'type'        => 'select',
				'options'     => $options,
				'label'       => __( 'Select UTM', 'wp-marketing-automations-pro' ),
				"class"       => 'bwfan-input-wrapper',
				"required"    => true,
				'placeholder' => __( 'Select', 'wp-marketing-automations-pro' ),
				"description" => ""
			]
		];
	}
}

BWFAN_Merge_Tag_Loader::register( 'wc_ab_cart', 'BWFAN_Handl_Utm_Grabber_Data' );
BWFAN_Merge_Tag_Loader::register( 'wc_order', 'BWFAN_Handl_Utm_Grabber_Data' );
