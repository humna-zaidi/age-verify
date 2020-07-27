<?php
trait IvavAdminSettingsTrait {
    public function ivav_setup()
    {
        $this->id = 'wc-integration-iv-ageverify';
        $this->method_title = __('AgeVerify by Inverite', 'wc-integration-iv-ageverify');
        $this->method_description = __('AgeVerify by Inverite', 'wc-integration-iv-ageverify');
        $this->logger = wc_get_logger();

        $this->apiKey     = $this->get_option('apiKey');
        $this->siteName   = $this->get_option('siteName');
        $this->siteId     = $this->get_option('siteId');
        $this->siteKey    = md5($this->siteName . '-' . $this->apiKey);
        $this->allowPhone = 'no'; // $this->get_option('allowPhone');
        $this->allowEmail = 'no'; // $this->get_option('allowEmail');
        $this->forceName  = $this->get_option('forceName');

        $this->verificationMode         = 'thankyou';
        $this->minimumSimilarity        = $this->get_option('similarity');
        $this->customerMessage          = $this->get_option('customerMessage');
        $this->customerMessageAccount   = $this->get_option('customerMessageAccount');
        $this->thankyouInline           = 'popup';
        $this->placeOrderText           = $this->get_option('overridePlaceOrderText');
        $this->reminderDelay            = $this->get_option('reminderDelay') * 60;
        $this->templateTheme            = apply_filters('ivav_template_theme', $this->get_option('templateTheme'));
        # filter

        $this->requiredFields = [];
        if ($this->get_option('requireSelfie') == 'selfie') {
            $this->requiredFields = ['picture', 'id_front', 'id_back'];
        }
        elseif ($this->get_option('requireSelfie') == 'front') {
            $this->requiredFields = ['id_front', 'id_back'];
        }
        elseif ($this->get_option('requireSelfie') == 'back') {
            $this->requiredFields = ['id_back'];
        }
        $this->environment       = $this->get_option('environment');

        $this->isProd     = false;
        $this->isDev      = false;
        $this->isStage    = false;
        $this->isSandbox  = false;
        $path = plugin_dir_path(__FILE__);
        if ($this->environment == 'dev')
        {
            $this->isDev = true;
            $this->hostname = 'https://lloyd.inverite.com';
        }
        elseif ($this->environment == 'www' || $this->environment == 'prod')
        {
            $this->isProd = true;
            $this->hostname = 'https://www.inverite.com';
        }
        elseif ($this->environment == 'sandbox')
        {
            $this->isSandbox = true;
            $this->hostname = 'https://sandbox.inverite.com';
        }
        else {
            $this->isStage = true;
            $this->hostname = 'https://live.inverite.com';
        }

        $this->init_form_fields();
        $this->init_settings();
        $this->setup_events();
    }

