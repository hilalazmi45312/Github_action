<?php

#[AllowDynamicProperties]
class BWFCRM_Form_Elementor extends BWFCRM_Form_Base {
	public static $TYPE_FORM = 'form';
	public static $TYPE_POPUP_FORM = 'popup';

	private $total_selections = 3;
	private $source = 'elementor';

	/** Form Submission Captured Data */
	private $form_id = '';
	private $page_id = 0;
	private $form_title = '';
	private $fields = array();
	private $entry = array();
	private $autonami_event = '';
	private $post_id = 0;

	/** Form WP ID of the Submitted Form, obtained from form's page_id and form_id */
	private $submitted_form_wp_id = 0;

	public function get_source() {
		return $this->source;
	}

	/**
	 * @param BWFCRM_Form_Feed $feed
	 *
	 * @return string|void
	 */
	public function get_form_link( $feed ) {
		$url     = '';
		$page_id = $feed->get_data( 'page_id' );
		if ( $page_id ) {
			$url = admin_url( 'post.php?post=' . absint( $page_id ) . '&action=elementor' );
		}

		return $url;
	}

	public function capture_async_submission() {
		$this->form_id    = isset( BWFAN_Common::$events_async_data['form_id'] ) ? BWFAN_Common::$events_async_data['form_id'] : 0;
		$this->page_id    = isset( BWFAN_Common::$events_async_data['page_id'] ) ? BWFAN_Common::$events_async_data['page_id'] : 0;
		$this->form_title = isset( BWFAN_Common::$events_async_data['form_title'] ) ? BWFAN_Common::$events_async_data['form_title'] : '';
		$this->fields     = isset( BWFAN_Common::$events_async_data['fields'] ) ? BWFAN_Common::$events_async_data['fields'] : [];
		$this->entry      = isset( BWFAN_Common::$events_async_data['entry'] ) ? BWFAN_Common::$events_async_data['entry'] : [];
		$this->post_id    = isset( BWFAN_Common::$events_async_data['post_id'] ) ? BWFAN_Common::$events_async_data['post_id'] : 0;

		$this->autonami_event = BWFAN_Common::$events_async_data['event'];

		$this->find_feeds_and_create_contacts();
	}

	public function filter_feeds_for_current_entry() {
		/** @var BWFAN_Elementor_PopUp_Form_Submit|BWFAN_Elementor_Form_Submit $event */
		$event = BWFAN_Core()->sources->get_event( $this->autonami_event );
		if ( is_null( $event ) ) {
			return false;
		}

		return array_filter( array_map( function ( $feed ) use ( $event ) {
			if ( $event instanceof BWFAN_Elementor_Form_Submit ) {
				return $this->elementor_form_submits_is_feed_valid( $feed, $event );
			} else {
				return $this->elementor_popup_form_submits_is_feed_valid( $feed, $event );
			}
		}, $this->feeds ) );
	}

	/**
	 * @param BWFCRM_Form_Feed $feed
	 * @param BWFAN_Elementor_PopUp_Form_Submit|BWFAN_Elementor_Form_Submit $event
	 *
	 * @return BWFCRM_Form_Feed|false
	 */
	private function elementor_form_submits_is_feed_valid( $feed, $event ) {
		$feed_form_id = $feed->get_data( 'form_id' );
		$feed_page_id = $feed->get_data( 'page_id' );

		$type = $feed->get_data( 'form_type' );

		if ( $type !== 'form' ) {
			return false;
		}

		/** Simple Form Submission (not from Global Widget's Form) */
		if ( ( $feed_form_id === $this->form_id ) && ( absint( $feed_page_id ) === absint( $this->post_id ) ) ) {
			return $feed;
		}

		/** Now Check if the form is submitted from Global Widget */
		/** Step 1: Fetch the form_wp_id of Submitted Form, with form's page_id and form_id */
		if ( empty( $this->submitted_form_wp_id ) ) {
			$this->submitted_form_wp_id = $event->maybe_get_global_form_wp_id( $this->page_id, $this->form_id );
		}

		/** Step 2: If submitted_form_wp_id is empty then fetch form using post id */
		if ( empty( $this->submitted_form_wp_id ) ) {
			$this->submitted_form_wp_id = $event->maybe_get_global_form_wp_id( $this->post_id, $this->form_id );
		}

		/**
		 * Step 3: Check if the Submitted Form's form_wp_id matches with the Feed's Form ID,
		 * then it's a Global Widget Form Submission, and it is a match
		 */
		if ( ! empty( $this->submitted_form_wp_id ) && absint( $this->submitted_form_wp_id ) === absint( $feed_form_id ) ) {
			return $feed;
		}

		/** Return false on no match from either of the Simple Form, or Global Form */
		return false;
	}

