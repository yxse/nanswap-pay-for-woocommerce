<?php

/**
 * @wordpress-plugin
 * Plugin Name:             Nanswap Pay Gateway for WooCommerce
 * Plugin URI:              https://nanswap.com/pay
 * Description:             Cryptocurrency Payment Gateway powered by Nanswap Pay.
 * Version:                 1.0.0
 * Author:                  Nanswap
 * Author URI:              https://nanswap.com/
 * License:                 proprietary
 * Text Domain:             wc-nanswap-pay-gateway
 * Requires at least:       5.5
 * Tested up to:            7.0
 * WC requires at least:    4.9.4
 * WC tested up to:         10.8.1
 *
 */

/**
 * Exit if accessed directly.
 */
if (!defined('ABSPATH'))
{
    exit();
}

if (version_compare(phpversion(), '7.1', '>=')) {
    ini_set('precision', 14);
    ini_set('serialize_precision', 14);
}



if (!defined('NANSWAP_PAY_FOR_WOOCOMMERCE_PLUGIN_DIR')) {
    define('NANSWAP_PAY_FOR_WOOCOMMERCE_PLUGIN_DIR', dirname(__FILE__));
}
if (!defined('NANSWAP_PAY_FOR_WOOCOMMERCE_ASSET_URL')) {
    define('NANSWAP_PAY_FOR_WOOCOMMERCE_ASSET_URL', plugin_dir_url(__FILE__));
}
if (!defined('VERSION_PFW')) {
    define('VERSION_PFW', '1.0.0');
}


/**
 * Add the gateway to WC Available Gateways
 *
 * @since 1.0.0
 * @param array $gateways all available WC gateways
 * @return array $gateways all WC gateways + offline gateway
 */
function wc_nanswap_pay_add_to_gateways( $gateways ) {
    if (!in_array('WC_Gateway_Nanswap_Pay', $gateways)) {
        $gateways[] = 'WC_Gateway_Nanswap_Pay';
    }
	return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'wc_nanswap_pay_add_to_gateways' );


/**
 * Adds plugin page links
 *
 * @since 1.0.0
 * @param array $links all plugin links
 * @return array $links all plugin links + our custom links (i.e., "Settings")
 */
function wc_nanswap_pay_gateway_plugin_links( $links ) {

	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=nanswap_pay_gateway' ) . '">' . __( 'Configure', 'wc-nanswap-pay-gateway' ) . '</a>',
        '<a href="mailto:contact@nanswap.com">' . __( 'Email Support', 'wc-nanswap-pay-gateway' ) . '</a>'
	);

	return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_nanswap_pay_gateway_plugin_links' );


// Declare compatibility with WooCommerce HPOS and Checkout Blocks (required for WC 7+)
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
    }
} );

// Register payment method with WooCommerce Checkout Block
add_action( 'woocommerce_blocks_loaded', function() {
    if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
        return;
    }
    require_once NANSWAP_PAY_FOR_WOOCOMMERCE_PLUGIN_DIR . '/includes/class-nanswap-pay-blocks-support.php';
    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $registry ) {
            $registry->register( new WC_Gateway_Nanswap_Pay_Blocks_Support() );
        }
    );
} );


/**
 * Nanswap Pay Payment Gateway
 *
 *
 * @class 		WC_Gateway_Nanswap_Pay
 * @extends		WC_Payment_Gateway
 * @version		1.0.0
 * @package		WooCommerce/Classes/Payment
 * @author 		Nanswap
 */
