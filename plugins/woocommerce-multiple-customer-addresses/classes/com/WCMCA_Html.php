<?php 
class WCMCA_Html
{
	var $allowed_field_type = array('text','datepicker', 'number', 'multiselect', 'select','checkbox','radio', 'phone', 'tel', 'email','state', 'country', 'xcf_multiradio');
	public function __construct()
	{ 
		add_action('admin_menu', array(&$this,'init_admin_pages'));
		add_action('wp_ajax_wcmca_get_addresses_html_popup_by_user_id', array(&$this, 'ajax_get_addresses_html_popup_by_user_id'));
		add_action('wp_ajax_wcmca_reload_address_selectors_data', array(&$this, 'ajax_reload_address_selectors_data'));
	}
	function init_admin_pages()
	{
		//Parent slug is null, in this way the page is not showed in admin menu
		add_submenu_page("", 'Edit addresses', 'WooCommerce Multiple Customer Adresses', 'manage_woocommerce', 'woocommerce-multiple-customer-addresses-edit-user', array(&$this, 'render_admin_user_addresses_edit_page'));
	}
	function curPageURL() 
	{
		 $pageURL = 'http';
		 if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
		 $pageURL .= "://";
		 if ($_SERVER["SERVER_PORT"] != "80") {
		  $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		 } else {
		  $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		 }
		 return $pageURL;
	}
	public function common_js()
	{
		
	}
	public function render_custom_css($page)
	{
		global $wcmca_option_model;
		$css = $wcmca_option_model->get_custom_css_rules();
		if(!isset($css[$page]))
			return;
		?>
		<style type="text/css">
		<?php echo $css[$page]; ?>
		</style>
		<?php 
	}
	//add admin user edit addresses page link button
	public function add_multiple_address_link_to_user_admin_profile_page($user)
	{
		if(!current_user_can('manage_woocommerce'))
			return;
		?>
		<h2><?php esc_html_e('Additional addresses','woocommerce-multiple-customer-addresses'); ?></h2>
		<table class="form-table">
			<tbody>
				<tr>
					<th><?php esc_html_e('Addresses list','woocommerce-multiple-customer-addresses'); ?></th>
					<td>
						<a class="button button-primary wcmca_primary" target="_blank" href="<?php echo get_admin_url(); ?>admin.php?page=woocommerce-multiple-customer-addresses-edit-user&user_id=<?php echo $user->ID; ?>"><?php esc_html_e('View & Edit','woocommerce-multiple-customer-addresses'); ?></a>
					</td>
				</tr>
			</tbody>			
		</table>
		<?php 
	}
	// ------------------ ORDER PAGE ---------------------- //
	//Admin order edit page -> container rendering
	public function render_admin_order_page_additional_addresses_loading_tools()
	{
		global  $wcmca_option_model;
		$which_addresses_to_hide = $wcmca_option_model->which_addresses_type_are_disabled();
		$this->addresses_list_common_scripts();
		wp_dequeue_script('wcmca-additional-addresses');
		wp_dequeue_script('wcmca-additional-addresses-ui');
		
		
		wp_enqueue_script('wcmca-admin-order-edit-ui', WCMCA_PLUGIN_PATH.'/js/admin-order-edit-ui.js?'.time(), array('jquery'));
		wp_register_script('wcmca-admin-order-edit', WCMCA_PLUGIN_PATH.'/js/admin-order-edit.js?'.time(), array('jquery'));
		wp_enqueue_script('jquery-ui-tooltip');
		
		wp_enqueue_style('wcmca-backend-edit-user-addresses', WCMCA_PLUGIN_PATH.'/css/backend-edit-user-addresses.css');
		wp_enqueue_style('wcmca-backend-edit-order-addresses', WCMCA_PLUGIN_PATH.'/css/backend-order-edit.css');
		
		 $js_settings = array(
				'reload_page_alert_message' => esc_html__( 'This action will reload the page. Unsaved changes will be lost, proceed?', 'woocommerce-multiple-customer-addresses' ),
				'load_additional_addresses_text_button' =>  esc_html__( 'Click to load addresses list', 'woocommerce-multiple-customer-addresses' ),
				'loader_html' => '<img class="wcmca_preloader_image" src="'.WCMCA_PLUGIN_PATH.'/img/loader.gif" ></img>',
				'hide_billing_addresses_selection' =>   $which_addresses_to_hide['billing'] ? "true" : "false",
				'hide_shipping_addresses_selection' =>  $which_addresses_to_hide['shipping'] ? "true" : "false"
			);
		wp_localize_script( 'wcmca-admin-order-edit', 'wcmca_options', $js_settings );
		wp_enqueue_script('wcmca-admin-order-edit');
		?>
		<div id="wcmca_additional_addresses_container" class="mfp-hide"></div>
		<?php 
	}
	//Admin order page -> ajax call to retrieve data  
	function ajax_get_addresses_html_popup_by_user_id()
	{
		$user_id = isset($_POST['user_id']) && is_numeric($_POST['user_id']) && $_POST['user_id'] > 0 ? $_POST['user_id'] : $user_id;
		$type = isset($_POST['type']) ? $_POST['type'] : null;
		
		?>
		<a href= "#"  id="wcmca_close_button" class="mfp-close">X</a>
		<?php 
			if(isset($user_id) && isset($type))
				$this->render_addresses_list($user_id, $type, false, true);
			else 
				echo "<h3>".esc_html__('Please select a regisered user.','woocommerce-multiple-customer-addresses')."</h3>";
		 wp_die();
	}
	// ------------------ END ORDER PAGE ---------------------- //
	
	
	//Admin user edit addresses page 
	public function render_admin_user_addresses_edit_page($user_id = null)
	{
		global $wp_roles;
		$this->addresses_list_common_scripts();
		wp_enqueue_style('wcmca-backend-edit-user-addresses', WCMCA_PLUGIN_PATH.'/css/backend-edit-user-addresses.css');		
		
		$user_id = isset($_GET['user_id']) && is_numeric($_GET['user_id']) && $_GET['user_id'] > 0 ? $_GET['user_id'] : $user_id;
		
		?>
			<div class="wrap white-box">
			<?php if(isset($user_id)) : 
				$user = new WP_User( $user_id);
				$customer_data = get_userdata( $user_id);
				$customer_extra_data = get_user_meta($user_id);
				?>
				<div id="wcmca_user_details">	
				<label class="wcmca-label"><?php  esc_html_e('First Name', 'woocommerce-multiple-customer-addresses'); ?></label><br /><?php if(isset($customer_extra_data['first_name'])) echo $customer_extra_data['first_name'][0]; ?> <br /><br />
				<label class="wcmca-label"><?php  esc_html_e('Last Name', 'woocommerce-multiple-customer-addresses'); ?></label><br /><?php if(isset($customer_extra_data['last_name'])) echo $customer_extra_data['last_name'][0];?> <br/><br />
				<label class="wcmca-label"><?php  esc_html_e('Email Address', 'woocommerce-multiple-customer-addresses'); ?></label><br /><?php echo $customer_data->user_email; ?> <br/><br />
				
				<label class="wcmca-label"><?php  esc_html_e('Billing First Name', 'woocommerce-multiple-customer-addresses'); ?></label><br /><?php if(isset($customer_extra_data['billing_first_name'])) echo $customer_extra_data['billing_first_name'][0]; ?> <br /><br />
				<label class="wcmca-label"><?php  esc_html_e('Billing Last Name', 'woocommerce-multiple-customer-addresses'); ?></label><br /><?php if(isset($customer_extra_data['billing_last_name'])) echo $customer_extra_data['billing_last_name'][0];?> <br/><br />
				<label class="wcmca-label"><?php  esc_html_e('Biling Email Address', 'woocommerce-multiple-customer-addresses'); ?></label><br /><?php if(isset($customer_extra_data['billing_email'])) echo $customer_extra_data['billing_email'][0]; ?> <br/><br />
				
				<label class="wcmca-label"><?php  esc_html_e('Registration Date', 'woocommerce-multiple-customer-addresses'); ?> </label><br /><?php echo $customer_data->user_registered; ?> <br/><br />
				<label class="wcmca-label"><?php  esc_html_e('Roles', 'woocommerce-multiple-customer-addresses'); ?> </label><br /> 
							<?php 
							if ( !empty( $user->roles ) && is_array( $user->roles ) ) {
								foreach ( $user->roles as $role_code )
									echo  $wp_roles->roles[$role_code]["name"]." (". esc_html__('Role code:', 'woocommerce-multiple-customer-addresses')." <i>".$role_code."</i>)<br/>";
							} ?> <br/>
				<label class="wcmca-label"><?php esc_html_e('More details', 'woocommerce-multiple-customer-addresses'); ?></label><br/>		
				<a class="button" href="<?php echo get_edit_user_link($user_id); ?>" target="_blank" ><?php esc_html_e('User page', 'woocommerce-multiple-customer-addresses'); ?></a>
				<a class="button" href="<?php echo get_admin_url(); ?>edit.php?s&post_status=all&post_type=shop_order&action=-1&_customer_user=<?php echo $user_id ?>&filter_action=Filter" target="_blank"><?php esc_html_e('Orders list', 'woocommerce-multiple-customer-addresses'); ?></a>
				</div>
				<?php $this->render_addresses_list($user_id, null, false); 
				endif; ?>
			</div>
		<?php 
	}
	function addresses_list_common_scripts($is_checkout = 'no')
	{
		wp_enqueue_style('wcmca-magnific-popup', WCMCA_PLUGIN_PATH.'/css/vendor/magnific-popup.css'); 
		wp_enqueue_style('wcmca-additional-addresses',WCMCA_PLUGIN_PATH.'/css/frontend-my-account-addresses-list.css');
		wp_enqueue_style('wcmca-frontend-common',WCMCA_PLUGIN_PATH.'/css/frontend-common.css');
			
		if(!is_admin())		
			wp_enqueue_script('wcmca-custom-select2',WCMCA_PLUGIN_PATH.'/js/select2-manager.js', array('jquery', 'select2')); 
		wp_register_script('wcmca-additional-addresses-ui',WCMCA_PLUGIN_PATH.'/js/frontend-address-form-ui.js?'.time(), array('jquery'));  
		wp_register_script('wcmca-additional-addresses',WCMCA_PLUGIN_PATH.'/js/frontend-address-form.js?'.time(), array('jquery')); 
		$additional_js_options = array(
			'is_checkout_page' => $is_checkout,
			'user_id' => get_current_user_id(),
			'ajax_url' => admin_url('admin-ajax.php'),
			'current_url' =>  $this->curPageURL(),
			'confirm_delete_message' => esc_attr__('Selected addresses will be deleted, Are you sure?','woocommerce-multiple-customer-addresses'),
			'confirm_duplicate_message' => esc_attr__('Address will be duplicated, are you sure?','woocommerce-multiple-customer-addresses'),
			'confirm_delete_all_message' => esc_attr__('All the %s addresses will be deleted, Are you sure?','woocommerce-multiple-customer-addresses'),
			'security_token' => wp_create_nonce('wcmca_security_token')
		);
		$address_form_ui_js_options = array( 
			'state_string' => esc_attr__('State','woocommerce-multiple-customer-addresses'),
			'postcode_string' => esc_attr__('Postcode / ZIP','woocommerce-multiple-customer-addresses'),
			'city_string' => esc_attr__('City','woocommerce-multiple-customer-addresses')
		);
		
		wp_localize_script( 'wcmca-additional-addresses', 'wcmca_address_form', $additional_js_options );
		wp_localize_script( 'wcmca-additional-addresses-ui', 'wcmca_address_form_ui', $address_form_ui_js_options );
		wp_enqueue_script( 'wcmca-additional-addresses' );
		wp_enqueue_script( 'wcmca-additional-addresses-ui' );
		wp_enqueue_script('wcmca-magnific-popup', WCMCA_PLUGIN_PATH.'/js/vendor/jquery.magnific-popup.js', array('jquery'));
	}
	//Woocommerce My account page (used also for admin order and user profile pages)
	public function render_addresses_list($user_id = null, $type_to_show_in_order_edit_page = null, $include_scripts = true, $is_order_edit = false)
	{
		global $wcmca_address_model, $wcmca_customer_model, $wcmca_option_model;
		$is_vat_identification_number_enabled = $wcmca_option_model->is_vat_identification_number_enabled();
		$default_addresses_style = $wcmca_option_model->get_style_options();
		$which_addresses_to_hide = $wcmca_option_model->which_addresses_type_are_disabled();
		$addresses_by_type = $wcmca_customer_model->get_addresses_by_type(!isset($user_id) ? get_current_user_id() : $user_id);
		
		$field_managment_options = $wcmca_option_model->get_addresseses_managment_settings();
		$user_can_add_new_billing_addresses = $field_managment_options['max_number_of_billing_addresses'] == 0 ||  count($addresses_by_type['billing']) < $field_managment_options['max_number_of_billing_addresses'];
		$user_can_add_new_shipping_addresses = $field_managment_options['max_number_of_shipping_addresses'] == 0 ||  count($addresses_by_type['shipping']) < $field_managment_options['max_number_of_shipping_addresses'];
			
		$no_addresses_available = empty($addresses_by_type['billing']) && 	empty($addresses_by_type['shipping']);
		
		$has_edit_capabilities = array(
						'billing' 	=> (current_user_can('manage_woocommerce') && is_admin()) || (!$which_addresses_to_hide['billing'] && !isset($type_to_show_in_order_edit_page) && !$field_managment_options['disable_user_billing_addresses_editing_capabilities']  ),
						'shipping' =>  (current_user_can('manage_woocommerce') && is_admin()) || (!$which_addresses_to_hide['shipping'] && !isset($type_to_show_in_order_edit_page) && !$field_managment_options['disable_user_shipping_addresses_editing_capabilities'] )
		);
		
		if($include_scripts)
		{
			$this->addresses_list_common_scripts();	
		}
		
		if(is_admin() && $no_addresses_available):
		?>
			<h2><?php esc_html_e('This user has no saved addresses!','woocommerce-multiple-customer-addresses'); ?></h2>
		<?php 
		endif;
		
		?>
		<div id="wcmca_custom_addresses">
			<div class="u-columns woocommerce-Addresses col2-set addresses">
			<?php if($has_edit_capabilities['billing']): ?>
				<div class="u-column1 col-1 woocommerce-Address">
					<?php if($user_can_add_new_billing_addresses): 
							if(!$is_order_edit): ?>
							<a href="#wcmca_address_form_container_billing" class="button wcmca_add_new_address_button" id="wcmca_add_new_address_button_billing"><?php esc_html_e('Add new billing address','woocommerce-multiple-customer-addresses'); ?></a>
							<?php endif; ?>
							<div class="wcmca_loader_container">
								<img class="wcmca_saving_loader_image" src="<?php echo WCMCA_PLUGIN_PATH.'/img/loader.gif' ?>" ></img>
							</div>
					<?php else: 
						echo "<i>".esc_html__('Billing addresses limit reached! To add a new one, delete one of the existings!','woocommerce-multiple-customer-addresses')."</i>";
					endif; ?>
				</div>
			<?php endif;
				 if($has_edit_capabilities['shipping']): ?>
				<div class="u-column2 col-2 woocommerce-Address">
					<?php if($user_can_add_new_shipping_addresses): ?>
						<?php if(!$is_order_edit): ?>
						<a href="#wcmca_address_form_container_shipping" class="button wcmca_add_new_address_button" id="wcmca_add_new_address_button_shipping"><?php esc_html_e('Add new shipping address','woocommerce-multiple-customer-addresses'); ?></a>
						<?php endif; ?>
						<div class="wcmca_loader_container">
							<img class="wcmca_saving_loader_image" src="<?php echo WCMCA_PLUGIN_PATH.'/img/loader.gif' ?>" ></img>
						</div>
					<?php else:
						echo "<i>".esc_html__('Shipping addresses limit reached! To add a new one, delete one of the existings!','woocommerce-multiple-customer-addresses')."</i>";
					endif; ?>
				</div>
			<?php endif; ?>
			</div>
			<?php 
				
				$col_counter = 0;
				foreach($addresses_by_type as $type => $addresses) 
				  //Enable the "is_admin()" if you wish to force the display in the admin area
				  if(!empty($addresses) && (!$which_addresses_to_hide[$type] /* || is_admin() */) && (!isset($type_to_show_in_order_edit_page) || $type_to_show_in_order_edit_page == $type))
				  { 
					if(file_exists ( get_theme_file_path()."/woocommerce-multiple-customer-addresses/frontend/my-account.php" ))
						include get_theme_file_path()."/woocommerce-multiple-customer-addresses/frontend/my-account.php";
					else
						include WCMCA_PLUGIN_ABS_PATH.'/templates/my-account.php';
				  }			
				?>
			
			<?php $this->common_js(); ?>
			
			<form id="wcmca_address_form_container_billing" class="mfp-hide">
				<?php $this->render_address_form('billing', $user_id ); ?>
			</form>
			<form id="wcmca_address_form_container_shipping" class="mfp-hide">
				<?php $this->render_address_form('shipping', $user_id ); ?>
			</form>
		</div>
		<?php
	}
	