	/**
	 * @param BWFCRM_Form_Feed $feed
	 *
	 * @return BWFCRM_Form_Feed|false
	 */
	private function elementor_popup_form_submits_is_feed_valid( $feed, $event ) {
		$form_id = $feed->get_data( 'form_id' );
		$page_id = $feed->get_data( 'page_id' );
		$type    = $feed->get_data( 'form_type' );

		if ( $type !== 'popup' ) {
			return false;
		}

		/** Simple Form Submission (not from Global Widget's Form) */
		if ( ( $form_id === $this->form_id ) && ( absint( $page_id ) === absint( $this->post_id ) ) ) {
			return $feed;
		}

		/** Now Check if the form is submitted from Global Widget */
		/** Step 1: Fetch the form_wp_id of Submitted Form, with form's page_id and form_id */
		if ( empty( $this->submitted_form_wp_id ) ) {
			$this->submitted_form_wp_id = $event->maybe_get_global_form_wp_id( $this->page_id, $this->form_id );
		}

		/**
		 * Step 2: Check if the Submitted Form's form_wp_id matches with the Feed's Form ID,
		 * then it's a Global Widget Form Submission, and it is a match
		 */
		if ( ! empty( $this->submitted_form_wp_id ) && absint( $this->submitted_form_wp_id ) === absint( $form_id ) ) {
			return $feed;
		}

		/** Return false on no match from either of the Simple Form, or Global Form */
		return false;
	}

	public function prepare_contact_data_from_feed_entry( $mapped_fields ) {
		$contact_data = array();
		foreach ( $this->entry as $key => $item ) {
			if ( isset( $mapped_fields[ $key ] ) ) {
				$contact_field                  = is_numeric( $mapped_fields[ $key ] ) ? absint( $mapped_fields[ $key ] ) : $mapped_fields[ $key ];
				$contact_data[ $contact_field ] = is_array( $this->entry[ $key ] ) ? wp_json_encode( $this->entry[ $key ] ) : $this->entry[ $key ];
			}
		}

		return $contact_data;
	}

	public function get_form_fields( $feed ) {
		if ( ! $feed instanceof BWFCRM_Form_Feed ) {
			return BWFCRM_Common::crm_error( __( 'Feed  not Exists: ', 'wp-marketing-automations-pro' ) );
		}
		$feed_id = $feed->get_id();
		if ( ! $feed->is_feed_exists() ) {
			return BWFCRM_Common::crm_error( __( 'No Feed Exists ', 'wp-marketing-automations-pro' ) );
		}

		$form_type = $feed->get_data( 'form_type' );
		$page_id   = absint( $feed->get_data( 'page_id' ) );
		$form_id   = $feed->get_data( 'form_id' );
		if ( empty( $form_type ) || empty( $page_id ) || empty( $form_id ) ) {
			return BWFCRM_Common::crm_error( __( 'Form Feed doesn\'t have sufficient data to get fields: ' . $feed_id, 'wp-marketing-automations-pro' ) );
		}

		$forms       = $this->get_form_options( $page_id, $form_type, 'other' );
		$form        = is_array( $forms ) && isset( $forms[ $form_id ] ) ? $forms[ $form_id ] : array();
		$form_fields = is_array( $form ) && is_array( $form['form_fields'] ) ? $form['form_fields'] : array();

		$return_fields = array();
		foreach ( $form_fields as $field ) {
			$return_fields[ $field['field_slug'] ] = $field['field_label'];
		}

		return $return_fields;
	}

	public function is_selected_form_valid( $feed ) {
		if ( ! $feed instanceof BWFCRM_Form_Feed ) {
			return false;
		}

		$data = $feed->get_data();
		if ( ! is_array( $data ) || empty( $data ) ) {
			return false;
		}

		$steps = $this->get_form_selection_valaidation( $data, true );

		return $this->verify_selected_data( $steps, $data );
	}

