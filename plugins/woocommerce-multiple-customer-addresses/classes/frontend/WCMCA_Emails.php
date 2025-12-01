<?php 
class WCMCA_Emails
{
	public function __construct()
	{
		//Emails
		add_action('woocommerce_email_customer_details', array( &$this, 'woocommerce_include_extra_fields_in_emails' ), 11, 3);
		
		//add_action('init', array( &$this, 'init' ));
		add_action('wp_loaded', array( &$this, 'init' ));
	}
	
	function init()
	{
		global $wcmca_email_model, $wcmca_option_model;
		
		if(!$wcmca_option_model->send_notification_to_shipping_email())
			return;
		
		$statuses = $wcmca_email_model->get_email_ids();
		foreach($statuses as $status_id)
		{
			//wcmca_var_dump('woocommerce_email_recipient_'.$status_id);
			add_action('woocommerce_email_recipient_'.$status_id, array( &$this, 'woocommerce_change_email_recipient' ), 10, 2); 
		}
	}
	function woocommerce_change_email_recipient( $this_recipient, $order) 
	{ 
		global $wcmca_option_model;
		/* if(!is_a($order, 'WC_Order'))
			return $this_recipient; */
		$reflect = is_object($order) ? new ReflectionClass($order) : "none";
		if(is_a($order, 'WC_Order') || ($reflect != "none" && $reflect->getShortName() != 'Order'))
		{
			$order_id = $order->get_id();
			$shipping_email = get_post_meta($order_id, '_shipping_email', true);
		}
		//In case of new Account email, the $order object is "WP_User" object. The shipping email then is retrieved from $_POST (this however can be always be done, first "if" could be removed)
		else if(wcmca_get_value_if_set($_POST, 'shipping_email', false) != false)
		{
			$shipping_email = $_POST['shipping_email'];
		}
		else 
			return $this_recipient;
		
		if($shipping_email)
		{
			return $wcmca_option_model->send_notification_copty_to_billing_email() ? $this_recipient.",".$shipping_email: $shipping_email;
		}

		return $this_recipient; 
	} 
	public function woocommerce_include_extra_fields_in_emails( $order, $sent_to_admin = false, $plain_text = false)
	{
		global $wcmca_option_model, $wcmca_order_model, $wcev_order_model;
		$billing_vat_number = $wcmca_order_model->get_vat_meta_field(WCMCA_Order::get_id($order));
		if(isset($wcev_order_model) || !$wcmca_option_model->is_vat_identification_number_enabled())
			return;
		?>
		<ul>
		 <li><strong><?php _e( 'VAT Identification Number', 'woocommerce-multiple-customer-addresses' ); ?>:</strong> 
			 <span class="text"><?php echo $billing_vat_number; ?></span></li>
		</ul>
		<?php 
	}
}
?>