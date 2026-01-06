<?php

class BWFAN_LD_Quiz_Selected_Answer extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'ld_quiz_selected_answer';
		$this->tag_description = __( 'Quiz\'s selected answer', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_ld_quiz_selected_answer', array( $this, 'parse_shortcode' ) );
		add_action( 'wp_ajax_bwfan_get_automation_learndash_questions', array( $this, 'bwfan_get_automation_learndash_questions' ) );
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Show the html in popup for the merge tag.
	 */
	public function get_view() {
		$this->get_back_button();
		$this->get_questions();

		$this->get_preview();
		$this->get_copy_button();
	}

	public function get_questions() {
		?>
        <div class="bwfan-input-form clearfix bwfan_quiz_questions_dropdown">
            <label for="" class="bwfan-label-title"><?php echo esc_html__( 'Select Question', 'wp-marketing-automations-pro' ); ?></label>
			<?php /** Needed this class 'bwfan_ld_quiz_questions' (bwfan_'event_slug') to get the questions loaded into this select by WP BWFAN Filter 'bwfan_all_event_js_data'  */ ?>
            <select id="" class="bwfan-input-wrapper bwfan-mb-15 bwfan_tag_select bwfan_ld_quiz_questions" name="question"></select>
        </div>
		<?php
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

		$quiz_answers = BWFAN_Merge_Tag_Loader::get_data( 'quiz_answers' );

		$answer = '';
		if ( isset( $attr['question'] ) && ! empty( $quiz_answers ) && is_array( $quiz_answers ) ) {
			$answer = $quiz_answers[ absint( $attr['question'] ) ];
		}

		return $this->parse_shortcode_output( $answer, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 *
	 * @todo:Hard values shouldn't be passed
	 */
	public function get_dummy_preview() {
		return 'Dummy Answer to the Question';
	}

	public function bwfan_get_automation_learndash_questions() {
		BWFAN_PRO_Common::nocache_headers();
		$finalarr     = [];
		$automationId = absint( sanitize_text_field( $_POST['automationId'] ) );

		/** Check Automation */
		$automation_obj = BWFAN_Automation_V2::get_instance( $automationId );

		/** Check for automation exists */
		if ( empty( $automation_obj->error ) ) {
			$automation_meta = $automation_obj->get_automation_meta_data();

			if ( isset( $automation_meta['event_meta'] ) && isset( $automation_meta['event_meta']['quiz_id'] ) ) {
				$quiz_id = sanitize_text_field( $automation_meta['event_meta']['quiz_id'] );
				if ( ! empty( $quiz_id ) ) {
					$questions = BWFAN_Learndash_Common::get_learndash_quiz_questions_models( $quiz_id );
					$finalarr  = [];
					foreach ( $questions as $question ) {
						$finalarr[] = [
							'key'   => $question->getId(),
							'value' => $question->getQuestion() ? $question->getQuestion() : $question->getId(),
						];
					}
				}
			}
		}


		wp_send_json( array(
			'results' => $finalarr
		) );
		exit;
	}

	/**
	 * Returns merge tag schema
	 *
	 * @return array[]
	 */
	public function get_setting_schema() {
		return [
			[
				'id'          => 'question',
				'type'        => 'ajax',
				'label'       => __( 'Select Question', 'wp-marketing-automations-pro' ),
				"class"       => 'bwfan-input-wrapper',
				"required"    => true,
				'placeholder' => __( 'Select', 'wp-marketing-automations-pro' ),
				"description" => "",
				"ajax_cb"     => 'bwfan_get_automation_learndash_questions',
			]
		];
	}

}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_learndash_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'learndash_quiz', 'BWFAN_LD_Quiz_Selected_Answer', null, __( 'Learndash', 'wp-marketing-automations-pro' ) );
}