	public function get_form_selection_valaidation( $args, $return_all_available = false ) {
		$all_selections = array();

		/** Form Type Handling */
		$form_type = ! empty( $args['form_type'] ) ? $args['form_type'] : false;
		if ( empty( $form_type ) || true === $return_all_available ) {
			$type_options = $this->get_step_selection_array( __( 'Form Type', 'wp-marketing-automations-pro' ), 'form_type', 1, array(
				'default' => array( // default (No Option Group Heading)
					self::$TYPE_FORM       => __( 'Form (Page/Global Widget)', 'wp-marketing-automations-pro' ),
					self::$TYPE_POPUP_FORM => __( 'Popup Form', 'wp-marketing-automations-pro' ),
				),
			) );

			if ( ! $return_all_available ) {
				return $type_options;
			} else {
				$all_selections = array_replace( $all_selections, $type_options );
			}
		}

		/** Page ID Handling */
		$page_id = isset( $args['page_id'] ) && ! empty( $args['page_id'] ) ? $args['page_id'] : false;
		if ( ( empty( $page_id ) || true === $return_all_available ) && ! empty( $form_type ) ) {
			global $wpdb;
			$query          = $wpdb->prepare( "SELECT p.id as post_id, p.post_title as post_title, p.post_type as type FROM {$wpdb->prefix}posts as p JOIN {$wpdb->prefix}postmeta as pm WHERE p.id = pm.post_id AND p.id = %d AND pm.meta_key = %s AND pm.meta_value LIKE %s", $page_id, '_elementor_data', '%"widgetType":"form"%' );
			$page_options   = $wpdb->get_results( $query, ARRAY_A );//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$return_options = array();
			foreach ( $page_options as $post ) {
				$return_options['Page'][ $post['post_id'] ] = $post['post_title'];;
			}

			$type_text      = self::$TYPE_FORM === $form_type ? __( 'Page', 'wp-marketing-automations-pro' ) : __( 'Popup', 'wp-marketing-automations-pro' );
			$return_options = $this->get_step_selection_array( $type_text, 'page_id', 2, $return_options );

			if ( ! $return_all_available ) {
				return $return_options;
			} else {
				$all_selections = array_replace( $all_selections, $return_options );
			}
		}

		/** Form ID Handling */
		$form_id = isset( $args['form_id'] ) && ! empty( $args['form_id'] ) ? $args['form_id'] : false;

		if ( ( empty( $form_id ) || true === $return_all_available ) && ! empty( $page_id ) ) {
			$form_options = $this->get_form_options( absint( $page_id ), $form_type );
			$form_options = array( 'default' => $form_options );
			$form_options = $this->get_step_selection_array( __( 'Form', 'wp-marketing-automations-pro' ), 'form_id', 3, $form_options );

			if ( ! $return_all_available ) {
				return $form_options;
			} else {
				$all_selections = array_replace( $all_selections, $form_options );
			}
		}

		/** $return_all_available is true; */
		return $all_selections;
	}

	public function get_form_selection( $args, $return_all_available = false ) {
		$all_selections = array();

		/** Form Type Handling */
		$form_type = isset( $args['form_type'] ) && ! empty( $args['form_type'] ) ? $args['form_type'] : false;
		if ( empty( $form_type ) || true === $return_all_available ) {
			$type_options = $this->get_step_selection_array( __( 'Form Type', 'wp-marketing-automations-pro' ), 'form_type', 1, array(
				'default' => array( // default (No Option Group Heading)
					self::$TYPE_FORM       => __( 'Form (Page/Global Widget)', 'wp-marketing-automations-pro' ),
					self::$TYPE_POPUP_FORM => __( 'Popup Form', 'wp-marketing-automations-pro' ),
				),
			) );

			if ( ! $return_all_available ) {
				return $type_options;
			} else {
				$all_selections = array_replace( $all_selections, $type_options );
			}
		}

		/** Page ID Handling */
		$page_id = isset( $args['page_id'] ) && ! empty( $args['page_id'] ) ? $args['page_id'] : false;
		if ( ( empty( $page_id ) || true === $return_all_available ) && ! empty( $form_type ) ) {
			$page_options = $this->get_page_options( $form_type );
			$type_text    = self::$TYPE_FORM === $form_type ? __( 'Page', 'wp-marketing-automations-pro' ) : __( 'Popup', 'wp-marketing-automations-pro' );
			$page_options = $this->get_step_selection_array( $type_text, 'page_id', 2, $page_options );

			if ( ! $return_all_available ) {
				return $page_options;
			} else {
				$all_selections = array_replace( $all_selections, $page_options );
			}
		}

		/** Form ID Handling */
		$form_id = isset( $args['form_id'] ) && ! empty( $args['form_id'] ) ? $args['form_id'] : false;
		if ( ( empty( $form_id ) || true === $return_all_available ) && ! empty( $page_id ) ) {
			$form_options = $this->get_form_options( absint( $page_id ), $form_type );
			$form_options = array( 'default' => $form_options );
			$form_options = $this->get_step_selection_array( __( 'Form', 'wp-marketing-automations-pro' ), 'form_id', 3, $form_options );

			if ( ! $return_all_available ) {
				return $form_options;
			} else {
				$all_selections = array_replace( $all_selections, $form_options );
			}
		}

		/** $return_all_available is true; */
		return $all_selections;
	}