    private function setup_events()
    {
        // Admin Options
        add_filter('plugin_action_links', array($this, 'ivav_plugin_add_settings_link'), 10, 2);
        add_action('woocommerce_update_options_integration_wc-integration-iv-ageverify', array(
            $this,
            'process_admin_options'
        ));

        // handle postback
        add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'ivav_handle_callback'));

        if ($this->apiKey == '' || $this->siteName == '')
        {
            $this->logger->error('IV-AV: empty configuration, disabling');
        }
        else
        {
            // include JS
            add_filter(
                'script_loader_tag',
                array(
                    $this,
                    'ivav_apply_js_attributes'
                ),
                10,
                2
            );
            add_action(
                'wp_enqueue_scripts',
                array(
                    $this,
                    'ivav_setup_js'
                )
            );
            // Admin Order Page AgeVerify status
            add_filter('manage_edit-shop_order_columns', array($this, 'ivav_shop_order_columns'));
            add_filter('manage_shop_order_posts_custom_column', array($this, 'ivav_shop_order_posts_custom_column'), 20, 2);
            add_filter('wc_order_statuses', array($this, 'ivav_hide_order_status'));

            add_action('add_meta_boxes', function() {
                add_meta_box('custom_order_option', 'AgeVerify By Inverite', array($this, 'ivav_custom_order_option'), 'shop_order', 'side', 'high');
            });

            // Admin Profile Page AgeVerify status
            add_filter('edit_user_profile', array($this, 'ivav_show_extra_user_fields'));
            add_filter('show_user_profile', array($this, 'ivav_show_extra_user_fields'));

            // Bulk Manually Verify User
            add_filter(
                "bulk_actions-users",
                array(
                    $this,
                    'ivav_register_bulk_verify'
                )
            );
            add_filter(
                'handle_bulk_actions-users',
                array(
                    $this,
                    'ivav_bulk_action_edit_user'
                ),
                10,
                3
            );
            // Bulk Manually Verify Order
            add_filter(
                'bulk_actions-edit-shop_order',
                array(
                    $this,
                    'ivav_register_bulk_verify_order'
                )
            );
            add_filter(
                'handle_bulk_actions-edit-shop_order',
                array(
                    $this,
                    'ivav_bulk_action_edit_order'
                ),
                10,
                3
            );

            // Edit Profile Verification
            if (!in_array($this->siteKey, ['ca15f8bc2637626660651c02bd2f9c17', '5665acdd9a7d7d88cf16ac72bfb3bd65'])) {
                add_action('woocommerce_edit_account_form', array($this, 'ivav_wc_edit_account_form'));
                add_action('woocommerce_before_account_orders', array($this, 'ivav_wc_edit_account_form'));
            }
            add_shortcode('ivav_profile', array($this, 'ivav_profile_shortcode'));
            add_shortcode('ivav_iframe', array($this, 'ivav_iframe_shortcode'));


            add_action('woocommerce_order_actions', array($this, 'ivav_wc_order_actions'));
            add_action('woocommerce_order_action_ivav_wc_order_manual_verify_action', array($this, 'ivav_wc_order_manual_verify_action'));
            add_action('woocommerce_order_action_ivav_wc_order_manual_clear_action',  array($this, 'ivav_wc_order_manual_clear_action'));

            if ($this->forceName != 'none') {
                add_action('woocommerce_before_checkout_form', array($this, 'ivav_before_checkout_form_strict'), 10);
                add_action('woocommerce_checkout_order_processed',
                array(
                    $this,
                    'ivav_checkout_order_processed_strict'
                )
            );
            }

            if ($this->verificationMode == 'checkout' || $this->verificationMode == 'precheckout') {
                if ($this->verificationMode == 'precheckout') {
                    add_action('woocommerce_before_checkout_form', array($this, 'ivav_before_checkout_form'), 10);
                }
                add_action('woocommerce_after_checkout_validation',
                    array(
                        $this,
                        'ivav_after_checkout_validation'
                    )
                );

                add_action('woocommerce_checkout_order_processed',
                    array(
                        $this,
                        'ivav_checkout_order_processed'
                    )
                );
            } elseif ($this->verificationMode == 'thankyou') {
                add_action('woocommerce_thankyou',
                    array(
                        $this,
                        'ivav_thankyou'
                    ), 10, 3
                );
                add_action('woocommerce_email_before_order_table',
                    array(
                        $this,
                        'ivav_thankyou_email_before_order_table'
                    ), 1, 3
                );
                add_action('woocommerce_checkout_order_processed',
                    array(
                        $this,
                        'ivav_checkout_order_processed_skip'
                    )
                );
            } else {
                $this->logger->error('IV-AV: unknown mode ' . $this->verificationMode);
            }
        }

        add_filter( 'woocommerce_order_button_text', array( $this, 'ivav_custom_order_button_text'));

        add_filter('woocommerce_email_classes', array($this, 'ivav_add_custom_email'));
        add_action('ivav_send_reminder', array($this, 'ivav_send_reminder'), 10, 3);
    }

    public function init_form_fields()
    {
        $fields = array(
            'apiKey' => array(
                'title' => __('API Key', 'wc-integration-iv-ageverify'),
                'type' => 'text',
                'description' => __('AgeVerify by Inverite API Key', 'wc-integration-iv-ageverify'),
                'default' => '',
                'desc_tip' => false,
            ),
            'siteId' => array(
                'title' => __('Site ID', 'wc-integration-iv-ageverify'),
                'type' => 'text',
                'description' => __('AgeVerify by Inverite Site ID', 'wc-integration-iv-ageverify'),
                'default' => '',
                'desc_tip' => false,
            ),
            'siteName' => array(
                'title' => __('Site Name', 'wc-integration-iv-ageverify'),
                'type' => 'text',
                'description' => __('AgeVerify by Inverite Site Name', 'wc-integration-iv-ageverify'),
                'default' => '',
                'desc_tip' => false,
            ),
        );

        /*
        if (!in_array($this->forceName, array('shipping', 'billing'))) {
            $fields = array_merge($fields, array(
                'verificationMode' => [
                    'title' => __('Verification Mode', 'wc-integration-iv-ageverify'),
                    'type' => 'select',
                    'description' => __('Verification Mode', 'wc-integration-iv-ageverify'),
                    'default' => 'checkout',
                    'options' => array(
                        //'signup' => 'Signup',
                        'precheckout' => 'Checkout',
                        //'checkout' => 'Checkout',
                        'thankyou' => 'Thank you'
                    ),
                    'desc_tip' => false,
                ]
            ));
        }
        */

        $fields = array_merge($fields, array(
            'requireSelfie' => array(
                'title' => __('Require selfie', 'wc-integration-iv-ageverify'),
                'type' => 'select',
                'description' => __('Require customer selfie.', 'wc-integration-iv-ageverify'),
                'default' => 'yes',
                'options' => array(
                    //'signup' => 'Signup',
                    'selfie' => 'Selfie + ID Front + ID Back',
                    'front' => 'ID Front + ID Back',
                    'back' => 'ID Back'
                ),
                'desc_tip' => false,
            ),
            'forceName' => array(
                'title' => __('Force Name', 'wc-integration-iv-ageverify'),
                'type' => 'select',
                'description' => __('Require name to match identification.', 'wc-integration-iv-ageverify'),
                'default' => 'none',
                'desc_tip' => false,
                'options' => array(
                    'none' => 'None',
                    'billing' => 'Billing Name',
                    'shipping' => 'Shipping Name',
                    'both' => 'Billing & Shipping Name',
                ),
            ),
            'reminderDelay' => array(
                'title' => __('Reminder Email Delay', 'wc-integration-iv-ageverify'),
                'type' => 'text',
                'description' => __('A reminder will be sent in N minutes if age verification is not completed on checkout (0 or blank to disable).', 'wc-integration-iv-ageverify'),
                'default' => '',
                'desc_tip' => false,
            ),
            'templateTheme' => array(
                'title' => __('Theme', 'wc-integration-iv-ageverify'),
                'type' => 'select',
                'description' => __('Theme For Age Verification IFrame', 'wc-integration-iv-ageverify'),
                'default' => '',
                'options' => [
                    '' => 'White',
                    'green' => 'Green',
                    'dark' => 'Dark',
                ],
                'desc_tip' => false,
            ),
            'customerMessage' => array(
                'title' => __('Customer Message Checkout', 'wc-integration-iv-ageverify'),
                'type' => 'textarea',
                'description' => __('Customer Message (Checkout)', 'wc-integration-iv-ageverify'),
                'default' => '',
                'desc_tip' => false,
            ),
            'customerMessageAccount' => array(
                'title' => __('Customer Message Account', 'wc-integration-iv-ageverify'),
                'type' => 'textarea',
                'description' => __('Customer Message (Account)', 'wc-integration-iv-ageverify'),
                'default' => '',
                'desc_tip' => false,
            ),
            'overridePlaceOrderText' => array(
                'title' => __('Conditional Place Order Button Text', 'wc-integration-iv-ageverify'),
                'type' => 'textarea',
                'description' => __('Conditional Place Order Button Text', 'wc-integration-iv-ageverify'),
                'default' => '',
                'desc_tip' => false,
            ),

        ));
        if (in_array('picture', $this->requiredFields)) {
            $fields['similarity'] = [
                'title' => __('Selfie Minimum Similarity', 'wc-integration-iv-ageverify'),
                'type' => 'text',
                'description' => __('A warning will be added to orders if the selfie to ID match is below this %.', 'wc-integration-iv-ageverify'),
                'default' => '50',
                'desc_tip' => false,
            ];
        }

        $fields['environment'] = [
            'title' => __('Devops Mode', 'wc-integration-iv-ageverify'),
            'type' => 'select',
            'description' => __('Devops Mode (Do not change).', 'wc-integration-iv-ageverify'),
            'default' => 'live',
            'options' => array(
                'dev' => 'Development',
                'live' => 'Latest',
                'prod' => 'Stable',
                'sandbox' => 'Sandbox',
            ),
            'desc_tip' => false,
        ];
            /*
            'allowEmail' => array(
                'title' => __('Allow Email Address', 'wc-integration-iv-ageverify'),
                'type' => 'checkbox',
                'description' => __('Allow customer email address to be passed.', 'wc-integration-iv-ageverify'),
                'default' => 'no',
                'desc_tip' => false,
            ),
            'allowPhone' => array(
                'title' => __('Allow Phone Number', 'wc-integration-iv-ageverify'),
                'type' => 'checkbox',
                'description' => __('Allow customer phone number to be passed.', 'wc-integration-iv-ageverify'),
                'default' => 'no',
                'desc_tip' => false,
            ),
            */


        $this->form_fields = $fields;
    }

    public function ivav_plugin_add_settings_link($actions, $file)
    {
        if ( $file != 'iv-ageverify/iv-ageverify.php')
            return $actions;

        $settings_link = '<a href="admin.php?page=wc-settings&tab=integration">' . __('Settings') . '</a>';
        array_push($actions, $settings_link);
        return $actions;
    }
}
