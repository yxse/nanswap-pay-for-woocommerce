<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$this->form_fields = apply_filters( 'nanswap_pay_form_fields', array(

                'enabled' => array(
                    'title' => __('Enable/Disable', 'nanswap-pay-for-woocommerce-main'),
                    'type' => 'checkbox',
                    'label' => __('Enable Nanswap Pay', 'nanswap-pay-for-woocommerce-main'),
                    'default' => 'yes',
                ),
                'title' => array(
                    'title' => __('Title', 'nanswap-pay-for-woocommerce-main'),
                    'type' => 'text',
                    'description' => __('Pay with cryptocurrency via Nanswap Pay.', 'nanswap-pay-for-woocommerce-main'),
                    'default' => __('Nanswap Pay', 'nanswap-pay-for-woocommerce-main'),
                    'desc_tip' => true,
                ),
                'description' => array(
                    'title' => __('Description', 'nanswap-pay-for-woocommerce-main'),
                    'type' => 'textarea',
                    'description' => __('This controls the description which the user sees during checkout.', 'nanswap-pay-for-woocommerce-main'),
                    'default' => __('Pay with cryptocurrency via Nanswap Pay', 'nanswap-pay-for-woocommerce-main'),
                ),
                'instructions' => array(
					'title'       => __( 'Instructions', 'nanswap-pay-for-woocommerce-main' ),
					'type'        => 'textarea',
					'description' => '',
					'default'     => '',
					'desc_tip'    => true,
				),
                'webhook_secret' => array(
                    'title' => __('Webhook Secret', 'nanswap-pay-for-woocommerce-main'),
                    'type' => 'password',
                    'description' => __('Please enter your Nanswap Pay Webhook Secret.', 'nanswap-pay-for-woocommerce-main'),
                    'default' => '',
                ),
                'api_key' => array(
                    'title' => __('Public Key', 'nanswap-pay-for-woocommerce-main'),
                    'type' => 'password',
                    'description' => __('Please enter your Nanswap Pay Public Key.', 'nanswap-pay-for-woocommerce-main'),
                    'default' => '',
                ),
                'simple_total' => array(
                    'title' => __('Compatibility Mode', 'nanswap-pay-for-woocommerce-main'),
                    'type' => 'checkbox',
                    'label' => __("This may be needed for compatibility with certain addons if the order total isn't correct.", 'nanswap-pay-for-woocommerce-main'),
                    'default' => '',
                ),
                'invoice_prefix' => array(
                    'title' => __('Invoice Prefix', 'nanswap-pay-for-woocommerce-main'),
                    'type' => 'text',
                    'description' => __('Please enter a prefix for your invoice numbers. If you modify this field, all current pending orders will not be able to update.', 'nanswap-pay-for-woocommerce-main'),
                    'default' => 'WC-',
                    'desc_tip' => true,
                ),
            ) );
