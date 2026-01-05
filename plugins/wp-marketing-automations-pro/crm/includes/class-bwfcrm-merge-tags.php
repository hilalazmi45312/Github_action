<?php
/**
 * Campaign Controller Class
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BWFCRM_Contact
 *
 */
#[AllowDynamicProperties]
class BWFCRM_Merge_Tags {
	private static $ins = null;

	private $_merge_tags = array();
	private $_tag_groups = array();
	private $_data = array();

	private $_fields_merge_tags = [
		/** Contact Columns */
		'email'         => 'contact_email',
		'f_name'        => 'contact_first_name',
		'l_name'        => 'contact_last_name',
		'contact_no'    => 'contact_phone',
		'timezone'      => 'contact_timezone',
		'creation_date' => 'contact_creation_date',
		'status'        => 'contact_marketing_status',
		'address'       => 'contact_address',
		'country'       => 'contact_country',
		'state'         => 'contact_state',

		/** Contact Fields */
		'company'       => 'contact_company',
		'dob'           => 'contact_dob',
		'gender'        => 'contact_gender',
		'address-1'     => 'contact_address_1',
		'address-2'     => 'contact_address_2',
		'city'          => 'contact_city',
		'postcode'      => 'contact_postcode',
	];

	private $_form_merge_tags = [
		'contact_confirmation_link'
	];

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self;
		}

		return self::$ins;
	}

	public function __construct() {
		add_action( 'bwfan_capture_async_form_submission', [ $this, 'load_merge_tags' ], 8 );
		add_action( 'bwf_as_data_store_set', [ $this, 'load_merge_tags' ], 8 );
		add_action( 'bwfan_rest_call', [ $this, 'load_merge_tags' ], 8 );
	}

	public function load_merge_tags() {
		$dir = __DIR__ . '/merge_tags';
		foreach ( glob( $dir . '/class-*.php' ) as $_field_filename ) {
			require_once( $_field_filename );
		}
	}

	public function register( $slug, $class, $group = 'uncategorized' ) {
		if ( empty( $slug ) ) {
			return;
		}

		if ( ! method_exists( $class, 'get_instance' ) || ! method_exists( $class, 'get_value' ) ) {
			return;
		}

		$this->_merge_tags[ $slug ] = $class;
		if ( ! empty( $group ) ) {
			$this->_tag_groups[ $group ][ $slug ] = $class;
		}

		/** @var BWFCRM_Merge_Tag_Base $merge_tag_obj */
		$merge_tag_obj = call_user_func( array( $class, 'get_instance' ) );
		add_shortcode( 'bwfan_crm_' . $slug, array( $merge_tag_obj, 'shortcode_callback' ) );
	}

	/**
	 * If contact_id provided, merge_tags will have values for the contact
	 *
	 * @param string $string
	 * @param array $merge_tags_data
	 *
	 * @return array
	 */
	public function fetch_tags_from_string( $string, $merge_tags_data = [] ) {
		/** set data in merge tags */
		if ( ! empty( $merge_tags_data ) ) {
			BWFAN_Merge_Tag_Loader::set_data( $merge_tags_data );
		}

		$merge_tags         = BWFAN_Email_Conversations::get_email_merge_tags( $string );
		return $merge_tags;
	}

	public function get_tag_details( $tag ) {
		if ( false === strpos( $tag, ' ' ) ) {
			return [ 'name' => $tag, 'data' => [] ];
		}

		$tag_name = explode( ' ', $tag )[0];
		preg_match_all( '/ (.*?)=[\"\'](.*?)[\"\']/', $tag, $tag_data );
		if ( ! isset( $tag_data[0] ) || empty( $tag_data[0] ) || ! is_array( $tag_data[0] ) ) {
			return [ 'name' => $tag_name, 'data' => [] ];
		}

		unset( $tag_data[0] );
		$tag_data = array_values( $tag_data );

		$final_data = [];
		foreach ( $tag_data[0] as $index => $datum ) {
			$final_data[ $datum ] = $tag_data[1][ $index ];
		}

		return [ 'name' => $tag_name, 'data' => $final_data ];
	}

	public function get_registered_tags( $include_contact_field_tags = true ) {
		$tags = is_array( $this->_merge_tags ) && ! empty( $this->_merge_tags ) ? $this->_merge_tags : [];
		if ( isset( $tags['contact_field'] ) ) {
			unset( $tags['contact_field'] );
		}

		foreach ( $tags as $tag_slug => $tag_class ) {
			/** @var BWFCRM_Merge_Tag_Base $merge_tag_obj */
			$merge_tag_obj     = call_user_func( array( $tag_class, 'get_instance' ) );
			$tags[ $tag_slug ] = $merge_tag_obj->get_name();
		}

		if ( false === $include_contact_field_tags ) {
			return $tags;
		}

		$contact_field_tags = $this->get_contact_fields_tags();

		return array_merge( $tags, $contact_field_tags );
	}

	public function get_registered_grouped_tags( $include_contact_field_tags = true, $form_tags = false ) {
		$tag_groups = $this->_tag_groups;
		if ( isset( $tag_groups['contact_fields'] ) ) {
			unset( $tag_groups['contact_fields'] );
		}

		foreach ( $tag_groups as $group => $tags ) {
			if ( ! is_array( $tags ) || empty( $tags ) ) {
				continue;
			}

			foreach ( $tags as $tag_slug => $tag_class ) {
				/** Don't show deprecated {{contact_marketing_status}} merge tag */
				if ( 'contact_marketing_status' === $tag_slug ) {
					unset( $tags[ $tag_slug ] );
					continue;
				}

				if ( false === $form_tags && in_array( $tag_slug, $this->_form_merge_tags ) ) {
					unset( $tags[ $tag_slug ] );
					continue;
				}

				/** @var BWFCRM_Merge_Tag_Base $merge_tag_obj */
				$merge_tag_obj     = call_user_func( array( $tag_class, 'get_instance' ) );
				$tags[ $tag_slug ] = $merge_tag_obj->get_name();
			}

			$tag_groups[ $group ] = $tags;
		}

		if ( false === $include_contact_field_tags ) {
			return $tag_groups;
		}

		$contact_field_tags = $this->get_contact_fields_tags();
		// merging contact fields merge tags into contact group
		$tag_groups['contact'] = array_merge( $tag_groups['contact'], $contact_field_tags );

		return $tag_groups;
	}

	public function get_contact_fields_tags() {
		$fields = BWFCRM_Fields::get_custom_fields( 1, 1, null );

		$return = [];
		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) || ! isset( $field['slug'] ) ) {
				continue;
			}

			$merge_tag            = 'contact_field key="' . $field['slug'] . '"';
			$return[ $merge_tag ] = $field['name'];
		}

		return $return;
	}

	public function get_data( $key = '' ) {
		if ( empty( $key ) ) {
			return $this->_data;
		}

		return $this->_data[ $key ] ? $this->_data[ $key ] : '';
	}

	public function set_data( $key, $value ) {
		if ( empty( $key ) ) {
			return;
		}

		$this->_data[ $key ] = $value;
	}

	public function get_field_tag( $field_slug ) {
		if ( empty( $field_slug ) ) {
			return '';
		}

		if ( isset( $this->_fields_merge_tags[ $field_slug ] ) ) {
			return "{{{$this->_fields_merge_tags[ $field_slug ]}}}";
		}

		return "{{contact_field key=\"{$field_slug}\"}}";
	}
}

if ( class_exists( 'BWFCRM_Merge_Tags' ) ) {
	BWFCRM_Core::register( 'merge_tags', 'BWFCRM_Merge_Tags' );
}