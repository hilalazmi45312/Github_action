<?php

class BWFAN_LD_Quiz_Lowest_Points extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'ld_quiz_lowest_points';
		$this->tag_description = __( 'User\'s lowest points in a Quiz', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_ld_quiz_lowest_points', array( $this, 'parse_shortcode' ) );
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
		$this->get_quizzes();

		$this->get_preview();
		$this->get_copy_button();
	}

	public function get_quizzes() {
		?>
        <div class="bwfan-input-form clearfix">
            <label for="" class="bwfan-label-title"><?php esc_html_e( 'Quiz', 'wp-marketing-automations-pro' ); ?></label>
            <select id="" data-search="sfwd-quizzes" data-search-text="<?php esc_attr_e( 'Select Quiz', 'wp-marketing-automations-pro' ); ?>" class="bwfan-select2ajax-single bwfan-input-wrapper bwfan_tag_select" name="quiz">
                <option value=""><?php esc_html_e( 'Select Quiz', 'wp-marketing-automations-pro' ); ?></option>
            </select>
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

		$email   = BWFAN_Merge_Tag_Loader::get_data( 'email' );
		$user_id = BWFAN_Merge_Tag_Loader::get_data( 'user_id' );

		$user = ( ! empty( $email ) && is_email( $email ) ) ? get_user_by_email( $email ) : get_user_by( 'ID', $user_id );

		/** If no quiz is selected, then check highest points among all quizzes attempts */
		$quiz_id = isset( $attr['quiz'] ) ? absint( $attr['quiz'] ) : 0;
		$quizzes = BWFAN_Learndash_Common::get_user_quiz_attempts( $user->ID );

		$lowest_points = 0;
		if ( ! empty( $quizzes ) && is_array( $quizzes ) ) {
			foreach ( $quizzes as $quiz ) {
				$points       = absint( $quiz['points'] );
				$current_quiz = absint( $quiz['quiz'] );

				/** $lowest_points initialise on first iteration */
				$lowest_points = ( 0 === $lowest_points ) ? $points : $lowest_points;
				/** If no quiz is selected ( i.e.: $quiz_id === 0 ), then check lowest points among all quizzes attempts, otherwise check against the specified quiz_id */
				$lowest_points = ( ( $quiz_id === 0 || $quiz_id === $current_quiz ) && $points < $lowest_points ) ? $points : $lowest_points;
			}
		}

		return $this->parse_shortcode_output( $lowest_points, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 *
	 * @todo:Hard values shouldn't be passed
	 */
	public function get_dummy_preview() {
		return 0;
	}

	/**
	 * Returns merge tag schema
	 *
	 * @return array[]
	 */
	public function get_setting_schema() {
		return [
			[
				'id'          => 'field',
				'type'        => 'ajax',
				'label'       => __( 'Select Quiz', 'wp-marketing-automations-pro' ),
				"class"       => 'bwfan-input-wrapper',
				"required"    => true,
				'placeholder' => __( 'Select', 'wp-marketing-automations-pro' ),
				"description" => "",
				"ajax_cb"     => 'bwfan_get_learndash_quiz',
			]
		];
	}
}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_learndash_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'learndash_user', 'BWFAN_LD_Quiz_Lowest_Points' );
}