	//New/Edit address popup HTML
	public function render_address_form($type = 'billing', $user_id=null)
	{
		global $wcmca_address_model, $wcmca_option_model;
		$is_vat_identification_number_enabled = $wcmca_option_model->is_vat_identification_number_enabled();
		$is_identifier_field_disabled = $wcmca_option_model->is_identifier_field_disabled();
		$countries = $wcmca_address_model->get_countries($type);	
		//WCBCF (Brazialian extra fields) support
		$wcbcf_settings = get_option( 'wcbcf_settings' );
		$wcmca_is_wcbcf_active = wcmca_is_wcbcf_active();
		$required_fields = $wcmca_option_model->get_required_fields();
		$fields_options = $wcmca_option_model->get_addresseses_managment_settings();
		//No need: $user_id = isset($user_id) ? $user_id : get_current_user_id();
		?>
		
		<div id="wcmca_form_popup_container_<?php echo $type; ?>">
			<a href= "#"  id="wcmca_close_address_form_button_<?php echo $type; ?>" class="mfp-close">X</a>
			<div class="woocommerce">
				<div  id="wcmca_address_form_<?php echo $type; ?>">
					<div id="wcmca_address_form_fieldset_<?php echo $type; ?>">
					<!-- Error messages -->
					<div class="wcmca_error" id="wcmca_required_field_error"><?php esc_html_e('Please make sure to have filled all the required fields.','woocommerce-multiple-customer-addresses'); ?></div>
					<div class="wcmca_error" id="wcmca_email_field_error"><?php esc_html_e('The entered email has not a valid format.','woocommerce-multiple-customer-addresses'); ?></div>
					<div class="wcmca_error" id="wcmca_postcode_field_error"><?php esc_html_e('The entered postcode has not a valid format.','woocommerce-multiple-customer-addresses'); ?></div>
					<div class="wcmca_error" id="wcmca_phone_field_error"><?php esc_html_e('The entered phone  has not a valid format.','woocommerce-multiple-customer-addresses'); ?></div>
					<!-- End error messages -->
					<input id="wcmca_address_id_<?php echo $type; ?>" name="wcmca_address_id" type="hidden" value="-1"></input>
					<?php if(isset($user_id)): ?>
						<input type="hidden" name="wcmca_user_id" id="wcmca_user_id" value="<?php echo $user_id;?>"></input>
					<?php endif; ?>
						<?php 
						$address_fields = $wcmca_address_model->get_woocommerce_address_fields_by_type($type);
						
						//Field name
						if(!$is_identifier_field_disabled && get_current_user_id() > 0) //in case of product shipping address for guest
							woocommerce_form_field('wcmca_'.$type.'_address_internal_name', array(
									'type'       => 'text',
									'id' 		 => 'wcmca_'.$type.'_address_internal_name',
									'class'      => array( 'form-row-wide' ),
									'required'   => true,
									'input_class' => array('not_empty' ,'wcmca_input_field'),
									'label'      => esc_html__('Identifier / Name (Examples: "Office address," "Mary Jones," "MJ 2145," etc.)','woocommerce-multiple-customer-addresses'),
									'label_class' => array( 'wcmca_form_label' ),
									'custom_attributes'    => array('required' => 'required'),
									)
									);
								
						//Is default checkbox
						$default_address_label = $type == "shipping" ? esc_html__('Make this address the default shipping address','woocommerce-multiple-customer-addresses'): esc_html__('Make this address the default billing address','woocommerce-multiple-customer-addresses');
						if(get_current_user_id() > 0) //in case of product shipping address for guest
							woocommerce_form_field('wcmca_'.$type.'_is_default_address', array(
									'type'       => 'checkbox',
									'class'      => array( 'form-row-wide' ),
									'required'   => false,
									'label'      => $default_address_label,
									'label_class' => array( 'wcmca_default_checkobx_label' )
									)
									);
								
						$was_prev_field_first_row = false;
						foreach($address_fields as $field_name => $address_field)
						{
							do_action("wcmca_".$field_name."_popup_field_rendering");
							if($field_name == 'billing_state' || $field_name == 'shipping_state' 
								|| (isset($address_field['type']) && !in_array($address_field['type'],$this->allowed_field_type)) || 
								(isset($address_field['enabled']) && !$address_field['enabled']))
								{
									continue;
								}
							else if($field_name == 'billing_country' || $field_name == 'shipping_country')
							{
								$was_prev_field_first_row = $was_prev_field_last_row = false;
								?>
								<div class="wcmca_divider"></div>
								<?php 
								woocommerce_form_field('wcmca_'.$type.'_country', array(
									'type'				=> 'select',
									'class'				=> array( 'form-row-first' ),
									'input_class'		=> array('wcmca-country-select2', 'not_empty'),
									'required'			=> true,
									'label'				=> esc_html__('Select a country','woocommerce-multiple-customer-addresses'),
									'label_class'		=> array( 'wcmca_form_label' ),
									'default'			=> $fields_options['default_country'],
									//placeholder'		=> esc_html__('Select a country','woocommerce-multiple-customer-addresses'),
									'options'			=> $countries,
									'custom_attributes'	=> array('required' => 'required')
									)
								);
								?> 
									<div id="wcmca_country_field_container_<?php echo $type; ?>"></div>
									<img class="wcmca_preloader_image" src="<?php echo WCMCA_PLUGIN_PATH.'/img/loader.gif' ?>" ></img>
									<div class="wcmca_divider"></div>
								<?php 
							}
							else
							{
								$address_field_class = isset($address_field['class']) ? $address_field['class'] : "";
								$is_required = isset($address_field['required']) ? $address_field['required'] : false;
								$custom_attributes = isset($address_field['custom_attributes']) ? $address_field['custom_attributes'] : array();
								$class_to_assign = $address_field_class;
								
								//row class managment
								if(wcmca_array_element_contains_substring('form-row-first', $address_field_class) && $was_prev_field_first_row)
								{
									$class_to_assign = array( 'form-row-last' );
									$was_prev_field_last_row = true;
								}
								else
									$was_prev_field_last_row = wcmca_array_element_contains_substring('form-row-last', $address_field_class) ? true : false;
								
								if(wcmca_array_element_contains_substring('form-row-last', $address_field_class) && (!$was_prev_field_first_row /* || $was_prev_field_last_row */))
								{
									$class_to_assign = array( 'form-row-wide' );
									$was_prev_field_first_row = false;
								}
								else
									$was_prev_field_first_row = wcmca_array_element_contains_substring('form-row-first', $address_field_class) ? true : false;
								
								
								
								//requirement managment and class managment
								if($is_required)
									$custom_attributes['required'] = 'required';
								$input_class = isset($address_field['required']) && $address_field['required'] ? array('not_empty' ,'wcmca_input_field') : array('wcmca_input_field');
								$label_class = array( 'wcmca_form_label' );
								
								//field options managment
								$field_options = isset($address_field['options']) ? $address_field['options'] : array(); 
								
								//Support for Checkout Field Editor Pro Advanced
								if(isset($address_field['options_object']))
								{
									$field_options = array();
									foreach($address_field['options_object'] as $object_option)
										$field_options[$object_option['key']] = $object_option['text'];
								}
								//extra field type managment
								if(isset($address_field['type']) && $address_field['type'] == "multiselect")
								{	
									$address_field['type'] = 'select';
									$custom_attributes['multiple'] = 'multiple';
								}
								elseif(isset($address_field['type']) && ($address_field['type'] == "radio" || $address_field['type'] == "checkbox"))
								{
									$custom_attributes['data-default'] = isset($woocommerce_address_field['default'])  ? $woocommerce_address_field['default'] :  0;
									$label_class = array( 'wcmca_form_inline_input_label' );
									$input_class = isset($address_field['required']) && $address_field['required'] ? array('not_empty' ,'wcmca_inline_input_field') : array('wcmca_inline_input_field');
								}
								
								//Forcing/Unforcing required
								if( (($field_name == 'billing_first_name' || $field_name == 'billing_last_name') && $required_fields['billing_first_and_last_name_disable_required']) || 
								    (($field_name == 'shipping_first_name' || $field_name == 'shipping_last_name') && $required_fields['shipping_first_and_last_name_disable_required']) )
									{
										$is_required = false;
										$input_class =  array('wcmca_input_field');
										if(isset($custom_attributes['required']))
											unset($custom_attributes['required']);
									}
								if( ($field_name == 'billing_company'  && $required_fields['billing_company_name_enable_required']) || 
								    ($field_name == 'shipping_company'  && $required_fields['shipping_company_name_enable_required'])  )
									{
										$is_required = true;
										$input_class = array('not_empty' ,'wcmca_input_field');
										$custom_attributes['required'] = 'required';
									}
								 @woocommerce_form_field('wcmca_'.$field_name, array(
										'type'       => isset($address_field['type']) ? $address_field['type'] : 'text',
										'autocomplete' => isset($address_field['autocomplete']) ? $address_field['autocomplete'] : false,
										'class'      => $class_to_assign,//array( 'form-row-first' ),
										'required'   => $is_required,
										'input_class' => $input_class,
										'label'      => isset($address_field['label']) ? $address_field['label'] : "",										
										'description'    => isset($address_field['description']) ? $address_field['description'] : '',
										'label_class' => $label_class,
										'placeholder'    => isset($address_field['placeholder']) ? $address_field['placeholder'] : '',
										'maxlength'    => isset($address_field['maxlength']) ? $address_field['maxlength'] : false,
										'validate'    => isset($address_field['validate']) ? $address_field['validate'] : array(),
										'custom_attributes'    => $custom_attributes,
										'options'    => $field_options,
										),
										isset($address_field['type']) && $address_field['type'] == 'checkbox' && $address_field['default'] ? true : null /* $address_field['checked'] */
									);
							}
						}
						do_action('wcmca_after_render_address_form', $type); 
						?>
						
						<p class="wcmca_save_address_button_container">
							<button class="button" class="wcmca_save_address_button" id="wcmca_save_address_button_<?php echo $type; ?>"><?php esc_html_e('Save','woocommerce-multiple-customer-addresses'); ?></button>
							<img class="wcmca_preloader_image" id="wcmca_validation_loader_<?php echo $type; ?>" src="<?php echo WCMCA_PLUGIN_PATH.'/img/loader.gif' ?>" ></img>
						</p>
					</div>
				 </div>
			</div>
		</div>
		<!--<div id="wcmca_form_background_overlay" ></div>-->
		<?php		
	}
	//Checkout page
	function render_address_form_popup()
	{
		 $this->common_js();
		 if(get_current_user_id() == 0)
			$this->addresses_list_common_scripts('yes');
		 
		 ?>
		<div id="wcmca_custom_addresses" display="height:1px">
		</div>
		<div id="wcmca_address_form_container_billing" class="mfp-hide">
				<?php $this->render_address_form('billing'); ?>
		</div>
		<div id="wcmca_address_form_container_shipping" class="mfp-hide">
			<?php $this->render_address_form('shipping'); ?>
		</div>
		<?php
	}
	
