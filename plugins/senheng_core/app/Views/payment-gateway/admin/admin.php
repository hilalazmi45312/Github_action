<?php
/**
 * Payment Gateway Admin Settings View
 * 
 * @package Senheng_Core
 * @version 1.0.0
 * @author Senheng Core Team
 */

// Get current settings
$ipay88_settings = get_option('senheng_ipay88_settings', []);
$enabled = isset($ipay88_settings['enabled']) ? $ipay88_settings['enabled'] : 'no';
$title = isset($ipay88_settings['title']) ? $ipay88_settings['title'] : 'iPay88';
$description = isset($ipay88_settings['description']) ? $ipay88_settings['description'] : 'Secure payment via iPay88. Pay with credit card, debit card, or online banking.';
$instructions = isset($ipay88_settings['instructions']) ? $ipay88_settings['instructions'] : 'You will be redirected to iPay88 to complete your payment securely.';
$merchant_key = isset($ipay88_settings['merchant_key']) ? $ipay88_settings['merchant_key'] : '';
$merchant_code = isset($ipay88_settings['merchant_code']) ? $ipay88_settings['merchant_code'] : '';
$environment = isset($ipay88_settings['environment']) ? $ipay88_settings['environment'] : 'test';
$response_url = isset($ipay88_settings['response_url']) ? $ipay88_settings['response_url'] : home_url('/?wc-api=WC_Gateway_Ipay88');
$backend_url = isset($ipay88_settings['backend_url']) ? $ipay88_settings['backend_url'] : home_url('/?wc-api=WC_Gateway_Ipay88');

// Check if we're viewing a specific gateway configuration
$gateway_view = isset($_GET['gateway']) ? sanitize_text_field($_GET['gateway']) : '';
?>

