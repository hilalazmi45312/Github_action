<?php 
class WCMCA_ImportPage
{
	public function __construct()
	{
		
	}
	public function render_page()
	{
		global $wcmca_option_model, $wcmca_address_model;
		
		$address_fields_name = $wcmca_address_model->get_address_field_names();
		$address_fields_instruction = array('country' => wcmca_html_escape_allowing_special_tags(__('You must use country codes. Example: IT, FR, DE', 'woocommerce-multiple-customer-addresses'),false)
											);
		//Assets
		$js_data =  array(
							 'csv_file_format_error' =>  esc_html__('Invalid file format. Please select a valid CSV file.','woocommerce-multiple-customer-addresses'),
							 'file_selection_error' =>  esc_html__('Select a file first!','woocommerce-multiple-customer-addresses'),
							 'upload_complete_message' =>  esc_html__('100% done!','woocommerce-multiple-customer-addresses'),
							 'not_compliant_browser_error' =>  esc_html__('Please use a fully HTML5 compliant browser. The one you are using does not allow file reading.','woocommerce-multiple-customer-addresses'),
							 'security' => wp_create_nonce('wcmca_import_page_security_token')
							);
							
		wp_enqueue_style( 'wcmca-backend-settings-page', WCMCA_PLUGIN_PATH.'/css/backend-common.css');
		wp_enqueue_style( 'wcmca-backend-import-page', WCMCA_PLUGIN_PATH.'/css/backend-import-page.css');
		
		wp_enqueue_script('wcmca-backend-paperparse', WCMCA_PLUGIN_PATH.'/js/vendor/paperparse/papaparse.js', array('jquery'));
		wp_register_script('wcmca-admin-import-page', WCMCA_PLUGIN_PATH.'/js/admin-import-page.js', array('jquery'));
		wp_register_script('wcmca-admin-import-page-ui', WCMCA_PLUGIN_PATH.'/js/admin-import-page-ui.js', array('jquery'));
		wp_localize_script('wcmca-admin-import-page', 'wcmca', $js_data);
		wp_localize_script('wcmca-admin-import-page-ui', 'wcmca', $js_data);
		wp_enqueue_script('wcmca-admin-import-page' );
		wp_enqueue_script('wcmca-admin-import-page-ui' );
		
		?>
		<div class="wrap white-box">
			<div id="wcmca_inner_container">
					<div id="wcmca_instruction">
						<h2 class="wcmca_section_title wcmca_no_margin_top"><?php  esc_html_e('CSV data import', 'woocommerce-multiple-customer-addresses');?></h3>
						<div id="instruction">
							<h3 class="wcmca_no_margin_top"><?php  esc_html_e('Instruction','woocommerce-multiple-customer-addresses');?></h3>
							<p id="instruction_description"><?php  esc_html_e('Here the list of columns that can be imported:','woocommerce-multiple-customer-addresses');?></p>
							<ul id="field_list">
								<li>user_email <span class="normal">(<?php  wcmca_html_escape_allowing_special_tags(__('Example: john@gmail.com. It is a <strong>required</strong> column and it contains the account email the customer used to register his account', 'woocommerce-multiple-customer-addresses')); ?>)</span></li>
								<li>type <span class="normal">(<?php  wcmca_html_escape_allowing_special_tags(__('Values: <strong>billing</strong> or <strong>shipping</strong>. It is a <strong>required</strong> column and it contains the type of the address', 'woocommerce-multiple-customer-addresses')); ?>)</span></li>
							<?php foreach($address_fields_name as $field_name):?>
								<li><?php echo $field_name; ?> <?php if(isset($address_fields_instruction[$field_name])):?><span class="normal">(<?php echo $address_fields_instruction[$field_name]; ?>)</span><?php endif; ?></li>
							<?php endforeach; ?>
								<li>is_default_address <span class="normal">(<?php  wcmca_html_escape_allowing_special_tags(__('Values: <strong>yes</strong> or <strong>no</strong>. It set the address as the default address', 'woocommerce-multiple-customer-addresses')); ?>)</span></li>
								<li>address_internal_name <span class="normal">(<?php  wcmca_html_escape_allowing_special_tags(__('Example: <strong>Home</strong>, <strong>Office</strong>, ect. Identifier for the address', 'woocommerce-multiple-customer-addresses')); ?>)</span></li>
								<li>delete_previous_address_data <span class="normal">(<?php  wcmca_html_escape_allowing_special_tags(__('Values: <strong>yes</strong> or <strong>no</strong>. It is an <strong>optional</strong> column. If set to yes, it will delete <strong>all addresses associated with the customer</strong>', 'woocommerce-multiple-customer-addresses')); ?>)</span></li>
							</ul>
						</div>	
						
						<div class="wcmca_option_selector_container">
							<label><?php  esc_html_e('Select a file', 'woocommerce-multiple-customer-addresses');?></label>
							<input type="file" name="csv_file" id="csv_file_input" accept=".csv"></input>
						</div>				
						<p class="submit">
							<button class="button-primary" id="wcmca_import_button"><?php esc_attr_e('Import', 'woocommerce-multiple-customer-addresses'); ?></button>
						</p>
					</div>
					<div id="wcmca_loader_container">
						<div id="wcmca_progress_bar_container">
							<div id="wcmca_progress_bar_background"><div id="wcmca_progress_bar"></div></div>
							<div id="wcmca_notice_box"></div>				
						</div>		
						
						<p class="submit">
							<button class="button-primary" id="wcmca_import_another_button"><?php esc_attr_e('Import another', 'woocommerce-multiple-customer-addresses'); ?></button>
						</p>
					</div>
				</div>	
		</div>
		<?php
	}
}
?>