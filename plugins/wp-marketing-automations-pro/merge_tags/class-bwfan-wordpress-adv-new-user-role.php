<?php

class BWFAN_Wordpress_Adv_New_User_Role extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'contact_user_role';
		$this->tag_description = __( 'Contact User Role', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_new_user_role', array( $this, 'parse_shortcode' ) ); // legacy
		add_shortcode( 'bwfan_contact_user_new_role', array( $this, 'parse_shortcode' ) );
		add_shortcode( 'bwfan_contact_user_role', array( $this, 'parse_shortcode' ) );

		$this->priority = 15;
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
			return $this->get_dummy_preview();
		}
		/** Case of user ID */
		if ( isset( $get_data['user_id'] ) && ! empty( $get_data['user_id'] ) ) {
			$user_id = $get_data['user_id'];
			$user    = get_user_by( 'id', intval( $user_id ) );

			$user_roles = $this->get_user_roles( $user );
			if ( ! empty( $user_roles ) ) {
				return $this->parse_shortcode_output( $user_roles, $attr );
			}
		}

		/** Case of email */
		if ( ! isset( $get_data['email'] ) || empty( $get_data['email'] ) ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		$user       = get_user_by( 'email', $get_data['email'] );
		$user_roles = $this->get_user_roles( $user );

		return $this->parse_shortcode_output( $user_roles, $attr );
	}

	/**
	 * Get user roles as a string
	 *
	 * @param $user
	 *
	 * @return string
	 */
	protected function get_user_roles( $user ) {
		if ( ! $user instanceof WP_User ) {
			return '';
		}

		$user_roles = $user->roles;
		$user_roles = array_map( function ( $role_slug ) {
			$roles = get_editable_roles();

			return isset( $roles[ $role_slug ] ) ? $roles[ $role_slug ]['name'] : $role_slug;
		}, $user_roles );

		if ( empty( $user_roles ) ) {
			return '';
		}

		return is_array( $user_roles ) ? implode( ', ', $user_roles ) : $user_roles;
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 */
	public function get_dummy_preview() {
		if ( ! method_exists( BWFAN_Merge_Tag::class, 'get_contact_data' ) ) {
			return '-';
		}

		$contact = $this->get_contact_data();

		/** checking contact instance and id */
		if ( ! $contact instanceof WooFunnels_Contact || 0 === absint( $contact->get_id() ) ) {
			return '-';
		}

		$user_id = $contact->get_wpid() ? $contact->get_wpid() : 0;
		if ( empty( $user_id ) ) {
			return '-';
		}

		$user_meta  = get_userdata( $user_id );
		$user_roles = $user_meta->roles;

		return is_array( $user_roles ) ? implode( ',', $user_roles ) : $user_roles;
	}
}

/**
 * Register this merge tag to a group.
 */
BWFAN_Merge_Tag_Loader::register( 'bwf_contact', 'BWFAN_Wordpress_Adv_New_User_Role', null, __( 'Contact', 'wp-marketing-automations-pro' ) );
