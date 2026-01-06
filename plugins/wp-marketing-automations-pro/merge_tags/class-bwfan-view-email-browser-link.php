<?php

class BWFAN_View_Email_Browser_Link extends BWFAN_Merge_Tag {
	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'view_email_browser_link';
		$this->tag_description = __( 'View Email Browser URL', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_view_email_browser_link', array( $this, 'parse_shortcode' ) );
		$this->support_fallback = false;
		$this->priority         = 24;
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
		if ( true === BWFAN_Merge_Tag_Loader::get_data( 'is_preview' ) ) {
			return $this->get_dummy_preview();
		}

		$url = home_url();

		$link = add_query_arg( array(
			'bwfan-action' => 'view_in_browser'
		), $url );

		return $this->parse_shortcode_output( $link, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 */
	public function get_dummy_preview() {
		return home_url();
	}

}

/**
 * Register this merge tag to a group.
 */
BWFAN_Merge_Tag_Loader::register( 'bwfan_default', 'BWFAN_View_Email_Browser_Link', null, __( 'General', 'wp-marketing-automations-pro' ) );