	//Checkout page -> dropdown menus
	function render_address_select_menu($type = 'billing')
	{
		global $wcmca_customer_model, $wcmca_option_model;
		
		$checkout_disable_form = $wcmca_option_model->checkout_form_can_edit();
		wp_enqueue_style('wcmca-magnific-popup', WCMCA_PLUGIN_PATH.'/css/vendor/magnific-popup.css');
		wp_enqueue_style('wcmca-additional-addresses', WCMCA_PLUGIN_PATH.'/css/frontend-checkout.css');
		wp_enqueue_style('wcmca-frontend-common',WCMCA_PLUGIN_PATH.'/css/frontend-common.css');
		//alternative: wp_enqueue_style('selectWoo',WCMCA_PLUGIN_PATH.'/css/vendor/selectWoo/selectWoo.css');
		wp_enqueue_style('select2');
		
		wp_enqueue_script('wcmca-custom-select2',WCMCA_PLUGIN_PATH.'/js/select2-manager.js', array('jquery')); 
		wp_enqueue_script('wcmca-magnific-popup', WCMCA_PLUGIN_PATH.'/js/vendor/jquery.magnific-popup.js', array('jquery'));		
		
		wp_register_script('wcmca-additional-addresses-ui', WCMCA_PLUGIN_PATH.'/js/frontend-checkout-ui.js?'.time(), array('jquery'));
		wp_register_script('wcmca-additional-addresses', WCMCA_PLUGIN_PATH.'/js/frontend-checkout.js?'.time(), array('jquery'));
		wp_register_script('wcmca-address-form-ui',WCMCA_PLUGIN_PATH.'/js/frontend-address-form-ui.js?'.time(), array('jquery'));  
		wp_register_script('wcmca-address-form',WCMCA_PLUGIN_PATH.'/js/frontend-address-form.js?'.time(), array('jquery')); 
		$scurity_token = wp_create_nonce('wcmca_security_token');
		$additional_js_options = array(
			'is_checkout_page' => 'yes',
			'user_id' => get_current_user_id(),
			'ajax_url' => admin_url('admin-ajax.php'),
			'current_url' =>  $this->curPageURL(),
			'confirm_delete_message' => esc_attr__('Selected addresses will be deleted, Are you sure?','woocommerce-multiple-customer-addresses'), 
			'confirm_delete_all_message' => esc_attr__('All the %s addresses will be deleted, Are you sure?','woocommerce-multiple-customer-addresses'),
			'confirm_duplicate_message' => esc_attr__('Address will be duplicated, are you sure?','woocommerce-multiple-customer-addresses'),
			'disable_smooth_scroll' => $wcmca_option_model->disable_smooth_scroll() ? 'true' : 'false',
			'security_token' => $scurity_token
		);
		$additional_address_js_options = array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'disable_billing_form' => $checkout_disable_form['billing'], 
			'disable_shipping_form' => $checkout_disable_form['shipping'], 
			'security_token' => $scurity_token
		);
		$address_form_ui_js_options = array( 
			'state_string' => esc_attr__('State','woocommerce-multiple-customer-addresses'),
			'postcode_string' => esc_attr__('Postcode / ZIP','woocommerce-multiple-customer-addresses'),
			'city_string' => esc_attr__('City','woocommerce-multiple-customer-addresses')
		);
		