	public function get_total_selection_steps() {
		return $this->total_selections;
	}

	public function get_meta() {
		return array(
			'form_selection_fields' => array(
				'form_type' => __( 'Form Type', 'wp-marketing-automations-pro' ),
				'page_id'   => __( 'Page ID', 'wp-marketing-automations-pro' ),
				'form_id'   => __( 'Form ID', 'wp-marketing-automations-pro' ),
			),
		);
	}

	public function get_page_options( $form_type ) {
		$is_popup     = self::$TYPE_POPUP_FORM === $form_type;
		$page_options = BWFAN_Elementor_Common::get_elementor_form_by_type( $is_popup, true );
		if ( ! is_array( $page_options ) && empty( $page_options ) ) {
			return array();
		}

		$return_options = array();
		foreach ( $page_options as $option_group ) {
			$title = $option_group['title'];
			if ( ! isset( $option_group['posts'] ) || ! is_array( $option_group['posts'] ) || empty( $option_group['posts'] ) ) {
				continue;
			}

			$group = array();
			foreach ( $option_group['posts'] as $post ) {
				$group[ $post['post_id'] ] = $post['post_title'];
			}

			$return_options[ $title ] = $group;
		}

		return $return_options;
	}

	/**
	 * @param $page_id
	 * @param $form_type
	 * @param string $context
	 *
	 * @return array
	 */
	public function get_form_options( $page_id, $form_type, $context = 'selection' ) {
		$event = $form_type === self::$TYPE_FORM ? 'elementor_form_submit' : 'elementor_popup_form_submit';

		/** @var BWFAN_Elementor_Form_Submit|BWFAN_Elementor_PopUp_Form_Submit $event */
		$event = BWFAN_Core()->sources->get_event( $event );
		if ( is_null( $event ) ) {
			return array();
		}

		$forms        = $event->get_page_form_data( absint( $page_id ) );
		$return_forms = array();
		foreach ( $forms as $form ) {
			switch ( $context ) {
				case 'selection':
					$return_forms[ $form['form_id'] ] = $form['form_name'];
					break;
				case 'other':
				default:
					$return_forms[ $form['form_id'] ] = $form;
			}
		}

		return $return_forms;
	}

	/**
	 * @param $args
	 * @param $feed_id
	 *
	 * @return bool|WP_Error
	 */
	public function update_form_selection( $args, $feed_id ) {
		if ( empty( $feed_id ) ) {
			return BWFCRM_Common::crm_error( __( 'Empty Feed ID provided', 'wp-marketing-automations-pro' ) );
		}

		$form_type = ! empty( $args['form_type'] ) ? $args['form_type'] : false;
		$page_id   = ! empty( $args['page_id'] ) ? $args['page_id'] : false;
		$form_id   = ! empty( $args['form_id'] ) ? $args['form_id'] : false;

		$feed = new BWFCRM_Form_Feed( $feed_id );
		if ( ! $feed->is_feed_exists() ) {
			return BWFCRM_Common::crm_error( sprintf( __( 'Feed with ID not exists: %d', 'wp-marketing-automations-pro' ), $feed_id ) );
		}

		if ( empty( $form_type ) && empty( $page_id ) && empty( $form_id ) && 'elementor' === $feed->get_source() ) {
			return false;
		}

		$feed->unset_data( 'form_type' );
		$feed->unset_data( 'page_id' );
		$feed->unset_data( 'form_id' );
		$feed->get_source() !== 'elementor' && $feed->set_source( 'elementor' );
		! empty( $form_type ) && $feed->set_data( 'form_type', $form_type );
		! empty( $page_id ) && $feed->set_data( 'page_id', absint( $page_id ) );
		! empty( $form_id ) && $feed->set_data( 'form_id', $form_id );

		return ! ! $feed->save( true );
	}
}

if ( bwfan_is_elementorpro_active() ) {
	BWFCRM_Core()->forms->register( 'elementor', 'BWFCRM_Form_Elementor', 'Elementor', array(
		'elementor_form_submit',
		'elementor_popup_form_submit',
	) );
}
