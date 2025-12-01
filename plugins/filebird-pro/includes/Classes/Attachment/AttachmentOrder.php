<?php
namespace FileBird\Classes\Attachment;

defined( 'ABSPATH' ) || exit;

use FileBird\Model\UserSettingModel;
use FileBird\Classes\Helpers;

class AttachmentOrder {
	private $query;
	private $clauses;
	private $userSettingModel;

	public function __construct( $query, $clauses ) {
		$this->query            = $query;
		$this->clauses          = $clauses;
		$this->userSettingModel = UserSettingModel::getInstance();
	}

	public function apply_order() {
		global $wpdb;

		$meta_key     = AttachmentSize::META_KEY;
		$order        = $this->query->get( 'order' );
		$orderby      = $this->query->get( 'orderby' );
		$option_order = $this->userSettingModel->get( 'DEFAULT_SORT_FILES' );

		if ( $orderby === $meta_key && ! empty( $order ) ) {
			$this->clauses['join']   .= " LEFT JOIN {$wpdb->postmeta} AS fbmt ON ({$wpdb->posts}.ID = fbmt.post_id AND fbmt.meta_key = '{$meta_key}') ";
			$this->clauses['orderby'] = " fbmt.meta_value + 0 {$order} ";
		}

        if ( $orderby === 'fb_filename' ) {
			$this->clauses['fields'] .= ' ,SUBSTRING_INDEX(postmeta.meta_value, \'/\', -1) as postfilename ';
			$this->clauses['join']   .= " LEFT JOIN {$wpdb->postmeta} AS postmeta ON ({$wpdb->posts}.ID = postmeta.post_id AND postmeta.meta_key = '_wp_attached_file') ";
			$this->clauses['orderby'] = " CAST(postfilename AS UNSIGNED), postfilename {$order} ";
		}

		if ( Helpers::isListMode() && ! \is_null( $option_order ) && 'default' !== $option_order['orderby'] ) {
			if ( '' === $orderby ) {
				$this->clauses['orderby'] = "{$wpdb->posts}.post_{$option_order['orderby']} {$option_order['order']}";
			}
		}

		return $this->clauses;
	}
}