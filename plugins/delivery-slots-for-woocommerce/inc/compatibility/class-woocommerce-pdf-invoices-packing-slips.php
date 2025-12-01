<?php
/**
 * WooCommerce pdf invoices packing slips Compatibility.
 *
 * @since 3.7.0
 * */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'DEY_WooCommerce_PDF_Invoices_Packing_Slips_Compatibility' ) ) {

	/**
	 * Class.
	 *
	 * @since 3.7.0
	 * */
	class DEY_WooCommerce_PDF_Invoices_Packing_Slips_Compatibility extends DEY_Compatibility {

		/**
		 * Class constructor.
		 *
		 * @since 3.7.0
		 */
		public function __construct() {
			$this->id = 'woocommerce_pdf_invoices_packing_slips';

			parent::__construct();
		}

		/**
		 * Is plugin enabled?.
		 *
		 * @since 3.7.0
		 * @return bool
		 * */
		public function is_plugin_enabled() {
			return class_exists( 'WPO_WCPDF' );
		}

		/**
		 * Actions.
		 *
		 * @since 3.7.0
		 */
		public function actions() {
			// Display order delivery details.
			add_action( 'wpo_wcpdf_after_order_details', array( __CLASS__, 'display_order_delivery_details' ), 10, 2 );
		}

		/**
		 * Display the order delivery details.
		 *
		 * @since 3.7.0
		 * @param string $type Type.
		 * @param object $order Order object.
		 * @return void
		 */
		public static function display_order_delivery_details( $type, $order ) {
			// Return if the object is not order object.
			if ( ! is_object( $order ) ) {
				return;
			}

			// Return if the delivery details not exists in order.
			$delivery_details = dey_get_order_delivery_details( $order );
			if ( ! dey_check_is_array( $delivery_details ) ) {
				return;
			}

			dey_get_template(
				'compatibility/woocommerce-pdf-invoices-packing-slips/order-delivery-details.php',
				array(
					'delivery_details'     => $delivery_details,
					'order_scheduler_type' => $order->get_meta( 'dey_order_local_pickup_id' ) ? 'order_local_pickup' : 'order_delivery',
				)
			);
		}
	}

}
