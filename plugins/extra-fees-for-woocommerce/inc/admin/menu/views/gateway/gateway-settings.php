<?php
/* Admin HTML Fee Settings for Gateway */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="efw-gateway-fee-settings">
	<h3><?php echo esc_html( $gateway_title ); ?></h3>
	<p>
		<label><?php esc_html_e( 'Enable Fee for this Payment Gateway', 'extra-fees-for-woocommerce' ); ?></label>
		<input type="checkbox" class="efw-enable-gateway-fee" name="efw_enable_fee_for_<?php echo esc_attr( $gateway_id ); ?>" <?php checked( $enable_deal, 'yes', true ); ?>/>
	</p>
	<p class="efw-show-if-enable">
		<label><?php esc_html_e( 'Fee should apply for', 'extra-fees-for-woocommerce' ); ?></label>
		<select class="efw-user-filter-type" name="efw_user_filter_type_for_<?php echo esc_attr( $gateway_id ); ?>">
			<option value="1" <?php echo selected( $user_filter_type, '1', true ); ?>><?php esc_html_e( 'All User(s)', 'extra-fees-for-woocommerce' ); ?></option>
			<option value="2" <?php echo selected( $user_filter_type, '2', true ); ?>><?php esc_html_e( 'Include User(s)', 'extra-fees-for-woocommerce' ); ?></option>
			<option value="3" <?php echo selected( $user_filter_type, '3', true ); ?>><?php esc_html_e( 'Exclude User(s)', 'extra-fees-for-woocommerce' ); ?></option>
			<option value="4" <?php echo selected( $user_filter_type, '4', true ); ?>><?php esc_html_e( 'Include User Role(s)', 'extra-fees-for-woocommerce' ); ?></option>
			<option value="5" <?php echo selected( $user_filter_type, '5', true ); ?>><?php esc_html_e( 'Exclude User Role(s)', 'extra-fees-for-woocommerce' ); ?></option>
		</select>
	</p>
	<p class="efw-show-if-enable efw-include-user">
		<label><?php esc_html_e( 'Select User(s) to Include', 'extra-fees-for-woocommerce' ); ?></label>
		<?php
		$include_user_args = array(
			'name'                    => 'efw_include_user_for_' . $gateway_id,
			'list_type'               => 'customers',
			'exclude_global_variable' => 'yes',
			'action'                  => 'efw_customers_search',
			'placeholder'             => esc_html__( 'Search a User', 'extra-fees-for-woocommerce' ),
			'options'                 => $include_user,
		);
		efw_select2_html( $include_user_args );
		?>
	</p>
	<p class="efw-show-if-enable efw-exclude-user">
		<label><?php esc_html_e( 'Select User(s) to Exclude', 'extra-fees-for-woocommerce' ); ?></label>
		<?php
		$exclude_user_args = array(
			'name'                    => 'efw_exclude_user_for_' . $gateway_id,
			'list_type'               => 'customers',
			'exclude_global_variable' => 'yes',
			'action'                  => 'efw_customers_search',
			'placeholder'             => esc_html__( 'Search a User', 'extra-fees-for-woocommerce' ),
			'options'                 => $exclude_user,
		);
		efw_select2_html( $exclude_user_args );
		?>
	</p>
	<p class="efw-show-if-enable efw-include-user-role">
		<label><?php esc_html_e( 'Select User Role(s) to Include', 'extra-fees-for-woocommerce' ); ?></label>
		<select class="efw_select2" name="efw_include_userrole_for_<?php echo esc_attr( $gateway_id ); ?>[]" multiple="multiple">
			<?php
			foreach ( efw_get_user_roles() as $user_role_id => $user_role_name ) :
				$selected = ( in_array( $user_role_id, (array) $include_user_role ) ) ? ' selected="selected"' : '';
				?>
				<option value="<?php echo esc_attr( $user_role_id ); ?>"<?php echo esc_attr( $selected ); ?>><?php echo esc_html( $user_role_name ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
	<p class="efw-show-if-enable efw-exclude-user-role">
		<label><?php esc_html_e( 'Select User Role(s) to Exclude', 'extra-fees-for-woocommerce' ); ?></label>
		<select class="efw_select2" name="efw_exclude_userrole_for_<?php echo esc_attr( $gateway_id ); ?>[]" multiple="multiple">
			<?php
			foreach ( efw_get_user_roles() as $user_role_id => $user_role_name ) :
				$selected = ( in_array( $user_role_id, (array) $exclude_user_role ) ) ? ' selected="selected"' : '';
				?>
				<option value="<?php echo esc_attr( $user_role_id ); ?>"<?php echo esc_attr( $selected ); ?>><?php echo esc_html( $user_role_name ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
	<p class="efw-show-if-enable">
		<label><?php esc_html_e( 'Fee for Product(s)/Categories', 'extra-fees-for-woocommerce' ); ?></label>
		<select class="efw-product-filter-type" name="efw_product_filter_type_for_<?php echo esc_attr( $gateway_id ); ?>">
			<option value="1" <?php echo selected( $product_filter_type, '1', true ); ?>><?php esc_html_e( 'All Product(s)', 'extra-fees-for-woocommerce' ); ?></option>
			<option value="2" <?php echo selected( $product_filter_type, '2', true ); ?>><?php esc_html_e( 'Include Product(s)', 'extra-fees-for-woocommerce' ); ?></option>
			<option value="3" <?php echo selected( $product_filter_type, '3', true ); ?>><?php esc_html_e( 'Exclude Product(s)', 'extra-fees-for-woocommerce' ); ?></option>
			<option value="4" <?php echo selected( $product_filter_type, '4', true ); ?>><?php esc_html_e( 'Include Categories', 'extra-fees-for-woocommerce' ); ?></option>
			<option value="5" <?php echo selected( $product_filter_type, '5', true ); ?>><?php esc_html_e( 'Exclude Categories', 'extra-fees-for-woocommerce' ); ?></option>
		</select>
	</p>
	<p class="efw-show-if-enable efw-include-product">
		<label><?php esc_html_e( 'Select Product(s) to Include', 'extra-fees-for-woocommerce' ); ?></label>
		<?php
		$include_product_args = array(
			'name'                    => 'efw_include_product_for_' . $gateway_id,
			'list_type'               => 'products',
			'exclude_global_variable' => 'yes',
			'action'                  => 'efw_product_search',
			'placeholder'             => esc_html__( 'Search a Product', 'extra-fees-for-woocommerce' ),
			'options'                 => $include_product,
		);
		efw_select2_html( $include_product_args );
		?>
	</p>
	<p class="efw-show-if-enable efw-exclude-product">
		<label><?php esc_html_e( 'Select Product(s) to Exclude', 'extra-fees-for-woocommerce' ); ?></label>
		<?php
		$exclude_product_args = array(
			'name'                    => 'efw_exclude_product_for_' . $gateway_id,
			'list_type'               => 'products',
			'exclude_global_variable' => 'yes',
			'action'                  => 'efw_product_search',
			'placeholder'             => esc_html__( 'Search a Product', 'extra-fees-for-woocommerce' ),
			'options'                 => $exclude_product,
		);
		efw_select2_html( $exclude_product_args );
		?>
	</p>
	<p class="efw-show-if-enable efw-include-category">
		<label><?php esc_html_e( 'Select Categories to Include', 'extra-fees-for-woocommerce' ); ?></label>
		<select class="efw_select2" name="efw_include_category_for_<?php echo esc_attr( $gateway_id ); ?>[]" multiple="multiple">
			<?php
			foreach ( efw_get_wc_categories() as $category_id => $category_name ) :
				$selected = ( in_array( $category_id, (array) $include_category ) ) ? ' selected="selected"' : '';
				?>
				<option value="<?php echo esc_attr( $category_id ); ?>"<?php echo esc_attr( $selected ); ?>><?php echo esc_html( $category_name ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
	<p class="efw-show-if-enable efw-include-additional-product">
		<label><?php esc_html_e( 'Select Additional Product(s) to Include', 'extra-fees-for-woocommerce' ); ?></label>
		<?php
		$include_additional_product_args = array(
			'name'                    => 'efw_include_additional_product_for_' . $gateway_id,
			'list_type'               => 'products',
			'exclude_global_variable' => 'yes',
			'action'                  => 'efw_product_search',
			'placeholder'             => esc_html__( 'Search a Product', 'extra-fees-for-woocommerce' ),
			'options'                 => $include_additional_products,
		);
		efw_select2_html( $include_additional_product_args );
		?>
	</p>
	<p class="efw-show-if-enable efw-exclude-category">
		<label><?php esc_html_e( 'Select Categories to Exclude', 'extra-fees-for-woocommerce' ); ?></label>
		<select class="efw_select2" name="efw_exclude_category_for_<?php echo esc_attr( $gateway_id ); ?>[]" multiple="multiple">
			<?php
			foreach ( efw_get_wc_categories() as $category_id => $category_name ) :
				$selected = ( in_array( $category_id, (array) $exclude_category ) ) ? ' selected="selected"' : '';
				?>
				<option value="<?php echo esc_attr( $category_id ); ?>"<?php echo esc_attr( $selected ); ?>><?php echo esc_html( $category_name ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
	<p class="efw-show-if-enable efw-exclude-additional-product">
		<label><?php esc_html_e( 'Select Additional Product(s) to Exclude', 'extra-fees-for-woocommerce' ); ?></label>
		<?php
		$exclude_additional_product_args = array(
			'name'                    => 'efw_exclude_additional_product_for_' . $gateway_id,
			'list_type'               => 'products',
			'exclude_global_variable' => 'yes',
			'action'                  => 'efw_product_search',
			'placeholder'             => esc_html__( 'Search a Product', 'extra-fees-for-woocommerce' ),
			'options'                 => $exclude_additional_products,
		);
		efw_select2_html( $exclude_additional_product_args );
		?>
	</p>
	<p class="efw-show-if-enable">
		<label><?php esc_html_e( 'Restrict Fee based on', 'extra-fees-for-woocommerce' ); ?></label>
		<select class="efw-fee-based-on" name="efw_add_fee_based_on_<?php echo esc_attr( $gateway_id ); ?>">
			<option value="1" <?php echo selected( $fee_based_on, '1', true ); ?>><?php esc_html_e( 'Countries', 'extra-fees-for-woocommerce' ); ?></option>
			<option value="2" <?php echo selected( $fee_based_on, '2', true ); ?>><?php esc_html_e( 'State(s)', 'extra-fees-for-woocommerce' ); ?></option>
		</select>
	</p>
	<p class="efw-show-if-enable efw-include-country">
		<label><?php esc_html_e( 'Select Countries to Include', 'extra-fees-for-woocommerce' ); ?></label>
		<select class="efw_select2" name="efw_include_countries_for_<?php echo esc_attr( $gateway_id ); ?>[]" multiple="multiple">
			<?php
			foreach ( WC()->countries->get_allowed_countries() as $country_code => $country_name ) :
				$selected = ( in_array( $country_code, (array) $include_country ) ) ? ' selected="selected"' : '';
				?>
				<option value="<?php echo esc_attr( $country_code ); ?>"<?php echo esc_attr( $selected ); ?>><?php echo esc_html( $country_name ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
	<p class="efw-show-if-enable efw-include-states">
		<label><?php esc_html_e( 'Select State(s) to Include', 'extra-fees-for-woocommerce' ); ?></label>
		<select class="efw_select2"  name="efw_include_states_for_<?php echo esc_attr( $gateway_id ); ?>[]" multiple="multiple">
			<?php
			foreach ( efw_get_allowed_states() as $code => $name ) :
				$selected = ( in_array( $code, (array) $include_states ) ) ? ' selected="selected"' : '';
				?>
				<option value="<?php echo esc_attr( $code ); ?>"<?php echo esc_attr( $selected ); ?>><?php echo esc_html( $name ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
	<p class="efw-show-if-enable">
		<label><?php esc_html_e( 'Date Ranges', 'extra-fees-for-woocommerce' ); ?></label>
		<?php
		$rule_valid_from_date_args = array(
			'name'        => 'efw_from_date_for_' . $gateway_id,
			'value'       => $from_date,
			'wp_zone'     => false,
			'placeholder' => EFW_Date_Time::get_wp_date_format(),
		);
		efw_get_datepicker_html( $rule_valid_from_date_args );
		?>
		<?php esc_html_e( 'To', 'extra-fees-for-woocommerce' ); ?>
		<?php
		$rule_valid_to_date_args = array(
			'name'        => 'efw_to_date_for_' . $gateway_id,
			'value'       => $to_date,
			'wp_zone'     => false,
			'placeholder' => EFW_Date_Time::get_wp_date_format(),
		);
		efw_get_datepicker_html( $rule_valid_to_date_args );
		?>
	</p>
	<p class="efw-show-if-enable">
		<label><?php esc_html_e( 'Weekday(s)', 'extra-fees-for-woocommerce' ); ?></label>
		<select class="efw_select2" multiple="multiple" name="efw_weekdays_for_<?php echo esc_attr( $gateway_id ); ?>[]">
			<?php foreach ( efw_get_weekdays_options() as $specific_weekdays_id => $specific_weekdays_name ) : ?>
				<option value="<?php echo esc_attr( $specific_weekdays_id ) ; ?>" <?php echo in_array( $specific_weekdays_id , (array) $selected_week_days ) ? 'selected="selected"' : '' ; ?>><?php echo esc_html( $specific_weekdays_name ) ; ?></option>
			<?php endforeach ; ?>
		</select>
	</p>
	<p class="efw-show-if-enable">
		<label><?php esc_html_e( 'Fee Text', 'extra-fees-for-woocommerce' ); ?><span class="required">*</span></label>
		<input id="efw_fee_text_for_<?php echo esc_attr( $gateway_id ); ?>" class="efw_fee_text_for_<?php echo esc_attr( $gateway_id ); ?>" name="efw_fee_text_for_<?php echo esc_attr( $gateway_id ); ?>" value="<?php echo esc_html( $fee_text ); ?>"/>
	</p>
	<p class="efw-show-if-enable">
		<label><?php esc_html_e( 'Fee Text Description', 'extra-fees-for-woocommerce' ); ?></label>
				<textarea id="efw_fee_description_for_<?php echo esc_attr( $gateway_id ); ?>" class="efw_fee_description_for_<?php echo esc_attr( $gateway_id ); ?>" name="efw_fee_description_for_<?php echo esc_attr( $gateway_id ); ?>" style="margin-left:10px;"><?php echo wp_kses_post( $fee_description ); ?></textarea>
	</p>
	<p class="efw-show-if-enable">
		<label><?php esc_html_e( 'Tax Class', 'extra-fees-for-woocommerce' ); ?></label>
		<select name="efw_tax_class_for_<?php echo esc_attr( $gateway_id ); ?>">
			<?php foreach ( efw_get_fee_tax_classes() as $tax_class_id => $tax_class_name ) : ?>
					<option value="<?php echo esc_attr( $tax_class_id ); ?>" <?php echo selected( $tax_class, $tax_class_id, true ); ?>><?php echo esc_html( $tax_class_name ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php echo wc_help_tip( 'Select the tax which should be used for calculating the fee' ); ?>
	</p>
	<p class="efw-show-if-enable">
		<label><?php esc_html_e( 'Fee Type', 'extra-fees-for-woocommerce' ); ?></label>
		<select class="efw-fee-type" name="efw_fee_type_for_<?php echo esc_attr( $gateway_id ); ?>">
			<option value="1" <?php echo selected( $fee_type, '1', true ); ?>><?php esc_html_e( 'Fixed Fee', 'extra-fees-for-woocommerce' ); ?></option>
			<option value="2" <?php echo selected( $fee_type, '2', true ); ?>><?php esc_html_e( 'Percentage', 'extra-fees-for-woocommerce' ); ?></option>
			<option value="3" <?php echo selected( $fee_type, '3', true ); ?>><?php esc_html_e( 'Fixed + Percentage', 'extra-fees-for-woocommerce' ); ?></option>
		</select>
	</p>
	<p class="efw-show-if-enable efw-percentage-calc-based-on">
		<label><?php esc_html_e( 'Percentage calculation based on', 'extra-fees-for-woocommerce' ); ?></label>
		<select class="efw-percentage-calculation-based-on" name="efw_percentage_type_for_<?php echo esc_attr( $gateway_id ); ?>">
			<option value="1" <?php echo selected( $percentage_type, '1', true ); ?>><?php esc_html_e( 'Cart Subtotal', 'extra-fees-for-woocommerce' ); ?></option>
			<option value="2" <?php echo selected( $percentage_type, '2', true ); ?>><?php esc_html_e( 'Order Total', 'extra-fees-for-woocommerce' ); ?></option>
		</select>
	</p>
	<p class="efw-show-if-enable efw-percentage-fee-type">
		<label><?php esc_html_e( 'Percentage Type', 'extra-fees-for-woocommerce' ); ?></label>
		<select class="efw-percentage-fee-type" name="efw_percentage_fee_type_for_<?php echo esc_attr( $gateway_id ); ?>">
			<option value="1" <?php echo selected( $percentage_fee_type, '1', true ); ?>><?php esc_html_e( 'Add Percentage to Order Total', 'extra-fees-for-woocommerce' ); ?></option>
			<option value="2" <?php echo selected( $percentage_fee_type, '2', true ); ?>><?php esc_html_e( 'Include Percentage to Order Total', 'extra-fees-for-woocommerce' ); ?></option>
		</select>
		<?php echo wc_help_tip( '<b>Add Percentage to Total:</b> The fee will be calculated based on the Order Total without the fee included.<br><b>Include Percentage to Total:</b> The fee will be calculated based on the Order Total with the fee included.' ); ?>
	</p>
	<p class="efw-show-if-enable efw-fixed-fee">
		<label><?php esc_html_e( 'Fixed Fee Value', 'extra-fees-for-woocommerce' ); ?><span class="required">*</span></label>
		<input type="number" min="0" step="any" name="efw_fixed_value_for_<?php echo esc_attr( $gateway_id ); ?>" value="<?php echo esc_html( $fixed_value ); ?>"/>
	</p>
	<p class="efw-show-if-enable efw-percent-cart-subtotal">
		<label><?php esc_html_e( 'Fee Value in %', 'extra-fees-for-woocommerce' ); ?><span class="required">*</span></label>
		<input type="number" min="0" step="any" name="efw_percent_value_of_cart_subtotal_for_<?php echo esc_attr( $gateway_id ); ?>" value="<?php echo esc_html( $percent_of_cart_subtotal ); ?>"/>
	</p>
	<p class="efw-show-if-enable efw-add-fixed-fee">
		<label><?php esc_html_e( 'Add Fixed Fee', 'extra-fees-for-woocommerce' ); ?></label>
		<select name="efw_add_fixed_for_<?php echo esc_attr( $gateway_id ); ?>">
			<option value="1" <?php echo selected( $add_fixed_fee_on, '1', true ); ?>><?php esc_html_e( 'After Percentage Value is Calculated', 'extra-fees-for-woocommerce' ); ?></option>
			<option value="2" <?php echo selected( $add_fixed_fee_on, '2', true ); ?>><?php esc_html_e( 'Before Percentage Value is Calculated', 'extra-fees-for-woocommerce' ); ?></option>
		</select>
	</p>
	<p class="efw-show-if-enable efw-minimum-fee">
		<label><?php esc_html_e( 'Minimum Fee', 'extra-fees-for-woocommerce' ); ?></label>
		<input type="number" min="0" name="efw_min_fee_for_<?php echo esc_attr( $gateway_id ); ?>" value="<?php echo esc_html( $min_fee ); ?>"/>
		<?php echo wc_help_tip( 'The fee value configured in this field will consider when the fee is calculated less than the minimum fee' ); ?>
	</p>
	<p class="efw-show-if-enable efw-maximum-fee">
		<label><?php esc_html_e( 'Maximum Fee', 'extra-fees-for-woocommerce' ); ?></label>
		<input type="number" min="0" name="efw_max_fee_for_<?php echo esc_attr( $gateway_id ); ?>" value="<?php echo esc_html( $max_fee ); ?>"/>
		<?php echo wc_help_tip( 'The fee value configured in this field will consider when the fee is calculated more than the maximum fee' ); ?>
	</p>
	<p class="efw-show-if-enable">
		<label><?php esc_html_e( 'Minimum Cart Subtotal/Order Total to Add Fee', 'extra-fees-for-woocommerce' ); ?></label>
		<input type="number" min="0" name="efw_min_subtotal_for_<?php echo esc_attr( $gateway_id ); ?>" value="<?php echo esc_html( $min_total ); ?>" step="any"/>
		<?php echo wc_help_tip( '<b>Fixed Fee</b> - The Minimum Order Total is required to add a fee.<br><b>Percentage of Cart Subtotal/Order Total</b> - The Minimum Cart Subtotal/Order Total which is required to add a fee.' ); ?>
	</p>
	<p class="efw-show-if-enable">
		<label><?php esc_html_e( ' Maximum Cart Subtotal/Order Total to Add Fee', 'extra-fees-for-woocommerce' ); ?></label>
		<input type="number" min="0" name="efw_max_subtotal_for_<?php echo esc_attr( $gateway_id ); ?>" value="<?php echo esc_html( $max_total ); ?>" step="any"/>
		<?php echo wc_help_tip( '<b>Fixed Fee</b> - The fee will be added when the Order Total is less than or equal to the value set in this field.<br><b>Percentage of Cart Subtotal/Order Total</b> - The fee will be added when the Cart Subtotal/Order Total is less than or equal to the value set in this field.' ); ?>
	</p>
</div>
<?php
