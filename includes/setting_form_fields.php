
<?php
$this->form_fields = apply_filters( 'wc_offline_form_fields', array(

                'enabled' => array(
                    'title' => __('Enable/Disable', 'wc-nanswap-pay-gateway'),
                    'type' => 'checkbox',
                    'label' => __('Enable Nanswap Pay', 'wc-nanswap-pay-gateway'),
                    'default' => 'yes',
                ),
                'title' => array(
                    'title' => __('Title', 'wc-nanswap-pay-gateway'),
                    'type' => 'text',
                    'description' => __('Pay with cryptocurrency via Nanswap Pay.', 'wc-nanswap-pay-gateway'),
                    'default' => __('Nanswap Pay', 'wc-nanswap-pay-gateway'),
                    'desc_tip' => true,
                ),
                'description' => array(
                    'title' => __('Description', 'wc-nanswap-pay-gateway'),
                    'type' => 'textarea',
                    'description' => __('This controls the description which the user sees during checkout.', 'wc-nanswap-pay-gateway'),
                    'default' => __('Pay with cryptocurrency via Nanswap Pay', 'wc-nanswap-pay-gateway'),
                ),
                'instructions' => array(
					'title'       => __( 'Instructions', 'wc-nanswap-pay-gateway' ),
					'type'        => 'textarea',
					'description' => __( '', 'wc-nanswap-pay-gateway' ),
					'default'     => '',
					'desc_tip'    => true,
				),
                'webhook_secret' => array(
                    'title' => __('Webhook Secret', 'wc-nanswap-pay-gateway'),
                    'type' => 'password',
                    'description' => __('Please enter your Nanswap Pay Webhook Secret.', 'wc-nanswap-pay-gateway'),
                    'default' => '',
                ),
                'api_key' => array(
                    'title' => __('Public Key', 'wc-nanswap-pay-gateway'),
                    'type' => 'password',
                    'description' => __('Please enter your Nanswap Pay Public Key.', 'wc-nanswap-pay-gateway'),
                    'default' => '',
                ),
                'simple_total' => array(
                    'title' => __('Compatibility Mode', 'wc-nanswap-pay-gateway'),
                    'type' => 'checkbox',
                    'label' => __("This may be needed for compatibility with certain addons if the order total isn't correct.", 'wc-nanswap-pay-gateway'),
                    'default' => '',
                ),
                'invoice_prefix' => array(
                    'title' => __('Invoice Prefix', 'wc-nanswap-pay-gateway'),
                    'type' => 'text',
                    'description' => __('Please enter a prefix for your invoice numbers. If you modify this field, all current pending orders will not be able to update.', 'wc-nanswap-pay-gateway'),
                    'default' => 'WC-',
                    'desc_tip' => true,
                ),
            ) );
