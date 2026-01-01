<?php
/**
 * Export page post type select
 *
 * @package Webtoffee_Product_Feed_Sync_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wt_pf_export_main">
	<p><?php echo esc_html( $step_info['description'] ); ?></p>
	<div class="wt_pf_warn wt_pf_post_type_wrn" style="display:none;">
		<?php esc_html_e( 'Please select a post type', 'product-feed-woocommerce' ); ?>
	</div>
	<form class="wt_pf_feed_filter_form">
	<table class="form-table wt-pfd-form-table">
		
		<tr class="wt-pfd-settings-header">
		<th colspan="3"><label><?php esc_html_e( 'Configuration', 'product-feed-woocommerce' ); ?></label></th>
		</tr>
		<tr><td></td></tr>
		
		<tr>
			<th><label><?php esc_html_e( 'Country', 'product-feed-woocommerce' ); ?></label></th>
			<td>

				<?php
				global $woocommerce;
				if ( class_exists( 'WC_Countries' ) ) {
					$countries_obj = new WC_Countries();
					$countries = $countries_obj->__get( 'countries' );
				} else {
					$countries = array();
				}
				?>


				<select name="wt_pf_export_catalog_country" id="wt_pf_export_catalog_country">
					<?php
					foreach ( $countries as $key => $value ) {
						?>
						<option value="<?php echo esc_html( $key ); ?>" <?php echo ( $item_country == $key ? 'selected' : '' ); ?>><?php echo esc_html( $value ); ?></option>
						<?php
					}
					?>
				</select>
				<span class="wt-pf_form_help"><?php esc_html_e( 'Choose the country for which the feed should be generated', 'product-feed-woocommerce' ); ?></span>
			</td>
			<td></td>
		</tr>

		<tr>
			<th><label><?php esc_html_e( 'Channel', 'product-feed-woocommerce' ); ?></label></th>
			<td>
				<select name="wt_pf_export_post_type">
					<?php
					foreach ( $post_types as $key => $value ) {
						?>
						<option value="<?php echo esc_html( $key ); ?>" <?php echo ( $item_type == $key ? 'selected' : '' ); ?>><?php echo esc_html( $value ); ?></option>
						<?php
					}
					?>
				</select>
				<span class="wt-pf_form_help"><?php esc_html_e( 'Choose Google shopping or Facebook/Instagram feed from the drop-down.', 'product-feed-woocommerce' ); ?></span>
			</td>
			<td></td>
		</tr>        
		
		<tr>
			<th><label><?php esc_html_e( 'File name', 'product-feed-woocommerce' ); ?></label></th>
			<td>
				<input required type="text" name="wt_pf_export_catalog_name" value="<?php echo esc_html( $item_filename ); ?>" id="wt_pf_export_catalog_name"/>
				<span class="wt-pf_form_help"><?php esc_html_e( 'Enter a file name that is unique', 'product-feed-woocommerce' ); ?></span>
			</td>
		</tr>        


		<?php

					/**
					 * Filter the query arguments for a request.
					 *
					 * Enables adding extra arguments or setting defaults for a post
					 * collection request.
					 *
					 * @since 1.0.0
					 *
					 * @param array           $mapping_fields    Mapping fields.
					 * @param string          $this->to_export    Export post type.
					 * @param array           $form_data_mapping_fields    Form Mapping fields.
					 */
		if ( apply_filters( 'wpml_setting', false, 'setup_complete' ) ) {
			?>


			<tr>
				<th><label><?php esc_html_e( 'Language', 'product-feed-woocommerce' ); ?></label></th>
				<td>
					<select name="wt_pf_export_post_language" id="wt_pf_export_post_language">
						<?php
						/**
						 * Filter the query arguments for a request.
						 *
						 * Enables adding extra arguments or setting defaults for a post
						 * collection request.
						 *
						 * @since 1.0.0
						 *
						 * @param array           $mapping_fields    Mapping fields.
						 * @param string          $this->to_export    Export post type.
						 * @param array           $form_data_mapping_fields    Form Mapping fields.
						 */
						$site_languages = apply_filters( 'wpml_active_languages', null, 'orderby=id&order=desc' );
						$langs = array( '' => array( 'native_name' => _x( 'All', 'setting option', 'product-feed-woocommerce' ) ) ) + $site_languages;
						foreach ( $langs as $key => $value ) {
							?>
							<option value="<?php echo esc_html( $key ); ?>" <?php echo ( $item_lang == $key ? 'selected' : '' ); ?>><?php echo esc_html( $value['native_name'] ); ?></option>
							<?php
						}
						?>
					</select>
					<span class="wt-pf_form_help"><?php esc_html_e( 'Choose feed language', 'product-feed-woocommerce' ); ?></span>
				</td>
				<td></td>
			</tr>


		<?php } ?>


		<?php
		$multi_currency = false;

		if ( class_exists( 'WOOMULTI_CURRENCY_F' ) ) {
			$currency_list = array();
			$wcf_settings = WOOMULTI_CURRENCY_F_Data::get_ins();
			$wcf_currencies = $wcf_settings->get_list_currencies();
			foreach ( $wcf_currencies as $currency_key => $currency_name ) {
				$currency_list[ $currency_key ] = $currency_key;
			}
			$multi_currency = true;
		}

		if ( class_exists( 'WCML_Multi_Currency' ) && class_exists( 'woocommerce' ) ) {
			$wcml_mc = new WCML_Multi_Currency();
			$currency_list = $wcml_mc->get_currencies( true );
			$multi_currency = true;
		}

		if ( class_exists( 'WC_Aelia_CurrencySwitcher' ) ) {
			$currency_list = array();
			$settings_controller = WC_Aelia_CurrencySwitcher::settings();
			$enabled_currencies = $settings_controller->get_enabled_currencies();
			foreach ( $enabled_currencies as $currency_key => $currency_name ) {
				$currency_list[ $currency_name ] = $currency_name;
			}
				$multi_currency = true;
		}

		if ( $multi_currency ) {
			?>
			<tr>
				<th><label><?php esc_html_e( 'Currency', 'product-feed-woocommerce' ); ?></label></th>
				<td>
					<select name="wt_pf_export_post_currency" id="wt_pf_export_post_currency">
						<?php
						foreach ( $currency_list as $key => $value ) {
							?>
							<option value="<?php echo esc_html( $key ); ?>" <?php echo ( $item_currency == $key ? 'selected' : '' ); ?>><?php echo esc_html( $key ); ?></option>
							<?php
						}
						?>
					</select>
					<span class="wt-pf_form_help"><?php esc_html_e( 'Choose currency', 'product-feed-woocommerce' ); ?></span>
				</td>
				<td></td>
			</tr>
			<?php
		}
		?>

		<?php
		$vendor_active  = false;
		if ( is_plugin_active( 'wc-vendors/class-wc-vendors.php' ) ) {
			$args = array(
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key' => '_wcv_vendor_status',
						'value' => 'active',
						'compare' => '=',
					),
				),
			);
			$users = get_users( $args );
			$vendor_active = 1;
		}


		// If dokan active.
		if ( is_plugin_active( 'dokan-lite/dokan.php' ) ) {

			$args = array(
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key' => 'dokan_enable_selling',
						'value' => 'yes',
						'compare' => '=',
					),
				),
			);
			$users = get_users( $args );
			$vendor_active = 1;
		}

		?>
			<?php if ( $vendor_active ) { ?>
			<tr>
				<th><label><?php esc_html_e( 'Vendor', 'product-feed-woocommerce' ); ?></label></th>
				<td>
					<select name="wt_pf_export_post_author" id="wt_pf_export_post_author" multiple="true" class="wc-enhanced-select">
						<?php
						$item_author = is_scalar( $item_author ) ? explode( ',', $item_author ) : $item_author;
						if ( is_array( $item_author ) && 1 === count( $item_author ) && '' === $item_author[0] ) {
							$item_author = array();
						}

						?>
						<option value="" <?php echo esc_attr( empty( $item_author ) ) ? 'selected' : ''; ?>> <?php esc_html_e( 'All', 'product-feed-woocommerce' ); ?></option>
						<?php

						foreach ( $users as $user ) {
							?>
							<option value="<?php echo esc_html( $user->ID ); ?>" <?php echo ( in_array( $user->ID, $item_author ) ) ? 'selected' : ''; ?>><?php echo esc_html( $user->display_name ); ?></option>
							<?php
						}
						?>
					</select>
					<span class="wt-pf_form_help"><?php esc_html_e( 'Choose a vendor', 'product-feed-woocommerce' ); ?></span>
				</td>
				<td></td>
			</tr>


		<?php } ?> 
			
		<tr class="wt-pfd-settings-header">
			<th colspan="3"><label><?php esc_html_e( 'Automation', 'product-feed-woocommerce' ); ?></label></th>
		</tr>
		<tr><td></td></tr>            

		<tr>
			<th><label><?php esc_html_e( 'Auto-refresh interval', 'product-feed-woocommerce' ); ?>
					<span class="dashicons dashicons-editor-help wt-pf-tips" 
						  data-wt-pf-tip="
						  <span class='wt_pf_tooltip_span'><?php echo wp_kses_post( sprintf( /* translators: 1: html b. 2: html b close. */ __( ' Choose a suitable interval for refreshing the feed. Choose %1$s No Refresh %2$s to disable auto-refresh for the feed.', 'product-feed-woocommerce' ), '<b>', '</b>' ) ); ?></span><br />
						  ">			
					</span>

				</label></th>
			<td>
				<?php
				/**
				 * Filter the query arguments for a request.
				 *
				 * Enables adding extra arguments or setting defaults for a post
				 * collection request.
				 *
				 * @since 1.0.0
				 *
				 * @param array           $mapping_fields    Mapping fields.
				 * @param string          $this->to_export    Export post type.
				 * @param array           $form_data_mapping_fields    Form Mapping fields.
				 */
				$regenerate_intervals = apply_filters(
					'wt_pf_catalog_regenerate_interval',
					array(
						'hourly' => __( 'Hourly', 'product-feed-woocommerce' ),
						'daily' => __( 'Daily', 'product-feed-woocommerce' ),
						'weekly' => __( 'Weekly', 'product-feed-woocommerce' ),
						'monthly' => __( 'Monthly', 'product-feed-woocommerce' ),
						'12hour' => __( 'Every 12 hours', 'product-feed-woocommerce' ),
						'6hour' => __( 'Every 6 hours', 'product-feed-woocommerce' ),
						'manual' => __( 'No Refresh', 'product-feed-woocommerce' ),
					)
				);
				?>
				<select name="wt_pf_export_catalog_interval" id="wt_pf_export_catalog_interval">
					<?php
					foreach ( $regenerate_intervals as $key => $value ) {
						?>
						<option value="<?php echo esc_html( $key ); ?>" <?php echo ( $item_gen_interval == $key ? 'selected' : '' ); ?>><?php echo esc_html( $value ); ?></option>
						<?php
					}
					?>
				</select>
				<span class="wt-pf_form_help"><?php esc_html_e( 'Choose an interval to auto-refresh the feed', 'product-feed-woocommerce' ); ?></span>
			</td>
			<td></td>
		</tr>
		<tr class="wt_feed_schedule_options wt_feed_schedule_options_days" style="display:none;">
			<th><label style="margin-left:10px;"><?php esc_html_e( 'Choose day', 'product-feed-woocommerce' ); ?>								
				</label></th>
			<td>                            
				<select name="wt_pf_schedule_cron_day" id="wt_pf_schedule_cron_day" >
					<?php
					$days = array( 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' );
					foreach ( $days as $day ) {
						$day_vl = strtolower( $day );
						?>
						<option value="<?php echo esc_attr( $day_vl ); ?>" <?php echo ( $item_gen_cron_day == $day_vl ? 'selected' : '' ); ?> ><?php echo esc_html( $day, 'product-feed-woocommerce' ); ?></option>                                        
						<?php
					}
					?>
				</select>				
			</td>
			<td></td>
		</tr> 

		<tr class="wt_feed_schedule_options wt_feed_schedule_options_dayofmonth" style="display:none;">
			<th><label style="margin-left:10px;"><?php esc_html_e( 'Day of the Month', 'product-feed-woocommerce' ); ?>								
				</label></th>
			<td>                                    			
				<select name="wt_pf_cron_interval_date" id="wt_pf_cron_interval_date">
					<?php
					for ( $i = 1; $i <= 28; $i++ ) {
						?>
						<option value="<?php echo esc_html( $i ); ?>" <?php echo ( $item_gen_cron_date == $i ? 'selected' : '' ); ?>><?php echo esc_html( $i ); ?></option>
						<?php
					}
					?>
					<option value="last_day"><?php esc_html_e( 'Last day', 'product-feed-woocommerce' ); ?></option>
				</select>				
			</td>
			<td></td>
		</tr>                

		<tr class="wt_feed_schedule_options wt_feed_schedule_options_time" style="display:none;">
			<th><label style="margin-left:10px;"><?php esc_html_e( 'Time', 'product-feed-woocommerce' ); ?>								
				</label></th>
			<td>
				<div class="wt_pf_schedule_now_interval_sub_block wt_pf_schedule_starttime_block">                            
					<div style="float:left;margin-right:5px;">
						<input  type="number" step="1" min="1" max="12" name="wt_pf_cron_start_val" id="wt_pf_cron_start_val" value="<?php echo esc_html( $item_gen_cron_start_val ); ?>" style="width:75px;padding:5px;" />
						<span class="wt-pf_form_help" style="display:block; margin-top: 1px"><?php esc_html_e( 'Hour', 'product-feed-woocommerce' ); ?></span>
					</div>
					<div style="float:left;">                                    
						<input type="number" step="1" min="0" max="59" name="wt_pf_cron_start_val_min" id="wt_pf_cron_start_val_min" value="<?php echo esc_html( $item_gen_cron_start_val_min ); ?>" onchange="if (parseInt(this.value, 10) < 10)
												this.value = '0' + this.value;" style="width:75px;padding:5px;" />
						<span class="wt-pf_form_help" style="display:block;  margin-top: 1px"><?php esc_html_e( 'Minute', 'product-feed-woocommerce' ); ?></span>
					</div>
					<div style="float:left;padding-left:5px;">
						<select name="wt_pf_cron_start_ampm_val" id="wt_pf_cron_start_ampm_val" style="width:75px;">
							<?php
							$am_pm = array( 'AM', 'PM' );
							foreach ( $am_pm as $apvl ) {
								?>
								<option value="<?php echo esc_html( strtolower( $apvl ) ); ?>" <?php echo ( strtolower( $apvl ) == $item_gen_cron_ampm ? 'selected' : '' ); ?> ><?php echo esc_html( $apvl ); ?></option>
								<?php
							}
							?>
						</select>
					</div>
				</div>
			</td>
			<td></td>
		</tr>


		<tr>
			<th><label><?php esc_html_e( 'Cron Type', 'product-feed-woocommerce' ); ?>
					<span class="dashicons wtdashicons-editor-help wt-pf-tips" 
						  data-wt-pf-tip="
						  <span class='wt_pf_tooltip_span'><?php echo wp_kses_post( sprintf( /* translators: 1: html b. 2: html b close. */ __( ' Choose a suitable interval for refreshing the feed. Choose %1$s Manual %2$s to disable auto-refresh for the feed.', 'product-feed-woocommerce' ), '<b>', '</b>' ) ); ?></span><br />
						  ">			
					</span>

				</label></th>
			<td>
				<?php
				/**
				 * Filter the query arguments for a request.
				 *
				 * Enables adding extra arguments or setting defaults for a post
				 * collection request.
				 *
				 * @since 1.0.0
				 *
				 * @param array           $mapping_fields    Mapping fields.
				 * @param string          $this->to_export    Export post type.
				 * @param array           $form_data_mapping_fields    Form Mapping fields.
				 */
				$cron_types = apply_filters(
					'wt_pf_catalog_cron_type',
					array(
						'wordpress_cron' => __( 'WordPress cron', 'product-feed-woocommerce' ),
						'server_cron' => __( 'Server cron', 'product-feed-woocommerce' ),
					)
				);
				?>
				<div class="wt_form_radio_block">
					<?php

					foreach ( $cron_types as $rad_vl => $rad_label ) {
						?>
					<input type="radio" id="<?php echo esc_html( 'wt_pf_' . $rad_vl ); ?>" name="wt_pf_catalog_cron_type" value="<?php echo esc_html( $rad_vl ); ?>" <?php echo ( $item_gen_cron_type == $rad_vl ) ? ' checked="checked"' : ''; ?> /> <?php echo esc_html( $rad_label ); ?>
					&nbsp;&nbsp;
						<?php
					}
					?>
				</div>
				<span class="wt-pf_form_help"><?php esc_html_e( 'Choose a cron type for the feed refresh', 'product-feed-woocommerce' ); ?></span>
			</td>
			<td></td>
		</tr>

		
		<tr class="wt-pfd-settings-header">
			<th colspan="3"><label><?php esc_html_e( 'Filter', 'product-feed-woocommerce' ); ?></label></th>
		</tr>
		<tr>
			<td></td>
		</tr>          
		
		<tr class="wt-feed-filter-section">
			<th><label><?php esc_html_e( 'Categories', 'product-feed-woocommerce' ); ?></label>
			</th>
			<td>
				<?php
				$cat_filter_type = array(
					'include_cat' => __( 'Include', 'product-feed-woocommerce' ),
					'exclude_cat' => __( 'Exclude', 'product-feed-woocommerce' ),
				);
				?>
				<select name="wt_pf_export_cat_filter_type" id="wt_pf_export_cat_filter_type">
				<?php
				foreach ( $cat_filter_type as $key => $value ) {
					?>
						<option value="<?php echo esc_html( $key ); ?>" <?php echo ( $item_cat_filter_type == $key ? 'selected' : '' ); ?>><?php echo esc_html( $value ); ?></option>
						<?php
				}
				?>
				</select>
				<span class="wt-pf_form_help"><?php esc_html_e( 'Choose a category filter', 'product-feed-woocommerce' ); ?></span>
			</td>                
			<td class="wt-feed-filter-section-td" style="padding-top: 20px;">
				<select name="wt_pf_inc_exc_category" id="wt_pf_inc_exc_category" class="wc-enhanced-select" multiple="multiple" data-placeholder ="<?php esc_html_e( 'Select product category&hellip;', 'product-feed-woocommerce' ); ?>" >
<?php
$product_categories = Webtoffee_Product_Feed_Sync_Pro_Common_Helper::get_product_categories_sluged();
foreach ( $product_categories as $category_id => $category_name ) {
	?>


						<option value="<?php echo esc_html( $category_id ); ?>" <?php echo ( in_array( $category_id, $inc_exc_cat ) ? 'selected' : '' ); ?> ><?php echo esc_attr( $category_name ); ?></option>								

	<?php
}
?>

				</select>
				<span class="wt-pf_form_help"><?php esc_html_e( 'Search and add one or more categories.', 'product-feed-woocommerce' ); ?></span>
			</td>
		</tr>



		<tr>
			<th><label><?php esc_html_e( 'Exclude products', 'product-feed-woocommerce' ); ?></label></th>
			<td>
				<select name="wt_pf_exclude_products" id="wt_pf_exclude_products" class="wc-product-search" multiple="multiple" data-placeholder ="<?php esc_html_e( 'Search for a product &hellip;', 'product-feed-woocommerce' ); ?>" >
<?php
foreach ( $excl_prods as $single_vl ) {
	$single_vl = (int) $single_vl;
	if ( $single_vl > 0 ) {
		$product = wc_get_product( $single_vl );
		
		// Get proper title for variations
		if ( $product && $product->is_type( 'variation' ) ) {
			// For variations, use get_name() which includes variation attributes
			$display_title = $product->get_name();
		} else {
			// For regular products, use get_title()
			$display_title = $product->get_title();
		}
		
		// Add product ID to the display title
		$display_title .= ' (#' . $single_vl . ')';
		?>
		<option value="<?php echo esc_html( $single_vl ); ?>" selected><?php echo esc_html( $display_title ); ?></option>
		<?php
	}
}
?>
				</select>
				<span class="wt-pf_form_help"><?php esc_html_e( 'Search and add one or more products to be excluded from the feed.', 'product-feed-woocommerce' ); ?></span>
			</td>
			<td></td>
		</tr>


		<tr>
			<th><label><?php esc_html_e( 'Exclude out-of-stock product', 'product-feed-woocommerce' ); ?></label></th>
			<td>
				<input type="checkbox" name="wt_pf_exclude_outofstock" id="wt_pf_exclude_outofstock" <?php echo ( 1 == $item_outofstock ) ? ' checked="checked"' : ''; ?> >Enable
				<span class="wt-pf_form_help"><?php esc_html_e( 'Enable to exclude out of stock products from the feed.', 'product-feed-woocommerce' ); ?></span>
			</td>
			<td></td>
		</tr>

		<tr>
			<th><label><?php esc_html_e( 'Only include default product variation', 'product-feed-woocommerce' ); ?>

					<span class="dashicons dashicons-editor-help wt-pf-tips" 
						  data-wt-pf-tip="
						  <span class='wt_pf_tooltip_span'><?php esc_html_e( 'The default product variation can be configured from the product edit page.', 'product-feed-woocommerce' ); ?></span><br />">			
					</span>
				</label></th>
			<td>
				<input type="checkbox" name="wt_pf_include_parent_only" id="wt_pf_include_parent_only" <?php echo ( 1 == $item_inc_parentonly ) ? ' checked="checked"' : ''; ?> >Enable
				<span class="wt-pf_form_help"><?php esc_html_e( 'Enable to only include default product variation in the feed.', 'product-feed-woocommerce' ); ?></span>
			</td>
			<td></td>
		</tr>


		<tr>
			<th><label><?php esc_html_e( 'Product types', 'product-feed-woocommerce' ); ?>

					<span class="dashicons dashicons-editor-help wt-pf-tips" 
						  data-wt-pf-tip="
						  <span class='wt_pf_tooltip_span'><?php esc_html_e( 'The product types specified here will be included in the feed.', 'product-feed-woocommerce' ); ?></span><br />">			
					</span>

				</label></th>
			<td>
				<select name="wt_pf_product_types" id="wt_pf_product_types" class="wc-enhanced-select" multiple="multiple" data-placeholder ="<?php esc_html_e( 'Select product type&hellip;', 'product-feed-woocommerce' ); ?>" >
<?php
$product_types = function_exists( 'wc_get_product_types' ) ? wc_get_product_types() : array();
if ( ! empty( $product_types ) ) {
	$product_types['variation'] = __( 'Variations', 'product-feed-woocommerce' );
	// Remove variable product type from the list only if channel is not Google Product Reviews
	if ( $item_type !== 'google_product_reviews' ) {
		if ( isset( $product_types['variable'] ) ) {
			unset( $product_types['variable'] );
		}
	} else {
		if ( isset( $product_types['variation'] ) ) {
			unset( $product_types['variation'] );
		}
	}
}
foreach ( $product_types as $key => $product_type ) {
	?>
						<option value="<?php echo esc_html( $key ); ?>" <?php echo ( in_array( $key, $item_product_type ) ? 'selected' : '' ); ?> ><?php echo esc_attr( $product_type ); ?></option>								
						<?php
}
?>

				</select>
				<span class="wt-pf_form_help"><?php esc_html_e( 'Choose product types that need to be included in the feed.', 'product-feed-woocommerce' ); ?></span>
			</td>
			<td></td>
		</tr>

	</table>
	</form>
	<br/>
</div>