add_action('plugins_loaded', 'wc_nanswap_pay_gateway_init', 11);
function wc_nanswap_pay_gateway_init()
{

    if (!class_exists('WC_Payment_Gateway')) {
        // oops!
        return;
    }

    class WC_Gateway_Nanswap_Pay extends WC_Payment_Gateway
    {
        var $webhook_url;

        /**
         * Constructor for the gateway.
         *
         * @access public
         * @return void
         */
        public function __construct()
        {
            global $woocommerce;
            $this->id = 'nanswap_pay_gateway';
            $this->icon = apply_filters('woocommerce_nanswap_pay_icon', 'https://images.nanswap.com/logo/pay-in-crypto-white.svg');
            $this->has_fields = false;
            $this->method_title = __('Nanswap Pay', 'wc-nanswap-pay-gateway');
            $this->method_description = __( 'Allows Cryptocurrency payments via Nanswap Pay.', 'wc-nanswap-pay-gateway' );
            $this->webhook_url = add_query_arg('wc-api', 'WC_Gateway_Nanswap_Pay', home_url('/'));

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->instructions = $this->get_option( 'instructions', $this->description );
            $this->webhook_secret = $this->get_option('webhook_secret');
            $this->api_key = $this->get_option('api_key');
            $this->debug_email = $this->get_option('debug_email');
            $this->debug_post_url = $this->get_option('debug_post_url');
            $this->allow_zero_confirm = $this->get_option('allow_zero_confirm') == 'yes' ? true : false;
            $this->form_submission_method = $this->get_option('form_submission_method') == 'yes' ? true : false;
            $this->invoice_prefix = $this->get_option('invoice_prefix', 'WC-');
            $this->simple_total = $this->get_option('simple_total') == 'yes' ? true : false;

            // Logs
            $this->log = new WC_Logger();

            // Actions
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
			add_action('woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
            add_action('woocommerce_api_wc_gateway_nanswap_pay', array($this, 'check_webhook_response'));

            // Customer Emails
			add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );

            if (!$this->is_valid_for_use()) {
                $this->enabled = false;
            }
        }


        /**
         * @return string
         */
        public function get_icon() {
            $icon_html = '<img src="https://images.nanswap.com/logo/pay-in-crypto-white.svg" alt="Pay in crypto using Nanswap Pay" style="width: 200px;" />';
            return apply_filters( 'woocommerce_gateway_icon', $icon_html, $this->id );
        }

        /**
         * Initialise Gateway Settings Form Fields
         *
         * @access public
         * @return void
         */
        function init_form_fields()
        {
            require_once( 'includes/setting_form_fields.php' );
        }

        private function does_not_end_with_number($string) {
            $this->log('does_not_end_with_number:'.$string);
            return !preg_match('/\d$/', $string);
        }

        private function extract_ending_number($string) {
            if (preg_match('/(\d+)$/', $string, $matches)) {
                return $matches[1];
            }
            return null;
        }

        public function validate_invoice_prefix_field( $key, $value ) {
            if ( isset( $value ) && !$this->does_not_end_with_number($value)) {
                WC_Admin_Settings::add_error( esc_html__( 'Invoice prefix must not end with a digit.', 'wc-nanswap-pay-gateway'));
            }

            return $value;
        }


        /**
		 * Output for the order received page.
		 */
		public function thankyou_page() {
			if ( $this->instructions ) {
				echo wpautop( wptexturize( $this->instructions ) );
			}

        }

        private function log($message) {
            // for debug purposes
            // file_put_contents('log.log', $message . "\n", FILE_APPEND);
            return true;
        }

		/**
		 * Add content to the WC emails.
		 *
		 * @access public
		 * @param WC_Order $order
		 * @param bool $sent_to_admin
		 * @param bool $plain_text
		 */
		public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {

			if ( $this->instructions && ! $sent_to_admin && $this->id === $order->payment_method && $order->has_status( 'on-hold' ) ) {
				echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
			}
		}


        /**
         * Process the payment and return the result
         *
         * @access public
         * @param int $order_id
         * @return array
         */
        function process_payment($order_id)
        {
            $order = wc_get_order($order_id);
            $redirect_url= $this->generate_nanswap_pay_url($order);

            return array(
                'result' => 'success',
                'redirect' => $redirect_url
            );

        }


         /**
         * Generate the Nanswap Pay button link
         *
         * @access public
         * @param mixed $order_id
         * @return string
         */
        function generate_nanswap_pay_url($order)
        {
            global $woocommerce;

            if ($order->status != 'completed' && get_post_meta($order->id, 'nanswap_pay payment complete', true) != 'Yes') {
                $order->add_order_note('Customer is being redirected to Nanswap Pay...');
                $order->update_status('pending', 'Customer is being redirected to Nanswap Pay...');
            }

            $nanswap_pay_adr = "https://nanswap.com/pay/invoice?data=";
            $nanswap_pay_args = $this->get_nanswap_pay_args($order);
            $nanswap_pay_adr .= urlencode(json_encode($nanswap_pay_args));
            return $nanswap_pay_adr;
        }


        /**
         * Get Nanswap Pay Args
         *
         * @access public
         * @param mixed $order
         * @return array
         */
        function get_nanswap_pay_args($order)
        {
            global $woocommerce;

            $order_id = $order->id;

            if (in_array($order->billing_country, array('US', 'CA'))) {
                $order->billing_phone = str_replace(array('( ', '-', ' ', ' )', '.'), '', $order->billing_phone);
            }

            $nanswap_pay_args = array(
                'dataSource' => "woocommerce",
                'callbackUrl' => $this->webhook_url,
                'priceCurrency' => $order->get_currency(),
                'successUrl' => $this->get_return_url($order),
                'cancelUrl' => esc_url_raw($order->get_cancel_order_url_raw()),

                // Order key + ID
                'invoiceId' => $this->invoice_prefix . $order->get_order_number(),
                'publicKey' => $this->api_key,

                // Billing Address info
                'customerName' => $order->billing_first_name,
                'customerEmail' => $order->billing_email,
                'shopName' => get_bloginfo('name'),
            );

            if ($this->simple_total) {
                $nanswap_pay_args['priceAmount'] = number_format($order->get_total(), 8, '.', '');
                $nanswap_pay_args['tax'] = 0.00;
                $nanswap_pay_args['shipping'] = 0.00;
            } else if (wc_tax_enabled() && wc_prices_include_tax()) {
                $nanswap_pay_args['priceAmount'] = number_format($order->get_total(), 8, '.', '');
                $nanswap_pay_args['shipping'] = number_format($order->get_total_shipping() + $order->get_shipping_tax(), 8, '.', '');
                $nanswap_pay_args['tax'] = 0.00;
            } else {
                $nanswap_pay_args['priceAmount'] = number_format($order->get_total(), 8, '.', '');
                $nanswap_pay_args['shipping'] = number_format($order->get_total_shipping(), 8, '.', '');
                $nanswap_pay_args['tax'] = $order->get_total_tax();
            }
            $order_cur = wc_get_order($order_id);
            $items_cur = $order_cur->get_items();
            $items = [];
            foreach ($items_cur as $item_id => $item) {
                $items[] = $item->get_data();
            }
            $nanswap_pay_args["products"] = $items;
            $nanswap_pay_args = apply_filters('woocommerce_nanswap_pay_args', $nanswap_pay_args);

            return $nanswap_pay_args;
        }

        /**
         * Get Nanswap Pay invoice params
         *
         * @access public
         * @param mixed $order
         * @return array
         */
        function get_np_invoice_args($order)
        {
            global $woocommerce;

            $order_id = $order->id;

            if (in_array($order->billing_country, array('US', 'CA'))) {
                $order->billing_phone = str_replace(array('( ', '-', ' ', ' )', '.'), '', $order->billing_phone);
            }

            $php_version = phpversion();
            $wp_version = get_bloginfo('version');

            if (class_exists('WooCommerce')) {
                $wc_version = WC()->version;
            } else {
                $wc_version = 'null';
            }

            $invoice_args = array(
                'source' => "woocommerce_" . "php" .$php_version . "_wp" . $wp_version . "_wc" . $wc_version,
                'callback_url' => $this->webhook_url,
                'price_currency' => $order->get_currency(),
                'success_url' => $this->get_return_url($order),
                'cancel_url' => esc_url_raw($order->get_cancel_order_url_raw()),

                // Order key + ID
                'order_id' => $this->invoice_prefix . $order->get_order_number(),
                'order_description' => $order->billing_first_name . '-' . $order->billing_email,
                'price_amount' => number_format($order->get_total(), 8, '.', '')
            );

            $description = [
              'customerName' => $order->billing_first_name,
              'customerEmail' => $order->billing_email,
            ];

            if ($this->simple_total) {
                $description['tax'] = 0.00;
                $description['shipping'] = 0.00;
            } else if (wc_tax_enabled() && wc_prices_include_tax()) {
                $description['shipping'] = number_format($order->get_total_shipping() + $order->get_shipping_tax(), 8, '.', '');
                $description['tax'] = 0.00;
            } else {
                $description['shipping'] = number_format($order->get_total_shipping(), 8, '.', '');
                $description['tax'] = $order->get_total_tax();
            }
            $order_cur = wc_get_order($order_id);
            $items_cur = $order_cur->get_items();
            $items = [];
            foreach ($items_cur as $item_id => $item) {
                $items[] = $item->get_data();
            }
            $invoice_args['order_description'] = json_encode($description);
            $invoice_args = apply_filters('woocommerce_nanswap_pay_args', $invoice_args);

            return $invoice_args;
        }



        /**
         * Check if this gateway is enabled and available in the user's country
         *
         * @access public
         * @return bool
         */
        function is_valid_for_use()
        {
            return true;
        }




        /**
         * Admin Panel Options
         * - Options for bits like 'title' and availability on a country-by-country basis
         * @since 1.0.0
         */
        public function admin_options()
        {
            ?>
            <h3><?php _e('Nanswap Pay', 'woocommerce'); ?></h3>
            <p><?php _e('Completes checkout via Nanswap Pay', 'woocommerce'); ?></p>

            <?php if ($this->is_valid_for_use()) : ?>

                <table class="form-table">
                    <?php
                    $this->generate_settings_html();
                    ?>
                </table>
                <!--/.form-table-->

            <?php else : ?>
                <div class="inline error">
                    <p><strong><?php _e('Gateway Disabled', 'woocommerce'); ?></strong>: <?php _e('Nanswap Pay does not support your store currency.', 'woocommerce'); ?></p>
                </div>
            <?php endif;

        }

        function get_np_webhook_signature() {
            $this->log('$_SERVER:'.print_r($_SERVER, true));

            if (isset($_SERVER['HTTP_X_NANSWAP_SIG']) && !empty($_SERVER['HTTP_X_NANSWAP_SIG'])) {
                return trim($_SERVER['HTTP_X_NANSWAP_SIG']);
            }

            $all_headers = getallheaders();
            $this->log('$all_headers:'.print_r($all_headers, true));

            foreach ($all_headers as $key => $value) {
                $this->log('header:'."$key - $value");
                if (strtoupper($key) == 'X_NANSWAP_SIG') {
                    return trim($value);
                }
            }
            return false;
        }

        private function lookup_order($np_order_id) {
            $this->log('lookup_order:'. $np_order_id);
            $valid_order_id = str_replace($this->invoice_prefix, "", $np_order_id);
            $this->log('valid_order_id:'. $valid_order_id);
            $order = new WC_Order($valid_order_id);

            if ($order !== false) {
                return $order;
            }

            // try parse valid order id once again
            $valid_order_id = $this->extract_ending_number($np_order_id);
            $this->log('after extract_ending_number:'. $valid_order_id);
            $order = new WC_Order($valid_order_id);

            if ($order !== false) {
                return $order;
            }
            $this->log('order not found:');
            return false;
        }


        /**
         * Check Nanswap Pay Webhook validity
         **/
        function check_webhook_request_is_valid()
        {

            global $woocommerce;

            $order = false;
            $error_msg = "Unknown error";
            $auth_ok = false;
            $request_data = null;

            $received_hmac = $this->get_np_webhook_signature();

            $this->log('$received_hmac:'.$received_hmac);

            if ($received_hmac != false && $received_hmac !== '') {
                $request_json = file_get_contents('php://input');
                $this->log('$request_json:'.$request_json);
                $request_data = json_decode($request_json, true);
                $this->log('$request_data:'.print_r($request_data, true));
                ksort($request_data);
                $sorted_request_json = json_encode($request_data);
                $this->log('$sorted_request_json:'.print_r($sorted_request_json, true));

                if ($request_json !== false && !empty($request_json)) {
                    $hmac = hash_hmac("sha512", $sorted_request_json, trim($this->webhook_secret));
                    $this->log('$calculated hmac:'.$hmac);
                    if ($hmac == $received_hmac) {
                        $auth_ok = true;
                    } else {
                        $error_msg = 'HMAC signature does not match';
                    }
                } else {
                    $error_msg = 'Error reading POST data';
                }
            } else {
                $error_msg = 'No HMAC signature sent.';
            }

            if ($auth_ok) {
                $order = $this->lookup_order($request_data["order_id"]);

                if ($order !== false) {
                    $payment_currency = strtoupper($request_data["payout_currency"]);
                    if ($payment_currency == ($order->get_currency() || $payment_currency)) {
                        if ($request_data["payout_amount"] >= $order->get_total()) {
                            print "IPN check OK\n";
                            return true;
                        } else {
                            $error_msg = "Amount received is less than the total!";
                        }
                    } else {
                        $error_msg = "Original currency doesn't match!";
                    }
                } else {
                    $error_msg = "Could not find order info for order ";
                }
            }

            $report = "Error Message: " . $error_msg . "\n\n";

            if ($order) {
                $order->update_status('on-hold', sprintf(__('Nanswap Pay Webhook Error: %s', 'wc-nanswap-pay-gateway'), $error_msg));
            }



            if (!empty($this->debug_email)) {
                mail($this->debug_email, "Report", $report);
            };
            die('Error: ' . $error_msg);
            return false;
        }


        /**
         * Successful Payment!
         *
         * @access public
         * @param array $posted
         * @return void
         */
        function successful_request()
        {
            global $woocommerce;

            $request_json = file_get_contents('php://input');
            $request_data = json_decode($request_json, true);
            $order = $this->lookup_order($request_data["order_id"]);

            $order_status = $order->get_status();

            if ($request_data["status"] == "completed") {
                $order->update_status('completed', 'Order has been paid.');
            } else if ($request_data["status"] == "partially_paid") {
                $order->update_status('on-hold', 'Order is holded.');
                $order->add_order_note('Your payment is partially paid. Please contact contact@nanswap.com Amount received: ' . $request_data["actually_paid"] . " " . $request_data["payout_currency"]);
            } else if ($request_data["status"] == "confirming") {
                $order->update_status('processing', 'Order is processing.');
            } else if ($request_data["status"] == "confirmed") {
                $order->update_status('processing', 'Order is processing.');
            } else if ($request_data["status"] == "sending") {
                $order->update_status('processing', 'Order is processing.');
            } else if ($request_data["status"] == "failed") {
                $order->update_status('on-hold', 'Order is failed. Please contact contact@nanswap.com');
            }

            $order->add_order_note('Nanswap Pay Payment Status: ' . $request_data["status"]);
        }

        /**
         * Check for Nanswap Pay Webhook Response
         *
         * @access public
         * @return void
         */
        function check_webhook_response()
        {
            @ob_clean();
            if ($this->check_webhook_request_is_valid()) {
                $this->successful_request($_POST);
            } else {
                wp_die("Nanswap Pay Webhook Request Failure");
            }
        }
    }

}
