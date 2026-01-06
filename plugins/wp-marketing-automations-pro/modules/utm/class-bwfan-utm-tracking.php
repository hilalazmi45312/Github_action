<?php

#[AllowDynamicProperties]
class BWFAN_UTM_Tracking {

	private static $ins = null;
	private $utm_parameters = [];

	public function __construct() {
		add_filter( 'bwfan_before_send_email_body', [ $this, 'maybe_add_utm_parameters' ], 10, 2 );
	}

	public static function get_instance() {
		if ( is_null( self::$ins ) ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function maybe_add_utm_parameters( $body, $data, $mode = 'email' ) {
		/** Check for UTM parameters */
		if ( ! isset( $data['append_utm'] ) || 1 !== absint( $data['append_utm'] ) ) {
			return $body;
		}

		if ( isset( $data['utm_source'] ) && ! empty( $data['utm_source'] ) ) {
			$this->utm_parameters['utm_source'] = $this->get_prepared_utm( $data['utm_source'] );
		}

		if ( isset( $data['utm_medium'] ) && ! empty( $data['utm_medium'] ) ) {
			$this->utm_parameters['utm_medium'] = $this->get_prepared_utm( $data['utm_medium'] );
		}

		if ( isset( $data['utm_name'] ) && ! empty( $data['utm_name'] ) ) {
			$this->utm_parameters['utm_campaign'] = $this->get_prepared_utm( $data['utm_name'] );
		}

		if ( isset( $data['utm_term'] ) && ! empty( $data['utm_term'] ) ) {
			$this->utm_parameters['utm_term'] = $this->get_prepared_utm( $data['utm_term'] );
		}

		if ( isset( $data['utm_content'] ) && ! empty( $data['utm_content'] ) ) {
			$this->utm_parameters['utm_content'] = $this->get_prepared_utm( $data['utm_content'] );
		}

		$this->utm_parameters = apply_filters( 'bwfan_utm_parameters', $this->utm_parameters, $data );

		$regex_pattern = BWFAN_PRO_Common::get_regex_pattern( 'email' !== $mode ? 3 : 1 );

		return preg_replace_callback( $regex_pattern, function ( $matches ) use ( $mode ) {
			/** According to Href (1) regex, URL is at 1 index. And for Link (3) Regex, 0 index. */
			$url = 'email' !== $mode ? $matches[0] : $matches[1];

			/** Check url needs to exclude from click track */
			if ( BWFAN_PRO_Common::is_exclude_url( $url ) ) {
				return $matches[0];
			}

			$url = $this->append_tracking_click_utm_parameters( $url );

			/** In case of Href regex, replace old URL with new one in 'HREF' string, otherwise return */
			return 'email' !== $mode ? $url : str_replace( $matches[1], $url, $matches[0] );
		}, $body );
	}

	public function append_tracking_click_utm_parameters( $string ) {
		if ( empty( $string ) ) {
			return '';
		}

		/** Check url needs to exclude */
		if ( BWFAN_PRO_Common::is_exclude_url( $string ) ) {
			return $string;
		}

		/** In case of unsubscribe link, don't append */
		if ( strstr( $string, 'bwfan-action=unsubscribe' ) ) {
			return $string;
		}

		/** In case of email open url, don't append */
		if ( strstr( $string, 'bwfan-track-action=open' ) ) {
			return $string;
		}

		return add_query_arg( $this->utm_parameters, $string );
	}

	/**
	 * Remove space and replace with '+' from string
	 *
	 * @param $utm
	 *
	 * @return array|string|string[]
	 */
	public function get_prepared_utm( $utm ) {
		return str_replace( " ", "+", trim( $utm ) );
	}
}

BWFAN_UTM_Tracking::get_instance();
