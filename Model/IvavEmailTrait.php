<?php
trait IvavEmailTrait {
    public function ivav_add_custom_email($email_classes)
    {
        require_once(__DIR__ . '/../class-wc-ageverify-reminder-email.php');

        $email_classes['WC_AgeVerify_Reminder_Email'] = new WC_AgeVerify_Reminder_Email();

        return $email_classes;
    }

    public function ivav_send_reminder($email, $guid, $orderId)
    {
        if ($email == '' || $guid == '' || $guid == 'manual') return;

        if (in_array($this->siteKey, ['ca15f8bc2637626660651c02bd2f9c17', '5665acdd9a7d7d88cf16ac72bfb3bd65'])) {

            $order = new WC_Order($orderId);
            $status = $order->get_status();
            if (!in_array($status, array('pending-age', 'onhold-age'))) {
                return;
            }
        }
        $user = get_user_by('email', $email);
        if ($user != null) {
            $userId = $user->ID;
            if ($userId > 0) {
                $guidUser = get_user_meta($userId, 'ivav-guid', true);
                $response = $this->ivav_api_check($guidUser);
                if ($response['status'] != 'Not Started') {
                    return;
                }
            }
        }

        $response = $this->ivav_api_check($guid);
        if ($response['status'] != 'Not Started') {
            return;
        }
        $url = $this->hostname . '/customer/web/start_age/' . $guid . '/' . $this->templateTheme;

        $mailer = WC()->mailer();

        do_action( 'ivav_trigger_inverite_reminder_email', $email, $url);
    }
}