		wp_localize_script( 'wcmca-address-form', 'wcmca_address_form', $additional_js_options );
		wp_localize_script( 'wcmca-address-form-ui', 'wcmca_address_form', $additional_js_options );
		wp_localize_script( 'wcmca-additional-addresses', 'wcmca_additional_address', $additional_address_js_options );
		wp_localize_script( 'wcmca-additional-addresses-ui', 'wcmca_address_form_ui', $address_form_ui_js_options );
		wp_enqueue_script( 'wcmca-address-form' );
		wp_enqueue_script( 'wcmca-address-form-ui' );
		wp_enqueue_script( 'wcmca-additional-addresses' );
		wp_enqueue_script( 'wcmca-additional-addresses-ui' );
		//alternative: wp_enqueue_script( 'selectWoo' );
		wp_enqueue_script( 'select2' );
		
		$addresses = $wcmca_customer_model->get_addresses(get_current_user_id());
		$addresses_by_type = $wcmca_customer_model->get_addresses_by_type(get_current_user_id());
		$which_addresses_to_hide = $wcmca_option_model->which_addresses_type_are_disabled();
		
		$field_managment_options = $wcmca_option_model->get_addresseses_managment_settings();
		$user_can_add_new_addresses = !$field_managment_options['disable_user_'.$type.'_addresses_editing_capabilities'] && ($field_managment_options['max_number_of_'.$type.'_addresses'] == 0 ||  count($addresses_by_type[$type]) <= $field_managment_options['max_number_of_'.$type.'_addresses']);
	
