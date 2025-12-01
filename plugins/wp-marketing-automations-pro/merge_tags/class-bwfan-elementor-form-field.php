<?php

class BWFAN_Elementor_Form_Field extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'elementor_form_field';
		$this->tag_description = __( 'Form Field', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_elementor_form_field', array( $this, 'parse_shortcode' ) );
		add_action( 'wp_ajax_bwfan_get_automation_elementor_form_fields', array( $this, 'bwfan_get_automation_elementor_form_fields' ) );
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
		$this->get_back_button();
		?>
        <label for="" class="bwfan-label-title"><?php echo esc_html__( 'Select Field', 'wp-marketing-automations-pro' ); ?></label>
        <select id="" class="bwfan-input-wrapper bwfan-mb-15 bwfan_tag_select bwfan_elementor_form_fields" style="padding-left:10px;" name="field"></select>
		<?php
		if ( $this->support_fallback ) {
			$this->get_fallback();
		}

		$this->get_preview();
		$this->get_copy_button();
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

		$entry       = BWFAN_Merge_Tag_Loader::get_data( 'entry' );
		$field_value = '';
		if ( isset( $attr['field'] ) && isset( $entry[ $attr['field'] ] ) ) {
			$field_value = is_array( $entry[ $attr['field'] ] ) ? implode( ' ', $entry[ $attr['field'] ] ) : $entry[ $attr['field'] ];

			return $this->parse_shortcode_output( $field_value, $attr );
		}

		/*
		 * handling for elementor popup form
		 * in case of popup, entry contains array with numerical index instead of string index
		 */
		$slug = BWFAN_Merge_Tag_Loader::get_data( 'entry' );
		if ( empty( $field_value ) && 'elementor_popup_form_submit' === $slug ) {
			$form_id     = BWFAN_Merge_Tag_Loader::get_data( 'form_id' );
			$fields      = BWFAN_Merge_Tag_Loader::get_data( 'fields' );
			$field_slug  = empty( $fields[ $form_id ] ) ? [] : array_column( $fields[ $form_id ], 'field_slug' );
			$field_map   = array_search( $attr['field'], $field_slug );
			$field_value = ( ! empty( $field_map ) && isset( $entry[ $field_map ] ) && is_email( $entry[ $field_map ] ) ) ? $entry[ $field_map ] : ( isset( $entry['email'] ) && is_email( $entry['email'] ) ? $entry['email'] : '' );
		}

		return $this->parse_shortcode_output( $field_value, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 *
	 * @todo:Hard values shouldn't be passed
	 */
	public function get_dummy_preview() {
		return 'Test';
	}

	public function bwfan_get_automation_elementor_form_fields() {
		BWFAN_PRO_Common::nocache_headers();
		$finalarr     = [];
		$automationId = absint( sanitize_text_field( $_POST['automationId'] ) );

		/** Check Automation */
		$automation_obj = BWFAN_Automation_V2::get_instance( $automationId );

		/** Check for automation exists */
		if ( empty( $automation_obj->error ) ) {
			$automation_meta = $automation_obj->get_automation_meta_data();

			if ( isset( $automation_meta['event_meta'] ) && isset( $automation_meta['event_meta']['elementor_popup_submit_page_id'] ) ) {
				$page_id = $automation_meta['event_meta']['elementor_popup_submit_page_id'];
				if ( is_array( $page_id ) && $page_id[0] && is_array( $page_id[0] ) && isset( $page_id[0]['id'] ) ) {
					$page_id = intval( $page_id[0]['id'] );
				}
				$page_id = absint( $page_id );
				$form_id = isset( $automation_meta['event_meta']['elementor_submit_form_id'] ) ? sanitize_text_field( $automation_meta['event_meta']['elementor_submit_form_id'] ) : sanitize_text_field( $automation_meta['event_meta']['elementor_popup_submit_form_id'] );
				$fields  = [];
				if ( ! empty( $form_id ) ) {
					$obj            = BWFAN_Elementor_Form_Submit::get_instance();
					$page_form_data = $obj->get_page_form_data( $page_id );
					$form_fields    = $obj->get_form_fields_v2( $form_id, $page_form_data );
					$finalarr       = [];

					foreach ( $form_fields['form_fields'] as $key => $value ) {
						$finalarr[ $key ] = [
							'key'   => $value['field_slug'],
							'value' => isset( $value['field_label'] ) && ! empty( $value['field_label'] ) ? $value['field_label'] : $value['field_slug'],
						];
					}
				}
			}
		}


		wp_send_json( array(
			'results' => $finalarr
		) );
		exit;
	}

	/**
	 * Returns merge tag schema
	 *
	 * @return array[]
	 */
	public function get_setting_schema() {
		return [
			[
				'id'          => 'field',
				'type'        => 'ajax',
				'label'       => __( 'Select Field', 'wp-marketing-automations-pro' ),
				"class"       => 'bwfan-input-wrapper',
				"required"    => true,
				'placeholder' => __( 'Select', 'wp-marketing-automations-pro' ),
				"description" => "",
				"ajax_cb"     => 'bwfan_get_automation_elementor_form_fields',
			]
		];
	}
}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_elementorpro_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'elementor-forms', 'BWFAN_Elementor_Form_Field', null, __( 'Elementor Form', 'wp-marketing-automations-pro' ) );
}
