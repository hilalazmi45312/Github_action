
<div class="wcabe-top-bar-container">
    <div class="wcabe-title"><?php esc_html_e( "WooCommerce Advanced Bulk Edit", "woocommerce-advbulkedit" ); ?></div>
</div>

<div class="wrap boxed-layout-wcabe">

  <p>&nbsp;</p>

  <h3><?php esc_html_e( "General Settings", "woocommerce-advbulkedit" ); ?></h3>

  <a href="<?php echo admin_url( 'edit.php?post_type=product&page=advanced_bulk_edit' ); ?>"><?php esc_html_e( "< back", "woocommerce-advbulkedit" ); ?></a>

<?php
if (!wcabe_is_current_user_admin()) {
?>
	<p><?php esc_html_e( "Only admins can access this page.", "woocommerce-advbulkedit" ); ?></p>
<?php
	return;
}

$settings = get_option('w3exabe_settings');
$wcabe_license_key = $settings['license_key'] ?? '';

$connection_log = wcabe_connection_log_read();
?>



    <?php do_action('wcabe_demo_stats'); ?>

    <div class="wcabe-general-settings-section">
        <form method="post" action="<?php echo admin_url( 'edit.php' ); ?>">
          <h3><?php esc_html_e( "License Key (Purchase Code)", "woocommerce-advbulkedit" ); ?></h3>
          <p>
            <input type="text" id="wcabe_license_key" name="wcabe_license_key" value="<?php echo $wcabe_license_key; ?>" class="regular-text wcabe-license-key-input" />
            <input type="submit" name="wcabe-submit-settings" id="wcabe-submit-settings" class="button-primary-wcabe" value="Save">
          </p>
          <p>
              <?php esc_html_e( "The license key will allow you to use plugin auto-updates. If you don't have one, please purchase it ", "woocommerce-advbulkedit" ); ?>
              <a href="https://codecanyon.net/item/woocommerce-advanced-bulk-edit/8011417" target="_blank"><?php esc_html_e( "here", "woocommerce-advbulkedit" ); ?></a>.
          </p>
          <p>
              <?php esc_html_e( "You can find the purchase code in your CodeCanyon account in Downloads section. Click on the Download button next to WooCommerce Advanced Bulk Edit and from the drop-down menu select the text version of the purchase code doc. More detailed info ", "woocommerce-advbulkedit" ); ?>
              <a href="https://wpmelon.com/r/wcabe-purchase-code-info" target="_blank"><?php esc_html_e( "here", "woocommerce-advbulkedit" ); ?></a>.
          </p>
        </form>
    </div>

    <div class="wcabe-general-settings-section">
        <h3><?php esc_html_e( "Feedback", "woocommerce-advbulkedit" ); ?></h3>
        <p>
            <?php echo wp_kses(
                __( "Your feedback helps us improve WooCommerce Advanced Bulk Edit (WCABE) and build the features that matter most to you. Whether you have a <strong>suggestion</strong>, a <strong>bug to report</strong>, or a <strong>feature</strong> you'd love to see added — we're listening.", "woocommerce-advbulkedit" ),
                array( 'strong' => array() )
            ); ?>
        </p>
        <p>
            <a class="button-primary-wcabe" href="https://wpmelon.com/r/feedback" title="Give Feedback" target="_blank">
                <button class="button-primary-wcabe" value="Check Connection"><?php esc_html_e( "Give Feedback", "woocommerce-advbulkedit" ); ?></button>
            </a>
        </p>
    </div>


  <div class="wcabe-general-settings-section">
    <?php
    // Load config loader to check override status
    if (file_exists(WCABE_PLUGIN_PATH . 'includes/class-config-loader.php')) {
        require_once WCABE_PLUGIN_PATH . 'includes/class-config-loader.php';
    }

    // Get current failsafe filtering status
    $failsafe_enabled = false; // Default
    $is_overridden = false;
    $config_file_exists = false;

    if (class_exists('W3ExABulkEdit_ConfigLoader')) {
        $failsafe_enabled = W3ExABulkEdit_ConfigLoader::is_failsafe_filtering_enabled();
        $override_info = W3ExABulkEdit_ConfigLoader::get_override_info();
        $config_file_exists = $override_info['has_config_file'];
        $is_overridden = $config_file_exists && in_array('failsafe_filtering_enabled', $override_info['overridden_settings']);
    } else {
        // Fallback to database settings if config loader not available
        $settings = get_option('w3exabe_settings', array());
        $failsafe_enabled = isset($settings['failsafe_filtering_enabled']) ? $settings['failsafe_filtering_enabled'] : false;
    }
    ?>
    <form method="post" action="<?php echo admin_url( 'edit.php' ); ?>">
      <h3><?php esc_html_e( "Filters AND Mode", "woocommerce-advbulkedit" ); ?> <span class="wcabe-beta-badge" title="<?php esc_attr_e( 'Experimental feature. We recommend testing on a staging site before using on production.', 'woocommerce-advbulkedit' ); ?>">BETA</span></h3>
      <p><?php esc_html_e( "Control how multiple filters are combined when searching products.", "woocommerce-advbulkedit" ); ?></p>
      <p><?php esc_html_e( " - When unchecked (default): Filters use OR logic - a product matches if it meets any selected filter.", "woocommerce-advbulkedit" ); ?></p>
      <p><?php esc_html_e( " - When checked: Filters use AND logic - a product matches only if it meets all selected filters.", "woocommerce-advbulkedit" ); ?></p>

      <?php if ($is_overridden): ?>
      <div class="notice notice-info inline" style="margin: 10px 0;">
        <p>
          <strong><?php esc_html_e( "Note:", "woocommerce-advbulkedit" ); ?></strong>
          <?php esc_html_e( "This setting is currently overridden by the configuration file at:", "woocommerce-advbulkedit" ); ?>
          <code>config/wcabe-config.php</code>
        </p>
      </div>
      <?php endif; ?>

      <p>
        <label>
          <input type="checkbox" name="wcabe_failsafe_filtering_enabled" id="wcabe_failsafe_filtering_enabled" value="1"
                 <?php checked($failsafe_enabled, true); ?>
                 <?php echo $is_overridden ? 'disabled' : ''; ?>>
          <?php esc_html_e( "Enable AND Filtering", "woocommerce-advbulkedit" ); ?>
          <?php if ($is_overridden): ?>
            <span style="color: #666;">(<?php echo $failsafe_enabled ? esc_html__( "Enabled via config", "woocommerce-advbulkedit" ) : esc_html__( "Disabled via config", "woocommerce-advbulkedit" ); ?>)</span>
          <?php endif; ?>
        </label>
      </p>
      <p class="description">
        <p><?php esc_html_e( "This may reduce the number of matching results but provides more precise filtering.", "woocommerce-advbulkedit" ); ?></p>

        </p>
      <?php if (!$is_overridden): ?>
      <p>
        <input type="submit" name="wcabe-submit-failsafe-settings" id="wcabe-submit-failsafe-settings" class="button-primary-wcabe" value="Save Settings">
      </p>
      <?php endif; ?>
    </form>
  </div>

  <div class="wcabe-general-settings-section">
    <form method="post" action="<?php echo admin_url( 'edit.php' ); ?>">
      <h3><?php esc_html_e( "Check Connection With Updates Server", "woocommerce-advbulkedit" ); ?></h3>
      <p>
        <?php esc_html_e( "If you have issues getting the plugin updates, click the button below to check the connection with the updates server and get some error info, which might help the support team to ", "woocommerce-advbulkedit" ); ?>
        <a href="https://wpmelon.com/r/support" target="_blank"><?php esc_html_e( "resolve the issue.", "woocommerce-advbulkedit" ); ?></a>
      </p>
      <p>
        <input type="submit" name="wcabe-submit-settings-connection-test" id="wcabe-submit-settings-connection-test" class="button-primary-wcabe" value="Check Connection">
        <label style="margin-left: 15px;">
          <input type="checkbox" name="wcabe-log-full-response" id="wcabe-log-full-response" value="1">
          <?php esc_html_e( "Log full response", "woocommerce-advbulkedit" ); ?>
        </label>
      </p>
      <p>
        <?php 
        // Check if the log contains the full response
        $has_full_response = strpos($connection_log, '=== Full Response (Raw JSON) ===') !== false;
        $textarea_style = $has_full_response ? ' style="height: 675px;"' : '';
        ?>
        <textarea readonly class="check-connection-terminal"<?php echo $textarea_style; ?>><?php echo $connection_log; ?></textarea>
      </p>
      <p>
        <input type="submit" name="wcabe-submit-settings-connection-clear-log" id="wcabe-submit-settings-connection-test" class="button-wcabe" value="Clear Log">
      </p>
    </form>
  </div>

  <div class="wcabe-general-settings-section">
      <h3><?php esc_html_e( "Recommended Plugins", "woocommerce-advbulkedit" ); ?></h3>
      <p><?php esc_html_e( "If you love using WooCommerce Advanced Bulk Edit, you might also find our other plugins incredibly useful!", "woocommerce-advbulkedit" ); ?></p>
      <div class="box-container">
          <div class="flex-container">
              <div class="image-column">
                  <img src="<?php echo esc_attr(WCABE_PLUGIN_URL) ?>images/fomopop-thumb-80x80.png" alt="FomoPop Marketing Thumbnail">
              </div>
              <div class="content-column">
                  <h4 class="content-title"><?php esc_html_e( "FomoPop Marketing", "woocommerce-advbulkedit" ); ?></h4>
                  <p class="content-description"><?php esc_html_e( "FomoPop Marketing is a social proof plugin to display nicely designed pop-up notifications on your WooCommerce store. Showing that other people are buying gives site visitors more confidence. This will motivate visitors to make a purchase and result in increasing sales instantly.", "woocommerce-advbulkedit" ); ?></p>
              </div>
              <div class="button-column">
                  <a href="https://wpmelon.com/r/fomopop-marketing" target="_blank">
                      <button class="button-wcabe button-highlight-wcabe"><?php esc_html_e( "Get FomoPop Marketing Now", "woocommerce-advbulkedit" ); ?></button>
                  </a>
              </div>
          </div>
      </div>
  </div>

</div>