<div class="wrap">
    <?php if (empty($gateway_view)): ?>
        <!-- Payment Providers List View -->
        <div class="payment-providers-header">
            <h2>Payment Options</h2>
            <div class="business-location">
                <span>Business location: <strong>Malaysia</strong></span>
            </div>
        </div>

        <div class="payment-providers-container">
            <!-- iPay88 Payment Gateway -->
            <div class="payment-provider-item" data-gateway="ipay88">
                <div class="payment-provider-handle">⋮⋮</div>
                <div class="payment-provider-icon">
                    <img src="<?php echo SENHENG_CORE_URL . 'assets/uploads/ipay88.webp';?>" alt="iPay88" class="provider-logo-img">
                </div>
                <div class="payment-provider-details">
                    <h3>iPay88
                    <p>Accept payments through iPay88 gateway for credit cards, debit cards, and local payment methods in Malaysia.</p>
                </div>
                <div class="payment-provider-actions">
                    <?php if ($enabled === 'yes'): ?>
                        <span class="status-enabled">Enabled</span>
                    <?php else: ?>
                        <span class="status-disabled">Disabled</span>
                    <?php endif; ?>
                    <a href="?page=payment-options&gateway=ipay88" class="button">Manage</a>
                </div>
                <div class="payment-provider-menu">⋮</div>
            </div>
        </div>

    <?php elseif ($gateway_view === 'ipay88'): ?>
        <!-- iPay88 Configuration View -->
        <div class="gateway-header">
            <a href="?page=payment-options" class="back-link">← Payment Options</a>
            <h1>iPay88 Configuration</h1>
        </div>

        <?php
        // Get messages for custom display
        $success_message = get_transient('senheng_payment_gateway_success');
        $error_message = get_transient('senheng_payment_gateway_error');
        
        if ($success_message) {
            delete_transient('senheng_payment_gateway_success');
            echo '<div id="senheng-success-notification" class="senheng-notification senheng-notification-success">
                    <div class="senheng-notification-content">
                        <div class="senheng-notification-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div class="senheng-notification-message">
                            <h4>Success!</h4>
                            <p>' . esc_html($success_message) . '</p>
                        </div>
                        <button class="senheng-notification-close" onclick="this.parentElement.parentElement.style.display=\'none\'">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                  </div>';
        }
        
        if ($error_message) {
            delete_transient('senheng_payment_gateway_error');
            echo '<div id="senheng-error-notification" class="senheng-notification senheng-notification-error">
                    <div class="senheng-notification-content">
                        <div class="senheng-notification-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 9V13M12 17H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div class="senheng-notification-message">
                            <h4>Error!</h4>
                            <p>' . esc_html($error_message) . '</p>
                        </div>
                        <button class="senheng-notification-close" onclick="this.parentElement.parentElement.style.display=\'none\'">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                  </div>';
        }
        ?>

        <div class="gateway-config-container">
            <div class="gateway-config-header">
                <h2>Enable and customise</h2>
                <p>Configure your iPay88 payment gateway and customize how it appears to customers.</p>
            </div>

            <form method="post" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" class="gateway-config-form">
                <?php wp_nonce_field('save_payment_gateway_settings', 'payment_gateway_nonce'); ?>
                
                <div class="config-section">
                    <div class="config-enable">
                        <label class="toggle-switch">
                            <input type="checkbox" id="ipay88_enabled" name="ipay88_enabled" value="1" <?php checked($enabled, 'yes'); ?>>
                            <span class="toggle-slider"></span>
                        </label>
                        <span class="toggle-label">Enable iPay88</span>
                    </div>

                    <div class="config-field">
                        <label for="ipay88_description"><strong>DESCRIPTION</strong></label>
                        <textarea id="ipay88_description" name="ipay88_description" rows="4" class="large-text"><?php echo esc_textarea($description); ?></textarea>
                        <p class="description">Payment method description that the customer will see during checkout.</p>
                    </div>
                </div>

                <hr>

                <div class="config-section">
                    <h3>Gateway Settings</h3>
                    
                    <div class="config-field">
                        <label for="ipay88_environment"><strong>Environment</strong></label>
                        <select id="ipay88_environment" name="ipay88_environment">
                            <option value="test" <?php selected($environment, 'test'); ?>>Test/Sandbox</option>
                            <option value="live" <?php selected($environment, 'live'); ?>>Live/Production</option>
                        </select>
                    </div>

                    <div class="config-field">
                        <label for="ipay88_merchant_code"><strong>Merchant Code</strong></label>
                        <input type="text" id="ipay88_merchant_code" name="ipay88_merchant_code" value="<?php echo esc_attr($merchant_code); ?>" class="regular-text">
                        <p class="description">Your iPay88 Merchant Code provided by iPay88.</p>
                    </div>

                    <div class="config-field">
                        <label for="ipay88_merchant_key"><strong>Merchant Key</strong></label>
                        <input type="password" id="ipay88_merchant_key" name="ipay88_merchant_key" value="<?php echo esc_attr($merchant_key); ?>" class="regular-text">
                        <p class="description">Your iPay88 Merchant Key provided by iPay88.</p>
                    </div>

                    <div class="config-field">
                        <label for="ipay88_response_url"><strong>Response URL</strong></label>
                        <input type="url" id="ipay88_response_url" name="ipay88_response_url" value="<?php echo esc_attr($response_url); ?>" class="large-text" readonly>
                        <p class="description">Configure this URL in your iPay88 merchant panel as the Response URL.</p>
                    </div>

                    <div class="config-field">
                        <label for="ipay88_backend_url"><strong>Backend URL</strong></label>
                        <input type="url" id="ipay88_backend_url" name="ipay88_backend_url" value="<?php echo esc_attr($backend_url); ?>" class="large-text" readonly>
                        <p class="description">Configure this URL in your iPay88 merchant panel as the Backend URL.</p>
                    </div>
                </div>

                <hr>

                <div class="config-section">
                    <h3>Test Information</h3>
                    <div class="test-info-box">
                        <h4>Test Credit Card Details</h4>
                        <p><strong>Card Number:</strong> 4000 0000 0000 0002</p>
                        <p><strong>Expiry Date:</strong> 12/25</p>
                        <p><strong>CVV:</strong> 123</p>
                        <p><strong>Cardholder Name:</strong> Test User</p>
                        <p class="note">Note: Use these details only in test/sandbox mode for testing purposes.</p>
                    </div>
                </div>

                <div class="config-actions">
                    <button type="submit" name="submit_payment_gateway" class="button button-primary">Save Configuration</button>
                    <a href="?page=payment-options" class="button">Cancel</a>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>





<script>
document.addEventListener('DOMContentLoaded', function() {
    // Payment provider item clicks
    document.querySelectorAll('.payment-provider-item').forEach(function(item) {
        item.addEventListener('click', function(e) {
            // Don't trigger if clicking on buttons
            if (e.target.tagName === 'A' || e.target.tagName === 'BUTTON' || e.target.closest('.payment-provider-actions')) {
                return;
            }
            
            const gateway = this.getAttribute('data-gateway');
            if (gateway) {
                window.location.href = '?page=payment-options&gateway=' + gateway;
            }
        });
    });
});
</script>