<?php
/**
 * Plugin Name: EcomJoin WooCommerce Integration
 * Plugin URI: https://www.ecomjoin.com
 * Description: Connects your WooCommerce store to EcomJoin SaaS for automated WhatsApp confirmation & AI customer service.
 * Version: 1.0.0
 * Author: EcomJoin
 * Author URI: https://www.ecomjoin.com
 * Text Domain: ecomjoin-wc
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class EcomJoin_WooCommerce_Integration {

    public function __construct() {
        // Add Admin Menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register Settings
        add_action('admin_init', array($this, 'register_settings'));

        // Hook on WooCommerce Order Creation
        add_action('woocommerce_new_order', array($this, 'send_order_to_ecomjoin'), 10, 1);
    }

    // Add Menu under WooCommerce
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            'EcomJoin Settings',
            'EcomJoin AI',
            'manage_options',
            'ecomjoin-settings',
            array($this, 'render_settings_page')
        );
    }

    // Register Plugin Options
    public function register_settings() {
        register_setting('ecomjoin_options_group', 'ecomjoin_webhook_url');
        register_setting('ecomjoin_options_group', 'ecomjoin_api_key');
    }

    // Render Admin Settings Page
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>🚀 EcomJoin WooCommerce Integration</h1>
            <p>قم بربط متجرك مع منصة <a href="https://www.ecomjoin.com" target="_blank">EcomJoin</a> لأتمتة تأكيد الطلبات عبر الواتساب بالذكاء الاصطناعي.</p>
            <hr />
            <form method="post" action="options.php">
                <?php
                settings_fields('ecomjoin_options_group');
                do_settings_sections('ecomjoin_options_group');
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">EcomJoin Webhook URL:</th>
                        <td>
                            <input type="url" name="ecomjoin_webhook_url" value="<?php echo esc_attr(get_option('ecomjoin_webhook_url')); ?>" class="regular-text" placeholder="https://api.ecomjoin.com/api/webhooks/orders" required />
                            <p class="description">ادخل رابط الـ Webhook الخاص بحسابك في منصة EcomJoin.</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">API Key / Secret:</th>
                        <td>
                            <input type="password" name="ecomjoin_api_key" value="<?php echo esc_attr(get_option('ecomjoin_api_key')); ?>" class="regular-text" placeholder="ej_live_..." required />
                        </td>
                    </tr>
                </table>
                <?php submit_button('حفظ الإعدادات'); ?>
            </form>
            <hr />
            <p>ليس لديك حساب؟ <a href="https://www.ecomjoin.com" target="_blank">سجل الآن في EcomJoin</a> وابدأ أتمتة مبيعاتك!</p>
        </div>
        <?php
    }

    // Send Order Payload to EcomJoin
    public function send_order_to_ecomjoin($order_id) {
        $webhook_url = get_option('ecomjoin_webhook_url');
        $api_key = get_option('ecomjoin_api_key');

        if (empty($webhook_url)) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Prepare Items
        $items = array();
        foreach ($order->get_items() as $item) {
            $items[] = array(
                'name'     => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'total'    => $item->get_total(),
            );
        }

        // Prepare Payload
        $payload = array(
            'store_type'    => 'woocommerce',
            'order_id'      => $order->get_id(),
            'customer'      => array(
                'first_name' => $order->get_billing_first_name(),
                'last_name'  => $order->get_billing_last_name(),
                'phone'      => $order->get_billing_phone(),
                'city'       => $order->get_billing_city(),
                'address'    => $order->get_billing_address_1(),
            ),
            'currency'      => $order->get_currency(),
            'total_price'   => $order->get_total(),
            'items'         => $items,
            'created_at'    => $order->get_date_created()->date('Y-m-d H:i:s'),
        );

        // Send HTTP POST Request
        wp_remote_post($webhook_url, array(
            'method'    => 'POST',
            'headers'   => array(
                'Content-Type'  => 'application/json',
                'x-api-key'     => $api_key,
            ),
            'body'      => json_encode($payload),
            'timeout'   => 15,
            'blocking'  => false, // Non-blocking to keep store fast
        ));
    }
}

new EcomJoin_WooCommerce_Integration();