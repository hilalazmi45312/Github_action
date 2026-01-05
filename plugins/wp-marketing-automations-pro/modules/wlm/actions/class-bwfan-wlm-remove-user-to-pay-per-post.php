<?php

final class BWFAN_WLM_Remove_User_From_Pay_Per_Post extends BWFAN_Action {

	private static $ins = null;

	protected function __construct() {
		$this->action_name     = __( 'Remove User from Pay Per Post', 'wp-marketing-automations-pro' );
		$this->required_fields = array( 'user_id', 'content_id' );
		$this->support_v2      = true;
		$this->action_priority = 40;
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * Show the html fields for the current action.
	 */
	public function get_view() {
		?>
        <script>
            jQuery(document).ready(function ($) {
                $('body').on('select2:select', '.bwfan-ppp-search', function (e) {
                    var temp_ppp = {};
                    var ppp_data = $(this).select2('data');

                    ppp_data.forEach(function (item) {
                        temp_ppp[item.id] = item.text;
                    });
                    $(this).parent().find('.bwfan_searched_ppp_name').val(JSON.stringify(temp_ppp));
                });
            });
        </script>

        <script type="text/html" id="tmpl-action-<?php echo esc_html__( $this->get_slug() ); ?>">
            <#
            searched_ppp = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'searched_ppp')) ? data.actionSavedData.data.searched_ppp : '';
            selected_content_id = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'content_id')) ? data.actionSavedData.data.content_id : {};
            #>
            <div class="bwfan-input-form clearfix">
                <label for="" class="bwfan-label-title"><?php esc_html_e( 'Select Pay Per Posts', 'wp-marketing-automations-pro' ); ?></label>
                <select required id="" data-search="ppp" data-search-text="<?php esc_attr_e( 'Select Pay Per Posts', 'wp-marketing-automations-pro' ); ?>" class="bwfan-select2ajax-single bwfan-ppp-search bwfan-input-wrapper" name="bwfan[{{data.action_id}}][data][content_id][]" multiple>
                    <#
                    if(_.size(searched_ppp) >0) {
                    temp_selected_ppp = JSON.parse(searched_ppp);
                    _.each( selected_content_id, function( v ){
                    #>
                    <option value="{{v}}" selected>{{temp_selected_ppp[v]}}</option>
                    <#
                    })
                    }
                    #>
                </select>
                <input type="hidden" class="bwfan_searched_ppp_name" name="bwfan[{{data.action_id}}][data][searched_ppp]" value="{{searched_ppp}}"/>
            </div>

        </script>
		<?php
	}

	/**
	 * Make all the data which is required by the current action.
	 * This data will be used while executing the task of this action.
	 *
	 * @param $integration_object
	 * @param $task_meta
	 *
	 * @return array|void
	 */
	public function make_data( $integration_object, $task_meta ) {
		$data_to_set = array(
			'content_id' => $task_meta['data']['content_id'],
			'user_id'    => absint( $task_meta['global']['user_id'] ),
		);

		if ( empty( $data_to_set['user_id'] ) ) {
			$email                  = ( isset( $task_meta['global']['email'] ) && is_email( $task_meta['global']['email'] ) ) ? $task_meta['global']['email'] : '';
			$user                   = is_email( $email ) ? get_user_by( 'email', $email ) : '';
			$data_to_set['user_id'] = $user instanceof WP_User ? $user->ID : 0;
		}

		return $data_to_set;
	}

	public function make_v2_data( $automation_data, $step_data ) {
		$content_ids = isset( $step_data['content_id'] ) ? $step_data['content_id'] : [];

		$content_ids = is_array( $content_ids ) && ! empty( $content_ids ) ? array_map( function ( $data ) {
			return $data['id'];
		}, $step_data['content_id'] ) : [];

		$data_to_set               = array();
		$data_to_set['content_id'] = $content_ids;
		$user_id                   = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : 0;

		if ( 0 === absint( $user_id ) ) {
			$email   = ( isset( $automation_data['global']['email'] ) && is_email( $automation_data['global']['email'] ) ) ? $automation_data['global']['email'] : '';
			$user    = is_email( $email ) ? get_user_by( 'email', $email ) : '';
			$user_id = $user instanceof WP_User ? $user->ID : 0;
		}

		$data_to_set['user_id'] = $user_id;

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
		$result = $this->process();
		if ( true === $result ) {
			return array(
				'status'  => 3,
				'message' => 'User removed from pay per post successfully',
			);
		}

		return $result;
	}

	/**
	 * Process and do the actual processing for the current action.
	 * This function is present in every action class.
	 */
	public function process() {
		$is_required_fields_present = $this->check_fields( $this->data, $this->required_fields );
		if ( false === $is_required_fields_present ) {
			return $this->show_fields_error();
		}

		$user = get_userdata( $this->data['user_id'] );

		if ( false === $user ) {
			return array(
				'status'  => 4,
				'message' => __( 'User does not exists', 'wp-marketing-automations-pro' ),
			);
		}

		global $WishListMemberInstance;
		foreach ( $this->data['content_id'] as $content_id ) {
			$WishListMemberInstance->remove_post_users( get_post_type( $content_id ), $content_id, $user->ID );
		}

		return true;
	}

	public function process_v2() {
		$user = get_userdata( $this->data['user_id'] );
		if ( false === $user ) {
			return $this->skipped_response( __( 'User does not exists', 'wp-marketing-automations-pro' ) );
		}

		if ( empty( $this->data['content_id'] ) || ! is_array( $this->data['content_id'] ) ) {
			return $this->skipped_response( __( 'Invalid pay per post', 'wp-marketing-automations-pro' ) );
		}

		global $WishListMemberInstance;
		foreach ( $this->data['content_id'] as $content_id ) {
			$WishListMemberInstance->remove_post_users( get_post_type( $content_id ), $content_id, $user->ID );
		}

		return $this->success_message( __( 'User removed from pay per post.', 'wp-marketing-automations-pro' ) );
	}

	public function get_fields_schema() {
		return [
			[
				"id"                  => 'content_id',
				"label"               => __( 'Select Pay Per Post', 'wp-marketing-automations-pro' ),
				"type"                => 'custom_search',
				'autocompleterOption' => [
					'path'      => 'wlm_pay_per_post',
					'slug'      => 'wlm_pay_per_post',
					'labelText' => 'pay per post'
				],
				"allowFreeTextSearch" => false,
				"required"            => true,
				"errorMsg"            => __( "Pay Per Post is required", 'wp-marketing-automations-pro' ),
			]
		];
	}

	public function get_desc_text( $data ) {
		$data = json_decode( wp_json_encode( $data ), true );
		if ( ! isset( $data['content_id'] ) || empty( $data['content_id'] ) ) {
			return '';
		}
		$content_ids = [];
		foreach ( $data['content_id'] as $content_id ) {
			if ( ! isset( $content_id['name'] ) || empty( $content_id['name'] ) ) {
				continue;
			}
			$content_ids[] = $content_id['name'];
		}

		return $content_ids;
	}
}

/**
 * Register this action. Registering the action will make it eligible to see it on single automation screen in select actions dropdown.
 */
return 'BWFAN_WLM_Remove_User_From_Pay_Per_Post';
