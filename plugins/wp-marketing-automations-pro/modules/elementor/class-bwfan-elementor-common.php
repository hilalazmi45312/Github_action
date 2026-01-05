<?php

use ElementorPro\Plugin;

class BWFAN_Elementor_Common {

	public static function is_popup( $post_id ) {
		if ( empty( $post_id ) ) {
			return false;
		}

		return 'popup' === get_post_meta( $post_id, '_elementor_template_type', true );
	}

	public static function get_elementor_form_by_type( $is_popup = false, $group = false ) {
		global $wpdb;

		$popup_sql_operator = ( true === $is_popup ? '=' : '!=' );

		$get_post_type_sql   = ( true === $group ) ? ', p.post_type as type ' : ' ';
		$get_post_type_sql_2 = ( true === $group ) ? ', p.type as type ' : ' ';

		$get_elementor_pages_sql = "SELECT p.id as pid, p.post_title as title$get_post_type_sql
            FROM $wpdb->posts as p JOIN $wpdb->postmeta as pm 
            WHERE p.id = pm.post_id 
            AND pm.meta_key = '_elementor_template_type' 
            AND pm.meta_value $popup_sql_operator 'popup' 
            AND p.post_type != 'revision'
			AND p.post_status = 'publish'";

		//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$post_data = $wpdb->get_results( "SELECT p.pid as post_id, p.title as post_title$get_post_type_sql_2
            FROM ($get_elementor_pages_sql) as p JOIN $wpdb->postmeta as pm 
            WHERE p.pid = pm.post_id 
            AND pm.meta_key = '_elementor_data' 
            AND pm.meta_value LIKE '%\"widgetType\":\"form\"%'", ARRAY_A );

		if ( true === $group ) {
			$posts_by_groups = array();
			foreach ( $post_data as $post ) {
				$post_type = $post['type'];

				if ( 'elementor_library' === $post_type ) {
					$post_type = get_post_meta( absint( $post['post_id'] ), '_elementor_template_type', true );
				}

				if ( ! isset( $posts_by_groups[ $post_type ] ) || ! is_array( $posts_by_groups[ $post_type ] ) ) {
					$posts_by_groups[ $post_type ] = array();
				}

				$posts_by_groups[ $post_type ]['title']   = self::maybe_get_elementor_type_label( $post_type );
				$posts_by_groups[ $post_type ]['posts'][] = $post;
			}

			return $posts_by_groups;
		}

		return $post_data;
	}

	public static function maybe_get_elementor_type_label( $post_type ) {
		$elementor_types = Plugin::elementor()->documents->get_document_types();
		if ( isset( $elementor_types[ $post_type ] ) && is_callable( array( $elementor_types[ $post_type ], 'get_title' ) ) ) {
			return call_user_func( array( $elementor_types[ $post_type ], 'get_title' ) );
		}

		$type_object = get_post_type_object( $post_type );
		if ( ! $type_object instanceof WP_Post_Type || ! isset( $type_object->labels->singular_name ) ) {
			return $post_type;
		}

		return $type_object->labels->singular_name;
	}

	public static function get_elementor_global_form_pages() {
		global $wpdb;

		$get_elementor_pages_sql = "SELECT p.id as pid, p.post_title as title 
            FROM $wpdb->posts as p JOIN $wpdb->postmeta as pm 
            WHERE p.id = pm.post_id 
            AND pm.meta_key = '_elementor_template_type' 
            AND pm.meta_value IN ('wp-page', 'page','wp-post') 
            AND p.post_type != 'revision'
			AND p.post_status = 'publish'";

		//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$post_data = $wpdb->get_results( "SELECT pid as post_id, title as post_title 
            FROM ($get_elementor_pages_sql) as p JOIN $wpdb->postmeta as pm 
            WHERE p.pid = pm.post_id 
            AND pm.meta_key = '_elementor_data' 
            AND pm.meta_value LIKE '%\"widgetType\":\"global\"%'", ARRAY_A );

		$posts = array_map( function ( $row ) {
			$document = Plugin::elementor()->documents->get( absint( $row['post_id'] ) );
			if ( $document ) {
				$data = $document->get_elements_data();
			}

			$page_with_form = false;
			Plugin::elementor()->db->iterate_data( $data, function ( $element ) use ( &$page_with_form ) {
				if ( ! isset( $element['widgetType'] ) || 'global' !== $element['widgetType'] || ! isset( $element['templateID'] ) ) {
					return $element;
				}

				$global_widget_template = get_post_meta( absint( $element['templateID'] ), '_elementor_template_widget_type', true );
				if ( 'form' === $global_widget_template ) {
					$page_with_form = true;
				}

				return $element;
			} );

			return true === $page_with_form ? $row : false;
		}, $post_data );

		$posts = array_filter( $posts );

		return $posts;
	}

	public static function get_forms_by_global_form_page( $post_id ) {
		$document = Plugin::elementor()->documents->get( absint( $post_id ) );
		$data     = [];
		if ( $document ) {
			$data = $document->get_elements_data();
		}

		$forms = array();
		Plugin::elementor()->db->iterate_data( $data, function ( $element ) use ( &$forms ) {
			if ( ! isset( $element['widgetType'] ) || 'global' !== $element['widgetType'] || ! isset( $element['templateID'] ) ) {
				return $element;
			}

			$global_widget_template = get_post_meta( absint( $element['templateID'] ), '_elementor_template_widget_type', true );
			if ( 'form' !== $global_widget_template ) {
				return $element;
			}

			$form = get_post_meta( absint( $element['templateID'] ), '_elementor_data', true );
			$form = json_decode( $form, true );
			if ( ! is_array( $form ) || 0 === count( $form ) ) {
				return $element;
			}

			$form                 = $form[0];
			$form['widget_wp_id'] = $form['id'];
			$form['id']           = $element['id'];
			$forms[]              = $form;

			return $element;
		} );

		return $forms;
	}

	/**
	 * @param $post_id
	 *
	 * @return array
	 */
	public static function get_forms_by_page_id( $post_id ) {
		if ( ! class_exists( 'ElementorPro\Plugin' ) ) {
			return [];
		}

		$document = Plugin::elementor()->documents->get( intval( $post_id ) );
		if ( ! $document ) {
			return [];
		}
		$data  = $document->get_elements_data();
		$forms = [];
		Plugin::elementor()->db->iterate_data( $data, function ( $element ) use ( &$forms ) {
			if ( ! isset( $element['widgetType'] ) || 'global' !== $element['widgetType'] ) {
				foreach ( $element['elements'] as $element ) {
					if ( isset( $element['id'] ) ) {
						$forms[] = $element['id'];
					}
				}
			}
		} );

		return $forms;
	}

}
