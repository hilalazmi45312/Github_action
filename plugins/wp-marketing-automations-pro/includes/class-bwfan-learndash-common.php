<?php

class BWFAN_Learndash_Common {

	public static function init() {
		add_filter( 'bwfan_select2_ajax_callable', array( __CLASS__, 'get_callable_object' ), 1, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_assets' ), 99 );
		add_action( 'wp_ajax_bwfan_get_learndash_quiz', array( __CLASS__, 'bwfan_get_learndash_quiz' ) );
	}

	public static function admin_enqueue_assets() {
		if ( ! isset( $_GET['page'] ) || 'autonami' !== $_GET['page'] ) {
			return;
		}

		/** Prevent LearnDash styles to interfere with Autonami Styles */
		wp_dequeue_style( 'learndash-admin-settings-page' );
	}

	public static function get_callable_object( $is_empty, $data ) {
		if ( 'sfwd-courses' === $data['type'] ) {
			return [ __CLASS__, 'get_learndash_courses' ];
		}

		if ( 'sfwd-quizzes' === $data['type'] ) {
			return [ __CLASS__, 'get_learndash_quizzes' ];
		}

		return $is_empty;
	}

	/**
	 * Get quizzes by searched term
	 *
	 * @param $searched_term
	 */
	public static function get_learndash_quizzes( $searched_term = '', $limit = 10 ) {
		$query_params = array(
			'post_type'      => learndash_get_post_type_slug( 'quiz' ),
			'posts_per_page' => $limit,
			'post_status'    => 'publish'
		);

		if ( '' !== $searched_term ) {
			$query_params['s'] = $searched_term;
		}

		$query = new WP_Query( $query_params );

		$results = array();
		if ( $query->found_posts > 0 ) {
			foreach ( $query->posts as $post ) {
				$results[] = array(
					'id'   => $post->ID,
					'text' => $post->post_title,
				);
			}
		}

		return array( 'results' => $results );
	}

	/**
	 * Get WpProQuiz_Model_Quiz models
	 *
	 * @return WpProQuiz_Model_Quiz[]
	 */
	public static function get_learndash_quizzes_models() {
		$quiz_mapper = new WpProQuiz_Model_QuizMapper();

		/** @var WpProQuiz_Model_Quiz[] $quizzes */
		$quizzes = $quiz_mapper->fetchAll();
		foreach ( $quizzes as $key => $quiz ) {
			if ( empty( $quiz->getPostId() ) ) {
				unset( $quizzes[ $key ] );
			}
		}

		return $quizzes;
	}

	public static function get_learndash_courses( $searched_term = '', $limit = 10 ) {
		$courses      = array();
		$results      = array();
		$query_params = array(
			'post_type'      => 'sfwd-courses',
			'posts_per_page' => $limit,
			'post_status'    => 'publish',
		);

		if ( '' !== $searched_term ) {
			$query_params['s'] = $searched_term;
		}

		$query = new WP_Query( $query_params );

		if ( $query->found_posts > 0 ) {
			foreach ( $query->posts as $post ) {
				$results[] = array(
					'id'   => $post->ID,
					'text' => $post->post_title,
				);
			}
		}

		$courses['results'] = $results;

		return $courses;
	}

	/**
	 * Get LearnDash User's Group's Leaders
	 *
	 * @param int $user_id
	 *
	 * @return array|null
	 */
	public static function get_group_leaders_by_user_id( $user_id ) {
		global $wpdb;

		$query = $wpdb->prepare( "SELECT DISTINCT(user_id)  FROM {$wpdb->usermeta} WHERE `meta_key` LIKE '%learndash_group_leaders_%' AND meta_value IN (SELECT meta_value as 'group' FROM {$wpdb->usermeta} WHERE `meta_key` LIKE '%learndash_group_users_%' AND user_id = %d)", $user_id );

		return $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Get LearnDash Group's Leaders by Group ID
	 *
	 * @param $group_ids
	 *
	 * @return array|object|stdClass[]|null
	 */
	public static function get_group_leaders_by_group_id( $group_ids ) {
		global $wpdb;
		$meta_value = " AND meta_value = %d";
		if ( is_array( $group_ids ) ) {
			$string_placeholder = array_fill( 0, count( $group_ids ), '%d' );
			$placeholder        = implode( ', ', $string_placeholder );
			$meta_value         = " AND meta_value IN  ($placeholder)";
		}
		$query = "SELECT DISTINCT(user_id) FROM {$wpdb->usermeta} WHERE `meta_key` LIKE '%learndash_group_leaders_%' {$meta_value}";

		$query = $wpdb->prepare( $query, $group_ids );

		return $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Get LearnDash User's Groups
	 *
	 * @param int $user_id
	 *
	 * @return array|null
	 */
	public static function get_groups_by_user_id( $user_id ) {
		global $wpdb;

		$query = $wpdb->prepare( "SELECT meta_value as 'group' FROM {$wpdb->usermeta} WHERE `meta_key` LIKE '%learndash_group_users_%' AND user_id = %d", $user_id );

		return $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	public static function get_user_quiz_attempts( $user_id ) {
		return get_user_meta( $user_id, '_sfwd-quizzes', true );
	}

	/**
	 * Get LearnDash questions by quiz ID
	 *
	 * @param int $quiz_id
	 *
	 * @return array
	 */
	public static function get_learndash_quiz_questions( $quiz_id = 0 ) {
		if ( empty( $quiz_id ) ) {
			return array();
		}

		$questions = learndash_get_quiz_questions( absint( $quiz_id ) );
		$questions = array_keys( $questions );

		$questions_array = array();
		foreach ( $questions as $key ) {
			$questions_array[ $key ] = get_the_title( $key );
		}

		return $questions_array;
	}

	public static function get_learndash_quiz_questions_models( $quiz_id = 0 ) {
		if ( empty( $quiz_id ) ) {
			return array();
		}

		$question_mapper = new WpProQuiz_Model_QuestionMapper();

		return $question_mapper->fetchAll( $quiz_id );
	}

	/**
	 * Get Answers from Quiz Results data (LearnDash)
	 *
	 * @param $results
	 * @param WpProQuiz_Model_Question[] $question_models
	 *
	 * @return array
	 */
	public static function get_learndash_quiz_answers_from_result_data( $results, $question_models ) {
		$questions_and_answers = array();
		foreach ( $results as $key => $result ) {
			$questions_and_answers[ $key ] = $result['e']['r'];
		}

		// Map the question IDs into post IDs
		foreach ( $question_models as $model ) {
			foreach ( $questions_and_answers as $key => $result ) {
				if ( $key === $model->getId() ) {
					// Convert multiple choice from true / false into the selected option
					if ( is_array( $result ) ) {
						foreach ( $result as $n => $multiple_choice_answer ) {
							if ( true === $multiple_choice_answer ) {
								$answers = $model->getAnswerData();
								foreach ( $answers as $x => $answer ) {
									if ( $x === $n ) {
										$result = $answer->getAnswer();
										break 2;
									}
								}
							}
						}
					}

					$answers[ $model->getId() ] = $result;
				}
			}
		}

		return $questions_and_answers;
	}

	public function bwfan_get_learndash_quiz() {
		BWFAN_PRO_Common::nocache_headers();
		$finalarr = [];

		$query = new WP_Query( array(
			'post_type'      => learndash_get_post_type_slug( 'quiz' ),
			'posts_per_page' => - 1,
			'post_status'    => 'publish'
		) );

		if ( $query->found_posts > 0 ) {
			foreach ( $query->posts as $post ) {
				$finalarr[] = array(
					'key'   => $post->ID,
					'value' => $post->post_title,
				);
			}
		}

		wp_send_json( array(
			'results' => $finalarr
		) );
		exit;
	}

	/**
	 * fetching last user activity id by activity type
	 *
	 * @param $user_id
	 * @param $activity_type
	 *
	 * @return string|null
	 */
	public static function get_last_user_activity_by_type( $user_id, $activity_type ) {
		global $wpdb;
		$activity_date_col = 'access' === $activity_type ? 'activity_started' : 'activity_completed';
		$sql               = "SELECT `post_id`
				FROM `{$wpdb->prefix}learndash_user_activity` 
				WHERE `activity_type`= %s
				AND $activity_date_col IS NOT NULL
				AND `user_id`=%d
				ORDER BY $activity_date_col DESC LIMIT 1";

		return $wpdb->get_var( $wpdb->prepare( $sql, $activity_type, $user_id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * @param $course_id
	 *
	 * @return array|int|string|null
	 */
	public static function get_course_access_lists( $course_id ) {
		$course_access_list_setting = learndash_get_setting( $course_id, 'course_access_list' );
		$course_access_list_setting = learndash_convert_course_access_list( $course_access_list_setting, true );

		$course_access_list_post_meta = get_post_meta( $course_id, 'course_access_list', true );
		$course_access_list_post_meta = learndash_convert_course_access_list( $course_access_list_post_meta, true );

		$course_access_list_user_meta = learndash_get_course_users_access_from_meta( $course_id );
		$course_access_list_user_meta = learndash_convert_course_access_list( $course_access_list_user_meta, true );

		$course_access_list = array_merge( $course_access_list_setting, $course_access_list_post_meta, $course_access_list_user_meta );

		return array_unique( $course_access_list );
	}
}