		if($which_addresses_to_hide[$type])
			return;
		?>
		
		<p class="form-row form-row wcmca_address_selector_container">
			<label><?php esc_html_e('Select an address','woocommerce-multiple-customer-addresses'); ?></label>
			<select class="wcmca_address_select_menu" data-type="<?php echo $type; ?>" id="wcmca_address_select_menu_<?php echo $type; ?>" name="wcmca_<?php echo $type; ?>_selected_address_id">
				<?php $this->create_options_for_main_address_selector($type, $addresses); ?>
			</select>
			<!-- #wcmca_custom_addresses -->
			<?php if($user_can_add_new_addresses): ?>
			<a href="#wcmca_address_form_container_<?php echo $type; ?>" id="wcmca_add_new_address_button_<?php echo $type; ?>" data-associated-selector="wcmca_address_select_menu_<?php echo $type; ?>" class ="button wcmca_add_new_address_button"><?php esc_html_e('Add new address','woocommerce-multiple-customer-addresses'); ?></a>
			<?php endif; ?>
		</p>
		<p>
			<img class="wcmca_loader_image" id="wcmca_loader_image_<?php echo $type; ?>" src="<?php echo WCMCA_PLUGIN_PATH.'/img/loader.gif' ?>" ></img>
		</p>
		<?php 
	}
	public function render_add_product_address_for_guest_user($item_cart_id, $cart_item)
	{
		global  $wcmca_option_model;
		
		//In case of virtual product, shipping adddress won't be available
		$wc_product = $cart_item['data'];
		if($wc_product->get_virtual() || $wc_product->get_downloadable())
			return;
		
		$shipping_per_product_related_options = $wcmca_option_model->shipping_per_product_related_options();
		$default_address_message = esc_html__( 'Item will be shipped to the billing address (or to the shipping address if you selected to ship to a different address from the billing).', 'woocommerce-multiple-customer-addresses' );
		$collect_from_store_message = esc_html__( 'Pick-up in store.', 'woocommerce-multiple-customer-addresses' );
		$type = $shipping_per_product_related_options['product_address_type_for_guests']; 
		
		$this->addresses_list_common_scripts('yes');
		wp_register_script('wcmca-product-address-guest', WCMCA_PLUGIN_PATH.'/js/frontend-checkout-product-address-guest.js', array('jquery'));
		wp_register_script('wcmca-product-fee', WCMCA_PLUGIN_PATH.'/js/frontend-checkout-fee.js', array('jquery'));
		$js_options = array(
			'product_address_loading' => esc_html__( 'Loading...', 'woocommerce-multiple-customer-addresses' ),
			'default_address_message' => $default_address_message,
			'collect_from_store_message' => $collect_from_store_message,
			'security' =>  wp_create_nonce('wcfa_guest_addresses_management'),
			'ajax_url' => admin_url('admin-ajax.php')
		);
		$js_options_fee = array(
			'ajax_url' => admin_url('admin-ajax.php')
		);
		wp_localize_script( 'wcmca-product-address-guest', 'wcmca_guest', $js_options );
		wp_localize_script( 'wcmca-product-fee', 'wcmca_fee', $js_options_fee );
		wp_enqueue_script( 'wcmca-product-address-guest' );
		wp_enqueue_script('wcmca-product-fee');
		
		wp_enqueue_style('wcmca-product-address', WCMCA_PLUGIN_PATH.'/css/frontend-checkout-product-address.css');
		wp_enqueue_style('wcmca-additional-product-addresses', WCMCA_PLUGIN_PATH.'/css/frontend-checkout.css');
		
		?>
		<div id="wcmca_actions_container_<?php echo $item_cart_id; ?>">
			<a href="#wcmca_address_form_container_<?php echo $type; ?>" class="button wcmca_add_new_product_address_guest_button wcmca_add_new_address_button" data-cart-item-id="<?php echo $item_cart_id; ?>" ><?php esc_html_e('Set address','woocommerce-multiple-customer-addresses'); ?></a>
			<a href="#" class="button wcmca_remove_address_button" id="wcmca_remove_address_button_<?php echo $item_cart_id; ?>"  data-cart-item-id="<?php echo $item_cart_id; ?>" ><?php esc_html_e('Remove address','woocommerce-multiple-customer-addresses'); ?></a>
		</div>
		<?php if($shipping_per_product_related_options['display_pick_up_option']): ?>
			<div class="wcmca_collect_from_store_container">
				<input type="checkbox" data-cart-item-id="<?php echo $item_cart_id; ?>" id="wcmca_collect_from_store_checkbox_<?php echo $item_cart_id; ?>" class="wcmca_collect_from_store">&nbsp;<?php _e( 'Pick-up in store', 'woocommerce-multiple-customer-addresses' ); ?></input>
			</div>
		<?php endif; ?>
		<input type="hidden" name="wcmca_product_address_for_guest_user[<?php echo $item_cart_id; ?>]" id="product_address_for_guest_<?php echo $item_cart_id; ?>" value="same_as_billing"></input>
		
		<img class="wcmca_product_address_loader" id="wcmca_product_address_loader_<?php echo $item_cart_id; ?>" src="<?php echo WCMCA_PLUGIN_PATH.'/img/horizontal-15.gif' ?>"></img>
		<!-- Address preview container -->
		<div class="wcmca_product_address"  data-unique-id="<?php echo $item_cart_id; ?>" id="wcmca_product_address_<?php echo $item_cart_id; ?>"><?php echo $default_address_message;?></div>
		<?php if($shipping_per_product_related_options['display_notes_field']): 
					$is_note_field_required = $shipping_per_product_related_options['is_notes_field_required'] ? 'required="required"' : "";
				?>
					<label class="wcmca_product_field_label <?php if($shipping_per_product_related_options['is_notes_field_required']) echo ' wcmca_product_required_field_label '; ?>"><?php echo apply_filters('wcmca_product_note_label', esc_html__('Note','woocommerce-multiple-customer-addresses')); ?></label>
					<textarea class="wcmca_product_field_note" id="wcmca_product_fields_note_<?php echo $item_cart_id; ?>" name="wcmca_product_fields[<?php echo $item_cart_id; ?>][notes]" <?php echo $is_note_field_required; ?>></textarea>
		<?php endif; 
	}
	public function render_address_select_menu_for_product($item_cart_id, $cart_item)
	{
		global  $wcmca_customer_model, $wcmca_option_model, $wcmca_wpml_helper;
		
		//In case of virtual product, shipping adddress won't be available
		$wc_product = $cart_item['data'];
		if($wc_product->get_virtual() || $wc_product->get_downloadable())
			return;
		
		$addresse_type_disabled = $wcmca_option_model->which_addresses_type_are_disabled();
		$shipping_per_product_related_options = $wcmca_option_model->shipping_per_product_related_options();
		if($addresse_type_disabled['billing'] && $addresse_type_disabled['shipping'])
			return;

		//Product or category to exclude
		$product_id = $wc_product->get_type() == 'variation' ? $wc_product->get_parent_id() : $wc_product->get_id();
		$variation_id = $wc_product->get_type() == 'variation' ? $wc_product->get_id() : 0;
		$product_id = $wcmca_wpml_helper->get_original_id($product_id) ? $wcmca_wpml_helper->get_original_id($product_id) : $product_id;
		$variation_id = $wcmca_wpml_helper->get_original_id($variation_id) ? $wcmca_wpml_helper->get_original_id($variation_id) : $variation_id;
		$wc_parent_product =  $wc_product->get_type() == 'variation' ? wc_get_product($wc_product->get_parent_id()) : $wc_product;
		
		if(in_array($product_id ,$shipping_per_product_related_options['shipping_per_product_excluded_prod']) || in_array($variation_id ,$shipping_per_product_related_options['shipping_per_product_excluded_prod']))
			return;
		if(array_intersect($wc_product->get_category_ids(), $shipping_per_product_related_options['shipping_per_product_excluded_cat']) || array_intersect($wc_parent_product->get_category_ids(), $shipping_per_product_related_options['shipping_per_product_excluded_cat']))
			return;
		
		
		$type = 'shipping'; //no longer used
		$user_id = get_current_user_id();
		$addresses = $wcmca_customer_model->get_addresses_by_type($user_id);
		$addresses_by_type =  $wcmca_customer_model->get_addresses_by_type($user_id);
		$field_managment_options = $wcmca_option_model->get_addresseses_managment_settings();
		$user_can_add_new_billing_addresses = $field_managment_options['max_number_of_billing_addresses'] == 0 ||  count($addresses_by_type['billing']) <= $field_managment_options['max_number_of_billing_addresses'];
		$user_can_add_new_shipping_addresses = $field_managment_options['max_number_of_shipping_addresses'] == 0 ||  count($addresses_by_type['shipping']) <= $field_managment_options['max_number_of_shipping_addresses'];
		
		$pre_selected_address_id = "";
		$default_address_message = esc_html__( 'Item will be shipped to the billing address (or to the shipping address if you selected to ship to a different address from the billing).', 'woocommerce-multiple-customer-addresses' );
		$collect_from_store_message = esc_html__( 'Pick-up in store.', 'woocommerce-multiple-customer-addresses' );
		wp_register_script('wcmca-product-address', WCMCA_PLUGIN_PATH.'/js/frontend-checkout-product-address.js', array('jquery'));
		wp_register_script('wcmca-product-fee', WCMCA_PLUGIN_PATH.'/js/frontend-checkout-fee.js', array('jquery'));
		$js_options = array(
			'product_address_loading' => esc_html__( 'Loading...', 'woocommerce-multiple-customer-addresses' ),
			'default_address_message' => $default_address_message,
			'collect_from_store_message' => $collect_from_store_message,
			'ajax_url' => admin_url('admin-ajax.php')
		);
		$js_options_fee = array(
			'ajax_url' => admin_url('admin-ajax.php')
		);
		wp_localize_script( 'wcmca-product-address', 'wcmca', $js_options );
		wp_localize_script( 'wcmca-product-fee', 'wcmca_fee', $js_options );
		wp_enqueue_script( 'wcmca-product-address' );
		wp_enqueue_script( 'wcmca-product-fee' );
		
		
		wp_enqueue_style('wcmca-product-address', WCMCA_PLUGIN_PATH.'/css/frontend-checkout-product-address.css');
		
		?>
			<div class="wcmca_product_shipping_box">
				<span class="wcmca_product_shipping_title"><?php esc_html_e('Shipping address','woocommerce-multiple-customer-addresses'); ?></span>			
				<select class="wcmca_product_address_select_menu " data-type="<?php echo $type; ?>" data-unique-id="<?php echo $item_cart_id; ?>" id="wcmca_product_address_select_menu_<?php echo $type."_".$item_cart_id; ?>" name="wcmca_product_address[<?php echo $item_cart_id; ?>]">
					<?php echo $this->create_options_for_item_address_selector($type, $item_cart_id, $addresses, $addresse_type_disabled); ?>
				</select>
				
				<?php if($shipping_per_product_related_options['display_add_billing_address_button'] || $shipping_per_product_related_options['display_add_shipping_address_button'] ): ?>
				<div class="wcmca_add_new_address_buttons_container">
					<?php if($shipping_per_product_related_options['display_add_billing_address_button'] && !$field_managment_options['disable_user_billing_addresses_editing_capabilities'] && $user_can_add_new_billing_addresses): ?>
						<a href="#wcmca_address_form_container_billing" class="button wcmca_add_new_address_button" data-associated-selector="wcmca_product_address_select_menu_<?php echo $type."_".$item_cart_id; ?>" ><?php esc_html_e('Add new billing address','woocommerce-multiple-customer-addresses'); ?></a>
					<?php endif; 
					if($shipping_per_product_related_options['display_add_shipping_address_button'] && !$field_managment_options['disable_user_shipping_addresses_editing_capabilities'] && $user_can_add_new_shipping_addresses):?>
						<a href="#wcmca_address_form_container_shipping" class="button wcmca_add_new_address_button" data-associated-selector="wcmca_product_address_select_menu_<?php echo $type."_".$item_cart_id; ?>" ><?php esc_html_e('Add new shipping address','woocommerce-multiple-customer-addresses'); ?></a>
					<?php endif; ?>
				</div>
				<?php endif; ?>
				
				<img class="wcmca_product_address_loader" id="wcmca_product_address_loader_<?php echo $item_cart_id; ?>" src="<?php echo WCMCA_PLUGIN_PATH.'/img/horizontal-15.gif' ?>"></img>
				<!-- Address preview container -->
				<div class="wcmca_product_address"  id="wcmca_product_address_<?php echo $item_cart_id; ?>"><?php echo $default_address_message; ?></div>
				<?php if($shipping_per_product_related_options['display_notes_field']): 
					$is_note_field_required = $shipping_per_product_related_options['is_notes_field_required'] ? 'required="required"' : "";
				?>
					<label class="wcmca_product_field_label <?php if($shipping_per_product_related_options['is_notes_field_required']) echo ' wcmca_product_required_field_label '; ?>"><?php esc_html_e('Note','woocommerce-multiple-customer-addresses'); ?></label>
					<textarea class="wcmca_product_field_note" id="wcmca_product_fields_note_<?php echo $item_cart_id; ?>" name="wcmca_product_fields[<?php echo $item_cart_id; ?>][notes]" <?php echo $is_note_field_required; ?>></textarea>
				<?php endif; ?>
			</div>
		<?php 
	}
	//Used also to repopupate main billing/shipping addresses selector via AJAX
	public function create_options_for_main_address_selector($type, $addresses)
	{
		global $wcmca_option_model;
		$disable_last_used_address = $wcmca_option_model->disable_last_used_address();
	 
		if(empty($addresses)): ?>
					<option value="" selected disabled><?php esc_html_e('There are no additional addresses','woocommerce-multiple-customer-addresses'); ?></option>
			<?php else: 
					if($disable_last_used_address): ?>
						<option value="none"><?php esc_html_e('Select an address','woocommerce-multiple-customer-addresses'); ?></option>
					<?php else: ?>
						<?php if($type == 'shipping'): ?>
							<option value="last_used_<?php echo $type; ?>"><?php esc_html_e('Shipping address used for the previous order','woocommerce-multiple-customer-addresses'); ?></option>
						<?php else: ?>
							<option value="last_used_<?php echo $type; ?>"><?php esc_html_e('Billing address used for the previous order','woocommerce-multiple-customer-addresses'); ?></option>
						<?php endif; ?>
					<?php endif; ?>
			<?php endif;
				
				foreach( $addresses as $index => $address)
					if(isset($address['address_internal_name']) && $address['type'] == $type)
					{
						$is_dafault = isset($address[$type."_is_default_address"]) && $address[$type."_is_default_address"] ? " (".esc_html__('Default','woocommerce-multiple-customer-addresses').")" : "";
						$is_dafault_class = $is_dafault != "" ? " class='wcmca_default_droppdown_option' " : "";
						$selected = isset($address[$address['type']."_is_default_address"]) ? 'selected="selected"': "";
						echo '<option value="'.$address['address_id'].'" '.$selected.' '.$is_dafault_class .'>'.$address['address_internal_name'].$is_dafault.'</option>';
					}
			
	}
	//Used also to repopupate product addresses selector via AJAX
	public function create_options_for_item_address_selector($type, $item_cart_id, $addresses, $addresse_type_disabled)
	{
		global $wcmca_option_model;
		$options = $wcmca_option_model->shipping_per_product_related_options();
		
													   //checkout_data-||-same_as_billing ?>
		<option value="<?php echo $item_cart_id."-||-";?>checkout_data-||-last_used_shipping"><?php esc_html_e('Use the current shipping address','woocommerce-multiple-customer-addresses'); ?></option>
		<?php if($options['display_pick_up_option']): ?>
			<option value="<?php echo $item_cart_id."-||-";?>checkout_data-||-collect_from_store"><?php esc_html_e('Collect from store','woocommerce-multiple-customer-addresses'); ?></option>
		<?php endif;
				$pre_selected_address_id = "last_used_".$type ?>
				<?php if($type == 'shipping'):  ?>
					<!-- <option value="<?php echo $item_cart_id."-||-";?>last_used_<?php echo $type; ?>"><?php esc_html_e('Shipping address used for the previous order','woocommerce-multiple-customer-addresses'); ?></option> -->
				<?php else: ?>
					<!-- <option value="<?php echo $item_cart_id."-||-";?>last_used_<?php echo $type; ?>"><?php esc_html_e('Billing address used for the previous order','woocommerce-multiple-customer-addresses'); ?></option>-->
				<?php endif; ?>
		<?php 
			if(!empty($addresses['billing']) && !$addresse_type_disabled['billing'])
			{
				echo '<optgroup label="'.esc_html__('Billing addresses','woocommerce-multiple-customer-addresses').'">';
				foreach( $addresses['billing'] as $index => $address)
				{
					$address_id = $address['address_id'];
					if(isset($address['address_internal_name']) )
					{
						$is_dafault =  "";
						$is_dafault_class = $is_dafault != "" ? " class='wcmca_default_droppdown_option' " : "";
						$selected ="";
						echo '<option  value="'.$item_cart_id."-||-".$address_id.'-||-billing" '.$selected.' '.$is_dafault_class .'>'.$address['address_internal_name'].$is_dafault.'</option>';
					}
				}
				echo '</optgroup>';
			}
			
			if(!empty($addresses['shipping']) && !$addresse_type_disabled['shipping'])
			{
				echo '<optgroup label="'.esc_html__('Shipping addresses','woocommerce-multiple-customer-addresses').'">';
				foreach( $addresses['shipping'] as $index => $address)
				{
					$address_id = $address['address_id'];
					if(isset($address['address_internal_name']) )
					{
						$is_dafault = "";
						$is_dafault_class = $is_dafault != "" ? " class='wcmca_default_droppdown_option' " : "";
						$selected = "";
						echo '<option data-address-type="shipping" value="'.$item_cart_id."-||-".$address_id.'-||-shipping" '.$selected.' '.$is_dafault_class .'>'.$address['address_internal_name'].$is_dafault.'</option>';
					}
				}
				echo '</optgroup>';
			}
	}
	public function ajax_reload_address_selectors_data()
	{
		global $wcmca_customer_model, $wcmca_option_model, $woocommerce;
		
		$types = array('billing', 'shipping');
		$selectors_data = array('cart_items' => array());
		$user_id = wcmca_get_value_if_set($_POST, 'user_id', get_current_user_id());
		$addresse_type_disabled = $wcmca_option_model->which_addresses_type_are_disabled();
		
		if($user_id == 0)
		{
			echo "no";
			wp_die();
		}
		
		$addresses = $wcmca_customer_model->get_addresses_by_type($user_id);
		$main_addresses = $wcmca_customer_model->get_addresses(get_current_user_id());
		
		//main billing/shipping selectors
		foreach($types as $type)
		{
			ob_start();
			$this->create_options_for_main_address_selector($type,$main_addresses);
			$selectors_data[$type] = ob_get_contents(); 
			$selectors_data[$type] = str_replace(array("\t", "\n"), "", $selectors_data[$type]);
			ob_end_clean();
		}
		
		$items = WC()->cart->get_cart();	
		if(!$addresse_type_disabled['billing'] || !$addresse_type_disabled['shipping'])
			foreach($items as $item_key => $item)
			{
				ob_start();
				$this->create_options_for_item_address_selector('billing',  $item_key, $addresses, $addresse_type_disabled);
				$selectors_data['cart_items'][$item_key] = ob_get_contents(); 
				$selectors_data['cart_items'][$item_key] = str_replace(array("\t", "\n"), "", $selectors_data['cart_items'][$item_key]);
				ob_end_clean();
			}
		
		echo json_encode($selectors_data);
		wp_die();
	}
	public function render_product_address_preview($address_id, $user_id, $type = 'shipping') 
	{
		global $wcmca_customer_model, $wcmca_address_model;
		if($address_id == "")
			return;
		
		$address = $wcmca_customer_model->get_address_by_id($user_id, $address_id, $type);
		if(empty($address))
			return;
		$address_fields = isset($address[$type.'_country']) ? $wcmca_address_model->get_woocommerce_address_fields_by_type($type, $address[$type.'_country']) : array();
		
		if(file_exists ( get_theme_file_path()."/woocommerce-multiple-customer-addresses/frontend/checkout-product-address.php" ))
			include get_theme_file_path()."/woocommerce-multiple-customer-addresses/frontend/checkout-product-address.php";
		else
			include WCMCA_PLUGIN_ABS_PATH.'/templates/checkout-product-address.php';
	}
	public function render_product_address_preview_for_guest_user($address, $type = 'billing')
	{
		global $wcmca_address_model;
		$address_fields = isset($address[$type.'_country']) ? $wcmca_address_model->get_woocommerce_address_fields_by_type($type, $address[$type.'_country']) : array();
		
		if(file_exists ( get_theme_file_path()."/woocommerce-multiple-customer-addresses/frontend/checkout-product-address.php" ))
			include get_theme_file_path()."/woocommerce-multiple-customer-addresses/frontend/checkout-product-address.php";
		else
			include WCMCA_PLUGIN_ABS_PATH.'/templates/checkout-product-address.php';
	}
	public function get_formatted_order_item_shipping_address($address, $address_fields, $type = 'shipping', $is_html = true)
	{
		global $wcmca_address_model;
		$result = "";
		ob_start();
		
		if(file_exists ( get_theme_file_path()."/woocommerce-multiple-customer-addresses/frontend/order-table-item.php" ))
			include get_theme_file_path()."/woocommerce-multiple-customer-addresses/frontend/order-table-item.php";
		else
			include WCMCA_PLUGIN_ABS_PATH.'/templates/order-table-item.php';
		
		$result .= !$is_html ? strip_tags(ob_get_clean()) : ob_get_clean();
		$result = trim(preg_replace('/\s+/', ' ', $result));
		return $result;
	}
}
?>