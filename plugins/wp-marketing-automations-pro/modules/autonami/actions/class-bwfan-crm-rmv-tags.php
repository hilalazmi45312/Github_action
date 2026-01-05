<?php

final class BWFAN_CRM_Rmv_Tag extends BWFAN_Action {

	private static $instance = null;

	private function __construct() {
		$this->action_name     = __( 'Remove Tags', 'wp-marketing-automations-pro' );
		$this->action_desc     = __( 'This action removes the selected tags from the contact', 'wp-marketing-automations-pro' );
		$this->action_priority = 10;
		$this->support_v2      = true;
		$this->required_fields = array( 'remove_tags', 'email' );
	}

	/**
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @return BWFAN_BWFCRM_Rmv_Tag|null
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Localize data for html fields for the current action.
	 */
	public function admin_enqueue_assets() {
		if ( BWFAN_Common::is_load_admin_assets( 'automation' ) ) {
			$data = $this->get_view_data();
			BWFAN_Core()->admin->set_actions_js_data( $this->get_class_slug(), 'tags_options', $data );
		}
	}

	public function get_view_data() {
		$bwfcrm_tags = BWFCRM_Tag::get_tags();
		$tags        = array();

		if ( empty( $bwfcrm_tags ) ) {
			return $tags;
		}

		foreach ( $bwfcrm_tags as $key => $tag ) {
			$tags[ $tag['ID'] ] = $tag['name'];
		}

		return $tags;
	}

