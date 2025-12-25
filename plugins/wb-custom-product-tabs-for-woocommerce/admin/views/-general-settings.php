<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wb_cptb_content">
	<h2><?php esc_html_e( 'General Settings', 'wb-custom-product-tabs-for-woocommerce' ); ?></h2>
	<form method="post" action="options.php">
		<?php settings_fields( 'wb_cptb_custom_tab_settings_group' ); ?>
		<?php do_settings_sections( 'wb_cptb_custom_tab_settings_group' ); ?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">
					<?php esc_html_e( 'Default Tab Position', 'wb-custom-product-tabs-for-woocommerce' ); ?>
					<p><a href="https://webbuilder143.com/how-to-arrange-woocommerce-custom-product-tabs/?utm_source=plugin&utm_medium=settings&utm_campaign=default-position&utm_content=positioning" target="_blank">
						<?php esc_html_e( 'Learn more about arranging tabs', 'wb-custom-product-tabs-for-woocommerce' ); ?> <span class="dashicons dashicons-external" style="text-decoration:none;"></span>
					</a></p>
					<p class="description">
						<?php esc_html_e( 'This value will be auto-filled into the tab position field when creating a new tab.', 'wb-custom-product-tabs-for-woocommerce' ); ?> 
					</p>
				</th>
				<td>
					<label>
						<input type="radio" name="wb_cptb_default_tab_position" value="1" 
							<?php checked( get_option( 'wb_cptb_default_tab_position', 1 ), 1 ); ?> onclick="document.querySelector('[name=wb_cptb_default_tab_position][type=text]').value='1';" />
						<?php esc_html_e( 'Before description tab', 'wb-custom-product-tabs-for-woocommerce' ); ?>
					</label><br><br>
					<label>
						<input type="radio" name="wb_cptb_default_tab_position" value="11" 
							<?php checked( get_option( 'wb_cptb_default_tab_position' ), 11 ); ?> onclick="document.querySelector('[name=wb_cptb_default_tab_position][type=text]').value='11';" />
						<?php esc_html_e( 'In between Description and Additional Information', 'wb-custom-product-tabs-for-woocommerce' ); ?>
					</label><br><br>
					<label>
						<input type="radio" name="wb_cptb_default_tab_position" value="21" 
							<?php checked( get_option( 'wb_cptb_default_tab_position' ), 21 ); ?> onclick="document.querySelector('[name=wb_cptb_default_tab_position][type=text]').value='21';" />
						<?php esc_html_e( 'In between Additional Information and Reviews', 'wb-custom-product-tabs-for-woocommerce' ); ?>
					</label><br><br>
					<label>
						<input type="radio" name="wb_cptb_default_tab_position" value="31" 
							<?php checked( get_option( 'wb_cptb_default_tab_position' ), 31 ); ?> onclick="document.querySelector('[name=wb_cptb_default_tab_position][type=text]').value='31';" />
						<?php esc_html_e( 'After all default tabs', 'wb-custom-product-tabs-for-woocommerce' ); ?>
					</label><br><br>
					<input type="text" name="wb_cptb_default_tab_position" value="<?php echo esc_attr( get_option( 'wb_cptb_default_tab_position', '1' ) ); ?>" placeholder="<?php esc_attr_e( 'Custom value', 'wb-custom-product-tabs-for-woocommerce' ); ?>">
					<p class="description"><?php esc_html_e( 'Enter a custom position value or choose one of the options above.', 'wb-custom-product-tabs-for-woocommerce' ); ?></p>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'Hide Tab Heading Inside the Tab', 'wb-custom-product-tabs-for-woocommerce' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="wb_cptb_hide_tab_heading" value="1" 
							<?php checked( get_option( 'wb_cptb_hide_tab_heading', 0 ), 1 ); ?> />
						<?php esc_html_e( 'Yes', 'wb-custom-product-tabs-for-woocommerce' ); ?>
					</label>
				</td>
			</tr>

			<tr valign="top">
			    <th scope="row"><?php esc_html_e( 'Enable default tabs', 'wb-custom-product-tabs-for-woocommerce' ); ?></th>

			    <td>
			        <?php
			        $default_tabs_status = Wb_Custom_Product_Tabs_For_Woocommerce::get_default_woo_tab_status();
			        ?>

			        <label style="display:block; margin-bottom:6px;">
			            <input type="checkbox" name="wb_cptb_enable_default_tabs[]" value="description"
			                <?php checked( in_array( 'description', $default_tabs_status, true ) ); ?> />
			            <?php esc_html_e( 'Description', 'wb-custom-product-tabs-for-woocommerce' ); ?>
			        </label>

			        <label style="display:block; margin-bottom:6px;">
			            <input type="checkbox" name="wb_cptb_enable_default_tabs[]" value="additional_information"
			                <?php checked( in_array( 'additional_information', $default_tabs_status, true ) ); ?> />
			            <?php esc_html_e( 'Additional Information', 'wb-custom-product-tabs-for-woocommerce' ); ?>
			        </label>

			        <label style="display:block; margin-bottom:6px;">
			            <input type="checkbox" name="wb_cptb_enable_default_tabs[]" value="reviews"
			                <?php checked( in_array( 'reviews', $default_tabs_status, true ) ); ?> />
			            <?php esc_html_e( 'Reviews', 'wb-custom-product-tabs-for-woocommerce' ); ?>
			        </label>
			    </td>
			</tr>


			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'Default Behavior of Global Tabs', 'wb-custom-product-tabs-for-woocommerce' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="wb_cptb_global_tabs_behavior" value="1" 
							<?php checked( get_option( 'wb_cptb_global_tabs_behavior', 1 ), 1 ); ?> />
						<?php esc_html_e( 'Hide from all products if not assigned with any product, category, tags, brands, etc.', 'wb-custom-product-tabs-for-woocommerce' ); ?>
					</label>
				</td>
			</tr>

			<tr valign="top">
			    <th scope="row"><?php esc_html_e( 'Use custom content rendering', 'wb-custom-product-tabs-for-woocommerce' ); ?></th>
			    <td>
			        <label>
			            <input type="checkbox" name="wb_cptb_use_custom_the_content" value="1"
			                <?php checked( get_option( 'wb_cptb_use_custom_the_content', 1 ) ); ?> />
			            <?php esc_html_e( 'Enable safe, custom rendering for tab content.', 'wb-custom-product-tabs-for-woocommerce' ); ?>
			        </label>
			        <p class="description">
			            <?php esc_html_e( 'This setting controls how tab content is processed and displayed. Enabling it will use a custom rendering method that safely supports shortcodes and blocks, and avoids conflicts with page builders and other plugins.', 'wb-custom-product-tabs-for-woocommerce' ); ?>
			        </p>
			        <p class="description">
			            <?php esc_html_e( 'If disabled, the plugin will use WordPress\'s default `the_content` filter, which allows full compatibility with third-party plugins, but may occasionally cause layout issues or conflicts.', 'wb-custom-product-tabs-for-woocommerce' ); ?>
			        </p>
			        <p class="description">
			            <?php esc_html_e( 'If both modes cause issues, you can enable a legacy rendering method using the filter: `wb_cptb_use_legacy_tab_content_processing`. This exists for backward compatibility with earlier versions of the plugin.', 'wb-custom-product-tabs-for-woocommerce' ); ?>
			        </p>
			    </td>
			</tr>
			<tr>
				<td></td>
				<td>
					<?php submit_button(); ?>
				</td>
			</tr>
		</table>
	</form>
</div>