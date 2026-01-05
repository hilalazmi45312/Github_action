<?php

namespace BWFAN\Exporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Carts extends Base {
	private $status = '';

	public function __construct( $type ) {
		$this->type   = $type;
		$this->status = $this->type === 'lost_carts' ? 2 : '';
	}

	/**
	 * Create file with headers
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	private function create_file_with_headers( $name ) {
		global $wp_filesystem;
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}
		WP_Filesystem();

		$file_name = $name . '-' . time() . '-';
		if ( class_exists( '\BWFAN_Common' ) && method_exists( '\BWFAN_Common', 'create_token' ) ) {
			$file_name .= \BWFAN_Common::create_token( 5 );
		} else {
			$file_name .= wp_generate_password( 5, false );
		}
		$file_name .= '.csv';
		$file_path = self::$export_folder . '/' . $file_name;

		$headers = [
			__( 'Name', 'wp-marketing-automations-pro' ),
			__( 'Email', 'wp-marketing-automations-pro' ),
			__( 'Phone', 'wp-marketing-automations-pro' ),
			__( 'Coupon', 'wp-marketing-automations-pro' ),
			__( 'Created On', 'wp-marketing-automations-pro' ),
			__( 'Items', 'wp-marketing-automatons-pro' ),
			__( 'Currency', 'wp-marketing-automations-pro' ),
			__( 'Total', 'wp-marketing-automations-pro' ),
			__( 'Status', 'wp-marketing-automations-pro' )
		];

		if ( $this->type === 'lost_carts' ) {
			$headers = array_diff( $headers, [ 'Status' ] );
		}

		if ( 'recovered_carts' === $this->type ) {
			$headers = [
				__( 'Name', 'wp-marketing-automations-pro' ),
				__( 'Email', 'wp-marketing-automations-pro' ),
				__( 'Phone', 'wp-marketing-automations-pro' ),
				__( 'Coupons', 'wp-marketing-automations-pro' ),
				__( 'Recovered On', 'wp-marketing-automations-pro' ),
				__( 'Items', 'wp-marketing-automations-pro' ),
				__( 'Order', 'wp-marketing-automations-pro' ),
				__( 'Currency', 'wp-marketing-automations-pro' ),
				__( 'Total', 'wp-marketing-automations-pro' ),
			];
		}

		$file_content = implode( ',', $headers ) . "\n";
		$file         = $wp_filesystem->put_contents( $file_path, $file_content, FS_CHMOD_FILE );

		if ( ! $file ) {
			return '';
		}

		return $file_name;
	}

	/**
	 * Get file name
	 *
	 * @return string
	 */
	public function get_file_name() {
		$name = str_replace( '_', '-', $this->type ) . '-export';

		return $this->create_file_with_headers( $name );
	}

	/**
	 * Handle cart export
	 *
	 * @param int $user_id
	 * @param int $export_id
	 *
	 * @return void
	 */
	public function handle_export( $user_id = 0, $export_id = 0 ) {
		$db_export_row = \BWFAN_Model_Import_Export::get( $export_id );

		if ( empty( $db_export_row ) || ( isset( $db_export_row['status'] ) && 1 !== intval( $db_export_row['status'] ) ) ) {
			$this->end_user_export( $user_id, $export_id, '', self::$EXPORTER_FAILED, __( 'Export data not found', 'wp-marketing-automations-pro' ) );

			return;
		}
		$export_meta = ! empty( $db_export_row['meta'] ) ? json_decode( $db_export_row['meta'], true ) : [];

		/** Check if file is missing in meta or doesn't exist */
		if ( empty( $export_meta['file'] ) || ! file_exists( self::$export_folder . '/' . $export_meta['file'] ) ) {
			$name                = $this->status === 2 ? 'lost-cart-export' : 'recoverable-cart-export';
			$file_name           = $this->create_file_with_headers( $name );
			$export_meta['file'] = $file_name;

			\BWFAN_Model_Import_Export::update( [ 'meta' => wp_json_encode( $export_meta ) ], [ 'id' => intval( $export_id ) ] );
		}
		$file_name       = $export_meta['file'];
		$file_path       = self::$export_folder . '/' . $file_name;
		$current_pos     = intval( $db_export_row['offset'] );
		$processed_count = intval( $db_export_row['processed'] );
		$total_count     = $this->get_total_count( $export_meta );
		$search          = ! empty( $export_meta['search'] ) ? $export_meta['search'] : '';

		$batch_limit = 10;
		$start_time  = time();
		while ( ( ( time() - $start_time ) < 30 ) && ! \BWFCRM_Common::memory_exceeded() ) {
			/** Fetch carts */
			if ( 'recovered_carts' === $this->type ) {
				$carts = \BWFAN_Recoverable_Carts::get_recovered_carts( $search, $current_pos, $batch_limit, false, true );
				$carts = $carts['items'] ?? [];
			} else {
				$carts = $this->get_carts( $this->status, $current_pos, $batch_limit, $search );
			}
			if ( empty( $carts ) ) {
				break;
			}

			$batch_count = count( $carts );

			$this->export_to_csv( $user_id, $export_id, $file_path, $carts );
			$processed_count += $batch_count;
			$current_pos     += $batch_count;
		}
		$this->update_export_offset( $export_id, $current_pos, $processed_count, $total_count );

		/** If current position/processed count is greater than equal to total count then end the export */
		if ( $current_pos >= intval( $total_count ) ) {
			$this->end_user_export( $user_id, $export_id, $file_name, self::$EXPORTER_SUCCESS, __( 'Export completed successfully', 'wp-marketing-automations-pro' ) );
		}
	}