	/**
	 * Show the html fields for the current action.
	 */
	public function get_view() {
		$unique_slug = $this->get_slug();

		?>
        <script type="text/html" id="tmpl-action-<?php echo esc_attr__( $unique_slug ); ?>">
            <#
            selected_tags = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'tags')) ? data.actionSavedData.data.tags : {};

            if(_.has(data.actionFieldsOptions, 'tags_options') && _.isObject(data.actionFieldsOptions.tags_options) ) {
            tags_options_clone = data.actionFieldsOptions.tags_options;

            if( _.size(selected_tags) > 0 ) {
            diffTags = _.difference(selected_tags,_.keys(tags_options_clone));

            if(_.size(diffTags) > 0) {
            _.each( diffTags, function( value, key ){
            tags_options_clone[value] = value;
            });

            }
            }
            }
            #>
            <label for="" class="bwfan-label-title">
				<?php
				echo esc_html__( 'Select Tags', 'wp-marketing-automations-pro' );
				$message = __( 'Select available tags and if unable to locate then sync the connector.', 'wp-marketing-automations-pro' );
				echo $this->add_description( $message, '2xl', 'right' ); //phpcs:ignore WordPress.Security.EscapeOutput
				echo $this->inline_merge_tag_invoke(); //phpcs:ignore WordPress.Security.EscapeOutput
				?>
            </label>
            <div class="bwfan_add_tags">
                <div class="bwfan_tags_wrap">
                    <input list="tags" type="text" id="new-tag-bwfan_tag" class="bwfan-input-wrapper" autocomplete="on">
                    <input type="button" class="button bwfan-tag-add" value="Add">
                </div>
                <ul class="tagchecklist" role="list"></ul>
                <select style="display: none" name="bwfan[{{data.action_id}}][data][tags][]" multiple class="bwfan_add_tags_final_value" data-name="tags" data-action="<?php echo esc_attr( $unique_slug ) ?>">
                </select>
            </div>
        </script>
		<?php
	}

	/**
	 * Overrides the parent class method to return new array type values
	 *
	 * @param $dynamic_array
	 * @param $integration_data
	 *
	 * @return array
	 */
	public function parse_merge_tags( $dynamic_array, $integration_data ) {
		return $this->parse_tags_fields( $dynamic_array, $integration_data );
	}

	/**
	 * Make all the data which is required by the current action.
	 * This data will be used while executing the task of this action.
	 *
	 * @param $integration_object BWFAN_Integration
	 * @param $task_meta
	 *
	 * @return array|void
	 */
	public function make_data( $integration_object, $task_meta ) {
		$this->is_action_tag    = true;
		$final_tags             = [];
		$user_input_tags        = isset( $task_meta['data']['tags'] ) ? $task_meta['data']['tags'] : [];
		$data_to_set            = array();
		$data_to_set['email']   = isset( $task_meta['global']['email'] ) ? $task_meta['global']['email'] : '';
		$data_to_set['phone']   = isset( $task_meta['global']['phone'] ) ? $task_meta['global']['phone'] : '';
		$data_to_set['user_id'] = isset( $task_meta['global']['user_id'] ) ? $task_meta['global']['user_id'] : 0;

		/** check if email not exists in global then get from user is if available */
		if ( empty( $data_to_set['email'] ) && ! empty( $data_to_set['user_id'] ) ) {
			$user_data            = get_userdata( $data_to_set['user_id'] );
			$data_to_set['email'] = $user_data instanceof WP_User ? $user_data->user_email : '';
		}

		$db_tags = $this->get_view_data();
		if ( empty( $user_input_tags ) ) {
			$data_to_set['remove_tags'] = array();

			return $data_to_set;
		}

		foreach ( $user_input_tags as $tag_value ) {
			$tags_response = BWFAN_Common::decode_merge_tags( $tag_value );
			$tags          = json_decode( $tags_response );
			if ( is_array( $tags ) && count( $tags ) > 0 ) {
				foreach ( $tags as $single_tag ) {
					$final_tags[] = $single_tag;
				}
				continue;
			}
			$final_tags[] = $tags_response;
		}

		foreach ( $final_tags as $fkey => $final_tag ) {

			/** checking if the id already there than passing the id and value in array */
			if ( ! isset( $db_tags[ $final_tag ] ) ) {
				$tag = BWFCRM_Tag::get_tags( array(), $final_tag );
				if ( empty( $tag ) || ! isset( $tag[0]['ID'] ) ) {
					unset( $final_tags[ $fkey ] );
					continue;
				}

				$final_tag = $tag[0]['ID'];
			}

			$final_tag_data[ $fkey ] = $final_tag;
			unset( $final_tags[ $fkey ] );

		}
		$data_to_set['remove_tags'] = $final_tag_data;

		return $data_to_set;
	}

	public function make_v2_data( $automation_data, $step_data ) {

		$this->is_action_tag    = true;
		$final_tags             = [];
		$user_input_tags        = isset( $step_data['tags'] ) && is_array( $step_data['tags'] ) ? $step_data['tags'] : [];
		$data_to_set            = array();
		$data_to_set['email']   = isset( $automation_data['global']['email'] ) ? $automation_data['global']['email'] : '';
		$data_to_set['phone']   = isset( $automation_data['global']['phone'] ) ? $automation_data['global']['phone'] : '';
		$data_to_set['user_id'] = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : 0;

		/** check if email not exists in global then get from user is if available */
		if ( empty( $data_to_set['email'] ) && ! empty( $data_to_set['user_id'] ) ) {
			$user_data            = get_userdata( $data_to_set['user_id'] );
			$data_to_set['email'] = $user_data instanceof WP_User ? $user_data->user_email : '';
		}

		$db_tags = $this->get_view_data();
		if ( empty( $user_input_tags ) ) {
			$data_to_set['remove_tags'] = array();

			return $data_to_set;
		}

		$data_to_set['remove_tags'] = array_map( function ( $tag ) {
			if ( absint( $tag['id'] ) > 0 ) {
				return $tag['id'];
			}

			/**If mergetag in tag name */
			$tag['name'] = BWFAN_Common::decode_merge_tags( $tag['name'] );
			$tag         = BWFAN_PRO_Common::get_or_create_terms( [ $tag ], BWFCRM_Term_Type::$TAG );

			return isset( $tag[0]['id'] ) ? absint( $tag[0]['id'] ) : 0;
		}, $user_input_tags );

		return $data_to_set;
	}

	/**
	 * Execute the current action.
	 * Return 3 for successful execution , 4 for permanent failure.
	 *
	 * @param $action_data
	 *
	 * @return array
	 */
	public function execute_action( $action_data ) {
		$this->set_data( $action_data['processed_data'] );
		$is_required_fields_present = $this->check_fields( $this->data, $this->required_fields );

		if ( false === $is_required_fields_present ) {
			return $this->show_fields_error();
		}

		$contact = new BWFCRM_Contact( $this->data['email'], true );

		if ( ! $contact->is_contact_exists() && ! empty( $this->data['phone'] ) ) {
			/** get contact id using the phone number */
			$contact_id = BWFAN_Pro_Common::get_contact_id_by_phone( $this->data['phone'] );
			$contact    = new BWFCRM_Contact( $contact_id, false );
			if ( ! $contact->is_contact_exists() ) {
				return array(
					'status'  => 4,
					'message' => __( 'Unable to fetch contact', 'wp-marketing-automations-pro' ),
				);
			}
		}

		$tags_removed = $contact->remove_tags( $this->data['remove_tags'] );
		$contact->save();
		$not_removed_tags  = array_filter( array_values( array_diff( $this->data['remove_tags'], $tags_removed ) ) );
		$not_removed_terms = array();
		if ( ! empty( $not_removed_tags ) ) {
			$not_removed_terms = BWFCRM_Term::get_terms( 1, $not_removed_tags );
		}

		$nice_names = array_map( function ( $tag ) {
			if ( isset( $tag['name'] ) ) {
				return $tag['name'];
			}

		}, $not_removed_terms );

		$tag_message = is_array( $nice_names ) ? implode( ',', $nice_names ) : '';

		if ( ! empty( $tag_message ) ) {
			$message = 'Not all tags removed, except of ' . $tag_message;
		} else {
			$message = 'All Tags removed';
		}

		return array(
			'status'  => 3,
			'message' => __( $message, 'autonami-automation-connector' ),
		);
	}

	public function process_v2() {
		$contact = new BWFCRM_Contact( $this->data['email'], true );

		if ( ! $contact->is_contact_exists() && ! empty( $this->data['phone'] ) ) {
			/** get contact id using the phone number */
			$contact_id = BWFAN_Pro_Common::get_contact_id_by_phone( $this->data['phone'] );
			$contact    = new BWFCRM_Contact( $contact_id, false );
			if ( ! $contact->is_contact_exists() ) {

				return $this->error_response( __( 'Unable to fetch contact', 'wp-marketing-automations-pro' ) );
			}
		}

		$tags_removed = $contact->remove_tags( $this->data['remove_tags'] );
		$contact->save();

		$not_removed_tags  = array_filter( array_values( array_diff( $this->data['remove_tags'], $tags_removed ) ) );
		$not_removed_terms = array();
		if ( ! empty( $not_removed_tags ) ) {
			$not_removed_terms = BWFCRM_Term::get_terms( 1, $not_removed_tags );
		}

		$nice_names = array_map( function ( $tag ) {
			if ( isset( $tag['name'] ) ) {
				return $tag['name'];
			}

		}, $not_removed_terms );

		$tag_message = is_array( $nice_names ) ? implode( ',', $nice_names ) : '';

		if ( ! empty( $tag_message ) ) {
			$message = __( 'Not all tags removed, except of ' . $tag_message, 'wp-marketing-automations-pro' );
		} else {
			$message = __( 'All Tags removed', 'wp-marketing-automations-pro' );
		}

		return $this->success_message( $message );
	}

	public function get_fields_schema() {
		return [
			[
				'id'                  => 'tags',
				"label"               => __( 'Select tag', 'wp-marketing-automations-pro' ),
				"type"                => 'search',
				'autocompleter'       => 'tags',
				"class"               => "bwfan-col-sm-9",
				"tip"                 => __( "Can add available tags.", 'wp-marketing-automations-pro' ),
				"required"            => true,
				"allowFreeTextSearch" => true,
			]
		];
	}

	public function get_desc_text( $data ) {
		$data = json_decode( wp_json_encode( $data ), true );
		if ( ! isset( $data['tags'] ) || empty( $data['tags'] ) ) {
			return '';
		}
		$tags = [];
		foreach ( $data['tags'] as $tag ) {
			if ( ! isset( $tag['name'] ) || empty( $tag['name'] ) ) {
				continue;
			}
			$tags[] = $tag['name'];
		}

		return $tags;
	}
}

return 'BWFAN_CRM_Rmv_Tag';
