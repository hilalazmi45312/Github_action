<?php
if (!defined('ABSPATH')) {
    exit;
}

// Expect $current_code to be set by the controller
$code = isset($current_code) ? $current_code : get_option(TradeInController::OPTION_KEY, '');

?>
<div class="wrap">
    <h1><?php echo esc_html__('AutoSON Settings', 'senheng-core'); ?></h1>

    <?php if (isset($_GET['message']) && $_GET['message'] === 'updated') : ?>
        <div class="updated notice is-dismissible">
            <p><?php echo esc_html__('Trade-In code updated.', 'senheng-core'); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field('senheng_trade_in_settings'); ?>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="trade_in_code"><?php echo esc_html__('Trade-In Code', 'senheng-core'); ?></label>
                    </th>
                    <td>
                        <input type="text"
                               id="trade_in_code"
                               name="trade_in_code"
                               value="<?php echo esc_attr($code); ?>"
                               class="regular-text"
                               placeholder="<?php echo esc_attr__('e.g. TRD001', 'senheng-core'); ?>" />
                        <p class="description">
                            <?php echo esc_html__('This code will be sent with orders that have Trade-In = Yes at checkout.', 'senheng-core'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>

        <?php submit_button(__('Save Changes', 'senheng-core')); ?>
    </form>
</div>