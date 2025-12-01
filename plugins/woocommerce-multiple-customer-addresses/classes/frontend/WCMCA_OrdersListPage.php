<?php 
class WCMCA_OrdersListPage
{
	public function __construct()
	{
		 add_filter( 'woocommerce_account_orders_columns', array($this, 'add_custom_columns' ));
		 add_filter( 'woocommerce_my_account_my_orders_column_wcmca-bills-to', array($this, 'manage_bills_to_column' ));
		 add_filter( 'woocommerce_my_account_my_orders_column_wcmca-ships-to', array($this, 'manage_ships_to_column' ));
	}
	function add_custom_columns($columns)
	{
		global $wcmca_option_model;
		$options = $wcmca_option_model->get_orders_list_options();
		
		$new_columns = array();
		foreach ( $columns as $key => $name ) 
		{

            $new_columns[ $key ] = $name;

           if ( 'order-status' === $key ) 
		   {
			   if($options['display_billing_address_column'])
					$new_columns['wcmca-bills-to'] = esc_html__( 'Bills to', 'woocommerce-multiple-customer-addresses' );
			   if($options['display_shipping_address_column'])
					$new_columns['wcmca-ships-to'] = esc_html__( 'Ships to', 'woocommerce-multiple-customer-addresses' );
            }
        }

        return $new_columns;
	}
	 function manage_bills_to_column( $order ) 
	 {

       echo $order->get_formatted_billing_address();
        
    }
	 function manage_ships_to_column( $order ) 
	 {

        echo $order->get_formatted_shipping_address();
        
    }
}
?>