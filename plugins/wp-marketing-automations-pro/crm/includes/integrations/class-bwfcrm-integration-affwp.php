<?php
/**
 * Wp Affiliate Integration Class
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BWFCRM_Integration_Affiliate
 */
class BWFCRM_Integration_Affiliate {

	private $affiliate_id = 0;

	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		if ( ! bwfan_is_affiliatewp_active() ) {
			return;
		}

		/** Single Contact API Response */
		add_filter( 'bwfan_single_contact_get_array', array( $this, 'get_affwp_array' ), 10, 3 );

		/** Contacts Export */
		add_filter( 'bwfcrm_get_export_custom_selections', array( $this, 'get_export_custom_selections' ) );
		add_filter( 'bwfcrm_export_csv_row_before_insert', array( $this, 'insert_affwp_export_data' ), 10, 3 );

		/** Sort Merge Tags */
		add_filter( 'bwfan_crm_merge_tags_api', array( $this, 'sort_affwp_merge_tags' ) );
	}

	public function get_contact_affiliate_details( $contact ) {
		if ( is_numeric( $contact ) ) {
			$contact = new WooFunnels_Contact( '', '', '', absint( $contact ) );
		}

		if ( ! $contact instanceof WooFunnels_Contact || 0 === absint( $contact->get_wpid() ) ) {
			return array();
		}

		return $this->get_affiliate_and_referral_details( $contact->get_wpid() );
	}

	public function maybe_populate_affiliate_id( $user_id, $force = false ) {
		if ( ! empty( $this->affiliate_id ) && false === $force ) {
			return;
		}

		if ( empty( $user_id ) ) {
			return;
		}

		$this->affiliate_id = absint( affiliate_wp()->affiliates->get_column_by( 'affiliate_id', 'user_id', $user_id ) );
	}

	public function get_affiliate_details( $user_id ) {
		$this->maybe_populate_affiliate_id( $user_id );
		$affiliate_id = $this->affiliate_id;

		if ( empty( $affiliate_id ) ) {
			return array(
				'affiliate_id'       => '-',
				'affiliate_url'      => '-',
				'status'             => '-',
				'total_earnings'     => '-',
				'unpaid_earnings'    => '-',
				'total_referrals'    => '0',
				'total_visits'       => '0',
				'payment_email'      => '-',
				'edit_link'          => '-',
				'registration_date'  => '-',
				'affiliate_status'   => '-',
				'referral_rate_type' => '-',
				'referral_rate'      => '-',
				'promotion_methods'  => '-',
				'affiliate_notes'    => '-',
			);
		}

		$affiliate_url     = affwp_get_affiliate_referral_url( array( 'affiliate_id' => $affiliate_id ) );
		$status            = affwp_get_affiliate_status( $affiliate_id );
		$total_earnings    = affwp_get_affiliate_earnings( $affiliate_id, true );
		$unpaid_earnings   = affwp_get_affiliate_unpaid_earnings( $affiliate_id, true );
		$total_visits      = affwp_get_affiliate_visit_count( $affiliate_id );
		$payment_email     = affwp_get_affiliate_payment_email( $affiliate_id );
		$edit_link         = $this->get_edit_link( $affiliate_id );
		$affiliate         = affwp_get_affiliate( $affiliate_id );
		$registration_date = $affiliate->date_registered;
		$referral_rate     = affwp_get_affiliate_rate( $affiliate_id, true );
		$promotion_methods = get_user_meta( affwp_get_affiliate_user_id( $affiliate_id ), 'affwp_promotion_method', true );
		$affiliate_notes   = affwp_get_affiliate_meta( $affiliate_id, 'notes', true );

		// referrals of all status
		$paid_count     = affiliate_wp()->referrals->count_by_status( 'paid', $affiliate_id, '' );
		$unpaid_count   = affiliate_wp()->referrals->count_by_status( 'unpaid', $affiliate_id, '' );
		$pending_count  = affiliate_wp()->referrals->count_by_status( 'pending', $affiliate_id, '' );
		$rejected_count = affiliate_wp()->referrals->count_by_status( 'rejected', $affiliate_id, '' );

		$aff_count   = [];
		$aff_count[] = ( intval( $paid_count ) > 0 ) ? intval( $paid_count ) . ' ' . __( 'Paid', 'affiliate-wp' ) : '';
		$aff_count[] = ( intval( $unpaid_count ) > 0 ) ? intval( $unpaid_count ) . ' ' . __( 'Unpaid', 'affiliate-wp' ) : '';
		$aff_count[] = ( intval( $pending_count ) > 0 ) ? intval( $pending_count ) . ' ' . __( 'Pending', 'affiliate-wp' ) : '';
		$aff_count[] = ( intval( $rejected_count ) > 0 ) ? intval( $rejected_count ) . ' ' . __( 'Rejected', 'affiliate-wp' ) : '';
		$aff_count   = array_filter( $aff_count );

		return array(
			'affiliate_id'      => $affiliate_id ? $affiliate_id : '-',
			'affiliate_url'     => $affiliate_url ? $affiliate_url : '-',
			'status'            => $status ? affwp_get_affiliate_status_label( $status ) : '-',
			'total_earnings'    => $total_earnings ? $total_earnings : '-',
			'unpaid_earnings'   => $unpaid_earnings ? $unpaid_earnings : '-',
			'total_referrals'   => count( $aff_count ) > 0 ? implode( ' | ', $aff_count ) : 0,
			'total_visits'      => $total_visits ? $total_visits : '0',
			'payment_email'     => $payment_email ? $payment_email : '-',
			'edit_link'         => $edit_link ? $edit_link : '-',
			'registration_date' => $registration_date ? $registration_date : '-',
			'referral_rate'     => $referral_rate ? $referral_rate : '-',
			'promotion_methods' => $promotion_methods ? $promotion_methods : '-',
			'affiliate_notes'   => $affiliate_notes ? $affiliate_notes : '-',
		);
	}

	public function get_affiliate_and_referral_details( $user_id ) {
		$affiliate = $this->get_affiliate_details( $user_id );

		$this->maybe_populate_affiliate_id( $user_id );
		$affiliate_id           = $this->affiliate_id;
		$affiliate['referrals'] = $this->get_referrals( $affiliate_id );

		return array( $affiliate_id => $affiliate );
	}

	public function get_contact_referrals( $user_id, $offset = 0, $limit = 10 ) {
		$this->maybe_populate_affiliate_id( $user_id );
		$affiliate_id = $this->affiliate_id;

		return $this->get_referrals( $affiliate_id, $offset, $limit );
	}

	public function get_referrals( $affiliate_id, $offset = 0, $limit = 10 ) {
		$affiliate_referrals = affiliate_wp()->referrals->get_referrals( array(
			'number'       => $limit,
			'offset'       => $offset,
			'orderby'      => 'date',
			'affiliate_id' => $affiliate_id,
			'fields'       => array( 'referral_id', 'context', 'reference', 'amount', 'type', 'date', 'status' ),
		) );

		$aff_ref = array();
		if ( ! empty( $affiliate_referrals ) ) {
			foreach ( $affiliate_referrals as $ref_data ) {
				$aff_ref[ $ref_data->referral_id ]['source']    = $ref_data->context;
				$aff_ref[ $ref_data->referral_id ]['reference'] = $ref_data->reference;
				$aff_ref[ $ref_data->referral_id ]['amount']    = $ref_data->amount;
				$aff_ref[ $ref_data->referral_id ]['type']      = $ref_data->type;
				$aff_ref[ $ref_data->referral_id ]['date']      = $ref_data->date;
				$aff_ref[ $ref_data->referral_id ]['status']    = $ref_data->status;
			}
		}

		return $aff_ref;
	}

	public function get_affwp_array( $contact_array, $contact, $slim_data = true ) {
		if ( ! is_array( $contact_array ) || ! $contact instanceof BWFCRM_Contact || ! $contact->is_contact_exists() || true === $slim_data ) {
			return $contact_array;
		}

		$user_id = $contact->contact->get_wpid();
		if ( empty( $user_id ) ) {
			return $contact_array;
		}

		$contact_array['affwp'] = $this->get_affiliate_details( absint( $user_id ) );

		return $contact_array;
	}

	/** Export */
	public function get_export_custom_selections( $global_selections = array() ) {
		$affiliates = $this->get_affiliates();
		if ( empty( $affiliates ) ) {
			return $global_selections;
		}

		$global_selections['affiliate'] = array(
			'slug'       => 'affiliate',
			'name'       => __( 'Affiliates', 'wp-marketing-automations-pro' ),
			'selections' => array(
				'affwp_affiliate_id'    => array(
					'slug' => 'affwp_affiliate_id',
					'name' => 'Affiliate ID',
				),
				'affwp_affiliate_url'   => array(
					'slug' => 'affwp_affiliate_url',
					'name' => 'Affiliate URL',
				),
				'affwp_status'          => array(
					'slug' => 'affwp_status',
					'name' => 'Affiliate Status',
				),
				'affwp_total_earnings'  => array(
					'slug' => 'affwp_total_earnings',
					'name' => 'Total Earnings',
				),
				'affwp_unpaid_earnings' => array(
					'slug' => 'affwp_unpaid_earnings',
					'name' => 'Unpaid Earnings',
				),
				'affwp_total_referrals' => array(
					'slug' => 'affwp_total_referrals',
					'name' => 'Total Referrals',
				),
				'affwp_total_visits'    => array(
					'slug' => 'affwp_total_visits',
					'name' => 'Total Visits',
				),
				'affwp_payment_email'   => array(
					'slug' => 'affwp_payment_email',
					'name' => 'Payment Email',
				),
			),
		);

		return $global_selections;
	}

	/** AFFWP Helper Methods */
	public function get_affiliates( $slim_data = true ) {
		$affiliates = affiliate_wp()->affiliates->get_affiliates( array(
			'number' => - 1,
			'fields' => array( 'affiliate_id', 'user_id' ),
		) );

		if ( ! is_array( $affiliates ) ) {
			return array();
		}

		if ( empty( $affiliates ) ) {
			return array();
		}

		return $affiliates;
	}

	public function insert_affwp_export_data( $data, $contact, $fields ) {

		$this->affiliate_id = 0;
		if ( ! is_array( $fields ) || ! $contact instanceof BWFCRM_Contact || ! $contact->is_contact_exists() ) {
			return $data;
		}

		/** Check if affwp_ keys are present in fields */
		$affwp_fields_present = array_reduce( $fields, function ( $carry, $field ) {
			return true === $carry || false !== strpos( $field, 'affwp_' );
		}, false );

		/** Return if no affwp_ fields not found, to prevent unnecessary DB fetching */
		if ( ! $affwp_fields_present ) {
			return $data;
		}

		if ( ! is_array( $data ) ) {
			$data = array();
		}

		$user_id = $contact->contact->get_wpid();
		if ( empty( $user_id ) ) {
			return $data;
		}

		/** Get Contact's affiliate Data */
		$affiliate_data = $this->get_affiliate_details( absint( $user_id ) );
		if ( empty( $affiliate_data ) ) {
			return $data;
		}

		foreach ( $fields as $key => $field ) {
			if ( false === strpos( $field, 'affwp_' ) ) {
				continue;
			}

			$field = explode( 'affwp_', $field );
			$field = $field[1];

			$data[ $key ] = html_entity_decode( $affiliate_data[ $field ] );
		}

		return $data;
	}

	public function sort_affwp_merge_tags( $merge_tags ) {
		if ( ! isset( $merge_tags['affwp'] ) ) {
			return $merge_tags;
		}

		$sorting_order = array(
			'affwp_affiliate_id',
			'affwp_affiliate_url',
			'affwp_affiliate_rate',
			'affwp_affiliate_paid_amount',
			'affwp_affiliate_unpaid_amount',
			'affwp_affiliate_unpaid_referrals_count'
		);
		$sorted_tags   = array();
		foreach ( $sorting_order as $key ) {
			$sorted_tags[ $key ] = $merge_tags['affwp'][ $key ];
		}

		$merge_tags['affwp'] = $sorted_tags;

		return $merge_tags;
	}

	/**
	 * @param $aff_id
	 * getting edit link for affiliate
	 *
	 * @return string
	 */
	public function get_edit_link( $aff_id ) {
		$edit_link = admin_url() . 'admin.php';

		return add_query_arg( array(
			'page'         => 'affiliate-wp-affiliates',
			'action'       => 'edit_affiliate',
			'affiliate_id' => $aff_id,
		), $edit_link );
	}

}


if ( class_exists( 'BWFCRM_Integration_Affiliate' ) ) {
	BWFCRM_Core()->integrations->register( 'BWFCRM_Integration_Affiliate' );
}