	public function get_total_count( $data = [] ) {
		$search = ! empty( $data['search'] ) ? $data['search'] : '';
		if ( 'recovered_carts' === $this->type ) {
			return \BWFAN_Recoverable_Carts::get_recovered_carts( $search, '', '', true )['total_count'];
		}

		return \BWFAN_Recoverable_Carts::get_abandoned_carts( 'email', $search, '', '', $this->status, true )['total_count'];
	}

	/**
	 * Export data to CSV
	 *
	 * @param $user_id
	 * @param $file_path
	 * @param $carts
	 */
	private function export_to_csv( $user_id, $export_id, $file_path, $carts ) {
		global $wp_filesystem;
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}
		WP_Filesystem();

		$file_content = '';
		foreach ( $carts as $cart ) {
			if ( 'recovered_carts' === $this->type ) {
				$row_data = $this->get_recovered_data( $cart );
			} else {
				$name         = $this->get_the_name( $cart );
				$email        = $cart['email'] ?? '';
				$phone        = $this->get_phone( $cart );
				$coupon_names = $this->print_coupons( $cart );
				$created_time = get_date_from_gmt( $cart['created_time'], 'Y-m-d H:i:s' );
				$currency     = $cart['currency'];
				$total        = $cart['total'];
				$items        = $this->get_product_name_and_id( $cart );

				$row_data = [ $name, $email, $phone, '"' . $coupon_names . '"', $created_time, '"' . $items . '"', $currency, $total ];
				if ( 'lost_carts' !== $this->type ) {
					$status     = $this->get_status_description( $cart );
					$row_data[] = $status;
				}
			}

			if ( empty( $row_data ) ) {
				continue;
			}

			$file_content .= implode( ',', $row_data ) . "\n";
		}

