<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

class Nanswap_Pay_Blocks_Support extends AbstractPaymentMethodType {

    protected $name = 'nanswap_pay_gateway';

    private $gateway;

    public function initialize() {
        $this->settings = get_option( 'woocommerce_nanswap_pay_gateway_settings', array() );
        $gateways       = WC()->payment_gateways->payment_gateways();
        $this->gateway  = isset( $gateways[ $this->name ] ) ? $gateways[ $this->name ] : null;
    }

    public function is_active() {
        return $this->gateway ? $this->gateway->is_available() : false;
    }

    public function get_payment_method_script_handles() {
        wp_register_script(
            'wc-nanswap-pay-blocks-integration',
            NANSWAP_PAY_FOR_WOOCOMMERCE_ASSET_URL . 'assets/js/nanswap-pay-blocks.js',
            array( 'wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-html-entities' ),
            NANSWAP_PAY_VERSION,
            true
        );
        return array( 'wc-nanswap-pay-blocks-integration' );
    }

    public function get_payment_method_data() {
        return array(
            'title'       => $this->gateway ? $this->gateway->get_option( 'title' ) : '',
            'description' => $this->gateway ? $this->gateway->get_option( 'description' ) : '',
            'icon'        => $this->gateway ? $this->gateway->icon : '',
            'supports'    => $this->gateway
                ? array_filter( $this->gateway->supports, array( $this->gateway, 'supports' ) )
                : array(),
        );
    }
}
