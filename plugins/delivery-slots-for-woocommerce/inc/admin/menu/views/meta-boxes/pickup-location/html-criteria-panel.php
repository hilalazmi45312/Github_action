<?php
/**
 * Pickup location panel - Criteria.
 *
 * @since 4.0.0
 */

defined( 'ABSPATH' ) || exit;
?>
<div id='dey_pickup_location_data_criteria' class='dey-pickup-location-options-wrapper woocommerce_options_panel'>
	<div class='dey-restriction-rules-group-wrapper dey-rules-group-wrapper'>
		<button type='button' class='dey-add-restriction-rules-group dey-add-rules-group button-primary'><?php esc_html_e( 'Add Rule', 'delivery-slots-for-woocommerce' ); ?></button>

		<div class='dey-restriction-rules-group-content dey-rules-group-content'>
			<?php
			$rule_groups = $dey_pickup_location->get_restriction_rule_groups();
			if ( dey_check_is_array( $rule_groups ) ) :
				$show_or_label = false;
				foreach ( $rule_groups as $group_id => $rule_group ) :
					if ( $show_or_label ) :
						?>
						<p class='dey-restriction-rules-or-label dey-rules-or-label'><?php esc_html__( 'OR', 'delivery-slots-for-woocommerce' ); ?></p>
						<?php
					endif;
					?>
					<div class='dey-restriction-rules-wrapper dey-rules-wrapper' data-group_id='<?php echo esc_attr( $group_id ); ?>'>
						<p class='dey-restriction-rules-header dey-rules-header'>
							<button type='button' class='button dey-add-restriction-rule'><?php esc_html_e( 'Add Criteria', 'delivery-slots-for-woocommerce' ); ?></button>
							<button type='button' class='button dey-remove-restriction-rules-group'><?php esc_html_e( 'Remove Rule', 'delivery-slots-for-woocommerce' ); ?></button>
							<span class='dey-restriction-rules-toggle dashicons dashicons-arrow-down-alt2'></span>
						</p>
						<div class='dey-restriction-rules-content dey-rules-content'>
							<?php
							$show_and_label = false;
							foreach ( $rule_group as $rule_id => $rule ) :
								$rule = dey_format_order_pickup_location_restriction_rule_data( $rule );
								/* translators: %1$s - Group ID, %2$s - Rule ID */
								$name = sprintf( 'dey_restriction_rule_groups[%1$s][%2$s]', $group_id, $rule_id );
								if ( $show_and_label ) :
									?>
									<p class='dey-restriction-rules-and-label dey-rules-and-label'><?php esc_html__( 'AND', 'delivery-slots-for-woocommerce' ); ?></p>
									<?php
								endif;
								?>
								<div class='dey-restriction-rule-wrapper dey-rule-wrapper' data-rule_id='<?php echo esc_attr( $rule_id ); ?>'>
									<p class='dey-restriction-rule-types-field'>
										<select name='<?php echo esc_attr( $name ); ?>[rule_type]' class='dey-restriction-rule-type'>
											<?php foreach ( dey_order_pickup_location_restriction_rule_options() as $rule_option_id => $rule_option_name ) : ?>
												<option value='<?php echo esc_attr( $rule_option_id ); ?>' <?php selected( $rule['rule_type'], $rule_option_id ); ?>><?php echo esc_html( $rule_option_name ); ?></option>
											<?php endforeach; ?>
										</select>
									</p>

									<p class='dey-restriction-rule-type-field dey-restriction-rule-user-type-field'>
										<select name='<?php echo esc_attr( $name ); ?>[user_type]' class='dey-restriction-rule-user-type'>
											<?php foreach ( dey_restriction_rule_user_type_options() as $user_option_id => $user_option_name ) : ?>
												<option value='<?php echo esc_attr( $user_option_id ); ?>' <?php selected( $rule['user_type'], $user_option_id ); ?>><?php echo esc_html( $user_option_name ); ?></option>
											<?php endforeach; ?>
										</select>
									</p>

									<p class='dey-restriction-rule-type-field dey-restriction-rule-product-type-field'>
										<select class='dey-restriction-rule-product-type' name='<?php echo esc_attr( $name ); ?>[product_type]'>
											<?php foreach ( dey_restriction_rule_product_type_options() as $product_option_id => $product_option_name ) : ?>
												<option value='<?php echo esc_attr( $product_option_id ); ?>' <?php selected( $rule['product_type'], $product_option_id ); ?>><?php echo esc_html( $product_option_name ); ?></option>
											<?php endforeach; ?>
										</select>
									</p>
									<p class='dey-restriction-rule-type-field dey-restriction-rule-order-type-field'>
										<select class='dey-restriction-rule-order-type' name="<?php echo esc_attr( $name ); ?>[order_type]">
											<?php foreach ( dey_get_restriction_rule_order_type_options() as $order_type_id => $order_type_name ) : ?>
												<option value="<?php echo esc_attr( $order_type_id ); ?>" <?php selected( $order_type_id, $rule['order_type'] ); ?>><?php echo esc_html( $order_type_name ); ?></option>
											<?php endforeach; ?>
										</select>
									</p>
									<p class='dey-restriction-rule-type-field dey-restriction-rule-shipping-type-field'>
										<select class='dey-pickup-location-filter-shipping-type' name="<?php echo esc_attr( $name ); ?>[shipping_type]">
											<?php foreach ( dey_get_order_pickup_location_shipping_type_options() as $key => $product_option_name ) : ?>
												<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $product_option_name ); ?></option>
											<?php endforeach; ?>
										</select>
									</p>
									<p class='dey-restriction-rule-field dey-restriction-rule-products-field'>
										<?php
										dey_select2_html(
											array(
												'name'    => $name . '[products]',
												'list_type' => 'products',
												'action'  => 'dey_json_search_products_and_variations',
												'options' => $rule['products'],
												'placeholder' => __( 'Select products', 'delivery-slots-for-woocommerce' ),
											)
										);
										?>
									</p>

									<p class='dey-restriction-rule-field dey-restriction-rule-product-types-field'>
										<select name='<?php echo esc_attr( $name ); ?>[product_types][]' class='dey_select2' multiple='multiple' data-placeholder='<?php esc_attr_e( 'Select product types', 'delivery-slots-for-woocommerce' ); ?>'>
											<?php
											foreach ( wc_get_product_types() as $product_type_id => $product_type_name ) :
												$selected = in_array( $product_type_id, $rule['product_types'] ) ? ' selected="selected"' : '';
												?>
												<option value='<?php echo esc_attr( $product_type_id ); ?>'<?php echo esc_attr( $selected ); ?>><?php echo esc_html( $product_type_name ); ?></option>
											<?php endforeach; ?>
										</select>
									</p>

									<p class='dey-restriction-rule-field dey-restriction-rule-categories-field'>
										<select name='<?php echo esc_attr( $name ); ?>[categories][]' class='dey_select2' multiple='multiple' data-placeholder='<?php esc_attr_e( 'Select categories', 'delivery-slots-for-woocommerce' ); ?>'>
											<?php
											foreach ( dey_get_wc_categories() as $category_id => $category_name ) :
												$selected = in_array( $category_id, $rule['categories'] ) ? ' selected="selected"' : '';
												?>
												<option value='<?php echo esc_attr( $category_id ); ?>'<?php echo esc_attr( $selected ); ?>><?php echo esc_html( $category_name ); ?></option>
											<?php endforeach; ?>
										</select>
									</p>

									<p class='dey-restriction-rule-field dey-restriction-rule-tags-field'>
										<select name='<?php echo esc_attr( $name ); ?>[tags][]' class='dey_select2' multiple='multiple' data-placeholder='<?php esc_attr_e( 'Select tags', 'delivery-slots-for-woocommerce' ); ?>'>
											<?php
											foreach ( dey_get_wc_product_tags() as $tag_id => $tag_name ) :
												$selected = in_array( $tag_id, $rule['tags'] ) ? ' selected="selected"' : '';
												?>
												<option value='<?php echo esc_attr( $tag_id ); ?>'<?php echo esc_attr( $selected ); ?>><?php echo esc_html( $tag_name ); ?></option>
											<?php endforeach; ?>
										</select>
									</p>

									<p class='dey-restriction-rule-field dey-restriction-rule-country-field'>
										<select name='<?php echo esc_attr( $name ); ?>[countries][]' class='dey_select2' multiple='multiple' data-placeholder='<?php esc_attr_e( 'Select countries', 'delivery-slots-for-woocommerce' ); ?>'>
											<?php
											foreach ( dey_get_countries() as $country_code => $country_name ) :
												$selected = in_array( $country_code, $rule['countries'] ) ? ' selected="selected"' : '';
												?>
												<option value='<?php echo esc_attr( $country_code ); ?>'<?php echo esc_attr( $selected ); ?>><?php echo esc_html( $country_name ); ?></option>
											<?php endforeach; ?>
										</select>
									</p>
									<p class='dey-restriction-rule-field dey-restriction-rule-shipping-method-field'>
										<select class='dey_select2 dey-pickup-location-filter-shipping-methods' multiple='multiple' name="<?php echo esc_attr( $name ); ?>[shipping_methods][]">
											<?php
											foreach ( dey_get_shipping_methods() as $shipping_method_key => $shipping_method_name ) :
												$selected = in_array( $shipping_method_key, $rule['shipping_methods'] ) ? ' selected="selected"' : '';
												?>
												<option value="<?php echo esc_attr( $shipping_method_key ); ?>" <?php echo esc_attr( $selected ); ?>><?php echo esc_html( $shipping_method_name ); ?></option>
											<?php endforeach; ?>
										</select>
									</p>
									<p class='dey-restriction-rule-field dey-restriction-rule-order-field dey-restriction-rule-order-price-field'>
										<input type='text' class='dey-restriction-rule-price wc_input_price' name="<?php echo esc_attr( $name ); ?>[price]" value="<?php echo esc_attr( $rule['price'] ); ?>"/>
									</p>
									<p class='dey-restriction-rule-field dey-restriction-rule-order-field dey-restriction-rule-order-count-field'>
										<input type='number' class='dey-restriction-rule-count' name="<?php echo esc_attr( $name ); ?>[count]" value="<?php echo esc_attr( $rule['count'] ); ?>"/>
									</p>
									<button type='button' class='button dey-remove-restriction-rule dey-remove-rule'><?php esc_html_e( 'Remove Criteria', 'delivery-slots-for-woocommerce' ); ?></button>
								</div>
								<?php
								$show_and_label = true;
							endforeach;
							?>
						</div>
					</div>
					<?php
					$show_or_label = true;
				endforeach;
			endif;
			?>
		</div>
	</div>

	<script type='text/html' id='tmpl-dey-restriction-rule-or-label'>
		<p class='dey-restriction-rules-or-label dey-rules-or-label'><?php esc_html_e( 'OR', 'delivery-slots-for-woocommerce' ); ?></p>
	</script>

	<script type='text/html' id='tmpl-dey-restriction-rule-and-label'>
		<p class='dey-restriction-rules-and-label dey-rules-and-label'><?php esc_html_e( 'AND', 'delivery-slots-for-woocommerce' ); ?></p>
	</script>

	<script type='text/html' id='tmpl-dey-restriction-rule'>
		<?php require DEY_ABSPATH . 'inc/admin/menu/views/meta-boxes/pickup-location/html-restriction-rule.php'; ?>
	</script>

	<script type='text/html' id='tmpl-dey-restriction-rules-group'>
		<div class='dey-restriction-rules-wrapper dey-rules-wrapper' data-group_id='{{data.group_id}}'>
			<p class='dey-restriction-rules-header dey-rules-header'>
				<button type='button' class='button dey-add-restriction-rule'><?php esc_html_e( 'Add Criteria', 'delivery-slots-for-woocommerce' ); ?></button>
				<button type='button' class='button dey-remove-restriction-rules-group'><?php esc_html_e( 'Remove Rule', 'delivery-slots-for-woocommerce' ); ?></button>
				<span class='dey-restriction-rules-toggle dashicons dashicons-arrow-down-alt2'></span>
			</p>
			<div class='dey-restriction-rules-content dey-rules-content'>
				<?php require DEY_ABSPATH . 'inc/admin/menu/views/meta-boxes/pickup-location/html-restriction-rule.php'; ?>
			</div>
		</div>
	</script>

</div>
<?php
