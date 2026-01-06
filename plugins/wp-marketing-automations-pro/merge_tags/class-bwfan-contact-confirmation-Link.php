<?php

class BWFAN_Contact_Confirmation_Link extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'contact_confirmation_link';
		$this->tag_description = __( 'Confirmation Link', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_contact_confirmation_link', array( $this, 'parse_shortcode' ) );
		$this->priority         = 10;
		$this->is_crm_broadcast = true;
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
		$data = BWFAN_Merge_Tag_Loader::get_data();
		if ( true === $data['is_preview'] ) {
			return $this->parse_shortcode_output( $this->get_dummy_preview(), $attr );
		}

		/** If Contact ID available */
		$cid = isset( $data['contact_id'] ) ? $data['contact_id'] : '';

		if ( empty( $cid ) ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		if ( ! isset( $data['form_feed_id'] ) || empty( $data['form_feed_id'] ) ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		$contact = new BWFCRM_Contact( absint( $data['contact_id'] ) );
		$feed    = new BWFCRM_Form_Feed( absint( $data['form_feed_id'] ) );
		if ( ! $contact->is_contact_exists() || ! $feed->is_feed_exists() ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		$redirect_url = BWFAN_Common::decode_merge_tags( $feed->get_data( 'redirect_url' ) );
		if ( empty( $redirect_url ) || false === wp_http_validate_url( $redirect_url ) ) {
			$redirect_url = home_url( '/' );
		} else {
			$redirect_url = BWFAN_Common::decode_merge_tags( $redirect_url );
		}

		$args = [
			'bwfan-action' => 'incentive',
			'feed-id'      => absint( $data['form_feed_id'] ),
			'bwfan-uid'    => $contact->contact->get_uid(),
			'bwfan-link'   => urlencode( esc_url_raw( $redirect_url ) ),
		];
		if ( method_exists( BWFAN_Core()->conversation, 'get_link_hash' ) ) {
			$args['l_hash'] = BWFAN_Core()->conversation->get_link_hash( $redirect_url, [
				'oid'  => absint( $data['form_feed_id'] ),
				'type' => 6
			] );
		}


		return add_query_arg( $args, esc_url_raw( home_url( '/' ) ) );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 *
	 */
	public function get_dummy_preview() {
		$get_data     = BWFAN_Merge_Tag_Loader::get_data();
		$redirect_url = home_url( '/' );

		/** If Contact ID available */
		$contact = self::get_preview_contact( $get_data );

		/** If Contact is not available */
		if ( ! $contact instanceof WooFunnels_Contact || empty( $contact->get_id() ) ) {
			return $redirect_url;
		}

		$uid = $contact->get_uid();
		if ( empty( $uid ) ) {
			return $redirect_url;
		}

		/** If Contact is not available */
		return add_query_arg( [
			'bwfan-action' => 'incentive',
			'bwfan-uid'    => $uid,
			'bwfan-link'   => urlencode( esc_url_raw( $redirect_url ) ),
		], esc_url_raw( home_url( '/' ) ) );
	}

	/**
	 * @param $get_data
	 *
	 * @return WooFunnels_Contact|null
	 */
	public static function get_preview_contact( $get_data ) {
		/** if contact id is available */
		if ( ! empty( $get_data['contact_id'] ) ) {
			$contact = new WooFunnels_Contact( '', '', '', $get_data['contact_id'] );
			if ( $contact->get_id() > 0 ) {
				return $contact;
			}
		}

		/** if test email is available */
		if ( ! empty( $get_data['test_email'] ) && is_email( $get_data['test_email'] ) ) {
			$contact = new WooFunnels_Contact( '', $get_data['test_email'] );
			if ( $contact->get_id() > 0 ) {
				return $contact;
			}
		}

		/** if user is logged in */
		$user = wp_get_current_user();
		if ( $user instanceof WP_User && $user->ID > 0 ) {
			$contact = new WooFunnels_Contact( $user->ID );
			if ( $contact->get_id() > 0 ) {
				return $contact;
			}
		}

		return null;
	}
}

/**
 * Register this merge tag to a group.
 */
BWFAN_Merge_Tag_Loader::register( 'bwfan_default', 'BWFAN_Contact_Confirmation_Link', null, __( 'General', 'wp-marketing-automations-pro' ) );