		$file = $wp_filesystem->put_contents( $file_path, $wp_filesystem->get_contents( $file_path ) . $file_content, FS_CHMOD_FILE );
		if ( ! $file ) {
			$this->end_user_export( $user_id, $export_id, self::$EXPORTER_FAILED, '', __( 'Error exporting carts to CSV', 'wp-marketing-automations-pro' ) );
		}
	}

	/**
	 * Get recovered data
	 *
	 * @param $cart
	 *
	 * @return array
	 */
	public function get_recovered_data( $cart ) {
		if ( ! ( $cart instanceof \WC_Order ) ) {
			return [];
		}
		$order_data = $cart->get_data();
		$email      = $this->sanitize_field( $order_data['billing']['email'] ?? '' );

		$phone = $this->sanitize_field( $order_data['billing']['phone'] ?? '' );
		if ( ! empty( $phone ) && ! empty( $order_data['billing']['country'] ) ) {
			$phone = \BWFAN_Phone_Numbers::add_country_code( $phone, $order_data['billing']['country'] );
		}

		$name         = $this->sanitize_field( $order_data['billing']['first_name'] ?? '' ) . ' ' . $this->sanitize_field( $order_data['billing']['last_name'] ?? '' );
		$order_id     = '#' . $order_data['id'] . ' ' . $name;
		$currency     = $order_data['currency'];
		$coupon_names = $cart->get_coupon_codes() ? implode( "\n", $cart->get_coupon_codes() ) : '-';
		$total        = $order_data['total'];
		$created_time = $order_data['date_created'] ? $order_data['date_created']->date( 'Y-m-d H:i:s' ) : 'N/A';

		$items = [];
		foreach ( $order_data['line_items'] as $item ) {
			$items[] = wp_strip_all_tags( $item->get_name() ) . ' (#' . $item->get_id() . ')' . ' x ' . $item->get_quantity();
		}
		$items_string = implode( "\n", $items );

		return [
			$name,
			$email,
			$phone,
			'"' . $coupon_names . '"',
			$created_time,
			'"' . $items_string . '"',
			$order_id,
			$currency,
			$total,

		];
	}

	private function print_coupons( $cart ) {
		if ( empty( $cart['coupons'] ) ) {
			return '-';
		}

		$coupons_serialized = $cart['coupons'];
		$coupons_array      = maybe_unserialize( $coupons_serialized );

		if ( ! is_array( $coupons_array ) ) {
			return '-';
		}

		$coupon_names = array_keys( $coupons_array );

		return ! empty( $coupon_names ) ? implode( "\n", $coupon_names ) : '-';
	}

	private function sanitize_field( $field ) {
		return is_null( $field ) ? 'N/A' : ( is_array( $field ) || is_object( $field ) ? wp_json_encode( $field ) : sanitize_text_field( $field ) );
	}

	public function get_phone( $item ) {
		if ( ! isset( $item['checkout_data'] ) ) {
			return '';
		}
		$checkout_data = json_decode( $item['checkout_data'], true );
		if ( ! is_array( $checkout_data ) || ! isset( $checkout_data['fields'] ) || ! is_array( $checkout_data['fields'] ) ) {
			return '-';
		}

		$phone = ( isset( $checkout_data['fields']['billing_phone'] ) && ! empty( $checkout_data['fields']['billing_phone'] ) ) ? $checkout_data['fields']['billing_phone'] : '';
		if ( empty( $phone ) ) {
			return '-';
		}
		$country = ( isset( $checkout_data['fields']['billing_country'] ) && ! empty( $checkout_data['fields']['billing_country'] ) ) ? $checkout_data['fields']['billing_country'] : '';
		if ( ! empty( $country ) ) {
			$phone = \BWFAN_Phone_Numbers::add_country_code( $phone, $country );
		}

		return $phone;
	}

	public function get_product_name_and_id( $cart ) {
		if ( empty( $cart['items'] ) ) {
			return '';
		}
		$cart_items = $cart['items'];
		if ( is_string( $cart_items ) ) {
			$cart_items = maybe_unserialize( $cart_items );
		}

		if ( empty( $cart_items ) ) {
			return '-';
		}

		$products_details = [];
		foreach ( $cart_items as $item ) {
			$product_id         = $item['product_id'];
			$product_data       = $item['data'];
			$product_name       = wp_strip_all_tags( $product_data->get_name() );
			$quantity           = $item['quantity'];
			$products_details[] = "$product_name (#$product_id) - x $quantity";
		}

		return ! empty( $products_details ) ? implode( "\n", $products_details ) : '-';
	}

	public function get_the_name( $cart ) {
		$checkout = $cart['checkout_data'];

		if ( is_string( $checkout ) ) {
			$checkout = json_decode( $checkout, true );
		}

		if ( isset( $checkout['fields']['billing_first_name'] ) && isset( $checkout['fields']['billing_last_name'] ) ) {
			$first_name = $checkout['fields']['billing_first_name'];
			$last_name  = $checkout['fields']['billing_last_name'];

			return trim( $first_name . ' ' . $last_name );
		}

		return '-';
	}

	/**
	 * Map of status codes to descriptions
	 *
	 * @param $cart
	 *
	 * @return mixed|string|null
	 */
	function get_status_description( $cart ) {
		// Map of status codes to descriptions
		$status_descriptions = array(
			1 => __( 'In-Progress', 'wp-marketing-automations-pro' ),
			3 => __( 'Skipped: No Active Automation', 'wp-marketing-automations-pro' ),
			4 => __( 'Re-Scheduled', 'wp-marketing-automations-pro' ),
			5 => __( 'Under Cool Off Period', 'wp-marketing-automations-pro' ),
		);

		$status = isset( $cart['status'] ) && ! is_null( $cart['status'] ) ? $cart['status'] : '';

		return isset( $status_descriptions[ $status ] ) ? $status_descriptions[ $status ] : '-';
	}

	/**
	 * @param $status
	 * @param $offset
	 * @param $limit
	 * @param $search
	 *
	 * @return array|object|null
	 */
	public function get_carts( $status = '', $offset = 0, $limit = 10, $search = '' ) {
		global $wpdb;
		if ( empty( $status ) ) {
			$status = [ 0, 1, 3, 4, 5 ];
		} else if ( ! is_array( $status ) ) {
			$status = [ $status ];
		}
		$query = "SELECT * FROM {$wpdb->prefix}bwfan_abandonedcarts WHERE 1=1";
		if ( ! empty( $status ) ) {
			$placeholders = array_fill( 0, count( $status ), '%d' );
			$query        .= $wpdb->prepare( " AND status in ( " . implode( ',', $placeholders ) . " )", $status );
		}

		if ( ! empty( $search ) ) {
			$query .= $wpdb->prepare( " AND email LIKE %s ", '%' . $search . '%' );
		}

		$query .= $wpdb->prepare( " ORDER BY `created_time` DESC LIMIT %d, %d", $offset, $limit );

		return $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL
	}

}

if ( version_compare( BWFAN_VERSION, '3.5.3', '>' ) ) {
	BWFAN_Core()->exporter->register_exporter( 'recoverable_carts', 'BWFAN\Exporter\Carts' );
	BWFAN_Core()->exporter->register_exporter( 'lost_carts', 'BWFAN\Exporter\Carts' );
	BWFAN_Core()->exporter->register_exporter( 'recovered_carts', 'BWFAN\Exporter\Carts' );
}

