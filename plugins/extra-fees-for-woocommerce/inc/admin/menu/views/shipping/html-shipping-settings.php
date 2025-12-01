<?php
/* Admin HTML Fee Settings for Shipping */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="efw-shipping-fee-contents-container">
<h3><?php echo esc_html( $method_title ); ?></h3>
<table class="form-table efw-shipping-fee-contents widefat">
	<tbody>
		<tr valign="top">
			<th scope="row" class="titledesc">
			<?php
			echo wp_kses_post( __( 'Enable Fee for this Shipping Method', 'extra-fees-for-woocommerce' ) );
			?>
			</th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<label for="efw_<?php echo esc_attr( $shipping_method_key ); ?>_enable">
						<input name="efw_enable_<?php echo esc_attr( $shipping_method_key ); ?>"
							   class="efw-enable-shipping-method-fee"
							   id="efw_enable_<?php echo esc_attr( $shipping_method_key ); ?>" 
							   type="checkbox"
							   <?php
								if ( 'on' == $enable ) {
									?>
								   checked="checked"<?php } ?>>
												<?php echo esc_html__( 'When enabled, a fee has to be paid by the user for choosing the particular shipping method.', 'extra-fees-for-woocommerce' ); ?>
					</label> 																
				</fieldset>
			</td>
		</tr>
		<tr valign="top" class="efw-hide-shipping-options">
			<th scope="row" class="titledesc"><?php echo esc_html__( 'Fee should apply for', 'extra-fees-for-woocommerce' ); ?></th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<select class="efw-shipping-user-filter-type" name="efw_shipping_user_filter_type_<?php echo esc_attr( $shipping_method_key ); ?>">
						<option value="1" <?php echo selected( $user_filter_type, '1', true ); ?>><?php esc_html_e( 'All User(s)', 'extra-fees-for-woocommerce' ); ?></option>
						<option value="2" <?php echo selected( $user_filter_type, '2', true ); ?>><?php esc_html_e( 'Include User(s)', 'extra-fees-for-woocommerce' ); ?></option>
						<option value="3" <?php echo selected( $user_filter_type, '3', true ); ?>><?php esc_html_e( 'Exclude User(s)', 'extra-fees-for-woocommerce' ); ?></option>
						<option value="4" <?php echo selected( $user_filter_type, '4', true ); ?>><?php esc_html_e( 'Include User Role(s)', 'extra-fees-for-woocommerce' ); ?></option>
						<option value="5" <?php echo selected( $user_filter_type, '5', true ); ?>><?php esc_html_e( 'Exclude User Role(s)', 'extra-fees-for-woocommerce' ); ?></option>
					</select>																
				</fieldset>
			</td>
		</tr>
		<tr valign="top" class="efw-hide-shipping-options">
			<th scope="row" class="titledesc"><?php echo esc_html__( 'Select User(s) to Include', 'extra-fees-for-woocommerce' ); ?></th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<?php
					$include_user_args = array(
						'name'                    => "efw_shipping_include_users_$shipping_method_key",
						'list_type'               => 'customers',
						'exclude_global_variable' => 'yes',
						'action'                  => 'efw_customers_search',
						'placeholder'             => esc_html__( 'Search a User', 'extra-fees-for-woocommerce' ),
						'options'                 => $include_users,
						'class'                   => 'efw-shipping-include-users',
					);
					efw_select2_html( $include_user_args );
					?>
				</fieldset>
			</td>
		</tr>
		<tr valign="top" class="efw-hide-shipping-options">
			<th scope="row" class="titledesc"><?php echo esc_html__( 'Select User(s) to Exclude', 'extra-fees-for-woocommerce' ); ?></th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<?php
					$exclude_user_args = array(
						'name'                    => "efw_shipping_exclude_users_$shipping_method_key",
						'list_type'               => 'customers',
						'exclude_global_variable' => 'yes',
						'action'                  => 'efw_customers_search',
						'placeholder'             => esc_html__( 'Search a User', 'extra-fees-for-woocommerce' ),
						'options'                 => $exclude_users,
						'class'                   => 'efw-shipping-exclude-users',
					);
					efw_select2_html( $exclude_user_args );
					?>
				</fieldset>
			</td>
		</tr>
		<tr valign="top" class="efw-hide-shipping-options">
			<th scope="row" class="titledesc"><?php echo esc_html__( 'Select User Role(s) to Include', 'extra-fees-for-woocommerce' ); ?></th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<select class="efw_select2 efw-shipping-include-userroles" name="efw_shipping_include_userroles_<?php echo esc_attr( $shipping_method_key ); ?>[]" multiple="multiple">
						<?php
						foreach ( $user_roles as $user_role_id => $user_role_name ) :
							$selected = ( in_array( $user_role_id, (array) $include_user_roles ) ) ? ' selected="selected"' : '';
							?>
							<option value="<?php echo esc_attr( $user_role_id ); ?>"<?php echo esc_attr( $selected ); ?>><?php echo esc_html( $user_role_name ); ?></option>
						<?php endforeach; ?>
					</select>
				</fieldset>
			</td>
		</tr>
		<tr valign="top" class="efw-hide-shipping-options">
			<th scope="row" class="titledesc"><?php echo esc_html__( 'Select User Role(s) to Exclude', 'extra-fees-for-woocommerce' ); ?></th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<select class="efw_select2 efw-shipping-exclude-userroles" name="efw_shipping_exclude_userroles_<?php echo esc_attr( $shipping_method_key ); ?>[]" multiple="multiple">
						<?php
						foreach ( $user_roles as $user_role_id => $user_role_name ) :
							$selected = ( in_array( $user_role_id, (array) $exclude_user_roles ) ) ? ' selected="selected"' : '';
							?>
							<option value="<?php echo esc_attr( $user_role_id ); ?>"<?php echo esc_attr( $selected ); ?>><?php echo esc_html( $user_role_name ); ?></option>
						<?php endforeach; ?>
					</select>
				</fieldset>
			</td>
		</tr>
		<tr valign="top" class="efw-hide-shipping-options">
			<th scope="row" class="titledesc"><?php echo esc_html__( 'Fee for Product(s)/Categories', 'extra-fees-for-woocommerce' ); ?></th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<select class="efw-shipping-product-filter-type" name="efw_shipping_product_filter_type_<?php echo esc_attr( $shipping_method_key ); ?>">
						<option value="1" <?php echo selected( $product_filter_type, '1', true ); ?>><?php esc_html_e( 'All Product(s)', 'extra-fees-for-woocommerce' ); ?></option>
						<option value="2" <?php echo selected( $product_filter_type, '2', true ); ?>><?php esc_html_e( 'Include Product(s)', 'extra-fees-for-woocommerce' ); ?></option>
						<option value="3" <?php echo selected( $product_filter_type, '3', true ); ?>><?php esc_html_e( 'Exclude Product(s)', 'extra-fees-for-woocommerce' ); ?></option>
						<option value="4" <?php echo selected( $product_filter_type, '4', true ); ?>><?php esc_html_e( 'Include Categories', 'extra-fees-for-woocommerce' ); ?></option>
						<option value="5" <?php echo selected( $product_filter_type, '5', true ); ?>><?php esc_html_e( 'Exclude Categories', 'extra-fees-for-woocommerce' ); ?></option>
					</select>																
				</fieldset>
			</td>
		</tr>
		<tr valign="top" class="efw-hide-shipping-options">
			<th scope="row" class="titledesc"><?php echo esc_html__( 'Select Product(s) to Include', 'extra-fees-for-woocommerce' ); ?></th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<?php
					$include_products_args = array(
						'name'                    => 'efw_shipping_include_products_' . $shipping_method_key,
						'list_type'               => 'products',
						'exclude_global_variable' => 'yes',
						'action'                  => 'efw_product_search',
						'placeholder'             => esc_html__( 'Search a Product', 'extra-fees-for-woocommerce' ),
						'options'                 => $include_products,
						'class'                   => 'efw-shipping-include-products',
					);
					efw_select2_html( $include_products_args );
					?>
				</fieldset>
			</td>
		</tr>
		<tr valign="top" class="efw-hide-shipping-options">
			<th scope="row" class="titledesc"><?php echo esc_html__( 'Select Product(s) to Exclude', 'extra-fees-for-woocommerce' ); ?></th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<?php
					$exclude_products_args = array(
						'name'                    => 'efw_shipping_exclude_products_' . $shipping_method_key,
						'list_type'               => 'products',
						'exclude_global_variable' => 'yes',
						'action'                  => 'efw_product_search',
						'placeholder'             => esc_html__( 'Search a Product', 'extra-fees-for-woocommerce' ),
						'options'                 => $exclude_products,
						'class'                   => 'efw-shipping-exclude-products',
					);
					efw_select2_html( $exclude_products_args );
					?>
				</fieldset>
			</td>
		</tr>
		<tr valign="top" class="efw-hide-shipping-options">
			<th scope="row" class="titledesc"><?php echo esc_html__( 'Select Categories to Include', 'extra-fees-for-woocommerce' ); ?></th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<select class="efw_select2 efw-shipping-include-categories" name="efw_shipping_include_categories_<?php echo esc_attr( $shipping_method_key ); ?>[]" multiple="multiple">
						<?php
						foreach ( $categories as $category_id => $category_name ) :
							$selected = ( in_array( $category_id, (array) $include_categories ) ) ? ' selected="selected"' : '';
							?>
							<option value="<?php echo esc_attr( $category_id ); ?>"<?php echo esc_attr( $selected ); ?>><?php echo esc_html( $category_name ); ?></option>
						<?php endforeach; ?>
					</select>
				</fieldset>
			</td>
		</tr>
		<tr valign="top" class="efw-hide-shipping-options">
			<th scope="row" class="titledesc"><?php echo esc_html__( 'Select Additional Product(s) to Include', 'extra-fees-for-woocommerce' ); ?></th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<?php
					$include_additional_products_args = array(
						'name'                    => 'efw_shipping_include_additional_products_' . $shipping_method_key,
						'list_type'               => 'products',
						'exclude_global_variable' => 'yes',
						'action'                  => 'efw_product_search',
						'placeholder'             => esc_html__( 'Search a Product', 'extra-fees-for-woocommerce' ),
						'options'                 => $include_additional_products,
						'class'                   => 'efw-shipping-include-additional-products',
					);
					efw_select2_html( $include_additional_products_args );
					?>
				</fieldset>
			</td>
		</tr>
		<tr valign="top" class="efw-hide-shipping-options">
			<th scope="row" class="titledesc"><?php echo esc_html__( 'Select Categories to Exclude', 'extra-fees-for-woocommerce' ); ?></th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<select class="efw_select2 efw-shipping-exclude-categories" name="efw_shipping_exclude_categories_<?php echo esc_attr( $shipping_method_key ); ?>[]" multiple="multiple">
						<?php
						foreach ( $categories as $category_id => $category_name ) :
							$selected = ( in_array( $category_id, (array) $exclude_categories ) ) ? ' selected="selected"' : '';
							?>
							<option value="<?php echo esc_attr( $category_id ); ?>"<?php echo esc_attr( $selected ); ?>><?php echo esc_html( $category_name ); ?></option>
						<?php endforeach; ?>
					</select>
				</fieldset>
			</td>
		</tr>
		<tr valign="top" class="efw-hide-shipping-options">
			<th scope="row" class="titledesc"><?php echo esc_html__( 'Select Additional Product(s) to Exclude', 'extra-fees-for-woocommerce' ); ?></th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<?php
					$exclude_products_args = array(
						'name'                    => 'efw_shipping_exclude_additional_products_' . $shipping_method_key,
						'list_type'               => 'products',
						'exclude_global_variable' => 'yes',
						'action'                  => 'efw_product_search',
						'placeholder'             => esc_html__( 'Search a Product', 'extra-fees-for-woocommerce' ),
						'options'                 => $exclude_additional_products,
						'class'                   => 'efw-shipping-exclude-additional-products',
					);
					efw_select2_html( $exclude_products_args );
					?>
				</fieldset>
			</td>
		</tr>
		<tr valign="top" class="efw-hide-shipping-options">
			<th scope="row" class="titledesc"><?php echo esc_html__( 'Date Ranges', 'extra-fees-for-woocommerce' ); ?></th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<?php
					$rule_valid_from_date_args = array(
						'name'        => 'efw_shipping_from_date_' . $shipping_method_key,
						'value'       => $from_date,
						'wp_zone'     => false,
						'placeholder' => EFW_Date_Time::get_wp_date_format(),
					);
					efw_get_datepicker_html( $rule_valid_from_date_args );
					?>
					<span class="efw-date-ranges-to-label">
						<?php esc_html_e( 'To', 'extra-fees-for-woocommerce' ); ?>
					</span>
					<?php
					$rule_valid_to_date_args = array(
						'name'        => 'efw_shipping_to_date_' . $shipping_method_key,
						'value'       => $to_date,
						'wp_zone'     => false,
						'placeholder' => EFW_Date_Time::get_wp_date_format(),
					);
					efw_get_datepicker_html( $rule_valid_to_date_args );
					?>
				</fieldset>
			</td>
		</tr>
		<tr valign="top" class="efw-hide-shipping-options">
			<th scope="row" class="titledesc"><?php echo esc_html__( 'Weekday(s)', 'extra-fees-for-woocommerce' ); ?></th>
			<td class="forminp forminp-checkbox">
				<select class="efw_select2" multiple="multiple" name="efw_shipping_weekdays_for_<?php echo esc_attr( $shipping_method_key ); ?>[]">
					<?php foreach ( efw_get_weekdays_options() as $specific_weekdays_id => $specific_weekdays_name ) : ?>
						<option value="<?php echo esc_attr( $specific_weekdays_id ) ; ?>" <?php echo in_array( $specific_weekdays_id , (array) $selected_week_days ) ? 'selected="selected"' : '' ; ?>><?php echo esc_html( $specific_weekdays_name ) ; ?></option>
					<?php endforeach ; ?>
				</select>
			</td>
		</tr>
		<tr valign="top" class="efw-hide-shipping-options">
			<th scope="row" class="titledesc"><?php echo esc_html__( 'Fee Text', 'extra-fees-for-woocommerce' ); ?><span class="required">*</span></th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<input id="efw_shipping_fee_text_<?php echo esc_attr( $shipping_method_key ); ?>" 
						   class="efw-shipping-fee-text" 
						   name="efw_shipping_fee_text_<?php echo esc_attr( $shipping_method_key ); ?>" 
						   type="text"
						   value="<?php echo esc_html( $fee_text ); ?>"/>
				</fieldset>
			</td>
		</tr>
		<tr valign="top" class="efw-hide-shipping-options">
			<th scope="row" class="titledesc"><?php echo esc_html__( 'Fee Description', 'extra-fees-for-woocommerce' ); ?></th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<textarea id="efw_shipping_fee_description_<?php echo esc_attr( $shipping_method_key ); ?>" 
						   class="efw-shipping-fee-description" 
						   name="efw_shipping_fee_description_<?php echo esc_attr( $shipping_method_key ); ?>"><?php echo wp_kses_post( $fee_description ); ?></textarea>
				</fieldset>
			</td>
		</tr>
		<tr valign="top" class="efw-hide-shipping-options">
			<th scope="row" class="titledesc"><?php echo esc_html__( 'Tax Class', 'extra-fees-for-woocommerce' ); ?></th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<select name="efw_shipping_tax_class_<?php echo esc_attr( $shipping_method_key ); ?>">
						<?php foreach ( efw_get_fee_tax_classes() as $tax_class_id => $tax_class_name ) : ?>
							<option value="<?php echo esc_attr( $tax_class_id ); ?>" <?php echo selected( $tax_class, $tax_class_id, true ); ?>><?php echo esc_html( $tax_class_name ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php echo wp_kses_post( wc_help_tip( 'Select the tax which should be used for calculating the fee', 'extra-fees-for-woocommerce' ) ); ?>
				</fieldset>
			</td>
		</tr>

		<tr valign="top" class="efw-hide-shipping-options">
			<th scope="row" class="titledesc"><?php echo esc_html__( 'Fee Type', 'extra-fees-for-woocommerce' ); ?></th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<select class="efw-shipping-fee-type" name="efw_shipping_fee_type_<?php echo esc_attr( $shipping_method_key ); ?>">
						<option value="1" <?php echo selected( $fee_type, '1', true ); ?>><?php esc_html_e( 'Fixed', 'extra-fees-for-woocommerce' ); ?></option>
						<option value="2" <?php echo selected( $fee_type, '2', true ); ?>><?php esc_html_e( 'Percentage', 'extra-fees-for-woocommerce' ); ?></option>
						<option value="3" <?php echo selected( $fee_type, '3', true ); ?>><?php esc_html_e( 'Fixed + Percentage', 'extra-fees-for-woocommerce' ); ?></option>
					</select>
				</fieldset>
			</td>
		</tr>
		<tr valign="top" class="efw-hide-shipping-options">
			<th scope="row" class="titledesc"><?php echo esc_html__( 'Percentage based on', 'extra-fees-for-woocommerce' ); ?></th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<select class="efw-percentage-based-on" name="efw_percentage_based_on_<?php echo esc_attr( $shipping_method_key ); ?>">
						<option value="1" <?php echo selected( $percentage_type, '1', true ); ?>><?php esc_html_e( 'Cart Subtotal', 'extra-fees-for-woocommerce' ); ?></option>
						<option value="2" <?php echo selected( $percentage_type, '2', true ); ?>><?php esc_html_e( 'Order Total', 'extra-fees-for-woocommerce' ); ?></option>
					</select>
				</fieldset>
			</td>
		</tr>
		<tr valign="top" class="efw-hide-shipping-options">
			<th scope="row" class="titledesc"><?php echo esc_html__( 'Percentage Type', 'extra-fees-for-woocommerce' ); ?></th>
			<td class="forminp forminp-checkbox">
				<select class="efw-percentage-fee-type" name="efw_percentage_fee_type_for_<?php echo esc_attr( $shipping_method_key ); ?>">
					<option value="1" <?php echo selected( $percentage_fee_type, '1', true ); ?>><?php esc_html_e( 'Add Percentage to Order Total', 'extra-fees-for-woocommerce' ); ?></option>
					<option value="2" <?php echo selected( $percentage_fee_type, '2', true ); ?>><?php esc_html_e( 'Include Percentage to Order Total', 'extra-fees-for-woocommerce' ); ?></option>
				</select>
				<?php echo wc_help_tip( '<b>Add Percentage to Total:</b> The fee will be calculated based on the Order Total without the fee included.<br><b>Include Percentage to Total:</b> The fee will be calculated based on the Order Total with the fee included.' ); ?>
			</td>
		</tr>
		<tr valign="top" class="efw-hide-shipping-options">
			<th scope="row" class="titledesc"><?php echo esc_html__( 'Fixed Value', 'extra-fees-for-woocommerce' ); ?><span class="required">*</span></th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<input type="number" class="efw-shipping-fixed-value" min="0" step="any" name="efw_shipping_fixed_value_<?php echo esc_attr( $shipping_method_key ); ?>" value="<?php echo esc_html( $fixed_value ); ?>"/>
				</fieldset>
			</td>
		</tr>

		<tr valign="top" class="efw-hide-shipping-options">
			<th scope="row" class="titledesc"><?php echo esc_html__( 'Percentage Value', 'extra-fees-for-woocommerce' ); ?><span class="required">*</span></th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<input type="number" class="efw-shipping-percentage-value" min="0" step="any" name="efw_shipping_percentage_value_<?php echo esc_attr( $shipping_method_key ); ?>" value="<?php echo esc_html( $percentage_value ); ?>"/>
				</fieldset>
			</td>
		</tr>
		<tr valign="top" class="efw-hide-shipping-options">
			<th scope="row" class="titledesc"><?php echo esc_html__( 'Add Fixed Fee', 'extra-fees-for-woocommerce' ); ?><span class="required">*</span></th>
			<td class="forminp forminp-checkbox">
				<select class="efw-add-fixed-fee" name="efw_add_fixed_for_<?php echo esc_attr( $shipping_method_key ); ?>">
					<option value="1" <?php echo selected( $add_fixed_fee_on, '1', true ); ?>><?php esc_html_e( 'After Percentage Value is Calculated', 'extra-fees-for-woocommerce' ); ?></option>
					<option value="2" <?php echo selected( $add_fixed_fee_on, '2', true ); ?>><?php esc_html_e( 'Before Percentage Value is Calculated', 'extra-fees-for-woocommerce' ); ?></option>
				</select>
			</td>
		</tr>
		<tr valign="top" class="efw-hide-shipping-options">
			<th scope="row" class="titledesc"><?php echo esc_html__( 'Minimum Fee', 'extra-fees-for-woocommerce' ); ?></th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<input type="number" class="efw-shipping-minimum-fee-value" min="0" step="any" name="efw_shipping_minimum_fee_value_<?php echo esc_attr( $shipping_method_key ); ?>" value="<?php echo esc_html( $minimum_fee_value ); ?>"/>
				</fieldset>
			</td>
		</tr>

		<tr valign="top" class="efw-hide-shipping-options">
			<th scope="row" class="titledesc"><?php echo esc_html__( 'Maximum Fee', 'extra-fees-for-woocommerce' ); ?></th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<input type="number" class="efw-shipping-maximum-fee-value" min="0" step="any" name="efw_shipping_maximum_fee_value_<?php echo esc_attr( $shipping_method_key ); ?>" value="<?php echo esc_html( $maximum_fee_value ); ?>"/>
				</fieldset>
			</td>
		</tr>

		<tr valign="top" class="efw-hide-shipping-options">
			<th scope="row" class="titledesc"><?php echo esc_html__( 'Minimum Cart Subtotal/Order Total to Add Fee', 'extra-fees-for-woocommerce' ); ?></th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<input type="number" min="0" step="any" name="efw_shipping_fee_minimum_restriction_value_<?php echo esc_attr( $shipping_method_key ); ?>" value="<?php echo esc_html( $minimum_restriction_value ); ?>"/>
				</fieldset>
			</td>
		</tr>

		<tr valign="top" class="efw-hide-shipping-options">
			<th scope="row" class="titledesc"><?php echo esc_html__( 'Maximum Cart Subtotal/Order Total to Add Fee', 'extra-fees-for-woocommerce' ); ?></th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<input type="number" min="0" step="any" name="efw_shipping_fee_maximum_restriction_value_<?php echo esc_attr( $shipping_method_key ); ?>" value="<?php echo esc_html( $maximum_restriction_value ); ?>"/>
				</fieldset>
			</td>
		</tr>
	</tbody>
</table>
</div>
<?php
