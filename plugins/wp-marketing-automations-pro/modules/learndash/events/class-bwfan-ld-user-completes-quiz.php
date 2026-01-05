<?php

#[AllowDynamicProperties]
final class BWFAN_LD_User_Completes_Quiz extends BWFAN_Event {
	private static $instance = null;
	/** @var WP_User $user */
	public $user = null;
	public $quiz_data = [];
	/** @var WP_Post $quiz */
	public $quiz = null;
	public $quiz_answers = array();
	public $email = '';

	private function __construct() {
		$this->event_merge_tag_groups = array( 'learndash_course', 'learndash_user', 'learndash_quiz', 'bwf_contact' );
		$this->event_name             = __( 'User Completes a Quiz', 'wp-marketing-automations-pro' );
		$this->event_desc             = esc_html__( 'This event runs when the user completes a quiz.', 'wp-marketing-automations-pro' );
		$this->event_rule_groups      = array(
			'learndash_quiz',
			'learndash_quiz_result',
			'learndash_lesson',
			'learndash_course',
			'learndash_topic',
			'bwf_contact_segments',
			'bwf_contact',
			'bwf_contact_fields',
			'bwf_contact_user',
			'bwf_contact_wc',
			'bwf_contact_geo',
			'bwf_engagement',
			'bwf_broadcast'
		);
		$this->optgroup_label         = __( 'LearnDash', 'wp-marketing-automations-pro' );
		$this->priority               = 70;
		$this->v2                     = true;
		$this->automation_add         = true;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function load_hooks() {
		add_action( 'learndash_quiz_completed', [ $this, 'process' ], 10, 2 );
		add_filter( 'bwfan_all_event_js_data', array( $this, 'add_form_data' ), 10, 2 );
	}

	/**
	 * Make the required data for the current event and send it asynchronously.
	 *
	 * @param $quizdata : array( 'quiz' => $quiz, 'course' => $course, 'questions' => $questions, 'score' => $score, 'count' => $count, 'pass' => $pass, 'rank' => '-', 'time' => time(), 'pro_quizid' => $quiz_id, 'points' => $points, 'total_points' => $total_points, 'percentage' => $result, 'timespent' => $timespent);
	 * @param $current_user
	 */
	public function process( $quizdata, $current_user ) {
		$data = $this->get_default_data();

		$data['quiz_data'] = $quizdata;
		$data['quiz_id']   = ( isset( $quizdata['quiz'] ) && $quizdata['quiz'] instanceof WP_Post ) ? $quizdata['quiz']->ID : 0;
		$data['user_id']   = $current_user->ID;

		$user          = get_user_by( 'id', absint( $current_user->ID ) );
		$data['email'] = $user instanceof WP_User && is_email( $user->user_email ) ? $user->user_email : '';

		$this->send_async_call( $data );
	}

	public function add_form_data( $event_js_data, $automation_meta ) {
		if ( ! isset( $automation_meta['event_meta'] ) || ! isset( $event_js_data['ld_user_completes_quiz'] ) || ! isset( $automation_meta['event_meta']['quiz_id'] ) ) {
			return $event_js_data;
		}

		if ( isset( $automation_meta['event'] ) && ! empty( $automation_meta['event'] ) && 'ld_user_completes_quiz' !== $automation_meta['event'] ) {
			return $event_js_data;
		}

		$event_js_data['ld_user_completes_quiz']['selected_quiz'] = $automation_meta['event_meta']['quiz_id'];

		//$questions                                                     = BWFAN_Learndash_Common::get_learndash_quiz_questions( $automation_meta['event_meta']['quiz_id'] );
		$questions                                                     = $this->get_quiz_questions( $automation_meta['event_meta']['quiz_id'] );
		$event_js_data['ld_user_completes_quiz']['selected_questions'] = $questions;

		return $event_js_data;
	}

	public function get_quiz_questions( $quiz_id ) {
		$questions = BWFAN_Learndash_Common::get_learndash_quiz_questions_models( $quiz_id );
		$options   = array();
		if ( is_array( $questions ) ) {
			/** @var WpProQuiz_Model_Question $question */
			foreach ( $questions as $question ) {
				$options[ $question->getId() ] = esc_html( $question->getQuestion() );
			}
		}

		return $options;
	}

	/**
	 * Set up rules data
	 *
	 * @param $automation_data
	 */
	public function pre_executable_actions( $automation_data ) {
		BWFAN_Core()->rules->setRulesData( $this->user->ID, 'user_id' );
		BWFAN_Core()->rules->setRulesData( $this->user, 'user' );
		BWFAN_Core()->rules->setRulesData( $this->quiz_data, 'quiz_data' );
		BWFAN_Core()->rules->setRulesData( $this->quiz->ID, 'quiz_id' );
		BWFAN_Core()->rules->setRulesData( $this->quiz_answers, 'quiz_answers' );
		BWFAN_Core()->rules->setRulesData( $this->email, 'email' );
	}

	/**
	 * Registers the tasks for current event.
	 *
	 * @param $automation_id
	 * @param $integration_data
	 * @param $event_data
	 */
	public function register_tasks( $automation_id, $integration_data, $event_data ) {
		if ( ! is_array( $integration_data ) ) {
			return;
		}

		$data_to_send = $this->get_event_data();

		$this->create_tasks( $automation_id, $integration_data, $event_data, $data_to_send );
	}

	public function get_event_data() {
		$data_to_send                           = [ 'global' => [] ];
		$data_to_send['global']['user_id']      = $this->user->ID;
		$data_to_send['global']['user']         = $this->user;
		$data_to_send['global']['quiz_data']    = $this->quiz_data;
		$data_to_send['global']['email']        = $this->email;
		$data_to_send['global']['quiz_id']      = $this->quiz->ID;
		$data_to_send['global']['quiz_answers'] = $this->quiz_answers;

		return $data_to_send;
	}

	/**
	 * Show the html fields for the current event.
	 */
	public function get_view( $db_eventmeta_saved_value ) {

		?>

        <script>
            jQuery(document).ready(function ($) {

                $(document.body).on('click', '.item_modify_trigger', function () {

                    setTimeout(select2Quiz, 300);

                    function select2Quiz() {
                        $('#bwfan-ld_quiz_id').select2({
                            placeholder: 'Search quiz',
                            minimumInputLength: 2,
                            quizs: false,
                            data: [],
                            escapeMarkup(m) {
                                return m;
                            },
                            ajax: {
                                url: ajaxurl,
                                dataType: 'json',
                                type: 'POST',
                                delay: 250,
                                data(term) {
                                    return {
                                        search_term: term,
                                        action: 'bwfan_select2ajax',
                                        type: $('#bwfan-ld_quiz_id').attr('data-search'),
                                        _wpnonce: bwfanParams.ajax_nonce,
                                    };
                                },
                                processResults: function (data) {
                                    var options = [];
                                    if (data) {
                                        // data is the array of arrays, and each of them contains ID and the Label of the option
                                        $.each(data, function (index, text) { // do not forget that "index" is just auto incremented value
                                            options.push({id: text.id, text: text.text});
                                        });
                                    }
                                    return {
                                        results: options
                                    };
                                },
                                cache: true
                            }
                        });

                    }
                });
                $('body').on('change', '.bwfan-quiz-search', function () {
                    var temp_quiz = {id: $(this).val(), name: $(this).find(':selected').text()};

                    $(this).parent().find('.bwfan_searched_quiz').val(JSON.stringify(temp_quiz));
                });
            });
        </script>
        <script type="text/html" id="tmpl-event-<?php echo esc_html__( $this->get_slug() ); ?>">
            <#
            selected_quiz_id = (_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'quiz_id')) ? data.eventSavedData.quiz_id : '';
            searched_quiz = (_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'searched_quiz')) ? data.eventSavedData.searched_quiz : '';
            if(!_.isEmpty(searched_quiz)) {
            try {
            searched_quiz = JSON.parse(searched_quiz);
            }
            catch(e) {
            //Do Nothing
            }
            }
            #>
            <div class="bwfan_mt15"></div>
            <div class="bwfan-col-sm-12 bwfan-p-0">
                <label for="" class="bwfan-label-title"><?php esc_html_e( 'Select Quiz', 'wp-marketing-automations-pro' ); ?></label>
                <select id="bwfan-ld_quiz_id" data-search="quiz" data-search-text="<?php esc_attr_e( 'Select Quiz', 'wp-marketing-automations-pro' ); ?>" class="bwfan-select2ajax-single bwfan-quiz-search bwfan-input-wrapper" name="event_meta[quiz_id]">
                    <option value="bwfan-ld-any-quiz"><?php esc_html_e( 'Any Quiz', 'wp-marketing-automations-pro' ); ?></option>
                    <#
                    if(_.size(searched_quiz) > 0) {
                    temp_selected_quiz = _.isObject(searched_quiz) ? searched_quiz : JSON.parse(searched_quiz);
                    if(temp_selected_quiz.id == selected_quiz_id){
                    #>
                    <option value="{{temp_selected_quiz.id}}" selected>{{temp_selected_quiz.name}}</option>
                    <#
                    }
                    }
                    stringify_searched_quiz = _.isObject(searched_quiz) ? JSON.stringify(searched_quiz) :
                    searched_quiz;
                    #>
                </select>
                <input type="hidden" class="bwfan_searched_quiz" name="event_meta[searched_quiz]" value="{{stringify_searched_quiz}}"/>
            </div>
        </script>
		<?php
	}

	/**
	 * Make the view data for the current event which will be shown in task listing screen.
	 *
	 * @param $global_data
	 *
	 * @return false|string
	 */
	/**
	 * Make the view data for the current event which will be shown in task listing screen.
	 *
	 * @param $global_data
	 *
	 * @return false|string
	 */
	public function get_task_view( $global_data ) {
		$user = get_user_by( 'ID', absint( $global_data['user_id'] ) );
		ob_start();
		?>
        <li>
            <strong><?php echo esc_html__( 'User:', 'wp-marketing-automations-pro' ); ?> </strong>
            <a target="_blank" href="<?php echo admin_url() . '?user-edit.php?user_id=' . $user->ID; //phpcs:ignore WordPress.Security.EscapeOutput ?>"><?php echo esc_html__( $user->first_name . ' ' . $user->last_name ); ?></a>
        </li>
        <li>
            <strong><?php echo esc_html__( 'Result:', 'wp-marketing-automations-pro' ); ?> </strong>
			<?php echo ( ! empty( $global_data['quiz_data']['pass'] ) ) ? 'Pass' : 'Fail'; ?>
        </li>
        <li>
            <strong><?php echo esc_html__( 'Score:', 'wp-marketing-automations-pro' ); ?> </strong>
			<?php echo esc_html( $global_data['quiz_data']['score'] ); ?>
        </li>
        <li>
            <strong><?php echo esc_html__( 'Percentage:', 'wp-marketing-automations-pro' ); ?> </strong>
			<?php echo esc_html( $global_data['quiz_data']['percentage'] ); ?>
        </li>
		<?php
		return ob_get_clean();
	}

	/**
	 * Set global data for all the merge tags which are supported by this event.
	 *
	 * @param $task_meta
	 */
	public function set_merge_tags_data( $task_meta ) {
		$get_data = BWFAN_Merge_Tag_Loader::get_data( 'quiz_data' );
		if ( ( empty( $get_data ) || intval( $get_data ) !== intval( $task_meta['global']['quiz_data'] ) ) ) {
			$set_data = array(
				'user_id'      => $task_meta['global']['user_id'],
				'email'        => $task_meta['global']['email'],
				'user'         => $task_meta['global']['user'],
				'quiz_data'    => $task_meta['global']['quiz_data'],
				'quiz_answers' => $task_meta['global']['quiz_answers'],
				'quiz_id'      => $task_meta['global']['quiz_id']
			);
			BWFAN_Merge_Tag_Loader::set_data( $set_data );
		}
	}

	public function get_email_event() {
		return $this->email;
	}

	public function get_user_id_event() {
		return $this->user->ID;
	}

	/**
	 * Capture the async data for the current event.
	 * @return array|bool
	 */
	public function capture_async_data() {
		$this->user         = get_user_by( 'ID', BWFAN_Common::$events_async_data['user_id'] );
		$this->quiz_data    = BWFAN_Common::$events_async_data['quiz_data'];
		$this->quiz         = get_post( BWFAN_Common::$events_async_data['quiz_id'] );
		$this->quiz_answers = $this->get_answer_data();
		$this->email        = BWFAN_Common::$events_async_data['email'];

		return $this->run_automations();
	}

	public function get_answer_data() {
		$answers = array();

		if ( isset( $this->quiz_data['statistic_ref_id'] ) && $this->quiz_data['pro_quizid'] ) {
			/** @var WpProQuiz_Model_StatisticUser[] $stats */
			$stats = ( new WpProQuiz_Model_StatisticUserMapper() )->fetchUserStatistic( $this->quiz_data['statistic_ref_id'], $this->quiz_data['pro_quizid'] );

			foreach ( $stats as $stat ) {
				$user_answer      = $stat->getStatisticAnswerData();
				$user_question_id = $stat->getQuestionId();

				/** @var WpProQuiz_Model_AnswerTypes[] $questions_answer_data */
				$questions_answer_data = $stat->getQuestionAnswerData();
				foreach ( $user_answer as $index => $option ) {
					if ( 1 === $option ) {
						$answers[ $user_question_id ] = $questions_answer_data[ $index ]->getAnswer();
						break;
					}
				}
			}
		}

		return $answers;

	}

	/**
	 * Validating form id after submission with the selected form id in the event
	 *
	 * @param $automations_arr
	 *
	 * @return mixed
	 */
	public function validate_event_data_before_creating_task( $automations_arr ) {

		$automations_arr_temp = $automations_arr;
		foreach ( $automations_arr as $automation_id => $automation_data ) {
			if ( isset( $automation_data['event_meta']['quiz_id'] ) && isset( $this->quiz_data['pro_quizid'] ) && $automation_data['event_meta']['quiz_id'] !== 'bwfan-ld-any-quiz' && absint( $this->quiz_data['pro_quizid'] ) !== absint( $automation_data['event_meta']['quiz_id'] ) ) {
				unset( $automations_arr_temp[ $automation_id ] );
			}
		}

		return $automations_arr_temp;
	}

	/**
	 * v2 Method: Validate event settings
	 *
	 * @param $automation_data
	 *
	 * @return bool
	 */

	public function validate_v2_event_settings( $automation_data ) {
		/** check any quiz case */
		if ( ! isset( $automation_data['event_meta'] ) || ! isset( $automation_data['event_meta']['quizzes'] ) || 'any' === $automation_data['event_meta']['quizzes'] ) {
			return true;
		}

		/** check if specific quiz selected */
		if ( ! is_array( $automation_data['event_meta']['quiz_id'] ) || 0 === count( $automation_data['event_meta']['quiz_id'] ) ) {
			return false;
		}
		$ids = array_column( $automation_data['event_meta']['quiz_id'], 'id' );

		return in_array( intval( $automation_data['quiz_data']['quiz'] ), array_map( 'intval', $ids ), true );
	}

	/**
	 * Capture the async data for the current event.
	 *
	 * @return array|bool
	 */
	public function capture_v2_data( $automation_data ) {
		$this->user                      = get_user_by( 'ID', BWFAN_Common::$events_async_data['user_id'] );
		$this->quiz_data                 = BWFAN_Common::$events_async_data['quiz_data'];
		$this->quiz                      = get_post( BWFAN_Common::$events_async_data['quiz_id'] );
		$this->quiz_answers              = $this->get_answer_data();
		$this->email                     = BWFAN_Common::$events_async_data['email'];
		$automation_data['user']         = $this->user;
		$automation_data['quiz_data']    = $this->quiz_data;
		$automation_data['quiz']         = $this->quiz;
		$automation_data['quiz_answers'] = $this->quiz_answers;
		$automation_data['email']        = $this->email;

		return $automation_data;
	}

	/**
	 * v2 Method: Get field Schema
	 *
	 * @return array[]
	 */
	public function get_fields_schema() {

		return [
			[
				'id'          => 'quizzes',
				'label'       => __( 'Quizzes', 'wp-marketing-automations-pro' ),
				'type'        => 'radio',
				'options'     => [
					[
						'label' => __( 'Any Quiz', 'wp-marketing-automations-pro' ),
						'value' => 'any'
					],
					[
						'label' => __( 'Specific Quiz', 'wp-marketing-automations-pro' ),
						'value' => 'selected_quiz'
					],
				],
				"class"       => 'bwfan-input-wrapper',
				"tip"         => "",
				"required"    => false,
				"description" => "",
			],
			[
				"id"                  => 'quiz_id',
				"label"               => __( 'Select Quizzes', 'wp-marketing-automations-pro' ),
				"type"                => 'custom_search',
				'autocompleterOption' => [
					'path'      => 'ld_quiz',
					'slug'      => 'ld_quiz',
					'labelText' => 'quiz'
				],
				"allowFreeTextSearch" => false,
				"required"            => true,
				"errorMsg"            => __( "Quiz is required", 'wp-marketing-automations-pro' ),
				"multiple"            => true,
				'toggler'             => [
					'fields' => [
						[
							'id'    => 'quizzes',
							'value' => 'selected_quiz',
						]
					]
				],
			]
		];
	}

	/** set default values */
	public function get_default_values() {
		return array( "quizzes" => "any" );
	}

	/**
	 * store data in normalize table
	 *
	 * @return bool
	 */
	public function is_db_normalize() {
		return true;
	}

	/**
	 * store data in normalized table
	 *
	 * @return void
	 */
	public function execute_normalization( $post_parameters ) {
		if ( ! class_exists( 'BWFCRM_DB_Normalization_Learndash' ) ) {
			return;
		}
		$db_ins = BWFCRM_DB_Normalization_Learndash::get_instance();
		$db_ins->add_quiz_completed( $post_parameters['quiz_data'], $post_parameters['user_id'] );
	}

	/**
	 * get contact automation data
	 *
	 * @param $automation_data
	 * @param $cid
	 *
	 * @return array|null[]
	 */
	public function get_manually_added_contact_automation_data( $automation_data, $cid ) {
		$contact = new WooFunnels_Contact( '', '', '', $cid );

		/** Check if contact exists */
		if ( ! $contact instanceof WooFunnels_Contact || empty( $contact->get_id() ) ) {
			return [ 'status' => 0, 'type' => 'contact_not_found' ];
		}

		if ( 0 === intval( $contact->get_wpid() ) ) {
			return [ 'status' => 0, 'type' => 'user_not_found' ];
		}

		/** fetching last quiz id */
		$quiz_id = BWFAN_Learndash_Common::get_last_user_activity_by_type( $contact->get_wpid(), 'quiz' );

		/** return if no quiz available as event is completed a quiz */
		if ( empty( $quiz_id ) ) {
			return [ 'status' => 0, 'type' => '', 'message' => __( 'User has not completed any quiz', 'wp-marketing-automations-pro' ) ];
		}

		/** handling if selected_quiz is selected */
		if ( isset( $automation_data['event_meta']['quizzes'] ) && 'selected_quiz' === $automation_data['event_meta']['quizzes'] ) {
			$event_quiz_ids = array_column( $automation_data['event_meta']['quiz_id'], 'id' );

			if ( ! in_array( $quiz_id, $event_quiz_ids ) ) {
				return [ 'status' => 0, 'type' => '', 'message' => __( 'No quizzes are matching with event quizzes', 'wp-marketing-automations-pro' ) ];
			}
		}

		$this->email      = $contact->get_email();
		$this->contact_id = $cid;
		$this->user_id    = $contact->get_wpid();
		$this->user       = get_user_by( 'ID', $contact->get_wpid() );
		$this->quiz       = get_post( $quiz_id );
		$quiz_data        = get_user_meta( $this->user_id, '_sfwd-quizzes' );
		$quiz_data        = array_filter( $quiz_data[0], function ( $quizdata ) use ( $quiz_id ) {
			return intval( $quizdata['quiz'] ) === intval( $quiz_id );
		} );
		// taking the last of the array as there could be more attempts of same quiz
		$this->quiz_data    = ! empty( $quiz_data ) ? end( $quiz_data ) : [];
		$this->quiz_answers = ! empty( $this->quiz_data ) ? $this->get_answer_data() : [];


		return array_merge( $automation_data, [
			'contact_id'   => $this->contact_id,
			'email'        => $this->email,
			'user_id'      => $this->user_id,
			'user'         => $this->user,
			'quiz_data'    => $this->quiz_data,
			'quiz_id'      => $quiz_id,
			'quiz'         => $this->quiz,
			'quiz_answers' => $this->quiz_answers
		] );
	}
}

/**
 * Register this event to a source.
 * This will show the current event in dropdown in single automation screen.
 */
if ( bwfan_is_learndash_active() ) {
	return 'BWFAN_LD_User_Completes_Quiz';
}
