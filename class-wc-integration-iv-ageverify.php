<?php

include_once('Model/IvavAgeVerify.php');
include_once('Model/IvavAdminSettingsTrait.php');
include_once('Model/IvavAdminTrait.php');
include_once('Model/IvavApiTrait.php');
include_once('Model/IvavCheckoutTrait.php');
include_once('Model/IvavEmailTrait.php');
include_once('Model/IvavJsTrait.php');
include_once('Model/IvavProfileTrait.php');
include_once('Model/IvavStrictTrait.php');
include_once('Model/IvavThankyouTrait.php');

define('IVAV_IS_DEV', '1');
define('IVAV_IS_STAGE', '2');
define('IVAV_ALLOW_EMAIL', '4');
define('IVAV_ALLOW_PHONE', '8');
define('IVAV_FORCE_BILLING', '16');
define('IVAV_FORCE_SHIPPING', '32');
define('IVAV_IS_SANDBOX', '64');
define('IVAV_ERROR', 'Sorry, we are unable to verify you age.');

if (!class_exists('WC_Integration_IV_AgeVerify_Integration')) {
    class WC_Integration_IV_AgeVerify_Integration extends WC_Integration
    {

        use IvavAdminSettingsTrait;
        use IvavAdminTrait;
        use IvavApiTrait;
        use IvavCheckoutTrait;
        use IvavEmailTrait;
        use IvavJsTrait;
        use IvavProfileTrait;
        use IvavStrictTrait;
        use IvavThankyouTrait;

        public function __construct()
        {
            $this->ivav_setup();
        }

        private function ivav_version_check( $version = '3.0' )
        {
            if ( class_exists( 'WooCommerce' ) ) {
                global $woocommerce;
                if ( version_compare( $woocommerce->version, $version, ">=" ) ) {
                    return true;
                }
            }
            return false;
        }
    }
}
?>
