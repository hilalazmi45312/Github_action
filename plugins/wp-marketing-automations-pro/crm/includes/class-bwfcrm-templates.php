<?php

class BWFCRM_Templates {

	/** return single template or all templates json data
	 *
	 * @param null $template_id
	 * @param int $offset
	 * @param int $limit
	 * @param string $search
	 *
	 * @return false|mixed|string|void
	 */
	public static function get_json( $template_id = null, $offset = 0, $limit = 10, $search = '' ) {
		global $wpdb;

		$tempate_table = $wpdb->prefix . 'bwfan_templates';
		$query         = "SELECT `subject`, `template`, `type`, `title`, `mode`, `data` FROM $tempate_table WHERE 1 = 1 AND `canned` = 1 ";

		// Handle template ID filtering
		if ( is_array( $template_id ) && ! empty( $template_id ) ) {
			$template_id = array_values( $template_id );
			$template_id = implode( ', ', $template_id );
			$query       .= " AND `ID` IN ( " . $template_id . " ) ";
		} else if ( absint( $template_id ) > 0 ) {
			$query .= $wpdb->prepare( " AND `ID` = %d ", $template_id );
		}

		// Handle search filtering
		if ( ! empty( $search ) ) {
			$query .= $wpdb->prepare( " AND `title` LIKE %s ", '%' . $search . '%' );
		}

		if( empty( $template_id ) ) {
			$query .= " LIMIT %d OFFSET %d ";
		}

		$query = $wpdb->prepare( $query, $limit, $offset );

		$templates = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return wp_json_encode( $templates );
	}

	public static function import( $file_data ) {
		$template_id = 0;
		foreach ( $file_data as $data ) {
			if ( empty( $data['template'] ) ) {
				continue;
			}

			$create_time   = current_time( 'mysql', 1 );
			$template_data = [
				'title'      => $data['title'],
				'template'   => $data['template'],
				'subject'    => $data['subject'],
				'type'       => intval( $data['type'] ) > 0 ? intval( $data['type'] ) : 1,
				'mode'       => intval( $data['mode'] ) > 0 ? intval( $data['mode'] ) : 1,
				'canned'     => 1,
				'created_at' => $create_time,
				'updated_at' => $create_time,
				'data'       => BWFAN_Common::is_json( $data['data'] ) ? $data['data'] : wp_json_encode( $data['data'] ),
			];

			$template_id = BWFAN_Model_Templates::bwfan_create_new_template( $template_data );
		}

		return $template_id;
	}

	/**
	 * Get transactional mail by template id
	 *
	 * @param int $template_id
	 *
	 * @return array|false
	 */
	public static function get_transactional_mail_by_template_id( $template_id ) {
		global $wpdb;

		$template_table = $wpdb->prefix . 'bwfan_templates';
		$query = "SELECT `subject`, `template`, `type`,`title`,`mode`,`data` FROM $template_table WHERE `canned` = 0 AND `ID` = %d";
		$query = $wpdb->prepare( $query, $template_id );

		return $wpdb->get_row( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}
}
