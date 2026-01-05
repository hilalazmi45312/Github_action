<?php

class BWFAN_Pro_Rules {

	private static $ins = null;
	protected $rule_types = null;
	protected $custom_fields = null;

	private function __construct() {
		add_action( 'bwfan_rules_included', [ $this, 'include_rules' ] );
		add_action( 'bwfan_rules_input_included', [ $this, 'include_inputs' ] );

		add_filter( 'bwfan_rules_groups', [ $this, 'add_rule_group' ] );
		add_filter( 'bwfan_rule_get_rule_types', [ $this, 'add_rule_type' ] );

		add_filter( 'bwfan_modify_rule_class', [ $this, 'append_contact_field_rules' ], 10, 2 );
	}

	public static function get_instance() {
		if ( is_null( self::$ins ) ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function add_rule_group( $group ) {
		if ( class_exists( 'WFACP_Core' ) || class_exists( 'WFOCU_Common' ) ) {
			$group['woofunnels'] = array(
				'title' => __( 'Funnels', 'wp-marketing-automations-pro' )
			);
		}
		if ( class_exists( 'WFOCU_Common' ) ) {
			$group['upstroke_funnel']        = array(
				'title' => __( 'Funnels', 'wp-marketing-automations-pro' )
			);
			$group['upstroke_funnel_offers'] = array(
				'title' => 'Funnels',
			);
		}
		if ( class_exists( 'WFACP_Core' ) ) {
			$group['aerocheckout'] = array(
				'title' => __( 'Funnels', 'wp-marketing-automations-pro' )
			);
		}
		if ( bwfan_is_affiliatewp_active() ) {
			$group['affiliatewp']      = array(
				'title' => __( 'AffiliateWP', 'wp-marketing-automations-pro' ),
			);
			$group['affiliate_report'] = array(
				'title' => __( 'AffiliateWP Digest Range', 'wp-marketing-automations-pro' ),
			);
		}
		if ( bwfan_is_gforms_active() ) {
			$group['gforms'] = array(
				'title' => __( 'Gravity Forms', 'wp-marketing-automations-pro' ),
			);
		}

		if ( bwfan_is_elementorpro_active() ) {
			$group['elementor-forms'] = array(
				'title' => __( 'Elementor Forms', 'wp-marketing-automations-pro' ),
			);
		}

		if ( bwfan_is_tve_active() ) {
			$group['thrive-forms'] = array(
				'title' => __( 'Thrive Leads', 'wp-marketing-automations-pro' ),
			);
		}
		if ( bwfan_is_tve_architect_active() ) {
			$group['thrive-architect-forms'] = array(
				'title' => __( 'Thrive Forms', 'wp-marketing-automations-pro' ),
			);
		}
		if ( bwfan_is_wpforms_active() ) {
			$group['wpforms'] = array(
				'title' => __( 'WPForms', 'wp-marketing-automations-pro' ),
			);
		}

		if ( bwfan_is_woocommerce_membership_active() ) {
			$group['wc_member'] = array(
				'title' => __( 'WooCommerce Memberships', 'wp-marketing-automations-pro' ),
			);
		}

		if ( bwfan_is_woocommerce_subscriptions_active() ) {
			$group['wc_subscription'] = array(
				'title' => __( 'WooCommerce Subscriptions', 'wp-marketing-automations-pro' ),
			);
		}

		if ( bwfan_is_learndash_active() ) {
			$group['learndash_course']      = array(
				'title' => __( 'LearnDash Course', 'wp-marketing-automations-pro' ),
			);
			$group['learndash_lesson']      = array(
				'title' => __( 'LearnDash Lesson', 'wp-marketing-automations-pro' ),
			);
			$group['learndash_topic']       = array(
				'title' => __( 'LearnDash Topic', 'wp-marketing-automations-pro' ),
			);
			$group['learndash_quiz_result'] = array(
				'title' => __( 'LearnDash Quiz Result', 'wp-marketing-automations-pro' ),
			);
			$group['learndash_quiz']        = array(
				'title' => __( 'LearnDash Quiz', 'wp-marketing-automations-pro' ),
			);
			$group['learndash_group']       = array(
				'title' => __( 'LearnDash Group', 'wp-marketing-automations-pro' ),
			);
		}

		/** checking ninja exists **/
		if ( function_exists( 'Ninja_Forms' ) ) {
			$group['ninjaforms'] = array(
				'title' => __( 'Ninja Forms', 'wp-marketing-automations-pro' ),
			);
		}

		/** checking fluent exists **/
		if ( bwfan_is_fluent_forms_active() ) {
			$group['fluentforms'] = array(
				'title' => __( 'Fluent Forms', 'wp-marketing-automations-pro' ),
			);
		}
		/** checking forminator  exists **/
		if ( bwfan_is_forminator_forms_active() ) {
			$group['forminator_forms'] = array(
				'title' => __( 'Forminator Forms', 'wp-marketing-automations-pro' ),
			);
		}

		/** checking fluent exists **/
		if ( function_exists( 'bwfan_is_wlm_active' ) && bwfan_is_wlm_active() ) {
			$group['wlm_forms'] = array(
				'title' => __( 'Wishlist Member Form', 'wp-marketing-automations-pro' ),
			);

			$group['wlm'] = array(
				'title' => __( 'Wishlist Member', 'wp-marketing-automations-pro' ),
			);
		}

		/** checking  CRM list class */

		if ( class_exists( 'BWFCRM_Lists' ) ) {
			$group['bwfan_crm_lists'] = array(
				'title' => __( 'CRM Lists', 'wp-marketing-automations-pro' ),
			);
		}

		if ( class_exists( 'BWFCRM_Tag' ) ) {
			$group['bwfan_crm_tags'] = array(
				'title' => __( 'CRM Tags', 'wp-marketing-automations-pro' ),
			);
		}

		/** checking Caldera exists **/
		if ( bwfan_is_caldera_forms_active() ) {
			$group['calderaforms'] = array(
				'title' => __( 'Caldera Forms', 'wp-marketing-automations-pro' ),
			);
		}

		/** checking Optin exists **/
		if ( function_exists( 'bwfan_is_optin_forms_active' ) && bwfan_is_optin_forms_active() ) {
			$group['optinforms'] = array(
				'title' => __( 'Optin Forms', 'wp-marketing-automations-pro' ),
			);
		}

		/** BWF_Contact Group */
		$group['bwf_contact'] = array(
			'title' => __( 'Contact Details', 'wp-marketing-automations-pro' )
		);

		$group['bwf_contact_segments'] = array(
			'title' => __( 'Segments', 'wp-marketing-automations-pro' )
		);

		$group['bwf_contact_fields'] = array(
			'title' => __( 'Contact Field', 'wp-marketing-automations-pro' )
		);

		$group['bwf_contact_user'] = array(
			'title' => __( 'User', 'wp-marketing-automations-pro' )
		);

		$group['bwf_contact_wc'] = array(
			'title' => __( 'WooCommerce', 'wp-marketing-automations-pro' )
		);

		$group['bwf_contact_geo'] = array(
			'title' => __( 'Geography', 'wp-marketing-automations-pro' )
		);

		$group['bwf_engagement'] = array(
			'title' => __( 'Engagement', 'wp-marketing-automations-pro' )
		);

		$group['bwf_broadcast'] = array(
			'title' => __( 'Broadcast', 'wp-marketing-automations-pro' )
		);

		if ( bwfan_is_wc_wishlist_active() ) {
			$group['wishlist_items'] = array(
				'title' => __( 'Wishlist', 'wp-marketing-automations-pro' ),
			);
		}

		if ( function_exists( 'bwfan_is_divi_forms_active' ) && bwfan_is_divi_forms_active() ) {
			$group['divi_forms'] = array(
				'title' => __( 'Divi Forms', 'wp-marketing-automations-pro' ),
			);
		}
		/** Breakdance forms */
		if ( function_exists( 'bwfan_is_breakdance_active' ) && bwfan_is_breakdance_active() ) {
			$group['breakdance_forms'] = array(
				'title' => __( 'Breakdance Form', 'wp-marketing-automations-pro' ),
			);
		}
		if ( function_exists( 'bwfan_is_funnel_active' ) && bwfan_is_funnel_active() ) {
			$group['funnel'] = array(
				'title' => __( 'Funnel', 'wp-marketing-automations-pro' ),
			);
		}

		$group['bwf_automation'] = array(
			'title' => __( 'Automation', 'wp-marketing-automations-pro' )
		);
		if ( defined( 'BWFAN_VERSION' ) && version_compare( BWFAN_VERSION, '3.0.3', '>=' ) ) {
			$group['bwf_date_time'] = array(
				'title' => __( 'DateTime', 'wp-marketing-automations-pro' )
			);
		}


		if ( BWFAN_PRO_Common::is_language_enabled() ) {
			$group['languages'] = array(
				'title' => __( 'Languages', 'wp-marketing-automations-pro' ),
			);
		}

		$group['bwf_webhook'] = array(
			'title' => __( 'Webhook', 'wp-marketing-automations-pro' )
		);

		if ( function_exists( 'bwfan_is_yith_wishlist_active' ) && bwfan_is_yith_wishlist_active() ) {
			$group['yith_wishlist'] = array(
				'title' => __( 'Yith Wishlist', 'wp-marketing-automations-pro' ),
			);
		}
		if ( function_exists( 'bwfan_is_ti_wc_wishlist_active' ) && bwfan_is_ti_wc_wishlist_active() ) {
			$group['ti_wc_wishlist'] = array(
				'title' => __( 'TI WooCommerce Wishlist', 'wp-marketing-automations-pro' ),
			);
		}

		if ( bwfan_is_woocommerce_active() && bwfan_is_funnel_active() && bwfan_is_fk_stripe_active() ) {
			$group['fk_stripe_upsell'] = [
				'title' => __( 'Offers', 'wp-marketing-automations-pro' ),
			];
		}

		return $group;
	}

	public function add_rule_type( $types ) {
		/** BWF Contact */
		$types['bwf_contact'] = array(
			'contact_first_name'    => __( 'First Name', 'wp-marketing-automations-pro' ),
			'contact_last_name'     => __( 'Last Name', 'wp-marketing-automations-pro' ),
			'contact_email'         => __( 'Email', 'wp-marketing-automations-pro' ),
			'contact_phone'         => __( 'Phone', 'wp-marketing-automations-pro' ),
			'contact_creation_date' => __( 'Contact Created', 'wp-marketing-automations-pro' ),
			'contact_creation_days' => __( 'Contact Created (Days)', 'wp-marketing-automations-pro' ),
			'contact_company'       => __( 'Company', 'wp-marketing-automations-pro' ),
			'contact_gender'        => __( 'Gender', 'wp-marketing-automations-pro' ),
			'contact_dob'           => __( 'Date Of Birth is', 'wp-marketing-automations-pro' ),
		);

		$types['bwf_contact_segments'] = array(
			'contact_tags'              => __( 'Tags', 'wp-marketing-automations-pro' ),
			'contact_lists'             => __( 'Lists', 'wp-marketing-automations-pro' ),
			'contact_audience'          => __( 'Audience', 'wp-marketing-automations-pro' ),
			'customer_marketing_status' => __( 'Status', 'woocommerce' ),
		);
		$types['bwf_contact_fields']   = array(
			'customer_custom_field' => __( 'Fields', 'wp-marketing-automations-pro' ),
			/** Used older customer slug to provide legacy compatibility */
		);

		$types = $this->add_contact_fields_rule_types( $types );

		$types['bwf_contact_geo'] = array(
			'contact_country'   => __( 'Country', 'wp-marketing-automations-pro' ),
			'contact_state'     => __( 'State', 'wp-marketing-automations-pro' ),
			'contact_address_1' => __( 'Address 1', 'wp-marketing-automations-pro' ),
			'contact_address_2' => __( 'Address 2', 'wp-marketing-automations-pro' ),
			'contact_city'      => __( 'City', 'wp-marketing-automations-pro' ),
			'contact_post_code' => __( 'Post Code', 'wp-marketing-automations-pro' ),
		);

		if ( ! isset( $types['bwf_contact_user'] ) ) {
			$types['bwf_contact_user'] = [];
		}
		$types['bwf_contact_user'] = array_merge( $types['bwf_contact_user'], array(
			'customer_is_wp_user' => __( 'Is User', 'wp-marketing-automations-pro' ),
			'customer_role'       => __( 'User Role', 'wp-marketing-automations-pro' ),
		) );

		if ( class_exists( 'WooCommerce' ) ) {
			$types['bwf_contact_wc'] = array(
				'customer_total_spent'         => __( 'Total Revenue', 'wp-marketing-automations-pro' ),
				'customer_order_count'         => __( 'Total Orders Count', 'wp-marketing-automations-pro' ),
				'customer_purchased_products'  => __( 'Purchased Products', 'wp-marketing-automations-pro' ),
				'customer_purchased_cat'       => __( 'Purchased Products Categories', 'wp-marketing-automations-pro' ),
				'customer_purchased_tags'      => __( 'Purchased Products Tags', 'wp-marketing-automations-pro' ),
				'customer_has_purchased'       => __( 'Has Made A Purchase', 'wp-marketing-automations-pro' ),
				'customer_has_used_coupons'    => __( 'Has Used Coupons', 'wp-marketing-automations-pro' ),
				'customer_first_order_date'    => __( 'First order was', 'wp-marketing-automations-pro' ),
				'customer_last_order_date'     => __( 'Last order was', 'wp-marketing-automations-pro' ),
				'customer_last_order_days'     => __( 'Last order Days', 'wp-marketing-automations-pro' ),
				'customer_average_order_value' => __( 'Average Order Value is', 'wp-marketing-automations-pro' ),
				'customer_used_coupons'        => __( 'Used Coupons', 'wp-marketing-automations-pro' ),
			);
			if ( bwfan_is_woocommerce_subscriptions_active() ) {
				$types['bwf_contact_wc']['active_subscription'] = __( 'Has Active Subscription', 'wp-marketing-automations-pro' );
			}
		}

		$types['bwf_engagement'] = array(
			'email_opens'          => __( 'Email Opens', 'wp-marketing-automations-pro' ),
			'engaged'              => __( 'Engaged', 'wp-marketing-automations-pro' ),
			'unengaged'            => __( 'Unengaged', 'wp-marketing-automations-pro' ),
			'link_trigger_clicked' => __( 'Link Trigger Clicked', 'wp-marketing-automations-pro' ),
			'last_login'           => __( 'Last Login', 'wp-marketing-automations-pro' ),
			'last_open'            => __( 'Last Open', 'wp-marketing-automations-pro' ),
			'last_sent'            => __( 'Last Sent', 'wp-marketing-automations-pro' ),
			'last_clicked'         => __( 'Last Clicked', 'wp-marketing-automations-pro' ),
		);

		$types['bwf_broadcast'] = array(
			'broadcast_sent'    => __( 'Broadcast Sent', 'wp-marketing-automations-pro' ),
			'broadcast_open'    => __( 'Broadcast Open', 'wp-marketing-automations-pro' ),
			'broadcast_clicked' => __( 'Broadcast Clicked', 'wp-marketing-automations-pro' ),
		);

		if ( class_exists( 'WFACP_Core' ) ) {
			$types['woofunnels']['aerocheckout'] = __( 'Checkout Page', 'wp-marketing-automations-pro' );
			$types['aerocheckout']               = array(
				'aerocheckout' => __( 'Checkout Page', 'wp-marketing-automations-pro' ),
			);
		}
		if ( class_exists( 'WFOCU_Common' ) ) {
			$types['woofunnels']['upstroke_funnels'] = __( 'Upsell', 'wp-marketing-automations-pro' );
			$types['woofunnels']['upstroke_offers']  = __( 'Upsell Offers', 'wp-marketing-automations-pro' );
			$types['upstroke_funnel']                = array(
				'upstroke_funnels' => __( 'Upsell', 'wp-marketing-automations-pro' ),
			);
			$types['upstroke_funnel_offers']         = array(
				'upstroke_funnels' => __( 'Upsell', 'wp-marketing-automations-pro' ),
				'upstroke_offers'  => __( 'Upsell Offers', 'wp-marketing-automations-pro' ),
			);
		}
		if ( class_exists( 'WooCommerce' ) && bwfan_is_woocommerce_subscriptions_active() ) {
			$types['wc_order']['is_order_renewal']                        = __( 'Is Renewal', 'wp-marketing-automations-pro' );
			$types['wc_subscription']['subscription_status']              = __( 'Subscription Status', 'wp-marketing-automations-pro' );
			$types['wc_subscription']['subscription_total']               = __( 'Subscription Total', 'wp-marketing-automations-pro' );
			$types['wc_subscription']['subscription_parent_order_status'] = __( 'Subscription Parent Order Status', 'wp-marketing-automations-pro' );
			$types['wc_subscription']['subscription_item']                = __( 'Subscription Items', 'wp-marketing-automations-pro' );
			$types['wc_subscription']['subscription_payment_gateway']     = __( 'Subscription Payment Gateway', 'wp-marketing-automations-pro' );
			$types['wc_subscription']['subscription_old_status']          = __( 'Subscription Old Status', 'wp-marketing-automations-pro' );
			$types['wc_subscription']['subscription_note_text_match']     = __( 'Subscription Note Text', 'wp-marketing-automations-pro' );
			$types['wc_subscription']['subscription_payment_count']       = __( 'Subscription Payment Count', 'wp-marketing-automations-pro' );
			$types['wc_subscription']['subscription_coupons']             = __( 'Subscription Coupon', 'wp-marketing-automations-pro' );
			$types['wc_subscription']['subscription_coupon_text_match']   = __( 'Subscription Coupon Text', 'wp-marketing-automations-pro' );
			if ( 'yes' === get_option( 'woocommerce_subscriptions_enable_retry' ) ) {
				$types['wc_subscription']['subscription_failed_attempt'] = __( 'Subscription Failed Attempt', 'wp-marketing-automations-pro' );
			}
		}
		if ( class_exists( 'WooCommerce' ) ) {
			$new_arr = [];
			foreach ( $types['wc_order'] as $key => $rule ) {
				$new_arr[ $key ] = $rule;
				if ( 'product_item' === $key ) {
					$new_arr['customer_past_purchased_products'] = __( 'Past Purchased Products', 'wp-marketing-automations-pro' );
				}
			}
			$types['wc_order'] = $new_arr;
		}
		if ( bwfan_is_advanced_coupon_for_woocommerce_active() && bwfan_is_woocommerce_active() ) {
			$types['bwf_contact']['credit_amount'] = __( 'Total Credits', 'wp-marketing-automations-pro' );

			if ( bwfan_is_loyalty_program_for_woocommerce_active() ) {
				$types['bwf_contact']['loyalty_points'] = __( 'Loyalty Points', 'wp-marketing-automations-pro' );
			}
		}
		if ( bwfan_is_affiliatewp_active() ) {
			$types['affiliatewp']['affiliate_total_earnings']            = __( 'Affiliate Total Earnings', 'wp-marketing-automations-pro' );
			$types['affiliatewp']['affiliate_unpaid_amount']             = __( 'Affiliate Unpaid Earnings', 'wp-marketing-automations-pro' );
			$types['affiliatewp']['affiliate_total_visits']              = __( 'Affiliate Total Visits', 'wp-marketing-automations-pro' );
			$types['affiliate_report']['selected_range_referrals_count'] = __( 'Referral Count (Selected Frequency)', 'wp-marketing-automations-pro' );
			$types['affiliate_report']['selected_range_visits']          = __( 'Referral Visits (Selected Frequency)', 'wp-marketing-automations-pro' );
			$types['affiliatewp']['affiliate_rate']                      = __( 'Affiliate Rate', 'wp-marketing-automations-pro' );
		}
		if ( bwfan_is_gforms_active() ) {
			$types['gforms']['gf_form_field'] = __( 'Form Fields', 'wp-marketing-automations-pro' );
		}

		if ( bwfan_is_elementorpro_active() ) {
			$types['elementor-forms']['elementor_form_field'] = __( 'Form Fields', 'wp-marketing-automations-pro' );
		}

		if ( bwfan_is_wpforms_active() ) {
			$types['wpforms']['wpforms_form_field'] = __( 'Form Fields', 'wp-marketing-automations-pro' );
		}
		if ( bwfan_is_tve_active() ) {
			$types['thrive-forms']['tve_form_field'] = __( 'Form Fields', 'wp-marketing-automations-pro' );
		}
		if ( bwfan_is_tve_architect_active() ) {
			$types['thrive-architect-forms']['tve_architect_form_fields'] = __( 'Form Fields', 'wp-marketing-automations-pro' );
		}

		if ( function_exists( 'Ninja_Forms' ) ) {
			$types['ninjaforms']['ninja_form_field'] = __( 'Form Fields', 'wp-marketing-automations-pro' );
		}

		if ( bwfan_is_fluent_forms_active() ) {
			$types['fluentforms']['fluent_form_field'] = __( 'Form Fields', 'wp-marketing-automations-pro' );
		}

		if ( bwfan_is_caldera_forms_active() ) {
			$types['calderaforms']['caldera_form_field'] = __( 'Form Fields', 'wp-marketing-automations-pro' );
		}

		if ( function_exists( 'bwfan_is_optin_forms_active' ) && bwfan_is_optin_forms_active() ) {
			$types['optinforms']['optin_form_field'] = __( 'Form Fields', 'wp-marketing-automations-pro' );
		}
		/** Forminator forms */
		if ( function_exists( 'bwfan_is_forminator_forms_active' ) && bwfan_is_forminator_forms_active() ) {
			$types['forminator_forms']['forminator_form_field'] = __( 'Form Fields', 'wp-marketing-automations-pro' );
		}

		if ( class_exists( 'WooCommerce' ) && bwfan_is_woocommerce_membership_active() ) {
			$types['wc_member']['membership_has_status']   = __( 'Membership Has Status', 'wp-marketing-automations-pro' );
			$types['wc_member']['active_membership_plans'] = __( 'Membership Has Active Plans', 'wp-marketing-automations-pro' );
		}

		if ( bwfan_is_learndash_active() ) {
			$types['learndash_quiz_result']['learndash_quiz_percentage'] = __( 'Quiz Percentage', 'wp-marketing-automations-pro' );
			$types['learndash_quiz_result']['learndash_quiz_points']     = __( 'Quiz Points', 'wp-marketing-automations-pro' );
			$types['learndash_quiz_result']['learndash_quiz_score']      = __( 'Quiz Score (No. of correct answers)', 'wp-marketing-automations-pro' );
			$types['learndash_quiz_result']['learndash_quiz_timespent']  = __( 'Quiz Time Spent (in seconds)', 'wp-marketing-automations-pro' );
			$types['learndash_quiz_result']['learndash_quiz_result']     = __( 'User passed the Quiz', 'wp-marketing-automations-pro' );
			$types['learndash_course']['learndash_course']               = __( ' Enrolled Courses', 'wp-marketing-automations-pro' );
			$types['learndash_course']['learndash_course_completed']     = __( 'Courses Completed', 'wp-marketing-automations-pro' );
			$types['learndash_course']['learndash_courses_started']      = __( 'Courses Started', 'wp-marketing-automations-pro' );
			$types['learndash_course']['learndash_course_progress']      = __( 'Course Progress (%)', 'wp-marketing-automations-pro' );
			$types['learndash_lesson']['learndash_lesson']               = __( 'Lesson', 'wp-marketing-automations-pro' );
			$types['learndash_topic']['learndash_topic']                 = __( 'Topic', 'wp-marketing-automations-pro' );
			$types['learndash_quiz']['learndash_quiz']                   = __( 'Quiz', 'wp-marketing-automations-pro' );
			$types['learndash_group']['learndash_group']                 = __( 'Group', 'wp-marketing-automations-pro' );
			$types['learndash_course']['learndash_course_started']       = __( 'Has course started', 'wp-marketing-automations-pro' );
			$types['learndash_course']['learndash_has_course_completed'] = __( 'Has Course Completed', 'wp-marketing-automations-pro' );

			$group_category = get_option( 'learndash_settings_groups_taxonomies', [] );
			if ( isset( $group_category['ld_group_category'] ) && 'yes' === $group_category['ld_group_category'] ) {
				$types['learndash_group']['learndash_group_category'] = __( 'Group Category', 'wp-marketing-automations-pro' );
			}

		}

		/** Wishlist rules */
		if ( bwfan_is_wc_wishlist_active() ) {
			$types['wishlist_items']['wishlist_item'] = __( 'Items', 'wp-marketing-automations-pro' );
		}

		/** Yith Wishlist rules */
		if ( bwfan_is_yith_wishlist_active() ) {
			$types['yith_wishlist']['yith_wishlist_item'] = __( 'Wishlist Product', 'wp-marketing-automations-pro' );
		}
		/** Ti Wc Wishlist rules */
		if ( bwfan_is_ti_wc_wishlist_active() ) {
			$types['ti_wc_wishlist']['ti_wc_wishlist_item']       = __( 'Wishlist Product', 'wp-marketing-automations-pro' );
			$types['ti_wc_wishlist']['ti_wc_wishlist_item_count'] = __( 'Wishlist Product Count', 'wp-marketing-automations-pro' );
		}

		/** Advanced shipping rates rules */
		if ( bwfan_is_woocommerce_advanced_shipping_pro_active() ) {
			$types['wc_order']['advanced_shipping_rate'] = __( 'Advanced Shipping Rates', 'wp-marketing-automations-pro' );
		}

		/** Wishlist member rules */
		if ( function_exists( 'bwfan_is_wlm_active' ) && bwfan_is_wlm_active() ) {
			$types['wlm_forms']['wlm_form_field'] = __( 'Form Fields', 'wp-marketing-automations-pro' );
			$types['wlm']['wlm_level']            = __( 'Membership Level', 'wp-marketing-automations-pro' );
		}

		/** Divi forms rules */
		if ( function_exists( 'bwfan_is_divi_forms_active' ) && bwfan_is_divi_forms_active() ) {
			$types['divi_forms']['divi_form_field'] = __( 'Form Fields', 'wp-marketing-automations-pro' );
		}
		/** Break dance forms  */
		if ( function_exists( 'bwfan_is_breakdance_active' ) && bwfan_is_breakdance_active() ) {
			$types['breakdance_forms']['breakdance_form_field'] = __( 'Form Fields', 'wp-marketing-automations-pro' );
		}

		if ( function_exists( 'bwfan_is_funnel_active' ) && bwfan_is_funnel_active() ) {
			$types['funnel']['funnel'] = __( 'Funnel', 'wp-marketing-automations-pro' );
		}

		$types['bwf_automation']['ever_entered_automation']     = __( 'Has ever entered in Automation', 'wp-marketing-automations-pro' );
		$types['bwf_automation']['currently_active_automation'] = __( 'Currently active in Automation', 'wp-marketing-automations-pro' );
		$types['bwf_automation']['completed_automation']        = __( 'Completed the Automation', 'wp-marketing-automations-pro' );
		$types['bwf_webhook']['webhook_received']               = __( 'Webhook', 'wp-marketing-automations-pro' );
		if ( defined( 'BWFAN_VERSION' ) && version_compare( BWFAN_VERSION, '3.0.3', '>=' ) ) {
			$types['bwf_date_time'] = array(
				'current_year'         => __( 'Current Year', 'wp-marketing-automations-pro' ),
				'current_month'        => __( 'Current Month', 'wp-marketing-automations-pro' ),
				'current_day_of_month' => __( 'Current Day Of Month', 'wp-marketing-automations-pro' ),
				'current_day_of_week'  => __( 'Current Day Of Week', 'wp-marketing-automations-pro' ),
				'current_time'         => __( 'Current Time', 'wp-marketing-automations-pro' ),
				'current_date_time'    => __( 'Current Date Time', 'wp-marketing-automations-pro' ),
			);
		}

		if ( BWFAN_PRO_Common::is_language_enabled() ) {
			$types['languages'] = array(
				'language' => __( 'Language', 'wp-marketing-automations-pro' ),
			);
		}

		if ( bwfan_is_woocommerce_active() && bwfan_is_funnel_active() && bwfan_is_fk_stripe_active() ) {
			$types['fk_stripe_upsell'] = [
				'stripe_offers' => __( 'Upsell Offers', 'wp-marketing-automations-pro' ),
				'stripe_bumps'         => __( 'Bumps', 'wp-marketing-automations-pro' ),
			];
		}

		return $types;
	}

	/**
	 * @param $val
	 * @param $rule_type
	 *
	 * @return BWFAN_Rule_Contact_Field|mixed
	 */
	public function append_contact_field_rules( $val, $rule_type ) {
		$types = $this->rule_types;
		if ( is_null( $types ) ) {
			$types = apply_filters( 'bwfan_rule_get_rule_types', array() );

			$this->rule_types = $types;
		}

		if ( ! isset( $types['bwf_contact_fields'][ $rule_type ] ) ) {
			return $val;
		}

		$field_id = str_replace( 'bwf_contact_field_f', '', $rule_type );
		$fields   = $this->get_custom_fields( true );
		if ( ! isset( $fields[ $field_id ] ) ) {
			return $val;
		}

		return new BWFAN_Rule_Contact_Field( $rule_type, $fields[ $field_id ]['type'] );
	}

	public function add_contact_fields_rule_types( $types ) {
		$fields = $this->get_custom_fields();
		$page   = filter_input( INPUT_GET, 'page' );

		if ( 'autonami-automations' !== $page ) {
			unset( $types['bwf_contact_fields']['customer_custom_field'] );
		}
		$data = [];
		foreach ( $fields as $id => $name ) {
			$data[ 'bwf_contact_field_f' . $id ] = $name;
		}

		$types['bwf_contact_fields'] = array_merge( $types['bwf_contact_fields'], $data );

		return $types;
	}

	public function get_custom_fields( $more_data = false ) {
		$fields = $this->custom_fields;
		if ( is_null( $fields ) ) {
			$fields = BWFCRM_Fields::get_custom_fields( null, null, null, null, 1, null );

			$this->custom_fields = $fields;
		}

		$sorted_fields = BWFAN_PRO_Common::get_sorted_fields( $fields );
		$fields        = ! empty( $sorted_fields ) ? $sorted_fields : $fields;
		if ( empty( $fields ) ) {
			return [];
		}

		$field_data = array();
		foreach ( $fields as $field ) {
			if ( ! isset( $field['ID'] ) ) {
				continue;
			}
			if ( true === $more_data ) {
				$field_data[ $field['ID'] ] = $field;
				continue;
			}
			$field_data[ $field['ID'] ] = $field['name'];

		}

		return $field_data;
	}

	public function include_rules() {
		include_once __DIR__ . '/rules/bwf-customer.php';
		include_once __DIR__ . '/rules/bwf-contact.php';
		include_once __DIR__ . '/rules/bwf-contact-fields.php';
		include_once __DIR__ . '/rules/automation.php';
		include_once __DIR__ . '/rules/engagement.php';
		include_once __DIR__ . '/rules/webhook-received.php';
		if ( defined( 'BWFAN_VERSION' ) && version_compare( BWFAN_VERSION, '3.0.3', '>=' ) ) {
			include_once __DIR__ . '/rules/bwf-date-time.php';
		}

		if ( bwfan_is_woocommerce_subscriptions_active() ) {
			include_once __DIR__ . '/rules/subscriptions.php';
		}
		/** Include only if any of the woofunnel plugin activated */
		if ( class_exists( 'WFACP_Core' ) || class_exists( 'WFOCU_Common' ) ) {
			include_once __DIR__ . '/rules/woofunnels.php';
		}
		if ( bwfan_is_affiliatewp_active() ) {
			include_once __DIR__ . '/rules/affiliatewp.php';
		}
		if ( bwfan_is_gforms_active() ) {
			include_once __DIR__ . '/rules/gforms.php';
		}

		/** advanced shipping rates */
		if ( bwfan_is_woocommerce_advanced_shipping_pro_active() ) {
			include_once __DIR__ . '/rules/wc_advance_shipping.php';
		}

		if ( bwfan_is_elementorpro_active() ) {
			include_once __DIR__ . '/rules/elementorforms.php';
		}

		if ( bwfan_is_wpforms_active() ) {
			include_once __DIR__ . '/rules/wpforms.php';
		}

		if ( function_exists( 'Ninja_Forms' ) ) {
			include_once __DIR__ . '/rules/ninjaforms.php';
		}

		if ( bwfan_is_fluent_forms_active() ) {
			include_once __DIR__ . '/rules/fluentforms.php';
		}

		if ( bwfan_is_caldera_forms_active() ) {
			include_once __DIR__ . '/rules/calderaforms.php';
		}

		if ( bwfan_is_tve_active() ) {
			include_once __DIR__ . '/rules/thriveforms.php';
		}
		if ( bwfan_is_tve_architect_active() ) {
			include_once __DIR__ . '/rules/thrive-architect-forms.php';
		}
		/** Added Forminator rule */
		if ( bwfan_is_forminator_forms_active() ) {
			include_once __DIR__ . '/rules/forminator.php';
		}

		if ( bwfan_is_woocommerce_membership_active() ) {
			include_once __DIR__ . '/rules/memberships.php';
		}

		if ( bwfan_is_learndash_active() ) {
			include_once __DIR__ . '/rules/learndash.php';
		}

		if ( function_exists( 'bwfan_is_optin_forms_active' ) && bwfan_is_optin_forms_active() ) {
			include_once __DIR__ . '/rules/optinforms.php';
		}

		if ( bwfan_is_wc_wishlist_active() ) {
			include_once __DIR__ . '/rules/wishlist.php';
		}

		if ( function_exists( 'bwfan_is_wlm_active' ) && bwfan_is_wlm_active() ) {
			include_once __DIR__ . '/rules/wlm.php';
		}

		if ( function_exists( 'bwfan_is_divi_forms_active' ) && bwfan_is_divi_forms_active() ) {
			include_once __DIR__ . '/rules/divi-forms.php';
		}
		/** Breakdance form */
		if ( function_exists( 'bwfan_is_breakdance_active' ) && bwfan_is_breakdance_active() ) {
			include_once __DIR__ . '/rules/breakdance-form.php';
		}

		/** store credit rule  */
		if ( bwfan_is_advanced_coupon_for_woocommerce_active() && bwfan_is_woocommerce_active() ) {
			include_once __DIR__ . '/rules/bwf-store-credit.php';
		}

		if ( function_exists( 'bwfan_is_funnel_active' ) && bwfan_is_funnel_active() ) {
			include_once __DIR__ . '/rules/funnel.php';
		}

		if ( function_exists( 'bwfan_is_yith_wishlist_active' ) && bwfan_is_yith_wishlist_active() ) {
			include_once __DIR__ . '/rules/bwf-yith-wishlist.php';
		}
		if ( function_exists( 'bwfan_is_ti_wc_wishlist_active' ) && bwfan_is_ti_wc_wishlist_active() ) {
			include_once __DIR__ . '/rules/bwf-ti-wc-wishlist.php';
		}

		if ( BWFAN_PRO_Common::is_language_enabled() ) {
			include_once __DIR__ . '/rules/languages.php';
		}

		if ( bwfan_is_woocommerce_active() && bwfan_is_funnel_active() && bwfan_is_fk_stripe_active() ) {
			include_once BWFAN_PRO_PLUGIN_DIR . '/modules/stripe-offer/rules/upsell-offers.php';
		}
	}

	public function include_inputs() {
		if ( class_exists( 'WFOCU_Common' ) ) {
			include_once __DIR__ . '/html/html-funnel-onetime.php';
			include_once __DIR__ . '/html/html-funnel-products.php';
		}
	}

	public static function make_value_as_array( $value ) {
		if ( is_array( $value ) ) {
			return $value;
		}

		/** Checking if value contains comma */
		if ( strpos( $value, ',' ) !== false ) {
			$value = explode( ',', $value );
			$value = array_map( 'trim', $value );
		} else {
			$value = array( $value );
		}

		return $value;
	}

}

BWFAN_Pro_Rules::get_instance();
