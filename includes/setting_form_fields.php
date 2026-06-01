<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$this->form_fields = apply_filters( 'nanswap_pay_form_fields', array(

                'enabled' => array(
                    'title' => __('Enable/Disable', 'nanswap-pay-woocommerce-main'),
                    'type' => 'checkbox',
                    'label' => __('Enable Nanswap Pay', 'nanswap-pay-woocommerce-main'),
                    'default' => 'yes',
                ),
                'title' => array(
                    'title' => __('Title', 'nanswap-pay-woocommerce-main'),
                    'type' => 'text',
                    'description' => __('Pay with cryptocurrency via Nanswap Pay.', 'nanswap-pay-woocommerce-main'),
                    'default' => __('Nanswap Pay', 'nanswap-pay-woocommerce-main'),
                    'desc_tip' => true,
                ),
                'description' => array(
                    'title' => __('Description', 'nanswap-pay-woocommerce-main'),
                    'type' => 'textarea',
                    'description' => __('This controls the description which the user sees during checkout.', 'nanswap-pay-woocommerce-main'),
                    'default' => __('Pay with cryptocurrency via Nanswap Pay', 'nanswap-pay-woocommerce-main'),
                ),
                'instructions' => array(
					'title'       => __( 'Instructions', 'nanswap-pay-woocommerce-main' ),
					'type'        => 'textarea',
					'description' => '',
					'default'     => '',
					'desc_tip'    => true,
				),
                'webhook_secret' => array(
                    'title' => __('Webhook Secret', 'nanswap-pay-woocommerce-main'),
                    'type' => 'password',
                    'description' => __('Please enter your Nanswap Pay Webhook Secret.', 'nanswap-pay-woocommerce-main'),
                    'default' => '',
                ),
                'api_key' => array(
                    'title' => __('Public Key', 'nanswap-pay-woocommerce-main'),
                    'type' => 'password',
                    'description' => __('Please enter your Nanswap Pay Public Key.', 'nanswap-pay-woocommerce-main'),
                    'default' => '',
                ),
                'simple_total' => array(
                    'title' => __('Compatibility Mode', 'nanswap-pay-woocommerce-main'),
                    'type' => 'checkbox',
                    'label' => __("This may be needed for compatibility with certain addons if the order total isn't correct.", 'nanswap-pay-woocommerce-main'),
                    'default' => '',
                ),
                'invoice_prefix' => array(
                    'title' => __('Invoice Prefix', 'nanswap-pay-woocommerce-main'),
                    'type' => 'text',
                    'description' => __('Please enter a prefix for your invoice numbers. If you modify this field, all current pending orders will not be able to update.', 'nanswap-pay-woocommerce-main'),
                    'default' => 'WC-',
                    'desc_tip' => true,
                ),
            ) );
