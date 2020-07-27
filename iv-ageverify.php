<?php
/**
 * @package AgeVerify by Inverite
 * @version 2.1.5
 */
/*
Plugin Name: AgeVerify by Inverite
Plugin URI: https://www.inverite.com/age_verify
Description: AgeVerify quickly and conveniently verifies the age of your Canadian customers - keeping you safe from transactions with minors.
Author: Inverite Verification Inc
Version: 2.1.5
Author URI: http://www.inverite.com/
*/


if (!class_exists('WC_Integration_AgeVerify')) {
    class WC_Integration_AgeVerify
    {
        public function __construct()
        {
            add_action('plugins_loaded', array(
                $this,
                'init'
            ));
        }

        public function init()
        {
            if (class_exists('WC_Integration')) {
                include_once 'class-wc-integration-iv-ageverify.php';
                add_filter('woocommerce_integrations', array(
                    $this,
                    'add_integration'
                ));
            }
        }

        public function add_integration($integrations)
        {
            $integrations[] = 'WC_Integration_IV_AgeVerify_Integration';
            return $integrations;
        }
    }
    $WC_Integration_AgeVerify = new WC_Integration_AgeVerify(__FILE__);
}

?>
