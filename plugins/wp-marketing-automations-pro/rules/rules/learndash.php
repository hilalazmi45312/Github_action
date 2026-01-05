<?php

if ( bwfan_is_learndash_active() ) {
	class BWFAN_Rule_Learndash_Quiz_Percentage extends BWFAN_Rule_Base {
		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'learndash_quiz_percentage' );
		}

		public function get_possible_rule_operators() {
			return array(
				'==' => __( 'is equal to', 'wp-marketing-automations-pro' ),
				'!=' => __( 'is not equal to', 'wp-marketing-automations-pro' ),
				'>'  => __( 'is greater than', 'wp-marketing-automations-pro' ),
				'<'  => __( 'is less than', 'wp-marketing-automations-pro' ),
				'>=' => __( 'is greater or equal to', 'wp-marketing-automations-pro' ),
				'<=' => __( 'is less or equal to', 'wp-marketing-automations-pro' ),
			);
		}

		public function get_condition_input_type() {
			return 'Text';
		}

		public function is_match_v2( $automation_data, $rule_data ) {
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$result    = false;
			$quiz_data = isset( $automation_data['global']['quiz_data'] ) ? $automation_data['global']['quiz_data'] : [];
			if ( ! isset( $quiz_data['percentage'] ) ) {
				return $this->return_is_match( $result, $rule_data );
			}

			$percentage = (float) $quiz_data['percentage'];
			$value      = (float) $rule_data['data'];
			switch ( $rule_data['rule'] ) {
				case '==':
					$result = $percentage === $value;
					break;
				case '!=':
					$result = $percentage !== $value;
					break;
				case '>':
					$result = $percentage > $value;
					break;
				case '<':
					$result = $percentage < $value;
					break;
				case '>=':
					$result = $percentage >= $value;
					break;
				case '<=':
					$result = $percentage <= $value;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		/**
		 * Get percentage from quiz data
		 *
		 * @return int
		 */
		public function get_percentage() {
			$quiz_data = BWFAN_Core()->rules->getRulesData( 'quiz_data' );

			return $quiz_data['percentage'];
		}

		public function is_match( $rule_data ) {
			$percentage = (float) $this->get_percentage();
			$value      = (float) $rule_data['condition'];

			switch ( $rule_data['operator'] ) {
				case '==':
					$result = $percentage === $value;
					break;
				case '!=':
					$result = $percentage !== $value;
					break;
				case '>':
					$result = $percentage > $value;
					break;
				case '<':
					$result = $percentage < $value;
					break;
				case '>=':
					$result = $percentage >= $value;
					break;
				case '<=':
					$result = $percentage <= $value;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		public function ui_view() {
			esc_html_e( 'If user\'s percentage ', 'wp-marketing-automations-pro' )
			?>

            <% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>

            <%= ops[operator] %>
            <%= condition + '%' %>
			<?php
		}
	}

	class BWFAN_Rule_Learndash_Quiz_Points extends BWFAN_Rule_Base {
		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'learndash_quiz_points' );
		}

		public function get_possible_rule_operators() {
			return array(
				'==' => __( 'are equal to', 'wp-marketing-automations-pro' ),
				'!=' => __( 'are not equal to', 'wp-marketing-automations-pro' ),
				'>'  => __( 'are greater than', 'wp-marketing-automations-pro' ),
				'<'  => __( 'are less than', 'wp-marketing-automations-pro' ),
				'>=' => __( 'are greater or equal to', 'wp-marketing-automations-pro' ),
				'<=' => __( 'are less or equal to', 'wp-marketing-automations-pro' ),
			);
		}

		public function get_condition_input_type() {
			return 'Text';
		}

		public function is_match_v2( $automation_data, $rule_data ) {
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$result    = false;
			$quiz_data = isset( $automation_data['global']['quiz_data'] ) ? $automation_data['global']['quiz_data'] : [];
			if ( ! isset( $quiz_data['points'] ) ) {
				return $this->return_is_match( $result, $rule_data );
			}

			$points = (float) $quiz_data['points'];
			$value  = (float) $rule_data['data'];
			switch ( $rule_data['rule'] ) {
				case '==':
					$result = $points === $value;
					break;
				case '!=':
					$result = $points !== $value;
					break;
				case '>':
					$result = $points > $value;
					break;
				case '<':
					$result = $points < $value;
					break;
				case '>=':
					$result = $points >= $value;
					break;
				case '<=':
					$result = $points <= $value;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		/**
		 * Get points from quiz data
		 *
		 * @return float
		 */
		public function get_points() {
			$quiz_data = BWFAN_Core()->rules->getRulesData( 'quiz_data' );

			return $quiz_data['points'];
		}

		public function is_match( $rule_data ) {
			$points = (float) $this->get_points();
			$value  = (float) $rule_data['condition'];

			switch ( $rule_data['operator'] ) {
				case '==':
					$result = $points === $value;
					break;
				case '!=':
					$result = $points !== $value;
					break;
				case '>':
					$result = $points > $value;
					break;
				case '<':
					$result = $points < $value;
					break;
				case '>=':
					$result = $points >= $value;
					break;
				case '<=':
					$result = $points <= $value;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		public function ui_view() {
			esc_html_e( 'If user\'s points ', 'wp-marketing-automations-pro' )
			?>

            <% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>

            <%= ops[operator] %>
            <%= condition %>
			<?php
		}
	}

	class BWFAN_Rule_Learndash_Quiz_Score extends BWFAN_Rule_Base {
		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'learndash_quiz_score' );
		}

		public function get_possible_rule_operators() {
			return array(
				'==' => __( 'is equal to', 'wp-marketing-automations-pro' ),
				'!=' => __( 'is not equal to', 'wp-marketing-automations-pro' ),
				'>'  => __( 'is greater than', 'wp-marketing-automations-pro' ),
				'<'  => __( 'is less than', 'wp-marketing-automations-pro' ),
				'>=' => __( 'is greater or equal to', 'wp-marketing-automations-pro' ),
				'<=' => __( 'is less or equal to', 'wp-marketing-automations-pro' ),
			);
		}

		public function get_condition_input_type() {
			return 'Text';
		}

		public function is_match_v2( $automation_data, $rule_data ) {
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$result    = false;
			$quiz_data = isset( $automation_data['global']['quiz_data'] ) ? $automation_data['global']['quiz_data'] : [];
			if ( ! isset( $quiz_data['score'] ) ) {
				return $this->return_is_match( $result, $rule_data );
			}

			$score = (float) $quiz_data['score'];
			$value = (float) $rule_data['data'];
			switch ( $rule_data['rule'] ) {
				case '==':
					$result = $score === $value;
					break;
				case '!=':
					$result = $score !== $value;
					break;
				case '>':
					$result = $score > $value;
					break;
				case '<':
					$result = $score < $value;
					break;
				case '>=':
					$result = $score >= $value;
					break;
				case '<=':
					$result = $score <= $value;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		/**
		 * Get score from quiz data
		 *
		 * @return float
		 */
		public function get_score() {
			$quiz_data = BWFAN_Core()->rules->getRulesData( 'quiz_data' );

			return $quiz_data['score'];
		}

		public function is_match( $rule_data ) {
			$score = $this->get_score();
			$value = $rule_data['condition'];

			switch ( $rule_data['operator'] ) {
				case '==':
					$result = $score === $value;
					break;
				case '!=':
					$result = $score !== $value;
					break;
				case '>':
					$result = $score > $value;
					break;
				case '<':
					$result = $score < $value;
					break;
				case '>=':
					$result = $score >= $value;
					break;
				case '<=':
					$result = $score <= $value;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		public function ui_view() {
			esc_html_e( 'If user\'s score ', 'wp-marketing-automations-pro' )
			?>

            <% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>

            <%= ops[operator] %>
            <%= condition %>
			<?php
		}
	}

	class BWFAN_Rule_Learndash_Quiz_Timespent extends BWFAN_Rule_Base {
		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'learndash_quiz_timespent' );
		}

		public function get_possible_rule_operators() {
			return array(
				'==' => __( 'is equal to', 'wp-marketing-automations-pro' ),
				'!=' => __( 'is not equal to', 'wp-marketing-automations-pro' ),
				'>'  => __( 'is greater than', 'wp-marketing-automations-pro' ),
				'<'  => __( 'is less than', 'wp-marketing-automations-pro' ),
				'>=' => __( 'is greater or equal to', 'wp-marketing-automations-pro' ),
				'<=' => __( 'is less or equal to', 'wp-marketing-automations-pro' ),
			);
		}

		public function get_condition_input_type() {
			return 'Text';
		}

		public function is_match_v2( $automation_data, $rule_data ) {
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$result    = false;
			$quiz_data = isset( $automation_data['global']['quiz_data'] ) ? $automation_data['global']['quiz_data'] : [];
			if ( ! isset( $quiz_data['timespent'] ) ) {
				return $this->return_is_match( $result, $rule_data );
			}

			$timespent = (float) $quiz_data['timespent'];
			$value     = (float) $rule_data['data'];

			switch ( $rule_data['rule'] ) {
				case '==':
					$result = $timespent === $value;
					break;
				case '!=':
					$result = $timespent !== $value;
					break;
				case '>':
					$result = $timespent > $value;
					break;
				case '<':
					$result = $timespent < $value;
					break;
				case '>=':
					$result = $timespent >= $value;
					break;
				case '<=':
					$result = $timespent <= $value;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		/**
		 * Get timespent from quiz data
		 *
		 * @return float
		 */
		public function get_timespent() {
			$quiz_data = BWFAN_Core()->rules->getRulesData( 'quiz_data' );

			return $quiz_data['timespent'];
		}

		public function is_match( $rule_data ) {
			$score = (float) $this->get_timespent();
			$value = (float) $rule_data['condition'];

			switch ( $rule_data['operator'] ) {
				case '==':
					$result = $score === $value;
					break;
				case '!=':
					$result = $score !== $value;
					break;
				case '>':
					$result = $score > $value;
					break;
				case '<':
					$result = $score < $value;
					break;
				case '>=':
					$result = $score >= $value;
					break;
				case '<=':
					$result = $score <= $value;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		public function get_result_operator_values() {
			return array(
				'==' => __( 'spent', 'wp-marketing-automations-pro' ),
				'!=' => __( 'not spent', 'wp-marketing-automations-pro' ),
				'>'  => __( 'spent more than', 'wp-marketing-automations-pro' ),
				'<'  => __( 'spent less than', 'wp-marketing-automations-pro' ),
				'>=' => __( 'spent greater or equal to', 'wp-marketing-automations-pro' ),
				'<=' => __( 'spent less or equal to', 'wp-marketing-automations-pro' ),
			);
		}

		public function ui_view() {
			esc_html_e( 'If user ', 'wp-marketing-automations-pro' )
			?>

            <% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_result_operator_values() ); ?>'); %>

            <%= ops[operator] %>
            <%= condition + '<?php esc_html_e( ' seconds', 'wp-marketing-automations-pro' ); ?>' %>
			<?php
		}
	}

	class BWFAN_Rule_Learndash_Course extends BWFAN_Rule_Base {
		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'learndash_course' );
		}

		/** v2 Methods: START */

		public function get_options( $term = '' ) {
			return $this->get_possible_rule_values();
		}

		public function get_rule_type() {
			return 'Select';
		}

		public function is_match_v2( $automation_data, $rule_data ) {
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$result    = false;
			$course_id = isset( $automation_data['global']['course_id'] ) ? $automation_data['global']['course_id'] : 0;

			if ( empty( $course_id ) ) {
				$lesson_id = isset( $automation_data['global']['lesson_id'] ) ? $automation_data['global']['lesson_id'] : 0;
				$course_id = ! empty( $lesson_id ) ? get_post_meta( $lesson_id, 'course_id', true ) : 0;
			}

			if ( empty( $course_id ) ) {
				$topic_id  = isset( $automation_data['global']['topic_id'] ) ? $automation_data['global']['topic_id'] : 0;
				$course_id = ! empty( $topic_id ) ? get_post_meta( $topic_id, 'course_id', true ) : 0;
			}

			if ( empty( $course_id ) ) {
				$quiz_id   = isset( $automation_data['global']['quiz_id'] ) ? $automation_data['global']['quiz_id'] : 0;
				$course_id = ! empty( $quiz_id ) ? get_post_meta( $quiz_id, 'course_id', true ) : 0;
			}

			$value     = absint( $rule_data['data'] );
			$course_id = absint( $course_id );

			switch ( $rule_data['rule'] ) {
				case 'is':
					$result = $course_id === $value;
					break;
				case 'is_not':
					$result = $course_id !== $value;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		/** v2 Methods: END */

		public function get_possible_rule_operators() {
			return array(
				'is'     => __( 'is', 'wp-marketing-automations-pro' ),
				'is_not' => __( 'is not', 'wp-marketing-automations-pro' ),
			);
		}

		public function get_condition_input_type() {
			return 'select';
		}

		public function get_possible_rule_values() {
			$result = array();

			$args = array(
				'post_type'      => 'sfwd-courses',
				'fields'         => 'ids',
				'posts_per_page' => - 1,
				'post_status'    => 'publish'
			);

			$courses_query = new WP_Query( $args );
			$courses       = $courses_query->posts;

			foreach ( $courses as $course ) {
				$result[ $course ] = get_the_title( $course );
			}

			return $result;
		}

		/**
		 * Get Course ID from Event's Rule Data
		 *
		 * @return int
		 */
		public function get_event_courseId_from_rulesData() {
			$course_id = BWFAN_Core()->rules->getRulesData( 'course_id' );

			if ( empty( $course_id ) ) {
				$lesson_id = BWFAN_Core()->rules->getRulesData( 'lesson_id' );
				$course_id = ! empty( $lesson_id ) ? get_post_meta( $lesson_id, 'course_id', true ) : 0;
			}

			if ( empty( $course_id ) ) {
				$topic_id  = BWFAN_Core()->rules->getRulesData( 'topic_id' );
				$course_id = ! empty( $topic_id ) ? get_post_meta( $topic_id, 'course_id', true ) : 0;
			}

			if ( empty( $course_id ) ) {
				$quiz_id   = BWFAN_Core()->rules->getRulesData( 'quiz_id' );
				$course_id = ! empty( $quiz_id ) ? get_post_meta( $quiz_id, 'course_id', true ) : 0;
			}

			return $course_id;
		}

		public function is_match( $rule_data ) {
			$course_id = absint( $this->get_event_courseId_from_rulesData() );
			$value     = absint( $rule_data['condition'] );

			switch ( $rule_data['operator'] ) {
				case 'is':
					$result = $course_id === $value;
					break;
				case 'is_not':
					$result = $course_id !== $value;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		public function ui_view() {
			esc_html_e( 'If Course ', 'wp-marketing-automations-pro' )
			?>

            <% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>

            <%= ops[operator] %>
            <%= '"'+uiData[condition]+'"' %>
			<?php
		}
	}

	class BWFAN_Rule_Learndash_Course_Started extends BWFAN_Rule_Base {

		public function __construct() {
			$this->v2 = true;
			$this->v1 = false;
			parent::__construct( 'learndash_course_started' );
		}

		/** v2 Methods: START */

		public function get_options( $term = '' ) {
			return $this->get_possible_rule_values();
		}

		public function get_rule_type() {
			return 'Select';
		}

		public function is_match_v2( $automation_data, $rule_data ) {
			$result = false;
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( $result, $rule_data );
			}

			$course_id = isset( $automation_data['global']['course_id'] ) ? $automation_data['global']['course_id'] : 0;
			$user_id   = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : 0;

			// return if user id not found
			if ( empty( $user_id ) ) {
				return $this->return_is_match( $result, $rule_data );
			}

			if ( empty( $course_id ) ) {
				$lesson_id = isset( $automation_data['global']['lesson_id'] ) ? $automation_data['global']['lesson_id'] : 0;
				$course_id = ! empty( $lesson_id ) ? get_post_meta( $lesson_id, 'course_id', true ) : 0;
			}

			if ( empty( $course_id ) ) {
				$topic_id  = isset( $automation_data['global']['topic_id'] ) ? $automation_data['global']['topic_id'] : 0;
				$course_id = ! empty( $topic_id ) ? get_post_meta( $topic_id, 'course_id', true ) : 0;
			}

			if ( empty( $course_id ) ) {
				$quiz_id   = isset( $automation_data['global']['quiz_id'] ) ? $automation_data['global']['quiz_id'] : 0;
				$course_id = ! empty( $quiz_id ) ? get_post_meta( $quiz_id, 'course_id', true ) : 0;
			}

			// return if not course_id found
			if ( empty( $course_id ) ) {
				return $this->return_is_match( $result, $rule_data );
			}

			$course_id = absint( $course_id );

			$user_course_progress = learndash_user_get_course_progress( $user_id, $course_id );

			$result = ( ! isset( $user_course_progress['status'] ) || empty( $user_course_progress['status'] ) || ( 'not_started' === $user_course_progress['status'] ) ) ? false : true;

			return ( 'yes' === $rule_data['data'] ) ? $result : ! $result;
		}

		/** v2 Methods: END */

		public function get_possible_rule_operators() {
			return null;
		}

		public function get_possible_rule_values() {
			return array(
				'yes' => __( 'Yes', 'wp-marketing-automations-pro' ),
				'no'  => __( 'No', 'wp-marketing-automations-pro' ),
			);
		}
	}

	class BWFAN_Rule_Learndash_Course_Progress extends BWFAN_Rule_Base {

		public function __construct() {
			$this->v2 = true;
			$this->v1 = false;
			parent::__construct( 'learndash_course_progress' );
		}

		/** v2 Methods: START */

		public function get_rule_type() {
			return 'Number';
		}

		public function get_value_label() {
			return __( 'Value (enter number between 1 to 100)', 'wp-marketing-automations-pro' );
		}

		public function is_match_v2( $automation_data, $rule_data ) {
			$result = false;
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( $result, $rule_data );
			}

			$course_id = isset( $automation_data['global']['course_id'] ) ? $automation_data['global']['course_id'] : 0;
			$user_id   = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : 0;

			// return if user id not found
			if ( empty( $user_id ) ) {
				return $this->return_is_match( $result, $rule_data );
			}

			if ( empty( $course_id ) ) {
				$lesson_id = isset( $automation_data['global']['lesson_id'] ) ? $automation_data['global']['lesson_id'] : 0;
				$course_id = ! empty( $lesson_id ) ? get_post_meta( $lesson_id, 'course_id', true ) : 0;
			}

			if ( empty( $course_id ) ) {
				$topic_id  = isset( $automation_data['global']['topic_id'] ) ? $automation_data['global']['topic_id'] : 0;
				$course_id = ! empty( $topic_id ) ? get_post_meta( $topic_id, 'course_id', true ) : 0;
			}

			if ( empty( $course_id ) ) {
				$quiz_id   = isset( $automation_data['global']['quiz_id'] ) ? $automation_data['global']['quiz_id'] : 0;
				$course_id = ! empty( $quiz_id ) ? get_post_meta( $quiz_id, 'course_id', true ) : 0;
			}

			// return if no course_id found
			if ( empty( $course_id ) ) {
				return $this->return_is_match( $result, $rule_data );
			}

			$course_id       = absint( $course_id );
			$course_progress = learndash_user_get_course_progress( $user_id, $course_id );
			if ( ! isset( $course_progress['completed'] ) || ! isset( $course_progress['total'] ) ) {
				return $this->return_is_match( $result, $rule_data );
			}

			$percentage = intval( $course_progress['completed'] * 100 / $course_progress['total'] );
			$progress   = ( $percentage > 100 ) ? 100 : $percentage;
			$progress   = (float) $progress;
			$value      = (float) $rule_data['data'];

			switch ( $rule_data['rule'] ) {
				case '==':
					$result = $progress === $value;
					break;
				case '!=':
					$result = $progress !== $value;
					break;
				case '>':
					$result = $progress > $value;
					break;
				case '<':
					$result = $progress < $value;
					break;
				case '>=':
					$result = $progress >= $value;
					break;
				case '<=':
					$result = $progress <= $value;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		/** v2 Methods: END */

		public function get_possible_rule_operators() {
			return array(
				'==' => __( 'is equal to', 'wp-marketing-automations-pro' ),
				'!=' => __( 'is not equal to', 'wp-marketing-automations-pro' ),
				'>'  => __( 'is greater than', 'wp-marketing-automations-pro' ),
				'<'  => __( 'is less than', 'wp-marketing-automations-pro' ),
				'>=' => __( 'is greater or equal to', 'wp-marketing-automations-pro' ),
				'<=' => __( 'is less or equal to', 'wp-marketing-automations-pro' ),
			);
		}
	}

	class BWFAN_Rule_Learndash_Lesson extends BWFAN_Rule_Base {
		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'learndash_lesson' );
		}

		/** v2 Methods: START */

		public function get_options( $term = '' ) {
			return $this->get_possible_rule_values();
		}

		public function get_rule_type() {
			return 'Select';
		}

		public function is_match_v2( $automation_data, $rule_data ) {
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$result    = false;
			$lesson_id = isset( $automation_data['global']['lesson_id'] ) ? $automation_data['global']['lesson_id'] : 0;

			if ( empty( $lesson_id ) ) {
				$topic_id  = isset( $automation_data['global']['topic_id'] ) ? $automation_data['global']['topic_id'] : 0;
				$lesson_id = ! empty( $topic_id ) ? get_post_meta( $topic_id, 'lesson_id', true ) : 0;
			}

			if ( empty( $lesson_id ) ) {
				$quiz_id   = isset( $automation_data['global']['quiz_id'] ) ? $automation_data['global']['quiz_id'] : 0;
				$topic_id  = ! empty( $quiz_id ) ? learndash_get_lesson_id( $quiz_id ) : 0;
				$lesson_id = ! empty( $topic_id ) ? learndash_get_lesson_id( $topic_id ) : 0;
			}

			$value     = absint( $rule_data['data'] );
			$lesson_id = absint( $lesson_id );
			switch ( $rule_data['rule'] ) {
				case 'is':
					$result = $lesson_id === $value;
					break;
				case 'is_not':
					$result = $lesson_id !== $value;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		/** v2 Methods: END */

		public function get_possible_rule_operators() {
			return array(
				'is'     => __( 'is', 'wp-marketing-automations-pro' ),
				'is_not' => __( 'is not', 'wp-marketing-automations-pro' ),
			);
		}

		public function get_condition_input_type() {
			return 'select';
		}

		public function get_possible_rule_values() {
			$result = array();

			$args = array(
				'post_type'      => 'sfwd-lessons',
				'fields'         => 'ids',
				'posts_per_page' => - 1,
				'post_status'    => 'publish'
			);

			$lessons_query = new WP_Query( $args );
			$lessons       = $lessons_query->posts;

			foreach ( $lessons as $lesson ) {
				$course_id = '';
				if ( function_exists( 'learndash_get_course_id' ) ) {
					$course_id = learndash_get_course_id( $lesson );
				}

				$result[ $lesson ] = empty( $course_id ) ? get_the_title( $lesson ) : get_the_title( $lesson ) . ' (' . get_the_title( $course_id ) . ')';
			}

			return $result;
		}

		/**
		 * Get Course ID from Event's Rule Data
		 *
		 * @return int
		 */
		public function get_event_lessonId_from_rulesData() {
			$lesson_id = BWFAN_Core()->rules->getRulesData( 'lesson_id' );

			if ( empty( $lesson_id ) ) {
				$topic_id  = BWFAN_Core()->rules->getRulesData( 'topic_id' );
				$lesson_id = ! empty( $topic_id ) ? get_post_meta( $topic_id, 'course_id', true ) : 0;
			}

			if ( empty( $lesson_id ) ) {
				$quiz_id   = BWFAN_Core()->rules->getRulesData( 'quiz_id' );
				$lesson_id = ! empty( $quiz_id ) ? get_post_meta( $quiz_id, 'lesson_id', true ) : 0;
			}

			return $lesson_id;
		}

		public function is_match( $rule_data ) {
			$lesson_id = absint( $this->get_event_lessonId_from_rulesData() );
			$value     = absint( $rule_data['condition'] );

			switch ( $rule_data['operator'] ) {
				case 'is':
					$result = $lesson_id === $value;
					break;
				case 'is_not':
					$result = $lesson_id !== $value;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		public function ui_view() {
			esc_html_e( 'If Lesson ', 'wp-marketing-automations-pro' )
			?>

            <% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>

            <%= ops[operator] %>
            <%= '"'+uiData[condition]+'"' %>
			<?php
		}
	}

	class BWFAN_Rule_Learndash_Topic extends BWFAN_Rule_Base {
		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'learndash_topic' );
		}

		/** v2 Methods: START */

		public function get_options( $term = '' ) {
			return $this->get_possible_rule_values();
		}

		public function get_rule_type() {
			return 'Select';
		}

		public function is_match_v2( $automation_data, $rule_data ) {
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$result   = false;
			$topic_id = isset( $automation_data['global']['topic_id'] ) ? $automation_data['global']['topic_id'] : 0;

			if ( empty( $topic_id ) ) {
				$quiz_id  = isset( $automation_data['global']['quiz_id'] ) ? $automation_data['global']['quiz_id'] : 0;
				$topic_id = ! empty( $quiz_id ) ? learndash_get_lesson_id( $quiz_id ) : 0;
			}

			$value    = absint( $rule_data['data'] );
			$topic_id = absint( $topic_id );
			switch ( $rule_data['rule'] ) {
				case 'is':
					$result = $topic_id === $value;
					break;
				case 'is_not':
					$result = $topic_id !== $value;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		/** v2 Methods: END */

		public function get_possible_rule_operators() {
			return array(
				'is'     => __( 'is', 'wp-marketing-automations-pro' ),
				'is_not' => __( 'is not', 'wp-marketing-automations-pro' ),
			);
		}

		public function get_condition_input_type() {
			return 'select';
		}

		public function get_possible_rule_values() {
			$result = array();

			$args = array(
				'post_type'      => 'sfwd-topic',
				'fields'         => 'ids',
				'posts_per_page' => - 1,
				'post_status'    => 'publish'
			);

			$topics_query = new WP_Query( $args );
			$topics       = $topics_query->posts;

			foreach ( $topics as $topic ) {
				$result[ $topic ] = get_the_title( $topic );
			}

			return $result;
		}

		/**
		 * Get Course ID from Event's Rule Data
		 *
		 * @return int
		 */
		public function get_event_topicId_from_rulesData() {
			$topic_id = BWFAN_Core()->rules->getRulesData( 'topic_id' );

			if ( empty( $topic_id ) ) {
				$quiz_id  = BWFAN_Core()->rules->getRulesData( 'quiz_id' );
				$topic_id = ! empty( $quiz_id ) ? get_post_meta( $quiz_id, 'topic_id', true ) : 0;
			}

			return $topic_id;
		}

		public function is_match( $rule_data ) {
			$topic_id = absint( $this->get_event_topicId_from_rulesData() );
			$value    = absint( $rule_data['condition'] );

			switch ( $rule_data['operator'] ) {
				case 'is':
					$result = $topic_id === $value;
					break;
				case 'is_not':
					$result = $topic_id !== $value;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		public function ui_view() {
			esc_html_e( 'If Topic ', 'wp-marketing-automations-pro' )
			?>

            <% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>

            <%= ops[operator] %>
            <%= '"'+uiData[condition]+'"' %>
			<?php
		}
	}

	class BWFAN_Rule_Learndash_Quiz extends BWFAN_Rule_Base {
		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'learndash_quiz' );
		}

		/** v2 Methods: START */

		public function get_options( $term = '' ) {
			return $this->get_possible_rule_values();
		}

		public function get_rule_type() {
			return 'Select';
		}

		public function is_match_v2( $automation_data, $rule_data ) {
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$result  = false;
			$quiz_id = (int) isset( $automation_data['global']['quiz_id'] ) ? $automation_data['global']['quiz_id'] : 0;

			$value = absint( $rule_data['data'] );
			switch ( $rule_data['rule'] ) {
				case 'is':
					$result = $quiz_id === $value;
					break;
				case 'is_not':
					$result = $quiz_id !== $value;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		/** v2 Methods: END */

		public function get_possible_rule_operators() {
			return array(
				'is'     => __( 'is', 'wp-marketing-automations-pro' ),
				'is_not' => __( 'is not', 'wp-marketing-automations-pro' ),
			);
		}

		public function get_condition_input_type() {
			return 'select';
		}

		public function get_possible_rule_values() {
			$result = array();

			$args = array(
				'post_type'      => 'sfwd-quiz',
				'fields'         => 'ids',
				'posts_per_page' => - 1,
				'post_status'    => 'publish'
			);

			$quiz_query = new WP_Query( $args );
			$quizzes    = $quiz_query->posts;

			foreach ( $quizzes as $quiz ) {
				$result[ $quiz ] = get_the_title( $quiz );
			}

			return $result;
		}

		public function is_match( $rule_data ) {
			$quiz_id = absint( BWFAN_Core()->rules->getRulesData( 'quiz_id' ) );
			$value   = absint( $rule_data['condition'] );

			switch ( $rule_data['operator'] ) {
				case 'is':
					$result = $quiz_id === $value;
					break;
				case 'is_not':
					$result = $quiz_id !== $value;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		public function ui_view() {
			esc_html_e( 'If Quiz ', 'wp-marketing-automations-pro' )
			?>

            <% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>

            <%= ops[operator] %>
            <%= '"'+uiData[condition]+'"' %>
			<?php
		}
	}

	class BWFAN_Rule_Learndash_Quiz_Result extends BWFAN_Rule_Base {

		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'learndash_quiz_result' );
		}

		/** v2 Methods: START */

		public function get_options( $term = '' ) {
			return $this->get_possible_rule_values();
		}

		public function get_rule_type() {
			return 'Select';
		}

		public function is_match_v2( $automation_data, $rule_data ) {
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$quiz_data = isset( $automation_data['global']['quiz_data'] ) ? $automation_data['global']['quiz_data'] : [];
			$result    = isset( $quiz_data['pass'] ) && 1 === absint( $quiz_data['pass'] );

			return $rule_data['data'] === 'yes' ? $result : ! $result;
		}

		/** v2 Methods: END */

		public function get_possible_rule_operators() {
			return null;
		}

		public function get_possible_rule_values() {
			return array(
				'yes' => __( 'True', 'wp-marketing-automations-pro' ),
				'no'  => __( 'False', 'wp-marketing-automations-pro' ),
			);
		}

		public function is_match( $rule_data ) {


			$quiz_data = BWFAN_Core()->rules->getRulesData( 'quiz_data' );


			$result = isset( $quiz_data['pass'] ) && 1 === absint( $quiz_data['pass'] );

			return $rule_data['condition'] === 'yes' ? $result : ! $result;
		}

		public function ui_view() {
			esc_html_e( 'If ', 'wp-marketing-automations-pro' );
			?>
            <% if (condition == "yes") { %> user passes <% } %>
            <% if (condition == "no") { %> user fails <% } %>

			<?php
			esc_html_e( 'the Quiz', 'wp-marketing-automations-pro' );
		}


	}

	class BWFAN_Rule_Learndash_Group extends BWFAN_Rule_Base {
		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'learndash_group' );
		}

		/** v2 Methods: START */

		public function get_options( $term = '' ) {
			return $this->get_possible_rule_values();
		}

		public function get_rule_type() {
			return 'Select';
		}

		public function is_match_v2( $automation_data, $rule_data ) {
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$group_id = isset( $automation_data['global']['group_id'] ) ? absint( $automation_data['global']['group_id'] ) : 0;
			$value    = absint( $rule_data['data'] );

			switch ( $rule_data['rule'] ) {
				case 'is':
					$result = $group_id === $value;
					break;
				case 'is_not':
					$result = $group_id !== $value;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		/** v2 Methods: END */

		public function get_possible_rule_operators() {
			return array(
				'is'     => __( 'is', 'wp-marketing-automations-pro' ),
				'is_not' => __( 'is not', 'wp-marketing-automations-pro' ),
			);
		}

		public function get_condition_input_type() {
			return 'select';
		}

		public function get_possible_rule_values() {
			$result = array();

			$args = array(
				'post_type'      => 'groups',
				'fields'         => 'ids',
				'posts_per_page' => - 1,
				'post_status'    => 'publish'
			);

			$groups_query = new WP_Query( $args );
			$groups       = $groups_query->posts;

			foreach ( $groups as $group ) {
				$result[ $group ] = get_the_title( $group );
			}

			return $result;
		}

		public function is_match( $rule_data ) {
			$group_id = absint( BWFAN_Core()->rules->getRulesData( 'group_id' ) );
			$value    = absint( $rule_data['condition'] );

			switch ( $rule_data['operator'] ) {
				case 'is':
					$result = $group_id === $value;
					break;
				case 'is_not':
					$result = $group_id !== $value;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		public function ui_view() {
			esc_html_e( 'If Group ', 'wp-marketing-automations-pro' )
			?>

            <% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>

            <%= ops[operator] %>
            <%= '"'+uiData[condition]+'"' %>
			<?php
		}
	}

	class BWFAN_Rule_Learndash_Group_Category extends BWFAN_Rule_Base {
		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'learndash_group_category' );
		}

		/** v2 Methods: START */

		public function get_options( $term = '' ) {
			return $this->get_possible_rule_values();
		}

		public function get_rule_type() {
			return 'Select';
		}

		public function is_match_v2( $automation_data, $rule_data ) {
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$group_categories = isset( $automation_data['global']['group_categories'] ) ? $automation_data['global']['group_categories'] : [];
			$value            = absint( $rule_data['data'] );

			switch ( $rule_data['rule'] ) {
				case 'is':
					$result = in_array( $value, $group_categories );
					break;
				case 'is_not':
					$result = ! in_array( $value, $group_categories );
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		/** v2 Methods: END */

		public function get_possible_rule_operators() {
			return array(
				'is'     => __( 'is', 'wp-marketing-automations-pro' ),
				'is_not' => __( 'is not', 'wp-marketing-automations-pro' ),
			);
		}

		public function get_condition_input_type() {
			return 'select';
		}

		public function get_possible_rule_values() {
			$result = array();

			$args = array(
				'taxonomy'   => 'ld_group_category',
				'hide_empty' => false,
			);

			$terms = get_terms( $args );

			foreach ( $terms as $term ) {
				$result[ $term->term_id ] = $term->name;
			}

			return $result;
		}

		public function is_match( $rule_data ) {
			$group_categories = BWFAN_Core()->rules->getRulesData( 'group_categories' );
			$value            = absint( $rule_data['condition'] );

			switch ( $rule_data['operator'] ) {
				case 'is':
					$result = in_array( $value, $group_categories );
					break;
				case 'is_not':
					$result = ! in_array( $value, $group_categories );
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		public function ui_view() {
			esc_html_e( 'If Group Category', 'wp-marketing-automations-pro' );
			?>

            <% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>

            <%= ops[operator] %>
            <%= '"'+uiData[condition]+'"' %>
			<?php
		}
	}

	if ( ! class_exists( 'BWFAN_Rule_Learndash_Course_Completed' ) ) {
		class BWFAN_Rule_Learndash_Course_Completed extends BWFAN_Rule_Base {
			public function __construct() {
				$this->v2 = true;
				$this->v1 = false;
				parent::__construct( 'learndash_course_completed' );
			}

			/** v2 Methods: START */

			public function get_options( $term = '' ) {
				return $this->get_possible_rule_values();
			}

			public function get_rule_type() {
				return 'Search';
			}

			public function is_match_v2( $automation_data, $rule_data ) {
				if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
					return $this->return_is_match( false, $rule_data );
				}
				$result            = false;
				$type              = $rule_data['rule'];
				$saved_courses     = array_column( $rule_data['data'], 'key' );
				$completed_courses = array();
				$enrolled_courses  = learndash_user_get_enrolled_courses( $automation_data['global']['user_id'] );

				if ( ! isset( $enrolled_courses ) || empty( $enrolled_courses ) ) {
					return $this->return_is_match( $result, $rule_data );
				}

				foreach ( $enrolled_courses as $course_id ) {
					$is_completed = learndash_course_completed( $automation_data['global']['user_id'], $course_id );
					if ( $is_completed ) {
						$completed_courses[] = $course_id;
					}

				}
				if ( ! isset( $completed_courses ) || empty( $completed_courses ) ) {
					return $this->return_is_match( $result, $rule_data );
				}
				switch ( $type ) {
					case 'all':
						if ( is_array( $saved_courses ) && is_array( $completed_courses ) ) {
							$result = count( array_intersect( $saved_courses, $completed_courses ) ) === count( $saved_courses );
						}
						break;
					case 'any':
						if ( is_array( $saved_courses ) && is_array( $completed_courses ) ) {
							$result = count( array_intersect( $saved_courses, $completed_courses ) ) >= 1;
						}
						break;
					case 'none':
						if ( is_array( $saved_courses ) && is_array( $completed_courses ) ) {
							$result = count( array_intersect( $saved_courses, $completed_courses ) ) === 0;
						}
						break;
					default:
				}

				return $this->return_is_match( $result, $rule_data );
			}

			/** v2 Methods: END */

			public function get_possible_rule_values() {
				$result = array();

				$args = array(
					'post_type'      => 'sfwd-courses',
					'fields'         => 'ids',
					'posts_per_page' => - 1,
					'post_status'    => 'publish'
				);

				$courses_query = new WP_Query( $args );
				$courses       = $courses_query->posts;

				foreach ( $courses as $course ) {
					$result[ $course ] = get_the_title( $course );
				}

				return $result;
			}

			public function get_possible_rule_operators() {
				return array(
					'any'  => __( 'matches any of', 'wp-marketing-automations-pro' ),
					'all'  => __( 'matches all of', 'wp-marketing-automations-pro' ),
					'none' => __( 'matches none of', 'wp-marketing-automations-pro' ),
				);
			}
		}
	}
	if ( ! class_exists( 'BWFAN_Rule_Learndash_Courses_Started' ) ) {
		class BWFAN_Rule_Learndash_Courses_Started extends BWFAN_Rule_Base {
			public function __construct() {
				$this->v2 = true;
				$this->v1 = false;
				parent::__construct( 'learndash_courses_started' );
			}

			/** v2 Methods: START */

			public function get_options( $term = '' ) {
				return $this->get_possible_rule_values();
			}

			public function get_rule_type() {
				return 'Search';
			}

			public function is_match_v2( $automation_data, $rule_data ) {

				if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
					return $this->return_is_match( false, $rule_data );
				}

				$type            = $rule_data['rule'];
				$saved_courses   = array_column( $rule_data['data'], 'key' );
				$started_courses = array();
				$user_id         = $automation_data['global']['user_id'];

				if ( ! isset( $user_id ) || empty( $user_id ) ) {
					return $this->return_is_match( false, $rule_data );
				}

				$enrolled_courses = learndash_user_get_enrolled_courses( $user_id );
				if ( ! isset( $enrolled_courses ) || empty( $enrolled_courses ) ) {
					return $this->return_is_match( false, $rule_data );
				}

				foreach ( $enrolled_courses as $course_id ) {

					$progress = learndash_course_progress( array(
						'user_id'   => $user_id,
						'course_id' => $course_id,
						'array'     => true,
					) );
					if ( $progress['percentage'] > 0 || $progress['completed'] > 0 ) {
						$started_courses[] = $course_id;
					}
				}

				if ( empty( $started_courses ) ) {
					return $this->return_is_match( false, $rule_data );
				}

				$result = false;
				switch ( $type ) {
					case 'all':
						$result = count( array_intersect( $saved_courses, $started_courses ) ) === count( $saved_courses );
						break;
					case 'any':
						$result = count( array_intersect( $saved_courses, $started_courses ) ) >= 1;
						break;
					case 'none':
						$result = count( array_intersect( $saved_courses, $started_courses ) ) === 0;
						break;
					default:
						break;
				}

				return $this->return_is_match( $result, $rule_data );
			}


			/** v2 Methods: END */

			public function get_possible_rule_values() {
				$result = array();

				$args = array(
					'post_type'      => 'sfwd-courses',
					'fields'         => 'ids',
					'posts_per_page' => - 1,
					'post_status'    => 'publish'
				);

				$courses_query = new WP_Query( $args );
				$courses       = $courses_query->posts;

				foreach ( $courses as $course ) {
					$result[ $course ] = get_the_title( $course );
				}

				return $result;
			}

			public function get_possible_rule_operators() {
				return array(
					'any'  => __( 'matches any of', 'wp-marketing-automations-pro' ),
					'all'  => __( 'matches all of', 'wp-marketing-automations-pro' ),
					'none' => __( 'matches none of', 'wp-marketing-automations-pro' ),
				);
			}
		}
	}
	if ( ! class_exists( 'BWFAN_Rule_Learndash_Has_Course_Completed' ) ) {
		class BWFAN_Rule_Learndash_Has_Course_Completed extends BWFAN_Rule_Base {
			public function __construct() {
				$this->v2 = true;
				$this->v1 = false;
				parent::__construct( 'learndash_has_course_completed' );
			}

			//** v2 Methods: START */

			public function get_options( $term = '' ) {
				return $this->get_possible_rule_values();
			}

			public function get_rule_type() {
				return 'Select';
			}

			public function is_match_v2( $automation_data, $rule_data ) {
				$result = false;
				if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
					return $this->return_is_match( $result, $rule_data );
				}

				if ( ! isset( $automation_data['global']['course_id'] ) || empty( $automation_data['global']['course_id'] ) ) {
					return $this->return_is_match( $result, $rule_data );
				}
				if ( ! isset( $automation_data['global']['user_id'] ) || empty( $automation_data['global']['user_id'] ) ) {
					return $this->return_is_match( $result, $rule_data );
				}
				$type         = $rule_data['data'];
				$is_completed = learndash_course_completed( $automation_data['global']['user_id'], $automation_data['global']['course_id'] );
				switch ( $type ) {
					case 'is':
						$result = ( $is_completed ) ? true : false;
						break;
					case 'is_not':
						$result = ( ! $is_completed ) ? true : false;
						break;
					default:
						$result = false;
						break;
				}

				return $this->return_is_match( $result, $rule_data );
			}

			/** v2 Methods: END */

			public function get_possible_rule_operators() {
				return null;
			}

			public function get_possible_rule_values() {
				return array(
					'is'     => __( 'Yes', 'wp-marketing-automations-pro' ),
					'is_not' => __( 'No', 'wp-marketing-automations-pro' ),
				);
			}
		}
	}
}
