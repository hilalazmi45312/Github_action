<?php 
class WCMCA_OrderDetailsPage
{
	public function __construct()
	{
		add_action('woocommerce_order_details_after_customer_details', array(&$this, 'show_custom_fields')); //Oder datails page & Emails
		
		//Order details
		//add_filter( 'woocommerce_order_item_quantity_html', array( &$this, 'process_order_table_item_name' ),99, 4 ); //Order Details <--
		//add_filter( 'woocommerce_get_item_data', array( &$this, 'process_order_table_item_name' ),99, 2 ); //Order Details
		
		//Email
		//add_filter( 'woocommerce_email_order_item_quantity', array( &$this, 'process_order_table_item_name' ),10, 2 ); //Email <---
		//add_action( 'woocommerce_order_item_meta_start', array( &$this, 'process_email_order_table_item_name' ),10, 4 ); //Email
		
		//Both
		//add_filter( 'woocommerce_order_items_meta_display', array( &$this, 'process_order_item_meta_display' ), 99, 2 ); // < 2.7	
		//add_action( 'woocommerce_order_item_name', array( &$this, 'process_order_table_item_name' ),10, 3 ); // > 2.7
		
		//Shippings
		add_action( 'woocommerce_order_item_meta_end', array( &$this, 'process_order_table_item_name' ),10, 3 );  //Order datails & mails
		add_filter( 'woocommerce_get_order_item_totals', array( $this, 'add_shipping_rows_in_order_details' ), 10, 2 ); 
	}
	public function add_shipping_rows_in_order_details( $total_rows, $order ) 
	 {
			$shipping_items = $order->get_items( 'shipping' );
		    if ( ! $shipping_items || count( $shipping_items ) <= 1 ) {
			    return $total_rows;
		    }

		    foreach ( $shipping_items as $shipping_item_id => $shipping_item ) {
			    if ( ! $shipping_item->get_meta( 'wcmca_shipping_destination' ) ) {
				    break;
			    }
			    $is_local_pickup = 'local_pickup' == $shipping_item->get_method_id();
			    $address = wcmca_shipping_address_from_destination_array( $shipping_item->get_meta( 'wcmca_shipping_destination' ) );
			    $label = $is_local_pickup ? esc_html__( 'Local pickup', 'woocommerce-multiple-customer-addresses' ) : sprintf( esc_html__( 'Ship to: %s', 'woocommerce-multiple-customer-addresses' ), $address );

			    $value = $shipping_item->get_method_title();
			    $value .= $shipping_item->get_total() != 0 ? ' - ' . wc_price( $shipping_item->get_total() ) : '';
			    if ( $value && $shipping_item->get_meta( 'Items' ) )
				    $value .= '<br><small>' . $shipping_item->get_meta( 'Items' ) . '</small>';
			    
			    $shipping = array( 'shipping_' . $shipping_item_id => array(
				    'label' => $label,
				    'value' => $value
			    ) );
			    wcmca_array_insert( $total_rows, 'shipping', $shipping );
		    }

		    $total_rows['shipping']['label'] = esc_html__( 'Shipping total:', 'woocommerce-multiple-customer-addresses' );

		    return $total_rows;
		
	}
	public function process_order_item_meta_display($original_text, $item )
	{
		if(version_compare( WC_VERSION, '2.7', '>' ))
			return $original_text;
		
		global $wcmca_order_model, $wcmca_option_model;	
		$formatted_item_shipping_address =  $wcmca_option_model->shipping_per_product() ? $wcmca_order_model->get_formatted_item_shipping_address($item) : "";
		
		//echo "<br/>".str_replace(array("\n","\r"), "<br/>", $original_text).$formatted_item_shipping_address ;
		return $original_text.$formatted_item_shipping_address ;
	}
	//public function process_order_table_item_name($original_text, $item, $visible = false)
	public function process_order_table_item_name( $item_id, $item, $order)
	{
		/* if(version_compare( WC_VERSION, '2.7', '<' ))
			return ; */
		global $wcmca_order_model, $wcmca_option_model;		
		
		$options = $wcmca_option_model->shipping_per_product_related_options();
		
		$formatted_item_shipping_address =  $wcmca_option_model->shipping_per_product() ? $wcmca_order_model->get_formatted_item_shipping_address($item, true, $options['multiple_addresses_shipping']) : "";
		
		//return $original_text." ".$formatted_item_shipping_address ;
		echo $formatted_item_shipping_address ;
	}
	function show_custom_fields($order)
	{
		global $wcmca_option_model, $wcmca_order_model;
		$billing_vat_number = $wcmca_order_model->get_vat_meta_field(WCMCA_Order::get_id($order));
		if(isset($wcev_order_model) || !$wcmca_option_model->is_vat_identification_number_enabled())
			return;
		?>
		<tr>
			<th><?php _e( 'VAT Identification Number:', 'woocommerce-multiple-customer-addresses' ); ?></th>
			<td><?php echo $billing_vat_number; ?></td>
		</tr>
		<?php 
	}
}
?>